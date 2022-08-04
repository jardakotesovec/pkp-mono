<?php

/**
 * @file classes/mail/mailables/SubscriptionNotify.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionNotify
 *
 * @brief Email sent to notify user about new subscription
 */

namespace APP\mail\mailables;

use APP\journal\Journal;
use APP\mail\variables\SubscriptionEmailVariable;
use APP\subscription\Subscription;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\security\Role;

class SubscriptionNotify extends Mailable
{
    use Configurable;
    use Recipient;

    protected static ?string $name = 'mailable.subscriptionNotify.name';
    protected static ?string $description = 'mailable.subscriptionNotify.description';
    protected static ?string $emailTemplateKey = 'SUBSCRIPTION_NOTIFY';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $fromRoleIds = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUBSCRIPTION_MANAGER];
    protected static array $toRoleIds = [Role::ROLE_ID_READER];

    public function __construct(Journal $journal, Subscription $subscription)
    {
        parent::__construct(func_get_args());
    }

    protected static function templateVariablesMap(): array
    {
        $map = parent::templateVariablesMap();
        $map[Subscription::class] = SubscriptionEmailVariable::class;
        return $map;
    }
}
