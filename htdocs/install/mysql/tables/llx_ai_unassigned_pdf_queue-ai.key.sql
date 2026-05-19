-- ===================================================================
-- Copyright (C) 2026
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
-- ===================================================================

ALTER TABLE llx_ai_unassigned_pdf_queue ADD INDEX idx_ai_unassigned_pdf_queue_entity_status (entity, status, priority, rowid);
ALTER TABLE llx_ai_unassigned_pdf_queue ADD INDEX idx_ai_unassigned_pdf_queue_msgid (entity, message_id);
ALTER TABLE llx_ai_unassigned_pdf_queue ADD INDEX idx_ai_unassigned_pdf_queue_sha (entity, attachment_sha256);
ALTER TABLE llx_ai_unassigned_pdf_queue ADD UNIQUE INDEX uk_ai_unassigned_pdf_queue_msg_sha (entity, message_id, attachment_sha256);
