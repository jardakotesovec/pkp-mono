<?php

/**
 * @file controllers/grid/announcements/form/AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeForm
 * @ingroup controllers_grid_announcements_form
 * @see AnnouncementType
 *
 * @brief Form for manager to create/edit announcement types.
 */


import('lib.pkp.classes.form.Form');

class NavigationMenuForm extends Form {
	/** @var int Context ID */
	var $contextId;

	/** @var navigationMenuId int the ID of the announcement type being edited */
	var $navigationMenuId;

	/**
	 * Constructor
	 * @param $contextId int Context ID
	 * @param $navigationMenuId int leave as default for new announcement type
	 */
	function __construct($contextId, $navigationMenuId = null) {
		$this->navigationMenuId = isset($navigationMenuId) ? (int) $navigationMenuId : null;
		$this->contextId = $contextId;

		parent::__construct('manager/navigationMenus/navigationMenuForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidator($this, 'title', 'required', 'manager.announcementTypes.form.typeNameRequired'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		//$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		//return $announcementTypeDao->getLocaleFieldNames();
		return null;
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);

		$templateMgr->assign('navigationMenuId', $this->navigationMenuId);
		$templateMgr->assign('title', $this->getData('title'));

		return parent::fetch($request, 'controllers/grid/navigationMenus/form/navigationMenuForm.tpl');
	}

	/**
	 * Initialize form data from current announcement type.
	 */
	function initData() {
		$navigationMenusDao = DAORegistry::getDAO('NavigationMenuDAO');

		if (isset($this->navigationMenuId) && $this->navigationMenuId != 0) {
		    $navigationMenu = $navigationMenusDao->getById($this->navigationMenuId);

		    if ($navigationMenu != null) {
		        $this->_data = array(
		            'title' => $navigationMenu->getTitle(),
					'navigationMenuId' => $navigationMenu->getId()
		        );
		    } else {
		        $this->navigationMenuId = null;
		    }
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'navigationMenuId'));

	}

	/**
	 * Save announcement type.
	 */
	function execute() {
		$navigationMenusDao = DAORegistry::getDAO('NavigationMenuDAO');

		if (isset($this->navigationMenuId)) {
			$announcementType = $navigationMenusDao->getById($this->navigationMenuId);
		}

		if (!isset($announcementType)) {
			$announcementType = $navigationMenusDao->newDataObject();
		}

		//$announcementType->setAssocType(Application::getContextAssocType());
		$announcementType->setContextId($this->contextId);
		$announcementType->setTitle($this->getData('title'), null); // Localized

		// Update or insert announcement type
		if ($announcementType->getId() != null) {
			$navigationMenusDao->updateObject($announcementType);
		} else {
			$navigationMenusDao->insertObject($announcementType);
		}
	}
}

?>
