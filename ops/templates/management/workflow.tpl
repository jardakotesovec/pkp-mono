{**
 * templates/management/workflow.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief The workflow settings page.
 *}
{include file="common/header.tpl" pageTitle="manager.workflow.title"}

{assign var="uuid" value=""|uniqid|escape}
<div id="settings-context-{$uuid}">
	<tabs>
		<tab id="submission" name="{translate key="manager.publication.submissionStage"}">
			{help file="settings" section="workflow-submission" class="pkp_help_tab"}
			<tabs :options="{ useUrlFragment: false}" class="tabs-component--side">			
				<tab name="{translate key="submission.informationCenter.metadata"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_METADATA_SETTINGS}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="grid.genres.title.short"}">
					{capture assign=genresUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.genre.GenreGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="genresGridContainer" url=$genresUrl}
				</tab>
				<tab name="{translate key="manager.setup.checklist"}">
					{capture assign=submissionChecklistGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.submissionChecklist.SubmissionChecklistGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="submissionChecklistGridContainer" url=$submissionChecklistGridUrl}
				</tab>
				<tab name="{translate key="manager.setup.authorGuidelines"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_AUTHOR_GUIDELINES}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="manager.setup.screening"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_SCREENING}"
						@set="set"
					/>
				</tab>				
				{call_hook name="Template::Settings::workflow::submission"}
			</tabs>
		</tab>
		<tab id="emails" name="{translate key="manager.publication.emails"}">
			{help file="settings" section="workflow-emails" class="pkp_help_tab"}
			<tabs :options="{ useUrlFragment: false}">
				<tab name="{translate key="navigation.setup"}">
					<pkp-form
						v-bind="components.{$smarty.const.FORM_EMAIL_SETUP}"
						@set="set"
					/>
				</tab>
				<tab name="{translate key="manager.emails.emailTemplates"}">
					<email-templates-list-panel
						v-bind="components.emailTemplates"
						@set="set"
					/>
					{capture assign=preparedEmailsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.preparedEmails.preparedEmailsGridHandler" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="preparedEmailsGridDiv" url=$preparedEmailsGridUrl}
				</tab>
				{call_hook name="Template::Settings::workflow::emails"}
			</tabs>
		</tab>
		{call_hook name="Template::Settings::workflow"}
	</tabs>
</div>
<script type="text/javascript">
	pkp.registry.init('settings-context-{$uuid}', 'SettingsContainer', {$settingsData|json_encode});
</script>

{include file="common/footer.tpl"}
