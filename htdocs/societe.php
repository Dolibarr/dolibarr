<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/societe.php
 *	\ingroup    societe
 *	\brief      Page des societes
 *	\version    $Id$
 */

require_once("./pre.inc.php");
include_once(DOL_DOCUMENT_ROOT."/contact.class.php");

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_ville=isset($_GET["search_ville"])?$_GET["search_ville"]:$_POST["search_ville"];
$socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
$search_idprof1=$_REQUEST['search_idprof1'];
$search_idprof2=$_REQUEST['search_idprof2'];
$search_idprof3=$_REQUEST['search_idprof3'];
$search_idprof4=$_REQUEST['search_idprof4'];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Actions
 *
 */

// Recherche
$mode=isset($_GET["mode"])?$_GET["mode"]:$_POST["mode"];
$modesearch=isset($_GET["mode-search"])?$_GET["mode-search"]:$_POST["mode-search"];

if ($mode == 'search')
{
	$_POST["search_nom"]=$socname;

	$sql = "SELECT s.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE (";
	$sql.= "s.nom like '%".addslashes($socname)."%'";
	$sql.= " OR s.code_client LIKE '%".addslashes($socname)."%'";
	$sql.= " OR s.email like '%".addslashes($socname)."%'";
	$sql.= " OR s.url like '%".addslashes($socname)."%'";
	$sql.= ")";
	$sql.= " AND s.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
	if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
	{
		if (! $user->rights->fournisseur->lire) $sql.=" AND s.fourn != 1";
	}

	$result=$db->query($sql);
	if ($result)
	{
		if ($db->num_rows($result) == 1)
		{
			$obj = $db->fetch_object($result);
			$socid = $obj->rowid;
			header("Location: ".DOL_URL_ROOT."/soc.php?socid=".$socid);
			exit;
		}
		$db->free($result);
	}
	// Sécurité accès client
	if ($user->societe_id > 0)
	{
		$action = '';
		$socid = $user->societe_id;
	}
}


/*
 * View
 */

llxHeader($langs->trans("ThirdParty"),'','EN:Third_Parties|FR:Tiers|ES:Empresas');

$form=new Form($db);
$companystatic=new Societe($db);

// Do we click on purge search criteria ?
if (isset($_POST["button_removefilter_x"]))
{
	$socname="";
	$search_nom="";
	$search_ville="";
	$search_idprof1='';
	$search_idprof2='';
	$search_idprof3='';
	$search_idprof4='';
}

if ($socname)
{
	$search_nom=$socname;
}

// Affiche la confirmation de suppression d'un tiers
if ($_GET['delsoc']) print '<div class="warning">'.$langs->trans("CompanyDeleted",$_GET['delsoc']).'</div><br>';

/*
 * Mode Liste
 */
/*
 REM: Regle sur droits "Voir tous les clients"
 REM: Exemple, voir la page societe.php dans le mode liste.
 Utilisateur interne socid=0 + Droits voir tous clients        => Voit toute société
 Utilisateur interne socid=0 + Pas de droits voir tous clients => Ne voit que les sociétés liées comme commercial
 Utilisateur externe socid=x + Droits voir tous clients        => Ne voit que lui meme
 Utilisateur externe socid=x + Pas de droits voir tous clients => Ne voit que lui meme
 */
$title=$langs->trans("ListOfThirdParties");

$sql = "SELECT s.rowid, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea";
$sql.= ", st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur,";
$sql.= " s.siren as idprof1, s.siret as idprof2, ape as idprof3, idprof4 as idprof4";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.entity = ".$conf->entity;
if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = ".$socid;
if (strlen($stcomm))
{
	$sql.= " AND s.fk_stcomm=".$stcomm;
}
if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
{
	if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
}

if ($search_nom)
{
	$sql.= " AND (";
	$sql.= "s.nom LIKE '%".addslashes($search_nom)."%'";
	$sql.= " OR s.code_client LIKE '%".addslashes($search_nom)."%'";
	$sql.= " OR s.email like '%".addslashes($search_nom)."%'";
	$sql.= " OR s.url like '%".addslashes($search_nom)."%'";
	$sql.= ")";
}

