-- Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

-- Extrafields table for Index Adjustments

CREATE TABLE llx_indexadjustment_extrafields (
	rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
	tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object       INTEGER NOT NULL,
	import_key      VARCHAR(14)
) ENGINE=InnoDB;

ALTER TABLE llx_indexadjustment_extrafields ADD INDEX idx_indexadjustment_extrafields (fk_object);
