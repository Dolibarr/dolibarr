<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
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
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('CustomerInvoice');
	$head[$h][2] = 'compta';
	$h++;

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/contact.php?id='.urlencode($object->id);
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	if (isModEnabled('prelevement')) {
		$nbStandingOrders = 0;
		$sql = "SELECT COUNT(pfd.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
		$sql .= " WHERE pfd.fk_facture = ".((int) $object->id);
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

		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?id='.urlencode($object->id);
		$head[$h][1] = $langs->trans('StandingOrders');
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
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice', 'add', 'core');

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->facture->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda')&& ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
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
			$sql .= " AND elementtype = 'invoice'";
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

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice', 'remove');

	return $head;
}

/**
 * Return array head with list of tabs to view object informations.
 *
 * @return array head array with tabs
 */
function invoice_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('facture');
	$extrafields->fetch_name_optionals_label('facturedet');
	$extrafields->fetch_name_optionals_label('facture_rec');
	$extrafields->fetch_name_optionals_label('facturedet_rec');

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
	complete_head_from_modules($conf, $langs, null, $head, $h, 'invoice_admin');

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facture_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsCustomerInvoices");
	$nbExtrafields = $extrafields->attributes['facture']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facturedet_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = $extrafields->attributes['facturedet']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributeslines';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facture_rec_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsCustomerInvoicesRec");
	$nbExtrafields = $extrafields->attributes['facture_rec']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributesrec';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/admin/facturedet_rec_cust_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsLinesRec");
	$nbExtrafields = $extrafields->attributes['facturedet_rec']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributeslinesrec';
	$h++;

	if (getDolGlobalInt('INVOICE_USE_SITUATION') > 0) {	// Warning, implementation with value 1 is seriously bugged and a new one not compatible is expected to become stable
		$head[$h][0] = DOL_URL_ROOT.'/admin/facture_situation.php';
		$head[$h][1] = $langs->trans("InvoiceSituation");
		$head[$h][2] = 'situation';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'invoice_admin', 'remove');

	return $head;
}


/**
 * Return array head with list of tabs to view object informations.
 *
 * @param   Facture     $object     Invoice object
 * @return array                    head array with tabs
 */
function invoice_rec_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/compta/facture/card-rec.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("RepeatableInvoice");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/agenda-rec.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda')&& ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$nbEvent = 0;
		// Enable caching of thirdparty count actioncomm
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_facturerec_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbEvent = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(id) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE fk_element = ".((int) $object->id);
			$sql .= " AND elementtype = 'invoicerec'";
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

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice-rec');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice-rec', 'remove');

	return $head;
}

/**
 * Return array head with list of tabs to view object informations.
 *
 * @param   Facture     $object     Invoice object
 * @return array                    head array with tabs
 */
function supplier_invoice_rec_prepare_head($object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/fourn/facture/card-rec.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("RepeatableSupplierInvoice");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice_supplier_rec');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'invoice_supplier_rec', 'remove');

	return $head;
}

/**
 * Return an HTML table that contains a pie chart of the number of customers or supplier invoices
 *
 * @param 	string 	$mode 		Can be 'customers' or 'suppliers'
 * @return 	string 				A HTML table that contains a pie chart of customers or supplier invoices
 */
