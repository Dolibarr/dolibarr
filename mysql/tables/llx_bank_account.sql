-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_bank_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  label          varchar(30),
  bank           varchar(255),
  number         varchar(255)
);
