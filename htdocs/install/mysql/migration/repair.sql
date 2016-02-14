--
-- Script to repair some fatal errors due to database corruption
-- when current version is 2.6.0 or higher. 
--

-- Requests to clean corrupted database


UPDATE llx_user set api_key = null where api_key = '';


-- delete foreign key that should never exists
ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_currency;
ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_currency;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_currency;

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

update llx_product_batch set batch = '' where batch = 'Non d&eacute;fini';

-- Fix: delete category child with no category parent.
drop table tmp_categorie;
create table tmp_categorie as select * from llx_categorie; 
-- select * from llx_categorie where fk_parent not in (select rowid from tmp_categorie) and fk_parent is not null and fk_parent <> 0;
delete from llx_categorie where fk_parent not in (select rowid from tmp_categorie) and fk_parent is not null and fk_parent <> 0;
drop table tmp_categorie;
-- Fix: delete orphelin category.
delete from llx_categorie_product where fk_categorie not in (select rowid from llx_categorie where type = 0);
delete from llx_categorie_societe where fk_categorie not in (select rowid from llx_categorie where type in (1, 2));
delete from llx_categorie_member where fk_categorie not in (select rowid from llx_categorie where type = 3);
delete from llx_categorie_contact where fk_categorie not in (select rowid from llx_categorie where type = 4);


-- Fix: delete orphelin deliveries. Note: deliveries are linked to shipment by llx_element_element only. No other links.
delete from llx_livraisondet where fk_livraison not in (select fk_target from llx_element_element where targettype = 'delivery') AND fk_livraison not in (select fk_source from llx_element_element where sourcetype = 'delivery');
delete from llx_livraison    where rowid not in (select fk_target from llx_element_element where targettype = 'delivery') AND rowid not in (select fk_source from llx_element_element where sourcetype = 'delivery');


-- Fix delete element_element orphelins (right side)
delete from llx_element_element where targettype='shipping' and fk_target not in (select rowid from llx_expedition);
delete from llx_element_element where targettype='propal' and fk_target not in (select rowid from llx_propal);
delete from llx_element_element where targettype='facture' and fk_target not in (select rowid from llx_facture);
delete from llx_element_element where targettype='commande' and fk_target not in (select rowid from llx_commande);
-- Fix delete element_element orphelins (left side)
delete from llx_element_element where sourcetype='shipping' and fk_source not in (select rowid from llx_expedition);
delete from llx_element_element where sourcetype='propal' and fk_source not in (select rowid from llx_propal);
delete from llx_element_element where sourcetype='facture' and fk_source not in (select rowid from llx_facture);
delete from llx_element_element where sourcetype='commande' and fk_source not in (select rowid from llx_commande);


UPDATE llx_product SET canvas = NULL where canvas = 'default@product';
UPDATE llx_product SET canvas = NULL where canvas = 'service@product';

DELETE FROM llx_boxes where box_id NOT IN (SELECT rowid FROM llx_boxes_def);

update llx_document_model set nom = 'typhon' where (nom = '' OR nom is null) and type = 'delivery';
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

-- We delete old entries into menu for module margin (pb with margin and margins)
-- VMYSQL DELETE from llx_menu where module = 'margin' and url = '/margin/index.php' and not exists (select * from llx_const where name = 'MAIN_MODULE_MARGIN' or name = 'MAIN_MODULE_MARGINS');
-- VMYSQL DELETE from llx_menu where module = 'margins' and url = '/margin/index.php' and not exists (select * from llx_const where name = 'MAIN_MODULE_MARGIN' or name = 'MAIN_MODULE_MARGINS');


ALTER TABLE llx_product_fournisseur_price DROP COLUMN fk_product_fournisseur;
ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur;


-- Fix: deprecated tag to new one
update llx_opensurvey_sondage set format = 'D' where format = 'D+';
update llx_opensurvey_sondage set format = 'A' where format = 'A+';
update llx_opensurvey_sondage set tms = now();

-- ALTER TABLE llx_facture_fourn ALTER COLUMN fk_cond_reglement DROP NOT NULL;


update llx_product set barcode = null where barcode in ('', '-1', '0');
update llx_societe set barcode = null where barcode in ('', '-1', '0');


-- Sequence to removed duplicated values of barcode in llx_product. Use serveral times if you still have duplicate.
drop table tmp_product_double;
--select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_product where barcode is not null group by barcode having count(rowid) >= 2;
create table tmp_product_double as (select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_product where barcode is not null group by barcode having count(rowid) >= 2);
--select * from tmp_product_double;
update llx_product set barcode = null where (rowid, barcode) in (select max_rowid, barcode from tmp_product_double);
drop table tmp_product_double;


