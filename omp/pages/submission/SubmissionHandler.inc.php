<?php

/**
 * @file pages/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup pages_submission
 *
 * @brief Handle requests for the submission wizard.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');
import('lib.pkp.pages.submission.PKPSubmissionHandler');

class SubmissionHandler extends PKPSubmissionHandler {
	/**
	 * Constructor
	 */
	function SubmissionHandler() {
		parent::Handler();
		$this->addRoleAssignment(array(ROLE_ID_AUTHOR, ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
				array('index', 'wizard', 'step', 'saveStep', 'fetchChoices'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		// The policy for the submission handler depends on the
		// step currently requested.
		$step = isset($args[0]) ? (int) $args[0] : 1;
		if ($step<1 || $step>4) return false;

		// Do we have a submission present in the request?
		$submissionId = (int)$request->getUserVar('submissionId');

		// Are we in step one without a submission present?
		if ($step === 1 && $submissionId === 0) {
			// Authorize submission creation.
			import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
			$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));
		} else {
			// Authorize editing of incomplete submissions.
			import('classes.security.authorization.OmpSubmissionAccessPolicy');
			$this->addPolicy(new OmpSubmissionAccessPolicy($request, $args, $roleAssignments, 'submissionId'));
		}

		// Do policy checking.
		if (!parent::authorize($request, $args, $roleAssignments)) return false;

		// Execute additional checking of the step.
		// NB: Move this to its own policy for reuse when required in other places.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		// Permit if there is no submission set, but request is for initial step.
		if (!is_a($submission, 'Submission') && $step == 1) return true;

		// In all other cases we expect an authorized submission due to
		// the submission access policy above.
		assert(is_a($submission, 'Submission'));

		// Deny if submission is complete (==0 means complete) and at
		// any step other than the "complete" step (=4)
		if ($submission->getSubmissionProgress() == 0 && $step != 4 ) return false;

		// Deny if trying to access a step greater than the current progress
		if ($submission->getSubmissionProgress() != 0 && $step > $submission->getSubmissionProgress()) return false;

		return true;
	}


	//
	// Public Handler Methods
	//
	/**
	 * Redirect to the new submission wizard by default.
	 * @param $args array
	 * @param $request Request
	 */
	function index($args, $request) {
		$request->redirect(null, null, 'wizard');
	}

	/**
	 * Display the tab set for the submission wizard.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function wizard($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$step = isset($args[0]) ? (int) $args[0] : 1;
		$templateMgr->assign('step', $step);

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		if ($submission) {
			$templateMgr->assign('submissionId', $submission->getId());
			$templateMgr->assign('submissionProgress', (int) $submission->getSubmissionProgress());
		} else {
			$templateMgr->assign('submissionProgress', 1);
		}
		$templateMgr->display('submission/form/submitStepHeader.tpl');
	}

	/**
	 * Display a step for the submission wizard.
	 * Displays submission index page if a valid step is not specified.
	 * @param $args array
	 * @param $request Request
	 */
	function step($args, $request) {
		$step = isset($args[0]) ? (int) $args[0] : 1;

		$context = $request->getContext();
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$this->setupTemplate($request);

		if ( $step < 4 ) {
			$formClass = "SubmissionSubmitStep{$step}Form";
			import("classes.submission.form.$formClass");

			$submitForm = new $formClass($context, $submission);
			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$json = new JSONMessage(true, $submitForm->fetch($request));
                        return $json->getString();
			$submitForm->display($request);
		} elseif($step == 4) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign('context', $context);

			// Retrieve the correct url for author review his submission.
			import('lib.pkp.controllers.grid.submissions.SubmissionsListGridCellProvider');
			list($page, $operation) = SubmissionsListGridCellProvider::getPageAndOperationByUserRoles($request, $submission);
			$router = $request->getRouter();
			$dispatcher = $router->getDispatcher();
			$reviewSubmissionUrl = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), $page, $operation, $submission->getId());

			$templateMgr->assign('reviewSubmissionUrl', $reviewSubmissionUrl);
			$templateMgr->assign('submissionId', $submission->getId());
			$templateMgr->assign('submitStep', $step);
			$templateMgr->assign('submissionProgress', $submission->getSubmissionProgress());

			$json = new JSONMessage(true, $templateMgr->fetch('submission/form/complete.tpl'));
                        return $json->getString();
		}
	}

	/**
	 * Save a submission step.
	 * @param $args array first parameter is the step being saved
	 * @param $request Request
	 */
	function saveStep($args, $request) {
		$step = isset($args[0]) ? (int) $args[0] : 1;

		$router = $request->getRouter();
		$press = $router->getContext($request);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$this->setupTemplate($request);

		$formClass = "SubmissionSubmitStep{$step}Form";
		import("classes.submission.form.$formClass");

		$submitForm = new $formClass($press, $submission);
		$submitForm->readInputData();

		if (!HookRegistry::call('SubmissionHandler::saveSubmit', array($step, &$submission, &$submitForm))) {
			if ($submitForm->validate()) {
				$submissionId = $submitForm->execute($args, $request);
				if (!$submission) {
					return $request->redirectUrlJson($router->url($request, null, null, 'wizard', $step+1, array('submissionId' => $submissionId), '2'));
				}
				$json = new JSONMessage(true);
				$json->setEvent('setStep', max($step+1, $submission->getSubmissionProgress()));
			} else {
				$json = new JSONMessage(true, $submitForm->fetch($request));
			}
			return $json->getString();
		}
	}


	//
	// Protected helper methods
	//
	/**
	 * Setup common template variables.
	 * @param $request Request
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION);

		// Get steps information.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('steps', $this->_getStepsNumberAndLocaleKeys());
	}


	//
	// Private helper methods.
	//
	function _getStepsNumberAndLocaleKeys() {
		return array(
			1 => 'submission.submit.prepare',
			2 => 'submission.submit.upload',
			3 => 'submission.submit.catalog',
			4 => 'submission.submit.nextSteps'
		);
	}
}
?>
