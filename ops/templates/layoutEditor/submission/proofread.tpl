{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editor's proofreading table.
 *
 * $Id$
 *}

<a name="proofread"></a>
<h3>{translate key="submission.proofreading"}</h3>

{if $useProofreaders}
<p>{translate key="user.role.proofreader"}:
{if $proofAssignment->getProofreaderId()}&nbsp; {$proofAssignment->getProofreaderFullName()}{/if} {icon name="mail" url="FIXME"}</p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2"></td>
		<td width="24%" class="heading">{translate key="submission.request"}</td>
		<td width="24%" class="heading">{translate key="submission.underway"}</td>
		<td width="24%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="23%">{translate key="submission.proofread.authorProof"}</td>
		<td>
			{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
				{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
	</tr>
	<tr>
		<td width="5%">2.</td>
		<td width="23%">{translate key="submission.proofread.proofreadProof"}</td>
		<td>
			{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useProofreaders}
					{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
	</tr>
	<tr>
		<td width="5%">3.</td>
		<td width="23%">{translate key="submission.proofread.layoutProof"}</td>
		<td>				{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>				{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if not $proofAssignment->getDateLayoutEditorNotified() or $proofAssignment->getDateLayoutEditorCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{icon name="mail" url="$requestPageUrl/layoutEditorProofreadingComplete?articleId=`$submission->getArticleId()`"}
			{/if}
						{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
	</tr>
</table>

{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
	{assign var="comment" value=$submission->getMostRecentProofreadComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}
