<?php

/**
 * @file classes/notification/Notification.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Notification
 * @ingroup notification
 * @see NotificationDAO
 * @brief OMP subclass for Notifications (defines OMP-specific types and icons).
 */


/** Notification associative types. */
define('NOTIFICATION_TYPE_MONOGRAPH_SUBMITTED',			0x1000001);
define('NOTIFICATION_TYPE_METADATA_MODIFIED',			0x1000002);
define('NOTIFICATION_TYPE_REVIEWER_COMMENT',			0x1000003);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION',	0x1000004);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_INTERNAL_REVIEW',	0x1000005);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW',	0x1000006);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING',		0x1000007);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION',	0x1000008);
define('NOTIFICATION_TYPE_AUDITOR_REQUEST',			0x1000009);
define('NOTIFICATION_TYPE_SIGNOFF_COPYEDIT',			0x100000A);
define('NOTIFICATION_TYPE_REVIEW_ASSIGNMENT',			0x100000B);
define('NOTIFICATION_TYPE_SIGNOFF_PROOF',			0x100000C);
define('NOTIFICATION_TYPE_EDITOR_DECISION_INITIATE_REVIEW',	0x100000D);
define('NOTIFICATION_TYPE_EDITOR_DECISION_ACCEPT',		0x100000E);
define('NOTIFICATION_TYPE_EDITOR_DECISION_EXTERNAL_REVIEW',	0x100000F);
define('NOTIFICATION_TYPE_EDITOR_DECISION_PENDING_REVISIONS',	0x1000010);
define('NOTIFICATION_TYPE_EDITOR_DECISION_RESUBMIT',		0x1000011);
define('NOTIFICATION_TYPE_EDITOR_DECISION_DECLINE',		0x1000012);
define('NOTIFICATION_TYPE_EDITOR_DECISION_SEND_TO_PRODUCTION',	0x1000013);
define('NOTIFICATION_TYPE_REVIEW_ROUND_STATUS',			0x1000014);
define('NOTIFICATION_TYPE_PENDING_INTERNAL_REVISIONS',		0x1000015);
define('NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS',		0x1000016);
define('NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT',			0x1000017);
define('NOTIFICATION_TYPE_ALL_REVIEWS_IN',			0x1000018);
define('NOTIFICATION_TYPE_LAYOUT_ASSIGNMENT',			0x1000019);
define('NOTIFICATION_TYPE_INDEX_ASSIGNMENT',			0x100001A);
define('NOTIFICATION_TYPE_APPROVE_SUBMISSION',			0x100001B);
define('NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD',		0x100001C);
define('NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION',	0x100001D);
define('NOTIFICATION_TYPE_VISIT_CATALOG',			0x100001E);
define('NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_REQUIRED',			0x100001F);
define('NOTIFICATION_TYPE_ALL_REVISIONS_IN',			0x1000020);

import('lib.pkp.classes.notification.PKPNotification');

class Notification extends PKPNotification {
	/**
	 * Constructor.
	 */
	function Notification() {
		parent::PKPNotification();
	}
}

?>
