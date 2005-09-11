<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
	    \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$def = array();
$actiontest=$_POST["test"];
$actionsave=$_POST["save"];

// Test saisie mot de passe
if ($_POST["phpwebcalendar_pass"] != $_POST["phpwebcalendar_pass2"])
{
    $mesg="<div class=\"error\">".$langs->trans("ErrorPasswordDiffers")."</div>";
}
// Positionne la variable pour le test d'affichage de l'icone
elseif ($actionsave)
{
    $i=0;

    $db->begin();
    
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_URL',trim($_POST["phpwebcalendar_url"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_HOST',trim($_POST["phpwebcalendar_host"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_DBNAME',trim($_POST["phpwebcalendar_dbname"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_USER',trim($_POST["phpwebcalendar_user"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_PASS',trim($_POST["phpwebcalendar_pass"]),'',0);

    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_SYNCRO',trim($_POST["phpwebcalendar_syncro"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_COMPANYCREATE',trim($_POST["phpwebcalendar_companycreate"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_CONTRACTSTATUS',trim($_POST["phpwebcalendar_contractstatus"]),'',0);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_BILLSTATUS',trim($_POST["phpwebcalendar_billstatus"]),'',0);

    if ($i >= 9)
    {
        $db->commit();
        header("Location: webcalendar.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"ok\">".$langs->trans("WebCalSetupSaved")."</font>";
    }
}
elseif ($actiontest)
{
    //$resql=$db->query("select count(*) from llx_const");
    //print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";

    // Test de la connection a la database webcalendar
    $conf->webcal->db->type=$dolibarr_main_db_type;
    $conf->webcal->db->host=$_POST["phpwebcalendar_host"];
    $conf->webcal->db->user=$_POST["phpwebcalendar_user"];
    $conf->webcal->db->pass=$_POST["phpwebcalendar_pass"];
    $conf->webcal->db->name=$_POST["phpwebcalendar_dbname"];

    $webcal=new WebCal();

    //print "D ".$db." - ".$db->db."<br>\n";
    //print "W ".$webcal->localdb." - ".$webcal->localdb->db."<br>\n";
    
    if ($webcal->localdb->connected == 1 && $webcal->localdb->database_selected == 1)
    {
        // Vérifie si bonne base
        $sql="SELECT cal_value FROM webcal_config WHERE cal_setting='application_name'";
        $resql=$webcal->localdb->query($sql);
        if ($resql) {
            $mesg ="<div class=\"ok\">".$langs->trans("WebCalTestOk",$_POST["phpwebcalendar_host"],$_POST["phpwebcalendar_dbname"],$_POST["phpwebcalendar_user"]);
            $mesg.="</div>";
        }
        else {
            $mesg ="<div class=\"error\">".$langs->trans("ErrorConnectOkButWrongDatabase");
            $mesg.="</div>";
        }

        //$webcal->localdb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
    }
    elseif ($webcal->connected == 1 && $webcal->database_selected != 1)
    {
        $mesg ="<div class=\"error\">".$langs->trans("WebCalTestKo1",$_POST["phpwebcalendar_host"],$_POST["phpwebcalendar_dbname"]);
        $mesg.="<br>".$webcal->localdb->error();
        $mesg.="</div>";
        //$webcal->localdb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
    }
    else
    {
        $mesg ="<div class=\"error\">".$langs->trans("WebCalTestKo2",$_POST["phpwebcalendar_host"],$_POST["phpwebcalendar_user"]);
        $mesg.="<br>".$webcal->localdb->error();
        $mesg.="</div>";
    }

    //$resql=$db->query("select count(*) from llx_const");
    //print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();

print_titre($langs->trans("WebCalSetup"));
print '<br>';


print '<form name="phpwebcalendarconfig" action="webcalendar.php" method="post">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("WebCalURL")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpwebcalendar_url\" value=\"". ($_POST["phpwebcalendar_url"]?$_POST["phpwebcalendar_url"]:$conf->global->PHPWEBCALENDAR_URL) . "\" size=\"40\"></td>";
print "<td>http://localhost/webcalendar/";
print "<br>https://webcalendarserver/";
print "</td>";
print "</tr>";
print "<tr class=\"pair\">";
print "<td>".$langs->trans("WebCalServer")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpwebcalendar_host\" value=\"". ($_POST["phpwebcalendar_host"]?$_POST["phpwebcalendar_host"]:$conf->global->PHPWEBCALENDAR_HOST) . "\" size=\"30\"></td>";
print "<td>localhost";
//print "<br>__dolibarr_main_db_host__ <i>(".$dolibarr_main_db_host.")</i>"
print "</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("WebCalDatabaseName")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpwebcalendar_dbname\" value=\"". ($_POST["phpwebcalendar_dbname"]?$_POST["phpwebcalendar_dbname"]:$conf->global->PHPWEBCALENDAR_DBNAME) . "\" size=\"30\"></td>";
print "<td>webcalendar";
//print "<br>__dolibarr_main_db_name__ <i>(".$dolibarr_main_db_name.")</i>";
print "</td>";
print "</tr>";
print "<tr class=\"pair\">";
print "<td>".$langs->trans("WebCalUser")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpwebcalendar_user\" value=\"". ($_POST["phpwebcalendar_user"]?$_POST["phpwebcalendar_user"]:$conf->global->PHPWEBCALENDAR_USER) . "\" size=\"30\"></td>";
print "<td>webcaluser";
//print "<br>__dolibarr_main_db_user__ <i>(".$dolibarr_main_db_user.")</i>";
print "</td>";
print "</tr>";
print "<tr class=\"impair\">";
print "<td>".$langs->trans("Password")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"phpwebcalendar_pass\" value=\"" . $_POST["phpwebcalendar_pass"] . "\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.eregi_replace('.','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";
print "<tr class=\"pair\">";
print "<td>".$langs->trans("PasswordRetype")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"phpwebcalendar_pass2\" value=\"" . $_POST["phpwebcalendar_pass2"] ."\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.eregi_replace('.','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";
print "</table>";
print "<br>";

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td colspan=\"2\">".$langs->trans("WebCalSyncro")."</td>";
print "</tr>";
if ($conf->societe->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnCreateActions").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_syncro" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_SYNCRO=='always'?' selected':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="yesbydefault"'.($conf->global->PHPWEBCALENDAR_SYNCRO=='yesbydefault'?' selected':'').'>'.$langs->trans("WebCalYesByDefault").'</option>';
    print '<option value="nobydefault"'.((! $conf->global->PHPWEBCALENDAR_SYNCRO || $conf->global->PHPWEBCALENDAR_SYNCRO=='nobydefault')?' selected':'').'>'.$langs->trans("WebCalNoByDefault").'</option>';
    print '<option value="never"'.($conf->global->PHPWEBCALENDAR_SYNCRO=='never'?' selected':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->societe->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnCreateCompany").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_companycreate" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_COMPANYCREATE=='always'?' selected':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_COMPANYCREATE || $conf->global->PHPWEBCALENDAR_COMPANYCREATE=='never'?' selected':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->contrat->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnStatusContract").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_contractstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?' selected':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_CONTRACTSTATUS || $conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='never'?' selected':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->facture->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnStatusBill").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_billstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?' selected':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_BILLSTATUS || $conf->global->PHPWEBCALENDAR_BILLSTATUS=='never'?' selected':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
    print '</table>';
}
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
