<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");
require("./propal_model_pdf.class.php3");

$sql = "SELECT s.nom, s.idp, s.prefix_comm FROM llx_societe as s WHERE s.idp = $socidp;";

$result = $db->query($sql);
if ($result) 
{
  if ( $db->num_rows() ) 
    {
      $objsoc = $db->fetch_object(0);
    }
  $db->free();
}


llxHeader();

print_titre("Nouvelle proposition commerciale");

/*
 *
 * Creation d'une nouvelle propale
 *
 */
if ($action == 'create')
{
  $numpr = propale_get_num();
  $sql = "SELECT count(*) FROM llx_propal WHERE ref like '$numpr%'";

  if ( $db->query($sql) )
    {
      $num = $db->result(0, 0);
      $db->free();
      if ($num > 0)
	{
	  $numpr .= "." . ($num + 1);
	}
    }
    
  print "<form action=\"propal.php3?socidp=$socidp\" method=\"post\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  
  print '<table border="1" cellspacing="0" cellpadding="3" width="100%">';
  
  print '<tr><td>Société</td><td><a href="fiche.php3?socid='.$socidp.'">'.$objsoc->nom.'</a></td>';
  
  print '<td valign="top" colspan="2">';
  print "Commentaires</td></tr>";
  
  print "<tr><td>Date</td><td>";
  print_date_select();
  print "</td>";
  
  print '<td rowspan="4" colspan="2" valign="top">';
  print '<textarea name="note" wrap="soft" cols="30" rows="10"></textarea>';
  
  print '<tr><td>Auteur</td><td>'.$user->fullname.'</td></tr>';
  print "<tr><td>Numéro</td><td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";
  /*
   *
   * Destinataire de la propale
   *
   */
  print "<tr><td>Contact</td><td><select name=\"contactidp\">\n";
  $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email FROM llx_socpeople as p WHERE p.fk_soc = $socidp";
  
  if ( $db->query($sql) )
    {
      $i = 0 ;
      $numdest = $db->num_rows(); 
      while ($i < $numdest)
	{
	  $contact = $db->fetch_object( $i);
	  print '<option value="'.$contact->idp.'"';
	  if ($contact->idp == $setcontact)
	    {
	      print ' SELECTED';
	    }
	  print '>'.$contact->firstname.' '.$contact->name.' ['.$contact->email.']</option>';
	  $i++;
	}
      $db->free();
    }
  else
    {
      print $db->error();
    }
  print '</select>';
  
  if ($numdest==0)
    {
      print 'Cette societe n\'a pas de contact, veuillez en creer un avant de faire de propale</b><br>';
      print '<a href=people.php3?socid='.$socidp.'&action=addcontact>Ajouter un contact</a>';
    }
  print '</td></tr>';
  /*
   *
   * Projet associé
   *
   */
  print '<tr><td valign="top">Projet</td><td><select name="projetidp">';
  print '<option value="0"></option>';
  
  $sql = "SELECT p.rowid, p.title FROM llx_projet as p WHERE p.fk_soc = $socidp";
  
  if ( $db->query($sql) )
    {
      $i = 0 ;
      $numprojet = $db->num_rows();
      while ($i < $numprojet)
	{
	  $projet = $db->fetch_object($i);
	  print "<option value=\"$projet->rowid\">$projet->title</option>";
	  $i++;
	}
      $db->free();
    }
  else
    {
      print $db->error();
    }
  print '</select>';
  if ($numprojet==0)
    {
	print 'Cette societe n\'a pas de projet.<br>';
	print '<a href=projet/fiche.php3?socidp='.$socidp.'&action=create>Créer un projet</a>';
    }
  print '</td>';
  print '<td>Modèle</td>';
  print '<td>';
  $html = new Form($db);
  $modelpdf = new Propal_Model_pdf($db);
  $html->select_array("modelpdf",$modelpdf->liste_array(),PROPALE_ADDON_PDF);
  print "</td></tr></table>";
  
  /*
   *
   * Liste des elements
   *
   */
  $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM llx_product as p ";
  $sql .= " WHERE envente = 1";
  $sql .= " ORDER BY p.nbvente DESC LIMIT 20";
  if ( $db->query($sql) )
    {
      $opt = "<option value=\"0\" SELECTED></option>";
      if ($result)
	{
	  $num = $db->num_rows();	$i = 0;	
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);
	      $opt .= "<option value=\"$objp->rowid\">[$objp->ref] $objp->label : $objp->price</option>\n";
	      $i++;
	    }
	}
      $db->free();
    }
  else
    {
      print $db->error();
    }
  
  print_titre("Services/Produits");
    
  print '<table border="1" cellspacing="0">';
  
  for ($i = 1 ; $i < 5 ; $i++)
    {
      print '<tr><td><select name="idprod'.$i.'">'.$opt.'</select></td>';
      print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td></tr>';
    }
  
  print "</table>";
  /*
   * Si il n'y a pas de contact pour la societe on ne permet pas la creation de propale
   */
  if ($numdest > 0)
    {
      print '<input type="submit" value="Enregistrer">';
    }
  print "</form>";
}

$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