if ($search_ville)
{
	$sql .= " AND s.ville LIKE '%".addslashes($search_ville)."%'";
}
if ($search_idprof1)
{
	$sql .= " AND s.siren LIKE '%".addslashes($search_idprof1)."%'";
}
if ($search_idprof2)
{
	$sql .= " AND s.siret LIKE '%".addslashes($search_idprof2)."%'";
}
if ($search_idprof3)
{
	$sql .= " AND s.ape LIKE '%".addslashes($search_idprof3)."%'";
}
if ($search_idprof4)
{
	$sql .= " AND s.idprof4 LIKE '%".addslashes($search_idprof4)."%'";
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$params = "&amp;socname=".$socname."&amp;search_nom=".$search_nom."&amp;search_ville=".$search_ville;
	$params.= '&amp;search_idprof1='.$search_idprof1;
	$params.= '&amp;search_idprof2='.$search_idprof2;
	$params.= '&amp;search_idprof3='.$search_idprof3;
	$params.= '&amp;search_idprof4='.$search_idprof4;

	print_barre_liste($title, $page, "societe.php",$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	$langs->load("other");
	$textprofid=array();
	foreach(array(1,2,3,4) as $key)
	{
		$label=$langs->transnoentities("ProfId".$key.$mysoc->pays_code);
		$textprofid[$key]='';
		if ($label != "ProfId".$key.$mysoc->pays_code)
		{	// Get only text between ()
			if (eregi('\((.*)\)',$label,$reg)) $label=$reg[1];
			$textprofid[$key]=$langs->trans("ProfIdShortDesc",$key,$mysoc->pays_code,$label);
		}
	}

	print '<form method="post" action="societe.php" name="formfilter">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	// Lignes des titres
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),"societe.php","s.nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),"societe.php","s.ville","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"),$textprofid[1],1,0),"societe.php","s.siren","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"),$textprofid[2],1,0),"societe.php","s.siret","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"),$textprofid[3],1,0),"societe.php","s.ape","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"),$textprofid[4],1,0),"societe.php","s.idprof4","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print '<td class="liste_titre" colspan="2" align="center">&nbsp;</td>';
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input class="flat" type="text" name="search_nom" value="'.$search_nom.'">';
	print '</td><td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ville" value="'.$search_ville.'">';
	print '</td>';
	// IdProf1
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof1" value="'.$search_idprof1.'">';
	print '</td>';
	// IdProf2
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof2" value="'.$search_idprof2.'">';
	print '</td>';
	// IdProf3
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof3" value="'.$search_idprof3.'">';
	print '</td>';
	// IdProf4
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof4" value="'.$search_idprof4.'">';
	print '</td>';
	print '<td class="liste_titre" colspan="2" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
	print '</td>';
	print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]><td>";
		$companystatic->id=$obj->rowid;
		$companystatic->nom=$obj->nom;
		print $companystatic->getNomUrl(1,'',24);
		print "</td>\n";
		print "<td>".$obj->ville."</td>\n";
		print "<td>".$obj->idprof1."</td>\n";
		print "<td>".$obj->idprof2."</td>\n";
		print "<td>".$obj->idprof3."</td>\n";
		print "<td>".$obj->idprof4."</td>\n";
		print '<td align="center">';
		if ($obj->client==1)
		{
	  		print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$obj->rowid."\">".$langs->trans("Customer")."</a>\n";
		}
		elseif ($obj->client==2)
		{
	  		print "<a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$obj->rowid."\">".$langs->trans("Prospect")."</a>\n";
		}
		else
		{
	  		print "&nbsp;";
		}
		print "</td><td align=\"center\">";
		if ($obj->fournisseur)
		{
	  		print '<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->rowid.'">'.$langs->trans("Supplier").'</a>';
		}
		else
		{
	  		print "&nbsp;";
		}

		print '</td></tr>'."\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";

	print '</form>';

}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
