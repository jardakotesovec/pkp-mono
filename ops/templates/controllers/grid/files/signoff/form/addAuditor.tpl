{**
 * templates/controllers/grid/files/signoff/form/addAuditor.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows editor to add a user who should give feedback about copyedited files.
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#addAuditorForm').pkpHandler(
			'$.pkp.controllers.grid.files.signoff.form.AddAuditorFormHandler'
		);
	{rdelim});
</script>

<div id="addUserContainer">
	<form class="pkp_form" id="addAuditorForm" action="{url op="saveAddAuditor"}" method="post">

		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="addAuditorNotification"}

		<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
		{if $galleyId}
			<input type="hidden" name="representationId" value="{$galleyId|escape}" />
		{/if}

		<!-- User autocomplete -->
		<div id="userAutocomplete">
			{fbvFormSection}
				{fbvElement type="autocomplete" autocompleteUrl=$autocompleteUrl id="userId-GroupId" name="copyeditUserAutocomplete" label="editor.submission.addAuditor" value=$userNameString disableSync=true}
			{/fbvFormSection}
		</div>

		<!-- Available files listbuilder -->
		{url|assign:filesListbuilderUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.files.GalleyFilesListbuilderHandler" op="fetch" submissionId=$submissionId representationId=$galleyId escape=false}
		{assign var="filesListbuilderId" value="galleyFilesListbuilder"}

		{load_url_in_div id=$filesListbuilderId url=$filesListbuilderUrl}

		{fbvFormSection}
			{fbvElement type="text" id="responseDueDate" name="responseDueDate" label="submission.task.responseDueDate" value=$responseDueDate size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}

		<!-- Message to user -->
		{fbvFormSection}
			{fbvElement type="textarea" name="personalMessage" id="personalMessage" label="editor.submission.copyediting.personalMessageToUser" value=$personalMessage height=$fbvStyles.height.TALL rich=true}
		{/fbvFormSection}

		<!-- skip email checkbox -->
		{fbvFormSection for="skipEmail" size=$fbvStyles.size.MEDIUM list=true}
			{fbvElement type="checkbox" id="skipEmail" name="skipEmail" label="editor.submission.fileAuditor.skipEmail"}
		{/fbvFormSection}
		{fbvFormButtons}
	</form>
</div>

