{**
 * submissionEventLogEntry.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a single event log entry.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.eventLog"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}" class="active">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}" class="active">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.history.submissionEventLog"}</td>
</tr>
<tr>
	<td>
		<table class="logEntry" width="100%">
		<tr>
			<td class="logEntryLabel">Log ID</td>
			<td class="logEntryContent">{$logEntry->getLogID()}</td>
		</tr>
		<tr>
			<td class="logEntryLabel">{translate key="common.date"}</td>
			<td class="logEntryContent">{$logEntry->getDateLogged()|date_format:$datetimeFormatLong}</td>
		</tr>
		<tr>
			<td class="logEntryLabel">{translate key="submission.event.logLevel"}</td>
			<td class="logEntryContent">{translate key=$logEntry->getLogLevelString()}</td>
		</tr>
		<tr>
			<td class="logEntryLabel">{translate key="common.type"}</td>
			<td class="logEntryContent">{translate key=$logEntry->getAssocTypeLongString()}</td>
		</tr>
		<tr>
			<td class="logEntryLabel">{translate key="common.user"}</td>
			<td class="logEntryContent">{$logEntry->getUserFullName()} (<a href="mailto:{$logEntry->getUserEmail()}">{$logEntry->getUserEmail()}</a>)</td>
		</tr>
		<tr valign="top">
			<td class="logEntryLabel">{translate key="common.event"}</td>
			<td class="logEntryContent">
				<span class="boldText">{translate key=$logEntry->getEventTitle()}</span>
				<br /><br />
				{$logEntry->getMessage()|nl2br}
			</td>
		</tr>
		</table>
	</td>
</tr>
{if $isEditor}
<tr>
	<td>
		<table class="logEntry" width="100%">
		<tr>
			<td>
				<a href="{$requestPageUrl}/clearSubmissionEventLog/{$submission->getArticleId()}/{$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.event.confirmDeleteLogEntry"}')" class="tableButton">{translate key="submission.event.deleteLogEntry"}</a>
			</td>
		</tr>
		</table>
	</td>
</tr>
{/if}
</table>
</div>

&#187; <a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.event.backToEventLog"}</a>

{include file="common/footer.tpl"}
