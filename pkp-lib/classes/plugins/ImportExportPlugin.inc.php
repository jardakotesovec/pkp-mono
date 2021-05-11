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

namespace PKP\plugins;

use APP\core\Services;
use APP\i18n\AppLocale;
use APP\template\TemplateManager;
use Exception;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;

use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\linkAction\LinkAction;

use PKP\linkAction\request\RedirectAction;

abstract class ImportExportPlugin extends Plugin
{
    /** @var PKPImportExportDeployment The deployment that processes import/export operations */
    public $_childDeployment = null;

    /** @var Request Request made available for plugin URL generation */
    public $_request;

    /**
     * Execute import/export tasks using the command-line interface.
     *
     * @param $scriptName The name of the command-line script (displayed as usage info)
     * @param $args Parameters to the plugin
     */
    abstract public function executeCLI($scriptName, &$args);

    /**
     * Display the command-line usage information
     *
     * @param $scriptName string
     */
    abstract public function usage($scriptName);

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $dispatcher = $request->getDispatcher();
        return array_merge(
            [
                new LinkAction(
                    'settings',
                    new RedirectAction($dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_PAGE,
                        null,
                        'management',
                        'importexport',
                        ['plugin', $this->getName()]
                    )),
                    __('manager.importExport'),
                    null
                ),
            ],
            parent::getActions($request, $actionArgs)
        );
    }

    /**
     * Display the import/export plugin.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function display($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->registerPlugin('function', 'plugin_url', [$this, 'pluginUrl']);
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
     *
     * @see calling conventions at http://www.smarty.net/docsv2/en/api.register.function.tpl
     *
     * @param $params array
     * @param $smarty Smarty
     *
     * @return string
     */
    public function pluginUrl($params, $smarty)
    {
        $dispatcher = $this->_request->getDispatcher();
        return $dispatcher->url($this->_request, PKPApplication::ROUTE_PAGE, null, 'management', 'importexport', array_merge(['plugin', $this->getName(), $params['path'] ?? []]));
    }

    /**
     * Check if this is a relative path to the xml document
     * that describes public identifiers to be imported.
     *
     * @param $url string path to the xml file
     */
    public function isRelativePath($url)
    {
        // FIXME This is not very comprehensive, but will work for now.
        if ($this->isAllowedMethod($url)) {
            return false;
        }
        if ($url[0] == '/') {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the specified URL describes an allowed protocol.
     *
     * @param $url string
     *
     * @return boolean
     */
    public function isAllowedMethod($url)
    {
        $allowedPrefixes = [
            'http://',
            'ftp://',
            'https://',
            'ftps://'
        ];
        foreach ($allowedPrefixes as $prefix) {
            if (substr($url, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the plugin ID used as plugin settings prefix.
     *
     * @return string
     */
    public function getPluginSettingsPrefix()
    {
        return '';
    }

    /**
     * Return the plugin export directory.
     *
     * @return string The export directory path.
     */
    public function getExportPath()
    {
        return Config::getVar('files', 'files_dir') . '/temp/';
    }

    /**
     * Return the whole export file name.
     *
     * @param $basePath string Base path for temporary file storage
     * @param $objectsFileNamePart string Part different for each object type.
     * @param $context Context
     * @param $extension string
     *
     * @return string
     */
    public function getExportFileName($basePath, $objectsFileNamePart, $context, $extension = '.xml')
    {
        return $basePath . $this->getPluginSettingsPrefix() . '-' . date('Ymd-His') . '-' . $objectsFileNamePart . '-' . $context->getId() . $extension;
    }

    /**
     * Display XML validation errors.
     *
     * @param $errors array
     * @param $xml string
     */
    public function displayXMLValidationErrors($errors, $xml)
    {
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
    public function setDeployment($deployment)
    {
        $this->_childDeployment = $deployment;
    }

    /**
     * Get the deployment that processes import/export operations
     *
     * @return PKPImportExportDeployment
     */
    public function getDeployment()
    {
        return $this->_childDeployment;
    }

    /**
     * Get the submissions and proceed to the export
     *
     * @param $submissionIds array Array of submissions to export
     * @param $deployment PKPNativeImportExportDeployment
     * @param $opts array
     */
    public function getExportSubmissionsDeployment($submissionIds, $deployment, $opts = [])
    {
        $filter = $this->getExportFilter('exportSubmissions');

        $submissions = [];
        foreach ($submissionIds as $submissionId) {
            /** @var APP\services\SubmissionService $submissionService */
            $submissionService = Services::get('submission');
            $submission = $submissionService->get($submissionId);

            if ($submission && $submission->getData('contextId') == $deployment->getContext()->getId()) {
                $submissions[] = $submission;
            }
        }

        $deployment->export($filter, $submissions, $opts);
    }

    /**
     * Save the export result as an XML
     *
     * @param $deployment PKPNativeImportExportDeployment
     *
     * @return string
     */
    public function exportResultXML($deployment)
    {
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
     *
     * @param $deployment PKPNativeImportExportDeployment
     * @param $templateMgr PKPTemplateManager
     * @param $exportFileName string
     *
     * @return string
     */
    public function getExportTemplateResult($deployment, $templateMgr, $exportFileName)
    {
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
     *
     * @param $filter string
     * @param $xmlString string
     * @param $deployment PKPNativeImportExportDeployment
     * @param $templateMgr PKPTemplateManager
     *
     * @return string
     */
    public function getImportTemplateResult($filter, $xmlString, $deployment, $templateMgr)
    {
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
     *
     * @param $temporaryFileId int
     * @param $user User
     *
     * @return string
     */
    public function getImportedFilePath($temporaryFileId, $user)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

        $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /** @var TemporaryFileDAO $temporaryFileDao */

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
     *
     * @param $request PKPRequest
     * @param $title string
     * @param $bounceUrl string
     * @param $bounceParameterArray array
     *
     * @return string
     */
    public function getBounceTab($request, $title, $bounceUrl, $bounceParameterArray)
    {
        if (!$request->checkCSRF()) {
            throw new Exception('CSRF mismatch!');
        }
        $json = new JSONMessage(true);
        $json->setEvent('addTab', [
            'title' => $title,
            'url' => $request->url(null, null, null, ['plugin', $this->getName(), $bounceUrl], $bounceParameterArray),
        ]);
        header('Content-Type: application/json');
        return $json->getString();
    }

    /**
     * Download file given it's name
     *
     * @param $exportFileName string
     */
    public function downloadExportedFile($exportFileName)
    {
        $fileManager = new FileManager();
        $fileManager->downloadByPath($exportFileName);
        $fileManager->deleteByPath($exportFileName);
    }

    /**
     * Create file given it's name and content
     *
     * @param $filename string
     * @param $fileContent string
     * @param $context Context
     *
     * @return string
     */
    public function writeExportedFile($filename, $fileContent, $context)
    {
        $fileManager = new FileManager();
        $exportFileName = $this->getExportFileName($this->getExportPath(), $filename, $context, '.xml');
        $fileManager->writeFile($exportFileName, $fileContent);

        return $exportFileName;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\plugins\ImportExportPlugin', '\ImportExportPlugin');
}
