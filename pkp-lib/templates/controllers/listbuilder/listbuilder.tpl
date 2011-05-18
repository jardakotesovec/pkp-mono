{**
 * templates/controllers/listbuilder/listbuilder.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays a ListBuilder object
 *}

{assign var=gridId value="component-"|concat:$grid->getId()}
{assign var=gridTableId value=$gridId|concat:"-table"}

<script type="text/javascript">
	$(function() {ldelim}
		$('#{$gridId|escape}').pkpHandler(
			'$.pkp.controllers.listbuilder.ListbuilderHandler',
			{ldelim}
				gridId: '{$grid->getId()|escape:javascript}',
				fetchRowUrl: '{url|escape:javascript op='fetchRow' params=$gridRequestArgs escape=false}',
				fetchOptionsUrl: '{url|escape:javascript op='fetchOptions' params=$gridRequestArgs escape=false}',
				saveUrl: '{url|escape:javascript op='save' params=$gridRequestArgs escape=false}',
				sourceType: '{$grid->getSourceType()|escape:javascript}'
			{rdelim}
		);
	});
</script>


<div id="{$gridId|escape}" class="pkp_controllers_grid pkp_controllers_listbuilder formWidget">
	<div class="wrapper">
		{if $grid->getActions($smarty.const.GRID_ACTION_POSITION_ABOVE)}
			{include file="controllers/grid/gridActionsAbove.tpl" actions=$grid->getActions($smarty.const.GRID_ACTION_POSITION_ABOVE) gridId=$gridId}
		{/if}
		{if !$grid->getIsSubcomponent()}<h3>{$grid->getTitle()|translate}</h3>{/if}
		<table id="{$gridTableId|escape}">
			<tbody>
				{foreach from=$rows item=lb_row}
					{$lb_row}
				{/foreach}
				{**
					We need the last (=empty) line even if we have rows
					so that we can restore it if the user deletes all rows.
				**}
				<tr class="empty"{if $rows} style="display: none;"{/if}>
					<td colspan="{$columns|@count}">{translate key="grid.noItems"}</td>
				</tr>
				<div class="newRow"></div>
			</tbody>
		</table>
	</div>
</div>

