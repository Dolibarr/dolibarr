<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/compta/accounting-files.php
 *  \ingroup    compta
 *  \brief      Page to show portoflio and files of a thirdparty and download it
 */

if ((array_key_exists('action', $_GET) && $_GET['action'] == 'dl') || (array_key_exists('action', $_POST) && $_POST['action'] == 'dl')) {	// To not replace token when downloading file
	if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');
}

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

$langs->loadLangs(array("accountancy", "bills", "companies", "salaries", "compta"));

$date_start = GETPOST('date_start', 'alpha');
$date_startDay = GETPOST('date_startday', 'int');
$date_startMonth = GETPOST('date_startmonth', 'int');
$date_startYear = GETPOST('date_startyear', 'int');
$date_start = ($date_startDay) ?dol_mktime(0, 0, 0, $date_startMonth, $date_startDay, $date_startYear) : strtotime($date_start);
$date_stop = GETPOST('date_stop', 'alpha');
$date_stopDay = GETPOST('date_stopday', 'int');
$date_stopMonth = GETPOST('date_stopmonth', 'int');
$date_stopYear = GETPOST('date_stopyear', 'int');
//FIXME doldate
$date_stop = ($date_stopDay) ?dol_mktime(23, 59, 59, $date_stopMonth, $date_stopDay, $date_stopYear) : strtotime($date_stop);
$action = GETPOST('action', 'alpha');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('comptafileslist', 'globallist'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "date,item"; // Set here default search field
if (!$sortorder) $sortorder = "DESC";


$arrayfields = array(
    'type'=>array('label'=>"Type", 'checked'=>1),
    'date'=>array('label'=>"Date", 'checked'=>1),
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
	if (empty($entity) && ! empty($conf->global->MULTICOMPANY_ALLOW_EXPORT_ACCOUNTING_DOC_FOR_ALL_ENTITIES)) {
		$entity = '0,'.join(',', array_keys($arrayofentities));
	}
}
if (empty($entity)) $entity = $conf->entity;

$error = 0;



/*
 * Actions
 */


//$parameters = array('socid' => $id);
//$reshook = $hookmanager->executeHooks('doActions', $parameters, $object); // Note that $object may have been modified by some hooks
//if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$filesarray = array();
$result = false;
if (($action == 'searchfiles' || $action == 'dl')) {
	if (empty($date_start))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateStart")), null, 'errors');
		$error++;
	}
	if (empty($date_stop))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
		$error++;
	}

	if (!$error)
	{
		$wheretail = " '".$db->idate($date_start)."' AND '".$db->idate($date_stop)."'";

		// Customer invoices
		$sql = "SELECT t.rowid as id, t.entity, t.ref, t.paye as paid, total as total_ht, total_ttc, tva as total_vat, fk_soc, t.datef as date, 'Invoice' as item, s.nom as thirdparty_name, s.code_client as thirdparty_code, c.code as country_code, s.tva_intra as vatnum";
	    $sql .= " FROM ".MAIN_DB_PREFIX."facture as t LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = s.fk_pays";
	    $sql .= " WHERE datef between ".$wheretail;
	    $sql .= " AND t.entity IN (".($entity == 1 ? '0,1' : $entity).')';
	    $sql .= " AND t.fk_statut <> ".Facture::STATUS_DRAFT;
	    $sql .= " UNION ALL";
	    // Vendor invoices
	    $sql .= " SELECT t.rowid as id, t.entity, t.ref, paye as paid, total_ht, total_ttc, total_tva as total_vat, fk_soc, datef as date, 'SupplierInvoice' as item, s.nom as thirdparty_name, s.code_fournisseur as thirdparty_code, c.code as country_code, s.tva_intra as vatnum";
	    $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as t LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = s.fk_pays";
	    $sql .= " WHERE datef between ".$wheretail;
	    $sql .= " AND t.entity IN (".($entity == 1 ? '0,1' : $entity).')';
	    $sql .= " AND t.fk_statut <> ".FactureFournisseur::STATUS_DRAFT;
	    $sql .= " UNION ALL";
	    // Expense reports
	    $sql .= " SELECT t.rowid as id, t.entity, t.ref, paid, total_ht, total_ttc, total_tva as total_vat, fk_user_author as fk_soc, date_fin as date, 'ExpenseReport' as item, CONCAT(CONCAT(u.lastname, ' '), u.firstname) as thirdparty_name, '' as thirdparty_code, c.code as country_code, '' as vatnum";
	    $sql .= " FROM ".MAIN_DB_PREFIX."expensereport as t LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.fk_user_author LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = u.fk_country";
	    $sql .= " WHERE date_fin between  ".$wheretail;
	    $sql .= " AND t.entity IN (".($entity == 1 ? '0,1' : $entity).')';
	    $sql .= " AND t.fk_statut <> ".ExpenseReport::STATUS_DRAFT;
	    $sql .= " UNION ALL";
	    // Donations
	    $sql .= " SELECT t.rowid as id, t.entity, t.ref, paid, amount as total_ht, amount as total_ttc, 0 as total_vat, 0 as fk_soc, datedon as date, 'Donation' as item, t.societe as thirdparty_name, '' as thirdparty_code, c.code as country_code, '' as vatnum";
	    $sql .= " FROM ".MAIN_DB_PREFIX."don as t LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = t.fk_country";
	    $sql .= " WHERE datedon between ".$wheretail;
	    $sql .= " AND t.entity IN (".($entity == 1 ? '0,1' : $entity).')';
	    $sql .= " AND t.fk_statut <> ".Don::STATUS_DRAFT;
	    $sql .= " UNION ALL";
	    // Paiements of salaries
	    $sql .= " SELECT t.rowid as id, t.entity, t.ref as ref, 1 as paid, amount as total_ht, amount as total_ttc, 0 as total_vat, t.fk_user as fk_soc, datep as date, 'SalaryPayment' as item, CONCAT(CONCAT(u.lastname, ' '), u.firstname)  as thirdparty_name, '' as thirdparty_code, c.code as country_code, '' as vatnum";
	    $sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as t LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.fk_user LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = u.fk_country";
	    $sql .= " WHERE datep between ".$wheretail;
	    $sql .= " AND t.entity IN (".($entity == 1 ? '0,1' : $entity).')';
	    //$sql.=" AND fk_statut <> ".PaymentSalary::STATUS_DRAFT;
	    $sql .= " UNION ALL";
	    // Social contributions
	    $sql .= " SELECT t.rowid as id, t.entity, t.libelle as ref, paye as paid, amount as total_ht, amount as total_ttc, 0 as total_tva, 0 as fk_soc, date_creation as date, 'SocialContributions' as item, '' as thirdparty_name, '' as thirdparty_code, '' as country_code, '' as vatnum";
	    $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as t";
	    $sql .= " WHERE date_creation between ".$wheretail;
	    $sql .= " AND t.entity IN (".($entity == 1 ? '0,1' : $entity).')';
	    //$sql.=" AND fk_statut <> ".ChargeSociales::STATUS_DRAFT;
	    $sql .= $db->order($sortfield, $sortorder);
		//print $sql;

	    $resd = $db->query($sql);
	    $files = array();
	    $link = '';

	    if ($resd)
	    {
	        $numd = $db->num_rows($resd);

	        $tmpinvoice = new Facture($db);
	        $tmpinvoicesupplier = new FactureFournisseur($db);
	        $tmpdonation = new Don($db);

	        $upload_dir = '';
	        $i = 0;
	        while ($i < $numd)
	        {
	            $objd = $db->fetch_object($resd);

	            switch ($objd->item)
	            {
	                case "Invoice":
	                	$subdir = '';
	                	$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->ref);
	                	$upload_dir = $conf->facture->dir_output.'/'.$subdir;
	                	$link = "document.php?modulepart=facture&file=".str_replace('/', '%2F', $subdir).'%2F';
	                	break;
	                case "SupplierInvoice":
	                	$tmpinvoicesupplier->fetch($objd->id);
	                	$subdir = get_exdir($tmpinvoicesupplier->id, 2, 0, 1, $tmpinvoicesupplier, 'invoice_supplier'); // TODO Use first file
	                	$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->ref);
	                	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.$subdir;
	                	$link = "document.php?modulepart=facture_fournisseur&file=".str_replace('/', '%2F', $subdir).'%2F';
	                	break;
	                case "ExpenseReport":
	                	$subdir = '';
	                	$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->ref);
	                	$upload_dir = $conf->expensereport->dir_output.'/'.$subdir;
	                	$link = "document.php?modulepart=expensereport&file=".str_replace('/', '%2F', $subdir).'%2F';
	                	break;
	                case "SalaryPayment":
	                	$subdir = '';
	                	$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
	                	$upload_dir = $conf->salaries->dir_output.'/'.$subdir;
	                	$link = "document.php?modulepart=salaries&file=".str_replace('/', '%2F', $subdir).'%2F';
	                	break;
	                case "Donation":
	                	$tmpdonation->fetch($objp->id);
	                	$subdir = get_exdir(0, 0, 0, 0, $tmpdonation, 'donation');
	                	$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
	                	$upload_dir = $conf->don->dir_output.'/'.$subdir;
	                	$link = "document.php?modulepart=don&file=".str_replace('/', '%2F', $subdir).'%2F';
	                	break;
	                case "SocialContributions":
	                	$subdir = '';
	                	$subdir .= ($subdir ? '/' : '').dol_sanitizeFileName($objd->id);
	                	$upload_dir = $conf->tax->dir_output.'/'.$subdir;
	                	$link = "document.php?modulepart=tax&file=".str_replace('/', '%2F', $subdir).'%2F';
	                	break;
	                default:
	                    $subdir = '';
	                    $upload_dir = '';
	                    $link = '';
	                    break;
	            }

	            if (!empty($upload_dir))
	            {
	                $result = true;

	                $files = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', '', SORT_ASC, 1);
	                //var_dump($upload_dir);
	                //var_dump($files);

	                if (count($files) < 1)
	                {
	                	$nofile = array();
	                	$nofile['id'] = $objd->id;
	                	$nofile['entity'] = $objd->entity;
	                	$nofile['date'] = $db->idate($objd->date);
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

	                    $filesarray[$nofile['item'].'_'.$nofile['id']] = $nofile;
	                }
	                else
	                {
	                    foreach ($files as $key => $file)
	                    {
	                    	$file['id'] = $objd->id;
	                    	$file['entity'] = $objd->entity;
	                    	$file['date'] = $db->idate($objd->date);
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

	                        // Save record into array (only the first time it is found)
	                        if (empty($filesarray[$file['item'].'_'.$file['id']])) {
	                        	$filesarray[$file['item'].'_'.$file['id']] = $file;
	                        }

	                        // Add or concat file
	                        if (empty($filesarray[$file['item'].'_'.$file['id']]['files'])) {
	                        	$filesarray[$file['item'].'_'.$file['id']]['files'] = array();
	                        }
	                        $filesarray[$file['item'].'_'.$file['id']]['files'][] = array('link' => $link.$file['name'], 'name'=>$file['name'], 'ref'=>$file['ref'], 'fullname' => $file['fullname'], 'relpathnamelang' => $langs->trans($file['item']).'/'.$file['name']);
	                        //var_dump($file['item'].'_'.$file['id']);
	                        //var_dump($filesarray[$file['item'].'_'.$file['id']]['files']);
	                    }
	                }
	            }

	            $i++;
	        }
	    }
	    else
	    {
	        dol_print_error($db);
	    }

	    $db->free($resd);
	}
}


