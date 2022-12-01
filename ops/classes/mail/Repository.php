<?php
/**
 * @file classes/mailable/Repository.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and edit Mailables.
 */

namespace APP\mail;

use APP\mail\mailables\PostedAcknowledgement;
use Illuminate\Support\Collection;
use PKP\context\Context;
use PKP\mail\mailables as pkpMailables;

class Repository extends \PKP\mail\Repository
{
    protected function isMailableEnabled(string $class, Context $context): bool
    {
        if ($class === PostedAcknowledgement::class) {
            return (bool) $context->getData('postedAcknowledgement');
        }
        return parent::isMailableEnabled($class, $context);
    }

    /**
     * Overrides the map from the shared library as OPS uses distinct mailables from OJS and OMP
     */
    public function map(): Collection
    {
        return collect([
            pkpMailables\AnnouncementNotify::class,
            pkpMailables\DecisionAcceptNotifyAuthor::class,
            pkpMailables\DecisionInitialDeclineNotifyAuthor::class,
            pkpMailables\DecisionNotifyOtherAuthors::class,
            pkpMailables\DecisionRevertInitialDeclineNotifyAuthor::class,
            pkpMailables\DiscussionProduction::class,
            pkpMailables\EditorialReminder::class,
            pkpMailables\PasswordReset::class,
            pkpMailables\PasswordResetRequested::class,
            pkpMailables\StatisticsReportNotify::class,
            pkpMailables\SubmissionAcknowledgement::class,
            pkpMailables\SubmissionAcknowledgementNotAuthor::class,
            pkpMailables\UserCreated::class,
            pkpMailables\ValidateEmailContext::class,
            pkpMailables\ValidateEmailSite::class,
            mailables\PostedAcknowledgement::class,
        ]);
    }
}
