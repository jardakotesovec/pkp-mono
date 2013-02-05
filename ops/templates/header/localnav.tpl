{**
 * templates/header/localnav.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal-Specific Navigation Bar
 *}

{capture assign="publicMenu"}
	{if $currentJournal}
		{if $enableAnnouncements}
			<li><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}
		<li><a href="#">{translate key="navigation.about"}</a>
			<ul>
				{if not (empty($contextSettings.mailingAddress) && empty($contextSettings.contactName) && empty($contextSettings.contactAffiliation) && empty($contextSettings.contactMailingAddress) && empty($contextSettings.contactPhone) && empty($contextSettings.contactFax) && empty($contextSettings.contactEmail) && empty($contextSettings.supportName) && empty($contextSettings.supportPhone) && empty($contextSettings.supportEmail))}
					<li><a href="{url page="about" op="contact"}">{translate key="about.contact"}</a></li>
				{/if}
				<li><a href="{url page="about" op="description"}">{translate key="about.description"}</a></li>
				<li><a href="{url page="about" op="editorialTeam"}">{translate key="about.editorialTeam"}</a></li>
				<li><a href="{url page="about" op="editorialPolicies"}">{translate key="about.policies"}</a></li>
				<li><a href="{url page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
				{if not ($currentJournal->getLocalizedSetting('contributorNote') == '' && empty($contextSettings.contributors) && $currentJournal->getLocalizedSetting('sponsorNote') == '' && empty($contextSettings.sponsors))}<li><a href="{url page="about" op="journalSponsorship"}">{translate key="about.journalSponsorship"}</a></li>{/if}
			</ul>
		</li>
	{/if}
{/capture}

<div class="pkp_structure_head_localNav">
	{if $isUserLoggedIn}
		<ul class="sf-menu">
			<li><a href="{url page="user"}">{translate key="navigation.userHome"}</a>
			{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SERIES_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR), $userRoles)}
				{* FIXME: Dashboard not yet ported
				<li><a href="{url page="dashboard"}">{translate key="navigation.dashboard"}</a></li>
				*}
			{/if}
			{if $currentJournal}
				<li><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a>
				{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SERIES_EDITOR), $userRoles)}
					<li>
						<a href="#">{translate key="navigation.management"}</a>
						<ul>
							{if array_intersect(array(ROLE_ID_MANAGER), $userRoles)}
							<li>
								<a href="{url page="management" op="settings" path="index"}">{translate key="navigation.settings"}</a>
								<ul>
									<li><a href="{url page="management" op="settings" path="journal"}">{translate key="journal.journal"}</a></li>
									<li><a href="{url page="management" op="settings" path="website"}">{translate key="navigation.website"}</a></li>
									<li><a href="{url page="management" op="settings" path="publication"}">{translate key="navigation.publicationProcess"}</a></li>
									<li><a href="{url page="management" op="settings" path="distribution"}">{translate key="navigation.distributionProcess"}</a></li>
									<li><a href="{url page="management" op="settings" path="access"}">{translate key="navigation.access"}</a></li>
								</ul>
							</li>
							<li>
								<a href="{url page="management" op="tools" path="index"}">{translate key="navigation.tools"}</a>
								<ul>
									<li><a href="{url page="manager" op="importexport"}">{translate key="navigation.tools.importExport"}</a></li>
								</ul>
							</li>
							{/if}
						</ul>
					</li>
				{/if}{* ROLE_ID_MANAGER || ROLE_ID_SERIES_EDITOR *}
				{$publicMenu}
			{/if}
		</ul>
	{else}{* !$isUserLoggedIn *}
		<ul class="sf-menu">
			<li><a href="{url page="index"}">{translate key="navigation.home"}</a></li>
			<li><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a>
			{$publicMenu}
		</ul>
	{/if}{* $isUserLoggedIn *}
</div>
