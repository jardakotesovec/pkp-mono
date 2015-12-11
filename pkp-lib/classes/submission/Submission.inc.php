<?php

/**
 * @defgroup submission Submission
 * The abstract concept of a submission is implemented here, and extended
 * in each application with the specifics of that content model, i.e.
 * Articles in OJS, Papers in OCS, and Monographs in OMP.
 */

/**
 * @file classes/submission/Submission.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Submission
 * @ingroup submission
 * @see SubmissionDAO
 *
 * @brief The Submission class implements the abstract data model of a
 * scholarly submission.
 */

// Submission status constants
define('STATUS_QUEUED', 1);
define('STATUS_PUBLISHED', 3);
define('STATUS_DECLINED', 4);

// License settings (internal use only)
define ('PERMISSIONS_FIELD_LICENSE_URL', 1);
define ('PERMISSIONS_FIELD_COPYRIGHT_HOLDER', 2);
define ('PERMISSIONS_FIELD_COPYRIGHT_YEAR', 3);

abstract class Submission extends DataObject {
	/**
	 * Constructor.
	 */
	function Submission() {
		// Switch on meta-data adapter support.
		$this->setHasLoadableAdapters(true);

		parent::DataObject();
	}

	/**
	 * Get the localized copyright holder for this submission.
	 * @param $preferredLocale string Preferred locale code
	 * @return string Localized copyright holder.
	 */
	function getLocalizedCopyrightHolder($preferredLocale = null) {
		return $this->getLocalizedData('copyrightHolder', $preferredLocale);
	}

	/**
	 * Get the license URL for this submission.
	 * @return string License URL.
	 */
	function getDefaultLicenseUrl() {
		return $this->_getDefaultLicenseFieldValue(null, PERMISSIONS_FIELD_LICENSE_URL);
	}

	/**
	 * Get the copyright holder for this submission.
	 * @param $locale string Locale
	 * @return string Copyright holder.
	 */
	function getDefaultCopyrightHolder($locale) {
		return $this->_getDefaultLicenseFieldValue($locale, PERMISSIONS_FIELD_COPYRIGHT_HOLDER);
	}

	/**
	 * Get the copyright year for this submission.
	 * @return string Copyright year.
	 */
	function getDefaultCopyrightYear() {
		return $this->_getDefaultLicenseFieldValue(null, PERMISSIONS_FIELD_COPYRIGHT_YEAR);
	}

	/**
	 * Get the best guess license field for this submission.
	 * Return the existing value if the field is already set,
	 * otherwise calculate a best value based on the context settings.
	 * @param $locale string Locale
	 * @param $field int PERMISSIONS_FIELD_... Which to return
	 * @return string|null Field value.
	 */
	function _getDefaultLicenseFieldValue($locale, $field) {
		// If already set, use the stored permissions info
		switch ($field) {
			case PERMISSIONS_FIELD_LICENSE_URL:
				$fieldValue = $this->getLicenseURL();
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_HOLDER:
				$fieldValue = $this->getCopyrightHolder($locale);
				break;
			case PERMISSIONS_FIELD_COPYRIGHT_YEAR:
				$fieldValue = $this->getCopyrightYear();
				break;
			default: assert(false);
		}

		if (!empty($fieldValue)) {
			if ($locale === null || !is_array($fieldValue)) return $fieldValue;
			if (isset($fieldValue[$locale])) return $fieldValue[$locale];
		}

		// Otherwise, get the permissions info from context settings.
		return $this->_getContextLicenseFieldValue($locale, $field);
	}


	//
	// Getters / setters
	//

