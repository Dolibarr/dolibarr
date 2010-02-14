-- MySQL dump 10.13  Distrib 5.1.36, for Win32 (ia32)
--
-- Host: localhost    Database: dolibarrcvs
-- ------------------------------------------------------
-- Server version	5.1.36-community-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,MYSQL40' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `llx_accountingaccount`
--

DROP TABLE IF EXISTS `llx_accountingaccount`;
CREATE TABLE `llx_accountingaccount` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pcg_version` varchar(12) NOT NULL,
  `pcg_type` varchar(20) NOT NULL,
  `pcg_subtype` varchar(20) NOT NULL,
  `label` varchar(128) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `account_parent` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_accountingaccount_fk_pcg_version` (`fk_pcg_version`),
  CONSTRAINT `fk_accountingaccount_fk_pcg_version` FOREIGN KEY (`fk_pcg_version`) REFERENCES `llx_accountingsystem` (`pcg_version`)
) TYPE=InnoDB AUTO_INCREMENT=103;

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
CREATE TABLE `llx_accountingdebcred` (
  `fk_transaction` int(11) NOT NULL,
  `fk_account` int(11) NOT NULL,
  `amount` double NOT NULL,
  `direction` varchar(1) NOT NULL
) TYPE=InnoDB;

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
CREATE TABLE `llx_accountingsystem` (
  `pcg_version` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `label` varchar(128) NOT NULL,
  `datec` varchar(12) NOT NULL,
  `fk_author` varchar(20) DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `active` smallint(6) DEFAULT '0',
  PRIMARY KEY (`pcg_version`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_accountingsystem`
--

LOCK TABLES `llx_accountingsystem` WRITE;
/*!40000 ALTER TABLE `llx_accountingsystem` DISABLE KEYS */;
INSERT INTO `llx_accountingsystem` VALUES ('PCG99-ABREGE',1,'The simple accountancy french plan','2010-02-11',NULL,'2010-02-11 08:23:24',0);
/*!40000 ALTER TABLE `llx_accountingsystem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingtransaction`
--

DROP TABLE IF EXISTS `llx_accountingtransaction`;
CREATE TABLE `llx_accountingtransaction` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(128) NOT NULL,
  `datec` date NOT NULL,
  `fk_author` varchar(20) NOT NULL,
  `tms` timestamp NOT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_facture_fourn` int(11) DEFAULT NULL,
  `fk_paiement` int(11) DEFAULT NULL,
  `fk_paiement_fourn` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_action_def` (
  `rowid` int(11) NOT NULL,
  `code` varchar(32) NOT NULL,
  `tms` timestamp NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `objet_type` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_action_def`
--

LOCK TABLES `llx_action_def` WRITE;
/*!40000 ALTER TABLE `llx_action_def` DISABLE KEYS */;
INSERT INTO `llx_action_def` VALUES (1,'NOTIFY_VAL_FICHINTER','2010-02-11 08:23:35','Validation fiche intervention','Executed when a intervention is validated','ficheinter'),(2,'NOTIFY_VAL_FAC','2010-02-11 08:23:35','Validation facture client','Executed when a customer invoice is approved','facture'),(3,'NOTIFY_APP_ORDER_SUPPLIER','2010-02-11 08:23:35','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier'),(4,'NOTIFY_REF_ORDER_SUPPLIER','2010-02-11 08:23:35','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier'),(5,'NOTIFY_VAL_ORDER','2010-02-11 08:23:35','Validation commande client','Executed when a customer order is validated','order'),(6,'NOTIFY_VAL_PROPAL','2010-02-11 08:23:35','Validation proposition client','Executed when a commercial proposal is validated','propal');
/*!40000 ALTER TABLE `llx_action_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm`
--

DROP TABLE IF EXISTS `llx_actioncomm`;
CREATE TABLE `llx_actioncomm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datep` datetime DEFAULT NULL,
  `datep2` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `datea2` datetime DEFAULT NULL,
  `fk_action` int(11) DEFAULT NULL,
  `label` varchar(50) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `fk_project` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_parent` int(11) NOT NULL DEFAULT '0',
  `fk_user_action` int(11) DEFAULT NULL,
  `fk_user_done` int(11) DEFAULT NULL,
  `priority` smallint(6) DEFAULT NULL,
  `punctual` smallint(6) NOT NULL DEFAULT '1',
  `percent` smallint(6) NOT NULL DEFAULT '0',
  `location` varchar(128) DEFAULT NULL,
  `durationp` double DEFAULT NULL,
  `durationa` double DEFAULT NULL,
  `note` text,
  `propalrowid` int(11) DEFAULT NULL,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actioncomm_datea` (`datea`),
  KEY `idx_actioncomm_fk_soc` (`fk_soc`),
  KEY `idx_actioncomm_fk_contact` (`fk_contact`),
  KEY `idx_actioncomm_fk_facture` (`fk_facture`)
) TYPE=InnoDB AUTO_INCREMENT=15;

--
-- Dumping data for table `llx_actioncomm`
--

LOCK TABLES `llx_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_actioncomm` VALUES (1,'2010-02-07 02:24:00','2010-02-07 02:24:00',NULL,NULL,50,'Société Tiers 1 ajouté','2010-02-07 02:24:16','2010-02-08 01:50:25',1,1,NULL,1,NULL,0,NULL,1,0,1,100,NULL,NULL,NULL,'Société tiers1 ajoutée dans Dolibarr\r\nAuteur: admin',NULL,NULL,NULL),(2,'2010-02-08 02:47:00',NULL,NULL,NULL,4,'Send commercial proposal','2010-02-08 01:47:13','2010-02-08 01:47:13',1,NULL,NULL,NULL,NULL,0,1,NULL,0,1,0,'',NULL,NULL,'',NULL,NULL,NULL),(3,'2010-02-17 00:00:00','2010-02-18 00:00:00',NULL,NULL,5,'Meeting','2010-02-08 01:47:57','2010-02-08 01:47:57',1,NULL,1,1,NULL,0,1,NULL,0,1,0,'Madrid',86400,NULL,'',NULL,NULL,NULL),(4,'2010-02-08 03:31:53','2010-02-08 03:31:53',NULL,NULL,50,'Société oooo ajoutée dans Dolibarr','2010-02-08 03:31:53','2010-02-08 03:31:53',1,NULL,NULL,2,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Société oooo ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL),(5,'2010-02-08 03:34:28','2010-02-08 03:34:28',NULL,NULL,50,'Société Go sup company ajoutée dans Dolibarr','2010-02-08 03:34:28','2010-02-08 03:34:28',1,NULL,NULL,3,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Société Go sup company ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL),(6,'2010-02-08 12:47:02','2010-02-08 12:47:02',NULL,NULL,50,'Facture FA1002-0001 validée dans Dolibarr','2010-02-08 12:47:02','2010-02-08 12:47:02',1,NULL,NULL,2,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Facture FA1002-0001 validée dans Dolibarr\nAuteur: admin',NULL,NULL,1),(7,'2010-02-08 15:27:53','2010-02-08 15:27:53',NULL,NULL,50,'Facture 1 validée dans Dolibarr','2010-02-08 15:27:53','2010-02-08 15:27:53',1,NULL,NULL,3,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Facture 1 validée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL),(8,'2010-02-09 11:33:48','2010-02-09 11:33:48',NULL,NULL,50,'Commande CF1002-0001 validée','2010-02-09 11:33:48','2010-02-09 11:33:48',1,NULL,NULL,3,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Commande CF1002-0001 validée\nAuteur: admin',NULL,NULL,NULL),(9,'2010-02-10 13:44:13','2010-02-10 13:44:13',NULL,NULL,50,'Société RueDuCommerce FR ajoutée dans Dolibarr','2010-02-10 13:44:13','2010-02-10 13:44:13',1,NULL,NULL,4,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Société RueDuCommerce FR ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL),(10,'2010-02-10 13:44:32','2010-02-10 13:44:32',NULL,NULL,50,'Société RueDuCommerce BE ajoutée dans Dolibarr','2010-02-10 13:44:32','2010-02-10 13:44:32',1,NULL,NULL,5,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Société RueDuCommerce BE ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL),(11,'2010-02-10 16:03:20','2010-02-10 16:03:20',NULL,NULL,50,'Facture FA1002-0002 validée dans Dolibarr','2010-02-10 16:03:20','2010-02-10 16:03:20',1,NULL,NULL,2,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Facture FA1002-0002 validée dans Dolibarr\nAuteur: admin',NULL,NULL,2),(12,'2010-02-10 16:58:35','2010-02-10 16:58:35',NULL,NULL,50,'Facture 2 validée dans Dolibarr','2010-02-10 16:58:35','2010-02-10 16:58:35',1,NULL,NULL,3,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Facture 2 validée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL),(13,'2010-02-13 00:10:12','2010-02-13 00:10:12',NULL,NULL,50,'Facture FA1002-0003 validée dans Dolibarr','2010-02-13 00:10:12','2010-02-13 00:10:12',1,NULL,NULL,5,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Facture FA1002-0003 validée dans Dolibarr\nAuteur: admin',NULL,NULL,3),(14,'2010-02-13 00:25:40','2010-02-13 00:25:40',NULL,NULL,50,'Facture FMEDI-00270 validée dans Dolibarr','2010-02-13 00:25:40','2010-02-13 00:25:40',1,NULL,NULL,2,NULL,0,NULL,1,0,1,100,'',NULL,NULL,'Facture FMEDI-00270 validée dans Dolibarr\nAuteur: admin',NULL,NULL,4);
/*!40000 ALTER TABLE `llx_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent`
--

DROP TABLE IF EXISTS `llx_adherent`;
CREATE TABLE `llx_adherent` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `login` varchar(50) NOT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `fk_adherent_type` int(11) NOT NULL,
  `morphy` varchar(3) NOT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `adresse` text,
  `cp` varchar(30) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `pays` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_perso` varchar(30) DEFAULT NULL,
  `phone_mobile` varchar(30) DEFAULT NULL,
  `naiss` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `public` smallint(6) NOT NULL DEFAULT '0',
  `datefin` datetime DEFAULT NULL,
  `note` text,
  `datevalid` datetime DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_login` (`login`,`entity`),
  UNIQUE KEY `uk_adherent_fk_soc` (`fk_soc`),
  KEY `idx_adherent_fk_adherent_type` (`fk_adherent_type`),
  CONSTRAINT `adherent_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_adherent_adherent_type` FOREIGN KEY (`fk_adherent_type`) REFERENCES `llx_adherent_type` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=10;

--
-- Dumping data for table `llx_adherent`
--

LOCK TABLES `llx_adherent` WRITE;
/*!40000 ALTER TABLE `llx_adherent` DISABLE KEYS */;
INSERT INTO `llx_adherent` VALUES (1,1,'Destailleur','Laurent','login aaaaaa fd fd f','aaa',1,'phy',NULL,NULL,'11 Rue raymond Queneau','92500','Rueil Malmaison','1','eldy@destailleur.fr',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-07 02:46:48','2010-02-07 02:46:04','2010-02-07 17:25:20',1,1,1),(2,1,'b','b','b','jtwgpqf9',1,'phy',NULL,NULL,NULL,NULL,NULL,'1','b@b.com',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:05:04','2010-02-11 19:02:21','2010-02-11 19:05:04',1,1,1),(3,1,'c','c','c','c',1,'phy',NULL,NULL,NULL,NULL,NULL,'1','',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:04:55','2010-02-11 19:03:23','2010-02-11 19:04:55',1,1,1),(4,1,'d','d','d','d',1,'phy',NULL,NULL,NULL,NULL,NULL,'1','',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:04:46','2010-02-11 19:03:35','2010-02-11 19:04:46',1,1,1),(5,1,'e','e','e','e',1,'phy',NULL,NULL,NULL,NULL,NULL,'1','',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:04:38','2010-02-11 19:03:47','2010-02-11 19:04:38',1,1,1),(6,1,'f','f','f','f',1,'phy',NULL,NULL,NULL,NULL,NULL,'1','',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:04:30','2010-02-11 19:03:59','2010-02-11 19:04:30',1,1,1),(7,1,'g','g','g','g',1,'mor',NULL,NULL,NULL,NULL,NULL,'1','',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:04:22','2010-02-11 19:04:12','2010-02-11 19:04:22',1,1,1),(8,1,'h','h','h','h',1,'phy',NULL,NULL,'ss',NULL,NULL,'1','',NULL,NULL,NULL,NULL,'ldestailleur_124x127.png',1,0,NULL,NULL,'2010-02-11 19:05:45','2010-02-11 19:05:18','2010-02-13 22:06:49',1,1,1),(9,1,'i','i','i','i',1,'mor',NULL,NULL,NULL,NULL,NULL,'1','',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2010-02-11 19:05:36','2010-02-11 19:05:30','2010-02-11 19:05:36',1,1,1);
/*!40000 ALTER TABLE `llx_adherent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_options`
--

DROP TABLE IF EXISTS `llx_adherent_options`;
CREATE TABLE `llx_adherent_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_member` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_adherent_options` (`fk_member`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_adherent_options_label` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(8) DEFAULT NULL,
  `size` int(11) DEFAULT '0',
  `pos` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_options_label_name` (`name`,`entity`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_adherent_type` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `libelle` varchar(50) NOT NULL,
  `cotisation` varchar(3) NOT NULL DEFAULT 'yes',
  `vote` varchar(3) NOT NULL DEFAULT 'yes',
  `note` text,
  `mail_valid` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_type_libelle` (`libelle`,`entity`)
) TYPE=InnoDB AUTO_INCREMENT=2;

--
-- Dumping data for table `llx_adherent_type`
--

LOCK TABLES `llx_adherent_type` WRITE;
/*!40000 ALTER TABLE `llx_adherent_type` DISABLE KEYS */;
INSERT INTO `llx_adherent_type` VALUES (1,1,'2010-02-07 02:45:45',1,'aaaa','1','0','','');
/*!40000 ALTER TABLE `llx_adherent_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_appro`
--

DROP TABLE IF EXISTS `llx_appro`;
CREATE TABLE `llx_appro` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `fk_product` int(11) NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  `price` double DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_bank` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `dateo` date DEFAULT NULL,
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `label` varchar(255) DEFAULT NULL,
  `fk_account` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_rappro` int(11) DEFAULT NULL,
  `fk_type` varchar(4) DEFAULT NULL,
  `num_releve` varchar(50) DEFAULT NULL,
  `num_chq` varchar(50) DEFAULT NULL,
  `rappro` tinyint(4) DEFAULT '0',
  `note` text,
  `fk_bordereau` int(11) DEFAULT '0',
  `banque` varchar(255) DEFAULT NULL,
  `emetteur` varchar(255) DEFAULT NULL,
  `author` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_bank_datev` (`datev`),
  KEY `idx_bank_dateo` (`dateo`),
  KEY `idx_bank_fk_account` (`fk_account`),
  KEY `idx_bank_rappro` (`rappro`)
) TYPE=InnoDB AUTO_INCREMENT=19;

--
-- Dumping data for table `llx_bank`
--

LOCK TABLES `llx_bank` WRITE;
/*!40000 ALTER TABLE `llx_bank` DISABLE KEYS */;
INSERT INTO `llx_bank` VALUES (1,'2010-02-08 12:49:44','2009-02-07','2009-02-07',10000.00000000,'Solde initial',1,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(2,'2010-02-08 12:50:04','2009-05-07','2009-05-06',10000.00000000,'Règlement client',1,1,NULL,'CHQ',NULL,NULL,0,NULL,0,NULL,'ABS & Co',NULL),(3,'2010-02-08 13:29:38','2009-06-06','2009-06-06',-5432.00000000,'Epargne ABC',1,1,NULL,'PRE',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(4,'2010-02-08 13:30:58','2009-08-02','2009-08-02',-12780.00000000,'Remoubrsement emprunts',1,1,NULL,'PRE',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(5,'2010-02-08 13:32:40','2002-02-15','2002-02-15',9885.00000000,'Vente forfait',1,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(6,'2010-02-08 13:33:32','2009-02-24','2009-02-24',-16750.00000000,'Achat fer',1,1,NULL,'PRE',NULL,'12345',0,NULL,0,NULL,NULL,NULL),(7,'2010-02-08 13:35:23','2009-02-12','2009-02-12',-19200.00000000,'Paiemnt charges',1,1,NULL,'CHQ',NULL,'7896',0,NULL,0,NULL,NULL,NULL),(8,'2010-02-08 13:35:55','2009-02-15','2009-02-15',18700.00000000,'Récpetion accompte commande',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(9,'2010-02-08 15:28:20','2010-02-08','2010-02-08',-10.00000000,'(SupplierInvoicePayment)',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(10,'2010-02-08 15:32:43','2010-02-08','2010-02-08',-286.00000000,'Règlement charge sociale',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(11,'2010-02-08 15:45:48','2010-02-08','2010-02-08',-50.00000000,'Règlement charge sociale',1,1,NULL,'VIR',NULL,'123',0,NULL,0,NULL,NULL,NULL),(12,'2010-02-10 16:11:26','2010-02-10','2010-02-10',-1.00000000,'Règlement charge sociale',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(13,'2010-02-10 16:54:10','2010-02-10','2010-02-10',20.00000000,'(CustomerInvoicePayment)',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,'ABS & Co',NULL),(14,'2010-02-10 17:01:47','2010-02-10','2010-02-10',-10.00000000,'(SupplierInvoicePayment)',1,1,NULL,'TIP',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(15,'2010-02-14 12:18:27','2010-02-14','2010-02-14',-156.00000000,'Réglement TVA 2007',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(16,'2010-02-14 12:54:42','2010-02-14','2010-02-14',5.00000000,'(CustomerInvoicePayment)',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,'ABS & Co',NULL),(17,'2010-02-14 12:56:59','2010-02-14','2010-02-14',-100.00000000,'Règlement charge sociale',1,1,NULL,'TIP',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(18,'2010-02-14 13:16:23','2010-02-14','2010-02-14',-20.00000000,'Règlement charge sociale',1,1,NULL,'TIP',NULL,NULL,0,NULL,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_account`
--

DROP TABLE IF EXISTS `llx_bank_account`;
CREATE TABLE `llx_bank_account` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `ref` varchar(12) NOT NULL,
  `label` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `bank` varchar(60) DEFAULT NULL,
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `bic` varchar(11) DEFAULT NULL,
  `iban_prefix` varchar(34) DEFAULT NULL,
  `country_iban` varchar(2) DEFAULT NULL,
  `cle_iban` varchar(2) DEFAULT NULL,
  `domiciliation` varchar(255) DEFAULT NULL,
  `proprio` varchar(60) DEFAULT NULL,
  `adresse_proprio` varchar(255) DEFAULT NULL,
  `courant` smallint(6) NOT NULL DEFAULT '0',
  `clos` smallint(6) NOT NULL DEFAULT '0',
  `rappro` smallint(6) DEFAULT '1',
  `url` varchar(128) DEFAULT NULL,
  `account_number` varchar(8) DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL,
  `min_allowed` int(11) DEFAULT '0',
  `min_desired` int(11) DEFAULT '0',
  `comment` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bank_account_label` (`label`,`entity`)
) TYPE=InnoDB AUTO_INCREMENT=2;

--
-- Dumping data for table `llx_bank_account`
--

LOCK TABLES `llx_bank_account` WRITE;
/*!40000 ALTER TABLE `llx_bank_account` DISABLE KEYS */;
INSERT INTO `llx_bank_account` VALUES (1,'2010-02-08 12:49:44','2010-02-08 12:49:44','BAQ1','Banque postale du Nord',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,1,NULL,'','EUR',-500,300,'<br />');
/*!40000 ALTER TABLE `llx_bank_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_categ`
--

DROP TABLE IF EXISTS `llx_bank_categ`;
CREATE TABLE `llx_bank_categ` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_bank_class` (
  `lineid` int(11) NOT NULL,
  `fk_categ` int(11) NOT NULL,
  UNIQUE KEY `uk_bank_class_lineid` (`lineid`,`fk_categ`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_bank_url` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_bank` int(11) DEFAULT NULL,
  `url_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bank_url` (`fk_bank`,`type`)
) TYPE=InnoDB AUTO_INCREMENT=23;

--
-- Dumping data for table `llx_bank_url`
--

LOCK TABLES `llx_bank_url` WRITE;
/*!40000 ALTER TABLE `llx_bank_url` DISABLE KEYS */;
INSERT INTO `llx_bank_url` VALUES (1,2,1,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(2,2,2,'/compta/fiche.php?socid=','ABS & Co','company'),(3,9,1,'/fourn/paiement/fiche.php?id=','(paiement)','payment_supplier'),(4,9,3,'/fourn/fiche.php?socid=','Go sup company','company'),(5,10,1,'/compta/sociales/charges.php?id=','(socialcontribution)','sc'),(6,11,4,'/compta/sociales/charges.php?id=','(socialcontribution)','sc'),(7,12,2,'/compta/payment_sc/fiche.php?id=','(paiement)','payment_sc'),(8,13,2,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(9,13,2,'/compta/fiche.php?socid=','ABS & Co','company'),(12,14,2,'/fourn/paiement/fiche.php?id=','(paiement)','payment_supplier'),(13,14,3,'/fourn/fiche.php?socid=','Go sup company','company'),(14,15,1,'/compta/tva/fiche.php?id=','(VATPayment)','payment_vat'),(15,16,3,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(16,16,2,'/compta/fiche.php?socid=','ABS & Co','company'),(21,17,0,'/compta/payment_sc/fiche.php?id=','(paiement)','payment_sc'),(22,18,0,'/compta/payment_sc/fiche.php?id=','(paiement)','payment_sc');
/*!40000 ALTER TABLE `llx_bank_url` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bookmark`
--

DROP TABLE IF EXISTS `llx_bookmark`;
CREATE TABLE `llx_bookmark` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `dateb` datetime DEFAULT NULL,
  `url` varchar(128) NOT NULL,
  `target` varchar(16) DEFAULT NULL,
  `title` varchar(64) DEFAULT NULL,
  `favicon` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bookmark_url` (`fk_user`,`url`),
  UNIQUE KEY `uk_bookmark_title` (`fk_user`,`title`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_bordereau_cheque` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime NOT NULL,
  `date_bordereau` date DEFAULT NULL,
  `number` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `amount` double(24,8) NOT NULL,
  `nbcheque` smallint(6) NOT NULL,
  `fk_bank_account` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bordereau_cheque` (`number`,`entity`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_boxes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `box_id` int(11) NOT NULL,
  `position` smallint(6) NOT NULL,
  `box_order` varchar(3) NOT NULL,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes` (`box_id`,`position`,`fk_user`),
  KEY `idx_boxes_boxid` (`box_id`),
  KEY `idx_boxes_fk_user` (`fk_user`),
  CONSTRAINT `fk_boxes_box_id` FOREIGN KEY (`box_id`) REFERENCES `llx_boxes_def` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=7;

--
-- Dumping data for table `llx_boxes`
--

LOCK TABLES `llx_boxes` WRITE;
/*!40000 ALTER TABLE `llx_boxes` DISABLE KEYS */;
INSERT INTO `llx_boxes` VALUES (1,1,0,'B02',0),(2,5,0,'B04',0),(3,29,0,'B06',0),(4,12,0,'A01',0),(5,27,0,'A03',0),(6,11,0,'A05',0);
/*!40000 ALTER TABLE `llx_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_boxes_def`
--

DROP TABLE IF EXISTS `llx_boxes_def`;
CREATE TABLE `llx_boxes_def` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes_def` (`file`,`entity`)
) TYPE=InnoDB AUTO_INCREMENT=33;

--
-- Dumping data for table `llx_boxes_def`
--

LOCK TABLES `llx_boxes_def` WRITE;
/*!40000 ALTER TABLE `llx_boxes_def` DISABLE KEYS */;
INSERT INTO `llx_boxes_def` VALUES (1,'box_clients.php',1,'2010-02-04 23:02:15',NULL),(2,'box_prospect.php',1,'2010-02-04 23:02:15',NULL),(5,'box_actions.php',1,'2010-02-04 23:02:34',NULL),(11,'box_fournisseurs.php',1,'2010-02-08 03:32:57',NULL),(12,'box_factures_fourn_imp.php',1,'2010-02-08 03:32:57',NULL),(13,'box_factures_fourn.php',1,'2010-02-08 03:32:57',NULL),(14,'box_propales.php',1,'2010-02-08 12:35:38',NULL),(21,'box_bookmarks.php',1,'2010-02-08 12:36:37',NULL),(25,'box_services_vendus.php',1,'2010-02-13 23:34:54',NULL),(27,'box_factures_imp.php',1,'2010-02-14 13:49:41',NULL),(28,'box_factures.php',1,'2010-02-14 13:49:41',NULL),(29,'box_comptes.php',1,'2010-02-14 13:49:41',NULL),(31,'box_produits.php',1,'2010-02-14 13:50:14',NULL),(32,'box_commandes.php',1,'2010-02-14 13:53:39',NULL);
/*!40000 ALTER TABLE `llx_boxes_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_actioncomm`
--

DROP TABLE IF EXISTS `llx_c_actioncomm`;
CREATE TABLE `llx_c_actioncomm` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'system',
  `libelle` varchar(30) NOT NULL,
  `module` varchar(16) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `todo` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_c_actioncomm`
--

LOCK TABLES `llx_c_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_c_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_c_actioncomm` VALUES (1,'AC_TEL','system','Appel Téléphonique',NULL,1,NULL),(2,'AC_FAX','system','Envoi Fax',NULL,1,NULL),(3,'AC_PROP','system','Envoi Proposition','propal',1,NULL),(4,'AC_EMAIL','system','Envoi Email',NULL,1,NULL),(5,'AC_RDV','system','Rendez-vous',NULL,1,NULL),(8,'AC_COM','system','Envoi Commande','order',1,NULL),(9,'AC_FAC','system','Envoi Facture','invoice',1,NULL),(50,'AC_OTH','system','Autre',NULL,1,NULL);
/*!40000 ALTER TABLE `llx_c_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_barcode_type`
--

DROP TABLE IF EXISTS `llx_c_barcode_type`;
CREATE TABLE `llx_c_barcode_type` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `libelle` varchar(50) NOT NULL,
  `coder` varchar(16) NOT NULL,
  `example` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=7;

--
-- Dumping data for table `llx_c_barcode_type`
--

LOCK TABLES `llx_c_barcode_type` WRITE;
/*!40000 ALTER TABLE `llx_c_barcode_type` DISABLE KEYS */;
INSERT INTO `llx_c_barcode_type` VALUES (1,'EAN8',1,'EAN8','0','1234567'),(2,'EAN13',1,'EAN13','0','123456789012'),(3,'UPC',1,'UPC','0','123456789012'),(4,'ISBN',1,'ISBN','0','123456789'),(5,'C39',1,'Code 39','0','1234567890'),(6,'C128',1,'Code 128','0','ABCD1234567890');
/*!40000 ALTER TABLE `llx_c_barcode_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_chargesociales`
--

DROP TABLE IF EXISTS `llx_c_chargesociales`;
CREATE TABLE `llx_c_chargesociales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(80) DEFAULT NULL,
  `deductible` smallint(6) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `code` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) TYPE=InnoDB AUTO_INCREMENT=61;

--
-- Dumping data for table `llx_c_chargesociales`
--

LOCK TABLES `llx_c_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_c_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_c_chargesociales` VALUES (1,'Allocations familiales',1,1,'TAXFAM',1),(2,'GSG Deductible',1,1,'TAXCSGD',1),(3,'GSG/CRDS NON Deductible',0,1,'TAXCSGND',1),(10,'Taxe apprenttissage',0,1,'TAXAPP',1),(11,'Taxe professionnelle',0,1,'TAXPRO',1),(20,'Impots locaux/fonciers',0,1,'TAXFON',1),(25,'Impots revenus',0,1,'TAXREV',1),(30,'Assurance Sante',0,1,'TAXSECU',1),(40,'Mutuelle',0,1,'TAXMUT',1),(50,'Assurance vieillesse',0,1,'TAXRET',1),(60,'Assurance Chomage',0,1,'TAXCHOM',1);
/*!40000 ALTER TABLE `llx_c_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_civilite`
--

DROP TABLE IF EXISTS `llx_c_civilite`;
CREATE TABLE `llx_c_civilite` (
  `rowid` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `civilite` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_c_currencies` (
  `code` varchar(2) NOT NULL,
  `code_iso` varchar(3) NOT NULL,
  `label` varchar(64) DEFAULT NULL,
  `labelsing` varchar(64) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`),
  UNIQUE KEY `uk_c_currencies_code_iso` (`code_iso`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_c_currencies`
--

LOCK TABLES `llx_c_currencies` WRITE;
/*!40000 ALTER TABLE `llx_c_currencies` DISABLE KEYS */;
INSERT INTO `llx_c_currencies` VALUES ('AD','AUD','Dollars australiens',NULL,1),('AE','AED','Arabes emirats dirham',NULL,1),('BT','THB','Bath thailandais',NULL,1),('CD','DKK','Couronnes dannoises',NULL,1),('CF','XAF','Francs cfa beac',NULL,1),('CN','NOK','Couronnes norvegiennes',NULL,1),('CS','SEK','Couronnes suedoises',NULL,1),('CZ','CZK','Couronnes tcheques',NULL,1),('DA','DZD','Dinar algérien',NULL,1),('DC','CAD','Dollars canadiens',NULL,1),('DH','MAD','Dirham',NULL,1),('DR','GRD','Drachme (grece)',NULL,1),('DS','SGD','Dollars singapour',NULL,1),('DU','USD','Dollars us','Dollar',1),('EC','XEU','Ecus',NULL,1),('EG','EGP','Livre egyptienne',NULL,1),('ES','PTE','Escudos',NULL,0),('EU','EUR','Euros','Euro',1),('FB','BEF','Francs belges',NULL,0),('FF','FRF','Francs francais',NULL,0),('FH','HUF','Forint hongrois',NULL,1),('FL','LUF','Francs luxembourgeois',NULL,0),('FO','NLG','Florins',NULL,1),('FS','CHF','Francs suisses',NULL,1),('ID','IDR','Rupiahs d\'indonesie',NULL,1),('IN','INR','Roupie indienne',NULL,1),('KR','KRW','Won coree du sud',NULL,1),('LI','IEP','Livres irlandaises',NULL,1),('LK','LKR','Roupies sri lanka',NULL,1),('LR','ITL','Lires',NULL,0),('LS','GBP','Livres sterling',NULL,1),('LT','LTL','Litas',NULL,1),('MA','DEM','Deutsch mark',NULL,0),('MF','FIM','Mark finlandais',NULL,1),('MU','MUR','Roupies mauritiennes',NULL,1),('NZ','NZD','Dollar neo-zelandais',NULL,1),('PA','ARP','Pesos argentins',NULL,1),('PC','CLP','Pesos chilien',NULL,1),('PE','ESP','Pesete',NULL,1),('PL','PLN','Zlotys polonais',NULL,1),('RB','BRL','Real bresilien',NULL,1),('RU','SUR','Rouble',NULL,1),('SA','ATS','Shiliing autrichiens',NULL,1),('SK','SKK','Couronnes slovaques',NULL,1),('SR','SAR','Saudi riyal',NULL,1),('TD','TND','Dinar tunisien',NULL,1),('TR','TRL','Livre turque',NULL,1),('TW','TWD','Dollar taiwanais',NULL,1),('YC','CNY','Yuang chinois',NULL,1),('YE','JPY','Yens',NULL,1),('ZA','ZAR','Rand africa',NULL,1);
/*!40000 ALTER TABLE `llx_c_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_departements`
--

DROP TABLE IF EXISTS `llx_c_departements`;
CREATE TABLE `llx_c_departements` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code_departement` varchar(6) NOT NULL,
  `fk_region` int(11) DEFAULT NULL,
  `cheflieu` varchar(7) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `ncc` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_departements` (`code_departement`,`fk_region`),
  KEY `idx_departements_fk_region` (`fk_region`)
) TYPE=InnoDB AUTO_INCREMENT=264;

--
-- Dumping data for table `llx_c_departements`
--

LOCK TABLES `llx_c_departements` WRITE;
/*!40000 ALTER TABLE `llx_c_departements` DISABLE KEYS */;
INSERT INTO `llx_c_departements` VALUES (1,'0',0,'0',0,'-','-',1),(2,'01',82,'01053',5,'AIN','Ain',1),(3,'02',22,'02408',5,'AISNE','Aisne',1),(4,'03',83,'03190',5,'ALLIER','Allier',1),(5,'04',93,'04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence',1),(6,'05',93,'05061',4,'HAUTES-ALPES','Hautes-Alpes',1),(7,'06',93,'06088',4,'ALPES-MARITIMES','Alpes-Maritimes',1),(8,'07',82,'07186',5,'ARDECHE','Ardèche',1),(9,'08',21,'08105',4,'ARDENNES','Ardennes',1),(10,'09',73,'09122',5,'ARIEGE','Ariège',1),(11,'10',21,'10387',5,'AUBE','Aube',1),(12,'11',91,'11069',5,'AUDE','Aude',1),(13,'12',73,'12202',5,'AVEYRON','Aveyron',1),(14,'13',93,'13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône',1),(15,'14',25,'14118',2,'CALVADOS','Calvados',1),(16,'15',83,'15014',2,'CANTAL','Cantal',1),(17,'16',54,'16015',3,'CHARENTE','Charente',1),(18,'17',54,'17300',3,'CHARENTE-MARITIME','Charente-Maritime',1),(19,'18',24,'18033',2,'CHER','Cher',1),(20,'19',74,'19272',3,'CORREZE','Corrèze',1),(21,'2A',94,'2A004',3,'CORSE-DU-SUD','Corse-du-Sud',1),(22,'2B',94,'2B033',3,'HAUTE-CORSE','Haute-Corse',1),(23,'21',26,'21231',3,'COTE-D\'OR','Côte-d\'Or',1),(24,'22',53,'22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor',1),(25,'23',74,'23096',3,'CREUSE','Creuse',1),(26,'24',72,'24322',3,'DORDOGNE','Dordogne',1),(27,'25',43,'25056',2,'DOUBS','Doubs',1),(28,'26',82,'26362',3,'DROME','Drôme',1),(29,'27',23,'27229',5,'EURE','Eure',1),(30,'28',24,'28085',1,'EURE-ET-LOIR','Eure-et-Loir',1),(31,'29',53,'29232',2,'FINISTERE','Finistère',1),(32,'30',91,'30189',2,'GARD','Gard',1),(33,'31',73,'31555',3,'HAUTE-GARONNE','Haute-Garonne',1),(34,'32',73,'32013',2,'GERS','Gers',1),(35,'33',72,'33063',3,'GIRONDE','Gironde',1),(36,'34',91,'34172',5,'HERAULT','Hérault',1),(37,'35',53,'35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine',1),(38,'36',24,'36044',5,'INDRE','Indre',1),(39,'37',24,'37261',1,'INDRE-ET-LOIRE','Indre-et-Loire',1),(40,'38',82,'38185',5,'ISERE','Isère',1),(41,'39',43,'39300',2,'JURA','Jura',1),(42,'40',72,'40192',4,'LANDES','Landes',1),(43,'41',24,'41018',0,'LOIR-ET-CHER','Loir-et-Cher',1),(44,'42',82,'42218',3,'LOIRE','Loire',1),(45,'43',83,'43157',3,'HAUTE-LOIRE','Haute-Loire',1),(46,'44',52,'44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique',1),(47,'45',24,'45234',2,'LOIRET','Loiret',1),(48,'46',73,'46042',2,'LOT','Lot',1),(49,'47',72,'47001',0,'LOT-ET-GARONNE','Lot-et-Garonne',1),(50,'48',91,'48095',3,'LOZERE','Lozère',1),(51,'49',52,'49007',0,'MAINE-ET-LOIRE','Maine-et-Loire',1),(52,'50',25,'50502',3,'MANCHE','Manche',1),(53,'51',21,'51108',3,'MARNE','Marne',1),(54,'52',21,'52121',3,'HAUTE-MARNE','Haute-Marne',1),(55,'53',52,'53130',3,'MAYENNE','Mayenne',1),(56,'54',41,'54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle',1),(57,'55',41,'55029',3,'MEUSE','Meuse',1),(58,'56',53,'56260',2,'MORBIHAN','Morbihan',1),(59,'57',41,'57463',3,'MOSELLE','Moselle',1),(60,'58',26,'58194',3,'NIEVRE','Nièvre',1),(61,'59',31,'59350',2,'NORD','Nord',1),(62,'60',22,'60057',5,'OISE','Oise',1),(63,'61',25,'61001',5,'ORNE','Orne',1),(64,'62',31,'62041',2,'PAS-DE-CALAIS','Pas-de-Calais',1),(65,'63',83,'63113',2,'PUY-DE-DOME','Puy-de-Dôme',1),(66,'64',72,'64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques',1),(67,'65',73,'65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées',1),(68,'66',91,'66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales',1),(69,'67',42,'67482',2,'BAS-RHIN','Bas-Rhin',1),(70,'68',42,'68066',2,'HAUT-RHIN','Haut-Rhin',1),(71,'69',82,'69123',2,'RHONE','Rhône',1),(72,'70',43,'70550',3,'HAUTE-SAONE','Haute-Saône',1),(73,'71',26,'71270',0,'SAONE-ET-LOIRE','Saône-et-Loire',1),(74,'72',52,'72181',3,'SARTHE','Sarthe',1),(75,'73',82,'73065',3,'SAVOIE','Savoie',1),(76,'74',82,'74010',3,'HAUTE-SAVOIE','Haute-Savoie',1),(77,'75',11,'75056',0,'PARIS','Paris',1),(78,'76',23,'76540',3,'SEINE-MARITIME','Seine-Maritime',1),(79,'77',11,'77288',0,'SEINE-ET-MARNE','Seine-et-Marne',1),(80,'78',11,'78646',4,'YVELINES','Yvelines',1),(81,'79',54,'79191',4,'DEUX-SEVRES','Deux-Sèvres',1),(82,'80',22,'80021',3,'SOMME','Somme',1),(83,'81',73,'81004',2,'TARN','Tarn',1),(84,'82',73,'82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne',1),(85,'83',93,'83137',2,'VAR','Var',1),(86,'84',93,'84007',0,'VAUCLUSE','Vaucluse',1),(87,'85',52,'85191',3,'VENDEE','Vendée',1),(88,'86',54,'86194',3,'VIENNE','Vienne',1),(89,'87',74,'87085',3,'HAUTE-VIENNE','Haute-Vienne',1),(90,'88',41,'88160',4,'VOSGES','Vosges',1),(91,'89',26,'89024',5,'YONNE','Yonne',1),(92,'90',43,'90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort',1),(93,'91',11,'91228',5,'ESSONNE','Essonne',1),(94,'92',11,'92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine',1),(95,'93',11,'93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis',1),(96,'94',11,'94028',2,'VAL-DE-MARNE','Val-de-Marne',1),(97,'95',11,'95500',2,'VAL-D\'OISE','Val-d\'Oise',1),(98,'971',1,'97105',3,'GUADELOUPE','Guadeloupe',1),(99,'972',2,'97209',3,'MARTINIQUE','Martinique',1),(100,'973',3,'97302',3,'GUYANE','Guyane',1),(101,'974',4,'97411',3,'REUNION','Réunion',1),(102,'01',201,'',1,'ANVERS','Anvers',1),(103,'02',203,'',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale',1),(104,'03',202,'',2,'BRABANT-WALLON','Brabant-Wallon',1),(105,'04',201,'',1,'BRABANT-FLAMAND','Brabant-Flamand',1),(106,'05',201,'',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale',1),(107,'06',201,'',1,'FLANDRE-ORIENTALE','Flandre-Orientale',1),(108,'07',202,'',2,'HAINAUT','Hainaut',1),(109,'08',201,'',2,'LIEGE','Liège',1),(110,'09',202,'',1,'LIMBOURG','Limbourg',1),(111,'10',202,'',2,'LUXEMBOURG','Luxembourg',1),(112,'11',201,'',2,'NAMUR','Namur',1),(113,'NSW',2801,'',1,'','New South Wales',1),(114,'VIC',2801,'',1,'','Victoria',1),(115,'QLD',2801,'',1,'','Queensland',1),(116,'SA',2801,'',1,'','South Australia',1),(117,'ACT',2801,'',1,'','Australia Capital Territory',1),(118,'TAS',2801,'',1,'','Tasmania',1),(119,'WA',2801,'',1,'','Western Australia',1),(120,'NT',2801,'',1,'','Northern Territory',1),(121,'01',419,'',19,'PAIS VASCO','País Vasco',1),(122,'02',404,'',4,'ALBACETE','Albacete',1),(123,'03',411,'',11,'ALICANTE','Alicante',1),(124,'04',401,'',1,'ALMERIA','Almería',1),(125,'05',403,'',3,'AVILA','Avila',1),(126,'06',412,'',12,'BADAJOZ','Badajoz',1),(127,'07',414,'',14,'ISLAS BALEARES','Islas Baleares',1),(128,'08',406,'',6,'BARCELONA','Barcelona',1),(129,'09',403,'',8,'BURGOS','Burgos',1),(130,'10',412,'',12,'CACERES','Cáceres',1),(131,'11',401,'',1,'CADIz','Cádiz',1),(132,'12',411,'',11,'CASTELLON','Castellón',1),(133,'13',404,'',4,'CIUDAD REAL','Ciudad Real',1),(134,'14',401,'',1,'CORDOBA','Córdoba',1),(135,'15',413,'',13,'LA CORUÑA','La Coruña',1),(136,'16',404,'',4,'CUENCA','Cuenca',1),(137,'17',406,'',6,'GERONA','Gerona',1),(138,'18',401,'',1,'GRANADA','Granada',1),(139,'19',404,'',4,'GUADALAJARA','Guadalajara',1),(140,'20',419,'',19,'GUIPUZCOA','Guipúzcoa',1),(141,'21',401,'',1,'HUELVA','Huelva',1),(142,'22',402,'',2,'HUESCA','Huesca',1),(143,'23',401,'',1,'JAEN','Jaén',1),(144,'24',403,'',3,'LEON','León',1),(145,'25',406,'',6,'LERIDA','Lérida',1),(146,'26',415,'',15,'LA RIOJA','La Rioja',1),(147,'27',413,'',13,'LUGO','Lugo',1),(148,'28',416,'',16,'MADRID','Madrid',1),(149,'29',401,'',1,'MALAGA','Málaga',1),(150,'30',417,'',17,'MURCIA','Murcia',1),(151,'31',408,'',8,'NAVARRA','Navarra',1),(152,'32',413,'',13,'ORENSE','Orense',1),(153,'33',418,'',18,'ASTURIAS','Asturias',1),(154,'34',403,'',3,'PALENCIA','Palencia',1),(155,'35',405,'',5,'LAS PALMAS','Las Palmas',1),(156,'36',413,'',13,'PONTEVEDRA','Pontevedra',1),(157,'37',403,'',3,'SALAMANCA','Salamanca',1),(158,'38',405,'',5,'STA. CRUZ DE TENERIFE','Sta. Cruz de Tenerife',1),(159,'39',410,'',10,'CANTABRIA','Cantabria',1),(160,'40',403,'',3,'SEGOVIA','Segovia',1),(161,'41',401,'',1,'SEVILLA','Sevilla',1),(162,'42',403,'',3,'SORIA','Soria',1),(163,'43',406,'',6,'TARRAGONA','Tarragona',1),(164,'44',402,'',2,'TERUEL','Teruel',1),(165,'45',404,'',5,'TOLEDO','Toledo',1),(166,'46',411,'',11,'VALENCIA','Valencia',1),(167,'47',403,'',3,'VALLADOLID','Valladolid',1),(168,'48',419,'',19,'VIZCAYA','Vizcaya',1),(169,'49',403,'',3,'ZAMORA','Zamora',1),(170,'50',402,'',1,'ZARAGOZA','Zaragoza',1),(171,'51',407,'',7,'CEUTA','Ceuta',1),(172,'52',409,'',9,'MELILLA','Melilla',1),(173,'53',420,'',20,'OTROS','Otros',1),(174,'AG',601,NULL,NULL,'ARGOVIE','Argovie',1),(175,'AI',601,NULL,NULL,'APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures',1),(176,'AR',601,NULL,NULL,'APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures',1),(177,'BE',601,NULL,NULL,'BERNE','Berne',1),(178,'BL',601,NULL,NULL,'BALE CAMPAGNE','Bâle Campagne',1),(179,'BS',601,NULL,NULL,'BALE VILLE','Bâle Ville',1),(180,'FR',601,NULL,NULL,'FRIBOURG','Fribourg',1),(181,'GE',601,NULL,NULL,'GENEVE','Genève',1),(182,'GL',601,NULL,NULL,'GLARIS','Glaris',1),(183,'GR',601,NULL,NULL,'GRISONS','Grisons',1),(184,'JU',601,NULL,NULL,'JURA','Jura',1),(185,'LU',601,NULL,NULL,'LUCERNE','Lucerne',1),(186,'NE',601,NULL,NULL,'NEUCHATEL','Neuchâtel',1),(187,'NW',601,NULL,NULL,'NIDWALD','Nidwald',1),(188,'OW',601,NULL,NULL,'OBWALD','Obwald',1),(189,'SG',601,NULL,NULL,'SAINT-GALL','Saint-Gall',1),(190,'SH',601,NULL,NULL,'SCHAFFHOUSE','Schaffhouse',1),(191,'SO',601,NULL,NULL,'SOLEURE','Soleure',1),(192,'SZ',601,NULL,NULL,'SCHWYZ','Schwyz',1),(193,'TG',601,NULL,NULL,'THURGOVIE','Thurgovie',1),(194,'TI',601,NULL,NULL,'TESSIN','Tessin',1),(195,'UR',601,NULL,NULL,'URI','Uri',1),(196,'VD',601,NULL,NULL,'VAUD','Vaud',1),(197,'VS',601,NULL,NULL,'VALAIS','Valais',1),(198,'ZG',601,NULL,NULL,'ZUG','Zug',1),(199,'ZH',601,NULL,NULL,'ZURICH','Zürich',1),(200,'AL',1101,'',0,'ALABAMA','Alabama',1),(201,'AK',1101,'',0,'ALASKA','Alaska',1),(202,'AZ',1101,'',0,'ARIZONA','Arizona',1),(203,'AR',1101,'',0,'ARKANSAS','Arkansas',1),(204,'CA',1101,'',0,'CALIFORNIA','California',1),(205,'CO',1101,'',0,'COLORADO','Colorado',1),(206,'CT',1101,'',0,'CONNECTICUT','Connecticut',1),(207,'DE',1101,'',0,'DELAWARE','Delaware',1),(208,'FL',1101,'',0,'FLORIDA','Florida',1),(209,'GA',1101,'',0,'GEORGIA','Georgia',1),(210,'HI',1101,'',0,'HAWAII','Hawaii',1),(211,'ID',1101,'',0,'IDAHO','Idaho',1),(212,'IL',1101,'',0,'ILLINOIS','Illinois',1),(213,'IN',1101,'',0,'INDIANA','Indiana',1),(214,'IA',1101,'',0,'IOWA','Iowa',1),(215,'KS',1101,'',0,'KANSAS','Kansas',1),(216,'KY',1101,'',0,'KENTUCKY','Kentucky',1),(217,'LA',1101,'',0,'LOUISIANA','Louisiana',1),(218,'ME',1101,'',0,'MAINE','Maine',1),(219,'MD',1101,'',0,'MARYLAND','Maryland',1),(220,'MA',1101,'',0,'MASSACHUSSETTS','Massachusetts',1),(221,'MI',1101,'',0,'MICHIGAN','Michigan',1),(222,'MN',1101,'',0,'MINNESOTA','Minnesota',1),(223,'MS',1101,'',0,'MISSISSIPPI','Mississippi',1),(224,'MO',1101,'',0,'MISSOURI','Missouri',1),(225,'MT',1101,'',0,'MONTANA','Montana',1),(226,'NE',1101,'',0,'NEBRASKA','Nebraska',1),(227,'NV',1101,'',0,'NEVADA','Nevada',1),(228,'NH',1101,'',0,'NEW HAMPSHIRE','New Hampshire',1),(229,'NJ',1101,'',0,'NEW JERSEY','New Jersey',1),(230,'NM',1101,'',0,'NEW MEXICO','New Mexico',1),(231,'NY',1101,'',0,'NEW YORK','New York',1),(232,'NC',1101,'',0,'NORTH CAROLINA','North Carolina',1),(233,'ND',1101,'',0,'NORTH DAKOTA','North Dakota',1),(234,'OH',1101,'',0,'OHIO','Ohio',1),(235,'OK',1101,'',0,'OKLAHOMA','Oklahoma',1),(236,'OR',1101,'',0,'OREGON','Oregon',1),(237,'PA',1101,'',0,'PENNSYLVANIA','Pennsylvania',1),(238,'RI',1101,'',0,'RHODE ISLAND','Rhode Island',1),(239,'SC',1101,'',0,'SOUTH CAROLINA','South Carolina',1),(240,'SD',1101,'',0,'SOUTH DAKOTA','South Dakota',1),(241,'TN',1101,'',0,'TENNESSEE','Tennessee',1),(242,'TX',1101,'',0,'TEXAS','Texas',1),(243,'UT',1101,'',0,'UTAH','Utah',1),(244,'VT',1101,'',0,'VERMONT','Vermont',1),(245,'VA',1101,'',0,'VIRGINIA','Virginia',1),(246,'WA',1101,'',0,'WASHINGTON','Washington',1),(247,'WV',1101,'',0,'WEST VIRGINIA','West Virginia',1),(248,'WI',1101,'',0,'WISCONSIN','Wisconsin',1),(249,'WY',1101,'',0,'WYOMING','Wyoming',1),(250,'SS',8601,NULL,NULL,NULL,'San Salvador',1),(251,'SA',8603,NULL,NULL,NULL,'Santa Ana',1),(252,'AH',8603,NULL,NULL,NULL,'Ahuachapan',1),(253,'SO',8603,NULL,NULL,NULL,'Sonsonate',1),(254,'US',8602,NULL,NULL,NULL,'Usulutan',1),(255,'SM',8602,NULL,NULL,NULL,'San Miguel',1),(256,'MO',8602,NULL,NULL,NULL,'Morazan',1),(257,'LU',8602,NULL,NULL,NULL,'La Union',1),(258,'LL',8601,NULL,NULL,NULL,'La Libertad',1),(259,'CH',8601,NULL,NULL,NULL,'Chalatenango',1),(260,'CA',8601,NULL,NULL,NULL,'Cabañas',1),(261,'LP',8601,NULL,NULL,NULL,'La Paz',1),(262,'SV',8601,NULL,NULL,NULL,'San Vicente',1),(263,'CU',8601,NULL,NULL,NULL,'Cuscatlan',1);
/*!40000 ALTER TABLE `llx_c_departements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_ecotaxe`
--

DROP TABLE IF EXISTS `llx_c_ecotaxe`;
CREATE TABLE `llx_c_ecotaxe` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_ecotaxe` (`code`)
) TYPE=InnoDB AUTO_INCREMENT=39;

--
-- Dumping data for table `llx_c_ecotaxe`
--

LOCK TABLES `llx_c_ecotaxe` WRITE;
/*!40000 ALTER TABLE `llx_c_ecotaxe` DISABLE KEYS */;
INSERT INTO `llx_c_ecotaxe` VALUES (1,'ER-A-A','Materiels electriques < 0,2kg',0.01000000,'ERP',1,1),(2,'ER-A-B','Materiels electriques >= 0,2 kg et < 0,5 kg',0.03000000,'ERP',1,1),(3,'ER-A-C','Materiels electriques >= 0,5 kg et < 1 kg',0.04000000,'ERP',1,1),(4,'ER-A-D','Materiels electriques >= 1 kg et < 2 kg',0.13000000,'ERP',1,1),(5,'ER-A-E','Materiels electriques >= 2 kg et < 4kg',0.21000000,'ERP',1,1),(6,'ER-A-F','Materiels electriques >= 4 kg et < 8 kg',0.42000000,'ERP',1,1),(7,'ER-A-G','Materiels electriques >= 8 kg et < 15 kg',0.84000000,'ERP',1,1),(8,'ER-A-H','Materiels electriques >= 15 kg et < 20 kg',1.25000000,'ERP',1,1),(9,'ER-A-I','Materiels electriques >= 20 kg et < 30 kg',1.88000000,'ERP',1,1),(10,'ER-A-J','Materiels electriques >= 30 kg',3.34000000,'ERP',1,1),(11,'ER-M-1','TV, Moniteurs < 9kg',0.84000000,'ERP',1,1),(12,'ER-M-2','TV, Moniteurs >= 9kg et < 15kg',1.67000000,'ERP',1,1),(13,'ER-M-3','TV, Moniteurs >= 15kg et < 30kg',3.34000000,'ERP',1,1),(14,'ER-M-4','TV, Moniteurs >= 30 kg',6.69000000,'ERP',1,1),(15,'EC-A-A','Materiels electriques  0,2 kg max',0.00840000,'Ecologic',1,1),(16,'EC-A-B','Materiels electriques 0,21 kg min - 0,50 kg max',0.02500000,'Ecologic',1,1),(17,'EC-A-C','Materiels electriques  0,51 kg min - 1 kg max',0.04000000,'Ecologic',1,1),(18,'EC-A-D','Materiels electriques  1,01 kg min - 2,5 kg max',0.13000000,'Ecologic',1,1),(19,'EC-A-E','Materiels electriques  2,51 kg min - 4 kg max',0.21000000,'Ecologic',1,1),(20,'EC-A-F','Materiels electriques 4,01 kg min - 8 kg max',0.42000000,'Ecologic',1,1),(21,'EC-A-G','Materiels electriques  8,01 kg min - 12 kg max',0.63000000,'Ecologic',1,1),(22,'EC-A-H','Materiels electriques 12,01 kg min - 20 kg max',1.05000000,'Ecologic',1,1),(23,'EC-A-I','Materiels electriques  20,01 kg min',1.88000000,'Ecologic',1,1),(24,'EC-M-1','TV, Moniteurs 9 kg max',0.84000000,'Ecologic',1,1),(25,'EC-M-2','TV, Moniteurs 9,01 kg min - 18 kg max',1.67000000,'Ecologic',1,1),(26,'EC-M-3','TV, Moniteurs 18,01 kg min - 36 kg max',3.34000000,'Ecologic',1,1),(27,'EC-M-4','TV, Moniteurs 36,01 kg min',6.69000000,'Ecologic',1,1),(28,'ES-M-1','TV, Moniteurs <= 20 pouces',0.84000000,'Eco-systemes',1,1),(29,'ES-M-2','TV, Moniteurs > 20 pouces et <= 32 pouces',3.34000000,'Eco-systemes',1,1),(30,'ES-M-3','TV, Moniteurs > 32 pouces et autres grands ecrans',6.69000000,'Eco-systemes',1,1),(31,'ES-A-A','Ordinateur fixe, Audio home systems (HIFI), elements hifi separes',0.84000000,'Eco-systemes',1,1),(32,'ES-A-B','Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD, instruments de musique et caisses de resonance, haut parleurs...',0.25000000,'Eco-systemes',1,1),(33,'ES-A-C','Imprimante, photocopieur, telecopieur',0.42000000,'Eco-systemes',1,1),(34,'ES-A-D','Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, telephone, repondeur, telephone sans fil, modem, telecommande, casque, camescope, baladeur mp3, radio portable, radio K7 et CD portable, radio reveil',0.08400000,'Eco-systemes',1,1),(35,'ES-A-E','GSM',0.00840000,'Eco-systemes',1,1),(36,'ES-A-F','Jouets et equipements de loisirs et de sports < 0,5 kg',0.04200000,'Eco-systemes',1,1),(37,'ES-A-G','Jouets et equipements de loisirs et de sports > 0,5 kg',0.17000000,'Eco-systemes',1,1),(38,'ES-A-H','Jouets et equipements de loisirs et de sports > 10 kg',1.25000000,'Eco-systemes',1,1);
/*!40000 ALTER TABLE `llx_c_ecotaxe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_effectif`
--

DROP TABLE IF EXISTS `llx_c_effectif`;
CREATE TABLE `llx_c_effectif` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_c_forme_juridique` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(11) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `isvatexempted` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_forme_juridique` (`code`)
) TYPE=InnoDB AUTO_INCREMENT=778;

--
-- Dumping data for table `llx_c_forme_juridique`
--

LOCK TABLES `llx_c_forme_juridique` WRITE;
/*!40000 ALTER TABLE `llx_c_forme_juridique` DISABLE KEYS */;
INSERT INTO `llx_c_forme_juridique` VALUES (667,0,0,'-',0,1),(668,11,1,'Artisan Commerçant (EI)',0,1),(669,12,1,'Commerçant (EI)',0,1),(670,13,1,'Artisan (EI)',0,1),(671,14,1,'Officier public ou ministériel',0,1),(672,15,1,'Profession libérale (EI)',0,1),(673,16,1,'Exploitant agricole',0,1),(674,17,1,'Agent commercial',0,1),(675,18,1,'Associé Gérant de société',0,1),(676,19,1,'Personne physique',0,1),(677,21,1,'Indivision',0,1),(678,22,1,'Société créée de fait',0,1),(679,23,1,'Société en participation',0,1),(680,27,1,'Paroisse hors zone concordataire',0,1),(681,29,1,'Groupement de droit privé non doté de la personnalité morale',0,1),(682,31,1,'Personne morale de droit étranger, immatriculée au RCS',0,1),(683,32,1,'Personne morale de droit étranger, non immatriculée au RCS',0,1),(684,35,1,'Régime auto-entrepreneur',0,1),(685,41,1,'Établissement public ou régie à caractère industriel ou commercial',0,1),(686,51,1,'Société coopérative commerciale particulière',0,1),(687,52,1,'Société en nom collectif',0,1),(688,53,1,'Société en commandite',0,1),(689,54,1,'Société à responsabilité limitée (SARL)',0,1),(690,55,1,'Société anonyme à conseil d administration',0,1),(691,56,1,'Société anonyme à directoire',0,1),(692,57,1,'Société par actions simplifiée',0,1),(693,58,1,'Entreprise Unipersonnelle à Responsabilité Limitée (EURL)',0,1),(694,61,1,'Caisse d\'épargne et de prévoyance',0,1),(695,62,1,'Groupement d\'intérêt économique (GIE)',0,1),(696,63,1,'Société coopérative agricole',0,1),(697,64,1,'Société non commerciale d assurances',0,1),(698,65,1,'Société civile',0,1),(699,69,1,'Personnes de droit privé inscrites au RCS',0,1),(700,71,1,'Administration de l état',0,1),(701,72,1,'Collectivité territoriale',0,1),(702,73,1,'Établissement public administratif',0,1),(703,74,1,'Personne morale de droit public administratif',0,1),(704,81,1,'Organisme gérant régime de protection social à adhésion obligatoire',0,1),(705,82,1,'Organisme mutualiste',0,1),(706,83,1,'Comité d entreprise',0,1),(707,84,1,'Organisme professionnel',0,1),(708,85,1,'Organisme de retraite à adhésion non obligatoire',0,1),(709,91,1,'Syndicat de propriétaires',0,1),(710,92,1,'Association loi 1901 ou assimilé',0,1),(711,93,1,'Fondation',0,1),(712,99,1,'Personne morale de droit privé',0,1),(713,200,2,'Indépendant',0,1),(714,201,2,'SPRL - Société à responsabilité limitée',0,1),(715,202,2,'SA   - Société Anonyme',0,1),(716,203,2,'SCRL - Société coopérative à responsabilité limitée',0,1),(717,204,2,'ASBL - Association sans but Lucratif',0,1),(718,205,2,'SCRI - Société coopérative à responsabilité illimitée',0,1),(719,206,2,'SCS  - Société en commandite simple',0,1),(720,207,2,'SCA  - Société en commandite par action',0,1),(721,208,2,'SNC  - Société en nom collectif',0,1),(722,209,2,'GIE  - Groupement d intérêt économique',0,1),(723,210,2,'GEIE - Groupement européen d intérêt économique',0,1),(724,500,5,'Limited liability corporation (GmbH)',0,1),(725,501,5,'Stock corporation (AG)',0,1),(726,502,5,'Partnerships general or limited (GmbH & CO. KG)',0,1),(727,503,5,'Sole proprietor / Private business',0,1),(728,600,6,'Raison Individuelle',0,1),(729,601,6,'Société Simple',0,1),(730,602,6,'Société en nom collectif',0,1),(731,603,6,'Société en commandite',0,1),(732,604,6,'Société anonyme (SA)',0,1),(733,605,6,'Société en commandite par actions',0,1),(734,606,6,'Société à responsabilité limitée (SARL)',0,1),(735,607,6,'Société coopérative',0,1),(736,608,6,'Association',0,1),(737,609,6,'Fondation',0,1),(738,700,7,'Sole Trader',0,1),(739,701,7,'Partnership',0,1),(740,702,7,'Private Limited Company by shares (LTD)',0,1),(741,703,7,'Public Limited Company',0,1),(742,704,7,'Workers Cooperative',0,1),(743,705,7,'Limited Liability Partnership',0,1),(744,706,7,'Franchise',0,1),(745,1000,10,'Société à responsabilité limitée (SARL)',0,1),(746,1001,10,'Société en Nom Collectif (SNC)',0,1),(747,1002,10,'Société en Commandite Simple (SCS)',0,1),(748,1003,10,'société en participation',0,1),(749,1004,10,'Société Anonyme (SA)',0,1),(750,1005,10,'Société Unipersonnelle à Responsabilité Limitée (SUARL)',0,1),(751,1006,10,'Groupement d\'intérêt économique (GEI)',0,1),(752,1007,10,'Groupe de sociétés',0,1),(753,401,4,'Empresario Individual',0,1),(754,402,4,'Comunidad de Bienes',0,1),(755,403,4,'Sociedad Civil',0,1),(756,404,4,'Sociedad Colectiva',0,1),(757,405,4,'Sociedad Limitada',0,1),(758,406,4,'Sociedad Anonima',0,1),(759,407,4,'Sociedad Comandataria por Acciones',0,1),(760,408,4,'Sociedad Comandataria Simple',0,1),(761,409,4,'Sociedad Laboral',0,1),(762,410,4,'Sociedad Cooperativa',0,1),(763,411,4,'Sociedad de Garantía Recíproca',0,1),(764,412,4,'Entidad de Capital-Riesgo',0,1),(765,413,4,'Agrupación de Interes Económico',0,1),(766,414,4,'Sociedad de Invarsión Mobiliaria',0,1),(767,415,4,'Agrupación sin Animo de Lucro',0,1),(768,15201,152,'Mauritius Private Company Limited By Shares',0,1),(769,15202,152,'Mauritius Company Limited By Guarantee',0,1),(770,15203,152,'Mauritius Public Company Limited By Shares',0,1),(771,15204,152,'Mauritius Foreign Company',0,1),(772,15205,152,'Mauritius GBC1 (Offshore Company)',0,1),(773,15206,152,'Mauritius GBC2 (International Company)',0,1),(774,15207,152,'Mauritius General Partnership',0,1),(775,15208,152,'Mauritius Limited Partnership',0,1),(776,15209,152,'Mauritius Sole Proprietorship',0,1),(777,15210,152,'Mauritius Trusts',0,1);
/*!40000 ALTER TABLE `llx_c_forme_juridique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_methode_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_c_methode_commande_fournisseur`;
CREATE TABLE `llx_c_methode_commande_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `libelle` varchar(60) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_methode_commande_fournisseur` (`code`)
) TYPE=InnoDB AUTO_INCREMENT=6;

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
CREATE TABLE `llx_c_paiement` (
  `id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `type` smallint(6) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_c_paper_format` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `label` varchar(50) NOT NULL,
  `width` float(6,2) DEFAULT '0.00',
  `height` float(6,2) DEFAULT '0.00',
  `unit` varchar(5) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=226;

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
CREATE TABLE `llx_c_pays` (
  `rowid` int(11) NOT NULL,
  `code` varchar(2) NOT NULL,
  `code_iso` varchar(3) DEFAULT NULL,
  `libelle` varchar(50) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_c_pays_code` (`code`),
  UNIQUE KEY `idx_c_pays_libelle` (`libelle`),
  UNIQUE KEY `idx_c_pays_code_iso` (`code_iso`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_c_pays`
--

LOCK TABLES `llx_c_pays` WRITE;
/*!40000 ALTER TABLE `llx_c_pays` DISABLE KEYS */;
INSERT INTO `llx_c_pays` VALUES (0,'',NULL,'-',1),(1,'FR',NULL,'France',1),(2,'BE',NULL,'Belgique',1),(3,'IT',NULL,'Italie',1),(4,'ES',NULL,'Espagne',1),(5,'DE',NULL,'Allemagne',1),(6,'CH',NULL,'Suisse',1),(7,'GB',NULL,'Royaume uni',1),(8,'IE',NULL,'Irlande',1),(9,'CN',NULL,'Chine',1),(10,'TN',NULL,'Tunisie',1),(11,'US',NULL,'Etats Unis',1),(12,'MA',NULL,'Maroc',1),(13,'DZ',NULL,'Algérie',1),(14,'CA',NULL,'Canada',1),(15,'TG',NULL,'Togo',1),(16,'GA',NULL,'Gabon',1),(17,'NL',NULL,'Pays Bas',1),(18,'HU',NULL,'Hongrie',1),(19,'RU',NULL,'Russie',1),(20,'SE',NULL,'Suède',1),(21,'CI',NULL,'Côte d\'Ivoire',1),(22,'SN',NULL,'Sénégal',1),(23,'AR',NULL,'Argentine',1),(24,'CM',NULL,'Cameroun',1),(25,'PT',NULL,'Portugal',1),(26,'SA',NULL,'Arabie Saoudite',1),(27,'MC',NULL,'Monaco',1),(28,'AU',NULL,'Australie',1),(29,'SG',NULL,'Singapour',1),(30,'AF',NULL,'Afghanistan',1),(31,'AX',NULL,'Iles Aland',1),(32,'AL',NULL,'Albanie',1),(33,'AS',NULL,'Samoa américaines',1),(34,'AD',NULL,'Andorre',1),(35,'AO',NULL,'Angola',1),(36,'AI',NULL,'Anguilla',1),(37,'AQ',NULL,'Antarctique',1),(38,'AG',NULL,'Antigua-et-Barbuda',1),(39,'AM',NULL,'Arménie',1),(40,'AW',NULL,'Aruba',1),(41,'AT',NULL,'Autriche',1),(42,'AZ',NULL,'Azerbaïdjan',1),(43,'BS',NULL,'Bahamas',1),(44,'BH',NULL,'Bahreïn',1),(45,'BD',NULL,'Bangladesh',1),(46,'BB',NULL,'Barbade',1),(47,'BY',NULL,'Biélorussie',1),(48,'BZ',NULL,'Belize',1),(49,'BJ',NULL,'Bénin',1),(50,'BM',NULL,'Bermudes',1),(51,'BT',NULL,'Bhoutan',1),(52,'BO',NULL,'Bolivie',1),(53,'BA',NULL,'Bosnie-Herzégovine',1),(54,'BW',NULL,'Botswana',1),(55,'BV',NULL,'Ile Bouvet',1),(56,'BR',NULL,'Brésil',1),(57,'IO',NULL,'Territoire britannique de l\'Océan Indien',1),(58,'BN',NULL,'Brunei',1),(59,'BG',NULL,'Bulgarie',1),(60,'BF',NULL,'Burkina Faso',1),(61,'BI',NULL,'Burundi',1),(62,'KH',NULL,'Cambodge',1),(63,'CV',NULL,'Cap-Vert',1),(64,'KY',NULL,'Iles Cayman',1),(65,'CF',NULL,'République centrafricaine',1),(66,'TD',NULL,'Tchad',1),(67,'CL',NULL,'Chili',1),(68,'CX',NULL,'Ile Christmas',1),(69,'CC',NULL,'Iles des Cocos (Keeling)',1),(70,'CO',NULL,'Colombie',1),(71,'KM',NULL,'Comores',1),(72,'CG',NULL,'Congo',1),(73,'CD',NULL,'République démocratique du Congo',1),(74,'CK',NULL,'Iles Cook',1),(75,'CR',NULL,'Costa Rica',1),(76,'HR',NULL,'Croatie',1),(77,'CU',NULL,'Cuba',1),(78,'CY',NULL,'Chypre',1),(79,'CZ',NULL,'République Tchèque',1),(80,'DK',NULL,'Danemark',1),(81,'DJ',NULL,'Djibouti',1),(82,'DM',NULL,'Dominique',1),(83,'DO',NULL,'République Dominicaine',1),(84,'EC',NULL,'Equateur',1),(85,'EG',NULL,'Egypte',1),(86,'SV',NULL,'Salvador',1),(87,'GQ',NULL,'Guinée Equatoriale',1),(88,'ER',NULL,'Erythrée',1),(89,'EE',NULL,'Estonie',1),(90,'ET',NULL,'Ethiopie',1),(91,'FK',NULL,'Iles Falkland',1),(92,'FO',NULL,'Iles Féroé',1),(93,'FJ',NULL,'Iles Fidji',1),(94,'FI',NULL,'Finlande',1),(95,'GF',NULL,'Guyane française',1),(96,'PF',NULL,'Polynésie française',1),(97,'TF',NULL,'Terres australes françaises',1),(98,'GM',NULL,'Gambie',1),(99,'GE',NULL,'Géorgie',1),(100,'GH',NULL,'Ghana',1),(101,'GI',NULL,'Gibraltar',1),(102,'GR',NULL,'Grèce',1),(103,'GL',NULL,'Groenland',1),(104,'GD',NULL,'Grenade',1),(105,'GP',NULL,'Guadeloupe',1),(106,'GU',NULL,'Guam',1),(107,'GT',NULL,'Guatemala',1),(108,'GN',NULL,'Guinée',1),(109,'GW',NULL,'Guinée-Bissao',1),(110,'GY',NULL,'Guyana',1),(111,'HT',NULL,'Haïti',1),(112,'HM',NULL,'Iles Heard et McDonald',1),(113,'VA',NULL,'Saint-Siège (Vatican)',1),(114,'HN',NULL,'Honduras',1),(115,'HK',NULL,'Hong Kong',1),(116,'IS',NULL,'Islande',1),(117,'IN',NULL,'Inde',1),(118,'ID',NULL,'Indonésie',1),(119,'IR',NULL,'Iran',1),(120,'IQ',NULL,'Iraq',1),(121,'IL',NULL,'Israël',1),(122,'JM',NULL,'Jamaïque',1),(123,'JP',NULL,'Japon',1),(124,'JO',NULL,'Jordanie',1),(125,'KZ',NULL,'Kazakhstan',1),(126,'KE',NULL,'Kenya',1),(127,'KI',NULL,'Kiribati',1),(128,'KP',NULL,'Corée du Nord',1),(129,'KR',NULL,'Corée du Sud',1),(130,'KW',NULL,'Koweït',1),(131,'KG',NULL,'Kirghizistan',1),(132,'LA',NULL,'Laos',1),(133,'LV',NULL,'Lettonie',1),(134,'LB',NULL,'Liban',1),(135,'LS',NULL,'Lesotho',1),(136,'LR',NULL,'Liberia',1),(137,'LY',NULL,'Libye',1),(138,'LI',NULL,'Liechtenstein',1),(139,'LT',NULL,'Lituanie',1),(140,'LU',NULL,'Luxembourg',1),(141,'MO',NULL,'Macao',1),(142,'MK',NULL,'ex-République yougoslave de Macédoine',1),(143,'MG',NULL,'Madagascar',1),(144,'MW',NULL,'Malawi',1),(145,'MY',NULL,'Malaisie',1),(146,'MV',NULL,'Maldives',1),(147,'ML',NULL,'Mali',1),(148,'MT',NULL,'Malte',1),(149,'MH',NULL,'Iles Marshall',1),(150,'MQ',NULL,'Martinique',1),(151,'MR',NULL,'Mauritanie',1),(152,'MU',NULL,'Maurice',1),(153,'YT',NULL,'Mayotte',1),(154,'MX',NULL,'Mexique',1),(155,'FM',NULL,'Micronésie',1),(156,'MD',NULL,'Moldavie',1),(157,'MN',NULL,'Mongolie',1),(158,'MS',NULL,'Monserrat',1),(159,'MZ',NULL,'Mozambique',1),(160,'MM',NULL,'Birmanie (Myanmar)',1),(161,'NA',NULL,'Namibie',1),(162,'NR',NULL,'Nauru',1),(163,'NP',NULL,'Népal',1),(164,'AN',NULL,'Antilles néerlandaises',1),(165,'NC',NULL,'Nouvelle-Calédonie',1),(166,'NZ',NULL,'Nouvelle-Zélande',1),(167,'NI',NULL,'Nicaragua',1),(168,'NE',NULL,'Niger',1),(169,'NG',NULL,'Nigeria',1),(170,'NU',NULL,'Nioué',1),(171,'NF',NULL,'Ile Norfolk',1),(172,'MP',NULL,'Mariannes du Nord',1),(173,'NO',NULL,'Norvège',1),(174,'OM',NULL,'Oman',1),(175,'PK',NULL,'Pakistan',1),(176,'PW',NULL,'Palaos',1),(177,'PS',NULL,'territoire Palestinien Occupé',1),(178,'PA',NULL,'Panama',1),(179,'PG',NULL,'Papouasie-Nouvelle-Guinée',1),(180,'PY',NULL,'Paraguay',1),(181,'PE',NULL,'Pérou',1),(182,'PH',NULL,'Philippines',1),(183,'PN',NULL,'Iles Pitcairn',1),(184,'PL',NULL,'Pologne',1),(185,'PR',NULL,'Porto Rico',1),(186,'QA',NULL,'Qatar',1),(187,'RE',NULL,'Réunion',1),(188,'RO',NULL,'Roumanie',1),(189,'RW',NULL,'Rwanda',1),(190,'SH',NULL,'Sainte-Hélène',1),(191,'KN',NULL,'Saint-Christophe-et-Niévès',1),(192,'LC',NULL,'Sainte-Lucie',1),(193,'PM',NULL,'Saint-Pierre-et-Miquelon',1),(194,'VC',NULL,'Saint-Vincent-et-les-Grenadines',1),(195,'WS',NULL,'Samoa',1),(196,'SM',NULL,'Saint-Marin',1),(197,'ST',NULL,'Sao Tomé-et-Principe',1),(198,'RS',NULL,'Serbie',1),(199,'SC',NULL,'Seychelles',1),(200,'SL',NULL,'Sierra Leone',1),(201,'SK',NULL,'Slovaquie',1),(202,'SI',NULL,'Slovénie',1),(203,'SB',NULL,'Iles Salomon',1),(204,'SO',NULL,'Somalie',1),(205,'ZA',NULL,'Afrique du Sud',1),(206,'GS',NULL,'Iles Géorgie du Sud et Sandwich du Sud',1),(207,'LK',NULL,'Sri Lanka',1),(208,'SD',NULL,'Soudan',1),(209,'SR',NULL,'Suriname',1),(210,'SJ',NULL,'Iles Svalbard et Jan Mayen',1),(211,'SZ',NULL,'Swaziland',1),(212,'SY',NULL,'Syrie',1),(213,'TW',NULL,'Taïwan',1),(214,'TJ',NULL,'Tadjikistan',1),(215,'TZ',NULL,'Tanzanie',1),(216,'TH',NULL,'Thaïlande',1),(217,'TL',NULL,'Timor Oriental',1),(218,'TK',NULL,'Tokélaou',1),(219,'TO',NULL,'Tonga',1),(220,'TT',NULL,'Trinité-et-Tobago',1),(221,'TR',NULL,'Turquie',1),(222,'TM',NULL,'Turkménistan',1),(223,'TC',NULL,'Iles Turks-et-Caicos',1),(224,'TV',NULL,'Tuvalu',1),(225,'UG',NULL,'Ouganda',1),(226,'UA',NULL,'Ukraine',1),(227,'AE',NULL,'Émirats arabes unis',1),(228,'UM',NULL,'Iles mineures éloignées des États-Unis',1),(229,'UY',NULL,'Uruguay',1),(230,'UZ',NULL,'Ouzbékistan',1),(231,'VU',NULL,'Vanuatu',1),(232,'VE',NULL,'Vénézuela',1),(233,'VN',NULL,'Viêt Nam',1),(234,'VG',NULL,'Iles Vierges britanniques',1),(235,'VI',NULL,'Iles Vierges américaines',1),(236,'WF',NULL,'Wallis-et-Futuna',1),(237,'EH',NULL,'Sahara occidental',1),(238,'YE',NULL,'Yémen',1),(239,'ZM',NULL,'Zambie',1),(240,'ZW',NULL,'Zimbabwe',1),(241,'GG',NULL,'Guernesey',1),(242,'IM',NULL,'Ile de Man',1),(243,'JE',NULL,'Jersey',1),(244,'ME',NULL,'Monténégro',1),(245,'BL',NULL,'Saint-Barthélemy',1),(246,'MF',NULL,'Saint-Martin',1);
/*!40000 ALTER TABLE `llx_c_pays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_propalst`
--

DROP TABLE IF EXISTS `llx_c_propalst`;
CREATE TABLE `llx_c_propalst` (
  `id` smallint(6) NOT NULL,
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_c_prospectlevel` (
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_c_prospectlevel`
--

LOCK TABLES `llx_c_prospectlevel` WRITE;
/*!40000 ALTER TABLE `llx_c_prospectlevel` DISABLE KEYS */;
INSERT INTO `llx_c_prospectlevel` VALUES ('PL_HIGH','High',4,1),('PL_LOW','Low',2,1),('PL_MEDIUM','Medium',3,1),('PL_NONE','None',1,1);
/*!40000 ALTER TABLE `llx_c_prospectlevel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_regions`
--

DROP TABLE IF EXISTS `llx_c_regions`;
CREATE TABLE `llx_c_regions` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code_region` int(11) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `cheflieu` varchar(7) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code_region` (`code_region`),
  KEY `idx_c_regions_fk_pays` (`fk_pays`),
  CONSTRAINT `fk_c_regions_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=15213;

--
-- Dumping data for table `llx_c_regions`
--

LOCK TABLES `llx_c_regions` WRITE;
/*!40000 ALTER TABLE `llx_c_regions` DISABLE KEYS */;
INSERT INTO `llx_c_regions` VALUES (1,0,0,'0',0,'-',1),(101,1,1,'97105',3,'Guadeloupe',1),(102,2,1,'97209',3,'Martinique',1),(103,3,1,'97302',3,'Guyane',1),(104,4,1,'97411',3,'Réunion',1),(105,11,1,'75056',1,'Île-de-France',1),(106,21,1,'51108',0,'Champagne-Ardenne',1),(107,22,1,'80021',0,'Picardie',1),(108,23,1,'76540',0,'Haute-Normandie',1),(109,24,1,'45234',2,'Centre',1),(110,25,1,'14118',0,'Basse-Normandie',1),(111,26,1,'21231',0,'Bourgogne',1),(112,31,1,'59350',2,'Nord-Pas-de-Calais',1),(113,41,1,'57463',0,'Lorraine',1),(114,42,1,'67482',1,'Alsace',1),(115,43,1,'25056',0,'Franche-Comté',1),(116,52,1,'44109',4,'Pays de la Loire',1),(117,53,1,'35238',0,'Bretagne',1),(118,54,1,'86194',2,'Poitou-Charentes',1),(119,72,1,'33063',1,'Aquitaine',1),(120,73,1,'31555',0,'Midi-Pyrénées',1),(121,74,1,'87085',2,'Limousin',1),(122,82,1,'69123',2,'Rhône-Alpes',1),(123,83,1,'63113',1,'Auvergne',1),(124,91,1,'34172',2,'Languedoc-Roussillon',1),(125,93,1,'13055',0,'Provence-Alpes-Côte d\'Azur',1),(126,94,1,'2A004',0,'Corse',1),(201,201,2,'',1,'Flandre',1),(202,202,2,'',2,'Wallonie',1),(203,203,2,'',3,'Bruxelles-Capitale',1),(401,401,4,'',0,'Andalucia',1),(402,402,4,'',0,'Aragón',1),(403,403,4,'',0,'Castilla y León',1),(404,404,4,'',0,'Castilla la Mancha',1),(405,405,4,'',0,'Canarias',1),(406,406,4,'',0,'Cataluña',1),(407,407,4,'',0,'Comunidad de Ceuta',1),(408,408,4,'',0,'Comunidad Foral de Navarra',1),(409,409,4,'',0,'Comunidad de Melilla',1),(410,410,4,'',0,'Cantabria',1),(411,411,4,'',0,'Comunidad Valenciana',1),(412,412,4,'',0,'Extemadura',1),(413,413,4,'',0,'Galicia',1),(414,414,4,'',0,'Islas Baleares',1),(415,415,4,'',0,'La Rioja',1),(416,416,4,'',0,'Comunidad de Madrid',1),(417,417,4,'',0,'Región de Murcia',1),(418,418,4,'',0,'Principado de Asturias',1),(419,419,4,'',0,'Pais Vasco',1),(420,420,4,'',0,'Otros',1),(601,601,6,'',1,'Cantons',1),(1001,1001,10,'',0,'Ariana',1),(1002,1002,10,'',0,'Béja',1),(1003,1003,10,'',0,'Ben Arous',1),(1004,1004,10,'',0,'Bizerte',1),(1005,1005,10,'',0,'Gabès',1),(1006,1006,10,'',0,'Gafsa',1),(1007,1007,10,'',0,'Jendouba',1),(1008,1008,10,'',0,'Kairouan',1),(1009,1009,10,'',0,'Kasserine',1),(1010,1010,10,'',0,'Kébili',1),(1011,1011,10,'',0,'La Manouba',1),(1012,1012,10,'',0,'Le Kef',1),(1013,1013,10,'',0,'Mahdia',1),(1014,1014,10,'',0,'Médenine',1),(1015,1015,10,'',0,'Monastir',1),(1016,1016,10,'',0,'Nabeul',1),(1017,1017,10,'',0,'Sfax',1),(1018,1018,10,'',0,'Sidi Bouzid',1),(1019,1019,10,'',0,'Siliana',1),(1020,1020,10,'',0,'Sousse',1),(1021,1021,10,'',0,'Tataouine',1),(1022,1022,10,'',0,'Tozeur',1),(1023,1023,10,'',0,'Tunis',1),(1024,1024,10,'',0,'Zaghouan',1),(1101,1101,11,'',0,'United-States',1),(2801,2801,28,'',0,'Australia',1),(8601,8601,86,NULL,NULL,'Central',1),(8602,8602,86,NULL,NULL,'Oriental',1),(8603,8603,86,NULL,NULL,'Occidental',1),(15201,15201,152,'',0,'Rivière Noire',1),(15202,15202,152,'',0,'Flacq',1),(15203,15203,152,'',0,'Grand Port',1),(15204,15204,152,'',0,'Moka',1),(15205,15205,152,'',0,'Pamplemousses',1),(15206,15206,152,'',0,'Plaines Wilhems',1),(15207,15207,152,'',0,'Port-Louis',1),(15208,15208,152,'',0,'Rivière du Rempart',1),(15209,15209,152,'',0,'Savanne',1),(15210,15210,152,'',0,'Rodrigues',1),(15211,15211,152,'',0,'Les îles Agaléga',1),(15212,15212,152,'',0,'Les écueils des Cargados Carajos',1);
/*!40000 ALTER TABLE `llx_c_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_stcomm`
--

DROP TABLE IF EXISTS `llx_c_stcomm`;
CREATE TABLE `llx_c_stcomm` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_c_tva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pays` int(11) NOT NULL,
  `taux` double NOT NULL,
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=2462;

--
-- Dumping data for table `llx_c_tva`
--

LOCK TABLES `llx_c_tva` WRITE;
/*!40000 ALTER TABLE `llx_c_tva` DISABLE KEYS */;
INSERT INTO `llx_c_tva` VALUES (11,1,19.6,0,'VAT standard rate (France hors DOM-TOM)',1),(12,1,8.5,0,'VAT standard rate (DOM sauf Guyane et Saint-Martin)',0),(13,1,8.5,1,'VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0),(14,1,5.5,0,'VAT reduced rate (France hors DOM-TOM)',1),(15,1,0,0,'VAT Rate 0 ou non applicable',1),(16,1,2.1,0,'VAT super-reduced rate',1),(21,2,21,0,'VAT standard rate',1),(22,2,6,0,'VAT reduced rate',1),(23,2,0,0,'VAT Rate 0 ou non applicable',1),(24,2,12,0,'VAT reduced rate',1),(31,3,20,0,'VAT standard rate',1),(32,3,10,0,'VAT reduced rate',1),(33,3,4,0,'VAT super-reduced rate',1),(34,3,0,0,'VAT Rate 0',1),(41,4,16,0,'VAT standard rate',1),(42,4,7,0,'VAT reduced rate',1),(43,4,4,0,'VAT super-reduced rate',1),(44,4,0,0,'VAT Rate 0',1),(51,5,19,0,'VAT standard rate',1),(52,5,7,0,'VAT reduced rate',1),(53,5,0,0,'VAT Rate 0',1),(61,6,7.6,0,'VAT standard rate',1),(62,6,3.6,0,'VAT reduced rate',1),(63,6,2.4,0,'VAT super-reduced rate',1),(64,6,0,0,'VAT Rate 0',1),(71,7,17.5,0,'VAT standard rate',1),(72,7,5,0,'VAT reduced rate',1),(73,7,0,0,'VAT Rate 0',1),(101,10,6,0,'VAT 6%',1),(102,10,12,0,'VAT 12%',1),(103,10,18,0,'VAT 18%',1),(104,10,7.5,0,'VAT 6% Majoré à 25% (7.5%)',1),(105,10,15,0,'VAT 12% Majoré à 25% (15%)',1),(106,10,22.5,0,'VAT 18% Majoré à 25% (22.5%)',1),(107,10,0,0,'VAT Rate 0',1),(121,12,20,0,'VAT standard rate',1),(122,12,14,0,'VAT reduced rate',1),(123,12,10,0,'VAT reduced rate',1),(124,12,7,0,'VAT super-reduced rate',1),(141,14,7,0,'VAT standard rate',1),(142,14,0,0,'VAT Rate 0',1),(143,140,6,0,'VAT reduced rate',1),(144,140,3,0,'VAT super-reduced rate',1),(145,140,0,0,'VAT Rate 0',1),(171,17,19,0,'VAT standard rate',1),(172,17,6,0,'VAT reduced rate',1),(173,17,0,0,'VAT Rate 0',1),(201,20,25,0,'VAT standard rate',1),(202,20,12,0,'VAT reduced rate',1),(203,20,6,0,'VAT super-reduced rate',1),(204,20,0,0,'VAT Rate 0',1),(251,25,20,0,'VAT standard rate',1),(252,25,12,0,'VAT reduced rate',1),(253,25,0,0,'VAT Rate 0',1),(254,25,5,0,'VAT reduced rate',1),(281,28,10,0,'VAT standard rate',1),(282,28,0,0,'VAT Rate 0',1),(411,41,20,0,'VAT standard rate',1),(412,41,10,0,'VAT reduced rate',1),(413,41,0,0,'VAT Rate 0',1),(861,86,13,0,'IVA 13',1),(862,86,0,0,'SIN IVA',1),(1231,123,0,0,'VAT Rate 0',1),(1232,123,5,0,'VAT Rate 5',1),(1521,152,0,0,'VAT Rate 0',1),(1522,152,15,0,'VAT Rate 15',1),(1931,193,0,0,'No VAT in SPM',1),(2461,246,0,0,'VAT Rate 0',1);
/*!40000 ALTER TABLE `llx_c_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_contact`
--

DROP TABLE IF EXISTS `llx_c_type_contact`;
CREATE TABLE `llx_c_type_contact` (
  `rowid` int(11) NOT NULL,
  `element` varchar(30) NOT NULL,
  `source` varchar(8) NOT NULL DEFAULT 'external',
  `code` varchar(16) NOT NULL,
  `libelle` varchar(64) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_c_type_contact_uk` (`element`,`source`,`code`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_c_type_contact`
--

LOCK TABLES `llx_c_type_contact` WRITE;
/*!40000 ALTER TABLE `llx_c_type_contact` DISABLE KEYS */;
INSERT INTO `llx_c_type_contact` VALUES (10,'contrat','internal','SALESREPSIGN','Commercial signataire du contrat',1),(11,'contrat','internal','SALESREPFOLL','Commercial suivi du contrat',1),(20,'contrat','external','BILLING','Contact client facturation contrat',1),(21,'contrat','external','CUSTOMER','Contact client suivi contrat',1),(22,'contrat','external','SALESREPSIGN','Contact client signataire contrat',1),(31,'propal','internal','SALESREPFOLL','Commercial à l\'origine de la propale',1),(40,'propal','external','BILLING','Contact client facturation propale',1),(41,'propal','external','CUSTOMER','Contact client suivi propale',1),(50,'facture','internal','SALESREPFOLL','Responsable suivi du paiement',1),(60,'facture','external','BILLING','Contact client facturation',1),(61,'facture','external','SHIPPING','Contact client livraison',1),(62,'facture','external','SERVICE','Contact client prestation',1),(70,'facture_fourn','internal','SALESREPFOLL','Responsable suivi du paiement',1),(71,'facture_fourn','external','BILLING','Contact fournisseur facturation',1),(72,'facture_fourn','external','SHIPPING','Contact fournisseur livraison',1),(73,'facture_fourn','external','SERVICE','Contact fournisseur prestation',1),(91,'commande','internal','SALESREPFOLL','Responsable suivi de la commande',1),(100,'commande','external','BILLING','Contact client facturation commande',1),(101,'commande','external','CUSTOMER','Contact client suivi commande',1),(102,'commande','external','SHIPPING','Contact client livraison commande',1),(120,'fichinter','internal','INTERREPFOLL','Responsable suivi de l\'intervention',1),(121,'fichinter','internal','INTERVENING','Intervenant',1),(130,'fichinter','external','BILLING','Contact client facturation intervention',1),(131,'fichinter','external','CUSTOMER','Contact client suivi de l\'intervention',1),(140,'order_supplier','internal','SALESREPFOLL','Responsable suivi de la commande',1),(141,'order_supplier','internal','SHIPPING','Responsable réception de la commande',1),(142,'order_supplier','external','BILLING','Contact fournisseur facturation commande',1),(143,'order_supplier','external','CUSTOMER','Contact fournisseur suivi commande',1),(145,'order_supplier','external','SHIPPING','Contact fournisseur livraison commande',1),(160,'project','internal','PROJECTLEADER','Chef de Projet',1),(161,'project','internal','CONTRIBUTOR','Intervenant',1),(170,'project','external','PROJECTLEADER','Chef de Projet',1),(171,'project','external','CONTRIBUTOR','Intervenant',1),(180,'project_task','internal','TASKEXECUTIVE','Responsable',1),(181,'project_task','internal','CONTRIBUTOR','Intervenant',1),(190,'project_task','external','TASKEXECUTIVE','Responsable',1),(191,'project_task','external','CONTRIBUTOR','Intervenant',1);
/*!40000 ALTER TABLE `llx_c_type_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_fees`
--

DROP TABLE IF EXISTS `llx_c_type_fees`;
CREATE TABLE `llx_c_type_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB AUTO_INCREMENT=4;

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
CREATE TABLE `llx_c_typent` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_categorie` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `fk_soc` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_categorie_ref` (`label`,`type`,`entity`),
  KEY `idx_categorie_type` (`type`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_categorie_association` (
  `fk_categorie_mere` int(11) NOT NULL,
  `fk_categorie_fille` int(11) NOT NULL,
  UNIQUE KEY `uk_categorie_association` (`fk_categorie_mere`,`fk_categorie_fille`),
  UNIQUE KEY `uk_categorie_association_fk_categorie_fille` (`fk_categorie_fille`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_fille` FOREIGN KEY (`fk_categorie_fille`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_mere` FOREIGN KEY (`fk_categorie_mere`) REFERENCES `llx_categorie` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_categorie_fournisseur` (
  `fk_categorie` int(11) NOT NULL,
  `fk_societe` int(11) NOT NULL,
  UNIQUE KEY `fk_categorie` (`fk_categorie`,`fk_societe`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_categorie_product` (
  `fk_categorie` int(11) NOT NULL,
  `fk_product` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_product`),
  KEY `idx_categorie_product_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_product_fk_product` (`fk_product`),
  CONSTRAINT `fk_categorie_product_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_product_product_rowid` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_categorie_societe` (
  `fk_categorie` int(11) NOT NULL,
  `fk_societe` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_societe`),
  KEY `idx_categorie_societe_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_societe_fk_societe` (`fk_societe`),
  CONSTRAINT `fk_categorie_societe_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_societe_fk_soc` FOREIGN KEY (`fk_societe`) REFERENCES `llx_societe` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_chargesociales` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_ech` datetime NOT NULL,
  `libelle` varchar(80) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_type` int(11) NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `periode` date DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=5;

--
-- Dumping data for table `llx_chargesociales`
--

LOCK TABLES `llx_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_chargesociales` VALUES (1,'2009-02-02 00:00:00','Impot 2008',1,25,286,0,'2008-12-31'),(2,'2009-02-02 00:00:00','Impot 2008',1,25,286,0,'2008-12-31'),(3,'2009-02-02 00:00:00','Impot 2008',1,25,286,0,'2008-12-31'),(4,'2010-02-08 00:00:00','Paiement impot 2008',1,25,100,0,'2010-02-01');
/*!40000 ALTER TABLE `llx_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande`
--

DROP TABLE IF EXISTS `llx_commande`;
CREATE TABLE `llx_commande` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_client` varchar(30) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `date_commande` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `source` smallint(6) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `amount_ht` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `tva` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `facture` tinyint(4) DEFAULT '0',
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_ref` (`ref`,`entity`),
  KEY `idx_commande_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_commande_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_soc` int(11) NOT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_supplier` varchar(30) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `date_commande` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `source` smallint(6) NOT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `amount_ht` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `tva` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `fk_methode_commande` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_fournisseur_ref` (`ref`,`fk_soc`,`entity`),
  KEY `idx_commande_fournisseur_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fournisseur_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=3;

--
-- Dumping data for table `llx_commande_fournisseur`
--

LOCK TABLES `llx_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur` VALUES (1,'2010-02-09 11:05:29',3,'(PROV1)',1,NULL,0,'2010-02-09 11:05:29',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0),(2,'2010-02-09 11:33:48',3,'CF1002-0001',1,NULL,0,'2010-02-09 11:07:36','2010-02-09 11:33:48',NULL,NULL,1,1,NULL,0,1,0,0,0,0.00000000,40.00000000,40.00000000,NULL,NULL,'muscadet',0);
/*!40000 ALTER TABLE `llx_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_dispatch`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_dispatch`;
CREATE TABLE `llx_commande_fournisseur_dispatch` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `qty` float DEFAULT NULL,
  `fk_entrepot` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commande_fournisseur_dispatch_fk_commande` (`fk_commande`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_commande_fournisseur_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `datelog` datetime NOT NULL,
  `fk_commande` int(11) NOT NULL,
  `fk_statut` smallint(6) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_commande_fournisseur_log`
--

LOCK TABLES `llx_commande_fournisseur_log` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur_log` VALUES (1,'2010-02-09 11:05:29','2010-02-09 11:05:29',1,0,1,NULL),(2,'2010-02-09 11:07:36','2010-02-09 11:07:36',2,0,1,NULL),(3,'2010-02-09 11:33:48','2010-02-09 11:33:48',2,1,1,NULL);
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseurdet`
--

DROP TABLE IF EXISTS `llx_commande_fournisseurdet`;
CREATE TABLE `llx_commande_fournisseurdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_commande_fournisseurdet`
--

LOCK TABLES `llx_commande_fournisseurdet` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseurdet` VALUES (1,2,NULL,'','','Product 1',0.000,1,0,0,10.00000000,10.00000000,0.00000000,10.00000000,0,NULL,NULL,0),(2,2,NULL,'','','Service 1',0.000,1,0,0,15.00000000,15.00000000,0.00000000,15.00000000,1,NULL,NULL,0),(3,2,NULL,'','','Service 1',0.000,1,0,0,15.00000000,15.00000000,0.00000000,15.00000000,1,NULL,NULL,0);
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commandedet`
--

DROP TABLE IF EXISTS `llx_commandedet`;
CREATE TABLE `llx_commandedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `marge_tx` double(6,3) DEFAULT '0.000',
  `marque_tx` double(6,3) DEFAULT '0.000',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commandedet_fk_commande` (`fk_commande`),
  CONSTRAINT `fk_commandedet_fk_commande` FOREIGN KEY (`fk_commande`) REFERENCES `llx_commande` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_compta` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `fk_compta_account` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `valid` tinyint(4) DEFAULT '0',
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_compta_account` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `number` varchar(12) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_compta_account`
--

LOCK TABLES `llx_compta_account` WRITE;
/*!40000 ALTER TABLE `llx_compta_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_compta_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta_compte_generaux`
--

DROP TABLE IF EXISTS `llx_compta_compte_generaux`;
CREATE TABLE `llx_compta_compte_generaux` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_creation` datetime DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `intitule` varchar(255) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `numero` (`numero`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_cond_reglement` (
  `rowid` int(11) NOT NULL,
  `code` varchar(16) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` tinyint(4) DEFAULT '1',
  `libelle` varchar(255) DEFAULT NULL,
  `libelle_facture` text,
  `fdm` tinyint(4) DEFAULT NULL,
  `nbjour` smallint(6) DEFAULT NULL,
  `decalage` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_cond_reglement`
--

LOCK TABLES `llx_cond_reglement` WRITE;
/*!40000 ALTER TABLE `llx_cond_reglement` DISABLE KEYS */;
INSERT INTO `llx_cond_reglement` VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL);
/*!40000 ALTER TABLE `llx_cond_reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_const`
--

DROP TABLE IF EXISTS `llx_const`;
CREATE TABLE `llx_const` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `value` text NOT NULL,
  `type` varchar(6) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `note` text,
  `tms` timestamp NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_const` (`name`,`entity`)
) TYPE=InnoDB AUTO_INCREMENT=1109;

--
-- Dumping data for table `llx_const`
--

LOCK TABLES `llx_const` WRITE;
/*!40000 ALTER TABLE `llx_const` DISABLE KEYS */;
INSERT INTO `llx_const` VALUES (3,'MAIN_POPUP_CALENDAR',0,'eldy','chaine',0,'Popup calendar module','2010-02-04 22:05:44'),(4,'SYSLOG_FILE',0,'DOL_DATA_ROOT/dolibarr.log','chaine',0,'Directory where to write log file','2010-02-04 22:05:45'),(5,'SYSLOG_LEVEL',0,'7','chaine',0,'Level of debug info to show','2010-02-04 22:05:45'),(8,'MAIN_UPLOAD_DOC',0,'2048','chaine',0,'Max size for file upload (0 means no upload allowed)','2010-02-04 22:05:46'),(9,'MAIN_SEARCHFORM_SOCIETE',0,'1','yesno',0,'Show form for quick company search','2010-02-04 22:05:46'),(10,'MAIN_SEARCHFORM_CONTACT',0,'1','yesno',0,'Show form for quick contact search','2010-02-04 22:05:46'),(11,'MAIN_SEARCHFORM_PRODUITSERVICE',0,'1','yesno',0,'Show form for quick product search','2010-02-04 22:05:46'),(12,'MAIN_SEARCHFORM_ADHERENT',0,'1','yesno',0,'Show form for quick member search','2010-02-04 22:05:46'),(13,'MAIN_CONFIRM_AJAX',0,'1','chaine',0,'Use Ajax popup to make confirmations','2010-02-04 22:05:46'),(16,'MAIN_SIZE_LISTE_LIMIT',0,'25','chaine',0,'Longueur maximum des listes','2010-02-04 22:05:46'),(17,'MAIN_SHOW_WORKBOARD',0,'1','yesno',0,'Affichage tableau de bord de travail Dolibarr','2010-02-04 22:05:46'),(18,'MAIN_MENU_BARRETOP',1,'eldy_backoffice.php','chaine',0,'Module de gestion de la barre de menu du haut pour utilisateurs internes','2010-02-04 22:05:46'),(19,'MAIN_MENUFRONT_BARRETOP',1,'eldy_frontoffice.php','chaine',0,'Module de gestion de la barre de menu du haut pour utilisateurs externes','2010-02-04 22:05:46'),(20,'MAIN_MENU_BARRELEFT',1,'eldy_backoffice.php','chaine',0,'Module de gestion de la barre de menu gauche pour utilisateurs internes','2010-02-04 22:05:46'),(21,'MAIN_MENUFRONT_BARRELEFT',1,'eldy_frontoffice.php','chaine',0,'Module de gestion de la barre de menu gauche pour utilisateurs externes','2010-02-04 22:05:46'),(23,'MAIN_DELAY_ACTIONS_TODO',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées','2010-02-04 22:05:47'),(24,'MAIN_DELAY_ORDERS_TO_PROCESS',1,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes non traitées','2010-02-04 22:05:47'),(25,'MAIN_DELAY_PROPALS_TO_CLOSE',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales à cloturer','2010-02-04 22:05:47'),(26,'MAIN_DELAY_PROPALS_TO_BILL',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales non facturées','2010-02-04 22:05:47'),(27,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',1,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées','2010-02-04 22:05:47'),(28,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures client impayées','2010-02-04 22:05:47'),(29,'MAIN_DELAY_NOT_ACTIVATED_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2010-02-04 22:05:47'),(30,'MAIN_DELAY_RUNNING_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services expirés','2010-02-04 22:05:47'),(31,'MAIN_DELAY_MEMBERS',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard','2010-02-04 22:05:47'),(32,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',1,'62','chaine',0,'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire','2010-02-04 22:05:47'),(33,'SOCIETE_NOLIST_COURRIER',0,'1','yesno',0,'Liste les fichiers du repertoire courrier','2010-02-04 22:05:47'),(34,'SOCIETE_CODECLIENT_ADDON',1,'mod_codeclient_leopard','yesno',0,'Module to control third parties codes','2010-02-04 22:05:47'),(35,'SOCIETE_CODECOMPTA_ADDON',1,'mod_codecompta_panicum','yesno',0,'Module to control third parties codes','2010-02-04 22:05:47'),(37,'ADHERENT_MAIL_FROM',1,'adherents@domain.com','chaine',0,'From des mails adherents','2010-02-04 22:05:47'),(38,'ADHERENT_MAIL_RESIL',1,'Votre adhesion vient d\'etre resiliee.\r\nNous esperons vous revoir tres bientot','texte',0,'Mail de Resiliation','2010-02-04 22:05:47'),(39,'ADHERENT_MAIL_VALID',1,'Votre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFOS%\r\n\r\n','texte',0,'Mail de validation','2010-02-04 22:05:48'),(40,'ADHERENT_MAIL_COTIS',1,'Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\n','texte',0,'Mail de validation de cotisation','2010-02-04 22:05:48'),(41,'ADHERENT_MAIL_VALID_SUBJECT',1,'Votre adhésion a ete validée','chaine',0,'Sujet du mail de validation','2010-02-04 22:05:48'),(42,'ADHERENT_MAIL_RESIL_SUBJECT',1,'Resiliation de votre adhesion','chaine',0,'Sujet du mail de resiliation','2010-02-04 22:05:48'),(43,'ADHERENT_MAIL_COTIS_SUBJECT',1,'Recu de votre cotisation','chaine',0,'Sujet du mail de validation de cotisation','2010-02-04 22:05:48'),(44,'MAILING_EMAIL_FROM',1,'dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2010-02-04 22:05:48'),(51,'ADHERENT_MAILMAN_LISTS_COTISANT',1,'','chaine',0,'Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement','2010-02-04 22:05:49'),(53,'ADHERENT_USE_SPIP_AUTO',1,'0','yesno',0,'Utilisation de SPIP automatiquement','2010-02-04 22:05:50'),(54,'ADHERENT_SPIP_USER',1,'user','chaine',0,'user spip','2010-02-04 22:05:50'),(55,'ADHERENT_SPIP_PASS',1,'pass','chaine',0,'Pass de connection','2010-02-04 22:05:50'),(56,'ADHERENT_SPIP_SERVEUR',1,'localhost','chaine',0,'serveur spip','2010-02-04 22:05:50'),(57,'ADHERENT_SPIP_DB',1,'spip','chaine',0,'db spip','2010-02-04 22:05:50'),(61,'FCKEDITOR_ENABLE_USER',1,'1','yesno',0,'Activation fckeditor sur notes utilisateurs','2010-02-04 22:05:50'),(62,'FCKEDITOR_ENABLE_SOCIETE',1,'1','yesno',0,'Activation fckeditor sur notes societe','2010-02-04 22:05:50'),(63,'FCKEDITOR_ENABLE_PRODUCTDESC',1,'1','yesno',0,'Activation fckeditor sur notes produits','2010-02-04 22:05:50'),(64,'FCKEDITOR_ENABLE_MEMBER',1,'1','yesno',0,'Activation fckeditor sur notes adherent','2010-02-04 22:05:50'),(65,'FCKEDITOR_ENABLE_MAILING',1,'1','yesno',0,'Activation fckeditor sur emailing','2010-02-04 22:05:51'),(66,'OSC_DB_HOST',1,'localhost','chaine',0,'Host for OSC database for OSCommerce module 1','2010-02-04 22:05:51'),(67,'DON_ADDON_MODEL',1,'html_cerfafr','chaine',0,'','2010-02-04 22:05:51'),(68,'PROPALE_ADDON',1,'mod_propale_marbre','chaine',0,'','2010-02-04 22:05:51'),(69,'PROPALE_ADDON_PDF',1,'azur','chaine',0,'','2010-02-04 22:05:51'),(70,'COMMANDE_ADDON',1,'mod_commande_marbre','chaine',0,'','2010-02-04 22:05:51'),(71,'COMMANDE_ADDON_PDF',1,'einstein','chaine',0,'','2010-02-04 22:05:51'),(72,'COMMANDE_SUPPLIER_ADDON',1,'mod_commande_fournisseur_muguet','chaine',0,'','2010-02-04 22:05:51'),(73,'COMMANDE_SUPPLIER_ADDON_PDF',1,'muscadet','chaine',0,'','2010-02-04 22:05:51'),(74,'EXPEDITION_ADDON',1,'enlevement','chaine',0,'','2010-02-04 22:05:51'),(75,'EXPEDITION_ADDON_PDF',1,'rouget','chaine',0,'','2010-02-04 22:05:51'),(76,'FICHEINTER_ADDON',1,'pacific','chaine',0,'','2010-02-04 22:05:51'),(77,'FICHEINTER_ADDON_PDF',1,'soleil','chaine',0,'','2010-02-04 22:05:51'),(79,'FACTURE_ADDON_PDF',1,'crabe','chaine',0,'','2010-02-04 22:05:51'),(80,'PROPALE_VALIDITY_DURATION',1,'15','chaine',0,'Durée de validitée des propales','2010-02-04 22:05:52'),(81,'GENBARCODE_LOCATION',0,'/usr/local/bin/genbarcode','chaine',0,'location of genbarcode','2010-02-04 22:05:52'),(82,'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE',1,'1','chaine',0,'','2010-02-04 22:05:52'),(83,'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE',1,'1','chaine',0,'','2010-02-04 22:05:52'),(84,'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE',1,'1','chaine',0,'','2010-02-04 22:05:52'),(85,'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL',1,'1','chaine',0,'','2010-02-04 22:05:53'),(86,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE',1,'1','chaine',0,'','2010-02-04 22:05:53'),(87,'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL',1,'1','chaine',0,'','2010-02-04 22:05:53'),(88,'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE',1,'1','chaine',0,'','2010-02-04 22:05:53'),(89,'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED',1,'1','chaine',0,'','2010-02-04 22:05:54'),(90,'MAIN_AGENDA_ACTIONAUTO_BILL_CANCELED',1,'1','chaine',0,'','2010-02-04 22:05:54'),(91,'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL',1,'1','chaine',0,'','2010-02-04 22:05:54'),(92,'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-02-04 22:05:54'),(93,'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-02-04 22:05:54'),(379,'FAC_FORCE_DATE_VALIDATION',1,'0','yesno',0,NULL,'2010-02-04 23:02:31'),(386,'MAIN_MODULE_CONTRAT',1,'1',NULL,0,NULL,'2010-02-04 23:02:42'),(388,'MAIN_MODULE_FICHEINTER',1,'1',NULL,0,NULL,'2010-02-04 23:02:44'),(390,'MAIN_LOGTOHTML',1,'1','',1,'','2010-02-06 21:30:44'),(396,'MAIN_MODULE_PROJET',1,'1',NULL,0,NULL,'2010-02-06 23:25:49'),(397,'PROJECT_ADDON_PDF',1,'baleine','chaine',0,'Nom du gestionnaire de generation des projets en PDF','2010-02-06 23:25:49'),(399,'PROJECT_ADDON',1,'mod_project_simple','chaine',0,'','2010-02-07 00:16:37'),(407,'MAIN_MODULE_ADHERENT',1,'1',NULL,0,NULL,'2010-02-07 02:44:42'),(408,'ADHERENT_BANK_USE_AUTO',1,'','yesno',0,'Insertion automatique des cotisation dans le compte banquaire','2010-02-07 02:44:42'),(409,'ADHERENT_BANK_ACCOUNT',1,'','chaine',0,'ID du Compte banquaire utilise','2010-02-07 02:44:42'),(410,'ADHERENT_BANK_CATEGORIE',1,'','chaine',0,'ID de la categorie banquaire des cotisations','2010-02-07 02:44:42'),(435,'ADHERENT_CARD_FOOTER_TEXT',1,'Association Dolibarr','chaine',0,'Texte imprime sur le bas de la carte adherent','2010-02-07 17:35:06'),(451,'ADHERENT_CARD_HEADER_TEXT',1,'%PRENOM% %NOM%','chaine',0,'','2010-02-07 17:42:25'),(453,'ADHERENT_CARD_TEXT',1,'\r\n\r\n%TYPE%\r\nEMail: %EMAIL%\r\n%ADRESSE%\r\n%CP% %VILLE%','texte',0,'Texte imprime sur la carte adherent','2010-02-07 17:43:04'),(466,'MAIN_MAX_DECIMALS_UNIT',1,'5','chaine',0,'','2010-02-08 02:53:45'),(467,'MAIN_MAX_DECIMALS_TOT',1,'2','chaine',0,'','2010-02-08 02:53:46'),(468,'MAIN_MAX_DECIMALS_SHOWN',1,'8','chaine',0,'','2010-02-08 02:53:46'),(478,'MAIN_FEATURES_LEVEL',0,'1','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development','2010-02-08 03:26:55'),(500,'MAIN_MODULE_FOURNISSEUR',1,'1',NULL,0,NULL,'2010-02-08 03:32:57'),(536,'MAIN_MODULE_PROPALE',1,'1',NULL,0,NULL,'2010-02-08 12:35:38'),(540,'MAIN_MODULE_TAX',1,'1',NULL,0,NULL,'2010-02-08 12:35:47'),(541,'MAIN_MODULE_DEPLACEMENT',1,'1',NULL,0,NULL,'2010-02-08 12:35:50'),(549,'MAIN_MODULE_MAILING',1,'1',NULL,0,NULL,'2010-02-08 12:36:04'),(550,'MAIN_MODULE_EXPORT',1,'1',NULL,0,NULL,'2010-02-08 12:36:06'),(551,'MAIN_MODULE_IMPORT',1,'1',NULL,0,NULL,'2010-02-08 12:36:09'),(553,'MAIN_MODULE_CATEGORIE',1,'1',NULL,0,NULL,'2010-02-08 12:36:32'),(554,'MAIN_MODULE_FCKEDITOR',1,'1',NULL,0,NULL,'2010-02-08 12:36:34'),(555,'MAIN_MODULE_BOOKMARK',1,'1',NULL,0,NULL,'2010-02-08 12:36:37'),(558,'MAIN_MODULE_WEBSERVICES',1,'1',NULL,0,NULL,'2010-02-08 12:36:49'),(559,'MAIN_MODULE_GRAVATAR',1,'1',NULL,0,NULL,'2010-02-08 12:36:52'),(560,'MAIN_MODULE_GEOIPMAXMIND',1,'1',NULL,0,NULL,'2010-02-08 12:36:55'),(584,'MAIN_INFO_SOCIETE_NOM',1,'Ma société','chaine',0,'','2010-02-08 12:52:53'),(585,'MAIN_INFO_SOCIETE_ADRESSE',1,'11 rue de paris','chaine',0,'','2010-02-08 12:52:53'),(586,'MAIN_INFO_SOCIETE_VILLE',1,'Paris','chaine',0,'','2010-02-08 12:52:53'),(587,'MAIN_INFO_SOCIETE_CP',1,'75000','chaine',0,'','2010-02-08 12:52:53'),(588,'MAIN_INFO_SOCIETE_PAYS',1,'1','chaine',0,'','2010-02-08 12:52:53'),(589,'MAIN_MONNAIE',1,'EUR','chaine',0,'','2010-02-08 12:52:53'),(590,'MAIN_INFO_SOCIETE_TEL',1,'0899999999','chaine',0,'','2010-02-08 12:52:53'),(591,'MAIN_INFO_SOCIETE_MAIL',1,'monmail@nomail.com','chaine',0,'','2010-02-08 12:52:53'),(592,'MAIN_INFO_SOCIETE_LOGO',1,'logo_nltechno.png','chaine',0,'','2010-02-08 12:52:53'),(593,'MAIN_INFO_SOCIETE_LOGO_SMALL',1,'logo_nltechno_small.png','chaine',0,'','2010-02-08 12:52:54'),(594,'MAIN_INFO_SOCIETE_LOGO_MINI',1,'logo_nltechno_mini.png','chaine',0,'','2010-02-08 12:52:54'),(595,'MAIN_INFO_CAPITAL',1,'10000','chaine',0,'','2010-02-08 12:52:54'),(596,'MAIN_INFO_SOCIETE_FORME_JURIDIQUE',1,'54','chaine',0,'','2010-02-08 12:52:54'),(597,'MAIN_INFO_SIREN',1,'123456789','chaine',0,'','2010-02-08 12:52:54'),(598,'MAIN_INFO_SIRET',1,'1234567890123','chaine',0,'','2010-02-08 12:52:54'),(599,'MAIN_INFO_APE',1,'ABC','chaine',0,'','2010-02-08 12:52:54'),(600,'MAIN_INFO_TVAINTRA',1,'FR123456789','chaine',0,'','2010-02-08 12:52:54'),(601,'SOCIETE_FISCAL_MONTH_START',1,'0','chaine',0,'','2010-02-08 12:52:54'),(602,'FACTURE_TVAOPTION',1,'reel','chaine',0,'','2010-02-08 12:52:54'),(605,'MAIN_MODULE_DON',1,'1',NULL,0,NULL,'2010-02-08 14:45:51'),(606,'DON_FORM',1,'fsfe.fr.php','chaine',0,'Nom du gestionnaire de formulaire de dons','2010-02-08 14:45:51'),(896,'MAIN_MODULE_USER',0,'1',NULL,0,NULL,'2010-02-11 08:32:12'),(897,'MAIN_VERSION_LAST_INSTALL',0,'2.8.0-dev','chaine',0,'Dolibarr version when install','2010-02-11 08:32:12'),(899,'ADHERENT_MAIL_REQUIRED',1,'0','',0,'','2010-02-11 19:02:55'),(914,'ADHERENT_ETIQUETTE_TYPE',1,'CARD','texte',0,'Type d etiquette (pour impression de planche d etiquette)','2010-02-11 21:24:26'),(924,'ADHERENT_CARD_TYPE',1,'AVERYC32010','texte',0,'','2010-02-11 22:44:12'),(929,'FACTURE_MERCURE_MASK_INVOICE',1,'F{tttt}-{00000+269}','chaine',0,'','2010-02-13 00:21:53'),(930,'FACTURE_MERCURE_MASK_CREDIT',1,'F{tttt}-{00000+269}','chaine',0,'','2010-02-13 00:21:53'),(931,'FACTURE_ADDON',1,'mercure','chaine',0,'','2010-02-13 00:24:32'),(935,'ADHERENT_CARD_TEXT_RIGHT',1,'%PHOTO%','texte',0,'','2010-02-13 20:57:03'),(937,'ADHERENT_USE_MAILMAN',1,'1','',0,'','2010-02-13 22:45:06'),(940,'ADHERENT_CARD_BACKGROUND',1,'logo_parinux_small.jpg','',1,'','2010-02-13 23:06:46'),(941,'MAIN_MODULE_AGENDA',1,'1',NULL,0,NULL,'2010-02-13 23:34:54'),(943,'MAIN_MODULE_SERVICE',1,'1',NULL,0,NULL,'2010-02-13 23:34:54'),(944,'MAIN_VERSION_LAST_UPGRADE',0,'2.8.0-beta','chaine',0,'Dolibarr version for last upgrade','2010-02-13 23:34:58'),(947,'ADHERENT_MAILMAN_ADMINPW',1,'d0libarr-an','chaine',0,'Mot de passe Admin des liste mailman','2010-02-14 00:33:59'),(948,'ADHERENT_MAILMAN_SERVER',1,'lists.nongnu.org','chaine',0,'Serveur hebergeant les interfaces d Admin des listes mailman','2010-02-14 00:34:21'),(949,'ADHERENT_MAILMAN_LISTS',1,'dolibarr-association','chaine',0,'Listes auxquelles inscrire les nouveaux adherents','2010-02-14 00:37:13'),(954,'ADHERENT_MAILMAN_URL',1,'http://lists.nongnu.org/mailman/listinfo/%LISTE%?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine',0,'Url pour les inscriptions mailman','2010-02-14 00:45:01'),(958,'ADHERENT_MAILMAN_UNSUB_URL',1,'http://lists.nongnu.org/mailman/subscribe/%LISTE%/%EMAIL%?adminpw=%MAILMAN_ADMINPW%&email=%EMAIL%','chaine',0,'Url de desinscription aux listes mailman','2010-02-14 00:50:48'),(960,'MAIN_REMOVE_INSTALL_WARNING',1,'1','',1,'','2010-02-14 13:41:33'),(961,'MAIN_DEMO',1,'demo,demo','',1,'','2010-02-14 13:41:45'),(963,'MAIN_APPLICATION_TITLE',1,'DEMO DOLIBARR ERP/CRM','',1,'','2010-02-14 13:43:26'),(964,'MAIN_HTML_HEADER',1,'<script type=\"text/javascript\"> var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\"); document.write(unescape(\"%3Cscript src=\'\" + gaJsHost + \"google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E\")); </script> <script type=\"text/javascript\"> try { var pageTracker = _gat._getTracker(\"UA-9049390-3\"); pageTracker._trackPageview(); } catch(err) {}</script>',NULL,1,NULL,'2010-02-14 13:44:45'),(967,'LIVRAISON_ADDON_PDF',1,'typhon','chaine',0,'Nom du gestionnaire de generation des commandes en PDF','2010-02-14 13:49:31'),(968,'LIVRAISON_ADDON',1,'mod_livraison_jade','chaine',0,'Nom du gestionnaire de numerotation des bons de livraison','2010-02-14 13:49:31'),(971,'MAIN_MODULE_ECM',1,'1',NULL,0,NULL,'2010-02-14 13:49:38'),(972,'MAIN_MODULE_COMPTABILITE',1,'1',NULL,0,NULL,'2010-02-14 13:49:40'),(973,'MAIN_MODULE_FACTURE',1,'1',NULL,0,NULL,'2010-02-14 13:49:41'),(975,'MAIN_MODULE_BANQUE',1,'1',NULL,0,NULL,'2010-02-14 13:49:41'),(977,'MAIN_MODULE_STOCK',1,'1',NULL,0,NULL,'2010-02-14 13:50:14'),(978,'MAIN_MODULE_PRODUIT',1,'1',NULL,0,NULL,'2010-02-14 13:50:14'),(979,'MAIN_MODULE_EXPEDITION',1,'1',NULL,0,NULL,'2010-02-14 13:53:39'),(980,'MAIN_MODULE_COMMANDE',1,'1',NULL,0,NULL,'2010-02-14 13:53:39'),(981,'MAIN_MODULE_SOCIETE',1,'1',NULL,0,NULL,'2010-02-14 13:53:39'),(982,'MAIN_SUBMODULE_EXPEDITION',1,'1','chaine',0,'','2010-02-14 13:53:50'),(985,'MAIN_DISABLE_ALL_MAILS',1,'1','chaine',0,'','2010-02-14 14:34:00'),(986,'MAIN_MAIL_SENDMODE',0,'mail','chaine',0,'','2010-02-14 14:34:00'),(987,'MAIN_MAIL_EMAIL_FROM',1,'dolibarr-robot@domain.com','chaine',0,'','2010-02-14 14:34:01'),(988,'MAIN_LOGEVENTS_USER_LOGIN',1,'1','chaine',0,'','2010-02-14 14:34:35'),(989,'MAIN_LOGEVENTS_USER_LOGIN_FAILED',1,'1','chaine',0,'','2010-02-14 14:34:35'),(990,'MAIN_LOGEVENTS_USER_CREATE',1,'1','chaine',0,'','2010-02-14 14:34:35'),(991,'MAIN_LOGEVENTS_USER_MODIFY',1,'1','chaine',0,'','2010-02-14 14:34:35'),(992,'MAIN_LOGEVENTS_USER_NEW_PASSWORD',1,'1','chaine',0,'','2010-02-14 14:34:35'),(993,'MAIN_LOGEVENTS_USER_ENABLEDISABLE',1,'1','chaine',0,'','2010-02-14 14:34:35'),(994,'MAIN_LOGEVENTS_USER_DELETE',1,'1','chaine',0,'','2010-02-14 14:34:35'),(995,'MAIN_LOGEVENTS_GROUP_CREATE',1,'1','chaine',0,'','2010-02-14 14:34:35'),(996,'MAIN_LOGEVENTS_GROUP_MODIFY',1,'1','chaine',0,'','2010-02-14 14:34:35'),(997,'MAIN_LOGEVENTS_GROUP_DELETE',1,'1','chaine',0,'','2010-02-14 14:34:35'),(1092,'MAIN_LANG_DEFAULT',1,'auto','chaine',0,'','2010-02-14 14:44:30'),(1093,'MAIN_MULTILANGS',1,'0','chaine',0,'','2010-02-14 14:44:31'),(1094,'MAIN_SIZE_LISTE_LIMIT',1,'25','chaine',0,'','2010-02-14 14:44:31'),(1095,'MAIN_DISABLE_JAVASCRIPT',1,'0','chaine',0,'','2010-02-14 14:44:31'),(1096,'MAIN_CONFIRM_AJAX',1,'1','chaine',0,'','2010-02-14 14:44:31'),(1097,'MAIN_POPUP_CALENDAR',1,'eldy','chaine',0,'','2010-02-14 14:44:31'),(1098,'MAIN_START_WEEK',1,'1','chaine',0,'','2010-02-14 14:44:31'),(1099,'MAIN_SHOW_LOGO',1,'0','chaine',0,'','2010-02-14 14:44:31'),(1100,'MAIN_THEME',1,'eldy','chaine',0,'','2010-02-14 14:44:31'),(1101,'MAIN_SEARCHFORM_CONTACT',1,'1','chaine',0,'','2010-02-14 14:44:31'),(1102,'MAIN_SEARCHFORM_SOCIETE',1,'1','chaine',0,'','2010-02-14 14:44:31'),(1103,'MAIN_SEARCHFORM_PRODUITSERVICE',1,'1','chaine',0,'','2010-02-14 14:44:31'),(1104,'MAIN_SEARCHFORM_ADHERENT',1,'1','chaine',0,'','2010-02-14 14:44:31'),(1105,'MAIN_HELPCENTER_DISABLELINK',0,'1','chaine',0,'','2010-02-14 14:44:31'),(1106,'MAIN_HOME',1,'<div style=\"text-align: center;\">__(Login)__: demo<br /> __(Password)__: demo<br /><br />__(NoteSomeFeaturesAreDisabled)__</div>','chaine',0,'','2010-02-14 14:44:31'),(1107,'MAIN_HELP_DISABLELINK',0,'0','chaine',0,'','2010-02-14 14:44:32'),(1108,'SYSTEMTOOLS_MYSQLDUMP',1,'C:\\Program Files (x86)\\wamp\\bin\\mysql\\mysql5.1.36\\bin/mysqldump','chaine',0,'','2010-02-14 14:45:08');
/*!40000 ALTER TABLE `llx_const` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contrat`
--

DROP TABLE IF EXISTS `llx_contrat`;
CREATE TABLE `llx_contrat` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `date_contrat` datetime DEFAULT NULL,
  `statut` smallint(6) DEFAULT '0',
  `mise_en_service` datetime DEFAULT NULL,
  `fin_validite` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_commercial_signature` int(11) NOT NULL,
  `fk_commercial_suivi` int(11) NOT NULL,
  `fk_user_author` int(11) NOT NULL DEFAULT '0',
  `fk_user_mise_en_service` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_contrat_ref` (`ref`,`entity`),
  KEY `idx_contrat_fk_soc` (`fk_soc`),
  KEY `idx_contrat_fk_user_author` (`fk_user_author`),
  CONSTRAINT `fk_contrat_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_contrat_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_contratdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_contrat` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `statut` smallint(6) DEFAULT '0',
  `label` text,
  `description` text,
  `fk_remise_except` int(11) DEFAULT NULL,
  `date_commande` datetime DEFAULT NULL,
  `date_ouverture_prevue` datetime DEFAULT NULL,
  `date_ouverture` datetime DEFAULT NULL,
  `date_fin_validite` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `qty` double NOT NULL,
  `remise_percent` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT '0.00000000',
  `price_ht` double DEFAULT NULL,
  `remise` double DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `info_bits` int(11) DEFAULT '0',
  `fk_user_author` int(11) NOT NULL DEFAULT '0',
  `fk_user_ouverture` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `commentaire` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_contratdet_fk_contrat` (`fk_contrat`),
  KEY `idx_contratdet_fk_product` (`fk_product`),
  KEY `idx_contratdet_date_ouverture_prevue` (`date_ouverture_prevue`),
  KEY `idx_contratdet_date_ouverture` (`date_ouverture`),
  KEY `idx_contratdet_date_fin_validite` (`date_fin_validite`),
  CONSTRAINT `fk_contratdet_fk_contrat` FOREIGN KEY (`fk_contrat`) REFERENCES `llx_contrat` (`rowid`),
  CONSTRAINT `fk_contratdet_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_contratdet_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_contratdet` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `statut` smallint(6) NOT NULL,
  `fk_user_author` int(11) NOT NULL,
  `commentaire` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_contratdet_log_fk_contratdet` (`fk_contratdet`),
  KEY `idx_contratdet_log_date` (`date`),
  CONSTRAINT `fk_contratdet_log_fk_contratdet` FOREIGN KEY (`fk_contratdet`) REFERENCES `llx_contratdet` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_cotisation` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `fk_adherent` int(11) DEFAULT NULL,
  `dateadh` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `cotisation` double DEFAULT NULL,
  `fk_bank` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_cotisation` (`fk_adherent`,`dateadh`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_deplacement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime NOT NULL,
  `tms` timestamp NOT NULL,
  `dated` datetime DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `type` varchar(12) NOT NULL,
  `km` double DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_document` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_extension` varchar(5) NOT NULL,
  `date_generation` datetime DEFAULT NULL,
  `fk_owner` int(11) DEFAULT NULL,
  `fk_group` int(11) DEFAULT NULL,
  `permissions` char(9) DEFAULT 'rw-rw-rw',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_document_generator` (
  `rowid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `classfile` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_document_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `type` varchar(20) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_document_model` (`nom`,`type`,`entity`)
) TYPE=InnoDB AUTO_INCREMENT=15;

--
-- Dumping data for table `llx_document_model`
--

LOCK TABLES `llx_document_model` WRITE;
/*!40000 ALTER TABLE `llx_document_model` DISABLE KEYS */;
INSERT INTO `llx_document_model` VALUES (3,'soleil',1,'ficheinter',NULL,NULL),(5,'azur',1,'propal',NULL,NULL),(9,'muscadet',1,'supplier_order',NULL,NULL),(11,'crabe',1,'invoice',NULL,NULL),(12,'html_cerfafr',1,'donation',NULL,NULL),(13,'einstein',1,'order',NULL,NULL),(14,'rouget',1,'shipping',NULL,NULL);
/*!40000 ALTER TABLE `llx_document_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_dolibarr_modules`
--

DROP TABLE IF EXISTS `llx_dolibarr_modules`;
CREATE TABLE `llx_dolibarr_modules` (
  `numero` int(11) NOT NULL DEFAULT '0',
  `entity` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `active_date` datetime NOT NULL,
  `active_version` varchar(25) NOT NULL,
  PRIMARY KEY (`numero`,`entity`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_dolibarr_modules`
--

LOCK TABLES `llx_dolibarr_modules` WRITE;
/*!40000 ALTER TABLE `llx_dolibarr_modules` DISABLE KEYS */;
INSERT INTO `llx_dolibarr_modules` VALUES (0,1,1,'2010-02-11 08:32:12','dolibarr'),(1,1,1,'2010-02-14 13:53:39','dolibarr'),(10,1,1,'2010-02-14 13:49:40','dolibarr'),(20,1,1,'2010-02-08 12:35:38','dolibarr'),(22,1,1,'2010-02-08 12:36:04','dolibarr'),(25,1,1,'2010-02-14 13:53:39','dolibarr'),(30,1,1,'2010-02-14 13:49:41','dolibarr'),(40,1,1,'2010-02-08 03:32:57','dolibarr'),(50,1,1,'2010-02-14 13:50:14','dolibarr'),(52,1,1,'2010-02-14 13:50:14','dolibarr'),(53,1,1,'2010-02-13 23:34:54','dolibarr'),(54,1,1,'2010-02-04 23:02:42','dolibarr'),(70,1,1,'2010-02-04 23:02:44','dolibarr'),(75,1,1,'2010-02-08 12:35:50','dolibarr'),(80,1,1,'2010-02-14 13:53:39','dolibarr'),(85,1,1,'2010-02-14 13:49:41','dolibarr'),(240,1,1,'2010-02-08 12:36:06','dolibarr'),(250,1,1,'2010-02-08 12:36:09','dolibarr'),(310,1,1,'2010-02-07 02:44:42','dolibarr'),(330,1,1,'2010-02-08 12:36:37','dolibarr'),(400,1,1,'2010-02-06 23:25:49','dolibarr'),(500,1,1,'2010-02-08 12:35:47','dolibarr'),(700,1,1,'2010-02-08 14:45:51','dolibarr'),(1780,1,1,'2010-02-08 12:36:32','dolibarr'),(2000,1,1,'2010-02-08 12:36:34','dolibarr'),(2400,1,1,'2010-02-13 23:34:54','dolibarr'),(2500,1,1,'2010-02-14 13:49:38','dolibarr'),(2600,1,1,'2010-02-08 12:36:49','dolibarr'),(2700,1,1,'2010-02-08 12:36:52','dolibarr'),(2900,1,1,'2010-02-08 12:36:55','dolibarr');
/*!40000 ALTER TABLE `llx_dolibarr_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_domain`
--

DROP TABLE IF EXISTS `llx_domain`;
CREATE TABLE `llx_domain` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_don` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `datec` datetime DEFAULT NULL,
  `datedon` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_paiement` int(11) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `adresse` text,
  `cp` varchar(30) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `pays` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `public` smallint(6) NOT NULL DEFAULT '1',
  `fk_don_projet` int(11) DEFAULT NULL,
  `fk_user_author` int(11) NOT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=2;

--
-- Dumping data for table `llx_don`
--

LOCK TABLES `llx_don` WRITE;
/*!40000 ALTER TABLE `llx_don` DISABLE KEYS */;
INSERT INTO `llx_don` VALUES (1,NULL,1,'2010-02-08 14:50:01',2,'2010-02-08 14:48:07','2010-02-08 12:00:00',100,1,'','','','','','','','',1,1,1,1,'',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_don` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_droitpret_rapport`
--

DROP TABLE IF EXISTS `llx_droitpret_rapport`;
CREATE TABLE `llx_droitpret_rapport` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_envoie` datetime NOT NULL,
  `format` varchar(10) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `fichier` varchar(255) NOT NULL,
  `nbfact` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_ecm_directories` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_parent` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `cachenbofdoc` int(11) NOT NULL DEFAULT '0',
  `date_c` datetime DEFAULT NULL,
  `date_m` timestamp NOT NULL,
  `fk_user_c` int(11) DEFAULT NULL,
  `fk_user_m` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_ecm_directories`
--

LOCK TABLES `llx_ecm_directories` WRITE;
/*!40000 ALTER TABLE `llx_ecm_directories` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_ecm_directories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_documents`
--

DROP TABLE IF EXISTS `llx_ecm_documents`;
CREATE TABLE `llx_ecm_documents` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `filename` varchar(255) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filemime` varchar(32) NOT NULL,
  `fullpath_dol` varchar(255) NOT NULL,
  `fullpath_orig` varchar(255) NOT NULL,
  `description` text,
  `manualkeyword` text,
  `fk_create` int(11) NOT NULL,
  `fk_update` int(11) DEFAULT NULL,
  `date_c` datetime NOT NULL,
  `date_u` timestamp NOT NULL,
  `fk_directory` int(11) DEFAULT NULL,
  `fk_status` smallint(6) DEFAULT '0',
  `private` smallint(6) DEFAULT '0',
  `crc` varchar(32) NOT NULL DEFAULT '',
  `cryptkey` varchar(50) NOT NULL DEFAULT '',
  `cipher` varchar(50) NOT NULL DEFAULT 'twofish',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_ecm_documents`
--

LOCK TABLES `llx_ecm_documents` WRITE;
/*!40000 ALTER TABLE `llx_ecm_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_ecm_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_contact`
--

DROP TABLE IF EXISTS `llx_element_contact`;
CREATE TABLE `llx_element_contact` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datecreate` datetime DEFAULT NULL,
  `statut` smallint(6) DEFAULT '5',
  `element_id` int(11) NOT NULL,
  `fk_c_type_contact` int(11) NOT NULL,
  `fk_socpeople` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_contact_idx1` (`element_id`,`fk_c_type_contact`,`fk_socpeople`),
  KEY `idx_element_contact_fk_socpeople` (`fk_socpeople`),
  KEY `fk_element_contact_fk_c_type_contact` (`fk_c_type_contact`),
  CONSTRAINT `fk_element_contact_fk_c_type_contact` FOREIGN KEY (`fk_c_type_contact`) REFERENCES `llx_c_type_contact` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=6;

--
-- Dumping data for table `llx_element_contact`
--

LOCK TABLES `llx_element_contact` WRITE;
/*!40000 ALTER TABLE `llx_element_contact` DISABLE KEYS */;
INSERT INTO `llx_element_contact` VALUES (1,'2010-02-07 00:22:10',4,3,160,1),(3,'2010-02-07 01:44:17',4,2,180,1),(4,'2010-02-07 01:49:15',4,3,161,1),(5,'2010-02-07 02:29:43',4,1,181,2);
/*!40000 ALTER TABLE `llx_element_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_element`
--

DROP TABLE IF EXISTS `llx_element_element`;
CREATE TABLE `llx_element_element` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_source` int(11) NOT NULL,
  `sourcetype` varchar(16) NOT NULL,
  `fk_target` int(11) NOT NULL,
  `targettype` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_element_idx1` (`fk_source`,`sourcetype`,`fk_target`,`targettype`),
  KEY `idx_element_element_fk_target` (`fk_target`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_element_element`
--

LOCK TABLES `llx_element_element` WRITE;
/*!40000 ALTER TABLE `llx_element_element` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_element` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entity`
--

DROP TABLE IF EXISTS `llx_entity`;
CREATE TABLE `llx_entity` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `datec` datetime DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=2;

--
-- Dumping data for table `llx_entity`
--

LOCK TABLES `llx_entity` WRITE;
/*!40000 ALTER TABLE `llx_entity` DISABLE KEYS */;
INSERT INTO `llx_entity` VALUES (1,'2010-02-06 23:24:37','Default Entity','This is the default entity','2010-02-07 00:24:37',1,1,1);
/*!40000 ALTER TABLE `llx_entity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entrepot`
--

DROP TABLE IF EXISTS `llx_entrepot`;
CREATE TABLE `llx_entrepot` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `label` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `lieu` varchar(64) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `statut` tinyint(4) DEFAULT '1',
  `valo_pmp` float(12,4) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_entrepot_label` (`label`,`entity`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_entrepot`
--

LOCK TABLES `llx_entrepot` WRITE;
/*!40000 ALTER TABLE `llx_entrepot` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_entrepot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_events`
--

DROP TABLE IF EXISTS `llx_events`;
CREATE TABLE `llx_events` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `type` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `dateevent` datetime DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `description` varchar(250) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `user_agent` varchar(128) DEFAULT NULL,
  `fk_object` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_events_dateevent` (`dateevent`)
) TYPE=InnoDB AUTO_INCREMENT=5;

--
-- Dumping data for table `llx_events`
--

LOCK TABLES `llx_events` WRITE;
/*!40000 ALTER TABLE `llx_events` DISABLE KEYS */;
INSERT INTO `llx_events` VALUES (1,'2010-02-14 14:43:21','USER_LOGIN',1,'2010-02-14 14:43:21',1,'(UserLogged,dolibarr)','127.0.0.1','Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7',NULL),(2,'2010-02-14 14:44:09','USER_LOGIN',1,'2010-02-14 14:44:09',1,'(UserLogged,dolibarr)','127.0.0.1','Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7',NULL),(3,'2010-02-14 14:44:40','USER_LOGIN',1,'2010-02-14 14:44:40',3,'(UserLogged,demo)','127.0.0.1','Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7',NULL),(4,'2010-02-14 14:44:49','USER_LOGIN',1,'2010-02-14 14:44:49',1,'(UserLogged,dolibarr)','127.0.0.1','Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7',NULL);
/*!40000 ALTER TABLE `llx_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition`
--

DROP TABLE IF EXISTS `llx_expedition`;
CREATE TABLE `llx_expedition` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_soc` int(11) NOT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_expedition` date DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  `fk_expedition_methode` int(11) DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `height` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `size_units` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `weight_units` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `note` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_expedition_uk_ref` (`ref`,`entity`),
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
) TYPE=InnoDB;

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
CREATE TABLE `llx_expedition_methode` (
  `rowid` int(11) NOT NULL,
  `tms` timestamp NOT NULL,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `description` text,
  `active` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_expedition_methode`
--

LOCK TABLES `llx_expedition_methode` WRITE;
/*!40000 ALTER TABLE `llx_expedition_methode` DISABLE KEYS */;
INSERT INTO `llx_expedition_methode` VALUES (1,'2010-02-04 22:05:54','CATCH','Catch','Catch by client',1),(2,'2010-02-04 22:05:54','TRANS','Transporter','Generic transporter',1),(3,'2010-02-04 22:05:55','COLSUI','Colissimo Suivi','Colissimo Suivi',0);
/*!40000 ALTER TABLE `llx_expedition_methode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expeditiondet`
--

DROP TABLE IF EXISTS `llx_expeditiondet`;
CREATE TABLE `llx_expeditiondet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_expedition` int(11) NOT NULL,
  `fk_origin_line` int(11) DEFAULT NULL,
  `fk_entrepot` int(11) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_expeditiondet_fk_expedition` (`fk_expedition`),
  CONSTRAINT `fk_expeditiondet_fk_expedition` FOREIGN KEY (`fk_expedition`) REFERENCES `llx_expedition` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_export_compta` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(12) NOT NULL,
  `date_export` datetime NOT NULL,
  `fk_user` int(11) NOT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_export_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `label` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `field` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_export_model` (`label`,`type`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_export_model`
--

LOCK TABLES `llx_export_model` WRITE;
/*!40000 ALTER TABLE `llx_export_model` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_export_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extra_fields`
--

DROP TABLE IF EXISTS `llx_extra_fields`;
CREATE TABLE `llx_extra_fields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `object` varchar(64) NOT NULL,
  `assign` int(11) DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `label` varchar(64) NOT NULL,
  `format` varchar(8) NOT NULL,
  `fieldsize` int(11) DEFAULT NULL,
  `maxlength` int(11) DEFAULT NULL,
  `options` varchar(45) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_extra_fields_name` (`name`,`entity`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_extra_fields`
--

LOCK TABLES `llx_extra_fields` WRITE;
/*!40000 ALTER TABLE `llx_extra_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_extra_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extra_fields_options`
--

DROP TABLE IF EXISTS `llx_extra_fields_options`;
CREATE TABLE `llx_extra_fields_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_extra_fields` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `rank` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_extra_fields_options_fk_extra_fields` (`fk_extra_fields`),
  CONSTRAINT `fk_extra_fields_options_fk_extra_fields` FOREIGN KEY (`fk_extra_fields`) REFERENCES `llx_extra_fields` (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_extra_fields_options`
--

LOCK TABLES `llx_extra_fields_options` WRITE;
/*!40000 ALTER TABLE `llx_extra_fields_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_extra_fields_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extra_fields_values`
--

DROP TABLE IF EXISTS `llx_extra_fields_values`;
CREATE TABLE `llx_extra_fields_values` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `datem` datetime DEFAULT NULL,
  `fk_object` int(11) NOT NULL,
  `fk_extra_fields` int(11) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `fk_user_create` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_extra_fields_values_fk_extra_fields` (`fk_extra_fields`,`entity`),
  CONSTRAINT `fk_extra_fields_values_fk_extra_fields` FOREIGN KEY (`fk_extra_fields`) REFERENCES `llx_extra_fields` (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_extra_fields_values`
--

LOCK TABLES `llx_extra_fields_values` WRITE;
/*!40000 ALTER TABLE `llx_extra_fields_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_extra_fields_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture`
--

DROP TABLE IF EXISTS `llx_facture`;
CREATE TABLE `llx_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `facnumber` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `type` smallint(6) NOT NULL DEFAULT '0',
  `ref_client` varchar(30) DEFAULT NULL,
  `increment` varchar(10) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `date_valid` date DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `amount` double NOT NULL DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `close_code` varchar(16) DEFAULT NULL,
  `close_note` varchar(128) DEFAULT NULL,
  `tva` double DEFAULT '0',
  `total` double DEFAULT '0',
  `total_ttc` double DEFAULT '0',
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) NOT NULL DEFAULT '1',
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_uk_facnumber` (`facnumber`,`entity`),
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
) TYPE=InnoDB AUTO_INCREMENT=5;

--
-- Dumping data for table `llx_facture`
--

LOCK TABLES `llx_facture` WRITE;
/*!40000 ALTER TABLE `llx_facture` DISABLE KEYS */;
INSERT INTO `llx_facture` VALUES (1,'FA1002-0001',1,0,NULL,NULL,2,'2010-02-08 12:39:36','2010-02-08',NULL,'2010-02-08 12:47:02',0,0,NULL,NULL,0,NULL,NULL,7652.82,39045,46697.82,1,1,1,NULL,1,1,6,'2010-02-08',NULL,NULL,'crabe',NULL),(2,'FA1002-0002',1,3,NULL,NULL,2,'2010-02-09 11:34:52','2010-02-10',NULL,'2010-02-10 16:03:19',0,0,NULL,NULL,0,NULL,NULL,3.92,20,23.92,1,1,1,NULL,1,1,0,'2010-02-10',NULL,NULL,'crabe',NULL),(3,'FA1002-0003',1,0,NULL,NULL,5,'2010-02-10 13:48:01','2010-02-10',NULL,'2010-02-13 00:10:21',0,0,NULL,NULL,0,NULL,NULL,0.98,5,5.98,0,1,1,NULL,NULL,1,0,'2010-02-10',NULL,NULL,'crabe',NULL),(4,'FMEDI-00270',1,0,NULL,NULL,2,'2010-02-13 00:22:36','2010-02-13',NULL,'2010-02-13 00:25:40',0,0,NULL,NULL,0,NULL,NULL,1.96,10,11.96,1,1,1,NULL,NULL,1,0,'2010-02-13',NULL,NULL,'crabe',NULL);
/*!40000 ALTER TABLE `llx_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn`
--

DROP TABLE IF EXISTS `llx_facture_fourn`;
CREATE TABLE `llx_facture_fourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `facnumber` varchar(50) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `type` smallint(6) NOT NULL DEFAULT '0',
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise` double(24,8) DEFAULT '0.00000000',
  `tva` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) NOT NULL DEFAULT '1',
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_facture_fourn_ref` (`facnumber`,`fk_soc`,`entity`),
  KEY `idx_facture_fourn_date_lim_reglement` (`date_lim_reglement`),
  KEY `idx_facture_fourn_fk_soc` (`fk_soc`),
  KEY `idx_facture_fourn_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fourn_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fourn_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_fourn_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_facture_fourn`
--

LOCK TABLES `llx_facture_fourn` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn` DISABLE KEYS */;
INSERT INTO `llx_facture_fourn` VALUES (1,'REF AAA',1,0,3,'2010-02-08 15:27:17','2010-02-08','2010-02-08 15:30:12','Supplier invoice AAA',1,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0.00000000,10.00000000,1,1,1,NULL,NULL,1,'2010-02-08','','',NULL),(2,'AADDDD',1,0,3,'2010-02-09 10:49:21','2010-02-09','2010-02-10 16:58:35','Facture matière premières',0,0.00000000,0.00000000,0.00000000,0.00000000,25.00000000,0.00000000,25.00000000,1,1,1,NULL,NULL,1,'2010-02-09','','',NULL),(3,'aaaa',1,0,5,'2010-02-10 13:55:16','2010-02-10','2010-02-10 13:55:16','aaa',0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,1,'2010-02-10','','',NULL);
/*!40000 ALTER TABLE `llx_facture_fourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn_det`
--

DROP TABLE IF EXISTS `llx_facture_fourn_det`;
CREATE TABLE `llx_facture_fourn_det` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture_fourn` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `pu_ht` double(24,8) DEFAULT NULL,
  `pu_ttc` double(24,8) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT NULL,
  `total_ht` double(24,8) DEFAULT NULL,
  `tva` double(24,8) DEFAULT NULL,
  `total_ttc` double(24,8) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_facture_fourn_det_fk_facture` (`fk_facture_fourn`),
  CONSTRAINT `fk_facture_fourn_det_fk_facture` FOREIGN KEY (`fk_facture_fourn`) REFERENCES `llx_facture_fourn` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_facture_fourn_det`
--

LOCK TABLES `llx_facture_fourn_det` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn_det` DISABLE KEYS */;
INSERT INTO `llx_facture_fourn_det` VALUES (1,1,NULL,NULL,NULL,'hfhfgh',10.00000000,10.00000000,1,0.000,10.00000000,0.00000000,10.00000000,0,NULL,NULL,NULL),(2,2,NULL,NULL,NULL,'Product 1',10.00000000,10.00000000,1,0.000,10.00000000,0.00000000,10.00000000,0,NULL,NULL,NULL),(3,2,NULL,NULL,NULL,'Service 1',15.00000000,15.00000000,1,0.000,15.00000000,0.00000000,15.00000000,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_facture_fourn_det` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_rec`
--

DROP TABLE IF EXISTS `llx_facture_rec`;
CREATE TABLE `llx_facture_rec` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(50) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `remise` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `tva` double DEFAULT '0',
  `total` double DEFAULT '0',
  `total_ttc` double DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT '0',
  `fk_mode_reglement` int(11) DEFAULT '0',
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `frequency` char(2) DEFAULT NULL,
  `last_gen` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_rec_uk_titre` (`titre`,`entity`),
  KEY `idx_facture_rec_fk_soc` (`fk_soc`),
  KEY `idx_facture_rec_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_rec_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_rec_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_facture_rec`
--

LOCK TABLES `llx_facture_rec` WRITE;
/*!40000 ALTER TABLE `llx_facture_rec` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_rec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet`
--

DROP TABLE IF EXISTS `llx_facturedet`;
CREATE TABLE `llx_facturedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `tva_tx` double DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `subprice` double DEFAULT NULL,
  `price` double DEFAULT NULL,
  `total_ht` double DEFAULT NULL,
  `total_tva` double DEFAULT NULL,
  `total_ttc` double DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `fk_code_ventilation` int(11) NOT NULL DEFAULT '0',
  `fk_export_compta` int(11) NOT NULL DEFAULT '0',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fk_remise_except` (`fk_remise_except`),
  KEY `idx_facturedet_fk_facture` (`fk_facture`),
  CONSTRAINT `fk_facturedet_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=7;

--
-- Dumping data for table `llx_facturedet`
--

LOCK TABLES `llx_facturedet` WRITE;
/*!40000 ALTER TABLE `llx_facturedet` DISABLE KEYS */;
INSERT INTO `llx_facturedet` VALUES (1,1,NULL,'Voiture électrique Media 12ERA',19.6,1,10,4025,NULL,40250,36225,36225,7100.1,43325.1,0,NULL,NULL,0,0,0,0,1,NULL),(2,1,NULL,'Assurance (1 year)',19.6,1,0,0,NULL,20,20,20,3.92,23.92,1,'2010-02-08 00:00:00','2011-02-08 00:00:00',0,0,0,0,2,NULL),(3,1,NULL,'Remorque taille 4',19.6,1,0,0,NULL,2800,2800,2800,548.8,3348.8,0,NULL,NULL,0,0,0,0,3,NULL),(4,2,NULL,'Accompte abonnement',19.6,1,0,0,NULL,20,20,20,3.92,23.92,1,'2010-02-07 00:00:00',NULL,0,0,0,0,1,NULL),(5,3,NULL,'aéeer',19.6,1,0,0,NULL,5,5,5,0.98,5.98,1,NULL,NULL,0,0,0,0,1,NULL),(6,4,NULL,'ff',19.6,1,0,0,NULL,10,10,10,1.96,11.96,1,NULL,NULL,0,0,0,0,1,NULL);
/*!40000 ALTER TABLE `llx_facturedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet_rec`
--

DROP TABLE IF EXISTS `llx_facturedet_rec`;
CREATE TABLE `llx_facturedet_rec` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `description` text,
  `tva_tx` double DEFAULT '19.6',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `subprice` double DEFAULT NULL,
  `price` double DEFAULT NULL,
  `total_ht` double DEFAULT NULL,
  `total_tva` double DEFAULT NULL,
  `total_ttc` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_fichinter` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `fk_contrat` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `datei` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `duree` double DEFAULT NULL,
  `description` text,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fichinter_ref` (`ref`,`entity`),
  KEY `idx_fichinter_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_fichinter_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_fichinter`
--

LOCK TABLES `llx_fichinter` WRITE;
/*!40000 ALTER TABLE `llx_fichinter` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fichinter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinterdet`
--

DROP TABLE IF EXISTS `llx_fichinterdet`;
CREATE TABLE `llx_fichinterdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_fichinter` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `description` text,
  `duree` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_fichinterdet_fk_fichinter` (`fk_fichinter`),
  CONSTRAINT `fk_fichinterdet_fk_fichinter` FOREIGN KEY (`fk_fichinter`) REFERENCES `llx_fichinter` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_fournisseur_ca` (
  `fk_societe` int(11) DEFAULT NULL,
  `date_calcul` datetime DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `ca_genere` float DEFAULT NULL,
  `ca_achat` float(11,2) DEFAULT '0.00',
  UNIQUE KEY `fk_societe` (`fk_societe`,`year`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_fournisseur_ca`
--

LOCK TABLES `llx_fournisseur_ca` WRITE;
/*!40000 ALTER TABLE `llx_fournisseur_ca` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fournisseur_ca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_import_model`
--

DROP TABLE IF EXISTS `llx_import_model`;
CREATE TABLE `llx_import_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `label` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `field` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_import_model` (`label`,`type`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_import_model`
--

LOCK TABLES `llx_import_model` WRITE;
/*!40000 ALTER TABLE `llx_import_model` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_import_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_livraison`
--

DROP TABLE IF EXISTS `llx_livraison`;
CREATE TABLE `llx_livraison` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_client` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_expedition` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_livraison_uk_ref` (`ref`,`entity`),
  KEY `idx_livraison_fk_soc` (`fk_soc`),
  KEY `idx_livraison_fk_user_author` (`fk_user_author`),
  KEY `idx_livraison_fk_user_valid` (`fk_user_valid`),
  KEY `idx_livraison_fk_adresse_livraison` (`fk_adresse_livraison`),
  CONSTRAINT `fk_livraison_fk_adresse_livraison` FOREIGN KEY (`fk_adresse_livraison`) REFERENCES `llx_societe_adresse_livraison` (`rowid`),
  CONSTRAINT `fk_livraison_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_livraisondet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_livraison` int(11) DEFAULT NULL,
  `fk_origin_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `qty` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_livraisondet_fk_expedition` (`fk_livraison`),
  CONSTRAINT `fk_livraisondet_fk_livraison` FOREIGN KEY (`fk_livraison`) REFERENCES `llx_livraison` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_mailing` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `statut` smallint(6) DEFAULT '0',
  `titre` varchar(60) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `sujet` varchar(60) DEFAULT NULL,
  `body` text,
  `bgcolor` varchar(8) DEFAULT NULL,
  `bgimage` varchar(255) DEFAULT NULL,
  `cible` varchar(60) DEFAULT NULL,
  `nbemail` int(11) DEFAULT NULL,
  `email_from` varchar(160) DEFAULT NULL,
  `email_replyto` varchar(160) DEFAULT NULL,
  `email_errorsto` varchar(160) DEFAULT NULL,
  `date_creat` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_appro` datetime DEFAULT NULL,
  `date_envoi` datetime DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_appro` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_mailing_cibles` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_mailing` int(11) NOT NULL,
  `fk_contact` int(11) NOT NULL,
  `nom` varchar(160) DEFAULT NULL,
  `prenom` varchar(160) DEFAULT NULL,
  `email` varchar(160) NOT NULL,
  `other` varchar(255) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `url` varchar(160) DEFAULT NULL,
  `date_envoi` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_mailing_cibles` (`fk_mailing`,`email`),
  KEY `idx_mailing_cibles_email` (`email`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_menu` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `menu_handler` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `module` varchar(64) DEFAULT NULL,
  `type` varchar(4) NOT NULL,
  `mainmenu` varchar(100) NOT NULL,
  `fk_menu` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(100) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `langs` varchar(100) DEFAULT NULL,
  `level` smallint(6) DEFAULT NULL,
  `leftmenu` varchar(1) DEFAULT '1',
  `perms` varchar(255) DEFAULT NULL,
  `enabled` varchar(255) DEFAULT '1',
  `usertype` int(11) NOT NULL DEFAULT '0',
  `tms` timestamp NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_menu_uk_menu` (`menu_handler`,`fk_menu`,`url`,`entity`),
  KEY `idx_menu_menuhandler_type` (`menu_handler`,`type`)
) TYPE=InnoDB AUTO_INCREMENT=11;

--
-- Dumping data for table `llx_menu`
--

LOCK TABLES `llx_menu` WRITE;
/*!40000 ALTER TABLE `llx_menu` DISABLE KEYS */;
INSERT INTO `llx_menu` VALUES (5,'all',1,'agenda','top','agenda',0,100,'/comm/action/index.php','','Agenda','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',0,'2010-02-13 23:34:54'),(6,'all',1,'ecm','top','ecm',0,100,'/ecm/index.php','','MenuECM','ecm',0,'1','$user->rights->ecm->download || $user->rights->ecm->upload || $user->rights->ecm->setup','$conf->ecm->enabled',2,'2010-02-14 13:49:38'),(7,'all',1,'ecm','left','ecm',6,101,'/ecm/index.php','','ECMArea','ecm',0,'','$user->rights->ecm->download || $user->rights->ecm->upload','$user->rights->ecm->download || $user->rights->ecm->upload',2,'2010-02-14 13:49:38'),(8,'all',1,'ecm','left','ecm',7,100,'/ecm/docdir.php?action=create','','ECMNewSection','ecm',0,'','$user->rights->ecm->setup','$user->rights->ecm->setup',2,'2010-02-14 13:49:38'),(9,'all',1,'ecm','left','ecm',7,102,'/ecm/index.php?action=file_manager','','ECMFileManager','ecm',0,'','$user->rights->ecm->download || $user->rights->ecm->upload','$user->rights->ecm->download || $user->rights->ecm->upload',2,'2010-02-14 13:49:38'),(10,'all',1,'ecm','left','ecm',7,103,'/ecm/search.php','','Search','ecm',0,'','$user->rights->ecm->download','$user->rights->ecm->download',2,'2010-02-14 13:49:38');
/*!40000 ALTER TABLE `llx_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_notify`
--

DROP TABLE IF EXISTS `llx_notify`;
CREATE TABLE `llx_notify` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `daten` datetime DEFAULT NULL,
  `fk_action` int(11) NOT NULL,
  `fk_contact` int(11) NOT NULL,
  `objet_type` varchar(24) NOT NULL,
  `objet_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_notify_def` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `datec` date DEFAULT NULL,
  `fk_action` int(11) NOT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_contact` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_notify_def`
--

LOCK TABLES `llx_notify_def` WRITE;
/*!40000 ALTER TABLE `llx_notify_def` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_notify_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement`
--

DROP TABLE IF EXISTS `llx_paiement`;
CREATE TABLE `llx_paiement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_paiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_export_compta` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_paiement`
--

LOCK TABLES `llx_paiement` WRITE;
/*!40000 ALTER TABLE `llx_paiement` DISABLE KEYS */;
INSERT INTO `llx_paiement` VALUES (1,'2010-02-08 12:50:04','2010-02-08 12:50:04','2010-02-08 12:00:00',10000,7,'','',2,1,NULL,0,0),(2,'2010-02-10 16:54:10','2010-02-10 16:54:10','2010-02-10 12:00:00',20,2,'','',13,1,NULL,0,0),(3,'2010-02-14 12:54:42','2010-02-14 12:54:42','2010-02-14 12:00:00',5,2,'','',16,1,NULL,0,0);
/*!40000 ALTER TABLE `llx_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement_facture`
--

DROP TABLE IF EXISTS `llx_paiement_facture`;
CREATE TABLE `llx_paiement_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_paiement` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `amount` double DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiement_facture` (`fk_paiement`,`fk_facture`),
  KEY `idx_paiement_facture_fk_facture` (`fk_facture`),
  KEY `idx_paiement_facture_fk_paiement` (`fk_paiement`),
  CONSTRAINT `fk_paiement_facture_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_paiement_facture_fk_paiement` FOREIGN KEY (`fk_paiement`) REFERENCES `llx_paiement` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=5;

--
-- Dumping data for table `llx_paiement_facture`
--

LOCK TABLES `llx_paiement_facture` WRITE;
/*!40000 ALTER TABLE `llx_paiement_facture` DISABLE KEYS */;
INSERT INTO `llx_paiement_facture` VALUES (1,1,1,10000),(2,2,1,10),(3,2,2,10),(4,3,4,5);
/*!40000 ALTER TABLE `llx_paiement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementcharge`
--

DROP TABLE IF EXISTS `llx_paiementcharge`;
CREATE TABLE `llx_paiementcharge` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_charge` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_typepaiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=6;

--
-- Dumping data for table `llx_paiementcharge`
--

LOCK TABLES `llx_paiementcharge` WRITE;
/*!40000 ALTER TABLE `llx_paiementcharge` DISABLE KEYS */;
INSERT INTO `llx_paiementcharge` VALUES (1,1,'2010-02-08 15:32:43','2010-02-08 15:32:43','2010-02-08 12:00:00',286,2,'','',10,1,NULL),(2,4,'2010-02-08 15:45:48','2010-02-08 15:45:48','2010-02-08 12:00:00',50,2,'123','',11,1,NULL),(3,4,'2010-02-10 16:11:26','2010-02-10 16:11:26','2010-02-10 12:00:00',1,2,'','',12,1,NULL),(4,2,'2010-02-14 12:56:59','2010-02-14 12:56:59','2010-02-14 12:00:00',100,1,'','',17,1,NULL),(5,3,'2010-02-14 13:16:23','2010-02-14 13:16:23','2010-02-14 12:00:00',20,1,'','',18,1,NULL);
/*!40000 ALTER TABLE `llx_paiementcharge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn`;
CREATE TABLE `llx_paiementfourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_paiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=3;

--
-- Dumping data for table `llx_paiementfourn`
--

LOCK TABLES `llx_paiementfourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn` DISABLE KEYS */;
INSERT INTO `llx_paiementfourn` VALUES (1,'2010-02-08 15:28:20','2010-02-08 15:28:20','2010-02-08 12:00:00',10,1,6,'','',9,0),(2,'2010-02-10 17:01:47','2010-02-10 17:01:47','2010-02-10 12:00:00',10,1,1,'','',14,0);
/*!40000 ALTER TABLE `llx_paiementfourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn_facturefourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn_facturefourn`;
CREATE TABLE `llx_paiementfourn_facturefourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_paiementfourn` int(11) DEFAULT NULL,
  `fk_facturefourn` int(11) DEFAULT NULL,
  `amount` double DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiementfourn_facturefourn` (`fk_paiementfourn`,`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_facture` (`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_paiement` (`fk_paiementfourn`)
) TYPE=InnoDB AUTO_INCREMENT=3;

--
-- Dumping data for table `llx_paiementfourn_facturefourn`
--

LOCK TABLES `llx_paiementfourn_facturefourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` DISABLE KEYS */;
INSERT INTO `llx_paiementfourn_facturefourn` VALUES (1,1,1,10),(2,2,2,10);
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_bons`
--

DROP TABLE IF EXISTS `llx_prelevement_bons`;
CREATE TABLE `llx_prelevement_bons` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(12) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `statut` smallint(6) DEFAULT '0',
  `credite` smallint(6) DEFAULT '0',
  `note` text,
  `date_trans` datetime DEFAULT NULL,
  `method_trans` smallint(6) DEFAULT NULL,
  `fk_user_trans` int(11) DEFAULT NULL,
  `date_credit` datetime DEFAULT NULL,
  `fk_user_credit` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_prelevement_bons_ref` (`ref`,`entity`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_prelevement_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_prelevement_lignes` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_prelevement_facture_fk_prelevement_lignes` (`fk_prelevement_lignes`),
  CONSTRAINT `fk_prelevement_facture_fk_prelevement_lignes` FOREIGN KEY (`fk_prelevement_lignes`) REFERENCES `llx_prelevement_lignes` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_prelevement_facture_demande` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `amount` double NOT NULL,
  `date_demande` datetime NOT NULL,
  `traite` smallint(6) DEFAULT '0',
  `date_traite` datetime DEFAULT NULL,
  `fk_prelevement_bons` int(11) DEFAULT NULL,
  `fk_user_demande` int(11) NOT NULL,
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_prelevement_lignes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_prelevement_bons` int(11) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `statut` smallint(6) DEFAULT '0',
  `client_nom` varchar(255) DEFAULT NULL,
  `amount` double DEFAULT '0',
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_prelevement_lignes_fk_prelevement_bons` (`fk_prelevement_bons`),
  CONSTRAINT `fk_prelevement_lignes_fk_prelevement_bons` FOREIGN KEY (`fk_prelevement_bons`) REFERENCES `llx_prelevement_bons` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_prelevement_notifications` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `action` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_prelevement_rejet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_prelevement_lignes` int(11) DEFAULT NULL,
  `date_rejet` datetime DEFAULT NULL,
  `motif` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_creation` int(11) DEFAULT NULL,
  `note` text,
  `afacturer` tinyint(4) DEFAULT '0',
  `fk_facture` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `ref` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `label` varchar(255) NOT NULL,
  `description` text,
  `note` text,
  `price` double(24,8) DEFAULT '0.00000000',
  `price_ttc` double(24,8) DEFAULT '0.00000000',
  `price_min` double(24,8) DEFAULT '0.00000000',
  `price_min_ttc` double(24,8) DEFAULT '0.00000000',
  `price_base_type` varchar(3) DEFAULT 'HT',
  `tva_tx` double(6,3) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `envente` tinyint(4) DEFAULT '1',
  `fk_product_type` int(11) DEFAULT '0',
  `duration` varchar(6) DEFAULT NULL,
  `seuil_stock_alerte` int(11) DEFAULT '0',
  `stock_loc` varchar(10) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `fk_barcode_type` int(11) DEFAULT '0',
  `partnumber` varchar(32) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `weight_units` tinyint(4) DEFAULT NULL,
  `volume` float DEFAULT NULL,
  `volume_units` tinyint(4) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `pmp` double(24,8) NOT NULL DEFAULT '0.00000000',
  `canvas` varchar(15) DEFAULT '',
  `finished` tinyint(4) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_ref` (`ref`,`entity`),
  KEY `idx_product_barcode` (`barcode`),
  KEY `idx_product_import_key` (`import_key`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_product`
--

LOCK TABLES `llx_product` WRITE;
/*!40000 ALTER TABLE `llx_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_ca`
--

DROP TABLE IF EXISTS `llx_product_ca`;
CREATE TABLE `llx_product_ca` (
  `fk_product` int(11) DEFAULT NULL,
  `date_calcul` datetime DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `ca_genere` float DEFAULT NULL,
  UNIQUE KEY `fk_product` (`fk_product`,`year`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_cnv_livre` (
  `rowid` int(11) NOT NULL,
  `isbn` varchar(13) DEFAULT NULL,
  `ean` varchar(13) DEFAULT NULL,
  `format` varchar(7) DEFAULT NULL,
  `px_feuillet` float(12,4) DEFAULT NULL,
  `px_reliure` float(12,4) DEFAULT NULL,
  `px_couverture` float(12,4) DEFAULT NULL,
  `px_revient` float(12,4) DEFAULT NULL,
  `stock_loc` varchar(5) DEFAULT NULL,
  `pages` smallint(5) unsigned DEFAULT NULL,
  `fk_couverture` int(11) DEFAULT NULL,
  `fk_contrat` int(11) DEFAULT NULL,
  `fk_auteur` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_cnv_livre_contrat` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_cnv_livre` int(11) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `taux` float(3,2) DEFAULT NULL,
  `date_app` datetime DEFAULT NULL,
  `duree` varchar(50) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `locked` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_det` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL,
  `description` text,
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `ref_fourn` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_fournisseur_ref` (`ref_fourn`,`fk_soc`,`entity`),
  KEY `idx_product_fourn_fk_product` (`fk_product`,`entity`),
  KEY `idx_product_fourn_fk_soc` (`fk_soc`,`entity`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_product_fournisseur`
--

LOCK TABLES `llx_product_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price`;
CREATE TABLE `llx_product_fournisseur_price` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `fk_product_fournisseur` int(11) NOT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `unitprice` double(24,8) DEFAULT '0.00000000',
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_product_fournisseur_price_fk_user` (`fk_user`),
  KEY `idx_product_fournisseur_price_fk_product_fournisseur` (`fk_product_fournisseur`),
  CONSTRAINT `fk_product_fournisseur_price_fk_product_fournisseur` FOREIGN KEY (`fk_product_fournisseur`) REFERENCES `llx_product_fournisseur` (`rowid`),
  CONSTRAINT `fk_product_fournisseur_price_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_fournisseur_price_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `fk_product_fournisseur` int(11) NOT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_price` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_product` int(11) NOT NULL,
  `date_price` datetime NOT NULL,
  `price_level` smallint(6) DEFAULT '1',
  `price` double(24,8) DEFAULT NULL,
  `price_ttc` double(24,8) DEFAULT NULL,
  `price_min` double(24,8) DEFAULT NULL,
  `price_min_ttc` double(24,8) DEFAULT NULL,
  `price_base_type` varchar(3) DEFAULT 'HT',
  `tva_tx` double(6,3) NOT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `envente` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_stock` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `fk_product` int(11) NOT NULL,
  `fk_entrepot` int(11) NOT NULL,
  `reel` double DEFAULT NULL,
  `pmp` double(24,8) NOT NULL DEFAULT '0.00000000',
  PRIMARY KEY (`rowid`),
  KEY `idx_product_stock_fk_product` (`fk_product`),
  KEY `idx_product_stock_fk_entrepot` (`fk_entrepot`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_product_subproduct` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL,
  `fk_product_subproduct` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_product` (`fk_product`,`fk_product_subproduct`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_projet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `datec` date DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `dateo` date DEFAULT NULL,
  `datee` date DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL,
  `description` text,
  `fk_user_creat` int(11) NOT NULL,
  `public` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_projet_ref` (`ref`,`entity`),
  KEY `idx_projet_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_projet_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_projet`
--

LOCK TABLES `llx_projet` WRITE;
/*!40000 ALTER TABLE `llx_projet` DISABLE KEYS */;
INSERT INTO `llx_projet` VALUES (1,NULL,'2010-02-06','2010-02-08 14:59:12','2010-02-06',NULL,'PJ1',1,'Project Alpha','',1,0,0,NULL,NULL,'baleine'),(3,1,'2010-02-06','2010-02-08 14:59:29','2010-02-06',NULL,'PJ2',1,'Project Beta','',1,0,1,NULL,NULL,'baleine');
/*!40000 ALTER TABLE `llx_projet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_milestone`
--

DROP TABLE IF EXISTS `llx_projet_milestone`;
CREATE TABLE `llx_projet_milestone` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_projet` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `dateo` datetime DEFAULT NULL,
  `datee` datetime DEFAULT NULL,
  `priority` int(11) DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_projet_milestone_fk_projet` (`fk_projet`),
  KEY `idx_projet_milestone_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_projet_milestone_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_projet_milestone_fk_user_creat` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_projet_milestone`
--

LOCK TABLES `llx_projet_milestone` WRITE;
/*!40000 ALTER TABLE `llx_projet_milestone` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet_milestone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task`
--

DROP TABLE IF EXISTS `llx_projet_task`;
CREATE TABLE `llx_projet_task` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_projet` int(11) NOT NULL,
  `fk_task_parent` int(11) NOT NULL DEFAULT '0',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `dateo` datetime DEFAULT NULL,
  `datee` datetime DEFAULT NULL,
  `datev` datetime DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `duration_effective` double NOT NULL DEFAULT '0',
  `progress` int(11) DEFAULT '0',
  `priority` int(11) DEFAULT '0',
  `fk_milestone` int(11) DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `note_private` text,
  `note_public` text,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_projet_task_fk_projet` (`fk_projet`),
  KEY `idx_projet_task_fk_user_creat` (`fk_user_creat`),
  KEY `idx_projet_task_fk_user_valid` (`fk_user_valid`),
  CONSTRAINT `fk_projet_task_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_projet_task_fk_user_creat` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_projet_task_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=3;

--
-- Dumping data for table `llx_projet_task`
--

LOCK TABLES `llx_projet_task` WRITE;
/*!40000 ALTER TABLE `llx_projet_task` DISABLE KEYS */;
INSERT INTO `llx_projet_task` VALUES (1,3,0,'2010-02-07 01:42:00','2010-02-07 01:48:28','2010-02-09 12:00:00','2010-02-17 12:00:00',NULL,'aaapp','',0,20,0,0,1,NULL,NULL,0,'jjj','hhh',0),(2,3,1,'2010-02-07 01:44:17','2010-02-07 01:44:17','2010-02-07 12:00:00',NULL,NULL,'t2','',0,0,0,0,1,NULL,NULL,0,NULL,NULL,0);
/*!40000 ALTER TABLE `llx_projet_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task_time`
--

DROP TABLE IF EXISTS `llx_projet_task_time`;
CREATE TABLE `llx_projet_task_time` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) NOT NULL,
  `task_date` date DEFAULT NULL,
  `task_duration` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_propal` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_client` varchar(30) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `datep` date DEFAULT NULL,
  `fin_validite` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `price` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `tva` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_propal_ref` (`ref`,`entity`),
  KEY `idx_propal_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_propal_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_propaldet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_propal` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `fk_remise_except` int(11) DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `price` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `pa_ht` double(24,8) DEFAULT '0.00000000',
  `marge_tx` double(6,3) DEFAULT '0.000',
  `marque_tx` double(6,3) DEFAULT '0.000',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_propaldet_fk_propal` (`fk_propal`),
  CONSTRAINT `fk_propaldet_fk_propal` FOREIGN KEY (`fk_propal`) REFERENCES `llx_propal` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_rights_def` (
  `id` int(11) NOT NULL DEFAULT '0',
  `libelle` varchar(255) DEFAULT NULL,
  `module` varchar(64) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `perms` varchar(50) DEFAULT NULL,
  `subperms` varchar(50) DEFAULT NULL,
  `type` varchar(1) DEFAULT NULL,
  `bydefault` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`,`entity`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_rights_def`
--

LOCK TABLES `llx_rights_def` WRITE;
/*!40000 ALTER TABLE `llx_rights_def` DISABLE KEYS */;
INSERT INTO `llx_rights_def` VALUES (11,'Lire les factures','facture',1,'lire',NULL,'a',1),(12,'Creer les factures','facture',1,'creer',NULL,'a',0),(13,'Modifier les factures','facture',1,'modifier',NULL,'a',0),(14,'Valider les factures','facture',1,'valider',NULL,'a',0),(15,'Envoyer les factures par mail','facture',1,'envoyer',NULL,'a',0),(16,'Emettre des paiements sur les factures','facture',1,'paiement',NULL,'a',0),(19,'Supprimer les factures','facture',1,'supprimer',NULL,'a',0),(21,'Lire les propositions commerciales','propale',1,'lire',NULL,'r',1),(22,'Creer/modifier les propositions commerciales','propale',1,'creer',NULL,'w',0),(24,'Valider les propositions commerciales','propale',1,'valider',NULL,'d',0),(25,'Envoyer les propositions commerciales aux clients','propale',1,'envoyer',NULL,'d',0),(26,'Cloturer les propositions commerciales','propale',1,'cloturer',NULL,'d',0),(27,'Supprimer les propositions commerciales','propale',1,'supprimer',NULL,'d',0),(28,'Exporter les propositions commerciales et attributs','propale',1,'export',NULL,'r',0),(31,'Lire les produits','produit',1,'lire',NULL,'r',1),(32,'Creer/modifier les produits','produit',1,'creer',NULL,'w',0),(34,'Supprimer les produits','produit',1,'supprimer',NULL,'d',0),(38,'Exporter les produits','produit',1,'export',NULL,'r',0),(41,'Lire les projets','projet',1,'lire',NULL,'r',1),(42,'Creer/modifier les projets','projet',1,'creer',NULL,'w',0),(44,'Supprimer les projets','projet',1,'supprimer',NULL,'d',0),(61,'Lire les fiches d\'intervention','ficheinter',1,'lire',NULL,'r',1),(62,'Creer/modifier les fiches d\'intervention','ficheinter',1,'creer',NULL,'w',0),(64,'Supprimer les fiches d\'intervention','ficheinter',1,'supprimer',NULL,'d',0),(67,'Exporter les fiches interventions','ficheinter',1,'export',NULL,'r',0),(71,'Read members\' card','adherent',1,'lire',NULL,'r',1),(72,'Create/modify members (need also user module permissions if member linked to a user)','adherent',1,'creer',NULL,'w',0),(74,'Remove members','adherent',1,'supprimer',NULL,'d',0),(75,'Setup types and attributes of members','adherent',1,'configurer',NULL,'w',0),(76,'Export members','adherent',1,'export',NULL,'r',0),(78,'Read subscriptions','adherent',1,'cotisation','lire','r',1),(79,'Create/modify/remove subscriptions','adherent',1,'cotisation','creer','w',0),(81,'Lire les commandes clients','commande',1,'lire',NULL,'r',1),(82,'Creer/modifier les commandes clients','commande',1,'creer',NULL,'w',0),(84,'Valider les commandes clients','commande',1,'valider',NULL,'d',0),(86,'Envoyer les commandes clients','commande',1,'envoyer',NULL,'d',0),(87,'Cloturer les commandes clients','commande',1,'cloturer',NULL,'d',0),(88,'Annuler les commandes clients','commande',1,'annuler',NULL,'d',0),(89,'Supprimer les commandes clients','commande',1,'supprimer',NULL,'d',0),(91,'Lire les charges','tax',1,'charges','lire','r',1),(92,'Creer/modifier les charges','tax',1,'charges','creer','w',0),(93,'Supprimer les charges','tax',1,'charges','supprimer','d',0),(94,'Exporter les charges','tax',1,'charges','export','r',0),(95,'Lire CA, bilans, resultats','compta',1,'resultat','lire','r',1),(96,'Parametrer la ventilation','compta',1,'ventilation','parametrer','r',0),(97,'Lire les ventilations de factures','compta',1,'ventilation','lire','r',1),(98,'Ventiler les lignes de factures','compta',1,'ventilation','creer','r',0),(101,'Lire les expeditions','expedition',1,'lire',NULL,'r',1),(102,'Creer modifier les expeditions','expedition',1,'creer',NULL,'w',0),(104,'Valider les expeditions','expedition',1,'valider',NULL,'d',0),(109,'Supprimer les expeditions','expedition',1,'supprimer',NULL,'d',0),(111,'Lire les comptes bancaires','banque',1,'lire',NULL,'r',1),(112,'Creer/modifier montant/supprimer ecriture bancaire','banque',1,'modifier',NULL,'w',0),(113,'Configurer les comptes bancaires (creer, gerer categories)','banque',1,'configurer',NULL,'a',0),(114,'Rapprocher les ecritures bancaires','banque',1,'consolidate',NULL,'w',0),(115,'Exporter transactions et releves','banque',1,'export',NULL,'r',0),(116,'Virements entre comptes','banque',1,'transfer',NULL,'w',0),(117,'Gerer les envois de cheques','banque',1,'cheque',NULL,'w',0),(121,'Lire les societes','societe',1,'lire',NULL,'r',1),(122,'Creer modifier les societes','societe',1,'creer',NULL,'w',0),(125,'Supprimer les societes','societe',1,'supprimer',NULL,'d',0),(126,'Exporter les societes','societe',1,'export',NULL,'r',0),(141,'Lire les taches','projet',1,'task','lire','r',1),(142,'Creer/modifier les taches','projet',1,'task','creer','w',0),(144,'Supprimer les taches','projet',1,'task','supprimer','d',0),(161,'Lire les contrats','contrat',1,'lire',NULL,'r',1),(162,'Creer / modifier les contrats','contrat',1,'creer',NULL,'w',0),(163,'Activer un service d\'un contrat','contrat',1,'activer',NULL,'w',0),(164,'Desactiver un service d\'un contrat','contrat',1,'desactiver',NULL,'w',0),(165,'Supprimer un contrat','contrat',1,'supprimer',NULL,'d',0),(170,'Lire les deplacements','deplacement',1,'lire',NULL,'r',1),(171,'Creer/modifier les deplacements','deplacement',1,'creer',NULL,'w',0),(172,'Supprimer les deplacements','deplacement',1,'supprimer',NULL,'d',0),(178,'Exporter les deplacements','deplacement',1,'export',NULL,'d',0),(221,'Consulter les mailings','mailing',1,'lire',NULL,'r',1),(222,'Creer/modifier les mailings (sujet, destinataires...)','mailing',1,'creer',NULL,'w',0),(223,'Valider les mailings (permet leur envoi)','mailing',1,'valider',NULL,'w',0),(229,'Supprimer les mailings)','mailing',1,'supprimer',NULL,'d',0),(241,'Lire les categories','categorie',1,'lire',NULL,'r',1),(242,'Creer/modifier les categories','categorie',1,'creer',NULL,'w',0),(243,'Supprimer les categories','categorie',1,'supprimer',NULL,'d',0),(244,'Voir le contenu des categories cachees','categorie',1,'voir',NULL,'r',1),(251,'Consulter les autres utilisateurs, leurs groupes et permissions','user',1,'user','lire','r',1),(252,'Creer/modifier les autres utilisateurs, les groupes et leurs permissions','user',1,'user','creer','w',0),(253,'Modifier mot de passe des autres utilisateurs','user',1,'user','password','w',0),(254,'Supprimer ou desactiver les autres utilisateurs','user',1,'user','supprimer','d',0),(255,'Creer/modifier ses propres infos utilisateur','user',1,'self','creer','w',1),(256,'Modifier son propre mot de passe','user',1,'self','password','w',1),(258,'Exporter les utilisateurs','user',1,'user','export','r',0),(262,'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).','societe',1,'client','voir','r',1),(281,'Lire les contacts','societe',1,'contact','lire','r',1),(282,'Creer modifier les contacts','societe',1,'contact','creer','w',0),(283,'Supprimer les contacts','societe',1,'contact','supprimer','d',0),(286,'Exporter les contacts','societe',1,'contact','export','d',0),(331,'Lire les bookmarks','bookmark',1,'lire',NULL,'r',1),(332,'Creer/modifier les bookmarks','bookmark',1,'creer',NULL,'r',1),(333,'Supprimer les bookmarks','bookmark',1,'supprimer',NULL,'r',1),(531,'Lire les services','service',1,'lire',NULL,'r',1),(532,'Creer/modifier les services','service',1,'creer',NULL,'w',0),(534,'Supprimer les services','service',1,'supprimer',NULL,'d',0),(538,'Exporter les services','service',1,'export',NULL,'r',0),(700,'Lire les dons','don',1,'lire',NULL,'r',1),(701,'Creer/modifier les dons','don',1,'creer',NULL,'w',0),(702,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(1001,'Lire les stocks','stock',1,'lire',NULL,'r',1),(1002,'Creer/Modifier les stocks','stock',1,'creer',NULL,'w',0),(1003,'Supprimer les stocks','stock',1,'supprimer',NULL,'d',0),(1004,'Lire mouvements de stocks','stock',1,'mouvement','lire','r',1),(1005,'Creer/modifier mouvements de stocks','stock',1,'mouvement','creer','w',0),(1101,'Lire les bons de livraison','expedition',1,'livraison','lire','r',1),(1102,'Creer modifier les bons de livraison','expedition',1,'livraison','creer','w',0),(1104,'Valider les bons de livraison','expedition',1,'livraison','valider','d',0),(1109,'Supprimer les bons de livraison','expedition',1,'livraison','supprimer','d',0),(1181,'Consulter les fournisseurs','fournisseur',1,'lire',NULL,'r',1),(1182,'Lire les commandes fournisseur','fournisseur',1,'commande','lire','r',1),(1183,'Creer une commande fournisseur','fournisseur',1,'commande','creer','w',0),(1184,'Valider une commande fournisseur','fournisseur',1,'commande','valider','w',0),(1185,'Approuver les commandes fournisseur','fournisseur',1,'commande','approuver','w',0),(1186,'Commander une commande fournisseur','fournisseur',1,'commande','commander','w',0),(1187,'Receptionner les commandes fournisseur','fournisseur',1,'commande','receptionner','d',0),(1188,'Cloturer les commandes fournisseur','fournisseur',1,'commande','cloturer','d',0),(1189,'Annuler les commandes fournisseur','fournisseur',1,'commande','annuler','d',0),(1201,'Lire les exports','export',1,'lire',NULL,'r',1),(1202,'Creer/modifier un export','export',1,'creer',NULL,'w',0),(1231,'Lire les factures fournisseur','fournisseur',1,'facture','lire','r',1),(1232,'Creer une facture fournisseur','fournisseur',1,'facture','creer','w',0),(1233,'Valider une facture fournisseur','fournisseur',1,'facture','valider','w',0),(1234,'Supprimer une facture fournisseur','fournisseur',1,'facture','supprimer','d',0),(1236,'Exporter les factures fournisseurs, attributs et reglements','fournisseur',1,'facture','export','r',0),(1251,'Run mass imports of external data (data load)','import',1,'run',NULL,'r',0),(1321,'Exporter les factures clients, attributs et reglements','facture',1,'facture','export','r',0),(1421,'Exporter les commandes clients et attributs','commande',1,'commande','export','r',0),(2401,'Read actions/tasks linked to his account','agenda',1,'myactions','read','r',1),(2402,'Create/modify actions/tasks linked to his account','agenda',1,'myactions','create','w',0),(2403,'Delete actions/tasks linked to his account','agenda',1,'myactions','delete','w',0),(2411,'Read actions/tasks of others','agenda',1,'allactions','read','r',0),(2412,'Create/modify actions/tasks of others','agenda',1,'allactions','create','w',0),(2413,'Delete actions/tasks of others','agenda',1,'allactions','delete','w',0),(2500,'Consulter les documents','ecm',1,'download',NULL,'r',1),(2501,'Soumettre ou supprimer des documents','ecm',1,'upload',NULL,'w',1),(2515,'Administrer les rubriques de documents','ecm',1,'setup',NULL,'w',1);
/*!40000 ALTER TABLE `llx_rights_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe`
--

DROP TABLE IF EXISTS `llx_societe`;
CREATE TABLE `llx_societe` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `statut` tinyint(4) DEFAULT '0',
  `parent` int(11) DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `nom` varchar(60) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `code_client` varchar(15) DEFAULT NULL,
  `code_fournisseur` varchar(15) DEFAULT NULL,
  `code_compta` varchar(15) DEFAULT NULL,
  `code_compta_fournisseur` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT '0',
  `fk_pays` int(11) DEFAULT '0',
  `tel` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `fk_secteur` int(11) DEFAULT '0',
  `fk_effectif` int(11) DEFAULT '0',
  `fk_typent` int(11) DEFAULT '0',
  `fk_forme_juridique` int(11) DEFAULT '0',
  `siren` varchar(16) DEFAULT NULL,
  `siret` varchar(16) DEFAULT NULL,
  `ape` varchar(16) DEFAULT NULL,
  `idprof4` varchar(16) DEFAULT NULL,
  `tva_intra` varchar(20) DEFAULT NULL,
  `capital` double DEFAULT NULL,
  `description` text,
  `fk_stcomm` smallint(6) DEFAULT '0',
  `note` text,
  `services` tinyint(4) DEFAULT '0',
  `prefix_comm` varchar(5) DEFAULT NULL,
  `client` tinyint(4) DEFAULT '0',
  `fournisseur` tinyint(4) DEFAULT '0',
  `supplier_account` varchar(32) DEFAULT NULL,
  `fk_prospectlevel` varchar(12) DEFAULT NULL,
  `customer_bad` tinyint(4) DEFAULT '0',
  `customer_rate` double DEFAULT '0',
  `supplier_rate` double DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `remise_client` double DEFAULT '0',
  `mode_reglement` tinyint(4) DEFAULT NULL,
  `cond_reglement` tinyint(4) DEFAULT NULL,
  `tva_assuj` tinyint(4) DEFAULT '1',
  `localtax1_assuj` tinyint(4) DEFAULT '0',
  `localtax2_assuj` tinyint(4) DEFAULT '0',
  `gencod` varchar(255) DEFAULT NULL,
  `price_level` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_prefix_comm` (`prefix_comm`,`entity`),
  UNIQUE KEY `uk_societe_code_client` (`code_client`,`entity`),
  KEY `idx_societe_user_creat` (`fk_user_creat`),
  KEY `idx_societe_user_modif` (`fk_user_modif`)
) TYPE=InnoDB AUTO_INCREMENT=6;

--
-- Dumping data for table `llx_societe`
--

LOCK TABLES `llx_societe` WRITE;
/*!40000 ALTER TABLE `llx_societe` DISABLE KEYS */;
INSERT INTO `llx_societe` VALUES (1,0,NULL,'2010-02-08 12:39:02','2010-02-07 02:24:16','2010-02-08 12:39:02','Carro & Fils',1,NULL,NULL,NULL,NULL,'','78180','Le Mans',0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,3,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,0,0,NULL,NULL,NULL),(2,0,NULL,'2010-02-08 12:54:11','2010-02-08 03:31:53','2010-02-08 12:54:11','ABS & Co',1,'CU0001',NULL,NULL,NULL,'10 via allegro','85000','Madrid',0,4,NULL,NULL,NULL,NULL,0,NULL,3,NULL,'','','','','',500,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,0,0,NULL,NULL,NULL),(3,0,NULL,'2010-02-08 03:34:28','2010-02-08 03:34:28','2010-02-08 03:34:28','Go sup company',1,NULL,NULL,NULL,NULL,'',NULL,NULL,0,4,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,0,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,0,0,NULL,NULL,NULL),(4,0,NULL,'2010-02-10 13:44:13','2010-02-10 13:44:13','2010-02-10 13:44:13','RueDuCommerce FR',1,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','FR26422797720',0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,0,0,NULL,NULL,NULL),(5,0,NULL,'2010-02-10 13:50:08','2010-02-10 13:44:31','2010-02-10 13:50:08','RueDuCommerce BE',1,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','BE123456',0,NULL,0,NULL,0,NULL,1,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,0,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_adresse_livraison`
--

DROP TABLE IF EXISTS `llx_societe_adresse_livraison`;
CREATE TABLE `llx_societe_adresse_livraison` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `fk_societe` int(11) DEFAULT '0',
  `nom` varchar(60) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `tel` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `note` text,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_societe_commerciaux` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_commerciaux` (`fk_soc`,`fk_user`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_societe_commerciaux`
--

LOCK TABLES `llx_societe_commerciaux` WRITE;
/*!40000 ALTER TABLE `llx_societe_commerciaux` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_commerciaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_log`
--

DROP TABLE IF EXISTS `llx_societe_log`;
CREATE TABLE `llx_societe_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datel` datetime DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_statut` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `author` varchar(30) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_societe_prices` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT '0',
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `price_level` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_societe_remise` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `tms` timestamp NOT NULL,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `remise_client` double DEFAULT '0',
  `note` text,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_societe_remise_except` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `amount_ht` double(24,8) NOT NULL,
  `amount_tva` double(24,8) NOT NULL DEFAULT '0.00000000',
  `amount_ttc` double(24,8) NOT NULL DEFAULT '0.00000000',
  `tva_tx` double(6,3) NOT NULL DEFAULT '0.000',
  `fk_user` int(11) NOT NULL,
  `fk_facture_line` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`rowid`),
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
) TYPE=InnoDB;

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
CREATE TABLE `llx_societe_rib` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `bic` varchar(10) DEFAULT NULL,
  `iban_prefix` varchar(34) DEFAULT NULL,
  `domiciliation` varchar(255) DEFAULT NULL,
  `proprio` varchar(60) DEFAULT NULL,
  `adresse_proprio` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_socpeople` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `civilite` varchar(6) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(25) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `birthday` date DEFAULT NULL,
  `poste` varchar(80) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_perso` varchar(30) DEFAULT NULL,
  `phone_mobile` varchar(30) DEFAULT NULL,
  `fax` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `jabberid` varchar(255) DEFAULT NULL,
  `priv` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT '0',
  `fk_user_modif` int(11) DEFAULT NULL,
  `note` text,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_socpeople_fk_soc` (`fk_soc`),
  KEY `idx_socpeople_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_socpeople_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_socpeople_user_creat_user_rowid` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=2;

--
-- Dumping data for table `llx_socpeople`
--

LOCK TABLES `llx_socpeople` WRITE;
/*!40000 ALTER TABLE `llx_socpeople` DISABLE KEYS */;
INSERT INTO `llx_socpeople` VALUES (1,'2010-02-10 16:48:44','2010-02-10 16:50:01',2,1,'','aaaaa','','10 via allegro','85000','Madrid',4,NULL,'','','','','','','',0,1,1,'',NULL);
/*!40000 ALTER TABLE `llx_socpeople` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_stock_mouvement`
--

DROP TABLE IF EXISTS `llx_stock_mouvement`;
CREATE TABLE `llx_stock_mouvement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `datem` datetime DEFAULT NULL,
  `fk_product` int(11) NOT NULL,
  `fk_entrepot` int(11) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `price` float(13,4) DEFAULT '0.0000',
  `type_mouvement` smallint(6) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_stock_mouvement_fk_product` (`fk_product`),
  KEY `idx_stock_mouvement_fk_entrepot` (`fk_entrepot`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_stock_mouvement`
--

LOCK TABLES `llx_stock_mouvement` WRITE;
/*!40000 ALTER TABLE `llx_stock_mouvement` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_stock_mouvement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_texts`
--

DROP TABLE IF EXISTS `llx_texts`;
CREATE TABLE `llx_texts` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(32) DEFAULT NULL,
  `typemodele` varchar(32) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `private` smallint(6) NOT NULL DEFAULT '0',
  `fk_user` int(11) DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `content` text,
  `tms` timestamp NOT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_texts`
--

LOCK TABLES `llx_texts` WRITE;
/*!40000 ALTER TABLE `llx_texts` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_texts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_tmp_caisse`
--

DROP TABLE IF EXISTS `llx_tmp_caisse`;
CREATE TABLE `llx_tmp_caisse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_article` tinyint(4) NOT NULL,
  `qte` int(11) NOT NULL,
  `fk_tva` int(11) NOT NULL,
  `remise_percent` int(11) NOT NULL,
  `remise` float NOT NULL,
  `total_ht` float NOT NULL,
  `total_ttc` float NOT NULL,
  PRIMARY KEY (`id`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_tmp_caisse`
--

LOCK TABLES `llx_tmp_caisse` WRITE;
/*!40000 ALTER TABLE `llx_tmp_caisse` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_tmp_caisse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_tva`
--

DROP TABLE IF EXISTS `llx_tva`;
CREATE TABLE `llx_tva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL,
  `datep` date DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `note` text,
  `fk_bank` int(11) DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=2;

--
-- Dumping data for table `llx_tva`
--

LOCK TABLES `llx_tva` WRITE;
/*!40000 ALTER TABLE `llx_tva` DISABLE KEYS */;
INSERT INTO `llx_tva` VALUES (1,'2010-02-14 12:18:27','2010-02-14','2010-02-14',156,'Réglement TVA 2007',1,NULL,15,1,NULL);
/*!40000 ALTER TABLE `llx_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user`
--

DROP TABLE IF EXISTS `llx_user`;
CREATE TABLE `llx_user` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `login` varchar(24) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `pass` varchar(32) DEFAULT NULL,
  `pass_crypted` varchar(128) DEFAULT NULL,
  `pass_temp` varchar(32) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `office_fax` varchar(20) DEFAULT NULL,
  `user_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `admin` smallint(6) DEFAULT '0',
  `webcal_login` varchar(25) DEFAULT NULL,
  `phenix_login` varchar(25) DEFAULT NULL,
  `phenix_pass` varchar(128) DEFAULT NULL,
  `module_comm` smallint(6) DEFAULT '1',
  `module_compta` smallint(6) DEFAULT '1',
  `fk_societe` int(11) DEFAULT NULL,
  `fk_socpeople` int(11) DEFAULT NULL,
  `fk_member` int(11) DEFAULT NULL,
  `note` text,
  `datelastlogin` datetime DEFAULT NULL,
  `datepreviouslogin` datetime DEFAULT NULL,
  `egroupware_id` int(11) DEFAULT NULL,
  `ldap_sid` varchar(255) DEFAULT NULL,
  `statut` tinyint(4) DEFAULT '1',
  `photo` varchar(255) DEFAULT NULL,
  `lang` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_login` (`login`,`entity`),
  UNIQUE KEY `uk_user_fk_socpeople` (`fk_socpeople`),
  UNIQUE KEY `uk_user_fk_member` (`fk_member`),
  KEY `uk_user_fk_societe` (`fk_societe`)
) TYPE=InnoDB AUTO_INCREMENT=4;

--
-- Dumping data for table `llx_user`
--

LOCK TABLES `llx_user` WRITE;
/*!40000 ALTER TABLE `llx_user` DISABLE KEYS */;
INSERT INTO `llx_user` VALUES (1,'2010-02-04 22:55:24','2010-02-14 14:32:17','dolibarr',0,'d0libarr-an','0ffdd97fb373728b35db853c7177de80',NULL,'Dolibarr admin','','','','','',1,'','','',1,1,NULL,NULL,NULL,'','2010-02-14 14:44:49','2010-02-14 14:44:09',NULL,'',1,NULL,NULL),(2,'2010-02-04 23:04:29','2010-02-13 22:30:30','aaa',1,'x4g05kzd','ceb598364f90134243283d373b131ef5',NULL,'aaa','','','','','',0,'','','',1,1,NULL,NULL,NULL,'',NULL,NULL,NULL,'',1,'ldestailleur_124x127.png',NULL),(3,'2010-02-14 14:27:50','2010-02-14 14:27:50','demo',1,'demo','fe01ce2a7fbac8fafaed7c982a04e229',NULL,'demo','','','','','',0,'','','',1,1,NULL,NULL,NULL,'This is a demo users<br />','2010-02-14 14:44:40',NULL,NULL,'',1,NULL,NULL);
/*!40000 ALTER TABLE `llx_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_alert`
--

DROP TABLE IF EXISTS `llx_user_alert`;
CREATE TABLE `llx_user_alert` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_user_clicktodial` (
  `fk_user` int(11) NOT NULL,
  `login` varchar(32) DEFAULT NULL,
  `pass` varchar(64) DEFAULT NULL,
  `poste` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`fk_user`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_user_entrepot` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_entrepot` int(10) unsigned DEFAULT NULL,
  `fk_user` int(10) unsigned DEFAULT NULL,
  `consult` smallint(5) unsigned DEFAULT NULL,
  `send` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_user_param` (
  `fk_user` int(11) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `param` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `uk_user_param` (`fk_user`,`param`,`entity`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_user_param`
--

LOCK TABLES `llx_user_param` WRITE;
/*!40000 ALTER TABLE `llx_user_param` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_rights`
--

DROP TABLE IF EXISTS `llx_user_rights`;
CREATE TABLE `llx_user_rights` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_rights` (`fk_user`,`fk_id`),
  CONSTRAINT `fk_user_rights_fk_user_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) TYPE=InnoDB AUTO_INCREMENT=321;

--
-- Dumping data for table `llx_user_rights`
--

LOCK TABLES `llx_user_rights` WRITE;
/*!40000 ALTER TABLE `llx_user_rights` DISABLE KEYS */;
INSERT INTO `llx_user_rights` VALUES (30,1,11),(31,1,12),(32,1,13),(33,1,14),(34,1,15),(35,1,16),(36,1,19),(112,1,21),(113,1,22),(114,1,24),(115,1,25),(116,1,26),(117,1,27),(118,1,28),(128,1,31),(129,1,32),(130,1,34),(131,1,38),(65,1,41),(66,1,42),(67,1,44),(38,1,61),(39,1,62),(40,1,64),(41,1,67),(71,1,71),(72,1,72),(73,1,74),(74,1,75),(75,1,76),(76,1,78),(77,1,79),(95,1,81),(96,1,82),(97,1,84),(98,1,86),(99,1,87),(100,1,88),(101,1,89),(124,1,91),(125,1,92),(126,1,93),(127,1,94),(26,1,95),(27,1,96),(28,1,97),(29,1,98),(19,1,111),(20,1,112),(21,1,113),(22,1,114),(23,1,115),(24,1,116),(25,1,117),(4,1,121),(5,1,122),(6,1,125),(7,1,126),(68,1,141),(69,1,142),(70,1,144),(46,1,161),(47,1,162),(48,1,163),(49,1,164),(50,1,165),(103,1,170),(104,1,171),(105,1,172),(106,1,178),(132,1,221),(133,1,222),(134,1,223),(135,1,229),(137,1,241),(138,1,242),(139,1,243),(140,1,244),(1,1,251),(2,1,255),(3,1,256),(8,1,262),(9,1,281),(10,1,282),(11,1,283),(12,1,286),(92,1,300),(93,1,301),(94,1,302),(141,1,331),(142,1,332),(143,1,333),(42,1,531),(43,1,532),(44,1,534),(45,1,538),(144,1,700),(145,1,701),(146,1,702),(119,1,1001),(120,1,1002),(121,1,1003),(122,1,1004),(123,1,1005),(78,1,1181),(79,1,1182),(80,1,1183),(81,1,1184),(82,1,1185),(83,1,1186),(84,1,1187),(85,1,1188),(86,1,1189),(109,1,1201),(110,1,1202),(87,1,1231),(88,1,1232),(89,1,1233),(90,1,1234),(91,1,1236),(136,1,1251),(37,1,1321),(102,1,1421),(13,1,2401),(14,1,2402),(15,1,2403),(16,1,2411),(17,1,2412),(18,1,2413),(111,1,2800),(51,2,11),(52,2,61),(53,2,95),(54,2,97),(55,2,111),(56,2,121),(57,2,161),(58,2,251),(59,2,255),(60,2,256),(61,2,262),(62,2,281),(63,2,531),(64,2,2401),(249,3,11),(250,3,12),(251,3,13),(252,3,14),(253,3,15),(254,3,16),(255,3,19),(290,3,21),(291,3,22),(292,3,24),(293,3,25),(294,3,26),(295,3,27),(280,3,31),(281,3,32),(282,3,34),(284,3,41),(285,3,42),(286,3,44),(257,3,61),(258,3,62),(259,3,64),(188,3,71),(189,3,72),(190,3,74),(191,3,75),(192,3,76),(193,3,78),(194,3,79),(215,3,81),(216,3,82),(217,3,84),(218,3,86),(219,3,87),(220,3,88),(221,3,89),(315,3,91),(316,3,92),(317,3,93),(223,3,95),(224,3,96),(225,3,97),(226,3,98),(239,3,101),(240,3,102),(241,3,104),(242,3,109),(201,3,111),(202,3,112),(203,3,113),(204,3,114),(205,3,115),(206,3,116),(207,3,117),(301,3,121),(302,3,122),(303,3,125),(304,3,126),(287,3,141),(288,3,142),(289,3,144),(231,3,161),(232,3,162),(233,3,163),(234,3,164),(227,3,170),(228,3,171),(229,3,172),(276,3,221),(279,3,229),(211,3,241),(212,3,242),(213,3,243),(214,3,244),(167,3,251),(305,3,262),(306,3,281),(307,3,282),(308,3,283),(208,3,331),(297,3,531),(298,3,532),(299,3,534),(236,3,700),(237,3,701),(238,3,702),(310,3,1001),(311,3,1002),(312,3,1003),(313,3,1004),(314,3,1005),(243,3,1101),(244,3,1102),(245,3,1104),(246,3,1109),(261,3,1181),(262,3,1182),(263,3,1183),(264,3,1184),(265,3,1185),(266,3,1186),(267,3,1187),(268,3,1188),(269,3,1189),(247,3,1201),(270,3,1231),(271,3,1232),(272,3,1233),(273,3,1234),(195,3,2401),(196,3,2402),(197,3,2403),(319,3,2411),(199,3,2412),(320,3,2413),(185,3,2500),(187,3,2515);
/*!40000 ALTER TABLE `llx_user_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup`
--

DROP TABLE IF EXISTS `llx_usergroup`;
CREATE TABLE `llx_usergroup` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_usergroup_name` (`nom`,`entity`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_usergroup_rights` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_usergroup` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_usergroup` (`fk_usergroup`,`fk_id`),
  CONSTRAINT `fk_usergroup_rights_fk_usergroup` FOREIGN KEY (`fk_usergroup`) REFERENCES `llx_usergroup` (`rowid`)
) TYPE=InnoDB;

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
CREATE TABLE `llx_usergroup_user` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_usergroup` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_user` (`fk_user`,`fk_usergroup`)
) TYPE=InnoDB;

--
-- Dumping data for table `llx_usergroup_user`
--

LOCK TABLES `llx_usergroup_user` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_usergroup_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-02-14 15:45:10
