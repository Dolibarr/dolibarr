-- ============================================================================
-- Copyright (C) 2017 ATM Consulting      <contact@atm-consulting.fr>
-- Copyright (C) 2017 Pierre-Henry Favre  <phf@atm-consulting.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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

CREATE TABLE llx_expensereport_rules (
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  datec						datetime  DEFAULT NULL,
  tms						timestamp,
  dates						datetime NOT NULL,
  datee						datetime NOT NULL,
  amount					double(24,8) NOT NULL,
  restrictive				tinyint NOT NULL,
  fk_user					integer DEFAULT NULL,
  fk_usergroup				integer DEFAULT NULL,
  fk_c_type_fees			integer NOT NULL,
  code_expense_rules_type	varchar(50) NOT NULL,
  is_for_all				tinyint DEFAULT 0,
  entity					integer DEFAULT 1
) ENGINE=InnoDB;