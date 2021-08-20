<?php

/**
 * @file classes/mail/SubmissionMailTemplate.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of MailTemplate for sending emails related to submissions.
 *
 * This allows for submission-specific functionality like logging, etc.
 */

namespace PKP\mail;

use APP\core\Application;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\db\DAORegistry;

use PKP\security\Role;

class SubmissionMailTemplate extends MailTemplate
{
    /** @var object the associated submission */
    public $submission;

    /** @var object the associated context */
    public $context;

    /** @var int Event type of this email for logging purposes */
    public $logEventType;

    /**
     * Constructor.
     *
     * @param $submission Submission
     * @param $emailKey string optional
     * @param $locale string optional
     * @param $context object optional
     * @param $includeSignature boolean optional
     *
     * @see MailTemplate::MailTemplate()
     */
    public function __construct($submission, $emailKey = null, $locale = null, $context = null, $includeSignature = true)
    {
        parent::__construct($emailKey, $locale, $context, $includeSignature);
        $this->submission = $submission;
    }

    /**
     * Assign parameters to template
     *
     * @param $paramArray array
     */
    public function assignParams($paramArray = [])
    {
        $submission = $this->submission;
        $request = Application::get()->getRequest();
        parent::assignParams(array_merge(
            [
                'submissionTitle' => strip_tags($submission->getLocalizedFullTitle()),
                'submissionId' => $submission->getId(),
                'submissionAbstract' => PKPString::stripUnsafeHtml($submission->getLocalizedAbstract()),
                'authorString' => strip_tags($submission->getAuthorString()),
            ],
            $paramArray
        ));
    }

    /**
     * @see parent::send()
     *
     * @param $request PKPRequest optional (used for logging purposes)
     */
    public function send($request = null)
    {
        if (parent::send()) {
            $this->log($request);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @copydoc parent::sendWithParams()
     */
    public function sendWithParams($paramArray)
    {
        $savedSubject = $this->getSubject();
        $savedBody = $this->getBody();

        $this->assignParams($paramArray);

        $ret = $this->send();

        $this->setSubject($savedSubject);
        $this->setBody($savedBody);

        return $ret;
    }

    /**
     * Add logging properties to this email.
     *
     * @param $eventType int
     */
    public function setEventType($eventType)
    {
        $this->logEventType = $eventType;
    }

    /**
     * Set the context this message is associated with.
     *
     * @param $context object
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Save the email in the submission email log.
     *
     * @param null|mixed $request
     */
    public function log($request = null)
    {
        $logDao = DAORegistry::getDAO('SubmissionEmailLogDAO'); /** @var SubmissionEmailLogDAO $logDao */
        $entry = $logDao->newDataObject();
        $submission = $this->submission;

        // Event data
        $entry->setEventType($this->logEventType);
        $entry->setAssocId($submission->getId());
        $entry->setDateSent(Core::getCurrentDate());

        // User data
        if ($request) {
            $user = $request->getUser();
            $entry->setSenderId($user == null ? 0 : $user->getId());
        } else {
            // No user supplied -- this is e.g. a cron-automated email
            $entry->setSenderId(0);
        }

        // Email data
        $entry->setSubject($this->getSubject());
        $entry->setBody($this->getBody());
        $entry->setFrom($this->getFromString(false));
        $entry->setRecipients($this->getRecipientString());
        $entry->setCcs($this->getCcString());
        $entry->setBccs($this->getBccString());

        // Add log entry
        $logEntryId = $logDao->insertObject($entry);
    }

    /**
     *  Send this email to all assigned sub editors in the given stage
     *
     * @param $submissionId int
     * @param $stageId int
     */
    public function toAssignedSubEditors($submissionId, $stageId)
    {
        return $this->_addUsers($submissionId, Role::ROLE_ID_SUB_EDITOR, $stageId, 'addRecipient');
    }

    /**
     *  CC this email to all assigned sub editors in the given stage
     *
     * @param $submissionId int
     * @param $stageId int
     *
     * @return array of Users
     */
    public function ccAssignedSubEditors($submissionId, $stageId)
    {
        return $this->_addUsers($submissionId, Role::ROLE_ID_SUB_EDITOR, $stageId, 'addCc');
    }

    /**
     *  BCC this email to all assigned sub editors in the given stage
     *
     * @param $submissionId int
     * @param $stageId int
     */
    public function bccAssignedSubEditors($submissionId, $stageId)
    {
        return $this->_addUsers($submissionId, Role::ROLE_ID_SUB_EDITOR, $stageId, 'addBcc');
    }

    /**
     * Fetch the requested users and add to the email
     *
     * @param $submissionId int
     * @param $roleId int
     * @param $stageId int
     * @param $method string one of addRecipient, addCC, or addBCC
     *
     * @return array of Users
     */
    protected function _addUsers($submissionId, $roleId, $stageId, $method)
    {
        assert(in_array($method, ['addRecipient', 'addCc', 'addBcc']));

        $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
        $userGroups = $userGroupDao->getByRoleId($this->context->getId(), $roleId);

        $returner = [];
        // Cycle through all the userGroups for this role
        $userDao = Repo::user()->dao;
        while ($userGroup = $userGroups->next()) {
            $userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO'); /** @var UserStageAssignmentDAO $userStageAssignmentDao */
            // FIXME: #6692# Should this be getting users just for a specific user group?
            $collector = Repo::user()->getCollector();
            $collector->filterSubmissionAssignment($submissionId, $stageId, $userGroup->getId());
            $users = $userDao->getMany($collector);
            foreach ($users as $user) {
                $this->$method($user->getEmail(), $user->getFullName());
                $returner[] = $user;
            }
        }
        return $returner;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\mail\SubmissionMailTemplate', '\SubmissionMailTemplate');
}
