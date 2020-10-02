--
-- Script to repair some fatal errors due to database corruption
-- when current version is 2.6.0 or higher. 
--


-- Replace xxx with your IP Address 
-- bind-address        = xxx.xxx.xxx.xxx
-- CREATE USER 'myuser'@'localhost' IDENTIFIED BY 'mypass';
-- CREATE USER 'myuser'@'%' IDENTIFIED BY 'mypass';
-- GRANT ALL ON *.* TO 'myuser'@'localhost';
-- GRANT ALL ON *.* TO 'myuser'@'%';
-- flush privileges;


-- Request to change default pagecode + colation of database
-- ALTER DATABASE name_of_database CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Request to change default pagecode + colation of table
-- ALTER TABLE name_of_table CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- Request to change character set and collation of a varchar column.
-- utf8 and utf8_unicode_ci is recommended (or even better utf8mb4 and utf8mb4_unicode_ci with mysql 5.5.3+)
-- ALTER TABLE name_of_table MODIFY field VARCHAR(20) CHARACTER SET utf8;
-- ALTER TABLE name_of_table MODIFY field VARCHAR(20) COLLATE utf8_unicode_ci;
-- You can check with 'show full columns from mytablename';



-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY account_number VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY account_number VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_bookkeeping MODIFY numero_compte VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_bookkeeping MODIFY numero_compte VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_journal MODIFY code VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_journal MODIFY code VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_bank_account MODIFY accountancy_journal VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_bank_account MODIFY accountancy_journal VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_stock_mouvement MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_stock_mouvement MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_lot MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_lot MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_batch MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_batch MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_buy VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_buy VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_c_type_fees MODIFY accountancy_code VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_c_type_fees MODIFY accountancy_code VARCHAR(32) COLLATE utf8_unicode_ci;


-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_facture set date_pointoftax = NULL where DATE(STR_TO_DATE(date_pointoftax, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_facture set date_pointoftax = NULL where DATE(STR_TO_DATE(date_pointoftax, '%Y-%m-%d')) IS NULL;



-- Requests to clean corrupted data

-- VMYSQL4.1 INSERT IGNORE INTO llx_product_lot (entity, fk_product, batch, eatby, sellby, datec, fk_user_creat, fk_user_modif) SELECT DISTINCT e.entity, ps.fk_product, pb.batch, pb.eatby, pb.sellby, pb.tms, e.fk_user_author, e.fk_user_author from llx_product_batch as pb, llx_product_stock as ps, llx_entrepot as e WHERE pb.fk_product_stock = ps.rowid AND ps.fk_entrepot = e.rowid;
-- -- a tester VPGSQL9.5 INSERT IGNORE INTO llx_product_lot (entity, fk_product, batch, eatby, sellby, datec, fk_user_creat, fk_user_modif) SELECT DISTINCT e.entity, ps.fk_product, pb.batch, pb.eatby, pb.sellby, pb.tms, e.fk_user_author, e.fk_user_author from llx_product_batch as pb, llx_product_stock as ps, llx_entrepot as e WHERE pb.fk_product_stock = ps.rowid AND ps.fk_entrepot = e.rowid ON CONFLICT DO NOTHING;
-- -- avant 9.5 faire en variant x pour qu'au 2eme passage, le premier doublon soit dans la tabel cible
-- -- INSERT INTO llx_product_lot (entity, fk_product, batch, eatby, sellby, datec, fk_user_creat, fk_user_modif) 
-- -- SELECT DISTINCT e.entity, ps.fk_product, pb.batch, pb.eatby, pb.sellby, pb.tms, e.fk_user_author, e.fk_user_author 
-- -- from llx_product_batch as pb, llx_product_stock as ps, llx_entrepot as e
-- -- WHERE pb.fk_product_stock = ps.rowid AND ps.fk_entrepot = e.rowid 
-- -- AND NOT EXISTS (SELECT 1 FROM llx_product_lot as b WHERE b.fk_product=ps.fk_product and pb.batch=b.batch) LIMIT x


UPDATE llx_user set api_key = null where api_key = '';

UPDATE llx_c_email_templates SET position = 0 WHERE position IS NULL;
-- DELETE FROM llx_c_email_templates WHERE label = '(SendAnEMailToMember)';		-- Now it is '(SendingAnEMailToMemner)'


-- delete foreign key that should never exists
ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_currency;
ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_currency;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_currency;

delete from llx_facturedet where fk_facture in (select rowid from llx_facture where ref in ('(PROV)','ErrorBadMask'));
delete from llx_facture where ref in ('(PROV)','ErrorBadMask');
delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref in ('(PROV)','ErrorBadMask'));
delete from llx_commande where ref in ('(PROV)','ErrorBadMask');
delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref in ('(PROV)','ErrorBadMask'));
delete from llx_propal where ref in ('(PROV)','ErrorBadMask');
delete from llx_facturedet where fk_facture in (select rowid from llx_facture where ref = '');
delete from llx_facture where ref = '';
delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '');
delete from llx_commande where ref = '';
delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '');
delete from llx_propal where ref = '';
delete from llx_livraisondet where fk_livraison in (select rowid from llx_livraison where ref = '');
delete from llx_livraison where ref = '';
delete from llx_expeditiondet where fk_expedition in (select rowid from llx_expedition where ref = '');
delete from llx_expedition where ref = '';
delete from llx_holiday_logs where fk_user_update not IN (select rowid from llx_user);

