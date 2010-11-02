{**
 * peerReview.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's editor decision table.

 *}
<div id="editorDecision">
<h3>{translate key="submission.editorDecision"}</h3>

{assign var=authorFiles value=$submission->getAuthorFileRevisions($submission->getCurrentReviewType(), $submission->getCurrentRound())}
{assign var=editorFiles value=$submission->getEditorFileRevisions($submission->getCurrentReviewType(), $submission->getCurrentRound())}

<table width="100%" class="data">
	<tr valign="top">
		<td class="label">{translate key="editor.monograph.decision"}</td>
		<td>
			{if $lastEditorDecision}
				{assign var="decision" value=$lastEditorDecision.decision}
				{translate key=$editorDecisionOptions.$decision} {$lastEditorDecision.dateDecided|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.notifyEditor"}
		</td>
		<td class="value" width="80%">
			{if !$submission->getEditAssignments()}
				{translate key="common.noneAssigned"}
			{else}
			{url|assign:"notifyAuthorUrl" op="emailEditorDecisionComment" monographId=$submission->getId()}
			{icon name="mail" url=$notifyAuthorUrl}
			&nbsp;&nbsp;&nbsp;&nbsp;
			{translate key="submission.editorAuthorRecord"}
			{if $submission->getMostRecentEditorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
				<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
			{/if}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.editorVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$editorFiles item=editorFile key=key}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$editorFile->getDateModified()|date_format:$dateFormatShort}<br />
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
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$authorFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="{url op="deleteMonographFile" path=$submission->getId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="action">{translate key="common.delete"}</a><br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.submit.uploadAuthorVersion"}
		</td>
		<td class="value" width="80%">
			<form method="post" action="{url op="uploadRevisedVersion"}" enctype="multipart/form-data">
				<input type="hidden" name="monographId" value="{$submission->getId()}" />
				{fbvFileInput id="upload" submit="submit"}
			</form>

		</td>
	</tr>
</table>
</div>
