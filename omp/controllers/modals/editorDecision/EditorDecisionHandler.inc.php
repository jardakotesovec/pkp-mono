<?php

/**
 * @file controllers/modals/editorDecision/EditorDecisionHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorDecisionHandler
 * @ingroup controllers_modals_editorDecision
 *
 * @brief Handle requests for editors to make a decision
 */

import('classes.handler.Handler');

// import JSON class for use with all AJAX requests
import('lib.pkp.classes.core.JSONMessage');

// Bring in decision constants
import('classes.submission.common.Action');

class EditorDecisionHandler extends Handler {
	/**
	 * Constructor.
	 */
	function EditorDecisionHandler() {
		parent::Handler();

		$this->addRoleAssignment(array(ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER),
				array('newReviewRound', 'saveNewReviewRound', 'initiateReview', 'saveInitiateReview', 'sendReviews',
						'saveSendReviews', 'promote', 'savePromote', 'importPeerReviews', 'sendToProduction'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpWorkflowStageAccessPolicy');
		$this->addPolicy(new OmpWorkflowStageAccessPolicy($request, $args, $roleAssignments, 'monographId', WORKFLOW_STAGE_ID_SUBMISSION));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler actions
	//
	/**
	 * Start a new review round
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function newReviewRound($args, &$request) {
		return $this->_initiateEditorDecision($args, $request, 'NewReviewRoundForm');
	}

	/**
	 * Start a new review round
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function saveNewReviewRound($args, &$request) {
		// Retrieve the authorized monograph.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Form handling.
		import('controllers.modals.editorDecision.form.NewReviewRoundForm');
		$newReviewRoundForm = new NewReviewRoundForm($monograph);

		$newReviewRoundForm->readInputData();
		if ($newReviewRoundForm->validate()) {
			$round = $newReviewRoundForm->execute($args, $request);

			// FIXME: Sending scripts through JSON is evil. This script
			// should (and can) be moved to the client side, #see 6357.
			// When this is done then we can also refactor this method
			// to work with _saveEditorDecision().

			// Generate the new review round tab script.
			$router =& $request->getRouter();
			$dispatcher =& $router->getDispatcher();
			$newRoundUrl = $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'review', array($monograph->getId(), $round));
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('newRoundUrl', $newRoundUrl);
			$templateMgr->assign('round', $round);
			$reviewRoundTabScript = $templateMgr->fetch('controllers/modals/editorDecision/form/reviewRoundTab.tpl');

			// Create a JSON message with the script.
			$additionalAttributes = array('script' => $reviewRoundTabScript);
			$json = new JSONMessage(true, null, true, null, $additionalAttributes);
		} else {
			$json = new JSONMessage(false);
		}

		return $json->getString();
	}

	/**
	 * Start a new review round
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function initiateReview($args, &$request) {
		return $this->_initiateEditorDecision($args, $request, 'InitiateReviewForm');
	}

	/**
	 * Start a new review round
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function saveInitiateReview($args, &$request) {
		return $this->_saveEditorDecision($args, $request, 'InitiateReviewForm', 'review');
	}

	/**
	 * Show a save review form (responsible for request revisions, resubmit for review, and decline submission modals)
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function sendReviews($args, &$request) {
		return $this->_initiateEditorDecision($args, $request, 'SendReviewsForm');
	}

	/**
	 * Save the send review form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function saveSendReviews($args, &$request) {
		return $this->_saveEditorDecision($args, $request, 'SendReviewsForm');
	}

	/**
	 * Show a promote form (responsible for external review and accept submission modals)
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function promote($args, &$request) {
		return $this->_initiateEditorDecision($args, $request, 'PromoteForm');
	}

	/**
	 * Save the send review form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function savePromote($args, &$request) {
		// Redirect to the next workflow page after
		// promoting the submission.
		$decision = (int)$request->getUserVar('decision');

		$redirectOp = null;
        // FIXME: get these URL's from the constants
		if ($decision == SUBMISSION_EDITOR_DECISION_ACCEPT) {
			$redirectOp = 'copyediting';
		} elseif ($decision == SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW) {
			$redirectOp = 'review';
		}

		return $this->_saveEditorDecision($args, $request, 'PromoteForm', $redirectOp);
	}

	/**
	 * Import all free-text/review form reviews to paste into message
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function importPeerReviews($args, &$request) {
		// Retrieve the authorized submission.
		$seriesEditorSubmission =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Retrieve peer reviews.
		import('classes.submission.seriesEditor.SeriesEditorAction');
		$seriesEditorAction = new SeriesEditorAction();
		$peerReviews = $seriesEditorAction->getPeerReviews($seriesEditorSubmission);

		if(empty($peerReviews)) {
			$json = new JSONMessage(false, Locale::translate('editor.review.noReviews'));
		} else {
			$json = new JSONMessage(true, $peerReviews);
		}
		return $json->getString();
	}

	/**
	 * Promote the submission into the production stage
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function sendToProduction(&$args, &$request) {
		// Retrieve the submission.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Move to the production workflow stage
		import('classes.submission.seriesEditor.SeriesEditorAction');
		$seriesEditorAction = new SeriesEditorAction();
		$seriesEditorAction->incrementWorkflowStage($monograph, WORKFLOW_STAGE_ID_PRODUCTION);

		$json = new JSONMessage(true);
		return $json->getString();
	}


	//
	// Private helper methods
	//
	/**
	 * Initiate an editor decision.
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $formName string Name of form to call
	 * @return string Serialized JSON object
	 */
	function _initiateEditorDecision($args, &$request, $formName) {
		// Retrieve the authorized monograph.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		// FIXME: Need to validate the decision (Does it combine with the
		// requested operation? Is it a valid decision? Is the user authorized
		// to take that decision? See #6199.
		$decision = (int)$request->getUserVar('decision');

		// Form handling
		import("controllers.modals.editorDecision.form.$formName");
		$editorDecisionForm = new $formName($monograph, $decision);
		$editorDecisionForm->initData($args, $request);

		$json = new JSONMessage(true, $editorDecisionForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Save an editor decision.
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $formName string Name of form to call
	 * @param $redirectOp string A workflow stage operation to
	 *  redirect to if successful (if any).
	 * @return string Serialized JSON object
	 */
	function _saveEditorDecision($args, &$request, $formName, $redirectOp = null) {
		// Retrieve the authorized monograph.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		// FIXME: Need to validate the decision. See #6199.
		$decision = (int)$request->getUserVar('decision');

		// Form handling
		import("controllers.modals.editorDecision.form.$formName");
		$editorDecisionForm = new $formName($monograph, $decision);

		$editorDecisionForm->readInputData();
		if ($editorDecisionForm->validate()) {
			$editorDecisionForm->execute($args, $request);

			if ($redirectOp) {
				$dispatcher =& $this->getDispatcher();
				$redirectUrl = $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', $redirectOp, array($monograph->getId()));
				return $request->redirectUrlJson($redirectUrl);
			} else {
				$json = new JSONMessage(true);
			}
		} else {
			$json = new JSONMessage(false);
		}
		return $json->getString();
	}
}
?>
