--
-- $Id$
-- $Source$
-- $Revision$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.0.0 
-- sans AUCUNE erreur ni warning
--

create table llx_paiementfourn_facturefourn
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiementfourn INT(11) DEFAULT NULL,
  fk_facturefourn  INT(11) DEFAULT NULL,
  amount DOUBLE DEFAULT '0'
) TYPE=innodb;
ALTER TABLE llx_paiementfourn_facturefourn ADD INDEX idx_paiementfourn_facturefourn_fk_facture(fk_facturefourn);
ALTER TABLE llx_paiementfourn_facturefourn ADD INDEX idx_paiementfourn_facturefourn_fk_paiement(fk_paiementfourn);


drop table if exists llx_commande_model_pdf;
drop table if exists llx_commande_fournisseur_model_pdf;

alter table llx_commande add column note_public text after note;

alter table llx_contrat add column note text;
alter table llx_contrat add column note_public text after note;

alter table llx_facture add column note_public text after note;
alter table llx_facture add column remise_absolue real DEFAULT 0 after remise_percent;
alter table llx_facture add column close_code  varchar(16)  after remise;
alter table llx_facture add column close_note  varchar(128) after close_code;
alter table llx_facture modify close_code  varchar(16);

alter table llx_propal add column note_public text after note;
alter table llx_propal add column remise_absolue real DEFAULT 0 after remise_percent;

alter table llx_commande add column remise_absolue real DEFAULT 0 after remise_percent;

ALTER TABLE llx_societe add mode_reglement tinyint;
ALTER TABLE llx_societe add cond_reglement tinyint;
ALTER TABLE llx_societe add tva_assuj      tinyint DEFAULT '1';
ALTER TABLE llx_societe add email          varchar(128) after url;


ALTER TABLE llx_societe change active statut tinyint DEFAULT 0;

ALTER TABLE llx_societe modify mode_reglement     tinyint NULL;
ALTER TABLE llx_societe modify cond_reglement     tinyint NULL;
ALTER TABLE llx_societe modify cond_reglement     tinyint NULL;
ALTER TABLE llx_societe modify fk_stcomm          tinyint        DEFAULT 0;
ALTER TABLE llx_societe modify services           tinyint        DEFAULT 0;
ALTER TABLE llx_societe modify client             tinyint        DEFAULT 0;
ALTER TABLE llx_societe modify fournisseur        tinyint        DEFAULT 0;

ALTER TABLE llx_societe drop column id;

ALTER TABLE llx_societe modify parent             integer;
UPDATE llx_societe set parent = null where parent = 0;

ALTER TABLE llx_product ADD COLUMN stock_loc VARCHAR(10) DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN gencode VARCHAR(255) DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN weight float DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN weight_units tinyint DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN canvas varchar(15) DEFAULT '';

ALTER TABLE llx_stock_mouvement ADD COLUMN price FLOAT(13,4) DEFAULT 0;

insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (6,'PROFORMA',    6,1, 'Proforma','Réglement avant livraison',0,0);

alter table llx_cond_reglement add (decalage smallint(6) default 0);

alter table llx_commande add fk_cond_reglement int(11) DEFAULT NULL;
alter table llx_commande add fk_mode_reglement int(11) DEFAULT NULL;

create table llx_comfourn_facfourn
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer NOT NULL,
  fk_facture  integer NOT NULL,

  key(fk_commande),
  key(fk_facture)
)type=innodb;



alter table llx_categorie drop column fk_statut;
alter table llx_categorie add visible tinyint DEFAULT 1 NOT NULL;


alter table llx_c_actioncomm  add module varchar(16) DEFAULT NULL after libelle;

delete from llx_c_actioncomm where id in (1,2,3,4,5,8,9,50);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 1, 'AC_TEL',  'system', 'Appel Téléphonique' ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 2, 'AC_FAX',  'system', 'Envoi Fax'          ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 3, 'AC_PROP', 'system', 'Envoi Proposition'  ,'propal');
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 4, 'AC_EMAIL','system', 'Envoi Email'        ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 5, 'AC_RDV',  'system', 'Rendez-vous'        ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 8, 'AC_COM',  'system', 'Envoi Commande'     ,'order');
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 9, 'AC_FAC',  'system', 'Envoi Facture'      ,'invoice');
insert into llx_c_actioncomm (id, code, type, libelle, module) values (50, 'AC_OTH',  'system', 'Autre'              ,NULL);

