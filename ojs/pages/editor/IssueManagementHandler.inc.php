<?php

/**
 * @file IssueManagementHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueManagementHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for issue management in publishing.
 */

// $Id$

import('pages.editor.EditorHandler');

class IssueManagementHandler extends EditorHandler {
	/** issue associated with the request **/
	var $issue;

	/**
	 * Constructor
	 **/
	function IssueManagementHandler() {
		parent::EditorHandler();
	}

	/**
	 * Displays the listings of future (unpublished) issues
	 */
	function futureIssues() {
		$this->validate(null, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$rangeInfo = Handler::getRangeInfo('issues');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getUnpublishedIssues($journal->getJournalId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('editor/issues/futureIssues.tpl');
	}

	/**
	 * Displays the listings of back (published) issues
	 */
	function backIssues() {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$rangeInfo = Handler::getRangeInfo('issues');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getPublishedIssues($journal->getJournalId(), $rangeInfo));

		$allIssuesIterator = $issueDao->getPublishedIssues($journal->getJournalId());
		$issueMap = array();
		while ($issue =& $allIssuesIterator->next()) {
			$issueMap[$issue->getIssueId()] = $issue->getIssueIdentification();
			unset($issue);
		}
		$templateMgr->assign('allIssues', $issueMap);
		$templateMgr->assign('rangeInfo', $rangeInfo);

		$currentIssue =& $issueDao->getCurrentIssue($journal->getJournalId());
		$currentIssueId = $currentIssue?$currentIssue->getIssueId():null;
		$templateMgr->assign('currentIssueId', $currentIssueId);

		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->assign('usesCustomOrdering', $issueDao->customIssueOrderingExists($journal->getJournalId()));
		$templateMgr->display('editor/issues/backIssues.tpl');
	}

	/**
	 * Removes an issue
	 */
	function removeIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue; 
		$isBackIssue = $issue->getPublished() > 0 ? true: false;

		// remove all published articles and return original articles to editing queue
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			foreach ($publishedArticles as $article) {
				$articleDao->changeArticleStatus($article->getArticleId(),STATUS_QUEUED);
				$publishedArticleDao->deletePublishedArticleById($article->getPubId());
			}
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteIssue($issue);
		if ($issue->getCurrent()) {
			$journal =& Request::getJournal();
			$issues = $issueDao->getPublishedIssues($journal->getJournalId());
			if (!$issues->eof()) {
				$issue =& $issues->next();
				$issue->setCurrent(1);
				$issueDao->updateIssue($issue);
			}
		}

		if ($isBackIssue) {
			Request::redirect(null, null, 'backIssues');
		} else {
			Request::redirect(null, null, 'futureIssues');
		}
	}

