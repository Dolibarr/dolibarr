<?PHP
/*
 * Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>

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
 * \file        htdocs/accountancy/admin/productaccount.php
 * \ingroup     Accounting Expert
 * \brief       Onglet de gestion de parametrages des ventilations
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Langs
$langs->load("companies");
$langs->load("compta");
$langs->load("main");
$langs->load("accountancy");

// Search & action GETPOST
$action = GETPOST('action');
$codeventil_buy = GETPOST('codeventil_buy', 'array');
$codeventil_sell = GETPOST('codeventil_sell', 'array');
$mesCasesCochees = GETPOST('mesCasesCochees', 'array');
$account_number_buy  = GETPOST('account_number_buy');
$account_number_sell = GETPOST('account_number_sell');
$changeaccount  = GETPOST('changeaccount','array');
$changeaccount_buy   = GETPOST('changeaccount_buy','array');
$changeaccount_sell  = GETPOST('changeaccount_sell','array');
$search_ref     = GETPOST('search_ref','alpha');
$search_label   = GETPOST('search_label','alpha');
$search_desc    = GETPOST('search_desc','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page < 0) $page = 0;
$pageprev = $page - 1;
$pagenext = $page + 1;
//bug in page limit if ACCOUNTING_LIMIT_LIST_VENTILATION < $conf->liste_limit there is no pagination displayed !
if (! empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) && $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION >= $conf->liste_limit) {
    $limit = $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION;
//} else if ($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION <= 0) {
//  $limit = $conf->liste_limit;
} else {
    $limit = $conf->liste_limit;
}
$offset = $limit * $page;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";

// Security check
if ($user->societe_id > 0)
    accessforbidden();
// TODO after adding menu
// if (! $user->rights->accounting->ventilation->dispatch)
// accessforbidden();

$form = new FormVentilation($db);

//Defaut AccountingAccount RowId Product / Service
//at this time ACCOUNTING_SERVICE_SOLD_ACCOUNT & ACCOUNTING_PRODUCT_SOLD_ACCOUNT are account number not accountingacount rowid
//so we need to get those default value rowid first
$accounting = new AccountingAccount($db);
//TODO: we should need to check if result is a really exist accountaccount rowid.....
$aarowid_servbuy  = $accounting->fetch('', ACCOUNTING_SERVICE_BUY_ACCOUNT);
$aarowid_prodbuy  = $accounting->fetch('', ACCOUNTING_PRODUCT_BUY_ACCOUNT);
$aarowid_servsell = $accounting->fetch('', ACCOUNTING_SERVICE_SOLD_ACCOUNT);
$aarowid_prodsell = $accounting->fetch('', ACCOUNTING_PRODUCT_SOLD_ACCOUNT);

$aacompta_servbuy  = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
$aacompta_prodbuy  = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
$aacompta_servsell = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
$aacompta_prodsell = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_ref='';
    $search_label='';
    $search_desc='';
}

//debug move header to top
llxHeader('', $langs->trans("Accounts"));


//TODO: modify to update all selected product with a sell account
if (is_array($changeaccount) && count($changeaccount) > 0 && $action == $langs->trans("Accountancy_code_sell")) {
//print_r ($changeaccount);
    $error = 0;
    
    $db->begin();
    
    $sql1 = "UPDATE " . MAIN_DB_PREFIX . "product as p";
    $sql1 .= " SET p.accountancy_code_sell=" . $account_number_sell;
    $sql1 .= ' WHERE p.rowid IN (' . implode(',', $changeaccount) . ')';
    
    dol_syslog('accountancy/customer/lines.php::changeaccount product sell sql= ' . $sql1);
print_r ($sql1);
    $resql1 = $db->query($sql1);
    if (! $resql1) {
        $error ++;
        setEventMessage($db->lasterror(), 'errors');
    }
    if (! $error) {
        $db->commit();
        setEventMessage($langs->trans('Save'), 'mesgs');
    } else {
        $db->rollback();
        setEventMessage($db->lasterror(), 'errors');
    }
}


//TODO: modify to update all selected product with a buy account
if (is_array($changeaccount) && count($changeaccount) > 0 && $action == $langs->trans("Accountancy_code_buy")) {
    $error = 0;
    
    $db->begin();
    
    $sql1 = "UPDATE " . MAIN_DB_PREFIX . "product as p";
    $sql1 .= " SET p.accountancy_code_buy=" . $account_number_buy;
    $sql1 .= ' WHERE p.rowid IN (' . implode(',', $changeaccount) . ')';
print_r ($sql1);    
    dol_syslog('accountancy/customer/lines.php::changeaccount product buy sql= ' . $sql1);
    $resql1 = $db->query($sql1);
    if (! $resql1) {
        $error ++;
        setEventMessage($db->lasterror(), 'errors');
    }
    if (! $error) {
        $db->commit();
        setEventMessage($langs->trans('Save'), 'mesgs');
    } else {
        $db->rollback();
        setEventMessage($db->lasterror(), 'errors');
    }
}

/*
 * View
 */
