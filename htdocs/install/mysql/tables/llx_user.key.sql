-- ============================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- $Id: llx_user.key.sql,v 1.2 2011/08/03 01:25:26 eldy Exp $
-- ===========================================================================


ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_login (login, entity);

ALTER TABLE llx_user ADD INDEX uk_user_fk_societe   (fk_societe);

ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_fk_socpeople (fk_socpeople);
ALTER TABLE llx_user ADD UNIQUE INDEX uk_user_fk_member    (fk_member);