function getNumberInvoicesPieChart($mode)
{
	global $conf, $db, $langs, $user;

	if (($mode == 'customers' && isModEnabled('facture') && $user->hasRight('facture', 'lire'))
		|| ($mode == 'suppliers' && (isModEnabled('fournisseur') || isModEnabled('supplier_invoice')) && $user->hasRight('fournisseur', 'facture', 'lire'))
		) {
		global $badgeStatus1, $badgeStatus3, $badgeStatus4, $badgeStatus8, $badgeStatus11;
		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		$now = date_create(date('Y-m-d', dol_now()));
		$datenowsub30 = date_create(date('Y-m-d', dol_now()));
		$datenowsub15 = date_create(date('Y-m-d', dol_now()));
		$datenowadd30 = date_create(date('Y-m-d', dol_now()));
		$datenowadd15 = date_create(date('Y-m-d', dol_now()));
		$interval30days = date_interval_create_from_date_string('30 days');
		$interval15days = date_interval_create_from_date_string('15 days');
		date_sub($datenowsub30, $interval30days);
		date_sub($datenowsub15, $interval15days);
		date_add($datenowadd30, $interval30days);
		date_add($datenowadd15, $interval15days);

		$sql = "SELECT";
		$sql .= " sum(".$db->ifsql("f.date_lim_reglement < '".date_format($datenowsub30, 'Y-m-d')."'", 1, 0).") as nblate30";
		$sql .= ", sum(".$db->ifsql("f.date_lim_reglement < '".date_format($datenowsub15, 'Y-m-d')."'", 1, 0).") as nblate15";
		$sql .= ", sum(".$db->ifsql("f.date_lim_reglement < '".date_format($now, 'Y-m-d')."'", 1, 0).") as nblatenow";
		$sql .= ", sum(".$db->ifsql("f.date_lim_reglement >= '".date_format($now, 'Y-m-d')."' OR f.date_lim_reglement IS NULL", 1, 0).") as nbnotlatenow";
		$sql .= ", sum(".$db->ifsql("f.date_lim_reglement > '".date_format($datenowadd15, 'Y-m-d')."'", 1, 0).") as nbnotlate15";
		$sql .= ", sum(".$db->ifsql("f.date_lim_reglement > '".date_format($datenowadd30, 'Y-m-d')."'", 1, 0).") as nbnotlate30";
		if ($mode == 'customers') {
			$element = 'invoice';
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		} elseif ($mode == 'fourn' || $mode == 'suppliers') {
			$element = 'supplier_invoice';
			$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		} else {
			return '';
		}
		$sql .= " WHERE f.entity IN (".getEntity($element).")";
		$sql .= " AND f.type <> 2";
		$sql .= " AND f.fk_statut = 1";
		if (isset($user->socid) && $user->socid > 0) {
			$sql .= " AND f.fk_soc = ".((int) $user->socid);
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$total = 0;
			$dataseries = array();

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				/*
				$dataseries = array(array($langs->trans('InvoiceLate30Days'), $obj->nblate30)
									,array($langs->trans('InvoiceLate15Days'), $obj->nblate15 - $obj->nblate30)
									,array($langs->trans('InvoiceLateMinus15Days'), $obj->nblatenow - $obj->nblate15)
									,array($langs->trans('InvoiceNotLate'), $obj->nbnotlatenow - $obj->nbnotlate15)
									,array($langs->trans('InvoiceNotLate15Days'), $obj->nbnotlate15 - $obj->nbnotlate30)
									,array($langs->trans('InvoiceNotLate30Days'), $obj->nbnotlate30));
				*/
				$dataseries[$i]=array($langs->transnoentitiesnoconv('NbOfOpenInvoices'), $obj->nblate30, $obj->nblate15 - $obj->nblate30, $obj->nblatenow - $obj->nblate15, $obj->nbnotlatenow - $obj->nbnotlate15, $obj->nbnotlate15 - $obj->nbnotlate30, $obj->nbnotlate30);
				$i++;
			}
			if (!empty($dataseries[0])) {
				foreach ($dataseries[0] as $key => $value) {
					if (is_numeric($value)) {
						$total += $value;
					}
				}
			}
			$legend = array(
				$langs->trans('InvoiceLate30Days'),
				$langs->trans('InvoiceLate15Days'),
				$langs->trans('InvoiceLateMinus15Days'),
				$mode == 'customers' ? $langs->trans('InvoiceNotLate') : $langs->trans("InvoiceToPay"),
				$mode == 'customers' ? $langs->trans('InvoiceNotLate15Days') : $langs->trans("InvoiceToPay15Days"),
				$mode == 'customers' ? $langs->trans('InvoiceNotLate30Days') : $langs->trans("InvoiceToPay30Days"),
			);

			$colorseries = array($badgeStatus8, $badgeStatus1, $badgeStatus3, $badgeStatus4, $badgeStatus11, '-'.$badgeStatus11);

			$result = '<div class="div-table-responsive-no-min">';
			$result .= '<table class="noborder nohover centpercent">';
			$result .= '<tr class="liste_titre">';
			$result .= '<td>'.$langs->trans("NbOfOpenInvoices").' - ';
			if ($mode == 'customers') {
				$result .= $langs->trans("CustomerInvoice");
			} elseif ($mode == 'fourn' || $mode == 'suppliers') {
				$result .= $langs->trans("SupplierInvoice");
			} else {
				return '';
			}
			$result .= '</td>';
			$result .= '</tr>';

			if ($conf->use_javascript_ajax) {
				//var_dump($dataseries);
				$dolgraph = new DolGraph();
				$dolgraph->SetData($dataseries);

				$dolgraph->setLegend($legend);

				$dolgraph->SetDataColor(array_values($colorseries));
				$dolgraph->setShowLegend(2);
				$dolgraph->setShowPercent(1);
				$dolgraph->SetType(array('bars', 'bars', 'bars', 'bars', 'bars', 'bars'));
				//$dolgraph->SetType(array('pie'));
				$dolgraph->setHeight('160');	/* 160 min is required to show the 6 lines of legend */
				$dolgraph->setWidth('450');
				$dolgraph->setHideXValues(true);
				if ($mode == 'customers') {
					$dolgraph->draw('idgraphcustomerinvoices');
				} elseif ($mode == 'fourn' || $mode == 'suppliers') {
					$dolgraph->draw('idgraphfourninvoices');
				} else {
					return '';
				}
				$result .= '<tr maxwidth="255">';
				$result .= '<td class="center">'.$dolgraph->show($total ? 0 : $langs->trans("NoOpenInvoice")).'</td>';
				$result .= '</tr>';
			} else {
				// Print text lines
			}

			$result .= '</table>';
			$result .= '</div>';

			return $result;
		} else {
			dol_print_error($db);
		}
	}
	return '';
}

