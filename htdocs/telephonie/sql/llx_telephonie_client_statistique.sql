
create table llx_telephonie_client_statistique (
  dates          date,
  stat           varchar(15),
  nb             real,

  UNIQUE (dates, stat)

)type=innodb;

