{**
 * templates/about/editorialTeam.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Press index.
 *}
{strip}
{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}
{/strip}

{url|assign:editUrl page="management" op="settings" path="press" anchor="masthead"}
{include file="common/linkToEditPage.tpl" editUrl=$editUrl}

{if $currentPress->getLocalizedSetting('masthead') != ''}
	{$currentPress->getLocalizedSetting('masthead')}
{/if}

{include file="common/footer.tpl"}