/**
 * Return a HTML table that contains a list with customer invoice drafts
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the customer with this id
 * @return	string				A HTML table that contains a list with customer invoice drafts
 */
function getCustomerInvoiceDraftTable($maxCount = 500, $socid = 0)
{
	global $conf, $db, $langs, $user, $hookmanager;

	$result = '';

	if (isModEnabled('facture') && $user->hasRight('facture', 'lire')) {
		$maxofloop = (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD);

		$tmpinvoice = new Facture($db);

		$sql = "SELECT f.rowid, f.ref, f.datef as date, f.total_ht, f.total_tva, f.total_ttc, f.ref_client";
		$sql .= ", f.type, f.fk_statut as status, f.paye";
		$sql .= ", s.nom as name";
		$sql .= ", s.rowid as socid, s.email";
		$sql .= ", s.code_client, s.code_compta, s.code_fournisseur, s.code_compta_fournisseur";
		$sql .= ", cc.rowid as country_id, cc.code as country_code";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", sc.fk_soc, sc.fk_user ";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE s.rowid = f.fk_soc AND f.fk_statut = ".Facture::STATUS_DRAFT;
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}

		if ($socid) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerDraft', $parameters);
		$sql .= $hookmanager->resPrint;

		$sql .= " GROUP BY f.rowid, f.ref, f.datef, f.total_ht, f.total_tva, f.total_ttc, f.ref_client, f.type, f.fk_statut, f.paye,";
		$sql .= " s.nom, s.rowid, s.email, s.code_client, s.code_compta, s.code_fournisseur, s.code_compta_fournisseur,";
		$sql .= " cc.rowid, cc.code";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", sc.fk_soc, sc.fk_user";
		}

		// Add Group from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListGroupByCustomerDraft', $parameters);
		$sql .= $hookmanager->resPrint;

		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$nbofloop = min($num, $maxofloop);

			$result .= '<div class="div-table-responsive-no-min">';
			$result .= '<table class="noborder centpercent">';

			$result .= '<tr class="liste_titre">';
			$result .= '<th colspan="3">';
			$result .= $langs->trans("CustomersDraftInvoices").' ';
			$result .= '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status='.Facture::STATUS_DRAFT.'">';
			$result .= '<span class="badge marginleftonlyshort">'.$num.'</span>';
			$result .= '</a>';
			$result .= '</th>';
			$result .= '</tr>';

			if ($num) {
				$companystatic = new Societe($db);

				$i = 0;
				$othernb = 0;
				$tot_ttc = 0;
				while ($i < $nbofloop) {
					$obj = $db->fetch_object($resql);

					if ($i >= $maxCount) {
						$othernb += 1;
						$i++;
						$tot_ttc += $obj->total_ttc;
						continue;
					}

					$tmpinvoice->id = $obj->rowid;
					$tmpinvoice->ref = $obj->ref;
					$tmpinvoice->date = $db->jdate($obj->date);
					$tmpinvoice->type = $obj->type;
					$tmpinvoice->total_ht = $obj->total_ht;
					$tmpinvoice->total_tva = $obj->total_tva;
					$tmpinvoice->total_ttc = $obj->total_ttc;
					$tmpinvoice->ref_client = $obj->ref_client;
					$tmpinvoice->statut = $obj->status;
					$tmpinvoice->paye = $obj->paye;

					$companystatic->id = $obj->socid;
					$companystatic->name = $obj->name;
					$companystatic->email = $obj->email;
					$companystatic->country_id = $obj->country_id;
					$companystatic->country_code = $obj->country_code;
					$companystatic->client = 1;
					$companystatic->code_client = $obj->code_client;
					$companystatic->code_fournisseur = $obj->code_fournisseur;
					$companystatic->code_compta = $obj->code_compta;
					$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

					$result .= '<tr class="oddeven">';
					$result .= '<td class="nowrap tdoverflowmax100">';
					$result .= $tmpinvoice->getNomUrl(1, '');
					$result .= '</td>';
					$result .= '<td class="nowrap tdoverflowmax100">';
					$result .= $companystatic->getNomUrl(1, 'customer');
					$result .= '</td>';
					$result .= '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc).'</span></td>';
					$result .= '</tr>';
					$tot_ttc += $obj->total_ttc;
					$i++;
				}

				if ($othernb) {
					$result .= '<tr class="oddeven">';
					$result .= '<td class="nowrap" colspan="3">';
					$result .= '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
					$result .= '</td>';
					$result .= "</tr>\n";
				}

				$result .= '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
				$result .= '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
				$result .= '</tr>';
			} else {
				$result .= '<tr class="oddeven"><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoInvoice").'</span></td></tr>';
			}
			$result .= "</table></div>";
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}

	return $result;
}

