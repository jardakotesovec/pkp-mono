{**
 * controllers/fileUploadContainer.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Markup for file uploader widget.
 *}

<div id="{$id}" class="pkp_controller_fileUpload loading">
	<div class="pkp_uploader_loading">
		{**
		 * This wrapper div is a hack to emulate the inPlaceNotification.tpl
		 * structure. There's currently not a way to use these notifications
		 * without loading the JavaScript handler, but in this case we don't
		 * have the required settings.
		 *}
		<div class="pkp_notification">
			{translate|assign:"warningMessage" key="common.fileUploaderError"}
			{translate|assign:"warningTitle" key="common.warning"}
			{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=$id
				notificationStyleClass="notifyWarning" notificationContents=$warningMessage}
		</div>
	</div>

	{* The file upload and drag-and-drop area *}
	<div id="pkpUploaderDropZone" class="pkp_uploader_drop_zone">

		<div class="pkp_uploader_drop_zone_label">
			{translate key="submission.dragFile"}
		</div>

		<div class="pkp_uploader_details">
			<span class="pkpUploaderProgress">
				{translate key="common.percentage" percentage='<span class="percentage">0</span>'}
			</span>{* Live progress (%) *}
			<div class="pkp_uploader_progress_bar_wrapper">
				<span class="pkpUploaderProgressBar"></span>{* Live progress bar*}
			</div>
			<span class="pkpUploaderFilename"></span>{* Uploaded file name *}
		</div>

		{* Placeholder for errors during upload *}
		<div class="pkpUploaderError"></div>

		{* Button to add/change file *}
		<button id="pkpUploaderButton" class="pkp_uploader_button pkp_button">
			<span class="pkp_uploader_button_add">
				{translate key="submission.addFile"}
			</span>
			<span class="pkp_uploader_button_change">
				{translate key="submission.changeFile"}
			</span>
		</button>
	</div>
</div>
