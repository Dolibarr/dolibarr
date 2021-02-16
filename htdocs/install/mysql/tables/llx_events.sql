-- ========================================================================
-- Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
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
-- This table logs all dolibarr security events
-- Content of this table is not managed by users but by Dolibarr
-- trigger interface_20_all_Logevents.
-- ========================================================================

create table llx_events
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,                   -- last modification date
  type           varchar(32)  NOT NULL,       -- action type
  entity         integer DEFAULT 1 NOT NULL,	-- multi company id
  prefix_session varchar(255) NULL,				  -- prefix of session, obtained with dol_getprefix
  dateevent      datetime,                    -- date event
  fk_user        integer,                     -- id user
  description    varchar(250) NOT NULL,       -- full description of action
  ip             varchar(250) NOT NULL,       -- ip (must contains ip v4 and v6 or dns names)
  user_agent     varchar(255) NULL,           -- user agent
  fk_object      integer                      -- id of related object
) ENGINE=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company events
-- 2 : second company events
-- 3 : etc...
--
