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

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT .'/notify.class.php');
require_once(DOL_DOCUMENT_ROOT ."/product.class.php");
require_once(DOL_DOCUMENT_ROOT ."/client.class.php");

/**
	\class      Facture
	\brief      Classe permettant la gestion des factures clients
*/

class Facture extends CommonObject
{
	var $db;
	var $element='facture';

	var $id;

	var $socidp;		// Id client
	var $client;		// Objet societe client (à charger par fetch_client)

	var $number;
	var $author;
	var $date;
	var $ref;
	var $ref_client;
	var $amount;
	var $remise;
	var $tva;
	var $total;
	var $note;
	var $note_public;
	var $statut;
	var $paye;					// 1 si facture payée COMPLETEMENT, 0 sinon
	var $propalid;
	var $projetid;
	var $date_lim_reglement;
	var $cond_reglement_id;
	var $cond_reglement_code;
	var $mode_reglement_id;
	var $mode_reglement_code;
	var $modelpdf;

	// Pour board
	var $nbtodo;
	var $nbtodolate;
	
	var $specimen;
	var $error;


	/**
	*    \brief  Constructeur de la classe
	*    \param  DB         handler accès base de données
	*    \param  socidp		id societe ('' par defaut)
	*    \param  facid      id facture ('' par defaut)
	*/
	function Facture($DB, $socidp='', $facid='')
	{
		$this->db = $DB ;

		$this->id = $facid;
		$this->socidp = $socidp;

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
		global $langs,$conf,$mysoc;

		// Nettoyage paramètres
		$this->note=trim($this->note);
		$this->note_public=trim($this->note_public);
		$this->ref_client=trim($this->ref_client);
		if (! $this->remise) $this->remise = 0 ;
		if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;

		// On positionne en mode brouillon la facture
		$this->brouillon = 1;

		dolibarr_syslog("Facture::create");

		$soc = new Societe($this->db);
		$soc->fetch($this->socidp);

		$this->db->begin();

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
			$this->remise			       = $_facrec->remise;
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
	  	$sql.= ' ref_client,';
		$sql.= ' fk_user_author, fk_projet,';
		$sql.= ' fk_cond_reglement, fk_mode_reglement, date_lim_reglement, model_pdf) ';
		$sql.= " VALUES (";
		$sql.= "'$number','$socid', now(), '$totalht', '".$this->remise_absolue."'";
		$sql.= ",'".$this->remise_percent."', ".$this->db->idate($this->date);
		$sql.= ",".($this->note?"'".addslashes($this->note)."'":"null");
		$sql.= ",".($this->note_public?"'".addslashes($this->note_public)."'":"null");
		$sql.= ",".($this->ref_client?"'".addslashes($this->ref_client)."'":"null");
		$sql.= ",".$user->id;
		$sql.= ",".($this->projetid?$this->projetid:"null");
		$sql.= ','.$this->cond_reglement_id;
		$sql.= ",".$this->mode_reglement_id;
		$sql.= ",".$this->db->idate($datelim).", '".$this->modelpdf."')";

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
						$res=$prod->fetch($_facrec->lignes[$i]->produit_id);
					}
					$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

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
	            $resql=$this->update_price($this->id);
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
		$sql .= ', f.fk_cond_reglement, c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_facture';
		$sql .= ', cf.fk_commande';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'cond_reglement as c, '.MAIN_DB_PREFIX.'facture as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'co_fa as cf ON cf.fk_facture = f.rowid';
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
				$this->cond_reglement_code    = $obj->cond_reglement_code;
				$this->cond_reglement         = $obj->cond_reglement_libelle;
				$this->cond_reglement_facture = $obj->cond_reglement_libelle_facture;
				$this->projetid               = $obj->fk_projet;
				$this->note                   = $obj->note;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->commande_id            = $obj->fk_commande;
				$this->lignes                 = array();


				if ($this->user_author)
        {
             $sql = "SELECT name, firstname";
             $sql.= " FROM ".MAIN_DB_PREFIX."user";
             $sql.= " WHERE rowid = ".$this->user_author;

             $resqluser = $this->db->query($sql);

             if ($resqluser)
             {
                $obju = $this->db->fetch_object($resqluser);
                $this->user_author_name      = $obju->name;
                $this->user_author_firstname = $obju->firstname;
             }
        }

