<?php

/**
 * @file SectionHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for section management functions. 
 */

// $Id$

import('pages.manager.ManagerHandler');

class SectionHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function SectionHandler() {
		parent::ManagerHandler();
	}
	/**
	 * Display a list of the sections within the current journal.
	 */
	function sections() {
		$this->validate();
		$this->setupTemplate();

		$journal =& Request::getJournal();
		$rangeInfo =& Handler::getRangeInfo('sections');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getJournalId(), $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'manager'), 'manager.journalManagement')));
		$templateMgr->assign_by_ref('sections', $sections);
		$templateMgr->assign('helpTopicId','journal.managementPages.sections');
		$templateMgr->display('manager/sections/sections.tpl');
	}

	/**
	 * Display form to create a new section.
	 */
	function createSection() {
		$this->editSection();
	}

	/**
	 * Display form to create/edit a section.
	 * @param $args array optional, if set the first parameter is the ID of the section to edit
	 */
	function editSection($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.SectionForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$sectionForm =& new SectionForm(!isset($args) || empty($args) ? null : ((int) $args[0]));
		if ($sectionForm->isLocaleResubmit()) {
			$sectionForm->readInputData();
		} else {
			$sectionForm->initData();
		}
		$sectionForm->display();
	}

	/**
	 * Save changes to a section.
	 */
	function updateSection($args) {
		$this->validate();

		import('manager.form.SectionForm');
		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$sectionForm =& new SectionForm(!isset($args) || empty($args) ? null : ((int) $args[0]));

		switch (Request::getUserVar('editorAction')) {
			case 'addSectionEditor':
				$sectionForm->includeSectionEditor((int) Request::getUserVar('userId'));
				$canExecute = false;
				break;
			case 'removeSectionEditor':
				$sectionForm->omitSectionEditor((int) Request::getUserVar('userId'));
				$canExecute = false;
				break;
			default:
				$canExecute = true;
				break;
		}

		$sectionForm->readInputData();
		if ($canExecute && $sectionForm->validate()) {
			$sectionForm->execute();
			Request::redirect(null, null, 'sections');

		} else {
			$this->setupTemplate(true);
			$sectionForm->display();
		}
	}

	/**
	 * Delete a section.
	 * @param $args array first parameter is the ID of the section to delete
	 */
	function deleteSection($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sectionDao->deleteSectionById($args[0], $journal->getJournalId());
		}

		Request::redirect(null, null, 'sections');
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSection() {
		$this->validate();

		$journal =& Request::getJournal();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection(Request::getUserVar('id'), $journal->getJournalId());

		if ($section != null) {
			$direction = Request::getUserVar('d');

			if ($direction != null) {
				// moving with up or down arrow
				$section->setSequence($section->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

			} else {
				// Dragging and dropping
				$prevId = Request::getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else
					$prevSeq = $sectionDao->getSection($prevId)->getSequence();

				$section->setSequence($prevSeq + .5);
			}

			$sectionDao->updateSection($section);
			$sectionDao->resequenceSections($journal->getJournalId());
		}

		// Moving up or down with the arrows requires a page reload.
		if ($direction != null) {
			Request::redirect(null, null, 'sections');
		}
	}

	function setupTemplate($subclass = false) {
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER));
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'sections'), 'section.sections'));
		}
	}
}

?>