/**
 * Return a HTML table that contains a list with customer invoice drafts
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the customer with this id
 * @return	string				A HTML table that contains a list with customer invoice drafts
 */
function getDraftSupplierTable($maxCount = 500, $socid = 0)
{
	global $conf, $db, $langs, $user, $hookmanager;

	$result = '';

	if ((isModEnabled('fournisseur') || isModEnabled('supplier_invoice')) && $user->hasRight('facture', 'lire')) {
		$maxofloop = (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD);

		$facturesupplierstatic = new FactureFournisseur($db);

		$sql = "SELECT f.ref, f.rowid, f.total_ht, f.total_tva, f.total_ttc, f.type, f.ref_supplier, f.fk_statut as status, f.paye";
		$sql .= ", s.nom as name";
		$sql .= ", s.rowid as socid, s.email";
		$sql .= ", s.code_client, s.code_compta";
		$sql .= ", s.code_fournisseur, s.code_compta_fournisseur";
		$sql .= ", cc.rowid as country_id, cc.code as country_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE s.rowid = f.fk_soc AND f.fk_statut = ".FactureFournisseur::STATUS_DRAFT;
		$sql .= " AND f.entity IN (".getEntity('invoice').')';
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if ($socid) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhereSupplierDraft', $parameters);
		$sql .= $hookmanager->resPrint;
		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$nbofloop = min($num, $maxofloop);

			$result .= '<div class="div-table-responsive-no-min">';
			$result .= '<table class="noborder centpercent">';

			$result .= '<tr class="liste_titre">';
			$result .= '<th colspan="3">';
			$result .= $langs->trans("SuppliersDraftInvoices").' ';
			$result .= '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?search_status='.FactureFournisseur::STATUS_DRAFT.'">';
			$result .= '<span class="badge marginleftonlyshort">'.$num.'</span>';
			$result .= '</a>';
			$result .= '</th>';
			$result .= '</tr>';

			if ($num) {
				$companystatic = new Societe($db);

				$i = 0;
				$othernb = 0;
				$tot_ttc = 0;
				while ($i < $nbofloop) {
					$obj = $db->fetch_object($resql);

					if ($i >= $maxCount) {
						$othernb += 1;
						$i++;
						$tot_ttc += $obj->total_ttc;
						continue;
					}

					$facturesupplierstatic->ref = $obj->ref;
					$facturesupplierstatic->id = $obj->rowid;
					$facturesupplierstatic->total_ht = $obj->total_ht;
					$facturesupplierstatic->total_tva = $obj->total_tva;
					$facturesupplierstatic->total_ttc = $obj->total_ttc;
					$facturesupplierstatic->ref_supplier = $obj->ref_supplier;
					$facturesupplierstatic->type = $obj->type;
					$facturesupplierstatic->statut = $obj->status;
					$facturesupplierstatic->paye = $obj->paye;

					$companystatic->id = $obj->socid;
					$companystatic->name = $obj->name;
					$companystatic->email = $obj->email;
					$companystatic->country_id = $obj->country_id;
					$companystatic->country_code = $obj->country_code;
					$companystatic->fournisseur = 1;
					$companystatic->code_client = $obj->code_client;
					$companystatic->code_fournisseur = $obj->code_fournisseur;
					$companystatic->code_compta = $obj->code_compta;
					$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

					$result .= '<tr class="oddeven">';
					$result .= '<td class="nowrap tdoverflowmax100">';
					$result .= $facturesupplierstatic->getNomUrl(1, '');
					$result .= '</td>';
					$result .= '<td class="nowrap tdoverflowmax100">';
					$result .= $companystatic->getNomUrl(1, 'supplier');
					$result .= '</td>';
					$result .= '<td class="right"><span class="amount">'.price($obj->total_ttc).'</span></td>';
					$result .= '</tr>';
					$tot_ttc += $obj->total_ttc;
					$i++;
				}

				if ($othernb) {
					$result .= '<tr class="oddeven">';
					$result .= '<td class="nowrap" colspan="3">';
					$result .= '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
					$result .= '</td>';
					$result .= "</tr>\n";
				}

				$result .= '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
				$result .= '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
				$result .= '</tr>';
			} else {
				$result .= '<tr class="oddeven"><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoInvoice").'</span></td></tr>';
			}
			$result .= "</table></div>";
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}

	return $result;
}


