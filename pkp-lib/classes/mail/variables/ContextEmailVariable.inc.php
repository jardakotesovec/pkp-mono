<?php

/**
 * @file classes/mail/variables/ContextEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables that are associated with a given context (journal or press)
 */

namespace PKP\mail\variables;

use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;

class ContextEmailVariable extends Variable
{
    const CONTEXT_NAME = 'contextName';
    const CONTEXT_URL = 'contextUrl';
    const CONTACT_NAME = 'contactName';
    const PRINCIPAL_CONTACT_SIGNATURE = 'principalContactSignature';
    const CONTACT_EMAIL = 'contactEmail';
    const PASSWORD_LOST_URL = 'passwordLostUrl';

    protected Context $context;

    protected PKPRequest $request;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->request = PKPApplication::get()->getRequest();
    }

    /**
     * @copydoc Variable::description()
     */
    protected static function description() : array
    {
        return
        [
            self::CONTACT_NAME => __('emailTemplate.variable.context.contactName'),
            self::PRINCIPAL_CONTACT_SIGNATURE => __('emailTemplate.variable.context.principalContactSignature'),
            self::CONTACT_EMAIL => __('emailTemplate.variable.context.contactEmail'),
            self::PASSWORD_LOST_URL => __('emailTemplate.variable.context.passwordLostUrl'),
        ];
    }

    /**
     * @copydoc Variable::values()
     */
    public function values(string $locale): array
    {
        return
        [
            self::CONTACT_NAME => $this->context->getData('contactName'),
            self::PRINCIPAL_CONTACT_SIGNATURE => $this->getPrincipalContactSignature($locale),
            self::CONTACT_EMAIL => $this->context->getData('contactEmail'),
            self::PASSWORD_LOST_URL => $this->getPasswordLostUrl(),
        ];
    }

    protected function getContextUrl() : string
    {
        return $this->request->getDispatcher()->url($this->request, PKPApplication::ROUTE_PAGE, $this->context->getData('urlPath'));
    }

    protected function getPrincipalContactSignature(string $locale) : string
    {
        return $this->context->getData('contactName')
            . "\n"
            . $this->context->getLocalizedData('name', $locale);
    }

    /**
     * URL to the lost password page
     */
    protected function getPasswordLostUrl() : string
    {
        return $this->request->getDispatcher()->url($this->request, PKPApplication::ROUTE_PAGE, $this->context->getData('urlPath'), 'login', 'lostPassword');
    }
}
