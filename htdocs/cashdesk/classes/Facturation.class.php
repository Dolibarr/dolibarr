<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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

class Facturation {

	/**
		* Attributs "volatiles" : r�initialis�s apr�s chaque traitement d'un article
		* <p>Attributs "volatiles" : r�initialis�s apr�s chaque traitement d'un article</p>
		* @var int $id			=> 'rowid' du produit dans llx_product
		* @var string $ref		=> 'ref' du produit dans llx_product
		* @var int $qte			=> Quantit� pour le produit en cours de traitement
		* @var int $stock		=> Stock th�orique pour le produit en cours de traitement
		* @var int $remise_percent	=> Remise en pourcent sur le produit en cours
		* @var int $montant_remise	=> Remise en pourcent sur le produit en cours
		* @var int $prix		=> Prix HT du produit en cours
		* @var int $tva			=> 'rowid' du taux de tva dans llx_c_tva
		*/
	protected $id;
	protected $ref;
	protected $qte;
	protected $stock;
	protected $remise_percent;
	protected $montant_remise;
	protected $prix;
	protected $tva;

	/**
		* Attributs persistants : utilis�s pour toute la dur�e de la vente (jusqu'� validation ou annulation)
		* @var string $num_facture	=> Num�ro de la facture (de la forme FAYYMM-XXXX)
		* @var string $mode_reglement	=> Mode de r�glement (ESP, CB ou CHQ)
		* @var int $montant_encaisse	=> Montant encaiss� en cas de r�glement en esp�ces
		* @var int $montant_rendu	=> Monnaie rendue en cas de r�glement en esp�ces
		* @var int $paiement_le		=> Date de paiement en cas de paiement diff�r�
		*
		* @var int $prix_total_ht	=> Prix total hors taxes
		* @var int $montant_tva		=> Montant total de la TVA, tous taux confondus
		* @var int $prix_total_ttc	=> Prix total TTC
		*/
	protected $num_facture;
	protected $mode_reglement;
	protected $montant_encaisse;
	protected $montant_rendu;
	protected $paiement_le;

	protected $prix_total_ht;
	protected $montant_tva;
	protected $prix_total_ttc;


	public function __construct () {

		$this->raz();
		$this->raz_pers();
	}


	// Methodes de traitement des donnees

	/**
		* Ajout d'un article au panier
		*/
	public function ajoutArticle () {
//		global $conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base;
//		$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);

		global $sql;
		$req='SELECT taux FROM '.MAIN_DB_PREFIX.'c_tva WHERE rowid = '.$this->tva();
		dol_syslog("ajoutArticle sql=".$req);
		$resql=$sql->query ();

		$tab_tva = $sql->fetch_array($resql);
		$ret=array();
		foreach ( $tab_tva as $cle => $valeur )
		{
			$ret[$cle] = $valeur;
		}
		$tab_tva=$ret;
//		var_dump($tab_tva);exit;

		// TODO Mettre methode de calcul arrondi TVA de Dolibarr

		// Calcul du total ht sans remise
		$total_ht = ( $this->qte * $this->prix() );
		// Calcul du montant de la remise
		if ( $this->remise_percent() ) {

			$remise_percent = $this->remise_percent();

		} else {

			$remise_percent = 0;

		}
		$montant_remise = $total_ht * $remise_percent / 100;
		$this->montant_remise ($montant_remise);
		// Calcul du total ttc
		$total_ttc = ($total_ht - $montant_remise) * (($tab_tva['taux'] / 100) + 1);


		$req='INSERT INTO '.MAIN_DB_PREFIX.'tmp_caisse (
					fk_article,
					qte,
					fk_tva,
					remise_percent,
					remise,
					total_ht,
					total_ttc
				) VALUES (
					'.$this->id().',
					'.$this->qte().',
					'.$this->tva().',
					'.$remise_percent.',
					'.price2num($montant_remise).',
					'.price2num($total_ht).',
					'.price2num($total_ttc).')';
		dol_syslog("ajoutArticle sql=".$req);
		$sql->query($req);

