{**
 * templates/dashboard/active.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard active submissions tab.
 *}

{help file="chapter3/all-active.md" class="pkp_helpers_align_right"}
<div class="pkp_helpers_clear"></div>

<!-- Archived submissions grid: Show all archived submissions -->
{url|assign:activeSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.activeSubmissions.ActiveSubmissionsListGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="activeSubmissionsListGridContainer" url=$activeSubmissionsListGridUrl}
