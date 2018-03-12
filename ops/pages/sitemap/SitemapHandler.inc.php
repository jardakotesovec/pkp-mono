<?php

/**
 * @file pages/sitemap/SitemapHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SitemapHandler
 * @ingroup pages_sitemap
 *
 * @brief Produce a sitemap in XML format for submitting to search engines.
 */

import('lib.pkp.classes.xml.XMLCustomWriter');
import('classes.handler.Handler');

define('SITEMAP_XSD_URL', 'http://www.sitemaps.org/schemas/sitemap/0.9');

class SitemapHandler extends Handler {
	/**
	 * Generate an XML sitemap for webcrawlers
	 * Creates a sitemap index if in site context, else creates a sitemap
	 */
	function index() {
		if (Request::getRequestedJournalPath() == 'index') {
			$doc = $this->_createSitemapIndex();
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: inline; filename=\"sitemap_index.xml\"");
			XMLCustomWriter::printXML($doc);
		} else {
			$doc = $this->_createJournalSitemap();
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: inline; filename=\"sitemap.xml\"");
			XMLCustomWriter::printXML($doc);
		}
	}

	/**
	 * Construct a sitemap index listing each journal's individual sitemap
	 * @return XMLNode
	 */
	function _createSitemapIndex() {
		$journalDao = DAORegistry::getDAO('JournalDAO');

		$doc = XMLCustomWriter::createDocument();
		$root = XMLCustomWriter::createElement($doc, 'sitemapindex');
		XMLCustomWriter::setAttribute($root, 'xmlns', SITEMAP_XSD_URL);

		$journals = $journalDao->getAll(true);
		while ($journal = $journals->next()) {
			$sitemapUrl = Request::url($journal->getPath(), 'sitemap');
			$sitemap = XMLCustomWriter::createElement($doc, 'sitemap');
			XMLCustomWriter::createChildWithText($doc, $sitemap, 'loc', $sitemapUrl, false);
			XMLCustomWriter::appendChild($root, $sitemap);
		}

		XMLCustomWriter::appendChild($doc, $root);
		return $doc;
	}

	 /**
	 * Construct the sitemap
	 * @return XMLNode
	 */
	function _createJournalSitemap() {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');

		$journal = Request::getJournal();
		$journalId = $journal->getId();

		$doc = XMLCustomWriter::createDocument();
		$root = XMLCustomWriter::createElement($doc, 'urlset');
		XMLCustomWriter::setAttribute($root, 'xmlns', SITEMAP_XSD_URL);

		// Journal home
		XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(),'index','index')));
		// User register
		if ($journal->getSetting('disableUserReg') != 1) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'user', 'register')));
		}
		// User login
		XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'login')));
		// Announcements
		if ($journal->getSetting('enableAnnouncements') == 1) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'announcement')));
			$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
			$announcementsResult = $announcementDao->getByAssocId(ASSOC_TYPE_JOURNAL, $journalId);
			while ($announcement = $announcementsResult->next()) {
				XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'announcement', 'view', $announcement->getId())));
			}
		}
		// About: journal
		if (!empty($journal->getSetting('about'))) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'about')));
		}
		// About: submissions
		XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'about', 'submissions')));
		// About: editorial team
		if (!empty($journal->getSetting('editorialTeam'))) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'about', 'editorialTeam')));
		}
		// About: contact
		if (!empty($journal->getSetting('mailingAddress')) || !empty($journal->getSetting('contactName'))) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'about', 'contact')));
		}
		// Search
		XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'search')));
		XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'search', 'authors')));
		// Issues
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_NONE) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'issue', 'current')));
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'issue', 'archive')));
			$publishedIssues = $issueDao->getPublishedIssues($journalId);
			while ($issue = $publishedIssues->next()) {
				XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'issue', 'view', $issue->getId())));
				// Articles for issue
				$articles = $publishedArticleDao->getPublishedArticles($issue->getId());
				foreach($articles as $article) {
					// Abstract
					XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId()))));
					// Galley files
					$galleys = $galleyDao->getBySubmissionId($article->getId());
					while ($galley = $galleys->next()) {
						XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId(), $galley->getBestGalleyId()))));
					}
				}
			}
		}
		// Custom pages (navigation menu items)
		$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO');
		$menuItemsResult = $navigationMenuItemDao->getByType(NMI_TYPE_CUSTOM, $journalId);
		while ($menuItem = $menuItemsResult->next()) {
			XMLCustomWriter::appendChild($root, $this->_createUrlTree($doc, Request::url($journal->getPath(), $menuItem->getPath())));
		}

		XMLCustomWriter::appendChild($doc, $root);

		// Enable plugins to change the sitemap
		HookRegistry::call('SitemapHandler::createJournalSitemap', array(&$doc));

		return $doc;
	}

	/**
	 * Create a url entry with children
	 * @param $doc XMLNode Reference to the XML document object
	 * @param $loc string URL of page (required)
	 * @param $lastmod string Last modification date of page (optional)
	 * @param $changefreq Frequency of page modifications (optional)
	 * @param $priority string Subjective priority assessment of page (optional)
	 * @return XMLNode
	 */
	function _createUrlTree(&$doc, $loc, $lastmod = null, $changefreq = null, $priority = null) {
		$url =& XMLCustomWriter::createElement($doc, 'url');

		XMLCustomWriter::createChildWithText($doc, $url, 'loc', $loc, false);
		XMLCustomWriter::createChildWithText($doc, $url, 'lastmod', $lastmod, false);
		XMLCustomWriter::createChildWithText($doc, $url, 'changefreq', $changefreq, false);
		XMLCustomWriter::createChildWithText($doc, $url, 'priority', $priority, false);

		return $url;
	}

}

?>
