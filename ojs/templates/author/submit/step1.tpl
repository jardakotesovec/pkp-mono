{**
 * step1.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author article submission.
 *
 * $Id$
 *}

{assign var="pageId" value="author.submit.step1"}
{assign var="pageTitle" value="author.submit.step1"}
{include file="author/submit/submitHeader.tpl"}

<br />

<p>{translate key="author.submit.howToSubmit" supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}</p>

<div class="separator"></div>

<script type="text/javascript">
{literal}
function checkSubmissionChecklist() {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].type == 'checkbox' && elements[i].name.match('^checklist') && !elements[i].checked) {
			alert({/literal}'{translate|escape:"javascript" key="author.submit.verifyChecklist"}'{literal});
			return false;
		}
	}
	return true;
}
{/literal}
</script>

<form name="submit" method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}" onsubmit="return checkSubmissionChecklist()">
{if $articleId}
<input type="hidden" name="articleId" value="{$articleId}" />
{/if}
<input type="hidden" name="submissionChecklist" value="1" />
{include file="common/formErrors.tpl"}

<h3>{translate key="author.submit.submissionChecklist"}</h3>
<p>{translate key="author.submit.submissionChecklistDescription"}</p>
<table width="100%" class="data">
{foreach name=checklist from=$journalSettings.submissionChecklist key=checklistId item=checklistItem}
<tr valign="top">
	<td><input type="checkbox" name="checklist[]" value="{$checklistId}"{if $articleId || $submissionChecklist} checked="checked"{/if} /></td>
	<td>{$checklistItem.content}</td>
</tr>
{/foreach}
</table>

<div class="separator"></div>

<h3>{translate key="author.submit.journalSection"}</h3>

<p>{translate key="author.submit.journalSectionDescription"}</p>


<table class="data">
<tr valign="top">	
	<td class="label">{fieldLabel name="sectionId" required="true" key="section.section"}</td>
	<td class="value"><select name="sectionId" size="1">{html_options options=$sectionOptions selected=$sectionId}</select></td>
</tr>
	
</table>

<div class="separator"></div>

<h3>{translate key="author.submit.commentsForEditor"}</h3>
<table width="100%" class="data">

<tr valign="top">
	<td class="label">{formLabel name="commentsToEditor"}{translate key="author.submit.comments"}:{/formLabel}</td>
	<td class="value"><textarea name="commentsToEditor" rows="3" cols="60">{$commentsToEditor|escape}</textarea></td>
</tr>

</table>

<div class="separator"></div>

<table width="100%" class="data">
<tr valign="top">
	<td class="label"><span class="formRequired">{translate key="common.requiredField"}</span></td>
	<td class="value"><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $articleId}confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}'){else}document.location.href='{$pageUrl}/author'{/if}" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
