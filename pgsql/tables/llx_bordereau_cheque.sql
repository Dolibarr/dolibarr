-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id: llx_bordereau_cheque.sql,v 1.3 2006/12/22 14:38:16 rodolphe Exp $
-- $Source: /cvsroot/dolibarr/dolibarr/mysql/tables/llx_bordereau_cheque.sql,v $
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
-- ===================================================================
--
-- Bordereaux de remise de cheque
--
create table llx_bordereau_cheque
(
  rowid SERIAL PRIMARY KEY,
  "datec"             timestamp,
  "date_bordereau"    date,
  "number"            mediumint ZEROFILL,
  "amount"            float(12,2),
  "nbcheque"          smallint DEFAULT 0,
  "fk_bank_account"   integer,
  "fk_user_author"    integer,
  "note"              text,
  "statut"            int2 DEFAULT 0
);
