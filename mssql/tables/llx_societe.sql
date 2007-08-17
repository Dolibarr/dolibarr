-- ========================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ========================================================================

create table llx_societe
(
  idp                int IDENTITY PRIMARY KEY,
  statut             tinyint        DEFAULT 0,            -- statut
  parent             int,
  tms                timestamp,
  datec	             datetime,                            -- creation date
  datea	             datetime,                            -- activation date
  nom                varchar(60),                         -- company name
  code_client        varchar(15),                         -- code client
  code_fournisseur   varchar(15),                         -- code founisseur
  code_compta        varchar(15),                         -- code compta client
  code_compta_fournisseur  varchar(15),                         -- code compta founisseur
  address            varchar(255),                        -- company adresse
  cp                 varchar(10),                         -- zipcode
  ville              varchar(50),                         -- town
  fk_departement     int        DEFAULT 0,            --
  fk_pays            int        DEFAULT 0,            --
  tel                varchar(20),                         -- phone number
  fax                varchar(20),                         -- fax number
  url                varchar(255),                        --
  email              varchar(128),                        --
  fk_secteur         int        DEFAULT 0,            --
  fk_effectif        int        DEFAULT 0,            --
  fk_typent          int        DEFAULT 0,            --
  fk_forme_juridique int        DEFAULT 0,            -- forme juridique INSEE
  siren	             varchar(16),                         -- IDProf1: siren ou RCS pour france
  siret              varchar(16),                         -- IDProf2: siret pour france
  ape                varchar(16),                         -- IDProf3: code ape pour france
  idprof4            varchar(16),                         -- IDProf4: nu pour france
  tva_intra          varchar(20),                         -- tva intracommunautaire
  capital            real,                                -- capital de la société
  description        text,                                --
  fk_stcomm          tinyint        DEFAULT 0,            -- commercial statut
  note               text,                                --
  services           tinyint        DEFAULT 0,            --
  prefix_comm        varchar(5),                          -- prefix commercial
  client             tinyint        DEFAULT 0,            -- client 0/1/2
  fournisseur        tinyint        DEFAULT 0,            -- fournisseur 0/1
  customer_bad       tinyint        DEFAULT 0,            -- mauvais payeur 0/1
  customer_rate      real           DEFAULT 0,            -- taux fiabilié client (0 à 1)
  supplier_rate      real           DEFAULT 0,            -- taux fiabilié fournisseur (0 à 1)
  rubrique           varchar(255),                        -- champ rubrique libre
  fk_user_creat      int,                             -- utilisateur qui a créé l'info
  fk_user_modif      int,                             -- utilisateur qui a modifié l'info
  remise_client      real           DEFAULT 0,            -- remise systématique pour le client
  mode_reglement     tinyint, 					          -- mode de réglement
  cond_reglement     tinyint, 							  -- condition de réglement
  tva_assuj          tinyint        DEFAULT 1	          -- assujéti ou non à la TVA
);
