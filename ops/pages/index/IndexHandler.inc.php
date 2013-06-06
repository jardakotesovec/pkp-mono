<?php

/**
 * @file pages/index/IndexHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndexHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

import('classes.handler.Handler');

class IndexHandler extends Handler {
	/**
	 * Constructor
	 */
	function IndexHandler() {
		parent::Handler();
	}

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 * @param $args array
	 * @param $request Request
	 */
	function index($args, $request) {
		$this->validate(null, $request);
		$this->setupTemplate($request);

		$router = $request->getRouter();
		$templateMgr = TemplateManager::getManager($request);
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journalPath = $router->getRequestedContextPath($request);
		$journal = $this->getTargetContext($request);
		if ($journal) {
			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $journal->getLocalizedPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $journal->getLocalizedPageHeaderLogo(true));
			$templateMgr->assign('displayPageHeaderTitleAltText', $journal->getLocalizedSetting('homeHeaderTitleImageAltText'));
			$templateMgr->assign('displayPageHeaderLogoAltText', $journal->getLocalizedSetting('homeHeaderLogoImageAltText'));
			$templateMgr->assign('additionalHomeContent', $journal->getLocalizedSetting('additionalHomeContent'));
			$templateMgr->assign('homepageImage', $journal->getLocalizedSetting('homepageImage'));
			$templateMgr->assign('homepageImageAltText', $journal->getLocalizedSetting('homepageImageAltText'));
			$templateMgr->assign('journalDescription', $journal->getLocalizedSetting('description'));

			$displayCurrentIssue = $journal->getSetting('displayCurrentIssue');
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getCurrent($journal->getId(), true);
			if ($displayCurrentIssue && isset($issue)) {
				import('pages.issue.IssueHandler');
				// The current issue TOC/cover page should be displayed below the custom home page.
				IssueHandler::_setupIssueTemplate($request, $issue);
			}

			$enableAnnouncements = $journal->getSetting('enableAnnouncements');
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $journal->getSetting('enableAnnouncementsHomepage');
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $journal->getSetting('numAnnouncementsHomepage');
					$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
					$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			}

			// Include any social media items that are configured for the context itself.
			$socialMediaDao = DAORegistry::getDAO('SocialMediaDAO');
			$socialMedia =& $socialMediaDao->getEnabledForContextByContextId($journal->getId());
			$blocks = array();
			while ($media = $socialMedia->next()) {
				$media->replaceCodeVars();
				$blocks[] = $media->getCode();
			}
			$templateMgr->assign('socialMediaBlocks', $blocks);
			$templateMgr->assign('journal', $journal);

			$templateMgr->display('index/journal.tpl');
		} else {
			$site = $request->getSite();

			if ($site->getRedirect() && ($journal = $journalDao->getById($site->getRedirect())) != null) {
				$request->redirect($journal->getPath());
			}

			$templateMgr->assign('intro', $site->getLocalizedIntro());
			$templateMgr->assign('journalFilesPath', $request->getBaseUrl() . '/' . Config::getVar('files', 'public_files_dir') . '/journals/');

			// If we're using paging, fetch the parameters
			$usePaging = $site->getSetting('usePaging');
			if ($usePaging) $rangeInfo = $this->getRangeInfo($request, 'journals');
			else $rangeInfo = null;
			$templateMgr->assign('usePaging', $usePaging);

			// Fetch the alpha list parameters
			$searchInitial = $request->getUserVar('searchInitial');
			$templateMgr->assign('searchInitial', $searchInitial);
			$templateMgr->assign('useAlphalist', $site->getSetting('useAlphalist'));

			$journals = $journalDao->getJournals(
				true,
				$rangeInfo,
				$searchInitial?JOURNAL_FIELD_TITLE:JOURNAL_FIELD_SEQUENCE,
				$searchInitial?JOURNAL_FIELD_TITLE:null,
				$searchInitial?'startsWith':null,
				$searchInitial
			);
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->assign_by_ref('site', $site);

			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));

			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
			$templateMgr->display('index/site.tpl');
		}
	}
}

?>
