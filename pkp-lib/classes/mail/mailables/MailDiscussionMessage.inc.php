<?php

/**
 * @file classes/mail/mailables/MailDiscussionMessage.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MailDiscussionMessage
 * @ingroup mail_mailables
 *
 * @brief Email sent when a message is added to a query
 */

namespace PKP\mail\mailables;

use PKP\context\Context;
use PKP\emailTemplate\EmailTemplate;
use PKP\facades\Repo;
use PKP\mail\Configurable;
use PKP\mail\Mailable;
use PKP\submission\PKPSubmission;
use PKP\mail\Recipient;
use PKP\mail\Sender;

class MailDiscussionMessage extends Mailable
{
    use Recipient;
    use Sender;
    use Configurable;

    public const EMAIL_KEY = 'NOTIFICATION';

    protected static ?string $name = 'mailable.mailDiscussionMessage.name';

    protected static ?string $description = 'mailable.mailDiscussionMessage.description';

    protected static array $groupIds = [self::GROUP_SUBMISSION, self::GROUP_REVIEW, self::GROUP_COPYEDITING, self::GROUP_PRODUCTION];

    public function __construct(Context $context, PKPSubmission $submission)
    {
        parent::__construct(func_get_args());
    }

    public function getTemplate(int $contextId) : EmailTemplate {
        return Repo::emailTemplate()->getByKey($contextId, self::EMAIL_KEY);
    }
}
