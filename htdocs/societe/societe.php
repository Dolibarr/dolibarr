<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/societe.php
 *	\ingroup    societe
 *	\brief      Page to show a third party
 */

require_once("../main.inc.php");
include_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");

// Security check
$socid = GETPOST("socid");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$search_nom=trim(GETPOST("search_nom"));
$search_nom_only=trim(GETPOST("search_nom_only"));
$search_all=trim(GETPOST("search_all"));
$search_ville=trim(GETPOST("search_ville"));
$socname=trim(GETPOST("socname"));
$search_idprof1=trim(GETPOST('search_idprof1'));
$search_idprof2=trim(GETPOST('search_idprof2'));
$search_idprof3=trim(GETPOST('search_idprof3'));
$search_idprof4=trim(GETPOST('search_idprof4'));
$search_sale=trim(GETPOST("search_sale"));
$search_categ=trim(GETPOST("search_categ"));
$mode=GETPOST("mode");
$modesearch=GETPOST("mode_search");

$sortfield=GETPOST("sortfield");
$sortorder=GETPOST("sortorder");
$page=GETPOST("page");
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Actions
 */

// Recherche
if ($mode == 'search')
{
	$search_nom=$socname;

	$sql = "SELECT s.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if ($search_sale || !$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    // We'll need this table joined to the select in order to filter by categ
    if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
	$sql.= " WHERE (";
	$sql.= " s.nom like '%".$db->escape($socname)."%'";
	$sql.= " OR s.code_client LIKE '%".$db->escape($socname)."%'";
	$sql.= " OR s.email like '%".$db->escape($socname)."%'";
	$sql.= " OR s.url like '%".$db->escape($socname)."%'";
	$sql.= ")";
	$sql.= " AND s.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
    if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
    if ($search_categ) $sql.= " AND s.rowid = cs.fk_societe";   // Join for the needed table to filter by categ
	if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
	{
		if (! $user->rights->fournisseur->lire) $sql.=" AND s.fourn != 1";
	}
    // Insert sale filter
    if ($search_sale)
    {
        $sql .= " AND sc.fk_user = ".$search_sale;
    }
    // Insert categ filter
    if ($search_categ)
    {
        $sql .= " AND cs.fk_categorie = ".$search_categ;
    }
	$result=$db->query($sql);
	if ($result)
	{
		if ($db->num_rows($result) == 1)
		{
			$obj = $db->fetch_object($result);
			$socid = $obj->rowid;
			header("Location: ".DOL_URL_ROOT."/societe/soc.php?socid=".$socid);
			exit;
		}
		$db->free($result);
	}
}



/*
 * View
 */

$form=new Form($db);
$htmlother=new FormOther($db);
$companystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);


// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_categ='';
    $search_sale='';
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


/*
 * Mode Liste
 */
/*
 REM: Regle sur droits "Voir tous les clients"
 REM: Exemple, voir la page societe.php dans le mode liste.
 Utilisateur interne socid=0 + Droits voir tous clients        => Voit toute societe
 Utilisateur interne socid=0 + Pas de droits voir tous clients => Ne voit que les societes liees comme commercial
 Utilisateur externe socid=x + Droits voir tous clients        => Ne voit que lui meme
 Utilisateur externe socid=x + Pas de droits voir tous clients => Ne voit que lui meme
 */
$title=$langs->trans("ListOfThirdParties");

$sql = "SELECT s.rowid, s.nom as name, s.ville, s.datec, s.datea,";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status,";
$sql.= " s.siren as idprof1, s.siret as idprof2, ape as idprof3, idprof4 as idprof4";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_societe";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || !$user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.entity = ".$conf->entity;
if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = ".$socid;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
if ($search_categ) $sql.= " AND s.rowid = cs.fk_societe";   // Join for the needed table to filter by categ
if (dol_strlen($stcomm))
{
	$sql.= " AND s.fk_stcomm=".$stcomm;
}
if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
{
	if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
}
// Insert sale filter
if ($search_sale)
{
    $sql .= " AND sc.fk_user = ".$search_sale;
}
// Insert categ filter
if ($search_categ)
{
    $sql .= " AND cs.fk_categorie = ".$search_categ;
}
if ($search_nom_only)
{
	$sql.= " AND s.nom LIKE '%".$db->escape($search_nom_only)."%'";
}
if ($search_all)
{
	$sql.= " AND (";
	$sql.= "s.nom LIKE '%".$db->escape($search_all)."%'";
	$sql.= " OR s.code_client LIKE '%".$db->escape($search_all)."%'";
	$sql.= " OR s.email like '%".$db->escape($search_all)."%'";
	$sql.= " OR s.url like '%".$db->escape($search_all)."%'";
	$sql.= ")";
}
if ($search_nom)
{
	$sql.= " AND (";
	$sql.= "s.nom LIKE '%".$db->escape($search_nom)."%'";
	$sql.= " OR s.code_client LIKE '%".$db->escape($search_nom)."%'";
	$sql.= " OR s.email like '%".$db->escape($search_nom)."%'";
	$sql.= " OR s.url like '%".$db->escape($search_nom)."%'";
	$sql.= ")";
}

