<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class FactureLigne
{
  Function FactureLigne()
    {
    }
}

class Facture
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
  var $paye;
  var $db_table;
  var $propalid;
  var $projetid;

  /**
   * Initialisation de la class
   *
   */
  Function Facture($DB, $soc_idp="", $facid="")
    {
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->products = array();
      $this->db_table = MAIN_DB_PREFIX."facture";
      $this->amount = 0;
      $this->remise = 0;
      $this->remise_percent = 0;
      $this->tva = 0;
      $this->total = 0;
      $this->propalid = 0;
      $this->projetid = 0;
      $this->id = $facid;
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

      /* Facture récurrente */
      if ($this->fac_rec > 0)
	{
	  require_once DOL_DOCUMENT_ROOT . '/compta/facture/facture-rec.class.php';
	  $_facrec = new FactureRec($this->db, $this->fac_rec);
	  $_facrec->fetch($this->fac_rec);

	  $this->projetid       = $_facrec->projetid;
	  $this->cond_reglement = $_facrec->cond_reglement_id;
	  $this->amount         = $_facrec->amount;
	  $this->remise         = $_facrec->remise;
	  $this->remise_percent = $_facrec->remise_percent;
	}

      $sql = "SELECT fdm,nbjour FROM ".MAIN_DB_PREFIX."cond_reglement WHERE rowid = $this->cond_reglement";
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      $cdr_nbjour = $obj->nbjour;
	      $cdr_fdm = $obj->fdm;
	    }
	  $this->db->free();
	}
      $datelim = $this->date + ( $cdr_nbjour * 3600 * 24 );
      /*
       *  Insertion dans la base
       */
      $socid = $this->socidp;
      $number = $this->number;
      $amount = $this->amount;
      $remise = $this->remise;
      
      if (! $remise)
	{
	  $remise = 0 ;
	}

      if (! $this->projetid)
	{
	  $this->projetid = "NULL";
	}
      
      $totalht = ($amount - $remise);
      $tva = tva($totalht);
      $total = $totalht + $tva;
      
      $sql = "INSERT INTO $this->db_table (facnumber, fk_soc, datec, amount, remise, remise_percent, datef, note, fk_user_author,fk_projet, fk_cond_reglement, date_lim_reglement) ";
      $sql .= " VALUES ('$number', $socid, now(), $totalht, $remise, $this->remise_percent, ".$this->db->idate($this->date).",'$this->note',$user->id, $this->projetid, $this->cond_reglement,".$this->db->idate($datelim).")";      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET facnumber='(PROV".$this->id.")' WHERE rowid=".$this->id;
	  $this->db->query($sql);

	  if ($this->id && $this->propalid)
	    {
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."fa_pr (fk_facture, fk_propal) VALUES (".$this->id.",".$this->propalid.")";
	      $this->db->query($sql);
	    }

	  if ($this->id && $this->commandeid)
	    {
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."co_fa (fk_facture, fk_commande) VALUES (".$this->id.",".$this->commandeid.")";
	      $this->db->query($sql);
	    }

	  /*
	   * Produits
	   *
	   */
	  for ($i = 0 ; $i < sizeof($this->products) ; $i++)
	    {
	      $prod = new Product($this->db, $this->products[$i]);
	      $prod->fetch($this->products[$i]);

	      $result_insert = $this->addline($this->id, 
					      $prod->libelle,
					      $prod->price,
					      $this->products_qty[$i], 
					      $prod->tva_tx, 
					      $this->products[$i], 
					      $this->products_remise_percent[$i]);


	      if ( $result_insert < 0)
		{
		  print $sql . '<br>' . $this->db->error() .'<br>';
		}
	    }
	  /*
	   * Produits de la facture récurrente
	   *
	   */
	  if ($this->fac_rec > 0)
	    {
	      for ($i = 0 ; $i < sizeof($_facrec->lignes) ; $i++)
		{
		  if ($_facrec->lignes[$i]->produit_id)
		    {
		      $prod = new Product($this->db, $_facrec->lignes[$i]->produit_id);
		      $prod->fetch($_facrec->lignes[$i]->produit_id);
		    }
		  
		  $result_insert = $this->addline($this->id, 
						  addslashes($_facrec->lignes[$i]->desc),
						  $_facrec->lignes[$i]->subprice,
						  $_facrec->lignes[$i]->qty,
						  $_facrec->lignes[$i]->tva_taux,
						  $_facrec->lignes[$i]->produit_id,
						  $_facrec->lignes[$i]->remise_percent);
		  
		  
		  if ( $result_insert < 0)
		    {
		      print $sql . '<br>' . $this->db->error() .'<br>';
		    }
		}
	    }
	  /*
	   *
	   *
	   */
	  $this->updateprice($this->id);	  
	  return $this->id;
	}
      else
	{
	  print $this->db->error() . '<b><br>'.$sql;
	  return 0;
	}
    }

  /**
   * Recupére l'objet facture
   *
   *
   */
  Function fetch($rowid, $societe_id=0)
    {
      $sql = "SELECT f.fk_soc,f.facnumber,f.amount,f.tva,f.total,f.total_ttc,f.remise,f.remise_percent,".$this->db->pdate("f.datef")."as df,f.fk_projet,".$this->db->pdate("f.date_lim_reglement")." as dlr, c.rowid as cond_regl_id, c.libelle, c.libelle_facture, f.note, f.paye, f.fk_statut, f.fk_user_author";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."cond_reglement as c";
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
	      $this->date               = $obj->df;
	      $this->ref                = $obj->facnumber;
	      $this->amount             = $obj->amount;
	      $this->remise             = $obj->remise;
	      $this->total_ht           = $obj->total;
	      $this->total_tva          = $obj->tva;
	      $this->total_ttc          = $obj->total_ttc;
	      $this->paye               = $obj->paye;
	      $this->remise_percent     = $obj->remise_percent;
	      $this->socidp             = $obj->fk_soc;
	      $this->statut             = $obj->fk_statut;
	      $this->date_lim_reglement = $obj->dlr;
	      $this->cond_reglement_id  = $obj->cond_regl_id;
	      $this->cond_reglement     = $obj->libelle;
	      $this->cond_reglement_facture = $obj->libelle_facture;
	      $this->projetid           = $obj->fk_projet;
	      $this->note               = stripslashes($obj->note);
	      $this->user_author        = $obj->fk_user_author;
	      $this->lignes             = array();

	      if ($this->statut == 0)
		{
		  $this->brouillon = 1;
		}

	      $this->db->free();

	      /*
	       * Lignes
	       */

	      $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.remise, l.remise_percent, l.subprice";
	      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l WHERE l.fk_facture = ".$this->id;
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $i = 0; $total = 0;
		  
		  while ($i < $num)
		    {
		      $objp = $this->db->fetch_object($i);
		      $faclig = new FactureLigne();
		      $faclig->desc           = stripslashes($objp->description);
		      $faclig->qty            = $objp->qty;
		      $faclig->price          = $objp->price;
		      $faclig->subprice       = $objp->subprice;
		      $faclig->tva_taux       = $objp->tva_taux;
		      $faclig->remise         = $objp->remise;
		      $faclig->remise_percent = $objp->remise_percent;
		      $faclig->produit_id     = $objp->fk_product;
		      $this->lignes[$i] = $faclig;
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
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_statut = 1, date_valid=now(), fk_user_valid=$userid";
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
   * Class la facture
   *
   *
   */
  Function classin($cat_id)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_projet = $cat_id";
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

  /**
   * Supprime la facture
   *
   */
  Function delete($rowid)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_tva_sum WHERE fk_facture = $rowid;";

      if ( $this->db->query( $sql) )
	{
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_facture = $rowid;";

	  if ($this->db->query( $sql) )
	    {

	      $sql = "DELETE FROM ".MAIN_DB_PREFIX."co_fa WHERE fk_facture = $rowid;";
	      
	      if ($this->db->query( $sql) )
		{
		  $sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture = $rowid;";
	      
		  if ($this->db->query( $sql) )
		    {
		      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture WHERE rowid = $rowid AND fk_statut = 0;";
		      
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
	      else
		{
		  print "Err : ".$this->db->error();
		  return -3;
		}
	    }
	  else
	    {
	      print "Err : ".$this->db->error();
	      return -4;
	    }
	}
      else
	{
	  print "Err : ".$this->db->error();
	  return -5;
	}
    }

  /**
   * Retourne le libellé du statut d'une facture (brouillon, validée, annulée, payée)
   *
   */
  Function get_libstatut()
    {
		if (! $this->paye)
		  {
		    if ($this->statut == 0) return 'Brouillon (à valider)';
		    if ($this->statut == 3) return 'Annulée';
			return 'Validée (à payer)';
		  }
		else
		  {
		    return 'Payée';
		  }
    }

  /**
   * Tag la facture comme payée complètement
   *
   */
  Function set_payed($rowid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture set paye=1 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }
  /**
   * Tag la facture comme paiement commencée
   *
   */
  Function set_paiement_started($rowid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture set fk_statut=2 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }
  /**
   * Tag la facture comme annulée
   *
   */
  Function set_canceled($rowid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture set fk_statut=3 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }
  /**
   * Tag la facture comme validée et valide la facture
   *
   */
  Function set_valid($rowid, $user, $soc)
    {
      if ($this->brouillon)
	{
	  $action_notify = 2; // ne pas modifier cette valeur

	  $numfa = facture_get_num($soc); // définit dans includes/modules/facture

	  $sql = "UPDATE ".MAIN_DB_PREFIX."facture set facnumber='$numfa', fk_statut = 1, fk_user_valid = $user->id WHERE rowid = $rowid ;";
	  $result = $this->db->query( $sql);

	  if (! $result) { print "Err : ".$this->db->error(); return -1; }
     
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
	  $sql = "SELECT fk_product FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture = ".$this->id;
	  $sql .= " AND fk_product > 0";
	  
	  $result = $this->db->query($sql);
	  
	  if ($result)
	    {
	      $num = $this->db->num_rows();
	      $i = 0;
	      while ($i < $num)	  
		{
		  $obj = $this->db->fetch_object($i);
		  
		  $sql = "UPDATE ".MAIN_DB_PREFIX."product SET nbvente=nbvente+1 WHERE rowid = ".$obj->fk_product;
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
  Function addline($facid, $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
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

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."facturedet (fk_facture,description,price,qty,tva_taux, fk_product, remise_percent, subprice, remise)";
	  $sql .= " VALUES ($facid, '$desc', $price, $qty, $txtva, $fk_product, $remise_percent, $subprice, $remise) ;";

	  if ( $this->db->query( $sql) )
	    {
	      $this->updateprice($facid);
	      return 1;
	    }
	  else
	    {
	      print "<br>$sql<br>";
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

	  $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set description='$desc',price=$price,subprice=$subprice,remise=$remise,remise_percent=$remise_percent,qty=$qty WHERE rowid = $rowid ;";
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
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE rowid = $rowid;";
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
      $sql = "SELECT price, qty, tva_taux FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture = $facid;";
  
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

	  $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET amount = $this->amount_ht, remise=$this->total_remise,  total=$this->total_ht, tva=$this->total_tva, total_ttc=$this->total_ttc";
	  $sql .= " WHERE rowid = $facid ;";
	  
	  if ( $this->db->query($sql) )
	    {
	      
	      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_tva_sum WHERE fk_facture=".$this->id;

	      if ( $this->db->query($sql) )
		{
		  foreach ($tvas as $key => $value)
		    {
		      $sql = "REPLACE INTO ".MAIN_DB_PREFIX."facture_tva_sum SET fk_facture=".$this->id;
		      $sql .= ", amount = ".$tvas[$key];
		      $sql .= ", tva_tx=".$key;
		      
		      if (! $this->db->query($sql) )
			{
			  print "$sql<br>";
			  $err++;
			}
		    }
		}
	      else
		{
		  $err++;
		}

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

	  $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET remise_percent = ".ereg_replace(",",".",$remise);
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
  /**
   * Envoie une relance
   *
   *
   */
  Function send_relance($destinataire, $replytoname, $replytomail, $user)
    {
      $soc = new Societe($this->db, $this->socidp);

      $file = FAC_OUTPUTDIR . "/" . $this->ref . "/" . $this->ref . ".pdf";

      if (file_exists($file))
	{

	  $sendto = $soc->contact_get_email($destinataire);
	  $sendtoid = $destinataire;
	  
	  if (strlen($sendto))
	    {
	      
	      $subject = "Relance facture $this->ref";
	      $message = "Nous apportons à votre connaissance que la facture $this->ref n'a toujours pas été réglée.\n\nCordialement\n\n";
	      $filename = "$this->ref.pdf";
	      
	      $replyto = $replytoname . " <".$replytomail .">";
	      
	      $mailfile = new CMailFile($subject,
					$sendto,
					$replyto,
					$message,
					array($file), 
					array("application/pdf"), 
					array($filename)
					);
	      
	      if ( $mailfile->sendfile() )
		{
		  
		  $sendto = htmlentities($sendto);
		  
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), 10 ,$this->socidp ,'Relance envoyée à $sendto',$this->id, $sendtoid, $user->id, 'Relance Facture par mail',100);";
		  
		  if (! $this->db->query($sql) )
		    {
		      print $this->db->error();
		      print "<p>$sql</p>";
		    }	      	      	      
		}
	      else
		{
		  print "<b>!! erreur d'envoi<br>$sendto<br>$replyto<br>$filename";
		}	  
	    }
	  else
	    {
	      print "Can't get email $sendto";
	    }
	}      
    }
  /** 
   * Renvoie la liste des sommes de tva
   *
   */
  Function getSumTva()
  {
    $sql = "SELECT amount, tva_tx FROM ".MAIN_DB_PREFIX."facture_tva_sum WHERE fk_facture = ".$this->id;
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)	  
	  {
	    $row = $this->db->fetch_row($i);
	    $tvs[$row[1]] = $row[0];
	    $i++;
	  }
	
	return $tvs;
      }
    else
      {
	return -1;
      }
  }
  /** 
   * Renvoie la sommes des paiements
   *
   */
  Function getSommePaiement()
  {
    $sql = "SELECT sum(amount) FROM ".MAIN_DB_PREFIX."paiement WHERE fk_facture = ".$this->id;
    if ($this->db->query($sql))
      {
	$row = $this->db->fetch_row(0);
	return $row[0];
      }
    else
      {
	return -1;
      }
  }
  /**
   * RODO TODO
   *
   */
  Function pdf()
    {      

    }
  
}
?>
