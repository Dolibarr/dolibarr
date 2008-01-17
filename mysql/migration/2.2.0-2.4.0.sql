--
-- $Id$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.2.0 
--

delete from llx_const where name='MAIN_GRAPH_LIBRARY' and (value like 'phplot%' or value like 'artichow%');

ALTER TABLE llx_societe_adresse_livraison ADD COLUMN tel varchar(20) after fk_pays;
ALTER TABLE llx_societe_adresse_livraison ADD COLUMN fax varchar(20) after tel;

alter table llx_c_barcode_type modify coder varchar(16) NOT NULL;
update llx_c_barcode_type set coder = 0 where coder in (1,2);

update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_ALL'      and value='MAIN_FORCE_SETLOCALE_LC_ALL';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_MONETARY' and value='MAIN_FORCE_SETLOCALE_LC_MONETARY';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_NUMERIC'  and value='MAIN_FORCE_SETLOCALE_LC_NUMERIC';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_TIME'     and value='MAIN_FORCE_SETLOCALE_LC_TIME';

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
alter table llx_expedition add column fk_adresse_livraison integer after date_expedition;
-- V4.1 UPDATE llx_expedition as e SET e.fk_soc = (SELECT c.fk_soc FROM llx_commande AS c WHERE e.fk_commande = c.rowid);
-- V4.1 UPDATE llx_expedition as e SET e.fk_adresse_livraison = (SELECT c.fk_adresse_livraison FROM llx_commande AS c WHERE e.fk_commande = c.rowid);

ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_soc (fk_soc);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_user_author (fk_user_author);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_user_valid (fk_user_valid);
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_expedition_methode (fk_expedition_methode);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_soc                FOREIGN KEY (fk_soc)                 REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_user_author        FOREIGN KEY (fk_user_author)         REFERENCES llx_user (rowid);
-- V4 ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_user_valid         FOREIGN KEY (fk_user_valid)          REFERENCES llx_user (rowid);
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
