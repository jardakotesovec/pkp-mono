<?php

/**
 * @file classes/context/SubEditorsDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubEditorsDAO
 * @ingroup context
 *
 * @brief Base class associating sections, series and categories to sub editors.
 */

namespace PKP\context;

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\submission\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\db\DAORegistry;
use PKP\log\SubmissionEmailLogDAO;
use PKP\log\SubmissionEmailLogEntry;
use PKP\mail\mailables\EditorAssigned;
use PKP\notification\NotificationSubscriptionSettingsDAO;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;
use PKP\stageAssignment\StageAssignmentDAO;
use PKP\userGroup\UserGroup;

class SubEditorsDAO extends \PKP\db\DAO
{
    /**
     * Insert a new sub editor.
     *
     * @param int $contextId
     * @param int $assocId
     * @param int $userId
     */
    public function insertEditor($contextId, $assocId, $userId, $assocType, int $userGroupId)
    {
        return $this->update(
            'INSERT INTO subeditor_submission_group
				(context_id, assoc_id, user_id, assoc_type, user_group_id)
				VALUES
				(?, ?, ?, ?, ?)',
            [
                (int) $contextId,
                (int) $assocId,
                (int) $userId,
                (int) $assocType,
                $userGroupId,
            ]
        );
    }

    /**
     * Delete a sub editor.
     *
     * @param int $contextId
     * @param int $assocId
     * @param int $userId
     * @param int $assocType ASSOC_TYPE_SECTION or ASSOC_TYPE_CATEGORY
     */
    public function deleteEditor($contextId, $assocId, $userId, $assocType)
    {
        $this->update(
            'DELETE FROM subeditor_submission_group WHERE context_id = ? AND section_id = ? AND user_id = ? AND assoc_type = ?',
            [
                (int) $contextId,
                (int) $assocId,
                (int) $userId,
                (int) $assocType,
            ]
        );
    }

    /**
     * Retrieve a list of all sub editors assigned to the specified submission group.
     *
     * @param int[] $assocIds Section or category ids
     * @param int $assocType ASSOC_TYPE_SECTION or ASSOC_TYPE_CATEGORY
     *
     * @return Collection result rows with user_id and user_group_id columns
     */
    public function getBySubmissionGroupIds(array $assocIds, int $assocType, int $contextId): Collection
    {
        return DB::table('subeditor_submission_group')
            ->where('assoc_type', '=', $assocType)
            ->where('context_id', '=', $contextId)
            ->whereIn('assoc_id', $assocIds)
            ->get(['user_id', 'user_group_id']);
    }

    /**
     * Delete all sub editors for a specified submission group in a context.
     *
     * @param int $assocId
     * @param int $assocType ASSOC_TYPE_SECTION or ASSOC_TYPE_CATEGORY
     * @param int $contextId
     */
    public function deleteBySubmissionGroupId($assocId, $assocType, $contextId = null)
    {
        $params = [(int) $assocId, (int) $assocType];
        if ($contextId) {
            $params[] = (int) $contextId;
        }
        $this->update(
            'DELETE FROM subeditor_submission_group WHERE assoc_id = ? AND assoc_type = ?' .
            ($contextId ? ' AND context_id = ?' : ''),
            $params
        );
    }

    /**
     * Delete all submission group assignments for the specified user.
     */
    public function deleteByUserId(int $userId)
    {
        DB::table('subeditor_submission_group')
            ->where('user_id', '=', $userId)
            ->delete();
    }

    /**
     * Delete all submission group assignments for a user group
     */
    public function deleteByUserGroupId(int $userGroupId)
    {
        DB::table('subeditor_submission_group')
            ->where('user_group_id', '=', $userGroupId)
            ->delete();
    }

