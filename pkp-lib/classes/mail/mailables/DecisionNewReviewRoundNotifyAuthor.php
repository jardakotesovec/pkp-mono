<?php

/**
 * @file classes/mail/mailables/DecisionNewReviewRoundNotifyAuthor.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DecisionNewReviewRoundNotifyAuthor
 *
 * @brief Email sent to the author(s) when the following decisions are made:
 *      Decision::NEW_EXTERNAL_ROUND
 */

namespace PKP\mail\mailables;

use APP\decision\Decision;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\mail\traits\Sender;
use PKP\security\Role;

class DecisionNewReviewRoundNotifyAuthor extends Mailable
{
    use Configurable;
    use Recipient;
    use Sender;

    protected static ?string $name = 'mailable.decision.newReviewRound.notifyAuthor.name';
    protected static ?string $description = 'mailable.decision.newReviewRound.notifyAuthor.description';
    protected static ?string $emailTemplateKey = 'EDITOR_DECISION_NEW_ROUND';
    protected static bool $supportsTemplates = true;
    protected static array $groupIds = [self::GROUP_REVIEW];
    protected static array $fromRoleIds = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR];
    protected static array $toRoleIds = [Role::ROLE_ID_AUTHOR];

    public function __construct(Context $context, Submission $submission, Decision $decision)
    {
        parent::__construct(func_get_args());
    }
}
