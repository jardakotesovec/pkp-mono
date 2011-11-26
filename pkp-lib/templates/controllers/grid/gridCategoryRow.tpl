{**
 * templates/controllers/grid/gridCategoryRow.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * a category row
 *}
{assign var=categoryId value="component-"|concat:$categoryRow->getGridId():"-category-":$categoryRow->getId()}
{foreach name=columnLoop from=$columns key=columnId item=column}
	{if $smarty.foreach.columnLoop.first}
		<td>
			{if $categoryRow->getActions()}
				{foreach name=actions from=$categoryRow->getActions() item=action}
					{include file="linkAction/linkAction.tpl" action=$action contextId=$gridId}
				{/foreach}
			{/if}
			{$categoryRow->getCategoryLabel()|escape}
		</td>
	{else}
		<td />
	{/if}
{/foreach}

