<?php

/**
 * @file AcquisitionsEditorHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AcquisitionsEditorHandler
 * @ingroup pages_acquisitionsEditor
 *
 * @brief Handle requests for acquistions editor functions. 
 */

// $Id$


// Filter arrangement
define('FILTER_ARRANGEMENT_ALL', 0);

import('handler.Handler');

class AcquisitionsEditorHandler extends Handler {
	/**
	 * Constructor
	 */
	 function AcquisitionsEditorHandler() {
	 	parent::Handler();
	 	
		$this->addCheck(new HandlerValidatorPress($this));
		// FIXME This is kind of evil
		$page = Request::getRequestedPage();
		if ( $page == 'acquisitionsEditor' )  
			$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_ACQUISITIONS_EDITOR)));
		elseif ( $page == 'editor' ) 		
			$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_EDITOR)));					
	 }

	/**
	 * Display acquisitions editor index page.
	 */
	function index($args) {
		$this->validate();
		$this->setupTemplate();

		$press =& Request::getPress();
		$pressId = $press->getId();
		$user =& Request::getUser();

		$rangeInfo = Handler::getRangeInfo('submissions');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$arrangementDao =& DAORegistry::getDAO('AcquisitionsArrangementDAO');
		$acquisitionsEditorSubmissionDao =& DAORegistry::getDAO('AcquisitionsEditorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$arrangements =& $arrangementDao->getTitlesByPressId($press->getId());

		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = Request::getUserVar('sortDirection');

		$filterArrangementOptions = array(
			FILTER_ARRANGEMENT_ALL => Locale::Translate('editor.allArrangements')
		) + $arrangements;

		switch($page) {
			case 'submissionsInEditing':
				$functionName = 'getAcquisitionsEditorSubmissionsInEditing';
				$helpTopicId = 'editorial.acquisitionsEditorsRole.submissions.inEditing';
				break;
			case 'submissionsArchives':
				$functionName = 'getAcquisitionsEditorSubmissionsArchives';
				$helpTopicId = 'editorial.acquisitionsEditorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getAcquisitionsEditorSubmissionsInReview';
				$helpTopicId = 'editorial.acquisitionsEditorsRole.submissions.inReview';
		}

		$filterArrangement = Request::getUserVar('filterArrangement');
		if ($filterArrangement != '' && array_key_exists($filterArrangement, $filterArrangementOptions)) {
			$user->updateSetting('filterArrangement', $filterArrangement, 'int', $pressId);
		} else {
			$filterArrangement = $user->getSetting('filterArrangement', $pressId);
			if ($filterArrangement == null) {
				$filterArrangement = FILTER_ARRANGEMENT_ALL;
				$user->updateSetting('filterArrangement', $filterArrangement, 'int', $pressId);
			}	
		}

		$submissions =& $acquisitionsEditorSubmissionDao->$functionName(
			$user->getId(),
			$press->getId(),
			$filterArrangement,
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo,
			$acquisitionsEditorSubmissionDao->getSortMapping($sort)
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('arrangementOptions', $filterArrangementOptions);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('filterArrangement', $filterArrangement);
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('acquisitionsEditor', $user->getFullName());

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'monograph.title',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_EDITOR => 'user.role.editor'
		));
		$templateMgr->assign('dateFieldOptions', Array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
			SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		));

		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);

		$templateMgr->display('acquisitionsEditor/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $monographId = 0, $parentPage = null, $showSidebar = true) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OMP_EDITOR));
		$templateMgr =& TemplateManager::getManager();
		$isEditor = Validation::isEditor();

		if (($page = Request::getRequestedPage()) == 'editor') {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole');

		} else {
			$templateMgr->assign('helpTopicId', 'editorial.acquisitionsEditorsRole');
		}

		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $isEditor?'editor':'acquisitionsEditor'), $isEditor?'user.role.editor':'user.role.acquisitionsEditor'), array(Request::url(null, 'acquisitionsEditor'), 'manuscript.submissions'))
			: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $isEditor?'editor':'acquisitionsEditor'), $isEditor?'user.role.editor':'user.role.acquisitionsEditor'));

		import('submission.acquisitionsEditor.AcquisitionsEditorAction');
		$submissionCrumb = AcquisitionsEditorAction::submissionBreadcrumb($monographId, $parentPage, $page);
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
