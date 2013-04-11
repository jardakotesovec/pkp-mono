<?php

/**
 * @file pages/reviewer/ReviewerHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

import('classes.handler.Handler');
import('classes.submission.reviewer.ReviewerAction');

import('lib.pkp.classes.core.JSONMessage');

class ReviewerHandler extends Handler {
	/**
	 * Constructor
	 */
	function ReviewerHandler() {
		parent::Handler();
		$this->addRoleAssignment(
			ROLE_ID_REVIEWER, array(
				'submission', 'step', 'saveStep',
				'showDeclineReview', 'saveDeclineReview', 'downloadFile'
			)
		);
	}

	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display the submission review page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, $request) {
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO'); /* @var $reviewerSubmissionDao ReviewerSubmissionDAO */
		$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('submission', $reviewerSubmission);
		$templateMgr->assign('reviewIsCompleted', $reviewAssignment->getDateCompleted()?1:0);
		$templateMgr->display('reviewer/review/reviewStepHeader.tpl');
	}

	/**
	 * Display a step tab contents in the submission review page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function step($args, $request) {
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		$reviewId = (int) $reviewAssignment->getId();
		assert(!empty($reviewId));

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO'); /* @var $reviewerSubmissionDao ReviewerSubmissionDAO */
		$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
		$this->setupTemplate($request);

		$reviewStep = max($reviewerSubmission->getStep(), 1); // Get the current saved step from the DB
		$userStep = (int) $request->getUserVar('step');
		$step = (int) (!empty($userStep) ? $userStep: $reviewStep);
		if($step > $reviewStep) $step = $reviewStep; // Reviewer can't go past incomplete steps
		if ($step<1 || $step>4) fatalError('Invalid step!');

		if($step < 4) {
			$formClass = "ReviewerReviewStep{$step}Form";
			import("classes.submission.reviewer.form.$formClass");

			$reviewerForm = new $formClass($request, $reviewerSubmission, $reviewAssignment);

			if ($reviewerForm->isLocaleResubmit()) {
				$reviewerForm->readInputData();
			} else {
				$reviewerForm->initData();
			}
			$json = new JSONMessage(true, $reviewerForm->fetch($request));
			return $json->getString();
		} else {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign_by_ref('submission', $reviewerSubmission);
			$templateMgr->assign('step', 4);
			return $templateMgr->fetchJson('reviewer/review/reviewCompleted.tpl');
		}
	}

	/**
	 * Save a review step.
	 * @param $args array first parameter is the step being saved
	 * @param $request PKPRequest
	 */
	function saveStep($args, $request) {
		$step = (int)$request->getUserVar('step');
		if ($step<1 || $step>3) fatalError('Invalid step!');

		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		if ($reviewAssignment->getDateCompleted()) fatalError('Review already completed!');

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		$formClass = "ReviewerReviewStep{$step}Form";
		import("classes.submission.reviewer.form.$formClass");

		$reviewerForm = new $formClass($request, $reviewerSubmission, $reviewAssignment);
		$reviewerForm->readInputData();

		if ($reviewerForm->validate()) {
			$reviewerForm->execute($request);
			$json = new JSONMessage(true);
			$json->setEvent('setStep', $step+1);
		} else {
			$json = new JSONMessage(true, $reviewerForm->fetch($request));
		}
		return $json->getString();
	}

	/**
	 * Show a form for the reviewer to enter regrets into.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function showDeclineReview($args, $request) {
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submissionId', $reviewerSubmission->getId());

		return $templateMgr->fetchJson('reviewer/review/modal/regretMessage.tpl');
	}

	/**
	 * Save the reviewer regrets form and decline the review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveDeclineReview($args, $request) {
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		if ($reviewAssignment->getDateCompleted()) fatalError('Review already completed!');

		$reviewId = (int) $reviewAssignment->getId();
		$declineReviewMessage = $request->getUserVar('declineReviewMessage');

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewId);
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		// Save regret message
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getById($reviewId);
		assert(is_a($reviewAssignment, 'ReviewAssignment'));
		$reviewAssignment->setRegretMessage($declineReviewMessage);
		$reviewAssignmentDao->updateObject($reviewAssignment);

		$reviewerAction = new ReviewerAction();
		$reviewerAction->confirmReview($request, $reviewerSubmission, true, true);
		$dispatcher = $request->getDispatcher();
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'index'));
	}

	/**
	 * Setup common template variables.
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_GRID);
	}


	//
	// Private helper methods
	//
	function _retrieveStep() {
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		$reviewId = (int) $reviewAssignment->getId();
		assert(!empty($reviewId));
		return $reviewId;
	}
}

?>
