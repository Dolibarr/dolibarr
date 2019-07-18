<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *		\file       htdocs/fourn/product/list.php
 *		\ingroup    produit
 *		\brief      Page to list supplier products and services
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

$langs->loadLangs(array("products","suppliers"));

if (!$user->rights->produit->lire && !$user->rights->service->lire) accessforbidden();

$sref = GETPOST('sref');
$sRefSupplier = GETPOST('srefsupplier');
$snom = GETPOST('snom');
$type = GETPOST('type');
$optioncss = GETPOST('optioncss', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.ref"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

$fourn_id = GETPOST('fourn_id', 'intcomma');
if ($user->societe_id) $fourn_id=$user->societe_id;

$catid = GETPOST('catid', 'intcomma');

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('supplierpricelist'));
$extrafields = new ExtraFields($db);




/*
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();

$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') ||GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
    {
    	$sref = '';
    	$sRefSupplier = '';
    	$snom = '';
        $search_field1='';
        $search_field2='';
        $search_date_creation='';
        $search_date_update='';
        $toselect='';
        $search_array_options=array();
    }
}

/*
 * View
 */

$form = new Form($db);
$productstatic = new Product($db);
$companystatic = new Societe($db);

$title=$langs->trans("ProductsAndServices");

if ($fourn_id)
{
	$supplier = new Fournisseur($db);
	$supplier->fetch($fourn_id);
}



$arrayofmassactions =  array(
	'generate_doc'=>$langs->trans("ReGeneratePDF"),
    'builddoc'=>$langs->trans("PDFMerge"),
    'presend'=>$langs->trans("SendByMail"),
);
if ($user->rights->mymodule->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);


$sql = "SELECT p.rowid, p.label, p.ref, p.fk_product_type, p.entity,";
$sql.= " ppf.fk_soc, ppf.ref_fourn, ppf.price as price, ppf.quantity as qty, ppf.unitprice,";
$sql.= " s.rowid as socid, s.nom as name";
// Add fields to SELECT from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
$sql .= $hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
if ($catid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as ppf ON p.rowid = ppf.fk_product";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ppf.fk_soc = s.rowid";
$sql.= " WHERE p.entity IN (".getEntity('product').")";
if ($sRefSupplier)
{
	$sql .= natural_search('ppf.ref_fourn', $sRefSupplier);
}
if (GETPOST('type'))
{
	$sql .= " AND p.fk_product_type = " . GETPOST('type', 'int');
}
if ($sref)
{
	$sql .= natural_search('p.ref', $sref);
}
if ($snom)
{
	$sql .= natural_search('p.label', $snom);
}
if($catid)
{
	$sql .= " AND cp.fk_categorie = ".$catid;
}
if ($fourn_id > 0)
{
	$sql .= " AND ppf.fk_soc = ".$fourn_id;
}

// Add WHERE filters from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records without orderby and limit
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("fourn/product/list.php:", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num == 1 && (GETPOST("mode") == 'search'))
	{
		$objp = $db->fetch_object($resql);
		header("Location: ".DOL_URL_ROOT."/product/card.php?id=".$objp->rowid);
		exit;
	}

	if (! empty($supplier->id)) $texte = $langs->trans("ListOfSupplierProductForSupplier", $supplier->name);
	else $texte = $langs->trans("List");

	llxHeader("", "", $texte);


	$param="&tobuy=".$tobuy."&sref=".$sref."&snom=".$snom."&fourn_id=".$fourn_id.(isset($type)?"&amp;type=".$type:"").(empty($sRefSupplier)?"":"&amp;srefsupplier=".$sRefSupplier);
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);


	if (! empty($catid))
	{
		print "<div id='ways'>";
		$c = new Categorie($db);
		$ways = $c->print_all_ways(' &gt; ', 'fourn/product/list.php');
		print " &gt; ".$ways[0]."<br>\n";
		print "</div><br>";
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	if ($fourn_id > 0) print '<input type="hidden" name="fourn_id" value="'.$fourn_id.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	$topicmail="Information";
	$modelmail="product";
	$objecttmp=new Product($db);
	$trackid='prod'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	print '<table class="liste" width="100%">';

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="sref" value="'.$sref.'" size="12">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="srefsupplier" value="'.$sRefSupplier.'" size="12">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
	print '</td>';
	print '<td></td>';
	print '<td></td>';
	print '<td></td>';
	print '<td></td>';
    // add filters from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action);
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    print $hookmanager->resPrint;
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>';

	// Lignes des titres
	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("RefSupplierShort", $_SERVER["PHP_SELF"], "ppf.ref_fourn", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("Supplier", $_SERVER["PHP_SELF"], "ppf.fk_soc", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("BuyingPrice", $_SERVER["PHP_SELF"], "ppf.price", $param, "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("QtyMin", $_SERVER["PHP_SELF"], "ppf.quantity", $param, "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("UnitPrice", $_SERVER["PHP_SELF"], "ppf.unitprice", $param, "", '', $sortfield, $sortorder, 'right ');
	// add header cells from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    print $hookmanager->resPrint;
	print_liste_field_titre('', $_SERVER["PHP_SELF"]);
	print "</tr>\n";

	$oldid = '';

	while ($i < min($num, $limit))
	{
		$objp = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->ref;
		$productstatic->type=$objp->fk_product_type;
		$productstatic->entity=$objp->entity;
		print $productstatic->getNomUrl(1, 'supplier');
		print '</td>';

		print '<td>'.$objp->ref_fourn.'</td>';

		print '<td>'.$objp->label.'</td>'."\n";

		$companystatic->name=$objp->name;
		$companystatic->id=$objp->socid;
		print '<td>';
		if ($companystatic->id > 0) print $companystatic->getNomUrl(1, 'supplier');
		print '</td>';

		print '<td class="right">'.(isset($objp->price) ? price($objp->price) : '').'</td>';

		print '<td class="right">'.$objp->qty.'</td>';

		print '<td class="right">'.(isset($objp->unitprice) ? price($objp->unitprice) : '').'</td>';

		// add additional columns from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objp, $action);
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        print $hookmanager->resPrint;

		print '<td class="right"></td>';

		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table>";

	print '</form>';
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