delete from llx_rights_def where perms IS NULL;
delete from llx_user_rights where fk_user not IN (select rowid from llx_user);
delete from llx_usergroup_rights where fk_usergroup not in (select rowid from llx_usergroup);
delete from llx_usergroup_rights where fk_id not in (select id from llx_rights_def);

update llx_deplacement set dated='2010-01-01' where dated < '2000-01-01';

update llx_subscription set fk_bank = null where fk_bank not in (select rowid from llx_bank);

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

UPDATE llx_product SET datec = tms WHERE datec IS NULL;


-- Clean stocks

-- Reference for qty is llx_product_stock (detail in llx_product_batch may be not complete)
-- qty in llx_product may be not up to date
update llx_product_batch set batch = '' where batch = 'Non d&eacute;fini';
update llx_product_batch set batch = '' where batch = 'Non défini';

update llx_stock_mouvement set batch = null where batch = 'Non d&eacute;fini';
update llx_stock_mouvement set batch = null where batch = 'Non défini';

DELETE FROM llx_product_lot WHERE fk_product NOT IN (select rowid from llx_product); 
DELETE FROM llx_product_stock WHERE fk_product NOT IN (select rowid from llx_product); 
DELETE FROM llx_product_stock WHERE reel = 0 AND rowid NOT IN (SELECT fk_product_stock FROM llx_product_batch as pb);



-- Merge splitted lines into one in table llx_product_batch 
DROP TABLE tmp_llx_product_batch;
DROP TABLE tmp_llx_product_batch2;
CREATE TABLE tmp_llx_product_batch AS select fk_product_stock, eatby, sellby, batch, SUM(qty) as qty, COUNT(rowid) as nb FROM llx_product_batch GROUP BY fk_product_stock, eatby, sellby, batch HAVING COUNT(rowid) > 1;
CREATE TABLE tmp_llx_product_batch2 AS select pb.rowid, pb.fk_product_stock, pb.eatby, pb.sellby, pb.batch, pb.qty from llx_product_batch as pb, tmp_llx_product_batch as tpb where pb.fk_product_stock = tpb.fk_product_stock and COALESCE(pb.eatby, '2000-01-01') = COALESCE(tpb.eatby,'2000-01-01') and COALESCE(pb.sellby, '2000-01-01') = COALESCE(tpb.sellby, '2000-01-01') and pb.batch = tpb.batch;
--select * from tmp_llx_product_batch;
--select * from tmp_llx_product_batch2;
DELETE FROM llx_product_batch WHERE rowid IN (select rowid FROM tmp_llx_product_batch2);
INSERT INTO llx_product_batch(fk_product_stock, eatby, sellby, batch, qty) SELECT fk_product_stock, eatby, sellby, batch, qty FROM tmp_llx_product_batch;

DELETE FROM llx_product_stock WHERE reel = 0 AND rowid NOT IN (SELECT fk_product_stock FROM llx_product_batch as pb);
DELETE FROM llx_product_batch WHERE qty = 0;


-- Stock calculation on product
UPDATE llx_product p SET p.stock= (SELECT SUM(ps.reel) FROM llx_product_stock ps WHERE ps.fk_product = p.rowid);


-- Fix: delete orphelins in product_association
delete from llx_product_association where fk_product_pere NOT IN (select rowid from llx_product);
delete from llx_product_association where fk_product_fils NOT IN (select rowid from llx_product);

