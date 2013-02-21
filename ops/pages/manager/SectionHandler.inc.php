<?php

/**
 * @file pages/manager/SectionHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for section management functions.
 */

import('pages.manager.ManagerHandler');

class SectionHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function SectionHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the sections within the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function sections($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$rangeInfo = $this->getRangeInfo($request, 'sections');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getByJournalId($journal->getId(), $rangeInfo);
		$emptySectionIds = $sectionDao->getEmptyByJournalId($journal->getId());
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('sections', $sections);
		$templateMgr->assign('emptySectionIds', $emptySectionIds);
		$templateMgr->display('manager/sections/sections.tpl');
	}

	/**
	 * Display form to create a new section.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createSection($args, &$request) {
		$this->editSection($args, $request);
	}

	/**
	 * Display form to create/edit a section.
	 * @param $args array if set the first parameter is the ID of the section to edit
	 * @param $request PKPRequest
	 */
	function editSection($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.manager.form.SectionForm');

		$sectionForm = new SectionForm(!isset($args) || empty($args) ? null : ((int) $args[0]));
		if ($sectionForm->isLocaleResubmit()) {
			$sectionForm->readInputData();
		} else {
			$sectionForm->initData();
		}
		$sectionForm->display();
	}

	/**
	 * Save changes to a section.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateSection($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.manager.form.SectionForm');
		$sectionForm = new SectionForm(!isset($args) || empty($args) ? null : ((int) $args[0]));

		switch ($request->getUserVar('editorAction')) {
			case 'addSectionEditor':
				$sectionForm->includeSectionEditor((int) $request->getUserVar('userId'));
				$canExecute = false;
				break;
			case 'removeSectionEditor':
				$sectionForm->omitSectionEditor((int) $request->getUserVar('userId'));
				$canExecute = false;
				break;
			default:
				$canExecute = true;
				break;
		}

		$sectionForm->readInputData();
		if ($canExecute && $sectionForm->validate()) {
			$sectionForm->execute();
			$request->redirect(null, null, 'sections');
		} else {
			$sectionForm->display();
		}
	}

	/**
	 * Delete a section.
	 * @param $args array first parameter is the ID of the section to delete
	 * @param $request PKPRequest
	 */
	function deleteSection($args, &$request) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& $request->getJournal();

			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$sectionDao->deleteSectionById($args[0], $journal->getId());
		}
		$request->redirect(null, null, 'sections');
	}

	/**
	 * Change the sequence of a section.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function moveSection($args, &$request) {
		$this->validate();

		$journal =& $request->getJournal();

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($request->getUserVar('id'), $journal->getId());

		if ($section != null) {
			$direction = $request->getUserVar('d');

			if ($direction != null) {
				// moving with up or down arrow
				$section->setSequence($section->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

			} else {
				// Dragging and dropping
				$prevId = $request->getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevJournal = $sectionDao->getById($prevId);
					$prevSeq = $prevJournal->getSequence();
				}

				$section->setSequence($prevSeq + .5);
			}

			$sectionDao->updateObject($section);
			$sectionDao->resequenceSections($journal->getId());
		}

		// Moving up or down with the arrows requires a page reload.
		if ($direction != null) {
			$request->redirect(null, null, 'sections');
		}
	}

	/**
	 * Configure the template.
	 * @param $request PKPRequest
	 */
	function setupTemplate($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
		parent::setupTemplate($request);
	}
}

?>
