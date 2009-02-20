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

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Ligne - Commande - Retour');


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="rowid";
}

/*
 *
 */
$sql = "SELECT ";
$sql .= " cli,mode,situation,date_mise_service,date_resiliation,motif_resiliation,commentaire,fichier, traite ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour";
$sql .= " WHERE traite = 0";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print_barre_liste("Retours Fournisseurs", $page, "atraiter.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td>Mode</td><td align="center">Resultat</td>';
  print '<td align="center">Date</td><td>R�sil</td><td>Commentaire</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      $ligne = new LigneTel($db);

      if ( $ligne->fetch($obj->cli) == 1);
      {
	print "<tr $bc[$var]><td>";
	print '<img src="'.DOL_URL_ROOT.'/telephonie/ligne/graph'.$ligne->statut.'.png">&nbsp;';
	print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$obj->cli.'">';
	print dol_print_phone($obj->cli,0,0,true)."</a></td>\n";
	print '<td>'.$obj->mode."</td>\n";
	print '<td align="center">'.$obj->situation."</td>\n";
	print '<td align="center">'.$obj->date_mise_service."</td>\n";
	print '<td align="center">'.$obj->date_resiliation."</td>\n";
	print '<td>'.$obj->commentaire."</td>\n";
	print "</tr>\n";
      }
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  print $db->error() . ' ' . $sql;
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
