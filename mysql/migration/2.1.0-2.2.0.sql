--
-- $Id$
-- $Source$
-- $Revision$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.1.0 
-- sans AUCUNE erreur ni warning
--



-- Supprime les doublons de la table llx_categories
-- V4.1 DROP TABLE tmp_categorie1;
-- V4.1 DROP TABLE tmp_categorie2;
-- V4.1 CREATE TABLE tmp_categorie1 SELECT * FROM llx_categorie;
-- V4.1 CREATE TABLE tmp_categorie2 SELECT * FROM llx_categorie;
-- V4.1 delete c from llx_categorie as c where c.rowid in (select distinct c2.rowid from tmp_categorie1 as c2, tmp_categorie2 as cc2 where c2.rowid != cc2.rowid and c2.type = cc2.type and c2.label = cc2.label) and c.rowid not in (select min(c3.rowid) from tmp_categorie1 as c3, tmp_categorie2 as cc3 where c3.rowid != cc3.rowid and c3.type = cc3.type and c3.label = cc3.label group by c3.label,c3.type);
-- V4.1 DROP TABLE tmp_categorie1;
-- V4.1 DROP TABLE tmp_categorie2;
-- Si suppression des doublons precedente a ete faite, on monte la clé sur les categories
-- V4.1 ALTER TABLE llx_categorie ADD UNIQUE INDEX uk_categorie_ref (label,type);

-- On migre les categories fournisseur de la table llx_fournisseur_categorie qui est obsolete vers table llx_categories qui est generique pour gerer les categories de tout type
-- V4.1 INSERT into llx_categorie (label, description, visible, type) (select distinct label, label, 1, 1 from llx_fournisseur_categorie);
-- V4.1 UPDATE llx_categorie_fournisseur as cf SET cf.fk_categorie = IFNULL((SELECT distinct c.rowid from llx_categorie as c, llx_fournisseur_categorie as fc where fc.rowid = cf.fk_categorie AND c.type = 1 AND c.label = fc.label),cf.fk_categorie);

-- Supprime les doublons de la table llx_categories
-- V4.1 DROP TABLE tmp_categorie1;
-- V4.1 DROP TABLE tmp_categorie2;
-- V4.1 CREATE TABLE tmp_categorie1 SELECT * FROM llx_categorie;
-- V4.1 CREATE TABLE tmp_categorie2 SELECT * FROM llx_categorie;
-- V4.1 delete c from llx_categorie as c where c.rowid in (select distinct c2.rowid from tmp_categorie1 as c2, tmp_categorie2 as cc2 where c2.rowid != cc2.rowid and c2.type = cc2.type and c2.label = cc2.label) and c.rowid not in (select min(c3.rowid) from tmp_categorie1 as c3, tmp_categorie2 as cc3 where c3.rowid != cc3.rowid and c3.type = cc3.type and c3.label = cc3.label group by c3.label,c3.type);
-- V4.1 DROP TABLE tmp_categorie1;
-- V4.1 DROP TABLE tmp_categorie2;
-- Si suppression des doublons precedente a ete faite, on monte la clé sur les categories
-- V4.1 ALTER TABLE llx_categorie ADD UNIQUE INDEX uk_categorie_ref (label,type);


-- Corrige mauvaise insertion du a champ trop court
alter table llx_action_def modify code varchar(28) UNIQUE NOT NULL;
alter table llx_action_def modify objet_type varchar(16) NOT NULL;
update llx_action_def set code = 'NOTIFY_VAL_ORDER_SUPPLIER' where code = 'NOTIFY_VAL_ORDER_SUUPLIE';
update llx_action_def set objet_type = 'order_supplier' where code = 'NOTIFY_VAL_ORDER_SUPPLIER';

-- Nettoyage champ ref table llx_bank_account
update llx_bank_account set ref=concat('ACCOUNT',rowid) where (ref='' or ref is null);

update llx_bank_account set currency_code='EU' where (currency_code IS NULL or currency_code='');
alter table llx_bank_account modify currency_code varchar(3) NOT NULL;
update llx_bank_account set currency_code='EUR' where (currency_code IS NULL or currency_code='' or currency_code='EU');

-- Sequence de requete pour nettoyage et correction champ type table llx_bank_url
update llx_bank_url set type='company'  where (type is null or type = '') and url like '%compta/fiche.php?socid=%';
alter table llx_bank_url modify `type` varchar(20);
update llx_bank_url set type='?'  where (type is null or type = '') and url like '%compta/facture.php?facid=%';
update llx_bank_url set type='payment_supplier' where (type='' or type is null) and url like '%fourn/paiement/fiche.php?id=%';
update llx_bank_url set type='?'  where (type is null or type = '');
alter table llx_bank_url modify `type` varchar(20) NOT NULL;

update llx_bank set datev = datec where datev = '1970-01-01 00:00:00' and rappro = 0;
update llx_bank set dateo = datec where datev = '1970-01-01 00:00:00' and rappro = 0;

alter table llx_c_chargesociales add column actioncompta varchar(12) NOT NULL;
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values ( 1, 'Allocations familiales', 1,1,'TAXFAM');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values ( 2, 'GSG Deductible',         1,1,'TAXCSGD');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values ( 3, 'GSG/CRDS NON Deductible',0,1,'TAXCSGND');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (10, 'Taxe apprenttissage',    0,1,'TAXAPP');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (11, 'Taxe professionnelle',   0,1,'TAXPRO');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (20, 'Impots locaux/fonciers', 0,1,'TAXFON');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (30, 'Assurance Sante (SECU-URSSAF)',  0,1,'TAXSECU');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (40, 'Mutuelle',                       0,1,'TAXMUT');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (50, 'Assurance vieillesse (CNAV)',    0,1,'TAXRET');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (60, 'Assurance Chomage (ASSEDIC)',    0,1,'TAXCHOM');
update llx_c_chargesociales set actioncompta='TAXFAM'   where id = 1;
update llx_c_chargesociales set actioncompta='TAXCSGD'  where id = 2;
update llx_c_chargesociales set actioncompta='TAXCSGND' where id = 3;
update llx_c_chargesociales set actioncompta='TAXAPP'   where id = 10;
update llx_c_chargesociales set actioncompta='TAXPRO'   where id = 11;
update llx_c_chargesociales set actioncompta='TAXFON'   where id = 20;
alter table llx_chargesociales modify fk_type integer NOT NULL; 
alter table llx_chargesociales modify libelle varchar(80) NOT NULL;

insert into llx_rights_def (id, libelle, module, type, bydefault, subperms, perms) values (114,'Rapprocher transactions','banque','w',0,null,'consolidate');
update llx_rights_def set libelle='Créer/modifier/supprimer écriture bancaire' where perms='modifier' AND module='banque';

-- Supprime colone en doublon avec fk_user_creat
alter table llx_paiement drop column author;

update llx_actioncomm set fk_action = 9 where fk_action = 10;
update llx_actioncomm set percent = 100 where percent = 0 and datea is not null;

ALTER TABLE llx_cotisation ADD COLUMN datef date after dateadh;
ALTER TABLE llx_cotisation modify datef date;
ALTER TABLE llx_cotisation ADD UNIQUE INDEX uk_cotisation (fk_adherent,dateadh);
-- V4.1 update llx_cotisation set datef = ADDDATE(ADDDATE(dateadh, INTERVAL 1 YEAR),INTERVAL -1 DAY);

delete from llx_const where name='MAIN_SHOW_DEVELOPMENT_MODULES';
delete from llx_const where name='MAIN_ENABLE_DEVELOPMENT';
insert into llx_const(name,value,type,visible,note) values('MAIN_FEATURES_LEVEL','0','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development');

update llx_const set name='MAIN_MAIL_EMAIL_FROM' where name='NOTIFICATION_EMAIL_FROM';

update llx_const set visible=0 where name in ('MAIN_UPLOAD_DOC','MAIN_MAIL_SMTP_SERVER','MAIN_MAIL_SMTP_PORT','MAIN_MAIL_EMAIL_FROM');

update llx_const set value='rodolphe.php' where name='MAIN_MENU_BARRELEFT'      and  value='default.php';
update llx_const set value='rodolphe.php' where name='MAIN_MENU_BARRETOP'       and  value='default.php';
update llx_const set value='rodolphe.php' where name='MAIN_MENUFRONT_BARRELEFT' and  value='default.php';
update llx_const set value='rodolphe.php' where name='MAIN_MENUFRONT_BARRETOP'  and  value='default.php';

delete from llx_adherent_type where libelle IS NULL;
alter table llx_adherent_type modify libelle varchar(50) NOT NULL;


alter table llx_tva add fk_bank         integer NOT NULL;
alter table llx_tva add fk_user_creat   integer;
alter table llx_tva add fk_user_modif   integer;

-- V4.1 UPDATE llx_tva as t set fk_bank = (SELECT IFNULL(MIN(rowid),0) FROM llx_bank as b WHERE b.datev = t.datev AND b.amount = -t.amount AND b.label like 'R%glement TVA') WHERE t.fk_bank = 0;
-- V4.1 UPDATE llx_tva as t set fk_user_creat = (SELECT MIN(fk_user_author) FROM llx_bank as b WHERE b.datev = t.datev AND b.amount = -t.amount AND b.label like 'R%glement TVA') WHERE t.fk_user_creat IS NULL;


-- Extention de la gestion des catégories
alter table llx_categorie ADD type int not null default '0';
-- V4 ALTER TABLE llx_categorie DROP INDEX uk_categorie_ref;

drop table if exists `llx_categorie_societe`;
create table `llx_categorie_societe` (
  `fk_categorie` int(11) not null,
  `fk_societe` int(11) not null,
  UNIQUE KEY `fk_categorie` (`fk_categorie`,`fk_societe`),
  KEY `fk_societe` (`fk_societe`)
) type=innodb;

-- 
alter table `llx_categorie_societe` drop foreign key fk_societe;
alter table `llx_categorie_societe` add constraint `fk_categorie_societe_categorie_rowid` foreign key(`fk_categorie`) REFERENCES `llx_categorie` (`rowid`);
alter table `llx_categorie_societe` add constraint `fk_categorie_societe_fk_soc` foreign key(`fk_societe`) REFERENCES `llx_societe` (`rowid`);

create table `llx_categorie_product` (
  `fk_categorie` int(11) not null,
  `fk_product` int(11) not null,
  PRIMARY KEY  (`fk_categorie`,`fk_product`),
  KEY `idx_categorie_product_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_product_fk_product` (`fk_product`)
) type=innodb;

