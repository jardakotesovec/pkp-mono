<?php

/**
 * @file classes/rt/RTStruct.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTVersion
 * @ingroup rt
 * @see RT
 *
 * @brief Data structures associated with the Reading Tools component.
 */


/**
 * RT Version entity.
 */
class RTVersion {

	/** @var mixed unique identifier */
	var $versionId;

	/** @var string key */
	var $key;

	/** @var string locale key */
	var $locale;

	/** @var string version title */
	var $title;

	/** @var string version description */
	var $description;

	/** @var array RTContext version contexts */
	var $contexts = array();


	/**
	 * Add an RT Context to this version.
	 * @param $context RTContext
	 */
	function addContext($context) {
		array_push($this->contexts, $context);
	}

	function &getContexts() {
		return $this->contexts;
	}

	function setContexts(&$contexts) {
		$this->contexts =& $contexts;
	}

	function setVersionId($versionId) {
		$this->versionId = $versionId;
	}

	function getVersionId() {
		return $this->versionId;
	}

	function setTitle($title) {
		$this->title = $title;
	}

	function getTitle() {
		return $this->title;
	}

	function setLocale($locale) {
		$this->locale = $locale;
	}

	function getLocale() {
		return $this->locale;
	}

	function setKey($key) {
		$this->key = $key;
	}

	function getKey() {
		return $this->key;
	}

	function setDescription($description) {
		$this->description = $description;
	}

	function getDescription() {
		return $this->description;
	}
}

/**
 * RT Context entity.
 */
class RTContext {

	/** @var mixed unique identifier */
	var $contextId;

	/** @var mixed unique version identifier */
	var $versionId;

	/** @var string context title */
	var $title;

	/** @var string context abbreviation */
	var $abbrev;

	/** @var string context description */
	var $description;

	/** @var boolean default search terms to author names */
	var $authorTerms = false;

	/** @var boolean default search terms to geo indexing data */
	var $geoTerms = false;

	/** @var boolean default use as define terms context */
	var $defineTerms = false;

	/** @var boolean default use as "cited by" context */
	var $citedBy = false;

	/** @var int ordering of this context within version */
	var $order = 0;

	/** @var array RTSearch context searches */
	var $searches = array();


	/**
	 * Add an RT Search to this context.
	 * @param $search RTSearch
	 */
	function addSearch($search) {
		array_push($this->searches, $search);
	}

	function &getSearches() {
		return $this->searches;
	}

	function setSearches(&$searches) {
		$this->searches =& $searches;
	}

	function setContextId($contextId) {
		$this->contextId = $contextId;
	}

	function getContextId() {
		return $this->contextId;
	}

	function setVersionId($versionId) {
		$this->versionId = $versionId;
	}

	function getVersionId() {
		return $this->versionId;
	}

	function setTitle($title) {
		$this->title = $title;
	}

	function getTitle() {
		return $this->title;
	}

	function setAbbrev($abbrev) {
		$this->abbrev = $abbrev;
	}

	function getAbbrev() {
		return $this->abbrev;
	}

	function setDescription($description) {
		$this->description = $description;
	}

	function getDescription() {
		return $this->description;
	}

	function getCitedBy() {
		return $this->citedBy;
	}

	function setCitedBy($citedBy) {
		$this->citedBy = $citedBy;
	}

	function getAuthorTerms() {
		return $this->authorTerms;
	}

	function setAuthorTerms($authorTerms) {
		$this->authorTerms = $authorTerms;
	}

	function getGeoTerms() {
		return $this->geoTerms;
	}

	function setGeoTerms($geoTerms) {
		$this->geoTerms = $geoTerms;
	}

	function getDefineTerms() {
		return $this->defineTerms;
	}

	function setDefineTerms($defineTerms) {
		$this->defineTerms = $defineTerms;
	}

	function getOrder() {
		return $this->order;
	}

	function setOrder($order) {
		$this->order = $order;
	}
}

/**
 * RT Search entity.
 */
class RTSearch {

	/** @var mixed unique identifier */
	var $searchId;

	/** @var mixed unique context identifier */
	var $contextId;

	/** @var string site title */
	var $title;

	/** @var string site description */
	var $description;

	/** @var string site URL */
	var $url;

	/** @var string search URL */
	var $searchUrl;

	/** @var string search POST body */
	var $searchPost;

	/** @var int ordering of this search within context */
	var $order = 0;

	/* Getter / Setter Functions */
	function getSearchId() {
		return $this->searchId;
	}

	function setSearchId($searchId) {
		$this->searchId = $searchId;
	}

	function getContextId() {
		return $this->contextId;
	}

	function setContextId($contextId) {
		$this->contextId = $contextId;
	}

	function getTitle() {
		return $this->title;
	}

	function setTitle($title) {
		$this->title = $title;
	}

	function getDescription() {
		return $this->description;
	}

	function setDescription($description) {
		$this->description = $description;
	}

	function getUrl() {
		return $this->url;
	}

	function setUrl($url) {
		$this->url = $url;
	}

	function getSearchUrl() {
		return $this->searchUrl;
	}

	function setSearchUrl($searchUrl) {
		$this->searchUrl = $searchUrl;
	}

	function getSearchPost() {
		return $this->searchPost;
	}

	function setSearchPost($searchPost) {
		$this->searchPost = $searchPost;
	}

	function getOrder() {
		return $this->order;
	}

	function setOrder($order) {
		$this->order = $order;
	}
}

?>
