<?php
/* Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Marc Barilley		<marc@ocebo.com>
 * Copyright (C) 2011-2013  Philippe Grand      <philippe.grand@atoo-net.com>
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
 *	    \file       htdocs/core/lib/fourn.lib.php
 *		\brief      Functions used by supplier invoice module
 *		\ingroup	supplier
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
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

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/contact.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_invoice');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/note.php?facid='.$object->id;
    	$head[$h][1] = $langs->trans('Notes');
		if($nbNote > 0) $head[$h][1].= ' ('.$nbNote.')';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir,'files'));
	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if($nbFiles > 0) $head[$h][1].= ' ('.$nbFiles.')';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_invoice','remove');

    return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function ordersupplier_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderCard");
	$head[$h][2] = 'card';
	$h++;

	if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
	{
		$langs->load("stocks");
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderDispatch");
		$head[$h][2] = 'dispatch';
		$h++;
	}

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_order');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$object->id;
    	$head[$h][1] = $langs->trans("Notes");
		if($nbNote > 0) $head[$h][1].= ' ('.$nbNote.')';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->fournisseur->dir_output . "/commande/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files'));
	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if($nbFiles > 0) $head[$h][1].= ' ('.$nbFiles.')';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderFollow");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @param	Object	$object		order
 *  @return	array   	        head array with tabs
 */
function supplierorder_admin_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/supplier_order.php";
	$head[$h][1] = $langs->trans("SupplierOrder");
	$head[$h][2] = 'order';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/supplier_invoice.php";
	$head[$h][1] = $langs->trans("SuppliersInvoice");
	$head[$h][2] = 'invoice';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'supplierorder_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierorder_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierOrders");
	$head[$h][2] = 'supplierorder';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierinvoice_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoices");
	$head[$h][2] = 'supplierinvoice';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'supplierorder_admin','remove');

	return $head;
}


?>