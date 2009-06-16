<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/admin/societe.php
 *	\ingroup    company
 *	\brief      Third party module setup page
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'setcodeclient')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECLIENT_ADDON",$_GET["value"],'chaine',0,'',$conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_GET["action"] == 'setcodecompta')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECOMPTA_ADDON",$_GET["value"],'chaine',0,'',$conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_POST["action"] == 'usesearchtoselectcompany')
{
	if (dolibarr_set_const($db, "COMPANY_USE_SEARCH_TO_SELECT", $_POST["activate_usesearchtoselectcompany"],'chaine',0,'',$conf->entity))
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// define constants for tigre model
if ($_POST["action"] == 'updateMask')
{
	dolibarr_set_const($db, "COMPANY_ELEPHANT_MASK_CUSTOMER",$_POST["maskcustomer"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "COMPANY_ELEPHANT_MASK_SUPPLIER",$_POST["masksupplier"],'chaine',0,'',$conf->entity);
}


/*
 * 	View
 */

$form=new Form($db);


llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CompanySetup"),$linkback,'setup');

print "<br>";


// Choix du module de gestion des codes clients / fournisseurs

print_titre($langs->trans("CompanyCodeChecker"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td align="center">'.$langs->trans("Activated").'</td>';
print '  <td align="center" width="20">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/societe/";
$handle = opendir($dir);
if ($handle)
{
	$var = true;

	// Loop on each module find in opened directory
	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 15) == 'mod_codeclient_' && substr($file, -3) == 'php')
		{
			$file = substr($file, 0, strlen($file)-4);

			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$file.".php");

			$modCodeTiers = new $file;

			// Show modules according to features level
			if ($modCodeTiers->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
			if ($modCodeTiers->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

			$var = !$var;
			print "<tr ".$bc[$var].">\n  <td width=\"140\">".$modCodeTiers->nom."</td>\n  <td>";
			print $modCodeTiers->info($langs);
			print "</td>\n";
			print '<td nowrap="nowrap">'.$modCodeTiers->getExample($langs)."</td>\n";

			if ($conf->global->SOCIETE_CODECLIENT_ADDON == "$file")
			{
				print "<td align=\"center\">\n";
				print img_tick();
				print "</td>\n";
			}
			else
			{
				print '<td align="center"><a href="societe.php?action=setcodeclient&amp;value='.$file.'">'.$langs->trans("Activate").'</a></td>';
			}

			print '<td align="center">';
			$s=$modCodeTiers->getToolTip($langs,$soc,-1);
			print $form->textwithpicto('',$s,1);
			print '</td>';

			print '</tr>';
		}
	}
	closedir($handle);
}
print '</table>';


print "<br>";


// Choix du module de gestion des codes compta

print_titre($langs->trans("AccountCodeManager"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/societe/";
$handle = opendir($dir);
if ($handle)
{
	$var = true;
	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 15) == 'mod_codecompta_' && substr($file, -3) == 'php')
		{
	  $file = substr($file, 0, strlen($file)-4);

	  require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$file.".php");

	  $modCodeCompta = new $file;
	  $var = !$var;

	  print '<tr '.$bc[$var].'>';
	  print '<td width="140">'.$modCodeCompta->nom."</td><td>\n";
	  print $modCodeCompta->info($langs);
	  print '</td>';
	  print '<td nowrap="nowrap">'.$modCodeCompta->getExample($langs)."</td>\n";

	  if ($conf->global->SOCIETE_CODECOMPTA_ADDON == "$file")
	  {
	  	print '<td align="center">';
	  	print img_tick();
	  	print '</td>';
	  }
	  else
	  {
	  	print '<td align="center"><a href="societe.php?action=setcodecompta&amp;value='.$file.'">'.$langs->trans("Activate").'</a></td>';

	  }
	  print '<td>&nbsp;</td>';
	  print "</tr>\n";
		}
	}
	closedir($handle);
}
print "</table>\n";

print '<br>';


// Autres options
$html=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("Parameters")."</td>\n";
print "  <td align=\"right\" width=\"60\">".$langs->trans("Value")."</td>\n";
print "  <td width=\"80\">&nbsp;</td></tr>\n";

// Utilisation formulaire Ajax sur choix societe
$var=!$var;
print "<form method=\"post\" action=\"societe.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"usesearchtoselectcompany\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("UseSearchToSelectCompany").'</td>';
if (! $conf->use_javascript_ajax)
{
	print '<td nowrap="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
}
else
{
	print '<td width="60" align="right">';
	print $html->selectyesno("activate_usesearchtoselectcompany",$conf->global->COMPANY_USE_SEARCH_TO_SELECT,1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';
print '</form>';

print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
