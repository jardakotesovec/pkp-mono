{**
 * validate.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Validate URLs for searches.
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.validateUrls}
{include file="common/header.tpl"}

<p>{translate key="rt.admin.validateUrls.description"}</p>

{foreach from=$versions item=version}
	<h3>{$version->getTitle()}</h3>
	<ul>
	{foreach from=$version->getContexts() item=context}
		<li>{$context->getTitle()}
		{assign var=errors value=0}
		{foreach from=$context->getSearches() item=search}
			{assign var=errors value=$search|validate_url:$errors}.
		{/foreach}
		{foreach from=$errors item=error}
			<br />
			{translate key="rt.admin.validateUrls.urlIsInvalid" url=$error|truncate|escape}&nbsp;&nbsp;<a href="{$requestPageUrl}/editSearch/{$version->getVersionId()}/{$context->getContextId()}/{$search->getSearchId()}" class="action">{translate key="common.edit"}</a>
		{foreachelse}
			{translate key="rt.admin.validateUrls.ok"}
		{/foreach}
		</li>
	{/foreach}
	</ul>
{/foreach}

{include file="common/footer.tpl"}
