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

class Commande 
{
  var $db ;
  var $id ;
  var $brouillon;

  Function Commande($DB)
    {
      $this->db = $DB;

      $this->sources[0] = "Proposition commerciale";
      $this->sources[1] = "OsCommerce";
      $this->sources[2] = "Papier";
      $this->sources[3] = "Téléphone";
      $this->sources[4] = "Fax";
    }
  /**
   * Créé la facture depuis une propale existante
   *
   *
   */
  Function create_from_propale($user, $propale_id)
    {
      $propal = new Propal($this->db);
      $propal->fetch($propale_id);

      $this->lines = array();

      $this->date_commande = "now()";

      for ($i = 0 ; $i < sizeof($propal->lignes) ; $i++)
	{
	  $CommLigne = new CommandeLigne();

	  $CommLigne->libelle        = $propal->lignes[$i]->libelle;
	  $CommLigne->price          = $propal->lignes[$i]->subprice;
	  $CommLigne->subprice       = $propal->lignes[$i]->subprice;
	  $CommLigne->tva_tx         = $propal->lignes[$i]->tva_tx;
	  $CommLigne->qty            = $propal->lignes[$i]->qty;
	  $CommLigne->remise_percent = $propal->lignes[$i]->remise_percent;
	  $CommLigne->product_id     = $propal->lignes[$i]->product_id;

	  $this->lines[$i] = $CommLigne;
	}

      $this->soc_id = $propal->soc_id;
      $this->propale_id = $propal->id;
      return $this->create($user);
    }
  /**
   * Valide la commande
   *
   *
   */
  Function valid($user)
    {
      if ($user->rights->commande->valider)
	{

	  $sql = "UPDATE llx_commande SET fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
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

  /**
   * Créé la facture
   *
   *
   */
  Function create($user)
    {
      /* On positionne en mode brouillon la facture */
      $this->brouillon = 1;

      if (! $remise)
	{
	  $remise = 0 ;
	}

      if (! $this->projetid)
	{
	  $this->projetid = 0;
	}
      
      $sql = "INSERT INTO llx_commande (fk_soc, date_creation, fk_user_author, fk_projet, date_commande) ";
      $sql .= " VALUES ($this->soc_id, now(), $user->id, $this->projetid, $this->date_commande)";
      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  $sql = "UPDATE llx_commande SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
	  if ($this->db->query($sql))
	    {

	      if ($this->id && $this->propale_id)
		{
		  $sql = "INSERT INTO llx_co_pr (fk_commande, fk_propale) VALUES (".$this->id.",".$this->propale_id.")";
		  $this->db->query($sql);
		}
	      /*
	       * Produits
	       *
	       */
	      for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
		{
		  $result_insert = $this->insert_product_generic(
								 $this->lines[$i]->libelle,
								 $this->lines[$i]->price,
								 $this->lines[$i]->qty,
								 $this->lines[$i]->tva_tx, 
								 $this->lines[$i]->remise_percent,
								 $this->lines[$i]->product_id);
		  if ( $result_insert < 0)
		    {
		      print $sql . '<br>' . $this->db->error() .'<br>';
		    }
		}
	      
	      /*
	       *
	       *
	       */
	      return $this->id;
	    }
	  else
	    {
	      return -1;
	    }
	}
      else
	{
	  print $this->db->error() . '<b><br>'.$sql;
	  return 0;
	}
    }
  /**
   * Ajoute un produit
   *
   */
  Function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx=19.6, $p_product_id=0, $remise_percent=0)
    {
      if ($this->statut == 0)
	{


	  print $p_product_id;

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

	  $sql = "INSERT INTO llx_commandedet (fk_commande, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
	  $sql .= " (".$this->id.", $p_product_id,". $p_qty.",". $price.",".$p_tva_tx.",'".$p_desc."',$remise_percent, $subprice) ; ";
	  
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
   * Ajoute une ligne de commande
   *
   */
  Function addline( $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
    {
      if ($this->brouillon && strlen(trim($desc)))
	{
	  if (strlen(trim($qty))==0)
	    {
	      $qty=1;
	    }

	  if ($fk_product > 0)
	    {
	      $prod = new Product($this->db, $fk_product);
	      if ($prod->fetch($fk_product) > 0)
		{
		  $desc  = $prod->libelle;
		  $pu    = $prod->price;
		  $txtva = $prod->tva_tx;
		}
	    }


	  $remise = 0;
	  $price = round(ereg_replace(",",".",$pu), 2);
	  $subprice = $price;
	  if (trim(strlen($remise_percent)) > 0)
	    {
	      $remise = round(($pu * $remise_percent / 100), 2);
	      $price = $pu - $remise;
	    }

	  $sql = "INSERT INTO llx_commandedet (fk_commande,label,description,price,qty,tva_tx, fk_product, remise_percent, subprice, remise)";
	  $sql .= " VALUES ($this->id, '$desc','$desc', $price, $qty, $txtva, $fk_product, $remise_percent, $subprice, $remise) ;";

	  if ( $this->db->query( $sql) )
	    {
	      $this->update_price();
	      return 1;
	    }
	  else
	    {
	      print "<br>$sql<br>".$this->db->error();
	      return -1;
	    }
	}
    }

  /** 
   *
   * Lit une commande
   *
   */
  Function fetch ($id)
    {
      $sql = "SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva";
      $sql .= ", ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet, c.remise_percent";
      $sql .= " FROM llx_commande as c";
      $sql .= " WHERE c.rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $obj = $this->db->fetch_object();

	  $this->id              = $obj->rowid;
	  $this->ref             = $obj->ref;
	  $this->soc_id          = $obj->fk_soc;
	  $this->statut          = $obj->fk_statut;
	  $this->user_author_id  = $obj->fk_user_author;
	  $this->total_ht        = $obj->total_ht;
	  $this->total_tva       = $obj->tva;
	  $this->total_ttc       = $obj->total_ttc;
	  $this->date            = $obj->date_commande;
	  $this->remise_percent  = $obj->remise_percent;

	  $this->projet_id       = $obj->fk_projet;

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
   *
   *
   */
  Function delete_line($idligne)
    {
      if ($this->statut == 0)
	{
	  $sql = "DELETE FROM llx_commandedet WHERE rowid = $idligne";
	  
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

  /*
   *
   *
   */
  Function update_price()
    {
      include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";

      /*
       *  Liste des produits a ajouter
       */
      $sql = "SELECT price, qty, tva_tx FROM llx_commandedet WHERE fk_commande = $this->id";
      if ( $this->db->query($sql) )
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object($i);
	      $products[$i][0] = $obj->price;
	      $products[$i][1] = $obj->qty;
	      $products[$i][2] = $obj->tva_tx;
	      $i++;
	    }
	}
      $calculs = calcul_price($products, $this->remise_percent);

      $totalht = $calculs[0];
      $totaltva = $calculs[1];
      $totalttc = $calculs[2];
      $total_remise = $calculs[3];

      $this->remise         = $total_remise;
      $this->total_ht       = $totalht;
      $this->total_tva      = $totaltva;
      $this->total_ttc      = $totalttc;
      /*
       *
       */
      $sql = "UPDATE llx_commande set amount_ht=$totalht, total_ht=$totalht, tva=$totaltva, total_ttc=$totalttc, remise=$total_remise WHERE rowid = $this->id";
      if ( $this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print "Erreur mise à jour du prix<p>".$sql;
	  return -1;
	}
    }

  /**
   * Supprime la commande
   *
   */
  Function delete()
  {
    $sql = "DELETE FROM llx_commandedet WHERE fk_commande = $this->id ;";
    if ( $this->db->query($sql) ) 
      {
	$sql = "DELETE FROM llx_commande WHERE rowid = $this->id;";
	if ( $this->db->query($sql) ) 
	  {
	    return 1;
	  }
	else
	  {
	    return -2;
	  }
      }
    else
      {
	return -1;
      }
  }
  /**
   * Class la facture
   *
   *
   */
  Function classin($cat_id)
    {
      $sql = "UPDATE llx_commande SET fk_projet = $cat_id";
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

class CommandeLigne
{
  var $pu;
}

?>
