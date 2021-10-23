-- ===================================================================
-- Copyright (C) 2000-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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

create table llx_bank
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datev           date,                      -- date de valeur
  dateo           date,                      -- date operation
  amount          double(24,8) NOT NULL default 0,
  label           varchar(255),
  fk_account      integer,
  fk_user_author  integer,
  fk_user_rappro  integer,
  fk_type         varchar(6),                -- TIP,VIR,PRE,CB,CHQ,... (Code in llx_c_paiement)
  num_releve      varchar(50),
  num_chq         varchar(50),
  numero_compte   varchar(32) NULL,		       -- FEC:CompteNum	| account number
  rappro          tinyint default 0,
  note            text,
  fk_bordereau    integer DEFAULT 0,
  banque          varchar(255),              -- banque pour les cheques
  emetteur        varchar(255),              -- emetteur du cheque
  author          varchar(40),               -- a supprimer apres migration
  origin_id       integer,
  origin_type     varchar(64) NULL,
  import_key	  varchar(14)
)ENGINE=innodb;
