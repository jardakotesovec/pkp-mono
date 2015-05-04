<?php
/**
 * @defgroup notification_form Notification Form
 */

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationSettingsForm
 * @ingroup notification_form
 *
 * @brief Form to edit notification settings.
 */


import('lib.pkp.classes.form.Form');

class PKPNotificationSettingsForm extends Form {
	/**
	 * Constructor.
	 */
	function PKPNotificationSettingsForm() {
		parent::Form('user/notificationSettingsForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array();
		foreach($this->getNotificationSettingsMap() as $notificationSetting) {
			$userVars[] = $notificationSetting['settingName'];
			$userVars[] = $notificationSetting['emailSettingName'];
		}

		$this->readUserVars($userVars);
	}

	/**
	 * Get all notification settings form names and their setting type values
	 * @return array
	 */
	protected function getNotificationSettingsMap() {
		return array(
			NOTIFICATION_TYPE_SUBMISSION_SUBMITTED => array('settingName' => 'notificationSubmissionSubmitted',
				'emailSettingName' => 'emailNotificationSubmissionSubmitted',
				'settingKey' => 'notification.type.submissionSubmitted'),
			NOTIFICATION_TYPE_METADATA_MODIFIED => array('settingName' => 'notificationMetadataModified',
				'emailSettingName' => 'emailNotificationMetadataModified',
				'settingKey' => 'notification.type.metadataModified'),
			NOTIFICATION_TYPE_REVIEWER_COMMENT => array('settingName' => 'notificationReviewerComment',
				'emailSettingName' => 'emailNotificationReviewerComment',
				'settingKey' => 'notification.type.reviewerComment')
		);
	}

	/**
	 * Get a list of notification category names (to display as headers)
	 *  and the notification types under each category
	 * @return array
	 */
	protected function getNotificationSettingCategories() {
		return array(
			array('categoryKey' => 'notification.type.submissions',
				'settings' => array(NOTIFICATION_TYPE_SUBMISSION_SUBMITTED, NOTIFICATION_TYPE_METADATA_MODIFIED)),
			array('categoryKey' => 'notification.type.reviewing',
				'settings' => array(NOTIFICATION_TYPE_REVIEWER_COMMENT))
		);
	}

	/**
	 * @copydoc
	 */
	function fetch($request) {
		$context = $request->getContext();
		$user = $request->getUser();
		$userId = $user->getId();

		$notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
		$blockedNotifications = $notificationSubscriptionSettingsDao->getNotificationSubscriptionSettings('blocked_notification', $userId, $context->getId());
		$emailSettings = $notificationSubscriptionSettingsDao->getNotificationSubscriptionSettings('blocked_emailed_notification', $userId, $context->getId());

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('blockedNotifications', $blockedNotifications);
		$templateMgr->assign('emailSettings', $emailSettings);
		$templateMgr->assign('titleVar', __('common.title'));
		$templateMgr->assign('userVar', __('common.user'));
		$templateMgr->assign('notificationSettingCategories', $this->getNotificationSettingCategories());
		$templateMgr->assign('notificationSettings',  $this->getNotificationSettingsMap());
		return parent::fetch($request);
	}

	/**
	 * @copydoc
	 */
	function execute($request) {
		$user = $request->getUser();
		$userId = $user->getId();
		$context = $request->getContext();

		$blockedNotifications = array();
		$emailSettings = array();
		foreach($this->getNotificationSettingsMap() as $settingId => $notificationSetting) {
			// Get notifications that the user wants blocked
			if(!$this->getData($notificationSetting['settingName'])) $blockedNotifications[] = $settingId;
			// Get notifications that the user wants to be notified of by email
			if($this->getData($notificationSetting['emailSettingName'])) $emailSettings[] = $settingId;
		}

		$notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('blocked_notification', $blockedNotifications, $userId, $context->getId());
		$notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings('blocked_emailed_notification', $emailSettings, $userId, $context->getId());

		return true;
	}
}

?>
