<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/expedition/list.php
 *      \ingroup    expedition
 *      \brief      Page to list all shipments
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

$langs->load("sendings");
$langs->load("deliveries");
$langs->load('companies');

$socid=GETPOST('socid','int');
// Security check
$expeditionid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition',$expeditionid,'');

$search_ref_exp = GETPOST("search_ref_exp");
$search_ref_liv = GETPOST('search_ref_liv');
$search_company = GETPOST("search_company");
$sall = GETPOST('sall');
$optioncss = GETPOST('optioncss','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;

if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="DESC";

$viewstatut=GETPOST('viewstatut');

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_ref_exp='';
    $search_ref_liv='';
    $search_company='';
    $viewstatut='';
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.ref'=>"Ref",
    's.nom'=>"ThirdParty"
);


/*
 * View
 */

$form=new Form($db);
$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ListOfSendings'),$helpurl);

$sql = "SELECT e.rowid, e.ref, e.date_expedition as date_expedition, e.date_delivery as date_livraison, l.date_delivery as date_reception, e.fk_statut";
$sql.= ", s.nom as socname, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."expedition as e";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON e.rowid = ee.fk_source AND ee.sourcetype = 'shipping' AND ee.targettype = 'delivery'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.rowid = ee.fk_target";
$sql.= " WHERE e.entity IN (".getEntity('expedition', 1).")";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= " AND e.fk_soc = sc.fk_soc";
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
	$sql.= " AND e.fk_soc = ".$socid;
}
if ($viewstatut <> '' && $viewstatut >= 0) {
	$sql.= " AND e.fk_statut = ".$viewstatut;
}
if ($search_ref_exp) $sql .= natural_search('e.ref', $search_ref_exp);
if ($search_ref_liv) $sql .= natural_search('l.ref', $search_ref_liv);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1,$offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$expedition = new Expedition($db);

	$param="";
	if ($search_ref_exp) $param.= "&amp;search_ref_exp=".$search_ref_exp;
	if ($search_ref_liv) $param.= "&amp;search_ref_liv=".$search_ref_liv;
	if ($search_company) $param.= "&amp;search_company=".$search_company;
	if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;

	print_barre_liste($langs->trans('ListOfSendings'), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num);


	$i = 0;
    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';

    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
    $moreforfilter='';
    
    print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';

	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"],"e.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"), $_SERVER["PHP_SELF"],"s.nom", "", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDeliveryPlanned"), $_SERVER["PHP_SELF"],"e.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
    if($conf->livraison_bon->enabled)
    {
		print_liste_field_titre($langs->trans("DeliveryOrder"), $_SERVER["PHP_SELF"],"l.ref","",$param, '',$sortfield,$sortorder);
    	print_liste_field_titre($langs->trans("DateReceived"), $_SERVER["PHP_SELF"],"l.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"],"e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref_exp" value="'.$search_ref_exp.'">';
    print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="10" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
	print '</td>';
	// Date delivery planned
	print '<td class="liste_titre">&nbsp;</td>';
    if ($conf->livraison_bon->enabled)
    {
    	// Delivery order
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref_liv" value="'.$search_ref_liv.'"';
		print '</td>';
		// Date received
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Status
	print '<td align="right">';
	print $form->selectarray('viewstatut', array('0'=>$langs->trans('StatusSendingDraftShort'),'1'=>$langs->trans('StatusSendingValidatedShort'),'2'=>$langs->trans('StatusSendingProcessedShort')),$viewstatut,1);
	print '</td>';
	// Search
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';

	print "</tr>\n";

	$var=True;

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);

		$var=!$var;

		print "<tr ".$bc[$var].">";

		// Ref
		print "<td>";
		$shipment->id=$objp->rowid;
		$shipment->ref=$objp->ref;
		print $shipment->getNomUrl(1);
		print "</td>\n";

		// Third party
		print '<td>';
		$companystatic->id=$objp->socid;
		$companystatic->ref=$objp->socname;
		$companystatic->name=$objp->socname;
		print $companystatic->getNomUrl(1);
		print '</td>';

		// Date delivery planed
		print "<td align=\"center\">";
		print dol_print_date($db->jdate($objp->date_livraison),"day");
		/*$now = time();
		if ( ($now - $db->jdate($objp->date_expedition)) > $conf->warnings->lim && $objp->statutid == 1 )
		{
		}*/
		print "</td>\n";

        if ($conf->livraison_bon->enabled)
        {
		    $shipment->fetchObjectLinked($shipment->id,$shipment->element);
            $receiving='';
            if (count($shipment->linkedObjects['delivery']) > 0) $receiving=reset($shipment->linkedObjects['delivery']);

        	// Ref
            print '<td>';
            print !empty($receiving) ? $receiving->getNomUrl($db) : '';
            print '</td>';
			// Date received
        	print '<td align="center">';
			print dol_print_date($db->jdate($objp->date_reception),"day");
			print '</td>'."\n";
		}

		print '<td align="right">'.$expedition->LibStatut($objp->fk_statut,5).'</td>';

		print '<td></td>';

		print "</tr>\n";

		$i++;
	}

	print "</table>";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
