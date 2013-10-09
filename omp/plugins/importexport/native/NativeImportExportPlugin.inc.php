<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native XML import/export plugin
 */

import('classes.plugins.ImportExportPlugin');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Constructor
	 */
	function NativeImportExportPlugin() {
		parent::ImportExportPlugin();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		$this->import('NativeImportExportDeployment');
		return $success;
	}

	/**
	 * Get the template path
	 * @return string
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'NativeImportExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.native.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.native.description');
	}

	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$press = $request->getPress();

		parent::display($args, $request);

		$templateMgr->assign('plugin', $this);

		switch (array_shift($args)) {
			case 'index':
			case '':
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
			case 'uploadImportXML':
				$user = $request->getUser();
				import('lib.pkp.classes.file.TemporaryFileManager');
				$temporaryFileManager = new TemporaryFileManager();
				$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
				if ($temporaryFile) {
					$json = new JSONMessage(true);
					$json->setAdditionalAttributes(array(
						'temporaryFileId' => $temporaryFile->getId()
					));
				} else {
					$json = new JSONMessage(false, __('common.uploadFailed'));
				}

				return $json->getString();
			case 'import':
				$temporaryFileId = $request->getUserVar('temporaryFileId');
				$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
				$user = $request->getUser();
				$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
				$temporaryFilePath = $temporaryFile->getFilePath();
				$submissions = $this->importSubmissions(file_get_contents($temporaryFilePath), $press);
				$templateMgr->assign('submissions', $submissions);
				$json = new JSONMessage(true, $templateMgr->fetch($this->getTemplatePath() . 'results.tpl'));
				return $json->getString();
			case 'export':
				$exportXml = $this->exportSubmissions(
					(array) $request->getUserVar('selectedSubmissions'),
					$request->getContext()
				);
				header('Content-type: application/xml');
				echo $exportXml;
				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	/**
	 * Get the XML for a set of submissions.
	 * @param $submissionIds array Array of submission IDs
	 * @return string XML contents representing the supplied submission IDs.
	 */
	function exportSubmissions($submissionIds, $context) {
		$submissionDao = Application::getSubmissionDAO();
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('monograph=>native-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new NativeImportExportDeployment());
		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			$submission = $submissionDao->getById($submissionId, $context->getId());
			if ($submission) $submissions[] = $submission;
		}
		$submissionXml = $exportFilter->execute($submissions);
		if ($submissionXml) $xml = $submissionXml->saveXml();
		else fatalError('Could not convert submissions.');
		return $xml;
	}

	/**
	 * Get the XML for a set of submissions.
	 * @param $importXml string XML contents to import
	 * @return array Set of imported submissions
	 */
	function importSubmissions($importXml, $context) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeImportFilters = $filterDao->getObjectsByGroup('native-xml=>monograph');
		assert(count($nativeImportFilters) == 1); // Assert only a single unserialization filter
		$importFilter = array_shift($nativeImportFilters);
		$importFilter->setDeployment(new NativeImportExportDeployment());

		return $importFilter->execute($importXml);
	}
}

?>
