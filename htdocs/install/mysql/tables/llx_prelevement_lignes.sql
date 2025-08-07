-- ===================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

-- This table contains the detail of status and bank information on direct debit or credit transfer.
-- This is the real value used to generated the SEPA file for example when llx_prelevement_demande contains the intent.
create table llx_prelevement_lignes
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  fk_prelevement_bons integer,
  fk_soc              integer NOT NULL,
  fk_user             integer NULL,

  statut              smallint DEFAULT 0,

  client_nom          varchar(255),
  amount              double(24,8) DEFAULT 0,

  code_banque         varchar(128), -- deprecated
  code_guichet        varchar(6), 	-- deprecated
  number              varchar(255), -- deprecated
  cle_rib             varchar(5),	-- deprecated

  bic                 varchar(11),   -- 11 according to ISO 9362
  iban			      varchar(80),   -- full iban. 34 according to ISO 13616 but we set 80 to allow to store it with encryption information
  rum			      varchar(32),   -- RUM used for the direct debit

  note                text,
  tms             	  timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
