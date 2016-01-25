<?php

/**
 * @file plugins/pubIds/urn/classes/form/URNSettingsForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNSettingsForm
 * @ingroup plugins_pubIds_urn
 *
 * @brief Form for journal managers to setup URN plugin
 */


import('lib.pkp.classes.form.Form');

class URNSettingsForm extends Form {

	//
	// Private properties
	//
	/** @var integer */
	var $_journalId;

	/** @var URNPubIdPlugin */
	var $_plugin;

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin URNPubIdPlugin
	 * @param $journalId integer
	 */
	function URNSettingsForm(&$plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'enableIssueURN', 'required', 'plugins.pubIds.urn.manager.settings.form.journalContentRequired', create_function('$enableIssueURN,$form', 'return $form->getData(\'enableIssueURN\') || $form->getData(\'enableArticleURN\') || $form->getData(\'enableGalleyURN\');'), array($this)));
		$this->addCheck(new FormValidator($this, 'urnPrefix', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPrefixRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'urnPrefix', 'optional', 'plugins.pubIds.urn.manager.settings.form.urnPrefixPattern', '/^urn:[a-zA-Z0-9-]*:.*/'));
		$this->addCheck(new FormValidatorCustom($this, 'urnIssueSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnIssueSuffixPatternRequired', create_function('$urnIssueSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableIssueURN\')) return $urnIssueSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnArticleSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnArticleSuffixPatternRequired', create_function('$urnArticleSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableArticleURN\')) return $urnArticleSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnGalleySuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnGalleySuffixPatternRequired', create_function('$urnGalleySuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableGalleyURN\')) return $urnGalleySuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidator($this, 'namespace', 'required', 'plugins.pubIds.urn.manager.settings.form.namespaceRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'urnResolver', 'required', 'plugins.pubIds.urn.manager.settings.form.urnResolverRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::display()
	 */
	function fetch($request) {
		$namespaces = array(
			'urn:nbn:de' => 'urn:nbn:de',
			'urn:nbn:at' => 'urn:nbn:at',
			'urn:nbn:ch' => 'urn:nbn:ch',
			'urn:nbn' => 'urn:nbn',
			'urn' => 'urn'
		);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('namespaces', $namespaces);

		// for DOI reset requests
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$clearPubIdsLinkAction =
		new LinkAction(
			'reassignURNs',
			new RemoteActionConfirmationModal(
				__('plugins.pubIds.urn.manager.settings.clearURNs.confirm'),
				__('common.delete'),
				$request->url(null, null, 'plugin', null, array('verb' => 'settings', 'clearPubIds' => true, 'plugin' => $this->_plugin->getName(), 'category' => 'pubIds')),
				'modal_delete'
			),
			__('plugins.pubIds.urn.manager.settings.clearURNs'),
			'delete'
		);

		$templateMgr->assign('clearPubIdsLinkAction', $clearPubIdsLinkAction);

		return parent::fetch();
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		$journalId = $this->_journalId;
		$plugin =& $this->_plugin;

		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($journalId, $fieldName));
		}

		$this->setData('pluginName', $plugin->getName());
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * @see Form::validate()
	 */
	function execute() {
		$plugin =& $this->_plugin;
		$journalId = $this->_journalId;

		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($journalId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'enableIssueURN' => 'bool',
			'enableArticleURN' => 'bool',
			'enableGalleyURN' => 'bool',
			'urnPrefix' => 'string',
			'urnSuffix' => 'string',
			'urnIssueSuffixPattern' => 'string',
			'urnArticleSuffixPattern' => 'string',
			'urnGalleySuffixPattern' => 'string',
			'checkNo' => 'bool',
			'namespace' => 'string',
			'urnResolver' => 'string'
		);
	}
}

?>
