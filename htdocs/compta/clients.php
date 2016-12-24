<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/compta/clients.php
 *	\ingroup    compta
 *	\brief      Show list of customers to add an new invoice
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

$action=GETPOST('action');

// Secrutiy check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

if (! $user->rights->facture->lire)
accessforbidden();


$langs->load("companies");

$mode=GETPOST("mode");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";


/*
 * View
 */

llxHeader();

$thirdpartystatic=new Societe($db);

if ($action == 'note')
{
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".$note."' WHERE rowid=".$socid;
	$result = $db->query($sql);
}

if ($mode == 'search') 
{
	$resql=$db->query($sql);
	if ($resql) {
		if ( $db->num_rows($resql) == 1) {
			$obj = $db->fetch_object($resql);
			$socid = $obj->rowid;
		}
		$db->free($resql);
	}
}



/*
 * Mode List
 */

$sql = "SELECT s.rowid, s.nom as name, s.client, s.town, s.datec, s.datea";
$sql.= ", st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta ";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id AND s.client in (1, 3)";
$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if (dol_strlen($stcomm))
{
	$sql.= " AND s.fk_stcomm=".$stcomm;
}
if ($socname)
{
	$sql.= natural_search("s.nom", $socname);
	$sortfield = "s.nom";
	$sortorder = "ASC";
}
if ($_GET["search_nom"])
{
	$sql.= natural_search("s.nom", GETPOST("search_nom"));
}
if ($_GET["search_compta"])
{
	$sql.= natural_search("s.code_compta", GETPOST("search_compta"));
}
if ($_GET["search_code_client"])
{
	$sql.= natural_search("s.code_client", GETPOST("search_code_client"));
}
if (dol_strlen($begin))
{
	$sql.= natural_search("s.nom", $begin);
}
if ($socid)
{
	$sql.= " AND s.rowid = ".$socid;
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit+1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$langs->load('commercial');

	print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',$num);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"datec",$addu,"",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" name="search_nom" value="'.$_GET["search_nom"].'"></td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_code_client" value="'.$_GET["search_code_client"].'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_compta" value="'.$_GET["search_compta"].'">';
	print '</td>';

	print '<td align="right" colspan="2" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	$var=true;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);

		$var=!$var;

		print "<tr ".$bc[$var].">";
		print '<td>';
		$thirdpartystatic->id=$obj->rowid;
		$thirdpartystatic->name=$obj->name;
		$thirdpartystatic->client=$obj->client;
		print $thirdpartystatic->getNomUrl(1,'compta');
		print '</td>';
		print '<td>'.$obj->town.'&nbsp;</td>';
		print '<td align="left">'.$obj->code_client.'&nbsp;</td>';
		print '<td align="left">'.$obj->code_compta.'&nbsp;</td>';
		print '<td align="right">'.dol_print_date($db->jdate($obj->datec)).'</td>';
		print "</tr>\n";
		$i++;
	}
	print "</table>";

	print '</form>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
