<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2015       Juanjo Menent           <jmenent@2byte.es>
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
 *      \file       htdocs/product/stock/list.php
 *      \ingroup    stock
 *      \brief      Page with warehouse and stock value
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

// Load translation files required by the page
$langs->load("stocks");

// Security check
<<<<<<< HEAD
$result=restrictedArea($user,'stock');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_ref=GETPOST("sref","alpha")?GETPOST("sref","alpha"):GETPOST("search_ref","alpha");
$search_label=GETPOST("snom","alpha")?GETPOST("snom","alpha"):GETPOST("search_label","alpha");
$search_status=GETPOST("search_status","int");

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
=======
$result=restrictedArea($user, 'stock');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_ref=GETPOST("sref", "alpha")?GETPOST("sref", "alpha"):GETPOST("search_ref", "alpha");
$search_label=GETPOST("snom", "alpha")?GETPOST("snom", "alpha"):GETPOST("search_label", "alpha");
$search_status=GETPOST("search_status", "int");

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="ASC";
$page = GETPOST("page");
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;

<<<<<<< HEAD
$year = strftime("%Y",time());
=======
$year = strftime("%Y", time());

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Entrepot($db);
$hookmanager->initHooks(array('stocklist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('entrepot');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.ref'=>"Ref",
    'e.lieu'=>"LocationSummary",
    'e.description'=>"Description",
    'e.address'=>"Address",
    'e.zip'=>'Zip',
    'e.town'=>'Town',
);


<<<<<<< HEAD
=======
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
    }
}


>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

<<<<<<< HEAD
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // Both test are required to be compatible with all browsers
=======
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    $search_ref="";
    $sall="";
    $search_label="";
    $search_status="";
    $search_array_options=array();
}


/*
 *	View
 */

$form=new Form($db);
$warehouse=new Entrepot($db);

$sql = "SELECT e.rowid, e.ref, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays, e.fk_parent,";
$sql.= " SUM(p.pmp * ps.reel) as estimatedvalue, SUM(p.price * ps.reel) as sellvalue, SUM(ps.reel) as stockqty";
<<<<<<< HEAD
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON e.rowid = ps.fk_entrepot";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
=======
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON e.rowid = ps.fk_entrepot";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot_extrafields as ef on (e.rowid = ef.fk_object)";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sql.= " WHERE e.entity IN (".getEntity('stock').")";
if ($search_ref) $sql.= natural_search("e.ref", $search_ref);			// ref
if ($search_label) $sql.= natural_search("e.lieu", $search_label);		// label
if ($search_status != '' && $search_status >= 0) $sql.= " AND e.statut = ".$search_status;
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
<<<<<<< HEAD
=======
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sql.= " GROUP BY e.rowid, e.ref, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays, e.fk_parent";
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
	// fetch totals
	$line = $total = $totalsell = $totalStock = 0;
	while ($line < $totalnboflines)
	{
		$objp = $db->fetch_object($result);
<<<<<<< HEAD
		$total += price2num($objp->estimatedvalue,'MU');
		$totalsell += price2num($objp->sellvalue,'MU');
=======
		$total += price2num($objp->estimatedvalue, 'MU');
		$totalsell += price2num($objp->sellvalue, 'MU');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$totalStock += $objp->stockqty;
		$line++;
	}
}
<<<<<<< HEAD
$sql.= $db->order($sortfield,$sortorder);
=======
$sql.= $db->order($sortfield, $sortorder);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
<<<<<<< HEAD
	llxHeader("",$langs->trans("ListOfWarehouses"),$help_url);
=======
	llxHeader("", $langs->trans("ListOfWarehouses"), $help_url);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($search_ref)	$param.="&search_ref=".urlencode($search_ref);
	if ($search_label)	$param.="&search_label=".urlencode($search_label);
	if ($search_status)	$param.="&search_status=".urlencode($search_status);
	if ($sall)			$param.="&sall=".urlencode($sall);

