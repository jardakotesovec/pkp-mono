<?php

/**
 * @file classes/submission/PKPSubmissionFileDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionFileDAO
 * @ingroup submission
 * @see SubmissionFile
 * @see SubmissionFileDAODelegate
 *
 * @brief Abstract base class for retrieving and modifying SubmissionFile
 * objects and their decendents (e.g. MonographFile, ArtworkFile).
 *
 * This class provides access to all SubmissionFile implementations. It
 * instantiates and uses delegates internally to provide the right database
 * access behaviour depending on the type of the accessed file.
 *
 * The state classes are named after the data object plus the "DAODelegate"
 * extension, e.g. ArtworkFileDAODelegate. An internal factory method will
 * provide the correct implementation to the DAO.
 *
 * This design allows clients to access all types of files without having
 * to know about the specific file implementation unless the client really
 * wishes to access file implementation specific data. This also enables
 * us to let delegates inherit from each others to avoid code duplication
 * between DAO implementations.
 */

import('lib.pkp.classes.file.PKPFileDAO');


class PKPSubmissionFileDAO extends PKPFileDAO {
	/**
	 * @var array a private list of delegates that provide operations for
	 *  different SubmissionFile implementations.
	 */
	var $_delegates = array();

	/**
	 * Constructor
	 */
	function PKPSubmissionFileDAO() {
		parent::DAO();
	}


	//
	// Public methods
	//
	/**
	 * Retrieve a specific revision of a file.
	 * @param $fileId int
	 * @param $revision int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $submissionId int (optional) for validation
	 *  purposes only
	 */
	function &getRevision($fileId, $revision, $fileStage = null, $submissionId = null) {
		if (!($fileId && $revision)) {
			$nullVar = null;
			return $nullVar;
		}
		$revisions =& $this->_getInternally($submissionId, $fileStage, $fileId, $revision);
		return $this->_checkAndReturnRevision($revisions);
	}


	/**
	 * Retrieve the latest revision of a file.
	 * @param $fileId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $submissionId int (optional) for validation
	 *  purposes only
	 * @return SubmissionFile
	 */
	function &getLatestRevision($fileId, $fileStage = null, $submissionId = null) {
		if (!$fileId) {
			$nullVar = null;
			return $nullVar;
		}
		$revisions =& $this->_getInternally($submissionId, $fileStage, $fileId, null, null, null, null, null, true);
		return $this->_checkAndReturnRevision($revisions);
	}

	/**
	 * Retrieve a list of current revisions.
	 * @param $submissionId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $rangeInfo DBResultRange (optional)
	 * @return array a list of SubmissionFile instances
	 */
	function &getLatestRevisions($submissionId, $fileStage = null, $rangeInfo = null) {
		if (!$submissionId) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally($submissionId, $fileStage, null, null, null, null, null, null, true, $rangeInfo);
	}

	/**
	 * Retrieve all revisions of a submission file.
	 * @param $fileId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $submissionId int (optional) for validation
	 *  purposes only
	 * @param $rangeInfo DBResultRange (optional)
	 * @return array a list of SubmissionFile instances
	 */
	function &getAllRevisions($fileId, $fileStage = null, $submissionId = null, $rangeInfo = null) {
		if (!$fileId) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally($submissionId, $fileStage, $fileId, null, null, null, null, null, false, $rangeInfo);
	}

	/**
	 * Retrieve the latest revision of all files associated
	 * to a certain object.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $rangeInfo DBResultRange (optional)
	 * @return array a list of SubmissionFile instances
	 */
	function &getLatestRevisionsByAssocId($assocType, $assocId, $fileStage = null, $rangeInfo = null) {
		if (!($assocType && $assocId)) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally(null, $fileStage, null, null, $assocType, $assocId, null, null, true, $rangeInfo);
	}

	/**
	 * Retrieve all files associated to a certain object.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $rangeInfo DBResultRange (optional)
	 * @return array a list of SubmissionFile instances
	 */
	function &getAllRevisionsByAssocId($assocType, $assocId, $fileStage = null, $rangeInfo = null) {
		if (!($assocType && $assocId)) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally(null, $fileStage, null, null, $assocType, $assocId, null, null, false, $rangeInfo);
	}

