-- ===================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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

create table llx_tva
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec           datetime,                   -- Create date
  datep           date,                       -- date de paiement
  datev           date,                       -- date de valeur
  amount          double(24,8) NOT NULL DEFAULT 0,
  fk_typepayment  integer NULL,
  num_payment     varchar(50),
  label           varchar(255),
  entity          integer DEFAULT 1 NOT NULL,	-- multi company id
  note            text,
  fk_bank         integer,  
  fk_user_creat   integer,                    -- utilisateur who create record
  fk_user_modif   integer,                    -- utilisateur who modify record
  import_key      varchar(14)
)ENGINE=innodb;
