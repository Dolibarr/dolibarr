-- ========================================================================
-- Copyright (C) 2005     Patrick Rouillon     <patrick.rouillon.net>
-- Copyright (C) 2005     Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ========================================================================
-- Defini les types de contact d'un element sert de reference pour
-- la table llx_element_contact
--
-- element est le nom de la table utilisant le type de contact.
-- i.e. contact, facture, projet, societe (sans le llx_ devant).
-- Libelle est un texte décrivant le type de contact.
-- active précide si cette valeur est active ou 'archivé'.



ALTER TABLE llx_c_type_contact 
	ADD UNIQUE INDEX idx_c_type_contact_uk (element, source, code);

