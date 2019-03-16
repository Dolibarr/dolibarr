-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2019 	   Markus Welters       <markus@welters.de>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Setup constants
--

-- Visible in misc page
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_NOT_INSTALLED','1','chaine','Setup is running',1,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_FEATURES_LEVEL','0','chaine','Level of features to show: -1=stable+deprecated, 0=stable only (default), 1=stable+experimental, 2=stable+experimental+development',1,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAILING_LIMIT_SENDBYWEB','25','chaine','Number of targets to defined packet size when sending mass email',1,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_ENABLE_LOG_TO_HTML','0','chaine','If this option is set to 1, it is possible to see log output at end of HTML sources by adding paramater logtohtml=1 on URL. Module log must also be enabled.',1,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_SECURITY_CSRF_WITH_TOKEN','0','chaine','If this option is set to 1, a CSRF protection using an antiCSRF token is added.',1,0);

-- Hidden and common to all entities
insert into llx_const (name, value, type, note, visible, entity) values ('SYSLOG_HANDLERS','["mod_syslog_file"]','chaine','Which logger to use',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('SYSLOG_FILE','DOL_DATA_ROOT/dolibarr.log','chaine','Directory where to write log file',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('SYSLOG_LEVEL','7','chaine','Level of debug info to show',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_UPLOAD_DOC','2048','chaine','Max size for file upload (0 means no upload allowed)',0,0);

-- Hidden but specific to one entity
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_ENABLE_OVERWRITE_TRANSLATION','1','chaine','Enable translation overwrite',0,1);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_ENABLE_DEFAULT_VALUES','1','chaine','Enable default value overwrite',0,1);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MONNAIE','EUR','chaine','Monnaie',0,1);

insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MAIL_SMTP_SERVER','','chaine','Host or ip address for SMTP server',0,1);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MAIL_SMTP_PORT','','chaine','Port for SMTP server',0,1);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MAIL_EMAIL_FROM','robot@domain.com','chaine','EMail emetteur pour les emails automatiques Dolibarr',0,1);

--
-- IHM
--
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_SIZE_LISTE_LIMIT','25','chaine','Longueur maximum des listes',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_SIZE_SHORTLIST_LIMIT','3','chaine','Longueur maximum des listes courtes (fiche client)',0,0);

insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENU_STANDARD','eldy_menu.php','chaine','Menu manager for internal users',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENUFRONT_STANDARD','eldy_menu.php','chaine','Menu manager for external users',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENU_SMARTPHONE','eldy_menu.php','chaine','Menu manager for internal users using smartphones',0,0);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENUFRONT_SMARTPHONE','eldy_menu.php','chaine','Menu manager for external users using smartphones',0,0);


--
-- Warning delays
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_ACTIONS_TODO','7','chaine','Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_ORDERS_TO_PROCESS','2','chaine','Tolérance de retard avant alerte (en jours) sur commandes clients non traitées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS','7','chaine','Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_PROPALS_TO_CLOSE','31','chaine','Tolérance de retard avant alerte (en jours) sur propales à cloturer',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_PROPALS_TO_BILL','7','chaine','Tolérance de retard avant alerte (en jours) sur propales non facturées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_CUSTOMER_BILLS_UNPAYED','31','chaine','Tolérance de retard avant alerte (en jours) sur factures client impayées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_SUPPLIER_BILLS_TO_PAY','2','chaine','Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_NOT_ACTIVATED_SERVICES','0','chaine','Tolérance de retard avant alerte (en jours) sur services à activer',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_RUNNING_SERVICES','0','chaine','Tolérance de retard avant alerte (en jours) sur services expirés',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_MEMBERS','31','chaine','Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE','62','chaine','Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_EXPENSEREPORTS_TO_PAY','31','chaine','Tolérance de retard avant alerte (en jours) sur les notes de frais impayées',0);


--
-- Mail Mailing
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_FIX_FOR_BUGGED_MTA','1','chaine','Set constant to fix email ending from PHP with some linux ike system',1);
insert into llx_const (name, value, type, note, visible) values ('MAILING_EMAIL_FROM','dolibarr@domain.com','chaine','EMail emmetteur pour les envois d emailings',0);


--
-- ODT Path
--
insert into llx_const (name, value, type, visible, entity) VALUES ('PRODUCT_ADDON_PDF_ODT_PATH',   'DOL_DATA_ROOT/doctemplates/products', 'chaine', 0, 1);
insert into llx_const (name, value, type, visible, entity) VALUES ('CONTRACT_ADDON_PDF_ODT_PATH',  'DOL_DATA_ROOT/doctemplates/contracts', 'chaine', 0, 1);
insert into llx_const (name, value, type, visible, entity) VALUES ('USERGROUP_ADDON_PDF_ODT_PATH', 'DOL_DATA_ROOT/doctemplates/usergroups', 'chaine', 0, 1);
insert into llx_const (name, value, type, visible, entity) VALUES ('USER_ADDON_PDF_ODT_PATH',      'DOL_DATA_ROOT/doctemplates/users', 'chaine', 0, 1);

