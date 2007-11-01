<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/admin/phenix.php
        \ingroup    phenix
        \brief      Page de configuration du module Phenix
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/phenix.class.php');


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
    
    $i+=dolibarr_set_const($db,'PHPPHENIX_URL',trim($_POST["phpphenix_url"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_HOST',trim($_POST["phpphenix_host"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_DBNAME',trim($_POST["phpphenix_dbname"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_USER',trim($_POST["phpphenix_user"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_PASS',trim($_POST["phpphenix_pass"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_COOKIE',trim($_POST["phpphenix_cookie"]),'chaine',0);

    $i+=dolibarr_set_const($db,'PHPPHENIX_SYNCRO',trim($_POST["phpphenix_syncro"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_COMPANYCREATE',trim($_POST["phpphenix_companycreate"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_PROPALSTATUS',trim($_POST["phpphenix_propalstatus"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_CONTRACTSTATUS',trim($_POST["phpphenix_contractstatus"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_BILLSTATUS',trim($_POST["phpphenix_billstatus"]),'chaine',0);
    $i+=dolibarr_set_const($db,'PHPPHENIX_MEMBERSTATUS',trim($_POST["phpphenix_memberstatus"]),'chaine',0);

    if ($i >= 9)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("PhenixSetupSaved")."</font>";
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

    // Test de la connexion a la database phenix
    $conf->phenix->db->type=$dolibarr_main_db_type;
    $conf->phenix->db->host=$_POST["phpphenix_host"];
    $conf->phenix->db->user=$_POST["phpphenix_user"];
    $conf->phenix->db->pass=$_POST["phpphenix_pass"];
    $conf->phenix->db->name=$_POST["phpphenix_dbname"];

    $phenix=new Phenix();

    //print "D ".$db." - ".$db->db."<br>\n";
    //print "W ".$phenix->localdb." - ".$phenix->localdb->db."<br>\n";
    
    if ($phenix->localdb->connected == 1 && $phenix->localdb->database_selected == 1)
    {
    	// Vérifie si bonne base
      /*
      $sql="SELECT cal_value FROM webcal_config WHERE cal_setting='application_name'";
      $resql=$phenix->localdb->query($sql);
      if ($resql)
      {
      	# Search version
      	$webcal->version='';
      	$sql="SELECT cal_value FROM webcal_config WHERE cal_setting='WEBCAL_PROGRAM_VERSION'";
      	$resql=$webcal->localdb->query($sql);
      	if ($resql)
      	{
      		$obj=$webcal->localdb->fetch_object($resql);
      		if ($obj)
      		{
      			$webcal->version=$obj->cal_value;
      		}
      	}
      	*/
      	$mesg ="<div class=\"ok\">";
        $mesg.=$langs->trans("WebCalTestOk",$_POST["phpphenix_host"],$_POST["phpphenix_dbname"],$_POST["phpphenix_user"]);
        //$mesg.='<br>'.$langs->trans("DetectedVersion").': '.($webcal->version?$webcal->version:$langs->trans("NotAvailable"));
        $mesg.="</div>";
        /*
        }
        else {
            $mesg ="<div class=\"error\">";
            $mesg.=$langs->trans("WebCalErrorConnectOkButWrongDatabase");
            $mesg.="</div>";
        }
        */
		// Ne pas fermer car la conn de webcal est la meme que dolibarr si
		// parametre host/user/pass identique.
        //$webcal->localdb->close();
    }
    elseif ($phenix->connected == 1 && $phenix->database_selected != 1)
    {
        $mesg ="<div class=\"error\">".$langs->trans("PhenixTestKo1",$_POST["phpphenix_host"],$_POST["phpphenix_dbname"]);
        $mesg.="<br>".$phenix->localdb->error();
        $mesg.="</div>";
        //$webcal->localdb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
    }
    else
    {
        $mesg ="<div class=\"error\">".$langs->trans("PhenixTestKo2",$_POST["phpphenix_host"],$_POST["phpphenix_user"]);
        $mesg.="<br>".$phenix->localdb->error();
        $mesg.="</div>";
    }

    //$resql=$db->query("select count(*) from llx_const");
    //print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";
}


/**
 * Affichage du formulaire de saisie
 */

llxHeader();

print_fiche_titre($langs->trans("PhenixSetup"),'','setup');
print '<br>';


print '<form name="phpphenixconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("PhenixURL")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpphenix_url\" value=\"". ($_POST["phpphenix_url"]?$_POST["phpphenix_url"]:$conf->global->PHPPHENIX_URL) . "\" size=\"40\"></td>";
print "<td>http://localhost/phenix/";
print "<br>https://phenixserver/";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("PhenixServer")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpphenix_host\" value=\"". ($_POST["phpphenix_host"]?$_POST["phpphenix_host"]:$conf->global->PHPPHENIX_HOST) . "\" size=\"30\"></td>";
print "<td>localhost";
//print "<br>__dolibarr_main_db_host__ <i>(".$dolibarr_main_db_host.")</i>"
print "</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("PhenixDatabaseName")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpphenix_dbname\" value=\"". ($_POST["phpphenix_dbname"]?$_POST["phpphenix_dbname"]:$conf->global->PHPPHENIX_DBNAME) . "\" size=\"30\"></td>";
print "<td>phenix";
//print "<br>__dolibarr_main_db_name__ <i>(".$dolibarr_main_db_name.")</i>";
print "</td>";
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("PhenixUser")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpphenix_user\" value=\"". ($_POST["phpphenix_user"]?$_POST["phpphenix_user"]:$conf->global->PHPPHENIX_USER) . "\" size=\"30\"></td>";
print "<td>phenixuser";
//print "<br>__dolibarr_main_db_user__ <i>(".$dolibarr_main_db_user.")</i>";
print "</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("Password")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"phpphenix_pass\" value=\"" . ($_POST["phpphenix_pass"]?$_POST["phpphenix_pass"]:$conf->global->PHPPHENIX_PASS) . "\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.eregi_replace('.','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";

print "<tr class=\"pair\">";
print "<td>".$langs->trans("PhenixCookie")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"phpphenix_cookie\" value=\"". ($_POST["phpphenix_cookie"]?$_POST["phpphenix_cookie"]:$conf->global->PHPPHENIX_COOKIE) . "\" size=\"30\"></td>";
print "<td>PXlogin";
print "</td>";
print "</tr>";

print "</table>";
print "<br>";

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td colspan=\"2\">".$langs->trans("PhenixSyncro")."</td>";
print "</tr>";
if ($conf->societe->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("PhenixAddEventOnCreateActions").'</td>';
    print '<td>';
    print '<select name="phpphenix_syncro" class="flat">';
    print '<option value="always"'.($conf->global->PHPPHENIX_SYNCRO=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="yesbydefault"'.($conf->global->PHPPHENIX_SYNCRO=='yesbydefault'?' selected="true"':'').'>'.$langs->trans("WebCalYesByDefault").'</option>';
    print '<option value="nobydefault"'.((! $conf->global->PHPPHENIX_SYNCRO || $conf->global->PHPPHENIX_SYNCRO=='nobydefault')?' selected="true"':'').'>'.$langs->trans("WebCalNoByDefault").'</option>';
    print '<option value="never"'.($conf->global->PHPPHENIX_SYNCRO=='never'?' selected="true"':'').'>'.$langs->trans("Never").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->societe->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("PhenixAddEventOnCreateCompany").'</td>';
    print '<td>';
    print '<select name="phpphenix_companycreate" class="flat">';
    print '<option value="always"'.($conf->global->PHPPHENIX_COMPANYCREATE=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPPHENIX_COMPANYCREATE || $conf->global->PHPPHENIX_COMPANYCREATE=='never'?' selected="true"':'').'>'.$langs->trans("Never").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->propal->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("PhenixAddEventOnStatusPropal").'</td>';
    print '<td>';
    print '<select name="phpphenix_propalstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPPHENIX_PROPALSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPPHENIX_PROPALSTATUS || $conf->global->PHPPHENIX_PROPALSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("Never").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->contrat->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("phenixAddEventOnStatusContract").'</td>';
    print '<td>';
    print '<select name="phpphenix_contractstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPPHENIX_CONTRACTSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPPHENIX_CONTRACTSTATUS || $conf->global->PHPPHENIX_CONTRACTSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("Never").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->facture->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("PhenixAddEventOnStatusBill").'</td>';
    print '<td>';
    print '<select name="phpphenix_billstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPPHENIX_BILLSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPPHENIX_BILLSTATUS || $conf->global->PHPPHENIX_BILLSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("Never").'</option>';
    print '</select>';
    print '</td></tr>';
}
if ($conf->adherent->enabled)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("PhenixAddEventOnStatusMember").'</td>';
    print '<td>';
    print '<select name="phpphenix_memberstatus" class="flat">';
    print '<option value="always"'.($conf->global->PHPPHENIX_MEMBERSTATUS=='always'?' selected="true"':'').'>'.$langs->trans("WebCalAllways").'</option>';
    print '<option value="never"'.(! $conf->global->PHPPHENIX_MEMBERSTATUS || $conf->global->PHPPHENIX_MEMBERSTATUS=='never'?' selected="true"':'').'>'.$langs->trans("Never").'</option>';
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

if ($mesg) print "<br>".$mesg."<br>";
print "<br>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
