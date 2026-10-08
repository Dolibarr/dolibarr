-- ========================================================================
-- Copyright (C) 2010 Regis Houssin      <regis.houssin@inodbox.com>
-- Copyright (C) 201 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- ========================================================================


ALTER TABLE llx_c_ziptown ADD INDEX idx_c_ziptown_fk_county (fk_county);
ALTER TABLE llx_c_ziptown ADD INDEX idx_c_ziptown_fk_pays   (fk_pays);
ALTER TABLE llx_c_ziptown ADD INDEX idx_c_ziptown_zip       (zip);

ALTER TABLE llx_c_ziptown ADD CONSTRAINT fk_c_ziptown_fk_county	FOREIGN KEY (fk_county) REFERENCES llx_c_departements (rowid);
ALTER TABLE llx_c_ziptown ADD CONSTRAINT fk_c_ziptown_fk_pays   FOREIGN KEY (fk_pays)   REFERENCES llx_c_country(rowid);

ALTER TABLE llx_c_ziptown ADD UNIQUE INDEX uk_ziptown_fk_pays (zip, town, fk_pays);
