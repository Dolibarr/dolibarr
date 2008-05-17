--
-- $Id$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.2.0 
--

DROP TABLE llx_facture_tva_sum;
DROP TABLE llx_c_ape;

delete from llx_const where name='MAIN_GRAPH_LIBRARY' and (value like 'phplot%' or value like 'artichow%');

ALTER TABLE llx_societe_adresse_livraison ADD COLUMN tel varchar(20) after fk_pays;
ALTER TABLE llx_societe_adresse_livraison ADD COLUMN fax varchar(20) after tel;

alter table llx_c_barcode_type modify coder varchar(16) NOT NULL;
update llx_c_barcode_type set coder = 0 where coder in (1,2);

update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_ALL'      and value='MAIN_FORCE_SETLOCALE_LC_ALL';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_MONETARY' and value='MAIN_FORCE_SETLOCALE_LC_MONETARY';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_NUMERIC'  and value='MAIN_FORCE_SETLOCALE_LC_NUMERIC';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_TIME'     and value='MAIN_FORCE_SETLOCALE_LC_TIME';

-- remove old deprecated options
update llx_const set name='SOCIETE_CODECLIENT_ADDON' where name='CODECLIENT_ADDON';
update llx_const set name='SOCIETE_CODEFOURNISSEUR_ADDON' where name='CODEFOURNISSEUR_ADDON';
delete from llx_const where name='CODECLIENT_ADDON';
delete from llx_const where name='CODEFOURNISSEUR_ADDON';

alter table llx_const add tms timestamp;
update llx_const set tms=sysdate() where tms is null;
update llx_const set tms=sysdate() where tms <= 0;


alter table llx_document_model modify type varchar(20) NOT NULL;

DELETE FROM llx_rights_def WHERE module = 'menudb';

ALTER table llx_boxes_def drop column name;
ALTER table llx_boxes_def add column tms timestamp;

-- Rename primary key of llx_menu
ALTER TABLE llx_menu_const drop foreign key fk_menu_const_fk_menu;
alter table llx_menu drop primary key;
alter table llx_menu modify rowid integer AUTO_INCREMENT NOT NULL PRIMARY KEY;
ALTER TABLE llx_menu_const ADD CONSTRAINT fk_menu_const_fk_menu FOREIGN KEY (fk_menu) REFERENCES llx_menu (rowid);

alter table llx_menu modify user integer NOT NULL default '0';
alter table llx_menu change `order` position integer NOT NULL;
alter table llx_menu change `right` perms varchar(255);
alter table llx_menu add column module varchar(64) after menu_handler;
alter table llx_menu add column tms timestamp;

-- Add a unique key
update llx_menu set url='/comm/prospect/prospects.php?leftmenu=prospects' where rowid=702 and url='/contact/index.php?leftmenu=prospects&type=p';
ALTER TABLE llx_menu ADD UNIQUE INDEX idx_menu_uk_menu (menu_handler, fk_menu, url);

-- Drop unused table
drop table if exists llx_so_gr;

-- Modification expedition
create table llx_co_exp
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande   integer NOT NULL,
  fk_expedition integer NOT NULL,

  key(fk_commande),
  key(fk_expedition)
)type=innodb;

-- V4 ALTER TABLE llx_expedition DROP INDEX fk_expedition_methode;
-- V4 ALTER TABLE llx_expedition DROP INDEX fk_commande;
-- V4 ALTER TABLE llx_expedition DROP INDEX ref;
-- V4 ALTER TABLE llx_expeditiondet DROP INDEX fk_expedition;
-- V4 ALTER TABLE llx_expeditiondet DROP INDEX fk_commande_ligne;

