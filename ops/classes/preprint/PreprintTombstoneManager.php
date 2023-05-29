<?php

/**
 * @file classes/preprint/PreprintTombstoneManager.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreprintTombstoneManager
 *
 * @ingroup preprint
 *
 * @brief Class defining basic operations for preprint tombstones.
 */

namespace APP\preprint;

use APP\core\Application;
use APP\facades\Repo;
use APP\section\Section;
use APP\submission\Submission;
use PKP\config\Config;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\oai\OAIUtils;
use PKP\plugins\Hook;
use PKP\tombstone\DataObjectTombstoneDAO;

class PreprintTombstoneManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    public function insertPreprintTombstone($preprint, $context, Section $section)
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        // delete preprint tombstone -- to ensure that there aren't more than one tombstone for this preprint

        $tombstoneDao->deleteByDataObjectId($preprint->getId());
        // insert preprint tombstone
        $setSpec = $context->getPath() . ':' . OAIUtils::toValidSetSpec($section->getLocalizedAbbrev());
        $oaiIdentifier = 'oai:' . Config::getVar('oai', 'repository_id') . ':' . 'preprint/' . $preprint->getId();
        $oaiSetObjectIds = [
            Application::ASSOC_TYPE_SERVER => $context->getId(),
            Application::ASSOC_TYPE_SECTION => $section->getId(),
        ];

        $preprintTombstone = $tombstoneDao->newDataObject();
        $preprintTombstone->setDataObjectId($preprint->getId());
        $preprintTombstone->stampDateDeleted();
        $preprintTombstone->setSetSpec($setSpec);
        $preprintTombstone->setSetName($section->getLocalizedTitle());
        $preprintTombstone->setOAIIdentifier($oaiIdentifier);
        $preprintTombstone->setOAISetObjectsIds($oaiSetObjectIds);
        $tombstoneDao->insertObject($preprintTombstone);

        if (Hook::call('PreprintTombstoneManager::insertPreprintTombstone', [$preprintTombstone, $preprint, $context])) {
            return;
        }
    }

    /**
     * Insert tombstone for every published submission
     */
    public function insertTombstonesByContext(Context $context)
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        foreach ($submissions as $submission) {
            $section = Repo::section()->get($submission->getSectionId());
            $this->insertPreprintTombstone($submission, $context, $section);
        }
    }

    /**
     * Delete tombstones for published submissions in this context
     */
    public function deleteTombstonesByContextId(int $contextId)
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        foreach ($submissions as $submission) {
            $tombstoneDao->deleteByDataObjectId($submission->getId());
        }
    }
}
