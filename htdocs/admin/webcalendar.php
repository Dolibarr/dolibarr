<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/admin/webcalendar.php
        \ingroup    webcalendar
        \brief      Page de configuration du module webcalendar
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/webcal/webcal.class.php');


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");

$def = array();
$actiontest=$_POST["test"];
$actionsave=$_POST["save"];

// Sauvegardes parametres
if ($actionsave)
{
    $i=0;

    $db->begin();
    
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_URL',trim($_POST["phpwebcalendar_url"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_HOST',trim($_POST["phpwebcalendar_host"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_DBNAME',trim($_POST["phpwebcalendar_dbname"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_USER',trim($_POST["phpwebcalendar_user"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_PASS',trim($_POST["phpwebcalendar_pass"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_PASSWORD_VCALEXPORT',trim($_POST["PHPWEBCALENDAR_PASSWORD_VCALEXPORT"]),'chaine',0,'',$conf->entity);

    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_SYNCRO',trim($_POST["phpwebcalendar_syncro"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_COMPANYCREATE',trim($_POST["phpwebcalendar_companycreate"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_PROPALSTATUS',trim($_POST["phpwebcalendar_propalstatus"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_CONTRACTSTATUS',trim($_POST["phpwebcalendar_contractstatus"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_BILLSTATUS',trim($_POST["phpwebcalendar_billstatus"]),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'PHPWEBCALENDAR_MEMBERSTATUS',trim($_POST["phpwebcalendar_memberstatus"]),'chaine',0,'',$conf->entity);

    if ($i >= 9)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("WebCalSetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
}
elseif ($actiontest)
{
    //$resql=$db->query("select count(*) from llx_const");
    //print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";

    // Test de la connexion a la database webcalendar
    $conf->webcal->db->type=$dolibarr_main_db_type;
    $conf->webcal->db->host=$_POST["phpwebcalendar_host"];
    $conf->webcal->db->port=$_POST["phpwebcalendar_port"];
    $conf->webcal->db->user=$_POST["phpwebcalendar_user"];
    $conf->webcal->db->pass=$_POST["phpwebcalendar_pass"];
    $conf->webcal->db->name=$_POST["phpwebcalendar_dbname"];

    $webcal=new WebCal();

    //print "D ".$db." - ".$db->db."<br>\n";
    //print "W ".$webcal->localdb." - ".$webcal->localdb->db."<br>\n";
    
    if ($webcal->localdb->connected == 1 && $webcal->localdb->database_selected == 1)
    {
        // Verifie si bonne base
        $sql="SELECT cal_value FROM webcal_config WHERE cal_setting='application_name'";
        $resql=$webcal->localdb->query($sql);
        if ($resql) {
			# Search version
			$webcal->version='';
			$sql="SELECT cal_value FROM webcal_config WHERE cal_setting='WEBCAL_PROGRAM_VERSION'";
			$resql=$webcal->localdb->query($sql);
			if ($resql) {
				$obj=$webcal->localdb->fetch_object($resql);
				if ($obj)
				{
					$webcal->version=$obj->cal_value;
				}
			}

			$mesg ="<div class=\"ok\">";
            $mesg.=$langs->trans("WebCalTestOk",$_POST["phpwebcalendar_host"],$_POST["phpwebcalendar_dbname"],$_POST["phpwebcalendar_user"]);
            $mesg.='<br>'.$langs->trans("DetectedVersion").': '.($webcal->version?$webcal->version:$langs->trans("NotAvailable"));
            $mesg.="</div>";
        }
        else {
            $mesg ="<div class=\"error\">";
            $mesg.=$langs->trans("WebCalErrorConnectOkButWrongDatabase");
            $mesg.="</div>";
        }
		// Ne pas fermer car la conn de webcal est la meme que dolibarr si
		// parametre host/user/pass identique.
        //$webcal->localdb->close();
    }
    elseif ($webcal->localdb->connected == 1 && $webcal->localdb->database_selected != 1)
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

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("WebCalSetup"),$linkback,'setup');
print '<br>';


print '<form name="phpwebcalendarconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
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
print "<td><input type=\"password\" class=\"flat\" name=\"phpwebcalendar_pass\" value=\"" . ($_POST["phpwebcalendar_pass"]?$_POST["phpwebcalendar_pass"]:$conf->global->PHPWEBCALENDAR_PASS) . "\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.eregi_replace('.','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("PasswordTogetVCalExport")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"PHPWEBCALENDAR_PASSWORD_VCALEXPORT\" value=\"". ($_POST["PHPWEBCALENDAR_PASSWORD_VCALEXPORT"]?$_POST["PHPWEBCALENDAR_PASSWORD_VCALEXPORT"]:$conf->global->PHPWEBCALENDAR_PASSWORD_VCALEXPORT) . "\" size=\"40\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "</table>";
print "<br>";

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td colspan=\"3\">".$langs->trans("WebCalSyncro")."</td>";
print "</tr>";
if ($conf->agenda->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnCreateActions").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_syncro" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_SYNCRO=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="yesbydefault"'.($conf->global->PHPWEBCALENDAR_SYNCRO=='yesbydefault'?' selected="true"':'').'>'.$langs->trans("WebCalYesByDefault").'</option>';
    print '<option value="nobydefault"'.((! $conf->global->PHPWEBCALENDAR_SYNCRO || $conf->global->PHPWEBCALENDAR_SYNCRO=='nobydefault')?' selected="true"':'').'>'.$langs->trans("WebCalNoByDefault").'</option>';
    print '<option value="never"'.($conf->global->PHPWEBCALENDAR_SYNCRO=='never'?' selected="true"':'').'>'.$langs->trans("WebCalNever").'</option>';
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
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_COMPANYCREATE=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_COMPANYCREATE || $conf->global->PHPWEBCALENDAR_COMPANYCREATE=='never'?' selected="true"':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->propal->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnStatusPropal").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_propalstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_PROPALSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_PROPALSTATUS || $conf->global->PHPWEBCALENDAR_PROPALSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("WebCalNever").'</option>';
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
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_CONTRACTSTATUS || $conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("WebCalNever").'</option>';
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
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_BILLSTATUS || $conf->global->PHPWEBCALENDAR_BILLSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->adherent->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("WebCalAddEventOnStatusMember").'</td>';
    print '<td>';
    print '<select name="phpwebcalendar_memberstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPWEBCALENDAR_MEMBERSTATUS || $conf->global->PHPWEBCALENDAR_MEMBERSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("WebCalNever").'</option>';
    print '</select>';
    print '</td></tr>';
}
print '</table>';


print '<br><center>';
print "<input type=\"submit\" name=\"test\" class=\"button\" value=\"".$langs->trans("TestConnection")."\">";
print "&nbsp; &nbsp;";
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";


clearstatcache();

if ($mesg) print "<br>$mesg<br>";
print "<br>";

// Show message
$message='';
$urlwithouturlroot=eregi_replace(DOL_URL_ROOT.'$','',$dolibarr_main_url_root);
$urlvcal='<a href="'.DOL_URL_ROOT.'/webcal/webcalexport.php?format=vcal&exportkey='.$conf->global->PHPWEBCALENDAR_PASSWORD_VCALEXPORT.'" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/webcal/webcalexport.php?format=vcal&exportkey='.$conf->global->PHPWEBCALENDAR_PASSWORD_VCALEXPORT.'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'vcal',$urlvcal);
$message.='<br>';
$urlical='<a href="'.DOL_URL_ROOT.'/webcal/webcalexport.php?format=ical&type=event&exportkey='.$conf->global->PHPWEBCALENDAR_PASSWORD_VCALEXPORT.'" target="_blank">'.$urlwithouturlroot.DOL_URL_ROOT.'/webcal/webcalexport.php?format=ical&type=event&exportkey='.$conf->global->PHPWEBCALENDAR_PASSWORD_VCALEXPORT.'</a>';
$message.=$langs->trans("WebCalUrlForVCalExport",'ical',$urlical);
print info_admin($message);

$db->close();

llxFooter('$Date$ - $Revision$');
?>
