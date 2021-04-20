<?php
/**
 * @defgroup controlled_vocab Controlled Vocabulary
 */

/**
 * @file classes/controlledVocab/ControlledVocab.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocab
 * @ingroup controlled_vocab
 *
 * @see ControlledVocabDAO
 *
 * @brief Basic class describing an controlled vocab.
 */

class ControlledVocab extends \PKP\core\DataObject
{
    //
    // Get/set methods
    //

    /**
     * get assoc id
     *
     * @return int
     */
    public function getAssocId()
    {
        return $this->getData('assocId');
    }

    /**
     * set assoc id
     *
     * @param $assocId int
     */
    public function setAssocId($assocId)
    {
        $this->setData('assocId', $assocId);
    }

    /**
     * Get associated type.
     *
     * @return int
     */
    public function getAssocType()
    {
        return $this->getData('assocType');
    }

    /**
     * Set associated type.
     *
     * @param $assocType int
     */
    public function setAssocType($assocType)
    {
        $this->setData('assocType', $assocType);
    }

    /**
     * Get symbolic name.
     *
     * @return string
     */
    public function getSymbolic()
    {
        return $this->getData('symbolic');
    }

    /**
     * Set symbolic name.
     *
     * @param $symbolic string
     */
    public function setSymbolic($symbolic)
    {
        $this->setData('symbolic', $symbolic);
    }

    /**
     * Get a list of controlled vocabulary options.
     *
     * @param $settingName string optional
     *
     * @return array $controlledVocabEntryId => name
     */
    public function enumerate($settingName = 'name')
    {
        $controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO'); /** @var ControlledVocabDAO $controlledVocabDao */
        return $controlledVocabDao->enumerate($this->getId(), $settingName);
    }
}
