<?php
/**
 * @defgroup user_form User Forms
 */

/**
 * @file classes/user/form/RegistrationForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RegistrationForm
 * @ingroup user_form
 *
 * @brief Form for user registration.
 */

namespace PKP\user\form;

use APP\core\Application;
use APP\facades\Repo;
use APP\i18n\AppLocale;
use APP\notification\form\NotificationSettingsForm;
use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\form\Form;
use PKP\mail\MailTemplate;
use PKP\notification\PKPNotification;
use PKP\security\AccessKeyManager;
use PKP\security\Role;
use PKP\security\Validation;
use PKP\session\SessionManager;
use PKP\user\InterestManager;

class RegistrationForm extends Form
{
    /** @var User The user object being created (available to hooks during registrationform::execute hook) */
    public $user;

    /** @var boolean user is already registered with another context */
    public $existingUser;

    /** @var AuthPlugin default authentication source, if specified */
    public $defaultAuth;

    /** @var boolean whether or not captcha is enabled for this form */
    public $captchaEnabled;

    /**
     * Constructor.
     */
    public function __construct($site)
    {
        parent::__construct('frontend/pages/userRegister.tpl');

        // Validation checks for this form
        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', [Repo::user()->dao, 'getByUsername'], [], true));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorUsername($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
        $this->addCheck(new \PKP\form\validation\FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthRestriction', '>=', $site->getMinPasswordLength()));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', function ($password) use ($form) {
            return $password == $form->getData('password2');
        }));

        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'givenName', 'required', 'user.profile.form.givenNameRequired'));

        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'country', 'required', 'user.profile.form.countryRequired'));

        // Email checks
        $this->addCheck(new \PKP\form\validation\FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', [DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'], [], true));

        $this->captchaEnabled = Config::getVar('captcha', 'captcha_on_register') && Config::getVar('captcha', 'recaptcha');
        if ($this->captchaEnabled) {
            $request = Application::get()->getRequest();
            $this->addCheck(new \PKP\form\validation\FormValidatorReCaptcha($this, $request->getRemoteAddr(), 'common.captcha.error.invalid-input-response', $request->getServerHost()));
        }

        $authDao = DAORegistry::getDAO('AuthSourceDAO'); /** @var AuthSourceDAO $authDao */
        $this->defaultAuth = $authDao->getDefaultPlugin();
        if (isset($this->defaultAuth)) {
            $auth = $this->defaultAuth;
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', function ($username) use ($form, $auth) {
                return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData('password')));
            }));
        }

        $context = Application::get()->getRequest()->getContext();
        if ($context && $context->getData('privacyStatement')) {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'privacyConsent', 'required', 'user.profile.form.privacyConsentRequired'));
        }

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $site = $request->getSite();

        if ($this->captchaEnabled) {
            $templateMgr->assign('recaptchaPublicKey', Config::getVar('captcha', 'recaptcha_public_key'));
        }

        $isoCodes = new \Sokil\IsoCodes\IsoCodesFactory();
        $countries = [];
        foreach ($isoCodes->getCountries() as $country) {
            $countries[$country->getAlpha2()] = $country->getLocalName();
        }
        asort($countries);
        $templateMgr->assign('countries', $countries);

        $userFormHelper = new UserFormHelper();
        $userFormHelper->assignRoleContent($templateMgr, $request);

        $templateMgr->assign([
            'source' => $request->getUserVar('source'),
            'minPasswordLength' => $site->getMinPasswordLength(),
            'enableSiteWidePrivacyStatement' => Config::getVar('general', 'sitewide_privacy_statement'),
            'siteWidePrivacyStatement' => $site->getData('privacyStatement'),
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData()
    {
        $this->_data = [
            'userLocales' => [],
            'userGroupIds' => [],
        ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        parent::readInputData();

        $this->readUserVars([
            'username',
            'password',
            'password2',
            'givenName',
            'familyName',
            'affiliation',
            'email',
            'country',
            'interests',
            'emailConsent',
            'privacyConsent',
            'readerGroup',
            'reviewerGroup',
        ]);

        if ($this->captchaEnabled) {
            $this->readUserVars([
                'g-recaptcha-response',
            ]);
        }

        // Collect the specified user group IDs into a single piece of data
        $this->setData('userGroupIds', array_merge(
            array_keys((array) $this->getData('readerGroup')),
            array_keys((array) $this->getData('reviewerGroup'))
        ));
    }

    /**
     * @copydoc Form::validate()
     */
    public function validate($callHooks = true)
    {
        $request = Application::get()->getRequest();

        // Ensure the consent checkbox has been completed for the site and any user
        // group signups if we're in the site-wide registration form
        if (!$request->getContext()) {
            if ($request->getSite()->getData('privacyStatement')) {
                $privacyConsent = $this->getData('privacyConsent');
                if (!is_array($privacyConsent) || !array_key_exists(Application::CONTEXT_ID_NONE, $privacyConsent)) {
                    $this->addError('privacyConsent[' . Application::CONTEXT_ID_NONE . ']', __('user.register.form.missingSiteConsent'));
                }
            }

            if (!Config::getVar('general', 'sitewide_privacy_statement')) {
                $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
                $contextIds = [];
                foreach ($this->getData('userGroupIds') as $userGroupId) {
                    $userGroup = $userGroupDao->getById($userGroupId);
                    $contextIds[] = $userGroup->getContextId();
                }

                $contextIds = array_unique($contextIds);
                if (!empty($contextIds)) {
                    $contextDao = Application::getContextDao();
                    $privacyConsent = (array) $this->getData('privacyConsent');
                    foreach ($contextIds as $contextId) {
                        $context = $contextDao->getById($contextId);
                        if ($context->getData('privacyStatement') && !array_key_exists($contextId, $privacyConsent)) {
                            $this->addError('privacyConsent[' . $contextId . ']', __('user.register.form.missingContextConsent'));
                            break;
                        }
                    }
                }
            }
        }

        return parent::validate($callHooks);
    }

    /**
     * Register a new user.
     *
     * @return int|null User ID, or false on failure
     */
    public function execute(...$functionArgs)
    {
        $requireValidation = Config::getVar('email', 'require_validation');
        $userDao = DAORegistry::getDAO('UserDAO'); /** @var UserDAO $userDao */

        // New user
        $this->user = $user = $userDao->newDataObject();

        $user->setUsername($this->getData('username'));

        // The multilingual user data (givenName, familyName and affiliation) will be saved
        // in the current UI locale and copied in the site's primary locale too
        $request = Application::get()->getRequest();
        $site = $request->getSite();
        $sitePrimaryLocale = $site->getPrimaryLocale();
        $currentLocale = AppLocale::getLocale();

        // Set the base user fields (name, etc.)
        $user->setGivenName($this->getData('givenName'), $currentLocale);
        $user->setFamilyName($this->getData('familyName'), $currentLocale);
        $user->setEmail($this->getData('email'));
        $user->setCountry($this->getData('country'));
        $user->setAffiliation($this->getData('affiliation'), $currentLocale);

        if ($sitePrimaryLocale != $currentLocale) {
            $user->setGivenName($this->getData('givenName'), $sitePrimaryLocale);
            $user->setFamilyName($this->getData('familyName'), $sitePrimaryLocale);
            $user->setAffiliation($this->getData('affiliation'), $sitePrimaryLocale);
        }

        $user->setDateRegistered(Core::getCurrentDate());
        $user->setInlineHelp(1); // default new users to having inline help visible.

        if (isset($this->defaultAuth)) {
            $user->setPassword($this->getData('password'));
            // FIXME Check result and handle failures
            $this->defaultAuth->doCreateUser($user);
            $user->setAuthId($this->defaultAuth->authId);
        }
        $user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

        if ($requireValidation) {
            // The account should be created in a disabled
            // state.
            $user->setDisabled(true);
            $user->setDisabledReason(__('user.login.accountNotValidated', ['email' => $this->getData('email')]));
        }

        parent::execute(...$functionArgs);

        $userDao->insertObject($user);
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        // Associate the new user with the existing session
        $sessionManager = SessionManager::getManager();
        $session = $sessionManager->getUserSession();
        $session->setSessionVar('username', $user->getUsername());

        // Save the selected roles or assign the Reader role if none selected
        if ($request->getContext() && !$this->getData('reviewerGroup')) {
            $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
            $defaultReaderGroup = $userGroupDao->getDefaultByRoleId($request->getContext()->getId(), Role::ROLE_ID_READER);
            if ($defaultReaderGroup) {
                $userGroupDao->assignUserToGroup($user->getId(), $defaultReaderGroup->getId(), $request->getContext()->getId());
            }
        } else {
            $userFormHelper = new UserFormHelper();
            $userFormHelper->saveRoleContent($this, $user);
        }

        // Save the email notification preference
        if ($request->getContext() && !$this->getData('emailConsent')) {

            // Get the public notification types
            $notificationSettingsForm = new NotificationSettingsForm();
            $notificationCategories = $notificationSettingsForm->getNotificationSettingCategories();
            foreach ($notificationCategories as $notificationCategory) {
                if ($notificationCategory['categoryKey'] === 'notification.type.public') {
                    $publicNotifications = $notificationCategory['settings'];
                }
            }
            if (isset($publicNotifications)) {
                $notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO'); /** @var NotificationSubscriptionSettingsDAO $notificationSubscriptionSettingsDao */
                $notificationSubscriptionSettingsDao->updateNotificationSubscriptionSettings(
                    'blocked_emailed_notification',
                    $publicNotifications,
                    $user->getId(),
                    $request->getContext()->getId()
                );
            }
        }

        // Insert the user interests
        $interestManager = new InterestManager();
        $interestManager->setInterestsForUser($user, $this->getData('interests'));

        if ($requireValidation) {
            // Create an access key
            $accessKeyManager = new AccessKeyManager();
            $accessKey = $accessKeyManager->createKey('RegisterContext', $user->getId(), null, Config::getVar('email', 'validation_timeout'));

            // Send email validation request to user
            $mail = new MailTemplate('USER_VALIDATE');
            $this->_setMailFrom($request, $mail);
            $context = $request->getContext();
            $contextPath = $context ? $context->getPath() : null;
            $mail->assignParams([
                'userFullName' => $user->getFullName(),
                'contextName' => $context ? $context->getLocalizedName() : $site->getLocalizedTitle(),
                'activateUrl' => $request->url($contextPath, 'user', 'activateUser', [$this->getData('username'), $accessKey])
            ]);
            $mail->addRecipient($user->getEmail(), $user->getFullName());
            if (!$mail->send()) {
                $notificationMgr = new NotificationManager();
                $notificationMgr->createTrivialNotification($user->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
            }
            unset($mail);
        }
        return $userId;
    }

    /**
     * Set mail from address
     *
     * @param $request PKPRequest
     * @param $mail MailTemplate
     */
    public function _setMailFrom($request, $mail)
    {
        $site = $request->getSite();
        $context = $request->getContext();

        // Set the sender based on the current context
        if ($context && $context->getData('supportEmail')) {
            $mail->setReplyTo($context->getData('supportEmail'), $context->getData('supportName'));
        } else {
            $mail->setReplyTo($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\user\form\RegistrationForm', '\RegistrationForm');
}