	/**
	 * Get the context ID for this submission.
	 * @return int
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set the context ID for this submission.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		$this->setData('contextId', $contextId);
	}

	/**
	 * Get a piece of data for this object, localized to the current
	 * locale if possible.
	 * @param $key string
	 * @param $preferredLocale string
	 * @return mixed
	 */
	function &getLocalizedData($key, $preferredLocale = null) {
		if (is_null($preferredLocale)) $preferredLocale = AppLocale::getLocale();
		$localePrecedence = array($preferredLocale, $this->getLocale());
		foreach ($localePrecedence as $locale) {
			if (empty($locale)) continue;
			$value =& $this->getData($key, $locale);
			if (!empty($value)) return $value;
			unset($value);
		}

		// Fallback: Get the first available piece of data.
		$data =& $this->getData($key, null);
		if (!empty($data)) {
			$keys = array_keys($data);
			return $data[array_shift($keys)];
		}

		// No data available; return null.
		unset($data);
		$data = null;
		return $data;
	}

	/**
	 * Get stored public ID of the submission.
	 * @param @literal $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>). @endliteral
	 * @return int
	 */
	function getStoredPubId($pubIdType) {
		return $this->getData('pub-id::'.$pubIdType);
	}

	/**
	 * Set the stored public ID of the submission.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function setStoredPubId($pubIdType, $pubId) {
		$this->setData('pub-id::'.$pubIdType, $pubId);
	}

	/**
	 * Get stored copyright holder for the submission.
	 * @param $locale string locale
	 * @return string
	 */
	function getCopyrightHolder($locale) {
		return $this->getData('copyrightHolder', $locale);
	}

	/**
	 * Set the stored copyright holder for the submission.
	 * @param $copyrightHolder string Copyright holder
	 * @param $locale string locale
	 */
	function setCopyrightHolder($copyrightHolder, $locale) {
		$this->setData('copyrightHolder', $copyrightHolder, $locale);
	}

	/**
	 * Get stored copyright year for the submission.
	 * @return string
	 */
	function getCopyrightYear() {
		return $this->getData('copyrightYear');
	}

	/**
	 * Set the stored copyright year for the submission.
	 * @param $copyrightYear string Copyright holder
	 */
	function setCopyrightYear($copyrightYear) {
		$this->setData('copyrightYear', $copyrightYear);
	}

	/**
	 * Get stored license URL for the submission content.
	 * @return string
	 */
	function getLicenseURL() {
		return $this->getData('licenseURL');
	}

	/**
	 * Set the stored license URL for the submission content.
	 * @param $license string License of submission content
	 */
	function setLicenseURL($licenseURL) {
		$this->setData('licenseURL', $licenseURL);
	}

	/**
	 * Get comments to editor.
	 * @return string
	 */
	function getCommentsToEditor() {
		return $this->getData('commentsToEditor');
	}

	/**
	 * Return option selection indicating if author should be hidden in issue ToC.
	 * @return int AUTHOR_TOC_...
	 */
	function getHideAuthor() {
		return $this->getData('hideAuthor');
	}

	/**
	 * Set option selection indicating if author should be hidden in issue ToC.
	 * @param $hideAuthor int AUTHOR_TOC_...
	 */
	function setHideAuthor($hideAuthor) {
		$this->setData('hideAuthor', $hideAuthor);
	}

	/**
	 * Set comments to editor.
	 * @param $commentsToEditor string
	 */
	function setCommentsToEditor($commentsToEditor) {
		$this->setData('commentsToEditor', $commentsToEditor);
	}

	/**
	 * Return first author
	 * @param $lastOnly boolean return lastname only (default false)
	 * @return string
	 */
	function getFirstAuthor($lastOnly = false) {
		$authors = $this->getAuthors();
		if (is_array($authors) && !empty($authors)) {
			$author = $authors[0];
			return $lastOnly ? $author->getLastName() : $author->getFullName();
		} else {
			return null;
		}
	}

