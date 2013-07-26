<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *   \file       htdocs/fourn/commande/liste.php
 *   \ingroup    fournisseur
 *   \brief      List of suppliers orders
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load("orders");

$sref=GETPOST('search_ref');
$snom=GETPOST('search_nom');
$suser=GETPOST('search_user');
$sttc=GETPOST('search_ttc');
$search_ref=GETPOST('search_ref');
$search_nom=GETPOST('search_nom');
$search_user=GETPOST('search_user');
$search_ttc=GETPOST('search_ttc');
$sall=GETPOST('search_all');

$page  = GETPOST('page','int');
$socid = GETPOST('socid','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');

// Security check
$orderid = GETPOST('orderid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $orderid, '', 'commande');


/*
 *	View
 */

$title = $langs->trans("SuppliersOrders");
if ($socid > 0)
{
	$fourn = new Fournisseur($db);
	$fourn->fetch($socid);
	$title .= ' ('.$fourn->nom.')';
}

llxHeader('',$title);

$commandestatic=new CommandeFournisseur($db);


if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="cf.date_creation";
$offset = $conf->liste_limit * $page ;


/*
 * Mode Liste
 */

$sql = "SELECT s.rowid as socid, s.nom, cf.date_commande as dc,";
$sql.= " cf.rowid,cf.ref, cf.fk_statut, cf.total_ttc, cf.fk_user_author,";
$sql.= " u.login";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql.= " WHERE cf.fk_soc = s.rowid ";
$sql.= " AND cf.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($sref)
{
	$sql.= " AND cf.ref LIKE '%".$db->escape($sref)."%'";
}
if ($snom)
{
	$sql.= " AND s.nom LIKE '%".$db->escape($snom)."%'";
}
if ($suser)
{
	$sql.= " AND u.login LIKE '%".$db->escape($suser)."%'";
}
if ($sttc)
{
	$sql .= " AND total_ttc = ".price2num($sttc);
}
if ($sall)
{
	$sql.= " AND (cf.ref LIKE '%".$db->escape($sall)."%' OR cf.note LIKE '%".$db->escape($sall)."%')";
}
if ($socid) $sql.= " AND s.rowid = ".$socid;

if (GETPOST('statut'))
{
	$sql .= " AND fk_statut =".GETPOST('statut');
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{

	$num = $db->num_rows($resql);
	$i = 0;

	$param="";
	if ($search_ref)   $param.="&search_ref=".$search_ref;
	if ($search_nom)   $param.="&search_nom=".$search_nom;
	if ($search_user)  $param.="&search_user=".$search_user;
	if ($search_ttc)   $param.="&search_ttc=".$search_ttc;
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"cf.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.login","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"total_ttc","",$param,$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("OrderDate"),$_SERVER["PHP_SELF"],"dc","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"cf.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$sref.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_nom" value="'.$snom.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_user" value="'.$suser.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_ttc" value="'.$sttc.'"></td>';
	print '<td colspan="2" class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print '</tr>';

	$var=true;

	$userstatic = new User($db);

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

		print "<tr $bc[$var]>";

		// Ref
		print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>'."\n";

		// Company
		print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' ';
		print $obj->nom.'</a></td>'."\n";

		// Author
		$userstatic->id=$obj->fk_user_author;
		$userstatic->login=$obj->login;
		print "<td>";
		if ($userstatic->id) print $userstatic->getLoginUrl(1);
		else print "&nbsp;";
		print "</td>";

		// Amount
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

		// Statut
		print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut, 5).'</td>';

		print "</tr>\n";
		$i++;
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
