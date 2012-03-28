{**
 * templates/controllers/grid/listbuilderGridCell.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * a regular listbuilder grid cell (with or without actions)
 *}
{if $id}
	{assign var=cellId value="cell-"|concat:$id}
{else}
	{assign var=cellId value=""}
{/if}
<span {if $cellId}id="{$cellId|escape}" {/if}class="pkp_linkActions gridCellContainer">
	<div class="gridCellDisplay">
		{if $column->getFlag('sourceType') == $smarty.const.LISTBUILDER_SOURCE_TYPE_SELECT}
			{**
			 * Include a hidden element containing the current key.
		 	 * Used e.g. to match the currently selected value.
			 *}
			<input type="hidden" value="{$labelKey|escape}" />
		{/if}

		{* Display the current value *}
		{include file="controllers/grid/gridCellContents.tpl"}
	</div>

	<div class="gridCellEdit">
		{if $column->getFlag('sourceType') == $smarty.const.LISTBUILDER_SOURCE_TYPE_TEXT}
			{if $column->hasFlag('multilingual')}{* Multilingual *}

				{assign var="FBV_id" value="newRowId"}{* we can just use this generic name since a unique id gets appended to it in the template *}
				{assign var="FBV_name" value="newRowId["|concat:$column->getId()|escape|concat:"]"}
				{include file="form/textInput.tpl" formLocale=$primaryLocale FBV_id=$FBV_id FBV_name=$FBV_name FBV_value=$label FBV_multilingual=true formLocales=$formLocales}

			{else}{* Not multilingual *}
				<input type="text" name="newRowId[{$column->getId()|escape}]" class="textField" value="{$label|escape}" />
			{/if}
		{elseif $column->getFlag('sourceType') == $smarty.const.LISTBUILDER_SOURCE_TYPE_SELECT}
			<select name="newRowId[{$column->getId()|escape}]" class="selectMenu">
				{* Populated by JavaScript in ListbuilderHandler.js *}
				<option value="{$labelKey|escape}">{translate key="common.loading"}</option>
			</select>
		{/if}
	</div>
</span>

