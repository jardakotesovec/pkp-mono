<?php

/**
 * @file classes/publication/PublicationDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationDAO
 * @ingroup core
 *
 * @see DAO
 *
 * @brief Add OPS-specific functions for PKPPublicationDAO
 */

namespace APP\publication;

use \PKP\publication\PKPPublicationDAO;

use \APP\core\Services;

class PublicationDAO extends PKPPublicationDAO
{
    /** @copydoc SchemaDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'publication_id',
        'accessStatus' => 'access_status',
        'datePublished' => 'date_published',
        'lastModified' => 'last_modified',
        'primaryContactId' => 'primary_contact_id',
        'sectionId' => 'section_id',
        'submissionId' => 'submission_id',
        'status' => 'status',
        'urlPath' => 'url_path',
        'version' => 'version',
    ];

    /**
     * @copydoc SchemaDAO::_fromRow()
     */
    public function _fromRow($primaryRow)
    {
        $publication = parent::_fromRow($primaryRow);
        $publication->setData('galleys', iterator_to_array(
            Services::get('galley')->getMany(['publicationIds' => $publication->getId()])
        ));
        return $publication;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\publication\PublicationDAO', '\PublicationDAO');
}

