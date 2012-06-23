<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
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
 *	\file       	htdocs/comm/propal.php
 *	\ingroup    	propale
 *	\brief      	Page of commercial proposals card and list
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formpropal.class.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');

$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer=GETPOST('search_refcustomer','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');

$sall=GETPOST("sall");
$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));
$year=GETPOST("year");
$month=GETPOST("month");

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

// Security check
$module='propale';
if (isset($socid))
{
	$objectid=$socid;
	$module='societe';
	$dbtable='&societe';
}
else if (isset($id) &&  $id > 0)
{
	$objectid=$id;
	$module='propale';
	$dbtable='propal';
}
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, $module, $objectid, $dbtable);

$object = new Propal($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('propalcard'));



/*
 * Actions
 */

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

llxHeader('',$langs->trans('Proposal'),'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic=new Societe($db);

$now=dol_now();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$viewstatut=$db->escape(GETPOST('viewstatut'));
$object_statut = $db->escape(GETPOST('propal_statut'));
if($object_statut != '')
$viewstatut=$object_statut;

if (! $sortfield) $sortfield='p.datep';
if (! $sortorder) $sortorder='DESC';
$limit = $conf->liste_limit;

$sql = 'SELECT s.nom, s.rowid, s.client, ';
$sql.= 'p.rowid as propalid, p.total_ht, p.ref, p.ref_client, p.fk_statut, p.fk_user_author, p.datep as dp, p.fin_validite as dfv,';
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " sc.fk_soc, sc.fk_user,";
$sql.= ' u.login';
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p';
if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
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
if ($search_ref)
{
	$sql.= " AND p.ref LIKE '%".$db->escape(trim($search_ref))."%'";
}
if ($search_refcustomer)
{
	$sql.= " AND p.ref_client LIKE '%".$db->escape(trim($search_refcustomer))."%'";
}
if ($search_societe)
{
	$sql.= " AND s.nom LIKE '%".$db->escape(trim($search_societe))."%'";
}
if ($search_montant_ht)
{
	$sql.= " AND p.total_ht='".$db->escape(trim($search_montant_ht))."'";
}
if ($sall) $sql.= " AND (s.nom LIKE '%".$db->escape($sall)."%' OR p.note LIKE '%".$db->escape($sall)."%' OR pd.description LIKE '%".$db->escape($sall)."%')";
if ($socid) $sql.= ' AND s.rowid = '.$socid;
if ($viewstatut <> '')
{
	$sql.= ' AND p.fk_statut IN ('.$viewstatut.')';
}
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(p.datep, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='propal' AND tc.source='internal' AND c.element_id = p.rowid AND c.fk_socpeople = ".$search_user;
}


$sql.= ' ORDER BY '.$sortfield.' '.$sortorder.', p.ref DESC';
$sql.= $db->plimit($limit + 1,$offset);
$result=$db->query($sql);

if ($result)
{
	$objectstatic=new Propal($db);
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
    if ($search_refcustomer) $param.='&search_ref=' .$search_refcustomer;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
	if ($search_user > 0)    $param.='&search_user='.$search_user;
	if ($search_sale > 0)    $param.='&search_sale='.$search_sale;
	if ($search_montant_ht)  $param.='&search_montant_ht='.$search_montant_ht;
	print_barre_liste($langs->trans('ListOfProposals').' '.($socid?'- '.$soc->nom:''), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num);

	// Lignes des champs de filtre
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	$i = 0;
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
	print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Company'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'p.ref_client','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DateEndPropalShort'),$_SERVER["PHP_SELF"],'dfv','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('AmountHT'),$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Author'),$_SERVER["PHP_SELF"],'u.login','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'p.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="16" name="search_societe" value="'.$search_societe.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
	print '</td>';
	print '<td class="liste_titre" colspan="1" align="center">';
	print $langs->trans('Month').': <input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	print '&nbsp;'.$langs->trans('Year').': ';
	$syear = $year;
	$formother->select_year($syear,'year',1, 20, 5);
	print '</td>';
	print '<td class="liste_titre" colspan="1">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	$formpropal->select_propal_statut($viewstatut,1);
	print '</td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	$var=true;

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($result);
		$now = time();
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td nowrap="nowrap">';

		$objectstatic->id=$objp->propalid;
		$objectstatic->ref=$objp->ref;

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		print '<td class="nobordernopadding" nowrap="nowrap">';
		print $objectstatic->getNomUrl(1);
		print '</td>';

		print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
		if ($objp->fk_statut == 1 && $db->jdate($objp->dfv) < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
		print '</td>';

		print '<td width="16" align="right" class="nobordernopadding">';
		$filename=dol_sanitizeFileName($objp->ref);
		$filedir=$conf->propal->dir_output . '/' . dol_sanitizeFileName($objp->ref);
		$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->propalid;
		$formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','',1,'',1);
		print '</td></tr></table>';

		if ($objp->client == 1)
		{
			$url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->rowid;
		}
		else
		{
			$url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->rowid;
		}

		// Company
		$companystatic->id=$objp->rowid;
		$companystatic->nom=$objp->nom;
		$companystatic->client=$objp->client;
		print '<td>';
		print $companystatic->getNomUrl(1,'customer');
		print '</td>';

		// Customer ref
		print '<td nowrap="nowrap">';
		print $objp->ref_client;
		print '</td>';

		// Date propale
		print '<td align="center">';
		$y = dol_print_date($db->jdate($objp->dp),'%Y');
		$m = dol_print_date($db->jdate($objp->dp),'%m');
		$mt= dol_print_date($db->jdate($objp->dp),'%b');
		$d = dol_print_date($db->jdate($objp->dp),'%d');
		print $d."\n";
		print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'&amp;month='.$m.'">';
		print $mt."</a>\n";
		print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'">';
		print $y."</a></td>\n";

		// Date fin validite
		if ($objp->dfv)
		{
			print '<td align="center">'.dol_print_date($db->jdate($objp->dfv),'day');
			print '</td>';
		}
		else
		{
			print '<td>&nbsp;</td>';
		}

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

		$total = $total + $objp->total_ht;
		$subtotal = $subtotal + $objp->total_ht;

		$i++;
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
?>
