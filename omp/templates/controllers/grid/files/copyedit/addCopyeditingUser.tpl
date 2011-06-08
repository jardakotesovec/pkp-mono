
{**
 * templates/controllers/grid/files/copyedit/addCopyeditingUser.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows editor to add a user who should give feedback about copyedited files.
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#addCopyeditingUser').pkpHandler(
			'$.pkp.controllers.grid.files.copyedit.form.AddCopyeditingUserFormHandler'
		);
	{rdelim});
</script>

<div id="addUserContainer">
	<form id="addCopyeditingUser" action="{url op="saveAddUser" monographId=$monographId|escape}" method="post">
		<input type="hidden" name="monographId" value="{$monographId|escape}" />

		<!-- User autocomplete -->
		<div id="userAutocomplete">
			{fbvFormSection}
				{url|assign:"autocompleteUrl" op="getCopyeditUserAutocomplete" monographId=$monographId escape=false}
				{fbvElement type="autocomplete" autocompleteUrl=$autocompleteUrl id="sourceTitle-" name="copyeditUserAutocomplete" label="user.role.copyeditor" required=true class="required" value=$userNameString|escape}
				<input type="hidden" id="sourceId-" name="userId" class="required" />
			{/fbvFormSection}
		</div>

		<!-- Available copyediting files listbuilder -->
		{url|assign:copyeditingFilesListbuilderUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.files.CopyeditingFilesListbuilderHandler" op="fetch" monographId=$monographId}
		{load_url_in_div id="copyeditingFilesListbuilder" url=$copyeditingFilesListbuilderUrl}

		{fbvFormSection}
			{fbvElement type="text" id="responseDueDate" name="responseDueDate" label="editor.responseDueDate" value=$responseDueDate }
		{/fbvFormSection}

		<!-- Message to user -->
		{fbvFormSection}
			{fbvElement type="textarea" name="personalMessage" id="personalMessage" required=true class="required" label="editor.monograph.copyediting.personalMessageTouser" value=$personalMessage measure=$fbvStyles.measure.1OF1 size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{include file="form/formButtons.tpl"}
	</form>
</div>

