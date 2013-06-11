{**
 * templates/admin/journalSettings.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic journal settings under site administration.
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#journalSettingsForm').pkpHandler('$.pkp.controllers.tab.settings.form.FileViewFormHandler',
			{ldelim}
				fetchFileUrl: '{url|escape:javascript op='fetchFile' escape=false}'
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="journalSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.admin.journal.JournalGridHandler" op="updateContext"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="journalSettingsNotification"}

	{if $contextId}
		{fbvElement id="contextId" type="hidden" name="contextId" value=$contextId}
	{else}
		<p>{translate key="admin.journals.createInstructions"}</p>
	{/if}

	{fbvFormArea id="journalSettings"}
		{fbvFormSection title="manager.setup.journalTitle" required=true for="name"}
			{fbvElement type="text" id="name" value=$name multilingual=true}
		{/fbvFormSection}
		{fbvFormSection title="admin.journals.journalDescription" for="description"}
			{fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
		{/fbvFormSection}
		{fbvFormSection title="journal.path" required=true for="path"}
			{fbvElement type="text" id="path" value=$path size=$smarty.const.SMALL maxlength="32"}
			{url|assign:"sampleUrl" router=$smarty.const.ROUTE_PAGE journal="path"}
			{** FIXME: is this class instruct still the right one? **}
			<span class="instruct">{translate key="admin.journals.urlWillBe" sampleUrl=$sampleUrl}</span>
		{/fbvFormSection}
		{fbvFormSection label="admin.journals.thumbnail"}
			<div id="{$uploadThumbnailLinkAction->getId()}" class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$uploadThumbnailLinkAction contextId="journalSettingsForm"}
			</div>
			<div id="homepageImage">
				{$thumbnailImage}
			</div>
		{/fbvFormSection}
		{fbvFormSection for="enabled" list=true}
			{if $enabled}{assign var="enabled" value="checked"}{/if}
			{fbvElement type="checkbox" id="enabled" checked=$enabled value="1" label="admin.journals.enableJournalInstructions"}
		{/fbvFormSection}

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="journalSettingsFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
