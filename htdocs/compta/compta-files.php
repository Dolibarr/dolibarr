<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 *  \file       htdocs/compta/compta-files.php
 *  \ingroup    compta
 *  \brief      Page to show portoflio and files of a thirdparty and download it
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

$langs->loadLangs(array("accountancy", "bills", "companies"));

$date_start =GETPOST('date_start', 'alpha');
$date_startDay= GETPOST('date_startday', 'int');
$date_startMonth= GETPOST('date_startmonth', 'int');
$date_startYear= GETPOST('date_startyear', 'int');
$date_start=($date_startDay)?dol_mktime(0, 0, 0, $date_startMonth, $date_startDay, $date_startYear):strtotime($date_start);
$date_stop =GETPOST('date_stop', 'alpha');
$date_stopDay= GETPOST('date_stopday', 'int');
$date_stopMonth= GETPOST('date_stopmonth', 'int');
$date_stopYear= GETPOST('date_stopyear', 'int');
//FIXME doldate
$date_stop=($date_stopDay)?dol_mktime(0, 0, 0, $date_stopMonth, $date_stopDay, $date_stopYear):strtotime($date_stop);
$action =GETPOST('action', 'alpha');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('comptafileslist', 'globallist'));

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="date,item"; // Set here default search field
if (! $sortorder) $sortorder="DESC";


$arrayfields=array(
    'date'=>array('label'=>"Date", 'checked'=>1),
    //...
);

// Security check
if (empty($conf->comptabilite->enabled) && empty($conf->accounting->enabled)) {
    accessforbidden();
}
if ($user->societe_id > 0)
    accessforbidden();



/*
 * Actions
 */

$entity = GETPOST('entity', 'int')?GETPOST('entity', 'int'):$conf->entity;

