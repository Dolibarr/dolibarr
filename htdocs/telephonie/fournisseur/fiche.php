<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$mesg = '';

if ($_POST["action"] == 'add')
{
  $fourn = new FournisseurTelephonie($db);

  $fourn->nom            = $_POST["nom"];
  $fourn->email_commande = $_POST["email_commande"];

  if ( $fourn->create($user) == 0)
    {
      Header("Location: index.php");
    }
}

if ($_GET["action"] == 'active')
{
  $fourn = new FournisseurTelephonie($db);
  $fourn->id = $_GET["id"];

  if ( $fourn->active($user) == 0)
    {
      Header("Location: index.php");
    }
}

if ($_GET["action"] == 'desactive')
{
  $fourn = new FournisseurTelephonie($db);
  $fourn->id = $_GET["id"];

  if ( $fourn->desactive($user) == 0)
    {
      Header("Location: index.php");
    }
}

llxHeader("","","Fiche Fournisseur");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}

/*
 * Création
 *
 */

if ($_GET["action"] == 'create')
{
  $fourn = new FournisseurTelephonie($db);
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print_titre("Nouveau  fournisseur");
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Nom</td><td><input name="nom" size="30" value=""></td></tr>';
  print '<tr><td width="20%">Email de commande</td><td><input name="email_commande" size="40" value=""> (adresse email à laquelle sont envoyées les commandes de lignes</td></tr>';

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
}
else
{
  print "Error";
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
