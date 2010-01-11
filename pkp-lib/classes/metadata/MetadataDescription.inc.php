<?php

/**
 * @file classes/metadata/MetadataDescription.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataDescription
 * @ingroup metadata
 * @see MetadataProperty
 * @see MetadataRecord
 * @see MetadataSchema
 *
 * @brief Class modeling a description (DCMI abstract model) or subject-
 *  predicate-object graph (RDF). This class and its children provide
 *  meta-data (DCMI abstract model: statements of property-value pairs,
 *  RDF: assertions of predicate-object pairs) about a given PKP application
 *  entity instance (DCMI abstract model: described resource, RDF: subject).
 *
 *  This class has primarily been designed to describe journals, journal
 *  issues, articles, conferences, conference proceedings (conference papers),
 *  monographs (books), monograph components (book chapters) or citations.
 *
 *  It is, however, flexible enough to be extended to describe any
 *  application entity in the future. Descriptions can be retrieved from
 *  any application object that implements the MetadataProvider interface.
 *
 *  Special attention has been paid to the compatibility of the class
 *  implementation with the implementation of several meta-data standards
 *  that we consider especially relevant to our use cases.
 *
 *  We distinguish two main use cases for meta-data: discovery and delivery
 *  of described resources. We have chosen the element-citation tag from the
 *  NLM standard <http://dtd.nlm.nih.gov/publishing/tag-library/3.0/n-8xa0.html>
 *  as our primary representation of delivery meta-data and dcterms
 *  <http://dublincore.org/documents/dcmi-terms/> as our primary
 *  representation of discovery meta-data.
 *
 *  Our specific use of meta-data has important implications and determines
 *  our design goals:
 *  * Neither NLM-citation nor dcterms have been designed with an object
 *    oriented encoding in mind. NLM-citation is usually XML encoded
 *    while typical dcterms encodings are HTML meta-tags, RDF or XML.
 *  * We believe that trying to implement a super-set of meta-data
 *    standards ("least common denominator" or super-schema approach)
 *    is fundamentally flawed as meta-data standards are always
 *    developed with specific use-cases in mind that require potentially
 *    incompatible data properties or encodings.
 *  * Although we think that NLM-citation and dcterms are sensible default
 *    meta-data schemes our design should remain flexible enough for
 *    users to implement and use other schemes as an internal meta-data
 *    standard.
 *  * We have to make sure that we can easily extract/inject meta-data
 *    from/to PKP application objects.
 *  * We have to avoid code duplication to keep maintenance cost under
 *    control.
 *  * We have to minimize the "impedance mismatch" between our own
 *    object oriented encoding and fully standard compliant external
 *    encodings (i.e. XML, RDF, HTML meta-tags, ...) to allow for easy
 *    conversion between encodings.
 *  * We have to make sure that we can switch between internal and
 *    external encodings without any data loss.
 *  * We have to make sure that crosswalks to and from other important
 *    meta-data standards (e.g. OpenURL variants, MODS, MARC) can be
 *    performed in a well-defined and easy way while minimizing data
 *    loss.
 *  * We have to make sure that we can support qualified fields (e.g.
 *    qualified DC).
 *  * We have to make sure that we can support RDF triples.
 *
 *  We took the following design decisions to achieve these goals:
 *  * We only implement properties that are justified by strong real-world
 *    use-cases. We recognize that the limiting factor is not the data that
 *    we could represent but the data we actually have. This is not determined
 *    by the chosen standard but by the PKP application objects we want to
 *    represent. Additional meta-data properties/predicates can be added as
 *    required.
 *  * We do adapt data structures as long as we can make sure that a
 *    fully standard compliant encoding can always be re-constructed. This
 *    is especially true for NLM-citation which is designed with
 *    XML in mind and therefore uses hierarchical constructs that are
 *    difficult to represent in an OO class model.
 *    This means that our meta-data framework only supports (nested) key/
 *    value-based schemas which can however be converted to hierarchical
 *    representations.
 *  * We borrow class and property names from the DCMI abstract model as
 *    the terms used there provide better readability for developers less
 *    acquainted with formal model theory. We'll, however, make sure that
 *    data can easily be RDF encoded within our data model.
 *  * Data validation must ensure that meta-data always complies with a
 *    specific meta-data standard. As we are speaking about an object
 *    oriented encoding that is not defined in the original standard, we
 *    define compliance as "roundtripability". This means we must be able
 *    to convert our object oriented data encoding to a fully standard
 *    compliant encoding and back without any data loss.
 *
 *  TODO: Let all meta-data providers implement a common MetadataProvider
 *  interface once we drop PHP4 compatibility.
 *
 *  TODO: Let PKPAuthor inherit from a "Person" class that we can use generically
 *  for authors and editors.
 *
 *  TODO: Let Editor return an array of Persons rather than a string.
 *
 *  TODO: Develop an object representation for NLM's "collab", "anonymous" and "etal".
 *
 *  TODO: Move Harvester's Schema and Record to the new Metadata object model.
 */

// $Id$

import('core.DataObject');

class MetadataDescription extends DataObject {
	/** @var MetadataSchema the schema this description complies to */
	var $_metadataSchema;

	/** @var int association type (the type of the described resource) */
	var $_assocType;

	/** @var int association id (the identifier of the described resource) */
	var $_assocId;

	/**
	 * Constructor
	 */
	function MetadataDescription(&$metadataSchema, $assocType) {
		parent::DataObject();
		$this->_metadataSchema =& $metadataSchema;
		$this->_assocType = $assocType;
	}

	//
	// Get/set methods
	//
	/**
	 * Get the metadata schema
	 * @return MetadataSchema
	 */
	function &getMetadataSchema() {
		return $this->_metadataSchema;
	}

