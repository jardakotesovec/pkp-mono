<?php

/**
 * @file classes/submissionFile/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 * @ingroup submissionFile
 *
 * @see SubmissionFile
 *
 * @brief Operations for retrieving and modifying submission files
 */

namespace PKP\submissionFile;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Submission;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

use PKP\core\EntityDAO;
use PKP\db\DAORegistry;
use PKP\plugins\PKPPubIdPluginDAO;
use PKP\services\PKPSchemaService;
use PKP\submission\reviewRound\ReviewRound;

use stdClass;

class DAO extends EntityDAO implements PKPPubIdPluginDAO
{
    /** @copydoc EntityDAO::$schema */
    public $schema = PKPSchemaService::SCHEMA_SUBMISSION_FILE;

    /** @copydoc EntityDAO::$table */
    public $table = 'submission_files';

    /** @copydoc EntityDAO::$settingsTable */
    public $settingsTable = 'submission_file_settings';

    /** @copydoc EntityDAO::$primaryKeyColumn */
    public $primaryKeyColumn = 'submission_file_id';

    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'assocId' => 'assoc_id',
        'assocType' => 'assoc_type',
        'createdAt' => 'created_at',
        'fileId' => 'file_id',
        'fileStage' => 'file_stage',
        'genreId' => 'genre_id',
        'id' => 'submission_file_id',
        'sourceSubmissionFileId' => 'source_submission_file_id',
        'submissionId' => 'submission_id',
        'updatedAt' => 'updated_at',
        'uploaderUserId' => 'uploader_user_id',
        'viewable' => 'viewable',
    ];

    /**
     * Instantiate a new SubmissionFile
     */
    public function newDataObject(): SubmissionFile
    {
        return App::make(SubmissionFile::class);
    }

    /**
     * @copydoc EntityDAO::get()
     */
    public function get(int $id): ?SubmissionFile
    {
        $query = new Collector($this);
        $row = $query
            ->getQueryBuilder()
            ->where($this->primaryKeyColumn, '=', $id)
            ->first();

        return $row ? $this->fromRow($row) : null;
    }

    /**
     * Get the number of announcements matching the configured query
     */
    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->count();
    }

    /**
     * Get a list of ids matching the configured query
     */
    public function getIds(Collector $query): Collection
    {
        return $query
            ->getQueryBuilder()
            ->select('sf.' . $this->primaryKeyColumn)
            ->pluck('sf.' . $this->primaryKeyColumn);
    }

    /**
     * Get a collection of announcements matching the configured query
     */
    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->submission_file_id = $this->fromRow($row);
            }
        });
    }

    /**
     * @copydoc EntityDAO::fromRow()
     */
    public function fromRow(stdClass $primaryRow): SubmissionFile
    {
        $submissionFile = parent::fromRow($primaryRow);
        $submissionFile->setData('locale', $primaryRow->locale);
        $submissionFile->setData('path', $primaryRow->path);
        $submissionFile->setData('mimetype', $primaryRow->mimetype);

        return $submissionFile;
    }

    /**
     * @copydoc EntityDAO::insert()
     */
    public function insert(SubmissionFile $submissionFile): int
    {
        parent::_insert($submissionFile);

        DB::table('submission_file_revisions')->insert([
            'submission_file_id' => $submissionFile->getId(),
            'file_id' => $submissionFile->getData('fileId'),
        ]);

        $this->insertReviewRound($submissionFile);

        return $submissionFile->getId();
    }

    /**
     * Update a Submission File
     */
    public function update(SubmissionFile $submissionFile): void
    {
        parent::_update($submissionFile);

        $hasSubmissionFileRevision = DB::table('submission_file_revisions')
            ->where([
                'submission_file_id' => $submissionFile->getId(),
                'file_id' => $submissionFile->getData('fileId')
            ])
            ->exists();

        if ($hasSubmissionFileRevision) {
            return;
        }

        DB::table('submission_file_revisions')->insert([
            'submission_file_id' => $submissionFile->getId(),
            'file_id' => $submissionFile->getData('fileId'),
        ]);
    }

    /**
     * @copydoc EntityDAO::delete()
     */
    public function delete(SubmissionFile $submissionFile)
    {
        parent::_delete($submissionFile);
    }

    /**
     * @copydoc EntityDao::deleteById()
     */
    public function deleteById(int $submissionFileId)
    {
        DB::table('submission_file_revisions')
            ->where('submission_file_id', '=', $submissionFileId)
            ->delete();

        DB::table('review_round_files')
            ->where('submission_file_id', '=', $submissionFileId)
            ->delete();

        $reviewFilesDAO = DAORegistry::getDAO('ReviewFilesDAO');
        $reviewFilesDAO->revokeBySubmissionFileId($submissionFileId);

        parent::deleteById($submissionFileId);
    }

    /**
     * Get the files for each revision of a submission file
     */
    public function getRevisions(int $submissionFileId): Collection
    {
        return DB::table('submission_file_revisions as sfr')
            ->leftJoin('files as f', 'f.file_id', '=', 'sfr.file_id')
            ->where('submission_file_id', '=', $submissionFileId)
            ->orderBy('revision_id', 'desc')
            ->select(['f.file_id as fileId', 'f.path', 'f.mimetype'])
            ->get();
    }

    /**
     * Retrieve file by public file ID
     *
     * $pubIdType it is one of the NLM pub-id-type values or
     * 'other::something' if not part of the officia.l NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     *
     * @param null|mixed $submissionId
     * @param null|mixed $contextId
     */
    public function getByPubId(
        $pubIdType,
        $pubId,
        $submissionId = null,
        $contextId = null
    ): ?SubmissionFile {
        if (empty($pubId)) {
            return null;
        }

        $submissionFileId = DB::table('submission_files as sf')
            ->leftJoin($this->settingsTable . ' as sfs', 'sfs.submission_file_id', '=', 'sf.submission_file_id')
            ->where('sf.submission_id', '=', $submissionId)
            ->where(function ($q) use ($pubIdType, $pubId) {
                $q->where('sfs.setting_name', '=', 'pub-id::' . $pubIdType);
                $q->where('sfs.setting_value', '=', $pubId);
            })
            ->select('sf.*')
            ->groupBy('sf.submission_file_id')
            ->value('sf.submission_file_id');

        if (empty($submissionFileId)) {
            return null;
        }

        $submissionFile = $this->get($submissionFileId);

        if ($submissionFile->getData('fileStage') === SubmissionFile::SUBMISSION_FILE_PROOF) {
            return $submissionFile;
        }

        return null;
    }

    /**
     * Retrieve file by public ID or submissionFileId
     *
     * @param string|int $bestId Publisher id or submissionFileId
     */
    public function getByBestId(
        $bestId,
        int $submissionId
    ): ?SubmissionFile {
        $submissionFile = null;

        if ($bestId != '') {
            $submissionFile = $this->getByPubId('publisher-id', $bestId, $submissionId, null);
        }

        if (!isset($submissionFile)) {
            $submissionFile = $this->get($bestId);
        }

        if (
            $submissionFile &&
            in_array(
                $submissionFile->getData('fileStage'),
                [
                    SubmissionFile::SUBMISSION_FILE_PROOF,
                    SubmissionFile::SUBMISSION_FILE_DEPENDENT
                ]
            )
        ) {
            return $submissionFile;
        }

        return null;
    }

    /**
     * Assign file to a review round.
     *
     * @param $submissionFileId int The file to be assigned.
     * @param $reviewRound ReviewRound
     */
    public function assignRevisionToReviewRound(
        int $submissionFileId,
        ReviewRound $reviewRound
    ): void {
        // Avoid duplication errors -- clear out any existing entries
        $this->deleteReviewRoundAssignment($submissionFileId);

        DB::table('review_round_files')->insert([
            'submission_id' => (int) $reviewRound->getSubmissionId(),
            'review_round_id' => (int) $reviewRound->getId(),
            'stage_id' => (int) $reviewRound->getStageId(),
            'submission_file_id' => $submissionFileId,
        ]);
    }

    /**
     * Remove a specific file assignment from a review round.
     */
    public function deleteReviewRoundAssignment(int $submissionFileId): void
    {
        // Remove currently assigned review files.
        DB::table('review_round_files')->where([
            'submission_file_id' => (int) $submissionFileId
        ])->delete();
    }

    /**
     * Checks if public identifier exists (other than for the specified
     * submission file ID, which is treated as an exception).
     *
     * $pubIdType it is one of the NLM pub-id-type values or
     * 'other::something' if not part of the official NLM list
     * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
     */
    public function pubIdExists(
        $pubIdType,
        $pubId,
        $excludePubObjectId,
        $contextId
    ): bool {
        $result = DB::table($this->settingsTable . ' as sfs')
            ->join('submission_files AS sf', 'sfs.submission_file_id', '=', 'sf.submission_file_id')
            ->join('submissions AS s', 'sf.submission_id', '=', 's.submission_id')
            ->where([
                'sfs.setting_name' => 'pub-id::' . (string) $pubIdType,
                'sfs.setting_value' => (string) $pubId,
                'sfs.submission_file_id' => (int) $excludePubObjectId,
                's.context_id' => (int) $contextId
            ])->count();
        return (bool) $result > 0;
    }

    /**
     * @copydoc PKPPubIdPluginDAO::changePubId()
     */
    public function changePubId($pubObjectId, $pubIdType, $pubId)
    {
        DB::table($this->settingsTable)
            ->updateOrInsert(
                [
                    'submission_file_id' => (int) $pubObjectId,
                    'setting_name' => 'pub-id::' . (string) $pubIdType,
                    'setting_value' => (string) $pubId
                ],
                [
                    'setting_type' => 'string',
                    'locale' => '',
                ]
            );
    }

    /**
     * @copydoc PKPPubIdPluginDAO::deletePubId()
     */
    public function deletePubId($pubObjectId, $pubIdType)
    {
        DB::table($this->settingsTable)
            ->where([
                'submission_file_id' => (int) $pubObjectId,
                'setting_name' => 'pub-id::' . (string) $pubIdType
            ])->delete();
    }

    /**
     * @copydoc PKPPubIdPluginDAO::deleteAllPubIds()
     */
    public function deleteAllPubIds($contextId, $pubIdType)
    {
        $collector = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$contextId]);
        $submissionsIds = Repo::submission()->getIds($collector)->toArray();

        $submissionFilesCollector = Repo::submissionFiles()
            ->getCollector()
            ->filterBySubmissionIds($submissionsIds);

        Repo::submissionFiles()
            ->deleteMany($submissionFilesCollector);
    }

    /**
     * Insert a review round for Submission File
     *
     * @throws Exception If we couldn't find the review Round, throws an exception.
     */
    protected function insertReviewRound(SubmissionFile $submissionFile): void
    {
        if (
            !in_array(
                $submissionFile->getData('assocType'),
                [
                    Application::ASSOC_TYPE_REVIEW_ROUND,
                    Application::ASSOC_TYPE_REVIEW_ASSIGNMENT
                ]
            )
        ) {
            return;
        }

        $reviewRound = $this->getReviewRound($submissionFile);

        if (!$reviewRound) {
            throw new Exception('Review round not found for adding submission file.');
        }

        DB::table('review_round_files')->insert([
            'submission_id' => $submissionFile->getData('submissionId'),
            'review_round_id' => $reviewRound->getId(),
            'stage_id' => $reviewRound->getStageId(),
            'submission_file_id' => $submissionFile->getId(),
        ]);
    }

    /**
     * Retrieve the review round for a SubmissionFile, otherwise returns null
     */
    protected function getReviewRound(SubmissionFile $submissionFile): ?ReviewRound
    {
        if ($submissionFile->getData('assocType') === Application::ASSOC_TYPE_REVIEW_ROUND) {
            $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
            return $reviewRoundDao->getById($submissionFile->getData('assocId'));
        }

        if ($submissionFile->getData('assocType') === Application::ASSOC_TYPE_REVIEW_ASSIGNMENT) {
            $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /** @var ReviewAssignmentDAO $reviewAssignmentDao */
            $reviewAssignment = $reviewAssignmentDao->getById($submissionFile->getData('assocId'));
            $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
            return $reviewRoundDao->getById($reviewAssignment->getReviewRoundId());
        }

        return null;
    }
}
