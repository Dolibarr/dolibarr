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
require("./pre.inc.php");
require("../contact.class.php");
require("../cactioncomm.class.php");
require("../actioncomm.class.php");

$user->getrights('propale');
$user->getrights('commande');
$user->getrights('projet');


$langs->load("orders");
$langs->load("companies");


if ($_POST["action"] == 'setremise')
{
  $soc = New Societe($db);
  $soc->fetch($_GET["id"]);
  $soc->set_remise_except($_POST["remise"],$user);


  Header("Location: remx.php?id=".$_GET["id"]);
}


llxHeader();


/*
 *
 */
$_socid = $_GET["id"];
/*
 * Sécurité si un client essaye d'accéder à une autre fiche que la sienne
 */
if ($user->societe_id > 0) 
{
  $_socid = $user->societe_id;
}
/*********************************************************************************
 *
 * Mode fiche
 *
 *
 *********************************************************************************/  
if ($_socid > 0)
{
  // On recupere les donnees societes par l'objet
  $objsoc = new Societe($db);
  $objsoc->id=$_socid;
  $objsoc->fetch($_socid,$to);
  
  $dac = strftime("%Y-%m-%d %H:%M", time());
  if ($errmesg)
    {
      print "<b>$errmesg</b><br>";
    }
  
  /*
   * Affichage onglets
   */
  $h = 0;
  
  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
  $head[$h][1] = $langs->trans("Company");
  $h++;
  
  if ($objsoc->client==1)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = 'Client';
      $h++;
    }

  if ($objsoc->client==1)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/remx.php?id='.$objsoc->id;
      $head[$h][1] = 'Remises exceptionnelles';
      $hselected=$h;
      $h++;
    }

    dolibarr_fiche_head($head, $hselected, $objsoc->nom);

    /*
     *
     *
     */
    print '<form method="POST" action="remx.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setremise">';

    print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';

    print '<tr><td width="20%">Remises exceptionnelles';
    print '</td><td>&nbsp;</td></tr>';

    print '<tr><td>Montant HT';
    print '</td><td><input type="text" size="5" name="remise" value="'.$objsoc->remise_client.'">&nbsp;<input type="submit" value="'.$langs->trans("Save").'"></td></tr>';

    print "</table></form>";

    print "<br>";
    
    /*
     *
     */
    print "</td>\n";    
    print "</div>\n";    
    print '<br>';        
    /*
     *
     * Liste
     *
     */
    $sql  = "SELECT rc.amount_ht,".$db->pdate("rc.datec")." as dc, u.code, fk_facture";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql .= " , ".MAIN_DB_PREFIX."user as u";
    $sql .= " WHERE rc.fk_soc =". $objsoc->id;
    $sql .= " AND u.rowid = rc.fk_user AND fk_facture IS NULL";
    $sql .= " ORDER BY rc.datec DESC";

    if ( $db->query($sql) )
      {
	print '<table class="border" cellspacing="0" width="100%" cellpadding="1">';
	print '<tr class="liste_titre"><td>Date</td>';
	print '<td>Montant HT</td><td>Utilisateur</td></tr>';
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	$i = 0 ; 
	$num = $db->num_rows();

	while ($i < $num )
	  {
	    $obj = $db->fetch_object( $i);
	    $tag = !$tag;
	    print "<tr $bc[$tag]>";
	    print '<td>'.strftime("%d %B %Y",$obj->dc).'</td>';
	    print '<td>'.price($obj->amount_ht).'</td>';
	    print '<td>'.$obj->code.'</td></tr>';
	    

	    $i++;
	  }
	$db->free();
	print "</table>";
      }
    else
      {
	print $db->error();
      }

    print '<br />';

    /*
     *
     * Liste Archives
     *
     */
    $sql  = "SELECT rc.amount_ht,".$db->pdate("rc.datec")." as dc, u.code, fk_facture";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql .= " , ".MAIN_DB_PREFIX."user as u";
    $sql .= " WHERE rc.fk_soc =". $objsoc->id;
    $sql .= " AND u.rowid = rc.fk_user AND fk_facture IS NOT NULL";
    $sql .= " ORDER BY rc.datec DESC";

    if ( $db->query($sql) )
      {
	print '<table class="border" cellspacing="0" width="100%" cellpadding="1">';
	print '<tr class="liste_titre"><td>Date</td>';
	print '<td>Montant HT</td><td align="center">Facture</td><td>Utilisateur</td></tr>';
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	$i = 0 ; 
	$num = $db->num_rows();

	while ($i < $num )
	  {
	    $obj = $db->fetch_object( $i);
	    $tag = !$tag;
	    print "<tr $bc[$tag]>";
	    print '<td>'.strftime("%d %B %Y",$obj->dc).'</td>';
	    print '<td>'.price($obj->amount_ht).'</td>';
	    print '<td align="center"><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->fk_facture.'">'.$obj->fk_facture.'</a></td>';
	    print '<td>'.$obj->code.'</td></tr>';
	    

	    $i++;
	  }
	$db->free();
	print "</table>";
      }
    else
      {
	print $db->error();
      }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
