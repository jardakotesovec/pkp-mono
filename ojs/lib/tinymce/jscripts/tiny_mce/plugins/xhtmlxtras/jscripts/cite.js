 /**
 * $Id$
 *
 * @author Moxiecode - based on work by Andrew Tetlaw
 * @copyright Copyright � 2004-2007, Moxiecode Systems AB, All rights reserved.
 */

function preinit() {
	// Initialize
	tinyMCE.setWindowArg('mce_windowresize', false);
}

function init() {
	tinyMCEPopup.resizeToInnerSize();
	SXE.initElementDialog('cite');
	if (SXE.currentAction == "update") {
		SXE.showRemoveButton();
	}
}

function insertCite() {
	SXE.insertElement('cite');
	tinyMCEPopup.close();
}

function removeCite() {
	SXE.removeElement('cite');
	tinyMCEPopup.close();
}