	/**
	 * Get all file revisions assigned to the given review round.
	 * @param $submissionId integer
	 * @param $reviewType integer
	 * @param $round integer
	 * @return array A list of MonographFiles.
	 */
	function &getRevisionsByReviewRound($submissionId, $reviewType, $round) {
		if (!($reviewType && $round)) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally($submissionId, null, null, null, null, null, $reviewType, $round);
	}

	/**
	 * Get all files that are in the current review
	 * round, but have later revisions.
	 * @param $submissionId int
	 * @param $reviewType int
	 * @param $round int
	 * @return array A list of MonographFiles.
	 */
	function &getLatestNewRevisionsByReviewRound($submissionId, $reviewType, $round) {
		if (!($reviewType && $round)) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally($submissionId, null, null, null, null, null, $reviewType, $round, true);
	}

	/**
	 * Retrieve the current revision number for a file.
	 * @param $fileId int
	 * @return int
	 */
	function getLatestRevisionNumber($fileId) {
		assert(!is_null($fileId));

		// Retrieve the latest revision from the database.
		$result =& $this->retrieve(
			'SELECT MAX(revision) AS max_revision FROM '.$this->getSubmissionEntityName().'_files WHERE file_id = ?',
			$fileId
		);
		if($result->RecordCount() != 1) return null;

		$row =& $result->FetchRow();
		$result->Close();
		unset($result);

		$latestRevision = (int)$row['max_revision'];
		assert($latestRevision > 0);
		return $latestRevision;
	}

	/**
	 * Insert a new SubmissionFile.
	 * @param $submissionFile SubmissionFile
	 * @param $sourceFile string The place where the physical file
	 *  resides right now or the file name in the case of an upload.
	 *  The file will be copied to its canonical target location.
	 * @param $isUpload boolean set to true if the file has just been
	 *  uploaded.
	 * @return SubmissionFile
	 */
	function &insertObject(&$submissionFile, $sourceFile, $isUpload = false) {
		// Make sure that the implementation of the updated file
		// is compatible with its genre (upcast but no downcast).
		$submissionFile =& $this->_castToGenre($submissionFile);

		// Find the required target implementation and delegate.
		$targetImplementation = strtolower($this->_getFileImplementationForGenreId(
				$submissionFile->getGenreId()));
		$targetDaoDelegate =& $this->_getDaoDelegate($targetImplementation);
		$insertedFile =& $targetDaoDelegate->insertObject($submissionFile, $sourceFile, $isUpload);

		// If the updated file does not have the correct target type then we'll have
		// to retrieve it again from the database to cast it to the right type (downcast).
		if (strtolower(get_class($insertedFile)) != $targetImplementation) {
			$insertedFile =& $this->_castToDatabase($insertedFile);
		}
		return $insertedFile;
	}

