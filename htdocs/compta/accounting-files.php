<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Pierre-Henry Favre   <support@atm-consulting.fr>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.fr>
 * Copyright (C) 2021      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
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
  *  \file       htdocs/compta/accounting-files.php
  *  \ingroup    compta
  *  \brief      Page to show portoflio and files of a thirdparty and download it
  */

if ((array_key_exists('action', $_GET) && $_GET['action'] == 'dl') || (array_key_exists('action', $_POST) && $_POST['action'] == 'dl')) {	// To not replace token when downloading file
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', '1');
	}
}

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';

// Constant to define payment sens
const PAY_DEBIT = 0;
const PAY_CREDIT = 1;

$langs->loadLangs(array("accountancy", "bills", "companies", "salaries", "compta", "trips", "banks", "loan"));

$date_start = GETPOST('date_start', 'alpha');
$date_startDay = GETPOST('date_startday', 'int');
$date_startMonth = GETPOST('date_startmonth', 'int');
$date_startYear = GETPOST('date_startyear', 'int');
$date_start = dol_mktime(0, 0, 0, $date_startMonth, $date_startDay, $date_startYear, 'tzuserrel');
$date_stop = GETPOST('date_stop', 'alpha');
$date_stopDay = GETPOST('date_stopday', 'int');
$date_stopMonth = GETPOST('date_stopmonth', 'int');
$date_stopYear = GETPOST('date_stopyear', 'int');
$date_stop = dol_mktime(23, 59, 59, $date_stopMonth, $date_stopDay, $date_stopYear, 'tzuserrel');
$action = GETPOST('action', 'aZ09');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('comptafileslist', 'globallist'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "date,item"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "DESC";
}


$arrayfields = array(
	'type'=>array('label'=>"Type", 'checked'=>1),
	'date'=>array('label'=>"Date", 'checked'=>1),
	'date_due'=>array('label'=>"DateDue", 'checked'=>1),
	'ref'=>array('label'=>"Ref", 'checked'=>1),
	'documents'=>array('label'=>"Documents", 'checked'=>1),
	'paid'=>array('label'=>"Paid", 'checked'=>1),
	'total_ht'=>array('label'=>"TotalHT", 'checked'=>1),
	'total_ttc'=>array('label'=>"TotalTTC", 'checked'=>1),
	'total_vat'=>array('label'=>"TotalVAT", 'checked'=>1),
	//...
);

// Security check
if (empty($conf->comptabilite->enabled) && empty($conf->accounting->enabled)) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}

// Define $arrayofentities if multientity is set.
$arrayofentities = array();
if (!empty($conf->multicompany->enabled) && is_object($mc)) {
	$arrayofentities = $mc->getEntitiesList();
}

$entity = (GETPOSTISSET('entity') ? GETPOST('entity', 'int') : (GETPOSTISSET('search_entity') ? GETPOST('search_entity', 'int') : $conf->entity));
if (!empty($conf->multicompany->enabled) && is_object($mc)) {
	if (empty($entity) && !empty($conf->global->MULTICOMPANY_ALLOW_EXPORT_ACCOUNTING_DOC_FOR_ALL_ENTITIES)) {
		$entity = '0,'.join(',', array_keys($arrayofentities));
	}
}
if (empty($entity)) {
	$entity = $conf->entity;
}

$error = 0;

$listofchoices = array(
	'selectinvoices'=>array('label'=>'Invoices', 'lang'=>'bills', 'enabled' => !empty($conf->facture->enabled), 'perms' => !empty($user->rights->facture->lire)),
	'selectsupplierinvoices'=>array('label'=>'BillsSuppliers', 'lang'=>'bills', 'enabled' => !empty($conf->supplier_invoice->enabled), 'perms' => !empty($user->rights->fournisseur->facture->lire)),
	'selectexpensereports'=>array('label'=>'ExpenseReports', 'lang'=>'trips', 'enabled' => !empty($conf->expensereport->enabled), 'perms' => !empty($user->rights->expensereport->lire)),
	'selectdonations'=>array('label'=>'Donations', 'lang'=>'donation', 'enabled' => !empty($conf->don->enabled), 'perms' => !empty($user->rights->don->lire)),
	'selectsocialcontributions'=>array('label'=>'SocialContributions', 'enabled' => !empty($conf->tax->enabled), 'perms' => !empty($user->rights->tax->charges->lire)),
	'selectpaymentsofsalaries'=>array('label'=>'SalariesPayments', 'lang'=>'salaries', 'enabled' => !empty($conf->salaries->enabled), 'perms' => !empty($user->rights->salaries->read)),
	'selectvariouspayment'=>array('label'=>'VariousPayment', 'enabled' => !empty($conf->banque->enabled), 'perms' => !empty($user->rights->banque->lire)),
	'selectloanspayment'=>array('label'=>'PaymentLoan', 'enabled' => !empty($conf->loan->enabled), 'perms' => !empty($user->rights->loan->read)),
);



