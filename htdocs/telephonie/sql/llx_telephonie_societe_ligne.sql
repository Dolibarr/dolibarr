--
-- Statut des lignes
--
-- 0 a commander
-- 1 commandée
-- 2 recue
-- 3 probleme
--
create table llx_telephonie_societe_ligne (
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  datec            datetime,
  fk_client_comm   integer NOT NULL,      -- Client décideur
  fk_soc           integer NOT NULL,
  ligne            varchar(12) NOT NULL,
  fk_soc_facture   integer NOT NULL,
  statut           smallint DEFAULT 0,
  fk_fournisseur   integer NOT NULL,
  remise           real DEFAULT 0,
  note             text,
  fk_commercial    integer NOT NULL,
  fk_concurrent    integer DEFAULT 1 NOT NULL,
  fk_user_creat    integer,
  date_commande    datetime,
  fk_user_commande integer,
  isfacturable     enum('oui','non') DEFAULT 'oui',
  mode_paiement    enum('vir','pre') DEFAULT 'pre',

  code_analytique  varchar(12),

  UNIQUE INDEX(fk_soc, ligne)
)type=innodb;

ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_fournisseur);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_client_comm);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_soc);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_soc_facture);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_user_creat);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_user_commande);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_commercial);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_concurrent);

ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_fournisseur)   REFERENCES llx_telephonie_fournisseur (rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_client_comm)   REFERENCES llx_societe(idp);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_soc)           REFERENCES llx_societe(idp);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_soc_facture)   REFERENCES llx_societe(idp);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_user_creat)    REFERENCES llx_user(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_user_commande) REFERENCES llx_user(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_commercial) REFERENCES llx_user(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD FOREIGN KEY (fk_concurrent)   REFERENCES llx_telephonie_concurrents (rowid);



create table llx_telephonie_societe_ligne_statut (
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              datetime,
  fk_ligne         integer NOT NULL,
  statut           smallint NOT NULL,
  fk_user          integer,
  comment          varchar(255)

)type=innodb;

ALTER TABLE llx_telephonie_societe_ligne_statut ADD INDEX (fk_ligne);
ALTER TABLE llx_telephonie_societe_ligne_statut ADD INDEX (fk_user);

ALTER TABLE llx_telephonie_societe_ligne_statut ADD FOREIGN KEY (fk_ligne) REFERENCES llx_telephonie_societe_ligne(rowid);
ALTER TABLE llx_telephonie_societe_ligne_statut ADD FOREIGN KEY (fk_user) REFERENCES llx_user(rowid);