alter table `llx_categorie_product`
  add constraint `fk_categorie_product_categorie_rowid` foreign key(`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  add constraint `fk_categorie_product_product_rowid` foreign key(`fk_product`) REFERENCES `llx_product` (`rowid`);

  
-- Ajout gestion du droit de prêt
drop table if exists `llx_droitpret_rapport`;
create table `llx_droitpret_rapport` (
  `rowid` int(11) NOT NULL auto_increment,
  `date_envoie` datetime NOT NULL,
  `format` varchar(10) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `fichier` varchar(255) NOT NULL,
  `nbfact` int(11) NOT NULL,
  PRIMARY KEY  (`rowid`)
) type=innodb;


-- Gestion des menu
CREATE TABLE `llx_menu` (
  `rowid` int(11) NOT NULL,
  `menu_handler` varchar(16) NOT NULL default 'auguria',
  `type` enum('top','left') NOT NULL default 'left',
  `mainmenu` varchar(100) NOT NULL,
  `fk_menu` int(11) NOT NULL,
  `order` tinyint(4) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(100) NULL,
  `titre` varchar(255) NOT NULL,
  `langs` varchar(100),
  `level` tinyint(1),
  `leftmenu` varchar(100) NULL,
  `right` varchar(255),
  `user` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) type=innodb;

create table `llx_menu_constraint` (
  `rowid` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  PRIMARY KEY  (`rowid`)
) type=innodb;

create table `llx_menu_const` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_menu` int(11) NOT NULL,
  `fk_constraint` int(11) NOT NULL,
  `user` tinyint(4) NOT NULL default '2',
  PRIMARY KEY  (`rowid`)
) type=innodb;

ALTER TABLE `llx_menu_const` ADD INDEX `idx_menu_const_fk_menu` (`fk_menu`);
ALTER TABLE `llx_menu_const` ADD INDEX `idx_menu_const_fk_constraint` (`fk_constraint`);

ALTER TABLE `llx_menu_const` ADD CONSTRAINT `fk_menu_const_fk_menu` FOREIGN KEY (`fk_menu`) REFERENCES `llx_menu` (`rowid`);
ALTER TABLE `llx_menu_const` ADD CONSTRAINT `fk_menu_const_fk_constraint` FOREIGN KEY (`fk_constraint`) REFERENCES `llx_menu_constraint` (`rowid`);


