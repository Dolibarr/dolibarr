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
        \file       htdocs/commande/commande.class.php
        \ingroup    commande
        \brief      Fichier des classes de commandes
        \version    $Revision$
*/


/**	
        \class      Commande
        \brief      Classe de gestion de commande
*/

class Commande 
{
    var $db ;
    var $id ;
    var $brouillon;
    
    // Pour board
    var $nbtodo;
    var $nbtodolate;
  
    /**
     *        \brief      Constructeur
     *        \param      DB      Handler d'accès base
     */
    function Commande($DB)
    {
        global $langs;
        $langs->load("orders");
        
        $this->db = $DB;
        
        $this->statuts[-1] = $langs->trans("StatusOrderCanceled");
        $this->statuts[0]  = $langs->trans("StatusOrderDraft");  
        $this->statuts[1]  = $langs->trans("StatusOrderValidated");
        $this->statuts[2]  = $langs->trans("StatusOrderOnProcess");
        $this->statuts[3]  = $langs->trans("StatusOrderProcessed");
        
        $this->status_label_short[-1] = $langs->trans("StatusOrderCanceled");
        $this->status_label_short[0]  = $langs->trans("StatusOrderDraft");  
        $this->status_label_short[1]  = $langs->trans("StatusOrderValidated");
        $this->status_label_short[2]  = $langs->trans("StatusOrderOnProcessShort");
        $this->status_label_short[3]  = $langs->trans("StatusOrderProcessed");
        
        $this->sources[0] = $langs->trans("OrderSource0");
        $this->sources[1] = $langs->trans("OrderSource1");
        $this->sources[2] = $langs->trans("OrderSource2");
        $this->sources[3] = $langs->trans("OrderSource3");
        $this->sources[4] = $langs->trans("OrderSource4");
        $this->sources[5] = $langs->trans("OrderSource5");
        
        $this->products = array();
    }

  /**   \brief      Créé la facture depuis une propale existante
        \param      user            Utilisateur qui crée
        \param      propale_id      id de la propale qui sert de modèle
   */
  function create_from_propale($user, $propale_id)
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

      /* Définit la société comme un client */
      $soc = new Societe($this->db);
      $soc->id = $this->soc_id;
      $soc->set_as_client();

