{**
 * templates/index/press.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Press index page.
 *}
{strip}
{assign var="pageTitleTranslated" value=$siteTitle}
{include file="common/header.tpl"}
{/strip}

<div>{$pressDescription}</div>

{call_hook name="Templates::Index::press"}

{if $homepageImage}
	<br />
	<div id="homepageImage">
		<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}" width="{$homepageImage.width|escape}" height="{$homepageImage.height|escape}" {if $homepageImage.altText != ''}alt="{$homepageImage.altText|escape}"{else}alt="{translate key="common.pressHomepageImage.altText"}"{/if} />
	</div>
{/if}

{if $additionalHomeContent}
	<br />
	{$additionalHomeContent}
{/if}

{if $enableAnnouncementsHomepage}
	{* Display announcements *}
	<div id="announcementsHome">
		<h3 class="pkp_helpers_text_center">{translate key="announcement.announcementsHome"}</h3>
		{include file="announcements/announcements.tpl" displayLimit=true}
	</div>
{/if}

{if $spotlights|@count > 0}
	{include file="index/spotlights.tpl"}
{/if}

{foreach from=$socialMediaBlocks item=block name=b}
	<div id="socialMediaBlock{$smarty.foreach.b.index}" class="pkp_helpers_clear">
		{$block}
	</div>
{/foreach}

{include file="common/footer.tpl"}
