<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Expedition 
{
  var $db ;
  var $id ;
  var $brouillon;
  var $entrepot_id;
  /**
   * Initialisation
   *
   */
  Function Expedition($DB)
    {
      $this->db = $DB;
      $this->lignes = array();

      $this->sources[0] = "Proposition commerciale";
      $this->sources[1] = "Internet";
      $this->sources[2] = "Courrier";
      $this->sources[3] = "Téléphone";
      $this->sources[4] = "Fax";

      $this->statuts[-1] = "Annulée";
      $this->statuts[0] = "Brouillon";
      $this->statuts[1] = "Validée";

      $this->products = array();
    }
  /**
   * Créé
   *
   *
   */
  Function create($user)
    {
      require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";
      $error = 0;
      /* On positionne en mode brouillon la commande */
      $this->brouillon = 1;
      

      $this->user = $user;
      $this->db->begin();

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition (date_creation, fk_user_author, date_expedition, fk_commande, fk_entrepot) ";
      $sql .= " VALUES (now(), $user->id, ".$this->db->idate($this->date_expedition).",$this->commande_id, $this->entrepot_id)";
      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  /*
	   *
	   *
	   */

	  $sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
	  if ($this->db->query($sql))
	    {

	      $this->commande = new Commande($this->db);
	      $this->commande->id = $this->commande_id;
	      $this->commande->fetch_lignes();

	      /*
	       *  Insertion des produits dans la base
	       */
	      for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
		{
		  //TODO
		  if (! $this->create_line(0, $this->lignes[$i]->commande_ligne_id, $this->lignes[$i]->qty))
		    {
		      $error++;
		    }
		}
	      /*
	       *
	       *
	       */
	      $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 2 WHERE rowid=".$this->commande_id;
	      if (! $this->db->query($sql))
		{
		  $error++;
		}


	      if ($error ==0)
		{
		  $this->db->commit();
		}
	      else
		{
		  $this->db->rollback();
		}

	      return $this->id;
	    }
	  else
	    {
	      $error++;
	      return -1;
	    }
	}
      else
	{
	  $error++;
	  print $this->db->error() . '<b><br>'.$sql;
	  return 0;
	}
    }
  /**
   *
   *
   */
  Function create_line($transaction, $commande_ligne_id, $qty)
  {
    $error = 0;

    $idprod = 0;
    $j = 0;
    while (($j < sizeof($this->commande->lignes)) && idprod == 0)
      {
	if ($this->commande->lignes[$j]->id == $commande_ligne_id)
	  {
	    $idprod = $this->commande->lignes[$j]->product_id;
	  }
	$j++;
      }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."expeditiondet (fk_expedition, fk_commande_ligne, qty)";
    $sql .= " VALUES ($this->id,".$commande_ligne_id.",".$qty.")";
    
    if (! $this->db->query($sql) )
      {
	$error++;
      }

    if ($error == 0 )
      {
	return 1;
      }
  }
  /** 
   *
   * Lit une commande
   *
   */
  Function fetch ($id)
    {
      $sql = "SELECT e.rowid, e.date_creation, e.ref, e.fk_user_author, e.fk_statut, e.fk_commande, e.fk_entrepot";
      $sql .= ", ".$this->db->pdate("e.date_expedition")." as date_expedition ";
      $sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
      $sql .= " WHERE e.rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $obj = $this->db->fetch_object();

	  $this->id              = $obj->rowid;
	  $this->ref             = $obj->ref;
	  $this->statut          = $obj->fk_statut;
	  $this->commande_id     = $obj->fk_commande;
	  $this->user_author_id  = $obj->fk_user_author;
	  $this->date            = $obj->date_expedition;
	  $this->entrepot_id     = $obj->fk_entrepot;
	  $this->db->free();
	  
	  if ($this->statut == 0)
	    $this->brouillon = 1;
	  
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  return -1;
	}
    }

  /**
   * Valide l'expedition
   *
   *
   */
  Function valid($user)
    {
      require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";

      $result = 0;
      if ($user->rights->expedition->valider)
	{
	  
	  $sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET ref='EXP".$this->id."', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
		  
	  if ($this->db->query($sql) )
	    {
	      $result = 1;

	      /*
	       * Enregistrement d'un mouvement de stock
	       * pour chaque ligne produit de l'expedition
	       */

	      $sql = "SELECT cd.fk_product,  ed.qty ";
	      $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd , ".MAIN_DB_PREFIX."expeditiondet as ed";
	      $sql .= " WHERE ed.fk_expedition = $this->id AND cd.rowid = ed.fk_commande_ligne ";
	  
	      if ($this->db->query($sql))
		{
		  $num = $this->db->num_rows();
		  $i=0;
		  while($i < $num)
		    {
		      $mouvS = new MouvementStock($this->db);
		      $obj = $this->db->fetch_object($i);
		      $mouvS->livraison($user, $obj->fk_product, $this->entrepot_id, $obj->qty, 0);
		      $i++;
		    }
		}

	    }
	  else
	    {
	      $result = -1;
	      print $this->db->error() . ' in ' . $sql;
	    }
	}
      return $result ;
    }

  /**
   * Ajoute un produit
   *
   */
  Function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx=19.6, $p_product_id=0, $remise_percent=0)
    {
      if ($this->statut == 0)
	{
	  if (strlen(trim($p_qty)) == 0)
	    {
	      $p_qty = 1;
	    }

	  $p_price = ereg_replace(",",".",$p_price);

	  $price = $p_price;
	  $subprice = $p_price;
	  if ($remise_percent > 0)
	    {
	      $remise = round(($p_price * $remise_percent / 100), 2);
	      $price = $p_price - $remise;
	    }

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet (fk_commande, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
	  $sql .= " (".$this->id.", $p_product_id,". $p_qty.",". $price.",".$p_tva_tx.",'". addslashes($p_desc) ."',$remise_percent, $subprice) ; ";
	  
	  if ($this->db->query($sql) )
	    {
	      
	      if ($this->update_price() > 0)
		{	      
		  return 1;
		}
	      else
		{
		  return -1;
		}
	    }
	  else
	    {
	      print $this->db->error();
	      print "<br>".$sql;
	      return -2;
	    }
	}
    }
  /**
   * Ajoute une ligne
   *
   */
  Function addline( $id, $qty )
    {
      $num = sizeof($this->lignes);
      $ligne = new ExpeditionLigne();

      $ligne->commande_ligne_id = $id;
      $ligne->qty = $qty;

      $this->lignes[$num] = $ligne;
    }

  /** 
   *
   *
   */
  Function delete_line($idligne)
    {
      if ($this->statut == 0)
	{
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE rowid = $idligne";
	  
	  if ($this->db->query($sql) )
	    {
	      $this->update_price();
	      
	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	}
    }
  /**
   * Supprime la fiche
   *
   */
  Function delete()
  {
    $this->db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition = $this->id ;";
    if ( $this->db->query($sql) ) 
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."expedition WHERE rowid = $this->id;";
	if ( $this->db->query($sql) ) 
	  {
	    $this->db->commit();
	    return 1;
	  }
	else
	  {
	    $this->db->rollback();
	    return -2;
	  }
      }
    else
      {
	$this->db->rollback();
	return -1;
      }
  }
  /**
   * Class la commande
   *
   *
   */
  Function classin($cat_id)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_projet = $cat_id";
      $sql .= " WHERE rowid = $this->id;";
      
      if ($this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}
    }
}


class ExpeditionLigne
{

}

?>
