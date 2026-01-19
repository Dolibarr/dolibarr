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

-- Table for Index Adjustment lines
-- Each line records before/after prices for a contract line

CREATE TABLE llx_indexadjustment_line (
	rowid               INTEGER AUTO_INCREMENT PRIMARY KEY,
	fk_indexadjustment  INTEGER NOT NULL,
	fk_contrat          INTEGER NOT NULL,
	fk_contratdet       INTEGER NOT NULL,

	-- Line info snapshot
	product_ref         VARCHAR(128),
	product_label       VARCHAR(255),

	-- Price Before
	subprice_before     DOUBLE(24,8) NOT NULL,
	qty                 DOUBLE(24,8) DEFAULT 1,
	total_ht_before     DOUBLE(24,8) NOT NULL,

	-- Price After
	subprice_after      DOUBLE(24,8) NOT NULL,
	total_ht_after      DOUBLE(24,8) NOT NULL,

	-- Change
	price_diff_ht       DOUBLE(24,8) NOT NULL,

	-- Rollback
	rollback_executed   TINYINT DEFAULT 0,
	rollback_date       DATETIME,
	fk_user_rollback    INTEGER,

	CONSTRAINT fk_indexadjustment_line_parent
		FOREIGN KEY (fk_indexadjustment)
		REFERENCES llx_indexadjustment(rowid)
		ON DELETE CASCADE

) ENGINE=InnoDB;

-- Indexes for performance
ALTER TABLE llx_indexadjustment_line ADD INDEX idx_indexadjustment_line_parent (fk_indexadjustment);
ALTER TABLE llx_indexadjustment_line ADD INDEX idx_indexadjustment_line_contrat (fk_contrat);
ALTER TABLE llx_indexadjustment_line ADD INDEX idx_indexadjustment_line_contratdet (fk_contratdet);
