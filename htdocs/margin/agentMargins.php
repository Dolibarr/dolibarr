<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2014		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
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
 *	\file       htdocs/margin/agentMargins.php
 *	\ingroup    margin
 *	\brief      Page des marges par agent commercial
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'products', 'margins'));

$mesg = '';

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield)
{
	if ($agentid > 0)
		$sortfield = "s.nom";
	else
	    $sortfield = "u.lastname";
}

$startdate = $enddate = '';

$startdateday   = GETPOST('startdateday', 'int');
$startdatemonth = GETPOST('startdatemonth', 'int');
$startdateyear  = GETPOST('startdateyear', 'int');
$enddateday     = GETPOST('enddateday', 'int');
$enddatemonth   = GETPOST('enddatemonth', 'int');
$enddateyear    = GETPOST('enddateyear', 'int');

if (!empty($startdatemonth))
    $startdate  = dol_mktime(0, 0, 0, $startdatemonth, $startdateday, $startdateyear);
if (!empty($enddatemonth))
    $enddate = dol_mktime(23, 59, 59, $enddatemonth, $enddateday, $enddateyear);

// Security check
if ($user->rights->margins->read->all) {
    $agentid = GETPOST('agentid', 'int');
} else {
    $agentid = $user->id;
}
$result = restrictedArea($user, 'margins');


/*
 * Actions
 */

// None



/*
 * View
 */

$userstatic = new User($db);
$companystatic = new Societe($db);
$invoicestatic = new Facture($db);

$form = new Form($db);

llxHeader('', $langs->trans("Margins").' - '.$langs->trans("Agents"));

$text = $langs->trans("Margins");
//print load_fiche_titre($text);

// Show tabs
$head = marges_prepare_head($user);
$titre = $langs->trans("Margins");
$picto = 'margin';

print '<form method="post" name="sel" action="'.$_SERVER['PHP_SELF'].'">';

dol_fiche_head($head, 'agentMargins', $titre, 0, $picto);

print '<table class="border centpercent">';

