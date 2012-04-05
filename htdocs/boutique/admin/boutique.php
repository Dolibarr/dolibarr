<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012 Juanjo Menent		   <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 *  \file 		htdocs/boutique/admin/boutique.php
 *  \ingroup    boutique
 *  \brief      Page d'administration/configuration du module OsCommerce
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("oscommerce");

if (!$user->admin) accessforbidden();



/*
 * Actions
 */

if (GETPOST('save','alpha'))
{
	$db->begin();

	$i=0;

	$i+=dolibarr_set_const($db,'OSC_DB_HOST',trim(GETPOST('oscommerce_dbhost','alpha')),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'OSC_DB_NAME',trim(GETPOST('oscommerce_dbname','alpha')),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'OSC_DB_USER',trim(GETPOST('oscommerce_dbuser','alpha')),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'OSC_DB_PASS',trim(GETPOST('oscommerce_dbpass','alpha')),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'OSC_DB_TABLE_PREFIX',trim(GETPOST('oscommerce_db_table_prefix','alpha')),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'OSC_LANGUAGE_ID',1,'chaine',0,'',$conf->entity);

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
elseif (GETPOST('test','alpha'))
{
	//$resql=$db->query("select count(*) from ".MAIN_DB_PREFIX."const");
	//print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";

	// Test de la connexion a la database webcalendar
	$conf->oscommerce->db->type=$dolibarr_main_db_type;
	$conf->oscommerce->db->host=GETPOST('oscommerce_dbhost','alpha');
	$conf->oscommerce->db->port=GETPOST('oscommerce_dbport','alpha');
	$conf->oscommerce->db->name=GETPOST('oscommerce_dbname','alpha');
	$conf->oscommerce->db->user=GETPOST('oscommerce_dbuser','alpha');
	$conf->oscommerce->db->pass=GETPOST('oscommerce_dbpass','alpha');

    $oscommercedb=getDoliDBInstance($conf->oscommerce->db->type,$conf->oscommerce->db->host,$conf->oscommerce->db->user,$conf->oscommerce->db->pass,$conf->oscommerce->db->name,$conf->oscommerce->db->port);

	if ($oscommercedb->connected == 1 && $oscommercedb->database_selected == 1)
	{
		// Verifie si bonne base par requete sur une table OSCommerce
		$sql ="SELECT configuration_value";
		$sql.=" FROM ".$_POST["oscommerce_db_table_prefix"]."configuration";
		$sql.=" WHERE configuration_key='STORE_NAME'";
		$resql=$oscommercedb->query($sql);
		if ($resql) {
			$mesg ="<div class=\"ok\">".$langs->trans("OSCommerceTestOk",GETPOST('oscommerce_dbhost','alpha'),GETPOST('oscommerce_dbname','alpha'),GETPOST('oscommerce_dbuser','alpha'));
			$mesg.="</div>";
		}
		else {
			$mesg ="<div class=\"error\">".$langs->trans("OSCommerceErrorConnectOkButWrongDatabase",'STORE_NAME',GETPOST('oscommerce_db_table_prefix','alpha')."configuration");
			$mesg.="</div>";
		}

		//$oscommercedb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
	}
	elseif ($oscommercedb->connected == 1 && $oscommercedb->database_selected != 1)
	{
		$mesg ="<div class=\"error\">".$langs->trans("OSCommerceTestKo1",GETPOST('oscommerce_dbhost','alpha'),GETPOST('oscommerce_dbname','alpha'));
		$mesg.="<br>".$oscommercedb->error();
		$mesg.="</div>";
		//$oscommercedb->close();    Ne pas fermer car la conn de webcal est la meme que dolibarr si parametre host/user/pass identique
	}
	else
	{
		$mesg ="<div class=\"error\">".$langs->trans("OSCommerceTestKo2",GETPOST('oscommerce_dbhost','alpha'),GETPOST('oscommerce_dbuser','alpha'));
		$mesg.="<br>".$oscommercedb->error();
		$mesg.="</div>";
	}

	//$resql=$db->query("select count(*) from ".MAIN_DB_PREFIX."const");
	//print "< ".$db." - ".$db->db." - ".$resql." - ".$db->error()."><br>\n";
}



/*
 * Affichage page
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("OSCommerceSetup"),$linkback,'setup');



print '<br>';

$var=true;
print '<form name="oscommerceconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td width=\"40%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCommerceServer")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_dbhost\" value=\"". (GETPOST('oscommerce_dbhost','alpha')?GETPOST('oscommerce_dbhost','alpha'):$conf->global->OSC_DB_HOST) . "\" size=\"30\"></td>";
print "<td>localhost";
//print "<br>__dolibarr_main_db_host__ <i>(".$dolibarr_main_db_host.")</i>"
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCommerceDatabaseName")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_dbname\" value=\"". (GETPOST('oscommerce_dbname','alpha')?GETPOST('oscommerce_dbname','alpha'):$conf->global->OSC_DB_NAME) . "\" size=\"30\"></td>";
print "<td>oscommerce";
//print "<br>__dolibarr_main_db_name__ <i>(".$dolibarr_main_db_name.")</i>";
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCommercePrefix")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_db_table_prefix\" value=\"". (GETPOST('oscommerce_db_table_prefix','alpha')?GETPOST('oscommerce_db_table_prefix','alpha'):$conf->global->DB_TABLE_PREFIX) . "\" size=\"30\"></td>";
print "<td>osc_";
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("OSCommerceUser")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"oscommerce_dbuser\" value=\"". (GETPOST('oscommerce_dbuser','alpha')?GETPOST('oscommerce_dbuser','alpha'):$conf->global->OSC_DB_USER) . "\" size=\"30\"></td>";
print "<td>oscommerceuser";
//print "<br>__dolibarr_main_db_user__ <i>(".$dolibarr_main_db_user.")</i>";
print "</td>";
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("Password")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"oscommerce_dbpass\" value=\"" . (GETPOST('oscommerce_dbpass','alpha')?GETPOST('oscommerce_dbpass','alpha'):$conf->global->OSC_DB_PASS) . "\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.preg_replace('/./i','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";
$var=!$var;
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("PasswordRetype")."</td>";
print "<td><input type=\"password\" class=\"flat\" name=\"oscommerce_dbpass2\" value=\"" . (GETPOST('oscommerce_dbpass2','alpha')?GETPOST('oscommerce_dbpass2','alpha'):$conf->global->OSC_DB_PASS) ."\" size=\"30\"></td>";
print '<td>';
//if ($dolibarr_main_db_pass) print '__dolibarr_main_db_pass__ <i>('.preg_replace('/./i','*',$dolibarr_main_db_pass).')</i>';
print '&nbsp;</td>';
print "</tr>";
print "</table>";


print '<br><center>';
print "<input type=\"submit\" name=\"test\" class=\"button\" value=\"".$langs->trans("TestConnection")."\">";
print "&nbsp; &nbsp;";
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";


dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
