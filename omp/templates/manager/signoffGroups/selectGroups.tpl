{**
 * selectGroups.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of groups
 *
 * $Id: selectGroups.tpl
 *}
{strip}
{assign var="pageTitle" value="manager.reviewSignoff.selectGroup"}
{include file="common/header.tpl"}
{/strip}

<br/>
<div id="groups">

<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td colspan="2" width="75%">{translate key="manager.groups.title"}</td>
		<td width="25%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{assign var="isFirstEditorialTeamEntry" value=1}
{iterate from=groups item=group}
	<tr valign="top">
		{if $group->getContext() == GROUP_CONTEXT_EDITORIAL_TEAM}
			{if $isFirstEditorialTeamEntry}
				{assign var="isFirstEditorialTeamEntry" value=0}
					<td colspan="3">{translate key="manager.groups.context.editorialTeam.short"}</td>
					</tr>
					<tr>
						<td colspan="3" class="separator">&nbsp;</td>
					</tr>
					<tr valign="top">
			{/if}
			<td width="5%">&nbsp;</td>
			<td>
				{url|assign:"url" page="manager" op="email" toGroup=$group->getId()}
				{$group->getLocalizedTitle()|escape}&nbsp;{icon name="mail" url=$url}
			</td>
		{else}
			<td colspan="2">
				{url|assign:"url" page="manager" op="email" toGroup=$group->getId()}
				{$group->getLocalizedTitle()|escape}&nbsp;{icon name="mail" url=$url}
			</td>
		{/if}
		<td align="right">
			<a href="{url op="addSignoffGroup" path=$reviewType groupId=$group->getId()}" class="action">{translate key="manager.reviewSignoff.addGroup"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $groups->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $groups->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.groups.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$groups}</td>
		<td colspan="1" align="right">{page_links anchor="groups" name="groups" iterator=$groups}</td>
	</tr>
{/if}
</table>

<a href="{url op="createGroup"}" class="action">{translate key="manager.groups.create"}</a>
</div>

{include file="common/footer.tpl"}