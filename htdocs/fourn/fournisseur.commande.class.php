<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**	   
        \file       htdocs/fourn/fournisseur.commande.class.php
        \ingroup    fournisseur,commande
        \brief      Fichier des classes des commandes fournisseurs
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**	  
        \class      CommandeFournisseur
        \brief      Classe de gestion de commande fournisseur
*/

class CommandeFournisseur
{
  var $db ;
  var $id ;
  var $brouillon;

  /**   \brief      Constructeur
   *    \param      DB      Handler d'accès aux bases de données
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
      $this->statuts[6] = "Annulée";
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
      $sql .= ", ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet, c.remise_percent, c.source, c.fk_methode_commande ";
      $sql .= ", c.note";

      $sql .= ", cm.libelle as methode_commande";

      $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_methode_commande_fournisseur as cm ON cm.rowid = c.fk_methode_commande";

      $sql .= " WHERE c.rowid = ".$id;
      $resql = $this->db->query($sql) ;
      
      if ( $resql )
	{
	  $obj = $this->db->fetch_object($resql);
	  
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
	  $this->note                = stripslashes($obj->note);

	  $this->db->free($resql);
	  
	  if ($this->statut == 0)
	    {
	      $this->brouillon = 1;
	    }

	  $result = 0;

	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Fetch Error $sql");
	  dolibarr_syslog("CommandeFournisseur::Fetch Error ".$this->db->error());
	  $result = -1;
	}

      return $result ;

    }

    /**
     *      \brief      Insère ligne de log
     *      \param      user        Utilisateur qui modifie la commande
     *      \param      statut      Statut de la commande
     *      \param      datelog     Date de modification
     *      \return     int         <0 si ko, >0 si ok
     */
    function log($user, $statut, $datelog)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_log (datelog, fk_commande, fk_statut, fk_user)";
        $sql.= " VALUES (".$this->db->idate($datelog).",".$this->id.", $statut, ".$user->id.")";
        
