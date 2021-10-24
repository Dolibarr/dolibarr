<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/install/etape2.php
 *		\ingroup	install
 *      \brief      Cree les tables, cles primaires, cles etrangeres, index et fonctions en base puis charge les donnees de reference
 *      \version    $Id$
 */

include("./inc.php");
require_once($dolibarr_main_document_root."/lib/databases/".$dolibarr_main_db_type.".lib.php");
require_once($dolibarr_main_document_root."/core/conf.class.php");
require_once($dolibarr_main_document_root."/lib/admin.lib.php");

$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);		// Disable all errors
//error_reporting(E_ALL);
@set_time_limit(300);	// Need more than 240 on Windows 7/64
error_reporting($err);

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");

if ($dolibarr_main_db_type == "mysql")  $choix=1;
if ($dolibarr_main_db_type == "mysqli") $choix=1;
if ($dolibarr_main_db_type == "pgsql")  $choix=2;
if ($dolibarr_main_db_type == "mssql")  $choix=3;

// Init "forced values" to nothing. "forced values" are used after a Doliwamp install wizard.
if (file_exists("./install.forced.php")) include_once("./install.forced.php");

dolibarr_install_syslog("etape2: Entering etape2.php page");


/*
 *	View
 */

pHeader($langs->trans("CreateDatabaseObjects"),"etape4");


