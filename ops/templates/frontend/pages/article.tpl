{**
 * templates/frontend/pages/article.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view an article with all of it's details.
 *
 * @uses $article Article This article
 * @uses $ccLicenseBadge @todo
 *}
{include file="common/frontend/header.tpl" pageTitleTranslated=$article->getLocalizedTitle()|escape}

<div class="page">

	{* A specific galley is being viewed *}
	{* @todo figure out when and how this is used *}
	{if $galley}
		<h1 class="page_title">{$article->getLocalizedTitle()|escape}</h1>

		{assign var=pubObject value=$galley}
		{call_hook name="Templates::Galley::displayGalley" fileId=$fileId}

	{* Show article overview *}
	{else}
		{include file="frontend/objects/article_details.tpl"}

		{* Display a legend describing the open/restricted access icons *}
		{if $article->getGalleys()}
			{include file="frontend/components/accessLegend.tpl"}
		{/if}
	{/if}

	{* Copyright and licensing *}
	{* @todo has not been tested *}
	{if $currentJournal->getSetting('includeCopyrightStatement')}
		<div class="article_copyright">
			{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear()|escape copyrightHolder=$article->getLocalizedCopyrightHolder()|escape}
		</div>
	{/if}

	{if $currentJournal->getSetting('includeLicense') && $ccLicenseBadge}
		<div class="article_license">
			{$ccLicenseBadge}
		</div>
	{/if}

	{call_hook name="Templates::Article::Footer::PageFooter"}
	{if $pageFooter}
		{$pageFooter}
	{/if}

</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
