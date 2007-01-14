<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/facture/facture-rec.class.php
        \ingroup    facture
        \brief      Fichier de la classe des factures recurentes
        \version    $Revision$
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
	var $element='facture';

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
    	
		// Nettoyage parametere
		$this->titre=trim($this->titre);

		// Validation parameteres
		if (! $this->titre)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Title"));
			return -3;
		}

    	// Charge facture modele
    	$facsrc=new Facture($this->db);
    	$result=$facsrc->fetch($this->facid);
        if ($result > 0)
        {
            // On positionne en mode brouillon la facture
            $this->brouillon = 1;

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_rec (titre, fk_soc, datec, amount, remise, remise_percent, note, fk_user_author,fk_projet, fk_cond_reglement, fk_mode_reglement) ";
            $sql.= " VALUES ('$this->titre', '$facsrc->socid', now(), '$facsrc->amount', '$facsrc->remise', '$facsrc->remise_percent', '".addslashes($this->note)."','$user->id',";
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
                    if ($facsrc->lignes[$i]->produit_id > 0)
                    {
                        $prod = new Product($this->db);
                        $prod->fetch($facsrc->lignes[$i]->produit_id);
                    }

                    $result_insert = $this->addline($this->id,
                    addslashes($facsrc->lignes[$i]->desc),
                    $facsrc->lignes[$i]->subprice,
                    $facsrc->lignes[$i]->qty,
                    $facsrc->lignes[$i]->tva_tx,
                    $facsrc->lignes[$i]->produit_id,
                    $facsrc->lignes[$i]->remise_percent);


                    if ( $result_insert < 0)
                    {
                        $this->error=$this->db->error().' sql='.$sql;
                    }
                }

                return $this->id;
            }
            else
            {
                $this->error=$this->db->error().' sql='.$sql;
                return -2;
            }
        }
        else
        {
            return -1;
        }
    }

    /**
     * Recupére l'objet facture
     */
    function fetch($rowid, $societe_id=0)
    {

        $sql = "SELECT f.fk_soc,f.titre,f.amount,f.tva,f.total,f.total_ttc,f.remise,f.remise_percent,f.fk_projet, c.rowid as crid, c.libelle, c.libelle_facture, f.note, f.fk_user_author, f.fk_mode_reglement";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture_rec as f, ".MAIN_DB_PREFIX."cond_reglement as c";
        $sql .= " WHERE f.rowid=$rowid AND c.rowid = f.fk_cond_reglement";

        if ($societe_id > 0)
        {
            $sql .= " AND f.fk_soc = ".$societe_id;
        }

        if ($this->db->query($sql) )
        {
            if ($this->db->num_rows())
            {
                $obj = $this->db->fetch_object();

                $this->id                 = $rowid;
                $this->datep              = $obj->dp;
                $this->titre              = $obj->titre;
                $this->amount             = $obj->amount;
                $this->remise             = $obj->remise;
                $this->total_ht           = $obj->total;
                $this->total_tva          = $obj->tva;
                $this->total_ttc          = $obj->total_ttc;
                $this->paye               = $obj->paye;
                $this->remise_percent     = $obj->remise_percent;
                $this->socid             = $obj->fk_soc;
                $this->statut             = $obj->fk_statut;
                $this->date_lim_reglement     = $obj->dlr;
                $this->mode_reglement_id      = $obj->fk_mode_reglement;
                $this->cond_reglement_id      = $obj->crid;
                $this->cond_reglement         = $obj->libelle;
                $this->cond_reglement_facture = $obj->libelle_facture;
                $this->projetid               = $obj->fk_projet;
                $this->note                   = stripslashes($obj->note);
                $this->user_author            = $obj->fk_user_author;
                $this->lignes                 = array();

                if ($this->statut == 0)
                {
                    $this->brouillon = 1;
                }

                $this->db->free();

                /*
                * Lignes
                */

                $sql = "SELECT l.fk_product,l.description, l.subprice, l.price, l.qty, l.rowid, l.tva_taux, l.remise_percent";
                $sql .= " FROM ".MAIN_DB_PREFIX."facturedet_rec as l WHERE l.fk_facture = ".$this->id." ORDER BY l.rowid ASC";

                $result = $this->db->query($sql);
                if ($result)
                {
                    $num = $this->db->num_rows();
                    $i = 0; $total = 0;

                    while ($i < $num)
                    {
                        $objp = $this->db->fetch_object($result);
                        $faclig = new FactureLigne($this->db);
                        $faclig->produit_id     = $objp->fk_product;
                        $faclig->desc           = $objp->description;
                        $faclig->qty            = $objp->qty;
                        $faclig->price          = $objp->price;
                        $faclig->subprice       = $objp->subprice;
                        $faclig->tva_tx         = $objp->tva_taux;
                        $faclig->remise_percent = $objp->remise_percent;
                        $this->lignes[$i]       = $faclig;
                        $i++;
                    }

                    $this->db->free();

                    return 1;
                }
                else
                {
                    print $this->db->error();
                    return -1;
                }
            }
            else
            {
                print "Error";
                return -2;
            }
        }
        else
        {
            print $this->db->error();
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
	function addline($facid, $desc, $pu, $qty, $txtva, $fk_product='NULL', $remise_percent=0)
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
		
		if ($this->brouillon)
		{
			if (strlen(trim($qty))==0)
			{
				$qty=1;
			}
			$remise = 0;
			$price = round(price2num($pu), 2);
			$subprice = $price;
			
			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			
			if (trim(strlen($remise_percent)) > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = $pu - $remise;
			}
	
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facturedet_rec (fk_facture,description,price,qty,tva_taux, fk_product, remise_percent, subprice, remise, total_ht, total_tva, total_ttc)";
			$sql .= " VALUES ('$facid', '$desc'";
			$sql .= ",".price2num($price);
			$sql .= ",".price2num($qty);
			$sql .= ",".price2num($txtva);
			$sql .= ",'".$fk_product."'";
			$sql .= ",'".price2num($remise_percent)."'";
			$sql .= ",'".price2num($subprice)."'";
			$sql .= ",'".price2num($remise)."'";
			$sql .= ",'".price2num($total_ht)."'";
			$sql .= ",'".price2num($total_tva)."'";
			$sql .= ",'".price2num($total_ttc)."') ;";
	
			if ( $this->db->query( $sql) )
			{
				$this->update_price($facid);
				return 1;
			}
			else
			{
				print "$sql";
				return -1;
			}
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

        // Liste des lignes factures a sommer (Ne plus utiliser price)
		$sql = 'SELECT qty, tva_taux, subprice, remise_percent, price,';
		$sql.= ' total_ht, total_tva, total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec';
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

				$this->total_ht       += $obj->total_ht;
				$this->total_tva      += ($obj->total_ttc - $obj->total_ht);
				$this->total_ttc      += $obj->total_ttc;

				// Ne plus utiliser amount, ni remise
				$this->amount_ht      += ($obj->price * $obj->qty);
				$this->total_remise   += 0;		// Plus de remise globale (toute remise est sur une ligne)
				$tvas[$obj->tva_taux] += ($obj->total_ttc - $obj->total_ht);
				$i++;
			}

			$this->db->free($resql);

			// Met a jour indicateurs sur facture
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_rec ';
			$sql .= "SET amount ='".price2num($this->amount_ht)."'";
			$sql .= ", remise='".   price2num($this->total_remise)."'";
			$sql .= ", total='".    price2num($this->total_ht)."'";
			$sql .= ", tva='".      price2num($this->total_tva)."'";
			$sql .= ", total_ttc='".price2num($this->total_ttc)."'";
			$sql .= ' WHERE rowid = '.$facid;
			$resql=$this->db->query($sql);

		}
		else
		{
			dolibarr_print_error($this->db);
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
