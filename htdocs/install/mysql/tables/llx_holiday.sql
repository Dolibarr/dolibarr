-- ===================================================================
-- Copyright (C) 2012      Laurent Destailleur  <eldy@users.sourceforge.net>
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

CREATE TABLE llx_holiday 
(
rowid          integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
ref			   varchar(30) NOT NULL,
ref_ext		   varchar(255),
entity         integer DEFAULT 1 NOT NULL,		-- Multi company id
fk_user        integer NOT NULL,
fk_user_create integer,
fk_user_modif  integer,
fk_type        integer NOT NULL,
date_create    DATETIME NOT NULL,
description    VARCHAR( 255 ) NOT NULL,
date_debut     DATE NOT NULL,
date_fin       DATE NOT NULL,
halfday        integer DEFAULT 0,				-- 0=start morning and end afternoon, -1=start afternoon end afternoon, 1=start morning and end morning, 2=start afternoon and end morning
nb_open_day    double(24,8) DEFAULT NULL,       -- denormalized number of open days of holiday. Not always set. More reliable when re-calculated with num_open_days(date_debut, date_fin, halfday).
statut         integer NOT NULL DEFAULT 1,      -- status of leave request
fk_validator   integer NOT NULL,				-- who should approve the leave
date_valid     DATETIME DEFAULT NULL,			-- date validation
fk_user_valid  integer DEFAULT NULL,			-- user validation
date_approval  DATETIME DEFAULT NULL,			-- date approval
fk_user_approve integer DEFAULT NULL,			-- user approval
date_refuse    DATETIME DEFAULT NULL,
fk_user_refuse integer DEFAULT NULL,
date_cancel    DATETIME DEFAULT NULL,
fk_user_cancel integer DEFAULT NULL,
detail_refuse  varchar( 250 ) DEFAULT NULL,
note_private   text,
note_public    text,
tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
import_key			varchar(14),
extraparams			varchar(255)				-- for other parameters with json format
) 
ENGINE=innodb;
