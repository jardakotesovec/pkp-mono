{**
 * templates/controllers/informationCenter/submissionHistory.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display submission history in information center.
 *}
{url|assign:eventLogGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.eventLog.SubmissionEventLogGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}
{load_url_in_div id="eventLogGrid" url=$eventLogGridUrl}
