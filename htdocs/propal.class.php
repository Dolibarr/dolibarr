<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2006 Regis Houssin         <regis.houssin@cap-networks.com>
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
	var $element='propal';

    var $id;
    var $socidp;
    var $contactid;
    var $projetidp;
    var $author;
    var $ref;
    var $ref_client;
    var $status;
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

    var $products;
    var $products_qty;

    var $labelstatut=array();
    var $labelstatut_short=array();

    var $product=array();

    // Pour board
    var $nbtodo;
    var $nbtodolate;

    var $specimen;
    var $error;


    /**
     *		\brief      Constructeur
     *      \param      DB          Handler d'accès base
     *      \param      socidp		Id de la société
     *      \param      propalid    Id de la propal
     */
    function Propal($DB, $socidp="", $propalid=0)
    {
      global $langs;

      $this->db = $DB ;
      $this->socidp = $socidp;
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
	 * 	\brief     Ajout d'un produit dans la proposition, en memoire dans l'objet
	 * 	\param     idproduct       	Id du produit à ajouter
	 * 	\param     qty             	Quantité
	 * 	\param     remise_percent  	Remise relative effectuée sur le produit
	 * 	\return    void
	 */
    function add_product($idproduct, $qty, $remise_percent=0)
    {
        if (! $qty) $qty = 1;

        if ($idproduct > 0)
        {
            $i = sizeof($this->products);
            $this->products[$i] = $idproduct;
            $this->products_qty[$i] = $qty;
            $this->products_remise_percent[$i] = $remise_percent;
        }
    }

    /**
     *    \brief     Ajout d'un produit dans la proposition, en base
     *    \param     idproduct           Id du produit à ajouter
     *    \param     qty                 Quantité
     *    \param     remise_percent      Remise relative effectuée sur le produit
     *    \param     p_desc              Descriptif optionnel
     *    \return    int                 >0 si ok, <0 si ko
     *    \see       add_product
     */
    function insert_discount($idproduct, $qty, $remise_percent=0, $desc='')
    {


	}

    /**
     *    	\brief     	Ajout d'un produit dans la proposition, en base
	 * 		\param    	propalid        id de la propale
	 * 		\param    	desc            description de la ligne
	 * 		\param    	pu              prix unitaire
	 * 		\param    	qty             quantité
	 * 		\param    	txtva           taux de tva forcé, sinon -1
	 *		\param    	fk_product      id du produit/service predéfini
	 * 		\param    	remise_percent  pourcentage de remise de la ligne
     *    	\return    	int             >0 si ok, <0 si ko
     *    	\see       	add_product
	 * 		\remarks	Les parametres sont deja censé etre juste et avec valeurs finales a l'appel
	 *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete défini
	 *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
 	 *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    function addline($propalid, $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
    {
    	dolibarr_syslog("propal.class.php::addline $propalid, $desc, $pu, $qty, $txtva, $fk_product, $remise_percent");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

        if ($this->statut == 0)
        {
			$this->db->begin();

			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (! $qty) $qty=1;
            $pu = price2num($pu);
			$txtva = price2num($txtva);

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			// Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
            $price = $pu;
            $subprice = $pu;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }

			// Récupère rang max de la propale dans $rangmax
			$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'propaldet';
			$sql.= ' WHERE fk_propal ='.$propalid;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$row = $this->db->fetch_row($resql);
				$rangmax = $row[0];
			}
			else
			{
				dolibarr_print_error($this->db);
				$this->db->rollback();
				return -1;
			}

			// Insertion ligne
			$ligne=new PropaleLigne($this->db);

			$ligne->fk_propal=$propalid;
			$ligne->desc=$desc;
			$ligne->price=$price;
			$ligne->qty=$qty;
			$ligne->txtva=$txtva;
			$ligne->fk_product=$fk_product;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$subprice;
			$ligne->remise=$remise;
			$ligne->rang=($rangmax+1);
			$ligne->info_bits=$info_bits;
			$ligne->total_ht=$total_ht;
			$ligne->total_tva=$total_tva;
			$ligne->total_ttc=$total_ttc;

			$result=$ligne->insert();			
			if ($result > 0)
            {
				// Mise a jour informations denormalisees au niveau de la facture meme
				$result=$this->update_price($facid);

                if ($result > 0)
                {
					$this->db->commit();
					return 1;
                }
                else
                {
	            	$this->error=$this->db->error();
	            	dolibarr_syslog("Error sql=$sql, error=".$this->error);
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
   *    \brief      Mise à jour d'une ligne de produit
   *    \param      id              Id de la ligne
   *    \param      subprice        Prix unitaire
   *    \param      qty             Quantité
   *    \param      remise_percent  Remise effectuée sur le produit
   *    \param      tva_tx          Taux de TVA
   *    \param      desc            Description
   *    \return     int             0 en cas de succès
   */
    function updateline($rowid, $pu, $qty, $remise_percent=0, $txtva, $desc='')
    {
    	dolibarr_syslog("propal.class.php::updateligne $rowid, $pu, $qty, $remise_percent, $txtva, $desc");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

        if ($this->statut == 0)
        {
			$this->db->begin();

			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (! $qty) $qty=1;
            $pu = price2num($pu);
			$txtva = price2num($txtva);
			
			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			// Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
            $price = $pu;
            $subprice = $pu;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }
            
            $sql = "UPDATE ".MAIN_DB_PREFIX."propaldet ";
            $sql.= " SET qty='".$qty."'";
            $sql.= " , price='". price2num($price)."'";
            $sql.= " , remise_percent='".$remise_percent."'";
            $sql.= " , subprice='".$subprice."'";
            $sql.= " , tva_tx='".$txtva."'";
            $sql.= " , description='".addslashes($desc)."'";
            $sql.= " , total_ht='".price2num($total_ht)."'";
            $sql.= " , total_tva='".price2num($total_tva)."'";
            $sql.= " , total_ttc='".price2num($total_ttc)."'";
            $sql.= " WHERE rowid = '".$rowid."';";

            $result=$this->db->query($sql);
            if ($result > 0)
            {
                $this->update_price();
				$this->db->commit();
                return 0;
            }
            else
            {
                $this->error=$this->db->error();
				$this->db->rollback();
                dolibarr_syslog("Propal::UpdateLigne Erreur sql=$sql, error=".$this->error);
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
    function create($user='')
    {
    	global $langs,$conf,$mysoc;
        
        // Nettoyage/définition paramètres
        $this->fin_validite = $this->datep + ($this->duree_validite * 24 * 3600);

		dolibarr_syslog("Propal::create ref=".$this->ref);

		$soc = new Societe($this->db);
		$soc->fetch($this->socidp);

        $this->db->begin();

        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal (fk_soc, price, remise, remise_percent, remise_absolue,";
        $sql.= " tva, total, datep, datec, ref, fk_user_author, note, note_public, model_pdf, fin_validite,";
        $sql.= " fk_cond_reglement, fk_mode_reglement, date_livraison, ref_client) ";
        $sql.= " VALUES ($this->socidp, 0, $this->remise, $this->remise_percent, $this->remise_absolue,";
        $sql.= " 0,0,".$this->db->idate($this->datep).", now(), '".$this->ref."', ".$this->author.",";
        $sql.= "'".addslashes($this->note)."',";
        $sql.= "'".addslashes($this->note_public)."',";
        $sql.= "'$this->modelpdf',".$this->db->idate($this->fin_validite).",";
        $sql.= " $this->cond_reglement_id, $this->mode_reglement_id,";
        $sql.= " ".$this->db->idate($this->date_livraison).",'".addslashes($this->ref_client)."')";

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
					if ($prod->fetch($this->products[$i]))
					{
						$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);
						// multiprix
						if($conf->global->PRODUIT_MULTIPRICES == 1)
							$price = $prod->multiprices[$soc->price_level];
						else
							$price = $prod->price;

						$resql = $this->addline(
							$this->id,
							$prod->description,
							$price,
							$this->products_qty[$i],
							$tva_tx,
							$this->products[$i],
							$this->products_remise_percent[$i],
							$this->products_date_start[$i],
							$this->products_date_end[$i]
							);
							
						if ($resql < 0)
						{
							$this->error=$this->db->error;
							dolibarr_print_error($this->db);
							break;
						}
					}
                }

                // Affectation au projet
                if ($resql && $this->projetidp)
                {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_projet=$this->projetidp WHERE ref='$this->ref'";
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
   					// Mise a jour infos dénormalisés
	                $resql=$this->update_price();
	                if ($resql)
	                {
	                    // Appel des triggers
	                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	                    $interface=new Interfaces($this->db);
	                    $result=$interface->run_triggers('PROPAL_CREATE',$this,$user,$langs,$conf);
	                    // Fin appel triggers
	
	                    $this->db->commit();
			            dolibarr_syslog("Propal::Create done id=".$this->id);
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
     *    \brief      Mets à jour le prix total de la proposition
     *    \return     int     <0 si ko, >0 si ok
     */
    function update_price()
    {
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

        // Liste des lignes factures a sommer
        $sql = "SELECT price, qty, tva_tx, total_ht, total_tva, total_ttc";
        $sql.= " FROM ".MAIN_DB_PREFIX."propaldet";
        $sql.= " WHERE fk_propal = ".$this->id;
		$result = $this->db->query($sql);
		if ($result)
        {
			$this->total_ht  = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);

				$this->total_ht    += $obj->total_ht;
				$this->total_tva   += ($obj->total_ttc - $obj->total_ht);
				$this->total_ttc   += $obj->total_ttc;

				// Anciens indicateurs
				$this->amount_ht      += $obj->price * $obj->qty;
				$this->total_remise   += 0;		// Plus de remise globale (toute remise est sur une ligne)
/* \deprecated car simplifie par les 3 indicateurs total_ht, total_tva et total_ttc sur lignes
                $products[$i][0] = $obj->price;
                $products[$i][1] = $obj->qty;
                $products[$i][2] = $obj->tva_tx;
*/
                $i++;
            }

			$this->db->free($result);
        }
/* \deprecated car simplifie par les 3 indicateurs total_ht, total_tva et total_ttc sur lignes
        $calculs = calcul_price($products, $this->remise_percent, $this->remise_absolue);
        $this->total_remise   = $calculs[3];
		$this->amount_ht      = $calculs[4];
        $this->total_ht       = $calculs[0];
        $this->total_tva      = $calculs[1];
        $this->total_ttc      = $calculs[2];
		$tvas                 = $calculs[5];
*/
        // Met a jour en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET";
        $sql .= " price='".  price2num($this->total_ht)."'";
        $sql .= ", tva='".   price2num($this->total_tva)."'";
        $sql .= ", total='". price2num($this->total_ttc)."'";
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
	 *      \brief      Stocke un numéro de rang pour toutes les lignes de
	 *                  detail d'une propale qui n'en ont pas.
	 */
	function line_order()
	{
		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'propaldet';
		$sql .= ' WHERE fk_propal='.$this->id;
		$sql .= ' AND rang = 0';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		if ($nl > 0)
		{
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'propaldet';
			$sql .= ' WHERE fk_propal='.$this->id;
			$sql .= ' ORDER BY rang ASC, rowid ASC';
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					$li[$i] = $row[0];
					$i++;
				}
			}
			for ($i = 0 ; $i < sizeof($li) ; $i++)
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'propaldet SET rang = '.($i+1);
				$sql .= ' WHERE rowid = '.$li[$i];
				if (!$this->db->query($sql) )
				{
					dolibarr_syslog($this->db->error());
				}
			}
		}
	}

	function line_up($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'propaldet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		if ($rang > 1 )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'propaldet SET rang = '.$rang ;
			$sql .= ' WHERE fk_propal  = '.$this->id;
			$sql .= ' AND rang = '.($rang - 1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'propaldet SET rang  = '.($rang - 1);
				$sql .= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dolibarr_print_error($this->db);
				}
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

	function line_down($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'propaldet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		/* Lecture du rang max de la propale */
		$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'propaldet';
		$sql .= ' WHERE fk_propal ='.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$max = $row[0];
		}

		if ($rang < $max )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'propaldet SET rang = '.$rang;
			$sql .= ' WHERE fk_propal  = '.$this->id;
			$sql .= ' AND rang = '.($rang+1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'propaldet SET rang = '.($rang+1);
				$sql .= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dolibarr_print_error($this->db);
				}
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

    /**
     *    	\brief      Recupère de la base les caractéristiques d'une propale
     *    	\param      rowid       id de la propal à récupérer
     *		\return		int			<0 si ko, 0 si non trouvé, >0 si ok
     */
    function fetch($rowid)
    {
        $sql = "SELECT rowid,ref,total,price,remise,remise_percent,remise_absolue,tva,fk_soc,fk_soc_contact";
        $sql.= ", ".$this->db->pdate("datep")." as dp";
        $sql.= ", ".$this->db->pdate("fin_validite")." as dfv";
        $sql.= ", ".$this->db->pdate("date_livraison")." as date_livraison";
        $sql.= ", model_pdf, ref_client";
        $sql.= ", note, note_public";
        $sql.= ", fk_projet, fk_statut, fk_user_author";
        $sql.= ", fk_cond_reglement, fk_mode_reglement, fk_adresse_livraison";
        $sql.= ", c.label as statut_label";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal,";
        $sql.= " ".MAIN_DB_PREFIX."c_propalst as c";
        $sql.= " WHERE fk_statut = c.id";
        $sql.= " AND rowid='".$rowid."'";

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
                $this->price                = $obj->price;
                $this->remise               = $obj->remise;
                $this->remise_percent       = $obj->remise_percent;
                $this->remise_absolue       = $obj->remise_absolue;
                $this->total                = $obj->total;
                $this->total_ht             = $obj->price;
                $this->total_tva            = $obj->tva;
                $this->total_ttc            = $obj->total;
                $this->socidp               = $obj->fk_soc;
                $this->projetidp            = $obj->fk_projet;
                $this->contactid            = $obj->fk_soc_contact;
                $this->modelpdf             = $obj->model_pdf;
                $this->note                 = $obj->note;
                $this->note_public          = $obj->note_public;
                $this->statut               = $obj->fk_statut;
                $this->statut_libelle       = $obj->statut_label;
                $this->cond_reglement_id    = $obj->fk_cond_reglement;
                $this->mode_reglement_id    = $obj->fk_mode_reglement;
		        $this->date_livraison       = $obj->date_livraison;
		        $this->adresse_livraison_id = $obj->fk_adresse_livraison;

                $this->user_author_id = $obj->fk_user_author;

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

                if ($obj->fk_statut == 0)
                {
                    $this->brouillon = 1;
                }

                $this->lignes = array();
                $this->db->free($resql);

                $this->ref_url = '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$this->id.'">'.$this->ref.'</a>';

                /*
                 * Lignes propales liées à un produit ou non
                 */
                $sql = "SELECT d.description, d.price, d.tva_tx, d.qty, d.remise_percent, d.subprice, d.fk_product,";
                $sql.= " d.total_ht, d.total_tva, d.total_ttc,";
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
                        $objp                  = $this->db->fetch_object($result);

                        $ligne                 = new PropaleLigne($this->db);

                        $ligne->desc           = $objp->description;  // Description ligne
                        $ligne->qty            = $objp->qty;
                        $ligne->tva_tx         = $objp->tva_tx;
                        $ligne->subprice       = $objp->subprice;
                        $ligne->remise_percent = $objp->remise_percent;
                        $ligne->price          = $objp->price;

                        $ligne->total_ht       = $objp->total_ht;
                        $ligne->total_tva      = $objp->total_tva;
                        $ligne->total_ttc      = $objp->total_ttc;

                        $ligne->product_id     = $objp->fk_product;
                        $ligne->coef           = $objp->coef;

                        $ligne->libelle        = $objp->label;        // Label produit
                        $ligne->product_desc   = $objp->product_desc; // Description produit
                        $ligne->ref            = $objp->ref;

                        $this->lignes[$i]      = $ligne;
                        //dolibarr_syslog("1 ".$ligne->desc);
                        //print "xx $i ".$this->lignes[$i]->libelle;
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
            return 0;
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
     *      \brief      Définit la date de fin de validité
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
     *      \brief      Définit une date de livraison
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
     *      \brief      Définit une adresse de livraison
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
     *      \brief      Définit une remise globale relative sur la proposition
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
     *      \brief      Définit une remise globale absolue sur la proposition
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

	/**
	 *		\brief		Positionne modele derniere generation
	 *		\param		user		Objet use qui modifie
	 *		\param		modelpdf	Nom du modele
	 */
	function set_pdf_model($user, $modelpdf)
	{
		if ($user->rights->propale->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."propal";
			$sql.= " SET model_pdf = '".$modelpdf."'";
			$sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->modelpdf=$modelpdf;
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
	 *        \brief      Classe la propale comme facturée
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
     *    	\brief      Renvoie un tableau contenant les numéros de factures associées
     *		\return		array		Tableau des id de factures
     */
    function getFactureListeArray ()
    {
		return $this->FactureListeArray($this->id);
	}

    /**
     *    	\brief      Renvoie un tableau contenant les numéros de factures associées
     *		\param		id			Id propal
     *		\return		array		Tableau des id de factures
     */
    function FactureListeArray($id)
    {
        $ga = array();

        $sql = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."fa_pr as fp";
        $sql .= " WHERE fk_propal = " . $id;
        if ($this->db->query($sql))
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

	    // On efface le répertoire du pdf
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
 	 *    \brief      Mets à jour les commentaires privés
	 *    \param      note        	Commentaire
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note($note)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal';
		$sql.= " SET note = '".addslashes($note)."'";
		$sql.= " WHERE rowid =". $this->id;

		dolibarr_syslog("Propal::update_note $sql");

		if ($this->db->query($sql))
		{
			$this->note = $note;
			return 1;
		}
		else
		{
            $this->error=$this->db->error();
			return -1;
		}
	}

	/**
 	 *    \brief      Mets à jour les commentaires publiques
	 *    \param      note_public	Commentaire
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note_public($note_public)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'propal';
		$sql.= " SET note_public = '".addslashes($note_public)."'";
		$sql.= " WHERE rowid =". $this->id;

		dolibarr_syslog("Propal::update_note_public $sql");

		if ($this->db->query($sql))
		{
			$this->note_public = $note_public;
			return 1;
		}
		else
		{
            $this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *   \brief      Change les conditions de réglement de la facture
	 *   \param      cond_reglement_id      Id de la nouvelle condition de réglement
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
	 *   \brief      Change le mode de réglement
	 *   \param      mode        Id du nouveau mode
	 *   \return     int         >0 si ok, <0 si ko
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
	 *    	\brief      Retourne le libellé du statut d'une propale (brouillon, validée, ...)
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string		Libellé
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libellé d'un statut donné
	 *    	\param      statut		id statut
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string		Libellé
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
     *      \param      mode        "opened" pour propal à fermer, "signed" pour propale à facturer
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_board($user,$mode)
    {
        global $conf, $user;

        $this->nbtodo=$this->nbtodolate=0;
        $sql ="SELECT p.rowid, p.ref, ".$this->db->pdate("p.datec")." as datec,".$this->db->pdate("p.fin_validite")." as datefin";
        if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.=" FROM ".MAIN_DB_PREFIX."propal as p";
        if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        if ($mode == 'opened') $sql.=" WHERE p.fk_statut = 1";
        if ($mode == 'signed') $sql.=" WHERE p.fk_statut = 2";
        if ($user->societe_id) $sql.=" AND p.fk_soc = ".$user->societe_id;
        if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
// \todo Definir règle des propales à facturer en retard
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
     *    \brief     Charge l'objet propal en mémoire avec propal dont l'id est passé en argument
     *    \param     $src_propal_id      Id de la propal source
     *    \return    int                 >0 si ok, <0 si ko
     *    \see       fetch
     */
	function load_from($src_propal_id)
	{
		$src_propal = new Propal($this->db);
		if($src_propal->fetch($src_propal_id) < 0)
		{
			dolibarr_syslog("Propal::load_from: Erreur de lecture de la propal source.");
            return -1;
		}
		$this->datep             = $src_propal->datep;
		$this->fin_validite      = $src_propal->fin_validite;
		$this->date              = $src_propal->date;
        $this->ref               = $src_propal->ref;
        $this->price             = $src_propal->price;
        $this->remise            = $src_propal->remise;
        $this->remise_percent    = $src_propal->remise_percent;
        $this->remise_absolue    = $src_propal->remise_absolue;
        $this->total             = $src_propal->total;
        $this->total_ht          = $src_propal->total_ht;
        $this->total_tva         = $src_propal->total_tva;
        $this->total_ttc         = $src_propal->total_ttc;
        $this->socidp            = $src_propal->socidp;
        $this->projetidp         = $src_propal->projetidp;
        $this->contactid         = $src_propal->contactid;
        $this->modelpdf          = $src_propal->modelpdf;
        $this->note              = $src_propal->note;
        $this->note_public       = $src_propal->note_public;
        $this->statut            = $src_propal->statut;
        $this->statut_libelle    = $src_propal->statut_libelle;
        $this->cond_reglement_id = $src_propal->cond_reglement_id;
        $this->mode_reglement_id = $src_propal->mode_reglement_id;
		$this->date_livraison 	 = $src_propal->date_livraison;
		$this->adresse_livraison_id  = $src_propal->adresse_livraison_id;
		$this->user_author_id    = $src_propal->user_author_id;

        $this->cond_reglement    = $src_propal->cond_reglement;
        $this->cond_reglement_code=$src_propal->cond_reglement_code;
        $this->ref_url 			 = $src_propal->ref_url;
        $this->lignes 			 = $src_propal->lignes;
        return 1;
	}

	/**
     *    \brief     Insert en base un objet propal complétement définie par ses données membres (resultant d'une copie par exemple).
     *    \return    int                 l'id du nouvel objet propal en base si ok, <0 si ko
     *    \see       create
     */
	function create_from()
	{

        $this->db->begin();

        $this->fin_validite = $this->datep + ($this->duree_validite * 24 * 3600);

        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal (fk_soc, fk_soc_contact, price, remise, remise_percent, remise_absolue,";
        $sql.= " tva, total, datep, datec, ref, fk_user_author, note, note_public, model_pdf, fin_validite, fk_cond_reglement, fk_mode_reglement, date_livraison, fk_adresse_livraison) ";
        $sql.= " VALUES ('$this->socidp', '$this->contactid', '0', '$this->remise', '$this->remise_percent', '$this->remise_absolue',";
        $sql.= " '0','0','".$this->db->idate($this->datep)."', now(), '$this->ref', '$this->author',";
        $sql.= "'".addslashes($this->note)."',";
        $sql.= "'".addslashes($this->note_public)."',";
        $sql.= "'$this->modelpdf','".$this->db->idate($this->fin_validite)."',";
        $sql.= " '$this->cond_reglement_id', '$this->mode_reglement_id', '".$this->db->idate($this->date_livraison)."', '$this->adresse_livraison_id')";
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
						$prod->description,
						$price,
						$ligne->qty,
						$tva_tx,
						$ligne->product_id,
						$ligne->remise_percent
						);

					if ($resql < 0)
					{
						$this->error=$this->db->error;
						dolibarr_print_error($this->db);
						break;
					}
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
                    dolibarr_syslog("Propal::Create_from -2 ".$this->error);
                    $this->db->rollback();
                    return -2;
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

        return $this->id;
	}


	/**
	 *		\brief		Initialise la propale avec valeurs fictives aléatoire
	 *					Sert à générer une facture pour l'aperu des modèles ou demo
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe WHERE client=1 LIMIT 10";
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

		// Initialise paramètres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socidp = $socids[$socid];
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
			$ligne->tva_taux=19.6;
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
    var $desc;          	// Description ligne
    var $product_id;		// Id produit prédéfini

    var $qty;
    var $tva_tx;
    var $subprice;
    var $remise;
    var $remise_percent;
    var $price;
	var $rang;
	var $coef;

	var $info_bits;			// Bit 0: 	0 si TVA normal - 1 si TVA NPR
	var $total_ht;			// Total HT  de la ligne toute quantité et incluant la remise ligne
	var $total_tva;			// Total TVA  de la ligne toute quantité et incluant la remise ligne
	var $total_ttc;			// Total TTC de la ligne toute quantité et incluant la remise ligne

    // From llx_product
    var $ref;				// Reference produit
    var $libelle;       	// Label produit
    var $product_desc;  	// Description produit


	/**
	 *      \brief     Constructeur d'objets ligne de propal
	 *      \param     DB      handler d'accès base de donnée
	 */
	function PropaleLigne($DB)
	{
		$this->db= $DB ;
	}

	/**
	 *      \brief     Recupére l'objet ligne de propal
	 *      \param     rowid           id de la ligne de propal
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT pd.rowid, pd.fk_propal, pd.fk_product, pd.description, pd.price, pd.qty, pd.tva_tx,';
		$sql.= ' pd.remise, pd.remise_percent, pd.fk_remise_except, pd.subprice,';
		$sql.= ' pd.info_bits, pd.total_ht, pd.total_tva, pd.total_ttc, pd.coef, pd.rang,';
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
			$this->coef           = $objp->coef;
			$this->rang           = $objp->rang;

			$this->ref			  = $objp->product_ref;
			$this->libelle		  = $objp->product_libelle;
			$this->product_desc	  = $objp->product_desc;

			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}
	
	/**
	 *      \brief     	Insère l'objet ligne de propal en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function insert()
	{
		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'propaldet';
		$sql.= ' (fk_propal, description, price, qty, tva_tx,';
		$sql.= ' fk_product, remise_percent, subprice, remise, fk_remise_except, ';
		$sql.= ' rang, coef,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc)';
		$sql.= " VALUES (".$this->fk_propal.",";
		$sql.= " '".addslashes($this->desc)."',";
		$sql.= " '".price2num($this->price)."',";
		$sql.= " '".price2num($this->qty)."',";
		$sql.= " '".price2num($this->tva_tx)."',";
		if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
		else { $sql.='0,'; }
		$sql.= " '".price2num($this->remise_percent)."',";
		$sql.= " '".price2num($this->subprice)."',";
		$sql.= " '".price2num($this->remise)."',";
		if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
		else $sql.= 'null,';
		$sql.= ' '.$this->rang.',';
		if (isset($this->coef)) $sql.= ' '.$this->coef.',';
		else $sql.= ' null,';
		$sql.= " '".$this->info_bits."',";
		$sql.= " '".price2num($this->total_ht)."',";
		$sql.= " '".price2num($this->total_tva)."',";
		$sql.= " '".price2num($this->total_ttc)."'";
		$sql.= ')';

       	dolibarr_syslog("PropaleLigne::insert sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("PropaleLigne::insert Error ".$this->error);
			$this->db->rollback();
            return -2;
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
		$sql.= ",price='".price2num($this->price)."'";
		$sql.= ",subprice='".price2num($this->subprice)."'";
		$sql.= ",remise='".price2num($this->remise)."'";
		$sql.= ",remise_percent='".price2num($this->remise_percent)."'";
		if ($fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
		else $sql.= ",fk_remise_except=null";
		$sql.= ",tva_tx='".price2num($this->tva_tx)."'";
		$sql.= ",qty='".price2num($this->qty)."'";
		$sql.= ",rang='".$this->rang."'";
		$sql.= ",coef='".$this->coef."'";
		$sql.= ",info_bits='".$this->info_bits."'";
		$sql.= ",total_ht='".price2num($this->total_ht)."'";
		$sql.= ",total_tva='".price2num($this->total_tva)."'";
		$sql.= ",total_ttc='".price2num($this->total_ttc)."'";
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
		
}

?>
