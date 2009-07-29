<?php

/**
 * @file classes/mail/EmailTemplateDAO.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplateDAO
 * @ingroup mail
 * @see EmailTemplate
 *
 * @brief Operations for retrieving and modifying Email Template objects.
 */

// $Id$


import('mail.PKPEmailTemplateDAO');
import('mail.EmailTemplate');

class EmailTemplateDAO extends PKPEmailTemplateDAO {
	/**
	 * Retrieve a base email template by key.
	 * @param $emailKey string
	 * @param $pressId int
	 * @return BaseEmailTemplate
	 */
	function &getBaseEmailTemplate($emailKey, $pressId) {
		return parent::getBaseEmailTemplate($emailKey, ASSOC_TYPE_JOURNAL, $pressId);
	}

	/**
	 * Retrieve localized email template by key.
	 * @param $emailKey string
	 * @param $pressId int
	 * @return LocaleEmailTemplate
	 */
	function &getLocaleEmailTemplate($emailKey, $pressId) {
		return parent::getLocaleEmailTemplate($emailKey, ASSOC_TYPE_JOURNAL, $pressId);
	}

	/**
	 * Retrieve an email template by key.
	 * @param $emailKey string
	 * @param $locale string
	 * @param $pressId int
	 * @return EmailTemplate
	 */
	function &getEmailTemplate($emailKey, $locale, $pressId) {
		return parent::getEmailTemplate($emailKey, $locale, ASSOC_TYPE_JOURNAL, $pressId);
	}

	/**
	 * Delete an email template by key.
	 * @param $emailKey string
	 * @param $pressId int
	 */
	function deleteEmailTemplateByKey($emailKey, $pressId) {
		return parent::deleteEmailTemplateByKey($emailKey, ASSOC_TYPE_JOURNAL, $pressId);
	}

	/**
	 * Retrieve all email templates.
	 * @param $locale string
	 * @param $pressId int
	 * @param $rangeInfo object optional
	 * @return array Email templates
	 */
	function &getEmailTemplates($locale, $pressId, $rangeInfo = null) {
		return parent::getEmailTemplates($locale, ASSOC_TYPE_JOURNAL, $pressId, $rangeInfo);
	}

	/**
	 * Delete all email templates for a specific press.
	 * @param $pressId int
	 */
	function deleteEmailTemplatesByPress($pressId) {
		return parent::deleteEmailTemplatesByAssoc(ASSOC_TYPE_JOURNAL, $pressId);
	}

	/**
	 * Check if a template exists with the given email key for a press.
	 * @param $emailKey string
	 * @param $pressId int
	 * @return boolean
	 */
	function templateExistsByKey($emailKey, $pressId) {
		return parent::templateExistsByKey($emailKey, ASSOC_TYPE_JOURNAL, $pressId);
	}

	/**
	 * Check if a custom template exists with the given email key for a press.
	 * @param $emailKey string
	 * @param $pressId int
	 * @return boolean
	 */
	function customTemplateExistsByKey($emailKey, $pressId) {
		return parent::customTemplateExistsByKey($emailKey, ASSOC_TYPE_JOURNAL, $pressId);
	}
}

?>
