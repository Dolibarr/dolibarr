-- ===================================================================
-- Copyright (C) 2011-2018 Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2021      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
-- Copyright (C) 2023      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

create table llx_salary
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(30) NULL,           -- payment reference number (currently NULL because there is no numbering manager yet)
  ref_ext         varchar(255),				  -- reference into an external system (not used by dolibarr)
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec           datetime,                   -- Create date
  fk_user         integer NOT NULL,
  datep           date,                       -- payment date
  datev           date,                       -- value date (this field should not be here, only into bank tables)
  salary          double(24,8),               -- salary of user when payment was done
  amount          double(24,8) NOT NULL DEFAULT 0,
  fk_projet       integer DEFAULT NULL,
  fk_typepayment  integer NOT NULL,			  -- default expected payment
  num_payment     varchar(50),                -- num cheque or other (deprecated, now stored into the payment)
  label           varchar(255),
  datesp          date,                       -- date start period
  dateep          date,                       -- date end period
  entity          integer DEFAULT 1 NOT NULL, -- multi company id
  note            text,
  note_public     text,
  fk_bank         integer,
  paye            smallint default 0 NOT NULL,
  fk_account      integer,
  fk_user_author  integer,                    -- user creating
  fk_user_modif   integer                     -- user making last change
)ENGINE=innodb;