/**
 * Return a HTML table that contains a list with latest edited customer invoices
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the customer with this id
 * @return	string				A HTML table that contains a list with latest edited customer invoices
 */
function getCustomerInvoiceLatestEditTable($maxCount = 5, $socid = 0)
{
	global $conf, $db, $langs, $user;

	$sql = "SELECT f.rowid, f.entity, f.ref, f.fk_statut as status, f.paye, f.type, f.total_ht, f.total_tva, f.total_ttc, f.datec,";
	$sql .= " s.nom as socname, s.rowid as socid, s.canvas, s.client";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.entity IN (".getEntity('facture').")";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " ORDER BY f.tms DESC";
	$sql .= $db->plimit($maxCount, 0);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}

	$num = $db->num_rows($resql);

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder centpercent">';

	$result .= '<tr class="liste_titre">';
	$result .= '<th colspan="3">'.$langs->trans("LastCustomersBills", $maxCount).'</th>';
	$result .= '<th class="right">'.$langs->trans("AmountTTC").'</th>';
	$result .= '<th class="right"></th>';
	$result .= '</tr>';

	if ($num < 1) {
		$result .= '</table>';
		$result .= '</div>';
		return $result;
	}

	$formfile = new FormFile($db);
	$objectstatic = new Facture($db);
	$companystatic = new Societe($db);
	$i = 0;

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->paye = $obj->paye;
		$objectstatic->statut = $obj->status;
		$objectstatic->total_ht = $obj->total_ht;
		$objectstatic->total_tva = $obj->total_tva;
		$objectstatic->total_ttc = $obj->total_ttc;
		$objectstatic->type = $obj->type;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->socname;
		$companystatic->client	= $obj->client;
		$companystatic->canvas	= $obj->canvas;

		$filename = dol_sanitizeFileName($obj->ref);
		$filedir = $conf->propal->multidir_output[$obj->entity].'/'.$filename;

		$result .= '<tr class="nowrap">';

		$result .= '<td class="oddeven">';
		$result .= '<table class="nobordernopadding">';
		$result .= '<tr class="nocellnopadd">';

		$result .= '<td width="96" class="nobordernopadding nowrap">'.$objectstatic->getNomUrl(1).'</td>';
		$result .= '<td width="16" class="nobordernopadding nowrap">&nbsp;</td>';
		$result .= '<td width="16" class="nobordernopadding right">'.$formfile->getDocumentsLink($objectstatic->element, $filename, $filedir).'</td>';

		$result .= '</tr>';
		$result .= '</table>';
		$result .= '</td>';

		$result .= '<td class="tdoverflowmax150">'.$companystatic->getNomUrl(1, 'customer').'</td>';
		$result .= '<td>'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';
		$result .= '<td class="right amount">'.price($obj->total_ttc).'</td>';

		// Load amount of existing payment of invoice (needed for complete status)
		$payment = $objectstatic->getSommePaiement();
		$result .= '<td class="right">'.$objectstatic->getLibStatut(5, $payment).'</td>';

		$result .= '</tr>';

		$i++;
	}

	$result .= '</table>';
	$result .= '</div>';
	return $result;
}

/**
 * Return a HTML table that contains a list with latest edited supplier invoices
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that contains a list with latest edited supplier invoices
 */
