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
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

restrictedArea($user,'banque');

$langs->load("accountancy");
if (! empty($conf->facture->enabled)) $langs->load("bills");
$date_start =GETPOST('date_start','alpha');
$date_startDay= GETPOST('date_startday','int');
$date_startMonth= GETPOST('date_startmonth','int');
$date_startYear= GETPOST('date_startyear','int');
$date_start=($date_startDay)?dol_mktime(0,0,0,$date_startMonth,$date_startDay,$date_startYear):strtotime($date_start);
$date_stop =GETPOST('date_stop','alpha');
$date_stopDay= GETPOST('date_stopday','int');
$date_stopMonth= GETPOST('date_stopmonth','int');
$date_stopYear= GETPOST('date_stopyear','int');
//FIXME doldate
$date_stop=($date_stopDay)?dol_mktime(0,0,0,$date_stopMonth,$date_stopDay,$date_stopYear):strtotime($date_stop);
$action =GETPOST('action','alpha');
// Security check
//if ($user->societe_id) $id=$user->societe_id;
//$result = restrictedArea($user, 'societe', $id, '&societe');
//$object = new Societe($db);
//if ($id > 0) $object->fetch($id);
// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('comptafilescard','globalcard'));
// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="f.datef,f.rowid"; // Set here default search field
if (! $sortorder) $sortorder="DESC";
$arrayfields=array(
    'date'=>array('label'=>"Date", 'checked'=>1),
    //...
);


/*
 * Actions
 */

