<?php

/**
 * @file controllers/modals/submissionMetadata/CatalogEntryHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CatalogEntryHandler
 * @ingroup controllers_modals_submissionMetadata
 *
 * @brief Handle the request to generate the tab structure on the New Catalog Entry page.
 */

// Import the base Handler.
import('classes.handler.Handler');

class CatalogEntryHandler extends Handler {

	/** The monograph **/
	var $_monograph;

	/** The current stage id **/
	var $_stageId;

	/** the current tab position **/
	var $_tabPosition;

	/** the selected format id **/
	var $_selectedFormatId;

	/**
	 * Constructor.
	 */
	function CatalogEntryHandler() {
		parent::Handler();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array('fetch', 'fetchFormatInfo'));
	}


	//
	// Overridden methods from Handler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		$monographDao = DAORegistry::getDAO('MonographDAO');
		$this->_monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		$this->_stageId =& $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$this->_tabPosition = (int) $request->getUserVar('tabPos');
		$this->_selectedFormatId = (int) $request->getUserVar('selectedFormatId');

		// Load grid-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_APP_SUBMISSION);
		$this->setupTemplate($request);
	}

	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int) $request->getUserVar('stageId');
		import('classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the Monograph
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->_monograph;
	}

	/**
	 * Get the stage id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the current tab position
	 * @return int
	 */
	function getTabPosition() {
		return $this->_tabPosition;
	}

	/**
	 * Get the selected format id.
	 * @return int
	 */
	function getSelectedFormatId() {
		return $this->_selectedFormatId;
	}


	//
	// Public handler methods
	//
	/**
	 * Display the tabs index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, $request) {
		$templateMgr =& TemplateManager::getManager($request);

		$monograph =& $this->getMonograph();

		$templateMgr->assign('submissionId', $monograph->getId());
		$templateMgr->assign('stageId', $this->getStageId());

		// check to see if this monograph has been published yet
		$publishedMonographDao = DAORegistry::getDAO('PublishedMonographDAO');
		$publishedMonograph =& $publishedMonographDao->getById($monograph->getId());
		$tabPosition = (int) $this->getTabPosition();
		$templateMgr->assign('selectedTab', $tabPosition);
		$templateMgr->assign('selectedFormatId', $this->getSelectedFormatId());

		// load in any publication formats assigned to this published monograph
		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$formats =& $publicationFormatDao->getByMonographId($monograph->getId());
		$publicationFormats = array();
		while ($publicationFormat =& $formats->next()) {
			$publicationFormats[] =& $publicationFormat;
		}

		$templateMgr->assign_by_ref('publicationFormats', $publicationFormats);

		$application =& Application::getApplication();
		$request =& $application->getRequest();
		$router =& $request->getRouter();
		$dispatcher =& $router->getDispatcher();

		$tabsUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'modals.submissionMetadata.CatalogEntryHandler', 'fetchFormatInfo', null, array('submissionId' => $monograph->getId(), 'stageId' => $this->getStageId()));
		$templateMgr->assign('tabsUrl', $tabsUrl);

		$tabContentUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'tab.catalogEntry.CatalogEntryTabHandler', 'publicationMetadata', null, array('submissionId' => $monograph->getId(), 'stageId' => $this->getStageId()));
		$templateMgr->assign('tabContentUrl', $tabContentUrl);
		if ($request->getUserVar('hideHelp')) {
			$templateMgr->assign('hideHelp', true);
		}

		$this->setupTemplate($request);
		return $templateMgr->fetchJson('controllers/modals/submissionMetadata/catalogEntryTabs.tpl');
	}

	/**
	 * Returns a JSON response containing information regarding the formats enabled
	 * for this monograph.
	 * @param $args array
	 * @param $request Request
	 */
	function fetchFormatInfo($args, $request) {
		$monograph =& $this->getMonograph();
		// check to see if this monograph has been published yet
		$publishedMonographDao = DAORegistry::getDAO('PublishedMonographDAO');
		$json = new JSONMessage();

		$publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
		$formats =& $publicationFormatDao->getByMonographId($monograph->getId());
		$publicationFormats = array();
		while ($format =& $formats->next()) {
			$publicationFormats[$format->getId()] = $format->getLocalizedName();
		}
		$json->setStatus(true);
		$json->setContent(true);
		$json->setAdditionalAttributes(array('formats' => $publicationFormats));
		return $json->getString();
	}
}

?>