	/**
	 * Update an existing submission file.
	 *
	 * NB: We implement a delete + insert strategy to deal with
	 * various casting problems (e.g. file implementation/genre
	 * may change, file path may change, etc.).
	 *
	 * @param $updatedFile SubmissionFile
	 * @param $previousFileId integer The file id before the file
	 *  was changed. Must only be given if the file id changed
	 *  so that the previous file can be identified.
	 * @param $previousRevision integer The revision before the file
	 *  was changed. Must only be given if the revision changed
	 *  so that the previous file can be identified.
	 * @return SubmissionFile The updated file. This file may be of
	 *  a different file implementation than the file passed into the
	 *  method if the genre of the file didn't fit its implementation.
	 */
	function &updateObject(&$updatedFile, $previousFileId = null, $previousRevision = null) {
		// Make sure that the implementation of the updated file
		// is compatible with its genre.
		$updatedFile =& $this->_castToGenre($updatedFile);

		// Complete the identifying data of the previous file if not given.
		$previousFileId = (int)($previousFileId ? $previousFileId : $updatedFile->getFileId());
		$previousRevision = (int)($previousRevision ? $previousRevision : $updatedFile->getRevision());

		// Retrieve the previous file.
		$previousFile =& $this->getRevision($previousFileId, $previousRevision);
		assert(is_a($previousFile, 'MonographFile'));

		// Canonicalized the implementation of the previous file.
		$previousImplementation = strtolower(get_class($previousFile));

		// Find the required target implementation and delegate.
		$targetImplementation = strtolower($this->_getFileImplementationForGenreId(
				$updatedFile->getGenreId()));
		$targetDaoDelegate =& $this->_getDaoDelegate($targetImplementation);

		// If the implementation in the database differs from the target
		// implementation then we'll have to delete + insert the object
		// to make sure that the database contains consistent data.
		if ($previousImplementation != $targetImplementation) {
			// We'll have to copy the previous file to its target
			// destination so that it is not lost when we delete the
			// previous file.
			// When the implementation (i.e. genre) changes then the
			// file locations will also change so we should not get
			// a file name clash.
			$previousFilePath = $previousFile->getFilePath();
			$targetFilePath = $updatedFile->getFilePath();
			assert($previousFilePath != $targetFilePath && !file_exists($targetFilePath));
			import('lib.pkp.classes.file.FileManager');
			FileManager::copyFile($previousFilePath, $targetFilePath);

			// We use the delegates directly to make sure
			// that we address the right implementation in the database
			// on delete and insert.
			$sourceDaoDelegate =& $this->_getDaoDelegate($previousImplementation);
			$sourceDaoDelegate->deleteObject($previousFile);
			$targetDaoDelegate->insertObject($updatedFile, $targetFilePath);
		} else {
			// If the implementation in the database does not change then we
			// can do an efficient update.
			if (!$targetDaoDelegate->updateObject($updatedFile, $previousFile)) {
				$nullVar = null;
				return $nullVar;
			}
		}

		// If the updated file does not have the correct target type then we'll have
		// to retrieve it again from the database to cast it to the right type.
		if (strtolower(get_class($updatedFile)) != $targetImplementation) {
			$updatedFile =& $this->_castToDatabase($updatedFile);
		}

		return $updatedFile;
	}

	/**
	 * Set the latest revision of a file as the latest revision
	 * of another file.
	 * @param $revisedFileId integer the revised file
	 * @param $newFileId integer the file that will become the
	 *  latest revision of the revised file.
	 * @param $submissionId integer the submission id the two files
	 *  must belong to.
	 * @param $fileStage integer the file stage the two files
	 *  must belong to.
	 * @return SubmissionFile the new revision or null if something went wrong.
	 */
	function &setAsLatestRevision($revisedFileId, $newFileId, $submissionId, $fileStage) {
		$revisedFileId = (int)$revisedFileId;
		$newFileId = (int)$newFileId;
		$submissionId = (int)$submissionId;
		$fileStage = (int)$fileStage;

		// Check whether the two files are already revisions of each other.
		$nullVar = null;
		if ($revisedFileId == $newFileId) return $nullVar;

		// Retrieve the latest revisions of the two submission files.
		$revisedFile =& $this->getLatestRevision($revisedFileId, $fileStage, $submissionId);
		$newFile =& $this->getLatestRevision($newFileId, $fileStage, $submissionId);
		if (!($revisedFile && $newFile)) return $nullVar;

		// Save identifying data of the changed file required for update.
		$previousFileId = $newFile->getFileId();
		$previousRevision = $newFile->getRevision();

		// Copy data over from the revised file to the new file.
		$newFile->setFileId($revisedFileId);
		$newFile->setRevision($revisedFile->getRevision()+1);
		$newFile->setGenreId($revisedFile->getGenreId());
		$newFile->setAssocType($revisedFile->getAssocType());
		$newFile->setAssocId($revisedFile->getAssocId());

		// Update the file in the database.
		return $this->updateObject($newFile, $previousFileId, $previousRevision);
	}

	/**
	 * Assign file to a review round.
	 * @param $fileId int The file to be assigned.
	 * @param $revision int The revision of the file to be assigned.
	 * @param $reviewType int The review round type.
	 * @param $round int The review round number.
	 * @param $submissionId int The submission id of the file.
	 */
	function assignRevisionToReviewRound($fileId, $revision, $reviewType, $round, $submissionId) {
		if (!is_numeric($fileId) || !is_numeric($revision)) fatalError('Invalid file!');
		return $this->update('INSERT INTO review_round_files
				('.$this->getSubmissionEntityName().'_id, review_type, round, file_id, revision)
				VALUES (?, ?, ?, ?, ?)',
				array((int)$submissionId, (int)$reviewType, (int)$round, (int)$fileId, (int)$revision));
	}

