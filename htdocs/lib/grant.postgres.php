<?PHP

$conf = "../conf/conf.php";

if (file_exists($conf))
{
  include($conf);

}
$nom =$dolibarr_main_db_user;

$grant_query = "GRANT ALL ON llx_action_def,llx_actioncomm,llx_adherent,llx_adherent_options,
llx_adherent_options_label,llx_adherent_type,llx_album_to_groupart,llx_appro,llx_auteur, llx_bank,llx_bank_account,llx_bank_categ,llx_bank_class,llx_bank_url,llx_bookmark,       llx_boxes,llx_boxes_def,llx_c_accountingsystem,llx_c_actioncomm,llx_c_ape,       
llx_c_chargesociales,llx_c_civilite,llx_c_departements,llx_c_effectif,        
llx_c_forme_juridique,llx_c_paiement,llx_c_pays,llx_c_propalst,llx_c_regions,  llx_c_stcomm,llx_c_typent,llx_cash,llx_cash_account,llx_chargesociales,llx_co_fa,   
llx_co_pr,llx_commande,llx_commandedet,llx_compta,llx_compta_account,llx_concert,
llx_cond_reglement,llx_const,llx_contrat,llx_cotisation,llx_deplacement,llx_domain,
llx_don,llx_don_projet,llx_editeur,llx_entrepot,llx_expedition,llx_expedition_methode,
llx_expeditiondet,llx_fa_pr,llx_facture,llx_facture_fourn,llx_facture_fourn_det,
llx_facture_rec,llx_facture_tva_sum,llx_facturedet,llx_facturedet_rec,llx_fichinter,
llx_groupart,llx_lieu_concert,llx_livre,llx_livre_to_auteur,llx_newsletter,llx_notify,
llx_notify_def,llx_paiement,llx_paiement_facture,llx_paiementcharge,llx_paiementfourn,
llx_pointmort,llx_product,llx_product_fournisseur,llx_product_price,llx_product_stock,
llx_projet,llx_propal,llx_propal_model_pdf,llx_propaldet,llx_rights_def,llx_service,
llx_soc_events,llx_soc_recontact,llx_societe,llx_socpeople,llx_socstatutlog,llx_sqltables,
llx_stock_mouvement,llx_todocomm,llx_transaction_bplc,llx_tva,llx_user,      llx_user_alert,llx_user_param,llx_user_rights,llx_ventes,llx_voyage,llx_voyage_reduc
TO \"$nom\" ;";

?>
