<?php

/**
 * @file plugins/blocks/notification/NotificationBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationBlockPlugin
 * @ingroup plugins_blocks_notification
 *
 * @brief Class for "notification" block plugin
 */



import('lib.pkp.classes.plugins.BlockPlugin');

class NotificationBlockPlugin extends BlockPlugin {
	/**
	 * Determine whether the plugin is enabled. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getEnabled() {
		if (!Config::getVar('general', 'installed')) return true;
		return parent::getEnabled();
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on press creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.notification.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.notification.description');
	}


	function getContents(&$templateMgr) {
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$user = $request->getUser();
		$press = $request->getPress();

		if ($user && $press) {
			$userId = $user->getId();
			$notificationDao =& DAORegistry::getDAO('NotificationDAO');
			$templateMgr->assign('unreadNotifications',  $notificationDao->getNotificationCount(false, $userId, $press->getId()));
		}

		return parent::getContents($templateMgr);
	}
}

?>
