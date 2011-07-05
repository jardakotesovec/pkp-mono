{**
 * templates/submission/form/step2.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of author monograph submission.
 *}
{assign var="pageTitle" value="submission.submit"}
{include file="submission/form/submitStepHeader.tpl"}



<form class="pkp_form" id="submitStepForm" method="post" action="{url op="saveStep" path=$submitStep}" enctype="multipart/form-data">
	<input type="hidden" name="monographId" value="{$monographId|escape}" />
	{include file="common/formErrors.tpl"}

	<!-- Submission upload grid -->

	{url|assign:submissionFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.submission.SubmissionWizardFilesGridHandler" op="fetchGrid" monographId=$monographId}
	{load_url_in_div id="submissionFilesGridDiv" url=$submissionFilesGridUrl}

	{if $pressSettings.supportPhone}
		{assign var="howToKeyName" value="submission.submit.howToSubmit"}
	{else}
		{assign var="howToKeyName" value="submission.submit.howToSubmitNoPhone"}
	{/if}

	<p>{translate key=$howToKeyName supportName=$pressSettings.supportName supportEmail=$pressSettings.supportEmail supportPhone=$pressSettings.supportPhone}</p>

	<div class="separator"></div>

	{fbvFormButtons id="step2Buttons" submitText="common.saveAndContinue"}
</form>

{include file="common/footer.tpl"}