	/**
	 * Displays the create issue form
	 */
	function createIssue() {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('issue.form.IssueForm');

		$templateMgr =& TemplateManager::getManager();
		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.createIssue');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/createIssue.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/createIssue.tpl');
		}

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueForm->initData();
		}
		$issueForm->display();
	}

	/**
	 * Saves the new issue form
	 */
	function saveIssue() {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('issue.form.IssueForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/createIssue.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/createIssue.tpl');
		}

		$issueForm->readInputData();

		if ($issueForm->validate()) {
			$issueForm->execute();
			$this->futureIssues();
		} else {
			$templateMgr =& TemplateManager::getManager();
			import('issue.IssueAction');
			$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
			$templateMgr->assign('helpTopicId', 'publishing.createIssue');
			$issueForm->display();
		}
	}

	/**
	 * Displays the issue data page
	 */
	function issueData($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('issue.form.IssueForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/issueData.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/issueData.tpl');
		}

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueId = $issueForm->initData($issueId);
		}
		$templateMgr->assign('issueId', $issueId);

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$issueForm->display();
	}

	/**
	 * Edit the current issue form
	 */
	function editIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('issue.form.IssueForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/issueData.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/issueData.tpl');
		}
		$issueForm->readInputData();

		if ($issueForm->validate($issueId)) {
			$issueForm->execute($issueId);
			$issueForm->initData($issueId);
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());

		$issueForm->display();
	}

	/**
	 * Remove cover page from issue
	 */
	function removeCoverPage($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$formLocale = $args[1];
		$this->validate($issueId, true);
		$issue =& $this->issue; 

		import('file.PublicFileManager');
		$journal =& Request::getJournal();
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getFileName($formLocale));
		$issue->setFileName('', $formLocale);
		$issue->setOriginalFileName('', $formLocale);
		$issue->setWidth('', $formLocale);
		$issue->setHeight('', $formLocale);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Remove style file from issue
	 */
	function removeStyleFile($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 

		import('file.PublicFileManager');
		$journal =& Request::getJournal();
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getStyleFileName());
		$issue->setStyleFileName('');
		$issue->setOriginalStyleFileName('');

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Display the table of contents
	 */
	function issueToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$enablePublicArticleId = $journalSettingsDao->getSetting($journalId,'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enablePageNumber = $journalSettingsDao->getSetting($journalId, 'enablePageNumber');
		$templateMgr->assign('enablePageNumber', $enablePageNumber);
		$templateMgr->assign('customSectionOrderingExists', $customSectionOrderingExists = $sectionDao->customSectionOrderingExists($issueId));

		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished', !$issue->getPublished());
		$templateMgr->assign('issueAccess', $issue->getAccessStatus());

		// get issue sections and articles
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);

		$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$proofedArticleIds = $layoutEditorSubmissionDao->getProofedArticlesByIssueId($issueId);
		$templateMgr->assign('proofedArticleIds', $proofedArticleIds);

		$currSection = 0;
		$counter = 0;
		$sections = array();
		$sectionCount = 0;
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		foreach ($publishedArticles as $article) {
			$sectionId = $article->getSectionId();
			if ($currSection != $sectionId) {
				$lastSectionId = $currSection;
				$sectionCount++;
				if ($lastSectionId !== 0) $sections[$lastSectionId][5] = $customSectionOrderingExists?$sectionDao->getCustomSectionOrder($issueId, $sectionId):$sectionCount; // Store next custom order
				$currSection = $sectionId;
				$counter++;
				$sections[$sectionId] = array(
					$sectionId,
					$article->getSectionTitle(),
					array($article),
					$counter,
					$customSectionOrderingExists?
						$sectionDao->getCustomSectionOrder($issueId, $lastSectionId): // Last section custom ordering
						($sectionCount-1),
					null // Later populated with next section ordering
				);
			} else {
				$sections[$article->getSectionId()][2][] = $article;
			}
		}
		$templateMgr->assign_by_ref('sections', $sections);

		$templateMgr->assign('accessOptions', array(
			ARTICLE_ACCESS_ISSUE_DEFAULT => Locale::Translate('editor.issues.default'),
			ARTICLE_ACCESS_OPEN => Locale::Translate('editor.issues.open')
		));

		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.tableOfContents');
		$templateMgr->display('editor/issues/issueToc.tpl');
	}

	/**
	 * Updates issue table of contents with selected changes and article removals.
	 */
	function updateIssueToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);

		$journal =& Request::getJournal();

		$removedPublishedArticles = array();

		$publishedArticles = Request::getUserVar('publishedArticles');
		$removedArticles = Request::getUserVar('remove');
		$accessStatus = Request::getUserVar('accessStatus');
		$pages = Request::getUserVar('pages');

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$articles = $publishedArticleDao->getPublishedArticles($issueId);

		foreach($articles as $article) {
			$articleId = $article->getArticleId();
			$pubId = $article->getPubId();
			if (!isset($removedArticles[$articleId])) {
				if (isset($pages[$articleId])) {
					$article->setPages($pages[$articleId]);
				}
				if (isset($publishedArticles[$articleId])) {
					$publicArticleId = $publishedArticles[$articleId];
					if (!$publicArticleId || !$publishedArticleDao->publicArticleIdExists($publicArticleId, $articleId, $journal->getJournalId())) {
						$publishedArticleDao->updatePublishedArticleField($pubId, 'public_article_id', $publicArticleId);
					}
				}
				if (isset($accessStatus[$pubId])) {
					$publishedArticleDao->updatePublishedArticleField($pubId, 'access_status', $accessStatus[$pubId]);
				}
			} else {
				$article->setStatus(STATUS_QUEUED);
				$article->stampStatusModified();
				
				// If the article is the only one in the section, delete the section from custom issue ordering				
				$sectionId = $article->getSectionId();
				$publishedArticleArray =& $publishedArticleDao->getPublishedArticlesBySectionId($sectionId, $issueId);
				if (sizeof($publishedArticleArray) == 1) {
					$sectionDao->deleteCustomSection($issueId, $sectionId);
				}
				
				$publishedArticleDao->deletePublishedArticleById($pubId);
				$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issueId);
			}
			$articleDao->updateArticle($article);
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Change the sequence of an issue.
	 */
	function setCurrentIssue($args) {
		$issueId = Request::getUserVar('issueId');
		$journal =& Request::getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$this->validate($issueId);
			$issue =& $this->issue; 
			$issue->setCurrent(1);
			$issueDao->updateCurrentIssue($journal->getJournalId(), $issue);
		} else {
			$this->validate();
			$issueDao->updateCurrentIssue($journal->getJournalId());
		}
		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of an issue.
	 */
	function moveIssue($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue; 
		$journal =& Request::getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		// If custom issue ordering isn't yet in place, bring it in.
		if (!$issueDao->customIssueOrderingExists($journal->getJournalId())) {
			$issueDao->setDefaultCustomIssueOrders($journal->getJournalId());
		}

		$issueDao->moveCustomIssueOrder($journal->getJournalId(), $issue->getIssueId(), Request::getUserVar('newPos'), Request::getUserVar('d') == 'u');

		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Reset issue ordering to defaults.
	 */
	function resetIssueOrder($args) {
		$this->validate();

		$journal =& Request::getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteCustomIssueOrdering($journal->getJournalId());

		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSectionToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 
		$journal =& Request::getJournal();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection(Request::getUserVar('sectionId'), $journal->getJournalId());

		if ($section != null) {
			// If issue-specific section ordering isn't yet in place, bring it in.
			if (!$sectionDao->customSectionOrderingExists($issueId)) {
				$sectionDao->setDefaultCustomSectionOrders($issueId);
			}

			$sectionDao->moveCustomSectionOrder($issueId, $section->getSectionId(), Request::getUserVar('newPos'), Request::getUserVar('d') == 'u');
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Reset section ordering to section defaults.
	 */
	function resetSectionOrder($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		Request::redirect(null, null, 'issueToc', $issue->getIssueId());
	}

	/**
	 * Change the sequence of the articles.
	 */
	function moveArticleToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$this->validate($issueId, true);
		$issue =& $this->issue; 

		$journal =& Request::getJournal();

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleById(Request::getUserVar('pubId'));

		if ($publishedArticle != null && $publishedArticle->getIssueId() == $issue->getIssueId() && $issue->getJournalId() == $journal->getJournalId()) {
			$publishedArticle->setSeq($publishedArticle->getSeq() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$publishedArticleDao->updatePublishedArticle($publishedArticle);
			$publishedArticleDao->resequencePublishedArticles(Request::getUserVar('sectionId'),$issueId);
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Publish issue
	 */
	function publishIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue; 

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		if (!$issue->getPublished()) {
			// Set the status of any attendant queued articles to STATUS_PUBLISHED.
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$publishedArticles =& $publishedArticleDao->getPublishedArticles($issueId, null, true);
			foreach ($publishedArticles as $publishedArticle) {
				$article =& $articleDao->getArticle($publishedArticle->getArticleId());
				if ($article && $article->getStatus() == STATUS_QUEUED) {
					$article->setStatus(STATUS_PUBLISHED);
					$article->stampStatusModified();
					$articleDao->updateArticle($article);
				}
				unset($article);
			}
		}

		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		// If subscriptions with delayed open access are enabled then
		// update open access date according to open access delay policy
		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $journal->getSetting('enableDelayedOpenAccess')) {

			$delayDuration = $journal->getSetting('delayedOpenAccessDuration');
			$delayYears = (int)floor($delayDuration/12);
			$delayMonths = (int)fmod($delayDuration,12);

			$curYear = date('Y');
			$curMonth = date('n');
			$curDay = date('j');

			$delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth+$delayMonths)/12);
 			$delayOpenAccessMonth = (int)fmod($curMonth+$delayMonths,12);

			$issue->setAccessStatus(ISSUE_ACCESS_SUBSCRIPTION);
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$delayOpenAccessMonth,$curDay,$delayOpenAccessYear)));
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrentIssue($journalId,$issue);
		
		// Send a notification to associated users
		import('notification.Notification');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByJournalId($journalId);
		while (!$allUsers->eof()) {
			$user =& $allUsers->next();
			$notificationUsers[] = array('id' => $user->getId());
			unset($user);
		}
		$url = Request::url(null, 'issue', 'current');
		foreach ($notificationUsers as $userRole) {
			Notification::createNotification($userRole['id'], "notification.type.issuePublished",
				null, $url, 1, NOTIFICATION_TYPE_PUBLISHED_ISSUE);
		}
		$notificationDao =& DAORegistry::getDAO('NotificationDAO');
		$notificationDao->sendToMailingList(Notification::createNotification(0, "notification.type.issuePublished",
				null, $url, 1, NOTIFICATION_TYPE_PUBLISHED_ISSUE));
				
		Request::redirect(null, null, 'issueToc', $issue->getIssueId());
	}

	/**
	 * Unpublish a previously-published issue
	 */
	function unpublishIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($issueId);
		$issue =& $this->issue; 

		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		$issue->setCurrent(0);
		$issue->setPublished(0);
		$issue->setDatePublished(null);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'futureIssues');
	}

	/**
	 * Allows editors to write emails to users associated with the journal.
	 */
	function notifyUsers($args) {
		$this->validate(Request::getUserVar('issue'));
		$issue =& $this->issue; 
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$journal =& Request::getJournal();
		$user =& Request::getUser();
		$templateMgr =& TemplateManager::getManager();

		import('mail.MassMail');
		$email = new MassMail('PUBLISH_NOTIFY');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->addRecipient($user->getEmail(), $user->getFullName());

			$recipients = $roleDao->getUsersByJournalId($journal->getJournalId());

			while (!$recipients->eof()) {
				$recipient =& $recipients->next();
				$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
				unset($recipient);
			}

			if (Request::getUserVar('includeToc')=='1' && isset($issue)) {
				$issue = $issueDao->getIssueById(Request::getUserVar('issue'));

				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

				$templateMgr->assign_by_ref('journal', $journal);
				$templateMgr->assign_by_ref('issue', $issue);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);

				$email->setBody($templateMgr->fetch('editor/notifyUsersEmail.tpl'));

				// Stamp the "users notified" date.
				$issue->setDateNotified(Core::getCurrentDate());
				$issueDao->updateIssue($issue);
			}

			$callback = array(&$email, 'send');
			$templateMgr->setProgressFunction($callback);
			unset($callback);

			$email->setFrequency(10); // 10 emails per callback
			$callback = array(&$templateMgr, 'updateProgressBar');
			$email->setCallback($callback);
			unset($callback);

			$templateMgr->assign('message', 'editor.notifyUsers.inProgress');
			$templateMgr->display('common/progress.tpl');
			echo '<script type="text/javascript">window.location = "' . Request::url(null, 'editor') . '";</script>';
		} else {
			if (!Request::getUserVar('continued')) {
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature()
				));
			}
			$allUsersCount = $roleDao->getJournalUsersCount($journal->getJournalId());

			$issuesIterator =& $issueDao->getIssues($journal->getJournalId());

			$email->displayEditForm(
				Request::url(null, null, 'notifyUsers'),
				array(),
				'editor/notifyUsers.tpl',
				array(
					'issues' => $issuesIterator,
					'allUsersCount' => $allUsersCount
				)
			);
		}
	}

	/**
	 * Validate that user is an editor in the selected journal and if the issue id is valid
	 * Redirects to issue create issue page if not properly authenticated.
	 * NOTE: As of OJS 2.2, Layout Editors are allowed if specified in args.
	 */
	function validate($issueId = null, $allowLayoutEditor = false) {
		$issue = null;
		$journal =& Request::getJournal();

		if (!isset($journal)) Validation::redirectLogin();

		if (isset($issueId)) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getIssueById($issueId, $journal->getJournalId());

			if (!$issue) {
				Request::redirect(null, null, 'createIssue');
			}
		}

		if (!Validation::isEditor($journal->getJournalId())) {
			if (isset($journal) && $allowLayoutEditor && Validation::isLayoutEditor($journal->getJournalId())) {
				// We're a Layout Editor. If specified, make sure that the issue is not published.
				if ($issue && !$issue->getPublished()) {
					Validation::redirectLogin();
				}
			} else {
				Validation::redirectLogin();
			}
		}

		$this->issue =& $issue;
		return true;
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to one of EDITOR_SECTION_? defined in EditorHandler.
	 */
	function setupTemplate($level) {
		parent::setupTemplate($level);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('isLayoutEditor', Request::getRequestedPage() == 'layoutEditor');
	}
}
