<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/adherents/liste.php
 *      \ingroup    member
 *		\brief      Page to list all members of foundation
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");

$langs->load("members");
$langs->load("companies");

$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="ASC"; }
if (! $sortfield) {  $sortfield="d.nom"; }

$filter=$_GET["filter"];
$statut=isset($_GET["statut"])?$_GET["statut"]:'';


if ($_REQUEST["button_removefilter"])
{
	$_GET["search_nom"]="";
	$_REQUEST["search_nom"]="";
	$_GET["search_prenom"]="";
	$_REQUEST["search_prenom"]="";
	$_GET["type"]="";
	$_REQUEST["type"]="";
	$_GET["search_email"]="";
	$_REQUEST["search_email"]="";
	$_GET["search_categ"]="";
	$_REQUEST["search_categ"]="";
	$sall="";
}

// Load categ filters
$search_categ = isset($_GET["search_categ"])?$_GET["search_categ"]:$_POST["search_categ"];


/*
 * View
 */

$membertypestatic=new AdherentType($db);
$memberstatic=new Adherent($db);

llxHeader('',$langs->trans("Member"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);
$formother=new FormOther($db);

$now=dol_now();

$sql = "SELECT d.rowid, d.login, d.prenom, d.nom, d.societe, ";
$sql.= " d.datefin,";
$sql.= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
$sql.= " t.libelle as type, t.cotisation";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_member as cf";
$sql.= " WHERE d.fk_adherent_type = t.rowid ";
if ($search_categ) $sql.= " AND d.rowid = cf.fk_member";	// Join for the needed table to filter by categ
$sql.= " AND d.entity = ".$conf->entity;
if ($sall)
{
	$sql.=" AND (d.prenom like '%".$sall."%' OR d.nom like '%".$sall."%' OR d.societe like '%".$sall."%'";
	$sql.=" OR d.email like '%".$sall."%' OR d.login like '%".$sall."%' OR d.adresse like '%".$sall."%'";
	$sql.=" OR d.ville like '%".$sall."%' OR d.note like '%".$sall."%')";
}
if ($_REQUEST["type"] > 0)
{
	$sql.=" AND t.rowid=".$_REQUEST["type"];
}
if (isset($_GET["statut"]))
{
	$sql.=" AND d.statut in ($statut)";     // Peut valoir un nombre ou liste de nombre separes par virgules
}
if ( $_POST["action"] == 'search')
{
	if (isset($_POST['search']) && $_POST['search'] != '')
	{
		$sql.= " AND (d.prenom LIKE '%".$_POST['search']."%' OR d.nom LIKE '%".$_POST['search']."%')";
	}
}
if ($_GET["search_nom"])
{
	$sql.= " AND (d.prenom LIKE '%".$_GET["search_nom"]."%' OR d.nom LIKE '%".$_GET["search_nom"]."%')";
}
if ($_GET["search_login"])
{
	$sql.= " AND d.login LIKE '%".$_GET["search_login"]."%'";
}
if ($_GET["search_email"])
{
	$sql.= " AND (d.email LIKE '%".$_GET["search_email"]."%')";
}
if ($filter == 'uptodate')
{
	$sql.=" AND datefin >= ".$db->idate($now);
}
if ($filter == 'outofdate')
{
	$sql.=" AND datefin < ".$db->idate($now);
}
// Insert categ filter
if ($search_categ)
{
	$sql.= " AND cf.fk_categorie = ".$db->escape($search_categ);
}

// Count total nb of records with no order and no limits
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	if ($resql) $nbtotalofrecords = $db->num_rows($result);
	else dol_print_error($db);
}
// Add order and limit
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$titre=$langs->trans("MembersList");
	if (isset($_GET["statut"]))
	{
		if ($statut == '-1,1') { $titre=$langs->trans("MembersListQualified"); }
		if ($statut == '-1')   { $titre=$langs->trans("MembersListToValid"); }
		if ($statut == '1' && ! $filter)    		{ $titre=$langs->trans("MembersListValid"); }
		if ($statut == '1' && $filter=='uptodate')  { $titre=$langs->trans("MembersListUpToDate"); }
		if ($statut == '1' && $filter=='outofdate')	{ $titre=$langs->trans("MembersListNotUpToDate"); }
		if ($statut == '0')    { $titre=$langs->trans("MembersListResiliated"); }
	}
	elseif ($_POST["action"] == 'search')
	{
		$titre=$langs->trans("MembersListQualified");
	}

	if ($_REQUEST["type"] > 0)
	{
		$membertype=new AdherentType($db);
		$result=$membertype->fetch($_REQUEST["type"]);
		$titre.=" (".$membertype->libelle.")";
	}

	$param="";
	if (isset($_GET["statut"]))       $param.="&statut=".$_GET["statut"];
	if (isset($_GET["search_nom"]))   $param.="&search_nom=".$_GET["search_nom"];
	if (isset($_GET["search_login"])) $param.="&search_login=".$_GET["search_login"];
	if (isset($_GET["search_email"])) $param.="&search_email=".$_GET["search_email"];
	if (isset($_GET["filter"]))       $param.="&filter=".$_GET["filter"];
	print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	if ($sall)
	{
		print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
	}

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print "<table class=\"noborder\" width=\"100%\">";

	// Filter on categories
	$moreforfilter='';
	if ($conf->categorie->enabled)
	{
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(3,$search_categ,'search_categ');
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
	if ($moreforfilter)
	{
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" colspan="8">';
		print $moreforfilter;
		print '</td></tr>';
	}

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Name")." / ".$langs->trans("Company"),"liste.php","d.nom",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Login"),"liste.php","d.login",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),"liste.php","t.libelle",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Person"),"liste.php","d.morphy",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("EMail"),"liste.php","d.email",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php","d.statut,d.datefin",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("EndSubscription"),"liste.php","d.datefin",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),"liste.php","",$param,"",'width="60" align="center"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_nom" value="'.$_REQUEST["search_nom"].'" size="12"></td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_login" value="'.$_REQUEST["search_login"].'" size="7"></td>';

	print '<td class="liste_titre">';
	$listetype=$membertypestatic->liste_array();
	print $form->selectarray("type", $listetype, $_REQUEST["type"], 1, 0, 0, '', 0, 12);
	print '</td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_email" value="'.$_REQUEST["search_email"].'" size="12"></td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td align="right" colspan="2" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

	print "</tr>\n";
	print '</form>';

	$var=True;
	while ($i < $num && $i < $conf->liste_limit)
	{
		$objp = $db->fetch_object($resql);

		$datefin=$db->jdate($objp->datefin);

		// Nom
		$var=!$var;
		print "<tr ".$bc[$var].">";
		$memberstatic->nom=$objp->nom;
		$memberstatic->prenom=$objp->prenom;
		if ($objp->societe != '')
		{
			print "<td><a href=\"fiche.php?rowid=$objp->rowid\">".img_object($langs->trans("ShowMember"),"user").' '.dol_trunc($memberstatic->getFullName($langs))." / ".dol_trunc($objp->societe,12)."</a></td>\n";
		}
		else
		{
			print "<td><a href=\"fiche.php?rowid=$objp->rowid\">".img_object($langs->trans("ShowMember"),"user").' '.dol_trunc($memberstatic->getFullName($langs))."</a></td>\n";
		}

		// Login
		print "<td>".$objp->login."</td>\n";

		// Type
		$membertypestatic->id=$objp->type_id;
		$membertypestatic->libelle=$objp->type;
		print '<td nowrap="nowrap">';
		print $membertypestatic->getNomUrl(1,12);
		print '</td>';

		// Moral/Physique
		print "<td>".$memberstatic->getmorphylib($objp->morphy)."</td>\n";

		// EMail
		print "<td>".dol_print_email($objp->email,0,0,1)."</td>\n";

		// Statut
		print '<td nowrap="nowrap">';
		print $memberstatic->LibStatut($objp->statut,$objp->cotisation,$datefin,2);
		print "</td>";

		// End of subscription date
		if ($datefin)
		{
			print '<td align="center" nowrap="nowrap">';
			print dol_print_date($datefin,'day');
			if ($datefin < ($now -  $conf->adherent->cotisation->warning_delay) && $objp->statut > 0) print " ".img_warning($langs->trans("SubscriptionLate"));
			print '</td>';
		}
		else
		{
			print '<td align="left" nowrap="nowrap">';
			if ($objp->cotisation == 'yes')
			{
				print $langs->trans("SubscriptionNotReceived");
				if ($objp->statut > 0) print " ".img_warning();
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
		}

		// Actions
		print '<td align="center">';
		if ($user->rights->adherent->creer)
		{
			print "<a href=\"fiche.php?rowid=$objp->rowid&action=edit&return=liste.php\">".img_edit()."</a>";
		}
		print '&nbsp;';
		if ($user->rights->adherent->supprimer)
		{
			print "<a href=\"fiche.php?rowid=$objp->rowid&action=resign&return=liste.php\">".img_picto($langs->trans("Resiliate"),'disable.png')."</a>";
		}
		print "</td>";

		print "</tr>\n";
		$i++;
	}

	print "</table>\n";

	if ($num > $conf->liste_limit)
	{
		print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'');
	}
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter();
?>
