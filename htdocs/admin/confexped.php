<?php
/* Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011-2016 Juanjo Menent		<jmenent@2byte.es>ù
 * Copyright (C) 2015      Claudio Aschieri     <c.aschieri@19.coop>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/confexped.php
 *		\ingroup    produit
 *		\brief      Page to setup sending module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expedition.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'sendings', 'deliveries'));

if (!$user->admin)
  accessforbidden();

$action=GETPOST('action', 'alpha');


/*
 * Actions
 */

// Shipment note
if (! empty($conf->expedition->enabled) && empty($conf->global->MAIN_SUBMODULE_EXPEDITION))
{
    // This option should always be set to on when module is on.
    dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1", 'chaine', 0, '', $conf->entity);
}
/*
if ($action == 'activate_sending')
{
    dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1",'chaine',0,'',$conf->entity);
    header("Location: confexped.php");
    exit;
}
if ($action == 'disable_sending')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_EXPEDITION",$conf->entity);
    header("Location: confexped.php");
    exit;
}
*/

// Delivery note
if ($action == 'activate_delivery')
{
    dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1", 'chaine', 0, '', $conf->entity);    // We must also enable this
    dolibarr_set_const($db, "MAIN_SUBMODULE_LIVRAISON", "1", 'chaine', 0, '', $conf->entity);
	header("Location: confexped.php");
	exit;
}
elseif ($action == 'disable_delivery')
{
	dolibarr_del_const($db, "MAIN_SUBMODULE_LIVRAISON", $conf->entity);
    header("Location: confexped.php");
    exit;
}


/*
 * View
 */

$dir = DOL_DOCUMENT_ROOT."/core/modules/expedition/";
$form=new Form($db);

llxHeader("", $langs->trans("SendingsSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SendingsSetup"), $linkback, 'title_setup');
print '<br>';
$head = expedition_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Sendings"), -1, 'sending');

// Miscellaneous parameters

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Feature").'</td>';
print '<td width="20">&nbsp;</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

// expedition activation/desactivation
print "<tr>";
print '<td>'.$langs->trans("SendingsAbility").'</td>';
print '<td>';
print '</td>';
print '<td class="center">';
print $langs->trans("Required");
/*if (empty($conf->global->MAIN_SUBMODULE_EXPEDITION))
{
	print '<a href="confexped.php?action=activate_sending">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
	print '<a href="confexped.php?action=disable_sending">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}*/
print "</td>";
print '</tr>';

// Bon de livraison activation/desactivation
print '<tr>';
print '<td>';
print $langs->trans("DeliveriesOrderAbility");
print '<br>'.info_admin($langs->trans("NoNeedForDeliveryReceipts"), 0, 1);
print '</td>';
print '<td>';
print '</td>';
print '<td class="center">';

if (empty($conf->global->MAIN_SUBMODULE_LIVRAISON))
{
	print '<a href="confexped.php?action=activate_delivery">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
else
{
	print '<a href="confexped.php?action=disable_delivery">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
}

print "</td>";
print '</tr>';
print '</table>';

print '</div>';

// End of page
llxFooter();
$db->close();
