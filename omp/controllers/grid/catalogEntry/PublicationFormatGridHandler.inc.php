<?php

/**
 * @file controllers/grid/catalogEntry/PublicationFormatGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatGridHandler
 * @ingroup controllers_grid_catalogEntry
 *
 * @brief Handle publication format grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');


// import format grid specific classes
import('controllers.grid.catalogEntry.PublicationFormatGridCellProvider');
import('controllers.grid.catalogEntry.PublicationFormatGridRow');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class PublicationFormatGridHandler extends GridHandler {
	/** @var Monograph */
	var $_monograph;

	/** @var boolean */
	var $_inCatalogEntryModal;

	/**
	 * Constructor
	 */
	function PublicationFormatGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array(
				'fetchGrid', 'fetchRow', 'addFormat',
				'editFormat', 'updateFormat', 'deleteFormat',
				'setAvailable'
			)
		);
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the monograph associated with this publication format grid.
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->_monograph;
	}

	/**
	 * Set the Monograph
	 * @param Monograph
	 */
	function setMonograph($monograph) {
		$this->_monograph =& $monograph;
	}

	/**
	 * Get flag indicating if this grid is loaded
	 * inside a catalog entry modal or not.
	 * @return boolean
	 */
	function getInCatalogEntryModal() {
		return $this->_inCatalogEntryModal;
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		import('classes.security.authorization.OmpSubmissionAccessPolicy');
		$this->addPolicy(new OmpSubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/*
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);

		$this->setTitle('monograph.publicationFormats');
		$this->setInstructions('editor.monograph.production.publicationFormatDescription');
		$this->_inCatalogEntryModal = (boolean) $request->getUserVar('inCatalogEntryModal');

		// Retrieve the authorized monograph.
		$this->setMonograph($this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH));

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_APP_DEFAULT,
			LOCALE_COMPONENT_PKP_DEFAULT,
			LOCALE_COMPONENT_APP_EDITOR
		);

		// Grid actions
		$router =& $request->getRouter();
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
		$monograph =& $this->getMonograph();
		$cellProvider = new PublicationFormatGridCellProvider($monograph->getId(), $this->getInCatalogEntryModal());
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				'controllers/grid/gridCell.tpl',
				$cellProvider,
				array('width' => 50, 'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'proofComplete',
				'grid.catalogEntry.proof',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'isApproved',
				'payment.directSales.catalog',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'isAvailable',
				'grid.catalogEntry.isAvailable',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$cellProvider
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return PublicationFormatGridRow
	 */
	function &getRowInstance() {
		$monograph =& $this->getMonograph();
		$row = new PublicationFormatGridRow($monograph);
		return $row;
	}

	/**
	 * Get the arguments that will identify the data in the grid
	 * In this case, the monograph.
	 * @return array
	 */
	function getRequestArgs() {
		$monograph =& $this->getMonograph();

		return array(
			'submissionId' => $monograph->getId(),
			'inCatalogEntryModal' => $this->getInCatalogEntryModal()
		);
	}

	/**
	 * @see GridHandler::loadData
	 */
	function &loadData($request, $filter = null) {
		$monograph =& $this->getMonograph();
		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$data =& $publicationFormatDao->getByMonographId($monograph->getId());
		return $data->toAssociativeArray();
	}


	//
	// Public Publication Format Grid Actions
	//

	function addFormat($args, $request) {
		return $this->editFormat($args, $request);
	}

	/**
	 * Edit a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editFormat($args, &$request) {
		// Identify the format to be updated
		$publicationFormatId = (int) $request->getUserVar('publicationFormatId');
		$monograph =& $this->getMonograph();

		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat = $publicationFormatDao->getById($publicationFormatId);

		// Form handling
		import('controllers.grid.catalogEntry.form.PublicationFormatForm');
		$publicationFormatForm = new PublicationFormatForm($monograph, $publicationFormat);
		$publicationFormatForm->initData();

		$json = new JSONMessage(true, $publicationFormatForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateFormat($args, &$request) {
		// Identify the format to be updated
		$publicationFormatId = (int) $request->getUserVar('publicationFormatId');
		$monograph =& $this->getMonograph();

		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat = $publicationFormatDao->getById($publicationFormatId);

		// Form handling
		import('controllers.grid.catalogEntry.form.PublicationFormatForm');
		$publicationFormatForm = new PublicationFormatForm($monograph, $publicationFormat);
		$publicationFormatForm->readInputData();
		if ($publicationFormatForm->validate()) {
			$publicationFormatId = $publicationFormatForm->execute($request);

			if(!isset($publicationFormat)) {
				// This is a new format
				$publicationFormat =& $publicationFormatDao->getById($publicationFormatId);
				// New added format action notification content.
				$notificationContent = __('notification.addedPublicationFormat');
			} else {
				// Format edit action notification content.
				$notificationContent = __('notification.editedPublicationFormat');
			}

			// Create trivial notification.
			$currentUser =& $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => $notificationContent));

			// Prepare the grid row data
			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$row->setId($publicationFormatId);
			$row->setData($publicationFormat);
			$row->initialize($request);

			// Render the row into a JSON response
			return DAO::getDataChangedEvent();

		} else {
			$json = new JSONMessage(true, $publicationFormatForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteFormat($args, &$request) {
		$press =& $request->getPress();
		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat =& $publicationFormatDao->getById(
			$request->getUserVar('publicationFormatId'),
			null, // $pressId
			$press->getId() // Make sure to validate the press context
		);
		$result = false;
		if ($publicationFormat) {
			$result = $publicationFormatDao->deleteById($publicationFormat->getId());
		}

		if ($result) {
			// Create a tombstone for this publication format.
			import('classes.publicationFormat.PublicationFormatTombstoneManager');
			$publicationFormatTombstoneMgr = new PublicationFormatTombstoneManager();
			$press =& $request->getPress();
			$publicationFormatTombstoneMgr->insertTombstoneByPublicationFormat($publicationFormat, $press);

			$currentUser =& $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedPublicationFormat')));

			// log the deletion of the format.
			import('classes.log.MonographLog');
			import('classes.log.MonographEventLogEntry');
			MonographLog::logEvent($request, $this->getMonograph(), MONOGRAPH_LOG_PUBLICATION_FORMAT_REMOVE, 'submission.event.publicationFormatRemoved', array('formatName' => $publicationFormat->getLocalizedName()));

			return DAO::getDataChangedEvent();
		} else {
			$json = new JSONMessage(false, __('manager.setup.errorDeletingItem'));
			return $json->getString();
		}

	}

	/**
	 * Set a format's "available" state
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function setAvailable($args, &$request) {
		$press =& $request->getPress();
		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat =& $publicationFormatDao->getById(
			$request->getUserVar('publicationFormatId'),
			null, // $monographId
			$press->getId() // Make sure to validate the context.
		);

		if ($publicationFormat) {
			$newAvailableState = (int) $request->getUserVar('newAvailableState');
			$publicationFormat->setIsAvailable($newAvailableState);
			$publicationFormatDao->updateObject($publicationFormat);

			// log the state changing of the format.
			import('classes.log.MonographLog');
			import('classes.log.MonographEventLogEntry');
			MonographLog::logEvent(
				$request, $this->getMonograph(),
				$newAvailableState?MONOGRAPH_LOG_PUBLICATION_FORMAT_AVAILABLE:MONOGRAPH_LOG_PUBLICATION_FORMAT_UNAVAILABLE,
				$newAvailableState?'submission.event.publicationFormatMadeAvailable':'submission.event.publicationFormatMadeUnavailable',
				array('publicationFormatName' => $publicationFormat->getLocalizedName())
			);

			// Update the formats tombstones.
			import('classes.publicationFormat.PublicationFormatTombstoneManager');
			$publicationFormatTombstoneMgr = new PublicationFormatTombstoneManager();

			if ($newAvailableState) {
				// Delete any existing tombstone.
				$publicationFormatTombstoneMgr->deleteTombstonesByPublicationFormats(array($publicationFormat));
			} else {
				// Create a tombstone for this publication format.
				$publicationFormatTombstoneMgr->insertTombstoneByPublicationFormat($publicationFormat, $press);
			}

			return DAO::getDataChangedEvent($publicationFormat->getId());
		} else {
			$json = new JSONMessage(false, __('manager.setup.errorDeletingItem'));
			return $json->getString();
		}

	}
}

?>
