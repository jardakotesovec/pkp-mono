<?php

/**
 * ArticleFileDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for ArticleFile DAO.
 * Operations for retrieving and modifying ArticleFile objects.
 *
 * $Id$
 */

class ArticleFileDAO extends DAO {


	/**
	 * Constructor.
	 */
	function ArticleFileDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve an article by ID.
	 * @param $articleId int
	 * @return Article
	 */
	function &getArticleFile($fileId) {
		$result = &$this->retrieve(
			'SELECT a.* FROM article_files a WHERE file_id = ?', $fileId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnArticleFileFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return an ArticleFile object from a row.
	 * @param $row array
	 * @return Article
	 */
	function &_returnArticleFileFromRow(&$row) {
		$articleFile = &new ArticleFile();
		$articleFile->setFileId($row['file_id']);
		$articleFile->setArticleId($row['article_id']);
		$articleFile->setFileName($row['file_name']);
		$articleFile->setFileType($row['file_type']);
		$articleFile->setStatus($row['status']);
		$articleFile->setDateUploaded($row['date_uploaded']);
		$articleFile->setDateModified($row['date_modified']);
		return $articleFile;
	}

	/**
	 * Insert a new ArticleFile.
	 * @param $articleFile ArticleFile
	 */	
	function insertArticleFile(&$articleFile) {
		$this->update(
			'INSERT INTO article_files
				(article_id, file_name, file_type, status, date_uploaded, date_modified)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$articleFile->getArticleId(),
				$articleFile->getFileName(),
				$articleFile->getFileType(),
				$articleFile->getStatus(),
				$articleFile->getDateUploaded(),
				$articleFile->getDateModified()
			)
		);
		
		$articleFile->setFileId($this->getInsertArticleFileId());
		
	}
	
	/**
	 * Update an existing article file.
	 * @param $article ArticleFile
	 */
	function updateArticleFile(&$articleFile) {
		$this->update(
			'UPDATE article_files
				SET
					article_id = ?,
					file_name = ?,
					file_type = ?,
					status = ?,
					date_uploaded = ?,
					date_modified = ?
				WHERE file_id = ?',
			array(
				$articleFile->getArticleId(),
				$articleFile->getFileName(),
				$articleFile->getFileType(),
				$articleFile->getStatus(),
				$articleFile->getDateUploaded(),
				$articleFile->getDateModified()
			)
		);
		
	}
	
	/**
	 * Delete an article file.
	 * @param $article ArticleFile
	 */
	function deleteArticle(&$articleFile) {
		return $this->deleteArticleById($articleFile->getArticleFileId());
	}
	
	/**
	 * Delete an article file by ID.
	 * @param $articleId int
	 */
	function deleteArticleById($fileId) {
		return $this->update(
			'DELETE FROM article_files WHERE file_id = ?', $fileId
		);
	}
	
	/**
	 * Get the ID of the last inserted article file.
	 * @return int
	 */
	function getInsertArticleFileId() {
		return $this->getInsertId('article_files', 'file_id');
	}
	
}

?>
