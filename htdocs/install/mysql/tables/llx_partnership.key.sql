-- ===================================================================
-- Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2016	Regis Houssin		<regis.houssin@inodbox.com>
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

ALTER TABLE llx_partnership ADD INDEX idx_partnership_entity (entity);
ALTER TABLE llx_partnership ADD UNIQUE INDEX uk_partnership_ref (ref, entity);