	/**
	 * Delete a specific revision of a submission file.
	 * @param $submissionFile SubmissionFile
	 * @return integer the number of deleted file revisions
	 */
	function deleteRevision(&$submissionFile) {
		return $this->deleteRevisionById($submissionFile->getFileId(), $submissionFile->getRevision(), $submissionFile->getFileStage(), $submissionFile->getSubmissionId());
	}

	/**
	 * Delete a specific revision of a submission file by id.
	 * @param $fileId int
	 * @param $revision int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $submissionId int (optional) for validation
	 *  purposes only
	 * @return integer the number of deleted file revisions
	 */
	function deleteRevisionById($fileId, $revision, $fileStage = null, $submissionId = null) {
		return $this->_deleteInternally($submissionId, $fileStage, $fileId, $revision);
	}

	/**
	 * Delete the latest revision of a submission file by id.
	 * @param $fileId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $submissionId int (optional) for validation
	 *  purposes only
	 * @return integer the number of deleted file revisions
	 */
	function deleteLatestRevisionById($fileId, $fileStage= null, $submissionId = null) {
		return $this->_deleteInternally($submissionId, $fileStage, $fileId, null, null, null, null, null, true);
	}

	/**
	 * Delete all revisions of a file, optionally
	 * restricted to a given file stage.
	 * @param $fileId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @param $submissionId int (optional) for validation
	 *  purposes only
	 * @return integer the number of deleted file revisions
	 */
	function deleteAllRevisionsById($fileId, $fileStage = null, $submissionId = null) {
		return $this->_deleteInternally($submissionId, $fileStage, $fileId);
	}

	/**
	 * Delete all revisions of all files of a submission,
	 * optionally restricted to a given file stage.
	 * @param $submissionId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @return integer the number of deleted file revisions
	 */
	function deleteAllRevisionsBySubmissionId($submissionId, $fileStage = null) {
		return $this->_deleteInternally($submissionId, $fileStage);
	}

	/**
	 * Retrieve all files associated to a certain object.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $fileStage int (optional) further restricts
	 *  the selection to a given file stage.
	 * @return integer the number of deleted file revisions
	 */
	function deleteAllRevisionsByAssocId($assocType, $assocId, $fileStage = null) {
		return $this->_deleteInternally(null, $fileStage, null, null, $assocType, $assocId);
	}

