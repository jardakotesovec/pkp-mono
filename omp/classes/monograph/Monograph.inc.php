<?php

/**
 * @defgroup monograph
 */

/**
 * @file classes/monograph/Monograph.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Monograph
 * @ingroup monograph
 * @see MonographDAO
 *
 * @brief Class for a Monograph.
 */


// Submission status constants
define('STATUS_ARCHIVED', 0);
define('STATUS_QUEUED', 1);
define('STATUS_PUBLISHED', 3);
define('STATUS_DECLINED', 4);

define ('STATUS_QUEUED_UNASSIGNED', 5);
define ('STATUS_QUEUED_REVIEW', 6);
define ('STATUS_QUEUED_EDITING', 7);
define ('STATUS_INCOMPLETE', 8);

define('WORK_TYPE_EDITED_VOLUME', 1);
define('WORK_TYPE_AUTHORED_WORK', 2);

import('lib.pkp.classes.submission.Submission');
import('classes.monograph.Author');

class Monograph extends Submission {

 	/**
	 * get monograph id
	 * @return int
	 * Constructor.
 	 */
	function Monograph() {
		// Switch on meta-data adapter support.
		$this->setHasLoadableAdapters(true);

		parent::Submission();
 	}

	/**
	 * @see Submission::getAssocType()
	 */
	function getAssocType() {
		return ASSOC_TYPE_MONOGRAPH;
	}

	/**
	 * get press id
	 * @return int
	 */
	function getPressId() {
		return $this->getData('pressId');
	}

	/**
	 * set press id
	 * @param $pressId int
	 */
	function setPressId($pressId) {
		return $this->setData('pressId', $pressId);
	}

	/**
	 * Get the series id.
	 * @return int
	 */
	function getSeriesId() {
		 return $this->getData('seriesId');
	}

	/**
	 * Set the series id.
	 * @param $id int
	 */
	function setSeriesId($id) {
		 $this->setData('seriesId', $id);
	}

	/**
	 * Get the series's title.
	 * @return string
	 */
	function getSeriesTitle() {
		 return $this->getData('seriesTitle');
	}

	/**
	 * Set the series title.
	 * @param $title string
	 */
	function setSeriesTitle($title) {
		 $this->setData('seriesTitle', $title);
	}

	/**
	 * Get the series's abbreviated identifier.
	 * @return string
	 */
	function getSeriesAbbrev() {
		 return $this->getData('seriesAbbrev');
	}

	/**
	 * Set the series's abbreviated identifier.
	 * @param $abbrev string
	 */
	function setSeriesAbbrev($abbrev) {
		 $this->setData('seriesAbbrev', $abbrev);
	}

	/**
	 * Get comments to editor.
	 * @return string
	 */
	function getCommentsToEditor() {
		return $this->getData('commentsToEditor');
	}

	/**
	 * Set comments to editor.
	 * @param $commentsToEditor string
	 */
	function setCommentsToEditor($commentsToEditor) {
		return $this->setData('commentsToEditor', $commentsToEditor);
	}

	/**
	 * Return boolean indicating if author should be hidden in contributor statement.
	 * @return boolean
	 */
	function getHideAuthor() {
		return $this->getData('hideAuthor');
	}

	/**
	 * Set if author should be hidden in the contributor statement.
	 * @param $hideAuthor boolean
	 */
	function setHideAuthor($hideAuthor) {
		return $this->setData('hideAuthor', $hideAuthor);
	}

	/**
	 * Get the monograph's current publication stage ID
	 * @return int
	 */
	function getStageId() {
		return $this->getData('stageId');
	}

	/**
	 * Set the monograph's current publication stage ID
	 * @param $stageId int
	 */
	function setStageId($stageId) {
		return $this->setData('stageId', $stageId);
	}


	//
	// Peer Review
	//

	/**
	 * Set the current review round
	 * @param $rount int
	 */
	function setCurrentRound($round) {
		 $this->setData('currentRound', $round);
	}

	/**
	 * Get the current review round
	 * @return int
	 */
	function getCurrentRound() {
		 return $this->getData('currentRound');
	}

	/**
	 * Set the current review round per stage ID
	 * @param $reviewRoundsInfo array ($stageId => $currentReviewRound)
	 */
	function setReviewRoundsInfo($reviewRoundsInfo) {
		 $this->setData('reviewRoundsInfo', $reviewRoundsInfo);
	}

	/**
	 * Get the current review round per stage ID
	 * @return array ($stageId => $currentReviewRound)
	 */
	function getReviewRoundsInfo() {
		 return $this->getData('reviewRoundsInfo');
	}

