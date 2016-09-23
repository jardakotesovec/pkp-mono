<?php

/**
 * @file plugins/generic/googleScholar/GoogleScholarPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GoogleScholarPlugin
 * @ingroup plugins_generic_googleScholar
 *
 * @brief Inject Google Scholar meta tags into article views to facilitate indexing.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class GoogleScholarPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('ArticleHandler::view',array(&$this, 'articleView'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new context
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Inject Google Scholar metadata into article view
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function articleView($hookName, $args) {
		$request = $args[0];
		$issue = $args[1];
		$article = $args[2];
		$journal = $request->getContext();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->addHeader('googleScholarRevision', '<meta name="gs_meta_revision" content="1.1"/>');
		$templateMgr->addHeader('googleScholarJournalTitle', '<meta name="citation_journal_title" content="' . htmlspecialchars($journal->getLocalizedName()) . '"/>');
		if (($issn = $journal->getSetting('onlineIssn')) || ($issn = $journal->getSetting('printIssn')) || ($issn = $journal->getSetting('issn'))) {
			$templateMgr->addHeader('googleScholarIssn', '<meta name="citation_issn" content="' . htmlspecialchars($issn) . '"/> ');
			
		}

		foreach ($article->getAuthors() as $i => $author) {
			$templateMgr->addHeader('googleScholarAuthor' . $i, '<meta name="citation_author" content="' . htmlspecialchars($author->getFirstName()) . (($middleName = htmlspecialchars($author->getMiddleName()))?" $middleName":'') . ' ' . htmlspecialchars($author->getLastName()) . '"/>');
			if ($affiliation = htmlspecialchars($author->getLocalizedAffiliation())) {
				$templateMgr->addHeader('googleScholarAuthor' . $i . 'Affiliation', '<meta name="citation_author_institution" content="' . $affiliation . '"/>');
			}
		}

		$templateMgr->addHeader('googleScholarTitle', '<meta name="citation_title" content="' . htmlspecialchars($article->getLocalizedTitle()) . '"/>');

		if (is_a($article, 'PublishedArticle') && ($datePublished = $article->getDatePublished())) {
			$templateMgr->addHeader('googleScholarDate', '<meta name="citation_date" content="' . strftime('%Y/%m/%d', strtotime($datePublished)) . '"/>');
		} elseif ($issue && $issue->getYear()) {
			$templateMgr->addHeader('googleScholarDate', '<meta name="citation_date" content="' . htmlspecialchars($issue->getYear()) . '"/>');
		} elseif ($issue && ($datePublished = $issue->getDatePublished())) {
			$templateMgr->addHeader('googleScholarDate', '<meta name="citation_date" content="' . strftime('%Y/%m/%d', strtotime($datePublished)) . '"/>');
		}

		if ($issue) {
			$templateMgr->addHeader('googleScholarVolume', '<meta name="citation_volume" content="' . htmlspecialchars($issue->getVolume()) . '"/>');
			$templateMgr->addHeader('googleScholarNumber', '<meta name="citation_issue" content="' . htmlspecialchars($issue->getNumber()) . '"/>');
		}

		if ($article->getPages()) {
			if ($startPage = $article->getStartingPage()) $templateMgr->addHeader('googleScholarStartPage', '<meta name="citation_firstpage" content="' . htmlspecialchars($startPage) . '"/>');
			if ($endPage = $article->getEndingPage()) $templateMgr->addHeader('googleScholarEndPage', '<meta name="citation_lastpage" content="' . htmlspecialchars($endPage) . '"/>');
		}

		foreach($templateMgr->get_template_vars('pubIdPlugins') as $pubIdPlugin) {
			if ($pubId = $pubIdPlugin->getPubId($article)) {
				$templateMgr->addHeader('googleScholarPubId' . $pubIdPlugin->getPubIdDisplayType(), '<meta name="citation_' . htmlspecialchars(strtolower($pubIdPlugin->getPubIdDisplayType())) . '" content="' . htmlspecialchars($pubId) . '"/>');
				
			}
		}

		$templateMgr->addHeader('googleScholarHtmlUrl', '<meta name="citation_abstract_html_url" content="' . $request->url(null, 'article', 'view', array($article->getBestArticleId($journal))) . '"/>');
		if ($language = $article->getLanguage()) $templateMgr->addHeader('googleScholarLanguage', '<meta name="citation_language" content="' . htmlspecialchars($language()) . '"/>');

		$i=0;
		if ($subject = $article->getSubject(null)) foreach ($subject as $locale => $localeSubject) {
			foreach (explode($localeSubject, '; ') as $gsKeyword) if ($gsKeyword) {
				$templateMgr->addHeader('googleScholarKeyword' . $i++, '<meta name="citation_keywords" xml:lang="' . htmlspecialchars(substr($locale, 0, 2)) . '" content="' . htmlspecialchars($gsKeyword) . '"/>');
			}
		}

		$i=$j=0;
		if (is_a($article, 'PublishedArticle')) foreach ($article->getGalleys() as $galley) {
			if (is_a($galley->getFile(), 'SupplementaryFile')) continue;
				$templateMgr->addHeader('googleScholarPdfUrl' . $i++, '<meta name="citation_pdf_url" content="' . $request->url(null, 'article', 'download', array($article->getBestArticleId($journal), $galley->getBestGalleyId($journal))) . '"/>');
			if ($galley->getFileType()=='application/pdf') {
			} else {
				$templateMgr->addHeader('googleScholarHtmlUrl' . $i++, '<meta name="citation_fulltext_html_url" content="' . $request->url(null, 'article', 'view', array($article->getBestArticleId($journal), $galley->getBestGalleyId($journal))) . '"/>');
			}
		}

		return false;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.googleScholar.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.googleScholar.description');
	}
}

?>
