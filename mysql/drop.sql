--
-- Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

drop table if exists llx_co_fa;

drop table if exists llx_co_pr;

drop table if exists llx_c_actioncomm;

drop table if exists llx_c_chargesociales;

drop table if exists llx_c_effectif;

drop table if exists llx_c_paiement ;

drop table if exists llx_c_pays ;

drop table if exists llx_c_prestatype ;

drop table if exists llx_c_propalst ;

drop table if exists llx_c_stcomm;

drop table if exists llx_c_typent ;

drop table if exists llx_action_def;

drop table if exists llx_actioncomm;

drop table if exists llx_adherent;

drop table if exists llx_adherent_type;

drop table if exists llx_adherent_options;

drop table if exists llx_adherent_options_label;

drop table if exists llx_album;

drop table if exists llx_album_to_groupart ;

drop table if exists llx_appro ;

drop table if exists llx_auteur;

drop table if exists llx_bank;

drop table if exists llx_bank_account;

drop table if exists llx_bank_categ;

drop table if exists llx_bank_class;

drop table if exists llx_bank_url;

drop table if exists llx_birthday_alert;

drop table if exists llx_bookmark;

drop table if exists llx_boxes;

drop table if exists llx_boxes_def;

drop table if exists llx_chargesociales;

drop table if exists llx_contrat;

drop table if exists llx_commande;

drop table if exists llx_commandedet;

drop table if exists llx_commande_fournisseur;

drop table if exists llx_commande_fournisseur_log;

drop table if exists llx_commande_fournisseurdet;

drop table if exists llx_compta;

drop table if exists llx_compta_account;

drop table if exists llx_concert ;

drop table if exists llx_cond_reglement ;

drop table if exists llx_const;

drop table if exists llx_contact_facture;

drop table if exists llx_cotisation;

drop table if exists llx_deplacement;

drop table if exists llx_domain ;

drop table if exists llx_don;

drop table if exists llx_don_projet;

drop table if exists llx_editeur;

drop table if exists llx_entrepot;

drop table if exists llx_expedition;

drop table if exists llx_expedition_methode;

drop table if exists llx_expeditiondet;

drop table if exists llx_fa_pr;

drop table if exists llx_facture;

drop table if exists llx_facture_rec;

drop table if exists llx_facturedet;

drop table if exists llx_facturedet_rec;

drop table if exists llx_facture_fourn;

drop table if exists llx_facture_fourn_det;

drop table if exists llx_fichinter;

drop table if exists llx_groupart;

drop table if exists llx_lieu_concert ;

drop table if exists llx_livre;

drop table if exists llx_livre_to_auteur ;

drop table if exists llx_newsletter;

drop table if exists llx_notify ;

drop table if exists llx_notify_def;

drop table if exists llx_paiement;

drop table if exists llx_paiementfourn;

drop table if exists llx_pointmort;

drop table if exists llx_product;

drop table if exists llx_product_fournisseur;

drop table if exists llx_product_price;

drop table if exists llx_product_stock;

drop table if exists llx_projet;

drop table if exists llx_propal;

drop table if exists llx_propal_model_pdf;

drop table if exists llx_propaldet;

drop table if exists llx_rights_def;

drop table if exists llx_service;

drop table if exists llx_societe;

drop table if exists llx_societe_ca;

drop table if exists llx_societe_details;

drop table if exists llx_societe_prestation;

drop table if exists llx_societe_reference;

drop table if exists llx_societe_remise;

drop table if exists llx_societe_remise_except;

drop table if exists llx_societe_rib;

drop table if exists llx_societe_techno;

drop table if exists llx_socpeople;

drop table if exists llx_soc_events;

drop table if exists llx_soc_recontact;

drop table if exists llx_socstatutlog ;

drop table if exists llx_stock;

drop table if exists llx_stock_mouvement;

drop table if exists llx_sqltables;

drop table if exists llx_todocomm ;

drop table if exists llx_transaction_bplc ;

drop table if exists llx_tva;

drop table if exists llx_user;

drop table if exists llx_user_rights;

drop table if exists llx_ventes;

drop table if exists llx_voyage;

drop table if exists llx_voyage_reduc;

drop table if exists llx_facture_tva_sum;

drop table if exists llx_c_accountingsystem;

drop table if exists llx_c_ape;

drop table if exists llx_c_civilite;

drop table if exists llx_c_currencies;

drop table if exists llx_c_departements;

drop table if exists llx_c_forme_juridique;

drop table if exists llx_c_prestation;

drop table if exists llx_c_regions;

drop table if exists llx_c_techno;

drop table if exists llx_catalog_societe;

drop table if exists llx_catalogsoc;

drop table if exists llx_groupesociete;
drop table if exists llx_groupesociete_remise;
drop table if exists llx_mailing;
drop table if exists llx_mailing_cibles;
drop table if exists llx_paiement_facture;
drop table if exists llx_paiementcharge;
drop table if exists llx_so_gr;

drop table if exists llx_user_alert;
drop table if exists llx_user_param;

drop table if exists llx_bookmark4u_login;
drop table if exists llx_c_methode_commande_fournisseur;
drop table if exists llx_compta_compte_generaux;
drop table if exists llx_contratdet;
drop table if exists llx_contratdet_log;
drop table if exists llx_export_compta;
drop table if exists llx_prelevement;
drop table if exists llx_prelevement_facture;
drop table if exists llx_prelevement_facture_demande;
drop table if exists llx_prelevement_rejet;
drop table if exists llx_product_fournisseur_price;
drop table if exists llx_societe_commerciaux;
drop table if exists llx_user_clicktodial;
drop table if exists llx_usergroup;
drop table if exists llx_usergroup_user;