	/**
	 * Return string of author names, separated by the specified token
	 * @param $lastOnly boolean return list of lastnames only (default false)
	 * @param $nameSeparator string Separator for names (default comma+space)
	 * @param $userGroupSeparator string Separator for user groups (default semicolon+space)
	 * @return string
	 */
	function getAuthorString($lastOnly = false, $nameSeparator = ', ', $userGroupSeparator = '; ') {
		$authors = $this->getAuthors(true);

		$str = '';
		$lastUserGroupId = null;
		$author = null;
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		foreach($authors as $author) {
			if (!empty($str)) {
				if ($lastUserGroupId != $author->getUserGroupId()) {
					$userGroup = $userGroupDao->getById($lastUserGroupId);
					if ($userGroup->getShowTitle()) $str .= ' (' . $userGroup->getLocalizedName() . ')';
					$str .= $userGroupSeparator;
				} else {
					$str .= $nameSeparator;
				}
			}
			$str .= $lastOnly ? $author->getLastName() : $author->getFullName();
			$lastUserGroupId = $author->getUserGroupId();
		}

		// If there needs to be a trailing user group title, add it
		if ($author && $author->getShowTitle()) {
			$userGroup = $userGroupDao->getById($author->getUserGroupId());
			$str .= ' (' . $userGroup->getLocalizedName() . ')';
		}

		return $str;
	}

	/**
	 * Return short author names string.
	 * @return string
	 */
	function getShortAuthorString() {
		$primaryAuthor = $this->getPrimaryAuthor();
		$authors = $this->getAuthors();
		if (!isset($primaryAuthor)) {
			if (sizeof($authors) > 0) {
				$primaryAuthor = $authors[0];
			}
		}
		if (!$primaryAuthor) return '';

		$authorString = $primaryAuthor->getLastName();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
		if (count($authors) > 1) $authorString = __('submission.shortAuthor', array('author' => $authorString));
		return $authorString;
	}

	/**
	 * Return a list of author email addresses.
	 * @return array
	 */
	function getAuthorEmails() {
		$authors = $this->getAuthors();

		import('lib.pkp.classes.mail.Mail');
		$returner = array();
		foreach($authors as $author) {
			$returner[] = Mail::encodeDisplayName($author->getFullName()) . ' <' . $author->getEmail() . '>';
		}
		return $returner;
	}

	/**
	 * Get all authors of this submission.
	 * @param $onlyIncludeInBrowse boolean whether to limit to include_in_browse authors.
	 * @return array Authors
	 */
	function getAuthors($onlyIncludeInBrowse = false) {
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		if (!$onlyIncludeInBrowse)
			return $authorDao->getBySubmissionId($this->getId());
		else
			return $authorDao->getBySubmissionId($this->getId(), false, true);
	}

	/**
	 * Get the primary author of this submission.
	 * @return Author
	 */
	function getPrimaryAuthor() {
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		return $authorDao->getPrimaryContact($this->getId());
	}

	/**
	 * Get the locale of the submission.
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}

	/**
	 * Set the locale of the submission.
	 * @param $locale string
	 */
	function setLocale($locale) {
		$this->setData('locale', $locale);
	}

	/**
	 * Get "localized" submission title (if applicable).
	 * @param $preferredLocale string
	 * @return string
	 */
	function getLocalizedTitle($preferredLocale = null) {
		return $this->getLocalizedData('title', $preferredLocale);
	}

	/**
	 * Get title.
	 * @param $locale
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set title.
	 * @param $title string
	 * @param $locale
	 */
	function setTitle($title, $locale) {
		$this->setCleanTitle($title, $locale);
		$this->setData('title', $title, $locale);
	}

	/**
	 * Set 'clean' title (with punctuation removed).
	 * @param $cleanTitle string
	 * @param $locale
	 */
	function setCleanTitle($cleanTitle, $locale) {
		$punctuation = array ('"', '\'', ',', '.', '!', '?', '-', '$', '(', ')');
		$cleanTitle = str_replace($punctuation, '', $cleanTitle);
		$this->setData('cleanTitle', $cleanTitle, $locale);
	}

	/**
	 * Get the localized version of the subtitle
	 * @return string
	 */
	function getLocalizedSubtitle() {
		return $this->getLocalizedData('subtitle');
	}

	/**
	 * Get the subtitle for a given locale
	 * @param $locale string
	 * @return string
	 */
	function getSubtitle($locale) {
		return $this->getData('subtitle', $locale);
	}