-- Fix: delete category child with no category parent.
drop table tmp_categorie;
create table tmp_categorie as select * from llx_categorie; 
-- select * from llx_categorie where fk_parent not in (select rowid from tmp_categorie) and fk_parent is not null and fk_parent <> 0;
delete from llx_categorie where fk_parent not in (select rowid from tmp_categorie) and fk_parent is not null and fk_parent <> 0;
drop table tmp_categorie;
-- Fix: delete orphelin category.
delete from llx_categorie_product where fk_categorie not in (select rowid from llx_categorie where type = 0);
delete from llx_categorie_fournisseur where fk_categorie not in (select rowid from llx_categorie where type = 1);
delete from llx_categorie_societe where fk_categorie not in (select rowid from llx_categorie where type = 2);
delete from llx_categorie_member where fk_categorie not in (select rowid from llx_categorie where type = 3);
delete from llx_categorie_contact where fk_categorie not in (select rowid from llx_categorie where type = 4);
delete from llx_categorie_project where fk_categorie not in (select rowid from llx_categorie where type = 6);

-- Fix: delete orphelins in ecm_files
delete from llx_ecm_files where src_object_type = 'expensereport' and src_object_id NOT IN (select rowid from llx_expensereport);

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


-- Fix: delete orphelin actioncomm_resources
DELETE FROM llx_actioncomm_resources WHERE fk_actioncomm not in (select id from llx_actioncomm);


-- Fix: delete orphelin links in llx_bank_url
DELETE from llx_bank_url where type = 'payment' and url_id not in (select rowid from llx_paiement);
DELETE from llx_bank_url where type = 'payment_supplier' and url_id not in (select rowid from llx_paiementfourn);
DELETE from llx_bank_url where type = 'company' and url_id not in (select rowid from llx_societe);
--SELECT * from llx_bank where rappro = 0 and label LIKE '(CustomerInvoicePayment%)' and rowid not in (select fk_bank from llx_bank_url where type = 'payment');
--SELECT * from llx_bank where rappro = 0 and label LIKE '(SupplierInvoicePayment%)' and rowid not in (select fk_bank from llx_bank_url where type = 'payment_supplier');

-- Fix link on parent that were removed
DROP table tmp_user;
CREATE TABLE tmp_user as (select * from llx_user);
UPDATE llx_user SET fk_user = NULL where fk_user NOT IN (select rowid from tmp_user);



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


-- Sequence to removed duplicated values of llx_links. Use several times if you still have duplicate.
drop table tmp_links_double;
--select objectid, label, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_links where label is not null group by objectid, label having count(rowid) >= 2;
create table tmp_links_double as (select objectid, label, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_links where label is not null group by objectid, label having count(rowid) >= 2);
--select * from tmp_links_double;
delete from llx_links where (rowid, label) in (select max_rowid, label from tmp_links_double);	--update to avoid duplicate, delete to delete
drop table tmp_links_double;


-- Sequence to removed duplicated values of barcode in llx_product. Use several times if you still have duplicate.
drop table tmp_product_double;
--select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_product where barcode is not null group by barcode having count(rowid) >= 2;
create table tmp_product_double as (select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_product where barcode is not null group by barcode having count(rowid) >= 2);
--select * from tmp_product_double;
update llx_product set barcode = null where (rowid, barcode) in (select max_rowid, barcode from tmp_product_double);	--update to avoid duplicate, delete to delete
drop table tmp_product_double;


-- Sequence to removed duplicated values of barcode in llx_societe. Use several times if you still have duplicate.
drop table tmp_societe_double;
--select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_societe where barcode is not null group by barcode having count(rowid) >= 2;
create table tmp_societe_double as (select barcode, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_societe where barcode is not null group by barcode having count(rowid) >= 2);
--select * from tmp_societe_double;
update llx_societe set barcode = null where (rowid, barcode) in (select max_rowid, barcode from tmp_societe_double);
drop table tmp_societe_double;


-- Sequence to removed duplicated values of llx_accounting_account. Use several times if you still have duplicate.
drop table tmp_accounting_account_double;
--select account_number, fk_pcg_version, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_accounting_account where label is not null group by account_number, fk_pcg_version having count(rowid) >= 2;
create table tmp_accounting_account_double as (select account_number, fk_pcg_version, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_accounting_account where label is not null group by account_number, fk_pcg_version having count(rowid) >= 2);
--select * from tmp_accounting_account_double;
delete from llx_accounting_account where (rowid) in (select max_rowid from tmp_accounting_account_double);	--update to avoid duplicate, delete to delete
drop table tmp_accounting_account_double;


