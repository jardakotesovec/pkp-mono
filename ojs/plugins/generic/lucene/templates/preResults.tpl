{**
 * plugins/generic/lucene/templates/preResults.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Search::SearchResults::PreResults hook.
 *}
<div id="lucenePreResults">
	<p>
		{translate key="plugins.generic.lucene.results.orderBy"}:&nbsp;
		<select id="luceneSearchOrder" name="luceneOrderBy" class="selectMenu">
			{html_options options=$luceneOrderByOptions selected=$orderBy}
		</select>
		&nbsp;
		<select id="luceneSearchDirection" name="luceneOrderDir" class="selectMenu">
			{html_options options=$luceneOrderDirOptions selected=$orderDir}
		</select>
		&nbsp;

		<script type="text/javascript">
			// Get references to the required elements.
			var $orderBySelect = $('#content #luceneSearchOrder');
			var $orderDirSelect = $('#content #luceneSearchDirection');

			function luceneReorder(useDefaultOrderDir) {ldelim}
				var reorderUrl = '{strip}
						{if $basicQuery}
							{url query=$basicQuery searchField=$searchField escape=false}
						{else}
							{url query=$query searchJournal=$searchJournal author=$author title=$title fullText=$fullText supplementaryFiles=$supplementaryFiles discipline=$discipline subject=$subject type=$type coverage=$coverage dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear escape=false}
						{/if}
					{/strip}';
				var orderBy = $orderBySelect.val();
				if (useDefaultOrderDir) {ldelim}
					var orderDir = '';
				{rdelim} else {ldelim}
					var orderDir = $orderDirSelect.val();
				{rdelim}
				reorderUrl += '&orderBy=' + orderBy + '&orderDir=' + orderDir;
				window.location = reorderUrl;
			{rdelim}

			$orderBySelect.change(function() {ldelim} luceneReorder(true); {rdelim});
			$orderDirSelect.change(function() {ldelim} luceneReorder(false); {rdelim});
		</script>
	</p>
</div>
