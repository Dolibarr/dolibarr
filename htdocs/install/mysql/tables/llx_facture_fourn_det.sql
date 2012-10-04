-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2010 juanjo Menent        <jmenent@2byte.es>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_facture_fourn_det
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture_fourn  integer NOT NULL,
  fk_product        integer NULL,
  ref               varchar(50),   -- supplier product ref
  label             varchar(255),  -- product label
  description       text,
  pu_ht             double(24,8), -- unit price excluding tax
  pu_ttc            double(24,8), -- unit price with tax
  qty               real,         -- quantity of product/service
  remise_percent	real       DEFAULT 0,				-- % de la remise ligne (exemple 20%)
  tva_tx            double(6,3),  -- TVA taux product/service
  localtax1_tx      double(6,3)  DEFAULT 0,    -- localtax1 rate
  localtax1_type	varchar(1)	  NULL, 		-- localtax1 type
  localtax2_tx      double(6,3)  DEFAULT 0,    -- localtax2 rate
  localtax2_type	varchar(1)	  NULL, 		-- localtax2 type
  total_ht          double(24,8), -- Total line price of product excluding tax
  tva               double(24,8), -- Total TVA of line
  total_localtax1   double(24,8) DEFAULT 0,	-- Total LocalTax1 for total quantity of line
  total_localtax2   double(24,8) DEFAULT 0,	-- total LocalTax2 for total quantity of line
  total_ttc         double(24,8), -- Total line with tax
  product_type	    integer      DEFAULT 0,
  date_start        datetime   DEFAULT NULL,       -- date debut si service
  date_end          datetime   DEFAULT NULL,       -- date fin si service
  import_key        varchar(14)
)ENGINE=innodb;
