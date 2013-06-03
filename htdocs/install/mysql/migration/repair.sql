--
-- Script to repair some fatal errors due to database corruption
-- when current version is 2.6.0 or higher. 
--


-- Requests to clean corrupted database

delete from llx_facturedet where fk_facture in (select rowid from llx_facture where facnumber in ('(PROV)','ErrorBadMask'));
delete from llx_facture where facnumber in ('(PROV)','ErrorBadMask');
delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref in ('(PROV)','ErrorBadMask'));
delete from llx_commande where ref in ('(PROV)','ErrorBadMask');
delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref in ('(PROV)','ErrorBadMask'));
delete from llx_propal where ref in ('(PROV)','ErrorBadMask');
delete from llx_facturedet where fk_facture in (select rowid from llx_facture where facnumber = '');
delete from llx_facture where facnumber = '';
delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '');
delete from llx_commande where ref = '';
delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '');
delete from llx_propal where ref = '';
delete from llx_livraisondet where fk_livraison in (select rowid from llx_livraison where ref = '');
delete from llx_livraison where ref = '';
delete from llx_expeditiondet where fk_expedition in (select rowid from llx_expedition where ref = '');
delete from llx_expedition where ref = '';

update llx_deplacement set dated='2010-01-01' where dated < '2000-01-01';

update llx_cotisation set fk_bank = null where fk_bank not in (select rowid from llx_bank);

update llx_propal set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_commande set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_commande_fournisseur set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_contrat set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_deplacement set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture_fourn set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture_rec set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_fichinter set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_projet_task set fk_projet = null where fk_projet not in (select rowid from llx_projet);

update llx_propal set fk_user_author = null where fk_user_author not in (select rowid from llx_user);
update llx_propal set fk_user_valid = null where fk_user_valid not in (select rowid from llx_user);
update llx_propal set fk_user_cloture = null where fk_user_cloture not in (select rowid from llx_user);
update llx_commande set fk_user_author = null where fk_user_author not in (select rowid from llx_user);
update llx_commande set fk_user_valid = null where fk_user_valid not in (select rowid from llx_user);

delete from llx_societe_extrafields where fk_object not in (select rowid from llx_societe);
delete from llx_adherent_extrafields where fk_object not in (select rowid from llx_adherent);
delete from llx_product_extrafields where fk_object not in (select rowid from llx_product);
--delete from llx_societe_commerciaux where fk_soc not in (select rowid from llx_societe);


-- Fix: delete orphelin deliveries. Note: deliveries are linked to shipment by llx_element_element only. No other links.
delete from llx_livraisondet where fk_livraison not in (select fk_target from llx_element_element where targettype = 'delivery') AND fk_livraison not in (select fk_source from llx_element_element where sourcetype = 'delivery');
delete from llx_livraison    where rowid not in (select fk_target from llx_element_element where targettype = 'delivery') AND rowid not in (select fk_source from llx_element_element where sourcetype = 'delivery');


UPDATE llx_product SET canvas = NULL where canvas = 'default@product';
UPDATE llx_product SET canvas = NULL where canvas = 'service@product';

DELETE FROM llx_boxes where box_id NOT IN (SELECT rowid FROM llx_boxes_def);

DELETE FROM llx_document_model WHERE nom ='elevement' AND type='delivery';

-- Fix: It seems this is missing for some users
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 1,  'AC_TEL',     'system', 'Phone call'							,NULL, 2);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 2,  'AC_FAX',     'system', 'Send Fax'							,NULL, 3);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 3,  'AC_PROP',    'systemauto', 'Send commercial proposal by email'	,'propal',  10);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 4,  'AC_EMAIL',   'system', 'Send Email'							,NULL, 4);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 5,  'AC_RDV',     'system', 'Rendez-vous'							,NULL, 1);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 8,  'AC_COM',     'systemauto', 'Send customer order by email'		,'order',   8);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 9,  'AC_FAC',     'systemauto', 'Send customer invoice by email'		,'invoice', 6);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 10, 'AC_SHIP',    'systemauto', 'Send shipping by email'				,'shipping', 11);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 30, 'AC_SUP_ORD', 'systemauto', 'Send supplier order by email'		,'order_supplier',    9);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values  (31, 'AC_SUP_INV', 'systemauto', 'Send supplier invoice by email'		,'invoice_supplier', 7);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 50, 'AC_OTH',     'system', 'Other'								,NULL, 5);


-- Stock calculation on product
UPDATE llx_product p SET p.stock= (SELECT SUM(ps.reel) FROM llx_product_stock ps WHERE ps.fk_product = p.rowid);


-- VMYSQL4.1 DELETE T1 FROM llx_boxes_def as T1, llx_boxes_def as T2 where T1.entity = T2.entity AND T1.file = T2.file AND T1.note = T2.note and T1.rowid > T2.rowid;
-- VPGSQL8.2 DELETE FROM llx_boxes_def as T1 WHERE rowid NOT IN (SELECT min(rowid) FROM llx_boxes_def GROUP BY file, entity, note);


-- Requests to clean old tables or fields

-- DROP TABLE llx_c_methode_commande_fournisseur;
-- DROP TABLE llx_c_source;
-- DROP TABLE llx_cond_reglement;
-- DROP TABLE llx_expedition_methode;
-- DROP TABLE llx_product_fournisseur;
-- ALTER TABLE llx_product_fournisseur_price DROP COLUMN fk_product_fournisseur;
ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur;
