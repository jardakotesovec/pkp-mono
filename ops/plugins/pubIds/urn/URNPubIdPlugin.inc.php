<?php

/**
 * @file plugins/pubIds/urn/URNPubIdPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNPubIdPlugin
 * @ingroup plugins_pubIds_urn
 *
 * @brief URN plugin class
 */


import('classes.plugins.PubIdPlugin');

class URNPubIdPlugin extends PubIdPlugin {

	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PubIdPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'URNPubIdPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.urn.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.urn.description');
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}


	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @see PubIdPlugin::getPubId()
	 */
	function getPubId(&$pubObject, $preview = false) {
		$urn = $pubObject->getStoredPubId($this->getPubIdType());
		if (!$urn) {
			// Determine the type of the publishing object
			$pubObjectType = $this->getPubObjectType($pubObject);

			// Initialize variables for publication objects
			$issue = ($pubObjectType == 'Issue' ? $pubObject : null);
			$article = ($pubObjectType == 'Article' ? $pubObject : null);
			$galley = ($pubObjectType == 'Galley' ? $pubObject : null);
			$suppFile = ($pubObjectType == 'SuppFile' ? $pubObject : null);

			// Get the journal id of the object
			if (in_array($pubObjectType, array('Issue', 'Article'))) {
				$journalId = $pubObject->getJournalId();
			} else {
				// Retrieve the published article
				assert(is_a($pubObject, 'ArticleFile'));
				$articleDao = DAORegistry::getDAO('ArticleDAO');
				$article = $articleDao->getById($pubObject->getArticleId(), null, true);
				if (!$article) return null;

				// Now we can identify the journal
				$journalId = $article->getJournalId();
			}
			// get the journal
			$request =& $this->getRequest();
			$router =& $request->getRouter();
			$journal =& $router->getContext($request);
			if (!$journal || $journal->getId() != $journalId) return null;

			// Check whether URNs are enabled for the given object type
			$urnEnabled = ($this->getSetting($journalId, "enable${pubObjectType}URN") == '1');
			if (!$urnEnabled) return null;

			// Retrieve the issue
			if (!is_a($pubObject, 'Issue')) {
				assert(!is_null($article));
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issue =& $issueDao->getIssueByArticleId($article->getId(), $journal->getId(), true);
			}

			// Retrieve the URN prefix
			$urnPrefix = $this->getSetting($journal->getId(), 'urnPrefix');
			if (empty($urnPrefix)) return null;

			// Generate the URN suffix
			$urnSuffixSetting = $this->getSetting($journal->getId(), 'urnSuffix');
			switch ($urnSuffixSetting) {
				case 'publisherId':
					$urnSuffix = (string) call_user_func_array(array($pubObject, "getBest${pubObjectType}Id"), array(&$journal));
					// When the suffix equals the object's ID then
					// require an object-specific prefix to be sure that the suffix is unique
					if ($pubObjectType != 'Article' && $urnSuffix === (string) $pubObject->getId()) {
						$urnSuffix = strtolower_codesafe($pubObjectType{0}) . $urnSuffix;
					}
					$urn = $urnPrefix . $urnSuffix;
					if ($this->getSetting($journal->getId(), 'checkNo')) {
						$urn .= $this->_calculateCheckNo($urn);
					}
					break;

				case 'customIdentifier':
					$urnSuffix = $pubObject->getData('urnSuffix');
					if (!empty($urnSuffix)) {
						$urn = $urnPrefix . $urnSuffix;
					}
					break;

				case 'pattern':
					$suffixPattern = $this->getSetting($journal->getId(), "urn${pubObjectType}SuffixPattern");
					$urn = $urnPrefix . $suffixPattern;
					if ($issue) {
						// %j - journal initials
						$suffixPattern = String::regexp_replace('/%j/', String::strtolower($journal->getLocalizedAcronym()), $suffixPattern);
						// %v - volume number
						$suffixPattern = String::regexp_replace('/%v/', $issue->getVolume(), $suffixPattern);
						// %i - issue number
						$suffixPattern = String::regexp_replace('/%i/', $issue->getNumber(), $suffixPattern);
						// %Y - year
						$suffixPattern = String::regexp_replace('/%Y/', $issue->getYear(), $suffixPattern);

						if ($article) {
							// %a - article id
							$suffixPattern = String::regexp_replace('/%a/', $article->getId(), $suffixPattern);
							// %p - page number
							$suffixPattern = String::regexp_replace('/%p/', $article->getPages(), $suffixPattern);
						}

						if ($galley) {
							// %g - galley id
							$suffixPattern = String::regexp_replace('/%g/', $galley->getId(), $suffixPattern);
						}

						if ($suppFile) {
							// %s - supp file id
							$suffixPattern = String::regexp_replace('/%s/', $suppFile->getId(), $suffixPattern);
						}
						$urn = $urnPrefix . $suffixPattern;
						if ($this->getSetting($journal->getId(), 'checkNo')) {
							$urn .= $this->_calculateCheckNo($urn);
						}
					}
					break;

				default:
					if ($issue) {
						$suffixPattern = String::strtolower($journal->getLocalizedAcronym());
						$suffixPattern .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
						if ($article) {
		 					$suffixPattern .= '.' . $article->getId();
						}
						if ($galley) {
							$suffixPattern .= '.g' . $galley->getId();
						}
						if ($suppFile) {
							$suffixPattern .= '.s' . $suppFile->getId();
						}
						$urn = $urnPrefix . $suffixPattern;
						if ($this->getSetting($journal->getId(), 'checkNo')) {
							$urn .= $this->_calculateCheckNo($urn);
						}
					} else {
						$suffixPattern = '%j.v%vi%i';
						if ($article) {
							$suffixPattern .= '.%a';
						}
						if ($galley) {
							$suffixPattern .= '.g%g';
						}
						if ($suppFile) {
							$suffixPattern .= '.s%s';
						}
						$urn = $urnPrefix . $suffixPattern;
					}
			}

			if ($urn && !$preview) {
				$this->setStoredPubId($pubObject, $pubObjectType, $urn);
			}
		}
		return $urn;
	}

	/**
	 * @see PubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'other::urn';
	}

	/**
	 * @see PubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'URN';
	}

	/**
	 * @see PubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'Uniform Resource Name';
	}

	/**
	 * @see PubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($journalId, $pubId) {
		$resolverURL = $this->getSetting($journalId, 'urnResolver');
		return $resolverURL . $pubId;
	}

	/**
	 * @see PubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('urnSuffix');
	}

	/**
	 * @see PubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::other::urn');
	}

	/**
	 * @see PubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplatePath().'urnSuffixEdit.tpl';
	}

	/**
	 * @see PubIdPlugin::getSettingsFormName()
	 */
	function getSettingsFormName() {
		return 'classes.form.URNSettingsForm';
	}

	/**
	 * @see PubIdPlugin::verifyData()
	 */
	function verifyData($fieldName, $fieldValue, &$pubObject, $journalId, &$errorMsg) {
		assert($fieldName == 'urnSuffix');
		if (empty($fieldValue)) return true;

		// Construct the potential new URN with the posted suffix.
		$urnPrefix = $this->getSetting($journalId, 'urnPrefix');
		if (empty($urnPrefix)) return true;
		$newURN = $urnPrefix . $fieldValue;
		if ($this->getSetting($journalId, 'checkNo')) {
			$newURNWithoutCheckNo = substr($newURN, 0, -1);
			$newURNWithCheckNo = $newURNWithoutCheckNo . $this->_calculateCheckNo($newURNWithoutCheckNo);
			if ($newURN != $newURNWithCheckNo) {
				$errorMsg = __('plugins.pubIds.urn.form.checkNoRequired');
				return false;
			}
		}
		if(!$this->checkDuplicate($newURN, $pubObject, $journalId)) {
			$errorMsg = __('plugins.pubIds.urn.form.customIdentifierNotUnique');
			return false;
		}
		return true;
	}

	//
	// Private helper methods
	//
	/**
	 * Get the last, check number.
	 * Algorithm (s. http://www.persistent-identifier.de/?link=316):
	 *  every URN character is replaced with a number according to the conversion table,
	 *  every number is multiplied by it's position/index (beginning with 1),
	 *  the numbers' sum is calculated,
	 *  the sum is devided by the last number,
	 *  the last number of the quotient before the decimal point is the check number.
	 */
	function _calculateCheckNo($urn) {
	    $urnLower = strtolower_codesafe($urn);

	    $conversionTable = array('9' => '41', '8' => '9', '7' => '8', '6' => '7', '5' => '6', '4' => '5', '3' => '4', '2' => '3', '1' => '2', '0' => '1', 'a' => '18', 'b' => '14', 'c' => '19', 'd' => '15', 'e' => '16', 'f' => '21', 'g' => '22', 'h' => '23', 'i' => '24', 'j' => '25', 'k' => '42', 'l' => '26', 'm' => '27', 'n' => '13', 'o' => '28', 'p' => '29', 'q' => '31', 'r' => '12', 's' => '32', 't' => '33', 'u' => '11', 'v' => '34', 'w' => '35', 'x' => '36', 'y' => '37', 'z' => '38', '-' => '39', ':' => '17', '_' => '43', '/' => '45', '.' => '47', '+' => '49');

	    $newURN = '';
	    for ($i = 0; $i < strlen($urnLower); $i++) {
	    	$char = $urnLower[$i];
	    	$newURN .= $conversionTable[$char];
	    }
	    $sum = 0;
	    for ($j = 1; $j <= strlen($newURN); $j++) {
		    $sum = $sum + ($newURN[$j-1] * $j);
	    }
	    $lastNumber = $newURN[strlen($newURN)-1];
	    $quot = $sum / $lastNumber;
	    $quotRound = floor($quot);
	    $quotString = (string)$quotRound;

	    return $quotString[strlen($quotString)-1];
	}
}

?>
