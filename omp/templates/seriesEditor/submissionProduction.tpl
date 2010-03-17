{**
 * submissionProduction.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission production.
 *
 * $Id$
 *}

{include file="seriesEditor/submission/summary.tpl"}

<div class="separator"></div>

{if $currentProcess != null and $currentProcess->getProcessId() == WORKFLOW_PROCESS_EDITING_COPYEDIT}

{include file="seriesEditor/submission/production.tpl"}

<div class="separator"></div>
{else}

<em>Production not available</em>

{/if}

