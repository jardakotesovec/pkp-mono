{**
 * templates/submission/form/step3.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of author monograph submission.
 *}

{assign var="pageTitle" value="submission.submit"}
{include file="submission/form/submitStepHeader.tpl"}


<script type="text/javascript">
	// Attach the JS form handler.
	$(function() {ldelim}
		$('#submitStep3Form').pkpHandler(
				'$.pkp.pages.submission.SubmissionStep3FormHandler',
				{ldelim}
					isEditedVolume: {if $isEditedVolume}true{else}false{/if},
					chaptersGridContainer: 'chaptersGridContainer',
					authorsGridContainer: 'authorsGridContainer'
				{rdelim});
	{rdelim});
</script>


<form class="pkp_form" id="submitStep3Form" method="post" action="{url op="saveStep" path=$submitStep}">
	<input type="hidden" name="monographId" value="{$monographId|escape}" />
	{include file="common/formErrors.tpl"}

	{include file="submission/submissionMetadataFormFields.tpl"}

	{fbvFormArea id="contributors"}
		{fbvFormSection}
			<!--  Contributors -->
			{url|assign:authorGridUrl router=$smarty.const.ROUTE_COMPONENT  component="grid.users.author.AuthorGridHandler" op="fetchGrid" monographId=$monographId}
			{load_url_in_div id="authorsGridContainer" url="$authorGridUrl"}

			<!--  Chapters -->
			{if $isEditedVolume}
				{url|assign:chaptersGridUrl router=$smarty.const.ROUTE_COMPONENT  component="grid.users.chapter.ChapterGridHandler" op="fetchGrid" monographId=$monographId}
				{load_url_in_div id="chaptersGridContainer" url="$chaptersGridUrl"}
			{/if}
		{/fbvFormSection}

		{fbvFormButtons id="step2Buttons" submitText="submission.submit.finishSubmission" confirmSubmit="submission.confirmSubmit"}
	{/fbvFormArea}
</form>
{include file="common/footer.tpl"}

