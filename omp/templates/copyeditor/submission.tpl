{**
 * submission.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Copyeditor's submission view.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.editing" id=$submission->getMonographId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}
{/strip}

{include file="copyeditor/submission/summary.tpl"}

<div class="separator"></div>

{include file="copyeditor/submission/copyedit.tpl"}

{include file="common/footer.tpl"}
