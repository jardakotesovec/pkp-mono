<?php

/**
 * @file NotificationHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationHandler
 * @ingroup pages_help
 *
 * @brief Handle requests for viewing notifications.
 */

import('handler.Handler');
import('notification.Notification');

class NotificationHandler extends Handler {

	/**
	 * Display help table of contents.
	 */
	function index() {
		parent::validate();
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();

		$user = Request::getUser();
		if(isset($user)) {
			$userId = $user->getId();
			$templateMgr->assign('isUserLoggedIn', true);
		} else {
			$userId = 0;
			$templateMgr->assign('emailUrl', PKPRequest::url(NotificationHandler::getContextDepthArray(), 'notification', 'subscribeMailList'));
			$templateMgr->assign('isUserLoggedIn', false);
		}

		$rangeInfo =& Handler::getRangeInfo('notifications');
		$notificationDao =& DAORegistry::getDAO('NotificationDAO');
		$notifications = $notificationDao->getNotificationsByUserId($userId, $rangeInfo);

		$templateMgr->assign('notifications', $notifications);
		$templateMgr->assign('unread', $notificationDao->getUnreadNotificationCount($userId));
		$templateMgr->assign('read', $notificationDao->getReadNotificationCount($userId));
		$templateMgr->assign('url', PKPRequest::url(NotificationHandler::getContextDepthArray(), 'notification', 'settings'));
		$templateMgr->display('notification/index.tpl');
	}

	/**
	 * Delete a notification
	 */
	function delete($args) {
		parent::validate();

		$notificationId = array_shift($args);
		if (array_shift($args) == 'ajax') {
			$isAjax = true;
		} else $isAjax = false;

		$user = Request::getUser();
		if(isset($user)) {
			$userId = $user->getId();
			$notificationDao =& DAORegistry::getDAO('NotificationDAO');
			$notifications = $notificationDao->deleteNotificationById($notificationId, $userId);
		}

		if (!$isAjax) PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification');
	}

	/**
	 * View and modify notification settings
	 */
	function settings() {
		parent::validate();
		parent::setupTemplate();


		$user = Request::getUser();
		if(isset($user)) {
			import('notification.form.NotificationSettingsForm');
			$notificationSettingsForm =& new NotificationSettingsForm();
			$notificationSettingsForm->display();
		} else PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification');
	}

	/**
	 * Save user notification settings
	 */
	function saveSettings() {
		parent::validate();

		import('notification.form.NotificationSettingsForm');

		$notificationSettingsForm =& new NotificationSettingsForm();
		$notificationSettingsForm->readInputData();

		if ($notificationSettingsForm->validate()) {
			$notificationSettingsForm->execute();
			PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification', 'settings');
		} else {
			parent::setupTemplate(true);
			$notificationSettingsForm->display();
		}
	}

	/**
	 * Fetch the existing or create a new URL for the user's RSS feed
	 */
	function getNotificationFeedUrl($args) {
		$user = Request::getUser();
		if(isset($user)) {
			$userId = $user->getId();
		} else $userId = 0;

		$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
		$feedType = array_shift($args);

		$token = $notificationSettingsDao->getRSSTokenByUserId($userId);

		if ($token) {
			PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification', 'notificationFeed', array($feedType, $token));
		} else {
			$token = $notificationSettingsDao->insertNewRSSToken($userId);
			PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification', 'notificationFeed', array($feedType, $token));
		}
	}

	/**
	 * Fetch the actual RSS feed
	 */
	function notificationFeed($args) {
		if(isset($args[0]) && isset($args[1])) {
			$type = $args[0];
			$token = $args[1];
		} else return false;

		parent::setupTemplate(true);

		$application = PKPApplication::getApplication();
		$appName = $application->getNameKey();

		$site =& Request::getSite();
		$siteTitle = $site->getLocalizedTitle();

		$notificationDao =& DAORegistry::getDAO('NotificationDAO');
		$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');

		$userId = $notificationSettingsDao->getUserIdByRSSToken($token);
		$notifications = $notificationDao->getNotificationsByUserId($userId);

		// Make sure the feed type is specified and valid
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version = $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('version', $version->getVersionString());
		$templateMgr->assign('selfUrl', Request::getCompleteUrl());
		$templateMgr->assign('locale', Locale::getPrimaryLocale());
		$templateMgr->assign('appName', $appName);
		$templateMgr->assign('siteTitle', $siteTitle);
		$templateMgr->assign_by_ref('notifications', $notifications->toArray());

		$templateMgr->display(Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR .
			'pkp' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'notification' . DIRECTORY_SEPARATOR . $typeMap[$type], $mimeTypeMap[$type]);

		return true;
	}

