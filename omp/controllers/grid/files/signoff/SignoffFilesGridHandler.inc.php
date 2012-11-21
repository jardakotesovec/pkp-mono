<?php

/**
 * @file controllers/grid/files/copyedit/SignoffFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SignoffFilesGridHandler
 * @ingroup controllers_grid_files_signoff
 *
 * @brief Base grid for providing a list of files as categories and the requested signoffs on that file as rows.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.CategoryGridHandler');

// import copyediting grid specific classes
import('controllers.grid.files.signoff.SignoffFilesGridCategoryRow');
import('controllers.grid.files.signoff.SignoffGridRow');
import('controllers.grid.files.signoff.SignoffGridCellProvider');
import('controllers.grid.files.signoff.SignoffFilesGridCellProvider');

// Link actions
import('lib.pkp.classes.linkAction.request.AjaxModal');

class SignoffFilesGridHandler extends CategoryGridHandler {
	/* @var int */
	var $_stageId;

	/* @var string */
	var $_symbolic;

	/* @var int */
	var $_fileStage;

	/* @var string */
	var $_eventType;

	/* @var int */
	var $_assocType;

	/* @var int */
	var $_assocId;


	/**
	 * Constructor
	 */
	function SignoffFilesGridHandler($stageId, $fileStage, $symbolic, $eventType, $assocType = null, $assocId = null) {
		$this->_stageId = $stageId;
		$this->_fileStage = $fileStage;
		$this->_symbolic = $symbolic;
		$this->_eventType = $eventType;
		$this->_assocType = $assocType;
		$this->_assocId = $assocId;

		$this->addRoleAssignment(
			array(ROLE_ID_PRESS_MANAGER, ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_ASSISTANT),
			array(
				'fetchGrid', 'fetchCategory', 'fetchRow', 'returnFileRow', 'returnSignoffRow',
				'addAuditor', 'saveAddAuditor', 'getAuditorAutocomplete',
				'signOffsignOff', 'deleteSignOffSignOff', 'deleteSignoff', 'viewLibrary',
				'editReminder', 'sendReminder'
			)
		);
		parent::CategoryGridHandler();
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
		import('classes.security.authorization.OmpWorkflowStageAccessPolicy');
		$this->addPolicy(new OmpWorkflowStageAccessPolicy($request, $args, $roleAssignments, 'monographId', $this->getStageId()));

		// If a signoff ID was specified, authorize it.
		if ($request->getUserVar('signoffId')) {
			import('classes.security.authorization.OmpSignoffAccessPolicy');
			$this->addPolicy(new OmpSignoffAccessPolicy($request, $args, $roleAssignments, SIGNOFF_ACCESS_MODIFY, $this->getStageId()));
		}

		// If a publication ID was specified, authorize it.
		if ($request->getUserVar('publicationFormatId')) {
			import('classes.security.authorization.internal.PublicationFormatRequiredPolicy');
			$this->addPolicy(new PublicationFormatRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);

		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_COMMON,
			LOCALE_COMPONENT_APPLICATION_COMMON,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_OMP_EDITOR,
			LOCALE_COMPONENT_OMP_SUBMISSION
		);

		$monograph =& $this->getMonograph();

		// Bring in file constants
		import('classes.monograph.MonographFile');

		// Grid actions
		// Action to add a file -- Adds a category row for the file
		import('controllers.api.file.linkAction.AddFileLinkAction');
		$this->addAction(new AddFileLinkAction(
			$request, $monograph->getId(),
			$this->getStageId(),
			array(ROLE_ID_PRESS_MANAGER, ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_ASSISTANT),
			$this->getFileStage(),
			$this->getAssocType(), $this->getAssocId()
		));

		// Action to signoff on a file -- Lets user interact with their own rows.
		$user =& $request->getUser();
		$signoffDao =& DAORegistry::getDAO('MonographFileSignoffDAO'); /* @var $signoffDao MonographFileSignoffDAO */
		$signoffFactory =& $signoffDao->getAllByMonograph($monograph->getId(), $this->getSymbolic(), $user->getId(), null, true);
		if (!$signoffFactory->wasEmpty()) {
			import('controllers.api.signoff.linkAction.AddSignoffFileLinkAction');
			$this->addAction(new AddSignoffFileLinkAction(
				$request, $monograph->getId(),
				$this->getStageId(), $this->getSymbolic(), null,
				__('submission.upload.signoff'), __('submission.upload.signoff')));
		}

		$router =& $request->getRouter();

		// Action to add a user -- Adds the user as a subcategory to the files selected in its modal
		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$monographFiles =& $submissionFileDao->getLatestRevisions($monograph->getId(), $this->getFileStage());

		// The "Add Auditor" link should only be available if at least
		// one file already exists.
		if (!empty($monographFiles)) {
			$this->addAction(new LinkAction(
				'addAuditor',
				new AjaxModal(
					$router->url($request, null, null, 'addAuditor', null, $this->getRequestArgs()),
					__('editor.monograph.addAuditor'),
					'modal_add_user'
				),
				__('editor.monograph.addAuditor'),
				'add_user'
			));
		}

		//
		// Grid Columns
		//
		$userIds = $this->_getSignoffCapableUsersId();

		// Add a column for the file's label
		$this->addColumn(
			new GridColumn(
				'name',
				'common.file',
				null,
				'controllers/grid/gridCell.tpl',
				new SignoffGridCellProvider($monograph->getId(), $this->getStageId()),
				array('alignment' => COLUMN_ALIGNMENT_LEFT, 'width' => 60)
			)
		);

		// Add the considered column (signoff).
		import('controllers.grid.files.SignoffOnSignoffGridColumn');
		$this->addColumn(new SignoffOnSignoffGridColumn('common.considered', $userIds, $this->getRequestArgs(), array('hoverTitle' => true)));

		// Add approved column (make the file visible). This column
		// will only have content in category rows, so we define
		// a cell provider there. See getCategoryRowInstance().
		import('lib.pkp.classes.controllers.grid.GridColumn');
		import('lib.pkp.classes.controllers.grid.NullGridCellProvider');
		$this->addColumn(new GridColumn(
			'approved',
			'editor.signoff.approved', null, 'controllers/grid/gridCell.tpl',
			new NullGridCellProvider())
		);

		// Set the no-row locale key
		$this->setEmptyRowText('grid.noFiles');
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the monograph associated with this chapter grid.
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
	}


	/**
	 * Get the workflow stage id.
	 * @return integer
	 */
	function getStageId() {
		return $this->_stageId;
	}


	/**
	 * Get the signoff's symbolic
	 * @return string
	 */
	function getSymbolic() {
		return $this->_symbolic;
	}


	/**
	 * Get the fileStage (for categories)
	 */
	function getFileStage() {
		return $this->_fileStage;
	}


	/**
	 * Get the email key
	 */
	function getEventType() {
		return $this->_eventType;
	}


	/**
	 * Get the assoc type
	 */
	function getAssocType() {
		return $this->_assocType;
	}


	/**
	 * Set the assoc Id
	 */
	function setAssocId($assocId) {
		$this->_assocId = $assocId;
	}


	/**
	 * Get the assoc id
	 */
	function getAssocId() {
		return $this->_assocId;
	}

	/**
	 * Get publication format, if any.
	 * @return PublicationFormat
	 */
	function &getPublicationFormat() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_PUBLICATION_FORMAT);
	}


	/**
	 * @see GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$monograph =& $this->getMonograph();
		$signoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);
		$args = array_merge(
			parent::getRequestArgs(),
			array('monographId' => $monograph->getId(),
				'stageId' => $this->getStageId())
		);

		if (is_a($signoff, 'Signoff')) {
			$args['signoffId'] = $signoff->getId();
		}

		return $args;
	}


	/**
	 * @see GridHandler::loadData
	 */
	function &loadData(&$request, $filter) {
		// Grab the files to display as categories
		$monograph =& $this->getMonograph();
		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		if ($this->getAssocType() && $this->getAssocId()) {
			$monographFiles =& $submissionFileDao->getLatestRevisionsByAssocId(
				$this->getAssocType(), $this->getAssocId(),
				$monograph->getId(), $this->getFileStage()
			);
		} else {
			$monographFiles =& $submissionFileDao->getLatestRevisions($monograph->getId(), $this->getFileStage());
		}

		// $monographFiles is keyed on file and revision, for the grid we need to key on file only
		// since the grid shows only the most recent revision.
		$data = array();
		foreach ($monographFiles as $monographFile) {
			$data[$monographFile->getFileId()] = $monographFile;
		}
		return $data;
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see CategoryGridHandler::getCategoryRowInstance()
	 * @return CopyeditingFilesGridCategoryRow
	 */
	function &getCategoryRowInstance() {
		$row = new SignoffFilesGridCategoryRow($this->getStageId());
		$monograph =& $this->getMonograph();
		$row->setCellProvider(new SignoffFilesGridCellProvider($monograph->getId(), $this->getStageId()));
		$row->addFlag('gridRowStyle', true);
		return $row;
	}


	/**
	 * Get all the signoffs for this category.
	 * @see CategoryGridHandler::getCategoryData()
	 * @param $monographFile MonographFile
	 * @return array Signoffs
	 */
	function getCategoryData(&$monographFile) {
		$monographFileSignoffDao =& DAORegistry::getDAO('MonographFileSignoffDAO');
		$signoffFactory =& $monographFileSignoffDao->getAllBySymbolic($this->getSymbolic(), $monographFile->getFileId()); /* @var $signoffs DAOResultFactory */
		$signoffs = $signoffFactory->toAssociativeArray();
		return $signoffs;
	}

	/**
	 * @see CategoryGridHandler::getCategoryRowIdParameterName()
	 */
	function getCategoryRowIdParameterName() {
		return 'fileId';
	}


	/**
	 * Get the row handler - override the default row handler
	 * @return CopyeditingFilesGridRow
	 */
	function &getRowInstance() {
		$row = new SignoffGridRow($this->getStageId());
		return $row;
	}


	//
	// Public methods
	//
	/**
	 * Adds an auditor (signoff) to a file
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function addAuditor($args, &$request) {
		// Identify the monograph being worked on
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Form handling
		$router =& $request->getRouter();
		$autocompleteUrl = $router->url($request, null, null, 'getAuditorAutocomplete', null, $this->getRequestArgs());
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('autocompleteUrl', $autocompleteUrl);

		import('controllers.grid.files.signoff.form.FileAuditorForm');
		$publicationFormat =& $this->getPublicationFormat();
		$publicationFormatId = null;
		if (is_a($publicationFormat, 'PublicationFormat')) {
			$publicationFormatId = $publicationFormat->getId();
		}
		$auditorForm = new FileAuditorForm($monograph, $this->getFileStage(), $this->getStageId(), $this->getSymbolic(), $this->getEventType(), $this->getAssocId(), $publicationFormatId);
		if ($auditorForm->isLocaleResubmit()) {
			$auditorForm->readInputData();
		} else {
			$auditorForm->initData($args, $request);
		}

		$json = new JSONMessage(true, $auditorForm->fetch($request));
		return $json->getString();
	}


	/**
	 * Save the form for adding an auditor to a copyediting file
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function saveAddAuditor($args, &$request) {
		// Identify the monograph being worked on
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Form handling
		import('controllers.grid.files.signoff.form.FileAuditorForm');
		$auditorForm = new FileAuditorForm($monograph, $this->getFileStage(), $this->getStageId(), $this->getSymbolic(), $this->getEventType(), $this->getAssocId());
		$auditorForm->readInputData();
		if ($auditorForm->validate()) {
			$auditorForm->execute($request);

			// Create trivial notification.
			$currentUser =& $request->getUser();
			NotificationManager::createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.addedAuditor')));

			// We need to refresh the whole grid because multiple files can be assigned at once.
			return DAO::getDataChangedEvent();
		}

		$json = new JSONMessage(false);
		return $json->getString();
	}


	/**
	 * Get users for copyediting autocomplete.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function getAuditorAutocomplete($args, &$request) {
		// Identify the Monograph we are working with
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Retrieve the users for the autocomplete control: Any user assigned to this stage
		$stageAssignmentDao = & DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$stageUsers = $stageAssignmentDao->getBySubmissionAndStageId($monograph->getId(), $this->getStageId());

		$itemList = array();
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$userDao =& DAORegistry::getDAO('UserDAO');
		$term =& $request->getUserVar('term');
		while($stageUser =& $stageUsers->next()) {
			$userGroup =& $userGroupDao->getById($stageUser->getUserGroupId());
			$user =& $userDao->getById($stageUser->getUserId());
			$term = preg_quote($term, '/');
			if ($term == '' || preg_match('/' . $term .'/i', $user->getFullName()) || preg_match('/' . $term .'/i', $userGroup->getLocalizedName())) {
				$itemList[] = array(
					'label' =>  sprintf('%s (%s)', $user->getFullName(), $userGroup->getLocalizedName()),
					'value' => $user->getId() . '-' . $stageUser->getUserGroupId()
				);
			}
			unset($stageUser, $userGroup, $user);
		}

		if (count($itemList) == 0) {
			return $this->noAutocompleteResults();
		}

		$json = new JSONMessage(true, $itemList);
		return $json->getString();
	}


	/**
	 * Return a grid row with for the copyediting grid
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function returnSignoffRow($args, &$request) {
		$signoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);

		if($signoff) {
			return DAO::getDataChangedEvent();
		} else {
			$json = new JSONMessage(false, __('common.uploadFailed'));
			return $json->getString();
		}
	}


	/**
	 * Delete a user's signoff
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function deleteSignoff($args, &$request) {
		$signoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);

		if($signoff && !$signoff->getDateCompleted()) {

			$signoffUserId = $signoff->getUserId();
			if ($signoff->getAssocType() == ASSOC_TYPE_MONOGRAPH_FILE) {
				$fileId = $signoff->getAssocId();
			}
			$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO');
			$monographFile =& $submissionFileDao->getLatestRevision($fileId);

			// Remove the signoff
			$signoffDao =& DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */
			$signoffDao->deleteObjectById($signoff->getId());

			// Trivial notifications.
			$user =& $request->getUser();
			NotificationManager::createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedAuditor')));

			$notificationMgr = new NotificationManager();
			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_AUDITOR_REQUEST),
				array($signoff->getUserId()),
				ASSOC_TYPE_SIGNOFF,
				$signoff->getId()
			);

			// Delete for all users.
			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT),
				null,
				ASSOC_TYPE_SIGNOFF,
				$signoff->getId()
			);

			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_SIGNOFF_COPYEDIT, NOTIFICATION_TYPE_SIGNOFF_PROOF),
				array($signoff->getUserId()),
				ASSOC_TYPE_MONOGRAPH,
				$monographFile->getSubmissionId()
			);

			// log the remove auditor event.
			import('classes.log.MonographFileLog');
			import('classes.log.MonographFileEventLogEntry'); // constants
			$userDao =& DAORegistry::getDAO('UserDAO');
			$signoffUser =& $userDao->getById($signoffUserId);

			if (isset($signoffUser) && isset($monographFile)) {
				MonographFileLog::logEvent($request, $monographFile, MONOGRAPH_LOG_FILE_AUDITOR_CLEAR, 'submission.event.fileAuditorCleared', array('file' => $monographFile->getOriginalFileName(), 'name' => $signoffUser->getFullName(), 'username' => $signoffUser->getUsername()));
			}
			return DAO::getDataChangedEvent($signoff->getId(), $signoff->getAssocId());
		} else {
			$json = new JSONMessage(false, 'manager.setup.errorDeletingItem');
			return $json->getString();
		}
	}


	/**
	 * Let the user signoff on the signoff
	 * @param $args array
	 * @param $request Request
	 */
	function signOffsignOff($args, &$request) {
		$rowSignoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);
		if (!$rowSignoff) fatalError('Invalid Signoff given');

		$user =& $request->getUser();
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff =& $signoffDao->build('SIGNOFF_SIGNOFF', ASSOC_TYPE_SIGNOFF, $rowSignoff->getId(), $user->getId());
		$signoff->setDateCompleted(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);

		// Delete for all users.
		$notificationMgr = new NotificationManager();
		$notificationMgr->updateNotification(
			$request,
			array(NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT),
			null,
			ASSOC_TYPE_SIGNOFF,
			$signoff->getAssocId()
		);

		// log the sign off sign off
		import('classes.log.MonographFileLog');
		import('classes.log.MonographFileEventLogEntry'); // constants
		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO');
		$monographFile =& $submissionFileDao->getLatestRevision($rowSignoff->getAssocId());
		if (isset($monographFile)) {
			MonographFileLog::logEvent($request, $monographFile, MONOGRAPH_LOG_FILE_SIGNOFF_SIGNOFF, 'submission.event.signoffSignoff', array('file' => $monographFile->getOriginalFileName(), 'name' => $user->getFullName(), 'username' => $user->getUsername()));
		}
		// Redraw the row.
		return DAO::getDataChangedEvent($rowSignoff->getId(), $rowSignoff->getAssocId());
	}

	/**
	 * Delete the signoff on the signoff in request.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteSignOffSignOff($args, &$request) {
		$rowSignoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);
		if (!$rowSignoff) fatalError('Invalid Signoff given');

		$user =& $request->getUser();
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoffOnSignoffFactory = $signoffDao->getAllByAssocType(ASSOC_TYPE_SIGNOFF, $rowSignoff->getId());
		$signoffOnSignoff =& $signoffOnSignoffFactory->next();
		if (!$signoffOnSignoff) fatalError('Invalid Signoff given');

		$signoffDao->deleteObject($signoffOnSignoff);

		return DAO::getDataChangedEvent($rowSignoff->getId(), $rowSignoff->getAssocId());
	}


	/**
	 * Load the (read only) press file library.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function viewLibrary($args, &$request) {

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('canEdit', false);
		return $templateMgr->fetchJson('controllers/tab/settings/library.tpl');
	}

	/**
	 * Displays a modal to allow the editor to enter a message to send to the auditor as a reminder.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editReminder($args, &$request) {
		// Identify the signoff.
		$signoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Initialize form.
		import('controllers.grid.files.fileSignoff.form.AuditorReminderForm');
		$publicationFormat =& $this->getPublicationFormat();
		$publicationFormatId = null;
		if (is_a($publicationFormat, 'PublicationFormat')) {
			$publicationFormatId = $publicationFormat->getId();
		}
		$auditorReminderForm = new AuditorReminderForm($signoff, $monograph->getId(), $this->getStageId(), $publicationFormatId);
		$auditorReminderForm->initData($args, $request);

		// Render form.
		$json = new JSONMessage(true, $auditorReminderForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Send the auditor reminder and close the modal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function sendReminder($args, &$request) {
		$signoff =& $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Form handling
		import('controllers.grid.files.fileSignoff.form.AuditorReminderForm');
		$publicationFormat =& $this->getPublicationFormat();
		$publicationFormatId = null;
		if (is_a($publicationFormat, 'PublicationFormat')) {
			$publicationFormatId = $publicationFormat->getId();
		}
		$auditorReminderForm = new AuditorReminderForm($signoff, $monograph->getId(), $this->getStageId(), $publicationFormatId);
		$auditorReminderForm->readInputData();
		if ($auditorReminderForm->validate()) {
			$auditorReminderForm->execute($args, $request);
			$json = new JSONMessage(true);

			// Insert a trivial notification to indicate the auditor was reminded successfully.
			$currentUser =& $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.sentNotification')));
		} else {
			$json = new JSONMessage(false, __('editor.review.reminderError'));
		}
		return $json->getString();
	}


	//
	// Private helper methods.
	//
	/**
	 * Get all ids of users that are capable of signing off a signoff.
	 * @return array
	 */
	function _getSignoffCapableUsersId() {
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);

		// Get all the users that are assigned to the stage (managers, series editors, and assistants)
		// FIXME: is there a better way to do this?
		$userIds = array();
		$stageAssignmentDao = & DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$seriesEditorAssignments =& $stageAssignmentDao->getBySubmissionAndRoleId($monograph->getId(), ROLE_ID_SERIES_EDITOR, $this->getStageId());
		$assistantAssignments =& $stageAssignmentDao->getBySubmissionAndRoleId($monograph->getId(), ROLE_ID_PRESS_ASSISTANT, $this->getStageId());

		$allAssignments = array_merge(
			$seriesEditorAssignments->toArray(),
			$assistantAssignments->toArray()
		);

		foreach ($allAssignments as $assignment) {
			$userIds[] = $assignment->getUserId();
		}

		// We need to manually include the press editor, because he has access
		// to all submission and its workflow stages but not always with
		// an stage assignment (editorial and production stages, for example).
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$pressManagerUserGroupsFactory =& $userGroupDao->getByRoleId($monograph->getPressId(), ROLE_ID_PRESS_MANAGER);
		while ($userGroup =& $pressManagerUserGroupsFactory->next()) {
			$usersFactory =& $userGroupDao->getUsersById($userGroup->getId(), $monograph->getPressId());
			while ($user =& $usersFactory->next()) {
				$userIds[] = $user->getId();
				unset($user);
			}
			unset($userGroup);
		}

		return array_unique($userIds);
	}
}

?>
