-- ============================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ============================================================================

create table llx_contrat
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  enservice       smallint default 0,
  mise_en_service datetime,
  fin_validite    datetime,
  date_cloture    datetime,
  fk_soc          integer NOT NULL,
  fk_product      integer NOT NULL,
  fk_facture      integer NOT NULL default 0,
  fk_user_author  integer NOT NULL,
  fk_user_mise_en_service integer NOT NULL,
  fk_user_cloture integer NOT NULL
);

