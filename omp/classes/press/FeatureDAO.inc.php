<?php

/**
 * @file classes/press/FeatureDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FeatureDAO
 * @ingroup press
 * @see Feature
 *
 * @brief Operations for setting Featured status on various items.
 */

class FeatureDAO extends DAO {
	/**
	 * Constructor
	 */
	function FeatureDAO() {
		parent::DAO();
	}

	/**
	 * Insert a new feature.
	 * @param $monographId int
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @param $seq int
	 */
	function insertFeature($monographId, $assocType, $assocId, $seq) {
		$this->update(
			'INSERT INTO features
				(monograph_id, $assocType, $assocId, $seq)
				VALUES
				(?, ?, ?, ?)',
			array(
				(int) $monographId,
				(int) $assocType,
				(int) $assocId,
				(int) $seq
			)
		);
	}

	/**
	 * Delete a feature by ID.
	 * @param $featureId int
	 * @param $pressId int optional
	 */
	function deleteByMonographId($monographId) {
		$this->update(
			'DELETE FROM features WHERE monograph_id = ?',
			(int) $monographId
		);
	}

	/**
	 * Delete a feature by association.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function deleteByAssoc($assocType, $assocId) {
		$this->update(
			'DELETE FROM features WHERE assoc_type = ? AND assoc_id = ?',
			array((int) $assocType, (int) $assocId)
		);
	}

	/**
	 * Delete a feature.
	 * @param $monographId int
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function deleteFeature($monographId, $assocType, $assocId) {
		$this->update(
			'DELETE FROM features
			WHERE	monograph_id = ? AND
				assoc_type = ? AND
				assoc_id = ?',
			array(
				(int) $monographId,
				(int) $assocType,
				(int) $assocId
			)
		);
	}

	/**
	 * Resequence features by association.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function resequenceByAssoc($assocType, $assocId) {
		$result =& $this->retrieve(
			'SELECT monograph_id FROM features WHERE assoc_type = ? AND assoc_id = ? ORDER BY seq',
			array((int) $assocType, (int) $assocId)
		);

		for ($i=2; !$result->EOF; $i+=2) {
			list($monographId) = $result->fields;
			$this->update(
				'UPDATE features SET seq = ? WHERE monograph_id = ? AND assoc_type = ? AND assoc_id = ?',
				array(
					$i,
					$monographId,
					(int) $assocType,
					(int) $assocId
				)
			);

			$result->MoveNext();
		}

		$result->Close();
		unset($result);
	}
}

?>
