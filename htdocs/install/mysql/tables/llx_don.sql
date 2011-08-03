-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_don.sql,v 1.6 2011/08/03 01:25:39 eldy Exp $
-- ===================================================================


create table llx_don
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(30) DEFAULT NULL,     -- Ref donation (TODO change to NOT NULL)
  entity          integer DEFAULT 1 NOT NULL,	-- multi company id
  tms             timestamp,
  fk_statut       smallint NOT NULL DEFAULT 0,  -- etat du don promesse/valid
  datec           datetime,                     -- date de creation de l'enregistrement
  datedon         datetime,                     -- date du don/promesse
  amount          real DEFAULT 0,
  fk_paiement     integer,
  prenom          varchar(50),
  nom             varchar(50),
  societe         varchar(50),
  adresse         text,
  cp              varchar(30),
  ville           varchar(50),
  pays            varchar(50),
  email           varchar(255),
  phone           varchar(24),
  phone_mobile    varchar(24),
  public          smallint DEFAULT 1 NOT NULL,   -- le don est-il public (0,1)
  fk_don_projet   integer NULL,                  -- projet auquel est fait le don
  fk_user_author  integer NOT NULL,
  fk_user_valid   integer NULL,
  note            text,
  note_public     text,
  model_pdf       varchar(255),
  import_key      varchar(14)
)ENGINE=innodb;
