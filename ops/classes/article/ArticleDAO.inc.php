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

class ArticleDAO extends DAO {
	var $authorDao;

	var $cache;

	function _cacheMiss($cache, $id) {
		$article = $this->getById($id, null, false);
		$cache->setCache($id, $article);
		return $article;
	}

	function _getCache() {
		if (!isset($this->cache)) {
			$cacheManager = CacheManager::getManager();
			$this->cache = $cacheManager->getObjectCache('articles', 0, array($this, '_cacheMiss'));
		}
		return $this->cache;
	}

	/**
	 * Constructor.
	 */
	function ArticleDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
	}

	/**
	 * Get a list of field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array(
			'title', 'cleanTitle', 'abstract', 'coverPageAltText', 'showCoverPage', 'hideCoverPageToc', 'hideCoverPageAbstract', 'originalFileName', 'fileName', 'width', 'height',
			'discipline', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'source', 'rights', 'sponsor', 'prefix', 'subtitle',
		);
	}

	/**
	 * Get a list of additional fields that do not have
	 * dedicated accessors.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		$additionalFields = parent::getAdditionalFieldNames();
		// FIXME: Move this to a PID plug-in.
		$additionalFields[] = 'pub-id::publisher-id';
		return $additionalFields;
	}

	/**
	 * Update the settings for this object
	 * @param $article object
	 */
	function updateLocaleFields($article) {
		$this->updateDataObjectSettings('article_settings', $article, array(
			'article_id' => $article->getId()
		));
	}

	/**
	 * Retrieve an article by ID.
	 * @param $articleId int
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return Article
	 */
	function getById($articleId, $journalId = null, $useCache = false) {
		if ($useCache) {
			$cache = $this->_getCache();
			$returner = $cache->get($articleId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$articleId
		);
		$sql = 'SELECT	a.*, pa.date_published,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN published_articles pa ON (a.article_id = pa.article_id)
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.article_id = ?';
		if ($journalId !== null) {
			$sql .= ' AND a.journal_id = ?';
			$params[] = $journalId;
		}

		$result = $this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_returnArticleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}


	/**
	 * Find articles by querying article settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $journalId int optional
	 * @param $rangeInfo DBResultRange optional
	 * @return array The articles identified by setting.
	 */
	function getBySetting($settingName, $settingValue, $journalId = null, $rangeInfo = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$settingName
		);

		$sql = 'SELECT a.*, pm.date_published,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN published_articles pa ON (a.article_id = pa.article_id)
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?) ';
		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN article_settings ast ON a.article_id = ast.article_id AND ast.setting_name = ?
				WHERE	(ast.setting_value IS NULL OR ast.setting_value = "")';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN article_settings ast ON a.article_id = ast.article_id
				WHERE	ast.setting_name = ? AND ast.setting_value = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND a.journal_id = ?';
		}
		$sql .= ' ORDER BY a.journal_id, a.article_id';
		$result = $this->retrieveRange($sql, $params, $rangeInfo);

		return new DAOResultFactory($result, $this, '_returnArticleFromRow');
	}

	/**
	 * Internal function to return an Article object from a row.
	 * @param $row array
	 * @return Article
	 */
	function _returnArticleFromRow($row) {
		$article = $this->newDataObject();
		$this->_articleFromRow($article, $row);
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
	 * Internal function to fill in the passed article object from the row.
	 * @param $article Article output article
	 * @param $row array input row
	 */
	function _articleFromRow($article, $row) {
		$article->setId($row['article_id']);
		$article->setLocale($row['locale']);
		$article->setUserId($row['user_id']);
		$article->setJournalId($row['journal_id']);
		$article->setSectionId($row['section_id']);
		$article->setSectionTitle($row['section_title']);
		$article->setSectionAbbrev($row['section_abbrev']);
		$article->setLanguage($row['language']);
		$article->setCommentsToEditor($row['comments_to_ed']);
		$article->setCitations($row['citations']);
		$article->setDateSubmitted($this->datetimeFromDB($row['date_submitted']));
		$article->setDateStatusModified($this->datetimeFromDB($row['date_status_modified']));
		$article->setDatePublished($this->datetimeFromDB($row['date_published']));
		$article->setLastModified($this->datetimeFromDB($row['last_modified']));
		$article->setStatus($row['status']);
		$article->setSubmissionProgress($row['submission_progress']);
		$article->setCurrentRound($row['current_round']);
		$article->setSubmissionFileId($row['submission_file_id']);
		$article->setRevisedFileId($row['revised_file_id']);
		$article->setReviewFileId($row['review_file_id']);
		$article->setEditorFileId($row['editor_file_id']);
		$article->setPages($row['pages']);
		$article->setFastTracked($row['fast_tracked']);
		$article->setHideAuthor($row['hide_author']);
		$article->setCommentsStatus($row['comments_status']);

		$this->getDataObjectSettings('article_settings', 'article_id', $row['article_id'], $article);

		HookRegistry::call('ArticleDAO::_returnArticleFromRow', array(&$article, &$row));

	}

	/**
	 * Insert a new Article.
	 * @param $article Article
	 */
	function insertObject($article) {
		$article->stampModified();
		$this->update(
			sprintf('INSERT INTO articles
				(locale, user_id, journal_id, section_id, language, comments_to_ed, citations, date_submitted, date_status_modified, last_modified, status, submission_progress, current_round, submission_file_id, revised_file_id, review_file_id, editor_file_id, pages, fast_tracked, hide_author, comments_status)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, %s, %s, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getLocale(),
				$article->getUserId(),
				$article->getJournalId(),
				$article->getSectionId(),
				$article->getLanguage(),
				$article->getCommentsToEditor(),
				$article->getCitations(),
				$article->getStatus() === null ? STATUS_QUEUED : $article->getStatus(),
				$article->getSubmissionProgress() === null ? 1 : $article->getSubmissionProgress(),
				$article->getCurrentRound() === null ? 1 : $article->getCurrentRound(),
				$this->nullOrInt($article->getSubmissionFileId()),
				$this->nullOrInt($article->getRevisedFileId()),
				$this->nullOrInt($article->getReviewFileId()),
				$this->nullOrInt($article->getEditorFileId()),
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
			sprintf('UPDATE articles
				SET	locale = ?,
					user_id = ?,
					section_id = ?,
					language = ?,
					comments_to_ed = ?,
					citations = ?,
					date_submitted = %s,
					date_status_modified = %s,
					last_modified = %s,
					status = ?,
					submission_progress = ?,
					current_round = ?,
					submission_file_id = ?,
					revised_file_id = ?,
					review_file_id = ?,
					editor_file_id = ?,
					pages = ?,
					fast_tracked = ?,
					hide_author = ?,
					comments_status = ?
				WHERE article_id = ?',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getLocale(),
				(int) $article->getUserId(),
				(int) $article->getSectionId(),
				$article->getLanguage(),
				$article->getCommentsToEditor(),
				$article->getCitations(),
				(int) $article->getStatus(),
				(int) $article->getSubmissionProgress(),
				(int) $article->getCurrentRound(),
				$this->nullOrInt($article->getSubmissionFileId()),
				$this->nullOrInt($article->getRevisedFileId()),
				$this->nullOrInt($article->getReviewFileId()),
				$this->nullOrInt($article->getEditorFileId()),
				$article->getPages(),
				(int) $article->getFastTracked(),
				(int) $article->getHideAuthor(),
				(int) $article->getCommentsStatus(),
				$article->getId()
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
	 * Delete an article.
	 * @param $article Article
	 */
	function deleteObject($article) {
		return $this->deleteById($article->getId());
	}

	/**
	 * Delete an article by ID.
	 * @param $articleId int
	 */
	function deleteById($articleId) {
		$this->authorDao->deleteAuthorsBySubmission($articleId);

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticleByArticleId($articleId);

		$commentDao = DAORegistry::getDAO('CommentDAO');
		$commentDao->deleteBySubmissionId($articleId);

		$noteDao = DAORegistry::getDAO('NoteDAO');
		$noteDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);

		$sectionEditorSubmissionDao = DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmissionDao->deleteDecisionsByArticle($articleId);
		$sectionEditorSubmissionDao->deleteReviewRoundsByArticle($articleId);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->deleteBySubmissionId($articleId);

		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignmentDao->deleteEditAssignmentsByArticle($articleId);

		// Delete copyedit, layout, and proofread signoffs
		$signoffDao = DAORegistry::getDAO('SignoffDAO');
		$copyedInitialSignoffs = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $articleId);
		$copyedAuthorSignoffs = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $articleId);
		$copyedFinalSignoffs = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $articleId);
		$layoutSignoffs = $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		$proofreadAuthorSignoffs = $signoffDao->getBySymbolic('SIGNOFF_PROOFREADING_AUTHOR', ASSOC_TYPE_ARTICLE, $articleId);
		$proofreadProofreaderSignoffs = $signoffDao->getBySymbolic('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
		$proofreadLayoutSignoffs = $signoffDao->getBySymbolic('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		$signoffs = array($copyedInitialSignoffs, $copyedAuthorSignoffs, $copyedFinalSignoffs, $layoutSignoffs,
						$proofreadAuthorSignoffs, $proofreadProofreaderSignoffs, $proofreadLayoutSignoffs);
		foreach ($signoffs as $signoff) {
			$signoffDao->deleteObject($signoff);
		}

		$articleCommentDao = DAORegistry::getDAO('ArticleCommentDAO');
		$articleCommentDao->deleteObject($articleId);

		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalleyDao->deleteGalleysByArticle($articleId);

		$articleSearchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$articleSearchDao->deleteArticleKeywords($articleId);

		$articleEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$articleEventLogDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);

		$articleEmailLogDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
		$articleEmailLogDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);

		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);

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

		$this->update('DELETE FROM article_settings WHERE article_id = ?', $articleId);
		$this->update('DELETE FROM articles WHERE article_id = ?', $articleId);

		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleDeleted($articleId);
		$articleSearchIndex->articleChangesFinished();

		$this->flushCache();
	}

	/**
	 * Get all articles for a journal (or all articles in the system).
	 * @param $journalId int
	 * @return DAOResultFactory containing matching Articles
	 */
	function getByJournalId($journalId = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale
		);
		if ($journalId !== null) $params[] = (int) $journalId;

		$result = $this->retrieve(
			'SELECT	a.*, pa.published_articles,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN published_articles pa ON (a.article_id = pa.article_id)
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			' . ($journalId !== null ? 'WHERE a.journal_id = ?' : ''),
			$params
		);

		return new DAOResultFactory($result, $this, '_returnArticleFromRow');
	}

	/**
	 * Get all articles by user ID.
	 * @param $journalId int
	 * @return DAOResultFactory containing matching Articles
	 */
	function getByUserId($userId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$result = $this->retrieve(
			'SELECT	a.*, pa.date_published,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN published_articles pa ON (a.article_id = pa.article_id)
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE a.journal_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				(int) $userId
			)
		);

		return new DAOResultFactory($result, $this, '_returnArticleFromRow');
	}

	/**
	 * Delete all articles by journal ID.
	 * @param $journalId int
	 */
	function deleteByJournalId($journalId) {
		$articles = $this->getByJournalId($journalId);
		while ($article = $articles->next()) {
			$this->deleteById($article->getId());
		}
	}

	/**
	 * Get the ID of the journal an article is in.
	 * @param $articleId int
	 * @return int
	 */
	function getJournalId($articleId) {
		$result = $this->retrieve(
			'SELECT journal_id FROM articles WHERE article_id = ?', (int) $articleId
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
			'SELECT submission_progress FROM articles WHERE article_id = ? AND user_id = ? AND journal_id = ? AND date_submitted IS NULL',
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
			'UPDATE articles SET status = ? WHERE article_id = ?',
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
		$keyFields = array('setting_name', 'locale', 'article_id');
		foreach ($values as $locale => $value) {
			// Locale-specific entries will be deleted when no value exists.
			// Non-localized settings will always be set.
			if ($isLocalized) {
				$this->update(
					'DELETE FROM article_settings WHERE article_id = ? AND setting_name = ? AND locale = ?',
					array((int) $articleId, $name, $locale)
				);
				if (empty($value)) continue;
			}

			// Convert the new value to the correct type.
			$value = $this->convertToDB($value, $type);

			// Update the database.
			$this->replace('article_settings',
				array(
					'article_id' => $articleId,
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
	 * Change the public ID of an article.
	 * @param $articleId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function changePubId($articleId, $pubIdType, $pubId) {
		$this->updateSetting($articleId, 'pub-id::'.$pubIdType, $pubId, 'string');
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
			FROM article_settings ast
				INNER JOIN articles a ON ast.article_id = a.article_id
			WHERE ast.setting_name = ? and ast.setting_value = ? and ast.article_id <> ? AND a.journal_id = ?',
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
			'UPDATE articles SET section_id = null WHERE section_id = ?', (int) $sectionId
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

		$articles = $this->getByJournalId($journalId);
		while ($article = $articles->next()) {
			$this->update(
				'DELETE FROM article_settings WHERE setting_name = ? AND article_id = ?',
				array(
					$settingName,
					(int)$article->getId()
				)
			);
		}
		$this->flushCache();
	}

	/**
	 * Get the ID of the last inserted article.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('articles', 'article_id');
	}

	function flushCache() {
		// Because both publishedArticles and articles are cached by
		// article ID, flush both caches on update.
		$cache = $this->_getCache();
		$cache->flush();

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$cache = $publishedArticleDao->_getPublishedArticleCache();
		$cache->flush();
	}

	/**
	 * Get all unassigned submissions for a context or all contexts
	 * @param $pressId int optional the ID of the press to query.
	 * @param $subEditorId int optional the ID of the sub editor
	 * 	whose section will be included in the results (excluding others).
	 * @return DAOResultFactory containing matching Submissions
	 */
	function getBySubEditorId($journalId = null, $subEditorId = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$params = array(
			'title', $primaryLocale, // Series title
			'title', $locale, // Series title
			'abbrev', $primaryLocale, // Series abbreviation
			'abbrev', $locale, // Series abbreviation
			(int) ROLE_ID_MANAGER
		);
		if ($subEditorId) $params[] = (int) $subEditorId;
		if ($pressId) $params[] = (int) $pressId;

		$result = $this->retrieve(
			'SELECT	a.*, pa.date_published
			FROM	articles a
				LEFT JOIN published_articles pa ON a.article_id = pa.article_id
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
				LEFT JOIN stage_assignments sa ON (a.article_id = sa.submission_id)
				LEFT JOIN user_groups g ON (sa.user_group_id = g.user_group_id AND g.role_id = ?)
				' . ($subEditorId?' JOIN section_editors se ON (se.press_id = a.journal_id AND se.user_id = ? AND se.section_id = a.section_id)':'') . '
			WHERE	a.date_submitted IS NOT NULL
				' . ($pressId?' AND a.journal_id = ?':'') . '
			GROUP BY a.article_id',
			$params
		);

		return new DAOResultFactory($result, $this, '_returnArticleFromRow');
	}

}

?>
