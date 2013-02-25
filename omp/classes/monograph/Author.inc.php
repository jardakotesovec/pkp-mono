<?php

/**
 * @file classes/monograph/Author.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Author
 * @ingroup monograph
 * @see AuthorDAO
 *
 * @brief Monograph author metadata class.
 */


import('lib.pkp.classes.submission.PKPAuthor');

class Author extends PKPAuthor {
	/**
	 * Constructor.
	 */
	function Author() {
		parent::PKPAuthor();
	}

	//
	// Get/set methods
	//

	/**
	 * Return the published monograph associated with this author.
	 * @return PublishedMonograph
	 */
	function &getPublishedMonograph() {
		$monographId = $this->getSubmissionId();
		$publishedMonographDao =& DAORegistry::getDAO('PublishedMonographDAO');
		return $publishedMonographDao->getById($monographId);
	}
}

?>
