-- ========================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_societe
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  statut             tinyint        DEFAULT 0,            -- statut
  parent             integer,
  tms                timestamp,
  datec	             datetime,                            -- creation date
  datea	             datetime,                            -- activation date
  nom                varchar(60),                         -- company name
  code_client        varchar(15),                         -- code client
  code_fournisseur   varchar(15),                         -- code founisseur
  code_compta        varchar(15),                         -- code compta client
  code_compta_fournisseur  varchar(15),                   -- code compta founisseur
  address            varchar(255),                        -- company adresse
  cp                 varchar(10),                         -- zipcode
  ville              varchar(50),                         -- town
  fk_departement     integer        DEFAULT 0,            --
  fk_pays            integer        DEFAULT 0,            --
  tel                varchar(20),                         -- phone number
  fax                varchar(20),                         -- fax number
  url                varchar(255),                        --
  email              varchar(128),                        --
  fk_secteur         integer        DEFAULT 0,            --
  fk_effectif        integer        DEFAULT 0,            --
  fk_typent          integer        DEFAULT 0,            --
  fk_forme_juridique integer        DEFAULT 0,            -- forme juridique INSEE
  siren	             varchar(16),                         -- IDProf1: siren ou RCS pour france
  siret              varchar(16),                         -- IDProf2: siret pour france
  ape                varchar(16),                         -- IDProf3: code ape pour france
  idprof4            varchar(16),                         -- IDProf4: nu pour france
  tva_intra          varchar(20),                         -- tva
  capital            real,                                -- capital de la société
  description        text,                                --
  fk_stcomm          smallint       DEFAULT 0,            -- commercial statut
  note               text,                                --
  services           tinyint        DEFAULT 0,            --
  prefix_comm        varchar(5),                          -- prefix commercial
  client             tinyint        DEFAULT 0,            -- client 0/1/2
  fournisseur        tinyint        DEFAULT 0,            -- fournisseur 0/1
  supplier_account   varchar(32),                         -- compte client chez un fournisseur
  fk_prospectlevel   varchar(12),                         -- prospect level (in llx_c_prospectlevel)
  customer_bad       tinyint        DEFAULT 0,            -- mauvais payeur 0/1
  customer_rate      real           DEFAULT 0,            -- taux fiabilié client (0 à 1)
  supplier_rate      real           DEFAULT 0,            -- taux fiabilié fournisseur (0 à 1)
  fk_user_creat      integer NULL,                        -- utilisateur qui a créé l'info
  fk_user_modif      integer,                             -- utilisateur qui a modifié l'info
  remise_client      real           DEFAULT 0,            -- remise systématique pour le client
  mode_reglement     tinyint, 					          -- mode de réglement
  cond_reglement     tinyint, 							  -- condition de réglement
  tva_assuj          tinyint        DEFAULT 1,	          -- assujeti ou non à la TVA
  gencod			 varchar(255),						  -- barcode
  price_level        tinyint(4) NULL					  -- level of price for multiprices
)type=innodb;
