-- ========================================================================
-- Copyright (C) 2014-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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

ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (fk_pays, code, taux, recuperableonly);

-- ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (fk_pays, code, recuperableonly); -- Not yet possible for compatibility reason, where old code is ''
