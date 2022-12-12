<?php

/**
 * @file Jobs/Notifications/NewAnnouncementNotifyUsers.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NewAnnouncementNotifyUsers
 * @ingroup jobs
 *
 * @brief Class to send system notifications when a new announcement is added
 */

namespace PKP\Jobs\Notifications;

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\Notification;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Mail;
use PKP\announcement\Announcement;
use PKP\context\Context;
use PKP\Domains\Jobs\Exceptions\JobException;
use PKP\emailTemplate\EmailTemplate;
use PKP\mail\mailables\AnnouncementNotify;
use PKP\notification\managerDelegate\AnnouncementNotificationManager;
use PKP\Support\Jobs\BaseJob;
use Illuminate\Support\Collection;
use PKP\user\User;

class NewAnnouncementNotifyUsers extends BaseJob
{
    use Batchable;

    protected Collection $recipientIds;
    protected int $contextId;
    protected int $announcementId;
    protected string $locale;

    // Sender of the email, should be set if sendEmail is true
    protected ?User $sender;

    // Whether to send emails; don't send to unsubscribed users
    protected bool $sendEmail = false;

    public function __construct(
        Collection $recipientIds,
        int $contextId,
        int $announcementId,
        string $locale,
        ?User $sender = null,
        bool $sendEmail = false
    )
    {
        parent::__construct();

        $this->recipientIds = $recipientIds;
        $this->contextId = $contextId;
        $this->announcementId = $announcementId;
        $this->locale = $locale;
        if (!is_null($sender)) {
            $this->sender = $sender;
        }
        if ($sendEmail) {
            $this->sendEmail = $sendEmail;
        }
    }

    public function handle()
    {
        $announcement = Repo::announcement()->get($this->announcementId);
        // Announcement was removed
        if (!$announcement) {
            throw new JobException(JobException::INVALID_PAYLOAD);
        }

        $announcementNotificationManager = new AnnouncementNotificationManager(Notification::NOTIFICATION_TYPE_NEW_ANNOUNCEMENT);
        $announcementNotificationManager->initialize($announcement);
        $context = Application::getContextDAO()->getById($this->contextId);
        $template = Repo::emailTemplate()->getByKey($context->getId(), AnnouncementNotify::getEmailTemplateKey());

        foreach ($this->recipientIds as $recipientId) {
            $recipient = Repo::user()->get($recipientId);
            if (!$recipient) {
                continue;
            }
            $notification = $announcementNotificationManager->notify($recipient);

            if (!$this->sendEmail) {
                continue;
            }
            if (!$this->sender) {
                throw new JobException(JobException::INVALID_PAYLOAD);
            }

            // Send email
            $mailable = $this->createMailable($context, $recipient, $announcement, $template);
            $mailable->allowUnsubscribe($notification);
            $mailable->setData($this->locale);
            Mail::send($mailable);
        }
    }

    /**
     * Creates new announcement notification email
     */
    protected function createMailable(
        Context $context,
        User $recipient,
        Announcement $announcement,
        EmailTemplate $template
    ): AnnouncementNotify
    {
        $mailable = new AnnouncementNotify($context, $announcement);

        $mailable->sender($this->sender);
        $mailable->recipients([$recipient]);
        $mailable->body($template->getLocalizedData('body', $this->locale));
        $mailable->subject($template->getLocalizedData('subject', $this->locale));

        return $mailable;
    }
}
