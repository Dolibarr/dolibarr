<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/lib/order.lib.php
 *  \brief      Ensemble de fonctions de base pour le module commande
 *  \ingroup    commande
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function commande_prepare_head($object)
{
	global $langs, $conf, $user;
	if (! empty($conf->expedition->enabled)) $langs->load("sendings");
	$langs->load("orders");

	$h = 0;
	$head = array();

	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/fiche.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderCard");
		$head[$h][2] = 'order';
		$h++;
	}

	if (($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	|| ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->lire))
	{
		$head[$h][0] = DOL_URL_ROOT.'/expedition/shipment.php?id='.$object->id;
		if ($conf->expedition_bon->enabled) $text=$langs->trans("Sendings");
		if ($conf->expedition_bon->enabled && $conf->livraison_bon->enabled) $text.='/';
		if ($conf->livraison_bon->enabled)  $text.=$langs->trans("Receivings");
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	if (! empty($conf->global->MAIN_USE_PREVIEW_TABS))
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/apercu.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Preview");
		$head[$h][2] = 'preview';
		$h++;
	}

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'order');

    $head[$h][0] = DOL_URL_ROOT.'/commande/document.php?id='.$object->id;
	/*$filesdir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($commande->ref);
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (count($listoffiles)?$langs->trans('DocumentsNb',count($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		$head[$h][2] = 'note';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/commande/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'order','remove');

    return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @param	Object	$object		order
 *  @return	array   	        head array with tabs
 */
function order_admin_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/commande.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'order_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/order_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT.'/admin/orderdet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'attributeslines';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'order_admin','remove');

	return $head;
}


?>