alter table llx_actioncomm modify datea datetime;
alter table llx_actioncomm add column datec datetime after id;
alter table llx_actioncomm add column datep datetime after datec;
alter table llx_actioncomm add column datep2 datetime after datep;
alter table llx_actioncomm add column datea2 datetime after datea;
alter table llx_actioncomm add column tms timestamp after datea2;
alter table llx_actioncomm add column fk_commande integer after propalrowid;
alter table llx_actioncomm add column fk_parent integer NOT NULL default 0 after fk_contact;
alter table llx_actioncomm add column durationp real after percent;
alter table llx_actioncomm add column durationa real after durationp;
alter table llx_actioncomm add column fk_projet integer after label;
alter table llx_actioncomm add column punctual smallint NOT NULL default 1 after priority;


update llx_actioncomm set datec = datea where datec is null;
update llx_actioncomm set datep = datea where datep is null AND percent < 100;
update llx_actioncomm set datep = datec where datea is null AND datep is null AND percent < 100;
update llx_actioncomm set datea = datec where datea is null AND datep is null AND percent = 100;
update llx_actioncomm set fk_action = '8' where fk_action =  '3' and label = 'Envoi commande par mail';



drop table if exists llx_expedition_model_pdf;


create table llx_product_det
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_product     integer      DEFAULT 0 NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  label          varchar(255) NOT NULL,
  description    text,
  note           text
)type=innodb;

ALTER TABLE `llx_propal` ADD `date_livraison` DATE;
ALTER TABLE `llx_commande` ADD `date_livraison` DATE;
update llx_commande set date_livraison = null where date_livraison = '0000-00-00';
update llx_commande set date_livraison = null where date_livraison = '1970-01-01';

ALTER TABLE llx_facture_fourn DROP INDEX facnumber;
ALTER TABLE llx_facture_fourn ADD UNIQUE INDEX uk_facture_fourn_ref (facnumber, fk_soc);
ALTER TABLE llx_facture_fourn ADD note_public text after note;

ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_facture (fk_facture_fourn);
ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_facture FOREIGN KEY (fk_facture_fourn) REFERENCES llx_facture_fourn (rowid);


ALTER TABLE llx_facturedet ADD COLUMN fk_remise_except	integer NULL AFTER remise;
ALTER TABLE llx_facturedet ADD COLUMN total_ht        real AFTER price;
ALTER TABLE llx_facturedet ADD COLUMN total_tva       real AFTER total_ht;
ALTER TABLE llx_facturedet ADD COLUMN total_ttc       real AFTER total_tva;
ALTER TABLE llx_facturedet ADD COLUMN info_bits		  integer DEFAULT 0 AFTER date_end;

UPDATE llx_facturedet SET info_bits=0 where (fk_remise_except IS NULL OR fk_remise_except = 0);

ALTER TABLE llx_propaldet ADD COLUMN total_ht        real AFTER price;
ALTER TABLE llx_propaldet ADD COLUMN total_tva       real AFTER total_ht;
ALTER TABLE llx_propaldet ADD COLUMN total_ttc       real AFTER total_tva;
ALTER TABLE llx_propaldet ADD COLUMN info_bits		 integer DEFAULT 0 AFTER total_ttc;

ALTER TABLE llx_commandedet ADD COLUMN total_ht        real AFTER price;
ALTER TABLE llx_commandedet ADD COLUMN total_tva       real AFTER total_ht;
ALTER TABLE llx_commandedet ADD COLUMN total_ttc       real AFTER total_tva;
ALTER TABLE llx_commandedet ADD COLUMN info_bits	   integer DEFAULT 0 AFTER total_ttc;

