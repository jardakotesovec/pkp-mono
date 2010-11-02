<?php

/**
 * @file PressLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PressLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing press language settings.
 */


import('pages.manager.ManagerHandler');

class PressLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function PressLanguagesHandler() {
		parent::ManagerHandler();
		$this->addRoleAssignment(ROLE_ID_PRESS_MANAGER,
				array('languages', 'saveLanguageSettings', 'reloadLocalizedDefaultSettings'));
	}

	/**
	 * Display form to edit language settings.
	 */
	function languages() {
		$this->setupTemplate(true);

		import('classes.manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to language settings.
	 * @param $args array
	 * @param $request object
	 */
	function saveLanguageSettings($args, &$request) {
		$this->setupTemplate(true);

		import('classes.manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager =& new NotificationManager();
			$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
			$request->redirect(null, null, 'index');
		} else {
			$settingsForm->display();
		}
	}

	/**
	 * Reload the default localized settings for the press
	 * @param $args array
	 * @param $request object
	 */
	function reloadLocalizedDefaultSettings($args, &$request) {
		// make sure the locale is valid
		$locale = $request->getUserVar('localeToLoad');
		if ( !Locale::isLocaleValid($locale) ) {
			$request->redirect(null, null, 'languages');
		}

		$this->setupTemplate(true);

		$press =& $request->getPress();
		$pressSettingsDao =& DAORegistry::getDAO('PressSettingsDAO');
		$pressSettingsDao->reloadLocalizedDefaultSettings(
			$press->getId(), 'registry/pressSettings.xml',
			array(
				'indexUrl' => $request->getIndexUrl(),
				'pressPath' => $press->getData('path'),
				'primaryLocale' => $press->getPrimaryLocale(),
				'pressName' => $press->getName($press->getPrimaryLocale())
			),
			$locale
		);

		// also reload the user group localizable data
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userGroupDao->installLocale($locale, $press->getId());

		// Display a notification
		import('lib.pkp.classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
		$request->redirect(null, null, 'languages');
	}
}

?>