-- 
-- Contenu de la table `llx_menu`
-- 
delete from llx_menu_const;
delete from llx_menu_constraint;
delete from llx_menu where menu_handler='auguria';
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1, 'home', '', 0, '/index.php?mainmenu=home&leftmenu=', 'Home', -1, '', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2, 'companies', '', 0, '/index.php?mainmenu=companies&amp;leftmenu=', 'ThirdParties', -1, 'companies', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3, 'products', '', 0, '/product/index.php?mainmenu=products&amp;leftmenu=', 'Products/Services', -1, 'products', '$user->rights->produit->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4, 'suppliers', '', 0, '/fourn/index.php?mainmenu=suppliers&amp;leftmenu=', 'Suppliers', -1, 'suppliers', '$user->rights->fournisseur->lire', '', 0, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5, 'commercial', '', 0, '/comm/index.php?mainmenu=commercial&amp;leftmenu=', 'Commercial', -1, 'commercial', '$user->rights->commercial->main->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (6, 'accountancy', '', 0, '/compta/index.php?mainmenu=accountancy&amp;leftmenu=', 'MenuFinancial', -1, 'compta', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->plancompte->lire || $user->rights->commande->lire || $user->rights->facture->lire || $user->rights->banque->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (7, 'project', '', 0, '/projet/index.php?mainmenu=project&amp;leftmenu=', 'Projects', -1, 'projects', '$user->rights->projet->lire', '', 0, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (8, 'tools', '', 0, '/index.php?mainmenu=tools&amp;leftmenu=', 'Tools', -1, 'other', '$user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (9, 'telephony', '', 0, '/telephonie/index.php?mainmenu=telephony&amp;leftmenu=', 'Telephony', -1, 'telephony', '$user->rights->telephonie->lire', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (10, 'energy', '', 0, '/energie/index.php?mainmenu=energy&amp;leftmenu=', 'Energy', -1, 'energy', '', '', 2, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (11, 'shop', '', 0, '/boutique/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (12, 'shop', '', 0, '/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 12);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (13, 'webcal', '', 0, '/webcal/webcal.php?mainmenu=webcal&amp;leftmenu=', 'Calendar', -1, 'other', '', '', 0, 13);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (14, 'mantis', '', 0, '/mantis/mantis.php?mainmenu=mantis', 'BugTracker', -1, 'other', '', '', 2, 14);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (15, 'members', '', 0, '/adherents/index.php?mainmenu=members&amp;leftmenu=', 'Members', -1, 'members', '', '', 2, 15);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (16, 'phenix', '', 0, '/phenix/phenix.php?mainmenu=phenix&amp;leftmenu=', 'Calendar', -1, 'other', '', '', 0, 16);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (100, 'home', '', 1, '/admin/index.php?leftmenu=setup', 'Setup', 0, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (101, 'home', '$leftmenu=="setup"', 100, '/admin/company.php', 'MenuCompanySetup', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (102, 'home', '$leftmenu=="setup"', 100, '/admin/ihm.php', 'GUISetup', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (103, 'home', '$leftmenu=="setup"', 100, '/admin/modules.php', 'Modules', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (104, 'home', '$leftmenu=="setup"', 100, '/admin/boxes.php', 'Boxes', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (105, 'home', '$leftmenu=="setup"', 100, '/admin/menus.php', 'Menus', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (106, 'home', '$leftmenu=="setup"', 100, '/admin/delais.php', 'DelaysBeforeWarning', 1, 'admin', '', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (107, 'home', '$leftmenu=="setup"', 100, '/admin/triggers.php', 'Triggers', 1, 'admin', '', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (108, 'home', '$leftmenu=="setup"', 100, '/admin/perms.php', 'Security', 1, 'admin', '', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (109, 'home', '$leftmenu=="setup"', 100, '/admin/mails.php', 'Emails', 1, 'admin', '', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (110, 'home', '$leftmenu=="setup"', 100, '/admin/limits.php', 'Limits', 1, 'admin', '', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (111, 'home', '$leftmenu=="setup"', 100, '/admin/dict.php', 'DictionnarySetup', 1, 'admin', '', '', 2, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (112, 'home', '$leftmenu=="setup"', 100, '/admin/const.php', 'OtherSetup', 1, 'admin', '', '', 2, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (200, 'home', '', 1, '/admin/system/index.php?leftmenu=system', 'SystemInfo', 0, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (201, 'home', '$leftmenu=="system"', 200, '/admin/system/dolibarr.php', 'Dolibarr', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (202, 'home', '$leftmenu=="system"', 201, '/admin/system/constall.php', 'AllParameters', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (203, 'home', '$leftmenu=="system"', 201, '/about.php', 'About', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (204, 'home', '$leftmenu=="system"', 200, '/admin/system/os.php', 'OS', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (205, 'home', '$leftmenu=="system"', 200, '/admin/system/web.php', 'WebServer', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (206, 'home', '$leftmenu=="system"', 200, '/admin/system/phpinfo.php', 'Php', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (207, 'home', '$leftmenu=="system"', 206, '/admin/system/phpinfo.php?what=conf', 'PhpConf', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (208, 'home', '$leftmenu=="system"', 206, '/admin/system/phpinfo.php?what=env', 'PhpEnv', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (209, 'home', '$leftmenu=="system"', 206, '/admin/system/phpinfo.php?what=modules', 'PhpModules', 2, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (210, 'home', '$leftmenu=="system"', 200, '/admin/system/database.php', 'Database', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (211, 'home', '$leftmenu=="system"', 210, '/admin/system/database-tables.php', 'Tables', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (212, 'home', '$leftmenu=="system"', 210, '/admin/system/database-tables-contraintes.php', 'Constraints', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (300, 'home', '', 1, '/admin/tools/index.php?leftmenu=admintools', 'SystemTools', 0, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (301, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/dolibarr_export.php', 'Backup', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (302, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/dolibarr_import.php', 'Restore', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (303, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/purge.php', 'Purge', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (304, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/eaccelerator.php', 'EAccelerator', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (400, 'home', '', 1, '/user/home.php?leftmenu=users', 'MenuUsersAndGroups', 0, 'users', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (401, 'home', '$leftmenu=="users"', 400, '/user/index.php', 'Users', 1, 'users', '$user->rights->user->user->lire || $user->admin', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (402, 'home', '$leftmenu=="users"', 401, '/user/fiche.php?action=create', 'NewUser', 2, 'users', '$user->rights->user->user->creer || $user->admin', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (403, 'home', '$leftmenu=="users"', 400, '/user/group/index.php', 'Groups', 1, 'users', '$user->rights->user->user->lire || $user->admin', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (404, 'home', '$leftmenu=="users"', 403, '/user/group/fiche.php?action=create', 'NewGroup', 2, 'users', '$user->rights->user->user->creer || $user->admin', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (500, 'companies', '', 2, '/societe.php', 'ThirdParty', 0, 'companies', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (501, 'companies', '', 500, '/soc.php?action=create', 'MenuNewThirdParty', 1, 'companies', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (502, 'companies', '', 500, '/societe/groupe/index.php', 'MenuSocGroup', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (503, 'companies', '', 500, '/fourn/liste.php?leftmenu=suppliers', 'Suppliers', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (504, 'companies', '', 503, '/soc.php?leftmenu=supplier&action=create&type=f', 'NewSupplier', 2, 'suppliers', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (505, 'companies', '', 503, '/contact/index.php?leftmenu=suppliers&type=f', 'Contacts', 2, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (506, 'companies', '', 500, '/comm/prospect/prospects.php?leftmenu=prospects', 'Prospects', 1, 'companies', '$user->rights->societe->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (507, 'companies', '', 506, '/soc.php?leftmenu=prospects&action=create&type=p', 'MenuNewProspect', 2, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (508, 'companies', '', 506, '/contact/index.php?leftmenu=customers&type=p', 'Contacts', 2, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (509, 'companies', '', 500, '/comm/clients.php?leftmenu=customers', 'Customers', 1, 'companies', '$user->rights->societe->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (510, 'companies', '', 509, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 2, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (511, 'companies', '', 509, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 2, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (600, 'companies', '', 2, '/contact/index.php?leftmenu=contacts', 'Contacts', 0, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (601, 'companies', '', 600, '/contact/fiche.php?leftmenu=contacts&action=create', 'NewContact', 1, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (602, 'companies', '', 600, '/contact/index.php?leftmenu=contacts', 'List', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (700, 'commercial', '', 5, '/comm/prospect/index.php?leftmenu=prospects', 'Prospects', 0, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (701, 'commercial', '', 700, '/soc.php?leftmenu=prospects&action=create&type=c', 'MenuNewProspect', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (702, 'commercial', '', 700, '/contact/index.php?leftmenu=prospects&type=p', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (703, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=-1', 'LastProspectDoNotContact', 2, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (704, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=0', 'LastProspectNeverContacted', 2, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (705, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=1', 'LastProspectToContact', 2, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (706, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=2', 'LastProspectContactInProcess', 2, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (707, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=3', 'LastProspectContactDone', 2, 'companies', '$user->rights->societe->lire', '', 0, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (708, 'commercial', '', 700, '/contact/index.php?leftmenu=prospects&type=p', 'Contacts', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (800, 'commercial', '', 5, '/comm/index.php?leftmenu=customers', 'Customers', 0, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (801, 'commercial', '', 800, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (802, 'commercial', '', 800, '/comm/clients.php?leftmenu=customers', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (803, 'commercial', '', 800, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (900, 'commercial', '', 5, '/contact/index.php?leftmenu=contacts', 'Contacts', 0, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (901, 'commercial', '', 900, '/contact/fiche.php?leftmenu=contacts&action=create', 'NewContact', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (902, 'commercial', '', 900, '/contact/index.php?leftmenu=contacts&action=create', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1000, 'commercial', '', 5, '/comm/action/index.php?leftmenu=actions', 'Actions', 0, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1001, 'commercial', '$leftmenu=="actions"', 1000, '/societe.php?leftmenu=actions', 'NewAction', 1, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1002, 'commercial', '$leftmenu=="actions"', 1000, '/comm/action/index.php?leftmenu=actions&status=todo', 'MenuToDoActions', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1003, 'commercial', '$leftmenu=="actions"', 1000, '/comm/action/index.php?leftmenu=actions&time=today', 'Today', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1004, 'commercial', '$leftmenu=="actions"', 1000, '/comm/action/rapport/index.php?leftmenu=actions', 'Reportings', 1, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1100, 'commercial', '', 5, '/comm/propal.php?leftmenu=propals', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1101, 'commercial', '$leftmenu=="propals"', 1100, '/societe.php?leftmenu=propals', 'NewPropal', 1, 'propal', '$user->rights->propale->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1102, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal.php?viewstatut=0', 'PropalsDraft', 1, 'propal', '$user->rights->propale->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1103, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal.php?viewstatut=1', 'PropalsOpened', 1, 'propal', '$user->rights->propale->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1104, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal.php?viewstatut=2,3,4', 'PropalStatusClosedShort', 1, 'propal', '$user->rights->propale->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1105, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal/stats/index.php?leftmenu=propals', 'Statistics', 1, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1200, 'commercial', '', 5, '/commande/index.php?leftmenu=orders', 'Orders', 0, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1201, 'commercial', '$leftmenu=="orders"', 1200, '/societe.php?leftmenu=orders', 'NewOrder', 1, 'orders', '$user->rights->commande->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1202, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=0', 'StatusOrderDraftShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1203, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=1', 'StatusOrderValidated', 1, 'orders', '$user->rights->commande->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1204, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=2', 'StatusOrderOnProcessShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1205, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=3', 'StatusOrderToBill', 1, 'orders', '$user->rights->commande->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1206, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=4', 'StatusOrderProcessed', 1, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1207, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=-1', 'StatusOrderCanceledShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1208, 'commercial', '$leftmenu=="orders"', 1200, '/commande/stats/index.php?leftmenu=orders', 'Statistics', 1, 'orders', '$user->rights->commande->lire', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1300, 'commercial', '', 5, '/expedition/index.php?leftmenu=sendings', 'Sendings', 0, 'orders', '$user->rights->expedition->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1301, 'commercial', '$leftmenu=="sendings"', 1300, '/expedition/liste.php?leftmenu=sendings', 'List', 1, 'orders', '$user->rights->expedition->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1302, 'commercial', '$leftmenu=="sendings"', 1300, '/expedition/stats/index.php?leftmenu=sendings', 'Statistics', 1, 'orders', '$user->rights->expedition->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1400, 'commercial', '', 5, '/contrat/index.php?leftmenu=contracts', 'Contracts', 0, 'contracts', '$user->rights->contrat->lire', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1401, 'commercial', '$leftmenu=="contracts"', 1400, '/societe.php?leftmenu=contracts', 'NewContract', 1, 'contracts', '$user->rights->contrat->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1402, 'commercial', '$leftmenu=="contracts"', 1400, '/contrat/liste.php?leftmenu=contracts', 'List', 1, 'contracts', '$user->rights->contrat->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1403, 'commercial', '$leftmenu=="contracts"', 1400, '/contrat/services.php?leftmenu=contracts', 'MenuServices', 1, 'contracts', '$user->rights->contrat->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1404, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=0', 'MenuInactiveServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1405, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=4', 'MenuRunningServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1406, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=4&filter=expired', 'MenuExpiredServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1407, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=5', 'MenuClosedServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1500, 'commercial', '', 5, '/fichinter/index.php?leftmenu=ficheinter', 'Interventions', 0, 'interventions', '$user->rights->ficheinter->lire', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1501, 'commercial', '$leftmenu=="ficheinter"', 1500, '/fichinter/fiche.php?action=create&leftmenu=ficheinter', 'NewIntervention', 1, 'interventions', '$user->rights->ficheinter->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1502, 'commercial', '$leftmenu=="ficheinter"', 1500, '/fichinter/index.php?leftmenu=ficheinter', 'List', 1, 'interventions', '$user->rights->ficheinter->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1600, 'accountancy', '', 6, '/compta/index.php?leftmenu=suppliers', 'Suppliers', 0, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1601, 'accountancy', '', 1600, '/soc.php?leftmenu=suppliers&action=create&type=f', 'NewSupplier', 1, 'companies', '$user->rights->societe->creer && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1602, 'accountancy', '', 1600, '/fourn/liste.php?leftmenu=suppliers', 'List', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1603, 'accountancy', '', 1600, '/contact/index.php?leftmenu=suppliers&type=f', 'Contacts', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1604, 'accountancy', '', 1600, '/fourn/facture/index.php?leftmenu=suppliers_bills', 'BillsSuppliers', 1, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1605, 'accountancy', '$leftmenu=="suppliers_bills"', 1604, '/fourn/facture/fiche.php?action=create', 'NewBill', 2, 'bills', '$user->rights->fournisseur->facture->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1606, 'accountancy', '$leftmenu=="suppliers_bills"', 1604, '/fourn/facture/impayees.php', 'Unpayed', 2, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1607, 'accountancy', '$leftmenu=="suppliers_bills"', 1604, '/fourn/facture/paiement.php', 'Payments', 2, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1700, 'accountancy', '', 6, '/compta/index.php?leftmenu=customers', 'Customers', 0, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1701, 'accountancy', '', 1700, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 1, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1702, 'accountancy', '', 1700, '/compta/clients.php?leftmenu=customers', 'List', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1703, 'accountancy', '', 1700, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1704, 'accountancy', '', 1700, '/compta/facture.php?leftmenu=customers_bills', 'BillsCustomers', 1, 'bills', '$user->rights->facture->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1705, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/clients.php?action=facturer&leftmenu=customers_bills', 'NewBill', 2, 'bills', '$user->rights->facture->creer', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1706, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/facture/fiche-rec.php?leftmenu=customers_bills', 'Repeatable', 2, 'bills', '$user->rights->facture->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1707, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/facture/impayees.php?action=facturer&leftmenu=customers_bills', 'Unpayed', 2, 'bills', '$user->rights->facture->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1708, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/paiement/liste.php?leftmenu=customers_bills_payments', 'Payments', 2, 'bills', '$user->rights->facture->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1709, 'accountancy', 'eregi("customers_bills_payments",$leftmenu)', 1708, '/compta/paiement/avalider.php?leftmenu=customers_bills_payments', 'MenuToValid', 3, 'bills', '$user->rights->facture->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1710, 'accountancy', 'eregi("customers_bills_payments",$leftmenu)', 1708, '/compta/paiement/rapport.php?leftmenu=customers_bills_payments', 'Reportings', 3, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1711, 'accountancy', '', 6, '/compta/paiement/cheque/index.php?leftmenu=checks', 'MenuChequeDeposits', 0, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1712, 'accountancy', 'eregi("checks",$leftmenu)', 1711, '/compta/paiement/cheque/fiche.php?leftmenu=checks&action=new', 'NewCheckDeposit', 1, 'bills', '$user->rights->facture->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1713, 'accountancy', 'eregi("checks",$leftmenu)', 1711, '/compta/paiement/cheque/liste.php?leftmenu=checks', 'MenuChequesReceipts', 1, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1714, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/facture/stats/index.php?leftmenu=customers_bills', 'Statistics', 2, 'bills', '$user->rights->facture->lire', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1715, 'accountancy', '', 1700, '/compta/paiement/cheque/index.php', 'CheckReceipt', 1, 'bills', '$user->rights->facture->lire', '', 1, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1716, 'accountancy', '', 1704, '/compta/paiement/cheque/fiche.php?action=new', 'New', 2, 'bills', '$user->rights->facture->lire', '', 1, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1717, 'accountancy', '', 1704, '/compta/paiement/cheque/liste.php', 'List', 2, 'bills', '$user->rights->facture->lire', '', 1, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1800, 'accountancy', '', 6, '/compta/propal.php', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1900, 'accountancy', '', 6, '/compta/commande/liste.php?leftmenu=orders&status=3&afacturer=1', 'MenuOrdersToBill', 0, 'orders', '$user->rights->commande->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2000, 'accountancy', '', 6, '/compta/dons/index.php?leftmenu=donations&mainmenu=accountancy', 'Donations', 0, 'donations', '$user->rights->don->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2001, 'accountancy', '$leftmenu=="donations"', 2000, '/compta/dons/fiche.php?action=create', 'NewDonation', 1, 'donations', '$user->rights->don->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2002, 'accountancy', '$leftmenu=="donations"', 2000, '/compta/dons/liste.php?action=create', 'List', 1, 'donations', '$user->rights->don->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2003, 'accountancy', '$leftmenu=="donations"', 2000, '/compta/dons/stats.php', 'Statistics', 1, 'donations', '$user->rights->don->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2100, 'accountancy', '', 6, '/compta/deplacement/index.php', 'Trips', 0, 'trips', '$user->rights->deplacement->lire', '', 0, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2200, 'accountancy', '', 6, '/compta/charges/index.php?leftmenu=charges&mainmenu=accountancy', 'Charges', 0, 'Charges', '$user->rights->tax->charges->lire', '', 0, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2201, 'accountancy', '$leftmenu=="charges"', 2200, '/compta/sociales/index.php', 'SocialContributions', 1, '', '$user->rights->tax->charges->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2300, 'accountancy', '', 6, '/compta/tva/index.php?leftmenu=vat&mainmenu=accountancy', 'VAT', 0, 'companies', '$user->rights->tax->charges->lire', '', 0, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2301, 'accountancy', '$leftmenu=="vat"', 2300, '/compta/tva/fiche.php?action=create', 'NewPayment', 1, 'companies', '$user->rights->tax->charges->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2302, 'accountancy', '$leftmenu=="vat"', 2300, '/compta/tva/reglement.php', 'Payments', 1, 'companies', '$user->rights->tax->charges->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2303, 'accountancy', '$leftmenu=="vat"', 2300, '/compta/tva/clients.php', 'ReportByCustomers', 1, 'companies', '$user->rights->tax->charges->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2400, 'accountancy', '', 6, '/compta/ventilation/index.php?leftmenu=ventil', 'Ventilation', 0, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2401, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/ventilation/liste.php', 'A ventiler', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2402, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/ventilation/lignes.php', 'Ventilées', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2403, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/param/', 'Setup', 1, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2404, 'accountancy', '$leftmenu=="ventil"', 2403, '/compta/param/comptes/liste.php', 'List', 2, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2405, 'accountancy', '$leftmenu=="ventil"', 2403, '/compta/param/comptes/fiche.php?action=create', 'New', 2, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2406, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/export/', 'Export', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2407, 'accountancy', '$leftmenu=="ventil"', 2406, '/compta/export/index.php', 'New', 2, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2408, 'accountancy', '$leftmenu=="ventil"', 2406, '/compta/export/liste.php', 'List', 2, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2500, 'accountancy', '', 6, '/compta/prelevement/index.php?leftmenu=withdraw', 'StandingOrders', 0, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2501, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/demandes.php?status=0', 'StandingOrderToProcess', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2502, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/create.php', 'NewStandingOrder', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2503, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/bons.php', 'WithdrawalsReceipts', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2504, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/liste.php', 'WithdrawalsLines', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2505, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/liste_factures.php', 'WithdrawedBills', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2506, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/rejets.php', 'Rejects', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2507, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/stats.php', 'Statistics', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2508, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/config.php', 'Setup', 1, 'withdrawals', '$user->rights->prelevement->bons->configurer', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2600, 'accountancy', '', 6, '/compta/bank/index.php?leftmenu=bank', 'MenuBankCash', 0, 'banks', '$user->rights->banque->lire', '', 0, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2601, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/fiche.php?action=create', 'MenuNewFinancialAccount', 1, 'banks', '$user->rights->banque->configurer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2602, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/categ.php', 'Categories', 1, 'banks', '$user->rights->banque->configurer', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2603, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/search.php', 'SearchTransaction', 1, 'banks', '$user->rights->banque->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2604, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/budget.php', 'ByCategories', 1, 'banks', '$user->rights->banque->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2605, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/bilan.php', 'Bilan', 1, 'banks', '$user->rights->banque->lire', '', 0, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2606, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/virement.php', 'BankTransfers', 1, 'banks', '$user->rights->banque->modifier', '', 0, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2607, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/bplc.php', 'Transactions BPLC', 1, 'banks', '', '', 0, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2700, 'accountancy', '', 6, '/compta/resultat/index.php?leftmenu=ca&mainmenu=accountancy', 'Reportings', 0, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2701, 'accountancy', '$leftmenu=="ca"', 2700, '/compta/resultat/index.php?leftmenu=ca', 'Résultat / Exercice', 1, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2702, 'accountancy', '$leftmenu=="ca"', 2701, '/compta/resultat/clientfourn.php?leftmenu=ca', 'ByCompanies', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2703, 'accountancy', '$leftmenu=="ca"', 2700, '/compta/stats/index.php?leftmenu=ca', 'Chiffre d''affaire', 1, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2704, 'accountancy', '$leftmenu=="ca"', 2703, '/compta/stats/casoc?leftmenu=ca', 'ByCompanies', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2705, 'accountancy', '$leftmenu=="ca"', 2703, '/compta/stats/cabyuser.php?leftmenu=ca', 'ByUsers', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2800, 'products', '', 3, '/product/index.php?leftmenu=product&type=0', 'Products', 0, 'products', '$user->rights->produit->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2801, 'products', '', 2800, '/product/fiche.php?leftmenu=product&action=create&type=0', 'NewProduct', 1, 'products', '$user->rights->produit->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2802, 'products', '', 2800, '/product/liste.php?leftmenu=product&type=0', 'List', 1, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2803, 'products', '', 2800, '/product/reassort.php?type=0', 'Stocks', 1, 'products', '$user->rights->stock->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2804, 'products', '', 2800, '/product/fiche.php?leftmenu=product&action=create&type=0&canvas=livre', 'Nouveau livre', 1, 'products', '$user->rights->produit->creer', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2805, 'products', '', 2800, '/product/liste.php?leftmenu=product&type=0&canvas=livre', 'Livre', 1, 'products', '$user->rights->produit->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2900, 'products', '', 3, '/product/index.php?leftmenu=service&type=1', 'Services', 0, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2901, 'products', '', 2900, '/product/fiche.php?leftmenu=service&action=create&type=1', 'NewService', 1, 'products', '$user->rights->produit->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2902, 'products', '', 2900, '/product/liste.php?leftmenu=service&type=1', 'List', 1, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3000, 'products', '', 3, '/product/stats/index.php?leftmenu=stats', 'Statistics', 0, 'main', '$user->rights->produit>lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3001, 'products', '', 3000, '/product/popuprop.php?leftmenu=stats', 'Popularity', 1, 'main', '$user->rights->produit>lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3100, 'products', '', 3, '/product/stock/index.php?leftmenu=stock', 'Stock', 0, 'stocks', '$user->rights->stock->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3101, 'products', '$leftmenu=="stock"', 3100, '/product/stock/fiche.php?action=create', 'MenuNewWarehouse', 1, 'stocks', '$user->rights->stock->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3102, 'products', '$leftmenu=="stock"', 3100, '/product/stock/liste.php', 'List', 1, 'stocks', '$user->rights->stock->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3103, 'products', '$leftmenu=="stock"', 3100, '/product/stock/valo.php', 'EnhancedValue', 1, 'stocks', '$user->rights->stock->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3104, 'products', '$leftmenu=="stock"', 3100, '/product/stock/mouvement.php', 'Movements', 1, 'stocks', '$user->rights->stock->mouvement->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3200, 'products', '', 3, '/categories/index.php?leftmenu=cat&type=0', 'Categories', 0, 'categories', '$user->rights->categorie>lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3201, 'products', '$leftmenu=="cat"', 3200, '/categories/fiche.php?action=create&type=0', 'NewCat', 1, 'categories', '$user->rights->categorie>creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3300, 'suppliers', '', 4, '/fourn/index.php?leftmenu=suppliers', 'Suppliers', 0, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3301, 'suppliers', '', 3300, '/soc.php?leftmenu=suppliers&action=create&type=f', 'NewSupplier', 1, 'suppliers', '$user->rights->societe->creer && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3302, 'suppliers', '', 3300, '/fourn/liste.php', 'List', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3303, 'suppliers', '', 3300, '/contact/index.php?leftmenu=supplier&type=f', 'Contacts', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3304, 'suppliers', '', 3300, '/fourn/stats.php', 'Statistics', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3400, 'suppliers', '', 4, '/fourn/facture/index.php', 'Bills', 0, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3401, 'suppliers', '', 3400, '/fourn/facture/fiche.php?action=create', 'NewBill', 1, 'bills', '$user->rights->fournisseur->facture->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3402, 'suppliers', '', 3400, '/fourn/facture/paiement.php', 'Payments', 1, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3500, 'suppliers', '', 4, '/fourn/commande/index.php?leftmenu=suppliers', 'Orders', 0, 'orders', '$user->rights->fournisseur->commande->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3501, 'suppliers', '', 3500, '/societe.php?leftmenu=supplier', 'NewOrder', 1, 'orders', '$user->rights->fournisseur->commande->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3502, 'suppliers', '', 3500, '/fourn/commande/liste.php?leftmenu=suppliers', 'List', 1, 'orders', '$user->rights->fournisseur->commande->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3600, 'project', '', 7, '/projet/index.php?leftmenu=projects', 'Projects', 0, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3601, 'project', '', 3600, '/comm/clients.php?leftmenu=projects', 'NewProject', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3602, 'project', '', 3600, '/projet/liste.php?leftmenu=projects', 'List', 1, 'projects', '$user->rights->projet->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3700, 'project', '', 7, '/projet/tasks', 'Tasks', 0, 'projects', '$user->rights->projet->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3701, 'project', '', 3700, '/projet/tasks/mytasks.php', 'Mytasks', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3800, 'project', '', 7, '/projet/activity', 'Activity', 0, 'projects', '$user->rights->projet->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3801, 'project', '', 3800, '/projet/activity/myactivity.php', 'MyActivity', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3900, 'tools', '', 8, '/comm/mailing/index.php?leftmenu=mailing', 'EMailings', 0, 'mails', '$user->rights->mailing->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3901, 'tools', '', 3900, '/comm/mailing/fiche.php?leftmenu=mailing&action=create', 'NewMailing', 1, 'mails', '$user->rights->mailing->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3902, 'tools', '', 3900, '/comm/mailing/fiche.php?leftmenu=mailing', 'List', 1, 'mails', '$user->rights->mailing->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4000, 'tools', '', 8, '/bookmarks/liste.php?leftmenu=bookmarks', 'Bookmarks', 0, 'other', '$user->rights->bookmark->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4001, 'tools', '', 4000, '/bookmarks/fiche.php?action=create', 'NewBookmark', 1, 'other', '$user->rights->bookmark->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4002, 'tools', '', 4000, '/bookmarks/liste.php', 'List', 1, 'other', '$user->rights->bookmark->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4100, 'tools', '', 8, '/exports/index.php?leftmenu=export', 'FormatedExport', 0, 'exports', '$user->rights->export->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4101, 'tools', '', 4100, '/exports/export.php?leftmenu=export', 'NewExport', 1, 'exports', '$user->rights->export->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4130, 'tools', '', 8, '/admin/import/index.php?leftmenu=import', 'FormatedImport', 0, 'imports', '$user->rights->import->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4131, 'tools', '', 4130, '/admin/import/import.php?leftmenu=import', 'NewImport', 1, 'imports', '$user->rights->import->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4200, 'members', '', 13, '/adherents/index.php?leftmenu=members&mainmenu=members', 'Members', 0, 'members', '$user->rights->adherent->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4201, 'members', '', 4200, '/adherents/fiche.php?action=create', 'NewMember', 1, 'members', '$user->rights->adherent->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4202, 'members', '', 4200, '/adherents/liste.php', 'List', 1, 'members', '$user->rights->adherent->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4203, 'members', '', 4200, '/adherents/liste.php?statut=-1', 'MenuMembersToValidate', 1, 'members', '$user->rights->adherent->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4204, 'members', '', 4200, '/adherents/liste.php?statut=1', 'MenuMembersValidated', 1, 'members', '$user->rights->adherent->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4205, 'members', '', 4200, '/adherents/liste.php?statut=1&filter=outofdate', 'MenuMembersNotUpToDate', 1, 'members', '$user->rights->adherent->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4206, 'members', '', 4200, '/adherents/liste.php?statut=1&filter=uptodate', 'MenuMembersUpToDate', 1, 'members', '$user->rights->adherent->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4207, 'members', '', 4200, '/adherents/liste.php?statut=0', 'MenuMembersResiliated', 1, 'members', '$user->rights->adherent->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4300, 'members', '', 13, '/adherents/index.php?leftmenu=accountancy&mainmenu=members', 'Subscriptions', 0, 'compta', '$user->rights->adherent->cotisation->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4301, 'members', '', 4300, '/adherents/liste.php?statut=-1&leftmenu=accountancy&mainmenu=members', 'NewSubscription', 1, 'compta', '$user->rights->adherent->cotisation->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4302, 'members', '', 4300, '/adherents/cotisations.php?leftmenu=accountancy', 'List', 1, 'compta', '$user->rights->adherent->cotisation->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4400, 'members', '', 13, '/compta/bank/index.php?leftmenu=accountancy', 'Bank', 0, 'banks', '$user->rights->adherent->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4500, 'members', '', 13, '/adherents/index.php?leftmenu=export&mainmenu=members', 'Exports', 0, 'members', '$user->rights->adherent->export', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4501, 'members', '$leftmenu=="export"', 4500, '/exports/index.php?leftmenu=export', 'Datas', 1, 'members', '$user->rights->adherent->export', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4502, 'members', '$leftmenu=="export"', 4500, '/adherents/htpasswd.php?leftmenu=export', 'Filehtpasswd', 1, 'members', '$user->rights->adherent->export', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4503, 'members', '$leftmenu=="export"', 4500, '/adherents/cartes/carte.php?leftmenu=export', 'MembersCards', 1, 'members', '$user->rights->adherent->export', '_new', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4504, 'members', '$leftmenu=="export"', 4500, '/adherents/cartes/etiquette.php?leftmenu=export', 'Etiquettes d''adhérents', 1, 'members', '$user->rights->adherent->export', '_new', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4600, 'members', '', 13, '/public/adherents/index.php?leftmenu=member_public', 'MemberPublicLinks', 0, 'members', '$user->rights->adherent->export', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4700, 'members', '', 13, '/adherents/index.php?leftmenu=setup&mainmenu=members', 'Setup', 0, 'members', '$user->rights->adherent->configurer', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4701, 'members', '', 4700, '/adherents/type.php?leftmenu=setup', 'MembersTypes', 1, 'members', '$user->rights->adherent->configurer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4702, 'members', '', 4700, '/adherents/options.php?leftmenu=setup', 'MembersAttributes', 1, 'members', '$user->rights->adherent->configurer', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4800, 'product', '', 3, '/product/droitpret/index.php?leftmenu=droitpret', 'Droit de prêt', 0, 'products', '$user->rights->droitpret->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4801, 'product', '$leftmenu=="droitpret"', 4800, '/product/droitpret/index.php?leftmenu=droitpret', 'Générer rapport', 1, 'products', '$user->rights->droitpret->creer', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4900, 'suppliers', '', 4, '/categories/index.php?leftmenu=cat&type=1', 'Categories', 0, 'categories', '$user->rights->categorie>lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4901, 'suppliers', '$leftmenu=="cat"', 4900, '/categories/fiche.php?action=create&type=1', 'NewCat', 1, 'categories', '$user->rights->categorie>creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5000, 'commercial', '', 5, '/categories/index.php?leftmenu=cat&type=2', 'Categories', 0, 'commercial', '$user->rights->categorie>lire', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5001, 'commercial', '$leftmenu=="cat"', 5000, '/categories/fiche.php?action=create&type=2', 'NewCat', 1, 'commercial', '$user->rights->categorie>creer', '', 2, 0);
update llx_menu set type='top' where level=-1;

-- 
-- Contenu de la table `llx_menu_constraint`
-- 
insert into `llx_menu_constraint` (`rowid`, `action`) values (1, '$user->admin');
insert into `llx_menu_constraint` (`rowid`, `action`) values (2, '$conf->societe->enabled && $user->rights->societe->lire');
insert into `llx_menu_constraint` (`rowid`, `action`) values (3, '$user->rights->societe->creer');
insert into `llx_menu_constraint` (`rowid`, `action`) values (4, 'is_dir("societe/groupe")');
insert into `llx_menu_constraint` (`rowid`, `action`) values (5, '$conf->societe->enabled && $conf->fournisseur->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (6, '$user->societe_id == 0');
insert into `llx_menu_constraint` (`rowid`, `action`) values (7, '$conf->propal->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (8, '$conf->commande->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (9, '$conf->expedition->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (10, '$conf->contrat->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (11, '$conf->fichinter->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (12, '$conf->societe->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (13, '$conf->facture->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (14, '! $conf->global->FACTURE_DISABLE_RECUR');
insert into `llx_menu_constraint` (`rowid`, `action`) values (15, '$conf->don->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (16, '$conf->deplacement->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (17, '$conf->tax->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (18, '($conf->compta->enabled || $conf->comptaexpert->enabled) && $conf->compta->tva && $user->societe_id == 0');
insert into `llx_menu_constraint` (`rowid`, `action`) values (19, '$conf->compta-enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (20, '$conf->prelevement->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (21, '$conf->banque->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (22, '$conf->compta->enabled || $conf->comptaexpert->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (23, '$conf->produit->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (24, '$conf->stock->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (25, '$conf->service->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (26, '$conf->categorie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (27, '$conf->projet->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (28, '$conf->mailing->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (29, '$conf->bookmark->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (30, '$conf->export->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (31, '$conf->adherent->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (32, '($conf->societe->enabled && $user->rights->societe->lire) || ($conf->fournisseur->enabled && $user->rights->fournisseur->lire)');
insert into `llx_menu_constraint` (`rowid`, `action`) values (33, '$conf->produit->enabled || $conf->service->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (34, '$conf->fournisseur->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (35, '$conf->commercial->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (36, '$conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled\r\n        	|| $conf->commande->enabled || $conf->facture->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (37, '$conf->mailing->enabled || $conf->export->enabled || $conf->bookmark->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (38, '$conf->boutique->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (39, '$conf->oscommerce2->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (40, '$conf->webcal->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (41, '$conf->mantis->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (42, '(dolibarr_get_const($this->db,"PRODUIT_SPECIAL_LIVRE")) && (dolibarr_get_const($this->db,"PRODUCT_CANVAS_ABILITY"))');
insert into `llx_menu_constraint` (`rowid`, `action`) values (43, '!((dolibarr_get_const($this->db,"PRODUIT_SPECIAL_LIVRE")) && (dolibarr_get_const($this->db,"PRODUCT_CANVAS_ABILITY")))');
insert into `llx_menu_constraint` (`rowid`, `action`) values (44, '$conf->droitpret->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (45, '$conf->menudb->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (46, '$conf->energie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (47, '$conf->telephonie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (48, '($user->admin && function_exists("eaccelerator_info"))');
insert into `llx_menu_constraint` (`rowid`, `action`) values (49, '$conf->import->enabled');

-- 
-- Contenu de la table `llx_menu_const`
-- 
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (1, 100, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (2, 200, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (3, 300, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (4, 304, 48, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (5, 501, 3, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (6, 502, 4, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (7, 504, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (8, 503, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (9, 504, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (10, 505, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (11, 500, 2, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (12, 1100, 7, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (13, 1200, 8, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (14, 1300, 9, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (15, 1400, 10, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (16, 1500, 11, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (17, 1600, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (18, 1601, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (19, 1603, 12, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (20, 1605, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (21, 1604, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (22, 1605, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (23, 1606, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (24, 1607, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (25, 1701, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (26, 1700, 12, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (27, 1705, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (28, 1706, 14, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (29, 1704, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (30, 1705, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (31, 1706, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (32, 1708, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (33, 1709, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (34, 1710, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (35, 1711, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (36, 1712, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (37, 1713, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (38, 1714, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (39, 1800, 7, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (40, 1900, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (41, 1900, 8, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (42, 2000, 15, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (43, 2100, 16, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (44, 2200, 17, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (45, 2300, 18, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (46, 2400, 19, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (47, 2500, 20, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (48, 2300, 21, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (49, 2700, 22, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (50, 2800, 23, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (51, 2801, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (52, 2803, 24, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (53, 2900, 25, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (54, 2901, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (55, 3000, 7, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (56, 3100, 24, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (57, 3200, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (58, 3201, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (59, 3300, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (60, 3301, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (61, 3400, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (62, 3401, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (63, 3500, 8, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (64, 3600, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (65, 3700, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (66, 3800, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (67, 3900, 28, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (68, 4000, 29, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (69, 4100, 30, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (70, 4130, 49, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (71, 4200, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (72, 4300, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (73, 4400, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (74, 4500, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (75, 4600, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (76, 4700, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (77, 4400, 21, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (78, 4501, 30, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (79, 2, 32, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (80, 3, 33, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (81, 4, 34, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (82, 5, 35, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (83, 6, 36, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (84, 7, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (85, 8, 37, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (86, 9, 47, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (87, 10, 46, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (88, 11, 38, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (89, 12, 39, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (90, 13, 40, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (91, 14, 41, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (92, 15, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (100, 1715, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (101, 1716, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (102, 1717, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (103, 2804, 42, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (104, 2805, 42, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (105, 2801, 43, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (106, 2802, 43, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (110, 4800, 44, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (111, 4900, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (112, 4901, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (113, 5000, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (114, 5001, 6, 2);

ALTER TABLE llx_product ADD COLUMN volume float DEFAULT NULL after weight_units;
ALTER TABLE llx_product ADD COLUMN volume_units tinyint DEFAULT NULL after volume;

ALTER TABLE llx_product modify ref varchar(32) NOT NULL;

ALTER TABLE `llx_socpeople` CHANGE `fk_user` `fk_user_creat` INT(11) NULL;
ALTER TABLE `llx_socpeople` CHANGE `fk_user_create` `fk_user_creat` INT(11) NULL;
-- V4.1 UPDATE llx_socpeople set fk_user_creat = null where llx_socpeople.fk_user_creat is not null and llx_socpeople.fk_user_creat not in (select rowid from llx_user);
-- V4 ALTER TABLE llx_socpeople ADD INDEX idx_socpeople_fk_user_creat (fk_user_creat);
-- V4 ALTER TABLE llx_socpeople DROP INDEX idx_fk_user_creat;
-- V4 ALTER TABLE llx_socpeople ADD CONSTRAINT fk_socpeople_user_creat_user_rowid FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);

ALTER TABLE llx_user add pass_temp VARCHAR(32) NULL after pass_crypted;
update llx_user set pass = null where pass = pass_crypted and length(pass) = 32;

drop table if exists llx_soc_events;
drop table if exists llx_todocomm;
drop table if exists llx_ventes;
drop table if exists llx_pointmort;
drop table if exists llx_birthday_alert;

ALTER TABLE llx_commande_fournisseurdet ADD total_ht  double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_commande_fournisseurdet ADD total_tva double(24,8) DEFAULT 0 after total_ht;
ALTER TABLE llx_commande_fournisseurdet ADD total_ttc double(24,8) DEFAULT 0 after total_tva;
ALTER TABLE llx_commande_fournisseurdet ADD info_bits integer      DEFAULT 0 after total_ttc;


-- Pas de limite sur nb decimal des prix dans base car definie en option
-- Tous les prix doivent etre au format float(16,8)
-- Tous les tx tva doivent etre au format float(6,3)
ALTER TABLE llx_product_price ADD COLUMN price_ttc double(24,8) DEFAULT 0 AFTER price;
ALTER TABLE llx_product ADD COLUMN price_ttc       double(24,8) DEFAULT 0 AFTER price_base_type;

ALTER TABLE llx_product MODIFY price     double(24,8) DEFAULT 0;
ALTER TABLE llx_product MODIFY price_ttc double(24,8) DEFAULT 0;
ALTER TABLE llx_product MODIFY tva_tx    double(6,3)  DEFAULT 0;

ALTER TABLE llx_product_price MODIFY price     double(24,8) DEFAULT 0;
ALTER TABLE llx_product_price MODIFY price_ttc double(24,8) DEFAULT 0;
ALTER TABLE llx_product_price MODIFY tva_tx    double(6,3)  DEFAULT 0;

ALTER TABLE llx_product_fournisseur_price_log MODIFY price    double(24,8) DEFAULT 0;
ALTER TABLE llx_product_fournisseur_price_log MODIFY quantity double;
ALTER TABLE llx_product_fournisseur_price MODIFY price        double(24,8) DEFAULT 0;
ALTER TABLE llx_product_fournisseur_price MODIFY quantity     double;


ALTER TABLE llx_facture_fourn MODIFY   amount     double(24,8)     DEFAULT 0 NOT NULL;
ALTER TABLE llx_facture_fourn MODIFY   remise     double(24,8)     DEFAULT 0;
ALTER TABLE llx_facture_fourn MODIFY   tva        double(24,8)     DEFAULT 0;
ALTER TABLE llx_facture_fourn MODIFY   total      double(24,8)     DEFAULT 0;
ALTER TABLE llx_facture_fourn MODIFY   total_ht   double(24,8)     DEFAULT 0;
ALTER TABLE llx_facture_fourn MODIFY   total_tva  double(24,8)     DEFAULT 0;
ALTER TABLE llx_facture_fourn MODIFY   total_ttc  double(24,8)     DEFAULT 0;

ALTER TABLE llx_facture_fourn_det MODIFY  pu_ht             double(24,8);
ALTER TABLE llx_facture_fourn_det ADD     pu_ttc            double(24,8) AFTER pu_ht;
ALTER TABLE llx_facture_fourn_det MODIFY  pu_ttc            double(24,8);
ALTER TABLE llx_facture_fourn_det MODIFY  qty               smallint DEFAULT 1;
ALTER TABLE llx_facture_fourn_det MODIFY  total_ht          double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn_det MODIFY  tva_taux          double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn_det MODIFY  tva               double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn_det MODIFY  total_ttc         double(24,8) DEFAULT 0;

ALTER TABLE llx_propal ADD total_ht double(24,8)     DEFAULT 0 after remise;

ALTER TABLE llx_propal MODIFY   tva      double(24,8)     DEFAULT 0;
ALTER TABLE llx_propal MODIFY   total_ht double(24,8)     DEFAULT 0;
ALTER TABLE llx_propal MODIFY   total    double(24,8)     DEFAULT 0;

ALTER TABLE llx_propaldet MODIFY   tva_tx    double(6,3)      DEFAULT 0;
ALTER TABLE llx_propaldet MODIFY   total_ht  double(24,8)     DEFAULT 0;
ALTER TABLE llx_propaldet MODIFY   total_tva double(24,8)     DEFAULT 0;
ALTER TABLE llx_propaldet MODIFY   total_ttc double(24,8)     DEFAULT 0;
ALTER TABLE llx_propaldet MODIFY   subprice  double(24,8)     DEFAULT 0;

ALTER TABLE llx_contratdet MODIFY  tva_tx    double(6,3)  DEFAULT 0;
ALTER TABLE llx_contratdet MODIFY  subprice  double(24,8) DEFAULT 0;
ALTER TABLE llx_contratdet MODIFY  total_ht  double(24,8) DEFAULT 0;
ALTER TABLE llx_contratdet MODIFY  total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_contratdet MODIFY  total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_commande MODIFY  total_ht  double(24,8) DEFAULT 0;
ALTER TABLE llx_commande MODIFY  tva       double(24,8) DEFAULT 0;
ALTER TABLE llx_commande MODIFY  total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_commande_fournisseur MODIFY  total_ht  double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseur MODIFY  tva       double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseur MODIFY  total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_commandedet MODIFY  subprice   double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet MODIFY  total_tva  double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet MODIFY  total_ht   double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet MODIFY  total_ttc  double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet MODIFY  tva_tx     double(6,3)  DEFAULT 0;

ALTER TABLE llx_commande_fournisseurdet MODIFY  subprice   double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet MODIFY  total_tva  double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet MODIFY  total_ht   double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet MODIFY  total_ttc  double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet MODIFY  tva_tx     double(6,3)  DEFAULT 0;

ALTER TABLE llx_societe_remise_except MODIFY  amount_ht     double(24,8) DEFAULT 0;
ALTER TABLE llx_societe_remise_except MODIFY  amount_tva    double(24,8) DEFAULT 0;
ALTER TABLE llx_societe_remise_except MODIFY  amount_ttc    double(24,8) DEFAULT 0;
ALTER TABLE llx_societe_remise_except MODIFY  tva_tx        double(6,3)  DEFAULT 0;


-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_commande_fournisseur FROM llx_commande_fournisseur LEFT JOIN llx_societe ON llx_commande_fournisseur.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL; 


-- Changement de idp en rowid
-- V4 ALTER TABLE llx_propal DROP FOREIGN KEY llx_propal_ibfk1;
-- V4 ALTER TABLE llx_socpeople DROP FOREIGN KEY fk_socpeople_fk_soc;
-- V4 ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_societe;
-- V4 ALTER TABLE llx_commande_fournisseur DROP FOREIGN KEY fk_commande_fournisseur_societe;
-- V4 ALTER TABLE llx_contrat DROP FOREIGN KEY fk_contrat_societe;
-- V4 ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_soc;
-- V4 ALTER TABLE llx_facture_fourn DROP FOREIGN KEY fk_facture_fourn_fk_soc;
-- V4 ALTER TABLE llx_fichinter DROP FOREIGN KEY fk_fichinter_fk_soc;
-- V4 ALTER TABLE llx_osc_customer DROP FOREIGN KEY llx_osc_customer_fk_soc;
-- V4 ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_soc;
-- V4 ALTER TABLE llx_societe_remise_except DROP FOREIGN KEY fk_societe_remise_fk_soc;
-- V4 ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_soc;
-- V4 ALTER TABLE llx_categorie_societe DROP FOREIGN KEY fk_categorie_societe_societe_rowid;

-- V4 ALTER TABLE `llx_osc_customer` DROP FOREIGN KEY llx_osc_customer_societe_idp;
-- V4 ALTER TABLE `llx_osc_customer` DROP FOREIGN KEY fk_osc_customer_fk_soc;

-- V4 ALTER TABLE llx_telephonie_adsl_fournisseur DROP FOREIGN KEY fk_soc;
-- V4 ALTER TABLE llx_telephonie_client_stats DROP FOREIGN KEY fk_client_comm;
-- V4 ALTER TABLE llx_telephonie_contact_facture DROP FOREIGN KEY fk_contact;
-- V4 ALTER TABLE llx_telephonie_societe_ligne DROP FOREIGN KEY fk_client_comm;
-- V4 ALTER TABLE llx_telephonie_societe_ligne DROP FOREIGN KEY fk_soc;
-- V4 ALTER TABLE llx_telephonie_societe_ligne DROP FOREIGN KEY fk_soc_facture;
-- V4 ALTER TABLE llx_telephonie_tarif_client DROP FOREIGN KEY fk_client;
-- V4 ALTER TABLE llx_telephonie_adsl_fournisseur DROP INDEX fk_soc_2;
-- V4 ALTER TABLE llx_telephonie_commande_ligne DROP INDEX fk_ligne_2;
-- V4 ALTER TABLE llx_telephonie_commande_ligne DROP INDEX fk_commande_2;
-- V4 ALTER TABLE llx_telephonie_commande DROP INDEX fk_user_creat_2;
-- V4 ALTER TABLE llx_telephonie_commande DROP INDEX fk_fournisseur_2;
-- V4 ALTER TABLE llx_telephonie_contact_facture DROP INDEX fk_contact_2;
-- V4 ALTER TABLE llx_telephonie_contact_facture DROP INDEX fk_contact_3;
-- V4 ALTER TABLE llx_telephonie_contact_facture DROP INDEX fk_ligne_2;
-- V4 ALTER TABLE llx_telephonie_contact_facture DROP INDEX fk_ligne_3;

ALTER TABLE `llx_societe` CHANGE `idp` `rowid` integer AUTO_INCREMENT;
ALTER TABLE `llx_socpeople` CHANGE `idp` `rowid` integer AUTO_INCREMENT;

ALTER TABLE `llx_osc_customer` CHANGE `osc_custid` `rowid` integer NOT NULL default 0;
ALTER TABLE `llx_osc_customer` CHANGE `osc_lastmodif` `datem` datetime default NULL;
ALTER TABLE `llx_osc_customer` CHANGE `doli_socidp` `fk_soc` integer NOT NULL default '0';
ALTER TABLE `llx_osc_customer` ADD PRIMARY KEY (rowid);
ALTER TABLE `llx_osc_customer` ADD UNIQUE KEY `fk_soc` (`fk_soc`);
ALTER TABLE `llx_osc_order` CHANGE `osc_orderid` `rowid` integer NOT NULL default 0;
ALTER TABLE `llx_osc_order` CHANGE `osc_lastmodif` `datem` datetime default NULL;
ALTER TABLE `llx_osc_order` CHANGE `doli_orderidp` `fk_commande` integer NOT NULL default 0;
ALTER TABLE `llx_osc_order` ADD PRIMARY KEY (rowid);
ALTER TABLE `llx_osc_order` ADD UNIQUE KEY `fk_commande` (`fk_commande`);
ALTER TABLE `llx_osc_product` CHANGE `osc_prodid` `rowid` integer NOT NULL default 0;
ALTER TABLE `llx_osc_product` CHANGE `osc_lastmodif` `datem` datetime default NULL;
ALTER TABLE `llx_osc_product` CHANGE `doli_prodidp` `fk_product` integer NOT NULL default 0;
ALTER TABLE `llx_osc_product` ADD PRIMARY KEY (rowid);
ALTER TABLE `llx_osc_product` ADD UNIQUE KEY `fk_product` (`fk_product`);

-- V4 ALTER TABLE llx_socpeople ADD CONSTRAINT fk_socpeople_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_commande_fournisseur ADD CONSTRAINT fk_commande_fournisseur_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_contrat ADD CONSTRAINT fk_contrat_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_facture_fourn ADD CONSTRAINT fk_facture_fourn_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_fichinter ADD CONSTRAINT fk_fichinter_fk_soc	FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_categorie_societe ADD CONSTRAINT fk_categorie_societe_fk_soc   FOREIGN KEY (fk_societe) REFERENCES llx_societe (rowid);

-- V4 ALTER TABLE llx_osc_customer ADD CONSTRAINT fk_osc_customer_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);

-- V4 ALTER TABLE llx_telephonie_adsl_fournisseur ADD CONSTRAINT fk_telephonie_adsl_fournisseur_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
-- V4 ALTER TABLE llx_telephonie_client_stats ADD CONSTRAINT fk_telephonie_client_stats_societe FOREIGN KEY (fk_client_comm) REFERENCES llx_societe(rowid);
-- V4 ALTER TABLE llx_telephonie_contact_facture ADD CONSTRAINT fk_telephonie_contact_facture_contact FOREIGN KEY (fk_contact) REFERENCES llx_socpeople (rowid);
-- V4 ALTER TABLE llx_telephonie_contact_facture ADD CONSTRAINT fk_telephonie_contact_facture_ligne FOREIGN KEY (fk_ligne) REFERENCES llx_telephonie_societe_ligne (rowid);
-- V4 ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_client_comm FOREIGN KEY (fk_client_comm) REFERENCES llx_societe(rowid);
-- V4 ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_soc         FOREIGN KEY (fk_soc)         REFERENCES llx_societe(rowid);
-- V4 ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_soc_facture FOREIGN KEY (fk_soc_facture) REFERENCES llx_societe(rowid);
-- V4 ALTER TABLE llx_telephonie_tarif_client ADD CONSTRAINT llx_telephonie_tarif_client_client FOREIGN KEY (fk_client) REFERENCES llx_societe (rowid);
-- fin du changement idp en rowid

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (120, 'fichinter','internal', 'INTERREPFOLL',  'Responsable suivi de l\'intervention', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (121, 'fichinter','internal', 'INTERVENING',   'Intervenant', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (130, 'fichinter','external', 'BILLING',       'Contact client facturation intervention', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (131, 'fichinter','external', 'CUSTOMER',      'Contact client suivi de l\'intervention', 1);

ALTER TABLE llx_fichinter CHANGE note description text DEFAULT NULL;
ALTER TABLE llx_fichinter ADD COLUMN note_private text DEFAULT NULL after description;
ALTER TABLE llx_fichinter ADD COLUMN note_public text DEFAULT NULL after note_private;
ALTER TABLE llx_fichinter ADD COLUMN tms timestamp after ref;
ALTER TABLE llx_fichinter ADD COLUMN fk_contrat integer DEFAULT 0 after fk_projet;

drop table if exists `llx_accountingsystem_det`;


update llx_bank set label='(InitialBankBalance)' where fk_type='SOLD' and label in ('Balance','(Balance)','Solde','(Solde)');

alter table llx_product_fournisseur_price add unitprice double(24,8);
alter table llx_product_fournisseur_price MODIFY unitprice double(24,8);
update llx_product_fournisseur_price set unitprice = ROUND(price/quantity,8) where unitprice IS NULL;

update llx_fichinter set tms=datec where tms < datec;
update llx_fichinter set tms=date_valid where tms < date_valid;

ALTER TABLE llx_commande_fournisseur DROP INDEX ref;
ALTER TABLE llx_commande_fournisseur ADD UNIQUE INDEX uk_commande_fournisseur_ref (ref, fk_soc);

create table llx_c_ecotaxe
(
  rowid        integer      AUTO_INCREMENT PRIMARY KEY,
  code         varchar(64)  UNIQUE NOT NULL,
  libelle      varchar(255),
  price        double(24,8),
  organization varchar(255),
  fk_pays      integer NOT NULL,
  active       tinyint DEFAULT 1  NOT NULL
)type=innodb;

INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (1, 'ER-A-A', 'Matériels électriques < 0,2kg', 0.01000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (2, 'ER-A-B', 'Matériels électriques >= 0,2 kg et < 0,5 kg', 0.03000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (3, 'ER-A-C', 'Matériels électriques >= 0,5 kg et < 1 kg', 0.04000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (4, 'ER-A-D', 'Matériels électriques >= 1 kg et < 2 kg', 0.13000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (5, 'ER-A-E', 'Matériels électriques >= 2 kg et < 4kg', 0.21000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (6, 'ER-A-F', 'Matériels électriques >= 4 kg et < 8 kg', 0.42000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (7, 'ER-A-G', 'Matériels électriques >= 8 kg et < 15 kg', 0.84000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (8, 'ER-A-H', 'Matériels électriques >= 15 kg et < 20 kg', 1.25000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (9, 'ER-A-I', 'Matériels électriques >= 20 kg et < 30 kg', 1.88000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (10, 'ER-A-J', 'Matériels électriques >= 30 kg', 3.34000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (11, 'ER-M-1', 'TV, Moniteurs < 9kg', 0.84000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (12, 'ER-M-2', 'TV, Moniteurs >= 9kg et < 15kg', 1.67000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (13, 'ER-M-3', 'TV, Moniteurs >= 15kg et < 30kg', 3.34000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (14, 'ER-M-4', 'TV, Moniteurs >= 30 kg', 6.69000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (15, 'EC-A-A', 'Matériels électriques  0,2 kg max', 0.00840000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (16, 'EC-A-B', 'Matériels électriques 0,21 kg min - 0,50 kg max', 0.02500000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (17, 'EC-A-C', 'Matériels électriques  0,51 kg min - 1 kg max', 0.04000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (18, 'EC-A-D', 'Matériels électriques  1,01 kg min - 2,5 kg max', 0.13000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (19, 'EC-A-E', 'Matériels électriques  2,51 kg min - 4 kg max', 0.21000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (20, 'EC-A-F', 'Matériels électriques 4,01 kg min - 8 kg max', 0.42000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (21, 'EC-A-G', 'Matériels électriques  8,01 kg min - 12 kg max', 0.63000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (22, 'EC-A-H', 'Matériels électriques 12,01 kg min - 20 kg max', 1.05000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (23, 'EC-A-I', 'Matériels électriques  20,01 kg min', 1.88000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (24, 'EC-M-1', 'TV, Moniteurs 9 kg max', 0.84000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (25, 'EC-M-2', 'TV, Moniteurs 9,01 kg min - 18 kg max', 1.67000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (26, 'EC-M-3', 'TV, Moniteurs 18,01 kg min - 36 kg max', 3.34000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (27, 'EC-M-4', 'TV, Moniteurs 36,01 kg min', 6.69000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (28, 'ES-M-1', 'TV, Moniteurs <= 20 pouces', 0.84000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (29, 'ES-M-2', 'TV, Moniteurs > 20 pouces et <= 32 pouces', 3.34000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (30, 'ES-M-3', 'TV, Moniteurs > 32 pouces et autres grands écrans', 6.69000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (31, 'ES-A-A', 'Ordinateur fixe, Audio home systems (HIFI), éléments hifi séparés...', 0.84000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (32, 'ES-A-B', 'Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD...  Instruments de musique et caisses de résonance, haut parleurs...', 0.25000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (33, 'ES-A-C', 'Imprimante, photocopieur, télécopieur,...', 0.42000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (34, 'ES-A-D', 'Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, téléphone, répondeur, téléphone sans fil, modem,...   Télécommande, casque, caméscope, baladeur mp3, radio portable, radio K7 et CD portable, set top box, radio réveil,...', 0.08400000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (35, 'ES-A-E', 'GSM', 0.00840000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (36, 'ES-A-F', 'Jouets et équipements de loisirs et de sports < 0,5 kg', 0.04200000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (37, 'ES-A-G', 'Jouets et équipements de loisirs et de sports > 0,5 kg', 0.17000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (38, 'ES-A-H', 'Jouets et équipements de loisirs et de sports > 10 kg', 1.25000000, 'Eco-systèmes', 1, 1);

ALTER TABLE llx_commandedet CHANGE coef marge_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_commandedet ADD COLUMN marge_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_commandedet ADD COLUMN marque_tx double(6,3) DEFAULT 0 after marge_tx;
ALTER TABLE llx_commandedet MODIFY marge_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_commandedet MODIFY marque_tx double(6,3) DEFAULT 0;

ALTER TABLE llx_propaldet CHANGE coef marge_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_propaldet ADD COLUMN marge_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_propaldet ADD COLUMN marque_tx double(6,3) DEFAULT 0 after marge_tx;
ALTER TABLE llx_propaldet MODIFY marge_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_propaldet MODIFY marque_tx double(6,3) DEFAULT 0;

-- Nouveau mode de stockage de l'ordre des box (X99 ou X = colonne et 99 position dans colonne)
alter table llx_boxes modify box_order varchar(3) NOT NULL;
-- V4.1 update llx_boxes set box_order = concat('A0',box_order) where length(box_order) = 1 and substring(box_order,-1) in ('1','3','5','7','9');
-- V4.1 update llx_boxes set box_order = concat('B0',box_order) where length(box_order) = 1 and substring(box_order,-1) in ('0','2','4','6','8');
-- V4.1 update llx_boxes set box_order = concat('A',box_order) where length(box_order) = 2 and substring(box_order,-1) in ('1','3','5','7','9');
-- V4.1 update llx_boxes set box_order = concat('B',box_order) where length(box_order) = 2 and substring(box_order,-1) in ('0','2','4','6','8');

create table llx_fichinterdet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_fichinter      integer,
  date              date,
  description       text,
  duree             integer,
  rang              integer DEFAULT 0
)type=innodb;

ALTER TABLE llx_fichinter ADD COLUMN model_pdf varchar(50) after note_public;

ALTER TABLE llx_bordereau_cheque MODIFY number varchar(16) NOT NULL;
ALTER TABLE llx_bordereau_cheque MODIFY amount double(24,8) NOT NULL;
ALTER TABLE llx_bordereau_cheque MODIFY nbcheque          smallint NOT NULL;
ALTER TABLE llx_bordereau_cheque MODIFY statut            smallint(1) NOT NULL DEFAULT 0;

ALTER TABLE llx_facturedet ADD COLUMN special_code tinyint(4) unsigned default 0;
ALTER TABLE llx_facturedet MODIFY special_code tinyint(4) unsigned default 0;

ALTER TABLE llx_commandedet MODIFY special_code tinyint(4) unsigned default 0;

ALTER TABLE llx_propaldet ADD COLUMN special_code tinyint(4) unsigned default 0 after marque_tx;
ALTER TABLE llx_propaldet ADD COLUMN pa_ht double(24,8) DEFAULT 0 after info_bits;
ALTER TABLE llx_propaldet MODIFY pa_ht double(24,8) DEFAULT 0;

ALTER TABLE llx_bank MODIFY amount double(24,8) DEFAULT 0;


-- Nouveau fonctionnement de la table llx_product_fournisseur_price
-- V4 ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_user;
-- V4 ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_soc;
-- V4 ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_product;
ALTER TABLE llx_product_fournisseur_price DROP INDEX idx_product_fournisseur_price_fk_user;
ALTER TABLE llx_product_fournisseur_price DROP INDEX idx_product_fournisseur_price_fk_soc;
ALTER TABLE llx_product_fournisseur_price DROP INDEX idx_product_fournisseur_price_fk_product;
ALTER TABLE llx_product_fournisseur_price DROP COLUMN ref_fourn;
-- V4.1 UPDATE llx_product_fournisseur_price as pfp SET pfp.fk_product = (SELECT pf.rowid FROM llx_product_fournisseur AS pf WHERE pfp.fk_product = pf.fk_product AND pfp.fk_soc = pf.fk_soc);
ALTER TABLE llx_product_fournisseur_price DROP COLUMN fk_soc;
ALTER TABLE llx_product_fournisseur_price CHANGE fk_product fk_product_fournisseur integer NOT NULL;
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fournisseur_price_fk_user (fk_user);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fournisseur_price_fk_product_fournisseur (fk_product_fournisseur);
-- V4 ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_user    FOREIGN KEY (fk_user)    REFERENCES llx_user (rowid);
-- V4 ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_product_fournisseur FOREIGN KEY (fk_product_fournisseur) REFERENCES llx_product_fournisseur (rowid);

-- Nouveau fonctionnement de la table llx_product_fournisseur_price_log
-- V4.1 UPDATE llx_product_fournisseur_price_log as pfpl SET pfpl.fk_product = (SELECT pf.rowid FROM llx_product_fournisseur AS pf WHERE pfpl.fk_product = pf.fk_product AND pfpl.fk_soc = pf.fk_soc);
ALTER TABLE llx_product_fournisseur_price_log DROP COLUMN fk_soc;
ALTER TABLE llx_product_fournisseur_price_log CHANGE fk_product fk_product_fournisseur integer NOT NULL;

ALTER TABLE llx_commande_fournisseurdet MODIFY fk_commande integer NOT NULL;

ALTER TABLE llx_product ADD COLUMN partnumber varchar(32) after gencode;

ALTER TABLE llx_element_contact ADD INDEX idx_element_contact_fk_socpeople (fk_socpeople);

-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_fichinter FROM llx_fichinter LEFT JOIN llx_societe ON llx_fichinter.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL;



ALTER TABLE llx_societe ADD COLUMN supplier_account varchar(32) after fournisseur;

drop table if exists llx_c_barcode;

create table llx_c_barcode_type
(
  rowid    integer            AUTO_INCREMENT PRIMARY KEY,
  code     varchar(16)        NOT NULL,
  libelle  varchar(50)        NOT NULL,
  coder    integer            NOT NULL DEFAULT 0,
  example  varchar(16)        NOT NULL
)type=innodb;

INSERT INTO llx_c_barcode_type (rowid, code, libelle, coder, example) VALUES (1, 'EAN8', 'EAN8', 0, '1234567');
INSERT INTO llx_c_barcode_type (rowid, code, libelle, coder, example) VALUES (2, 'EAN13', 'EAN13', 0, '123456789012');
INSERT INTO llx_c_barcode_type (rowid, code, libelle, coder, example) VALUES (3, 'UPC', 'UPC', 0, '123456789012');
INSERT INTO llx_c_barcode_type (rowid, code, libelle, coder, example) VALUES (4, 'ISBN', 'ISBN', 0, '123456789');
INSERT INTO llx_c_barcode_type (rowid, code, libelle, coder, example) VALUES (5, 'C39', 'Code 39', 0, '1234567890');
INSERT INTO llx_c_barcode_type (rowid, code, libelle, coder, example) VALUES (6, 'C128', 'Code 128', 0, 'ABCD1234567890');

ALTER TABLE llx_product CHANGE gencode barcode varchar(255) DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN fk_barcode_type integer DEFAULT 0 after barcode;

INSERT INTO llx_const (name, value, type, note, visible) VALUES ('GENBARCODE_LOCATION','/usr/local/bin/genbarcode','chaine','location of genbarcode',0);

create table llx_c_paper_format
(
  rowid    integer                          AUTO_INCREMENT PRIMARY KEY,
  code     varchar(16)                      NOT NULL,
  label    varchar(50)                      NOT NULL,
  width    float(6,2)                       DEFAULT 0,
  height   float(6,2)                       DEFAULT 0,
  unit     enum('mm','cm','point','inch')   NOT NULL,
  active   tinyint DEFAULT 1                NOT NULL
)type=innodb;

INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (1, '4A0', 'Format 4A0', '1682', '2378', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (2, '2A0', 'Format 2A0', '1189', '1682', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (3, 'A0', 'Format A0', '840', '1189', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (4, 'A1', 'Format A1', '594', '840', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (5, 'A2', 'Format A2', '420', '594', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (6, 'A3', 'Format A3', '297', '420', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (7, 'A4', 'Format A4', '210', '297', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (8, 'A5', 'Format A5', '148', '210', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (9, 'A6', 'Format A6', '105', '148', 'mm', 1);

ALTER TABLE llx_user ADD COLUMN phenix_login varchar(25) after webcal_login;
ALTER TABLE llx_user ADD COLUMN phenix_pass varchar(128) after phenix_login;

update llx_propal set total_ht = price where total_ht = 0 and total > 0;
update llx_propal set date_livraison = NULL where date_livraison = '1970-01-01 00:00:00';

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (102,'commande','external', 'SHIPPING',      'Contact client livraison commande', 1);

-- Uniformisation du nom. Rem: Cette table n'est pas utilise en lecture a ce jour
drop table llx_socstatutlog;
create table llx_societe_log
(
  id          integer AUTO_INCREMENT PRIMARY KEY,
  datel       datetime,
  fk_soc      integer,
  fk_statut   integer,
  fk_user     integer,
  author      varchar(30),
  label       varchar(128)
)type=innodb;


-- Pour la Tunisie (Formes les plus utilisées)
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1000','Société à responsabilité limitée SARL');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1001','Société en Nom Collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1002','Société en Commandite Simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1003','société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1004','Société Anonyme SA');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1005','Société Unipersonnelle à Responsabilité Limitée SUARL');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1006','Groupement d\'intérêt économique GEI');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1007','Groupe de sociétés');

-- Regions de Tunisie (id pays=10)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1001,10,1001, '',0,'Ariana');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1002,10,1002, '',0,'Béja');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1003,10,1003, '',0,'Ben Arous');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1004,10,1004, '',0,'Bizerte');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1005,10,1005, '',0,'Gabès');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1006,10,1006, '',0,'Gafsa');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1007,10,1007, '',0,'Jendouba');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1008,10,1008, '',0,'Kairouan');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1009,10,1009, '',0,'Kasserine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1010,10,1010, '',0,'Kébili');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1011,10,1011, '',0,'La Manouba');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1012,10,1012, '',0,'Le Kef');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1013,10,1013, '',0,'Mahdia');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1014,10,1014, '',0,'Médenine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1015,10,1015, '',0,'Monastir');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1016,10,1016, '',0,'Nabeul');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1017,10,1017, '',0,'Sfax');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1018,10,1018, '',0,'Sidi Bouzid');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1019,10,1019, '',0,'Siliana');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1020,10,1020, '',0,'Sousse');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1021,10,1021, '',0,'Tataouine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1022,10,1022, '',0,'Tozeur');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1023,10,1023, '',0,'Tunis');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1024,10,1024, '',0,'Zaghouan');

-- TUNISIE (id 10)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (101,10, '6','0','TVA 6%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (102,10, '12','0','TVA 12%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (103,10, '18','0','VAT 18%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (104,10, '7.5','0','TVA 6% Majoré à 25% (7.5%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (105,10, '15','0','TVA 12% Majoré à 25% (15%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (106,10, '22.5','0','VAT 18% Majoré à 25% (22.5%)',1);

-- GUADELOUPE (id 105)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 111, 105, '8.5','0','VAT Rate 8.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 112, 105, '8.5','1','VAT Rate 8.5 non perçu par le vendeur mais récupérable par l\'acheteur',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 113, 105,   '0','0','VAT Rate 0 ou non applicable',1);

-- MARTINIQUE (id 150)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 121, 150, '8.5','0','VAT Rate 8.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 122, 150, '8.5','1','VAT Rate 8.5 non perçu par le vendeur mais récupérable par l\'acheteur',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 123, 150,   '0','0','VAT Rate 0 ou non applicable',1);

-- REUNION (id 187)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 131, 187, '8.5','0','VAT Rate 8.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 132, 187, '8.5','1','VAT Rate 8.5 non perçu par le vendeur mais récupérable par l\'acheteur',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 133, 187,   '0','0','VAT Rate 0 ou non applicable',1);

ALTER TABLE llx_bank_account MODIFY iban_prefix varchar(50);
ALTER TABLE llx_bank_account ADD COLUMN country_iban varchar(2) after iban_prefix;
ALTER TABLE llx_bank_account ADD COLUMN cle_iban varchar(2) after country_iban;

delete from llx_const where name='PRODUIT_CHANGE_PROD_DESC';

-- Mise à jour des pays
update llx_c_pays set libelle = 'Palaos' where rowid = 176 and code = 'PW' and libelle = 'Belau';
update llx_c_pays set libelle = 'Serbie' where rowid = 198 and code = 'CS';
update llx_c_pays set code = 'RS' where rowid = 198 and code = 'CS';
insert into llx_c_pays (rowid,code,libelle) values (241, 'GG', 'Guernesey'     );
insert into llx_c_pays (rowid,code,libelle) values (242, 'IM', 'Ile de Man'    );
insert into llx_c_pays (rowid,code,libelle) values (243, 'JE', 'Jersey'        );
insert into llx_c_pays (rowid,code,libelle) values (244, 'ME', 'Monténégro'    );
insert into llx_c_pays (rowid,code,libelle) values (245, 'BL', 'Saint-Barthélemy');
insert into llx_c_pays (rowid,code,libelle) values (246, 'MF', 'Saint-Martin'  );


ALTER TABLE llx_boxes ADD UNIQUE INDEX uk_boxes (box_id, position, fk_user);

-- Nettoyage vieux enregistrement detail pourris
delete from llx_facturedet where price = 0 and subprice = 0 and remise_percent = 0 and total_ttc = 0 and total_ht = 0;


-- Drop constraints to allow rename
ALTER TABLE llx_societe_remise_except drop foreign key fk_societe_remise_fk_facture;
ALTER TABLE llx_societe_remise_except drop index idx_societe_remise_except_fk_facture;

-- Rename field
ALTER TABLE llx_societe_remise_except change fk_facture fk_facture_line integer;
ALTER TABLE llx_societe_remise_except add fk_facture integer after fk_facture_line;

-- Create constraints
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_facture_line (fk_facture_line);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_facture (fk_facture);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_facture_line   FOREIGN KEY (fk_facture_line) REFERENCES llx_facturedet (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_facture        FOREIGN KEY (fk_facture)        REFERENCES llx_facture (rowid);

-- Corrige statut des avoirs qui ont ete transforme en reduc et pour lesquels la reduc a ete ensuite supprimee
-- V4.1 update llx_facture set paye=0, fk_statut=1 where paye=1 and type=2 and rowid not in (select fk_facture_source from llx_societe_remise_except);

-- Corrige avoirs affectes en ligne a affectation sur facture. On met total a null pour permettre recalcul par upgrade2
-- V4.1 update llx_facture set total_ttc = NULL where rowid in (select fk_facture from llx_facturedet where description = '(CREDIT_NOTE)');
-- V4.1 update llx_societe_remise_except as re set re.fk_facture = (select fk_facture from llx_facturedet as fd where fd.rowid = re.fk_facture_line), re.fk_facture_line = NULL where re.fk_facture_line in (select rowid from llx_facturedet where description = '(CREDIT_NOTE)');
-- V4.1 delete from llx_facturedet where description = '(CREDIT_NOTE)';