UPDATE llx_projet_task SET fk_task_parent = 0 WHERE fk_task_parent = rowid;


UPDATE llx_actioncomm set fk_user_action = fk_user_done where fk_user_done > 0 and (fk_user_action is null or fk_user_action = 0);
UPDATE llx_actioncomm set fk_user_action = fk_user_author where fk_user_author > 0 and (fk_user_action is null or fk_user_action = 0);


UPDATE llx_projet_task_time set task_datehour = task_date where task_datehour IS NULL and task_date IS NOT NULL;

UPDATE llx_projet set fk_opp_status = NULL where fk_opp_status = -1;
UPDATE llx_projet set fk_opp_status = (SELECT rowid FROM llx_c_lead_status WHERE code='PROSP') where fk_opp_status IS NULL and opp_amount > 0;
UPDATE llx_c_lead_status set code = 'WON' where code = 'WIN';

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
-- update llx_facture set total = round(total_ttc / 1.2, 5) where total_ht = total_ttc;

-- To fix bad total of price excluding tax, vat and price tax including tax.
-- select * from llx_facture where tva <> (total_ttc - total - localtax1 - localtax2 - revenuestamp);
-- update llx_facture set tva = (total_ttc - total - localtax1 - localtax2 - revenuestamp) where tva <> (total_ttc - total - localtax1 - localtax2 - revenuestamp);
-- select * from llx_facturedet where total_tva <> (total_ttc - total_ht - total_localtax1 - total_localtax2);
-- update llx_facturedet set total_tva = (total_ttc - total_ht - total_localtax1 - total_localtax2) where total_tva <> (total_ttc - total_ht - total_localtax1 - total_localtax2);


-- To insert elements into a category
-- Search idcategory: select rowid from llx_categorie where type=0 and ref like '%xxx%'
-- Select all products to include: select * from llx_product where ref like '%xxx%'
-- If ok, insert: insert into llx_categorie_product(fk_categorie, fk_product) select idcategory, rowid from llx_product where ref like '%xxx%'
-- List of product with a category xxx: select distinct cp.fk_product from llx_categorie_product as cp, llx_categorie as c where cp.fk_categorie = c.rowid and c.label like 'xxx-%' order by fk_product;
-- List of product into 2 categories xxx: select cp.fk_product, count(cp.fk_product) as nb from llx_categorie_product as cp, llx_categorie as c where cp.fk_categorie = c.rowid and c.label like 'xxx-%' group by fk_product having nb > 1;
-- List of product with no category xxx yet: select rowid, ref from llx_product where rowid not in (select distinct cp.fk_product from llx_categorie_product as cp, llx_categorie as c where cp.fk_categorie = c.rowid and c.label like 'xxx-%' order by fk_product);

-- Fix type of product 2 does not exists
update llx_propaldet set product_type = 1 where product_type = 2;
update llx_commandedet set product_type = 1 where product_type = 2;
update llx_facturedet set product_type = 1 where product_type = 2;
--update llx_propaldet as d set d.product_type = 1 where d.fk_product = 22 and d.product_type = 0;
--update llx_commandedet as d set d.product_type = 1 where d.fk_product = 22 and d.product_type = 0;
--update llx_facturedet as d set d.product_type = 1 where d.fk_product = 22 and d.product_type = 0;

update llx_propal set fk_statut = 1 where fk_statut = -1;

delete from llx_commande_fournisseur_dispatch where fk_commandefourndet = 0 or fk_commandefourndet IS NULL;


delete from llx_menu where menu_handler = 'smartphone';

update llx_expedition set date_valid = date_creation where fk_statut = 1 and date_valid IS NULL;

-- Detect bad consistency between duraction_effective of a task and sum of time of tasks
-- select pt.rowid, pt.duration_effective, SUM(ptt.task_duration) as y from llx_projet_task as pt, llx_projet_task_time as ptt where ptt.fk_task = pt.rowid group by pt.rowid, pt.duration_effective having pt.duration_effective <> y;
update llx_projet_task as pt set pt.duration_effective = (select SUM(ptt.task_duration) as y from llx_projet_task_time as ptt where ptt.fk_task = pt.rowid) where pt.duration_effective <> (select SUM(ptt.task_duration) as y from llx_projet_task_time as ptt where ptt.fk_task = pt.rowid);
 

