-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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
-- ============================================================================

ALTER TABLE llx_fichinter        ADD CONSTRAINT fichinter_fk_soc_idp       FOREIGN KEY (fk_soc)     REFERENCES llx_societe (idp);

ALTER TABLE llx_propal           ADD CONSTRAINT propal_fk_soc_idp       FOREIGN KEY (fk_soc)     REFERENCES llx_societe (idp);

ALTER TABLE llx_facture          ADD CONSTRAINT facture_fk_soc_idp       FOREIGN KEY (fk_soc)     REFERENCES llx_societe (idp);

ALTER TABLE llx_facturedet       ADD CONSTRAINT facturedet_fk_facture_rowid FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);

ALTER TABLE llx_facture_tva_sum  ADD CONSTRAINT facture_tva_sum_fk_facture_rowid FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);

ALTER TABLE llx_socpeople        ADD CONSTRAINT socpeople_fk_soc_idp       FOREIGN KEY (fk_soc)     REFERENCES llx_societe (idp);
