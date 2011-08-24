<?php

/**
 * @file controllers/grid/files/review/ReviewerReviewFilesGridDataProvider.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewFilesGridDataProvider
 * @ingroup controllers_grid_files_review
 *
 * @brief Provide reviewer access to review file data for review file grids.
 */


import('controllers.grid.files.review.ReviewGridDataProvider');

class ReviewerReviewFilesGridDataProvider extends ReviewGridDataProvider {
	/**
	 * Constructor
	 */
	function ReviewerReviewFilesGridDataProvider() {
		parent::ReviewGridDataProvider(MONOGRAPH_FILE_REVIEW_FILE);
	}


	//
	// Implement template methods from GridDataProvider
	//
	/**
	 * @see GridDataProvider::getAuthorizationPolicy()
	 * Override the parent class, which defines a Workflow policy, to allow
	 * reviewer access to this grid.
	 */
	function getAuthorizationPolicy(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpSubmissionAccessPolicy');
		// FIXME: Need to authorize review round, see #6200.
		// Get the review round from the request
		$this->setRound($request->getUserVar('round'));
		return new OmpSubmissionAccessPolicy($request, $args, $roleAssignments);
	}
}

?>
