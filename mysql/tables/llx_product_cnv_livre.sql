-- ============================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
--
-- Produit specifique livre
--
create table llx_product_cnv_livre
(
  rowid              integer PRIMARY KEY,
  isbn               varchar(13),       -- code ISBN
  ean                varchar(13),       -- code EAN
  format             varchar(7),        -- format de l'ouvrage

  px_feuillet        float,             -- prix au feuillet
  px_couverture      float,             -- prix de la couverture
  px_revient         float,             -- prix de revient
  stock_loc          varchar(5),        -- emplacement dans le stock

  pages              smallint UNSIGNED  -- nombre de page

)type=innodb;



