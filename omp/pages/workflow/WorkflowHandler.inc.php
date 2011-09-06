<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the copyediting stage of the submssion workflow.
 */


import('classes.handler.Handler');

// import UI base classes
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxModal');

// Import decision constants.
import('classes.submission.common.Action');


class WorkflowHandler extends Handler {
	/**
	 * Constructor
	 */
	function WorkflowHandler() {
		parent::Handler();

		$this->addRoleAssignment(
			array(ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER, ROLE_ID_PRESS_ASSISTANT),
			array('submission', 'internalReview', 'internalReviewRound', 'externalReview', 'externalReviewRound', 'copyediting', 'production')
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpWorkflowStageAccessPolicy');
		$this->addPolicy(new OmpWorkflowStageAccessPolicy($request, $args, $roleAssignments, 'monographId', $this->_identifyStageId($request)));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args) {
		$this->setupTemplate($request);

		// Call parent method.
		parent::initialize($request, $args);
	}

	/**
	 * Setup variables for the template
	 * @param $request Request
	 */
	function setupTemplate(&$request) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OMP_SUBMISSION, LOCALE_COMPONENT_OMP_EDITOR));

		$router =& $request->getRouter();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array($router->url($request, null, 'dashboard', 'status'), 'navigation.submissions')));

		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		// Assign the authorized monograph.
		$templateMgr->assign_by_ref('monograph', $monograph);
		$templateMgr->assign('stageId', $stageId);
		$templateMgr->assign('lastCompletedStageId', $monograph->getStageId());

		// Get the right editor assignment notification type based on current stage id.
		$notificationMgr = new NotificationManager();
		$editorAssignmentNotificationType = $notificationMgr->getEditorAssignmentNotificationTypeByStageId($stageId);

		// Define the workflow notification options.
		$notificationRequestOptions = array(
			NOTIFICATION_LEVEL_TASK => array(
				$editorAssignmentNotificationType => array(ASSOC_TYPE_MONOGRAPH, $monograph->getId())),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);

		$templateMgr->assign('workflowNotificationRequestOptions', $notificationRequestOptions);

		$dispatcher =& $request->getDispatcher();
		import('controllers/modals/submissionMetadata/linkAction/WorkflowViewMetadataLinkAction');
		$editMetadataAction = new WorkflowViewMetadataLinkAction($request, $monograph->getId(), $stageId);

		$templateMgr->assign_by_ref('editMetadataAction', $editMetadataAction);

		$submissionInformationCentreAction = new LinkAction(
			'informationCentre',
			new AjaxModal(
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'informationCenter.SubmissionInformationCenterHandler', 'viewInformationCenter',
				null, array('monographId' => $monograph->getId())
			),
				__('informationCenter.informationCenter')
			),
			__('informationCenter.informationCenter'),
			'information'
		);
		$templateMgr->assign_by_ref('submissionInformationCentreAction', $submissionInformationCentreAction);
	}


	//
	// Public handler methods
	//
	/**
	 * Show the submission stage.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, &$request) {
		$this->_assignEditorDecisionActions($request, '_submissionStageDecisions');

		// Render the view.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display('workflow/submission.tpl');
	}

	/**
	 * Show the internal review stage.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function internalReview($args, &$request) {
		// Use different ops so we can identify stage by op.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewRoundOp', 'internalReviewRound');
		return $this->_review($args, $request);
	}

	/**
	 * Show the external review stage.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function externalReview($args, &$request) {
		// Use different ops so we can identify stage by op.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewRoundOp', 'externalReviewRound');
		return $this->_review($args, $request);
	}

	/**
	 * Internal function to handle both internal and external reviews
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function _review($args, &$request) {
		// Retrieve the authorized submission.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		$selectedStageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		// Retrieve and validate the review round currently being looked at.
		if (count($args) > 1 && is_numeric($args[1])) {
			$selectedRound = (int)$args[1];
		} else {
			$selectedRound = null;
		}

		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO');
		$currentRound = $reviewRoundDao->getCurrentRoundByMonographId($monograph->getId(), $selectedStageId);

		// Make sure round is not higher than the monograph's latest round.
		if(!$selectedRound || $selectedRound > $currentRound) {
			$selectedRound = $currentRound;
		}

		// Add the review stage and the round information to the template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('selectedStageId', $selectedStageId);
		$templateMgr->assign('currentRound', $currentRound);
		$templateMgr->assign('selectedRound', $selectedRound);

		if ($monograph->getStageId() == $selectedStageId) {
			$dispatcher =& $request->getDispatcher();
			$newRoundAction = new LinkAction(
				'newRound',
				new AjaxModal(
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'modals.editorDecision.EditorDecisionHandler',
						'newReviewRound', null, array(
							'monographId' => $monograph->getId(),
							'decision' => SUBMISSION_EDITOR_DECISION_RESUBMIT,
							'stageId' => $selectedStageId
						)
					),
					__('editor.monograph.newRound')
				),
				__('editor.monograph.newRound'),
				'add_item_small'
			); // FIXME: add icon.
			$templateMgr->assign_by_ref('newRoundAction', $newRoundAction);
		}

		// Render the view.
		$templateMgr->display('workflow/review.tpl');
	}

	/**
	 * JSON fetch the internal review round info (tab).
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function internalReviewRound($args, &$request) {
		return $this->_reviewRound($args, $request);
	}

	/**
	 * JSON fetch the external review round info (tab).
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function externalReviewRound($args, &$request) {
		return $this->_reviewRound($args, $request);
	}

	/**
	 * Internal function to handle both internal and external reviews round info (tab content).
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function _reviewRound($args, &$request) {
		// Retrieve the authorized submission.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		// FIXME: #6199 make sure this is not creater than the current round for this stage
		if (!(count($args) > 1 && is_numeric($args[1]))) fatalError('Invalid round given!');
		$round = (int)$args[1];

		// Add the review stage and the round information to the template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('stageId', $stageId);
		$templateMgr->assign('round', $round);

		// Assign editor decision actions to the template.
		$additionalActionArgs = array(
			'stageId' => $stageId,
			'round' => $round
		);
		if ($stageId == WORKFLOW_STAGE_ID_INTERNAL_REVIEW) {
			$callback = '_internalReviewStageDecisions';
		} elseif ($stageId == WORKFLOW_STAGE_ID_EXTERNAL_REVIEW) {
			$callback = '_externalReviewStageDecisions';
		}
		$this->_assignEditorDecisionActions($request, $callback, $additionalActionArgs);

		// Retrieve and assign the review round status.
		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO'); /* @var $reviewRoundDao ReviewRoundDAO */
		$reviewRound =& $reviewRoundDao->build($monograph->getId(), $stageId, $round);
		$templateMgr->assign('roundStatus', $reviewRound->getStatusKey());

		return $templateMgr->fetchJson('workflow/reviewRound.tpl');
	}

	/**
	 * Show the copyediting stage
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function copyediting(&$args, &$request) {
		// Assign editor decision actions to the template.
		$this->_assignEditorDecisionActions($request, '_copyeditingStageDecisions');

		// Render the view.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display('workflow/copyediting.tpl');
	}

	/**
	 * Show the production stage
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function production(&$args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$press =& $request->getContext();
		$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormats =& $publicationFormatDao->getEnabledByPressId($press->getId());

		$templateMgr->assign_by_ref('publicationFormats', $publicationFormats);

		$templateMgr->display('workflow/production.tpl');
	}

	//
	// Private helper methods
	//
	/**
	 * Translate the requested operation to a stage id.
	 * @param $request Request
	 * @return integer One of the WORKFLOW_STAGE_* constants.
	 */
	function _identifyStageId(&$request) {
		if ($stageId = $request->getUserVar('stageId')) {
			return (int) $stageId;
		}
		static $operationAssignment = array(
			'submission' => WORKFLOW_STAGE_ID_SUBMISSION,
			'internalReview' => WORKFLOW_STAGE_ID_INTERNAL_REVIEW,
			'internalReviewRound' => WORKFLOW_STAGE_ID_INTERNAL_REVIEW,
			'externalReview' => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
			'externalReviewRound' => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
			'copyediting' => WORKFLOW_STAGE_ID_EDITING,
			'production' => WORKFLOW_STAGE_ID_PRODUCTION
		);

		// Retrieve the requested operation.
		$router =& $request->getRouter();
		$operation = $router->getRequestedOp($request);

		// Reject rogue requests.
		if(!isset($operationAssignment[$operation])) fatalError('Invalid stage!');

		// Translate the operation to a workflow stage identifier.
		return $operationAssignment[$operation];
	}

	/**
	 * Create actions for editor decisions and assign them to the template.
	 * @param $request Request
	 * @param $decisionsCallback string the name of the class method
	 *  that will return the decision configuration.
	 * @param $additionalArgs array additional action arguments
	 */
	function _assignEditorDecisionActions(&$request, $decisionsCallback, $additionalArgs = array()) {
		// Prepare the action arguments.
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$actionArgs = array('monographId' => $monograph->getId(), 'stageId' => $stageId);
		$actionArgs = array_merge($actionArgs, $additionalArgs);

		// Retrieve the editor decisions.
		$decisions = call_user_func(array($this, $decisionsCallback));

		// Iterate through the editor decisions and create a link action for each decision.
		$dispatcher =& $this->getDispatcher();
		foreach($decisions as $decision => $action) {
			$actionArgs['decision'] = $decision;
			$editorActions[] = new LinkAction(
				$action['name'],
				new AjaxModal(
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'modals.editorDecision.EditorDecisionHandler',
						$action['operation'], null, $actionArgs
					),
					__($action['title'])
				),
				__($action['title']),
				(isset($action['image']) ? $action['image'] : null)
			);
		}
		// Assign the actions to the template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('editorActions', $editorActions);
	}

	/**
	 * Define and return editor decisions for the submission stage.
	 * @return array
	 */
	function _submissionStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_ACCEPT => array(
				'name' => 'accept',
				'operation' => 'promote',
				'title' => 'editor.monograph.decision.accept',
				'image' => 'promote'
			),
			SUBMISSION_EDITOR_DECISION_DECLINE => array(
				'name' => 'decline',
				'operation' => 'sendReviews',
				'title' => 'editor.monograph.decision.decline',
				'image' => 'decline'
			),
			SUBMISSION_EDITOR_DECISION_INITIATE_REVIEW => array(
				'name' => 'initiateReview',
				'operation' => 'initiateReview',
				'title' => 'editor.monograph.initiateReview',
				'image' => 'advance'
			)
		);

		return $decisions;
	}

	/**
	 * Define and return editor decisions for the review stage.
	 * @return array
	 */
	function _internalReviewStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => array(
				'operation' => 'sendReviews',
				'name' => 'requestRevisions',
				'title' => 'editor.monograph.decision.requestRevisions',
				'image' => 'revisions'
			),
			SUBMISSION_EDITOR_DECISION_RESUBMIT => array(
				'operation' => 'sendReviews',
				'name' => 'resubmit',
				'title' => 'editor.monograph.decision.resubmit',
				'image' => 'resubmit'
			),
			SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW => array(
				'operation' => 'promote',
				'name' => 'externalReview',
				'title' => 'editor.monograph.decision.externalReview',
				'image' => 'advance'
			),
			SUBMISSION_EDITOR_DECISION_ACCEPT => array(
				'operation' => 'promote',
				'name' => 'accept',
				'title' => 'editor.monograph.decision.accept',
				'image' => 'promote'
			),
			SUBMISSION_EDITOR_DECISION_DECLINE => array(
				'operation' => 'sendReviews',
				'name' => 'decline',
				'title' => 'editor.monograph.decision.decline',
				'image' => 'decline'
			)
		);

		return $decisions;
	}

	/**
	 * Define and return editor decisions for the review stage.
	 * @return array
	 */
	function _externalReviewStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => array(
				'operation' => 'sendReviews',
				'name' => 'requestRevisions',
				'title' => 'editor.monograph.decision.requestRevisions'
			),
			SUBMISSION_EDITOR_DECISION_RESUBMIT => array(
				'operation' => 'sendReviews',
				'name' => 'resubmit',
				'title' => 'editor.monograph.decision.resubmit'
			),
			SUBMISSION_EDITOR_DECISION_ACCEPT => array(
				'operation' => 'promote',
				'name' => 'accept',
				'title' => 'editor.monograph.decision.accept',
				'image' => 'approve'
			),
			SUBMISSION_EDITOR_DECISION_DECLINE => array(
				'operation' => 'sendReviews',
				'name' => 'decline',
				'title' => 'editor.monograph.decision.decline',
				'image' => 'delete'
			)
		);

		return $decisions;
	}


	/**
	 * Define and return editor decisions for the copyediting stage.
	 * @return array
	 */
	function _copyeditingStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION => array(
				'operation' => 'promote',
				'name' => 'sendToProduction',
				'title' => 'editor.monograph.decision.sendToProduction',
				'image' => 'approve'
			)
		);

		return $decisions;
	}
}

?>
