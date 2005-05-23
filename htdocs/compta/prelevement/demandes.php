<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/compta/prelevement/demandes.php
        \brief      Page de la liste des demandes de prélèvements
        \version    $Revision$
*/

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php";

$langs->load("widthdrawals");

if ($user->societe_id > 0)
{
  $socidp = $user->societe_id;
}

llxHeader();

/*
 *
 */

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($page == -1) $page = 0 ;
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.facnumber";


/*
 * Demandes en attente
 *
 */

$sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp";
$sql .= " , ".$db->pdate("pfd.date_demande")." as date_demande";
$sql .= " , pfd.fk_user_demande";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql .= " WHERE s.idp = f.fk_soc";
$sql .= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid";

if (strlen(trim($_GET["search_societe"])))
{
  $sql .= " AND s.nom LIKE '%".$_GET["search_societe"]."%'";
}

if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Demandes de prélèvement à traiter", $page, "demandes.php", $urladd, $sortfield, $sortorder, '', $num);
  
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Bill").'</td><td>'.$langs->trans("Company").'</td><td>Date demande</td>';
  print '<td>Emetteur</td></tr>';
  
  print '<form action="demandes.php" method="GET">';

  print '<tr class="liste_titre"><td>-</td>';
  print '<td>';
  print '<input type="text" class="flat" name="search_societe" size="12" value="'.$GET["search_societe"].'">';
  print '</td>';

  print '<td colspan="2" align="right"><input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'"></td></tr>';

  print '</form>';

  $var = True;

  $users = array();

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object();
      $var=!$var;
      print '<tr '.$bc[$var].'><td>';
      print '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.img_file().'</a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.$obj->facnumber.'</a></td>';
      print '<td>'.$obj->nom.'</td>';
      print '<td>'.strftime("%d %m %Y", $obj->date_demande).'</td>';
      if (!array_key_exists($obj->fk_user_demande,$users))
	{
	  $users[$obj->fk_user_demande] = new User($db, $obj->fk_user_demande);
	  $users[$obj->fk_user_demande]->fetch();
	}
      print '<td>'.$users[$obj->fk_user_demande]->fullname.'</td>';
      print '</tr>';
      $i++;
    }
  
  print "</table><br />";

}
else
{
  dolibarr_print_error($db);
}  


llxFooter('$Date$ - $Revision$');
?>
