{**
 * navsidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Proofreader navigation sidebar.
 *
 * $Id$
 *}

<div class="sidebarBlockTitle">{translate key="proofreader.proofreaderProcess"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="submission.submissions"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}/proofreader/index/active">{translate key="common.active"}{if $submissionsCount[0]}&nbsp;({$submissionsCount[0]}){/if}</a></li>
	<li><a href="{$pageUrl}/proofreader/index/completed">{translate key="common.completed"}{if $submissionsCount[1]}&nbsp;({$submissionsCount[1]}){/if}</a></li>
</ul>
</div>