	/**
	 * Get a signoff for this monograph
	 * @param $signoffType string
	 * @return Signoff
	 */
	function getSignoff($signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		return $signoffDao->getBySymbolic($signoffType, ASSOC_TYPE_MONOGRAPH, $this->getId());
	}

	/**
	 * Get an array of user IDs associated with this monograph
	 * @param $includeReviewers boolean Include reviewers in the array
	 * @param $userGroupIds array Only look up the user group IDs in the array
	 * @return array User IDs
	 */
	function getAssociatedUserIdsByUserGroupId($stageId = null, $includeReviewers = false, $includeAuthors = false) {
		$monographId = $this->getId();
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$userIds = array();

		$stageAssignmentDAO =& DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments =& $stageAssignmentDAO->getBySubmissionAndStageId($monographId, $stageId);
		while ($stageAssignment =& $stageAssignments->next()) {
			$usersIds[$stageAssignment->getUserGroupId()][] = $stageAssignment->getUserId();
			unset($stageAssignment);
		}

		if ( $includeReviewers ) {
			// FIXME: #6688# reviewers not inluded in stage participants grid
			// need to add userGroupId's to reviewAssignments.
			// all this is doing nothing at the moment.
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($monographId);
			foreach ($reviewAssignments as $reviewAssignment) {
				// getReviewerUserGroupId does not exist ... yet.
				// $userIds[$reviewAssignment->getReviewerUserGroupId()][] = $reviewAssignment->getReviewerId();
				unset($reviewAssignment);
			}
		}

		if ( $includeAuthors ) {
			$authors =& $this->getAuthors();

			while ($author =& $authors->next()) {
				$usersIds[$author->getUserGroupId()][] = $author->getUserId();
				unset($author);
			}
		}

		return $userIds;
	}

	/**
	 * Get the file for this monograph at a given signoff stage
	 *
	 * FIXME: Move to some DAO, initialize on load or implement
	 * via lazy-load pattern to remove coupling of domain object
	 * with DAOs.
	 *
	 * @param $signoffType string
	 * @param $idOnly boolean Return only file ID
	 * @return MonographFile
	 */
	function &getFileBySignoffType($signoffType, $idOnly = false) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->getBySymbolic($signoffType, ASSOC_TYPE_MONOGRAPH, $this->getId());
		if (!$signoff) {
			$returner = false;
			return $returner;
		}

		if ($idOnly) {
			$returner = $signoff->getFileId();
			return $returner;
		}

		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		assert(is_numeric($signoff->getFileId()) && is_numeric($signoff->getFileRevision()));
		$monographFile =& $submissionFileDao->getRevision($signoff->getFileId(), $signoff->getFileRevision());
		return $monographFile;
	}

	/**
	 * Get the user associated with a given signoff and this monograph
	 * @param $signoffType string
	 * @return User
	 */
	function &getUserBySignoffType($signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$signoff =& $signoffDao->build($signoffType, ASSOC_TYPE_MONOGRAPH, $this->getId());

		if (!$signoff) return false;
		$user =& $userDao->getUser($signoff->getUserId());

		return $user;
	}

	/**
	 * Get the user id associated with a given signoff and this monograph
	 * @param $signoffType string
	 * @return int
	 */
	function getUserIdBySignoffType($signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->getBySymbolic($signoffType, ASSOC_TYPE_MONOGRAPH, $this->getId());
		if (!$signoff) return false;

		return $signoff->getUserId();
	}

	function getWorkType() {
		return $this->getData('workType');
	}

	function setWorkType($type) {
		$this->setData('workType', $type);
	}

	/**
	 * Get localized supporting agencies array.
	 * @return array
	 */
	function getLocalizedSupportingAgencies() {
		return $this->getLocalizedData('supportingAgencies');
	}

	/**
	 * Get supporting agencies.
	 * @param $locale
	 * @return array
	 */
	function getSupportingAgencies($locale) {
		return $this->getData('supportingAgencies', $locale);
	}

	/**
	 * Set supporting agencies.
	 * @param $supportingAgencies array
	 * @param $locale
	 */
	function setSupportingAgencies($title, $locale) {
		return $this->setData('supportingAgencies', $title, $locale);
	}

	/**
	 * Return absolute path to the files of this
	 * monograph on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		return Config::getVar('files', 'files_dir') . '/presses/' . $this->getPressId() .
				'/monographs/' . $this->getId() . '/';
	}
}

?>
