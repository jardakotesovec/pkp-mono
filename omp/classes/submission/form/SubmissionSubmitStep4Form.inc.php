<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep4Form.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep4Form
 * @ingroup submission_form
 *
 * @brief Form for Step 4 of author submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep4Form');

use PKP\log\SubmissionLog;
use APP\log\SubmissionEventLogEntry;

class SubmissionSubmitStep4Form extends PKPSubmissionSubmitStep4Form
{
    /**
     * Constructor.
     */
    public function __construct($context, $submission)
    {
        parent::__construct($context, $submission);
    }

    /**
     * Save changes to submission.
     *
     * @return int the submission ID
     */
    public function execute(...$functionParams)
    {
        parent::execute(...$functionParams);

        // Send author notification email
        import('classes.mail.MonographMailTemplate');
        $mail = new MonographMailTemplate($this->submission, 'SUBMISSION_ACK', null, null, false);
        $authorMail = new MonographMailTemplate($this->submission, 'SUBMISSION_ACK_NOT_USER', null, null, false);

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $router = $request->getRouter();
        if ($mail->isEnabled()) {
            // submission ack emails should be from the contact.
            $mail->setFrom($this->context->getData('contactEmail'), $this->context->getData('contactName'));
            $authorMail->setFrom($this->context->getData('contactEmail'), $this->context->getData('contactName'));

            $user = $request->getUser();
            $primaryAuthor = $this->submission->getPrimaryAuthor();
            if (!isset($primaryAuthor)) {
                $authors = $this->submission->getAuthors();
                $primaryAuthor = $authors[0];
            }
            $mail->addRecipient($user->getEmail(), $user->getFullName());

            // Add primary contact and e-mail addresses as specified in the press settings
            if ($this->context->getData('copySubmissionAckPrimaryContact')) {
                $mail->addBcc(
                    $context->getData('contactEmail'),
                    $context->getData('contactName')
                );
            }

            $submissionAckAddresses = $this->context->getData('copySubmissionAckAddress');
            if (!empty($submissionAckAddresses)) {
                $submissionAckAddressArray = explode(',', $submissionAckAddresses);
                foreach ($submissionAckAddressArray as $submissionAckAddress) {
                    $mail->addBcc($submissionAckAddress);
                }
            }

            if ($user->getEmail() != $primaryAuthor->getEmail()) {
                $authorMail->addRecipient($primaryAuthor->getEmail(), $primaryAuthor->getFullName());
            }

            $assignedAuthors = $this->submission->getAuthors();

            foreach ($assignedAuthors as $author) {
                $authorEmail = $author->getEmail();
                // only add the author email if they have not already been added as the primary author
                // or user creating the submission.
                if ($authorEmail != $primaryAuthor->getEmail() && $authorEmail != $user->getEmail()) {
                    $authorMail->addRecipient($author->getEmail(), $author->getFullName());
                }
            }
            $mail->bccAssignedSeriesEditors($this->submissionId, WORKFLOW_STAGE_ID_SUBMISSION);

            $mail->assignParams([
                'authorName' => $user->getFullName(),
                'authorUsername' => $user->getUsername(),
                'editorialContactSignature' => $context->getData('contactName') . "\n" . $context->getLocalizedName(),
                'submissionUrl' => $router->url($request, null, 'authorDashboard', 'submission', $this->submissionId),
            ]);

            $authorMail->assignParams([
                'submitterName' => $user->getFullName(),
                'editorialContactSignature' => $context->getData('contactName') . "\n" . $context->getLocalizedName(),
            ]);

            if (!$mail->send($request)) {
                import('classes.notification.NotificationManager');
                $notificationMgr = new NotificationManager();
                $notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
            }

            $recipients = $authorMail->getRecipients();
            if (!empty($recipients)) {
                if (!$authorMail->send($request)) {
                    import('classes.notification.NotificationManager');
                    $notificationMgr = new NotificationManager();
                    $notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
                }
            }
        }

        // Log submission.
        SubmissionLog::logEvent($request, $this->submission, SubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT, 'submission.event.submissionSubmitted');

        return $this->submissionId;
    }
}
