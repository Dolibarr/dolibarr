-- ========================================================================
-- $Id$
-- $Source$
-- ========================================================================

create table soc_events
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,  -- public id
  fk_soc        int          NOT NULL,            --
  dateb	        datetime    NOT NULL,            -- begin date
  datee	        datetime    NOT NULL,            -- end date
  title         varchar(100) NOT NULL,
  url           varchar(255),
  description   text
);
