-- ===================================================================
-- Copyright (C) 2000-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
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

create table llx_bank_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  label          varchar(30),
  bank           varchar(255),
  number         varchar(255),

  code_banque    varchar(7),
  code_guichet   varchar(6),
  cle_rib        varchar(5),
  bic            varchar(10),

  iban_prefix    varchar(5),

  domiciliation  varchar(50),

  courant        smallint default 0 not null
);