	/**
	 * Set the subtitle for a locale
	 * @param $subtitle string
	 * @param $locale string
	 */
	function setSubtitle($subtitle, $locale) {
		$this->setData('subtitle', $subtitle, $locale);
	}

	/**
	 * Get the submission full title (with prefix, title
	 * and subtitle).
	 * @return string
	 */
	function getLocalizedFullTitle() {
		$fullTitle = null;
		if ($prefix = $this->getLocalizedPrefix()) {
			$fullTitle = $prefix . ' ';
		}

		$fullTitle .= $this->getLocalizedTitle();

		if ($subtitle = $this->getLocalizedSubtitle()) {
			$fullTitle = PKPString::concatTitleFields(array($fullTitle, $subtitle));
		}

		return $fullTitle;
	}

	/**
	 * Get the submission full title (with prefix, title
	 * and subtitle).
	 * @param $locale string Locale to fetch data in.
	 * @return string
	 */
	function getFullTitle($locale) {
		$fullTitle = null;
		if ($prefix = $this->getPrefix($locale)) {
			$fullTitle = $prefix . ' ';
		}

		$fullTitle .= $this->getTitle($locale);

		if ($subtitle = $this->getSubtitle($locale)) {
			$fullTitle = PKPString::concatTitleFields(array($fullTitle, $subtitle));
		}

		return $fullTitle;
	}

	/**
	 * Get "localized" submission prefix (if applicable).
	 * @return string
	 */
	function getLocalizedPrefix() {
		return $this->getLocalizedData('prefix');
	}

	/**
	 * Get prefix.
	 * @param $locale
	 * @return string
	 */
	function getPrefix($locale) {
		return $this->getData('prefix', $locale);
	}

	/**
	 * Set prefix.
	 * @param $prefix string
	 * @param $locale
	 */
	function setPrefix($prefix, $locale) {
		$this->setData('prefix', $prefix, $locale);
	}

	/**
	 * Get "localized" submission abstract (if applicable).
	 * @return string
	 */
	function getLocalizedAbstract() {
		return $this->getLocalizedData('abstract');
	}

	/**
	 * Get abstract.
	 * @param $locale
	 * @return string
	 */
	function getAbstract($locale) {
		return $this->getData('abstract', $locale);
	}

	/**
	 * Set abstract.
	 * @param $abstract string
	 * @param $locale
	 */
	function setAbstract($abstract, $locale) {
		$this->setData('abstract', $abstract, $locale);
	}

	/**
	 * Return the localized discipline
	 * @return string
	 */
	function getLocalizedDiscipline() {
		return $this->getLocalizedData('discipline');
	}

	/**
	 * Get discipline
	 * @param $locale
	 * @return string
	 */
	function getDiscipline($locale) {
		return $this->getData('discipline', $locale);
	}

	/**
	 * Set discipline
	 * @param $discipline string
	 * @param $locale
	 */
	function setDiscipline($discipline, $locale) {
		$this->setData('discipline', $discipline, $locale);
	}

	/**
	 * Return the localized subject
	 * @return string
	 */
	function getLocalizedSubject() {
		return $this->getLocalizedData('subject');
	}

	/**
	 * Get subject.
	 * @param $locale
	 * @return string
	 */
	function getSubject($locale) {
		return $this->getData('subject', $locale);
	}

	/**
	 * Set subject.
	 * @param $subject string
	 * @param $locale
	 */
	function setSubject($subject, $locale) {
		$this->setData('subject', $subject, $locale);
	}

	/**
	 * Return the localized coverage
	 * @return string
	 */
	function getLocalizedCoverage() {
		return $this->getLocalizedData('coverage');
	}

	/**
	 * Get coverage.
	 * @param $locale
	 * @return string
	 */
	function getCoverage($locale) {
		return $this->getData('coverage', $locale);
	}

	/**
	 * Set coverage.
	 * @param $coverage string
	 * @param $locale
	 */
	function setCoverage($coverage, $locale) {
		$this->setData('coverage', $coverage, $locale);
	}

	/**
	 * Return the localized type (method/approach)
	 * @return string
	 */
	function getLocalizedType() {
		return $this->getLocalizedData('type');
	}

