--
--
create table llx_telephonie_client_stats (
  fk_client_comm   integer NOT NULL,      -- Client décideur
  ca               real,
  gain             real,

  UNIQUE INDEX(fk_client_comm)
)type=innodb;


ALTER TABLE llx_telephonie_client_stats ADD FOREIGN KEY (fk_client_comm) REFERENCES llx_societe(idp);
