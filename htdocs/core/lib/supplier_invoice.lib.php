<?php
/* Copyright (C) 2005-2009  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Marc Barilley           <marc@ocebo.com>
 * Copyright (C) 2011-2013  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2022-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2023       Nick Fragoulis
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 *	\file       htdocs/core/lib/supplier_invoice.lib.php
 *	\brief      Set of basic functions for the supplier_invoice module
 *	\ingroup    supplier_invoice
 */

/**
 * Prepare array with list of tabs
 *
 * @param   FactureFournisseur	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
/*
function supplierinvoice_prepare_head(FactureFournisseur $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/supplier_invoice/card.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('SupplierInvoice');
	$head[$h][2] = 'card';
	$h++;

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/supplier_invoice/contact.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	//if ($fac->mode_reglement_code == 'PRE')
	if (isModEnabled('paymentbybanktransfer')) {
		$nbStandingOrders = 0;
		$sql = "SELECT COUNT(pfd.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
		$sql .= " WHERE pfd.fk_facture_fourn = ".((int) $object->id);
		$sql .= " AND type = 'ban'";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$nbStandingOrders = $obj->nb;
			}
		} else {
			dol_print_error($db);
		}
		$langs->load("banks");
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$object->id.'&type=bank-transfer';
		$head[$h][1] = $langs->trans('BankTransfer');
		if ($nbStandingOrders > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbStandingOrders.'</span>';
		}
		$head[$h][2] = 'standingorders';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'add', 'core');

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/supplier_invoice/note.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/supplier_invoice/document.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/supplier_invoice/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/supplier_invoice/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$nbEvent = 0;
		// Enable caching of thirdparty count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_facture_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE fk_element = ".((int) $object->id);
			$sql .= " AND elementtype = 'invoice_supplier'";
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbEvent = $obj->nb;
			} else {
				dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
			}
			dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}

		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
		if ($nbEvent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
		}
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'remove');

	return $head;
}
*/

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function supplier_invoice_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('facture_fourn');
	$extrafields->fetch_name_optionals_label('facture_fourn_det');
	$extrafields->fetch_name_optionals_label('facture_fourn_rec');
	$extrafields->fetch_name_optionals_label('facture_fourn_det_rec');

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/supplier_invoice/admin/supplier_invoice.php");
	$head[$h][1] = $langs->trans("SuppliersInvoice");
	$head[$h][2] = 'invoice';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/supplier_invoice/admin/supplier_payment.php");
	$head[$h][1] = $langs->trans("SuppliersPayment");
	$head[$h][2] = 'supplierpayment';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'supplierinvoice_admin');

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/supplier_invoice/admin/supplierinvoice_extrafields.php');
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoices");
	$nbExtrafields = $extrafields->attributes['facture_fourn']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierinvoice';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/supplier_invoice/admin/supplierinvoicedet_extrafields.php');
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoicesLines");
	$nbExtrafields = $extrafields->attributes['facture_fourn_det']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierinvoicedet';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/supplier_invoice/admin/supplierinvoice_rec_extrafields.php');
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoicesRec");
	$nbExtrafields = $extrafields->attributes['facture_fourn_rec']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributesrec';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/supplier_invoice/admin/supplierinvoicedet_rec_extrafields.php');
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoicesLinesRec");
	$nbExtrafields = $extrafields->attributes['facture_fourn_det_rec']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributeslinesrec';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'supplierinvoice_admin', 'remove');

	return $head;
}
