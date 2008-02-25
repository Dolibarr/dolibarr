<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$result = restrictedArea($user, 'prelevement','','',1);


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
 * Liste de demandes
 *
 */

$sql= "SELECT f.facnumber, f.rowid, s.nom, s.rowid as socid";
$sql.= " , ".$db->pdate("pfd.date_demande")." as date_demande";
$sql.= " , pfd.fk_user_demande";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.rowid = f.fk_soc";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if (! $statut) $sql.= " AND pfd.traite = 0";
if ($statut) $sql.= " AND pfd.traite = ".$statut;
$sql.= " AND pfd.fk_facture = f.rowid";
if (strlen(trim($_GET["search_societe"])))
{
  $sql .= " AND s.nom LIKE '%".$_GET["search_societe"]."%'";
}
if ($socid)
{
  $sql .= " AND f.fk_soc = $socid";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  
if (! $statut)
{  
  print_barre_liste($langs->trans("RequestStandingOrderToTreat"), $page, "demandes.php", $urladd, $sortfield, $sortorder, '', $num);
}
else
{
	print_barre_liste($langs->trans("RequestStandingOrderTreated"), $page, "demandes.php", $urladd, $sortfield, $sortorder, '', $num);
}
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print '<td class="liste_titre">'.$langs->trans("Bill").'</td><td class="liste_titre">'.$langs->trans("Company").'</td>';
  print '<td class="liste_titre" align="center">'.$langs->trans("Date").'</td>';
  print '<td class="liste_titre" align="center">'.$langs->trans("Author").'</td>';
  print '</tr>';
  
  print '<form action="demandes.php" method="GET">';
  print '<td class="liste_titre"><input type="text" class="flat" name="search_facture" size="12" value="'.$GET["search_facture"].'"></td>';
  print '<td class="liste_titre"><input type="text" class="flat" name="search_societe" size="18" value="'.$GET["search_societe"].'"></td>';
  print '<td colspan="2" class="liste_titre" align="right"><input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'"></td>';
  print '</tr>';
  print '</form>';

  $var = True;

  $users = array();

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object();
      $var=!$var;
      print '<tr '.$bc[$var].'>';
      
      // Ref facture
      print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$obj->rowid.'">'.img_file().' '.$obj->facnumber.'</a></td>';

      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$obj->nom.'</a></td>';

      print '<td align="center">'.dolibarr_print_date($obj->date_demande).'</td>';

      if (!array_key_exists($obj->fk_user_demande,$users))
	{
	  $users[$obj->fk_user_demande] = new User($db, $obj->fk_user_demande);
	  $users[$obj->fk_user_demande]->fetch();
	}

      // User
      print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$users[$obj->fk_user_demande]->id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$users[$obj->fk_user_demande]->code.'</a></td>';

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
