<?php
/* Copyright (C) 2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// The session handler file must be included just after the call of the master.inc.php into main.inc.php
// The $conf is already defined from conf.php file.
// To use it set in your PHP.ini:  session.save_handler = user

/**
 * The session open handler called by PHP whenever a session is initialized.
 *
 * @param	string	$database_name  Database NamedConstraint
 * @param	string	$table_name		Table name
 * @return	boolean					Always true
 */
function dolSessionOpen($database_name, $table_name)
{
	global $conf, $dbsession;

	$dbsession = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, $conf->db->port);

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

	$sql = "SELECT session_variable FROM ".MAIN_DB_PREFIX."session";
	$sql .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";

	// Execute the query
	$resql = $dbsession->query($sql);
	$num_rows = $dbsession->num_rows($resql);
	if ($num_rows == 0) {
		// No session found - return an empty string
		return '';
	} else {
		// Found a session - return the serialized string
		$obj = $dbsession->fetch_object($resql);
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

	$time_stamp = dol_now();

	$sql = "SELECT session_id FROM ".MAIN_DB_PREFIX."session";
	$sql .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";

	// Execute the query
	$resql = $dbsession->query($sql);
	$num_rows = $dbsession->num_rows($resql);
	if ($num_rows == 0) {
		// No session found, insert a new one
		$insert_query = "INSERT INTO ".MAIN_DB_PREFIX."session";
		$insert_query .= "(session_id, session_variable, last_accessed)";
		$insert_query .= " VALUES ('".$dbsession->escape($sess_id)."', '".$dbsession->escape($val)."', '".$dbsession->idate($time_stamp)."')";
		$dbsession->query($insert_query);
	} else {
		// Existing session found - Update the session variables
		$update_query = "UPDATE ".MAIN_DB_PREFIX."session";
		$update_query .= "SET session_variable = '".$dbsession->escape($val)."',";
		$update_query .= " last_accessed = '".$dbsession->idate($time_stamp)."'";
		$update_query .= " WHERE session_id = '".$dbsession->escape($sess_id)."'";
		$dbsession->query($update_query);
	}
	return true;
}

/**
 * This function is executed on shutdown of the session.
 *
 * @param	string		$sess_id	Session ID
 * @return	boolean					Always returns true.
 */
function dolSessionClose($sess_id)
{
	global $dbsession;

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
session_set_save_handler("dolSessionOpen", "dolSessionClose", "dolSessionRead", "dolSessionWrite", "dolSessionDestroy", "dolSessionGC");
