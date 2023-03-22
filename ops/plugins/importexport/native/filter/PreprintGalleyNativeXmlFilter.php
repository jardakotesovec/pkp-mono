<?php

/**
 * @file plugins/importexport/native/filter/PreprintGalleyNativeXmlFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreprintGalleyNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Galley to a Native XML document.
 */

namespace APP\plugins\importexport\native\filter;

use APP\facades\Repo;

class PreprintGalleyNativeXmlFilter extends \PKP\plugins\importexport\native\filter\RepresentationNativeXmlFilter
{
    //
    // Extend functions in RepresentationNativeXmlFilter
    //
    /**
     * Create and return a representation node. Extend the parent class
     * with publication format specific data.
     *
     * @param DOMDocument $doc
     * @param Representation $representation
     *
     * @return DOMElement
     */
    public function createRepresentationNode($doc, $representation)
    {
        $representationNode = parent::createRepresentationNode($doc, $representation);
        $representationNode->setAttribute('approved', $representation->getIsApproved() ? 'true' : 'false');

        return $representationNode;
    }

    /**
     * Get the available submission files for a representation
     *
     * @param Representation $representation
     *
     * @return array
     */
    public function getFiles($representation)
    {
        if ($representation->getData('submissionFileId')) {
            return [
                Repo::submissionFile()->get($representation->getData('submissionFileId'))
            ];
        }

        return [];
    }
}
