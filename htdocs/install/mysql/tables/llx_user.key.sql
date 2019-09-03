-- ============================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007      Regis Houssin        <regis.houssin@inodbox.com>
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
-- ===========================================================================


ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_login (login, entity);

ALTER TABLE llx_user ADD INDEX idx_user_fk_societe  (fk_soc);

ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_fk_socpeople (fk_socpeople);
ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_fk_member    (fk_member);
ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_api_key      (api_key);
