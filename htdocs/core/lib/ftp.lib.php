<?php
/* Copyright (C) 2022-2023	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2022	    Anthony Berton       	<bertonanthony@gmail.com>
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
 * @return	array					Result of connect
 */
function dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section, $ftp_passive = 0)
{
	global $langs, $conf;

	$ok = 1;
	$error = 0;
	$connect_id = null;
	$newsectioniso = '';
	$mesg="";

	if (!is_numeric($ftp_port)) {
		$mesg = $langs->transnoentitiesnoconv("FailedToConnectToFTPServer", $ftp_server, $ftp_port);
		$ok = 0;
	}

	if ($ok) {
		$connecttimeout = (!getDolGlobalString('FTP_CONNECT_TIMEOUT') ? 40 : $conf->global->FTP_CONNECT_TIMEOUT);
		if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
			dol_syslog('Try to connect with ssh2_connect');
			$tmp_conn_id = ssh2_connect($ftp_server, $ftp_port);
		} elseif (getDolGlobalString('FTP_CONNECT_WITH_SSL')) {
			dol_syslog('Try to connect with ftp_ssl_connect');
			$connect_id = ftp_ssl_connect($ftp_server, $ftp_port, $connecttimeout);
		} else {
			dol_syslog('Try to connect with ftp_connect');
			$connect_id = ftp_connect($ftp_server, $ftp_port, $connecttimeout);
		}
		if (!empty($connect_id) || !empty($tmp_conn_id)) {
			if ($ftp_user) {
				if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
					dol_syslog('Try to authenticate with ssh2_auth_password');
					if (ssh2_auth_password($tmp_conn_id, $ftp_user, $ftp_password)) {
						// Turn on passive mode transfers (must be after a successful login
						//if ($ftp_passive) ftp_pasv($connect_id, true);

						// Change the dir
						$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
						//ftp_chdir($connect_id, $newsectioniso);
						$connect_id = ssh2_sftp($tmp_conn_id);
						if (!$connect_id) {
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
					if (ftp_login($connect_id, $ftp_user, $ftp_password)) {
						// Turn on passive mode transfers (must be after a successful login
						if ($ftp_passive) {
							ftp_pasv($connect_id, true);
						}

						// Change the dir
						$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
						ftp_chdir($connect_id, $newsectioniso);
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

	$arrayresult = array('conn_id'=>$connect_id, 'ok'=>$ok, 'mesg'=>$mesg, 'curdir'=>$section, 'curdiriso'=>$newsectioniso);
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
 * @return		boolean						Result of closing
 */
function dol_ftp_close($connect_id)
{
	global $conf;

	// Close FTP connection
	if ($connect_id) {
		if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		} elseif (getDolGlobalString('FTP_CONNECT_WITH_SSL')) {
			return ftp_close($connect_id);
		} else {
			return ftp_close($connect_id);
		}
	}
	return true;
}

/**
 * Delete a FTP file
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$file			File
 * @param 		string		$newsection			$newsection
 * @return		bool
 */
function dol_ftp_delete($connect_id, $file, $newsection)
{
	global $conf;

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		$newsection = ssh2_sftp_realpath($connect_id, ".").'/./'; // workaround for bug https://bugs.php.net/bug.php?id=64169
	}

	// Remote file
	$filename = $file;
	$remotefile = $newsection.(preg_match('@[\\\/]$@', $newsection) ? '' : '/').$file;
	$newremotefileiso = mb_convert_encoding($remotefile, 'ISO-8859-1');

	//print "x".$newremotefileiso;
	dol_syslog("ftp/index.php ftp_delete ".$newremotefileiso);
	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		return ssh2_sftp_unlink($connect_id, $newremotefileiso);
	} else {
		return @ftp_delete($connect_id, $newremotefileiso);
	}
}

/**
 * Download a FTP file
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$localfile		The local file path
 * @param 		string		$file					The remote file path
 * @param 		string		$newsection			$newsection
 * @return		bool|resource
 */
function dol_ftp_get($connect_id, $localfile, $file, $newsection)
{
	global $conf;

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		$newsection = ssh2_sftp_realpath($connect_id, ".").'/./'; // workaround for bug https://bugs.php.net/bug.php?id=64169
	}

	// Remote file
	$filename = $file;
	$remotefile = $newsection.(preg_match('@[\\\/]$@', $newsection) ? '' : '/').$file;
	$newremotefileiso = mb_convert_encoding($remotefile, 'ISO-8859-1');

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		return fopen('ssh2.sftp://'.intval($connect_id).$newremotefileiso, 'r');
	} else {
		return ftp_get($connect_id, $localfile, $newremotefileiso, FTP_BINARY);
	}
}

/**
 * Upload a FTP file
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$file			File name
 * @param 		string		$localfile		The path to the local file
 * @param 		string		$newsection		$newsection
 * @return		bool
 */
function dol_ftp_put($connect_id, $file, $localfile, $newsection)
{
	global $conf;

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		$newsection = ssh2_sftp_realpath($connect_id, ".").'/./'; // workaround for bug https://bugs.php.net/bug.php?id=64169
	}

	// Remote file
	$filename = $file;
	$remotefile = $newsection.(preg_match('@[\\\/]$@', $newsection) ? '' : '/').$file;
	$newremotefileiso = mb_convert_encoding($remotefile, 'ISO-8859-1');

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		return ssh2_scp_send($connect_id, $localfile, $newremotefileiso, 0644);
	} else {
		return ftp_put($connect_id, $newremotefileiso, $localfile, FTP_BINARY);
	}
}

/**
 * Remove FTP directory
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$file			File
 * @param 		string		$newsection			$newsection
 * @return		bool
 */
function dol_ftp_rmdir($connect_id, $file, $newsection)
{
	global $conf;

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		$newsection = ssh2_sftp_realpath($connect_id, ".").'/./'; // workaround for bug https://bugs.php.net/bug.php?id=64169
	}

	// Remote file
	$filename = $file;
	$remotefile = $newsection.(preg_match('@[\\\/]$@', $newsection) ? '' : '/').$file;
	$newremotefileiso = mb_convert_encoding($remotefile, 'ISO-8859-1');

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		return ssh2_sftp_rmdir($connect_id, $newremotefileiso);
	} else {
		return @ftp_rmdir($connect_id, $newremotefileiso);
	}
}


/**
 * Remove FTP directory
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$newdir			Dir create
 * @param 		string		$newsection		$newsection
 * @return		bool|string
 */
function dol_ftp_mkdir($connect_id, $newdir, $newsection)
{
	global $conf;

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		$newsection = ssh2_sftp_realpath($connect_id, ".").'/./'; // workaround for bug https://bugs.php.net/bug.php?id=64169
	}

	// Remote file
	$newremotefileiso = $newsection.(preg_match('@[\\\/]$@', $newsection) ? '' : '/').$newdir;
	$newremotefileiso = mb_convert_encoding($newremotefileiso, 'ISO-8859-1');

	if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
		return ssh2_sftp_mkdir($connect_id, $newremotefileiso, 0777);
	} else {
		return @ftp_mkdir($connect_id, $newremotefileiso);
	}
}
