{**
 * plugins/viewableFiles/lensGalley/articleGalley.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a PDF galley.
 *}
{include file="common/frontend/header.tpl" pageTitleTranslated=$issue->getIssueSeries()|escape}

<div class="page">
	{url|assign:"xmlUrl" op="download" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal):$firstGalleyFile->getId() escape=false}
	{include file="$pluginTemplatePath/display.tpl" xmlUrl=$xmlUrl}
</div>

{include file="common/frontend/footer.tpl"}
