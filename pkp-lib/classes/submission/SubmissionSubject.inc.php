<?php

/**
 * @file classes/submission/SubmissionSubject.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubject
 * @ingroup submission
 *
 * @see SubmissionSubjectEntryDAO
 *
 * @brief Basic class describing a submission subject
 */

class SubmissionSubject extends \PKP\controlledVocab\ControlledVocabEntry
{
    //
    // Get/set methods
    //

    /**
     * Get the subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getData('submissionSubject');
    }

    /**
     * Set the subject text
     *
     * @param subject string
     * @param locale string
     */
    public function setSubject($subject, $locale)
    {
        $this->setData('submissionSubject', $subject, $locale);
    }

    /**
     * @copydoc \PKP\controlledVocab\ControlledVocabEntry::getLocaleMetadataFieldNames()
     */
    public function getLocaleMetadataFieldNames()
    {
        return ['submissionSubject'];
    }
}
