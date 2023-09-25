-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2008 Regis Houssin        <regis.houssin@capnetworks.com>
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


ALTER TABLE llx_returndet ADD INDEX idx_returndet_fk_return (fk_return);
ALTER TABLE llx_returndet ADD INDEX idx_origin (fk_origin_line, fk_product);
ALTER TABLE llx_returndet ADD CONSTRAINT fk_returndet_fk_return FOREIGN KEY (fk_return) REFERENCES llx_return (rowid);
