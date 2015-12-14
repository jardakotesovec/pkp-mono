{**
 * plugins/blocks/browse/block.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Common site sidebar menu for browsing the catalog.
 *
 * @uses $browseNewReleases bool Whether or not to show a new releases link
 * @uses $browseCategoryFactory object Category factory providing access to
 *  browseable categories.
 * @uses $browseSeriesFactory object Series factory providing access to
 *  browseable series.
 *
 *}
<div class="pkp_block block_browse">
	<span class="title">
		{translate key="plugins.block.browse"}
	</span>

	<div class="content">
		<ul>

			{if $browseNewReleases}
				<li>
					<a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="newReleases"}">
						{translate key="navigation.newReleases"}
					</a>
				</li>
			{/if}

			{if $browseCategoryFactory->count}
				<li class="has_submenu">
					{translate key="plugins.block.browse.category"}
					<ul>
						{iterate from=browseCategoryFactory item=browseCategory}
							<li class="category_{$browseCategory->getId()}{if $browseCategory->getParentId()} is_sub{/if}{if $browseBlockSelectedCategory == $browseCategory->getPath()} current{/if}">
								<a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="category" path=$browseCategory->getPath()|escape}">
									{$browseCategory->getLocalizedTitle()|escape}
								</a>
							</li>
						{/iterate}
					</ul>
				</li>
			{/if}

			{if $browseSeriesFactory->count}
				<li class="has_submenu">
					{translate key="plugins.block.browse.series"}
					<ul>
						{iterate from=browseSeriesFactory item=browseSeriesItem}
							<li class="series_{$browseSeriesItem->getId()}{if $browseBlockSelectedSeries == $browseSeriesItem->getPath()} current{/if}">
								<a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="series" path=$browseSeriesItem->getPath()|escape}">
									{$browseSeriesItem->getLocalizedTitle()|escape}
								</a>
							</li>
						{/iterate}
					</ul>
				</li>
			{/if}

		</ul>
	</div>
</div><!-- .block_browse -->
