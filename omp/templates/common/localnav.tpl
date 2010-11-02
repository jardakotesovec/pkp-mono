{**
 * navbar.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Navigation Bar
 *}

{capture assign="publicMenu"}
	{if $currentPress}
		{if $enableAnnouncements}
			<li><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}
		<li>
			<a href="{url page="about"}">{translate key="about.aboutThePress"}<span class="sf-sub-indicator"> »</span></a>
			<ul>
				{if not (empty($pressSettings.mailingAddress) && empty($pressSettings.contactName) && empty($pressSettings.contactAffiliation) && empty($pressSettings.contactMailingAddress) && empty($pressSettings.contactPhone) && empty($pressSettings.contactFax) && empty($pressSettings.contactEmail) && empty($pressSettings.supportName) && empty($pressSettings.supportPhone) && empty($pressSettings.supportEmail))}
					<li><a page="about" href="{url page="about" op="contact"}">{translate key="about.contact"}</a></li>
				{/if}
				<li><a href="{url page="about" op="editorialTeam"}">{translate key="about.editorialTeam"}</a></li>
				<li><a page="about" href="{url page="about" op="editorialPolicies"}">{translate key="about.policies"}</a></li>
				<li><a page="about" href="{url page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
			</ul>
		</li>
		<li>
			<a href="#"}">{translate key="navigation.catalog"}</a>
			<ul>
				<li><a href="#">{translate key="navigation.featuredBooks"}</a></li>
				<li><a href="#">{translate key="navigation.newReleases"}</a></li>
				<li><a href="#">{translate key="navigation.seriesAndEditions"}</a></li>
			</ul>
		</li>
		<li><a href="{url page="issue" op="archive"}">{translate key="navigation.backlist"}</a></li>
	{/if}
{/capture}

{if $isUserLoggedIn}
	<div class="navigation">
		<ul class="sf-menu">
			<li><a href="{url page="dashboard"}">{translate key="navigation.dashboard"}</a></li>
			<li><a href="{url page="dashboard" op="status"}">{translate key="navigation.submissions"}</a></li>
			{if $currentPress}
				<li>
					<a href="#"}">{translate key="navigation.catalog"}</a>
					<ul>
						<li><a href="{url page="issue" op="archive"}">{translate key="navigation.published"}</a></li>
						<li><a href="#">{translate key="navigation.seriesAndEditions"}</a></li>
						<li><a href="#">{translate key="navigation.featuredBooks"}</a></li>
						<li><a href="#">{translate key="navigation.admin"}</a></li>
					</ul>
				</li>
				<li>
					<a href="{url page="manager" op="settings"}">{translate key="navigation.settings"}</a>
					<ul>
						<li><a href="{url page="manager" op="setup"}">{translate key="navigation.setup"}</a></li>
						<li><a href="{url page="manager" op="data"}">{translate key="navigation.data"}</a></li>
						<li><a href="{url page="manager" op="system"}">{translate key="navigation.system"}</a></li>
						<li><a href="{url page="manager" op="users"}">{translate key="navigation.userManagement"}</a></li>
					</ul>
				</li>
				{if $enableAnnouncements}
					<li><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
				{/if}
				<li>
					<a href="#"}">{translate key="navigation.publicMenu"}</a>
					<ul>{$publicMenu}</ul>
				</li>
			{/if}
		</ul>
	</div>
{else}
	<div class="navigation">
		<ul class="sf-menu">{$publicMenu}</ul>
	</div>
{/if}
