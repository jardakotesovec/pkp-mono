{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Layout editor's view of submission details.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<br />

{assign var=layoutAssignment value=$submission->getLayoutAssignment()}
{assign var=proofAssignment value=$submission->getProofAssignment()}
{assign var=layoutFile value=$layoutAssignment->getLayoutFile()}
<a name="layout"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.layout"}</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		{if $useLayoutEditors}
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%">
					{if $layoutAssignment->getEditorId()}
						<span class="boldText">{translate key="user.role.layoutEditor"}:</span> {$layoutAssignment->getEditorFullName()}
					{else}
						<form method="post" action="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.layout.assignLayoutEditor"}">
						</form>
					{/if}
				</td>
				<td width="60%">
					{if $layoutAssignment->getEditorId()}
						<form method="post" action="{$requestPageUrl}/assignLayoutEditor/{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.layout.replaceLayoutEditor"}">
						</form>
					{/if}
				</td>
			</tr>
		</table>
		{/if}
		<table class="plainFormat" width="100%">
			<tr>
				<td width="100%">
					<span class="boldText">{translate key="submission.layout.layoutVersion"}:</span>
					{if $layoutFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()}</a> {$layoutFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><strong>{translate key="submission.layout.initialGalleyCreation"}</strong></td>
				<td width="20%" align="center"><strong>{translate key="submission.request"}</strong></td>
				<td width="20%" align="center"><strong>{translate key="submission.underway"}</strong></td>
				<td width="20%" align="center">
				{if !$disableEdit && !$layoutAssignment->getDateCompleted()}
				<form action="{$requestPageUrl}/completeAssignment/{$submission->getArticleId()}" method="post">
<input type="submit" value="{translate key="layoutEditor.article.complete"}" class="button">
</form>
				{else}
				<strong>{translate key="submission.complete"}</strong>
				{/if}
				</td>
				<td width="20%" align="center"><strong>{translate key="submission.thank"}</strong></td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"></td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateNotified()}
						{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort}
					{else}
						-
					{/if}
				</td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateUnderway()}
						{$layoutAssignment->getDateUnderway()|date_format:$dateFormatShort}
					{else}
						-
					{/if}
				</td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateCompleted()}
						{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort}
					{else}
						-
					{/if}
				</td>
				<td width="20%" align="center">
					{if $layoutAssignment->getDateAcknowledged()}
						{$layoutAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
					{else}
					-
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>&nbsp;&nbsp;</td>
				<td width="25%"><strong>{translate key="submission.layout.galleys"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="submission.layout.proof"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.file"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.updated"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.order"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.action"}</strong></td>
			</tr>
		</table>
	</td>
</tr>
{foreach name=galleys from=$submission->getGalleys() item=galley}
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td><span class="boldText">{$smarty.foreach.galleys.iteration}.</span></td>
				<td width="25%"><a href="{$requestPageUrl}/editGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}">{$galley->getLabel()}</a></td>
				<td width="15%" align="center"><a href="{$requestPageUrl}/proofGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" class="file">{translate key="common.view"}</a></td>
				<td width="15%" align="center"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galley->getFileId()}" class="file">{$galley->getFileName()}</a></td>
				<td width="15%" align="center">{$galley->getDateModified()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{if $disableEdit}&uarr;{else}<a href="{$requestPageUrl}/orderGalley?d=u&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}">&uarr;</a>{/if} {if $disableEdit}&darr;{else}<a href="{$requestPageUrl}/orderGalley?d=d&amp;articleId={$submission->getArticleId()}&amp;galleyId={$galley->getGalleyId()}">&darr;</a>{/if}</td>
				<td width="15%" align="center">
					{icon name="edit" disabled="$disableEdit" url="$requestPageUrl/editGalley/`$submission->getArticleId()`/`$galley->getGalleyId()`"}&nbsp;{if $disableEdit}{icon name="delete" disabled="true"}{else}<a href="{$requestPageUrl}/deleteGalley/{$submission->getArticleId()}/{$galley->getGalleyId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteGalley"}')" class="icon">{icon name="delete"}</a>{/if}
				</td>
			</tr>
			{if $galley->isHTMLGalley()}
			{assign var=galleyStyleFile value=$galley->getStyleFile()}
			<tr>
				<td></td>
				<td colspan="6">
					<span class="highlightText">{translate key="submission.layout.galleyStyle"}:</span>
					{if $galleyStyleFile}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galleyStyleFile->getFileId()}" class="file">{$galleyStyleFile->getFileName()}</a>
					{else}
					-
					{/if}
					&nbsp;&nbsp;
					<span class="highlightText">{translate key="submission.layout.galleyImages"}:</span>
				{foreach from=$galley->getImageFiles() item=galleyImageFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$galleyImageFile->getFileId()}" class="file">{$galleyImageFile->getFileName()}</a>
				{foreachelse}
				-
				{/foreach}
				</td>
			</tr>
			{/if}
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="center">
		<span class="boldText">{translate key="common.none"}</span>
	</td>
