<?php
/**
 * @file classes/security/authorization/OmpMonographFileAccessPolicy.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OmpMonographFileAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control (write) access to submissions and (read) access to
 * submission details in OMP.
 */

import('classes.security.authorization.internal.PressPolicy');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

// Define the bitfield for monograph file access levels
define('MONOGRAPH_FILE_ACCESS_READ', 1);
define('MONOGRAPH_FILE_ACCESS_MODIFY', 2);

class OmpMonographFileAccessPolicy extends PressPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $roleAssignments array
	 * @param $mode int bitfield MONOGRAPH_FILE_ACCESS_...
	 * @param $submissionParameterName string the request parameter we expect
	 *  the submission id in.
	 */
	function OmpMonographFileAccessPolicy(&$request, $args, $roleAssignments, $mode, $submissionParameterName = 'monographId') {
		// TODO: Refine file access policies. Differentiate between
		// read and modify access using bitfield:
		// $mode & MONOGRAPH_FILE_ACCESS_...

		parent::PressPolicy($request);

		// We need a submission matching the file in the request.
		import('classes.security.authorization.internal.MonographRequiredPolicy');
		$this->addPolicy(new MonographRequiredPolicy($request, $args, $submissionParameterName));
		import('classes.security.authorization.internal.MonographFileMatchesMonographPolicy');
		$this->addPolicy(new MonographFileMatchesMonographPolicy($request));

		// Authors, press managers and series editors potentially have
		// access to submission files. We'll have to define
		// differentiated policies for those roles in a policy set.
		$fileAccessPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);


		//
		// Managerial role
		//
		if (isset($roleAssignments[ROLE_ID_PRESS_MANAGER])) {
			// Press managers have all access to all submissions.
			$fileAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_PRESS_MANAGER, $roleAssignments[ROLE_ID_PRESS_MANAGER]));
		}


		//
		// Series editor role
		//
		if (isset($roleAssignments[ROLE_ID_SERIES_EDITOR])) {
			// 1) Series editors can access all operations on submissions ...
			$seriesEditorFileAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$seriesEditorFileAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SERIES_EDITOR, $roleAssignments[ROLE_ID_SERIES_EDITOR]));

			// 2) ... but only if the requested submission is part of their series.
			import('classes.security.authorization.internal.SeriesAssignmentPolicy');
			$seriesEditorFileAccessPolicy->addPolicy(new SeriesAssignmentPolicy($request));
			$fileAccessPolicy->addPolicy($seriesEditorFileAccessPolicy);
		}


		//
		// Author role
		//
		if (isset($roleAssignments[ROLE_ID_AUTHOR])) {
			// 1) Author role user groups can access whitelisted operations ...
			$authorFileAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$authorFileAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_AUTHOR, $roleAssignments[ROLE_ID_AUTHOR]));

			// 2) ...if they meet one of the following requirements:
			$authorFileAccessOptionsPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

			// 2a) If the file was uploaded by the current user, allow.
			import('classes.security.authorization.internal.MonographFileUploaderAccessPolicy');
			$authorFileAccessOptionsPolicy->addPolicy(new MonographFileUploaderAccessPolicy($request));
			// 2b) If the file is a viewable reviewer response, allow.
			import('classes.security.authorization.internal.MonographFileViewableReviewerResponseAccessPolicy');
			$authorFileAccessOptionsPolicy->addPolicy(new MonographFileViewableReviewerResponseAccessPolicy($request));
			// Add the rules from 2)
			$authorFileAccessPolicy->addPolicy($authorFileAccessOptionsPolicy);

			$fileAccessPolicy->addPolicy($authorFileAccessPolicy);
		}


		//
		// Reviewer role
		//
		if (isset($roleAssignments[ROLE_ID_REVIEWER])) {
			// 1) Reviewers can access whitelisted operations ...
			$reviewerFileAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$reviewerFileAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_REVIEWER, $roleAssignments[ROLE_ID_REVIEWER]));

			// 2) ...if they meet one of the following requirements:
			$reviewerFileAccessOptionsPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

			// 2a) If the file was uploaded by the current user, allow.
			import('classes.security.authorization.internal.MonographFileUploaderAccessPolicy');
			$reviewerFileAccessOptionsPolicy->addPolicy(new MonographFileUploaderAccessPolicy($request));

			// 2b) If the file is part of an assigned review, allow.
			import('classes.security.authorization.internal.MonographFileAssignedReviewerAccessPolicy');
			$reviewerFileAccessOptionsPolicy->addPolicy(new MonographFileAssignedReviewerAccessPolicy($request));

			// Add the rules from 2)
			$reviewerFileAccessPolicy->addPolicy($reviewerFileAccessOptionsPolicy);

			// Add this policy set
			$fileAccessPolicy->addPolicy($reviewerFileAccessPolicy);
		}

		$this->addPolicy($fileAccessPolicy);
	}
}

?>
