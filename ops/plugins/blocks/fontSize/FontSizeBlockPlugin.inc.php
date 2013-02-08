<?php

/**
 * @file plugins/blocks/fontSize/FontSizeBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FontSizeBlockPlugin
 * @ingroup plugins_blocks_fontSize
 *
 * @brief Class for font size block plugin
 *
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class FontSizeBlockPlugin extends BlockPlugin {

	/**
	 * Determine whether the plugin is enabled. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getEnabled() {
		if (!Config::getVar('general', 'installed')) $enabled = true;
		else $enabled = parent::getEnabled();

		if ($enabled) {
			HookRegistry::register('TemplateManager::display', array(&$this, 'displayTemplateCallback'));
		}
		return $enabled;
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the block context. Overrides parent so that the plugin will be
	 * displayed during install.
	 * @return int
	 */
	function getBlockContext() {
		if (!Config::getVar('general', 'installed')) return BLOCK_CONTEXT_LEFT_SIDEBAR;
		return parent::getBlockContext();
	}

	/**
	 * Determine the plugin sequence. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getSeq() {
		if (!Config::getVar('general', 'installed')) return 3;
		return parent::getSeq();
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.fontSize.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.fontSize.description');
	}

	/**
	 * Callback to add the sizer CSS and JS
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function displayTemplateCallback($hookName, $args) {
		$templateMgr =& $args[0];
		$request = $this->getRequest();
		$templateMgr->addStylesheet($request->getBaseUrl() . '/' . $this->getPluginPath() . '/fontSize.css');
		$templateMgr->addJavaScript($this->getPluginPath() . '/jquery.jfontsize-1.0.min.js');
		return false;
	}
}

?>
