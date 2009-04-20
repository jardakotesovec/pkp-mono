<?php

/**
 * @file RTContextHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTContextHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- contexts section.
 */

// $Id$


import('rt.ojs.JournalRTAdmin');
import('pages.rtadmin.RTAdminHandler');

class RTContextHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTContextHandler() {
		parent::RTAdminHandler();
	}
	
	function createContext($args) {
		$this->validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		import('rt.ojs.form.ContextForm');
		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$contextForm =& new ContextForm(null, $versionId);

		if (isset($args[1]) && $args[1]=='save') {
			$contextForm->readInputData();
			$contextForm->execute();
			Request::redirect(null, null, 'contexts', $versionId);
		} else {
			$this->setupTemplate(true, $version);
			$contextForm->display();
		}
	}

	function contexts($args) {
		$this->validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('contexts');

		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if ($version) {
			$this->setupTemplate(true, $version);

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign_by_ref('version', $version);

			import('core.ArrayItemIterator');
			$templateMgr->assign_by_ref('contexts', new ArrayItemIterator($version->getContexts(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.contexts');
			$templateMgr->display('rtadmin/contexts.tpl');
		}
		else Request::redirect(null, null, 'versions');
	}

	function editContext($args) {
		$this->validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('rt.ojs.form.ContextForm');
			$this->setupTemplate(true, $version, $context);
			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$contextForm =& new ContextForm($contextId, $versionId);
			$contextForm->initData();
			$contextForm->display();
		}
		else Request::redirect(null, null, 'contexts', $versionId);


	}

	function deleteContext($args) {
		$this->validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$rtDao->deleteContext($contextId, $versionId);
		}

		Request::redirect(null, null, 'contexts', $versionId);
	}

	function saveContext($args) {
		$this->validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('rt.ojs.form.ContextForm');
			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$contextForm =& new ContextForm($contextId, $versionId);
			$contextForm->readInputData();
			$contextForm->execute();
		}

		Request::redirect(null, null, 'contexts', $versionId);
	}

	function moveContext($args) {
		$this->validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$context->setOrder($context->getOrder()+($isDown?1.5:-1.5));
			$rtDao->updateContext($context);
			$rtDao->resequenceContexts($version->getVersionId());
		}

		Request::redirect(null, null, 'contexts', $versionId);
	}
}

?>
