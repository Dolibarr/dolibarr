<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

llxHeader();

$mois=$_GET["month"];
$annee=$_GET["year"];

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socidp = $user->societe_id;
}

if (!$mois)
{
	$mois = strftime("%m", time());
}
if ($mois == 'all') { $mois=0; }

if (!$annee)
{
  $annee = strftime("%Y", time());
}

$time = mktime(12,0,0,$mois, 1, $annee);

$titre_mois = strftime("%B", $time);


print_fiche_titre("Journal de caisse".($mois?" $titre_mois":"").($annee?" $annee":""));
print '<br>';


// Recettes
$sql = "SELECT f.amount, date_format(f.datep,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement as f";
$sql .= " WHERE date_format(f.datep,'%Y%m') = ".$annee.$mois;

if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0; 
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $encaiss[$row[1]] = $row[0];
      $i++;
    }
}
else {
	print $db->error();	
}

print_fiche_titre("Recettes");
print '<table class="noborder" width="100%" cellspacing="0" cellpading="3">';
print '<tr class="liste_titre"><td>Jour</td><td>Description</td><td>Montant</td><td>Type</td></tr>';

print '<tr><td colspan="4">Fonction pas encore disponible</td></tr>';

print "</table>";


// Dépenses
$sql = "SELECT sum(f.amount) as amount , date_format(f.datep,'%d') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as f";

if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm DESC";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0; 
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $decaiss[$row[1]] = $row[0];
      $i++;
    }
}
else {
	print $db->error();	
}

print '<br>';

print_fiche_titre("Dépenses");
print '<table class="noborder" width="100%" cellspacing="0" cellpading="3">';
print '<tr class="liste_titre"><td>Jour</td><td>Description</td><td>Montant</td><td>Type</td></tr>';

print '<tr><td colspan="4">Fonction pas encore disponible</td></tr>';

print "</table>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
