<?php

/**
 * @file controllers/grid/users/reviewer/form/ReviewerForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerForm
 * @ingroup controllers_grid_users_reviewer_form
 *
 * @brief Base Form for adding a reviewer to a submission.
 * N.B. Requires a subclass to implement the "reviewerId" to be added.
 */

use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\linkAction\LinkAction;
use PKP\mail\SubmissionMailTemplate;
use PKP\notification\PKPNotification;

use PKP\submission\action\EditorAction;
use PKP\submission\SubmissionFile;

class ReviewerForm extends Form
{
    /** The submission associated with the review assignment **/
    public $_submission;

    /** The review round associated with the review assignment **/
    public $_reviewRound;

    /** An array of actions for the other reviewer forms */
    public $_reviewerFormActions;

    /** An array with all current user roles */
    public $_userRoles;

    /**
     * Constructor.
     *
     * @param $submission Submission
     * @param $reviewRound ReviewRound
     */
    public function __construct($submission, $reviewRound)
    {
        parent::__construct('controllers/grid/users/reviewer/form/defaultReviewerForm.tpl');
        $this->setSubmission($submission);
        $this->setReviewRound($reviewRound);

        // Validation checks for this form
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'responseDueDate', 'required', 'editor.review.errorAddingReviewer'));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'reviewDueDate', 'required', 'editor.review.errorAddingReviewer'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    //
    // Getters and Setters
    //
    /**
     * Get the submission Id
     *
     * @return int submissionId
     */
    public function getSubmissionId()
    {
        $submission = $this->getSubmission();
        return $submission->getId();
    }

    /**
     * Get the submission
     *
     * @return Submission
     */
    public function getSubmission()
    {
        return $this->_submission;
    }

    /**
     * Get the ReviewRound
     *
     * @return ReviewRound
     */
    public function getReviewRound()
    {
        return $this->_reviewRound;
    }

    /**
     * Set the submission
     *
     * @param $submission Submission
     */
    public function setSubmission($submission)
    {
        $this->_submission = $submission;
    }

    /**
     * Set the ReviewRound
     *
     * @param $reviewRound ReviewRound
     */
    public function setReviewRound($reviewRound)
    {
        $this->_reviewRound = $reviewRound;
    }

    /**
     * Set a reviewer form action
     *
     * @param $action LinkAction
     */
    public function setReviewerFormAction($action)
    {
        $this->_reviewerFormActions[$action->getId()] = $action;
    }

    /**
     * Set current user roles.
     *
     * @param $userRoles Array
     */
    public function setUserRoles($userRoles)
    {
        $this->_userRoles = $userRoles;
    }

    /**
     * Get current user roles.
     *
     * @return $userRoles Array
     */
    public function getUserRoles()
    {
        return $this->_userRoles;
    }

    /**
     * Get all of the reviewer form actions
     *
     * @return array
     */
    public function getReviewerFormActions()
    {
        return $this->_reviewerFormActions;
    }
    //
    // Overridden template methods
    //
    /**
     * @copydoc Form::initData
     */
    public function initData()
    {
        $request = Application::get()->getRequest();
        $reviewerId = (int) $request->getUserVar('reviewerId');
        $context = $request->getContext();
        $reviewRound = $this->getReviewRound();
        $submission = $this->getSubmission();

        // The reviewer id has been set
        if (!empty($reviewerId)) {
            if ($this->_isValidReviewer($context, $submission, $reviewRound, $reviewerId)) {
                $userDao = DAORegistry::getDAO('UserDAO'); /** @var UserDAO $userDao */
                $reviewer = $userDao->getById($reviewerId);
                $this->setData('userNameString', sprintf('%s (%s)', $reviewer->getFullname(), $reviewer->getUsername()));
            }
        }

        // Get review assignment related data;
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /** @var ReviewAssignmentDAO $reviewAssignmentDao */
        $reviewAssignment = $reviewAssignmentDao->getReviewAssignment($reviewRound->getId(), $reviewerId, $reviewRound->getRound());

        // Get the review method (open, anonymous, or double-anonymous)
        if (isset($reviewAssignment) && $reviewAssignment->getReviewMethod() != false) {
            $reviewMethod = $reviewAssignment->getReviewMethod();
            $reviewFormId = $reviewAssignment->getReviewFormId();
        } else {
            // Set default review method.
            $reviewMethod = $context->getData('defaultReviewMode');
            if (!$reviewMethod) {
                $reviewMethod = SUBMISSION_REVIEW_METHOD_DOUBLEANONYMOUS;
            }

            // If there is a section/series and it has a default
            // review form designated, use it.
            $sectionDao = Application::getSectionDAO();
            $section = $sectionDao->getById($submission->getSectionId(), $context->getId());
            if ($section) {
                $reviewFormId = $section->getReviewFormId();
            } else {
                $reviewFormId = null;
            }
        }

        // Get the response/review due dates or else set defaults
        if (isset($reviewAssignment) && $reviewAssignment->getDueDate() != null) {
            $reviewDueDate = strtotime($reviewAssignment->getDueDate());
        } else {
            $numWeeks = (int) $context->getData('numWeeksPerReview');
            if ($numWeeks <= 0) {
                $numWeeks = 4;
            }
            $reviewDueDate = strtotime('+' . $numWeeks . ' week');
        }
        if (isset($reviewAssignment) && $reviewAssignment->getResponseDueDate() != null) {
            $responseDueDate = strtotime($reviewAssignment->getResponseDueDate());
        } else {
            $numWeeks = (int) $context->getData('numWeeksPerResponse');
            if ($numWeeks <= 0) {
                $numWeeks = 3;
            }
            $responseDueDate = strtotime('+' . $numWeeks . ' week');
        }

        // Get the currently selected reviewer selection type to show the correct tab if we're re-displaying the form
        $selectionType = (int) $request->getUserVar('selectionType');
        $stageId = $reviewRound->getStageId();

        $this->setData('submissionId', $this->getSubmissionId());
        $this->setData('stageId', $stageId);
        $this->setData('reviewMethod', $reviewMethod);
        $this->setData('reviewFormId', $reviewFormId);
        $this->setData('reviewRoundId', $reviewRound->getId());
        $this->setData('reviewerId', $reviewerId);

        $context = $request->getContext();
        $templateKey = $this->_getMailTemplateKey($context);
        $template = new SubmissionMailTemplate($submission, $templateKey, null, null, false);
        if ($template) {
            $user = $request->getUser();
            $dispatcher = $request->getDispatcher();
            AppLocale::requireComponents(LOCALE_COMPONENT_PKP_REVIEWER); // reviewer.step1.requestBoilerplate
            $template->assignParams([
                'contextUrl' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath()),
                'editorialContactSignature' => $user->getContactSignature(),
                'signatureFullName' => $user->getFullname(),
                'passwordResetUrl' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'login', 'lostPassword'),
                'messageToReviewer' => __('reviewer.step1.requestBoilerplate'),
                'abstractTermIfEnabled' => ($submission->getLocalizedAbstract() == '' ? '' : __('common.abstract')), // Deprecated; for OJS 2.x templates
            ]);
            $template->replaceParams();
        }
        $this->setData('personalMessage', $template->getBody());
        $this->setData('responseDueDate', $responseDueDate);
        $this->setData('reviewDueDate', $reviewDueDate);
        $this->setData('selectionType', $selectionType);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $context = $request->getContext();

        // Get the review method options.
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /** @var ReviewAssignmentDAO $reviewAssignmentDao */
        $reviewMethods = $reviewAssignmentDao->getReviewMethodsTranslationKeys();
        $submission = $this->getSubmission();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('reviewMethods', $reviewMethods);
        $templateMgr->assign('reviewerActions', $this->getReviewerFormActions());

        $reviewFormDao = DAORegistry::getDAO('ReviewFormDAO'); /** @var ReviewFormDAO $reviewFormDao */
        $reviewFormsIterator = $reviewFormDao->getActiveByAssocId(Application::getContextAssocType(), $context->getId());
        $reviewForms = [];
        while ($reviewForm = $reviewFormsIterator->next()) {
            $reviewForms[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
        }

        $templateMgr->assign('reviewForms', $reviewForms);
        $templateMgr->assign('emailVariables', [
            'reviewerName' => __('user.name'),
            'responseDueDate' => __('reviewer.submission.responseDueDate'),
            'reviewDueDate' => __('reviewer.submission.reviewDueDate'),
            'submissionReviewUrl' => __('common.url'),
            'reviewerUserName' => __('user.username'),
        ]);
        // Allow the default template
        $templateKeys[] = $this->_getMailTemplateKey($request->getContext());

        // Determine if the current user can use any custom templates defined.
        $user = $request->getUser();
        $roleDao = DAORegistry::getDAO('RoleDAO'); /** @var RoleDAO $roleDao */

        $userRoles = $roleDao->getByUserId($user->getId(), $submission->getData('contextId'));
        foreach ($userRoles as $userRole) {
            if (in_array($userRole->getId(), [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT])) {
                $emailTemplatesIterator = Services::get('emailTemplate')->getMany([
                    'contextId' => $submission->getData('contextId'),
                    'isCustom' => true,
                ]);
                $customTemplateKeys = [];
                foreach ($emailTemplatesIterator as $emailTemplate) {
                    $customTemplateKeys[] = $emailTemplate->getData('key');
                };
                $templateKeys = array_merge($templateKeys, $customTemplateKeys);
                break;
            }
        }

        $templates = [];
        foreach ($templateKeys as $templateKey) {
            $thisTemplate = new SubmissionMailTemplate($submission, $templateKey, null, null, null, false);
            $thisTemplate->assignParams([]);
            $templates[$templateKey] = $thisTemplate->getSubject();
        }

        $templateMgr->assign('templates', $templates);

        // Get the reviewer user groups for the create new reviewer/enroll existing user tabs
        $context = $request->getContext();
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
        $reviewRound = $this->getReviewRound();
        $reviewerUserGroups = $userGroupDao->getUserGroupsByStage($context->getId(), $reviewRound->getStageId(), ROLE_ID_REVIEWER);
        $userGroups = [];
        while ($userGroup = $reviewerUserGroups->next()) {
            $userGroups[$userGroup->getId()] = $userGroup->getLocalizedName();
        }

        $this->setData('userGroups', $userGroups);
        return parent::fetch($request, $template, $display);
    }

    /**
     * Assign form data to user-submitted data.
     *
     * @see Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars([
            'selectionType',
            'submissionId',
            'template',
            'personalMessage',
            'responseDueDate',
            'reviewDueDate',
            'reviewMethod',
            'skipEmail',
            'keywords',
            'interests',
            'reviewRoundId',
            'stageId',
            'selectedFiles',
            'reviewFormId',
        ]);
    }

    /**
     * Save review assignment
     */
    public function execute(...$functionParams)
    {
        parent::execute(...$functionParams);

        $submission = $this->getSubmission();
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $currentReviewRound = $this->getReviewRound();
        $stageId = $currentReviewRound->getStageId();
        $reviewDueDate = $this->getData('reviewDueDate');
        $responseDueDate = $this->getData('responseDueDate');

        // Get reviewer id and validate it.
        $reviewerId = (int) $this->getData('reviewerId');

        if (!$this->_isValidReviewer($context, $submission, $currentReviewRound, $reviewerId)) {
            fatalError('Invalid reviewer id.');
        }

        $reviewMethod = (int) $this->getData('reviewMethod');

        $editorAction = new EditorAction();
        $editorAction->addReviewer($request, $submission, $reviewerId, $currentReviewRound, $reviewDueDate, $responseDueDate, $reviewMethod);

        // Get the reviewAssignment object now that it has been added.
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /** @var ReviewAssignmentDAO $reviewAssignmentDao */
        $reviewAssignment = $reviewAssignmentDao->getReviewAssignment($currentReviewRound->getId(), $reviewerId, $currentReviewRound->getRound(), $stageId);
        $reviewAssignment->setDateNotified(Core::getCurrentDate());
        $reviewAssignment->stampModified();

        // Ensure that the review form ID is valid, if specified
        $reviewFormId = (int) $this->getData('reviewFormId');
        $reviewFormDao = DAORegistry::getDAO('ReviewFormDAO'); /** @var ReviewFormDAO $reviewFormDao */
        $reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());
        $reviewAssignment->setReviewFormId($reviewForm ? $reviewFormId : null);

        $reviewAssignmentDao->updateObject($reviewAssignment);

        // Grant access for this review to all selected files.
        $submissionFilesIterator = Services::get('submissionFile')->getMany([
            'submissionIds' => [$submission->getId()],
            'reviewRoundIds' => [$currentReviewRound->getId()],
            'fileStages' => [$stageId == WORKFLOW_STAGE_ID_INTERNAL_REVIEW ? SubmissionFile::SUBMISSION_FILE_INTERNAL_REVIEW_FILE : SubmissionFile::SUBMISSION_FILE_REVIEW_FILE],
        ]);
        $selectedFiles = array_map(function ($id) {
            return (int) $id;
        }, (array) $this->getData('selectedFiles'));
        $reviewFilesDao = DAORegistry::getDAO('ReviewFilesDAO'); /** @var ReviewFilesDAO $reviewFilesDao */
        foreach ($submissionFilesIterator as $submissionFile) {
            if (in_array($submissionFile->getId(), $selectedFiles)) {
                $reviewFilesDao->grant($reviewAssignment->getId(), $submissionFile->getId());
            }
        }

        // Notify the reviewer via email.
        $templateKey = $this->getData('template');
        $mail = new SubmissionMailTemplate($submission, $templateKey, null, null, null, false);
        $userDao = DAORegistry::getDAO('UserDAO'); /** @var UserDAO $userDao */
        $reviewer = $userDao->getById($reviewerId);

        if ($mail->isEnabled() && !$this->getData('skipEmail')) {
            $user = $request->getUser();
            $mail->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
            $mail->setBody($this->getData('personalMessage'));
            $dispatcher = $request->getDispatcher();

            // Set the additional arguments for the one click url
            $reviewUrlArgs = ['submissionId' => $this->getSubmissionId()];
            if ($context->getData('reviewerAccessKeysEnabled')) {
                import('lib.pkp.classes.security.AccessKeyManager');
                $accessKeyManager = new AccessKeyManager();
                $expiryDays = ($context->getData('numWeeksPerReview') + 4) * 7;
                $accessKey = $accessKeyManager->createKey($context->getId(), $reviewerId, $reviewAssignment->getId(), $expiryDays);
                $reviewUrlArgs = array_merge($reviewUrlArgs, ['reviewId' => $reviewAssignment->getId(), 'key' => $accessKey]);
            }

            // Assign the remaining parameters
            $mail->assignParams([
                'reviewerName' => $reviewer->getFullName(),
                'responseDueDate' => $responseDueDate,
                'reviewDueDate' => $reviewDueDate,
                'reviewerUserName' => $reviewer->getUsername(),
                'submissionReviewUrl' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'reviewer', 'submission', null, $reviewUrlArgs)
            ]);
            if (!$mail->send($request)) {
                $notificationMgr = new NotificationManager();
                $notificationMgr->createTrivialNotification($request->getUser()->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
            }
        }

        // Insert a trivial notification to indicate the reviewer was added successfully.
        $currentUser = $request->getUser();
        $notificationMgr = new NotificationManager();
        $msgKey = $this->getData('skipEmail') ? 'notification.addedReviewerNoEmail' : 'notification.addedReviewer';
        $notificationMgr->createTrivialNotification(
            $currentUser->getId(),
            PKPNotification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __($msgKey, ['reviewerName' => $reviewer->getFullName()])]
        );

        return $reviewAssignment;
    }


    //
    // Protected methods.
    //
    /**
     * Get the link action that fetchs the advanced search form content
     *
     * @param $request Request
     *
     * @return LinkAction
     */
    public function getAdvancedSearchAction($request)
    {
        $reviewRound = $this->getReviewRound();
        return new LinkAction(
            'addReviewer',
            new AjaxAction($request->url(null, null, 'reloadReviewerForm', null, [
                'submissionId' => $this->getSubmissionId(),
                'stageId' => $reviewRound->getStageId(),
                'reviewRoundId' => $reviewRound->getId(),
                'selectionType' => REVIEWER_SELECT_ADVANCED_SEARCH,
            ])),
            __('editor.submission.backToSearch'),
            'return'
        );
    }


    //
    // Private helper methods
    //
    /**
     * Check if a given user id is enrolled in reviewer user group.
     *
     * @param $context Context
     * @param $submission Submission
     * @param $reviewRound ReviewRound
     * @param $reviewerId int
     *
     * @return boolean
     */
    public function _isValidReviewer($context, $submission, $reviewRound, $reviewerId)
    {
        $userDao = DAORegistry::getDAO('UserDAO'); /** @var UserDAO $userDao */
        $reviewerFactory = $userDao->getReviewersNotAssignedToSubmission($context->getId(), $submission->getId(), $reviewRound);
        $reviewersArray = $reviewerFactory->toAssociativeArray();
        if (array_key_exists($reviewerId, $reviewersArray)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the email template key depending on if reviewer one click access is
     * enabled or not as well as on review round.
     *
     * @param $context Context The user's current context.
     *
     * @return int Email template key
     */
    public function _getMailTemplateKey($context)
    {
        $reviewerAccessKeysEnabled = $context->getData('reviewerAccessKeysEnabled');
        $round = $this->getReviewRound()->getRound();

        switch (1) {
            case $reviewerAccessKeysEnabled && $round == 1: return 'REVIEW_REQUEST_ONECLICK';
            case $reviewerAccessKeysEnabled: return 'REVIEW_REQUEST_ONECLICK_SUBSEQUENT';
            case $round == 1: return 'REVIEW_REQUEST';
            default: return 'REVIEW_REQUEST_SUBSEQUENT';
        }
    }
}
