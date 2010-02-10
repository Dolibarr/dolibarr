-- ============================================================================
-- Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
--
-- ============================================================================


ALTER TABLE llx_projet_milestone ADD INDEX idx_projet_milestone_fk_projet (fk_projet);
ALTER TABLE llx_projet_milestone ADD INDEX idx_projet_milestone_fk_user_creat (fk_user_creat);

ALTER TABLE llx_projet_milestone ADD CONSTRAINT fk_projet_milestone_fk_projet 	  FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
ALTER TABLE llx_projet_milestone ADD CONSTRAINT fk_projet_milestone_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
