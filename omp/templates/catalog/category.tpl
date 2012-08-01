{**
 * templates/catalog/category.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a public-facing category view in the catalog.
 *}
{strip}
{if $category}{assign var="pageTitleTranslated" value=$category->getLocalizedTitle()}{/if}
{include file="common/header.tpl"}
{/strip}

<div class="catalogContainer">

{if $category}
	{if $category->getLocalizedDescription() || $image}
	<div class="pkp_catalog_categoryDescription">
		{$category->getLocalizedDescription()|strip_unsafe_html}
		{assign var="image" value=$category->getImage()}
		{if $image}
			<a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="fullSize" type="category" id=$category->getId()}">
				<img class="pkp_helpers_container_center" height="{$image.thumbnailHeight}" width="{$image.thumbnailWidth}" src="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="thumbnail" type="category" id=$category->getId()}" alt="{$category->getLocalizedTitle()|escape}" />
			</a>
		{/if}
	</div>
	{/if}
	{* Include the carousel view of featured content *}
	{if $featuredMonographIds|@count}
		{include file="catalog/carousel.tpl" publishedMonographs=$publishedMonographs featuredMonographIds=$featuredMonographIds}
	{/if}

	{* Include the highlighted feature *}
	{include file="catalog/feature.tpl" publishedMonographs=$publishedMonographs featuredMonographIds=$featuredMonographIds}

	{* Include the full monograph list *}
	{include file="catalog/monographs.tpl" publishedMonographs=$publishedMonographs}

	</div><!-- catalogContainer -->
{/if}

{include file="common/footer.tpl"}
