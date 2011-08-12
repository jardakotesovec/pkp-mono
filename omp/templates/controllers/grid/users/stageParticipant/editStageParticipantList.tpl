{**
 * templates/controllers/grid/users/stageParticipant/editStageParticipantList.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form that holds the stage participants list
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#editStageParticipantsListForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<p>{translate key="submission.submit.addStageParticipant.description"}</p>
<form class="pkp_form" id="editStageParticipantsListForm" action="{url op="saveStageParticipantList"}" method="post">
	{fbvFormArea id="editStageParticipantList"}
		<input type="hidden" name="monographId" value="{$monographId|escape}" />
		<input type="hidden" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" name="userGroupId" value="{$userGroupId|escape}" />

		{fbvFormSection}
			{url|assign:submissionParticipantsUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.users.StageParticipantListbuilderHandler" op="fetch" monographId=$monographId stageId=$stageId userGroupId=$userGroupId escape=false}
			{load_url_in_div id="submissionParticipantsContainer" url=$submissionParticipantsUrl}
		{/fbvFormSection}
		{fbvFormButtons}
	{/fbvFormArea}
</form>
