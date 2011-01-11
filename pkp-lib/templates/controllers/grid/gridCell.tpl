{**
 * gridCell.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * a regular grid cell (with or without actions)
 *}
{assign var=cellId value="cell-"|concat:$id}
<span id="{$cellId|escape}">
	{if count($actions) gt 0}
		{assign var=defaultCellAction value=$actions[0]}
		{include file="linkAction/legacyLinkAction.tpl" id=$cellId|concat:"-action-":$defaultCellAction->getId() action=$defaultCellAction objectId=$cellId}
	{else}
		{$label|strip_unsafe_html}
	{/if}
</span>

