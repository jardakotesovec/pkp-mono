<?php

/**
 * @file classes/note/Note.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Note
 * @ingroup note
 * @see NoteDAO
 *
 * @brief Class for OJS Note.
 */

import('classes.article.ArticleFile');
import('lib.pkp.classes.note.PKPNote');

class Note extends PKPNote {
	/**
	 * Constructor.
	 */
	function Note() {
		parent::PKPNote();
	}

	/**
	 * get file
	 * @return string
	 */
	function getFile() {
		return $this->getData('file');
	}

	/**
	 * set note
	 * @param $note string
	 */
	function setFile($file) {
		return $this->setData('file', $file);
	}

	/**
	 * Get the original filename.
	 * @return string
	 */
	function getOriginalFileName() {
		$file = $this->getFile();
		return $file->getOriginalFileName();
	}
}

?>
