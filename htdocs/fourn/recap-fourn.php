<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019 Pierre Ardoin <mapiolca@me.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  	\file       htdocs/fourn/recap-fourn.php
 *		\ingroup    fournisseur
 *		\brief      Page de fiche recap supplier
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies'));

// Security check
$socid = GETPOSTINT("socid");
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('supplierbalencelist', 'globalcard'));

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

/*
 * View
 */

$form = new Form($db);
$userstatic = new User($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-fourn page-recap-fourn');

if ($socid > 0) {
	$societe = new Societe($db);
	$societe->fetch($socid);

	/*
	 * Show tabs
	 */
	$head = societe_prepare_head($societe);

	print dol_get_fiche_head($head, 'supplier', $langs->trans("ThirdParty"), 0, 'company');
	dol_banner_tab($societe, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');
	print dol_get_fiche_end();

	if ((isModEnabled("fournisseur") && $user->hasRight("fournisseur", "facture", "lire") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_invoice") && $user->hasRight("supplier_invoice", "lire"))) {
		// Invoice list
		print load_fiche_titre($langs->trans("SupplierPreview"));

		// Add parameter for sorting
		$param = '';
		if ($socid > 0) {
			$param .= '&socid=' . $socid;
		}

		print '<table class="noborder tagtable liste centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
		print '<td>' . $langs->trans("Element") . '</td>';
		print '<td>' . $langs->trans("Status") . '</td>';
		print '<td class="right">' . $langs->trans("Debit") . '</td>';
		print '<td class="right">' . $langs->trans("Credit") . '</td>';
		print '<td class="right">' . $langs->trans("Balance") . '</td>';
		print '<td class="right">' . $langs->trans("Author") . '</td>';
		print '</tr>';

		/** @var array<string|int,mixed> $TData */
		$TData = array();

		$sql = "SELECT s.nom, s.rowid as socid, f.ref_supplier, f.total_ttc, f.datef as df,";
		$sql .= " f.paye as paye, f.fk_statut as statut, f.rowid as facid,";
		$sql .= " u.login, u.rowid as userid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s," . MAIN_DB_PREFIX . "facture_fourn as f," . MAIN_DB_PREFIX . "user as u";
		$sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = " . ((int) $societe->id);
		$sql .= " AND f.entity IN (" . getEntity("facture_fourn") . ")"; // Recognition of the entity attributed to this invoice for Multicompany
		$sql .= " AND f.fk_user_valid = u.rowid";
		$sql .= $db->order($sortfield, $sortorder);

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			// Boucle sur chaque facture
			for ($i = 0; $i < $num; $i++) {
				$objf = $db->fetch_object($resql);

				$fac = new FactureFournisseur($db);
				$ret = $fac->fetch($objf->facid);
				if ($ret < 0) {
					print $fac->error . "<br>";
					continue;
				}
				$totalpaid = $fac->getSommePaiement();

				$userstatic->id = $objf->userid;
				$userstatic->login = $objf->login;

				$values = array(
					'fk_facture' => $objf->facid,
					'date' => $fac->date,
					'datefieldforsort' => $fac->date . '-' . $fac->ref,
					'link' => $fac->getNomUrl(1),
					'status' => $fac->getLibStatut(2, $totalpaid),
					'amount' => $fac->total_ttc,
					'author' => $userstatic->getLoginUrl(1)
				);

				$TData[] = $values;

				// Payments
				$sql = "SELECT p.rowid, p.datep as dp, pf.amount, p.statut,";
				$sql .= " p.fk_user_author, u.login, u.rowid as userid";
				$sql .= " FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as pf,";
				$sql .= " " . MAIN_DB_PREFIX . "paiementfourn as p";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON p.fk_user_author = u.rowid";
				$sql .= " WHERE pf.fk_paiementfourn = p.rowid";
				$sql .= " AND pf.fk_facturefourn = " . ((int) $fac->id);
				$sql .= " ORDER BY p.datep ASC, p.rowid ASC";

				$resqlp = $db->query($sql);
				if ($resqlp) {
					$nump = $db->num_rows($resqlp);
					$j = 0;

					while ($j < $nump) {
						$objp = $db->fetch_object($resqlp);

						$paymentstatic = new PaiementFourn($db);
						$paymentstatic->id = $objp->rowid;

						$userstatic->id = $objp->userid;
						$userstatic->login = $objp->login;

						$values = array(
							'fk_paiement' => $objp->rowid,
							'date' => $db->jdate($objp->dp),
							'datefieldforsort' => $db->jdate($objp->dp) . '-' . $fac->ref,
							'link' => $langs->trans("Payment") . ' ' . $paymentstatic->getNomUrl(1),
							'status' => '',
							'amount' => -$objp->amount,
							'author' => $userstatic->getLoginUrl(1)
						);

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
			print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoInvoice") . '</span></td></tr>';
		} else {
			// Sort array by date ASC to calculate balance
			$TData = dol_sort_array($TData, 'datefieldforsort', 'ASC');

			// Balance calculation
			$balance = 0;
			foreach (array_keys($TData) as $key) {
				$balance += $TData[$key]['amount'];
				if (!array_key_exists('balance', $TData[$key])) {
					$TData[$key]['balance'] = 0;
				}
				$TData[$key]['balance'] += $balance;
			}

			// Resorte array to have elements on the required $sortorder
			$TData = dol_sort_array($TData, 'datefieldforsort', $sortorder);

			$totalDebit = 0;
			$totalCredit = 0;

			// Display array
			foreach ($TData as $data) {
				$html_class = '';
				if (!empty($data['fk_facture'])) {
					$html_class = 'facid-' . $data['fk_facture'];
				} elseif (!empty($data['fk_paiement'])) {
					$html_class = 'payid-' . $data['fk_paiement'];
				}

				print '<tr class="oddeven ' . $html_class . '">';

				$datedetail = dol_print_date($data['date'], 'dayhour');
				if (!empty($data['fk_facture'])) {
					$datedetail = dol_print_date($data['date'], 'day');
				}
				print '<td class="center" title="' . dol_escape_htmltag($datedetail) . '">';
				print dol_print_date($data['date'], 'day');
				print "</td>\n";

				print '<td>' . $data['link'] . "</td>\n";

				print '<td class="left">' . $data['status'] . '</td>';

				print '<td class="right">' . (($data['amount'] > 0) ? price(abs($data['amount'])) : '') . "</td>\n";

				$totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;

				print '<td class="right">' . (($data['amount'] > 0) ? '' : price(abs($data['amount']))) . "</td>\n";
				$totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);

				// Balance
				print '<td class="right"><span class="amount">' . price($data['balance']) . "</span></td>\n";

				// Author
				print '<td class="nowrap right">';
				print $data['author'];
				print '</td>';

				print "</tr>\n";
			}

			print '<tr class="liste_total">';
			print '<td colspan="3">&nbsp;</td>';
			print '<td class="right">' . price($totalDebit) . '</td>';
			print '<td class="right">' . price($totalCredit) . '</td>';
			print '<td class="right">' . price(price2num($totalDebit - $totalCredit, 'MT')) . '</td>';
			print '<td></td>';
			print "</tr>\n";
		}

		print "</table>";
	}
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
