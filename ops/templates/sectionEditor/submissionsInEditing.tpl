{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show section editor's submissions in editing.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr><td colspan="8" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td width="25%">{translate key="article.title"}</td>
		<td width="10%">{translate key="submission.copyedit"}</td>
		<td width="10%">{translate key="submission.layout"}</td>
		<td width="10%">{translate key="submissions.proof"}</td>
	</tr>
	<tr><td colspan="8" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}

	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionEditing/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>{$layoutAssignment->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="8" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="endseparator"></td>
	<tr>
{/foreach}
</table>