ALTER TABLE llx_contratdet ADD COLUMN total_ht        real AFTER price_ht;
ALTER TABLE llx_contratdet ADD COLUMN total_tva       real AFTER total_ht;
ALTER TABLE llx_contratdet ADD COLUMN total_ttc       real AFTER total_tva;
ALTER TABLE llx_contratdet ADD COLUMN info_bits	      integer DEFAULT 0 AFTER total_ttc;


ALTER TABLE llx_commande ADD INDEX idx_commande_fk_soc (fk_soc);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);

ALTER TABLE llx_commande_fournisseur ADD INDEX idx_commande_fournisseur_fk_soc (fk_soc);
ALTER TABLE llx_commande_fournisseur ADD CONSTRAINT fk_commande_fournisseur_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);


alter table llx_commande_fournisseur add note_public text after note;


drop table if exists llx_avoir_model_pdf;


drop table if exists llx_soc_recontact;


update llx_const set name='PRODUIT_DESC_IN_FORM' where name='FORM_ADD_PROD_DESC';
update llx_const set name='PRODUIT_CHANGE_PROD_DESC' where name='CHANGE_PROD_DESC';
update llx_const set name='COMMANDE_ADD_PROD_DESC' where name='COM_ADD_PROD_DESC';
update llx_const set name='PROPALE_ADD_PROD_DESC' where name='PROP_ADD_PROD_DESC';
update llx_const set name='DON_FORM' where name='DONS_FORM';
update llx_const set name='MAIN_SIZE_LISTE_LIMIT' where name='SIZE_LISTE_LIMIT';
update llx_const set name='SOCIETE_FISCAL_MONTH_START' where name='FISCAL_MONTH_START';
update llx_const set visible=0 where name='FACTURE_DISABLE_RECUR';
update llx_const set visible=0 where name='MAILING_EMAIL_FROM';
update llx_const set visible=1 where name='PRODUIT_CONFIRM_DELETE_LINE';
update llx_const set name='NOTIFICATION_EMAIL_FROM', visible=0 where name='MAIN_MAIL_FROM';
update llx_const set name='NOTIFICATION_EMAIL_FROM', visible=0 where name='MAIN_EMAIL_FROM';
update llx_const set value=2048,visible=1 where name='MAIN_UPLOAD_DOC' and value=1;


insert into llx_const(name,value,type,visible,note) values('MAIN_SHOW_DEVELOPMENT_MODULES','0','yesno',1,'Make development modules visible');
insert into llx_const(name,value,type,visible,note) values('PRODUCT_SHOW_WHEN_CREATE','1','yesno',1,'Add products\' list in first step of proposal, invoice, order creation');

delete from llx_const where name in ('OSC_CATALOG_URL','OSC_LANGUAGE_ID');

alter table llx_paiementfourn add statut smallint(6) NOT NULL DEFAULT 0;


alter table llx_bank_url add column type enum("company","payment","member","donation","charge");

update llx_bank_url set type = 'payment_supplier' where label = '(paiement)' and type='payment' and url like '%/fourn/%';

alter table llx_bank_url drop index fk_bank;
alter table llx_bank_url drop index fk_bank_2;
alter table llx_bank_url drop index fk_bank_3;
alter table llx_bank_url drop index fk_bank_4;
alter table llx_bank_url drop index fk_bank_5;
alter table llx_bank_url drop index fk_bank_6;
alter table llx_bank_url drop index fk_bank_7;
alter table llx_bank_url drop index fk_bank_8;
alter table llx_bank_url drop index fk_bank_9;

ALTER TABLE llx_bank_url DROP INDEX uk_bank_url;
ALTER TABLE llx_bank_url ADD UNIQUE INDEX uk_bank_url (fk_bank,url_id,type);

create table llx_societe_adresse_livraison
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec	             datetime,
  tms                timestamp,
  fk_societe         integer        DEFAULT 0,
  nom                varchar(60),
  address            varchar(255),
  cp                 varchar(10),
  ville              varchar(50),
  fk_departement     integer        DEFAULT 0,
  fk_pays            integer        DEFAULT 0,
  note               text,
  fk_user_creat      integer,
  fk_user_modif      integer
)type=innodb;

