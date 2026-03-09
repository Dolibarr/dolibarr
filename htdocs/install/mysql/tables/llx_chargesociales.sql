-- ========================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2017      Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2021      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
-- Copyright (C) 2022      Laurent Destailleur	<eldy@users.sourceforge.net>
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
-- ========================================================================

create table llx_chargesociales
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  ref               varchar(16),                    -- 'TX....'
  date_ech          datetime NOT NULL,              -- date echeance
  libelle           varchar(80) NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,     -- multi company id
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  date_creation     datetime,                       -- date de creation 
  date_valid        datetime,                       -- date de validation
  fk_user integer   DEFAULT NULL,		    -- utilisateur concerné
  fk_user_author    integer,                        -- user making creation
  fk_user_modif     integer,                        -- user making last change
  fk_user_valid     integer,                        -- user validating
  fk_type           integer NOT NULL,
  fk_account        integer,                        -- bank account
  fk_mode_reglement integer,                        -- mode de reglement
  amount            double(24,8) default 0 NOT NULL,
  paye              smallint default 0 NOT NULL,
  periode           date,
  fk_projet         integer DEFAULT NULL,
  note_private		text,
  note_public		text,
  import_key        varchar(14)
)ENGINE=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company tax
-- 2 : second company tax
-- 3 : etc...
--