/*
 * Actions
 */


//$parameters = array('socid' => $id);
//$reshook = $hookmanager->executeHooks('doActions', $parameters, $object); // Note that $object may have been modified by some hooks
//if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$filesarray = array();
$result = false;
if (($action == 'searchfiles' || $action == 'dl')) {
	if (empty($date_start)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateStart")), null, 'errors');
		$error++;
	}
	if (empty($date_stop)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$sql = '';

		$wheretail = " '".$db->idate($date_start)."' AND '".$db->idate($date_stop)."'";

		// Customer invoices
		if (GETPOST('selectinvoices') && !empty($listofchoices['selectinvoices']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= "SELECT t.rowid as id, t.entity, t.ref, t.paye as paid, t.total_ht, t.total_ttc, t.total_tva as total_vat, t.multicurrency_code as currency, t.fk_soc, t.datef as date, t.date_lim_reglement as date_due, 'Invoice' as item, s.nom as thirdparty_name, s.code_client as thirdparty_code, c.code as country_code, s.tva_intra as vatnum, ".PAY_CREDIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as t LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = s.fk_pays";
			$sql .= " WHERE datef between ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
			$sql .= " AND t.fk_statut <> ".Facture::STATUS_DRAFT;
		}
		// Vendor invoices
		if (GETPOST('selectsupplierinvoices') && !empty($listofchoices['selectsupplierinvoices']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, t.entity, t.ref, t.paye as paid, t.total_ht, t.total_ttc, t.total_tva as total_vat, t.multicurrency_code as currency, t.fk_soc, t.datef as date, t.date_lim_reglement as date_due, 'SupplierInvoice' as item, s.nom as thirdparty_name, s.code_fournisseur as thirdparty_code, c.code as country_code, s.tva_intra as vatnum, ".PAY_DEBIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as t LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = s.fk_pays";
			$sql .= " WHERE datef between ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
			$sql .= " AND t.fk_statut <> ".FactureFournisseur::STATUS_DRAFT;
		}
		// Expense reports
		if (GETPOST('selectexpensereports') && !empty($listofchoices['selectexpensereports']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, t.entity, t.ref, t.paid, t.total_ht, t.total_ttc, t.total_tva as total_vat, t.multicurrency_code as currency, t.fk_user_author as fk_soc, t.date_fin as date, t.date_fin as date_due, 'ExpenseReport' as item, CONCAT(CONCAT(u.lastname, ' '), u.firstname) as thirdparty_name, '' as thirdparty_code, c.code as country_code, '' as vatnum, ".PAY_DEBIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as t LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.fk_user_author LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = u.fk_country";
			$sql .= " WHERE date_fin between  ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
			$sql .= " AND t.fk_statut <> ".ExpenseReport::STATUS_DRAFT;
		}
		// Donations
		if (GETPOST('selectdonations') && !empty($listofchoices['selectdonations']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, t.entity, t.ref, paid, amount as total_ht, amount as total_ttc, 0 as total_vat, '".$db->escape($conf->currency)."' as currency, 0 as fk_soc, t.datedon as date, t.datedon as date_due, 'Donation' as item, t.societe as thirdparty_name, '' as thirdparty_code, c.code as country_code, '' as vatnum, ".PAY_CREDIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."don as t LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = t.fk_country";
			$sql .= " WHERE datedon between ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
			$sql .= " AND t.fk_statut <> ".Don::STATUS_DRAFT;
		}
		// Payments of salaries
		if (GETPOST('selectpaymentsofsalaries') && !empty($listofchoices['selectpaymentsofsalaries']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, t.entity, t.label as ref, 1 as paid, amount as total_ht, amount as total_ttc, 0 as total_vat, '".$db->escape($conf->currency)."' as currency, t.fk_user as fk_soc, t.datep as date, t.dateep as date_due, 'SalaryPayment' as item, CONCAT(CONCAT(u.lastname, ' '), u.firstname)  as thirdparty_name, '' as thirdparty_code, c.code as country_code, '' as vatnum, ".PAY_DEBIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as t LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.fk_user LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = u.fk_country";
			$sql .= " WHERE datep between ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
			//$sql.=" AND fk_statut <> ".PaymentSalary::STATUS_DRAFT;
		}
		// Social contributions
		if (GETPOST('selectsocialcontributions') && !empty($listofchoices['selectsocialcontributions']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, t.entity, t.libelle as ref, t.paye as paid, t.amount as total_ht, t.amount as total_ttc, 0 as total_vat, '".$db->escape($conf->currency)."' as currency, 0 as fk_soc, t.date_ech as date, t.periode as date_due, 'SocialContributions' as item, '' as thirdparty_name, '' as thirdparty_code, '' as country_code, '' as vatnum, ".PAY_DEBIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as t";
			$sql .= " WHERE t.date_ech between ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
			//$sql.=" AND fk_statut <> ".ChargeSociales::STATUS_DRAFT;
		}
		// Various payments
		if (GETPOST('selectvariouspayment') && !empty($listofchoices['selectvariouspayment']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, t.entity, t.ref, 1 as paid, t.amount as total_ht, t.amount as total_ttc, 0 as total_vat, '".$db->escape($conf->currency)."' as currency, 0 as fk_soc, t.datep as date, t.datep as date_due, 'VariousPayment' as item, '' as thirdparty_name, '' as thirdparty_code, '' as country_code, '' as vatnum, sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."payment_various as t";
			$sql .= " WHERE datep between ".$wheretail;
			$sql .= " AND t.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
		}
		// Loan payments
		if (GETPOST('selectloanspayment') && !empty($listofchoices['selectloanspayment']['perms'])) {
			if (!empty($sql)) {
				$sql .= " UNION ALL";
			}
			$sql .= " SELECT t.rowid as id, l.entity, l.label as ref, 1 as paid, (t.amount_capital+t.amount_insurance+t.amount_interest) as total_ht, (t.amount_capital+t.amount_insurance+t.amount_interest) as total_ttc, 0 as total_vat, '".$db->escape($conf->currency)."' as currency, 0 as fk_soc, t.datep as date, t.datep as date_due, 'LoanPayment' as item, '' as thirdparty_name, '' as thirdparty_code, '' as country_code, '' as vatnum, ".PAY_DEBIT." as sens";
			$sql .= " FROM ".MAIN_DB_PREFIX."payment_loan as t LEFT JOIN ".MAIN_DB_PREFIX."loan as l ON l.rowid = t.fk_loan";
			$sql .= " WHERE datep between ".$wheretail;
			$sql .= " AND l.entity IN (".$db->sanitize($entity == 1 ? '0,1' : $entity).')';
		}

		if ($sql) {
			$sql .= $db->order($sortfield, $sortorder);
			//print $sql;

			$resd = $db->query($sql);
			$files = array();
			$link = '';

			if ($resd) {
				$numd = $db->num_rows($resd);

				$tmpinvoice = new Facture($db);
				$tmpinvoicesupplier = new FactureFournisseur($db);
				$tmpdonation = new Don($db);

				$upload_dir = '';
				$i = 0;
				while ($i < $numd) {
					$objd = $db->fetch_object($resd);

					switch ($objd->item) {
						case "Invoice":
							$subdir = '';
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->ref);
							$upload_dir = $conf->facture->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=facture&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "facture";
							break;
						case "SupplierInvoice":
							$tmpinvoicesupplier->fetch($objd->id);
							$subdir = get_exdir($tmpinvoicesupplier->id, 2, 0, 1, $tmpinvoicesupplier, 'invoice_supplier'); // TODO Use first file
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->ref);
							$upload_dir = $conf->fournisseur->facture->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=facture_fournisseur&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "facture_fournisseur";
							break;
						case "ExpenseReport":
							$subdir = '';
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->ref);
							$upload_dir = $conf->expensereport->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=expensereport&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "expensereport";
							break;
						case "SalaryPayment":
							$subdir = '';
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
							$upload_dir = $conf->salaries->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=salaries&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "salaries";
							break;
						case "Donation":
							$tmpdonation->fetch($objp->id);
							$subdir = get_exdir(0, 0, 0, 0, $tmpdonation, 'donation');
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
							$upload_dir = $conf->don->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=don&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "don";
							break;
						case "SocialContributions":
							$subdir = '';
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
							$upload_dir = $conf->tax->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=tax&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "tax";
							break;
						case "VariousPayment":
							$subdir = '';
							$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
							$upload_dir = $conf->bank->dir_output.'/'.$subdir;
							$link = "document.php?modulepart=banque&file=".str_replace('/', '%2F', $subdir).'%2F';
							$modulepart = "banque";
							break;
						case "LoanPayment":
							// Loan payment has no linked file
							$subdir = '';
							$upload_dir = $conf->loan->dir_output.'/'.$subdir;
							$link = "";
							$modulepart = "";
							break;
						default:
							$subdir = '';
							$upload_dir = '';
							$link = '';
							break;
					}

					if (!empty($upload_dir)) {
						$result = true;

						$files = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', '', SORT_ASC, 1);
						//var_dump($upload_dir);
						//var_dump($files);
						if (count($files) < 1) {
							$nofile = array();
							$nofile['id'] = $objd->id;
							$nofile['entity'] = $objd->entity;
							$nofile['date'] = $db->jdate($objd->date);
							$nofile['date_due'] = $db->jdate($objd->date_due);
							$nofile['paid'] = $objd->paid;
							$nofile['amount_ht'] = $objd->total_ht;
							$nofile['amount_ttc'] = $objd->total_ttc;
							$nofile['amount_vat'] = $objd->total_vat;
							$nofile['ref'] = ($objd->ref ? $objd->ref : $objd->id);
							$nofile['fk'] = $objd->fk_soc;
							$nofile['item'] = $objd->item;
							$nofile['thirdparty_name'] = $objd->thirdparty_name;
							$nofile['thirdparty_code'] = $objd->thirdparty_code;
							$nofile['country_code'] = $objd->country_code;
							$nofile['vatnum'] = $objd->vatnum;
							$nofile['sens'] = $objd->sens;

							$filesarray[$nofile['item'].'_'.$nofile['id']] = $nofile;
						} else {
							foreach ($files as $key => $file) {
								$file['id'] = $objd->id;
								$file['entity'] = $objd->entity;
								$file['date'] = $db->jdate($objd->date);
								$file['date_due'] = $db->jdate($objd->date_due);
								$file['paid'] = $objd->paid;
								$file['amount_ht'] = $objd->total_ht;
								$file['amount_ttc'] = $objd->total_ttc;
								$file['amount_vat'] = $objd->total_vat;
								$file['ref'] = ($objd->ref ? $objd->ref : $objd->id);
								$file['fk'] = $objd->fk_soc;
								$file['item'] = $objd->item;
								$file['thirdparty_name'] = $objd->thirdparty_name;
								$file['thirdparty_code'] = $objd->thirdparty_code;
								$file['country_code'] = $objd->country_code;
								$file['vatnum'] = $objd->vatnum;
								$file['sens'] = $objd->sens;

								// Save record into array (only the first time it is found)
								if (empty($filesarray[$file['item'].'_'.$file['id']])) {
									$filesarray[$file['item'].'_'.$file['id']] = $file;
								}

								// Add or concat file
								if (empty($filesarray[$file['item'].'_'.$file['id']]['files'])) {
									$filesarray[$file['item'].'_'.$file['id']]['files'] = array();
								}
								$filesarray[$file['item'].'_'.$file['id']]['files'][] = array(
									'link' => $link.urlencode($file['name']),
									'name'=>$file['name'],
									'ref'=>$file['ref'],
									'fullname' => $file['fullname'],
									'relpath' => '/'.$file['name'],
									'relpathnamelang' => $langs->trans($file['item']).'/'.$file['name'],
									'modulepart' => $modulepart,
									'subdir' => $subdir,
								);
								//var_dump($file['item'].'_'.$file['id']);
								//var_dump($filesarray[$file['item'].'_'.$file['id']]['files']);
							}
						}
					}

					$i++;
				}
			} else {
				dol_print_error($db);
			}

			$db->free($resd);
		} else {
			setEventMessages($langs->trans("ErrorSelectAtLeastOne"), null, 'errors');
			$error++;
		}
	}
}


/*
 *ZIP creation
 */

$dirfortmpfile = ($conf->accounting->dir_temp ? $conf->accounting->dir_temp : $conf->comptabilite->dir_temp);
if (empty($dirfortmpfile)) {
	setEventMessages($langs->trans("ErrorNoAccountingModuleEnabled"), null, 'errors');
	$error++;
}


if ($result && $action == "dl" && !$error) {
	if (!extension_loaded('zip')) {
		setEventMessages('PHPZIPExtentionNotLoaded', null, 'errors');
		exit;
	}

	dol_mkdir($dirfortmpfile);

	$log = $langs->transnoentitiesnoconv("Type");
	if (!empty($conf->multicompany->enabled) && is_object($mc)) {
		$log .= ','.$langs->transnoentitiesnoconv("Entity");
	}
	$log .= ','.$langs->transnoentitiesnoconv("Date");
	$log .= ','.$langs->transnoentitiesnoconv("DateDue");
	$log .= ','.$langs->transnoentitiesnoconv("Ref");
	$log .= ','.$langs->transnoentitiesnoconv("TotalHT");
	$log .= ','.$langs->transnoentitiesnoconv("TotalTTC");
	$log .= ','.$langs->transnoentitiesnoconv("TotalVAT");
	$log .= ','.$langs->transnoentitiesnoconv("Paid");
	$log .= ','.$langs->transnoentitiesnoconv("Document");
	$log .= ','.$langs->transnoentitiesnoconv("ItemID");
	$log .= ','.$langs->transnoentitiesnoconv("ThirdParty");
	$log .= ','.$langs->transnoentitiesnoconv("Code");
	$log .= ','.$langs->transnoentitiesnoconv("Country");
	$log .= ','.$langs->transnoentitiesnoconv("VATIntra");
	$log .= ','.$langs->transnoentitiesnoconv("Sens")."\n";
	$zipname = $dirfortmpfile.'/'.dol_print_date($date_start, 'dayrfc', 'tzuserrel')."-".dol_print_date($date_stop, 'dayrfc', 'tzuserrel').'_export.zip';

	dol_delete_file($zipname);

	$zip = new ZipArchive;
	$res = $zip->open($zipname, ZipArchive::OVERWRITE | ZipArchive::CREATE);
	if ($res) {
		foreach ($filesarray as $key => $file) {
			if (!empty($file['files'])) {
				foreach ($file['files'] as $filecursor) {
					if (file_exists($filecursor["fullname"])) {
						$zip->addFile($filecursor["fullname"], $filecursor["relpathnamelang"]);
					}
				}
			}

			$log .= '"'.$langs->trans($file['item']).'"';
			if (!empty($conf->multicompany->enabled) && is_object($mc)) {
				$log .= ',"'.(empty($arrayofentities[$file['entity']]) ? $file['entity'] : $arrayofentities[$file['entity']]).'"';
			}
			$log .= ','.dol_print_date($file['date'], 'dayrfc');
			$log .= ','.dol_print_date($file['date_due'], 'dayrfc');
			$log .= ',"'.$file['ref'].'"';
			$log .= ','.$file['amount_ht'];
			$log .= ','.$file['amount_ttc'];
			$log .= ','.$file['amount_vat'];
			$log .= ','.$file['paid'];
			$log .= ',"'.$file["name"].'"';
			$log .= ','.$file['fk'];
			$log .= ',"'.$file['thirdparty_name'].'"';
			$log .= ',"'.$file['thirdparty_code'].'"';
			$log .= ',"'.$file['country_code'].'"';
			$log .= ',"'.$file['vatnum'].'"';
			$log .= ',"'.$file['sens'].'"';
			$log .= "\n";
		}
		$zip->addFromString('transactions.csv', $log);
		$zip->close();

		///Then download the zipped file.
		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.basename($zipname));
		header('Content-Length: '.filesize($zipname));
		readfile($zipname);

		dol_delete_file($zipname);

		exit();
	} else {
		setEventMessages($langs->trans("FailedToOpenFile", $zipname), null, 'errors');
	}
}


/*
 * View
 */

$form = new form($db);
$formfile = new FormFile($db);
$userstatic = new User($db);
$invoice = new Facture($db);
$supplier_invoice = new FactureFournisseur($db);
$expensereport = new ExpenseReport($db);
$don = new Don($db);
$salary_payment = new PaymentSalary($db);
$charge_sociales = new ChargeSociales($db);
$various_payment = new PaymentVarious($db);
$payment_loan = new PaymentLoan($db);

$title = $langs->trans("ComptaFiles").' - '.$langs->trans("List");
$help_url = '';

llxHeader('', $title, $help_url);

$h = 0;
$head = array();
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("AccountantFiles");
$head[$h][2] = 'AccountancyFiles';

print dol_get_fiche_head($head, 'AccountancyFiles');


print '<form name="searchfiles" action="?action=searchfiles" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<span class="opacitymedium">'.$langs->trans("ExportAccountingSourceDocHelp");
if (!empty($conf->accounting->enabled)) {
	print ' '.$langs->trans("ExportAccountingSourceDocHelp2", $langs->transnoentitiesnoconv("Accounting"), $langs->transnoentitiesnoconv("Journals"));
}
print '</span><br>';
print '<br>';

print $langs->trans("ReportPeriod").': ';
print $form->selectDate($date_start, 'date_start', 0, 0, 0, "", 1, 1, 0, '', '', '', '', 1, '', '', 'tzuserrel');
print ' - ';
print $form->selectDate($date_stop, 'date_stop', 0, 0, 0, "", 1, 1, 0, '', '', '', '', 1, '', '', 'tzuserrel');
print "\n";

// Export is for current company only
if (!empty($conf->multicompany->enabled) && is_object($mc)) {
	$mc->getInfo($conf->entity);
	print '<span class="marginleftonly marginrightonly'.(empty($conf->global->MULTICOMPANY_ALLOW_EXPORT_ACCOUNTING_DOC_FOR_ALL_ENTITIES) ? ' opacitymedium' : '').'">('.$langs->trans("Entity").' : ';
	print "<td>";
	if (!empty($conf->global->MULTICOMPANY_ALLOW_EXPORT_ACCOUNTING_DOC_FOR_ALL_ENTITIES)) {
		print $mc->select_entities(GETPOSTISSET('search_entity') ? GETPOST('search_entity', 'int') : $mc->id, 'search_entity', '', false, false, false, false, true);
	} else {
		print $mc->label;
	}
	print "</td>";
	print ")</span>\n";
}

print '<br>';

foreach ($listofchoices as $choice => $val) {
	if (empty($val['enabled'])) {
		continue;		// list not qualified
	}
	$disabled = '';
	if (empty($val['perms'])) {
		$disabled = ' disabled';
	}
	$checked = (((!GETPOSTISSET('search') && $action != 'searchfiles') || GETPOST($choice)) ? ' checked="checked"' : '');
	print '<div class="paddingleft inline-block marginrightonly"><input type="checkbox" id="'.$choice.'" name="'.$choice.'" value="1"'.$checked.$disabled.'> <label for="'.$choice.'">'.$langs->trans($val['label']).'</label></div>';
}

print '<input class="button" type="submit" name="search" value="'.$langs->trans("Search").'">';

print '</form>'."\n";

print dol_get_fiche_end();

if (!empty($date_start) && !empty($date_stop)) {
	$param = 'action=searchfiles';
	$param .= '&date_startday='.GETPOST('date_startday', 'int');
	$param .= '&date_startmonth='.GETPOST('date_startmonth', 'int');
	$param .= '&date_startyear='.GETPOST('date_startyear', 'int');
	$param .= '&date_stopday='.GETPOST('date_stopday', 'int');
	$param .= '&date_stopmonth='.GETPOST('date_stopmonth', 'int');
	$param .= '&date_stopyear='.GETPOST('date_stopyear', 'int');
	foreach ($listofchoices as $choice => $val) {
		$param .= '&'.$choice.'='.(GETPOST($choice, 'int') ? 1 : 0);
	}
	print '<form name="dl" action="'.$_SERVER["PHP_SELF"].'?action=dl" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.currentToken().'">';

	echo dol_print_date($date_start, 'day', 'tzuserrel')." - ".dol_print_date($date_stop, 'day', 'tzuserrel');

	print '<input type="hidden" name="date_startday" value="'.GETPOST('date_startday', 'int').'" />';
	print '<input type="hidden" name="date_startmonth" value="'.GETPOST('date_startmonth', 'int').'" />';
	print '<input type="hidden" name="date_startyear" value="'.GETPOST('date_startyear', 'int').'" />';
	print '<input type="hidden" name="date_stopday" value="'.GETPOST('date_stopday', 'int').'" />';
	print '<input type="hidden" name="date_stopmonth" value="'.GETPOST('date_stopmonth', 'int').'" />';
	print '<input type="hidden" name="date_stopyear" value="'.GETPOST('date_stopyear', 'int').'" />';
	foreach ($listofchoices as $choice => $val) {
		print '<input type="hidden" name="'.$choice.'" value="'.GETPOST($choice).'">';
	}

	print '<input class="butAction butDownload" type="submit" value="'.$langs->trans("Download").'" />';
	print '</form>'."\n";

	print '<br>';

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], "item", "", $param, '', $sortfield, $sortorder, 'nowrap ');
	print_liste_field_titre($arrayfields['date']['label'], $_SERVER["PHP_SELF"], "date", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	print_liste_field_titre($arrayfields['date_due']['label'], $_SERVER["PHP_SELF"], "date_due", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	print_liste_field_titre($arrayfields['ref']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'nowraponall ');
	print '<td>'.$langs->trans("Document").'</td>';
	print '<td>'.$langs->trans("Paid").'</td>';
	print '<td align="right">'.$langs->trans("TotalHT").($conf->multicurrency->enabled ? ' ('.$conf->currency.')' : '').'</td>';
	print '<td align="right">'.$langs->trans("TotalTTC").($conf->multicurrency->enabled ? ' ('.$conf->currency.')' : '').'</td>';
	print '<td align="right">'.$langs->trans("TotalVAT").($conf->multicurrency->enabled ? ' ('.$conf->currency.')' : '').'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td class="center">'.$langs->trans("Code").'</td>';
	print '<td class="center">'.$langs->trans("Country").'</td>';
	print '<td class="center">'.$langs->trans("VATIntra").'</td>';
	if (!empty($conf->multicurrency->enabled)) {
		print '<td class="center">'.$langs->trans("Currency").'</td>';
	}
	print '</tr>';
	if ($result) {
		$TData = dol_sort_array($filesarray, $sortfield, $sortorder);

		if (empty($TData)) {
			print '<tr class="oddeven"><td colspan="7">'.$langs->trans("NoItem").'</td>';
			if (!empty($conf->multicurrency->enabled)) {
				print '<td></td>';
			}
			print '</tr>';
		} else {
			// Sort array by date ASC to calculate balance

			$totalET_debit = 0;
			$totalIT_debit = 0;
			$totalVAT_debit = 0;
			$totalET_credit = 0;
			$totalIT_credit = 0;
			$totalVAT_credit = 0;

			// Display array
			foreach ($TData as $data) {
				$html_class = '';
				//if (!empty($data['fk_facture'])) $html_class = 'facid-'.$data['fk_facture'];
				//elseif (!empty($data['fk_paiement'])) $html_class = 'payid-'.$data['fk_paiement'];
				print '<tr class="oddeven '.$html_class.'">';

				// Type
				print '<td>'.$langs->trans($data['item']).'</td>';

				// Date
				print '<td class="center">';
				print dol_print_date($data['date'], 'day');
				print "</td>\n";

				// Date due
				print '<td class="center">';
				print dol_print_date($data['date_due'], 'day');
				print "</td>\n";

				// Ref
				print '<td class="nowraponall">';

				if ($data['item'] == 'Invoice') {
					$invoice->id = $data['id'];
					$invoice->ref = $data['ref'];
					$invoice->total_ht = $data['amount_ht'];
					$invoice->total_ttc = $data['amount_ttc'];
					$invoice->total_tva = $data['amount_vat'];
					$invoice->multicurrency_code = $data['currency'];
					print $invoice->getNomUrl(1, '', 0, 0, '', 0, 0, 0);
				} elseif ($data['item'] == 'SupplierInvoice') {
					$supplier_invoice->id = $data['id'];
					$supplier_invoice->ref = $data['ref'];
					$supplier_invoice->total_ht = $data['amount_ht'];
					$supplier_invoice->total_ttc = $data['amount_ttc'];
					$supplier_invoice->total_tva = $data['amount_vat'];
					$supplier_invoice->multicurrency_code = $data['currency'];
					print $supplier_invoice->getNomUrl(1, '', 0, 0, '', 0, 0, 0);
				} elseif ($data['item'] == 'ExpenseReport') {
					$expensereport->id = $data['id'];
					$expensereport->ref = $data['ref'];
					print $expensereport->getNomUrl(1, 0, 0, '', 0, 0);
				} elseif ($data['item'] == 'SalaryPayment') {
					$salary_payment->id = $data['id'];
					$salary_payment->ref = $data['ref'];
					print $salary_payment->getNomUrl(1);
				} elseif ($data['item'] == 'Donation') {
					$don->id = $data['id'];
					$don->ref = $data['ref'];
					print $don->getNomUrl(1, 0, '', 0);
				} elseif ($data['item'] == 'SocialContributions') {
					$charge_sociales->id = $data['id'];
					$charge_sociales->ref = $data['ref'];
					print $charge_sociales->getNomUrl(1, 0, 0, 0, 0);
				} elseif ($data['item'] == 'VariousPayment') {
					$various_payment->id = $data['id'];
					$various_payment->ref = $data['ref'];
					print $various_payment->getNomUrl(1, '', 0, 0);
				} elseif ($data['item'] == 'LoanPayment') {
					$payment_loan->id = $data['id'];
					$payment_loan->ref = $data['ref'];
					print $payment_loan->getNomUrl(1, 0, 0, '', 0);
				} else {
					print $data['ref'];
				}
				print '</td>';

				// File link
				print '<td>';
				if (!empty($data['files'])) {
					foreach ($data['files'] as $id => $filecursor) {
						print '<a href='.DOL_URL_ROOT.'/'.$filecursor['link'].' target="_blank">'.($filecursor['name'] ? $filecursor['name'] : $filecursor['ref']).'</a>&nbsp;'.$formfile->showPreview($filecursor, $filecursor['modulepart'], $filecursor['subdir'].'/'.$filecursor['name']).'<br>';
					}
				}
				print "</td>\n";

				// Paid
				print '<td aling="left">'.$data['paid'].'</td>';

				// Total ET
				print '<td align="right">'.price(price2num($data['sens'] ? $data['amount_ht'] : -$data['amount_ht'], 'MT'))."</td>\n";
				// Total IT
				print '<td align="right">'.price(price2num($data['sens'] ? $data['amount_ttc'] : -$data['amount_ttc'], 'MT'))."</td>\n";
				// Total VAT
				print '<td align="right">'.price(price2num($data['sens'] ? $data['amount_vat'] : -$data['amount_vat'], 'MT'))."</td>\n";

				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($data['thirdparty_name']).'">'.dol_escape_htmltag($data['thirdparty_name'])."</td>\n";

				print '<td class="center">'.$data['thirdparty_code']."</td>\n";

				print '<td class="center">'.$data['country_code']."</td>\n";

				print '<td class="tdoverflowmax150 right" title="'.dol_escape_htmltag($data['vatnum']).'">'.dol_escape_htmltag($data['vatnum'])."</td>\n";

				if ($data['sens']) {
					$totalET_credit += $data['amount_ht'];
					$totalIT_credit += $data['amount_ttc'];
					$totalVAT_credit += $data['amount_vat'];
				} else {
					$totalET_debit -= $data['amount_ht'];
					$totalIT_debit -= $data['amount_ttc'];
					$totalVAT_debit -= $data['amount_vat'];
				}

				if (!empty($conf->multicurrency->enabled)) {
					print '<td class="center">'.$data['currency']."</td>\n";
				}

				print "</tr>\n";
			}

			// Total credits
			print '<tr class="liste_total">';
			print '<td colspan="6" class="right">'.$langs->trans('Total').' '.$langs->trans('Income').'</td>';
			print '<td align="right">'.price(price2num($totalET_credit, 'MT')).'</td>';
			print '<td align="right">'.price(price2num($totalIT_credit, 'MT')).'</td>';
			print '<td align="right">'.price(price2num($totalVAT_credit, 'MT')).'</td>';
			print '<td colspan="4"></td>';
			if (!empty($conf->multicurrency->enabled)) {
				print '<td></td>';
			}
			print "</tr>\n";
			// Total debits
			print '<tr class="liste_total">';
			print '<td colspan="6" class="right">'.$langs->trans('Total').' '.$langs->trans('Outcome').'</td>';
			print '<td align="right">'.price(price2num($totalET_debit, 'MT')).'</td>';
			print '<td align="right">'.price(price2num($totalIT_debit, 'MT')).'</td>';
			print '<td align="right">'.price(price2num($totalVAT_debit, 'MT')).'</td>';
			print '<td colspan="4"></td>';
			if (!empty($conf->multicurrency->enabled)) {
				print '<td></td>';
			}
			print "</tr>\n";
			// Balance
			print '<tr class="liste_total">';
			print '<td colspan="6" class="right">'.$langs->trans('Total').'</td>';
			print '<td align="right">'.price(price2num($totalET_credit + $totalET_debit, 'MT')).'</td>';
			print '<td align="right">'.price(price2num($totalIT_credit + $totalIT_debit, 'MT')).'</td>';
			print '<td align="right">'.price(price2num($totalVAT_credit + $totalVAT_debit, 'MT')).'</td>';
			print '<td colspan="4"></td>';
			if (!empty($conf->multicurrency->enabled)) {
				print '<td></td>';
			}
			print "</tr>\n";
		}
	}
	print "</table>";
	print '</div>';
}


llxFooter();
$db->close();
