<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C)      2005 Marc Barilley / Ocebo <marc@ocebo.com>
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
	var $paye;
	var $propalid;
	var $projetid;
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
		$this->db->begin();

		/* On positionne en mode brouillon la facture */
		$this->brouillon = 1;

		/* Facture récurrente */
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
			$this->remise            = $_facrec->remise;
			$this->remise_percent    = $_facrec->remise_percent;
		}

		// Definition de la date limite
		$datelim=$this->calculate_date_lim_reglement();

		/*
		 *  Insertion dans la base
		 */
		$socid = $this->socidp;
		$number = $this->number;
		$amount = $this->amount;
		$remise = $this->remise;

		if (! $remise) $remise = 0 ;
		if (strlen($this->mode_reglement_id)==0) $this->mode_reglement_id = 0;
		if (! $this->projetid) $this->projetid = 'NULL';

		$totalht = ($amount - $remise);
// NE ME SEMBLE PLUS JUSTIFIE ICI
// 		$tva = tva($totalht);
// 		$total = $totalht + $tva;

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture (facnumber, fk_soc, datec, amount, remise, remise_percent';
		$sql .= ', datef, note, fk_user_author, fk_projet';
		$sql .= ', fk_cond_reglement, fk_mode_reglement, date_lim_reglement, ref_client) ';
		$sql .= " VALUES ('$number','$socid', now(), '$totalht', '$remise'";
		$sql .= ",'$this->remise_percent', ".$this->db->idate($this->date);
		$sql .= ",'".addslashes($this->note)."',$user->id, $this->projetid";
		$sql .= ','.$this->cond_reglement_id.','.$this->mode_reglement_id.','.$this->db->idate($datelim).', \''.$this->ref_client.'\')';
		if ( $this->db->query($sql) )
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture');

			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET facnumber='(PROV".$this->id.")' WHERE rowid=".$this->id;
			$this->db->query($sql);

			if ($this->id && $this->propalid)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fa_pr (fk_facture, fk_propal) VALUES ('.$this->id.','.$this->propalid.')';
				$this->db->query($sql);
			}
			if ($this->id && $this->commandeid)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_fa (fk_facture, fk_commande) VALUES ('.$this->id.','.$this->commandeid.')';
				$this->db->query($sql);
			}

			/*
			* Produits/services
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
					$this->products_remise_percent[$i],
					$this->products_date_start[$i],
					$this->products_date_end[$i]
				);

				if ( $result_insert < 0)
				{
					dolibarr_print_error($this->db);
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
						dolibarr_print_error($this->db);
					}
				}
			}

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
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }


	/*
	 *       \brief      Affecte la remise exceptionnelle
	 */
	function _affect_remise_exceptionnelle()
	{
		$error = 0;

		$this->db->begin();

		if ($this->remise_exceptionnelle[1] > 0)
		{
			// Calcul valeur de remise a appliquer (remise) et reliquat
			if ($this->remise_exceptionnelle[1] > ($this->total_ht * 0.9))
			{
				$remise = floor($this->total_ht * 0.9);
				$reliquat = $this->remise_exceptionnelle[1] - $remise;
			}
			else
			{
				$remise = $this->remise_exceptionnelle[1];
				$reliquat=0;
			}

			$result_insert = $this->addline($this->id,
				addslashes('Remise exceptionnelle'),
				(0 - $remise),
				1,
				'0');   // Une remise est un négatif sur le TTC, on ne doit pas appliquer de TVA,
						// sinon on impute une TVA négative.

			if ($result_insert < 0)
			{
				$error++;
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql .= ' SET fk_facture = '.$this->id;
			$sql .= " ,amount_ht = '".ereg_replace(',','.',$remise)."'";
			$sql .= ' WHERE rowid ='.$this->remise_exceptionnelle[0];
			$sql .= ' AND fk_soc ='. $this->socidp;

			if (! $this->db->query( $sql))
			{
				$error++;
			}

			if ($reliquat > 0)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'societe_remise_except';
				$sql .= ' (fk_soc, datec, amount_ht, fk_user) ';
				$sql .= ' VALUES ';
				$sql .= ' ('.$this->socidp;
				$sql .= ' ,now()';
				$sql .= " ,'".ereg_replace(',','.',$reliquat)."'";
				$sql .= ' ,'.$this->remise_exceptionnelle[3];
				$sql .= ')';

				if (! $this->db->query( $sql) )
				{
					$error++;
				}
			}
		}

		if (! $error)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
		}

		return $error;
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

		$sql = 'SELECT f.fk_soc,f.facnumber,f.amount,f.tva,f.total,f.total_ttc,f.remise,f.remise_percent';
		$sql .= ','.$this->db->pdate('f.datef').' as df, f.fk_projet';
		$sql .= ','.$this->db->pdate('f.date_lim_reglement').' as dlr';
		$sql .= ', f.note, f.paye, f.fk_statut, f.fk_user_author';
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
				$this->remise                 = $obj->remise;
				$this->total_ht               = $obj->total;
				$this->total_tva              = $obj->tva;
				$this->total_ttc              = $obj->total_ttc;
				$this->paye                   = $obj->paye;
				$this->remise_percent         = $obj->remise_percent;
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
				$this->note                   = stripslashes($obj->note);
				$this->user_author            = $obj->fk_user_author;
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
	* \brief     Valide la facture
	* \param     userid      id de l'utilisateur qui valide
	*/
	function valid($userid)
	{
		$error = 0;

		if ($this->db->begin())
		{
			/*
			* Lecture de la remise exceptionnelle
			*
			*/
			$sql  = 'SELECT rowid, rc.amount_ht, fk_soc, fk_user';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc';
			$sql .= ' WHERE rc.fk_soc ='. $this->socidp;
			$sql .= ' AND fk_facture IS NULL';
			$resql = $this->db->query($sql) ;
			if ( $resql)
			{
				$nurmx = $this->db->num_rows($resql);
				if ($nurmx > 0)
				{
					$row = $this->db->fetch_row($resql);
					$this->remise_exceptionnelle = $row;
				}
				$this->db->free($resql);
			}
			else
			{
				dolibarr_syslog('Facture::Valide Erreur lecture Remise');
				$error++;
			}

			/*
			* Affectation de la remise exceptionnelle
			*/
			if ( $this->_affect_remise_exceptionnelle() <> 0)
			{
				$error++;
			}
			else
			{
				$this->updateprice($this->id);
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET fk_statut = 1, date_valid=now(), fk_user_valid='.$userid;
				$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';
				if (! $this->db->query($sql) )
				{
					$error++;
					dolibarr_syslog('Facture::Valide Erreur ');
				}
			}

			if ($error == 0)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}
		}
		else
		{
			$error++;
		}

		if ($error > 0)
		{
			return 0;
		}
		else
		{
			return 1;
		}
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
	* \brief     Supprime la facture
	* \param     rowid      id de la facture à supprimer
	*/
	function delete($rowid)
	{
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
						/*
						* On repositionne la remise
						*/
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
						$sql .= ' SET fk_facture = NULL WHERE fk_facture = '.$rowid;
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
		$sqltemp = 'SELECT c.fdm,c.nbjour';
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
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
		$this->db->free($resqltemp);
		// Definition de la date limite
		$datelim = $this->date + ( $cdr_nbjour * 3600 * 24 );
		if ($cdr_fdm)
		{
			$mois=date('m', $datelim);
			$annee=date('Y', $datelim);
			$fins=array(31,28,31,30,31,30,31,31,30,31,30,31);
			$datelim=mktime(12,0,0,$mois,$fins[$mois-1],$annee);
		}
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
	 *      \brief     Tag la facture comme validée + appel trigger BILL_VALIDATE
	 *      \param     rowid            Id de la facture à valider
	 *      \param     user             Utilisateur qui valide la facture
	 *      \param     soc              Objet societe
	 *      \param     force_number     Référence à forcer de la facture
	 */
	function set_valid($rowid, $user, $soc, $force_number='')
	{
		global $conf,$langs;
		
		$error = 0;
		if ($this->brouillon)
		{
			$action_notify = 2; // ne pas modifier cette valeur
			if ($force_number)
			{
				$numfa=$force_number;
			}
			else
			{
				$numfa = $this->getNextNumRef($soc);
			}

            $this->db->begin();
            
			/*
			 * Affectation de la remise exceptionnelle
			 *
			 * \todo    Appliquer la remise avoir dans les lignes quand brouillon plutot
			 *          qu'au moment de la validation
			 */
			$sql  = 'SELECT rowid, rc.amount_ht, fk_soc, fk_user';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc';
			$sql .= ' WHERE rc.fk_soc ='. $this->socidp;
			$sql .= ' AND fk_facture IS NULL';
			$resql = $this->db->query($sql) ;
			if ($resql)
			{
				$nurmx = $this->db->num_rows($resql);
				if ($nurmx > 0)
				{
					$row = $this->db->fetch_row($resql);
					$this->remise_exceptionnelle = $row;
				}
				$this->db->free($resql);
			}
			else
			{
				dolibarr_syslog('Facture::Valide Erreur lecture Remise');
				$error++;
			}
			if ( $this->_affect_remise_exceptionnelle() <> 0)
			{
				$error++;
			}
			else
			{
				$this->updateprice($this->id);
			}

			/* Validation de la facture */
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
			$sql.= " SET facnumber='".$numfa."', fk_statut = 1, fk_user_valid = ".$user->id;

			/* Si l'option est activée on force la date de facture */
			if ($conf->global->FAC_FORCE_DATE_VALIDATION)
			{
				$this->date=time();
				$datelim=$this->calculate_date_lim_reglement();
				$sql .= ', datef='.$this->db->idate($this->date);
				$sql .= ', date_lim_reglement='.$this->db->idate($datelim);
			}
			$sql .= ' WHERE rowid = '.$rowid;
			$resql = $this->db->query($sql);
            if ($resql)
            {
                $this->facnumber=$numfa;
            }
            else
            {
                dolibarr_syslog("Facture::set_valid() Echec - 10");
                dolibarr_print_error($this->db);
                $error++;
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

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('BILL_VALIDATE',$this,$user,$langs,$conf);
                // Fin appel triggers

                $this->db->commit();

                /*
                 * Notify
                 */
                $facref = sanitize_string($this->ref);
				$filepdf = $conf->facture->dir_output . '/' . $facref . '/' . $facref . '.pdf';
				$mesg = 'La facture '.$this->ref." a été validée.\n";

                $notify = New Notify($this->db);
				$notify->send($action_notify, $this->socidp, $mesg, 'facture', $rowid, $filepdf);

                return 1;
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->error();
                return -1;
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
	* \brief     Ajoute une ligne de facture (associé à un produit/service prédéfini ou non)
	* \param     facid           id de la facture
	* \param     desc            description de la ligne
	* \param     pu              prix unitaire
	* \param     qty             quantit
	* \param     txtva           taux de tva
	* \param     fk_product      id du produit/service predéfini
	* \param     remise_percent  pourcentage de remise de la ligne
	* \param     datestart       date de debut de validité du service
	* \param     dateend         date de fin de validité du service
	* \param     ventil          code de ventilation comptable
	*/
	function addline($facid, $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0, $datestart='', $dateend='', $ventil = 0)
	{
		dolibarr_syslog("facture.class.php::addline($facid,$desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$datestart,$dateend,$ventil)");
		if ($this->brouillon)
		{
			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (strlen(trim($qty))==0) $qty=1;

            if ($fk_product && ! $pu)
            {
                $prod = new Product($this->db, $fk_product);
                $prod->fetch($fk_product);
                $pu=$prod->price;
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
				if ($remise_client > $remise_percent)
				{
					$remise_percent = $remise_client ;
				}
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

			// Formatage des prix
			$price    = price2num($price);
			$subprice  = price2num($subprice);

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet ';
			$sql.= ' (fk_facture, description, price, qty, tva_taux, fk_product, remise_percent, subprice, remise, date_start, date_end, fk_code_ventilation, rang)';
			$sql.= " VALUES ($facid, '".addslashes($desc)."','$price','$qty','$txtva',";
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
		dolibarr_syslog('Facture::UpdateLine');

		if ($this->brouillon)
		{
			$this->db->begin();
			if (strlen(trim($qty))==0)
			{
				$qty=1;
			}
			$remise = 0;
			$price = ereg_replace(',','.',$pu);
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

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet set description=\''.addslashes($desc).'\'';
			$sql .= ",price='"    .     ereg_replace(',','.',$price)."'";
			$sql .= ",subprice='" .     ereg_replace(',','.',$subprice)."'";
			$sql .= ",remise='".        ereg_replace(',','.',$remise)."'";
			$sql .= ",remise_percent='".ereg_replace(',','.',$remise_percent)."'";
			$sql .= ",tva_taux='".      ereg_replace(',','.',$tva_tx)."'";
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
	* \brief     Mise à jour des sommes de la facture
	* \param     facid      id de la facture a modifier
	*/
	function updateprice($facid)
	{
		include_once DOL_DOCUMENT_ROOT . '/lib/price.lib.php';
		$err=0;
		$sql = 'SELECT price, qty, tva_taux FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.$facid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$products[$i][0] = $obj->price;
				$products[$i][1] = $obj->qty;
				$products[$i][2] = $obj->tva_taux;
				$i++;
			}

			$this->db->free($result);
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

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
			$sql .= "SET amount ='".ereg_replace(',','.',$this->amount_ht)."'";
			$sql .= ", remise='".   ereg_replace(',','.',$this->total_remise)."'";
			$sql .= ", total='".    ereg_replace(',','.',$this->total_ht)."'";
			$sql .= ", tva='".      ereg_replace(',','.',$this->total_tva)."'";
			$sql .= ", total_ttc='".ereg_replace(',','.',$this->total_ttc)."'";
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
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX."facture_tva_sum (fk_facture,amount,tva_tx) values ($this->id,'".ereg_replace(',','.',$tvas[$key])."','".ereg_replace(',','.',$key)."');";
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
	* \brief     Applique une remise
	* \param     user
	* \param     remise
	*/
	function set_remise($user, $remise)
	{
		if ($user->rights->facture->creer)
		{
			$this->remise_percent = $remise ;
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET remise_percent = '.ereg_replace(',','.',$remise);
			$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			if ($this->db->query($sql) )
			{
				$this->updateprice($this->id);
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}


	/**
	* \brief     Renvoie la liste des sommes de tva
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
	*    \return     string      Libell
	*/
	function getLibStatut()
	{
		return $this->LibStatut($this->paye,$this->statut);
	}

	/**
	 *    \brief      Renvoi le libellé d'un statut donn
	 *    \param      paye          Etat paye
	 *    \param      statut        Id statut
	 *    \param      mode          0=libellé long, 1=libellé court
	 *    \return     string        Libellé du statut
	 */
	function LibStatut($paye,$statut,$mode=0)
	{
		global $langs;
		$langs->load('bills');

		$prefix='';
		if ($mode == 1)
			$prefix='Short';
		if (! $paye)
		{
			if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
			if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled');
			return $langs->trans('Bill'.$prefix.'StatusValidated');
		}
		else
		{
			return $langs->trans('Bill'.$prefix.'StatusPayed');
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
 	 *    \brief      Mets à jour les commentaires
	 *    \param      note        note
	 *    \return     int         <0 si erreur, >0 si ok
	 */
	function update_note($note)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET note = '".addslashes($note)."'";
		$sql .= ' WHERE rowid ='. $this->id;

		if ($this->db->query($sql) )
		{
			$this->note = $note;
			return 1;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       Id de la facture a charger
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
	* \brief     Créé une demande de prélèvement
    *   \param      user        Utilisateur créant la demande
    *   \return     int         <0 si ko, >0 si ok 
	*/
	function demande_prelevement($user)
	{
        dolibarr_syslog("Facture::demande_prelevement");

		$soc = new Societe($this->db);
		$soc->id = $this->socidp;
		$soc->rib();
		if ($this->statut > 0 && $this->paye == 0 &&  $this->mode_reglement_id == 3)
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
					$sql .= ",'".ereg_replace(',','.',$this->total_ttc)."'";
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
		global $conf;

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT f.rowid,'.$this->db->pdate('f.date_lim_reglement').' as datefin';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' WHERE f.paye=0 AND f.fk_statut = 1';
		if ($user->societe_id) $sql.=' AND fk_soc = '.$user->societe_id;
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

}



/**
		\class      FactureLigne
		\brief      Classe permettant la gestion des lignes de factures
*/

class FactureLigne
{
	var $subprice;  // Prix unitaire HT
	var $price;     // Prix HT apres remise %

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
		$sql.= ' subprice, '.$this->db->pdate('date_start').' as date_start,'.$this->db->pdate('date_end').' as date_end';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet WHERE rowid = '.$rowid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->desc           = stripslashes($objp->description);
			$this->qty            = $objp->qty;
			$this->price          = $objp->price;
			$this->price_ttc      = $objp->price_ttc;
			$this->subprice       = $objp->subprice;
			$this->tva_taux       = $objp->tva_taux;
			$this->remise         = $objp->remise;
			$this->remise_percent = $objp->remise_percent;
			$this->produit_id     = $objp->fk_product;
			$this->date_start     = $objp->date_start;
			$this->date_end       = $objp->date_end;
			$i++;
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

}

?>