//DEBUG elarifr 
//llxHeader('', $langs->trans("Accounts"));

//For updating account export
print '<script type="text/javascript">

        function launch_export() {
            $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
            $("div.fiche div.tabBar form input[type=\"submit\"]").click();
            $("div.fiche div.tabBar form input[name=\"action\"]").val("");
        }
</script>';

//TODO For select box
print  '<script type="text/javascript">
            $(function () {
                $(\'#select-all\').click(function(event) {
                    // Iterate each checkbox
                    $(\':checkbox\').each(function() {
                        this.checked = true;
                    });
                });
                $(\'#unselect-all\').click(function(event) {
                    // Iterate each checkbox
                    $(\':checkbox\').each(function() {
                        this.checked = false;
                    });
                });
            });
             </script>';

/*
 * Action
 */
//TODO 
/*
if ($action == 'ventil') {
    print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
    if (! empty($codeventil_buy) && ! empty($mesCasesCochees)) {
    if (! empty($codeventil_sell) && ! empty($mesCasesCochees)) {

    } else {
        print '<div><font color="red">' . $langs->trans("AnyLineVentilate") . '</font></div>';
    }
    print '<div><font color="red">' . $langs->trans("EndProcessing") . '</font></div>';
}
*/
//do we really need to exclude old product not tosell / tobuy ?
//$sql = "SELECT p.rowid, p.ref , p.label, p.description , p.accountancy_code_sell, p.accountancy_code_buy, p.tms, p.fk_product_type as product_type , p.tosell , p.tobuy ";
//$sql .= " WHERE p.accountancy_code_sell IS NULL  AND p.tosell = 1  OR p.accountancy_code_buy IS NULL AND p.tobuy = 1";
//$sql .= " WHERE p.accountancy_code_sell ='' AND p.tosell = 1  OR p.accountancy_code_buy ='' AND p.tobuy = 1";
$sql  = "SELECT p.rowid, p.ref , p.label, p.description , p.accountancy_code_sell, p.accountancy_code_buy, p.tms, p.fk_product_type as product_type";
$sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
//$sql .= " , " . MAIN_DB_PREFIX . "accountingaccount as aa";
$sql .= " WHERE (";
$sql .= " p.accountancy_code_sell ='' OR p.accountancy_code_sell IS NULL OR p.accountancy_code_buy ='' OR p.accountancy_code_buy IS NULL";

//Search on correct pcg version
$pcgver = $conf->global->CHARTOFACCOUNTS;
$sql .= " OR (p.accountancy_code_sell IS NOT NULL AND p.accountancy_code_sell != '' AND p.accountancy_code_sell NOT IN
    (SELECT aa.account_number FROM " . MAIN_DB_PREFIX . "accountingaccount as aa , " . MAIN_DB_PREFIX . "accounting_system as asy  WHERE fk_pcg_version = asy.pcg_version AND asy.rowid = " . $pcgver . "))";
   //(SELECT account_number FROM " . MAIN_DB_PREFIX . "accountingaccount as aa WHERE fk_pcg_version='PCG99-BASE'))";