        if ($this->commande_id)
        {
             $sql = "SELECT ref";
             $sql.= " FROM ".MAIN_DB_PREFIX."commande";
             $sql.= " WHERE rowid = ".$this->commande_id;

             $resqlcomm = $this->db->query($sql);

             if ($resqlcomm)
             {
                $objc = $this->db->fetch_object($resqlcomm);
                $this->commande_ref      = $objc->ref;
             }
        }

				if ($this->statut == 0)
				{
					$this->brouillon = 1;
				}

				/*
				 * Lignes
				 */
				$sql = 'SELECT l.rowid, l.fk_product, l.description, l.price, l.qty, l.tva_taux, l.remise, l.remise_percent, l.subprice,';
				$sql.= ' '.$this->db->pdate('l.date_start').' as date_start,'.$this->db->pdate('l.date_end').' as date_end,';
				$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_ttc, l.fk_code_ventilation, l.fk_export_compta,';
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
						$faclig->rowid			      = $objp->rowid;
						$faclig->desc             = $objp->description;     // Description ligne
						$faclig->libelle          = $objp->label;           // Label produit
						$faclig->product_desc     = $objp->product_desc;    // Description produit
						$faclig->qty              = $objp->qty;
						$faclig->price            = $objp->price;
						$faclig->subprice         = $objp->subprice;
						$faclig->tva_taux         = $objp->tva_taux;
						$faclig->remise           = $objp->remise;
						$faclig->remise_percent   = $objp->remise_percent;
						$faclig->produit_id       = $objp->fk_product;
						$faclig->date_start       = $objp->date_start;
						$faclig->date_end         = $objp->date_end;
						$faclig->date_start       = $objp->date_start;
						$faclig->date_end         = $objp->date_end;
						$faclig->info_bits        = $objp->info_bits;
						$faclig->total_ht         = $objp->total_ht;
						$faclig->total_tva        = $objp->total_tva;
						$faclig->total_ttc        = $objp->total_ttc;
						$faclig->export_compta    = $objp->fk_export_compta;
						$faclig->code_ventilation = $objp->fk_code_ventilation;
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
     *    \brief     Ajout d'une ligne remise fixe dans la facture, en base
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
			$facligne=new FactureLigne($this->db);
			$facligne->fk_facture=$this->id;
			$facligne->fk_remise_except=$remise->id;
			$facligne->desc=$remise->description;   	// Description ligne
			$facligne->tva_tx=$remise->tva_tx;
			$facligne->subprice=-$remise->amount_ht;
			$facligne->price=-$remise->amount_ht;
			$facligne->fk_product=0;					// Id produit prédéfini
			$facligne->qty=1;
			$facligne->remise=0;
			$facligne->remise_percent=0;
			$facligne->rang=-1;
			$facligne->info_bits=2;

			$tabprice=calcul_price_total($facligne->qty, $facligne->subprice, 0,$facligne->tva_tx);
			$facligne->total_ht  = $tabprice[0];
			$facligne->total_tva = $tabprice[1];
			$facligne->total_ttc = $tabprice[2];

			$result=$facligne->insert();
			if ($result > 0)
			{
				$result=$this->update_price($this->id);
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
				$this->error=$facligne->error;
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
			$sql .= ' SET ref_client = \''.addslashes($ref_client).'\'';
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

			// on vérifie si la facture est en numérotation provisoire
			$facref = substr($this->ref, 1, 4);
			
			$action_notify = 2; // ne pas modifier cette valeur
			if ($force_number)
			{
				$numfa = $force_number;
			}
			else if ($facref == PROV)
			{
				$numfa = $this->getNextNumRef($soc);
			}
			else
			{
				$numfa = $this->ref;
			}

			$this->update_price($this->id);

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
                dolibarr_syslog("Facture::set_valid() sql=$sql");
            }
            else
            {
                dolibarr_syslog("Facture::set_valid() Echec update - 10 - sql=$sql");
                dolibarr_print_error($this->db);
                $error++;
            }