print '<tr><td class="titlefield">'.$langs->trans('ContactOfInvoice').'</td>';
print '<td class="maxwidthonsmartphone" colspan="4">';
print $form->select_dolusers($agentid, 'agentid', 1, '', $user->rights->margins->read->all ? 0 : 1, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td></tr>';

// Start date
print '<td>'.$langs->trans('DateStart').' ('.$langs->trans("DateValidation").')</td>';
print '<td>';
print $form->selectDate($startdate, 'startdate', '', '', 1, "sel", 1, 1);
print '</td>';
print '<td>'.$langs->trans('DateEnd').' ('.$langs->trans("DateValidation").')</td>';
print '<td>';
print $form->selectDate($enddate, 'enddate', '', '', 1, "sel", 1, 1);
print '</td>';
print '<td style="text-align: center;">';
print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Refresh')).'" />';
print '</td></tr>';
print "</table>";

dol_fiche_end();

print '</form>';

$invoice_status_except_list = array(Facture::STATUS_DRAFT, Facture::STATUS_ABANDONED);

$sql = "SELECT";
$sql .= " s.rowid as socid, s.nom as name, s.code_client, s.client,";
$sql .= " u.rowid as agent, u.login, u.lastname, u.firstname,";
$sql .= " sum(d.total_ht) as selling_price,";
// Note: qty and buy_price_ht is always positive (if not your database may be corrupted, you can update this)
$sql .= " sum(".$db->ifsql('d.total_ht < 0', 'd.qty * d.buy_price_ht * -1 * (d.situation_percent / 100)', 'd.qty * d.buy_price_ht * (d.situation_percent / 100)').") as buying_price,";
$sql .= " sum(".$db->ifsql('d.total_ht < 0', '-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty * (d.situation_percent / 100)))', 'd.total_ht - (d.buy_price_ht * d.qty * (d.situation_percent / 100))').") as marge";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."facture as f";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact e ON e.element_id = f.rowid and e.statut = 4 and e.fk_c_type_contact = ".(empty($conf->global->AGENT_CONTACT_TYPE) ?-1 : $conf->global->AGENT_CONTACT_TYPE);
$sql .= ", ".MAIN_DB_PREFIX."facturedet as d";
$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= ", ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE f.fk_soc = s.rowid";
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
$sql .= " AND sc.fk_soc = f.fk_soc";
$sql .= " AND (d.product_type = 0 OR d.product_type = 1)";
if (!empty($conf->global->AGENT_CONTACT_TYPE))
	$sql .= " AND ((e.fk_socpeople IS NULL AND sc.fk_user = u.rowid) OR (e.fk_socpeople IS NOT NULL AND e.fk_socpeople = u.rowid))";
else
	$sql .= " AND sc.fk_user = u.rowid";
$sql .= " AND f.fk_statut NOT IN (".implode(', ', $invoice_status_except_list).")";
$sql .= ' AND s.entity IN ('.getEntity('societe').')';
$sql .= " AND d.fk_facture = f.rowid";
if ($agentid > 0) {
	if (!empty($conf->global->AGENT_CONTACT_TYPE))
  		$sql .= " AND ((e.fk_socpeople IS NULL AND sc.fk_user = ".$agentid.") OR (e.fk_socpeople IS NOT NULL AND e.fk_socpeople = ".$agentid."))";
	else
	    $sql .= " AND sc.fk_user = ".$agentid;
}
if (!empty($startdate))
  $sql .= " AND f.datef >= '".$db->idate($startdate)."'";
if (!empty($enddate))
  $sql .= " AND f.datef <= '".$db->idate($enddate)."'";
$sql .= " AND d.buy_price_ht IS NOT NULL";
if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1) $sql .= " AND d.buy_price_ht <> 0";
//if ($agentid > 0) $sql.= " GROUP BY s.rowid, s.nom, s.code_client, s.client, u.rowid, u.login, u.lastname, u.firstname";
//else $sql.= " GROUP BY u.rowid, u.login, u.lastname, u.firstname";
$sql .= " GROUP BY s.rowid, s.nom, s.code_client, s.client, u.rowid, u.login, u.lastname, u.firstname";
$sql .= $db->order($sortfield, $sortorder);
// TODO: calculate total to display then restore pagination
//$sql.= $db->plimit($conf->liste_limit +1, $offset);


print '<br>';
print img_info('').' '.$langs->trans("MarginPerSaleRepresentativeWarning").'<br>';

$param = '';
if (!empty($agentid))           $param .= "&amp;agentid=".urlencode($agentid);
if (!empty($startdateday))      $param .= "&amp;startdateday=".urlencode($startdateday);
if (!empty($startdatemonth))    $param .= "&amp;startdatemonth=".urlencode($startdatemonth);
if (!empty($startdateyear))     $param .= "&amp;startdateyear=".urlencode($startdateyear);
if (!empty($enddateday))        $param .= "&amp;enddateday=".urlencode($enddateday);
if (!empty($enddatemonth))      $param .= "&amp;enddatemonth=".urlencode($enddatemonth);
if (!empty($enddateyear))       $param .= "&amp;enddateyear=".urlencode($enddateyear);


