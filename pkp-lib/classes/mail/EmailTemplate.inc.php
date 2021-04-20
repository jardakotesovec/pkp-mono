<?php

/**
 * @file classes/mail/EmailTemplate.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplate
 * @ingroup mail
 *
 * @see EmailTemplateDAO
 *
 * @brief Describes basic email template properties.
 */

class EmailTemplate extends \PKP\core\DataObject
{
    //
    // Get/set methods
    //

    /**
     * Get ID of journal/conference/...
     *
     * @deprecated 3.2
     *
     * @return int
     */
    public function getAssocId()
    {
        return $this->getData('contextId');
    }

    /**
     * Set ID of journal/conference/...
     *
     * @deprecated 3.2
     *
     * @param $assocId int
     */
    public function setAssocId($assocId)
    {
        $this->setData('contextId', $assocId);
    }

    /**
     * Determine whether or not this is a custom email template
     * (ie one that was created by the journal/conference/... manager and
     * is not part of the system upon installation)
     *
     * @deprecated 3.2
     */
    public function isCustomTemplate()
    {
        return false;
    }

    /**
     * Get sender role ID.
     *
     * @deprecated 3.2
     */
    public function getFromRoleId()
    {
        return $this->getData('fromRoleId');
    }

    /**
     * Set sender role ID.
     *
     * @param $fromRoleId int
     *
     * @deprecated 3.2
     */
    public function setFromRoleId($fromRoleId)
    {
        $this->setData('fromRoleId', $fromRoleId);
    }

    /**
     * Get recipient role ID.
     *
     * @deprecated 3.2
     */
    public function getToRoleId()
    {
        return $this->getData('toRoleId');
    }

    /**
     * Set recipient role ID.
     *
     * @deprecated 3.2
     *
     * @param $toRoleId int
     */
    public function setToRoleId($toRoleId)
    {
        $this->setData('toRoleId', $toRoleId);
    }

    /**
     * Get ID of email template.
     *
     * @deprecated 3.2
     *
     * @return int
     */
    public function getEmailId()
    {
        return $this->getData('id');
    }

    /**
     * Set ID of email template.
     *
     * @deprecated 3.2
     *
     * @param $emailId int
     */
    public function setEmailId($emailId)
    {
        $this->setData('id', $emailId);
    }

    /**
     * Get key of email template.
     *
     * @deprecated 3.2
     *
     * @return string
     */
    public function getEmailKey()
    {
        return $this->getData('key');
    }

    /**
     * Set key of email template.
     *
     * @deprecated 3.2
     *
     * @param $key string
     */
    public function setEmailKey($key)
    {
        $this->setData('key', $key);
    }

    /**
     * Get the enabled setting of email template.
     *
     * @deprecated 3.2
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->getData('enabled');
    }

    /**
     * Set the enabled setting of email template.
     *
     * @deprecated 3.2
     *
     * @param $enabled boolean
     */
    public function setEnabled($enabled)
    {
        $this->setData('enabled', $enabled);
    }

    /**
     * Check if email template is allowed to be disabled.
     *
     * @deprecated 3.2
     *
     * @return boolean
     */
    public function getCanDisable()
    {
        return $this->getData('canDisable');
    }

    /**
     * Set whether or not email template is allowed to be disabled.
     *
     * @deprecated 3.2
     *
     * @param $canDisable boolean
     */
    public function setCanDisable($canDisable)
    {
        $this->setData('canDisable', $canDisable);
    }

    /**
     * Get subject of email template.
     *
     * @deprecated 3.2
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getData('subject');
    }

    /**
     * Set subject of email.
     *
     * @deprecated 3.2
     *
     * @param $subject string
     */
    public function setSubject($subject)
    {
        $this->setData('subject', $subject);
    }

    /**
     * Get body of email template.
     *
     * @deprecated 3.2
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getData('body');
    }

    /**
     * Set body of email template.
     *
     * @deprecated 3.2
     *
     * @param $body string
     */
    public function setBody($body)
    {
        $this->setData('body', $body);
    }
}
