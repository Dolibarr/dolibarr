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

$mesg_erreur = "";

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($user->rights->telephonie->ligne_commander)
{
  if ($_GET["action"] == "create" && $_GET["fournid"] > 0)
    {

      $fourn = new FournisseurTelephonie($db);

      $result = $fourn->fetch($_GET["fournid"]);

      if ($result == 0)
	{
	  $result = $fourn->CreateCommande($user);
	}
      
      /*
	$fourntel = new FournisseurTelephonie($db,1);
	if ( $fourntel->fetch(1) == 0)
	{
	$ct = new CommandeTableur($db, $user, $fourntel);
	
	$result = $ct->create();
	
	if ($result == 0)
	{
	Header("Location: archives.php");
	}
	elseif ($result == -3)
	{
	$mesg_erreur = "Email fournisseur non définit"; 
	}
	}
      */
    }
}


llxHeader("","Telephonie - Ligne - Commande");

print $mesg_erreur;

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT count(l.ligne), f.rowid, f.nom, f.commande_bloque";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ",".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= ",".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= ",".MAIN_DB_PREFIX."societe as sf";
$sql .= " WHERE l.fk_soc = s.idp AND l.fk_fournisseur = f.rowid AND l.statut IN (1,4) ";
$sql .= " AND l.fk_soc_facture = sf.idp";
$sql .= " GROUP BY f.rowid, f.nom ASC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Commande", $page, "liste.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Fournisseur</td>';
  print '<td align="center">Nb Lignes</td><td>&nbsp;</td>';
  print "</tr>\n";

  $var=True;

  $ligne = new LigneTel($db);

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$row[2].'</td>';
      print '<td align="center">'.$row[0]."</td>\n";
      print '<td>';
      if ($row[3] == 1)
	{
	  print "Les commandes sont bloquées";
	}
      else
	{
	  print '<a class="tabAction" href="fiche.php?action=create&amp;fournid='.$row[1].'">Créer la commande</a>';
	}
      print "</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table><br />";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

/*
 *
 *
 */

$sql = "SELECT sf.idp as sfidp, sf.nom as sfnom, s.idp as socidp, s.nom, l.ligne, f.nom as fournisseur, l.statut, l.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as sf";
$sql .= " WHERE l.fk_soc = s.idp AND l.fk_fournisseur = f.rowid";
$sql .= " AND l.statut IN (1,4) ";
$sql .= " AND l.fk_soc_facture = sf.idp";
$sql .= " ORDER BY s.nom ASC ";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td valign="center">Ligne';
  print '</td><td align="center">Statut</td><td>Client';
  print '</td><td>Client Facturé</td><td align="center">Rib OK</td><td>Fournisseur</td>';
  print "</tr>\n";

  $var=True;

  $ligne = new LigneTel($db);

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      $socf = new Societe($db);
      $socf->fetch($obj->sfidp);

      print "<tr $bc[$var]>";
      print '<td><a href="../fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne)."</a></td>\n";
      print '<td align="center">'.$ligne->statuts[$obj->statut]."</td>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socidp.'">'.$obj->nom.'</a></td>';
      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->sfidp.'">'.$obj->sfnom.'</a></td>';
      print '<td align="center">'.$socf->verif_rib().'</a></td>';
      print "<td>".$obj->fournisseur."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
