{**
 * templates/controllers/tab/publicationEntry/form/citationsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#citationsForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
<form class="pkp_form" id="citationsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="updateCitations"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="publicationIdentifiersFormFieldsNotification"}
	<input type="hidden" name="submissionId" value="{$submission->getId()|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="tabPos" value="{$tabPos|escape}" />
	<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
	<input type="hidden" name="tab" value="citations" />
	{csrf}

	{fbvFormArea id="citationsFiled"}
		{fbvFormSection label="submission.citations" description="submission.citations.description"}
			{fbvElement type="textarea" id="citations" value=$citations disabled=$readOnly required=$citationsRequired}
		{/fbvFormSection}
	{/fbvFormArea}

	{if $parsedCitations->getCount()}
		{fbvFormArea id="parsedCitations" title="submission.parsedCitations"}
			{fbvFormSection description="submission.parsedCitations.description"}
				{iterate from=parsedCitations item=parsedCitation}
					<p>{$parsedCitation->getRawCitation()|escape}</p>
				{/iterate}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

	{fbvFormButtons submitText="submission.parsedAndSaveCitations" cancelText="common.cancel"}
</form>
