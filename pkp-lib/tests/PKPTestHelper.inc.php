<?php
/**
 * @file tests/PKPTestHelper.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TestHelper
 * @ingroup tests
 *
 * @brief Class that implements functionality common to all PKP test types.
 */

abstract class PKPTestHelper {

	//
	// Public helper methods
	//
	/**
	 * Backup the given tables.
	 * @param $tables array
	 * @param $test PHPUnit_Framework_Assert
	 */
	public static function backupTables($tables, $test) {
		$dao = new DAO();
		$driver = Config::getVar('database', 'driver');
		foreach ($tables as $table) {
			switch ($driver) {
				case 'mysql':
					$createLikeSql = "CREATE TABLE backup_$table LIKE $table";
					break;
				case 'postgres':
					$createLikeSql = "CREATE TABLE backup_$table (LIKE $table)";
					break;
				default:
					$test->fail("Unknown driver \"$driver\"");
					return;
			}

			$sqls = array(
				$createLikeSql,
				"INSERT INTO backup_$table SELECT * FROM $table"
			);
			foreach ($sqls as $sql) {
				if (!$dao->update($sql, false, true, false)) {
					$test->fail("Error while backing up $table: offending SQL is '$sql'");
				}
			}
		}
	}

	/**
	 * Restore the given tables.
	 * @param $tables array
	 * @param $test PHPUnit_Framework_Assert
	 */
	public static function restoreTables($tables, $test) {
		$dao = new DAO();
		foreach ($tables as $table) {
			$sqls = array(
				"TRUNCATE TABLE $table",
				"INSERT INTO $table SELECT * FROM backup_$table",
				"DROP TABLE backup_$table"
			);
			foreach ($sqls as $sql) {
				if (!$dao->update($sql, false, true, false)) {
					$test->fail("Error while restoring $table: offending SQL is '$sql'");
				}
			}
		}
	}

	/**
	 * Some 3rd-party libraries (i.e. adodb)
	 * use the PHP @ operator a lot which can lead
	 * to test failures when xdebug's scream parameter
	 * is on. This helper method can be used to safely
	 * (de)activate this.
	 *
	 * If the xdebug extension is not installed then
	 * this method does nothing.
	 *
	 * @param $scream boolean
	 */
	public static function xdebugScream($scream) {
		if (extension_loaded('xdebug')) {
			static $previous = null;
			if ($scream) {
				assert(!is_null($previous));
				ini_set('xdebug.scream', $previous);
			} else {
				$previous = ini_get('xdebug.scream');
				ini_set('xdebug.scream', false);
			}
		}
	}
}
?>
