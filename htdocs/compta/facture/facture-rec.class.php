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

class FactureRec
{
  var $id;
  var $db;
  var $socidp;
  var $number;
  var $author;
  var $date;
  var $ref;
  var $amount;
  var $remise;
  var $tva;
  var $total;
  var $note;
  var $db_table;
  var $propalid;
  var $projetid;

  /**
   * Initialisation de la class
   *
   */
  Function FactureRec($DB, $facid=0)
  {
    $this->db = $DB ;
    $this->facid = $facid;
  }
  /**
   * Créé la facture
   *
   *
   */
  Function create($user)
    {
      /*
       *
       */
      $facsrc = new Facture($this->db);
      
      if ($facsrc->fetch($this->facid) > 0) 
	{
	  /*
	   * On positionne en mode brouillon la facture
	   */
	  $this->brouillon = 1;
	  if (! $facsrc->projetid)
	    {
	      $facsrc->projetid = "NULL";
	    }

	  /*
	   *
	   */
	  	  	  	  
	  $sql = "INSERT INTO llx_facture_rec (titre, fk_soc, datec, amount, remise, remise_percent, note, fk_user_author,fk_projet, fk_cond_reglement) ";
	  $sql .= " VALUES ('$this->titre', $facsrc->socidp, now(), $facsrc->amount, $facsrc->remise, $facsrc->remise_percent, '$this->note',$user->id, $facsrc->projetid, $facsrc->cond_reglement_id)";      
	  if ( $this->db->query($sql) )
	    {
	      $this->id = $this->db->last_insert_id();
	      	      	      
	      /*
	       * Produits
	       *
	       */
	      for ($i = 0 ; $i < sizeof($facsrc->lignes) ; $i++)
		{
		  if ($facsrc->lignes[$i]->produit_id > 0)
		    {
		      $prod = new Product($this->db);
		      $prod->fetch($facsrc->lignes[$i]->produit_id);
		    }

		  
		  $result_insert = $this->addline($this->id, 
						  $facsrc->lignes[$i]->desc,
						  $facsrc->lignes[$i]->subprice,
						  $facsrc->lignes[$i]->qty,
						  $facsrc->lignes[$i]->tva_taux,
						  $facsrc->lignes[$i]->produit_id,
						  $facsrc->lignes[$i]->remise_percent);
		  
		  
		  if ( $result_insert < 0)
		    {
		      print '<br>' . $this->db->error() .'<br>';
		    }
		}
	      
	      return $this->id;
	    }
	  else
	    {
	      print $this->db->error() . '<b><br>'.$sql;
	      return 0;
	    }
	}
      else
	{
	  return -1;
	}
    }