<<<<<<< HEAD
	$newcardbutton='';
	if ($user->rights->stock->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create"><span class="valignmiddle">'.$langs->trans('MenuNewWarehouse').'</span>';
=======
    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$newcardbutton='';
	if ($user->rights->stock->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create"><span class="valignmiddle text-plus-circle">'.$langs->trans('MenuNewWarehouse').'</span>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("ListOfWarehouses"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_generic.png', 0, $newcardbutton, '', $limit);

	if ($sall)
	{
	    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
<<<<<<< HEAD
	    print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
=======
	    print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall).'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	$moreforfilter='';

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

<<<<<<< HEAD
	// Lignes des champs de filtre
	print '<tr class="liste_titre_filter">';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_ref" size="6" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';

	print '<td class="liste_titre" align="left">';
=======
	// Fields title search
	print '<tr class="liste_titre_filter">';

	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_ref" size="6" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';

	print '<td class="liste_titre left">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '<input class="flat" type="text" name="search_label" size="10" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';

	print '<td class="liste_titre" colspan="3">';
	print '</td>';

<<<<<<< HEAD
	print '<td class="liste_titre" align="right">';
	print $form->selectarray('search_status', $warehouse->statuts, $search_status, 1, 0, 0, '', 1);
	print '</td>';

    print '<td class="liste_titre" align="right">';
=======
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	print '<td class="liste_titre right">';
	print $form->selectarray('search_status', $warehouse->statuts, $search_status, 1, 0, 0, '', 1);
	print '</td>';

    print '<td class="liste_titre maxwidthsearch">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
<<<<<<< HEAD
	print_liste_field_titre("Ref",$_SERVER["PHP_SELF"], "e.ref","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre("LocationSummary",$_SERVER["PHP_SELF"], "e.lieu","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stockqty",'',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre("EstimatedStockValue", $_SERVER["PHP_SELF"], "estimatedvalue",'',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre("EstimatedStockValueSell", $_SERVER["PHP_SELF"], "",'',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre("Status",$_SERVER["PHP_SELF"], "e.statut",'',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'',$param,'',$sortfield,$sortorder,'maxwidthsearch ');
=======
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "e.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("LocationSummary", $_SERVER["PHP_SELF"], "e.lieu", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stockqty", '', $param, '', $sortfield, $sortorder, 'right ');
    print_liste_field_titre("EstimatedStockValue", $_SERVER["PHP_SELF"], "estimatedvalue", '', $param, '', $sortfield, $sortorder, 'right ');
    print_liste_field_titre("EstimatedStockValueSell", $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ');
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
    print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "e.statut", '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "</tr>\n";

	if ($num)
	{
		$warehouse=new Entrepot($db);
        $var=false;
<<<<<<< HEAD
		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($result);

			$warehouse->id = $objp->rowid;
			$warehouse->ref = $objp->ref;
			$warehouse->label = $objp->ref;
            $warehouse->lieu = $objp->lieu;
            $warehouse->fk_parent = $objp->fk_parent;

            print '<tr class="oddeven">';
            print '<td>' . $warehouse->getNomUrl(1) . '</td>';
            // Location
            print '<td>'.$objp->lieu.'</td>';
            // Stock qty
            print '<td align="right">'.price2num($objp->stockqty,5).'</td>';
            // PMP value
            print '<td align="right">';
            if (price2num($objp->estimatedvalue,'MT')) print price(price2num($objp->estimatedvalue,'MT'),1);
            else print '';
            print '</td>';
            // Selling value
            print '<td align="right">';
            if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($objp->sellvalue,'MT'),1);
            else
			{
				$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
            	print $form->textwithtooltip($langs->trans("Variable"),$htmltext);
			}
            print '</td>';
            // Status
            print '<td align="right">'.$warehouse->LibStatut($objp->statut,5).'</td>';

            print '<td></td>';
=======
        $totalarray=array();
		while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($result);

			$warehouse->id = $obj->rowid;
			$warehouse->ref = $obj->ref;
			$warehouse->label = $obj->ref;
            $warehouse->lieu = $obj->lieu;
            $warehouse->fk_parent = $obj->fk_parent;

            print '<tr class="oddeven">';
            print '<td>' . $warehouse->getNomUrl(1) . '</td>';
            if (! $i) $totalarray['nbfield']++;
            // Location
            print '<td>'.$obj->lieu.'</td>';
            if (! $i) $totalarray['nbfield']++;

            // Stock qty
            print '<td class="right">'.price2num($obj->stockqty, 5).'</td>';
            if (! $i) $totalarray['nbfield']++;

            // PMP value
            print '<td class="right">';
            if (price2num($obj->estimatedvalue, 'MT')) print price(price2num($obj->estimatedvalue, 'MT'), 1);
            else print '';
            print '</td>';
            if (! $i) $totalarray['nbfield']++;

            // Selling value
            print '<td class="right">';
            if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($obj->sellvalue, 'MT'), 1);
            else
			{
				$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
            	print $form->textwithtooltip($langs->trans("Variable"), $htmltext);
			}
            print '</td>';
            if (! $i) $totalarray['nbfield']++;

            // Extra fields
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

            // Status
            print '<td class="right">'.$warehouse->LibStatut($obj->statut, 5).'</td>';
            if (! $i) $totalarray['nbfield']++;

            print '<td></td>';
            if (! $i) $totalarray['nbfield']++;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

            print "</tr>\n";


            $i++;
		}

		if ($totalnboflines-$offset <= $limit)
		{
    		print '<tr class="liste_total">';
<<<<<<< HEAD
            print '<td colspan="2" align="right">'.$langs->trans("Total").'</td>';
			print '<td align="right">'.price2num($totalStock,5).'</td>';
            print '<td align="right">'.price(price2num($total,'MT'),1,$langs,0,0,-1,$conf->currency).'</td>';
            print '<td align="right">';
    		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($totalsell,'MT'),1,$langs,0,0,-1,$conf->currency);
            else
    		{
    			$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
               	print $form->textwithtooltip($langs->trans("Variable"),$htmltext);
=======
            print '<td colspan="2" class="right">'.$langs->trans("Total").'</td>';
			print '<td class="right">'.price2num($totalStock, 5).'</td>';
            print '<td class="right">'.price(price2num($total, 'MT'), 1, $langs, 0, 0, -1, $conf->currency).'</td>';
            print '<td class="right">';
    		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($totalsell, 'MT'), 1, $langs, 0, 0, -1, $conf->currency);
            else
    		{
    			$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
               	print $form->textwithtooltip($langs->trans("Variable"), $htmltext);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    		}
            print '</td>';
            print '<td></td>';
            print '<td></td>';
<<<<<<< HEAD
=======
            print '<td></td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print "</tr>\n";
		}
	}

	$db->free($result);

	print "</table>";
    print "</table>";

	print '</form>';
}
else
{
  dol_print_error($db);
}

<<<<<<< HEAD

llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