			// On vérifie si la facture était une provisoire
			if ($facref == PROV)
			{
				// On renomme repertoire facture ($this->ref = ancienne ref, $numfa = nouvelle ref)
				// afin de ne pas perdre les fichiers attachés
			  $facref = sanitize_string($this->ref);
			  $snumfa = sanitize_string($numfa);
			  $dirsource = $conf->facture->dir_output.'/'.$facref;
			  $dirdest = $conf->facture->dir_output.'/'.$snumfa;
			  if (file_exists($dirsource))
			  {
				  dolibarr_syslog("Facture::set_valid() renommage rep ".$dirsource." en ".$dirdest);

				  if (rename($dirsource, $dirdest))
				  {
					  dolibarr_syslog("Renommage ok");
					  // Suppression ancien fichier PDF dans nouveau rep
					  dol_delete_file($conf->facture->dir_output.'/'.$snumfa.'/'.$facref.'.*');
				  }
			  }
			}


			/*
			 *	Tope les lignes de remises fixes avec id des lignes de facture au montant négatif
			 */
/* TODO Toper les lignes de remises fixes avec id des lignes de facture au montant négatif.

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
*/

      // On vérifie si la facture était une provisoire
			if ($facref == PROV)
			{
            /*
             * Pour chaque produit, on met a jour indicateur nbvente
             * On crée ici une dénormalisation des données pas forcément utilisée.
             */
			      $sql = 'SELECT fk_product FROM '.MAIN_DB_PREFIX.'facturedet';
			      $sql.= ' WHERE fk_facture = '.$this->id;
			      $sql.= ' AND fk_product > 0';

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
    
  /**
   *
   *
   */
    function reopen($userid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_statut = 0";
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
	 *		\brief		Positionne modele derniere generation
	 *		\param		user		Objet use qui modifie
	 *		\param		modelpdf	Nom du modele
	 */
	function set_pdf_model($user, $modelpdf)
	{
		if ($user->rights->facture->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET model_pdf = '$modelpdf'";
			$sql .= " WHERE rowid = $this->id AND fk_statut < 2 ;";
	
			if ($this->db->query($sql) )
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
	 * 	\brief     Ajoute un produit dans les tableaux products, products_qty, products_date_start|end
	 * 	\param     idproduct
	 * 	\param     qty
	 * 	\param     remise_percent
	 * 	\param     date_start
	 * 	\param     date_end
	 */
	function add_product($idproduct, $qty, $remise_percent, $date_start='', $date_end='')
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
			if ($date_start) { $this->products_date_start[$i] = $date_start; }
			if ($date_end)   { $this->products_date_end[$i] = $date_end; }
		}
	}

	/**
	 * 		\brief    	Ajoute une ligne de facture (associé à un produit/service prédéfini ou non)
	 * 		\param    	facid           Id de la facture
	 * 		\param    	desc            Description de la ligne
	 * 		\param    	pu              Prix unitaire
	 * 		\param    	qty             Quantité
	 * 		\param    	txtva           Taux de tva forcé, sinon -1
	 *		\param    	fk_product      Id du produit/service predéfini
	 * 		\param    	remise_percent  Pourcentage de remise de la ligne
	 * 		\param    	date_start      Date de debut de validité du service
	 * 		\param    	date_end        Date de fin de validité du service
	 * 		\param    	ventil          Code de ventilation comptable
	 * 		\remarks	Les parametres sont deja censé etre juste et avec valeurs finales a l'appel
	 *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete défini
	 *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
 	 *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
 	 */
	function addline($facid, $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil = 0)
	{
		global $conf;
		dolibarr_syslog("facture.class.php::addline($facid,$desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$date_start,$date_end,$ventil)");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		if ($this->brouillon)
		{
			$this->db->begin();

			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (! $qty) $qty=1;
			if (! $ventil) $ventil=0;
			if (! $info_bits) $info_bits=0;
			$pu = price2num($pu);
			$txtva=price2num($txtva);

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
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100),2);
				$price = ($pu - $remise);
			}

			// Insertion ligne
			$ligne=new FactureLigne($this->db);

			$ligne->fk_facture=$facid;
			$ligne->desc=$desc;
			$ligne->price=$price;
			$ligne->qty=$qty;
			$ligne->txtva=$txtva;
			$ligne->fk_product=$fk_product;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$subprice;
			$ligne->remise=$remise;
			$ligne->date_start=$date_start;
			$ligne->date_end=$date_end;				
			$ligne->ventil=$ventil;
			$ligne->rang=-1;
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
	 *      \brief     Mets à jour une ligne de facture
	 *      \param     rowid            Id de la ligne de facture
	 *      \param     desc             Description de la ligne
	 *      \param     pu               Prix unitaire
	 *      \param     qty              Quantité
	 *      \param     remise_percent   Pourcentage de remise de la ligne
	 *      \param     date_start        Date de debut de validité du service
	 *      \param     date_end          Date de fin de validité du service
	 *      \param     tva_tx           Taux TVA
	 *      \return    int              < 0 si erreur, > 0 si ok
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $date_start, $date_end, $txtva)
	{
		dolibarr_syslog("Facture::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		if ($this->brouillon)
		{
			$this->db->begin();
			
			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (! $qty) $qty=1;
			$pu = price2num($pu);
			$txtva=price2num($txtva);

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
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100),2);
				$price = ($pu - $remise);
			}
			$price    = price2num($price);
			$subprice  = price2num($subprice);

