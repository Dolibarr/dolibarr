-- ========================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
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
-- ========================================================================

create table llx_c_ape
(
  rowid       integer AUTO_INCREMENT UNIQUE,
  code_ape    varchar(5) PRIMARY KEY,
  libelle     varchar(255),
  active      tinyint default 1
)type=innodb;

insert into llx_c_ape (code_ape,libelle) values
('721Z','Conseil en systèmes informatiques');
insert into llx_c_ape (code_ape,libelle) values
('722A','Edition de logiciels (non personnalisés)');
insert into llx_c_ape (code_ape,libelle) values
('722C','Autres activités de réalisation de logiciels');
insert into llx_c_ape (code_ape,libelle) values
('723Z','Traitement de données');
insert into llx_c_ape (code_ape,libelle) values
('724Z','Activités de banques de données');
insert into llx_c_ape (code_ape,libelle) values
('725Z','Entretien et réparation de machines de bureau et de matériel informatique');
insert into llx_c_ape (code_ape,libelle) values
('726Z','Autres activités rattachées à l\'informatique (L\'utilisation de cette classe est différée jusqu\'à nouvel avis)');
insert into llx_c_ape (code_ape,libelle) values
('731Z','Recherche-développement en sciences physiques et naturelles');
insert into llx_c_ape (code_ape,libelle) values
('732Z','Recherche-développement en sciences humaines et sociales');
