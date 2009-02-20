<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
  accessforbidden();


llxHeader("","Telephonie - Commande - Archives");

/* ***************************************** */
$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="c.datec";
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT c.filename, u.name, u.firstname, f.nom,".$db->pdate("c.datec"). " as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande as c";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " ,".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE c.fk_user_creat = u.rowid AND c.fk_fournisseur = f.rowid";

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Commandes archives", $page, "archives.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td valign="center">Date</td>';
  print '<td>Utilisateur</td>';
  print '<td>Fournisseur</td>';
  print '<td>Fichier</td>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".strftime("%a %d %b %Y %HH%M",$obj->datec)."</td>\n";

      print "<td>".$obj->firstname . " ".$obj->name."</td>\n";
      print "<td>".$obj->nom."</td>\n";

      $dir = $conf->telephonie->dir_output . "/ligne/commande/";
      $relativepath = urlencode("ligne/commande/".$obj->filename);
      
      if (substr($obj->filename, -3) == 'txt')
	{
	  print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=telephonie&amp;file='.urlencode($relativepath).'&amp;type=plain/text">'.$obj->filename.'</a></td>';
	}
      else
	{
	  print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=telephonie&amp;file='.urlencode($relativepath).'&amp;type=application/msexcel">'.$obj->filename.'</a></td>';
	}

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  dol_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
