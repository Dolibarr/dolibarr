<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT ."/contact.class.php");

 
/**
        \class      Commande
        \brief      Classe de gestion de commande
*/
class Commande
{
	var $db ;
	var $id ;
	var $socidp;
	var $contactid;
	var $statut;
	var $facturee;
	var $brouillon;
	var $cond_reglement_id;
	var $cond_reglement_code;
	var $mode_reglement_id;
	var $mode_reglement_code;
	var $adresse_livraison_id;
	var $date;				// Date commande
	var $date_livraison;	// Date livraison souhaitée
    var $remise_percent;
    var $remise_absolue;

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
		$langs->load('orders');
		$this->db = $DB;

		$this->sources[0] = $langs->trans('OrderSource0');
		$this->sources[1] = $langs->trans('OrderSource1');
		$this->sources[2] = $langs->trans('OrderSource2');
		$this->sources[3] = $langs->trans('OrderSource3');
		$this->sources[4] = $langs->trans('OrderSource4');
		$this->sources[5] = $langs->trans('OrderSource5');

		$this->remise = 0;
		$this->remise_percent = 0;

		$this->products = array();
	}

    /**     \brief      Créé la commande depuis une propale existante
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
			$CommLigne->libelle           = $propal->lignes[$i]->libelle;
			$CommLigne->description       = $propal->lignes[$i]->desc;
			$CommLigne->price             = $propal->lignes[$i]->subprice;
			$CommLigne->subprice          = $propal->lignes[$i]->subprice;
			$CommLigne->tva_tx            = $propal->lignes[$i]->tva_tx;
			$CommLigne->qty               = $propal->lignes[$i]->qty;
			$CommLigne->remise_percent    = $propal->lignes[$i]->remise_percent;
			$CommLigne->product_id        = $propal->lignes[$i]->product_id;
			$this->lines[$i] = $CommLigne;
		}

		$this->soc_id = $propal->soc_id;
		$this->cond_reglement_id = $propal->cond_reglement_id;
		$this->mode_reglement_id = $propal->mode_reglement_id;
		$this->date_livraison = $propal->date_livraison;
		$this->adresse_livraison_id = $propal->adresse_livraison_id;
    
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
		global $conf;
		if ($user->rights->commande->valider)
		{
			if (defined('COMMANDE_ADDON'))
			{
				if (is_readable(DOL_DOCUMENT_ROOT .'/includes/modules/commande/'.COMMANDE_ADDON.'.php'))
				{
					require_once DOL_DOCUMENT_ROOT .'/includes/modules/commande/'.COMMANDE_ADDON.'.php';

					// Definition du nom de module de numerotation de commande

					// \todo  Normer le nom des classes des modules de numérotation de ref de commande avec un nom du type NumRefCommandesXxxx
					//
					//$list=split('_',COMMANDE_ADDON);
					//$numrefname=$list[2];
					//$modName = 'NumRefCommandes'.ucfirst($numrefname);
					$modName=COMMANDE_ADDON;

					// Recuperation de la nouvelle reference
					$objMod = new $modName($this->db);
					$soc = new Societe($this->db);
					$soc->fetch($this->soc_id);
					
					// on vérifie si la commande est en numérotation provisoire
					$comref = substr($this->ref, 1, 4);
					if ($comref == PROV)
					{
						$num = $objMod->commande_get_num($soc);
					}
					else
					{
						$num = $this->ref;
					}

					$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
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
					print 'Impossible de lire le module de numérotation';
				}
			}
			else
			{
				print 'Le module de numérotation n\'est pas défini' ;
			}
		}
		return $result ;
	}
	
	/**
   *
   *
   */
    function reopen($userid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 0";
    
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
   *    \brief      Cloture la commande
   *    \param      user        Objet utilisateur qui cloture
   *    \return     int         <0 si ko, >0 si ok
   */
	function cloture($user)
	{
		global $conf;
		if ($user->rights->commande->valider)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql.= ' SET fk_statut = 3,';
			$sql.= ' fk_user_cloture = '.$user->id.',';
			$sql.= ' date_cloture = now()';
			$sql.= " WHERE rowid = $this->id AND fk_statut > 0 ;";

			if ($this->db->query($sql) )
			{
				if($conf->stock->enabled && $conf->global->PRODUIT_SOUSPRODUITS == 1)
							{
								require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");
								for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
								{
										$prod = new Product($this->db, $this->lignes[$i]->product_id);
										$prod -> get_sousproduits_arbo ();
										$prods_arbo = $prod->get_each_prod();
										if(sizeof($prods_arbo) > 0)
										{
											foreach($prods_arbo as $key => $value)
											{
													// on décompte le stock de tous les sousproduits
													$mouvS = new MouvementStock($this->db);
													$entrepot_id = "1";
                            						$result=$mouvS->livraison($user, $value[1], $entrepot_id, $value[0]);
													
											}
										}
										// on décompte pas le stock du produit principal, ça serait fait manuellement avec l'expédition
										// $result=$mouvS->livraison($user, $this->lignes[$i]->product_id, $entrepot_id, $this->lignes[$i]->qty);
									}
							
							}
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
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET fk_statut = -1';
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
	 * 		\brief		Créé la commande
	 *		\param		user		Id utilisateur qui crée
	 */
	function create($user)
	{
		global $conf,$langs;

		// Vérification paramètres
		if ($this->source < 0)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Source"));
			return -1;
		}

		// On positionne en mode brouillon la commande
		$this->brouillon = 1;
		if (! $remise)
		{
			$remise = 0 ;
		}
		if (! $this->projetid)
		{
			$this->projetid = 0;
		}

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commande (';
		$sql.= 'fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note, ref_client,';
		$sql.= ' model_pdf, fk_cond_reglement, fk_mode_reglement, date_livraison, fk_adresse_livraison,';
		$sql.= ' remise_absolue, remise_percent)';
		$sql.= ' VALUES ('.$this->soc_id.', now(), '.$user->id.', '.$this->projetid.',';
		$sql.= ' '.$this->db->idate($this->date_commande).',';
		$sql.= ' '.$this->source.', ';
		$sql.= " '".addslashes($this->note)."', ";
		$sql.= " '".$this->ref_client."', '".$this->modelpdf.'\', \''.$this->cond_reglement_id.'\', \''.$this->mode_reglement_id.'\',';
		$sql.= " '".($this->date_livraison?$this->db->idate($this->date_livraison):'null').'\',';
		$sql.= " '".$this->adresse_livraison_id."',";
		$sql.= " '".$this->remise_absolue."',";
		$sql.= " '".$this->remise_percent."')";

		dolibarr_syslog("Commande.class.php::create sql=$sql");
		if ( $this->db->query($sql) )
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commande');
			/*
			 *  Insertion des produits dans la base
			 */
			for ($i = 0 ; $i < sizeof($this->products) ; $i++)
			{
				$prod = new Product($this->db, $this->products[$i]);
				if ($prod->fetch($this->products[$i]))
				{
					$this->soc_id;
					$client = new Societe($this->db);
     				$client->fetch($this->soc_id);
					if($client->tva_assuj == "0")
						$tva_tx ="0";
					else
						$tva_tx=$prod->tva_tx;
					// multiprix
					if($conf->global->PRODUIT_MULTIPRICES == 1)
					{
						
						//$prod->multiprices[$client->price_level]
						$this->insert_product_generic($prod->libelle,
						$prod->description,
						$prod->multiprices[$client->price_level],
						$this->products_qty[$i],
						$tva_tx,
						$this->products[$i],
						$this->products_remise_percent[$i]);
					}
					else
					{
						$this->insert_product_generic($prod->libelle,
						$prod->description,
						$prod->price,
						$this->products_qty[$i],
						$tva_tx,
						$this->products[$i],
						$this->products_remise_percent[$i]);
					}
				}
			}
			$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
			if ($this->db->query($sql))
			{
				if ($this->id && $this->propale_id)
				{
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_pr (fk_commande, fk_propale) VALUES ('.$this->id.','.$this->propale_id.')';
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
						 $this->lines[$i]->description,
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
	function insert_product_generic($p_desc, $p_product_desc, $p_price, $p_qty, $p_tva_tx=19.6, $p_product_id=0, $remise_percent=0)
	{
		global $conf;
		if ($this->statut == 0)
		{
			if (strlen(trim($p_qty)) == 0)
			{
				$p_qty = 1;
			}

			$p_price = ereg_replace(',','.',$p_price);

			$price = $p_price;
			$subprice = $p_price;
			if ($remise_percent > 0)
			{
				$remise = round(($p_price * $remise_percent / 100), 2);
				$price = $p_price - $remise;
			}
			
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet (fk_commande, fk_product, qty, price, tva_tx, label, description, remise_percent, subprice) VALUES ';
			$sql .= " ('".$this->id."', '$p_product_id','". $p_qty."','".price2num($price)."','".$p_tva_tx."','".addslashes($p_desc)."','".addslashes($p_product_desc)."', '$remise_percent', '$subprice') ; ";
			
			// Bugfix 
			/* 
			if ($conf->global->PRODUIT_CHANGE_PROD_DESC) 	 
      {
				$sql .= " ('".$this->id."', '$p_product_id','". $p_qty."','".price2num($price)."','".$p_tva_tx."','".addslashes($p_desc)."','".addslashes($p_product_desc)."', '$remise_percent', '$subprice') ; ";
			}
			else
			{
				$sql .= " ('".$this->id."', '$p_product_id','". $p_qty."','".price2num($price)."','".$p_tva_tx."','".addslashes($p_desc)."','".addslashes($p_product_desc)."', '$remise_percent', '$subprice') ; ";
			}
				*/
				
								
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
	function addline($desc, $product_desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
	{
		global $conf;
		// Nettoyage parametres
		$qty = ereg_replace(',','.',$qty);
		$pu = ereg_replace(',','.',$pu);
		$desc=trim($desc);
		$product_desc=trim($product_desc);
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
				$product_desc = $prod->description;
				$client = new Societe($this->db);
     			$client->fetch($this->soc_id);
				if($client->tva_assuj == "0")
					$txtva ="0";
				else
					$txtva=$prod->tva_tx;
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES == 1)
				{
						$pu    = $prod->multiprices[$client->price_level];
				}
				else
					$pu    = $prod->price;
			
			}
		}
		$remise = 0;
		$price = round(ereg_replace(',','.',$pu), 2);
		$subprice = $price;
		if (trim(strlen($remise_percent)) > 0)
		{
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = $pu - $remise;
		}

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet (fk_commande,label,description,fk_product, price,qty,tva_tx, remise_percent, subprice, remise)';
		
		if ($conf->global->PRODUIT_CHANGE_PROD_DESC)
     {
		   $sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($product_desc) . "',$fk_product,".price2num($price).", '$qty', $txtva, $remise_percent,'".price2num($subprice)."','".price2num( $remise)."') ;";
	   }
	  else
	  {
	  	 $sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "',$fk_product,".price2num($price).", '$qty', $txtva, $remise_percent,'".price2num($subprice)."','".price2num( $remise)."') ;";
	  }

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
   * Ajoute une ligne de commande libre
   *
   */
	function addline_libre($desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
	{
		global $conf;
		// Nettoyage parametres
		$qty = ereg_replace(',','.',$qty);
		$pu = ereg_replace(',','.',$pu);
		$desc=trim($desc);
		if (strlen(trim($qty))==0)
		{
			$qty=1;
		}

		// Verifs
		if (! $this->brouillon) return -1;

		$this->db->begin();

		$remise = 0;
		$price = round(ereg_replace(',','.',$pu), 2);
		$subprice = $price;
		if (trim(strlen($remise_percent)) > 0)
		{
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = $pu - $remise;
		}

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet (fk_commande,label,description,fk_product, price,qty,tva_tx, remise_percent, subprice, remise)';
		$sql .= " VALUES ($this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "','$fk_product',".price2num($price).", '$qty', '$txtva', $remise_percent,'".price2num($subprice)."','".price2num( $remise)."') ;";

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
		global $conf;
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
			/** POUR AJOUTER AUTOMATIQUEMENT LES SOUSPRODUITS À LA COMMANDE
			if($conf->global->PRODUIT_SOUSPRODUITS == 1)
			{
				$prod = new Product($this->db, $idproduct);
				$prod -> get_sousproduits_arbo ();
				$prods_arbo = $prod->get_each_prod();
				if(sizeof($prods_arbo) > 0)
				{
					foreach($prods_arbo as $key => $value)
					{
						// print "id : ".$value[1].' :qty: '.$value[0].'<br>';
						if(! in_array($value[1],$this->products))
							$this->add_product($value[1], $value[0]);
					
					}
				}
			
			}
			**/
		}
	}

	/**
	* Lit une commande
	*
	*/
	function fetch($id)
	{
		$sql = 'SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva, c.fk_cond_reglement, c.fk_mode_reglement,';
		$sql.= ' '.$this->db->pdate('c.date_commande').' as date_commande, '.$this->db->pdate('c.date_livraison').' as date_livraison,';
		$sql.= ' c.fk_projet, c.remise_percent, c.remise, c.remise_absolue, c.source, c.facture, c.note, c.ref_client, c.model_pdf, c.fk_adresse_livraison';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql.= ' WHERE c.rowid = '.$id;

		$result = $this->db->query($sql) ;
		if ( $result )
		{
			$obj = $this->db->fetch_object();
			$this->id                   = $obj->rowid;
			$this->ref                  = $obj->ref;
			$this->ref_client           = $obj->ref_client;
			$this->soc_id               = $obj->fk_soc;
			$this->socidp               = $obj->fk_soc;
			$this->statut               = $obj->fk_statut;
			$this->user_author_id       = $obj->fk_user_author;
			$this->total_ht             = $obj->total_ht;
			$this->total_tva            = $obj->tva;
			$this->total_ttc            = $obj->total_ttc;
			$this->date                 = $obj->date_commande;
			$this->remise               = $obj->remise;
			$this->remise_percent       = $obj->remise_percent;
			$this->remise_absolue       = $obj->remise_absolue;
			$this->source               = $obj->source;
			$this->facturee             = $obj->facture;
			$this->note                 = $obj->note;
			$this->projet_id            = $obj->fk_projet;
			$this->modelpdf             = $obj->model_pdf;
			$this->cond_reglement_id    = $obj->fk_cond_reglement;
			$this->mode_reglement_id    = $obj->fk_mode_reglement;
			$this->date_livraison       = $obj->date_livraison;
			$this->adresse_livraison_id = $obj->fk_adresse_livraison;
			
			$this->db->free();
			
			if ($this->cond_reglement_id)
      {
              $sql = "SELECT rowid, libelle, code";
              $sql.= " FROM ".MAIN_DB_PREFIX."cond_reglement";
              $sql.= " WHERE rowid = ".$this->cond_reglement_id;
                   
              $resqlcond = $this->db->query($sql);
                   
              if ($resqlcond)
              {
                 $objc = $this->db->fetch_object($resqlcond);
                 $this->cond_reglement      = $objc->libelle;
                 $this->cond_reglement_code = $objc->code;
              }
      }
      
      if ($this->user_author_id)
      {
             $sql = "SELECT name, firstname";
             $sql.= " FROM ".MAIN_DB_PREFIX."user";
             $sql.= " WHERE rowid = ".$this->user_author_id;
                	
             $resqluser = $this->db->query($sql);
                	
             if ($resqluser)
             {
                $obju = $this->db->fetch_object($resqluser);
                $this->user_author_name      = $obju->name;
                $this->user_author_firstname = $obju->firstname;
             }
      }
			
			if ($this->statut == 0)
				$this->brouillon = 1;
			// exp pdf -----------
			$this->lignes = array();
			$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice, l.coef,';
			$sql.= ' p.label, p.description as product_desc, p.ref, p.fk_product_type, p.rowid as prodid';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product=p.rowid';
			$sql.= ' WHERE l.fk_commande = '.$this->id;
			$sql.= ' ORDER BY l.rowid';
			$result = $this->db->query($sql);
                if ($result)
                {
                    $num = $this->db->num_rows($result);
                    $i = 0;
    
                    while ($i < $num)
                    {
                        $objp                  = $this->db->fetch_object($result);
    
                        $ligne                 = new CommandeLigne();

                        $ligne->desc           = $objp->description;  // Description ligne
                        $ligne->qty            = $objp->qty;
                        $ligne->tva_tx         = $objp->tva_tx;
                        $ligne->subprice       = $objp->subprice;
                        $ligne->remise_percent = $objp->remise_percent;
                        $ligne->price          = $objp->price;
                        $ligne->product_id     = $objp->fk_product;
                        $ligne->coef           = $objp->coef;

                        $ligne->libelle        = $objp->label;        // Label produit
                        $ligne->product_desc   = $objp->product_desc; // Description produit
                        $ligne->ref            = $objp->ref;
    
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
			
			
			// -------- exp pdf //
			/*
			* Propale associée
			*/
			$sql = 'SELECT fk_propale FROM '.MAIN_DB_PREFIX.'co_pr WHERE fk_commande = '.$this->id;
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
	
	  /*
   *
   *
   *
   */
	 
  function set_pdf_model($user, $modelpdf)
   {
      if ($user->rights->commande->creer)
	     {

	      $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET model_pdf = '$modelpdf'";
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
   *
   *
   */
	function fetch_lignes($only_product=0)
	{
		$this->lignes = array();
		$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice';
		if ($only_product==1)
		{
			$sql .= ' FROM '.MAIN_DB_PREFIX.'commandedet as l LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = l.fk_product) WHERE l.fk_commande = '.$this->id.' AND p.fk_product_type <> 1 ORDER BY l.rowid';
		}
		else
		{
			$sql .= ' FROM '.MAIN_DB_PREFIX."commandedet as l WHERE l.fk_commande = $this->id ORDER BY l.rowid";
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
				$ligne->id             = $objp->rowid;
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
   *
   *
   */
	 
  function fetch_adresse_livraison($id)
    {
    	$idadresse = $id;
      $adresse = new Societe($this->db);
      $adresse->fetch_adresse_livraison($idadresse);
      $this->adresse = $adresse;
    }
    
 /**
   *
   *
   */
	 
  function fetch_contact_commande($id)
    {
    	$idcontact = $id;
      $contact = new Contact($this->db);
      $contact->fetch($idcontact);
      $this->contact = $contact;
    }

    /**
     *      \brief      Renvoie un tableau avec les livraison par ligne
     *      \param      filtre_statut       Filtre sur statut
     *      \return     int                 0 si OK, <0 si KO
     */
	function livraison_array($filtre_statut=-1)
	{
		$this->livraisons = array();
		$sql = 'SELECT fk_product, sum(ed.qty)';
		$sql.=' FROM '.MAIN_DB_PREFIX.'expeditiondet as ed, '.MAIN_DB_PREFIX.'expedition as e, '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.=' WHERE ed.fk_expedition = e.rowid AND ed.fk_commande_ligne = cd .rowid AND cd.fk_commande = c.rowid';
		$sql.=' AND cd.fk_commande =' .$this->id;
		if ($filtre_statut >= 0) $sql.=' AND e.fk_statut = '.$filtre_statut;
		$sql .= ' GROUP BY fk_product ';
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
	
	    return 0;
	}
	
  /**
   * Renvoie un tableau avec les livraison par ligne
   *
   */
	function nb_expedition()
	{
		$sql = 'SELECT count(*) FROM '.MAIN_DB_PREFIX.'expedition as e';
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
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE rowid = $idligne";
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
	 * 		\brief     	Applique une remise relative
	 * 		\param     	user		User qui positionne la remise
	 * 		\param     	remise
	 *		\return		int 		<0 si ko, >0 si ok
	 */
	function set_remise($user, $remise)
	{
		$remise=trim($remise)?trim($remise):0;

		if ($user->rights->commande->creer)
		{
			$remise=price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql.= ' SET remise_percent = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			if ($this->db->query($sql))
			{
				$this->remise_percent = $remise;
				$this->update_price($this->id);
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
	 * 		\brief     	Applique une remise absolue
	 * 		\param     	user 		User qui positionne la remise
	 * 		\param     	remise
	 *		\return		int 		<0 si ko, >0 si ok
	 */
	function set_remise_absolue($user, $remise)
	{
		$remise=trim($remise)?trim($remise):0;
		
		if ($user->rights->commande->creer)
		{
			$remise=price2num($remise);
			
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql.= ' SET remise_absolue = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			dolibarr_syslog("Commande::set_remise_absolue sql=$sql");

			if ($this->db->query($sql))
			{
				$this->remise_absolue = $remise;
				$this->update_price($this->id);
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
     *    \brief      Mets à jour le prix total de la proposition
     *    \return     int     <0 si ko, >0 si ok
     */
    function update_price()
    {
        include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";
    
        /*
         *  Liste des produits a ajouter
         */
        $sql = "SELECT price, qty, tva_tx FROM ".MAIN_DB_PREFIX."commandedet";
        $sql.= " WHERE fk_commande = ".$this->id;
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
        $calculs = calcul_price($products, $this->remise_percent, $this->remise_absolue);
    
        $this->total_remise   = $calculs[3];
		$this->amount_ht      = $calculs[4];
        $this->total_ht       = $calculs[0];
        $this->total_tva      = $calculs[1];
        $this->total_ttc      = $calculs[2];
		$tvas                 = $calculs[5];

        // Met a jour en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";
		$sql .= "  total_ht='". price2num($this->total_ht)."'";
		$sql .= ", tva='".      price2num($this->total_tva)."'";
		$sql .= ", total_ttc='".price2num($this->total_ttc)."'";
        $sql .= ", remise='".price2num($this->total_remise)."'";
        $sql .=" WHERE rowid = ".$this->id;
    
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
     *      \brief      Définit une date de livraison
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      date_livraison      Date de livraison  
     *      \return     int         		<0 si ko, >0 si ok
     */
    function set_date_livraison($user, $date_livraison)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET date_livraison = '".$date_livraison."'";
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
    
            if ($this->db->query($sql) )
            {
                $this->date_livraison = $date_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Commande::set_date_livraison Erreur SQL");
                return -1;
            }
        }
    }
    
    /**
     *      \brief      Définit une adresse de livraison
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      adresse_livraison      Adresse de livraison  
     *      \return     int         		<0 si ko, >0 si ok
     */
    function set_adresse_livraison($user, $adresse_livraison)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_adresse_livraison = '".$adresse_livraison."'";
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
    
            if ($this->db->query($sql) )
            {
                $this->adresse_livraison_id = $adresse_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Commande::set_adresse_livraison Erreur SQL");
                return -1;
            }
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
    
        $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."commande";
    
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
    
        $sql .= " ORDER BY date_commande DESC";

        $result=$this->db->query($sql);
        if ($result)
        {
            $numc = $this->db->num_rows($result);
    
            if ($numc)
            {
                $i = 0;
                while ($i < $numc)
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
	 *   \brief      Change les conditions de réglement de la commande
	 *   \param      cond_reglement_id      Id de la nouvelle condition de réglement
	 *   \return     int                    >0 si ok, <0 si ko
	 */
	function cond_reglement($cond_reglement_id)
	{
		dolibarr_syslog('Commande::cond_reglement('.$cond_reglement_id.')');
		if ($this->statut >= 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->cond_reglement_id = $cond_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Commande::cond_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Commande::cond_reglement, etat commande incompatible');
			$this->error='Etat commande incompatible '.$this->statut;
			return -2;
		}
	}


	/**
	 *   \brief      Change le mode de réglement
	 *   \param      mode        Id du nouveau mode
	 *   \return     int         >0 si ok, <0 si ko
	 */
	function mode_reglement($mode_reglement_id)
	{
		dolibarr_syslog('Commande::mode_reglement('.$mode_reglement_id.')');
		if ($this->statut >= 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->mode_reglement_id = $mode_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Commande::mode_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Commande::mode_reglement, etat facture incompatible');
			$this->error='Etat commande incompatible '.$this->statut;
			return -2;
		}
	}

    /**
     *      \brief      Positionne numero reference commande client
     *      \param      user            Utilisateur qui modifie
     *      \param      ref_client      Reference commande client
     *      \return     int             <0 si ko, >0 si ok
     */
	function set_ref_client($user, $ref_client)
	{
		if ($user->rights->commande->creer)
		{
    		dolibarr_syslog('Commande::set_ref_client this->id='.$this->id.', ref_client='.$ref_client);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET ref_client = '.(empty($ref_client) ? 'NULL' : '\''.addslashes($ref_client).'\'');
			$sql.= ' WHERE rowid = '.$this->id;
			if ($this->db->query($sql) )
			{
				$this->ref_client = $ref_client;
				return 1;
			}
			else
			{
                $this->error=$this->db->error();
				dolibarr_syslog('Commande::set_ref_client Erreur '.$this->error.' - '.$sql);
			    return -2;
			}
		}
		else
		{
		    return -1;
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
			$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET note = '".addslashes($note)."'";
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
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET facture = 1';
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
	 *      \brief     Mets à jour une ligne de commande
	 *      \param     rowid            Id de la ligne de facture
	 *      \param     desc             Description de la ligne
	 *      \param     pu               Prix unitaire
	 *      \param     qty              Quantité
	 *      \param     remise_percent   Pourcentage de remise de la ligne
	 *      \param     tva_tx           Taux TVA
	 *      \return    int              < 0 si erreur, > 0 si ok
	 */
	function update_line($rowid, $desc, $pu, $qty, $remise_percent=0, $tva_tx)
	{
		dolibarr_syslog("Commande::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent=0, $tva_tx");
		if ($this->brouillon)
		{
			$this->db->begin();
			
            // Nettoyage paramètres
			$pu=price2num($pu);
			if (strlen(trim($qty))==0) $qty=1;
			$remise = 0;
			$price = $pu;
			$subprice = $price;
			$remise_percent=trim($remise_percent);
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = $pu - $remise;
			}
			else
			{
				$remise_percent=0;
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet';
			$sql.= " SET description='".addslashes($desc)."',price='".price2num($price)."',subprice='".$subprice."',";
			$sql.= " remise='".$remise."',remise_percent='".$remise_percent."',qty='".$qty."',tva_tx='".$tva_tx."'";
			$sql.= " WHERE rowid = '".$rowid."'";

			$result=$this->db->query( $sql);
			if ( $result )
			{
				$this->update_price($this->id);
				$this->db->commit();
			   return $result;
			}
			else
			{
				$this->db->rollback();
				dolibarr_print_error($this->db);
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}
	
	/**
   *    \brief Liste les valeurs possibles de type de contacts pour les factures
   *    \param source 'internal' ou 'external'
   *    \return array Tableau des types de contacts
   */
   function liste_type_contact($source)
   {
   	global $langs;
   	$element='commande';
   	$tab = array();
   	
   	$sql = "SELECT distinct tc.rowid, tc.code, tc.libelle";
   	$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
   	$sql.= " WHERE element='".$element."'";
   	$sql.= " AND source='".$source."'";
   	$sql.= " ORDER by tc.code";
   	
   	$resql=$this->db->query($sql);
   	
   	if ($resql)
   	{
   		$num=$this->db->num_rows($resql);
   		$i=0;
   		
   		while ($i < $num)
   		{
   			$obj = $this->db->fetch_object($resql);
   			$transkey="TypeContact_".$element."_".$source."_".$obj->code;
   			$libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
   			$tab[$obj->rowid]=$libelle_type;
   			$i++;
   		}
   		
   		return $tab;
   	 }
   	 else
   	 {
   		 $this->error=$this->db->error();
   		 return null;
   	 }
   	} 


  /** 
   *   \brief Récupère les lignes de contact de l'objet
   *   \param statut Statut des lignes detail à récupérer
   *   \param source Source du contact external (llx_socpeople) ou internal (llx_user)
   *   \return array Tableau des rowid des contacts
   */
   function liste_contact($statut=-1,$source='external')
   {
   	global $langs;
   	$element='commande';
   	$tab=array();
   	
   	$sql = "SELECT ec.rowid, ec.statut, ec.fk_socpeople as id,";
   	if ($source == 'internal') $sql.=" '-1' as socid,";
   	if ($source == 'external') $sql.=" t.fk_soc as socid,";
   	if ($source == 'internal') $sql.=" t.name as nom,";
   	if ($source == 'external') $sql.=" t.name as nom,";
   	$sql.= "tc.source, tc.element, tc.code, tc.libelle";
   	$sql.= " FROM ".MAIN_DB_PREFIX."element_contact ec,";
   	if ($source == 'internal') $sql.=" ".MAIN_DB_PREFIX."user t,";
   	if ($source == 'external') $sql.=" ".MAIN_DB_PREFIX."socpeople t,";
   	$sql.= " ".MAIN_DB_PREFIX."c_type_contact tc";
   	$sql.= " WHERE element_id =".$this->id;
   	$sql.= " AND ec.fk_c_type_contact=tc.rowid";
   	$sql.= " AND tc.element='".$element."'";
   	if ($source == 'internal') $sql.= " AND tc.source = 'internal'";
   	if ($source == 'external') $sql.= " AND tc.source = 'external'";
   	$sql.= " AND tc.active=1";
   	if ($source == 'internal') $sql.= " AND ec.fk_socpeople = t.rowid";
   	if ($source == 'external') $sql.= " AND ec.fk_socpeople = t.idp";
   	if ($statut >= 0) $sql.= " AND statut = '$statut'";
   	$sql.=" ORDER BY t.name ASC";
   	
   	$resql=$this->db->query($sql);
   	
   	if ($resql)
   	{
   		$num=$this->db->num_rows($resql);
   		$i=0;
   		
   		while ($i < $num)
   		{
   			$obj = $this->db->fetch_object($resql);
   			$transkey="TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
   			$libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
   			$tab[$i]=array('source'=>$obj->source,'socid'=>$obj->socid,'id'=>$obj->id,'nom'=>$obj->nom,'rowid'=>$obj->rowid,'code'=>$obj->code,'libelle'=>$libelle_type,'status'=>$obj->statut);
   			$i++;
   		}
   		
   		return $tab;
   	}
   	else
   	{
   		$this->error=$this->db->error();
   		dolibarr_print_error($this->db);
   		return -1;
   	}
   }


  /**
   *   \brief Ajoute un contact associé une commande
   *   \param fk_socpeople Id du contact a ajouter.
   *   \param type_contact Type de contact
   *   \param source extern=Contact externe (llx_socpeople), intern=Contact interne (llx_user)
   *   \return int <0 si erreur, >0 si ok
   */
   function add_contact($fk_socpeople, $type_contact, $source='extern')
   {
   	dolibarr_syslog("Commande::add_contact $fk_socpeople, $type_contact, $source");
   	
   	if ($fk_socpeople <= 0) return -1;
   	
   	// Verifie type_contact
   	if (! $type_contact || ! is_numeric($type_contact)) 
   	{
   		$this->error="Valeur pour type_contact incorrect";
   		return -3;
   	}
   	
   	$datecreate = time();
   	
   	// Insertion dans la base
   	$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
   	$sql.= " (element_id, fk_socpeople, datecreate, statut, fk_c_type_contact) ";
   	$sql.= " VALUES (".$this->id.", ".$fk_socpeople." , " ;
   	$sql.= $this->db->idate($datecreate);
   	$sql.= ", 4, '". $type_contact . "' ";
   	$sql.= ")";
   	
   	// Retour
   	if ( $this->db->query($sql) )
   	{
   		return 1;
   	}
   	else
   	{
   		$this->error=$this->db->error()." - $sql";
   		return -1;
   	}
   } 


  /**
   *   \brief Supprime une ligne de contact
   *   \param rowid La reference du contact
   *   \return statur >0 si ok, <0 si ko
   */
   function delete_contact($rowid)
   {
   	$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
   	$sql.= " WHERE rowid =".$rowid;
   	
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
   *   
   *  
   *  
   */
   function getIdContact($source,$code)
   {
   	$element='commande'; // Contact sur la facture
   	$result=array();
   	$i=0;
   	
   	$sql = "SELECT ec.fk_socpeople";
   	$sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
   	$sql.= " WHERE ec.element_id = ".$this->id;
   	$sql.= " AND ec.fk_c_type_contact=tc.rowid";
   	$sql.= " AND tc.element = '".$element."'";
   	$sql.= " AND tc.source = '".$source."'";
   	$sql.= " AND tc.code = '".$code."'";
   	
   	$resql=$this->db->query($sql);
   	
   	if ($resql)
   	{
   		while ($obj = $this->db->fetch_object($resql))
   		{
   			$result[$i]=$obj->fk_socpeople;
   			$i++;
   		}
   	}
   	else
   	{
   		$this->error=$this->db->error();
   		return null;
   	}
   	
   	return $result;
   } 



	/**
	* Supprime la commande
	*
	*/
	function delete()
	{
		$err = 0;
		$this->db->begin();
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE fk_commande = $this->id ;";
		if (! $this->db->query($sql) )
		{
			$err++;
		}

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commande WHERE rowid = $this->id;";
		if (! $this->db->query($sql) )
		{
			$err++;
		}

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."co_pr WHERE fk_commande = $this->id;";
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
		$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET fk_projet = $cat_id";
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
		global $conf, $user;

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT c.rowid,'.$this->db->pdate('c.date_creation').' as datec';
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ' WHERE c.fk_statut BETWEEN 1 AND 2';
		if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
	 *    	\brief      Retourne le libellé du statut de la commande
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string      Libellé
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$this->facture,$mode);
	}

	/**
	 *		\brief      Renvoi le libellé d'un statut donné
	 *    	\param      statut      Id statut
	 *    	\param      facturee    Si facturee
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string		Libellé
	 */
	function LibStatut($statut,$facturee,$mode)
	{
		global $langs;
		
        if ($mode == 0)
        {
        	if ($statut==-1) return $langs->trans('StatusOrderCanceled');
        	if ($statut==0) return $langs->trans('StatusOrderDraft');
        	if ($statut==1) return $langs->trans('StatusOrderValidated');
        	if ($statut==2) return $langs->trans('StatusOrderOnProcess');
        	if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBill');
        	if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessed');
		}
        if ($mode == 1)
        {
        	if ($statut==-1) return $langs->trans('StatusOrderCanceledShort');
        	if ($statut==0) return $langs->trans('StatusOrderDraftShort');
        	if ($statut==1) return $langs->trans('StatusOrderValidatedShort');
        	if ($statut==2) return $langs->trans('StatusOrderOnProcessShort');
        	if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBillShort');
        	if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessed');
        }
        if ($mode == 2)
        {
        	if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceledShort'),'statut5').' '.$langs->trans('StatusOrderCanceled');
        	if ($statut==0) return img_picto($langs->trans('StatusOrderDraftShort'),'statut0').' '.$langs->trans('StatusOrderDraft');
        	if ($statut==1) return img_picto($langs->trans('StatusOrderValidatedShort'),'statut1').' '.$langs->trans('StatusOrderValidated');
        	if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcessShort'),'statut3').' '.$langs->trans('StatusOrderOnProcess');
        	if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBillShort'),'statut4').' '.$langs->trans('StatusOrderToBill');
        	if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessedShort'),'statut6').' '.$langs->trans('StatusOrderProcessed');
        }
        if ($mode == 3)
        {
        	if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
        	if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
        	if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
        	if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
        	if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut4');
        	if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6');
        }
        if ($mode == 4)
        {
        	if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$langs->trans('StatusOrderCanceled');
        	if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$langs->trans('StatusOrderDraft');
        	if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$langs->trans('StatusOrderValidated');
        	if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3').' '.$langs->trans('StatusOrderOnProcess');
        	if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut4').' '.$langs->trans('StatusOrderToBill');
        	if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$langs->trans('StatusOrderProcessed');
        }
        if ($mode == 5)
        {
        	if ($statut==-1) return $langs->trans('StatusOrderCanceledShort').' '.img_picto($langs->trans('StatusOrderCanceledShort'),'statut5');
        	if ($statut==0) return $langs->trans('StatusOrderDraftShort').' '.img_picto($langs->trans('StatusOrderDraftShort'),'statut0');
        	if ($statut==1) return $langs->trans('StatusOrderValidatedShort').' '.img_picto($langs->trans('StatusOrderValidatedShort'),'statut1');
        	if ($statut==2) return $langs->trans('StatusOrderOnProcessShort').' '.img_picto($langs->trans('StatusOrderOnProcessShort'),'statut3');
        	if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBillShort').' '.img_picto($langs->trans('StatusOrderToBillShort'),'statut4');
        	if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessedShort').' '.img_picto($langs->trans('StatusOrderProcessedShort'),'statut6');
        }
	}


    /**
     *      \brief     Charge les informations d'ordre info dans l'objet commande
     *      \param     id       Id de la commande a charger
     */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('date_creation').' as datec,';
		$sql.= ' '.$this->db->pdate('date_valid').' as datev,';
		$sql.= ' '.$this->db->pdate('date_cloture').' as datecloture,';
		$sql.= ' fk_user_author, fk_user_valid, fk_user_cloture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql.= ' WHERE c.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
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
	// From llx_propaldet
		var $qty;
		var $tva_tx;
		var $subprice;
		var $remise_percent;
		var $price;
		var $product_id;
		var $desc;          // Description ligne
		var $coef;
	
		// From llx_product
		var $libelle;       // Label produit
		var $product_desc;  // Description produit
		var $ref;
	function CommandeLigne()
	{
		
	}
}

?>
