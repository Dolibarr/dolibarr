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
  /**
   * Initialisation
   *
   */
  Function Commande($DB)
    {
      $this->db = $DB;

      $this->sources[0] = "Proposition commerciale";
      $this->sources[1] = "Internet";
      $this->sources[2] = "Courrier";
      $this->sources[3] = "Téléphone";
      $this->sources[4] = "Fax";

      $this->statuts[-1] = "Annulée";
      $this->statuts[0] = "Brouillon";
      $this->statuts[1] = "Validée";
      $this->statuts[2] = "En traitement";
      $this->statuts[3] = "Traitée";

      $this->products = array();
    }
  /**
   * Créé la facture depuis une propale existante
   *
   */
  Function create_from_propale($user, $propale_id)
    {
      $propal = new Propal($this->db);
      $propal->fetch($propale_id);

      $this->lines = array();

      $this->date_commande = time();
      $this->source = 0;

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
   */
  Function valid($user)
    {
      $result = 0;
      if ($user->rights->commande->valider)
	{
	  if (defined("COMMANDE_ADDON"))
	    {
	      if (is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/commande/".COMMANDE_ADDON.".php"))
		{
		  require_once DOL_DOCUMENT_ROOT ."/includes/modules/commande/".COMMANDE_ADDON.".php";
		  
		  $modName = COMMANDE_ADDON;
		  $objMod = new $modName($this->db);
		  $num = $objMod->commande_get_num();

		  $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
		  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
		  
		  if ($this->db->query($sql) )
		    {
		      $result = 1;
		    }
		  else
		    {
		      $result = -1;
		      print $this->db->error() . ' in ' . $sql;
		    }
		  
		}
	      else
		{
		  print "Impossible de lire " ;
		}
	    }
	  else
	    {
	      print "Impossible de lire " ;
	    }
	}
      return $result ;
    }
  /**
   * Cloture la commande
   *
   */
  Function cloture($user)
    {
      if ($user->rights->commande->valider)
	{

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 3";
	  $sql .= " WHERE rowid = $this->id AND fk_statut > 0 ;";
	  
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
   * Annule la commande
   *
   */
  Function cancel($user)
    {
      if ($user->rights->commande->valider)
	{

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = -1";
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 1 ;";
	  
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
   * Créé la commande
   *
   */
  Function create($user)
    {
      /* On positionne en mode brouillon la commande */
      $this->brouillon = 1;

      if (! $remise)
	{
	  $remise = 0 ;
	}

      if (! $this->projetid)
	{
	  $this->projetid = 0;
	}
      
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande (fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source) ";
      $sql .= " VALUES ($this->soc_id, now(), $user->id, $this->projetid, ".$this->db->idate($this->date_commande).", $this->source)";
      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  /*
	   *  Insertion des produits dans la base
	   */
	  for ($i = 0 ; $i < sizeof($this->products) ; $i++)
	    {
	      $prod = new Product($this->db, $this->products[$i]);
	      if ($prod->fetch($this->products[$i]))
		{		    
		  $this->insert_product_generic($prod->libelle,
						$prod->price,
						$this->products_qty[$i], 
						$prod->tva_tx,
						$this->products[$i], 
						$this->products_remise_percent[$i]);
		}
	    }
	  /*
	   *
	   *
	   */

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
	  if ($this->db->query($sql))
	    {

	      if ($this->id && $this->propale_id)
		{
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."co_pr (fk_commande, fk_propale) VALUES (".$this->id.",".$this->propale_id.")";
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
								 $this->lines[$i]->product_id,
								 $this->lines[$i]->remise_percent);
								 
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
	  $sql .= " ('".$this->id."', '$p_product_id','". $p_qty."','". $price."','".$p_tva_tx."',''".addslashes($p_desc)."','$remise_percent', '$subprice') ; ";
	  
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

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet (fk_commande,label,description,price,qty,tva_tx, fk_product, remise_percent, subprice, remise)";
	  $sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "', $price, $qty, $txtva, $fk_product, $remise_percent, $subprice, $remise) ;";

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
   * Ajoute un produit dans la commande
   *
   */
  Function add_product($idproduct, $qty, $remise_percent=0)
    {
      if ($idproduct > 0)
	{
	  $i = sizeof($this->products);
	  $this->products[$i] = $idproduct;
	  if (!$qty)
	    {
	      $qty = 1 ;
	    }
	  $this->products_qty[$i] = $qty;
	  $this->products_remise_percent[$i] = $remise_percent;
	}
    }

  /** 
   * Lit une commande
   *
   */
  Function fetch ($id)
    {
      $sql = "SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva";
      $sql .= ", ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet, c.remise_percent, c.source, c.facture";
      $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
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

	  $this->source          = $obj->source;
	  $this->facturee        = $obj->facture;
	  $this->projet_id       = $obj->fk_projet;

	  $this->db->free();
	  
	  if ($this->statut == 0)
	    $this->brouillon = 1;
	  
	  /*
	   * Propale associée
	   */
	  $sql = "SELECT fk_propale FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_commande = ".$this->id;
	  if ($this->db->query($sql) )
	    {
	      if ($this->db->num_rows())
		{
		  $obj = $this->db->fetch_object(0);
		  $this->propale_id = $obj->fk_propale;
		}
	    }

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
  Function fetch_lignes($only_product=0)
  {
    $this->lignes = array();

    $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";

    if ($only_product==1)
      {
	$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as l LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = l.fk_product) WHERE l.fk_commande = ".$this->id." AND p.fk_product_type <> 1 ORDER BY l.rowid";
      }
    else
      {
	$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as l WHERE l.fk_commande = $this->id ORDER BY l.rowid";	  
      }

    $result = $this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $ligne = new CommandeLigne();

	    $objp = $this->db->fetch_object( $i);

	    $ligne->id = $objp->rowid;

	    $ligne->qty            = $objp->qty;
	    $ligne->price          = $objp->price;
	    $ligne->tva_tx         = $objp->tva_tx;
	    $ligne->subprice       = $objp->subprice;
	    $ligne->remise_percent = $objp->remise_percent;
	    $ligne->product_id     = $objp->fk_product;
	    $ligne->description    = stripslashes($objp->description);	    

	    $this->lignes[$i] = $ligne;	    
	    $i++;
	  }	      
	$this->db->free();
      }

    return $this->lignes;
  }
  /**
   * Renvoie un tableau avec les livraison par ligne
   *
   *
   */
  Function livraison_array()
  {
    $this->livraisons = array();

    $sql = "SELECT fk_product, sum(ed.qty)";
    $sql .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as cd";
    $sql .=" WHERE ed.fk_commande_ligne = cd .rowid AND cd.fk_commande = c.rowid";
    $sql .= " AND cd.fk_commande =" .$this->id;
    $sql .= " GROUP BY fk_product ";
    $result = $this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row( $i);

	    $this->livraisons[$row[0]] = $row[1];

	    $i++;
	  }	      
	$this->db->free();
      }
  }
  /**
   * Renvoie un tableau avec les livraison par ligne
   *
   */
  Function nb_expedition()
  {
    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."expedition as e";
    $sql .=" WHERE e.fk_commande = $this->id";

    $result = $this->db->query($sql);
    if ($result)
      {
	$row = $this->db->fetch_row(0);

	return $row[0];
      }
  }

  /** 
   * Supprime une ligne de la commande
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
   *
   *
   */
  Function set_remise($user, $remise)
    {
      if ($user->rights->commande->creer)
	{

	  $remise = ereg_replace(",",".",$remise);

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET remise_percent = ".$remise;
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $this->remise_percent = $remise;
	      $this->update_price();
	      return 1;
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	    }
	}
    }
  /**
   * Classe la facture comme facturée
   *
   */
  Function classer_facturee()
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET facture = 1";
      $sql .= " WHERE rowid = ".$this->id." AND fk_statut > 0 ;";
      
      if ($this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}

    }
  /**
   * Mettre à jour le prix
   *
   */
  Function update_price()
    {
      include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";

      /*
       *  Liste des produits a ajouter
       */
      $sql = "SELECT price, qty, tva_tx FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = $this->id";
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
      $sql = "UPDATE ".MAIN_DB_PREFIX."commande set amount_ht=$totalht, total_ht=$totalht, tva=$totaltva, total_ttc=$totalttc, remise=$total_remise WHERE rowid = $this->id";
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
   * Mets à jour une ligne de commande
   *
   */
  Function update_line($rowid, $desc, $pu, $qty, $remise_percent=0)
    {
      if ($this->brouillon)
	{
	  if (strlen(trim($qty))==0)
	    {
	      $qty=1;
	    }
	  $remise = 0;
	  $price = round(ereg_replace(",",".",$pu), 2);
	  $subprice = $price;
	  if (trim(strlen($remise_percent)) > 0)
	    {
	      $remise = round(($pu * $remise_percent / 100), 2);
	      $price = $pu - $remise;
	    }
	  else
	    {
	      $remise_percent=0;
	    }

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET description='$desc',price=$price,subprice=$subprice,remise=$remise,remise_percent=$remise_percent,qty=$qty WHERE rowid = $rowid ;";
	  if ( $this->db->query( $sql) )
	    {
	      $this->update_price($this->id);
	    }
	  else
	    {
	      print "Erreur : $sql";
	    }
	}
    }


  /**
   * Supprime la commande
   *
   */
  Function delete()
  {
    $err = 0;

    $this->db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = $this->id ;";
    if (! $this->db->query($sql) ) 
      {
	$err++;
      }

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande WHERE rowid = $this->id;";
    if (! $this->db->query($sql) ) 
      {
	$err++;
      }

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_commande = $this->id;";
    if (! $this->db->query($sql) ) 
      {
	$err++;
      }

    if ($err == 0)
      {
	$this->db->commit();
	return 1;
      }
    else
      {
	$this->db->rollback();
	return -1;
      }
  }
  /**
   * Classe la commande
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

class CommandeLigne
{
  var $pu;
}

?>
