{**
 * submissionCitations.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission citations.
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.citations" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.citations"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li class="current"><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

{* JavaScript - FIXME: will be moved to JS file as soon as development is done *}
{literal}
<script type="text/javascript">
	$(function() {
		// Vertical splitter.
		$('#citationEditorCanvas').splitter({
			splitVertical:true,
			A:$('#citationEditorNavPane'),
			minAsize:200,
			B:$('#citationEditorDetailPane'),
			minBsize:300
		});

		// Main tabs.
		$mainTabs = $('#citationEditorMainTabs').tabs({
			show: function(e, ui) {
				// Make sure the citation editor is correctly sized when
				// opened for the first time.
				if (ui.panel.id == 'citationEditorTabEdit') {
					$('#citationEditorCanvas').triggerHandler('resize');
				}
				{/literal}{if !$citationEditorConfigurationError}{literal}
					if (ui.panel.id == 'citationEditorTabExport') {
						$('#citationEditorExportPane').html('<div id="citationEditorExportThrobber" class="throbber"></div>');
						$('#citationEditorExportThrobber').show();
						
						// Re-load export tab whenever it is shown.
						$.getJSON('{/literal}{$citationExportUrl}{literal}', function(jsonData) {
							if (jsonData.status === true) {
								$("#citationEditorExportPane").html(jsonData.content);
							} else {
								// Alert that loading failed
								alert(jsonData.content);
							}
						});
					}
				{/literal}{/if}{literal}
			}
		});

		{/literal}{if !$introductionHide}{literal}
			// Feature to disable introduction message.
			$('#introductionHide').change(function() {
				$.getJSON(
					'{/literal}{url router=$smarty.const.ROUTE_COMPONENT component="api.user.UserApiHandler" op="setUserSetting"}{literal}?setting-name=citation-editor-hide-intro&setting-value='+($(this).attr('checked')===true ? 'true' : 'false'),
					function(jsonData) {
						if (jsonData.status !== true) {
							alert(jsonData.content);
						}
					}
				);
			});
		{/literal}{/if}{literal}

		{/literal}{if $citationEditorConfigurationError}{literal}
			// Disable editor when not properly configured.
			$mainTabs.tabs('option', 'disabled', [1, 2]);
		{/literal}{/if}{literal}

		// Throbber feature (binds to ajaxAction()'s 'actionStart' event).
		actionThrobber('#citationEditorDetailCanvas');

		// Fullscreen feature.
		var $citationEditor = $('#submissionCitations');
		var beforeFullscreen;
		$('#fullScreenButton').click(function() {
			if ($citationEditor.hasClass('fullscreen')) {
				// Going back to normal: Restore saved values.
				$citationEditor.removeClass('fullscreen');
				$('.composite-ui>.ui-tabs').css('margin-top', beforeFullscreen.topMargin);
				$('.composite-ui div.main-tabs>.canvas').each(function() {
					$(this).css('height', beforeFullscreen.height);
				});
				$('.composite-ui div.two-pane>div.left-pane .scrollable').first().css('height', beforeFullscreen.navHeight);
				
				$('body').css('overflow', 'auto');
				window.scroll(beforeFullscreen.x, beforeFullscreen.y);
				$(this).text('{/literal}{translate key="common.fullscreen"}{literal}');
			} else {
				// Going fullscreen:
				// 1) Save current values.
				beforeFullscreen = {
					topMargin: $('.composite-ui>.ui-tabs').css('margin-top'),
					height: $('.composite-ui div.main-tabs>.canvas').first().css('height'),
					navHeight: $('.composite-ui div.two-pane>div.left-pane tbody').first().css('height'),
					x: $(window).scrollLeft(),
					y: $(window).scrollTop()
				};
		
				// 2) Set values needed to go fullscreen.
				$('body').css('overflow', 'hidden');
				$citationEditor.addClass('fullscreen');
				$('.composite-ui>.ui-tabs').css('margin-top', '0');
				canvasHeight=$(window).height()-$('ul.main-tabs').height();
				$('.composite-ui div.main-tabs>.canvas').each(function() {
					$(this).css('height', canvasHeight+'px');
				});
				$('.composite-ui div.two-pane>div.left-pane .scrollable').first().css('height', (canvasHeight-30)+'px');
				window.scroll(0,0);
				$(this).text('{/literal}{translate key="common.fullscreenOff"}{literal}');
			}

			// Resize 2-pane layout.
			$('.two-pane').css('width', '100%').triggerHandler('resize');
		});

		// Resize citation editor in fullscreen mode
		// when the browser window is being resized.
		$(window).resize(function() {
			canvasHeight=$(window).height()-$('ul.main-tabs').height();
			if ($citationEditor.hasClass('fullscreen')) {
				$('div.main-tabs>.canvas').each(function() {
					$(this).css('height', canvasHeight+'px');
				});
				$('.composite-ui div.two-pane>div.left-pane .scrollable').first().css('height', (canvasHeight-30)+'px');
			}
		});
	});
</script>
{/literal}

{* CSS - FIXME: will be moved to JS file as soon as development is done *}
{literal}
<style type="text/css">
	/* Composite UI: main tabs */
	.composite-ui>.ui-tabs {
		margin-top: 20px;
		padding: 0;
		border: 0 none;
	}
	
	.composite-ui>.ui-tabs ul.main-tabs {
		background: none #FBFBF3;
		border: 0 none;
		padding: 0;
	}

	.composite-ui>.ui-tabs ul.main-tabs li.ui-tabs-selected a {
		color: #555555;
	}

	.composite-ui>.ui-tabs ul.main-tabs li.ui-tabs-selected {
		padding-bottom: 2px;
		background: none #CED7E1;
	}
	
	.composite-ui>.ui-tabs ul.main-tabs a {
		color: #CCCCCC;
		font-size: 1.5em;
		padding: 0.2em 3em;
	}
	
	.composite-ui>.ui-tabs div.main-tabs {
		padding: 0;
		padding: 0;
	}

	/* Composite UI: canvas and pane */
	.composite-ui div.canvas {
		margin: 0;
		padding: 0;
		background-color:#EFEFEF;
		width: 100%;
	}
	
	.composite-ui div.pane {
		border: 1px solid #B6C9D5;
		background-color: #EFEFEF;
		height: 100%;
	}
	
	.composite-ui div.pane div.wrapper {
		padding: 30px;
	}
	
	.composite-ui .scrollable {
		overflow-y: auto;
		overflow-x: hidden;
	}
	
	/* Composite UI: fullscreen support */
	.fullscreen {
		display: block;
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 999;
		margin: 0;
		padding: 0;
		background: inherit;
		font-size: 120%;
	}

	#fullScreenButton {
		float: right;
		margin-top: 5px;
	}
	
	/* Composite UI: generic help or info message */
	.composite-ui div.pane div.help-message {
		margin: 20px 40px 40px 40px;
		padding-left: 30px;
		/* FIXME: change path when moving this to its own file */
		background: transparent url("../../../../lib/pkp/templates/images/icons/alert.gif") no-repeat;
	}

	/* Composite UI: text pane layout */
	.composite-ui div.canvas>div.text-pane {
		background-color: #CED7E1;
		padding-top: 30px;
	}
	
	/* Composite UI: grids as sub-components */
	.composite-ui div.grid table {
		border: 0 none;
	}

	.composite-ui div.grid th .options {
		margin: 0;
	}
	
	.composite-ui div.grid th .options a {
		margin: 0;
	}
	
	.composite-ui div.grid td {
		border-bottom: 1px solid #B6C9D5;
	}
	
	/* Composite UI: 2-pane layout */
	.composite-ui div.two-pane table.pane_header {
		width: 100%;
	}
	
	.composite-ui div.two-pane table.pane_header th {
		padding: 4px;
		height: 30px;
		background-color: #CED7E1;
		color: #20538D;
		vertical-align: middle;
	}
	
	.composite-ui div.two-pane>div.left-pane,
	.composite-ui div.two-pane>div.right-pane {
		float: left;
	}

	.composite-ui div.two-pane>div.left-pane {
		width: 25%;
	}
	
	/* Composite UI: 2-pane layout - navigation list */
	.composite-ui div.two-pane>div.left-pane div.grid div.row_container {
		background-color: #FFFFFF;
	}
	
	.composite-ui div.two-pane>div.left-pane div.grid div.clickable-row:hover,
	.composite-ui div.two-pane>div.left-pane div.grid div.clickable-row:hover div.row_file {
		background-color: #B6C9D5;
		cursor: pointer;
		text-decoration: underline:
	}
	
	.composite-ui div.two-pane>div.left-pane div.grid tr.approved-citation .row_container {
		border-left: 3px solid #20538D;
		padding-left: 22px;
	}
	
	.composite-ui div.two-pane>div.left-pane div.grid tr.approved-citation .row_actions {
		width: 22px;
	}

	/* Composite UI: 2-pane layout - splitbar */
	.composite-ui div.two-pane>div.splitbarV {
		float: left;
		width: 6px;
		height: 100%;
		line-height: 0;
		font-size: 0;
		border-top: solid 1px #9cbdff;
		border-bottom: solid 1px #9cbdff;
		/* FIXME: change path when moving this to its own file */
		background: #cbe1fb url(../../../../lib/pkp/styles/splitter/ui-bg_pane.gif) 0% 50%;
	}

	.composite-ui div.two-pane>div.splitbarV.working,
	.composite-ui div.two-pane>div.splitbuttonV.working {
		 -moz-opacity: .50;
		 filter: alpha(opacity=50);
		 opacity: .50;
	}

	/* Composite UI: 2-pane layout - detail editor */
	.composite-ui div.two-pane>div.right-pane {
		position: relative;
	}
	
	.composite-ui div.two-pane>div.right-pane div.wrapper {
		position: absolute;
		top: 30px;
		bottom: 60px;
		padding-top: 10px;
		padding-bottom: 0;
	}
	
	.composite-ui div.two-pane>div.right-pane div.pane_actions {
		width: 100%;
		position: absolute;
		margin: 0px;
		left: 0;
		bottom: 0;
	}
	
	.composite-ui div.two-pane>div.right-pane div.pane_actions>div {
		padding: 20px 30px;
	}
	
	.composite-ui div.two-pane>div.right-pane div.pane_actions button {
		float: right;
	}

	.composite-ui div.two-pane>div.right-pane div.pane_actions button.secondary-button {
		float: none;
	}
	
	.composite-ui div.two-pane>div.right-pane .form-block {
		margin-bottom: 40px;
		clear: both;
	}
	
	/* Composite UI: 2-pane layout - detail editor grids */
	.composite-ui div.two-pane>div.right-pane div.grid table {
		border-top: 1px solid #B6C9D5;
	}
	
	.composite-ui div.two-pane>div.right-pane div.grid td,
	.composite-ui div.two-pane>div.right-pane div.grid .row_actions,
	.composite-ui div.two-pane>div.right-pane div.grid .row_file {
		height: auto;
		min-height: 0;
		line-height: 1em;
		text-align: left;
	}
	
	.composite-ui div.two-pane>div.right-pane div.grid .row_container {
		background-color: #FFFFFF;
		padding-right: 30px;
		padding-right: 5px;
	}

	.composite-ui div.two-pane>div.right-pane div.grid .row_actions {
		right: 26px;
	}

	.composite-ui div.two-pane>div.right-pane div.grid .row_file {
		width: auto;
		padding: 0;
	}
	
	/* Citation editor: editor height */
	#submissionCitations.composite-ui div.main-tabs>.canvas {
		height: 600px;
	}
	
	/* Citation editor: citation list */
	#submissionCitations.composite-ui div.two-pane>div.left-pane div.grid .scrollable {
		/* The overflow definition should be in the generic styles part
		   but as the height is citation editor specific we better define
		   the overflow here to make the connection more obvious. */
		height: 570px; /* This is necessary for tbody overflow to work. */
	}

	.composite-ui div.two-pane>div.left-pane div.grid tr.current-item div.row_file,
	.composite-ui div.two-pane>div.left-pane div.grid tr.current-item div.row_container {
		background-color: #B6C9D5;
	}

	/* Citation editor: citation detail editor - before/after fields */
	#submissionCitations.composite-ui div.two-pane>div.right-pane .citation-comparison {
		margin-bottom: 10px;
	}
		
	#submissionCitations.composite-ui div.two-pane>div.right-pane .citation-comparison div.value {
		border: 1px solid #AAAAAA;
		padding: 5px;
		background-color: #FFFFFF;
	}
	
	#rawCitationWithMarkup .actions, #editableRawCitation .actions {
		float: right;
	}
		
	#editableRawCitation div.value {
		margin-right: 15px;  // FIXME: check for box model bug in IE
	}
		
	#editableRawCitation textarea.textarea {
		width: 100%;
		padding: 5px;
	}
	
	#editableRawCitation .options-head .ui-icon {
		float: left;
	}
	
	#editableRawCitation .option-block {
		margin-bottom: 10px;
		padding-left: 30px;
	}
	
	#editableRawCitation .option-block p {
		margin: 5px 0 0 0;
	}
	
	#editableRawCitation .option-block-option {
		float: left;
		margin-left: 5px;
	}

	#editableRawCitation .clear {
		clear: both;
	}
	
	#rawCitationWithMarkup div.value {
		margin-right: 25px;
	}
	
	#rawCitationWithMarkup a {
		display: block;
		width: 14px;
		height: 14px;
		margin-top: 1em;
		margin-left: 0; 
	}
		
	#generatedCitationWithMarkup span {
		cursor: default;
	}
	
	#submissionCitations.composite-ui div.two-pane>div.right-pane .citation-comparison span,
	#editableRawCitation textarea.textarea {
		font-size: 1.3em;
	}
	
	#submissionCitations.composite-ui div.two-pane>div.right-pane .citation-comparison-deletion {
		color: red;
		text-decoration: line-through;
	}
		
	#submissionCitations.composite-ui div.two-pane>div.right-pane .citation-comparison-addition {
		color: green;
		text-decoration: underline;
	}
	
	#citationFormErrorsAndComparison .throbber {
		height: 150px;
	}
	
	/* Citation fields */
