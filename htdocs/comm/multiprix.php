<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani  <acianfa@free.fr>
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
 *	\file       htdocs/comm/multiprix.php
 *	\ingroup    societe
 *	\brief      Tab to set the price level of a thirdparty
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Load translation files required by the page
$langs->loadLangs(array('orders', 'companies'));

$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOSTINT('id');
$_socid = GETPOSTINT("id");
// Security check
if ($user->socid > 0) {
	$_socid = $user->socid;
}

// Security check
$socid = GETPOSTINT("socid");
if ($user->socid > 0) {
	$action = '';
	$id = $user->socid;
}
$result = restrictedArea($user, 'societe', $id, '&societe', '', 'fk_soc', 'rowid', 0);


/*
 * Actions
 */

if ($action == 'setpricelevel' && $user->hasRight('societe', 'creer')) {
	$soc = new Societe($db);
	$soc->fetch($id);
	$soc->setPriceLevel(GETPOST("price_level"), $user);

	header("Location: multiprix.php?id=".$id);
	exit;
}


/*
 * View
 */

llxHeader();

$userstatic = new User($db);

if ($_socid > 0) {
	// We load data of thirdparty
	$objsoc = new Societe($db);
	$objsoc->id = $_socid;
	$objsoc->fetch($_socid);


	$head = societe_prepare_head($objsoc);

	$tabchoice = '';
	if ($objsoc->client == 1) {
		$tabchoice = 'customer';
	}
	if ($objsoc->client == 2) {
		$tabchoice = 'prospect';
	}

	print '<form method="POST" action="multiprix.php?id='.$objsoc->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setpricelevel">';

	print dol_get_fiche_head($head, $tabchoice, $langs->trans("ThirdParty"), 0, 'company');

	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefieldcreate">';
	print $langs->trans("PriceLevel").'</td><td>'.$objsoc->price_level."</td></tr>";

	print '<tr><td>';
	print $langs->trans("NewValue").'</td><td>';
	print '<select name="price_level" class="flat">';
	for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
		print '<option value="'.$i.'"';
		if ($i == $objsoc->price_level) {
			print 'selected';
		}
		print '>'.$i;
		$keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.$i;
		if (getDolGlobalString($keyforlabel)) {
			print ' - '.$langs->trans(getDolGlobalString($keyforlabel));
		}
		print '</option>';
	}
	print '</select>';
	print '</td></tr>';

	print "</table>";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Save", '');

	print "</form>";


	print '<br><br>';


	/*
	 * List historic of multiprices
	 */
	$sql  = "SELECT rc.rowid,rc.price_level, rc.datec as dc, u.rowid as uid, u.login";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe_prices as rc, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE rc.fk_soc = ".((int) $objsoc->id);
	$sql .= " AND u.rowid = rc.fk_user_author";
	$sql .= " ORDER BY rc.datec DESC";

	$resql = $db->query($sql);
	if ($resql) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("PriceLevel").'</td>';
		print '<td class="right">'.$langs->trans("User").'</td>';
		print '</tr>';
		$i = 0;
		$num = $db->num_rows($resql);

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td>'.dol_print_date($db->jdate($obj->dc), "dayhour").'</td>';
			print '<td>'.$obj->price_level.' </td>';
			$userstatic->id = $obj->uid;
			$userstatic->lastname = $obj->login;
			print '<td class="right">'.$userstatic->getNomUrl(1).'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		print "</table>";
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