	/**
	 * Get the association type (described resource type)
	 * @return int
	 */
	function getAssocType() {
		return $this->_assocType;
	}

	/**
	 * Get the association id (described resource identifier)
	 * @return int
	 */
	function getAssocId() {
		return $this->_assocId;
	}

	/**
	 * Set the association id (described resource identifier)
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->_assocId = $assocId;
	}

	/**
	 * Add a meta-data statement. Statements can only be added
	 * for properties that are part of the meta-data schema. This
	 * method will also check the validity of the value for the
	 * given property before adding the statement.
	 * @param $propertyName string The name of the property
	 * @param $value mixed The value to be assigned to the property
	 * @param $replace boolean whether to replace an existing statement
	 * @param $locale string
	 * @return boolean true if a valid statement was added, otherwise false
	 */
	function addStatement($propertyName, &$value, $replace = true, $locale = null) {
		// Check the property
		$property =& $this->getProperty($propertyName);
		if (is_null($property)) return false;
		assert(is_a($property, 'MetadataProperty'));

		// Check that the property is allowed for the described resource
		if (!in_array($this->_assocType, $property->getAssocTypes())) return false;

		// Check that the value is compliant with the property specification
		if (!$property->isValid($value)) return false;

		// Handle translation
		$translated = $property->getTranslated();
		if (isset($locale) && !$translated) return false;
		if (!isset($locale) && $translated) {
			// Retrieve the current locale
			$locale = Locale::getLocale();
		}

		// Handle cardinality
		$existingValue =& $this->getStatement($propertyName, $locale);
		switch ($property->getCardinality()) {
			case METADATA_PROPERTY_CARDINALITY_ONE:
				if (isset($existingValue) && !$replace) return false;
				$newValue =& $value;
				break;

			case METADATA_PROPERTY_CARDINALITY_MANY:
				if (isset($existingValue) && !$replace) {
					assert(is_array($existingValue));
					$newValue = $existingValue;
					array_push($newValue, $value);
				} else {
					$newValue = array(&$value);
				}
				break;

			default:
				assert(false);
		}

		// Add the value
		$this->setData($propertyName, $newValue, $locale);
		return true;
	}

	/**
	 * Remove statement. If the property has cardinality 'many' or
	 * if it has several translations then all statements for the
	 * property will be removed at once.
	 * @param $propertyName string
	 * @return boolean true if the statement was found and removed, otherwise false
	 */
	function removeStatement($propertyName) {
		// Remove the statement if it exists
		if (isset($propertyName) && $this->hasData($propertyName)) {
			$this->setData($propertyName, null);
			return true;
		}

		return false;
	}

	/**
	 * Get all statements
	 * @return array statements
	 */
	function &getStatements() {
		// Do not retrieve the data by-ref
		// otherwise the following unset()
		// will change internal state.
		$allData = $this->getAllData();

		// Unset data variables that are not statements
		unset($allData['id']);
		return $allData;
	}

	/**
	 * Get a specific statement
	 * @param $propertyName string
	 * @param $locale string
	 * @return mixed a scalar property value or an array of property values
	 *  if the cardinality of the property is 'many'.
	 */
	function &getStatement($propertyName, $locale = null) {
		// Check the property
		$property =& $this->getProperty($propertyName);
		assert(isset($property) && is_a($property, 'MetadataProperty'));

		// Handle translation
		$translated = $property->getTranslated();
		if (!$translated) assert(is_null($locale));
		if ($translated && !isset($locale)) {
			// Retrieve the current locale
			$locale = Locale::getLocale();
		}

		// Retrieve the value
		return $this->getData($propertyName, $locale);
	}

	/**
	 * Replace all existing statements at once. If one of the statements
	 * is invalid then the meta-data description will be empty after this
	 * operation.
	 * * Properties with a cardinality of 'many' must be passed in as
	 *   sub-arrays.
	 * * Translated properties with a cardinality of 'one' must be
	 *   passed in as sub-arrays with the locale as a key.
	 * * Translated properties with a cardinality of 'many' must be
	 *   passed in as sub-sub-arrays with the locale as the second key.
	 * @param $statements array statements
	 * @return boolean true if all statements could be added, false otherwise
	 */
	function setStatements(&$statements) {
		// Delete existing statements
		$emptyArray = array();
		$this->setAllData($emptyArray);

		// Add statements one by one to detect invalid values.
		foreach($statements as $propertyName => $value) {
			// Transform scalars to arrays so that we can handle
			// properties with different cardinalities in the same way.
			if (is_scalar($value)) $value = array(&$value);

			foreach($value as $scalarValue) {
				if (!($this->addStatement($propertyName, $scalarValue, false))) {
					$this->setAllData($emptyArray);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Convenience method that returns the properties of
	 * the underlying meta-data schema.
	 * @return array an array of MetadataProperties
	 */
	function &getProperties() {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getProperties();
	}

	/**
	 * Convenience method that returns a property from
	 * the underlying meta-data schema.
	 * @return MetadataProperty
	 */
	function &getProperty($propertyName) {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getProperty($propertyName);
	}

	/**
	 * Convenience method that returns the valid
	 * property names of the underlying meta-data schema.
	 * @return array an array of string values representing valid property names
	 */
	function getPropertyNames() {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getPropertyNames();
	}

	/**
	 * Returns an array of property names for
	 * which statements exist.
	 * @return array an array of string values representing valid property names
	 */
	function getSetPropertyNames() {
		return array_keys($this->getStatements());
	}

	/**
	 * Convenience method that checks the existence
	 * of a property in the underlying meta-data schema.
	 * @param $propertyName string
	 * @return boolean
	 */
	function hasProperty($propertyName) {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->hasProperty($propertyName);
	}
}
?>