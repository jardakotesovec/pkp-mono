/**
 * modal.js
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Implementation of jQuery modals for OMP.
 *
 * $Id:
 */

/**
 * modal
 * @param $url URL to load into the modal
 * @param $actType Type to define if callback should do (nothing|append|replace|remove)
 * @param $actOnId The ID on which to perform the action on callback
 * @param $apendTo Selector to append data to
 * @param $localizedButtons Array of translated 'Cancel/submit' strings
 * @param $callingButton Selector of the button that opens the modal
 * @param $dialogTitle Set a custom title for the dialog
 */
function modal(url, actType, actOnId, localizedButtons, callingButton, dialogTitle) {
	$(document).ready(function() {
		var validator = null;
		var title = dialogTitle ? dialogTitle : $(callingButton).text();
		var okButton = localizedButtons[0];
		var cancelButton = localizedButtons[1];
		var d = new Date();
		var UID = Math.ceil(1000 * Math.random(d.getTime()));
		var formContainer = '#' + UID;

		// Construct action to perform when OK and Cancels buttons are clicked
		var dialogOptions = {};
		if (actType == 'nothing') {
			// If the action type is 'nothing' then simply close the
			// dialog when the OK button is pressed. No cancel button
			// is needed.
			dialogOptions[okButton] = function() {
				$(this).dialog("close");
			};
		} else {
			// All other action types will assume that there is a
			// form to be posted and post it.
			dialogOptions[okButton] = function() {
				submitModalForm(formContainer, actType, actOnId);
			};
			dialogOptions[cancelButton] = function() {
				$(this).dialog("close");
			};
		}

		// Construct dialog
		var $dialog = $('<div id=' + UID + '></div>').dialog({
			title: title,
			autoOpen: false,
			width: 700,
			modal: true,
			draggable: false,
			resizable: false,
			position: ['center', 100],
			buttons: dialogOptions,
			open: function(event, ui) {
		        $(this).css({'max-height': 600, 'overflow-y': 'auto'}); 
				$.getJSON(url, function(jsonData) {
					$('#loading').hide();
					if (jsonData.status === true) {
						$('#' + UID).html(jsonData.content);
					} else {
						// Alert that the modal failed
						alert(jsonData.content);
					}
				});
				$(this).html("<div id='loading' class='throbber'></div>");
				$('#loading').show();
			},
			close: function() {
				// Reset form validation errors and inputs on close
				if (validator != null) {
					validator.resetForm();
				}
				clearFormFields($(formContainer).find('form'));
			}
		});

		// Tell the calling button to open this modal on click
		$(callingButton).die("click").live("click", (function() {
			$dialog.dialog('open');
			return false;
		}));

	});
}

/**
 * modal
 * @param $formContainer Container of form to be submitted, most likely a modal's ID (must include '#')
 * @param $actType Type to define if callback should do (nothing|append|replace|remove)
 * @param $actOnId The ID on which to perform the action on callback
 */
function submitModalForm(formContainer, actType, actOnId ) {
	$form = $(formContainer).find('form');
	validator = $form.validate();
	// Post to server and construct callback
	if ($form.valid()) {
		$.post(
			$form.attr("action"),
			$form.serialize(),
			function(jsonData) {
				if(jsonData.isScript == true) {
					eval(jsonData.script);
				}
				if (jsonData.status == true) {
					updateItem(actType, actOnId, jsonData.content);
					$(formContainer).dialog("close");
				} else {
					// If an error occurs then redisplay the form
					$(formContainer).html(jsonData.content);
				}
			},
			"json"
		);
		validator = null;
	}
}


/**
 * modalConfirm
 * @param $url URL to load into the modal
 * @param $actType Type to define if callback should do (nothing|append|replace|remove)
 * @param $actOnId The ID on which to perform the action on callback
 * @param $dialogText Text to display in the dialog
 * @param $localizedButtons Array of translated 'Cancel/submit' strings
 * @param $callingButton Selector of the button that opens the modal
 */
function modalConfirm(url, actType, actOnId, dialogText, localizedButtons, callingButton) {
	$(document).ready(function() {
		var title = $(callingButton).text(); // Assign title to calling button's text
		var okButton = localizedButtons[0];
		var cancelButton = localizedButtons[1];
		var d = new Date();
		var UID = Math.ceil(1000 * Math.random(d.getTime()));
		// Construct action to perform when OK and Cancels buttons are clicked
		var dialogOptions = {};
		if(url == null) {
			// Show a simple alert dialog (does not communicate with server)
			dialogOptions[okButton] = function() {
				$(this).dialog("close");
			};
		} else {
			dialogOptions[okButton] = function() {
				// Post to server and construct callback
				$.post(url, '', function(returnString) {
					if (returnString.status) {
						if(returnString.isScript) {
							eval(returnString.script);
						} else {
							updateItem(actType, actOnId, returnString.content);
						}
					} else {
						// Alert that the action failed
						confirm(null, null, null, returnString.content, localizedButtons, callingButton);
					}
				}, 'json');
				$('#'+UID).dialog("close");
			};
			dialogOptions[cancelButton] = function() {
				$(this).dialog("close");
			};
		}

		// Construct dialog
		var $dialog = $('<div id=' + UID + '>'+dialogText+'</div>').dialog({
			title: title,
			autoOpen: false,
			modal: true,
			draggable: false,
			buttons: dialogOptions
		});

		// Tell the calling button to open this modal on click
		$(callingButton).live("click", (function() {
			$dialog.dialog('open');
			return false;
		}));
	});
}
/**
 * alert - Display a simple alert dialog
 * @param $dialogText Text to display in the dialog
 * @param $localizedButtons Array of translated 'Cancel/submit' strings
 */
