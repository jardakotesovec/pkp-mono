<?php

/**
 * ArticleEmailLogDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 *
 * Class for inserting/accessing article email log entries.
 *
 * $Id$
 */

class ArticleEmailLogDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ArticleEmailLogDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $articleId int optional
	 * @return ArticleEmailLogEntry
	 */
	function &getLogEntry($logId, $articleId = null) {
		if (isset($articleId)) {
			$result = &$this->retrieve(
				'SELECT * FROM article_email_log WHERE log_id = ? AND article_id = ?',
				array($logId, $articleId)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT * FROM article_email_log WHERE log_id = ?', $logId
			);
		}
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnLogEntryFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve all log entries for an article.
	 * @param $articleId int
	 * @param $limit int limit the number of entries retrieved (default false)
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEmailLogEntry ordered by sequence
	 */
	function &getArticleLogEntries($articleId, $limit = false, $recentFirst = true) {
		return $this->getArticleLogEntriesByAssoc($articleId, null, null, $limit, $recentFirst);
	}
	
	/**
	 * Retrieve all log entries for an article matching the specified association.
	 * @param $articleId int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $limit int limit the number of entries retrieved (default false)
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEventLogEntry ordered by sequence
	 */
	function &getArticleLogEntriesByAssoc($articleId, $assocType = null, $assocId = null, $limit = false, $recentFirst = true) {
		$entries = array();
		
		$params = array($articleId);
		if (isset($assocType)) {
			array_push($params, $assocType);
			if (isset($assocId)) {
				array_push($params, $assocId);
			}
		}
		
		$result = &$this->retrieveLimit(
			'SELECT * FROM article_email_log WHERE article_id = ?' . (isset($assocType) ? ' AND assoc_type = ?' . (isset($assocId) ? ' AND assoc_id = ?' : '') : '') . ' ORDER BY date_sent ' . ($recentFirst ? 'DESC' : 'ASC'),
			$params,
			$limit
		);
		
		while (!$result->EOF) {
			$entries[] = &$this->_returnLogEntryFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $entries;
	}
	
	/**
	 * Internal function to return an ArticleEmailLogEntry object from a row.
	 * @param $row array
	 * @return ArticleEmailLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = &new ArticleEmailLogEntry();
		$entry->setLogId($row['log_id']);
		$entry->setArticleId($row['article_id']);
		$entry->setSenderId($row['sender_id']);
		$entry->setDateSent($row['date_sent']);
		$entry->setIPAddress($row['ip_address']);
		$entry->setEventType($row['event_type']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setFrom($row['from_address']);
		$entry->setRecipients($row['recipients']);
		$entry->setCcs($row['cc_recipients']);
		$entry->setBccs($row['bcc_recipients']);
		$entry->setSubject($row['subject']);
		$entry->setBody($row['body']);
		
		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry ArticleEmailLogEntry
	 */	
	function insertLogEntry(&$entry) {
		$ret = $this->update(
			'INSERT INTO article_email_log
				(article_id, sender_id, date_sent, ip_address, event_type, assoc_type, assoc_id, from_address, recipients, cc_recipients, bcc_recipients, subject, body)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$entry->getArticleId(),
				$entry->getSenderId(),
				$entry->getDateSent() == null ? Core::getCurrentDate() : $entry->getDateSent(),
				$entry->getIPAddress() == null ? Request::getRemoteAddr() : $entry->getIPAddress(),
				$entry->getEventType(),
				$entry->getAssocType(),
				$entry->getAssocId(),
				$entry->getFrom(),
				$entry->getRecipients(),
				$entry->getCcs(),
				$entry->getBccs(),
				$entry->getSubject(),
				$entry->getBody()
			)
		);
		
		if ($ret) {
			$entry->setLogId($this->getInsertLogId());
		}
			
		return $ret;
	}
	
	/**
	 * Delete a single log entry for an article.
	 * @param $logId int
	 * @param $articleId int optional
	 */
	function deleteLogEntry($logId, $articleId = null) {
		if (isset($articleId)) {
			return $this->update(
				'DELETE FROM article_email_log WHERE log_id = ? AND article_id = ?',
				array($logId, $articleId)
			);
			
		} else {
			return $this->update(
				'DELETE FROM article_event_log WHERE log_id = ?', $logId
			);
		}
	}
	
	/**
	 * Delete all log entries for an article.
	 * @param $articleId int
	 */
	function deleteArticleLogEntries($articleId) {
		return $this->update(
			'DELETE FROM article_email_log WHERE article_id = ?', $articleId
		);
	}
	
	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('article_email_log', 'log_id');
	}
	
}

?>
