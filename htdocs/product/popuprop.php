<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry	<jfefe@aternatik.fr>
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
 * \file       htdocs/product/popuprop.php
 * \ingroup    propal, produit
 * \brief      Liste des produits/services par popularite
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

//Required to translate NbOfProposals
$langs->load('propal');

$type=GETPOST("type","int");

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service');

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
if (! $sortfield) $sortfield="c";
if (! $sortorder) $sortorder="DESC";
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


$staticproduct=new Product($db);


/*
 * View
 */

$helpurl='';
if ($type == '0')
{
    $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
else if ($type == '1')
{
    $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}
else
{
    $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}
$title=$langs->trans("Statistics");


llxHeader('', $title, $helpurl);

print load_fiche_titre($title, $mesg,'title_products.png');


$param = '';
$title = $langs->trans("ListProductServiceByPopularity");
if ((string) $type == '1') {
	$title = $langs->trans("ListServiceByPopularity");
}
if ((string) $type == '0') {
	$title = $langs->trans("ListProductByPopularity");
}

if ($type != '') $param .= '&type='.$type;


$h=0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/product/stats/card.php?id=all';
$head[$h][1] = $langs->trans("Chart");
$head[$h][2] = 'chart';
$h++;

$head[$h][0] = $_SERVER['PHP_SELF'];
$head[$h][1] = $title;
$head[$h][2] = 'popularityprop';
$h++;

dol_fiche_head($head,'popularityprop',$langs->trans("Statistics"));


// Array of liens to show
$infoprod=array();


// Add lines for proposals
$sql  = "SELECT p.rowid, p.label, p.ref, p.fk_product_type as type, SUM(pd.qty) as c";
$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pd";
$sql.= ", ".MAIN_DB_PREFIX."product as p";
$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
$sql.= " AND p.rowid = pd.fk_product";
if ($type !== '') {
	$sql.= " AND fk_product_type = ".$type;
}
$sql.= " GROUP BY p.rowid, p.label, p.ref, p.fk_product_type";

$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $var=True;
    while ($i < $num)
    {
        $objp = $db->fetch_object($resql);
        
        $infoprod[$objp->rowid]=array('type'=>$objp->type, 'ref'=>$objp->ref, 'label'=>$objp->label);
        $infoprod[$objp->rowid]['nblineproposal']=$objp->c;
        
        $i++;
    }
    $db->free($resql);
}
else
{
    dol_print_error($db);
}
//var_dump($infoprod);


print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $totalnboflines, '');

print '<table class="noborder" width="100%">';

print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans('Ref'), $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Type'), $_SERVER["PHP_SELF"], 'p.fk_product_type', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Label'), $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('NbOfQtyInProposals'), $_SERVER["PHP_SELF"], 'c', '', $param, 'align="right"', $sortfield, $sortorder);
print "</tr>\n";

$var=True;
foreach($infoprod as $prodid => $vals)
{
	// Multilangs
	if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
	{
		$sql = "SELECT label";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
		$sql.= " WHERE fk_product=".$prodid;
		$sql.= " AND lang='". $langs->getDefaultLang() ."'";
		$sql.= " LIMIT 1";

		$resultp = $db->query($sql);
		if ($resultp)
		{
			$objtp = $db->fetch_object($resultp);
			if (! empty($objtp->label)) $vals['label'] = $objtp->label;
		}
	}

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td><a href="'.DOL_URL_ROOT.'/product/stats/card.php?id='.$prodid.'">';
	if ($vals['type'] == 1) print img_object($langs->trans("ShowService"),"service");
	else print img_object($langs->trans("ShowProduct"),"product");
	print " ";
	print $vals['ref'].'</a></td>';
	print '<td>';
	if ($vals['type'] == 1) print $langs->trans("Service");
	else print $langs->trans("Product");
	print '</td>';
	print '<td>'.$vals['label'].'</td>';
	print '<td align="right">'.$vals['nblineproposal'].'</td>';
	print "</tr>\n";
	$i++;
}

print "</table>";



dol_fiche_end();


llxFooter();
$db->close();
