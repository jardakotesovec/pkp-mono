{**
 * templates/controllers/grid/issues/issueGalleys.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for uploading and editing issue galleys
 *}

{url|assign:issueGalleysGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.issueGalleys.IssueGalleyGridHandler" op="fetchGrid" issueId=$issueId}
{load_url_in_div id="IssueGalleysGridContainer" url=$issueGalleysGridUrl}
