<?php

/**
 * @file classes/submission/reviewer/ReviewerSubmission.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmission
 * @ingroup submission
 * @see ReviewerSubmissionDAO
 *
 * @brief ReviewerSubmission class.
 */



import('classes.monograph.Monograph');

class ReviewerSubmission extends Monograph {

	/** @var array MonographFiles reviewer file revisions of this monograph */
	var $reviewerFileRevisions;

	/** @var array MonographComments peer review comments of this monograph */
	var $peerReviewComments;

	/** @var array the editor decisions of this monograph */
	var $editorDecisions;

	/**
	 * Constructor.
	 */
	function ReviewerSubmission() {
		parent::Monograph();
	}

	/**
	 * Get/Set Methods.
	 */

	/**
	 * Get the competing interests for this monograph.
	 * @return string
	 */
	function getCompetingInterests() {
		return $this->getData('competingInterests');
	}

	/**
	 * Set the competing interests statement.
	 * @param $competingInterests string
	 */
	function setCompetingInterests($competingInterests) {
		return $this->setData('competingInterests', $competingInterests);
	}

	/**
	 * Get ID of review assignment.
	 * @return int
	 */
	function getReviewId() {
		return $this->getData('reviewId');
	}

	/**
	 * Set ID of review assignment
	 * @param $reviewId int
	 */
	function setReviewId($reviewId) {
		return $this->setData('reviewId', $reviewId);
	}

	/**
	 * Get ID of reviewer.
	 * @return int
	 */
	function getReviewerId() {
		return $this->getData('reviewerId');
	}

	/**
	 * Set ID of reviewer.
	 * @param $reviewerId int
	 */
	function setReviewerId($reviewerId) {
		return $this->setData('reviewerId', $reviewerId);
	}

	/**
	 * Get full name of reviewer.
	 * @return string
	 */
	function getReviewerFullName() {
		return $this->getData('reviewerFullName');
	}

	/**
	 * Set full name of reviewer.
	 * @param $reviewerFullName string
	 */
	function setReviewerFullName($reviewerFullName) {
		return $this->setData('reviewerFullName', $reviewerFullName);
	}

	/**
	 * Get editor decisions.
	 * @return array
	 */
	function getDecisions() {
		return $this->editorDecisions;
	}

	/**
	 * Set editor decisions.
	 * @param $editorDecisions array
	 * @param $round int
	 */
	function setDecisions($editorDecisions) {
		return $this->editorDecisions = $editorDecisions;
	}

	/**
	 * Get reviewer recommendation.
	 * @return string
	 */
	function getRecommendation() {
		return $this->getData('recommendation');
	}

	/**
	 * Set reviewer recommendation.
	 * @param $recommendation string
	 */
	function setRecommendation($recommendation) {
		return $this->setData('recommendation', $recommendation);
	}

