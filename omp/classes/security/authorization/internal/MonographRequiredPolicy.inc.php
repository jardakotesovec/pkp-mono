<?php
/**
 * @file classes/security/authorization/internal/MonographRequiredPolicy.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid monograph.
 */

import('lib.pkp.classes.security.authorization.SubmissionRequiredPolicy');

class MonographRequiredPolicy extends SubmissionRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $submissionParameterName string the request parameter we expect
	 *  the submission id in.
	 */
	function MonographRequiredPolicy(&$request, &$args, $submissionParameterName = 'monographId') {
		parent::SubmissionRequiredPolicy($request, $args, $submissionParameterName, 'Invalid monograph or no monograph requested!');
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the monograph id.
		$monographId = $this->getSubmissionId();
		if ($monographId === false) return AUTHORIZATION_DENY;

		// Validate the monograph id.
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$monograph =& $monographDao->getMonograph($monographId);
		if (!is_a($monograph, 'Monograph')) return AUTHORIZATION_DENY;

		// Save the monograph to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH, $monograph);
		return AUTHORIZATION_PERMIT;
	}
}

?>
