{**
 * controllers/notification/inPlaceNotificationContent.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a single notification for in place notifications data.
 *}

<div id="pkp_notification_{$notificationId|escape}"{if $notificationStyleClass} class="{$notificationStyleClass}"{/if}>
	{if $notificationTitle}
		<span class="title">
			{$notificationTitle}
		</span>
	{/if}
	{if $notificationContents}
		<span class="description">
			{$notificationContents}
		</span>
	{/if}
</div>
