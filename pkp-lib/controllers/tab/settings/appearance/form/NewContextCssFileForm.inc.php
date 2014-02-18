<?php

/**
 * @file controllers/tab/settings/appearance/form/NewContextCssFileForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NewContextCssFileForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form to upload an css file.
 */

import('lib.pkp.controllers.tab.settings.form.SettingsFileUploadForm');

class NewContextCssFileForm extends SettingsFileUploadForm {

	/**
	 * Constructor.
	 * @param $imageSettingName string
	 */
	function NewContextCssFileForm($cssSettingName) {
		parent::SettingsFileUploadForm();
		$this->setFileSettingName($cssSettingName);
	}


	//
	// Extend methods from SettingsFileUploadForm.
	//
	/**
	 * @copydoc SettingsFileUploadForm::fetch()
	 */
	function fetch($request) {
		$params = array('fileType' => 'css');
		return parent::fetch($request, $params);
	}


	//
	// Extend methods from Form.
	//
	/**
	 * Save the new image file.
	 * @param $request Request.
	 */
	function execute($request) {
		$temporaryFile = $this->fetchTemporaryFile($request);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if (is_a($temporaryFile, 'TemporaryFile')) {
			$type = $temporaryFile->getFileType();
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}

			$settingName = $this->getFileSettingName();
			$uploadName = $settingName . '.css';
			$context = $request->getContext();
			if($publicFileManager->copyContextFile($context->getAssocType(), $context->getId(), $temporaryFile->getFilePath(), $uploadName)) {
				$value = array(
					'name' => $temporaryFile->getOriginalFileName(),
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate()
				);

				$settingsDao = $context->getSettingsDAO();
				$settingsDao->updateSetting($context->getId(), $settingName, $value, 'object');

				// Clean up the temporary file
				$this->removeTemporaryFile($request);

				return true;
			}
		}
		return false;
	}
}

?>
