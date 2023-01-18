<?php

/**
 * @file classes/section/DAO.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 * @ingroup section
 *
 * @see Section
 *
 * @brief Operations for retrieving and modifying Section objects.
 */

namespace APP\section;

use APP\facades\Repo;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\traits\EntityWithParent;
use PKP\services\PKPSchemaService;

class DAO extends \PKP\section\DAO
{
    use EntityWithParent;

    /** @copydoc EntityDAO::$schema */
    public $schema = PKPSchemaService::SCHEMA_SECTION;

    /** @copydoc EntityDAO::$table */
    public $table = 'sections';

    /** @copydoc EntityDAO::$settingsTable */
    public $settingsTable = 'section_settings';

    /** @copydoc EntityDAO::$primarykeyColumn */
    public $primaryKeyColumn = 'section_id';

    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'section_id',
        'contextId' => 'journal_id',
        'reviewFormId' => 'review_form_id',
        'sequence' => 'seq',
        'editorRestricted' => 'editor_restricted',
        'metaIndexed' => 'meta_indexed',
        'metaReviewed' => 'meta_reviewed',
        'abstractsNotRequired' => 'abstracts_not_required',
        'hideTitle' => 'hide_title',
        'hideAuthor' => 'hide_author',
        'isInactive' => 'is_inactive',
        'wordCount' => 'abstract_word_count'
    ];

    /**
     * Get the parent object ID column name
     */
    public function getParentColumn(): string
    {
        return 'journal_id';
    }

    /**
     * Retrieve all sections in which articles are currently published in
     * the given issue.
     */
    public function getByIssueId(int $issueId): LazyCollection
    {
        $issue = Repo::issue()->get($issueId);
        $allowedStatuses = [Submission::STATUS_PUBLISHED];
        if (!$issue->getPublished()) {
            $allowedStatuses[] = Submission::STATUS_SCHEDULED;
        }
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$issue->getJournalId()])
            ->filterByIssueIds([$issueId])
            ->filterByStatus($allowedStatuses)
            ->orderBy(\APP\submission\Collector::ORDERBY_SEQUENCE, \APP\submission\Collector::ORDER_DIR_ASC)
            ->getMany();

        $sectionIds = $submissions
            ->map(fn ($submission) => $submission->getCurrentPublication()->getData('sectionId'))
            ->unique()
            ->values();
        if (empty($sectionIds)) {
            return new LazyCollection();
        }
        $rows = DB::table('sections', 's')
            ->select('s.*', DB::raw('COALESCE(o.seq, s.seq) AS section_seq'))
            ->leftJoin('custom_section_orders AS o', function ($join) use ($issueId) {
                $join->on('s.section_id', '=', 'o.section_id')
                    ->on('o.issue_id', '=', DB::raw($issueId));
            })
            ->whereIn('s.section_id', $sectionIds)
            ->orderBy('section_seq')
            ->get();
        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->section_id => $this->fromRow($row);
            }
        });
    }

    /**
     * Delete the custom ordering of an issue's sections.
     */
    public function deleteCustomSectionOrdering(int $issueId): void
    {
        DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->delete();
    }

    /**
     * Get the custom section order of a section.
     */
    public function getCustomSectionOrder(int $issueId, int $sectionId): ?int
    {
        return DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->where('section_id', $sectionId)
            ->value('seq');
    }

    /**
     * Delete a section from the custom section order table.
     */
    public function deleteCustomSectionOrder(int $issueId, int $sectionId): void
    {
        $seq = $this->getCustomSectionOrder($issueId, $sectionId);

        DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->where('section_id', $sectionId)
            ->delete();

        // Reduce the section order of every successive section by one
        DB::table('custom_section_orders')
            ->where('issue_id', $issueId)
            ->where('seq', '>', $seq)
            ->update(['seq' => DB::raw('seq - 1')]);
    }

    /**
     * Insert or update a custom section ordering
     */
    public function upsertCustomSectionOrder(int $issueId, int $sectionId, int $seq): void
    {
        DB::table('custom_section_orders')->upsert(
            [['issue_id' => $issueId, 'section_id' => $sectionId, 'seq' => $seq]],
            ['issue_id', 'section_id'],
            ['seq']
        );
    }
}
