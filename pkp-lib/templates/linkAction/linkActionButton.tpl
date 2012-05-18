{**
 * linkActionButton.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template that renders a button for a link action.
 *
 * Parameter:
 *  action: The LinkAction we create a button for.
 *  buttonId: The id of the link.
 *  hoverTitle: Whether to show the title as hover text only.
 *}

{if !$imageClass}
	{assign var="imageClass" value="sprite"}
{/if}
<a href="javascript:$.noop();" id="{$buttonId|escape}" {strip}
	{if $action->getImage()}
		class="{$imageClass} {$action->getImage()|escape} pkp_controllers_linkAction"
		{if $hoverTitle}title="{$action->getTitle()|escape}">&nbsp;{else}>{$action->getTitle()|escape}{/if}
	{else}
		class="pkp_controllers_linkAction"
		{if $hoverTitle} title="{$action->getTitle()|escape}">{else}>{$action->getTitle()|escape}{/if}
	{/if}
{/strip}</a>
