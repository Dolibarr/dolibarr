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
-- ============================================================================

create table llx_stock_valorisation
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  tms                timestamp,             -- date technique mise à jour automatiquement
  date_valo          datetime,              -- date de valorisation
  fk_product         integer NOT NULL,      -- id du produit concerne par l'operation
  qty_ope            float(9,3),            -- quantité de l'operation
  price_ope          float(12,4),           -- prix unitaire du produit concerne par l'operation
  valo_ope           float(12,4),           -- valorisation de l'operation
  price_pmp          float(12,4),           -- valeur PMP de l'operation
  qty_stock          float(9,3) DEFAULT 0,  -- qunatite en stock
  valo_pmp           float(12,4),           -- valorisation du stock en PMP
  fk_stock_mouvement integer               -- id du mouvement de stock
)type=innodb;

