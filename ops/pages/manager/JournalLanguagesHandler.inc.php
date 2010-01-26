<?php

/**
 * @file JournalLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing journal language settings. 
 */

// $Id$

import('pages.manager.ManagerHandler');

class JournalLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function JournalLanguagesHandler() {
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
	 */
	function saveLanguageSettings() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'languages'),
				'pageTitle' => 'common.languages',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.journalManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}
	
	function reloadLocalizedDefaultSettings() {
		// make sure the locale is valid
		$locale = Request::getUserVar('localeToLoad');
		if ( !Locale::isLocaleValid($locale) ) {
			Request::redirect(null, null, 'languages');
		}

		$this->validate();
		$this->setupTemplate(true);
					
		$journal =& Request::getJournal();
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->reloadLocalizedDefaultSettings($journal->getId(), 'registry/journalSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'journalPath' => $journal->getData('path'),
				'primaryLocale' => $journal->getPrimaryLocale(),
				'journalName' => $journal->getTitle($journal->getPrimaryLocale())
			),
			$locale);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'currentUrl' => Request::url(null, null, 'languages'),
			'pageTitle' => 'common.languages',
			'message' => 'common.changesSaved',
			'backLink' => Request::url(null, Request::getRequestedPage()),
			'backLinkLabel' => 'manager.journalManagement'
		));
		$templateMgr->display('common/message.tpl');
	}

	

}
?>
