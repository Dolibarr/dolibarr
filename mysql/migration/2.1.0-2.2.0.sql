--
-- $Id$
-- $Source$
-- $Revision$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.1.0 
-- sans AUCUNE erreur ni warning
--

ALTER TABLE llx_cotisation ADD UNIQUE INDEX uk_cotisation (fk_adherent,dateadh);

update llx_const set name='MAIN_ENABLE_DEVELOPMENT' where name='MAIN_SHOW_DEVELOPMENT_MODULES';


delete from llx_adherent_type where libelle IS NULL;
alter table llx_adherent_type modify libelle          varchar(50) NOT NULL;


-- Extention de la gestion des catégories
alter table llx_categorie ADD type int not null default '0';

drop table if exists `llx_categorie_societe`;
create table `llx_categorie_societe` (
  `fk_categorie` int(11) not null,
  `fk_societe` int(11) not null,
  UNIQUE KEY `fk_categorie` (`fk_categorie`,`fk_societe`),
  KEY `fk_societe` (`fk_societe`)
) type=innodb;

-- 
alter table `llx_categorie_societe`
  add constraint `llx_categorie_societe_ibfk_1` foreign key(`fk_societe`) REFERENCES `llx_societe` (`idp`) ON DELETE CASCADE ON UPDATE CASCADE,
  add constraint `llx_categorie_societe_ibfk_2` foreign key(`fk_categorie`) REFERENCES `llx_categorie` (`rowid`) ON DELETE CASCADE ON UPDATE CASCADE;

drop table if exists `llx_categorie_product`;
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
drop table if exists `llx_menu_const`;
drop table if exists `llx_menu`;
drop table if exists `llx_menu_constraint`;

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
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1, 'home', '', 0, '/index.php?mainmenu=home&leftmenu=', 'Home', -1, '', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2, 'companies', '', 0, '/index.php?mainmenu=companies&amp;leftmenu=', 'ThirdParties', -1, 'companies', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3, 'products', '', 0, '/product/index.php?mainmenu=products&amp;leftmenu=', 'Products/Services', -1, 'products', '$user->rights->produit->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4, 'suppliers', '', 0, '/fourn/index.php?mainmenu=suppliers&amp;leftmenu=', 'Suppliers', -1, 'suppliers', '$user->rights->fournisseur->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5, 'commercial', '', 0, '/comm/index.php?mainmenu=commercial&amp;leftmenu=', 'Commercial', -1, 'commercial', '$user->rights->commercial->main->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (6, 'accountancy', '', 0, '/compta/index.php?mainmenu=accountancy&amp;leftmenu=', 'MenuFinancial', -1, 'compta', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->plancompte->lire || $user->rights->commande->lire || $user->rights->facture->lire || $user->rights->banque->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (7, 'project', '', 0, '/projet/index.php?mainmenu=project&amp;leftmenu=', 'Projects', -1, 'projects', '$user->rights->projet->lire', '', 0, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (8, 'tools', '', 0, '/index.php?mainmenu=tools&amp;leftmenu=', 'Tools', -1, 'other', '$user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (9, 'shop', '', 0, '/boutique/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (10, 'shop', '', 0, '/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (11, 'webcal', '', 0, '/webcal/webcal.php?mainmenu=webcal&amp;leftmenu=', 'Calendar', -1, 'other', '', '', 0, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (12, 'mantis', '', 0, '/mantis/mantis.php?mainmenu=mantis', 'BugTracker', -1, 'other', '', '', 2, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (13, 'members', '', 0, '/adherents/index.php?mainmenu=members&amp;leftmenu=', 'Members', -1, 'members', '', '', 2, 12);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (100, 'home', '', 1, '/admin/index.php?leftmenu=setup', 'Setup', 0, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (101, 'home', '$leftmenu=="setup"', 100, '/admin/company.php', 'MenuCompanySetup', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (102, 'home', '$leftmenu=="setup"', 100, '/admin/ihm.php', 'GUISetup', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (103, 'home', '$leftmenu=="setup"', 100, '/admin/modules.php', 'Modules', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (104, 'home', '$leftmenu=="setup"', 100, '/admin/boxes.php', 'Boxes', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (105, 'home', '$leftmenu=="setup"', 100, '/admin/delais.php', 'DelaysBeforeWarning', 1, 'admin', '', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (106, 'home', '$leftmenu=="setup"', 100, '/admin/triggers.php', 'Triggers', 1, 'admin', '', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (107, 'home', '$leftmenu=="setup"', 100, '/admin/perms.php', 'Security', 1, 'admin', '', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (108, 'home', '$leftmenu=="setup"', 100, '/admin/dict.php', 'DictionnarySetup', 1, 'admin', '', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (109, 'home', '$leftmenu=="setup"', 100, '/admin/const.php', 'OtherSetup', 1, 'admin', '', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (111, 'home', '$leftmenu=="setup"', 100, '/admin/menus.php', 'Menus', 1, 'admin', '', '', 2, 4);
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
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1105, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal/stats.php', 'Statistics', 1, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1200, 'commercial', '', 5, '/commande/index.php?leftmenu=orders', 'Orders', 0, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1201, 'commercial', '', 1200, '/societe.php?leftmenu=orders', 'NewOrder', 1, 'orders', '$user->rights->commande->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1202, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders', 'List', 1, 'orders', '$user->rights->commande->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1203, 'commercial', '', 1200, '/commande/stats/index.php?leftmenu=orders', 'Statistics', 1, 'orders', '$user->rights->commande->lire', '', 2, 2);
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
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1501, 'commercial', '$leftmenu=="ficheinter"', 1500, '/societe.php?leftmenu=ficheinter', 'NewIntervention', 1, 'interventions', '$user->rights->ficheinter->creer', '', 2, 0);
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
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1800, 'accountancy', '', 6, '/compta/propal', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 2);
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

-- 
-- Contenu de la table `llx_menu_const`
-- 
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (1, 100, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (2, 200, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (3, 300, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (4, 501, 3, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (5, 502, 4, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (6, 504, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (7, 503, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (8, 504, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (9, 505, 5, 2);
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
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (70, 4200, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (71, 4300, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (72, 4400, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (73, 4500, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (74, 4600, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (75, 4700, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (76, 4400, 21, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (77, 4501, 30, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (78, 2, 32, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (79, 3, 33, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (80, 4, 34, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (81, 5, 35, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (82, 6, 36, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (83, 7, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (84, 8, 37, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (85, 9, 38, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (86, 10, 39, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (87, 11, 40, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (88, 12, 41, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (89, 13, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (90, 1715, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (91, 1716, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (92, 1717, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (93, 2804, 42, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (94, 2805, 42, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (95, 2801, 43, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (96, 2802, 43, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (105, 4800, 44, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (106, 4900, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (107, 4901, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (108, 5000, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (109, 5001, 6, 2);
