-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

create table llx_facturedet_rec
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer,
  product_type    integer DEFAULT 0,
  description     text,
  tva_taux        real DEFAULT 19.6, -- taux tva
  qty             real,              -- quantity
  remise_percent  real DEFAULT 0,    -- pourcentage de remise
  remise          real DEFAULT 0,    -- montant de la remise
  subprice        real,              -- prix avant remise
  price           real,               -- prix final
  total_ht        real,	             	-- Total HT de la ligne toute quantity et incluant remise ligne et globale
  total_tva       real,	             	-- Total TVA de la ligne toute quantity et incluant remise ligne et globale
  total_ttc       real	             	-- Total TTC de la ligne toute quantity et incluant remise ligne et globale
)type=innodb;
