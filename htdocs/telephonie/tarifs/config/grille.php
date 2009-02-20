<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$h=0;
$head[$h][0] = DOL_URL_ROOT."/telephonie/tarifs/config/grille.php?id=".$_GET["id"];
$head[$h][1] = $langs->trans("Grille");
$hselected = $h;
$h++;

require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.grille.class.php";

dol_fiche_head($head, $hselected, "Grille de tarif");

$grille = new TelephonieTarifGrille($db);
$grille->fetch($_GET["id"]);

print '<br> <table width="100%" class="border">';
print '<tr><td width="25%">Nom</td><td>'.$grille->libelle."</a></td></tr>\n";
print '<tr><td width="25%">Type</td><td>'.$grille->type."</a></td></tr>\n";
$grille->CountContrats();
print '<tr><td width="25%">Nombre de contrats</td><td>'.$grille->nb_contrats."</a></td></tr>\n";
print '</table></div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
