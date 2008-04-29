<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/install/upgrade.php
        \brief      Execute le script de migration
        \version    $Revision$
*/

include_once("./inc.php");
if (file_exists($conffile)) include_once($conffile);
require_once($dolibarr_main_document_root."/lib/databases/".$dolibarr_main_db_type.".lib.php");
require_once($dolibarr_main_document_root."/lib/admin.lib.php");


$grant_query='';
$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le d�lai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);
$versionfrom=isset($_GET["versionfrom"])?$_GET["versionfrom"]:'';
$versionto=isset($_GET["versionto"])?$_GET["versionto"]:'';

$langs->load("admin");
$langs->load("install");

if ($dolibarr_main_db_type == "mysql") $choix=1;
if ($dolibarr_main_db_type == "mysqli") $choix=1;
if ($dolibarr_main_db_type == "pgsql") $choix=2;
if ($dolibarr_main_db_type == "mssql") $choix=3;


dolibarr_install_syslog("upgrade: Entering upgrade.php page");
if (! is_object($conf)) dolibarr_install_syslog("upgrade2: conf file not initialized",LOG_ERR);


pHeader($langs->trans("DatabaseMigration"),"upgrade2","upgrade");


if (! isset($_GET["action"]) || $_GET["action"] == "upgrade")
{
	print '<h3>'.$langs->trans("DatabaseMigration").'<h3>';

	if (! $versionfrom && ! $versionto)
	{
		print '<div class="error">Parameter versionfrom or version to missing.</div>';
		exit;
	}

	print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
	$error=0;
	
	// decode database pass if needed
	if (! empty($dolibarr_main_db_encrypted_pass))
	{
		require_once($dolibarr_main_document_root."/lib/security.lib.php");
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
	}

	// $conf is already instancied inside inc.php
	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;

	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
	if ($db->connected == 1)
	{
		print "<tr><td nowrap>";
		print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
		dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ServerConnection")." : $dolibarr_main_db_host ".$langs->transnoentities("OK"));
		$ok = 1;
	}
	else
	{
		print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->transnoentities("Error")."</td></tr>";
		dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name));
		$ok = 0;
	}

	if ($ok)
	{
		if($db->database_selected == 1)
		{
			print "<tr><td nowrap>";
			print $langs->trans("DatabaseConnection")." : ".$dolibarr_main_db_name."</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
			dolibarr_install_syslog("upgrade: Database connection successfull : $dolibarr_main_db_name");
			$ok=1;        
		}
		else
		{
			print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->trans("Error")."</td></tr>";
			dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name));
			$ok=0;
		}
	}

	// Affiche version
	if ($ok)
	{
		$version=$db->getVersion();
		$versionarray=$db->getVersionArray();
		print '<tr><td>'.$langs->trans("ServerVersion").'</td>';
		print '<td align="right">'.$version.'</td></tr>';
		dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ServerVersion")." : $version");
		//print '<td align="right">'.join('.',$versionarray).'</td></tr>';
	}
	
  // Force l'affichage de la progression
	print '<tr><td>'.$langs->trans("PleaseBePatient").'</td>';
	flush();

	if ($ok)
	{
		$versioncommande=split('\.','4.0');
		if (sizeof($versioncommande) && sizeof($versionarray)
			&& versioncompare($versioncommande,$versionarray) <= 0)	// Si mysql >= 4.0
		{
			// Suppression vieilles contraintes sans noms et en doubles
			// Les contraintes indesirables ont un nom qui commence par 0_ ou se termine par ibfk_999
/*			$listtables=array(  'llx_product_fournisseur_price',
								'llx_fichinter',
								'llx_facture_fourn',
								'llx_propal',
								'llx_socpeople',
								'llx_telephonie_adsl_fournisseur',
								'llx_telephonie_client_stats',
								'llx_telephonie_contact_facture',
								'llx_telephonie_societe_ligne',
								'llx_telephonie_tarif_client');
*/		    
			$listtables = $db->DDLListTables($conf->db->name,'');
		    foreach ($listtables as $val)
			{
				//print "x".$val."<br>";
				$sql = "SHOW CREATE TABLE ".$val;
				$resql = $db->query($sql);
				if ($resql)
				{
					$values=$db->fetch_array($resql);
					$i=0;
					$createsql=$values[1];
					while (eregi('CONSTRAINT `(0_[0-9a-zA-Z]+|[_0-9a-zA-Z]+_ibfk_[0-9]+)`',$createsql,$reg) && $i < 100)
					{
						$sqldrop="ALTER TABLE ".$val." DROP FOREIGN KEY ".$reg[1];
						$resqldrop = $db->query($sqldrop);
						if ($resqldrop)
						{
							print '<tr><td colspan="2">'.$sqldrop.";</td></tr>\n";
						}
						$createsql=eregi_replace('CONSTRAINT `'.$reg[1].'`','XXX',$createsql);
						$i++;
					}
					$db->free($resql);
				}
				else
				{
					if ($db->lasterrno() != 'DB_ERROR_NOSUCHTABLE')
					{
						print '<tr><td colspan="2"><font  class="error">'.$sql.' : '.$db->lasterror()."</font></td></tr>\n";
					}
				}
			}
		}
	}

	/***************************************************************************************
	*
	* Chargement fichiers dans migration
	*
	***************************************************************************************/
	if ($ok)
	{
		if ($choix==1) $dir = "../../mysql/migration/";
		elseif ($choix==2) $dir = "../../pgsql/migration/";
		else $dir = "../../mssql/migration/";
		
		$filelist=array();
		$i = 0;
		$ok = 0;
		$from='^'.$versionfrom;
		$to=$versionto.'\.sql$';

		# Recupere list fichier
		$filesindir=array();
		$handle=opendir($dir);
		while (($file = readdir($handle))!==false)
		{
			if (eregi('\.sql$',$file)) $filesindir[]=$file;
		}
		sort($filesindir);

		# Determine les fichiers sql a passer
		foreach($filesindir as $file)
		{
			if (eregi($from,$file))
			{
				$filelist[]=$file;

				// Mettre from avec valeur fin de $file
			}
			else if (eregi($to,$file))
			{
				$filelist[]=$file;
			}
		}

		# Boucle sur chaque fichier
		foreach($filelist as $file)
		{
			print '<tr><td nowrap>';
			print $langs->trans("ChoosedMigrateScript").'</td><td align="right">'.$file.'</td></tr>';
			
			$name = substr($file, 0, strlen($file) - 4);
			
			// Run sql script
			$ok=run_sql($dir.$file, 0);
		}
	}

	print '</table>';

	if ($db->connected) $db->close();
}
else
{
    print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}

pFooter(! $ok,$setuplang);

?>
