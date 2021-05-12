<?php
/* Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * @return  array				Array of tabs to show
 */
function facturefourn_prepare_head($object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
<<<<<<< HEAD
	    $nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
=======
	    $nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    $head[$h][0] = DOL_URL_ROOT.'/fourn/facture/contact.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
<<<<<<< HEAD
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_invoice');
=======
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/note.php?facid='.$object->id;
    	$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
<<<<<<< HEAD
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
=======
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

<<<<<<< HEAD
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_invoice','remove');
=======
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'remove');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function ordersupplier_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderCard");
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
<<<<<<< HEAD
	    $nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
=======
	    $nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	    $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

<<<<<<< HEAD
	if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
=======
	if (! empty($conf->stock->enabled) && (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$langs->load("stocks");
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderDispatch");
		$head[$h][2] = 'dispatch';
		$h++;
	}

	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
<<<<<<< HEAD
    complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_order');
=======
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_order');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$object->id;
    	$head[$h][1] = $langs->trans("Notes");
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->fournisseur->dir_output . "/commande/" . dol_sanitizeFileName($object->ref);
<<<<<<< HEAD
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
=======
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/info.php?id='.$object->id;
	$head[$h][1].= $langs->trans("Events");
	if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
	{
	    $head[$h][1].= '/';
	    $head[$h][1].= $langs->trans("Agenda");
	}
	$head[$h][2] = 'info';
	$h++;
<<<<<<< HEAD
	complete_head_from_modules($conf,$langs,$object,$head,$h,'supplier_order', 'remove');
=======
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_order', 'remove');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function supplierorder_admin_prepare_head()
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

	$head[$h][0] = DOL_URL_ROOT."/admin/supplier_payment.php";
	$head[$h][1] = $langs->trans("SuppliersPayment");
	$head[$h][2] = 'supplierpayment';
	$h++;

<<<<<<< HEAD
	complete_head_from_modules($conf,$langs,null,$head,$h,'supplierorder_admin');
=======
	complete_head_from_modules($conf, $langs, null, $head, $h, 'supplierorder_admin');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierorder_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierOrders");
	$head[$h][2] = 'supplierorder';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierorderdet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierOrdersLines");
	$head[$h][2] = 'supplierorderdet';
	$h++;



	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierinvoice_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoices");
	$head[$h][2] = 'supplierinvoice';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierinvoicedet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoicesLines");
	$head[$h][2] = 'supplierinvoicedet';
	$h++;

<<<<<<< HEAD
	complete_head_from_modules($conf,$langs,null,$head,$h,'supplierorder_admin','remove');

	return $head;
}


=======
	complete_head_from_modules($conf, $langs, null, $head, $h, 'supplierorder_admin', 'remove');

	return $head;
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
