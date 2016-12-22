-- ===================================================================
-- Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2016	Regis Houssin		<regis.houssin@capnetworks.com>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

ALTER TABLE llx_holiday ADD INDEX idx_holiday_entity (entity);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_user (fk_user);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_user_create (fk_user_create);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_create (date_create);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_debut (date_debut);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_fin (date_fin);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_validator (fk_validator);
