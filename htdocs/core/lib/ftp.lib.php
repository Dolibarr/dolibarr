<?php
/* Copyright (C) 2022	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2022	Anthony Berton       	<bertonanthony@gmail.com>
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
 *	\file       htdocs/core/lib/ftp.lib.php
 *	\brief      Set of functions used for FTP
 *	\ingroup    core
 */




/**
 * Connect to FTP server
 *
 * @param 	string	$ftp_server		Server name
 * @param 	string	$ftp_port		Server port
 * @param 	string	$ftp_user		FTP user
 * @param 	string	$ftp_password	FTP password
 * @param 	string	$section		Directory
 * @param	integer	$ftp_passive	Use a passive mode
 * @return	int 	<0 if OK, >0 if KO
 */
function dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section, $ftp_passive = 0)
{
	global $langs, $conf;

	$ok = 1;
	$error = 0;
	$conn_id = null;
	$newsectioniso = '';
	$mesg="";

	if (!is_numeric($ftp_port)) {
		$mesg = $langs->transnoentitiesnoconv("FailedToConnectToFTPServer", $ftp_server, $ftp_port);
		$ok = 0;
	}

	if ($ok) {
		$connecttimeout = (empty($conf->global->FTP_CONNECT_TIMEOUT) ? 40 : $conf->global->FTP_CONNECT_TIMEOUT);
		if (!empty($conf->global->FTP_CONNECT_WITH_SFTP)) {
			dol_syslog('Try to connect with ssh2_connect');
			$tmp_conn_id = ssh2_connect($ftp_server, $ftp_port);
		} elseif (!empty($conf->global->FTP_CONNECT_WITH_SSL)) {
			dol_syslog('Try to connect with ftp_ssl_connect');
			$conn_id = ftp_ssl_connect($ftp_server, $ftp_port, $connecttimeout);
		} else {
			dol_syslog('Try to connect with ftp_connect');
			$conn_id = ftp_connect($ftp_server, $ftp_port, $connecttimeout);
		}
		if (!empty($conn_id) || !empty($tmp_conn_id)) {
			if ($ftp_user) {
				if (!empty($conf->global->FTP_CONNECT_WITH_SFTP)) {
					dol_syslog('Try to authenticate with ssh2_auth_password');
					if (ssh2_auth_password($tmp_conn_id, $ftp_user, $ftp_password)) {
						// Turn on passive mode transfers (must be after a successful login
						//if ($ftp_passive) ftp_pasv($conn_id, true);

						// Change the dir
						$newsectioniso = utf8_decode($section);
						//ftp_chdir($conn_id, $newsectioniso);
						$conn_id = ssh2_sftp($tmp_conn_id);
						if (!$conn_id) {
							dol_syslog('Failed to connect to SFTP after sssh authentication', LOG_DEBUG);
							$mesg = $langs->transnoentitiesnoconv("FailedToConnectToSFTPAfterSSHAuthentication");
							$ok = 0;
							$error++;
						}
					} else {
						dol_syslog('Failed to connect to FTP with login '.$ftp_user, LOG_DEBUG);
						$mesg = $langs->transnoentitiesnoconv("FailedToConnectToFTPServerWithCredentials");
						$ok = 0;
						$error++;
					}
				} else {
					if (ftp_login($conn_id, $ftp_user, $ftp_password)) {
						// Turn on passive mode transfers (must be after a successful login
						if ($ftp_passive) {
							ftp_pasv($conn_id, true);
						}

						// Change the dir
						$newsectioniso = utf8_decode($section);
						ftp_chdir($conn_id, $newsectioniso);
					} else {
						$mesg = $langs->transnoentitiesnoconv("FailedToConnectToFTPServerWithCredentials");
						$ok = 0;
						$error++;
					}
				}
			}
		} else {
			dol_syslog('FailedToConnectToFTPServer '.$ftp_server.' '.$ftp_port, LOG_ERR);
			$mesg = $langs->transnoentitiesnoconv("FailedToConnectToFTPServer", $ftp_server, $ftp_port);
			$ok = 0;
		}
	}

	$arrayresult = array('conn_id'=>$conn_id, 'ok'=>$ok, 'mesg'=>$mesg, 'curdir'=>$section, 'curdiriso'=>$newsectioniso);
	return $arrayresult;
}


/**
 * Tell if an entry is a FTP directory
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$dir			Directory
 * @return		int			1=directory, 0=not a directory
 */
function ftp_isdir($connect_id, $dir)
{
	if (@ftp_chdir($connect_id, $dir)) {
		ftp_cdup($connect_id);
		return 1;
	} else {
		return 0;
	}
}

/**
 * Tell if an entry is a FTP directory
 *
 * @param 		resource	$connect_id		Connection handler
 */
function dol_ftp_close($connect_id)
{
	// Close FTP connection
	if ($connect_id) {
		if (!empty($conf->global->FTP_CONNECT_WITH_SFTP)) {
		} elseif (!empty($conf->global->FTP_CONNECT_WITH_SSL)) {
			return ftp_close($connect_id);
		} else {
			return ftp_close($connect_id);
		}
	}
}