if ($search_ville)
{
	$sql .= " AND s.ville LIKE '%".$db->escape($search_ville)."%'";
}
if ($search_idprof1)
{
	$sql .= " AND s.siren LIKE '%".$db->escape($search_idprof1)."%'";
}
if ($search_idprof2)
{
	$sql .= " AND s.siret LIKE '%".$db->escape($search_idprof2)."%'";
}
if ($search_idprof3)
{
	$sql .= " AND s.ape LIKE '%".$db->escape($search_idprof3)."%'";
}
if ($search_idprof4)
{
	$sql .= " AND s.idprof4 LIKE '%".$db->escape($search_idprof4)."%'";
}
//print $sql;

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

	$params = "&amp;socname=".$socname."&amp;search_nom=".$search_nom."&amp;search_ville=".$search_ville;
	$params.= '&amp;search_idprof1='.$search_idprof1;
	$params.= '&amp;search_idprof2='.$search_idprof2;
	$params.= '&amp;search_idprof3='.$search_idprof3;
	$params.= '&amp;search_idprof4='.$search_idprof4;

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

    // Show delete result message
    if (GETPOST('delsoc'))
    {
        dol_htmloutput_mesg($langs->trans("CompanyDeleted",GETPOST('delsoc')),'','ok');
    }

	$langs->load("other");
	$textprofid=array();
	foreach(array(1,2,3,4) as $key)
	{
		$label=$langs->transnoentities("ProfId".$key.$mysoc->country_code);
		$textprofid[$key]='';
		if ($label != "ProfId".$key.$mysoc->country_code)
		{	// Get only text between ()
			if (preg_match('/\((.*)\)/i',$label,$reg)) $label=$reg[1];
			$textprofid[$key]=$langs->trans("ProfIdShortDesc",$key,$mysoc->country_code,$label);
		}
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	print '<table class="liste" width="100%">';

    // Filter on categories
    /* Not possible in this page because list is for ALL third parties type
	$moreforfilter='';
    if ($conf->categorie->enabled)
    {
        $moreforfilter.=$langs->trans('Categories'). ': ';
        $moreforfilter.=$htmlother->select_categories(2,$search_categ,'search_categ');
        $moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
    }
    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
        $moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
        $moreforfilter.=$htmlother->select_salesrepresentatives($search_sale,'search_sale',$user);
    }
    if ($moreforfilter)
    {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="8">';
        print $moreforfilter;
        print '</td></tr>';
    }
	*/

    // Lines of titles
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.ville","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"),$textprofid[1],1,0),$_SERVER["PHP_SELF"],"s.siren","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"),$textprofid[2],1,0),$_SERVER["PHP_SELF"],"s.siret","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"),$textprofid[3],1,0),$_SERVER["PHP_SELF"],"s.ape","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof4","",$params,'nowrap="nowrap"',$sortfield,$sortorder);
	print '<td></td>';
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$params,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	if (! empty($search_nom_only) && empty($search_nom)) $search_nom=$search_nom_only;
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
	// Type (customer/prospect/supplier)
	print '<td colspan="2" class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]><td>";
		$companystatic->id=$obj->rowid;
		$companystatic->name=$obj->name;
		$companystatic->canvas=$obj->canvas;
        $companystatic->client=$obj->client;
        $companystatic->status=$obj->status;
		print $companystatic->getNomUrl(1,'',24);
		print "</td>\n";
		print "<td>".$obj->ville."</td>\n";
		print "<td>".$obj->idprof1."</td>\n";
		print "<td>".$obj->idprof2."</td>\n";
		print "<td>".$obj->idprof3."</td>\n";
		print "<td>".$obj->idprof4."</td>\n";
		print '<td align="center">';
		$s='';
		if (($obj->client==1 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
		{
	  		$companystatic->name=$langs->trans("Customer");
		    $s.=$companystatic->getNomUrl(0,'customer');
		}
		if (($obj->client==2 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
		{
            if ($s) $s.=" / ";
		    $companystatic->name=$langs->trans("Prospect");
            $s.=$companystatic->getNomUrl(0,'prospect');
		}
		if ($conf->fournisseur->enabled && $obj->fournisseur)
		{
			if ($s) $s.=" / ";
            $companystatic->name=$langs->trans("Supplier");
            $s.=$companystatic->getNomUrl(0,'supplier');
		}
		print $s;
		print '</td>';
        print '<td align="right">'.$companystatic->getLibStatut(3).'</td>';

		print '</tr>'."\n";
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

llxFooter();
?>
