<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
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
 */

/**
        \file       htdocs/install/etape1.php
        \brief      Génère le fichier conf.php avec les informations issues de l'étape précédente
        \version    $Revision$
*/

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langcode);
$langs->defaultlang=$setuplang;
$langs->load("admin");
$langs->load("install");

pHeader($langs->trans("ConfigurationFile"),"etape2");

$etape = 1;

// Répertoire des pages dolibarr
$main_dir=isset($_POST["main_dir"])?$_POST["main_dir"]:'';

// Répertoire des documents générés (factures, etc...)
$main_data_dir=isset($_POST["main_data_dir"])?$_POST["main_data_dir"]:'';

// En attendant que le main_data_dir soit géré de manière autonome,
// on le force à sa valeur fixe des anciennes versions.
// Eric Seigne 2004
$main_data_dir=ereg_replace("htdocs","documents",$main_dir);

// Quand ça sera géré !
if (! $main_data_dir) { $main_data_dir="$main_dir/documents"; }

if ($_POST["action"] == "set")
{
    umask(0);
    print '<h2>'.$langs->trans("SaveConfigurationFile").'</h2>';

    print '<table cellspacing="0" width="100%" cellpadding="1" border="0">';
    $error=0;
    $fp = fopen("$conffile", "w");

    if($fp)
    {
        if (substr($main_dir, strlen($main_dir) -1) == "/")
        {
            $main_dir = substr($main_dir, 0, strlen($main_dir)-1);
        }

        if (substr($_POST["main_url"], strlen($_POST["main_url"]) -1) == "/")
        {
            $_POST["main_url"] = substr($_POST["main_url"], 0, strlen($_POST["main_url"])-1);
        }

        clearstatcache();

        fwrite($fp, '<?php');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_url_root="'.$_POST["main_url"].'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_document_root="'.$main_dir.'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_data_root="'.$main_data_dir.'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_db_host="'.$_POST["db_host"].'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_db_name="'.$_POST["db_name"].'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_db_user="'.$_POST["db_user"].'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_db_pass="'.$_POST["db_pass"].'";');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_db_type="'.$_POST["db_type"].'";');
        fputs($fp,"\n");

        fputs($fp, '?>');
        fclose($fp);

        if (file_exists("$conffile"))
        {
            include ("$conffile");
            print "<tr><td>".$langs->trans("ConfigurationSaving")."</td><td>".$langs->trans("OK")."</td>";
            $error = 0;
        }
        else
        {
            $error = 1;
        }
    }

    if($dolibarr_main_db_type == "mysql")
    {
        include_once("../lib/mysql.lib.php");
        $choix=1;
    }
    else
    {
        include_once("../lib/pgsql.lib.php");
        $choix=2;
    }


    /***************************************************************************
    *
    * Creation des répertoires
    *
    ***************************************************************************/

    if ($error == 0)
    {

        // Les documents sont en dehors de htdocs car ne doivent pas pouvoir etre téléchargés en passant outre l'authentification
        $dir[0] = "$main_data_dir/facture";
        $dir[1] = "$main_data_dir/users";
        $dir[2] = "$main_data_dir/propale";
        $dir[3] = "$main_data_dir/societe";
        $dir[4] = "$main_data_dir/ficheinter";
        $dir[5] = "$main_data_dir/produit";
        $dir[6] = "$main_data_dir/rapport";
        $dir[7] = "$main_data_dir/rsscache";
        $dir[8] = "$main_data_dir/logo";

        if (! is_dir($main_dir))
        {
            print "<tr><td>";
            print $langs->trans("DirDoesNotExists",$main_dir);
            print "</td><td>";
            print $langs->trans("Error");
            print "</td></tr>";
            $error++;
        }
        else
        {
            dolibarr_syslog ("Le dossier '".$main_dir."' existe");

            // Répertoire des documents
            if (! is_dir($main_data_dir))
            {
                @mkdir($main_data_dir, 0755);
            }

            if (! is_dir($main_data_dir))
            {
                print "<tr><td>Le dossier '$main_data_dir' n'existe pas ! ";
                print "Vous devez créer ce dossier et permettre au serveur web d'écrire dans celui-ci";
                print '</td><td bgcolor="red">Erreur</td></tr>';
                $error++;
            }
            else
            {
                // Boucle sur chaque répertoire de dir[] pour les créer s'ils nexistent pas
                for ($i = 0 ; $i < sizeof($dir) ; $i++)
                {
                    if (is_dir($dir[$i]))
                    {
                        dolibarr_syslog ("Le dossier '".$dir[$i]."' existe");
                    }
                    else
                    {
                        if (! @mkdir($dir[$i], 0755))
                        {
                            print "<tr><td>";
                            print "Impossible de créer : ".$dir[$i];
                            print "</td><td bgcolor=\"red\">";
                            print $langs->trans("Error");
                            print "</td></tr>";
                            $error++;
                        }
                        else
                        {
                            dolibarr_syslog ("Le dossier '".$dir[$i]."' a ete cree");
                        }
                    }
                }
            }
        }
    }


    /*
     * Base de données
     *
     */

    if ($error == 0)
    {
        include_once($dolibarr_main_document_root . "/conf/conf.class.php");

        $conf = new Conf();
        $conf->db->type = trim($dolibarr_main_db_type);
        $conf->db->host = trim($dolibarr_main_db_host);
        $conf->db->name = trim($dolibarr_main_db_name);
        $conf->db->user = trim($dolibarr_main_db_user);
        $conf->db->pass = trim($dolibarr_main_db_pass);

        $userroot=isset($_POST["db_user_root"])?$_POST["db_user_root"]:"";
        $passroot=isset($_POST["db_pass_root"])?$_POST["db_pass_root"]:"";

        $ok=0;


        /*
         * Si creation utilisateur admin demandée, on le crée
         */
        if (isset($_POST["db_create_user"]) && $_POST["db_create_user"] == "on")
        {
            dolibarr_syslog ("Creation de l'utilisateur : ".$dolibarr_main_db_user);

            if ($choix == 1)     //choix 1=mysql
            {
                //print $conf->db->host." , ".$conf->db->name." , ".$conf->db->user." , ".$conf->db->pass;
                $db = new DoliDb($conf->db->type,$conf->db->host,$userroot,$passroot,'mysql');

                if ($db->connected) 
                {
                    $sql = "INSERT INTO user ";
                    $sql .= "(Host,User,password,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv)";
                    $sql .= " VALUES ('$dolibarr_main_db_host','$dolibarr_main_db_user',password('$dolibarr_main_db_pass')";
                    $sql .= ",'Y','Y','Y','Y','Y','Y','Y','Y');";
    
                    //print "$sql<br>\n";
    
                    $db->query($sql);
    
                    $sql = "INSERT INTO db ";
                    $sql .= "(Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv)";
                    $sql .= " VALUES ('$dolibarr_main_db_host','$dolibarr_main_db_name','$dolibarr_main_db_user'";
                    $sql .= ",'Y','Y','Y','Y','Y','Y','Y','Y');";
    
                    //print "$sql<br>\n";

                    if ($db->query($sql))
                    {
                        dolibarr_syslog("flush privileges");
                        $db->query("FLUSH Privileges;");

                        print '<tr><td>';
                        print $langs->trans("UserCreation").' : ';
                        print $dolibarr_main_db_user;
                        print '</td>';
                        print '<td>'.$langs->trans("OK").'</td></tr>';
                    }
                    else
                    {
                        if ($db->errno() == DB_ERROR_RECORD_ALREADY_EXISTS)
                        {
                            dolibarr_syslog("Utilisateur deja existant");
                            print '<tr><td>';
                            print $langs->trans("UserCreation").' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print '<td>'.$langs->trans("LoginAlreadyExists").'</td></tr>';
                        }
                        else
                        {
                            dolibarr_syslog("impossible de creer l'utilisateur");
                            print '<tr><td>';
                            print $langs->trans("UserCreation").' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print "<td>".$langs->trans("Error").' '.$db->error()."</td></tr>";
                        }
                    }
    
                    $db->close();
                }
                else {
                    print '<tr><td>';
                    print $langs->trans("UserCreation").' : ';
                    print $dolibarr_main_db_user;
                    print '</td>';
                    print '<td>'.$langs->trans("Error").'</td>';
                    print '</tr>';
        
                    // Affiche aide diagnostique
                    print '<tr><td colspan="2"><br>Vous avez demandé la création du login Dolibarr ('.$dolibarr_main_db_user.') mais pour cela ';
                    print 'Dolibarr doit se connecter sur le serveur ('.$dolibarr_main_db_host.') via le super utilisateur ('.$userroot.'), mot de passe ('.$passroot.'). ';
                    print 'La connexion ayant échoué, les paramètres du serveur ou du super utilisateur sont peut-etre incorrects.<br>';
                    print 'Revenez en arrière pour corriger les paramètres.<br>';
                    print '</td></tr>';

                    $ok=-1;
                }
            }
            else        //choix 2=postgresql
            {  
                $nom = $dolibarr_main_db_user;
                $con=pg_connect("host=localhost dbname=dolibarr user=postgres");
                $query_str = "create user \"$nom\" with password '".$dolibarr_main_db_pass."';";
                //print $query_str;
                $ret = pg_query($con,$query_str);

                if ($ret)
                {
                    print '<tr><td>';
                    print $langs->trans("UserCreation").' : ';
                    print $dolibarr_main_db_user;
                    print '</td>';
                    print '<td>'.$langs->trans("OK").'</td>';
                    print '</tr>';
                }
                else
                {
                    print '<tr><td>';
                    print $langs->trans("UserCreation").' : ';
                    print $dolibarr_main_db_user;
                    print '</td>';
                    print '<td>'.$langs->trans("Error").'</td>';
                    print '</tr>';
                }
            }

        }   // Fin si "creation utilisateur"
        

        /*
         * Si creation database demandée, on la crée
         */
        if (isset($_POST["db_create_database"]) && $_POST["db_create_database"] == "on")
        {
            dolibarr_syslog ("Creation de la base : ".$dolibarr_main_db_name);

            $db = new DoliDb($conf->db->type,$conf->db->host,$userroot,$passroot);
 
            if ($db->connected) 
            {
                if ($db->create_db($dolibarr_main_db_name))
                {
                    print '<tr><td>';
                    print $langs->trans("DatabaseCreation").' : ';
                    print $dolibarr_main_db_name;
                    print '</td>';
                    print "<td>".$langs->trans("OK")."</td></tr>";
                }
                else
                {
                    print '<tr><td>';
                    print $langs->trans("DatabaseCreation").' : ';
                    print $dolibarr_main_db_name;
                    print '</td>';
                    print '<td>'.$langs->trans("Error").' '.$db->errno().'</td></tr>';

                    // Affiche aide diagnostique
                    print '<tr><td colspan="2"><br>La création de la base Dolibarr ('.$dolibarr_main_db_name.') a échoué.';
                    print 'Si la base existe déjà, revenez en arrière et désactiver l\'option "Créer la base de donnée".<br>';
                    print '</td></tr>';
                    
                    $ok=-1;
                }
                $db->close();
            }
            else {
                print '<tr><td>';
                print $langs->trans("DatabaseCreation").' : ';
                print $dolibarr_main_db_name;
                print '</td>';
                print '<td>'.$langs->trans("Error").'</td>';
                print '</tr>';
    
                // Affiche aide diagnostique
                print '<tr><td colspan="2"><br>Vous avez demandé la création de la base Dolibarr ('.$dolibarr_main_db_name.') mais pour cela ';
                print 'Dolibarr doit se connecter sur le serveur ('.$dolibarr_main_db_host.') via le super utilisateur ('.$userroot.'), mot de passe ('.$passroot.'). ';
                print 'La connexion ayant échoué, les paramètres du serveur ou du super utilisateur sont peut-etre incorrects.<br>';
                print 'Revenez en arrière pour corriger les paramètres.<br>';
                print '</td></tr>';

                $ok=-1;
            }
        }   // Fin si "creation database"


        /*
         * On essaie l'accès par le user admin dolibarr
         */
        if ($ok == 0)
        {

            $db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name);
    
            if ($db->connected == 1)
            {
                //   si accès serveur ok et accès base ok, tout est ok, on ne va pas plus loin, on a même pas utilisé le compte root.
                if ($db->database_selected == 1)
                {
                    dolibarr_syslog("la connexion au serveur par le user dolibarr est reussie");
                    print "<tr><td>";
                    print $langs->trans("ServerConnection")." : ";
                    print $dolibarr_main_db_host;
                    print "</td><td>";
                    print $langs->trans("OK");
                    print "</td></tr>";
    
                    dolibarr_syslog("la connexion a la database par le user dolibarr est reussie");
                    print "<tr><td>";
                    print $langs->trans("DatabaseConnection")." : ";
                    print $dolibarr_main_db_name;
                    print "</td><td>";
                    print $langs->trans("OK");
                    print "</td></tr>";
    
                    $ok = 1;
                }
                else
                {
                    dolibarr_syslog("la connection au serveur par le user dolibarr est reussie");
                    print "<tr><td>";
                    print $langs->trans("ServerConnection")." : ";
                    print $dolibarr_main_db_host;
                    print "</td><td>";
                    print $langs->trans("OK");
                    print "</td></tr>";
    
                    dolibarr_syslog("la connexion a la database par le user dolibarr a échoué");
                    print "<tr><td>";
                    print $langs->trans("DatabaseConnection")." : ";
                    print $dolibarr_main_db_name;
                    print "</td><td>";
                    print $langs->trans("Error");
                    print "</td></tr>";
    
                    // Affiche aide diagnostique
                    print '<tr><td colspan="2"><br>Vérifier que le nom de base ('.$dolibarr_main_db_name.') est correct. ';
                    print 'Si ce nom est correct et que cette base n\'existe pas déjà, vous devez cocher l\'option "Créer la base de donnée".<br>';
                    print 'Revenez en arrière pour corriger les paramètres.<br>';
                    print '</td></tr>';

                    $ok = -1;
                }
            }
            else {
                dolibarr_syslog("la connection au serveur par le user dolibarr est rate");
                print "<tr><td>";
                print $langs->trans("ServerConnection")." : ";
                print $dolibarr_main_db_host;
                print "</td><td>";
                print $langs->trans("Error");
                print "</td></tr>";
    
                // Affiche aide diagnostique
                print '<tr><td colspan="2"><br>Le serveur ('.$conf->db->host.'), nom de base ('.$conf->db->name.'), login ('.$conf->db->user.'), ou mot de passe ('.$conf->db->pass.') de la base de donnée est peut-être incorrect.<br>';
                print 'Si le login n\'existe pas encore, vous devez cocher l\'option "Créer l\'utilisateur".<br>';
                print 'Revenez en arrière pour corriger les paramètres.<br>';
                print '</td></tr>';
    
                $ok = -1;
            }
        }

    }
}

?>
</table>
<?php
pFooter($ok==1?0:1);
?>
