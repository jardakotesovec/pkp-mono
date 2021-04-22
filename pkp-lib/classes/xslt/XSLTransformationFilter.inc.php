<?php

/**
 * @file classes/xslt/XSLTransformationFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class XSLTransformationFilter
 * @ingroup xslt
 *
 * @brief Class that transforms XML via XSL.
 */

namespace PKP\xslt;

import('lib.pkp.classes.filter.PersistableFilter');

class XSLTransformationFilter extends PersistableFilter
{
    /**
     * Constructor
     *
     * @param $filterGroup FilterGroup
     * @param $displayName string
     *
     * NB: The input side of the transformation must always
     * be an XML format. See the XMLTypeDescription class for
     * more details how to enable XML validation.
     */
    public function __construct($filterGroup, $displayName = 'XSL Transformation')
    {
        // Check that we only get xml input, the output type is arbitrary.
        if (!substr($filterGroup->getInputType(), 0, 5) == 'xml::') {
            fatalError('XSL filters need XML as input.');
        }

        // Instantiate the settings of this filter
        import('lib.pkp.classes.filter.FilterSetting');
        $this->addSetting(new FilterSetting('xsl', null, null));
        $this->addSetting(new FilterSetting('xslType', null, null));
        $this->addSetting(new FilterSetting('resultType', null, null, FORM_VALIDATOR_OPTIONAL_VALUE));

        $this->setDisplayName($displayName);

        parent::__construct($filterGroup);
    }


    //
    // Getters and Setters
    //
    /**
     * Get the XSL
     *
     * @return DOMDocument|string a document, xsl string or file name
     */
    public function &getXSL()
    {
        return $this->getData('xsl');
    }

    /**
     * Get the XSL Type
     *
     * @return integer
     */
    public function getXSLType()
    {
        return $this->getData('xslType');
    }

    /**
     * Set the XSL
     *
     * @param $xsl DOMDocument|string
     */
    public function setXSL(&$xsl)
    {
        // Determine the xsl type
        if (is_string($xsl)) {
            $this->setData('xslType', XSLTransformer::XSL_TRANSFORMER_DOCTYPE_STRING);
        } elseif (is_a($xsl, 'DOMDocument')) {
            $this->setData('xslType', XSLTransformer::XSL_TRANSFORMER_DOCTYPE_DOM);
        } else {
            assert(false);
        }

        $this->setData('xsl', $xsl);
    }

    /**
     * Set the XSL as a file name
     *
     * @param $xslFile string
     */
    public function setXSLFilename($xslFile)
    {
        $this->setData('xslType', XSLTransformer::XSL_TRANSFORMER_DOCTYPE_FILE);
        $this->setData('xsl', $xslFile);
    }

    /**
     * Get the result type
     *
     * @return integer
     */
    public function getResultType()
    {
        return $this->getData('resultType');
    }

    /**
     * Set the result type
     *
     * @param $resultType integer
     */
    public function setResultType($resultType)
    {
        $this->setData('resultType', $resultType);
    }


    //
    // Implement template methods from PersistableFilter
    //
    /**
     * @see PersistableFilter::getClassName()
     */
    public function getClassName()
    {
        return 'lib.pkp.classes.xslt.XSLTransformationFilter';
    }


    //
    // Implement template methods from Filter
    //
    /**
     * Process the given XML with the configured XSL
     *
     * @see Filter::process()
     *
     * @param $xml DOMDocument|string
     *
     * @return DOMDocument|string
     */
    public function &process(&$xml)
    {
        // Determine the input type
        if (is_string($xml)) {
            $xmlType = XSLTransformer::XSL_TRANSFORMER_DOCTYPE_STRING;
        } elseif (is_a($xml, 'DOMDocument')) {
            $xmlType = XSLTransformer::XSL_TRANSFORMER_DOCTYPE_DOM;
        } else {
            assert(false);
        }

        // Determine the result type based on
        // the input type if it has not been
        // set explicitly.
        if (is_null($this->getResultType())) {
            $this->setResultType($xmlType);
        }

        // Transform the input
        $xslTransformer = new XSLTransformer();
        $result = $xslTransformer->transform($xml, $xmlType, $this->getXsl(), $this->getXslType(), $this->getResultType());
        return $result;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\xslt\XSLTransformationFilter', '\XSLTransformationFilter');
}