alter table llx_societe_adresse_livraison add column label varchar(30) after tms;

alter table llx_propal add column fk_adresse_livraison integer after date_livraison;
alter table llx_commande add column fk_adresse_livraison integer after date_livraison;


insert into llx_c_pays (rowid,code,libelle) values (29, 'AU', 'Australie'      );
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (2901,29,2901,     '',0,'Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'NSW','',1,'','New South Wales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'VIC','',1,'','Victoria');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'QLD','',1,'','Queensland');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901, 'SA','',1,'','South Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'ACT','',1,'','Australia Capital Territory');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'TAS','',1,'','Tasmania');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901, 'WA','',1,'','Western Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901, 'NT','',1,'','Northern Territory');
delete from llx_c_tva where rowid='291' and fk_pays='5';
delete from llx_c_tva where rowid='292' and fk_pays='5';
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (291, 29,  '10','0','VAT Rate 10',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (292, 29,   '0','0','VAT Rate 0',1);


update llx_const set name='DON_ADDON_MODEL' where name='DON_ADDON';
update llx_const set value='neptune' where value='pluton' and name = 'FACTURE_ADDON';
update llx_const set value='azur' where value='orange' and name = 'PROPALE_ADDON';
update llx_const set value='mod_commande_diamant' where value='mod_commande_jade' and name ='COMMANDE_ADDON';
insert into llx_const (name, value, type, visible) values ('FICHEINTER_ADDON', 'pacific','chaine',0);

alter table llx_propal_model_pdf rename to llx_document_model;

alter table llx_document_model DROP PRIMARY KEY;
alter table llx_document_model add column rowid integer AUTO_INCREMENT PRIMARY KEY FIRST;
alter table llx_document_model add column type varchar(20) NOT NULL after nom;
update llx_document_model set type='propal' where type='';

delete from llx_document_model where nom='adytek';
delete from llx_document_model where nom='rouge' and type='order';
delete from llx_document_model where nom='azur' and type='order';
delete from llx_document_model where nom='orange' and type='propal';
delete from llx_document_model where nom='transporteur' and type='shipping';
delete from llx_document_model where nom='dorade' and type='shipping';




ALTER TABLE llx_facture ADD UNIQUE INDEX idx_facture_uk_facnumber (facnumber);


ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_fk_soc (fk_soc);
ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_fk_user_author (fk_user_author);
ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_fk_projet (fk_projet);

ALTER TABLE llx_facture_rec ADD CONSTRAINT fk_facture_rec_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_rec ADD CONSTRAINT fk_facture_rec_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

ALTER TABLE llx_facture_rec ADD UNIQUE INDEX idx_facture_rec_uk_titre (titre);

ALTER TABLE llx_commandedet ADD COLUMN fk_remise_except	integer NULL AFTER remise;
ALTER TABLE llx_commandedet ADD COLUMN coef real;
ALTER TABLE llx_commandedet ADD COLUMN special_code tinyint(1) UNSIGNED DEFAULT 0;

ALTER TABLE llx_propaldet ADD COLUMN fk_remise_except	integer NULL AFTER remise;
ALTER TABLE llx_propaldet ADD COLUMN coef real after price;

ALTER TABLE llx_contratdet ADD COLUMN fk_remise_except	integer NULL AFTER remise;

create table llx_livraison
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  fk_commande           integer DEFAULT 0,
  fk_expedition         integer,
  ref                   varchar(30) NOT NULL,
  date_creation         datetime,
  date_valid            datetime,
  fk_user_author        integer,
  fk_user_valid         integer,
  fk_statut             smallint  default 0,
  note                  text,
  note_public           text,
  model_pdf             varchar(50),
  date_livraison 	      date 	  default NULL,
  fk_adresse_livraison  integer,

  UNIQUE INDEX (ref),
  key(fk_commande)
)type=innodb;

alter table llx_livraison drop foreign key fk_livraison_societe;
alter table llx_livraison drop column fk_soc;


