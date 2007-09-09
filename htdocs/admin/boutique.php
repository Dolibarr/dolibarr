<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file 		htdocs/admin/boutique.php
		\ingroup    boutique
		\brief      Page d'administration/configuration du module OsCommerce
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("oscommerce");

if (!$user->admin)
  accessforbidden();



/*
 * Actions
 */

if ($_POST["save"])
{
    $db->begin();

    $i=0;
    
    $i+=dolibarr_set_const($db,'OSC_DB_HOST',trim($_POST["oscommerce_dbhost"]),'chaine',0);
    $i+=dolibarr_set_const($db,'OSC_DB_NAME',trim($_POST["oscommerce_dbname"]),'chaine',0);
    $i+=dolibarr_set_const($db,'OSC_DB_USER',trim($_POST["oscommerce_dbuser"]),'chaine',0);
    $i+=dolibarr_set_const($db,'OSC_DB_PASS',trim($_POST["oscommerce_dbpass"]),'chaine',0);

    if ($i >= 4)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("OSCommerceSetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
}
elseif ($_POST["test"])
{
    //$resql=$db->query("select count(*) from llx_const");
    //print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";

    // Test de la connexion a la database webcalendar
    $conf->oscommerce->db->type=$dolibarr_main_db_type;
    $conf->oscommerce->db->host=$_POST["oscommerce_dbhost"];
    $conf->oscommerce->db->name=$_POST["oscommerce_dbname"];
    $conf->oscommerce->db->user=$_POST["oscommerce_dbuser"];
    $conf->oscommerce->db->pass=$_POST["oscommerce_dbpass"];

    $oscommercedb=new DoliDB($conf->oscommerce->db->type,$conf->oscommerce->db->host,$conf->oscommerce->db->user,$conf->oscommerce->db->pass,$conf->oscommerce->db->name);

    //print "D ".$db." - ".$db->db."<br>\n";
    //print "W ".$oscommercedb." - ".$oscommercedb->db."<br>\n";
    
    if ($oscommercedb->connected == 1 && $oscommercedb->database_selected == 1)
    {
        // Vérifie si bonne base par requete sur une table OSCommerce
        $sql ="SELECT configuration_value";
        $sql.=" FROM configuration";
        $sql.=" WHERE configuration_key='STORE_NAME'";
        $resql=$oscommercedb->query($sql);
        if ($resql) {
            $mesg ="<div class=\"ok\">".$langs->trans("OSCommerceTestOk",$_POST["oscommerce_dbhost"],$_POST["oscommerce_dbname"],$_POST["oscommerce_dbuser"]);
            $mesg.="</div>";
        }
        else {
            $mesg ="<div class=\"error\">".$langs->trans("OSCommerceErrorConnectOkButWrongDatabase");
            $mesg.="</div>";
        }

        //$oscommercedb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
    }
    elseif ($oscommercedb->connected == 1 && $oscommercedb->database_selected != 1)
    {
        $mesg ="<div class=\"error\">".$langs->trans("OSCommerceTestKo1",$_POST["oscommerce_dbhost"],$_POST["oscommerce_dbname"]);
        $mesg.="<br>".$oscommercedb->error();
        $mesg.="</div>";
        //$oscommercedb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
    }
    else
    {
        $mesg ="<div class=\"error\">".$langs->trans("OSCommerceTestKo2",$_POST["oscommerce_dbhost"],$_POST["oscommerce_dbuser"]);
        $mesg.="<br>".$oscommercedb->error();
        $mesg.="</div>";
    }

    //$resql=$db->query("select count(*) from llx_const");
    //print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";
}



/*
 * Affichage page
 */

llxHeader();

print_fiche_titre($langs->trans("OSCommerceSetup"),'','setup');



print '<br>';

$var=true;
print '<form name="oscommerceconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCOmmerceServer")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_dbhost\" value=\"". ($_POST["oscommerce_dbhost"]?$_POST["oscommerce_dbhost"]:$conf->global->OSC_DB_HOST) . "\" size=\"30\"></td>";
print "<td>localhost";
//print "<br>__dolibarr_main_db_host__ <i>(".$dolibarr_main_db_host.")</i>"
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCOmmerceDatabaseName")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_dbname\" value=\"". ($_POST["oscommerce_dbname"]?$_POST["oscommerce_dbname"]:$conf->global->OSC_DB_NAME) . "\" size=\"30\"></td>";
print "<td>oscommerce";
//print "<br>__dolibarr_main_db_name__ <i>(".$dolibarr_main_db_name.")</i>";
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCOmmerceUser")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_dbuser\" value=\"". ($_POST["oscommerce_dbuser"]?$_POST["oscommerce_dbuser"]:$conf->global->OSC_DB_USER) . "\" size=\"30\"></td>";
print "<td>oscommerceuser";
//print "<br>__dolibarr_main_db_user__ <i>(".$dolibarr_main_db_user.")</i>";
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("Password")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"oscommerce_dbpass\" value=\"" . ($_POST["oscommerce_dbpass"]?$_POST["oscommerce_dbpass"]:$conf->global->OSC_DB_PASS) . "\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.eregi_replace('.','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("PasswordRetype")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"oscommerce_dbpass2\" value=\"" . ($_POST["oscommerce_dbpass2"]?$_POST["oscommerce_dbpass2"]:$conf->global->OSC_DB_PASS) ."\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.eregi_replace('.','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";
print "</table>";


print '<br><center>';
print "<input type=\"submit\" name=\"test\" class=\"button\" value=\"".$langs->trans("TestConnection")."\">";
print "&nbsp; &nbsp;";
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";


clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";



$db->close();

llxFooter('$Date$ - $Revision$');
?>
