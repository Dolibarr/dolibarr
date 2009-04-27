<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/compta/clients.php
 \ingroup    compta
 \brief      Page accueil des clients
 \version    $Id$
 */

require_once("./pre.inc.php");

if (! $user->rights->societe->lire)
accessforbidden();

require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$langs->load("companies");

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * View
 */

llxHeader();

// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

if ($action == 'attribute_prefix')
{
	$societe = new Societe($db, $socid);
	$societe->attribute_prefix($db, $socid);
}

if ($action == 'note')
{
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".$note."' WHERE rowid=".$socid;
	$result = $db->query($sql);
}

if ($mode == 'search') {
	if ($mode-search == 'soc') {
		$sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s ";
		$sql.= " WHERE lower(s.nom) like '%".addslashes(strtolower($socname))."%'";
		$sql.= " AND s.entity = ".$conf->entity;
	}

	if ( $db->query($sql) ) {
		if ( $db->num_rows() == 1) {
			$obj = $db->fetch_object();
			$socid = $obj->rowid;
		}
		$db->free();
	}
}



/*
 * Mode Liste
 *
 */

$sql = "SELECT s.rowid, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea";
$sql.= ", st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta ";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id AND s.client=1";
$sql.= " AND s.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if (strlen($stcomm))
{
	$sql.= " AND s.fk_stcomm=$stcomm";
}

if ($socname)
{
	$sql.= " AND s.nom like '%".addslashes(strtolower($socname))."%'";
	$sortfield = "s.nom";
	$sortorder = "ASC";
}

if ($_GET["search_nom"])
{
	$sql.= " AND s.nom like '%".addslashes(strtolower($_GET["search_nom"]))."%'";
}

if ($_GET["search_compta"])
{
	$sql.= " AND s.code_compta like '%".addslashes($_GET["search_compta"])."%'";
}

if ($_GET["search_code_client"])
{
	$sql.= " AND s.code_client like '%".addslashes($_GET["search_code_client"])."%'";
}

if (strlen($begin))
{
	$sql.= " AND s.nom like '".addslashes($begin)."'";
}

if ($socid)
{
	$sql.= " AND s.rowid = ".$socid;
}

$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows();
	$i = 0;

	if ($action == 'facturer') {
		print_barre_liste("Liste des clients facturables", $page, "clients.php","",$sortfield,$sortorder,'',$num);
	}
	else {
		print_barre_liste($langs->trans("ListOfCustomers"), $page, "clients.php","",$sortfield,$sortorder,'',$num);
	}

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","",'valign="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.ville","","",'valign="center"',$sortfield,$sortorder);
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
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'">';
	print '</td>';
	print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object();

		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->rowid.'">';
		print img_object($langs->trans("ShowCustomer"),"company");
		print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->rowid.'">'.$obj->nom.'</a></td>';
		print '<td>'.$obj->ville.'&nbsp;</td>';
		print '<td align="left">'.$obj->code_client.'&nbsp;</td>';
		print '<td align="left">'.$obj->code_compta.'&nbsp;</td>';
		print '<td align="right">'.dol_print_date($obj->datec).'</td>';
		print "</tr>\n";
		$i++;
	}
	print "</table>";

	print '</form>';

	$db->free();
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - 1.46 $');
?>
