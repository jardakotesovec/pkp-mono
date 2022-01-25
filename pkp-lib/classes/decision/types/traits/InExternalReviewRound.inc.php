<?php
/**
 * @file classes/decision/types/traits/InExternalReviewRound.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class decision
 *
 * @brief Helper functions for decisions taken in an external review round
 */

namespace PKP\decision\types\traits;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Submission;
use PKP\components\fileAttachers\FileStage;
use PKP\components\fileAttachers\Library;
use PKP\components\fileAttachers\ReviewFiles;
use PKP\components\fileAttachers\Upload;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\submission\reviewAssignment\ReviewAssignmentDAO;
use PKP\submission\reviewRound\ReviewRound;
use PKP\submissionFile\SubmissionFile;

trait InExternalReviewRound
{
    /** @copydoc DecisionType::getStageId() */
    public function getStageId(): int
    {
        return WORKFLOW_STAGE_ID_EXTERNAL_REVIEW;
    }

    /**
     * Get the assigned reviewers who completed their review
     *
     * @return array<int>
     */
    protected function getCompletedReviewerIds(Submission $submission, int $reviewRoundId): array
    {
        $userIds = [];
        /** @var ReviewAssignmentDAO $reviewAssignmentDao */
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignments = $reviewAssignmentDao->getBySubmissionId(
            $submission->getId(),
            $reviewRoundId,
            $this->getStageId()
        );
        foreach ($reviewAssignments as $reviewAssignment) {
            if (!in_array($reviewAssignment->getStatus(), ReviewAssignment::REVIEW_COMPLETE_STATUSES)) {
                continue;
            }
            $userIds[] = (int) $reviewAssignment->getReviewerId();
        }
        return $userIds;
    }

    /**
     * Get the submission file stages that are permitted to be attached to emails
     * sent in this decision
     *
     * @return array<int>
     */
    protected function getAllowedAttachmentFileStages(): array
    {
        return [
            SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT,
            SubmissionFile::SUBMISSION_FILE_REVIEW_FILE,
            SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION,
        ];
    }

    /**
     * Get the file attacher components supported for emails in this decision
     */
    protected function getFileAttachers(Submission $submission, Context $context, ?ReviewRound $reviewRound = null): array
    {
        $attachers = [
            new Upload(
                $context,
                __('common.upload.addFile'),
                __('common.upload.addFile.description'),
                __('common.upload.addFile')
            ),
        ];

        if ($reviewRound) {
            /** @var ReviewAssignmentDAO $reviewAssignmentDAO */
            $reviewAssignmentDAO = DAORegistry::getDAO('ReviewAssignmentDAO');
            $reviewAssignments = $reviewAssignmentDAO->getByReviewRoundId($reviewRound->getId());
            $reviewerFiles = [];
            if (!empty($reviewAssignments)) {
                $reviewerFiles = Repo::submissionFile()->getMany(
                    Repo::submissionFile()
                        ->getCollector()
                        ->filterBySubmissionIds([$submission->getId()])
                        ->filterByAssoc(Application::ASSOC_TYPE_REVIEW_ASSIGNMENT, array_keys($reviewAssignments))
                );
            }
            $attachers[] = new ReviewFiles(
                __('reviewer.submission.reviewFiles'),
                __('email.addAttachment.reviewFiles.description'),
                __('email.addAttachment.reviewFiles.attach'),
                $reviewerFiles,
                $reviewAssignments,
                $context
            );
        }

        $attachers[] = (new FileStage(
            $context,
            $submission,
            __('submission.submit.submissionFiles'),
            __('email.addAttachment.submissionFiles.reviewDescription'),
            __('email.addAttachment.submissionFiles.attach')
        ))
            ->withFileStage(
                SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION,
                __('editor.submission.revisions'),
                $reviewRound
            )->withFileStage(
                SubmissionFile::SUBMISSION_FILE_REVIEW_FILE,
                __('reviewer.submission.reviewFiles'),
                $reviewRound
            );

        $attachers[] = new Library(
            $context,
            $submission
        );

        return $attachers;
    }

    /**
     * Get the completed review assignments for this round
     */
    protected function getCompletedReviewAssignments(int $submissionId, int $reviewRoundId): array
    {
        /** @var ReviewAssignmentDAO $reviewAssignmentDao */
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignments = $reviewAssignmentDao->getBySubmissionId(
            $submissionId,
            $reviewRoundId,
            $this->getStageId()
        );
        $completedReviewAssignments = [];
        foreach ($reviewAssignments as $reviewAssignment) {
            if (in_array($reviewAssignment->getStatus(), ReviewAssignment::REVIEW_COMPLETE_STATUSES)) {
                $completedReviewAssignments[] = $reviewAssignment;
            }
        }

        return $completedReviewAssignments;
    }
}