	/**
	 * Get type (method/approach).
	 * @param $locale
	 * @return string
	 */
	function getType($locale) {
		return $this->getData('type', $locale);
	}

	/**
	 * Set type (method/approach).
	 * @param $type string
	 * @param $locale
	 */
	function setType($type, $locale) {
		$this->setData('type', $type, $locale);
	}

	/**
	 * Get rights.
	 * @param $locale
	 * @return string
	 */
	function getRights($locale) {
		return $this->getData('rights', $locale);
	}

	/**
	 * Set rights.
	 * @param $rights string
	 * @param $locale
	 */
	function setRights($rights, $locale) {
		$this->setData('rights', $rights, $locale);
	}

	/**
	 * Get source.
	 * @param $locale
	 * @return string
	 */
	function getSource($locale) {
		return $this->getData('source', $locale);
	}

	/**
	 * Set source.
	 * @param $source string
	 * @param $locale
	 */
	function setSource($source, $locale) {
		$this->setData('source', $source, $locale);
	}

	/**
	 * Get language.
	 * @return string
	 */
	function getLanguage() {
		return $this->getData('language');
	}

	/**
	 * Set language.
	 * @param $language string
	 */
	function setLanguage($language) {
		$this->setData('language', $language);
	}

	/**
	 * Return the localized sponsor
	 * @return string
	 */
	function getLocalizedSponsor() {
		return $this->getLocalizedData('sponsor');
	}

	/**
	 * Get sponsor.
	 * @param $locale
	 * @return string
	 */
	function getSponsor($locale) {
		return $this->getData('sponsor', $locale);
	}

	/**
	 * Set sponsor.
	 * @param $sponsor string
	 * @param $locale
	 */
	function setSponsor($sponsor, $locale) {
		$this->setData('sponsor', $sponsor, $locale);
	}

	/**
	 * Get the copyright notice for a given locale
	 * @param $locale string
	 * @return string
	 */
	function getCopyrightNotice($locale) {
		return $this->getData('copyrightNotice', $locale);
	}

	/**
	 * Set the copyright notice for a locale
	 * @param $copyrightNotice string
	 * @param $locale string
	 */
	function setCopyrightNotice($copyrightNotice, $locale) {
		$this->setData('copyrightNotice', $copyrightNotice, $locale);
	}

	/**
	 * Get citations.
	 * @return string
	 */
	function getCitations() {
		return $this->getData('citations');
	}

	/**
	 * Set citations.
	 * @param $citations string
	 */
	function setCitations($citations) {
		$this->setData('citations', $citations);
	}

	/**
	 * Get the localized cover filename
	 * @return string
	 */
	function getLocalizedFileName() {
		return $this->getLocalizedData('fileName');
	}

	/**
	 * get cover page server-side file name
	 * @param $locale string
	 * @return string
	 */
	function getFileName($locale) {
		return $this->getData('fileName', $locale);
	}

	/**
	 * set cover page server-side file name
	 * @param $fileName string
	 * @param $locale string
	 */
	function setFileName($fileName, $locale) {
		$this->setData('fileName', $fileName, $locale);
	}

	/**
	 * Get the localized submission cover width
	 * @return string
	 */
	function getLocalizedWidth() {
		return $this->getLocalizedData('width');
	}

	/**
	 * get width of cover page image
	 * @param $locale string
	 * @return string
	 */
	function getWidth($locale) {
		return $this->getData('width', $locale);
	}

	/**
	 * set width of cover page image
	 * @param $locale string
	 * @param $width int
	 */
	function setWidth($width, $locale) {
		$this->setData('width', $width, $locale);
	}

	/**
	 * Get the localized submission cover height
	 * @return string
	 */
	function getLocalizedHeight() {
		return $this->getLocalizedData('height');
	}

	/**
	 * get height of cover page image
	 * @param $locale string
	 * @return string
	 */
	function getHeight($locale) {
		return $this->getData('height', $locale);
	}

