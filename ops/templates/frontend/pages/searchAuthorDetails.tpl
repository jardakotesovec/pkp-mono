{**
 * templates/frontend/pages/searchAuthorDetails.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published submissions by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorDetails"}
{include file="frontend/components/header.tpl"}
{/strip}
<div id="authorDetails">
<h3>{$authorName|escape}{if $affiliation}, {$affiliation|escape}{/if}{if $country}, {$country|escape}{/if}</h3>
<ul>
{foreach from=$submissions item=article}
	{assign var=sectionId value=$article->getCurrentPublication()->getData('sectionId')}
	{assign var=journalId value=$article->getData('contextId')}
	{assign var=journal value=$journals[$journalId]}
	{assign var=section value=$sections[$sectionId]}
	{if $section && $journal}
	<li>
		<em>{$section->getLocalizedTitle()|escape}</em><br />
		{$article->getLocalizedTitle()|strip_unsafe_html}<br/>
		<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestId()}" class="file">{if $article->getCurrentPublication()->getData('abstract')}{translate key="article.abstract"}{else}{translate key="article.details"}{/if}</a>
		{if ($article->getCurrentPublication()->getData('accessStatus') == $smarty.const.ARTICLE_ACCESS_OPEN)}
		{foreach from=$article->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestId()|to_array:$galley->getBestGalleyId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
		{/foreach}
		{/if}
	</li>
	{/if}
{/foreach}
</ul>
</div>
{include file="frontend/components/footer.tpl"}