alter table llx_expedition add column fk_soc integer NOT NULL after ref;
alter table llx_expedition add column fk_adresse_livraison integer DEFAULT NULL after date_expedition;
-- V4.1 UPDATE llx_expedition as e SET e.fk_soc = (SELECT c.fk_soc FROM llx_commande AS c WHERE e.fk_commande = c.rowid);
-- V4.1 UPDATE llx_expedition as e SET e.fk_adresse_livraison = (SELECT c.fk_adresse_livraison FROM llx_commande AS c WHERE e.fk_commande = c.rowid);
update llx_expedition set fk_adresse_livraison=NULL where fk_adresse_livraison = 0;

ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_soc (fk_soc);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_user_author (fk_user_author);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_user_valid (fk_user_valid);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_adresse_livraison (fk_adresse_livraison);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_expedition_methode (fk_expedition_methode);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_soc                FOREIGN KEY (fk_soc)                 REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_user_author        FOREIGN KEY (fk_user_author)         REFERENCES llx_user (rowid);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_user_valid         FOREIGN KEY (fk_user_valid)          REFERENCES llx_user (rowid);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_adresse_livraison  FOREIGN KEY (fk_adresse_livraison)   REFERENCES llx_societe_adresse_livraison (rowid);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_expedition_methode FOREIGN KEY (fk_expedition_methode)  REFERENCES llx_expedition_methode (rowid);
ALTER TABLE llx_expedition ADD UNIQUE INDEX idx_expedition_uk_ref (ref);

ALTER TABLE llx_expeditiondet CHANGE fk_commande_ligne fk_origin_line integer;
ALTER TABLE llx_expeditiondet ADD COLUMN fk_entrepot integer after fk_origin_line;
ALTER TABLE llx_expeditiondet ADD COLUMN rang integer DEFAULT 0 after qty;
-- V4.1 UPDATE llx_expeditiondet as ed SET ed.fk_entrepot = (SELECT e.fk_entrepot FROM llx_expedition AS e WHERE ed.fk_expedition = e.rowid);
ALTER TABLE llx_expedition DROP COLUMN fk_entrepot;

ALTER TABLE llx_expeditiondet ADD INDEX idx_expeditiondet_fk_expedition (fk_expedition);
ALTER TABLE llx_expeditiondet ADD INDEX idx_expeditiondet_fk_entrepot (fk_entrepot);
-- V4 ALTER TABLE llx_expeditiondet ADD CONSTRAINT fk_expeditiondet_fk_expedition FOREIGN KEY (fk_expedition) REFERENCES llx_expedition (rowid);
-- V4 ALTER TABLE llx_expeditiondet ADD CONSTRAINT fk_expeditiondet_fk_entrepot   FOREIGN KEY (fk_entrepot)   REFERENCES llx_entrepot (rowid);

-- Modification livraison
create table llx_co_liv
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande   integer NOT NULL,
  fk_livraison  integer NOT NULL,

  key(fk_commande),
  key(fk_livraison)
)type=innodb;

-- V4 ALTER TABLE llx_livraison DROP INDEX fk_commande;
-- V4 ALTER TABLE llx_livraison DROP INDEX ref;
-- V4 ALTER TABLE llx_livraisondet DROP INDEX fk_livraison;
-- V4 ALTER TABLE llx_livraisondet DROP INDEX fk_commande_ligne;
ALTER TABLE llx_livraison DROP COLUMN total_ttc;

ALTER TABLE llx_livraison add column total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_livraison MODIFY total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_livraison MODIFY fk_adresse_livraison integer DEFAULT NULL;
alter table llx_livraison add column ref_client varchar(30) after ref;
alter table llx_livraison add column fk_soc integer NOT NULL after ref_client;
UPDATE llx_livraison SET fk_adresse_livraison = NULL WHERE fk_adresse_livraison = 0;
-- V4.1 UPDATE llx_livraison as l SET l.fk_soc = (SELECT c.fk_soc FROM llx_commande AS c WHERE l.fk_commande = c.rowid);