//$parameters = array('socid' => $id);
//$reshook = $hookmanager->executeHooks('doActions', $parameters, $object); // Note that $object may have been modified by some hooks
//if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$filesarray=array();
$result=false;
if(($action=="searchfiles"||$action=="dl" ) && $date_start && $date_stop){
    $wheretail=" '".$db->idate($date_start)."' AND '".$db->idate($date_stop)."'";
    $sql="SELECT rowid as id, ref as ref,paye as paid,total_ttc,fk_soc,datef as date, 'Invoice' as item FROM ".MAIN_DB_PREFIX."facture";
    $sql.=" WHERE datef between ".$wheretail;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id,ref, paye as paid, total_ttc, fk_soc,datef as date, 'InvoiceSupplier' as item  FROM ".MAIN_DB_PREFIX."facture_fourn";
    $sql.=" WHERE datef between  ".$wheretail;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id,ref,paid,total_ttc,fk_user_author as fk_soc,date_fin as date,'Expense' as item  FROM ".MAIN_DB_PREFIX."expensereport";
    $sql.=" WHERE date_fin between  ".$wheretail;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id,ref,paid,amount as total_ttc,CONCAT(firstname,' ',lastname) as fk_soc,datedon as date,'Donation' as item  FROM ".MAIN_DB_PREFIX."don";
    $sql.=" WHERE datedon between  ".$wheretail;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id,label as ref,1 as paid,amount as total_ttc,fk_user as fk_soc,datep as date,'Salary' as item  FROM ".MAIN_DB_PREFIX."payment_salary";
    $sql.=" WHERE datep between  ".$wheretail;
    $sql.=" UNION ALL";
    $sql.=" SELECT rowid as id,num_paiement as ref,1 as paid,amount as total_ttc,fk_charge as fk_soc,datep as date,'Charge' as item  FROM ".MAIN_DB_PREFIX."paiementcharge";
    $sql.=" WHERE datep between  ".$wheretail;
    $resd = $db->query($sql);
    $files=array();
    $link='';

    if ($resd)
     {
         $numd = $db->num_rows($resd);

        $upload_dir ='';
         $i=0;
         while($i<$numd)
         {


            $objd = $db->fetch_object($resd);

            switch($objd->item){
            case "Invoice":
                $subdir=dol_sanitizeFileName($objd->ref);
                $upload_dir = $conf->facture->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=facture&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "InvoiceSupplier":
                $subdir=get_exdir($objd->id,2,0,0,$objd,'invoice_supplier').dol_sanitizeFileName($objd->ref);
                $upload_dir = $conf->fournisseur->facture->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=facture_fournisseur&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "Expense":
                $subdir=dol_sanitizeFileName($objd->ref);
                $upload_dir = $conf->expensereport->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=expensereport&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "Salary":
                $subdir=dol_sanitizeFileName($objd->id);
                $upload_dir = $conf->salaries->dir_output.'/'.$subdir;
                $link="../../document.php?modulepart=salaries&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "Donation":
                $subdir=get_exdir(null,2,0,1,$objd,'donation'). '/'. dol_sanitizeFileName($objd->id);
                $upload_dir = $conf->don->dir_output . '/' . $subdir;
                $link="../../document.php?modulepart=don&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            case "Charge":
                $subdir=dol_sanitizeFileName($objd->id);
                $upload_dir = $conf->tax->dir_output . '/' . $subdir;
                $link="../../document.php?modulepart=tax&file=".str_replace('/','%2F',$subdir).'%2F';
                break;
            default:
                break;
            }

            if(!empty($upload_dir)){
                $result=true;
                $files=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$','',SORT_ASC,1);
                foreach ($files as $key => $file){
                    $file['date']=$db->idate($objd->date);
                    $file['paid']=$objd->paid;
                    $file['amount']=$objd->total_ttc;
                    $file['ref']=$objd->ref;
                    $file['fk']=$objd->fk_soc;
                    $file['item']=$objd->item;
                    $file['link']=$link.$file['name'];
                    $out.= '<br><a href="'.$link.$file['name'].'">'.$file['name'].'</a>';
                    $filesarray[]=$file;
                }
                if(count($files)<1){
                    $nofile['date']=$db->idate($objd->date);
                    $nofile['paid']=$objd->paid;
                    $nofile['amount']=$objd->total_ttc;
                    $nofile['ref']=$objd->ref;
                    $nofile['fk']=$objd->fk_soc;
                    $nofile['item']=$objd->item;
                     $filesarray[]=$nofile;
                }
            }
          $i++;
         }
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
if($result & $action=="dl"){
        unset($zip);
   $log='date,type,ref,total,paid,filename,item_id'."\n";
        $zipname = ($date_start)."-".($date_stop).'_export.zip';
        $zip = new ZipArchive;
        $res = $zip->open($zipname, ZipArchive::OVERWRITE|ZipArchive::CREATE);
        if ($res){
          foreach ($filesarray as $key=> $file) {
                    if(file_exists($file["fullname"])) $zip->addFile($file["fullname"],$file["name"]);//
                    $log.=$file['date'].','.$file['item'].','.$file['ref'].','.$file['amount'].','.$file['paid'].','.$file["name"].','.$file['fk']."\n";
           }
          $zip->addFromString('log.csv', $log);
          $zip->close();
        ///Then download the zipped file.
          header('Content-Type: application/zip');
          header('Content-disposition: attachment; filename='.$zipname);
          header('Content-Length: ' . filesize($zipname));
          readfile($zipname);
                unlink($zipname);
          exit();
        }
}
// None
/*
 *      View
 */


llxHeader('',$title,$help_url);

$h=0;
$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
$head[$h][1] = $langs->trans("AccountantFiles");
$head[$h][2] = 'AccountantFiles';
dol_fiche_head($head, 'AccountantFiles');
$form = new Form($db);
$userstatic=new User($db);
$title=$langs->trans("ComptaFiles").' - '.$langs->trans("List");
print   '<div><form name="searchfiles" action="?action=searchfiles'.$tail.'" method="POST" >'."\n\t\t\t";
print    '<a>'.$langs->trans("ReportPeriod").': '.$form->select_date($date_start,'date_start',0,0,0,"",1,1,1);
print    ' - '.$form->select_date($date_stop,'date_stop',0,0,0,"",1,1,1)."\n</a>";
print   '<input class="butAction" type="submit" value="'.$langs->trans("Refresh").'" /></form></div>'."\n\t\t";
if (!empty($date_start) && !empty($date_stop)){
    echo dol_print_date($date_start)." - ".dol_print_date($date_stop);
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($arrayfields['date']['label'],$_SERVER["PHP_SELF"],"date","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
    print '<td>'.$langs->trans("Type").'</td>';
    print '<td align="right">'.$langs->trans("Ref").'</td>';
    print '<td>'.$langs->trans("File").'</td>';
    print '<td>'.$langs->trans("Paid").'</td>';
    print '<td align="right">'.$langs->trans("Debit").'</td>';
    print '<td align="right">'.$langs->trans("Credit").'</td>';
    print '<td align="right">'.$langs->trans("Balance").'</td>';
    print '</tr>';
    if ($result)
    {
        $TData = dol_sort_array($filesarray, 'date', 'ASC');
            if(empty($TData)) {
                            print '<tr class="oddeven"><td colspan="7">'.$langs->trans("NoItem").'</td></tr>';
            } else {
                            // Sort array by date ASC to calucalte balance

                            $totalDebit = 0;
                            $totalCredit = 0;
                            // Balance calculation
                            $balance = 0;
                            foreach($TData as &$data1) {
                                    if($data1['item']!='Invoice'&& $data1['item']!='Donation' ){
                                         $data1['amount']=-$data1['amount'];
                                    }
                                    if ($data1['amount']>0){
                                   }else{
                                   }
                                   $balance += $data1['amount'];
                                    $data1['balance'] = $balance;
                            }
                    // Display array
                    foreach($TData as $data) {
                            $html_class = '';
                            //if (!empty($data['fk_facture'])) $html_class = 'facid-'.$data['fk_facture'];
                            //elseif (!empty($data['fk_paiement'])) $html_class = 'payid-'.$data['fk_paiement'];
                            print '<tr class="oddeven '.$html_class.'">';
                            print "<td align=\"center\">";
                            print dol_print_date($data['date'],'day');
                            print "</td>\n";
                            print '<td aling="left">'.$data['item'].'</td>';
                             print '<td aling="left">'.$data['ref'].'</td>';
                            print '<td> <a href='.$data['link'].">".$data['name']."</a></td>\n";
                            print '<td aling="left">'.$data['paid'].'</td>';
                            print '<td align="right">'.(($data['amount'] > 0) ? price(abs($data['amount'])) : '')."</td>\n";
                            $totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;
                            print '<td align="right">'.(($data['amount'] > 0) ? '' : price(abs($data['amount'])))."</td>\n";
                            $totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);
                            // Balance
                            print '<td align="right">'.price($data['balance'])."</td>\n";
                            print "</tr>\n";
                    }
                    print '<tr class="liste_total">';
                    print '<td colspan="5">&nbsp;</td>';
                    print '<td align="right">'.price($totalDebit).'</td>';
                    print '<td align="right">'.price($totalCredit).'</td>';
                    print '<td align="right">'.price(price2num($totalDebit - $totalCredit, 'MT')).'</td>';
                    print '<td></td>';
                    print "</tr>\n";
                    }
            }
    print "</table>";
    print   '<form name="dl" action="?action=dl" method="POST" >'."\n\t\t\t";

    print   '<input type="hidden" name="date_start" value="'.dol_print_date($date_start,'dayxcard').'" />';
    print   '<input type="hidden" name="date_stop"  value="'.dol_print_date($date_stop, 'dayxcard').'" />';

    //print   '<input type="hidden" name="date_stopDay"  value="'.dol_print_date($date_stop, '%d').'" />';
    //print   '<input type="hidden" name="date_stopMonth"  value="'.dol_print_date($date_stop, '%m').'" />';
    //print   '<input type="hidden" name="date_stopYear"  value="'.dol_print_date($date_stop, '%Y').'" />';

    //print   '<input type="hidden" name="date_startDay"  value="'.dol_print_date($date_start, '%d').'" />';
    //print   '<input type="hidden" name="date_startMonth"  value="'.dol_print_date($date_start, '%m').'" />';
    //print   '<input type="hidden" name="date_startYear"  value="'.dol_print_date($date_start, '%m').'" />';

    print   '<input class="butAction" type="submit" value="'.$langs->trans("Download").'" /></form>'."\n\t\t</th>\n\t\t<th>\n\t\t\t";
}


llxFooter();
$db->close();
