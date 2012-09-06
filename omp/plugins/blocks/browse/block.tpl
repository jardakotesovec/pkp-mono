{**
 * plugins/blocks/browse/block.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- "browse" tools.
 *
 *}
<div class="block" id="sidebarBrowse">

	<span class="blockTitle pkp_helpers_dotted_underline"><a href="{url page="catalog" op="newReleases"}">{translate key="navigation.newReleases"}</a></span>
	<span class="blockTitle">{translate key="plugins.block.browse"}</span>

	<form class="pkp_form" action="#">
		<div id="browseCategoryContainer">
			<select class="applyPlugin" size="1" name="browseCategory" onchange="location.href=('{url|escape:"javascript" page="catalog" op="category" path="CATEGORY_PATH"}'.replace('CATEGORY_PATH', this.options[this.selectedIndex].value))" class="selectMenu">
				<option disabled="disabled"{if !$browseBlockSelectedCategory} selected="selected"{/if}>{translate key="plugins.block.browse.category"}</option>
				{iterate from=browseCategories item=browseCategory}
					<option {if $browseBlockSelectedCategory == $browseCategory->getPath()}selected="selected"{/if} value="{$browseCategory->getPath()|escape}">{if $browseCategory->getParentId()}&nbsp;&nbsp;{/if}{$browseCategory->getLocalizedTitle()|escape}</option>
				{/iterate}
			</select>
		</div>
		<div id="browseSeriesContainer">
			<select class="applyPlugin" size="1" name="browseSeries" onchange="location.href=('{url|escape:"javascript" page="catalog" op="series" path="SERIES_PATH"}'.replace('SERIES_PATH', this.options[this.selectedIndex].value))" class="selectMenu">
				<option disabled="disabled"{if !$browseBlockSelectedSeries} selected="selected"{/if}>{translate key="plugins.block.browse.series"}</option>
				{iterate from=browseSeries item=browseSeriesItem}
					<option {if $browseBlockSelectedSeries == $browseSeriesItem->getPath()}selected="selected"{/if} value="{$browseSeriesItem->getPath()|escape}">{$browseSeriesItem->getLocalizedTitle()|escape}</option>
				{/iterate}
			</select>
		</div>
	</form>
</div>
