<?php

/**
 * @file StaticPagesDAO.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesDAO
 * Operations for retrieving and modifying StaticPages objects.
 */
import('lib.pkp.classes.db.DAO');

class StaticPagesDAO extends DAO {
	/** @var Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 * @param $parentPluginName string Name of static pages plugin
	 */
	function StaticPagesDAO($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Get a static page by ID
	 * @param $staticPageId int Static page ID
	 * @param $contextId int Optional context ID
	 */
	function getById($staticPageId, $contextId = null) {
		$params = array((int) $staticPageId);
		if ($contextId) $params[] = $contextId;

		$result = $this->retrieve(
			'SELECT * FROM static_pages WHERE static_page_id = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get a set of static pages by context ID
	 * @param $contextId int
	 * @param $rangeInfo Object optional
	 * @return DAOResultFactory
	 */
	function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM static_pages WHERE context_id = ?',
			(int) $contextId,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get a static page by path.
	 * @param $contextId int Context ID
	 * @param $path string Path
	 * @return StaticPage
	 */
	function getByPath($contextId, $path) {
		$result = $this->retrieve(
			'SELECT * FROM static_pages WHERE context_id = ? AND path = ?',
			array((int) $contextId, $path)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Insert a static page.
	 * @param $staticPage StaticPage
	 * @return int Inserted static page ID
	 */
	function insertObject($staticPage) {
		$this->update(
			'INSERT INTO static_pages
				(context_id, path)
				VALUES
				(?, ?)',
			array(
				$staticPage->getContextId(),
				$staticPage->getPath()
			)
		);

		$staticPage->setId($this->getInsertId());
		$this->updateLocaleFields($staticPage);

		return $staticPage->getId();
	}

	/**
	 * Update the database with a static page object
	 * @param $staticPage StaticPage
	 */
	function updateObject($staticPage) {
		$this->update(
			'UPDATE static_pages
				SET
					context_id = ?,
					path = ?
				WHERE static_page_id = ?',
				array(
					$staticPage->getContextId(),
					$staticPage->getPath(),
					$staticPage->getId()
				)
			);
		$this->updateLocaleFields($staticPage);
	}

	/**
	 * Delete a static page by ID.
	 * @param $staticPageId int
	 */
	function deleteById($staticPageId) {
		$this->update(
			'DELETE FROM static_pages WHERE static_page_id = ?',
			(int) $staticPageId
		);
		$this->update(
			'DELETE FROM static_page_settings WHERE static_page_id = ?',
			(int) $staticPageId
		);
	}

	/**
	 * Delete a static page object.
	 * @param $staticPage StaticPage
	 */
	function deleteObject($staticPage) {
		$this->deleteById($staticPage->getId());
	}

	/**
	 * Generate a new static page object.
	 * @return StaticPage
	 */
	function newDataObject() {
		$staticPagesPlugin = PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$staticPagesPlugin->import('StaticPage');
		return new StaticPage();
	}

	/**
	 * Return a new static pages object from a given row.
	 * @return StaticPage
	 */
	function _fromRow($row) {
		$staticPage = $this->newDataObject();
		$staticPage->setId($row['static_page_id']);
		$staticPage->setPath($row['path']);
		$staticPage->setContextId($row['context_id']);

		$this->getDataObjectSettings('static_page_settings', 'static_page_id', $row['static_page_id'], $staticPage);
		return $staticPage;
	}

	/**
	 * Get the insert ID for the last inserted static page.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('static_pages', 'static_page_id');
	}

	/**
	 * Get field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'content');
	}

	/**
	 * Update the localized data for this object
	 * @param $author object
	 */
	function updateLocaleFields(&$staticPage) {
		$this->updateDataObjectSettings('static_page_settings', $staticPage, array(
			'static_page_id' => $staticPage->getId()
		));
	}

	/**
	 * Find duplicate path
	 * @param $path string Path to check
	 * @param contextId int Context ID to check
	 * @param $staticPageId	int Optional static page ID to exclude from test
	 * @return boolean
	 */
	function duplicatePathExists ($path, $contextId, $staticPageId = null) {
		$params = array((int) $contextId, $path);
		if ($staticPageId) $params[] = (int) $staticPageId;

		$result = $this->retrieve(
			'SELECT	*
			FROM	static_pages
			WHERE	context_id = ?
			AND	path = ?' .
			($staticPageId?' AND static_page_id <> ?':''),
			$params
		);

		if ($result->RecordCount() == 0) {
			return false;
		}
		return true;
	}
}

?>
