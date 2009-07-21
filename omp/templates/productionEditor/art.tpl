{**
 * submission.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission summary.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.art" id=$submission->getMonographId()}
{assign var="pageCrumbTitle" value="submission.art"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getMonographId()}">{translate key="submission.summary"}</a></li>
	<li class="current"><a href="{url op="submissionArt" path=$submission->getMonographId()}">{translate key="submission.art"}</a></li>
	<li><a href="{url op="submissionLayout" path=$submission->getMonographId()}">{translate key="submission.layout"}</a></li>
</ul>

{include file="productionEditor/submission/summary.tpl"}

<div class="separator"></div>
<h3>{translate key="underConstruction.newSpecs"}</h3>
{include file="productionEditor/submission/components.tpl"}

{include file="common/footer.tpl"}
