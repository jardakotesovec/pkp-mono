{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer navigation sidebar.
 *
 * $Id$
 *}

<div class="block">
	<span class="blockTitle">{translate key="reviewer.journalReviewer"}</span>
	<span class="blockSubtitle">{translate key="article.submissions"}</span>
	<ul>
		<li><a href="{$pageUrl}/reviewer/index/active">{translate key="common.active"}&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</a></li>
		<li><a href="{$pageUrl}/reviewer/index/completed">{translate key="common.completed"}&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</a></li>
	</ul>
</div>
