{**
 * templates/notification/unsubscribeNotificationsForm.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Unsubscribe Notifications Form
 *
 *}
{include file="frontend/components/header.tpl" pageTitle="notification.unsubscribeNotifications"}

<div class="page page_unsubscribe_notifications">
	<h1>{translate key="notification.unsubscribeNotifications"}</h1>

	<p>{translate key="notification.unsubscribeNotifications.pageMessage" contextName=$contextName email=$userEmail}

	<form class="cmp_form" id="unsubscribeNotificationForm" method="post" action="{url router=$smarty.const.ROUTE_PAGE page="notification" op="unsubscribe"}">
		{csrf}

		<input type="hidden" name="validate" value="{$validationToken|escape}" />
		<input type="hidden" name="id" value="{$notificationId|escape}" />

		<fieldset>
			<div class="fields">
				{foreach from=$emailSettings key=$emailKey item=$emailSetting}
					<div>
						<label for="{$emailSetting.emailSettingName|escape}">
							<input id="{$emailSetting.emailSettingName|escape}" name="{$emailSetting.emailSettingName|escape}" type="checkbox" value="1" checked="checked">
							{translate key=$emailSetting.settingKey title="common.title"|translate}
						</label>
					</div>
				{/foreach}
			</div>
		</fieldset>

		{capture assign="profileNotificationUrl"}{url page="user" op="profile"}{/capture}
		<p>{translate key="notification.unsubscribeNotifications.resubscribe" profileNotificationUrl=$profileNotificationUrl}</p>

		<div class="buttons">
			<button class="submit" type="submit">
				{translate key="notification.unsubscribeNotifications"}
			</button>
		</div>
	</form>

</div>

{include file="frontend/components/footer.tpl"}