			// Mise a jour ligne en base
			$ligne=new FactureLigne($this->db);
			$ligne->rowid=$rowid;
			$ligne->fetch($rowid);
			
			$ligne->desc=$desc;
			$ligne->price=$price;
			$ligne->qty=$qty;
			$ligne->tva_taux=$txtva;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$subprice;
			$ligne->remise=$remise;
			$ligne->date_start=$date_start;
			$ligne->date_end=$date_end;				
			$ligne->total_ht=$total_ht;
			$ligne->total_tva=$total_tva;
			$ligne->total_ttc=$total_ttc;

			$result=$ligne->update();			
			if ($result > 0)
			{
				// Mise a jour info denormalisees au niveau facture
				$this->update_price($this->id);
				$this->db->commit();
				return $result;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error="Facture::UpdateLine Invoice statut makes operation forbidden";
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
			$this->update_price($this->id);
		}
	}

	/**
	 *		\brief     	Mise à jour des sommes de la facture et calculs denormalises
	 * 		\param     	facid      	id de la facture a modifier
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function update_price($facid)
	{
		$tvas=array();
		$err=0;

        // Liste des lignes factures a sommer
		$sql = 'SELECT qty, tva_taux, subprice, remise_percent, price,';
		$sql.= ' total_ht, total_tva, total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' WHERE fk_facture = '.$facid;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->total_ht  = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->total_ht    += $obj->total_ht;
				$this->total_tva   += ($obj->total_ttc - $obj->total_ht);
				$this->total_ttc   += $obj->total_ttc;

				// Anciens indicateurs
				$this->amount_ht      += ($obj->price * $obj->qty);
				$this->total_remise   += 0;		// Plus de remise globale (toute remise est sur une ligne)
				$tvas[$obj->tva_taux] += ($obj->total_ttc - $obj->total_ht);

/* \deprecated car simplifie par les 3 indicateurs total_ht, total_tva et total_ttc sur lignes
				$products[$i][0] = $obj->price;
				$products[$i][1] = $obj->qty;
				$products[$i][2] = $obj->tva_taux;
*/
				$i++;
			}

			$this->db->free($resql);