function modalAlert(dialogText, localizedButtons) {
		var localizedText = new Array();
		localizedText = localizedButtons.split(',');
		var okButton = localizedText[0];
		if (localizedText[1]) {
			var title = localizedText[1];
		} else {
			var title = "Alert";
		}
		var d = new Date();
		var UID = Math.ceil(1000 * Math.random(d.getTime()));

		// Construct action to perform when OK button is clicked
		var dialogOptions = {};
		dialogOptions[okButton] = function() {
			$(this).dialog("close");
		};

		// Construct dialog
		var $dialog = $('<div id=' + UID + '>'+dialogText+'</div>').dialog({
			title: title,
			autoOpen: false,
			modal: true,
			draggable: false,
			buttons: dialogOptions
		});

		$dialog.dialog('open');
		return false;

}

function changeModalFormLocale() {
	oldLocale = $("#currentLocale").val();
	newLocale = $("#formLocale").val();

	$("#currentLocale").val(newLocale);
	$("."+oldLocale).hide();
	$("."+newLocale).show("normal");
}

function clearFormFields(form) {
	$(':input', form).each(function() {
		if(!$(this).is('.static')) {
			switch(this.type) {
			case 'password':
			case 'select-multiple':
				case 'select-one':
				case 'text':
				case 'textarea':
					$(this).val('');
					break;
				case 'checkbox':
				case 'radio':
					this.checked = false;
			}
		}
	});
}

/**
 * ajaxAction
 * Implements an ajax action.
 * @param $actType can be either 'get' or 'post', 'post' expects a form as
 *  a child element of 'actOnId'.
 * @param $callingButton Selector of the button that initiates the ajax call
 * @param $url the url to be called, defaults to the form action in case of
 *  action type 'post'.
 * @param $data (post action type only) the data to be posted, defaults to
 *  the form data.
 */
function ajaxAction(actType, actOnId, callingButton, url, data) {
	if (actType == 'post') {
		clickAction = function() {
			$form = $('#' + actOnId).find('form');

			// Default url and data
			if (!url) {
				postUrl = $form.attr("action");
			} else {
				postUrl = url;
			}
			if (!data) {
				postData = $form.serialize();
			} else {
				postData = data;
			}

			// Validate
			validator = $form.validate();
			var d = new Date();
			var UID = Math.ceil(1000 * Math.random(d.getTime()));
			var $throbberDialog = $('<div></div>').html('<div class="throbber" id="' + UID + '"></div>').dialog( {title: $(callingButton).text(), draggable: false, width: 600, autoOpen: false, modal: true, position: 'center'} );
			$('#' + UID).show();

			// Post to server and construct callback
			if ($form.valid()) {
				$throbberDialog.dialog('open');
				$.post(
					postUrl,
					postData,
					function(jsonData) {
						$throbberDialog.dialog('close');
						// An AJAX action will always return content that
						// replaces the original content independent of whether
						// an error occured or not.
						$('#' + actOnId).replaceWith(jsonData.content);
					},
					'json'
				);
				validator = null;
			}
		};
	} else {
		clickAction = function() {
			$.get(
				url,
				function(jsonData) {
					if (jsonData.status === true) {
						$('#' + actOnId).replaceWith(returnString.content);
					} else {
						// Alert that the action failed
						alert(jsonData.content);
					}
				},
				'json'
			);
		};
	}

	$(document).ready(function() {
		$(callingButton).unbind('click').bind('click', clickAction);
	});
}

function updateItem(actType, actOnId, content) {
	switch (actType) {
		case 'append':
			$empty = $('#' + actOnId).find('.empty');
			$empty.hide();
			$('#' + actOnId).append(content);
			break;
		case 'replace':
			$('#' + actOnId).replaceWith(content);
			break;
		case 'remove':
			if ($('#' + actOnId).siblings().length == 0) {
				deleteElementById(actOnId, true);
			} else {
				deleteElementById(actOnId);
			}
			break;
	}
}

function deleteElementById(elementId, showEmpty) {
	var $emptyRow = $('#' + elementId).parent().siblings('.empty');
	$("#"+elementId).fadeOut(500, function() {
		$(this).remove();
		if (showEmpty) {
			$emptyRow.fadeIn(500);
		}
	});
}

function saveAndUpdate(url, actOnType, actOnId, tabContainer, reopen) {
	$.post(url, null, function(returnString) {
		if (returnString.status == true) {
			updateItem(actOnType, actOnId, returnString.content);
			$(tabContainer).parent().dialog('close');
			if (reopen == true) {
				$(tabContainer).parent().dialog('open');
			}
		} else {
			// Display errors in error list
			$('#formErrors .formErrorList').html(returnString.content);
		}
	}, "json");
}
