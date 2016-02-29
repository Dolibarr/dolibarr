<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
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
 *   \file       htdocs/fourn/commande/list.php
 *   \ingroup    fournisseur
 *   \brief      List of suppliers orders
 */


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load("orders");
$langs->load("sendings");


$sall=GETPOST('search_all');
$search_ref=GETPOST('search_ref');
$search_refsupp=GETPOST('search_refsupp');
$search_company=GETPOST('search_company');
$search_user=GETPOST('search_user');
$search_ht=GETPOST('search_ht');
$search_ttc=GETPOST('search_ttc');
$search_status=(GETPOST('search_status','alpha')!=''?GETPOST('search_status','alpha'):GETPOST('statut','alpha'));	// alpha and not intbecause it can be '6,7'
$optioncss = GETPOST('optioncss','alpha');

$page  = GETPOST('page','int');
$socid = GETPOST('socid','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');

$status=GETPOST('statut','alpha');
$billed=GETPOST('billed','int');
$viewstatut=GETPOST('viewstatut');

// Security check
$orderid = GETPOST('orderid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $orderid, '', 'commande');

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_ref='';
    $search_refsupp='';
    $search_company='';
	$search_user='';
	$search_ht='';
	$search_ttc='';
	$search_status='';
	$billed='';
}

if ($search_status == '') $search_status=-1;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'cf.ref'=>'Ref',
    'cf.ref_supplier'=>'RefSupplier',
    //'pd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'cf.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["cf.note_private"]="NotePrivate";



/*
 *	View
 */

$form=new Form($db);
$thirdpartytmp = new Fournisseur($db);
$commandestatic=new CommandeFournisseur($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);

$title = $langs->trans("SuppliersOrders");
if ($socid > 0)
{
	$fourn = new Fournisseur($db);
	$fourn->fetch($socid);
	$title .= ' - '.$fourn->name;
}
if ($status) 
{
    if ($status == '1,2,3') $title.=' - '.$langs->trans("StatusOrderToProcessShort");
    if ($status == '6,7') $title.=' - '.$langs->trans("StatusOrderCanceled");
    else $title.=' - '.$langs->trans($commandestatic->statuts[$status]);
}
if ($billed) $title.=' - '.$langs->trans("Billed");


llxHeader('',$title);


if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="cf.date_creation";
$offset = $conf->liste_limit * $page ;


/*
 * Mode list
 */

