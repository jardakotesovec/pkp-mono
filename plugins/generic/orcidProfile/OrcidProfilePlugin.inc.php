<?php

/**
 * @file plugins/generic/orcidProfile/OrcidProfilePlugin.inc.php
 *
 * Copyright (c) 2015-2016 University of Pittsburgh
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrcidProfilePlugin
 * @ingroup plugins_generic_orcidProfile
 *
 * @brief ORCID Profile plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('ORCID_OAUTH_URL', 'https://orcid.org/oauth/');
define('ORCID_OAUTH_URL_SANDBOX', 'https://sandbox.orcid.org/oauth/');
define('ORCID_API_URL_PUBLIC', 'https://pub.orcid.org/');
define('ORCID_API_URL_PUBLIC_SANDBOX', 'https://pub.sandbox.orcid.org/');
define('ORCID_API_URL_MEMBER', 'https://api.orcid.org/');
define('ORCID_API_URL_MEMBER_SANDBOX', 'https://api.sandbox.orcid.org/');

define('OAUTH_TOKEN_URL', 'oauth/token');
define('ORCID_API_VERSION_URL', 'v1.2/');
define('ORCID_PROFILE_URL', 'orcid-profile');

class OrcidProfilePlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Register callback for Smarty filters; add CSS
			HookRegistry::register('TemplateManager::display', array($this, 'handleTemplateDisplay'));

			// Insert ORCID callback
			HookRegistry::register('LoadHandler', array($this, 'setupCallbackHandler'));

			// Send emails to authors without ORCID id upon submission
			HookRegistry::register('Author::Form::Submit::AuthorSubmitStep3Form::Execute', array($this, 'collectAuthorOrcidId'));

			// Add ORCiD hash to author DAO
			HookRegistry::register('authordao::getAdditionalFieldNames', array($this, 'authorSubmitGetFieldNames'));
		}
		return $success;
	}

	/**
	 * Get page handler path for this plugin.
	 * @return string Path to plugin's page handler
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'pages';
	}

	/**
	 * Hook callback: register pages for each sushi-lite method
	 * This URL is of the form: orcidapi/{$orcidrequest}
	 * @see PKPPageRouter::route()
	 */
	function setupCallbackHandler($hookName, $params) {
		$page = $params[0];
		if ($this->getEnabled() && $page == 'orcidapi') {
			$this->import('pages/OrcidHandler');
			define('HANDLER_CLASS', 'OrcidHandler');
			return true;
		}
		return false;
	}

	/**
	 * Hook callback: register output filter to add data citation to submission
	 * summaries; add data citation to reading tools' suppfiles and metadata views.
	 * @see TemplateManager::display()
	 */
	function handleTemplateDisplay($hookName, $args) {
		$templateMgr =& $args[0];
		$template =& $args[1];
		$request =& PKPApplication::getRequest();

		// Assign our private stylesheet.
		$templateMgr->addStylesheet($request->getBaseUrl() . '/' . $this->getStyleSheet());

		switch ($template) {
			case 'user/register.tpl':
				$templateMgr->register_outputfilter(array($this, 'registrationFilter'));
				break;
			case 'user/publicProfileForm.tpl':
				$templateMgr->register_outputfilter(array($this, 'profileFilter'));
				break;
			case 'author/submit/step3.tpl':
				$templateMgr->register_outputfilter(array($this, 'submitFilter'));
				break;
		}
		return false;
	}

	/**
	 * Return the OAUTH path (prod or sandbox) based on the current API configuration
	 * @return $string
	 */
	function getOauthPath() {
		$journal = Request::getJournal();
		$apiPath =  $this->getSetting($journal->getId(), 'orcidProfileAPIPath');
		if ($apiPath == ORCID_API_URL_PUBLIC || $apiPath == ORCID_API_URL_MEMBER) {
			return ORCID_OAUTH_URL;
		} else {
			return ORCID_OAUTH_URL_SANDBOX;
		}
	}

	/**
	 * Output filter adds ORCiD interaction to registration form.
	 * @param $output string
	 * @param $templateMgr TemplateManager
	 * @return $string
	 */
	function registrationFilter($output, &$templateMgr) {
		if (preg_match('/<form id="registerForm"[^>]+>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
			$match = $matches[0][0];
			$offset = $matches[0][1];
			$journal = Request::getJournal();

			$templateMgr->assign(array(
				'targetOp' => 'register',
				'orcidProfileOauthPath' => $this->getOauthPath(),
				'orcidClientId' => $this->getSetting($journal->getId(), 'orcidClientId'),
			));

			$newOutput = substr($output, 0, $offset);
			$newOutput .= $templateMgr->fetch($this->getTemplatePath() . 'orcidProfile.tpl');
			$newOutput .= substr($output, $offset);
			$output = $newOutput;
		}
		$templateMgr->unregister_outputfilter('registrationFilter');
		return $output;
	}

	/**
	 * Output filter adds ORCiD interaction to user profile form.
	 * @param $output string
	 * @param $templateMgr TemplateManager
	 * @return $string
	 */
	function profileFilter($output, &$templateMgr) {
		if (preg_match('/<label[^>]+for="orcid[^"]*"[^>]*>[^<]+<\/label>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
			$match = $matches[0][0];
			$offset = $matches[0][1];
			$journal = Request::getJournal();

			// Entering the registration without ORCiD; present the button.
			$templateMgr->assign(array(
				'targetOp' => 'profile',
				'orcidProfileOauthPath' => $this->getOauthPath(),
				'orcidClientId' => $this->getSetting($journal->getId(), 'orcidClientId'),
			));

			$newOutput = substr($output, 0, $offset+strlen($match));
			$newOutput .= $templateMgr->fetch($this->getTemplatePath() . 'orcidProfile.tpl');
			$newOutput .= '<script type="text/javascript">
			        $(document).ready(function() {
					$(\'#orcid\').attr(\'readonly\', "true");
				});
			</script>';
			$newOutput .= substr($output, $offset+strlen($match));
			$output = $newOutput;
		}
		$templateMgr->unregister_outputfilter('profileFilter');
		return $output;
	}

	/**
	 * Output filter adds ORCiD interaction to the 3rd step submission form.
	 * @param $output string
	 * @param $templateMgr TemplateManager
	 * @return $string
	 */
	function submitFilter($output, &$templateMgr) {
		if (preg_match('/<input type="text" class="textField" name="authors\[0\]\[orcid\][^>]+>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
			$match = $matches[0][0];
			$offset = $matches[0][1];
			$journal = Request::getJournal();

			// Entering the registration without ORCiD; present the button.
			$templateMgr->assign(array(
				'targetOp' => 'submit',
				'orcidProfileOauthPath' => $this->getOauthPath(),
				'orcidClientId' => $this->getSetting($journal->getId(), 'orcidClientId'),
				'params' => array('articleId' => Request::getUserVar('articleId')),
			));

			$newOutput = substr($output, 0, $offset + strlen($match) - 1);
			$newOutput .= ' readonly=\'readonly\'><br />';
			$newOutput .= $templateMgr->fetch($this->getTemplatePath() . 'orcidProfile.tpl');
			$newOutput .= '<button id="remove-orcid-button">Remove ORCID ID</button>
				<script>$("#remove-orcid-button").click(function(event) {
					event.preventDefault();
					$("#authors-0-orcid").val("");
					$("#connect-orcid-button").show();
				});</script>';
			$newOutput .= substr($output, $offset + strlen($match));
			$output = $newOutput;
		}
		$templateMgr->unregister_outputfilter('submitFilter');
		return $output;
	}

	/**
	 * Output filter adds ORCiD interaction to the 3rd step submission form.
	 * @param $output string
	 * @param $templateMgr TemplateManager
	 * @return $string
	 */
	function collectAuthorOrcidId($hookName, $params) {
		$author =& $params[0];
		$formAuthor =& $params[1];

		// if author has no orcid id
		if (!$author->getData('orcid')){
			$mail =& $this->getMailTemplate('ORCID_COLLECT_AUTHOR_ID');

			$orcidToken = md5(time());
			$author->setData('orcidToken', $orcidToken);

			$request =& PKPApplication::getRequest();
			$context = $request->getContext();

			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($author->getSubmissionId());

			$mail->assignParams(array(
				'authorOrcidUrl' => $this->getOauthPath() . 'authorize?' . http_build_query(array(
					'client_id' => $this->getSetting($context->getId(), 'orcidClientId'),
					'response_type' => 'code',
					'scope' => '/authenticate',
					'redirect_uri' => Request::url(null, 'orcidapi', 'orcidVerify', null, array('orcidToken' => $orcidToken, 'articleId' => $author->getSubmissionId()))
				)),
				'authorName' => $author->getFullName(),
				'editorialContactSignature' => $context->getSetting('contactName'),
				'articleTitle' => $article->getLocalizedTitle(),
			));

			// Send to author
			$mail->addRecipient($author->getEmail(), $author->getFullName());

			// Send the mail.
			$mail->send($request);
		}
		return false;
	}

	/**
	 * Add the author hash storage to the author record
	 * @param $hookName string
	 * @param $params array
	 */
	function authorSubmitGetFieldNames($hookName, $params) {
		$fields =& $params[1];
		$fields[] = 'orcidToken';
		return false;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.orcidProfile.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.orcidProfile.description');
	}

	/**
	 * @see PKPPlugin::getInstallEmailTemplatesFile()
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . '/emailTemplates.xml');
	}

	/**
	 * @see PKPPlugin::getInstallEmailTemplateDataFile()
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * @see Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * @see Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				$templateMgr = TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$apiOptions = array(
					ORCID_API_URL_PUBLIC => 'plugins.generic.orcidProfile.manager.settings.orcidProfileAPIPath.public',
					ORCID_API_URL_PUBLIC_SANDBOX => 'plugins.generic.orcidProfile.manager.settings.orcidProfileAPIPath.publicSandbox',
					ORCID_API_URL_MEMBER => 'plugins.generic.orcidProfile.manager.settings.orcidProfileAPIPath.member',
					ORCID_API_URL_MEMBER_SANDBOX => 'plugins.generic.orcidProfile.manager.settings.orcidProfileAPIPath.memberSandbox'
				);

				$templateMgr->assign_by_ref('orcidApiUrls', $apiOptions);

				$this->import('OrcidProfileSettingsForm');
				$form = new OrcidProfileSettingsForm($this, $context->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * Return the location of the plugin's CSS file
	 * @return string
	 */
	function getStyleSheet() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'orcidProfile.css';
	}

	/**
	 * Instantiate a MailTemplate
	 *
	 * @param $emailKey string
	 * @param $journal Journal
	 */
	function &getMailTemplate($emailKey, $journal = null) {
		if (!isset($this->_mailTemplates[$emailKey])) {
			import('classes.mail.MailTemplate');
			$mailTemplate = new MailTemplate($emailKey, null, null, $journal, true, true);
			$this->_mailTemplates[$emailKey] =& $mailTemplate;
		}
		return $this->_mailTemplates[$emailKey];
	}

}
?>
