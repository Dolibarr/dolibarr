-- ===========================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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

create table llx_facture_fourn
(
  rowid      SERIAL PRIMARY KEY,
  facnumber  varchar(50) NOT NULL,
  fk_soc     integer NOT NULL,
  datec      timestamp without time zone,  -- date de creation de la facture
  datef      date,                         -- date de la facture
  libelle    varchar(255),
  paye       smallint DEFAULT 0 NOT NULL,
  amount     real     DEFAULT 0 NOT NULL,
  remise     real     DEFAULT 0,
  tva        real     DEFAULT 0,
  total      real     DEFAULT 0,
  total_ht   real     DEFAULT 0,
  total_tva  real     DEFAULT 0,
  total_ttc  real     DEFAULT 0,
	
  fk_statut  smallint DEFAULT 0 NOT NULL,
  
	fk_user_author  integer,   -- createur de la propale
  fk_user_valid   integer,   -- valideur de la propale
  note       text
);

create unique index llx_facture_fourn_facnumber on llx_facture_fourn(facnumber, fk_soc);
