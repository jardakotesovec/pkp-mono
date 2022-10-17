<?php

/**
 * @file classes/mail/mailables/DiscussionReview.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DiscussionReview
 * @ingroup mail_mailables
 *
 * @brief Email sent when a new query is created or a note is added to a query on the (external) review workflow stage
 */

namespace PKP\mail\mailables;

use APP\submission\Submission;
use PKP\context\Context;
use PKP\mail\traits\Configurable;
use PKP\mail\Mailable;
use PKP\mail\traits\Recipient;
use PKP\mail\traits\Sender;
use PKP\mail\traits\Discussion;
use PKP\mail\traits\Unsubscribe;

class DiscussionReview extends Mailable
{
    use Recipient;
    use Sender;
    use Configurable;
    use Discussion;
    use Unsubscribe;

    protected static ?string $name = 'mailable.discussionReview.name';
    protected static ?string $description = 'mailable.discussionReview.description';
    protected static ?string $emailTemplateKey = 'DISCUSSION_NOTIFICATION';
    protected static bool $canDisable = true;
    protected static array $groupIds = [self::GROUP_REVIEW];

    public function __construct(Context $context, Submission $submission, string $subject, string $content)
    {
        parent::__construct([$context, $submission]);
        $this->setupDiscussionVariables($subject, $content);
    }

    public static function getDataDescriptions(): array
    {
        $variables = parent::getDataDescriptions();
        return self::addDiscussionDescription($variables);
    }

    /**
     * @copydoc PKP\mail\Mailable::embedFooter()
     */
    protected function embedFooter(string $locale): self
    {
        $this->setupUnsubscribeFooter($locale);
        return $this;
    }
}
