<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 \file       htdocs/compta/prelevement/demandes.php
 \brief      Page de la liste des demandes de prélèvements
 \version    $Id$
 */

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php";
require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/societe.class.php";

$langs->load("widthdrawals");
$langs->load("companies");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');


/*
 * View
 */

llxHeader();

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($page == -1) $page = 0 ;
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.facnumber";


/*
 * Liste de demandes
 *
 */

$sql= "SELECT f.facnumber, f.rowid, s.nom, s.rowid as socid";
$sql.= ", ".$db->pdate("pfd.date_demande")." as date_demande";
$sql.= ", pfd.fk_user_demande";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND f.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
if (!$statut) $sql.= " AND pfd.traite = 0";
if ($statut) $sql.= " AND pfd.traite = ".$statut;
$sql.= " AND pfd.fk_facture = f.rowid";
if (strlen(trim($_GET["search_societe"])))
{
	$sql.= " AND s.nom LIKE '%".$_GET["search_societe"]."%'";
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

if ( $db->query($sql) )
{
	$num = $db->num_rows();
	$i = 0;

	if (!$statut)
	{
		print_barre_liste($langs->trans("RequestStandingOrderToTreat"), $page, "demandes.php", $urladd, $sortfield, $sortorder, '', $num);
	}
	else
	{
		print_barre_liste($langs->trans("RequestStandingOrderTreated"), $page, "demandes.php", $urladd, $sortfield, $sortorder, '', $num);
	}

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">'.$langs->trans("Bill").'</td><td class="liste_titre">'.$langs->trans("Company").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("DateRequest").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("Author").'</td>';
	print '</tr>';

	print '<form action="demandes.php" method="GET">';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_facture" size="12" value="'.$GET["search_facture"].'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_societe" size="18" value="'.$GET["search_societe"].'"></td>';
	print '<td colspan="2" class="liste_titre" align="right"><input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'"></td>';
	print '</tr>';
	print '</form>';

	$var = True;

	$users = array();

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object();
		$var=!$var;
		print '<tr '.$bc[$var].'>';

		// Ref facture
		print '<td>';
		$invoicestatic->id=$obj->rowid;
		$invoicestatic->ref=$obj->facnumber;
		print $invoicestatic->getNomUrl(1,'withdraw');
		print '</td>';

		print '<td>';
		$thirdpartystatic->id=$obj->socid;
		$thirdpartystatic->nom=$obj->nom;
		print $thirdpartystatic->getNomUrl(1,'customer');
		print '</td>';

		print '<td align="center">'.dol_print_date($obj->date_demande,'day').'</td>';

		if (!array_key_exists($obj->fk_user_demande,$users))
		{
			$users[$obj->fk_user_demande] = new User($db, $obj->fk_user_demande);
			$users[$obj->fk_user_demande]->fetch();
		}

		// User
		print '<td align="center">';
		print $users[$obj->fk_user_demande]->getNomUrl(1);
		print '</td>';

		print '</tr>';
		$i++;
	}

	print "</table><br />";

}
else
{
	dol_print_error($db);
}


llxFooter('$Date$ - $Revision$');
?>
