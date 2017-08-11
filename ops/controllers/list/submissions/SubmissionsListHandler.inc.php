<?php
/**
 * @file controllers/list/submissions/SubmissionsListHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListHandler
 * @ingroup classes_controllers_list
 *
 * @brief Instantiates and manages a UI component to list submissions.
 */
import('lib.pkp.controllers.list.submissions.PKPSubmissionsListHandler');
import('lib.pkp.classes.db.DBResultRange');
import('lib.pkp.classes.submission.Submission');

class SubmissionsListHandler extends PKPSubmissionsListHandler {

	/**
	 * @copydoc PKPSubmissionsListHandler::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();

		$request = Application::getRequest();
		if ($request->getContext()) {
			if (!isset($config['filters'])) {
				$config['filters'] = array();
			}
			$config['filters']['sectionIds'] = array(
				'heading' => __('section.sections'),
				'filters' => $this->getSectionFilters(),
			);

			// Put the incomplete filter at the end
			if (isset($config['filters']['isIncomplete'])) {
				$isIncompleteFilter = $config['filters']['isIncomplete'];
				unset($config['filters']['isIncomplete']);
				$config['filters']['isIncomplete'] = $isIncompleteFilter;
			}
		}

		return $config;
	}

	/**
	 * @copydoc PKPSubmissionsListHandler::getWorkflowStages()
	 */
	public function getWorkflowStages() {
		return array(
			array(
				'val' => WORKFLOW_STAGE_ID_SUBMISSION,
				'title' => __('manager.publication.submissionStage'),
			),
			array(
				'val' => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
				'title' => __('manager.publication.reviewStage'),
			),
			array(
				'val' => WORKFLOW_STAGE_ID_EDITING,
				'title' => __('submission.copyediting'),
			),
			array(
				'val' => WORKFLOW_STAGE_ID_PRODUCTION,
				'title' => __('manager.publication.productionStage'),
			),
		);
	}

	/**
	 * Compile the sections for passing as filters
	 *
	 * @return array
	 */
	public function getSectionFilters() {
		$request = Application::getRequest();
		$context = $request->getContext();

		if (!$context) {
			return array();
		}

		import('classes.core.ServicesContainer');
		$sections = ServicesContainer::instance()
				->get('section')
				->getSectionList($context->getId());

		return array_map(function($section) {
			return array(
				'val' => $section['id'],
				'title' => $section['title'],
			);
		}, $sections);
	}
}
