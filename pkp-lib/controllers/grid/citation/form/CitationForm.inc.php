<?php

/**
 * @file controllers/grid/citation/form/CitationForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationForm
 * @ingroup controllers_grid_citation_form
 *
 * @brief Form for adding/editing a citation
 * stores/retrieves from an associative array
 */

import('form.Form');

class CitationForm extends Form {
	/** Citation the citation being edited **/
	var $_citation;

	/**
	 * Constructor.
	 */
	function CitationForm($citation) {
		parent::Form('controllers/grid/citation/form/citationForm.tpl');

		assert(is_a($citation, 'Citation'));
		$this->_citation =& $citation;

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'editedCitation', 'required', 'submission.citations.grid.editedCitationRequired'));
		$this->addCheck(new FormValidatorPost($this));
		// FIXME: Write and add meta-data description validator
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the citation
	 * @return Citation
	 */
	function &getCitation() {
		return $this->_citation;
	}

	//
	// Template methods from Form
	//
	/**
	 * Initialize form data from the associated citation.
	 * @param $citation Citation
	 */
	function initData() {
		$citation =& $this->getCitation();

		// The unparsed citation text
		$this->setData('editedCitation', $citation->getEditedCitation());

		// Citation meta-data
		foreach($citation->getSupportedMetadataAdapters() as $metadataAdapter) {
			// Retrieve the meta-data schema
			$metadataSchema =& $metadataAdapter->getMetadataSchema();
			$metadataSchemaNamespace = $metadataSchema->getNamespace();

			// Loop over the properties in the schema and add string
			// values for all form fields.
			$citationVars = array();
			$properties = $metadataSchema->getProperties();
			$metadataDescription =& $citation->extractMetadata($metadataSchema);
			foreach($properties as $propertyName => $property) {
				if ($metadataDescription->hasStatement($propertyName)) {
					$value = $metadataDescription->getStatement($propertyName);

					if ($property->getType() == METADATA_PROPERTY_TYPE_COMPOSITE) {
						// We currently only support composite name arrays
						assert(in_array($property->getCompositeType(), array(ASSOC_TYPE_AUTHOR, ASSOC_TYPE_EDITOR)));
						import('metadata.nlm.NlmNameSchemaPersonStringFilter');
						$personStringFilter = new NlmNameSchemaPersonStringFilter(PERSON_STRING_FILTER_MULTIPLE);
						assert($personStringFilter->supportsAsInput($value));
						$fieldValue = $personStringFilter->execute($value);
					} else {
						// We currently don't support repeated values
						if ($property->getCardinality() == METADATA_PROPERTY_CARDINALITY_MANY && !empty($value)) {
							assert(is_array($value) && count($value) <= 1);
							$value = $value[0];
						}
						$fieldValue = (string)$value;
					}
				} else {
					$fieldValue = '';
				}
				$fieldName = $metadataSchema->getNamespacedPropertyId($propertyName);
				$citationVars[$fieldName] = array(
					'translationKey' => $property->getDisplayName(),
					'value' => $fieldValue
				);
			}
		}
		$this->setData('citationVars', $citationVars);
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$citation =& $this->getCitation();
		assert(is_a($citation, 'Citation'));
		$namespacedMetadataProperties = $citation->getNamespacedMetadataProperties();

		// Add properties to the template (required for property display names)
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('namespacedMetadataProperties', $namespacedMetadataProperties);

		// Add the citation to the template
		$templateMgr->assign_by_ref('citation', $citation);

		// Add actions for parsing and lookup
		$actionArgs = array(
			'articleId' => $citation->getAssocId(),
			'citationId' => $citation->getId()
		);
		$router = $request->getRouter();
		$parseAction = new GridAction(
			'parseCitation',
			GRID_ACTION_MODE_AJAX,
			GRID_ACTION_TYPE_NOTHING,
			$router->url($request, null, null, 'parseCitation', null, $actionArgs),
			'submission.citations.grid.parseCitation'
		);
		$templateMgr->assign_by_ref('parseAction', $parseAction);
		$lookupAction = new GridAction(
			'lookupCitation',
			GRID_ACTION_MODE_AJAX,
			GRID_ACTION_TYPE_NOTHING,
			$router->url($request, null, null, 'lookupCitation', null, $actionArgs),
			'submission.citations.grid.lookupCitation'
		);
		$templateMgr->assign_by_ref('lookupAction', $lookupAction);

		parent::display($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('editedCitation'));

		$citation =& $this->getCitation();
		$citationVars = array();
		foreach($citation->getSupportedMetadataAdapters() as $metadataAdapter) {
			// Retrieve the meta-data schema
			$metadataSchema =& $metadataAdapter->getMetadataSchema();
			$metadataSchemaNamespace = $metadataSchema->getNamespace();

			// Loop over the property names in the schema and add string
			// values for all form fields.
			$propertyNames =& $metadataSchema->getPropertyNames();
			foreach($propertyNames as $propertyName) {
				$citationVars[] = $metadataSchema->getNamespacedPropertyId($propertyName);
			}
		}
		$this->readUserVars($citationVars);
	}

	/**
	 * Save citation
	 */
	function execute() {
		$citation =& $this->getCitation();
		$citation->setEditedCitation($this->getData('editedCitation'));

		// Extract data from citation form fields and inject it into the citation
		$metadataAdapters = $citation->getSupportedMetadataAdapters();
		foreach($metadataAdapters as $metadataAdapter) {
			// Instantiate a meta-data description for the given schema
			$metadataSchema =& $metadataAdapter->getMetadataSchema();
			import('metadata.MetadataDescription');
			$metadataDescription = new MetadataDescription($metadataSchema, ASSOC_TYPE_CITATION);

			// Set the meta-data statements
			$metadataSchemaNamespace = $metadataSchema->getNamespace();
			foreach($metadataSchema->getProperties() as $propertyName => $property) {
				$fieldName = $metadataSchema->getNamespacedPropertyId($propertyName);
				$fieldValue = trim($this->getData($fieldName));
				if (!empty($fieldValue)) {
					// Some property types need to be converted first
					switch($property->getType()) {
						case METADATA_PROPERTY_TYPE_COMPOSITE:
							// We currently only support name composites
							assert(in_array($property->getCompositeType(), array(ASSOC_TYPE_AUTHOR, ASSOC_TYPE_EDITOR)));
							import('metadata.nlm.PersonStringNlmNameSchemaFilter');
							$personStringFilter = new PersonStringNlmNameSchemaFilter($property->getCompositeType(), PERSON_STRING_FILTER_MULTIPLE);
							assert($personStringFilter->supportsAsInput($fieldValue));
							$fieldValue =& $personStringFilter->execute($fieldValue);
							break;

						case METADATA_PROPERTY_TYPE_INTEGER:
							$fieldValue = array((integer)$fieldValue);
							break;

						case METADATA_PROPERTY_TYPE_DATE:
							import('metadata.DateStringNormalizerFilter');
							$dateStringFilter = new DateStringNormalizerFilter();
							assert($dateStringFilter->supportsAsInput($fieldValue));
							$fieldValue = array($dateStringFilter->execute($fieldValue));
							break;

						default:
							$fieldValue = array($fieldValue);
					}
					foreach($fieldValue as $fieldValueStatement) {
						$metadataDescription->addStatement($property->getName(), $fieldValueStatement);
						unset($fieldValueStatement);
					}
				}
			}

			// Inject the meta-data into the citation
			$citation->injectMetadata($metadataDescription);
		}

		// Persist citation
		$citationDAO =& DAORegistry::getDAO('CitationDAO');
		if (is_numeric($citation->getId())) {
			$citationDAO->updateCitation($citation);
		} else {
			$citationDAO->insertCitation($citation);
		}

		return true;
	}
}

?>
