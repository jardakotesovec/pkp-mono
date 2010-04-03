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

// $Id$

import('pages.manager.ManagerHandler');

class PressLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function PressLanguagesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit language settings.
	 */
	function languages() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

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
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			import('notification.NotificationManager');
			$notificationManager =& new NotificationManager();
			$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
			$request->redirect(null, null, 'index');
			$templateMgr->display('common/message.tpl');
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

		$this->validate();
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

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'currentUrl' => $request->url(null, null, 'languages'),
			'pageTitle' => 'common.languages',
			'message' => 'common.changesSaved',
			'backLink' => $request->url(null, $request->getRequestedPage()),
			'backLinkLabel' => 'manager.pressManagement'
		));
		$templateMgr->display('common/message.tpl');
	}
}

?>
