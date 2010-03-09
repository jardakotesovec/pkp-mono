{**
 * step1.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author monograph submission.
 *
 * $Id$
 *}
{assign var="pageTitle" value="author.submit.step1"}
{include file="author/submit/submitStepHeader.tpl"}

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStepAlias}">
{if $monographId}<input type="hidden" name="monographId" value="{$monographId|escape}" />{/if}

{include file="common/formErrors.tpl"}


<!-- Submission Type -->
<h3>{translate key="author.submission.workType"}</h3>

{fbvFormArea id="submissionType"}
	{fbvFormSection layout=$fbvStyles.layout.ONE_COLUMN}
	<p>{translate key="author.submission.workType.description"}</p>
	{fbvElement type="radio" name="isEditedVolume" id="isEditedVolume-0" value="0" checked=`$isEditedVolume` label="author.submission.workType.editedVolume"}
	{fbvElement type="radio" name="isEditedVolume" id="isEditedVolume-1" value="0" checked=`$isEditedVolume` label="author.submission.workType.authoredWork"}
	{/fbvFormSection}
{/fbvFormArea}
<div class="separator"></div>


<!-- Submission Placement -->
<h3>{translate key="author.submit.placement"}</h3>

{fbvFormArea id="placement"}
	{fbvFormSection layout=$fbvStyles.layout.ONE_COLUMN}
		{fbvElement type="select" id="seriesId" from=$seriesOptions selected=$seriesId translate=false}
	{/fbvFormSection}
{/fbvFormArea}


<!-- Submission checklist -->
{if $currentPress->getLocalizedSetting('submissionChecklist')}
<script type="text/javascript">
	{literal}
        $(function(){
        $("#messageBox").hide();
		$("form[name=submit]").validate({
			showErrors: function(errorMap, errorList) {
				$("#messageBox").html("<ul><li class='error'>{/literal}{translate key='author.submit.checklistErrors.begin'}{literal} "
											+ this.numberOfInvalids() 
		     								+ " {/literal}{translate key='author.submit.checklistErrors.end'}{literal}</li></ul>");
				if (this.numberOfInvalids() == 0) {
					$("#messageBox").hide('slow');
				}
			}
						
		});
	});
	{/literal}
</script>
<h3>{translate key="author.submit.submissionChecklist"}</h3>

	<div id="messageBox"></div>
	
	{fbvFormArea id="checklist"}
	{fbvFormSection layout=$fbvStyles.layout.ONE_COLUMN}
	<p>{translate key="author.submit.submissionChecklistDescription"}</p>
	{foreach name=checklist from=$currentPress->getLocalizedSetting('submissionChecklist') key=checklistId item=checklistItem}
		{if $checklistItem.content}
			{fbvElement type="checkbox" id="checklist-`$smarty.foreach.checklist.iteration`" required=true value="`$checklistId|escape`" label=`$checklistItem.content` translate=false}
		{/if}
	{/foreach}
	{/fbvFormSection}
	{/fbvFormArea}
	<div class="separator"></div>
{/if}


<!-- Cover Note To Editor-->
<h3>{translate key="author.submit.coverNote"}</h3>

{fbvFormArea id="commentsToEditor"}
	{fbvFormSection for="commentsToEditor"}
	{fbvElement type="textarea" name="commentsToEditor" id="commentsToEditor" size=$fbvStyles.size.MEDIUM measure=$fbvStyles.measure.3OF4}
	{/fbvFormSection}
{/fbvFormArea}


<!-- Privacy Statement -->
<h3>{translate key="author.submit.privacyStatement"}</h3>

{fbvFormArea id="privacyStatement"}
	{fbvFormSection for="privacyStatement"}
	{fbvElement type="textarea" name="privacyStatement" id="privacyStatement" disabled=true value=$currentPress->getLocalizedSetting('privacyStatement') size=$fbvStyles.size.MEDIUM measure=$fbvStyles.measure.3OF4}
	{/fbvFormSection}
{/fbvFormArea}

<div class="separator"></div>


<!-- Continue -->

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $monographId}confirmAction('{url page="author"}', '{translate|escape:"jsparam" key="author.submit.cancelSubmission"}'){else}document.location.href='{url page="author" escape=false}'{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>
</div>
{include file="common/footer.tpl"}
