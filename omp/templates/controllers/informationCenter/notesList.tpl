{**
 * templates/controllers/informationCenter/notesList.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display submission file note list in information center.
 *}

<div id="{$notesListId}">
	{iterate from=notes item=note}
		{assign var=noteId value=$note->getId()}
		{if $noteFilesDownloadLink && isset($noteFilesDownloadLink[$noteId])}
			{assign var=downloadLink value=$noteFilesDownloadLink[$noteId]}
		{else}
			{assign var=downloadLink value=0}
		{/if}
		{include file="controllers/informationCenter/note.tpl" noteFileDownloadLink=$downloadLink}
		{$note->markViewed($currentUserId)}
	{/iterate}
	{if $notes->wasEmpty()}
		<p>{translate key="informationCenter.noNotes"}</p>
	{/if}
</div>
