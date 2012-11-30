<?php
/**
 * @file classes/press/PressDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PressDAO
 * @ingroup press
 * @see Press
 *
 * @brief Operations for retrieving and modifying Press objects.
 */

import('classes.press.Press');
import('lib.pkp.classes.core.ContextDAO');

class PressDAO extends ContextDAO {
	/**
	 * Constructor
	 */
	function PressDAO() {
		parent::ContextDAO();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Press
	 */
	function newDataObject() {
		return new Press();
	}

	/**
	 * Internal function to return a Press object from a row.
	 * @param $row array
	 * @return Press
	 */
	function _fromRow($row) {
		$press = parent::_fromRow($row);
		$press->setPrimaryLocale($row['primary_locale']);
		$press->setEnabled($row['enabled']);
		HookRegistry::call('PressDAO::_fromRow', array(&$press, &$row));
		return $press;
	}

	/**
	 * Insert a new press.
	 * @param $press Press
	 */
	function insertObject(&$press) {
		$this->update(
			'INSERT INTO presses
				(path, seq, enabled, primary_locale)
				VALUES
				(?, ?, ?, ?)',
			array(
				$press->getPath(),
				(int) $press->getSequence(),
				(int) $press->getEnabled(),
				$press->getPrimaryLocale()
			)
		);

		$press->setId($this->getInsertId());
		return $press->getId();
	}

	/**
	 * Update an existing press.
	 * @param $press Press
	 */
	function updateObject(&$press) {
		return $this->update(
			'UPDATE presses
				SET
					path = ?,
					seq = ?,
					enabled = ?,
					primary_locale = ?
				WHERE press_id = ?',
			array(
				$press->getPath(),
				(int) $press->getSequence(),
				(int) $press->getEnabled(),
				$press->getPrimaryLocale(),
				(int) $press->getId()
			)
		);
	}

	/**
	 * Retrieve all enabled presses
	 * @return array Presses ordered by sequence
	 */
	function &getEnabledPresses() {
		$result =& $this->retrieve(
			'SELECT * FROM presses WHERE enabled=1 ORDER BY seq'
		);

		$resultFactory = new DAOResultFactory($result, $this, '_fromRow');
		return $resultFactory;
	}

	/**
	 * Delete a press by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $pressId int
	 */
	function deleteById($pressId) {
		$pressSettingsDao =& DAORegistry::getDAO('PressSettingsDAO');
		$pressSettingsDao->deleteById($pressId);

		$seriesDao =& DAORegistry::getDAO('SeriesDAO');
		$seriesDao->deleteByPressId($pressId);

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByPress($pressId);

		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$monographDao->deleteByPressId($pressId);

		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userGroupDao->deleteAssignmentsByContextId($pressId);
		$userGroupDao->deleteByContextId($pressId);

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->deleteByPressId($pressId);

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormDao->deleteByAssocId(ASSOC_TYPE_PRESS, $pressId);

		$genreDao =& DAORegistry::getDAO('GenreDAO');
		$genreDao->deleteByPressId($pressId);

		$featureDao =& DAORegistry::getDAO('FeatureDAO');
		$featureDao->deleteByAssoc(ASSOC_TYPE_PRESS, $pressId);

		$newReleaseDao =& DAORegistry::getDAO('NewReleaseDAO');
		$newReleaseDao->deleteByAssoc(ASSOC_TYPE_PRESS, $pressId);

		$this->update('DELETE FROM press_defaults WHERE press_id = ?', (int) $pressId);

		parent::deleteById($pressId);
	}

	//
	// Private functions
	//
	/**
	 * Get the table name for this context.
	 * @return string
	 */
	protected function _getTableName() {
		return 'presses';
	}

	/**
	 * Get the table name for this context's settings table.
	 * @return string
	 */
	protected function _getSettingsTableName() {
		return 'press_settings';
	}

	/**
	 * Get the name of the primary key column for this context.
	 * @return string
	 */
	protected function _getPrimaryKeyColumn() {
		return 'press_id';
	}
}

?>
