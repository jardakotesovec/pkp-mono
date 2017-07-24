<?php
/**
 * @file classes/controllers/list/submissions/SubmissionListHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionListHandler
 * @ingroup classes_controllers_list
 *
 * @brief A base class for submission list handlers. Sub-classes should extend
 *  this class by defining a `getItems` method to fetch a particular set of
 *  submissions (eg - active, archived, assigned to me).
 */
import('lib.pkp.controllers.list.ListHandler');

abstract class SubmissionListHandler extends ListHandler {
	/**
	 * Component path
	 *
	 * Used to generate component URLs
	 */
	public $_componentPath = 'list.submissions.SubmissionListHandler';

	/**
	 * Define the routes this component supports
	 *
	 * @return array Routes supported by this component
	 */
	public function setRoutes() {

		$this->addRoute('get', array(
			'methods' => array('GET'),
			'roleAccess' => array(
				ROLE_ID_SITE_ADMIN,
				ROLE_ID_MANAGER,
				ROLE_ID_SUB_EDITOR,
			),
		));

		$this->addRoute('delete', array(
			'methods' => array('POST'),
			'roleAccess' => array(
				ROLE_ID_MANAGER,
			),
		));

		return $this->_routes;
	}

    /**
     * Retrieve the configuration data to be used when initializing this
     * handler on the frontend
     *
     * return array Configuration data
     */
    public function getConfig() {

		$config = parent::getConfig();

		$request = Application::getRequest();

		// URL to add a new submission
		$config['addUrl'] = $request->getDispatcher()->url(
			$request,
			ROUTE_PAGE,
			null,
			'submission',
			'wizard'
		);

		// URL to view info center for a submission
		$config['infoUrl'] = $request->getDispatcher()->url(
			$request,
			ROUTE_COMPONENT,
			null,
			'informationCenter.SubmissionInformationCenterHandler',
			'viewInformationCenter',
			null,
			array('submissionId' => '__id__')
		);

		$config['i18n']['add'] = __('submission.submit.newSubmissionSingle');
		$config['i18n']['search'] = __('common.search');
		$config['i18n']['itemCount'] = __('submission.list.count');
		$config['i18n']['delete'] = __('common.delete');
		$config['i18n']['infoCenter'] = __('submission.list.infoCenter');
		$config['i18n']['ok'] = __('common.ok');
		$config['i18n']['cancel'] = __('common.cancel');
		$config['i18n']['confirmDelete'] = __('common.confirmDelete');

		// Attach a CSRF token for post requests
		$config['csrfToken'] = $request->getSession()->getCSRFToken();

		return $config;
    }

	/**
	 * API Route: Get all submissions assigned to author
	 *
	 * @param array $args None supported at this time
	 * @param Request $request
	 */
	public function get($args, $request) {
		echo json_encode($this->getItems($args));
		exit();
	}

	/**
	 * API Route: delete a submission
	 *
	 * @param $args int ID of the submission to delete
	 * @param $request PKPRequest
	 * return JSONMessage
	 */
	public function delete($args, $request) {

		if (!$request->checkCSRF()) {
			return new JSONMessage(false);
		}

		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById(
			(int) $request->getUserVar('id')
		);

		if (!$submission) {
			return new JSONMessage(false);
		}

		$submissionDao->deleteById($submission->getId());

		$json = DAO::getDataChangedEvent($submission->getId());
		$json->setGlobalEvent('submissionDeleted', array('id' => $submission->getId()));
		return $json;
	}
}
