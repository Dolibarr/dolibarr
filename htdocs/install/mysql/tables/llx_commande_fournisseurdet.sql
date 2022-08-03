-- ===================================================================
-- Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_commande_fournisseurdet
(
  rowid                      integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande                integer      NOT NULL,
  fk_parent_line             integer      NULL,
  fk_product                 integer,
  ref                        varchar(50),               -- supplier product ref
  label                      varchar(255),              -- product label
  description                text,
  vat_src_code               varchar(10)  DEFAULT '',   -- Vat code used as source of vat fields. Not strict foreign key here.
  tva_tx                     double(7,4)  DEFAULT 0,    -- taux tva
  localtax1_tx               double(7,4)  DEFAULT 0,    -- localtax1 rate
  localtax1_type             varchar(10)  NULL,         -- localtax1 type
  localtax2_tx               double(7,4)  DEFAULT 0,    -- localtax2 rate
  localtax2_type             varchar(10)  NULL,         -- localtax2 type
  qty                        real,                      -- quantity
  remise_percent             real         DEFAULT 0,    -- pourcentage de remise
  remise                     real         DEFAULT 0,    -- montant de la remise
  subprice                   double(24,8) DEFAULT 0,    -- prix unitaire
  total_ht                   double(24,8) DEFAULT 0,    -- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva                  double(24,8) DEFAULT 0,    -- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1            double(24,8) DEFAULT 0,    -- Total Local Tax 1
  total_localtax2            double(24,8) DEFAULT 0,    -- Total Local Tax 2
  total_ttc                  double(24,8) DEFAULT 0,    -- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  product_type               integer      DEFAULT 0,
  date_start                 datetime     DEFAULT NULL, -- date debut si service
  date_end                   datetime     DEFAULT NULL, -- date fin si service
  info_bits	                 integer      DEFAULT 0,    -- TVA NPR ou non
  special_code               integer      DEFAULT 0,    -- code for special lines
  rang                       integer      DEFAULT 0,
  import_key                 varchar(14),
  fk_unit                    integer      DEFAULT NULL,
  
  fk_multicurrency           integer,
  multicurrency_code         varchar(3),
  multicurrency_subprice     double(24,8) DEFAULT 0,
  multicurrency_total_ht     double(24,8) DEFAULT 0,
  multicurrency_total_tva    double(24,8) DEFAULT 0,
  multicurrency_total_ttc    double(24,8) DEFAULT 0
)ENGINE=innodb;
