-- ============================================================================
-- Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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

ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_doc_date (doc_date);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_fk_doc (fk_doc);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_fk_docdet (fk_docdet);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_numero_compte (numero_compte);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_code_journal (code_journal);

-- Current unicity is tested by the journalize page on couple (fk_doc, doc_type) 
-- TODO Add a key for unicity (not so easy as fk_doc, doc_type may have several lines for one piece)
