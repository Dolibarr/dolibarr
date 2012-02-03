<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin          <regis@dolibarr.fr>
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
 *	\file       htdocs/commande/liste.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 */


require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT ."/commande/class/commande.class.php");

$langs->load('orders');
$langs->load('deliveries');
$langs->load('companies');

$orderyear=GETPOST("orderyear","int");
$ordermonth=GETPOST("ordermonth","int");
$deliveryyear=GETPOST("deliveryyear","int");
$deliverymonth=GETPOST("deliverymonth","int");
$sref=GETPOST('sref','alpha');
$sref_client=GETPOST('sref_client','alpha');
$snom=GETPOST('snom','alpha');
$sall=GETPOST('sall');
$socid=GETPOST('socid','int');

// Security check
$id = (GETPOST('orderid')?GETPOST('orderid'):GETPOST('id'));
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $id,'');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='c.rowid';
if (! $sortorder) $sortorder='DESC';
$limit = $conf->liste_limit;

$viewstatut=GETPOST('viewstatut');



/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

llxHeader();

$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
$sql.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as facturee';
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ' WHERE c.fk_soc = s.rowid';
$sql.= ' AND c.entity = '.$conf->entity;
if ($socid)	$sql.= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($sref)
{
	$sql.= " AND c.ref LIKE '%".$db->escape($sref)."%'";
}
if ($sall)
{
	$sql.= " AND (c.ref LIKE '%".$db->escape($sall)."%' OR c.note LIKE '%".$db->escape($sall)."%')";
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
		$sql .= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
	}
}
if ($ordermonth > 0)
{
	$sql.= " AND date_format(c.date_valid, '%Y-%m') = '".$orderyear."-".$ordermonth."'";
}
if ($orderyear > 0)
{
	$sql.= " AND date_format(c.date_valid, '%Y') = '".$orderyear."'";
}
if ($deliverymonth > 0)
{
	$sql.= " AND date_format(c.date_livraison, '%Y-%m') = '".$deliveryyear."-".$deliverymonth."'";
}
if ($deliveryyear > 0)
{
	$sql.= " AND date_format(c.date_livraison, '%Y') = '".$deliveryyear."'";
}
if (!empty($snom))
{
	$sql.= ' AND s.nom LIKE \'%'.$db->escape($snom).'%\'';
}
if (!empty($sref_client))
{
	$sql.= ' AND c.ref_client LIKE \'%'.$db->escape($sref_client).'%\'';
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
	if (strval($viewstatut) == '0')
	$title.=' - '.$langs->trans('StatusOrderDraftShort');
	if ($viewstatut == 1)
	$title.=' - '.$langs->trans('StatusOrderValidatedShort');
	if ($viewstatut == 2)
	$title.=' - '.$langs->trans('StatusOrderOnProcessShort');
	if ($viewstatut == 3)
	$title.=' - '.$langs->trans('StatusOrderToBillShort');
	if ($viewstatut == 4)
	$title.=' - '.$langs->trans('StatusOrderProcessedShort');
	if ($viewstatut == -1)
	$title.=' - '.$langs->trans('StatusOrderCanceledShort');
	if ($viewstatut == -2)
	$title.=' - '.$langs->trans('StatusOrderToProcessShort');

	$num = $db->num_rows($resql);
	print_barre_liste($title, $page, 'liste.php','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,$sortfield,$sortorder,'',$num);
	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),'liste.php','c.ref','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'width="25%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Company'),'liste.php','s.nom','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('RefCustomerOrder'),'liste.php','c.ref_client','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('OrderDate'),'liste.php','c.date_commande','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DeliveryDate'),'liste.php','c.date_livraison','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Status'),'liste.php','c.fk_statut','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'align="center"',$sortfield,$sortorder);
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
	print '</td><td class="liste_titre">&nbsp;';
	print '</td><td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td></tr>';
	print '</form>';
	$var=True;
	$generic_commande = new Commande($db);
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td nowrap="nowrap">';

		$generic_commande->id=$objp->rowid;
		$generic_commande->ref=$objp->ref;

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		print '<td class="nobordernopadding" nowrap="nowrap">';
		print $generic_commande->getNomUrl(1,$objp->fk_statut);
		print '</td>';

		print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
		if (($objp->fk_statut > 0) && ($objp->fk_statut < 3) && $db->jdate($objp->date_valid) < ($now - $conf->commande->client->warning_delay)) print img_picto($langs->trans("Late"),"warning");
		print '</td>';

		print '<td width="16" align="right" class="nobordernopadding">';
		$filename=dol_sanitizeFileName($objp->ref);
		$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
		$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
		$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
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

		// Order date
		$y = dol_print_date($db->jdate($objp->date_commande),'%Y');
		$m = dol_print_date($db->jdate($objp->date_commande),'%m');
		$ml = dol_print_date($db->jdate($objp->date_commande),'%B');
		$d = dol_print_date($db->jdate($objp->date_commande),'%d');
		print '<td align="right">';
		print $d;
		print ' <a href="'.$_SERVER['PHP_SELF'].'?orderyear='.$y.'&amp;ordermonth='.$m.'">'.$ml.'</a>';
		print ' <a href="'.$_SERVER['PHP_SELF'].'?orderyear='.$y.'">'.$y.'</a>';
		print '</td>';

		// Delivery date
		$y = dol_print_date($db->jdate($objp->date_livraison),'%Y');
		$m = dol_print_date($db->jdate($objp->date_livraison),'%m');
		$ml = dol_print_date($db->jdate($objp->date_livraison),'%B');
		$d = dol_print_date($db->jdate($objp->date_livraison),'%d');
		print '<td align="right">';
		print $d;
		print ' <a href="'.$_SERVER['PHP_SELF'].'?deliveryyear='.$y.'&amp;deliverymonth='.$m.'">'.$ml.'</a>';
		print ' <a href="'.$_SERVER['PHP_SELF'].'?deliveryyear='.$y.'">'.$y.'</a>';
		print '</td>';

		// Statut
		print '<td align="right" nowrap="nowrap">'.$generic_commande->LibStatut($objp->fk_statut,$objp->facturee,5).'</td>';

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

llxFooter();
?>
