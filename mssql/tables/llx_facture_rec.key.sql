-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- ============================================================================


CREATE INDEX idx_facture_rec_fk_soc ON llx_facture_rec(fk_soc);
CREATE INDEX idx_facture_rec_fk_user_author ON llx_facture_rec(fk_user_author);
CREATE INDEX idx_facture_rec_fk_projet ON llx_facture_rec(fk_projet);

ALTER TABLE llx_facture_rec ADD CONSTRAINT fk_facture_rec_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_rec ADD CONSTRAINT fk_facture_rec_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

CREATE UNIQUE INDEX idx_facture_rec_uk_titre ON llx_facture_rec(titre);

