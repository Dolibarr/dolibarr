<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  var $db_table;
  var $propalid;
  var $projetid;
  /*
   * Initialisation
   *
   */

  Function Facture($DB, $soc_idp="", $facid="")
    {
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->products = array();
      $this->db_table = "llx_facture";
      $this->amount = 0;
      $this->remise = 0;
      $this->tva = 0;
      $this->total = 0;
      $this->propalid = 0;
      $this->projetid = 0;
      $this->id = $facid;
  }
  /*
   *
   *
   *
   */

  Function create($userid)
    {
      /*
       *
       */
      $sql = "SELECT fdm,nbjour FROM llx_cond_reglement WHERE rowid = $this->cond_reglement";
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
      
      $sql = "INSERT INTO $this->db_table (facnumber, fk_soc, datec, amount, remise, tva, total, datef, note, fk_user_author,fk_projet, fk_cond_reglement, date_lim_reglement) ";
      $sql .= " VALUES ('$number', $socid, now(), $totalht, $remise, $tva, $total,".$this->db->idate($this->date).",'$this->note',$userid, $this->projetid, $this->cond_reglement,".$this->db->idate($datelim).")";      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  if ($this->id && $this->propalid)
	    {
	      $sql = "INSERT INTO llx_fa_pr (fk_facture, fk_propal) VALUES (".$this->id.",".$this->propalid.")";
	      $this->db->query($sql);
	    }
	  return $this->id;
	}
      else
	{
	  print $this->db->error() . '<b><br>'.$sql;
	  return 0;
	}
      
    }

  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {

      $sql = "SELECT f.fk_soc,f.facnumber,f.amount,f.tva,f.total,f.remise,".$this->db->pdate("f.datef")."as df,f.fk_projet,".$this->db->pdate("f.date_lim_reglement")." as dlr, c.libelle, f.note, f.paye, f.fk_statut";
      $sql .= " FROM llx_facture as f, llx_cond_reglement as c";
      $sql .= " WHERE f.rowid=$rowid AND c.rowid = f.fk_cond_reglement";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id                 = $rowid;
	      $this->datep              = $obj->dp;
	      $this->date               = $obj->df;
	      $this->ref                = $obj->facnumber;
	      $this->total_ht           = $obj->amount;
	      $this->total_tva          = $obj->tva;
	      $this->total_ttc          = $obj->total;
	      $this->paye               = $obj->paye;
	      $this->remise             = $obj->remise;
	      $this->socidp             = $obj->fk_soc;
	      $this->statut             = $obj->fk_statut;
	      $this->date_lim_reglement = $obj->dlr;
	      $this->cond_reglement     = $obj->libelle;
	      $this->projetid           = $obj->fk_projet;
	      $this->note               = stripslashes($obj->note);
	      $this->lignes             = array();

	      if ($this->statut == 0)
		{
		  $this->brouillon = 1;
		}

	      $this->db->free();

	      /*
	       * Lignes
	       */

	      $sql = "SELECT l.description, l.price, l.qty, l.rowid, l.tva_taux";
	      $sql .= " FROM llx_facturedet as l WHERE l.fk_facture = ".$this->id;
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $i = 0; $total = 0;
		  
		  while ($i < $num)
		    {
		      $objp = $this->db->fetch_object($i);
		      $faclig = new FactureLigne();
		      $faclig->desc = stripslashes($objp->description);
		      $faclig->qty  = $objp->qty;
		      $faclig->price = $objp->price;
		      $faclig->tva_taux = $objp->tva_taux;
		      $this->lignes[$i] = $faclig;
		      $i++;
		    }
	    
		  $this->db->free();
		} 
	      else
		{
		  print $this->db->error();
		}
	    }
	  else
	    {
	      print "Error";
	    }
	}
      else
	{
	  print $this->db->error();
	}    
    }
  /*
   *
   *
   */
  Function fetch_client()
    {
      $client = new Societe($this->db);
      $client->fetch($this->socidp);
      $this->client = $client;
	
    }
  /*
   *
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

  /*
   * Suppression de la facture
   *
   */
  Function delete($rowid)
    {

      $sql = "DELETE FROM llx_facture WHERE rowid = $rowid AND fk_statut = 0;";

      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->affected_rows() )
	    {
	      $sql = "DELETE FROM llx_fa_pr WHERE fk_facture = $rowid;";

	      if ($this->db->query( $sql) )
		{

		  $sql = "DELETE FROM llx_facturedet WHERE fk_facture = $rowid;";

		  if ($this->db->query( $sql) )
		    {
		      return 1;
		    }
		  else
		    {
		      print "Err : ".$this->db->error();
		      return 0;
		    }
		}
	      else
		{
		  print "Err : ".$this->db->error();
		  return 0;
		}
	    }
	}
      else
	{
	  print "Err : ".$this->db->error();
	  return 0;
	}


    }
  /*
   *
   *
   *
   */
  Function set_payed($rowid)
    {
      $sql = "UPDATE llx_facture set paye = 1 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }
  /*
   *
   *
   */
  Function set_valid($rowid, $user)
    {
      if ($this->brouillon)
	{
	  $action_notify = 2; // ne pas modifier cette valeur

	  $sql = "UPDATE llx_facture set fk_statut = 1, fk_user_valid = $user->id WHERE rowid = $rowid ;";
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
  /*
   *
   *
   */
  Function addline($facid, $desc, $pu, $qty, $txtva, $fk_product='NULL')
    {
      if ($this->brouillon)
	{
	  $sql = "INSERT INTO llx_facturedet (fk_facture,description,price,qty,tva_taux, fk_product)";
	  $sql .= " VALUES ($facid, '$desc', $pu, $qty, $txtva, $fk_product) ;";

	  if ( $this->db->query( $sql) )
	    {
	      $this->updateprice($facid);
	    }
	}
    }
  /*
   *
   *
   */
  Function updateline($rowid, $desc, $pu, $qty)
    {
      if ($this->brouillon)
	{
	  $sql = "UPDATE llx_facturedet set description='$desc',price=$pu,qty=$qty WHERE rowid = $rowid ;";
	  $result = $this->db->query( $sql);

	  $this->updateprice($this->id);
	}
    }
  /*
   *
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
  /*
   *
   *
   */
  Function updateprice($facid)
    {

      $sql = "SELECT price, qty, tva_taux FROM llx_facturedet WHERE fk_facture = $facid;";
  
      $result = $this->db->query($sql);

      if ($result)
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  $totalht = 0;
	  $totaltva = 0;
	  while ($i < $num)	  
	    {
	      $obj = $this->db->fetch_object($i);

	      $totalht = $totalht + ($obj->qty * $obj->price);
	      $totaltva = $totaltva + tva($obj->qty * $obj->price, $obj->tva_taux);
	      $i++;
	    }
	  $this->db->free();

	  $total = $totalht + $totaltva;
	  
	  $sql = "UPDATE llx_facture SET amount = $totalht, tva=$totaltva, total=$total";
	  $sql .= " WHERE rowid = $facid ;";
	  
	  $result = $this->db->query($sql);
	  
	}
      else
	{
	  print "Error";
	}
    }
  /*
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
					$file, 
					"application/pdf", 
					$filename);
	      
	      if ( $mailfile->sendfile() )
		{
		  
		  $sendto = htmlentities($sendto);
		  
		  $sql = "INSERT INTO llx_actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), 10 ,$this->socidp ,'Relance envoyée à $sendto',$this->id, $sendtoid, $user->id, 'Relance Facture par mail',100);";
		  
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
  /*
   *
   * Génération du PDF
   *
   */
  Function pdf()
    {      

    }
  
}
?>
