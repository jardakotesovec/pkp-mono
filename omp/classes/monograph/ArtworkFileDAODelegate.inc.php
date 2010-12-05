<?php

/**
 * @file classes/monograph/ArtworkFileDAODelegate.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArtworkFileDAODelegate
 * @ingroup monograph
 * @see ArtworkFile
 *
 * @brief Operations for retrieving and modifying ArtworkFile objects.
 *
 * The SubmissionFileDAO will delegate to this class if it wishes
 * to access ArtworkFile classes.
 */


import('classes.monograph.ArtworkFile');
import('classes.monograph.MonographFileDAODelegate');

class ArtworkFileDAODelegate extends MonographFileDAODelegate {
	/**
	 * Constructor
	 */
	function ArtworkFileDAODelegate(&$submissionFileDao) {
		parent::SubmissionFileDAODelegate($submissionFileDao);
	}


	//
	// Public methods
	//
	/**
	 * @see SubmissionFileDAODelegate::insert()
	 * @param $artworkFile ArtworkFile
	 * @return ArtworkFile
	 */
	function &insertObject(&$artworkFile) {
		// First insert the data for the super-class.
		$artworkFile =& parent::insertObject($artworkFile);

		// Now insert the artwork-specific data.
		$submissionFileDao =& $this->getSubmissionFileDAO();
		$submissionFileDao->update(
			'INSERT INTO monograph_artwork_files
			   (file_id, revision, caption, chapter_id, contact_author, copyright_owner, copyright_owner_contact, credit, permission_file_id, permission_terms, placement)
			 VALUES
			   (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$artworkFile->getFileId(),
				$artworkFile->getRevision(),
				$artworkFile->getCaption(),
				$artworkFile->getChapterId(),
				$artworkFile->getContactAuthor(),
				$artworkFile->getCopyrightOwner(),
				$artworkFile->getCopyrightOwnerContactDetails(),
				$artworkFile->getCredit(),
				$artworkFile->getPermissionFileId(),
				$artworkFile->getPermissionTerms(),
				$artworkFile->getPlacement()
			)
		);

		return $artworkFile;
	}

	/**
	 * @see SubmissionFileDAODelegate::update()
	 * @param $artworkFile ArtworkFile
	 */
	function updateObject(&$artworkFile) {
		// Update the parent class table first.
		parent::updateObject($artworkFile);

		// Now update the artwork file table.
		$submissionFileDao =& $this->getSubmissionFileDAO();
		$submissionFileDao->update(
			'UPDATE monograph_artwork_files
				 SET
					caption = ?,
					chapter_id = ?,
					contact_author = ?,
					copyright_owner = ?,
					copyright_owner_contact = ?,
					credit = ?,
					permission_file_id = ?,
					permission_terms = ?,
					placement = ?
				WHERE file_id = ? and revision = ?',
			array(
				$artworkFile->getCaption(),
				is_null($artworkFile->getChapterId()) ? null : (int)$artworkFile->getChapterId(),
				$artworkFile->getContactAuthor(),
				$artworkFile->getCopyrightOwner(),
				$artworkFile->getCopyrightOwnerContactDetails(),
				$artworkFile->getCredit(),
				is_null($artworkFile->getPermissionFileId()) ? null : (int)$artworkFile->getPermissionFileId(),
				$artworkFile->getPermissionTerms(),
				$artworkFile->getPlacement(),
				(int)$artworkFile->getFileId(),
				(int)$artworkFile->getRevision()
			)
		);
		return true;
	}

	/**
	 * @see SubmissionFileDAODelegate::delete()
	 */
	function deleteObject($filterSql, $params, $latestOnly) {
		$submissionFileDao =& $this->getSubmissionFileDAO();
		if ($latestOnly) {
			$subSelect = 'SELECT file_id, MAX(revision)
				   FROM monograph_files'.$filterSql.' GROUP BY file_id';
		}else{
			$subSelect = 'SELECT file_id, revision
				   FROM monograph_files'.$filterSql;
		}
		return $submissionFileDao->update(
			'DELETE FROM monograph_artwork_files
			 WHERE (file_id, revision) IN ('.$subSelect.')', $params
		);
	}

	/**
	 * @see SubmissionFileDAODelegate::fromRow()
	 * @return ArtworkFile
	 */
	function &fromRow(&$row) {
		$artworkFile =& parent::fromRow($row);
		$artworkFile->setCredit($row['credit']);
		$artworkFile->setCaption($row['caption']);
		$artworkFile->setPlacement($row['placement']);
		$artworkFile->setChapterId(is_null($row['chapter_id']) ? null : (int)$row['chapter_id']);
		$artworkFile->setContactAuthor($row['contact_author']);
		$artworkFile->setCopyrightOwner($row['copyright_owner']);
		$artworkFile->setPermissionTerms($row['permission_terms']);
		$artworkFile->setPermissionFileId(is_null($row['permission_file_id']) ? null : (int)$row['permission_file_id']);
		$artworkFile->setCopyrightOwnerContactDetails($row['copyright_owner_contact']);

		return $artworkFile;
	}

	/**
	 * @see SubmissionFileDAODelegate::newDataObject()
	 * @return MonographFile
	 */
	function newDataObject() {
		return new ArtworkFile();
	}
}

?>