<?php

/**
 * RTVersionHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests -- setup section. 
 *
 * $Id$
 */

import('rt.ojs.JournalRTAdmin');

class RTVersionHandler extends RTAdminHandler {
	function exportVersion() {
		RTAdminHandler::validate();
	}

	function importVersion() {
		RTAdminHandler::validate();
	}

	function restoreVersions() {
		RTAdminHandler::validate();

		$journal = &Request::getJournal();
		$rtAdmin = &new JournalRTAdmin($journal->getJournalId());
		$rtAdmin->restoreVersions();

		Request::redirect('rtadmin/versions');
	}

	function versions() {
		RTAdminHandler::validate();
		RTAdminHandler::setupTemplate(true);

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('versions', $rtDao->getVersions($journal->getJournalId()));
		$templateMgr->display('rtadmin/versions.tpl');
	}

	function editVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			import('rt.ojs.form.VersionForm');
			RTAdminHandler::setupTemplate(true);
			$versionForm = new VersionForm($versionId, $journal->getJournalId());
			$versionForm->initData();
			$versionForm->display();
		}
		else Request::redirect('rtadmin/versions');

		
	}

	function deleteVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $journal->getJournalId());

		Request::redirect('rtadmin/versions');
	}

	function saveVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			import('rt.ojs.form.VersionForm');
			$versionForm = new VersionForm($versionId, $journal->getJournalId());
			$versionForm->readInputData();
			$versionForm->execute();
		}

		Request::redirect('rtadmin/versions');
	}
}

?>
