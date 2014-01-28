{**
 * templates/catalog/book/googlescholar.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Metadata elements for monographs based on preferred types for Google Scholar
 *
 *}
	<meta name="gs_meta_revision" content="1.1" />
{foreach name="authors" from=$publishedMonograph->getAuthors() item=author}
        <meta name="citation_author" content="{$author->getFirstName()|escape}{if $author->getMiddleName() != ""} {$author->getMiddleName()|escape}{/if} {$author->getLastName()|escape}"/>
{/foreach}
<meta name="citation_title" content="{$publishedMonograph->getLocalizedTitle()|strip_tags|escape}"/>

{if is_a($publishedMonograph, 'PublishedMonograph') && $publishedMonograph->getDatePublished()}
	<meta name="citation_publication_date" content="{$publishedMonograph->getDatePublished()|date_format:"%Y/%m/%d"}"/>
{/if}
	<meta name="citation_publisher" content="{$currentPress->getSetting('publisher')|escape}"/>
	{* <meta name="citation_isbn" content=""/> *}
	<meta name="citation_keywords" content=""/>
{if $publishedMonograph->getSubject(null)}{foreach from=$publishedMonograph->getSubject(null) key=metaLocale item=metaValue}
	{foreach from=$metaValue|explode:"; " item=gsKeyword}
		{if $gsKeyword}
			<meta name="citation_keywords" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$gsKeyword|escape}"/>
		{/if}
	{/foreach}
{/foreach}{/if}