dol_syslog('margin::agentMargins.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	print '<br>';
	print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num, $num, '', 0, '', '', 0, 1);

	if ($conf->global->MARGIN_TYPE == "1")
	    $labelcostprice = 'BuyingPrice';
	else   // value is 'costprice' or 'pmp'
	    $labelcostprice = 'CostPrice';

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	if ($agentid > 0)
		print_liste_field_titre("Customer", $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	else
		print_liste_field_titre("SalesRepresentative", $_SERVER["PHP_SELF"], "u.lastname", "", $param, '', $sortfield, $sortorder);

	print_liste_field_titre("SellingPrice", $_SERVER["PHP_SELF"], "selling_price", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($labelcostprice, $_SERVER["PHP_SELF"], "buying_price", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Margin", $_SERVER["PHP_SELF"], "marge", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($conf->global->DISPLAY_MARGIN_RATES))
		print_liste_field_titre("MarginRate", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($conf->global->DISPLAY_MARK_RATES))
		print_liste_field_titre("MarkRate", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";

    if ($num > 0)
    {
        $group_list = array();
        while ($objp = $db->fetch_object($result)) {
            if ($agentid > 0) {
                $group_id = $objp->socid;
            } else {
                $group_id = $objp->agent;
            }

            if (!isset($group_list[$group_id])) {
                if ($agentid > 0) {
                    $group_name = $objp->name;
                    $companystatic->id = $objp->socid;
                    $companystatic->name = $objp->name;
                    $companystatic->client = $objp->client;
                    $group_htmlname = $companystatic->getNomUrl(1, 'customer');
                } else {
                    $group_name = $objp->lastname;
                    $userstatic->fetch($objp->agent);
                    $group_htmlname = $userstatic->getFullName($langs, 0, 0, 0);
                }
                $group_list[$group_id] = array('name' => $group_name, 'htmlname' => $group_htmlname, 'selling_price' => 0, 'buying_price' => 0, 'marge' => 0);
            }

            $seller_nb = 1;
            if ($objp->socid > 0) {
                // sql nb sellers
                $sql_seller  = "SELECT COUNT(sc.rowid) as nb";
                $sql_seller .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
                $sql_seller .= " WHERE sc.fk_soc = ".$objp->socid;
                $sql_seller .= " LIMIT 1";

                $resql_seller = $db->query($sql_seller);
                if (!$resql_seller) {
                    dol_print_error($db);
                } else {
                    if ($obj_seller = $db->fetch_object($resql_seller)) {
                        if ($obj_seller->nb > 0) {
                            $seller_nb = $obj_seller->nb;
                        }
                    }
                }
            }

            $group_list[$group_id]['selling_price'] += $objp->selling_price / $seller_nb;
            $group_list[$group_id]['buying_price'] += $objp->buying_price / $seller_nb;
            $group_list[$group_id]['marge'] += $objp->marge / $seller_nb;
        }

        // sort group array by sortfield
        if ($sortfield == 'u.lastname' || $sortfield == 's.nom') {
            $sortfield = 'name';
        }
        $group_list = dol_sort_array($group_list, $sortfield, $sortorder);

        foreach ($group_list as $group_id => $group_array) {
            $pa = $group_array['buying_price'];
            $pv = $group_array['selling_price'];
            $marge = $group_array['marge'];

            $marginRate = ($pa != 0) ? (100 * $marge / $pa) : '';
            $markRate = ($pv != 0) ? (100 * $marge / $pv) : '';

            print '<tr class="oddeven">';
            print "<td>".$group_array['htmlname']."</td>\n";
            print "<td class=\"right\">".price(price2num($pv, 'MT'))."</td>\n";
            print "<td class=\"right\">".price(price2num($pa, 'MT'))."</td>\n";
            print "<td class=\"right\">".price(price2num($marge, 'MT'))."</td>\n";
            if (!empty($conf->global->DISPLAY_MARGIN_RATES))
            	print "<td class=\"right\">".(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%")."</td>\n";
            if (!empty($conf->global->DISPLAY_MARK_RATES))
            	print "<td class=\"right\">".(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%")."</td>\n";
            print "</tr>\n";
        }
    }
    print "</table>";
}
else
{
	dol_print_error($db);
}
$db->free($result);

print "\n".'<script type="text/javascript">
$(document).ready(function() {
  $("#agentid").change(function() {
     $("div.fiche form").submit();
  });
});
</script>'."\n";

// End of page
llxFooter();
$db->close();
