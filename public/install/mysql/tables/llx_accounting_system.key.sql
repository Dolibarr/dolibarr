-- ============================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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


ALTER TABLE llx_accounting_system ADD UNIQUE INDEX uk_accounting_system_pcg_version (pcg_version);

-- This key is for another table but created here because must be done after foreign key index is created
ALTER TABLE llx_accounting_account ADD CONSTRAINT fk_accounting_account_fk_pcg_version    FOREIGN KEY (fk_pcg_version)    REFERENCES llx_accounting_system (pcg_version);
