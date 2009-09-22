<?php

/**
 * @file SetupHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SetupHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for press setup functions. 
 */

// $Id$

import('pages.manager.ManagerHandler');

class SetupHandler extends ManagerHandler {
	/**
	 * Constructor
	 */	
	function SetupHandler() {
		parent::ManagerHandler();
	}
	
	/**
	 * Display press setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function setup($args) {
		$this->validate();
		$this->setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 5) {

			$formClass = "PressSetupStep{$step}Form";
			import("manager.form.setup.$formClass");

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$setupForm =& new $formClass();
			if ($setupForm->isLocaleResubmit()) {
				$setupForm->readInputData();
			} else {
				$setupForm->initData();
			}
			$setupForm->display();

		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('helpTopicId','press.managementPages.setup');
			$templateMgr->display('manager/setup/index.tpl');
		}
	}

	/**
	 * Save changes to press settings.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSetup($args) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 5) {

			$this->setupTemplate(true);

			$formClass = "PressSetupStep{$step}Form";
			import("manager.form.setup.$formClass");

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$setupForm =& new $formClass();
			$setupForm->readInputData();
			$formLocale = $setupForm->getFormLocale();

			// Check for any special cases before trying to save
			switch ($step) {
				case 1:
					if (Request::getUserVar('addSponsor')) {
						// Add a sponsor
						$editData = true;
						$sponsors = $setupForm->getData('sponsors');
						array_push($sponsors, array());
						$setupForm->setData('sponsors', $sponsors);

					} else if (($delSponsor = Request::getUserVar('delSponsor')) && count($delSponsor) == 1) {
						// Delete a sponsor
						$editData = true;
						list($delSponsor) = array_keys($delSponsor);
						$delSponsor = (int) $delSponsor;
						$sponsors = $setupForm->getData('sponsors');
						array_splice($sponsors, $delSponsor, 1);
						$setupForm->setData('sponsors', $sponsors);

					} else if (Request::getUserVar('addContributor')) {
						// Add a contributor
						$editData = true;
						$contributors = $setupForm->getData('contributors');
						array_push($contributors, array());
						$setupForm->setData('contributors', $contributors);

					} else if (($delContributor = Request::getUserVar('delContributor')) && count($delContributor) == 1) {
						// Delete a contributor
						$editData = true;
						list($delContributor) = array_keys($delContributor);
						$delContributor = (int) $delContributor;
						$contributors = $setupForm->getData('contributors');
						array_splice($contributors, $delContributor, 1);
						$setupForm->setData('contributors', $contributors);
					}

					break;

				case 2:
					if (Request::getUserVar('addChecklist')) {
						// Add a checklist item
						$editData = true;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!isset($checklist[$formLocale]) || !is_array($checklist[$formLocale])) {
							$checklist[$formLocale] = array();
							$lastOrder = 0;
						} else {
							$lastOrder = $checklist[$formLocale][count($checklist[$formLocale])-1]['order'];
						}
						array_push($checklist[$formLocale], array('order' => $lastOrder+1));
						$setupForm->setData('submissionChecklist', $checklist);

					} else if (($delChecklist = Request::getUserVar('delChecklist')) && count($delChecklist) == 1) {
						// Delete a checklist item
						$editData = true;
						list($delChecklist) = array_keys($delChecklist);
						$delChecklist = (int) $delChecklist;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!isset($checklist[$formLocale])) $checklist[$formLocale] = array();
						array_splice($checklist[$formLocale], $delChecklist, 1);
						$setupForm->setData('submissionChecklist', $checklist);
					} 
					break;

				case 3:
					if (Request::getUserVar('deleteSelectedBookFileTypes')) {
						// Delete book file types
						$editData = true;
						$press =& Request::getPress();
						$settingsDao =& DAORegistry::getDAO('PressSettingsDAO');
						$bookFileIds = $setupForm->getData('bookFileTypeSelect');
						$bookFileTypes =& $settingsDao->getSetting($press->getId(), 'bookFileTypes');

						foreach ($bookFileIds as $bookFileIndex) {
							if (isset($bookFileTypes[$formLocale][$bookFileIndex])) {
								 unset($bookFileTypes[$formLocale][$bookFileIndex]);
							}
						}
						$settingsDao->updateSetting(
							$press->getId(), 
							'bookFileTypes', 
							$bookFileTypes, 
							'object', 
							true
						);

						$setupForm->setData('bookFileTypes', $bookFileTypes);
					} else if (Request::getUserVar('restoreDefaultBookFileTypes')) {
						// Delete book file types
						$editData = true;
						$press =& Request::getPress();
						$settingsDao =& DAORegistry::getDAO('PressSettingsDAO');
						$newBookFileType = $setupForm->getData('bookFileTypeId');

						Locale::requireComponents(array(LOCALE_COMPONENT_OMP_DEFAULT_SETTINGS));
						$settingsDao->reloadDefaultSetting(
							$press->getId(), 
							'registry/pressSettings.xml', 
							'bookFileTypes'
						);

					} else if (Request::getUserVar('addBookFileType')) {
						// Add a book file type
						// FIXME validate user data
						$editData = true;
						$press =& Request::getPress();
						$settingsDao =& DAORegistry::getDAO('PressSettingsDAO');
						$newBookFileType = $setupForm->getData('newBookFileType');

						$bookFileTypes = $settingsDao->getSetting($press->getId(), 'bookFileTypes');

						if (isset($newBookFileType['sortable'])) {
							$newBookFileType['prefix'] = '--';
							$newBookFileType['sortable'] = true;
						} else {
							$newBookFileType['sortable'] = false;
						}

						array_push($bookFileTypes[$formLocale], $newBookFileType);

						$settingsDao->updateSetting(
							$press->getId(),
							'bookFileTypes',
							$bookFileTypes,
							'object',
							true
						);
;
						$setupForm->setData('bookFileTypes', $bookFileTypes);
					}

					if (!isset($editData)) {
						// Reorder checklist items
						$checklist = $setupForm->getData('submissionChecklist');
						if (isset($checklist[$formLocale]) && is_array($checklist[$formLocale])) {
							usort($checklist[$formLocale], create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
						}
						$setupForm->setData('submissionChecklist', $checklist);
					}
					break;

				case 4:
					$press =& Request::getPress();
					$templates = $press->getSetting('templates');
					import('file.PressFileManager');
					$pressFileManager = new PressFileManager($press);
					if (Request::getUserVar('addTemplate')) {
						// Add a layout template
						$editData = true;
						if (!is_array($templates)) $templates = array();
						$templateId = count($templates);
						$originalFilename = $_FILES['template-file']['name'];
						$fileType = $_FILES['template-file']['type'];
						$filename = "template-$templateId." . $pressFileManager->parseFileExtension($originalFilename);
						$pressFileManager->uploadFile('template-file', $filename);
						$templates[$templateId] = array(
							'originalFilename' => $originalFilename,
							'fileType' => $fileType,
							'filename' => $filename,
							'title' => Request::getUserVar('template-title')
						);
						$press->updateSetting('templates', $templates);
					} else if (($delTemplate = Request::getUserVar('delTemplate')) && count($delTemplate) == 1) {
						// Delete a template
						$editData = true;
						list($delTemplate) = array_keys($delTemplate);
						$delTemplate = (int) $delTemplate;
						$template = $templates[$delTemplate];
						$filename = "template-$delTemplate." . $pressFileManager->parseFileExtension($template['originalFilename']);
						$pressFileManager->deleteFile($filename);
						array_splice($templates, $delTemplate, 1);
						$press->updateSetting('templates', $templates);
					}

					$setupForm->setData('templates', $templates);
					break;
				case 5:	
					if (Request::getUserVar('uploadHomeHeaderTitleImage')) {
						if ($setupForm->uploadImage('homeHeaderTitleImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImage', Locale::translate('manager.setup.homeTitleImageInvalid'));
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImage', $formLocale);

					} else if (Request::getUserVar('uploadHomeHeaderLogoImage')) {
						if ($setupForm->uploadImage('homeHeaderLogoImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImage', Locale::translate('manager.setup.homeHeaderImageInvalid'));
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImage', $formLocale);

					} else if (Request::getUserVar('uploadPageHeaderTitleImage')) {
						if ($setupForm->uploadImage('pageHeaderTitleImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImage', Locale::translate('manager.setup.pageHeaderTitleImageInvalid'));
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImage', $formLocale);

					} else if (Request::getUserVar('uploadPageHeaderLogoImage')) {
						if ($setupForm->uploadImage('pageHeaderLogoImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImage', Locale::translate('manager.setup.pageHeaderLogoImageInvalid'));
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImage', $formLocale);

					} else if (Request::getUserVar('uploadHomepageImage')) {
						if ($setupForm->uploadImage('homepageImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homepageImage', Locale::translate('manager.setup.homepageImageInvalid'));
						}

					} else if (Request::getUserVar('deleteHomepageImage')) {
						$editData = true;
						$setupForm->deleteImage('homepageImage', $formLocale);
					} else if (Request::getUserVar('uploadPressStyleSheet')) {
						if ($setupForm->uploadStyleSheet('pressStyleSheet')) {
							$editData = true;
						} else {
							$setupForm->addError('pressStyleSheet', Locale::translate('manager.setup.pressStyleSheetInvalid'));
						}

					} else if (Request::getUserVar('deletePressStyleSheet')) {
						$editData = true;
						$setupForm->deleteImage('pressStyleSheet');

					} else if (Request::getUserVar('addNavItem')) {
						// Add a navigation bar item
						$editData = true;
						$navItems = $setupForm->getData('navItems');
						$navItems[$formLocale][] = array();
						$setupForm->setData('navItems', $navItems);

					} else if (($delNavItem = Request::getUserVar('delNavItem')) && count($delNavItem) == 1) {
						// Delete a  navigation bar item
						$editData = true;
						list($delNavItem) = array_keys($delNavItem);
						$delNavItem = (int) $delNavItem;
						$navItems = $setupForm->getData('navItems');
						if (is_array($navItems) && is_array($navItems[$formLocale])) {
							array_splice($navItems[$formLocale], $delNavItem, 1);		
							$setupForm->setData('navItems', $navItems);
						}
					} else if (Request::getUserVar('addCustomAboutItem')) {
						// Add a custom about item
						$editData = true;
						$customAboutItems = $setupForm->getData('customAboutItems');
						$customAboutItems[$formLocale][] = array();
						$setupForm->setData('customAboutItems', $customAboutItems);

					} else if (($delCustomAboutItem = Request::getUserVar('delCustomAboutItem')) && count($delCustomAboutItem) == 1) {
						// Delete a custom about item
						$editData = true;
						list($delCustomAboutItem) = array_keys($delCustomAboutItem);
						$delCustomAboutItem = (int) $delCustomAboutItem;
						$customAboutItems = $setupForm->getData('customAboutItems');
						if (!isset($customAboutItems[$formLocale])) $customAboutItems[$formLocale][] = array();
						array_splice($customAboutItems[$formLocale], $delCustomAboutItem, 1);
						$setupForm->setData('customAboutItems', $customAboutItems);
					}

					break;
			}

			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();

				Request::redirect(null, null, 'setupSaved', $step);
			} else {
				$setupForm->display();
			}

		} else {
			Request::redirect();
		}
	}

	/**
	 * Display a "Settings Saved" message
	 */
	function setupSaved($args) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 5) {
			$this->setupTemplate(true);

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('setupStep', $step);
			$templateMgr->assign('helpTopicId', 'press.managementPages.setup');
			$templateMgr->display('manager/setup/settingsSaved.tpl');
		} else {
			Request::redirect(null, 'index');
		}
	}

	function downloadLayoutTemplate($args) {
		$this->validate();
		$press =& Request::getPress();
		$templates = $press->getSetting('templates');
		import('file.PressFileManager');
		$pressFileManager = new PressFileManager($press);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) Request::redirect(null, null, 'setup');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $pressFileManager->parseFileExtension($template['originalFilename']);
		$pressFileManager->downloadFile($filename, $template['fileType']);
	}
}
?>