        if ( $this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            return -1;
        }
    }

  /**
   * Valide la commande
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

	      $this->ref = $ref;

	      $this->_NotifyApprobator($user);

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
   * Annule la commande
   * L'annulation se fait après la validation
   *
   */
  function Cancel($user)
    {
      //dolibarr_syslog("CommandeFournisseur::Cancel");
      $result = 0;
      if ($user->rights->fournisseur->commande->annuler)
	{

	  $statut = 6;

	  $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".$statut;
	  $sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $result = 0;
	      $this->log($user, $statut, time());
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Cancel Error -1");
	      $result = -1;
	    }	  
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Cancel Not Authorized");
	}
      return $result ;
    }
  /*
   *
   *
   */
  function _NotifyApprobator($user)
  {
    require_once (DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
    
    $this->ReadApprobators();
    
    if (sizeof($this->approbs) > 0)
      {

	$this->_details_text();

	$from = $user->email;
	$subject = "Nouvelle commande en attente d'approbation réf : ".$this->ref;

	$message = "Bonjour,\n\n";
	$message .= "La commande ".$this->ref." validée par $user->fullname, est en attente de votre approbation.\n\n";


	$message .= $this->details_text;

	if (sizeof($this->approbs) > 1)
	  {
	    $message .= "\nCette demande d'approbation a été envoyée à :\n";
	    
	    foreach($this->approbs as $approb)
	      {
		if (strlen($approb[2]))
		  {
		    $message .= "- $approb[0] $approb[1] <$approb[2]>\n";
		  }
	      }
	  }

	$message .= "\nCordialement,\n\n";
	$message .="--\n(message automatique envoyé par Dolibarr)";	    
	
	foreach($this->approbs as $approb)
	  {

	    $sendto = $approb[2];

	    $mailfile = new CMailFile($subject,
					 $sendto,
					 $from,
					 $message, array(), array(), array());
	    if ( $mailfile->sendfile() )
	      {
		
	      }
	  }
      }
  }
  /**
   * Approuve une commande
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

	      $subject = "Votre commande ".$this->ref." a été approuvée";
	      $message = "Bonjour,\n\n";
	      $message .= "Votre commande ".$this->ref." a été approuvée, par $user->fullname";
	      $message .= "\n\nCordialement,\n\n";

	      $this->_NotifyCreator($user, $subject, $message);

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
   * Refuse une commande
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

	      $subject = "Votre commande ".$this->ref." a été refusée";
	      $message = "Votre commande ".$this->ref." a été refusée, par $user->fullname";

	      $this->_NotifyCreator($user, $subject, $message);
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
  /*
   *
   *
   */
  function _NotifyCreator($user, $subject, $message)
  {
    require_once (DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
    
    $cc = new user($this->db, $this->user_author_id);
    $cc->fetch();
    
    $sendto = $cc->email;
    $from = $user->email;

    $mailfile = new CMailFile($subject,
				 $sendto,
				 $from,
				 $message, array(), array(), array());
    if ( $mailfile->sendfile() )
      {
	return 0;
      }
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
     *      \brief      Créé la commande au statut brouillon
     *      \param      user        Utilisateur qui crée
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
        dolibarr_syslog("CommandeFournisseur::Create soc id=".$this->soc_id);

        $this->db->begin();
        
        /* On positionne en mode brouillon la commande */
        $this->brouillon = 1;
    
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur (fk_soc, date_creation, fk_user_author, fk_statut) ";
        $sql .= " VALUES (".$this->soc_id.", now(), ".$user->id.",0)";
    
        if ( $this->db->query($sql) )
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."commande_fournisseur");
    
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " SET ref='(PROV".$this->id.")'";
            $sql.= " WHERE rowid=".$this->id;
            if ($this->db->query($sql))
            {
                // On logue creation pour historique   
                $this->log($user, 0, time());
                
                dolibarr_syslog("CommandeFournisseur::Create : Success");
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->error=$this->db->error()." - ".$sql;
                dolibarr_syslog("CommandeFournisseur::Create: Failed -2 - ".$this->error);
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error()." - ".$sql;
            dolibarr_syslog("CommandeFournisseur::Create: Failed -1 - ".$this->error);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief      Ajoute une ligne de commande
     *      \param      desc            Description
     *      \param      pu              Prix unitaire
     *      \param      qty             Quantité
     *      \param      txtva           Taux tva
     *      \param      fk_product      Id produit
     *      \param      remise_percent  Remise
     *      \param      int             <0 si ko, >0 si ok
     */
    function addline($desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
    {
        $qty = ereg_replace(",",".",$qty);
        $pu = ereg_replace(",",".",$pu);
        $desc=trim($desc);

        dolibarr_syslog("Fournisseur_Commande.class.php::addline $desc, $pu, $qty, $txtva, $fk_product, $remise_percent");
    
        if ($this->brouillon)
        {
            $this->db->begin();
            
            if (strlen(trim($qty))==0)
            {
                $qty=1;
            }
    
            if ($fk_product > 0)
            {
                $prod = new Product($this->db, $fk_product);
                if ($prod->fetch($fk_product) > 0)
                {
                    $prod->get_buyprice($this->fourn_id,$qty);

                    $desc  = $prod->libelle;
                    $txtva = $prod->tva_tx;
                    $pu    = $prod->buyprice/$qty;
                    $ref   = $prod->ref;
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
    
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet (fk_commande,label,description,fk_product, price, qty, tva_tx, remise_percent, subprice, remise, ref)";
            $sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "',$fk_product,".ereg_replace(",",".",$price).", '$qty', $txtva, $remise_percent,'".ereg_replace(",",".",$subprice)."','".ereg_replace(",",".", $remise)."','".$ref."') ;";
    
            if ( $this->db->query( $sql) )
            {
                $this->update_price();

                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
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
   *
   */
  function generate_pdf()
  {
    // Récupération des info sur le fournisseur
    $this->fournisseur = new Societe($this->db);
    $this->fournisseur->fetch($this->fourn_id);

    $name = 'muscadet';
    require_once DOL_DOCUMENT_ROOT.'/fourn/commande/modules/pdf/pdf_fourncomm_'.$name.'.modules.php';

    $var = 'pdf_fourncomm_'.$name;

    $opdf = new $var();
    $opdf->write_pdf_file($this);
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

  /**
   * Livraison
   *
   *
   */
  function Livraison($user, $date, $type)
    {
      dolibarr_syslog("CommandeFournisseur::Livraison");
      $result = 0;
      if ($user->rights->fournisseur->commande->receptionner && $date < time())
	{
	  if ($type == 'par')
	    {
	      $statut = 4;
	    }

	  if ($type == 'tot')
	    {
	      $statut = 5;
	    }

	  if ($statut == 4 or $statut == 5)
	    {
	      $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
	      $sql .= " SET fk_statut = ".$statut;
	      $sql .= " WHERE rowid = ".$this->id;
	      $sql .= " AND (fk_statut = 3 OR fk_statut = 4) ;";
	      
	      if ($this->db->query($sql) )
		{
		  $result = 0;
		  $this->log($user, $statut, $date);
		}
	      else
		{
		  dolibarr_syslog("CommandeFournisseur::Livraison Error -1");
		  $result = -1;
		}	  
	    }
	  else
	    {
	      dolibarr_syslog("CommandeFournisseur::Livraison Error -2");
	      $result = -2;
	    }	
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::Livraison Not Authorized");
	}
      return $result ;
    }

  /**
   *
   *
   *
   */
  function UpdateNote($user, $note)
    {
      dolibarr_syslog("CommandeFournisseur::UpdateNote");
      $result = 0;

      $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";

      $sql .= " SET note  ='".trim($note) ."'";

      $sql .= " WHERE rowid = ".$this->id;
      
      if ($this->db->query($sql) )
	{
	  $result = 0;
	}
      else
	{
	  dolibarr_syslog("CommandeFournisseur::UpdateNote Error -1");
	  $result = -1;
	}	  

      return $result ;
    }

  /*
   *
   *
   *
   */
  function ReadApprobators()
  {
    $this->approbs = array();

    $sql = "SELECT u.name, u.firstname, u.email";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql .= " , ".MAIN_DB_PREFIX."user_rights as ur";
    $sql .= " WHERE u.rowid = ur.fk_user";
    $sql .= " AND ur.fk_id = 184";
    
    $resql = $this->db->query($sql);

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	    
	    $this->approbs[$i] = $row;
	    $i++;
	  }

	$this->db->free($resql);
      }
    else 
      {
	dolibarr_syslog("ReadApprobators Erreur");
      }    
  }
  /*
   *
   *
   */
  function _details_text()
  {
    $blank = "                                                                                                                            ";
    $this->details_text = substr("Produit".$blank,0,50);
    $this->details_text .= substr("Qty".$blank,0,8);
    $this->details_text .= substr("Prix".$blank,0,8);
    $this->details_text .= substr("-----------------------------------------------------------------------------------------------------------------------",0,66);
    $this->details_text .= "\n";

    $sql = "SELECT l.ref, l.fk_product, l.description, l.price, l.qty";
    $sql .= ", l.rowid, l.tva_tx, l.remise_percent, l.subprice";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l ";
    $sql .= " WHERE l.fk_commande = ".$this->id." ORDER BY l.rowid";
    
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$num_lignes = $this->db->num_rows($resql);
	$i = 0;
	
	while ($i < $num_lignes)
	  {
	    $objp = $this->db->fetch_object();

	    $this->details_text .=  "-".substr(stripslashes($objp->description).$blank, 0, 50);
	    $this->details_text .= substr($objp->qty.$blank, 0, 7);
	    $this->details_text .= substr($blank.price($objp->subprice),-8);
	    $this->details_text .= "\n";
	    $i++;
	    
	  }
	$this->details_text .= substr("-----------------------------------------------------------------------------------------------------------------------",0,66);
	$this->details_text .= "\n";
	$this->details_text .= substr($blank."Total HT : ".price($this->total_ht), -66);
	$this->details_text .= "\n";
	$this->details_text .= substr($blank."Total TTC : ".price($this->total_ttc), -66);
	$this->details_text .= "\n";
	
	$this->db->free();
      } 
    else
      {
	print $this->db->error();
      }
  }
}
?>
