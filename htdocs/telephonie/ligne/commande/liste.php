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

if (!$user->rights->telephonie->ligne_commander)
  accessforbidden();

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($_GET["action"] == "commande" && $user->rights->telephonie->ligne_commander)
{
  $ltel = new LigneTel($db);
  $ltel->fetch_by_id($_GET["lid"]);

  if ($_GET["statut"] == 1)
    {
      $ltel->set_a_commander($user);
    }

  if ($_GET["statut"] == -1)
    {
      $ltel->set_en_attente($user);
    }

  Header("Location: liste.php");
}


llxHeader("","Telephonie - Ligne - Commande");

/*
 * S�curit� acc�s client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="l.statut";
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT sf.rowid as sfidp, sf.nom as sfnom, s.rowid as socid, s.nom, l.ligne, f.nom as fournisseur, l.statut, l.rowid, f.rowid as fournid, l.mode_paiement";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ",".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= ",".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= ",".MAIN_DB_PREFIX."societe as sf";
$sql .= " WHERE l.fk_soc = s.rowid ";
$sql .= " AND l.fk_fournisseur = f.rowid ";
$sql .= " AND l.statut IN (-1,1,4) ";
$sql .= " AND l.techno = 'presel'";
$sql .= " AND l.fk_soc_facture = sf.rowid";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Commande", $page, "liste.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Ligne","liste.php","l.ligne");
  print '<td align="center">Statut</td>';
  print_liste_field_titre("Client","liste.php","s.nom");
  print '<td>Client Factur�</td><td align="center">Rib OK</td><td>Fournisseur</td>';
  print "</tr>\n";

  $var=True;

  $ligne = new LigneTel($db);

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      $fourntels = array();

      if (!array_key_exists($obj->fournid, $fourntels)) 
	{
	  $ft = new FournisseurTelephonie($db, $obj->fournid);
	  $ft->fetch($obj->fournid);
	  $fourntels[$obj->fournid] = $ft;
	}


      $socf = new Societe($db);
      $socf->fetch($obj->sfidp);

      print "<tr $bc[$var]>";
      print '<td><img src="../graph'.$obj->statut.'.png">';
      print '&nbsp;<a href="../fiche.php?id='.$obj->rowid.'">';

      if (strlen($obj->ligne) <> 10)
	{
	  print "Erreur";
	  $ok_commande = 0;
	}
      else
	{
	  print dolibarr_print_phone($obj->ligne,0,0,true);
	  $ok_commande = 1;
	}

	print "</a></td>\n";

      $ftx = $fourntels[$obj->fournid];

      if ($ok_commande && $ftx->commande_enable && $user->rights->telephonie->ligne_commander && ($obj->statut == 1 or $obj->statut == -1) && ( $socf->verif_rib() or $obj->mode_paiement == 'vir'))
	{
	  $nst = ($obj->statut * -1);
	  print '<td align="center"><a href="liste.php?lid='.$obj->rowid.'&amp;action=commande&amp;statut='.$nst.'">';
	  print img_edit();
	  print '</a>&nbsp;<a href="liste.php?lid='.$obj->rowid.'&amp;action=commande&amp;statut='.$nst.'">';
	  print $ligne->statuts[$obj->statut];
	  print "</a></td>\n";
	}
      else
	{
	  print '<td align="center">'.$ligne->statuts[$obj->statut]."</td>\n";
	}

      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">'.stripslashes($obj->nom).'</a></td>';
      print '<td>'.stripslashes($obj->sfnom).'</td>';

      print '<td align="center">'.$yesno[$socf->verif_rib()].'</a></td>';
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