function getPurchaseInvoiceLatestEditTable($maxCount = 5, $socid = 0)
{
	global $conf, $db, $langs, $user;

	$sql = "SELECT f.rowid, f.entity, f.ref, f.fk_statut as status, f.paye, f.total_ht, f.total_tva, f.total_ttc, f.type, f.ref_supplier, f.datec,";
	$sql .= " s.nom as socname, s.rowid as socid, s.canvas, s.client";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.entity IN (".getEntity('facture_fourn').")";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " ORDER BY f.tms DESC";
	$sql .= $db->plimit($maxCount, 0);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		return '';
	}

	$num = $db->num_rows($resql);

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder centpercent">';
	$result .= '<tr class="liste_titre">';
	$result .= '<th colspan="3">'.$langs->trans("BoxTitleLastSupplierBills", $maxCount).'</th>';
	$result .= '<th class="right">'.$langs->trans("AmountTTC").'</th>';
	$result .= '<th class="right"></th>';
	$result .= '</tr>';

	if ($num < 1) {
		$result .= '</table>';
		$result .= '</div>';
		return $result;
	}

	$objectstatic = new FactureFournisseur($db);
	$companystatic = new Societe($db);
	$formfile = new FormFile($db);
	$i = 0;

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->paye = $obj->paye;
		$objectstatic->statut = $obj->status;
		$objectstatic->total_ht = $obj->total_ht;
		$objectstatic->total_tva = $obj->total_tva;
		$objectstatic->total_ttc = $obj->total_ttc;
		$objectstatic->type = $obj->type;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->socname;
		$companystatic->client = $obj->client;
		$companystatic->canvas = $obj->canvas;

		$filename = dol_sanitizeFileName($obj->ref);
		$filedir = $conf->propal->multidir_output[$obj->entity].'/'.$filename;

		$result .= '<tr class="nowrap">';

		$result .= '<td class="oddeven">';
		$result .= '<table class="nobordernopadding">';
		$result .= '<tr class="nocellnopadd">';

		$result .= '<td width="96" class="nobordernopadding nowrap">'.$objectstatic->getNomUrl(1).'</td>';
		$result .= '<td width="16" class="nobordernopadding nowrap">&nbsp;</td>';
		$result .= '<td width="16" class="nobordernopadding right">'.$formfile->getDocumentsLink($objectstatic->element, $filename, $filedir).'</td>';

		$result .= '</tr>';
		$result .= '</table>';
		$result .= '</td>';

		$result .= '<td class="tdoverflowmax150">'.$companystatic->getNomUrl(1, 'supplier').'</td>';

		$result .= '<td>'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';

		$result .= '<td class="amount right">'.price($obj->total_ttc).'</td>';

		$result .= '<td class="right">'.$objectstatic->getLibStatut(5).'</td>';

		$result .= '</tr>';

		$i++;
	}

	$result .= '</table>';
	$result .= '</div>';
	return $result;
}

/**
 * Return a HTML table that contains of unpaid customers invoices
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that conatins a list with open (unpaid) supplier invoices
 */
