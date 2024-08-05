<?php
/* Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008-2009  Laurent Destailleur (Eldy)  <eldy@users.sourceforge.net>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com
 * Copyright (C) 2016       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2022       Alexandre Spangaro          <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/compta/bank/treso.php
 *	\ingroup    banque
 *	\brief      Page to estimate future balance
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'bills', 'categories', 'companies', 'salaries'));

// Security check
if (GETPOSTISSET("account") || GETPOSTISSET("ref")) {
	$id = GETPOSTISSET("account") ? GETPOST("account") : (GETPOSTISSET("ref") ? GETPOST("ref") : '');
}
$fieldid = GETPOSTISSET("ref") ? 'ref' : 'rowid';
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'banque', $id, 'bank_account&bank_account', '', '', $fieldid);


$vline = GETPOST('vline');
$page = GETPOSTISSET("page") ? GETPOST("page") : 0;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('banktreso', 'globalcard'));


/*
 * View
 */
$societestatic = new Societe($db);
$userstatic = new User($db);
$facturestatic = new Facture($db);
$facturefournstatic = new FactureFournisseur($db);
$socialcontribstatic = new ChargeSociales($db);
$salarystatic = new Salary($db);
$vatstatic = new Tva($db);

$form = new Form($db);

if (GETPOST("account") || GETPOST("ref")) {
	if ($vline) {
		$viewline = $vline;
	} else {
		$viewline = 20;
	}

	$object = new Account($db);
	if (GETPOSTINT("account")) {
		$result = $object->fetch(GETPOSTINT("account"));
	}
	if (GETPOST("ref")) {
		$result = $object->fetch(0, GETPOST("ref"));
		$id = $object->id;
	}

	$title = $object->ref.' - '.$langs->trans("PlannedTransactions");
	$helpurl = "";
	llxHeader('', $title, $helpurl);

	// Onglets
	$head = bank_prepare_head($object);
	print dol_get_fiche_head($head, 'cash', $langs->trans("FinancialAccount"), 0, 'account');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

	print dol_get_fiche_end();


	// Remainder to pay in future
	$sqls = array();

	// Customer invoices
	$sql = "SELECT 'invoice' as family, f.rowid as objid, f.ref as ref, f.total_ttc, f.type, f.date_lim_reglement as dlr,";
	$sql .= " s.rowid as socid, s.nom as name, s.fournisseur";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
	$sql .= " WHERE f.entity IN  (".getEntity('invoice').")";
	$sql .= " AND f.paye = 0 AND f.fk_statut = 1"; // Not paid
	$sql .= " AND (f.fk_account IN (0, ".$object->id.") OR f.fk_account IS NULL)"; // Id bank account of invoice
	$sql .= " ORDER BY dlr ASC";
	$sqls[] = $sql;

	// Supplier invoices
	$sql = " SELECT 'invoice_supplier' as family, ff.rowid as objid, ff.ref as ref, ff.ref_supplier as ref_supplier, (-1*ff.total_ttc) as total_ttc, ff.type, ff.date_lim_reglement as dlr,";
	$sql .= " s.rowid as socid, s.nom as name, s.fournisseur";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ff.fk_soc = s.rowid";
	$sql .= " WHERE ff.entity = ".$conf->entity;
	$sql .= " AND ff.paye = 0 AND fk_statut = 1"; // Not paid
	$sql .= " AND (ff.fk_account IN (0, ".$object->id.") OR ff.fk_account IS NULL)"; // Id bank account of supplier invoice
	$sql .= " ORDER BY dlr ASC";
	$sqls[] = $sql;

	// Social contributions
	$sql = " SELECT 'social_contribution' as family, cs.rowid as objid, cs.libelle as ref, (-1*cs.amount) as total_ttc, ccs.libelle as type, cs.date_ech as dlr,";
	$sql .= " 0 as socid, 'noname' as name, 0 as fournisseur";
	$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_chargesociales as ccs ON cs.fk_type = ccs.id";
	$sql .= " WHERE cs.entity = ".$conf->entity;
	$sql .= " AND cs.paye = 0"; // Not paid
	$sql .= " AND (cs.fk_account IN (0, ".$object->id.") OR cs.fk_account IS NULL)"; // Id bank account of social contribution
	$sql .= " ORDER BY dlr ASC";
	$sqls[] = $sql;

	// Salaries
	$sql = " SELECT 'salary' as family, sa.rowid as objid, sa.label as ref, (-1*sa.amount) as total_ttc, sa.dateep as dlr,";
	$sql .= " s.rowid as socid, CONCAT(s.firstname, ' ', s.lastname) as name, 0 as fournisseur";
	$sql .= " FROM ".MAIN_DB_PREFIX."salary as sa";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as s ON sa.fk_user = s.rowid";
	$sql .= " WHERE sa.entity = ".$conf->entity;
	$sql .= " AND sa.paye = 0"; // Not paid
	$sql .= " AND (sa.fk_account IN (0, ".$object->id.") OR sa.fk_account IS NULL)"; // Id bank account of salary
	$sql .= " ORDER BY dlr ASC";
	$sqls[] = $sql;

	// VAT
	$sql = " SELECT 'vat' as family, t.rowid as objid, t.label as ref, (-1*t.amount) as total_ttc, t.datev as dlr,";
	$sql .= " 0 as socid, 'noname' as name, 0 as fournisseur";
	$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
	$sql .= " WHERE t.entity = ".$conf->entity;
	$sql .= " AND t.paye = 0"; // Not paid
	$sql .= " AND (t.fk_account IN (-1, 0, ".$object->id.") OR t.fk_account IS NULL)"; // Id bank account of vat
	$sql .= " ORDER BY dlr ASC";
	$sqls[] = $sql;

	// others sql
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreSQL', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook) and isset($hookmanager->resArray['sql'])) {
		$sqls[] = $hookmanager->resArray['sql'];
	}

	$error = 0;
	$tab_sqlobjOrder = array();
	$tab_sqlobj = array();
	$nbtotalofrecords = 0;

	foreach ($sqls as $sql) {
		$resql = $db->query($sql);
		if ($resql) {
			$nbtotalofrecords += $db->num_rows($resql);
			while ($sqlobj = $db->fetch_object($resql)) {
				$tmpobj = new stdClass();
				$tmpobj->family = $sqlobj->family;
				$tmpobj->objid = $sqlobj->objid;
				$tmpobj->ref = $sqlobj->ref;
				$tmpobj->total_ttc = $sqlobj->total_ttc;
				$tmpobj->type = $sqlobj->type;
				$tmpobj->dlr = $db->jdate($sqlobj->dlr);
				$tmpobj->socid = $sqlobj->socid;
				$tmpobj->name = $sqlobj->name;
				$tmpobj->fournisseur = $sqlobj->fournisseur;

				$tab_sqlobj[] = $tmpobj;
				$tab_sqlobjOrder[] = $db->jdate($sqlobj->dlr);
			}
			$db->free($resql);
		} else {
			$error++;
		}
	}

	$param = '';
	$sortfield = '';
	$sortorder = '';
	$massactionbutton = '';
	$num = 0;
	$picto = '';
	$morehtml = '';
	$limit = 0;

	print_barre_liste($langs->trans("RemainderToPay"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $morehtml, '', $limit, 0, 0, 1);


	$solde = $object->solde(0);
	if (getDolGlobalInt('MULTICOMPANY_INVOICE_SHARING_ENABLED')) {
		$colspan = 6;
	} else {
		$colspan = 5;
	}

	// Show next coming entries
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';

	// Ligne de titre tableau des ecritures
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("DateDue").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	if (getDolGlobalInt('MULTICOMPANY_INVOICE_SHARING_ENABLED')) {
		print '<td>'.$langs->trans("Entity").'</td>';
	}
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td class="right">'.$langs->trans("Debit").'</td>';
	print '<td class="right">'.$langs->trans("Credit").'</td>';
	print '<td class="right" width="80">'.$langs->trans("BankBalance").'</td>';
	print '</tr>';

	// Current balance
	print '<tr class="liste_total">';
	print '<td class="left" colspan="5">'.$langs->trans("CurrentBalance").'</td>';
	print '<td class="nowrap right">'.price($solde).'</td>';
	print '</tr>';

	// Sort array
	if (!$error) {
		array_multisort($tab_sqlobjOrder, $tab_sqlobj);

		$num = count($tab_sqlobj);

		$i = 0;
		while ($i < $num) {
			$ref = '';
			$refcomp = '';
			$totalpayment = '';

			$tmpobj = array_shift($tab_sqlobj);

			if ($tmpobj->family == 'invoice_supplier') {
				$showline = 1;
				// Uncomment this line to avoid to count suppliers credit note (ff.type = 2)
				//$showline=(($tmpobj->total_ttc < 0 && $tmpobj->type != 2) || ($tmpobj->total_ttc > 0 && $tmpobj->type == 2))
				if ($showline) {
					$ref = $tmpobj->ref;
					$facturefournstatic->ref = $ref;
					$facturefournstatic->id = $tmpobj->objid;
					$facturefournstatic->type = $tmpobj->type;
					$ref = $facturefournstatic->getNomUrl(1, '');

					$societestatic->id = $tmpobj->socid;
					$societestatic->name = $tmpobj->name;
					$refcomp = $societestatic->getNomUrl(1, '', 24);

					$totalpayment = -1 * $facturefournstatic->getSommePaiement(); // Payment already done
				}
			}
			if ($tmpobj->family == 'invoice') {
				$facturestatic->ref = $tmpobj->ref;
				$facturestatic->id = $tmpobj->objid;
				$facturestatic->type = $tmpobj->type;
				$ref = $facturestatic->getNomUrl(1, '');

				$societestatic->id = $tmpobj->socid;
				$societestatic->name = $tmpobj->name;
				$refcomp = $societestatic->getNomUrl(1, '', 24);

				$totalpayment = $facturestatic->getSommePaiement(); // Payment already done
				$totalpayment += $facturestatic->getSumDepositsUsed();
				$totalpayment += $facturestatic->getSumCreditNotesUsed();
			}
			if ($tmpobj->family == 'social_contribution') {
				$socialcontribstatic->ref = $tmpobj->ref;
				$socialcontribstatic->id = $tmpobj->objid;
				$socialcontribstatic->label = $tmpobj->type;
				$ref = $socialcontribstatic->getNomUrl(1, 24);

				$totalpayment = -1 * $socialcontribstatic->getSommePaiement(); // Payment already done
			}
			if ($tmpobj->family == 'salary') {
				$salarystatic->ref = $tmpobj->ref;
				$salarystatic->id = $tmpobj->objid;
				$salarystatic->label = $langs->trans("SalaryPayment");
				$ref = $salarystatic->getNomUrl(1, '');

				$userstatic->id = $tmpobj->socid;
				$userstatic->name = $tmpobj->name;
				$refcomp = $userstatic->getNomUrl(1);

				$totalpayment = -1 * $salarystatic->getSommePaiement(); // Payment already done
			}
			if ($tmpobj->family == 'vat') {
				$vatstatic->ref = $tmpobj->ref;
				$vatstatic->id = $tmpobj->objid;
				$vatstatic->type = $tmpobj->type;
				$ref = $vatstatic->getNomUrl(1, '');

				$totalpayment = -1 * $vatstatic->getSommePaiement(); // Payment already done
			}

			$parameters = array('obj' => $tmpobj, 'ref' => $ref, 'refcomp' => $refcomp, 'totalpayment' => $totalpayment);
			$reshook = $hookmanager->executeHooks('moreFamily', $parameters, $tmpobject, $action); // Note that $action and $tmpobject may have been modified by hook
			if (empty($reshook)) {
				$ref = isset($hookmanager->resArray['ref']) ? $hookmanager->resArray['ref'] : $ref;
				$refcomp = isset($hookmanager->resArray['refcomp']) ? $hookmanager->resArray['refcomp'] : $refcomp;
				$totalpayment = isset($hookmanager->resArray['totalpayment']) ? $hookmanager->resArray['totalpayment'] : $totalpayment;
			}

			$total_ttc = $tmpobj->total_ttc;
			if ($totalpayment) {
				$total_ttc = $tmpobj->total_ttc - $totalpayment;
			}
			$solde += $total_ttc;

			// We discard lines with a remainder to pay to 0
			if (price2num($total_ttc) != 0) {
				// Show line
				print '<tr class="oddeven">';
				print '<td>';
				if ($tmpobj->dlr) {
					print dol_print_date($tmpobj->dlr, "day");
				} else {
					print $langs->trans("NotDefined");
				}
				print "</td>";
				print "<td>".$ref."</td>";
				if (getDolGlobalString("MULTICOMPANY_INVOICE_SHARING_ENABLED")) {
					if ($tmpobj->family == 'invoice') {
						$mc->getInfo($tmpobj->entity);
						print "<td>".$mc->label."</td>";
					} else {
						print "<td></td>";
					}
				}
				print "<td>".$refcomp."</td>";
				if ($tmpobj->total_ttc < 0) {
					print '<td class="nowrap right">'.price(abs($total_ttc))."</td><td>&nbsp;</td>";
				}
				if ($tmpobj->total_ttc >= 0) {
					print '<td>&nbsp;</td><td class="nowrap right">'.price($total_ttc)."</td>";
				}
				print '<td class="nowrap right">'.price($solde).'</td>';
				print "</tr>";
			}

			$i++;
		}
	} else {
		dol_print_error($db);
	}

	// Other lines
	$parameters = array('solde' => $solde);
	$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		print $hookmanager->resPrint;
		$solde = isset($hookmanager->resArray['solde']) ? $hookmanager->resArray['solde'] : $solde;
	}

	// solde
	print '<tr class="liste_total">';
	print '<td class="left" colspan="'.$colspan.'">'.$langs->trans("FutureBalance").' ('.$object->currency_code.')</td>';
	print '<td class="nowrap right">'.price($solde, 0, $langs, 0, 0, -1, $object->currency_code).'</td>';
	print '</tr>';

	print "</table>";
	print "</div>";
} else {
	print $langs->trans("ErrorBankAccountNotFound");
}

// End of page
llxFooter();
$db->close();
