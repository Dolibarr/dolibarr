<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
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
 *	\file       	htdocs/supplier_proposal/list.php
 *	\ingroup    	supplier_proposal
 *	\brief      	Page of supplier proposals card and list
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsupplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load('companies');
$langs->load('supplier_proposal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

$socid=GETPOST('socid','int');

$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_author=GETPOST('search_author','alpha');
$viewstatut=$db->escape(GETPOST('viewstatut'));
$object_statut=$db->escape(GETPOST('supplier_proposal_statut'));

$sall=GETPOST("sall");
$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));
$year=GETPOST("year");
$month=GETPOST("month");

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

// Security check
$module='supplier_proposal';
$dbtable='';
$objectid='';
if (! empty($user->societe_id))	$socid=$user->societe_id;
if (! empty($socid))
{
	$objectid=$socid;
	$module='societe';
	$dbtable='&societe';
}
$result = restrictedArea($user, $module, $objectid, $dbtable);

if (GETPOST("button_removefilter") || GETPOST("button_removefilter_x"))	// Both tests are required to be compatible with all browsers
{
    $search_categ='';
    $search_user='';
    $search_sale='';
    $search_ref='';
    $search_societe='';
    $search_montant_ht='';
    $search_author='';
    $year='';
    $month='';
	$viewstatut='';
	$object_statut='';
}

if($object_statut != '')
$viewstatut=$object_statut;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'p.ref'=>'Ref',
    's.nom'=>'Supplier',
    'pd.description'=>'Description',
    'p.note_private'=>"NotePrivate",
    'p.note_public'=>'NotePublic',
);



// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('supplier_proposallist'));



/*
 * Actions
 */


$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

llxHeader('',$langs->trans('CommRequest'),'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formsupplier_proposal = new FormSupplierProposal($db);
$companystatic=new Societe($db);

$now=dol_now();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortfield) $sortfield='p.date_livraison';
if (! $sortorder) $sortorder='DESC';


$sql = 'SELECT s.rowid, s.nom as name, s.town, s.client, s.code_client,';
$sql.= ' p.rowid as supplier_proposalid, p.note_private, p.total_ht, p.ref, p.fk_statut, p.fk_user_author, p.date_livraison as dp,';
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " sc.fk_soc, sc.fk_user,";
$sql.= ' u.login';
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'supplier_proposal as p';
if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'supplier_proposaldet as pd ON p.rowid=pd.fk_supplier_proposal';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as c";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE p.fk_soc = s.rowid';
$sql.= ' AND p.entity = '.$conf->entity;
if (! $user->rights->societe->client->voir && ! $socid) //restriction
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($search_ref) {
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_societe) {
	$sql .= natural_search('s.nom', $search_societe);
}
if ($search_author)
{
	$sql.= " AND u.login LIKE '%".$db->escape(trim($search_author))."%'";
}
if ($search_montant_ht)
{
	$sql.= " AND p.total_ht='".$db->escape(price2num(trim($search_montant_ht)))."'";
}
if ($sall) {
    $sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($socid) $sql.= ' AND s.rowid = '.$socid;
if ($viewstatut <> '')
{
	$sql.= ' AND p.fk_statut IN ('.$viewstatut.')';
}
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND p.date_livraison BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND p.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(p.date_livraison, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND p.date_livraison BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='supplier_proposal' AND tc.source='internal' AND c.element_id = p.rowid AND c.fk_socpeople = ".$search_user;
}


$sql.= ' ORDER BY '.$sortfield.' '.$sortorder.', p.ref DESC';

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}


$sql.= $db->plimit($limit + 1,$offset);
$result=$db->query($sql);

