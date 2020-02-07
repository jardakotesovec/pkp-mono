<?php

/**
 * @file classes/notification/Notification.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSNotification
 * @ingroup notification
 * @see NotificationDAO
 * @brief OJS subclass for Notifications (defines OJS-specific types).
 */

/** Notification associative types. */
// OPS-specific trivial notifications

import('lib.pkp.classes.notification.PKPNotification');
import('lib.pkp.classes.notification.NotificationDAO');

class Notification extends PKPNotification { }

