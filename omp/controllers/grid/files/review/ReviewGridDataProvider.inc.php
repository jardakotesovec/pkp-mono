<?php

/**
 * @file controllers/grid/files/review/ReviewGridDataProvider.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewGridDataProvider
 * @ingroup controllers_grid_files_review
 *
 * @brief Provide access to review file data for grids.
 */


import('lib.pkp.controllers.grid.files.SubmissionFilesGridDataProvider');

class ReviewGridDataProvider extends SubmissionFilesGridDataProvider {

	/**
	 * Constructor
	 */
	function ReviewGridDataProvider($fileStageId, $viewableOnly = false) {
		parent::SubmissionFilesGridDataProvider($fileStageId);
		$this->_viewableOnly = $viewableOnly;
	}


	//
	// Implement template methods from GridDataProvider
	//
	/**
	 * @see GridDataProvider::getAuthorizationPolicy()
	 */
	function getAuthorizationPolicy($request, $args, $roleAssignments) {
		// Get the parent authorization policy.
		$policy = parent::getAuthorizationPolicy($request, $args, $roleAssignments);

		// Add policy to ensure there is a review round id.
		import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$policy->addPolicy(new ReviewRoundRequiredPolicy($request, $args));

		return $policy;
	}

	/**
	 * @see GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$reviewRound = $this->getReviewRound();
		return array_merge(parent::getRequestArgs(), array(
			'reviewRoundId' => $reviewRound->getId()
			)
		);
	}

	/**
	 * @see GridDataProvider::loadData()
	 */
	function loadData() {
		// Get all review files assigned to this submission.
		$reviewRound = $this->getReviewRound();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFiles = $submissionFileDao->getLatestNewRevisionsByReviewRound($reviewRound, $this->getFileStage());
		return $this->prepareSubmissionFileData($submissionFiles, $this->_viewableOnly);
	}

	//
	// Overridden public methods from FilesGridDataProvider
	//
	/**
	 * @see FilesGridDataProvider::getSelectAction()
	 */
	function getSelectAction($request) {
		import('controllers.grid.files.fileList.linkAction.SelectReviewFilesLinkAction');
		$reviewRound = $this->getReviewRound();
		$modalTitle = __('editor.monograph.review.currentFiles', array('round' => $reviewRound->getRound()));
		return new SelectReviewFilesLinkAction(
			$request, $reviewRound,
			__('editor.monograph.uploadSelectFiles'),
			$modalTitle
		);
	}

	/**
	 * @see FilesGridDataProvider::getAddFileAction()
	 */
	function getAddFileAction($request) {
		import('lib.pkp.controllers.api.file.linkAction.AddFileLinkAction');
		$submission = $this->getSubmission();
		$reviewRound = $this->getReviewRound();

		return new AddFileLinkAction(
			$request, $submission->getId(), $this->getStageId(),
			$this->getUploaderRoles(), $this->getFileStage(),
			null, null, $reviewRound->getId()
		);
	}

	/**
	 * Get the review round object.
	 * @return ReviewRound
	 */
	function getReviewRound() {
		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		return $reviewRound;
	}
}

?>
