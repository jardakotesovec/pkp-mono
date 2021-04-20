<?php

/**
 * @file classes/submission/SubmissionComment.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionComment
 * @ingroup submission
 *
 * @see SubmissionCommentDAO
 *
 * @brief Class for SubmissionComment.
 */

/** Comment associative types. All types must be defined here. */
define('COMMENT_TYPE_PEER_REVIEW', 0x01);
define('COMMENT_TYPE_EDITOR_DECISION', 0x02);
define('COMMENT_TYPE_COPYEDIT', 0x03);
define('COMMENT_TYPE_LAYOUT', 0x04);
define('COMMENT_TYPE_PROOFREAD', 0x05);

class SubmissionComment extends \PKP\core\DataObject
{
    /**
     * get comment type
     *
     * @return int COMMENT_TYPE_...
     */
    public function getCommentType()
    {
        return $this->getData('commentType');
    }

    /**
     * set comment type
     *
     * @param $commentType int COMMENT_TYPE_...
     */
    public function setCommentType($commentType)
    {
        $this->setData('commentType', $commentType);
    }

    /**
     * get role id
     *
     * @return int
     */
    public function getRoleId()
    {
        return $this->getData('roleId');
    }

    /**
     * set role id
     *
     * @param $roleId int
     */
    public function setRoleId($roleId)
    {
        $this->setData('roleId', $roleId);
    }

    /**
     * get submission id
     *
     * @return int
     */
    public function getSubmissionId()
    {
        return $this->getData('submissionId');
    }

    /**
     * set submission id
     *
     * @param $submissionId int
     */
    public function setSubmissionId($submissionId)
    {
        $this->setData('submissionId', $submissionId);
    }

    /**
     * get assoc id
     *
     * @return int
     */
    public function getAssocId()
    {
        return $this->getData('assocId');
    }

    /**
     * set assoc id
     *
     * @param $assocId int
     */
    public function setAssocId($assocId)
    {
        $this->setData('assocId', $assocId);
    }

    /**
     * get author id
     *
     * @return int
     */
    public function getAuthorId()
    {
        return $this->getData('authorId');
    }

    /**
     * set author id
     *
     * @param $authorId int
     */
    public function setAuthorId($authorId)
    {
        $this->setData('authorId', $authorId);
    }

    /**
     * get author name
     *
     * @return string
     */
    public function getAuthorName()
    {
        // Reference used to set if not already fetched
        $authorFullName = & $this->getData('authorFullName');

        if (!isset($authorFullName)) {
            $userDao = DAORegistry::getDAO('UserDAO'); /** @var UserDAO $userDao */
            $authorFullName = $userDao->getUserFullName($this->getAuthorId(), true);
        }

        return $authorFullName ? $authorFullName : '';
    }

    /**
     * get author email
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        // Reference used to set if not already fetched
        $authorEmail = & $this->getData('authorEmail');

        if (!isset($authorEmail)) {
            $userDao = DAORegistry::getDAO('UserDAO'); /** @var UserDAO $userDao */
            $authorEmail = $userDao->getUserEmail($this->getAuthorId(), true);
        }

        return $authorEmail ? $authorEmail : '';
    }

    /**
     * get comment title
     *
     * @return string
     */
    public function getCommentTitle()
    {
        return $this->getData('commentTitle');
    }

    /**
     * set comment title
     *
     * @param $commentTitle string
     */
    public function setCommentTitle($commentTitle)
    {
        $this->setData('commentTitle', $commentTitle);
    }

    /**
     * get comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->getData('comments');
    }

    /**
     * set comments
     *
     * @param $comments string
     */
    public function setComments($comments)
    {
        $this->setData('comments', $comments);
    }

    /**
     * get date posted
     *
     * @return date
     */
    public function getDatePosted()
    {
        return $this->getData('datePosted');
    }

    /**
     * set date posted
     *
     * @param $datePosted date
     */
    public function setDatePosted($datePosted)
    {
        $this->setData('datePosted', $datePosted);
    }

    /**
     * get date modified
     *
     * @return date
     */
    public function getDateModified()
    {
        return $this->getData('dateModified');
    }

    /**
     * set date modified
     *
     * @param $dateModified date
     */
    public function setDateModified($dateModified)
    {
        $this->setData('dateModified', $dateModified);
    }

    /**
     * get viewable
     *
     * @return boolean
     */
    public function getViewable()
    {
        return $this->getData('viewable');
    }

    /**
     * set viewable
     *
     * @param $viewable boolean
     */
    public function setViewable($viewable)
    {
        $this->setData('viewable', $viewable);
    }
}
