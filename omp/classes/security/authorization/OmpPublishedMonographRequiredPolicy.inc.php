<?php
/**
 * @file classes/security/authorization/OmpPublishedMonographRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OmpPublishedMonographRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid published monograph.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class OmpPublishedMonographRequiredPolicy extends DataObjectRequiredPolicy {
	/** @var Context */
	var $context;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $operations array
	 */
	function OmpPublishedMonographRequiredPolicy($request, &$args, $operations = null) {
		parent::DataObjectRequiredPolicy($request, $args, 'publishedMonographId', 'user.authorization.invalidPublishedMonograph', $operations);
		$this->context = $request->getContext();
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see DataObjectRequiredPolicy::dataObjectEffect()
	 */
	function dataObjectEffect() {
		$publishedMonographId = $this->getDataObjectId();
		if (!$publishedMonographId) return AUTHORIZATION_DENY;

		// Make sure the published monographs belongs to the press.
		$publishedMonographDao = DAORegistry::getDAO('PublishedMonographDAO');
		$publishedMonograph = $publishedMonographDao->getByBestId($publishedMonographId,  $this->context->getId());
		if (!is_a($publishedMonograph, 'PublishedMonograph')) return AUTHORIZATION_DENY;

		// Save the published monograph to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_PUBLISHED_MONOGRAPH, $publishedMonograph);
		return AUTHORIZATION_PERMIT;
	}

	/**
	 * @copydoc DataObjectRequiredPolicy::getDataObjectId()
	 * Considers a not numeric public URL identifier
	 */
	function getDataObjectId() {
		// Identify the data object id.
		$router = $this->_request->getRouter();
		switch(true) {
			case is_a($router, 'PKPPageRouter'):
				if ( ctype_digit((string) $this->_request->getUserVar($this->_parameterName)) ) {
					// We may expect a object id in the user vars
					return (int) $this->_request->getUserVar($this->_parameterName);
				} else if (isset($this->_args[0])) {
					// Or the object id can be expected as the first path in the argument list
					return $this->_args[0];
				}
				break;

			default:
				return parent::getDataObjectId();
		}

		return false;
	}
}

?>
