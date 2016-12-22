<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/compta/prelevement/demandes.php
 *  \ingroup    prelevement
 *  \brief      Page to list withdraw requests
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/modules/modPrelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("withdrawals");
$langs->load("companies");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');

// Get supervariables
$page =  GETPOST('page','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');

/*
 * View
 */

llxHeader();

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);

if ($page == -1) $page = 0 ;
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.facnumber";


/*
 * Liste de demandes
 */

$sql= "SELECT f.facnumber, f.rowid, f.total_ttc,";
$sql.= " s.nom as name, s.rowid as socid,";
$sql.= " pfd.date_demande as date_demande,";
$sql.= " pfd.fk_user_demande";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql.= " ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND f.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
if (!$statut) $sql.= " AND pfd.traite = 0";
if ($statut) $sql.= " AND pfd.traite = ".$statut;
$sql.= " AND pfd.fk_facture = f.rowid";
if (dol_strlen(trim(GETPOST('search_societe','alpha'))))
{
	$sql.= " AND s.nom LIKE '%".GETPOST('search_societe','alpha')."%'";
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
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
    print '<td class="liste_titre" align="right">'.$langs->trans("Amount").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("DateRequest").'</td>';
	print '</tr>';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="GET">';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_facture" size="12" value="'.dol_escape_htmltag(GETPOST('search_facture','alpha')).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_societe" size="18" value="'.dol_escape_htmltag(GETPOST('search_societe','alpha')).'"></td>';
	print '<td colspan="2" class="liste_titre" align="right"><input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print '</tr>';
	print '</form>';

	$var = True;

	$users = array();

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
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
		$thirdpartystatic->name=$obj->name;
		print $thirdpartystatic->getNomUrl(1,'customer');
		print '</td>';

        print '<td align="right">'.price($obj->total_ttc).'</td>';

        print '<td align="right">'.dol_print_date($db->jdate($obj->date_demande),'day').'</td>';

		print '</tr>';
		$i++;
	}

	print "</table><br>";

}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
