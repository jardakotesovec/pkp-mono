{**
 * templates/controllers/grid/settings/series/form/seriesForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
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

<form class="pkp_form" id="seriesForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.series.SeriesGridHandler" op="updateSeries"}">
{include file="controllers/notification/inPlaceNotification.tpl" notificationId="seriesFormNotification"}

{fbvFormArea id="mastheadInfo"}
{fbvFormSection title="common.name" required="true" for="title"}
	{fbvElement type="text" multilingual="true" id="title" value="$title" maxlength="80"}
{/fbvFormSection}
{fbvFormSection title="manager.setup.category" for="context"}
<select name="category" class="field select">
	<option>{translate key='common.none'}</option>
	{foreach from=$categories item=category}
	<option value="{$category.id}" {if $currentCategory == $category.id}selected="selected"{/if}>{$category.title}</option>
	{/foreach}
</select>
{/fbvFormSection}


{fbvFormSection title="user.affiliation" for="context" required="true"}
 	{fbvElement type="text" multilingual="true" id="affiliation" value="$affiliation" maxlength="80"}
{/fbvFormSection}
{/fbvFormArea}

{if $gridId}
	<input type="hidden" name="gridId" value="{$gridId|escape}" />
{/if}
{if $rowId}
	<input type="hidden" name="rowId" value="{$rowId|escape}" />
{/if}
{if $seriesId}
	<input type="hidden" name="seriesId" value="{$seriesId|escape}" />
{/if}

<br />
{if $seriesId}
	<div id="seriesEditorsContainer">
		<input type="hidden" name="seriesId" value="{$seriesId}"/>
		{url|assign:seriesEditorsUrl router=$smarty.const.ROUTE_COMPONENT component="listbuilder.settings.SeriesEditorsListbuilderHandler" op="fetch" seriesId=$seriesId}
		{load_url_in_div id="seriesEditorsContainer" url=$seriesEditorsUrl}
	</div>
{/if}
{fbvFormButtons submitText="common.save"}
</form>