if ($result)
{
	$objectstatic=new SupplierProposal($db);
	$userstatic=new User($db);
	$num = $db->num_rows($result);

 	if ($socid)
	{
		$soc = new Societe($db);
		 $soc->fetch($socid);
	}

	$param='&socid='.$socid.'&viewstatut='.$viewstatut;
	if ($month)              $param.='&month='.$month;
	if ($year)               $param.='&year='.$year;
    if ($search_ref)         $param.='&search_ref=' .$search_ref;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
	if ($search_user > 0)    $param.='&search_user='.$search_user;
	if ($search_sale > 0)    $param.='&search_sale='.$search_sale;
	if ($search_montant_ht)  $param.='&search_montant_ht='.$search_montant_ht;
	if ($search_author)  	 $param.='&search_author='.$search_author;
	print_barre_liste($langs->trans('ListOfSupplierProposal').' '.($socid?'- '.$soc->name:''), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	// Lignes des champs de filtre
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	
	if ($sall)
	{
	    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	    //sort($fieldstosearchall);
	    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
	}
	
	$i = 0;

	$moreforfilter='';

 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
	 	$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user, 0, 1, 'maxwidth300');
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
    if (! empty($moreforfilter))
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print '</div>';
    }
	

    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Supplier'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('SupplierProposalDate'),$_SERVER["PHP_SELF"],'p.date_livraison','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('AmountHT'),$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Author'),$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'p.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="12" name="search_societe" value="'.$search_societe.'">';
	print '</td>';

	// Date
	print '<td class="liste_titre" colspan="1" align="center">';
	//print $langs->trans('Month').': ';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	//print '&nbsp;'.$langs->trans('Year').': ';
	$syear = $year;
	$formother->select_year($syear,'year',1, 20, 5);
	print '</td>';

	// Amount
	print '<td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
	print '</td>';
	// Author
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" size="10" type="text" name="search_author" value="'.$search_author.'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	$formsupplier_proposal->selectSupplierProposalStatus($viewstatut,1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print '<input type="image" name="button_search" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" name="button_removefilter" class="liste_titre" src="'.img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

	print "</tr>\n";

	$var=true;
	$total=0;
	$subtotal=0;

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($result);
		$now = dol_now();
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td class="nowrap">';

		$objectstatic->id=$objp->supplier_proposalid;
		$objectstatic->ref=$objp->ref;

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		print '<td class="nobordernopadding nowrap">';
		print $objectstatic->getNomUrl(1);
		print '</td>';

		print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
		if ($objp->fk_statut == 1 && $db->jdate($objp->dfv) < ($now - $conf->supplier_proposal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
		if (! empty($objp->note_private))
		{
			print ' <span class="note">';
			print '<a href="'.DOL_URL_ROOT.'/supplier_proposal/note.php?id='.$objp->supplier_proposalid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
			print '</span>';
		}
		print '</td>';

		// Ref
		print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
		$filename=dol_sanitizeFileName($objp->ref);
		$filedir=$conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($objp->ref);
		$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->supplier_proposalid;
		print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		print '</td></tr></table>';

		print "</td>\n";

		$url = DOL_URL_ROOT.'/comm/card.php?socid='.$objp->rowid;

		// Company
		$companystatic->id=$objp->rowid;
		$companystatic->name=$objp->name;
		$companystatic->client=$objp->client;
		$companystatic->code_client=$objp->code_client;
		print '<td>';
		print $companystatic->getNomUrl(1,'customer');
		print '</td>';

		// Date askprice
		print '<td align="center">';
		print dol_print_date($db->jdate($objp->dp), 'day');
		print "</td>\n";

		print '<td align="right">'.price($objp->total_ht)."</td>\n";

		$userstatic->id=$objp->fk_user_author;
		$userstatic->login=$objp->login;
		print '<td align="center">';
		if ($userstatic->id) print $userstatic->getLoginUrl(1);
		else print '&nbsp;';
		print "</td>\n";

		print '<td align="right">'.$objectstatic->LibStatut($objp->fk_statut,5)."</td>\n";

		print '<td>&nbsp;</td>';

		print "</tr>\n";

		$total += $objp->total_ht;
		$subtotal += $objp->total_ht;

		$i++;
	}

	if ($total>0)
	{
		if($num<$limit){
			$var=!$var;
			print '<tr class="liste_total"><td align="left">'.$langs->trans("TotalHT").'</td>';
			print '<td colspan="3" align="right">'.price($total).'</td><td colspan="3"></td>';
			print '</tr>';
		}
		else
		{
			$var=!$var;
			print '<tr class="liste_total"><td align="left">'.$langs->trans("TotalHTforthispage").'</td>';
			print '<td colspan="3" align="right">'.price($total).'</td><td colspan="3"></td>';
			print '</tr>';
		}

	}

	print '</table>';

	print '</form>';

	$db->free($result);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
