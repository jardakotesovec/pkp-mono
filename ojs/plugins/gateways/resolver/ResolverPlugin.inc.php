<?php

/**
 * @file plugins/gateways/resolver/ResolverPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ResolverPlugin
 * @ingroup plugins_gateways_resolver
 *
 * @brief Simple resolver gateway plugin
 */

import('classes.plugins.GatewayPlugin');

class ResolverPlugin extends GatewayPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ResolverPlugin';
	}

	function getDisplayName() {
		return __('plugins.gateways.resolver.displayName');
	}

	function getDescription() {
		return __('plugins.gateways.resolver.description');
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args, $request) {
		if (!$this->getEnabled()) {
			return false;
		}

		$scheme = array_shift($args);
		switch ($scheme) {
			case 'doi':
				$doi = implode('/', $args);
				$journal =& $request->getJournal();
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
				$article =& $publishedArticleDao->getPublishedArticleByPubId('doi', $doi, $journal?$journal->getId():null);
				if(is_a($article, 'PublishedArticle')) {
					$request->redirect(null, 'article', 'view', $article->getBestArticleId());
				}
				break;
			case 'vnp': // Volume, number, page
			case 'ynp': // Volume, number, year, page
				// This can only be used from within a journal context
				$journal =& $request->getJournal();
				if (!$journal) break;

				if ($scheme == 'vnp') {
					$volume = (int) array_shift($args);
					$year = null;
				} elseif ($scheme == 'ynp') {
					$year = (int) array_shift($args);
					$volume = null;
				}
				$number = array_shift($args);
				$page = (int) array_shift($args);

				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getPublishedIssuesByNumber($journal->getId(), $volume, $number, $year);

				// Ensure only one issue matched, and fetch it.
				$issue = $issues->next();
				if (!$issue || $issues->next()) break;
				unset($issues);

				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$articles =& $publishedArticleDao->getPublishedArticles($issue->getId());
				foreach ($articles as $article) {
					// Look for the correct page in the list of articles.
					$matches = null;
					if (String::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)$/', $article->getPages(), $matches)) {
						$matchedPage = $matches[1];
						if ($page == $matchedPage) $request->redirect(null, 'article', 'view', $article->getBestArticleId());
					}
					if (String::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)[ ]?-[ ]?([Pp][Pp]?[.]?[ ]?)?(\d+)$/', $article->getPages(), $matches)) {
						$matchedPageFrom = $matches[1];
						$matchedPageTo = $matches[3];
						if ($page >= $matchedPageFrom && ($page < $matchedPageTo || ($page == $matchedPageTo && $matchedPageFrom = $matchedPageTo))) $request->redirect(null, 'article', 'view', $article->getBestArticleId());
					}
					unset($article);
				}
		}

		// Failure.
		header("HTTP/1.0 500 Internal Server Error");
		$templateMgr =& TemplateManager::getManager($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
		$templateMgr->assign('message', 'plugins.gateways.resolver.errors.errorMessage');
		$templateMgr->display('common/message.tpl');
		exit;
	}

	function sanitize($string) {
		return str_replace("\t", " ", strip_tags($string));
	}

	function exportHoldings() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$journals = $journalDao->getJournals(true);
		$request = $this->getRequest();
		header('content-type: text/plain');
		header('content-disposition: attachment; filename=holdings.txt');
		echo "title\tissn\te_issn\tstart_date\tend_date\tembargo_months\tembargo_days\tjournal_url\tvol_start\tvol_end\tiss_start\tiss_end\n";
		while ($journal = $journals->next()) {
			$issues = $issueDao->getPublishedIssues($journal->getId());
			$startDate = $endDate = null;
			$startNumber = $endNumber = null;
			$startVolume = $endVolume = null;
			while ($issue = $issues->next()) {
				$datePublished = $issue->getDatePublished();
				if ($datePublished !== null) $datePublished = strtotime($datePublished);
				if ($startDate === null || $startDate > $datePublished) $startDate = $datePublished;
				if ($endDate === null || $endDate < $datePublished) $endDate = $datePublished;
				$volume = $issue->getVolume();
				if ($startVolume === null || $startVolume > $volume) $startVolume = $volume;
				if ($endVolume === null || $endVolume < $volume) $endVolume = $volume;
				$number = $issue->getNumber();
				if ($startNumber === null || $startNumber > $number) $startNumber = $number;
				if ($endNumber === null || $endNumber < $number) $endNumber = $number;
			}

			echo $this->sanitize($journal->getLocalizedName()) . "\t";
			echo $this->sanitize($journal->getSetting('printIssn')) . "\t";
			echo $this->sanitize($journal->getSetting('onlineIssn')) . "\t";
			echo $this->sanitize($startDate===null?'':strftime('%Y-%m-%d', $startDate)) . "\t"; // start_date
			echo $this->sanitize($endDate===null?'':strftime('%Y-%m-%d', $endDate)) . "\t"; // end_date
			echo $this->sanitize('') . "\t"; // embargo_months
			echo $this->sanitize('') . "\t"; // embargo_days
			echo $request->url($journal->getPath()) . "\t"; // journal_url
			echo $this->sanitize($startVolume) . "\t"; // vol_start
			echo $this->sanitize($endVolume) . "\t"; // vol_end
			echo $this->sanitize($startNumber) . "\t"; // iss_start
			echo $this->sanitize($endNumber) . "\n"; // iss_end

		}
	}

	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if (Validation::isSiteAdmin() && $this->getEnabled()) {
			$verbs[] = array(
				'exportHoldings',
				__('plugins.gateways.resolver.exportHoldings')
			);
		}
		return $verbs;
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		switch ($verb) {
			case 'exportHoldings':
				if (Validation::isSiteAdmin() && $this->getEnabled()) {
					$this->exportHoldings();
					return true;
				}
				break;
		}
		return parent::manage($verb, $args);
	}
}

?>
