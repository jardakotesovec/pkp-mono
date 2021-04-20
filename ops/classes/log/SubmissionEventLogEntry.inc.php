<?php

/**
 * @file classes/log/SubmissionEventLogEntry.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEventLogEntry
 * @ingroup log
 *
 * @see SubmissionEventLogDAO
 *
 * @brief Describes an entry in the submission history log.
 */

import('lib.pkp.classes.log.PKPSubmissionEventLogEntry');

// Log entry associative types. All types must be defined here

// General events 					0x10000000
define('SUBMISSION_LOG_SUPPFILE_UPDATE', 0x10000003);
define('SUBMISSION_LOG_PREPRINT_PUBLISH', 0x10000006);
define('SUBMISSION_LOG_PREPRINT_IMPORT', 0x10000007);

// Author events 					0x20000000
define('SUBMISSION_LOG_AUTHOR_REVISION', 0x20000001);

// Editor events 					0x30000000
define('SUBMISSION_LOG_EDITOR_ASSIGN', 0x30000001);
define('SUBMISSION_LOG_EDITOR_UNASSIGN', 0x30000002);
define('SUBMISSION_LOG_EDITOR_FILE', 0x30000004);
define('SUBMISSION_LOG_EDITOR_ARCHIVE', 0x30000005);
define('SUBMISSION_LOG_EDITOR_RESTORE', 0x30000006);

// Reviewer events 					0x40000000
define('SUBMISSION_LOG_REVIEW_RECOMMENDATION_BY_PROXY', 0x40000016);

// Copyeditor events 					0x50000000
define('SUBMISSION_LOG_COPYEDIT_ASSIGN', 0x50000001);
define('SUBMISSION_LOG_COPYEDIT_UNASSIGN', 0x50000002);
define('SUBMISSION_LOG_COPYEDIT_INITIATE', 0x50000003);
define('SUBMISSION_LOG_COPYEDIT_REVISION', 0x50000004);
define('SUBMISSION_LOG_COPYEDIT_INITIAL', 0x50000005);
define('SUBMISSION_LOG_COPYEDIT_FINAL', 0x50000006);
define('SUBMISSION_LOG_COPYEDIT_SET_FILE', 0x50000007);
define('SUBMISSION_LOG_COPYEDIT_COPYEDIT_FILE', 0x50000008);
define('SUBMISSION_LOG_COPYEDIT_COPYEDITOR_FILE', 0x50000009);

// Proofreader events 					0x60000000
define('SUBMISSION_LOG_PROOFREAD_ASSIGN', 0x60000001);
define('SUBMISSION_LOG_PROOFREAD_UNASSIGN', 0x60000002);
define('SUBMISSION_LOG_PROOFREAD_INITIATE', 0x60000003);
define('SUBMISSION_LOG_PROOFREAD_REVISION', 0x60000004);
define('SUBMISSION_LOG_PROOFREAD_COMPLETE', 0x60000005);

// Layout events 					0x70000000
define('SUBMISSION_LOG_LAYOUT_ASSIGN', 0x70000001);
define('SUBMISSION_LOG_LAYOUT_UNASSIGN', 0x70000002);
define('SUBMISSION_LOG_LAYOUT_INITIATE', 0x70000003);
define('SUBMISSION_LOG_LAYOUT_GALLEY', 0x70000004);
define('SUBMISSION_LOG_LAYOUT_COMPLETE', 0x70000005);
define('SUBMISSION_LOG_LAYOUT_GALLEY_AVAILABLE', 0x70000006);
define('SUBMISSION_LOG_LAYOUT_GALLEY_UNAVAILABLE', 0x70000007);


class SubmissionEventLogEntry extends PKPSubmissionEventLogEntry
{
}
