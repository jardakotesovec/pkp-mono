{**
 * templates/controllers/grid/settings/reviewForms/editReviewForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The edit/preview a review form tabset.
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#editReviewFormTabs').pkpHandler(
				'$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="editReviewFormTabs">
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="reviewFormBasics" reviewFormId=$reviewFormId}">{translate key="manager.reviewForms.edit"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="reviewFormElements" reviewFormId=$reviewFormId}">{translate key="manager.reviewFormElements"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="reviewFormPreview" reviewFormId=$reviewFormId}">{translate key="manager.reviewForms.preview"}</a></li>
	</ul>
</div>
