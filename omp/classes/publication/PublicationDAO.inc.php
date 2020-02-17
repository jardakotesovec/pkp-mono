<?php
/**
 * @file classes/publication/PublicationDAO.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationDAO
 * @ingroup core
 * @see DAO
 *
 * @brief Add OMP-specific functions for PKPPublicationDAO
 */

import('lib.pkp.classes.publication.PKPPublicationDAO');
class PublicationDAO extends PKPPublicationDAO {

	/** @copydoc SchemaDao::$primaryTableColumns */
	public $primaryTableColumns = [
		'id' => 'publication_id',
		'accessStatus' => 'access_status',
		'datePublished' => 'date_published',
		'lastModified' => 'last_modified',
		'locale' => 'locale',
		'primaryContactId' => 'primary_contact_id',
		'seq' => 'seq',
		'seriesId' => 'series_id',
		'submissionId' => 'submission_id',
		'status' => 'status',
		'urlPath' => 'url_path',
		'version' => 'version',
	];

	/**
	 * @copydoc SchemaDAO::_fromRow()
	 */
	public function _fromRow($primaryRow) {
		$publication = parent::_fromRow($primaryRow);

		$publication->setData('publicationFormats', Application::getRepresentationDao()->getByPublicationId($publication->getId())->toArray());
		$publication->setData('chapters', DAORegistry::getDAO('ChapterDAO')->getByPublicationId($publication->getId())->toArray());

		return $publication;
	}
}