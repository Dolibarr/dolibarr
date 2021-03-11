-- Copyright (C) 2009-2018 Regis Houssin  <regis.houssin@inodbox.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


-- Hidden but specific to one entity
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MAIL_EMAIL_FROM','dolibarr-robot@domain.com','chaine','EMail emetteur pour les emails automatiques Dolibarr',0,__ENTITY__);

--
-- IHM
--

insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENU_STANDARD','eldy_menu.php','chaine','Module de gestion de la barre de menu du haut pour utilisateurs internes',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENUFRONT_STANDARD','eldy_menu.php','chaine','Module de gestion de la barre de menu du haut pour utilisateurs externes',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENU_SMARTPHONE','eldy_menu.php','chaine','Module de gestion de la barre de menu smartphone pour utilisateurs internes',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_MENUFRONT_SMARTPHONE','eldy_menu.php','chaine','Module de gestion de la barre de menu smartphone pour utilisateurs externes',0,__ENTITY__);

insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_THEME','eldy','chaine','Default theme',0,__ENTITY__);

--
-- Delai tolerance
--
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_ACTIONS_TODO','7','chaine','Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_ORDERS_TO_PROCESS','2','chaine','Tolérance de retard avant alerte (en jours) sur commandes clients non traitées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS','7','chaine','Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_PROPALS_TO_CLOSE','31','chaine','Tolérance de retard avant alerte (en jours) sur propales à cloturer',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_PROPALS_TO_BILL','7','chaine','Tolérance de retard avant alerte (en jours) sur propales non facturées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_CUSTOMER_BILLS_UNPAYED','31','chaine','Tolérance de retard avant alerte (en jours) sur factures client impayées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_SUPPLIER_BILLS_TO_PAY','2','chaine','Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_NOT_ACTIVATED_SERVICES','0','chaine','Tolérance de retard avant alerte (en jours) sur services à activer',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_RUNNING_SERVICES','0','chaine','Tolérance de retard avant alerte (en jours) sur services expirés',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_MEMBERS','31','chaine','Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values ('MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE','62','chaine','Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire',0,__ENTITY__);

--
-- Mail Mailing
--
insert into llx_const (name, value, type, note, visible, entity) values ('MAILING_EMAIL_FROM','dolibarr@domain.com','chaine','EMail emmetteur pour les envois d emailings',0,__ENTITY__);

--
-- Accounting
--
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('VT', 'Sale Journal',           2, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('AC', 'Purchase Journal',       3, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('BQ', 'Bank Journal',           4, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('OD', 'Other Journal',          1, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('AN', 'Has new Journal',        9, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('ER', 'Expense Report Journal', 5, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('INV', 'Inventory Journal'    , 8, 1, __ENTITY__);