{**
 * templates/management/context.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The press settings page.
 *}
{include file="common/header.tpl" pageTitle="manager.setup"}

{if $newVersionAvailable}
	<div class="pkp_notification">
		{capture assign="notificationContents"}{translate key="site.upgradeAvailable.manager" currentVersion=$currentVersion latestVersion=$latestVersion siteAdminName=$siteAdmin->getFullName() siteAdminEmail=$siteAdmin->getEmail()}{/capture}
		{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId="upgradeWarning-"|uniqid notificationStyleClass="notifyWarning" notificationTitle="common.warning"|translate notificationContents=$notificationContents}
	</div>
{/if}

{assign var="uuid" value=""|uniqid|escape}
<div id="settings-context-{$uuid}">
	<tabs>
		<tab id="masthead" name="{translate key="manager.setup.masthead"}">
			{help file="settings" section="context" class="pkp_help_tab"}
			<pkp-form
				v-bind="components.{$smarty.const.FORM_MASTHEAD}"
				@set="set"
			/>
		</tab>
		<tab id="contact" name="{translate key="about.contact"}">
			{help file="settings" section="context" class="pkp_help_tab"}
			<pkp-form
				v-bind="components.{$smarty.const.FORM_CONTACT}"
				@set="set"
			/>
		</tab>
		<tab id="series" name="{translate key="series.series"}">
			{help file="settings" section="context" class="pkp_help_tab"}
			{capture assign=seriesGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.series.SeriesGridHandler" op="fetchGrid" escape=false}{/capture}
			{load_url_in_div id="seriesGridContainer" url=$seriesGridUrl}
		</tab>
		<tab id="categories" name="{translate key="grid.category.categories"}">
			{help file="settings" section="context" class="pkp_help_tab"}
			{capture assign=categoriesUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.category.CategoryCategoryGridHandler" op="fetchGrid" escape=false}{/capture}
			{load_url_in_div id="categoriesContainer" url=$categoriesUrl}
		</tab>
	</tabs>
</div>
<script type="text/javascript">
	pkp.registry.init('settings-context-{$uuid}', 'SettingsContainer', {$settingsData|json_encode});
</script>

{include file="common/footer.tpl"}
