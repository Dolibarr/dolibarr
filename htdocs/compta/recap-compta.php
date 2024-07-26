<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Pierre-Henry Favre   <support@atm-consulting.fr>
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
 *  \file       htdocs/compta/recap-compta.php
 *	\ingroup    compta
 *  \brief      Page de fiche recap customer
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

// Load translation files required by the page
$langs->load("companies");
if (isModEnabled('invoice')) {
	$langs->load("bills");
}

$id = GETPOST('id') ? GETPOSTINT('id') : GETPOSTINT('socid');

// Security check
if ($user->socid > 0) {
	$id = $user->socid;
}

$result = restrictedArea($user, 'societe', $id, '&societe');

$object = new Societe($db);
if ($id > 0) {
	$object->fetch($id);
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('recapcomptacard', 'globalcard'));

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "f.datef,f.rowid"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "DESC";
}


$arrayfields = array(
	'f.datef'=>array('label'=>"Date", 'checked'=>1),
	//...
);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('supplierbalencelist', 'globalcard'));


/*
 * Actions
 */

$parameters = array('socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object); // Note that $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// None


/*
 *	View
 */

$form = new Form($db);
$userstatic = new User($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Summary");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Summary");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url);

if ($id > 0) {
	$param = '';
	if ($id > 0) {
		$param .= '&socid='.$id;
	}

	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');
	dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 1);
	print dol_get_fiche_end();

	if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
		// Invoice list
		print load_fiche_titre($langs->trans("CustomerPreview"));

		print '<table class="noborder tagtable liste centpercent">';
		print '<tr class="liste_titre">';
		if (!empty($arrayfields['f.datef']['checked'])) {
			print_liste_field_titre($arrayfields['f.datef']['label'], $_SERVER["PHP_SELF"], "f.datef", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
		}
		print '<td>'.$langs->trans("Element").'</td>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td class="right">'.$langs->trans("Debit").'</td>';
		print '<td class="right">'.$langs->trans("Credit").'</td>';
		print '<td class="right">'.$langs->trans("Balance").'</td>';
		print '<td class="right">'.$langs->trans("Author").'</td>';
		print '</tr>';

		$TData = array();

		$sql = "SELECT s.nom, s.rowid as socid, f.ref, f.total_ttc, f.datef as df,";
		$sql .= " f.paye as paye, f.fk_statut as statut, f.rowid as facid,";
		$sql .= " u.login, u.rowid as userid";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = ".((int) $object->id);
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.fk_user_valid = u.rowid";
		$sql .= $db->order($sortfield, $sortorder);

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			// Boucle sur chaque facture
			for ($i = 0; $i < $num; $i++) {
				$objf = $db->fetch_object($resql);

				$fac = new Facture($db);
				$ret = $fac->fetch($objf->facid);
				if ($ret < 0) {
					print $fac->error."<br>";
					continue;
				}
				$totalpaid = $fac->getSommePaiement();

				$userstatic->id = $objf->userid;
				$userstatic->login = $objf->login;

				$values = array(
					'fk_facture' => $objf->facid,
					'date' => $fac->date,
					'datefieldforsort' => $fac->date.'-'.$fac->ref,
					'link' => $fac->getNomUrl(1),
					'status' => $fac->getLibStatut(2, $totalpaid),
					'amount' => $fac->total_ttc,
					'author' => $userstatic->getLoginUrl(1)
				);

				$parameters = array('socid' => $id, 'values' => &$values, 'fac' => $fac, 'userstatic' => $userstatic);
				$reshook = $hookmanager->executeHooks('facdao', $parameters, $object); // Note that $parameters['values'] and $object may have been modified by some hooks
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				$TData[] = $values;

				// Paiements
				$sql = "SELECT p.rowid, p.datep as dp, pf.amount, p.statut,";
				$sql .= " p.fk_user_creat, u.login, u.rowid as userid";
				$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf,";
				$sql .= " ".MAIN_DB_PREFIX."paiement as p";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_creat = u.rowid";
				$sql .= " WHERE pf.fk_paiement = p.rowid";
				$sql .= " AND p.entity = ".$conf->entity;
				$sql .= " AND pf.fk_facture = ".((int) $fac->id);
				$sql .= " ORDER BY p.datep ASC, p.rowid ASC";

				$resqlp = $db->query($sql);
				if ($resqlp) {
					$nump = $db->num_rows($resqlp);
					$j = 0;

					while ($j < $nump) {
						$objp = $db->fetch_object($resqlp);

						$paymentstatic = new Paiement($db);
						$paymentstatic->id = $objp->rowid;

						$userstatic->id = $objp->userid;
						$userstatic->login = $objp->login;

						$values = array(
						'fk_paiement' => $objp->rowid,
							'date' => $db->jdate($objp->dp),
							'datefieldforsort' => $db->jdate($objp->dp).'-'.$fac->ref,
							'link' => $langs->trans("Payment").' '.$paymentstatic->getNomUrl(1),
							'status' => '',
							'amount' => -$objp->amount,
							'author' => $userstatic->getLoginUrl(1)
						);

						$parameters = array('socid' => $id, 'values' => &$values, 'fac' => $fac, 'userstatic' => $userstatic, 'paymentstatic' => $paymentstatic);
						$reshook = $hookmanager->executeHooks('paydao', $parameters, $object); // Note that $parameters['values'] and $object may have been modified by some hooks
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						$TData[] = $values;

						$j++;
					}

					$db->free($resqlp);
				} else {
					dol_print_error($db);
				}
			}
		} else {
			dol_print_error($db);
		}

		if (empty($TData)) {
			print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("NoInvoice").'</span></td></tr>';
		} else {
			// Sort array by date ASC to calculate balance
			$TData = dol_sort_array($TData, 'datefieldforsort', 'ASC');

			// Balance calculation
			$balance = 0;
			foreach ($TData as &$data1) {
				$balance += $data1['amount'];
				if (!isset($data1['balance'])) {
					$data1['balance'] = 0;
				}
				$data1['balance'] += $balance;
			}

			// Resorte array to have elements on the required $sortorder
			$TData = dol_sort_array($TData, 'datefieldforsort', $sortorder);

			$totalDebit = 0;
			$totalCredit = 0;

			// Display array
			foreach ($TData as $data) {
				$html_class = '';
				if (!empty($data['fk_facture'])) {
					$html_class = 'facid-'.$data['fk_facture'];
				} elseif (!empty($data['fk_paiement'])) {
					$html_class = 'payid-'.$data['fk_paiement'];
				}

				print '<tr class="oddeven '.$html_class.'">';

				$datedetail = dol_print_date($data['date'], 'dayhour');
				if (!empty($data['fk_facture'])) {
					$datedetail = dol_print_date($data['date'], 'day');
				}
				print '<td class="center" title="'.dol_escape_htmltag($datedetail).'">';
				print dol_print_date($data['date'], 'day');
				print "</td>\n";

				print '<td>'.$data['link']."</td>\n";

				print '<td class="left">'.$data['status'].'</td>';

				print '<td class="right">'.(($data['amount'] > 0) ? price(abs($data['amount'])) : '')."</td>\n";

				$totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;

				print '<td class="right">'.(($data['amount'] > 0) ? '' : price(abs($data['amount'])))."</td>\n";
				$totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);

				// Balance
				print '<td class="right"><span class="amount">'.price($data['balance'])."</span></td>\n";

				// Author
				print '<td class="nowrap right">';
				print $data['author'];
				print '</td>';

				print "</tr>\n";
			}

			print '<tr class="liste_total">';
			print '<td colspan="3">&nbsp;</td>';
			print '<td class="right">'.price($totalDebit).'</td>';
			print '<td class="right">'.price($totalCredit).'</td>';
			print '<td class="right">'.price(price2num($totalDebit - $totalCredit, 'MT')).'</td>';
			print '<td></td>';
			print "</tr>\n";
		}

		print "</table>";
	}
} else {
	dol_print_error($db);
}

llxFooter();

$db->close();
