--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.4.0 or higher. 
--

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

ALTER TABLE llx_adherent drop index login;
ALTER TABLE llx_adherent ADD UNIQUE INDEX uk_adherent_login (login);
ALTER TABLE llx_adherent add column fk_soc           integer NULL after societe;

ALTER TABLE llx_societe drop column rubrique;

-- SAINT PIERRE ET MIQUELON
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1931,193,  '0','0','No VAT in SPM',1);

-- SAINT MARTIN
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2461,246,  '0','0','VAT Rate 0',1);

