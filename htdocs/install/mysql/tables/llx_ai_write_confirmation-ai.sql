-- ===================================================================
-- Copyright (C) 2026 Nick Fragoulis
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
-- Pending write confirmations of the AI assistant (MCP multi-round-trip).
-- A write tool does not execute on the first call: it returns a preview and an
-- opaque state, and a row here records it. The row is marked consumed when the
-- caller comes back with that state, so a confirmation cannot be replayed.
-- A row left unconsumed is a write that was proposed and never carried out.
-- ===================================================================


create table llx_ai_write_confirmation
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  entity					integer DEFAULT 1 NOT NULL,
  state_hash				varchar(80) NOT NULL,					-- Hash of the requestState handed to the caller
  fk_user					integer NOT NULL,						-- User the state was issued to
  tool_name					varchar(255) NOT NULL,					-- Tool the confirmation is for
  args_hash					varchar(80) NOT NULL,					-- Hash of the arguments, so confirmed arguments cannot change
  preview					text,									-- Description of the pending write
  date_creation				datetime NOT NULL,
  date_expiration			datetime NOT NULL,						-- After this date the state is refused
  date_consumed				datetime,								-- Set when the write was confirmed and executed
  ip						varchar(250)							-- Origin of the request that asked for the write
)ENGINE=innodb;
