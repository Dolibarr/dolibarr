-- ============================================================================
-- Copyright (C) 2014      Cédric GROSS         <c.gross@kreiz-it.fr>
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
-- ============================================================================

ALTER TABLE llx_returndet_batch ADD INDEX idx_fk_returndet (fk_returndet);
ALTER TABLE llx_returndet_batch ADD CONSTRAINT fk_returndet_batch_fk_returndet FOREIGN KEY (fk_returndet) REFERENCES llx_returndet(rowid);
