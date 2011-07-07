<?php

/**
 * @file controllers/tab/settings/appearance/form/AppearanceForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppearanceForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form to edit press appearance settings.
 */


// Import the base Form.
import('controllers.tab.settings.form.PressSettingsForm');

class AppearanceForm extends PressSettingsForm {

	/** @var array */
	var $_imagesSettingsName;


	/**
	 * Constructor.
	 */
	function AppearanceForm($wizardMode = false) {
		// Define an array with the image setting name as key and its
		// alternate text setting name as value.
		$this->setImagesSettingsName(array(
			'homeHeaderTitleImage' => 'homeHeaderTitleImageAltText',
			'homeHeaderLogoImage' => 'homeHeaderLogoImageAltText',
			'homepageImage' => 'homepageImageAltText',
			'pageHeaderTitleImage' => 'pageHeaderTitleImageAltText',
			'pageHeaderLogoImage' => 'pageHeaderLogoImageAltText'
		));

		$settings = array(
			'homeHeaderTitleType' => 'int',
			'homeHeaderTitle' => 'string',
			'description' => 'string',
			'numRecentTitlesOnHomepage' => 'int',
			'additionalHomeContent' => 'string',
			'pressPageHeader' => 'string',
			'pressPageFooter' => 'string',
			'navItems' => 'object',
			'itemsPerPage' => 'int',
			'numPageLinks' => 'int'
		);

		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));

		parent::PressSettingsForm($settings, 'controllers/tab/settings/appearance/form/appearanceForm.tpl', $wizardMode);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the images settings name.
	 * @return array
	 */
	function getImagesSettingsName() {
		return $this->_imagesSettingsName;
	}

	/**
	 * Set the image settings name.
	 * @param array $imagesSettingsName
	 * @return array
	 */
	function setImagesSettingsName($imagesSettingsName) {
		$this->_imagesSettingsName = $imagesSettingsName;
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * @see Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array(
			'homeHeaderTitleType',
			'homeHeaderTitle',
			'description',
			'additionalHomeContent',
			'pressPageHeader',
			'pressPageFooter'
		);
	}


	//
	// Extend methods from PressSettingsForm.
	//
	/**
	 * @see PressSettingsForm::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_values($this->getImagesSettingsName()));
		parent::readInputData();
	}

	/**
	 * @see PressSettingsForm::fetch()
	 */
	function fetch(&$request) {
		$press =& $request->getPress();

		// Get all upload form image link actions.
		$uploadImageLinkActions = array();
		foreach ($this->getImagesSettingsName() as $settingName => $altText) {
			$uploadImageLinkActions[$settingName] = $this->_getFileUploadLinkAction($settingName, 'ImageUploadForm', $request);
		}
		// Get the css upload link action.
		$uploadCssLinkAction = $this->_getFileUploadLinkAction('pressStyleSheet', 'CssUploadForm', $request);

		$imagesViews = $this->_renderAllFormImagesViews($request);
		$cssView = $this->renderFileView('pressStyleSheet', $request);

		$params = array(
			'uploadImageLinkActions' => $uploadImageLinkActions,
			'uploadCssLinkAction' => $uploadCssLinkAction,
			'imagesViews' => $imagesViews,
			'pressStyleSheetView' => $cssView,
			'locale' => Locale::getLocale()
		);

		return parent::fetch(&$request, $params);
	}

		// Save alt text for images
		$press =& $request->getPress();
		$pressId = $press->getId();
		$locale = $this->getFormLocale();
		$settingsDao =& DAORegistry::getDAO('PressSettingsDAO');
		$imagesSettingsName = $this->getImagesSettingsName();

		foreach($imagesSettingsName as $image => $altText) {
			$value = $press->getSetting($image);
			if (!empty($value)) {
				$imageAltText = $this->getData($altText);
				$value[$locale]['altText'] = $imageAltText[$locale];
				$settingsDao->updateSetting($pressId, $image, $value, 'object', true);
			}
		}


	//
	// Public methods.
	//
	/**
	 * Render a template to show details about an uploaded file in the form
	 * and a link action to delete it.
	 * @param $fileSettingName string The uploaded file setting name.
	 * @param $request Request
	 * @return string
	 */
	function renderFileView($fileSettingName, $request) {
		$file = $this->getData($fileSettingName);
		$locale = Locale::getLocale();

		// Check if the file is localized.
		if (!is_null($file) && key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		}

		if (is_array($file)) {
			$templateMgr = TemplateManager::getManager();
			$deleteLinkAction = $this->_getDeleteFileLinkAction($fileSettingName, $request);

			// Get the right template to render the view.
			$imagesSettingsName = array_keys($this->getImagesSettingsName());
			if (in_array($fileSettingName, $imagesSettingsName)) {
				$template = 'controllers/tab/settings/formImageView.tpl';

				// Get the common alternate text for the image.
				$localeKey = "common." . $fileSettingName . ".altText";
				$commonAltText = Locale::translate($localeKey);
				$templateMgr->assign('commonAltText', $commonAltText);
			} else {
				$template = 'controllers/tab/settings/formFileView.tpl';
			}

			$templateMgr->assign('file', $file);
			$templateMgr->assign('deleteLinkAction', $deleteLinkAction);
			$templateMgr->assign('fileSettingName', $fileSettingName);

			return $templateMgr->fetch($template);
		} else {
			return false;
		}
	}

	/**
	 * Delete an uploaded file.
	 * @param $fileSettingName string
	 * @return boolean
	 */
	function deleteFile($fileSettingName, $request) {
		$press =& $request->getPress();
		$locale = Locale::getLocale();

		// Get the file.
		$file = $this->getData($fileSettingName);

		// Check if the file is localized.
		if (key_exists($locale, $file)) {
			// We use the current localized file value.
			$file = $file[$locale];
		} else {
			$locale = null;
		}

		// Deletes the file and its settings.
		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->removePressFile($press->getId(), $file['uploadName'])) {
			$settingsDao =& DAORegistry::getDAO('PressSettingsDAO');
			$settingsDao->deleteSetting($press->getId(), $fileSettingName, $locale);
			return true;
		} else {
			return false;
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Render all form images views.
	 * @param $request Request
	 * @return array
	 */
	function _renderAllFormImagesViews($request) {
		$imagesViews = array();
		foreach ($this->getImagesSettingsName() as $imageSettingName => $altText) {
			$imagesViews[$imageSettingName] = $this->renderFileView($imageSettingName, $request);
		}

		return $imagesViews;
	}

	/**
	 * Get a link action for file upload.
	 * @param $settingName string
	 * @param $fileUploadForm string
	 * @param $request Request
	 * @return LinkAction
	 */
	function _getFileUploadLinkAction($settingName, $fileUploadForm, $request) {
		$router =& $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$ajaxModal = new AjaxModal(
			$router->url(
				$request, null, null, 'showFileUploadForm', null, array(
					'fileSettingName' => $settingName,
					'fileUploadForm' => $fileUploadForm
				)
			)
		);
		$linkAction = new LinkAction(
			'uploadFile-' . $settingName,
			$ajaxModal,
			__('common.upload'),
			null
		);

		return $linkAction;
	}

	/**
	 * Get the delete file link action.
	 * @param $setttingName string File setting name.
	 * @param $request Request
	 * @return LinkAction
	 */
	function _getDeleteFileLinkAction($settingName, $request) {
		$router =& $request->getRouter();
		import('lib.pkp.classes.linkAction.request.ConfirmationModal');

		$confirmationModal = new ConfirmationModal(
			__('common.confirmDelete'), null,
			$router->url(
				$request, null, null, 'deleteFile', null, array(
					'fileSettingName' => $settingName,
					'formName' => get_class($this)
				)
			)
		);
		$linkAction = new LinkAction(
			'deleteFile-' . $settingName,
			$confirmationModal,
			__('common.delete'),
			null
		);

		return $linkAction;
	}
}

?>