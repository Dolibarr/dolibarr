-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ============================================================================

create table llx_newsletter
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec              datetime,
  tms                timestamp,
  email_subject      varchar(32) NOT NULL,
  email_from_name    varchar(255) NOT NULL,
  email_from_email   varchar(255) NOT NULL,
  email_replyto      varchar(255) NOT NULL,
  email_body         text,
  target             smallint,
  sql_target         text,
  status             smallint NOT NULL DEFAULT 0,
  date_send_request  datetime,   -- debut de l'envoi demandé
  date_send_begin    datetime,   -- debut de l'envoi
  date_send_end      datetime,   -- fin de l'envoi
  nbsent             integer,    -- nombre de mails envoyés
  nberror            integer,    -- nombre de mails envoyés
  fk_user_author     integer,
  fk_user_valid      integer,
  fk_user_modif      integer
);

