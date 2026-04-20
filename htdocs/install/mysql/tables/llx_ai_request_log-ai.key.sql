-- ===================================================================
-- Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@inodbox.com>
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


-- Index for Entity
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_entity (entity);

-- Index for date searching
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_date (date_request);

-- Index for searching by user
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_user (fk_user);

-- Index for filtering by status
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_status (status);
