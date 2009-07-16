<?php

/**
 * @file classes/plugins/Plugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Plugin
 * @ingroup plugins
 *
 * @brief Abstract class for plugins
 */

// $Id$


import('plugins.PKPPlugin');

class Plugin extends PKPPlugin {
	/**
	 * Constructor
	 */
	function Plugin() {
		parent::PKPPlugin();
	}

	function getTemplatePath() {
		$basePath = dirname(dirname(dirname(__FILE__)));
		return "file:$basePath/" . $this->getPluginPath() . '/';
	}

	/**
	 * Called as a plugin is registered to the registry. Subclasses over-
	 * riding this method should call the parent method first.
	 * @param $category String Name of category plugin was registered to
	 * @param $path String The path the plugin was found in
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$returner = parent::register($category, $path);
		if ($this->getNewPressPluginSettingsFile()) {
			HookRegistry::register ('PressSiteSettingsForm::execute', array(&$this, 'installPressSettings'));
		}
		return $returner;
	}

	function getSetting($pressId, $name) {
		if (!Config::getVar('general', 'installed')) return null;
		if (defined('RUNNING_UPGRADE')) {
			// Bug #2504: Make sure plugin_settings table is not
			// used if it's not available.
			$versionDao =& DAORegistry::getDAO('VersionDAO');
			$version =& $versionDao->getCurrentVersion();
			if ($version->compare('2.1.0') < 0) return null;
		}
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		return $pluginSettingsDao->getSetting($pressId, $this->getName(), $name);
	}

	/**
	 * Update a plugin setting.
	 * @param $pressId int
	 * @param $name string The name of the setting
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($pressId, $name, $value, $type = null) {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->updateSetting($pressId, $this->getName(), $name, $value, $type);
	}

	/**
	 * Get the filename of the settings data for this plugin to install
	 * when a press is created (i.e. press-level plugin settings).
	 * Subclasses using default settings should override this.
	 * @return string
	 */
	function getNewPressPluginSettingsFile() {
		return null;
	}

	/**
	 * Callback used to install settings on press creation.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installPressSettings($hookName, $args) {
		$press =& $args[1];
		$isNewPress = $args[3];

		if (!$isNewPress) return false;

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->installSettings($press->getId(), $this->getName(), $this->getNewPressPluginSettingsFile());

		return false;
	}

	/**
	 * Callback used to install settings on system install.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installSiteSettings($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		// Settings are only installed during automated installs. FIXME!
		if (!$installer->getParam('manualInstall')) {
			$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
			$pluginSettingsDao->installSettings(0, $this->getName(), $this->getInstallSitePluginSettingsFile());
		}

		return false;
	}

	/**
	 * Get the current version of this plugin
	 * @return object Version
	 */
	function getCurrentVersion() {
		$versionDao =& DAORegistry::getDAO('VersionDAO'); 
		$product = basename($this->getPluginPath());
		$installedPlugin = $versionDao->getCurrentVersion($product);
		
		if ($installedPlugin) {
			return $installedPlugin;
		} else {
			return false;
		}
	}
}

?>