  /**
   * Recupére l'objet facture
   *
   *
   */
  Function fetch($rowid, $societe_id=0)
    {

      $sql = "SELECT f.fk_soc,f.titre,f.amount,f.tva,f.total,f.total_ttc,f.remise,f.remise_percent,f.fk_projet, c.rowid as crid, c.libelle, c.libelle_facture, f.note, f.fk_user_author";
      $sql .= " FROM llx_facture_rec as f, llx_cond_reglement as c";
      $sql .= " WHERE f.rowid=$rowid AND c.rowid = f.fk_cond_reglement";
      
      if ($societe_id > 0) 
	{
	  $sql .= " AND f.fk_soc = ".$societe_id;
	}

      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id                 = $rowid;
	      $this->datep              = $obj->dp;
	      $this->titre              = $obj->titre;
	      $this->amount             = $obj->amount;
	      $this->remise             = $obj->remise;
	      $this->total_ht           = $obj->total;
	      $this->total_tva          = $obj->tva;
	      $this->total_ttc          = $obj->total_ttc;
	      $this->paye               = $obj->paye;
	      $this->remise_percent     = $obj->remise_percent;
	      $this->socidp             = $obj->fk_soc;
	      $this->statut             = $obj->fk_statut;
	      $this->date_lim_reglement     = $obj->dlr;
	      $this->cond_reglement_id      = $obj->crid;
	      $this->cond_reglement         = $obj->libelle;
	      $this->cond_reglement_facture = $obj->libelle_facture;
	      $this->projetid               = $obj->fk_projet;
	      $this->note                   = stripslashes($obj->note);
	      $this->user_author            = $obj->fk_user_author;
	      $this->lignes                 = array();

	      if ($this->statut == 0)
		{
		  $this->brouillon = 1;
		}

	      $this->db->free();

	      /*
	       * Lignes
	       */

	      $sql = "SELECT l.fk_product,l.description, l.subprice, l.price, l.qty, l.rowid, l.tva_taux, l.remise_percent";
	      $sql .= " FROM llx_facturedet_rec as l WHERE l.fk_facture = ".$this->id." ORDER BY l.rowid ASC";
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $i = 0; $total = 0;
		  
		  while ($i < $num)
		    {
		      $objp = $this->db->fetch_object($i);
		      $faclig = new FactureLigne();
		      $faclig->produit_id     = $objp->fk_product;
		      $faclig->desc           = stripslashes($objp->description);
		      $faclig->qty            = $objp->qty;
		      $faclig->price          = $objp->price;
		      $faclig->subprice          = $objp->subprice;
		      $faclig->tva_taux       = $objp->tva_taux;
		      $faclig->remise_percent = $objp->remise_percent;
		      $this->lignes[$i]       = $faclig;
		      $i++;
		    }
	    
		  $this->db->free();

		  return 1;
		} 
	      else
		{
		  print $this->db->error();
		  return -1;
		}
	    }
	  else
	    {
	      print "Error";
	      return -2;
	    }
	}
      else
	{
	  print $this->db->error();
	  return -3;
	}    
    }
  /**
   * Recupére l'objet client lié à la facture
   *
   */
  Function fetch_client()
    {
      $client = new Societe($this->db);
      $client->fetch($this->socidp);
      $this->client = $client;
	
    }
  /**
   * Valide la facture
   *
   *
   */
  Function valid($userid, $dir)
    {
      $sql = "UPDATE llx_facture SET fk_statut = 1, date_valid=now(), fk_user_valid=$userid";
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

  /**
   * Supprime la facture
   *
   */
  Function delete($rowid)
    {
      $sql = "DELETE FROM llx_facturedet_rec WHERE fk_facture = $rowid;";
	  
      if ($this->db->query( $sql) )
	{
	  $sql = "DELETE FROM llx_facture_rec WHERE rowid = $rowid";
	  
	  if ($this->db->query( $sql) )
	    {
	      return 1;
	    }
	  else
	    {
	      print "Err : ".$this->db->error();
	      return -1;
	    }
	}
      else
	{
	  print "Err : ".$this->db->error();
	  return -2;
	}
    }
  /**
   * Valide la facture
   *
   */
  Function set_valid($rowid, $user, $soc)
    {
      if ($this->brouillon)
	{
	  $action_notify = 2; // ne pas modifier cette valeur

	  $numfa = facture_get_num($soc); // définit dans includes/modules/facture

	  $sql = "UPDATE llx_facture set facnumber='$numfa', fk_statut = 1, fk_user_valid = $user->id WHERE rowid = $rowid ;";
	  $result = $this->db->query( $sql);

	  /*
	   * Notify
	   *
	   */
	  $filepdf = FAC_OUTPUTDIR . "/" . $this->ref . "/" . $this->ref . ".pdf";
	  
	  $mesg = "La facture ".$this->ref." a été validée.\n";
	  
	  $notify = New Notify($this->db);
	  $notify->send($action_notify, $this->socidp, $mesg, "facture", $rowid, $filepdf);
	  /*
	   * Update Stats
	   *
	   */
	  $sql = "SELECT fk_product FROM llx_facturedet WHERE fk_facture = ".$this->id;
	  $sql .= " AND fk_product IS NOT NULL";
	  
	  $result = $this->db->query($sql);
	  
	  if ($result)
	    {
	      $num = $this->db->num_rows();
	      $i = 0;
	      while ($i < $num)	  
		{
		  $obj = $this->db->fetch_object($i);
		  
		  $sql = "UPDATE llx_product SET nbvente=nbvente+1 WHERE rowid = ".$obj->fk_product;
		  $db2 = $this->db->clone();
		  $result = $db2->query($sql);
		  $i++;
		}
	    }
	  /*
	   * Contrats
	   */      
	  $contrat = new Contrat($this->db);
	  $contrat->create_from_facture($this->id, $user, $this->socidp);
      
	  return $result;
	}
    }
  /**
   * Ajoute un produit dans la facture
   *
   */
  Function add_product($idproduct, $qty, $remise_percent)
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
   * Ajoute une ligne de facture
   *
   */
  Function addline($facid, $desc, $pu, $qty, $txtva, $fk_product='NULL', $remise_percent=0)
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

	  $sql = "INSERT INTO llx_facturedet_rec (fk_facture,description,price,qty,tva_taux, fk_product, remise_percent, subprice, remise)";
	  $sql .= " VALUES ($facid, '$desc', $price, $qty, $txtva, $fk_product, $remise_percent, $subprice, $remise) ;";

	  if ( $this->db->query( $sql) )
	    {
	      $this->updateprice($facid);
	      return 1;
	    }
	  else
	    {
	      print "$sql";
	      return -1;
	    }
	}
    }
  /**
   * Mets à jour une ligne de facture
   *
   */
  Function updateline($rowid, $desc, $pu, $qty, $remise_percent=0)
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

	  $sql = "UPDATE llx_facturedet set description='$desc',price=$price,subprice=$subprice,remise=$remise,remise_percent=$remise_percent,qty=$qty WHERE rowid = $rowid ;";
	  $result = $this->db->query( $sql);

	  $this->updateprice($this->id);
	}
    }
  /**
   * Supprime une ligne
   *
   */
  Function deleteline($rowid)
    {
      if ($this->brouillon)
	{
	  $sql = "DELETE FROM llx_facturedet WHERE rowid = $rowid;";
	  $result = $this->db->query( $sql);

	  $this->updateprice($this->id);
	}
    }
  /**
   * Mise à jour des sommes de la facture
   *
   */
  Function updateprice($facid)
    {
      include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";
      $err=0;
      $sql = "SELECT price, qty, tva_taux FROM llx_facturedet_rec WHERE fk_facture = $facid;";
  
      $result = $this->db->query($sql);

      if ($result)
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)	  
	    {
	      $obj = $this->db->fetch_object($i);

	      $products[$i][0] = $obj->price;
	      $products[$i][1] = $obj->qty;
	      $products[$i][2] = $obj->tva_taux;

	      $i++;
	    }

	  $this->db->free();
	  /*
	   *
	   */
	  $calculs = calcul_price($products, $this->remise_percent);

	  $this->total_remise   = $calculs[3];
	  $this->amount_ht      = $calculs[4];
	  $this->total_ht       = $calculs[0];
	  $this->total_tva      = $calculs[1];
	  $this->total_ttc      = $calculs[2];
	  $tvas                 = $calculs[5];
	  /*
	   *
	   */

	  $sql = "UPDATE llx_facture_rec SET amount = $this->amount_ht, remise=$this->total_remise,  total=$this->total_ht, tva=$this->total_tva, total_ttc=$this->total_ttc";
	  $sql .= " WHERE rowid = $facid ;";
	  
	  if ( $this->db->query($sql) )
	    {
	      if ($err == 0)
		{
		  return 1;	  
		}
	      else
		{
		  return -3;
		}
	    }
	  else
	    {
	      print "$sql<br>";
	      return -2;
	    }
	}
      else
	{
	  print "Error";
	  return -1;
	}
    }
  /**
   * Applique une remise
   *
   */
  Function set_remise($user, $remise)
    {
      if ($user->rights->facture->creer)
	{

	  $this->remise_percent = $remise ;

	  $sql = "UPDATE llx_facture SET remise_percent = ".ereg_replace(",",".",$remise);
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $this->updateprice($this->id);
	      return 1;
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	      return 0;
	    }
	}
  }
  
}
?>
