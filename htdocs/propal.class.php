<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
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
    	\file       htdocs/propal.class.php
		\brief      Fichier de la classe des propales
		\author     Rodolphe Qiedeville
		\author	    Eric Seigne
		\author	    Laurent Destailleur
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/product.class.php");


/**
        \class      Propal
		\brief      Classe permettant la gestion des propales
*/

class Propal
{
    var $id;
    var $db;
    var $socidp;
    var $contactid;
    var $projetidp;
    var $author;
    var $ref;
    var $datep;
    var $remise;
    var $products;
    var $products_qty;
    var $note;
    var $price;
    var $status;
    var $fin_validite;
    
    var $labelstatut=array();
    var $labelstatut_short=array();
    
    var $product=array();

    // Pour board
    var $nbtodo;
    var $nbtodolate;


    /** 
     *		\brief      Constructeur
     *      \param      DB          Handler d'accès base
     *      \param      soc_idp     Id de la société
     *      \param      propalid    Id de la propal
     */
    function Propal($DB, $soc_idp="", $propalid=0)
    {
      global $langs;
      
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->id = $propalid;
      $this->products = array();
      $this->remise = 0;
      
      $langs->load("propals");
      $this->labelstatut[0]=$langs->trans("PropalStatusDraft");
      $this->labelstatut[1]=$langs->trans("PropalStatusValidated");
      $this->labelstatut[2]=$langs->trans("PropalStatusSigned");
      $this->labelstatut[3]=$langs->trans("PropalStatusNotSigned");
      $this->labelstatut[4]=$langs->trans("PropalStatusBilled");
      $this->labelstatut_short[0]=$langs->trans("PropalStatusDraftShort");
      $this->labelstatut_short[1]=$langs->trans("Opened");
      $this->labelstatut_short[2]=$langs->trans("PropalStatusSignedShort");
      $this->labelstatut_short[3]=$langs->trans("PropalStatusNotSignedShort");
      $this->labelstatut_short[4]=$langs->trans("PropalStatusBilledShort");
    }


  /**
   * \brief     Ajout d'un produit dans la proposition, en memoire dans l'objet
   * \param     idproduct       id du produit à ajouter
   * \param     qty             quantité
   * \param     remise_percent  remise effectuée sur le produit
   * \return    void
   * \see       insert_product
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
     *    \brief     Ajout d'un produit dans la proposition, en base
     *    \param     idproduct           Id du produit à ajouter
     *    \param     qty                 Quantité
     *    \param     remise_percent      Remise effectuée sur le produit
     *    \param     p_desc              Descriptif optionnel
     *    \return    int                 >0 si ok, <0 si ko
     *    \see       add_product
     */
    function insert_product($idproduct, $qty, $remise_percent=0, $p_desc='')
    {
        dolibarr_syslog("propal.class.php::insert_product $idproduct, $qty, $remise_percent, $p_desc");
        if ($this->statut == 0)
        {
            // Nettoyage parametres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            
            if ($idproduct)
            {
                $prod = new Product($this->db, $idproduct);
                if ($prod->fetch($idproduct) > 0)
                {
                    $txtva = $prod->tva_tx;
                    $price = price2num($prod->price);
                    $subprice = price2num($prod->price);
                    
                    // Calcul remise et nouveau prix
        			$remise = 0;
                    if ($remise_percent > 0)
                    {
                        $remise = round(($prod->price * $remise_percent / 100), 2);
                        $price = $prod->price - $remise;
                    }
        
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."propaldet (fk_propal, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
                    $sql .= " (".$this->id.",". $idproduct.",'". $qty."','". $price."','".$txtva."','".addslashes($p_desc?$p_desc:$prod->label)."','".ereg_replace(",",".",$remise_percent)."','".ereg_replace(",",".",$subprice)."')";
        
                    if ($this->db->query($sql) )
                    {
                        $this->update_price();
                        return 1;
                    }
                    else
                    {
                        $this->error=$this->db->error();
                        return -1;
                    }
                }
                else
                {
                    $this->error=$this->db->error();
                    return -2;
                }
            }
        }
        else
        {
            return -3;
        }
    }


