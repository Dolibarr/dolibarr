-- ===========================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2011 Herve Prot           <herve.prot@symeos.com>
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
-- $Id: llx_modify.sql,v 1.7 2010/02/03 22:36:08 herve Exp $
-- ===========================================================================

alter table llx_societe add latitude       double     DEFAULT NULL;		-- coordonnées GPS
alter table llx_societe add longitude       double     DEFAULT NULL;		-- coordonnées GPS

