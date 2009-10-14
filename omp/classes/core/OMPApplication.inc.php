<?php

/**
 * @file classes/core/OMPApplication.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OMPApplication
 * @ingroup core
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

// $Id: OMPApplication.inc.php,v 1.24 2009/10/14 19:25:59 tylerl Exp $


import('core.PKPApplication');
 	
define('ASSOC_TYPE_PRESS',			0x0000200);
define('ASSOC_TYPE_MONOGRAPH',			0x0000201);
define('ASSOC_TYPE_PRODUCTION_ASSIGNMENT',	0x0000202);

class OMPApplication extends PKPApplication {
	function OMPApplication() {
		parent::PKPApplication();
	}

	function initialize(&$application) {
		PKPApplication::initialize($application);

		import('i18n.Locale');
		import('core.Request');
	}

	/**
	 * Get the "context depth" of this application, i.e. the number of
	 * parts of the URL after index.php that represent the context of
	 * the current request (e.g. Journal [1], or Conference and
	 * Scheduled Conference [2], or Press [1]).
	 * @return int
	 */
	function getContextDepth() {
		return 1;
	}
		
	function getContextList() {
		return array('press');
	}

	/**
	 * Get the symbolic name of this application
	 * @return string
	 */
	function getName() {
		return 'omp';
	}

	/**
	 * Get the locale key for the name of this application.
	 * @return string
	 */
	function getNameKey() {
		return('common.openMonographPress');
	}

	/**
	 * Get the URL to the XML descriptor for the current version of this
	 * application.
	 * @return string
	 */
	function getVersionDescriptorUrl() {
		return('http://pkp.sfu.ca/omp/xml/omp-version.xml');
	}

	/**
	 * Determine whether or not the request is cacheable.
	 * @return boolean
	 */
	function isCacheable() {
		if (defined('SESSION_DISABLE_INIT')) return false;
		if (!Config::getVar('general', 'installed')) return false;
		if (!empty($_POST) || Validation::isLoggedIn()) return false;
		if (!PKPRequest::isPathInfoEnabled()) {
			$ok = array('press', 'page', 'op', 'path');
			if (!empty($_GET) && count(array_diff(array_keys($_GET), $ok)) != 0) {
				return false;
			}
		} else {
			if (!empty($_GET)) return false;
		}

		if (in_array(PKPRequest::getRequestedPage(), array(
			'about', 'announcement', 'help', 'index', 'information', 'rt', ''
		))) return true;

		return false;
	}

	/**
	 * Get the filename to use for cached content for the current request.
	 * @return string
	 */
	function getCacheFilename() {
		static $cacheFilename;
		if (!isset($cacheFilename)) {
			if (PKPRequest::isPathInfoEnabled()) {
				$id = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'index';
				$id .= '-' . Locale::getLocale();
			} else {
				$id = Request::getUserVar('press') . '-' . Request::getUserVar('page') . '-' . Request::getUserVar('op') . '-' . Request::getUserVar('path') . '-' . Locale::getLocale();
			}
			$path = dirname(dirname(dirname(__FILE__)));
			$cacheFilename = $path . '/cache/wc-' . md5($id) . '.html';
		}
		return $cacheFilename;
	}

	/**
	 * Get the map of DAOName => full.class.Path for this application.
	 * @return array
	 */
	function getDAOMap() {
		return array_merge(parent::getDAOMap(), array(
			'AnnouncementDAO' => 'announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'announcement.AnnouncementTypeDAO',
			'BookFileTypeDAO' => 'bookFile.BookFileTypeDAO',
			'MonographEmailLogDAO' => 'monograph.log.MonographEmailLogDAO',
			'MonographEventLogDAO' => 'monograph.log.MonographEventLogDAO',
			'MonographCommentDAO' => 'monograph.MonographCommentDAO',
			'MonographComponentDAO' => 'monograph.MonographComponentDAO',
			'MonographSearchDAO' => 'search.MonographSearchDAO',
			'MonographFileSettingsDAO' => 'monograph.MonographFileSettingsDAO',
			'MonographDAO' => 'monograph.MonographDAO',
			'ProductionAssignmentDAO' => 'submission.productionAssignment.ProductionAssignmentDAO',
			'AcquisitionsArrangementDAO' => 'press.AcquisitionsArrangementDAO',
			'AcquisitionsArrangementEditorsDAO' => 'press.AcquisitionsArrangementEditorsDAO',
			'MonographFileDAO' => 'monograph.MonographFileDAO',
			'MonographGalleyDAO' => 'monograph.MonographGalleyDAO',
			'NotificationStatusDAO' => 'press.NotificationStatusDAO',
			'AuthorDAO' => 'monograph.AuthorDAO',
			'AuthorSubmissionDAO' => 'submission.author.AuthorSubmissionDAO',
			'ProductionEditorSubmissionDAO' => 'submission.productionEditor.ProductionEditorSubmissionDAO',
			'CopyeditorSubmissionDAO' => 'submission.copyeditor.CopyeditorSubmissionDAO',
			'EditAssignmentDAO' => 'submission.editAssignment.EditAssignmentDAO',
			'EditorSubmissionDAO' => 'submission.editor.EditorSubmissionDAO',
			'EmailTemplateDAO' => 'mail.EmailTemplateDAO',
			'DesignerSubmissionDAO' => 'submission.designer.DesignerSubmissionDAO',
			'PluginSettingsDAO' => 'plugins.PluginSettingsDAO',
			'PressDAO' => 'press.PressDAO',
			'PressSettingsDAO' => 'press.PressSettingsDAO',
			'ReviewAssignmentDAO' => 'submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'submission.reviewer.ReviewerSubmissionDAO',
			'ReviewFormDAO' => 'reviewForm.ReviewFormDAO',
			'ReviewRoundDAO' => 'monograph.reviewRound.ReviewRoundDAO',
			'ReviewFormElementDAO' => 'reviewForm.ReviewFormElementDAO',
			'ReviewFormResponseDAO' => 'reviewForm.ReviewFormResponseDAO',
			'WorkflowDAO' => 'workflow.WorkflowDAO',
			'LayoutAssignmentDAO' => 'submission.layoutAssignment.LayoutAssignmentDAO',
			'RoleDAO' => 'security.RoleDAO',
			'FlexibleRoleDAO' => 'role.FlexibleRoleDAO',
			'SuppFileDAO' => 'monograph.SuppFileDAO',
			'AcquisitionsEditorSubmissionDAO' => 'submission.acquisitionsEditor.AcquisitionsEditorSubmissionDAO',
			'UserDAO' => 'user.UserDAO',
			'UserSettingsDAO' => 'user.UserSettingsDAO',
			'SignoffEntityDAO' => 'signoff.SignoffEntityDAO'
		));
	}

	/**
	 * Get the list of plugin categories for this application.
	 */
	function getPluginCategories() {
		return array(
			'auth',
			'blocks',
			'generic',
			'importexport',
			'themes'
		);
	}

	/**
	 * Instantiate the help object for this application.
	 * @return object
	 */
	function &instantiateHelp() {
		import('help.Help');
		$help = new Help();
		return $help;
	}
}

?>
