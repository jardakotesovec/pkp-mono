<?php

/**
 * @defgroup cache Cache
 * Implements various forms of caching, i.e. object caches, file caches, etc.
 */

/**
 * @file classes/cache/FileCache.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileCache
 * @ingroup cache
 *
 * @brief Provides caching based on machine-generated PHP code on the filesystem.
 */


import('lib.pkp.classes.cache.GenericCache');

class FileCache extends GenericCache {
	/**
	 * Connection to use for caching.
	 */
	var $filename;

	/**
	 * The cached data
	 */
	var $cache;

	/**
	 * Instantiate a cache.
	 */
	function FileCache($context, $cacheId, $fallback, $path) {
		parent::GenericCache($context, $cacheId, $fallback);

		$this->filename = $path . DIRECTORY_SEPARATOR . "fc-$context-" . str_replace('/', '.', $cacheId) . '.php';

		// Load the cache data if it exists. (To avoid a race condition,
		// we assume the file exists and suppress the potential warn.)
		$result = @include($this->filename);
		if ($result !== false) {
			$this->cache = $result;
		} else {
			$this->cache = null;
		}
	}

	/**
	 * Flush the cache
	 */
	function flush() {
		unset($this->cache);
		$this->cache = null;
		if (file_exists($this->filename)) {
			@unlink($this->filename);
		}
	}

	/**
	 * Get an object from the cache.
	 * @param $id
	 */
	function getCache($id) {
		if (!isset($this->cache)) return $this->cacheMiss;
		return (isset($this->cache[$id])?$this->cache[$id]:null);
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 * @param $value
	 */
	function setCache($id, $value) {
		// Flush the cache; it will be regenerated on demand.
		$this->flush();
	}

	/**
	 * Set the entire contents of the cache.
	 */
	function setEntireCache(&$contents) {
		file_put_contents($this->filename, '<?php return ' . var_export($contents, true) . '; ?>', LOCK_EX);
		$umask = Config::getVar('files', 'umask');
		if ($umask) @chmod($this->filename, FILE_MODE_MASK & ~$umask);

		$this->cache =& $contents;
	}

	/**
	 * Get the time at which the data was cached.
	 * If the file does not exist or an error occurs, null is returned.
	 * @return int
	 */
	function getCacheTime() {
		if (!file_exists($this->filename)) return null;
		$result = filemtime($this->filename);
		if ($result === false) return null;
		return ((int) $result);
	}

	/**
	 * Get the entire contents of the cache in an associative array.
	 */
	function &getContents() {
		if (!isset($this->cache)) {
			// Trigger a cache miss to load the cache.
			$this->get(null);
		}
		return $this->cache;
	}
}

?>
