-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

-- ============================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
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
-- $Source$
--
-- ===========================================================================

create table llx_user
(
  rowid SERIAL PRIMARY KEY,
  "datec"             timestamp,
  "tms"               timestamp,
  "login"             varchar(24) NOT NULL,
  "pass"              varchar(32),
  "pass_crypted"      varchar(128),
  "pass_temp"         varchar(32),			-- temporary password when asked for forget password
  "name"              varchar(50),
  "firstname"         varchar(50),
  "office_phone"      varchar(20),
  "office_fax"        varchar(20),
  "user_mobile"       varchar(20),
  "email"             varchar(255),
  "admin"             smallint DEFAULT 0,
  "webcal_login"      varchar(25),
  "module_comm"       smallint DEFAULT 1,
  "module_compta"     smallint DEFAULT 1,
  "fk_societe"        integer,
  "fk_socpeople"      integer,
  "fk_member"         integer,
  "note"              text DEFAULT NULL,
  "datelastlogin"     timestamp,
  "datepreviouslogin" timestamp,
  "egroupware_id"     integer,
  "ldap_sid"          varchar(255) DEFAULT NULL,
  "statut"			      smallint DEFAULT 1,
  "lang"              varchar(6)
);
