<?php

/**
 * @file classes/plugins/PidPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PidPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for public identifiers plugins
 */


import('classes.plugins.Plugin');

class PubIdPlugin extends Plugin {

	//
	// Constructor
	//
	function PubIdPlugin() {
		parent::Plugin();
	}


	//
	// Implement template methods from PKPPlugin
	//
	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			// Enable storage of additional fields.
			foreach($this->_getDAOs() as $daoName) {
				HookRegistry::register(strtolower($daoName).'::getAdditionalFieldNames', array($this, 'getAdditionalFieldNames'));
			}
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		if ($this->getEnabled()) {
			$verbs = array(
				array(
					'disable',
					Locale::translate('manager.plugins.disable')
				),
				array(
					'settings',
					Locale::translate('manager.plugins.settings')
				)
			);
		} else {
			$verbs = array(
				array(
					'enable',
					Locale::translate('manager.plugins.enable')
				)
			);
		}
		return $verbs;
	}

	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args) {
		$templateManager =& TemplateManager::getManager();
		$templateManager->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		if (!$this->getEnabled() && $verb != 'enable') return false;
		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				return false;

			case 'disable':
				$this->setEnabled(false);
				return false;

			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$journal =& Request::getJournal();

				$settingsFormName = $this->getSettingsFormName();
				$settingsFormNameParts = explode('.', $settingsFormName);
				$settingsFormClassName = array_pop($settingsFormNameParts);
				$this->import($settingsFormName);
				$form = new $settingsFormClassName($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$this->_setBreadcrumbs();
						$form->display();
					}
				} elseif (Request::getUserVar('clearPubIds')) {
					$form->readInputData();
					$journalDao =& DAORegistry::getDAO('JournalDAO');
					$journalDao->deleteAllPubIds($journal->getId(), $this->getPubIdType());
					$this->_setBreadcrumbs();
					$form->display();
				} else {
					$this->_setBreadcrumbs();
					$form->initData();
					$form->display();
				}
				return true;

			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}


	//
	// Protected template methods to be implemented by sub-classes.
	//
	/**
	 * Get the public identifier.
	 * @param $pubObject object
	 *  (Issue, Article, PublishedArticle, ArticleGalley, SuppFile)
	 * @param $preview boolean
	 *  when true, the public identifier will not be stored
	 * @return string
	 */
	function getPubId($pubObject, $preview = false) {
		assert(false); // Should always be overridden
	}

	/**
	 * Public identifier type, see
	 * http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html
	 * @return string
	 */
	function getPubIdType() {
		assert(false); // Should always be overridden
	}

	/**
	 * Public identifier type that will be displayed to the reader.
	 * @return string
	 */
	function getPubIdDisplayType() {
		assert(false); // Should always be overridden
	}

	/**
	 * Full name of the public identifier.
	 * @return string
	 */
	function getPubIdFullName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the whole resolving URL.
	 * @param $pubId string
	 * @return string resolving URL
	 */
	function getResolvingURL($pubId) {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the file (path + filename)
	 * to be included into the object's
	 * metadata pages.
	 * @return string
	 */
	function getPubIdMetadataFile() {
		assert(false); // Should be overridden
	}

	/**
	 * Get the class name of the settings form.
	 * @return string
	 */
	function getSettingsFormName() {
		assert(false); // Should be overridden
	}

	/**
	 * Verify form data.
	 * @param $fieldName string The form field to be checked.
	 * @param $fieldValue string The value of the form field.
	 * @param $pubObject object
	 * @param $journalId integer
	 * @param $errorMsg string Return validation error messages here.
	 * @return boolean
	 */
	function verifyData($fieldName, $fieldValue, &$pubObject, $journalId, &$errorMsg) {
		assert(false); // Should be overridden
	}

	/**
	 * Check for duplicate URN.
	 * @param $pubId string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function checkDuplicate($pubId, &$pubObject, $journalId) {
		assert(false); // Should be overridden
	}

	/**
	 * Check whether the given pubId is valid.
	 * @param $pubId string
	 * @return boolean
	 */
	function validatePubId($pubId) {
		return true; // Assume a valid ID by default;
	}

	/**
	 * Get the additional form field names.
	 * @return array
	 */
	function getFormFieldNames() {
		assert(false); // Should be overridden
	}

	/**
	 * Get additional field names to be considered for storage.
	 * @return array
	 */
	function getDAOFieldNames() {
		assert(false); // Should be overridden
	}


	//
	// Public API
	//
	/**
	 * Add the suffix element and the public identifier
	 * to the object (issue, article, galley, supplementary file).
	 * @param $hookName string
	 * @param $params array ()
	 */
	function getAdditionalFieldNames($hookName, $params) {
		$fields =& $params[1];
		$formFieldNames = $this->getFormFieldNames();
		foreach ($formFieldNames as $formFieldName) {
			$fields[] = $formFieldName;
		}
		$daoFieldNames = $this->getDAOFieldNames();
		foreach ($daoFieldNames as $daoFieldName) {
			$fields[] = $daoFieldName;
		}
		return false;
	}

	/**
	 * Return the object type.
	 * @param $pubObject object
	 *  (Issue, Article, PublishedArticle, ArticleGalley, SuppFile)
	 * @return array
	 */
	function getPubObjectType($pubObject) {
		$allowedTypes = array(
			'Issue' => 'Issue',
			'Article' => 'Article',
			'ArticleGalley' => 'Galley',
			'SuppFile' => 'SuppFile'
		);
		$pubObjectType = null;
		foreach ($allowedTypes as $allowedType => $pubObjectTypeCandidate) {
			if (is_a($pubObject, $allowedType)) {
				$pubObjectType = $pubObjectTypeCandidate;
				break;
			}
		}
		if (is_null($pubObjectType)) {
			// This must be a dev error, so bail with an assertion.
			assert(false);
			return null;
		}
		return $pubObjectType;
	}

	/**
	 * Set and store a public identifier.
	 * @param $pubObject Issue|Article|ArticleGalley|SuppFile
	 * @param $pubObjectType string As returned from self::getPubObjectType()
	 * @param $pubId string
	 * @return string
	 */
	function setStoredPubId(&$pubObject, $pubObjectType, $pubId) {
		$dao =& $this->getDAO($pubObjectType);
		$dao->changePubId($pubObject->getId(), $this->getPubIdType(), $pubId);
		$pubObject->setStoredPubId($this->getPubIdType(), $pubId);
	}

	/**
	 * Return the name of the corresponding DAO.
	 * @param $pubObject object
	 * @return DAO
	 */
	function &getDAO($pubObjectType) {
		$daos =  array(
			'Issue' => 'IssueDAO',
			'Article' => 'ArticleDAO',
			'Galley' => 'ArticleGalleyDAO',
			'SuppFile' => 'SuppFileDAO'
		);
		$daoName = $daos[$pubObjectType];
		assert(!empty($daoName));
		return DAORegistry::getDAO($daoName);
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 * @return boolean
	 */
	function getEnabled($journalId = null) {
		if (!$journalId) {
			$request =& Application::getRequest();
			$router =& $request->getRouter();
			$journal =& $router->getContext($request);

			if (!$journal) return false;
			$journalId = $journal->getid();
		}
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin.
	 * @param $enabled boolean
	 */
	function setEnabled($enabled) {
		$journal =& Request::getJournal();
		if ($journal) {
			$this->updateSetting(
				$journal->getId(),
				'enabled',
				$enabled?true:false
			);
			return true;
		}
		return false;
	}


	//
	// Private helper methods
	//
	/**
	 * Return an array of the corresponding DAOs.
	 * @return array
	 */
	function _getDAOs() {
		return array('IssueDAO', 'ArticleDAO', 'ArticleGalleyDAO', 'SuppFileDAO');
	}

	/**
	 * Set the breadcrumbs, given the plugin's tree of items to append.
	 */
	function _setBreadcrumbs() {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			),
			array(
				Request::url(null, 'manager', 'plugins'),
				'manager.plugins'
			)
		);
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}
}

?>
