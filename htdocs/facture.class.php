<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
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
        \file       htdocs/facture.class.php
        \ingroup    facture
        \brief      Fichier de la classe des factures clients
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT .'/notify.class.php');
require_once(DOL_DOCUMENT_ROOT ."/product.class.php");


/**
	\class      Facture
	\brief      Classe permettant la gestion des factures clients
*/

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
	var $note_public;
	var $paye;
	var $propalid;
	var $projetid;
	var $date_lim_reglement;
	var $cond_reglement_id;
	var $cond_reglement_code;
	var $mode_reglement_id;
	var $mode_reglement_code;

	/**
	*    \brief  Constructeur de la classe
	*    \param  DB          handler accès base de données
	*    \param  soc_idp     id societe ('' par defaut)
	*    \param  facid       id facture ('' par defaut)
	*/
	function Facture($DB, $soc_idp='', $facid='')
	{
		$this->db = $DB ;

		$this->id = $facid;
		$this->socidp = $soc_idp;

		$this->amount = 0;
		$this->remise = 0;
		$this->remise_percent = 0;
		$this->tva = 0;
		$this->total = 0;
		$this->propalid = 0;
		$this->projetid = 0;
		$this->remise_exceptionnelle = 0;

		$this->products = array();        // Tableau de lignes de factures
	}

	/**
	 *    \brief      Création de la facture en base
	 *    \param      user       object utilisateur qui crée
	 */
	function create($user)
	{
		global $langs,$conf;

		$this->db->begin();

		// Nettoyage paramètres
		$this->note=trim($this->note);
		$this->note_public=trim($this->note_public);
		if (! $this->remise) $this->remise = 0 ;
		if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;

		// On positionne en mode brouillon la facture
		$this->brouillon = 1;

		dolibarr_syslog("Facture::create");

		// Facture récurrente
		if ($this->fac_rec > 0)
		{
			require_once DOL_DOCUMENT_ROOT . '/compta/facture/facture-rec.class.php';
			$_facrec = new FactureRec($this->db, $this->fac_rec);
			$_facrec->fetch($this->fac_rec);

			$this->projetid          = $_facrec->projetid;
			$this->cond_reglement    = $_facrec->cond_reglement_id;
			$this->cond_reglement_id = $_facrec->cond_reglement_id;
			$this->mode_reglement    = $_facrec->mode_reglement_id;
			$this->mode_reglement_id = $_facrec->mode_reglement_id;
			$this->amount            = $_facrec->amount;
			$this->remise_absolue    = $_facrec->remise_absolue;
			$this->remise_percent    = $_facrec->remise_percent;
			$this->remise			 = $_facrec->remise;
		}

		// Definition de la date limite
		$datelim=$this->calculate_date_lim_reglement();

		// Insertion dans la base
		$socid  = $this->socidp;
		$number = $this->number;
		$amount = $this->amount;
		$remise = $this->remise;

		$totalht = ($amount - $remise);

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture (';
		$sql.= ' facnumber, fk_soc, datec, amount, remise_absolue, remise_percent,';
		$sql.= ' datef,';
		$sql.= ' note,';
		$sql.= ' note_public,';
		$sql.= ' fk_user_author, fk_projet,';
		$sql.= ' fk_cond_reglement, fk_mode_reglement, date_lim_reglement, ref_client) ';
		$sql.= " VALUES (";
		$sql.= "'$number','$socid', now(), '$totalht', '".$this->remise_absolue."'";
		$sql.= ",'".$this->remise_percent."', ".$this->db->idate($this->date);
		$sql.= ",".($this->note?"'".addslashes($this->note)."'":"null");
		$sql.= ",".($this->note_public?"'".addslashes($this->note_public)."'":"null");
		$sql.= ",".$user->id;
		$sql.= ",".($this->projetid?$this->projetid:"null");
		$sql.= ','.$this->cond_reglement_id;
		$sql.= ",".$this->mode_reglement_id;
		$sql.= ','.$this->db->idate($datelim);
		$sql.= ", '".$this->ref_client."')";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture');

			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET facnumber='(PROV".$this->id.")' WHERE rowid=".$this->id;
			$resql=$this->db->query($sql);

			if ($resql && $this->id && $this->propalid)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fa_pr (fk_facture, fk_propal) VALUES ('.$this->id.','.$this->propalid.')';
				$resql=$this->db->query($sql);
			}
			if ($resql && $this->id && $this->commandeid)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_fa (fk_facture, fk_commande) VALUES ('.$this->id.','.$this->commandeid.')';
				$resql=$this->db->query($sql);
			}

			/*
 			 * Produits/services
			 */
			for ($i = 0 ; $i < sizeof($this->products) ; $i++)
			{
				$prod = new Product($this->db, $this->products[$i]);
				$res=$prod->fetch($this->products[$i]);
				$soc = new Societe($this->db);
				$soc->fetch($this->socidp);
				if($soc->tva_assuj == "0")
						$tva_tx ="0";
				else
						$tva_tx=$prod->tva_tx;
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES == 1)
					$price = $prod->multiprices[$soc->price_level];
				else
					$price = $prod->price;

				$resql = $this->addline(
					$this->id,
					$prod->libelle,
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

			/*
			 * Produits de la facture récurrente
 			 */
			if ($resql && $this->fac_rec > 0)
			{
				for ($i = 0 ; $i < sizeof($_facrec->lignes) ; $i++)
				{
					if ($_facrec->lignes[$i]->produit_id)
					{
						$prod = new Product($this->db, $_facrec->lignes[$i]->produit_id);
						$prod->fetch($_facrec->lignes[$i]->produit_id);
					}

					$result_insert = $this->addline(
						$this->id,
						$_facrec->lignes[$i]->desc,
						$_facrec->lignes[$i]->subprice,
						$_facrec->lignes[$i]->qty,
						$tva_tx,
						$_facrec->lignes[$i]->produit_id,
						$_facrec->lignes[$i]->remise_percent);

					if ( $result_insert < 0)
					{
						dolibarr_print_error($this->db);
					}
				}
			}

            if ($resql)
            {
	            $resql=$this->updateprice($this->id);
	            if ($resql)
	            {
	                // Appel des triggers
	                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	                $interface=new Interfaces($this->db);
	                $result=$interface->run_triggers('BILL_CREATE',$this,$user,$langs,$conf);
	                // Fin appel triggers
	    
	                $this->db->commit();
	                return $this->id;
	            }
	            else
	            {
	                $this->db->rollback();
	                return -3;
	            }
			}
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Facture::create error ".$this->error." sql=".$sql);
            $this->db->rollback();
            return -1;
        }
    }


	/**
	*    \brief      Recupére l'objet facture et ses lignes de factures
	*    \param      rowid       id de la facture a récupérer
	*    \param      societe_id  id de societe
	*    \return     int         1 si ok, < 0 si erreur
	*/
	function fetch($rowid, $societe_id=0)
	{
		//dolibarr_syslog("Facture::Fetch rowid : $rowid, societe_id : $societe_id");

		$sql = 'SELECT f.fk_soc,f.facnumber,f.amount,f.tva,f.total,f.total_ttc,f.remise_percent,f.remise_absolue,f.remise';
		$sql .= ','.$this->db->pdate('f.datef').' as df, f.fk_projet';
		$sql .= ','.$this->db->pdate('f.date_lim_reglement').' as dlr';
		$sql .= ', f.note, f.note_public, f.paye, f.fk_statut, f.fk_user_author, f.model_pdf';
		$sql .= ', f.fk_mode_reglement, f.ref_client, p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql .= ', f.fk_cond_reglement, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_facture';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'cond_reglement as c, '.MAIN_DB_PREFIX.'facture as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql .= ' WHERE f.rowid='.$rowid.' AND c.rowid = f.fk_cond_reglement';
		if ($societe_id > 0)
		{
			$sql .= ' AND f.fk_soc = '.$societe_id;
		}
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				//print strftime('%Y%m%d%H%M%S',$obj->df).' '.$obj->df.' '.dolibarr_print_date($obj->df);

				$this->id                     = $rowid;
				$this->datep                  = $obj->dp;
				$this->date                   = $obj->df;
				$this->ref                    = $obj->facnumber;
				$this->ref_client             = $obj->ref_client;
				$this->amount                 = $obj->amount;
				$this->remise_percent         = $obj->remise_percent;
				$this->remise_absolue         = $obj->remise_absolue;
				$this->remise                 = $obj->remise;
				$this->total_ht               = $obj->total;
				$this->total_tva              = $obj->tva;
				$this->total_ttc              = $obj->total_ttc;
				$this->paye                   = $obj->paye;
				$this->socidp                 = $obj->fk_soc;
				$this->statut                 = $obj->fk_statut;
				$this->date_lim_reglement     = $obj->dlr;
				$this->mode_reglement_id      = $obj->fk_mode_reglement;
				$this->mode_reglement_code    = $obj->mode_reglement_code;
				$this->mode_reglement         = $obj->mode_reglement_libelle;
				$this->cond_reglement_id      = $obj->fk_cond_reglement;
				$this->cond_reglement         = $obj->cond_reglement_libelle;
				$this->cond_reglement_facture = $obj->cond_reglement_libelle_facture;
				$this->projetid               = $obj->fk_projet;
				$this->note                   = $obj->note;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->lignes                 = array();

				if ($this->statut == 0)
				{
					$this->brouillon = 1;
				}

				/*
				 * Lignes
				 */
				$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.remise, l.remise_percent, l.subprice, '.$this->db->pdate('l.date_start').' as date_start,'.$this->db->pdate('l.date_end').' as date_end,';
				$sql.= ' p.label as label, p.description as product_desc';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
				$sql.= ' WHERE l.fk_facture = '.$this->id;
				$sql.= ' ORDER BY l.rang';
				$result2 = $this->db->query($sql);
				if ($result2)
				{
					$num = $this->db->num_rows($result2);
					$i = 0; $total = 0;
					while ($i < $num)
					{
						$objp = $this->db->fetch_object($result2);
						$faclig = new FactureLigne($this->db);
						$faclig->desc           = stripslashes($objp->description);     // Description ligne
						$faclig->libelle        = stripslashes($objp->label);           // Label produit
						$faclig->product_desc   = stripslashes($objp->product_desc);    // Description produit
						$faclig->qty            = $objp->qty;
						$faclig->price          = $objp->price;
						$faclig->subprice       = $objp->subprice;
						$faclig->tva_taux       = $objp->tva_taux;
						$faclig->remise         = $objp->remise;
						$faclig->remise_percent = $objp->remise_percent;
						$faclig->produit_id     = $objp->fk_product;
						$faclig->date_start     = $objp->date_start;
						$faclig->date_end       = $objp->date_end;
						$this->lignes[$i] = $faclig;
						$i++;
					}
					$this->db->free($result2);
					$this->db->free($result);
					return 1;
				}
				else
				{
					dolibarr_syslog('Erreur Facture::Fetch rowid='.$rowid.', Erreur dans fetch des lignes');
					$this->error=$this->db->error();
					return -3;
				}
			}
			else
			{
				dolibarr_syslog('Erreur Facture::Fetch rowid='.$rowid.' numrows=0 sql='.$sql);
				$this->error='Bill with id '.$rowid.' not found sql='.$sql;
				return -2;
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_syslog('Erreur Facture::Fetch rowid='.$rowid.' Erreur dans fetch de la facture');
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	* \brief     Recupére l'objet client lié à la facture
	*
	*/
	function fetch_client()
	{
		$client = new Societe($this->db);
		$client->fetch($this->socidp);
		$this->client = $client;
	}


	/**
	 * 		\brief     	Valide la facture
	 * 		\param     	userid      	Id de l'utilisateur qui valide
	 *		\return		int				<0 si ko, >0 si ok
	 *		\remarks	Utiliser set_valid directement plutot que cette methode
	 *		\deprecated
	 */
	function valid($userid)
	{
		$user=new User($this->db);
		$user->fetch($userid);
		
		$soc=new Societe($this->db);
		$soc->fetch($this->socidp);
		
		return set_valid($this->rowid,$user,$soc,'');
	}


	/**
	 *      \brief     Classe la facture dans un projet
	 *      \param     projid       Id du projet dans lequel classer la facture
	 */
	function classin($projid)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		if ($projid) $sql.= ' SET fk_projet = '.$projid;
		else $sql.= ' SET fk_projet = NULL';
		$sql.= ' WHERE rowid = '.$this->id;
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}

	function set_ref_client($ref_client)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		if (empty($ref_client))
			$sql .= ' SET ref_client = NULL';
		else
			$sql .= ' SET ref_client = \''.$ref_client.'\'';
		$sql .= ' WHERE rowid = '.$this->id;
		if ($this->db->query($sql))
		{
			$this->ref_client = $ref_client;
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 		\brief     Supprime la facture
	 * 		\param     rowid      id de la facture à supprimer
	 */
	function delete($rowid)
	{
		global $user,$langs,$conf;
		
        $this->db->begin();
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture = '.$rowid;

		if ( $this->db->query( $sql) )
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'fa_pr WHERE fk_facture = '.$rowid;
			if ($this->db->query( $sql) )
			{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'co_fa WHERE fk_facture = '.$rowid;
				if ($this->db->query( $sql) )
				{
					$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$rowid;
					if ($this->db->query( $sql) )
					{
						// On désaffecte de la facture les remises liées
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
						$sql.= ' SET fk_facture = NULL WHERE fk_facture = '.$rowid;
						if ($this->db->query( $sql) )
						{
							$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture WHERE rowid = '.$rowid.' AND fk_statut = 0';
                            $resql=$this->db->query($sql) ;

                            if ($resql)
                            {
                                // Appel des triggers
                                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                                $interface=new Interfaces($this->db);
                                $result=$interface->run_triggers('BILL_DELETE',$this,$user,$langs,$conf);
                                // Fin appel triggers
                
                                $this->db->commit();
                                return 1;
                            }
                            else
                            {
                                $this->db->rollback();
                                return -6;
                            }
                        }
                        else
                        {
                            $this->db->rollback();
                            return -5;
                        }
                    }
                    else
                    {
                        $this->db->rollback();
                        return -4;
                    }
                }
                else
                {
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }


	/**
	 *      \brief      Renvoi une date limite de reglement de facture en fonction des
	 *                  conditions de reglements de la facture et date de facturation
	 *      \param      cond_reglement_id   Condition de reglement à utiliser, 0=Condition actuelle de la facture
	 *      \return     date                Date limite de réglement si ok, <0 si ko
	 */
	function calculate_date_lim_reglement($cond_reglement_id=0)
	{
		if (! $cond_reglement_id)
			$cond_reglement_id=$this->cond_reglement_id;
		$sqltemp = 'SELECT c.fdm,c.nbjour,c.decalage';
		$sqltemp.= ' FROM '.MAIN_DB_PREFIX.'cond_reglement as c';
		$sqltemp.= ' WHERE c.rowid='.$cond_reglement_id;
		$resqltemp=$this->db->query($sqltemp);
		if ($resqltemp)
		{
			if ($this->db->num_rows($resqltemp))
			{
				$obj = $this->db->fetch_object($resqltemp);
				$cdr_nbjour = $obj->nbjour;
				$cdr_fdm = $obj->fdm;
				$cdr_decalage = $obj->decalage;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
		$this->db->free($resqltemp);
		
		/* Definition de la date limite */
		
		// 1 : ajout du nombre de jours
		$datelim = $this->date + ( $cdr_nbjour * 3600 * 24 );
		
		// 2 : application de la règle "fin de mois"
		if ($cdr_fdm)
		{
			$mois=date('m', $datelim);
			$annee=date('Y', $datelim);
			if ($mois == 12)
			{
				$mois = 1;
				$annee += 1;
			}
			else
			{
				$mois += 1;
			}
			// On se déplace au début du mois suivant, et on retire un jour
			$datelim=mktime(12,0,0,$mois,1,$annee);
			$datelim -= (3600 * 24);
		}
		
		// 3 : application du décalage
		$datelim += ( $cdr_decalage * 3600 * 24);
		
		return $datelim;
	}

	/**
	 *      \brief      Tag la facture comme payée complètement + appel trigger BILL_PAYED
     *      \param      user        Objet utilisateur qui modifie 
 	 *      \return     int         <0 si ok, >0 si ok
	 */
	function set_payed($user)
	{
		global $conf,$langs;
	    
	    dolibarr_syslog("Facture.class.php::set_payed rowid=".$this->id);
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= ' SET paye=1 WHERE rowid = '.$this->id ;
		$resql = $this->db->query($sql);

        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);
    
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('BILL_PAYED',$this,$user,$langs,$conf);
            // Fin appel triggers
        }
        
        return 1;
	}
	
	/**
	 *      \brief      Tag la facture comme non payée complètement + appel trigger BILL_UNPAYED
     *      \param      user        Objet utilisateur qui modifie 
 	 *      \return     int         <0 si ok, >0 si ok
 	 */
	function set_unpayed($user)
	{
		global $conf,$langs;

	    dolibarr_syslog("Facture.class.php::set_unpayed rowid=".$this->id);
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= ' SET paye=0 WHERE rowid = '.$this->id;
		$resql = $this->db->query($sql);

        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);
    
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('BILL_UNPAYED',$this,$user,$langs,$conf);
            // Fin appel triggers
        }
        
        return 1;
	}
	
	/**
	*    \brief     Tag la facture comme payer partiellement
	*    \param     rowid       id de la facture à modifier
	*/
	function set_paiement_started($rowid)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture set fk_statut=2 WHERE rowid = '.$rowid;
		$return = $this->db->query( $sql);
	}

	/**
 	 *      \brief      Tag la facture comme abandonnée + appel trigger BILL_CANCEL
     *      \param      user        Objet utilisateur qui modifie 
 	 *      \return     int         <0 si ok, >0 si ok
	 */
	function set_canceled($user)
	{
		global $conf,$langs;

	    dolibarr_syslog("Facture.class.php::set_canceled rowid=".$this->id);
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= ' SET fk_statut=3 WHERE rowid = '.$this->id;
		$resql = $this->db->query($sql);

        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('BILL_CANCEL',$this,$user,$langs,$conf);
            // Fin appel triggers
        }

        return 1;
	}

	/**
	 *      \brief     	Tag la facture comme validée + appel trigger BILL_VALIDATE
	 *      \param     	rowid           Id de la facture à valider
	 *      \param     	user            Utilisateur qui valide la facture
	 *      \param     	soc             Objet societe
	 *      \param     	force_number	Référence à forcer de la facture
	 *		\return		int				<0 si ko, >0 si ok
	 */
	function set_valid($rowid, $user, $soc, $force_number='')
	{
		global $conf,$langs;
		
		$error = 0;
		if ($this->brouillon)
		{
            $this->db->begin();

			$action_notify = 2; // ne pas modifier cette valeur
			if ($force_number)
			{
				$numfa = $force_number;
			}
			else
			{
				$numfa = $this->getNextNumRef($soc);
			}

			$this->updateprice($this->id);

			// Validation de la facture
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
			$sql.= " SET facnumber='".$numfa."', fk_statut = 1, fk_user_valid = ".$user->id;
			if ($conf->global->FAC_FORCE_DATE_VALIDATION)
			{
				// Si l'option est activée, on force la date de facture
				$this->date=time();
				$datelim=$this->calculate_date_lim_reglement();
				$sql.= ', datef='.$this->db->idate($this->date);
				$sql.= ', date_lim_reglement='.$this->db->idate($datelim);
			}
			$sql.= ' WHERE rowid = '.$rowid;
			$resql=$this->db->query($sql);
            if ($resql)
            {
                $this->facnumber=$numfa;
            }
            else
            {
                dolibarr_syslog("Facture::set_valid() Echec update - 10");
                dolibarr_print_error($this->db);
                $error++;
            }

			/*
			 *	Lit les avoirs / remises absolues en cours et les décrémente
			 */
			$remise_a_decrementee=$this->remise_absolue;
			if ($remise_a_decrementee)
			{
				$sql = 'SELECT rowid, fk_soc, datec, rc.amount_ht as amount, fk_user, description';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc';
				$sql.= ' WHERE rc.fk_soc ='. $this->socidp;
				$sql.= ' AND fk_facture IS NULL';
				$sql.= ' ORDER BY datec';
				$resql = $this->db->query($sql) ;
				if ($resql)
				{
					$nurmx = $this->db->num_rows($resql);
					if ($nurmx > 0)
					{
						$i=0;
						while ($i < $nurmx && $remise_a_decrementee && ! $error)
						{
							$obj = $this->db->fetch_object($resql);
							$avoir=$obj->amount;
						
							// On met à jour avoir comme affecté à facture
							$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
							$sql.= ' SET fk_facture = '.$this->id.',';
							$sql.= " amount_ht = '".price2num(min($remise_a_decrementee,$avoir))."'";
							$sql.= ' WHERE rowid ='.$obj->rowid;
							dolibarr_syslog("Societe::set_valid Mise a jour avoir sql=$sql");
							if (! $this->db->query($sql))
							{
								$error++;
							}

							if ($remise_a_decrementee < $avoir)
							{
								// L'avoir n'a pas été complètement consommée, on insère ligne du reste
								$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'societe_remise_except';
								$sql.= ' (fk_soc, datec, amount_ht, fk_user, fk_facture, description) ';
								$sql.= ' VALUES ';
								$sql.= ' ('.$this->socidp;
								$sql.= ' ,'.$obj->datec;
								$sql.= " ,'".price2num($avoir - $remise_a_decrementee)."'";
								$sql.= ' ,'.$user->id;
								$sql.= ' ,null';
								$sql.= " ,'".addslashes($obj->description)."'";
								$sql.= ')';
								if (! $this->db->query( $sql))
								{
									$error++;
								}
							}

							$remise_a_decrementee-=min($remise_a_decrementee,$avoir);
							$i++;
						}
					}
					$this->db->free($resql);
				}
				else
				{
					dolibarr_syslog('Facture::set_valid() Erreur lecture Remise');
					$error++;
				}
			}
		
                
            /*
             * Pour chaque produit, on met a jour indicateur nbvente
             * On crée ici une dénormalisation des données pas forcément utilisée.
             */
			$sql = 'SELECT fk_product FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$this->id;
			$sql .= ' AND fk_product > 0';

            $resql = $this->db->query($sql);
            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'product SET nbvente=nbvente+1 WHERE rowid = '.$obj->fk_product;
                    $resql2 = $this->db->query($sql);
                    $i++;
                }
            }
            else
            {
                $error++;
            }

            if ($error == 0)
            {
                $this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

                $this->ref = $numfa;
                
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('BILL_VALIDATE',$this,$user,$langs,$conf);
                // Fin appel triggers

                /*
                 * Notify
                 * \todo	Mettre notifications dans triggers
                 */
                $facref = sanitize_string($this->ref);
				$filepdf = $conf->facture->dir_output . '/' . $facref . '/' . $facref . '.pdf';
				$mesg = 'La facture '.$this->ref." a été validée.\n";

                $notify = New Notify($this->db);
				$notify->send($action_notify, $this->socidp, $mesg, 'facture', $rowid, $filepdf);

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
    }
    
  /*
   *
   *
   *
   */
	 
  function set_pdf_model($user, $modelpdf)
   {
      if ($user->rights->facture->creer)
	     {

	      $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET model_pdf = '$modelpdf'";
	      $sql .= " WHERE rowid = $this->id AND fk_statut < 2 ;";
	  
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
	* \brief     Ajoute un produit dans l'objet facture
	* \param     idproduct
	* \param     qty
	* \param     remise_percent
	* \param     datestart
	* \param     dateend
	*/
	function add_product($idproduct, $qty, $remise_percent, $datestart='', $dateend='')
	{
		if ($idproduct > 0)
		{
			$i = sizeof($this->products);     // On recupere nb de produit deja dans tableau products
			$this->products[$i] = $idproduct; // On ajoute a la suite
			if (!$qty)
			{
				$qty = 1 ;
			}
			$this->products_qty[$i] = $qty;
			$this->products_remise_percent[$i] = $remise_percent;
			if ($datestart) { $this->products_date_start[$i] = $datestart; }
			if ($dateend)   { $this->products_date_end[$i] = $dateend; }
		}
	}

	/**
	* \brief    Ajoute une ligne de facture (associé à un produit/service prédéfini ou non)
	* \param    facid           id de la facture
	* \param    desc            description de la ligne
	* \param	product_desc	surcharge description produit
	* \param    pu              prix unitaire
	* \param    qty             quantit
	* \param    txtva           taux de tva
	* \param    fk_product      id du produit/service predéfini
	* \param    remise_percent  pourcentage de remise de la ligne
	* \param    datestart       date de debut de validité du service
	* \param    dateend         date de fin de validité du service
	* \param    ventil          code de ventilation comptable
	*/
	function addline($facid, $desc, $product_desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0, $datestart='', $dateend='', $ventil = 0)
	{
		global $conf;
		dolibarr_syslog("facture.class.php::addline($facid,$desc,$product_desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$datestart,$dateend,$ventil)");

		if ($this->brouillon)
		{
			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (! $qty) $qty=1;
			if (! $ventil) $ventil=0;
			$soc = new Societe($this->db);
			$soc->fetch($this->socidp);
			if($soc->tva_assuj == "0")
					$txtva ="0";
			dolibarr_syslog("facture.class.php:: txtva : ".$txtva);
            if ($fk_product && ! $pu)
            {
                $prod = new Product($this->db, $fk_product);
                $prod->fetch($fk_product);
                $product_desc = $prod->description;
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES == 1)
					$pu = $prod->multiprices[$soc->price_level];
				else
				{
                	$pu=$prod->price;
				}
				if($txtva == "")
					$txtva=$prod->tva_tx;
            }
			$price = $pu;
			$subprice = $pu;

            // Calcul remise et nouveau prix
			$remise = 0;
			if ($this->socidp)
			{
				$soc = new Societe($this->db);
				$soc->fetch($this->socidp);
				$remise_client = $soc->remise_client;
				/* La remise est client n'est pas a mettre au niveau ligne de produit mais globale
				if ($remise_client > $remise_percent)
				{
					$remise_percent = $remise_client ;
				}
				*/
			}

			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100),2);
				$price = ($pu - $remise);
			}

			// Stockage du rang max de la facture dans rangmax
			$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'facturedet';
			$sql .= ' WHERE fk_facture ='.$facid;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$row = $this->db->fetch_row($resql);
				$rangmax = $row[0];
			}
			
			 if ($conf->global->PRODUIT_CHANGE_PROD_DESC)
       {
          if (!$product_desc)
          {
             $product_desc = $desc;
          }
       }
			
			// Formatage des prix
			$price    = price2num($price);
			$subprice  = price2num($subprice);

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet ';
			$sql.= ' (fk_facture, description, price, qty, tva_taux, fk_product, remise_percent, subprice, remise, date_start, date_end, fk_code_ventilation, rang)';
			
			if ($conf->global->PRODUIT_CHANGE_PROD_DESC)
			{
				$sql.= " VALUES ($facid, '".addslashes($product_desc)."','$price','$qty','$txtva',";
			}
			else
			{
				$sql.= " VALUES ($facid, '".addslashes($desc)."','$price','$qty','$txtva',";
			}
      
			if ($fk_product) { $sql.= "'$fk_product',"; }
			else { $sql.='0,'; }
			$sql.= " '$remise_percent','$subprice','$remise',";
			if ($datestart) { $sql.= "'$datestart',"; }
			else { $sql.='null,'; }
			if ($dateend) { $sql.= "'$dateend'"; }
			else { $sql.='null'; }
			$sql.= ','.$ventil;
			$sql.= ','.($rangmax + 1).')';
			if ( $this->db->query( $sql) )
			{
				$this->updateprice($facid);
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

	/**
	 *      \brief     Mets à jour une ligne de facture
	 *      \param     rowid            Id de la ligne de facture
	 *      \param     desc             Description de la ligne
	 *      \param     pu               Prix unitaire
	 *      \param     qty              Quantité
	 *      \param     remise_percent   Pourcentage de remise de la ligne
	 *      \param     datestart        Date de debut de validité du service
	 *      \param     dateend          Date de fin de validité du service
	 *      \param     tva_tx           Taux TVA
	 *      \return    int              < 0 si erreur, > 0 si ok
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $datestart, $dateend, $tva_tx)
	{
		dolibarr_syslog("Facture::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $datestart, $dateend, $tva_tx");

		if ($this->brouillon)
		{
			$this->db->begin();
			if (strlen(trim($qty))==0)
			{
				$qty=1;
			}
			$remise = 0;
			$subprice = price2num($pu);
			$price = $subprice;
			if (trim(strlen($remise_percent)) > 0)
			{
				$remise = round(($subprice * $remise_percent / 100), 2);
				$price = $subprice - $remise;
			}
			else
			{
				$remise_percent=0;
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet set description=\''.addslashes($desc).'\'';
			$sql .= ",price='"    .     price2num($price)."'";
			$sql .= ",subprice='" .     price2num($subprice)."'";
			$sql .= ",remise='".        price2num($remise)."'";
			$sql .= ",remise_percent='".price2num($remise_percent)."'";
			$sql .= ",tva_taux='".      price2num($tva_tx)."'";
			$sql .= ",qty='$qty'";
			if ($datestart) { $sql.= ",date_start='$datestart'"; }
			else { $sql.=',date_start=null'; }
			if ($dateend) { $sql.= ",date_end='$dateend'"; }
			else { $sql.=',date_end=null'; }
			$sql .= ' WHERE rowid = '.$rowid;
			$result = $this->db->query( $sql);
			if ($result)
			{
				$this->updateprice($this->id);
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
	* \brief     Supprime une ligne facture de la base
	* \param     rowid      id de la ligne de facture a supprimer
	*/
	function deleteline($rowid)
	{
		if ($this->brouillon)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE rowid = '.$rowid;
			$result = $this->db->query( $sql);
			$this->updateprice($this->id);
		}
	}

	/**
	 *		\brief     	Mise à jour des sommes de la facture
	 * 		\param     	facid      	id de la facture a modifier
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function updateprice($facid)
	{
		include_once DOL_DOCUMENT_ROOT . '/lib/price.lib.php';
		$err=0;

		// Lit les lignes detail
		$sql = 'SELECT qty, tva_taux, subprice, remise_percent, price';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$facid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				// qty, tva_taux, sub_price = prix unitaire HT, remise_percent, price = prix unitaire HT apres remise %
				$products[$i][0] = $obj->price;
				$products[$i][1] = $obj->qty;
				$products[$i][2] = $obj->tva_taux;
				$i++;
			}

			$this->db->free($result);

			$calculs = calcul_price($products, $this->remise_percent, $this->remise_absolue);
			$this->total_remise   = $calculs[3];
			$this->amount_ht      = $calculs[4];
			$this->total_ht       = $calculs[0];
			$this->total_tva      = $calculs[1];
			$this->total_ttc      = $calculs[2];
			$tvas                 = $calculs[5];

			/*
			*
			*/

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
			$sql .= "SET amount ='".price2num($this->amount_ht)."'";
			$sql .= ", remise='".   price2num($this->total_remise)."'";
			$sql .= ", total='".    price2num($this->total_ht)."'";
			$sql .= ", tva='".      price2num($this->total_tva)."'";
			$sql .= ", total_ttc='".price2num($this->total_ttc)."'";
			$sql .= ' WHERE rowid = '.$facid;
			if ( $this->db->query($sql) )
			{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture='.$this->id;
				if ( $this->db->query($sql) )
				{
					foreach ($tvas as $key => $value)
					{
						$sql_del = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum where fk_facture ='.$this->id;
						$this->db->query($sql_del);
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX."facture_tva_sum (fk_facture,amount,tva_tx) values ($this->id,'".price2num($tvas[$key])."','".price2num($key)."');";
						//  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."facture_tva_sum SET fk_facture=".$this->id;
						//		      $sql .= ", amount = '".$tvas[$key]."'";
						//	      $sql .= ", tva_tx='".$key."'";
						if (! $this->db->query($sql) )
						{
							dolibarr_print_error($this->db);
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
				dolibarr_print_error($this->db);
			}
		}
		else
		{
			dolibarr_print_error($this->db);
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

		if ($user->rights->facture->creer)
		{
			$remise=price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= ' SET remise_percent = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			if ($this->db->query($sql))
			{
				$this->remise_percent = $remise;
				$this->updateprice($this->id);
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
		
		if ($user->rights->facture->creer)
		{
			$remise=price2num($remise);
			
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql.= ' SET remise_absolue = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			dolibarr_syslog("Facture::set_remise_absolue sql=$sql");

			if ($this->db->query($sql))
			{
				$this->remise_absolue = $remise;
				$this->updateprice($this->id);
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
	 * 		\brief     Renvoie la liste des sommes de tva
	 */
	function getSumTva()
	{
		$sql = 'SELECT amount, tva_tx FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture = '.$this->id;
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
	* \brief     Renvoie la sommes des paiements deja effectués
	* \remarks   Utilisé entre autre par certains modèles de factures
	*/
	function getSommePaiement()
	{
		$sql = 'SELECT sum(amount) FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE fk_facture = '.$this->id;
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
	 *    \brief      Retourne le libellé du statut d'une facture (brouillon, validée, abandonnée, payée)
	 *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0,$alreadypayed=-1)
	{
		return $this->LibStatut($this->paye,$this->statut,$mode,$alreadypayed);
	}

	/**
	 *    	\brief      Renvoi le libellé d'un statut donné
	 *    	\param      paye          	Etat paye
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *		\param		alreadypayed	Montant deja payé
	 *    	\return     string        	Libellé du statut
	 */
	function LibStatut($paye,$statut,$mode=0,$alreadypayed=-1)
	{
		global $langs;
		$langs->load('bills');

		if ($mode == 0)
		{
			$prefix='';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 1)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 2)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('Bill'.$prefix.'StatusDraft'),'statut0').' '.$langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3) return img_picto($langs->trans('Bill'.$prefix.'StatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($alreadypayed <= 0) return img_picto($langs->trans('Bill'.$prefix.'StatusNotPayed'),'statut1').' '.$langs->trans('Bill'.$prefix.'StatusNotPayed');
				return img_picto($langs->trans('Bill'.$prefix.'StatusStarted'),'statut3').' '.$langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return img_picto($langs->trans('Bill'.$prefix.'StatusPayed'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('Bill'.$prefix.'StatusDraft'),'statut0');
				if ($statut == 3) return img_picto($langs->trans('Bill'.$prefix.'StatusCanceled'),'statut5');
				if ($alreadypayed <= 0) return img_picto($langs->trans('Bill'.$prefix.'StatusNotPayed'),'statut1');
				return img_picto($langs->trans('Bill'.$prefix.'StatusStarted'),'statut3');
			}
			else
			{
				return img_picto($langs->trans('Bill'.$prefix.'StatusPayed'),'statut6');
			}
		}
		if ($mode == 4)
		{
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('BillStatusDraft');
				if ($statut == 3) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('BillStatusCanceled');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1').' '.$langs->trans('BillStatusNotPayed');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('BillStatusStarted');
			}
			else
			{
				return img_picto($langs->trans('BillStatusPayed'),'statut6').' '.$langs->trans('BillStatusPayed');
			}
		}
		if ($mode == 5)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft').' '.img_picto($langs->trans('Bill'.$prefix.'StatusDraft'),'statut0');
				if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled').' '.img_picto($langs->trans('Bill'.$prefix.'StatusCanceled'),'statut5');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed').' '.img_picto($langs->trans('Bill'.$prefix.'StatusNotPayed'),'statut1');
				return $langs->trans('Bill'.$prefix.'StatusStarted').' '.img_picto($langs->trans('Bill'.$prefix.'StatusStarted'),'statut3');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed').' '.img_picto($langs->trans('Bill'.$prefix.'StatusPayed'),'statut6');
			}
		}

	}

	/**
	*    \brief      Renvoi le libellé court d'un statut donné
	*    \param      paye        etat paye
	*    \param      statut      id statut
	*    \param      amount      amount already payed
	*    \return     string      Libellé court du statut
	*/
	function PayedLibStatut($paye,$statut,$amount=0)
	{
		global $langs;
		$langs->load('bills');
		if (! $paye)
		{
			if ($statut == 0) return $langs->trans('BillShortStatusDraft');
			if ($statut == 3) return $langs->trans('BillStatusCanceled');
			if ($amount) return $langs->trans('BillStatusStarted');
			return $langs->trans('BillStatusNotPayed');
		}
		else
		{
			return $langs->trans('BillStatusPayed');
		}
	}


    /**
     *      \brief      Renvoie la référence de facture suivante non utilisée en fonction du module
     *                  de numérotation actif défini dans FACTURE_ADDON
     *      \param	    soc  		            objet societe
     *      \return     string                  reference libre pour la facture
     */
    function getNextNumRef($soc)
    {
        global $db, $langs;
        $langs->load("bills");
    
        $dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";
    
        if (defined("FACTURE_ADDON") && FACTURE_ADDON)
        {
            $file = FACTURE_ADDON."/".FACTURE_ADDON.".modules.php";
    
            // Chargement de la classe de numérotation
            $classname = "mod_facture_".FACTURE_ADDON;
            require_once($dir.$file);
    
            $obj = new $classname();
    
            $numref = "";
            $numref = $obj->getNumRef($soc,$this);
    
            if ( $numref != "")
            {
                return $numref;
            }
            else
            {
                dolibarr_print_error($db,"Facture::getNextNumRef ".$obj->error);
                return "";
            }
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_NotDefined");
            return "";
        }
    }
    
	/**
 	 *    \brief      Mets à jour les commentaires privés
	 *    \param      note        	Commentaire
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note($note)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= " SET note = '".addslashes($note)."'";
		$sql.= " WHERE rowid =". $this->id;

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
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql.= " SET note_public = '".addslashes($note_public)."'";
		$sql.= " WHERE rowid =". $this->id;

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
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('datec').' as datec';
		$sql .= ', fk_user_author, fk_user_valid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as c';
		$sql .= ' WHERE c.rowid = '.$id;

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
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}
				$this->date_creation     = $obj->datec;
				//$this->date_validation   = $obj->datev; \todo La date de validation n'est pas encore gérée
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

	/**
	 *   \brief      Change les conditions de réglement de la facture
	 *   \param      cond_reglement_id      Id de la nouvelle condition de réglement
	 *   \return     int                    >0 si ok, <0 si ko
	 */
	function cond_reglement($cond_reglement_id)
	{
		dolibarr_syslog('Facture::cond_reglement('.$cond_reglement_id.')');
		if ($this->statut >= 0 && $this->paye == 0)
		{
			$datelim=$this->calculate_date_lim_reglement($cond_reglement_id);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
			$sql .= ', date_lim_reglement='.$this->db->idate($datelim);
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->cond_reglement_id = $cond_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Facture::cond_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Facture::cond_reglement, etat facture incompatible');
			$this->error='Etat facture incompatible '.$this->statut.' '.$this->paye;
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
		dolibarr_syslog('Facture::mode_reglement('.$mode_reglement_id.')');
		if ($this->statut >= 0 && $this->paye == 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->mode_reglement_id = $mode_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Facture::mode_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Facture::mode_reglement, etat facture incompatible');
			$this->error='Etat facture incompatible '.$this->statut.' '.$this->paye;
			return -2;
		}
	}


   /**
	*   \brief      Créé une demande de prélèvement
    *   \param      user        Utilisateur créant la demande
    *   \return     int         <0 si ko, >0 si ok 
	*/
	function demande_prelevement($user)
	{
        dolibarr_syslog("Facture::demande_prelevement $this->statut $this->paye $this->mode_reglement_id");

		$soc = new Societe($this->db);
		$soc->id = $this->socidp;
		$soc->rib();
		if ($this->statut > 0 && $this->paye == 0 && $this->mode_reglement_id == 3)
		{
			$sql = 'SELECT count(*) FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
			$sql .= ' WHERE fk_facture='.$this->id;
			$sql .= ' AND traite = 0';
			if ( $this->db->query( $sql) )
			{
				$row = $this->db->fetch_row();
				if ($row[0] == 0)
				{
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'prelevement_facture_demande';
					$sql .= ' (fk_facture, amount, date_demande, fk_user_demande, code_banque, code_guichet, number, cle_rib)';
					$sql .= ' VALUES ('.$this->id;
					$sql .= ",'".price2num($this->total_ttc)."'";
					$sql .= ',now(),'.$user->id;
					$sql .= ",'".$soc->bank_account->code_banque."'";
					$sql .= ",'".$soc->bank_account->code_guichet."'";
					$sql .= ",'".$soc->bank_account->number."'";
					$sql .= ",'".$soc->bank_account->cle_rib."')";
					if ( $this->db->query( $sql) )
					{
                        return 1;
					}
					else
					{
                        $this->error=$this->db->error();
						dolibarr_syslog('Facture::DemandePrelevement Erreur');
						return -1;
					}
				}
				else
				{
                    $this->error="Une demande existe déjà";
					dolibarr_syslog('Facture::DemandePrelevement Impossible de créer une demande, demande déja en cours');
				}
			}
			else
			{
                $this->error=$this->db->error();
				dolibarr_syslog('Facture::DemandePrelevement Erreur -2');
				return -2;
			}
		}
		else
		{
            $this->error="Etat facture incompatible avec l'action";
            dolibarr_syslog("Facture::DemandePrelevement Etat facture incompatible $this->statut, $this->paye, $this->mode_reglement_id");
			return -3;
		}
	}

	/**
	* \brief     Supprime une demande de prélèvement
	* \param     user         utilisateur créant la demande
	* \param     did          id de la demande a supprimer
	*/
	function demande_prelevement_delete($user, $did)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'prelevement_facture_demande';
		$sql .= ' WHERE rowid = '.$did;
		$sql .= ' AND traite = 0';
		if ( $this->db->query( $sql) )
		{
			return 0;
		}
		else
		{
			dolibarr_syslog('Facture::DemandePrelevement Erreur');
			return -1;
		}
	}

	/**
	 *      \brief      Stocke un numéro de rand pour toutes les lignes de
	 *                  detail d'une facture qui n'en ont pas.
	 */
	function line_order()
	{
		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql .= ' WHERE fk_facture='.$this->id;
		$sql .= ' AND rang = 0';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		if ($nl > 0)
		{
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facturedet';
			$sql .= ' WHERE fk_facture='.$this->id;
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.($i+1);
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
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		if ($rang > 1 )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.$rang ;
			$sql .= ' WHERE fk_facture  = '.$this->id;
			$sql .= ' AND rang = '.($rang - 1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang  = '.($rang - 1);
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
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		/* Lecture du rang max de la facture */
		$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql .= ' WHERE fk_facture ='.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$max = $row[0];
		}

		if ($rang < $max )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.$rang;
			$sql .= ' WHERE fk_facture  = '.$this->id;
			$sql .= ' AND rang = '.($rang+1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.($rang+1);
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
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        Objet user
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_board($user)
	{
		global $conf, $user;

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT f.rowid,'.$this->db->pdate('f.date_lim_reglement').' as datefin';
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ' WHERE f.paye=0 AND f.fk_statut = 1';
		if ($user->societe_id) $sql.=' AND f.fk_soc = '.$user->societe_id;
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($obj->datefin < (time() - $conf->facture->client->warning_delay)) $this->nbtodolate++;
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
  	 *      \brief      Ajoute un contact associé une facture
     *      \param      fk_socpeople        Id du contact a ajouter.
     *      \param      type_contact        Type de contact
     *      \param      source              extern=Contact externe (llx_socpeople), intern=Contact interne (llx_user)
     *      \return     int                 <0 si erreur, >0 si ok
     */
	function add_contact($fk_socpeople, $type_contact, $source='extern')
	{
        dolibarr_syslog("Facture::add_contact $fk_socpeople, $type_contact, $source");

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
	 *      \brief      Mise a jour du contact associé une facture
     *      \param      rowid               La reference du lien facture-contact
     * 		\param		statut	            Le nouveau statut
     *      \param      type_contact_id     Description du type de contact
     *      \return     int                 <0 si erreur, =0 si ok
     */
	function update_contact($rowid, $statut, $type_contact_id)
	{
        // Insertion dans la base
        $sql = "UPDATE ".MAIN_DB_PREFIX."element_contact set";
        $sql.= " statut = $statut,";
        $sql.= " fk_c_type_contact = '".$type_contact_id ."'";
        $sql.= " where rowid = ".$rowid;
        // Retour
        if (  $this->db->query($sql) )
        {
            return 0;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
	 }    

	/** 
     *    \brief      Supprime une ligne de contact
     *    \param      rowid		La reference du contact
     *    \return     statur        >0 si ok, <0 si ko
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
     *    \brief      Récupère les lignes de contact de l'objet
     *    \param      statut        Statut des lignes detail à récupérer
     *    \param      source        Source du contact external (llx_socpeople) ou internal (llx_user)
     *    \return     array         Tableau des rowid des contacts
     */
    function liste_contact($statut=-1,$source='external')
    {
        global $langs;
        
  		$element='facture';

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
                $tab[$i]=array('source'=>$obj->source,'socid'=>$obj->socid,'id'=>$obj->id,'nom'=>$obj->nom,
                               'rowid'=>$obj->rowid,'code'=>$obj->code,'libelle'=>$libelle_type,'status'=>$obj->statut);
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
     *    \brief      Le détail d'un contact
     *    \param      rowid      L'identifiant du contact
     *    \return     object     L'objet construit par DoliDb.fetch_object
     */
 	function detail_contact($rowid)
    {
  		$element='facture';

        $sql = "SELECT ec.datecreate, ec.statut, ec.fk_socpeople, ec.fk_c_type_contact,";
        $sql.= " tc.code, tc.libelle, s.fk_soc";
        $sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc, ";
        $sql.= " ".MAIN_DB_PREFIX."socpeople as s";
        $sql.= " WHERE ec.rowid =".$rowid;
        $sql.= " AND ec.fk_socpeople=s.idp";
        $sql.= " AND ec.fk_c_type_contact=tc.rowid";
        $sql.= " AND tc.element = '".$element."'";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            return $obj;
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_print_error($this->db);
            return null;
        }
    }	

    /** 
     *      \brief      Liste les valeurs possibles de type de contacts pour les factures
     *      \param      source      'internal' ou 'external'
     *      \return     array       Tableau des types de contacts
     */
 	function liste_type_contact($source)
    {
        global $langs;
        
  		$element='facture';
  		
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
     *      \brief      Retourne id des contacts d'une source et d'un type donné
     *                  Exemple: contact client de facturation ('external', 'BILLING')
     *                  Exemple: contact client de livraison ('external', 'SHIPPING')
     *                  Exemple: contact interne suivi paiement ('internal', 'SALESREPFOLL')
     *      \return     array       Liste des id contacts
     */   
    function getIdContact($source,$code)
    {
  		$element='facture';     // Contact sur la facture
        
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
     *      \brief      Retourne id des contacts clients de facturation
     *      \return     array       Liste des id contacts facturation
     */   
    function getIdBillingContact()
    {
        return $this->getIdContact('external','BILLING');
    }

    /**
     *      \brief      Retourne id des contacts clients de livraison
     *      \return     array       Liste des id contacts livraison
     */   
    function getIdShippingContact()
    {
        return $this->getIdContact('external','SHIPPING');
    }

}



/**
		\class      FactureLigne
		\brief      Classe permettant la gestion des lignes de factures
*/

class FactureLigne
{
    // From llx_facturedet
	var $desc;

	var $qty;				// Quantité (exemple 2)
	var $subprice;      	// P.U. HT (exemple 100)
	var $remise_percent;	// % de la remise ligne (exemple 20%)
	var $price;         	// P.U. HT apres remise % de ligne (exemple 80)
	var $tva_taux;			// Taux tva produit/service (exemple 19.6)
	var $remise;			// Montant calculé de la remise % sur PU HT (exemple 20)
							// subprice = price + remise

	var $total_ht;			// Total HT de la ligne toute quantité et incluant
							// la remise ligne et remise globale.
	var $total_ttc;

	var $produit_id;
	var $date_start;
	var $date_end;
	var $info_bits;			// 0 si TVA normal, 1 si TVA NPR


	/**
	 *      \brief     Constructeur d'objets ligne de facture
	 *      \param     DB      handler d'accès base de donnée
	 */
	function FactureLigne($DB)
	{
		$this->db= $DB ;
	}


	/**
	 *      \brief     Recupére l'objet ligne de facture
	 *      \param     rowid           id de la ligne de facture
	 *      \param     societe_id      id de la societe
	 */
	function fetch($rowid, $societe_id=0)
	{
		$sql = 'SELECT fk_product, description, price, qty, rowid, tva_taux, remise, remise_percent,';
		$sql.= ' subprice, '.$this->db->pdate('date_start').' as date_start,'.$this->db->pdate('date_end').' as date_end,';
		$sql.= ' info_bits';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet WHERE rowid = '.$rowid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->desc           = stripslashes($objp->description);
			$this->qty            = $objp->qty;
			$this->price          = $objp->price;
			//$this->price_ttc      = $objp->price_ttc;
			$this->subprice       = $objp->subprice;
			$this->tva_taux       = $objp->tva_taux;
			$this->remise         = $objp->remise;
			$this->remise_percent = $objp->remise_percent;
			$this->produit_id     = $objp->fk_product;
			$this->date_start     = $objp->date_start;
			$this->date_end       = $objp->date_end;
			$this->info_bits      = $objp->info_bits;
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

}

?>
