<?php
/**
 * @defgroup controllers_review_linkAction
 */

/**
 * @file controllers/review/linkAction/SendReminderLinkAction.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SendReminderLinkAction
 * @ingroup controllers_api_task
 *
 * @brief An action to open up a modal to send a reminder to users assigned to a task.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class SendReminderLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $actionArgs array The action arguments.
	 */
	function SendReminderLinkAction(&$request, $modalTitle, $actionArgs) {
		// Instantiate the send review modal.
		$router =& $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$ajaxModal = new AjaxModal(
			$router->url($request, null, null, 'editReminder', null, $actionArgs),
			__($modalTitle),
			'review_reminder'
		);

		// Configure the link action.
		parent::LinkAction(
			'sendReminder',
			$ajaxModal,
			null,
			'overdue'
		);
	}
}

?>
