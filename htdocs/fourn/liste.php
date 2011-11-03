<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
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
 *       \file       htdocs/fourn/liste.php
 *       \ingroup    fournisseur
 *       \brief      Home page of supplier area
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

$socname                   = GETPOST("socname");
$search_nom                = GETPOST("search_nom");
$search_ville              = GETPOST("search_ville");
$search_code_fournisseur   = GETPOST("search_code_fournisseur");
$search_compta_fournisseur = GETPOST("search_compta_fournisseur");
$search_datec              = GETPOST("search_datec");

// Security check
$socid = GETPOST('socid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$page = GETPOST('page');
$sortorder = GETPOST('sortorder');
$sortfield = GETPOST('sortfield');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";

// Load categ filters
$search_categ = GETPOST('search_categ');


/*
 *	View
 */

$htmlother=new FormOther($db);
$thirdpartystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$sql = "SELECT s.rowid as socid, s.nom, s.ville, s.datec, s.datea,  st.libelle as stcomm, s.prefix_comm, s.status as status, ";
$sql.= "code_fournisseur, code_compta_fournisseur";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id AND s.fournisseur = 1";
$sql.= " AND s.entity = ".$conf->entity;
if ($search_categ) $sql.= " AND s.rowid = cf.fk_societe";	// Join for the needed table to filter by categ
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND s.rowid = ".$socid;
if ($socname)
{
	$sql .= " AND s.nom LIKE '%".$db->escape($socname)."%'";
	$sortfield = "s.nom";
	$sortorder = "ASC";
}
if ($search_nom)   $sql .= " AND s.nom LIKE '%".$db->escape($search_nom)."%'";
if ($search_ville) $sql .= " AND s.ville LIKE '%".$db->escape($search_ville)."%'";
if ($search_code_fournisseur)   $sql .= " AND s.code_fournisseur LIKE '%".$db->escape($search_code_fournisseur)."%'";
if ($search_compta_fournisseur) $sql .= " AND s.code_compta_fournisseur LIKE '%".$db->escape($search_compta_fournisseur)."%'";
if ($search_datec)   $sql .= " AND s.datec LIKE '%".$db->escape($search_datec)."%'";

// Insert categ filter
if ($search_categ)
{
	$sql .= " AND cf.fk_categorie = ".$db->escape($search_categ);
}
// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param = "&amp;search_nom=".$search_nom."&amp;search_code=".$search_code."&amp;search_ville=".$search_ville;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;

	print_barre_liste($langs->trans("ListOfSuppliers"), $page, "liste.php", $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

	print '<form action="liste.php" method="GET">';
	print '<table class="liste" width="100%">';

	// Filter on categories
	$moreforfilter='';
	if ($conf->categorie->enabled)
	{
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$htmlother->select_categories(1,$search_categ,'search_categ');
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
	if ($moreforfilter)
	{
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" colspan="6">';
		print $moreforfilter;
		print '</td></tr>';
	}

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,'valign="middle"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.ville","",$param,'valign="middle"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("SupplierCode"),$_SERVER["PHP_SELF"],"s.code_fournisseur","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta_fournisseur","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"s.datec","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$params,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre"><input type="text" class="flat" name="search_nom" value="'.$search_nom.'"></td>';

	print '<td class="liste_titre"><input type="text" class="flat" name="search_ville" value="'.$search_ville.'"></td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_code_fournisseur" value="'.$search_code_fournisseur.'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_compta_fournisseur" value="'.$search_compta_fournisseur.'">';
	print '</td>';

	print '<td align="right" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_datec" value="'.$search_datec.'">';
	print '</td>';

	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';

	print '</tr>';

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

        $thirdpartystatic->id=$obj->socid;
        $thirdpartystatic->nom=$obj->nom;
        $thirdpartystatic->status=$obj->status;

		print "<tr ".$bc[$var].">";
		print '<td>';
        print $thirdpartystatic->getNomUrl(1,'supplier');
		print "</td>\n";
		print "<td>".$obj->ville."</td>\n";
		print '<td align="left">'.$obj->code_fournisseur.'&nbsp;</td>';
		print '<td align="left">'.$obj->code_compta_fournisseur.'&nbsp;</td>';
		print '<td align="right">';
		print dol_print_date($db->jdate($obj->datec),'day').'</td>';
		print '<td align="right">'.$thirdpartystatic->getLibStatut(3).'</td>';
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

$db->close();

llxFooter();
?>