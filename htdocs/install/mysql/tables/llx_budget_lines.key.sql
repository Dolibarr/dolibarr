-- ===================================================================
-- Copyright (C) 2015	Laurent Destailleur	<eldy@users.sourceforge.net>
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


ALTER TABLE llx_budget_lines ADD UNIQUE INDEX uk_budget_lines (fk_budget, fk_project_ids);

-- Supprime orphelins pour permettre montee de la cle
-- MYSQL V4 DELETE llx_budget_lines FROM llx_budget_lines LEFT JOIN llx_budget ON llx_budget.rowid = llx_budget_lines.fk_budget WHERE llx_budget_lines.rowid IS NULL;
-- POSTGRESQL V8 DELETE FROM llx_budget_lines USING llx_budget WHERE llx_budget_lines.fk_budget NOT IN (SELECT llx_budget.rowid FROM llx_budget);

ALTER TABLE llx_budget_lines ADD CONSTRAINT fk_budget_lines_budget FOREIGN KEY (fk_budget) REFERENCES llx_budget (rowid);
