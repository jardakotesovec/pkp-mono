<?php

/**
 * @file classes/reviewForm/ReviewFormElement.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElement
 * @ingroup reviewForm
 *
 * @see ReviewFormElementDAO
 *
 * @brief Basic class describing a review form element.
 *
 */

define('REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD', 0x000001);
define('REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD', 0x000002);
define('REVIEW_FORM_ELEMENT_TYPE_TEXTAREA', 0x000003);
define('REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES', 0x000004);
define('REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS', 0x000005);
define('REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX', 0x000006);

class ReviewFormElement extends \PKP\core\DataObject
{
    /**
     * Get localized question.
     *
     * @return string
     */
    public function getLocalizedQuestion()
    {
        return $this->getLocalizedData('question');
    }

    /**
     * Get localized description.
     *
     * @return string
     */
    public function getLocalizedDescription()
    {
        return $this->getLocalizedData('description');
    }

    /**
     * Get localized list of possible responses.
     *
     * @return array
     */
    public function getLocalizedPossibleResponses()
    {
        return $this->getLocalizedData('possibleResponses');
    }

    //
    // Get/set methods
    //

    /**
     * Get the review form ID of the review form element.
     *
     * @return int
     */
    public function getReviewFormId()
    {
        return $this->getData('reviewFormId');
    }

    /**
     * Set the review form ID of the review form element.
     *
     * @param $reviewFormId int
     */
    public function setReviewFormId($reviewFormId)
    {
        $this->setData('reviewFormId', $reviewFormId);
    }

    /**
     * Get sequence of review form element.
     *
     * @return float
     */
    public function getSequence()
    {
        return $this->getData('sequence');
    }

    /**
     * Set sequence of review form element.
     *
     * @param $sequence float
     */
    public function setSequence($sequence)
    {
        $this->setData('sequence', $sequence);
    }

    /**
     * Get the type of the review form element.
     *
     * @return string
     */
    public function getElementType()
    {
        return $this->getData('reviewFormElementType');
    }

    /**
     * Set the type of the review form element.
     *
     * @param $reviewFormElementType string
     */
    public function setElementType($reviewFormElementType)
    {
        $this->setData('reviewFormElementType', $reviewFormElementType);
    }

    /**
     * Get required flag
     *
     * @return boolean
     */
    public function getRequired()
    {
        return $this->getData('required');
    }

    /**
     * Set required flag
     */
    public function setRequired($required)
    {
        $this->setData('required', $required);
    }

    /**
     * get included
     *
     * @return boolean
     */
    public function getIncluded()
    {
        return $this->getData('included');
    }

    /**
     * set included
     *
     * @param $included boolean
     */
    public function setIncluded($included)
    {
        $this->setData('included', $included);
    }

    /**
     * Get question.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getQuestion($locale)
    {
        return $this->getData('question', $locale);
    }

    /**
     * Set question.
     *
     * @param $question string
     * @param $locale string
     */
    public function setQuestion($question, $locale)
    {
        $this->setData('question', $question, $locale);
    }

    /**
     * Get description.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getDescription($locale)
    {
        return $this->getData('description', $locale);
    }

    /**
     * Set description.
     *
     * @param $description string
     * @param $locale string
     */
    public function setDescription($description, $locale)
    {
        $this->setData('description', $description, $locale);
    }

    /**
     * Get possible response.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getPossibleResponses($locale)
    {
        return $this->getData('possibleResponses', $locale);
    }

    /**
     * Set possibleResponse.
     *
     * @param $locale string
     */
    public function setPossibleResponses($possibleResponses, $locale)
    {
        $this->setData('possibleResponses', $possibleResponses, $locale);
    }

    /**
     * Get an associative array matching review form element type codes with locale strings.
     * (Includes default '' => "Choose One" string.)
     *
     * @return array reviewFormElementType => localeString
     */
    public function &getReviewFormElementTypeOptions()
    {
        static $reviewFormElementTypeOptions = [
            '' => 'manager.reviewFormElements.chooseType',
            REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD => 'manager.reviewFormElements.smalltextfield',
            REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD => 'manager.reviewFormElements.textfield',
            REVIEW_FORM_ELEMENT_TYPE_TEXTAREA => 'manager.reviewFormElements.textarea',
            REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES => 'manager.reviewFormElements.checkboxes',
            REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS => 'manager.reviewFormElements.radiobuttons',
            REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX => 'manager.reviewFormElements.dropdownbox'
        ];
        return $reviewFormElementTypeOptions;
    }

    /**
     * Get an array of all multiple responses element types.
     *
     * @return array reviewFormElementTypes
     */
    public function &getMultipleResponsesElementTypes()
    {
        static $multipleResponsesElementTypes = [REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES, REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS, REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX];
        return $multipleResponsesElementTypes;
    }
}
