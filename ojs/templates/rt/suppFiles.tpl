{**
 * templates/rt/suppFiles.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- supplementary files page.
 *
 *}
{strip}
{assign var=pageTitle value="rt.suppFiles"}
{include file="rt/header.tpl"}
{/strip}

<h3>{$article->getLocalizedTitle()|strip_unsafe_html}</h3>

{foreach from=$article->getSuppFiles() item=suppFile key=key}
<h4>{$key+1}. {$suppFile->getSuppFileTitle()|escape}</h4>
<table class="data">
<tr>
	<td class="label">{translate key="common.subject"}</td>
	<td class="value">
		{$suppFile->getSuppFileSubject()|escape}
	</td>
</tr>
<tr>
	<td class="label">{translate key="common.type"}</td>
	<td class="value">
		{if $suppFile->getType()|escape}
			{$suppFile->getType()|escape}
		{elseif $suppFile->getSuppFileTypeOther()}
			{$suppFile->getSuppFileTypeOther()|escape}
		{else}
			{translate key="common.other"}
		{/if}
	</td>
</tr>
<tr>
	<td class="label">&nbsp;</td>
	<td class="value">
		<a href="{url page="article" op="downloadSuppFile" path=$article->getBestArticleId()|to_array:$suppFile->getBestSuppFileId($currentJournal)}" class="action">{if $suppFile->isInlineable() || $suppFile->getRemoteURL()}{translate key="common.view"}{else}{translate key="common.download"}{/if}</a> {if !$suppFile->getRemoteURL()}({$suppFile->getNiceFileSize()}){/if}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="suppFileMetadata" path=$articleId|to_array:$galleyId:$suppFile->getId()}" class="action">{translate key="rt.suppFiles.viewMetadata"}</a>
	</td>
</tr>
</table>

{/foreach}

{include file="rt/footer.tpl"}

