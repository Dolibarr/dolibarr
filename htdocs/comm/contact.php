<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne			<erics@rycks.com>
 * Copyright (C) 2004-2009 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
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
 *      \file       htdocs/comm/contact.php
 *      \ingroup    commercial
 *      \brief      Liste des contacts
 */

require '../main.inc.php';

$langs->load("companies");

$sortfield=GETPOST('sortfield', 'alpha');
$sortorder=GETPOST('sortorder', 'alpha');
$page=GETPOST('page', 'int');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.name";
if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$type=GETPOST('type', 'alpha');
$search_lastname=GETPOST('search_nom')?GETPOST('search_nom'):GETPOST('search_lastname');			// For backward compatibility
$search_firstname=GETPOST('search_firstname')?GETPOST('search_firstname'):GETPOST('search_firstname');	// For backward compatibility
$search_company=GETPOST('search_societe')?GETPOST('search_societe'):GETPOST('search_company');		// For backward compatibility
$contactname=GETPOST('contactname');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
*	View
*/

llxHeader('','Contacts');

if ($type == "c" || $type == "p")
{
  $label = $langs->trans("Customers");
  $urlfiche="fiche.php";
}
if ($type == "f")
{
  $label = $langs->trans("Suppliers");
  $urlfiche="fiche.php";
}

/*
 * Mode liste
 *
 */

$sql = "SELECT s.rowid, s.nom,  st.libelle as stcomm";
$sql.= ", p.rowid as cidp, p.name, p.firstname, p.email, p.phone";
$sql.= " FROM ".MAIN_DB_PREFIX."c_stcomm as st,";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."socpeople as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND p.entity IN (".getEntity('societe', 1).")";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($type == "c") $sql.= " AND s.client IN (1, 3)";
if ($type == "p") $sql.= " AND s.client IN (2, 3)";
if ($type == "f") $sql.= " AND s.fournisseur = 1";
if ($socid) $sql.= " AND s.rowid = ".$socid;

if (dol_strlen($stcomm))
{
  $sql.= " AND s.fk_stcomm=$stcomm";
}

// FIXME $begin not exist
if (dol_strlen($begin)) // filtre sur la premiere lettre du nom
{
  $sql.= " AND upper(p.name) LIKE '".$begin."%'";
}

if (! empty($search_lastname))
{
  $sql.= " AND p.name LIKE '%".$db->escape($search_lastname)."%'";
}

if (! empty($search_firstname))
{
  $sql.= " AND p.firstname LIKE '%".$db->escape($search_firstname)."%'";
}

if (! empty($search_company))
{
  $sql.= " AND s.nom LIKE '%".$db->escape($search_company)."%'";
}

if (! empty($contactname)) // acces a partir du module de recherche
{
  $sql.= " AND (p.name LIKE '%".$db->escape(strtolower($contactname))."%' OR lower(p.firstname) LIKE '%".$db->escape(strtolower($contactname))."%') ";
  $sortfield = "p.name";
  $sortorder = "ASC";
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  $title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ListOfContacts") : $langs->trans("ListOfContactsAddresses"));
  print_barre_liste($title.($label?" (".$label.")":""),$page, $_SERVER["PHP_SELF"], "&amp;type=$type",$sortfield,$sortorder,"",$num);

  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Lastname"),$_SERVER["PHP_SELF"],"p.name", $begin,"&amp;type=$type","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Firstname"),$_SERVER["PHP_SELF"],"p.firstname", $begin,"&amp;type=$type","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom", $begin,"&amp;type=$type","",$sortfield,$sortorder);
  print '<td class="liste_titre">'.$langs->trans("Email").'</td>';
  print '<td class="liste_titre">'.$langs->trans("Phone").'</td>';
  print "</tr>\n";

  print '<form action="'.$_SERVER["PHP_SELF"].'?type='.$_GET["type"].'" method="GET">';
  print '<tr class="liste_titre">';
  print '<td class="liste_titre"><input class="flat" name="search_lastname" size="12" value="'.$search_lastname.'"></td>';
  print '<td class="liste_titre"><input class="flat" name="search_firstname" size="12"  value="'.$search_firstname.'"></td>';
  print '<td class="liste_titre"><input class="flat" name="search_company" size="12"  value="'.$search_company.'"></td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
  print "</tr>\n";
  print '</form>';

  $var=True;
  $i = 0;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object($resql);

      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'&socid='.$obj->rowid.'">'.img_object($langs->trans("ShowContact"),"contact");
      print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'&socid='.$obj->rowid.'">'.$obj->name.'</a></td>';
      print "<td>$obj->firstname</TD>";

      print '<td><a href="'.$_SERVER["PHP_SELF"].'?type='.$type.'&socid='.$obj->rowid.'">'.img_object($langs->trans("ShowCompany"),"company").'</a>&nbsp;';
      print "<a href=\"".$urlfiche."?socid=".$obj->rowid."\">$obj->nom</a></td>\n";

      print '<td>'.dol_print_phone($obj->email,$obj->cidp,$obj->rowid,'AC_EMAIL').'</td>';

      print '<td>'.dol_print_phone($obj->phone,$obj->country_code,$obj->cidp,$obj->rowid,'AC_TEL').'&nbsp;</td>';

      print "</tr>\n";
      $i++;
    }
  	print "</table></p>";
  	$db->free($resql);
}
else
{
    dol_print_error($db);
}

llxFooter();

$db->close();

?>
