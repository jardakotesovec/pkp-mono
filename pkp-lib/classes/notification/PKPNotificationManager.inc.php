<?php

/**
 * @file classes/notification/PKPNotificationManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationManager
 * @ingroup notification
 * @see NotificationDAO
 * @see Notification
 * @brief Class for Notification Manager.
 */

import('lib.pkp.classes.notification.PKPNotificationOperationManager');

class PKPNotificationManager extends PKPNotificationOperationManager {
	/**
	 * Constructor.
	 */
	function PKPNotificationManager() {
	}

	/**
	 * Construct a URL for the notification based on its type and associated object
	 * @see INotificationInfoProvider::getNotificationContents()
	 */
	public function getNotificationUrl(&$request, &$notification) {
		return $this->getByDelegate(
			$notification->getType(),
			$notification->getAssocType(),
			$notification->getAssocId(),
			__FUNCTION__,
			array(&$request, &$notification)
		);
	}

	/**
	 * Return a message string for the notification based on its type
	 * and associated object.
	 * @see INotificationInfoProvider::getNotificationContents()
	 */
	public function getNotificationMessage(&$request, &$notification) {
		$type = $notification->getType();
		assert(isset($type));

		switch ($type) {
			case NOTIFICATION_TYPE_SUCCESS:
			case NOTIFICATION_TYPE_ERROR:
				if (!is_null($this->getNotificationSettings($notification->getId()))) {
					$notificationSettings = $this->getNotificationSettings($notification->getId());
					return $notificationSettings['contents'];
				} else {
					return __('common.changesSaved');
				}
			case NOTIFICATION_TYPE_FORM_ERROR:
			case NOTIFICATION_TYPE_ERROR:
				$notificationSettings = $this->getNotificationSettings($notification->getId());
				assert(!is_null($notificationSettings['contents']));
				return $notificationSettings['contents'];
			case NOTIFICATION_TYPE_PLUGIN_ENABLED:
				return $this->_getTranslatedKeyWithParameters('common.pluginEnabled', $notification->getId());
			case NOTIFICATION_TYPE_PLUGIN_DISABLED:
				return $this->_getTranslatedKeyWithParameters('common.pluginDisabled', $notification->getId());
			case NOTIFICATION_TYPE_LOCALE_INSTALLED:
				return $this->_getTranslatedKeyWithParameters('admin.languages.localeInstalled', $notification->getId());
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				return __('notification.type.newAnnouncement');
			default:
				return $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array(&$request, &$notification)
				);
		}
	}

	/**
	 * Using the notification message, construct, if needed, any additional
	 * content for the notification body. If a specific notification type
	 * is not defined, it will return the string from getNotificationMessage
	 * method for that type.
	 * Define a notification type case on this method only if you need to
	 * present more than just text in notification. If you need to define
	 * just a locale key, use the getNotificationMessage method only.
	 * @see INotificationInfoProvider::getNotificationContents()
	 */
	public function getNotificationContents(&$request, &$notification) {
		$type = $notification->getType();
		assert(isset($type));
		$notificationMessage = $this->getNotificationMessage($request, $notification);
		$notificationContent = null;

		switch ($type) {
			case NOTIFICATION_TYPE_FORM_ERROR:
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('errors', $notificationMessage);
				return $templateMgr->fetch('controllers/notification/formErrorNotificationContent.tpl');
			case NOTIFICATION_TYPE_ERROR:
				if (is_array($notificationMessage)) {
					$templateMgr->assign('errors', $notificationMessage);
					return $templateMgr->fetch('controllers/notification/errorNotificationContent.tpl');
				} else {
					return $notificationMessage;
				}
			default:
				$notificationContent = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array(&$request, &$notification)
				);
				break;
		}

		if ($notificationContent) {
			return $notificationContent;
		} else {
			return $notificationMessage;
		}
	}

	/**
	 * @see INotificationInfoProvider::getNotificationContents()
	 */
	public function getNotificationTitle(&$notification) {
		$type = $notification->getType();
		assert(isset($type));
		$notificationTitle = null;

		switch ($type) {
			case NOTIFICATION_TYPE_FORM_ERROR:
				return __('form.errorsOccurred');
			default:
				$notificationTitle = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array(&$notification)
				);
				break;
		}

		if ($notificationTitle) {
			return $notificationTitle;
		} else {
			return __('notification.notification');
		}
	}

	/**
	 * @see INotificationInfoProvider::getNotificationContents()
	 */
	public function getStyleClass(&$notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_SUCCESS: return NOTIFICATION_STYLE_CLASS_SUCCESS;
			case NOTIFICATION_TYPE_WARNING: return NOTIFICATION_STYLE_CLASS_WARNING;
			case NOTIFICATION_TYPE_ERROR: return NOTIFICATION_STYLE_CLASS_ERROR;
			case NOTIFICATION_TYPE_INFORMATION: return NOTIFICATION_STYLE_CLASS_INFORMATION;
			case NOTIFICATION_TYPE_FORBIDDEN: return NOTIFICATION_STYLE_CLASS_FORBIDDEN;
			case NOTIFICATION_TYPE_HELP: return NOTIFICATION_STYLE_CLASS_HELP;
			case NOTIFICATION_TYPE_FORM_ERROR: return NOTIFICATION_STYLE_CLASS_FORM_ERROR;
			default:
				$notificationStyleClass = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array(&$notification)
				);
				break;
		}

		if ($notificationStyleClass) {
			return $notificationStyleClass;
		} else {
			return '';
		}
	}

	/**
	 * @see INotificationInfoProvider::getNotificationContents()
	 */
	public function getIconClass(&$notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_SUCCESS: return 'notifyIconSuccess';
			case NOTIFICATION_TYPE_WARNING: return 'notifyIconWarning';
			case NOTIFICATION_TYPE_ERROR: return 'notifyIconError';
			case NOTIFICATION_TYPE_INFORMATION: return 'notifyIconInfo';
			case NOTIFICATION_TYPE_FORBIDDEN: return 'notifyIconForbidden';
			case NOTIFICATION_TYPE_HELP: return 'notifyIconHelp';
			default:
				$notificationIconClass = $this->getByDelegate(
					$notification->getType(),
					$notification->getAssocType(),
					$notification->getAssocId(),
					__FUNCTION__,
					array(&$notification)
				);
				break;
		}
		if ($notificationIconClass) {
			return $notificationIconClass;
		} else {
			return 'notifyIconPageAlert';
		}
	}

	/**
	 * @see INotificationInfoProvider::isVisibleToAllUsers()
	 */
	public function isVisibleToAllUsers($notificationType, $assocType, $assocId) {
		switch ($notificationType) {
			default:
				$isVisible = $this->getByDelegate(
					$notificationType,
					$assocType,
					$assocId,
					__FUNCTION__,
					array($notificationType, $assocType, $assocId)
				);
				break;
		}

		if (!is_null($isVisible)) {
			return $isVisible;
		} else {
			return false;
		}
	}

	/**
	 * Update notifications by type using a delegate. If you want to be able to use
	 * this method to update notifications associated with a certain type, you need
	 * to first create a manager delegate and define it in getMgrDelegate() method.
	 * @param $request PKPRequest
	 * @param $notificationTypes array The type(s) of the notification(s) to
	 * be updated.
	 * @param $userIds array The notification user(s) id(s).
	 * @param $assocType int The notification associated object type.
	 * @param $assocId int The notification associated object id.
	 * @return mixed Return false if no operation is executed or the last operation
	 * returned value.
	 */
	final public function updateNotification(&$request, $notificationTypes = array(), $userIds = array(), $assocType, $assocId) {
		$returner = false;
		foreach ($notificationTypes as $type) {
			$managerDelegate = $this->getMgrDelegate($type, $assocType, $assocId);
			if (!is_null($managerDelegate) && is_a($managerDelegate, 'NotificationManagerDelegate')) {
				$returner = $managerDelegate->updateNotification($request, $userIds, $assocType, $assocId);
			} else {
				assert(false);
			}
		}

		return $returner;
	}


	//
	// Protected methods
	//
	/**
	 * Get the notification manager delegate based on the passed notification type.
	 * @param $notificationType int
	 * @param $assocType int
	 * @param $assocId int
	 * @return mixed Null or NotificationManagerDelegate
	 */
	protected function getMgrDelegate($notificationType, $assocType, $assocId) {
		return null;
	}

	/**
	 * Try to use a delegate to retrieve a notification data that's defined
	 * by the implementation of the
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @param $operationName string
	 */
	protected function getByDelegate($notificationType, $assocType, $assocId, $operationName, $parameters) {
		$delegate = $this->getMgrDelegate($notificationType, $assocType, $assocId);
		if (is_a($delegate, 'NotificationManagerDelegate')) {
			return call_user_func_array(array($delegate, $operationName), $parameters);
		} else {
			return null;
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Return notification settings.
	 * @param $notificationId int
	 * @return Array
	 */
	private function getNotificationSettings($notificationId) {
		$notificationSettingsDao =& DAORegistry::getDAO('NotificationSettingsDAO'); /* @var $notificationSettingsDao NotificationSettingsDAO */
		$notificationSettings = $notificationSettingsDao->getNotificationSettings($notificationId);
		if (empty($notificationSettings)) {
			return null;
		} else {
			return $notificationSettings;
		}
	}

	/**
	 * Helper function to get a translated string from a notification with parameters
	 * @param $key string
	 * @param $notificationId int
	 * @return String
	 */
	private function _getTranslatedKeyWithParameters($key, $notificationId) {
		$params = $this->getNotificationSettings($notificationId);
		return __($key, $this->getParamsForCurrentLocale($params));
	}
}

?>
