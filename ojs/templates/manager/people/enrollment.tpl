{**
 * enrollment.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List enrolled users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
function checkAll (allOn) {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name = 'bcc') {
			elements[i].checked = allOn;
		}
	}
}
{/literal}
</script>

<h3>{translate key=$roleName}</h3>

{if not $roleId}
<ul>
	<li><a href="{$pageUrl}/manager/people/managers">{translate key="user.role.managers"}</a></li>
	<li><a href="{$pageUrl}/manager/people/editors">{translate key="user.role.editors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/sectionEditors">{translate key="user.role.sectionEditors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/layoutEditors">{translate key="user.role.layoutEditors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/copyeditors">{translate key="user.role.copyeditors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/proofreaders">{translate key="user.role.proofreaders"}</a></li>
	<li><a href="{$pageUrl}/manager/people/reviewers">{translate key="user.role.reviewers"}</a></li>
	<li><a href="{$pageUrl}/manager/people/authors">{translate key="user.role.authors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/readers">{translate key="user.role.readers"}</a></li>
</ul>

<br />
{else}
<p><a href="{$pageUrl}/manager/people/all" class="action">{translate key="manager.people.allUsers"}</a></p>
{/if}

<form action="{$requestPageUrl}/email" method="post" name="submit">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator"></td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%"></td>
		<td width="15%">{translate key="user.username"}</td>
		<td width="25%">{translate key="user.name"}</td>
		<td width="25%">{translate key="user.email"}</td>
		<td width="30%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator"></td>
	</tr>
	{foreach name=users from=$users item=user}
	{assign var=userExists value=1}
	<tr valign="top">
		<td><input type="checkbox" name="bcc[]" value="{$user->getEmail()|escape}"/></td>
		<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
		<td>{$user->getFullName()}</td>
		<td>
			{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			<nobr>{$user->getEmail()}&nbsp;{icon name="mail" url="`$requestPageUrl`/email?to[]=$emailStringEscaped"}</nobr>
		</td>
		<td align="right">
			{if $roleId}
			<a href="{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$roleId}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="action">{translate key="manager.people.unenroll"}</a>
			{/if}
			<a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$pageUrl}/manager/signInAsUser/{$user->getUserId()}" class="action">{translate key="manager.people.signInAs"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $smarty.foreach.users.last}end{/if}separator"></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator"></td>
	</tr>
	{/foreach}
</table>

{if $userExists}
	<p><input type="submit" value="{translate key="email.compose"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onClick="checkAll(true)"/>&nbsp;<input type="button" value="{translate key="common.selectNone"}" class="button" onClick="checkAll(false)"/></p>
{/if}
</form>

{if $roleId}
<a href="{$pageUrl}/manager/enrollSearch/{$roleId}" class="action">{translate key="manager.people.enrollExistingUser"}</a> |
{/if}
<a href="{$pageUrl}/manager/createUser" class="action">{translate key="manager.people.createUser"}</a>

{include file="common/footer.tpl"}
