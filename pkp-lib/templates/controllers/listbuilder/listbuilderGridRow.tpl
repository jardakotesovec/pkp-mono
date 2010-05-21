{**
 * gridRow.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * a regular grid row
 *}
{assign var=rowId value=$row->getId()}
<tr id="{$rowId}">
	{foreach from=$cells item=cell name=cell}
		<td>{$cell}</td>
	{/foreach}
</tr>
