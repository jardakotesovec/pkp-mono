<?php

/**
 * @file classes/workflow/EditorDecisionActionsManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EditorDecisionActionsManager
 * @ingroup classes_workflow
 *
 * @brief Wrapper class for create and assign editor decisions actions to template manager.
 */

// Defining other decision types as well, because these are not defined in pkp-lib
define('SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW', 8);
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);
define('SUBMISSION_EDITOR_DECISION_NEW_ROUND', 16);
define('SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION', 7);

import('lib.pkp.classes.workflow.PKPEditorDecisionActionsManager');

class EditorDecisionActionsManager extends PKPEditorDecisionActionsManager {

	/**
	 * Get decision actions labels.
	 * @param $request PKPRequest
	 * @param $stageId int
	 * @param $decisions array
	 * @return array
	 */
	function getActionLabels($request, $submission, $stageId, $decisions) {
		$allDecisionsData = $this->_productionStageDecisions($submission);
		$actionLabels = array();

		foreach($decisions as $decision) {
			if (isset($allDecisionsData[$decision]['title'])) {
				$actionLabels[$decision] = $allDecisionsData[$decision]['title'];
			}
		}

		return $actionLabels;
	}

	/**
	 * @copydoc PKPEditorDecisionActionsManager::getStageDecisions()
	 */
	public  function getStageDecisions($request, $submission, $stageId, $makeDecision = true) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_PRODUCTION:
				return $this->_productionStageDecisions($submission, $makeDecision);
		}
		return parent::getStageDecisions($request, $submission, $stageId, $makeDecision);
	}

	//
	// Private helper methods.
	//
	/**
	 * Define and return editor decisions for the production stage.
	 * If the user cannot make decisions i.e. if it is a recommendOnly user,
	 * there will be no decisions options in the production stage.
	 * @param $submission Submission	 
	 * @param $makeDecision boolean If the user can make decisions
	 * @return array
	 */
	protected function _productionStageDecisions($submission, $makeDecision = true) {
		$decisions = array();
		if ($makeDecision) {
			if ($submission->getStatus() == STATUS_QUEUED){
				$decisions = $decisions + array(
					SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE => array(
						'name' => 'decline',
						'operation' => 'sendReviews',
						'title' => 'editor.submission.decision.decline',
					),
				);
			}
			if ($submission->getStatus() == STATUS_DECLINED){
				$decisions = $decisions + array(
					SUBMISSION_EDITOR_DECISION_REVERT_DECLINE => array(
						'name' => 'revert',
						'operation' => 'revertDecline',
						'title' => 'editor.submission.decision.revertDecline',
					),
				);
			}
		}
		return $decisions;
	}

}