$sql .= " OR (p.accountancy_code_buy  IS NOT NULL AND p.accountancy_code_buy  != '' AND p.accountancy_code_buy  NOT IN
    (SELECT aa.account_number FROM " . MAIN_DB_PREFIX . "accountingaccount as aa , " . MAIN_DB_PREFIX . "accounting_system as asy  WHERE fk_pcg_version = asy.pcg_version AND asy.rowid = " . $pcgver . "))";
   //(SELECT account_number FROM " . MAIN_DB_PREFIX . "accountingaccount as aa WHERE fk_pcg_version='PCG99-BASE'))";
$sql .= ")";
//Add search filter like
if (strlen(trim($search_ref))) {
    $sql .= " AND (p.ref like '" . $search_ref . "%')";
}
if (strlen(trim($search_label))) {
    $sql .= " AND (p.label like '" . $search_label . "%')";
}
if (strlen(trim($search_desc))) {
    $sql .= " AND (p.description like '%" . $search_desc . "%')";
}
$sql.= $db->order($sortfield,$sortorder);

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/accountancy/admin/productaccount.php:: sql=" . $sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
    $num_lines = $db->num_rows($result);
    $i = 0;

/*


 * View
 */
    print_barre_liste($langs->trans("ProductAccountingAccountSelect"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num_lines);

    print '<td align="left"><b>' . $langs->trans("DescProductAccountingAccount") . '</b></td>&nbsp;';
    print_liste_field_titre($langs->trans("RowId"), $_SERVER["PHP_SELF"],"p.rowid","",$param,'',$sortfield,$sortorder);
    print '&nbsp;&nbsp;';


//DEBUG
//print $sql;

    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post"><br />';
//  print '<input type="hidden" name="action" value="ventil">';

    print '<table class="noborder" width="100%">';
    print '<tr>';
    print '<td width="33%">';
    print '<div class="inline-block divButAction">' . $langs->trans("ChangeAccount") . '<br />';
    print $langs->trans("Accountancy_code_buy") . ': ' . $form->select_account($account_number_buy, 'account_number_buy', 1,'', 0, 1);
    print '<input type="submit" class="butAction" name="action" value="' . $langs->trans("Accountancy_code_buy") . '"/></div>';
    print '</td>';

    print '<td width="33%">';
    print '<div class="inline-block divButAction">' . $langs->trans("ChangeAccount") . '<br />';
    print $langs->trans("Accountancy_code_sell") . ': ' . $form->select_account($account_number_sell, 'account_number_sell', 1, '', 0, 1);
    print '<input type="submit" class="butAction" name="action" value="' . $langs->trans("Accountancy_code_sell") . '"/></div>';
    print '</td>';
    print '<td width="33%">';
    //TODO change button
    print '<input type="button" class="button" style="float: right;" value="Renseigner les comptes comptables produits manquant" onclick="launch_export();" />';
    print '</td>';
    print '</tr>';
    print '</table>';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
//  print '<td align="left">' . $langs->trans("Ref") . '</td>';
//  print '<td align="left">' . $langs->trans("Label") . '</td>';
//  print '<td align="left">' . $langs->trans("Description") . '</td>';
    print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"],"p.label","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"],"l.description","",$param,'',$sortfield,$sortorder);
    print '<td align="left">' . $langs->trans("Accountancy_code_buy") . '</td>';
    print '<td align="left">' . $langs->trans("Accountancy_code_buy_suggest") . '</td>';
    print '<td align="left">' . $langs->trans("Accountancy_code_sell") . '</td>';
    print '<td align="left">' . $langs->trans("Accountancy_code_sell_suggest") . '</td>';
    print_liste_field_titre('');
//  print_liste_field_titre('');
    print '<td align="center" colspan="2">' . $langs->trans("Ventilate") . '<br><label id="select-all">'.$langs->trans('All').'</label>/<label id="unselect-all">'.$langs->trans('None').'</label>'.'</td>';
    print '</tr>';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">%<input type="text" class="flat" size="20" name="search_ref" value="' . $search_ref . '"></td>';
    print '<td class="liste_titre">%<input type="text" class="flat" size="20" name="search_label" value="' . $search_label . '"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" size="30" name="search_desc" value="' . $search_desc . '"></td>';

    print '<td class="liste_titre" colspan="3">&nbsp;</td>';
    print '<td align="right" colspan="4" class="liste_titre">';
    print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
//  print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '&nbsp;';
    print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
//  print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';
//  print '<td class="liste_titre" colspan="2">&nbsp;</td>';
    print '</tr>';
    
    $var = true;

    
    while ( $i < min($num_lines, 250) ) {
        $obj = $db->fetch_object($result);
        $var = ! $var;

        
        $compta_prodsell = $obj->accountancy_code_sell;
        if (empty($compta_prodsell)) {
            if ($obj->product_type == 0) {
                $compta_prodsell = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
                $compta_prodsell_id  = $aarowid_prodsell;
            } else {
                $compta_prodsell = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT : $langs->trans("CodeNotDef"));
                $compta_prodsell_id  = $aarowid_servsell;
            }
        }

        
        $compta_prodbuy = $obj->accountancy_code_buy;
        if (empty($compta_prodbuy)) {
            if ($obj->product_type == 0) {
                $compta_prodbuy = (! empty($conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_PRODUCT_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
                $compta_prodbuy_id  = $aarowid_prodbuy;
            } else {
                $compta_prodbuy = (! empty($conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT) ? $conf->global->ACCOUNTING_SERVICE_BUY_ACCOUNT : $langs->trans("CodeNotDef"));
                $compta_prodbuy_id  = $aarowid_servbuy;
            }
        }

        
        $product_static = new Product($db);
        
        print "<tr $bc[$var]>";
//debug
print '<td align="left" colspan="6">Compte Suggeres compta_prodbuy=' . $compta_prodbuy . '  -- compta_prodbuy_id' . $compta_prodbuy_id . '-- compta_prodsell:' . $compta_prodsell . '-- compta_prodsell_id' . $compta_prodsell_id . '</td>';

        print "</tr>";
        print "<tr $bc[$var]>";
        // Ref produit as link
        $product_static->ref = $obj->ref;
        $product_static->id = $obj->rowid;
        $product_static->type = $obj->type;
        print '<td>';
        if ($product_static->id)
            print $product_static->getNomUrl(1);
        else
            print '-&nbsp;';
        print '</td>';

        print '<td align="left">' . $obj->label . '</td>';
//TODO ADJUST DESCRIPTION SIZE
//      print '<td align="left">' . $obj->description . '</td>';
        //TODO: we shoul set a user defined value to adjust user square / wide screen size
        $trunclengh = defined('ACCOUNTING_LENGTH_DESCRIPTION') ? ACCOUNTING_LENGTH_DESCRIPTION : 32;
        print '<td style="' . $code_sell_p_l_differ . '">' . nl2br(dol_trunc($obj->description, $trunclengh)) . '</td>';

        //acountingaccount buy
        print '<td align="left">' . $obj->accountancy_code_buy . '</td>';
//TODO: replace by select
//      print '<td align="left">' . $compta_prodbuy . '</td>';
        //TODO: we shoul set a user defined value to adjust user square / wide screen size
        //$trunclenghform = defined('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT') ? ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT : 50;
        print '<td align="center">';
        print $form->select_account($compta_prodbuy_id, 'codeventil[]', 1);
        print '</td>';

        //acountingaccount sel
        print '<td align="left">' . $obj->accountancy_code_sell . '</td>';

//TODO: replace by select
        //TODO: we shoul set a user defined value to adjust user square / wide screen size
        //$trunclenghform = defined('ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT') ? ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT : 50;
        print '<td align="center">';
        print $form->select_account($compta_prodsell_id, 'codeventil[]', 1);
        print '</td>';
        //action edit & select box
        print '<td align="center">' . $obj->rowid . '</td>';
        print '<td><a href="./card.php?id=' . $obj->rowid . '">';
        print img_edit();
        print '</a></td>';
        //Checkbox select
        print '<td align="center">';
        print '<input type="checkbox" name="changeaccount[]" value="' . $obj->rowid . '"/></td>';

        print "</tr>";
        $i ++;
    }

    $db->free($result);
} else {
//  print $db->error();
    dol_print_error($db);
}

print "</table></form>";

llxFooter();
$db->close();