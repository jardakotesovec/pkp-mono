{**
 * submissionNotes.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a list of submission notes.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.notes"}
{include file="common/header.tpl"}

{literal}
<script type="text/javascript">
{/literal}
	var toggleAll = 0;
	var noteArray = new Array();
	{foreach from=$submissionNotes item=note}
	noteArray.push({$note->getNoteId()});
	{/foreach}
{literal}
	function toggleNote(divNoteId) {
		var domStyle = getBrowserObject(divNoteId,1);
		domStyle.display = (domStyle.display == "block") ? "none" : "block";
	}

	function toggleNoteAll() {
		for(var i = 0; i < noteArray.length; i++) {
			var domStyle = getBrowserObject(noteArray[i],1);
			domStyle.display = toggleAll ? "none" : "block";
		}
		toggleAll = toggleAll ? 0 : 1;

		var collapse = getBrowserObject("collapseNotes",1);
		var expand = getBrowserObject("expandNotes",1);
		if (collapse.display == "inline") {
			collapse.display = "none";
			expand.display = "inline";
		} else {
			collapse.display = "inline";
			expand.display = "none";
		}
	}
</script>
{/literal}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}" class="active">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="{$requestPageUrl}/submissionEventLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEmailLog/{$submission->getArticleId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{$requestPageUrl}/submissionNotes/{$submission->getArticleId()}" class="active">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.notes"}</td>
</tr>
<tr class="subHeading">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr valign="top">
				<td width="15%">{translate key="common.date"}</td>
				<td width="30%">{translate key="common.title"}</td>
				<td width="10%">{translate key="submission.notes.attachedFile"}</td>
				<td width="45%" align="right">{translate key="common.action"}</td>
			</tr>
		</table>
	</td>
</tr>
{foreach from=$submissionNotes item=note}
<tr class="{cycle values="logRow,logRowAlt"}">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr valign="top">
				<td width="15%" valign="top">{$note->getDateModified()}</td>
				<td width="30%" valign="top"><a href="javascript:toggleNote({$note->getNoteId()})" class="tableAction">{$note->getTitle()}</a><div class="note" id="{$note->getNoteId()}" name="{$note->getNoteId()}">{$note->getNote()}</div></td>
				<td width="10%" valign="top">{if $note->getFileId()}{translate key="common.yes"}{else}{translate key="common.no"}{/if}</td>
				<td width="45%" valign="top" align="right"><a href="{$pageUrl}/sectionEditor/submissionNotes/{$submission->getArticleId()}/edit/{$note->getNoteId()}" class="icon"><img src="{$baseUrl}/templates/images/view.gif" width="16" height="16" border="0" alt="" /></a>&nbsp;<a href="#" onclick="confirmAction('{$pageUrl}/sectionEditor/removeSubmissionNote?articleId={$submission->getArticleId()}&amp;noteId={$note->getNoteId()}&amp;fileId={$note->getFileId()}', '{translate|escape:"javascript" key="submission.notes.confirmDelete"}')" class="icon"><img src="{$baseUrl}/templates/images/delete.gif" width="16" height="16" border="0" alt="" /></a></td>
			</tr>
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRow">
	<td class="submissionBox" align="center"><span class="boldText">{translate key="submission.notes.noSubmissionNotes"}</span></td>
</tr>
{/foreach}
<tr class="subHeading">
	<td class="submissionBox">
		<a href="javascript:toggleNoteAll()"><div id="expandNotes" class="showInline">{translate key="submission.notes.expandNotes"}</div><div id="collapseNotes" class="hideInline">{translate key="submission.notes.collapseNotes"}</div></a> | <a href="{$pageUrl}/sectionEditor/submissionNotes/{$submission->getArticleId()}/add" class="{if $noteViewType == "add"}active{/if}">{translate key="submission.notes.addNewNote"}</a> | <a href="#" onclick="confirmAction('{$pageUrl}/sectionEditor/clearAllSubmissionNotes?articleId={$submission->getArticleId()}', '{translate|escape:"javascript" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>
	</td>
</tr>
</table>
</div>

{if $noteViewType == "edit"}
	<form name="editNote" method="post" action="{$pageUrl}/sectionEditor/updateSubmissionNote" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$articleNote->getArticleId()}" />
	<input type="hidden" name="noteId" value="{$articleNote->getNoteId()}" />
	<input type="hidden" name="fileId" value="{$articleNote->getFileId()}" />
	<div class="formSection">
	<table width="100%" class="form">
	<tr class="heading"><td colspan="2">{translate key="submission.notes.editNote"}</td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="formLabel">Title:</td>
		<td class="formField"><input type="text" name="title" value="{$articleNote->getTitle()}" size="50" maxlength="120" class="textField" /></td>
	</tr>
	<tr>
		<td class="formLabel">Note:</td>
		<td class="formField"><textarea name="note" rows="10" cols="50" class="textArea">{$articleNote->getNote()}</textarea></td>
	</tr>
	<tr>
		<td class="formLabel">File:</td>
		<td class="formField"><input type="file" name="upload" class="textField" /></td>
	</tr>
	<tr>
		<td class="formLabel">Uploaded File:</td>
		<td class="formField"><a href="{$pageUrl}/sectionEditor/downloadFile/{$submission->getArticleId()}/{$articleNote->getFileId()}">{$noteFileName}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formField"><input type="submit" value="Update Note" /></td>
	</tr>
	</table>
	</div>
	</form>
{elseif $noteViewType == "add"}
	<form name="addNote" method="post" action="{$pageUrl}/sectionEditor/addSubmissionNote" enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
	<div class="formSection">
	<table width="100%" class="form">
	<tr class="heading"><td colspan="2">{translate key="submission.notes.addNewNote"}</td></tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td class="formLabel">Title:</td>
		<td class="formField"><input type="text" name="title" size="50" maxlength="120" class="textField" /></td>
	</tr>
	<tr>
		<td class="formLabel">Note:</td>
		<td class="formField"><textarea name="note" rows="10" cols="50" class="textArea">&nbsp;</textarea></td>
	</tr>
	<tr>
		<td class="formLabel">File:</td>
		<td class="formField"><input type="file" name="upload" class="textField" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formField"><input type="submit" value="Create New Note" /></td>
	</tr>
	</table>
	</div>
	</form>
{else}

{/if}

{include file="common/footer.tpl"}
