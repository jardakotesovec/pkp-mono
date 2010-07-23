{**
 * citationGridCell.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A citation editor grid cell.
 *}
{assign var=cellId value="cell-"|concat:$id}
<span id="{$cellId}">
	{assign var=cellAction value=$actions[0]}
	{include file="linkAction/linkAction.tpl" id=$cellId|concat:"-action-":$cellAction->getId() action=$cellAction actOnId=$cellAction->getActOn() buttonId=$cellId}
	[{$citationSeq}] {$label|escape}
	<script type="text/javascript">
		$(function() {ldelim}
			$parentDiv = $('#{$cellId}').parent();

			// Format parent div.
			$parentDiv
				.addClass('active_cell')
				{if $isApproved}.addClass('approved_citation'){/if}
				.attr('title', '{$cellAction->getLocalizedTitle()} [{if $isApproved}Approved{else}Not Approved{/if}]');

			// Copy click event to parent div.
			clickEventHandlers = $('#{$cellId}').data('events')['click'];
			for(clickEventName in clickEventHandlers) {ldelim}
				$parentDiv.click(clickEventHandlers[clickEventName]);
			{rdelim}
		{rdelim});
	</script>
</span>
