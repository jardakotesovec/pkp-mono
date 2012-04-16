{**
 * controllers/tab/settings/contact/form/contactForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contact management form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#contactForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="contactForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PressSettingsTabHandler" op="saveFormData" tab="contact"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="contactFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	{fbvFormArea id="contactFormArea" title="manager.setup.principalContact" border=true}
		{fbvFormSection description="manager.setup.principalContactDescription"}
		{/fbvFormSection}
		{fbvFormSection required=true for="contactName" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="user.name" id="contactName" value=$contactName|escape maxlength="60" inline=true}
		{/fbvFormSection}
		{fbvFormSection required=true for="contactName" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="user.title" multilingual=true name="contactTitle" id="contactTitle" value=$contactTitle|escape maxlength="90" inline=true}
		{/fbvFormSection}
		{fbvFormSection for="contactEmail" required=true size=$fbvStyles.size.MEDIUM inline="true"}
			{fbvElement type="text"  label="user.email" id="contactEmail" value=$contactEmail|escape maxlength="90"}
		{/fbvFormSection}
		{fbvFormSection for="contactPhone" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="user.phone" id="contactPhone" value=$contactPhone|escape maxlength="24"}
		{/fbvFormSection}
		{fbvFormSection for="contactFax" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="user.fax" id="contactFax" value=$contactFax|escape maxlength="24"}
		{/fbvFormSection}
		{fbvFormSection title="user.affiliation" for="contactAffiliation"}
			{fbvElement type="textarea" multilingual=true name="contactAffiliation" id="contactAffiliation" value=$contactAffiliation rich=true}
		{/fbvFormSection}
		{fbvFormSection title="common.mailingAddress" for="contactMailingAddress"}
			{fbvElement type="textarea" multilingual=true name="contactMailingAddress" id="contactMailingAddress" value=$contactMailingAddress rich=true}
		{/fbvFormSection}
	{/fbvFormArea}

	<div {if $wizardMode}class="pkp_form_hidden"{/if}>
		{fbvFormArea id="contactFormArea" title="manager.setup.technicalSupportContact" border=true}
			{fbvFormSection description="manager.setup.technicalSupportContactDescription"}
			{/fbvFormSection}
			{fbvFormSection title="user.name" for="supportName" required=true inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="text" id="supportName" value=$supportName|escape maxlength="60"}
			{/fbvFormSection}
			{fbvFormSection title="user.email" for="supportEmail" required=true inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="text" id="supportEmail" value=$supportEmail|escape maxlength="90"}
			{/fbvFormSection}
			{fbvFormSection title="user.phone" for="supportPhone" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="text" id="supportPhone" value=$supportPhone|escape maxlength="24"}
			{/fbvFormSection}
		{/fbvFormArea}
	</div>

	{if !$wizardMode}
		{fbvFormButtons id="contactFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>