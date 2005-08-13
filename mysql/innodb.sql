--
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
--
-- Migration des tables au format innodb
--
alter table llx_co_fa type=INNODB;

alter table llx_co_pr type=INNODB;

alter table llx_c_actioncomm type=INNODB;

alter table llx_c_chargesociales type=INNODB;

alter table llx_c_effectif type=INNODB;

alter table llx_c_paiement  type=INNODB;

alter table llx_c_prestatype  type=INNODB;

alter table llx_c_propalst  type=INNODB;

alter table llx_c_stcomm type=INNODB;

alter table llx_c_typent  type=INNODB;

alter table llx_action_def type=INNODB;

alter table llx_actioncomm type=INNODB;

alter table llx_adherent type=INNODB;

alter table llx_adherent_type type=INNODB;

alter table llx_adherent_options type=INNODB;

alter table llx_adherent_options_label type=INNODB;

alter table llx_album type=INNODB;

alter table llx_album_to_groupart  type=INNODB;

alter table llx_appro  type=INNODB;

alter table llx_auteur type=INNODB;

alter table llx_bank type=INNODB;

alter table llx_bank_account type=INNODB;

alter table llx_bank_categ type=INNODB;

alter table llx_bank_class type=INNODB;

alter table llx_bank_url type=INNODB;

alter table llx_birthday_alert type=INNODB;

alter table llx_bookmark type=INNODB;

alter table llx_boxes type=INNODB;

alter table llx_boxes_def type=INNODB;

alter table llx_chargesociales type=INNODB;

alter table llx_commande type=INNODB;

alter table llx_commandedet type=INNODB;

alter table llx_commande_fournisseur type=INNODB;

alter table llx_commande_fournisseur_log type=INNODB;

alter table llx_commande_fournisseurdet type=INNODB;

alter table llx_compta type=INNODB;

alter table llx_compta_account type=INNODB;

alter table llx_concert  type=INNODB;

alter table llx_cond_reglement  type=INNODB;

alter table llx_const type=INNODB;

alter table llx_contact_facture type=INNODB;

alter table llx_cotisation type=INNODB;

alter table llx_deplacement type=INNODB;

alter table llx_domain  type=INNODB;

alter table llx_don type=INNODB;

alter table llx_don_projet type=INNODB;

alter table llx_editeur type=INNODB;

alter table llx_entrepot type=INNODB;

alter table llx_expedition type=INNODB;

alter table llx_expedition_methode type=INNODB;

alter table llx_expeditiondet type=INNODB;

alter table llx_fa_pr type=INNODB;

alter table llx_facture_rec type=INNODB;

alter table llx_facturedet type=INNODB;

alter table llx_facturedet_rec type=INNODB;

alter table llx_facture_fourn type=INNODB;

alter table llx_facture_fourn_det type=INNODB;

alter table llx_fichinter type=INNODB;

alter table llx_groupart type=INNODB;

alter table llx_lieu_concert  type=INNODB;

alter table llx_livre type=INNODB;

alter table llx_livre_to_auteur  type=INNODB;

alter table llx_newsletter type=INNODB;

alter table llx_notify  type=INNODB;

alter table llx_notify_def type=INNODB;

alter table llx_paiementfourn type=INNODB;

alter table llx_pointmort type=INNODB;

alter table llx_product_fournisseur type=INNODB;

alter table llx_product_price type=INNODB;

alter table llx_product_stock type=INNODB;

alter table llx_propal_model_pdf type=INNODB;

alter table llx_propaldet type=INNODB;

alter table llx_rights_def type=INNODB;

alter table llx_service type=INNODB;

alter table llx_societe_ca type=INNODB;

alter table llx_societe_details type=INNODB;

alter table llx_societe_prestation type=INNODB;

alter table llx_societe_reference type=INNODB;

alter table llx_societe_remise type=INNODB;

alter table llx_societe_remise_except type=INNODB;

alter table llx_societe_rib type=INNODB;

alter table llx_societe_techno type=INNODB;

alter table llx_socpeople type=INNODB;

alter table llx_soc_events type=INNODB;

alter table llx_soc_recontact type=INNODB;

alter table llx_socstatutlog  type=INNODB;

alter table llx_stock type=INNODB;

alter table llx_stock_mouvement type=INNODB;

alter table llx_sqltables type=INNODB;

alter table llx_todocomm  type=INNODB;

alter table llx_transaction_bplc  type=INNODB;

alter table llx_tva type=INNODB;

alter table llx_user_rights type=INNODB;

alter table llx_ventes type=INNODB;

alter table llx_voyage type=INNODB;

alter table llx_voyage_reduc type=INNODB;

alter table llx_facture_tva_sum type=INNODB;

alter table llx_c_accountingsystem type=INNODB;

alter table llx_c_ape type=INNODB;

alter table llx_c_civilite type=INNODB;

alter table llx_c_currencies type=INNODB;

alter table llx_c_departements type=INNODB;

