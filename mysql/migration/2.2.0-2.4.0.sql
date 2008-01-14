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