    /**
     *    \brief     Ajout d'un produit dans la proposition, en base
     *    \param     p_desc             Descriptif optionnel
     *    \param     p_price            Prix
     *    \param     p_qty              Quantité
     *    \param     p_tva_tx           Taux tva
     *    \param     remise_percent     Remise effectuée sur le produit
     *    \return    int                >0 si ok, <0 si ko
     *    \see       add_product
     */
    function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx, $remise_percent=0)
    {
        dolibarr_syslog("propal.class.php::insert_product_generic $p_desc, $p_price, $p_qty, $p_tva_tx, $remise_percent");
        if ($this->statut == 0)
        {
			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $p_qty=price2num($p_qty);
			if (strlen(trim($p_qty))==0) $p_qty=1;
            $p_price = price2num($p_price);

            $price = $p_price;
            $subprice = $p_price;
    
            if ($remise_percent > 0)
            {
                $remise = round(($p_price * $remise_percent / 100), 2);
                $price = $p_price - $remise;
            }
    
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."propaldet (fk_propal, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
            $sql .= " (".$this->id.", 0,'". $p_qty."','". ereg_replace(",",".",$price)."','".$p_tva_tx."','".addslashes($p_desc)."','$remise_percent', '".ereg_replace(",",".",$subprice)."') ; ";
    
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
                return -2;
            }
        }
    }
    
    
  /**
   *    \brief      Mise à jour d'une ligne de produit
   *    \param      id              Id de la ligne
   *    \param      subprice        Prix unitaire
   *    \param      qty             Quantité
   *    \param      remise_percent  Remise effectuée sur le produit
   *    \param      tva_tx          Taux de TVA
   *    \param      desc            Description
   *    \return     int             0 en cas de succès
   */
    	
    function UpdateLigne($id, $subprice, $qty, $remise_percent=0, $tva_tx, $desc='')
    {
        if ($this->statut == 0)
        {
            // Nettoyage paramètres
            $subprice=price2num($subprice);
            $price = $subprice;
            $remise_percent=price2num($remise_percent);
            $tva_tx=price2num($tva_tx);
            
            if ($remise_percent > 0)
            {
                $remise = round(($subprice * $remise_percent / 100), 2);
                $price = $subprice - $remise;
            }
    
            $sql = "UPDATE ".MAIN_DB_PREFIX."propaldet ";
            $sql.= " SET qty='".$qty."'";
            $sql.= " , price='". $price."'";
            $sql.= " , remise_percent='".$remise_percent."'";
            $sql.= " , subprice='".$subprice."'";
            $sql.= " , tva_tx='".$tva_tx."'";
            $sql.= " , description='".addslashes($desc)."'";
            $sql.= " WHERE rowid = '".$id."';";
    
            if ($this->db->query($sql))
            {
                $this->update_price();
                return 0;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Propal::UpdateLigne Erreur -1");
                return -1;
            }
        }
        else
        {
            dolibarr_syslog("Propal::UpdateLigne Erreur -2 Propal en mode incompatible pour cette action");
            return -2;
        }
    }
    
		
  /**
   *
   *
   */
	 
  function fetch_client()
    {
      $client = new Societe($this->db);
      $client->fetch($this->socidp);
      $this->client = $client;
    }
		
    /**
     *      \brief      Supprime une ligne de detail
     *      \param      idligne     Id de la ligne detail à supprimer
     *      \return     int         >0 si ok, <0 si ko
     */
    function delete_product($idligne)
    {
        if ($this->statut == 0)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE rowid = ".$idligne;
    
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
        else
        {
            return -2;
        }
    }

		
    /**
     *      \brief      Crée une propal
     *      \return     int     <0 si ko, >=0 si ok
     */
    function create()
    {
		global $langs,$conf;

        // Définition paramètres
        $this->fin_validite = $this->datep + ($this->duree_validite * 24 * 3600);
    
        $this->db->begin();

        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal (fk_soc, fk_soc_contact, price, remise, tva, total, datep, datec, ref, fk_user_author, note, model_pdf, fin_validite) ";
        $sql.= " VALUES ($this->socidp, $this->contactid, 0, $this->remise, 0,0,".$this->db->idate($this->datep).", now(), '$this->ref', $this->author, '".addslashes($this->note)."','$this->modelpdf',".$this->db->idate($this->fin_validite).")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."propal");
    
            if ($this->id)
            {
                /*
                 *  Insertion du detail des produits dans la base
                 */
                for ($i = 0 ; $i < sizeof($this->products) ; $i++)
                {
                    $prod = new Product($this->db, $this->products[$i]);
                    $result=$prod->fetch($this->products[$i]);

                    $result=$this->insert_product($this->products[$i],
                                        $this->products_qty[$i],
                                        $this->products_remise_percent[$i]);
                }

                // Affectation au projet
                if ($this->projetidp)
                {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_projet=$this->projetidp WHERE ref='$this->ref'";
                    $result=$this->db->query($sql);
                }

                $resql=$this->update_price();
                if ($resql)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('PROPAL_CREATE',$this,$user,$langs,$conf);
                    // Fin appel triggers
        
                    $this->db->commit();
                    return $this->id;
                }
                else
                {
                    $this->error=$this->db->error();
                    dolibarr_syslog("Propal::Create -2 ".$this->error);
                    $this->db->rollback();
                    return -2;
                }
            }
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Propal::Create -1 ".$this->error);
            $this->db->rollback();
            return -1;
        }
        
        return $this->id;
    }
		
    /**
     *    \brief      Mets à jour le prix total de la proposition
     *    \return     int     <0 si ko, >0 si ok
     */
    function update_price()
    {
        include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";
    
        /*
         *  Liste des produits a ajouter
         */
        $sql = "SELECT price, qty, tva_tx FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = $this->id";
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
    
        $this->remise         = $calculs[3];
        $this->total_ht       = $calculs[0];
        $this->total_tva      = $calculs[1];
        $this->total_ttc      = $calculs[2];

        // Met a jour en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET";
        $sql .= " price='".  ereg_replace(",",".",$this->total_ht)."'";
        $sql .= ", tva='".   ereg_replace(",",".",$this->total_tva)."'";
        $sql .= ", total='". ereg_replace(",",".",$this->total_ttc)."'";
        $sql .= ", remise='".ereg_replace(",",".",$this->remise)."'";
        $sql .=" WHERE rowid = $this->id";
    
        if ( $this->db->query($sql) )
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
     *    \brief      Recupère de la base les caractéristiques d'une propale
     *    \param      rowid       id de la propal à récupérer
     */
    function fetch($rowid)
    {
        $sql = "SELECT ref,total,price,remise,tva,fk_soc,fk_soc_contact";
        $sql .= " ,".$this->db->pdate("datep")."as dp";
        $sql .= " ,".$this->db->pdate("fin_validite")."as dfv, model_pdf, note";
        $sql .= " , fk_projet, fk_statut, remise_percent, fk_user_author";
        $sql .= ", c.label as statut_label";
        $sql .= " FROM ".MAIN_DB_PREFIX."propal";
        $sql .= "," . MAIN_DB_PREFIX."c_propalst as c";
        $sql .= " WHERE fk_statut = c.id";
        $sql .= " AND rowid='".$rowid."';";
    
        $resql=$this->db->query($sql);
    
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id             = $rowid;
    
                $this->datep          = $obj->dp;
                $this->fin_validite   = $obj->dfv;
                $this->date           = $obj->dp;
                $this->ref            = $obj->ref;
                $this->price          = $obj->price;
                $this->remise         = $obj->remise;
                $this->remise_percent = $obj->remise_percent;
                $this->total          = $obj->total;
                $this->total_ht       = $obj->price;
                $this->total_tva      = $obj->tva;
                $this->total_ttc      = $obj->total;
                $this->socidp         = $obj->fk_soc;
                $this->soc_id         = $obj->fk_soc;
                $this->projetidp      = $obj->fk_projet;
                $this->contactid      = $obj->fk_soc_contact;
                $this->modelpdf       = $obj->model_pdf;
                $this->note           = $obj->note;
                $this->statut         = $obj->fk_statut;
                $this->statut_libelle = $obj->statut_label;
    
                $this->user_author_id = $obj->fk_user_author;
    
                if ($obj->fk_statut == 0)
                {
                    $this->brouillon = 1;
                }
    
                $this->lignes = array();
                $this->db->free($resql);
    
                $this->ref_url = '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$this->id.'">'.$this->ref.'</a>';
    
                /*
                * Lignes propales liées à un produit
                */
                $sql = "SELECT d.description, p.rowid, p.label, p.description as product_desc, p.ref, d.price, d.tva_tx, d.qty, d.remise_percent, d.subprice";
                $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as d, ".MAIN_DB_PREFIX."product as p";
                $sql .= " WHERE d.fk_propal = ".$this->id ." AND d.fk_product = p.rowid";
                $sql .= " ORDER by d.rowid ASC";
    
                $result = $this->db->query($sql);
                if ($result)
                {
                    $num = $this->db->num_rows($result);
                    $i = 0;
    
                    while ($i < $num)
                    {
                        $objp                  = $this->db->fetch_object($result);
    
                        $ligne                 = new PropaleLigne();
                        $ligne->desc           = $objp->description;  // Description ligne
                        $ligne->libelle        = $objp->label;        // Label produit
                        $ligne->product_desc   = $objp->product_desc; // Description produit
                        $ligne->qty            = $objp->qty;
                        $ligne->ref            = $objp->ref;
                        $ligne->tva_tx         = $objp->tva_tx;
                        $ligne->subprice       = $objp->subprice;
                        $ligne->remise_percent = $objp->remise_percent;
                        $ligne->price          = $objp->price;
                        $ligne->product_id     = $objp->rowid;
    
                        $this->lignes[$i]      = $ligne;
                        //dolibarr_syslog("1 ".$ligne->desc);
                        //dolibarr_syslog("2 ".$ligne->product_desc);
                        $i++;
                    }
                    $this->db->free($result);
                }
                else
                {
                    dolibarr_syslog("Propal::Fetch Erreur lecture des produits");
                    return -1;
                }
    
                /*
                 * Lignes propales liées à aucun produit
                 */
                $sql = "SELECT d.qty, d.description, d.price, d.subprice, d.tva_tx, d.rowid, d.remise_percent";
                $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as d";
                $sql .= " WHERE d.fk_propal = ".$this->id ." AND d.fk_product = 0";
    
                $result = $this->db->query($sql);
                if ($result)
                {
                    $num = $this->db->num_rows($result);
                    $j = 0;
    
                    while ($j < $num)
                    {
                        $objp                  = $this->db->fetch_object($result);
                        $ligne                 = new PropaleLigne();
                        $ligne->libelle        = $objp->description;
                        $ligne->desc           = $objp->description;
                        $ligne->qty            = $objp->qty;
                        $ligne->ref            = $objp->ref;
                        $ligne->tva_tx         = $objp->tva_tx;
                        $ligne->subprice       = $objp->subprice;
                        $ligne->remise_percent = $objp->remise_percent;
                        $ligne->price          = $objp->price;
                        $ligne->product_id     = 0;
    
                        $this->lignes[$i]      = $ligne;
                        $i++;
                        $j++;
                    }
    
                    $this->db->free($result);
                }
                else
                {
                    dolibarr_syslog("Propal::Fetch Erreur lecture des lignes de propale");
    
                    return -1;
                }
            }
            return 1;
        }
        else
        {
            dolibarr_syslog("Propal::Fetch Erreur lecture de la propale $rowid");
            return -1;
        }
    }
  
    /**
     *      \brief      Passe au statut valider une propale
     *      \param      user        Objet utilisateur qui valide
     *      \return     int         <0 si ko, >=0 si ok
     */
    function valid($user)
    {
        global $conf,$langs;
        
        if ($user->rights->propale->valider)
        {
            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."propal";
            $sql.= " SET fk_statut = 1, date_valid=now(), fk_user_valid=".$user->id;
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
    
            if ($this->db->query($sql))
            {

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('PROPAL_VALIDATE',$this,$user,$langs,$conf);
                // Fin appel triggers

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
     *      \brief      Définit une remise globale sur la proposition
     *      \param      user        Objet utilisateur qui modifie
     *      \param      remise      Montant remise    
     *      \return     int         <0 si ko, >0 si ok
     */
    function set_echeance($user, $date_fin_validite)
    {
        if ($user->rights->propale->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fin_validite = ".$this->db->idate($date_fin_validite);
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
    
            if ($this->db->query($sql) )
            {
                $this->fin_validite = $date_fin_validite;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Propal::set_echeance Erreur SQL");
                return -1;
            }
        }
    }


    /**
     *      \brief      Définit une remise globale sur la proposition
     *      \param      user        Objet utilisateur qui modifie
     *      \param      remise      Montant remise    
     *      \return     int         <0 si ko, >0 si ok
     */
    function set_remise($user, $remise)
    {
        if ($user->rights->propale->creer)
        {
            $remise = price2num($remise);
    
            $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET remise_percent = ".$remise;
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
    
            if ($this->db->query($sql) )
            {
                $this->remise_percent = $remise;
                $this->update_price();
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Propal::set_remise Erreur SQL");
                return -1;
            }
        }
    }

  
  /*
   *
   *
   *
   */
  function set_project($user, $project_id)
  {
    if ($user->rights->propale->creer)
      {
	//verif que le projet et la société concordent
	$sql = 'SELECT p.rowid, p.title FROM '.MAIN_DB_PREFIX.'projet as p WHERE p.fk_soc ='.$this->socidp.' AND p.rowid='.$project_id;
	$sqlres = $this->db->query($sql);
	if ($sqlres)
	  {
	    $numprojet = $this->db->num_rows($sqlres);
	    if ($numprojet > 0)
	      {
		$this->projetidp=$project_id;
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal SET fk_projet = '.$project_id;
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';
		$this->db->query($sql);
	      }
	  }
	else
	  {
	    
	    dolibarr_syslog("Propal::set_project Erreur SQL");
	  }
      }
  }

  /*
   *
   *
   *
   */
  
  function set_contact($user, $contact_id)
  {
    if ($user->rights->propale->creer)
      {
	//verif que le contact et la société concordent
	$sql = 'SELECT p.idp FROM '.MAIN_DB_PREFIX.'socpeople as p WHERE p.fk_soc = '.$this->socidp.' AND p.idp='.$contact_id;
	$sqlres = $this->db->query($sql);
	if ($sqlres)
	  {
	    $numprojet = $this->db->num_rows($sqlres);
	    if ($numprojet > 0)
	      {
		$this->projetidp=$project_id;
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal SET fk_soc_contact = '.$contact_id;
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';
		$this->db->query($sql);
	      }
	  }
	else
	  {
	    dolibarr_syslog("Propal::set_contact Erreur SQL");
	  }
      }
  }
  
  /*
   *
   *
   *
   */
	 
  function set_pdf_model($user, $modelpdf)
    {
      if ($user->rights->propale->creer)
	{

	  $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET model_pdf = '$modelpdf'";
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      return 1;
	    }
	  else
	    {
    	  dolibarr_print_error($this->db);
	      return 0;
	    }
	}
  }
	
    /**
     *      \brief      Cloture de la proposition commerciale
     *      \param      user        Utilisateur qui cloture
     *      \param      statut      Statut
     *      \param      note        Commentaire
     *      \return     int         <0 si ko, >0 si ok
     */
    function cloture($user, $statut, $note)
    {
        $this->statut = $statut;
    
        $this->db->begin();
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal";
        $sql.= " SET fk_statut = $statut, note = '".addslashes($note)."', date_cloture=now(), fk_user_cloture=".$user->id;
        $sql.= " WHERE rowid = ".$this->id;
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($statut == 2)
            {
                // Propale signée
                include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");

                $result=$this->create_commande($user);

                if ($result >= 0)
                {
                    // Classe la société rattachée comme client
                    $soc=new Societe($this->db);
                    $soc->id = $this->socidp;
                    $result=$soc->set_as_client();
                }
                
                if ($result < 0)
                {
                    $this->error=$this->db->error();
                    $this->db->rollback();
                    return -2;
                }
                
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('PROPAL_CLOSE_SIGNED',$this,$user,$langs,$conf);
                // Fin appel triggers
            }
            else
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('PROPAL_CLOSE_REFUSED',$this,$user,$langs,$conf);
                // Fin appel triggers
            }
            
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
     *      \brief      Crée une commande à partir de la proposition commerciale
     *      \param      user        Utilisateur
     *      \return     int         <0 si ko, >=0 si ok
     */
    function create_commande($user)
    {
        global $conf;
        
        if ($conf->commande->enabled)
        {
            if ($this->statut == 2)
            {
                // Propale signée
                include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
                $commande = new Commande($this->db);
                $result=$commande->create_from_propale($user, $this->id);
    
                return $result;
            }
            else return 0;
        }
        else return 0;
    }


    /**
    *
    *
    */
    function reopen($userid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_statut = 0";
    
        $sql .= " WHERE rowid = $this->id;";
    
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
   *    \brief      Renvoi la liste des propal (éventuellement filtrée sur un user) dans un tableau
   *    \param      brouillon       0=non brouillon, 1=brouillon
   *    \param      user            Objet user de filtre
   *    \return     int             -1 si erreur, tableau résultat si ok
   */
	 
    function liste_array ($brouillon=0, $user='')
    {
        $ga = array();
    
        $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."propal";
    
        if ($brouillon)
        {
            $sql .= " WHERE fk_statut = 0";
            if ($user)
            {
                $sql .= " AND fk_user_author".$user;
            }
        }
        else
        {
            if ($user)
            {
                $sql .= " WHERE fk_user_author".$user;
            }
        }
    
        $sql .= " ORDER BY datep DESC";

        $result=$this->db->query($sql);
        if ($result)
        {
            $nump = $this->db->num_rows($result);
    
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($result);
    
                    $ga[$obj->rowid] = $obj->ref;
                    $i++;
                }
            }
            return $ga;
        }
        else
        {
            return -1;
        }
    }
		
    /**
     *    \brief        Renvoie un tableau contenant les numéros de commandes associées
     *    \remarks      Fonction plus light que associated_orders
     *    \sa           associated_orders
     */
    function commande_liste_array ()
    {
        $ga = array();
        
        $sql = "SELECT fk_commande FROM ".MAIN_DB_PREFIX."co_pr";
        $sql .= " WHERE fk_propale = " . $this->id;
        if ($this->db->query($sql) )
        {
            $nump = $this->db->num_rows();
            
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object();
                    
                    $ga[$i] = $obj->fk_commande;
                    $i++;
                }
            }
            return $ga;
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }
		
    /**
     *    \brief      Renvoie un tableau contenant les commandes associées
     *    \remarks    Fonction plus lourde que commande_liste_array
     *    \sa         commande_liste_array
     */
    function associated_orders()
    {
        $ga = array();
    
        $sql = "SELECT fk_commande FROM ".MAIN_DB_PREFIX."co_pr";
        $sql.= " WHERE fk_propale = " . $this->id;
        if ($this->db->query($sql) )
        {
            $nump = $this->db->num_rows();
    
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object();
                    $order=new Commande($this->db);

                    if ($obj->fk_commande)
                    {
                        $order->fetch($obj->fk_commande);
                        $ga[$i] = $order;
                    }
                    $i++;
                }
            }
            return $ga;
        }
        else
        {
            print $this->db->error();
        }
    }
		
    /**
     *    \brief      Renvoie un tableau contenant les numéros de factures associées
     */
    function facture_liste_array ()
    {
        $ga = array();
        
        $sql = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."fa_pr as fp";
        $sql .= " WHERE fk_propal = " . $this->id;
        if ($this->db->query($sql) )
        {
            $nump = $this->db->num_rows();
            
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object();
                    
                    $ga[$i] = $obj->fk_facture;
                    $i++;
                }
            }
            return $ga;
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }

  /**
   *    \brief      Efface propal
   *    \param      user        Objet du user qui efface
   */
  function delete($user)
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = $this->id ;";
    if ( $this->db->query($sql) ) 
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal WHERE rowid = $this->id;";
	if ( $this->db->query($sql) ) 
	  {
	    dolibarr_syslog("Suppression de la proposition $this->id par $user->fullname ($user->id)");
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
     *      \brief      Mets à jour la note
     *      \param      note        Note à mettre à jour
     *      \return     int         <0 si ko, >0 si ok
     */
    function update_note($note)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal";
        $sql.= " SET note = '".addslashes($note)."'";
        $sql.= " WHERE rowid = ".$this->id;
    
        if ($this->db->query($sql))
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
   *      \brief      Information sur l'objet propal
   *      \param      id      id de la propale
   */
  function info($id)
  {
    $sql = "SELECT c.rowid, ";
    $sql.= $this->db->pdate("datec")." as datec, ".$this->db->pdate("date_valid")." as datev, ".$this->db->pdate("date_cloture")." as dateo";
    $sql.= ", fk_user_author, fk_user_valid, fk_user_cloture";
    $sql.= " FROM ".MAIN_DB_PREFIX."propal as c";
    $sql.= " WHERE c.rowid = $id";
    
    if ($this->db->query($sql))
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object();
	    
	    $this->id                = $obj->rowid;
	    
	    $this->date_creation     = $obj->datec;
	    $this->date_validation   = $obj->datev;
	    $this->date_cloture      = $obj->dateo;
	    
	    $cuser = new User($this->db, $obj->fk_user_author);
	    $cuser->fetch();
	    $this->user_creation     = $cuser;
	    
	    if ($obj->fk_user_valid)
	      {
		$vuser = new User($this->db, $obj->fk_user_valid);
		$vuser->fetch();
		$this->user_validation     = $vuser;
	      }
	    
	    if ($obj->fk_user_cloture)
	      {
		$cluser = new User($this->db, $obj->fk_user_cloture);
		$cluser->fetch();
		$this->user_cloture     = $cluser;
	      }
	    
	    
	  }
	$this->db->free();
	
      }
    else
      {
	dolibarr_print_error($this->db);
      }
  }
  
  
  /**
   *    \brief      Retourne le libellé du statut d'une propale (brouillon, validée, ...)
   *    \return     string      Libellé
   */
  function getLibStatut()
  {
    return $this->LibStatut($this->statut);
  }
  
  /**
   *    \brief      Renvoi le libellé d'un statut donné
   *    \param      statut      id statut
   *    \param      size        Libellé court si 0, long si non défini
   *    \return     string      Libellé
   */
    function LibStatut($statut,$size=1)
    {
        if ($size) return $this->labelstatut[$statut];
        else return $this->labelstatut_short[$statut];
    }


    /**
     *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param      user        Objet user
     *      \param      mode        "opened" pour propal à fermer, "signed" pour propale à facturer
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_board($user,$mode)
    {
        global $conf;
        
        $this->nbtodo=$this->nbtodolate=0;
        $sql ="SELECT p.rowid,".$this->db->pdate("p.datec")." as datec,".$this->db->pdate("p.fin_validite")." as datefin";
        $sql.=" FROM ".MAIN_DB_PREFIX."propal as p";
        if ($mode == 'opened') $sql.=" WHERE p.fk_statut = 1";
        if ($mode == 'signed') $sql.=" WHERE p.fk_statut = 2";
        if ($user->societe_id) $sql.=" AND fk_soc = ".$user->societe_id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->datefin < (time() - $conf->propal->cloture->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else 
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

}


/**
        \class      PropalLigne
		\brief      Classe permettant la gestion des lignes de propales
*/

class PropaleLigne  
{
    function PropaleLigne()
    {
    }
}

?>
