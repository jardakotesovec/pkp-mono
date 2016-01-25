{**
 * controllers/grid/plugins/pluginGridFilter.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter template for plugin grid.
 *}
<script>
	// Attach the form handler to the form.
	$('#pluginSearchForm').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form class="pkp_form filter" id="pluginSearchForm" action="{url router=$smarty.const.ROUTE_COMPONENT op="fetchGrid"}" method="post">
	{fbvFormArea id="userSearchFormArea"}
		{fbvFormSection}
			{fbvElement type="select" id="category" from=$filterData.categories selected=$filterSelectionData.category translate=false size=$fbvStyles.size.SMALL inline=true}
			{fbvElement type="text" id="pluginName" value=$filterSelectionData.pluginName size=$fbvStyles.size.LARGE inline=true}
		{/fbvFormSection}
		{* Buttons generate their own section *}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
