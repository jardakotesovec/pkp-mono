<?php

/**
 * Validation.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 *
 * Class providing user validation/authentication operations. 
 *
 * $Id$
 */

class Validation {

	/**
	 * Authenticate user credentials and mark the user as logged in in the current session.
	 * @param $username string
	 * @param $password string encrypted password
	 * @param $remember boolean remember a user's session past the current browser session
	 * @return User the User associated with the login credentials, or false if the credentials are invalid
	 */
	function &login($username, $password, $remember = false) {
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$user = &$userDao->getUserByCredentials($username, Validation::encryptCredentials($username, $password));
		
		if (!isset($user)) {
			// Login credentials are invalid
			return false;
			
		} else {
			// The user is valid, mark user as logged in in current session
			$sessionManager = &SessionManager::getManager();
			
			// Regenerate session ID first
			$sessionManager->regenerateSessionId();
			
			$session = &$sessionManager->getUserSession();
			$session->setSessionVar('userId', $user->getUserId());
			$session->setUserId($user->getUserId());
			$session->setSessionVar('username', $user->getUsername());
			$session->setRemember($remember);
			
			if ($remember && Config::getVar('general', 'session_lifetime') > 0) {
				// Update session expiration time
				$sessionManager->updateSessionLifetime(time() +  Config::getVar('general', 'session_lifetime') * 86400);
			}
			return $user;
		}
	}
	
	/**
	 * Mark the user as logged out in the current session.
	 * @return boolean
	 */
	function logout() {
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$session->unsetSessionVar('userId');
		$session->setUserId(null);
		
		if ($session->getRemember()) {
			$session->setRemember(false);
			$sessionManager->updateSessionLifetime(0);
		}
			
		$sessionDao = &DAORegistry::getDAO('SessionDAO');
		$sessionDao->updateSession($session);
		
		return true;
	}
	
	/**
	 * Check if the current session belongs to a logged in user or not.
	 * @return boolean
	 */
	function isLoggedIn() {
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		
		$userId = $session->getUserId();
		return isset($userId) && !empty($userId);
	}
	
	/**
	 * Check if a user is authorized to access the specified role in the specified journal.
	 * @param $roleId int
	 * @param $journalId optional (e.g., for global site admin role), the ID of the journal
	 * @return boolean
	 */
	function isAuthorized($roleId, $journalId = 0) {
		if (!Validation::isLoggedIn()) {
			return false;
		}
		
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$user = &$session->getUser();
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		return $roleDao->roleExists($journalId, $user->getUserId(), $roleId);
	}
	
	/**
	 * Encrypt user passwords for database storage.
	 * The username is used as a unique salt to make dictionary
	 * attacks against a compromised database more difficult.
	 * @param $username string username
	 * @param $password string unencrypted password
	 * @return string encrypted password
	 */
	function encryptCredentials($username, $password) {
		return md5($username . $password);
	}
	
	/**
	 * Shortcut for checking authorization as site admin.
	 * @return boolean
	 */
	function isSiteAdmin() {
		return Validation::isAuthorized(ROLE_ID_SITE_ADMIN);
	}
	
	/**
	 * Shortcut for checking authorization as journal manager.
	 * @param $journalId int
	 * @return boolean
	 */
	function isJournalManager($journalId) {
		return Validation::isAuthorized(ROLE_ID_JOURNAL_MANAGER, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as editor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isEditor($journalId) {
		return Validation::isAuthorized(ROLE_ID_EDITOR, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as section editor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isSectionEditor($journalId) {
		return Validation::isAuthorized(ROLE_ID_SECTION_EDITOR, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as layout editor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isLayoutEditor($journalId) {
		return Validation::isAuthorized(ROLE_ID_LAYOUT_EDITOR, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as reviewer.
	 * @param $journalId int
	 * @return boolean
	 */
	function isReviewer($journalId) {
		return Validation::isAuthorized(ROLE_ID_REVIEWER, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as copyeditor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isCopyeditor($journalId) {
		return Validation::isAuthorized(ROLE_ID_COPYEDITOR, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as proofreader.
	 * @param $journalId int
	 * @return boolean
	 */
	function isProofreader($journalId) {
		return Validation::isAuthorized(ROLE_ID_PROOFREADER, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as author.
	 * @param $journalId int
	 * @return boolean
	 */
	function isAuthor($journalId) {
		return Validation::isAuthorized(ROLE_ID_AUTHOR, $journalId);
	}
	
	/**
	 * Shortcut for checking authorization as reader.
	 * @param $journalId int
	 * @return boolean
	 */
	function isReader($journalId) {
		return Validation::isAuthorized(ROLE_ID_READER, $journalId);
	}
	
}

?>