	/**
	 * Remove all file assignements for the given review round.
	 * @param $reviewType int The review round type.
	 * @param $round int The review round number.
	 * @param $submissionId int The submission id of
	 *  the file.
	 */
	function deleteAllRevisionsByReviewRound($submissionId, $reviewType, $round) {
		// Remove currently assigned review files.
		$returner = $this->update('DELETE FROM review_round_files
				WHERE '.$this->getSubmissionEntityName().'_id = ? AND review_type = ? AND round = ?',
				array((int)$submissionId, (int)$reviewType, (int)$round));
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @param $genreId integer The genre is required to identify the right
	 *  file implementation.
	 * @return SubmissionFile
	 */
	function &newDataObjectByGenreId($genreId) {
		// Identify the delegate.
		$daoDelegate =& $this->_getDaoDelegateForGenreId($genreId);

		// Instantiate and return the object.
		$newSubmissionFile =& $daoDelegate->newDataObject();
		return $newSubmissionFile;
	}


	//
	// Abstract template methods to be implemented by subclasses.
	//
	/**
	 * Return the name of the base submission entity
	 * (i.e. 'monograph', 'paper', 'article', etc.)
	 * @return string
	 */
	function getSubmissionEntityName() {
		assert(false);
	}

	/**
	 * Return the available delegates mapped by lower
	 * case class names.
	 * @return array a list of fully qualified class names
	 *  indexed by the lower case class name of the file
	 *  implementation they serve.
	 *  NB: Be careful to order class names such that they
	 *  can be called in the given order to delete files
	 *  without offending foreign key constraints, i.e.
	 *  place the sub-classes before the super-classes.
	 */
	function getDelegateClassNames() {
		assert(false);
	}

	/**
	 * Return the mapping of genre categories to the lower
	 * case class name of file implementation.
	 * @return array a list of lower case class names of
	 *  file implementations.
	 */
	function getGenreCategoryMapping() {
		assert(false);
	}

	/**
	 * Return the basic join over all file class tables.
	 * @return string
	 */
	function baseQueryForFileSelection() {
		assert(false);
	}


	//
	// Protected helper methods
	//
	/**
	 * Internal function to return a SubmissionFile object from a row.
	 * @param $row array
	 * @param $fileImplementation string
	 * @return SubmissionFile
	 */
	function &fromRow(&$row, $fileImplementation) {
		// Identify the delegate.
		$daoDelegate =& $this->_getDaoDelegate($fileImplementation); /* @var $daoDelegate SubmissionFileDAODelegate */

		// Let the DAO delegate instantiate the file implementation.
		return $daoDelegate->fromRow($row);
	}


	//
	// Private helper methods
	//
	/**
	 * Map a genre to the corresponding file implementation.
	 * @param $genreId integer
	 * @return string The class name of the file implementation.
	 */
	function &_getFileImplementationForGenreId($genreId) {
		static $genreCache = array();

		if (!isset($genreCache[$genreId])) {
			// We have to instantiate the genre to find out about
			// its category.
			$genreDao =& DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */
			$genre =& $genreDao->getById($genreId);

			// Identify the file implementation.
			$genreMapping = $this->getGenreCategoryMapping();
			assert(isset($genreMapping[$genre->getCategory()]));
			$genreCache[$genreId] = $genreMapping[$genre->getCategory()];
		}

		return $genreCache[$genreId];
	}

	/**
	 * Instantiates an approprate SubmissionFileDAODelegate
	 * based on the given genre identifier.
	 * @param $genreId integer
	 * @return SubmissionFileDAODelegate
	 */
	function &_getDaoDelegateForGenreId($genreId) {
		// Find the required file implementation.
		$fileImplementation = $this->_getFileImplementationForGenreId($genreId);

		// Return the DAO delegate.
		return $this->_getDaoDelegate($fileImplementation);
	}

	/**
	 * Instantiates an appropriate SubmissionFileDAODelegate
	 * based on the given SubmissionFile.
	 * @param $object SubmissionFile
	 * @return SubmissionFileDAODelegate
	 */
	function &_getDaoDelegateForObject(&$object) {
		return $this->_getDaoDelegate(get_class($object));
	}

	/**
	 * Return the requested SubmissionFileDAODelegate.
	 * @param $fileImplementation string the class name of
	 *  a file implementation that the requested delegate
	 *  should serve.
	 * @return SubmissionFileDAODelegate
	 */
	function &_getDaoDelegate($fileImplementation) {
		// Normalize the file implementation name.
		$fileImplementation = strtolower($fileImplementation);

		// Did we already instantiate the requested delegate?
		if (!isset($this->_delegates[$fileImplementation])) {
			// Instantiate the requested delegate.
			$delegateClasses = $this->getDelegateClassNames();
			assert(isset($delegateClasses[$fileImplementation]));
			$delegateClass = $delegateClasses[$fileImplementation];
			$this->_delegates[$fileImplementation] =& instantiate($delegateClass, 'SubmissionFileDAODelegate', null, null, $this);
		}

		// Return the delegate.
		return $this->_delegates[$fileImplementation];
	}

	/**
	 * Private method to retrieve submission file revisions
	 * according to the given filters.
	 * @param $submissionId integer
	 * @param $fileStage integer
	 * @param $fileId integer
	 * @param $revision integer
	 * @param $assocType integer
	 * @param $assocId integer
	 * @param $reviewType integer
	 * @param $round integer
	 * @param $latestOnly boolean
	 * @param $rangeInfo DBResultRange
	 * @return array a list of SubmissionFile instances
	 */
	function &_getInternally($submissionId = null, $fileStage = null, $fileId = null, $revision = null,
			$assocType = null, $assocId = null, $reviewType = null, $round = null, $latestOnly = false, $rangeInfo = null) {

		// Sanitize parameters.
		$latestOnly = (boolean)$latestOnly;
		if (!is_null($rangeInfo)) assert(is_a($rangeInfo, 'DBResultRange'));

		// Retrieve the base query.
		$sql = $this->baseQueryForFileSelection($latestOnly);

		// Add the revision round file join if a revision round
		// filter was requested.
		$submissionEntity = $this->getSubmissionEntityName();
		if ($reviewType) {
			$sql .= 'INNER JOIN review_round_files rrf
					ON sf.'.$submissionEntity.'_id = rrf.'.$submissionEntity.'_id
					AND sf.file_id = rrf.file_id
					AND sf.revision '.($latestOnly ? '>' : '=').' rrf.revision ';
		}

		// Filter the query.
		list($filterClause, $params) = $this->_buildFileSelectionFilter(
				$submissionId, $fileStage, $fileId, $revision,
				$assocType, $assocId, $reviewType, $round);

		// Did the user request all or only the latest revision?
		if ($latestOnly) {
			// Filter the latest revision of each file.
			// NB: We have to do this in the SQL for paging to work
			// correctly. We use a partial cartesian join here to
			// maintain MySQL 3.23 backwards compatibility. This
			// should be ok as we usually only have few revisions per
			// file.
			$sql .= 'LEFT JOIN '.$submissionEntity.'_files sf2 ON sf.file_id = sf2.file_id AND sf.revision < sf2.revision
			         WHERE sf2.revision IS NULL AND '.$filterClause;
		} else {
			$sql .= 'WHERE '.$filterClause;
		}

		// Order the query.
		$sql .= ' ORDER BY sf.'.$submissionEntity.'_id ASC, sf.file_stage ASC, sf.file_id ASC, sf.revision DESC';

		// Execute the query.
		if ($rangeInfo) {
			$result =& $this->retrieveRange($sql, $params, $rangeInfo);
		} else {
			$result =& $this->retrieve($sql, $params);
		}

		// Build the result array.
		$submissionFiles = array();
		while (!$result->EOF) {
			// Retrieve the next result row.
			$row =& $result->GetRowAssoc(false);

			// Construct a combined id from file id and revision
			// that uniquely identifies the file.
			$idAndRevision = $row['monograph_file_id'].'-'.$row['monograph_revision'];

			// Check for duplicates.
			assert(!isset($submissionFiles[$idAndRevision]));

			// Instantiate the file and add it to the
			// result array with a unique key.
			$submissionFiles[$idAndRevision] =& $this->fromRow($row);

			// Move the query cursor to the next record.
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $submissionFiles;
	}

	/**
	 * Private method to delete submission file revisions
	 * according to the given filters.
	 * @param $submissionId integer
	 * @param $fileStage integer
	 * @param $fileId integer
	 * @param $revision integer
	 * @param $assocType integer
	 * @param $assocId integer
	 * @param $reviewType integer
	 * @param $round integer
	 * @param $latestOnly boolean
	 * @return boolean|integer Returns boolean false if an error occurs, otherwise the number
	 *  of deleted files.
	 */
	function _deleteInternally($submissionId = null, $fileStage = null, $fileId = null, $revision = null,
			$assocType = null, $assocId = null, $reviewType = null, $round = null, $latestOnly = false) {

		// Identify all matched files.
		$deletedFiles =& $this->_getInternally($submissionId, $fileStage, $fileId, $revision,
				$assocType, $assocId, $reviewType, $round, $latestOnly);
		if (empty($deletedFiles)) return 0;

		$filterClause = '';
		$conjunction = '';
		$params = array();
		foreach($deletedFiles as $deletedFile) { /* @var $deletedFile SubmissionFile */
			// Delete file in the database.
			// NB: We cannot safely bulk-delete because MySQL 3.23
			// does not support multi-column IN-clauses. Same is true
			// for multi-table access or subselects in the DELETE
			// statement. And having a long (... AND ...) OR (...)
			// clause could hit length limitations.
			$daoDelegate =& $this->_getDaoDelegateForObject($deletedFile);
			if (!$daoDelegate->deleteObject($deletedFile)) return false;
		}

		// Return the number of deleted files.
		return count($deletedFiles);
	}

	/**
	 * Build an SQL where clause to select
	 * submissions based on the given filter information.
	 * @param $submissionId integer
	 * @param $fileStage integer
	 * @param $fileId integer
	 * @param $revision integer
	 * @param $assocType integer
	 * @param $assocId integer
	 * @param $reviewType integer
	 * @param $round integer
	 * @return array an array that contains the generated SQL
	 *  filter clause and the corresponding parameters.
	 */
	function _buildFileSelectionFilter($submissionId, $fileStage,
			$fileId, $revision, $assocType, $assocId, $reviewType, $round) {

		// Make sure that at least one entity filter has been set.
		assert((int)$submissionId || (int)$fileId || (int)$assocId);

		// Both, assoc type and id, must be set (or unset) together.
		assert(((int)$assocType && (int)$assocId) || !((int)$assocType || (int)$assocId));

		// Both, review type and round, must be set (or unset) together.
		assert(((int)$reviewType && (int)$round) || !((int)$reviewType || (int)$round));

		// Collect the filtered columns and ids in
		// an array for consistent handling.
		$submissionEntity = $this->getSubmissionEntityName();
		$filters = array(
			'sf.'.$submissionEntity.'_id' => $submissionId,
			'sf.file_stage' => $fileStage,
			'sf.file_id' => $fileId,
			'sf.revision' => $revision,
			'sf.assoc_type' => $assocType,
			'sf.assoc_id' => $assocId,
			'rrf.review_type' => $reviewType,
			'rrf.round' => $round
		);

		// Build and return a SQL where clause and a parameter
		// array.
		$filterClause = '';
		$params = array();
		$conjunction = '';
		foreach($filters as $filteredColumn => $filteredId) {
			if ($filteredId) {
				$filterClause .= $conjunction.' '.$filteredColumn.' = ?';
				$conjunction = ' AND';
				$params[] = (int)$filteredId;
			}
		}
		return array($filterClause, $params);
	}

	/**
	 * Make sure that the genre of the file and its file
	 * implementation are compatible.
	 *
	 * NB: In the case of a downcast this means that not all data in the
	 * object will be saved to the database. It is the UI's responsibility
	 * to inform users about potential loss of data if they change to
	 * a genre that permits less meta-data than the prior genre!
	 *
	 * @param $submissionFile SubmissionFile
	 * @return SubmissionFile The same file in a compatible implementation.
	 */
	function &_castToGenre(&$submissionFile) {
		// Find the required target implementation.
		$targetImplementation = strtolower($this->_getFileImplementationForGenreId(
				$submissionFile->getGenreId()));

		// If the current implementation of the updated object
		// differs from the target implementation then we'll
		// have to cast the object.
		if (!is_a($submissionFile, $targetImplementation)) {
			// The updated file has to be upcast by manually
			// instantiating the target object and copying data
			// to the target.
			$targetDaoDelegate =& $this->_getDaoDelegate($targetImplementation);
			$targetFile =& $targetDaoDelegate->newDataObject();
			$targetFile =& $submissionFile->upcastTo($targetFile);
			unset($submissionFile);
			$submissionFile =& $targetFile;
		}

		return $submissionFile;
	}

	/**
	 * Make sure that a file's implementation corresponds to the way it is
	 * saved in the database.
	 * @param $submissionFile SubmissionFile
	 * @return SubmissionFile
	 */
	function &_castToDatabase(&$submissionFile) {
		$fileId = $submissionFile->getFileId();
		$revision = $submissionFile->getRevision();
		unset($submissionFile);
		return $this->getRevision($fileId, $revision);
	}

	/**
	 * Check whether the given array contains exactly
	 * zero or one revisions and return it.
	 * @param $revisions array
	 * @return SubmissionFile
	 */
	function &_checkAndReturnRevision(&$revisions) {
		assert(count($revisions) <= 1);
		if (empty($revisions)) {
			$nullVar = null;
			return $nullVar;
		} else {
			$revision =& array_pop($revisions);
			assert(is_a($revision, 'SubmissionFile'));
			return $revision;
		}
	}
}

?>