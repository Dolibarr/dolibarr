-- ===================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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

create table llx_propaldet
(
  rowid         SERIAL PRIMARY KEY,
  fk_propal     integer,
  fk_product    integer,
  description    text,
  tva_tx         real default 19.6, -- taux tva
  qty		 real,              -- quantité
  remise_percent real default 0,    -- pourcentage de remise
  remise         real default 0,    -- montant de la remise
  subprice       real,              -- prix avant remise
  price          real               -- prix final
);
