-- Copyright (C) ---Put here your own copyright and developer email---
-- Copyright (C) 2021  Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
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
-- along with this program. If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_stocktransfer_stocktransfer_extrafields ADD INDEX idx_fk_object(fk_object);
-- END MODULEBUILDER INDEXES
