{**
 * peerReview.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's peer review table.
 *
 * $Id$
 *}
<div id="peerReview">
<h3>{translate key="submission.peerReview"}</h3>

{foreach from=$reviewProcesses item=reviewProcess}
<h3>{$reviewProcess->getTitle()}</h3>
{if $reviewProcess->getDateInitiated() == null}

	<table class="info" width="100%">
		<tr><td colspan="2" class="separator">&nbsp;</td></tr>
		<tr valign="middle">
			<td colspan="2" align="center">{translate key="common.notAvailable"}</td>
		</tr>

		<tr><td colspan="2" class="separator">&nbsp;</td></tr>

	</table>


{/if}
{assign var=processId value=$reviewProcess->getProcessId()}

{assign var=start value="A"|ord}
{section name="round" loop=$reviewRounds[$processId]}
{assign var="round" value=$smarty.section.round.index+1}
{assign var=authorFiles value=$submission->getAuthorFileRevisions($processId, $round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($processId, $round)}
{assign var="viewableFiles" value=$authorViewableFilesByRound[$processId]}

<h4>{translate key="submission.round" round=$round}</h4>
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.reviewVersion"}
		</td>
		<td class="value" width="80%">
			{assign var=reviewFile value=$reviewFilesByRound[$processId][$round]}
			{if $reviewFile}
				<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;{$reviewFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.initiated"}
		</td>
		<td class="value" width="80%">
			{if $reviewEarliestNotificationByRound[$processId][$round]}
				{$reviewEarliestNotificationByRound[$processId][$round]|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.lastModified"}
		</td>
		<td class="value" width="80%">
			{if $reviewModifiedByRound[$processId][$round]}
				{$reviewModifiedByRound[$processId][$round]|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="common.uploadedFile"}
		</td>
		<td class="value" width="80%">
			{foreach from=$viewableFiles item=reviewerFiles key=reviewer}
				{foreach from=$reviewerFiles item=viewableFilesForReviewer key=reviewId}
					{assign var="roundIndex" value=$reviewIndexesByRound[$round][$reviewId]}
					{assign var=thisReviewer value=$start+$roundIndex|chr}
					{foreach from=$viewableFilesForReviewer item=viewableFile}
						{translate key="user.role.reviewer"} {$thisReviewer|escape}
						<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$viewableFile->getFileId():$viewableFile->getRevision()}" class="file">{$viewableFile->getFileName()|escape}</a>&nbsp;&nbsp;{$viewableFile->getDateModified()|date_format:$dateFormatShort}<br />
					{/foreach}
				{/foreach}
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	{if !$smarty.section.round.last}
		<tr valign="top">
			<td class="label" width="20%">
				{translate key="submission.editorVersion"}
			</td>
			<td class="value" width="80%">
				{foreach from=$editorFiles item=editorFile key=key}
					<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$editorFile->getDateModified()|date_format:$dateFormatShort}<br />
				{foreachelse}
					{translate key="common.none"}
				{/foreach}
			</td>
		</tr>
		<tr valign="top">
			<td class="label" width="20%">
				{translate key="submission.authorVersion"}
			</td>
			<td class="value" width="80%">
				{foreach from=$authorFiles item=authorFile key=key}
					<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$authorFile->getDateModified()|date_format:$dateFormatShort}<br />
				{foreachelse}
					{translate key="common.none"}
				{/foreach}
			</td>
		</tr>
	{/if}

</table>

{if $smarty.section.round.last}
	<!--<div class="separator"></div>-->
{/if}

{/section}

{/foreach}

</div>