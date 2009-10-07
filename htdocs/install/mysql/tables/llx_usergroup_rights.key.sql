-- ============================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- ===========================================================================


-- Supprime orhpelins pour permettre montée de la clé
-- V4 DELETE llx_usergroup_rights FROM llx_usergroup_rights LEFT JOIN llx_usergroup ON llx_usergroup_rights.fk_usergroup = llx_usergroup.rowid WHERE llx_usergroup.rowid IS NULL;


ALTER TABLE llx_usergroup_rights ADD CONSTRAINT fk_usergroup_rights_fk_usergroup FOREIGN KEY (fk_usergroup)    REFERENCES llx_usergroup (rowid);
