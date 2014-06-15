--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.6.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):   VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres) VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE

-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_societe DROP COLUMN datea;

ALTER TABLE llx_holiday ADD COLUMN fk_user_create integer;
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_user_create (fk_user_create);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_create (date_create);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_validator (fk_validator);

create table llx_c_email_templates
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  type_template   varchar(32),  -- template for wich type of email (send invoice by email, send order, ...)
  datec           datetime,
  label           varchar(255),
  content         text
)ENGINE=innodb;


ALTER TABLE llx_bank_account MODIFY COLUMN account_number varchar(24);


-- delete foreign key that should never exists
ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_currency;
ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_currency;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_currency;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_societe_fk_currency;

ALTER TABLE llx_propal MODIFY COLUMN fk_currency varchar(3) NULL;
ALTER TABLE llx_commande MODIFY COLUMN fk_currency varchar(3) NULL;
ALTER TABLE llx_facture MODIFY COLUMN fk_currency varchar(3) NULL;
ALTER TABLE llx_societe MODIFY COLUMN fk_currency varchar(3) NULL;

ALTER TABLE llx_bookmark ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_bookmark MODIFY COLUMN url varchar(255) NOT NULL;

ALTER TABLE llx_opensurvey_sondage ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_comments tinyint NOT NULL DEFAULT 1;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN survey_link_visible;
-- ALTER TABLE llx_opensurvey_sondage DROP INDEX idx_id_sondage_admin;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN id_sondage_admin;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN canedit;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_spy tinyint NOT NULL DEFAULT 1 AFTER allow_comments;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN origin;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN fk_user_creat integer AFTER nom_admin;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN mailsonde mailsonde tinyint NOT NULL DEFAULT 0;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN titre titre TEXT NOT NULL;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN date_fin date_fin DATETIME NOT NULL;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN format format VARCHAR(2) NOT NULL;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN sujet TEXT;

ALTER TABLE llx_facture_rec CHANGE COLUMN usenewprice usenewprice INTEGER DEFAULT 0;

-- Uniformize index name to match http://wiki.dolibarr.org/index.php/Language_and_development_rules#SQL_rules
ALTER TABLE llx_c_type_contact DROP index idx_c_type_contact_uk;
ALTER TABLE llx_c_type_contact ADD UNIQUE INDEX uk_c_type_contact_id (element, source, code);
ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (fk_pays, taux, recuperableonly);

ALTER TABLE llx_accountingaccount MODIFY COLUMN label varchar(255);

-- Plan comptable BE PCMN-BASE
INSERT INTO llx_accounting_system (pcg_version, fk_pays, label, active) VALUES ('PCMN-BASE', '2', 'The base accountancy belgium plan', '1');

INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '10', '1', 'Capital', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '100', '10', 'Capital souscrit ou capital personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1000', '100', 'Capital non amorti', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1001', '100', 'Capital amorti', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '101', '10', 'Capital non appelé', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '109', '10', 'Compte de l''exploitant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1090', '109', 'Opérations courantes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1091', '109', 'Impôts personnels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1092', '109', 'Rémunérations et autres avantages', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '11', '1', 'Primes d''émission', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '12', '1', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '120', '12', 'Plus-values de réévaluation sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1200', '120', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1201', '120', 'Reprises de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '121', '12', 'Plus-values de réévaluation sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1210', '121', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1211', '121', 'Reprises de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '122', '12', 'Plus-values de réévaluation sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1220', '122', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1221', '122', 'Reprises de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '123', '12', 'Plus-values de réévaluation sur stocks', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '124', '12', 'Reprises de réductions de valeur sur placements de trésorerie', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '13', '1', 'Réserve', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '130', '13', 'Réserve légale', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '131', '13', 'Réserves indisponibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1310', '131', 'Réserve pour actions propres', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1311', '131', 'Autres réserves indisponibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '132', '13', 'Réserves immunisées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '133', '13', 'Réserves disponibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1330', '133', 'Réserve pour régularisation de dividendes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1331', '133', 'Réserve pour renouvellement des immobilisations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1332', '133', 'Réserve pour installations en faveur du personnel 1333 Réserves libres', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '14', '1', 'Bénéfice reporté (ou perte reportée)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '15', '1', 'Subsides en capital', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '150', '15', 'Montants obtenus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '151', '15', 'Montants transférés aux résultats', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '16', '1', 'Provisions pour risques et charges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '160', '16', 'Provisions pour pensions et obligations similaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '161', '16', 'Provisions pour charges fiscales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '162', '16', 'Provisions pour grosses réparations et gros entretiens', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '163', '16', 'à 169 Provisions pour autres risques et charges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '164', '16', 'Provisions pour sûretés personnelles ou réelles constituées à l''appui de dettes et d''engagements de tiers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '165', '16', 'Provisions pour engagements relatifs à l''acquisition ou à la cession d''immobilisations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '166', '16', 'Provisions pour exécution de commandes passées ou reçues', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '167', '16', 'Provisions pour positions et marchés à terme en devises ou positions et marchés à terme en marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '168', '16', 'Provisions pour garanties techniques attachées aux ventes et prestations déjà effectuées par l''entreprise', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '169', '16', 'Provisions pour autres risques et charges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1690', '169', 'Pour litiges en cours', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1691', '169', 'Pour amendes, doubles droits et pénalités', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1692', '169', 'Pour propre assureur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1693', '169', 'Pour risques inhérents aux opérations de crédits à moyen ou long terme', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1695', '169', 'Provision pour charge de liquidation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1696', '169', 'Provision pour départ de personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1699', '169', 'Pour risques divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17', '1', 'Dettes à plus d''un an', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '170', '17', 'Emprunts subordonnés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1700', '170', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1701', '170', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '171', '17', 'Emprunts obligataires non subordonnés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1710', '171', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1711', '171', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '172', '17', 'Dettes de location-financement et assimilés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1720', '172', 'Dettes de location-financement de biens immobiliers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1721', '172', 'Dettes de location-financement de biens mobiliers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1722', '172', 'Dettes sur droits réels sur immeubles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '173', '17', 'Etablissements de crédit', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1730', '173', 'Dettes en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17300', '1730', 'Banque A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17301', '1730', 'Banque B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17302', '1730', 'Banque C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17303', '1730', 'Banque D', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1731', '173', 'Promesses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17310', '1731', 'Banque A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17311', '1731', 'Banque B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17312', '1731', 'Banque C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17313', '1731', 'Banque D', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1732', '173', 'Crédits d''acceptation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17320', '1732', 'Banque A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17321', '1732', 'Banque B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17322', '1732', 'Banque C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17323', '1732', 'Banque D', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '174', '17', 'Autres emprunts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175', '17', 'Dettes commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1750', '175', 'Fournisseurs : dettes en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17500', '1750', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175000', '17500', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175001', '17500', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17501', '1750', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175010', '17501', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175011', '17501', 'Fournisseurs C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175012', '17501', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1751', '175', 'Effets à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17510', '1751', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175100', '17510', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175101', '17510', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '17511', '1751', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175110', '17511', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175111', '17511', 'Fournisseurs C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '175112', '17511', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '176', '17', 'Acomptes reçus sur commandes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '178', '17', 'Cautionnements reçus en numéraires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '179', '17', 'Dettes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1790', '179', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1791', '179', 'Autres entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1792', '179', 'Administrateurs, gérants et associés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1794', '179', 'Rentes viagères capitalisées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1798', '179', 'Dettes envers les coparticipants des associations momentanées et en participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '1799', '179', 'Autres dettes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CAPIT', 'XXXXXX', '18', '1', 'Comptes de liaison des établissements et succursales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '20', '2', 'Frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '200', '20', 'Frais de constitution et d''augmentation de capital', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2000', '200', 'Frais de constitution et d''augmentation de capital', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2009', '200', 'Amortissements sur frais de constitution et d''augmentation de capital', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '201', '20', 'Frais d''émission d''emprunts et primes de remboursement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2010', '201', 'Agios sur emprunts et frais d''émission d''emprunts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2019', '201', 'Amortissements sur agios sur emprunts et frais d''émission d''emprunts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '202', '20', 'Autres frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2020', '202', 'Autres frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2029', '202', 'Amortissements sur autres frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '203', '20', 'Intérêts intercalaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2030', '203', 'Intérêts intercalaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2039', '203', 'Amortissements sur intérêts intercalaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '204', '20', 'Frais de restructuration', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2040', '204', 'Coût des frais de restructuration', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2049', '204', 'Amortissements sur frais de restructuration', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '21', '2', 'Immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '210', '21', 'Frais de recherche et de développement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2100', '210', 'Frais de recherche et de mise au point', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2108', '210', 'Plus-values actées sur frais de recherche et de mise au point', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2109', '210', 'Amortissements sur frais de recherche et de mise au point', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '211', '21', 'Concessions, brevets, licences, savoir-faire, marque et droits similaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2110', '211', 'Concessions, brevets, licences, marques, etc', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2118', '211', 'Plus-values actées sur concessions, etc', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2119', '211', 'Amortissements sur concessions, etc', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '212', '21', 'Goodwill', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2120', '212', 'Coût d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2128', '212', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2129', '212', 'Amortissements sur goodwill', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '213', '21', 'Acomptes versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22', '2', 'Terrains et constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '220', '22', 'Terrains', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2200', '220', 'Terrains', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2201', '220', 'Frais d''acquisition sur terrains', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2208', '220', 'Plus-values actées sur terrains', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2209', '220', 'Amortissements et réductions de valeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22090', '2209', 'Amortissements sur frais d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22091', '2209', 'Réductions de valeur sur terrains', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '221', '22', 'Constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2210', '221', 'Bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2211', '221', 'Bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2212', '221', 'Autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2213', '221', 'Voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2215', '221', 'Constructions sur sol d''autrui', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2216', '221', 'Frais d''acquisition sur constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2218', '221', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22180', '2218', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22181', '2218', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22182', '2218', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22184', '2218', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2219', '221', 'Amortissements sur constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22190', '2219', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22191', '2219', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22192', '2219', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22194', '2219', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22195', '2219', 'Sur constructions sur sol d''autrui', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22196', '2219', 'Sur frais d''acquisition sur constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '222', '22', 'Terrains bâtis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2220', '222', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22200', '2220', 'Bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22201', '2220', 'Bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22202', '2220', 'Autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22203', '2220', 'Voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22204', '2220', 'Frais d''acquisition des terrains à bâtir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2228', '222', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22280', '2228', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22281', '2228', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22282', '2228', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22283', '2228', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2229', '222', 'Amortissements sur terrains bâtis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22290', '2229', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22291', '2229', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22292', '2229', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22293', '2229', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '22294', '2229', 'Sur frais d''acquisition des terrains bâtis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '223', '22', 'Autres droits réels sur des immeubles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2230', '223', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2238', '223', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2239', '223', 'Amortissements', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '23', '2', 'Installations, machines et outillages', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '230', '23', 'Installations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2300', '230', 'Installations bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2301', '230', 'Installations bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2302', '230', 'Installations bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2303', '230', 'Installations voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2300', '230', 'Installation d''eau', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2301', '230', 'Installation d''électricité', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2302', '230', 'Installation de vapeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2303', '230', 'Installation de gaz', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2304', '230', 'Installation de chauffage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2305', '230', 'Installation de conditionnement d''air', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2306', '230', 'Installation de chargement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '231', '23', 'Machines', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2310', '231', 'Division A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2311', '231', 'Division B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2312', '231', 'Division C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '237', '23', 'Outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2370', '237', 'Division A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2371', '237', 'Division B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2372', '237', 'Division C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '238', '23', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2380', '238', 'Sur installations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2381', '238', 'Sur machines', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2382', '238', 'Sur outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '239', '23', 'Amortissements', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2390', '239', 'Sur installations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2391', '239', 'Sur machines', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2392', '239', 'Sur outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24', '2', 'Mobilier et matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '240', '24', 'Mobilier', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2400', '240', 'Mobilier', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24000', '2400', 'Mobilier des bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24001', '2400', 'Mobilier des bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24002', '2400', 'Mobilier des autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24003', '2400', 'Mobilier oeuvres sociales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2401', '240', 'Matériel de bureau et de service social', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24010', '2401', 'Des bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24011', '2401', 'Des bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24012', '2401', 'Des autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24013', '2401', 'Des oeuvres sociales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2408', '240', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24080', '2408', 'Plus-values actées sur mobilier', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24081', '2408', 'Plus-values actées sur matériel de bureau et service social', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2409', '240', 'Amortissements', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24090', '2409', 'Amortissements sur mobilier', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24091', '2409', 'Amortissements sur matériel de bureau et service social', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '241', '24', 'Matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2410', '241', 'Matériel automobile', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24100', '2410', 'Voitures', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24105', '2410', 'Camions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2411', '241', 'Matériel ferroviaire', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2412', '241', 'Matériel fluvial', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2413', '241', 'Matériel naval', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2414', '241', 'Matériel aérien', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2418', '241', 'Plus-values sur matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24180', '2418', 'Plus-values sur matériel automobile', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24181', '2418', 'Idem sur matériel ferroviaire', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24182', '2418', 'Idem sur matériel fluvial', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24183', '2418', 'Idem sur matériel naval', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24184', '2418', 'Idem sur matériel aérien', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2419', '241', 'Amortissements sur matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24190', '2419', 'Amortissements sur matériel automobile', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24191', '2419', 'Idem sur matériel ferroviaire', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24192', '2419', 'Idem sur matériel fluvial', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24193', '2419', 'Idem sur matériel naval', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '24194', '2419', 'Idem sur matériel aérien', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '25', '2', 'Immobilisation détenues en location-financement et droits similaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '250', '25', 'Terrains et constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2500', '250', 'Terrains', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2501', '250', 'Constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2508', '250', 'Plus-values sur emphytéose,  leasing et droits similaires : terrains et constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2509', '250', 'Amortissements et réductions de valeur sur terrains et constructions en leasing', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '(', '25', 'Installations,  machines et outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2510', '251', 'Installations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2511', '251', 'Machines', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2512', '251', 'Outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2518', '251', 'Plus-values actées sur installations machines et outillage pris en leasing', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2519', '251', 'Amortissements sur installations machines et outillage pris en leasing', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '252', '25', 'Mobilier et matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2520', '252', 'Mobilier', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2521', '252', 'Matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2528', '252', 'Plus-values actées sur mobilier et matériel roulant en leasing', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2529', '252', 'Amortissements sur mobilier et matériel roulant en leasing', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '26', '2', 'Autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '260', '26', 'Frais d''aménagements de locaux pris en location', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '261', '26', 'Maison d''habitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '262', '26', 'Réserve immobilière', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '263', '26', 'Matériel d''emballage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '264', '26', 'Emballages récupérables', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '268', '26', 'Plus-values actées sur autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '269', '26', 'Amortissements sur autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2690', '269', 'Amortissements sur frais d''aménagement des locaux pris en location', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2691', '269', 'Amortissements sur maison d''habitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2692', '269', 'Amortissements sur réserve immobilière', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2693', '269', 'Amortissements sur matériel d''emballage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2694', '269', 'Amortissements sur emballages récupérables', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '27', '2', 'Immobilisations corporelles en cours et acomptes versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '270', '27', 'Immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2700', '270', 'Constructions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2701', '270', 'Installations machines et outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2702', '270', 'Mobilier et matériel roulant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2703', '270', 'Autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '271', '27', 'Avances et acomptes versés sur immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '28', '2', 'Immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '280', '28', 'Participations dans des entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2800', '280', 'Valeur d''acquisition (peut être subdivisé par participation)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2801', '280', 'Montants non appelés (idem)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2808', '280', 'Plus-values actées (idem)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2809', '280', 'Réductions de valeurs actées (idem)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '281', '28', 'Créances sur des entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2810', '281', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2811', '281', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2812', '281', 'Titres à revenu fixes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2817', '281', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2819', '281', 'Réductions de valeurs actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '282', '28', 'Participations dans des entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2820', '282', 'Valeur d''acquisition (peut être subdivisé par participation)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2821', '282', 'Montants non appelés (idem)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2828', '282', 'Plus-values actées (idem)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2829', '282', 'Réductions de valeurs actées (idem)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '283', '28', 'Créances sur des entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2830', '283', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2831', '283', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2832', '283', 'Titres à revenu fixe', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2837', '283', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2839', '283', 'Réductions de valeurs actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '284', '28', 'Autres actions et parts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2840', '284', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2841', '284', 'Montants non appelés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2848', '284', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2849', '284', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '285', '28', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2850', '285', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2851', '285', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2852', '285', 'Titres à revenu fixe', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2857', '285', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2859', '285', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '288', '28', 'Cautionnements versés en numéraires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2880', '288', 'Téléphone, téléfax, télex', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2881', '288', 'Gaz', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2882', '288', 'Eau', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2883', '288', 'Electricité', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2887', '288', 'Autres cautionnements versés en numéraires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29', '2', 'Créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '290', '29', 'Créances commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2900', '290', 'Clients', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29000', '2900', 'Créances en compte sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29001', '2900', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29002', '2900', 'Sur clients Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29003', '2900', 'Sur clients C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29004', '2900', 'Sur clients exportation hors C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29005', '2900', 'Créances sur les coparticipants (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2901', '290', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29010', '2901', 'Sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29011', '2901', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29012', '2901', 'Sur clients Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29013', '2901', 'Sur clients C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29014', '2901', 'Sur clients exportation hors C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2905', '290', 'Retenues sur garanties', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2906', '290', 'Acomptes versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2907', '290', 'Créances douteuses (à ventiler comme clients 2900)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2909', '290', 'Réductions de valeur actées (à ventiler comme clients 2900)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '291', '29', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2910', '291', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29100', '2910', 'Sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29101', '2910', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29102', '2910', 'Sur autres débiteurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2911', '291', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29110', '2911', 'Sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29111', '2911', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '29112', '2911', 'Sur autres débiteurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2912', '291', 'Créances résultant de la cession d''immobilisations données en leasing', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2917', '291', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'IMMO', 'XXXXXX', '2919', '291', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '30', '3', 'Approvisionnements - matières premières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '300', '30', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '309', '30', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '31', '3', 'Approvsionnements et fournitures', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '310', '31', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3100', '310', 'Matières d''approvisionnement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3101', '310', 'Energie, charbon, coke, mazout, essence, propane', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3102', '310', 'Produits d''entretien', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3103', '310', 'Fournitures diverses et petit outillage', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3104', '310', 'Imprimés et fournitures de bureau', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3105', '310', 'Fournitures de services sociaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3106', '310', 'Emballages commerciaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '31060', '3106', 'Emballages perdus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '31061', '3106', 'Emballages récupérables', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '319', '31', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '32', '3', 'En cours de fabrication', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '320', '32', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3200', '320', 'Produits semi-ouvrés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3201', '320', 'Produits en cours de fabrication', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3202', '320', 'Travaux en cours', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3205', '320', 'Déchets', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3206', '320', 'Rebuts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3209', '320', 'Travaux en association momentanée', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '329', '32', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '33', '3', 'Produits finis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '330', '33', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3300', '330', 'Produits finis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '339', '33', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '34', '3', 'Marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '340', '34', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3400', '340', 'Groupe A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3401', '340', 'Groupe B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3402', '340', 'Groupe C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '349', '34', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '35', '3', 'Immeubles destinés à la vente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '350', '35', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3500', '350', 'Immeuble A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3501', '350', 'Immeuble B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3502', '350', 'Immeuble C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '351', '35', 'Immeubles construits en vue de leur revente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3510', '351', 'Immeuble A', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3511', '351', 'Immeuble B', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '3512', '351', 'Immeuble C', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '359', '35', 'Réductions de valeurs actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '36', '3', 'Acomptes versés sur achats pour stocks', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '360', '36', 'Acomptes versés (à ventiler éventuellement par catégorie)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '369', '36', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '37', '3', 'Commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '370', '37', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '371', '37', 'Bénéfice pris en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'STOCK', 'XXXXXX', '379', '37', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '40', '4', 'Créances commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '400', '40', 'Clients', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4007', '400', 'Rabais, remises et  ristournes à accorder et autres notes de crédit à établir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4008', '400', 'Créances résultant de livraisons de biens (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '401', '40', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4010', '401', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4013', '401', 'Effets à l''encaissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4015', '401', 'Effets à l''escompte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '402', '40', 'Clients, créances courantes, entreprises apparentées, administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4020', '402', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4021', '402', 'Autres entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4022', '402', 'Administrateurs et gérants d''entreprise', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '403', '40', 'Effets à recevoir sur entreprises apparentées et administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4030', '403', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4031', '403', 'Autres entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4032', '403', 'Administrateurs et gérants de l''entreprise', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '404', '40', 'Produits à recevoir (factures à établir)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '405', '40', 'Clients : retenues sur garanties', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '406', '40', 'Acomptes versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '407', '40', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '408', '40', 'Compensation clients', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '409', '40', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '41', '4', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '410', '41', 'Capital appelé, non versé', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4100', '410', 'Appels de fonds', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4101', '410', 'Actionnaires défaillants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '411', '41', 'T.V.A. à récupérer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4110', '411', 'T.V.A. due', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4111', '411', 'T.V.A. déductible', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4112', '411', 'Compte courant administration T.V.A.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4118', '411', 'Taxe d''égalisation due', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '412', '41', 'Impôts et versements fiscaux à récupérer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4120', '412', 'Impôts belges sur le résultat', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4125', '412', 'Autres impôts belges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4128', '412', 'Impôts étrangers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '414', '41', 'Produits à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '416', '41', 'Créances diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4160', '416', 'Associés (compte d''apport en société)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4161', '416', 'Avances et prêts au personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4162', '416', 'Compte courant des associés en S.P.R.L.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4163', '416', 'Compte courant des administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4164', '416', 'Créances sur sociétés apparentées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4166', '416', 'Emballages et matériel à rendre', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4167', '416', 'Etat et établissements publics', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '41670', '4167', 'Subsides à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '41671', '4167', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4168', '416', 'Rabais, ristournes et remises à obtenir et autres avoirs non encore reçus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '417', '41', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '418', '41', 'Cautionnements versés en numéraires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '419', '41', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '42', '4', 'Dettes à plus d''un an échéant dans l''année', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '420', '42', 'Emprunts subordonnés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4200', '420', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4201', '420', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '421', '42', 'Emprunts obligataires non subordonnés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4210', '421', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4211', '421', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '422', '42', 'Dettes de location-financement et assimilées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4220', '422', 'Financement de biens immobiliers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4221', '422', 'Financement de biens mobiliers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '423', '42', 'Etablissements de crédit', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4230', '423', 'Dettes en compte', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4231', '423', 'Promesses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4232', '423', 'Crédits d''acceptation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '424', '42', 'Autres emprunts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '425', '42', 'Dettes commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4250', '425', 'Fournisseurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4251', '425', 'Effets à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '426', '42', 'Cautionnements reçus en numéraires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '429', '42', 'Dettes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4290', '429', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4291', '429', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4292', '429', 'Administrateurs, gérants, associés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4299', '429', 'Autres dettes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '43', '4', 'Dettes financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '430', '43', 'Etablissements de crédit. Emprunts en compte à terme fixe', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '431', '43', 'Etablissements de crédit. Promesses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '432', '43', 'Etablissements de crédit. Crédits d''acceptation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '433', '43', 'Etablissements de crédit. Dettes en compte courant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '439', '43', 'Autres emprunts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44', '4', 'Dettes commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '440', '44', 'Fournisseurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4400', '440', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44000', '4400', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44001', '4400', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4401', '440', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44010', '4401', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44011', '4401', 'Fournisseurs CEE', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44012', '4401', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4402', '440', 'Dettes envers les coparticipants (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4403', '440', 'Fournisseurs - retenues de garanties', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '441', '44', 'Effets à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4410', '441', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44100', '4410', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44101', '4410', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4411', '441', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44110', '4411', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44111', '4411', 'Fournisseurs CEE', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '44112', '4411', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '444', '44', 'Factures à recevoir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '446', '44', 'Acomptes reçus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '448', '44', 'Compensations fournisseurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45', '4', 'Dettes fiscales, salariales et sociales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '450', '45', 'Dettes fiscales estimées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4501', '450', 'Impôts sur le résultat', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4505', '450', 'Autres impôts en Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4508', '450', 'Impôts à l''étranger', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '451', '45', 'T.V.A. à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4510', '451', 'T.V.A. due', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4511', '451', 'T.V.A. déductible', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4512', '451', 'Compte courant administration T.V.A.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4518', '451', 'Taxe d''égalisation due', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '452', '45', 'Impôts et taxes à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4520', '452', 'Autres impôts sur le résultat', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4525', '452', 'Autres impôts et taxes en Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45250', '4525', 'Précompte immobilier', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45251', '4525', 'Impôts communaux à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45252', '4525', 'Impôts provinciaux à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45253', '4525', 'Autres impôts et taxes à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4528', '452', 'Impôts et taxes à l''étranger', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '453', '45', 'Précomptes retenus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4530', '453', 'Précompte professionnel retenu sur rémunérations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4531', '453', 'Précompte professionnel retenu sur tantièmes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4532', '453', 'Précompte mobilier retenu sur dividendes attribués', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4533', '453', 'Précompte mobilier retenu sur intérêts payés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4538', '453', 'Autres précomptes retenus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '454', '45', 'Office National de la Sécurité Sociale', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4540', '454', 'Arriérés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4541', '454', '1er trimestre', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4542', '454', '2ème trimestre', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4543', '454', '3ème trimestre', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4544', '454', '4ème trimestre', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '455', '45', 'Rémunérations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4550', '455', 'Administrateurs,  gérants et commissaires (non réviseurs)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4551', '455', 'Direction', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4552', '455', 'Employés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4553', '455', 'Ouvriers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '456', '45', 'Pécules de vacances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4560', '456', 'Direction', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4561', '456', 'Employés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4562', '456', 'Ouvriers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '459', '45', 'Autres dettes sociales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4590', '459', 'Provision pour gratifications de fin d''année', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4591', '459', 'Départs de personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4592', '459', 'Oppositions sur rémunérations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4593', '459', 'Assurances relatives au personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45930', '4593', 'Assurance loi', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45931', '4593', 'Assurance salaire garanti', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45932', '4593', 'Assurance groupe', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '45933', '4593', 'Assurances individuelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4594', '459', 'Caisse d''assurances sociales pour travailleurs indépendants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4597', '459', 'Dettes et provisions sociales diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '46', '4', 'Acomptes reçus sur commande', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '47', '4', 'Dettes découlant de l''affectation des résultats', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '470', '47', 'Dividendes et tantièmes d''exercices antérieurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '471', '47', 'Dividendes de l''exercice', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '472', '47', 'Tantièmes de l''exercice', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '473', '47', 'Autres allocataires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '48', '4', 'Dettes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '480', '48', 'Obligations et coupons échus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '481', '48', 'Actionnaires - capital à rembourser', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '482', '48', 'Participation du personnel à payer', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '483', '48', 'Acomptes reçus d''autres tiers à moins d''un an', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '486', '48', 'Emballages et matériel consignés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '488', '48', 'Cautionnements reçus en numéraires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '489', '48', 'Autres dettes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '49', '4', 'Comptes de régularisation et compte d''attente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '490', '49', 'Charges à reporter (à subdiviser par catégorie de charges)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '491', '49', 'Produits acquis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4910', '491', 'Produits d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '49100', '4910', 'Ristournes et rabais à obtenir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '49101', '4910', 'Commissions à obtenir', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '49102', '4910', 'Autres produits d''exploitation (redevances par exemple)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4911', '491', 'Produits financiers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '49110', '4911', 'Intérêts courus et non échus sur prêts et débits', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '49111', '4911', 'Autres produits financiers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '492', '49', 'Charges à imputer (à subdiviser par catégorie de charges)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '493', '49', 'Produits à reporter', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4930', '493', 'Produits d''exploitation à reporter', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4931', '493', 'Produits financiers à reporter', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '499', '49', 'Comptes d''attente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4990', '499', 'Compte d''attente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4991', '499', 'Compte de répartition périodique des charges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'TIERS', 'XXXXXX', '4999', '499', 'Transferts d''exercice', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '50', '5', 'Actions propres', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '51', '5', 'Actions et parts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '510', '51', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '511', '51', 'Montants non appelés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '519', '51', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '52', '5', 'Titres à revenus fixes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '520', '52', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '529', '52', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '53', '5', 'Dépots à terme', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '530', '53', 'De plus d''un an', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '531', '53', 'De plus d''un mois et à un an au plus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '532', '53', 'd''un mois au plus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '539', '53', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '54', '5', 'Valeurs échues à l''encaissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '540', '54', 'Chèques à encaisser', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '541', '54', 'Coupons à encaisser', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '55', '5', 'Etablissements de crédit - Comptes ouverts auprès des divers établissements.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '550', '55', 'Comptes courants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '551', '55', 'Chèques émis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '559', '55', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '56', '5', 'Office des chèques postaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '560', '56', 'Compte courant', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '561', '56', 'Chèques émis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '57', '5', 'Caisses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '570', '57', 'à 577 Caisses - espèces ( 0 - centrale ; 7 - succursales et agences)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '578', '57', 'Caisses - timbres ( 0 - fiscaux ; 1 - postaux)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'FINAN', 'XXXXXX', '58', '5', 'Virements internes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '60', '6', 'Approvisionnements et marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '600', '60', 'Achats de matières premières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '601', '60', 'Achats de fournitures', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '602', '60', 'Achats de services, travaux et études', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '603', '60', 'Sous-traitances générales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '604', '60', 'Achats de marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '605', '60', 'Achats d''immeubles destinés à la revente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '608', '60', 'Remises , ristournes et rabais obtenus sur achats', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '609', '60', 'Variations de stocks', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6090', '609', 'De matières premières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6091', '609', 'De fournitures', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6094', '609', 'De marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6095', '609', 'd''immeubles destinés à la vente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61', '6', 'Services et biens divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '610', '61', 'Loyers et charges locatives', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6100', '610', 'Loyers divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6101', '610', 'Charges locatives (assurances, frais de confort,etc)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '611', '61', 'Entretien et réparation (fournitures et prestations)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '612', '61', 'Fournitures faites à l''entreprise', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6120', '612', 'Eau, gaz, électricité, vapeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61200', '6120', 'Eau', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61201', '6120', 'Gaz', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61202', '6120', 'Electricité', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61203', '6120', 'Vapeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6121', '612', 'Téléphone, télégrammes, télex, téléfax, frais postaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61210', '6121', 'Téléphone', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61211', '6121', 'Télégrammes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61212', '6121', 'Télex et téléfax', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61213', '6121', 'Frais postaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6122', '612', 'Livres, bibliothèque', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6123', '612', 'Imprimés et fournitures de bureau (si non comptabilisé au 601)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '613', '61', 'Rétributions de tiers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6130', '613', 'Redevances et royalties', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61300', '6130', 'Redevances pour brevets, licences, marques et accessoires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61301', '6130', 'Autres redevances (procédés de fabrication)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6131', '613', 'Assurances non relatives au personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61310', '6131', 'Assurance incendie', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61311', '6131', 'Assurance vol', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61312', '6131', 'Assurance autos', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61313', '6131', 'Assurance crédit', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61314', '6131', 'Assurances frais généraux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6132', '613', 'Divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61320', '6132', 'Commissions aux tiers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61321', '6132', 'Honoraires d''avocats, d''experts, etc', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61322', '6132', 'Cotisations aux groupements professionnels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61323', '6132', 'Dons, libéralités, etc', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61324', '6132', 'Frais de contentieux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61325', '6132', 'Publications légales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6133', '613', 'Transports et déplacements', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61330', '6133', 'Transports de personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61331', '6133', 'Voyages, déplacements et représentations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6134', '613', 'Personnel intérimaire', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '614', '61', 'Annonces, publicité, propagande et documentation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6140', '614', 'Annonces et insertions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6141', '614', 'Catalogues et imprimés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6142', '614', 'Echantillons', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6143', '614', 'Foires et expositions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6144', '614', 'Primes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6145', '614', 'Cadeaux à la clientèle', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6146', '614', 'Missions et réceptions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6147', '614', 'Documentation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '615', '61', 'Sous-traitants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6150', '615', 'Sous-traitants pour activités propres', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6151', '615', 'Sous-traitants d''associations momentanées (coparticipants)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6152', '615', 'Quote-part bénéficiaire des coparticipants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61700', '6170', 'Personnel intérimaire et personnes mises à la disposition de l''entreprise', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '61800', '6180', 'Rémunérations, primes pour assurances extralégales, pensions de retraite et de survie des administrateurs, gérants et associés actifs qui ne sont pas attribuées en vertu d''un contrat de travail', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62', '6', 'Rémunérations, charges sociales et pensions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '620', '62', 'Rémunérations et avantages sociaux directs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6200', '620', 'Administrateurs ou gérants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6201', '620', 'Personnel de direction', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6202', '620', 'Employés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6203', '620', 'Ouvriers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6204', '620', 'Autres membres du personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '621', '62', 'Cotisations patronales d''assurances sociales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6210', '621', 'Sur salaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6211', '621', 'Sur appointements et commissions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '622', '62', 'Primes patronales pour assurances extralégales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '623', '62', 'Autres frais de personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6230', '623', 'Assurances du personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62300', '6230', 'Assurances loi, responsabilité civile, chemin du travail', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62301', '6230', 'Assurance salaire garanti', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62302', '6230', 'Assurances individuelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6231', '623', 'Charges sociales diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62310', '6231', 'Jours fériés payés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62311', '6231', 'Salaire hebdomadaire garanti', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62312', '6231', 'Allocations familiales complémentaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6232', '623', 'Charges sociales des administrateurs, gérants et commissaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62320', '6232', 'Allocations familiales complémentaires pour non salariés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62321', '6232', 'Lois sociales pour indépendants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '62322', '6232', 'Divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '624', '62', 'Pensions de retraite et de survie', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6240', '624', 'Administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6241', '624', 'Personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '625', '62', 'Provision pour pécule de vacances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6250', '625', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6251', '625', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '63', '6', 'Amortissements, réductions de valeur et provisions pour risques et charges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '630', '63', 'Dotations aux amortissements et aux réductions de valeur sur immobilisations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6300', '630', 'Dotations aux amortissements sur frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6301', '630', 'Dotations aux amortissements sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6302', '630', 'Dotations aux amortissements sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6308', '630', 'Dotations aux réductions de valeur sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6309', '630', 'Dotations aux réductions de valeur sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '631', '63', 'Réductions de valeur sur stocks', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6310', '631', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6311', '631', 'Reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '632', '63', 'Réductions de valeur sur commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6320', '632', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6321', '632', 'Reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '633', '63', 'Réductions de valeur sur créances commerciales à plus d''un an', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6330', '633', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6331', '633', 'Reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '634', '63', 'Réductions de valeur sur créances commerciales à un an au plus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6340', '634', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6341', '634', 'Reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '635', '63', 'Provisions pour pensions et obligations similaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6350', '635', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6351', '635', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '636', '63', 'Provisions pour grosses réparations et gros entretiens', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6360', '636', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6361', '636', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '637', '63', 'Provisions pour autres risques et charges', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6370', '637', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6371', '637', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64', '6', 'Autres charges d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '640', '64', 'Charges fiscales d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6400', '640', 'Taxes et impôts directs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64000', '6400', 'Taxes sur autos et camions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6401', '640', 'Taxes et impôts indirects', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64010', '6401', 'Timbres fiscaux pris en charge par la firme', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64011', '6401', 'Droits d''enregistrement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64012', '6401', 'T.V.A. non déductible', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6402', '640', 'Impôts provinciaux et communaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64020', '6402', 'Taxe sur la force motrice', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '64021', '6402', 'Taxe sur le personnel occupé', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6403', '640', 'Taxes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '641', '64', 'Moins-values sur réalisations courantes d''immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '642', '64', 'Moins-values sur réalisations de créances commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '643', '64', 'à 648 Charges d''exploitations diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '649', '64', 'Charges d''exploitation portées à l''actif au titre de restructuration', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '65', '6', 'Charges financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '650', '65', 'Charges des dettes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6500', '650', 'Intérêts, commissions et frais afférents aux dettes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6501', '650', 'Amortissements des agios et frais d''émission d''emprunts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6502', '650', 'Autres charges de dettes', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6503', '650', 'Intérêts intercalaires portés à l''actif', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '651', '65', 'Réductions de valeur sur actifs circulants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6510', '651', 'Dotations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6511', '651', 'Reprises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '652', '65', 'Moins-values sur réalisation d''actifs circulants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '653', '65', 'Charges d''escompte de créances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '654', '65', 'Différences de change', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '655', '65', 'Ecarts de conversion des devises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '656', '65', 'Frais de banques, de chèques postaux', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '657', '65', 'Commissions sur ouvertures de crédit, cautions et avals', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '658', '65', 'Frais de vente des titres', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '66', '6', 'Charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '660', '66', 'Amortissements et réductions de valeur exceptionnels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6600', '660', 'Sur frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6601', '660', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6602', '660', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '661', '66', 'Réductions de valeur sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '662', '66', 'Provisions pour risques et charges exceptionnels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '663', '66', 'Moins-values sur réalisation d''actifs immobilisés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6630', '663', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6631', '663', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6632', '663', 'Sur immobilisations détenues en location-financement et droits similaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6633', '663', 'Sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6634', '663', 'Sur immeubles acquis ou construits en vue de la revente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '664', '66', 'à 668 Autres charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '664', '66', 'Pénalités et amendes diverses', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '665', '66', 'Différence de charge', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '669', '66', 'Charges exceptionnelles transférées à l''actif en frais de restructuration', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '67', '6', 'Impôts sur le résultat', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '670', '67', 'Impôts belges sur le résultat de l''exercice', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6700', '670', 'Impôts et précomptes dus ou versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6701', '670', 'Excédent de versements d''impôts et précomptes porté à l''actif', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6702', '670', 'Charges fiscales estimées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '671', '67', 'Impôts belges sur le résultat d''exercices antérieurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6710', '671', 'Suppléments d''impôts dus ou versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6711', '671', 'Suppléments d''impôts estimés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '6712', '671', 'Provisions fiscales constituées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '672', '67', 'Impôts étrangers sur le résultat de l''exercice', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '673', '67', 'Impôts étrangers sur le résultat d''exercices antérieurs', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '68', '6', 'Transferts aux réserves immunisées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '69', '6', 'Affectation des résultats', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '690', '69', 'Perte reportée de l''exercice précédent', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '691', '69', 'Dotation à la réserve légale', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '692', '69', 'Dotation aux autres réserves', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '693', '69', 'Bénéfice à reporter', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '694', '69', 'Rémunération du capital', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '695', '69', 'Administrateurs ou gérants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'CHARGE', 'XXXXXX', '696', '69', 'Autres allocataires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '70', '7', 'Chiffre d''affaires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '700', '70', 'à 707 Ventes et prestations de services', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '700', '70', 'Ventes de marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7000', '700', 'Ventes en Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7001', '700', 'Ventes dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7002', '700', 'Ventes à l''exportation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '701', '70', 'Ventes de produits finis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7010', '701', 'Ventes en Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7011', '701', 'Ventes dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7012', '701', 'Ventes à l''exportation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '702', '70', 'Ventes de déchets et rebuts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7020', '702', 'Ventes en Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7021', '702', 'Ventes dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7022', '702', 'Ventes à l''exportation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '703', '70', 'Ventes d''emballages récupérables', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '704', '70', 'Facturations des travaux en cours (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '705', '70', 'Prestations de services', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7050', '705', 'Prestations de services en Belgique', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7051', '705', 'Prestations de services dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7052', '705', 'Prestations de services en vue de l''exportation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '706', '70', 'Pénalités et dédits obtenus par l''entreprise', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '708', '70', 'Remises, ristournes et rabais accordés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7080', '708', 'Sur ventes de marchandises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7081', '708', 'Sur ventes de produits finis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7082', '708', 'Sur ventes de déchets et rebuts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7083', '708', 'Sur prestations de services', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7084', '708', 'Mali sur travaux facturés aux associations momentanées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '71', '7', 'Variation des stocks et des commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '712', '71', 'Des en cours de fabrication', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '713', '71', 'Des produits finis', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '715', '71', 'Des immeubles construits destinés à la vente', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '717', '71', 'Des commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7170', '717', 'Commandes en cours - Coût de revient', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '71700', '7170', 'Coût des commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '71701', '7170', 'Coût des travaux en cours des associations momentanées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7171', '717', 'Bénéfices portés en compte sur commandes en cours', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '71710', '7171', 'Sur commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '71711', '7171', 'Sur travaux en cours des associations momentanées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '72', '7', 'Production immobilisée', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '720', '72', 'En frais d''établissement', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '721', '72', 'En immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '722', '72', 'En immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '723', '72', 'En immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '74', '7', 'Autres produits d''exploitation', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '740', '74', 'Subsides d''exploitation et montants compensatoires', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '741', '74', 'Plus-values sur réalisations courantes d''immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '742', '74', 'Plus-values sur réalisations de créances commerciales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '743', '74', 'à 749 Produits d''exploitation divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '743', '74', 'Produits de services exploités dans l''intérêt du personnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '744', '74', 'Commissions et courtages', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '745', '74', 'Redevances pour brevets et licences', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '746', '74', 'Prestations de services (transports, études, etc)', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '747', '74', 'Revenus des immeubles affectés aux activités non professionnelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '748', '74', 'Locations diverses à caractère professionnel', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '749', '74', 'Produits divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7490', '749', 'Bonis sur reprises d''emballages consignés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7491', '749', 'Bonis sur travaux en associations momentanées', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '75', '7', 'Produits financiers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '750', '75', 'Produits des immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7500', '750', 'Revenus des actions', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7501', '750', 'Revenus des obligations', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7502', '750', 'Revenus des créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '751', '75', 'Produits des actifs circulants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '752', '75', 'Plus-values sur réalisations d''actifs circulants', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '753', '75', 'Subsides en capital et en intérêts', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '754', '75', 'Différences de change', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '755', '75', 'Ecarts de conversion des devises', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '756', '75', 'à 759 Produits financiers divers', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '756', '75', 'Produits des autres créances', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '757', '75', 'Escomptes obtenus', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '76', '7', 'Produits exceptionnels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '760', '76', 'Reprises d''amortissements et de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7600', '760', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7601', '760', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '761', '76', 'Reprises de réductions de valeur sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '762', '76', 'Reprises de provisions pour risques et charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '763', '76', 'Plus-values sur réalisation d''actifs immobilisés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7630', '763', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7631', '763', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7632', '763', 'Sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '764', '76', 'Autres produits exceptionnels', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '77', '7', 'Régularisations d''impôts et reprises de provisions fiscales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '771', '77', 'Impôts belges sur le résultat', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7710', '771', 'Régularisations d''impôts dus ou versés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7711', '771', 'Régularisations d''impôts estimés', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '7712', '771', 'Reprises de provisions fiscales', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '773', '77', 'Impôts étrangers sur le résultat', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '79', '7', 'Affectation aux résultats', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '790', '79', 'Bénéfice reporté de l''exercice précédent', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '791', '79', 'Prélèvement sur le capital et les primes d''émission', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '792', '79', 'Prélèvement sur les réserves', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '793', '79', 'Perte à reporter', '1');
INSERT INTO llx_accountingaccount (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ('PCMN-BASE', 'PROD', 'XXXXXX', '794', '79', 'Intervention d''associés (ou du propriétaire) dans la perte', '1');


ALTER TABLE llx_projet_task ADD COLUMN  entity integer DEFAULT 1 NOT NULL AFTER ref;

create table llx_product_customer_price
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer DEFAULT 1 NOT NULL,	   -- multi company id
  datec					datetime,
  tms					timestamp,
  fk_product			integer NOT NULL,
  fk_soc				integer NOT NULL,
  price						double(24,8) DEFAULT 0,
  price_ttc					double(24,8) DEFAULT 0,
  price_min					double(24,8) DEFAULT 0,
  price_min_ttc				double(24,8) DEFAULT 0,
  price_base_type			varchar(3)   DEFAULT 'HT',
  tva_tx					double(6,3),
  recuperableonly           integer NOT NULL DEFAULT '0',   -- Other NPR VAT
  localtax1_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 1
  localtax2_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 2
  fk_user				    integer,
  import_key			    varchar(14)                  -- Import key
)ENGINE=innodb;

ALTER TABLE llx_product_customer_price ADD INDEX idx_product_customer_price_fk_user (fk_user);
ALTER TABLE llx_product_customer_price ADD INDEX idx_product_customer_price_fk_soc (fk_soc);
ALTER TABLE llx_product_customer_price ADD UNIQUE INDEX uk_customer_price_fk_product_fk_soc (fk_product, fk_soc);

ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_customer_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_customer_price_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);

