<?php

/**
 * @file controllers/tab/settings/siteSetup/form/NewSiteImageFileForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NewSiteImageFileForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form for upload an image.
 */

import('lib.pkp.controllers.tab.settings.form.SettingsFileUploadForm');

class NewSiteImageFileForm extends SettingsFileUploadForm {

	/**
	 * Constructor.
	 * @param $imageSettingName string
	 */
	function __construct($imageSettingName) {
		parent::__construct('controllers/tab/settings/form/newImageFileUploadForm.tpl');
		$this->setFileSettingName($imageSettingName);
	}


	//
	// Extend methods from Form.
	//
	/**
	 * @copydoc SettingsFileUploadForm::initData()
	 */
	function initData() {
		$request = Application::getRequest();
		$site = $request->getSite();
		$fileSettingName = $this->getFileSettingName();

		$image = $site->getSetting($fileSettingName);
		$imageAltText = array();

		$supportedLocales = AppLocale::getSupportedLocales();
		foreach ($supportedLocales as $key => $locale) {
			if (isset($image[$key])) {
				$imageAltText[$key] = $image[$key]['altText'];
			}
		}

		$this->setData('imageAltText', $imageAltText);
	}

	//
	// Extend methods from SettingsFileUploadForm.
	//
	/**
	 * @copydoc SettingsFileUploadForm::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('imageAltText'));

		parent::readInputData();
	}

	/**
	 * @copydoc SettingsFileUploadForm::fetch()
	 */
	function fetch($request, $template = null, $display = false, $params = null) {
		$params = array('fileType' => 'image');
		return parent::fetch($request, $template, $display, $params);
	}


	//
	// Extend methods from Form.
	//
	/**
	 * Save the new image file.
	 */
	function execute() {
		$request = Application::getRequest();
		$temporaryFile = $this->fetchTemporaryFile($request);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if (is_a($temporaryFile, 'TemporaryFile')) {
			$type = $temporaryFile->getFileType();
			$extension = $publicFileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			$locale = AppLocale::getLocale();
			$uploadName = $this->getFileSettingName() . '_' . $locale . $extension;
			if ($publicFileManager->copyFile($temporaryFile->getFilePath(), $publicFileManager->getSiteFilesPath() . '/' . $uploadName)) {

				// Get image dimensions
				$filePath = $publicFileManager->getSiteFilesPath();
				list($width, $height) = getimagesize($filePath . '/' . $uploadName);

				$site = $request->getSite();
				$siteDao = DAORegistry::getDAO('SiteDAO');
				$value = $site->getSetting($this->getFileSettingName());
				$imageAltText = $this->getData('imageAltText');

				$value[$locale] = array(
					'originalFilename' => $temporaryFile->getOriginalFileName(),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate(),
					'altText' => $imageAltText[$locale]
				);

				$site->updateSetting($this->getFileSettingName(), $value, 'object', true);

				// Clean up the temporary file
				$this->removeTemporaryFile($request);

				return true;
			}
		}
		return false;
	}
}

?>
