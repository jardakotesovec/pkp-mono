{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show section editor's submissions in editing.
 *
 * $Id$
 *}
<div id="submissions">
<table width="100%" class="listing">
	<tr><td colspan="8" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
	<td width="5%">{sort_search key="common.id" heading="id"}</td>
		<td width="5%">{sort_search key="submissions.submit" heading="submitDate"}</td>
		<td width="5%">{sort_search key="submissions.sec" heading="section"}</td>
		<td width="20%">{sort_search key="monograph.authors" heading="authors"}</td>
		<td width="25%">{sort_search key="monograph.title" heading="title"}</td>
		<td width="10%">{sort_search key="submission.copyedit" heading="subCopyedit"}</td>
		<td width="10%">{sort_search key="submission.layout" heading="subLayout"}</td>
		<td width="10%">{sort_search key="submissions.proof" heading="subProof"}</td>
	</tr>
	<tr><td colspan="8" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}

	{assign var="layoutEditorProofSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_LAYOUT')}
	{assign var="layoutSignoff" value=$submission->getSignoff('SIGNOFF_LAYOUT')}
	{assign var="copyeditorFinalSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_FINAL')}
	{assign var="monographId" value=$submission->getMonographId()}
	{assign var="highlightClass" value=$submission->getHighlightClass()}
	<tr valign="top"{if $highlightClass} class="{$highlightClass|escape}"{/if}>
		<td>{$submission->getMonographId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getAcquisitionsArrangementAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$monographId}" class="action">{$submission->getLocalizedTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td>{$copyeditorFinalSignoff->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>{$layoutSignoff->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>{$layoutEditorProofSignoff->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="8" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="endseparator">&nbsp;</td>
	<tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="4" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth section=$section}</td>
	</tr>
{/if}
</table>
</div>
