-- ========================================================================
-- Copyright (C) 2000-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- SGBD : Mysql 3.23
--
-- $Id$
-- $Source$
-- ========================================================================
create table societe
(
  idp            integer AUTO_INCREMENT PRIMARY KEY,
  id             varchar(32),                         -- private id
  active         smallint       default 0,            --
  parent         integer        default 0,            --
  intern         bool           default 1 NOT NULL,   -- is an intern company
  cjn            bool           default 1 NOT NULL,   -- is allowed to export to cjn
  ssii           bool           default 0 NOT NULL,   --
  datec	         datetime,                            -- creation date
  datem	         datetime,                            -- modification date
  datea	         datetime,                            -- activation date
  datel          datetime,                            -- last login date
  nom            varchar(60),                         -- company name
  address        varchar(255),                        -- company adresse
  cp             varchar(10),                         -- zipcode
  ville          varchar(50),                         -- town
  fk_pays        integer        default 0,            --
  tel            varchar(20),                         -- phone number
  fax            varchar(20),                         -- fax number
  url            varchar(255),                        --
  fk_secteur     integer        default 0,            --
  fk_effectif    integer        default 0,            --
  fk_typent      integer        default 0,            --
  siren	         varchar(9),                          --
  tchoozeid      integer        default 0,            --
  c_nom	         varchar(40),                         --
  c_prenom       varchar(40),                         --
  c_tel          varchar(20),                         --
  c_mail         varchar(80),                         --
  description    text,                                --
  viewed         integer        default 0,            --
  formatcv       varchar(50),                         -- 
  alias	         varchar(50),                         -- alias unix name for rewrite 
  fplus          bool           default 0 NOT NULL ,  -- flag fiche plus
  logo           varchar(255),                        --
  pubkey         varchar(32),                         --
  caddie         integer        default 0,            --
  karma	         integer        default 0,            --
  off_acc        smallint       default 0,            -- offers accepted
  off_ref        smallint       default 0,            -- offers refused
  fk_stcomm      smallint       default 0,            -- commercial statut
  note           text,                                --
  newsletter     bool           default 1,            -- newsletter on or off
  view_res_coord bool           default 0,            -- view resume personnal info
  cabrecrut      bool           default 0,            -- Cabinet de recrutement
  services       integer        default 0,            --
  reminder       integer        default 1,            --
  prefix_comm    varchar(5),                          -- prefix commercial

  UNIQUE INDEX(prefix_comm)
);
