-- ========================================================================
-- Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
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


ALTER TABLE llx_menu ADD INDEX idx_menu_menuhandler_type (menu_handler, type);

-- Erreur SQL DB_ERROR_1170 BLOB/TEXT column 'url' used in key specification without a key length

-- ALTER TABLE llx_menu ADD UNIQUE INDEX idx_menu_uk_menu (menu_handler, fk_menu, position, url, entity);
ALTER TABLE llx_menu ADD UNIQUE INDEX idx_menu_uk_menu (menu_handler, fk_menu, position, entity);
