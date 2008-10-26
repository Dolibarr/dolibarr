<?php
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
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
		* Attributs "volatiles" : réinitialisés après chaque traitement d'un article
		* <p>Attributs "volatiles" : réinitialisés après chaque traitement d'un article</p>
		* @var int $id			=> 'rowid' du produit dans llx_product
		* @var string $ref		=> 'ref' du produit dans llx_product
		* @var int $qte			=> Quantité pour le produit en cours de traitement
		* @var int $stock		=> Stock théorique pour le produit en cours de traitement
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
		* Attributs persistants : utilisés pour toute la durée de la vente (jusqu'à validation ou annulation)
		* @var string $num_facture	=> Numéro de la facture (de la forme FAYYMM-XXXX)
		* @var string $mode_reglement	=> Mode de réglement (ESP, CB ou CHQ)
		* @var int $montant_encaisse	=> Montant encaissé en cas de réglement en espèces
		* @var int $montant_rendu	=> Monnaie rendue en cas de réglement en espèces
		* @var int $paiement_le		=> Date de paiement en cas de paiement différé
		*
		* @var int $prix_total_ht	=> Prix total hors taxes
		* @var int $montant_tva		=> Montant total de la TVA, tous taux confondus
		* @var int $montant_tva_19_6	=> Montant de la TVA à 19.6%
		* @var int $montant_tva_5_5	=> Montant de la TVA à 5.5%
		* @var int $prix_total_ttc	=> Prix total TTC
		*/
		protected $num_facture;
		protected $mode_reglement;
		protected $montant_encaisse;
		protected $montant_rendu;
		protected $paiement_le;

		protected $prix_total_ht;
		protected $montant_tva;
		protected $montant_tva_19_6;
		protected $montant_tva_5_5;
		protected $prix_total_ttc;


		public function __construct () {

			$this->raz();
			$this->raz_pers();
		}


		// Méthodes de traitement des données

		/**
		* Ajout d'un article au panier
		*/
		public function ajoutArticle () {
			global $conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base;
			
			$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);

			$tab_tva = $sql->fetchFirst ( $sql->query ('SELECT taux FROM '.MAIN_DB_PREFIX.'c_tva WHERE rowid = '.$this->tva().';') );

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

			$sql->query('
				INSERT INTO '.MAIN_DB_PREFIX.'tmp_caisse (
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
					'.price2num($total_ttc).')');

			// On modifie les totaux
			$this->calculTotaux();

			$this->raz();

		}

		/**
		* Suppression du panier d'un article identifié par son id dans la table llx_tmp_caisse
		*/
		public function supprArticle ($aArticle) {
			global $conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base;

			$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);

			$sql->query('DELETE FROM '.MAIN_DB_PREFIX.'tmp_caisse WHERE id = '.$aArticle.' LIMIT 1');

		}

		/**
		* Calcul du total HT, total TTC et montants TVA par types
		*/
		public function calculTotaux () {
			global $conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base;

			$sql = new Sql ($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_base);

			// Incrémentation des compteurs
			$res = $sql->query ('SELECT remise, total_ht, taux FROM '.MAIN_DB_PREFIX.'tmp_caisse as c
				LEFT JOIN '.MAIN_DB_PREFIX.'c_tva as t ON c.fk_tva = t.rowid
				ORDER BY id');

			$total_tva_19_6 = 0;
			$total_tva_5_5 = 0;
			$total_tva_0 = 0;
			if ( $sql->numRows($res) ) {

				$tab = $sql->fetchAll($res);

				for ( $i = 0; $i < count($tab); $i++ ) {

					// Total HT
					$remise = $tab[$i]['remise'];
					$total = ($tab[$i]['total_ht'] - $remise);

					// Calcul des totaux HT par taux de tva
					if ( $tab[$i]['taux'] == '19.6' ) {

						$total_tva_19_6 += $total;

					} elseif ( $tab[$i]['taux'] == '5.5' ) {

						$total_tva_5_5 += $total;

					} else {

						$total_tva_0 += $total;

					}

				}

				$this->prix_total_ht = $total_tva_0 + $total_tva_19_6 + $total_tva_5_5;

				$total_ttc_19_6 = round ( ($total_tva_19_6 * 1.196), 2 );
				$total_ttc_5_5 = round ( ($total_tva_5_5 * 1.055), 2 );
				$this->prix_total_ttc = $total_ttc_19_6 + $total_ttc_5_5 + $total_tva_0;

				$this->montant_tva_19_6 = ($total_ttc_19_6 - $total_tva_19_6);
				$this->montant_tva_5_5 = ($total_ttc_5_5 - $total_tva_5_5);
				$this->montant_tva = ($this->montant_tva_19_6 + $this->montant_tva_5_5);

			}

		}

		/**
		* Réinitialisation des attributs
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
		* Réinitialisation des attributs persistants
		*/
		public function raz_pers () {

			$this->num_facture ('RESET');
			$this->mode_reglement ('RESET');
			$this->montant_encaisse ('RESET');
			$this->montant_rendu ('RESET');
			$this->paiement_le ('RESET');

			$this->prix_total_ht ('RESET');
			$this->montant_tva ('RESET');
			$this->montant_tva_19_6 ('RESET');
			$this->montant_tva_5_5 ('RESET');
			$this->prix_total_ttc ('RESET');

		}


		// Méthodes de modification des attributs protégés
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

		public function montant_tva_19_6 ( $aMontantTva=null ) {

			if ( !$aMontantTva ) {

				return $this->montant_tva_19_6;

			} else if ( $aMontantTva == 'RESET' ) {

				$this->montant_tva_19_6 = NULL;

			} else {

				$this->montant_tva_19_6 = $aMontantTva;

			}

		}

		public function montant_tva_5_5 ( $aMontantTva=null ) {

			if ( !$aMontantTva ) {

				return $this->montant_tva_5_5;

			} else if ( $aMontantTva == 'RESET' ) {

				$this->montant_tva_5_5 = NULL;

			} else {

				$this->montant_tva_5_5 = $aMontantTva;

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