ALTER TABLE llx_user ADD COLUMN barcode varchar(255) DEFAULT NULL;
ALTER TABLE llx_user ADD COLUMN fk_barcode_type integer DEFAULT 0;
ALTER TABLE llx_user ADD COLUMN nb_holiday integer DEFAULT 0;
ALTER TABLE llx_user ADD COLUMN salary double(24,8) DEFAULT NULL;

ALTER TABLE llx_product ADD COLUMN url varchar(255);

create table llx_product_customer_price_log
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer DEFAULT 1 NOT NULL,	   -- multi company id
  datec                       datetime,
  fk_product			integer NOT NULL,
  fk_soc				integer NOT NULL,
  price						double(24,8) DEFAULT 0,
  price_ttc					double(24,8) DEFAULT 0,
  price_min					double(24,8) DEFAULT 0,
  price_min_ttc				double(24,8) DEFAULT 0,
  price_base_type			varchar(3)   DEFAULT 'HT',
  tva_tx					double(6,3),
  recuperableonly           integer NOT NULL DEFAULT 0,   -- Other NPR VAT
  localtax1_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 1
  localtax2_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 2
  fk_user				integer,
 import_key			varchar(14)                  -- Import key
)ENGINE=innodb;

-- Batch number management
ALTER TABLE llx_product ADD COLUMN tobatch tinyint DEFAULT 0 NOT NULL;