//$parameters = array('socid' => $id);
//$reshook = $hookmanager->executeHooks('doActions', $parameters, $object); // Note that $object may have been modified by some hooks
//if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$filesarray=array();
$result=false;
if(($action=="searchfiles" || $action=="dl" ) && $date_start && $date_stop) {
    $wheretail=" '".$db->idate($date_start)."' AND '".$db->idate($date_stop)."'";
    $sql="SELECT rowid as id, ref as ref, paye as paid, total_ttc, fk_soc, datef as date, 'Invoice' as item FROM ".MAIN_DB_PREFIX."facture";
    $sql.=" WHERE datef between ".$wheretail;
    $sql.=" AND entity IN (".($entity==1?'0,1':$entity).')';
    $sql.=" AND fk_statut <> ".Facture::STATUS_DRAFT;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id, ref, paye as paid, total_ttc, fk_soc, datef as date, 'SupplierInvoice' as item FROM ".MAIN_DB_PREFIX."facture_fourn";
    $sql.=" WHERE datef between ".$wheretail;
    $sql.=" AND entity IN (".($entity==1?'0,1':$entity).')';
    $sql.=" AND fk_statut <> ".FactureFournisseur::STATUS_DRAFT;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id, ref, paid, total_ttc, fk_user_author as fk_soc, date_fin as date, 'ExpenseReport' as item FROM ".MAIN_DB_PREFIX."expensereport";
    $sql.=" WHERE date_fin between  ".$wheretail;
    $sql.=" AND entity IN (".($entity==1?'0,1':$entity).')';
    $sql.=" AND fk_statut <> ".ExpenseReport::STATUS_DRAFT;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id, ref,paid,amount as total_ttc, '0' as fk_soc, datedon as date, 'Donation' as item FROM ".MAIN_DB_PREFIX."don";
    $sql.=" WHERE datedon between ".$wheretail;
    $sql.=" AND entity IN (".($entity==1?'0,1':$entity).')';
    $sql.=" AND fk_statut <> ".Don::STATUS_DRAFT;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id, label as ref, 1 as paid, amount as total_ttc, fk_user as fk_soc,datep as date, 'SalaryPayment' as item FROM ".MAIN_DB_PREFIX."payment_salary";
    $sql.=" WHERE datep between ".$wheretail;
    $sql.=" AND entity IN (".($entity==1?'0,1':$entity).')';
    //$sql.=" AND fk_statut <> ".PaymentSalary::STATUS_DRAFT;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id, libelle as ref, paye as paid, amount as total_ttc, 0 as fk_soc, date_creation as date, 'SocialContributions' as item FROM ".MAIN_DB_PREFIX."chargesociales";
    $sql.=" WHERE date_creation between ".$wheretail;
    $sql.=" AND entity IN (".($entity==1?'0,1':$entity).')';
    //$sql.=" AND fk_statut <> ".ChargeSociales::STATUS_DRAFT;
    $sql.= $db->order($sortfield, $sortorder);

    $resd = $db->query($sql);
    $files=array();
    $link='';

    if ($resd)
    {
        $numd = $db->num_rows($resd);

        $tmpinvoice=new Facture($db);
        $tmpinvoicesupplier=new FactureFournisseur($db);
        $tmpdonation=new Don($db);

        $upload_dir ='';
        $i=0;
        while ($i < $numd)
        {
            $objd = $db->fetch_object($resd);

            switch($objd->item)
            {
                case "Invoice":
                    $subdir=dol_sanitizeFileName($objd->ref);
                    $upload_dir = $conf->facture->dir_output.'/'.$subdir;
                    $link="document.php?modulepart=facture&file=".str_replace('/', '%2F', $subdir).'%2F';
                    break;
                case "SupplierInvoice":
                    $tmpinvoicesupplier->fetch($objd->id);
                    $subdir=get_exdir($tmpinvoicesupplier->id, 2, 0, 0, $tmpinvoicesupplier, 'invoice_supplier').'/'.dol_sanitizeFileName($objd->ref);
                    $upload_dir = $conf->fournisseur->facture->dir_output.'/'.$subdir;
                    $link="document.php?modulepart=facture_fournisseur&file=".str_replace('/', '%2F', $subdir).'%2F';
                    break;
                case "ExpenseReport":
                    $subdir=dol_sanitizeFileName($objd->ref);
                    $upload_dir = $conf->expensereport->dir_output.'/'.$subdir;
                    $link="document.php?modulepart=expensereport&file=".str_replace('/', '%2F', $subdir).'%2F';
                    break;
                case "SalaryPayment":
                    $subdir=dol_sanitizeFileName($objd->id);
                    $upload_dir = $conf->salaries->dir_output.'/'.$subdir;
                    $link="document.php?modulepart=salaries&file=".str_replace('/', '%2F', $subdir).'%2F';
                    break;
                case "Donation":
                    $tmpdonation->fetch($objp->id);
                    $subdir=get_exdir(0, 0, 0, 1, $tmpdonation, 'donation'). '/'. dol_sanitizeFileName($objd->id);
                    $upload_dir = $conf->don->dir_output . '/' . $subdir;
                    $link="document.php?modulepart=don&file=".str_replace('/', '%2F', $subdir).'%2F';
                    break;
                case "SocialContributions":
                    $subdir=dol_sanitizeFileName($objd->id);
                    $upload_dir = $conf->tax->dir_output . '/' . $subdir;
                    $link="document.php?modulepart=tax&file=".str_replace('/', '%2F', $subdir).'%2F';
                    break;
                default:
                    $subdir='';
                    $upload_dir='';
                    $link='';
                    break;
            }

            if (!empty($upload_dir))
            {
                $result=true;
                $files=dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview\.png)$', '', SORT_ASC, 1);
                //var_dump($upload_dir);
                if (count($files) < 1)
                {
                    $nofile['id']=$objd->id;
                    $nofile['date']=$db->idate($objd->date);
                    $nofile['paid']=$objd->paid;
                    $nofile['amount']=$objd->total_ttc;
                    $nofile['ref']=$objd->ref;
                    $nofile['fk']=$objd->fk_soc;
                    $nofile['item']=$objd->item;

                    $filesarray[]=$nofile;
                }
                else
                {
                    foreach ($files as $key => $file)
                    {
                        $file['id']=$objd->id;
                        $file['date']=$db->idate($objd->date);
                        $file['paid']=$objd->paid;
                        $file['amount']=$objd->total_ttc;
                        $file['ref']=$objd->ref;
                        $file['fk']=$objd->fk_soc;
                        $file['item']=$objd->item;
                        $file['link']=$link.$file['name'];
                        $file['relpathnamelang'] = $langs->trans($file['item']).'/'.$file['name'];

                        $filesarray[]=$file;
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

/*
 * cleanup of old ZIP
 */
//FIXME
/*
 *ZIP creation
 */

if ($result && $action == "dl")
{
    if (! extension_loaded('zip'))
    {
        setEventMessages('PHPZIPExtentionNotLoaded', null, 'errors');
        exit;
    }

    $dirfortmpfile = ($conf->accounting->dir_temp ? $conf->accounting->dir_temp : $conf->compta->dir_temp);

    dol_mkdir($dirfortmpfile);

    $log='date,type,ref,total,paid,filename,item_id'."\n";
    $zipname = $dirfortmpfile.'/'.dol_print_date($date_start, 'dayrfc')."-".dol_print_date($date_stop, 'dayrfc').'_export.zip';

    dol_delete_file($zipname);

    $zip = new ZipArchive;
    $res = $zip->open($zipname, ZipArchive::OVERWRITE|ZipArchive::CREATE);
    if ($res)
    {
        foreach ($filesarray as $key=> $file)
        {
            if (file_exists($file["fullname"])) $zip->addFile($file["fullname"], $file["relpathnamelang"]); //
            $log.=dol_print_date($file['date'], 'dayrfc').','.$file['item'].','.$file['ref'].','.$file['amount'].','.$file['paid'].','.$file["name"].','.$file['fk']."\n";
        }
        $zip->addFromString('transactions.csv', $log);
        $zip->close();

        ///Then download the zipped file.
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.basename($zipname));
        header('Content-Length: ' . filesize($zipname));
        readfile($zipname);

        dol_delete_file($zipname);

        exit();
    }
}


/*
 * View
 */

$form = new Form($db);
$userstatic=new User($db);

$title=$langs->trans("ComptaFiles").' - '.$langs->trans("List");

llxHeader('', $title, $help_url);

$h=0;
$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
$head[$h][1] = $langs->trans("AccountantFiles");
$head[$h][2] = 'AccountancyFiles';

dol_fiche_head($head, 'AccountancyFiles');


print '<form name="searchfiles" action="?action=searchfiles'.$tail.'" method="POST" >'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print $langs->trans("ReportPeriod").': '.$form->selectDate($date_start, 'date_start', 0, 0, 0, "", 1, 1, 0);
print ' - '.$form->selectDate($date_stop, 'date_stop', 0, 0, 0, "", 1, 1, 0)."\n</a>";
// Multicompany
/*if (! empty($conf->multicompany->enabled) && is_object($mc))
 {
 print '<br>';
 // This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
 if (method_exists($mc, 'formObjectOptions'))
 {
 if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && ! $user->entity)	// condition must be same for create and edit mode
 {
 print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
 print "<td>".$mc->select_entities($entity);
 print "</td></tr>\n";
 }
 else
 {
 print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
 }
 }

 $object = new stdClass();
 // Other attributes
 $parameters=array('objectsrc' => null, 'colspan' => ' colspan="3"');
 $reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
 print $hookmanager->resPrint;
 if (empty($reshook))
 {
 print $object->showOptionals($extrafields, 'edit');
 }
 }*/
if (! empty($conf->multicompany->enabled) && is_object($mc))
{
    print ' &nbsp; - &nbsp; '.$langs->trans("Entity").' : ';
    $mc->dao->getEntities();
    $mc->dao->fetch($conf->entity);
    print $mc->dao->label;
    print "<br>\n";
}

print '<input class="button" type="submit" value="'.$langs->trans("Refresh").'" /></form>'."\n";

dol_fiche_end();

if (!empty($date_start) && !empty($date_stop))
{
    $param='action=searchfiles';
    $param.='&date_startday='.GETPOST('date_startday', 'int');
    $param.='&date_startmonth='.GETPOST('date_startmonth', 'int');
    $param.='&date_startyear='.GETPOST('date_startyear', 'int');
    $param.='&date_stopday='.GETPOST('date_stopday', 'int');
    $param.='&date_stopmonth='.GETPOST('date_stopmonth', 'int');
    $param.='&date_stopyear='.GETPOST('date_stopyear', 'int');

    print '<form name="dl" action="?action=dl" method="POST" >'."\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

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

    print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($arrayfields['date']['label'], $_SERVER["PHP_SELF"], "date", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    print '<td>'.$langs->trans("Type").'</td>';
    print '<td>'.$langs->trans("Ref").'</td>';
    print '<td>'.$langs->trans("Link").'</td>';
    print '<td>'.$langs->trans("Paid").'</td>';
    print '<td class="right">'.$langs->trans("Debit").'</td>';
    print '<td class="right">'.$langs->trans("Credit").'</td>';
    print '<td class="right">'.$langs->trans("Balance").'</td>';
    print '</tr>';
    if ($result)
    {
        $TData = dol_sort_array($filesarray, 'date', 'ASC');

        if (empty($TData))
        {
            print '<tr class="oddeven"><td colspan="7">'.$langs->trans("NoItem").'</td></tr>';
        }
        else
        {
            // Sort array by date ASC to calculate balance

            $totalDebit = 0;
            $totalCredit = 0;
            // Balance calculation
            $balance = 0;
            foreach($TData as &$data1) {
                if ($data1['item']!='Invoice'&& $data1['item']!='Donation' ){
                    $data1['amount']=-$data1['amount'];
                }
                if ($data1['amount']>0){
                }else{
                }
                $balance += $data1['amount'];
                $data1['balance'] = $balance;
            }

            // Display array
            foreach($TData as $data)
            {
                $html_class = '';
                //if (!empty($data['fk_facture'])) $html_class = 'facid-'.$data['fk_facture'];
                //elseif (!empty($data['fk_paiement'])) $html_class = 'payid-'.$data['fk_paiement'];
                print '<tr class="oddeven '.$html_class.'">';
                print "<td class=\"center\">";
                print dol_print_date($data['date'], 'day');
                print "</td>\n";
                print '<td class="left">'.$langs->trans($data['item']).'</td>';
                print '<td class="left">'.$data['ref'].'</td>';

                // File link
                print '<td><a href='.DOL_URL_ROOT.'/'.$data['link'].">".$data['name']."</a></td>\n";

                print '<td class="left">'.$data['paid'].'</td>';
                print '<td class="right">'.(($data['amount'] > 0) ? price(abs($data['amount'])) : '')."</td>\n";
                $totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;
                print '<td class="right">'.(($data['amount'] > 0) ? '' : price(abs($data['amount'])))."</td>\n";
                $totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);
                // Balance
                print '<td class="right">'.price($data['balance'])."</td>\n";
                print "</tr>\n";
            }

            print '<tr class="liste_total">';
            print '<td colspan="5">&nbsp;</td>';
            print '<td class="right">'.price($totalDebit).'</td>';
            print '<td class="right">'.price($totalCredit).'</td>';
            print '<td class="right">'.price(price2num($totalDebit - $totalCredit, 'MT')).'</td>';
            print "</tr>\n";
        }
    }
    print "</table>";
    print '</div>';
}

llxFooter();
$db->close();
