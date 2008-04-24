<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2007 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
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
 */

/**
    	\file       htdocs/propal.class.php
		\brief      Fichier de la classe des propales
		\author     Rodolphe Qiedeville
		\author	    Eric Seigne
		\author	    Laurent Destailleur
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT ."/product.class.php");
require_once(DOL_DOCUMENT_ROOT ."/contact.class.php");


/**
        \class      Propal
		\brief      Classe permettant la gestion des propales
*/

class Propal extends CommonObject
{
	var $db;
	var $error;
	var $element='propal';
	var $table_element='propal';
	var $table_element_line='propaldet';
	var $fk_element='fk_propal';

	var $id;

	var $socid;		// Id client
	var $client;		// Objet societe client (a charger par fetch_client)

	var $contactid;
	var $projetidp;
	var $author;
	var $ref;
	var $ref_client;
	var $statut;					// 0, 1, 2, 3, 4
	var $datep;
	var $fin_validite;
	var $price;						// Total HT
	var $tva;						// Total TVA
	var $total;						// Total TTC
	var $cond_reglement_id;
	var $cond_reglement_code;
	var $mode_reglement_id;
	var $mode_reglement_code;
	var $remise;
	var $remise_percent;
	var $remise_absolue;
	var $note;
	var $note_public;
	var $date_livraison;
	var $adresse_livraison_id;
	var $adresse;

	var $products=array();

	var $labelstatut=array();
	var $labelstatut_short=array();

	// Pour board
	var $nbtodo;
	var $nbtodolate;

	var $specimen;