CREATE TABLE llx_product_batch (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_product_stock integer NOT NULL,
  eatby datetime DEFAULT NULL,
  sellby datetime DEFAULT NULL,
  batch varchar(30) DEFAULT NULL,
  qty double NOT NULL DEFAULT 0,
  import_key varchar(14) DEFAULT NULL,
  KEY ix_fk_product_stock (fk_product_stock)
) ENGINE=InnoDB;

CREATE TABLE llx_expeditiondet_batch (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_expeditiondet integer NOT NULL,
  eatby date DEFAULT NULL,
  sellby date DEFAULT NULL,
  batch varchar(30) DEFAULT NULL,
  qty double NOT NULL DEFAULT 0,
  fk_origin_stock integer NOT NULL,
  KEY ix_fk_expeditiondet (fk_expeditiondet)
) ENGINE=InnoDB;

-- Salary payment in tax module
--DROP TABLE llx_payment_salary
CREATE TABLE llx_payment_salary (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_user integer NOT NULL,
  datep date,
  datev date,
  amount real NOT NULL DEFAULT 0,
  fk_typepayment integer NOT NULL,
  num_payment varchar(50),
  label varchar(255),
  datesp date,                       -- date de début de la période
  dateep date,                       -- date de fin de la période
  entity integer DEFAULT 1 NOT NULL,	-- multi company id
  note text,
  fk_bank integer,
  fk_user_creat integer,
  fk_user_modif integer
)ENGINE=innodb;

