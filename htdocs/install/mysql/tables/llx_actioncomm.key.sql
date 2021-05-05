-- ============================================================================
-- Copyright (C) 2005-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2011		Regis Houssin		<regis.houssin@inodbox.com>
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
-- ===========================================================================

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_soc (fk_soc);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_contact (fk_contact);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_code (code);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_element (fk_element);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_user_action (fk_user_action);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_project (fk_project);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_datep (datep);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_datep2 (datep2);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_recurid (recurid);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_ref_ext (ref_ext);

ALTER TABLE llx_actioncomm ADD UNIQUE INDEX uk_actioncomm_ref (ref, entity);
