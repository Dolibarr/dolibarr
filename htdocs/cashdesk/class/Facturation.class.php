<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent    <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');


/**
 * Class ot manage invoices for pos module (cashdesk)
 */
class Facturation {

	/**
	 * Attributs "volatiles" : reinitialises apres chaque traitement d'un article
	 * <p>Attributs "volatiles" : reinitialises apres chaque traitement d'un article</p>
	 * int $id			=> 'rowid' du produit dans llx_product
	 * string $ref		=> 'ref' du produit dans llx_product
	 * int $qte			=> Quantite pour le produit en cours de traitement
	 * int $stock		=> Stock theorique pour le produit en cours de traitement
	 * int $remise_percent	=> Remise en pourcent sur le produit en cours
	 * int $montant_remise	=> Remise en pourcent sur le produit en cours
	 * int $prix		=> Prix HT du produit en cours
	 * int $tva			=> 'rowid' du taux de tva dans llx_c_tva
	 */
	var $id;
	protected $ref;
	protected $qte;
	protected $stock;
	protected $remise_percent;
	protected $montant_remise;
	protected $prix;
	protected $tva;

	/**
	 * Attributs persistants : utilises pour toute la duree de la vente (jusqu'a validation ou annulation)
	 * string $num_facture	=> Numero de la facture (de la forme FAYYMM-XXXX)
	 * string $mode_reglement	=> Mode de reglement (ESP, CB ou CHQ)
	 * int $montant_encaisse	=> Montant encaisse en cas de reglement en especes
	 * int $montant_rendu	=> Monnaie rendue en cas de reglement en especes
	 * int $paiement_le		=> Date de paiement en cas de paiement differe
	 *
	 * int $prix_total_ht	=> Prix total hors taxes
	 * int $montant_tva		=> Montant total de la TVA, tous taux confondus
	 * int $prix_total_ttc	=> Prix total TTC
	 */
	protected $num_facture;
	protected $mode_reglement;
	protected $montant_encaisse;
	protected $montant_rendu;
	protected $paiement_le;

	protected $prix_total_ht;
	protected $montant_tva;
	protected $prix_total_ttc;


	/**
	 *	Constructor
	 */
	public function Facturation()
	{
		$this->raz();
		$this->raz_pers();
	}


	// Methodes de traitement des donnees

	/**
	 *  Ajout d'un article au panier
	 */
	public function ajoutArticle()
	{
		global $db;

		$sql = "SELECT taux";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_tva";
		$sql.= " WHERE rowid = ".$this->tva();

		dol_syslog("ajoutArticle sql=".$sql);
		$resql = $db->query($sql);

		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$vat_rate=$obj->taux;
			//var_dump($vat_rate);exit;
		}
		else
		{
			dol_print_error($db);
		}


		// Define part of HT, VAT, TTC
		$resultarray=calcul_price_total($this->qte,$this->prix(),$this->remise_percent(),$vat_rate,0,0,0,'HT',0);

		// Calcul du total ht sans remise
		$total_ht = $resultarray[0];
		$total_vat = $resultarray[1];
		$total_ttc = $resultarray[2];

		// Calcul du montant de la remise
		if ($this->remise_percent())
		{
			$remise_percent = $this->remise_percent();
		} else {
			$remise_percent = 0;
		}
		$montant_remise_ht = ($resultarray[6] - $resultarray[0]);
		$this->montant_remise($montant_remise_ht);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."pos_tmp (";
		$sql.= "fk_article";
		$sql.= ", qte";
		$sql.= ", fk_tva";
		$sql.= ", remise_percent";
		$sql.= ", remise";
		$sql.= ", total_ht";
		$sql.= ", total_ttc";
		$sql.= ") VALUES (";
		$sql.= $this->id();
		$sql.= ", ".$this->qte();
		$sql.= ", ".$this->tva();
		$sql.= ", ".$remise_percent;
		$sql.= ", ".price2num($montant_remise_ht);
		$sql.= ", ".price2num($total_ht,'MT');
		$sql.= ", ".price2num($total_ttc,'MT');
		$sql.= ")";

