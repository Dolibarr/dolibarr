-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
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
--
-- ===================================================================

create table llx_voyage
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  dateo           date,                    -- date operation
  date_depart     timestamp,                -- date du voyage
  date_arrivee    timestamp,                -- date du voyage
  amount          real NOT NULL default 0, -- prix du billet
  reduction       real NOT NULL default 0, -- montant de la reduction obtenue
  depart          varchar(255),
  arrivee         varchar(255),
  fk_type         smallint,                -- Train, Avion, Bateaux
  fk_reduc        integer,
  distance        integer,                 -- distance en kilometre
  dossier         varchar(50),             -- numero de dossier
  note            text
);

-- insert into llx_voyage (date_depart, date_arrivee, amount, depart, arrivee, fk_reduc) 
-- values ('2002-04-21 12:05','2002-04-21 15:25',26.8,'Paris','Auray',1);

-- insert into llx_voyage (date_depart, date_arrivee, amount, depart, arrivee, fk_reduc) 
-- values ('2002-04-23 15:42','2002-04-23 19:10',26.8,'Auray','Paris',1);
