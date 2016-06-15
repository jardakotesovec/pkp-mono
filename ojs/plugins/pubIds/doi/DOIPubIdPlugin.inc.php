<?php

/**
 * @file plugins/pubIds/doi/DOIPubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdPlugin
 * @ingroup plugins_pubIds_doi
 *
 * @brief DOI plugin class
 */


import('classes.plugins.PubIdPlugin');

class DOIPubIdPlugin extends PubIdPlugin {

	//
	// Implement template methods from Plugin.
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.doi.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.doi.description');
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 * @param $inCore boolean True iff a core template should be preferred
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}


	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @copydoc PKPPubIdPlugin::constructPubId()
	 */
	function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId) {
		return $pubIdPrefix . '/' . $pubIdSuffix;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'Digital Object Identifier';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($journalId, $pubId) {
		return 'http://dx.doi.org/'.$this->_doiURLEncode($pubId);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplatePath().'doiSuffixEdit.tpl';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdAssignFile()
	 */
	function getPubIdAssignFile() {
		return $this->getTemplatePath().'doiAssign.tpl';
	}

	/**
	 * @copydoc PKPPubIdPlugin::instantiateSettingsForm()
	 */
	function instantiateSettingsForm($contextId) {
		$this->import('classes.form.DOISettingsForm');
		return new DOISettingsForm($this, $contextId);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('doiSuffix');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getAssignFormFieldName()
	 */
	function getAssignFormFieldName() {
		return 'assignDoi';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPrefixFieldName()
	 */
	function getPrefixFieldName() {
		return 'doiPrefix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixFieldName()
	 */
	function getSuffixFieldName() {
		return 'doiSuffix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getLinkActions()
	 */
	function getLinkActions($pubObject) {
		$linkActions = array();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$userVars = $request->getUserVars();
		$userVars['pubIdPlugIn'] = get_class($this);
		// Clear object pub id
		$linkActions['clearPubIdLinkActionDoi'] = new LinkAction(
			'clearPubId',
			new RemoteActionConfirmationModal(
				__('plugins.pubIds.doi.editor.clearObjectsDoi.confirm'),
				__('common.delete'),
				$request->url(null, null, 'clearPubId', null, $userVars),
				'modal_delete'
			),
			__('plugins.pubIds.doi.editor.clearObjectsDoi'),
			'delete',
			__('plugins.pubIds.doi.editor.clearObjectsDoi')
		);

		if (is_a($pubObject, 'Issue')) {
			// Clear issue objects pub ids
			$linkActions['clearIssueObjectsPubIdsLinkActionDoi'] = new LinkAction(
				'clearIssueObjectsPubIds',
				new RemoteActionConfirmationModal(
					__('plugins.pubIds.doi.editor.clearIssueObjectsDoi.confirm'),
					__('common.delete'),
					$request->url(null, null, 'clearIssueObjectsPubIds', null, $userVars),
					'modal_delete'
				),
				__('plugins.pubIds.doi.editor.clearIssueObjectsDoi'),
				'delete',
				__('plugins.pubIds.doi.editor.clearIssueObjectsDoi')
			);
		}

		return $linkActions;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixPatternsFieldNames()
	 */
	function getSuffixPatternsFieldNames() {
		return  array(
			'Issue' => 'doiIssueSuffixPattern',
			'Article' => 'doiArticleSuffixPattern',
			'Representation' => 'doiRepresentationSuffixPattern',
			'SubmissionFile' => 'doiSubmissionFileSuffixPattern',
		);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::doi');
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function isObjectTypeEnabled($pubObjectType, $contextId) {
		return $this->getSetting($contextId, "enable${pubObjectType}Doi") == '1';
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function getNotUniqueErrorMsg() {
		return __('plugins.pubIds.doi.editor.doiSuffixCustomIdentifierNotUnique');
	}

	/**
	 * @copydoc PKPPubIdPlugin::validatePubId()
	 */
	function validatePubId($pubId) {
		return preg_match('/^\d+(.\d+)+\//', $pubId);
	}


	/*
	 * Private methods
	 */
	/**
	 * Encode DOI according to ANSI/NISO Z39.84-2005, Appendix E.
	 * @param $pubId string
	 * @return string
	 */
	function _doiURLEncode($pubId) {
		$search = array ('%', '"', '#', ' ', '<', '>', '{');
		$replace = array ('%25', '%22', '%23', '%20', '%3c', '%3e', '%7b');
		$pubId = str_replace($search, $replace, $pubId);
		return $pubId;
	}

}

?>