ALTER TABLE llx_livraison ADD INDEX idx_livraison_fk_soc (fk_soc);
ALTER TABLE llx_livraison ADD INDEX idx_livraison_fk_user_author (fk_user_author);
ALTER TABLE llx_livraison ADD INDEX idx_livraison_fk_user_valid (fk_user_valid);
ALTER TABLE llx_livraison ADD INDEX idx_livraison_fk_adresse_livraison (fk_adresse_livraison);
-- V4 ALTER TABLE llx_livraison ADD CONSTRAINT fk_livraison_fk_soc                FOREIGN KEY (fk_soc)                 REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_livraison ADD CONSTRAINT fk_livraison_fk_user_author        FOREIGN KEY (fk_user_author)         REFERENCES llx_user (rowid);
-- V4 ALTER TABLE llx_livraison ADD CONSTRAINT fk_livraison_fk_user_valid         FOREIGN KEY (fk_user_valid)          REFERENCES llx_user (rowid);
-- V4 ALTER TABLE llx_livraison ADD CONSTRAINT fk_livraison_fk_adresse_livraison  FOREIGN KEY (fk_adresse_livraison)   REFERENCES llx_societe_adresse_livraison (rowid);
ALTER TABLE llx_livraison ADD UNIQUE INDEX idx_expedition_uk_ref (ref);

alter table llx_livraisondet add column fk_product  integer after fk_livraison;
alter table llx_livraisondet add column description text after fk_product;
alter table llx_livraisondet add column subprice    double(24,8) DEFAULT 0 after qty;
alter table llx_livraisondet add column total_ht    double(24,8) DEFAULT 0 after subprice;
alter table llx_livraisondet add column rang        integer      DEFAULT 0 after total_ht;

ALTER TABLE llx_livraisondet ADD INDEX idx_livraisondet_fk_expedition (fk_livraison);
-- V4 ALTER TABLE llx_livraisondet ADD CONSTRAINT fk_livraisondet_fk_livraison FOREIGN KEY (fk_livraison) REFERENCES llx_livraison (rowid);

create table llx_pr_exp
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_propal     integer NOT NULL,
  fk_expedition integer NOT NULL,

  key(fk_propal),
  key(fk_expedition)
)type=innodb;

create table llx_pr_liv
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_propal     integer NOT NULL,
  fk_livraison  integer NOT NULL,

  key(fk_propal),
  key(fk_livraison)
)type=innodb;

ALTER TABLE llx_paiement modify fk_bank integer NOT NULL DEFAULT 0;


create table llx_element_element
(
  rowid           	integer AUTO_INCREMENT PRIMARY KEY,  
  sourceid			integer NOT NULL,
  sourcetype		varchar(12) NOT NULL,
  targetid			integer NOT NULL,
  targettype		varchar(12) NOT NULL
) type=innodb;


ALTER TABLE llx_element_element 
	ADD UNIQUE INDEX idx_element_element_idx1 (sourceid, sourcetype, targetid, targettype);

ALTER TABLE llx_element_element ADD INDEX idx_element_element_targetid (targetid);


ALTER  TABLE llx_actioncomm add column fk_user_mod integer after fk_user_author;
ALTER  TABLE llx_actioncomm add column fk_user_done integer after fk_user_action;

drop table if exists llx_events;
create table llx_events
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  tms            timestamp,            
  type			 varchar(32)  NOT NULL, 
  dateevent      datetime,             
  fk_user        integer,              
  description    varchar(250) NOT NULL,        
  ip			 varchar(32) NOT NULL,
  fk_object      integer               
) type=innodb;


ALTER TABLE llx_events ADD INDEX idx_events_dateevent (dateevent);

ALTER TABLE llx_c_forme_juridique ADD isvatexempted	tinyint DEFAULT 0  NOT NULL after libelle;

ALTER TABLE llx_facturedet        ADD product_type	  integer      DEFAULT NULL after total_ttc;
ALTER TABLE llx_facture_fourn_det ADD product_type	  integer      DEFAULT NULL after total_ttc;

-- V4.1 update llx_facturedet        set product_type = 0 where fk_product in (select rowid from llx_product where fk_product_type = 0);
-- V4.1 update llx_facture_fourn_det set product_type = 0 where fk_product in (select rowid from llx_product where fk_product_type = 0);
-- V4.1 update llx_facturedet        set product_type = 1 where fk_product in (select rowid from llx_product where fk_product_type = 1);
-- V4.1 update llx_facture_fourn_det set product_type = 1 where fk_product in (select rowid from llx_product where fk_product_type = 1);
-- V4.1 update llx_facturedet        set product_type = 1 where product_type is null;
-- V4.1 update llx_facture_fourn_det set product_type = 1 where product_type is null;

