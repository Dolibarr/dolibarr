-- MySQL dump 10.11
--
-- Host: localhost    Database: dolibarrdemo
-- ------------------------------------------------------
-- Server version	5.0.51a-3ubuntu5.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `llx_accountingaccount`
--

DROP TABLE IF EXISTS `llx_accountingaccount`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_accountingaccount` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_pcg_version` varchar(12) NOT NULL default '',
  `pcg_type` varchar(20) NOT NULL default '',
  `pcg_subtype` varchar(20) NOT NULL default '',
  `label` varchar(128) NOT NULL default '',
  `account_number` varchar(20) NOT NULL default '',
  `account_parent` varchar(20) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_accountingaccount_fk_pcg_version` (`fk_pcg_version`),
  CONSTRAINT `fk_accountingaccount_fk_pcg_version` FOREIGN KEY (`fk_pcg_version`) REFERENCES `llx_accountingsystem` (`pcg_version`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_accountingaccount`
--

LOCK TABLES `llx_accountingaccount` WRITE;
/*!40000 ALTER TABLE `llx_accountingaccount` DISABLE KEYS */;
INSERT INTO `llx_accountingaccount` VALUES (1,'PCG99-ABREGE','CAPIT','CAPITAL','Capital','101','1'),(2,'PCG99-ABREGE','CAPIT','XXXXXX','Ecarts de réévaluation','105','1'),(3,'PCG99-ABREGE','CAPIT','XXXXXX','Réserve légale','1061','1'),(4,'PCG99-ABREGE','CAPIT','XXXXXX','Réserves statutaires ou contractuelles','1063','1'),(5,'PCG99-ABREGE','CAPIT','XXXXXX','Réserves réglementées','1064','1'),(6,'PCG99-ABREGE','CAPIT','XXXXXX','Autres réserves','1068','1'),(7,'PCG99-ABREGE','CAPIT','XXXXXX','Compte de l\'exploitant','108','1'),(8,'PCG99-ABREGE','CAPIT','XXXXXX','Résultat de l\'exercice','12','1'),(9,'PCG99-ABREGE','CAPIT','XXXXXX','Amortissements dérogatoires','145','1'),(10,'PCG99-ABREGE','CAPIT','XXXXXX','Provision spéciale de réévaluation','146','1'),(11,'PCG99-ABREGE','CAPIT','XXXXXX','Plus-values réinvesties','147','1'),(12,'PCG99-ABREGE','CAPIT','XXXXXX','Autres provisions réglementées','148','1'),(13,'PCG99-ABREGE','CAPIT','XXXXXX','Provisions pour risques et charges','15','1'),(14,'PCG99-ABREGE','CAPIT','XXXXXX','Emprunts et dettes assimilees','16','1'),(15,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations incorporelles','20','2'),(16,'PCG99-ABREGE','IMMO','XXXXXX','Frais d\'établissement','201','20'),(17,'PCG99-ABREGE','IMMO','XXXXXX','Droit au bail','206','20'),(18,'PCG99-ABREGE','IMMO','XXXXXX','Fonds commercial','207','20'),(19,'PCG99-ABREGE','IMMO','XXXXXX','Autres immobilisations incorporelles','208','20'),(20,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations corporelles','21','2'),(21,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations en cours','23','2'),(22,'PCG99-ABREGE','IMMO','XXXXXX','Autres immobilisations financieres','27','2'),(23,'PCG99-ABREGE','IMMO','XXXXXX','Amortissements des immobilisations incorporelles','280','2'),(24,'PCG99-ABREGE','IMMO','XXXXXX','Amortissements des immobilisations corporelles','281','2'),(25,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des immobilisations incorporelles','290','2'),(26,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des immobilisations corporelles','291','2'),(27,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des autres immobilisations financières','297','2'),(28,'PCG99-ABREGE','STOCK','XXXXXX','Matieres premières','31','3'),(29,'PCG99-ABREGE','STOCK','XXXXXX','Autres approvisionnements','32','3'),(30,'PCG99-ABREGE','STOCK','XXXXXX','En-cours de production de biens','33','3'),(31,'PCG99-ABREGE','STOCK','XXXXXX','En-cours de production de services','34','3'),(32,'PCG99-ABREGE','STOCK','XXXXXX','Stocks de produits','35','3'),(33,'PCG99-ABREGE','STOCK','XXXXXX','Stocks de marchandises','37','3'),(34,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des matières premières','391','3'),(35,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des autres approvisionnements','392','3'),(36,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des en-cours de production de biens','393','3'),(37,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des en-cours de production de services','394','3'),(38,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des stocks de produits','395','3'),(39,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des stocks de marchandises','397','3'),(40,'PCG99-ABREGE','TIERS','SUPPLIER','Fournisseurs et Comptes rattachés','400','4'),(41,'PCG99-ABREGE','TIERS','XXXXXX','Fournisseurs débiteurs','409','4'),(42,'PCG99-ABREGE','TIERS','CUSTOMER','Clients et Comptes rattachés','410','4'),(43,'PCG99-ABREGE','TIERS','XXXXXX','Clients créditeurs','419','4'),(44,'PCG99-ABREGE','TIERS','XXXXXX','Personnel','421','4'),(45,'PCG99-ABREGE','TIERS','XXXXXX','Personnel','428','4'),(46,'PCG99-ABREGE','TIERS','XXXXXX','Sécurité sociale et autres organismes sociaux','43','4'),(47,'PCG99-ABREGE','TIERS','XXXXXX','Etat - impôts sur bénéfice','444','4'),(48,'PCG99-ABREGE','TIERS','XXXXXX','Etat - Taxes sur chiffre affaire','445','4'),(49,'PCG99-ABREGE','TIERS','XXXXXX','Autres impôts, taxes et versements assimilés','447','4'),(50,'PCG99-ABREGE','TIERS','XXXXXX','Groupe et associes','45','4'),(51,'PCG99-ABREGE','TIERS','XXXXXX','Associés','455','45'),(52,'PCG99-ABREGE','TIERS','XXXXXX','Débiteurs divers et créditeurs divers','46','4'),(53,'PCG99-ABREGE','TIERS','XXXXXX','Comptes transitoires ou d\'attente','47','4'),(54,'PCG99-ABREGE','TIERS','XXXXXX','Charges à répartir sur plusieurs exercices','481','4'),(55,'PCG99-ABREGE','TIERS','XXXXXX','Charges constatées d\'avance','486','4'),(56,'PCG99-ABREGE','TIERS','XXXXXX','Produits constatés d\'avance','487','4'),(57,'PCG99-ABREGE','TIERS','XXXXXX','Provisions pour dépréciation des comptes de clients','491','4'),(58,'PCG99-ABREGE','TIERS','XXXXXX','Provisions pour dépréciation des comptes de débiteurs divers','496','4'),(59,'PCG99-ABREGE','FINAN','XXXXXX','Valeurs mobilières de placement','50','5'),(60,'PCG99-ABREGE','FINAN','BANK','Banques, établissements financiers et assimilés','51','5'),(61,'PCG99-ABREGE','FINAN','CASH','Caisse','53','5'),(62,'PCG99-ABREGE','FINAN','XXXXXX','Régies d\'avance et accréditifs','54','5'),(63,'PCG99-ABREGE','FINAN','XXXXXX','Virements internes','58','5'),(64,'PCG99-ABREGE','FINAN','XXXXXX','Provisions pour dépréciation des valeurs mobilières de placement','590','5'),(65,'PCG99-ABREGE','CHARGE','PRODUCT','Achats','60','6'),(66,'PCG99-ABREGE','CHARGE','XXXXXX','Variations des stocks','603','60'),(67,'PCG99-ABREGE','CHARGE','SERVICE','Services extérieurs','61','6'),(68,'PCG99-ABREGE','CHARGE','XXXXXX','Autres services extérieurs','62','6'),(69,'PCG99-ABREGE','CHARGE','XXXXXX','Impôts, taxes et versements assimiles','63','6'),(70,'PCG99-ABREGE','CHARGE','XXXXXX','Rémunérations du personnel','641','6'),(71,'PCG99-ABREGE','CHARGE','XXXXXX','Rémunération du travail de l\'exploitant','644','6'),(72,'PCG99-ABREGE','CHARGE','SOCIAL','Charges de sécurité sociale et de prévoyance','645','6'),(73,'PCG99-ABREGE','CHARGE','XXXXXX','Cotisations sociales personnelles de l\'exploitant','646','6'),(74,'PCG99-ABREGE','CHARGE','XXXXXX','Autres charges de gestion courante','65','6'),(75,'PCG99-ABREGE','CHARGE','XXXXXX','Charges financières','66','6'),(76,'PCG99-ABREGE','CHARGE','XXXXXX','Charges exceptionnelles','67','6'),(77,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','681','6'),(78,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','686','6'),(79,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','687','6'),(80,'PCG99-ABREGE','CHARGE','XXXXXX','Participation des salariés aux résultats','691','6'),(81,'PCG99-ABREGE','CHARGE','XXXXXX','Impôts sur les bénéfices','695','6'),(82,'PCG99-ABREGE','CHARGE','XXXXXX','Imposition forfaitaire annuelle des sociétés','697','6'),(83,'PCG99-ABREGE','CHARGE','XXXXXX','Produits','699','6'),(84,'PCG99-ABREGE','PROD','PRODUCT','Ventes de produits finis','701','7'),(85,'PCG99-ABREGE','PROD','SERVICE','Prestations de services','706','7'),(86,'PCG99-ABREGE','PROD','PRODUCT','Ventes de marchandises','707','7'),(87,'PCG99-ABREGE','PROD','PRODUCT','Produits des activités annexes','708','7'),(88,'PCG99-ABREGE','PROD','XXXXXX','Rabais, remises et ristournes accordés par l\'entreprise','709','7'),(89,'PCG99-ABREGE','PROD','XXXXXX','Variation des stocks','713','7'),(90,'PCG99-ABREGE','PROD','XXXXXX','Production immobilisée','72','7'),(91,'PCG99-ABREGE','PROD','XXXXXX','Produits nets partiels sur opérations à long terme','73','7'),(92,'PCG99-ABREGE','PROD','XXXXXX','Subventions d\'exploitation','74','7'),(93,'PCG99-ABREGE','PROD','XXXXXX','Autres produits de gestion courante','75','7'),(94,'PCG99-ABREGE','PROD','XXXXXX','Jetons de présence et rémunérations d\'administrateurs, gérants,...','753','75'),(95,'PCG99-ABREGE','PROD','XXXXXX','Ristournes perçues des coopératives','754','75'),(96,'PCG99-ABREGE','PROD','XXXXXX','Quotes-parts de résultat sur opérations faites en commun','755','75'),(97,'PCG99-ABREGE','PROD','XXXXXX','Produits financiers','76','7'),(98,'PCG99-ABREGE','PROD','XXXXXX','Produits exceptionnels','77','7'),(99,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur amortissements et provisions','781','7'),(100,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur provisions pour risques','786','7'),(101,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur provisions','787','7'),(102,'PCG99-ABREGE','PROD','XXXXXX','Transferts de charges','79','7');
/*!40000 ALTER TABLE `llx_accountingaccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingdebcred`
--

DROP TABLE IF EXISTS `llx_accountingdebcred`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_accountingdebcred` (
  `fk_transaction` int(11) NOT NULL default '0',
  `fk_account` int(11) NOT NULL default '0',
  `amount` double NOT NULL default '0',
  `direction` char(1) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_accountingdebcred`
--

LOCK TABLES `llx_accountingdebcred` WRITE;
/*!40000 ALTER TABLE `llx_accountingdebcred` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_accountingdebcred` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingsystem`
--

DROP TABLE IF EXISTS `llx_accountingsystem`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_accountingsystem` (
  `pcg_version` varchar(12) NOT NULL default '',
  `fk_pays` int(11) NOT NULL default '0',
  `label` varchar(128) NOT NULL default '',
  `datec` varchar(12) NOT NULL default '',
  `fk_author` varchar(20) default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `active` smallint(6) default '0',
  PRIMARY KEY  (`pcg_version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_accountingsystem`
--

LOCK TABLES `llx_accountingsystem` WRITE;
/*!40000 ALTER TABLE `llx_accountingsystem` DISABLE KEYS */;
INSERT INTO `llx_accountingsystem` VALUES ('PCG99-ABREGE',1,'Plan de compte standard français abrégé','2008-08-07',NULL,'2008-08-07 19:56:02',0);
/*!40000 ALTER TABLE `llx_accountingsystem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingtransaction`
--

DROP TABLE IF EXISTS `llx_accountingtransaction`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_accountingtransaction` (
  `rowid` int(11) NOT NULL auto_increment,
  `label` varchar(128) NOT NULL default '',
  `datec` date NOT NULL default '0000-00-00',
  `fk_author` varchar(20) NOT NULL default '',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_facture` int(11) default NULL,
  `fk_facture_fourn` int(11) default NULL,
  `fk_paiement` int(11) default NULL,
  `fk_paiement_fourn` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_accountingtransaction`
--

LOCK TABLES `llx_accountingtransaction` WRITE;
/*!40000 ALTER TABLE `llx_accountingtransaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_accountingtransaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_action_def`
--

DROP TABLE IF EXISTS `llx_action_def`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_action_def` (
  `rowid` int(11) NOT NULL default '0',
  `code` varchar(28) NOT NULL default '',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `titre` varchar(255) NOT NULL default '',
  `description` text,
  `objet_type` varchar(16) NOT NULL default '',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_action_def`
--

LOCK TABLES `llx_action_def` WRITE;
/*!40000 ALTER TABLE `llx_action_def` DISABLE KEYS */;
INSERT INTO `llx_action_def` VALUES (1,'NOTIFY_VAL_FICHINTER','2008-08-07 19:56:01','Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter'),(2,'NOTIFY_VAL_FAC','2008-08-07 19:56:01','Validation facture','Déclenché lors de la validation d\'une facture','facture'),(3,'NOTIFY_VAL_ORDER_SUPPLIER','2008-08-07 19:56:01','Validation commande fournisseur','Déclenché lors de la validation d\'une commande fournisseur','order_supplier');
/*!40000 ALTER TABLE `llx_action_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm`
--

DROP TABLE IF EXISTS `llx_actioncomm`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_actioncomm` (
  `id` int(11) NOT NULL auto_increment,
  `datep` datetime default NULL,
  `datep2` datetime default NULL,
  `datea` datetime default NULL,
  `datea2` datetime default NULL,
  `fk_action` int(11) default NULL,
  `label` varchar(50) NOT NULL default '',
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_user_author` int(11) default NULL,
  `fk_user_mod` int(11) default NULL,
  `fk_project` int(11) default NULL,
  `fk_soc` int(11) default NULL,
  `fk_contact` int(11) default NULL,
  `fk_parent` int(11) NOT NULL default '0',
  `fk_user_action` int(11) default NULL,
  `fk_user_done` int(11) default NULL,
  `priority` smallint(6) default NULL,
  `punctual` smallint(6) NOT NULL default '1',
  `percent` smallint(6) NOT NULL default '0',
  `durationp` double default NULL,
  `durationa` double default NULL,
  `note` text,
  `propalrowid` int(11) default NULL,
  `fk_commande` int(11) default NULL,
  `fk_facture` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `idx_actioncomm_datea` (`datea`),
  KEY `idx_actioncomm_fk_soc` (`fk_soc`),
  KEY `idx_actioncomm_fk_contact` (`fk_contact`),
  KEY `idx_actioncomm_fk_facture` (`fk_facture`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_actioncomm`
--

LOCK TABLES `llx_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_actioncomm` VALUES (9,NULL,NULL,'2002-04-06 00:00:00',NULL,1,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,10,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(10,NULL,NULL,'2002-04-05 00:00:00',NULL,2,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,12,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(11,NULL,NULL,'2002-04-05 00:00:00',NULL,1,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,10,0,2,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(12,NULL,NULL,'2002-04-02 00:00:00',NULL,3,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,13,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(13,NULL,NULL,'2002-04-02 00:00:00',NULL,3,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,13,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(14,NULL,NULL,'2002-03-05 00:00:00',NULL,3,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,13,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(15,NULL,NULL,'2002-03-04 00:00:00',NULL,1,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,11,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL),(16,NULL,NULL,'2001-03-05 00:00:00',NULL,1,'',NULL,'2008-08-19 19:35:31',1,NULL,NULL,1,11,0,1,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent`
--

DROP TABLE IF EXISTS `llx_adherent`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_adherent` (
  `rowid` int(11) NOT NULL auto_increment,
  `nom` varchar(50) default NULL,
  `prenom` varchar(50) default NULL,
  `login` varchar(50) NOT NULL default '',
  `pass` varchar(50) default NULL,
  `fk_adherent_type` smallint(6) default NULL,
  `morphy` enum('mor','phy') NOT NULL default 'mor',
  `societe` varchar(50) default NULL,
  `adresse` text,
  `cp` varchar(30) default NULL,
  `ville` varchar(50) default NULL,
  `pays` varchar(50) default NULL,
  `email` varchar(255) default NULL,
  `phone` varchar(30) default NULL,
  `phone_perso` varchar(30) default NULL,
  `phone_mobile` varchar(30) default NULL,
  `naiss` date default NULL,
  `photo` varchar(255) default NULL,
  `statut` smallint(6) NOT NULL default '0',
  `public` smallint(6) NOT NULL default '0',
  `datefin` datetime default NULL,
  `note` text,
  `datevalid` datetime default NULL,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_user_author` int(11) NOT NULL default '0',
  `fk_user_mod` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_adherent`
--

LOCK TABLES `llx_adherent` WRITE;
/*!40000 ALTER TABLE `llx_adherent` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_options`
--

DROP TABLE IF EXISTS `llx_adherent_options`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_adherent_options` (
  `optid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `adhid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`optid`),
  UNIQUE KEY `adhid` (`adhid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_adherent_options`
--

LOCK TABLES `llx_adherent_options` WRITE;
/*!40000 ALTER TABLE `llx_adherent_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_options_label`
--

DROP TABLE IF EXISTS `llx_adherent_options_label`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_adherent_options_label` (
  `name` varchar(64) NOT NULL default '',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_adherent_options_label`
--

LOCK TABLES `llx_adherent_options_label` WRITE;
/*!40000 ALTER TABLE `llx_adherent_options_label` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent_options_label` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_type`
--

DROP TABLE IF EXISTS `llx_adherent_type`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_adherent_type` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `statut` smallint(6) NOT NULL default '0',
  `libelle` varchar(50) NOT NULL default '',
  `cotisation` enum('yes','no') NOT NULL default 'yes',
  `vote` enum('yes','no') NOT NULL default 'yes',
  `note` text,
  `mail_valid` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_adherent_type_libelle` (`libelle`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_adherent_type`
--

LOCK TABLES `llx_adherent_type` WRITE;
/*!40000 ALTER TABLE `llx_adherent_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_appro`
--

DROP TABLE IF EXISTS `llx_appro`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_appro` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL default '0',
  `quantity` smallint(5) unsigned NOT NULL default '0',
  `price` double default NULL,
  `fk_user_author` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_appro`
--

LOCK TABLES `llx_appro` WRITE;
/*!40000 ALTER TABLE `llx_appro` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_appro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank`
--

DROP TABLE IF EXISTS `llx_bank`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bank` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `datev` date default NULL,
  `dateo` date default NULL,
  `amount` double(24,8) NOT NULL default '0.00000000',
  `label` varchar(255) default NULL,
  `fk_account` int(11) default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_rappro` int(11) default NULL,
  `fk_type` varchar(4) default NULL,
  `num_releve` varchar(50) default NULL,
  `num_chq` varchar(50) default NULL,
  `rappro` tinyint(4) default '0',
  `note` text,
  `fk_bordereau` int(11) default '0',
  `banque` varchar(255) default NULL,
  `emetteur` varchar(255) default NULL,
  `author` varchar(40) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bank`
--

LOCK TABLES `llx_bank` WRITE;
/*!40000 ALTER TABLE `llx_bank` DISABLE KEYS */;
INSERT INTO `llx_bank` VALUES (1,'2008-08-19 21:35:31','2002-01-13','2002-01-13',4000.00000000,'Dépôt liquide',1,1,1,'DEP','200201',NULL,1,NULL,0,NULL,NULL,NULL),(2,'2008-08-19 21:35:31','2002-01-14','2002-01-14',-20.00000000,'Liquide',1,1,1,'CB','200201',NULL,1,NULL,0,NULL,NULL,NULL),(3,'2008-08-19 21:35:31','2002-02-14','2002-02-14',-23.20000000,'Essence',1,1,1,'CB','200201',NULL,1,NULL,0,NULL,NULL,NULL),(4,'2008-08-19 21:35:31','2002-02-15','2002-02-15',-53.32000000,'Cartouches imprimante',1,1,1,'CB','200202',NULL,0,NULL,0,NULL,NULL,NULL),(5,'2008-08-19 21:35:31','2002-02-17','2002-02-17',-100.00000000,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(6,'2008-08-19 21:35:31','2002-02-18','2002-02-18',-153.32000000,'Restaurant',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(7,'2008-08-19 21:35:31','2002-02-20','2002-02-20',-1532.00000000,'Réparation climatisation',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(8,'2008-08-19 21:35:31','2002-02-21','2002-02-21',-100.00000000,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(9,'2008-08-19 21:35:31','2002-02-22','2002-02-22',-46.00000000,'Timbres postes',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(10,'2008-08-19 21:35:31','2002-03-02','2002-03-02',-60.00000000,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(11,'2008-08-19 21:35:31','2002-03-02','2002-03-02',-25.66000000,'Essence',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(12,'2008-08-19 21:35:31','2002-03-03','2002-03-03',-60.00000000,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(13,'2008-08-19 21:35:31','2002-03-04','2002-03-04',-15.20000000,'Café',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(14,'2008-08-19 21:35:31','2002-03-06','2002-03-06',-12.30000000,'Péage',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(15,'2008-08-19 21:35:31','2002-03-06','2002-03-06',-25.30000000,'Péage',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(16,'2008-08-19 21:35:31','2002-03-06','2002-03-06',-9.60000000,'Tickets de bus',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(17,'2008-08-19 21:35:31','2002-03-13','2002-03-13',-10.00000000,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_account`
--

DROP TABLE IF EXISTS `llx_bank_account`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bank_account` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `ref` varchar(12) NOT NULL default '',
  `label` varchar(30) NOT NULL default '',
  `bank` varchar(60) default NULL,
  `code_banque` varchar(7) default NULL,
  `code_guichet` varchar(6) default NULL,
  `number` varchar(255) default NULL,
  `cle_rib` varchar(5) default NULL,
  `bic` varchar(11) default NULL,
  `iban_prefix` varchar(50) default NULL,
  `country_iban` char(2) default NULL,
  `cle_iban` char(2) default NULL,
  `domiciliation` varchar(255) default NULL,
  `proprio` varchar(60) default NULL,
  `adresse_proprio` varchar(255) default NULL,
  `courant` smallint(6) NOT NULL default '0',
  `clos` smallint(6) NOT NULL default '0',
  `rappro` smallint(6) default '1',
  `url` varchar(128) default NULL,
  `account_number` varchar(8) default NULL,
  `currency_code` char(3) NOT NULL default '',
  `min_allowed` int(11) default '0',
  `min_desired` int(11) default '0',
  `comment` varchar(254) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_bank_account_label` (`label`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bank_account`
--

LOCK TABLES `llx_bank_account` WRITE;
/*!40000 ALTER TABLE `llx_bank_account` DISABLE KEYS */;
INSERT INTO `llx_bank_account` VALUES (1,'2001-01-01 13:06:11','2003-10-14 15:34:28','','CCP','La PosteToto','','','','','','',NULL,NULL,'',NULL,NULL,1,0,1,NULL,NULL,'',0,0,NULL);
/*!40000 ALTER TABLE `llx_bank_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_categ`
--

DROP TABLE IF EXISTS `llx_bank_categ`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bank_categ` (
  `rowid` int(11) NOT NULL auto_increment,
  `label` varchar(255) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bank_categ`
--

LOCK TABLES `llx_bank_categ` WRITE;
/*!40000 ALTER TABLE `llx_bank_categ` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bank_categ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_class`
--

DROP TABLE IF EXISTS `llx_bank_class`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bank_class` (
  `lineid` int(11) NOT NULL default '0',
  `fk_categ` int(11) NOT NULL default '0',
  KEY `lineid` (`lineid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bank_class`
--

LOCK TABLES `llx_bank_class` WRITE;
/*!40000 ALTER TABLE `llx_bank_class` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bank_class` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_url`
--

DROP TABLE IF EXISTS `llx_bank_url`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bank_url` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_bank` int(11) default NULL,
  `url_id` int(11) default NULL,
  `url` varchar(255) default NULL,
  `label` varchar(255) default NULL,
  `type` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_bank_url` (`fk_bank`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bank_url`
--

LOCK TABLES `llx_bank_url` WRITE;
/*!40000 ALTER TABLE `llx_bank_url` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bank_url` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bookmark`
--

DROP TABLE IF EXISTS `llx_bookmark`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bookmark` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) default NULL,
  `fk_user` int(11) NOT NULL default '0',
  `dateb` datetime default NULL,
  `url` varchar(128) NOT NULL default '',
  `target` varchar(16) default NULL,
  `title` varchar(64) default NULL,
  `favicon` varchar(24) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_bookmark_url` (`fk_user`,`url`),
  UNIQUE KEY `uk_bookmark_title` (`fk_user`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bookmark`
--

LOCK TABLES `llx_bookmark` WRITE;
/*!40000 ALTER TABLE `llx_bookmark` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bookmark` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bordereau_cheque`
--

DROP TABLE IF EXISTS `llx_bordereau_cheque`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_bordereau_cheque` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_bordereau` date default NULL,
  `number` varchar(16) NOT NULL default '',
  `amount` double(24,8) NOT NULL default '0.00000000',
  `nbcheque` smallint(6) NOT NULL default '0',
  `fk_bank_account` int(11) default NULL,
  `fk_user_author` int(11) default NULL,
  `note` text,
  `statut` smallint(1) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_bordereau_cheque`
--

LOCK TABLES `llx_bordereau_cheque` WRITE;
/*!40000 ALTER TABLE `llx_bordereau_cheque` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bordereau_cheque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_boxes`
--

DROP TABLE IF EXISTS `llx_boxes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_boxes` (
  `rowid` int(11) NOT NULL auto_increment,
  `box_id` int(11) NOT NULL default '0',
  `position` smallint(6) NOT NULL default '0',
  `box_order` char(3) NOT NULL default '',
  `fk_user` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_boxes` (`box_id`,`position`,`fk_user`),
  KEY `idx_boxes_boxid` (`box_id`),
  KEY `idx_boxes_fk_user` (`fk_user`),
  CONSTRAINT `fk_boxes_box_id` FOREIGN KEY (`box_id`) REFERENCES `llx_boxes_def` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_boxes`
--

LOCK TABLES `llx_boxes` WRITE;
/*!40000 ALTER TABLE `llx_boxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_boxes_def`
--

DROP TABLE IF EXISTS `llx_boxes_def`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_boxes_def` (
  `rowid` int(11) NOT NULL auto_increment,
  `file` varchar(255) NOT NULL default '',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_boxes_def`
--

LOCK TABLES `llx_boxes_def` WRITE;
/*!40000 ALTER TABLE `llx_boxes_def` DISABLE KEYS */;
INSERT INTO `llx_boxes_def` VALUES (5,'box_propales.php','2008-08-07 19:59:02',NULL),(10,'box_commandes.php','2008-08-07 19:59:07',NULL),(13,'box_comptes.php','2008-08-07 19:59:16',NULL),(14,'box_fournisseurs.php','2008-08-07 19:59:19',NULL),(15,'box_factures_fourn_imp.php','2008-08-07 19:59:19',NULL),(16,'box_factures_fourn.php','2008-08-07 19:59:19',NULL),(21,'box_services_vendus.php','2008-08-07 19:59:27',NULL),(22,'box_produits.php','2008-08-07 19:59:27',NULL),(23,'box_actions.php','2008-08-07 19:59:53',NULL),(24,'box_factures_imp.php','2008-08-19 19:38:19',NULL),(25,'box_factures.php','2008-08-19 19:38:19',NULL),(26,'box_clients.php','2008-08-19 19:38:19',NULL),(27,'box_prospect.php','2008-08-19 19:38:19',NULL);
/*!40000 ALTER TABLE `llx_boxes_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_actioncomm`
--

DROP TABLE IF EXISTS `llx_c_actioncomm`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_actioncomm` (
  `id` int(11) NOT NULL default '0',
  `code` varchar(12) NOT NULL default '',
  `type` varchar(10) NOT NULL default 'system',
  `libelle` varchar(30) NOT NULL default '',
  `module` varchar(16) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  `todo` tinyint(4) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_actioncomm`
--

LOCK TABLES `llx_c_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_c_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_c_actioncomm` VALUES (1,'AC_TEL','system','Appel Téléphonique',NULL,1,NULL),(2,'AC_FAX','system','Envoi Fax',NULL,1,NULL),(3,'AC_PROP','system','Envoi Proposition','propal',1,NULL),(4,'AC_EMAIL','system','Envoi Email',NULL,1,NULL),(5,'AC_RDV','system','Rendez-vous',NULL,1,NULL),(8,'AC_COM','system','Envoi Commande','order',1,NULL),(9,'AC_FAC','system','Envoi Facture','invoice',1,NULL),(50,'AC_OTH','system','Autre',NULL,1,NULL);
/*!40000 ALTER TABLE `llx_c_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_barcode`
--

DROP TABLE IF EXISTS `llx_c_barcode`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_barcode` (
  `rowid` int(11) NOT NULL auto_increment,
  `code` varchar(16) NOT NULL default '',
  `libelle` varchar(50) NOT NULL default '',
  `coder` varchar(16) NOT NULL default '',
  `example` varchar(16) NOT NULL default '',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_barcode`
--

LOCK TABLES `llx_c_barcode` WRITE;
/*!40000 ALTER TABLE `llx_c_barcode` DISABLE KEYS */;
INSERT INTO `llx_c_barcode` VALUES (1,'EAN8','EAN8','0','1234567'),(2,'EAN13','EAN13','0','123456789012'),(3,'UPC','UPC','0','123456789012'),(4,'ISBN','ISBN','0','123456789'),(5,'C39','Code 39','0','1234567890'),(6,'C128','Code 128','0','ABCD1234567890');
/*!40000 ALTER TABLE `llx_c_barcode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_chargesociales`
--

DROP TABLE IF EXISTS `llx_c_chargesociales`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_chargesociales` (
  `id` int(11) NOT NULL auto_increment,
  `libelle` varchar(80) default NULL,
  `deductible` smallint(6) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '1',
  `actioncompta` varchar(12) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_chargesociales`
--

LOCK TABLES `llx_c_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_c_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_c_chargesociales` VALUES (1,'Allocations familiales',1,1,'TAXFAM'),(2,'GSG Deductible',1,1,'TAXCSGD'),(3,'GSG/CRDS NON Deductible',0,1,'TAXCSGND'),(10,'Taxe apprenttissage',0,1,'TAXAPP'),(11,'Taxe professionnelle',0,1,'TAXPRO'),(20,'Impots locaux/fonciers',0,1,'TAXFON'),(25,'Impots revenus',0,1,'TAXREV'),(30,'Assurance Sante (SECU-URSSAF)',0,1,'TAXSECU'),(40,'Mutuelle',0,1,'TAXMUT'),(50,'Assurance vieillesse (CNAV)',0,1,'TAXRET'),(60,'Assurance Chomage (ASSEDIC)',0,1,'TAXCHOM');
/*!40000 ALTER TABLE `llx_c_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_civilite`
--

DROP TABLE IF EXISTS `llx_c_civilite`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_civilite` (
  `rowid` int(11) NOT NULL default '0',
  `code` varchar(6) NOT NULL default '',
  `civilite` varchar(50) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_civilite`
--

LOCK TABLES `llx_c_civilite` WRITE;
/*!40000 ALTER TABLE `llx_c_civilite` DISABLE KEYS */;
INSERT INTO `llx_c_civilite` VALUES (1,'MME','Madame',1),(3,'MR','Monsieur',1),(5,'MLE','Mademoiselle',1),(7,'MTRE','Maître',1);
/*!40000 ALTER TABLE `llx_c_civilite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_currencies`
--

DROP TABLE IF EXISTS `llx_c_currencies`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_currencies` (
  `code` char(2) NOT NULL default '',
  `code_iso` char(3) NOT NULL default '',
  `label` varchar(64) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`code`),
  UNIQUE KEY `uk_c_currencies_code_iso` (`code_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_currencies`
--

LOCK TABLES `llx_c_currencies` WRITE;
/*!40000 ALTER TABLE `llx_c_currencies` DISABLE KEYS */;
INSERT INTO `llx_c_currencies` VALUES ('AD','AUD','Dollars australiens',1),('AE','AED','Arabes emirats dirham',1),('BT','THB','Bath thailandais',1),('CD','DKK','Couronnes dannoises',1),('CF','XAF','Francs cfa beac',1),('CN','NOK','Couronnes norvegiennes',1),('CS','SEK','Couronnes suedoises',1),('CZ','CZK','Couronnes tcheques',1),('DA','DZD','Dinar algérien',1),('DC','CAD','Dollars canadiens',1),('DH','MAD','Dirham',1),('DR','GRD','Drachme (grece)',1),('DS','SGD','Dollars singapour',1),('DU','USD','Dollars us',1),('EC','XEU','Ecus',1),('EG','EGP','Livre egyptienne',1),('ES','PTE','Escudos',0),('EU','EUR','Euros',1),('FB','BEF','Francs belges',0),('FF','FRF','Francs francais',0),('FH','HUF','Forint hongrois',1),('FL','LUF','Francs luxembourgeois',0),('FO','NLG','Florins',1),('FS','CHF','Francs suisses',1),('ID','IDR','Rupiahs d\'indonesie',1),('IN','INR','Roupie indienne',1),('KR','KRW','Won coree du sud',1),('LI','IEP','Livres irlandaises',1),('LK','LKR','Roupie sri lanka',1),('LR','ITL','Lires',0),('LS','GBP','Livres sterling',1),('LT','LTL','Litas',1),('MA','DEM','Deutsch mark',0),('MF','FIM','Mark finlandais',1),('NZ','NZD','Dollar neo-zelandais',1),('PA','ARP','Pesos argentins',1),('PC','CLP','Pesos chilien',1),('PE','ESP','Pesete',1),('PL','PLN','Zlotys polonais',1),('RB','BRL','Real bresilien',1),('RU','SUR','Rouble',1),('SA','ATS','Shiliing autrichiens',1),('SK','SKK','Couronnes slovaques',1),('TD','TND','Dinar tunisien',1),('TR','TRL','Livre turque',1),('TW','TWD','Dollar taiwanais',1),('YC','CNY','Yuang chinois',1),('YE','JPY','Yens',1),('ZA','ZAR','Rand africa',1);
/*!40000 ALTER TABLE `llx_c_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_departements`
--

DROP TABLE IF EXISTS `llx_c_departements`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_departements` (
  `rowid` int(11) NOT NULL auto_increment,
  `code_departement` varchar(6) NOT NULL default '',
  `fk_region` int(11) default NULL,
  `cheflieu` varchar(7) default NULL,
  `tncc` int(11) default NULL,
  `ncc` varchar(50) default NULL,
  `nom` varchar(50) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_departements` (`code_departement`,`fk_region`),
  KEY `idx_departements_fk_region` (`fk_region`)
) ENGINE=InnoDB AUTO_INCREMENT=174 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_departements`
--

LOCK TABLES `llx_c_departements` WRITE;
/*!40000 ALTER TABLE `llx_c_departements` DISABLE KEYS */;
INSERT INTO `llx_c_departements` VALUES (1,'0',0,'0',0,'-','-',1),(2,'01',82,'01053',5,'AIN','Ain',1),(3,'02',22,'02408',5,'AISNE','Aisne',1),(4,'03',83,'03190',5,'ALLIER','Allier',1),(5,'04',93,'04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence',1),(6,'05',93,'05061',4,'HAUTES-ALPES','Hautes-Alpes',1),(7,'06',93,'06088',4,'ALPES-MARITIMES','Alpes-Maritimes',1),(8,'07',82,'07186',5,'ARDECHE','Ardèche',1),(9,'08',21,'08105',4,'ARDENNES','Ardennes',1),(10,'09',73,'09122',5,'ARIEGE','Ariège',1),(11,'10',21,'10387',5,'AUBE','Aube',1),(12,'11',91,'11069',5,'AUDE','Aude',1),(13,'12',73,'12202',5,'AVEYRON','Aveyron',1),(14,'13',93,'13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône',1),(15,'14',25,'14118',2,'CALVADOS','Calvados',1),(16,'15',83,'15014',2,'CANTAL','Cantal',1),(17,'16',54,'16015',3,'CHARENTE','Charente',1),(18,'17',54,'17300',3,'CHARENTE-MARITIME','Charente-Maritime',1),(19,'18',24,'18033',2,'CHER','Cher',1),(20,'19',74,'19272',3,'CORREZE','Corrèze',1),(21,'2A',94,'2A004',3,'CORSE-DU-SUD','Corse-du-Sud',1),(22,'2B',94,'2B033',3,'HAUTE-CORSE','Haute-Corse',1),(23,'21',26,'21231',3,'COTE-D\'OR','Côte-d\'Or',1),(24,'22',53,'22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor',1),(25,'23',74,'23096',3,'CREUSE','Creuse',1),(26,'24',72,'24322',3,'DORDOGNE','Dordogne',1),(27,'25',43,'25056',2,'DOUBS','Doubs',1),(28,'26',82,'26362',3,'DROME','Drôme',1),(29,'27',23,'27229',5,'EURE','Eure',1),(30,'28',24,'28085',1,'EURE-ET-LOIR','Eure-et-Loir',1),(31,'29',53,'29232',2,'FINISTERE','Finistère',1),(32,'30',91,'30189',2,'GARD','Gard',1),(33,'31',73,'31555',3,'HAUTE-GARONNE','Haute-Garonne',1),(34,'32',73,'32013',2,'GERS','Gers',1),(35,'33',72,'33063',3,'GIRONDE','Gironde',1),(36,'34',91,'34172',5,'HERAULT','Hérault',1),(37,'35',53,'35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine',1),(38,'36',24,'36044',5,'INDRE','Indre',1),(39,'37',24,'37261',1,'INDRE-ET-LOIRE','Indre-et-Loire',1),(40,'38',82,'38185',5,'ISERE','Isère',1),(41,'39',43,'39300',2,'JURA','Jura',1),(42,'40',72,'40192',4,'LANDES','Landes',1),(43,'41',24,'41018',0,'LOIR-ET-CHER','Loir-et-Cher',1),(44,'42',82,'42218',3,'LOIRE','Loire',1),(45,'43',83,'43157',3,'HAUTE-LOIRE','Haute-Loire',1),(46,'44',52,'44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique',1),(47,'45',24,'45234',2,'LOIRET','Loiret',1),(48,'46',73,'46042',2,'LOT','Lot',1),(49,'47',72,'47001',0,'LOT-ET-GARONNE','Lot-et-Garonne',1),(50,'48',91,'48095',3,'LOZERE','Lozère',1),(51,'49',52,'49007',0,'MAINE-ET-LOIRE','Maine-et-Loire',1),(52,'50',25,'50502',3,'MANCHE','Manche',1),(53,'51',21,'51108',3,'MARNE','Marne',1),(54,'52',21,'52121',3,'HAUTE-MARNE','Haute-Marne',1),(55,'53',52,'53130',3,'MAYENNE','Mayenne',1),(56,'54',41,'54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle',1),(57,'55',41,'55029',3,'MEUSE','Meuse',1),(58,'56',53,'56260',2,'MORBIHAN','Morbihan',1),(59,'57',41,'57463',3,'MOSELLE','Moselle',1),(60,'58',26,'58194',3,'NIEVRE','Nièvre',1),(61,'59',31,'59350',2,'NORD','Nord',1),(62,'60',22,'60057',5,'OISE','Oise',1),(63,'61',25,'61001',5,'ORNE','Orne',1),(64,'62',31,'62041',2,'PAS-DE-CALAIS','Pas-de-Calais',1),(65,'63',83,'63113',2,'PUY-DE-DOME','Puy-de-Dôme',1),(66,'64',72,'64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques',1),(67,'65',73,'65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées',1),(68,'66',91,'66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales',1),(69,'67',42,'67482',2,'BAS-RHIN','Bas-Rhin',1),(70,'68',42,'68066',2,'HAUT-RHIN','Haut-Rhin',1),(71,'69',82,'69123',2,'RHONE','Rhône',1),(72,'70',43,'70550',3,'HAUTE-SAONE','Haute-Saône',1),(73,'71',26,'71270',0,'SAONE-ET-LOIRE','Saône-et-Loire',1),(74,'72',52,'72181',3,'SARTHE','Sarthe',1),(75,'73',82,'73065',3,'SAVOIE','Savoie',1),(76,'74',82,'74010',3,'HAUTE-SAVOIE','Haute-Savoie',1),(77,'75',11,'75056',0,'PARIS','Paris',1),(78,'76',23,'76540',3,'SEINE-MARITIME','Seine-Maritime',1),(79,'77',11,'77288',0,'SEINE-ET-MARNE','Seine-et-Marne',1),(80,'78',11,'78646',4,'YVELINES','Yvelines',1),(81,'79',54,'79191',4,'DEUX-SEVRES','Deux-Sèvres',1),(82,'80',22,'80021',3,'SOMME','Somme',1),(83,'81',73,'81004',2,'TARN','Tarn',1),(84,'82',73,'82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne',1),(85,'83',93,'83137',2,'VAR','Var',1),(86,'84',93,'84007',0,'VAUCLUSE','Vaucluse',1),(87,'85',52,'85191',3,'VENDEE','Vendée',1),(88,'86',54,'86194',3,'VIENNE','Vienne',1),(89,'87',74,'87085',3,'HAUTE-VIENNE','Haute-Vienne',1),(90,'88',41,'88160',4,'VOSGES','Vosges',1),(91,'89',26,'89024',5,'YONNE','Yonne',1),(92,'90',43,'90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort',1),(93,'91',11,'91228',5,'ESSONNE','Essonne',1),(94,'92',11,'92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine',1),(95,'93',11,'93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis',1),(96,'94',11,'94028',2,'VAL-DE-MARNE','Val-de-Marne',1),(97,'95',11,'95500',2,'VAL-D\'OISE','Val-d\'Oise',1),(98,'971',1,'97105',3,'GUADELOUPE','Guadeloupe',1),(99,'972',2,'97209',3,'MARTINIQUE','Martinique',1),(100,'973',3,'97302',3,'GUYANE','Guyane',1),(101,'974',4,'97411',3,'REUNION','Réunion',1),(102,'01',201,'',1,'ANVERS','Anvers',1),(103,'02',203,'',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale',1),(104,'03',202,'',2,'BRABANT-WALLON','Brabant-Wallon',1),(105,'04',201,'',1,'BRABANT-FLAMAND','Brabant-Flamand',1),(106,'05',201,'',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale',1),(107,'06',201,'',1,'FLANDRE-ORIENTALE','Flandre-Orientale',1),(108,'07',202,'',2,'HAINAUT','Hainaut',1),(109,'08',201,'',2,'LIEGE','Liège',1),(110,'09',202,'',1,'LIMBOURG','Limbourg',1),(111,'10',202,'',2,'LUXEMBOURG','Luxembourg',1),(112,'11',201,'',2,'NAMUR','Namur',1),(113,'NSW',2801,'',1,'','New South Wales',1),(114,'VIC',2801,'',1,'','Victoria',1),(115,'QLD',2801,'',1,'','Queensland',1),(116,'SA',2801,'',1,'','South Australia',1),(117,'ACT',2801,'',1,'','Australia Capital Territory',1),(118,'TAS',2801,'',1,'','Tasmania',1),(119,'WA',2801,'',1,'','Western Australia',1),(120,'NT',2801,'',1,'','Northern Territory',1),(121,'01',419,'',19,'PAIS VASCO','País Vasco',1),(122,'02',404,'',4,'ALBACETE','Albacete',1),(123,'03',411,'',11,'ALICANTE','Alicante',1),(124,'04',401,'',1,'ALMERIA','Almería',1),(125,'05',403,'',3,'AVILA','Avila',1),(126,'06',412,'',12,'BADAJOZ','Badajoz',1),(127,'07',414,'',14,'ISLAS BALEARES','Islas Baleares',1),(128,'08',406,'',6,'BARCELONA','Barcelona',1),(129,'09',403,'',8,'BURGOS','Burgos',1),(130,'10',412,'',12,'CACERES','Cáceres',1),(131,'11',401,'',1,'CADIz','Cádiz',1),(132,'12',411,'',11,'CASTELLON','Castellón',1),(133,'13',404,'',4,'CIUDAD REAL','Ciudad Real',1),(134,'14',401,'',1,'CORDOBA','Córdoba',1),(135,'15',413,'',13,'LA CORUÑA','La Coruña',1),(136,'16',404,'',4,'CUENCA','Cuenca',1),(137,'17',406,'',6,'GERONA','Gerona',1),(138,'18',401,'',1,'GRANADA','Granada',1),(139,'19',404,'',4,'GUADALAJARA','Guadalajara',1),(140,'20',419,'',19,'GUIPUZCOA','Guipúzcoa',1),(141,'21',401,'',1,'HUELVA','Huelva',1),(142,'22',402,'',2,'HUESCA','Huesca',1),(143,'23',401,'',1,'JAEN','Jaén',1),(144,'24',403,'',3,'LEON','León',1),(145,'25',406,'',6,'LERIDA','Lérida',1),(146,'26',415,'',15,'LA RIOJA','La Rioja',1),(147,'27',413,'',13,'LUGO','Lugo',1),(148,'28',416,'',16,'MADRID','Madrid',1),(149,'29',401,'',1,'MALAGA','Málaga',1),(150,'30',417,'',17,'MURCIA','Murcia',1),(151,'31',408,'',8,'NAVARRA','Navarra',1),(152,'32',413,'',13,'ORENSE','Orense',1),(153,'33',418,'',18,'ASTURIAS','Asturias',1),(154,'34',403,'',3,'PALENCIA','Palencia',1),(155,'35',405,'',5,'LAS PALMAS','Las Palmas',1),(156,'36',413,'',13,'PONTEVEDRA','Pontevedra',1),(157,'37',403,'',3,'SALAMANCA','Salamanca',1),(158,'38',405,'',5,'STA. CRUZ DE TENERIFE','Sta. Cruz de Tenerife',1),(159,'39',410,'',10,'CANTABRIA','Cantabria',1),(160,'40',403,'',3,'SEGOVIA','Segovia',1),(161,'41',401,'',1,'SEVILLA','Sevilla',1),(162,'42',403,'',3,'SORIA','Soria',1),(163,'43',406,'',6,'TARRAGONA','Tarragona',1),(164,'44',402,'',2,'TERUEL','Teruel',1),(165,'45',404,'',5,'TOLEDO','Toledo',1),(166,'46',411,'',11,'VALENCIA','Valencia',1),(167,'47',403,'',3,'VALLADOLID','Valladolid',1),(168,'48',419,'',19,'VIZCAYA','Vizcaya',1),(169,'49',403,'',3,'ZAMORA','Zamora',1),(170,'50',402,'',1,'ZARAGOZA','Zaragoza',1),(171,'51',407,'',7,'CEUTA','Ceuta',1),(172,'52',409,'',9,'MELILLA','Melilla',1),(173,'53',420,'',20,'OTROS','Otros',1);
/*!40000 ALTER TABLE `llx_c_departements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_ecotaxe`
--

DROP TABLE IF EXISTS `llx_c_ecotaxe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_ecotaxe` (
  `rowid` int(11) NOT NULL auto_increment,
  `code` varchar(64) NOT NULL default '',
  `libelle` varchar(255) default NULL,
  `price` double(24,8) default NULL,
  `organization` varchar(255) default NULL,
  `fk_pays` int(11) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_ecotaxe`
--

LOCK TABLES `llx_c_ecotaxe` WRITE;
/*!40000 ALTER TABLE `llx_c_ecotaxe` DISABLE KEYS */;
INSERT INTO `llx_c_ecotaxe` VALUES (1,'ER-A-A','Matériels électriques < 0,2kg',0.01000000,'ERP',1,1),(2,'ER-A-B','Matériels électriques >= 0,2 kg et < 0,5 kg',0.03000000,'ERP',1,1),(3,'ER-A-C','Matériels électriques >= 0,5 kg et < 1 kg',0.04000000,'ERP',1,1),(4,'ER-A-D','Matériels électriques >= 1 kg et < 2 kg',0.13000000,'ERP',1,1),(5,'ER-A-E','Matériels électriques >= 2 kg et < 4kg',0.21000000,'ERP',1,1),(6,'ER-A-F','Matériels électriques >= 4 kg et < 8 kg',0.42000000,'ERP',1,1),(7,'ER-A-G','Matériels électriques >= 8 kg et < 15 kg',0.84000000,'ERP',1,1),(8,'ER-A-H','Matériels électriques >= 15 kg et < 20 kg',1.25000000,'ERP',1,1),(9,'ER-A-I','Matériels électriques >= 20 kg et < 30 kg',1.88000000,'ERP',1,1),(10,'ER-A-J','Matériels électriques >= 30 kg',3.34000000,'ERP',1,1),(11,'ER-M-1','TV, Moniteurs < 9kg',0.84000000,'ERP',1,1),(12,'ER-M-2','TV, Moniteurs >= 9kg et < 15kg',1.67000000,'ERP',1,1),(13,'ER-M-3','TV, Moniteurs >= 15kg et < 30kg',3.34000000,'ERP',1,1),(14,'ER-M-4','TV, Moniteurs >= 30 kg',6.69000000,'ERP',1,1),(15,'EC-A-A','Matériels électriques  0,2 kg max',0.00840000,'Ecologic',1,1),(16,'EC-A-B','Matériels électriques 0,21 kg min - 0,50 kg max',0.02500000,'Ecologic',1,1),(17,'EC-A-C','Matériels électriques  0,51 kg min - 1 kg max',0.04000000,'Ecologic',1,1),(18,'EC-A-D','Matériels électriques  1,01 kg min - 2,5 kg max',0.13000000,'Ecologic',1,1),(19,'EC-A-E','Matériels électriques  2,51 kg min - 4 kg max',0.21000000,'Ecologic',1,1),(20,'EC-A-F','Matériels électriques 4,01 kg min - 8 kg max',0.42000000,'Ecologic',1,1),(21,'EC-A-G','Matériels électriques  8,01 kg min - 12 kg max',0.63000000,'Ecologic',1,1),(22,'EC-A-H','Matériels électriques 12,01 kg min - 20 kg max',1.05000000,'Ecologic',1,1),(23,'EC-A-I','Matériels électriques  20,01 kg min',1.88000000,'Ecologic',1,1),(24,'EC-M-1','TV, Moniteurs 9 kg max',0.84000000,'Ecologic',1,1),(25,'EC-M-2','TV, Moniteurs 9,01 kg min - 18 kg max',1.67000000,'Ecologic',1,1),(26,'EC-M-3','TV, Moniteurs 18,01 kg min - 36 kg max',3.34000000,'Ecologic',1,1),(27,'EC-M-4','TV, Moniteurs 36,01 kg min',6.69000000,'Ecologic',1,1),(28,'ES-M-1','TV, Moniteurs <= 20 pouces',0.84000000,'Eco-systèmes',1,1),(29,'ES-M-2','TV, Moniteurs > 20 pouces et <= 32 pouces',3.34000000,'Eco-systèmes',1,1),(30,'ES-M-3','TV, Moniteurs > 32 pouces et autres grands écrans',6.69000000,'Eco-systèmes',1,1),(31,'ES-A-A','Ordinateur fixe, Audio home systems (HIFI), éléments hifi séparés…',0.84000000,'Eco-systèmes',1,1),(32,'ES-A-B','Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD …  Instruments de musique et caisses de résonance, haut parleurs...',0.25000000,'Eco-systèmes',1,1),(33,'ES-A-C','Imprimante, photocopieur, télécopieur,…',0.42000000,'Eco-systèmes',1,1),(34,'ES-A-D','Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, téléphone, répondeur, téléphone sans fil, modem,...   Télécommande, casque, caméscope, baladeur mp3, radio portable, radio K7 et CD portable, set top box, radio réveil …',0.08400000,'Eco-systèmes',1,1),(35,'ES-A-E','GSM',0.00840000,'Eco-systèmes',1,1),(36,'ES-A-F','Jouets et équipements de loisirs et de sports < 0,5 kg',0.04200000,'Eco-systèmes',1,1),(37,'ES-A-G','Jouets et équipements de loisirs et de sports > 0,5 kg',0.17000000,'Eco-systèmes',1,1),(38,'ES-A-H','Jouets et équipements de loisirs et de sports > 10 kg',1.25000000,'Eco-systèmes',1,1);
/*!40000 ALTER TABLE `llx_c_ecotaxe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_effectif`
--

DROP TABLE IF EXISTS `llx_c_effectif`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_effectif` (
  `id` int(11) NOT NULL default '0',
  `code` varchar(12) NOT NULL default '',
  `libelle` varchar(30) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_effectif`
--

LOCK TABLES `llx_c_effectif` WRITE;
/*!40000 ALTER TABLE `llx_c_effectif` DISABLE KEYS */;
INSERT INTO `llx_c_effectif` VALUES (0,'EF0','-',1),(1,'EF1-5','1 - 5',1),(2,'EF6-10','6 - 10',1),(3,'EF11-50','11 - 50',1),(4,'EF51-100','51 - 100',1),(5,'EF100-500','100 - 500',1),(6,'EF500-','> 500',1);
/*!40000 ALTER TABLE `llx_c_effectif` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_forme_juridique`
--

DROP TABLE IF EXISTS `llx_c_forme_juridique`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_forme_juridique` (
  `rowid` int(11) NOT NULL auto_increment,
  `code` varchar(12) NOT NULL default '',
  `fk_pays` int(11) NOT NULL default '0',
  `libelle` varchar(255) default NULL,
  `isvatexempted` tinyint(4) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_forme_juridique`
--

LOCK TABLES `llx_c_forme_juridique` WRITE;
/*!40000 ALTER TABLE `llx_c_forme_juridique` DISABLE KEYS */;
INSERT INTO `llx_c_forme_juridique` VALUES (1,'0',0,'-',0,1),(2,'11',1,'Artisan Commerçant',0,1),(3,'12',1,'Commerçant',0,1),(4,'13',1,'Artisan',0,1),(5,'14',1,'Officier public ou ministériel',0,1),(6,'15',1,'Profession libérale',0,1),(7,'16',1,'Exploitant agricole',0,1),(8,'17',1,'Agent commercial',0,1),(9,'18',1,'Associé Gérant de société',0,1),(10,'19',1,'(Autre) personne physique',0,1),(11,'21',1,'Indivision',0,1),(12,'22',1,'Société créée de fait',0,1),(13,'23',1,'Société en participation',0,1),(14,'27',1,'Paroisse hors zone concordataire',0,1),(15,'29',1,'Autre groupement de droit privé non doté de la personnalité morale',0,1),(16,'31',1,'Personne morale de droit étranger, immatriculée au RCS',0,1),(17,'32',1,'Personne morale de droit étranger, non immatriculée au RCS',0,1),(18,'41',1,'Établissement public ou régie à caractère industriel ou commercial',0,1),(19,'51',1,'Société coopérative commerciale particulière',0,1),(20,'52',1,'Société en nom collectif',0,1),(21,'53',1,'Société en commandite',0,1),(22,'54',1,'Société à responsabilité limitée (SARL)',0,1),(23,'55',1,'Société anonyme à conseil d\'administration',0,1),(24,'56',1,'Société anonyme à directoire',0,1),(25,'57',1,'Société par actions simplifiée',0,1),(26,'58',1,'Entreprise Unipersonnelle à Responsabilité Limitée (EURL)',0,1),(27,'61',1,'Caisse d\'épargne et de prévoyance',0,1),(28,'62',1,'Groupement d\'intérêt économique (GIE)',0,1),(29,'63',1,'Société coopérative agricole',0,1),(30,'64',1,'Société non commerciale d\'assurances',0,1),(31,'65',1,'Société civile',0,1),(32,'69',1,'Autres personnes de droit privé inscrites au RCS',0,1),(33,'71',1,'Administration de l\'état',0,1),(34,'72',1,'Collectivité territoriale',0,1),(35,'73',1,'Établissement public administratif',0,1),(36,'74',1,'Autre personne morale de droit public administratif',0,1),(37,'81',1,'Organisme gérant régime de protection social à adhésion obligatoire',0,1),(38,'82',1,'Organisme mutualiste',0,1),(39,'83',1,'Comité d\'entreprise',0,1),(40,'84',1,'Organisme professionnel',0,1),(41,'85',1,'Organisme de retraite à adhésion non obligatoire',0,1),(42,'91',1,'Syndicat de propriétaires',0,1),(43,'92',1,'Association loi 1901 ou assimilé',0,1),(44,'93',1,'Fondation',0,1),(45,'99',1,'Autre personne morale de droit privé',0,1),(46,'200',2,'Indépendant',0,1),(47,'201',2,'SPRL - Société à responsabilité limitée',0,1),(48,'202',2,'SA   - Société Anonyme',0,1),(49,'203',2,'SCRL - Société coopérative à responsabilité limitée',0,1),(50,'204',2,'ASBL - Association sans but Lucratif',0,1),(51,'205',2,'SCRI - Société coopérative à responsabilité illimitée',0,1),(52,'206',2,'SCS  - Société en commandite simple',0,1),(53,'207',2,'SCA  - Société en commandite par action',0,1),(54,'208',2,'SNC  - Société en nom collectif',0,1),(55,'209',2,'GIE  - Groupement d\'intérêt économique',0,1),(56,'210',2,'GEIE - Groupement européen d\'intérêt économique',0,1),(57,'600',6,'Raison Individuelle',0,1),(58,'601',6,'Société Simple',0,1),(59,'602',6,'Société en nom collectif',0,1),(60,'603',6,'Société en commandite',0,1),(61,'604',6,'Société anonyme (SA)',0,1),(62,'605',6,'Société en commandite par actions',0,1),(63,'606',6,'Société à responsabilité limitée (SARL)',0,1),(64,'607',6,'Société coopérative',0,1),(65,'608',6,'Association',0,1),(66,'609',6,'Fondation',0,1),(67,'700',7,'Sole Trader',0,1),(68,'701',7,'Partnership',0,1),(69,'702',7,'Private Limited Company by shares (LTD)',0,1),(70,'703',7,'Public Limited Company',0,1),(71,'704',7,'Workers Cooperative',0,1),(72,'705',7,'Limited Liability Partnership',0,1),(73,'706',7,'Franchise',0,1),(74,'1000',10,'Société à responsabilité limitée (SARL)',0,1),(75,'1001',10,'Société en Nom Collectif (SNC)',0,1),(76,'1002',10,'Société en Commandite Simple (SCS)',0,1),(77,'1003',10,'société en participation',0,1),(78,'1004',10,'Société Anonyme (SA)',0,1),(79,'1005',10,'Société Unipersonnelle à Responsabilité Limitée (SUARL)',0,1),(80,'1006',10,'Groupement d\'intérêt économique (GEI)',0,1),(81,'1007',10,'Groupe de sociétés',0,1),(82,'401',4,'Empresario Individual',0,1),(83,'402',4,'Comunidad de Bienes',0,1),(84,'403',4,'Sociedad Civil',0,1),(85,'404',4,'Sociedad Colectiva',0,1),(86,'405',4,'Sociedad Limitada',0,1),(87,'406',4,'Sociedad Anonima',0,1),(88,'407',4,'Sociedad Comandataria por Acciones',0,1),(89,'408',4,'Sociedad Comandataria Simple',0,1),(90,'409',4,'Sociedad Laboral',0,1),(91,'410',4,'Sociedad Cooperativa',0,1),(92,'411',4,'Sociedad de Garantía Recíproca',0,1),(93,'412',4,'Entidad de Capital-Riesgo',0,1),(94,'413',4,'Agrupación de Interes Económico',0,1),(95,'414',4,'Sociedad de Invarsión Mobiliaria',0,1),(96,'415',4,'Agrupación sin Animo de Lucro',0,1);
/*!40000 ALTER TABLE `llx_c_forme_juridique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_methode_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_c_methode_commande_fournisseur`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_methode_commande_fournisseur` (
  `rowid` int(11) NOT NULL auto_increment,
  `code` varchar(30) default NULL,
  `libelle` varchar(60) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_methode_commande_fournisseur`
--

LOCK TABLES `llx_c_methode_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_c_methode_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_c_methode_commande_fournisseur` VALUES (1,'OrderByMail','Courrier',1),(2,'OrderByFax','Fax',1),(3,'OrderByEMail','EMail',1),(4,'OrderByPhone','Téléphone',1),(5,'OrderByWWW','En ligne',1);
/*!40000 ALTER TABLE `llx_c_methode_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_paiement`
--

DROP TABLE IF EXISTS `llx_c_paiement`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_paiement` (
  `id` int(11) NOT NULL default '0',
  `code` varchar(6) NOT NULL default '',
  `libelle` varchar(30) default NULL,
  `type` smallint(6) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_paiement`
--

LOCK TABLES `llx_c_paiement` WRITE;
/*!40000 ALTER TABLE `llx_c_paiement` DISABLE KEYS */;
INSERT INTO `llx_c_paiement` VALUES (0,'','-',3,1),(1,'TIP','TIP',2,1),(2,'VIR','Virement',2,1),(3,'PRE','Prélèvement',2,1),(4,'LIQ','Espèces',2,1),(5,'VAD','Paiement en ligne',2,0),(6,'CB','Carte Bancaire',2,1),(7,'CHQ','Chèque',2,1),(8,'TRA','Traite',2,0),(9,'LCR','LCR',2,0),(10,'FAC','Factor',2,0),(11,'PRO','Proforma',2,0);
/*!40000 ALTER TABLE `llx_c_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_paper_format`
--

DROP TABLE IF EXISTS `llx_c_paper_format`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_paper_format` (
  `rowid` int(11) NOT NULL auto_increment,
  `code` varchar(16) NOT NULL default '',
  `label` varchar(50) NOT NULL default '',
  `width` float(6,2) default '0.00',
  `height` float(6,2) default '0.00',
  `unit` enum('mm','cm','point','inch') NOT NULL default 'mm',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_paper_format`
--

LOCK TABLES `llx_c_paper_format` WRITE;
/*!40000 ALTER TABLE `llx_c_paper_format` DISABLE KEYS */;
INSERT INTO `llx_c_paper_format` VALUES (1,'4A0','Format 4A0',1682.00,2378.00,'mm',1),(2,'2A0','Format 2A0',1189.00,1682.00,'mm',1),(3,'A0','Format A0',840.00,1189.00,'mm',1),(4,'A1','Format A1',594.00,840.00,'mm',1),(5,'A2','Format A2',420.00,594.00,'mm',1),(6,'A3','Format A3',297.00,420.00,'mm',1),(7,'A4','Format A4',210.00,297.00,'mm',1),(8,'A5','Format A5',148.00,210.00,'mm',1),(9,'A6','Format A6',105.00,148.00,'mm',1),(100,'USLetter','Format Letter (A)',216.00,279.00,'mm',0),(105,'USLegal','Format Legal',216.00,356.00,'mm',0),(110,'USExecutive','Format Executive',190.00,254.00,'mm',0),(115,'USLedger','Format Ledger/Tabloid (B)',279.00,432.00,'mm',0),(200,'Canadian P1','Format Canadian P1',560.00,860.00,'mm',0),(205,'Canadian P2','Format Canadian P2',430.00,560.00,'mm',0),(210,'Canadian P3','Format Canadian P3',280.00,430.00,'mm',0),(215,'Canadian P4','Format Canadian P4',215.00,280.00,'mm',0),(220,'Canadian P5','Format Canadian P5',140.00,215.00,'mm',0),(225,'Canadian P6','Format Canadian P6',107.00,140.00,'mm',0);
/*!40000 ALTER TABLE `llx_c_paper_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_pays`
--

DROP TABLE IF EXISTS `llx_c_pays`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_pays` (
  `rowid` int(11) NOT NULL default '0',
  `code` char(2) NOT NULL default '',
  `code_iso` char(3) default NULL,
  `libelle` varchar(50) NOT NULL default '',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_c_pays_code` (`code`),
  UNIQUE KEY `idx_c_pays_libelle` (`libelle`),
  UNIQUE KEY `idx_c_pays_code_iso` (`code_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_pays`
--

LOCK TABLES `llx_c_pays` WRITE;
/*!40000 ALTER TABLE `llx_c_pays` DISABLE KEYS */;
INSERT INTO `llx_c_pays` VALUES (0,'',NULL,'-',1),(1,'FR',NULL,'France',1),(2,'BE',NULL,'Belgique',1),(3,'IT',NULL,'Italie',1),(4,'ES',NULL,'Espagne',1),(5,'DE',NULL,'Allemagne',1),(6,'CH',NULL,'Suisse',1),(7,'GB',NULL,'Royaume uni',1),(8,'IE',NULL,'Irlande',1),(9,'CN',NULL,'Chine',1),(10,'TN',NULL,'Tunisie',1),(11,'US',NULL,'Etats Unis',1),(12,'MA',NULL,'Maroc',1),(13,'DZ',NULL,'Algérie',1),(14,'CA',NULL,'Canada',1),(15,'TG',NULL,'Togo',1),(16,'GA',NULL,'Gabon',1),(17,'NL',NULL,'Pays Bas',1),(18,'HU',NULL,'Hongrie',1),(19,'RU',NULL,'Russie',1),(20,'SE',NULL,'Suède',1),(21,'CI',NULL,'Côte d\'Ivoire',1),(22,'SN',NULL,'Sénégal',1),(23,'AR',NULL,'Argentine',1),(24,'CM',NULL,'Cameroun',1),(25,'PT',NULL,'Portugal',1),(26,'SA',NULL,'Arabie Saoudite',1),(27,'MC',NULL,'Monaco',1),(28,'AU',NULL,'Australie',1),(29,'SG',NULL,'Singapoure',1),(30,'AF',NULL,'Afghanistan',1),(31,'AX',NULL,'Iles Aland',1),(32,'AL',NULL,'Albanie',1),(33,'AS',NULL,'Samoa américaines',1),(34,'AD',NULL,'Andorre',1),(35,'AO',NULL,'Angola',1),(36,'AI',NULL,'Anguilla',1),(37,'AQ',NULL,'Antarctique',1),(38,'AG',NULL,'Antigua-et-Barbuda',1),(39,'AM',NULL,'Arménie',1),(40,'AW',NULL,'Aruba',1),(41,'AT',NULL,'Autriche',1),(42,'AZ',NULL,'Azerbaïdjan',1),(43,'BS',NULL,'Bahamas',1),(44,'BH',NULL,'Bahreïn',1),(45,'BD',NULL,'Bangladesh',1),(46,'BB',NULL,'Barbade',1),(47,'BY',NULL,'Biélorussie',1),(48,'BZ',NULL,'Belize',1),(49,'BJ',NULL,'Bénin',1),(50,'BM',NULL,'Bermudes',1),(51,'BT',NULL,'Bhoutan',1),(52,'BO',NULL,'Bolivie',1),(53,'BA',NULL,'Bosnie-Herzégovine',1),(54,'BW',NULL,'Botswana',1),(55,'BV',NULL,'Ile Bouvet',1),(56,'BR',NULL,'Brésil',1),(57,'IO',NULL,'Territoire britannique de l\'Océan Indien',1),(58,'BN',NULL,'Brunei',1),(59,'BG',NULL,'Bulgarie',1),(60,'BF',NULL,'Burkina Faso',1),(61,'BI',NULL,'Burundi',1),(62,'KH',NULL,'Cambodge',1),(63,'CV',NULL,'Cap-Vert',1),(64,'KY',NULL,'Iles Cayman',1),(65,'CF',NULL,'République centrafricaine',1),(66,'TD',NULL,'Tchad',1),(67,'CL',NULL,'Chili',1),(68,'CX',NULL,'Ile Christmas',1),(69,'CC',NULL,'Iles des Cocos (Keeling)',1),(70,'CO',NULL,'Colombie',1),(71,'KM',NULL,'Comores',1),(72,'CG',NULL,'Congo',1),(73,'CD',NULL,'République démocratique du Congo',1),(74,'CK',NULL,'Iles Cook',1),(75,'CR',NULL,'Costa Rica',1),(76,'HR',NULL,'Croatie',1),(77,'CU',NULL,'Cuba',1),(78,'CY',NULL,'Chypre',1),(79,'CZ',NULL,'République Tchèque',1),(80,'DK',NULL,'Danemark',1),(81,'DJ',NULL,'Djibouti',1),(82,'DM',NULL,'Dominique',1),(83,'DO',NULL,'République Dominicaine',1),(84,'EC',NULL,'Equateur',1),(85,'EG',NULL,'Egypte',1),(86,'SV',NULL,'Salvador',1),(87,'GQ',NULL,'Guinée Equatoriale',1),(88,'ER',NULL,'Erythrée',1),(89,'EE',NULL,'Estonie',1),(90,'ET',NULL,'Ethiopie',1),(91,'FK',NULL,'Iles Falkland',1),(92,'FO',NULL,'Iles Féroé',1),(93,'FJ',NULL,'Iles Fidji',1),(94,'FI',NULL,'Finlande',1),(95,'GF',NULL,'Guyane française',1),(96,'PF',NULL,'Polynésie française',1),(97,'TF',NULL,'Terres australes françaises',1),(98,'GM',NULL,'Gambie',1),(99,'GE',NULL,'Géorgie',1),(100,'GH',NULL,'Ghana',1),(101,'GI',NULL,'Gibraltar',1),(102,'GR',NULL,'Grèce',1),(103,'GL',NULL,'Groenland',1),(104,'GD',NULL,'Grenade',1),(105,'GP',NULL,'Guadeloupe',1),(106,'GU',NULL,'Guam',1),(107,'GT',NULL,'Guatemala',1),(108,'GN',NULL,'Guinée',1),(109,'GW',NULL,'Guinée-Bissao',1),(110,'GY',NULL,'Guyana',1),(111,'HT',NULL,'Haïti',1),(112,'HM',NULL,'Iles Heard et McDonald',1),(113,'VA',NULL,'Saint-Siège (Vatican)',1),(114,'HN',NULL,'Honduras',1),(115,'HK',NULL,'Hong Kong',1),(116,'IS',NULL,'Islande',1),(117,'IN',NULL,'Inde',1),(118,'ID',NULL,'Indonésie',1),(119,'IR',NULL,'Iran',1),(120,'IQ',NULL,'Iraq',1),(121,'IL',NULL,'Israël',1),(122,'JM',NULL,'Jamaïque',1),(123,'JP',NULL,'Japon',1),(124,'JO',NULL,'Jordanie',1),(125,'KZ',NULL,'Kazakhstan',1),(126,'KE',NULL,'Kenya',1),(127,'KI',NULL,'Kiribati',1),(128,'KP',NULL,'Corée du Nord',1),(129,'KR',NULL,'Corée du Sud',1),(130,'KW',NULL,'Koweït',1),(131,'KG',NULL,'Kirghizistan',1),(132,'LA',NULL,'Laos',1),(133,'LV',NULL,'Lettonie',1),(134,'LB',NULL,'Liban',1),(135,'LS',NULL,'Lesotho',1),(136,'LR',NULL,'Liberia',1),(137,'LY',NULL,'Libye',1),(138,'LI',NULL,'Liechtenstein',1),(139,'LT',NULL,'Lituanie',1),(140,'LU',NULL,'Luxembourg',1),(141,'MO',NULL,'Macao',1),(142,'MK',NULL,'ex-République yougoslave de Macédoine',1),(143,'MG',NULL,'Madagascar',1),(144,'MW',NULL,'Malawi',1),(145,'MY',NULL,'Malaisie',1),(146,'MV',NULL,'Maldives',1),(147,'ML',NULL,'Mali',1),(148,'MT',NULL,'Malte',1),(149,'MH',NULL,'Iles Marshall',1),(150,'MQ',NULL,'Martinique',1),(151,'MR',NULL,'Mauritanie',1),(152,'MU',NULL,'Maurice',1),(153,'YT',NULL,'Mayotte',1),(154,'MX',NULL,'Mexique',1),(155,'FM',NULL,'Micronésie',1),(156,'MD',NULL,'Moldavie',1),(157,'MN',NULL,'Mongolie',1),(158,'MS',NULL,'Monserrat',1),(159,'MZ',NULL,'Mozambique',1),(160,'MM',NULL,'Birmanie (Myanmar)',1),(161,'NA',NULL,'Namibie',1),(162,'NR',NULL,'Nauru',1),(163,'NP',NULL,'Népal',1),(164,'AN',NULL,'Antilles néerlandaises',1),(165,'NC',NULL,'Nouvelle-Calédonie',1),(166,'NZ',NULL,'Nouvelle-Zélande',1),(167,'NI',NULL,'Nicaragua',1),(168,'NE',NULL,'Niger',1),(169,'NG',NULL,'Nigeria',1),(170,'NU',NULL,'Nioué',1),(171,'NF',NULL,'Ile Norfolk',1),(172,'MP',NULL,'Mariannes du Nord',1),(173,'NO',NULL,'Norvège',1),(174,'OM',NULL,'Oman',1),(175,'PK',NULL,'Pakistan',1),(176,'PW',NULL,'Palaos',1),(177,'PS',NULL,'territoire Palestinien Occupé',1),(178,'PA',NULL,'Panama',1),(179,'PG',NULL,'Papouasie-Nouvelle-Guinée',1),(180,'PY',NULL,'Paraguay',1),(181,'PE',NULL,'Pérou',1),(182,'PH',NULL,'Philippines',1),(183,'PN',NULL,'Iles Pitcairn',1),(184,'PL',NULL,'Pologne',1),(185,'PR',NULL,'Porto Rico',1),(186,'QA',NULL,'Qatar',1),(187,'RE',NULL,'Réunion',1),(188,'RO',NULL,'Roumanie',1),(189,'RW',NULL,'Rwanda',1),(190,'SH',NULL,'Sainte-Hélène',1),(191,'KN',NULL,'Saint-Christophe-et-Niévès',1),(192,'LC',NULL,'Sainte-Lucie',1),(193,'PM',NULL,'Saint-Pierre-et-Miquelon',1),(194,'VC',NULL,'Saint-Vincent-et-les-Grenadines',1),(195,'WS',NULL,'Samoa',1),(196,'SM',NULL,'Saint-Marin',1),(197,'ST',NULL,'Sao Tomé-et-Principe',1),(198,'RS',NULL,'Serbie',1),(199,'SC',NULL,'Seychelles',1),(200,'SL',NULL,'Sierra Leone',1),(201,'SK',NULL,'Slovaquie',1),(202,'SI',NULL,'Slovénie',1),(203,'SB',NULL,'Iles Salomon',1),(204,'SO',NULL,'Somalie',1),(205,'ZA',NULL,'Afrique du Sud',1),(206,'GS',NULL,'Iles Géorgie du Sud et Sandwich du Sud',1),(207,'LK',NULL,'Sri Lanka',1),(208,'SD',NULL,'Soudan',1),(209,'SR',NULL,'Suriname',1),(210,'SJ',NULL,'Iles Svalbard et Jan Mayen',1),(211,'SZ',NULL,'Swaziland',1),(212,'SY',NULL,'Syrie',1),(213,'TW',NULL,'Taïwan',1),(214,'TJ',NULL,'Tadjikistan',1),(215,'TZ',NULL,'Tanzanie',1),(216,'TH',NULL,'Thaïlande',1),(217,'TL',NULL,'Timor Oriental',1),(218,'TK',NULL,'Tokélaou',1),(219,'TO',NULL,'Tonga',1),(220,'TT',NULL,'Trinité-et-Tobago',1),(221,'TR',NULL,'Turquie',1),(222,'TM',NULL,'Turkménistan',1),(223,'TC',NULL,'Iles Turks-et-Caicos',1),(224,'TV',NULL,'Tuvalu',1),(225,'UG',NULL,'Ouganda',1),(226,'UA',NULL,'Ukraine',1),(227,'AE',NULL,'Émirats arabes unis',1),(228,'UM',NULL,'Iles mineures éloignées des États-Unis',1),(229,'UY',NULL,'Uruguay',1),(230,'UZ',NULL,'Ouzbékistan',1),(231,'VU',NULL,'Vanuatu',1),(232,'VE',NULL,'Vénézuela',1),(233,'VN',NULL,'Viêt Nam',1),(234,'VG',NULL,'Iles Vierges britanniques',1),(235,'VI',NULL,'Iles Vierges américaines',1),(236,'WF',NULL,'Wallis-et-Futuna',1),(237,'EH',NULL,'Sahara occidental',1),(238,'YE',NULL,'Yémen',1),(239,'ZM',NULL,'Zambie',1),(240,'ZW',NULL,'Zimbabwe',1),(241,'GG',NULL,'Guernesey',1),(242,'IM',NULL,'Ile de Man',1),(243,'JE',NULL,'Jersey',1),(244,'ME',NULL,'Monténégro',1),(245,'BL',NULL,'Saint-Barthélemy',1),(246,'MF',NULL,'Saint-Martin',1);
/*!40000 ALTER TABLE `llx_c_pays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_propalst`
--

DROP TABLE IF EXISTS `llx_c_propalst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_propalst` (
  `id` smallint(6) NOT NULL default '0',
  `code` varchar(12) NOT NULL default '',
  `label` varchar(30) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_propalst`
--

LOCK TABLES `llx_c_propalst` WRITE;
/*!40000 ALTER TABLE `llx_c_propalst` DISABLE KEYS */;
INSERT INTO `llx_c_propalst` VALUES (0,'PR_DRAFT','Brouillon',1),(1,'PR_OPEN','Ouverte',1),(2,'PR_SIGNED','Signée',1),(3,'PR_NOTSIGNED','Non Signée',1),(4,'PR_FAC','Facturée',1);
/*!40000 ALTER TABLE `llx_c_propalst` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_prospectlevel`
--

DROP TABLE IF EXISTS `llx_c_prospectlevel`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_prospectlevel` (
  `code` varchar(12) NOT NULL default '',
  `label` varchar(30) default NULL,
  `sortorder` smallint(6) default NULL,
  `active` smallint(6) NOT NULL default '1',
  PRIMARY KEY  (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_prospectlevel`
--

LOCK TABLES `llx_c_prospectlevel` WRITE;
/*!40000 ALTER TABLE `llx_c_prospectlevel` DISABLE KEYS */;
INSERT INTO `llx_c_prospectlevel` VALUES ('PL_HIGH','High',4,1),('PL_LOW','Low',2,1),('PL_MEDIUM','Medium',3,1),('PL_UNKOWN','Unknown',1,1);
/*!40000 ALTER TABLE `llx_c_prospectlevel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_regions`
--

DROP TABLE IF EXISTS `llx_c_regions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_regions` (
  `rowid` int(11) NOT NULL auto_increment,
  `code_region` int(11) NOT NULL default '0',
  `fk_pays` int(11) NOT NULL default '0',
  `cheflieu` varchar(7) default NULL,
  `tncc` int(11) default NULL,
  `nom` varchar(50) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `code_region` (`code_region`),
  KEY `idx_c_regions_fk_pays` (`fk_pays`),
  CONSTRAINT `fk_c_regions_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2822 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_regions`
--

LOCK TABLES `llx_c_regions` WRITE;
/*!40000 ALTER TABLE `llx_c_regions` DISABLE KEYS */;
INSERT INTO `llx_c_regions` VALUES (1,0,0,'0',0,'-',1),(101,1,1,'97105',3,'Guadeloupe',1),(102,2,1,'97209',3,'Martinique',1),(103,3,1,'97302',3,'Guyane',1),(104,4,1,'97411',3,'Réunion',1),(105,11,1,'75056',1,'Île-de-France',1),(106,21,1,'51108',0,'Champagne-Ardenne',1),(107,22,1,'80021',0,'Picardie',1),(108,23,1,'76540',0,'Haute-Normandie',1),(109,24,1,'45234',2,'Centre',1),(110,25,1,'14118',0,'Basse-Normandie',1),(111,26,1,'21231',0,'Bourgogne',1),(112,31,1,'59350',2,'Nord-Pas-de-Calais',1),(113,41,1,'57463',0,'Lorraine',1),(114,42,1,'67482',1,'Alsace',1),(115,43,1,'25056',0,'Franche-Comté',1),(116,52,1,'44109',4,'Pays de la Loire',1),(117,53,1,'35238',0,'Bretagne',1),(118,54,1,'86194',2,'Poitou-Charentes',1),(119,72,1,'33063',1,'Aquitaine',1),(120,73,1,'31555',0,'Midi-Pyrénées',1),(121,74,1,'87085',2,'Limousin',1),(122,82,1,'69123',2,'Rhône-Alpes',1),(123,83,1,'63113',1,'Auvergne',1),(124,91,1,'34172',2,'Languedoc-Roussillon',1),(125,93,1,'13055',0,'Provence-Alpes-Côte d\'Azur',1),(126,94,1,'2A004',0,'Corse',1),(201,201,2,'',1,'Flandre',1),(202,202,2,'',2,'Wallonie',1),(203,203,2,'',3,'Bruxelles-Capitale',1),(1001,1001,10,'',0,'Ariana',1),(1002,1002,10,'',0,'Béja',1),(1003,1003,10,'',0,'Ben Arous',1),(1004,1004,10,'',0,'Bizerte',1),(1005,1005,10,'',0,'Gabès',1),(1006,1006,10,'',0,'Gafsa',1),(1007,1007,10,'',0,'Jendouba',1),(1008,1008,10,'',0,'Kairouan',1),(1009,1009,10,'',0,'Kasserine',1),(1010,1010,10,'',0,'Kébili',1),(1011,1011,10,'',0,'La Manouba',1),(1012,1012,10,'',0,'Le Kef',1),(1013,1013,10,'',0,'Mahdia',1),(1014,1014,10,'',0,'Médenine',1),(1015,1015,10,'',0,'Monastir',1),(1016,1016,10,'',0,'Nabeul',1),(1017,1017,10,'',0,'Sfax',1),(1018,1018,10,'',0,'Sidi Bouzid',1),(1019,1019,10,'',0,'Siliana',1),(1020,1020,10,'',0,'Sousse',1),(1021,1021,10,'',0,'Tataouine',1),(1022,1022,10,'',0,'Tozeur',1),(1023,1023,10,'',0,'Tunis',1),(1024,1024,10,'',0,'Zaghouan',1),(2801,2801,28,'',0,'Australia',1),(2802,401,4,'',1,'Andalucia',1),(2803,402,4,'',2,'Aragón',1),(2804,403,4,'',3,'Castilla y León',1),(2805,404,4,'',4,'Castilla la Mancha',1),(2806,405,4,'',5,'Canarias',1),(2807,406,4,'',6,'Cataluña',1),(2808,407,4,'',7,'Comunidad de Ceuta',1),(2809,408,4,'',8,'Comunidad Foral de Navarra',1),(2810,409,4,'',9,'Comunidad de Melilla',1),(2811,410,4,'',10,'Cantabria',1),(2812,411,4,'',11,'Comunidad Valenciana',1),(2813,412,4,'',12,'Extemadura',1),(2814,413,4,'',13,'Galicia',1),(2815,414,4,'',14,'Islas Baleares',1),(2816,415,4,'',15,'La Rioja',1),(2817,416,4,'',16,'Comunidad de Madrid',1),(2818,417,4,'',17,'Región de Murcia',1),(2819,418,4,'',18,'Principado de Asturias',1),(2820,419,4,'',19,'Pais Vasco',1),(2821,420,4,'',20,'Otros',1);
/*!40000 ALTER TABLE `llx_c_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_stcomm`
--

DROP TABLE IF EXISTS `llx_c_stcomm`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_stcomm` (
  `id` int(11) NOT NULL default '0',
  `code` varchar(12) NOT NULL default '',
  `libelle` varchar(30) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_stcomm`
--

LOCK TABLES `llx_c_stcomm` WRITE;
/*!40000 ALTER TABLE `llx_c_stcomm` DISABLE KEYS */;
INSERT INTO `llx_c_stcomm` VALUES (-1,'ST_NO','Ne pas contacter',1),(0,'ST_NEVER','Jamais contacté',1),(1,'ST_TODO','A contacter',1),(2,'ST_PEND','Contact en cours',1),(3,'ST_DONE','Contactée',1);
/*!40000 ALTER TABLE `llx_c_stcomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_tva`
--

DROP TABLE IF EXISTS `llx_c_tva`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_tva` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_pays` int(11) NOT NULL default '0',
  `taux` double NOT NULL default '0',
  `recuperableonly` int(11) NOT NULL default '0',
  `note` varchar(128) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=283 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_tva`
--

LOCK TABLES `llx_c_tva` WRITE;
/*!40000 ALTER TABLE `llx_c_tva` DISABLE KEYS */;
INSERT INTO `llx_c_tva` VALUES (11,1,19.6,0,'VAT Rate 19.6 (France hors DOM-TOM)',1),(12,1,8.5,0,'VAT Rate 8.5 (DOM sauf Guyane et Saint-Martin)',0),(13,1,8.5,1,'VAT Rate 8.5 (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par l\'acheteur',0),(14,1,5.5,0,'VAT Rate 5.5 (France hors DOM-TOM)',1),(15,1,0,0,'VAT Rate 0 ou non applicable',1),(16,1,2.1,0,'VAT Rate 2.1',1),(21,2,21,0,'VAT Rate 21',1),(22,2,6,0,'VAT Rate 6',1),(23,2,0,0,'VAT Rate 0 ou non applicable',1),(31,3,20,0,'VAT Rate 20',1),(32,3,10,0,'VAT Rate 10',1),(33,3,4,0,'VAT Rate 4',1),(34,3,0,0,'VAT Rate 0',1),(41,4,16,0,'VAT Rate 16',1),(42,4,7,0,'VAT Rate 7',1),(43,4,4,0,'VAT Rate 4',1),(44,4,0,0,'VAT Rate 0',1),(51,5,16,0,'VAT Rate 16',1),(52,5,7,0,'VAT Rate 7',1),(53,5,0,0,'VAT Rate 0',1),(61,6,7.6,0,'VAT Rate 7.6',1),(62,6,3.6,0,'VAT Rate 3.6',1),(63,6,2.4,0,'VAT Rate 2.4',1),(64,6,0,0,'VAT Rate 0',1),(71,7,17.5,0,'VAT Rate 17.5',1),(72,7,5,0,'VAT Rate 5',1),(73,7,0,0,'VAT Rate 0',1),(101,10,6,0,'TVA 6%',1),(102,10,12,0,'TVA 12%',1),(103,10,18,0,'VAT 18%',1),(104,10,7.5,0,'TVA 6% Majoré à 25% (7.5%)',1),(105,10,15,0,'TVA 12% Majoré à 25% (15%)',1),(106,10,22.5,0,'VAT 18% Majoré à 25% (22.5%)',1),(121,12,20,0,'VAT Rate 20',1),(122,12,14,0,'VAT Rate 14',1),(123,12,10,0,'VAT Rate 10',1),(124,12,7,0,'VAT Rate 7',1),(141,14,7,0,'VAT Rate 7',1),(142,14,0,0,'VAT Rate 0',1),(143,140,6,0,'VAT Rate 6',1),(144,140,3,0,'VAT Rate 3',1),(145,140,0,0,'VAT Rate 0',1),(171,17,19,0,'VAT Rate 19',1),(172,17,6,0,'VAT Rate 6',1),(173,17,0,0,'VAT Rate 0',1),(251,25,17,0,'VAT Rate 17',1),(252,25,12,0,'VAT Rate 12',1),(253,25,0,0,'VAT Rate 0',1),(281,28,10,0,'VAT Rate 10',1),(282,28,0,0,'VAT Rate 0',1);
/*!40000 ALTER TABLE `llx_c_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_contact`
--

DROP TABLE IF EXISTS `llx_c_type_contact`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_type_contact` (
  `rowid` int(11) NOT NULL default '0',
  `element` varchar(30) NOT NULL default '',
  `source` varchar(8) NOT NULL default 'external',
  `code` varchar(16) NOT NULL default '',
  `libelle` varchar(64) NOT NULL default '',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_c_type_contact_uk` (`element`,`source`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_type_contact`
--

LOCK TABLES `llx_c_type_contact` WRITE;
/*!40000 ALTER TABLE `llx_c_type_contact` DISABLE KEYS */;
INSERT INTO `llx_c_type_contact` VALUES (10,'contrat','internal','SALESREPSIGN','Commercial signataire du contrat',1),(11,'contrat','internal','SALESREPFOLL','Commercial suivi du contrat',1),(20,'contrat','external','BILLING','Contact client facturation contrat',1),(21,'contrat','external','CUSTOMER','Contact client suivi contrat',1),(22,'contrat','external','SALESREPSIGN','Contact client signataire contrat',1),(31,'propal','internal','SALESREPFOLL','Commercial à l\'origine de la propale',1),(40,'propal','external','BILLING','Contact client facturation propale',1),(41,'propal','external','CUSTOMER','Contact client suivi propale',1),(50,'facture','internal','SALESREPFOLL','Responsable suivi du paiement',1),(60,'facture','external','BILLING','Contact client facturation',1),(61,'facture','external','SHIPPING','Contact client livraison',1),(62,'facture','external','SERVICE','Contact client prestation',1),(80,'projet','internal','PROJECTLEADER','Chef de Projet',1),(81,'projet','external','PROJECTLEADER','Chef de Projet',1),(91,'commande','internal','SALESREPFOLL','Responsable suivi de la commande',1),(100,'commande','external','BILLING','Contact client facturation commande',1),(101,'commande','external','CUSTOMER','Contact client suivi commande',1),(102,'commande','external','SHIPPING','Contact client livraison commande',1),(120,'fichinter','internal','INTERREPFOLL','Responsable suivi de l\'intervention',1),(121,'fichinter','internal','INTERVENING','Intervenant',1),(130,'fichinter','external','BILLING','Contact client facturation intervention',1),(131,'fichinter','external','CUSTOMER','Contact client suivi de l\'intervention',1);
/*!40000 ALTER TABLE `llx_c_type_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_fees`
--

DROP TABLE IF EXISTS `llx_c_type_fees`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_type_fees` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(12) NOT NULL default '',
  `libelle` varchar(30) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_type_fees`
--

LOCK TABLES `llx_c_type_fees` WRITE;
/*!40000 ALTER TABLE `llx_c_type_fees` DISABLE KEYS */;
INSERT INTO `llx_c_type_fees` VALUES (1,'TF_OTHER','Other',1),(2,'TF_TRIP','Trip',1),(3,'TF_LUNCH','Lunch',1);
/*!40000 ALTER TABLE `llx_c_type_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_typent`
--

DROP TABLE IF EXISTS `llx_c_typent`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_c_typent` (
  `id` int(11) NOT NULL default '0',
  `code` varchar(12) NOT NULL default '',
  `libelle` varchar(30) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_c_typent`
--

LOCK TABLES `llx_c_typent` WRITE;
/*!40000 ALTER TABLE `llx_c_typent` DISABLE KEYS */;
INSERT INTO `llx_c_typent` VALUES (0,'TE_UNKNOWN','-',1),(1,'TE_STARTUP','Start-up',0),(2,'TE_GROUP','Grand groupe',1),(3,'TE_MEDIUM','PME/PMI',1),(4,'TE_SMALL','TPE',1),(5,'TE_ADMIN','Administration',1),(6,'TE_WHOLE','Grossiste',0),(7,'TE_RETAIL','Revendeur',0),(8,'TE_PRIVATE','Particulier',1),(100,'TE_OTHER','Autres',1);
/*!40000 ALTER TABLE `llx_c_typent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie`
--

DROP TABLE IF EXISTS `llx_categorie`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_categorie` (
  `rowid` int(11) NOT NULL auto_increment,
  `label` varchar(255) default NULL,
  `description` text,
  `visible` tinyint(4) NOT NULL default '1',
  `type` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_categorie_ref` (`label`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_categorie`
--

LOCK TABLES `llx_categorie` WRITE;
/*!40000 ALTER TABLE `llx_categorie` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_categorie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_association`
--

DROP TABLE IF EXISTS `llx_categorie_association`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_categorie_association` (
  `fk_categorie_mere` int(11) NOT NULL default '0',
  `fk_categorie_fille` int(11) NOT NULL default '0',
  KEY `idx_categorie_association_fk_categorie_mere` (`fk_categorie_mere`),
  KEY `idx_categorie_association_fk_categorie_fille` (`fk_categorie_fille`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_fille` FOREIGN KEY (`fk_categorie_fille`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_mere` FOREIGN KEY (`fk_categorie_mere`) REFERENCES `llx_categorie` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_categorie_association`
--

LOCK TABLES `llx_categorie_association` WRITE;
/*!40000 ALTER TABLE `llx_categorie_association` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_categorie_association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_fournisseur`
--

DROP TABLE IF EXISTS `llx_categorie_fournisseur`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_categorie_fournisseur` (
  `fk_categorie` int(11) NOT NULL default '0',
  `fk_societe` int(11) NOT NULL default '0',
  UNIQUE KEY `fk_categorie` (`fk_categorie`,`fk_societe`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_categorie_fournisseur`
--

LOCK TABLES `llx_categorie_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_categorie_fournisseur` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_categorie_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_product`
--

DROP TABLE IF EXISTS `llx_categorie_product`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_categorie_product` (
  `fk_categorie` int(11) NOT NULL default '0',
  `fk_product` int(11) NOT NULL default '0',
  PRIMARY KEY  (`fk_categorie`,`fk_product`),
  KEY `idx_categorie_product_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_product_fk_product` (`fk_product`),
  CONSTRAINT `fk_categorie_product_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_product_product_rowid` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_categorie_product`
--

LOCK TABLES `llx_categorie_product` WRITE;
/*!40000 ALTER TABLE `llx_categorie_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_categorie_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_societe`
--

DROP TABLE IF EXISTS `llx_categorie_societe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_categorie_societe` (
  `fk_categorie` int(11) NOT NULL default '0',
  `fk_societe` int(11) NOT NULL default '0',
  PRIMARY KEY  (`fk_categorie`,`fk_societe`),
  KEY `idx_categorie_societe_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_societe_fk_societe` (`fk_societe`),
  CONSTRAINT `fk_categorie_societe_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_societe_fk_soc` FOREIGN KEY (`fk_societe`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_categorie_societe`
--

LOCK TABLES `llx_categorie_societe` WRITE;
/*!40000 ALTER TABLE `llx_categorie_societe` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_categorie_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_chargesociales`
--

DROP TABLE IF EXISTS `llx_chargesociales`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_chargesociales` (
  `rowid` int(11) NOT NULL auto_increment,
  `date_ech` datetime NOT NULL default '0000-00-00 00:00:00',
  `libelle` varchar(80) NOT NULL default '',
  `fk_type` int(11) NOT NULL default '0',
  `amount` double NOT NULL default '0',
  `paye` smallint(6) NOT NULL default '0',
  `periode` date default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_chargesociales`
--

LOCK TABLES `llx_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_chargesociales` VALUES (1,'2002-05-15 00:00:00','Acompte 1er Trimestre 2002',1,120,0,'2002-01-01'),(2,'2002-05-15 00:00:00','Acompte 1er Trimestre 2002',2,200,0,'2002-01-01'),(3,'2002-05-15 00:00:00','Acompte 1er Trimestre 2002',3,170,0,'2002-01-01'),(4,'2002-02-15 00:00:00','Acompte 4ème Trimestre 2001',1,120,1,'2001-10-01'),(5,'2002-02-15 00:00:00','Acompte 4ème Trimestre 2001',2,200,1,'2001-10-01'),(6,'2002-02-15 00:00:00','Acompte 4ème Trimestre 2001',3,170,1,'2001-10-01'),(7,'2001-11-15 00:00:00','Acompte 3ème Trimestre 2001',1,70,1,'2001-07-01'),(8,'2001-11-15 00:00:00','Acompte 3ème Trimestre 2001',2,180,1,'2001-07-01'),(9,'2001-11-15 00:00:00','Acompte 3ème Trimestre 2001',3,150,1,'2001-07-01');
/*!40000 ALTER TABLE `llx_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_co_exp`
--

DROP TABLE IF EXISTS `llx_co_exp`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_co_exp` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) NOT NULL default '0',
  `fk_expedition` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  KEY `fk_commande` (`fk_commande`),
  KEY `fk_expedition` (`fk_expedition`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_co_exp`
--

LOCK TABLES `llx_co_exp` WRITE;
/*!40000 ALTER TABLE `llx_co_exp` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_co_exp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_co_fa`
--

DROP TABLE IF EXISTS `llx_co_fa`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_co_fa` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) NOT NULL default '0',
  `fk_facture` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  KEY `fk_commande` (`fk_commande`),
  KEY `fk_facture` (`fk_facture`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_co_fa`
--

LOCK TABLES `llx_co_fa` WRITE;
/*!40000 ALTER TABLE `llx_co_fa` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_co_fa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_co_liv`
--

DROP TABLE IF EXISTS `llx_co_liv`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_co_liv` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) NOT NULL default '0',
  `fk_livraison` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  KEY `fk_commande` (`fk_commande`),
  KEY `fk_livraison` (`fk_livraison`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_co_liv`
--

LOCK TABLES `llx_co_liv` WRITE;
/*!40000 ALTER TABLE `llx_co_liv` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_co_liv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_co_pr`
--

DROP TABLE IF EXISTS `llx_co_pr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_co_pr` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) default NULL,
  `fk_propale` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_co_pr`
--

LOCK TABLES `llx_co_pr` WRITE;
/*!40000 ALTER TABLE `llx_co_pr` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_co_pr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande`
--

DROP TABLE IF EXISTS `llx_commande`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_commande` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_soc` int(11) NOT NULL default '0',
  `fk_projet` int(11) default '0',
  `ref` varchar(30) NOT NULL default '',
  `ref_client` varchar(30) default NULL,
  `date_creation` datetime default NULL,
  `date_valid` datetime default NULL,
  `date_cloture` datetime default NULL,
  `date_commande` date default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_user_cloture` int(11) default NULL,
  `source` smallint(6) NOT NULL default '0',
  `fk_statut` smallint(6) default '0',
  `amount_ht` double default '0',
  `remise_percent` double default '0',
  `remise_absolue` double default '0',
  `remise` double default '0',
  `tva` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  `facture` tinyint(4) default '0',
  `fk_cond_reglement` int(11) default NULL,
  `fk_mode_reglement` int(11) default NULL,
  `date_livraison` date default NULL,
  `fk_adresse_livraison` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `ref` (`ref`),
  KEY `idx_commande_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_commande`
--

LOCK TABLES `llx_commande` WRITE;
/*!40000 ALTER TABLE `llx_commande` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_commande_fournisseur` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_soc` int(11) NOT NULL default '0',
  `fk_projet` int(11) default '0',
  `ref` varchar(30) NOT NULL default '',
  `date_creation` datetime default NULL,
  `date_valid` datetime default NULL,
  `date_cloture` datetime default NULL,
  `date_commande` date default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_user_cloture` int(11) default NULL,
  `source` smallint(6) NOT NULL default '0',
  `fk_statut` smallint(6) default '0',
  `amount_ht` double default '0',
  `remise_percent` double default '0',
  `remise` double default '0',
  `tva` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  `fk_methode_commande` int(11) default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_commande_fournisseur_ref` (`ref`,`fk_soc`),
  KEY `idx_commande_fournisseur_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fournisseur_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_commande_fournisseur`
--

LOCK TABLES `llx_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_dispatch`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_dispatch`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_commande_fournisseur_dispatch` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) default NULL,
  `fk_product` int(11) default NULL,
  `qty` float default NULL,
  `fk_entrepot` int(11) default NULL,
  `fk_user` int(11) default NULL,
  `datec` datetime default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_commande_fournisseur_dispatch_fk_commande` (`fk_commande`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_commande_fournisseur_dispatch`
--

LOCK TABLES `llx_commande_fournisseur_dispatch` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_dispatch` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande_fournisseur_dispatch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_log`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_commande_fournisseur_log` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datelog` datetime NOT NULL default '0000-00-00 00:00:00',
  `fk_commande` int(11) NOT NULL default '0',
  `fk_statut` smallint(6) NOT NULL default '0',
  `fk_user` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_commande_fournisseur_log`
--

LOCK TABLES `llx_commande_fournisseur_log` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseurdet`
--

DROP TABLE IF EXISTS `llx_commande_fournisseurdet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_commande_fournisseurdet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) NOT NULL default '0',
  `fk_product` int(11) default NULL,
  `ref` varchar(50) default NULL,
  `label` varchar(255) default NULL,
  `description` text,
  `tva_tx` double(6,3) default '0.000',
  `qty` double default NULL,
  `remise_percent` double default '0',
  `remise` double default '0',
  `subprice` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `total_tva` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `info_bits` int(11) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_commande_fournisseurdet`
--

LOCK TABLES `llx_commande_fournisseurdet` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commandedet`
--

DROP TABLE IF EXISTS `llx_commandedet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_commandedet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_commande` int(11) default NULL,
  `fk_product` int(11) default NULL,
  `description` text,
  `tva_tx` double(6,3) default NULL,
  `qty` double default NULL,
  `remise_percent` double default '0',
  `remise` double default '0',
  `fk_remise_except` int(11) default NULL,
  `price` double default NULL,
  `subprice` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `total_tva` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `info_bits` int(11) default '0',
  `marge_tx` double(6,3) default '0.000',
  `marque_tx` double(6,3) default '0.000',
  `special_code` tinyint(4) unsigned default '0',
  `rang` int(11) default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_commandedet_fk_commande` (`fk_commande`),
  CONSTRAINT `fk_commandedet_fk_commande` FOREIGN KEY (`fk_commande`) REFERENCES `llx_commande` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_commandedet`
--

LOCK TABLES `llx_commandedet` WRITE;
/*!40000 ALTER TABLE `llx_commandedet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commandedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta`
--

DROP TABLE IF EXISTS `llx_compta`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_compta` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `datev` date default NULL,
  `amount` double NOT NULL default '0',
  `label` varchar(255) default NULL,
  `fk_compta_account` int(11) default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `valid` tinyint(4) default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_compta`
--

LOCK TABLES `llx_compta` WRITE;
/*!40000 ALTER TABLE `llx_compta` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_compta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta_account`
--

DROP TABLE IF EXISTS `llx_compta_account`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_compta_account` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `number` varchar(12) default NULL,
  `label` varchar(255) default NULL,
  `fk_user_author` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_compta_account`
--

LOCK TABLES `llx_compta_account` WRITE;
/*!40000 ALTER TABLE `llx_compta_account` DISABLE KEYS */;
INSERT INTO `llx_compta_account` VALUES (3,'2008-08-19 21:35:31','431000','URSSAF',1,NULL),(4,'2008-08-19 21:35:31','654000','Clients',1,NULL);
/*!40000 ALTER TABLE `llx_compta_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta_compte_generaux`
--

DROP TABLE IF EXISTS `llx_compta_compte_generaux`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_compta_compte_generaux` (
  `rowid` int(11) NOT NULL auto_increment,
  `date_creation` datetime default NULL,
  `numero` varchar(50) default NULL,
  `intitule` varchar(255) default NULL,
  `fk_user_author` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_compta_compte_generaux`
--

LOCK TABLES `llx_compta_compte_generaux` WRITE;
/*!40000 ALTER TABLE `llx_compta_compte_generaux` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_compta_compte_generaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_cond_reglement`
--

DROP TABLE IF EXISTS `llx_cond_reglement`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_cond_reglement` (
  `rowid` int(11) NOT NULL default '0',
  `code` varchar(16) default NULL,
  `sortorder` smallint(6) default NULL,
  `active` tinyint(4) default '1',
  `libelle` varchar(255) default NULL,
  `libelle_facture` text,
  `fdm` tinyint(4) default NULL,
  `nbjour` smallint(6) default NULL,
  `decalage` smallint(6) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_cond_reglement`
--

LOCK TABLES `llx_cond_reglement` WRITE;
/*!40000 ALTER TABLE `llx_cond_reglement` DISABLE KEYS */;
INSERT INTO `llx_cond_reglement` VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL),(6,'PROFORMA',6,1,'Proforma','Réglement avant livraison',0,0,NULL);
/*!40000 ALTER TABLE `llx_cond_reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_const`
--

DROP TABLE IF EXISTS `llx_const`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_const` (
  `rowid` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `value` text,
  `type` enum('yesno','texte','chaine') default NULL,
  `visible` tinyint(4) NOT NULL default '1',
  `note` text,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=346 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_const`
--

LOCK TABLES `llx_const` WRITE;
/*!40000 ALTER TABLE `llx_const` DISABLE KEYS */;
INSERT INTO `llx_const` VALUES (4,'SYSLOG_FILE','dolibarr.log','chaine',0,'Directory where to write log file','2008-08-07 19:56:01'),(5,'SYSLOG_LEVEL','6','chaine',0,'Level of debug info to show','2008-08-07 19:56:01'),(10,'MAIN_FEATURES_LEVEL','0','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development','2008-08-07 19:56:01'),(11,'MAIN_FASTSEARCH_COMPANY','1','yesno',0,'Show form for quick company search','2008-08-07 19:56:01'),(12,'MAIN_FASTSEARCH_CONTACT','1','yesno',0,'Show form for quick contact search','2008-08-07 19:56:01'),(13,'MAIN_FASTSEARCH_PRODUCT','1','yesno',0,'Show form for quick product search','2008-08-07 19:56:01'),(21,'MAIN_DELAY_ACTIONS_TODO','7','chaine',0,'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées','2008-08-07 19:56:01'),(22,'MAIN_DELAY_ORDERS_TO_PROCESS','2','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes non traitées','2008-08-07 19:56:01'),(23,'MAIN_DELAY_PROPALS_TO_CLOSE','31','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales à cloturer','2008-08-07 19:56:01'),(24,'MAIN_DELAY_PROPALS_TO_BILL','7','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales non facturées','2008-08-07 19:56:01'),(25,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY','2','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées','2008-08-07 19:56:01'),(26,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED','31','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures client impayées','2008-08-07 19:56:01'),(27,'MAIN_DELAY_NOT_ACTIVATED_SERVICES','0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2008-08-07 19:56:01'),(28,'MAIN_DELAY_RUNNING_SERVICES','0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services expirés','2008-08-07 19:56:01'),(29,'MAIN_DELAY_MEMBERS','31','chaine',0,'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard','2008-08-07 19:56:01'),(30,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE','62','chaine',0,'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire','2008-08-07 19:56:01'),(31,'SOCIETE_NOLIST_COURRIER','1','yesno',0,'Liste les fichiers du repertoire courrier','2008-08-07 19:56:01'),(32,'SOCIETE_CODECLIENT_ADDON','mod_codeclient_leopard','yesno',0,'Module to control third parties codes','2008-08-07 19:56:01'),(33,'SOCIETE_CODECOMPTA_ADDON','mod_codecompta_panicum','yesno',0,'Module to control third parties codes','2008-08-07 19:56:01'),(34,'FACTURE_DISABLE_RECUR','1','yesno',0,'Desactivation facture recurrentes','2008-08-07 19:56:01'),(35,'ADHERENT_MAIL_REQUIRED','1','yesno',0,'Le mail est obligatoire pour créer un adhérent','2008-08-07 19:56:01'),(36,'ADHERENT_MAIL_FROM','adherents@domain.com','chaine',0,'From des mails adherents','2008-08-07 19:56:02'),(37,'ADHERENT_MAIL_RESIL','Votre adhesion vient d\'etre resiliee.\r\nNous esperons vous revoir tres bientot','texte',0,'Mail de Resiliation','2008-08-07 19:56:02'),(38,'ADHERENT_MAIL_VALID','Votre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFOS%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%DOL_MAIN_URL_ROOT%/public/adherents/','texte',0,'Mail de validation','2008-08-07 19:56:02'),(39,'ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%DOL_MAIN_URL_ROOT%/public/adherents/','texte',0,'Mail de validation de cotisation','2008-08-07 19:56:02'),(40,'ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée','chaine',0,'Sujet du mail de validation','2008-08-07 19:56:02'),(41,'ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion','chaine',0,'Sujet du mail de resiliation','2008-08-07 19:56:02'),(42,'ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine',0,'Sujet du mail de validation de cotisation','2008-08-07 19:56:02'),(43,'MAILING_EMAIL_FROM','dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2008-08-07 19:56:02'),(44,'ADHERENT_USE_MAILMAN','0','yesno',0,'Utilisation de Mailman','2008-08-07 19:56:02'),(45,'ADHERENT_MAILMAN_UNSUB_URL','http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%','chaine',0,'Url de desinscription aux listes mailman','2008-08-07 19:56:02'),(46,'ADHERENT_MAILMAN_URL','http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine',0,'Url pour les inscriptions mailman','2008-08-07 19:56:02'),(47,'ADHERENT_MAILMAN_LISTS','test-test,test-test2','chaine',0,'Listes auxquelles inscrire les nouveaux adherents','2008-08-07 19:56:02'),(48,'ADHERENT_MAILMAN_ADMINPW','','chaine',0,'Mot de passe Admin des liste mailman','2008-08-07 19:56:02'),(49,'ADHERENT_MAILMAN_SERVER','lists.domain.com','chaine',0,'Serveur hebergeant les interfaces d Admin des listes mailman','2008-08-07 19:56:02'),(50,'ADHERENT_MAILMAN_LISTS_COTISANT','','chaine',0,'Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement','2008-08-07 19:56:02'),(51,'ADHERENT_USE_SPIP','0','yesno',0,'Utilisation de SPIP ?','2008-08-07 19:56:02'),(52,'ADHERENT_USE_SPIP_AUTO','0','yesno',0,'Utilisation de SPIP automatiquement','2008-08-07 19:56:02'),(53,'ADHERENT_SPIP_USER','user','chaine',0,'user spip','2008-08-07 19:56:02'),(54,'ADHERENT_SPIP_PASS','pass','chaine',0,'Pass de connection','2008-08-07 19:56:02'),(55,'ADHERENT_SPIP_SERVEUR','localhost','chaine',0,'serveur spip','2008-08-07 19:56:02'),(56,'ADHERENT_SPIP_DB','spip','chaine',0,'db spip','2008-08-07 19:56:02'),(57,'ADHERENT_CARD_HEADER_TEXT','%ANNEE%','chaine',0,'Texte imprime sur le haut de la carte adherent','2008-08-07 19:56:02'),(58,'ADHERENT_CARD_FOOTER_TEXT','Association AZERTY','chaine',0,'Texte imprime sur le bas de la carte adherent','2008-08-07 19:56:02'),(59,'ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte',0,'Texte imprime sur la carte adherent','2008-08-07 19:56:02'),(60,'FCKEDITOR_ENABLE_USER','1','yesno',0,'Activation fckeditor sur notes utilisateurs','2008-08-07 19:56:02'),(61,'FCKEDITOR_ENABLE_SOCIETE','1','yesno',0,'Activation fckeditor sur notes societe','2008-08-07 19:56:02'),(62,'FCKEDITOR_ENABLE_PRODUCTDESC','1','yesno',0,'Activation fckeditor sur notes produits','2008-08-07 19:56:02'),(63,'FCKEDITOR_ENABLE_MEMBER','1','yesno',0,'Activation fckeditor sur notes adherent','2008-08-07 19:56:02'),(64,'FCKEDITOR_ENABLE_MAILING','1','yesno',0,'Activation fckeditor sur emailing','2008-08-07 19:56:02'),(65,'OSC_DB_HOST','localhost','chaine',0,'Host for OSC database for OSCommerce module 1','2008-08-07 19:56:02'),(66,'DON_ADDON_MODEL','html_cerfafr','chaine',0,NULL,'2008-08-07 19:56:02'),(67,'PROPALE_ADDON','mod_propale_marbre','chaine',0,NULL,'2008-08-07 19:56:02'),(68,'PROPALE_ADDON_PDF','azur','chaine',0,NULL,'2008-08-07 19:56:02'),(69,'COMMANDE_ADDON','mod_commande_marbre','chaine',0,NULL,'2008-08-07 19:56:02'),(70,'COMMANDE_ADDON_PDF','einstein','chaine',0,NULL,'2008-08-07 19:56:02'),(71,'COMMANDE_SUPPLIER_ADDON','mod_commande_fournisseur_muguet','chaine',0,NULL,'2008-08-07 19:56:02'),(72,'COMMANDE_SUPPLIER_ADDON_PDF','muscadet','chaine',0,NULL,'2008-08-07 19:56:02'),(73,'EXPEDITION_ADDON','enlevement','chaine',0,NULL,'2008-08-07 19:56:02'),(74,'EXPEDITION_ADDON_PDF','rouget','chaine',0,NULL,'2008-08-07 19:56:02'),(75,'FICHEINTER_ADDON','pacific','chaine',0,NULL,'2008-08-07 19:56:02'),(76,'FICHEINTER_ADDON_PDF','soleil','chaine',0,NULL,'2008-08-07 19:56:02'),(77,'FACTURE_ADDON','terre','chaine',0,NULL,'2008-08-07 19:56:02'),(78,'FACTURE_ADDON_PDF','crabe','chaine',0,NULL,'2008-08-07 19:56:02'),(79,'MAIN_FORCE_SETLOCALE_LC_ALL','','chaine',1,'Pour forcer LC_ALL si pb de locale','2008-08-07 19:56:02'),(80,'MAIN_FORCE_SETLOCALE_LC_TIME','','chaine',1,'Pour forcer LC_TIME si pb de locale','2008-08-07 19:56:02'),(81,'MAIN_FORCE_SETLOCALE_LC_MONETARY','','chaine',1,'Pour forcer LC_MONETARY si pb de locale','2008-08-07 19:56:02'),(82,'MAIN_FORCE_SETLOCALE_LC_NUMERIC','','chaine',1,'Mettre la valeur C si problème de centimes','2008-08-07 19:56:02'),(83,'PROPALE_VALIDITY_DURATION','15','chaine',0,'Durée de validitée des propales','2008-08-07 19:56:02'),(84,'GENBARCODE_LOCATION','/usr/local/bin/genbarcode','chaine',0,'location of genbarcode','2008-08-07 19:56:02'),(85,'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE','1','chaine',0,'','2008-08-07 19:56:03'),(86,'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE','1','chaine',0,'','2008-08-07 19:56:03'),(87,'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE','1','chaine',0,'','2008-08-07 19:56:03'),(88,'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL','1','chaine',0,'','2008-08-07 19:56:03'),(89,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE','1','chaine',0,'','2008-08-07 19:56:03'),(90,'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL','1','chaine',0,'','2008-08-07 19:56:03'),(91,'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE','1','chaine',0,'','2008-08-07 19:56:03'),(92,'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED','1','chaine',0,'','2008-08-07 19:56:03'),(93,'MAIN_AGENDA_ACTIONAUTO_BILL_CANCELED','1','chaine',0,'','2008-08-07 19:56:03'),(94,'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL','1','chaine',0,'','2008-08-07 19:56:03'),(95,'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE','1','chaine',0,'','2008-08-07 19:56:03'),(96,'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE','1','chaine',0,'','2008-08-07 19:56:03'),(98,'MAIN_VERSION_LAST_INSTALL','2.4-beta','chaine',0,'Dolibarr version for last install','2008-08-07 19:57:39'),(110,'MAIN_MODULE_COMMANDE','1',NULL,0,NULL,'2008-08-07 19:59:07'),(113,'MAIN_MODULE_TAX','1',NULL,0,NULL,'2008-08-07 19:59:11'),(114,'MAIN_MODULE_BANQUE','1',NULL,0,NULL,'2008-08-07 19:59:16'),(115,'MAIN_MODULE_FOURNISSEUR','1',NULL,0,NULL,'2008-08-07 19:59:19'),(119,'MAIN_MODULE_STOCK','1',NULL,0,NULL,'2008-08-07 19:59:25'),(123,'MAIN_MODULE_FCKEDITOR','1',NULL,0,NULL,'2008-08-07 19:59:35'),(124,'MAIN_MODULE_SYSLOG','1',NULL,0,NULL,'2008-08-07 19:59:39'),(126,'MAIN_MODULE_ECM','1',NULL,0,NULL,'2008-08-07 20:00:42'),(318,'MAIN_MENUFRONT_BARRELEFT','eldy_frontoffice','chaine',0,NULL,'2008-08-19 19:35:31'),(319,'MAIN_MENUFRONT_BARRETOP','eldy_frontoffice','chaine',0,NULL,'2008-08-19 19:35:31'),(320,'MAIN_MENU_BARRELEFT','eldy_backoffice','chaine',0,NULL,'2008-08-19 19:35:31'),(321,'MAIN_MENU_BARRETOP','eldy_backoffice','chaine',0,NULL,'2008-08-19 19:35:31'),(336,'MAIN_MODULE_FACTURE','1',NULL,0,NULL,'2008-08-19 19:38:19'),(337,'FAC_FORCE_DATE_VALIDATION','0','yesno',0,NULL,'2008-08-19 19:38:19'),(339,'MAIN_MODULE_EXPORT','1',NULL,0,NULL,'2008-08-19 19:38:42'),(340,'MAIN_MODULE_DEPLACEMENT','1',NULL,0,NULL,'2008-08-19 19:38:56'),(341,'MAIN_MODULE_MAILING','1',NULL,0,NULL,'2008-08-19 19:39:13'),(343,'MAIN_SECURITY_DISABLEFORGETPASSLINK','1','chaine',0,'','2008-08-19 19:44:19'),(344,'MAIN_UPLOAD_DOC','0','chaine',0,'','2008-08-19 19:44:39'),(345,'MAIN_LOGEVENTS_USER_LOGIN','1','chaine',0,'','2008-08-19 19:45:17'),(357,'MAIN_MODULE_AGENDA','1',NULL,0,NULL,'2008-08-24 22:03:20'),(359,'MAIN_MODULE_USER','1',NULL,0,NULL,'2008-08-24 22:03:33'),(360,'MAIN_VERSION_LAST_UPGRADE','2.4','chaine',0,'Dolibarr version for last upgrade','2008-08-24 22:03:33'),(361,'MAIN_MODULE_EXPEDITION','1',NULL,0,NULL,'2008-08-25 21:24:45'),(362,'LIVRAISON_ADDON_PDF','typhon','chaine',0,'Nom du gestionnaire de generation des commandes en PDF','2008-08-25 21:24:45'),(363,'LIVRAISON_ADDON','mod_livraison_jade','chaine',0,'Nom du gestionnaire de numerotation des bons de livraison','2008-08-25 21:24:45'),(364,'MAIN_MODULE_PROPALE','1',NULL,0,NULL,'2008-08-25 21:24:45'),(370,'MAIN_MODULE_COMMERCIAL','1',NULL,0,NULL,'2008-08-25 21:24:48'),(371,'MAIN_MODULE_SOCIETE','1',NULL,0,NULL,'2008-08-25 21:24:48'),(372,'MAIN_MODULE_CONTRAT','1',NULL,0,NULL,'2008-08-25 21:24:53'),(373,'MAIN_MODULE_SERVICE','1',NULL,0,NULL,'2008-08-25 21:24:53'),(374,'MAIN_MODULE_PRODUIT','1',NULL,0,NULL,'2008-08-25 21:24:53'),(375,'MAIN_INFO_SOCIETE_NOM','Barridol','chaine',0,'','2008-08-26 08:43:26'),(376,'MAIN_INFO_SOCIETE_ADRESSE','10 road street','chaine',0,'','2008-08-26 08:43:26'),(377,'MAIN_INFO_SOCIETE_VILLE','BigTown','chaine',0,'','2008-08-26 08:43:26'),(378,'MAIN_INFO_SOCIETE_CP','75000','chaine',0,'','2008-08-26 08:43:26'),(379,'MAIN_INFO_SOCIETE_PAYS','1','chaine',0,'','2008-08-26 08:43:26'),(380,'MAIN_MONNAIE','EUR','chaine',0,'','2008-08-26 08:43:26'),(381,'MAIN_INFO_SOCIETE_TEL','01 02 03 04 05','chaine',0,'','2008-08-26 08:43:26'),(382,'MAIN_INFO_SOCIETE_FAX','01 02 03 04 06','chaine',0,'','2008-08-26 08:43:26'),(383,'MAIN_INFO_SOCIETE_MAIL','mycompany@mycompany.com','chaine',0,'','2008-08-26 08:43:26'),(384,'MAIN_INFO_SOCIETE_WEB','www.dolibarr.org','chaine',0,'','2008-08-26 08:43:26'),(385,'MAIN_INFO_SOCIETE_LOGO','dolibarr_logo2.png','chaine',0,'','2008-08-26 08:43:26'),(386,'MAIN_INFO_SOCIETE_LOGO_SMALL','dolibarr_logo2_small.png','chaine',0,'','2008-08-26 08:43:26'),(387,'MAIN_INFO_SOCIETE_LOGO_MINI','dolibarr_logo2_mini.png','chaine',0,'','2008-08-26 08:43:26'),(388,'MAIN_INFO_CAPITAL','15000','chaine',0,'','2008-08-26 08:43:26'),(389,'MAIN_INFO_SOCIETE_FORME_JURIDIQUE','0','chaine',0,'','2008-08-26 08:43:26'),(390,'MAIN_INFO_SIREN','123456789','chaine',0,'','2008-08-26 08:43:26'),(391,'MAIN_INFO_SIRET','123456789001','chaine',0,'','2008-08-26 08:43:26'),(392,'MAIN_INFO_APE','721Z','chaine',0,'','2008-08-26 08:43:26'),(393,'MAIN_INFO_TVAINTRA','12345679012345','chaine',0,'','2008-08-26 08:43:26'),(394,'SOCIETE_FISCAL_MONTH_START','0','chaine',0,'','2008-08-26 08:43:26'),(395,'FACTURE_TVAOPTION','reel','chaine',0,'','2008-08-26 08:43:26'),(407,'MAIN_LANG_DEFAULT','auto','chaine',0,'','2008-08-29 18:29:34'),(408,'MAIN_MULTILANGS','0','chaine',0,'','2008-08-29 18:29:34'),(409,'MAIN_SIZE_LISTE_LIMIT','25','chaine',0,'','2008-08-29 18:29:34'),(410,'MAIN_DISABLE_JAVASCRIPT','0','chaine',0,'','2008-08-29 18:29:34'),(411,'MAIN_POPUP_CALENDAR','eldy','chaine',0,'','2008-08-29 18:29:34'),(412,'MAIN_THEME','eldy','chaine',0,'','2008-08-29 18:29:34'),(413,'MAIN_SEARCHFORM_CONTACT','1','chaine',0,'','2008-08-29 18:29:34'),(414,'MAIN_SEARCHFORM_SOCIETE','1','chaine',0,'','2008-08-29 18:29:34'),(415,'MAIN_SEARCHFORM_PRODUITSERVICE','1','chaine',0,'','2008-08-29 18:29:34'),(416,'MAIN_MOTD','<br />','chaine',0,'','2008-08-29 18:29:34'),(417,'MAIN_HOME','Login: <strong>demo</strong><br />Password: <strong>demo</strong><br />','chaine',0,'','2008-08-29 18:29:34'),(418,'MAIN_DEMO','1','chaine',1,'','2008-08-29 19:42:33'),(419,'MAIN_MAIL_EMAIL_FROM','dolibarr-robot@domain.com','chaine',0,'','2008-08-29 19:43:20'),(420,'MAIN_DISABLE_ALL_MAILS','1','chaine',0,'','2008-08-29 19:43:20'),(421,'SYSTEMTOOLS_MYSQLDUMP','mysqldump','chaine',0,'','2008-08-29 19:45:42');
/*!40000 ALTER TABLE `llx_const` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contrat`
--

DROP TABLE IF EXISTS `llx_contrat`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_contrat` (
  `rowid` int(11) NOT NULL auto_increment,
  `ref` varchar(30) default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `date_contrat` datetime default NULL,
  `statut` smallint(6) default '0',
  `mise_en_service` datetime default NULL,
  `fin_validite` datetime default NULL,
  `date_cloture` datetime default NULL,
  `fk_soc` int(11) NOT NULL default '0',
  `fk_projet` int(11) default NULL,
  `fk_commercial_signature` int(11) NOT NULL default '0',
  `fk_commercial_suivi` int(11) NOT NULL default '0',
  `fk_user_author` int(11) NOT NULL default '0',
  `fk_user_mise_en_service` int(11) default NULL,
  `fk_user_cloture` int(11) default NULL,
  `note` text,
  `note_public` text,
  PRIMARY KEY  (`rowid`),
  KEY `idx_contrat_fk_soc` (`fk_soc`),
  KEY `idx_contrat_fk_user_author` (`fk_user_author`),
  CONSTRAINT `fk_contrat_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_contrat_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_contrat`
--

LOCK TABLES `llx_contrat` WRITE;
/*!40000 ALTER TABLE `llx_contrat` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_contrat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contratdet`
--

DROP TABLE IF EXISTS `llx_contratdet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_contratdet` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_contrat` int(11) NOT NULL default '0',
  `fk_product` int(11) default NULL,
  `statut` smallint(6) default '0',
  `label` text,
  `description` text,
  `fk_remise_except` int(11) default NULL,
  `date_commande` datetime default NULL,
  `date_ouverture_prevue` datetime default NULL,
  `date_ouverture` datetime default NULL,
  `date_fin_validite` datetime default NULL,
  `date_cloture` datetime default NULL,
  `tva_tx` double(6,3) default '0.000',
  `qty` double NOT NULL default '0',
  `remise_percent` double default '0',
  `subprice` double(24,8) default '0.00000000',
  `price_ht` double default NULL,
  `remise` double default '0',
  `total_ht` double(24,8) default '0.00000000',
  `total_tva` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `info_bits` int(11) default '0',
  `fk_user_author` int(11) NOT NULL default '0',
  `fk_user_ouverture` int(11) default NULL,
  `fk_user_cloture` int(11) default NULL,
  `commentaire` text,
  PRIMARY KEY  (`rowid`),
  KEY `idx_contratdet_fk_contrat` (`fk_contrat`),
  KEY `idx_contratdet_fk_product` (`fk_product`),
  KEY `idx_contratdet_date_ouverture_prevue` (`date_ouverture_prevue`),
  KEY `idx_contratdet_date_ouverture` (`date_ouverture`),
  KEY `idx_contratdet_date_fin_validite` (`date_fin_validite`),
  CONSTRAINT `fk_contratdet_fk_contrat` FOREIGN KEY (`fk_contrat`) REFERENCES `llx_contrat` (`rowid`),
  CONSTRAINT `fk_contratdet_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_contratdet`
--

LOCK TABLES `llx_contratdet` WRITE;
/*!40000 ALTER TABLE `llx_contratdet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_contratdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contratdet_log`
--

DROP TABLE IF EXISTS `llx_contratdet_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_contratdet_log` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_contratdet` int(11) NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `statut` smallint(6) NOT NULL default '0',
  `fk_user_author` int(11) NOT NULL default '0',
  `commentaire` text,
  PRIMARY KEY  (`rowid`),
  KEY `idx_contratdet_log_fk_contratdet` (`fk_contratdet`),
  KEY `idx_contratdet_log_date` (`date`),
  CONSTRAINT `fk_contratdet_log_fk_contratdet` FOREIGN KEY (`fk_contratdet`) REFERENCES `llx_contratdet` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_contratdet_log`
--

LOCK TABLES `llx_contratdet_log` WRITE;
/*!40000 ALTER TABLE `llx_contratdet_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_contratdet_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_cotisation`
--

DROP TABLE IF EXISTS `llx_cotisation`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_cotisation` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `fk_adherent` int(11) default NULL,
  `dateadh` datetime default NULL,
  `datef` date default NULL,
  `cotisation` double default NULL,
  `fk_bank` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_cotisation` (`fk_adherent`,`dateadh`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_cotisation`
--

LOCK TABLES `llx_cotisation` WRITE;
/*!40000 ALTER TABLE `llx_cotisation` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_cotisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_deplacement`
--

DROP TABLE IF EXISTS `llx_deplacement`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_deplacement` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime NOT NULL default '0000-00-00 00:00:00',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `dated` datetime default NULL,
  `fk_user` int(11) NOT NULL default '0',
  `fk_user_author` int(11) default NULL,
  `type` varchar(12) NOT NULL default '',
  `km` double default NULL,
  `fk_soc` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_deplacement`
--

LOCK TABLES `llx_deplacement` WRITE;
/*!40000 ALTER TABLE `llx_deplacement` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_deplacement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document`
--

DROP TABLE IF EXISTS `llx_document`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_document` (
  `rowid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `file_name` varchar(255) NOT NULL default '',
  `file_extension` varchar(5) NOT NULL default '',
  `date_generation` datetime default NULL,
  `fk_owner` int(11) default NULL,
  `fk_group` int(11) default NULL,
  `permissions` varchar(9) default 'rw-rw-rw',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_document`
--

LOCK TABLES `llx_document` WRITE;
/*!40000 ALTER TABLE `llx_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document_generator`
--

DROP TABLE IF EXISTS `llx_document_generator`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_document_generator` (
  `rowid` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `classfile` varchar(255) NOT NULL default '',
  `class` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_document_generator`
--

LOCK TABLES `llx_document_generator` WRITE;
/*!40000 ALTER TABLE `llx_document_generator` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_document_generator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document_model`
--

DROP TABLE IF EXISTS `llx_document_model`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_document_model` (
  `rowid` int(11) NOT NULL auto_increment,
  `nom` varchar(50) default NULL,
  `type` varchar(20) NOT NULL default '',
  `libelle` varchar(255) default NULL,
  `description` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_document_model` (`nom`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_document_model`
--

LOCK TABLES `llx_document_model` WRITE;
/*!40000 ALTER TABLE `llx_document_model` DISABLE KEYS */;
INSERT INTO `llx_document_model` VALUES (1,'azur','propal',NULL,NULL),(2,'einstein','order',NULL,NULL),(3,'crabe','invoice',NULL,NULL);
/*!40000 ALTER TABLE `llx_document_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_dolibarr_modules`
--

DROP TABLE IF EXISTS `llx_dolibarr_modules`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_dolibarr_modules` (
  `numero` int(11) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '0',
  `active_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `active_version` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_dolibarr_modules`
--

LOCK TABLES `llx_dolibarr_modules` WRITE;
/*!40000 ALTER TABLE `llx_dolibarr_modules` DISABLE KEYS */;
INSERT INTO `llx_dolibarr_modules` VALUES (0,1,'2008-08-07 21:57:39','1.23'),(1,1,'2008-08-19 21:38:19','1.61'),(2,1,'2008-08-07 21:59:07','1.33'),(20,1,'2008-08-07 21:59:02','1.57'),(22,1,'2008-08-19 21:39:13','dolibarr'),(25,1,'2008-08-07 21:59:07','1.59'),(30,1,'2008-08-19 21:38:19','1.82'),(40,1,'2008-08-07 21:59:19','1.60'),(42,1,'2008-08-07 21:59:39','dolibarr'),(50,1,'2008-08-07 21:59:27','1.50'),(52,1,'2008-08-07 21:59:25','1.24'),(53,1,'2008-08-07 21:59:27','1.30'),(75,1,'2008-08-19 21:38:56','1.13'),(85,1,'2008-08-07 21:59:16','1.46'),(240,1,'2008-08-19 21:38:42','dolibarr'),(500,1,'2008-08-07 21:59:11','1.10'),(2000,1,'2008-08-07 21:59:35','dolibarr'),(2400,1,'2008-08-07 21:59:53','dolibarr'),(2500,1,'2008-08-07 22:00:42','dolibarr');
/*!40000 ALTER TABLE `llx_dolibarr_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_domain`
--

DROP TABLE IF EXISTS `llx_domain`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_domain` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `label` varchar(255) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_domain`
--

LOCK TABLES `llx_domain` WRITE;
/*!40000 ALTER TABLE `llx_domain` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_don`
--

DROP TABLE IF EXISTS `llx_don`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_don` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_statut` smallint(6) NOT NULL default '0',
  `datec` datetime default NULL,
  `datedon` datetime default NULL,
  `amount` double default '0',
  `fk_paiement` int(11) default NULL,
  `prenom` varchar(50) default NULL,
  `nom` varchar(50) default NULL,
  `societe` varchar(50) default NULL,
  `adresse` text,
  `cp` varchar(30) default NULL,
  `ville` varchar(50) default NULL,
  `pays` varchar(50) default NULL,
  `email` varchar(255) default NULL,
  `public` smallint(6) NOT NULL default '1',
  `fk_don_projet` int(11) NOT NULL default '0',
  `fk_user_author` int(11) NOT NULL default '0',
  `fk_user_valid` int(11) NOT NULL default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_don`
--

LOCK TABLES `llx_don` WRITE;
/*!40000 ALTER TABLE `llx_don` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_don` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_don_projet`
--

DROP TABLE IF EXISTS `llx_don_projet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_don_projet` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `libelle` varchar(255) default NULL,
  `fk_user_author` int(11) NOT NULL default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_don_projet`
--

LOCK TABLES `llx_don_projet` WRITE;
/*!40000 ALTER TABLE `llx_don_projet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_don_projet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_droitpret_rapport`
--

DROP TABLE IF EXISTS `llx_droitpret_rapport`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_droitpret_rapport` (
  `rowid` int(11) NOT NULL auto_increment,
  `date_envoie` datetime NOT NULL default '0000-00-00 00:00:00',
  `format` varchar(10) NOT NULL default '',
  `date_debut` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin` datetime NOT NULL default '0000-00-00 00:00:00',
  `fichier` varchar(255) NOT NULL default '',
  `nbfact` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_droitpret_rapport`
--

LOCK TABLES `llx_droitpret_rapport` WRITE;
/*!40000 ALTER TABLE `llx_droitpret_rapport` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_droitpret_rapport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_directories`
--

DROP TABLE IF EXISTS `llx_ecm_directories`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_ecm_directories` (
  `rowid` int(11) NOT NULL auto_increment,
  `label` varchar(32) NOT NULL default '',
  `fk_parent` int(11) default NULL,
  `description` varchar(255) NOT NULL default '',
  `cachenbofdoc` int(11) NOT NULL default '0',
  `date_c` datetime default NULL,
  `date_m` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_user_c` int(11) default NULL,
  `fk_user_m` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_ecm_directories`
--

LOCK TABLES `llx_ecm_directories` WRITE;
/*!40000 ALTER TABLE `llx_ecm_directories` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_ecm_directories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_document`
--

DROP TABLE IF EXISTS `llx_ecm_document`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_ecm_document` (
  `rowid` int(11) NOT NULL auto_increment,
  `ref` varchar(16) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `filesize` int(11) NOT NULL default '0',
  `filemime` varchar(32) NOT NULL default '',
  `fullpath_dol` varchar(255) NOT NULL default '',
  `fullpath_orig` varchar(255) NOT NULL default '',
  `description` text,
  `manualkeyword` text,
  `fk_create` int(11) NOT NULL default '0',
  `fk_update` int(11) default NULL,
  `date_c` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_u` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_directory` int(11) default NULL,
  `fk_status` smallint(6) default '0',
  `private` smallint(6) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_ecm_document`
--

LOCK TABLES `llx_ecm_document` WRITE;
/*!40000 ALTER TABLE `llx_ecm_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_ecm_document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_contact`
--

DROP TABLE IF EXISTS `llx_element_contact`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_element_contact` (
  `rowid` int(11) NOT NULL auto_increment,
  `datecreate` datetime default NULL,
  `statut` smallint(6) default '5',
  `element_id` int(11) NOT NULL default '0',
  `fk_c_type_contact` int(11) NOT NULL default '0',
  `fk_socpeople` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_element_contact_idx1` (`element_id`,`fk_c_type_contact`,`fk_socpeople`),
  KEY `fk_element_contact_fk_c_type_contact` (`fk_c_type_contact`),
  KEY `idx_element_contact_fk_socpeople` (`fk_socpeople`),
  CONSTRAINT `fk_element_contact_fk_c_type_contact` FOREIGN KEY (`fk_c_type_contact`) REFERENCES `llx_c_type_contact` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_element_contact`
--

LOCK TABLES `llx_element_contact` WRITE;
/*!40000 ALTER TABLE `llx_element_contact` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_element`
--

DROP TABLE IF EXISTS `llx_element_element`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_element_element` (
  `rowid` int(11) NOT NULL auto_increment,
  `sourceid` int(11) NOT NULL default '0',
  `sourcetype` varchar(16) NOT NULL default '',
  `targetid` int(11) NOT NULL default '0',
  `targettype` varchar(16) NOT NULL default '',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_element_element_idx1` (`sourceid`,`sourcetype`,`targetid`,`targettype`),
  KEY `idx_element_element_targetid` (`targetid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_element_element`
--

LOCK TABLES `llx_element_element` WRITE;
/*!40000 ALTER TABLE `llx_element_element` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_element` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_energie_compteur`
--

DROP TABLE IF EXISTS `llx_energie_compteur`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_energie_compteur` (
  `rowid` int(11) NOT NULL auto_increment,
  `libelle` varchar(50) default NULL,
  `fk_energie` int(11) NOT NULL default '0',
  `datec` datetime default NULL,
  `fk_user_author` int(11) NOT NULL default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_energie_compteur`
--

LOCK TABLES `llx_energie_compteur` WRITE;
/*!40000 ALTER TABLE `llx_energie_compteur` DISABLE KEYS */;
INSERT INTO `llx_energie_compteur` VALUES (1,'EDF Vitré',1,'2008-08-19 21:35:31',1,''),(2,'Eau Vitré',2,'2008-08-19 21:35:31',1,''),(3,'Gaz Vitré',3,'2008-08-19 21:35:31',1,'');
/*!40000 ALTER TABLE `llx_energie_compteur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_energie_compteur_groupe`
--

DROP TABLE IF EXISTS `llx_energie_compteur_groupe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_energie_compteur_groupe` (
  `fk_energie_compteur` int(11) NOT NULL default '0',
  `fk_energie_groupe` int(11) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_energie_compteur_groupe`
--

LOCK TABLES `llx_energie_compteur_groupe` WRITE;
/*!40000 ALTER TABLE `llx_energie_compteur_groupe` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_energie_compteur_groupe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_energie_compteur_releve`
--

DROP TABLE IF EXISTS `llx_energie_compteur_releve`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_energie_compteur_releve` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_compteur` int(11) NOT NULL default '0',
  `date_releve` datetime default NULL,
  `valeur` double default NULL,
  `datec` datetime default NULL,
  `fk_user_author` int(11) NOT NULL default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_energie_compteur_releve`
--

LOCK TABLES `llx_energie_compteur_releve` WRITE;
/*!40000 ALTER TABLE `llx_energie_compteur_releve` DISABLE KEYS */;
INSERT INTO `llx_energie_compteur_releve` VALUES (1,1,'2005-07-26 00:00:00',1139,NULL,0,NULL),(2,1,'2005-07-21 00:00:00',1129,NULL,0,NULL),(3,1,'2005-07-16 00:00:00',1128,NULL,0,NULL),(4,1,'2005-07-11 00:00:00',1128,NULL,0,NULL),(5,1,'2005-07-06 00:00:00',1128,NULL,0,NULL),(6,1,'2005-07-01 00:00:00',1127,NULL,0,NULL),(7,1,'2005-06-26 00:00:00',1127,NULL,0,NULL),(8,1,'2005-06-21 00:00:00',1126,NULL,0,NULL),(9,1,'2005-06-16 00:00:00',1116,NULL,0,NULL),(10,1,'2005-06-11 00:00:00',1107,NULL,0,NULL),(11,1,'2005-06-06 00:00:00',1097,NULL,0,NULL),(12,1,'2005-06-01 00:00:00',1087,NULL,0,NULL),(13,1,'2005-05-26 00:00:00',1078,NULL,0,NULL),(14,1,'2005-05-21 00:00:00',1068,NULL,0,NULL),(15,1,'2005-05-16 00:00:00',1059,NULL,0,NULL),(16,1,'2005-05-11 00:00:00',1049,NULL,0,NULL),(17,1,'2005-05-06 00:00:00',1038,NULL,0,NULL),(18,1,'2005-05-01 00:00:00',1028,NULL,0,NULL),(19,1,'2005-04-26 00:00:00',1013,NULL,0,NULL),(20,1,'2005-04-21 00:00:00',1003,NULL,0,NULL),(21,1,'2005-04-16 00:00:00',984,NULL,0,NULL),(22,1,'2005-04-11 00:00:00',965,NULL,0,NULL),(23,1,'2005-04-06 00:00:00',945,NULL,0,NULL),(24,1,'2005-04-01 00:00:00',926,NULL,0,NULL),(25,1,'2005-03-26 00:00:00',906,NULL,0,NULL),(26,1,'2005-03-21 00:00:00',884,NULL,0,NULL),(27,1,'2005-03-16 00:00:00',862,NULL,0,NULL),(28,1,'2005-03-11 00:00:00',841,NULL,0,NULL),(29,1,'2005-03-06 00:00:00',828,NULL,0,NULL),(30,1,'2005-03-01 00:00:00',807,NULL,0,NULL),(31,1,'2005-02-26 00:00:00',785,NULL,0,NULL),(32,1,'2005-02-21 00:00:00',760,NULL,0,NULL),(33,1,'2005-02-16 00:00:00',737,NULL,0,NULL),(34,1,'2005-02-11 00:00:00',713,NULL,0,NULL),(35,1,'2005-02-06 00:00:00',688,NULL,0,NULL),(36,1,'2005-02-01 00:00:00',662,NULL,0,NULL),(37,1,'2005-01-26 00:00:00',637,NULL,0,NULL),(38,1,'2005-01-21 00:00:00',605,NULL,0,NULL),(39,1,'2005-01-16 00:00:00',575,NULL,0,NULL),(40,1,'2005-01-11 00:00:00',545,NULL,0,NULL),(41,1,'2005-01-06 00:00:00',510,NULL,0,NULL),(42,1,'2005-01-01 00:00:00',480,NULL,0,NULL);
/*!40000 ALTER TABLE `llx_energie_compteur_releve` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_energie_groupe`
--

DROP TABLE IF EXISTS `llx_energie_groupe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_energie_groupe` (
  `rowid` int(11) NOT NULL auto_increment,
  `libelle` varchar(100) default NULL,
  `datec` datetime default NULL,
  `fk_user_author` int(11) NOT NULL default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_energie_groupe`
--

LOCK TABLES `llx_energie_groupe` WRITE;
/*!40000 ALTER TABLE `llx_energie_groupe` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_energie_groupe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entrepot`
--

DROP TABLE IF EXISTS `llx_entrepot`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_entrepot` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL default '',
  `description` text,
  `lieu` varchar(64) default NULL,
  `address` varchar(255) default NULL,
  `cp` varchar(10) default NULL,
  `ville` varchar(50) default NULL,
  `fk_pays` int(11) default '0',
  `statut` tinyint(4) default '1',
  `valo_pmp` float(12,4) default NULL,
  `fk_user_author` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_entrepot`
--

LOCK TABLES `llx_entrepot` WRITE;
/*!40000 ALTER TABLE `llx_entrepot` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_entrepot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entrepot_valorisation`
--

DROP TABLE IF EXISTS `llx_entrepot_valorisation`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_entrepot_valorisation` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_calcul` date default NULL,
  `fk_entrepot` int(10) unsigned NOT NULL default '0',
  `valo_pmp` float(12,4) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_entrepot_valorisation`
--

LOCK TABLES `llx_entrepot_valorisation` WRITE;
/*!40000 ALTER TABLE `llx_entrepot_valorisation` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_entrepot_valorisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_events`
--

DROP TABLE IF EXISTS `llx_events`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_events` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `type` varchar(32) NOT NULL default '',
  `dateevent` datetime default NULL,
  `fk_user` int(11) default NULL,
  `description` varchar(250) NOT NULL default '',
  `ip` varchar(32) NOT NULL default '',
  `fk_object` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_events_dateevent` (`dateevent`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_events`
--

LOCK TABLES `llx_events` WRITE;
/*!40000 ALTER TABLE `llx_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition`
--

DROP TABLE IF EXISTS `llx_expedition`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_expedition` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL default '',
  `fk_soc` int(11) NOT NULL default '0',
  `date_creation` datetime default NULL,
  `fk_user_author` int(11) default NULL,
  `date_valid` datetime default NULL,
  `fk_user_valid` int(11) default NULL,
  `date_expedition` date default NULL,
  `fk_adresse_livraison` int(11) default NULL,
  `fk_expedition_methode` int(11) default NULL,
  `fk_statut` smallint(6) default '0',
  `note` text,
  `model_pdf` varchar(50) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_expedition_uk_ref` (`ref`),
  KEY `idx_expedition_fk_soc` (`fk_soc`),
  KEY `idx_expedition_fk_user_author` (`fk_user_author`),
  KEY `idx_expedition_fk_user_valid` (`fk_user_valid`),
  KEY `idx_expedition_fk_adresse_livraison` (`fk_adresse_livraison`),
  KEY `idx_expedition_fk_expedition_methode` (`fk_expedition_methode`),
  CONSTRAINT `fk_expedition_fk_adresse_livraison` FOREIGN KEY (`fk_adresse_livraison`) REFERENCES `llx_societe_adresse_livraison` (`rowid`),
  CONSTRAINT `fk_expedition_fk_expedition_methode` FOREIGN KEY (`fk_expedition_methode`) REFERENCES `llx_expedition_methode` (`rowid`),
  CONSTRAINT `fk_expedition_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_expedition`
--

LOCK TABLES `llx_expedition` WRITE;
/*!40000 ALTER TABLE `llx_expedition` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_expedition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition_methode`
--

DROP TABLE IF EXISTS `llx_expedition_methode`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_expedition_methode` (
  `rowid` int(11) NOT NULL default '0',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `code` varchar(30) NOT NULL default '',
  `libelle` varchar(50) NOT NULL default '',
  `description` text,
  `statut` tinyint(4) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_expedition_methode`
--

LOCK TABLES `llx_expedition_methode` WRITE;
/*!40000 ALTER TABLE `llx_expedition_methode` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_expedition_methode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expeditiondet`
--

DROP TABLE IF EXISTS `llx_expeditiondet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_expeditiondet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_expedition` int(11) NOT NULL default '0',
  `fk_origin_line` int(11) default NULL,
  `fk_entrepot` int(11) default NULL,
  `qty` double default NULL,
  `rang` int(11) default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_expeditiondet_fk_expedition` (`fk_expedition`),
  CONSTRAINT `fk_expeditiondet_fk_expedition` FOREIGN KEY (`fk_expedition`) REFERENCES `llx_expedition` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_expeditiondet`
--

LOCK TABLES `llx_expeditiondet` WRITE;
/*!40000 ALTER TABLE `llx_expeditiondet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_expeditiondet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_export_compta`
--

DROP TABLE IF EXISTS `llx_export_compta`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_export_compta` (
  `rowid` int(11) NOT NULL auto_increment,
  `ref` varchar(12) NOT NULL default '',
  `date_export` datetime NOT NULL default '0000-00-00 00:00:00',
  `fk_user` int(11) NOT NULL default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_export_compta`
--

LOCK TABLES `llx_export_compta` WRITE;
/*!40000 ALTER TABLE `llx_export_compta` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_export_compta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_export_model`
--

DROP TABLE IF EXISTS `llx_export_model`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_export_model` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_user` int(11) NOT NULL default '0',
  `label` varchar(50) NOT NULL default '',
  `type` varchar(20) NOT NULL default '',
  `field` text NOT NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_export_model` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_export_model`
--

LOCK TABLES `llx_export_model` WRITE;
/*!40000 ALTER TABLE `llx_export_model` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_export_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fa_pr`
--

DROP TABLE IF EXISTS `llx_fa_pr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_fa_pr` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture` int(11) default NULL,
  `fk_propal` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_fa_pr`
--

LOCK TABLES `llx_fa_pr` WRITE;
/*!40000 ALTER TABLE `llx_fa_pr` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fa_pr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture`
--

DROP TABLE IF EXISTS `llx_facture`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facture` (
  `rowid` int(11) NOT NULL auto_increment,
  `facnumber` varchar(30) NOT NULL default '',
  `type` smallint(6) NOT NULL default '0',
  `ref_client` varchar(30) default NULL,
  `increment` varchar(10) default NULL,
  `fk_soc` int(11) NOT NULL default '0',
  `datec` datetime default NULL,
  `datef` date default NULL,
  `date_valid` date default NULL,
  `paye` smallint(6) NOT NULL default '0',
  `amount` double NOT NULL default '0',
  `remise_percent` double default '0',
  `remise_absolue` double default '0',
  `remise` double default '0',
  `close_code` varchar(16) default NULL,
  `close_note` varchar(128) default NULL,
  `tva` double default '0',
  `total` double default '0',
  `total_ttc` double default '0',
  `fk_statut` smallint(6) NOT NULL default '0',
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_facture_source` int(11) default NULL,
  `fk_projet` int(11) default NULL,
  `fk_cond_reglement` int(11) NOT NULL default '1',
  `fk_mode_reglement` int(11) default NULL,
  `date_lim_reglement` date default NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_facture_uk_facnumber` (`facnumber`),
  KEY `idx_facture_fk_soc` (`fk_soc`),
  KEY `idx_facture_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fk_facture_source` (`fk_facture_source`),
  KEY `idx_facture_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_fk_facture_source` FOREIGN KEY (`fk_facture_source`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_facture_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facture`
--

LOCK TABLES `llx_facture` WRITE;
/*!40000 ALTER TABLE `llx_facture` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn`
--

DROP TABLE IF EXISTS `llx_facture_fourn`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facture_fourn` (
  `rowid` int(11) NOT NULL auto_increment,
  `facnumber` varchar(50) NOT NULL default '',
  `type` smallint(6) NOT NULL default '0',
  `fk_soc` int(11) NOT NULL default '0',
  `datec` datetime default NULL,
  `datef` date default NULL,
  `libelle` varchar(255) default NULL,
  `paye` smallint(6) NOT NULL default '0',
  `amount` double(24,8) NOT NULL default '0.00000000',
  `remise` double(24,8) default '0.00000000',
  `tva` double(24,8) default '0.00000000',
  `total` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `total_tva` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `fk_statut` smallint(6) NOT NULL default '0',
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_projet` int(11) default NULL,
  `fk_cond_reglement` int(11) NOT NULL default '1',
  `date_lim_reglement` date default NULL,
  `note` text,
  `note_public` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_facture_fourn_ref` (`facnumber`,`fk_soc`),
  KEY `idx_facture_fourn_date_lim_reglement` (`date_lim_reglement`),
  KEY `idx_facture_fourn_fk_soc` (`fk_soc`),
  KEY `idx_facture_fourn_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fourn_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fourn_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_fourn_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facture_fourn`
--

LOCK TABLES `llx_facture_fourn` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn` DISABLE KEYS */;
INSERT INTO `llx_facture_fourn` VALUES (71,'LOL-509',0,1,'2001-05-09 00:00:00','2001-05-09',NULL,1,1000.00000000,0.00000000,196.00000000,1196.00000000,0.00000000,0.00000000,0.00000000,1,1,1,NULL,1,NULL,'',NULL),(72,'LOL-510',0,1,'2001-09-09 00:00:00','2001-09-09',NULL,1,100.00000000,0.00000000,19.60000000,119.60000000,0.00000000,0.00000000,0.00000000,1,1,1,NULL,1,NULL,'',NULL),(73,'02-1-YHGT',0,2,'2008-08-19 21:35:31','2002-01-01',NULL,1,100.00000000,0.00000000,19.60000000,119.60000000,0.00000000,0.00000000,0.00000000,1,NULL,NULL,NULL,1,NULL,'',NULL),(74,'02-5-YHGT',0,2,'2008-08-19 21:35:31','2002-05-01',NULL,1,1000.00000000,0.00000000,196.00000000,1196.00000000,0.00000000,0.00000000,0.00000000,1,NULL,NULL,NULL,1,NULL,'',NULL),(75,'02-10-YHGT',0,2,'2008-08-19 21:35:31','2002-10-01',NULL,1,1000.00000000,0.00000000,196.00000000,1196.00000000,0.00000000,0.00000000,0.00000000,1,NULL,NULL,NULL,1,NULL,'',NULL),(76,'02-11-YHGT',0,2,'2008-08-19 21:35:31','2002-11-01',NULL,1,1000.00000000,0.00000000,196.00000000,1196.00000000,0.00000000,0.00000000,0.00000000,1,NULL,NULL,NULL,1,NULL,'',NULL),(77,'02-12-YHGT',0,2,'2008-08-19 21:35:31','2002-12-01',NULL,1,1000.00000000,0.00000000,196.00000000,1196.00000000,0.00000000,0.00000000,0.00000000,1,NULL,NULL,NULL,1,NULL,'',NULL);
/*!40000 ALTER TABLE `llx_facture_fourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn_det`
--

DROP TABLE IF EXISTS `llx_facture_fourn_det`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facture_fourn_det` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture_fourn` int(11) NOT NULL default '0',
  `fk_product` int(11) default NULL,
  `description` text,
  `pu_ht` double(24,8) default NULL,
  `pu_ttc` double(24,8) default NULL,
  `qty` smallint(6) default '1',
  `tva_taux` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `tva` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `product_type` int(11) default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_facture_fourn_det_fk_facture` (`fk_facture_fourn`),
  CONSTRAINT `fk_facture_fourn_det_fk_facture` FOREIGN KEY (`fk_facture_fourn`) REFERENCES `llx_facture_fourn` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facture_fourn_det`
--

LOCK TABLES `llx_facture_fourn_det` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn_det` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_fourn_det` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_rec`
--

DROP TABLE IF EXISTS `llx_facture_rec`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facture_rec` (
  `rowid` int(11) NOT NULL auto_increment,
  `titre` varchar(50) NOT NULL default '',
  `fk_soc` int(11) NOT NULL default '0',
  `datec` datetime default NULL,
  `amount` double NOT NULL default '0',
  `remise` double default '0',
  `remise_percent` double default '0',
  `remise_absolue` double default '0',
  `tva` double default '0',
  `total` double default '0',
  `total_ttc` double default '0',
  `fk_user_author` int(11) default NULL,
  `fk_projet` int(11) default NULL,
  `fk_cond_reglement` int(11) default '0',
  `fk_mode_reglement` int(11) default '0',
  `date_lim_reglement` date default NULL,
  `note` text,
  `note_public` text,
  `frequency` char(2) default NULL,
  `last_gen` varchar(7) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_facture_rec_uk_titre` (`titre`),
  KEY `idx_facture_rec_fksoc` (`fk_soc`),
  KEY `idx_facture_rec_fk_soc` (`fk_soc`),
  KEY `idx_facture_rec_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_rec_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_rec_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facture_rec`
--

LOCK TABLES `llx_facture_rec` WRITE;
/*!40000 ALTER TABLE `llx_facture_rec` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_rec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_stats`
--

DROP TABLE IF EXISTS `llx_facture_stats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facture_stats` (
  `date_full` datetime default NULL,
  `date_day` date default NULL,
  `data` varchar(50) default NULL,
  `value` double default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facture_stats`
--

LOCK TABLES `llx_facture_stats` WRITE;
/*!40000 ALTER TABLE `llx_facture_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet`
--

DROP TABLE IF EXISTS `llx_facturedet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facturedet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture` int(11) NOT NULL default '0',
  `fk_product` int(11) default NULL,
  `description` text,
  `tva_taux` double default NULL,
  `qty` double default NULL,
  `remise_percent` double default '0',
  `remise` double default '0',
  `fk_remise_except` int(11) default NULL,
  `subprice` double default NULL,
  `price` double default NULL,
  `total_ht` double default NULL,
  `total_tva` double default NULL,
  `total_ttc` double default NULL,
  `product_type` int(11) default '0',
  `date_start` datetime default NULL,
  `date_end` datetime default NULL,
  `info_bits` int(11) default '0',
  `fk_code_ventilation` int(11) NOT NULL default '0',
  `fk_export_compta` int(11) NOT NULL default '0',
  `special_code` tinyint(4) unsigned default '0',
  `rang` int(11) default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_facturedet_fk_facture` (`fk_facture`),
  CONSTRAINT `fk_facturedet_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facturedet`
--

LOCK TABLES `llx_facturedet` WRITE;
/*!40000 ALTER TABLE `llx_facturedet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facturedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet_rec`
--

DROP TABLE IF EXISTS `llx_facturedet_rec`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_facturedet_rec` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture` int(11) NOT NULL default '0',
  `fk_product` int(11) default NULL,
  `description` text,
  `tva_taux` double default '19.6',
  `qty` double default NULL,
  `remise_percent` double default '0',
  `remise` double default '0',
  `subprice` double default NULL,
  `price` double default NULL,
  `total_ht` double default NULL,
  `total_tva` double default NULL,
  `total_ttc` double default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_facturedet_rec`
--

LOCK TABLES `llx_facturedet_rec` WRITE;
/*!40000 ALTER TABLE `llx_facturedet_rec` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facturedet_rec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinter`
--

DROP TABLE IF EXISTS `llx_fichinter`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_fichinter` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) NOT NULL default '0',
  `fk_projet` int(11) default '0',
  `fk_contrat` int(11) default '0',
  `ref` varchar(30) NOT NULL default '',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `date_valid` datetime default NULL,
  `datei` date default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_statut` smallint(6) default '0',
  `duree` double default NULL,
  `description` text,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `ref` (`ref`),
  KEY `idx_fichinter_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_fichinter_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_fichinter`
--

LOCK TABLES `llx_fichinter` WRITE;
/*!40000 ALTER TABLE `llx_fichinter` DISABLE KEYS */;
INSERT INTO `llx_fichinter` VALUES (2,1,0,0,'FI-LP-1','2008-08-19 19:35:31','2001-12-05 00:00:00','2001-12-05 00:00:00','2001-12-05',1,1,1,4,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_fichinter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinterdet`
--

DROP TABLE IF EXISTS `llx_fichinterdet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_fichinterdet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_fichinter` int(11) default NULL,
  `date` date default NULL,
  `description` text,
  `duree` int(11) default NULL,
  `rang` int(11) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_fichinterdet`
--

LOCK TABLES `llx_fichinterdet` WRITE;
/*!40000 ALTER TABLE `llx_fichinterdet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fichinterdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fournisseur_ca`
--

DROP TABLE IF EXISTS `llx_fournisseur_ca`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_fournisseur_ca` (
  `fk_societe` int(11) default NULL,
  `date_calcul` datetime default NULL,
  `year` smallint(5) unsigned default NULL,
  `ca_genere` float default NULL,
  `ca_achat` float(11,2) default '0.00',
  UNIQUE KEY `fk_societe` (`fk_societe`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_fournisseur_ca`
--

LOCK TABLES `llx_fournisseur_ca` WRITE;
/*!40000 ALTER TABLE `llx_fournisseur_ca` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fournisseur_ca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_groupesociete`
--

DROP TABLE IF EXISTS `llx_groupesociete`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_groupesociete` (
  `rowid` int(11) NOT NULL auto_increment,
  `parent` int(11) default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `nom` varchar(60) default NULL,
  `note` text,
  `remise` double default '0',
  `fk_user_author` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `parent` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_groupesociete`
--

LOCK TABLES `llx_groupesociete` WRITE;
/*!40000 ALTER TABLE `llx_groupesociete` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_groupesociete` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_groupesociete_remise`
--

DROP TABLE IF EXISTS `llx_groupesociete_remise`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_groupesociete_remise` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_groupe` int(11) NOT NULL default '0',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `fk_user_author` int(11) default NULL,
  `remise` double default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_groupesociete_remise`
--

LOCK TABLES `llx_groupesociete_remise` WRITE;
/*!40000 ALTER TABLE `llx_groupesociete_remise` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_groupesociete_remise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_livraison`
--

DROP TABLE IF EXISTS `llx_livraison`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_livraison` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL default '',
  `ref_client` varchar(30) default NULL,
  `fk_soc` int(11) NOT NULL default '0',
  `fk_expedition` int(11) default NULL,
  `date_creation` datetime default NULL,
  `fk_user_author` int(11) default NULL,
  `date_valid` datetime default NULL,
  `fk_user_valid` int(11) default NULL,
  `date_livraison` date default NULL,
  `fk_adresse_livraison` int(11) default NULL,
  `fk_statut` smallint(6) default '0',
  `total_ht` double(24,8) default '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_livraison_uk_ref` (`ref`),
  KEY `idx_livraison_fk_soc` (`fk_soc`),
  KEY `idx_livraison_fk_user_author` (`fk_user_author`),
  KEY `idx_livraison_fk_user_valid` (`fk_user_valid`),
  KEY `idx_livraison_fk_adresse_livraison` (`fk_adresse_livraison`),
  CONSTRAINT `fk_livraison_fk_adresse_livraison` FOREIGN KEY (`fk_adresse_livraison`) REFERENCES `llx_societe_adresse_livraison` (`rowid`),
  CONSTRAINT `fk_livraison_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_livraison`
--

LOCK TABLES `llx_livraison` WRITE;
/*!40000 ALTER TABLE `llx_livraison` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_livraison` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_livraisondet`
--

DROP TABLE IF EXISTS `llx_livraisondet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_livraisondet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_livraison` int(11) default NULL,
  `fk_origin_line` int(11) default NULL,
  `fk_product` int(11) default NULL,
  `description` text,
  `qty` double default NULL,
  `subprice` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `rang` int(11) default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_livraisondet_fk_expedition` (`fk_livraison`),
  CONSTRAINT `fk_livraisondet_fk_livraison` FOREIGN KEY (`fk_livraison`) REFERENCES `llx_livraison` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_livraisondet`
--

LOCK TABLES `llx_livraisondet` WRITE;
/*!40000 ALTER TABLE `llx_livraisondet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_livraisondet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_mailing`
--

DROP TABLE IF EXISTS `llx_mailing`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_mailing` (
  `rowid` int(11) NOT NULL auto_increment,
  `statut` smallint(6) default '0',
  `titre` varchar(60) default NULL,
  `sujet` varchar(60) default NULL,
  `body` text,
  `cible` varchar(60) default NULL,
  `nbemail` int(11) default NULL,
  `email_from` varchar(160) default NULL,
  `email_replyto` varchar(160) default NULL,
  `email_errorsto` varchar(160) default NULL,
  `date_creat` datetime default NULL,
  `date_valid` datetime default NULL,
  `date_appro` datetime default NULL,
  `date_envoi` datetime default NULL,
  `fk_user_creat` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_user_appro` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_mailing`
--

LOCK TABLES `llx_mailing` WRITE;
/*!40000 ALTER TABLE `llx_mailing` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_mailing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_mailing_cibles`
--

DROP TABLE IF EXISTS `llx_mailing_cibles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_mailing_cibles` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_mailing` int(11) NOT NULL default '0',
  `fk_contact` int(11) NOT NULL default '0',
  `nom` varchar(160) default NULL,
  `prenom` varchar(160) default NULL,
  `email` varchar(160) NOT NULL default '',
  `statut` smallint(6) NOT NULL default '0',
  `url` varchar(160) default NULL,
  `date_envoi` datetime default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_mailing_cibles` (`fk_mailing`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_mailing_cibles`
--

LOCK TABLES `llx_mailing_cibles` WRITE;
/*!40000 ALTER TABLE `llx_mailing_cibles` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_mailing_cibles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_menu`
--

DROP TABLE IF EXISTS `llx_menu`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_menu` (
  `rowid` int(11) NOT NULL auto_increment,
  `menu_handler` varchar(16) NOT NULL default '',
  `module` varchar(64) default NULL,
  `type` enum('top','left') NOT NULL default 'top',
  `mainmenu` varchar(100) NOT NULL default '',
  `fk_menu` int(11) NOT NULL default '0',
  `position` int(11) NOT NULL default '0',
  `url` varchar(255) NOT NULL default '',
  `target` varchar(100) default NULL,
  `titre` varchar(255) NOT NULL default '',
  `langs` varchar(100) default NULL,
  `level` tinyint(1) default NULL,
  `leftmenu` char(1) default '1',
  `perms` varchar(255) default NULL,
  `user` int(11) NOT NULL default '0',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `idx_menu_uk_menu` (`menu_handler`,`fk_menu`,`url`),
  KEY `idx_menu_menuhandler_type` (`menu_handler`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_menu`
--

LOCK TABLES `llx_menu` WRITE;
/*!40000 ALTER TABLE `llx_menu` DISABLE KEYS */;
INSERT INTO `llx_menu` VALUES (1,'all','agenda','top','agenda',0,100,'/comm/action/index.php','','Agenda','commercial',0,'0','$user->rights->agenda->myactions->read',0,'2008-08-07 19:59:53'),(2,'all','ecm','top','ecm',0,100,'/ecm/index.php','','MenuECM','ecm',0,'1','$user->rights->ecm->upload || $user->rights->ecm->download || $user->rights->ecm->setup',0,'2008-08-07 20:00:42'),(3,'all','ecm','left','ecm',2,100,'/ecm/index.php','','ECMArea','ecm',0,'','$user->rights->ecm->download',0,'2008-08-07 20:00:42'),(4,'all','ecm','left','ecm',3,100,'/ecm/index.php','','List','ecm',0,'','$user->rights->ecm->download',0,'2008-08-07 20:00:42'),(5,'all','ecm','left','ecm',3,100,'/ecm/docdir.php?action=create','','ECMNewSection','ecm',0,'','$user->rights->ecm->setup',0,'2008-08-07 20:00:42');
/*!40000 ALTER TABLE `llx_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_menu_const`
--

DROP TABLE IF EXISTS `llx_menu_const`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_menu_const` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_menu` int(11) NOT NULL default '0',
  `fk_constraint` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_menu_const` (`fk_menu`,`fk_constraint`),
  KEY `idx_menu_const_fk_menu` (`fk_menu`),
  KEY `idx_menu_const_fk_constraint` (`fk_constraint`),
  CONSTRAINT `fk_menu_const_fk_constraint` FOREIGN KEY (`fk_constraint`) REFERENCES `llx_menu_constraint` (`rowid`),
  CONSTRAINT `fk_menu_const_fk_menu` FOREIGN KEY (`fk_menu`) REFERENCES `llx_menu` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_menu_const`
--

LOCK TABLES `llx_menu_const` WRITE;
/*!40000 ALTER TABLE `llx_menu_const` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_menu_const` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_menu_constraint`
--

DROP TABLE IF EXISTS `llx_menu_constraint`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_menu_constraint` (
  `rowid` int(11) NOT NULL default '0',
  `action` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_menu_constraint`
--

LOCK TABLES `llx_menu_constraint` WRITE;
/*!40000 ALTER TABLE `llx_menu_constraint` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_menu_constraint` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_models`
--

DROP TABLE IF EXISTS `llx_models`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_models` (
  `rowid` int(11) NOT NULL auto_increment,
  `module` varchar(32) default NULL,
  `typemodele` varchar(32) default NULL,
  `sortorder` smallint(6) default NULL,
  `private` smallint(6) NOT NULL default '0',
  `fk_user` int(11) default NULL,
  `title` varchar(128) default NULL,
  `filename` varchar(128) default NULL,
  `content` text,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_models`
--

LOCK TABLES `llx_models` WRITE;
/*!40000 ALTER TABLE `llx_models` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_notify`
--

DROP TABLE IF EXISTS `llx_notify`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_notify` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `daten` datetime default NULL,
  `fk_action` int(11) NOT NULL default '0',
  `fk_contact` int(11) NOT NULL default '0',
  `objet_type` enum('ficheinter','facture','propale') default NULL,
  `objet_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_notify`
--

LOCK TABLES `llx_notify` WRITE;
/*!40000 ALTER TABLE `llx_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_notify_def`
--

DROP TABLE IF EXISTS `llx_notify_def`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_notify_def` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` date default NULL,
  `fk_action` int(11) NOT NULL default '0',
  `fk_soc` int(11) NOT NULL default '0',
  `fk_contact` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_notify_def`
--

LOCK TABLES `llx_notify_def` WRITE;
/*!40000 ALTER TABLE `llx_notify_def` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_notify_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_osc_categories`
--

DROP TABLE IF EXISTS `llx_osc_categories`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_osc_categories` (
  `rowid` int(11) unsigned NOT NULL auto_increment,
  `dolicatid` int(11) NOT NULL default '0',
  `osccatid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `dolicatid` (`dolicatid`),
  UNIQUE KEY `osccatid` (`osccatid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Correspondance categorie Dolibarr categorie OSC';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_osc_categories`
--

LOCK TABLES `llx_osc_categories` WRITE;
/*!40000 ALTER TABLE `llx_osc_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_osc_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_osc_customer`
--

DROP TABLE IF EXISTS `llx_osc_customer`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_osc_customer` (
  `rowid` int(11) NOT NULL default '0',
  `datem` datetime default NULL,
  `fk_soc` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_soc` (`fk_soc`),
  CONSTRAINT `fk_osc_customer_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Table transition client OSC - societe Dolibarr';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_osc_customer`
--

LOCK TABLES `llx_osc_customer` WRITE;
/*!40000 ALTER TABLE `llx_osc_customer` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_osc_customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_osc_order`
--

DROP TABLE IF EXISTS `llx_osc_order`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_osc_order` (
  `rowid` int(11) NOT NULL default '0',
  `datem` datetime default NULL,
  `fk_commande` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_commande` (`fk_commande`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Table transition commande OSC - commande Dolibarr';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_osc_order`
--

LOCK TABLES `llx_osc_order` WRITE;
/*!40000 ALTER TABLE `llx_osc_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_osc_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_osc_product`
--

DROP TABLE IF EXISTS `llx_osc_product`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_osc_product` (
  `rowid` int(11) NOT NULL default '0',
  `datem` datetime default NULL,
  `fk_product` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_product` (`fk_product`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Table transition produit OSC - produit Dolibarr';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_osc_product`
--

LOCK TABLES `llx_osc_product` WRITE;
/*!40000 ALTER TABLE `llx_osc_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_osc_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement`
--

DROP TABLE IF EXISTS `llx_paiement`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_paiement` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture` int(11) default NULL,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datep` datetime default NULL,
  `amount` double default '0',
  `fk_paiement` int(11) NOT NULL default '0',
  `num_paiement` varchar(50) default NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL default '0',
  `fk_user_creat` int(11) default NULL,
  `fk_user_modif` int(11) default NULL,
  `statut` smallint(6) NOT NULL default '0',
  `fk_export_compta` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_paiement`
--

LOCK TABLES `llx_paiement` WRITE;
/*!40000 ALTER TABLE `llx_paiement` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement_facture`
--

DROP TABLE IF EXISTS `llx_paiement_facture`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_paiement_facture` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_paiement` int(11) default NULL,
  `fk_facture` int(11) default NULL,
  `amount` double default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_paiement_facture_fk_facture` (`fk_facture`),
  KEY `idx_paiement_facture_fk_paiement` (`fk_paiement`),
  CONSTRAINT `fk_paiement_facture_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_paiement_facture_fk_paiement` FOREIGN KEY (`fk_paiement`) REFERENCES `llx_paiement` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_paiement_facture`
--

LOCK TABLES `llx_paiement_facture` WRITE;
/*!40000 ALTER TABLE `llx_paiement_facture` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementcharge`
--

DROP TABLE IF EXISTS `llx_paiementcharge`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_paiementcharge` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_charge` int(11) default NULL,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datep` datetime default NULL,
  `amount` double default '0',
  `fk_typepaiement` int(11) NOT NULL default '0',
  `num_paiement` varchar(50) default NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL default '0',
  `fk_user_creat` int(11) default NULL,
  `fk_user_modif` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_paiementcharge`
--

LOCK TABLES `llx_paiementcharge` WRITE;
/*!40000 ALTER TABLE `llx_paiementcharge` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiementcharge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_paiementfourn` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `fk_facture_fourn` int(11) default NULL,
  `datep` datetime default NULL,
  `amount` double default '0',
  `fk_user_author` int(11) default NULL,
  `fk_paiement` int(11) NOT NULL default '0',
  `num_paiement` varchar(50) default NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL default '0',
  `statut` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_paiementfourn`
--

LOCK TABLES `llx_paiementfourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiementfourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn_facturefourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn_facturefourn`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_paiementfourn_facturefourn` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_paiementfourn` int(11) default NULL,
  `fk_facturefourn` int(11) default NULL,
  `amount` double default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_paiementfourn_facturefourn_fk_facture` (`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_paiement` (`fk_paiementfourn`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_paiementfourn_facturefourn`
--

LOCK TABLES `llx_paiementfourn_facturefourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pr_exp`
--

DROP TABLE IF EXISTS `llx_pr_exp`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_pr_exp` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_propal` int(11) NOT NULL default '0',
  `fk_expedition` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  KEY `fk_propal` (`fk_propal`),
  KEY `fk_expedition` (`fk_expedition`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_pr_exp`
--

LOCK TABLES `llx_pr_exp` WRITE;
/*!40000 ALTER TABLE `llx_pr_exp` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_pr_exp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pr_liv`
--

DROP TABLE IF EXISTS `llx_pr_liv`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_pr_liv` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_propal` int(11) NOT NULL default '0',
  `fk_livraison` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  KEY `fk_propal` (`fk_propal`),
  KEY `fk_livraison` (`fk_livraison`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_pr_liv`
--

LOCK TABLES `llx_pr_liv` WRITE;
/*!40000 ALTER TABLE `llx_pr_liv` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_pr_liv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_bons`
--

DROP TABLE IF EXISTS `llx_prelevement_bons`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_prelevement_bons` (
  `rowid` int(11) NOT NULL auto_increment,
  `ref` varchar(12) default NULL,
  `datec` datetime default NULL,
  `amount` double default '0',
  `statut` smallint(6) default '0',
  `credite` smallint(6) default '0',
  `note` text,
  `date_trans` datetime default NULL,
  `method_trans` smallint(6) default NULL,
  `fk_user_trans` int(11) default NULL,
  `date_credit` datetime default NULL,
  `fk_user_credit` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `ref` (`ref`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_prelevement_bons`
--

LOCK TABLES `llx_prelevement_bons` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_bons` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_bons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_facture`
--

DROP TABLE IF EXISTS `llx_prelevement_facture`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_prelevement_facture` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture` int(11) NOT NULL default '0',
  `fk_prelevement_lignes` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_prelevement_facture_fk_prelevement_lignes` (`fk_prelevement_lignes`),
  CONSTRAINT `fk_prelevement_facture_fk_prelevement_lignes` FOREIGN KEY (`fk_prelevement_lignes`) REFERENCES `llx_prelevement_lignes` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_prelevement_facture`
--

LOCK TABLES `llx_prelevement_facture` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_facture` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_facture_demande`
--

DROP TABLE IF EXISTS `llx_prelevement_facture_demande`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_prelevement_facture_demande` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_facture` int(11) NOT NULL default '0',
  `amount` double NOT NULL default '0',
  `date_demande` datetime NOT NULL default '0000-00-00 00:00:00',
  `traite` smallint(6) default '0',
  `date_traite` datetime default NULL,
  `fk_prelevement_bons` int(11) default NULL,
  `fk_user_demande` int(11) NOT NULL default '0',
  `code_banque` varchar(7) default NULL,
  `code_guichet` varchar(6) default NULL,
  `number` varchar(255) default NULL,
  `cle_rib` varchar(5) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_prelevement_facture_demande`
--

LOCK TABLES `llx_prelevement_facture_demande` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_facture_demande` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_facture_demande` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_lignes`
--

DROP TABLE IF EXISTS `llx_prelevement_lignes`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_prelevement_lignes` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_prelevement_bons` int(11) default NULL,
  `fk_soc` int(11) NOT NULL default '0',
  `statut` smallint(6) default '0',
  `client_nom` varchar(255) default NULL,
  `amount` double default '0',
  `code_banque` varchar(7) default NULL,
  `code_guichet` varchar(6) default NULL,
  `number` varchar(255) default NULL,
  `cle_rib` varchar(5) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`),
  KEY `idx_prelevement_lignes_fk_prelevement_bons` (`fk_prelevement_bons`),
  CONSTRAINT `fk_prelevement_lignes_fk_prelevement_bons` FOREIGN KEY (`fk_prelevement_bons`) REFERENCES `llx_prelevement_bons` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_prelevement_lignes`
--

LOCK TABLES `llx_prelevement_lignes` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_lignes` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_lignes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_notifications`
--

DROP TABLE IF EXISTS `llx_prelevement_notifications`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_prelevement_notifications` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_user` int(11) NOT NULL default '0',
  `action` char(2) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_prelevement_notifications`
--

LOCK TABLES `llx_prelevement_notifications` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_rejet`
--

DROP TABLE IF EXISTS `llx_prelevement_rejet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_prelevement_rejet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_prelevement_lignes` int(11) default NULL,
  `date_rejet` datetime default NULL,
  `motif` int(11) default NULL,
  `date_creation` datetime default NULL,
  `fk_user_creation` int(11) default NULL,
  `note` text,
  `afacturer` tinyint(4) default '0',
  `fk_facture` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_prelevement_rejet`
--

LOCK TABLES `llx_prelevement_rejet` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_rejet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_rejet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product`
--

DROP TABLE IF EXISTS `llx_product`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `ref` varchar(32) NOT NULL default '',
  `label` varchar(255) NOT NULL default '',
  `description` text,
  `note` text,
  `price` double(24,8) default '0.00000000',
  `price_ttc` double(24,8) default '0.00000000',
  `price_base_type` char(3) default 'HT',
  `tva_tx` double(6,3) default NULL,
  `fk_user_author` int(11) default NULL,
  `envente` tinyint(4) default '1',
  `nbvente` int(11) default '0',
  `fk_product_type` int(11) default '0',
  `duration` varchar(6) default NULL,
  `stock_propale` int(11) default '0',
  `stock_commande` int(11) default '0',
  `seuil_stock_alerte` int(11) default '0',
  `stock_loc` varchar(10) default NULL,
  `barcode` varchar(255) default NULL,
  `fk_barcode_type` int(11) default '0',
  `partnumber` varchar(32) default NULL,
  `weight` float default NULL,
  `weight_units` tinyint(4) default NULL,
  `volume` float default NULL,
  `volume_units` tinyint(4) default NULL,
  `canvas` varchar(15) default '',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_product_ref` (`ref`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product`
--

LOCK TABLES `llx_product` WRITE;
/*!40000 ALTER TABLE `llx_product` DISABLE KEYS */;
INSERT INTO `llx_product` VALUES (29,NULL,'2008-08-19 19:35:31','RJ451MR','Câble Réseaux RJ45 1m rouge','Câble Réseaux RJ45 1m rouge',NULL,10.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(30,NULL,'2008-08-19 19:35:31','RJ454M','Câble Réseaux RJ45 4m','Câble Réseaux RJ45 4m\n couleur suivant stock',NULL,19.50000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(31,NULL,'2008-08-19 19:35:31','RJ452M','Câble Réseaux RJ45 2m','Câble Réseaux RJ45 2m',NULL,10.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(32,NULL,'2008-08-19 19:35:31','RJ458M','Câble Réseaux RJ45 8m','Câble Réseaux RJ45 8m',NULL,10.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(33,NULL,'2008-08-19 19:35:31','RJ4515M','Câble Réseaux RJ45 15m','Câble Réseaux RJ45 15m',NULL,10.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(34,NULL,'2008-08-19 19:35:31','HEB12MS','Hébergement serveur 12 mois','Hébergement serveur 12 mois',NULL,2400.00000000,0.00000000,'HT',19.600,NULL,1,0,1,'12m',0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(35,NULL,'2008-08-19 19:35:31','HEB03MS','Hébergement serveur 3 mois','Hébergement serveur 3 mois',NULL,600.00000000,0.00000000,'HT',19.600,NULL,1,0,1,'3m',0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(36,NULL,'2008-08-19 19:35:31','HEB06MS','Hébergement serveur 6 mois','Hébergement serveur 6 mois',NULL,1200.00000000,0.00000000,'HT',19.600,NULL,1,0,1,'6m',0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(37,NULL,'2008-08-19 19:35:31','SW8','Switch 8 ports 100Mbits','Switch 8 ports 100Mbits',NULL,1000.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(38,NULL,'2008-08-19 19:35:31','SER1U','Serveur 1U Serie 3W','Serveur avec 1G de RAM et 2 processeurs',NULL,9750.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(39,NULL,'2008-08-19 19:35:31','HUB8-10','Hub 8 ports 10Mbits','Hub 8 ports',NULL,750.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(40,NULL,'2008-08-19 19:35:31','PB-16','Pan. Brass. 16','Panneau de brassage extensible, incluant 1 barre de 16 prises',NULL,650.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(41,NULL,'2008-08-19 19:35:31','PB-32','Pan. Brass. 32','Panneau de brassage extensible, incluant 2 barres de 16 prises',NULL,1200.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,''),(42,NULL,'2008-08-19 19:35:31','HB-USB1','Hub Usb 4 ports','Hub USB 4 ports avec bloc d\'alimentation indépendant',NULL,31.00000000,0.00000000,'HT',19.600,NULL,1,0,0,NULL,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'');
/*!40000 ALTER TABLE `llx_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_ca`
--

DROP TABLE IF EXISTS `llx_product_ca`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_ca` (
  `fk_product` int(11) default NULL,
  `date_calcul` datetime default NULL,
  `year` smallint(5) unsigned default NULL,
  `ca_genere` float default NULL,
  UNIQUE KEY `fk_product` (`fk_product`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_ca`
--

LOCK TABLES `llx_product_ca` WRITE;
/*!40000 ALTER TABLE `llx_product_ca` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_ca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_cnv_livre`
--

DROP TABLE IF EXISTS `llx_product_cnv_livre`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_cnv_livre` (
  `rowid` int(11) NOT NULL default '0',
  `isbn` varchar(13) default NULL,
  `ean` varchar(13) default NULL,
  `format` varchar(7) default NULL,
  `px_feuillet` float(12,4) default NULL,
  `px_reliure` float(12,4) default NULL,
  `px_couverture` float(12,4) default NULL,
  `px_revient` float(12,4) default NULL,
  `stock_loc` varchar(5) default NULL,
  `pages` smallint(5) unsigned default NULL,
  `fk_couverture` int(11) default NULL,
  `fk_contrat` int(11) default NULL,
  `fk_auteur` int(11) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_cnv_livre`
--

LOCK TABLES `llx_product_cnv_livre` WRITE;
/*!40000 ALTER TABLE `llx_product_cnv_livre` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_cnv_livre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_cnv_livre_contrat`
--

DROP TABLE IF EXISTS `llx_product_cnv_livre_contrat`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_cnv_livre_contrat` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_cnv_livre` int(11) default NULL,
  `quantite` int(11) default NULL,
  `taux` float(3,2) default NULL,
  `date_app` datetime default NULL,
  `duree` varchar(50) default NULL,
  `fk_user` int(11) default NULL,
  `locked` tinyint(4) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_cnv_livre_contrat`
--

LOCK TABLES `llx_product_cnv_livre_contrat` WRITE;
/*!40000 ALTER TABLE `llx_product_cnv_livre_contrat` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_cnv_livre_contrat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_det`
--

DROP TABLE IF EXISTS `llx_product_det`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_det` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_product` int(11) NOT NULL default '0',
  `lang` varchar(5) NOT NULL default '0',
  `label` varchar(255) NOT NULL default '',
  `description` text,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_det`
--

LOCK TABLES `llx_product_det` WRITE;
/*!40000 ALTER TABLE `llx_product_det` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_det` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur`
--

DROP TABLE IF EXISTS `llx_product_fournisseur`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_fournisseur` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_product` int(11) default NULL,
  `fk_soc` int(11) default NULL,
  `ref_fourn` varchar(30) default NULL,
  `fk_user_author` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_product_fourn_fk_product` (`fk_product`),
  KEY `idx_product_fourn_fk_soc` (`fk_soc`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_fournisseur`
--

LOCK TABLES `llx_product_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur` VALUES (1,'2008-08-19 21:32:17','2008-08-19 19:32:17',1,2,'2313487',1),(2,'2008-08-19 21:32:17','2008-08-19 19:32:17',2,2,'2313409',1),(3,'2008-08-19 21:32:17','2008-08-19 19:32:17',3,2,'2323134',1),(4,'2008-08-19 21:32:17','2008-08-19 19:32:17',3,4,'2313784',1),(5,'2008-08-19 21:33:39','2008-08-19 19:33:39',1,2,'2313487',1),(6,'2008-08-19 21:33:39','2008-08-19 19:33:39',2,2,'2313409',1),(7,'2008-08-19 21:33:39','2008-08-19 19:33:39',3,2,'2323134',1),(8,'2008-08-19 21:33:39','2008-08-19 19:33:39',3,4,'2313784',1),(9,'2008-08-19 21:35:31','2008-08-19 19:35:31',1,2,'2313487',1),(10,'2008-08-19 21:35:31','2008-08-19 19:35:31',2,2,'2313409',1),(11,'2008-08-19 21:35:31','2008-08-19 19:35:31',3,2,'2323134',1),(12,'2008-08-19 21:35:31','2008-08-19 19:35:31',3,4,'2313784',1);
/*!40000 ALTER TABLE `llx_product_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_fournisseur_price` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_product_fournisseur` int(11) NOT NULL default '0',
  `price` double(24,8) default '0.00000000',
  `quantity` double default NULL,
  `unitprice` double(24,8) default '0.00000000',
  `fk_user` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_product_fournisseur_price_fk_user` (`fk_user`),
  KEY `idx_product_fournisseur_price_fk_product_fournisseur` (`fk_product_fournisseur`),
  CONSTRAINT `fk_product_fournisseur_price_fk_product_fournisseur` FOREIGN KEY (`fk_product_fournisseur`) REFERENCES `llx_product_fournisseur` (`rowid`),
  CONSTRAINT `fk_product_fournisseur_price_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_fournisseur_price`
--

LOCK TABLES `llx_product_fournisseur_price` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_fournisseur_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price_log`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_fournisseur_price_log` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `fk_product_fournisseur` int(11) NOT NULL default '0',
  `price` double(24,8) default '0.00000000',
  `quantity` double default NULL,
  `fk_user` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_fournisseur_price_log`
--

LOCK TABLES `llx_product_fournisseur_price_log` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_fournisseur_price_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_price`
--

DROP TABLE IF EXISTS `llx_product_price`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_price` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL default '0',
  `date_price` datetime NOT NULL default '0000-00-00 00:00:00',
  `price_level` tinyint(4) default '1',
  `price` double(24,8) default NULL,
  `price_ttc` double(24,8) default '0.00000000',
  `price_base_type` char(3) default 'HT',
  `tva_tx` double(6,3) NOT NULL default '0.000',
  `fk_user_author` int(11) default NULL,
  `envente` tinyint(4) default '1',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_price`
--

LOCK TABLES `llx_product_price` WRITE;
/*!40000 ALTER TABLE `llx_product_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_stock`
--

DROP TABLE IF EXISTS `llx_product_stock`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_stock` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL default '0',
  `fk_entrepot` int(11) NOT NULL default '0',
  `reel` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_product_stock_fk_product` (`fk_product`),
  KEY `idx_product_stock_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_stock`
--

LOCK TABLES `llx_product_stock` WRITE;
/*!40000 ALTER TABLE `llx_product_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_subproduct`
--

DROP TABLE IF EXISTS `llx_product_subproduct`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_product_subproduct` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_product` int(11) NOT NULL default '0',
  `fk_product_subproduct` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_product` (`fk_product`,`fk_product_subproduct`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_product_subproduct`
--

LOCK TABLES `llx_product_subproduct` WRITE;
/*!40000 ALTER TABLE `llx_product_subproduct` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_subproduct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet`
--

DROP TABLE IF EXISTS `llx_projet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_projet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) default NULL,
  `fk_statut` smallint(6) NOT NULL default '0',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `dateo` date default NULL,
  `ref` varchar(50) default NULL,
  `title` varchar(255) default NULL,
  `fk_user_resp` int(11) default NULL,
  `fk_user_creat` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `ref` (`ref`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_projet`
--

LOCK TABLES `llx_projet` WRITE;
/*!40000 ALTER TABLE `llx_projet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task`
--

DROP TABLE IF EXISTS `llx_projet_task`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_projet_task` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_projet` int(11) NOT NULL default '0',
  `fk_task_parent` int(11) NOT NULL default '0',
  `title` varchar(255) default NULL,
  `duration_effective` double NOT NULL default '0',
  `fk_user_creat` int(11) default NULL,
  `statut` enum('open','closed') default 'open',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_projet_task`
--

LOCK TABLES `llx_projet_task` WRITE;
/*!40000 ALTER TABLE `llx_projet_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task_actors`
--

DROP TABLE IF EXISTS `llx_projet_task_actors`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_projet_task_actors` (
  `fk_projet_task` int(11) NOT NULL default '0',
  `fk_user` int(11) NOT NULL default '0',
  `role` enum('admin','read','acto','info') default 'admin',
  UNIQUE KEY `fk_projet_task` (`fk_projet_task`,`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_projet_task_actors`
--

LOCK TABLES `llx_projet_task_actors` WRITE;
/*!40000 ALTER TABLE `llx_projet_task_actors` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet_task_actors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task_time`
--

DROP TABLE IF EXISTS `llx_projet_task_time`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_projet_task_time` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_task` int(11) NOT NULL default '0',
  `task_date` date default NULL,
  `task_duration` double default NULL,
  `fk_user` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_projet_task_time`
--

LOCK TABLES `llx_projet_task_time` WRITE;
/*!40000 ALTER TABLE `llx_projet_task_time` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet_task_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propal`
--

DROP TABLE IF EXISTS `llx_propal`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_propal` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) default NULL,
  `fk_projet` int(11) default '0',
  `ref` varchar(30) NOT NULL default '',
  `ref_client` varchar(30) default NULL,
  `datec` datetime default NULL,
  `datep` date default NULL,
  `fin_validite` datetime default NULL,
  `date_valid` datetime default NULL,
  `date_cloture` datetime default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_user_cloture` int(11) default NULL,
  `fk_statut` smallint(6) NOT NULL default '0',
  `price` double default '0',
  `remise_percent` double default '0',
  `remise_absolue` double default '0',
  `remise` double default '0',
  `total_ht` double(24,8) default '0.00000000',
  `tva` double(24,8) default '0.00000000',
  `total` double(24,8) default '0.00000000',
  `fk_cond_reglement` int(11) default NULL,
  `fk_mode_reglement` int(11) default NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  `date_livraison` date default NULL,
  `fk_adresse_livraison` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `ref` (`ref`),
  KEY `idx_propal_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_propal_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_propal`
--

LOCK TABLES `llx_propal` WRITE;
/*!40000 ALTER TABLE `llx_propal` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_propal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propaldet`
--

DROP TABLE IF EXISTS `llx_propaldet`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_propaldet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_propal` int(11) default NULL,
  `fk_product` int(11) default NULL,
  `description` text,
  `fk_remise_except` int(11) default NULL,
  `tva_tx` double(6,3) default '0.000',
  `qty` double default NULL,
  `remise_percent` double default '0',
  `remise` double default '0',
  `price` double default NULL,
  `subprice` double(24,8) default '0.00000000',
  `total_ht` double(24,8) default '0.00000000',
  `total_tva` double(24,8) default '0.00000000',
  `total_ttc` double(24,8) default '0.00000000',
  `info_bits` int(11) default '0',
  `pa_ht` double(24,8) default '0.00000000',
  `marge_tx` double(6,3) default '0.000',
  `marque_tx` double(6,3) default '0.000',
  `special_code` tinyint(4) unsigned default '0',
  `rang` int(11) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_propaldet`
--

LOCK TABLES `llx_propaldet` WRITE;
/*!40000 ALTER TABLE `llx_propaldet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_propaldet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_rights_def`
--

DROP TABLE IF EXISTS `llx_rights_def`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_rights_def` (
  `id` int(11) NOT NULL default '0',
  `libelle` varchar(255) default NULL,
  `module` varchar(12) default NULL,
  `perms` varchar(50) default NULL,
  `subperms` varchar(50) default NULL,
  `type` enum('r','w','m','d','a') default NULL,
  `bydefault` tinyint(4) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_rights_def`
--

LOCK TABLES `llx_rights_def` WRITE;
/*!40000 ALTER TABLE `llx_rights_def` DISABLE KEYS */;
INSERT INTO `llx_rights_def` VALUES (11,'Lire les factures','facture','lire',NULL,'a',1),(12,'Creer les factures','facture','creer',NULL,'a',0),(13,'Modifier les factures','facture','modifier',NULL,'a',0),(14,'Valider les factures','facture','valider',NULL,'a',0),(15,'Envoyer les factures par mail','facture','envoyer',NULL,'a',0),(16,'Emettre des paiements sur les factures','facture','paiement',NULL,'a',0),(19,'Supprimer les factures','facture','supprimer',NULL,'a',0),(21,'Lire les propositions commerciales','propale','lire',NULL,'r',1),(22,'Creer/modifier les propositions commerciales','propale','creer',NULL,'w',0),(24,'Valider les propositions commerciales','propale','valider',NULL,'d',0),(25,'Envoyer les propositions commerciales aux clients','propale','envoyer',NULL,'d',0),(26,'Cloturer les propositions commerciales','propale','cloturer',NULL,'d',0),(27,'Supprimer les propositions commerciales','propale','supprimer',NULL,'d',0),(31,'Lire les produits/services','produit','lire',NULL,'r',1),(32,'Crï¿½er modifier les produits/services','produit','creer',NULL,'w',0),(33,'Commander les produits/services','produit','commander',NULL,'w',0),(34,'Supprimer les produits/services','produit','supprimer',NULL,'d',0),(38,'Exporter les produits','produit','export',NULL,'r',0),(81,'Lire les commandes clients','commande','lire',NULL,'r',1),(82,'Crï¿½er modifier les commandes clients','commande','creer',NULL,'w',0),(84,'Valider les commandes clients','commande','valider',NULL,'d',0),(86,'Envoyer les commandes clients','commande','envoyer',NULL,'d',0),(87,'Clï¿½turer les commandes clients','commande','cloturer',NULL,'d',0),(88,'Annuler les commandes clients','commande','annuler',NULL,'d',0),(89,'Supprimer les commandes clients','commande','supprimer',NULL,'d',0),(91,'Lire les charges','tax','charges','lire','r',1),(92,'Creer/modifier les charges','tax','charges','creer','w',0),(93,'Supprimer les charges','tax','charges','supprimer','d',0),(94,'Exporter les charges','tax','charges','export','r',0),(111,'Lire les comptes bancaires','banque','lire',NULL,'r',1),(112,'Créer/modifier montant/supprimer écriture bancaire','banque','modifier',NULL,'w',0),(113,'Configurer les comptes bancaires (créer, gérer catégories)','banque','configurer',NULL,'a',0),(114,'Rapprocher les écritures bancaires','banque','consolidate',NULL,'w',0),(115,'Exporter transactions et relevés','banque','export',NULL,'r',0),(116,'Virements entre comptes','banque','transfer',NULL,'w',0),(117,'Gérer les envois de chèques','banque','cheque',NULL,'w',0),(121,'Lire les societes','societe','lire',NULL,'r',1),(122,'Creer modifier les societes','societe','creer',NULL,'w',0),(125,'Supprimer les societes','societe','supprimer',NULL,'d',0),(126,'Exporter les societes','societe','export',NULL,'r',0),(170,'Lire les deplacements','deplacement','lire',NULL,'r',1),(171,'Creer/modifier les deplacements','deplacement','creer',NULL,'w',0),(172,'Supprimer les deplacements','deplacement','supprimer',NULL,'d',0),(221,'Consulter les mailings','mailing','lire',NULL,'r',1),(222,'Creer/modifier les mailings (sujet, destinataires...)','mailing','creer',NULL,'w',0),(223,'Valider les mailings (permet leur envoi)','mailing','valider',NULL,'w',0),(229,'Supprimer les mailings)','mailing','supprimer',NULL,'d',0),(251,'Consulter les autres utilisateurs, leurs groupes et permissions','user','user','lire','r',1),(252,'Creer/modifier les autres utilisateurs, les groupes et leurs permissions','user','user','creer','w',0),(253,'Modifier mot de passe des autres utilisateurs','user','user','password','w',0),(254,'Supprimer ou desactiver les autres utilisateurs','user','user','supprimer','d',0),(255,'Creer/modifier ses propres infos utilisateur','user','self','creer','w',1),(256,'Modifier son propre mot de passe','user','self','password','w',1),(258,'Exporter les utilisateurs','user','user','export','r',0),(261,'Consulter menu commercial','commercial','main','lire','r',1),(262,'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limitÃ©s Ã  eux-meme).','societe','client','voir','r',1),(281,'Lire les contacts','societe','contact','lire','r',1),(282,'Creer modifier les contacts','societe','contact','creer','w',0),(283,'Supprimer les contacts','societe','contact','supprimer','d',0),(286,'Exporter les contacts','societe','contact','export','d',0),(1001,'Lire les stocks','stock','lire',NULL,'r',1),(1002,'Creer/Modifier les stocks','stock','creer',NULL,'w',0),(1003,'Supprimer les stocks','stock','supprimer',NULL,'d',0),(1004,'Lire mouvements de stocks','stock','mouvement','lire','r',1),(1005,'Creer/modifier mouvements de stocks','stock','mouvement','creer','w',0),(1181,'Consulter les fournisseurs','fournisseur','lire',NULL,'r',1),(1182,'Lire les commandes fournisseur','fournisseur','commande','lire','r',1),(1183,'Creer une commande fournisseur','fournisseur','commande','creer','w',0),(1184,'Valider une commande fournisseur','fournisseur','commande','valider','w',0),(1185,'Approuver les commandes fournisseur','fournisseur','commande','approuver','w',0),(1186,'Commander une commande fournisseur','fournisseur','commande','commander','w',0),(1187,'Receptionner les commandes fournisseur','fournisseur','commande','receptionner','d',0),(1188,'Cloturer les commandes fournisseur','fournisseur','commande','cloturer','d',0),(1189,'Annuler les commandes fournisseur','fournisseur','commande','annuler','d',0),(1201,'Lire les exports','export','lire',NULL,'r',1),(1202,'Crï¿½er/modifier un export','export','creer',NULL,'w',0),(1231,'Lire les factures fournisseur','fournisseur','facture','lire','r',1),(1232,'Creer une facture fournisseur','fournisseur','facture','creer','w',0),(1233,'Valider une facture fournisseur','fournisseur','facture','valider','w',0),(1234,'Supprimer une facture fournisseur','fournisseur','facture','supprimer','d',0),(1236,'Exporter les factures fournisseurs, attributs et reglements','fournisseur','facture','export','r',0),(1321,'Exporter les factures clients, attributs et rï¿½glements','facture','facture','export','r',0),(1421,'Exporter les commandes clients et attributs','commande','commande','export','r',0),(2401,'Read actions/tasks linked to his account','agenda','myactions','read','r',1),(2402,'Create/modify/delete actions/tasks linked to his account','agenda','myactions','create','w',0),(2403,'Read actions/tasks of others','agenda','allactions','read','r',0),(2405,'Create/modify/delete actions/tasks of others','agenda','allactions','create','w',0),(2500,'Consulter les documents','ecm','read',NULL,'r',1),(2501,'Soumettre ou supprimer des documents','ecm','create',NULL,'w',0),(2515,'Administrer les rubriques de documents','ecm','setup',NULL,'w',0);
/*!40000 ALTER TABLE `llx_rights_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe`
--

DROP TABLE IF EXISTS `llx_societe`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe` (
  `rowid` int(11) NOT NULL auto_increment,
  `statut` tinyint(4) default '0',
  `parent` int(11) default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `datea` datetime default NULL,
  `nom` varchar(60) default NULL,
  `code_client` varchar(15) default NULL,
  `code_fournisseur` varchar(15) default NULL,
  `code_compta` varchar(15) default NULL,
  `code_compta_fournisseur` varchar(15) default NULL,
  `address` varchar(255) default NULL,
  `cp` varchar(10) default NULL,
  `ville` varchar(50) default NULL,
  `fk_departement` int(11) default '0',
  `fk_pays` int(11) default '0',
  `tel` varchar(20) default NULL,
  `fax` varchar(20) default NULL,
  `url` varchar(255) default NULL,
  `email` varchar(128) default NULL,
  `fk_secteur` int(11) default '0',
  `fk_effectif` int(11) default '0',
  `fk_typent` int(11) default '0',
  `fk_forme_juridique` int(11) default '0',
  `siren` varchar(16) default NULL,
  `siret` varchar(16) default NULL,
  `ape` varchar(16) default NULL,
  `idprof4` varchar(16) default NULL,
  `tva_intra` varchar(20) default NULL,
  `capital` double default NULL,
  `description` text,
  `fk_stcomm` smallint(6) default '0',
  `note` text,
  `services` tinyint(4) default '0',
  `prefix_comm` varchar(5) default NULL,
  `client` tinyint(4) default '0',
  `fournisseur` tinyint(4) default '0',
  `supplier_account` varchar(32) default NULL,
  `fk_prospectlevel` varchar(12) default NULL,
  `customer_bad` tinyint(4) default '0',
  `customer_rate` double default '0',
  `supplier_rate` double default '0',
  `rubrique` varchar(255) default NULL,
  `fk_user_creat` int(11) default NULL,
  `fk_user_modif` int(11) default NULL,
  `remise_client` double default '0',
  `mode_reglement` tinyint(4) default NULL,
  `cond_reglement` tinyint(4) default NULL,
  `tva_assuj` tinyint(4) default '1',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_societe_prefix_comm` (`prefix_comm`),
  UNIQUE KEY `uk_societe_code_client` (`code_client`),
  KEY `idx_societe_user_creat` (`fk_user_creat`),
  KEY `idx_societe_user_modif` (`fk_user_modif`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe`
--

LOCK TABLES `llx_societe` WRITE;
/*!40000 ALTER TABLE `llx_societe` DISABLE KEYS */;
INSERT INTO `llx_societe` VALUES (1,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Cumulo',NULL,NULL,NULL,NULL,'3 place de la République','56610','Arradon',0,0,'01 40 15 03 18','01 40 15 06 18',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'CU',1,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(2,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Bolix SA',NULL,NULL,NULL,NULL,'13 rue Pierre Mendès France','56350','Allaire',0,0,'01 40 15 03 18','01 40 15 06 18','www.dolibarr.com',NULL,0,0,0,54,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'LO',1,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(3,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Foo SARL',NULL,NULL,NULL,NULL,'3bis Avenue de la Liberté','22300','Ploubezre',0,0,'01 55 55 03 18','01 55 55 55 55','www.gnu.org',NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'FOO',1,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(4,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Talphinfo',NULL,NULL,NULL,NULL,'Place Dolores Ibarruri','29400','Bodilis',0,0,'01 40 15 03 18','01 40 15 06 18',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'AP',1,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(5,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Yratin SA',NULL,NULL,NULL,NULL,NULL,'29660','Carantec',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(6,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Raggos SARL',NULL,NULL,NULL,NULL,NULL,'29233','Cléder',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(7,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Pruitosa',NULL,NULL,NULL,NULL,NULL,'29870','Coat-Méal',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(8,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Stratus',NULL,NULL,NULL,NULL,NULL,'29120','Combrit',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(9,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Nimbus',NULL,NULL,NULL,NULL,'15 rue des petites écuries','29490','Guipavas',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(10,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Doli INC.',NULL,NULL,NULL,NULL,'Rue du Port','29300','Arzano',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'DO',1,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(20,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Bouleau',NULL,NULL,NULL,NULL,NULL,'22800','Le Foeil',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'BTP',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(100,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Chêne',NULL,NULL,NULL,NULL,NULL,'22330','Le Gouray',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'DEL',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(101,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Cerisier',NULL,NULL,NULL,NULL,NULL,'22290','Goudelin',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'CER',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(164,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Valphanix',NULL,NULL,NULL,NULL,NULL,'29820','Bohars',0,0,'01 40 15 03 18','01 40 15 06 18',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'AL',2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(165,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Turin',NULL,NULL,NULL,NULL,NULL,'29890','Brignogan-Plage',0,0,'01 55 55 03 18','01 55 55 55 55','http://www.ot-brignogan-plage.fr/',NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(166,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Iono',NULL,NULL,NULL,NULL,NULL,'22110','Rostrenen',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(167,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Bolan',NULL,NULL,NULL,NULL,'104 Avenue de la Marne','29820','Bohars',0,0,'01 40 15 03 18','01 40 15 06 18',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'CAL',2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(168,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Travail Temporaire Boharssais',NULL,NULL,NULL,NULL,'125 Rue des moineaux','29820','Bohars',0,0,'01 40 15 03 18','01 40 15 06 18',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'TTBOH',2,0,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(169,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Peuplier',NULL,NULL,NULL,NULL,NULL,'22300','Lanmérin',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'JP',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(170,0,NULL,'2008-08-19 19:35:31','2008-08-19 21:35:31',NULL,'Poirier',NULL,NULL,NULL,NULL,NULL,'22290','Lannebert',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'PO',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(171,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Orme',NULL,NULL,NULL,NULL,NULL,'22400','Noyal',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'ORM',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(172,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Pin',NULL,NULL,NULL,NULL,NULL,'22200','Pabu',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'PIN',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(173,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Merisier',NULL,NULL,NULL,NULL,NULL,'22510','Penguily',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'IKE',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(174,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Hêtre',NULL,NULL,NULL,NULL,NULL,'22480','Peumerit-Quintin',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'CAS',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(175,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Saule',NULL,NULL,NULL,NULL,NULL,'22800','Quintin',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'ME',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1),(176,0,NULL,'2008-08-19 19:35:31',NULL,NULL,'Tek',NULL,NULL,NULL,NULL,NULL,'22300','Rospez',0,0,'01 55 55 03 18','01 55 55 55 55',NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'LMT',0,1,NULL,NULL,0,0,0,NULL,NULL,NULL,0,NULL,NULL,1);
/*!40000 ALTER TABLE `llx_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_adresse_livraison`
--

DROP TABLE IF EXISTS `llx_societe_adresse_livraison`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_adresse_livraison` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `label` varchar(30) default NULL,
  `fk_societe` int(11) default '0',
  `nom` varchar(60) default NULL,
  `address` varchar(255) default NULL,
  `cp` varchar(10) default NULL,
  `ville` varchar(50) default NULL,
  `fk_pays` int(11) default '0',
  `tel` varchar(20) default NULL,
  `fax` varchar(20) default NULL,
  `note` text,
  `fk_user_creat` int(11) default NULL,
  `fk_user_modif` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_adresse_livraison`
--

LOCK TABLES `llx_societe_adresse_livraison` WRITE;
/*!40000 ALTER TABLE `llx_societe_adresse_livraison` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_adresse_livraison` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_commerciaux`
--

DROP TABLE IF EXISTS `llx_societe_commerciaux`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_commerciaux` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) default NULL,
  `fk_user` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_soc` (`fk_soc`,`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_commerciaux`
--

LOCK TABLES `llx_societe_commerciaux` WRITE;
/*!40000 ALTER TABLE `llx_societe_commerciaux` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_commerciaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_consult`
--

DROP TABLE IF EXISTS `llx_societe_consult`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_consult` (
  `fk_soc` int(11) default NULL,
  `fk_user` int(11) default NULL,
  `datec` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `action` enum('w','r') default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_consult`
--

LOCK TABLES `llx_societe_consult` WRITE;
/*!40000 ALTER TABLE `llx_societe_consult` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_consult` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_log`
--

DROP TABLE IF EXISTS `llx_societe_log`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_log` (
  `id` int(11) NOT NULL auto_increment,
  `datel` datetime default NULL,
  `fk_soc` int(11) default NULL,
  `fk_statut` int(11) default NULL,
  `fk_user` int(11) default NULL,
  `author` varchar(30) default NULL,
  `label` varchar(128) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_log`
--

LOCK TABLES `llx_societe_log` WRITE;
/*!40000 ALTER TABLE `llx_societe_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_prices`
--

DROP TABLE IF EXISTS `llx_societe_prices`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_prices` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) default '0',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `fk_user_author` int(11) default NULL,
  `price_level` tinyint(4) default '1',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_prices`
--

LOCK TABLES `llx_societe_prices` WRITE;
/*!40000 ALTER TABLE `llx_societe_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_remise`
--

DROP TABLE IF EXISTS `llx_societe_remise`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_remise` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) NOT NULL default '0',
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `fk_user_author` int(11) default NULL,
  `remise_client` double default '0',
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_remise`
--

LOCK TABLES `llx_societe_remise` WRITE;
/*!40000 ALTER TABLE `llx_societe_remise` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_remise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_remise_except`
--

DROP TABLE IF EXISTS `llx_societe_remise_except`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_remise_except` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) NOT NULL default '0',
  `datec` datetime default NULL,
  `amount_ht` double(24,8) NOT NULL default '0.00000000',
  `amount_tva` double(24,8) NOT NULL default '0.00000000',
  `amount_ttc` double(24,8) NOT NULL default '0.00000000',
  `tva_tx` double(6,3) NOT NULL default '0.000',
  `fk_user` int(11) NOT NULL default '0',
  `fk_facture_line` int(11) default NULL,
  `fk_facture` int(11) default NULL,
  `fk_facture_source` int(11) default NULL,
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`rowid`),
  KEY `idx_societe_remise_except_fk_user` (`fk_user`),
  KEY `idx_societe_remise_except_fk_soc` (`fk_soc`),
  KEY `idx_societe_remise_except_fk_facture_line` (`fk_facture_line`),
  KEY `idx_societe_remise_except_fk_facture` (`fk_facture`),
  KEY `idx_societe_remise_except_fk_facture_source` (`fk_facture_source`),
  CONSTRAINT `fk_societe_remise_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_facture_line` FOREIGN KEY (`fk_facture_line`) REFERENCES `llx_facturedet` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_facture_source` FOREIGN KEY (`fk_facture_source`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_remise_except`
--

LOCK TABLES `llx_societe_remise_except` WRITE;
/*!40000 ALTER TABLE `llx_societe_remise_except` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_remise_except` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_rib`
--

DROP TABLE IF EXISTS `llx_societe_rib`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_societe_rib` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) NOT NULL default '0',
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `label` varchar(30) default NULL,
  `bank` varchar(255) default NULL,
  `code_banque` varchar(7) default NULL,
  `code_guichet` varchar(6) default NULL,
  `number` varchar(255) default NULL,
  `cle_rib` varchar(5) default NULL,
  `bic` varchar(10) default NULL,
  `iban_prefix` varchar(5) default NULL,
  `domiciliation` varchar(255) default NULL,
  `proprio` varchar(60) default NULL,
  `adresse_proprio` varchar(255) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_societe_rib`
--

LOCK TABLES `llx_societe_rib` WRITE;
/*!40000 ALTER TABLE `llx_societe_rib` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_rib` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_socpeople`
--

DROP TABLE IF EXISTS `llx_socpeople`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_socpeople` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `fk_soc` int(11) default NULL,
  `civilite` varchar(6) default NULL,
  `name` varchar(50) default NULL,
  `firstname` varchar(50) default NULL,
  `address` varchar(255) default NULL,
  `cp` varchar(25) default NULL,
  `ville` varchar(255) default NULL,
  `fk_pays` int(11) default '0',
  `birthday` date default NULL,
  `poste` varchar(80) default NULL,
  `phone` varchar(30) default NULL,
  `phone_perso` varchar(30) default NULL,
  `phone_mobile` varchar(30) default NULL,
  `fax` varchar(30) default NULL,
  `email` varchar(255) default NULL,
  `jabberid` varchar(255) default NULL,
  `priv` smallint(6) NOT NULL default '0',
  `fk_user_creat` int(11) default '0',
  `fk_user_modif` int(11) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`),
  KEY `idx_socpeople_fk_soc` (`fk_soc`),
  KEY `idx_socpeople_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_socpeople_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_socpeople_user_creat_user_rowid` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_socpeople`
--

LOCK TABLES `llx_socpeople` WRITE;
/*!40000 ALTER TABLE `llx_socpeople` DISABLE KEYS */;
INSERT INTO `llx_socpeople` VALUES (10,NULL,'2008-08-19 19:35:31',1,NULL,'Maréchal','Ferdinand',NULL,NULL,NULL,0,NULL,'Administrateur système','01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(11,NULL,'2008-08-19 19:35:31',5,NULL,'Pejat','Jean-Marie',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(12,NULL,'2008-08-19 19:35:31',1,NULL,'Poulossière','Paul',NULL,NULL,NULL,0,NULL,'Directeur technique','01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(13,NULL,'2008-08-19 19:35:31',6,NULL,'Myriam','Isabelle',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(14,NULL,'2008-08-19 19:35:31',7,NULL,'Victoire','Renoir',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(15,NULL,'2008-08-19 19:35:31',7,NULL,'Baudelaire','Matthias',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(16,NULL,'2008-08-19 19:35:31',8,NULL,'Hugo','Benjamin',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(17,NULL,'2008-08-19 19:35:31',9,NULL,'Rembrandt','Stéphanie',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(18,NULL,'2008-08-19 19:35:31',10,NULL,'Picasso','Myriam',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(19,NULL,'2008-08-19 19:35:31',1,NULL,'Beethoven','John',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(20,NULL,'2008-08-19 19:35:31',2,NULL,'Corin','Arnaud',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(21,NULL,'2008-08-19 19:35:31',10,NULL,'','Joséphine',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(22,NULL,'2008-08-19 19:35:31',6,NULL,'Dumas','Elisabeth',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(30,NULL,'2008-08-19 19:35:31',3,NULL,'Philippine','Sagan',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(31,NULL,'2008-08-19 19:35:31',3,NULL,'Marie','Jeanne',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL),(41,NULL,'2008-08-19 19:35:31',4,NULL,'Alix','Hopper',NULL,NULL,NULL,0,NULL,NULL,'01 40 15 03 18',NULL,NULL,'01 40 15 06 18','dev@lafrere.net',NULL,0,1,NULL,NULL);
/*!40000 ALTER TABLE `llx_socpeople` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_stock_mouvement`
--

DROP TABLE IF EXISTS `llx_stock_mouvement`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_stock_mouvement` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datem` datetime default NULL,
  `fk_product` int(11) NOT NULL default '0',
  `fk_entrepot` int(11) NOT NULL default '0',
  `value` int(11) default NULL,
  `price` float(13,4) default '0.0000',
  `type_mouvement` smallint(6) default NULL,
  `fk_user_author` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_stock_mouvement_fk_product` (`fk_product`),
  KEY `idx_stock_mouvement_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_stock_mouvement`
--

LOCK TABLES `llx_stock_mouvement` WRITE;
/*!40000 ALTER TABLE `llx_stock_mouvement` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_stock_mouvement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_stock_valorisation`
--

DROP TABLE IF EXISTS `llx_stock_valorisation`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_stock_valorisation` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_valo` datetime default NULL,
  `fk_product` int(11) NOT NULL default '0',
  `qty_ope` float(9,3) default NULL,
  `price_ope` float(12,4) default NULL,
  `valo_ope` float(12,4) default NULL,
  `price_pmp` float(12,4) default NULL,
  `qty_stock` float(9,3) default '0.000',
  `valo_pmp` float(12,4) default NULL,
  `fk_stock_mouvement` int(11) default NULL,
  PRIMARY KEY  (`rowid`),
  KEY `idx_stock_valorisation_fk_product` (`fk_product`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_stock_valorisation`
--

LOCK TABLES `llx_stock_valorisation` WRITE;
/*!40000 ALTER TABLE `llx_stock_valorisation` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_stock_valorisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_surveys_answers`
--

DROP TABLE IF EXISTS `llx_surveys_answers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_surveys_answers` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_question` int(11) NOT NULL default '0',
  `ip_adresse` varchar(15) NOT NULL default '',
  `datec` date NOT NULL default '0000-00-00',
  `rep1` decimal(6,0) default NULL,
  `rep2` decimal(6,0) default NULL,
  `rep3` decimal(6,0) default NULL,
  `rep4` decimal(6,0) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_surveys_answers`
--

LOCK TABLES `llx_surveys_answers` WRITE;
/*!40000 ALTER TABLE `llx_surveys_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_surveys_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_surveys_answers_summary`
--

DROP TABLE IF EXISTS `llx_surveys_answers_summary`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_surveys_answers_summary` (
  `fk_question` int(11) NOT NULL default '0',
  `nb_rep1` decimal(10,0) NOT NULL default '0',
  `nb_rep2` decimal(10,0) default NULL,
  `nb_rep3` decimal(10,0) default NULL,
  `nb_rep4` decimal(10,0) default NULL,
  `tot_rep1` decimal(10,0) NOT NULL default '0',
  `tot_rep2` decimal(10,0) default NULL,
  `tot_rep3` decimal(10,0) default NULL,
  `tot_rep4` decimal(10,0) default NULL,
  PRIMARY KEY  (`fk_question`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_surveys_answers_summary`
--

LOCK TABLES `llx_surveys_answers_summary` WRITE;
/*!40000 ALTER TABLE `llx_surveys_answers_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_surveys_answers_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_surveys_questions`
--

DROP TABLE IF EXISTS `llx_surveys_questions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_surveys_questions` (
  `rowid` int(11) NOT NULL auto_increment,
  `type_question` decimal(1,0) NOT NULL default '0',
  `group_question` varchar(16) NOT NULL default 'NONE',
  `status` decimal(1,0) NOT NULL default '0',
  `lib` varchar(255) NOT NULL default '',
  `lib_rep1` varchar(100) NOT NULL default '',
  `lib_rep2` varchar(100) default NULL,
  `lib_rep3` varchar(100) default NULL,
  `lib_rep4` varchar(100) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_surveys_questions`
--

LOCK TABLES `llx_surveys_questions` WRITE;
/*!40000 ALTER TABLE `llx_surveys_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_surveys_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_transaction_bplc`
--

DROP TABLE IF EXISTS `llx_transaction_bplc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_transaction_bplc` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `ipclient` varchar(20) default NULL,
  `num_transaction` varchar(10) default NULL,
  `date_transaction` varchar(10) default NULL,
  `heure_transaction` varchar(10) default NULL,
  `num_autorisation` varchar(10) default NULL,
  `cle_acceptation` varchar(5) default NULL,
  `code_retour` int(11) default NULL,
  `ref_commande` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_transaction_bplc`
--

LOCK TABLES `llx_transaction_bplc` WRITE;
/*!40000 ALTER TABLE `llx_transaction_bplc` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_transaction_bplc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_tva`
--

DROP TABLE IF EXISTS `llx_tva`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_tva` (
  `rowid` int(11) NOT NULL auto_increment,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datep` date default NULL,
  `datev` date default NULL,
  `amount` double NOT NULL default '0',
  `label` varchar(255) default NULL,
  `note` text,
  `fk_bank` int(11) default NULL,
  `fk_user_creat` int(11) default NULL,
  `fk_user_modif` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_tva`
--

LOCK TABLES `llx_tva` WRITE;
/*!40000 ALTER TABLE `llx_tva` DISABLE KEYS */;
INSERT INTO `llx_tva` VALUES (23,'2008-08-19 19:35:31','2001-11-11','2001-10-01',1960,NULL,NULL,NULL,NULL,NULL),(24,'2008-08-19 19:35:31','2001-04-11','2001-01-01',2000,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user`
--

DROP TABLE IF EXISTS `llx_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_user` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `login` varchar(24) NOT NULL default '',
  `pass` varchar(32) default NULL,
  `pass_crypted` varchar(128) default NULL,
  `pass_temp` varchar(32) default NULL,
  `name` varchar(50) default NULL,
  `firstname` varchar(50) default NULL,
  `office_phone` varchar(20) default NULL,
  `office_fax` varchar(20) default NULL,
  `user_mobile` varchar(20) default NULL,
  `email` varchar(255) default NULL,
  `admin` smallint(6) default '0',
  `webcal_login` varchar(25) default NULL,
  `phenix_login` varchar(25) default NULL,
  `phenix_pass` varchar(128) default NULL,
  `module_comm` smallint(6) default '1',
  `module_compta` smallint(6) default '1',
  `fk_societe` int(11) default NULL,
  `fk_socpeople` int(11) default NULL,
  `fk_member` int(11) default NULL,
  `note` text,
  `datelastlogin` datetime default NULL,
  `datepreviouslogin` datetime default NULL,
  `egroupware_id` int(11) default NULL,
  `ldap_sid` varchar(255) default NULL,
  `statut` tinyint(4) default '1',
  `lang` varchar(6) default NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `uk_user_login` (`login`),
  UNIQUE KEY `uk_user_fk_socpeople` (`fk_socpeople`),
  UNIQUE KEY `uk_user_fk_member` (`fk_member`),
  KEY `uk_user_fk_societe` (`fk_societe`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_user`
--

LOCK TABLES `llx_user` WRITE;
/*!40000 ALTER TABLE `llx_user` DISABLE KEYS */;
INSERT INTO `llx_user` VALUES (1,'2008-08-19 21:35:31','2008-08-19 19:37:03','demo','demo',NULL,NULL,'demo','demo',NULL,NULL,NULL,NULL,0,'demo',NULL,NULL,1,1,NULL,NULL,NULL,NULL,'2008-08-19 21:35:39',NULL,NULL,NULL,1,NULL),(2,'2008-08-19 21:35:31','2008-08-19 19:35:31','demo1','demo',NULL,NULL,'demo1','demo1',NULL,NULL,NULL,NULL,0,'demo1',NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL),(3,'2008-08-19 21:35:31','2008-08-19 19:35:31','demo2','demo',NULL,NULL,'demo2','demo2',NULL,NULL,NULL,NULL,0,'demo2',NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL);
/*!40000 ALTER TABLE `llx_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_alert`
--

DROP TABLE IF EXISTS `llx_user_alert`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_user_alert` (
  `rowid` int(11) NOT NULL auto_increment,
  `type` int(11) default NULL,
  `fk_contact` int(11) default NULL,
  `fk_user` int(11) default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_user_alert`
--

LOCK TABLES `llx_user_alert` WRITE;
/*!40000 ALTER TABLE `llx_user_alert` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_alert` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_clicktodial`
--

DROP TABLE IF EXISTS `llx_user_clicktodial`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_user_clicktodial` (
  `fk_user` int(11) NOT NULL default '0',
  `login` varchar(32) default NULL,
  `pass` varchar(64) default NULL,
  `poste` varchar(20) default NULL,
  PRIMARY KEY  (`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_user_clicktodial`
--

LOCK TABLES `llx_user_clicktodial` WRITE;
/*!40000 ALTER TABLE `llx_user_clicktodial` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_clicktodial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_entrepot`
--

DROP TABLE IF EXISTS `llx_user_entrepot`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_user_entrepot` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_entrepot` int(10) unsigned default NULL,
  `fk_user` int(10) unsigned default NULL,
  `consult` tinyint(1) unsigned default NULL,
  `send` tinyint(1) unsigned default NULL,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_user_entrepot`
--

LOCK TABLES `llx_user_entrepot` WRITE;
/*!40000 ALTER TABLE `llx_user_entrepot` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_entrepot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_param`
--

DROP TABLE IF EXISTS `llx_user_param`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_user_param` (
  `fk_user` int(11) NOT NULL default '0',
  `page` varchar(255) NOT NULL default '',
  `param` varchar(64) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  UNIQUE KEY `fk_user` (`fk_user`,`page`,`param`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_user_param`
--

LOCK TABLES `llx_user_param` WRITE;
/*!40000 ALTER TABLE `llx_user_param` DISABLE KEYS */;
INSERT INTO `llx_user_param` VALUES (1,'','MAIN_THEME','eldy');
/*!40000 ALTER TABLE `llx_user_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_rights`
--

DROP TABLE IF EXISTS `llx_user_rights`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_user_rights` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_user` int(11) NOT NULL default '0',
  `fk_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_user` (`fk_user`,`fk_id`),
  CONSTRAINT `fk_user_rights_fk_user_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=1357 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_user_rights`
--

LOCK TABLES `llx_user_rights` WRITE;
/*!40000 ALTER TABLE `llx_user_rights` DISABLE KEYS */;
INSERT INTO `llx_user_rights` VALUES (1174,1,11),(1175,1,12),(1176,1,13),(1177,1,14),(1178,1,15),(1179,1,16),(1180,1,17),(1181,1,18),(1182,1,19),(1333,1,21),(1334,1,22),(1185,1,23),(1335,1,24),(1336,1,25),(1337,1,26),(1338,1,27),(1190,1,28),(1191,1,29),(1328,1,31),(1329,1,32),(1330,1,33),(1331,1,34),(1196,1,35),(1197,1,36),(1198,1,37),(1332,1,38),(1200,1,39),(1201,1,41),(1202,1,42),(1203,1,43),(1204,1,44),(1205,1,45),(1206,1,46),(1207,1,47),(1208,1,48),(1209,1,49),(1210,1,61),(1211,1,62),(1212,1,63),(1213,1,64),(1214,1,65),(1215,1,66),(1216,1,67),(1217,1,68),(1218,1,69),(1219,1,71),(1220,1,72),(1221,1,73),(1222,1,74),(1223,1,75),(1224,1,76),(1225,1,77),(1226,1,78),(1227,1,79),(1302,1,81),(1303,1,82),(1230,1,83),(1304,1,84),(1232,1,85),(1305,1,86),(1306,1,87),(1307,1,88),(1308,1,89),(1353,1,91),(1354,1,92),(1355,1,93),(1356,1,94),(1241,1,95),(1242,1,96),(1243,1,97),(1244,1,98),(1245,1,99),(1246,1,101),(1247,1,102),(1248,1,103),(1249,1,104),(1250,1,105),(1251,1,106),(1252,1,107),(1253,1,108),(1254,1,109),(1295,1,111),(1296,1,112),(1297,1,113),(1298,1,114),(1299,1,115),(1300,1,116),(1301,1,117),(1262,1,118),(1263,1,119),(1339,1,121),(1340,1,122),(1266,1,123),(1267,1,124),(1341,1,125),(1342,1,126),(1270,1,127),(1271,1,128),(1272,1,129),(1273,1,161),(1274,1,162),(1275,1,163),(1276,1,164),(1277,1,165),(1278,1,166),(1279,1,167),(1280,1,168),(1281,1,169),(1282,1,221),(1283,1,222),(1284,1,223),(1285,1,224),(1286,1,225),(1287,1,226),(1288,1,227),(1289,1,228),(1290,1,229),(1310,1,261),(1343,1,262),(1344,1,281),(1345,1,282),(1346,1,283),(1347,1,286),(1348,1,1001),(1349,1,1002),(1350,1,1003),(1351,1,1004),(1352,1,1005),(1314,1,1181),(1315,1,1182),(1316,1,1183),(1317,1,1184),(1318,1,1185),(1319,1,1186),(1320,1,1187),(1321,1,1188),(1322,1,1189),(1323,1,1231),(1324,1,1232),(1325,1,1233),(1326,1,1234),(1327,1,1236),(1309,1,1421),(1291,1,2401),(1292,1,2402),(1293,1,2403),(1294,1,2405),(1311,1,2500),(1312,1,2501),(1313,1,2515);
/*!40000 ALTER TABLE `llx_user_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup`
--

DROP TABLE IF EXISTS `llx_usergroup`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_usergroup` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `nom` varchar(255) NOT NULL default '',
  `note` text,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_usergroup`
--

LOCK TABLES `llx_usergroup` WRITE;
/*!40000 ALTER TABLE `llx_usergroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_usergroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup_rights`
--

DROP TABLE IF EXISTS `llx_usergroup_rights`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_usergroup_rights` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_usergroup` int(11) NOT NULL default '0',
  `fk_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_usergroup` (`fk_usergroup`,`fk_id`),
  CONSTRAINT `fk_usergroup_rights_fk_usergroup` FOREIGN KEY (`fk_usergroup`) REFERENCES `llx_usergroup` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_usergroup_rights`
--

LOCK TABLES `llx_usergroup_rights` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_rights` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_usergroup_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup_user`
--

DROP TABLE IF EXISTS `llx_usergroup_user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_usergroup_user` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_user` int(11) NOT NULL default '0',
  `fk_usergroup` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `fk_user` (`fk_user`,`fk_usergroup`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_usergroup_user`
--

LOCK TABLES `llx_usergroup_user` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_usergroup_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_voyage`
--

DROP TABLE IF EXISTS `llx_voyage`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_voyage` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `dateo` date default NULL,
  `date_depart` datetime default NULL,
  `date_arrivee` datetime default NULL,
  `amount` double NOT NULL default '0',
  `reduction` double NOT NULL default '0',
  `depart` varchar(255) default NULL,
  `arrivee` varchar(255) default NULL,
  `fk_type` smallint(6) default NULL,
  `fk_reduc` int(11) default NULL,
  `distance` int(11) default NULL,
  `dossier` varchar(50) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_voyage`
--

LOCK TABLES `llx_voyage` WRITE;
/*!40000 ALTER TABLE `llx_voyage` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_voyage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_voyage_reduc`
--

DROP TABLE IF EXISTS `llx_voyage_reduc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `llx_voyage_reduc` (
  `rowid` int(11) NOT NULL auto_increment,
  `datec` datetime default NULL,
  `datev` date default NULL,
  `date_debut` date default NULL,
  `date_fin` date default NULL,
  `amount` double NOT NULL default '0',
  `label` varchar(255) default NULL,
  `numero` varchar(255) default NULL,
  `fk_type` smallint(6) default NULL,
  `note` text,
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `llx_voyage_reduc`
--

LOCK TABLES `llx_voyage_reduc` WRITE;
/*!40000 ALTER TABLE `llx_voyage_reduc` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_voyage_reduc` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-08-19 19:45:46
