-- ========================================================================
-- Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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
-- ========================================================================


ALTER TABLE `llx_menu_const`
  ADD CONSTRAINT `llx_menu_const_ibfk_3` FOREIGN KEY (`fk_menu`) REFERENCES `llx_menu` (`rowid`),
  ADD CONSTRAINT `llx_menu_const_ibfk_4` FOREIGN KEY (`fk_constraint`) REFERENCES `llx_menu_constraint` (`rowid`);