	/**
	 * Get the reviewer's assigned date.
	 * @return string
	 */
	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}

	/**
	 * Set the reviewer's assigned date.
	 * @param $dateAssigned string
	 */
	function setDateAssigned($dateAssigned) {
		return $this->setData('dateAssigned', $dateAssigned);
	}

	/**
	 * Get the reviewer's notified date.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}

	/**
	 * Set the reviewer's notified date.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}

	/**
	 * Get the reviewer's confirmed date.
	 * @return string
	 */
	function getDateConfirmed() {
		return $this->getData('dateConfirmed');
	}

	/**
	 * Set the reviewer's confirmed date.
	 * @param $dateConfirmed string
	 */
	function setDateConfirmed($dateConfirmed) {
		return $this->setData('dateConfirmed', $dateConfirmed);
	}

	/**
	 * Get the reviewer's completed date.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}

	/**
	 * Set the reviewer's completed date.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted) {
		return $this->setData('dateCompleted', $dateCompleted);
	}

	/**
	 * Get the reviewer's acknowledged date.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}

	/**
	 * Set the reviewer's acknowledged date.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged) {
		return $this->setData('dateAcknowledged', $dateAcknowledged);
	}

	/**
	 * Get the reviewer's due date.
	 * @return string
	 */
	function getDateDue() {
		return $this->getData('dateDue');
	}

	/**
	 * Set the reviewer's due date.
	 * @param $dateDue string
	 */
	function setDateDue($dateDue) {
		return $this->setData('dateDue', $dateDue);
	}

	/**
	 * Get the reviewer's response due date.
	 * @return string
	 */
	function getDateResponseDue() {
		return $this->getData('dateResponseDue');
	}

	/**
	 * Set the reviewer's response due date.
	 * @param $dateResponseDue string
	 */
	function setDateResponseDue($dateResponseDue) {
		return $this->setData('dateResponseDue', $dateResponseDue);
	}

	/**
	 * Get the declined value.
	 * @return boolean
	 */
	function getDeclined() {
		return $this->getData('declined');
	}

	/**
	 * Set the reviewer's declined value.
	 * @param $declined boolean
	 */
	function setDeclined($declined) {
		return $this->setData('declined', $declined);
	}

	/**
	 * Get the replaced value.
	 * @return boolean
	 */
	function getReplaced() {
		return $this->getData('replaced');
	}

	/**
	 * Set the reviewer's replaced value.
	 * @param $replaced boolean
	 */
	function setReplaced($replaced) {
		return $this->setData('replaced', $replaced);
	}

	/**
	 * Get the cancelled value.
	 * @return boolean
	 */
	function getCancelled() {
		return $this->getData('cancelled');
	}

	/**
	 * Set the reviewer's cancelled value.
	 * @param $replaced boolean
	 */
	function setCancelled($cancelled) {
		return $this->setData('cancelled', $cancelled);
	}

	/**
	 * Get reviewer file id.
	 * @return int
	 */
	function getReviewerFileId() {
		return $this->getData('reviewerFileId');
	}

	/**
	 * Set reviewer file id.
	 * @param $reviewerFileId int
	 */
	function setReviewerFileId($reviewerFileId) {
		return $this->setData('reviewerFileId', $reviewerFileId);
	}

	/**
	 * Get quality.
	 * @return int
	 */
	function getQuality() {
		return $this->getData('quality');
	}

	/**
	 * Set quality.
	 * @param $quality int
	 */
	function setQuality($quality) {
		return $this->setData('quality', $quality);
	}

	/**
	 * Get reviewType.
	 * @return int
	 */
	function getReviewType() {
		return $this->getData('reviewType');
	}

	/**
	 * Set reviewType.
	 * @param $reviewType int
	 */
	function setReviewType($reviewType) {
		return $this->setData('reviewType', $reviewType);
	}

	/**
	 * Get the method of the review (open, blind, or double-blind).
	 * @return int
	 */
	function getReviewMethod() {
		return $this->getData('reviewMethod');
	}

	/**
	 * Set the type of review.
	 * @param $method int
	 */
	function setReviewMethod($method) {
		return $this->setData('reviewMethod', $method);
	}

	/**
	 * Get round.
	 * @return int
	 */
	function getRound() {
		return $this->getData('round');
	}

	/**
	 * Set round.
	 * @param $round int
	 */
	function setRound($round) {
		return $this->setData('round', $round);
	}

	/**
	 * Get step.
	 * @return int
	 */
	function getStep() {
		return $this->getData('step');
	}

	/**
	 * Set status.
	 * @param $status int
	 */
	function setStep($step) {
		return $this->setData('step', $step);
	}
	/**
	 * Get review file id.
	 * @return int
	 */
	function getReviewFileId() {
		return $this->getData('reviewFileId');
	}

	/**
	 * Set review file id.
	 * @param $reviewFileId int
	 */
	function setReviewFileId($reviewFileId) {
		return $this->setData('reviewFileId', $reviewFileId);
	}


	//
	// Files
	//

	/**
	 * Get submission file for this monograph.
	 * @return MonographFile
	 */
	function &getSubmissionFile() {
		$returner =& $this->getData('submissionFile');
		return $returner;
	}

	/**
	 * Set submission file for this monograph.
	 * @param $submissionFile MonographFile
	 */
	function setSubmissionFile($submissionFile) {
		return $this->setData('submissionFile', $submissionFile);
	}

	/**
	 * Get revised file for this monograph.
	 * @return MonographFile
	 */
	function &getRevisedFile() {
		$returner =& $this->getData('revisedFile');
		return $returner;
	}

	/**
	 * Set revised file for this monograph.
	 * @param $submissionFile MonographFile
	 */
	function setRevisedFile($revisedFile) {
		return $this->setData('revisedFile', $revisedFile);
	}

	/**
	 * Get review file.
	 * @return MonographFile
	 */
	function &getReviewFile() {
		$returner =& $this->getData('reviewFile');
		return $returner;
	}

	/**
	 * Set review file.
	 * @param $reviewFile MonographFile
	 */
	function setReviewFile($reviewFile) {
		return $this->setData('reviewFile', $reviewFile);
	}

	/**
	 * Get reviewer file.
	 * @return MonographFile
	 */
	function &getReviewerFile() {
		$returner =& $this->getData('reviewerFile');
		return $returner;
	}

	/**
	 * Set reviewer file.
	 * @param $reviewFile MonographFile
	 */
	function setReviewerFile($reviewerFile) {
		return $this->setData('reviewerFile', $reviewerFile);
	}

	/**
	 * Get all reviewer file revisions.
	 * @return array MonographFiles
	 */
	function getReviewerFileRevisions() {
		return $this->reviewerFileRevisions;
	}

	/**
	 * Set all reviewer file revisions.
	 * @param $reviewerFileRevisions array MonographFiles
	 */
	function setReviewerFileRevisions($reviewerFileRevisions) {
		return $this->reviewerFileRevisions = $reviewerFileRevisions;
	}

	//
	// Comments
	//

	/**
	 * Get most recent peer review comment.
	 * @return MonographComment
	 */
	function getMostRecentPeerReviewComment() {
		return $this->getData('peerReviewComment');
	}

	/**
	 * Set most recent peer review comment.
	 * @param $peerReviewComment MonographComment
	 */
	function setMostRecentPeerReviewComment($peerReviewComment) {
		return $this->setData('peerReviewComment', $peerReviewComment);
	}
}

?>
