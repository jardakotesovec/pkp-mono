<?php

/**
 * @file plugins/importexport/pubIds/PubIdImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdImportExportPlugin
 * @ingroup plugins_importexport_pubIds
 *
 * @brief Public identifier import/export plugin
 */


import('classes.plugins.ImportExportPlugin');

import('lib.pkp.classes.xml.XMLCustomWriter');

define('PID_DTD_URL', 'http://pkp.sfu.ca/ojs/dtds/2.3/pubIds.dtd');
define('PID_DTD_ID', '-//PKP//OJS PubIds XML//EN');

class PubIdImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'PubIdImportExportPlugin';
	}

	function getDisplayName() {
		return AppLocale::translate('plugins.importexport.pubIds.displayName');
	}

	function getDescription() {
		return AppLocale::translate('plugins.importexport.pubIds.description');
	}

	/**
	 * Get the template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	function display(&$args, $request) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args, $request);

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$journal =& $request->getJournal();
		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue =& $issueDao->getIssueById($issueId, $journal->getId());
					if (!$issue) $request->redirect();
					$issues[] =& $issue;
					unset($issue);
				}
				$this->exportPubIdsForIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue =& $issueDao->getIssueById($issueId, $journal->getId());
				if (!$issue) $request->redirect();
				$issues = array(&$issue);
				$this->exportPubIdsForIssues($journal, $issues);
				break;
			case 'selectIssue':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				Locale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getIssues($journal->getId(), Handler::getRangeInfo('issues'));
				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'selectIssue.tpl');
				break;
			case 'import':
				import('classes.file.TemporaryFileManager');
				$user =& $request->getUser();
				$temporaryFileManager = new TemporaryFileManager();

				if (($existingFileId = $request->getUserVar('temporaryFileId'))) {
					// The user has just entered more context. Fetch an existing file.
					$temporaryFile = TemporaryFileManager::getFile($existingFileId, $user->getId());
				} else {
					$temporaryFile = $temporaryFileManager->handleUpload('importFile', $user->getId());
				}
				if (!$temporaryFile) {
					$templateMgr->assign('error', 'plugins.importexport.pubIds.import.error.uploadFailed');
					return $templateMgr->display($this->getTemplatePath() . 'importResults.tpl');
				}

				$context = array('journal' => $journal);

				$doc =& $this->getDocument($temporaryFile->getFilePath());
				@set_time_limit(0);
				$this->handleImport($context, $doc, $errors, $pubIds, $false);
				$templateMgr->assign_by_ref('errors', $errors);
				$templateMgr->assign_by_ref('pubIds', $pubIds);
				return $templateMgr->display($this->getTemplatePath() . 'importResults.tpl');
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'importExportIndex.tpl');
		}
	}

	function exportPubIdsForIssues(&$journal, &$issues, $outputFile = null) {
		$doc =& XMLCustomWriter::createDocument('pubIds', PID_DTD_URL, PID_DTD_URL);
		$pubIdsNode =& XMLCustomWriter::createElement($doc, 'pubIds');
		XMLCustomWriter::appendChild($doc, $pubIdsNode);

		foreach ($issues as $issue) {
			$this->generatePubId($doc, $pubIdsNode, $issue, $journal->getId());

			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			foreach ($publishedArticleDao->getPublishedArticles($issue->getId()) as $publishedArticle) {
				$this->generatePubId($doc, $pubIdsNode, $publishedArticle, $journal->getId());

				$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
				foreach ($articleGalleyDao->getGalleysByArticle($publishedArticle->getId()) as $articleGalley) {
					$this->generatePubId($doc, $pubIdsNode, $articleGalley, $journal->getId());
				}

				$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
				foreach ($suppFileDao->getSuppFilesByArticle($publishedArticle->getId()) as $suppFile) {
					$this->generatePubId($doc, $pubIdsNode, $suppFile, $journal->getId());
				}
			}
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"pubIds.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function importPubId(&$journal, &$pubIdNode, &$pubId, &$errors, $isCommandLine) {
		$errors = array();
		$pubId = null;

		$pubIdValue = $pubIdNode->getValue();
		$pubIdType = $pubIdNode->getAttribute('pubIdType');
		$pubObjectType = $pubIdNode->getAttribute('pubObjectType');
		$pubObjectId = $pubIdNode->getAttribute('pubObjectId');

		$pubIdPluginFound = false;
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journal->getId());
		foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($pubIdPlugin->getPubIdType() == $pubIdType) {
				$dao =& $pubIdPlugin->getDAO($pubObjectType);
				switch ($pubObjectType) {
					case 'Issue':
						$pubObject =& $dao->getIssueById($pubObjectId, $journal->getId());
						break;
					case 'Article':
						$pubObject =& $dao->getArticle($pubObjectId, $journal->getId());
						break;
					case 'Galley':
						$pubObject =& $dao->getGalley($pubObjectId);
						break;
					case 'SuppFile':
						$pubObject =& $dao->getSuppFile($pubObjectId);
						break;
					default:
						$errors[] = array('plugins.importexport.pubIds.import.error.unknownObjectType', array('pubObjectType' => $pubObjectType, 'pubId' => $pubIdValue));
						break;
				}
				if ($pubObject) {
					$storedPubId = $pubObject->getStoredPubId($pubIdType);
					if (!$storedPubId) {
						if (!$pubIdPlugin->checkDuplicate($pubIdValue, $pubObject, $journal->getId())) {
							$errors[] = array('plugins.importexport.pubIds.import.error.duplicatePubId', array('pubId' => $pubIdValue));
						} else {
							$pubIdPlugin->setStoredPubId($pubObject, $pubObjectType, $pubIdValue);
							$pubId = array('pubObjectType' => $pubObjectType, 'pubObjectId' => $pubObjectId, 'value' => $pubIdValue);
						}
					} else {
						$errors[] = array('plugins.importexport.pubIds.import.error.pubIdExists', array('pubIdType' => $pubIdType, 'pubObjectType' => $pubObjectType, 'pubObjectId' => $pubObjectId));
					}
				} else {
					$errors[] = array('plugins.importexport.pubIds.import.error.unknownObject', array('pubObjectType' => $pubObjectType, 'pubObjectId' => $pubObjectId, 'pubId' => $pubIdValue));
				}
				$pubIdPluginFound = true;
				break;
			}
		}
		if (!$pubIdPluginFound) {
			$errors[] = array('plugins.importexport.native.import.error.unknownPubId', array('pubIdType' => $pubIdType));
		}
	}

	function importPubIds(&$journal, &$pubIdNodes, &$pubIds, &$errors, $isCommandLine) {
		$errors = array();
		$pubIds = array();
		foreach ($pubIdNodes as $pubIdNode) {
			$this->importPubId($journal, $pubIdNode, $pubId, $pubIdErrors, $isCommandLine);
			if ($pubId) $pubIds[] = $pubId;
			$errors = array_merge($errors, $pubIdErrors);
		}
	}

	function &getDocument($fileName) {
		$parser = new XMLParser();
		$returner =& $parser->parse($fileName);
		return $returner;
	}

	function getRootNodeName(&$doc) {
		return $doc->name;
	}

	function handleImport(&$context, &$doc, &$errors, &$pubIds, $isCommandLine) {
		$errors = array();
		$pubIds = array();

		$journal =& $context['journal'];

		$rootNodeName = $this->getRootNodeName($doc);

		switch ($rootNodeName) {
			case 'pubIds':
				$this->importPubIds($journal, $doc->children, $pubIds, $errors, $isCommandLine);
				break;
			default:
				$errors[] = array('plugins.importexport.pubIds.import.error.unsupportedRoot', array('rootName' => $rootNodeName));
				break;
		}
	}

	/**
	 * Add ID-nodes to the given node.
	 * @param $doc DOMDocument
	 * @param $node DOMNode
	 * @param $pubObject object
	 * @param $journalId int
	 */
	function generatePubId(&$doc, &$node, &$pubObject, $journalId) {
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journalId);
		foreach ($pubIdPlugins as $pubIdPlugin) {
			$pubIdType = $pubIdPlugin->getPubIdType();
			$pubId = $pubObject->getStoredPubId($pubIdType);
			if ($pubId) {
				$pubObjectType = $pubIdPlugin->getPubObjectType($pubObject);

				$pubIdNode =& XMLCustomWriter::createChildWithText($doc, $node, 'pubId', $pubId);

				XMLCustomWriter::setAttribute($pubIdNode, 'pubIdType', $pubIdType);
				XMLCustomWriter::setAttribute($pubIdNode, 'pubObjectType', $pubObjectType);
				XMLCustomWriter::setAttribute($pubIdNode, 'pubObjectId', $pubObject->getId());

			}
		}
	}

	function isRelativePath($url) {
		// FIXME This is not very comprehensive, but will work for now.
		if ($this->isAllowedMethod($url)) return false;
		if ($url[0] == '/') return false;
		return true;
	}

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
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$journal =& $journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.pubIds.cliError') . "\n";
				echo __('plugins.importexport.pubIds.cliError.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile && $this->isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}

		switch ($command) {
			case 'import':
				$userName = array_shift($args);
				$user =& $userDao->getUserByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo __('plugins.importexport.pubIds.cliError') . "\n";
						echo __('plugins.importexport.pubIds.cliError.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$doc =& $this->getDocument($xmlFile);

				$context = array(
					'user' => $user,
					'journal' => $journal
				);

				$this->handleImport($context, $doc, $errors, $pubIds, true);
				if (!empty($pubIds)) echo __('plugins.importexport.pubIds.import.success.description') . "\n";
					foreach ($pubIds as $pubId) {
						echo "\t" . $pubId['value'] . "\n";
					}

				if (!empty($errors)) echo __('plugins.importexport.pubIds.cliError') . "\n";
				$errorsTranslated = array();
				foreach ($errors as $error) {
					$errorsTranslated[] = __($error[0], $error[1]);
				}
				foreach ($errorsTranslated as $errorTranslated) {
					echo "\t" . $errorTranslated . "\n";
				}
				return;
				break;
			case 'export':
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'issue':
						$issueId = array_shift($args);
						$issue =& $issueDao->getIssueByBestIssueId($issueId, $journal->getId());
						if ($issue == null) {
							echo __('plugins.importexport.pubIds.cliError') . "\n";
							echo __('plugins.importexport.pubIds.cliError.issueNotFound', array('issueId' => $issueId)) . "\n\n";
							return;
						}
						$issues = array(&$issue);
						if (!$this->exportPubIdsForIssues($journal, $issues, $xmlFile)) {
							echo __('plugins.importexport.pubIds.cliError') . "\n";
							echo __('plugins.importexport.pubIds.cliError.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'issues':
						$issues = array();
						while (($issueId = array_shift($args))!==null) {
							$issue =& $issueDao->getIssueByBestIssueId($issueId, $journal->getId());
							if ($issue == null) {
								echo __('plugins.importexport.pubIds.cliError') . "\n";
								echo __('plugins.importexport.pubIds.cliError.issueNotFound', array('issueId' => $issueId)) . "\n\n";
								return;
							}
							$issues[] =& $issue;
						}
						if (!$this->exportPubIdsForIssues($journal, array(&$issue), $xmlFile)) {
							echo __('plugins.importexport.pubIds.cliError') . "\n";
							echo __('plugins.importexport.pubIds.cliError.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
				}
				break;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.pubIds.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}

}

?>
