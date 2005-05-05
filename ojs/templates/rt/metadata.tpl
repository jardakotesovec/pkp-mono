{**
 * metadata.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- article metadata page.
 *
 * $Id$
 *}

{assign var=pageTitle value="rt.articleMetadata"}

{include file="rt/header.tpl"}

<h3>"{$article->getArticleTitle()}"</h3>

<br />

<table class="listing" width="100%">
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="25%">{translate key="rt.metadata.dublinCore"}</td>
		<td class="heading" width="25%">{translate key="rt.metadata.pkpItem"}</td>
		<td class="heading" width="50%">{translate key="rt.metadata.forThisDocument"}</td>
	</tr>
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>

<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.title"}</td>
	<td>{translate key="rt.metadata.pkp.title"}</td>
	<td>{$article->getArticleTitle()}</td>
</tr>
{foreach from=$article->getAuthors() item=author}
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.primaryAuthor"}</td>
	<td>{translate key="rt.metadata.pkp.primaryAuthor"}</td>
	<td>{$author->getFullName()}{if $author->getAffiliation()}, {$author->getAffiliation()}{/if}{if $author->getEmail()}, {$author->getEmail()}{/if}</td>
</tr>
{/foreach}
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.subject"}</td>
	<td>{translate key="rt.metadata.pkp.discipline"}</td>
	<td>{$article->getDiscipline()}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.subject"}</td>
	<td>{translate key="rt.metadata.pkp.subject"}</td>
	<td>{$article->getSubject()}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.description"}</td>
	<td>{translate key="rt.metadata.pkp.abstract"}</td>
	<td>{$article->getAbstract()}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.publisher"}</td>
	<td>{translate key="rt.metadata.pkp.publisher"}</td>
	{assign var=pubUrl value=$journalSettings.publisher.url}
	<td>{if $pubUrl}<a target="_new" href="{$pubUrl}">{/if}{$journalSettings.publisher.institution}{if $pubUrl}</a>{/if}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.contributor"}</td>
	<td>{translate key="rt.metadata.pkp.sponsors"}</td>
	<td>
		{foreach from=$journalSettings.contributors item=contributor}
			{assign var=contUrl value=$contributor.url}
			{if $contUrl}<a target="_new" href="{$contUrl}">{/if}{$contributor.name}{if $contUrl}</a>{/if}<br/>
		{/foreach}
	</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.date"}</td>
	<td>{translate key="rt.metadata.pkp.date"}</td>
	<td>{$article->getDatePublished()|date_format:$dateFormatShort}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.type"}</td>
	<td>{translate key="rt.metadata.pkp.genre"}</td>
	<td>{translate key="rt.metadata.pkp.peerReviewed"}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.type"}</td>
	<td>{translate key="rt.metadata.pkp.type"}</td>
	<td>{$article->getType()}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.format"}</td>
	<td>{translate key="rt.metadata.pkp.format"}</td>
	<td>
		{foreach from=$article->getGalleys() item=galley name=galleys}
			{$galley->getLabel()}{if !$smarty.foreach.galleys.last}, {/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.identifier"}</td>
	<td>{translate key="rt.metadata.pkp.uri"}</td>
	<td><a target="_new" href="{$pageUrl}/article/view/{$articleId}/{$galleyId}">{$pageUrl|escape}/article/view/{$articleId}/{$galleyId}</a></td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.source"}</td>
	<td>{translate key="rt.metadata.pkp.source"}</td>
	<td>{$issue->getIssueIdentification()}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.language"}</td>
	<td>{translate key="rt.metadata.pkp.language"}</td>
	<td>{$article->getLanguage()}</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
{if $journalRt->getSupplementaryFiles()}
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.relation"}</td>
	<td>{translate key="rt.metadata.pkp.suppFiles"}</td>
	<td>
		{foreach from=$article->getSuppFiles() item=suppFile}
			<a href="{$pageUrl}/article/download/{$articleId}/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a> ({$suppFile->getNiceFileSize()})<br />
		{/foreach}
	</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
{/if}
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.coverage"}</td>
	<td>{translate key="rt.metadata.pkp.coverage"}</td>
	<td>
		{if $article->getCoverageGeo()}{$article->getCoverageGeo()}{assign var=notFirstItem value=1}{/if}{if $article->getCoverageChron()}{if $notFirstItem}, <br/>{/if}{$article->getCoverageChron()}{assign var=notFirstItem value=1}{/if}{if $article->getCoverageSample()}{if $notFirstItem}, <br/>{/if}{$article->getCoverageSample()}{assign var=notFirstItem value=1}{/if}
	</td>
</tr>
<tr><td colspan="3" class="separator">&nbsp;</td></tr>
<tr valign="top">
	<td>{translate key="rt.metadata.dublinCore.rights"}</td>
	<td>{translate key="rt.metadata.pkp.copyright"}</td>
	<td>{$journalSettings.copyrightNotice|nl2br}</td>
</tr>
</table>

{include file="rt/footer.tpl"}
