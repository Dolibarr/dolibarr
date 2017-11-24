<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017      	Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2017       ATM-CONSULTING		<contact@atm-consulting.fr>
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
 *	    \file       htdocs/core/lib/invoice.lib.php
 *		\brief      Functions used by invoice module
 * 		\ingroup	invoice
 */

/**
 * Initialize the array of tabs for customer invoice
 *
 * @param	Facture		$object		Invoice object
 * @return	array					Array of head tabs
 */
function facture_prepare_head($object)
{
	global $db, $langs, $conf;
	
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/card.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'compta';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
	    $nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
	    $head[$h][0] = DOL_URL_ROOT.'/compta/facture/contact.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

	//if ($fac->mode_reglement_code == 'PRE')
	if (! empty($conf->prelevement->enabled))
	{
	    $nbStandingOrders=0;
	    $sql = "SELECT COUNT(pfd.rowid) as nb";
	    $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
	    $sql .= " WHERE pfd.fk_facture = ".$object->id;
        $resql=$db->query($sql);
        if ($resql) 
        {
            $obj=$db->fetch_object($resql);
            if ($obj) $nbStandingOrders = $obj->nb;
        }
        else dol_print_error($db);
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('StandingOrders');
		if ($nbStandingOrders > 0) $head[$h][1].= ' <span class="badge">'.$nbStandingOrders.'</span>';
		$head[$h][2] = 'standingorders';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'invoice');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
    	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$object->id;
    	$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
    	$head[$h][2] = 'note';
    	$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
    $nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/document.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'invoice','remove');

	return $head;
}

/**
 * Return array head with list of tabs to view object informations.
 *
 * @return array head array with tabs
 */
function invoice_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/facture.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/payment.php';
	$head[$h][1] = $langs->trans("Payments");
	$head[$h][2] = 'payment';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to remove a tab
	complete_head_from_modules($conf,$langs,null,$head,$h,'invoice_admin');

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facture_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsCustomerInvoices");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facturedet_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'attributeslines';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facture_rec_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsCustomerInvoicesRec");
	$head[$h][2] = 'attributesrec';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facturedet_rec_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLinesRec");
	$head[$h][2] = 'attributeslinesrec';
	$h++;

	complete_head_from_modules($conf,$langs,null,$head,$h,'invoice_admin','remove');

	return $head;
}


function invoice_rec_prepare_head($object)
{
	global $db, $langs, $conf;
	
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/fiche-rec.php?id='.$object->id;
	$head[$h][1] = $langs->trans("CardBill");
	$head[$h][2] = 'card';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'invoice-rec');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'invoice-rec','remove');

	return $head;
}