	/**
	 * Display the public notification email subscription form
	 */
	function subscribeMailList() {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('new', true);

		$user = Request::getUser();

		if(!isset($user)) {
			// $templateMgr->assign('subscriptionEnabled', $subscriptionEnabled);

			if($userEmail = Request::getUserVar('email')) {
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				if($password = $notificationSettingsDao->subscribeGuest($userEmail)) {
					Notification::sendMailingListEmail($userEmail, $password, 'NOTIFICATION_MAILLIST_WELCOME');
					$templateMgr->assign('success', "notification.subscribeSuccess");
					$templateMgr->display('notification/maillist.tpl');
				} else {
					$templateMgr->assign('error', "notification.subscribeError");
					$templateMgr->display('notification/maillist.tpl');

				}
			} else {
				$templateMgr->assign('settings', Notification::getSubscriptionSettings());
				$templateMgr->display('notification/maillist.tpl');
			}
		} else PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification');
	}

	/**
	 * Display the public notification email subscription form
	 */
	function confirmMailListSubscription($args) {
		parent::setupTemplate();
		$keyHash = array_shift($args);
		$email = array_shift($args);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('confirm', true);

		$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
		$settingId = $notificationSettingsDao->getMailListSettingId($email);

		$accessKeyDao =& DAORegistry::getDAO('AccessKeyDAO');
		$accessKey = $accessKeyDao->getAccessKeyByKeyHash('MailListContext', $settingId, $keyHash);

		if($accessKey) {
			$notificationSettingsDao->confirmMailListSubscription($settingId);
			$templateMgr->assign('success', "notification.confirmSuccess");
			$templateMgr->display('notification/maillist.tpl');
		} else {
			$templateMgr->assign('error', "notification.confirmError");
			$templateMgr->display('notification/maillist.tpl');
		}
	}

	/**
	 * Display the public notification email subscription form
	 */
	function unsubscribeMailList() {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('remove', true);

		$user = Request::getUser();
		if(!isset($user)) {
			$userEmail = Request::getUserVar('email');
			$userPassword = Request::getUserVar('password');

			if($userEmail != '' && $userPassword != '') {
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				if($notificationSettingsDao->unsubscribeGuest($userEmail, $userPassword)) {
					$templateMgr->assign('success', "notification.unsubscribeSuccess");
					$templateMgr->display('notification/maillist.tpl');
				} else {
					$templateMgr->assign('error', "notification.unsubscribeError");
					$templateMgr->display('notification/maillist.tpl');
				}
			} else if($userEmail != '' && $userPassword == '') {
				$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO');
				if($newPassword = $notificationSettingsDao->resetPassword($userEmail)) {
					Notification::sendMailingListEmail($userEmail, $newPassword, 'NOTIFICATION_MAILLIST_PASSWORD');
					$templateMgr->assign('success', "notification.reminderSent");
					$templateMgr->display('notification/maillist.tpl');
				} else {
					$templateMgr->assign('error', "notification.reminderError");
					$templateMgr->display('notification/maillist.tpl');
				}
			} else {
				$templateMgr->assign('remove', true);
				$templateMgr->display('notification/maillist.tpl');
			}
		} else PKPRequest::redirect(NotificationHandler::getContextDepthArray(), 'notification');
	}

	/**
	 * Return an array with null values * the context depth
	 */
	 function getContextDepthArray() {
	 	$contextDepthArray = array();

	 	$application = PKPApplication::getApplication();
		$contextDepth = $application->getContextDepth();

		for ($i=0; $i < $contextDepth; $i++) {
			array_push($contextDepthArray, null);
		}

		return $contextDepthArray;
	 }
}

?>
