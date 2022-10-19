<?php

/**
 * @file plugins/reports/monographReport/MonographReportPlugin.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MonographReportPlugin
 * @ingroup plugins_reports_monographReport
 *
 * @brief The monograph report plugin will output a .csv file containing basic
 * information (title, DOI, etc.) from all monographs
 */

namespace APP\plugins\reports\monographReport;

use APP\author\Author;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\press\Press;
use APP\press\Series;
use APP\press\SeriesDAO;
use APP\publication\Publication;
use APP\publicationFormat\IdentificationCode;
use APP\publicationFormat\PublicationFormat;
use APP\submission\Submission;
use DateTimeImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use IteratorAggregate;
use PKP\category\Category;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\plugins\ReportPlugin;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;
use PKP\stageAssignment\StageAssignmentDAO;
use PKP\submission\SubmissionAgencyDAO;
use PKP\submission\SubmissionDisciplineDAO;
use PKP\submission\SubmissionKeywordDAO;
use PKP\submission\SubmissionSubjectDAO;
use PKP\user\User;
use PKP\userGroup\UserGroup;
use SplFileObject;
use Traversable;

class MonographReportPlugin extends ReportPlugin implements IteratorAggregate
{
    /** Maximum quantity of authors in a submission */
    private int $maxAuthors;
    /** Maximum quantity of editors in a submission */
    private int $maxEditors;
    /** Maximum quantity of decisions in a submission */
    private int $maxDecisions;
    /** The current press being processed */
    private Press $press;
    /** The current submission being processed */
    private Submission $submission;
    /** The current publication being processed */
    private Publication $publication;
    /** @var Author[] The list of authors */
    private array $authors;
    /** @var array<string, string> Map */
    private array $statusMap;
    /** @var User[] Editor list */
    private array $editors;
    /** @var array<int, Decision[]> Decisions grouped by editor ID */
    private array $decisionsByEditor;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }

    /**
     * @copydoc Plugin::getName()
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
        return __('plugins.reports.monographReport.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.reports.monographReport.description');
    }

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request): void
    {
        $this->press = $this->getRequest()->getContext();
        if (!$this->press) {
            throw new Exception('The monograph report requires a context');
        }

        $output = $this->createOutputStream();
        // Display the data rows.
        foreach ($this as $row) {
            $output->fputcsv($row);
        }
    }

    /**
     * Retrieves a row generator, it includes the report header
     */
    public function getIterator(): Traversable
    {
        $this->retrieveLimits();

        $fieldMapper = $this->getFieldMapper();

        // Yields the report headers
        yield array_keys($fieldMapper);

        /** @var StageAssignmentDAO */
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

        /** @var Submission */
        foreach (Repo::submission()->getCollector()->filterByContextIds([$this->press->getId()])->getMany() as $this->submission) {
            // Shared getter data related to the current submission being processed
            $this->statusMap ??= $this->submission->getStatusMap();
            $this->publication = $this->submission->getCurrentPublication();
            $this->authors = $this->publication->getData('authors')->values()->toArray();
            $this->decisionsByEditor = collect(Repo::decision()->getCollector()->filterBySubmissionIds([$this->submission->getId()])->getMany())
                ->groupBy(fn (Decision $decision) => $decision->getData('editorId'))
                ->toArray();
            $this->editors = collect($stageAssignmentDao->getBySubmissionAndStageId($this->submission->getId())->toIterator())
                ->filter(fn (StageAssignment $stageAssignment) => $this->getEditorUserGroups()->get($stageAssignment->getUserGroupId()))
                ->map(fn (StageAssignment $stageAssignment) => $this->getUser($stageAssignment->getUserId()))
                ->unique(fn (User $user) => $user->getId())
                ->values()
                ->toArray();
            // Calls the getter for each field and yields an array
            yield array_map(fn (callable $getter) => $getter(), $fieldMapper);
        }
    }

    /**
     * Retrieves the stage label
     */
    public function getStageLabel(int $stageId): string
    {
        return match ($stageId) {
            WORKFLOW_STAGE_ID_SUBMISSION => __('submission.submission'),
            WORKFLOW_STAGE_ID_INTERNAL_REVIEW => __('workflow.review.internalReview'),
            WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => __('submission.review'),
            WORKFLOW_STAGE_ID_EDITING => __('submission.copyediting'),
            WORKFLOW_STAGE_ID_PRODUCTION => __('submission.production'),
            default => ''
        };
    }

    /**
     * Retrieves the decision message
     */
    private function getDecisionMessage(?int $decision): string
    {
        return match ($decision) {
            Decision::INTERNAL_REVIEW => __('editor.submission.decision.sendInternalReview'),
            Decision::ACCEPT => __('editor.submission.decision.accept'),
            Decision::EXTERNAL_REVIEW => __('editor.submission.decision.sendExternalReview'),
            Decision::PENDING_REVISIONS => __('editor.submission.decision.requestRevisions'),
            Decision::RESUBMIT => __('editor.submission.decision.resubmit'),
            Decision::DECLINE => __('editor.submission.decision.decline'),
            Decision::SEND_TO_PRODUCTION => __('editor.submission.decision.sendToProduction'),
            Decision::INITIAL_DECLINE => __('editor.submission.decision.decline'),
            Decision::RECOMMEND_ACCEPT => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.accept')]),
            Decision::RECOMMEND_PENDING_REVISIONS => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.requestRevisions')]),
            Decision::RECOMMEND_RESUBMIT => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.resubmit')]),
            Decision::RECOMMEND_DECLINE => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.decline')]),
            Decision::RECOMMEND_EXTERNAL_REVIEW => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.sendExternalReview')]),
            Decision::NEW_EXTERNAL_ROUND => __('editor.submission.decision.newReviewRound'),
            Decision::REVERT_DECLINE => __('editor.submission.decision.revertDecline'),
            Decision::REVERT_INITIAL_DECLINE => __('editor.submission.decision.revertDecline'),
            Decision::SKIP_EXTERNAL_REVIEW => __('editor.submission.decision.skipReview'),
            Decision::SKIP_INTERNAL_REVIEW => __('editor.submission.decision.skipReview'),
            Decision::ACCEPT_INTERNAL => __('editor.submission.decision.accept'),
            Decision::PENDING_REVISIONS_INTERNAL => __('editor.submission.decision.requestRevisions'),
            Decision::RESUBMIT_INTERNAL => __('editor.submission.decision.resubmit'),
            Decision::DECLINE_INTERNAL => __('editor.submission.decision.decline'),
            Decision::RECOMMEND_ACCEPT_INTERNAL => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.accept')]),
            Decision::RECOMMEND_PENDING_REVISIONS_INTERNAL => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.requestRevisions')]),
            Decision::RECOMMEND_RESUBMIT_INTERNAL => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.resubmit')]),
            Decision::RECOMMEND_DECLINE_INTERNAL => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.decline')]),
            Decision::REVERT_INTERNAL_DECLINE => __('editor.submission.decision.decline'),
            Decision::NEW_INTERNAL_ROUND => __('editor.submission.decision.newReviewRound'),
            Decision::BACK_FROM_PRODUCTION => __('editor.submission.decision.backToCopyediting'),
            Decision::BACK_FROM_COPYEDITING => __('editor.submission.decision.backFromCopyediting'),
            Decision::CANCEL_REVIEW_ROUND => __('editor.submission.decision.cancelReviewRound'),
            Decision::CANCEL_INTERNAL_REVIEW_ROUND => __('editor.submission.decision.cancelReviewRound'),
            default => ''
        };
    }

    /**
     * Retrieves the report header
     */
    private function getFieldMapper(): array
    {
        /** @var SeriesDAO */
        $seriesDao = DAORegistry::getDAO('SeriesDAO');
        /** @var SubmissionKeywordDAO */
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        /** @var SubmissionSubjectDAO */
        $submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
        /** @var SubmissionDisciplineDAO */
        $submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
        /** @var SubmissionAgencyDAO */
        $submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');

        /** @var Series[] */
        $seriesList = $seriesDao->getByContextId($this->press->getId())->toAssociativeArray();
        /** @var Category[] */
        $categoryList = Repo::category()->getCollector()
            ->filterByContextIds([$this->press->getId()])
            ->getMany()
            ->keyBy(fn (Category $category) => $category->getId())
            ->toArray();

        $roleHeader = fn (string $title, string $role, int $index) => "{$title} ({$role} " . ($index + 1) . ')';
        $authorHeader = fn (string $title, int $index) => $roleHeader($title, __('user.role.author'), $index);
        $editorHeader = fn (string $title, int $index) => $roleHeader($title, __('user.role.editor'), $index);
        $decisionHeader = fn (string $title, int $editorIndex, int $decisionIndex) => $editorHeader("{$title} " . ($decisionIndex + 1), $editorIndex);

        return [
            __('common.id') => fn () => $this->submission->getId(),
            __('common.title') => fn () => $this->publication->getLocalizedFullTitle(),
            __('common.abstract') => fn () => html_entity_decode(strip_tags($this->publication->getLocalizedData('abstract'))),
            __('series.series') => fn () => $this->seriesList[$this->publication->getData('seriesId')]?->getLocalizedTitle() ?: '',
            __('submission.submit.seriesPosition') => fn () => $this->publication->getData('seriesPosition'),
            __('common.language') => fn () => $this->publication->getData('locale'),
            __('submission.coverage') => fn () => $this->publication->getLocalizedData('coverage'),
            __('submission.rights') => fn () => $this->publication->getLocalizedData('rights'),
            __('submission.source') => fn () => $this->publication->getLocalizedData('source'),
            __('common.subjects') => fn () => collect([$submissionSubjectDao->getSubjects($this->publication->getId())])
                ->map(fn (array $subjects) => $subjects[Locale::getLocale()] ?? $subjects[$this->submission->getData('locale')] ?? [])
                ->flatten()
                ->join(', '),
            __('common.type') => fn () => $this->publication->getLocalizedData('type'),
            __('search.discipline') => fn () => collect([$submissionDisciplineDao->getDisciplines($this->publication->getId())])
                ->map(fn (array $disciplines) => $disciplines[Locale::getLocale()] ?? $disciplines[$this->submission->getData('locale')] ?? [])
                ->flatten()
                ->join(', '),
            __('common.keywords') => fn () => collect([$submissionKeywordDao->getKeywords($this->publication->getId())])
                ->map(fn (array $keywords) => $keywords[Locale::getLocale()] ?? $keywords[$this->submission->getData('locale')] ?? [])
                ->flatten()
                ->join(', '),
            __('submission.supportingAgencies') => fn () => collect([$submissionAgencyDao->getAgencies($this->publication->getId())])
                ->map(fn (array $agencies) => $agencies[Locale::getLocale()] ?? $agencies[$this->submission->getData('locale')] ?? [])
                ->flatten()
                ->join(', '),
            __('common.status') => fn () => $this->submission->getData('status') === Submission::STATUS_QUEUED
                ? $this->getStageLabel($this->submission->getData('stageId'))
                : __($this->statusMap[$this->submission->getData('status')]),
            __('common.url') => fn () => $this->getRequest()->url(null, 'workflow', 'access', $this->submission->getId()),
            __('catalog.manage.series.onlineIssn') => fn () => $seriesList[$this->publication->getData('seriesId')]?->getOnlineISSN(),
            __('catalog.manage.series.printIssn') => fn () => $seriesList[$this->publication->getData('seriesId')]?->getPrintISSN(),
            __('metadata.property.displayName.doi') => fn () => $this->publication->getDoi(),
            __('catalog.categories') => fn () => collect($this->publication->getData('categoryIds'))
                ->map(fn (int $id) => $categoryList[$id]?->getLocalizedTitle())
                ->implode("\n"),
            __('submission.identifiers') => fn () => collect($this->publication->getData('publicationFormats'))
                ->map(
                    fn (PublicationFormat $pf) => collect($pf->getIdentificationCodes()->toIterator())
                        ->map(fn (IdentificationCode $ic) => [$ic->getNameForONIXCode(), $ic->getValue()])
                )
                ->flatten(1)
                ->filter(fn (array $identifier) => trim(end($identifier)))
                ->map(fn (array $identifier) => __('plugins.reports.monographReport.identifierFormat', ['name' => reset($identifier), 'value' => end($identifier)]))
                ->implode("\n"),
            __('common.dateSubmitted') => fn () => $this->submission->getData('dateSubmitted'),
            __('submission.lastModified') => fn () => $this->submission->getData('lastModified'),
            __('submission.firstPublished') => fn () => $this->submission->getOriginalPublication()?->getData('datePublished') ?? ''
        ]
        /** @todo: PHP 8.0 doesn't support unpacking arrays with string keys (PHP 8.1 does, so the "collects" below could be ...unpacked into the array) */
        + collect($this->maxAuthors ? range(0, $this->maxAuthors - 1) : [])
            ->map(
                fn ($i) => [
                    $authorHeader(__('user.givenName'), $i) => fn () => $this->getAuthor($i)?->getLocalizedGivenName(),
                    $authorHeader(__('user.familyName'), $i) => fn () => $this->getAuthor($i)?->getLocalizedFamilyName(),
                    $authorHeader(__('user.orcid'), $i) => fn () => $this->getAuthor($i)?->getData('orcid'),
                    $authorHeader(__('common.country'), $i) => fn () => $this->getAuthor($i)?->getData('country'),
                    $authorHeader(__('user.affiliation'), $i) => fn () => $this->getAuthor($i)?->getLocalizedData('affiliation'),
                    $authorHeader(__('user.email'), $i) => fn () => $this->getAuthor($i)?->getData('email'),
                    $authorHeader(__('user.url'), $i) => fn () => $this->getAuthor($i)?->getData('url'),
                    $authorHeader(__('user.biography'), $i) => fn () => html_entity_decode(strip_tags($this->getAuthor($i)?->getLocalizedData('biography')))
                ]
            )
            ->collapse()
            ->toArray()
        + collect($this->maxEditors ? range(0, $this->maxEditors - 1) : [])
            ->map(
                fn ($i) => [
                    $editorHeader(__('user.givenName'), $i) => fn () => $this->getEditor($i)?->getLocalizedGivenName(),
                    $editorHeader(__('user.familyName'), $i) => fn () => $this->getEditor($i)?->getLocalizedFamilyName(),
                    $editorHeader(__('user.orcid'), $i) => fn () => $this->getEditor($i)?->getData('orcid'),
                    $editorHeader(__('user.email'), $i) => fn () => $this->getEditor($i)?->getEmail()
                ]
                + collect($this->maxDecisions ? range(0, $this->maxDecisions - 1) : [])
                    ->map(
                        fn ($j) => [
                            $decisionHeader(__('manager.setup.editorDecision'), $i, $j) => fn () => $this->getDecisionMessage($this->getDecision($i, $j)?->getData('decision')),
                            $decisionHeader(__('common.dateDecided'), $i, $j) => fn () => $this->getDecision($i, $j)?->getData('dateDecided')
                        ]
                    )
                    ->collapse()
                    ->toArray()
            )
            ->collapse()
            ->toArray();
    }

    /**
     * Retrieves a cached user
     */
    private function getUser(int $userId): ?User
    {
        static $users = [];
        return $users[$userId] ??= Repo::user()->get($userId, true);
    }

    /**
     * Retrieves a SplFileObject and sends HTTP headers to enforce the report download
     */
    private function createOutputStream(): SplFileObject
    {
        $acronym = PKPString::regexp_replace('/[^A-Za-z0-9 ]/', '', $this->press->getLocalizedAcronym());
        $date = (new DateTimeImmutable())->format('Ymd');

        // Prepare for UTF8-encoded CSV output.
        header('content-type: text/comma-separated-values');
        header("content-disposition: attachment; filename=monographs-{$acronym}-{$date}.csv");

        $output = new SplFileObject('php://output', 'w');
        // UTF-8 BOM to force the file to be read with the right encoding
        $output->fwrite("\xEF\xBB\xBF");
        return $output;
    }

    /**
     * Retrieves the maximum amount of authors, editors and decisions that a submission may have
     */
    private function retrieveLimits(): void
    {
        $editorUserGroupIds = $this->getEditorUserGroups()->keys()->toArray();
        $query = DB::selectOne(
            'SELECT MAX(tmp.authors) AS authors, MAX(tmp.editors) AS editors, MAX(tmp.decisions) AS decisions
            FROM (
                SELECT (
                    SELECT COUNT(0)
                    FROM authors a
                    WHERE a.publication_id = s.current_publication_id
                ) AS authors,
                (
                    SELECT COUNT(sa.user_id)
                    FROM stage_assignments sa
                    WHERE sa.submission_id = s.submission_id
                    AND sa.user_group_id IN (0' . str_repeat(',?', count($editorUserGroupIds)) . ')
                ) AS editors,
                (
                    SELECT MAX(count)
                    FROM (
                        SELECT COUNT(0) AS count
                        FROM edit_decisions ed
                        WHERE ed.submission_id = s.submission_id
                        GROUP BY ed.editor_id
                    ) AS tmp
                ) AS decisions
                FROM submissions s
            ) AS tmp',
            $editorUserGroupIds
        );
        $this->maxAuthors = (int) $query->authors;
        $this->maxEditors = (int) $query->editors;
        $this->maxDecisions = (int) $query->decisions;
    }

    /**
     * Retrieves an author from the current submission
     */
    private function getAuthor(int $index): ?Author
    {
        return $this->authors[$index] ?? null;
    }

    /**
     * Retrieves an editor from the current submission
     */
    private function getEditor(int $index): ?User
    {
        return $this->editors[$index] ?? null;
    }

    /**
     * Retrieves a decision from the current submission
     */
    private function getDecision(int $editorIndex, int $decisionIndex): ?Decision
    {
        return $this->decisionsByEditor[$this->getEditor($editorIndex)?->getId()][$decisionIndex] ?? null;
    }

    /**
     * Retrieves
     */
    private function getEditorUserGroups(): Collection
    {
        static $cache;
        return $cache ??= collect(Repo::userGroup()->getCollector()->filterByContextIds([$this->press->getId()])->getMany())
            ->filter(fn (UserGroup $userGroup) => in_array($userGroup->getRoleId(), [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR]))
            ->mapWithKeys(fn (UserGroup $userGroup) => [$userGroup->getId() => true]);
    }
}
