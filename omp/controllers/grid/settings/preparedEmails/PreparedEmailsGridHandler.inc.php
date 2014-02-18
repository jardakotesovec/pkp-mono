<?php

/**
 * @file controllers/grid/settings/preparedEmails/PreparedEmailsGridHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreparedEmailsGridHandler
 * @ingroup controllers_grid_settings_preparedEmails
 *
 * @brief Handle preparedEmails grid requests.
 */

// Import grid base classes
import('lib.pkp.classes.controllers.grid.settings.preparedEmails.PKPPreparedEmailsGridHandler');

class PreparedEmailsGridHandler extends PKPPreparedEmailsGridHandler {
	/**
	 * Constructor
	 */
	function PreparedEmailsGridHandler() {
		parent::PKPPreparedEmailsGridHandler();
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return PreparedEmailsGridRow
	 */
	function getRowInstance() {
		return new PreparedEmailsGridRow();
	}


	//
	// Public handler methods
	//
	/**
	 * Edit a prepared email
	 * Will create a new prepared email if their is no emailKey in the request
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editPreparedEmail($args, $request) {
		$press = $request->getPress();
		$emailKey = $request->getUserVar('emailKey');

		import('lib.pkp.controllers.grid.settings.preparedEmails.form.PreparedEmailForm');
		$preparedEmailForm = new PreparedEmailForm($emailKey, $press);
		$preparedEmailForm->initData($request);

		$json = new JSONMessage(true, $preparedEmailForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Save the email editing form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updatePreparedEmail($args, $request) {
		$press = $request->getPress();
		$emailKey = $request->getUserVar('emailKey');

		import('lib.pkp.controllers.grid.settings.preparedEmails.form.PreparedEmailForm');
		$preparedEmailForm = new PreparedEmailForm($emailKey, $press);
		$preparedEmailForm->readInputData();

		if ($preparedEmailForm->validate()) {
			$preparedEmailForm->execute();

			// Create notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			// Let the calling grid reload itself
			return DAO::getDataChangedEvent($emailKey);
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * Reset a single email
	 * @param $args array
	 * @param $request Request
	 * @return string a serialized JSON object
	 */
	function resetEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		assert(is_string($emailKey));

		$press = $request->getPress();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		if ($emailTemplateDao->templateExistsByKey($emailKey, $press->getId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $press->getId());
			return DAO::getDataChangedEvent($emailKey);
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * Reset all email to stock.
	 * @param $args array
	 * @param $request Request
	 */
	function resetAllEmails($args, $request) {
		$press = $request->getPress();
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$emailTemplateDao->deleteEmailTemplatesByPress($press->getId());
		return DAO::getDataChangedEvent();
	}

	/**
	 * Disables an email template.
	 * @param $args array
	 * @param $request Request
	 */
	function disableEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		assert(is_string($emailKey));

		$press = $request->getPress();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($emailKey, $press->getId());

		if (isset($emailTemplate)) {
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled(0);

				if ($emailTemplate->getAssocId() == null) {
					$emailTemplate->setAssocId($press->getId());
					$emailTemplate->setAssocType(ASSOC_TYPE_PRESS);
				}

				if ($emailTemplate->getEmailId() != null) {
					$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
				} else {
					$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
				}

				return DAO::getDataChangedEvent($emailKey);
			}
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}


	/**
	 * Enables an email template.
	 * @param $args array
	 * @param $request Request
	 */
	function enableEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		assert(is_string($emailKey));

		$press = $request->getPress();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($emailKey, $press->getId());

		if (isset($emailTemplate)) {
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled(1);

				if ($emailTemplate->getEmailId() != null) {
					$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
				} else {
					$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
				}

				return DAO::getDataChangedEvent($emailKey);
			}
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * Delete a custom email.
	 * @param $args array
	 * @param $request Request
	 */
	function deleteCustomEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		$press = $request->getPress();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		if ($emailTemplateDao->customTemplateExistsByKey($emailKey, $press->getId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $press->getId());
			return DAO::getDataChangedEvent($emailKey);
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

}

?>
