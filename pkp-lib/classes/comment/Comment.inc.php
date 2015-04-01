<?php

/**
 * @defgroup comment Comment
 * Implements reader comments.
 */

/**
 * @file classes/comment/Comment.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Comment
 * @ingroup comment
 * @see CommentDAO
 *
 * @brief Class for public Comment associated with submission.
 */

class Comment extends DataObject {
	/**
	 * Constructor.
	 */
	function Comment() {
		parent::DataObject();
		$this->setPosterIP(Request::getRemoteAddr());
	}

	/**
	 * get number of child comments
	 * @return int
	 */
	function getChildCommentCount() {
		return $this->getData('childCommentCount');
	}

	/**
	 * set number of child comments
	 * @param $childCommentCount int
	 */
	function setChildCommentCount($childCommentCount) {
		$this->setData('childCommentCount', $childCommentCount);
	}

	/**
	 * get parent comment id
	 * @return int
	 */
	function getParentCommentId() {
		return $this->getData('parentCommentId');
	}

	/**
	 * set parent comment id
	 * @param $parentCommentId int
	 */
	function setParentCommentId($parentCommentId) {
		$this->setData('parentCommentId', $parentCommentId);
	}

	/**
	 * Get submission id
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set submission id
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * get user object
	 * @return object
	 */
	function getUser() {
		return $this->getData('user');
	}

	/**
	 * set user object
	 * @param $user object
	 */
	function setUser($user) {
		$this->setData('user', $user);
	}

	/**
	 * get poster name
	 */
	function getPosterName() {
		return $this->getData('posterName');
	}

	/**
	 * set poster name
	 * @param $posterName string
	 */
	function setPosterName($posterName) {
		$this->setData('posterName', $posterName);
	}

	/**
	 * get poster email
	 */
	function getPosterEmail() {
		return $this->getData('posterEmail');
	}

	/**
	 * set poster email
	 * @param $posterEmail string
	 */
	function setPosterEmail($posterEmail) {
		$this->setData('posterEmail', $posterEmail);
	}

	/**
	 * get posterIP
	 * @return string
	 */
	function getPosterIP() {
		return $this->getData('posterIP');
	}

	/**
	 * set posterIP
	 * @param $posterIP string
	 */
	function setPosterIP($posterIP) {
		$this->setData('posterIP', $posterIP);
	}

	/**
	 * get title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * set title
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}

	/**
	 * get comment body
	 * @return string
	 */
	function getBody() {
		return $this->getData('body');
	}

	/**
	 * set comment body
	 * @param $body string
	 */
	function setBody($body) {
		$this->setData('body', $body);
	}

	/**
	 * get date posted
	 * @return date
	 */
	function getDatePosted() {
		return $this->getData('datePosted');
	}

	/**
	 * set date posted
	 * @param $datePosted date
	 */
	function setDatePosted($datePosted) {
		$this->setData('datePosted', $datePosted);
	}

	/**
	 * get date modified
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * set date modified
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		$this->setData('dateModified', $dateModified);
	}

	/**
	 * get child comments (if fetched using recursive option)
	 * @return array
	 */
	function &getChildren() {
		$children =& $this->getData('children');
		return $children;
	}

	/**
	 * set child comments
	 * @param $children array
	 */
	function setChildren(&$children) {
		$this->setData('children', $children);
	}
}

?>
