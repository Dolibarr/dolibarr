<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/facture/facture-rec.class.php
        \ingroup    facture
        \brief      Fichier de la classe des factures recurentes
        \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT."/notify.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");


/**
        \class      FactureRec
        \brief      Classe de gestion des factures recurrentes/Modèles
*/
class FactureRec extends Facture
{
	var $db ;
	var $element='facturerec';
	var $table_element='facture_rec';
	var $table_element_line='facturedet_rec';
	var $fk_element='fk_facture';
	
	var $id ;

	var $socid;		// Id client
	var $client;		// Objet societe client (à charger par fetch_client)

    var $number;
    var $author;
    var $date;
    var $ref;
    var $amount;
    var $remise;
    var $tva;
    var $total;
    var $note;
    var $db_table;
    var $propalid;
    var $projetid;


    /**
     * 		\brief		Initialisation de la class
     *
     */
    function FactureRec($DB, $facid=0)
    {
        $this->db = $DB ;
        $this->facid = $facid;
    }
    
    /**
     * 		\brief		Créé la facture recurrente/modele
     *		\return		int			<0 si ko, id facture rec crée si ok
     */
    function create($user)
    {
    	global $langs;
    	
    	$error=0;
    	
		// Nettoyage parametere
		$this->titre=trim($this->titre);

		// Validation parameteres
		if (! $this->titre)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Title"));
			return -3;
		}

		$this->db->begin();
		
    	// Charge facture modele
    	$facsrc=new Facture($this->db);
    	$result=$facsrc->fetch($this->facid);
        if ($result > 0)
        {
            // On positionne en mode brouillon la facture
            $this->brouillon = 1;

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_rec (titre, fk_soc, datec, amount, remise, note, fk_user_author,fk_projet, fk_cond_reglement, fk_mode_reglement) ";
            $sql.= " VALUES ('$this->titre', '$facsrc->socid', ".$this->db->idate(mktime()).", '$facsrc->amount', '$facsrc->remise', '".addslashes($this->note)."','$user->id',";
            $sql.= " ".($facsrc->projetid?"'".$facsrc->projetid."'":"null").", ";
            $sql.= " '".$facsrc->cond_reglement_id."',";
            $sql.= " '".$facsrc->mode_reglement_id."')";
            if ( $this->db->query($sql) )
            {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

                /*
                 * Produits
                 */
                for ($i = 0 ; $i < sizeof($facsrc->lignes) ; $i++)
                {
                    $result_insert = $this->addline($this->id,
		                    $facsrc->lignes[$i]->desc,
		                    $facsrc->lignes[$i]->subprice,
		                    $facsrc->lignes[$i]->qty,
		                    $facsrc->lignes[$i]->tva_tx,
		                    $facsrc->lignes[$i]->fk_product,
		                    $facsrc->lignes[$i]->remise_percent);

                    if ($result_insert < 0)
                    {
                    	$error++;
                    }
                }
	
                if ($error)
                {
					$this->db->rollback();
                }
                else
                {
					$this->db->commit();
                	return $this->id;
                }
            }
            else
            {
                $this->error=$this->db->error().' sql='.$sql;
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
		\brief      Recupére l'objet facture et ses lignes de factures
		\param      rowid       id de la facture a récupérer
		\param      societe_id  id de societe
		\return     int         >0 si ok, <0 si ko
	*/
	function fetch($rowid, $societe_id=0)
	{
		dolibarr_syslog("Facture::Fetch rowid=".$rowid.", societe_id=".$societe_id, LOG_DEBUG);

		$sql = 'SELECT f.titre,f.fk_soc,f.amount,f.tva,f.total,f.total_ttc,f.remise_percent,f.remise_absolue,f.remise';
		$sql.= ','.$this->db->pdate('f.date_lim_reglement').' as dlr';
		$sql.= ', f.note, f.note_public, f.fk_user_author';
		$sql.= ', f.fk_mode_reglement, f.fk_cond_reglement';
		$sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql.= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_facture';
		$sql.= ', cf.fk_commande';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'cond_reglement as c ON f.fk_cond_reglement = c.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'co_fa as cf ON cf.fk_facture = f.rowid';
		$sql.= ' WHERE f.rowid='.$rowid;
		if ($societe_id > 0)
		{
			$sql.= ' AND f.fk_soc = '.$societe_id;
		}
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                     = $rowid;
				$this->titre                  = $obj->titre;
				$this->ref                    = $obj->facnumber;
				$this->ref_client             = $obj->ref_client;
				$this->type                   = $obj->type;
				$this->datep                  = $obj->dp;
				$this->date                   = $obj->df;
				$this->amount                 = $obj->amount;
				$this->remise_percent         = $obj->remise_percent;
				$this->remise_absolue         = $obj->remise_absolue;
				$this->remise                 = $obj->remise;
				$this->total_ht               = $obj->total;
				$this->total_tva              = $obj->tva;
				$this->total_ttc              = $obj->total_ttc;
				$this->paye                   = $obj->paye;
				$this->close_code             = $obj->close_code;
				$this->close_note             = $obj->close_note;
				$this->socid                  = $obj->fk_soc;
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
				$this->fk_facture_source      = $obj->fk_facture_source;
				$this->note                   = $obj->note;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->modelpdf               = $obj->model_pdf;
				$this->commande_id            = $obj->fk_commande;
				$this->lignes                 = array();

				if ($this->commande_id)
				{
					$sql = "SELECT ref";
					$sql.= " FROM ".MAIN_DB_PREFIX."commande";
					$sql.= " WHERE rowid = ".$this->commande_id;

					$resqlcomm = $this->db->query($sql);

					if ($resqlcomm)
					{
						$objc = $this->db->fetch_object($resqlcomm);
						$this->commande_ref = $objc->ref;
						$this->db->free($resqlcomm);
					}
				}

				if ($this->statut == 0)	$this->brouillon = 1;

				/*
				* Lignes
				*/
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->error();
					dolibarr_syslog('Facture::Fetch Error '.$this->error);
					return -3;
				}
				return 1;
			}
			else
			{
				$this->error='Bill with id '.$rowid.' not found sql='.$sql;
				dolibarr_syslog('Facture::Fetch Error '.$this->error);
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog('Facture::Fetch Error '.$this->error);
			return -1;
		}
	}


