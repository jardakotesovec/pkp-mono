{**
 * bio.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- author bio page.
 *
 * $Id$
 *}

{assign var=pageTitle value="rst.aboutAuthor"}

{include file="rt/header.tpl"}

{foreach from=$article->getAuthors() item=author name=authors}
<p>
	{$author->getFullName()}<br />
	{if $author->getAffiliation()}<strong>{$author->getAffiliation()}</strong>{/if}
</p>

<p>{$author->getBiography()}</p>

{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

{/foreach}

<div class="separator"></div>
<a href="javascript:window.close()">{translate key="common.close"}</a>

{include file="rt/footer.tpl"}