/*
 *ZIP creation
 */

$dirfortmpfile = ($conf->accounting->dir_temp ? $conf->accounting->dir_temp : $conf->comptabilite->dir_temp);
if (empty($dirfortmpfile))
{
	setEventMessages($langs->trans("ErrorNoAccountingModuleEnabled"), null, 'errors');
	$error++;
}


if ($result && $action == "dl" && !$error)
{
	if (!extension_loaded('zip'))
	{
		setEventMessages('PHPZIPExtentionNotLoaded', null, 'errors');
		exit;
	}

    dol_mkdir($dirfortmpfile);

    $log = $langs->transnoentitiesnoconv("Type");
    if (!empty($conf->multicompany->enabled) && is_object($mc))
    {
    	$log .= ','.$langs->transnoentitiesnoconv("Entity");
    }
    $log .= ','.$langs->transnoentitiesnoconv("Date");
    $log .= ','.$langs->transnoentitiesnoconv("Ref");
    $log .= ','.$langs->transnoentitiesnoconv("TotalHT");
    $log .= ','.$langs->transnoentitiesnoconv("TotalTTC");
    $log .= ','.$langs->transnoentitiesnoconv("TotalVAT");
    $log .= ','.$langs->transnoentitiesnoconv("Paid");
    $log .= ',filename,item_id';
    $log .= ','.$langs->transnoentitiesnoconv("ThirdParty");
    $log .= ','.$langs->transnoentitiesnoconv("Code");
    $log .= ','.$langs->transnoentitiesnoconv("Country");
    $log .= ','.$langs->transnoentitiesnoconv("VATIntra")."\n";
    $zipname = $dirfortmpfile.'/'.dol_print_date($date_start, 'dayrfc')."-".dol_print_date($date_stop, 'dayrfc').'_export.zip';

    dol_delete_file($zipname);

    $zip = new ZipArchive;
    $res = $zip->open($zipname, ZipArchive::OVERWRITE | ZipArchive::CREATE);
    if ($res)
    {
        foreach ($filesarray as $key => $file)
        {
        	foreach($file['files'] as $filecursor) {
        		if (file_exists($filecursor["fullname"])) {
        			$zip->addFile($filecursor["fullname"], $filecursor["relpathnamelang"]);
        		}
        	}

            $log .= $file['item'];
            if (!empty($conf->multicompany->enabled) && is_object($mc))
            {
            	$log .= ','.(empty($arrayofentities[$file['entity']]) ? $file['entity'] : $arrayofentities[$file['entity']]);
            }
            $log .= ','.dol_print_date($file['date'], 'dayrfc');
            $log .= ','.$file['ref'];
            $log .= ','.$file['amount_ht'];
            $log .= ','.$file['amount_ttc'];
            $log .= ','.$file['amount_vat'];
            $log .= ','.$file['paid'];
            $log .= ','.$file["name"];
            $log .= ','.$file['fk'];
            $log .= ','.$file['thirdparty_name'];
            $log .= ','.$file['thirdparty_code'];
            $log .= ','.$file['country_code'];
            $log .= ',"'.$file['vatnum'].'"'."\n";
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
    }
    else
    {
    	setEventMessages($langs->trans("FailedToOpenFile", $zipname), null, 'errors');
    }
}


/*
 * View
 */

$form = new Form($db);
$userstatic = new User($db);

$title = $langs->trans("ComptaFiles").' - '.$langs->trans("List");
$help_url = '';

llxHeader('', $title, $help_url);

$h = 0;
$head = array();
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("AccountantFiles");
$head[$h][2] = 'AccountancyFiles';

dol_fiche_head($head, 'AccountancyFiles');


print '<form name="searchfiles" action="?action=searchfiles" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';

print $langs->trans("ReportPeriod").': '.$form->selectDate($date_start, 'date_start', 0, 0, 0, "", 1, 1, 0);
print ' - '.$form->selectDate($date_stop, 'date_stop', 0, 0, 0, "", 1, 1, 0)."\n</a>";

// Export is for current company only
if (!empty($conf->multicompany->enabled) && is_object($mc))
{
	$mc->getInfo($conf->entity);
	print '<span class="marginleftonly marginrightonly">('.$langs->trans("Entity").' : ';
	print "<td>";
	if (! empty($conf->global->MULTICOMPANY_ALLOW_EXPORT_ACCOUNTING_DOC_FOR_ALL_ENTITIES)) {
		print $mc->select_entities(GETPOSTISSET('search_entity') ? GETPOST('search_entity', 'int') : $mc->id, 'search_entity', '', false, false, false, false, true);
	} else {
		print $mc->label;
	}
	print "</td>";
	print ")</span>\n";
}

print '<input class="button" type="submit" name="search" value="'.$langs->trans("Search").'">';

print '</form>'."\n";

dol_fiche_end();

if (!empty($date_start) && !empty($date_stop))
{
    $param = 'action=searchfiles';
    $param .= '&date_startday='.GETPOST('date_startday', 'int');
    $param .= '&date_startmonth='.GETPOST('date_startmonth', 'int');
    $param .= '&date_startyear='.GETPOST('date_startyear', 'int');
    $param .= '&date_stopday='.GETPOST('date_stopday', 'int');
    $param .= '&date_stopmonth='.GETPOST('date_stopmonth', 'int');
    $param .= '&date_stopyear='.GETPOST('date_stopyear', 'int');

    print '<form name="dl" action="?action=dl" method="POST" >'."\n";
    print '<input type="hidden" name="token" value="'.currentToken().'">';

    echo dol_print_date($date_start, 'day')." - ".dol_print_date($date_stop, 'day');

    print '<input type="hidden" name="date_start" value="'.dol_print_date($date_start, 'dayxcard').'" />';
    print '<input type="hidden" name="date_stop"  value="'.dol_print_date($date_stop, 'dayxcard').'" />';

    //print   '<input type="hidden" name="date_stopDay"  value="'.dol_print_date($date_stop, '%d').'" />';
    //print   '<input type="hidden" name="date_stopMonth"  value="'.dol_print_date($date_stop, '%m').'" />';
    //print   '<input type="hidden" name="date_stopYear"  value="'.dol_print_date($date_stop, '%Y').'" />';

    //print   '<input type="hidden" name="date_startDay"  value="'.dol_print_date($date_start, '%d').'" />';
    //print   '<input type="hidden" name="date_startMonth"  value="'.dol_print_date($date_start, '%m').'" />';
    //print   '<input type="hidden" name="date_startYear"  value="'.dol_print_date($date_start, '%m').'" />';

    print '<input class="butAction" type="submit" value="'.$langs->trans("Download").'" />';
    print '</form>'."\n";

    print '<br>';

    print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], "item", "", $param, '', $sortfield, $sortorder, 'nowrap ');
    print_liste_field_titre($arrayfields['date']['label'], $_SERVER["PHP_SELF"], "date", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
    print '<td>'.$langs->trans("Ref").'</td>';
    print '<td>'.$langs->trans("Document").'</td>';
    print '<td>'.$langs->trans("Paid").'</td>';
    print '<td align="right">'.$langs->trans("TotalHT").'</td>';
    print '<td align="right">'.$langs->trans("TotalTTC").'</td>';
    print '<td align="right">'.$langs->trans("TotalVAT").'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td class="center">'.$langs->trans("Code").'</td>';
	print '<td class="center">'.$langs->trans("Country").'</td>';
    print '<td class="center">'.$langs->trans("VATIntra").'</td>';
    print '</tr>';
    if ($result)
    {
        $TData = dol_sort_array($filesarray, $sortfield, $sortorder);

        if (empty($TData))
        {
            print '<tr class="oddeven"><td colspan="7">'.$langs->trans("NoItem").'</td></tr>';
        }
        else
        {
            // Sort array by date ASC to calculate balance

            $totalET = 0;
            $totalIT = 0;
            $totalVAT = 0;
            $totalDebit = 0;
            $totalCredit = 0;

            // Display array
            foreach ($TData as $data)
            {
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

                // Ref
                print '<td aling="left">'.$data['ref'].'</td>';

                // File link
                print '<td>';
                if (! empty($data['files']))
                {
                	foreach($data['files'] as $filecursor) {
                		print '<a href='.DOL_URL_ROOT.'/'.$filecursor['link'].' target="_blank">'.($filecursor['name'] ? $filecursor['name'] : $filecursor['ref']).'</a><br>';
                	}
                }
                print "</td>\n";

                // Paid
                print '<td aling="left">'.$data['paid'].'</td>';

                // Total ET
                print '<td align="right">'.price($data['amount_ht'])."</td>\n";
                // Total IT
                print '<td align="right">'.price($data['amount_ttc'])."</td>\n";
                // Total VAT
                print '<td align="right">'.price($data['amount_vat'])."</td>\n";

                print '<td>'.$data['thirdparty_name']."</td>\n";

                print '<td class="center">'.$data['thirdparty_code']."</td>\n";

                print '<td class="center">'.$data['country_code']."</td>\n";

                print '<td align="right">'.$data['vatnum']."</td>\n";

                // Debit
                //print '<td align="right">'.(($data['amount_ttc'] > 0) ? price(abs($data['amount_ttc'])) : '')."</td>\n";
                // Credit
                //print '<td align="right">'.(($data['amount_ttc'] > 0) ? '' : price(abs($data['amount_ttc'])))."</td>\n";

                $totalET += $data['amount_ht'];
                $totalIT += $data['amount_ttc'];
                $totalVAT += $data['amount_vat'];

                $totalDebit += ($data['amount_ttc'] > 0) ? abs($data['amount_ttc']) : 0;
                $totalCredit += ($data['amount_ttc'] > 0) ? 0 : abs($data['amount_ttc']);

                // Balance
                //print '<td align="right">'.price($data['balance'])."</td>\n";

                print "</tr>\n";
            }

            print '<tr class="liste_total">';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '<td align="right">'.price(price2num($totalET, 'MT')).'</td>';
            print '<td align="right">'.price(price2num($totalIT, 'MT')).'</td>';
            print '<td align="right">'.price(price2num($totalVAT, 'MT')).'</td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            /*print '<td align="right">'.price($totalDebit).'</td>';
            print '<td align="right">'.price($totalCredit).'</td>';
            print '<td align="right">'.price(price2num($totalDebit - $totalCredit, 'MT')).'</td>';
			*/
            print "</tr>\n";
        }
    }
    print "</table>";
    print '</div>';
}


llxFooter();
$db->close();
