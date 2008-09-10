<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/projet/liste.php
        \ingroup    projet
        \brief      Page liste des projets
        \version    $Id$
*/

require("./pre.inc.php");

if (!$user->rights->projet->lire) accessforbidden();

$socid = ( is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );

$title = $langs->trans("Projects");

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;

if ($socid > 0)
{
  $soc = new Societe($db);
  $soc->fetch($socid);
  $title .= ' (<a href="liste.php">'.$soc->nom.'</a>)';
}


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])? $_GET["page"]:$_POST["page"];
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



/**
 * Affichage de la liste des projets
 * 
 */

llxHeader();

$staticsoc=new Societe($db);

$sql = "SELECT p.rowid as projectid, p.ref, p.title, ".$db->pdate("p.dateo")." as do";
$sql .= ", s.nom, s.rowid as socid, s.client";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = p.fk_soc";
$sql .= " WHERE 1 = 1 ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
{ 
  $sql .= " AND s.rowid = ".$socid; 
}
if ($_GET["search_ref"])
{
  $sql .= " AND p.ref LIKE '%".addslashes($_GET["search_ref"])."%'";
}
if ($_GET["search_label"])
{
  $sql .= " AND p.title LIKE '%".addslashes($_GET["search_label"])."%'";
}
if ($_GET["search_societe"])
{
  $sql .= " AND s.nom LIKE '%".addslashes($_GET["search_societe"])."%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$var=true;
$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print_barre_liste($langs->trans("ProjectsList"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);
  
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"liste.php","p.ref","","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Label"),"liste.php","p.title","","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom","","","",$sortfield,$sortorder);
  print '<td>&nbsp;</td>';
  print "</tr>\n";
  
  print '<form method="get" action="liste.php">';
  print '<tr class="liste_titre">';
  print '<td valign="right">';
  print '<input type="text" class="flat" name="search_ref" value="'.$_GET["search_ref"].'">';
  print '</td>';
  print '<td valign="right">';
  print '<input type="text" class="flat" name="search_label" value="'.$_GET["search_label"].'">';
  print '</td>';
  print '<td valign="right">';
  print '<input type="text" class="flat" name="search_societe" value="'.$_GET["search_societe"].'">';
  print '</td>';
  print '<td class="liste_titre" align="center"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
  print "</td>";
  print "</tr>\n";
  
  while ($i < $num)
    {
      $objp = $db->fetch_object($resql);    
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$objp->projectid\">".img_object($langs->trans("ShowProject"),"project")." ".$objp->ref."</a></td>\n";
      print "<td><a href=\"fiche.php?id=$objp->projectid\">".$objp->title."</a></td>\n";
      
	  // Company
	  print '<td>';
	  if ($objp->socid)
	  {
		  $staticsoc->id=$objp->socid;
		  $staticsoc->nom=$objp->nom;
		  print $staticsoc->getNomUrl(1);
		 }
		 else
		 { 
		 print '&nbsp;';
		}
	print '</td>';
	  
      print '<td>&nbsp;</td>';
      print "</tr>\n";
      
      $i++;
    }
  
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}

print "</table>";

$db->close();


llxFooter('$Date$ - $Revision$');

?>
