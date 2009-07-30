{**
 * active.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show copyeditor's active submissions.
 *
 * $Id$
 *}
<div id="submissions">
<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
			<td width="5%">{translate key="common.id"}</td>
			<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="common.assign"}</td>
			<td width="5%">{translate key="submissions.arrangement"}</td>
			<td width="30%">{translate key="monograph.authors"}</td>
			<td width="40%">{translate key="monograph.title"}</td>
			<td width="15%" align="right">{translate key="common.status"}</td>
		</tr>
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>

{iterate from=submissions item=submission}
	{assign var="copyeditingInitialSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_INITIAL')}
	{assign var="monographId" value=$submission->getMonographId()}
	<tr valign="top">
		<td>{$monographId|escape}</td>
		<td>{$copyeditingInitialSignoff->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getArrangementAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submission" path=$monographId}" class="action">{$submission->getLocalizedTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td align="right">
			{if not $copyeditingInitialSignoff->getDateCompleted()}
				{translate key="submissions.step1"}
			{else}
				{translate key="submissions.step3"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
</div>
