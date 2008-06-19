--
-- $Id$
-- $Source$
-- $Revision$
--
-- Menu auguria entries
-- This file is loaded when a menu handler auguria is activated
--

delete from llx_menu_const where fk_menu in (select rowid from llx_menu where menu_handler='auguria');
delete from llx_menu where menu_handler='auguria';
delete from llx_menu_constraint;
delete from llx_menu_const;

-- 
-- table `llx_menu`
-- 
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'home',        '', 0, '/index.php?mainmenu=home&amp;leftmenu=', 'Home', -1, '', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'companies',   '', 0, '/index.php?mainmenu=companies&amp;leftmenu=', 'ThirdParties', -1, 'companies', '', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'products',    '', 0, '/product/index.php?mainmenu=products&amp;leftmenu=', 'Products/Services', -1, 'products', '$user->rights->produit->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'suppliers',   '', 0, '/fourn/index.php?mainmenu=suppliers&amp;leftmenu=', 'Suppliers', -1, 'suppliers', '$user->rights->fournisseur->lire', '', 0, 4);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'commercial',  '', 0, '/comm/index.php?mainmenu=commercial&amp;leftmenu=', 'Commercial', -1, 'commercial', '$user->rights->commercial->main->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'accountancy', '', 0, '/compta/index.php?mainmenu=accountancy&amp;leftmenu=', 'MenuFinancial', -1, 'compta', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->plancompte->lire || $user->rights->commande->lire || $user->rights->facture->lire || $user->rights->banque->lire', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'project',     '', 0, '/projet/index.php?mainmenu=project&amp;leftmenu=', 'Projects', -1, 'projects', '$user->rights->projet->lire', '', 0, 7);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'tools',       '', 0, '/index.php?mainmenu=tools&amp;leftmenu=', 'Tools', -1, 'other', '$user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire', '', 2, 8);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'telephony',   '', 0, '/telephonie/index.php?mainmenu=telephony&amp;leftmenu=', 'Telephony', -1, 'telephony', '$user->rights->telephonie->lire', '', 2, 9);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'energy',      '', 0, '/energie/index.php?mainmenu=energy&amp;leftmenu=', 'Energy', -1, 'energy', '', '', 2, 10);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'shop',        '', 0, '/boutique/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 11);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'shop',        '', 0, '/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 12);
insert into `llx_menu` (`menu_handler`, `type`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'top', 'members',     '', 0, '/adherents/index.php?mainmenu=members&amp;leftmenu=', 'Members', -1, 'members', '', '', 2, 15);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 100, 'home', '', __1__, '/admin/index.php?leftmenu=setup', 'Setup', 0, 'admin', '', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 101, 'home', '', 100, '/admin/company.php', 'MenuCompanySetup', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 102, 'home', '', 100, '/admin/ihm.php', 'GUISetup', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 103, 'home', '', 100, '/admin/modules.php', 'Modules', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 104, 'home', '', 100, '/admin/boxes.php', 'Boxes', 1, 'admin', '', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 105, 'home', '', 100, '/admin/menus.php', 'Menus', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 106, 'home', '', 100, '/admin/delais.php', 'Alerts', 1, 'admin', '', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 108, 'home', '', 100, '/admin/perms.php', 'Security', 1, 'admin', '', '', 2, 7);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 109, 'home', '', 100, '/admin/mails.php', 'Emails', 1, 'admin', '', '', 2, 8);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 110, 'home', '', 100, '/admin/limits.php', 'MenuLimits', 1, 'admin', '', '', 2, 9);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 111, 'home', '', 100, '/admin/dict.php', 'DictionnarySetup', 1, 'admin', '', '', 2, 10);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 112, 'home', '', 100, '/admin/const.php', 'OtherSetup', 1, 'admin', '', '', 2, 11);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 200, 'home', '', __1__, '/admin/system/index.php?leftmenu=system', 'SystemInfo', 0, 'admin', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 201, 'home', '', 200, '/admin/system/dolibarr.php', 'Dolibarr', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 202, 'home', '', 201, '/admin/system/constall.php', 'AllParameters', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 207, 'home', '', 201, '/admin/triggers.php', 'Triggers', 2, 'admin', '', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 203, 'home', '', 201, '/about.php', 'About', 2, 'admin', '', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 204, 'home', '', 200, '/admin/system/os.php', 'OS', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 205, 'home', '', 200, '/admin/system/web.php', 'WebServer', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 206, 'home', '', 200, '/admin/system/phpinfo.php', 'Php', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 210, 'home', '', 200, '/admin/system/database.php', 'Database', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 211, 'home', '', 210, '/admin/system/database-tables.php', 'Tables', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 212, 'home', '', 210, '/admin/system/database-tables-contraintes.php', 'Constraints', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 300, 'home', '', __1__, '/admin/tools/index.php?leftmenu=admintools', 'SystemTools', 0, 'admin', '', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 301, 'home', '', 300, '/admin/tools/dolibarr_export.php', 'Backup', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 302, 'home', '', 300, '/admin/tools/dolibarr_import.php', 'Restore', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 303, 'home', '', 300, '/admin/tools/purge.php', 'Purge', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 305, 'home', '', 300, '/admin/tools/update.php', 'Upgrade', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 306, 'home', '', 300, '/admin/tools/listevents.php', 'Audit', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 304, 'home', '', 300, '/admin/tools/eaccelerator.php', 'EAccelerator', 1, 'admin', '', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 400, 'home', '', __1__, '/user/home.php?leftmenu=users', 'MenuUsersAndGroups', 0, 'users', '', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 401, 'home', '', 400, '/user/index.php', 'Users', 1, 'users', '$user->rights->user->user->lire || $user->admin', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 402, 'home', '', 401, '/user/fiche.php?action=create', 'NewUser', 2, 'users', '$user->rights->user->user->creer || $user->admin', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 403, 'home', '', 400, '/user/group/index.php', 'Groups', 1, 'users', '$user->rights->user->user->lire || $user->admin', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 404, 'home', '', 403, '/user/group/fiche.php?action=create', 'NewGroup', 2, 'users', '$user->rights->user->user->creer || $user->admin', '', 2, 0);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 500, 'companies', '', __2__, '/societe.php', 'ThirdParty', 0, 'companies', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 501, 'companies', '', 500, '/soc.php?action=create', 'MenuNewThirdParty', 1, 'companies', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 502, 'companies', '', 500, '/societe/groupe/index.php', 'MenuSocGroup', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 503, 'companies', '', 500, '/fourn/liste.php?leftmenu=suppliers', 'Suppliers', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 504, 'companies', '', 503, '/soc.php?leftmenu=supplier&action=create&type=f', 'NewSupplier', 2, 'suppliers', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 505, 'companies', '', 503, '/contact/index.php?leftmenu=suppliers&type=f', 'Contacts', 2, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 506, 'companies', '', 500, '/comm/prospect/prospects.php?leftmenu=prospects', 'Prospects', 1, 'companies', '$user->rights->societe->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 507, 'companies', '', 506, '/soc.php?leftmenu=prospects&action=create&type=p', 'MenuNewProspect', 2, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 508, 'companies', '', 506, '/contact/index.php?leftmenu=customers&type=p', 'Contacts', 2, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 509, 'companies', '', 500, '/comm/clients.php?leftmenu=customers', 'Customers', 1, 'companies', '$user->rights->societe->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 510, 'companies', '', 509, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 2, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 511, 'companies', '', 509, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 2, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 600, 'companies', '', __2__, '/contact/index.php?leftmenu=contacts', 'Contacts', 0, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 601, 'companies', '', 600, '/contact/fiche.php?leftmenu=contacts&action=create', 'NewContact', 1, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 602, 'companies', '', 600, '/contact/index.php?leftmenu=contacts', 'List', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2800, 'products', '', __3__, '/product/index.php?leftmenu=product&type=0', 'Products', 0, 'products', '$user->rights->produit->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2801, 'products', '', 2800, '/product/fiche.php?leftmenu=product&action=create&type=0', 'NewProduct', 1, 'products', '$user->rights->produit->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2802, 'products', '', 2800, '/product/liste.php?leftmenu=product&type=0', 'ProductList', 1, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2803, 'products', '', 2800, '/product/reassort.php?type=0', 'Stocks', 1, 'products', '$user->rights->stock->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2804, 'products', '', 2800, '/product/fiche.php?leftmenu=product&action=create&type=0&canvas=livre', 'NewBook', 1, 'products', '$user->rights->produit->creer', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2805, 'products', '', 2800, '/product/liste.php?leftmenu=product&type=0&canvas=livre', 'BookList', 1, 'products', '$user->rights->produit->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2900, 'products', '', __3__, '/product/index.php?leftmenu=service&type=1', 'Services', 0, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2901, 'products', '', 2900, '/product/fiche.php?leftmenu=service&action=create&type=1', 'NewService', 1, 'products', '$user->rights->produit->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2902, 'products', '', 2900, '/product/liste.php?leftmenu=service&type=1', 'List', 1, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3000, 'products', '', __3__, '/product/stats/index.php?leftmenu=stats', 'Statistics', 0, 'main', '$user->rights->produit>lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3001, 'products', '', 3000, '/product/popuprop.php?leftmenu=stats', 'Popularity', 1, 'main', '$user->rights->produit>lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3100, 'products', '', __3__, '/product/stock/index.php?leftmenu=stock', 'Stock', 0, 'stocks', '$user->rights->stock->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3101, 'products', '', 3100, '/product/stock/fiche.php?action=create', 'MenuNewWarehouse', 1, 'stocks', '$user->rights->stock->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3102, 'products', '', 3100, '/product/stock/liste.php', 'List', 1, 'stocks', '$user->rights->stock->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3103, 'products', '', 3100, '/product/stock/valo.php', 'EnhancedValue', 1, 'stocks', '$user->rights->stock->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3104, 'products', '', 3100, '/product/stock/mouvement.php', 'Movements', 1, 'stocks', '$user->rights->stock->mouvement->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3200, 'products', '', __3__, '/categories/index.php?leftmenu=cat&type=0', 'Categories', 0, 'categories', '$user->rights->categorie>lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3201, 'products', '', 3200, '/categories/fiche.php?action=create&type=0', 'NewCat', 1, 'categories', '$user->rights->categorie>creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4800, 'products', '', __3__, '/product/droitpret/index.php?leftmenu=droitpret', 'Droit de prêt', 0, 'products', '$user->rights->droitpret->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4801, 'products', '', 4800, '/product/droitpret/index.php?leftmenu=droitpret', 'Générer rapport', 1, 'products', '$user->rights->droitpret->creer', '', 2, 1);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3300, 'suppliers', '', __4__, '/fourn/index.php?leftmenu=suppliers', 'Suppliers', 0, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3301, 'suppliers', '', 3300, '/soc.php?leftmenu=suppliers&action=create&type=f', 'NewSupplier', 1, 'suppliers', '$user->rights->societe->creer && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3302, 'suppliers', '', 3300, '/fourn/liste.php', 'List', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3303, 'suppliers', '', 3300, '/contact/index.php?leftmenu=supplier&type=f', 'Contacts', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3304, 'suppliers', '', 3300, '/fourn/stats.php', 'Statistics', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3400, 'suppliers', '', __4__, '/fourn/facture/index.php', 'Bills', 0, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3401, 'suppliers', '', 3400, '/fourn/facture/fiche.php?action=create', 'NewBill', 1, 'bills', '$user->rights->fournisseur->facture->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3402, 'suppliers', '', 3400, '/fourn/facture/paiement.php', 'Payments', 1, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3500, 'suppliers', '', __4__, '/fourn/commande/index.php?leftmenu=suppliers', 'Orders', 0, 'orders', '$user->rights->fournisseur->commande->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3501, 'suppliers', '', 3500, '/societe.php?leftmenu=supplier', 'NewOrder', 1, 'orders', '$user->rights->fournisseur->commande->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3502, 'suppliers', '', 3500, '/fourn/commande/liste.php?leftmenu=suppliers', 'List', 1, 'orders', '$user->rights->fournisseur->commande->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4900, 'suppliers', '', __4__, '/categories/index.php?leftmenu=cat&type=1', 'Categories', 0, 'categories', '$user->rights->categorie>lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4901, 'suppliers', '', 4900, '/categories/fiche.php?action=create&type=1', 'NewCat', 1, 'categories', '$user->rights->categorie>creer', '', 2, 0);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 700, 'commercial', '', __5__, '/comm/prospect/index.php?leftmenu=prospects', 'Prospects', 0, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 701, 'commercial', '', 700, '/soc.php?leftmenu=prospects&action=create&type=c', 'MenuNewProspect', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 702, 'commercial', '', 700, '/comm/prospect/prospects.php?leftmenu=prospects', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 703, 'commercial', '', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=-1', 'LastProspectDoNotContact', 2, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 704, 'commercial', '', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=0', 'LastProspectNeverContacted', 2, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 705, 'commercial', '', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=1', 'LastProspectToContact', 2, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 706, 'commercial', '', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=2', 'LastProspectContactInProcess', 2, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 707, 'commercial', '', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=3', 'LastProspectContactDone', 2, 'companies', '$user->rights->societe->lire', '', 0, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 708, 'commercial', '', 700, '/contact/index.php?leftmenu=prospects&type=p', 'Contacts', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 800, 'commercial', '', __5__, '/comm/index.php?leftmenu=customers', 'Customers', 0, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 801, 'commercial', '', 800, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 802, 'commercial', '', 800, '/comm/clients.php?leftmenu=customers', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 803, 'commercial', '', 800, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 900, 'commercial', '', __5__, '/contact/index.php?leftmenu=contacts', 'Contacts', 0, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 901, 'commercial', '', 900, '/contact/fiche.php?leftmenu=contacts&action=create', 'NewContact', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 902, 'commercial', '', 900, '/contact/index.php?leftmenu=contacts&action=create', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 910, 'commercial', '', 902, '/contact/index.php?leftmenu=contacts&type=p', 'Prospects', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 911, 'commercial', '', 902, '/contact/index.php?leftmenu=contacts&type=c', 'Customers', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 912, 'commercial', '', 902, '/contact/index.php?leftmenu=contacts&type=f', 'Suppliers', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 913, 'commercial', '', 902, '/contact/index.php?leftmenu=contacts&type=o', 'Other', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1100, 'commercial', '', __5__, '/comm/propal.php?leftmenu=propals', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1101, 'commercial', '', 1100, '/societe.php?leftmenu=propals', 'NewPropal', 1, 'propal', '$user->rights->propale->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1102, 'commercial', '', 1100, '/comm/propal.php?viewstatut=0', 'PropalsDraft', 1, 'propal', '$user->rights->propale->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1103, 'commercial', '', 1100, '/comm/propal.php?viewstatut=1', 'PropalsOpened', 1, 'propal', '$user->rights->propale->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1104, 'commercial', '', 1100, '/comm/propal.php?viewstatut=2,3,4', 'PropalStatusClosedShort', 1, 'propal', '$user->rights->propale->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1105, 'commercial', '', 1100, '/comm/propal/stats/index.php?leftmenu=propals', 'Statistics', 1, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1200, 'commercial', '', __5__, '/commande/index.php?leftmenu=orders', 'Orders', 0, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1201, 'commercial', '', 1200, '/societe.php?leftmenu=orders', 'NewOrder', 1, 'orders', '$user->rights->commande->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1202, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=0', 'StatusOrderDraftShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1203, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=1', 'StatusOrderValidated', 1, 'orders', '$user->rights->commande->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1204, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=2', 'StatusOrderOnProcessShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1205, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=3', 'StatusOrderToBill', 1, 'orders', '$user->rights->commande->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1206, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=4', 'StatusOrderProcessed', 1, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1207, 'commercial', '', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=-1', 'StatusOrderCanceledShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1208, 'commercial', '', 1200, '/commande/stats/index.php?leftmenu=orders', 'Statistics', 1, 'orders', '$user->rights->commande->lire', '', 2, 7);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1300, 'commercial', '', __5__, '/expedition/index.php?leftmenu=sendings', 'Sendings', 0, 'orders', '$user->rights->expedition->lire', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1301, 'commercial', '', 1300, '/expedition/liste.php?leftmenu=sendings', 'List', 1, 'orders', '$user->rights->expedition->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1302, 'commercial', '', 1300, '/expedition/stats/index.php?leftmenu=sendings', 'Statistics', 1, 'orders', '$user->rights->expedition->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1400, 'commercial', '', __5__, '/contrat/index.php?leftmenu=contracts', 'Contracts', 0, 'contracts', '$user->rights->contrat->lire', '', 2, 7);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1401, 'commercial', '', 1400, '/societe.php?leftmenu=contracts', 'NewContract', 1, 'contracts', '$user->rights->contrat->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1402, 'commercial', '', 1400, '/contrat/liste.php?leftmenu=contracts', 'List', 1, 'contracts', '$user->rights->contrat->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1403, 'commercial', '', 1400, '/contrat/services.php?leftmenu=contracts', 'MenuServices', 1, 'contracts', '$user->rights->contrat->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1404, 'commercial', '', 1402, '/contrat/services.php?leftmenu=contracts&mode=0', 'MenuInactiveServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1405, 'commercial', '', 1402, '/contrat/services.php?leftmenu=contracts&mode=4', 'MenuRunningServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1406, 'commercial', '', 1402, '/contrat/services.php?leftmenu=contracts&mode=4&filter=expired', 'MenuExpiredServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1407, 'commercial', '', 1402, '/contrat/services.php?leftmenu=contracts&mode=5', 'MenuClosedServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1500, 'commercial', '', __5__, '/fichinter/index.php?leftmenu=ficheinter', 'Interventions', 0, 'interventions', '$user->rights->ficheinter->lire', '', 2, 8);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1501, 'commercial', '', 1500, '/fichinter/fiche.php?action=create&leftmenu=ficheinter', 'NewIntervention', 1, 'interventions', '$user->rights->ficheinter->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1502, 'commercial', '', 1500, '/fichinter/index.php?leftmenu=ficheinter', 'List', 1, 'interventions', '$user->rights->ficheinter->lire', '', 2, 1);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1600, 'accountancy', '', __6__, '/compta/index.php?leftmenu=suppliers', 'Suppliers', 0, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1601, 'accountancy', '', 1600, '/soc.php?leftmenu=suppliers&action=create&type=f', 'NewSupplier', 1, 'companies', '$user->rights->societe->creer && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1602, 'accountancy', '', 1600, '/fourn/liste.php?leftmenu=suppliers', 'List', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1603, 'accountancy', '', 1600, '/contact/index.php?leftmenu=suppliers&type=f', 'Contacts', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1604, 'accountancy', '', 1600, '/fourn/facture/index.php?leftmenu=suppliers_bills', 'BillsSuppliers', 1, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1605, 'accountancy', '', 1604, '/fourn/facture/fiche.php?action=create', 'NewBill', 2, 'bills', '$user->rights->fournisseur->facture->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1606, 'accountancy', '', 1604, '/fourn/facture/impayees.php', 'Unpayed', 2, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1607, 'accountancy', '', 1604, '/fourn/facture/paiement.php', 'Payments', 2, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1700, 'accountancy', '', __6__, '/compta/index.php?leftmenu=customers', 'Customers', 0, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1701, 'accountancy', '', 1700, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 1, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1702, 'accountancy', '', 1700, '/compta/clients.php?leftmenu=customers', 'List', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1703, 'accountancy', '', 1700, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1704, 'accountancy', '', 1700, '/compta/facture.php?leftmenu=customers_bills', 'BillsCustomers', 1, 'bills', '$user->rights->facture->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1705, 'accountancy', '', 1704, '/compta/clients.php?action=facturer&leftmenu=customers_bills', 'NewBill', 2, 'bills', '$user->rights->facture->creer', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1706, 'accountancy', '', 1704, '/compta/facture/fiche-rec.php?leftmenu=customers_bills', 'Repeatable', 2, 'bills', '$user->rights->facture->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1707, 'accountancy', '', 1704, '/compta/facture/impayees.php?action=facturer&leftmenu=customers_bills', 'Unpayed', 2, 'bills', '$user->rights->facture->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1708, 'accountancy', '', 1704, '/compta/paiement/liste.php?leftmenu=customers_bills_payments', 'Payments', 2, 'bills', '$user->rights->facture->lire', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1709, 'accountancy', '', 1708, '/compta/paiement/avalider.php?leftmenu=customers_bills_payments', 'MenuToValid', 3, 'bills', '$user->rights->facture->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1710, 'accountancy', '', 1708, '/compta/paiement/rapport.php?leftmenu=customers_bills_payments', 'Reportings', 3, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1711, 'accountancy', '', __6__, '/compta/paiement/cheque/index.php?leftmenu=checks', 'MenuChequeDeposits', 0, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1712, 'accountancy', '', 1711, '/compta/paiement/cheque/fiche.php?leftmenu=checks&action=new', 'NewCheckDeposit', 1, 'bills', '$user->rights->facture->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1713, 'accountancy', '', 1711, '/compta/paiement/cheque/liste.php?leftmenu=checks', 'MenuChequesReceipts', 1, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1714, 'accountancy', '', 1704, '/compta/facture/stats/index.php?leftmenu=customers_bills', 'Statistics', 2, 'bills', '$user->rights->facture->lire', '', 2, 8);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1715, 'accountancy', '', 1700, '/compta/paiement/cheque/index.php', 'CheckReceipt', 1, 'bills', '$user->rights->banque->cheque', '', 1, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1716, 'accountancy', '', 1704, '/compta/paiement/cheque/fiche.php?action=new', 'New', 2, 'bills', '$user->rights->banque->cheque', '', 1, 9);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1717, 'accountancy', '', 1704, '/compta/paiement/cheque/liste.php', 'List', 2, 'bills', '$user->rights->banque->cheque', '', 1, 10);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1800, 'accountancy', '', __6__, '/compta/propal.php', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 1900, 'accountancy', '', __6__, '/compta/commande/liste.php?leftmenu=orders&status=3&afacturer=1', 'MenuOrdersToBill', 0, 'orders', '$user->rights->commande->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2000, 'accountancy', '', __6__, '/compta/dons/index.php?leftmenu=donations&mainmenu=accountancy', 'Donations', 0, 'donations', '$user->rights->don->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2001, 'accountancy', '', 2000, '/compta/dons/fiche.php?action=create', 'NewDonation', 1, 'donations', '$user->rights->don->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2002, 'accountancy', '', 2000, '/compta/dons/liste.php?action=create', 'List', 1, 'donations', '$user->rights->don->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2003, 'accountancy', '', 2000, '/compta/dons/stats.php', 'Statistics', 1, 'donations', '$user->rights->don->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2100, 'accountancy', '', __6__, '/compta/deplacement/index.php?leftmenu=tripsandexpenses', 'TripsAndExpenses', 0, 'trips', '$user->rights->deplacement->lire', '', 0, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2101, 'accountancy', '', 2100, '/compta/deplacement/fiche.php?action=create&leftmenu=tripsandexpenses', 'New', 1, 'trips', '$user->rights->deplacement->creer', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2102, 'accountancy', '', 2100, '/compta/deplacement/index.php?leftmenu=tripsandexpenses', 'List', 1, 'trips', '$user->rights->deplacement->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2200, 'accountancy', '', __6__, '/compta/charges/index.php?leftmenu=tax&mainmenu=accountancy', 'MenuTaxAndDividends', 0, 'compta', '$user->rights->tax->charges->lire', '', 0, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2201, 'accountancy', '', 2200, '/compta/sociales/index.php?leftmenu=tax_social', 'SocialContributions', 1, '', '$user->rights->tax->charges->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2202, 'accountancy', '', 2201, '/compta/sociales/charges.php?leftmenu=tax_social&action=create', 'MenuNewSocialContribution', 2, '', '$user->rights->tax->charges->creer', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2203, 'accountancy', '', 2201, '/compta/sociales/index.php?leftmenu=tax_social', 'List', 2, '', '$user->rights->tax->charges->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2300, 'accountancy', '', 2200, '/compta/tva/index.php?leftmenu=tax_vat&mainmenu=accountancy', 'VAT', 1, 'companies', '$user->rights->tax->charges->lire', '', 0, 7);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2301, 'accountancy', '', 2300, '/compta/tva/fiche.php?leftmenu=tax_vat&action=create', 'NewPayment', 2, 'companies', '$user->rights->tax->charges->creer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2302, 'accountancy', '', 2300, '/compta/tva/reglement.php?leftmenu=tax_vat', 'List', 2, 'companies', '$user->rights->tax->charges->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2303, 'accountancy', '', 2300, '/compta/tva/clients.php?leftmenu=tax_vat', 'ReportByCustomers', 2, 'companies', '$user->rights->tax->charges->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2304, 'accountancy', '', 2300, '/compta/tva/quadri_detail.php?leftmenu=tax_vat', 'ReportByQuarter', 2, 'companies', '$user->rights->tax->charges->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2400, 'accountancy', '', __6__, '/compta/ventilation/index.php?leftmenu=ventil', 'Ventilation', 0, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 8);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2401, 'accountancy', '', 2400, '/compta/ventilation/liste.php', 'A ventiler', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2402, 'accountancy', '', 2400, '/compta/ventilation/lignes.php', 'Ventilées', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2403, 'accountancy', '', 2400, '/compta/param/', 'Setup', 1, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2404, 'accountancy', '', 2403, '/compta/param/comptes/liste.php', 'List', 2, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2405, 'accountancy', '', 2403, '/compta/param/comptes/fiche.php?action=create', 'New', 2, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2406, 'accountancy', '', 2400, '/compta/export/', 'Export', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2407, 'accountancy', '', 2406, '/compta/export/index.php', 'New', 2, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2408, 'accountancy', '', 2406, '/compta/export/liste.php', 'List', 2, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2500, 'accountancy', '', __6__, '/compta/prelevement/index.php?leftmenu=withdraw', 'StandingOrders', 0, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 9);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2501, 'accountancy', '', 2500, '/compta/prelevement/demandes.php?status=0', 'StandingOrderToProcess', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2502, 'accountancy', '', 2500, '/compta/prelevement/create.php', 'NewStandingOrder', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2503, 'accountancy', '', 2500, '/compta/prelevement/bons.php', 'WithdrawalsReceipts', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2504, 'accountancy', '', 2500, '/compta/prelevement/liste.php', 'WithdrawalsLines', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2505, 'accountancy', '', 2500, '/compta/prelevement/liste_factures.php', 'WithdrawedBills', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2506, 'accountancy', '', 2500, '/compta/prelevement/rejets.php', 'Rejects', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2507, 'accountancy', '', 2500, '/compta/prelevement/stats.php', 'Statistics', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2508, 'accountancy', '', 2500, '/compta/prelevement/config.php', 'Setup', 1, 'withdrawals', '$user->rights->prelevement->bons->configurer', '', 2, 7);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2600, 'accountancy', '', __6__, '/compta/bank/index.php?leftmenu=bank', 'MenuBankCash', 0, 'banks', '$user->rights->banque->lire', '', 0, 10);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2601, 'accountancy', '', 2600, '/compta/bank/fiche.php?action=create', 'MenuNewFinancialAccount', 1, 'banks', '$user->rights->banque->configurer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2602, 'accountancy', '', 2600, '/compta/bank/categ.php', 'Categories', 1, 'banks', '$user->rights->banque->configurer', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2603, 'accountancy', '', 2600, '/compta/bank/search.php', 'SearchTransaction', 1, 'banks', '$user->rights->banque->lire', '', 0, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2604, 'accountancy', '', 2600, '/compta/bank/budget.php', 'ByCategories', 1, 'banks', '$user->rights->banque->lire', '', 0, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2606, 'accountancy', '', 2600, '/compta/bank/virement.php', 'BankTransfers', 1, 'banks', '$user->rights->banque->modifier', '', 0, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2700, 'accountancy', '', __6__, '/compta/resultat/index.php?leftmenu=ca&mainmenu=accountancy', 'Reportings', 0, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 11);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2701, 'accountancy', '', 2700, '/compta/resultat/index.php?leftmenu=ca', 'Résultat / Exercice', 1, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2702, 'accountancy', '', 2701, '/compta/resultat/clientfourn.php?leftmenu=ca', 'ByCompanies', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2703, 'accountancy', '', 2700, '/compta/stats/index.php?leftmenu=ca', 'Chiffre d''affaire', 1, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2704, 'accountancy', '', 2703, '/compta/stats/casoc?leftmenu=ca', 'ByCompanies', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 2705, 'accountancy', '', 2703, '/compta/stats/cabyuser.php?leftmenu=ca', 'ByUsers', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 1);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3600, 'project', '', __7__, '/projet/index.php?leftmenu=projects', 'Projects', 0, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3601, 'project', '', 3600, '/comm/clients.php?leftmenu=projects', 'NewProject', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3602, 'project', '', 3600, '/projet/liste.php?leftmenu=projects', 'List', 1, 'projects', '$user->rights->projet->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3700, 'project', '', __7__, '/projet/tasks', 'Tasks', 0, 'projects', '$user->rights->projet->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3701, 'project', '', 3700, '/projet/tasks/mytasks.php', 'Mytasks', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3800, 'project', '', __7__, '/projet/activity', 'Activity', 0, 'projects', '$user->rights->projet->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3801, 'project', '', 3800, '/projet/activity/myactivity.php', 'MyActivity', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3900, 'tools', '', __8__, '/comm/mailing/index.php?leftmenu=mailing', 'EMailings', 0, 'mails', '$user->rights->mailing->lire', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3901, 'tools', '', 3900, '/comm/mailing/fiche.php?leftmenu=mailing&action=create', 'NewMailing', 1, 'mails', '$user->rights->mailing->creer', '', 0, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 3902, 'tools', '', 3900, '/comm/mailing/liste.php?leftmenu=mailing', 'List', 1, 'mails', '$user->rights->mailing->lire', '', 0, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4000, 'tools', '', __8__, '/bookmarks/liste.php?leftmenu=bookmarks', 'Bookmarks', 0, 'other', '$user->rights->bookmark->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4001, 'tools', '', 4000, '/bookmarks/fiche.php?action=create', 'NewBookmark', 1, 'other', '$user->rights->bookmark->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4002, 'tools', '', 4000, '/bookmarks/liste.php', 'List', 1, 'other', '$user->rights->bookmark->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4100, 'tools', '', __8__, '/exports/index.php?leftmenu=export', 'FormatedExport', 0, 'exports', '$user->rights->export->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4101, 'tools', '', 4100, '/exports/export.php?leftmenu=export', 'NewExport', 1, 'exports', '$user->rights->export->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4130, 'tools', '', __8__, '/admin/import/index.php?leftmenu=import', 'FormatedImport', 0, 'exports', '$user->rights->import->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4131, 'tools', '', 4130, '/admin/import/import.php?leftmenu=import', 'NewImport', 1, 'exports', '$user->rights->import->creer', '', 2, 0);

insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4200, 'members', '', __13__, '/adherents/index.php?leftmenu=members&mainmenu=members', 'Members', 0, 'members', '$user->rights->adherent->lire', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4201, 'members', '', 4200, '/adherents/fiche.php?action=create', 'NewMember', 1, 'members', '$user->rights->adherent->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4202, 'members', '', 4200, '/adherents/liste.php', 'List', 1, 'members', '$user->rights->adherent->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4203, 'members', '', 4200, '/adherents/liste.php?statut=-1', 'MenuMembersToValidate', 1, 'members', '$user->rights->adherent->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4204, 'members', '', 4200, '/adherents/liste.php?statut=1', 'MenuMembersValidated', 1, 'members', '$user->rights->adherent->lire', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4205, 'members', '', 4200, '/adherents/liste.php?statut=1&filter=outofdate', 'MenuMembersNotUpToDate', 1, 'members', '$user->rights->adherent->lire', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4206, 'members', '', 4200, '/adherents/liste.php?statut=1&filter=uptodate', 'MenuMembersUpToDate', 1, 'members', '$user->rights->adherent->lire', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4207, 'members', '', 4200, '/adherents/liste.php?statut=0', 'MenuMembersResiliated', 1, 'members', '$user->rights->adherent->lire', '', 2, 6);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4300, 'members', '', __13__, '/adherents/index.php?leftmenu=accountancy&mainmenu=members', 'Subscriptions', 0, 'compta', '$user->rights->adherent->cotisation->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4301, 'members', '', 4300, '/adherents/liste.php?statut=-1&leftmenu=accountancy&mainmenu=members', 'NewSubscription', 1, 'compta', '$user->rights->adherent->cotisation->creer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4302, 'members', '', 4300, '/adherents/cotisations.php?leftmenu=accountancy', 'List', 1, 'compta', '$user->rights->adherent->cotisation->lire', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4400, 'members', '', __13__, '/compta/bank/index.php?leftmenu=accountancy', 'Bank', 0, 'banks', '$user->rights->adherent->lire', '', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4500, 'members', '', __13__, '/adherents/index.php?leftmenu=export&mainmenu=members', 'Exports', 0, 'members', '$user->rights->adherent->export', '', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4501, 'members', '', 4500, '/exports/index.php?leftmenu=export', 'Datas', 1, 'members', '$user->rights->adherent->export', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4502, 'members', '', 4500, '/adherents/htpasswd.php?leftmenu=export', 'Filehtpasswd', 1, 'members', '$user->rights->adherent->export', '', 2, 1);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4503, 'members', '', 4500, '/adherents/cartes/carte.php?leftmenu=export', 'MembersCards', 1, 'members', '$user->rights->adherent->export', '_blank', 2, 2);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4504, 'members', '', 4500, '/adherents/cartes/etiquette.php?leftmenu=export', 'Etiquettes d''adhérents', 1, 'members', '$user->rights->adherent->export', '_blank', 2, 3);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4600, 'members', '', __13__, '/public/adherents/index.php?leftmenu=member_public', 'MemberPublicLinks', 0, 'members', '$user->rights->adherent->export', '', 2, 4);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4700, 'members', '', __13__, '/adherents/index.php?leftmenu=setup&mainmenu=members', 'Setup', 0, 'members', '$user->rights->adherent->configurer', '', 2, 5);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4701, 'members', '', 4700, '/adherents/type.php?leftmenu=setup', 'MembersTypes', 1, 'members', '$user->rights->adherent->configurer', '', 2, 0);
insert into `llx_menu` (`menu_handler`, `type`, `rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, perms, `target`, `user`, position) values ('auguria', 'left', 4702, 'members', '', 4700, '/adherents/options.php?leftmenu=setup', 'MembersAttributes', 1, 'members', '$user->rights->adherent->configurer', '', 2, 1);


-- 
-- table `llx_menu_constraint`
-- 
insert into `llx_menu_constraint` (`rowid`, `action`) values (1,  '$user->admin');
insert into `llx_menu_constraint` (`rowid`, `action`) values (2,  '$conf->societe->enabled && $user->rights->societe->lire');
insert into `llx_menu_constraint` (`rowid`, `action`) values (3,  '$user->rights->societe->creer');
insert into `llx_menu_constraint` (`rowid`, `action`) values (4,  'is_dir("societe/groupe")');
insert into `llx_menu_constraint` (`rowid`, `action`) values (5,  '$conf->societe->enabled && $conf->fournisseur->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (6,  '! $user->societe_id');
insert into `llx_menu_constraint` (`rowid`, `action`) values (7,  '$conf->propal->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (8,  '$conf->commande->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (9,  '$conf->expedition->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (10, '$conf->contrat->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (11, '$conf->fichinter->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (12, '$conf->societe->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (13, '$conf->facture->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (14, '! $conf->global->FACTURE_DISABLE_RECUR');
insert into `llx_menu_constraint` (`rowid`, `action`) values (15, '$conf->don->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (16, '$conf->deplacement->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (17, '$conf->tax->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (18, '$conf->compta->tva');
insert into `llx_menu_constraint` (`rowid`, `action`) values (19, '$conf->compta->enabled');
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
insert into `llx_menu_constraint` (`rowid`, `action`) values (36, '$conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled || $conf->commande->enabled || $conf->facture->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (37, '$conf->mailing->enabled || $conf->export->enabled || $conf->bookmark->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (38, '$conf->boutique->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (39, '$conf->oscommerce2->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (40, '$conf->webcal->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (41, '$conf->mantis->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (42, '$conf->global->PRODUIT_SPECIAL_LIVRE && $conf->global->PRODUCT_CANVAS_ABILITY');
insert into `llx_menu_constraint` (`rowid`, `action`) values (44, '$conf->droitpret->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (45, '$conf->menudb->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (46, '$conf->energie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (47, '$conf->telephonie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (48, '$user->admin && function_exists("eaccelerator_info")');
insert into `llx_menu_constraint` (`rowid`, `action`) values (49, '$conf->import->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (50, '$conf->phenix->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (51, '$leftmenu=="setup"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (52, '$leftmenu=="system"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (53, '$leftmenu=="admintools"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (54, '$leftmenu=="users"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (55, '$leftmenu=="customers"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (56, '$leftmenu=="prospects"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (57, '$leftmenu=="contacts"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (58, '$leftmenu=="propals"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (59, '$leftmenu=="orders"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (60, '$leftmenu=="orders_suppliers"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (61, '$leftmenu=="contracts"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (62, '$leftmenu=="ficheinter"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (63, '$leftmenu=="suppliers"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (64, '$leftmenu=="tripsandexpenses"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (65, 'eregi("tax",$leftmenu)');
insert into `llx_menu_constraint` (`rowid`, `action`) values (66, '$leftmenu=="tax_social"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (67, '$leftmenu=="tax_vat"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (68, '$leftmenu=="checks"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (69, '$leftmenu=="bank"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (70, '$leftmenu=="suppliers_bills"');
insert into `llx_menu_constraint` (`rowid`, `action`) values (71, '$leftmenu=="customers_bills"');


-- 
-- table `llx_menu_const`
-- 

-- Menu Setup
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (100, 1);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (101, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (102, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (103, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (104, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (105, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (106, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (108, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (109, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (110, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (111, 51);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (112, 51);
-- Menu System infos
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (200, 1);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (201, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (202, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (203, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (204, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (205, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (206, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (210, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (211, 52);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (212, 52);
-- Menu System tools
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (300, 1);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (301, 53);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (302, 53);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (303, 53);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (305, 53);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (306, 53);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (304, 53);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (304, 48);
-- Menu users and groups
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (401, 54);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (402, 54);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (403, 54);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (404, 54);


insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (501, 3);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (502, 4);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (504, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (503, 5);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (504, 5);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (505, 5);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (500, 2);

-- Menu Prospects - List
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (703, 56);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (704, 56);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (705, 56);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (706, 56);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (707, 56);

-- Menu Contacts
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (912, 34);

-- Menu commercial proposal
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1100, 7);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1101, 58);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1102, 58);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1103, 58);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1104, 58);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1105, 58);
-- Menu orders
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1200, 8);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1201, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1202, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1203, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1204, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1205, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1206, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1207, 59);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1208, 59);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1300, 9);
-- Menu contracts
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1400, 10);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1401, 61);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1402, 61);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1403, 61);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1404, 61);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1405, 61);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1406, 61);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1407, 61);
-- Menu interventions
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1500, 11);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1501, 62);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1502, 62);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1600, 5);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1601, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1603, 12);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1605, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1604, 13);
-- Menu suppliers invoices
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1605, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1605, 70);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1606, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1606, 70);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1607, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1607, 70);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1701, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1700, 12);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1704, 13);
-- Menu customers invoices
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1705, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1705, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1705, 71);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1706, 14);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1706, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1706, 71);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1707, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1707, 71);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1708, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1708, 71);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1709, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1709, 71);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1710, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1710, 71);

-- Menu checks deposit 
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1711, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1711, 21);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1712, 68);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1713, 68);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1714, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1714, 71);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1800, 7);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1900, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1900, 8);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2000, 15);
-- Menu trips and expenses
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2100, 16);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2101, 64);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2102, 64);

-- Menu tax
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2200, 17);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2201, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2202, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2203, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2300, 18);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2300, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2301, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2302, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2303, 65);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2304, 65);

-- Menu accountacy - Ventilation
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2400, 19);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2500, 20);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2300, 21);

-- Menu bank 
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2601, 69);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2602, 69);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2603, 69);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2604, 69);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2605, 69);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2606, 69);

insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2700, 22);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2800, 23);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2801, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2803, 24);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2900, 25);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2901, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3000, 7);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3100, 24);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3200, 26);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3201, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3300, 5);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3301, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3400, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3401, 6);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3500, 8);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3600, 27);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3700, 27);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3800, 27);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (3900, 28);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4000, 29);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4100, 30);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4130, 49);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4200, 31);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4300, 31);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4400, 31);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4500, 31);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4600, 31);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4700, 31);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4400, 21);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4501, 30);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1715, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1716, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (1717, 13);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2804, 42);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (2805, 42);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4800, 44);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4900, 26);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (4901, 6);

-- Top menu
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__2__, 32);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__3__, 33);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__4__, 34);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__5__, 35);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__6__, 36);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__7__, 27);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__8__, 37);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__9__, 47);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__10__, 46);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__11__, 38);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__12__, 39);
insert into `llx_menu_const` (`fk_menu`, `fk_constraint`) values (__13__, 31);

