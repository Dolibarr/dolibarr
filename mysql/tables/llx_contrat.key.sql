-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Source$
--
-- ============================================================================

--
--
ALTER TABLE llx_contrat ADD INDEX (fk_soc);
ALTER TABLE llx_contrat ADD INDEX (fk_commercial_signature);
ALTER TABLE llx_contrat ADD INDEX (fk_commercial_suivi);
ALTER TABLE llx_contrat ADD INDEX (fk_user_author);
--
--
ALTER TABLE llx_contrat ADD FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);
ALTER TABLE llx_contrat ADD FOREIGN KEY (fk_commercial_signature) REFERENCES llx_user (rowid);
ALTER TABLE llx_contrat ADD FOREIGN KEY (fk_commercial_suivi) REFERENCES llx_user (rowid);
ALTER TABLE llx_contrat ADD FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