    /**
     *		\brief      Constructeur
     *      \param      DB          Handler d'acc�s base
     *      \param      socid		Id de la soci�t�
     *      \param      propalid    Id de la propal
     */
    function Propal($DB, $socid="", $propalid=0)
    {
      global $langs;

      $this->db = $DB ;
      $this->socid = $socid;
      $this->id = $propalid;
      $this->products = array();
      $this->remise = 0;
      $this->remise_percent = 0;
      $this->remise_absolue = 0;

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
	 * 	\brief     	Ajoute une ligne dans tableau products
	 * 	\param     	idproduct       	Id du produit � ajouter
	 * 	\param     	qty             	Quantit�
	 * 	\param     	remise_percent  	Remise relative effectu�e sur le produit
	 *	\remarks	$this->client doit etre charg�
	 *	\TODO	Remplacer les appels a cette fonction par generation objet Ligne 
	 *			ins�r� dans tableau $this->products
	 */
    function add_product($idproduct, $qty, $remise_percent=0)
	{
		global $conf, $mysoc;
	
		if (! $qty) $qty = 1;
	
		dolibarr_syslog("Propal::add_product $idproduct, $qty, $remise_percent");
		if ($idproduct > 0)
		{
			$prod=new Product($this->db);
			$prod->fetch($idproduct);

			$productdesc = $prod->description;

			$tva_tx = get_default_tva($mysoc,$this->client,$prod->tva_tx);
			// multiprix
			if($conf->global->PRODUIT_MULTIPRICES == 1)
			{
				$price = $prod->multiprices[$this->client->price_level];
			}
			else
			{
				$price = $prod->price;
			}
	
			$line = new PropaleLigne($this->db);
	
			$line->fk_product=$idproduct;
			$line->desc=$productdesc;
			$line->qty=$qty;
			$line->subprice=$price;
			$line->remise_percent=$remise_percent;
			$line->tva_tx=$tva_tx;
	
			$this->products[]=$line;
		}
    }

    /**
     *    \brief     Ajout d'une ligne remise fixe dans la proposition, en base
     *    \param     idremise			Id de la remise fixe
     *    \return    int          		>0 si ok, <0 si ko
     */
    function insert_discount($idremise)
    {
		global $langs;

		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
		include_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

		$this->db->begin();

		$remise=new DiscountAbsolute($this->db);
		$result=$remise->fetch($idremise);

		if ($result > 0)
		{
			if ($remise->fk_facture)	// Protection against multiple submission
			{
				$this->error=$langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}

			$propalligne=new PropaleLigne($this->db);
			$propalligne->fk_propal=$this->id;
			$propalligne->fk_remise_except=$remise->id;
			$propalligne->desc=$remise->description;   	// Description ligne
			$propalligne->tva_tx=$remise->tva_tx;
			$propalligne->subprice=-$remise->amount_ht;
			$propalligne->price=-$remise->amount_ht;
			$propalligne->fk_product=0;					// Id produit pr�d�fini
			$propalligne->qty=1;
			$propalligne->remise=0;
			$propalligne->remise_percent=0;
			$propalligne->rang=-1;
			$propalligne->info_bits=2;

			$propalligne->total_ht  = -$remise->amount_ht;
			$propalligne->total_tva = -$remise->amount_tva;
			$propalligne->total_ttc = -$remise->amount_ttc;

			$result=$propalligne->insert();
			if ($result > 0)
			{
				$result=$this->update_price();
				if ($result > 0)
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
			else
			{
				$this->error=$propalligne->error;
				$this->db->rollback();	
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			return -2;	
		}
	}

    /**
     *    	\brief     	Ajout d'un produit dans la proposition, en base
	 * 		\param    	propalid        	Id de la propale
	 * 		\param    	desc            	Description de la ligne
	 * 		\param    	pu_ht              	Prix unitaire
	 * 		\param    	qty             	Quantit�
	 * 		\param    	txtva           	Taux de tva forc�, sinon -1
	 *		\param    	fk_product      	Id du produit/service pred�fini
	 * 		\param    	remise_percent  	Pourcentage de remise de la ligne
	 * 		\param    	price_base_type		HT or TTC
     * 		\param    	pu_ttc             	Prix unitaire TTC
     * 		\param    	info_bits			Bits de type de lignes
     *    	\return    	int             	>0 si ok, <0 si ko
     *    	\see       	add_product
	 * 		\remarks	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
	 *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete d�fini
	 *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
 	 *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    function addline($propalid, $desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $info_bits=0)
    {
    	global $conf;
    	
    	dolibarr_syslog("Propal::Addline propalid=$propalid, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_except=$remise_percent, price_base_type=$price_base_type, pu_ttc=$pu_ttc, info_bits=$info_bits");
    	include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
    	
    	if ($this->statut == 0)
      	{
	      	$this->db->begin();
		
	      	// Clean parameters
	      	$remise_percent=price2num($remise_percent);
	      	$qty=price2num($qty);
	      	if ($conf->global->PROPALE_USE_OPTION_LINE && !$qty)
	      	{
	      		$qty=0;
	      	}
	      	else if (! $qty)
	      	{
	      		$qty=1;
	      	}
	      	$pu_ht=price2num($pu_ht);
	      	$pu_ttc=price2num($pu_ttc);
	      	$txtva=price2num($txtva);
	      	
	      	if ($price_base_type=='HT')
	      	{
	      		$pu=$pu_ht;
	      	}
	      	else
	      	{
	      		$pu=$pu_ttc;
	      	}
	      	
	      	// Calcul du total TTC et de la TVA pour la ligne a partir de
			  // qty, pu, remise_percent et txtva
			  // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
			  // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			  $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
			  $total_ht  = $tabprice[0];
			  $total_tva = $tabprice[1];
			  $total_ttc = $tabprice[2];
			  
			  // \TODO A virer
			  // Anciens indicateurs: $price, $remise (a ne plus utiliser)
			  $price = $pu;
			  $remise = 0;
			  if ($remise_percent > 0)
			  {
			  	$remise = round(($pu * $remise_percent / 100), 2);
			  	$price = $pu - $remise;
			  }

			// Insert line
			$ligne=new PropaleLigne($this->db);

			$ligne->fk_propal=$propalid;
			$ligne->desc=$desc;
			$ligne->qty=$qty;
			$ligne->tva_tx=$txtva;
			$ligne->fk_product=$fk_product;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$pu_ht;
			$ligne->rang=-1;
			$ligne->info_bits=$info_bits;
			$ligne->fk_remise_except=$fk_remise_except;
			$ligne->total_ht=$total_ht;
			$ligne->total_tva=$total_tva;
			$ligne->total_ttc=$total_ttc;
			
			// Mise en option de la ligne
			if ($conf->global->PROPALE_USE_OPTION_LINE && !$qty) $ligne->special_code=3;

			// \TODO Ne plus utiliser
			$ligne->price=$price;
			$ligne->remise=$remise;

			$result=$ligne->insert();			
			if ($result > 0)
			{
				// Mise a jour informations denormalisees au niveau de la propale meme
				$result=$this->update_price();
        		if ($result > 0)
        		{
					$this->db->commit();
					return 1;
				}
				else
        		{
        			$this->error=$this->db->error();
	        		dolibarr_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error=$ligne->error;
				$this->db->rollback();
        		return -2;
      		}
    	}
	}


  /**
   *    \brief      Mise a jour d'une ligne de produit
   *    \param      rowid              	Id de la ligne
   *    \param      pu		        	Prix unitaire (HT ou TTC selon price_base_type)
   *    \param      qty             	Quantity
   *    \param      remise_percent  	Remise effectuee sur le produit
   *    \param      txtva	          	Taux de TVA
   *    \param      desc            	Description
   *	\param		price_base_type		HT ou TTC
   *	\param     	info_bits        	Miscellanous informations
   *    \return     int             	0 en cas de succes
   */
    function updateline($rowid, $pu, $qty, $remise_percent=0, $txtva, $desc='', $price_base_type='HT', $info_bits=0)
    {
    	global $conf;
    	
    	dolibarr_syslog("Propal::UpdateLine $rowid, $pu, $qty, $remise_percent, $txtva, $desc, $price_base_type, $info_bits");
    	include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
    	
    	if ($this->statut == 0)
      {
      	$this->db->begin();
      	
      	// Nettoyage param�tres
        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        if ($conf->global->PROPALE_USE_OPTION_LINE && !$qty)
      	{
      		$qty=0;
      		$remise_percent=0;
      	}
      	else if (! $qty)
      	{
      		$qty=1;
      	}
        $pu = price2num($pu);
        $txtva = price2num($txtva);
        
        // Calcul du total TTC et de la TVA pour la ligne a partir de
        // qty, pu, remise_percent et txtva
        // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
        // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
        $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
        $total_ht  = $tabprice[0];
        $total_tva = $tabprice[1];
        $total_ttc = $tabprice[2];
        
        // Anciens indicateurs: $price, $remise (a ne plus utiliser)
        $price = $pu;
        if ($remise_percent > 0)
        {
        	$remise = round(($pu * $remise_percent / 100), 2);
          $price = $pu - $remise;
        }
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."propaldet ";
        $sql.= " SET qty='".$qty."'";
        $sql.= " , price='". price2num($price)."'";			// \TODO A virer
        $sql.= " , remise_percent='".$remise_percent."'";	// \TODO A virer
        $sql.= " , subprice=".price2num($pu);
        $sql.= " , tva_tx=".price2num($txtva);
        $sql.= " , description='".addslashes($desc)."'";
        $sql.= " , total_ht=".price2num($total_ht);
        $sql.= " , total_tva=".price2num($total_tva);
        $sql.= " , total_ttc=".price2num($total_ttc);
        $sql.= " , info_bits=".$info_bits;
        if ($conf->global->PROPALE_USE_OPTION_LINE && !$qty)
        {
        	$sql.= " , special_code=3";
        }
        else
        {
        	$sql.= " , special_code=0";
        }
        $sql.= " WHERE rowid = '".$rowid."';";

        $result=$this->db->query($sql);
        if ($result > 0)
        {
        	$this->update_price();
        	$this->db->commit();
        	return $result;
        }
        else
        {
        	$this->error=$this->db->error();
        	$this->db->rollback();
        	dolibarr_syslog("Propal::UpdateLine Erreur sql=$sql, error=".$this->error);
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
     *      \brief      Supprime une ligne de detail
     *      \param      idligne     Id de la ligne detail � supprimer
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
     *      \brief      Cr�e une propal
     *      \return     int     <0 si ko, >=0 si ok
     */
    function create($user='')
    {
    	global $langs,$conf,$mysoc;
    	
    	// on v�rifie si la ref n'est pas utilis�e
		  $soc = new Societe($this->db);
	    $soc->fetch($this->socid);
	    $this->verifyNumRef($soc);

      // Nettoyage/d�finition param�tres
      $this->fin_validite = $this->datep + ($this->duree_validite * 24 * 3600);

		  dolibarr_syslog("Propal::create ref=".$this->ref);

      $this->db->begin();

		  $this->fetch_client();

        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal (fk_soc, price, remise, remise_percent, remise_absolue,";
        $sql.= " tva, total, datep, datec, ref, fk_user_author, note, note_public, model_pdf, fin_validite,";
        $sql.= " fk_cond_reglement, fk_mode_reglement, ref_client";
        if ($conf->global->PROPALE_ADD_SHIPPING_DATE) $sql.= ", date_livraison";
        $sql.= ") ";
        $sql.= " VALUES (".$this->socid.", 0, ".$this->remise.", ".$this->remise_percent.", ".$this->remise_absolue.",";
        $sql.= " 0,0,".$this->db->idate($this->datep).", now(), '".$this->ref."', ".$this->author.",";
        $sql.= "'".addslashes($this->note)."',";
        $sql.= "'".addslashes($this->note_public)."',";
        $sql.= "'".$this->modelpdf."',".$this->db->idate($this->fin_validite).",";
        $sql.= " ".$this->cond_reglement_id.", ".$this->mode_reglement_id.",";
        $sql.= "'".addslashes($this->ref_client)."'";
        if ($conf->global->PROPALE_ADD_SHIPPING_DATE) $sql.= ", ".($this->date_livraison?$this->db->idate($this->date_livraison):'null');
		$sql.= ")";

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
					$resql = $this->addline(
						$this->id,
						$this->products[$i]->desc,
						$this->products[$i]->subprice,
						$this->products[$i]->qty,
						$this->products[$i]->tva_tx,
						$this->products[$i]->fk_product,
						$this->products[$i]->remise_percent,
						'HT'
						);
						
					if ($resql < 0)
					{
						$this->error=$this->db->error;
						dolibarr_print_error($this->db);
						break;
					}
                }

                // Affectation au projet
                if ($resql && $this->projetidp)
                {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_projet=".$this->projetidp." WHERE ref='".$this->ref."'";
                    $result=$this->db->query($sql);
                }

                // Affectation de l'adresse de livraison
                if ($resql && $this->adresse_livraison_id)
                {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_adresse_livraison=$this->adresse_livraison_id WHERE ref='$this->ref'";
                    $result=$this->db->query($sql);
                }

	            if ($resql)
	            {
   					// Mise a jour infos d�normalis�s
	                $resql=$this->update_price();
	                if ($resql)
	                {
	                    // Appel des triggers
	                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	                    $interface=new Interfaces($this->db);
	                    $result=$interface->run_triggers('PROPAL_CREATE',$this,$user,$langs,$conf);
						if ($result < 0) { $error++; $this->errors=$interface->errors; }
	                    // Fin appel triggers
	
	                    $this->db->commit();
			            dolibarr_syslog("Propal::ass::Create done id=".$this->id);
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
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Propal::Create -1 ".$this->error);
            $this->db->rollback();
            return -1;
        }

		$this->db->commit();
        dolibarr_syslog("Propal::Create done id=".$this->id);
        return $this->id;
    }


    /**
     *    	\brief      Recupere de la base les caracteristiques d'une propale
     *    	\param      rowid       id de la propal a recuperer
     *		\return		int			<0 si ko, 0 si non trouve, >0 si ok
     */
    function fetch($rowid)
    {
        $sql = "SELECT p.rowid,ref,remise,remise_percent,remise_absolue,fk_soc";
		$sql.= ", total, tva, total_ht";
        $sql.= ", ".$this->db->pdate("datep")." as dp";
        $sql.= ", ".$this->db->pdate("fin_validite")." as dfv";
        $sql.= ", ".$this->db->pdate("date_livraison")." as date_livraison";
        $sql.= ", model_pdf, ref_client";
        $sql.= ", note, note_public";
        $sql.= ", fk_projet, fk_statut, fk_user_author";
        $sql.= ", fk_adresse_livraison";
        $sql.= ", p.fk_cond_reglement, cr.code as cond_reglement_code";
        $sql.= ", p.fk_mode_reglement, cp.code as mode_reglement_code";
        $sql.= ", c.label as statut_label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_propalst as c, ".MAIN_DB_PREFIX."propal as p";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_mode_reglement = cp.id';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'cond_reglement as cr ON p.fk_cond_reglement = cr.rowid';
        $sql.= " WHERE p.fk_statut = c.id";
        $sql.= " AND p.rowid='".$rowid."'";

		dolibarr_syslog("Propal::fecth rowid=".$rowid);
		
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id                   = $rowid;

                $this->datep                = $obj->dp;
                $this->fin_validite         = $obj->dfv;
                $this->date                 = $obj->dp;
                $this->ref                  = $obj->ref;
                $this->ref_client           = $obj->ref_client;
                $this->remise               = $obj->remise;
                $this->remise_percent       = $obj->remise_percent;
                $this->remise_absolue       = $obj->remise_absolue;
                $this->total                = $obj->total;
                $this->total_ht             = $obj->total_ht;
                $this->total_tva            = $obj->tva;
                $this->total_ttc            = $obj->total;
                $this->socid                = $obj->fk_soc;
                $this->projetidp            = $obj->fk_projet;
                $this->modelpdf             = $obj->model_pdf;
                $this->note                 = $obj->note;
                $this->note_public          = $obj->note_public;
                $this->statut               = $obj->fk_statut;
                $this->statut_libelle       = $obj->statut_label;
				        $this->mode_reglement_id    = $obj->fk_mode_reglement;
				        $this->mode_reglement_code  = $obj->mode_reglement_code;
				        $this->cond_reglement_id    = $obj->fk_cond_reglement;
				        $this->cond_reglement_code  = $obj->cond_reglement_code;
		            $this->date_livraison       = $obj->date_livraison;
		            $this->adresse_livraison_id = $obj->fk_adresse_livraison;

                $this->user_author_id = $obj->fk_user_author;

                if ($this->cond_reglement_id)
                {
                   $sql = "SELECT rowid, libelle, code, libelle_facture";
                   $sql.= " FROM ".MAIN_DB_PREFIX."cond_reglement";
                   $sql.= " WHERE rowid = ".$this->cond_reglement_id;

                   $resqlcond = $this->db->query($sql);

                   if ($resqlcond)
                   {
                   	$objc = $this->db->fetch_object($resqlcond);
                   	$this->cond_reglement           = $objc->libelle;
                   	$this->cond_reglement_document  = $objc->libelle_facture;
                   	$this->cond_reglement_code      = $objc->code;
                  }
                }

                if ($obj->fk_statut == 0)
                {
                    $this->brouillon = 1;
                }

                $this->lignes = array();
                $this->db->free($resql);

                /*
                 * Lignes propales li�es � un produit ou non
                 */
                $sql = "SELECT d.description, d.price, d.tva_tx, d.qty, d.fk_remise_except, d.remise_percent, d.subprice, d.fk_product,";
                $sql.= " d.info_bits, d.total_ht, d.total_tva, d.total_ttc, d.marge_tx, d.marque_tx, d.special_code, d.rang,";
                $sql.= " p.ref, p.label, p.description as product_desc";
                $sql.= " FROM ".MAIN_DB_PREFIX."propaldet as d";
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON d.fk_product = p.rowid";
                $sql.= " WHERE d.fk_propal = ".$this->id;
                $sql.= " ORDER by d.rang";

                $result = $this->db->query($sql);
                if ($result)
                {
                    $num = $this->db->num_rows($result);
                    $i = 0;

                    while ($i < $num)
                    {
                        $objp                    = $this->db->fetch_object($result);

                        $ligne                   = new PropaleLigne($this->db);

                        $ligne->desc             = $objp->description;  // Description ligne
                        $ligne->qty              = $objp->qty;
                        $ligne->tva_tx           = $objp->tva_tx;
                        $ligne->subprice         = $objp->subprice;
                        $ligne->fk_remise_except = $objp->fk_remise_except;
						            $ligne->remise_percent   = $objp->remise_percent;
                        $ligne->price            = $objp->price;

                        $ligne->info_bits        = $objp->info_bits;
                        $ligne->total_ht         = $objp->total_ht;
                        $ligne->total_tva        = $objp->total_tva;
                        $ligne->total_ttc        = $objp->total_ttc;
                        $ligne->marge_tx         = $objp->marge_tx;
                        $ligne->marque_tx        = $objp->marque_tx;
                        $ligne->special_code     = $objp->special_code;
                        $ligne->rang             = $objp->rang;

                        $ligne->fk_product       = $objp->fk_product;

                        $ligne->libelle          = $objp->label;        // Label produit
                        $ligne->product_desc     = $objp->product_desc; // Description produit
                        $ligne->ref              = $objp->ref;

                        $this->lignes[$i]        = $ligne;
                        //dolibarr_syslog("1 ".$ligne->fk_product);
                        //print "xx $i ".$this->lignes[$i]->fk_product;
                        $i++;
                    }
                    $this->db->free($result);
                }
                else
                {
                	$this->error=$this->db->error();
                    dolibarr_syslog("Propal::Fetch Error $this->error, sql=$sql");
                    return -1;
                }

	            return 1;
            }
            
            $this->error="Record Not Found";
            return 0;
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Propal::Fetch Error sql=$sql ".$this->error);
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
	            $this->use_webcal=($conf->global->PHPWEBCALENDAR_PROPALSTATUS=='always'?1:0);

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('PROPAL_VALIDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
     *      \brief      D�finit la date de fin de validit�
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      date_fin_validite	Date fin
     *      \return     int         		<0 si ko, >0 si ok
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
     *      \brief      D�finit une date de livraison
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      date_livraison      date de livraison
     *      \return     int         		<0 si ko, >0 si ok
     */
    function set_date_livraison($user, $date_livraison)
    {
        if ($user->rights->propale->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."propal ";
            $sql.= " SET date_livraison = ".$this->db->idate($date_livraison);
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            if ($this->db->query($sql))
            {
                $this->date_livraison = $date_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Propal::set_date_livraison Erreur SQL");
                return -1;
            }
        }
    }

    /**
     *      \brief      D�finit une adresse de livraison
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      adresse_livraison      Adresse de livraison
     *      \return     int         		<0 si ko, >0 si ok
     */
    function set_adresse_livraison($user, $adresse_livraison)
    {
        if ($user->rights->propale->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_adresse_livraison = '".$adresse_livraison."'";
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            if ($this->db->query($sql) )
            {
                $this->adresse_livraison_id = $adresse_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Propal::set_adresse_livraison Erreur SQL");
                return -1;
            }
        }
    }
    
    /**
     *      \brief      Positionne numero reference client
     *      \param      user            Utilisateur qui modifie
     *      \param      ref_client      Reference client
     *      \return     int             <0 si ko, >0 si ok
     */
	function set_ref_client($user, $ref_client)
	{
		if ($user->rights->propale->creer)
		{
    		dolibarr_syslog('Propale::set_ref_client this->id='.$this->id.', ref_client='.$ref_client);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal SET ref_client = '.(empty($ref_client) ? 'NULL' : '\''.addslashes($ref_client).'\'');
			$sql.= ' WHERE rowid = '.$this->id;
			if ($this->db->query($sql) )
			{
				$this->ref_client = $ref_client;
				return 1;
			}
			else
			{
        $this->error=$this->db->error();
				dolibarr_syslog('Propale::set_ref_client Erreur '.$this->error.' - '.$sql);
			  return -2;
			}
		}
		else
		{
		    return -1;
		}
	}

    /**
     *      \brief      D�finit une remise globale relative sur la proposition
     *      \param      user        Objet utilisateur qui modifie
     *      \param      remise      Montant remise
     *      \return     int         <0 si ko, >0 si ok
     */
    function set_remise_percent($user, $remise)
    {
		$remise=trim($remise)?trim($remise):0;

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
                dolibarr_syslog("Propal::set_remise_percent Error sql=$sql");
                return -1;
            }
        }
    }


    /**
     *      \brief      D�finit une remise globale absolue sur la proposition
     *      \param      user        Objet utilisateur qui modifie
     *      \param      remise      Montant remise
     *      \return     int         <0 si ko, >0 si ok
     */
    function set_remise_absolue($user, $remise)
    {
		$remise=trim($remise)?trim($remise):0;

        if ($user->rights->propale->creer)
        {
            $remise = price2num($remise);

            $sql = "UPDATE ".MAIN_DB_PREFIX."propal ";
            $sql.= " SET remise_absolue = ".$remise;
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            if ($this->db->query($sql) )
            {
                $this->remise_absolue = $remise;
                $this->update_price();
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Propal::set_remise_absolue Error sql=$sql");
                return -1;
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
        global $langs,$conf;
		
		$this->statut = $statut;

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."propal";
        $sql.= " SET fk_statut = ".$statut.", note = '".addslashes($note)."', date_cloture=now(), fk_user_cloture=".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($statut == 2)
            {
                // Propale sign�e
                include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");

                $result=$this->create_commande($user);

                if ($result >= 0)
                {
                    // Classe la soci�t� rattach�e comme client
                    $soc=new Societe($this->db);
                    $soc->id = $this->socid;
                    $result=$soc->set_as_client();
                }

                if ($result < 0)
                {
                    $this->error=$this->db->error();
                    $this->db->rollback();
                    return -2;
                }

	            $this->use_webcal=($conf->global->PHPWEBCALENDAR_PROPALSTATUS=='always'?1:0);

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('PROPAL_CLOSE_SIGNED',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
            }
            else
            {
	            $this->use_webcal=($conf->global->PHPWEBCALENDAR_PROPALSTATUS=='always'?1:0);

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('PROPAL_CLOSE_REFUSED',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
	 *        \brief      Classe la propale comme factur�e
	 *        \return     int     <0 si ko, >0 si ok
	 */
	function classer_facturee()
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal SET fk_statut = 4';
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0 ;';
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
     *      \brief      Cr�e une commande � partir de la proposition commerciale
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
                // Propale sign�e
                include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
                $commande = new Commande($this->db);
                $result=$commande->create_from_propale($user, $this->id);
                
                // Ne pas passer par la commande provisoire
                if ($conf->global->COMMANDE_VALID_AFTER_CLOSE_PROPAL == 1)
                {
                	$commande->fetch($result);
                	$commande->valid($user);
                }

                return $result;
            }
            else return 0;
        }
        else return 0;
    }


	/**
	*		\brief		Set draft status
	*		\param		user		Object user that modify
	*		\param		int			<0 if KO, >0 if OK
	*/
    function set_draft($user)
    {
		global $conf,$langs;

        $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
            return -1;
        }
    }


  /**
   *    \brief      Renvoi la liste des propal (�ventuellement filtr�e sur un user) dans un tableau
   *    \param      brouillon       0=non brouillon, 1=brouillon
   *    \param      user            Objet user de filtre
   *    \return     int             -1 si erreur, tableau r�sultat si ok
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
     *    \brief        Renvoie un tableau contenant les num�ros de commandes associ�es
     *    \remarks      Fonction plus light que associated_orders
     *    \sa           loadOrders
     */
    function getOrderArrayList()
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
     *		\brief      Charge tableau contenant les commandes associ�es
     *  	\remarks    Fonction plus lourde que getOrderArrayList
	 *		\return		int 				<0 si ko, >0 si ok
     *		\sa         getOrdersArrayList
     */
    function loadOrders()
    {
		$this->commandes = array();

        $ga = array();
        $sql = "SELECT fk_commande FROM ".MAIN_DB_PREFIX."co_pr";
        $sql.= " WHERE fk_propale = " . $this->id;
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
                    $order=new Commande($this->db);

                    if ($obj->fk_commande)
                    {
                        $order->fetch($obj->fk_commande);
                        $ga[$i] = $order;
                    }
                    $i++;
                }
            }
            $this->commandes=$ga;
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *    	\brief      Renvoie un tableau contenant les num�ros de factures associ�es
     *		\return		array		Tableau des id de factures
     */
    function getInvoiceArrayList ()
    {
		return $this->InvoiceArrayList($this->id);
	}

    /**
     *    	\brief      Renvoie un tableau contenant les id et ref des factures associ�es
     *		\param		id			Id propal
     *		\return		array		Tableau des id de factures
     */
    function InvoiceArrayList($id)
    {
        $ga = array();

		$sql = "SELECT f.rowid, f.facnumber";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."fa_pr as fp";
		$sql.= " WHERE fp.fk_facture = f.rowid AND fp.fk_propal = ".$id;
		$sql.= " UNION ";
		// Cas des factures lier via la commande
		$sql.= "SELECT f.rowid, f.facnumber";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."co_pr as cp, ".MAIN_DB_PREFIX."co_fa as cf";
		$sql.= " WHERE cp.fk_propale = ".$id." AND cf.fk_commande = cp.fk_commande AND cf.fk_facture = f.rowid";

        //$sql = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."fa_pr as fp";
        //$sql .= " WHERE fk_propal = " . $id;
        dolibarr_syslog("Propal::InvoiceArrayList sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);
                    $ga[$obj->rowid] = $obj->facnumber;
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
	*    \brief      Efface propal
	*    \param      user        Objet du user qui efface
	*/
	function delete($user)
	{
		global $conf;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = ".$this->id;
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".$this->id;
			if ( $this->db->query($sql) )
			{
	
				// On efface le r�pertoire du pdf
				$propalref = sanitize_string($this->ref);
				if ($conf->propal->dir_output)
				{
					$dir = $conf->propal->dir_output . "/" . $propalref ;
					$file = $conf->propal->dir_output . "/" . $propalref . "/" . $propalref . ".pdf";
					if (file_exists($file))
					{
						propale_delete_preview($this->db, $this->id, $this->ref);
	
						if (!dol_delete_file($file))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
							return 0;
						}
					}
					if (file_exists($dir))
					{
						if (!dol_delete_dir($dir))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
							return 0;
						}
					}
				}
	
				dolibarr_syslog("Suppression de la proposition $this->id par $user->id");
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
	 *   \brief      Change les conditions de r�glement de la facture
	 *   \param      cond_reglement_id      Id de la nouvelle condition de r�glement
	 *   \return     int                    >0 si ok, <0 si ko
	 */
	function cond_reglement($cond_reglement_id)
	{
		dolibarr_syslog('Propale::cond_reglement('.$cond_reglement_id.')');
		if ($this->statut >= 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal';
			$sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->cond_reglement_id = $cond_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Propale::cond_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Propale::cond_reglement, etat propale incompatible');
			$this->error='Etat propale incompatible '.$this->statut;
			return -2;
		}
	}


	/**
	 *   \brief      Change le mode de r�glement
	 *   \param      mode_reglement     Id du nouveau mode
	 *   \return     int         		>0 si ok, <0 si ko
	 */
	function mode_reglement($mode_reglement_id)
	{
		dolibarr_syslog('Propale::mode_reglement('.$mode_reglement_id.')');
		if ($this->statut >= 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal';
			$sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->mode_reglement_id = $mode_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Propale::mode_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Propale::mode_reglement, etat propale incompatible');
			$this->error='Etat facture incompatible '.$this->statut;
			return -2;
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

    $result = $this->db->query($sql);
    
    if ($result)
    {
    	if ($this->db->num_rows($result))
    	{
	    $obj = $this->db->fetch_object($result);

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
	 $this->db->free($result);

      }
    else
      {
	dolibarr_print_error($this->db);
      }
  }


	/**
	 *    	\brief      Retourne le libell� du statut d'une propale (brouillon, valid�e, ...)
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string		Libell�
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libell� d'un statut donn�
	 *    	\param      statut		id statut
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string		Libell�
 	 */
    function LibStatut($statut,$mode=1)
    {
    	global $langs;
    	$langs->load("propal");

        if ($mode == 0)
        {
	        return $this->labelstatut[$statut];
		}
        if ($mode == 1)
        {
        	return $this->labelstatut_short[$statut];
        }
        if ($mode == 2)
        {
        	if ($statut==0) return img_picto($langs->trans('PropalStatusDraftShort'),'statut0').' '.$this->labelstatut_short[$statut];
        	if ($statut==1) return img_picto($langs->trans('PropalStatusOpenedShort'),'statut1').' '.$this->labelstatut_short[$statut];
        	if ($statut==2) return img_picto($langs->trans('PropalStatusSignedShort'),'statut3').' '.$this->labelstatut_short[$statut];
        	if ($statut==3) return img_picto($langs->trans('PropalStatusNotSignedShort'),'statut5').' '.$this->labelstatut_short[$statut];
        	if ($statut==4) return img_picto($langs->trans('PropalStatusBilledShort'),'statut6').' '.$this->labelstatut_short[$statut];
        }
        if ($mode == 3)
        {
        	if ($statut==0) return img_picto($langs->trans('PropalStatusDraftShort'),'statut0');
        	if ($statut==1) return img_picto($langs->trans('PropalStatusOpenedShort'),'statut1');
        	if ($statut==2) return img_picto($langs->trans('PropalStatusSignedShort'),'statut3');
        	if ($statut==3) return img_picto($langs->trans('PropalStatusNotSignedShort'),'statut5');
        	if ($statut==4) return img_picto($langs->trans('PropalStatusBilledShort'),'statut6');
        }
        if ($mode == 4)
        {
        	if ($statut==0) return img_picto($langs->trans('PropalStatusDraft'),'statut0').' '.$this->labelstatut[$statut];
        	if ($statut==1) return img_picto($langs->trans('PropalStatusOpened'),'statut1').' '.$this->labelstatut[$statut];
        	if ($statut==2) return img_picto($langs->trans('PropalStatusSigned'),'statut3').' '.$this->labelstatut[$statut];
        	if ($statut==3) return img_picto($langs->trans('PropalStatusNotSigned'),'statut5').' '.$this->labelstatut[$statut];
        	if ($statut==4) return img_picto($langs->trans('PropalStatusBilled'),'statut6').' '.$this->labelstatut[$statut];
        }
        if ($mode == 5)
        {
        	if ($statut==0) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('PropalStatusDraftShort'),'statut0');
        	if ($statut==1) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('PropalStatusOpenedShort'),'statut1');
        	if ($statut==2) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('PropalStatusSignedShort'),'statut3');
        	if ($statut==3) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('PropalStatusNotSignedShort'),'statut5');
        	if ($statut==4) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('PropalStatusBilledShort'),'statut6');
        }
    }


    /**
     *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param      user        Objet user
     *      \param      mode        "opened" pour propal � fermer, "signed" pour propale � facturer
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_board($user,$mode)
    {
        global $conf, $user;

        $this->nbtodo=$this->nbtodolate=0;
        $clause = "WHERE";
        
        $sql ="SELECT p.rowid, p.ref, ".$this->db->pdate("p.datec")." as datec,".$this->db->pdate("p.fin_validite")." as datefin";
        $sql.=" FROM ".MAIN_DB_PREFIX."propal as p";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
        	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc";
        	$sql.= " WHERE sc.fk_user = " .$user->id;
        	$clause = "AND";
        }
        if ($mode == 'opened') $sql.=" ".$clause." p.fk_statut = 1";
        if ($mode == 'signed') $sql.=" ".$clause." p.fk_statut = 2";
        if ($user->societe_id) $sql.=" AND p.fk_soc = ".$user->societe_id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->datefin < (time() - $conf->propal->cloture->warning_delay))
                {
                	if ($mode == 'opened') $this->nbtodolate++;
                	if ($mode == 'signed') $this->nbtodolate++;
                	// \todo Definir r�gle des propales � facturer en retard
                	// if ($mode == 'signed' && ! sizeof($this->FactureListeArray($obj->rowid))) $this->nbtodolate++;
                }
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
     *    \brief     Insert en base un objet propal compl�tement d�finie par ses donn�es membres (resultant d'une copie par exemple).
     *    \return    int                 l'id du nouvel objet propal en base si ok, <0 si ko
     *    \see       create
     */
	function create_from($user)
	{
		global $conf,$lang;
		
		$this->fin_validite = $this->datep + ($this->duree_validite * 24 * 3600);
		
		// on verifie si la ref n'est pas utilisee
		$soc = new Societe($this->db);
	  $soc->fetch($this->socid);
	  $this->verifyNumRef($soc);

		dolibarr_syslog("Propal::create_from ref=".$this->ref);

    $this->db->begin();

		$this->fetch_client();

        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal (fk_soc, price, remise, remise_percent, remise_absolue,";
        $sql.= " tva, total, datep, datec, ref, fk_user_author, note, note_public, model_pdf, fin_validite, fk_cond_reglement, fk_mode_reglement, fk_adresse_livraison";
        if ($conf->global->PROPALE_ADD_SHIPPING_DATE) $sql.= ", date_livraison";
        $sql.= ")";
        $sql.= " VALUES ('$this->socid', '0', '$this->remise', '$this->remise_percent', '$this->remise_absolue',";
        $sql.= " '0','0','".$this->db->idate($this->datep)."', now(), '$this->ref', '$this->author',";
        $sql.= "'".addslashes($this->note)."',";
        $sql.= "'".addslashes($this->note_public)."',";
        $sql.= "'$this->modelpdf','".$this->db->idate($this->fin_validite)."',";
        $sql.= " '$this->cond_reglement_id', '$this->mode_reglement_id', '$this->adresse_livraison_id'";
        if ($conf->global->PROPALE_ADD_SHIPPING_DATE) $sql.= ", '".$this->db->idate($this->date_livraison)."'";
        $sql.= ")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."propal");

            if ($this->id)
            {
                /*
                 *  Insertion du detail des produits dans la base
                 */
                 foreach($this->lignes as $ligne)
                 {
					$resql = $this->addline(
						$this->id,
						$ligne->desc,
						$ligne->subprice, //r�cup�rer le prix non remis�
						$ligne->qty,
						$ligne->tva_tx,
						$ligne->fk_product,
						$ligne->remise_percent,
						'HT'
						);

					if ($resql < 0)
					{
						$this->error=$this->db->error;
						dolibarr_print_error($this->db);
						break;
					}
                }

	            if ($resql)
	            {
   					// Mise a jour infos d�normalis�s
	                $resql=$this->update_price();
	                if ($resql)
	                {
	                    // Appel des triggers
	                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	                    $interface=new Interfaces($this->db);
	                    $result=$interface->run_triggers('PROPAL_CREATE',$this,$user,$langs,$conf);
						if ($result < 0) { $error++; $this->errors=$interface->errors; }
	                    // Fin appel triggers
	
	                    $this->db->commit();
			            dolibarr_syslog("Propal::Create_from done id=".$this->id);
	                    return $this->id;
	                }
	                else
	                {
	                    $this->error=$this->db->error();
	                    dolibarr_syslog("Propal::Create_from -2 ".$this->error);
	                    $this->db->rollback();
	                    return -2;
	                }
	            }
            }
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Propal::Create_from -1 ".$this->error);
            $this->db->rollback();
            return -1;
        }

		$this->db->commit();
        dolibarr_syslog("Propal::Create_from done id=".$this->id);
        return $this->id;
	}


	/**
	 *		\brief		Initialise la propale avec valeurs fictives al�atoire
	 *					Sert � g�n�rer une facture pour l'aperu des mod�les ou demo
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de soci�t� socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client=1 LIMIT 10";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs)
			{
				$i++;

				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];
			}
		}

		// Charge tableau des produits prodids
		$prodids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods)
			{
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise param�tres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
		$this->date = time();
		$this->fin_validite = $this->date+3600*24*30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';
		$this->note_public='SPECIMEN';
		$nbp = rand(1, 9);
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$ligne=new PropaleLigne($this->db);
			$ligne->desc=$langs->trans("Description")." ".$xnbp;
			$ligne->qty=1;
			$ligne->subprice=100;
			$ligne->price=100;
			$ligne->tva_tx=19.6;
			$ligne->total_ht=100;
			$ligne->total_ttc=119.6;
			$ligne->total_tva=19.6;
			$prodid = rand(1, $num_prods);
			$ligne->produit_id=$prodids[$prodid];
			$this->lignes[$xnbp]=$ligne;
			$xnbp++;
		}

		$this->amount_ht      = $xnbp*100;
		$this->total_ht       = $xnbp*100;
		$this->total_tva      = $xnbp*19.6;
		$this->total_ttc      = $xnbp*119.6;
	}

	/**
	*      \brief      Charge indicateurs this->nb de tableau de bord
	*      \return     int         <0 si ko, >0 si ok
	*/
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
		}
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["proposals"]=$obj->nb;
			}
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}
	
 /**
   *      \brief      V�rifie si la ref n'est pas d�j� utilis�e
   *      \param	    soc  		            objet societe
   */
  function verifyNumRef($soc)
  {
  	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."propal";
  	$sql.= " WHERE ref = '".$this->ref."'";

  	$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num > 0)
			{
				$this->ref = $this->getNextNumRef($soc);
			}
		}
	}
  	
	
 /**
   *      \brief      Renvoie la r�f�rence de propale suivante non utilis�e en fonction du module
   *                  de num�rotation actif d�fini dans PROPALE_ADDON
   *      \param	    soc  		            objet societe
   *      \return     string              reference libre pour la propale
   */
  function getNextNumRef($soc)
  {
    global $db, $langs;
    $langs->load("propal");

    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/propale/";

    if (defined("PROPALE_ADDON") && PROPALE_ADDON)
    {
    	$file = PROPALE_ADDON.".php";

	    // Chargement de la classe de num�rotation
	    $classname = PROPALE_ADDON;
	    require_once($dir.$file);

	    $obj = new $classname();

	    $numref = "";
	    $numref = $obj->getNextValue($soc,$this);

	    if ( $numref != "")
	    {
	      return $numref;
	    }
	    else
	    {
	      dolibarr_print_error($db,"Propale::getNextNumRef ".$obj->error);
	      return "";
	    }
     }
     else
     {
	     print $langs->trans("Error")." ".$langs->trans("Error_PROPALE_ADDON_NotDefined");
	     return "";
     }
  }
  