-- New 1074 : Stock mouvement link to origin
ALTER TABLE llx_stock_mouvement ADD fk_origin integer;
ALTER TABLE llx_stock_mouvement ADD origintype VARCHAR(32);

-- New 1300 : Add THM on user
ALTER TABLE llx_user ADD thm double(24,8) AFTER fk_user;
ALTER TABLE llx_projet_task_time ADD thm double(24,8) AFTER fk_user;


-- New : extrafield on categories
create table llx_categories_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_categories_extrafields ADD INDEX idx_categories_extrafields (fk_object);

update llx_product set barcode = null where barcode in ('', '-1', '0');
update llx_societe set barcode = null where barcode in ('', '-1', '0');

-- Add missing unique keys
ALTER TABLE llx_product ADD INDEX idx_product_barcode (barcode);
ALTER TABLE llx_product ADD UNIQUE INDEX uk_product_barcode (barcode, fk_barcode_type, entity);
ALTER TABLE llx_societe ADD INDEX idx_societe_barcode (barcode);
ALTER TABLE llx_societe ADD UNIQUE INDEX uk_societe_barcode (barcode, fk_barcode_type, entity);


ALTER TABLE llx_tva ADD COLUMN fk_typepayment integer NULL;	-- table may already contains data
ALTER TABLE llx_tva ADD COLUMN num_payment varchar(50);

