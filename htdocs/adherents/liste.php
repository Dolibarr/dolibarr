<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *      \file       htdocs/adherents/liste.php
 *      \ingroup    member
 *		\brief      Page to list all members of foundation
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

$langs->load("members");
$langs->load("companies");

// Security check
$result=restrictedArea($user,'adherent');

$action=GETPOST("action");
$filter=GETPOST("filter");
$statut=GETPOST("statut");
$search=GETPOST("search");
$search_ref=GETPOST("search_ref");
$search_lastname=GETPOST("search_lastname");
$search_firstname=GETPOST("search_firstname");
$search_login=GETPOST("search_login");
$type=GETPOST("type");
$search_email=GETPOST("search_email");
$search_categ = GETPOST("search_categ",'int');
$catid        = GETPOST("catid",'int');
$sall=GETPOST("sall");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) { $sortorder=($filter=='outofdate'?"ASC":"DESC"); }
if (! $sortfield) { $sortfield=($filter=='outofdate'?"d.datefin":"d.lastname"); }

if (GETPOST("button_removefilter"))
{
    $search="";
	$search_ref="";
    $search_lastname="";
	$search_firstname="";
	$search_login="";
	$type="";
	$search_email="";
	$search_categ="";
	$catid="";
	$sall="";
}


/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$membertypestatic=new AdherentType($db);
$memberstatic=new Adherent($db);

