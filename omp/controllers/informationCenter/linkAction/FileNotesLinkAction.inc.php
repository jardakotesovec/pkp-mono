<?php
/**
 * @file controllers/informationCenter/linkAction/FileNotesLinkAction.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileNotesLinkAction
 * @ingroup controllers_informationCenter_linkAction
 *
 * @brief An action to open up the notes IC for a file.
 */

import('controllers.api.file.linkAction.FileLinkAction');

class FileNotesLinkAction extends FileLinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $monographFile MonographFile the monograph file
	 *  to show information about.
	 * @param $user User
	 * @param $stageId int (optional) The stage id that user is looking at.
	 * @param $removeHistoryTab boolean (optional) Open the information center
	 * without the history tab.
	 */
	function FileNotesLinkAction(&$request, &$monographFile, $user, $stageId = null, $removeHistoryTab = false) {
		// Instantiate the information center modal.
		$router =& $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$title = (isset($monographFile)) ? implode(': ', array(__('informationCenter.bookInfo'), $monographFile->getLocalizedName())) : __('informationCenter.bookInfo');

		$ajaxModal = new AjaxModal(
			$router->url(
				$request, null,
				'informationCenter.FileInformationCenterHandler', 'viewInformationCenter',
				null, array_merge($this->getActionArgs($monographFile, $stageId), array('removeHistoryTab' => $removeHistoryTab))
			),
			$title,
			'modal_information'
		);

		// Configure the file link action.
		parent::FileLinkAction(
			'moreInformation', $ajaxModal,
			'', $this->getNotesState($monographFile, $user),
			__('common.notes.tooltip')
		);
	}

	function getNotesState($monographFile, $user) {
		$noteDao =& DAORegistry::getDAO('NoteDAO');

		// If no notes exist, display a dimmed icon.
		if (!$noteDao->notesExistByAssoc(ASSOC_TYPE_SUBMISSION_FILE, $monographFile->getFileId())) {
			return 'notes_none';
		}

		// If new notes exist, display a bold icon.
		if ($noteDao->unreadNotesExistByAssoc(ASSOC_TYPE_SUBMISSION_FILE, $monographFile->getFileId(), $user->getId())) {
			return 'notes_new';
		}

		// Otherwise, notes exist but not new ones.
		return 'notes';
	}
}

?>
