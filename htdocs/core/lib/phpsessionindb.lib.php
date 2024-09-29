<?php
/* Copyright (C) 2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/phpsessionindb.lib.php
 *  \ingroup    core
 *  \brief		Set function handlers for PHP session management in DB.
 */

// This session handler file must be included just after the call of the master.inc.php into main.inc.php
// The $conf is already defined from conf.php file.
// To use it set
// - create table ll_session from the llx_session-disabled.sql file
// - uncomment the include DOL_DOCUMENT_ROOT.'/core/lib/phpsessionindb.inc.php into main.inc.php
// - in your PHP.ini, set:  session.save_handler = user
// The session_set_save_handler() at end of this file will replace default session management.


/**
 * The session open handler called by PHP whenever a session is initialized.
 *
 * @param	string	$save_path      Value of session.save_path into php.ini
 * @param	string	$session_name	Session name (Example: 'DOLSESSID_xxxxxx')
 * @return	boolean					Always true
 */
function dolSessionOpen($save_path, $session_name)
{
	global $dbsession;

	global $dolibarr_main_db_type, $dolibarr_main_db_host;
	global $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name, $dolibarr_main_db_port;

	global $dolibarr_session_db_type, $dolibarr_session_db_host;
	global $dolibarr_session_db_user, $dolibarr_session_db_pass, $dolibarr_session_db_name, $dolibarr_session_db_port;

	if (empty($dolibarr_session_db_type)) {
		$dolibarr_session_db_type = $dolibarr_main_db_type;
	}
	if (empty($dolibarr_session_db_host)) {
		$dolibarr_session_db_host = $dolibarr_main_db_host;
	}
	if (empty($dolibarr_session_db_user)) {
		$dolibarr_session_db_user = $dolibarr_main_db_user;
	}
	if (empty($dolibarr_session_db_pass)) {
		$dolibarr_session_db_pass = $dolibarr_main_db_pass;
	}
	if (empty($dolibarr_session_db_name)) {
		$dolibarr_session_db_name = $dolibarr_main_db_name;
	}
	if (empty($dolibarr_session_db_port)) {
		$dolibarr_session_db_port = $dolibarr_main_db_port;
	}
	//var_dump('open '.$database_name.' '.$table_name);

	$dbsession = getDoliDBInstance($dolibarr_session_db_type, $dolibarr_session_db_host, $dolibarr_session_db_user, $dolibarr_session_db_pass, $dolibarr_session_db_name, (int) $dolibarr_session_db_port);

	return true;
}

/**
 * This function is called whenever a session_start() call is made and reads the session variables.
 *
 * @param	string		$sess_id	Session ID
 * @return	string					Returns "" when a session is not found  or (serialized)string if session exists
 */
function dolSessionRead($sess_id)
{
	global $dbsession;
	global $sessionlastvalueread;
	global $sessionidfound;

	$sql = "SELECT session_id, session_variable FROM ".MAIN_DB_PREFIX."session";
	$sql .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";

	// Execute the query
	$resql = $dbsession->query($sql);
	$num_rows = $dbsession->num_rows($resql);
	if ($num_rows == 0) {
		// No session found - return an empty string
		$sessionlastvalueread = '';
		$sessionidfound = '';
		return '';
	} else {
		// Found a session - return the serialized string
		$obj = $dbsession->fetch_object($resql);
		$sessionlastvalueread = $obj->session_variable;
		$sessionidfound = $obj->session_id;
		//var_dump($sessionlastvalueread);
		//var_dump($sessionidfound);
		return $obj->session_variable;
	}
}

/**
 * This function is called when a session is initialized with a session_start(  ) call, when variables are registered or unregistered,
 * and when session variables are modified. Returns true on success.
 *
 * @param	string		$sess_id		Session iDecodeStream
 * @param	string		$val			Content of session
 * @return	boolean						Always true
 */
