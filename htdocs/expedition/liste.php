<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/*! \file htdocs/expedition/liste.php
        \ingroup    expedition
        \brief      Page de la liste des propositions commerciales
*/

require("./pre.inc.php");

$user->getrights('expedition');
if (!$user->rights->expedition->lire)
  accessforbidden();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/

llxHeader('','Liste des expéditions','ch-expedition.html');

if ($_GET["sortfield"] == "")
{
  $sortfield="e.rowid";
}
if ($_GET["sortorder"] == "")
{
  $sortorder="DESC";
}

$limit = $conf->liste_limit;
$offset = $limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;

$sql = "SELECT e.rowid, e.ref,".$db->pdate("e.date_expedition")." as date_expedition, e.fk_statut" ;
$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e ";
$sql_add = " WHERE ";
if ($socidp)
{ 
  $sql .= $sql_add . " s.idp = $socidp"; 
  $sql_add = " AND ";
}

if (strlen($HTTP_POST_VARS["sf_ref"]) > 0)
{
  $sql .= $sql_add . " e.ref like '%".$HTTP_POST_VARS["sf_ref"] . "%'";
}

$expedition = new Expedition($db);

$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($limit + 1,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  print_barre_liste("Expeditions", $_GET["page"], "liste.php","&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);
  
  $i = 0;
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  
  print '<tr class="liste_titre">';
  
  print_liste_field_titre_new ($langs->trans("Ref"),"liste.php","e.ref","","&amp;socidp=$socidp",'width="15%"',$sortfield);
  
  print_liste_field_titre_new ("Date","liste.php","e.date_expedition","","&amp;socidp=$socidp", 'width="25%" align="right" colspan="2"',$sortfield);
  
  print_liste_field_titre_new ($langs->trans("Status"),"liste.php","e.fk_statut","","&amp;socidp=$socidp",'width="10%" align="center"',$sortfield);
  print "</tr>\n";
  $var=True;
  
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></td>\n";
      
      $now = time();
      $lim = 3600 * 24 * 15 ;
      
      if ( ($now - $objp->date_expedition) > $lim && $objp->statutid == 1 )
	{
	  print "<td><b> &gt; 15 jours</b></td>";
	}
      else
	{
	  print "<td>&nbsp;</td>";
	}
	  
      print "<td align=\"right\">";
      $y = strftime("%Y",$objp->date_expedition);
      $m = strftime("%m",$objp->date_expedition);
      
      print strftime("%d",$objp->date_expedition)."\n";
      print " <a href=\"propal.php?year=$y&amp;month=$m\">";
      print strftime("%B",$objp->date_expedition)."</a>\n";
      print " <a href=\"propal.php?year=$y\">";
      print strftime("%Y",$objp->date_expedition)."</a></TD>\n";      
      
      print '<td align="center">'.$expedition->statuts[$objp->fk_statut].'</td>';
      print "</tr>\n";
      
      $i++;
    }
  
  print "</table>";
  $db->free();
}
else
{
  print $db->error();
}

$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
