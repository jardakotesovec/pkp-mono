{**
 * templates/controllers/grid/gridCategoryRow.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * a category row
 *}
{assign var=categoryId value="component-"|concat:$categoryRow->getGridId():"-category-":$categoryRow->getId()}

<td colspan="{$colums|@count}">
	{if $categoryRow->getActions()}
		<div class="row_actions">
			{foreach name=actions from=$categoryRow->getActions() item=action}
				{include file="linkAction/linkAction.tpl" action=$action contextId=$categoryId}
			{/foreach}
		</div>
	{/if}
	{if $categoryRow->getActions($smarty.const.GRID_ACTION_POSITION_ROW_CLICK)}
		<div>
			{foreach name=actions from=$categoryRow->getActions($smarty.const.GRID_ACTION_POSITION_ROW_CLICK) item=action}
				{include file="linkAction/linkAction.tpl" action=$action contextId=$categoryId}
			{/foreach}
		</div>
	{/if}
	{$categoryRow->getCategoryLabel()|escape}
</td>
