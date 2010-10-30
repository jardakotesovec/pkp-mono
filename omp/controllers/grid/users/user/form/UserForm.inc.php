<?php

/**
 * @file controllers/grid/users/user/form/UserForm.inc.php 
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserForm
 * @ingroup controllers_grid_users_user_form
 *
 * @brief Form for editing user profiles.
 */

import('lib.pkp.classes.form.Form');

class UserForm extends Form {

	/** @var Id of the user being edited */
	var $userId;

	/**
	 * Constructor.
	 */
	function UserForm($userId = null) {
		parent::Form('controllers/grid/users/user/form/userForm.tpl');

		$this->userId = isset($userId) ? (int) $userId : null;
		$site =& Request::getSite();

		// Validation checks for this form
		if ($userId == null) {
			$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array($this->userId, true), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));

			if (!Config::getVar('security', 'implicit_auth')) {
				$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));
				$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
				$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			}
		} else {
			$this->addCheck(new FormValidatorLength($this, 'password', 'optional', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'optional', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		}
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($this->userId, true), true));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current user profile.
	 */
	function initData($args, &$request) {
		if (isset($this->userId)) {
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getUser($this->userId);
			$interestDao =& DAORegistry::getDAO('InterestDAO');

			// Get all available interests to populate the autocomplete with
			if ($interestDao->getAllUniqueInterests()) {
				$existingInterests = $interestDao->getAllUniqueInterests();
			} else $existingInterests = null;
			// Get the user's current set of interests
			if ($interestDao->getInterests($user->getId())) {
				$currentInterests = $interestDao->getInterests($user->getId());
			} else $currentInterests = null;

			if ($user != null) {
				$this->_data = array(
					'authId' => $user->getAuthId(),
					'username' => $user->getUsername(),
					'salutation' => $user->getSalutation(),
					'firstName' => $user->getFirstName(),
					'middleName' => $user->getMiddleName(),
					'lastName' => $user->getLastName(),
					'signature' => $user->getSignature(null), // Localized
					'initials' => $user->getInitials(),
					'gender' => $user->getGender(),
					'affiliation' => $user->getAffiliation(null), // Localized
					'email' => $user->getEmail(),
					'userUrl' => $user->getUrl(),
					'phone' => $user->getPhone(),
					'fax' => $user->getFax(),
					'mailingAddress' => $user->getMailingAddress(),
					'country' => $user->getCountry(),
					'biography' => $user->getBiography(null), // Localized
					'existingInterests' => $existingInterests,
					'interestsKeywords' => $currentInterests,
					'gossip' => $user->getGossip(null), // Localized
					'userLocales' => $user->getLocales()
				);

			} else {
				$this->userId = null;
			}
		}
	}

	/**
	 * Display the form.
	 */
	function display($args, &$request) {
		$site =& $request->getSite();
		$templateMgr =& TemplateManager::getManager();
		$userDao =& DAORegistry::getDAO('UserDAO');

		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign('source', Request::getUserVar('source'));
		$templateMgr->assign('userId', $this->userId);

		if (isset($this->userId)) {
			$user =& $userDao->getUser($this->userId);
			$templateMgr->assign('username', $user->getUsername());
			$helpTopicId = 'press.users.index';
		} else {
			$helpTopicId = 'press.users.createNewUser';
		}

		$templateMgr->assign('implicitAuth', Config::getVar('security', 'implicit_auth'));
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		$templateMgr->assign('helpTopicId', $helpTopicId);

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$authSources =& $authDao->getSources();
		$authSourceOptions = array();
		foreach ($authSources->toArray() as $auth) {
			$authSourceOptions[$auth->getAuthId()] = $auth->getTitle();
		}
		if (!empty($authSourceOptions)) {
			$templateMgr->assign('authSourceOptions', $authSourceOptions);
		}

		return $this->fetch($request);
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'authId',
			'password',
			'password2',
			'salutation',
			'firstName',
			'middleName',
			'lastName',
			'gender',
			'initials',
			'signature',
			'affiliation',
			'email',
			'userUrl',
			'phone',
			'fax',
			'mailingAddress',
			'country',
			'biography',
			'interestsKeywords',
			'gossip',
			'userLocales',
			'generatePassword',
			'sendNotify',
			'mustChangePassword'
		));
		if ($this->userId == null) {
			$this->readUserVars(array('username'));
		}

		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}

		if ($this->getData('username') != null) {
			// Usernames must be lowercase
			$this->setData('username', strtolower($this->getData('username')));
		}
	}

	/**
	 * Get all locale field names 
	 */
	function getLocaleFieldNames() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Create or update a user.
	 */
	function &execute($args, &$request) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$press =& $request->getPress();

		if (isset($this->userId)) {
			$userId = $this->userId; 
			$user =& $userDao->getUser($userId);
		}

		if (!isset($user)) {
			$user = new User();
		}

		$user->setSalutation($this->getData('salutation'));
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setInitials($this->getData('initials'));
		$user->setGender($this->getData('gender'));
		$user->setAffiliation($this->getData('affiliation'), null); // Localized
		$user->setSignature($this->getData('signature'), null); // Localized
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setBiography($this->getData('biography'), null); // Localized
		$user->setGossip($this->getData('gossip'), null); // Localized
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);
		$user->setAuthId((int) $this->getData('authId'));

		$site =& $request->getSite();
		$availableLocales = $site->getSupportedLocales();

		$locales = array();
		foreach ($this->getData('userLocales') as $locale) {
			if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
				array_push($locales, $locale);
			}
		}
		$user->setLocales($locales);

		if ($user->getAuthId()) {
			$authDao =& DAORegistry::getDAO('AuthSourceDAO');
			$auth =& $authDao->getPlugin($user->getAuthId());
		}

		if ($user->getId() != null) {
			if ($this->getData('password') !== '') {
				if (isset($auth)) {
					$auth->doSetUserPassword($user->getUsername(), $this->getData('password'));
					$user->setPassword(Validation::encryptCredentials($user->getId(), Validation::generatePassword())); // Used for PW reset hash only
				} else {
					$user->setPassword(Validation::encryptCredentials($user->getUsername(), $this->getData('password')));
				}
			}

			if (isset($auth)) {
				// FIXME Should try to create user here too?
				$auth->doSetUserInfo($user);
			}

			$userDao->updateObject($user);

		} else {
			$user->setUsername($this->getData('username'));
			if ($this->getData('generatePassword')) {
				$password = Validation::generatePassword();
				$sendNotify = true;
			} else {
				$password = $this->getData('password');
				$sendNotify = $this->getData('sendNotify');
			}

			if (isset($auth)) {
				$user->setPassword($password);
				// FIXME Check result and handle failures
				$auth->doCreateUser($user);
				$user->setAuthId($auth->authId);
				$user->setPassword(Validation::encryptCredentials($user->getId(), Validation::generatePassword())); // Used for PW reset hash only
			} else {
				$user->setPassword(Validation::encryptCredentials($this->getData('username'), $password));
			}

			$user->setDateRegistered(Core::getCurrentDate());
			$userId = $userDao->insertUser($user);

			$isManager = Validation::isPressManager();

			if ($sendNotify) {
				// Send welcome email to user
				import('classes.mail.MailTemplate');
				$mail = new MailTemplate('USER_REGISTER');
				$mail->setFrom($press->getSetting('contactEmail'), $press->getSetting('contactName'));
				$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password, 'userFullName' => $user->getFullName()));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
			}
		}

		// Add reviewing interests to interests table
 		$interestDao =& DAORegistry::getDAO('InterestDAO');
 		$interests = Request::getUserVar('interestsKeywords');
		$interestTextOnly = Request::getUserVar('interests');
		if(!empty($interestsTextOnly)) {
			// If JS is disabled, this will be the input to read
			$interestsTextOnly = explode(",", $interestTextOnly);
		} else $interestsTextOnly = null;
		if ($interestsTextOnly && !isset($interests)) {
			$interests = $interestsTextOnly;
		} elseif (isset($interests) && !is_array($interests)) {
			$interests = array($interests);
		}
		$interestDao->insertInterests($interests, $userId, true);

		return $user;
	}
}

?>
