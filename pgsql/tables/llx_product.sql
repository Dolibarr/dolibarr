-- ===================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===================================================================

create table llx_product
(
  rowid              SERIAL PRIMARY KEY,
  datec              timestamp without time zone,
  tms                timestamp,
  ref                varchar(15) UNIQUE,
  label              varchar(255),
  description        text,
  price              double precision,
  tva_tx             double precision DEFAULT 19.6,
  fk_user_author     integer,
  envente            smallint DEFAULT 1,
  nbvente            integer DEFAULT 0,
  fk_product_type    integer DEFAULT 0,
  duration           varchar(6),
  stock_propale      integer DEFAULT 0,
  stock_commande     integer DEFAULT 0,
  seuil_stock_alerte integer DEFAULT 0
);
