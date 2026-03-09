-- ===================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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


create table llx_prelevement_demande
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  entity              integer DEFAULT 1 NOT NULL,
  fk_facture          integer NULL,
  fk_facture_fourn    integer NULL,
  fk_salary           integer NULL,
  sourcetype          varchar(32),
  amount              double(24,8) NOT NULL,
  date_demande        datetime NOT NULL,
  traite              smallint DEFAULT 0,		-- 1 means a file for direct-debit or credit-transfer has been created and is in process or finished. Note: can be detected by fk_prelevement_bons IS NOT NULL too.
  date_traite         datetime,
  fk_prelevement_bons integer,
  fk_user_demande     integer NOT NULL,

  code_banque         varchar(128),
  code_guichet        varchar(6),
  number              varchar(255),
  cle_rib             varchar(5),
  
  type                varchar(12) DEFAULT '',
  ext_payment_id      varchar(255),
  ext_payment_site    varchar(128)
)ENGINE=innodb;
