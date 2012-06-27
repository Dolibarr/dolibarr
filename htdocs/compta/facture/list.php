<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
if ($conf->projet->enabled)
{
	require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
}

$langs->load('bills');
//print 'ee'.$langs->trans('BillsCustomer');exit;

$langs->load('companies');
$langs->load('products');
$langs->load('main');

if (GETPOST('mesg','int',1) && isset($_SESSION['message'])) $mesg=$_SESSION['message'];

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');

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
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

$object=new Facture($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('invoicecard'));

$now=dol_now();


/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_categ='';
    $search_user='';
    $search_sale='';
    $search_ref='';
    $search_refcustomer='';
    $search_societe='';
    $search_montant_ht='';
    $year='';
    $month='';
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

if (! $sall) $sql = 'SELECT';
else $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as facid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
$sql.= ' f.paye as paye, f.fk_statut, f.note,';
$sql.= ' s.nom, s.rowid as socid';
if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
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
if ($socid) $sql.= ' AND s.rowid = '.$socid;
if ($userid)
{
    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
    else $sql.=' AND f.fk_user_author = '.$userid;
}
if ($_GET['filtre'])
{
    $filtrearr = explode(',', $_GET['filtre']);
    foreach ($filtrearr as $fil)
    {
        $filt = explode(':', $fil);
        $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
    }
}
if ($search_ref)
{
    $sql.= ' AND f.facnumber LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
}
if ($search_societe)
{
    $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($search_societe)).'%\'';
}
if ($search_montant_ht)
{
    $sql.= ' AND f.total = \''.$db->escape(trim($search_montant_ht)).'\'';
}
if ($search_montant_ttc)
{
    $sql.= ' AND f.total_ttc = \''.$db->escape(trim($search_montant_ttc)).'\'';
}
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
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
if (! $sall)
{
    $sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
    $sql.= ' f.datef, f.date_lim_reglement,';
    $sql.= ' f.paye, f.fk_statut, f.note,';
    $sql.= ' s.nom, s.rowid';
}
else
{
	$sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.facnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
}
$sql.= ' ORDER BY ';
$listfield=explode(',',$sortfield);
foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
$sql.= ' f.rowid DESC ';
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
    if ($month)              $param.='&month='.$month;
    if ($year)               $param.='&year=' .$year;
    if ($search_ref)         $param.='&search_ref=' .$search_ref;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
    if ($search_sale > 0)    $param.='&search_sale=' .$search_sale;
    if ($search_user > 0)    $param.='&search_user=' .$search_user;
    if ($search_montant_ht)  $param.='&search_montant_ht='.$search_montant_ht;
    if ($search_montant_ttc) $param.='&search_montant_ttc='.$search_montant_ttc;
    print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->nom:''),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num);

    $i = 0;
    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<table class="liste" width="100%">';

 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
	 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
 	}
    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
        $moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
        $moreforfilter.=$form->select_dolusers($search_user,'search_user',1);
    }
    if ($moreforfilter)
    {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="9">';
        print $moreforfilter;
        print '</td></tr>';
    }

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateDue"),$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Company'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
    //print '<td class="liste_titre">&nbsp;</td>';
    print '</tr>';

    // Filters lines
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
    print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    $formother->select_year($year?$year:-1,'year',1, 20, 5);
    print '</td>';
    print '<td class="liste_titre" align="left">&nbsp;</td>';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="search_societe" value="'.$search_societe.'">';
    print '</td><td class="liste_titre" align="right">';
    print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
    print '</td><td class="liste_titre" align="right">';
    print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print "</td></tr>\n";

    if ($num > 0)
    {
        $var=True;
        $total=0;
        $totalrecu=0;

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;

            $datelimit=$db->jdate($objp->datelimite);

            print '<tr '.$bc[$var].'>';
            print '<td nowrap="nowrap">';

            $facturestatic->id=$objp->facid;
            $facturestatic->ref=$objp->facnumber;
            $facturestatic->type=$objp->type;
            $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$objp->note_public:$objp->note),1);
            $paiement = $facturestatic->getSommePaiement();

            print '<table class="nobordernopadding"><tr class="nocellnopadd">';

            print '<td class="nobordernopadding" nowrap="nowrap">';
            print $facturestatic->getNomUrl(1,'',200,0,$notetoshow);
            print $objp->increment;
            print '</td>';

            print '<td width="16" align="right" class="nobordernopadding">';
            $filename=dol_sanitizeFileName($objp->facnumber);
            $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
            $urlsource=$_SERVER['PHP_SELF'].'?facid='.$objp->facid;
            $formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','',1,'',1);
            print '</td>';
            print '</tr>';
            print '</table>';

            print "</td>\n";

            // Date
            print '<td align="center" nowrap>';
            print dol_print_date($db->jdate($objp->df),'day');
            print '</td>';

            // Date limit
            print '<td align="center" nowrap="1">'.dol_print_date($datelimit,'day');
            if ($datelimit < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $paiement)
            {
                print img_warning($langs->trans('Late'));
            }
            print '</td>';

            print '<td>';
            $thirdparty=new Societe($db);
            $thirdparty->id=$objp->socid;
            $thirdparty->nom=$objp->nom;
            print $thirdparty->getNomUrl(1,'customer');
            print '</td>';

            print '<td align="right">'.price($objp->total).'</td>';

            print '<td align="right">'.price($objp->total_ttc).'</td>';

            print '<td align="right">'.price($paiement).'</td>';

            // Affiche statut de la facture
            print '<td align="right" nowrap="nowrap">';
            print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
            print "</td>";
            //print "<td>&nbsp;</td>";
            print "</tr>\n";
            $total+=$objp->total;
            $total_ttc+=$objp->total_ttc;
            $totalrecu+=$paiement;
            $i++;
        }

        if (($offset + $num) <= $limit)
        {
            // Print total
            print '<tr class="liste_total">';
            print '<td class="liste_total" colspan="4" align="left">'.$langs->trans('Total').'</td>';
            print '<td class="liste_total" align="right">'.price($total).'</td>';
            print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
            print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
            print '<td class="liste_total" align="center">&nbsp;</td>';
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
?>
