-- ============================================================================
-- Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
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

create table llx_product
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec              datetime,
  tms                timestamp,
  ref                varchar(32)  NOT NULL,
  label              varchar(255) NOT NULL,
  description        text,
  note               text,
  price              double(24,8) DEFAULT 0,
  price_ttc          double(24,8) DEFAULT 0,
  price_min          double(24,8) DEFAULT 0,
  price_min_ttc      double(24,8) DEFAULT 0,
  price_base_type    varchar(3)   DEFAULT 'HT',
  tva_tx             double(6,3),
  fk_user_author     integer,
  envente            tinyint      DEFAULT 1,
  fk_product_type    integer      DEFAULT 0,
  duration           varchar(6),
  seuil_stock_alerte integer      DEFAULT 0,
  stock_loc          varchar(10),               -- emplacement dans le stock
  barcode            varchar(255) DEFAULT NULL,
  fk_barcode_type    integer      DEFAULT 0,
  partnumber         varchar(32),
  weight             float        DEFAULT NULL,
  weight_units       tinyint      DEFAULT NULL,
  volume             float        DEFAULT NULL,
  volume_units       tinyint      DEFAULT NULL,
  pmp                double(24,8) default 0 NOT NULL;
  canvas             varchar(15)  DEFAULT '',
  finished			 tinyint	  DEFAULT NULL,
  import_key         varchar(14)
)type=innodb;

