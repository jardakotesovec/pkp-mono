{**
 * templates/announcements/announcements.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcements description and grid.
 *
 *}
{if $announcementsIntroduction}
	<div id="announcementsIntro">
		{$announcementsIntroduction|nl2br}
	</div>
{/if}

{url|assign:announcementGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.content.announcements.ViewAnnouncementGridHandler" op="fetchGrid" displayLimit=$displayLimit}
{load_url_in_div id="announcementGridContainer" url="$announcementGridUrl"}