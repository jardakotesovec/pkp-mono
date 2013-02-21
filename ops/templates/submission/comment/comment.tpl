{**
 * templates/submission/comment/comment.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to enter comments.
 *
 *}
{strip}
{include file="submission/comment/header.tpl"}
{/strip}

<script>
{literal}
<!--
// In case this page is the result of a comment submit, reload the parent
// so that the necessary buttons will be activated.
window.opener.location.reload();
// -->
{/literal}
</script>
<div id="articleComments">
<table class="data">
{foreach from=$articleComments item=comment}
<tr>
	<td>
		<div class="commentRole">{translate key=$comment->getRoleName()}</div>
		<div class="commentDate">{$comment->getDatePosted()|date_format:$datetimeFormatShort}</div>
	</td>
	<td>
		{if $comment->getAuthorId() eq $userId and not $isLocked}
			<div style="float: right"><a href="{url op="editComment" path=$articleId|to_array:$comment->getId()}" class="action">{translate key="common.edit"}</a> <a href="{url op="deleteComment" path=$articleId|to_array:$comment->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.comments.confirmDelete"}')" class="action">{translate key="common.delete"}</a></div>
		{/if}
		<div id="{$comment->getId()}">
		{if $comment->getCommentTitle() neq ""}
			<div class="commentTitle">{translate key="submission.comments.subject"}: {$comment->getCommentTitle()|escape}</div>
		{/if}
		</div>
		<div class="comments">{$comment->getComments()|strip_unsafe_html|nl2br}</div>
	</td>
</tr>
{foreachelse}
<tr>
	<td class="nodata">{translate key="submission.comments.noComments"}</td>
</tr>
{/foreach}
</table>
</div>
<br />
<br />

{if not $isLocked}
<div id="postComment">
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#commentForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="commentForm" method="post" action="{url op=$commentAction}">
{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key|escape}" value="{$hiddenFormParam|escape}" />
	{/foreach}
{/if}


<div id="new">
{include file="common/formErrors.tpl"}

<table class="data">
<tr>
	<td class="label">{fieldLabel name="commentTitle" key="submission.comments.subject"}</td>
	<td class="value"><input type="text" name="commentTitle" id="commentTitle" value="{$commentTitle|escape}" size="50" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="comments" required="true" key="submission.comments.comments"}</td>
	<td class="value"><textarea id="comments" name="comments" rows="10" cols="50" class="textArea richContent">{$comments|escape}</textarea></td>
</tr>
</table>
<p><input type="submit" name="save" value="{translate key="common.save"}" class="button defaultButton" /> <input type="submit" name="saveAndEmail" value="{translate key="common.saveAndEmail"}" class="button" /> <input type="button" value="{translate key="common.close"}" class="button" onclick="window.close()" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>

</form>
</div>
{else}
<input type="button" value="{translate key="common.close"}" class="button defaultButton" style="width: 5em" onclick="window.close()" />
{/if}

{include file="submission/comment/footer.tpl"}

