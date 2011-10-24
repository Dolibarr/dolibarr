<?php
/* Copyright (C) 2005-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Marc Barilley / Ocebo <marc@ocebo.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/lib/fourn.lib.php
 *		\brief      Functions used by supplier invoice module
 *		\ingroup	supplier
 */

/**
 * Initialize the array of tabs for supplier invoice
 *
 * @param	Facture		$object		Invoice object
 * @return	array					Array of head tabs
 */
function facturefourn_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('CardBill');
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/contact.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('BillContacts');
	$head[$h][2] = 'contact';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_invoice');

    $head[$h][0] = DOL_URL_ROOT.'/fourn/facture/note.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$object->id;
	/*$filesdir = $conf->fournisseur->dir_output.'/facture/'.get_exdir($fac->id,2).$fac->id;
	include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (count($listoffiles)?$langs->trans('DocumentsNb',count($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	return $head;
}


function ordersupplier_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderCard");
	$head[$h][2] = 'card';
	$h++;

	if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
	{
		$langs->load("stocks");
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderDispatch");
		$head[$h][2] = 'dispatch';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans('OrderContact');
	$head[$h][2] = 'contact';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_order');

    $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Notes");
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/document.php?id='.$object->id;
	/*$filesdir = $conf->fournisseur->dir_output . "/commande/" . dol_sanitizeFileName($commande->ref);
	include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (count($listoffiles)?$langs->trans('DocumentsNb',count($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderFollow");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}


?>