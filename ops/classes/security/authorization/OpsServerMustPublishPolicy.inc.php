<?php
/**
 * @file classes/security/authorization/OpsServerMustPublishPolicy.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpsServerMustPublishPolicy
 * @ingroup security_authorization
 *
 * @brief Access policy to limit access to servers that do not publish online.
 */

import('lib.pkp.classes.security.authorization.PolicySet');
import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class OpsServerMustPublishPolicy extends AuthorizationPolicy
{
    public $_context;

    /**
     * Constructor
     *
     * @param $request PKPRequest
     */
    public function __construct($request)
    {
        parent::__construct('user.authorization.serverDoesNotPublish');
        $this->_context = $request->getContext();
    }

    //
    // Implement template methods from AuthorizationPolicy
    //
    public function effect()
    {
        if (!$this->_context) {
            return AUTHORIZATION_DENY;
        }

        // Certain roles are allowed to see unpublished content.
        $userRoles = (array) $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
        if (count(array_intersect(
            $userRoles,
            [
                ROLE_ID_MANAGER,
                ROLE_ID_SITE_ADMIN,
                ROLE_ID_ASSISTANT,
                ROLE_ID_SUB_EDITOR,
            ]
        )) > 0) {
            return AUTHORIZATION_PERMIT;
        }

        if ($this->_context->getData('publishingMode') == PUBLISHING_MODE_NONE) {
            return AUTHORIZATION_DENY;
        }

        return AUTHORIZATION_PERMIT;
    }
}