</style>
{/literal}

<div id="submissionCitations" class="composite-ui">
	<div id="citationEditorMainTabs">
		<button id="fullScreenButton" type="button">{translate key="common.fullscreen"}</button>
		<ul class="main-tabs">
			{if !$introductionHide}<li><a href="#citationEditorTabIntroduction">{translate key="submission.citations.editor.introduction"}</a></li>{/if}
			<li><a href="#citationEditorTabEdit">{translate key="submission.citations.editor.edit"}</a></li>
			<li><a href="#citationEditorTabExport">{translate key="submission.citations.editor.export"}</a></li>
		</ul>
		{if !$introductionHide}
			<div id="citationEditorTabIntroduction" class="main-tabs">
				<div id="citationEditorIntroductionCanvas" class="canvas">
					<div id="citationEditorIntroductionPane" class="pane text-pane">
						<div class="help-message">
							{if $citationEditorConfigurationError}
								{capture assign="citationSetupUrl"}{url page="manager" op="setup" path="3" anchor="metaCitationEditing"}{/capture}
								{translate key=$citationEditorConfigurationError citationSetupUrl=$citationSetupUrl}
							{else}
								{translate key="submission.citations.editor.introduction.introductionMessage"}
								<input id="introductionHide" type="checkbox" >Don't show this message again.</input>
							{/if}
						</div>
					</div>
				</div>
			</div>
		{/if}
		<div id="citationEditorTabEdit" class="main-tabs">
			<div id="citationEditorCanvas" class="canvas two-pane">
				<div id="citationEditorNavPane" class="pane left-pane">
					{if !$citationEditorConfigurationError}
						{load_url_in_div id="#citationGridContainer" loadMessageId="submission.citations.editor.loadMessage" url="$citationGridUrl"}
					{/if}
				</div>
				<div id="citationEditorDetailPane" class="pane right-pane">
					<table class="pane_header"><thead><tr><th>&nbsp;</th></tr></thead></table>
					<div id="citationEditorDetailCanvas" class="canvas">
						<div class="wrapper scrollable">
							<div class="help-message">{$initialHelpMessage}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="citationEditorTabExport" class="main-tabs">
			<div id="citationEditorExportCanvas" class="canvas">
				<div id="citationEditorExportPane" class="pane text-pane"></div>
			</div>
		</div>
	</div>
</div>

{include file="common/footer.tpl"}
