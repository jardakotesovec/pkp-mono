{**
 * titleIndex.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display published articles by title
 *
 * $Id$
 *}

{assign var=pageTitle value="search.titleIndex"}

{include file="common/header.tpl"}

<br />

{if $currentJournal}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<table width="100%" class="listing">
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	{if !$currentJournal}<td width="20%">{translate key="journal.journal"}</td>{/if}
	<td width="20%">{translate key="issue.issue"}</td>
	<td width="{if !$currentJournal}60%{else}80%{/if}" colspan="2">{translate key="article.title"}</td>
</tr>
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=section value=$result.section}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
<tr valign="top">
	{if !$currentJournal}<td><a href="{$indexUrl}/{$journal->getPath()|escape:"url"}">{$journal->getTitle()|escape}</a></td>{/if}
	<td>{if $issue->getAccessStatus()}<a href="{$indexUrl}/{$journal->getPath()|escape}/issue/view/{$issue->getBestIssueId($journal)|escape:"url"}">{/if}{$issue->getIssueIdentification()|escape}{if $issue->getAccessStatus()}</a>{/if}</td>
	<td width="35%">{$article->getArticleTitle()|escape}</td>
	<td width="25%" align="right">
		{if !$section->getAbstractsDisabled()}
			<a href="{$indexUrl}/{$journal->getPath()|escape:"url"}/article/view/{$publishedArticle->getBestArticleId($journal)|escape:"url"}" class="file">{translate key="article.abstract"}</a>
			{assign var=needsSpace value=1}
		{else}
			{assign var=needsSpace value=0}
		{/if}
		{if ($issue->getAccessStatus() || $issueAvailable)}
		{foreach from=$publishedArticle->getGalleys() item=galley name=galleyList}
			{if $needsSpace}
				&nbsp;
			{else}
				{assign var=needsSpace value=1}
			{/if}
			<a href="{$indexUrl}/{$journal->getPath()|escape:"url"}/article/view/{$publishedArticle->getBestArticleId($journal)|escape:"url"}/{$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
		{/foreach}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="{$numCols}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$article->getAuthors() item=author name=authorList}
			{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="{$numCols}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentJournal}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		<td colspan="2" align="right">{page_links iterator=$results name="search"}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
