<?php

/**
 * ArticleLog.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 *
 * ArticleLog class.
 * Static class for adding / accessing article log entries.
 *
 * $Id$
 */

import('article.log.ArticleEventLogEntry');
import('article.log.ArticleEventLogDAO');
import('article.log.ArticleEmailLogEntry');
import('article.log.ArticleEmailLogDAO');

class ArticleLog {
	
	/**
	 * Add an event log entry to this article.
	 * @param $articleId int
	 * @param $entry ArticleEventLogEntry
	 */
	function logEventEntry($articleId, &$entry) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$journalId = &$articleDao->getArticleJournalId($articleId);
		
		if (!$journalId) {
			// Invalid article
			return false;
		}
		
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		if (!$settingsDao->getSetting($journalId, 'articleEventLog')) {
			// Event logging is disabled
			return false;
		}
	
		// Add the entry
		$entry->setArticleId($articleId);
		
		if ($entry->getUserId() == null) {
			$user = &Request::getUser();
			$entry->setUserId($user == null ? 0 : $user->getUserId());
		}
		
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		return $logDao->insertLogEntry($entry);
	}
	
	/**
	 * Add a new event log entry with the specified parameters.
	 * @param $articleId int
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEvent($articleId, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		$entry = &new ArticleEventLogEntry();
		$entry->setEventType($eventType);
		$entry->setAssocType($assocType);
		$entry->setAssocId($assocId);
		
		if (isset($messageKey)) {
			$entry->setLogMessage($messageKey, $messageParams);
		}
		
		ArticleLog::logEventEntry($articleId, $entry);
	}
	
	/**
	 * Get all event log entries for an article.
	 * @param $articleId int
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEventLogEntry
	 */
	function getEventLogEntries($articleId, $recentFirst = true) {
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		return $logDao->getArticleLogEntries($articleId, $recentFirst);
	}
	
	/**
	 * Add an email log entry to this article.
	 * @param $articleId int
	 * @param $entry ArticleEmailLogEntry
	 */
	function logEmailEntry($articleId, &$entry) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$journalId = &$articleDao->getArticleJournalId($articleId);
		
		if (!$journalId) {
			// Invalid article
			return false;
		}
		
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		if (!$settingsDao->getSetting($journalId, 'articleEmailLog')) {
			// Email logging is disabled
			return false;
		}
	
		// Add the entry
		$entry->setArticleId($articleId);
		
		if ($entry->getSenderId() == null) {
			$user = &Request::getUser();
			$entry->setSenderId($user == null ? 0 : $user->getUserId());
		}
		
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		return $logDao->insertLogEntry($entry);
	}
	
	/**
	 * Get all email log entries for an article.
	 * @param $articleId int
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEmailLogEntry
	 */
	function getEmailLogEntries($articleId, $recentFirst = true) {
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		return $logDao->getArticleLogEntries($articleId, $recentFirst);
	}
	
}

?>
