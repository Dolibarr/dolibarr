<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       htdocs/admin/societe.php
		\ingroup    propale
		\brief      Page d'administration/configuration du module Societe
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'setcodeclient')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECLIENT_ADDON",$_GET["value"]))
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dolibarr_print_error($db);	
	}
}

if ($_GET["action"] == 'setcodecompta')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECOMPTA_ADDON",$_GET["value"]))
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dolibarr_print_error($db);	
	}
}

if ($_POST["action"] == 'usesearchtoselectcompany')
{
  if (dolibarr_set_const($db, "COMPANY_USE_SEARCH_TO_SELECT", $_POST["activate_usesearchtoselectcompany"]))
  {
  	Header("Location: ".$_SERVER["PHP_SELF"]);
  	exit;
  }
  else
  {
  	dolibarr_print_error($db);
  }
}

// défini les constantes du modèle tigre
if ($_POST["action"] == 'updateMask')
{
	dolibarr_set_const($db, "CODE_TIGRE_MASK_CUSTOMER",$_POST["maskcustomer"]);
	dolibarr_set_const($db, "CODE_TIGRE_MASK_SUPPLIER",$_POST["masksupplier"]);
}


/*
 * 	Affichage page configuration module societe
 *
 */

$form=new Form($db);


llxHeader();

print_fiche_titre($langs->trans("CompanySetup"),'','setup');

print "<br>";


// Choix du module de gestion des codes clients / fournisseurs

print_titre($langs->trans("CompanyCodeChecker"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td align="center">'.$langs->trans("Activated").'</td>';
print '  <td>&nbsp;</td>';
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
	      print "  <td align=\"center\">\n";
    	  print img_tick();
	      print "</td>\n  <td>&nbsp;</td>\n";
	    }
	  else
	    {

	      print '<td>&nbsp;</td>';
	      print '<td align="center"><a href="societe.php?action=setcodeclient&amp;value='.$file.'">'.$langs->trans("Activate").'</a></td>';
	    }
	  
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
print '<td>&nbsp;</td>';
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
	      print '</td><td>&nbsp;</td>';
	    }
	  else
	    {
	      print '<td>&nbsp;</td>';
	      print '<td align="center"><a href="societe.php?action=setcodecompta&amp;value='.$file.'">'.$langs->trans("Activate").'</a></td>';

	    }
	  
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

// utilisation formulaire Ajax sur choix société
$var=!$var;
print "<form method=\"post\" action=\"societe.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"usesearchtoselectcompany\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("UseSearchToSelectCompany").'</td>';
if (! $conf->use_ajax)
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
