-- ============================================================================
-- Copyright (C) 2011-2016 Regis Houssin  <regis.houssin@inodbox.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ============================================================================


ALTER TABLE llx_entity ADD INDEX idx_entity_fk_user_creat (fk_user_creat);

ALTER TABLE llx_entity ADD CONSTRAINT fk_entity_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);