<?php

/**
 * @file classes/plugins/ImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ImportExportPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for import/export plugins
 */

import('lib.pkp.classes.plugins.Plugin');

use \PKP\core\JSONMessage;

abstract class ImportExportPlugin extends Plugin {
	/** @var PKPImportExportDeployment The deployment that processes import/export operations */
	var $_childDeployment = null;

	/** @var Request Request made available for plugin URL generation */
	var $_request;

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $scriptName The name of the command-line script (displayed as usage info)
	 * @param $args Parameters to the plugin
	 */
	abstract function executeCLI($scriptName, &$args);

	/**
	 * Display the command-line usage information
	 * @param $scriptName string
	 */
	abstract function usage($scriptName);

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		return array_merge(
			array(
				new LinkAction(
					'settings',
					new RedirectAction($dispatcher->url(
						$request, PKPApplication::ROUTE_PAGE,
						null, 'management', 'importexport', array('plugin', $this->getName())
					)),
					__('manager.importExport'),
					null
				),
			),
			parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * Display the import/export plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->registerPlugin('function', 'plugin_url', array($this, 'pluginUrl'));
		$this->_request = $request; // Store this for use by the pluginUrl function
		$templateMgr->assign([
			'breadcrumbs' => [
				[
					'id' => 'tools',
					'name' => __('navigation.tools'),
					'url' => $request->getRouter()->url($request, null, 'management', 'tools'),
				],
				[
					'id' => $this->getPluginPath(),
					'name' => $this->getDisplayName()
				],
			],
			'pageTitle' => $this->getDisplayName(),
		]);
	}

	/**
	 * Generate a URL into the plugin.
	 * @see calling conventions at http://www.smarty.net/docsv2/en/api.register.function.tpl
	 * @param $params array
	 * @param $smarty Smarty
	 * @return string
	 */
	function pluginUrl($params, $smarty) {
		$dispatcher = $this->_request->getDispatcher();
		return $dispatcher->url($this->_request, PKPApplication::ROUTE_PAGE, null, 'management', 'importexport', array_merge(array('plugin', $this->getName(), isset($params['path'])?$params['path']:array())));
	}

	/**
	 * Check if this is a relative path to the xml document
	 * that describes public identifiers to be imported.
	 * @param $url string path to the xml file
	 */
	function isRelativePath($url) {
		// FIXME This is not very comprehensive, but will work for now.
		if ($this->isAllowedMethod($url)) return false;
		if ($url[0] == '/') return false;
		return true;
	}

	/**
	 * Determine whether the specified URL describes an allowed protocol.
	 * @param $url string
	 * @return boolean
	 */
	function isAllowedMethod($url) {
		$allowedPrefixes = array(
			'http://',
			'ftp://',
			'https://',
			'ftps://'
		);
		foreach ($allowedPrefixes as $prefix) {
			if (substr($url, 0, strlen($prefix)) === $prefix) return true;
		}
		return false;
	}

	/**
	 * Get the plugin ID used as plugin settings prefix.
	 * @return string
	 */
	function getPluginSettingsPrefix() {
		return '';
	}

	/**
	 * Return the plugin export directory.
	 * @return string The export directory path.
	 */
	function getExportPath() {
		return Config::getVar('files', 'files_dir') . '/temp/';
	}

	/**
	 * Return the whole export file name.
	 * @param $basePath string Base path for temporary file storage
	 * @param $objectsFileNamePart string Part different for each object type.
	 * @param $context Context
	 * @param $extension string
	 * @return string
	 */
	function getExportFileName($basePath, $objectsFileNamePart, $context, $extension = '.xml') {
		return $basePath . $this->getPluginSettingsPrefix() . '-' . date('Ymd-His') .'-' . $objectsFileNamePart .'-' . $context->getId() . $extension;
	}

