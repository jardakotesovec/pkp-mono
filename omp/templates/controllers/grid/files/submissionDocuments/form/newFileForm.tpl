{**
 * templates/controllers/grid/files/submissionDocuments/form/newFileForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Library Files form
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#uploadForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: '{url|escape:javascript op="uploadFile" fileType=$fileType monographId=$monographId escape=false}',
					baseUrl: '{$baseUrl|escape:javascript}'
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="uploadForm" action="{url op="saveFile"}" method="post">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="libraryFileUploadNotification"}
	<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
	<input type="hidden" name="monographId" value="{$monographId|escape}" />

	{fbvFormArea id="name"}
		{fbvFormSection title="common.name" required=true}
			{fbvElement type="text" multilingual="true" id="libraryFileName" value=$libraryFileName maxlength="120"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="type"}
		{fbvFormSection title="common.type" required=true}
			{translate|assign:"defaultLabel" key="common.chooseOne"}
			{fbvElement type="select" from=$fileTypes id="fileType" selected=$fileType defaultValue="" defaultLabel=$defaultLabel}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="file"}
		{fbvFormSection title="common.file" required=true}
			<div id="plupload"></div>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>