	/**
	 * set height of cover page image
	 * @param $locale string
	 * @param $height int
	 */
	function setHeight($height, $locale) {
		$this->setData('height', $height, $locale);
	}

	/**
	 * Get the localized cover filename on the uploader's computer
	 * @return string
	 */
	function getLocalizedOriginalFileName() {
		return $this->getLocalizedData('originalFileName');
	}

	/**
	 * get original file name
	 * @param $locale string
	 * @return string
	 */
	function getOriginalFileName($locale) {
		return $this->getData('originalFileName', $locale);
	}

	/**
	 * set original file name
	 * @param $originalFileName string
	 * @param $locale string
	 */
	function setOriginalFileName($originalFileName, $locale) {
		$this->setData('originalFileName', $originalFileName, $locale);
	}

	/**
	 * Get the localized cover alternate text
	 * @return string
	 */
	function getLocalizedCoverPageAltText() {
		return $this->getLocalizedData('coverPageAltText');
	}

	/**
	 * get cover page alternate text
	 * @param $locale string
	 * @return string
	 */
	function getCoverPageAltText($locale) {
		return $this->getData('coverPageAltText', $locale);
	}

	/**
	 * set cover page alternate text
	 * @param $coverPageAltText string
	 * @param $locale string
	 */
	function setCoverPageAltText($coverPageAltText, $locale) {
		$this->setData('coverPageAltText', $coverPageAltText, $locale);
	}

	/**
	 * Get the flag indicating whether or not to show
	 * a cover page.
	 * @return string
	 */
	function getLocalizedShowCoverPage() {
		return $this->getLocalizedData('showCoverPage');
	}

	/**
	 * get show cover page
	 * @param $locale string
	 * @return int
	 */
	function getShowCoverPage($locale) {
		return $this->getData('showCoverPage', $locale);
	}

	/**
	 * set show cover page
	 * @param $showCoverPage int
	 * @param $locale string
	 */
	function setShowCoverPage($showCoverPage, $locale) {
		$this->setData('showCoverPage', $showCoverPage, $locale);
	}

	/**
	 * get hide cover page thumbnail in Toc
	 * @param $locale string
	 * @return int
	 */
	function getHideCoverPageToc($locale) {
		return $this->getData('hideCoverPageToc', $locale);
	}

	/**
	 * set hide cover page thumbnail in Toc
	 * @param $hideCoverPageToc int
	 * @param $locale string
	 */
	function setHideCoverPageToc($hideCoverPageToc, $locale) {
		$this->setData('hideCoverPageToc', $hideCoverPageToc, $locale);
	}

	/**
	 * get hide cover page in abstract view
	 * @param $locale string
	 * @return int
	 */
	function getHideCoverPageAbstract($locale) {
		return $this->getData('hideCoverPageAbstract', $locale);
	}

	/**
	 * set hide cover page in abstract view
	 * @param $hideCoverPageAbstract int
	 * @param $locale string
	 */
	function setHideCoverPageAbstract($hideCoverPageAbstract, $locale) {
		$this->setData('hideCoverPageAbstract', $hideCoverPageAbstract, $locale);
	}

	/**
	 * Get localized hide cover page in abstract view
	 */
	function getLocalizedHideCoverPageAbstract() {
		return $this->getLocalizedData('hideCoverPageAbstract');
	}

	/**
	 * Get submission date.
	 * @return date
	 */
	function getDateSubmitted() {
		return $this->getData('dateSubmitted');
	}

	/**
	 * Set submission date.
	 * @param $dateSubmitted date
	 */
	function setDateSubmitted($dateSubmitted) {
		$this->setData('dateSubmitted', $dateSubmitted);
	}

	/**
	 * Get the date of the last status modification.
	 * @return date
	 */
	function getDateStatusModified() {
		return $this->getData('dateStatusModified');
	}

	/**
	 * Set the date of the last status modification.
	 * @param $dateModified date
	 */
	function setDateStatusModified($dateModified) {
		$this->setData('dateStatusModified', $dateModified);
	}

	/**
	 * Get the date of the last modification.
	 * @return date
	 */
	function getLastModified() {
		return $this->getData('lastModified');
	}

