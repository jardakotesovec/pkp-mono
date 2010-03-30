{**
 * submissionName.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission name cell for submissionsList grid
 *
 *}
{if $roleId == $smarty.const.ROLE_ID_AUTHOR}
	{url|assign:submissionUrl router=$smarty.const.ROUTE_PAGE page="author" op="submit" path=$submission->getSubmissionProgress() monographId=$submission->getMonographId()}
{elseif $roleId == $smarty.const.ROLE_ID_EDITOR}
	{url|assign:submissionUrl router=$smarty.const.ROUTE_PAGE page="editor" op="submission" path=$submission->getMonographId()}
{elseif $roleId == $smarty.const.ROLE_ID_REVIEWER}
	{url|assign:submissionUrl router=$smarty.const.ROUTE_PAGE page="reviewer" op="submission" path=$submission->getMonographId()}
{/if}

<a href="{$submissionUrl}" class="action">
{if $submission->getLocalizedTitle()}
	{$submission->getLocalizedTitle()|strip_unsafe_html|truncate:60:"..."}
{else}
	{translate key="common.untitled"}
{/if}</a>