		$this->raz();

	}

	/**
		* Suppression du panier d'un article identifi� par son id dans la table llx_tmp_caisse
		*/
	public function supprArticle ($aArticle) {
//		global $conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base;
//		$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);

		global $sql;
		$sql->query('DELETE FROM '.MAIN_DB_PREFIX.'tmp_caisse WHERE id = '.$aArticle.' LIMIT 1');

	}

	/**
		* Calcul du total HT, total TTC et montants TVA
		*/
	public function calculTotaux () {
//		global $conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base;
//		$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);

		global $sql;
		$res = $sql->query ('SELECT remise, total_ht, total_ttc, taux FROM '.MAIN_DB_PREFIX.'tmp_caisse as c
				LEFT JOIN '.MAIN_DB_PREFIX.'c_tva as t ON c.fk_tva = t.rowid
				ORDER BY id');

		$total_ht=0;
		$total_ttc=0;

		if ( $sql->num_rows($res) ) {

			$ret=array(); $i=0;
			while ( $tab = $sql->fetch_array($res) )
			{
				foreach ( $tab as $cle => $valeur )
				{
					$ret[$i][$cle] = $valeur;
				}
				$i++;
			}
			$tab=$ret;

			for ( $i = 0; $i < count($tab); $i++ ) {

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
		* R�initialisation des attributs
		*/
	public function raz () {

		$this->id ('RESET');
		$this->ref ('RESET');
		$this->qte ('RESET');
		$this->stock ('RESET');
		$this->remise_percent ('RESET');
		$this->montant_remise ('RESET');
		$this->prix ('RESET');
		$this->tva ('RESET');

	}

	/**
		* R�initialisation des attributs persistants
		*/
	public function raz_pers () {

		$this->num_facture ('RESET');
		$this->mode_reglement ('RESET');
		$this->montant_encaisse ('RESET');
		$this->montant_rendu ('RESET');
		$this->paiement_le ('RESET');

		$this->prix_total_ht ('RESET');
		$this->montant_tva ('RESET');
		$this->prix_total_ttc ('RESET');

	}


	// Methodes de modification des attributs proteges
	public function id ( $aId=null ) {

		if ( !$aId ) {

			return $this->id;

		} else if ( $aId == 'RESET' ) {

			$this->id = NULL;

		} else {

			$this->id = $aId;

		}

	}

	public function ref ( $aRef=null ) {

		if ( !$aRef ) {

			return $this->ref;

		} else if ( $aRef == 'RESET' ) {

			$this->ref = NULL;

		} else {

			$this->ref = $aRef;

		}

	}

	public function qte ( $aQte=null ) {

		if ( !$aQte ) {

			return $this->qte;

		} else if ( $aQte == 'RESET' ) {

			$this->qte = NULL;

		} else {

			$this->qte = $aQte;

		}

	}

	public function stock ( $aStock=null ) {

		if ( !$aStock ) {

			return $this->stock;

		} else if ( $aStock == 'RESET' ) {

			$this->stock = NULL;

		} else {

			$this->stock = $aStock;

		}

	}

	public function remise_percent ( $aRemisePercent=null ) {

		if ( !$aRemisePercent ) {

			return $this->remise_percent;

		} else if ( $aRemisePercent == 'RESET' ) {

			$this->remise_percent = NULL;

		} else {

			$this->remise_percent = $aRemisePercent;

		}

	}

	public function montant_remise ( $aMontantRemise=null ) {

		if ( !$aMontantRemise ) {

			return $this->montant_remise;

		} else if ( $aMontantRemise == 'RESET' ) {

			$this->montant_remise = NULL;

		} else {

			$this->montant_remise = $aMontantRemise;

		}

	}

	public function prix ( $aPrix=null ) {

		if ( !$aPrix ) {

			return $this->prix;

		} else if ( $aPrix == 'RESET' ) {

			$this->prix = NULL;

		} else {

			$this->prix = $aPrix;

		}

	}

	public function tva ( $aTva=null ) {

		if ( !$aTva ) {

			return $this->tva;

		} else if ( $aTva == 'RESET' ) {

			$this->tva = NULL;

		} else {

			$this->tva = $aTva;

		}

	}

	public function num_facture ( $aNumFacture=null ) {

		if ( !$aNumFacture ) {

			return $this->num_facture;

		} else if ( $aNumFacture == 'RESET' ) {

			$this->num_facture = NULL;

		} else {

			$this->num_facture = $aNumFacture;

		}

	}

	public function mode_reglement ( $aModeReglement=null ) {

		if ( !$aModeReglement ) {

			return $this->mode_reglement;

		} else if ( $aModeReglement == 'RESET' ) {

			$this->mode_reglement = NULL;

		} else {

			$this->mode_reglement = $aModeReglement;

		}

	}

	public function montant_encaisse ( $aMontantEncaisse=null ) {

		if ( !$aMontantEncaisse ) {

			return $this->montant_encaisse;

		} else if ( $aMontantEncaisse == 'RESET' ) {

			$this->montant_encaisse = NULL;

		} else {

			$this->montant_encaisse = $aMontantEncaisse;

		}

	}

	public function montant_rendu ( $aMontantRendu=null ) {

		if ( !$aMontantRendu ) {

			return $this->montant_rendu;
		} else if ( $aMontantRendu == 'RESET' ) {

			$this->montant_rendu = NULL;

		} else {

			$this->montant_rendu = $aMontantRendu;

		}

	}

	public function paiement_le ( $aPaiementLe=null ) {

		if ( !$aPaiementLe ) {

			return $this->paiement_le;

		} else if ( $aPaiementLe == 'RESET' ) {

			$this->paiement_le = NULL;

		} else {

			$this->paiement_le = $aPaiementLe;

		}

	}

	public function prix_total_ht ( $aTotalHt=null ) {

		if ( !$aTotalHt ) {

			return $this->prix_total_ht;

		} else if ( $aTotalHt == 'RESET' ) {

			$this->prix_total_ht = NULL;

		} else {

			$this->prix_total_ht = $aTotalHt;

		}

	}

	public function montant_tva ( $aMontantTva=null ) {

		if ( !$aMontantTva ) {

			return $this->montant_tva;

		} else if ( $aMontantTva == 'RESET' ) {

			$this->montant_tva = NULL;

		} else {

			$this->montant_tva = $aMontantTva;

		}

	}

	public function prix_total_ttc ( $aTotalTtc=null ) {

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
