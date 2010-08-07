<?php
/**
 * @file classes/security/authorization/ProcessExecutionHandlerOperationPolicy.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProcessExecutionHandlerOperationPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to handler operations based on a one time key
 *  that authorizes a process to execute.
 */

import('lib.pkp.classes.security.authorization.PublicHandlerOperationPolicy');

class ProcessExecutionHandlerOperationPolicy extends PublicHandlerOperationPolicy {
	/** @var string the process authorization token */
	var $authToken;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $operations array|string either a single operation or a list of operations that
	 *  this policy is targeting.
	 * @param $message string a message to be displayed if the authorization fails
	 */
	function ProcessExecutionHandlerOperationPolicy(&$request, $args, $operations, $message = null) {
		if (isset($args['authToken'])) {
			$this->authToken = $args['authToken'];
		}

		parent::PublicHandlerOperationPolicy($request, $operations, $message);
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Check whether the requested operation is a remote public operation.
		if (parent::effect() == AUTHORIZATION_DENY) {
			return AUTHORIZATION_DENY;
		}

		// Check whether an authentication token is present in the request.
		if (empty($this->authToken) || strlen($this->authToken) != 23) {
			return AUTHORIZATION_DENY;
		}

		// Try to authorize the process with the token.
		$processDao =& DAORegistry::getDAO('ProcessDAO');
		if ($processDao->authorizeProcess($this->authToken)) {
			return AUTHORIZATION_PERMIT;
		}

		// In all other cases deny access.
		return AUTHORIZATION_DENY;
	}
}

?>
