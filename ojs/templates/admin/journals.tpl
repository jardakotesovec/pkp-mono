{**
 * journals.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of journals in site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.journals"}
{assign var="pageId" value="admin.journals"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="manager.setup.journalTitle"}</td>
	<td>{translate key="admin.journals.path"}</td>
	<td></td>
	<td></td>
	<td></td>
</tr>
{foreach from=$journals item=journal}
<tr class="{cycle values="row,rowAlt"}">
	<td width="100%"><a href="{$indexUrl}/{$journal->getPath()}/manager">{$journal->getTitle()}</a></td>
	<td>{$journal->getPath()}</td>
	<td><a href="#" onclick="confirmAction('{$pageUrl}/admin/deleteJournal/{$journal->getJournalId()}', '{translate|escape:"javascript" key="admin.journals.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></td>
	<td><a href="{$pageUrl}/admin/editJournal/{$journal->getJournalId()}" class="tableAction">{translate key="common.edit"}</a></td>
	<td><nobr><a href="{$pageUrl}/admin/moveJournal?d=u&amp;journalId={$journal->getJournalId()}">&uarr;</a> <a href="{$pageUrl}/admin/moveJournal?d=d&amp;journalId={$journal->getJournalId()}">&darr;</a></nobr></td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="admin.journals.noneCreated"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/admin/createJournal" class="tableButton">{translate key="admin.journals.create"}</a>

{include file="common/footer.tpl"}
