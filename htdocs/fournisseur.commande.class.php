<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/commande/commande.class.php
		\ingroup    commande
		\brief      Fichier des classes de commandes
		\version    $Revision$
*/


/*!	\class Commande
		\brief      Classe de gestion de commande
*/

class CommandeFournisseur
{
  var $db ;
  var $id ;
  var $brouillon;

  /*!  \brief  Constructeur
   */
  function CommandeFournisseur($DB)
    {
      $this->db = $DB;

      $this->statuts[-1] = "Annulée";
      $this->statuts[0] = "Brouillon";
      $this->statuts[1] = "Validée";
      $this->statuts[2] = "Approuvée";
      $this->statuts[3] = "Commandée";
      $this->statuts[4] = "Reçu partiellement";
      $this->statuts[5] = "Clotûrée";
      $this->statuts[9] = "Refusée";

      $this->products = array();
    }


  /** 
   * Lit une commande
   *
   */
  function fetch ($id)
    {
      $sql = "SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva";
      $sql .= ", ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet, c.remise_percent, c.source, c.fk_methode_commande, cm.libelle as methode_commande";
      $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_methode_commande_fournisseur as cm ON cm.rowid = c.fk_methode_commande";

      $sql .= " WHERE c.rowid = ".$id;

      $result = $this->db->query($sql) ;
      
      if ( $result )
	{
	  $obj = $this->db->fetch_object();
	  
	  $this->id                  = $obj->rowid;
	  $this->ref                 = $obj->ref;
	  $this->soc_id              = $obj->fk_soc;
	  $this->fourn_id            = $obj->fk_soc;
	  $this->statut              = $obj->fk_statut;
	  $this->user_author_id      = $obj->fk_user_author;
	  $this->total_ht            = $obj->total_ht;
	  $this->total_tva           = $obj->tva;
	  $this->total_ttc           = $obj->total_ttc;
	  $this->date_commande       = $obj->date_commande; // date à laquelle la commande a été transmise
	  $this->remise_percent      = $obj->remise_percent;
	  $this->methode_commande_id = $obj->fk_methode_commande;
	  $this->methode_commande = $obj->methode_commande;

	  $this->source              = $obj->source;
	  $this->facturee            = $obj->facture;
	  $this->projet_id           = $obj->fk_projet;

	  $this->db->free();
	  
	  if ($this->statut == 0)
	    {
	      $this->brouillon = 1;
	    }
	  return 0;
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Fetch Error $sql");
	  dolibarr_syslog("CommandeFournisseur::Fetch Error ".$this->db->error());
	  return -1;
	}
    }
  /**
   * Log
   *
   */
  function log($user, $statut, $datelog)
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_log (datelog, fk_commande, fk_statut, fk_user)";
      $sql .= " VALUES (".$this->db->idate($datelog).",".$this->id.", $statut, ".$user->id.")";

