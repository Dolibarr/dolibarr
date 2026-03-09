-- ===================================================================
-- Copyright (C) 2006-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2006-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
-- ===================================================================


ALTER TABLE llx_boxes ADD UNIQUE INDEX uk_boxes (entity, box_id, position, fk_user);

-- Supprime orphelins pour permettre montee de la cle
-- MYSQL V4 DELETE llx_boxes FROM llx_boxes LEFT JOIN llx_boxes_def ON llx_boxes.box_id = llx_boxes_def.rowid WHERE llx_boxes_def.rowid IS NULL;
-- POSTGRESQL V8 DELETE FROM llx_boxes USING llx_boxes_def WHERE llx_boxes.box_id NOT IN (SELECT llx_boxes_def.rowid FROM llx_boxes_def);

ALTER TABLE llx_boxes ADD INDEX idx_boxes_boxid (box_id);
ALTER TABLE llx_boxes ADD CONSTRAINT fk_boxes_box_id FOREIGN KEY (box_id) REFERENCES llx_boxes_def (rowid);

ALTER TABLE llx_boxes ADD INDEX idx_boxes_fk_user (fk_user);
