<?php
/**
 * @file classes/security/authorization/OmpPressPolicy.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OmpPressPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control basic access to OMP's press admin components
 */

import('lib.pkp.classes.security.authorization.PolicySet');

class OmpPressPolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function OmpPressPolicy(&$request) {
		parent::PolicySet();

		// 1) Ensure we're in a press
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request, 'No press in context!'));

		// 2) Ensure the user is logged in with a
		//    valid user group id.
		import('lib.pkp.classes.security.authorization.HandlerOperationLoggedInPolicy');
		$this->addPolicy(new HandlerOperationLoggedInPolicy($request));
	}
}

?>