function getCustomerInvoiceUnpaidOpenTable($maxCount = 500, $socid = 0)
{
	global $conf, $db, $langs, $user, $hookmanager;

	$result = '';

	if (isModEnabled('facture') && $user->hasRight('facture', 'lire')) {
		$tmpinvoice = new Facture($db);

		$sql = "SELECT f.rowid, f.ref, f.fk_statut as status, f.datef, f.type, f.total_ht, f.total_tva, f.total_ttc, f.paye, f.tms";
		$sql .= ", f.date_lim_reglement as datelimite";
		$sql .= ", s.nom as name";
		$sql .= ", s.rowid as socid, s.email";
		$sql .= ", s.code_client, s.code_compta";
		$sql .= ", s.code_fournisseur, s.code_compta_fournisseur";
		$sql .= ", cc.rowid as country_id, cc.code as country_code";
		$sql .= ", sum(pf.amount) as am";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays,".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = ".Facture::STATUS_VALIDATED;
		$sql .= " AND f.entity IN (".getEntity('invoice').')';
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if ($socid) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerUnpaid', $parameters);
		$sql .= $hookmanager->resPrint;

		$sql .= " GROUP BY f.rowid, f.ref, f.fk_statut, f.datef, f.type, f.total_ht, f.total_tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
		$sql .= " s.nom, s.rowid, s.email, s.code_client, s.code_compta, cc.rowid, cc.code";
		$sql .= ", s.code_fournisseur, s.code_compta_fournisseur";
		$sql .= " ORDER BY f.datef ASC, f.ref ASC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$othernb = 0;

			$formfile = new FormFile($db);

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<th colspan="2">';
			print $langs->trans("BillsCustomersUnpaid", $num).' ';
			print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status='.Facture::STATUS_VALIDATED.'">';
			print '<span class="badge">'.$num.'</span>';
			print '</a>';
			print '</th>';

			print '<th class="right">'.$langs->trans("DateDue").'</th>';
			if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
				print '<th class="right">'.$langs->trans("AmountHT").'</th>';
			}
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("Received").'</th>';
			print '<th width="16">&nbsp;</th>';
			print '</tr>';
			if ($num) {
				$societestatic = new Societe($db);
				$total_ttc = $totalam = $total = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					if ($i >= $maxCount) {
						$othernb += 1;
						$i++;
						$total += $obj->total_ht;
						$total_ttc += $obj->total_ttc;
						$totalam += $obj->am;
						continue;
					}

					$tmpinvoice->ref = $obj->ref;
					$tmpinvoice->id = $obj->rowid;
					$tmpinvoice->total_ht = $obj->total_ht;
					$tmpinvoice->total_tva = $obj->total_tva;
					$tmpinvoice->total_ttc = $obj->total_ttc;
					$tmpinvoice->type = $obj->type;
					$tmpinvoice->statut = $obj->status;
					$tmpinvoice->paye = $obj->paye;
					$tmpinvoice->date_lim_reglement = $db->jdate($obj->datelimite);

					$societestatic->id = $obj->socid;
					$societestatic->name = $obj->name;
					$societestatic->email = $obj->email;
					$societestatic->country_id = $obj->country_id;
					$societestatic->country_code = $obj->country_code;
					$societestatic->client = 1;
					$societestatic->code_client = $obj->code_client;
					$societestatic->code_fournisseur = $obj->code_fournisseur;
					$societestatic->code_compta = $obj->code_compta;
					$societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

					print '<tr class="oddeven">';
					print '<td class="nowrap">';

					print '<table class="nobordernopadding"><tr class="nocellnopadd">';
					print '<td class="nobordernopadding nowrap">';
					print $tmpinvoice->getNomUrl(1, '');
					print '</td>';
					print '<td width="16" class="nobordernopadding hideonsmartphone right">';
					$filename = dol_sanitizeFileName($obj->ref);
					$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
					$urlsource = $_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
					print $formfile->getDocumentsLink($tmpinvoice->element, $filename, $filedir);
					print '</td></tr></table>';

					print '</td>';
					print '<td class="nowrap tdoverflowmax100">';
					print $societestatic->getNomUrl(1, 'customer');
					print '</td>';
					print '<td class="right">';
					print dol_print_date($db->jdate($obj->datelimite), 'day');
					if ($tmpinvoice->hasDelay()) {
						print img_warning($langs->trans("Late"));
					}
					print '</td>';
					if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
						print '<td class="right"><span class="amount">'.price($obj->total_ht).'</span></td>';
					}
					print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc).'</span></td>';
					print '<td class="nowrap right"><span class="amount">'.price($obj->am).'</span></td>';
					print '<td>'.$tmpinvoice->getLibStatut(3, $obj->am).'</td>';
					print '</tr>';

					$total_ttc += $obj->total_ttc;
					$total += $obj->total_ht;
					$totalam += $obj->am;

					$i++;
				}

				if ($othernb) {
					$colspan = 6;
					if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
						$colspan++;
					}
					print '<tr class="oddeven">';
					print '<td class="nowrap" colspan="'.$colspan.'">';
					print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
					print '</td>';
					print "</tr>\n";
				}

				print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <span style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc - $totalam).')</span> </td>';
				print '<td>&nbsp;</td>';
				if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
					print '<td class="right"><span class="amount">'.price($total).'</span></td>';
				}
				print '<td class="nowrap right"><span class="amount">'.price($total_ttc).'</span></td>';
				print '<td class="nowrap right"><span class="amount">'.price($totalam).'</span></td>';
				print '<td>&nbsp;</td>';
				print '</tr>';
			} else {
				$colspan = 6;
				if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
					$colspan++;
				}
				print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
			}
			print '</table></div><br>';
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}

	return $result;
}


/**
 * Return a HTML table that contains of unpaid purchase invoices
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that conatins a list with open (unpaid) supplier invoices
 */
