<?php

/**
 * @file classes/OrcidValidator.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OrcidValidator
 * @brief General Orcid validations
 */

namespace APP\plugins\generic\orcidProfile\classes;

use APP\plugins\generic\orcidProfile\OrcidProfilePlugin;

class OrcidValidator {
    public $plugin;
	/**
	 * OrcidValidator constructor.
	 * @param OrcidProfilePlugin $plugin
	 */
	function __construct(&$plugin) {
		$this->plugin =& $plugin;
	}

	/**
	 * @param string $str
	 * @return bool
	 */
	public function validateClientId($str) {
		$valid = false;
		if (preg_match('/^APP-[\da-zA-Z]{16}|(\d{4}-){3,}\d{3}[\dX]/', $str) == 1) {
			$valid = true;
		}
		return $valid;
	}

	/**
	 * @param string $str
	 * @return bool
	 */
	public function validateClientSecret($str) {
		$valid = false;
		if (preg_match('/^(\d|-|[a-f]){36,64}/', $str) == 1) {
			$valid = true;
		}
		return $valid;
	}

}
