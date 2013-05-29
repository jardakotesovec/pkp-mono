<?php

/**
 * @file classes/search/MonographSearchDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographSearchDAO
 * @ingroup search
 * @see MonographSearch
 *
 * @brief DAO class for monograph search index.
 */



import('classes.search.MonographSearch');

class MonographSearchDAO extends DAO {
	/**
	 * Constructor
	 */
	function MonographSearchDAO() {
		parent::DAO();
	}

	/**
	 * Add a word to the keyword list (if it doesn't already exist).
	 * @param $keyword string
	 * @return int the keyword ID
	 */
	function insertKeyword($keyword) {
		static $monographSearchKeywordIds = array();
		if (isset($monographSearchKeywordIds[$keyword])) return $monographSearchKeywordIds[$keyword];
		$result = $this->retrieve(
			'SELECT keyword_id FROM submission_search_keyword_list WHERE keyword_text = ?',
			$keyword
		);
		if($result->RecordCount() == 0) {
			$result->Close();
			if ($this->update(
				'INSERT INTO submission_search_keyword_list (keyword_text) VALUES (?)',
				$keyword,
				true,
				false
			)) {
				$keywordId = $this->_getInsertId('submission_search_keyword_list', 'keyword_id');
			} else {
				$keywordId = null; // Bug #2324
			}
		} else {
			$keywordId = $result->fields[0];
			$result->Close();
		}

		$monographSearchKeywordIds[$keyword] = $keywordId;

		return $keywordId;
	}

	/**
	 * Retrieve the top results for a phrases with the given
	 * limit (default 500 results).
	 * @param $keywordId int
	 * @return array of results (associative arrays)
	 */
	function getPhraseResults($press, $phrase, $publishedFrom = null, $publishedTo = null, $type = null, $limit = 500, $cacheHours = 24) {
		import('lib.pkp.classes.db.DBRowIterator');
		if (empty($phrase)) {
			$results = false;
			return new DBRowIterator($results);
		}

		$sqlFrom = '';
		$sqlWhere = '';

		for ($i = 0, $count = count($phrase); $i < $count; $i++) {
			if (!empty($sqlFrom)) {
				$sqlFrom .= ', ';
				$sqlWhere .= ' AND ';
			}
			$sqlFrom .= 'submission_search_object_keywords o'.$i.' NATURAL JOIN submission_search_keyword_list k'.$i;
			if (strstr($phrase[$i], '%') === false) $sqlWhere .= 'k'.$i.'.keyword_text = ?';
			else $sqlWhere .= 'k'.$i.'.keyword_text LIKE ?';
			if ($i > 0) $sqlWhere .= ' AND o0.object_id = o'.$i.'.object_id AND o0.pos+'.$i.' = o'.$i.'.pos';

			$params[] = $phrase[$i];
		}

		if (!empty($type)) {
			$sqlWhere .= ' AND (o.type & ?) != 0';
			$params[] = $type;
		}

		if (!empty($press)) {
			$sqlWhere .= ' AND m.press_id = ?';
			$params[] = $press->getId();
		}

		$result = $this->retrieveCached(
			'SELECT
				o.submission_id,
				COUNT(*) AS count
			FROM
				submissions s,
				published_submissions ps,
				submission_search_objects o NATURAL JOIN ' . $sqlFrom . '
			WHERE
				s.submission_id = ps.submission_id AND o.submission_id = s.submission_id AND ' . $sqlWhere . '
			GROUP BY o.submission_id
			ORDER BY count DESC
			LIMIT ' . $limit,
			$params,
			3600 * $cacheHours // Cache for 24 hours
		);

		return new DBRowIterator($result);
	}

	/**
	 * Delete all keywords for an monograph object.
	 * @param $monographId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deleteMonographKeywords($monographId, $type = null, $assocId = null) {
		$sql = 'SELECT object_id FROM submission_search_objects WHERE submission_id = ?';
		$params = array($monographId);

		if (isset($type)) {
			$sql .= ' AND type = ?';
			$params[] = $type;
		}

		if (isset($assocId)) {
			$sql .= ' AND assoc_id = ?';
			$params[] = $assocId;
		}

		$result = $this->retrieve($sql, $params);
		while (!$result->EOF) {
			$objectId = $result->fields[0];
			$this->update('DELETE FROM submission_search_object_keywords WHERE object_id = ?', $objectId);
			$this->update('DELETE FROM submission_search_objects WHERE object_id = ?', $objectId);
			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Add an monograph object to the index (if already exists, indexed keywords are cleared).
	 * @param $monographId int
	 * @param $type int
	 * @param $assocId int
	 * @return int the object ID
	 */
	function insertObject($monographId, $type, $assocId, $keepExisting = false) {
		$result = $this->retrieve(
			'SELECT object_id FROM submission_search_objects WHERE submission_id = ? AND type = ? AND assoc_id = ?',
			array($monographId, $type, $assocId)
		);
		if ($result->RecordCount() == 0) {
			$this->update(
				'INSERT INTO submission_search_objects (submission_id, type, assoc_id) VALUES (?, ?, ?)',
				array($monographId, $type, (int) $assocId)
			);
			$objectId = $this->_getInsertId('submission_search_objects', 'object_id');

		} else {
			$objectId = $result->fields[0];
			$this->update(
				'DELETE FROM submission_search_object_keywords WHERE object_id = ?',
				$objectId
			);
		}
		$result->Close();
		unset($result);

		return $objectId;
	}

	/**
	 * Index an occurrence of a keyword in an object.s
	 * @param $objectId int
	 * @param $keyword string
	 * @param $position int
	 * @return $keywordId
	 */
	function insertObjectKeyword($objectId, $keyword, $position) {
		$keywordId = $this->insertKeyword($keyword);
		if ($keywordId === null) return null; // Bug #2324
		$this->update(
			'INSERT INTO submission_search_object_keywords (object_id, keyword_id, pos) VALUES (?, ?, ?)',
			array($objectId, $keywordId, $position)
		);
		return $keywordId;
	}
}

?>
