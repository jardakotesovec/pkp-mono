<?php

/**
 * @file pages/install/PKPInstallHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPInstallHandler
 * @ingroup pages_install
 *
 * @brief Handle installation requests.
 */


import('classes.install.form.InstallForm');
import('classes.install.form.UpgradeForm');
import('classes.handler.Handler');

class PKPInstallHandler extends Handler {
	/**
	 * Constructor
	 */
	function PKPInstallHandler() {
		parent::Handler();
	}

	/**
	 * If no context is selected, list all.
	 * Otherwise, display the index page for the selected context.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		// Make sure errors are displayed to the browser during install.
		@ini_set('display_errors', true);

		$this->validate($request);
		$this->setupTemplate($request);

		if (($setLocale = $request->getUserVar('setLocale')) != null && AppLocale::isLocaleValid($setLocale)) {
			$request->setCookieVar('currentLocale', $setLocale);
		}

		$installForm = new InstallForm($request);
		$installForm->initData();
		$installForm->display();
	}

	/**
	 * Redirect to index if system has already been installed.
	 * @param $request PKPRequest
	 */
	function validate($request) {
		if (Config::getVar('general', 'installed')) {
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Execute installer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function install($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);

		$installForm = new InstallForm($request);
		$installForm->readInputData();

		if ($installForm->validate()) {
			$installForm->execute();

		} else {
			$installForm->display();
		}
	}

	/**
	 * Display upgrade form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function upgrade($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);

		if (($setLocale = $request->getUserVar('setLocale')) != null && AppLocale::isLocaleValid($setLocale)) {
			$request->setCookieVar('currentLocale', $setLocale);
		}

		$installForm = new UpgradeForm();
		$installForm->initData();
		$installForm->display();
	}

	/**
	 * Execute upgrade.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function installUpgrade($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);

		$installForm = new UpgradeForm();
		$installForm->readInputData();

		if ($installForm->validate()) {
			$installForm->execute();
		} else {
			$installForm->display();
		}
	}

	/**
	 * Set up the installer template.
	 * @param $request PKPRequest
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_INSTALLER);
	}
}

?>
