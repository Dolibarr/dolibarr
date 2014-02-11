-- ============================================================================
-- Copyright (C) 2009      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
-- ============================================================================


ALTER TABLE llx_employee ADD UNIQUE INDEX uk_employee_login (login, entity);
ALTER TABLE llx_employee ADD UNIQUE INDEX uk_employee_fk_user (fk_user);

ALTER TABLE llx_employee ADD INDEX idx_employee_fk_employee_type (fk_employee_type);

ALTER TABLE llx_employee ADD CONSTRAINT employee_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
ALTER TABLE llx_employee ADD CONSTRAINT fk_employee_employee_type FOREIGN KEY (fk_employee_type)    REFERENCES llx_employee_type (rowid);
