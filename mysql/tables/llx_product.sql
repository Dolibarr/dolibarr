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

create table llx_product
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec              datetime,
  tms                timestamp,
  ref                varchar(15) UNIQUE,
  label              varchar(255),
  description        text,
  price              double,
  tva_tx             double default 19.6,
  fk_user_author     integer,
  envente            tinyint default 1,
  nbvente            integer default 0,
  fk_product_type    integer default 0,
  duration           varchar(6),
  stock_propale      integer default 0,
  stock_commande     integer default 0,
  seuil_stock_alerte integer default 0

)type=innodb;



