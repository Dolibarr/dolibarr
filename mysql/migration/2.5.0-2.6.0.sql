--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.4.0 or higher. 
--

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (140,'order_supplier','internal', 'SALESREPFOLL',  'Responsable suivi de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (141,'order_supplier','internal', 'SHIPPING',      'Responsable reception de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (142,'order_supplier','external', 'BILLING',       'Contact fournisseur facturation commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (143,'order_supplier','external', 'CUSTOMER',      'Contact fournisseur suivi commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (145,'order_supplier','external', 'SHIPPING',      'Contact fournisseur livraison commande', 1);

update llx_const set visible = 1 where name = 'PROPALE_ADD_DELIVERY_ADDRESS';

alter table llx_expedition_methode change statut active tinyint DEFAULT 1;

update llx_actioncomm set datep = datea where datep is null;


INSERT INTO llx_expedition_methode (rowid,code,libelle,description,active) VALUES (1,'CATCH','Catch','Catch by client',1);
INSERT INTO llx_expedition_methode (rowid,code,libelle,description,active) VALUES (2,'TRANS','Transporter','Generic transporter',1);
INSERT INTO llx_expedition_methode (rowid,code,libelle,description,active) VALUES (3,'COLSUI','Colissimo Suivi','Colissimo Suivi',0);


insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (70, 'facture_fourn', 'internal', 'SALESREPFOLL',  'Responsable suivi du paiement', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (71, 'facture_fourn', 'external', 'BILLING',       'Contact fournisseur facturation', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (72, 'facture_fourn', 'external', 'SHIPPING',      'Contact fournisseur livraison', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (73, 'facture_fourn', 'external', 'SERVICE',       'Contact fournisseur prestation', 1);

alter table llx_product_stock add column   pmp             double(24,8) default 0 NOT NULL;
alter table llx_product       add column   pmp             double(24,8) default 0 NOT NULL;

ALTER TABLE llx_bank ADD INDEX idx_bank_datev(datev);
ALTER TABLE llx_bank ADD INDEX idx_bank_dateo(dateo);
ALTER TABLE llx_bank ADD INDEX idx_bank_fk_account(fk_account);
ALTER TABLE llx_bank ADD INDEX idx_bank_rappro(rappro);


ALTER TABLE llx_mailing_cibles add column other           varchar(255) NULL;

ALTER TABLE llx_mailing_cibles ADD INDEX idx_mailing_cibles_email (email);

ALTER TABLE llx_categorie ADD INDEX idx_categorie_type (type);

ALTER TABLE llx_product drop column   stock_propale;
ALTER TABLE llx_product drop column   stock_commande;
ALTER TABLE llx_product add column   stock              integer after import_key;

ALTER TABLE llx_product ADD INDEX idx_product_barcode (barcode);
ALTER TABLE llx_product ADD INDEX idx_product_import_key (import_key);

ALTER TABLE llx_adherent drop index login;
ALTER TABLE llx_adherent ADD UNIQUE INDEX uk_adherent_login (login);

ALTER TABLE llx_adherent add column fk_soc           integer NULL after societe;
ALTER TABLE llx_adherent ADD INDEX idx_adherent_fk_soc (fk_soc);
ALTER TABLE llx_adherent ADD CONSTRAINT adherent_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);

ALTER TABLE llx_societe drop column rubrique;

-- SAINT PIERRE ET MIQUELON
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1931,193,  '0','0','No VAT in SPM',1);

-- SAINT MARTIN
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2461,246,  '0','0','VAT Rate 0',1);


insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'35','Regime auto-entrepreneur');


alter table llx_user_param drop column page;

alter table llx_commande_fournisseur_log add column comment varchar(255) NULL;

delete from llx_categorie_association where fk_categorie_mere = fk_categorie_fille;


alter table llx_societe add price_level tinyint(4) NULL;

delete from llx_document_model where nom = 'huitre' and type = 'invoice';

drop table llx_don_projet;

alter table llx_facture_fourn_det add column date_start        datetime   DEFAULT NULL;
alter table llx_facture_fourn_det add column date_end          datetime   DEFAULT NULL;

alter table llx_commandedet add column  product_type		  integer    DEFAULT 0 after total_ttc;

alter table llx_propaldet add column  product_type		  integer    DEFAULT 0 after total_ttc;
alter table llx_propaldet add column  date_start         datetime   DEFAULT NULL after product_type;
alter table llx_propaldet add column  date_end           datetime   DEFAULT NULL after date_start;

alter table llx_commande_fournisseurdet add column  product_type	integer    DEFAULT 0 after total_ttc;
alter table llx_commande_fournisseurdet add column  date_start     datetime   DEFAULT NULL after product_type;
alter table llx_commande_fournisseurdet add column  date_end       datetime   DEFAULT NULL after date_start;
alter table llx_commande_fournisseur drop column  product_type;
alter table llx_commande_fournisseur drop column  date_start;
alter table llx_commande_fournisseur drop column  date_end;

-- V4.1 delete from llx_projet_task where fk_projet not in (select rowid from llx_projet);
-- V4.1 ALTER TABLE llx_projet_task ADD CONSTRAINT fk_projet_task_fk_projet FOREIGN KEY (fk_projet)    REFERENCES llx_projet (rowid);

ALTER TABLE llx_adherent modify fk_adherent_type integer NOT NULL;
ALTER TABLE llx_adherent ADD INDEX idx_adherent_fk_adherent_type (fk_adherent_type);
-- V4.1 delete from llx_adherent where fk_adherent_type not in (select rowid from llx_adherent_type);
-- V4.1 ALTER TABLE llx_adherent ADD CONSTRAINT fk_adherent_adherent_type FOREIGN KEY (fk_adherent_type)    REFERENCES llx_adherent_type (rowid);

-- Put at the end. Cas have duplicate values
ALTER TABLE llx_categorie_association drop index idx_categorie_association_fk_categorie_fille;
ALTER TABLE llx_categorie_association ADD UNIQUE INDEX uk_categorie_association (fk_categorie_mere, fk_categorie_fille);
ALTER TABLE llx_categorie_association ADD UNIQUE INDEX uk_categorie_association_fk_categorie_fille (fk_categorie_fille);
