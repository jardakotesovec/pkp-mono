<?php

/**
 * @file classes/plugins/ThemePlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThemePlugin
 * @ingroup plugins
 *
 * @brief Abstract class for theme plugins
 */

import('lib.pkp.classes.plugins.LazyLoadPlugin');

abstract class ThemePlugin extends LazyLoadPlugin {
	/**
	 * Collection of styles
	 *
	 * @see self::_registerStyles
	 * @param array $styles
	 */
	public $styles = array();

	/**
	 * Collection of scripts
	 *
	 * @see self::_registerScripts
	 * @param array $scripts
	 */
	public $scripts = array();

	/**
	 * Constructor
	 */
	function ThemePlugin() {
		parent::Plugin();
	}

	/**
	 * @copydoc Plugin::register
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;

		// Don't perform any futher operations if theme is not enabled
		if (!$this->getEnabled() || defined('SESSION_DISABLE_INIT')) {
			return false;
		}

		// Fire an initialization method which themes should use to add
		// styles, scripts and fonts
		$this->init();

		$this->_registerTemplates();
		$this->_registerStyles();
		$this->_registerScripts();

		return true;
	}

	/**
	 * The primary method themes should use to add styles, scripts and fonrts,
	 * or register hooks. This method is only fired for the currently active
	 * theme.
	 *
	 * @return null
	 */
	public abstract function init();

	/**
	 * Determine whether or not this plugin is enabled
	 *
	 * This only returns true if the theme is currently the selected theme
	 * in a given context.
	 *
	 * @return boolean
	 */
	public function getEnabled() {
		if (!parent::getEnabled()) {
			return false;
		}

		$request = $this->getRequest();
		$context = $request->getContext();
		$activeTheme = $context->getSetting('themePluginPath');

		return $activeTheme == basename($this->getPluginPath());
	}

	/**
	 * Add a stylesheet to load with this theme
	 *
	 * Style paths with a .less extension will be compiled and redirected to
	 * the compiled file.
	 *
	 * @param string $name A name for this stylesheet
	 * @param string $path The path to this stylesheet, relative to the theme
	 * @param array $args Optional arguments hash. Supported args:
	 *   'context': Whether to load this on the `frontend` or `backend`.
	 *      default: `frontend`
	 *   'priority': Controls order in which styles are printed
	 *   'addLess': Additional LESS files to process before compiling. Array
	 */
	public function addStyle($name, $path, $args = array()) {

		// Pass a file path for LESS files
		if (substr($path, -4) == 'less' ) {
			$fullPath = $this->_getBaseDir($path);

		// Pass a URL for other files
		} else {
			$fullPath = $this->_getBaseUrl($path);
		}

		$this->styles[$name] = array(
			'path' => $fullPath,
			'context' => isset($args['context']) && $args['context'] == 'backend' ? 'backend' : 'frontend',
			'priority' => isset($args['priority']) ? $args['priority'] : STYLE_SEQUENCE_NORMAL,
			'addLess' => isset($args['addLess']) ? $args['addLess'] : array(),
			'baseUrl' => isset($args['baseUrl']) ? $args['baseUrl'] : '',
		);
	}

	/**
	 * Add a script to load with this theme
	 *
	 * @param string $name A name for this script
	 * @param string $path The path to this script, relative to the theme
	 * @param array $args Optional arguments hash. Supported args:
	 *   string $context Whether to load this on the `frontend` or `backend`.
	 *      default: `frontend`
	 *   int $priority Controls order in which styles are printed
	 */
	public function addScript($name, $path, $args = array()) {

		// @todo cast arg variable types
		$this->scripts[$name] = array(
			'path'     => $this->_getBaseUrl($path),
			'context'  => isset($args['context']) && $args['context'] == 'backend' ? 'backend' : 'frontend',
			'priority' => isset($args['priority']) ? $args['priority'] : STYLE_SEQUENCE_NORMAL,
		);
	}

	/**
	 * Register directories to search for template files
	 *
	 * @return null
	 */
	private function _registerTemplates() {

		// Register parent theme template directory
		if (method_exists('parent', 'registerTemplates')) {
			parent::registerTemplates();
		}

		// Register this theme's template directory
		$request = $this->getRequest();
		$templateManager = TemplateManager::getManager($request);
		array_unshift(
			$templateManager->template_dir,
			$this->_getBaseDir('templates')
		);
	}

	/**
	 * Register stylesheets and font assets
	 *
	 * @return null
	 */
	private function _registerStyles() {

		$request = $this->getRequest();
		$dispatcher = $request->getDispatcher();
		$templateManager = TemplateManager::getManager($request);

		foreach($this->styles as $name => $style) {

			// Compile LESS files
			if (substr($style['path'], -4) == 'less') {
				$url = $dispatcher->url(
					$request,
					ROUTE_COMPONENT,
					null,
					'page.PageHandler',
					'css',
					null,
					array(
						'name' => $name,
					)
				);
			} else {
				$url = $request->_getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . $path;
			}

			$templateManager->addStylesheet(
				$url,
				$style['priority'],
				$style['context']
			);
		}
	}

	/**
	 * Register script assets
	 *
	 * @return null
	 */
	public function _registerScripts() {
		// @todo
	}

	/**
	 * Get the base URL to be used for file references in LESS stylesheets
	 *
	 * {$baseUrl} will be replaced with this URL before LESS files are processed
	 *
	 * @param $path string An optional path to append to the base
	 * @return string
	 */
	public function _getBaseUrl($path = '') {
		$request = $this->getRequest();
		$path = empty($path) ? '' : DIRECTORY_SEPARATOR . $path;
		return $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . $path;
	}

	/**
	 * Get the base path to be used for file references
	 *
	 * @param $path string An optional path to append to the base
	 * @return string
	 */
	public function _getBaseDir($path = '') {
		$path = empty($path) ? '' : DIRECTORY_SEPARATOR . $path;
		return Core::getBaseDir() . DIRECTORY_SEPARATOR . $this->getPluginPath() . $path;
	}
}

?>
