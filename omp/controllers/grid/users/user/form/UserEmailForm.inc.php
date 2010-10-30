<?php

/**
 * @file controllers/grid/users/user/form/UserEmailForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserEmailForm
 * @ingroup controllers_grid_users_user_form
 *
 * @brief Form for sending an email to a user
 */

import('lib.pkp.classes.form.Form');

class UserEmailForm extends Form {

	/* @var the user id of user to send email to */
	var $userId;

	/**
	 * Constructor.
	 */
	function UserEmailForm($userId) {
		parent::Form('controllers/grid/users/user/form/userEmailForm.tpl');

		$this->userId = (int) $userId;

		$this->addCheck(new FormValidator($this, 'subject', 'required', 'email.subjectRequired'));
		$this->addCheck(new FormValidator($this, 'message', 'required', 'email.bodyRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData($args, &$request) {
		$fromUser =& $request->getUser();
		$fromSignature = "\n\n\n" . $fromUser->getLocalizedSignature();

		$this->_data = array(
			'message' => $fromSignature
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'subject',
				'message'
			)
		);

	}

	/**
	 * Display the form.
	 */
	function display($args, &$request) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUser($this->userId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('userId', $this->userId);
		$templateMgr->assign('userFullName', $user->getFullName());
		$templateMgr->assign('userEmail', $user->getEmail());

		return $this->fetch($request);
	}

	/**
	 * Send the email 
	 */
	function execute($args, &$request) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$toUser =& $userDao->getUser($this->userId);
		$fromUser =& $request->getUser();

		import('lib.pkp.classes.mail.Mail');
		$email = new Mail();

		$email->addRecipient($toUser->getEmail(), $toUser->getFullName());
		$email->setFrom($fromUser->getEmail(), $fromUser->getFullName());
		$email->setSubject($this->getData('subject'));
		$email->setBody($this->getData('message'));
		$email->send();
	}
}

?>
