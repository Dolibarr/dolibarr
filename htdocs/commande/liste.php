<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin          <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/commande/liste.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 *	\version    $Id$
 */


require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load('orders');
$langs->load('companies');

$year=isset($_GET["year"])?$_GET["year"]:$_POST["year"];
$month=isset($_GET["month"])?$_GET["month"]:$_POST["month"];
$sref=isset($_GET['sref'])?$_GET['sref']:$_POST['sref'];
$sref_client=isset($_GET['sref_client'])?$_GET['sref_client']:(isset($_POST['sref_client'])?$_POST['sref_client']:'');
$snom=isset($_GET['snom'])?$_GET['snom']:$_POST['snom'];
$sall=isset($_GET['sall'])?$_GET['sall']:$_POST['sall'];
$socid=isset($_GET['socid'])?$_GET['socid']:$_POST['socid'];

// Security check
$orderid = isset($_GET["orderid"])?$_GET["orderid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $orderid,'');


/*
 * View
 */

$now=gmmktime();

$html = new Form($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

llxHeader();

$begin=$_GET['begin'];
$sortorder=$_GET['sortorder'];
$sortfield=$_GET['sortfield'];
$viewstatut=$_GET['viewstatut'];

if (! $sortfield) $sortfield='c.rowid';
if (! $sortorder) $sortorder='DESC';

$limit = $conf->liste_limit;
$offset = $limit * $_GET['page'] ;

$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
$sql.= ' '.$db->pdate('c.date_commande').' as date_commande, c.fk_statut, c.facture as facturee';
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ' WHERE c.fk_soc = s.rowid';
$sql.= ' AND s.entity = '.$conf->entity;
if ($socid)	$sql.= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($sref)
{
	$sql.= " AND c.ref like '%".addslashes($sref)."%'";
}
if ($sall)
{
	$sql.= " AND (c.ref like '%".addslashes($sall)."%' OR c.note like '%".addslashes($sall)."%')";
}
if ($viewstatut <> '')
{
	if ($viewstatut < 4 && $viewstatut > -2)
	{
		$sql.= ' AND c.fk_statut ='.$viewstatut; // brouillon, validee, en cours, annulee
		if ($viewstatut == 3)
		{
			$sql.= ' AND c.facture = 0'; // need to create invoice
		}
	}
	if ($viewstatut == 4)
	{
		$sql.= ' AND c.facture = 1'; // invoice created
	}
	if ($viewstatut == -2)	// To process
	{
		$sql .= ' AND c.fk_statut > 0 and c.fk_statut <= 3 and c.facture = 0';
	}
}
if ($_GET['month'] > 0)
{
	$sql.= " AND date_format(c.date_commande, '%Y-%m') = '$year-$month'";
}
if ($_GET['year'] > 0)
{
	$sql.= " AND date_format(c.date_commande, '%Y') = $year";
}
if (!empty($snom))
{
	$sql.= ' AND s.nom like \'%'.addslashes($snom).'%\'';
}
if (!empty($sref_client))
{
	$sql.= ' AND c.ref_client like \'%'.addslashes($sref_client).'%\'';
}

$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
$sql.= $db->plimit($limit + 1,$offset);

$resql = $db->query($sql);

if ($resql)
{
	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfOrders') . ' - '.$soc->nom;
	}
	else
	{
		$title = $langs->trans('ListOfOrders');
	}
	if (strval($_GET['viewstatut']) == '0')
	$title.=' - '.$langs->trans('StatusOrderDraftShort');
	if ($_GET['viewstatut'] == 1)
	$title.=' - '.$langs->trans('StatusOrderValidatedShort');
	if ($_GET['viewstatut'] == 2)
	$title.=' - '.$langs->trans('StatusOrderOnProcessShort');
	if ($_GET['viewstatut'] == 3)
	$title.=' - '.$langs->trans('StatusOrderToBillShort');
	if ($_GET['viewstatut'] == 4)
	$title.=' - '.$langs->trans('StatusOrderProcessedShort');
	if ($_GET['viewstatut'] == -1)
	$title.=' - '.$langs->trans('StatusOrderCanceledShort');
	if ($_GET['viewstatut'] == -2)
	$title.=' - '.$langs->trans('StatusOrderToProcessShort');

	$num = $db->num_rows($resql);
	print_barre_liste($title, $_GET['page'], 'liste.php','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,$sortfield,$sortorder,'',$num);
	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),'liste.php','c.ref','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'width="25%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Company'),'liste.php','s.nom','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'width="30%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('RefCustomerOrder'),'liste.php','c.ref_client','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'width="15%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Date'),'liste.php','c.date_commande','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'width="20%" align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Status'),'liste.php','c.fk_statut','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'width="10%" align="center"',$sortfield,$sortorder);
	print '</tr>';
	// Lignes des champs de filtre
	print '<form method="get" action="liste.php">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="sref" value="'.$sref.'">';
	print '</td><td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
	print '</td><td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="10" name="sref_client" value="'.$sref_client.'">';
	print '</td><td class="liste_titre">&nbsp;';
	print '</td><td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
	print '</td></tr>';
	print '</form>';
	$var=True;
	$generic_commande = new Commande($db);
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td width="20%" nowrap="nowrap">';

		$generic_commande->id=$objp->rowid;
		$generic_commande->ref=$objp->ref;

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		print '<td class="nobordernopadding" nowrap="nowrap">';
		print $generic_commande->getNomUrl(1,$objp->fk_statut);
		print '</td>';

		print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
		if (($objp->fk_statut > 0) && ($objp->fk_statut < 3) && $objp->date_commande < ($now - $conf->commande->traitement->warning_delay)) print img_picto($langs->trans("Late"),"warning");
		print '</td>';

		print '<td width="16" align="right" class="nobordernopadding">';
		$filename=dol_sanitizeFileName($objp->ref);
		$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
		$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
		$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','','','',1);
		print '</td></tr></table>';

		print '</td>';

		// Company
		$companystatic->id=$objp->socid;
		$companystatic->nom=$objp->nom;
		$companystatic->client=$objp->client;
		print '<td>';
		print $companystatic->getNomUrl(1,'customer');
		print '</td>';

		print '<td>'.$objp->ref_client.'</td>';

		// Date
		$y = dol_print_date($objp->date_commande,'%Y');
		$m = dol_print_date($objp->date_commande,'%m');
		$ml = dol_print_date($objp->date_commande,'%B');
		$d = dol_print_date($objp->date_commande,'%d');
		print '<td align="right">';
		print $d;
		print ' <a href="liste.php?year='.$y.'&amp;month='.$m.'">'.$ml.'</a>';
		print ' <a href="liste.php?year='.$y.'">'.$y.'</a>';
		print '</td>';
		print '<td align="right">'.$generic_commande->LibStatut($objp->fk_statut,$objp->facturee,5).'</td>';
		print '</tr>';
		$total = $total + $objp->price;
		$subtotal = $subtotal + $objp->price;
		$i++;
	}
	print '</table>';
	$db->free($resql);
}
else
{
	print dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