	/**
		\brief      Recupére les lignes de factures dans this->lignes
		\return     int         1 si ok, < 0 si erreur
	*/
	function fetch_lines()
	{
		$sql = 'SELECT l.rowid, l.fk_product, l.description, l.price, l.qty, l.tva_taux, ';
		$sql.= ' l.remise, l.remise_percent, l.subprice,';
		$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
		$sql.= ' p.label as label, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql.= ' WHERE l.fk_facture = '.$this->id;

		dolibarr_syslog('Facture::fetch_lines', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$faclig = new FactureLigne($this->db);
				$faclig->rowid	          = $objp->rowid;
				$faclig->desc             = $objp->description;     // Description ligne
				$faclig->libelle          = $objp->label;           // Label produit
				$faclig->product_desc     = $objp->product_desc;    // Description produit
				$faclig->qty              = $objp->qty;
				$faclig->subprice         = $objp->subprice;
				$faclig->tva_tx           = $objp->tva_taux;
				$faclig->remise_percent   = $objp->remise_percent;
				$faclig->fk_remise_except = $objp->fk_remise_except;
				$faclig->produit_id       = $objp->fk_product;
				$faclig->fk_product       = $objp->fk_product;
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

				// Ne plus utiliser
				$faclig->price            = $objp->price;
				$faclig->remise           = $objp->remise;

				$this->lignes[$i] = $faclig;
				$i++;
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog('Facture::fetch_lines: Error '.$this->error);
			return -3;
		}
	}


    /**
     * Supprime la facture
     */
    function delete($rowid)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_rec WHERE fk_facture = $rowid;";

        if ($this->db->query( $sql) )
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_rec WHERE rowid = $rowid";

            if ($this->db->query( $sql) )
            {
                return 1;
            }
            else
            {
                print "Err : ".$this->db->error();
                return -1;
            }
        }
        else
        {
            print "Err : ".$this->db->error();
            return -2;
        }
    }

 
	/**
	 *		\brief		Ajoute une ligne de facture
	 */
	function addline($facid, $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0, $price_base_type='HT', $info_bits=0)
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
		
		if ($this->brouillon)
		{
			if (strlen(trim($qty))==0)
			{
				$qty=1;
			}
			$remise = 0;
			$price = $pu;
			$subprice = $price;
			
			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			
			if (trim(strlen($remise_percent)) > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = $pu - $remise;
			}
	
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facturedet_rec (fk_facture,description,price,qty,tva_taux, fk_product, remise_percent, subprice, remise, total_ht, total_tva, total_ttc)";
			$sql .= " VALUES ('".$facid."', '".addslashes($desc)."'";
			$sql .= ",".price2num($price);
			$sql .= ",".price2num($qty);
			$sql .= ",".price2num($txtva);
			$sql .= ",".($fk_product?"'".$fk_product."'":"null");
			$sql .= ",'".price2num($remise_percent)."'";
			$sql .= ",'".price2num($subprice)."'";
			$sql .= ",'".price2num($remise)."'";
			$sql .= ",'".price2num($total_ht)."'";
			$sql .= ",'".price2num($total_tva)."'";
			$sql .= ",'".price2num($total_ttc)."') ;";
	
			dolibarr_syslog("Facture-rec::addline sql=".$sql, LOG_DEBUG);
			if ($this->db->query( $sql))
			{
				$this->id=$facid;	// \TODO A virer
				$this->update_price();
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				dolibarr_syslog("Facture-rec::addline sql=".$this->error, LOG_ERR);
				return -1;
			}
		}
	}
	
	
	/**
	 *		\brief		Rend la facture automatique
	 *
	 */
	function set_auto($user, $freq, $courant)
	{
		if ($user->rights->facture->creer)
		{
	
			$sql = "UPDATE ".MAIN_DB_PREFIX."facture_rec ";
			$sql .= " SET frequency = '".$freq."', last_gen='".$courant."'";
			$sql .= " WHERE rowid = ".$this->facid.";";
	
			$resql = $this->db->query($sql);
	
			if ($resql)
			{
				$this->frequency 	= $freq;
				$this->last_gen 	= $courant;
				return 0;
			}
			else
			{
				print $this->db->error() . ' in ' . $sql;
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}
}
?>