      if ( $this->db->query($sql) )
	{
	  return 0;
	}
    }
  /**
   *
   *
   *
   */
  function valid($user)
    {
      dolibarr_syslog("CommandeFournisseur::Valid");
      $result = 0;
      if ($user->rights->fournisseur->commande->valider)
	{
	  $ref = 'CF'.substr('000000'.$this->id, -6);

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET ref='$ref', fk_statut = 1";
	  $sql .= " WHERE rowid = ".$this->id." AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $result = 0;
	      $this->log($user, 1, time());
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Valid Error -1");
	      $result = -1;
	    }	  
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Valid Not Authorized");
	}
      return $result ;
    }
  /**
   *
   *
   *
   */
  function approve($user)
    {
      dolibarr_syslog("CommandeFournisseur::Approve");
      $result = 0;
      if ($user->rights->fournisseur->commande->approuver)
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 2";
	  $sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $result = 0;
	      $this->log($user, 2, time());
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Approve Error -1");
	      $result = -1;
	    }	  
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Approve Not Authorized");
	}
      return $result ;
    }
  /**
   *
   *
   *
   */
  function refuse($user)
    {
      dolibarr_syslog("CommandeFournisseur::Refuse");
      $result = 0;
      if ($user->rights->fournisseur->commande->approuver)
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 9";
	  $sql .= " WHERE rowid = ".$this->id;
	  
	  if ($this->db->query($sql) )
	    {
	      $result = 0;
	      $this->log($user, 9, time());
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Refuse Error -1");
	      $result = -1;
	    }	  
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Refuse Not Authorized");
	}
      return $result ;
    }
  /**
   * Envoie la commande au fournisseur
   *
   *
   */
  function commande($user, $date, $methode)
    {
      dolibarr_syslog("CommandeFournisseur::Commande");
      $result = 0;
      if ($user->rights->fournisseur->commande->commander)
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 3, fk_methode_commande=".$methode.",date_commande=".$this->db->idate("$date");
	  $sql .= " WHERE rowid = ".$this->id." AND fk_statut = 2 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $result = 0;
	      $this->log($user, 3, $date);
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Commande Error -1");
	      $result = -1;
	    }	  
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Commande Not Authorized");
	}
      return $result ;
    }
  /**
   * Créé la commande
   *
   */
  function create($user)
    {
      dolibarr_syslog("CommandeFournisseur::Create");
      dolibarr_syslog("CommandeFournisseur::Create soc id=".$this->soc_id);
      /* On positionne en mode brouillon la commande */
      $this->brouillon = 1;
      
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur (fk_soc, date_creation, fk_user_author, fk_statut) ";
      $sql .= " VALUES (".$this->soc_id.", now(), ".$user->id.",0)";
      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
	  if ($this->db->query($sql))
	    {
   
	      /*
	       *
	       *
	       */
	      dolibarr_syslog("CommandeFournisseur::Create : Success");
	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Create : Failed 2");
	      return -2;
	    }
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Create : Failed 1");
	  dolibarr_syslog("CommandeFournisseur::Create : ".$this->db->error());
	  dolibarr_syslog("CommandeFournisseur::Create : ".$sql);
	  return -1;
	}
    }
  /**
   * Ajoute une ligne de commande
   *
   */
  function addline($desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
    {
      $qty = ereg_replace(",",".",$qty);
      $pu = ereg_replace(",",".",$pu);

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

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet (fk_commande,label,description,fk_product, price,qty,tva_tx, remise_percent, subprice, remise, ref)";
	  $sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "',$fk_product,".ereg_replace(",",".",$price).", '$qty', $txtva, $remise_percent,'".ereg_replace(",",".",$subprice)."','".ereg_replace(",",".", $remise)."','".$ref."') ;";

	  if ( $this->db->query( $sql) )
	    {
	      $this->update_price();
	      return 0;
	    }
	  else
	    {

	      return -1;
	    }
	}
    }

  /**
   * Ajoute un produit
   *
   */
  function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx=19.6, $p_product_id=0, $remise_percent=0)
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
	  $sql .= " ('".$this->id."', '$p_product_id','". $p_qty."','". $price."','".$p_tva_tx."','".addslashes($p_desc)."','$remise_percent', '$subprice') ; ";
	  
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
	      dolibarr_print_error($this->db);
	      return -2;
	    }
	}
    }
  /** 
   * Supprime une ligne de la commande
   *
   */
  function delete_line($idligne)
    {
      if ($this->statut == 0)
	{
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE rowid = ".$idligne;
	  
	  if ($this->db->query($sql) )
	    {
	      $this->update_price();
	      
	      return 0;
	    }
	  else
	    {
	      return -1;
	    }
	}
    }
  /**
   * Mettre à jour le prix
   *
   */
  function update_price()
    {
      include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";

      /*
       *  Liste des produits a ajouter
       */
      $sql = "SELECT price, qty, tva_tx ";
      $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet ";
      $sql .= " WHERE fk_commande = $this->id";

      if ( $this->db->query($sql) )
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object();
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
      $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur set";
      $sql .= "  amount_ht ='".ereg_replace(",",".",$totalht)."'";
      $sql .= ", total_ht  ='".ereg_replace(",",".",$totalht)."'";
      $sql .= ", tva       ='".ereg_replace(",",".",$totaltva)."'";
      $sql .= ", total_ttc ='".ereg_replace(",",".",$totalttc)."'";
      $sql .= ", remise    ='".ereg_replace(",",".",$total_remise)."'";
      $sql .= " WHERE rowid = $this->id";
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
  function delete()
  {
    $err = 0;

    $this->db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande =". $this->id ;
    if (! $this->db->query($sql) ) 
      {
	$err++;
      }

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE rowid =".$this->id;
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
   *
   *
   */
  function get_methodes_commande()
  {
    $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
    $sql .= " WHERE active = 1";

    if ($this->db->query($sql))
      {
	$i = 0;
	$num = $this->db->num_rows();
	$this->methodes_commande = array();
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    $this->methodes_commande[$row[0]] = $row[1];

	    $i++;
	  }
	return 0;
      }
    else
      {
	return -1;
      }
  }

}
?>
