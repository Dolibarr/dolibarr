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
        \file       htdocs/install/etape2.php
        \brief      Crée les tables, clés primaires, clés étrangères, index et fonctions en base puis charge les données de référence
        \version    $Revision$
*/

include_once("./inc.php");

$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le délai par défaut de 30 à 60.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
set_time_limit(60);         
error_reporting($err);

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langcode);
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");


pHeader($langs->trans("CreateDatabaseObjects"),"etape4");


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
    $choix=2;
}

require_once($dolibarr_main_document_root . "/conf/conf.class.php");


if ($_POST["action"] == "set")
{
    print '<h2>'.$langs->trans("Database").'</h2>';

    print '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
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
        print "<tr><td>";
        print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td>".$langs->trans("OK")."</td></tr>";
        $ok = 1 ;
    }
    else
    {
        print "<tr><td>Erreur lors de la création de : $dolibarr_main_db_name</td><td>".$langs->trans("Error")."</td></tr>";
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
    * Chargement fichiers tables/*.sql (non *.key.sql)
    * A faire avant les fichiers *.key.sql
    *
    ***************************************************************************************/
    if ($ok)
    {
        if ($choix==1) $dir = "../../mysql/tables/";
        else $dir = "../../pgsql/tables/";

        $ok = 0;
        $handle=opendir($dir);
        $table_exists = 0;
        while (($file = readdir($handle))!==false)
        {
            if (substr($file, strlen($file) - 4) == '.sql' && substr($file,0,4) == 'llx_' && substr($file, -8) <> '.key.sql')
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
                            $buffer .= $buf;
                        }
                    }
                    fclose($fp);
                }

                //print "<tr><td>Création de la table $name/td>";

                if ($db->query($buffer))
                {
                    //print "<td>OK requete ==== $buffer</td></tr>";
                }
                else
                {
                    if ($db->errno() == 'DB_ERROR_TABLE_ALREADY_EXISTS')
                    {
                        //print "<td>Déjà existante</td></tr>";
                        $table_exists = 1;
                    }
                    else
                    {
                        print "<tr><td>".$langs->trans("CreateTableAndPrimaryKey",$name)."</td>";
                        print "<td>".$langs->trans("Error")." ".$db->errno()." ".$db->error()."</td></tr>";
                        $error++;
                    }
                }
            }

        }
        closedir($handle);

        if ($error == 0)
        {
            print '<tr><td>';
            print $langs->trans("TablesAndPrimaryKeysCreation").'</td><td>'.$langs->trans("OK").'</td></tr>';
            $ok = 1;
        }
    }


    /***************************************************************************************
    *
    * Chargement fichiers tables/*.key.sql
    * A faire après les fichiers *.sql
    *
    ***************************************************************************************/
    if ($ok)
    {
        if ($choix==1) $dir = "../../mysql/tables/";
        else $dir = "../../pgsql/tables/";

        $okkeys = 0;
        $handle=opendir($dir);
        $table_exists = 0;
        while (($file = readdir($handle))!==false)
        {
            if (substr($file, strlen($file) - 4) == '.sql' && substr($file,0,4) == 'llx_' && substr($file, -8) == '.key.sql')
            {
                $name = substr($file, 0, strlen($file) - 4);
                //print "<tr><td>Création de la table $name</td>";
                $buffer = '';
                $fp = fopen($dir.$file,"r");
                if ($fp)
                {
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
                $listesql=split(';',$buffer);
                foreach ($listesql as $buffer) {                
                    if (trim($buffer)) {
                        //print "<tr><td>Création des clés et index de la table $name: '$buffer'</td>";
                        if ($db->query(trim($buffer)))
                        {
                            //print "<td>OK requete ==== $buffer</td></tr>";
                        }
                        else
                        {
                            if ($db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS' ||
                                $db->errno() == 'DB_ERROR_CANNOT_CREATE' ||
                                eregi('duplicate key name',$db->error()))
                            {
                                //print "<td>Déjà existante</td></tr>";
                                $key_exists = 1;
                            }
                            else
                            {
                                print "<tr><td>".$langs->trans("CreateOtherKeysForTable",$name)."</td>";
                                print "<td>".$langs->trans("Error")." ".$db->errno()." ".$db->error()."</td></tr>";
                                $error++;
                            }
                        }
                    }
                }
            }

        }
        closedir($handle);

        if ($error == 0)
        {
            print '<tr><td>';
            print $langs->trans("OtherKeysCreation").'</td><td>'.$langs->trans("OK").'</td></tr>';
            $okkeys = 1;
        }
    }
    
    
    /***************************************************************************************
    *
    * Positionnement des droits
    *
    ***************************************************************************************/
    if ($ok)
    {
        // Droits sur les tables
        $grant_query=$db->getGrantForUserQuery($dolibarr_main_db_user);
        
        if ($grant_query)   // Seules les bases qui en ont besoin le definisse
        {
            if ($db->query($grant_query))
            {
                print "<tr><td>Grant User</td><td>".$langs->trans("OK")."</td></tr>";
            }
        }
    }   


    /***************************************************************************************
    *
    * Chargement fichier functions.sql
    *
    ***************************************************************************************/
    if ($ok)
    {
        if ($choix==1) $dir = "../../mysql/functions/";
        else $dir = "../../pgsql/functions/";

        // Création données
        $file = "functions.sql";
        if (file_exists($dir.$file)) {
            $fp = fopen($dir.$file,"r");
            if ($fp)
            {
                while (!feof ($fp))
                {
                    $buffer = fgets($fp, 4096);
                    if (substr($buf, 0, 2) <> '--')
                    {
                        $buffer .= $buf;
                    }
                }
                fclose($fp);
            }

            // Si plusieurs requetes, on boucle sur chaque
            $listesql=split('§',eregi_replace(";';",";'§",$buffer));
            foreach ($listesql as $buffer) {                
                if (trim($buffer)) {
    
                    if ($db->query(trim($buffer)))
                    {
                        $ok = 1;
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
                            print $langs->trans("ErrorSQL")." : ".$db->errno()." - '$buffer' - ".$db->error()."<br>";
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
    * Chargement fichier data.sql
    *
    ***************************************************************************************/
    if ($ok)
    {
        if ($choix==1) $dir = "../../mysql/data/";
        else $dir = "../../pgsql/data/";

        // Création données
        $file = "data.sql";
        $fp = fopen($dir.$file,"r");
        if ($fp)
        {
            while (!feof ($fp))
            {
                $buffer = fgets($fp, 4096);

                if (strlen(trim(ereg_replace("--","",$buffer))))
                {
                    if ($db->query($buffer))
                    {
                        $ok = 1;
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
                            print $langs->trans("ErrorSQL")." : ".$db->errno()." - '$buffer' - ".$db->error()."<br>";
                        }
                    }
                }
            }
            fclose($fp);
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


    /***************************************************************************************
    *
    * Les variables qui ecrase le chemin par defaut sont redéfinies
    *
    ***************************************************************************************/
    if ($ok == 1)
    {
        $sql[0] = "UPDATE llx_const SET value = '".$dolibarr_main_data_root."/facture',
        type = 'chaine',
        visible = 0
        where name  ='FAC_OUTPUTDIR';" ;

        $sql[1] = "UPDATE llx_const SET value = '".$dolibarr_main_data_root."/propale',
        type = 'chaine',
        visible = 0
        where name  = 'PROPALE_OUTPUTDIR';" ;

        $sql[2] = "UPDATE llx_const SET value = '".$dolibarr_main_data_root."/ficheinter',
        type = 'chaine',
        visible = 0
        where name  = 'FICHEINTER_OUTPUTDIR';" ;

        $sql[3] = "UPDATE llx_const SET value='".$dolibarr_main_data_root."/societe',
        type = 'chaine',
        visible = 0
        where name  = 'SOCIETE_OUTPUTDIR';" ;

        $sql[4] = "DELETE from llx_const where name like '%_OUTPUT_URL';";


        $sql[5] = "UPDATE llx_const SET value='".$langs->defaultlang."',
        type = 'chaine',
        visible = 0
        where name  = 'MAIN_LANG_DEFAULT';" ;

        $result = 0;

        for ($i=0; $i < sizeof($sql);$i++)
        {
            if ($db->query($sql[$i]))
            {
                $result++;
            }
        }

    }

    print '</table>';

    $db->close();
}

pFooter(!$ok,$setuplang);

?>
