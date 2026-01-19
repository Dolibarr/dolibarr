-- Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

-- Table for Index Adjustment batches
-- Each batch represents one index adjustment operation (e.g., VPI 2024)

CREATE TABLE llx_indexadjustment (
	rowid               INTEGER AUTO_INCREMENT PRIMARY KEY,
	ref                 VARCHAR(128) NOT NULL,
	entity              INTEGER DEFAULT 1,
	datec               DATETIME NOT NULL,
	tms                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat       INTEGER NOT NULL,
	fk_user_modif       INTEGER,

	-- Adjustment Details
	label               VARCHAR(255) NOT NULL,
	description         TEXT,
	adjustment_date     DATE NOT NULL,
	adjustment_percent  DOUBLE(10,4) NOT NULL,

	-- VPI Reference (optional)
	vpi_base_year       INTEGER,
	vpi_base_value      DOUBLE(10,2),
	vpi_current_year    INTEGER,
	vpi_current_value   DOUBLE(10,2),

	-- Scope
	fk_soc              INTEGER,  -- NULL = all customers, else specific customer

	-- Status: 0=Draft, 1=Validated, 2=Executed, 9=Cancelled
	status              INTEGER DEFAULT 0,
	date_executed       DATETIME,
	fk_user_executed    INTEGER,

	-- Statistics (populated after execution)
	total_contracts     INTEGER DEFAULT 0,
	total_lines         INTEGER DEFAULT 0,
	total_ht_before     DOUBLE(24,8) DEFAULT 0,
	total_ht_after      DOUBLE(24,8) DEFAULT 0

) ENGINE=InnoDB;

-- Index for performance
ALTER TABLE llx_indexadjustment ADD INDEX idx_indexadjustment_ref (ref);
ALTER TABLE llx_indexadjustment ADD INDEX idx_indexadjustment_fk_soc (fk_soc);
ALTER TABLE llx_indexadjustment ADD INDEX idx_indexadjustment_status (status);
ALTER TABLE llx_indexadjustment ADD INDEX idx_indexadjustment_entity (entity);
ALTER TABLE llx_indexadjustment ADD UNIQUE INDEX uk_indexadjustment_ref (ref, entity);