  /**
		\brief    	Renvoie nom clicable (avec eventuellement le picto)
		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
		\param		option			Sur quoi pointe le lien
        \param      get_params		Parametres added to url
		\return		string			Chaine avec URL
	*/
	function getNomUrl($withpicto=0,$option='', $get_params='')
	{
		global $langs;
		
		$result='';
		if($option == '')
        {
		  $lien = '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$this->id. $get_params .'">';
        }
        if($option == 'compta')
        {
          $lien = '<a href="'.DOL_URL_ROOT.'/compta/propal.php?propalid='.$this->id. $get_params .'">';
        }
		$lienfin='</a>';
		
		$picto='order';
		$label=$langs->trans("ShowPropal").': '.$this->ref;
		
		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.$this->ref.$lienfin;
		return $result;
	}

}


/**
        \class      PropalLigne
		\brief      Classe permettant la gestion des lignes de propales
*/
class PropaleLigne
{
	var $db;
	var $error;

	// From llx_propaldet
	var $rowid;
	var $fk_propal;
	var $desc;          // Description ligne
	var $fk_product;		// Id produit pr�d�fini

	var $qty;
	var $tva_tx;
	var $subprice;
	var $remise_percent;
	var $fk_remise_except;
	
	var $rang = 0;
	var $marge_tx;
	var $marque_tx;
	