-- Remove duplicate of shipment mode (keep the one with tracking defined)
drop table tmp_c_shipment_mode;
create table tmp_c_shipment_mode as (select code, tracking from llx_c_shipment_mode);
DELETE FROM llx_c_shipment_mode where code IN (select code from tmp_c_shipment_mode WHERE tracking is NULL OR tracking = '') AND code IN (select code from tmp_c_shipment_mode WHERE tracking is NOT NULL AND tracking != '') AND (tracking IS NULL OR tracking = '');
drop table tmp_c_shipment_mode;


-- Restore id of user on link for payment of expense report
drop table tmp_bank_url_expense_user;
create table tmp_bank_url_expense_user (select e.fk_user_author, bu2.fk_bank from llx_expensereport as e, llx_bank_url as bu2 where bu2.url_id = e.rowid and bu2.type = 'payment_expensereport');
update llx_bank_url as bu set url_id = (select e.fk_user_author from tmp_bank_url_expense_user as e where e.fk_bank = bu.fk_bank) where (bu.url_id = 0 OR bu.url_id IS NULL) and bu.type ='user';
drop table tmp_bank_url_expense_user;


-- Delete duplicate accounting account, but only if not used
DROP TABLE tmp_llx_accouting_account;
CREATE TABLE tmp_llx_accouting_account AS SELECT MIN(rowid) as MINID, account_number, entity, fk_pcg_version, count(*) AS NB FROM llx_accounting_account group BY account_number, entity, fk_pcg_version HAVING count(*) >= 2 order by account_number, entity, fk_pcg_version;
--SELECT * from tmp_llx_accouting_account;
DELETE from llx_accounting_account where rowid in (select minid from tmp_llx_accouting_account where minid NOT IN (SELECT fk_code_ventilation from llx_facturedet) AND minid NOT IN (SELECT fk_code_ventilation from llx_facture_fourn_det) AND minid NOT IN (SELECT fk_code_ventilation from llx_expensereport_det));

ALTER TABLE llx_accounting_account DROP INDEX uk_accounting_account;
ALTER TABLE llx_accounting_account ADD UNIQUE INDEX uk_accounting_account (account_number, entity, fk_pcg_version);


-- VMYSQL4.1 update llx_projet_task_time set task_datehour = task_date where task_datehour < task_date or task_datehour > DATE_ADD(task_date, interval 1 day);


-- Clean product prices
--delete from llx_product_price where date_price between '2017-04-20 06:51:00' and '2017-04-20 06:51:05'; 
-- Set product prices into llx_product with last price into llx_product_prices
--update llx_product as p set 
-- p.price = (select pp.price from llx_product_price as pp where pp.price_level = 1 and pp.fk_product = p.rowid order by pp.tms desc limit 1),
-- p.price_ttc = (select pp.price_ttc from llx_product_price as pp where pp.price_level = 1 and pp.fk_product = p.rowid order by pp.tms desc limit 1),
-- p.price_min = (select pp.price_min from llx_product_price as pp where pp.price_level = 1 and pp.fk_product = p.rowid order by pp.tms desc limit 1),
-- p.price_min_ttc = (select pp.price_min_ttc from llx_product_price as pp where pp.price_level = 1 and pp.fk_product = p.rowid order by pp.tms desc limit 1),
-- p.tva_tx = 0
-- where price = 17.5

