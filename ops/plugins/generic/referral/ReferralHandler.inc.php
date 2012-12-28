<?php

/**
 * @file plugins/generic/referral/ReferralHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferralHandler
 * @ingroup plugins_generic_referral
 *
 * @brief This handles requests for the referral plugin.
 */

import('classes.handler.Handler');

class ReferralHandler extends Handler {
	/**
	 * Constructor
	 **/
	function ReferralHandler() {
		parent::Handler();
	}
	
	function setupTemplate($request) {
		parent::setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		$pageHierarchy = array(array($request->url(null, 'referral', 'index'), 'plugins.generic.referral.referrals'));
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	function editReferral($args, $request) {
		$referralId = (int) array_shift($args);
		if ($referralId === 0) $referralId = null;

		list($plugin, $referral, $article) = $this->validate($request, $referralId);
		$this->setupTemplate($request);

		$plugin->import('ReferralForm');
		$templateMgr =& TemplateManager::getManager($request);

		if ($referralId == null) {
			$templateMgr->assign('referralTitle', 'plugins.generic.referral.createReferral');
		} else {
			$templateMgr->assign('referralTitle', 'plugins.generic.referral.editReferral');	
		}

		$referralForm = new ReferralForm($plugin, $article, $referralId);
		if ($referralForm->isLocaleResubmit()) {
			$referralForm->readInputData();
		} else {
			$referralForm->initData();
		}
		$referralForm->display();
	}

	/**
	 * Save changes to an announcement type.
	 */
	function updateReferral($args, $request) {
		$referralId = (int) $request->getUserVar('referralId');
		if ($referralId === 0) $referralId = null;

		list($plugin, $referral, $article) = $this->validate($request, $referralId);
		// If it's an insert, ensure that it's allowed for this article
		if (!isset($referral)) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$journal =& $request->getJournal();
			$article =& $publishedArticleDao->getPublishedArticleByArticleId((int) $request->getUserVar('articleId'));
			if (!$article || ($article->getUserId() != $user->getId() && !Validation::isSectionEditor($journal->getId()) && !Validation::isEditor($journal->getId()))) {
				$request->redirect(null, 'author');
			}
		}
		$this->setupTemplate($request);

		$plugin->import('ReferralForm');

		$referralForm = new ReferralForm($plugin, $article, $referralId);
		$referralForm->readInputData();

		if ($referralForm->validate()) {
			$referralForm->execute();
			$request->redirect(null, 'author');
		} else {
			$templateMgr =& TemplateManager::getManager($request);

			if ($referralId == null) {
				$templateMgr->assign('referralTitle', 'plugins.generic.referral.createReferral');
			} else {
				$templateMgr->assign('referralTitle', 'plugins.generic.referral.editReferral');	
			}

			$referralForm->display();
		}
	}	

	function deleteReferral($args, $request) {
		$referralId = (int) array_shift($args);
		list($plugin, $referral) = $this->validate($request, $referralId);

		$referralDao =& DAORegistry::getDAO('ReferralDAO');
		$referralDao->deleteReferral($referral);

		$request->redirect(null, 'author');
	}

	function validate($request, $referralId = null) {
		parent::validate($request);

		if ($referralId) {
			$referralDao =& DAORegistry::getDAO('ReferralDAO');
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$referral =& $referralDao->getReferral($referralId);
			if (!$referral) $request->redirect(null, 'index');

			$user =& $request->getUser();
			$journal =& $request->getJournal();
			$article =& $publishedArticleDao->getPublishedArticleByArticleId($referral->getArticleId());
			if (!$article || !$journal) $request->redirect(null, 'index');
			if ($article->getJournalId() != $journal->getId()) $request->redirect(null, 'index');
			// The article's submitter, journal SE, and journal Editors are allowed.
			if ($article->getUserId() != $user->getId() && !Validation::isSectionEditor($journal->getId()) && !Validation::isEditor($journal->getId())) $request->redirect(null, 'index');
		} else {
			$referral = $article = null;
		}
		$plugin =& Registry::get('plugin');
		return array(&$plugin, &$referral, &$article);
	}
}

?>
