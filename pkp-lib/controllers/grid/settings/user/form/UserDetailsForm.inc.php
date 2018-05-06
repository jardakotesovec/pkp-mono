<?php

/**
 * @file controllers/grid/settings/user/form/UserDetailsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserDetailsForm
 * @ingroup controllers_grid_settings_user_form
 *
 * @brief Form for editing user profiles.
 */

import('lib.pkp.controllers.grid.settings.user.form.UserForm');

class UserDetailsForm extends UserForm {

	/** @var An optional author to base this user on */
	var $author;

	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $userId int optional
	 * @param $author Author optional
	 */
	function __construct($request, $userId = null, $author = null) {
		parent::__construct('controllers/grid/settings/user/form/userDetailsForm.tpl', $userId);

		if (isset($author)) {
			$this->author =& $author;
		} else {
			$this->author = null;
		}

		// the users register for the site, thus
		// the site primary locale is the required default locale
		$site = $request->getSite();
		$this->addSupportedFormLocale($site->getPrimaryLocale());

		// Validation checks for this form
		$form = $this;
		if ($userId == null) {
			$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array($this->userId, true), true));
			$this->addCheck(new FormValidatorUsername($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));

			if (!Config::getVar('security', 'implicit_auth')) {
				$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));
				$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthRestriction', '>=', $site->getMinPasswordLength()));
				$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', function($password) use ($form) {
					return $password == $form->getData('password2');
				}));
			}
		} else {
			$this->addCheck(new FormValidatorLength($this, 'password', 'optional', 'user.register.form.passwordLengthRestriction', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'optional', 'user.register.form.passwordsDoNotMatch', function($password) use ($form) {
				return $password == $form->getData('password2');
			}));
		}
		$this->addCheck(new FormValidatorLocale($this, 'givenName', 'required', 'user.profile.form.givenNameRequired', $site->getPrimaryLocale()));
		$this->addCheck(new FormValidatorCustom($this, 'familyName', 'optional', 'user.profile.form.givenNameRequired.locale', function($familyName) use ($form) {
			$givenNames = $form->getData('givenName');
			foreach ($familyName as $locale => $value) {
				if (!empty($value) && empty($givenNames[$locale])) {
					return false;
				}
			}
			return true;
		}));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($this->userId, true), true));
		$this->addCheck(new FormValidatorORCID($this, 'orcid', 'optional', 'user.orcid.orcidInvalid'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data from current user profile.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;

		$data = array();

		if (isset($this->userId)) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$user = $userDao->getById($this->userId);

			import('lib.pkp.classes.user.InterestManager');
			$interestManager = new InterestManager();

			$data = array(
				'authId' => $user->getAuthId(),
				'username' => $user->getUsername(),
				'givenName' => $user->getGivenName(null), // Localized
				'familyName' => $user->getFamilyName(null), // Localized
				'preferedPublicName' => $user->getPreferedPublicName(null), // Localized
				'signature' => $user->getSignature(null), // Localized
				'affiliation' => $user->getAffiliation(null), // Localized
				'email' => $user->getEmail(),
				'userUrl' => $user->getUrl(),
				'phone' => $user->getPhone(),
				'orcid' => $user->getOrcid(),
				'mailingAddress' => $user->getMailingAddress(),
				'country' => $user->getCountry(),
				'biography' => $user->getBiography(null), // Localized
				'interests' => $interestManager->getInterestsForUser($user),
				'userLocales' => $user->getLocales(),
			);
			import('classes.core.ServicesContainer');
			$userService = ServicesContainer::instance()->get('user');
			$data['canCurrentUserGossip'] = $userService->canCurrentUserGossip($user->getId());
			if ($data['canCurrentUserGossip']) {
				$data['gossip'] = $user->getGossip();
			}
		} else if (isset($this->author)) {
			$author = $this->author;
			$data = array(
				'givenName' => $author->getGivenName(null), // Localized
				'familyName' => $author->getFamilyName(null), // Localized
				'affiliation' => $author->getAffiliation(null), // Localized
				'preferedPublicName' => $author->getPreferedPublicName(null), // Localized
				'email' => $author->getEmail(),
				'userUrl' => $author->getUrl(),
				'orcid' => $author->getOrcid(),
				'country' => $author->getCountry(),
				'biography' => $author->getBiography(null), // Localized
			);
		} else {
			$data = array(
				'mustChangePassword' => true,
			);
		}
		foreach($data as $key => $value) {
			$this->setData($key, $value);
		}

		parent::initData($args, $request);
	}

	/**
	 * Display the form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {
		$site = $request->getSite();
		$templateMgr = TemplateManager::getManager($request);
		$userDao = DAORegistry::getDAO('UserDAO');

		$templateMgr->assign(array(
			'minPasswordLength' => $site->getMinPasswordLength(),
			'source' => $request->getUserVar('source'),
			'userId' => $this->userId,
			'sitePrimaryLocale' => $site->getPrimaryLocale(),
		));

		if (isset($this->userId)) {
			$user = $userDao->getById($this->userId);
			$templateMgr->assign('username', $user->getUsername());
		}

		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$templateMgr->assign('countries', $countryDao->getCountries());

		$authDao = DAORegistry::getDAO('AuthSourceDAO');
		$authSources =& $authDao->getSources();
		$authSourceOptions = array();
		foreach ($authSources->toArray() as $auth) {
			$authSourceOptions[$auth->getAuthId()] = $auth->getTitle();
		}
		if (!empty($authSourceOptions)) {
			$templateMgr->assign('authSourceOptions', $authSourceOptions);
		}

		return parent::display($args, $request);
	}


	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		parent::readInputData();

		$this->readUserVars(array(
			'authId',
			'password',
			'password2',
			'givenName',
			'familyName',
			'preferedPublicName',
			'signature',
			'affiliation',
			'email',
			'userUrl',
			'phone',
			'orcid',
			'mailingAddress',
			'country',
			'biography',
			'gossip',
			'interests',
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
	}

	/**
	 * Get all locale field names
	 */
	function getLocaleFieldNames() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Create or update a user.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function &execute($args, $request) {
		parent::execute($args, $request);

		$userDao = DAORegistry::getDAO('UserDAO');
		$context = $request->getContext();

		if (isset($this->userId)) {
			$userId = $this->userId;
			$user = $userDao->getById($userId);
		}

		if (!isset($user)) {
			$user = $userDao->newDataObject();
			$user->setInlineHelp(1); // default new users to having inline help visible
		}

		$user->setGivenName($this->getData('givenName'), null); // Localized
		$user->setFamilyName($this->getData('familyName'), null); // Localized
		$user->setPreferedPublicName($this->getData('preferedPublicName'), null); // Localized
		$user->setAffiliation($this->getData('affiliation'), null); // Localized
		$user->setSignature($this->getData('signature'), null); // Localized
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setOrcid($this->getData('orcid'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setBiography($this->getData('biography'), null); // Localized
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);
		$user->setAuthId((int) $this->getData('authId'));
		// Users can never view/edit their own gossip fields
		import('classes.core.ServicesContainer');
		$userService = ServicesContainer::instance()->get('user');
		if ($userService->canCurrentUserGossip($user->getId())) {
			$user->setGossip($this->getData('gossip'));
		}

		$site = $request->getSite();
		$availableLocales = $site->getSupportedLocales();

		$locales = array();
		foreach ($this->getData('userLocales') as $locale) {
			if (AppLocale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
				array_push($locales, $locale);
			}
		}
		$user->setLocales($locales);

		if ($user->getAuthId()) {
			$authDao = DAORegistry::getDAO('AuthSourceDAO');
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
			$userId = $userDao->insertObject($user);

			if ($sendNotify) {
				// Send welcome email to user
				import('lib.pkp.classes.mail.MailTemplate');
				$mail = new MailTemplate('USER_REGISTER');
				$mail->setReplyTo($context->getSetting('contactEmail'), $context->getSetting('contactName'));
				$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password, 'userFullName' => $user->getFullName()));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
			}
		}

		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		$interestManager->setInterestsForUser($user, $this->getData('interests'));

		return $user;
	}
}

?>
