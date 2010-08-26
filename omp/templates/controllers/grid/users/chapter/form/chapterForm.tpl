<!-- templates/controllers/grid/users/chapter/form/chapterForm.tpl -->

{**
 * chapters.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Chapters grid form
 *
 *}
{assign var='timeStamp' value=$smarty.now}
{modal_title id="#editChapterForm-$timeStamp" key="submission.chapter.addChapter" iconClass="fileManagement" canClose=1}

<form name="editChapterForm" id="editChapterForm-{$timeStamp}" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.users.chapter.ChapterGridHandler" op="updateChapter"}">
{include file="common/formErrors.tpl"}

{fbvFormSection title="common.title" for="title"}
	{fbvElement type="text" name="title[en_US]" id="title" value=$title maxlength="255" size=$fbvStyles.size.LARGE}
{/fbvFormSection}

<input type="hidden" name="monographId" value="{$monographId|escape}" />
{if $chapterId}
	<input type="hidden" name="chapterId" value="{$chapterId|escape}" />

	{* only show the contributor list builder if the chapter already exists *}
	<!--  Chapter Contributors -->
	{** FIXME: can remove escape=false after fix of bug 5265 **}
	{url|assign:chapterContributorUrl router=$smarty.const.ROUTE_COMPONENT  component="listbuilder.users.ChapterContributorListbuilderHandler" op="fetch" monographId=$monographId chapterId=$chapterId escape=false}
	{assign var='timeStamp' value=$smarty.now}
	{load_url_in_div id="chapterContributorContainer-$timeStamp" url=$chapterContributorUrl}
{/if}
</form>

{init_button_bar id="#editChapterForm-$timeStamp" cancelId="#cancelButton-$timeStamp" submitId="#okButton-$timeStamp"}
{fbvFormArea id="buttons"}
    {fbvFormSection}
        {fbvLink id="cancelButton-$timeStamp" label="common.cancel"}
        {fbvButton id="okButton-$timeStamp" label="submission.chapter.addChapter" align=$fbvStyles.align.RIGHT}
    {/fbvFormSection}
{/fbvFormArea}
<!-- / templates/controllers/grid/users/chapter/form/chapterForm.tpl -->