	var $special_code;	// Liste d'options non cumulabels:
						// 1: frais de port
						// 2: ecotaxe
						// 3: ?? 
	 
	var $info_bits = 0;	// Liste d'options cumulables:
						// Bit 0: 	0 si TVA normal - 1 si TVA NPR
						// Bit 1:	0 ligne normale - 1 si ligne de remise fixe
	
	var $total_ht;			// Total HT  de la ligne toute quantit� et incluant la remise ligne
	var $total_tva;			// Total TVA  de la ligne toute quantit� et incluant la remise ligne
	var $total_ttc;			// Total TTC de la ligne toute quantit� et incluant la remise ligne

	// Ne plus utiliser
	var $remise;
	var $price;

	// From llx_product
	var $ref;						// Reference produit
	var $libelle;       // Label produit
	var $product_desc;  // Description produit


	/**
	 *      \brief     Constructeur d'objets ligne de propal
	 *      \param     DB      handler d'acc�s base de donn�e
	 */
	function PropaleLigne($DB)
	{
		$this->db= $DB;
	}

	/**
	 *      \brief     Recup�re l'objet ligne de propal
	 *      \param     rowid           id de la ligne de propal
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT pd.rowid, pd.fk_propal, pd.fk_product, pd.description, pd.price, pd.qty, pd.tva_tx,';
		$sql.= ' pd.remise, pd.remise_percent, pd.fk_remise_except, pd.subprice,';
		$sql.= ' pd.info_bits, pd.total_ht, pd.total_tva, pd.total_ttc, pd.marge_tx, pd.marque_tx, pd.special_code, pd.rang,';
		$sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pd';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pd.fk_product = p.rowid';
		$sql.= ' WHERE pd.rowid = '.$rowid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->rowid          = $objp->rowid;
			$this->fk_propal      = $objp->fk_propal;
			$this->desc           = $objp->description;
			$this->qty            = $objp->qty;
			$this->price          = $objp->price;
			$this->subprice       = $objp->subprice;
			$this->tva_tx         = $objp->tva_tx;
			$this->remise         = $objp->remise;
			$this->remise_percent = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->produit_id     = $objp->fk_product;
			$this->info_bits      = $objp->info_bits;
			$this->total_ht       = $objp->total_ht;
			$this->total_tva      = $objp->total_tva;
			$this->total_ttc      = $objp->total_ttc;
			$this->marge_tx       = $objp->marge_tx;
			$this->marque_tx      = $objp->marque_tx;
			$this->special_code   = $objp->special_code;
			$this->rang           = $objp->rang;

			$this->ref            = $objp->product_ref;
			$this->libelle        = $objp->product_libelle;
			$this->product_desc	  = $objp->product_desc;

			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}
	
	/**
	 *      \brief     	Insert object line propal in database
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function insert()
	{
		dolibarr_syslog("PropaleLigne::insert rang=".$this->rang);
		$this->db->begin();

		// Nettoyage parameteres
		if (! $this->remise) $this->remise=0;
		if (! $this->remise_percent) $this->remise_percent=0;
		if (! $this->info_bits) $this->info_bits=0;
		
		$rangtouse=$this->rang;
		if ($rangtouse == -1)
		{
			// R�cup�re rang max de la propale dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'propaldet';
			$sql.= ' WHERE fk_propal ='.$this->fk_propal;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$rangtouse = $obj->max + 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}	

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'propaldet';
		$sql.= ' (fk_propal, description, fk_product, fk_remise_except, qty, tva_tx,';
		$sql.= ' subprice, remise_percent, ';
		$sql.= ' info_bits, ';
		$sql.= ' total_ht, total_tva, total_ttc, marge_tx, marque_tx, special_code, rang)';
		$sql.= " VALUES (".$this->fk_propal.",";
		$sql.= " '".addslashes($this->desc)."',";
		if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
		else { $sql.='null,'; }
		if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
		else $sql.= 'null,';
		$sql.= " ".price2num($this->qty).",";
		$sql.= " ".price2num($this->tva_tx).",";
		$sql.= " ".price2num($this->subprice).",";
		$sql.= " ".price2num($this->remise_percent).",";
		$sql.= " '".$this->info_bits."',";
		$sql.= " ".price2num($this->total_ht).",";
		$sql.= " ".price2num($this->total_tva).",";
		$sql.= " ".price2num($this->total_ttc).",";
		if (isset($this->marge_tx)) $sql.= ' '.$this->marge_tx.',';
		else $sql.= ' null,';
		if (isset($this->marque_tx)) $sql.= ' '.$this->marque_tx.',';
		else $sql.= ' null,';
		if (isset($this->special_code)) $sql.= ' '.$this->special_code.',';
		else $sql.= ' 0,';
		$sql.= ' '.$rangtouse;
		$sql.= ')';

       	dolibarr_syslog("PropaleLigne::insert sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->rang=$rangmax;
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error()." sql=".$sql;
        	dolibarr_syslog("PropaleLigne::insert Error ".$this->error);
			$this->db->rollback();
            return -1;
		}
	}
	
	
	/**
	 *      \brief     	Mise a jour de l'objet ligne de propale en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet SET";
		$sql.= " description='".addslashes($this->desc)."'";
		if ($fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
		else $sql.= ",fk_remise_except=null";
		$sql.= ",tva_tx='".price2num($this->tva_tx)."'";
		$sql.= ",qty='".price2num($this->qty)."'";
		$sql.= ",subprice=".price2num($this->subprice)."";
		$sql.= ",remise_percent=".price2num($this->remise_percent)."";
		$sql.= ",price=".price2num($this->price)."";					// \TODO A virer
		$sql.= ",remise=".price2num($this->remise)."";					// \TODO A virer
		$sql.= ",info_bits='".$this->info_bits."'";
		$sql.= ",total_ht=".price2num($this->total_ht)."";
		$sql.= ",total_tva=".price2num($this->total_tva)."";
		$sql.= ",total_ttc=".price2num($this->total_ttc)."";
		$sql.= ",rang='".$this->rang."'";
		$sql.= ",marge_tx='".$this->marge_tx."'";
		$sql.= ",marque_tx='".$this->marque_tx."'";
		$sql.= " WHERE rowid = ".$this->rowid;

       	dolibarr_syslog("PropaleLigne::update sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("PropaleLigne::update Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}

	/**
	 *      \brief     	Mise a jour en base des champs total_xxx de ligne
	 *		\remarks	Utilis� par migration
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."propaldet SET";
		$sql.= " total_ht=".price2num($this->total_ht,'MT')."";
		$sql.= ",total_tva=".price2num($this->total_tva,'MT')."";
		$sql.= ",total_ttc=".price2num($this->total_ttc,'MT')."";
		$sql.= " WHERE rowid = ".$this->rowid;

       	dolibarr_syslog("PropaleLigne::update_total sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("PropaleLigne::update_total Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}
	
}

?>
