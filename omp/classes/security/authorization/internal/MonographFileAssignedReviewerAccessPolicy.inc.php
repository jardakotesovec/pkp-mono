<?php
/**
 * @file classes/security/authorization/internal/MonographFileAssignedReviewerAccessPolicy.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographFileAssignedReviewerAccessPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Monograph file policy to check if the current user is an assigned
 * 	reviewer of the file.
 *
 */

import('lib.pkp.classes.security.authorization.internal.SubmissionFileBaseAccessPolicy');

class MonographFileAssignedReviewerAccessPolicy extends SubmissionFileBaseAccessPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function MonographFileAssignedReviewerAccessPolicy(&$request, $fileIdAndRevision = null) {
		parent::SubmissionFileBaseAccessPolicy($request, $fileIdAndRevision);
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		$request =& $this->getRequest();

		// Get the user
		$user =& $request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Get the monograph file
		$monographFile =& $this->getSubmissionFile($request);
		if (!is_a($monographFile, 'MonographFile')) return AUTHORIZATION_DENY;

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignments =& $reviewAssignmentDao->getByUserId($user->getId());
		$foundValid = false;
		foreach ($reviewAssignments as $reviewAssignment) {
			if (!$reviewAssignment->getDateConfirmed()) continue;

			if (
				$monographFile->getSubmissionId() == $reviewAssignment->getSubmissionId() &&
				$monographFile->getFileStage() == SUBMISSION_FILE_REVIEW_FILE &&
				$monographFile->getViewable()
			) {
				$foundValid = true;
			}
		}

		// Check if the uploader is the current user.
		if ($foundValid) {
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}
}

?>
