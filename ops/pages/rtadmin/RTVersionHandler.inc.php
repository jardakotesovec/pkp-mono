<?php

/**
 * @file RTVersionHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTVersionHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- setup section.
 */

// $Id$


import('rt.ojs.JournalRTAdmin');
import('pages.rtadmin.RTAdminHandler');

class RTVersionHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTVersionHandler() {
		parent::RTAdminHandler();
	}
	
	function createVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();

		import('rt.ojs.form.VersionForm');
		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$versionForm =& new VersionForm(null, $journal->getJournalId());

		if (isset($args[0]) && $args[0]=='save') {
			$versionForm->readInputData();
			$versionForm->execute();
			Request::redirect(null, null, 'versions');
		} else {
			$this->setupTemplate(true);
			$versionForm->display();
		}
	}

	function exportVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getJournalId());

		if ($version) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign_by_ref('version', $version);

			$templateMgr->display('rtadmin/exportXml.tpl', 'application/xml');
		}
		else Request::redirect(null, null, 'versions');
	}

	function importVersion() {
		$this->validate();
		$journal =& Request::getJournal();

		$fileField = 'versionFile';
		if (isset($_FILES[$fileField]['tmp_name']) && is_uploaded_file($_FILES[$fileField]['tmp_name'])) {
			$rtAdmin = new JournalRTAdmin($journal->getJournalId());
			$rtAdmin->importVersion($_FILES[$fileField]['tmp_name']);
		}
		Request::redirect(null, null, 'versions');
	}

	function restoreVersions() {
		$this->validate();

		$journal =& Request::getJournal();
		$rtAdmin = new JournalRTAdmin($journal->getJournalId());
		$rtAdmin->restoreVersions();

		// If the journal RT was configured, change its state to
		// "disabled" because the RT version it was configured for
		// has now been deleted.
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournal($journal);
		if ($journalRt) {
			$journalRt->setVersion(null);
			$rtDao->updateJournalRT($journalRt);
		}

		Request::redirect(null, null, 'versions');
	}

	function versions() {
		$this->validate();
		$this->setupTemplate(true);

		$journal = Request::getJournal();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('versions');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('versions', $rtDao->getVersions($journal->getJournalId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.versions');
		$templateMgr->display('rtadmin/versions.tpl');
	}

	function editVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			import('rt.ojs.form.VersionForm');
			$this->setupTemplate(true, $version);
			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$versionForm =& new VersionForm($versionId, $journal->getJournalId());
			$versionForm->initData();
			$versionForm->display();
		}
		else Request::redirect(null, null, 'versions');
	}

	function deleteVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $journal->getJournalId());

		Request::redirect(null, null, 'versions');
	}

	function saveVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			import('rt.ojs.form.VersionForm');
			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$versionForm =& new VersionForm($versionId, $journal->getJournalId());
			$versionForm->readInputData();
			$versionForm->execute();
		}

		Request::redirect(null, null, 'versions');
	}
}

?>