      $this->propale_id = $propal->id;
      return $this->create($user);
    }

  /**   \brief      Valide la commande
        \param      user            Utilisateur qui valide
   */
  function valid($user)
    {
      $result = 0;
      if ($user->rights->commande->valider)
	{
	  if (defined("COMMANDE_ADDON"))
	    {
	      if (is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/commande/".COMMANDE_ADDON.".php"))
		{
		  require_once DOL_DOCUMENT_ROOT ."/includes/modules/commande/".COMMANDE_ADDON.".php";
		  
		  // Definition du nom de module de numerotation de commande

		  // \todo  Normer le nom des classes des modules de numérotation de ref de commande avec un nom du type NumRefCommandesXxxx
		  //
		  //$list=split("_",COMMANDE_ADDON);
		  //$numrefname=$list[2];
		  //$modName = "NumRefCommandes".ucfirst($numrefname);
		  $modName=COMMANDE_ADDON;
		  
		  // Recuperation de la nouvelle reference
		  $objMod = new $modName($this->db);
		  $soc = new Societe($this->db);
		  $soc->fetch($this->soc_id);
		  $num = $objMod->commande_get_num($soc);

		  $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
		  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
		  
		  if ($this->db->query($sql) )
		    {
		      $result = 1;
		    }
		  else
		    {
		      $result = -1;
		      dolibarr_print_error($this->db);
		    }
		  
		}
	      else
		{
		  print "Impossible de lire le module de numérotation";
		}
	    }
	  else
	    {
	      print "Le module de numérotation n'est pas définit" ;
	    }
	}
      return $result ;
    }

  /**
   *    \brief      Cloture la commande
   *    \param      user        Objet utilisateur qui cloture
   *    \return     int         <0 si ko, >0 si ok
   */
    function cloture($user)
    {
        if ($user->rights->commande->valider)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET fk_statut = 3,";
            $sql.= " fk_user_cloture = ".$user->id.",";
            $sql.= " date_cloture = now()";
            $sql.= " WHERE rowid = $this->id AND fk_statut > 0 ;";
    
            if ($this->db->query($sql) )
            {
                return 1;
            }
            else
            {
                dolibarr_print_error($this->db);
                return -1;
            }
        }
    }

  /**
   * Annule la commande
   *
   */
  function cancel($user)
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
		      dolibarr_print_error($this->db);
	    }
	}
  }

  /**
   * Créé la commande
   *
   */
  function create($user)
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
	  $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."commande");

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
		      dolibarr_print_error($this->db);
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
      dolibarr_print_error($this->db);
	  return 0;
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
   * Ajoute une ligne de commande
   *
   */
    function addline($desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
    {
        // Nettoyage parametres
        $qty = ereg_replace(",",".",$qty);
        $pu = ereg_replace(",",".",$pu);
        $desc=trim($desc);
        if (strlen(trim($qty))==0)
        {
            $qty=1;
        }
        
        // Verifs
        if (! $this->brouillon) return -1;
        
        $this->db->begin();

        if ($fk_product > 0)
        {
            $prod = new Product($this->db, $fk_product);
            if ($prod->fetch($fk_product) > 0)
            {
                $desc  = $desc?$desc:$prod->libelle;
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

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet (fk_commande,label,description,fk_product, price,qty,tva_tx, remise_percent, subprice, remise)";
        $sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "',$fk_product,".ereg_replace(",",".",$price).", '$qty', $txtva, $remise_percent,'".ereg_replace(",",".",$subprice)."','".ereg_replace(",",".", $remise)."') ;";

        if ( $this->db->query( $sql) )
        {
            $this->update_price();
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }
    
  /**
   * Ajoute un produit dans la commande
   *
   */
  function add_product($idproduct, $qty, $remise_percent=0)
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
  function fetch ($id)
    {
      $sql = "SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva";
      $sql .= ", ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet, c.remise_percent, c.source, c.facture, c.note";
      $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
      $sql .= " WHERE c.rowid = ".$id;

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
	  $this->note            = $obj->note;
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
		  $obj = $this->db->fetch_object();
		  $this->propale_id = $obj->fk_propale;
		}

	      return 1;	      
	    }
	  else
	    {
	      dolibarr_print_error($this->db);
	      return -1;
	    }
	}
      else
	{
      dolibarr_print_error($this->db);
	  return -1;
	}
    }
  /**
   *
   *
   */
  function fetch_lignes($only_product=0)
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

	    $objp = $this->db->fetch_object($result);

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
  function livraison_array()
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
  function nb_expedition()
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
   *    \brief      Supprime une ligne de la commande
   *    \param      idligne     Id de la ligne à supprimer
   *    \return     int         >0 si ok, <0 si ko
   */
    function delete_line($idligne)
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
                return -1;
            }
        }
    }

  /**
   *
   *
   */
  function set_remise($user, $remise)
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
	    dolibarr_syslog("Commande::set_remise Erreur SQL");
	    }
	}
    }

	/**
	 *
	 *
	 */
	function set_note($user, $note)
	{
		if ($user->rights->commande->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET note = '".addslashes($note)."'";
			$sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
			if ($this->db->query($sql))
			{
				$this->note = $note;
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				return 0;
			}
		}
		else
		{
			return 0;
		}
	}
  /**
   *        \brief      Classe la facture comme facturée
   *        \return     int     <0 si ko, >0 si ok
   */
    function classer_facturee()
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET facture = 1";
        $sql .= " WHERE rowid = ".$this->id." AND fk_statut > 0 ;";
    
        if ($this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
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
      $sql = "SELECT price, qty, tva_tx FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = $this->id";
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
      $sql = "UPDATE ".MAIN_DB_PREFIX."commande set";
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
   * Mets à jour une ligne de commande
   *
   */
  function update_line($rowid, $desc, $pu, $qty, $remise_percent=0)
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
	      dolibarr_print_error($this->db);
	    }
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
   *        \brief      Classer la commande dans un projet
   *        \param      cat_id      Id du projet
   */
  function classin($cat_id)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_projet = $cat_id";
        $sql .= " WHERE rowid = $this->id;";
    
        if ($this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    
    /**
     *      \brief          Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param          user    Objet user
     *      \return         int     <0 si ko, >0 si ok
     */
    function load_board($user)
    {
        global $conf;
        
        $this->nbtodo=$this->nbtodolate=0;
        $sql = "SELECT c.rowid,".$this->db->pdate("c.date_creation")." as datec";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
        $sql.= " WHERE c.fk_statut BETWEEN 1 AND 2";
        if ($user->societe_id) $sql.=" AND fk_soc = ".$user->societe_id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->datec < (time() - $conf->commande->traitement->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else 
        {
             $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *      \brief     Charge les informations d'ordre info dans l'objet commande
     *      \param     id       Id de la commande a charger
     */
    function info($id)
    {
        $sql = "SELECT c.rowid, ".$this->db->pdate("date_creation")." as datec,";
        $sql.= " ".$this->db->pdate("date_valid")." as datev,";
        $sql.= " ".$this->db->pdate("date_cloture")." as datecloture,";
        $sql.= " fk_user_author, fk_user_valid, fk_user_cloture";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
        $sql.= " WHERE c.rowid = ".$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if ($obj->fk_user_author) {
                    $cuser = new User($this->db, $obj->fk_user_author);
                    $cuser->fetch();
                    $this->user_creation   = $cuser;
                }

                if ($obj->fk_user_valid) {
                    $vuser = new User($this->db, $obj->fk_user_valid);
                    $vuser->fetch();
                    $this->user_validation = $vuser;
                }

                if ($obj->fk_user_cloture) {
                    $cluser = new User($this->db, $obj->fk_user_cloture);
                    $cluser->fetch();
                    $this->user_cloture   = $cluser;
                }

                $this->date_creation     = $obj->datec;
                $this->date_validation   = $obj->datev;
                $this->date_cloture      = $obj->datecloture;
            }

            $this->db->free($result);

        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }

}



/**
    	\class      CommandeLigne
		\brief      Classe de gestion des lignes de commande
*/

class CommandeLigne
{
    function CommandeLigne()
    {
    }
}

?>