-- Add missing action triggers
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (31,'PROPAL_CLOSE_SIGNED','Customer proposal closed signed','Executed when a customer proposal is closed signed','propal',31);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (32,'PROPAL_CLOSE_REFUSED','Customer proposal closed refused','Executed when a customer proposal is closed refused','propal',32);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (33,'BILL_SUPPLIER_CANCELED','Supplier invoice cancelled','Executed when a supplier invoice is cancelled','invoice_supplier',33);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (34,'MEMBER_MODIFY','Member modified','Executed when a member is modified','member',34);

-- Automatic events for tasks
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (35,'TASK_CREATE','Task created','Executed when a project task is created','project',35);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (36,'TASK_MODIFY','Task modified','Executed when a project task is modified','project',36);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (37,'TASK_DELETE','Task deleted','Executed when a project task is deleted','project',37);

-- New : category translation
create table llx_categorie_lang
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_category    integer      DEFAULT 0 NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  label          varchar(255) NOT NULL,
  description    text
)ENGINE=innodb;

ALTER TABLE llx_categorie_lang ADD UNIQUE INDEX uk_category_lang (fk_category, lang);
ALTER TABLE llx_categorie_lang ADD CONSTRAINT fk_category_lang_fk_category 	FOREIGN KEY (fk_category) REFERENCES llx_categorie (rowid);