	/**
	 * Display XML validation errors.
	 * @param $errors array
	 * @param $xml string
	 */
	function displayXMLValidationErrors($errors, $xml) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER);
		if (defined('SESSION_DISABLE_INIT')) {
			echo __('plugins.importexport.common.validationErrors') . "\n";
			foreach ($errors as $error) {
				echo trim($error->message) . "\n";
			}
			libxml_clear_errors();
			echo __('plugins.importexport.common.invalidXML') . "\n";
			echo $xml . "\n";
		} else {
			$charset = Config::getVar('i18n', 'client_charset');
			header('Content-type: text/html; charset=' . $charset);
			echo '<html><body>';
			echo '<h2>' . __('plugins.importexport.common.validationErrors') . '</h2>';
			foreach ($errors as $error) {
				echo '<p>' . trim($error->message) . '</p>';
			}
			libxml_clear_errors();
			echo '<h3>' . __('plugins.importexport.common.invalidXML') . '</h3>';
			echo '<p><pre>' . htmlspecialchars($xml) . '</pre></p>';
			echo '</body></html>';
		}
		throw new Exception(__('plugins.importexport.common.error.validation'));
	}

	/**
	 * Set the deployment that processes import/export operations
	 */
	public function setDeployment($deployment) {
		$this->_childDeployment = $deployment;
	}

	/**
	 * Get the deployment that processes import/export operations
	 * @return PKPImportExportDeployment
	 */
	public function getDeployment() {
		return $this->_childDeployment;
	}

	/**
	 * Get the submissions and proceed to the export
	 * @param $submissionIds array Array of submissions to export
	 * @param $deployment PKPNativeImportExportDeployment
	 * @param $opts array
	 */
	function getExportSubmissionsDeployment($submissionIds, $deployment, $opts = array()) {
		$filter = $this->getExportFilter('exportSubmissions');

		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			/** @var $submissionService APP\Services\SubmissionService */
			$submissionService = Services::get('submission');
			$submission = $submissionService->get($submissionId);

			if ($submission && $submission->getData('contextId') == $deployment->getContext()->getId()) {
				$submissions[] = $submission;
			}
		}

		$deployment->export($filter, $submissions, $opts);
	}

	/**
	 * Define the appropriate import filter given the imported XML file path
	 * @param $xmlFile string
	 * @return array Containing the filter and the xmlString of the imported file
	 */
	abstract public function getImportFilter($xmlFile);

	/**
	 * Define the appropriate export filter given the export operation
	 * @param $exportType string
	 * @return string
	 */
	abstract public function getExportFilter($exportType);

	/**
	 * Get the application specific deployment object
	 * @param $context Context
	 * @param $user User
	 * @return PKPImportExportDeployment
	 */
	abstract public function getAppSpecificDeployment($context, $user);

	/**
	 * Save the export result as an XML
	 * @param $deployment PKPNativeImportExportDeployment
	 * @return string
	 */
	function exportResultXML($deployment) {
		$result = $deployment->processResult;
		$foundErrors = $deployment->isProcessFailed();

		$xml = null;
		if (!$foundErrors && $result) {
			$xml = $result->saveXml();
		}

		return $xml;
	}

	/**
	 * Gets template result for the export process
	 * @param $deployment PKPNativeImportExportDeployment
	 * @param $templateMgr PKPTemplateManager
	 * @param $exportFileName string
	 * @return string
	 */
	function getExportTemplateResult($deployment, $templateMgr, $exportFileName) {
		$result = $deployment->processResult;
		$problems = $deployment->getWarningsAndErrors();
		$foundErrors = $deployment->isProcessFailed();

		if (!$foundErrors) {
			$exportXml = $result->saveXml();

			if ($exportXml) {
				$path = $this->writeExportedFile($exportFileName, $exportXml, $deployment->getContext());
				$templateMgr->assign('exportPath', $path);
			}
		}

		$templateMgr->assign('validationErrors', $deployment->getXMLValidationErrors());

		$templateMgr->assign('errorsAndWarnings', $problems);
		$templateMgr->assign('errorsFound', $foundErrors);

		// Display the results
		$json = new JSONMessage(true, $templateMgr->fetch('plugins/importexport/resultsExport.tpl'));
		header('Content-Type: application/json');
		return $json->getString();
	}

	/**
	 * Gets template result for the import process
	 * @param $filter string
	 * @param $xmlString string
	 * @param $deployment PKPNativeImportExportDeployment
	 * @param $templateMgr PKPTemplateManager
	 * @return string
	 */
	function getImportTemplateResult($filter, $xmlString, $deployment, $templateMgr) {
		$deployment->import($filter, $xmlString);

		$templateMgr->assign('content', $deployment->processResult);
		$templateMgr->assign('validationErrors', $deployment->getXMLValidationErrors());

		$problems = $deployment->getWarningsAndErrors();
		$foundErrors = $deployment->isProcessFailed();

		$templateMgr->assign('errorsAndWarnings', $problems);
		$templateMgr->assign('errorsFound', $foundErrors);

		$templateMgr->assign('importedRootObjects', $deployment->getImportedRootEntitiesWithNames());

		// Display the results
		$json = new JSONMessage(true, $templateMgr->fetch('plugins/importexport/resultsImport.tpl'));
		header('Content-Type: application/json');
		return $json->getString();
	}

	/**
	 * Gets the imported file path
	 * @param $temporaryFileId int
	 * @param $user User
	 * @return string
	 */
	function getImportedFilePath($temporaryFileId, $user) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /** @var $temporaryFileDao TemporaryFileDAO */

		$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
		if (!$temporaryFile) {
			$json = new JSONMessage(true, __('plugins.inportexport.native.uploadFile'));
			header('Content-Type: application/json');
			return $json->getString();
		}
		$temporaryFilePath = $temporaryFile->getFilePath();

		return $temporaryFilePath;
	}

	/**
	 * Gets a tab to display after the import/export operation is over
	 * @param $request PKPRequest
	 * @param $title string
	 * @param $bounceUrl string
	 * @param $bounceParameterArray array
	 * @return string
	 */
	function getBounceTab($request, $title, $bounceUrl, $bounceParameterArray) {
		if (!$request->checkCSRF()) throw new Exception('CSRF mismatch!');
		$json = new JSONMessage(true);
		$json->setEvent('addTab', array(
			'title' => $title,
			'url' => $request->url(null, null, null, array('plugin', $this->getName(), $bounceUrl), $bounceParameterArray),
		));
		header('Content-Type: application/json');
		return $json->getString();
	}

	/**
	 * Download file given it's name
	 * @param $exportFileName string
	 */
	function downloadExportedFile($exportFileName) {
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$fileManager->downloadByPath($exportFileName);
		$fileManager->deleteByPath($exportFileName);
	}

	/**
	 * Create file given it's name and content
	 * @param $filename string
	 * @param $fileContent string
	 * @param $context Context
	 * @return string
	 */
	function writeExportedFile($filename, $fileContent, $context) {
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$exportFileName = $this->getExportFileName($this->getExportPath(), $filename, $context, '.xml');
		$fileManager->writeFile($exportFileName, $fileContent);

		return $exportFileName;
	}
}


