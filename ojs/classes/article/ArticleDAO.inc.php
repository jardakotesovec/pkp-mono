<?php

/**
 * @file classes/article/ArticleDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleDAO
 * @ingroup article
 * @see Article
 *
 * @brief Operations for retrieving and modifying Article objects.
 */

import('classes.article.Article');
import('lib.pkp.classes.submission.SubmissionDAO');

class ArticleDAO extends SubmissionDAO {
	/**
	 * Constructor.
	 */
	function ArticleDAO() {
		parent::SubmissionDAO();
	}

	/**
	 * Get a list of field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return parent::getLocaleFieldNames() + array(
			'coverPageAltText', 'showCoverPage', 'hideCoverPageToc', 'hideCoverPageAbstract', 'originalFileName', 'fileName', 'width', 'height',
		);
	}

	/**
	 * Find articles by querying article settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $journalId int optional
	 * @param $rangeInfo DBResultRange optional
	 * @return array The articles identified by setting.
	 * WARNING: This query is selectively indexed for PostgreSQL. Ensure that the
	 * settings you wish to query are specified in dbscripts/xml/indexes.xml to
	 * avoid a potentially costly query.
	 */
	function getBySetting($settingName, $settingValue, $journalId = null, $rangeInfo = null) {
		$params = $this->_getFetchParameters();
		$params[] = $settingName;

		$sql = 'SELECT s.*, ps.date_published,
				' . $this->_getFetchColumns() . '
			FROM	submissions s
				LEFT JOIN published_submissions ps ON (s.submission_id = ps.submission_id)
				' . $this->_getFetchJoins() . ' ';

		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN submission_settings sst ON a.submission_id = sst.submission_id AND sst.setting_name = ?
				WHERE	(sst.setting_value IS NULL OR sst.setting_value = \'\')';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN submission_settings sst ON s.submission_id = sst.submission_id
				WHERE	sst.setting_name = ? AND sst.setting_value = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND s.context_id = ?';
		}
		$sql .= ' ORDER BY s.context_id, s.submission_id';
		$result = $this->retrieveRange($sql, $params, $rangeInfo);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Internal function to return an Article object from a row.
	 * @param $row array
	 * @return Article
	 */
	function _fromRow($row) {
		$article = parent::_fromRow($row);

		$article->setSectionId($row['section_id']);
		$article->setSectionTitle($row['section_title']);
		$article->setSectionAbbrev($row['section_abbrev']);
		$article->setCitations($row['citations']);
		$article->setCurrentRound($row['current_round']);
		$article->setPages($row['pages']);
		$article->setFastTracked($row['fast_tracked']);
		$article->setHideAuthor($row['hide_author']);
		$article->setCommentsStatus($row['comments_status']);

		HookRegistry::call('ArticleDAO::_fromRow', array(&$article, &$row));
		return $article;
	}

	/**
	 * Return a new data object.
	 * @return Article
	 */
	function newDataObject() {
		return new Article();
	}

	/**
	 * Insert a new Article.
	 * @param $article Article
	 */
	function insertObject($article) {
		$article->stampModified();
		$this->update(
			sprintf('INSERT INTO submissions
				(locale, user_id, context_id, section_id, stage_id, language, comments_to_ed, citations, date_submitted, date_status_modified, last_modified, status, submission_progress, current_round, pages, fast_tracked, hide_author, comments_status)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, %s, %s, %s, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getLocale(),
				(int) $article->getUserId(),
				(int) $article->getContextId(),
				(int) $article->getSectionId(),
				(int) $article->getStageId(),
				$article->getLanguage(),
				$article->getCommentsToEditor(),
				$article->getCitations(),
				$article->getStatus() === null ? STATUS_QUEUED : $article->getStatus(),
				$article->getSubmissionProgress() === null ? 1 : $article->getSubmissionProgress(),
				$article->getCurrentRound() === null ? 1 : $article->getCurrentRound(),
				$article->getPages(),
				(int) $article->getFastTracked(),
				(int) $article->getHideAuthor(),
				(int) $article->getCommentsStatus()
			)
		);

		$article->setId($this->getInsertId());
		$this->updateLocaleFields($article);

		// Insert authors for this article
		$authors = $article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$authors[$i]->setSubmissionId($article->getId());
			$this->authorDao->insertObject($authors[$i]);
		}

		return $article->getId();
	}

	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateObject($article) {
		$article->stampModified();
		$this->update(
			sprintf('UPDATE submissions
				SET	locale = ?,
					user_id = ?,
					section_id = ?,
					stage_id = ?,
					language = ?,
					comments_to_ed = ?,
					citations = ?,
					date_submitted = %s,
					date_status_modified = %s,
					last_modified = %s,
					status = ?,
					submission_progress = ?,
					current_round = ?,
					pages = ?,
					fast_tracked = ?,
					hide_author = ?,
					comments_status = ?
				WHERE submission_id = ?',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getLocale(),
				(int) $article->getUserId(),
				(int) $article->getSectionId(),
				(int) $article->getStageId(),
				$article->getLanguage(),
				$article->getCommentsToEditor(),
				$article->getCitations(),
				(int) $article->getStatus(),
				(int) $article->getSubmissionProgress(),
				(int) $article->getCurrentRound(),
				$article->getPages(),
				(int) $article->getFastTracked(),
				(int) $article->getHideAuthor(),
				(int) $article->getCommentsStatus(),
				(int) $article->getId()
			)
		);

		$this->updateLocaleFields($article);

		// update authors for this article
		$authors = $article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]->getId() > 0) {
				$this->authorDao->updateObject($authors[$i]);
			} else {
				$this->authorDao->insertObject($authors[$i]);
			}
		}

		// Update author sequence numbers
		$this->authorDao->resequenceAuthors($article->getId());

		$this->flushCache();
	}

	/**
	 * Delete an article by ID.
	 * @param $articleId int
	 */
	function deleteById($articleId) {
		parent::deleteById($articleId);

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticleByArticleId($articleId);

		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalleyDao->deleteGalleysByArticle($articleId);

		$articleSearchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$articleSearchDao->deleteSubmissionKeywords($articleId);

		$suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$suppFileDao->deleteSuppFilesByArticle($articleId);

		// Delete article files -- first from the filesystem, then from the database
		import('classes.file.ArticleFileManager');
		$articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$articleFiles = $articleFileDao->getArticleFilesByArticle($articleId);

		$articleFileManager = new ArticleFileManager($articleId);
		foreach ($articleFiles as $articleFile) {
			$articleFileManager->deleteFile($articleFile->getFileId());
		}

		$articleFileDao->deleteArticleFiles($articleId);

		// Delete article citations.
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$citationDao->deleteObjectsByAssocId(ASSOC_TYPE_ARTICLE, $articleId);

		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleDeleted($articleId);
		$articleSearchIndex->articleChangesFinished();

		$this->flushCache();
	}

	/**
	 * Get the ID of the journal an article is in.
	 * @param $articleId int
	 * @return int
	 */
	function getJournalId($articleId) {
		$result = $this->retrieve(
			'SELECT context_id FROM submissions WHERE submission_id = ?', (int) $articleId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Check if the specified incomplete submission exists.
	 * @param $articleId int
	 * @param $userId int
	 * @param $journalId int
	 * @return int the submission progress
	 */
	function incompleteSubmissionExists($articleId, $userId, $journalId) {
		$result = $this->retrieve(
			'SELECT submission_progress FROM submissions WHERE submission_id = ? AND user_id = ? AND context_id = ? AND date_submitted IS NULL',
			array((int) $articleId, (int) $userId, (int) $journalId)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Change the status of the article
	 * @param $articleId int
	 * @param $status int
	 */
	function changeStatus($articleId, $status) {
		$this->update(
			'UPDATE submissions SET status = ? WHERE submission_id = ?',
			array((int) $status, (int) $articleId)
		);

		$this->flushCache();
	}

	/**
	 * Add/update an article setting.
	 * @param $articleId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string Data type of the setting.
	 * @param $isLocalized boolean
	 */
	function updateSetting($articleId, $name, $value, $type, $isLocalized = false) {
		// Check and prepare setting data.
		if ($isLocalized) {
			if (is_array($value)) {
				$values = $value;
			} else {
				// We expect localized data to come in as an array.
				assert(false);
				return;
			}
		} else {
			// Normalize non-localized data to an array so that
			// we can treat updates uniformly.
			$values = array('' => $value);
		}

		// Update setting values.
		$keyFields = array('setting_name', 'locale', 'submission_id');
		foreach ($values as $locale => $value) {
			// Locale-specific entries will be deleted when no value exists.
			// Non-localized settings will always be set.
			if ($isLocalized) {
				$this->update(
					'DELETE FROM submission_settings WHERE submission_id = ? AND setting_name = ? AND locale = ?',
					array((int) $articleId, $name, $locale)
				);
				if (empty($value)) continue;
			}

			// Convert the new value to the correct type.
			$value = $this->convertToDB($value, $type);

			// Update the database.
			$this->replace('submission_settings',
				array(
					'submission_id' => $articleId,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => $locale
				),
				$keyFields
			);
		}
		$this->flushCache();
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * article ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $articleId int An ID to be excluded from the search.
	 * @param $journalId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $articleId, $journalId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM submission_settings sst
				INNER JOIN submissions s ON sst.submission_id = s.submission_id
			WHERE sst.setting_name = ? and sst.setting_value = ? and sst.submission_id <> ? AND s.context_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $articleId,
				(int) $journalId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Removes articles from a section by section ID
	 * @param $sectionId int
	 */
	function removeArticlesFromSection($sectionId) {
		$this->update(
			'UPDATE submissions SET section_id = null WHERE section_id = ?', (int) $sectionId
		);

		$this->flushCache();
	}

	/**
	 * Delete the public IDs of all articles in a journal.
	 * @param $journalId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deleteAllPubIds($journalId, $pubIdType) {
		$journalId = (int) $journalId;
		$settingName = 'pub-id::'.$pubIdType;

		$articles = $this->getByContextId($journalId);
		while ($article = $articles->next()) {
			$this->update(
				'DELETE FROM submission_settings WHERE setting_name = ? AND submission_id = ?',
				array(
					$settingName,
					(int)$article->getId()
				)
			);
		}
		$this->flushCache();
	}

	function flushCache() {
		// Because both publishedArticles and articles are cached by
		// article ID, flush both caches on update.
		parent::flushCache();

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$cache = $publishedArticleDao->_getPublishedArticleCache();
		$cache->flush();
	}

	/**
	 * Get all unassigned submissions for a context or all contexts
	 * @param $journalId int optional the ID of the journal to query.
	 * @param $subEditorId int optional the ID of the sub editor
	 * 	whose section will be included in the results (excluding others).
	 * @return DAOResultFactory containing matching Submissions
	 */
	function getBySubEditorId($journalId = null, $subEditorId = null) {
		$params = $this->_getFetchParameters();
		$params[] = ROLE_ID_MANAGER;
		if ($subEditorId) $params[] = (int) $subEditorId;
		if ($journalId) $params[] = (int) $journalId;

		$result = $this->retrieve(
			'SELECT	s.*, ps.date_published,
				' . $this->_getFetchColumns() . '
			FROM	submissions s
				LEFT JOIN published_submissions ps ON s.submission_id = ps.submission_id
				' . $this->_getFetchJoins() . '
				LEFT JOIN stage_assignments sa ON (s.submission_id = sa.submission_id)
				LEFT JOIN user_groups g ON (sa.user_group_id = g.user_group_id AND g.role_id = ?)
				' . ($subEditorId?' JOIN section_editors se ON (se.journal_id = s.context_id AND se.user_id = ? AND se.section_id = s.section_id)':'') . '
			WHERE	s.date_submitted IS NOT NULL
				' . ($journalId?' AND s.context_id = ?':'') . '
			GROUP BY s.submission_id',
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	//
	// Protected functions
	//
	/**
	 * Return a list of extra parameters to bind to the submission fetch queries.
	 * @return array
	 */
	protected function _getFetchParameters() {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		return array(
			'title', $primaryLocale,
			'title', $locale,
			'abbrev', $primaryLocale,
			'abbrev', $locale,
		);
	}

	/**
	 * Return a SQL snippet of extra columns to fetch during submission fetch queries.
	 * @return string
	 */
	protected function _getFetchColumns() {
		return 'COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
			COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev';
	}

	/**
	 * Return a SQL snippet of extra joins to include during fetch queries.
	 * @return string
	 */
	protected function _getFetchJoins() {
		return 'JOIN sections se ON se.section_id = s.section_id
			LEFT JOIN section_settings stpl ON (se.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
			LEFT JOIN section_settings stl ON (se.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
			LEFT JOIN section_settings sapl ON (se.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
			LEFT JOIN section_settings sal ON (se.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)';
	}
}

?>
