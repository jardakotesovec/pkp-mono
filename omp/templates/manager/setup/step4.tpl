{**
 * step4.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of press setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.managingThePress"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="4"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
{fbvFormArea id="locales"}
{fbvFormSection title="form.formLanguage" for="languageSelector"}
	{fbvCustomElement}
		{url|assign:"setupFormUrl" op="setup" path="1"}
		{form_language_chooser form="setupForm" url=$setupFormUrl}
		<span class="instruct">{translate key="form.formLanguage.description"}</span>
	{/fbvCustomElement}
{/fbvFormSection}
{/fbvFormArea}
{/if} {* count($formLocales) > 1*}

<h3>4.1 {translate key="manager.setup.securitySettings"}</h3>

{fbvFormArea id="openAccessPolicyContainer"}
{fbvFormSection title="manager.setup.openAccessPolicy"}
	<p>{translate key="manager.setup.openAccessPolicyDescription"}</p>
	{fbvElement type="textarea" name="openAccessPolicy[$formLocale]" id="openAccessPolicy" value=$openAccessPolicy[$formLocale] size=$fbvStyles.size.MEDIUM measure=$fbvStyles.measure.3OF4}
{/fbvFormSection}
{/fbvFormArea}

<p>{translate key="manager.setup.securitySettingsDescription"}</p>

<script type="text/javascript">
{literal}
	$(function(){
		$('#disableUserReg-0').live("click", (function() { // Initialize grid settings button handler
			$('#allowRegReader').removeAttr('disabled');
			$('#allowRegAuthor').removeAttr('disabled');
			$('#allowRegReviewer').removeAttr('disabled');
		}));

		$('#disableUserReg-1').live("click", (function() { // Initialize grid settings button handler
			$('#allowRegReader').attr('disabled', true);
			$('#allowRegAuthor').attr('disabled', true);
			$('#allowRegReviewer').attr('disabled', true);
		}));
	});
{/literal}
</script>

{fbvFormArea id="siteAccess"}
{fbvFormSection title="manager.setup.siteAccess" layout=$fbvStyles.layout.ONE_COLUMN}
	{fbvElement type="checkbox" id="restrictSiteAccess" value="1" checked=$restrictSiteAccess label="manager.setup.restrictSiteAccess"}
	{fbvElement type="checkbox" id="restrictMonographAccess" value="1" checked=$restrictMonographAccess label="manager.setup.restrictMonographAccess"}
{/fbvFormSection}
{fbvFormSection title="manager.setup.userRegistration" layout=$fbvStyles.layout.ONE_COLUMN}
	{fbvElement type="radio" id="disableUserReg-0" name="disableUserReg" value="0" onclick="setRegAllowOpts()" checked=!$disableUserReg label="manager.setup.enableUserRegistration"}
	<div id="disableUserRegCheckboxes" style="padding-left: 20px;">
		{fbvElement type="checkbox" id="allowRegReader" value="1" checked=$restrictMonographAccess disabled=$disableUserReg label="manager.setup.enableUserRegistration.reader"}
		{fbvElement type="checkbox" id="allowRegAuthor" value="1" checked=$restrictMonographAccess disabled=$disableUserReg label="manager.setup.enableUserRegistration.author"}
		{fbvElement type="checkbox" id="allowRegReviewer" value="1" checked=$restrictMonographAccess disabled=$disableUserReg label="manager.setup.enableUserRegistration.reviewer"}
	</div>
	{fbvElement type="radio" id="disableUserReg-1" name="disableUserReg" value="1" onclick="setRegAllowOpts()" checked=$disableUserReg label="manager.setup.disableUserRegistration"}
{/fbvFormSection}
{/fbvFormArea}

<div class="separator"></div>

<h3>4.2 {translate key="manager.setup.announcements"}</h3>

<p>{translate key="manager.setup.announcementsDescription"}</p>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleEnableAnnouncementsHomepage(form) {
				form.numAnnouncementsHomepage.disabled = !form.numAnnouncementsHomepage.disabled;
			}
		// -->
		{/literal}
	</script>

<p>
	<input type="checkbox" name="enableAnnouncements" id="enableAnnouncements" value="1" {if $enableAnnouncements} checked="checked"{/if} />&nbsp;
	<label for="enableAnnouncements">{translate key="manager.setup.enableAnnouncements"}</label>
</p>

<p>
	<input type="checkbox" name="enableAnnouncementsHomepage" id="enableAnnouncementsHomepage" value="1" onclick="toggleEnableAnnouncementsHomepage(this.form)"{if $enableAnnouncementsHomepage} checked="checked"{/if} />&nbsp;
	<label for="enableAnnouncementsHomepage">{translate key="manager.setup.enableAnnouncementsHomepage1"}</label>
	<select name="numAnnouncementsHomepage" size="1" class="selectMenu" {if not $enableAnnouncementsHomepage}disabled="disabled"{/if}>
		{section name="numAnnouncementsHomepageOptions" start=1 loop=11}
		<option value="{$smarty.section.numAnnouncementsHomepageOptions.index}"{if $numAnnouncementsHomepage eq $smarty.section.numAnnouncementsHomepageOptions.index or ($smarty.section.numAnnouncementsHomepageOptions.index eq 1 and not $numAnnouncementsHomepage)} selected="selected"{/if}>{$smarty.section.numAnnouncementsHomepageOptions.index}</option>
		{/section}
	</select>
	{translate key="manager.setup.enableAnnouncementsHomepage2"}
</p>

{fbvFormArea id="announcementsIntroductionContainer"}
{fbvFormSection title="manager.setup.announcementsIntroduction"}
	<p>{translate key="manager.setup.announcementsIntroductionDescription"}</p>
	{fbvElement type="textarea" name="announcementsIntroduction[$formLocale]" id="announcementsIntroduction" value=$announcementsIntroduction[$formLocale] size=$fbvStyles.size.MEDIUM measure=$fbvStyles.measure.3OF4}
{/fbvFormSection}
{/fbvFormArea}

<div class="separator"></div>

<h3>4.3 {translate key="manager.setup.publicIdentifier"}</h3>

{fbvFormArea id="publicIdentifier"}
{fbvFormSection title="manager.setup.uniqueIdentifier" layout=$fbvStyles.layout.ONE_COLUMN}
	<p>{translate key="manager.setup.uniqueIdentifierDescription"}</p>
	<br />
	{fbvElement type="checkbox" id="enablePublicMonographId" value="1" checked=$enablePublicMonographId label="manager.setup.enablePublicMonographId"}
	{fbvElement type="checkbox" id="enablePublicGalleyId" value="1" checked=$enablePublicGalleyId label="manager.setup.enablePublicGalleyId"}
{/fbvFormSection}
{fbvFormSection title="manager.setup.pageNumberIdentifier" layout=$fbvStyles.layout.ONE_COLUMN}
	{fbvElement type="checkbox" id="enablePageNumber" value="1" checked=$enablePageNumber label="manager.setup.enablePageNumber"}
{/fbvFormSection}
{/fbvFormArea}

<div class="separator"></div>

<h3>4.4 {translate key="manager.setup.cataloguingMetadata"}</h3>

{url|assign:cataloguingMetadataUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.settings.CataloguingMetadataListbuilderHandler" op="fetch"}
{load_url_in_div id="cataloguingMetadataContainer" url=$cataloguingMetadataUrl}

<div class="separator"></div>

<h3>4.5 {translate key="manager.setup.searchEngineIndexing"}</h3>

<p>{translate key="manager.setup.searchEngineIndexingDescription"}</p>

{fbvFormArea id="searchEngineIndexing"}
{fbvFormSection title="common.description" float=$fbvStyles.float.LEFT}
	{fbvElement type="text" id="searchDescription" name="searchDescription[$formLocale]" value=$searchDescription[$formLocale] size=$fbvStyles.size.LARGE}
{/fbvFormSection}
{fbvFormSection title="common.keywords" float=$fbvStyles.float.RIGHT}
	{fbvElement type="text" id="searchKeywords" name="searchKeywords[$formLocale]" value=$searchKeywords[$formLocale] size=$fbvStyles.size.LARGE}
{/fbvFormSection}
{fbvFormSection title="manager.setup.customTags"}
	{fbvElement type="textarea" id="customHeaders" name="customHeaders[$formLocale]" value=$customHeaders[$formLocale] measure=$fbvStyles.measure.1OF2}
{/fbvFormSection}
{/fbvFormArea}

<div class="separator"></div>

<h3>4.6 {translate key="manager.setup.registerPressForIndexing"}</h3>

{url|assign:"oaiSiteUrl" press=$currentPress->getPath()}
{url|assign:"oaiUrl" page="oai"}
<p>{translate key="manager.setup.registerPressForIndexingDescription" siteUrl=$oaiSiteUrl oaiUrl=$oaiUrl}</p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>
</div>

{include file="common/footer.tpl"}