UPDATE llx_chargesociales SET date_creation = tms WHERE date_creation IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_accounting_account set tms = datec where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_accounting_account set tms = datec where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_expensereport set date_debut = date_create where DATE(STR_TO_DATE(date_debut, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_expensereport set date_debut = date_create where DATE(STR_TO_DATE(date_debut, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_expensereport set date_fin = date_debut where DATE(STR_TO_DATE(date_fin, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_expensereport set date_fin = date_debut where DATE(STR_TO_DATE(date_fin, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_expensereport set date_valid = date_fin where DATE(STR_TO_DATE(date_valid, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_expensereport set date_valid = date_fin where DATE(STR_TO_DATE(date_valid, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_expensereport_det as ed set date = (select date_debut from llx_expensereport as e where ed.fk_expensereport = e.rowid) where DATE(STR_TO_DATE(date, '%Y-%m-%d')) < '1000-00-00';
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_bank set tms = datec where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_bank set tms = datec where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_facture_fourn set date_lim_reglement = null where DATE(STR_TO_DATE(date_lim_reglement, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_facture_fourn set date_lim_reglement = null where DATE(STR_TO_DATE(date_lim_reglement, '%Y-%m-%d')) IS NULL;


-- Backport a change of value into the hourly rate. 
-- update llx_projet_task_time as ptt set ptt.thm = (SELECT thm from llx_user as u where ptt.fk_user = u.rowid) where (ptt.thm is null)


-- select * from llx_facturedet as fd, llx_product as p where fd.fk_product = p.rowid AND fd.product_type != p.fk_product_type;
update llx_facturedet set product_type = 0 where product_type = 1 AND fk_product > 0 AND fk_product IN (SELECT rowid FROM llx_product WHERE fk_product_type = 0);
update llx_facturedet set product_type = 1 where product_type = 0 AND fk_product > 0 AND fk_product IN (SELECT rowid FROM llx_product WHERE fk_product_type = 1);

update llx_facture_fourn_det set product_type = 0 where product_type = 1 AND fk_product > 0 AND fk_product IN (SELECT rowid FROM llx_product WHERE fk_product_type = 0);
update llx_facture_fourn_det set product_type = 1 where product_type = 0 AND fk_product > 0 AND fk_product IN (SELECT rowid FROM llx_product WHERE fk_product_type = 1);
 

DELETE FROM llx_mrp_production where qty = 0;


UPDATE llx_accounting_bookkeeping set date_creation = tms where date_creation IS NULL;

 
-- UPDATE llx_contratdet set label = NULL WHERE label IS NOT NULL;
-- UPDATE llx_facturedet_rec set label = NULL WHERE label IS NOT NULL;



UPDATE llx_facturedet SET situation_percent = 100 WHERE situation_percent IS NULL AND fk_prev_id IS NULL;

-- Test inconsistency of data into situation invoices: If it differs, it may be the total_ht that is wrong and situation_percent that is good.
-- select f.rowid, f.type, fd.qty, fd.subprice, fd.situation_percent, fd.total_ht, fd.total_ttc, fd.total_tva, fd.multicurrency_total_ht, fd.multicurrency_total_tva, fd.multicurrency_total_ttc, (situation_percent  / 100 * subprice * qty * (1 - (fd.remise_percent / 100)))
-- from llx_facturedet as fd, llx_facture as f where fd.fk_facture = f.rowid AND (total_ht - situation_percent  / 100 * subprice * qty * (1 - (fd.remise_percent / 100))) > 0.01 and f.type = 5;


-- Note to make all deposit as payed when there is already a discount generated from it.
--drop table tmp_invoice_deposit_mark_as_available;
--create table tmp_invoice_deposit_mark_as_available as select * from llx_facture as f where f.type = 3 and f.paye = 0 and f.rowid in (select fk_facture_source from llx_societe_remise_except);
--update llx_facture set paye = 1, fk_statut = 2 where rowid in (select rowid from tmp_invoice_deposit_mark_as_available);




-- Note to migrate from old counter aquarium to new one
-- drop table tmp;
-- create table tmp select rowid, code_client, concat(substr(code_client, 1, 6),'-0',substr(code_client, 8, 5)) as code_client2 from llx_societe where code_client like 'CU____-____';
-- update llx_societe as s set code_client = (select code_client2 from tmp as t where t.rowid = s.rowid) where code_client like 'CU____-____';
-- drop table tmp;
-- create table tmp select rowid, code_fournisseur, concat(substr(code_fournisseur, 1, 6),'-0',substr(code_fournisseur, 8, 5)) as code_fournisseur2 from llx_societe where code_fournisseur like 'SU____-____';
-- select * from tmp;
-- update llx_societe as s set s.code_fournisseur = (select code_fournisseur2 from tmp as t where t.rowid = s.rowid) where s.code_fournisseur like 'SU____-____';
-- update llx_societe set code_compta =  concat('411', substr(code_client, 3, 2),substr(code_client, 8, 5)) where client in (1,2,3) and code_compte is not null;
-- update llx_societe set code_compta_fournisseur =  concat('401', substr(code_fournisseur, 3, 2),substr(code_fournisseur, 8, 5)) where fournisseur in (1,2,3) and code_fournisseur is not null;


-- To fix a table with error 'ERROR 1118 (42000): Row size too large. The maximum row size for the used table type, not counting BLOBs, is 8126. This includes storage overhead, check the manual. You have to change some columns to TEXT or BLOBs'
--ALTER TABLE llx_tablename ROW_FORMAT=DYNAMIC;


