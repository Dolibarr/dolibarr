<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/install/upgrade.php
        \brief      Execute le script de migration
        \version    $Revision$
*/

include_once("./inc.php");
if (file_exists($conffile)) include_once($conffile);
if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_'; 
define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);
require_once($dolibarr_main_document_root . "/lib/databases/".$dolibarr_main_db_type.".lib.php");
require_once($dolibarr_main_document_root . "/conf/conf.class.php");

$migfile='^2.0.0-2.1.0.sql$';
$grant_query='';
$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le délai par défaut de 30 à 60.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
set_time_limit(60);
error_reporting($err);

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");

if ($dolibarr_main_db_type == "mysql") $choix=1;
if ($dolibarr_main_db_type == "mysqli") $choix=1;
if ($dolibarr_main_db_type == "pgsql") $choix=2;


dolibarr_install_syslog("Entering upgrade.php page");


pHeader($langs->trans("DatabaseMigration"),"upgrade2","upgrade");

if (! isset($_GET["action"]) || $_GET["action"] == "upgrade")
{
    print '<h2>'.$langs->trans("DatabaseMigration").'</h2>';

    print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
    $error=0;

    $conf = new Conf();// on pourrait s'en passer
    $conf->db->type = $dolibarr_main_db_type;
    $conf->db->host = $dolibarr_main_db_host;
    $conf->db->name = $dolibarr_main_db_name;
    $conf->db->user = $dolibarr_main_db_user;
    $conf->db->pass = $dolibarr_main_db_pass;

    $db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name);
    if ($db->connected == 1)
    {
        print "<tr><td nowrap>";
        print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
		dolibarr_install_syslog($langs->trans("ServerConnection")." : $dolibarr_main_db_host ".$langs->trans("OK"));
        $ok = 1 ;
    }
    else
    {
        print "<tr><td>".$langs->trans("ErrorFailedToCreateDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->trans("Error")."</td></tr>";
		dolibarr_install_syslog($langs->trans("ErrorFailedToCreateDatabase",$dolibarr_main_db_name));
    }

    if ($ok)
    {
        if($db->database_selected == 1)
        {
            dolibarr_install_syslog("Database connection successfull : $dolibarr_main_db_name");
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
        print '<td align="right">'.$version.'</td></tr>';
		dolibarr_install_syslog($langs->trans("DatabaseVersion")." : $version");
        //print '<td align="right">'.join('.',$versionarray).'</td></tr>';
    }



    /***************************************************************************************
    *
    * Chargement fichiers dans migration
    *
    ***************************************************************************************/
    if ($ok)
    {
        if ($choix==1) $dir = "../../mysql/migration/";
        else $dir = "../../pgsql/migration/";

        $i = 0;
        $ok = 0;
        $handle=opendir($dir);
        while (($file = readdir($handle))!==false)
        {
            if (eregi($migfile,$file))
            {
                print '<tr><td nowrap>';
                print $langs->trans("ChooseMigrateScript").'</td><td align="right">'.$file.'</td></tr>';

                $name = substr($file, 0, strlen($file) - 4);
                $buffer = '';
                $fp = fopen($dir.$file,"r");
                if ($fp)
                {
                    while (!feof ($fp))
                    {
                        $buf = fgets($fp, 4096);

                        // Cas special de lignes autorisees pour certaines versions uniquement
                        if (eregi('^-- V([0-9\.]+)',$buf,$reg))
                        {
                            $versioncommande=split('\.',$reg[1]);
							//print var_dump($versioncommande);
							//print var_dump($versionarray);
                            if (sizeof($versioncommande) && sizeof($versionarray)
                            	&& versioncompare($versioncommande,$versionarray) <= 0)
                            {
                            	// Version qualified, delete SQL comments
                                $buf=eregi_replace('^-- V([0-9\.]+)','',$buf);
                                //print "Ligne $i qualifiée par version: ".$buf.'<br>';
                            }                      
                        }

                        // Ajout ligne si non commentaire
                        if (! eregi('^--',$buf)) $buffer .= $buf;

//                        print $buf.'<br>';

                        if (eregi(';',$buffer))
                        {
                            // Found new request
                            $arraysql[$i]=trim($buffer);
                            $i++;
                            $buffer='';
                        }
                    }
                    if ($buffer) $arraysql[$i]=trim($buffer);
                    fclose($fp);
                }

                // Loop on each request
                foreach($arraysql as $i=>$sql)
                {
					if ($sql)
					{
						// Ajout trace sur requete (eventuellement à commenter si beaucoup de requetes)
						print('<tr><td valign="top">'.$langs->trans("Request").' '.($i+1)." sql='".$sql."'</td></tr>\n");
						dolibarr_install_syslog($langs->trans("Request").' '.($i+1)." sql='".$sql);

    	                if ($db->query($sql))
        	            {
// 	                       print '<td align="right">OK</td>';
            	        }
	                    else
	                    {
	                        $errno=$db->errno();
	                        $okerror=array( 'DB_ERROR_TABLE_ALREADY_EXISTS',
	                                        'DB_ERROR_COLUMN_ALREADY_EXISTS',
	                                        'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
	                                        'DB_ERROR_RECORD_ALREADY_EXISTS',
	                                        'DB_ERROR_NOSUCHTABLE',
	                                        'DB_ERROR_NOSUCHFIELD',
	                                        'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
	                                        'DB_ERROR_CANNOT_CREATE',    		// Qd contrainte deja existante
	                                       	'DB_ERROR_CANT_DROP_PRIMARY_KEY'
	                                       );
	                        if (in_array($errno,$okerror))
	                        {
	//                            print '<td align="right">'.$langs->trans("OK").'</td>';
	                        }
	                        else
	                        {
	                            print '<tr><td valign="top">'.$langs->trans("Request").' '.($i+1).'</td>';
	                            print '<td valign="top">'.$langs->trans("Error")." ".$db->errno()." ".$sql."<br>".$db->error()."</td>";
	                            print '</tr>';
								dolibarr_install_syslog($langs->trans("Request").' '.($i+1)." ".$langs->trans("Error")." ".$db->errno()." ".$sql."<br>".$db->error());
	                            $error++;
	                        }
	                    }

//                    	print '</tr>';
					}
                }

            }

        }
        closedir($handle);

        if ($error == 0)
        {
            print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
            print '<td align="right">'.$langs->trans("OK").'</td></tr>';
            $ok = 1;
        }
        else
        {
            print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
            print '<td align="right"><div class="error">'.$langs->trans("KO").'</div></td></tr>';
            $ok = 0;
        }
    }

    print '</table>';

    $db->close();
}
else
{
    print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}

pFooter(! $ok,$setuplang);

?>
