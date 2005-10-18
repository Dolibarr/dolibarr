<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

$grant_query='';
$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le délai par défaut de 30 à 60.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
set_time_limit(60);         
error_reporting($err);

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langcode);
$langs->defaultlang=$setuplang;
$langs->load("admin");
$langs->load("install");


pHeader($langs->trans("MigrateScript"),"etape5","upgrade");


if (file_exists($conffile))
{
    include_once($conffile);
}

if($dolibarr_main_db_type == "mysql")
{
    require_once($dolibarr_main_document_root . "/lib/mysql.lib.php");
    $choix=1;
}
else
{
    require_once($dolibarr_main_document_root . "/lib/pgsql.lib.php");
    require_once($dolibarr_main_document_root . "/lib/grant.postgres.php");
    $choix=2;
}

require_once($dolibarr_main_document_root . "/conf/conf.class.php");


if (isset($_GET["action"]) && $_GET["action"] == "upgrade")
{
    print '<h2>'.$langs->trans("Database").'</h2>';

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
        $ok = 1 ;
    }
    else
    {
        print "<tr><td>Erreur lors de la création de : $dolibarr_main_db_name</td><td align=\"right\">".$langs->trans("Error")."</td></tr>";
    }

    if ($ok)
    {
        if($db->database_selected == 1)
        {

            dolibarr_syslog("Connexion réussie à la base : $dolibarr_main_db_name");
        }
        else
        {
            $ok = 0 ;
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
        else $dir = "../../pgsql/migration/";
        $migfile='1.1.0-2.0.0.sql';
        
        $i = 0;
        $ok = 0;
        $handle=opendir($dir);
        while (($file = readdir($handle))!==false)
        {
            if (eregi($migfile,$file))
            {
                print '<tr><td nowrap>';
                print $langs->trans("MigrateScript").' :</td><td align="right">'.$file.'</td></tr>';

                $name = substr($file, 0, strlen($file) - 4);
                $buffer = '';
                $fp = fopen($dir.$file,"r");
                if ($fp)
                {
                    while (!feof ($fp))
                    {
                        $buf = fgets($fp, 4096);
                        $buf = ereg_replace('--(.*)','',$buf);  // Delete SQL comments
                        //print $buf.'<br>';
                        $buffer .= $buf;
                        if (eregi(';',$buffer))
                        {
                            // Found new request
                            $arraysql[$i]=$buffer;
                            $i++;
                            $buffer='';
                        }
                    }
                    if ($buffer) $arraysql[$i]=$buffer;
                    fclose($fp);
                }

                // Loop on each request
                foreach($arraysql as $i=>$sql)
                {
//                    print '<tr><td>'.$langs->trans("Request").' '.$i.'</td>';
    
                    if ($db->query($sql))
                    {
//                        print '<td align="right">OK</td>';
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
                                        'DB_ERROR_CANNOT_CREATE'    // Qd contrainte deja existante
                                       );
                        if (in_array($errno,$okerror))
                        {
//                            print '<td align="right">'.$langs->trans("OK").'</td>';
                        }
                        else
                        {
                            print '<tr><td>'.$langs->trans("Request").' '.$i.'</td>';
                            print '<td>'.$langs->trans("Error")." ".$db->errno()." ".$db->error()."</td>";
                            print '</tr>';
                            $error++;
                        }
                    }
    
//                    print '</tr>';
                }
                
            }

        }
        closedir($handle);

        if ($error == 0)
        {
            print '<tr><td>';
            print $langs->trans("ProcessMigrateScript").'</td><td align="right">'.$langs->trans("OK").'</td></tr>';
            $ok = 1;
        }
    }

    print '</table>';

    $db->close();
}
else
{
    print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}

pFooter(!$ok);

?>