create table llx_livraisondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_livraison      integer,
  fk_commande_ligne integer NOT NULL,
  qty               real,
  key(fk_livraison),
  key(fk_commande_ligne)
)type=innodb;


insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (90, 'commande',  'internal', 'SALESREPSIGN',  'Commercial signataire de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (91, 'commande',  'internal', 'SALESREPFOLL',  'Commercial suivi de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (100, 'commande',  'external', 'BILLING',       'Contact client facturation commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (101, 'commande',  'external', 'CUSTOMER',      'Contact client suivi commande', 1);

insert into llx_c_pays (rowid,code,libelle) values (30, 'SG', 'Singapoure');


alter table llx_bank_account add column ref varchar(12) NOT NULL;
alter table llx_bank_account add column url varchar(128);
alter table llx_bank_account add column currency_code varchar(2) NOT NULL;
alter table llx_bank_account add column min_allowed integer DEFAULT 0;
alter table llx_bank_account add column min_desired integer DEFAULT 0;
alter table llx_bank_account add column comment varchar(254);
alter table llx_bank_account modify bic varchar(11);

update llx_bank_account set currency_code='EU';
update llx_bank_account set rappro=0 where courant=2;

ALTER TABLE llx_bank ADD COLUMN fk_bordereau  INTEGER DEFAULT 0;
ALTER TABLE llx_bank ADD COLUMN banque   varchar(255);
ALTER TABLE llx_bank ADD COLUMN emetteur varchar(255);

alter table llx_accountingsystem_det rename to llx_accountingaccount;


insert into llx_rights_def (id, libelle, module, type, bydefault, subperms, perms) values (262,'Consulter tous les clients','commercial','r',1,'voir','client');
-- V4.1 insert into llx_user_rights(fk_user,fk_id) select distinct fk_user, '262' from llx_user_rights where fk_id = 261;
update llx_rights_def set subperms='creer' where subperms='supprimer' AND module='user' AND perms='self' AND id=255;
update llx_rights_def set module='tax' where module='compta' AND id in ('91','92','93');
update llx_rights_def set subperms='receptionner' where id=186;


alter table llx_commandedet add column rang integer DEFAULT 0;
alter table llx_propaldet add column rang integer DEFAULT 0;

alter table llx_facture drop column model;
alter table llx_facture add column model_pdf varchar(50) after note_public;

alter table llx_facture drop foreign key fk_facture_fk_facture;
alter table llx_facture drop column fk_facture;
alter table llx_facture add column fk_facture_source integer after fk_user_valid;
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_facture_source (fk_facture_source);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_source_fk_facture FOREIGN KEY (fk_facture_source)     REFERENCES llx_facture (rowid);
alter table llx_facture add column type smallint DEFAULT 0 NOT NULL after facnumber;


-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_commandedet FROM llx_commandedet LEFT JOIN llx_commande ON llx_commandedet.fk_commande = llx_commande.rowid WHERE llx_commande.rowid IS NULL;

ALTER TABLE llx_commandedet ADD INDEX idx_commandedet_fk_commande (fk_commande);
ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_commande FOREIGN KEY (fk_commande) REFERENCES llx_commande (rowid);


-- drop table llx_societe_remise_except;
create table llx_societe_remise_except
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  datec           datetime,
  amount_ht       real NOT NULL,
  fk_user         integer NOT NULL,
  fk_facture      integer,
  description     varchar(255) NOT NULL
)type=innodb;

-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_societe_remise_except FROM llx_societe_remise_except LEFT JOIN llx_facturedet ON llx_societe_remise_except.fk_facture = llx_facturedet.rowid WHERE llx_facturedet.rowid IS NULL;

ALTER TABLE llx_societe_remise_except DROP FOREIGN KEY fk_societe_remise_fk_facture;

ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_user (fk_user);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_soc (fk_soc);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_facture (fk_facture);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_user    FOREIGN KEY (fk_user)    REFERENCES llx_user (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_soc     FOREIGN KEY (fk_soc)     REFERENCES llx_societe (idp);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_facture FOREIGN KEY (fk_facture) REFERENCES llx_facturedet (rowid);

update llx_societe_remise_except set description='Remise sans description' where description is NULL or description ='';
alter table llx_societe_remise_except modify description varchar(255) NOT NULL;

insert into llx_const (name, value, type, visible, note) VALUES ('PROPALE_VALIDITY_DURATION', '15', 'chaine', 0, 'Durée de validitée des propales');

alter table llx_propal add column ref_client varchar(30) after ref;

alter table llx_societe_adresse_livraison drop column fk_departement;

alter table llx_user change datelastaccess datelastlogin datetime;
alter table llx_user add column datepreviouslogin datetime after datelastlogin;
alter table llx_user add column ldap_sid varchar(255) DEFAULT NULL;
alter table llx_user add column statut tinyint DEFAULT 1;
alter table llx_user add column lang varchar(6);

alter table llx_user add column office_phone      varchar(20);
alter table llx_user add column office_fax        varchar(20);
alter table llx_user add column user_mobile       varchar(20);

alter table llx_user modify login varchar(24) NOT NULL;
alter table llx_user modify code varchar(4) NOT NULL;

ALTER TABLE llx_user ADD UNIQUE uk_user_login (login);
ALTER TABLE llx_user ADD UNIQUE uk_user_code (code);


alter table llx_boxes add column fk_user integer;

alter table llx_commande_fournisseur drop column fk_soc_contact;
alter table llx_commande drop column fk_soc_contact;
alter table llx_livraison drop column fk_soc_contact;
alter table llx_propal drop column fk_soc_contact;

alter table llx_commandedet drop column label;

alter table llx_c_pays modify libelle varchar(50) NOT NULL;

insert into llx_action_def (rowid,code,titre,description,objet_type) values (3,'NOTIFY_VAL_ORDER_SUUPLIER','Validation commande fournisseur','Déclenché lors de la validation d\'une commande fournisseur','order_supplier');


alter table llx_product_price add price_level tinyint(4) NULL DEFAULT 1;

drop table if exists llx_sqltables;


ALTER IGNORE TABLE llx_categorie_product DROP FOREIGN KEY llx_categorie_product_ibfk_1;
ALTER IGNORE TABLE llx_categorie_product DROP FOREIGN KEY llx_categorie_product_ibfk_2;
ALTER IGNORE TABLE llx_categorie_product DROP FOREIGN KEY llx_categorie_product_ibfk_3;
ALTER IGNORE TABLE llx_categorie_product DROP FOREIGN KEY llx_categorie_product_ibfk_4;
ALTER IGNORE TABLE llx_categorie_product DROP FOREIGN KEY llx_categorie_product_ibfk_5;

ALTER TABLE llx_categorie_product ADD CONSTRAINT fk_categorie_product_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_product ADD CONSTRAINT fk_categorie_product_product_rowid   FOREIGN KEY (fk_product)   REFERENCES llx_product (rowid);

ALTER TABLE llx_categorie_product ADD PRIMARY KEY (fk_categorie, fk_product);

alter table llx_product modify label varchar(255) NOT NULL;
alter table llx_product modify description text;
ALTER TABLE llx_product ADD COLUMN price_base_type varchar(3) DEFAULT 'HT' AFTER price;
ALTER TABLE llx_product ADD COLUMN price_ttc float(12,4) DEFAULT 0 AFTER price_base_type;
alter table llx_product_det modify label varchar(255) NOT NULL;
alter table llx_product_det modify description text;

create table llx_accountingdebcred
(
 fk_transaction  integer		NOT NULL,
 fk_account      integer		NOT NULL,
	amount          real		NOT NULL,
	direction       varchar(1)	NOT NULL
)type=innodb;

alter table llx_facturedet_rec add column total_ht real;
alter table llx_facturedet_rec add column total_tva real;
alter table llx_facturedet_rec add column total_ttc real;

alter table llx_adherent add column phone            varchar(30) after email;
alter table llx_adherent add column phone_perso      varchar(30) after phone;
alter table llx_adherent add column phone_mobile     varchar(30) after phone_perso;

update llx_facture set fk_facture_source=null where fk_facture_source is not null and type = 0;


update llx_boxes set fk_user = 0 where fk_user IS NULL;
ALTER TABLE llx_boxes modify fk_user integer default 0 NOT NULL;

-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_boxes FROM llx_boxes LEFT JOIN llx_boxes_def ON llx_boxes.box_id = llx_boxes_def.rowid WHERE llx_boxes_def.rowid IS NULL;

ALTER TABLE llx_boxes ADD INDEX idx_boxes_boxid (box_id);
-- V4 ALTER TABLE llx_boxes ADD CONSTRAINT fk_boxes_box_id FOREIGN KEY (box_id) REFERENCES llx_boxes_def (rowid);

ALTER TABLE llx_boxes ADD INDEX idx_boxes_fk_user (fk_user);


create table llx_categorie_fournisseur
(
  fk_categorie  integer NOT NULL,
  fk_societe    integer NOT NULL,
  UNIQUE (fk_categorie, fk_societe)
)type=innodb;

create table llx_fournisseur_categorie
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  label         varchar(255)

)type=innodb;

create table llx_fournisseur_ca
(
  fk_societe    integer,
  date_calcul   datetime,
  year          smallint UNSIGNED,
  ca_genere     float,
  UNIQUE (fk_societe, year)
)type=innodb;

alter table llx_fournisseur_ca add ca_achat float(11,2) DEFAULT 0;

create table llx_product_ca
(
  fk_product    integer,
  date_calcul   datetime,
  year          smallint UNSIGNED,
  ca_genere     float,
  UNIQUE (fk_product, year)
)type=innodb;

create table llx_commande_fournisseur_dispatch
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande    integer,
  fk_product     integer,
  qty            float,              -- quantité
  fk_entrepot    integer,
  fk_user        integer,
  datec          datetime
)type=innodb;

ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX (fk_commande);

