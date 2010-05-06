{**
 * editors.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission editors table.
 *
 * $Id$
 *}
<div id="editors">
<h3>{translate key="user.role.editors"}</h3>
<form action="{url op="setEditorFlags"}" method="post">
<input type="hidden" name="monographId" value="{$submission->getId()}"/>
<table width="100%" class="listing">
	<tr class="heading" valign="bottom">
		<td width="{if $isEditor}20%{else}25%{/if}">&nbsp;</td>
		<td width="30%">&nbsp;</td>
		<td width="10%">{translate key="submission.review"}</td>
		<td width="10%">{translate key="submission.editing"}</td>
		<td width="{if $isEditor}20%{else}25%{/if}">{translate key="submission.request"}</td>
		{if $isEditor}<td width="10%">{translate key="common.action"}</td>{/if}
	</tr>
	{assign var=acqEdListed value=0}
	{iterate from=editAssignments item=editAssignment}
	{if $editAssignment->getEditorId() == $userId}
		{assign var=selfAssigned value=1}
	{/if}
		<tr valign="top">
			<td>{if $editAssignment->getIsEditor()}{translate key="user.role.editor"}{else}{assign var=acqEdListed value=1}{translate key="user.role.seriesEditor"}{/if}</td>
			<td>
				{assign var=emailString value=$editAssignment->getEditorFullName()|concat:" <":$editAssignment->getEditorEmail():">"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getLocalizedTitle|strip_tags monographId=$submission->getId()}
				{$editAssignment->getEditorFullName()|escape} {icon name="mail" url=$url}
			</td>
			<td>
				&nbsp;&nbsp;<input
					type="checkbox"
					name="canReview-{$editAssignment->getEditId()}"
					{if $editAssignment->getIsEditor()}
						checked="checked"
						disabled="disabled"
					{else}
						{if $editAssignment->getCanReview()} checked="checked"{/if}
						{if !$isEditor}disabled="disabled"{/if}
					{/if}
				/>
			</td>
			<td>
				&nbsp;&nbsp;<input
					type="checkbox"
					name="canEdit-{$editAssignment->getEditId()}"
					{if $editAssignment->getIsEditor()}
						checked="checked"
						disabled="disabled"
					{else}
						{if $editAssignment->getCanEdit()} checked="checked"{/if}
						{if !$isEditor}disabled="disabled"{/if}
					{/if}
				/>
			</td>
			<td>{if $editAssignment->getDateNotified()}{$editAssignment->getDateNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
			{if $isEditor}
				<td><a href="{url op="deleteEditAssignment" path=$editAssignment->getEditId()}" class="action">{translate key="common.delete"}</a></td>
			{/if}
		</tr>
	{/iterate}
		<tr><td class="separator" colspan="{if $isEditor}6{else}5{/if}">&nbsp;</td></tr>
</table>
{if $isEditor}
	{if $acqEdListed}<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>&nbsp;&nbsp;{/if}
	<a href="{url op="assignEditor" path="seriesEditor" monographId=$submission->getId()}" class="action">{translate key="editor.monograph.assignSeriesEditor"}</a>
	|&nbsp;<a href="{url op="assignEditor" path="editor" monographId=$submission->getId()}" class="action">{translate key="editor.monograph.assignEditor"}</a>
	{if !$selfAssigned}|&nbsp;<a href="{url op="assignEditor" path="editor" editorId=$userId monographId=$submission->getId()}" class="action">{translate key="common.addSelf"}</a>{/if}
{/if}
</form>
</div>
