<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * 
 * $Id$
 * $Source$
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
 */

require("pre.inc.php3");


llxHeader();
$db = new Db();
if ($sortorder == "") {
  $sortfield="lower(s.nom)";
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($action == 'add') {
  $soc = new Societe($db);
  $soc->nom = $nom;

  $soc->tel = $tel;
  $soc->fax = $fax;
  $soc->url = $url;

  $soc->client = 1;

  $socid = $soc->create();
}

if ($action == 'update') {
  $soc = new Societe($db);

  $soc->nom = $nom;

  $soc->adresse = $adresse;
  $soc->cp = $cp;
  $soc->ville = $ville;

  $soc->tel = $tel;
  $soc->fax = $fax;
  $soc->url = $url;
  $soc->siren = $siren;

  $soc->update($socid);
}

/*
 *
 *
 */

if ($action == 'create') {
  print '<div class="titre">Nouveau client</div><br>';
  print '<form action="soc.php3" method="post">';
  print '<input type="hidden" name="action" value="add">';

  print '<table border="1" cellpadding="3" cellspacing="0">';
  print '<tr><td>Nom</td><td><input type="text" name="nom"></td></tr>';
  print '<tr><td>Adresse</td><td><textarea name="adresse" cols="30" rows="3" wrap="soft"></textarea></td></tr>';
  print '<tr><td>CP</td><td><input size="6" type="text" name="cp">&nbsp;';
  print 'Ville&nbsp;<input type="text" name="ville"></td></tr>';

  print '<tr><td>Tel</td><td><input type="text" name="tel"></td></tr>';
  print '<tr><td>Fax</td><td><input type="text" name="fax"></td></tr>';
  print '<tr><td>Web</td><td><input type="text" name="url"></td></tr>';

  print '<tr><td>Siren</td><td><input type="text" name="siren"></td></tr>';

  print '<tr><td>Type</td><td><select name="type">';
  print '</select>';

  print '<tr><td>Effectif</td><td><select name="effectif">';
  print '</select>';

  print '</td></tr>';

  print '<tr><td colspan="2" align="center"><input type="submit" value="Ajouter"></td></tr>';
  print '</table>';
  print '</form>';
} elseif ($action == 'edit') {
  print '<div class="titre">Edition de la société</div><br>';

  $soc = new Societe($db);
  $soc->id = $socid;
  $soc->fetch($socid);

  print '<form action="soc.php3?socid='.$socid.'" method="post">';
  print '<input type="hidden" name="action" value="update">';

  print '<table border="1" cellpadding="3" cellspacing="0">';
  print '<tr><td>Nom</td><td><input type="text" name="nom" value="'.$soc->nom.'"></td></tr>';
  print '<tr><td valign="top">Adresse</td><td><textarea name="adresse" cols="30" rows="3" wrap="soft">';
  print $soc->adresse;
  print '</textarea></td></tr>';

  print '<tr><td>CP</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'">&nbsp;';
  print 'Ville&nbsp;<input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

  print '<tr><td>Tel</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td></tr>';
  print '<tr><td>Fax</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';
  print '<tr><td>Web</td><td>http://<input type="text" name="url" value="'.$soc->url.'"></td></tr>';

  print '<tr><td>Siren</td><td><input type="text" name="siren" value="'.$soc->siren.'"></td></tr>';

  print '<tr><td>Type</td><td><select name="type">';
  print '</select>';

  print '<tr><td>Effectif</td><td><select name="effectif">';
  print '</select>';

  print '</td></tr>';

  print '<tr><td colspan="2"><input type="submit" value="Mettre a jour"></td></tr>';
  print '</table>';
  print '</form>';
} else {

  $soc = new Societe($db);
  $soc->id = $socid;
  $soc->fetch();
  print "[<a href=\"soc.php3?socid=$socid&action=edit\">Editer</a>]";
  print '<table border="1" cellpadding="3" cellspacing="0">';
  print '<tr><td>Nom</td><td>'.$soc->nom.'</td></tr>';
  print '<tr><td valign="top">Adresse</td><td>'.nl2br($soc->adresse).'</td></tr>';
  print '<tr><td>CP</td><td>'.$soc->cp.'&nbsp;'.$soc->ville.'</td></tr>';

  print '<tr><td>Tel</td><td>'.$soc->tel.'</td></tr>';
  print '<tr><td>Fax</td><td>'.$soc->fax.'</td></tr>';
  print '<tr><td>Web</td><td><a href="http://'.$soc->url.'">http://'.$soc->url.'</a></td></tr>';

  print '<tr><td>Siren</td><td>'.$soc->siren.'</td></tr>';

  print '<tr><td>Type</td><td><select name="type">';
  print '</select>';

  print '<tr><td>Effectif</td><td><select name="effectif">';
  print '</select>';

  print '</td></tr>';

  print '</table>';


}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
