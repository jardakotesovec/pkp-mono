{**
 * editorialTeam.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}

{if count($editors) > 0}
<h3>{translate key="user.role.editors"}</h3>
<p>
{foreach from=$editors item=editor}
	{$editor->getFullName()|escape}{if strlen($editor->getAffiliation()) > 0}, {$editor->getAffiliation()|escape}{/if}
	<br />
{/foreach}
</p>
{/if}

{if count($sectionEditors) > 0}
<h3>{translate key="user.role.sectionEditors"}</h3>
<p>
{foreach from=$sectionEditors item=sectionEditor}
	{$sectionEditor->getFullName()|escape}{if strlen($sectionEditor->getAffiliation()) > 0}, {$sectionEditor->getAffiliation()|escape}{/if}
	<br/>
{/foreach}
</p>
{/if}

{if count($layoutEditors) > 0}
<h3>{translate key="user.role.layoutEditors"}</h3>
<p>
{foreach from=$layoutEditors item=layoutEditor}
	{$layoutEditor->getFullName()|escape}{if strlen($layoutEditor->getAffiliation()) > 0}, {$layoutEditor->getAffiliation()|escape}{/if}
	<br/>
{/foreach}
</p>
{/if}


{include file="common/footer.tpl"}
