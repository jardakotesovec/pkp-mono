{**
 * templates/controllers/grid/gridActionsAbove.tpl
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Actions markup for upper grid actions
 *}

<span class="options">
	{foreach from=$actions item=action}
		{include file="linkAction/linkAction.tpl" action=$action contextId=$gridId}
	{/foreach}
</span>
