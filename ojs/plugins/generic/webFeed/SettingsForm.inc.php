<?php

/**
 * @file plugins/generic/webFeed/SettingsForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_webFeed
 *
 * @brief Form for journal managers to modify web feeds plugin settings
 */

import('lib.pkp.classes.form.Form');

class SettingsForm extends Form {

	/** @var int Associated journal ID */
	private $_journalId;

	/** @var WebFeedPlugin Web feed plugin */
	private $_plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function SettingsForm($plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin = $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->_journalId;
		$plugin = $this->_plugin;

		$this->setData('displayPage', $plugin->getSetting($journalId, 'displayPage'));
		$this->setData('displayItems', $plugin->getSetting($journalId, 'displayItems'));
		$this->setData('recentItems', $plugin->getSetting($journalId, 'recentItems'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('displayPage','displayItems','recentItems'));

		// check that recent items value is a positive integer
		if ((int) $this->getData('recentItems') <= 0) $this->setData('recentItems', '');

		// if recent items is selected, check that we have a value
		if ($this->getData('displayItems') == 'recent') {
			$this->addCheck(new FormValidator($this, 'recentItems', 'required', 'plugins.generic.webfeed.settings.recentItemsRequired'));
		}

	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = $this->_plugin;
		$journalId = $this->_journalId;

		$plugin->updateSetting($journalId, 'displayPage', $this->getData('displayPage'));
		$plugin->updateSetting($journalId, 'displayItems', $this->getData('displayItems'));
		$plugin->updateSetting($journalId, 'recentItems', $this->getData('recentItems'));
	}
}

?>