-- Sequence to removed duplicated values of barcode in llx_societe. Use serveral times if you still have duplicate.
drop table tmp_societe_double;
--select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_societe where barcode is not null group by barcode having count(rowid) >= 2;
create table tmp_societe_double as (select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_societe where barcode is not null group by barcode having count(rowid) >= 2);
--select * from tmp_societe_double;
update llx_societe set barcode = null where (rowid, barcode) in (select max_rowid, barcode from tmp_societe_double);
drop table tmp_societe_double;


UPDATE llx_projet_task SET fk_task_parent = 0 WHERE fk_task_parent = rowid;


UPDATE llx_actioncomm set fk_user_action = fk_user_done where fk_user_done > 0 and (fk_user_action is null or fk_user_action = 0);
UPDATE llx_actioncomm set fk_user_action = fk_user_author where fk_user_author > 0 and (fk_user_action is null or fk_user_action = 0);


UPDATE llx_projet_task_time set task_datehour = task_date where task_datehour IS NULL and task_date IS NOT NULL;


-- Requests to clean old tables or external modules tables

-- DROP TABLE llx_c_methode_commande_fournisseur;
-- DROP TABLE llx_c_source;
-- DROP TABLE llx_congespayes;
-- DROP TABLE llx_congespayes_config;
-- DROP TABLE llx_congespayes_log;
-- DROP TABLE llx_congespayes_events;
-- DROP TABLE llx_congespayes_users;
-- DROP TABLE llx_compta;
-- DROP TABLE llx_compta_compte_generaux;
-- DROP TABLE llx_compta_account;
-- DROP TABLE llx_cabinetmed*;
-- DROP TABLE llx_cond_reglement;
-- DROP TABLE llx_expedition_methode;
-- DROP TABLE llx_product_fournisseur;
-- DROP TABLE llx_element_rang;
-- DROP TABLE llx_dolicloud_customers;
-- DROP TABLE llx_dolicloud_emailstemplates;
-- DROP TABLE llx_dolicloud_stats;
-- DROP TABLE llx_submitew_message;
-- DROP TABLE llx_submitew_targets;
-- DROP TABLE llx_submitew_targets_param;
-- DROP TABLE llx_pos_cash;
-- DROP TABLE llx_pos_control_cash;
-- DROP TABLE llx_pos_facture;
-- DROP TABLE llx_pos_moviments;
-- DROP TABLE llx_pos_ticketdet;

-- To replace amount on all invoice and lines when forgetting to apply a 20% vat
-- update llx_facturedet set tva_tx = 20 where tva_tx = 0;
-- update llx_facturedet set total_ht = round(total_ttc / 1.2, 5) where total_ht = total_ttc;
-- update llx_facturedet set total_tva = total_ttc - total_ht where total_vat = 0;
-- update llx_facture set total = round(total_ttc / 1.2, 5) where total_ht = total_ttc;
-- update llx_facture set tva = total_ttc - total where tva = 0;

-- To insert elements into a category
-- Search idcategory: select rowid from llx_categorie where type=0 and ref like '%xxx%'
-- Select all products to include: select * from llx_product where ref like '%xxx%'
-- If ok, insert: insert into llx_categorie_product(fk_categorie, fk_product) select idcategory, rowid from llx_product where ref like '%xxx%'
-- List of product with a category xxx: select distinct cp.fk_product from llx_categorie_product as cp, llx_categorie as c where cp.fk_categorie = c.rowid and c.label like 'xxx-%' order by fk_product;
-- List of product into 2 categories xxx: select cp.fk_product, count(cp.fk_product) as nb from llx_categorie_product as cp, llx_categorie as c where cp.fk_categorie = c.rowid and c.label like 'xxx-%' group by fk_product having nb > 1;
-- List of product with no category xxx yet: select rowid, ref from llx_product where rowid not in (select distinct cp.fk_product from llx_categorie_product as cp, llx_categorie as c where cp.fk_categorie = c.rowid and c.label like 'xxx-%' order by fk_product);

-- Replace xxx with your IP Address 
-- bind-address        = xxx.xxx.xxx.xxx
-- CREATE USER 'myuser'@'localhost' IDENTIFIED BY 'mypass';
-- CREATE USER 'myuser'@'%' IDENTIFIED BY 'mypass';
-- GRANT ALL ON *.* TO 'myuser'@'localhost';
-- GRANT ALL ON *.* TO 'myuser'@'%';
-- flush privileges;

-- Fix type of product 2 does not exists
update llx_propaldet set product_type = 1 where product_type = 2;
update llx_commandedet set product_type = 1 where product_type = 2;
update llx_facturedet set product_type = 1 where product_type = 2;
--update llx_propaldet as d set d.product_type = 1 where d.fk_product = 22 and d.product_type = 0;
--update llx_commandedet as d set d.product_type = 1 where d.fk_product = 22 and d.product_type = 0;
--update llx_facturedet as d set d.product_type = 1 where d.fk_product = 22 and d.product_type = 0;

delete from llx_commande_fournisseur_dispatch where fk_commandefourndet = 0 or fk_commandefourndet IS NULL;


delete from llx_menu where menu_handler = 'smartphone';


