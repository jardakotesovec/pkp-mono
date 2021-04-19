<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native XML import/export plugin
 */
use Colors\Color;

import('lib.pkp.plugins.importexport.native.PKPNativeImportExportPlugin');
class NativeImportExportPlugin extends PKPNativeImportExportPlugin {

	/**
	 * @see ImportExportPlugin::display()
	 */
	function display($args, $request) {
		parent::display($args, $request);

		if ($this->isResultManaged) {
			if ($this->result) {
				return $this->result;
			}

			return false;
		}

		$templateMgr = TemplateManager::getManager($request);

		switch ($this->opType) {
			case 'exportIssuesBounce':
				return $this->getBounceTab($request,
					__('plugins.importexport.native.export.issues.results'),
						'exportIssues',
						array('selectedIssues' => $request->getUserVar('selectedIssues'))
				);
			case 'exportIssues':
				$selectedEntitiesIds = (array) $request->getUserVar('selectedIssues');
				$deployment = $this->getDeployment();

				$this->getExportIssuesDeployment($selectedEntitiesIds, $deployment);

				return $this->getExportTemplateResult($deployment, $templateMgr, 'issues');
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	/**
	 * Get the issues and proceed to the export
	 * @param $issueIds array Array of issueIds to export
	 * @param $deployment PKPNativeImportExportDeployment
	 * @param $opts array
	 */
	function getExportIssuesDeployment($issueIds, &$deployment, $opts = array()) {
		$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
		$issues = array();
		foreach ($issueIds as $issueId) {
			$issue = $issueDao->getById($issueId, $deployment->getContext()->getId());
			if ($issue) $issues[] = $issue;
		}

		$deployment->export('issue=>native-xml', $issues, $opts);
	}

	/**
	 * Get the XML for a set of issues.
	 * @param $issueIds array
	 * @param $context Context
	 * @param $user User
	 * @param $opts array
	 * @return string XML contents representing the supplied issue IDs.
	 */
	function exportIssues($issueIds, $context, $user, $opts = array()) {
		$deployment = new NativeImportExportDeployment($context, $user);
		$this->getExportIssuesDeployment($issueIds, $deployment, $opts);

		return $this->exportResultXML($deployment);
	}

	/**
	 * @see PKPNativeImportExportPlugin::getImportFilter
	 */
	function getImportFilter($xmlFile) {
		$filter = 'native-xml=>issue';
		// is this articles import:
		$xmlString = file_get_contents($xmlFile);
		$document = new DOMDocument();
		$document->loadXml($xmlString);
		if (in_array($document->documentElement->tagName, array('article', 'articles'))) {
			$filter = 'native-xml=>article';
		}

		return array($filter, $xmlString);
	}

	/**
	 * @see PKPNativeImportExportPlugin::getExportFilter
	 */
	function getExportFilter($exportType) {
		$filter = 'issue=>native-xml';
		if ($exportType == 'exportSubmissions') {
			$filter = 'article=>native-xml';
		}

		return $filter;
	}

	/**
	 * @see PKPNativeImportExportPlugin::getAppSpecificDeployment
	 */
	function getAppSpecificDeployment($context, $user) {
		return new NativeImportExportDeployment($context, $user);
	}

	/**
	 * @see PKPImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		$result = parent::executeCLI($scriptName, $args);

		if ($result) {
			return $result;
		}

		$cliDeployment = $this->cliDeployment;
		$deployment = $this->getDeployment();

		switch ($cliDeployment->command) {
			case 'export':
				switch ($cliDeployment->exportEntity) {
					case 'issue':
					case 'issues':
						$this->getExportIssuesDeployment(
							$cliDeployment->args,
							$deployment,
							$cliDeployment->opts
						);

						$this->cliToolkit->getCLIExportResult($deployment, $cliDeployment->xmlFile);
						$this->cliToolkit->getCLIProblems($deployment);

						return true;
				}
		}

		$this->usage($scriptName);
	}

}


