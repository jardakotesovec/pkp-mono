<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @brief Gateway component of web feed plugin
 *
 */

namespace APP\plugins\generic\webFeed;

use APP\core\Request;
use APP\facades\Repo;
use APP\server\Section;
use APP\server\SectionDAO;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Exception;
use PKP\category\Category;
use PKP\core\Registry;
use PKP\db\DAORegistry;

class WebFeedGatewayPlugin extends \PKP\plugins\GatewayPlugin
{
    public const ATOM = 'atom';
    public const RSS = 'rss';
    public const RSS2 = 'rss2';

    public const FEED_MIME_TYPE = [
        self::ATOM => 'application/atom+xml',
        self::RSS => 'application/rdf+xml',
        self::RSS2 => 'application/rss+xml'
    ];

    public const DEFAULT_RECENT_ITEMS = 30;

    protected WebFeedPlugin $parentPlugin;

    /**
     * Constructor
     */
    public function __construct(WebFeedPlugin $parentPlugin)
    {
        parent::__construct();
        $this->parentPlugin = $parentPlugin;
    }

    /**
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement(): bool
    {
        return true;
    }

    /**
     * Get the name of this plugin. The name must be unique within its category.
     */
    public function getName(): string
    {
        return substr(static::class, strlen(__NAMESPACE__) + 1);
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.webfeed.description');
    }

    /**
     * Override the builtin to get the correct plugin path.
     *
     */
    public function getPluginPath(): string
    {
        return $this->parentPlugin->getPluginPath();
    }

    /**
     * Get whether or not this plugin is enabled. (Should always return true, as the
     * parent plugin will take care of loading this one when needed)
     *
     * @param int $contextId Context ID (optional)
     *
     */
    public function getEnabled($contextId = null): bool
    {
        return $this->parentPlugin->getEnabled($contextId);
    }

    /**
     * Handle fetch requests for this plugin.
     *
     * @param array $args Arguments.
     * @param Request $request Request object.
     */
    public function fetch($args, $request): bool
    {
        $server = $request->getServer();
        if (!$server || !$this->parentPlugin->getEnabled($server->getId())) {
            return false;
        }

        // Make sure the feed type is specified and valid
        $feedType = array_shift($args);
        if (!in_array($feedType, array_keys(static::FEED_MIME_TYPE))) {
            throw new Exception('Invalid feed format');
        }

        // Get limit setting from web feeds plugin
        $recentItems = (int) $this->parentPlugin->getSetting($server->getId(), 'recentItems');
        if ($recentItems < 1) {
            $recentItems = static::DEFAULT_RECENT_ITEMS;
        }
        $includeIdentifiers = (bool) $this->parentPlugin->getSetting($server->getId(), 'includeIdentifiers');

        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$server->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->limit($recentItems)
            ->orderBy(Collector::ORDERBY_LAST_MODIFIED, Collector::ORDER_DIR_DESC)
            ->getMany();

        $latestDate = $submissions->first()?->getData('lastModified');
        $submissions = $submissions->map(fn (Submission $submission) => ['submission' => $submission, 'identifiers' => $this->getIdentifiers($submission)]);
        $userGroups = Repo::userGroup()->getCollector()->filterByContextIds([$server->getId()])->getMany();

        TemplateManager::getManager($request)
            ->assign(
                [
                    'systemVersion' => Registry::get('appVersion'),
                    'submissions' => $submissions,
                    'server' => $server,
                    'latestDate' => $latestDate,
                    'feedUrl' => $request->getRequestUrl(),
                    'userGroups' => $userGroups,
                    'includeIdentifiers' => $includeIdentifiers
                ]
            )
            ->setHeaders(['content-type: ' . static::FEED_MIME_TYPE[$feedType] . '; charset=utf-8'])
            ->display($this->parentPlugin->getTemplateResource("{$feedType}.tpl"));

        return true;
    }

    /**
     * Retrieves the identifiers assigned to a submission
     *
     * @return array<array{'type':string,'label':string,'values':string[]}>
     */
    private function getIdentifiers(Submission $submission): array
    {
        $identifiers = [];
        if ($section = $this->getSection($submission->getSectionId())) {
            $identifiers[] = ['type' => 'section', 'label' => __('section.section'), 'values' => [$section->getLocalizedTitle()]];
        }

        $publication = $submission->getCurrentPublication();
        $categories = Repo::category()->getCollector()
            ->filterByPublicationIds([$publication->getId()])
            ->getMany()
            ->map(fn (Category $category) => $category->getLocalizedTitle())
            ->toArray();
        if (count($categories)) {
            $identifiers[] = ['type' => 'category', 'label' => __('category.category'), 'values' => $categories];
        }

        foreach (['keywords' => 'common.keywords', 'subjects' => 'common.subjects', 'disciplines' => 'search.discipline'] as $field => $label) {
            $values = $publication->getLocalizedData($field) ?? [];
            if (count($values)) {
                $identifiers[] = ['type' => $field, 'label' => __($label), 'values' => $values];
            }
        }

        return $identifiers;
    }

    /**
     * Retrieves a section
     */
    private function getSection(?int $sectionId): Section
    {
        static $sections = [];
        /** @var SectionDAO */
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        return $sectionId
            ? $sections[$sectionId] ??= $sectionDao->getById($sectionId)
            : null;
    }
}