llxHeader('',$langs->trans("Member"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$now=dol_now();

$sql = "SELECT d.rowid, d.login, d.lastname, d.firstname, d.societe as company, d.fk_soc,";
$sql.= " d.datefin,";
$sql.= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
$sql.= " t.libelle as type, t.cotisation";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_member as cm ON d.rowid = cm.fk_member"; // We need this table joined to the select in order to filter by categ
$sql.= ", ".MAIN_DB_PREFIX."adherent_type as t";
$sql.= " WHERE d.fk_adherent_type = t.rowid ";
if ($catid > 0)    $sql.= " AND cm.fk_categorie = ".$catid;
if ($catid == -2)  $sql.= " AND cm.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cm.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cm.fk_categorie IS NULL";
$sql.= " AND d.entity = ".$conf->entity;
if ($sall)
{
        // For natural search
        $scrit = explode(' ', $sall);
        foreach ($scrit as $crit) {
            $sql.=" AND (";
            if (is_numeric($sall)) $sql.= "d.rowid = ".$sall." OR ";
            $sql.=" d.firstname LIKE '%".$sall."%' OR d.lastname LIKE '%".$sall."%' OR d.societe LIKE '%".$sall."%'";
            $sql.=" OR d.email LIKE '%".$sall."%' OR d.login LIKE '%".$sall."%' OR d.address LIKE '%".$sall."%'";
            $sql.=" OR d.town LIKE '%".$sall."%' OR d.note LIKE '%".$sall."%')";
        }
}
if ($type > 0)
{
	$sql.=" AND t.rowid=".$type;
}
if (isset($_GET["statut"]) || isset($_POST["statut"]))
{
	$sql.=" AND d.statut in (".$statut.")";     // Peut valoir un nombre ou liste de nombre separes par virgules
}
if ($search_ref)
{
	if (is_numeric($search_ref)) $sql.= " AND (d.rowid = ".$search_ref.")";
	else $sql.=" AND 1 = 2";    // Always wrong
}
if ($search_lastname)
{
	$sql.= " AND (d.firstname LIKE '%".$search_lastname."%' OR d.lastname LIKE '%".$search_lastname."%')";
}
if ($search_login)
{
	$sql.= " AND d.login LIKE '%".$search_login."%'";
}
if ($search_email)
{
	$sql.= " AND (d.email LIKE '%".$search_email."%')";
}
if ($filter == 'uptodate')
{
	$sql.=" AND datefin >= '".$db->idate($now)."'";
}
if ($filter == 'outofdate')
{
	$sql.=" AND (datefin IS NULL OR datefin < '".$db->idate($now)."')";
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

dol_syslog("get list sql=".$sql);
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
	elseif ($action == 'search')
	{
		$titre=$langs->trans("MembersListQualified");
	}

	if ($type > 0)
	{
		$membertype=new AdherentType($db);
		$result=$membertype->fetch($_REQUEST["type"]);
		$titre.=" (".$membertype->libelle.")";
	}

	$param="";
	if (isset($_GET["statut"]))       $param.="&statut=".$statut;
	if ($search_nom)   $param.="&search_nom=".$search_nom;
	if ($search_login) $param.="&search_login=".$search_login;
	if ($search_email) $param.="&search_email=".$search_email;
	if ($filter)       $param.="&filter=".$filter;
	print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	if ($sall)
	{
		print $langs->trans("Filter")." (".$langs->trans("Ref").", ".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
	}

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print "<table class=\"noborder\" width=\"100%\">";

	// Filter on categories
	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(3,$search_categ,'search_categ',1);
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
	if ($moreforfilter)
	{
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" colspan="9">';
		print $moreforfilter;
		print '</td></tr>';
	}

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"d.rowid",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Name")." / ".$langs->trans("Company"),$_SERVER["PHP_SELF"],"d.lastname",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Login"),$_SERVER["PHP_SELF"],"d.login",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"t.libelle",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"d.morphy",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"d.email",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"d.statut,d.datefin",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("EndSubscription"),$_SERVER["PHP_SELF"],"d.datefin",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"",$param,"",'width="60" align="center"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_ref" value="'.$search_ref.'" size="4"></td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_lastname" value="'.$search_lastname.'" size="12"></td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_login" value="'.$search_login.'" size="7"></td>';

	print '<td class="liste_titre">';
	$listetype=$membertypestatic->liste_array();
	print $form->selectarray("type", $listetype, $type, 1, 0, 0, '', 0, 32);
	print '</td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_email" value="'.$search_email.'" size="12"></td>';

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
		$memberstatic->id=$objp->rowid;
		$memberstatic->ref=$objp->rowid;
		$memberstatic->lastname=$objp->lastname;
		$memberstatic->firstname=$objp->firstname;

		if (! empty($objp->fk_soc)) {
			$memberstatic->socid = $objp->fk_soc;
			$memberstatic->fetch_thirdparty();
			$companyname=$memberstatic->thirdparty->name;
		} else {
			$companyname=$objp->company;
		}

		$var=!$var;
		print "<tr ".$bc[$var].">";

		// Ref
		print "<td>";
		print $memberstatic->getNomUrl(1);
		print "</td>\n";

		// Lastname
		print "<td><a href=\"fiche.php?rowid=$objp->rowid\">";
		print ((! empty($objp->lastname) || ! empty($objp->firstname)) ? dol_trunc($memberstatic->getFullName($langs)) : '');
		print (((! empty($objp->lastname) || ! empty($objp->firstname)) && ! empty($companyname)) ? ' / ' : '');
		print (! empty($companyname) ? dol_trunc($companyname, 32) : '');
		print "</a></td>\n";

		// Login
		print "<td>".$objp->login."</td>\n";

		// Type
		$membertypestatic->id=$objp->type_id;
		$membertypestatic->libelle=$objp->type;
		print '<td class="nowrap">';
		print $membertypestatic->getNomUrl(1,32);
		print '</td>';

		// Moral/Physique
		print "<td>".$memberstatic->getmorphylib($objp->morphy)."</td>\n";

		// EMail
		print "<td>".dol_print_email($objp->email,0,0,1)."</td>\n";

		// Statut
		print '<td class="nowrap">';
		print $memberstatic->LibStatut($objp->statut,$objp->cotisation,$datefin,2);
		print "</td>";

		// End of subscription date
		if ($datefin)
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($datefin,'day');
			if ($datefin < ($now -  $conf->adherent->cotisation->warning_delay) && $objp->statut > 0) print " ".img_warning($langs->trans("SubscriptionLate"));
			print '</td>';
		}
		else
		{
			print '<td align="left" class="nowrap">';
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
			print "<a href=\"fiche.php?rowid=".$objp->rowid."&action=edit&backtopage=1\">".img_edit()."</a>";
		}
		print '&nbsp;';
		if ($user->rights->adherent->supprimer && $objp->statut == -1)
		{
			print "<a href=\"fiche.php?rowid=".$objp->rowid."&action=delete&backtopage=1\">".img_picto($langs->trans("Delete"),'disable.png')."</a>";
		}
		if ($user->rights->adherent->supprimer && $objp->statut == 1)
		{
			print "<a href=\"fiche.php?rowid=".$objp->rowid."&action=resign&backtopage=1\">".img_picto($langs->trans("Resiliate"),'disable.png')."</a>";
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


llxFooter();

$db->close();
?>
