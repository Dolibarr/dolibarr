-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2015      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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


create table llx_don
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(30) DEFAULT NULL,     -- Ref donation (TODO change to NOT NULL)
  entity          integer DEFAULT 1 NOT NULL,	-- multi company id
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_statut       smallint NOT NULL DEFAULT 0,  -- Status of donation promise or validate
  datedon         datetime,                     -- Date of the donation/promise
  amount          double(24,8) DEFAULT 0,
  fk_payment      integer,						-- Id of payment mode
  paid            smallint default 0 NOT NULL,
  fk_soc      	  integer NULL, 
  firstname       varchar(50),
  lastname        varchar(50),
  societe         varchar(50),
  address         text,
  zip             varchar(30),
  town            varchar(50),
  country         varchar(50),					-- Deprecated - Replace with fk_country
  fk_country      integer NOT NULL,
  email           varchar(255),
  phone           varchar(24),
  phone_mobile    varchar(24),
  public          smallint DEFAULT 1 NOT NULL,  -- Donation is public ? (0,1)
  fk_projet       integer NULL,                 -- Donation is given for a project ?
  datec           datetime,                     -- Create date
  fk_user_author  integer NOT NULL,
  fk_user_modif   integer,
  date_valid      datetime,						-- date de validation
  fk_user_valid   integer NULL,
  note_private    text,
  note_public     text,
  model_pdf       varchar(255),
  import_key      varchar(14),
  extraparams	  varchar(255)							-- for other parameters with json format
)ENGINE=innodb;