/* \deprecated car simplifie par les 3 indicateurs total_ht, total_tva et total_ttc sur lignes
			$calculs = calcul_price($products, $this->remise_percent, $this->remise_absolue);
			$this->total_remise   = $calculs[3];
			$this->amount_ht      = $calculs[4];
			$this->total_ht       = $calculs[0];
			$this->total_tva      = $calculs[1];
			$this->total_ttc      = $calculs[2];
			$tvas                 = $calculs[5];
*/

			// Met a jour indicateurs sur facture
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
			$sql .= "SET amount ='".price2num($this->amount_ht)."'";
			$sql .= ", remise='".   price2num($this->total_remise)."'";
			$sql .= ", total='".    price2num($this->total_ht)."'";
			$sql .= ", tva='".      price2num($this->total_tva)."'";
			$sql .= ", total_ttc='".price2num($this->total_ttc)."'";
			$sql .= ' WHERE rowid = '.$facid;
			$resql=$this->db->query($sql);

			if ($resql)
			{
				// \TODO A supprimer car l'utilisation de facture_tva_sum non utilisable
				// dans un context compta propre. On utilisera plutot les lignes.
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum WHERE fk_facture='.$this->id;
				if ( $this->db->query($sql) )
				{
					foreach ($tvas as $key => $value)
					{
						$sql_del = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_tva_sum where fk_facture ='.$this->id;
						$this->db->query($sql_del);
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX."facture_tva_sum (fk_facture,amount,tva_tx) values ($this->id,'".price2num($tvas[$key])."','".price2num($key)."');";
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
	 * 		\brief     Renvoie la liste des sommes de tva
	 */
	function getSumTva()
	{
		$tvs=array();

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
			dolibarr_print_error($this->db);
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
				if ($statut == 3 && $alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusClosedUnpayed');
				if ($statut == 3 && $alreadypayed > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
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
				if ($statut == 3 && $alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusClosedUnpayed');
				if ($statut == 3 && $alreadypayed > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
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
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3 && $alreadypayed <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusClosedUnpayed');
				if ($statut == 3 && $alreadypayed > 0) return img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7').' '.$langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1').' '.$langs->trans('Bill'.$prefix.'StatusNotPayed');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return img_picto($langs->trans('BillStatusPayed'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0');
				if ($statut == 3 && $alreadypayed <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if ($statut == 3 && $alreadypayed > 0) return img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1');
				return img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				return img_picto($langs->trans('BillStatusPayed'),'statut6');
			}
		}
		if ($mode == 4)
		{
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('BillStatusDraft');
				if ($statut == 3 && $alreadypayed <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($statut == 3 && $alreadypayed > 0) return img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7').' '.$langs->trans('Bill'.$prefix.'StatusClosedPayedPartially');
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
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft').' '.img_picto($langs->trans('BillStatusDraft'),'statut0');
				if ($statut == 3 && $alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusCanceled').' '.img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if ($statut == 3 && $alreadypayed > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPayedPartially').' '.img_picto($langs->trans('BillStatusClosedPayedPartially'),'statut7');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed').' '.img_picto($langs->trans('BillStatusNotPayed'),'statut1');
				return $langs->trans('Bill'.$prefix.'StatusStarted').' '.img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed').' '.img_picto($langs->trans('BillStatusPayed'),'statut6');
			}
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
	 *      \brief     Charge les informations de l'onglet info dans l'objet facture
	 *      \param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('datec').' as datec,';
		$sql.= ' '.$this->db->pdate('date_valid').' as datev,';
		$sql.= ' fk_user_author, fk_user_valid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as c';
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
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}
				$this->date_creation     = $obj->datec;
				$this->date_validation   = $obj->datev;
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
	*   \brief      Renvoi si une facture peut etre supprimée complètement
    *				La règle est la suivante:
    *				Si facture dernière, non provisoire, sans paiement et non exporté en compta -> oui fin de règle
    *       Si facture brouillon et provisoire -> oui
    *   \param      user        Utilisateur créant la demande
    *   \return     int         <0 si ko, 0=non, 1=oui
	*/
	function is_erasable()
	{
		global $conf, $db;

		// on vérifie si la facture est en numérotation provisoire
    $facref = substr($this->ref, 1, 4);

		// Si facture non brouillon et non provisoire
		if ($facref != PROV && $conf->compta->enabled && $conf->global->FACTURE_ENABLE_EDITDELETE)
		{
			// On ne peut supprimer que la dernière facture validée
			// pour ne pas avoir de trou dans la numérotation
			$sql = "SELECT MAX(facnumber)";
			$sql.= " FROM ".MAIN_DB_PREFIX."facture";

			$resql=$db->query($sql);
			if ($resql)
			{
				$maxfacnumber = $db->fetch_row($resql);
			}

			// On vérifie si les lignes de factures ont été exportées en compta et/ou ventilées
			$ventilExportCompta = 0 ;
			for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
			{
				if ($this->lignes[$i]->export_compta <> 0 && $this->lignes[$i]->code_ventilation <> 0)
				{
					$ventilExportCompta++;
				}
			}

			// Si derniere facture et si non ventilée, on peut supprimer
			if ($maxfacnumber[0] == $this->ref && $ventilExportCompta == 0)
			{
				return 1;
			}
		}
		else if ($this->statut == 0 && $facref == PROV) // Si facture brouillon et provisoire
		{
			return 1;
		}
		
		return 0;
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
	 *      \brief      Stocke un numéro de rang pour toutes les lignes de
	 *                  detail d'une facture qui n'en ont pas.
	 */
	function line_order()
	{
		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' WHERE fk_facture='.$this->id;
		$sql.= ' AND rang = 0';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		if ($nl > 0)
		{
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facturedet';
			$sql.= ' WHERE fk_facture='.$this->id;
			$sql.= ' ORDER BY rang ASC, rowid ASC';
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
				$sql.= ' WHERE rowid = '.$li[$i];
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
		$sql.= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		if ($rang > 1 )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.$rang ;
			$sql.= ' WHERE fk_facture  = '.$this->id;
			$sql.= ' AND rang = '.($rang - 1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang  = '.($rang - 1);
				$sql.= ' WHERE rowid = '.$rowid;
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
		$sql.= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		/* Lecture du rang max de la facture */
		$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' WHERE fk_facture ='.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$max = $row[0];
		}

		if ($rang < $max )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.$rang;
			$sql.= ' WHERE fk_facture  = '.$this->id;
			$sql.= ' AND rang = '.($rang+1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet SET rang = '.($rang+1);
				$sql.= ' WHERE rowid = '.$rowid;
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


    /* gestion des contacts d'une facture */

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


	/**
	 *		\brief		Initialise la facture avec valeurs fictives aléatoire
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
		$this->date_lim_reglement=$this->date+3600*24*30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';
		$this->note_public='SPECIMEN';
		$nbp = rand(1, 9);
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$ligne=new FactureLigne($this->db);
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
		\class      FactureLigne
		\brief      Classe permettant la gestion des lignes de factures
		\remarks	Gere des lignes de la table llx_facturedet
*/
class FactureLigne
{
	var $db;
	var $error;

    // From llx_facturedet
	var $rowid;
    var $desc;          	// Description ligne
	var $fk_facture;		// Id produit prédéfini

	var $qty;				// Quantité (exemple 2)
	var $subprice;      	// P.U. HT (exemple 100)
	var $remise;			// Montant calculé de la remise % sur PU HT (exemple 20)
							// subprice = price + remise
	var $remise_percent;	// % de la remise ligne (exemple 20%)
	var $price;         	// P.U. HT apres remise % de ligne (exemple 80)
	var $tva_taux;			// Taux tva produit/service (exemple 19.6)
	var $fk_code_ventilation = 0;
	var $fk_export_compta = 0;
	var $rang = 0;

	var $date_start;
	var $date_end;

	var $info_bits = 0;		// Bit 0: 	0 si TVA normal - 1 si TVA NPR
	var $total_ht;			// Total HT  de la ligne toute quantité et incluant la remise ligne
	var $total_tva;			// Total TVA  de la ligne toute quantité et incluant la remise ligne
	var $total_ttc;			// Total TTC de la ligne toute quantité et incluant la remise ligne

    // From llx_product
    var $ref;				// Reference produit
    var $libelle;      		// Label produit
    var $product_desc;  	// Description produit

	
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
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT fd.rowid, fd.fk_facture, fd.fk_product, fd.description, fd.price, fd.qty, fd.tva_taux,';
		$sql.= ' fd.remise, fd.remise_percent, fd.fk_remise_except, fd.subprice,';
		$sql.= ' '.$this->db->pdate('fd.date_start').' as date_start,'.$this->db->pdate('fd.date_end').' as date_end,';
		$sql.= ' fd.info_bits, fd.total_ht, fd.total_tva, fd.total_ttc, fd.rang,';
		$sql.= ' fd.fk_code_ventilation, fd.fk_export_compta,';
		$sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet as fd';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
		$sql.= ' WHERE fd.rowid = '.$rowid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->rowid          = $objp->rowid;
			$this->fk_facture     = $objp->fk_facture;
			$this->desc           = $objp->description;
			$this->qty            = $objp->qty;
			$this->price          = $objp->price;
			$this->subprice       = $objp->subprice;
			$this->tva_taux       = $objp->tva_taux;
			$this->remise         = $objp->remise;
			$this->remise_percent = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->produit_id     = $objp->fk_product;
			$this->date_start     = $objp->date_start;
			$this->date_end       = $objp->date_end;
			$this->info_bits      = $objp->info_bits;
			$this->total_ht       = $objp->total_ht;
			$this->total_tva      = $objp->total_tva;
			$this->total_ttc      = $objp->total_ttc;
			$this->fk_code_ventilation = $objp->fk_code_ventilation;
			$this->fk_export_compta    = $objp->fk_export_compta;
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
	 *      \brief     	Insère l'objet ligne de facture en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function insert()
	{
		dolibarr_syslog("FactureLigne.class::insert rang=".$this->rang);
		$this->db->begin();

		$rangtouse=$this->rang;
		if ($rangtouse == -1)
		{
			// Récupère rang max de la facture dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'facturedet';
			$sql.= ' WHERE fk_facture ='.$this->fk_facture;
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
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facturedet';
		$sql.= ' (fk_facture, description, price, qty, tva_taux,';
		$sql.= ' fk_product, remise_percent, subprice, remise, fk_remise_except,';
		$sql.= ' date_start, date_end, fk_code_ventilation, fk_export_compta, ';
		$sql.= ' rang,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc)';
		$sql.= " VALUES (".$this->fk_facture.",";
		$sql.= " '".addslashes($this->desc)."',";
		$sql.= " '".price2num($this->price)."',";
		$sql.= " '".price2num($this->qty)."',";
		$sql.= " '".price2num($this->txtva)."',";
		if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
		else { $sql.='null,'; }
		$sql.= " '".price2num($this->remise_percent)."',";
		$sql.= " '".price2num($this->subprice)."',";
		$sql.= " '".price2num($this->remise)."',";
		if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
		else $sql.= 'null,';
		if ($this->date_start) { $sql.= "'".$this->date_start."',"; }
		else { $sql.='null,'; }
		if ($this->date_end)   { $sql.= "'".$this->date_end."',"; }
		else { $sql.='null,'; }
		$sql.= ' '.$this->fk_code_ventilation.',';
		$sql.= ' '.$this->fk_export_compta.',';
		$sql.= ' '.$rangtouse.',';
		$sql.= " '".$this->info_bits."',";
		$sql.= " '".price2num($this->total_ht)."',";
		$sql.= " '".price2num($this->total_tva)."',";
		$sql.= " '".price2num($this->total_ttc)."'";
		$sql.= ')';

       	dolibarr_syslog("FactureLigne.class::insert sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("FactureLigne::insert Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}
	
	
	/**
	 *      \brief     	Mise a jour de l'objet ligne de facture en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
		$sql.= " description='".addslashes($this->desc)."'";
		$sql.= ",price='".price2num($this->price)."'";
		$sql.= ",subprice='".price2num($this->subprice)."'";
		$sql.= ",remise='".price2num($this->remise)."'";
		$sql.= ",remise_percent='".price2num($this->remise_percent)."'";
		if ($this->fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
		else $sql.= ",fk_remise_except=null";
		$sql.= ",tva_taux='".price2num($this->tva_taux)."'";
		$sql.= ",qty='".price2num($this->qty)."'";
		if ($this->date_start) { $sql.= ",date_start='".$this->date_start."'"; }
		else { $sql.=',date_start=null'; }
		if ($this->date_end) { $sql.= ",date_end='".$this->date_end."'"; }
		else { $sql.=',date_end=null'; }
		$sql.= ",rang='".$this->rang."'";
		$sql.= ",info_bits='".$this->info_bits."'";
		$sql.= ",total_ht='".price2num($this->total_ht)."'";
		$sql.= ",total_tva='".price2num($this->total_tva)."'";
		$sql.= ",total_ttc='".price2num($this->total_ttc)."'";
		$sql.= " WHERE rowid = ".$this->rowid;

       	dolibarr_syslog("FactureLigne::update sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("FactureLigne::update Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}
	
	/**
	 *      \brief     	Mise a jour en base des champs total_xxx de ligne de facture
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet SET";
		$sql.= " total_ht='".price2num($this->total_ht)."'";
		$sql.= ",total_tva='".price2num($this->total_tva)."'";
		$sql.= ",total_ttc='".price2num($this->total_ttc)."'";
		$sql.= " WHERE rowid = ".$this->rowid;

       	dolibarr_syslog("FactureLigne::update_total sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("FactureLigne::update_total Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}	
}

?>
