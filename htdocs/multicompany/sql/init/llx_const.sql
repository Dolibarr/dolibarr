-- Copyright (C) 2009   Regis Houssin        <regis@dolibarr.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- $Id$
--

--
-- Constantes communes de configuration
--
UPDATE llx_const SET entity=0 WHERE name='MAIN_MODULE_MULTICOMPANY' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_MODULE_USER' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_POPUP_CALENDAR' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_MAIL_SMTP_SERVER' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_MAIL_SMTP_PORT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_UPLOAD_DOC' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_FEATURES_LEVEL' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_SOCIETE' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_CONTACT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_PRODUITSERVICE' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_ADHERENT' AND entity=1;

--
-- IHM
--
UPDATE llx_const SET entity=0 WHERE name='MAIN_SIZE_LISTE_LIMIT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SHOW_WORKBOARD' AND entity=1;

--
-- Tiers
--
UPDATE llx_const SET entity=0 WHERE name='SOCIETE_NOLIST_COURRIER' AND entity=1;

--
-- Barcode
--
UPDATE llx_const SET entity=0 WHERE name='GENBARCODE_LOCATION' AND entity=1;