</tr>
{/foreach}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="right">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="25%" align="right">
					<span class="boldText">{translate key="submission.layout.newGalley"}:</span>
				</td>
				<td width="75%">
					<form method="post" action="{$requestPageUrl}/uploadGalley" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="galleyFile"{if $disableEdit} disabled="disabled"{/if} />
						<input type="submit" name="submit" value="{translate key="common.upload"}"{if $disableEdit} disabled="disabled"{/if} />
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>&nbsp;&nbsp;</td>
				<td width="40%"><strong>{translate key="submission.layout.supplementaryFiles"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.file"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.updated"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.order"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="common.action"}</strong></td>
			</tr>
		</table>
	</td>
</tr>
{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td><span class="boldText">{$smarty.foreach.suppFiles.iteration}.</span></td>
				<td width="40%"><a href="{$requestPageUrl}/editSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}">{$suppFile->getTitle()}</a></td>
				<td width="15%" align="center"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$suppFile->getFileId()}" class="file">{$suppFile->getFileName()}</a></td>
				<td width="15%" align="center">{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{if $disableEdit}&uarr;{else}<a href="{$requestPageUrl}/orderSuppFile?d=u&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}">&uarr;</a>{/if} {if $disableEdit}&darr;{else}<a href="{$requestPageUrl}/orderSuppFile?d=d&amp;articleId={$submission->getArticleId()}&amp;suppFileId={$suppFile->getSuppFileId()}">&darr;</a>{/if}</td>
				<td width="15%" align="center">
					{icon name="edit" disabled="$disableEdit" url="$requestPageUrl/editSuppFile/`$submission->getArticleId()`/`$suppFile->getSuppFileId()`"}&nbsp;{if $disableEdit}{icon name="delete" disabled="true"}{else}<a href="{$requestPageUrl}/deleteSuppFile/{$submission->getArticleId()}/{$suppFile->getSuppFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="icon">{icon name="delete"}</a>{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
{foreachelse}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="center">
		<span class="boldText">{translate key="common.none"}</span>
	</td>
</tr>
{/foreach}
<tr class="submissionRowAlt">
	<td class="submissionBox" align="right">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="25%" align="right">
					<span class="boldText">{translate key="submission.layout.newSupplementaryFile"}:</span>
				</td>
				<td width="75%">
					<form method="post" action="{$requestPageUrl}/uploadSuppFile" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<input type="file" name="uploadSuppFile"{if $disableEdit} disabled="disabled"{/if} />
						<input type="submit" name="submit" value="{translate key="common.upload"}"{if $disableEdit} disabled="disabled"{/if} />
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}');">{translate key="submission.layout.layoutComments"}</a>
		{if $submission->getMostRecentLayoutComment()}
			{assign var="comment" value=$submission->getMostRecentLayoutComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewLayoutComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
</tr>
</table>
</div>

<br />

<!-- START OF PROOFREADING -->
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.proofreading"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					{if $proofAssignment->getProofreaderId()}
						<span class="boldText">{translate key="user.role.proofreader"}:</span> {$proofAssignment->getProofreaderFullName()}
					{else}
						<span class="boldText">{translate key="user.role.proofreader"}:</span> {translate key="common.none"}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<!-- START AUTHOR COMMENTS -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
		<tr>
			<td width="55%"><span class="boldText">1. {translate key="editor.article.authorComments"}</td>
			<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
		</tr>
		<tr>
			<td width="55%">&nbsp;</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorNotified()}{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorUnderway()}{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorCompleted()}{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
		</tr>
		</table>
	</td>
</tr>
<!-- END AUTHOR COMMENTS -->
<!-- START PROOFREADER COMMENTS -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="55%"><span class="boldText">2. {translate key="editor.article.proofreaderComments"}</span></td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
			</tr>
			<tr>
				<td width="55%">&nbsp;</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderNotified()}{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderUnderway()}{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderCompleted()}{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END PROOFREADER COMMENTS -->
<!-- START LAYOUT EDITOR FINAL -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
		<tr>
			<td width="55%"><span class="boldText">3. {translate key="editor.article.layoutEditorFinal"}</td>
			<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
			<td align="center" width="15%">
				<form method="post" action="{$requestPageUrl}/layoutEditorProofreadingComplete">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
					<input type="submit" value="{translate key="submission.complete"}" {if not $proofAssignment->getDateLayoutEditorNotified() or $proofAssignment->getDateLayoutEditorCompleted()}disabled="disabled"{/if}>
				</form>			
			</td>
		</tr>
			<tr>
				<td width="55%">&nbsp;</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorNotified()}{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorUnderway()}{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END LAYOUT EDITOR FINAL -->
</table>
</div>
<!-- END OF PROOFREADING -->

<br />

{include file="common/footer.tpl"}
