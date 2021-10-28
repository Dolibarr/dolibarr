-- ============================================================================
-- Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================


ALTER TABLE llx_payment_salary ADD INDEX idx_payment_salary_ref (num_payment);
ALTER TABLE llx_payment_salary ADD INDEX idx_payment_salary_user (fk_user, entity);
ALTER TABLE llx_payment_salary ADD INDEX idx_payment_salary_datep (datep);
ALTER TABLE llx_payment_salary ADD INDEX idx_payment_salary_datesp (datesp);
ALTER TABLE llx_payment_salary ADD INDEX idx_payment_salary_dateep (dateep);

ALTER TABLE llx_payment_salary ADD CONSTRAINT fk_payment_salary_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
