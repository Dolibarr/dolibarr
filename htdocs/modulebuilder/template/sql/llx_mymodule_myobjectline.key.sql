-- Copyright (C) ---Replace with your own copyright and developer email---
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.



ALTER TABLE llx_mymodule_myobjectline ADD INDEX idx_mymodule_myobjectline_fk_myobject (fk_myobject);
ALTER TABLE llx_mymodule_myobjectline ADD INDEX idx_mymodule_myobjectline_fk_product (fk_product);

ALTER TABLE llx_mymodule_myobjectline ADD CONSTRAINT llx_mymodule_myobjectline_fk_myobject FOREIGN KEY (fk_myobject) REFERENCES llx_mymodule_myobject(rowid);
