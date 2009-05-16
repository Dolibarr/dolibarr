<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
	    \file       htdocs/admin/confexped.php
		\ingroup    produit
		\brief      Page d'administration/configuration du module Expedition
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("sendings");
$langs->load("deliveries");

if (!$user->admin)
  accessforbidden();



if ($_GET["action"] == 'activate_sending')
{
    dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1",'chaine',0,'',$conf->entity);
    Header("Location: confexped.php");
    exit;
}
else if ($_GET["action"] == 'disable_sending')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_EXPEDITION",$conf->entity);
    Header("Location: confexped.php");
    exit;
}
else if ($_GET["action"] == 'activate_delivery')
{
			dolibarr_set_const($db, "MAIN_SUBMODULE_LIVRAISON", "1",'chaine',0,'',$conf->entity);
			Header("Location: confexped.php");
			exit;
}
else if ($_GET["action"] == 'disable_delivery')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_LIVRAISON",$conf->entity);
    Header("Location: confexped.php");
    exit;
}


/*
 * Affiche page
 */
$dir = DOL_DOCUMENT_ROOT."/includes/modules/expedition/";
$html=new Form($db);

llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Setup"),$linkback,'setup');
print '<br>';

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$hselected=$h;
$h++;

if ($conf->global->MAIN_SUBMODULE_EXPEDITION)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
	$head[$h][1] = $langs->trans("Sending");
	$h++;
}

if ($conf->global->MAIN_SUBMODULE_LIVRAISON)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
	$head[$h][1] = $langs->trans("Receivings");
	$h++;
}

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 * Formulaire parametres divers
 */

$var=true;

// expedition activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"confexped.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Feature").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Action").'</td>';
print "</tr>\n";
print "<input type=\"hidden\" name=\"action\" value=\"sending\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("SendingsAbility").'</td>';
print '<td align="center" width="20">';

if($conf->global->MAIN_SUBMODULE_EXPEDITION == 1)
{
	print img_tick();
}

print '</td>';
print '<td align="center" width="100">';

if($conf->global->MAIN_SUBMODULE_EXPEDITION == 0)
{
	print '<a href="confexped.php?action=activate_sending">'.$langs->trans("Activate").'</a>';
}
else if($conf->global->MAIN_SUBMODULE_EXPEDITION == 1)
{
	print '<a href="confexped.php?action=disable_sending">'.$langs->trans("Disable").'</a>';
}

print "</td>";
print '</tr>';
print '</table>';
print '</form>';

// Bon de livraison activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"confexped.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print "<input type=\"hidden\" name=\"action\" value=\"delivery\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("DeliveriesOrderAbility").'</td>';
print '<td align="center" width="20">';

if($conf->global->MAIN_SUBMODULE_LIVRAISON == 1)
{
	print img_tick();
}

print '</td>';
print '<td align="center" width="100">';

if($conf->global->MAIN_SUBMODULE_LIVRAISON == 0)
{
	print '<a href="confexped.php?action=activate_delivery">'.$langs->trans("Activate").'</a>';
}
else if($conf->global->MAIN_SUBMODULE_LIVRAISON == 1)
{
	print '<a href="confexped.php?action=disable_delivery">'.$langs->trans("Disable").'</a>';
}

print "</td>";
print '</tr>';
print '</table>';
print '</form>';

print '</div>';

print info_admin($langs->trans("NoNeedForDeliveryReceipts"));

$db->close();

llxFooter();
?>