create table llx_stock_valorisation
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  tms                timestamp,             -- date technique mise à jour automatiquement
  date_valo          datetime,              -- date de valorisation
  fk_product         integer NOT NULL,      -- id du produit concerne par l'operation
  qty_ope            float(9,3),            -- quantité de l'operation
  price_ope          float(12,4),           -- prix unitaire du produit concerne par l'operation
  valo_ope           float(12,4),           -- valorisation de l'operation
  price_pmp          float(12,4),           -- valeur PMP de l'operation
  qty_stock          float(9,3) DEFAULT 0,  -- qunatite en stock
  valo_pmp           float(12,4),           -- valorisation du stock en PMP
  fk_stock_mouvement integer,               -- id du mouvement de stock

  key(fk_product)
)type=innodb;


create table llx_entrepot_valorisation
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,      -- date technique mise à jour automatiquement
  date_calcul     date,           -- date auquel a ete calcule la valeur
  fk_entrepot     integer UNSIGNED NOT NULL ,
  valo_pmp        float(12,4),    -- valoristaion du stock en PMP
  key(fk_entrepot)
)type=innodb;

ALTER TABLE llx_entrepot ADD COLUMN valo_pmp float(12,4) DEFAULT 0;

create table llx_user_entrepot
(
  rowid        integer AUTO_INCREMENT PRIMARY KEY,
  fk_entrepot  integer UNSIGNED, -- pointe sur llx_entrepot
  fk_user      integer UNSIGNED, -- pointe sur llx_user
  consult      tinyint(1) UNSIGNED,
  send         tinyint(1) UNSIGNED
)type=innodb;

create table llx_product_subproduct
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  fk_product            integer NOT NULL, -- id du produit maitre
  fk_product_subproduct integer NOT NULL, -- id du sous-produit
  UNIQUE(fk_product, fk_product_subproduct)
)type=innodb;

alter table llx_product_price add column price_ttc float(12,4) DEFAULT 0 after price;
alter table llx_product_price add column price_base_type varchar(3)  DEFAULT 'HT' after price_ttc;