$sql = "SELECT s.rowid as socid, s.nom as name, cf.date_commande as dc,";
$sql.= " cf.rowid, cf.ref, cf.ref_supplier, cf.fk_statut, cf.billed, cf.total_ht, cf.tva as total_tva, cf.total_ttc, cf.fk_user_author, cf.date_livraison,";
$sql.= " p.rowid as project_id, p.ref as project_ref,";
$sql.= " u.firstname,";
$sql.= " u.lastname,";
$sql.= " u.photo,";
$sql.= " u.login";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = cf.fk_projet";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cf.fk_soc = s.rowid ";
$sql.= " AND cf.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_ref)
{
	$sql .= natural_search('cf.ref', $search_ref);
}
if ($search_company)
{
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_user)
{
	$sql.= " AND u.login LIKE '%".$db->escape($search_user)."%'";
}
if ($search_ht != '')
{
	$sql .= natural_search("cf.total_ht",$search_ht, 1);
}
if ($search_ttc != '')
{
	$sql .= natural_search("cf.total_ttc", $search_ttc, 1);
}
if ($sall)
{
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($socid) $sql.= " AND s.rowid = ".$socid;

//Required triple check because statut=0 means draft filter
if (GETPOST('statut', 'alpha') !== '')
{
	$sql .= " AND cf.fk_statut IN (".GETPOST('statut', 'alpha').")";
}

if ($billed !== '')
{
	$sql .= " AND cf.billed = ".$billed;
}

if ($search_refsupp)
{
	$sql.= " AND (cf.ref_supplier LIKE '%".$db->escape($search_refsupp)."%')";
}
if ($search_status != '' && $search_status >= 0)
{
	if (strstr($search_status, ',')) $sql.=" AND cf.fk_statut IN (".$db->escape($search_status).")";
	else $sql.=" AND cf.fk_statut = ".$search_status;
}

$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{

	$num = $db->num_rows($resql);
	$i = 0;

	$param="";
	if ($search_ref)			$param.="&search_ref=".$search_ref;
	if ($search_company)		$param.="&search_company=".$search_company;
	if ($search_user)			$param.="&search_user=".$search_user;
	if ($search_ttc)			$param.="&search_ttc=".$search_ttc;
	if ($search_refsupp) 		$param.="&search_refsupp=".$search_refsupp;
	if ($socid)					$param.="&socid=".$socid;
	if ($search_status >= 0)  	$param.="&search_status=".$search_status;
	if ($billed != '')          $param.="billed=".$billed; 
	if ($optioncss != '') $param.='&optioncss='.$optioncss;

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	
    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"cf.ref","",$param,'',$sortfield,$sortorder);
	if (empty($conf->global->SUPPLIER_ORDER_HIDE_REF_SUPPLIER)) print_liste_field_titre($langs->trans("RefSupplier"),$_SERVER["PHP_SELF"],"cf.ref_supplier","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS)) print_liste_field_titre($langs->trans("Project"),$_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.login","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"cf.total_ht","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"cf.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("OrderDate"),$_SERVER["PHP_SELF"],"dc","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DateDeliveryPlanned'),$_SERVER["PHP_SELF"],'cf.date_livraison','',$param, 'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"cf.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Billed'),$_SERVER["PHP_SELF"],'cf.billed','',$param,'align="center"',$sortfield,$sortorder,'');
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre"><input size="8" type="text" class="flat" name="search_ref" value="'.$search_ref.'"></td>';
	if (empty($conf->global->SUPPLIER_ORDER_HIDE_REF_SUPPLIER)) print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_refsupp" value="'.$search_refsupp.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_company" value="'.$search_company.'"></td>';
	if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	print '<td class="liste_titre"><input type="text" size="6" class="flat" name="search_user" value="'.$search_user.'"></td>';
	print '<td class="liste_titre" align="right"><input type="text" size="6" class="flat" name="search_ht" value="'.$search_ht.'"></td>';
	print '<td class="liste_titre" align="right"><input type="text" size="6" class="flat" name="search_ttc" value="'.$search_ttc.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	$formorder->selectSupplierOrderStatus((strstr($search_status, ',')?-1:$search_status),1,'search_status');
	print '</td>';
	print '<td align="center">';
	print $form->selectyesno('billed', $billed, 1, 0, 1);
	print '</td>';
	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print "</td></tr>\n";

	$var=true;

	$userstatic = new User($db);
	$objectstatic=new CommandeFournisseur($db);
	$projectstatic=new Project($db);

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
        $objectstatic->id=$obj->rowid;
        $objectstatic->ref=$obj->ref;
        $objectstatic->ref_supplier = $obj->ref_supplier;
        $objectstatic->total_ht = $obj->total_ht;
        $objectstatic->total_tva = $obj->total_tva;
        $objectstatic->total_ttc = $obj->total_ttc;

		print "<tr ".$bc[$var].">";

		// Ref
        print '<td class="nobordernopadding nowrap">';
        print $objectstatic->getNomUrl(1);
		$filename=dol_sanitizeFileName($obj->ref);
		$filedir=$conf->fournisseur->dir_output.'/commande' . '/' . dol_sanitizeFileName($obj->ref);
		print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		print '</td>'."\n";

		// Ref Supplier
			if (empty($conf->global->SUPPLIER_ORDER_HIDE_REF_SUPPLIER)) print '<td>'.$obj->ref_supplier.'</td>'."\n";

		// Thirdparty
		print '<td>';
		$thirdpartytmp->id = $obj->socid;
		$thirdpartytmp->name = $obj->name;
		print $thirdpartytmp->getNomUrl(1,'supplier');
		print '</td>'."\n";

		// Project
		if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS))
		{
			$projectstatic->id=$obj->project_id;
			$projectstatic->ref=$obj->project_ref;
			print '<td>';
			if ($obj->project_id > 0) print $projectstatic->getNomUrl(1);
			print '</td>';
		}

        // Author
        $userstatic->id = $obj->fk_user_author;
        $userstatic->lastname = $obj->lastname;
        $userstatic->firstname = $obj->firstname;
        $userstatic->login = $obj->login;
        $userstatic->photo = $obj->photo;
		print "<td>";
		if ($userstatic->id) print $userstatic->getNomUrl(1);
		else print "&nbsp;";
		print "</td>";

		// Amount net
		print '<td align="right" width="100">'.price($obj->total_ht)."</td>";

		// Amount with tax
		print '<td align="right" width="100">'.price($obj->total_ttc)."</td>";

		// Date
		print "<td align=\"center\" width=\"100\">";
		if ($obj->dc)
		{
			print dol_print_date($db->jdate($obj->dc),"day");
		}
		else
		{
			print "-";
		}
		print '</td>';

		// Delivery date
		print '<td align="right">';
		print dol_print_date($db->jdate($obj->date_livraison), 'day');
		print '</td>';

		// Statut
		print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut, 5).'</td>';

		// Billed
		print '<td align="center">'.yn($obj->billed).'</td>';
		
		print '<td></td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>\n";
	print "</form>\n";

	if (! empty($conf->facture->enable)) print '<br>'.img_help(1,'').' '.$langs->trans("ToBillSeveralOrderSelectCustomer", $langs->transnoentitiesnoconv("CreateInvoiceForThisCustomer")).'<br>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