		dol_syslog("ajoutArticle sql=".$sql);
		$result = $db->query($sql);

		if (!$result)
		{
			dol_print_error($db);
		}

		$this->raz();

	}

	/**
	 *  Suppression du panier d'un article identifie par son id dans la table llx_pos_tmp
	 *  @param      aArticle
	 */
	public function supprArticle($aArticle)
	{
		global $db;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."pos_tmp";
		$sql.= " WHERE id = ".$aArticle;
		$sql.= " LIMIT 1";

		$db->query($sql);

	}

	/**
	 *  \brief    Calcul du total HT, total TTC et montants TVA
	 */
	public function calculTotaux()
	{
		global $db;

		$res = $db->query('SELECT remise, total_ht, total_ttc, taux FROM '.MAIN_DB_PREFIX.'pos_tmp as c
				LEFT JOIN '.MAIN_DB_PREFIX.'c_tva as t ON c.fk_tva = t.rowid
				ORDER BY id');

		$total_ht=0;
		$total_ttc=0;

		if ( $db->num_rows($res) ) {

			$ret=array(); $i=0;
			while ( $tab = $db->fetch_array($res) )
			{
				foreach ( $tab as $cle => $valeur )
				{
					$ret[$i][$cle] = $valeur;
				}
				$i++;
			}
			$tab=$ret;

			$tab_size=count($tab);
			for($i=0;$i < $tab_size;$i++) {

				// Total HT
				$remise = $tab[$i]['remise'];
				$total_ht += ($tab[$i]['total_ht']);
				$total_ttc += ($tab[$i]['total_ttc']);
			}

			$this->prix_total_ttc = $total_ttc;
			$this->prix_total_ht = $total_ht;

			$this->montant_tva = $total_ttc - $total_ht;
			//print $this->prix_total_ttc.'eeee'; exit;
		}

	}

	/**
	 * Reinitialisation des attributs
	 */
	public function raz ()
	{
		$this->id('RESET');
		$this->ref('RESET');
		$this->qte('RESET');
		$this->stock('RESET');
		$this->remise_percent('RESET');
		$this->montant_remise('RESET');
		$this->prix('RESET');
		$this->tva('RESET');

	}

	/**
	 * Reinitialisation des attributs persistants
	 */
	public function raz_pers ()
	{
		$this->num_facture('RESET');
		$this->mode_reglement('RESET');
		$this->montant_encaisse('RESET');
		$this->montant_rendu('RESET');
		$this->paiement_le('RESET');

		$this->prix_total_ht('RESET');
		$this->montant_tva('RESET');
		$this->prix_total_ttc('RESET');

	}


	// Methodes de modification des attributs proteges

	/**
	 * Getter for id
	 * @param      aId
	 * @return     id
	 */
	public function id ( $aId=null )
	{

		if ( !$aId ) {

			return $this->id;

		} else if ( $aId == 'RESET' ) {

			$this->id = NULL;

		} else {

			$this->id = $aId;

		}

	}

	/**
	 * Getter for ref
	 * @param $aRef
	 */
	public function ref ( $aRef=null ) {

		if ( !$aRef ) {

			return $this->ref;

		} else if ( $aRef == 'RESET' ) {

			$this->ref = NULL;

		} else {

			$this->ref = $aRef;

		}

	}

	/**
	 * Getter for qte
	 * @param $aQte
	 * @return
	 */
	public function qte ( $aQte=null ) {

		if ( !$aQte ) {

			return $this->qte;

		} else if ( $aQte == 'RESET' ) {

			$this->qte = NULL;

		} else {

			$this->qte = $aQte;

		}

	}

	/**
	 * Getter for stock
	 * @param      aStock
	 * @return
	 */
	public function stock ( $aStock=null )
	{

		if ( !$aStock ) {

			return $this->stock;

		} else if ( $aStock == 'RESET' ) {

			$this->stock = NULL;

		} else {

			$this->stock = $aStock;

		}

	}

	/**
	 * Getter for remise_percent
	 * @param      aRemisePercent
	 * @return
	 */
	public function remise_percent ( $aRemisePercent=null )
	{

		if ( !$aRemisePercent ) {

			return $this->remise_percent;

		} else if ( $aRemisePercent == 'RESET' ) {

			$this->remise_percent = NULL;

		} else {

			$this->remise_percent = $aRemisePercent;

		}

	}

	/**
	 * Getter for montant_remise
	 * @param      aMontantRemise
	 * @return
	 */
	public function montant_remise ( $aMontantRemise=null ) {

		if ( !$aMontantRemise ) {

			return $this->montant_remise;

		} else if ( $aMontantRemise == 'RESET' ) {

			$this->montant_remise = NULL;

		} else {

			$this->montant_remise = $aMontantRemise;

		}

	}

	/**
	 * Getter for prix
	 * @param      aPrix
	 * @return
	 */
	public function prix ( $aPrix=null )
	{

		if ( !$aPrix ) {

			return $this->prix;

		} else if ( $aPrix == 'RESET' ) {

			$this->prix = NULL;

		} else {

			$this->prix = $aPrix;

		}

	}

	/**
	 * Getter for tva
	 * @param      aTva
	 * @return
	 */
	public function tva ( $aTva=null )
	{

		if ( !$aTva ) {

			return $this->tva;

		} else if ( $aTva == 'RESET' ) {

			$this->tva = NULL;

		} else {

			$this->tva = $aTva;

		}

	}

	public function num_facture ( $aNumFacture=null )
	{

		if ( !$aNumFacture ) {

			return $this->num_facture;

		} else if ( $aNumFacture == 'RESET' ) {

			$this->num_facture = NULL;

		} else {

			$this->num_facture = $aNumFacture;

		}

	}

	public function mode_reglement ( $aModeReglement=null )
	{

		if ( !$aModeReglement ) {

			return $this->mode_reglement;

		} else if ( $aModeReglement == 'RESET' ) {

			$this->mode_reglement = NULL;

		} else {

			$this->mode_reglement = $aModeReglement;

		}

	}

	public function montant_encaisse ( $aMontantEncaisse=null )
	{

		if ( !$aMontantEncaisse ) {

			return $this->montant_encaisse;

		} else if ( $aMontantEncaisse == 'RESET' ) {

			$this->montant_encaisse = NULL;

		} else {

			$this->montant_encaisse = $aMontantEncaisse;

		}

	}

	public function montant_rendu ( $aMontantRendu=null )
	{

		if ( !$aMontantRendu ) {

			return $this->montant_rendu;
		} else if ( $aMontantRendu == 'RESET' ) {

			$this->montant_rendu = NULL;

		} else {

			$this->montant_rendu = $aMontantRendu;

		}

	}

	public function paiement_le ( $aPaiementLe=null )
	{

		if ( !$aPaiementLe ) {

			return $this->paiement_le;

		} else if ( $aPaiementLe == 'RESET' ) {

			$this->paiement_le = NULL;

		} else {

			$this->paiement_le = $aPaiementLe;

		}

	}

	public function prix_total_ht ( $aTotalHt=null )
	{

		if ( !$aTotalHt ) {

			return $this->prix_total_ht;

		} else if ( $aTotalHt == 'RESET' ) {

			$this->prix_total_ht = NULL;

		} else {

			$this->prix_total_ht = $aTotalHt;

		}

	}

	public function montant_tva ( $aMontantTva=null )
	{

		if ( !$aMontantTva ) {

			return $this->montant_tva;

		} else if ( $aMontantTva == 'RESET' ) {

			$this->montant_tva = NULL;

		} else {

			$this->montant_tva = $aMontantTva;

		}

	}

	public function prix_total_ttc ( $aTotalTtc=null )
	{

		if ( !$aTotalTtc ) {

			return $this->prix_total_ttc;

		} else if ( $aTotalTtc == 'RESET' ) {

			$this->prix_total_ttc = NULL;

		} else {

			$this->prix_total_ttc = $aTotalTtc;

		}

	}

}

?>
