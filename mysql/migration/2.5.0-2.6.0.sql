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

ALTER TABLE llx_bank ADD INDEX idx_bank_datev(datev);
ALTER TABLE llx_bank ADD INDEX idx_bank_dateo(dateo);
ALTER TABLE llx_bank ADD INDEX idx_bank_fk_account(fk_account);
ALTER TABLE llx_bank ADD INDEX idx_bank_rappro(rappro);