function getPurchaseInvoiceUnpaidOpenTable($maxCount = 500, $socid = 0)
{
	global $conf, $db, $langs, $user, $hookmanager;

	$result = '';

	if (isModEnabled("supplier_invoice") && ($user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'read'))) {
		$facstatic = new FactureFournisseur($db);

		$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut as status, ff.type, ff.libelle as label, ff.total_ht, ff.total_tva, ff.total_ttc, ff.paye";
		$sql .= ", ff.date_lim_reglement";
		$sql .= ", s.nom as name";
		$sql .= ", s.rowid as socid, s.email";
		$sql .= ", s.code_client, s.code_compta";
		$sql .= ", s.code_fournisseur, s.code_compta_fournisseur";
		$sql .= ", sum(pf.amount) as am";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE s.rowid = ff.fk_soc";
		$sql .= " AND ff.entity = ".$conf->entity;
		$sql .= " AND ff.paye = 0";
		$sql .= " AND ff.fk_statut = ".FactureFournisseur::STATUS_VALIDATED;
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if ($socid) {
			$sql .= " AND ff.fk_soc = ".((int) $socid);
		}
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhereSupplierUnpaid', $parameters);
		$sql .= $hookmanager->resPrint;

		$sql .= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.type, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.paye, ff.date_lim_reglement,";
		$sql .= " s.nom, s.rowid, s.email, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
		$sql .= " ORDER BY ff.date_lim_reglement ASC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$othernb = 0;

			$formfile = new FormFile($db);

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<th colspan="2">';
			print $langs->trans("BillsSuppliersUnpaid", $num).' ';
			print '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?search_status='.FactureFournisseur::STATUS_VALIDATED.'">';
			print '<span class="badge">'.$num.'</span>';
			print '</a>';
			print '</th>';

			print '<th class="right">'.$langs->trans("DateDue").'</th>';
			if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
				print '<th class="right">'.$langs->trans("AmountHT").'</th>';
			}
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("Paid").'</th>';
			print '<th width="16">&nbsp;</th>';
			print "</tr>\n";
			$societestatic = new Societe($db);
			if ($num) {
				$i = 0;
				$total = $total_ttc = $totalam = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					if ($i >= $maxCount) {
						$othernb += 1;
						$i++;
						$total += $obj->total_ht;
						$total_ttc += $obj->total_ttc;
						continue;
					}

					$facstatic->ref = $obj->ref;
					$facstatic->id = $obj->rowid;
					$facstatic->type = $obj->type;
					$facstatic->total_ht = $obj->total_ht;
					$facstatic->total_tva = $obj->total_tva;
					$facstatic->total_ttc = $obj->total_ttc;
					$facstatic->statut = $obj->status;
					$facstatic->paye = $obj->paye;

					$societestatic->id = $obj->socid;
					$societestatic->name = $obj->name;
					$societestatic->email = $obj->email;
					$societestatic->client = 0;
					$societestatic->fournisseur = 1;
					$societestatic->code_client = $obj->code_client;
					$societestatic->code_fournisseur = $obj->code_fournisseur;
					$societestatic->code_compta = $obj->code_compta;
					$societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

					print '<tr class="oddeven">';
					print '<td class="nowrap tdoverflowmax100">';
					print $facstatic->getNomUrl(1, '');
					print '</td>';
					print '<td class="nowrap tdoverflowmax100">'.$societestatic->getNomUrl(1, 'supplier').'</td>';
					print '<td class="right">'.dol_print_date($db->jdate($obj->date_lim_reglement), 'day').'</td>';
					if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
						print '<td class="right"><span class="amount">'.price($obj->total_ht).'</span></td>';
					}
					print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc).'</span></td>';
					print '<td class="nowrap right"><span class="amount">'.price($obj->am).'</span></td>';
					print '<td>'.$facstatic->getLibStatut(3, $obj->am).'</td>';
					print '</tr>';
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					$totalam += $obj->am;
					$i++;
				}

				if ($othernb) {
					$colspan = 6;
					if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
						$colspan++;
					}
					print '<tr class="oddeven">';
					print '<td class="nowrap" colspan="'.$colspan.'">';
					print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
					print '</td>';
					print "</tr>\n";
				}

				print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <span style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc - $totalam).')</span> </td>';
				print '<td>&nbsp;</td>';
				if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
					print '<td class="right">'.price($total).'</td>';
				}
				print '<td class="nowrap right">'.price($total_ttc).'</td>';
				print '<td class="nowrap right">'.price($totalam).'</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';
			} else {
				$colspan = 6;
				if (getDolGlobalString('MAIN_SHOW_HT_ON_SUMMARY')) {
					$colspan++;
				}
				print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
			}
			print '</table></div><br>';
		} else {
			dol_print_error($db);
		}
	}

	return $result;
}
