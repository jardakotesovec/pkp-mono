{**
 * templates/controllers/grid/settings/series/form/seriesForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Series form under press management.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#seriesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="seriesForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.series.SeriesGridHandler" op="updateSeries" seriesId=$seriesId}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="seriesFormNotification"}

	{fbvFormArea id="seriesInfo"}
		{fbvFormSection for="title" required="true" description="common.prefixAndTitle.tip" title="common.name"}
			{fbvElement type="text" multilingual="true" id="title" value="$title" maxlength="80" size=$fbvStyles.size.MEDIUM inline="true"}
			{fbvElement type="text" multilingual="true" id="prefix" label="common.prefix" value="$prefix" maxlength="32" inline="true"}
		{/fbvFormSection}

		{fbvFormSection title="series.featured" for="featured" inline="true" list="true"}
			{fbvElement type="checkbox" id="featured" checked=$featured label="series.featured.description" value=1}
		{/fbvFormSection}

		{fbvFormSection title="common.description" for="context" required="true"}
		 	{fbvElement type="textarea" multilingual="true" id="description" value=$description}
		{/fbvFormSection}

		<input type="hidden" name="seriesId" value="{$seriesId|escape}"/>
		{fbvFormSection for="context" inline="true" size=$fbvStyles.size.MEDIUM}
			<div id="seriesCategoriesContainer">
				{url|assign:seriesCategoriesUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.settings.CategoriesListbuilderHandler" op="fetch" seriesId=$seriesId}
				{load_url_in_div id="seriesCategoriesContainer" url=$seriesCategoriesUrl}
			</div>
		{/fbvFormSection}

		{fbvFormSection for="context" inline="true" size=$fbvStyles.size.MEDIUM}
			<div id="seriesEditorsContainer">
				{url|assign:seriesEditorsUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.settings.SeriesEditorsListbuilderHandler" op="fetch" seriesId=$seriesId}
				{load_url_in_div id="seriesEditorsContainer" url=$seriesEditorsUrl}
			</div>
		{/fbvFormSection}

		{fbvFormSection title="series.path" required=true for="path"}
			{fbvElement type="text" id="path" value=$path size=$smarty.const.SMALL maxlength="32"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
