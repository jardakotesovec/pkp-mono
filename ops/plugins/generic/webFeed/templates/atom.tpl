{**
 * plugins/generic/webFeed/templates/atom.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{url page="feed" op="feed"}</id>
	<title>{$server->getLocalizedName()|escape:"html"|strip}</title>

	{* Figure out feed updated date *}
	{assign var=latestDate value=null}
	{foreach name=sections from=$publishedSubmissions item=section}
		{foreach from=$section.articles item=article}
			{if $article->getLastModified() > $latestDate}
				{assign var=latestDate value=$article->getLastModified()}
			{/if}
		{/foreach}
	{/foreach}
	<updated>{$latestDate|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

	{* recommended elements *}
	{if $server->getData('contactName')}
		<author>
			<name>{$server->getData('contactName')|strip|escape:"html"}</name>
			{if $server->getData('contactEmail')}
			<email>{$server->getData('contactEmail')|strip|escape:"html"}</email>
			{/if}
		</author>
	{/if}

	<link rel="alternate" href="{url server=$server->getPath()}" />
	<link rel="self" type="application/atom+xml" href="{url page="feed" op="atom"}" />

	{* optional elements *}

	{* <category/> *}
	{* <contributor/> *}

	<generator uri="https://pkp.sfu.ca/ops/" version="{$opsVersion|escape}">Open Server Systems</generator>
	{if $server->getLocalizedDescription()}
		{assign var="description" value=$server->getLocalizedDescription()}
	{elseif $server->getLocalizedData('searchDescription')}
		{assign var="description" value=$server->getLocalizedData('searchDescription')}
	{/if}

	<subtitle type="html">{$description|strip|escape:"html"}</subtitle>

	{foreach name=sections from=$publishedSubmissions item=section key=sectionId}
		{foreach from=$section.articles item=article}
			<entry>
				{* required elements *}
				<id>{url page="article" op="view" path=$article->getBestId()}</id>
				<title>{$article->getLocalizedTitle()|strip|escape:"html"}</title>
				<updated>{$article->getLastModified()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

				{* recommended elements *}

				{foreach from=$article->getCurrentPublication()->getData('authors') item=author name=authorList}
					<author>
						<name>{$author->getFullName(false)|strip|escape:"html"}</name>
						{if $author->getEmail()}
							<email>{$author->getEmail()|strip|escape:"html"}</email>
						{/if}
					</author>
				{/foreach}{* authors *}

				<link rel="alternate" href="{url page="article" op="view" path=$article->getBestId()}" />

				{if $article->getLocalizedAbstract()}
					<summary type="html" xml:base="{url page="article" op="view" path=$article->getBestId()}">{$article->getLocalizedAbstract()|strip|escape:"html"}</summary>
				{/if}

				{* optional elements *}
				{* <category/> *}
				{* <contributor/> *}

				{if $article->getDatePublished()}
					<published>{$article->getDatePublished()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
				{/if}

				{* <source/> *}
				<rights>{translate|escape key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear() copyrightHolder=$article->getLocalizedCopyrightHolder()}</rights>
			</entry>
		{/foreach}{* articles *}
	{/foreach}{* sections *}
</feed>
