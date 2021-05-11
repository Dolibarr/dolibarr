-- ============================================================================
-- Copyright (C) 2010	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
-- ============================================================================


ALTER TABLE llx_ecm_directories ADD UNIQUE INDEX uk_ecm_directories (label, fk_parent, entity);
--ALTER TABLE llx_ecm_directories ADD UNIQUE INDEX uk_ecm_directories_fullpath(fullpath); Disabled, mysql limits size of index
ALTER TABLE llx_ecm_directories ADD INDEX idx_ecm_directories_fk_user_c (fk_user_c);
ALTER TABLE llx_ecm_directories ADD INDEX idx_ecm_directories_fk_user_m (fk_user_m);

ALTER TABLE llx_ecm_directories ADD CONSTRAINT fk_ecm_directories_fk_user_c      FOREIGN KEY (fk_user_c)         REFERENCES llx_user (rowid);
ALTER TABLE llx_ecm_directories ADD CONSTRAINT fk_ecm_directories_fk_user_m      FOREIGN KEY (fk_user_m)         REFERENCES llx_user (rowid);