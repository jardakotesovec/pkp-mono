<?php

/**
 * @file controllers/grid/catalogEntry/PublicationFormatGridHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatGridHandler
 * @ingroup controllers_grid_catalogEntry
 *
 * @brief Handle publication format grid requests.
 */

import('lib.pkp.controllers.grid.representations.RepresentationsGridHandler');

class PublicationFormatGridHandler extends RepresentationsGridHandler {
	/** @var PublicationFormatGridCellProvider */
	var $_cellProvider;

	/**
	 * Constructor
	 */
	function PublicationFormatGridHandler() {
		parent::RepresentationsGridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array(
				'setAvailable', 'editApprovedProof', 'saveApprovedProof',
			)
		);
	}


	/**
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);
		$this->setTitle('monograph.publicationFormats');

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_APP_DEFAULT,
			LOCALE_COMPONENT_APP_EDITOR
		);

		// Grid actions
		$router = $request->getRouter();
		$actionArgs = $this->getRequestArgs();
		$this->addAction(
			new LinkAction(
				'addFormat',
				new AjaxModal(
					$router->url($request, null, null, 'addFormat', null, $actionArgs),
					__('grid.action.addFormat'),
					'modal_add_item'
				),
				__('grid.action.addFormat'),
				'add_item'
			)
		);

		// Columns
		$submission = $this->getSubmission();
		import('controllers.grid.catalogEntry.PublicationFormatGridCellProvider');
		$this->_cellProvider = new PublicationFormatGridCellProvider($submission->getId());
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				null,
				$this->_cellProvider,
				array('width' => 60, 'anyhtml' => true)
			)
		);
		$this->addColumn(
			new GridColumn(
				'isComplete',
				'common.complete',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$this->_cellProvider,
				array('width' => 20)
			)
		);
		$this->addColumn(
			new GridColumn(
				'isAvailable',
				'grid.catalogEntry.availability',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$this->_cellProvider,
				array('width' => 20)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return RepresentationsGridCategoryRow
	 */
	function getCategoryRowInstance() {
		return new RepresentationsGridCategoryRow($this->getSubmission(), $this->_cellProvider);
	}


	//
	// Public Publication Format Grid Actions
	//
	/**
	 * Edit a publication format modal
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editFormat($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representationId = $request->getUserVar('representationId');
		$representation = $representationDao->getById(
			$representationId,
			$submission->getId()
		);
		// Check if this is a remote galley
		$remoteURL = isset($representation) ? $representation->getRemoteURL() : null;
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submissionId', $submission->getId());
		$templateMgr->assign('representationId', $representationId);
		$templateMgr->assign('remoteRepresentation', $remoteURL);
		return new JSONMessage(true, $templateMgr->fetch('controllers/grid/catalogEntry/editFormat.tpl'));
	}

	/**
	 * Edit a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editFormatTab($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		import('controllers.grid.catalogEntry.form.PublicationFormatForm');
		$publicationFormatForm = new PublicationFormatForm($submission, $representation);
		$publicationFormatForm->initData();

		return new JSONMessage(true, $publicationFormatForm->fetch($request));
	}

	/**
	 * Update a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateFormat($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		import('controllers.grid.catalogEntry.form.PublicationFormatForm');
		$publicationFormatForm = new PublicationFormatForm($submission, $representation);
		$publicationFormatForm->readInputData();
		if ($publicationFormatForm->validate()) {
			$publicationFormatForm->execute($request);
			return DAO::getDataChangedEvent();
		}
		return new JSONMessage(true, $publicationFormatForm->fetch($request));
	}

	/**
	 * Delete a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteFormat($args, $request) {
		$context = $request->getContext();
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		if (!$representation || !$representationDao->deleteById($representation->getId())) {
			return new JSONMessage(false, __('manager.setup.errorDeletingItem'));
		}

		// Create a tombstone for this publication format.
		import('classes.publicationFormat.PublicationFormatTombstoneManager');
		$publicationFormatTombstoneMgr = new PublicationFormatTombstoneManager();
		$publicationFormatTombstoneMgr->insertTombstoneByPublicationFormat($representation, $context);

		$currentUser = $request->getUser();
		$notificationMgr = new NotificationManager();
		$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedPublicationFormat')));

		// Log the deletion of the format.
		import('lib.pkp.classes.log.SubmissionLog');
		import('classes.log.SubmissionEventLogEntry');
		SubmissionLog::logEvent($request, $this->getSubmission(), SUBMISSION_LOG_PUBLICATION_FORMAT_REMOVE, 'submission.event.publicationFormatRemoved', array('formatName' => $representation->getLocalizedName()));

		return DAO::getDataChangedEvent();
	}

	/**
	 * Set a format's "approved" state
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function setApproved($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		if (!$representation) return new JSONMessage(false, __('manager.setup.errorDeletingItem'));

		$confirmationText = __('grid.catalogEntry.approvedRepresentation.removeMessage');
		if ($request->getUserVar('newApprovedState')) {
			$confirmationText = __('grid.catalogEntry.approvedRepresentation.message');
		}
		import('lib.pkp.controllers.grid.pubIds.form.PKPAssignPublicIdentifiersForm');
		$formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
		$assignPublicIdentifiersForm = new PKPAssignPublicIdentifiersForm($formTemplate, $representation, $request->getUserVar('newApprovedState'), $confirmationText);
		if (!$request->getUserVar('confirmed')) {
			// Display assign pub ids modal
			$assignPublicIdentifiersForm->initData($args, $request);
			return new JSONMessage(true, $assignPublicIdentifiersForm->fetch($request));
		}
		if ($request->getUserVar('newApprovedState')) {
			// Asign pub ids
			$assignPublicIdentifiersForm->readInputData();
			$assignPublicIdentifiersForm->execute($request);
		}

		$newApprovedState = (int) $request->getUserVar('newApprovedState');
		$representation->setIsApproved($newApprovedState);
		$representationDao->updateObject($representation);

		// log the state changing of the format.
		import('lib.pkp.classes.log.SubmissionLog');
		import('classes.log.SubmissionEventLogEntry');
		SubmissionLog::logEvent(
			$request, $this->getSubmission(),
			$newApprovedState?SUBMISSION_LOG_PUBLICATION_FORMAT_PUBLISH:SUBMISSION_LOG_PUBLICATION_FORMAT_UNPUBLISH,
			$newApprovedState?'submission.event.publicationFormatPublished':'submission.event.publicationFormatUnpublished',
			array('publicationFormatName' => $representation->getLocalizedName())
		);

		// Update the formats tombstones.
		import('classes.publicationFormat.PublicationFormatTombstoneManager');
		$publicationFormatTombstoneMgr = new PublicationFormatTombstoneManager();
		if ($representation->getIsAvailable() && $representation->getIsApproved()) {
			// Delete any existing tombstone.
			$publicationFormatTombstoneMgr->deleteTombstonesByPublicationFormats(array($representation));
		} else {
			// (Re)create a tombstone for this publication format.
			$publicationFormatTombstoneMgr->deleteTombstonesByPublicationFormats(array($representation));
			$publicationFormatTombstoneMgr->insertTombstoneByPublicationFormat($representation, $request->getContext());
		}

		return DAO::getDataChangedEvent($representation->getId());
	}

	/**
	 * Set a format's "available" state
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function setAvailable($args, $request) {
		$context = $request->getContext();
		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat = $publicationFormatDao->getById(
			$request->getUserVar('representationId'),
			null, // $submissionId
			$context->getId() // Make sure to validate the context.
		);

		if (!$publicationFormat) return new JSONMessage(false, __('manager.setup.errorDeletingItem'));

		$newAvailableState = (int) $request->getUserVar('newAvailableState');
		$publicationFormat->setIsAvailable($newAvailableState);
		$publicationFormatDao->updateObject($publicationFormat);

		// log the state changing of the format.
		import('lib.pkp.classes.log.SubmissionLog');
		import('classes.log.SubmissionEventLogEntry');
		SubmissionLog::logEvent(
			$request, $this->getSubmission(),
			$newAvailableState?SUBMISSION_LOG_PUBLICATION_FORMAT_AVAILABLE:SUBMISSION_LOG_PUBLICATION_FORMAT_UNAVAILABLE,
			$newAvailableState?'submission.event.publicationFormatMadeAvailable':'submission.event.publicationFormatMadeUnavailable',
			array('publicationFormatName' => $publicationFormat->getLocalizedName())
		);

		// Update the formats tombstones.
		import('classes.publicationFormat.PublicationFormatTombstoneManager');
		$publicationFormatTombstoneMgr = new PublicationFormatTombstoneManager();
		if ($publicationFormat->getIsAvailable() && $publicationFormat->getIsApproved()) {
			// Delete any existing tombstone.
			$publicationFormatTombstoneMgr->deleteTombstonesByPublicationFormats(array($publicationFormat));
		} else {
			// (Re)create a tombstone for this publication format.
			$publicationFormatTombstoneMgr->deleteTombstonesByPublicationFormats(array($publicationFormat));
			$publicationFormatTombstoneMgr->insertTombstoneByPublicationFormat($publicationFormat, $context);
		}

		return DAO::getDataChangedEvent($publicationFormat->getId());
	}

	/**
	 * Edit an approved proof.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editApprovedProof($args, $request) {
		$this->initialize($request);
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		import('controllers.grid.files.proof.form.ApprovedProofForm');
		$approvedProofForm = new ApprovedProofForm($submission, $representation, $request->getUserVar('fileId'));
		$approvedProofForm->initData();

		return new JSONMessage(true, $approvedProofForm->fetch($request));
	}

	/**
	 * Save an approved proof.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveApprovedProof($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		import('controllers.grid.files.proof.form.ApprovedProofForm');
		$approvedProofForm = new ApprovedProofForm($submission, $representation, $request->getUserVar('fileId'));
		$approvedProofForm->readInputData();

		if ($approvedProofForm->validate()) {
			$approvedProofForm->execute($request);
			return DAO::getDataChangedEvent();
		}
		return new JSONMessage(true, $approvedProofForm->fetch($request));
	}

	/**
	 * @copydoc RepresentationsGridHandler::getAssignPublicIdentifiersFormTemplate()
	 */
	function getAssignPublicIdentifiersFormTemplate() {
		return 'controllers/grid/pubIds/form/assignPublicIdentifiersForm.tpl';
	}
}

?>
