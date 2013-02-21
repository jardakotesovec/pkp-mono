{**
 * templates/controllers/grid/files/fileSignoff/auditorReminderForm.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the form to send a auditing reminder-- Contains a user-editable message field (all other fields are static)
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#sendReminderForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="sendReminderForm" method="post" action="{url op="sendReminder"}" >
	{fbvFormArea id="sendReminder"}
		<input type="hidden" name="monographId" value="{$monographId|escape}" />
		<input type="hidden" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" name="signoffId" value="{$signoffId}" />
		{* This form is used in production stage, where we need a publication format id *}
		{if $publicationFormatId}
			<input type="hidden" name="publicationFormatId" value="{$publicationFormatId}" />
		{/if}

		{fbvFormSection title="common.user"}
			{fbvElement type="text" id="auditorName" value=$auditorName disabled="true"}
		{/fbvFormSection}

		{fbvFormSection title="editor.monograph.personalMessageToUser" for="message"}
			{fbvElement type="textarea" id="message" value=$message}
		{/fbvFormSection}
		{fbvFormSection title="editor.monograph.taskSchedule"}
			{fbvElement type="text" id="dateNotified" label="reviewer.monograph.reviewRequestDate" value=$signoff->getDateNotified()|date_format:$dateFormatShort disabled=true inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" id="dateDue" label="editor.monograph.taskDueDate" value=$signoff->getDateUnderway()|date_format:$dateFormatShort disabled=true inline=true size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
		{fbvFormButtons submitText="editor.review.sendReminder"}
	{/fbvFormArea}
</form>