alter table llx_c_forme_juridique type=INNODB;

alter table llx_c_prestation type=INNODB;

alter table llx_c_regions type=INNODB;

alter table llx_c_techno type=INNODB;

alter table llx_catalog_societe type=INNODB;

alter table llx_catalogsoc type=INNODB;

alter table llx_groupesociete type=INNODB;
alter table llx_groupesociete_remise type=INNODB;
alter table llx_mailing type=INNODB;
alter table llx_mailing_cibles type=INNODB;
alter table llx_paiement_facture type=INNODB;
alter table llx_paiementcharge type=INNODB;
alter table llx_so_gr type=INNODB;

alter table llx_user_alert type=INNODB;
alter table llx_user_param type=INNODB;

alter table llx_bookmark4u_login type=INNODB;
alter table llx_c_methode_commande_fournisseur type=INNODB;
alter table llx_compta_compte_generaux type=INNODB;
alter table llx_contratdet type=INNODB;
alter table llx_contratdet_log type=INNODB;
alter table llx_export_compta type=INNODB;

alter table llx_prelevement_notifications type=INNODB;
alter table llx_prelevement_facture type=INNODB;
alter table llx_prelevement_facture_demande type=INNODB;
alter table llx_prelevement_rejet type=INNODB;
alter table llx_prelevement_lignes type=INNODB;
alter table llx_prelevement_bons type=INNODB;
alter table llx_prelevement type=INNODB;
alter table llx_product_fournisseur_price type=INNODB;
alter table llx_societe_commerciaux type=INNODB;
alter table llx_user_clicktodial type=INNODB;
alter table llx_usergroup_rights type=INNODB;
alter table llx_usergroup_user type=INNODB;
alter table llx_usergroup type=INNODB;


alter table llx_cash type=INNODB;
alter table llx_cash_account type=INNODB;
alter table llx_contrat_service type=INNODB;
alter table llx_telephonie_client_statistique type=INNODB;
alter table llx_telephonie_client_stats type=INNODB;
alter table llx_telephonie_client_stats_mensuel type=INNODB;
alter table llx_telephonie_commande type=INNODB;
alter table llx_telephonie_commande_retour type=INNODB;
alter table llx_telephonie_communications_details type=INNODB;
alter table llx_telephonie_concurrents type=INNODB;
alter table llx_telephonie_contact_facture type=INNODB;
alter table llx_telephonie_contrat type=INNODB;
alter table llx_telephonie_contrat_contact_facture type=INNODB;
alter table llx_telephonie_facture type=INNODB;
alter table llx_telephonie_fournisseur type=INNODB;
alter table llx_telephonie_groupe_ligne type=INNODB;
alter table llx_telephonie_groupeligne type=INNODB;
alter table llx_telephonie_import_cdr type=INNODB;
alter table llx_telephonie_ligne_statistique type=INNODB;
alter table llx_telephonie_prefix type=INNODB;
alter table llx_telephonie_service type=INNODB;
alter table llx_telephonie_simul type=INNODB;
alter table llx_telephonie_simul_comm type=INNODB;
alter table llx_telephonie_societe_ligne type=INNODB;
alter table llx_telephonie_societe_ligne_remise type=INNODB;
alter table llx_telephonie_societe_ligne_statut type=INNODB;
alter table llx_telephonie_tarif type=INNODB;
alter table llx_telephonie_tarif_client type=INNODB;
alter table llx_telephonie_tarif_fournisseur type=INNODB;

alter table llx_categorie_association type=INNODB;
alter table llx_categorie_product type=INNODB;
alter table llx_product_fournisseur_price_log type=INNODB;
alter table llx_categorie type=INNODB;

alter table llx_accountingsystem_det type=INNODB;

alter table llx_accountingsystem type=INNODB;

alter table llx_energie_compteur_releve type=INNODB;
alter table llx_energie_compteur_groupe type=INNODB;
alter table llx_energie_compteur type=INNODB;
alter table llx_energie_groupe type=INNODB;
alter table llx_dolibarr_modules type=INNODB;

alter table llx_energie_compteur_groupe type=INNODB;

alter table llx_energie_compteur_releve type=INNODB;

alter table llx_energie_groupe type=INNODB; 

alter table llx_energie_compteur type=INNODB;

alter table llx_propal type=INNODB;

alter table llx_product type=INNODB;

alter table llx_facture type=INNODB;

alter table llx_contrat type=INNODB;

alter table llx_paiement type=INNODB;

alter table llx_c_pays  type=INNODB;

alter table llx_projet type=INNODB;

alter table llx_societe type=INNODB;

alter table llx_user type=INNODB;

-- historique

alter table c_actioncomm type=INNODB;
alter table c_chargesociales type=INNODB;
alter table c_effectif type=INNODB;
alter table c_paiement type=INNODB;
alter table c_pays type=INNODB;
alter table c_propalst type=INNODB;
alter table c_stcomm type=INNODB;
alter table c_typent type=INNODB;