create table llx_c_prospectlevel
(
  code            varchar(12) PRIMARY KEY,
  label           varchar(30),
  sortorder       smallint,
  active          smallint    DEFAULT 1 NOT NULL
) type=innodb;

insert into llx_c_prospectlevel (code,label,sortorder) values ('PL_UNKOWN',    'Unknown',  1);
insert into llx_c_prospectlevel (code,label,sortorder) values ('PL_LOW',       'Low',      2);
insert into llx_c_prospectlevel (code,label,sortorder) values ('PL_MEDIUM',    'Medium',   3);
insert into llx_c_prospectlevel (code,label,sortorder) values ('PL_HIGH',      'High',     4);


alter table llx_societe add column fk_prospectlevel varchar(12) after fournisseur;
alter table llx_societe modify tva_assuj tinyint        DEFAULT 1;


--update llx_actioncomm set datea = datep where datea is null and percent = 100;
--update llx_actioncomm set datea2 = datea where datea2 is null and percent = 100;
update llx_actioncomm set datep = datea where datep is null and datea is not null;
update llx_actioncomm set datep = datec where datep is null and datea is null;
update llx_actioncomm set datep2 = datep where datep2 is null and percent = 100;


alter table llx_projet modify fk_soc           integer;

update llx_rights_def set module='societe' where module='commercial' and perms='client' and subperms='voir';

insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (25, 'Impots revenus',         0,1,'TAXREV');

alter table llx_socpeople add   priv           smallint NOT NULL DEFAULT 0 after jabberid;

alter table llx_tva modify fk_bank         integer;

delete from llx_const where name='MAIN_USE_PREVIEW_TABS';

alter table llx_menu_const drop column user;
update llx_menu set leftmenu = '1' where leftmenu != '0';
alter table llx_menu modify leftmenu varchar(1) default '1';


create table llx_ecm_directories
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  label           varchar(32) NOT NULL,
  fk_parent       integer,
  description     varchar(255) NOT NULL,
  cachenbofdoc    integer NOT NULL DEFAULT 0,
  date_c		  datetime,
  date_m		  timestamp,
  fk_user_c		  integer,
  fk_user_m		  integer
) type=innodb;

create table llx_ecm_document
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(16)  NOT NULL,
  filename        varchar(255) NOT NULL,
  filesize        integer      NOT NULL,
  filemime        varchar(32)  NOT NULL,
  fullpath_dol    varchar(255) NOT NULL,
  fullpath_orig   varchar(255) NOT NULL,
  description     text,
  manualkeyword   text,
  fk_create       integer  NOT NULL,
  fk_update       integer,
  date_c	      datetime NOT NULL,
  date_u		  timestamp,
  fk_directory    integer,
  fk_status		  smallint DEFAULT 0,
  private         smallint DEFAULT 0
) type=innodb;

ALTER TABLE llx_bank modify num_chq varchar(50);

ALTER TABLE llx_menu_const ADD UNIQUE KEY uk_menu_const(fk_menu, fk_constraint);

INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (100, 'USLetter',    'Format Letter (A)',    '216',  '279',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (105, 'USLegal',     'Format Legal',     '216',  '356',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (110, 'USExecutive', 'Format Executive', '190',  '254',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (115, 'USLedger',    'Format Ledger/Tabloid (B)', '279',  '432',  'mm', 0);

INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (200, 'Canadian P1', 'Format Canadian P1',    '560',  '860',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (205, 'Canadian P2', 'Format Canadian P2',    '430',  '560',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (210, 'Canadian P3', 'Format Canadian P3',    '280',  '430',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (215, 'Canadian P4', 'Format Canadian P4',    '215',  '280',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (220, 'Canadian P5', 'Format Canadian P5',    '140',  '215',  'mm', 0);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (225, 'Canadian P6', 'Format Canadian P6',    '107',  '140',  'mm', 0);

ALTER TABLE llx_commande_fournisseurdet DROP COLUMN price;

alter table llx_adherent modify fk_user_mod integer;
alter table llx_adherent modify fk_user_valid integer;
