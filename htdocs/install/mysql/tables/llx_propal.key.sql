-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_propal.key.sql,v 1.2 2011/08/03 01:25:24 eldy Exp $
-- ============================================================================


ALTER TABLE llx_propal ADD UNIQUE INDEX uk_propal_ref (ref, entity);

ALTER TABLE llx_propal ADD INDEX idx_propal_fk_soc (fk_soc);
ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);