function dolSessionWrite($sess_id, $val)
{
	global $dbsession;
	global $sessionlastvalueread;
	global $sessionidfound;

	//var_dump('write '.$sess_id);
	//var_dump($val);
	//var_dump('sessionlastvalueread='.$sessionlastvalueread.' sessionidfound='.$sessionidfound);

	//$sessionlastvalueread='';
	if ($sessionlastvalueread != $val) {
		$time_stamp = dol_now();

		if (empty($sessionidfound)) {
			// No session found, insert a new one
			$insert_query = "INSERT INTO ".MAIN_DB_PREFIX."session";
			$insert_query .= "(session_id, session_variable, last_accessed, fk_user, remote_ip, user_agent)";
			$insert_query .= " VALUES ('".$dbsession->escape($sess_id)."', '".$dbsession->escape($val)."', '".$dbsession->idate($time_stamp)."', 0, '".$dbsession->escape(getUserRemoteIP())."', '".$dbsession->escape(substr($_SERVER['HTTP_USER_AGENT'], 0, 255))."')";

			$result = $dbsession->query($insert_query);
			if (!$result) {
				dol_print_error($dbsession);
				return false;
			}
		} else {
			if ($sessionidfound != $sess_id) {
				// oops. How can this happen ?
				dol_print_error($dbsession, 'Oops sess_id received in dolSessionWrite differs from the cache value $sessionidfound. How can this happen ?');
				return false;
			}
			/*$sql = "SELECT session_id, session_variable FROM ".MAIN_DB_PREFIX."session";
			$sql .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";

			// Execute the query
			$resql = $dbsession->query($sql);
			$num_rows = $dbsession->num_rows($resql);
			if ($num_rows == 0) {
			// No session found, insert a new one
			$insert_query = "INSERT INTO ".MAIN_DB_PREFIX."session";
			$insert_query .= "(session_id, session_variable, last_accessed, fk_user, remote_ip, user_agent)";
			$insert_query .= " VALUES ('".$dbsession->escape($sess_id)."', '".$dbsession->escape($val)."', '".$dbsession->idate($time_stamp)."', 0, '".$dbsession->escape(getUserRemoteIP())."', '".$dbsession->escape(substr($_SERVER['HTTP_USER_AGENT'], 0, 255)."')";
			//var_dump($insert_query);
			$result = $dbsession->query($insert_query);
			if (!$result) {
				dol_print_error($dbsession);
				return false;
			}
			} else {
			*/
			// Existing session found - Update the session variables
			$update_query = "UPDATE ".MAIN_DB_PREFIX."session";
			$update_query .= " SET session_variable = '".$dbsession->escape($val)."',";
			$update_query .= " last_accessed = '".$dbsession->idate($time_stamp)."',";
			$update_query .= " remote_ip = '".$dbsession->escape(getUserRemoteIP())."',";
			$update_query .= " user_agent = '".$dbsession->escape($_SERVER['HTTP_USER_AGENT'])."'";
			$update_query .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";

			$result = $dbsession->query($update_query);
			if (!$result) {
				dol_print_error($dbsession);
				return false;
			}
		}
	}

	return true;
}

/**
 * This function is executed on shutdown of the session.
 *
 * @return	boolean					Always returns true.
 */
function dolSessionClose()
{
	global $dbsession;

	//var_dump('close');

	$dbsession->close();

	return true;
}

/**
 * This is called whenever the session_destroy() function call is made. Returns true if the session has successfully been deleted.
 *
 * @param	string	$sess_id		Session iDecodeStream
 * @return	boolean					Always true
 */
function dolSessionDestroy($sess_id)
{
	global $dbsession;

	//var_dump('destroy');

	$delete_query = "DELETE FROM ".MAIN_DB_PREFIX."session";
	$delete_query .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";
	$dbsession->query($delete_query);

	return true;
}

/**
 * This function is called on a session's start up with the probability specified in session.gc_probability.
 * Performs garbage collection by removing all sessions that haven't been updated in the last $max_lifetime seconds as set in session.gc_maxlifetime.
 *
 * @param	int		$max_lifetime		Max lifetime
 * @return	boolean						true if the DELETE query succeeded.
 */
function dolSessionGC($max_lifetime)
{
	global $dbsession;

	$time_stamp = dol_now();

	$delete_query = "DELETE FROM ".MAIN_DB_PREFIX."session";
	$delete_query .= " WHERE last_accessed < '".$dbsession->idate($time_stamp - $max_lifetime)."'";

	$resql = $dbsession->query($delete_query);
	if ($resql) {
		return true;
	} else {
		return false;
	}
}

// Call to register user call back functions.
session_set_save_handler("dolSessionOpen", "dolSessionClose", "dolSessionRead", "dolSessionWrite", "dolSessionDestroy", "dolSessionGC"); // @phpstan-ignore-line
