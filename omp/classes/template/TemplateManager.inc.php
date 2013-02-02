<?php

/**
 * @file classes/template/TemplateManager.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 *
 */

import('classes.file.PublicFileManager');
import('lib.pkp.classes.template.PKPTemplateManager');

class TemplateManager extends PKPTemplateManager {
	/**
	 * Constructor.
	 * Initialize template engine and assign basic template variables.
	 * @param $request PKPRequest FIXME: is optional for backwards compatibility only - make mandatory
	 */
	function TemplateManager($request = null) {
		parent::PKPTemplateManager($request);

		// Retrieve the router
		$router = $this->request->getRouter();
		assert(is_a($router, 'PKPRouter'));

		// Are we using implicit authentication?
		$this->assign('implicitAuth', Config::getVar('security', 'implicit_auth'));

		if (!defined('SESSION_DISABLE_INIT')) {
			/**
			 * Kludge to make sure no code that tries to connect to
			 * the database is executed (e.g., when loading
			 * installer pages).
			 */

			$context = $router->getContext($this->request);
			$site = $this->request->getSite();

			$publicFileManager = new PublicFileManager();
			$siteFilesDir = $this->request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath();
			$this->assign('sitePublicFilesDir', $siteFilesDir);
			$this->assign('publicFilesDir', $siteFilesDir); // May be overridden by press

			$siteStyleFilename = $publicFileManager->getSiteFilesPath() . '/' . $site->getSiteStyleFilename();
			if (file_exists($siteStyleFilename)) $this->addStyleSheet($this->request->getBaseUrl() . '/' . $siteStyleFilename);

			$this->assign('homeContext', array());
			if (isset($context)) {
				$this->assign('currentPress', $context);

				// Assign context settings.
				$contextSettingsDao = $context->getSettingsDAO();
				$this->assign('pressSettings', $contextSettingsDao->getSettings($context->getId()));

				$this->assign('siteTitle', $context->getLocalizedName());
				$this->assign('publicFilesDir', $this->request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getAssocType(), $context->getId()));

				$this->assign('primaryLocale', $context->getPrimaryLocale());
				$this->assign('alternateLocales', $context->getSetting('alternateLocales'));

				// Assign page header
				$this->assign('displayPageHeaderTitle', $context->getPageHeaderTitle());
				$this->assign('displayPageHeaderLogo', $context->getPageHeaderLogo());
				$this->assign('alternatePageHeader', $context->getLocalizedSetting('pageHeader'));
				$this->assign('metaSearchDescription', $context->getLocalizedSetting('searchDescription'));
				$this->assign('metaSearchKeywords', $context->getLocalizedSetting('searchKeywords'));
				$this->assign('metaCustomHeaders', $context->getLocalizedSetting('customHeaders'));
				$this->assign('numPageLinks', $context->getSetting('numPageLinks'));
				$this->assign('itemsPerPage', $context->getSetting('itemsPerPage'));
				$this->assign('enableAnnouncements', $context->getSetting('enableAnnouncements'));

				// Assign stylesheets and footer
				$styleSheet = $context->getSetting('styleSheet');
				if ($styleSheet) {
					$this->addStyleSheet($this->request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId(), $context->getId()) . '/' . $styleSheet['uploadName']);
				}

				// Include footer links if they have been defined.
				$footerCategoryDao = DAORegistry::getDAO('FooterCategoryDAO');
				$footerCategories = $footerCategoryDao->getNotEmptyByPressId($context->getId());
				$this->assign('footerCategories', $footerCategories->toArray());

				$footerLinkDao = DAORegistry::getDAO('FooterLinkDAO');
				$this->assign('maxLinks', $footerLinkDao->getLargestCategoryTotalByPressId($context->getId()));
				$this->assign('pageFooter', $context->getLocalizedSetting('pageFooter'));
			} else {
				// Add the site-wide logo, if set for this locale or the primary locale
				$displayPageHeaderTitle = $site->getLocalizedPageHeaderTitle();
				$this->assign('displayPageHeaderTitle', $displayPageHeaderTitle);
				if (isset($displayPageHeaderTitle['altText'])) $this->assign('displayPageHeaderTitleAltText', $displayPageHeaderTitle['altText']);

				$this->assign('siteTitle', $site->getLocalizedTitle());
			}

			// Check for multiple presses.
			$pressDao = DAORegistry::getDAO('PressDAO');

			$user = $this->request->getUser();
			if (is_a($user, 'User')) {
				$presses = $pressDao->getAll();
			} else {
				$presses = $pressDao->getEnabledPresses();
			}

			$multipleContexts = false;
			if ($presses->getCount() > 1) {
				$this->assign('multipleContexts', true);
				$multipleContexts = true;
			} else {
				if ($presses->getCount() == 0) { // no presses configured
					$this->assign('noContextsConfigured', true);
				}
			}

			if ($multipleContexts) {
				$this->_assignContextSwitcherData($presses, $context);
			}
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Get the press switcher data and assign it to
	 * the template manager.
	 * @param $contexts ItemIterator
	 * @param $currentContext Context
	 */
	function _assignContextSwitcherData(&$contexts, $currentContext = null) {
		$workingContexts = $contexts->toArray();

		$dispatcher = $this->request->getDispatcher();
		$contextsNameAndUrl = array();
		foreach ($workingContexts as $workingContext) {
			$contextUrl = $dispatcher->url($this->request, ROUTE_PAGE, $workingContext->getPath());
			$contextsNameAndUrl[$contextUrl] = $workingContext->getLocalizedName();
		};

		// Get the current context switcher value. We don´t need to worry about the
		// value when there is no current context, because then the switcher will not
		// be visible.
		$currentContextUrl = null;
		if ($currentContext) {
			$currentContextUrl = $dispatcher->url($this->request, ROUTE_PAGE, $currentContext->getPath());
		} else {
			$contextsNameAndUrl = array(__('press.select')) + $contextsNameAndUrl;
		}

		$this->assign('currentContextUrl', $currentContextUrl);
		$this->assign('contextsNameAndUrl', $contextsNameAndUrl);
	}
}

?>
