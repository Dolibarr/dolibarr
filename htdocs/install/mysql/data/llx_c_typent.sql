-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Types of thirdparties
--

delete from llx_c_typent;
-- Entries for all countries
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  0, 'TE_UNKNOWN', '-',             NULL, 1);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  1, 'TE_STARTUP', 'Start-up',      NULL, 0);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  2, 'TE_GROUP',   'Grand groupe',  NULL, 1);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  3, 'TE_MEDIUM',  'PME/PMI',       NULL, 1);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  4, 'TE_SMALL',   'TPE',           NULL, 1);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  5, 'TE_ADMIN',   'Administration',NULL, 1);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  6, 'TE_WHOLE',   'Grossiste',     NULL, 0);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  7, 'TE_RETAIL',  'Revendeur',     NULL, 0);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (  8, 'TE_PRIVATE', 'Particulier',   NULL, 1);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (100, 'TE_OTHER',   'Autres',        NULL, 1);
-- Argentina (country 23)
insert into llx_c_typent (id,code,libelle,fk_country,active) values (231, 'TE_A_RI',    'Responsable Inscripto (typo A)',    23, 0);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (232, 'TE_B_RNI',   'Responsable No Inscripto (typo B)', 23, 0);
insert into llx_c_typent (id,code,libelle,fk_country,active) values (233, 'TE_C_FE',    'Consumidor Final/Exento (typo C)',  23, 0);
