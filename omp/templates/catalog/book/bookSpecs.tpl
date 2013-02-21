{**
 * templates/catalog/book/bookSpecs.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the book specs portion of the public-facing book view.
 *}

<script type="text/javascript">
	// Initialize JS handler for catalog header.
	$(function() {ldelim}
		$('#bookAccordion').accordion({ldelim} autoHeight: false {rdelim});
	{rdelim});
</script>

<div class="bookSpecs">
	{assign var=coverImage value=$publishedMonograph->getCoverImage()}
	<a href="{$bookImageLinkUrl}"><img class="pkp_helpers_container_center" alt="{$publishedMonograph->getLocalizedFullTitle()|escape}" src="{url router=$smarty.const.ROUTE_COMPONENT component="submission.CoverHandler" op="catalog" monographId=$publishedMonograph->getId()}" /></a>
	<div id="bookAccordion">
		<h3><a href="#">{translate key="catalog.publicationInfo"}</a></h3>
		<div class="publicationInfo">
			<div class="dateAdded">{translate key="catalog.dateAdded" dateAdded=$publishedMonograph->getDatePublished()|date_format:$dateFormatShort}</div>
			{assign var=publicationFormats value=$publishedMonograph->getPublicationFormats(true)}
			{if count($publicationFormats) === 1}
				{foreach from=$publicationFormats item="publicationFormat"}
					{include file="catalog/book/bookPublicationFormatInfo.tpl" publicationFormat=$publicationFormat availableFiles=$availableFiles}
				{/foreach}
			{/if}
		</div>

		{if count($publicationFormats) > 1}
			{foreach from=$publicationFormats item="publicationFormat"}
				{if $publicationFormat->getIsApproved()}
					<h3><a href="#">{$publicationFormat->getLocalizedName()|escape}</a></h3>
					<div class="publicationFormat">
						{include file="catalog/book/bookPublicationFormatInfo.tpl" publicationFormat=$publicationFormat availableFiles=$availableFiles}
					</div>{* publicationFormat *}
				{/if}{* $publicationFormat->getIsAvailable() *}
			{/foreach}{* $publicationFormats *}
		{/if}{* publicationFormats > 1 *}

		{assign var=categories value=$publishedMonograph->getCategories()}
		{if !$categories->wasEmpty()}
			<h3><a href="#">{translate key="catalog.relatedCategories}</a></h3>
			<ul class="relatedCategories">
				{iterate from=categories item=category}
					<li><a href="{url op="category" path=$category->getPath()}">{$category->getLocalizedTitle()|strip_unsafe_html}</a></li>
				{/iterate}{* categories *}
			</ul>
		{/if}{* !$categories->wasEmpty() *}
	</div>
</div>
