-- ========================================================================
-- Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- Table to declare notifications (per object)
-- ========================================================================

create table llx_notify_def_object
(
  id				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  objet_type		varchar(16),					-- 'actioncomm'
  objet_id			integer NOT NULL,				-- id of parent key
  type_notif		varchar(16) DEFAULT 'browser',	-- 'browser', 'email', 'sms', 'webservice', ...
  date_notif		datetime,						-- date notification
  user_id			integer,						-- notification is for this user
  moreparam			varchar(255)
)ENGINE=innodb;
