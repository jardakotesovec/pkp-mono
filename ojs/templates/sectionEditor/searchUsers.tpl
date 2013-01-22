{**
 * templates/sectionEditor/searchUsers.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for enrolled users.
 *
 *
 *}
{strip}
{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}
{/strip}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#submit').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="submit" method="post" action="{url op="enrollSearch" path=$articleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op="enrollSearch" path=$articleId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="enrollSearch" path=$articleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<div id="users">
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#enrollForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="enrollForm" action="{url op="enroll" path=$articleId}" method="post">
<table class="listing">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="5%">&nbsp;</td>
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
	<td>{translate key="user.email"}</td>
	<td>{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getId()}
{assign var="stats" value=$statistics[$userid]}
<tr>
	<td><input type="checkbox" name="users[]" value="{$user->getId()}" /></td>
	<td><a class="action" href="{url op="userProfile" path=$userid}">{$user->getUsername()|escape}</a></td>
	<td>{$user->getFullName(true)|escape}</td>
	<td>{$user->getEmail(true)|escape}</td>
	<td><a href="{url op="enroll" path=$articleId userId=$user->getId()}" class="action">{translate key="manager.people.enroll"}</a></td>
</tr>
<tr><td colspan="5" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="5" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth}</td>
	</tr>
{/if}
</table>
</div>
<input type="submit" value="{translate key="manager.people.enrollSelected"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" escape=false}'" />

</form>


{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}

