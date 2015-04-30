{**
 * templates/user/identityForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#identityForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="identityForm" method="post" action="{url op="saveIdentity"}" enctype="multipart/form-data">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="identityFormNotification"}

	{fbvFormArea id="userNameInfo"}
		{fbvFormSection title="user.username"}
			{$username|escape}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="userFormCompactLeft"}
		{fbvFormSection title="user.name"}
			{fbvElement type="text" label="user.firstName" required="true" id="firstName" value=$firstName maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="user.middleName" id="middleName" value=$middleName maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="user.lastName" required="true" id="lastName" value=$lastName maxlength="40" inline=true size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" label="user.salutation" name="salutation" id="salutation" value=$salutation maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="user.initials" name="initials" id="initials" value=$initials maxlength="5" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="user.suffix" id="suffix" value=$suffix inline=true size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="select" label="user.gender" name="gender" id="gender" from=$genderOptions translate="true" selected=$gender size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
