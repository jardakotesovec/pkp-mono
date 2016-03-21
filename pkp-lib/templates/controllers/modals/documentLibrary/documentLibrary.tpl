{**
 * templates/controllers/modals/documentLibrary/documentLibrary.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Document library
 *}

{help file="chapter5/submission-library.md" class="pkp_help_modal"}

{url|assign:submissionLibraryGridUrl submissionId=$submission->getId() router=$smarty.const.ROUTE_COMPONENT component="grid.files.submissionDocuments.SubmissionDocumentsFilesGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="submissionLibraryGridContainer" url=$submissionLibraryGridUrl}
