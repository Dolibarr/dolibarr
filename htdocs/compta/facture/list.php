<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       htdocs/compta/facture/list.php
 *	\ingroup    facture
 *	\brief      Page to create/see an invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer=GETPOST('search_refcustomer','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');
$search_status=GETPOST('search_status','int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';
$limit = $conf->liste_limit;

$pageprev = $page - 1;
$pagenext = $page + 1;

$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$day_lim	= GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$filtre	= GETPOST('filtre');

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

$object=new Facture($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicelist'));

$now=dol_now();


/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_user='';
    $search_sale='';
    $search_product_category='';
    $search_ref='';
    $search_refcustomer='';
    $search_societe='';
    $search_montant_ht='';
    $search_montant_ttc='';
    $search_status='';
    $day='';
    $year='';
    $month='';
    $day_lim='';
    $year_lim='';
    $month_lim='';
}


/*
 * View
 */

llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$facturestatic=new Facture($db);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
$sql.= ' f.paye as paye, f.fk_statut,';
$sql.= ' s.nom as name, s.rowid as socid, s.code_client, s.client ';
if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE f.fk_soc = s.rowid';
$sql.= " AND f.entity = ".$conf->entity;
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($userid)
{
    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
    else $sql.=' AND f.fk_user_author = '.$userid;
}
if ($filtre)
{
    $aFilter = explode(',', $filtre);
    foreach ($aFilter as $filter)
    {
        $filt = explode(':', $filter);
        $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
    }
}
if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(f.datef, '%m') = '".$month."'";
}
else if ($year > 0)
{
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($month_lim > 0)
{
	if ($year_lim > 0 && empty($day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
	else if ($year_lim > 0 && ! empty($day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
	else
		$sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$month_lim."'";
}
else if ($year_lim > 0)
{
	$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
if (! $sall)
{
    $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.increment, f.total, f.tva, f.total_ttc,';
    $sql.= ' f.datef, f.date_lim_reglement,';
    $sql.= ' f.paye, f.fk_statut,';
    $sql.= ' s.nom, s.rowid, s.code_client, s.client';
}
else
{
    $sql .= natural_search(array('s.nom', 'f.facnumber', 'f.note_public', 'fd.description'), $sall);
}
$sql.= ' ORDER BY ';
$listfield=explode(',',$sortfield);
foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
$sql.= ' f.rowid DESC ';

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1,$offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    if ($socid)
    {
        $soc = new Societe($db);
        $soc->fetch($socid);
    }

    $param='&socid='.$socid;
    if ($day)                $param.='&day='.$day;
    if ($month)              $param.='&month='.$month;
    if ($year)               $param.='&year=' .$year;
    if ($day_lim)            $param.='&day_lim='.$day_lim;
    if ($month_lim)          $param.='&month_lim='.$month_lim;
    if ($year_lim)           $param.='&year_lim=' .$year_lim;
    if ($search_ref)         $param.='&search_ref=' .$search_ref;
    if ($search_refcustomer) $param.='&search_refcustomer=' .$search_refcustomer;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
    if ($search_sale > 0)    $param.='&search_sale=' .$search_sale;
    if ($search_user > 0)    $param.='&search_user=' .$search_user;
    if ($search_product_category > 0)   $param.='$search_product_category=' .$search_product_category;
    if ($search_montant_ht != '')  $param.='&search_montant_ht='.$search_montant_ht;
    if ($search_montant_ttc != '') $param.='&search_montant_ttc='.$search_montant_ttc;
	if ($search_status != '') $param.='&search_status='.$search_status;
    print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->name:''),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy.png');

    $i = 0;
    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<table class="liste" width="100%">';

 	// If the user can view prospects other than his'
    $moreforfilter='';
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
 		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
	 	$moreforfilter.='</div>';
 	}
    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
		$moreforfilter.='<div class="divsearchfield">';
    	$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
        $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	 	$moreforfilter.='</div>';
    }
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, '', 1);
		$moreforfilter.='</div>';
	}

    if ($moreforfilter)
    {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="11">';
        print $moreforfilter;
        print '</td></tr>';
    }

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateDue"),$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('ThirdParty'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountVAT'),$_SERVER['PHP_SELF'],'f.tva','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

    // Filters lines
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
	print '</td>';
    print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    $formother->select_year($year?$year:-1,'year',1, 20, 5);
    print '</td>';
 	print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day_lim" value="'.$day_lim.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month_lim" value="'.$month_lim.'">';
    $formother->select_year($year_lim?$year_lim:-1,'year_lim',1, 20, 5);
    print '</td>';
    print '<td class="liste_titre" align="left"><input class="flat" type="text" size="8" name="search_societe" value="'.$search_societe.'"></td>';
    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_montant_ht" value="'.$search_montant_ht.'"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_montant_ttc" value="'.$search_montant_ttc.'"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre" align="right">';
	$liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
	print $form->selectarray('search_status', $liststatus, $search_status, 1);
    print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td></tr>\n";

    if ($num > 0)
    {
        $var=true;
        $total_ht=0;
        $total_tva=0;
        $total_ttc=0;
        $totalrecu=0;

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;

            $datelimit=$db->jdate($objp->datelimite);

            print '<tr '.$bc[$var].'>';
            print '<td class="nowrap">';

            $facturestatic->id=$objp->facid;
            $facturestatic->ref=$objp->facnumber;
            $facturestatic->type=$objp->type;
            $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$objp->note_public:$objp->note),1);
            $paiement = $facturestatic->getSommePaiement();

            print '<table class="nobordernopadding"><tr class="nocellnopadd">';

            print '<td class="nobordernopadding nowrap">';
            print $facturestatic->getNomUrl(1,'',200,0,$notetoshow);
            print $objp->increment;
            print '</td>';

            print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
            if (! empty($objp->note_private))
            {
				print ' <span class="note">';
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$objp->facid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
				print '</span>';
			}
            $filename=dol_sanitizeFileName($objp->facnumber);
            $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
            $urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->facid;
            print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
			print '</td>';
            print '</tr>';
            print '</table>';

            print "</td>\n";

			// Customer ref
			print '<td class="nowrap">';
			print $objp->ref_client;
			print '</td>';

			// Date
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($objp->df),'day');
            print '</td>';

            // Date limit
            print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
            if ($datelimit < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $paiement)
            {
                print img_warning($langs->trans('Late'));
            }
            print '</td>';

            print '<td>';
            $thirdparty=new Societe($db);
            $thirdparty->id=$objp->socid;
            $thirdparty->name=$objp->name;
            $thirdparty->client=$objp->client;
            $thirdparty->code_client=$objp->code_client;
            print $thirdparty->getNomUrl(1,'customer');
            print '</td>';

            print '<td align="right">'.price($objp->total_ht,0,$langs).'</td>';

            print '<td align="right">'.price($objp->total_tva,0,$langs).'</td>';

            print '<td align="right">'.price($objp->total_ttc,0,$langs).'</td>';

            print '<td align="right">'.(! empty($paiement)?price($paiement,0,$langs):'&nbsp;').'</td>';

            // Affiche statut de la facture
            print '<td align="right" class="nowrap">';
            print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
            print "</td>";

            print "<td></td>";

            print "</tr>\n";
            $total_ht+=$objp->total_ht;
            $total_tva+=$objp->total_tva;
            $total_ttc+=$objp->total_ttc;
            $totalrecu+=$paiement;
            $i++;
        }

        if (($offset + $num) <= $limit)
        {
            // Print total
            print '<tr class="liste_total">';
            print '<td class="liste_total" colspan="5" align="left">'.$langs->trans('Total').'</td>';
            print '<td class="liste_total" align="right">'.price($total_ht,0,$langs).'</td>';
            print '<td class="liste_total" align="right">'.price($total_tva,0,$langs).'</td>';
            print '<td class="liste_total" align="right">'.price($total_ttc,0,$langs).'</td>';
            print '<td class="liste_total" align="right">'.price($totalrecu,0,$langs).'</td>';
            print '<td class="liste_total"></td>';
            print '<td class="liste_total"></td>';
            print '</tr>';
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