    /**
     * Check if a user is assigned to a specified submission group.
     *
     * @param int $contextId
     * @param int $assocId
     * @param int $userId
     * @param int $assocType optional ASSOC_TYPE_SECTION or ASSOC_TYPE_CATEGORY
     *
     * @return bool
     */
    public function editorExists($contextId, $assocId, $userId, $assocType)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count FROM subeditor_submission_group WHERE context_id = ? AND section_id = ? AND user_id = ? AND assoc_id = ?',
            [(int) $contextId, (int) $assocId, (int) $userId, (int) $assocType]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Assign editors to a submission
     *
     * Creates a stage assignment for each editorial user
     * configured in the section and category settings.
     *
     * @return Collection The user ids for editors that were assigned
     */
    public function assignEditors(Submission $submission, Context $context): Collection
    {
        $publication = $submission->getCurrentPublication();
        $sectionIdPropName = Application::getSectionIdPropName();

        $assignments = $this->getBySubmissionGroupIds(
            [$publication->getData($sectionIdPropName)],
            Application::ASSOC_TYPE_SECTION,
            $submission->getData('contextId')
        );

        if (!empty($publication->getData('categoryIds'))) {
            $assignedToCategory = $this->getBySubmissionGroupIds(
                $publication->getData('categoryIds'),
                Application::ASSOC_TYPE_CATEGORY,
                $submission->getData('contextId')
            );
            $assignments = $assignments->merge($assignedToCategory);
        }

        // Remove duplicate assignments for the same user in the
        // same user group by structuring the array with a key
        // that will cause duplicates to be overwritten
        $assignments = collect($assignments)->mapWithKeys(fn ($assignment, $key) => [$assignment->user_id . '-' . $assignment->user_group_id => $assignment]);

        $userGroups = Repo::userGroup()
            ->getCollector()
            ->filterByContextIds([$submission->getData('contextId')])
            ->getMany();

        $userGroupIds = $userGroups->keys();

        $assignments = $assignments->filter(function ($assignment) use ($userGroupIds) {
            return Repo::userGroup()->userInGroup($assignment->user_id, $assignment->user_group_id)
                && $userGroupIds->contains($assignment->user_group_id);
        });

        /** @var StageAssignmentDAO $stageAssignmentDao */
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        foreach ($assignments as $assignment) {
            $userGroup = $userGroups->first(fn (UserGroup $userGroup) => $userGroup->getId() === $assignment->user_group_id);
            $stageAssignmentDao->build($submission->getId(), $assignment->user_group_id, $assignment->user_id, $userGroup->getRecommendOnly());
        }

        // Update assignment notifications
        $notificationManager = new NotificationManager();
        $notificationManager->updateNotification(
            Application::get()->getRequest(),
            $notificationManager->getDecisionStageNotifications(),
            null,
            Application::ASSOC_TYPE_SUBMISSION,
            $submission->getId()
        );

        // Send a notification to assigned users
        foreach ($assignments as $assignment) {
            $notificationManager->createNotification(
                Application::get()->getRequest(),
                $assignment->user_id,
                Notification::NOTIFICATION_TYPE_SUBMISSION_SUBMITTED,
                $submission->getContextId(),
                Application::ASSOC_TYPE_SUBMISSION,
                $submission->getId()
            );
        }

        // Send an email to assigned editors
        $editorAssignments = $stageAssignmentDao->getBySubmissionAndRoleIds(
            $submission->getId(),
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR],
            WORKFLOW_STAGE_ID_SUBMISSION
        )->toArray();

        if (count($editorAssignments)) {

            // Never notify the same user twice, even if they are assigned in multiple roles
            $notifiedEditors = [];

            /** @var NotificationSubscriptionSettingsDAO $notificationSubscriptionSettingsDao */
            $notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
            $emailTemplate = Repo::emailTemplate()->getByKey($context->getId(), EditorAssigned::getEmailTemplateKey());
            $mailable = new EditorAssigned($context, $submission);
            $mailable
                ->from($context->getData('contactEmail'), $context->getData('contactName'))
                ->subject($emailTemplate->getLocalizedData('subject'))
                ->body($emailTemplate->getLocalizedData('body'));

            /** @var StageAssignment $editorAssignment */
            foreach ($editorAssignments as $editorAssignment) {
                $unsubscribed = in_array(
                    Notification::NOTIFICATION_TYPE_SUBMISSION_SUBMITTED,
                    $notificationSubscriptionSettingsDao->getNotificationSubscriptionSettings(
                        NotificationSubscriptionSettingsDAO::BLOCKED_EMAIL_NOTIFICATION_KEY,
                        $editorAssignment->getUserId(),
                        $context->getId()
                    )
                );

                if ($unsubscribed && !in_array($editorAssignment->getUserId(), $notifiedEditors)) {
                    continue;
                }

                $notifiedEditors[] = $editorAssignment->getUserId();

                $recipient = Repo::user()->get($editorAssignment->getUserId());
                $mailable->recipients([$recipient]);

                Mail::send($mailable);

                /** @var SubmissionEmailLogDAO $logDao */
                $logDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
                $logDao->logMailable(
                    SubmissionEmailLogEntry::SUBMISSION_EMAIL_EDITOR_ASSIGN,
                    $mailable,
                    $submission
                );
            }
        }

        return $assignments->map(fn ($assignment) => $assignment->user_id);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\context\SubEditorsDAO', '\SubEditorsDAO');
}
