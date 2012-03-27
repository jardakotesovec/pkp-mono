<?php

/**
 * @file plugins/pubIds/urn/URNPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNPlugin
 * @ingroup plugins_pubIds_urn
 *
 * @brief URN plugin class
 */


import('classes.plugins.PubIdPlugin');

// FIXME-BB: Rename class to UrnPubIdPlugin? This would
// correspond to how other plug-ins are being named.
class URNPlugin extends PubIdPlugin {

	// FIXME-BB: Comments are missing, see example in DoiPubIdPlugin.
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	function getName() {
		return 'URNPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.pubIds.urn.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.pubIds.urn.description');
	}

	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}


	// FIXME-BB: The following are all overridden template methods
	// from PubIdPlugin. IMO they should not be commented as if they
	// were new methods but rather use @see comments. (See how I did it
	// in DoiPubIdPlugin.
	/*
	 * Get and Set
	 */
	/**
	 * Get the public identifier.
	 * @param $pubObject object
	 *  (Issue, Article, PublishedArticle, ArticleGalley, SuppFile)
	 * @param $preview boolean
	 *  when true, the public identifier will not be stored
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
				$articleDao =& DAORegistry::getDAO('ArticleDAO');
				$article =& $articleDao->getArticle($pubObject->getArticleId(), null, true);
				if (!$article) return null;

				// Now we can identify the journal
				$journalId = $article->getJournalId();
			}
			// get the journal
			$request =& Application::getRequest();
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
						$urnSuffix = strtolower($pubObjectType{0}) . $urnSuffix;
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
						$suffixPattern = String::regexp_replace('/%j/', String::strtolower($journal->getLocalizedSetting('initials')), $suffixPattern);
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
						$suffixPattern = String::strtolower($journal->getLocalizedSetting('initials'));
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
	 * Public identifier type, that is used in the database.
	 * S. http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html
	 */
	function getPubIdType() {
		return 'other::urn';
	}

	/**
	 * Public identifier type that will be displayed to the reader.
	 */
	function getPubIdDisplayType() {
		return 'URN';
	}

	/**
	 * Full name of the public identifier.
	 */
	function getPubIdFullName() {
		return 'Uniform Resource Name';
	}

	/**
	 * Get the whole resolving URL.
	 * @param $pubId string
	 * @return string resolving URL
	 */
	function getResolvingURL($pubId) {
		return 'http://nbn-resolving.de/'.$pubId;
	}

	/**
	 * Get additional field names to be considered for custom suffix.
	 */
	function getFormFieldNames() {
		return array('urnSuffix');
	}

	/**
	 * Get additional field names to be considered for storage.
	 */
	function getDAOFieldNames() {
		return array('pub-id::other::urn');
	}

	/**
	 * Get the file (path + file name)
	 * that is included in the objects metadata pages.
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
		if(!$this->checkDuplicate($newURN, $pubObject, $journalId)) {
			$errorMsg = AppLocale::translate('plugins.pubIds.urn.form.customIdentifierNotUnique');
			return false;
		}
		return true;
	}

	/**
	 * Check for duplicate URN.
	 * @param $data string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function checkDuplicate($newURN, &$pubObject, $journalId) {
		// Check all objects of the journal whether they have the same URN.
		// This also includes URNs that are not generated yet.
		// We have to check "real" URNs rather than only the URN suffixes
		// as a URN with the given suffix may exist (e.g. through import)
		// even if the suffix itself is not in the database.
		$typesToCheck = array('Issue', 'Article', 'ArticleGalley', 'SuppFile');
		foreach($typesToCheck as $pubObjectType) {
			switch($pubObjectType) {
				case 'Issue':
					$issueDao =& DAORegistry::getDAO('IssueDAO');
					$objectsToCheck =& $issueDao->getIssues($journalId);
					break;

				case 'Article':
					$articleDao =& DAORegistry::getDAO('ArticleDAO');
					$objectsToCheck =& $articleDao->getArticlesByJournalId($journalId);
					break;

				case 'ArticleGalley':
					$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
					$objectsToCheck =& $galleyDao->getGalleysByJournalId($journalId);
					break;

				case 'SuppFile':
					$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
					$objectsToCheck =& $suppFileDao->getSuppFilesByJournalId($journalId);
					break;
			}

			$excludedId = (is_a($pubObject, $pubObjectType) ? $pubObject->getId() : null);
			while ($objectToCheck =& $objectsToCheck->next()) {
				// The publication object for which the new URN
				// should be admissible is to be ignored. Otherwise
				// we might get false positives by checking against
				// a URN that we're about to change anyway.
				if ($objectToCheck->getId() == $excludedId) continue;

				// Check for ID clashes.
				$existingURN = $this->getPubId($objectToCheck, true);
				if ($newURN == $existingURN) {
					return false;
				}
				unset($objectToCheck);
			}
			unset($objectsToCheck);
		}
		// We did not find any ID collision, so go ahead.
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
	    $urnLower = strtolower($urn);

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
