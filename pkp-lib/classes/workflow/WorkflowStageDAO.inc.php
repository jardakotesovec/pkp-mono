<?php

/**
 * @file classes/workflow/WorkflowStageDAO.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowStageDAO
 * @ingroup workflow
 *
 * @brief class for operations involving the workflow stages.
 *
 */

class WorkflowStageDAO extends DAO {

	/**
	 * Constructor.
	 */
	function WorkflowStageDAO() {
		parent::DAO();
	}

	/**
	 * Convert a stage id into a stage path
	 * @param $stageId integer
	 * @return string|null
	 */
	function getPathFromId($stageId) {
		static $stageMapping = array(
			WORKFLOW_STAGE_ID_SUBMISSION => WORKFLOW_STAGE_PATH_SUBMISSION,
			WORKFLOW_STAGE_ID_INTERNAL_REVIEW => WORKFLOW_STAGE_PATH_INTERNAL_REVIEW,
			WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW,
			WORKFLOW_STAGE_ID_EDITING => WORKFLOW_STAGE_PATH_EDITING,
			WORKFLOW_STAGE_ID_PRODUCTION => WORKFLOW_STAGE_PATH_PRODUCTION
		);
		if (isset($stageMapping[$stageId])) {
			return $stageMapping[$stageId];
		}
		return null;
	}

	/**
	 * Convert a stage path into a stage id
	 * @param $stagePath string
	 * @return integer|null
	 */
	function getIdFromPath($stagePath) {
		static $stageMapping = array(
			WORKFLOW_STAGE_PATH_SUBMISSION => WORKFLOW_STAGE_ID_SUBMISSION,
			WORKFLOW_STAGE_PATH_INTERNAL_REVIEW => WORKFLOW_STAGE_ID_INTERNAL_REVIEW,
			WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
			WORKFLOW_STAGE_PATH_EDITING => WORKFLOW_STAGE_ID_EDITING,
			WORKFLOW_STAGE_PATH_PRODUCTION => WORKFLOW_STAGE_ID_PRODUCTION
		);
		if (isset($stageMapping[$stagePath])) {
			return $stageMapping[$stagePath];
		}
		return null;
	}

	/**
	 * Convert a stage id into a stage translation key
	 * @param $stageId integer
	 * @return string|null
	 */
	function getTranslationKeyFromId($stageId) {
		$stageMapping = $this->getWorkflowStageTranslationKeys();

		assert(isset($stageMapping[$stageId]));
		return $stageMapping[$stageId];
	}

	/**
	 * Return a mapping of workflow stages and its translation keys.
	 * @return array
	 */
	static function getWorkflowStageTranslationKeys() {
		$applicationStages = Application::getApplicationStages();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
		static $stageMapping = array(
			WORKFLOW_STAGE_ID_SUBMISSION => 'submission.submission',
			WORKFLOW_STAGE_ID_INTERNAL_REVIEW => 'workflow.review.internalReview',
			WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => 'workflow.review.externalReview',
			WORKFLOW_STAGE_ID_EDITING => 'submission.editorial',
			WORKFLOW_STAGE_ID_PRODUCTION => 'submission.production'
		);

		return array_intersect_key($stageMapping, array_flip($applicationStages));
	}

	/**
	 * Return a mapping of workflow stages, its translation keys and
	 * paths.
	 * @return array
	 */
	function getWorkflowStageKeysAndPaths() {
		$workflowStages = $this->getWorkflowStageTranslationKeys();
		$stageMapping = array();
		foreach ($workflowStages as $stageId => $translationKey) {
			$stageMapping[$stageId] = array(
				'id' => $stageId,
				'translationKey' => $translationKey,
				'path' => $this->getPathFromId($stageId)
			);
		}

		return $stageMapping;
	}

	/**
	 * Returns an array containing data for rendering the stage workflow tabs
	 *  for a submission.
	 * @param $submission Submission
	 * @param $stagesWithDecisions array
	 * @param $stageNotifications array
	 * @return array
	 */
	function getStageStatusesBySubmission($submission, $stagesWithDecisions, $stageNotifications) {

		$stageId = $submission->getStageId();
		$workflowStages = $this->getWorkflowStageKeysAndPaths();

		foreach ($workflowStages as $stageId => $stage) {

			$foundState = false;
			if (!$foundState && $stage['id'] <= $stageId && (in_array($stage['id'], $stagesWithDecisions) || $stage['id'] == WORKFLOW_STAGE_ID_PRODUCTION) && !$stageNotifications[$stage['id']]) {
				$workflowStages[$stageId]['statusKey'] = 'submission.complete';
			}

			if (!$foundState && $stage['id'] < $stageId && !$stageNotifications[$stage['id']]) {
				$foundState = true;
				// Those are stages not initiated, that were skipped, like review stages.
			}

			if (!$foundState && $stage['id'] <= $stageId && ( !in_array($stage['id'], $stagesWithDecisions) || $stageNotifications[$stage['id']])) {
				$workflowStages[$stageId]['statusKey'] = 'submission.initiated';
				$foundState = true;
			}
		}

		return $workflowStages;

	}
}
?>
