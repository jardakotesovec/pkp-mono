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
	var $_contextId;

	/**
	 * Get the context ID.
	 * @return integer
	 */
	function _getContextId() {
		return $this->_contextId;
	}

	/** @var URNPubIdPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return URNPubIdPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin URNPubIdPlugin
	 * @param $contextId integer
	 */
	function URNSettingsForm($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'urnObjects', 'required', 'plugins.pubIds.urn.manager.settings.urnObjectsRequired', create_function('$enableIssueURN,$form', 'return $form->getData(\'enableIssueURN\') || $form->getData(\'enableSubmissionURN\') || $form->getData(\'enableRepresentationURN\');'), array($this)));
		$this->addCheck(new FormValidatorRegExp($this, 'urnPrefix', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPrefixPattern', '/^urn:[a-zA-Z0-9-]*:.*/'));
		$this->addCheck(new FormValidatorCustom($this, 'urnSubmissionSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnSubmissionSuffixPatternRequired', create_function('$urnSubmissionSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableSubmissionURN\')) return $urnSubmissionSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnRepresentationSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnRepresentationSuffixPatternRequired', create_function('$urnRepresentationSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableRepresentationURN\')) return $urnRepresentationSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnSubmissionFileSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnSubmissionFileSuffixPattern', create_function('$urnSubmissionFileSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableSubmissionFileURN\')) return $urnSubmissionFileSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorUrl($this, 'urnResolver', 'required', 'plugins.pubIds.urn.manager.settings.form.urnResolverRequired'));
		$this->addCheck(new FormValidatorPost($this));

		// for URN reset requests
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$this->setData('clearPubIdsLinkAction', new LinkAction(
			'reassignURNs',
			new RemoteActionConfirmationModal(
				__('plugins.pubIds.urn.manager.settings.urnReassign.confirm'),
				__('common.delete'),
				$request->url(null, null, 'manage', null, array('verb' => 'clearPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds')),
				'modal_delete'
			),
			__('plugins.pubIds.urn.manager.settings.urnReassign'),
			'delete'
		));
		$this->setData('pluginName', $plugin->getName());
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$urnNamespaces = array(
			'' => '',
			'urn:nbn:de' => 'urn:nbn:de',
			'urn:nbn:at' => 'urn:nbn:at',
			'urn:nbn:ch' => 'urn:nbn:ch',
			'urn:nbn' => 'urn:nbn',
			'urn' => 'urn'
		);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('urnNamespaces', $urnNamespaces);
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$contextId = $this->_getContextId();
		$plugin = $this->_getPlugin();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
		}
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * @copydoc Form::validate()
	 */
	function execute() {
		$contextId = $this->_getContextId();
		$plugin = $this->_getPlugin();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'enableSubmissionURN' => 'bool',
			'enableRepresentationURN' => 'bool',
			'enableSubmissionFileURN' => 'bool',
			'urnPrefix' => 'string',
			'urnSuffix' => 'string',
			'urnSubmissionSuffixPattern' => 'string',
			'urnRepresentationSuffixPattern' => 'string',
			'urnSubmissionFileSuffixPattern' => 'string',
			'urnCheckNo' => 'bool',
			'urnNamespace' => 'string',
			'urnResolver' => 'string',
		);
	}
}

?>