	/**
	 * Set the date of the last modification.
	 * @param $dateModified date
	 */
	function setLastModified($dateModified) {
		$this->setData('lastModified', $dateModified);
	}

	/**
	 * Stamp the date of the last modification to the current time.
	 */
	function stampModified() {
		return $this->setLastModified(Core::getCurrentDate());
	}

	/**
	 * Stamp the date of the last status modification to the current time.
	 */
	function stampStatusModified() {
		return $this->setDateStatusModified(Core::getCurrentDate());
	}

	/**
	 * Get submission status.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set submission status.
	 * @param $status int
	 */
	function setStatus($status) {
		$this->setData('status', $status);
	}

	/**
	 * Get a map for status constant to locale key.
	 * @return array
	 */
	function &getStatusMap() {
		static $statusMap;
		if (!isset($statusMap)) {
			$statusMap = array(
				STATUS_QUEUED => 'submissions.queued',
				STATUS_PUBLISHED => 'submissions.published',
				STATUS_DECLINED => 'submissions.declined',
				STATUS_INCOMPLETE => 'submissions.incomplete'
			);
		}
		return $statusMap;
	}

	/**
	 * Get a locale key for the paper's current status.
	 * @return string
	 */
	function getStatusKey() {
		$statusMap =& $this->getStatusMap();
		return $statusMap[$this->getStatus()];
	}

	/**
	 * Get submission progress (most recently completed submission step).
	 * @return int
	 */
	function getSubmissionProgress() {
		return $this->getData('submissionProgress');
	}

	/**
	 * Set submission progress.
	 * @param $submissionProgress int
	 */
	function setSubmissionProgress($submissionProgress) {
		$this->setData('submissionProgress', $submissionProgress);
	}

	/**
	 * get pages
	 * @return string
	 */
	function getPages() {
		return $this->getData('pages');
	}

	/**
	 * set pages
	 * @param $pages string
	 */
	function setPages($pages) {
		$this->setData('pages',$pages);
	}

	/**
	 * Get the submission's current publication stage ID
	 * @return int
	 */
	function getStageId() {
		return $this->getData('stageId');
	}

	/**
	 * Set the submission's current publication stage ID
	 * @param $stageId int
	 */
	function setStageId($stageId) {
		$this->setData('stageId', $stageId);
	}

	/**
	 * Get date published.
	 * @return date
	 */
	function getDatePublished() {
		return $this->getData('datePublished');
	}

	/**
	 * Set date published.
	 * @param $datePublished date
	 */
	function setDatePublished($datePublished) {
		return $this->SetData('datePublished', $datePublished);
	}

	/**
	 * Initialize the copyright and license metadata for a submission.
	 * This should be called at creation and at publication, to setup
	 * license/copyright holder and copyright year, respectively.
	 * This depends on the permissions configuration in Setup, and
	 * (potentially) on the authors of a submission being populated.
	 * Only initializes empty fields because of the getDefault...()
	 * behaviour, so subsequent calls are safe.
	 */
	function initializePermissions() {
		$this->setLicenseURL($this->getDefaultLicenseURL());
		$this->setCopyrightHolder($this->getDefaultCopyrightHolder(null), null);
		if ($this->getStatus() == STATUS_PUBLISHED) {
			$this->setCopyrightYear($this->getDefaultCopyrightYear());
		}
	}

	/**
	 * Determines whether or not the license for copyright on this submission is
	 * a Creative Commons license or not.
	 * @return boolean
	 */
	function isCCLicense() {
		return preg_match('/creativecommons\.org/i', $this->getLicenseURL());
	}


	//
	// Abstract methods.
	//
	/**
	 * Get section id.
	 * @return int
	 */
	abstract function getSectionId();

	/**
	 * Get the value of a license field from the containing context.
	 * @param $locale string Locale code
	 * @param $field PERMISSIONS_FIELD_...
	 * @return string|null
	 */
	abstract function _getContextLicenseFieldValue($locale, $field);
}

?>
