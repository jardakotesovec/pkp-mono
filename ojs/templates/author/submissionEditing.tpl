{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$pageUrl}/author/submission/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$pageUrl}/author/submissionEditing/{$submission->getArticleId()}"  class="active">{translate key="submission.submissionEditing"}</a></li>
</ul>
<ul id="subnav">
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td colspan="2">
					{translate key="article.title"}: <strong>{$submission->getArticleTitle()}</strong> <br />
					{translate key="article.authors"}: {foreach from=$submission->getAuthors() item=author key=key}{if $key neq 0},{/if} {$author->getFullName()}{/foreach}
				</td>
			</tr>
			<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$pageUrl}/sectionEditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="article.section"}: {$submission->getSectionTitle()}</td>
			</tr>
			<tr>
				<td colspan="2">
					{translate key="article.file"}:
					{if $submissionFile}
						<a href="{$pageUrl}/author/downloadFile/{$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}</td>
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			<tr>
				<td valign="top">
					<table class="plainFormat">
						<tr>
							<td valign="top">{translate key="article.suppFiles"}:</td>
							<td valign="top">
								{foreach from=$suppFiles item=suppFile}
									<a href="{$pageUrl}/author/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
								{foreachelse}
									{translate key="common.none"}
								{/foreach}
							</td>
						</tr>
					</table>
				</td>
				<td>
					<form method="post" action="{$pageUrl}/author/addSuppFile/{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.addSuppFile"}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.copyedit"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td width="5%"></td>
				<td width="25%"></td>
				<td width="40%"></td>
				<td class="label" width="15%">{translate key="submission.request"}</td>
				<td class="label" width="15%">{translate key="submission.complete"}</td>
			</tr>
			<tr>
				<td>1.</td>
				<td>{translate key="submission.initialCopyedit"}</td>
				<td></td>
				<td>{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}</td>
				<td>{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}</td>
			</tr>
			<tr>
				<td>2.</td>
				<td>{translate key="submission.editorAuthorReview"}</td>
				<td align="right">
					{if not $submission->getCopyeditorDateCompleted()}
						<form method="post" action="{$pageUrl}/author/completeAuthorCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="author.article.complete"}">
						</form>
					{/if}
				</td>
				<td>{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}</td>
				<td>{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}</td>
			</tr>
			<tr>
				<td>3.</td>
				<td>{translate key="submission.finalCopyedit"}</td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
