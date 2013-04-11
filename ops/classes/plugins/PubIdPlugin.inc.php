<?php

/**
 * @file classes/plugins/PubIdPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdPlugin
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
				HookRegistry::register(strtolower_codesafe($daoName).'::getAdditionalFieldNames', array($this, 'getAdditionalFieldNames'));
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
					__('manager.plugins.disable')
				),
				array(
					'settings',
					__('manager.plugins.settings')
				)
			);
		} else {
			$verbs = array(
				array(
					'enable',
					__('manager.plugins.enable')
				)
			);
		}
		return $verbs;
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		$request = $this->getRequest();
		$templateManager = TemplateManager::getManager($request);
		$templateManager->register_function('plugin_url', array($this, 'smartyPluginUrl'));
		if (!$this->getEnabled() && $verb != 'enable') return false;
		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$message = NOTIFICATION_TYPE_PLUGIN_ENABLED;
				$messageParams = array('pluginName' => $this->getDisplayName());
				return false;

			case 'disable':
				$this->setEnabled(false);
				$message = NOTIFICATION_TYPE_PLUGIN_DISABLED;
				$messageParams = array('pluginName' => $this->getDisplayName());
				return false;

			case 'settings':
				$journal = $request->getJournal();

				$settingsFormName = $this->getSettingsFormName();
				$settingsFormNameParts = explode('.', $settingsFormName);
				$settingsFormClassName = array_pop($settingsFormNameParts);
				$this->import($settingsFormName);
				$form = new $settingsFormClassName($this, $journal->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$message = NOTIFICATION_TYPE_SUCCESS;
						return false;
					} else {
						$pluginModalContent = $form->fetch($request);
					}
				} elseif ($request->getUserVar('clearPubIds')) {
					$form->readInputData();
					$journalDao = DAORegistry::getDAO('JournalDAO');
					$journalDao->deleteAllPubIds($journal->getId(), $this->getPubIdType());
					$message = NOTIFICATION_TYPE_SUCCESS;
					return false;
				} else {
					$form->initData();
					$pluginModalContent = $form->fetch($request);
				}
				return false;
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
	 * @param $journalId int
	 * @param $pubId string
	 * @return string resolving URL
	 */
	function getResolvingURL($journalId, $pubId) {
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

	/**
	 * Define management link actions for the settings verb.
	 * @return LinkAction
	 */
	function getManagementVerbLinkAction($request, $verb) {
		$router = $request->getRouter();

		list($verbName, $verbLocalized) = $verb;

		if ($verbName === 'settings') {
			import('lib.pkp.classes.linkAction.request.AjaxLegacyPluginModal');
			$actionRequest = new AjaxLegacyPluginModal(
					$router->url($request, null, null, 'plugin', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'pubIds')),
					$this->getDisplayName()
			);
			return new LinkAction($verbName, $actionRequest, $verbLocalized, null);
		}

		return null;
	}

	//
	// Public API
	//
	/**
	 * Check for duplicate public identifiers.
	 * @param $pubId string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function checkDuplicate($pubId, &$pubObject, $journalId) {
		// FIXME: Hack to ensure that we get a published article if possible.
		// Remove this when we have migrated getBest...(), etc. to Article.
		if (is_a($pubObject, 'SectionEditorSubmission')) {
			$articleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$pubArticle =& $articleDao->getPublishedArticleByArticleId($pubObject->getId());
			if (is_a($pubArticle, 'PublishedArticle')) {
				unset($pubObject);
				$pubObject =& $pubArticle;
			}
		}

		// Check all objects of the journal whether they have
		// the same pubId. This includes pubIds that are not yet generated
		// but could be generated at any moment if someone accessed
		// the object publicly. We have to check "real" pubIds rather than
		// the pubId suffixes only as a pubId with the given suffix may exist
		// (e.g. through import) even if the suffix itself is not in the
		// database.
		$typesToCheck = array('Issue', 'PublishedArticle', 'ArticleGalley', 'SuppFile');
		foreach($typesToCheck as $pubObjectType) {
			switch($pubObjectType) {
				case 'Issue':
					$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
					$objectsToCheck = $issueDao->getIssues($journalId);
					break;

				case 'PublishedArticle':
					// FIXME: We temporarily have to use the published article
					// DAO here until we've moved pubId-generation to the Article
					// class.
					$articleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
					$objectsToCheck =& $articleDao->getPublishedArticlesByJournalId($journalId);
					break;

				case 'ArticleGalley':
					$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
					$objectsToCheck =& $galleyDao->getGalleysByJournalId($journalId);
					break;

				case 'SuppFile':
					$suppFileDao = DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
					$objectsToCheck =& $suppFileDao->getSuppFilesByJournalId($journalId);
					break;
			}

			$excludedId = (is_a($pubObject, $pubObjectType) ? $pubObject->getId() : null);
			while ($objectToCheck = $objectsToCheck->next()) {
				// The publication object for which the new pubId
				// should be admissible is to be ignored. Otherwise
				// we might get false positives by checking against
				// a pubId that we're about to change anyway.
				if ($objectToCheck->getId() == $excludedId) continue;

				// Check for ID clashes.
				$existingPubId = $this->getPubId($objectToCheck, true);
				if ($pubId == $existingPubId) return false;
			}

			unset($objectsToCheck);
		}

		// We did not find any ID collision, so go ahead.
		return true;
	}

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
			$request = $this->getRequest();
			$router = $request->getRouter();
			$journal = $router->getContext($request);

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
		$request = $this->getRequest();
		$journal = $request->getJournal();
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
}

?>
