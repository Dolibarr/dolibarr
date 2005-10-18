<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 *
 *
 * Recupération des fichiers CDR
 *
 */
require ("../../master.inc.php");

$ftp_server    = GETCDR_FTP_SERVER;
$ftp_user_name = GETCDR_FTP_USER;
$ftp_user_pass = GETCDR_FTP_PASS;

// Mise en place d'une connexion basique
$conn_id = ftp_connect($ftp_server);

// Identification avec un nom d'utilisateur et un mot de passe
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

// Vérification de la connexion
if (!$conn_id)
{
  echo "La connexion FTP a échouée !";
  echo "Tentative de connexion au serveur $ftp_server";
  exit;
}

// Vérification de la connexion
if (!$login_result)
{
  echo "L'authentification  FTP a échouée !";
  echo "Tentative de connexion au serveur $ftp_server pour l'utilisateur $ftp_user_name";
  exit;
}

$date = time() - (24 * 3600); 

$file = "daily_report_".strftime("%d-%m-%Y", $date).".zip";

$remote_file = 'cdr/'.$file;
$local_file = DOL_DATA_ROOT.'/telephonie/CDR/atraiter/'.$file;
$handle = fopen($local_file, 'w');

if (ftp_fget($conn_id, $handle, $remote_file, FTP_ASCII, 0))
{
  //echo "Le chargement a réussi dans ".$local_file."\n";
}
else
{
  echo "Echec de recuperation du fichier ".$remote_file."\n";
}

// Fermeture du flux FTP
ftp_close($conn_id);
?>
