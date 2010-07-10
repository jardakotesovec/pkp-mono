<?php

/**
 * SelectRoleBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectRoleBlockPlugin
 * @ingroup plugins
 *
 * @brief Class for select role block plugin
 */


import('lib.pkp.classes.plugins.BlockPlugin');

class SelectRoleBlockPlugin extends BlockPlugin {

	/** @var User */
	var $_user;

	/** @var mixed */
	var $_context;

	/**
	 * @see PKPPlugin::getEnabled()
	 */
	function getEnabled() {
		// Only display the the block after installation
		// and only if a user is logged in.
		if (Config::getVar('general', 'installed')) {
			$request =& Registry::get('request');
			$this->_user = $request->getUser();
			if ($this->_user) {
				$router =& $request->getRouter();
				$this->_context =& $router->getContext($request);

				// Delegate to the parent class to
				// see whether the plug-in is enabled
				// in the configuration.
				return parent::getEnabled();
			} else return false;
		} else {
			return false;
		}
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on conference creation.
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
		return Locale::translate('plugins.block.selectRole.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.selectRole.description');
	}

	function getContents(&$templateMgr) {
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userId =& $this->_user->getId();

		if ($this->_context) {
			$userGroups =& $userGroupDao->getByUserId($userId, $this->_context->getId());
		} else {
			$userGroups =& $userGroupDao->getByUserId($userId);
		}

		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		$actingAsUserGroupId = $session->getActingAsUserGroupId();

		$templateMgr->assign_by_ref('userGroups', $userGroups);
		$templateMgr->assign('actingAsUserGroupId', $actingAsUserGroupId);
		return parent::getContents($templateMgr);
	}
}

?>
