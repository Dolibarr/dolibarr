<?php
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

/*!
	    \file       htdocs/comm/prospect/prospect.php
        \ingroup    prospect
		\brief      Page de la liste des prospects
		\version    $Revision$
*/

require("./pre.inc.php");
require("./propal_model_pdf.class.php");

$langs->load("propal");
$langs->load("projects");
$langs->load("companies");


$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


if (defined("PROPALE_ADDON") && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".PROPALE_ADDON.".php"))
{
  require(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".PROPALE_ADDON.".php");
}

llxHeader();

print_titre($langs->trans("NewProp"));

/*
 *
 * Creation d'une nouvelle propale
 *
 */
if ($_GET["action"] == 'create')
{

  $soc = new Societe($db);
  /* TODO Ajouter un test ici */
  $soc->fetch($_GET["socidp"]);

  $obj = PROPALE_ADDON;
  $modPropale = new $obj;
  $numpr = $modPropale->propale_get_num();
  $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."propal WHERE ref like '$numpr%'";

  if ( $db->query($sql) )
    {
      $num = $db->result(0, 0);
      $db->free();
      if ($num > 0)
	{
	  $numpr .= "." . ($num + 1);
	}
    }
    
  print "<form action=\"propal.php?socidp=".$soc->id."\" method=\"post\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  
  print '<table class="border" width="100%">';
  
  print '<tr><td>'.$langs->trans("Ref").'</td><td><input name="ref" value="'.$numpr.'"></td>';

  print '<td valign="top" colspan="2">';
  print $langs->trans("Comments").'</td></tr>';
  
  print '<tr><td>'.$langs->trans("Company").'</td><td><a href="fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
  print '<td rowspan="5" colspan="2" valign="top">';
  print '<textarea name="note" wrap="soft" cols="40" rows="6"></textarea>';
  print '</tr>';
  
  print "<tr><td>".$langs->trans("Date")."</td><td>";
  print_date_select();
  print "</td></tr>";
  
  print '<tr><td>'.$langs->trans("Author").'</td><td>'.$user->fullname.'</td></tr>';
  print '<tr><td>Durée de validité</td><td><input name="duree_validite" size="5" value="15"> '.$langs->trans("days").'</td></tr>';

  /*
   * Destinataire de la propale
   */
  print "<tr><td>".$langs->trans("Contact")."</td><td>\n";
  $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email FROM ".MAIN_DB_PREFIX."socpeople as p";
  $sql .= " WHERE p.fk_soc = ".$soc->id;
  
  if ( $db->query($sql) )
    {
      $i = 0 ;
      $numdest = $db->num_rows(); 
  
      if ($numdest==0)
	{
	  print '<font class="error">Cette societe n\'a pas de contact, veuillez en créer un avant de faire votre proposition commerciale</font><br>';
	  print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$soc->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans("AddContact").'</a>';
	}
      else
	{
	  print "<select name=\"contactidp\">\n";
	  
	  while ($i < $numdest)
	    {
	      $contact = $db->fetch_object();
	      print '<option value="'.$contact->idp.'"';
	      if ($contact->idp == $setcontact)
		{
		  print ' selected';
		}
	      print '>'.$contact->firstname.' '.$contact->name;
	      if ($contact->email) { print ' &lt;'.$contact->email.'&gt;'; }
	      print '</option>';
	      $i++;
	    }
	  print '</select>';
	}
      
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }
  
  print '</td></tr>';

  print '<tr>';
  if ($conf->projet->enabled) {

      /*
       * Projet associé
       */
      print '<td valign="top">'.$langs->trans("Project").'</td><td>';
      print '<option value="0"></option>';
      
      $sql = "SELECT p.rowid, p.title FROM ".MAIN_DB_PREFIX."projet as p WHERE p.fk_soc =".$soc->id;
      
      if ( $db->query($sql) )
        {
          $i = 0 ;
          $numprojet = $db->num_rows();
    
          if ($numprojet==0)
    	{
    	  print 'Cette société n\'a pas de projet.<br>';
    	  print '<a href=../projet/fiche.php?socidp='.$soc->id.'&action=create>'.$langs->trans("AddProject").'</a>';
    	}
          else
    	{	  
    	  print '<select name="projetidp">';
    	  
    	  while ($i < $numprojet)
    	    {
    	      $projet = $db->fetch_object();
    	      print "<option value=\"$projet->rowid\">".$projet->title."</option>";
    	      $i++;
    	    }
    	  print '</select>';
    	}
          $db->free();
        }
      else
        {
          dolibarr_print_error($db);
        }

      print '</td>';
  }
  else {
        print '<td colspan="2">&nbsp;</td>';
  }

  print '<td>Modèle</td>';
  print '<td>';
  $html = new Form($db);
  $modelpdf = new Propal_Model_pdf($db);
  $html->select_array("modelpdf",$modelpdf->liste_array(),PROPALE_ADDON_PDF);
  print "</td></tr></table>";
  
  print '<br>';
  
  if ($conf->produit->enabled || $conf->service->enabled) {
      /*
       * Liste les produits/services prédéfinis
       */
      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p ";
      $sql .= " WHERE envente = 1";
      $sql .= " ORDER BY ref DESC";
      if ( $db->query($sql) )
        {
          $opt = "<option value=\"0\" selected></option>";
          if ($result)
    	{
    	  $num = $db->num_rows();	$i = 0;	
    	  while ($i < $num)
    	    {
    	      $objp = $db->fetch_object();
    	      $opt .= "<option value=\"$objp->rowid\">[$objp->ref] ".substr($objp->label,0,40)."</option>\n";
    	      $i++;
    	    }
    	}
          $db->free();
        }
      else
        {
          dolibarr_print_error($db);
        }
      
      $titre=$langs->trans("ProductsAndServices");
      $lib=$langs->trans("Product").'/'.$langs->trans("Services");
      
      print_titre($titre);
        
      print '<table class="border">';
      print '<tr><td>'.$lib.'</td><td>'.$langs->trans("Qty").'</td><td>Remise</td></tr>';
      for ($i = 1 ; $i <= PROPALE_NEW_FORM_NB_PRODUCT ; $i++)
        {
          print '<tr><td><select name="idprod'.$i.'">'.$opt.'</select></td>';
          print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
          print '<td><input type="text" size="3" name="remise'.$i.'" value="'.$soc->remise_client.'"> %</td></tr>';
        }
      
      print "</table>";
    
      print '<br>';
  }

  /*
   * Si il n'y a pas de contact pour la societe on ne permet pas la creation de propale
   */
  if ($numdest > 0)
    {
      print '<input type="submit" value="'.$langs->trans("Save").'">';
    }
  print "</form>";
}

$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
