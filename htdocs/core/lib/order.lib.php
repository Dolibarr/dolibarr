<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/lib/order.lib.php
 *  \brief      Ensemble de fonctions de base pour le module commande
 *  \ingroup    commande
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Commande	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function commande_prepare_head(Commande $object)
{
	global $db, $langs, $conf, $user;
	if (!empty($conf->expedition->enabled)) $langs->load("sendings");
	$langs->load("orders");

	$h = 0;
	$head = array();

	if (!empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/card.php?id='.$object->id;
		$head[$h][1] = $langs->trans("CustomerOrder");
		$head[$h][2] = 'order';
		$h++;
	}

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/commande/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

	if (($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	|| ($conf->delivery_note->enabled && $user->rights->expedition->delivery->lire))
	{
		$nbShipments = $object->getNbOfShipments(); $nbReceiption = 0;
		$head[$h][0] = DOL_URL_ROOT.'/expedition/shipment.php?id='.$object->id;
		$text = '';
		if ($conf->expedition_bon->enabled) $text .= $langs->trans("Shipments");
		if ($conf->expedition_bon->enabled && $conf->delivery_note->enabled) $text .= ' - ';
		if ($conf->delivery_note->enabled)  $text .= $langs->trans("Receivings");
		if ($nbShipments > 0 || $nbReceiption > 0) $text .= '<span class="badge marginleftonlyshort">'.($nbShipments ? $nbShipments : 0);
		if ($conf->expedition_bon->enabled && $conf->delivery_note->enabled && ($nbShipments > 0 || $nbReceiption > 0)) $text .= ' - ';
		if ($conf->expedition_bon->enabled && $conf->delivery_note->enabled && ($nbShipments > 0 || $nbReceiption > 0)) $text .= ($nbReceiption ? $nbReceiption : 0);
		if ($nbShipments > 0 || $nbReceiption > 0) $text .= '</span>';
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/commande/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->commande->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/commande/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/commande/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	    		    head array with tabs
 */
function order_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/commande.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'order_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/order_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/orderdet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'attributeslines';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'order_admin', 'remove');

	return $head;
}
