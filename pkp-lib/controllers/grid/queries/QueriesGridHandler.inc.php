<?php

/**
 * @file controllers/grid/queries/QueriesGridHandler.inc.php
 *
 * Copyright (c) 2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueriesGridHandler
 * @ingroup controllers_grid_query
 *
 * @brief base PKP class to handle query grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.queries.QueriesGridCellProvider');


// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class QueriesGridHandler extends GridHandler {

	/** @var integer WORKFLOW_STAGE_ID_... */
	var $_stageId;

	/**
	 * Constructor
	 */
	function QueriesGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_AUTHOR, ROLE_ID_SUB_EDITOR),
			array('fetchGrid', 'fetchRow', 'addQuery', 'editQuery', 'updateQuery', 'readQuery', 'deleteQuery'));
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the authorized submission.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get the authorized query.
	 * @return Query
	 */
	function getQuery() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
	}

	/**
	 * Get the stage id.
	 * @return integer
	 */
	function getStageId() {
		return $this->_stageId;
	}

	//
	// Overridden methods from PKPHandler.
	// Note: this is subclassed in application-specific grids.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = $request->getUserVar('stageId'); // This is being validated in WorkflowStageAccessPolicy
		$this->_stageId = (int)$stageId;

		// Get the stage access policy
		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$workflowStageAccessPolicy = new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId);
		$this->addPolicy($workflowStageAccessPolicy);

		if ($request->getUserVar('queryId')) {
			import('lib.pkp.classes.security.authorization.internal.QueryRequiredPolicy');
			$this->addPolicy(new QueryRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);
		import('lib.pkp.controllers.grid.queries.QueriesGridCellProvider');

		$this->setTitle('submission.queries');
		$this->setInstructions('submission.queriesDescription');

		// Load pkp-lib translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR
		);

		// Columns
		import('lib.pkp.controllers.grid.queries.QueryTitleGridColumn');
		$cellProvider = new QueriesGridCellProvider();
		$this->addColumn(new QueryTitleGridColumn($this->getSubmission(), $this->getStageId()));

		$this->addColumn(
			new GridColumn(
				'replies',
				'submission.query.replies',
				null,
				null,
				$cellProvider,
				array('width' => 10, 'alignment' => COLUMN_ALIGNMENT_CENTER)
			)
		);
		$this->addColumn(
			new GridColumn(
				'from',
				'submission.query.from',
				null,
				null,
				$cellProvider,
				array('html' => TRUE)
			)
		);
		$this->addColumn(
			new GridColumn(
				'lastReply',
				'submission.query.lastReply',
				null,
				null,
				$cellProvider,
				array('html' => TRUE)
			)
		);

		$this->addColumn(
			new GridColumn(
				'closed',
				'submission.query.closed',
				null,
				'controllers/grid/queries/threadClosed.tpl',
				$cellProvider,
				array('width' => 40, 'alignment' => COLUMN_ALIGNMENT_CENTER)
			)
		);

		$router = $request->getRouter();
		$actionArgs = $this->getRequestArgs();
		$this->addAction(
				new LinkAction(
					'addQuery',
					new AjaxModal(
						$router->url($request, null, null, 'addQuery', null, $actionArgs),
						__('grid.action.addQuery'),
						'modal_add_item'
					),
				__('grid.action.addQuery'),
				'add_item'
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		$features = parent::initFeatures($request, $args);
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		$features[] = new OrderGridItemsFeature();

		return $features;
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($row) {
		return $row->getSequence();
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return QueriesGridRow
	 */
	function getRowInstance() {
		import('lib.pkp.controllers.grid.queries.QueriesGridRow');
		return new QueriesGridRow($this->getSubmission(), $this->getStageId());
	}

	/**
	 * Get the arguments that will identify the data in the grid.
	 * Overridden by child grids.
	 * @return array
	 */
	function getRequestArgs() {
		return array(
			'submissionId' => $this->getSubmission()->getId(),
			'stageId' => $this->getStageId(),
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter = null) {
		$queryDao = DAORegistry::getDAO('QueryDAO');
		return $queryDao->getBySubmissionId($this->getSubmission()->getId(), $this->getStageId(), true);
	}

	//
	// Public Query Grid Actions
	//
	/**
	 * Add a query
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function addQuery($args, $request) {
		import('lib.pkp.controllers.grid.queries.form.QueryForm');
		$queryForm = new QueryForm(
			$this->getSubmission(),
			$this->getStageId()
		);
		$queryForm->initData();
		return new JSONMessage(true, $queryForm->fetch($request));
	}

	/**
	 * Delete a query.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteQuery($args, $request) {
		if ($query = $this->getQuery()) {
			$queryDao = DAORegistry::getDAO('QueryDAO');
			$queryDao->deleteObject($query);
			return DAO::getDataChangedEvent($query->getId());
		}
		return new JSONMessage(false); // The query could not be found.
	}

	/**
	 * Read a query
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function readQuery($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'submission' => $this->getSubmission(),
			'stageId' => $this->getStageId(),
			'query' => $this->getQuery(),
		));
		return new JSONMessage(true, $templateMgr->fetch('controllers/grid/queries/readQuery.tpl'));
	}

	/**
	 * Edit a query
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editQuery($args, $request) {
		// Form handling
		import('lib.pkp.controllers.grid.queries.form.QueryForm');
		$queryForm = new QueryForm(
			$this->getSubmission(),
			$this->getStageId(),
			$request->getUserVar('queryId')
		);
		$queryForm->initData();
		return new JSONMessage(true, $queryForm->fetch($request));
	}

	/**
	 * Save a query
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateQuery($args, $request) {
		$query = $this->getQuery();
		import('lib.pkp.controllers.grid.queries.form.QueryForm');
		$queryForm = new QueryForm(
			$this->getSubmission(),
			$this->getStageId(),
			$query->getId()
		);
		$queryForm->readInputData();
		if ($queryForm->validate()) {
			$queryForm->execute($request);

			if(!isset($query)) {
				// New added query action notification content.
				$notificationContent = __('notification.addedQuery');
			} else {
				// Query edit action notification content.
				$notificationContent = __('notification.editedQuery');
			}

			// Create trivial notification.
			$currentUser = $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => $notificationContent));

			// Render the row into a JSON response
			return DAO::getDataChangedEvent($query->getId());
		} else {
			return new JSONMessage(true, $queryForm->fetch($request));
		}
	}
}

?>
