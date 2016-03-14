<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Philippe Grand		<philippe.grand@atoo-net.com>
 * Copyright (C) 2013	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015	   juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015 	   Abbes Bahfir 	<bafbes@gmail.com>
 * Copyright (C) 2015-2016 Ferran Marcet		<fmarcet@2byte.es>
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
 *       \file       htdocs/fourn/facture/list.php
 *       \ingroup    fournisseur,facture
 *       \brief      List of suppliers invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

if (!$user->rights->fournisseur->facture->lire) accessforbidden();

$langs->load("companies");
$langs->load("bills");

$socid = GETPOST('socid','int');

// Security check
if ($user->societe_id > 0)
{
	$action='';
    $_GET["action"] = '';
	$socid = $user->societe_id;
}

$mode=GETPOST("mode");
$modesearch=GETPOST("mode_search");

$page=GETPOST("page",'int');
$sortorder = GETPOST("sortorder",'alpha');
$sortfield = GETPOST("sortfield",'alpha');

if ($page == -1) { $page = 0 ; }
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="fac.datef,fac.rowid";

$search_all = GETPOST('sall');
$search_ref = GETPOST("search_ref","int");
$search_ref_supplier = GETPOST("search_ref_supplier","alpha");
$search_label = GETPOST("search_label","alpha");
$search_company = GETPOST("search_company","alpha");
$search_amount_no_tax = GETPOST("search_amount_no_tax","alpha");
$search_amount_all_tax = GETPOST("search_amount_all_tax","alpha");
$search_status=GETPOST('search_status','alpha');
$day = GETPOST("day","int");
$month = GETPOST("month","int");
$year = GETPOST("year","int");
$day_lim	= GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$filter = GETPOST("filtre");
$optioncss = GETPOST('optioncss','alpha');

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter"))		// Both test must be present to be compatible with all browsers
{
    $search_all="";
	$search_ref="";
	$search_ref_supplier="";
	$search_label="";
	$search_company="";
	$search_amount_no_tax="";
	$search_amount_all_tax="";
	$search_status="";
	$year="";
	$month="";
	$day="";
	$year_lim="";
	$month_lim="";
	$day_lim="";
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'fac.ref'=>'Ref',
    'fac.ref_supplier'=>'RefSupplier',
    //'fd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'fac.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["fac.note_private"]="NotePrivate";



/*
 * Actions
 */

if ($mode == 'search')
{
	if ($modesearch == 'soc')
	{
		$sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s ";
		$sql.= " WHERE s.nom LIKE '%".$db->escape($socname)."%'";
		$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
	}

    $resql=$db->query($sql);
	if ($resql)
	{
		if ( $db->num_rows($resql) == 1)
		{
			$obj = $db->fetch_object($resql);
			$socid = $obj->rowid;
		}
		$db->free($resql);
	}
}

/*
 * View
 */

$now=dol_now();
$form=new Form($db);
$formother=new FormOther($db);
$formfile = new FormFile($db);

llxHeader('',$langs->trans("SuppliersInvoices"),'EN:Suppliers_Invoices|FR:FactureFournisseur|ES:Facturas_de_proveedores');

$sql = "SELECT s.rowid as socid, s.nom as name, ";
$sql.= " fac.rowid as facid, fac.ref, fac.ref_supplier, fac.datef, fac.date_lim_reglement as date_echeance,";
$sql.= " fac.total_ht, fac.total_ttc, fac.paye as paye, fac.fk_statut as fk_statut, fac.libelle,";
$sql.= " p.rowid as project_id, p.ref as project_ref";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as fac";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = fac.fk_projet";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE fac.entity = ".$conf->entity;
$sql.= " AND fac.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
{
	$sql .= " AND s.rowid = ".$socid;
}
if ($search_all)
{
    $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($search_ref)
{
	if (is_numeric($search_ref)) $sql .= natural_search(array('fac.ref'), $search_ref);
	else $sql .= natural_search('fac.ref', $search_ref);
}
if ($search_ref_supplier)
{
	$sql .= natural_search('fac.ref_supplier', $search_ref_supplier);
}
if ($month > 0)
{
	if ($year > 0 && empty($day))
	$sql.= " AND fac.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else if ($year > 0 && ! empty($day))
		$sql.= " AND fac.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
	else
	$sql.= " AND date_format(fac.datef, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND fac.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($month_lim > 0)
{
	if ($year_lim > 0 && empty($day_lim))
		$sql.= " AND fac.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
	else if ($year_lim > 0 && ! empty($day_lim))
		$sql.= " AND fac.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
	else
		$sql.= " AND date_format(fac.date_lim_reglement, '%m') = '".$month_lim."'";
}
else if ($year_lim > 0)
{
	$sql.= " AND fac.datef BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
}
if ($search_label)
{
    $sql .= natural_search('fac.libelle', $search_label);
}

if ($search_company)
{
    $sql .= natural_search('s.nom', $search_company);
}

if ($search_amount_no_tax != '')
{
	$sql .= natural_search('fac.total_ht', $search_amount_no_tax, 1);
}

if ($search_amount_all_tax != '')
{
	$sql .= natural_search('fac.total_ttc', $search_amount_all_tax, 1);
}

if ($search_status != '')
{
	$sql.= " AND fac.fk_statut = ".$search_status;
}

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}


$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	if ($socid) {
		$soc = new Societe($db);
		$soc->fetch($socid);
	}

	$param='&socid='.$socid;
	if ($day) 					$param.='&day='.urlencode($day);
	if ($month) 				$param.='&month='.urlencode($month);
	if ($year)  				$param.='&year=' .urlencode($year);
	if ($day_lim) 				$param.='&day_lim='.urlencode($day_lim);
	if ($month_lim) 			$param.='&month_lim='.urlencode($month_lim);
	if ($year_lim)  			$param.='&year_lim=' .urlencode($year_lim);
	if ($search_ref)          	$param.='&search_ref='.urlencode($search_ref);
	if ($search_ref_supplier) 	$param.='&search_ref_supplier'.urlencode($search_ref_supplier);
	if ($search_label)      	$param.='&search_label='.urlencode($search_label);
	if ($search_company)      	$param.='&search_company='.urlencode($search_company);
	if ($search_amount_no_tax)	$param.='&search_amount_no_tax='.urlencode($search_amount_no_tax);
	if ($search_amount_all_tax)	$param.='&search_amount_all_tax='.urlencode($search_amount_all_tax);
	if ($filter && $filter != -1) $param.='&filtre='.urlencode($filter);
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	if ($search_status >= 0)  	$param.="&search_status=".$search_status;

	print_barre_liste($langs->trans("BillsSuppliers").($socid?" $soc->name.":""),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy');
	
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

    if ($search_all)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall);
    }
    
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"fac.ref,fac.rowid","",$param,"",$sortfield,$sortorder);
	if (empty($conf->global->SUPPLIER_INVOICE_HIDE_REF_SUPPLIER)) print_liste_field_titre($langs->trans("RefSupplier"),$_SERVER["PHP_SELF"],"ref_supplier","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"fac.datef,fac.rowid","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"fac.date_lim_reglement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"fac.libelle","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS)) print_liste_field_titre($langs->trans("Project"),$_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"fac.total_ht","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"fac.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Line for filters

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	if (empty($conf->global->SUPPLIER_INVOICE_HIDE_REF_SUPPLIER))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" size="6" type="text" name="search_ref_supplier" value="'.$search_ref_supplier.'">';
		print '</td>';
	}
	print '<td class="liste_titre" colspan="1" align="center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	$formother->select_year($year?$year:-1,'year',1, 20, 5);
	print '</td>';
	print '<td class="liste_titre" colspan="1" align="center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day_lim" value="'.$day_lim.'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month_lim" value="'.$month_lim.'">';
	$formother->select_year($year_lim?$year_lim:-1,'year_lim',1, 20, 5);
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" size="16" type="text" name="search_label" value="'.$search_label.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="8" name="search_company" value="'.$search_company.'">';
	print '</td>';
	if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	print '<td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="6" name="search_amount_no_tax" value="'.$search_amount_no_tax.'">';
	print '</td><td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="6" name="search_amount_all_tax" value="'.$search_amount_all_tax.'">';
	print '</td><td class="liste_titre" align="right">';
	$liststatus=array('0'=>$langs->trans("Draft"),'1'=>$langs->trans("Unpaid"), '2'=>$langs->trans("Paid"));
	print $form->selectarray('search_status', $liststatus, $search_status, 1);
	print '</td><td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";

	$facturestatic=new FactureFournisseur($db);
	$supplierstatic=new Fournisseur($db);
	$projectstatic=new Project($db);

	$var=true;
	$total=0;
	$total_ttc=0;
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$facturestatic->date_echeance = $db->jdate($obj->date_echeance);
		$facturestatic->statut = $obj->fk_statut;

		$var=!$var;

		print "<tr ".$bc[$var].">";

		print '<td class="nowrap">';
		$facturestatic->id=$obj->facid;
		$facturestatic->ref=$obj->ref;
		$facturestatic->ref_supplier=$obj->ref_supplier;
		print $facturestatic->getNomUrl(1);
		$filename=dol_sanitizeFileName($obj->ref);
		$filedir=$conf->fournisseur->facture->dir_output.'/'.get_exdir($obj->facid,2,0,0,$facturestatic,'invoice_supplier').dol_sanitizeFileName($obj->ref);
		print $formfile->getDocumentsLink('facture_fournisseur', $filename, $filedir);
		print "</td>\n";

		// Ref supplier
		if (empty($conf->global->SUPPLIER_INVOICE_HIDE_REF_SUPPLIER)) print '<td class="nowrap">'.$obj->ref_supplier."</td>";

		print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->datef),'day').'</td>';
		print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->date_echeance),'day');
		if ($facturestatic->hasDelay()) {
			print img_picto($langs->trans("Late"),"warning");
		}
		print '</td>';
		print '<td>'.dol_trunc($obj->libelle,36).'</td>';
		print '<td>';
		$supplierstatic->id=$obj->socid;
		$supplierstatic->name=$obj->name;
		print $supplierstatic->getNomUrl(1,'',12);
		print '</td>';
		if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS))
		{
			$projectstatic->id=$obj->project_id;
			$projectstatic->ref=$obj->project_ref;
			print '<td>';
			if ($obj->project_id > 0) print $projectstatic->getNomUrl(1);
			print '</td>';
		}
		print '<td align="right">'.price($obj->total_ht).'</td>';
		print '<td align="right">'.price($obj->total_ttc).'</td>';
		$total+=$obj->total_ht;
		$total_ttc+=$obj->total_ttc;

		// Status
		print '<td align="right" class="nowrap">';
		// TODO  le montant deja paye objp->am n'est pas definie
		//print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5,$objp->am);
		print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5);
		print '</td>';

		print '<td align="center">&nbsp;</td>';

		print "</tr>\n";
		$i++;

		if ($i == min($num,$limit))
		{
			$rowspan=5;
			if (empty($conf->global->SUPPLIER_INVOICE_HIDE_REF_SUPPLIER)) $rowspan++;

			// Print total
			print '<tr class="liste_total">';
			print '<td class="liste_total" colspan="'.$rowspan.'" align="left">'.$langs->trans("Total").'</td>';
			if (! empty($conf->global->PROJECT_SHOW_REF_INTO_LISTS)) print '<td></td>';
			print '<td class="liste_total" align="right">'.price($total).'</td>';
			print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
			print '<td class="liste_total" align="center">&nbsp;</td>';
			print '<td class="liste_total" align="center">&nbsp;</td>';
			print "</tr>\n";
		}
	}

	print "</table>\n";
	print "</form>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