-- Resource module
CREATE TABLE llx_resource
(
  rowid           		integer AUTO_INCREMENT PRIMARY KEY,
  entity          		integer,
  ref             		varchar(255),
  description     		text,
  fk_code_type_resource varchar(32),
  note_public     		text,
  note_private    		text,
  tms         			timestamp
)ENGINE=innodb;

ALTER TABLE llx_resource ADD INDEX fk_code_type_resource_idx (fk_code_type_resource);

CREATE TABLE llx_element_resources
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  resource_id     integer,
  resource_type	  varchar(64),
  element_id	  integer,
  element_type    varchar(64),
  busy			  integer,
  mandatory		  integer,
  fk_user_create   integer,
  tms             timestamp
)ENGINE=innodb;

ALTER TABLE llx_element_resources ADD UNIQUE INDEX idx_element_resources_idx1 (resource_id, resource_type, element_id, element_type);
ALTER TABLE llx_element_resources ADD INDEX idx_element_element_element_id (element_id);
-- Pas de contraite sur resource_id et element_id car pointe sur differentes tables

create table llx_c_type_resource
(
  rowid      	integer  AUTO_INCREMENT PRIMARY KEY,
  code          varchar(32) NOT NULL,
  label 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_type_resource ADD UNIQUE INDEX uk_c_type_resource_id (label, code);

-- Fix: Missing instruction not correctly done into 3.5
-- VPGSQL8.2 ALTER TABLE llx_facture_fourn ALTER fk_mode_reglement DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_facture_fourn ALTER fk_cond_reglement DROP NOT NULL;

