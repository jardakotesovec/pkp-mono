<?php
/**
 * @file classes/components/form/publication/PublishForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for publishing a publication
 */

namespace APP\components\forms\publication;

use PKP\components\forms\FieldHTML;
use PKP\components\forms\FormComponent;

define('FORM_PUBLISH', 'publish');

class PublishForm extends FormComponent
{
    /** @copydoc FormComponent::$id */
    public $id = FORM_PUBLISH;

    /** @copydoc FormComponent::$method */
    public $method = 'PUT';

    /** @var Publication */
    public $publication;

    /** @var \Context */
    public $submissionContext;

    /**
     * Constructor
     *
     * @param $action string URL to submit the form to
     * @param $publication Publication The publication to change settings for
     * @param $submissionContext \Context server or press
     * @param $requirementErrors array A list of pre-publication requirements that are not met.
     */
    public function __construct($action, $publication, $submissionContext, $requirementErrors)
    {
        $this->action = $action;
        $this->errors = $requirementErrors;
        $this->publication = $publication;
        $this->submissionContext = $submissionContext;

        // Set separate messages and buttons if publication requirements have passed
        if (empty($requirementErrors)) {
            $msg = __('publication.publish.confirmation');
            $submitLabel = __('publication.publish');
            $this->addPage([
                'id' => 'default',
                'submitButton' => [
                    'label' => $submitLabel,
                ],
            ]);
        } else {
            $msg = '<p>' . __('publication.publish.requirements') . '</p>';
            $msg .= '<ul>';
            foreach ($requirementErrors as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul>';
            $this->addPage([
                'id' => 'default',
            ]);
        }

        // Related Publication status
        if ($publication->getData('relationStatus') == \Publication::PUBLICATION_RELATION_PUBLISHED && $publication->getData('vorDoi')) {
            $relationStatus = __('publication.relation.published');
            $relationStatus .= '<br /><a target="_blank" href="' . $publication->getData('vorDoi') . '">' . $publication->getData('vorDoi') . '</a>';
        } elseif ($publication->getData('relationStatus') == \Publication::PUBLICATION_RELATION_SUBMITTED) {
            $relationStatus = __('publication.relation.submitted');
        } else {
            $relationStatus = __('publication.relation.none');
        }

        $relationStatusMsg = '<table class="pkpTable"><thead><tr>' .
            '<th>' . __('publication.publish.relationStatus') . '</th>' .
            '</tr></thead><tbody>';
        $relationStatusMsg .= '<tr><td>' . $relationStatus . '</td></tr>';
        $relationStatusMsg .= '</tbody></table>';

        $this->addGroup([
            'id' => 'default',
            'pageId' => 'default',
        ])
            ->addField(new FieldHTML('validation', [
                'description' => $msg,
                'groupId' => 'default',
            ]))
            ->addField(new FieldHTML('relationStatus', [
                'description' => $relationStatusMsg,
                'groupId' => 'default',
            ]));
    }
}