if ($_POST["action"] == "set")
{
	print '<h3>'.$langs->trans("Database").'</h3>';

	print '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
	$error=0;

	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
	if ($db->connected == 1)
	{
		print "<tr><td>";
		print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td>".$langs->trans("OK")."</td></tr>";
		$ok = 1 ;
	}
	else
	{
		print "<tr><td>Erreur lors de la creation de : $dolibarr_main_db_name</td><td>".$langs->trans("Error")."</td></tr>";
	}

	if ($ok)
	{
		if($db->database_selected == 1)
		{

			dolibarr_install_syslog("etape2: Connexion successful to database : $dolibarr_main_db_name");
		}
		else
		{
			$ok = 0 ;
		}
	}


	// Affiche version
	if ($ok)
	{
		$version=$db->getVersion();
		$versionarray=$db->getVersionArray();
		print '<tr><td>'.$langs->trans("DatabaseVersion").'</td>';
		print '<td>'.$version.'</td></tr>';
		//print '<td align="right">'.join('.',$versionarray).'</td></tr>';
	}

	$requestnb=0;

	// To disable some code
	$createtables=1;
	$createkeys=1;
	$createfunctions=1;
	$createdata=1;

	/**************************************************************************************
	 *
	 * Chargement fichiers tables/*.sql (non *.key.sql)
	 * A faire avant les fichiers *.key.sql
	 *
	 ***************************************************************************************/
	if ($ok && $createtables)
	{
		// We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
		$dir = "mysql/tables/";

		$ok = 0;
		$handle=opendir($dir);
		dolibarr_install_syslog("Open tables directory ".$dir." handle=".$handle,LOG_DEBUG);
		$tablefound = 0;
		$tabledata=array();
		while (($file = readdir($handle))!==false)
		{
			if (preg_match('/\.sql$/i',$file) && preg_match('/^llx_/i',$file) && ! preg_match('/\.key\.sql$/i',$file))
			{
				$tablefound++;
				$tabledata[]=$file;
			}
		}
		closedir($handle);

		// Sort list of data files on alphabetical order (load order is important)
		sort($tabledata);
		foreach($tabledata as $file)
		{
			$name = substr($file, 0, strlen($file) - 4);
			$buffer = '';
			$fp = fopen($dir.$file,"r");
			if ($fp)
			{
				while (!feof ($fp))
				{
					$buf = fgets($fp, 4096);
					if (substr($buf, 0, 2) <> '--')
					{
						if ($choix != 1)	// All databases except Mysql
						{
							$buf=$db->convertSQLFromMysql($buf);
						}

						$buffer .= $buf;
					}
				}
				fclose($fp);

				$buffer=trim($buffer);

				//print "<tr><td>Creation de la table $name/td>";
				$requestnb++;
				if ($conf->file->character_set_client == "UTF-8")
				{
					$buffer=utf8_encode($buffer);
				}

				dolibarr_install_syslog("Request: ".$buffer,LOG_DEBUG);
				$resql=$db->query($buffer);
				if ($resql)
				{
					// print "<td>OK requete ==== $buffer</td></tr>";
					$db->free($resql);
				}
				else
				{
					if ($db->errno() == 'DB_ERROR_TABLE_ALREADY_EXISTS' ||
					$db->errno() == 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS')
					{
						//print "<td>Deja existante</td></tr>";
					}
					else
					{
						print "<tr><td>".$langs->trans("CreateTableAndPrimaryKey",$name);
						print "<br>\n".$langs->trans("Request").' '.$requestnb.' : '.$buffer;
						print "\n</td>";
						print "<td>".$langs->trans("Error")." ".$db->errno()." ".$db->error()."</td></tr>";
						$error++;
					}
				}
			}
			else
			{
				print "<tr><td>".$langs->trans("CreateTableAndPrimaryKey",$name);
				print "</td>";
				print "<td>".$langs->trans("Error")." Failed to open file ".$dir.$file."</td></tr>";
				$error++;
				dolibarr_install_syslog("Failed to open file ".$dir.$file,LOG_ERR);
			}
		}

		if ($tablefound)
		{
			if ($error == 0)
			{
				print '<tr><td>';
				print $langs->trans("TablesAndPrimaryKeysCreation").'</td><td>'.$langs->trans("OK").'</td></tr>';
				$ok = 1;
			}
		}
		else
		{
			print "<tr><td>".$langs->trans("ErrorFailedToFindSomeFiles",$dir)."</td><td>".$langs->trans("Error")."</td></tr>";
			dolibarr_install_syslog("Failed to find files to create database in directory ".$dir,LOG_ERR);
		}
	}


	/***************************************************************************************
	 *
	 * Chargement fichiers tables/*.key.sql
	 * A faire apres les fichiers *.sql
	 *
	 ***************************************************************************************/
	if ($ok && $createkeys)
	{
		// We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
		$dir = "mysql/tables/";

		$okkeys = 0;
		$handle=opendir($dir);
		dolibarr_install_syslog("Open keys directory ".$dir." handle=".$handle,LOG_DEBUG);
		$tablefound = 0;
		$tabledata=array();
		while (($file = readdir($handle))!==false)
		{
			if (preg_match('/\.sql$/i',$file) && preg_match('/^llx_/i',$file) && preg_match('/\.key\.sql$/i',$file))
			{
				$tablefound++;
				$tabledata[]=$file;
			}
		}
		closedir($handle);

		// Sort list of data files on alphabetical order (load order is important)
		sort($tabledata);
		foreach($tabledata as $file)
		{
			$name = substr($file, 0, strlen($file) - 4);
			//print "<tr><td>Creation de la table $name</td>";
			$buffer = '';
			$fp = fopen($dir.$file,"r");
			if ($fp)
			{
				while (!feof ($fp))
				{
					$buf = fgets($fp, 4096);

					// Cas special de lignes autorisees pour certaines versions uniquement
					if ($choix == 1 && preg_match('/^--\sV([0-9\.]+)/i',$buf,$reg))
					{
						$versioncommande=explode('.',$reg[1]);
						//print var_dump($versioncommande);
						//print var_dump($versionarray);
						if (sizeof($versioncommande) && sizeof($versionarray)
						&& versioncompare($versioncommande,$versionarray) <= 0)
						{
							// Version qualified, delete SQL comments
							$buf=preg_replace('/^--\sV([0-9\.]+)/i','',$buf);
							//print "Ligne $i qualifiee par version: ".$buf.'<br>';
						}
					}
					if ($choix == 2 && preg_match('/^--\sPOSTGRESQL\sV([0-9\.]+)/i',$buf,$reg))
					{
						$versioncommande=explode('.',$reg[1]);
						//print var_dump($versioncommande);
						//print var_dump($versionarray);
						if (sizeof($versioncommande) && sizeof($versionarray)
						&& versioncompare($versioncommande,$versionarray) <= 0)
						{
							// Version qualified, delete SQL comments
							$buf=preg_replace('/^--\sPOSTGRESQL\sV([0-9\.]+)/i','',$buf);
							//print "Ligne $i qualifiee par version: ".$buf.'<br>';
						}
					}

					// Ajout ligne si non commentaire
					if (! preg_match('/^--/i',$buf)) $buffer .= $buf;
				}
				fclose($fp);

				// Si plusieurs requetes, on boucle sur chaque
				$listesql=explode(';',$buffer);
				foreach ($listesql as $req)
				{
					$buffer=trim($req);
					if ($buffer)
					{
						//print "<tr><td>Creation des cles et index de la table $name: '$buffer'</td>";
						$requestnb++;
						if ($conf->file->character_set_client == "UTF-8")
						{
							$buffer=utf8_encode($buffer);
						}

						dolibarr_install_syslog("Request: ".$buffer,LOG_DEBUG);
						$resql=$db->query($buffer);
						if ($resql)
						{
							//print "<td>OK requete ==== $buffer</td></tr>";
							$db->free($resql);
						}
						else
						{
							if ($db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS' ||
							$db->errno() == 'DB_ERROR_CANNOT_CREATE' ||
							$db->errno() == 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS' ||
							$db->errno() == 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS' ||
							preg_match('/duplicate key name/i',$db->error()))
							{
								//print "<td>Deja existante</td></tr>";
								$key_exists = 1;
							}
							else
							{
								print "<tr><td>".$langs->trans("CreateOtherKeysForTable",$name);
								print "<br>\n".$langs->trans("Request").' '.$requestnb.' : '.$db->lastqueryerror();
								print "\n</td>";
								print "<td>".$langs->trans("Error")." ".$db->errno()." ".$db->error()."</td></tr>";
								$error++;
							}
						}
					}
				}
			}
			else
			{
				print "<tr><td>".$langs->trans("CreateOtherKeysForTable",$name);
				print "</td>";
				print "<td>".$langs->trans("Error")." Failed to open file ".$dir.$file."</td></tr>";
				$error++;
				dolibarr_install_syslog("Failed to open file ".$dir.$file,LOG_ERR);
			}
		}

		if ($tablefound && $error == 0)
		{
			print '<tr><td>';
			print $langs->trans("OtherKeysCreation").'</td><td>'.$langs->trans("OK").'</td></tr>';
			$okkeys = 1;
		}
	}


	/***************************************************************************************
	 *
	 * Chargement fichier functions.sql
	 *
	 ***************************************************************************************/
	if ($ok && $createfunctions)
	{
		// For this file, we use directory according to database type
		if ($choix==1) $dir = "mysql/functions/";
		elseif ($choix==2) $dir = "pgsql/functions/";
		elseif ($choix==3) $dir = "mssql/functions/";

		// Creation donnees
		$file = "functions.sql";
		if (file_exists($dir.$file))
		{
			$fp = fopen($dir.$file,"r");
			dolibarr_install_syslog("Open function file ".$dir.$file." handle=".$fp,LOG_DEBUG);
			if ($fp)
			{
				$buffer='';
				while (!feof ($fp))
				{
					$buf = fgets($fp, 4096);
					if (substr($buf, 0, 2) <> '--')
					{
						$buffer .= $buf;
					}
				}
				fclose($fp);
			}

			// Si plusieurs requetes, on boucle sur chaque
			$buffer=preg_replace('/;\';/',";'§",$buffer);
			$listesql=explode('§',$buffer);
			foreach ($listesql as $buffer)
			{
				$buffer=trim($buffer);
				if ($buffer)
				{
					dolibarr_install_syslog("Request: ".$buffer,LOG_DEBUG);
					$resql=$db->query($buffer);
					if ($resql)
					{
						$ok = 1;
						$db->free($resql);
					}
					else
					{
						if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
						{
							// print "<tr><td>Insertion ligne : $buffer</td><td>
						}
						else
						{
							$ok = 0;
							print $langs->trans("ErrorSQL")." : ".$db->errno()." - '$buffer' - ".$db->lastqueryerror()."<br>";
						}
					}
				}
			}

			print "<tr><td>".$langs->trans("FunctionsCreation")."</td>";
			if ($ok)
			{
				print "<td>".$langs->trans("OK")."</td></tr>";
			}
			else
			{
				print "<td>".$langs->trans("Error")."</td></tr>";
				$ok = 1 ;
			}

		}
	}


	/***************************************************************************************
	 *
	 * Load files data/*.sql
	 *
	 ***************************************************************************************/
	if ($ok && $createdata)
	{
		// We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
		$dir = "mysql/data/";

		// Insert data
		$handle=opendir($dir);
		dolibarr_install_syslog("Open directory data ".$dir." handle=".$handle,LOG_DEBUG);
		$tablefound = 0;
		$tabledata=array();
		while (($file = readdir($handle))!==false)
		{
			if (preg_match('/\.sql$/i',$file) && preg_match('/^llx_/i',$file))
			{
				$tablefound++;
				$tabledata[]=$file;
			}
		}
		closedir($handle);

		// Sort list of data files on alphabetical order (load order is important)
		sort($tabledata);
		foreach($tabledata as $file)
		{
			$name = substr($file, 0, strlen($file) - 4);
			$fp = fopen($dir.$file,"r");
			dolibarr_install_syslog("Open data file ".$dir.$file." handle=".$fp,LOG_DEBUG);
			if ($fp)
			{
				while (!feof ($fp))
				{
					$buffer = fgets($fp, 4096);
					$buffer = trim($buffer);
					if ($buffer)
					{
						if (substr($buffer, 0, 2) == '--')
						{
							continue;
						}

						//dolibarr_install_syslog("Request: ".$buffer,LOG_DEBUG);
						$resql=$db->query($buffer);
						if ($resql)
						{
							$ok = 1;
							$db->free($resql);
						}
						else
						{
							if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
							{
								//print "<tr><td>Insertion ligne : $buffer</td><td>";
							}
							else
							{
								$ok = 0;
								print $langs->trans("ErrorSQL")." : ".$db->lasterrno()." - ".$db->lastqueryerror()." - ".$db->lasterror()."<br>";
							}
						}
					}
				}
				fclose($fp);
			}
		}

		print "<tr><td>".$langs->trans("ReferenceDataLoading")."</td>";
		if ($ok)
		{
			print "<td>".$langs->trans("OK")."</td></tr>";
		}
		else
		{
			print "<td>".$langs->trans("Error")."</td></tr>";
			$ok = 1 ;
		}
	}
	print '</table>';

	$db->close();
}

pFooter(!$ok,$setuplang);
?>