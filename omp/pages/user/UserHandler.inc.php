<?php

/**
 * @file pages/user/UserHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

// $Id$


import('handler.Handler');

class UserHandler extends Handler {
	/**
	 * Constructor
	 */
	function UserHandler() {
		parent::Handler();
	}
	
	/**
	 * Display user index page.
	 */
	function index() {
		$this->validate();

		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$this->setupTemplate();
		$templateMgr =& TemplateManager::getManager();

		$press =& Request::getPress();
		$templateMgr->assign('helpTopicId', 'user.userHome');

		$user =& Request::getUser();
		$workflowDao =& DAORegistry::getDAO('WorkflowDAO');

		if ($press == null) {
			// Prevent variable clobbering
			unset($press);

			// Show roles for all presses
			$pressDao =& DAORegistry::getDAO('PressDAO');
			$presses =& $pressDao->getPresses();

			$allPresses = array();
			$pressesToDisplay = array();
			$rolesToDisplay = array();

			// Fetch the user's roles for each press
			while ($press =& $presses->next()) {
				$roles =& $roleDao->getRolesByUserId($session->getUserId(), $press->getId());
				if (!empty($roles)) {
					$pressesToDisplay[] = $press;
					$rolesToDisplay[$press->getId()] =& $roles;
				}
				if ($press->getEnabled()) $allPresses[] =& $press;
				unset($press);
			}

			$templateMgr->assign_by_ref('allPresses', $allPresses);
			$templateMgr->assign('showAllPresses', 1);
			$templateMgr->assign_by_ref('userPresses', $pressesToDisplay);

		} else { // Currently within a press' context.
			// Show roles for the currently selected press
			$roles =& $roleDao->getRolesByUserId($session->getUserId(), $press->getId());

	/*		import('payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			$membershipEnabled = $paymentManager->membershipEnabled();
			$templateMgr->assign('membershipEnabled', $membershipEnabled);
			$subscriptionEnabled = $paymentManager->acceptSubscriptionPayments();
			$templateMgr->assign('subscriptionEnabled', $subscriptionEnabled);

			if ( $subscriptionEnabled ) {
				import('subscription.SubscriptionDAO');
				$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
				$subscriptionId = $subscriptionDAO->getSubscriptionIdByUser($user->getUserId(), $press->getId());
				$templateMgr->assign('userHasSubscription', $subscriptionId);
				if ( $subscriptionId !== false ) {
					$subscription =& $subscriptionDAO->getSubscription($subscriptionId);
					$templateMgr->assign('subscriptionEndDate', $subscription->getDateEnd());
				}
			}

			if ( $membershipEnabled ) {
				$templateMgr->assign('dateEndMembership', $user->getSetting('dateEndMembership', 0));
			}
*/

			$signoffTasks =& $workflowDao->getSignoffTasksByUserId($user->getUserId());
			$templateMgr->assign('signoffTasks', $signoffTasks);

			$templateMgr->assign('allowRegAuthor', $press->getSetting('allowRegAuthor'));
			$templateMgr->assign('allowRegReviewer', $press->getSetting('allowRegReviewer'));

			$rolesToDisplay[$press->getId()] =& $roles;
			$templateMgr->assign_by_ref('userPress', $press);
		}

		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, $session->getUserId(), ROLE_ID_SITE_ADMIN));
		$templateMgr->assign('userRoles', $rolesToDisplay);
		$templateMgr->display('user/index.tpl');
	}

	/**
	 * Change the locale for the current user.
	 * @param $args array first parameter is the new locale
	 */
	function setLocale($args) {
		$setLocale = isset($args[0]) ? $args[0] : null;

		$site =& Request::getSite();
		$press =& Request::getPress();
		if ($press != null) {
			$pressSupportedLocales = $press->getSetting('supportedLocales');
			if (!is_array($pressSupportedLocales)) {
				$pressSupportedLocales = array();
			}
		}

		if (Locale::isLocaleValid($setLocale) && (!isset($pressSupportedLocales) || in_array($setLocale, $pressSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session =& Request::getSession();
			$session->setSessionVar('currentLocale', $setLocale);
		}

		if(isset($_SERVER['HTTP_REFERER'])) {
			Request::redirectUrl($_SERVER['HTTP_REFERER']);
		}

		$source = Request::getUserVar('source');
		if (isset($source) && !empty($source)) {
			Request::redirectUrl(Request::getProtocol() . '://' . Request::getServerHost() . $source, false);
		}

		Request::redirect(null, 'index');
	}

	/**
	 * Become a given role.
	 */
	function become($args) {
		parent::validate(true, true);
		$press =& Request::getPress();
		$user =& Request::getUser();
		if (!$user) Request::redirect(null, null, 'index');

		switch (array_shift($args)) {
			case 'author':
				$roleId = ROLE_ID_AUTHOR;
				$setting = 'allowRegAuthor';
				$deniedKey = 'user.noRoles.submitArticleRegClosed';
				break;
			case 'reviewer':
				$roleId = ROLE_ID_REVIEWER;
				$setting = 'allowRegReviewer';
				$deniedKey = 'user.noRoles.regReviewerClosed';
				break;
			default:
				Request::redirect(null, null, 'index');
		}

		if ($press->getSetting($setting)) {
			$role =& new Role();
			$role->setPressId($press->getId());
			$role->setRoleId($roleId);
			$role->setUserId($user->getUserId());

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->insertRole($role);
			Request::redirectUrl(Request::getUserVar('source'));
		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('message', $deniedKey);
			return $templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Validate that user is logged in.
	 * Redirects to login form if not logged in.
	 * @param $loginCheck boolean check if user is logged in
	 */
	function validate($loginCheck = true) {
		parent::validate();
		if ($loginCheck && !Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'user'), 'navigation.user')));
		}
	}

	function workflowSignoff($args) {
		$this->validate();
		$processId = (int) array_shift($args);
		$workflowDao =& DAORegistry::getDAO('WorkflowDAO');
		$user =& Request::getUser();
		$process =& $workflowDao->getById($processId);

		if ($process != null) {
			$workflowDao->workflowSignoff($user->getUserId(), $processId);
			$workflowDao->proceed($process->getMonographId());
		}

  		Request::redirect(null, 'user');
	}

	//
	// Captcha
	//

	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		if ($captchaManager->isEnabled()) {
			$captchaDao =& DAORegistry::getDAO('CaptchaDAO');
			$captcha =& $captchaDao->getCaptcha($captchaId);
			if ($captcha) {
				$captchaManager->generateImage($captcha);
				exit();
			}
		}
		Request::redirect(null, 'user');
	}

	/**
	 * View the public user profile for a user, specified by user ID,
	 * if that user should be exposed for public view.
	 */
	function viewPublicProfile($args) {
		$this->validate(false);
		$templateMgr =& TemplateManager::getManager();
		$userId = (int) array_shift($args);

		$accountIsVisible = false;

		// Ensure that the user's profile info should be exposed:

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		if ($commentDao->attributedCommentsExistForUser($userId)) {
			// At least one comment is attributed to the user
			$accountIsVisible = true;
		}

		if(!$accountIsVisible) Request::redirect(null, 'index');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUser($userId);

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->display('user/publicProfile.tpl');
	}


	//
	// Payments
	//

	function payRenewSubscription($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		import('subscription.SubscriptionDAO');
		$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
		$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');

		$press =& Request::getMonograph();
		if ($press) {
			$user =& Request::getUser();
			$subscriptionId = $subscriptionDAO->getSubscriptionIdByUser($user->getUserId(), $press->getId());

			$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
			$subscription =& $subscriptionDAO->getSubscription($subscriptionId);
			$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());

			$queuedPayment =& $paymentManager->createQueuedPayment($press->getId(), PAYMENT_TYPE_SUBSCRIPTION, $user->getUserId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
			$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

			$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
		}

	}

	function payMembership($args) {
		$this->validate();
		$this->setupTemplate();

		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		$press =& Request::getMonograph();
		$user =& Request::getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($press->getId(), PAYMENT_TYPE_MEMBERSHIP, $user->getUserId(), null,  $press->getSetting('membershipFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);

	}
}

?>
