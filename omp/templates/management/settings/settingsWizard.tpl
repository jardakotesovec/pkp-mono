{**
 * templates/management/settings/settingsWizard.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The settings wizard page.
 *}

{strip}
{assign var="pageTitle" value="manager.settings.wizard"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#settingsWizard').pkpHandler(
				'$.pkp.controllers.wizard.WizardHandler',
			{ldelim}
				cancelButtonText: '{translate|escape:javascript key="common.cancel"}',
				continueButtonText: '{translate|escape:javascript key="common.continue"}',
				finishButtonText: '{translate|escape:javascript key="common.finish"}'
			{rdelim});
	{rdelim});
</script>
<div id=settingsWizard>
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PressSettingsTabHandler" op="showTab" tab="masthead" wizardMode=true}">{translate key="manager.setup.masthead"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PressSettingsTabHandler" op="showTab" tab="contact" wizardMode=true}">{translate key="about.contact"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PressSettingsTabHandler" op="showTab" tab="policies" wizardMode=true}">{translate key="about.policies"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.WebsiteSettingsTabHandler" op="showTab" tab="appearance" wizardMode=true}">{translate key="manager.website.appearance"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="general" wizardMode=true}">{translate key="manager.publication.general"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="submissionStage" wizardMode=true}">{translate key="manager.publication.submissionStage"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="reviewStage" wizardMode=true}">{translate key="manager.publication.reviewStage"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="editorialStage" wizardMode=true}">{translate key="manager.publication.editorialStage"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="showTab" tab="productionStage" wizardMode=true}">{translate key="manager.publication.productionStage"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.DistributionSettingsTabHandler" op="showTab" tab="indexing" wizardMode=true}">{translate key="manager.distribution.indexing"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" op="showTab" tab="users" wizardMode=true}">{translate key="manager.users"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" op="showTab" tab="roles" wizardMode=true}">{translate key="manager.roles"}
	</ul>
</div>

{include file="common/footer.tpl"}
