-- MySQL dump 10.13  Distrib 5.1.61, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: dolibarrold
-- ------------------------------------------------------
-- Server version	5.1.61-0ubuntu0.11.10.1

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingaccount`
--

LOCK TABLES `llx_accountingaccount` WRITE;
/*!40000 ALTER TABLE `llx_accountingaccount` DISABLE KEYS */;
INSERT INTO `llx_accountingaccount` (`rowid`, `fk_pcg_version`, `pcg_type`, `pcg_subtype`, `label`, `account_number`, `account_parent`) VALUES (1,'PCG99-ABREGE','CAPIT','CAPITAL','Capital','101','1'),(2,'PCG99-ABREGE','CAPIT','XXXXXX','Ecarts de réévaluation','105','1'),(3,'PCG99-ABREGE','CAPIT','XXXXXX','Réserve légale','1061','1'),(4,'PCG99-ABREGE','CAPIT','XXXXXX','Réserves statutaires ou contractuelles','1063','1'),(5,'PCG99-ABREGE','CAPIT','XXXXXX','Réserves réglementées','1064','1'),(6,'PCG99-ABREGE','CAPIT','XXXXXX','Autres réserves','1068','1'),(7,'PCG99-ABREGE','CAPIT','XXXXXX','Compte de l\'exploitant','108','1'),(8,'PCG99-ABREGE','CAPIT','XXXXXX','Résultat de l\'exercice','12','1'),(9,'PCG99-ABREGE','CAPIT','XXXXXX','Amortissements dérogatoires','145','1'),(10,'PCG99-ABREGE','CAPIT','XXXXXX','Provision spéciale de réévaluation','146','1'),(11,'PCG99-ABREGE','CAPIT','XXXXXX','Plus-values réinvesties','147','1'),(12,'PCG99-ABREGE','CAPIT','XXXXXX','Autres provisions réglementées','148','1'),(13,'PCG99-ABREGE','CAPIT','XXXXXX','Provisions pour risques et charges','15','1'),(14,'PCG99-ABREGE','CAPIT','XXXXXX','Emprunts et dettes assimilees','16','1'),(15,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations incorporelles','20','2'),(16,'PCG99-ABREGE','IMMO','XXXXXX','Frais d\'établissement','201','20'),(17,'PCG99-ABREGE','IMMO','XXXXXX','Droit au bail','206','20'),(18,'PCG99-ABREGE','IMMO','XXXXXX','Fonds commercial','207','20'),(19,'PCG99-ABREGE','IMMO','XXXXXX','Autres immobilisations incorporelles','208','20'),(20,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations corporelles','21','2'),(21,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations en cours','23','2'),(22,'PCG99-ABREGE','IMMO','XXXXXX','Autres immobilisations financieres','27','2'),(23,'PCG99-ABREGE','IMMO','XXXXXX','Amortissements des immobilisations incorporelles','280','2'),(24,'PCG99-ABREGE','IMMO','XXXXXX','Amortissements des immobilisations corporelles','281','2'),(25,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des immobilisations incorporelles','290','2'),(26,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des immobilisations corporelles','291','2'),(27,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des autres immobilisations financières','297','2'),(28,'PCG99-ABREGE','STOCK','XXXXXX','Matieres premières','31','3'),(29,'PCG99-ABREGE','STOCK','XXXXXX','Autres approvisionnements','32','3'),(30,'PCG99-ABREGE','STOCK','XXXXXX','En-cours de production de biens','33','3'),(31,'PCG99-ABREGE','STOCK','XXXXXX','En-cours de production de services','34','3'),(32,'PCG99-ABREGE','STOCK','XXXXXX','Stocks de produits','35','3'),(33,'PCG99-ABREGE','STOCK','XXXXXX','Stocks de marchandises','37','3'),(34,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des matières premières','391','3'),(35,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des autres approvisionnements','392','3'),(36,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des en-cours de production de biens','393','3'),(37,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des en-cours de production de services','394','3'),(38,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des stocks de produits','395','3'),(39,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des stocks de marchandises','397','3'),(40,'PCG99-ABREGE','TIERS','SUPPLIER','Fournisseurs et Comptes rattachés','400','4'),(41,'PCG99-ABREGE','TIERS','XXXXXX','Fournisseurs débiteurs','409','4'),(42,'PCG99-ABREGE','TIERS','CUSTOMER','Clients et Comptes rattachés','410','4'),(43,'PCG99-ABREGE','TIERS','XXXXXX','Clients créditeurs','419','4'),(44,'PCG99-ABREGE','TIERS','XXXXXX','Personnel','421','4'),(45,'PCG99-ABREGE','TIERS','XXXXXX','Personnel','428','4'),(46,'PCG99-ABREGE','TIERS','XXXXXX','Sécurité sociale et autres organismes sociaux','43','4'),(47,'PCG99-ABREGE','TIERS','XXXXXX','Etat - impôts sur bénéfice','444','4'),(48,'PCG99-ABREGE','TIERS','XXXXXX','Etat - Taxes sur chiffre affaire','445','4'),(49,'PCG99-ABREGE','TIERS','XXXXXX','Autres impôts, taxes et versements assimilés','447','4'),(50,'PCG99-ABREGE','TIERS','XXXXXX','Groupe et associes','45','4'),(51,'PCG99-ABREGE','TIERS','XXXXXX','Associés','455','45'),(52,'PCG99-ABREGE','TIERS','XXXXXX','Débiteurs divers et créditeurs divers','46','4'),(53,'PCG99-ABREGE','TIERS','XXXXXX','Comptes transitoires ou d\'attente','47','4'),(54,'PCG99-ABREGE','TIERS','XXXXXX','Charges à répartir sur plusieurs exercices','481','4'),(55,'PCG99-ABREGE','TIERS','XXXXXX','Charges constatées d\'avance','486','4'),(56,'PCG99-ABREGE','TIERS','XXXXXX','Produits constatés d\'avance','487','4'),(57,'PCG99-ABREGE','TIERS','XXXXXX','Provisions pour dépréciation des comptes de clients','491','4'),(58,'PCG99-ABREGE','TIERS','XXXXXX','Provisions pour dépréciation des comptes de débiteurs divers','496','4'),(59,'PCG99-ABREGE','FINAN','XXXXXX','Valeurs mobilières de placement','50','5'),(60,'PCG99-ABREGE','FINAN','BANK','Banques, établissements financiers et assimilés','51','5'),(61,'PCG99-ABREGE','FINAN','CASH','Caisse','53','5'),(62,'PCG99-ABREGE','FINAN','XXXXXX','Régies d\'avance et accréditifs','54','5'),(63,'PCG99-ABREGE','FINAN','XXXXXX','Virements internes','58','5'),(64,'PCG99-ABREGE','FINAN','XXXXXX','Provisions pour dépréciation des valeurs mobilières de placement','590','5'),(65,'PCG99-ABREGE','CHARGE','PRODUCT','Achats','60','6'),(66,'PCG99-ABREGE','CHARGE','XXXXXX','Variations des stocks','603','60'),(67,'PCG99-ABREGE','CHARGE','SERVICE','Services extérieurs','61','6'),(68,'PCG99-ABREGE','CHARGE','XXXXXX','Autres services extérieurs','62','6'),(69,'PCG99-ABREGE','CHARGE','XXXXXX','Impôts, taxes et versements assimiles','63','6'),(70,'PCG99-ABREGE','CHARGE','XXXXXX','Rémunérations du personnel','641','6'),(71,'PCG99-ABREGE','CHARGE','XXXXXX','Rémunération du travail de l\'exploitant','644','6'),(72,'PCG99-ABREGE','CHARGE','SOCIAL','Charges de sécurité sociale et de prévoyance','645','6'),(73,'PCG99-ABREGE','CHARGE','XXXXXX','Cotisations sociales personnelles de l\'exploitant','646','6'),(74,'PCG99-ABREGE','CHARGE','XXXXXX','Autres charges de gestion courante','65','6'),(75,'PCG99-ABREGE','CHARGE','XXXXXX','Charges financières','66','6'),(76,'PCG99-ABREGE','CHARGE','XXXXXX','Charges exceptionnelles','67','6'),(77,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','681','6'),(78,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','686','6'),(79,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','687','6'),(80,'PCG99-ABREGE','CHARGE','XXXXXX','Participation des salariés aux résultats','691','6'),(81,'PCG99-ABREGE','CHARGE','XXXXXX','Impôts sur les bénéfices','695','6'),(82,'PCG99-ABREGE','CHARGE','XXXXXX','Imposition forfaitaire annuelle des sociétés','697','6'),(83,'PCG99-ABREGE','CHARGE','XXXXXX','Produits','699','6'),(84,'PCG99-ABREGE','PROD','PRODUCT','Ventes de produits finis','701','7'),(85,'PCG99-ABREGE','PROD','SERVICE','Prestations de services','706','7'),(86,'PCG99-ABREGE','PROD','PRODUCT','Ventes de marchandises','707','7'),(87,'PCG99-ABREGE','PROD','PRODUCT','Produits des activités annexes','708','7'),(88,'PCG99-ABREGE','PROD','XXXXXX','Rabais, remises et ristournes accordés par l\'entreprise','709','7'),(89,'PCG99-ABREGE','PROD','XXXXXX','Variation des stocks','713','7'),(90,'PCG99-ABREGE','PROD','XXXXXX','Production immobilisée','72','7'),(91,'PCG99-ABREGE','PROD','XXXXXX','Produits nets partiels sur opérations à long terme','73','7'),(92,'PCG99-ABREGE','PROD','XXXXXX','Subventions d\'exploitation','74','7'),(93,'PCG99-ABREGE','PROD','XXXXXX','Autres produits de gestion courante','75','7'),(94,'PCG99-ABREGE','PROD','XXXXXX','Jetons de présence et rémunérations d\'administrateurs, gérants,...','753','75'),(95,'PCG99-ABREGE','PROD','XXXXXX','Ristournes perçues des coopératives','754','75'),(96,'PCG99-ABREGE','PROD','XXXXXX','Quotes-parts de résultat sur opérations faites en commun','755','75'),(97,'PCG99-ABREGE','PROD','XXXXXX','Produits financiers','76','7'),(98,'PCG99-ABREGE','PROD','XXXXXX','Produits exceptionnels','77','7'),(99,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur amortissements et provisions','781','7'),(100,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur provisions pour risques','786','7'),(101,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur provisions','787','7'),(102,'PCG99-ABREGE','PROD','XXXXXX','Transferts de charges','79','7');
/*!40000 ALTER TABLE `llx_accountingaccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingdebcred`
--

DROP TABLE IF EXISTS `llx_accountingdebcred`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingdebcred` (
  `fk_transaction` int(11) NOT NULL,
  `fk_account` int(11) NOT NULL,
  `amount` double NOT NULL,
  `direction` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingsystem` (
  `pcg_version` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `label` varchar(128) NOT NULL,
  `datec` varchar(12) NOT NULL,
  `fk_author` varchar(20) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` smallint(6) DEFAULT '0',
  PRIMARY KEY (`pcg_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingsystem`
--

LOCK TABLES `llx_accountingsystem` WRITE;
/*!40000 ALTER TABLE `llx_accountingsystem` DISABLE KEYS */;
INSERT INTO `llx_accountingsystem` (`pcg_version`, `fk_pays`, `label`, `datec`, `fk_author`, `tms`, `active`) VALUES ('PCG99-ABREGE',1,'The simple accountancy french plan','2011-07-29',NULL,'2011-07-28 22:26:11',0);
/*!40000 ALTER TABLE `llx_accountingsystem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingtransaction`
--

DROP TABLE IF EXISTS `llx_accountingtransaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingtransaction` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(128) NOT NULL,
  `datec` date NOT NULL,
  `fk_author` varchar(20) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_facture_fourn` int(11) DEFAULT NULL,
  `fk_paiement` int(11) DEFAULT NULL,
  `fk_paiement_fourn` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingtransaction`
--

LOCK TABLES `llx_accountingtransaction` WRITE;
/*!40000 ALTER TABLE `llx_accountingtransaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_accountingtransaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm`
--

DROP TABLE IF EXISTS `llx_actioncomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_actioncomm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref_ext` varchar(128) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datep` datetime DEFAULT NULL,
  `datep2` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `datea2` datetime DEFAULT NULL,
  `fk_action` int(11) DEFAULT NULL,
  `label` varchar(128) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `fk_project` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_parent` int(11) NOT NULL DEFAULT '0',
  `fk_user_action` int(11) DEFAULT NULL,
  `fk_user_done` int(11) DEFAULT NULL,
  `priority` smallint(6) DEFAULT NULL,
  `fulldayevent` smallint(6) NOT NULL DEFAULT '0',
  `punctual` smallint(6) NOT NULL DEFAULT '1',
  `percent` smallint(6) NOT NULL DEFAULT '0',
  `location` varchar(128) DEFAULT NULL,
  `durationp` double DEFAULT NULL,
  `durationa` double DEFAULT NULL,
  `note` text,
  `fk_element` int(11) DEFAULT NULL,
  `elementtype` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actioncomm_datea` (`datea`),
  KEY `idx_actioncomm_fk_soc` (`fk_soc`),
  KEY `idx_actioncomm_fk_contact` (`fk_contact`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_actioncomm`
--

LOCK TABLES `llx_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_actioncomm` (`id`, `ref_ext`, `entity`, `datep`, `datep2`, `datea`, `datea2`, `fk_action`, `label`, `datec`, `tms`, `fk_user_author`, `fk_user_mod`, `fk_project`, `fk_soc`, `fk_contact`, `fk_parent`, `fk_user_action`, `fk_user_done`, `priority`, `fulldayevent`, `punctual`, `percent`, `location`, `durationp`, `durationa`, `note`, `fk_element`, `elementtype`) VALUES (1,NULL,1,'2010-07-08 14:21:44','2010-07-08 14:21:44',NULL,NULL,50,'Company AAA and Co added into Dolibarr','2010-07-08 14:21:44','2010-07-08 12:21:44',1,NULL,NULL,1,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company AAA and Co added into Dolibarr\nAuthor: admin',NULL,NULL),(2,NULL,1,'2010-07-08 14:23:48','2010-07-08 14:23:48',NULL,NULL,50,'Company Belin SARL added into Dolibarr','2010-07-08 14:23:48','2010-07-08 12:23:48',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Belin SARL added into Dolibarr\nAuthor: admin',NULL,NULL),(3,NULL,1,'2010-07-08 22:42:12','2010-07-08 22:42:12',NULL,NULL,50,'Company Spanish Comp added into Dolibarr','2010-07-08 22:42:12','2010-07-08 20:42:12',1,NULL,NULL,3,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Spanish Comp added into Dolibarr\nAuthor: admin',NULL,NULL),(4,NULL,1,'2010-07-08 22:48:18','2010-07-08 22:48:18',NULL,NULL,50,'Company Prospector Vaalen added into Dolibarr','2010-07-08 22:48:18','2010-07-08 20:48:18',1,NULL,NULL,4,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Prospector Vaalen added into Dolibarr\nAuthor: admin',NULL,NULL),(5,NULL,1,'2010-07-08 23:22:57','2010-07-08 23:22:57',NULL,NULL,50,'Company NoCountry Co added into Dolibarr','2010-07-08 23:22:57','2010-07-08 21:22:57',1,NULL,NULL,5,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company NoCountry Co added into Dolibarr\nAuthor: admin',NULL,NULL),(6,NULL,1,'2010-07-09 00:15:09','2010-07-09 00:15:09',NULL,NULL,50,'Company Swiss customer added into Dolibarr','2010-07-09 00:15:09','2010-07-08 22:15:09',1,NULL,NULL,6,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Swiss customer added into Dolibarr\nAuthor: admin',NULL,NULL),(7,NULL,1,'2010-07-09 01:24:26','2010-07-09 01:24:26',NULL,NULL,50,'Company Generic customer added into Dolibarr','2010-07-09 01:24:26','2010-07-08 23:24:26',1,NULL,NULL,7,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Generic customer added into Dolibarr\nAuthor: admin',NULL,NULL),(8,NULL,1,'2010-07-10 14:54:27','2010-07-10 14:54:27',NULL,NULL,50,'Société Client salon ajoutée dans Dolibarr','2010-07-10 14:54:27','2010-07-10 12:54:27',1,NULL,NULL,8,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Client salon ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(9,NULL,1,'2010-07-10 14:54:44','2010-07-10 14:54:44',NULL,NULL,50,'Société Client salon invidivdu ajoutée dans Doliba','2010-07-10 14:54:44','2010-07-10 12:54:44',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Client salon invidivdu ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(10,NULL,1,'2010-07-10 14:56:10','2010-07-10 14:56:10',NULL,NULL,50,'Facture FA1007-0001 validée dans Dolibarr','2010-07-10 14:56:10','2011-07-18 17:29:22',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 validée dans Dolibarr\nAuteur: admin',1,'invoice'),(11,NULL,1,'2010-07-10 14:58:53','2010-07-10 14:58:53',NULL,NULL,50,'Facture FA1007-0001 validée dans Dolibarr','2010-07-10 14:58:53','2011-07-18 17:29:22',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 validée dans Dolibarr\nAuteur: admin',1,'invoice'),(12,NULL,1,'2010-07-10 15:00:55','2010-07-10 15:00:55',NULL,NULL,50,'Facture FA1007-0001 passée à payée dans Dolibarr','2010-07-10 15:00:55','2011-07-18 17:29:22',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 passée à payée dans Dolibarr\nAuteur: admin',1,'invoice'),(13,NULL,1,'2010-07-10 15:13:08','2010-07-10 15:13:08',NULL,NULL,50,'Société Smith Vick ajoutée dans Dolibarr','2010-07-10 15:13:08','2010-07-10 13:13:08',1,NULL,NULL,10,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Smith Vick ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(14,NULL,1,'2010-07-10 15:21:00','2010-07-10 16:21:00',NULL,NULL,5,'RDV avec mon chef','2010-07-10 15:21:48','2010-07-10 13:21:48',1,NULL,NULL,NULL,NULL,0,1,NULL,0,0,1,0,'',3600,NULL,'',NULL,NULL),(15,NULL,1,'2010-07-10 18:18:16','2010-07-10 18:18:16',NULL,NULL,50,'Contrat CONTRAT1 validé dans Dolibarr','2010-07-10 18:18:16','2010-07-10 16:18:16',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Contrat CONTRAT1 validé dans Dolibarr\nAuteur: admin',NULL,NULL),(16,NULL,1,'2010-07-10 18:35:57','2010-07-10 18:35:57',NULL,NULL,50,'Société Mon client ajoutée dans Dolibarr','2010-07-10 18:35:57','2010-07-10 16:35:57',1,NULL,NULL,11,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Mon client ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(17,NULL,1,'2010-07-11 16:18:08','2010-07-11 16:18:08',NULL,NULL,50,'Société Dupont Alain ajoutée dans Dolibarr','2010-07-11 16:18:08','2010-07-11 14:18:08',1,NULL,NULL,12,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Dupont Alain ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(18,NULL,1,'2010-07-11 17:11:00','2010-07-11 17:17:00',NULL,NULL,5,'Rendez-vous','2010-07-11 17:11:22','2010-07-11 15:11:22',1,NULL,NULL,NULL,NULL,0,1,NULL,0,0,1,0,'gfgdfgdf',360,NULL,'',NULL,NULL),(19,NULL,1,'2010-07-11 17:13:20','2010-07-11 17:13:20',NULL,NULL,50,'Société Vendeur de chips ajoutée dans Dolibarr','2010-07-11 17:13:20','2010-07-11 15:13:20',1,NULL,NULL,13,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Vendeur de chips ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(20,NULL,1,'2010-07-11 17:15:42','2010-07-11 17:15:42',NULL,NULL,50,'Commande CF1007-0001 validée','2010-07-11 17:15:42','2010-07-11 15:15:42',1,NULL,NULL,13,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Commande CF1007-0001 validée\nAuteur: admin',NULL,NULL),(21,NULL,1,'2010-07-11 18:47:33','2010-07-11 18:47:33',NULL,NULL,50,'Commande CF1007-0002 validée','2010-07-11 18:47:33','2010-07-11 16:47:33',1,NULL,NULL,1,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Commande CF1007-0002 validée\nAuteur: admin',NULL,NULL),(22,NULL,1,'2010-07-18 11:36:18','2010-07-18 11:36:18',NULL,NULL,50,'Proposition PR1007-0003 validée','2010-07-18 11:36:18','2011-07-18 17:29:22',1,NULL,NULL,4,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Proposition PR1007-0003 validée\nAuteur: admin',3,'propal'),(23,NULL,1,'2011-07-18 20:49:58','2011-07-18 20:49:58',NULL,NULL,50,'Invoice FA1007-0002 validated in Dolibarr','2011-07-18 20:49:58','2011-07-18 18:49:58',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1007-0002 validated in Dolibarr\nAuthor: admin',2,'invoice'),(24,NULL,1,'2011-07-28 01:37:00',NULL,NULL,NULL,1,'Phone call','2011-07-28 01:37:48','2011-07-27 23:37:48',1,NULL,NULL,NULL,2,0,1,NULL,0,0,1,-1,'',NULL,NULL,'',NULL,NULL),(25,NULL,1,'2011-08-01 02:31:24','2011-08-01 02:31:24',NULL,NULL,50,'Company mmm added into Dolibarr','2011-08-01 02:31:24','2011-08-01 00:31:24',1,NULL,NULL,15,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Company mmm added into Dolibarr\nAuthor: admin',15,'societe'),(26,NULL,1,'2011-08-01 02:31:43','2011-08-01 02:31:43',NULL,NULL,50,'Company ppp added into Dolibarr','2011-08-01 02:31:43','2011-08-01 00:31:43',1,NULL,NULL,16,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Company ppp added into Dolibarr\nAuthor: admin',16,'societe'),(27,NULL,1,'2011-08-01 02:41:26','2011-08-01 02:41:26',NULL,NULL,50,'Company aaa added into Dolibarr','2011-08-01 02:41:26','2011-08-01 00:41:26',1,NULL,NULL,17,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Company aaa added into Dolibarr\nAuthor: admin',17,'societe'),(28,NULL,1,'2011-08-01 03:34:11','2011-08-01 03:34:11',NULL,NULL,50,'Invoice FA1108-0003 validated in Dolibarr','2011-08-01 03:34:11','2011-08-01 01:34:11',1,NULL,NULL,7,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0003 validated in Dolibarr\nAuthor: admin',5,'invoice'),(29,NULL,1,'2011-08-01 03:34:11','2011-08-01 03:34:11',NULL,NULL,50,'Invoice FA1108-0003 validated in Dolibarr','2011-08-01 03:34:11','2011-08-01 01:34:11',1,NULL,NULL,7,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0003 changed to paid in Dolibarr\nAuthor: admin',5,'invoice'),(30,NULL,1,'2011-08-06 20:33:54','2011-08-06 20:33:54',NULL,NULL,50,'Invoice FA1108-0004 validated in Dolibarr','2011-08-06 20:33:54','2011-08-06 18:33:54',1,NULL,NULL,7,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0004 validated in Dolibarr\nAuthor: admin',6,'invoice'),(31,NULL,1,'2011-08-06 20:33:54','2011-08-06 20:33:54',NULL,NULL,50,'Invoice FA1108-0004 validated in Dolibarr','2011-08-06 20:33:54','2011-08-06 18:33:54',1,NULL,NULL,7,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0004 changed to paid in Dolibarr\nAuthor: admin',6,'invoice'),(38,NULL,1,'2011-08-08 02:41:55','2011-08-08 02:41:55',NULL,NULL,50,'Invoice FA1108-0005 validated in Dolibarr','2011-08-08 02:41:55','2011-08-08 00:41:55',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0005 validated in Dolibarr\nAuthor: admin',8,'invoice'),(40,NULL,1,'2011-08-08 02:53:40','2011-08-08 02:53:40',NULL,NULL,50,'Invoice FA1108-0005 changed to paid in Dolibarr','2011-08-08 02:53:40','2011-08-08 00:53:40',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0005 changed to paid in Dolibarr\nAuthor: admin',8,'invoice'),(41,NULL,1,'2011-08-08 02:54:05','2011-08-08 02:54:05',NULL,NULL,50,'Invoice FA1007-0002 changed to paid in Dolibarr','2011-08-08 02:54:05','2011-08-08 00:54:05',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1007-0002 changed to paid in Dolibarr\nAuthor: admin',2,'invoice'),(42,NULL,1,'2011-08-08 02:55:04','2011-08-08 02:55:04',NULL,NULL,50,'Invoice FA1107-0006 validated in Dolibarr','2011-08-08 02:55:04','2011-08-08 00:55:04',1,NULL,NULL,10,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1107-0006 validated in Dolibarr\nAuthor: admin',3,'invoice'),(43,NULL,1,'2011-08-08 02:55:26','2011-08-08 02:55:26',NULL,NULL,50,'Invoice FA1108-0007 validated in Dolibarr','2011-08-08 02:55:26','2011-08-08 00:55:26',1,NULL,NULL,10,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0007 validated in Dolibarr\nAuthor: admin',9,'invoice'),(44,NULL,1,'2011-08-08 02:55:58','2011-08-08 02:55:58',NULL,NULL,50,'Invoice FA1107-0006 changed to paid in Dolibarr','2011-08-08 02:55:58','2011-08-08 00:55:58',1,NULL,NULL,10,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1107-0006 changed to paid in Dolibarr\nAuthor: admin',3,'invoice'),(45,NULL,1,'2011-08-08 03:04:22','2011-08-08 03:04:22',NULL,NULL,50,'Order CO1108-0001 validated','2011-08-08 03:04:22','2011-08-08 01:04:22',1,NULL,NULL,1,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Order CO1108-0001 validated\nAuthor: admin',5,'order'),(46,NULL,1,'2011-08-08 13:59:09','2011-08-08 13:59:09',NULL,NULL,50,'Order CO1107-0002 validated','2011-08-08 13:59:10','2011-08-08 11:59:10',1,NULL,NULL,1,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Order CO1107-0002 validated\nAuthor: admin',1,'order'),(47,NULL,1,'2011-08-08 14:24:18','2011-08-08 14:24:18',NULL,NULL,50,'Proposal PR1007-0001 validated','2011-08-08 14:24:18','2011-08-08 12:24:18',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposal PR1007-0001 validated\nAuthor: admin',1,'propal'),(48,NULL,1,'2011-08-08 14:24:24','2011-08-08 14:24:24',NULL,NULL,50,'Proposal PR1108-0004 validated','2011-08-08 14:24:24','2011-08-08 12:24:24',1,NULL,NULL,17,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposal PR1108-0004 validated\nAuthor: admin',4,'propal'),(49,NULL,1,'2011-08-08 15:04:37','2011-08-08 15:04:37',NULL,NULL,50,'Order CF1108-0003 validated','2011-08-08 15:04:37','2011-08-08 13:04:37',1,NULL,NULL,17,NULL,0,NULL,1,0,0,1,-1,'',NULL,NULL,'Order CF1108-0003 validated\nAuthor: admin',6,'order_supplier');
/*!40000 ALTER TABLE `llx_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent`
--

DROP TABLE IF EXISTS `llx_adherent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
  `civilite` varchar(6) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `fk_adherent_type` int(11) NOT NULL,
  `morphy` varchar(3) NOT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `adresse` text,
  `cp` varchar(30) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
  `pays` int(11) DEFAULT NULL,
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
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_login` (`login`,`entity`),
  UNIQUE KEY `uk_adherent_fk_soc` (`fk_soc`),
  KEY `idx_adherent_fk_adherent_type` (`fk_adherent_type`),
  CONSTRAINT `adherent_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_adherent_adherent_type` FOREIGN KEY (`fk_adherent_type`) REFERENCES `llx_adherent_type` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent`
--

LOCK TABLES `llx_adherent` WRITE;
/*!40000 ALTER TABLE `llx_adherent` DISABLE KEYS */;
INSERT INTO `llx_adherent` (`rowid`, `entity`, `ref_ext`, `civilite`, `nom`, `prenom`, `login`, `pass`, `fk_adherent_type`, `morphy`, `societe`, `fk_soc`, `adresse`, `cp`, `ville`, `fk_departement`, `pays`, `email`, `phone`, `phone_perso`, `phone_mobile`, `naiss`, `photo`, `statut`, `public`, `datefin`, `note`, `datevalid`, `datec`, `tms`, `fk_user_author`, `fk_user_mod`, `fk_user_valid`, `import_key`) VALUES (1,1,NULL,NULL,'Smith','Vick','vsmith','vsx1n8tf',2,'phy',NULL,10,NULL,NULL,NULL,NULL,102,'vsmith@email.com',NULL,NULL,NULL,'1960-07-07',NULL,1,0,NULL,NULL,'2010-07-10 15:12:56','2010-07-08 23:50:00','2010-07-10 13:13:08',1,1,1,NULL),(2,1,NULL,NULL,'Dupont','Alain','adupont','sng2bdf6',2,'phy',NULL,12,NULL,NULL,NULL,NULL,1,'toto@aa.com',NULL,NULL,NULL,'1972-07-08',NULL,1,1,'2013-07-17 00:00:00',NULL,'2010-07-10 15:03:32','2010-07-10 15:03:09','2010-07-18 10:20:33',1,1,1,NULL),(3,1,NULL,NULL,'john','doe','john','8bs6gty5',2,'phy',NULL,NULL,NULL,NULL,NULL,NULL,1,'johndoe@email.com',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2011-07-18 21:28:00','2011-07-18 21:10:09','2011-07-18 19:28:00',1,1,1,NULL),(4,1,NULL,NULL,'smith','smith','Smith','s6hjp10f',2,'phy',NULL,NULL,NULL,NULL,NULL,NULL,11,'smith@email.com',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2011-07-18 21:27:52','2011-07-18 21:27:44','2011-07-18 19:27:52',1,1,1,NULL);
/*!40000 ALTER TABLE `llx_adherent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_extrafields`
--

DROP TABLE IF EXISTS `llx_adherent_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `zzz` varchar(125) DEFAULT NULL,
  `aaa` varchar(255) DEFAULT NULL,
  `sssss` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_adherent_options` (`fk_object`),
  KEY `idx_adherent_extrafields` (`fk_object`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_extrafields`
--

LOCK TABLES `llx_adherent_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_adherent_extrafields` DISABLE KEYS */;
INSERT INTO `llx_adherent_extrafields` (`rowid`, `tms`, `fk_object`, `zzz`, `aaa`, `sssss`, `import_key`) VALUES (2,'2011-06-19 12:03:23',12,'aaa',NULL,NULL,NULL),(3,'2011-06-19 14:19:32',13,NULL,NULL,NULL,NULL),(8,'2011-06-19 18:08:09',7,'zzz',NULL,NULL,NULL),(34,'2011-06-22 10:06:51',14,'moo',NULL,NULL,NULL),(37,'2011-06-22 10:43:55',16,'z',NULL,NULL,NULL),(40,'2011-06-22 10:55:37',17,NULL,NULL,NULL,NULL),(41,'2011-06-22 10:56:07',18,'l',NULL,NULL,NULL),(43,'2011-06-23 07:40:56',19,NULL,NULL,NULL,NULL),(44,'2011-06-26 18:13:20',20,'gdfgdf',NULL,NULL,NULL),(46,'2011-06-26 19:29:23',22,'gdfgdf',NULL,NULL,NULL),(47,'2011-07-03 16:17:56',23,NULL,NULL,NULL,NULL),(48,'2011-07-03 16:21:05',24,NULL,NULL,NULL,NULL),(49,'2011-07-03 16:30:54',25,NULL,NULL,NULL,NULL),(50,'2011-07-03 16:48:13',26,NULL,NULL,NULL,NULL),(51,'2011-07-03 16:51:36',27,NULL,NULL,NULL,NULL),(52,'2011-07-03 16:53:37',28,NULL,NULL,NULL,NULL),(53,'2011-07-03 16:54:24',29,NULL,NULL,NULL,NULL),(54,'2011-07-05 08:21:35',30,NULL,NULL,NULL,NULL),(55,'2011-07-05 08:26:15',31,NULL,NULL,NULL,NULL),(59,'2011-07-13 11:18:55',46,NULL,NULL,NULL,NULL),(61,'2011-07-13 11:50:36',47,NULL,NULL,NULL,NULL),(62,'2011-07-18 19:10:09',3,NULL,NULL,NULL,NULL),(63,'2011-07-18 19:27:44',4,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_adherent_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_options`
--

DROP TABLE IF EXISTS `llx_adherent_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_member` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_adherent_options` (`fk_member`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_options_label` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `type` varchar(8) DEFAULT NULL,
  `size` int(11) DEFAULT '0',
  `pos` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_type` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `libelle` varchar(50) NOT NULL,
  `cotisation` varchar(3) NOT NULL DEFAULT 'yes',
  `vote` varchar(3) NOT NULL DEFAULT 'yes',
  `note` text,
  `mail_valid` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_type_libelle` (`libelle`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_type`
--

LOCK TABLES `llx_adherent_type` WRITE;
/*!40000 ALTER TABLE `llx_adherent_type` DISABLE KEYS */;
INSERT INTO `llx_adherent_type` (`rowid`, `entity`, `tms`, `statut`, `libelle`, `cotisation`, `vote`, `note`, `mail_valid`) VALUES (1,1,'2010-07-08 21:41:55',1,'Board members','1','1','','<br />'),(2,1,'2010-07-08 21:41:43',1,'Standard members','1','0','','<br />');
/*!40000 ALTER TABLE `llx_adherent_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_advanced_extrafields`
--

DROP TABLE IF EXISTS `llx_advanced_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_advanced_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT '1',
  `elementtype` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `label` varchar(64) NOT NULL,
  `format` varchar(8) NOT NULL,
  `fieldsize` int(11) DEFAULT NULL,
  `maxlength` int(11) DEFAULT NULL,
  `options` varchar(255) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_advanced_extrafields_name` (`elementtype`,`entity`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_advanced_extrafields`
--

LOCK TABLES `llx_advanced_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_advanced_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_advanced_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_advanced_extrafields_options`
--

DROP TABLE IF EXISTS `llx_advanced_extrafields_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_advanced_extrafields_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_extrafields` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `rang` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_advanced_extrafields_options_fk_advanced_extrafields` (`fk_extrafields`),
  CONSTRAINT `fk_advanced_extrafields_options_fk_advanced_extrafields` FOREIGN KEY (`fk_extrafields`) REFERENCES `llx_advanced_extrafields` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_advanced_extrafields_options`
--

LOCK TABLES `llx_advanced_extrafields_options` WRITE;
/*!40000 ALTER TABLE `llx_advanced_extrafields_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_advanced_extrafields_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_advanced_extrafields_values`
--

DROP TABLE IF EXISTS `llx_advanced_extrafields_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_advanced_extrafields_values` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `datem` datetime DEFAULT NULL,
  `fk_element` int(11) NOT NULL,
  `fk_extrafields` int(11) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `fk_user_create` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_advanced_extrafields_values_fk_advanced_extrafields` (`fk_extrafields`,`entity`),
  CONSTRAINT `fk_advanced_extrafields_values_fk_advanced_extrafields` FOREIGN KEY (`fk_extrafields`) REFERENCES `llx_advanced_extrafields` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_advanced_extrafields_values`
--

LOCK TABLES `llx_advanced_extrafields_values` WRITE;
/*!40000 ALTER TABLE `llx_advanced_extrafields_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_advanced_extrafields_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank`
--

DROP TABLE IF EXISTS `llx_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `fk_type` varchar(6) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank`
--

LOCK TABLES `llx_bank` WRITE;
/*!40000 ALTER TABLE `llx_bank` DISABLE KEYS */;
INSERT INTO `llx_bank` (`rowid`, `datec`, `datev`, `dateo`, `amount`, `label`, `fk_account`, `fk_user_author`, `fk_user_rappro`, `fk_type`, `num_releve`, `num_chq`, `rappro`, `note`, `fk_bordereau`, `banque`, `emetteur`, `author`) VALUES (1,'2010-07-08 23:56:14','2010-07-08','2010-07-08',2000.00000000,'(Initial balance)',1,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(2,'2010-07-09 00:00:24','2010-07-09','2010-07-09',500.00000000,'(Initial balance)',2,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(3,'2010-07-10 13:33:42','2010-07-10','2010-07-10',0.00000000,'(Solde initial)',3,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(4,'2010-07-10 14:59:41','2010-07-10','2010-07-10',0.02000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,'Client salon invidivdu',NULL),(5,'2011-07-18 20:50:24','2011-07-08','2011-07-08',20.00000000,'(CustomerInvoicePayment)',1,1,NULL,'CB','201107',NULL,1,NULL,0,NULL,NULL,NULL),(6,'2011-07-18 20:50:47','2011-07-08','2011-07-08',10.00000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(8,'2011-08-01 03:34:11','2011-08-01','2011-08-01',5.63000000,'(CustomerInvoicePayment)',1,1,NULL,'CB',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(10,'2011-08-05 20:38:30','2011-08-05','2011-08-05',-1.00000000,'(SocialContributionPayment)',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(11,'2011-08-05 20:38:44','2011-08-05','2011-08-05',-9.00000000,'(SocialContributionPayment)',1,1,NULL,'TIP',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(12,'2011-08-05 23:11:37','2011-08-05','2011-08-05',-10.00000000,'(SocialContributionPayment)',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(13,'2011-08-06 20:33:54','2011-08-06','2011-08-06',5.98000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(14,'2011-08-08 02:53:40','2011-08-08','2011-08-08',26.10000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(15,'2011-08-08 02:55:58','2011-08-08','2011-08-08',26.96000000,'(CustomerInvoicePayment)',1,1,NULL,'TIP',NULL,NULL,0,NULL,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_account`
--

DROP TABLE IF EXISTS `llx_bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_account` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `fk_departement` int(11) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_account`
--

LOCK TABLES `llx_bank_account` WRITE;
/*!40000 ALTER TABLE `llx_bank_account` DISABLE KEYS */;
INSERT INTO `llx_bank_account` (`rowid`, `datec`, `tms`, `ref`, `label`, `entity`, `bank`, `code_banque`, `code_guichet`, `number`, `cle_rib`, `bic`, `iban_prefix`, `country_iban`, `cle_iban`, `domiciliation`, `fk_departement`, `fk_pays`, `proprio`, `adresse_proprio`, `courant`, `clos`, `rappro`, `url`, `account_number`, `currency_code`, `min_allowed`, `min_desired`, `comment`) VALUES (1,'2010-07-08 23:56:14','2010-07-08 21:56:14','SWIBAC','Swiss bank account',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,181,6,NULL,NULL,1,0,1,NULL,'','EUR',1500,1500,'<br />'),(2,'2010-07-09 00:00:24','2010-07-08 22:00:24','SWIBAC2','Swiss bank account 2',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,179,6,NULL,NULL,1,1,1,NULL,'','EUR',200,400,'<br />'),(3,'2010-07-10 13:33:42','2010-07-10 11:33:42','ACCOUNTCASH','Account for cash',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,1,NULL,NULL,2,0,1,NULL,'','EUR',0,0,'<br />');
/*!40000 ALTER TABLE `llx_bank_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_categ`
--

DROP TABLE IF EXISTS `llx_bank_categ`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_categ` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_categ`
--

LOCK TABLES `llx_bank_categ` WRITE;
/*!40000 ALTER TABLE `llx_bank_categ` DISABLE KEYS */;
INSERT INTO `llx_bank_categ` (`rowid`, `label`, `entity`) VALUES (1,'Bank category one',1),(2,'Bank category two',1);
/*!40000 ALTER TABLE `llx_bank_categ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_class`
--

DROP TABLE IF EXISTS `llx_bank_class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_class` (
  `lineid` int(11) NOT NULL,
  `fk_categ` int(11) NOT NULL,
  UNIQUE KEY `uk_bank_class_lineid` (`lineid`,`fk_categ`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_url` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_bank` int(11) DEFAULT NULL,
  `url_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bank_url` (`fk_bank`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_url`
--

LOCK TABLES `llx_bank_url` WRITE;
/*!40000 ALTER TABLE `llx_bank_url` DISABLE KEYS */;
INSERT INTO `llx_bank_url` (`rowid`, `fk_bank`, `url_id`, `url`, `label`, `type`) VALUES (1,4,1,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(2,4,9,'/dolibarrnew/compta/fiche.php?socid=','Client salon invidivdu','company'),(3,5,2,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(4,5,2,'/comm/fiche.php?socid=','Belin SARL','company'),(5,6,3,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(6,6,2,'/comm/fiche.php?socid=','Belin SARL','company'),(9,8,5,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(10,8,7,'/comm/fiche.php?socid=','Generic customer','company'),(17,12,4,'/compta/payment_sc/fiche.php?id=','(paiement)','payment_sc'),(18,12,4,'/compta/charges.php?id=','Assurance Chomage (fff)','sc'),(19,13,6,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(20,13,7,'/dolibarrnew/comm/fiche.php?socid=','Generic customer','company'),(21,14,8,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(22,14,2,'/comm/fiche.php?socid=','Belin SARL','company'),(23,15,9,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(24,15,10,'/comm/fiche.php?socid=','Smith Vick','company');
/*!40000 ALTER TABLE `llx_bank_url` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bookmark`
--

DROP TABLE IF EXISTS `llx_bookmark`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bookmark` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `dateb` datetime DEFAULT NULL,
  `url` varchar(128) NOT NULL,
  `target` varchar(16) DEFAULT NULL,
  `title` varchar(64) DEFAULT NULL,
  `favicon` varchar(24) DEFAULT NULL,
  `position` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bookmark_url` (`fk_user`,`url`),
  UNIQUE KEY `uk_bookmark_title` (`fk_user`,`title`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bookmark`
--

LOCK TABLES `llx_bookmark` WRITE;
/*!40000 ALTER TABLE `llx_bookmark` DISABLE KEYS */;
INSERT INTO `llx_bookmark` (`rowid`, `fk_soc`, `fk_user`, `dateb`, `url`, `target`, `title`, `favicon`, `position`) VALUES (1,NULL,0,'2010-07-09 01:29:03','http://wiki.dolibarr.org','1','Online documentation','none',1),(2,NULL,0,'2010-07-09 01:30:15','http://www.dolibarr.org','1','Official portal','none',2),(3,NULL,0,'2010-07-09 01:30:53','http://www.dolistore.com','1','DoliStore','none',10),(4,NULL,0,'2010-07-09 01:31:35','http://asso.dolibarr.org/index.php/Main_Page','1','The foundation','none',0);
/*!40000 ALTER TABLE `llx_bookmark` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bordereau_cheque`
--

DROP TABLE IF EXISTS `llx_bordereau_cheque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_boxes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `box_id` int(11) NOT NULL,
  `position` smallint(6) NOT NULL,
  `box_order` varchar(3) NOT NULL,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `maxline` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes` (`box_id`,`position`,`fk_user`),
  KEY `idx_boxes_boxid` (`box_id`),
  KEY `idx_boxes_fk_user` (`fk_user`),
  CONSTRAINT `fk_boxes_box_id` FOREIGN KEY (`box_id`) REFERENCES `llx_boxes_def` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_boxes`
--

LOCK TABLES `llx_boxes` WRITE;
/*!40000 ALTER TABLE `llx_boxes` DISABLE KEYS */;
INSERT INTO `llx_boxes` (`rowid`, `box_id`, `position`, `box_order`, `fk_user`, `maxline`) VALUES (12,20,0,'A03',0,NULL),(73,21,0,'A01',0,NULL),(80,21,0,'A01',1,NULL),(81,20,0,'B01',1,NULL);
/*!40000 ALTER TABLE `llx_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_boxes_def`
--

DROP TABLE IF EXISTS `llx_boxes_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_boxes_def` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(200) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `note` varchar(130) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes_def` (`file`,`entity`,`note`)
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_boxes_def`
--

LOCK TABLES `llx_boxes_def` WRITE;
/*!40000 ALTER TABLE `llx_boxes_def` DISABLE KEYS */;
INSERT INTO `llx_boxes_def` (`rowid`, `file`, `entity`, `tms`, `note`) VALUES (20,'box_actions.php',1,'2010-07-08 11:29:29',NULL),(21,'box_bookmarks.php',1,'2010-07-08 11:30:03',NULL),(87,'box_produits.php',1,'2011-07-18 17:30:24',NULL),(182,'box_clients.php',1,'2011-08-05 20:40:20',NULL),(183,'box_prospect.php',1,'2011-08-05 20:40:20',NULL),(184,'box_contacts.php',1,'2011-08-05 20:40:20',NULL),(185,'box_propales.php',1,'2011-08-05 20:40:22',NULL),(186,'box_commandes.php',1,'2011-08-05 20:40:25',NULL),(187,'box_contracts.php',1,'2011-08-05 20:40:27',NULL),(188,'box_services_vendus.php',1,'2011-08-05 20:40:27',NULL),(191,'box_factures_imp.php',1,'2011-08-05 20:40:33',NULL),(192,'box_factures.php',1,'2011-08-05 20:40:33',NULL),(193,'box_comptes.php',1,'2011-08-05 20:40:34',NULL),(197,'box_fournisseurs.php',1,'2011-08-05 20:40:42',NULL),(198,'box_factures_fourn_imp.php',1,'2011-08-05 20:40:42',NULL),(199,'box_factures_fourn.php',1,'2011-08-05 20:40:42',NULL);
/*!40000 ALTER TABLE `llx_boxes_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bt_namemap`
--

DROP TABLE IF EXISTS `llx_bt_namemap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bt_namemap` (
  `info_hash` char(40) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `url` varchar(250) NOT NULL DEFAULT '',
  `size` bigint(20) unsigned NOT NULL,
  `pubDate` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`info_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bt_namemap`
--

LOCK TABLES `llx_bt_namemap` WRITE;
/*!40000 ALTER TABLE `llx_bt_namemap` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bt_namemap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bt_speedlimit`
--

DROP TABLE IF EXISTS `llx_bt_speedlimit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bt_speedlimit` (
  `uploaded` bigint(25) NOT NULL DEFAULT '0',
  `total_uploaded` bigint(30) NOT NULL DEFAULT '0',
  `started` bigint(25) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bt_speedlimit`
--

LOCK TABLES `llx_bt_speedlimit` WRITE;
/*!40000 ALTER TABLE `llx_bt_speedlimit` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bt_speedlimit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bt_summary`
--

DROP TABLE IF EXISTS `llx_bt_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bt_summary` (
  `info_hash` char(40) NOT NULL DEFAULT '',
  `dlbytes` bigint(20) unsigned NOT NULL DEFAULT '0',
  `seeds` int(10) unsigned NOT NULL DEFAULT '0',
  `leechers` int(10) unsigned NOT NULL DEFAULT '0',
  `finished` int(10) unsigned NOT NULL DEFAULT '0',
  `lastcycle` int(10) unsigned NOT NULL DEFAULT '0',
  `lastSpeedCycle` int(10) unsigned NOT NULL DEFAULT '0',
  `speed` bigint(20) unsigned NOT NULL DEFAULT '0',
  `piecelength` int(11) NOT NULL DEFAULT '-1',
  `numpieces` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bt_summary`
--

LOCK TABLES `llx_bt_summary` WRITE;
/*!40000 ALTER TABLE `llx_bt_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bt_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bt_timestamps`
--

DROP TABLE IF EXISTS `llx_bt_timestamps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bt_timestamps` (
  `info_hash` char(40) NOT NULL,
  `sequence` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bytes` bigint(20) unsigned NOT NULL,
  `delta` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`sequence`),
  KEY `sorting` (`info_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bt_timestamps`
--

LOCK TABLES `llx_bt_timestamps` WRITE;
/*!40000 ALTER TABLE `llx_bt_timestamps` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bt_timestamps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bt_webseedfiles`
--

DROP TABLE IF EXISTS `llx_bt_webseedfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bt_webseedfiles` (
  `info_hash` char(40) DEFAULT NULL,
  `filename` char(250) NOT NULL DEFAULT '',
  `startpiece` int(11) NOT NULL DEFAULT '0',
  `endpiece` int(11) NOT NULL DEFAULT '0',
  `startpieceoffset` int(11) NOT NULL DEFAULT '0',
  `fileorder` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `fileseq` (`info_hash`,`fileorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bt_webseedfiles`
--

LOCK TABLES `llx_bt_webseedfiles` WRITE;
/*!40000 ALTER TABLE `llx_bt_webseedfiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bt_webseedfiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_action_trigger`
--

DROP TABLE IF EXISTS `llx_c_action_trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_action_trigger` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `label` varchar(128) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `elementtype` varchar(16) NOT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_action_trigger_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_action_trigger`
--

LOCK TABLES `llx_c_action_trigger` WRITE;
/*!40000 ALTER TABLE `llx_c_action_trigger` DISABLE KEYS */;
INSERT INTO `llx_c_action_trigger` (`rowid`, `code`, `label`, `description`, `elementtype`, `rang`) VALUES (1,'FICHEINTER_VALIDATE','Validation fiche intervention','Executed when a intervention is validated','ficheinter',18),(2,'BILL_VALIDATE','Validation facture client','Executed when a customer invoice is approved','facture',6),(3,'ORDER_SUPPLIER_APPROVE','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier',11),(4,'ORDER_SUPPLIER_REFUSE','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier',12),(5,'ORDER_VALIDATE','Validation commande client','Executed when a customer order is validated','commande',4),(6,'PROPAL_VALIDATE','Validation proposition client','Executed when a commercial proposal is validated','propal',2),(7,'WITHDRAW_TRANSMIT','Transmission prélèvement','Executed when a withdrawal is transmited','withdraw',25),(8,'WITHDRAW_CREDIT','Créditer prélèvement','Executed when a withdrawal is credited','withdraw',26),(9,'WITHDRAW_EMIT','Emission prélèvement','Executed when a withdrawal is emited','withdraw',27),(10,'COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1),(11,'CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',17),(12,'PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3),(13,'ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5),(14,'BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7),(15,'BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8),(16,'BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9),(17,'ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',10),(18,'ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',13),(19,'BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',14),(20,'BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',15),(21,'BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',16),(22,'SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',19),(23,'SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',20),(24,'MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',21),(25,'MEMBER_SUBSCRIPTION','Member subscribed','Executed when a member is subscribed','member',22),(26,'MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',23),(27,'MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',24);
/*!40000 ALTER TABLE `llx_c_action_trigger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_actioncomm`
--

DROP TABLE IF EXISTS `llx_c_actioncomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_actioncomm` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'system',
  `libelle` varchar(48) NOT NULL,
  `module` varchar(16) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `todo` tinyint(4) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_actioncomm`
--

LOCK TABLES `llx_c_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_c_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1,'AC_TEL','system','Phone call',NULL,1,NULL,2),(2,'AC_FAX','system','Send Fax',NULL,1,NULL,3),(3,'AC_PROP','system','Send commercial proposal by email','propal',1,NULL,10),(4,'AC_EMAIL','system','Send Email',NULL,1,NULL,4),(5,'AC_RDV','system','Rendez-vous',NULL,1,NULL,1),(8,'AC_COM','system','Send customer order by email','order',1,NULL,8),(9,'AC_FAC','system','Send customer invoice by email','invoice',1,NULL,6),(10,'AC_SHIP','system','Send shipping by email','shipping',1,NULL,11),(30,'AC_SUP_ORD','system','Send supplier order by email','order_supplier',1,NULL,9),(31,'AC_SUP_INV','system','Send supplier invoice by email','invoice_supplier',1,NULL,7),(50,'AC_OTH','system','Other',NULL,1,NULL,5);
/*!40000 ALTER TABLE `llx_c_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_availability`
--

DROP TABLE IF EXISTS `llx_c_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_availability` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_availability` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_availability`
--

LOCK TABLES `llx_c_availability` WRITE;
/*!40000 ALTER TABLE `llx_c_availability` DISABLE KEYS */;
INSERT INTO `llx_c_availability` (`rowid`, `code`, `label`, `active`) VALUES (1,'AV_NOW','Immediate',1),(2,'AV_1W','1 week',1),(3,'AV_2W','2 weeks',1),(4,'AV_3W','3 weeks',1);
/*!40000 ALTER TABLE `llx_c_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_barcode_type`
--

DROP TABLE IF EXISTS `llx_c_barcode_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_barcode_type` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `libelle` varchar(50) NOT NULL,
  `coder` varchar(16) NOT NULL,
  `example` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_barcode_type`
--

LOCK TABLES `llx_c_barcode_type` WRITE;
/*!40000 ALTER TABLE `llx_c_barcode_type` DISABLE KEYS */;
INSERT INTO `llx_c_barcode_type` (`rowid`, `code`, `entity`, `libelle`, `coder`, `example`) VALUES (1,'EAN8',1,'EAN8','0','1234567'),(2,'EAN13',1,'EAN13','0','123456789012'),(3,'UPC',1,'UPC','0','123456789012'),(4,'ISBN',1,'ISBN','0','123456789'),(5,'C39',1,'Code 39','0','1234567890'),(6,'C128',1,'Code 128','0','ABCD1234567890');
/*!40000 ALTER TABLE `llx_c_barcode_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_chargesociales`
--

DROP TABLE IF EXISTS `llx_c_chargesociales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_chargesociales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(80) DEFAULT NULL,
  `deductible` smallint(6) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `code` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_chargesociales`
--

LOCK TABLES `llx_c_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_c_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_c_chargesociales` (`id`, `libelle`, `deductible`, `active`, `code`, `fk_pays`, `module`) VALUES (1,'Allocations familiales',1,1,'TAXFAM',1,NULL),(2,'CSG Deductible',1,1,'TAXCSGD',1,NULL),(3,'CSG/CRDS NON Deductible',0,1,'TAXCSGND',1,NULL),(10,'Taxe apprentissage',0,1,'TAXAPP',1,NULL),(11,'Taxe professionnelle',0,1,'TAXPRO',1,NULL),(12,'Cotisation foncière des entreprises',0,1,'TAXCFE',1,NULL),(13,'Cotisation sur la valeur ajoutée des entreprises',0,1,'TAXCVAE',1,NULL),(20,'Impots locaux/fonciers',0,1,'TAXFON',1,NULL),(25,'Impots revenus',0,1,'TAXREV',1,NULL),(30,'Assurance Sante',0,1,'TAXSECU',1,NULL),(40,'Mutuelle',0,1,'TAXMUT',1,NULL),(50,'Assurance vieillesse',0,1,'TAXRET',1,NULL),(60,'Assurance Chomage',0,1,'TAXCHOM',1,NULL),(201,'ONSS',1,1,'TAXBEONSS',2,NULL),(210,'Precompte professionnel',1,1,'TAXBEPREPRO',2,NULL),(220,'Prime d\'existence',1,1,'TAXBEPRIEXI',2,NULL),(230,'Precompte immobilier',1,1,'TAXBEPREIMMO',2,NULL);
/*!40000 ALTER TABLE `llx_c_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_civilite`
--

DROP TABLE IF EXISTS `llx_c_civilite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_civilite` (
  `rowid` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `civilite` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_civilite`
--

LOCK TABLES `llx_c_civilite` WRITE;
/*!40000 ALTER TABLE `llx_c_civilite` DISABLE KEYS */;
INSERT INTO `llx_c_civilite` (`rowid`, `code`, `civilite`, `active`, `module`) VALUES (1,'MME','Madame',1,NULL),(3,'MR','Monsieur',1,NULL),(5,'MLE','Mademoiselle',1,NULL),(7,'MTRE','Maître',1,NULL),(8,'DR','Docteur',1,NULL);
/*!40000 ALTER TABLE `llx_c_civilite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_currencies`
--

DROP TABLE IF EXISTS `llx_c_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_currencies` (
  `code_iso` varchar(3) NOT NULL,
  `label` varchar(64) NOT NULL,
  `unicode` varchar(32) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_currencies`
--

LOCK TABLES `llx_c_currencies` WRITE;
/*!40000 ALTER TABLE `llx_c_currencies` DISABLE KEYS */;
INSERT INTO `llx_c_currencies` (`code_iso`, `label`, `unicode`, `active`) VALUES ('AED','United Arab Emirates Dirham',NULL,1),('AFN','Afghanistan Afghani','[1547]',1),('ALL','Albania Lek','[76,101,107]',1),('ANG','Netherlands Antilles Guilder','[402]',1),('ARP','Pesos argentins',NULL,0),('ARS','Argentino Peso','[36]',1),('ATS','Shiliing autrichiens',NULL,0),('AUD','Australia Dollar','[36]',1),('AWG','Aruba Guilder','[402]',1),('AZN','Azerbaijan New Manat','[1084,1072,1085]',1),('BAM','Bosnia and Herzegovina Convertible Marka','[75,77]',1),('BBD','Barbados Dollar','[36]',1),('BEF','Francs belges',NULL,0),('BGN','Bulgaria Lev','[1083,1074]',1),('BMD','Bermuda Dollar','[36]',1),('BND','Brunei Darussalam Dollar','[36]',1),('BOB','Bolivia Boliviano','[36,98]',1),('BRL','Brazil Real','[82,36]',1),('BSD','Bahamas Dollar','[36]',1),('BWP','Botswana Pula','[80]',1),('BYR','Belarus Ruble','[112,46]',1),('BZD','Belize Dollar','[66,90,36]',1),('CAD','Canada Dollar','[36]',1),('CHF','Switzerland Franc','[67,72,70]',1),('CLP','Chile Peso','[36]',1),('CNY','China Yuan Renminbi','[165]',1),('COP','Colombia Peso','[36]',1),('CRC','Costa Rica Colon','[8353]',1),('CUP','Cuba Peso','[8369]',1),('CZK','Czech Republic Koruna','[75,269]',1),('DEM','Deutsch mark',NULL,0),('DKK','Denmark Krone','[107,114]',1),('DOP','Dominican Republic Peso','[82,68,36]',1),('DZD','Algeria Dinar',NULL,1),('EEK','Estonia Kroon','[107,114]',1),('EGP','Egypt Pound','[163]',1),('ESP','Pesete',NULL,0),('EUR','Euro Member Countries','[8364]',1),('FIM','Mark finlandais',NULL,0),('FJD','Fiji Dollar','[36]',1),('FKP','Falkland Islands (Malvinas) Pound','[163]',1),('FRF','Francs francais',NULL,0),('GBP','United Kingdom Pound','[163]',1),('GGP','Guernsey Pound','[163]',1),('GHC','Ghana Cedis','[162]',1),('GIP','Gibraltar Pound','[163]',1),('GRD','Drachme (grece)',NULL,0),('GTQ','Guatemala Quetzal','[81]',1),('GYD','Guyana Dollar','[36]',1),('HKD','Hong Kong Dollar','[36]',1),('HNL','Honduras Lempira','[76]',1),('HRK','Croatia Kuna','[107,110]',1),('HUF','Hungary Forint','[70,116]',1),('IDR','Indonesia Rupiah','[82,112]',1),('IEP','Livres irlandaises',NULL,0),('ILS','Israel Shekel','[8362]',1),('IMP','Isle of Man Pound','[163]',1),('INR','India Rupee',NULL,1),('IRR','Iran Rial','[65020]',1),('ISK','Iceland Krona','[107,114]',1),('ITL','Lires',NULL,0),('JEP','Jersey Pound','[163]',1),('JMD','Jamaica Dollar','[74,36]',1),('JPY','Japan Yen','[165]',1),('KGS','Kyrgyzstan Som','[1083,1074]',1),('KHR','Cambodia Riel','[6107]',1),('KPW','Korea (North) Won','[8361]',1),('KRW','Korea (South) Won','[8361]',1),('KYD','Cayman Islands Dollar','[36]',1),('KZT','Kazakhstan Tenge','[1083,1074]',1),('LAK','Laos Kip','[8365]',1),('LBP','Lebanon Pound','[163]',1),('LKR','Sri Lanka Rupee','[8360]',1),('LRD','Liberia Dollar','[36]',1),('LTL','Lithuania Litas','[76,116]',1),('LUF','Francs luxembourgeois',NULL,0),('LVL','Latvia Lat','[76,115]',1),('MAD','Morocco Dirham',NULL,1),('MKD','Macedonia Denar','[1076,1077,1085]',1),('MNT','Mongolia Tughrik','[8366]',1),('MRO','Mauritania Ouguiya',NULL,1),('MUR','Mauritius Rupee','[8360]',1),('MXN','Mexico Peso','[36]',1),('MXP','Pesos Mexicans',NULL,0),('MYR','Malaysia Ringgit','[82,77]',1),('MZN','Mozambique Metical','[77,84]',1),('NAD','Namibia Dollar','[36]',1),('NGN','Nigeria Naira','[8358]',1),('NIO','Nicaragua Cordoba','[67,36]',1),('NLG','Florins',NULL,0),('NOK','Norway Krone','[107,114]',1),('NPR','Nepal Rupee','[8360]',1),('NZD','New Zealand Dollar','[36]',1),('OMR','Oman Rial','[65020]',1),('PAB','Panama Balboa','[66,47,46]',1),('PEN','Peru Nuevo Sol','[83,47,46]',1),('PHP','Philippines Peso','[8369]',1),('PKR','Pakistan Rupee','[8360]',1),('PLN','Poland Zloty','[122,322]',1),('PTE','Escudos',NULL,0),('PYG','Paraguay Guarani','[71,115]',1),('QAR','Qatar Riyal','[65020]',1),('RON','Romania New Leu','[108,101,105]',1),('RSD','Serbia Dinar','[1044,1080,1085,46]',1),('RUB','Russia Ruble','[1088,1091,1073]',1),('SAR','Saudi Arabia Riyal','[65020]',1),('SBD','Solomon Islands Dollar','[36]',1),('SCR','Seychelles Rupee','[8360]',1),('SEK','Sweden Krona','[107,114]',1),('SGD','Singapore Dollar','[36]',1),('SHP','Saint Helena Pound','[163]',1),('SKK','Couronnes slovaques',NULL,0),('SOS','Somalia Shilling','[83]',1),('SRD','Suriname Dollar','[36]',1),('SUR','Rouble',NULL,0),('SVC','El Salvador Colon','[36]',1),('SYP','Syria Pound','[163]',1),('THB','Thailand Baht','[3647]',1),('TND','Tunisia Dinar',NULL,1),('TRL','Turkey Lira','[84,76]',1),('TRY','Turkey Lira','[8356]',1),('TTD','Trinidad and Tobago Dollar','[84,84,36]',1),('TVD','Tuvalu Dollar','[36]',1),('TWD','Taiwan New Dollar','[78,84,36]',1),('UAH','Ukraine Hryvna','[8372]',1),('USD','United States Dollar','[36]',1),('UYU','Uruguay Peso','[36,85]',1),('UZS','Uzbekistan Som','[1083,1074]',1),('VEF','Venezuela Bolivar Fuerte','[66,115]',1),('VND','Viet Nam Dong','[8363]',1),('XAF','Communaute Financiere Africaine (BEAC) CFA Franc',NULL,1),('XCD','East Caribbean Dollar','[36]',1),('XEU','Ecus',NULL,0),('XOF','Communaute Financiere Africaine (BCEAO) Franc',NULL,1),('YER','Yemen Rial','[65020]',1),('ZAR','South Africa Rand','[82]',1),('ZWD','Zimbabwe Dollar','[90,36]',1);
/*!40000 ALTER TABLE `llx_c_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_departements`
--

DROP TABLE IF EXISTS `llx_c_departements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_departements` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code_departement` varchar(6) NOT NULL,
  `fk_region` int(11) DEFAULT NULL,
  `cheflieu` varchar(50) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `ncc` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_departements` (`code_departement`,`fk_region`),
  KEY `idx_departements_fk_region` (`fk_region`)
) ENGINE=InnoDB AUTO_INCREMENT=1061 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_departements`
--

LOCK TABLES `llx_c_departements` WRITE;
/*!40000 ALTER TABLE `llx_c_departements` DISABLE KEYS */;
INSERT INTO `llx_c_departements` (`rowid`, `code_departement`, `fk_region`, `cheflieu`, `tncc`, `ncc`, `nom`, `active`) VALUES (1,'0',0,'0',0,'-','-',1),(2,'01',82,'01053',5,'AIN','Ain',1),(3,'02',22,'02408',5,'AISNE','Aisne',1),(4,'03',83,'03190',5,'ALLIER','Allier',1),(5,'04',93,'04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence',1),(6,'05',93,'05061',4,'HAUTES-ALPES','Hautes-Alpes',1),(7,'06',93,'06088',4,'ALPES-MARITIMES','Alpes-Maritimes',1),(8,'07',82,'07186',5,'ARDECHE','Ardèche',1),(9,'08',21,'08105',4,'ARDENNES','Ardennes',1),(10,'09',73,'09122',5,'ARIEGE','Ariège',1),(11,'10',21,'10387',5,'AUBE','Aube',1),(12,'11',91,'11069',5,'AUDE','Aude',1),(13,'12',73,'12202',5,'AVEYRON','Aveyron',1),(14,'13',93,'13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône',1),(15,'14',25,'14118',2,'CALVADOS','Calvados',1),(16,'15',83,'15014',2,'CANTAL','Cantal',1),(17,'16',54,'16015',3,'CHARENTE','Charente',1),(18,'17',54,'17300',3,'CHARENTE-MARITIME','Charente-Maritime',1),(19,'18',24,'18033',2,'CHER','Cher',1),(20,'19',74,'19272',3,'CORREZE','Corrèze',1),(21,'2A',94,'2A004',3,'CORSE-DU-SUD','Corse-du-Sud',1),(22,'2B',94,'2B033',3,'HAUTE-CORSE','Haute-Corse',1),(23,'21',26,'21231',3,'COTE-D\'OR','Côte-d\'Or',1),(24,'22',53,'22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor',1),(25,'23',74,'23096',3,'CREUSE','Creuse',1),(26,'24',72,'24322',3,'DORDOGNE','Dordogne',1),(27,'25',43,'25056',2,'DOUBS','Doubs',1),(28,'26',82,'26362',3,'DROME','Drôme',1),(29,'27',23,'27229',5,'EURE','Eure',1),(30,'28',24,'28085',1,'EURE-ET-LOIR','Eure-et-Loir',1),(31,'29',53,'29232',2,'FINISTERE','Finistère',1),(32,'30',91,'30189',2,'GARD','Gard',1),(33,'31',73,'31555',3,'HAUTE-GARONNE','Haute-Garonne',1),(34,'32',73,'32013',2,'GERS','Gers',1),(35,'33',72,'33063',3,'GIRONDE','Gironde',1),(36,'34',91,'34172',5,'HERAULT','Hérault',1),(37,'35',53,'35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine',1),(38,'36',24,'36044',5,'INDRE','Indre',1),(39,'37',24,'37261',1,'INDRE-ET-LOIRE','Indre-et-Loire',1),(40,'38',82,'38185',5,'ISERE','Isère',1),(41,'39',43,'39300',2,'JURA','Jura',1),(42,'40',72,'40192',4,'LANDES','Landes',1),(43,'41',24,'41018',0,'LOIR-ET-CHER','Loir-et-Cher',1),(44,'42',82,'42218',3,'LOIRE','Loire',1),(45,'43',83,'43157',3,'HAUTE-LOIRE','Haute-Loire',1),(46,'44',52,'44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique',1),(47,'45',24,'45234',2,'LOIRET','Loiret',1),(48,'46',73,'46042',2,'LOT','Lot',1),(49,'47',72,'47001',0,'LOT-ET-GARONNE','Lot-et-Garonne',1),(50,'48',91,'48095',3,'LOZERE','Lozère',1),(51,'49',52,'49007',0,'MAINE-ET-LOIRE','Maine-et-Loire',1),(52,'50',25,'50502',3,'MANCHE','Manche',1),(53,'51',21,'51108',3,'MARNE','Marne',1),(54,'52',21,'52121',3,'HAUTE-MARNE','Haute-Marne',1),(55,'53',52,'53130',3,'MAYENNE','Mayenne',1),(56,'54',41,'54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle',1),(57,'55',41,'55029',3,'MEUSE','Meuse',1),(58,'56',53,'56260',2,'MORBIHAN','Morbihan',1),(59,'57',41,'57463',3,'MOSELLE','Moselle',1),(60,'58',26,'58194',3,'NIEVRE','Nièvre',1),(61,'59',31,'59350',2,'NORD','Nord',1),(62,'60',22,'60057',5,'OISE','Oise',1),(63,'61',25,'61001',5,'ORNE','Orne',1),(64,'62',31,'62041',2,'PAS-DE-CALAIS','Pas-de-Calais',1),(65,'63',83,'63113',2,'PUY-DE-DOME','Puy-de-Dôme',1),(66,'64',72,'64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques',1),(67,'65',73,'65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées',1),(68,'66',91,'66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales',1),(69,'67',42,'67482',2,'BAS-RHIN','Bas-Rhin',1),(70,'68',42,'68066',2,'HAUT-RHIN','Haut-Rhin',1),(71,'69',82,'69123',2,'RHONE','Rhône',1),(72,'70',43,'70550',3,'HAUTE-SAONE','Haute-Saône',1),(73,'71',26,'71270',0,'SAONE-ET-LOIRE','Saône-et-Loire',1),(74,'72',52,'72181',3,'SARTHE','Sarthe',1),(75,'73',82,'73065',3,'SAVOIE','Savoie',1),(76,'74',82,'74010',3,'HAUTE-SAVOIE','Haute-Savoie',1),(77,'75',11,'75056',0,'PARIS','Paris',1),(78,'76',23,'76540',3,'SEINE-MARITIME','Seine-Maritime',1),(79,'77',11,'77288',0,'SEINE-ET-MARNE','Seine-et-Marne',1),(80,'78',11,'78646',4,'YVELINES','Yvelines',1),(81,'79',54,'79191',4,'DEUX-SEVRES','Deux-Sèvres',1),(82,'80',22,'80021',3,'SOMME','Somme',1),(83,'81',73,'81004',2,'TARN','Tarn',1),(84,'82',73,'82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne',1),(85,'83',93,'83137',2,'VAR','Var',1),(86,'84',93,'84007',0,'VAUCLUSE','Vaucluse',1),(87,'85',52,'85191',3,'VENDEE','Vendée',1),(88,'86',54,'86194',3,'VIENNE','Vienne',1),(89,'87',74,'87085',3,'HAUTE-VIENNE','Haute-Vienne',1),(90,'88',41,'88160',4,'VOSGES','Vosges',1),(91,'89',26,'89024',5,'YONNE','Yonne',1),(92,'90',43,'90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort',1),(93,'91',11,'91228',5,'ESSONNE','Essonne',1),(94,'92',11,'92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine',1),(95,'93',11,'93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis',1),(96,'94',11,'94028',2,'VAL-DE-MARNE','Val-de-Marne',1),(97,'95',11,'95500',2,'VAL-D\'OISE','Val-d\'Oise',1),(98,'971',1,'97105',3,'GUADELOUPE','Guadeloupe',1),(99,'972',2,'97209',3,'MARTINIQUE','Martinique',1),(100,'973',3,'97302',3,'GUYANE','Guyane',1),(101,'974',4,'97411',3,'REUNION','Réunion',1),(102,'01',201,'',1,'ANVERS','Anvers',1),(103,'02',203,'',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale',1),(104,'03',202,'',2,'BRABANT-WALLON','Brabant-Wallon',1),(105,'04',201,'',1,'BRABANT-FLAMAND','Brabant-Flamand',1),(106,'05',201,'',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale',1),(107,'06',201,'',1,'FLANDRE-ORIENTALE','Flandre-Orientale',1),(108,'07',202,'',2,'HAINAUT','Hainaut',1),(109,'08',201,'',2,'LIEGE','Liège',1),(110,'09',202,'',1,'LIMBOURG','Limbourg',1),(111,'10',202,'',2,'LUXEMBOURG','Luxembourg',1),(112,'11',201,'',2,'NAMUR','Namur',1),(113,'NSW',2801,'',1,'','New South Wales',1),(114,'VIC',2801,'',1,'','Victoria',1),(115,'QLD',2801,'',1,'','Queensland',1),(116,'SA',2801,'',1,'','South Australia',1),(117,'ACT',2801,'',1,'','Australia Capital Territory',1),(118,'TAS',2801,'',1,'','Tasmania',1),(119,'WA',2801,'',1,'','Western Australia',1),(120,'NT',2801,'',1,'','Northern Territory',1),(121,'01',419,'',19,'PAIS VASCO','País Vasco',1),(122,'02',404,'',4,'ALBACETE','Albacete',1),(123,'03',411,'',11,'ALICANTE','Alicante',1),(124,'04',401,'',1,'ALMERIA','Almería',1),(125,'05',403,'',3,'AVILA','Avila',1),(126,'06',412,'',12,'BADAJOZ','Badajoz',1),(127,'07',414,'',14,'ISLAS BALEARES','Islas Baleares',1),(128,'08',406,'',6,'BARCELONA','Barcelona',1),(129,'09',403,'',8,'BURGOS','Burgos',1),(130,'10',412,'',12,'CACERES','Cáceres',1),(131,'11',401,'',1,'CADIz','Cádiz',1),(132,'12',411,'',11,'CASTELLON','Castellón',1),(133,'13',404,'',4,'CIUDAD REAL','Ciudad Real',1),(134,'14',401,'',1,'CORDOBA','Córdoba',1),(135,'15',413,'',13,'LA CORUÑA','La Coruña',1),(136,'16',404,'',4,'CUENCA','Cuenca',1),(137,'17',406,'',6,'GERONA','Gerona',1),(138,'18',401,'',1,'GRANADA','Granada',1),(139,'19',404,'',4,'GUADALAJARA','Guadalajara',1),(140,'20',419,'',19,'GUIPUZCOA','Guipúzcoa',1),(141,'21',401,'',1,'HUELVA','Huelva',1),(142,'22',402,'',2,'HUESCA','Huesca',1),(143,'23',401,'',1,'JAEN','Jaén',1),(144,'24',403,'',3,'LEON','León',1),(145,'25',406,'',6,'LERIDA','Lérida',1),(146,'26',415,'',15,'LA RIOJA','La Rioja',1),(147,'27',413,'',13,'LUGO','Lugo',1),(148,'28',416,'',16,'MADRID','Madrid',1),(149,'29',401,'',1,'MALAGA','Málaga',1),(150,'30',417,'',17,'MURCIA','Murcia',1),(151,'31',408,'',8,'NAVARRA','Navarra',1),(152,'32',413,'',13,'ORENSE','Orense',1),(153,'33',418,'',18,'ASTURIAS','Asturias',1),(154,'34',403,'',3,'PALENCIA','Palencia',1),(155,'35',405,'',5,'LAS PALMAS','Las Palmas',1),(156,'36',413,'',13,'PONTEVEDRA','Pontevedra',1),(157,'37',403,'',3,'SALAMANCA','Salamanca',1),(158,'38',405,'',5,'STA. CRUZ DE TENERIFE','Sta. Cruz de Tenerife',1),(159,'39',410,'',10,'CANTABRIA','Cantabria',1),(160,'40',403,'',3,'SEGOVIA','Segovia',1),(161,'41',401,'',1,'SEVILLA','Sevilla',1),(162,'42',403,'',3,'SORIA','Soria',1),(163,'43',406,'',6,'TARRAGONA','Tarragona',1),(164,'44',402,'',2,'TERUEL','Teruel',1),(165,'45',404,'',5,'TOLEDO','Toledo',1),(166,'46',411,'',11,'VALENCIA','Valencia',1),(167,'47',403,'',3,'VALLADOLID','Valladolid',1),(168,'48',419,'',19,'VIZCAYA','Vizcaya',1),(169,'49',403,'',3,'ZAMORA','Zamora',1),(170,'50',402,'',1,'ZARAGOZA','Zaragoza',1),(171,'51',407,'',7,'CEUTA','Ceuta',1),(172,'52',409,'',9,'MELILLA','Melilla',1),(173,'53',420,'',20,'OTROS','Otros',1),(174,'AG',601,NULL,NULL,'ARGOVIE','Argovie',1),(175,'AI',601,NULL,NULL,'APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures',1),(176,'AR',601,NULL,NULL,'APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures',1),(177,'BE',601,NULL,NULL,'BERNE','Berne',1),(178,'BL',601,NULL,NULL,'BALE CAMPAGNE','Bâle Campagne',1),(179,'BS',601,NULL,NULL,'BALE VILLE','Bâle Ville',1),(180,'FR',601,NULL,NULL,'FRIBOURG','Fribourg',1),(181,'GE',601,NULL,NULL,'GENEVE','Genève',1),(182,'GL',601,NULL,NULL,'GLARIS','Glaris',1),(183,'GR',601,NULL,NULL,'GRISONS','Grisons',1),(184,'JU',601,NULL,NULL,'JURA','Jura',1),(185,'LU',601,NULL,NULL,'LUCERNE','Lucerne',1),(186,'NE',601,NULL,NULL,'NEUCHATEL','Neuchâtel',1),(187,'NW',601,NULL,NULL,'NIDWALD','Nidwald',1),(188,'OW',601,NULL,NULL,'OBWALD','Obwald',1),(189,'SG',601,NULL,NULL,'SAINT-GALL','Saint-Gall',1),(190,'SH',601,NULL,NULL,'SCHAFFHOUSE','Schaffhouse',1),(191,'SO',601,NULL,NULL,'SOLEURE','Soleure',1),(192,'SZ',601,NULL,NULL,'SCHWYZ','Schwyz',1),(193,'TG',601,NULL,NULL,'THURGOVIE','Thurgovie',1),(194,'TI',601,NULL,NULL,'TESSIN','Tessin',1),(195,'UR',601,NULL,NULL,'URI','Uri',1),(196,'VD',601,NULL,NULL,'VAUD','Vaud',1),(197,'VS',601,NULL,NULL,'VALAIS','Valais',1),(198,'ZG',601,NULL,NULL,'ZUG','Zug',1),(199,'ZH',601,NULL,NULL,'ZURICH','Zürich',1),(200,'AL',1101,'',0,'ALABAMA','Alabama',1),(201,'AK',1101,'',0,'ALASKA','Alaska',1),(202,'AZ',1101,'',0,'ARIZONA','Arizona',1),(203,'AR',1101,'',0,'ARKANSAS','Arkansas',1),(204,'CA',1101,'',0,'CALIFORNIA','California',1),(205,'CO',1101,'',0,'COLORADO','Colorado',1),(206,'CT',1101,'',0,'CONNECTICUT','Connecticut',1),(207,'DE',1101,'',0,'DELAWARE','Delaware',1),(208,'FL',1101,'',0,'FLORIDA','Florida',1),(209,'GA',1101,'',0,'GEORGIA','Georgia',1),(210,'HI',1101,'',0,'HAWAII','Hawaii',1),(211,'ID',1101,'',0,'IDAHO','Idaho',1),(212,'IL',1101,'',0,'ILLINOIS','Illinois',1),(213,'IN',1101,'',0,'INDIANA','Indiana',1),(214,'IA',1101,'',0,'IOWA','Iowa',1),(215,'KS',1101,'',0,'KANSAS','Kansas',1),(216,'KY',1101,'',0,'KENTUCKY','Kentucky',1),(217,'LA',1101,'',0,'LOUISIANA','Louisiana',1),(218,'ME',1101,'',0,'MAINE','Maine',1),(219,'MD',1101,'',0,'MARYLAND','Maryland',1),(220,'MA',1101,'',0,'MASSACHUSSETTS','Massachusetts',1),(221,'MI',1101,'',0,'MICHIGAN','Michigan',1),(222,'MN',1101,'',0,'MINNESOTA','Minnesota',1),(223,'MS',1101,'',0,'MISSISSIPPI','Mississippi',1),(224,'MO',1101,'',0,'MISSOURI','Missouri',1),(225,'MT',1101,'',0,'MONTANA','Montana',1),(226,'NE',1101,'',0,'NEBRASKA','Nebraska',1),(227,'NV',1101,'',0,'NEVADA','Nevada',1),(228,'NH',1101,'',0,'NEW HAMPSHIRE','New Hampshire',1),(229,'NJ',1101,'',0,'NEW JERSEY','New Jersey',1),(230,'NM',1101,'',0,'NEW MEXICO','New Mexico',1),(231,'NY',1101,'',0,'NEW YORK','New York',1),(232,'NC',1101,'',0,'NORTH CAROLINA','North Carolina',1),(233,'ND',1101,'',0,'NORTH DAKOTA','North Dakota',1),(234,'OH',1101,'',0,'OHIO','Ohio',1),(235,'OK',1101,'',0,'OKLAHOMA','Oklahoma',1),(236,'OR',1101,'',0,'OREGON','Oregon',1),(237,'PA',1101,'',0,'PENNSYLVANIA','Pennsylvania',1),(238,'RI',1101,'',0,'RHODE ISLAND','Rhode Island',1),(239,'SC',1101,'',0,'SOUTH CAROLINA','South Carolina',1),(240,'SD',1101,'',0,'SOUTH DAKOTA','South Dakota',1),(241,'TN',1101,'',0,'TENNESSEE','Tennessee',1),(242,'TX',1101,'',0,'TEXAS','Texas',1),(243,'UT',1101,'',0,'UTAH','Utah',1),(244,'VT',1101,'',0,'VERMONT','Vermont',1),(245,'VA',1101,'',0,'VIRGINIA','Virginia',1),(246,'WA',1101,'',0,'WASHINGTON','Washington',1),(247,'WV',1101,'',0,'WEST VIRGINIA','West Virginia',1),(248,'WI',1101,'',0,'WISCONSIN','Wisconsin',1),(249,'WY',1101,'',0,'WYOMING','Wyoming',1),(250,'SS',8601,NULL,NULL,NULL,'San Salvador',1),(251,'SA',8603,NULL,NULL,NULL,'Santa Ana',1),(252,'AH',8603,NULL,NULL,NULL,'Ahuachapan',1),(253,'SO',8603,NULL,NULL,NULL,'Sonsonate',1),(254,'US',8602,NULL,NULL,NULL,'Usulutan',1),(255,'SM',8602,NULL,NULL,NULL,'San Miguel',1),(256,'MO',8602,NULL,NULL,NULL,'Morazan',1),(257,'LU',8602,NULL,NULL,NULL,'La Union',1),(258,'LL',8601,NULL,NULL,NULL,'La Libertad',1),(259,'CH',8601,NULL,NULL,NULL,'Chalatenango',1),(260,'CA',8601,NULL,NULL,NULL,'Cabañas',1),(261,'LP',8601,NULL,NULL,NULL,'La Paz',1),(262,'SV',8601,NULL,NULL,NULL,'San Vicente',1),(263,'CU',8601,NULL,NULL,NULL,'Cuscatlan',1),(264,'2301',2301,'',0,'CATAMARCA','Catamarca',1),(265,'2302',2301,'',0,'JUJUY','Jujuy',1),(266,'2303',2301,'',0,'TUCAMAN','Tucamán',1),(267,'2304',2301,'',0,'SANTIAGO DEL ESTERO','Santiago del Estero',1),(268,'2305',2301,'',0,'SALTA','Salta',1),(269,'2306',2302,'',0,'CHACO','Chaco',1),(270,'2307',2302,'',0,'CORRIENTES','Corrientes',1),(271,'2308',2302,'',0,'ENTRE RIOS','Entre Ríos',1),(272,'2309',2302,'',0,'FORMOSA MISIONES','Formosa Misiones',1),(273,'2310',2302,'',0,'SANTA FE','Santa Fe',1),(274,'2311',2303,'',0,'LA RIOJA','La Rioja',1),(275,'2312',2303,'',0,'MENDOZA','Mendoza',1),(276,'2313',2303,'',0,'SAN JUAN','San Juan',1),(277,'2314',2303,'',0,'SAN LUIS','San Luis',1),(278,'2315',2304,'',0,'CORDOBA','Córdoba',1),(279,'2316',2304,'',0,'BUENOS AIRES','Buenos Aires',1),(280,'2317',2304,'',0,'CABA','Caba',1),(281,'2318',2305,'',0,'LA PAMPA','La Pampa',1),(282,'2319',2305,'',0,'NEUQUEN','Neuquén',1),(283,'2320',2305,'',0,'RIO NEGRO','Río Negro',1),(284,'2321',2305,'',0,'CHUBUT','Chubut',1),(285,'2322',2305,'',0,'SANTA CRUZ','Santa Cruz',1),(286,'2323',2305,'',0,'TIERRA DEL FUEGO','Tierra del Fuego',1),(287,'2324',2305,'',0,'ISLAS MALVINAS','Islas Malvinas',1),(288,'2325',2305,'',0,'ANTARTIDA','Antártida',1),(289,'AN',11701,NULL,0,'AN','Andaman & Nicobar',1),(290,'AP',11701,NULL,0,'AP','Andhra Pradesh',1),(291,'AR',11701,NULL,0,'AR','Arunachal Pradesh',1),(292,'AS',11701,NULL,0,'AS','Assam',1),(293,'BR',11701,NULL,0,'BR','Bihar',1),(294,'CG',11701,NULL,0,'CG','Chattisgarh',1),(295,'CH',11701,NULL,0,'CH','Chandigarh',1),(296,'DD',11701,NULL,0,'DD','Daman & Diu',1),(297,'DL',11701,NULL,0,'DL','Delhi',1),(298,'DN',11701,NULL,0,'DN','Dadra and Nagar Haveli',1),(299,'GA',11701,NULL,0,'GA','Goa',1),(300,'GJ',11701,NULL,0,'GJ','Gujarat',1),(301,'HP',11701,NULL,0,'HP','Himachal Pradesh',1),(302,'HR',11701,NULL,0,'HR','Haryana',1),(303,'JH',11701,NULL,0,'JH','Jharkhand',1),(304,'JK',11701,NULL,0,'JK','Jammu & Kashmir',1),(305,'KA',11701,NULL,0,'KA','Karnataka',1),(306,'KL',11701,NULL,0,'KL','Kerala',1),(307,'LD',11701,NULL,0,'LD','Lakshadweep',1),(308,'MH',11701,NULL,0,'MH','Maharashtra',1),(309,'ML',11701,NULL,0,'ML','Meghalaya',1),(310,'MN',11701,NULL,0,'MN','Manipur',1),(311,'MP',11701,NULL,0,'MP','Madhya Pradesh',1),(312,'MZ',11701,NULL,0,'MZ','Mizoram',1),(313,'NL',11701,NULL,0,'NL','Nagaland',1),(314,'OR',11701,NULL,0,'OR','Orissa',1),(315,'PB',11701,NULL,0,'PB','Punjab',1),(316,'PY',11701,NULL,0,'PY','Puducherry',1),(317,'RJ',11701,NULL,0,'RJ','Rajasthan',1),(318,'SK',11701,NULL,0,'SK','Sikkim',1),(319,'TN',11701,NULL,0,'TN','Tamil Nadu',1),(320,'TR',11701,NULL,0,'TR','Tripura',1),(321,'UL',11701,NULL,0,'UL','Uttarakhand',1),(322,'UP',11701,NULL,0,'UP','Uttar Pradesh',1),(323,'WB',11701,NULL,0,'WB','West Bengal',1),(374,'151',6715,'',0,'151','Arica',1),(375,'152',6715,'',0,'152','Parinacota',1),(376,'011',6701,'',0,'011','Iquique',1),(377,'014',6701,'',0,'014','Tamarugal',1),(378,'021',6702,'',0,'021','Antofagasa',1),(379,'022',6702,'',0,'022','El Loa',1),(380,'023',6702,'',0,'023','Tocopilla',1),(381,'031',6703,'',0,'031','Copiapó',1),(382,'032',6703,'',0,'032','Chañaral',1),(383,'033',6703,'',0,'033','Huasco',1),(384,'041',6704,'',0,'041','Elqui',1),(385,'042',6704,'',0,'042','Choapa',1),(386,'043',6704,'',0,'043','Limarí',1),(387,'051',6705,'',0,'051','Valparaíso',1),(388,'052',6705,'',0,'052','Isla de Pascua',1),(389,'053',6705,'',0,'053','Los Andes',1),(390,'054',6705,'',0,'054','Petorca',1),(391,'055',6705,'',0,'055','Quillota',1),(392,'056',6705,'',0,'056','San Antonio',1),(393,'057',6705,'',0,'057','San Felipe de Aconcagua',1),(394,'058',6705,'',0,'058','Marga Marga',1),(395,'061',6706,'',0,'061','Cachapoal',1),(396,'062',6706,'',0,'062','Cardenal Caro',1),(397,'063',6706,'',0,'063','Colchagua',1),(398,'071',6707,'',0,'071','Talca',1),(399,'072',6707,'',0,'072','Cauquenes',1),(400,'073',6707,'',0,'073','Curicó',1),(401,'074',6707,'',0,'074','Linares',1),(402,'081',6708,'',0,'081','Concepción',1),(403,'082',6708,'',0,'082','Arauco',1),(404,'083',6708,'',0,'083','Biobío',1),(405,'084',6708,'',0,'084','Ñuble',1),(406,'091',6709,'',0,'091','Cautín',1),(407,'092',6709,'',0,'092','Malleco',1),(408,'141',6714,'',0,'141','Valdivia',1),(409,'142',6714,'',0,'142','Ranco',1),(410,'101',6710,'',0,'101','Llanquihue',1),(411,'102',6710,'',0,'102','Chiloé',1),(412,'103',6710,'',0,'103','Osorno',1),(413,'104',6710,'',0,'104','Palena',1),(414,'111',6711,'',0,'111','Coihaique',1),(415,'112',6711,'',0,'112','Aisén',1),(416,'113',6711,'',0,'113','Capitán Prat',1),(417,'114',6711,'',0,'114','General Carrera',1),(418,'121',6712,'',0,'121','Magallanes',1),(419,'122',6712,'',0,'122','Antártica Chilena',1),(420,'123',6712,'',0,'123','Tierra del Fuego',1),(421,'124',6712,'',0,'124','Última Esperanza',1),(422,'131',6713,'',0,'131','Santiago',1),(423,'132',6713,'',0,'132','Cordillera',1),(424,'133',6713,'',0,'133','Chacabuco',1),(425,'134',6713,'',0,'134','Maipo',1),(426,'135',6713,'',0,'135','Melipilla',1),(427,'136',6713,'',0,'136','Talagante',1),(428,'DIF',15401,'',0,'DIF','Distrito Federal',1),(429,'AGS',15401,'',0,'AGS','Aguascalientes',1),(430,'BCN',15401,'',0,'BCN','Baja California Norte',1),(431,'BCS',15401,'',0,'BCS','Baja California Sur',1),(432,'CAM',15401,'',0,'CAM','Campeche',1),(433,'CHP',15401,'',0,'CHP','Chiapas',1),(434,'CHI',15401,'',0,'CHI','Chihuahua',1),(435,'COA',15401,'',0,'COA','Coahuila',1),(436,'COL',15401,'',0,'COL','Colima',1),(437,'DUR',15401,'',0,'DUR','Durango',1),(438,'GTO',15401,'',0,'GTO','Guanajuato',1),(439,'GRO',15401,'',0,'GRO','Guerrero',1),(440,'HGO',15401,'',0,'HGO','Hidalgo',1),(441,'JAL',15401,'',0,'JAL','Jalisco',1),(442,'MEX',15401,'',0,'MEX','México',1),(443,'MIC',15401,'',0,'MIC','Michoacán de Ocampo',1),(444,'MOR',15401,'',0,'MOR','Morelos',1),(445,'NAY',15401,'',0,'NAY','Nayarit',1),(446,'NLE',15401,'',0,'NLE','Nuevo León',1),(447,'OAX',15401,'',0,'OAX','Oaxaca',1),(448,'PUE',15401,'',0,'PUE','Puebla',1),(449,'QRO',15401,'',0,'QRO','Querétaro',1),(451,'ROO',15401,'',0,'ROO','Quintana Roo',1),(452,'SLP',15401,'',0,'SLP','San Luis Potosí',1),(453,'SIN',15401,'',0,'SIN','Sinaloa',1),(454,'SON',15401,'',0,'SON','Sonora',1),(455,'TAB',15401,'',0,'TAB','Tabasco',1),(456,'TAM',15401,'',0,'TAM','Tamaulipas',1),(457,'TLX',15401,'',0,'TLX','Tlaxcala',1),(458,'VER',15401,'',0,'VER','Veracruz',1),(459,'YUC',15401,'',0,'YUC','Yucatán',1),(460,'ZAC',15401,'',0,'ZAC','Zacatecas',1),(461,'ANT',7001,'',0,'ANT','Antioquia',1),(462,'BOL',7001,'',0,'BOL','Bolívar',1),(463,'BOY',7001,'',0,'BOY','Boyacá',1),(464,'CAL',7001,'',0,'CAL','Caldas',1),(465,'CAU',7001,'',0,'CAU','Cauca',1),(466,'CUN',7001,'',0,'CUN','Cundinamarca',1),(467,'HUI',7001,'',0,'HUI','Huila',1),(468,'LAG',7001,'',0,'LAG','La Guajira',1),(469,'MET',7001,'',0,'MET','Meta',1),(470,'NAR',7001,'',0,'NAR','Nariño',1),(471,'NDS',7001,'',0,'NDS','Norte de Santander',1),(472,'SAN',7001,'',0,'SAN','Santander',1),(473,'SUC',7001,'',0,'SUC','Sucre',1),(474,'TOL',7001,'',0,'TOL','Tolima',1),(475,'VAC',7001,'',0,'VAC','Valle del Cauca',1),(476,'RIS',7001,'',0,'RIS','Risalda',1),(477,'ATL',7001,'',0,'ATL','Atlántico',1),(478,'COR',7001,'',0,'COR','Córdoba',1),(479,'SAP',7001,'',0,'SAP','San Andrés, Providencia y Santa Catalina',1),(480,'ARA',7001,'',0,'ARA','Arauca',1),(481,'CAS',7001,'',0,'CAS','Casanare',1),(482,'AMA',7001,'',0,'AMA','Amazonas',1),(483,'CAQ',7001,'',0,'CAQ','Caquetá',1),(484,'CHO',7001,'',0,'CHO','Chocó',1),(485,'GUA',7001,'',0,'GUA','Guainía',1),(486,'GUV',7001,'',0,'GUV','Guaviare',1),(487,'PUT',7001,'',0,'PUT','Putumayo',1),(488,'QUI',7001,'',0,'QUI','Quindío',1),(489,'VAU',7001,'',0,'VAU','Vaupés',1),(490,'BOG',7001,'',0,'BOG','Bogotá',1),(491,'VID',7001,'',0,'VID','Vichada',1),(492,'CES',7001,'',0,'CES','Cesar',1),(493,'MAG',7001,'',0,'MAG','Magdalena',1),(494,'AT',11401,'',0,'AT','Atlántida',1),(495,'CH',11401,'',0,'CH','Choluteca',1),(496,'CL',11401,'',0,'CL','Colón',1),(497,'CM',11401,'',0,'CM','Comayagua',1),(498,'CO',11401,'',0,'CO','Copán',1),(499,'CR',11401,'',0,'CR','Cortés',1),(500,'EP',11401,'',0,'EP','El Paraíso',1),(501,'FM',11401,'',0,'FM','Francisco Morazán',1),(502,'GD',11401,'',0,'GD','Gracias a Dios',1),(503,'IN',11401,'',0,'IN','Intibucá',1),(504,'IB',11401,'',0,'IB','Islas de la Bahía',1),(505,'LP',11401,'',0,'LP','La Paz',1),(506,'LM',11401,'',0,'LM','Lempira',1),(507,'OC',11401,'',0,'OC','Ocotepeque',1),(508,'OL',11401,'',0,'OL','Olancho',1),(509,'SB',11401,'',0,'SB','Santa Bárbara',1),(510,'VL',11401,'',0,'VL','Valle',1),(511,'YO',11401,'',0,'YO','Yoro',1),(512,'DC',11401,'',0,'DC','Distrito Central',1),(652,'CC',4601,'Oistins',0,'CC','Christ Church',1),(655,'SA',4601,'Greenland',0,'SA','Saint Andrew',1),(656,'SG',4601,'Bulkeley',0,'SG','Saint George',1),(657,'JA',4601,'Holetown',0,'JA','Saint James',1),(658,'SJ',4601,'Four Roads',0,'SJ','Saint John',1),(659,'SB',4601,'Bathsheba',0,'SB','Saint Joseph',1),(660,'SL',4601,'Crab Hill',0,'SL','Saint Lucy',1),(661,'SM',4601,'Bridgetown',0,'SM','Saint Michael',1),(662,'SP',4601,'Speightstown',0,'SP','Saint Peter',1),(663,'SC',4601,'Crane',0,'SC','Saint Philip',1),(664,'ST',4601,'Hillaby',0,'ST','Saint Thomas',1),(777,'AG',315,NULL,NULL,NULL,'AGRIGENTO',1),(778,'AL',312,NULL,NULL,NULL,'ALESSANDRIA',1),(779,'AN',310,NULL,NULL,NULL,'ANCONA',1),(780,'AO',319,NULL,NULL,NULL,'AOSTA',1),(781,'AR',316,NULL,NULL,NULL,'AREZZO',1),(782,'AP',310,NULL,NULL,NULL,'ASCOLI PICENO',1),(783,'AT',312,NULL,NULL,NULL,'ASTI',1),(784,'AV',304,NULL,NULL,NULL,'AVELLINO',1),(785,'BA',313,NULL,NULL,NULL,'BARI',1),(786,'BT',313,NULL,NULL,NULL,'BARLETTA-ANDRIA-TRANI',1),(787,'BL',320,NULL,NULL,NULL,'BELLUNO',1),(788,'BN',304,NULL,NULL,NULL,'BENEVENTO',1),(789,'BG',309,NULL,NULL,NULL,'BERGAMO',1),(790,'BI',312,NULL,NULL,NULL,'BIELLA',1),(791,'BO',305,NULL,NULL,NULL,'BOLOGNA',1),(792,'BZ',317,NULL,NULL,NULL,'BOLZANO',1),(793,'BS',309,NULL,NULL,NULL,'BRESCIA',1),(794,'BR',313,NULL,NULL,NULL,'BRINDISI',1),(795,'CA',314,NULL,NULL,NULL,'CAGLIARI',1),(796,'CL',315,NULL,NULL,NULL,'CALTANISSETTA',1),(797,'CB',311,NULL,NULL,NULL,'CAMPOBASSO',1),(798,'CI',314,NULL,NULL,NULL,'CARBONIA-IGLESIAS',1),(799,'CE',304,NULL,NULL,NULL,'CASERTA',1),(800,'CT',315,NULL,NULL,NULL,'CATANIA',1),(801,'CZ',303,NULL,NULL,NULL,'CATANZARO',1),(802,'CH',301,NULL,NULL,NULL,'CHIETI',1),(803,'CO',309,NULL,NULL,NULL,'COMO',1),(804,'CS',303,NULL,NULL,NULL,'COSENZA',1),(805,'CR',309,NULL,NULL,NULL,'CREMONA',1),(806,'KR',303,NULL,NULL,NULL,'CROTONE',1),(807,'CN',312,NULL,NULL,NULL,'CUNEO',1),(808,'EN',315,NULL,NULL,NULL,'ENNA',1),(809,'FM',310,NULL,NULL,NULL,'FERMO',1),(810,'FE',305,NULL,NULL,NULL,'FERRARA',1),(811,'FI',316,NULL,NULL,NULL,'FIRENZE',1),(812,'FG',313,NULL,NULL,NULL,'FOGGIA',1),(813,'FC',305,NULL,NULL,NULL,'FORLI-CESENA',1),(814,'FR',307,NULL,NULL,NULL,'FROSINONE',1),(815,'GE',308,NULL,NULL,NULL,'GENOVA',1),(816,'GO',306,NULL,NULL,NULL,'GORIZIA',1),(817,'GR',316,NULL,NULL,NULL,'GROSSETO',1),(818,'IM',308,NULL,NULL,NULL,'IMPERIA',1),(819,'IS',311,NULL,NULL,NULL,'ISERNIA',1),(820,'SP',308,NULL,NULL,NULL,'LA SPEZIA',1),(821,'AQ',301,NULL,NULL,NULL,'L AQUILA',1),(822,'LT',307,NULL,NULL,NULL,'LATINA',1),(823,'LE',313,NULL,NULL,NULL,'LECCE',1),(824,'LC',309,NULL,NULL,NULL,'LECCO',1),(825,'LI',314,NULL,NULL,NULL,'LIVORNO',1),(826,'LO',309,NULL,NULL,NULL,'LODI',1),(827,'LU',316,NULL,NULL,NULL,'LUCCA',1),(828,'MC',310,NULL,NULL,NULL,'MACERATA',1),(829,'MN',309,NULL,NULL,NULL,'MANTOVA',1),(830,'MS',316,NULL,NULL,NULL,'MASSA-CARRARA',1),(831,'MT',302,NULL,NULL,NULL,'MATERA',1),(832,'VS',314,NULL,NULL,NULL,'MEDIO CAMPIDANO',1),(833,'ME',315,NULL,NULL,NULL,'MESSINA',1),(834,'MI',309,NULL,NULL,NULL,'MILANO',1),(835,'MB',309,NULL,NULL,NULL,'MONZA e BRIANZA',1),(836,'MO',305,NULL,NULL,NULL,'MODENA',1),(837,'NA',304,NULL,NULL,NULL,'NAPOLI',1),(838,'NO',312,NULL,NULL,NULL,'NOVARA',1),(839,'NU',314,NULL,NULL,NULL,'NUORO',1),(840,'OG',314,NULL,NULL,NULL,'OGLIASTRA',1),(841,'OT',314,NULL,NULL,NULL,'OLBIA-TEMPIO',1),(842,'OR',314,NULL,NULL,NULL,'ORISTANO',1),(843,'PD',320,NULL,NULL,NULL,'PADOVA',1),(844,'PA',315,NULL,NULL,NULL,'PALERMO',1),(845,'PR',305,NULL,NULL,NULL,'PARMA',1),(846,'PV',309,NULL,NULL,NULL,'PAVIA',1),(847,'PG',318,NULL,NULL,NULL,'PERUGIA',1),(848,'PU',310,NULL,NULL,NULL,'PESARO e URBINO',1),(849,'PE',301,NULL,NULL,NULL,'PESCARA',1),(850,'PC',305,NULL,NULL,NULL,'PIACENZA',1),(851,'PI',316,NULL,NULL,NULL,'PISA',1),(852,'PT',316,NULL,NULL,NULL,'PISTOIA',1),(853,'PN',306,NULL,NULL,NULL,'PORDENONE',1),(854,'PZ',302,NULL,NULL,NULL,'POTENZA',1),(855,'PO',316,NULL,NULL,NULL,'PRATO',1),(856,'RG',315,NULL,NULL,NULL,'RAGUSA',1),(857,'RA',305,NULL,NULL,NULL,'RAVENNA',1),(858,'RC',303,NULL,NULL,NULL,'REGGIO CALABRIA',1),(859,'RE',305,NULL,NULL,NULL,'REGGIO NELL EMILIA',1),(860,'RI',307,NULL,NULL,NULL,'RIETI',1),(861,'RN',305,NULL,NULL,NULL,'RIMINI',1),(862,'RM',307,NULL,NULL,NULL,'ROMA',1),(863,'RO',320,NULL,NULL,NULL,'ROVIGO',1),(864,'SA',304,NULL,NULL,NULL,'SALERNO',1),(865,'SS',314,NULL,NULL,NULL,'SASSARI',1),(866,'SV',308,NULL,NULL,NULL,'SAVONA',1),(867,'SI',316,NULL,NULL,NULL,'SIENA',1),(868,'SR',315,NULL,NULL,NULL,'SIRACUSA',1),(869,'SO',309,NULL,NULL,NULL,'SONDRIO',1),(870,'TA',313,NULL,NULL,NULL,'TARANTO',1),(871,'TE',301,NULL,NULL,NULL,'TERAMO',1),(872,'TR',318,NULL,NULL,NULL,'TERNI',1),(873,'TO',312,NULL,NULL,NULL,'TORINO',1),(874,'TP',315,NULL,NULL,NULL,'TRAPANI',1),(875,'TN',317,NULL,NULL,NULL,'TRENTO',1),(876,'TV',320,NULL,NULL,NULL,'TREVISO',1),(877,'TS',306,NULL,NULL,NULL,'TRIESTE',1),(878,'UD',306,NULL,NULL,NULL,'UDINE',1),(879,'VA',309,NULL,NULL,NULL,'VARESE',1),(880,'VE',320,NULL,NULL,NULL,'VENEZIA',1),(881,'VB',312,NULL,NULL,NULL,'VERBANO-CUSIO-OSSOLA',1),(882,'VC',312,NULL,NULL,NULL,'VERCELLI',1),(883,'VR',320,NULL,NULL,NULL,'VERONA',1),(884,'VV',303,NULL,NULL,NULL,'VIBO VALENTIA',1),(885,'VI',320,NULL,NULL,NULL,'VICENZA',1),(886,'VT',307,NULL,NULL,NULL,'VITERBO',1),(1036,'VE-L',23201,'',0,'VE-L','Mérida',1),(1037,'VE-T',23201,'',0,'VE-T','Trujillo',1),(1038,'VE-E',23201,'',0,'VE-E','Barinas',1),(1039,'VE-M',23202,'',0,'VE-M','Miranda',1),(1040,'VE-W',23202,'',0,'VE-W','Vargas',1),(1041,'VE-A',23202,'',0,'VE-A','Distrito Capital',1),(1042,'VE-D',23203,'',0,'VE-D','Aragua',1),(1043,'VE-G',23203,'',0,'VE-G','Carabobo',1),(1044,'VE-I',23204,'',0,'VE-I','Falcón',1),(1045,'VE-K',23204,'',0,'VE-K','Lara',1),(1046,'VE-U',23204,'',0,'VE-U','Yaracuy',1),(1047,'VE-F',23205,'',0,'VE-F','Bolívar',1),(1048,'VE-X',23205,'',0,'VE-X','Amazonas',1),(1049,'VE-Y',23205,'',0,'VE-Y','Delta Amacuro',1),(1050,'VE-O',23206,'',0,'VE-O','Nueva Esparta',1),(1051,'VE-Z',23206,'',0,'VE-Z','Dependencias Federales',1),(1052,'VE-C',23207,'',0,'VE-C','Apure',1),(1053,'VE-J',23207,'',0,'VE-J','Guárico',1),(1054,'VE-H',23207,'',0,'VE-H','Cojedes',1),(1055,'VE-P',23207,'',0,'VE-P','Portuguesa',1),(1056,'VE-B',23208,'',0,'VE-B','Anzoátegui',1),(1057,'VE-N',23208,'',0,'VE-N','Monagas',1),(1058,'VE-R',23208,'',0,'VE-R','Sucre',1),(1059,'VE-V',23209,'',0,'VE-V','Zulia',1),(1060,'VE-S',23209,'',0,'VE-S','Táchira',1);
/*!40000 ALTER TABLE `llx_c_departements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_ecotaxe`
--

DROP TABLE IF EXISTS `llx_c_ecotaxe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_ecotaxe`
--

LOCK TABLES `llx_c_ecotaxe` WRITE;
/*!40000 ALTER TABLE `llx_c_ecotaxe` DISABLE KEYS */;
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (1,'ER-A-A','Materiels electriques < 0,2kg',0.01000000,'ERP',1,1),(2,'ER-A-B','Materiels electriques >= 0,2 kg et < 0,5 kg',0.03000000,'ERP',1,1),(3,'ER-A-C','Materiels electriques >= 0,5 kg et < 1 kg',0.04000000,'ERP',1,1),(4,'ER-A-D','Materiels electriques >= 1 kg et < 2 kg',0.13000000,'ERP',1,1),(5,'ER-A-E','Materiels electriques >= 2 kg et < 4kg',0.21000000,'ERP',1,1),(6,'ER-A-F','Materiels electriques >= 4 kg et < 8 kg',0.42000000,'ERP',1,1),(7,'ER-A-G','Materiels electriques >= 8 kg et < 15 kg',0.84000000,'ERP',1,1),(8,'ER-A-H','Materiels electriques >= 15 kg et < 20 kg',1.25000000,'ERP',1,1),(9,'ER-A-I','Materiels electriques >= 20 kg et < 30 kg',1.88000000,'ERP',1,1),(10,'ER-A-J','Materiels electriques >= 30 kg',3.34000000,'ERP',1,1),(11,'ER-M-1','TV, Moniteurs < 9kg',0.84000000,'ERP',1,1),(12,'ER-M-2','TV, Moniteurs >= 9kg et < 15kg',1.67000000,'ERP',1,1),(13,'ER-M-3','TV, Moniteurs >= 15kg et < 30kg',3.34000000,'ERP',1,1),(14,'ER-M-4','TV, Moniteurs >= 30 kg',6.69000000,'ERP',1,1),(15,'EC-A-A','Materiels electriques  0,2 kg max',0.00840000,'Ecologic',1,1),(16,'EC-A-B','Materiels electriques 0,21 kg min - 0,50 kg max',0.02500000,'Ecologic',1,1),(17,'EC-A-C','Materiels electriques  0,51 kg min - 1 kg max',0.04000000,'Ecologic',1,1),(18,'EC-A-D','Materiels electriques  1,01 kg min - 2,5 kg max',0.13000000,'Ecologic',1,1),(19,'EC-A-E','Materiels electriques  2,51 kg min - 4 kg max',0.21000000,'Ecologic',1,1),(20,'EC-A-F','Materiels electriques 4,01 kg min - 8 kg max',0.42000000,'Ecologic',1,1),(21,'EC-A-G','Materiels electriques  8,01 kg min - 12 kg max',0.63000000,'Ecologic',1,1),(22,'EC-A-H','Materiels electriques 12,01 kg min - 20 kg max',1.05000000,'Ecologic',1,1),(23,'EC-A-I','Materiels electriques  20,01 kg min',1.88000000,'Ecologic',1,1),(24,'EC-M-1','TV, Moniteurs 9 kg max',0.84000000,'Ecologic',1,1),(25,'EC-M-2','TV, Moniteurs 9,01 kg min - 18 kg max',1.67000000,'Ecologic',1,1),(26,'EC-M-3','TV, Moniteurs 18,01 kg min - 36 kg max',3.34000000,'Ecologic',1,1),(27,'EC-M-4','TV, Moniteurs 36,01 kg min',6.69000000,'Ecologic',1,1),(28,'ES-M-1','TV, Moniteurs <= 20 pouces',0.84000000,'Eco-systemes',1,1),(29,'ES-M-2','TV, Moniteurs > 20 pouces et <= 32 pouces',3.34000000,'Eco-systemes',1,1),(30,'ES-M-3','TV, Moniteurs > 32 pouces et autres grands ecrans',6.69000000,'Eco-systemes',1,1),(31,'ES-A-A','Ordinateur fixe, Audio home systems (HIFI), elements hifi separes',0.84000000,'Eco-systemes',1,1),(32,'ES-A-B','Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD, instruments de musique et caisses de resonance, haut parleurs...',0.25000000,'Eco-systemes',1,1),(33,'ES-A-C','Imprimante, photocopieur, telecopieur',0.42000000,'Eco-systemes',1,1),(34,'ES-A-D','Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, telephone, repondeur, telephone sans fil, modem, telecommande, casque, camescope, baladeur mp3, radio portable, radio K7 et CD portable, radio reveil',0.08400000,'Eco-systemes',1,1),(35,'ES-A-E','GSM',0.00840000,'Eco-systemes',1,1),(36,'ES-A-F','Jouets et equipements de loisirs et de sports < 0,5 kg',0.04200000,'Eco-systemes',1,1),(37,'ES-A-G','Jouets et equipements de loisirs et de sports > 0,5 kg',0.17000000,'Eco-systemes',1,1),(38,'ES-A-H','Jouets et equipements de loisirs et de sports > 10 kg',1.25000000,'Eco-systemes',1,1);
/*!40000 ALTER TABLE `llx_c_ecotaxe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_effectif`
--

DROP TABLE IF EXISTS `llx_c_effectif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_effectif` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_effectif`
--

LOCK TABLES `llx_c_effectif` WRITE;
/*!40000 ALTER TABLE `llx_c_effectif` DISABLE KEYS */;
INSERT INTO `llx_c_effectif` (`id`, `code`, `libelle`, `active`, `module`) VALUES (0,'EF0','-',1,NULL),(1,'EF1-5','1 - 5',1,NULL),(2,'EF6-10','6 - 10',1,NULL),(3,'EF11-50','11 - 50',1,NULL),(4,'EF51-100','51 - 100',1,NULL),(5,'EF100-500','100 - 500',1,NULL),(6,'EF500-','> 500',1,NULL);
/*!40000 ALTER TABLE `llx_c_effectif` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_field_list`
--

DROP TABLE IF EXISTS `llx_c_field_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_field_list` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `element` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `name` varchar(32) NOT NULL,
  `alias` varchar(32) NOT NULL,
  `title` varchar(32) NOT NULL,
  `align` varchar(6) DEFAULT 'left',
  `sort` tinyint(4) NOT NULL DEFAULT '1',
  `search` tinyint(4) NOT NULL DEFAULT '0',
  `enabled` varchar(255) DEFAULT '1',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_field_list`
--

LOCK TABLES `llx_c_field_list` WRITE;
/*!40000 ALTER TABLE `llx_c_field_list` DISABLE KEYS */;
INSERT INTO `llx_c_field_list` (`rowid`, `tms`, `element`, `entity`, `name`, `alias`, `title`, `align`, `sort`, `search`, `enabled`, `rang`) VALUES (1,'2011-02-06 11:18:30','product_default',1,'p.ref','ref','Ref','left',1,1,'1',1),(2,'2011-02-06 11:18:30','product_default',1,'p.label','label','Label','left',1,1,'1',2),(3,'2011-02-06 11:18:30','product_default',1,'p.barcode','barcode','BarCode','center',1,1,'$conf->barcode->enabled',3),(4,'2011-02-06 11:18:30','product_default',1,'p.tms','datem','DateModification','center',1,0,'1',4),(5,'2011-02-06 11:18:30','product_default',1,'p.price','price','SellingPriceHT','right',1,0,'1',5),(6,'2011-02-06 11:18:30','product_default',1,'p.price_ttc','price_ttc','SellingPriceTTC','right',1,0,'1',6),(7,'2011-02-06 11:18:30','product_default',1,'p.stock','stock','Stock','right',0,0,'$conf->stock->enabled',7),(8,'2011-02-06 11:18:30','product_default',1,'p.envente','status','Status','right',1,0,'1',8);
/*!40000 ALTER TABLE `llx_c_field_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_forme_juridique`
--

DROP TABLE IF EXISTS `llx_c_forme_juridique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_forme_juridique` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(11) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `isvatexempted` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_forme_juridique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=100014 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_forme_juridique`
--

LOCK TABLES `llx_c_forme_juridique` WRITE;
/*!40000 ALTER TABLE `llx_c_forme_juridique` DISABLE KEYS */;
INSERT INTO `llx_c_forme_juridique` (`rowid`, `code`, `fk_pays`, `libelle`, `isvatexempted`, `active`, `module`) VALUES (399,0,0,'-',0,1,NULL),(400,2301,23,'Monotributista',0,1,NULL),(401,2302,23,'Sociedad Civil',0,1,NULL),(402,2303,23,'Sociedades Comerciales',0,1,NULL),(403,2304,23,'Sociedades de Hecho',0,1,NULL),(404,2305,23,'Sociedades Irregulares',0,1,NULL),(405,2306,23,'Sociedad Colectiva',0,1,NULL),(406,2307,23,'Sociedad en Comandita Simple',0,1,NULL),(407,2308,23,'Sociedad de Capital e Industria',0,1,NULL),(408,2309,23,'Sociedad Accidental o en participación',0,1,NULL),(409,2310,23,'Sociedad de Responsabilidad Limitada',0,1,NULL),(410,2311,23,'Sociedad Anónima',0,1,NULL),(411,2312,23,'Sociedad Anónima con Participación Estatal Mayoritaria',0,1,NULL),(412,2313,23,'Sociedad en Comandita por Acciones (arts. 315 a 324, LSC)',0,1,NULL),(413,11,1,'Artisan Commerçant (EI)',0,1,NULL),(414,12,1,'Commerçant (EI)',0,1,NULL),(415,13,1,'Artisan (EI)',0,1,NULL),(416,14,1,'Officier public ou ministériel',0,1,NULL),(417,15,1,'Profession libérale (EI)',0,1,NULL),(418,16,1,'Exploitant agricole',0,1,NULL),(419,17,1,'Agent commercial',0,1,NULL),(420,18,1,'Associé Gérant de société',0,1,NULL),(421,19,1,'Personne physique',0,1,NULL),(422,21,1,'Indivision',0,1,NULL),(423,22,1,'Société créée de fait',0,1,NULL),(424,23,1,'Société en participation',0,1,NULL),(425,27,1,'Paroisse hors zone concordataire',0,1,NULL),(426,29,1,'Groupement de droit privé non doté de la personnalité morale',0,1,NULL),(427,31,1,'Personne morale de droit étranger, immatriculée au RCS',0,1,NULL),(428,32,1,'Personne morale de droit étranger, non immatriculée au RCS',0,1,NULL),(429,35,1,'Régime auto-entrepreneur',0,1,NULL),(430,41,1,'Établissement public ou régie à caractère industriel ou commercial',0,1,NULL),(431,51,1,'Société coopérative commerciale particulière',0,1,NULL),(432,52,1,'Société en nom collectif',0,1,NULL),(433,53,1,'Société en commandite',0,1,NULL),(434,54,1,'Société à responsabilité limitée (SARL)',0,1,NULL),(435,55,1,'Société anonyme à conseil d administration',0,1,NULL),(436,56,1,'Société anonyme à directoire',0,1,NULL),(437,57,1,'Société par actions simplifiée',0,1,NULL),(438,58,1,'Entreprise Unipersonnelle à Responsabilité Limitée (EURL)',0,1,NULL),(439,61,1,'Caisse d\'épargne et de prévoyance',0,1,NULL),(440,62,1,'Groupement d\'intérêt économique (GIE)',0,1,NULL),(441,63,1,'Société coopérative agricole',0,1,NULL),(442,64,1,'Société non commerciale d assurances',0,1,NULL),(443,65,1,'Société civile',0,1,NULL),(444,69,1,'Personnes de droit privé inscrites au RCS',0,1,NULL),(445,71,1,'Administration de l état',0,1,NULL),(446,72,1,'Collectivité territoriale',0,1,NULL),(447,73,1,'Établissement public administratif',0,1,NULL),(448,74,1,'Personne morale de droit public administratif',0,1,NULL),(449,81,1,'Organisme gérant régime de protection social à adhésion obligatoire',0,1,NULL),(450,82,1,'Organisme mutualiste',0,1,NULL),(451,83,1,'Comité d entreprise',0,1,NULL),(452,84,1,'Organisme professionnel',0,1,NULL),(453,85,1,'Organisme de retraite à adhésion non obligatoire',0,1,NULL),(454,91,1,'Syndicat de propriétaires',0,1,NULL),(455,92,1,'Association loi 1901 ou assimilé',0,1,NULL),(456,93,1,'Fondation',0,1,NULL),(457,99,1,'Personne morale de droit privé',0,1,NULL),(458,200,2,'Indépendant',0,1,NULL),(459,201,2,'SPRL - Société à responsabilité limitée',0,1,NULL),(460,202,2,'SA   - Société Anonyme',0,1,NULL),(461,203,2,'SCRL - Société coopérative à responsabilité limitée',0,1,NULL),(462,204,2,'ASBL - Association sans but Lucratif',0,1,NULL),(463,205,2,'SCRI - Société coopérative à responsabilité illimitée',0,1,NULL),(464,206,2,'SCS  - Société en commandite simple',0,1,NULL),(465,207,2,'SCA  - Société en commandite par action',0,1,NULL),(466,208,2,'SNC  - Société en nom collectif',0,1,NULL),(467,209,2,'GIE  - Groupement d intérêt économique',0,1,NULL),(468,210,2,'GEIE - Groupement européen d intérêt économique',0,1,NULL),(469,500,5,'Limited liability corporation (GmbH)',0,1,NULL),(470,501,5,'Stock corporation (AG)',0,1,NULL),(471,502,5,'Partnerships general or limited (GmbH & CO. KG)',0,1,NULL),(472,503,5,'Sole proprietor / Private business',0,1,NULL),(473,301,3,'Società semplice',0,1,NULL),(474,302,3,'Società in nome collettivo s.n.c.',0,1,NULL),(475,303,3,'Società in accomandita semplice s.a.s.',0,1,NULL),(476,304,3,'Società per azioni s.p.a.',0,1,NULL),(477,305,3,'Società a responsabilità limitata s.r.l.',0,1,NULL),(478,306,3,'Società in accomandita per azioni s.a.p.a.',0,1,NULL),(479,307,3,'Società cooperativa',0,1,NULL),(480,308,3,'Società consortile',0,1,NULL),(481,309,3,'Società europea',0,1,NULL),(482,310,3,'Società cooperativa europea',0,1,NULL),(483,311,3,'Società unipersonale',0,1,NULL),(484,312,3,'Società di professionisti',0,1,NULL),(485,313,3,'Società di fatto',0,1,NULL),(486,314,3,'Società occulta',0,1,NULL),(487,315,3,'Società apparente',0,1,NULL),(488,316,3,'Impresa individuale ',0,1,NULL),(489,317,3,'Impresa coniugale',0,1,NULL),(490,318,3,'Impresa familiare',0,1,NULL),(491,600,6,'Raison Individuelle',0,1,NULL),(492,601,6,'Société Simple',0,1,NULL),(493,602,6,'Société en nom collectif',0,1,NULL),(494,603,6,'Société en commandite',0,1,NULL),(495,604,6,'Société anonyme (SA)',0,1,NULL),(496,605,6,'Société en commandite par actions',0,1,NULL),(497,606,6,'Société à responsabilité limitée (SARL)',0,1,NULL),(498,607,6,'Société coopérative',0,1,NULL),(499,608,6,'Association',0,1,NULL),(500,609,6,'Fondation',0,1,NULL),(501,700,7,'Sole Trader',0,1,NULL),(502,701,7,'Partnership',0,1,NULL),(503,702,7,'Private Limited Company by shares (LTD)',0,1,NULL),(504,703,7,'Public Limited Company',0,1,NULL),(505,704,7,'Workers Cooperative',0,1,NULL),(506,705,7,'Limited Liability Partnership',0,1,NULL),(507,706,7,'Franchise',0,1,NULL),(508,1000,10,'Société à responsabilité limitée (SARL)',0,1,NULL),(509,1001,10,'Société en Nom Collectif (SNC)',0,1,NULL),(510,1002,10,'Société en Commandite Simple (SCS)',0,1,NULL),(511,1003,10,'société en participation',0,1,NULL),(512,1004,10,'Société Anonyme (SA)',0,1,NULL),(513,1005,10,'Société Unipersonnelle à Responsabilité Limitée (SUARL)',0,1,NULL),(514,1006,10,'Groupement d\'intérêt économique (GEI)',0,1,NULL),(515,1007,10,'Groupe de sociétés',0,1,NULL),(516,401,4,'Empresario Individual',0,1,NULL),(517,402,4,'Comunidad de Bienes',0,1,NULL),(518,403,4,'Sociedad Civil',0,1,NULL),(519,404,4,'Sociedad Colectiva',0,1,NULL),(520,405,4,'Sociedad Limitada',0,1,NULL),(521,406,4,'Sociedad Anónima',0,1,NULL),(522,407,4,'Sociedad Comandataria por Acciones',0,1,NULL),(523,408,4,'Sociedad Comandataria Simple',0,1,NULL),(524,409,4,'Sociedad Laboral',0,1,NULL),(525,410,4,'Sociedad Cooperativa',0,1,NULL),(526,411,4,'Sociedad de Garantía Recíproca',0,1,NULL),(527,412,4,'Entidad de Capital-Riesgo',0,1,NULL),(528,413,4,'Agrupación de Interés Económico',0,1,NULL),(529,414,4,'Sociedad de Inversión Mobiliaria',0,1,NULL),(530,415,4,'Agrupación sin Ánimo de Lucro',0,1,NULL),(531,15201,152,'Mauritius Private Company Limited By Shares',0,1,NULL),(532,15202,152,'Mauritius Company Limited By Guarantee',0,1,NULL),(533,15203,152,'Mauritius Public Company Limited By Shares',0,1,NULL),(534,15204,152,'Mauritius Foreign Company',0,1,NULL),(535,15205,152,'Mauritius GBC1 (Offshore Company)',0,1,NULL),(536,15206,152,'Mauritius GBC2 (International Company)',0,1,NULL),(537,15207,152,'Mauritius General Partnership',0,1,NULL),(538,15208,152,'Mauritius Limited Partnership',0,1,NULL),(539,15209,152,'Mauritius Sole Proprietorship',0,1,NULL),(540,15210,152,'Mauritius Trusts',0,1,NULL),(541,15401,154,'Sociedad en nombre colectivo',0,1,NULL),(542,15402,154,'Sociedad en comandita simple',0,1,NULL),(543,15403,154,'Sociedad de responsabilidad limitada',0,1,NULL),(544,15404,154,'Sociedad anónima',0,1,NULL),(545,15405,154,'Sociedad en comandita por acciones',0,1,NULL),(546,15406,154,'Sociedad cooperativa',0,1,NULL),(100001,100001,1,'Etudiant',0,0,'cabinetmed'),(100002,100002,1,'Retraité',0,0,'cabinetmed'),(100003,100003,1,'Artisan',0,0,'cabinetmed'),(100004,100004,1,'Femme de ménage',0,0,'cabinetmed'),(100005,100005,1,'Professeur',0,0,'cabinetmed'),(100006,100006,1,'Profession libérale',0,0,'cabinetmed'),(100007,100007,1,'Informaticien',0,0,'cabinetmed');
/*!40000 ALTER TABLE `llx_c_forme_juridique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_input_method`
--

DROP TABLE IF EXISTS `llx_c_input_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_input_method` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `libelle` varchar(60) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_methode_commande_fournisseur` (`code`),
  UNIQUE KEY `uk_c_input_method` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_input_method`
--

LOCK TABLES `llx_c_input_method` WRITE;
/*!40000 ALTER TABLE `llx_c_input_method` DISABLE KEYS */;
INSERT INTO `llx_c_input_method` (`rowid`, `code`, `libelle`, `active`, `module`) VALUES (1,'OrderByMail','Courrier',1,NULL),(2,'OrderByFax','Fax',1,NULL),(3,'OrderByEMail','EMail',1,NULL),(4,'OrderByPhone','Téléphone',1,NULL),(5,'OrderByWWW','En ligne',1,NULL);
/*!40000 ALTER TABLE `llx_c_input_method` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_input_reason`
--

DROP TABLE IF EXISTS `llx_c_input_reason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_input_reason` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_input_reason` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_input_reason`
--

LOCK TABLES `llx_c_input_reason` WRITE;
/*!40000 ALTER TABLE `llx_c_input_reason` DISABLE KEYS */;
INSERT INTO `llx_c_input_reason` (`rowid`, `code`, `label`, `active`, `module`) VALUES (1,'SRC_INTE','Web site',1,NULL),(2,'SRC_CAMP_MAIL','Mailing campaign',1,NULL),(3,'SRC_CAMP_PHO','Phone campaign',1,NULL),(4,'SRC_CAMP_FAX','Fax campaign',1,NULL),(5,'SRC_COMM','Commercial contact',1,NULL),(6,'SRC_SHOP','Shop contact',1,NULL),(7,'SRC_CAMP_EMAIL','EMailing campaign',1,NULL);
/*!40000 ALTER TABLE `llx_c_input_reason` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_methode_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_c_methode_commande_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_methode_commande_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `libelle` varchar(60) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_methode_commande_fournisseur` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_methode_commande_fournisseur`
--

LOCK TABLES `llx_c_methode_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_c_methode_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_c_methode_commande_fournisseur` (`rowid`, `code`, `libelle`, `active`) VALUES (1,'OrderByMail','Courrier',1),(2,'OrderByFax','Fax',1),(3,'OrderByEMail','EMail',1),(4,'OrderByPhone','Téléphone',1),(5,'OrderByWWW','En ligne',1);
/*!40000 ALTER TABLE `llx_c_methode_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_paiement`
--

DROP TABLE IF EXISTS `llx_c_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_paiement` (
  `id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `type` smallint(6) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_paiement`
--

LOCK TABLES `llx_c_paiement` WRITE;
/*!40000 ALTER TABLE `llx_c_paiement` DISABLE KEYS */;
INSERT INTO `llx_c_paiement` (`id`, `code`, `libelle`, `type`, `active`, `module`) VALUES (0,'','-',3,1,NULL),(1,'TIP','TIP',2,1,NULL),(2,'VIR','Virement',2,1,NULL),(3,'PRE','Prélèvement',2,1,NULL),(4,'LIQ','Espèces',2,1,NULL),(6,'CB','Carte Bancaire',2,1,NULL),(7,'CHQ','Chèque',2,1,NULL),(50,'VAD','Paiement en ligne',2,0,NULL),(51,'TRA','Traite',2,0,NULL),(52,'LCR','LCR',2,0,NULL),(53,'FAC','Factor',2,0,NULL),(54,'PRO','Proforma',2,0,NULL);
/*!40000 ALTER TABLE `llx_c_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_paper_format`
--

DROP TABLE IF EXISTS `llx_c_paper_format`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_paper_format` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `label` varchar(50) NOT NULL,
  `width` float(6,2) DEFAULT '0.00',
  `height` float(6,2) DEFAULT '0.00',
  `unit` varchar(5) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_paper_format`
--

LOCK TABLES `llx_c_paper_format` WRITE;
/*!40000 ALTER TABLE `llx_c_paper_format` DISABLE KEYS */;
INSERT INTO `llx_c_paper_format` (`rowid`, `code`, `label`, `width`, `height`, `unit`, `active`, `module`) VALUES (1,'4A0','Format 4A0',1682.00,2378.00,'mm',1,NULL),(2,'2A0','Format 2A0',1189.00,1682.00,'mm',1,NULL),(3,'A0','Format A0',840.00,1189.00,'mm',1,NULL),(4,'A1','Format A1',594.00,840.00,'mm',1,NULL),(5,'A2','Format A2',420.00,594.00,'mm',1,NULL),(6,'A3','Format A3',297.00,420.00,'mm',1,NULL),(7,'A4','Format A4',210.00,297.00,'mm',1,NULL),(8,'A5','Format A5',148.00,210.00,'mm',1,NULL),(9,'A6','Format A6',105.00,148.00,'mm',1,NULL),(100,'USLetter','Format Letter (A)',216.00,279.00,'mm',1,NULL),(105,'USLegal','Format Legal',216.00,356.00,'mm',1,NULL),(110,'USExecutive','Format Executive',190.00,254.00,'mm',1,NULL),(115,'USLedger','Format Ledger/Tabloid (B)',279.00,432.00,'mm',1,NULL),(200,'Canadian P1','Format Canadian P1',560.00,860.00,'mm',1,NULL),(205,'Canadian P2','Format Canadian P2',430.00,560.00,'mm',1,NULL),(210,'Canadian P3','Format Canadian P3',280.00,430.00,'mm',1,NULL),(215,'Canadian P4','Format Canadian P4',215.00,280.00,'mm',1,NULL),(220,'Canadian P5','Format Canadian P5',140.00,215.00,'mm',1,NULL),(225,'Canadian P6','Format Canadian P6',107.00,140.00,'mm',1,NULL);
/*!40000 ALTER TABLE `llx_c_paper_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_payment_term`
--

DROP TABLE IF EXISTS `llx_c_payment_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_payment_term` (
  `rowid` int(11) NOT NULL,
  `code` varchar(16) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` tinyint(4) DEFAULT '1',
  `libelle` varchar(255) DEFAULT NULL,
  `libelle_facture` text,
  `fdm` tinyint(4) DEFAULT NULL,
  `nbjour` smallint(6) DEFAULT NULL,
  `decalage` smallint(6) DEFAULT NULL,
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_payment_term`
--

LOCK TABLES `llx_c_payment_term` WRITE;
/*!40000 ALTER TABLE `llx_c_payment_term` DISABLE KEYS */;
INSERT INTO `llx_c_payment_term` (`rowid`, `code`, `sortorder`, `active`, `libelle`, `libelle_facture`, `fdm`, `nbjour`, `decalage`, `module`) VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL,NULL);
/*!40000 ALTER TABLE `llx_c_payment_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_pays`
--

DROP TABLE IF EXISTS `llx_c_pays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_pays`
--

LOCK TABLES `llx_c_pays` WRITE;
/*!40000 ALTER TABLE `llx_c_pays` DISABLE KEYS */;
INSERT INTO `llx_c_pays` (`rowid`, `code`, `code_iso`, `libelle`, `active`) VALUES (0,'',NULL,'-',1),(1,'FR',NULL,'France',1),(2,'BE',NULL,'Belgium',1),(3,'IT',NULL,'Italy',1),(4,'ES',NULL,'Spain',1),(5,'DE',NULL,'Germany',1),(6,'CH',NULL,'Suisse',1),(7,'GB',NULL,'United Kingdow',1),(8,'IE',NULL,'Irland',1),(9,'CN',NULL,'China',1),(10,'TN',NULL,'Tunisie',1),(11,'US',NULL,'United States',1),(12,'MA',NULL,'Maroc',1),(13,'DZ',NULL,'Algérie',1),(14,'CA',NULL,'Canada',1),(15,'TG',NULL,'Togo',1),(16,'GA',NULL,'Gabon',1),(17,'NL',NULL,'Nerderland',1),(18,'HU',NULL,'Hongrie',1),(19,'RU',NULL,'Russia',1),(20,'SE',NULL,'Sweden',1),(21,'CI',NULL,'Côte d\'Ivoire',1),(22,'SN',NULL,'Sénégal',1),(23,'AR',NULL,'Argentine',1),(24,'CM',NULL,'Cameroun',1),(25,'PT',NULL,'Portugal',1),(26,'SA',NULL,'Arabie Saoudite',1),(27,'MC',NULL,'Monaco',1),(28,'AU',NULL,'Australia',1),(29,'SG',NULL,'Singapour',1),(30,'AF',NULL,'Afghanistan',1),(31,'AX',NULL,'Iles Aland',1),(32,'AL',NULL,'Albanie',1),(33,'AS',NULL,'Samoa américaines',1),(34,'AD',NULL,'Andorre',1),(35,'AO',NULL,'Angola',1),(36,'AI',NULL,'Anguilla',1),(37,'AQ',NULL,'Antarctique',1),(38,'AG',NULL,'Antigua-et-Barbuda',1),(39,'AM',NULL,'Arménie',1),(40,'AW',NULL,'Aruba',1),(41,'AT',NULL,'Autriche',1),(42,'AZ',NULL,'Azerbaïdjan',1),(43,'BS',NULL,'Bahamas',1),(44,'BH',NULL,'Bahreïn',1),(45,'BD',NULL,'Bangladesh',1),(46,'BB',NULL,'Barbade',1),(47,'BY',NULL,'Biélorussie',1),(48,'BZ',NULL,'Belize',1),(49,'BJ',NULL,'Bénin',1),(50,'BM',NULL,'Bermudes',1),(51,'BT',NULL,'Bhoutan',1),(52,'BO',NULL,'Bolivie',1),(53,'BA',NULL,'Bosnie-Herzégovine',1),(54,'BW',NULL,'Botswana',1),(55,'BV',NULL,'Ile Bouvet',1),(56,'BR',NULL,'Brésil',1),(57,'IO',NULL,'Territoire britannique de l\'Océan Indien',1),(58,'BN',NULL,'Brunei',1),(59,'BG',NULL,'Bulgarie',1),(60,'BF',NULL,'Burkina Faso',1),(61,'BI',NULL,'Burundi',1),(62,'KH',NULL,'Cambodge',1),(63,'CV',NULL,'Cap-Vert',1),(64,'KY',NULL,'Iles Cayman',1),(65,'CF',NULL,'République centrafricaine',1),(66,'TD',NULL,'Tchad',1),(67,'CL',NULL,'Chili',1),(68,'CX',NULL,'Ile Christmas',1),(69,'CC',NULL,'Iles des Cocos (Keeling)',1),(70,'CO',NULL,'Colombie',1),(71,'KM',NULL,'Comores',1),(72,'CG',NULL,'Congo',1),(73,'CD',NULL,'République démocratique du Congo',1),(74,'CK',NULL,'Iles Cook',1),(75,'CR',NULL,'Costa Rica',1),(76,'HR',NULL,'Croatie',1),(77,'CU',NULL,'Cuba',1),(78,'CY',NULL,'Chypre',1),(79,'CZ',NULL,'République Tchèque',1),(80,'DK',NULL,'Danemark',1),(81,'DJ',NULL,'Djibouti',1),(82,'DM',NULL,'Dominique',1),(83,'DO',NULL,'République Dominicaine',1),(84,'EC',NULL,'Equateur',1),(85,'EG',NULL,'Egypte',1),(86,'SV',NULL,'Salvador',1),(87,'GQ',NULL,'Guinée Equatoriale',1),(88,'ER',NULL,'Erythrée',1),(89,'EE',NULL,'Estonie',1),(90,'ET',NULL,'Ethiopie',1),(91,'FK',NULL,'Iles Falkland',1),(92,'FO',NULL,'Iles Féroé',1),(93,'FJ',NULL,'Iles Fidji',1),(94,'FI',NULL,'Finlande',1),(95,'GF',NULL,'Guyane française',1),(96,'PF',NULL,'Polynésie française',1),(97,'TF',NULL,'Terres australes françaises',1),(98,'GM',NULL,'Gambie',1),(99,'GE',NULL,'Géorgie',1),(100,'GH',NULL,'Ghana',1),(101,'GI',NULL,'Gibraltar',1),(102,'GR',NULL,'Grèce',1),(103,'GL',NULL,'Groenland',1),(104,'GD',NULL,'Grenade',1),(105,'GP',NULL,'Guadeloupe',1),(106,'GU',NULL,'Guam',1),(107,'GT',NULL,'Guatemala',1),(108,'GN',NULL,'Guinée',1),(109,'GW',NULL,'Guinée-Bissao',1),(110,'GY',NULL,'Guyana',1),(111,'HT',NULL,'Haiti',1),(112,'HM',NULL,'Iles Heard et McDonald',1),(113,'VA',NULL,'Saint-Siège (Vatican)',1),(114,'HN',NULL,'Honduras',1),(115,'HK',NULL,'Hong Kong',1),(116,'IS',NULL,'Islande',1),(117,'IN',NULL,'India',1),(118,'ID',NULL,'Indonésie',1),(119,'IR',NULL,'Iran',1),(120,'IQ',NULL,'Iraq',1),(121,'IL',NULL,'Israel',1),(122,'JM',NULL,'Jamaïque',1),(123,'JP',NULL,'Japon',1),(124,'JO',NULL,'Jordanie',1),(125,'KZ',NULL,'Kazakhstan',1),(126,'KE',NULL,'Kenya',1),(127,'KI',NULL,'Kiribati',1),(128,'KP',NULL,'Corée du Nord',1),(129,'KR',NULL,'Corée du Sud',1),(130,'KW',NULL,'Koweït',1),(131,'KG',NULL,'Kirghizistan',1),(132,'LA',NULL,'Laos',1),(133,'LV',NULL,'Lettonie',1),(134,'LB',NULL,'Liban',1),(135,'LS',NULL,'Lesotho',1),(136,'LR',NULL,'Liberia',1),(137,'LY',NULL,'Libye',1),(138,'LI',NULL,'Liechtenstein',1),(139,'LT',NULL,'Lituanie',1),(140,'LU',NULL,'Luxembourg',1),(141,'MO',NULL,'Macao',1),(142,'MK',NULL,'ex-République yougoslave de Macédoine',1),(143,'MG',NULL,'Madagascar',1),(144,'MW',NULL,'Malawi',1),(145,'MY',NULL,'Malaisie',1),(146,'MV',NULL,'Maldives',1),(147,'ML',NULL,'Mali',1),(148,'MT',NULL,'Malte',1),(149,'MH',NULL,'Iles Marshall',1),(150,'MQ',NULL,'Martinique',1),(151,'MR',NULL,'Mauritanie',1),(152,'MU',NULL,'Maurice',1),(153,'YT',NULL,'Mayotte',1),(154,'MX',NULL,'Mexique',1),(155,'FM',NULL,'Micronésie',1),(156,'MD',NULL,'Moldavie',1),(157,'MN',NULL,'Mongolie',1),(158,'MS',NULL,'Monserrat',1),(159,'MZ',NULL,'Mozambique',1),(160,'MM',NULL,'Birmanie (Myanmar)',1),(161,'NA',NULL,'Namibie',1),(162,'NR',NULL,'Nauru',1),(163,'NP',NULL,'Népal',1),(164,'AN',NULL,'Antilles néerlandaises',1),(165,'NC',NULL,'Nouvelle-Calédonie',1),(166,'NZ',NULL,'Nouvelle-Zélande',1),(167,'NI',NULL,'Nicaragua',1),(168,'NE',NULL,'Niger',1),(169,'NG',NULL,'Nigeria',1),(170,'NU',NULL,'Nioué',1),(171,'NF',NULL,'Ile Norfolk',1),(172,'MP',NULL,'Mariannes du Nord',1),(173,'NO',NULL,'Norvège',1),(174,'OM',NULL,'Oman',1),(175,'PK',NULL,'Pakistan',1),(176,'PW',NULL,'Palaos',1),(177,'PS',NULL,'territoire Palestinien Occupé',1),(178,'PA',NULL,'Panama',1),(179,'PG',NULL,'Papouasie-Nouvelle-Guinée',1),(180,'PY',NULL,'Paraguay',1),(181,'PE',NULL,'Pérou',1),(182,'PH',NULL,'Philippines',1),(183,'PN',NULL,'Iles Pitcairn',1),(184,'PL',NULL,'Pologne',1),(185,'PR',NULL,'Porto Rico',1),(186,'QA',NULL,'Qatar',1),(187,'RE',NULL,'Réunion',1),(188,'RO',NULL,'Roumanie',1),(189,'RW',NULL,'Rwanda',1),(190,'SH',NULL,'Sainte-Hélène',1),(191,'KN',NULL,'Saint-Christophe-et-Niévès',1),(192,'LC',NULL,'Sainte-Lucie',1),(193,'PM',NULL,'Saint-Pierre-et-Miquelon',1),(194,'VC',NULL,'Saint-Vincent-et-les-Grenadines',1),(195,'WS',NULL,'Samoa',1),(196,'SM',NULL,'Saint-Marin',1),(197,'ST',NULL,'Sao Tomé-et-Principe',1),(198,'RS',NULL,'Serbie',1),(199,'SC',NULL,'Seychelles',1),(200,'SL',NULL,'Sierra Leone',1),(201,'SK',NULL,'Slovaquie',1),(202,'SI',NULL,'Slovénie',1),(203,'SB',NULL,'Iles Salomon',1),(204,'SO',NULL,'Somalie',1),(205,'ZA',NULL,'Afrique du Sud',1),(206,'GS',NULL,'Iles Géorgie du Sud et Sandwich du Sud',1),(207,'LK',NULL,'Sri Lanka',1),(208,'SD',NULL,'Soudan',1),(209,'SR',NULL,'Suriname',1),(210,'SJ',NULL,'Iles Svalbard et Jan Mayen',1),(211,'SZ',NULL,'Swaziland',1),(212,'SY',NULL,'Syrie',1),(213,'TW',NULL,'Taïwan',1),(214,'TJ',NULL,'Tadjikistan',1),(215,'TZ',NULL,'Tanzanie',1),(216,'TH',NULL,'Thaïlande',1),(217,'TL',NULL,'Timor Oriental',1),(218,'TK',NULL,'Tokélaou',1),(219,'TO',NULL,'Tonga',1),(220,'TT',NULL,'Trinité-et-Tobago',1),(221,'TR',NULL,'Turquie',1),(222,'TM',NULL,'Turkménistan',1),(223,'TC',NULL,'Iles Turks-et-Caicos',1),(224,'TV',NULL,'Tuvalu',1),(225,'UG',NULL,'Ouganda',1),(226,'UA',NULL,'Ukraine',1),(227,'AE',NULL,'Émirats arabes unis',1),(228,'UM',NULL,'Iles mineures éloignées des États-Unis',1),(229,'UY',NULL,'Uruguay',1),(230,'UZ',NULL,'Ouzbékistan',1),(231,'VU',NULL,'Vanuatu',1),(232,'VE',NULL,'Vénézuela',1),(233,'VN',NULL,'Viêt Nam',1),(234,'VG',NULL,'Iles Vierges britanniques',1),(235,'VI',NULL,'Iles Vierges américaines',1),(236,'WF',NULL,'Wallis-et-Futuna',1),(237,'EH',NULL,'Sahara occidental',1),(238,'YE',NULL,'Yémen',1),(239,'ZM',NULL,'Zambie',1),(240,'ZW',NULL,'Zimbabwe',1),(241,'GG',NULL,'Guernesey',1),(242,'IM',NULL,'Ile de Man',1),(243,'JE',NULL,'Jersey',1),(244,'ME',NULL,'Monténégro',1),(245,'BL',NULL,'Saint-Barthélemy',1),(246,'MF',NULL,'Saint-Martin',1);
/*!40000 ALTER TABLE `llx_c_pays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_propalst`
--

DROP TABLE IF EXISTS `llx_c_propalst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_propalst` (
  `id` smallint(6) NOT NULL,
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_propalst`
--

LOCK TABLES `llx_c_propalst` WRITE;
/*!40000 ALTER TABLE `llx_c_propalst` DISABLE KEYS */;
INSERT INTO `llx_c_propalst` (`id`, `code`, `label`, `active`) VALUES (0,'PR_DRAFT','Brouillon',1),(1,'PR_OPEN','Ouverte',1),(2,'PR_SIGNED','Signée',1),(3,'PR_NOTSIGNED','Non Signée',1),(4,'PR_FAC','Facturée',1);
/*!40000 ALTER TABLE `llx_c_propalst` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_prospectlevel`
--

DROP TABLE IF EXISTS `llx_c_prospectlevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_prospectlevel` (
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` smallint(6) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_prospectlevel`
--

LOCK TABLES `llx_c_prospectlevel` WRITE;
/*!40000 ALTER TABLE `llx_c_prospectlevel` DISABLE KEYS */;
INSERT INTO `llx_c_prospectlevel` (`code`, `label`, `sortorder`, `active`, `module`) VALUES ('PL_HIGH','High',4,1,NULL),('PL_LOW','Low',2,1,NULL),('PL_MEDIUM','Medium',3,1,NULL),('PL_NONE','None',1,1,NULL);
/*!40000 ALTER TABLE `llx_c_prospectlevel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_regions`
--

DROP TABLE IF EXISTS `llx_c_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_regions` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code_region` int(11) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `cheflieu` varchar(50) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code_region` (`code_region`),
  KEY `idx_c_regions_fk_pays` (`fk_pays`),
  CONSTRAINT `fk_c_regions_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=23210 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_regions`
--

LOCK TABLES `llx_c_regions` WRITE;
/*!40000 ALTER TABLE `llx_c_regions` DISABLE KEYS */;
INSERT INTO `llx_c_regions` (`rowid`, `code_region`, `fk_pays`, `cheflieu`, `tncc`, `nom`, `active`) VALUES (1,0,0,'0',0,'-',1),(101,1,1,'97105',3,'Guadeloupe',1),(102,2,1,'97209',3,'Martinique',1),(103,3,1,'97302',3,'Guyane',1),(104,4,1,'97411',3,'Réunion',1),(105,11,1,'75056',1,'Île-de-France',1),(106,21,1,'51108',0,'Champagne-Ardenne',1),(107,22,1,'80021',0,'Picardie',1),(108,23,1,'76540',0,'Haute-Normandie',1),(109,24,1,'45234',2,'Centre',1),(110,25,1,'14118',0,'Basse-Normandie',1),(111,26,1,'21231',0,'Bourgogne',1),(112,31,1,'59350',2,'Nord-Pas-de-Calais',1),(113,41,1,'57463',0,'Lorraine',1),(114,42,1,'67482',1,'Alsace',1),(115,43,1,'25056',0,'Franche-Comté',1),(116,52,1,'44109',4,'Pays de la Loire',1),(117,53,1,'35238',0,'Bretagne',1),(118,54,1,'86194',2,'Poitou-Charentes',1),(119,72,1,'33063',1,'Aquitaine',1),(120,73,1,'31555',0,'Midi-Pyrénées',1),(121,74,1,'87085',2,'Limousin',1),(122,82,1,'69123',2,'Rhône-Alpes',1),(123,83,1,'63113',1,'Auvergne',1),(124,91,1,'34172',2,'Languedoc-Roussillon',1),(125,93,1,'13055',0,'Provence-Alpes-Côte d\'Azur',1),(126,94,1,'2A004',0,'Corse',1),(201,201,2,'',1,'Flandre',1),(202,202,2,'',2,'Wallonie',1),(203,203,2,'',3,'Bruxelles-Capitale',1),(301,301,3,NULL,1,'Abruzzo',1),(302,302,3,NULL,1,'Basilicata',1),(303,303,3,NULL,1,'Calabria',1),(304,304,3,NULL,1,'Campania',1),(305,305,3,NULL,1,'Emilia-Romagna',1),(306,306,3,NULL,1,'Friuli-Venezia Giulia',1),(307,307,3,NULL,1,'Lazio',1),(308,308,3,NULL,1,'Liguria',1),(309,309,3,NULL,1,'Lombardia',1),(310,310,3,NULL,1,'Marche',1),(311,311,3,NULL,1,'Molise',1),(312,312,3,NULL,1,'Piemonte',1),(313,313,3,NULL,1,'Puglia',1),(314,314,3,NULL,1,'Sardegna',1),(315,315,3,NULL,1,'Sicilia',1),(316,316,3,NULL,1,'Toscana',1),(317,317,3,NULL,1,'Trentino-Alto Adige',1),(318,318,3,NULL,1,'Umbria',1),(319,319,3,NULL,1,'Valle d Aosta',1),(320,320,3,NULL,1,'Veneto',1),(401,401,4,'',0,'Andalucia',1),(402,402,4,'',0,'Aragón',1),(403,403,4,'',0,'Castilla y León',1),(404,404,4,'',0,'Castilla la Mancha',1),(405,405,4,'',0,'Canarias',1),(406,406,4,'',0,'Cataluña',1),(407,407,4,'',0,'Comunidad de Ceuta',1),(408,408,4,'',0,'Comunidad Foral de Navarra',1),(409,409,4,'',0,'Comunidad de Melilla',1),(410,410,4,'',0,'Cantabria',1),(411,411,4,'',0,'Comunidad Valenciana',1),(412,412,4,'',0,'Extemadura',1),(413,413,4,'',0,'Galicia',1),(414,414,4,'',0,'Islas Baleares',1),(415,415,4,'',0,'La Rioja',1),(416,416,4,'',0,'Comunidad de Madrid',1),(417,417,4,'',0,'Región de Murcia',1),(418,418,4,'',0,'Principado de Asturias',1),(419,419,4,'',0,'Pais Vasco',1),(420,420,4,'',0,'Otros',1),(601,601,6,'',1,'Cantons',1),(1001,1001,10,'',0,'Ariana',1),(1002,1002,10,'',0,'Béja',1),(1003,1003,10,'',0,'Ben Arous',1),(1004,1004,10,'',0,'Bizerte',1),(1005,1005,10,'',0,'Gabès',1),(1006,1006,10,'',0,'Gafsa',1),(1007,1007,10,'',0,'Jendouba',1),(1008,1008,10,'',0,'Kairouan',1),(1009,1009,10,'',0,'Kasserine',1),(1010,1010,10,'',0,'Kébili',1),(1011,1011,10,'',0,'La Manouba',1),(1012,1012,10,'',0,'Le Kef',1),(1013,1013,10,'',0,'Mahdia',1),(1014,1014,10,'',0,'Médenine',1),(1015,1015,10,'',0,'Monastir',1),(1016,1016,10,'',0,'Nabeul',1),(1017,1017,10,'',0,'Sfax',1),(1018,1018,10,'',0,'Sidi Bouzid',1),(1019,1019,10,'',0,'Siliana',1),(1020,1020,10,'',0,'Sousse',1),(1021,1021,10,'',0,'Tataouine',1),(1022,1022,10,'',0,'Tozeur',1),(1023,1023,10,'',0,'Tunis',1),(1024,1024,10,'',0,'Zaghouan',1),(1101,1101,11,'',0,'United-States',1),(2301,2301,23,'',0,'Norte',1),(2302,2302,23,'',0,'Litoral',1),(2303,2303,23,'',0,'Cuyana',1),(2304,2304,23,'',0,'Central',1),(2305,2305,23,'',0,'Patagonia',1),(2801,2801,28,'',0,'Australia',1),(4601,4601,46,'',0,'Barbados',1),(6701,6701,67,NULL,NULL,'Tarapacá',1),(6702,6702,67,NULL,NULL,'Antofagasta',1),(6703,6703,67,NULL,NULL,'Atacama',1),(6704,6704,67,NULL,NULL,'Coquimbo',1),(6705,6705,67,NULL,NULL,'Valparaíso',1),(6706,6706,67,NULL,NULL,'General Bernardo O Higgins',1),(6707,6707,67,NULL,NULL,'Maule',1),(6708,6708,67,NULL,NULL,'Biobío',1),(6709,6709,67,NULL,NULL,'Raucanía',1),(6710,6710,67,NULL,NULL,'Los Lagos',1),(6711,6711,67,NULL,NULL,'Aysén General Carlos Ibáñez del Campo',1),(6712,6712,67,NULL,NULL,'Magallanes y Antártica Chilena',1),(6713,6713,67,NULL,NULL,'Santiago',1),(6714,6714,67,NULL,NULL,'Los Ríos',1),(6715,6715,67,NULL,NULL,'Arica y Parinacota',1),(7001,7001,70,'',0,'Colombie',1),(8601,8601,86,NULL,NULL,'Central',1),(8602,8602,86,NULL,NULL,'Oriental',1),(8603,8603,86,NULL,NULL,'Occidental',1),(11401,11401,114,'',0,'Honduras',1),(11701,11701,117,'',0,'India',1),(15201,15201,152,'',0,'Rivière Noire',1),(15202,15202,152,'',0,'Flacq',1),(15203,15203,152,'',0,'Grand Port',1),(15204,15204,152,'',0,'Moka',1),(15205,15205,152,'',0,'Pamplemousses',1),(15206,15206,152,'',0,'Plaines Wilhems',1),(15207,15207,152,'',0,'Port-Louis',1),(15208,15208,152,'',0,'Rivière du Rempart',1),(15209,15209,152,'',0,'Savanne',1),(15210,15210,152,'',0,'Rodrigues',1),(15211,15211,152,'',0,'Les îles Agaléga',1),(15212,15212,152,'',0,'Les écueils des Cargados Carajos',1),(15401,15401,154,'',0,'Mexique',1),(23201,23201,232,'',0,'Los Andes',1),(23202,23202,232,'',0,'Capital',1),(23203,23203,232,'',0,'Central',1),(23204,23204,232,'',0,'Cento Occidental',1),(23205,23205,232,'',0,'Guayana',1),(23206,23206,232,'',0,'Insular',1),(23207,23207,232,'',0,'Los Llanos',1),(23208,23208,232,'',0,'Nor-Oriental',1),(23209,23209,232,'',0,'Zuliana',1);
/*!40000 ALTER TABLE `llx_c_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_shipment_mode`
--

DROP TABLE IF EXISTS `llx_c_shipment_mode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_shipment_mode` (
  `rowid` int(11) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `description` text,
  `active` tinyint(4) DEFAULT '0',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_shipment_mode`
--

LOCK TABLES `llx_c_shipment_mode` WRITE;
/*!40000 ALTER TABLE `llx_c_shipment_mode` DISABLE KEYS */;
INSERT INTO `llx_c_shipment_mode` (`rowid`, `tms`, `code`, `libelle`, `description`, `active`, `module`) VALUES (1,'2010-10-09 23:43:16','CATCH','Catch','Catch by client',1,NULL),(2,'2010-10-09 23:43:16','TRANS','Transporter','Generic transporter',1,NULL),(3,'2010-10-09 23:43:16','COLSUI','Colissimo Suivi','Colissimo Suivi',0,NULL),(4,'2011-07-18 17:28:27','LETTREMAX','Lettre Max','Courrier Suivi et Lettre Max',0,NULL);
/*!40000 ALTER TABLE `llx_c_shipment_mode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_source`
--

DROP TABLE IF EXISTS `llx_c_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_source` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_source`
--

LOCK TABLES `llx_c_source` WRITE;
/*!40000 ALTER TABLE `llx_c_source` DISABLE KEYS */;
INSERT INTO `llx_c_source` (`rowid`, `code`, `label`, `active`) VALUES (1,'SRC_00','Proposition commerciale',1),(2,'SRC_01','Internet',1),(3,'SRC_02','Campagne courrier',1),(4,'SRC_03','Campagne téléphone',1),(5,'SRC_04','Campagne fax',1),(6,'SRC_05','Commercial',1),(7,'SRC_06','Magasin',1);
/*!40000 ALTER TABLE `llx_c_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_stcomm`
--

DROP TABLE IF EXISTS `llx_c_stcomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_stcomm` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_stcomm`
--

LOCK TABLES `llx_c_stcomm` WRITE;
/*!40000 ALTER TABLE `llx_c_stcomm` DISABLE KEYS */;
INSERT INTO `llx_c_stcomm` (`id`, `code`, `libelle`, `active`) VALUES (-1,'ST_NO','Ne pas contacter',1),(0,'ST_NEVER','Jamais contacté',1),(1,'ST_TODO','A contacter',1),(2,'ST_PEND','Contact en cours',1),(3,'ST_DONE','Contactée',1);
/*!40000 ALTER TABLE `llx_c_stcomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_tva`
--

DROP TABLE IF EXISTS `llx_c_tva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_tva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pays` int(11) NOT NULL,
  `taux` double NOT NULL,
  `localtax1` double NOT NULL DEFAULT '0',
  `localtax2` double NOT NULL DEFAULT '0',
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `accountancy_code` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2462 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_tva`
--

LOCK TABLES `llx_c_tva` WRITE;
/*!40000 ALTER TABLE `llx_c_tva` DISABLE KEYS */;
INSERT INTO `llx_c_tva` (`rowid`, `fk_pays`, `taux`, `localtax1`, `localtax2`, `recuperableonly`, `note`, `active`, `accountancy_code`) VALUES (11,1,19.6,0,0,0,'VAT standard rate (France hors DOM-TOM)',1,NULL),(12,1,8.5,0,0,0,'VAT standard rate (DOM sauf Guyane et Saint-Martin)',0,NULL),(13,1,8.5,0,0,1,'VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0,NULL),(14,1,5.5,0,0,0,'VAT reduced rate (France hors DOM-TOM)',1,NULL),(15,1,0,0,0,0,'VAT Rate 0 ou non applicable',1,NULL),(16,1,2.1,0,0,0,'VAT super-reduced rate',1,NULL),(21,2,21,0,0,0,'VAT standard rate',1,NULL),(22,2,6,0,0,0,'VAT reduced rate',1,NULL),(23,2,0,0,0,0,'VAT Rate 0 ou non applicable',1,NULL),(24,2,12,0,0,0,'VAT reduced rate',1,NULL),(31,3,20,0,0,0,'VAT standard rate',1,NULL),(32,3,10,0,0,0,'VAT reduced rate',1,NULL),(33,3,4,0,0,0,'VAT super-reduced rate',1,NULL),(34,3,0,0,0,0,'VAT Rate 0',1,NULL),(41,4,18,4,0,0,'VAT standard rate',1,NULL),(42,4,8,1,0,0,'VAT reduced rate',1,NULL),(43,4,4,0.5,0,0,'VAT super-reduced rate',1,NULL),(44,4,0,0,0,0,'VAT Rate 0',1,NULL),(51,5,19,0,0,0,'VAT standard rate',1,NULL),(52,5,7,0,0,0,'VAT reduced rate',1,NULL),(53,5,0,0,0,0,'VAT Rate 0',1,NULL),(61,6,7.6,0,0,0,'VAT standard rate',1,NULL),(62,6,3.6,0,0,0,'VAT reduced rate',1,NULL),(63,6,2.4,0,0,0,'VAT super-reduced rate',1,NULL),(64,6,0,0,0,0,'VAT Rate 0',1,NULL),(71,7,20,0,0,0,'VAT standard rate',1,NULL),(72,7,17.5,0,0,0,'VAT standard rate before 2011',1,NULL),(73,7,5,0,0,0,'VAT reduced rate',1,NULL),(74,7,0,0,0,0,'VAT Rate 0',1,NULL),(91,9,17,0,0,0,'VAT standard rate',1,NULL),(92,9,13,0,0,0,'VAT reduced rate 0',1,NULL),(93,9,3,0,0,0,'VAT super reduced rate 0',1,NULL),(94,9,0,0,0,0,'VAT Rate 0',1,NULL),(101,10,6,0,0,0,'VAT 6%',1,NULL),(102,10,12,0,0,0,'VAT 12%',1,NULL),(103,10,18,0,0,0,'VAT 18%',1,NULL),(104,10,7.5,0,0,0,'VAT 6% Majoré à 25% (7.5%)',1,NULL),(105,10,15,0,0,0,'VAT 12% Majoré à 25% (15%)',1,NULL),(106,10,22.5,0,0,0,'VAT 18% Majoré à 25% (22.5%)',1,NULL),(107,10,0,0,0,0,'VAT Rate 0',1,NULL),(111,11,0,0,0,0,'No Sales Tax',1,NULL),(112,11,4,0,0,0,'Sales Tax 4%',1,NULL),(113,11,6,0,0,0,'Sales Tax 6%',1,NULL),(121,12,20,0,0,0,'VAT standard rate',1,NULL),(122,12,14,0,0,0,'VAT reduced rate',1,NULL),(123,12,10,0,0,0,'VAT reduced rate',1,NULL),(124,12,7,0,0,0,'VAT super-reduced rate',1,NULL),(125,12,0,0,0,0,'VAT Rate 0',1,NULL),(141,14,7,0,0,0,'VAT standard rate',1,NULL),(142,14,0,0,0,0,'VAT Rate 0',1,NULL),(171,17,19,0,0,0,'VAT standard rate',1,NULL),(172,17,6,0,0,0,'VAT reduced rate',1,NULL),(173,17,0,0,0,0,'VAT Rate 0',1,NULL),(201,20,25,0,0,0,'VAT standard rate',1,NULL),(202,20,12,0,0,0,'VAT reduced rate',1,NULL),(203,20,6,0,0,0,'VAT super-reduced rate',1,NULL),(204,20,0,0,0,0,'VAT Rate 0',1,NULL),(231,23,21,0,0,0,'IVA standard rate',1,NULL),(232,23,10.5,0,0,0,'IVA reduced rate',1,NULL),(233,23,0,0,0,0,'IVA Rate 0',1,NULL),(251,25,20,0,0,0,'VAT standard rate',1,NULL),(252,25,12,0,0,0,'VAT reduced rate',1,NULL),(253,25,0,0,0,0,'VAT Rate 0',1,NULL),(254,25,5,0,0,0,'VAT reduced rate',1,NULL),(281,28,10,0,0,0,'VAT standard rate',1,NULL),(282,28,0,0,0,0,'VAT Rate 0',1,NULL),(411,41,20,0,0,0,'VAT standard rate',1,NULL),(412,41,10,0,0,0,'VAT reduced rate',1,NULL),(413,41,0,0,0,0,'VAT Rate 0',1,NULL),(461,46,0,0,0,0,'No VAT',1,NULL),(462,46,15,0,0,0,'VAT 15%',1,NULL),(463,46,7.5,0,0,0,'VAT 7.5%',1,NULL),(591,59,20,0,0,0,'VAT standard rate',1,NULL),(592,59,7,0,0,0,'VAT reduced rate',1,NULL),(593,59,0,0,0,0,'VAT Rate 0',1,NULL),(671,67,19,0,0,0,'VAT standard rate',1,NULL),(672,67,0,0,0,0,'VAT Rate 0',1,NULL),(801,80,25,0,0,0,'VAT standard rate',1,NULL),(802,80,0,0,0,0,'VAT Rate 0',1,NULL),(861,86,13,0,0,0,'IVA 13',1,NULL),(862,86,0,0,0,0,'SIN IVA',1,NULL),(1141,114,0,0,0,0,'No ISV',1,NULL),(1142,114,12,0,0,0,'ISV 12%',1,NULL),(1161,116,25.5,0,0,0,'VAT standard rate',1,NULL),(1162,116,7,0,0,0,'VAT reduced rate',1,NULL),(1163,116,0,0,0,0,'VAT rate 0',1,NULL),(1171,117,12.5,0,0,0,'VAT standard rate',1,NULL),(1172,117,4,0,0,0,'VAT reduced rate',1,NULL),(1173,117,1,0,0,0,'VAT super-reduced rate',1,NULL),(1174,117,0,0,0,0,'VAT Rate 0',1,NULL),(1231,123,0,0,0,0,'VAT Rate 0',1,NULL),(1232,123,5,0,0,0,'VAT Rate 5',1,NULL),(1401,140,15,0,0,0,'VAT standard rate',1,NULL),(1402,140,12,0,0,0,'VAT reduced rate',1,NULL),(1403,140,6,0,0,0,'VAT reduced rate',1,NULL),(1404,140,3,0,0,0,'VAT super-reduced rate',1,NULL),(1405,140,0,0,0,0,'VAT Rate 0',1,NULL),(1521,152,0,0,0,0,'VAT Rate 0',1,NULL),(1522,152,15,0,0,0,'VAT Rate 15',1,NULL),(1541,154,0,0,0,0,'No VAT',1,NULL),(1542,154,16,0,0,0,'VAT 16%',1,NULL),(1543,154,10,0,0,0,'VAT Frontero',1,NULL),(1662,166,15,0,0,0,'VAT standard rate',1,NULL),(1663,166,0,0,0,0,'VAT Rate 0',1,NULL),(1731,173,25,0,0,0,'VAT standard rate',1,NULL),(1732,173,14,0,0,0,'VAT reduced rate',1,NULL),(1733,173,8,0,0,0,'VAT reduced rate',1,NULL),(1734,173,0,0,0,0,'VAT Rate 0',1,NULL),(1841,184,20,0,0,0,'VAT standard rate',1,NULL),(1842,184,7,0,0,0,'VAT reduced rate',1,NULL),(1843,184,3,0,0,0,'VAT reduced rate',1,NULL),(1844,184,0,0,0,0,'VAT Rate 0',1,NULL),(1881,188,24,0,0,0,'VAT standard rate',1,NULL),(1882,188,9,0,0,0,'VAT reduced rate',1,NULL),(1883,188,0,0,0,0,'VAT Rate 0',1,NULL),(1884,188,5,0,0,0,'VAT reduced rate',1,NULL),(1931,193,0,0,0,0,'No VAT in SPM',1,NULL),(2011,201,19,0,0,0,'VAT standard rate',1,NULL),(2012,201,10,0,0,0,'VAT reduced rate',1,NULL),(2013,201,0,0,0,0,'VAT Rate 0',1,NULL),(2021,202,20,0,0,0,'VAT standard rate',1,NULL),(2022,202,8.5,0,0,0,'VAT reduced rate',1,NULL),(2023,202,0,0,0,0,'VAT Rate 0',1,NULL),(2321,232,0,0,0,0,'No VAT',1,NULL),(2322,232,12,0,0,0,'VAT 12%',1,NULL),(2323,232,8,0,0,0,'VAT 8%',1,NULL),(2461,246,0,0,0,0,'VAT Rate 0',1,NULL);
/*!40000 ALTER TABLE `llx_c_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_contact`
--

DROP TABLE IF EXISTS `llx_c_type_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_type_contact` (
  `rowid` int(11) NOT NULL,
  `element` varchar(30) NOT NULL,
  `source` varchar(8) NOT NULL DEFAULT 'external',
  `code` varchar(16) NOT NULL,
  `libelle` varchar(64) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_c_type_contact_uk` (`element`,`source`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_type_contact`
--

LOCK TABLES `llx_c_type_contact` WRITE;
/*!40000 ALTER TABLE `llx_c_type_contact` DISABLE KEYS */;
INSERT INTO `llx_c_type_contact` (`rowid`, `element`, `source`, `code`, `libelle`, `active`, `module`) VALUES (10,'contrat','internal','SALESREPSIGN','Commercial signataire du contrat',1,NULL),(11,'contrat','internal','SALESREPFOLL','Commercial suivi du contrat',1,NULL),(20,'contrat','external','BILLING','Contact client facturation contrat',1,NULL),(21,'contrat','external','CUSTOMER','Contact client suivi contrat',1,NULL),(22,'contrat','external','SALESREPSIGN','Contact client signataire contrat',1,NULL),(31,'propal','internal','SALESREPFOLL','Commercial à l\'origine de la propale',1,NULL),(40,'propal','external','BILLING','Contact client facturation propale',1,NULL),(41,'propal','external','CUSTOMER','Contact client suivi propale',1,NULL),(50,'facture','internal','SALESREPFOLL','Responsable suivi du paiement',1,NULL),(60,'facture','external','BILLING','Contact client facturation',1,NULL),(61,'facture','external','SHIPPING','Contact client livraison',1,NULL),(62,'facture','external','SERVICE','Contact client prestation',1,NULL),(70,'invoice_supplier','internal','SALESREPFOLL','Responsable suivi du paiement',1,NULL),(71,'invoice_supplier','external','BILLING','Contact fournisseur facturation',1,NULL),(72,'invoice_supplier','external','SHIPPING','Contact fournisseur livraison',1,NULL),(73,'invoice_supplier','external','SERVICE','Contact fournisseur prestation',1,NULL),(91,'commande','internal','SALESREPFOLL','Responsable suivi de la commande',1,NULL),(100,'commande','external','BILLING','Contact client facturation commande',1,NULL),(101,'commande','external','CUSTOMER','Contact client suivi commande',1,NULL),(102,'commande','external','SHIPPING','Contact client livraison commande',1,NULL),(120,'fichinter','internal','INTERREPFOLL','Responsable suivi de l\'intervention',1,NULL),(121,'fichinter','internal','INTERVENING','Intervenant',1,NULL),(130,'fichinter','external','BILLING','Contact client facturation intervention',1,NULL),(131,'fichinter','external','CUSTOMER','Contact client suivi de l\'intervention',1,NULL),(140,'order_supplier','internal','SALESREPFOLL','Responsable suivi de la commande',1,NULL),(141,'order_supplier','internal','SHIPPING','Responsable réception de la commande',1,NULL),(142,'order_supplier','external','BILLING','Contact fournisseur facturation commande',1,NULL),(143,'order_supplier','external','CUSTOMER','Contact fournisseur suivi commande',1,NULL),(145,'order_supplier','external','SHIPPING','Contact fournisseur livraison commande',1,NULL),(160,'project','internal','PROJECTLEADER','Chef de Projet',1,NULL),(161,'project','internal','CONTRIBUTOR','Intervenant',1,NULL),(170,'project','external','PROJECTLEADER','Chef de Projet',1,NULL),(171,'project','external','CONTRIBUTOR','Intervenant',1,NULL),(180,'project_task','internal','TASKEXECUTIVE','Responsable',1,NULL),(181,'project_task','internal','CONTRIBUTOR','Intervenant',1,NULL),(190,'project_task','external','TASKEXECUTIVE','Responsable',1,NULL),(191,'project_task','external','CONTRIBUTOR','Intervenant',1,NULL),(200,'societe','external','GENERALREF','Généraliste (référent)',0,'cabinetmed'),(201,'societe','external','GENERALISTE','Généraliste',0,'cabinetmed'),(210,'societe','external','SPECCHIROR','Chirurgien ortho',0,'cabinetmed'),(211,'societe','external','SPECCHIROT','Chirurgien autre',0,'cabinetmed'),(220,'societe','external','SPECDERMA','Dermatologue',0,'cabinetmed'),(225,'societe','external','SPECENDOC','Endocrinologue',0,'cabinetmed'),(230,'societe','external','SPECGYNECO','Gynécologue',0,'cabinetmed'),(240,'societe','external','SPECGASTRO','Gastroantérologue',0,'cabinetmed'),(245,'societe','external','SPECINTERNE','Interniste',0,'cabinetmed'),(250,'societe','external','SPECCARDIO','Cardiologue',0,'cabinetmed'),(260,'societe','external','SPECNEPHRO','Néphrologue',0,'cabinetmed'),(263,'societe','external','SPECPNEUMO','Pneumologue',0,'cabinetmed'),(265,'societe','external','SPECNEURO','Neurologue',0,'cabinetmed'),(270,'societe','external','SPECRHUMATO','Rhumatologue',0,'cabinetmed'),(280,'societe','external','KINE','Kinésithérapeute',0,'cabinetmed');
/*!40000 ALTER TABLE `llx_c_type_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_fees`
--

DROP TABLE IF EXISTS `llx_c_type_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_type_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_type_fees`
--

LOCK TABLES `llx_c_type_fees` WRITE;
/*!40000 ALTER TABLE `llx_c_type_fees` DISABLE KEYS */;
INSERT INTO `llx_c_type_fees` (`id`, `code`, `libelle`, `active`, `module`) VALUES (1,'TF_OTHER','Other',1,NULL),(2,'TF_TRIP','Trip',1,NULL),(3,'TF_LUNCH','Lunch',1,NULL);
/*!40000 ALTER TABLE `llx_c_type_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_typent`
--

DROP TABLE IF EXISTS `llx_c_typent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_typent` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_typent`
--

LOCK TABLES `llx_c_typent` WRITE;
/*!40000 ALTER TABLE `llx_c_typent` DISABLE KEYS */;
INSERT INTO `llx_c_typent` (`id`, `code`, `libelle`, `active`, `module`) VALUES (0,'TE_UNKNOWN','-',1,NULL),(1,'TE_STARTUP','Start-up',0,NULL),(2,'TE_GROUP','Grand groupe',1,NULL),(3,'TE_MEDIUM','PME/PMI',1,NULL),(4,'TE_SMALL','TPE',1,NULL),(5,'TE_ADMIN','Administration',1,NULL),(6,'TE_WHOLE','Grossiste',0,NULL),(7,'TE_RETAIL','Revendeur',0,NULL),(8,'TE_PRIVATE','Particulier',1,NULL),(100,'TE_OTHER','Autres',1,NULL),(101,'TE_HOMME','Homme',0,'cabinetmed'),(102,'TE_FEMME','Femme',0,'cabinetmed');
/*!40000 ALTER TABLE `llx_c_typent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_ziptown`
--

DROP TABLE IF EXISTS `llx_c_ziptown`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_ziptown` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(5) DEFAULT NULL,
  `fk_county` int(11) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL DEFAULT '0',
  `zip` varchar(10) NOT NULL,
  `town` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_ziptown_fk_pays` (`zip`,`town`,`fk_pays`),
  KEY `idx_c_ziptown_fk_county` (`fk_county`),
  KEY `idx_c_ziptown_fk_pays` (`fk_pays`),
  KEY `idx_c_ziptown_zip` (`zip`),
  CONSTRAINT `fk_c_ziptown_fk_county` FOREIGN KEY (`fk_county`) REFERENCES `llx_c_departements` (`rowid`),
  CONSTRAINT `fk_c_ziptown_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_ziptown`
--

LOCK TABLES `llx_c_ziptown` WRITE;
/*!40000 ALTER TABLE `llx_c_ziptown` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_c_ziptown` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie`
--

DROP TABLE IF EXISTS `llx_categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `fk_soc` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_categorie_ref` (`label`,`type`,`entity`),
  KEY `idx_categorie_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie`
--

LOCK TABLES `llx_categorie` WRITE;
/*!40000 ALTER TABLE `llx_categorie` DISABLE KEYS */;
INSERT INTO `llx_categorie` (`rowid`, `label`, `type`, `entity`, `description`, `fk_soc`, `visible`, `import_key`) VALUES (1,'MySupplierCategory',1,1,'This is description of category MyCategory for suppliers<br />',NULL,0,NULL),(2,'MyCategory',2,1,'This is description of MyCategory for customer and prospects<br />',NULL,0,NULL),(3,'Hot products',0,1,'This is description of hot products<br />',NULL,0,NULL),(4,'Cold products',0,1,'This is a description of cold products<br />',NULL,0,NULL),(5,'Sub category hot',0,1,'<br />',NULL,0,NULL),(6,'New products',0,1,'<br />',NULL,0,NULL),(7,'Homme',3,1,'<br />',NULL,0,NULL),(8,'Femme',3,1,'<br />',NULL,0,NULL),(9,'Forfait moyen',1,1,'<br />',NULL,0,NULL),(10,'XL Cutomers',2,1,'<br />',NULL,0,NULL),(11,'Forfait black',1,1,'',NULL,0,NULL);
/*!40000 ALTER TABLE `llx_categorie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_association`
--

DROP TABLE IF EXISTS `llx_categorie_association`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_association` (
  `fk_categorie_mere` int(11) NOT NULL,
  `fk_categorie_fille` int(11) NOT NULL,
  UNIQUE KEY `uk_categorie_association` (`fk_categorie_mere`,`fk_categorie_fille`),
  UNIQUE KEY `uk_categorie_association_fk_categorie_fille` (`fk_categorie_fille`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_fille` FOREIGN KEY (`fk_categorie_fille`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_mere` FOREIGN KEY (`fk_categorie_mere`) REFERENCES `llx_categorie` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_association`
--

LOCK TABLES `llx_categorie_association` WRITE;
/*!40000 ALTER TABLE `llx_categorie_association` DISABLE KEYS */;
INSERT INTO `llx_categorie_association` (`fk_categorie_mere`, `fk_categorie_fille`) VALUES (3,5),(9,11);
/*!40000 ALTER TABLE `llx_categorie_association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_fournisseur`
--

DROP TABLE IF EXISTS `llx_categorie_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_fournisseur` (
  `fk_categorie` int(11) NOT NULL,
  `fk_societe` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_societe`),
  KEY `idx_categorie_fournisseur_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_fournisseur_fk_societe` (`fk_societe`),
  CONSTRAINT `fk_categorie_fournisseur_fk_soc` FOREIGN KEY (`fk_societe`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_categorie_fournisseur_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_fournisseur`
--

LOCK TABLES `llx_categorie_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_categorie_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_categorie_fournisseur` (`fk_categorie`, `fk_societe`) VALUES (1,2),(9,2);
/*!40000 ALTER TABLE `llx_categorie_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_member`
--

DROP TABLE IF EXISTS `llx_categorie_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_member` (
  `fk_categorie` int(11) NOT NULL,
  `fk_member` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_member`),
  KEY `idx_categorie_member_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_member_fk_member` (`fk_member`),
  CONSTRAINT `fk_categorie_member_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_member_member_rowid` FOREIGN KEY (`fk_member`) REFERENCES `llx_adherent` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_member`
--

LOCK TABLES `llx_categorie_member` WRITE;
/*!40000 ALTER TABLE `llx_categorie_member` DISABLE KEYS */;
INSERT INTO `llx_categorie_member` (`fk_categorie`, `fk_member`) VALUES (7,2),(8,1);
/*!40000 ALTER TABLE `llx_categorie_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_product`
--

DROP TABLE IF EXISTS `llx_categorie_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_product` (
  `fk_categorie` int(11) NOT NULL,
  `fk_product` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_product`),
  KEY `idx_categorie_product_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_product_fk_product` (`fk_product`),
  CONSTRAINT `fk_categorie_product_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_product_product_rowid` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_product`
--

LOCK TABLES `llx_categorie_product` WRITE;
/*!40000 ALTER TABLE `llx_categorie_product` DISABLE KEYS */;
INSERT INTO `llx_categorie_product` (`fk_categorie`, `fk_product`) VALUES (5,1),(5,2),(5,3),(6,2),(6,3);
/*!40000 ALTER TABLE `llx_categorie_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_societe`
--

DROP TABLE IF EXISTS `llx_categorie_societe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_societe` (
  `fk_categorie` int(11) NOT NULL,
  `fk_societe` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_societe`),
  KEY `idx_categorie_societe_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_societe_fk_societe` (`fk_societe`),
  CONSTRAINT `fk_categorie_societe_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_societe_fk_soc` FOREIGN KEY (`fk_societe`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_societe`
--

LOCK TABLES `llx_categorie_societe` WRITE;
/*!40000 ALTER TABLE `llx_categorie_societe` DISABLE KEYS */;
INSERT INTO `llx_categorie_societe` (`fk_categorie`, `fk_societe`) VALUES (2,2),(10,4);
/*!40000 ALTER TABLE `llx_categorie_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_chargesociales`
--

DROP TABLE IF EXISTS `llx_chargesociales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_chargesociales` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_ech` datetime NOT NULL,
  `libelle` varchar(80) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_type` int(11) NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `periode` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_chargesociales`
--

LOCK TABLES `llx_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_chargesociales` (`rowid`, `date_ech`, `libelle`, `entity`, `fk_type`, `amount`, `paye`, `periode`, `tms`, `date_creation`, `date_valid`) VALUES (4,'2011-08-09 00:00:00','fff',1,60,10,1,'2011-08-01','2012-04-11 10:04:14',NULL,NULL);
/*!40000 ALTER TABLE `llx_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande`
--

DROP TABLE IF EXISTS `llx_commande`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(255) DEFAULT NULL,
  `ref_int` varchar(255) DEFAULT NULL,
  `ref_client` varchar(255) DEFAULT NULL,
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
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `facture` tinyint(4) DEFAULT '0',
  `fk_account` int(11) DEFAULT NULL,
  `fk_currency` varchar(2) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_availability` int(11) DEFAULT NULL,
  `fk_demand_reason` int(11) DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_ref` (`ref`,`entity`),
  KEY `idx_commande_fk_soc` (`fk_soc`),
  KEY `idx_commande_fk_user_author` (`fk_user_author`),
  KEY `idx_commande_fk_user_valid` (`fk_user_valid`),
  KEY `idx_commande_fk_user_cloture` (`fk_user_cloture`),
  KEY `idx_commande_fk_projet` (`fk_projet`),
  KEY `idx_commande_fk_account` (`fk_account`),
  KEY `idx_commande_fk_currency` (`fk_currency`),
  CONSTRAINT `fk_commande_fk_currency` FOREIGN KEY (`fk_currency`) REFERENCES `llx_c_currencies` (`code_iso`),
  CONSTRAINT `fk_commande_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_commande_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_commande_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_commande_fk_user_cloture` FOREIGN KEY (`fk_user_cloture`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_commande_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande`
--

LOCK TABLES `llx_commande` WRITE;
/*!40000 ALTER TABLE `llx_commande` DISABLE KEYS */;
INSERT INTO `llx_commande` (`rowid`, `tms`, `fk_soc`, `fk_projet`, `ref`, `entity`, `ref_ext`, `ref_int`, `ref_client`, `date_creation`, `date_valid`, `date_cloture`, `date_commande`, `fk_user_author`, `fk_user_valid`, `fk_user_cloture`, `source`, `fk_statut`, `amount_ht`, `remise_percent`, `remise_absolue`, `remise`, `tva`, `localtax1`, `localtax2`, `total_ht`, `total_ttc`, `note`, `note_public`, `model_pdf`, `facture`, `fk_account`, `fk_currency`, `fk_cond_reglement`, `fk_mode_reglement`, `date_livraison`, `fk_availability`, `fk_demand_reason`, `fk_adresse_livraison`, `import_key`, `extraparams`) VALUES (1,'2012-04-11 10:03:49',1,NULL,'CO1107-0002',1,NULL,NULL,'','2011-07-20 15:23:12','2011-08-08 13:59:09',NULL,'2011-07-20',1,1,NULL,NULL,1,0,0,NULL,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','',0,NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL),(2,'2012-04-11 10:03:49',1,NULL,'(PROV2)',1,NULL,NULL,'','2011-07-20 23:20:12',NULL,NULL,'2011-07-21',1,NULL,NULL,NULL,0,0,0,NULL,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','einstein',0,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL,NULL),(3,'2012-04-11 10:03:49',1,NULL,'(PROV3)',1,NULL,NULL,'','2011-07-20 23:22:53',NULL,NULL,'2011-07-21',1,NULL,NULL,NULL,0,0,0,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,'','','einstein',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'2012-04-11 10:03:49',1,NULL,'CO1108-0001',1,NULL,NULL,'','2011-08-08 03:04:11','2011-08-08 03:04:21',NULL,'2011-08-08',1,1,NULL,NULL,2,0,0,NULL,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','einstein',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_commande` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_soc` int(11) NOT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
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
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `fk_methode_commande` int(11) DEFAULT '0',
  `fk_cond_reglement` int(11) DEFAULT '0',
  `fk_mode_reglement` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_fournisseur_ref` (`ref`,`fk_soc`,`entity`),
  KEY `idx_commande_fournisseur_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fournisseur_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur`
--

LOCK TABLES `llx_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur` (`rowid`, `tms`, `fk_soc`, `ref`, `entity`, `ref_ext`, `ref_supplier`, `fk_projet`, `date_creation`, `date_valid`, `date_cloture`, `date_commande`, `fk_user_author`, `fk_user_valid`, `fk_user_cloture`, `source`, `fk_statut`, `amount_ht`, `remise_percent`, `remise`, `tva`, `localtax1`, `localtax2`, `total_ht`, `total_ttc`, `note`, `note_public`, `model_pdf`, `fk_methode_commande`, `fk_cond_reglement`, `fk_mode_reglement`, `import_key`, `extraparams`) VALUES (1,'2012-04-11 10:03:49',13,'CF1007-0001',1,NULL,NULL,NULL,'2010-07-11 17:13:40','2010-07-11 17:15:42',NULL,'2010-07-11',1,1,NULL,0,5,0,0,0,39.20000000,0.00000000,0.00000000,200.00000000,239.20000000,NULL,NULL,'muscadet',2,0,0,NULL,NULL),(2,'2012-04-11 10:03:49',1,'CF1007-0002',1,NULL,NULL,NULL,'2010-07-11 18:46:28','2010-07-11 18:47:33',NULL,'2010-07-11',1,1,NULL,0,3,0,0,0,0.00000000,0.00000000,0.00000000,200.00000000,200.00000000,NULL,NULL,'muscadet',4,0,0,NULL,NULL),(3,'2012-04-11 10:03:49',17,'(PROV3)',1,NULL,NULL,NULL,'2011-08-04 23:00:52',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL),(4,'2012-04-11 10:03:49',17,'(PROV4)',1,NULL,NULL,NULL,'2011-08-04 23:19:32',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL),(5,'2012-04-11 10:03:49',17,'(PROV5)',1,NULL,NULL,NULL,'2011-08-04 23:22:16',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL),(6,'2012-04-11 10:03:49',17,'CF1108-0003',1,NULL,NULL,NULL,'2011-08-04 23:22:54','2011-08-08 15:04:37',NULL,NULL,1,1,NULL,0,2,0,0,0,0.98000000,0.00000000,0.00000000,5.00000000,5.98000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL),(7,'2012-04-11 10:03:49',17,'(PROV7)',1,NULL,NULL,NULL,'2011-08-04 23:23:29',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL),(8,'2012-04-11 10:03:49',17,'(PROV8)',1,NULL,NULL,NULL,'2011-08-04 23:36:10',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL);
/*!40000 ALTER TABLE `llx_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_dispatch`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_dispatch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur_dispatch`
--

LOCK TABLES `llx_commande_fournisseur_dispatch` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_dispatch` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur_dispatch` (`rowid`, `fk_commande`, `fk_product`, `qty`, `fk_entrepot`, `fk_user`, `datec`) VALUES (1,2,4,2,1,1,'2010-07-11 18:49:44');
/*!40000 ALTER TABLE `llx_commande_fournisseur_dispatch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_log`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseur_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datelog` datetime NOT NULL,
  `fk_commande` int(11) NOT NULL,
  `fk_statut` smallint(6) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur_log`
--

LOCK TABLES `llx_commande_fournisseur_log` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur_log` (`rowid`, `tms`, `datelog`, `fk_commande`, `fk_statut`, `fk_user`, `comment`) VALUES (1,'2010-07-11 15:13:40','2010-07-11 17:13:40',1,0,1,NULL),(2,'2010-07-11 15:15:42','2010-07-11 17:15:42',1,1,1,NULL),(3,'2010-07-11 15:16:28','2010-07-11 17:16:28',1,2,1,NULL),(4,'2010-07-11 15:19:14','2010-07-11 00:00:00',1,3,1,NULL),(5,'2010-07-11 15:19:36','2010-07-11 00:00:00',1,5,1,NULL),(6,'2010-07-11 16:46:28','2010-07-11 18:46:28',2,0,1,NULL),(7,'2010-07-11 16:47:33','2010-07-11 18:47:33',2,1,1,NULL),(8,'2010-07-11 16:47:41','2010-07-11 18:47:41',2,2,1,NULL),(9,'2010-07-11 16:48:00','2010-07-11 00:00:00',2,3,1,NULL),(10,'2011-08-04 21:00:52','2011-08-04 23:00:52',3,0,1,NULL),(11,'2011-08-04 21:19:32','2011-08-04 23:19:32',4,0,1,NULL),(12,'2011-08-04 21:22:16','2011-08-04 23:22:16',5,0,1,NULL),(13,'2011-08-04 21:22:54','2011-08-04 23:22:54',6,0,1,NULL),(14,'2011-08-04 21:23:29','2011-08-04 23:23:29',7,0,1,NULL),(15,'2011-08-04 21:36:10','2011-08-04 23:36:10',8,0,1,NULL),(19,'2011-08-08 13:04:37','2011-08-08 15:04:37',6,1,1,NULL),(20,'2011-08-08 13:04:38','2011-08-08 15:04:38',6,2,1,NULL);
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseurdet`
--

DROP TABLE IF EXISTS `llx_commande_fournisseurdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseurdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseurdet`
--

LOCK TABLES `llx_commande_fournisseurdet` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseurdet` (`rowid`, `fk_commande`, `fk_product`, `ref`, `label`, `description`, `tva_tx`, `localtax1_tx`, `localtax2_tx`, `qty`, `remise_percent`, `remise`, `subprice`, `total_ht`, `total_tva`, `total_localtax1`, `total_localtax2`, `total_ttc`, `product_type`, `date_start`, `date_end`, `info_bits`) VALUES (1,1,NULL,'','','Chips',19.600,0.000,0.000,10,0,0,20.00000000,200.00000000,39.20000000,0.00000000,0.00000000,239.20000000,0,NULL,NULL,0),(2,2,4,'ABCD','Decapsuleur','',0.000,0.000,0.000,20,0,0,10.00000000,200.00000000,0.00000000,0.00000000,0.00000000,200.00000000,0,NULL,NULL,0),(3,6,NULL,'','','ljkljl',19.600,0.000,0.000,1,0,0,5.00000000,5.00000000,0.98000000,0.00000000,0.00000000,5.98000000,0,NULL,NULL,0);
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commandedet`
--

DROP TABLE IF EXISTS `llx_commandedet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commandedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT NULL,
  `localtax2_tx` double(6,3) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
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
  KEY `idx_commandedet_fk_product` (`fk_product`),
  CONSTRAINT `fk_commandedet_fk_commande` FOREIGN KEY (`fk_commande`) REFERENCES `llx_commande` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commandedet`
--

LOCK TABLES `llx_commandedet` WRITE;
/*!40000 ALTER TABLE `llx_commandedet` DISABLE KEYS */;
INSERT INTO `llx_commandedet` (`rowid`, `fk_commande`, `fk_parent_line`, `fk_product`, `description`, `tva_tx`, `localtax1_tx`, `localtax2_tx`, `qty`, `remise_percent`, `remise`, `fk_remise_except`, `price`, `subprice`, `total_ht`, `total_tva`, `total_localtax1`, `total_localtax2`, `total_ttc`, `product_type`, `date_start`, `date_end`, `info_bits`, `marge_tx`, `marque_tx`, `special_code`, `rang`, `import_key`) VALUES (1,1,NULL,NULL,'Product 1',0.000,0.000,0.000,1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,NULL,0,1,NULL),(2,1,NULL,2,'',0.000,0.000,0.000,1,0,0,NULL,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,NULL,0,2,NULL),(3,1,NULL,5,'cccc',0.000,0.000,0.000,1,0,0,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,NULL,0,3,NULL),(4,2,NULL,NULL,'hgf',0.000,0.000,0.000,1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,NULL,0,1,NULL),(10,5,NULL,NULL,'gfdgdf',0.000,0.000,0.000,1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,NULL,0,1,NULL);
/*!40000 ALTER TABLE `llx_commandedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta`
--

DROP TABLE IF EXISTS `llx_compta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_compta_account` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `number` varchar(12) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_compta_compte_generaux` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_creation` datetime DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `intitule` varchar(255) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_cond_reglement`
--

LOCK TABLES `llx_cond_reglement` WRITE;
/*!40000 ALTER TABLE `llx_cond_reglement` DISABLE KEYS */;
INSERT INTO `llx_cond_reglement` (`rowid`, `code`, `sortorder`, `active`, `libelle`, `libelle_facture`, `fdm`, `nbjour`, `decalage`) VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL);
/*!40000 ALTER TABLE `llx_cond_reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_const`
--

DROP TABLE IF EXISTS `llx_const`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_const` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `value` text NOT NULL,
  `type` varchar(6) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `note` text,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_const` (`name`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=1767 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_const`
--

LOCK TABLES `llx_const` WRITE;
/*!40000 ALTER TABLE `llx_const` DISABLE KEYS */;
INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES (2,'MAIN_FEATURES_LEVEL',0,'0','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development','2010-07-08 11:17:57'),(4,'SYSLOG_FILE',0,'DOL_DATA_ROOT/dolibarr.log','chaine',0,'Directory where to write log file','2010-07-08 11:17:57'),(5,'SYSLOG_LEVEL',0,'7','chaine',0,'Level of debug info to show','2010-07-08 11:17:57'),(8,'MAIN_UPLOAD_DOC',0,'2048','chaine',0,'Max size for file upload (0 means no upload allowed)','2010-07-08 11:17:57'),(9,'MAIN_SEARCHFORM_SOCIETE',0,'1','yesno',0,'Show form for quick company search','2010-07-08 11:17:57'),(10,'MAIN_SEARCHFORM_CONTACT',0,'1','yesno',0,'Show form for quick contact search','2010-07-08 11:17:57'),(11,'MAIN_SEARCHFORM_PRODUITSERVICE',0,'1','yesno',0,'Show form for quick product search','2010-07-08 11:17:58'),(12,'MAIN_SEARCHFORM_ADHERENT',0,'1','yesno',0,'Show form for quick member search','2010-07-08 11:17:58'),(16,'MAIN_SIZE_LISTE_LIMIT',0,'25','chaine',0,'Longueur maximum des listes','2010-07-08 11:17:58'),(17,'MAIN_SHOW_WORKBOARD',0,'1','yesno',0,'Affichage tableau de bord de travail Dolibarr','2010-07-08 11:17:58'),(23,'MAIN_DELAY_ACTIONS_TODO',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées','2010-07-08 11:17:58'),(24,'MAIN_DELAY_ORDERS_TO_PROCESS',1,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes non traitées','2010-07-08 11:17:58'),(25,'MAIN_DELAY_PROPALS_TO_CLOSE',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales à cloturer','2010-07-08 11:17:58'),(26,'MAIN_DELAY_PROPALS_TO_BILL',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales non facturées','2010-07-08 11:17:58'),(27,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',1,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées','2010-07-08 11:17:58'),(28,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures client impayées','2010-07-08 11:17:58'),(29,'MAIN_DELAY_NOT_ACTIVATED_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2010-07-08 11:17:58'),(30,'MAIN_DELAY_RUNNING_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services expirés','2010-07-08 11:17:58'),(31,'MAIN_DELAY_MEMBERS',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard','2010-07-08 11:17:58'),(32,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',1,'62','chaine',0,'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire','2010-07-08 11:17:58'),(33,'SOCIETE_NOLIST_COURRIER',0,'1','yesno',0,'Liste les fichiers du repertoire courrier','2010-07-08 11:17:58'),(35,'SOCIETE_CODECOMPTA_ADDON',1,'mod_codecompta_panicum','yesno',0,'Module to control third parties codes','2010-07-08 11:17:58'),(36,'ADHERENT_MAIL_REQUIRED',1,'1','yesno',0,'EMail required to create a new member','2010-07-08 11:17:58'),(37,'ADHERENT_MAIL_FROM',1,'adherents@domain.com','chaine',0,'Sender EMail for automatic emails','2010-07-08 11:17:58'),(38,'ADHERENT_MAIL_RESIL',1,'Your subscription has been resiliated.\r\nWe hope to see you soon again','texte',0,'Mail resiliation','2010-07-08 11:17:58'),(39,'ADHERENT_MAIL_VALID',1,'Your subscription has been validated.\r\nThis is a remind of your personal information :\r\n\r\n%INFOS%\r\n\r\n','texte',0,'Mail de validation','2010-07-08 11:17:59'),(40,'ADHERENT_MAIL_COTIS',1,'Hello %PRENOM%,\r\nThanks for your subscription.\r\nThis email confirms that your subscription has been received and processed.\r\n\r\n','texte',0,'Mail de validation de cotisation','2010-07-08 11:17:59'),(41,'ADHERENT_MAIL_VALID_SUBJECT',1,'Your subscription has been validated','chaine',0,'Sujet du mail de validation','2010-07-08 11:17:59'),(42,'ADHERENT_MAIL_RESIL_SUBJECT',1,'Resiliating your subscription','chaine',0,'Sujet du mail de resiliation','2010-07-08 11:17:59'),(43,'ADHERENT_MAIL_COTIS_SUBJECT',1,'Receipt of your subscription','chaine',0,'Sujet du mail de validation de cotisation','2010-07-08 11:17:59'),(44,'MAILING_EMAIL_FROM',1,'dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2010-07-08 11:17:59'),(45,'ADHERENT_USE_MAILMAN',1,'0','yesno',0,'Utilisation de Mailman','2010-07-08 11:17:59'),(46,'ADHERENT_MAILMAN_UNSUB_URL',1,'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%','chaine',0,'Url de desinscription aux listes mailman','2010-07-08 11:17:59'),(47,'ADHERENT_MAILMAN_URL',1,'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine',0,'Url pour les inscriptions mailman','2010-07-08 11:17:59'),(48,'ADHERENT_MAILMAN_LISTS',1,'test-test,test-test2','chaine',0,'Listes auxquelles inscrire les nouveaux adherents','2010-07-08 11:17:59'),(49,'ADHERENT_MAILMAN_ADMINPW',1,'','chaine',0,'Mot de passe Admin des liste mailman','2010-07-08 11:17:59'),(50,'ADHERENT_MAILMAN_SERVER',1,'lists.domain.com','chaine',0,'Serveur hebergeant les interfaces d Admin des listes mailman','2010-07-08 11:17:59'),(51,'ADHERENT_MAILMAN_LISTS_COTISANT',1,'','chaine',0,'Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement','2010-07-08 11:17:59'),(52,'ADHERENT_USE_SPIP',1,'0','yesno',0,'Utilisation de SPIP ?','2010-07-08 11:17:59'),(53,'ADHERENT_USE_SPIP_AUTO',1,'0','yesno',0,'Utilisation de SPIP automatiquement','2010-07-08 11:17:59'),(54,'ADHERENT_SPIP_USER',1,'user','chaine',0,'user spip','2010-07-08 11:17:59'),(55,'ADHERENT_SPIP_PASS',1,'pass','chaine',0,'Pass de connection','2010-07-08 11:17:59'),(56,'ADHERENT_SPIP_SERVEUR',1,'localhost','chaine',0,'serveur spip','2010-07-08 11:17:59'),(57,'ADHERENT_SPIP_DB',1,'spip','chaine',0,'db spip','2010-07-08 11:17:59'),(58,'ADHERENT_CARD_HEADER_TEXT',1,'%ANNEE%','chaine',0,'Texte imprime sur le haut de la carte adherent','2010-07-08 11:17:59'),(59,'ADHERENT_CARD_FOOTER_TEXT',1,'Association AZERTY','chaine',0,'Texte imprime sur le bas de la carte adherent','2010-07-08 11:17:59'),(61,'FCKEDITOR_ENABLE_USER',1,'1','yesno',0,'Activation fckeditor sur notes utilisateurs','2010-07-08 11:17:59'),(62,'FCKEDITOR_ENABLE_SOCIETE',1,'1','yesno',0,'Activation fckeditor sur notes societe','2010-07-08 11:17:59'),(63,'FCKEDITOR_ENABLE_PRODUCTDESC',1,'1','yesno',0,'Activation fckeditor sur notes produits','2010-07-08 11:17:59'),(64,'FCKEDITOR_ENABLE_MEMBER',1,'1','yesno',0,'Activation fckeditor sur notes adherent','2010-07-08 11:17:59'),(65,'FCKEDITOR_ENABLE_MAILING',1,'1','yesno',0,'Activation fckeditor sur emailing','2010-07-08 11:17:59'),(66,'OSC_DB_HOST',1,'localhost','chaine',0,'Host for OSC database for OSCommerce module 1','2010-07-08 11:17:59'),(67,'DON_ADDON_MODEL',1,'html_cerfafr','chaine',0,'','2010-07-08 11:18:00'),(68,'PROPALE_ADDON',1,'mod_propale_marbre','chaine',0,'','2010-07-08 11:18:00'),(69,'PROPALE_ADDON_PDF',1,'azur','chaine',0,'','2010-07-08 11:18:00'),(70,'COMMANDE_ADDON',1,'mod_commande_marbre','chaine',0,'','2010-07-08 11:18:00'),(71,'COMMANDE_ADDON_PDF',1,'einstein','chaine',0,'','2010-07-08 11:18:00'),(72,'COMMANDE_SUPPLIER_ADDON',1,'mod_commande_fournisseur_muguet','chaine',0,'','2010-07-08 11:18:00'),(73,'COMMANDE_SUPPLIER_ADDON_PDF',1,'muscadet','chaine',0,'','2010-07-08 11:18:00'),(74,'EXPEDITION_ADDON',1,'enlevement','chaine',0,'','2010-07-08 11:18:00'),(76,'FICHEINTER_ADDON',1,'pacific','chaine',0,'','2010-07-08 11:18:00'),(77,'FICHEINTER_ADDON_PDF',1,'soleil','chaine',0,'','2010-07-08 11:18:00'),(79,'FACTURE_ADDON_PDF',1,'crabe','chaine',0,'','2010-07-08 11:18:00'),(80,'PROPALE_VALIDITY_DURATION',1,'15','chaine',0,'Durée de validitée des propales','2010-07-08 11:18:00'),(230,'COMPANY_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/thirdparties','chaine',0,NULL,'2010-07-08 11:26:20'),(238,'LIVRAISON_ADDON_PDF',1,'typhon','chaine',0,'Nom du gestionnaire de generation des commandes en PDF','2010-07-08 11:26:27'),(239,'LIVRAISON_ADDON',1,'mod_livraison_jade','chaine',0,'Nom du gestionnaire de numerotation des bons de livraison','2010-07-08 11:26:27'),(242,'MAIN_SUBMODULE_EXPEDITION',1,'1','chaine',0,'','2010-07-08 11:26:34'),(245,'FACTURE_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/invoices','chaine',0,NULL,'2010-07-08 11:28:53'),(249,'DON_FORM',1,'fsfe.fr.php','chaine',0,'Nom du gestionnaire de formulaire de dons','2010-07-08 11:29:00'),(252,'MAIN_MODULE_ADHERENT',1,'1',NULL,0,NULL,'2010-07-08 11:29:05'),(253,'ADHERENT_BANK_USE_AUTO',1,'','yesno',0,'Insertion automatique des cotisation dans le compte banquaire','2010-07-08 11:29:05'),(254,'ADHERENT_BANK_ACCOUNT',1,'','chaine',0,'ID du Compte banquaire utilise','2010-07-08 11:29:05'),(255,'ADHERENT_BANK_CATEGORIE',1,'','chaine',0,'ID de la categorie banquaire des cotisations','2010-07-08 11:29:05'),(256,'ADHERENT_ETIQUETTE_TYPE',1,'L7163','chaine',0,'Type d etiquette (pour impression de planche d etiquette)','2010-07-08 11:29:05'),(260,'MAIN_MODULE_STOCK',1,'1',NULL,0,NULL,'2010-07-08 11:29:18'),(269,'PROJECT_ADDON_PDF',1,'baleine','chaine',0,'Nom du gestionnaire de generation des projets en PDF','2010-07-08 11:29:33'),(270,'PROJECT_ADDON',1,'mod_project_simple','chaine',0,'Nom du gestionnaire de numerotation des projets','2010-07-08 11:29:33'),(271,'MAIN_MODULE_MAILING',1,'1',NULL,0,NULL,'2010-07-08 11:29:37'),(272,'MAIN_MODULE_EXPORT',1,'1',NULL,0,NULL,'2010-07-08 11:29:41'),(273,'MAIN_MODULE_IMPORT',1,'1',NULL,0,NULL,'2010-07-08 11:29:45'),(274,'MAIN_MODULE_CATEGORIE',1,'1',NULL,0,NULL,'2010-07-08 11:29:59'),(275,'MAIN_MODULE_BOOKMARK',1,'1',NULL,0,NULL,'2010-07-08 11:30:03'),(276,'MAIN_MODULE_WEBSERVICES',1,'1',NULL,0,NULL,'2010-07-08 11:30:30'),(278,'MAIN_MODULE_GEOIPMAXMIND',1,'1',NULL,0,NULL,'2010-07-08 11:30:36'),(279,'MAIN_MODULE_EXTERNALRSS',1,'1',NULL,0,NULL,'2010-07-08 11:30:38'),(280,'MAIN_SECURITY_DISABLEFORGETPASSLINK',1,'1','chaine',0,'','2010-07-08 11:32:59'),(292,'MAIN_MODULE_FCKEDITOR',1,'1',NULL,0,NULL,'2010-07-08 11:56:27'),(368,'STOCK_USERSTOCK_AUTOCREATE',1,'1','chaine',0,'','2010-07-08 22:44:59'),(369,'EXPEDITION_ADDON_PDF',1,'merou','chaine',0,'','2010-07-08 22:58:07'),(370,'MAIN_SUBMODULE_LIVRAISON',1,'1','chaine',0,'','2010-07-08 23:00:29'),(377,'FACTURE_ADDON',1,'mod_facture_terre','chaine',0,'','2010-07-08 23:08:12'),(380,'ADHERENT_CARD_TEXT',1,'%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','',0,'Texte imprime sur la carte adherent','2010-07-08 23:14:46'),(381,'ADHERENT_CARD_TEXT_RIGHT',1,'aaa','',0,'','2010-07-08 23:14:55'),(384,'PRODUIT_SOUSPRODUITS',1,'1','chaine',0,'','2010-07-08 23:22:12'),(385,'PRODUIT_USE_SEARCH_TO_SELECT',1,'1','chaine',0,'','2010-07-08 23:22:19'),(386,'STOCK_CALCULATE_ON_SHIPMENT',1,'1','chaine',0,'','2010-07-08 23:23:21'),(387,'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER',1,'1','chaine',0,'','2010-07-08 23:23:26'),(392,'MAIN_AGENDA_XCAL_EXPORTKEY',1,'dolibarr','chaine',0,'','2010-07-08 23:27:50'),(393,'MAIN_AGENDA_EXPORT_PAST_DELAY',1,'100','chaine',0,'','2010-07-08 23:27:50'),(523,'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(524,'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(525,'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(526,'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(527,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(528,'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(529,'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(530,'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED',1,'1','chaine',0,'','2010-07-10 12:48:49'),(531,'MAIN_AGENDA_ACTIONAUTO_BILL_CANCEL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(532,'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(533,'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:50'),(534,'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:50'),(602,'MAIN_MODULE_PROJET',1,'1',NULL,0,NULL,'2010-07-11 13:26:54'),(610,'CASHDESK_ID_THIRDPARTY',1,'7','chaine',0,'','2010-07-11 17:08:18'),(611,'CASHDESK_ID_BANKACCOUNT_CASH',1,'3','chaine',0,'','2010-07-11 17:08:18'),(612,'CASHDESK_ID_BANKACCOUNT_CHEQUE',1,'1','chaine',0,'','2010-07-11 17:08:18'),(613,'CASHDESK_ID_BANKACCOUNT_CB',1,'1','chaine',0,'','2010-07-11 17:08:18'),(614,'CASHDESK_ID_WAREHOUSE',1,'2','chaine',0,'','2010-07-11 17:08:18'),(660,'LDAP_USER_DN',1,'ou=users,dc=my-domain,dc=com','chaine',0,NULL,'2010-07-18 10:25:27'),(661,'LDAP_GROUP_DN',1,'ou=groups,dc=my-domain,dc=com','chaine',0,NULL,'2010-07-18 10:25:27'),(662,'LDAP_FILTER_CONNECTION',1,'&(objectClass=user)(objectCategory=person)','chaine',0,NULL,'2010-07-18 10:25:27'),(663,'LDAP_FIELD_LOGIN',1,'uid','chaine',0,NULL,'2010-07-18 10:25:27'),(664,'LDAP_FIELD_FULLNAME',1,'cn','chaine',0,NULL,'2010-07-18 10:25:27'),(665,'LDAP_FIELD_NAME',1,'sn','chaine',0,NULL,'2010-07-18 10:25:27'),(666,'LDAP_FIELD_FIRSTNAME',1,'givenname','chaine',0,NULL,'2010-07-18 10:25:27'),(667,'LDAP_FIELD_MAIL',1,'mail','chaine',0,NULL,'2010-07-18 10:25:27'),(668,'LDAP_FIELD_PHONE',1,'telephonenumber','chaine',0,NULL,'2010-07-18 10:25:27'),(669,'LDAP_FIELD_FAX',1,'facsimiletelephonenumber','chaine',0,NULL,'2010-07-18 10:25:27'),(670,'LDAP_FIELD_MOBILE',1,'mobile','chaine',0,NULL,'2010-07-18 10:25:27'),(671,'LDAP_SERVER_TYPE',1,'openldap','chaine',0,'','2010-07-18 10:25:46'),(672,'LDAP_SERVER_PROTOCOLVERSION',1,'3','chaine',0,'','2010-07-18 10:25:47'),(673,'LDAP_SERVER_HOST',1,'localhost','chaine',0,'','2010-07-18 10:25:47'),(674,'LDAP_SERVER_PORT',1,'389','chaine',0,'','2010-07-18 10:25:47'),(675,'LDAP_SERVER_USE_TLS',1,'0','chaine',0,'','2010-07-18 10:25:47'),(676,'LDAP_SYNCHRO_ACTIVE',1,'dolibarr2ldap','chaine',0,'','2010-07-18 10:25:47'),(677,'LDAP_CONTACT_ACTIVE',1,'1','chaine',0,'','2010-07-18 10:25:47'),(678,'LDAP_MEMBER_ACTIVE',1,'1','chaine',0,'','2010-07-18 10:25:47'),(691,'INVOICE_SUPPLIER_ADDON_PDF',1,'canelle','chaine',0,'','2011-02-06 11:18:27'),(692,'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées','2011-02-06 11:18:28'),(807,'MAIN_AGENDA_ACTIONAUTO_SHIPPING_VALIDATE',1,'1','chaine',0,NULL,'2011-07-18 17:27:52'),(808,'MAIN_AGENDA_ACTIONAUTO_SHIPPING_SENTBYMAIL',1,'1','chaine',0,NULL,'2011-07-18 17:27:52'),(834,'MAIN_MODULE_CASHDESK',1,'1',NULL,0,NULL,'2011-07-18 17:30:24'),(838,'MAIN_MODULE_PRODUCT',1,'1',NULL,0,NULL,'2011-07-18 17:30:24'),(969,'MAIN_MODULE_PRELEVEMENT',1,'1',NULL,0,NULL,'2011-07-18 18:01:59'),(973,'MAIN_MODULE_WORKFLOW',1,'1',NULL,0,NULL,'2011-07-18 18:02:20'),(974,'MAIN_MODULE_WORKFLOW_TRIGGERS',1,'1','chaine',0,NULL,'2011-07-18 18:02:20'),(975,'WORKFLOW_PROPAL_AUTOCREATE_ORDER',1,'1','chaine',0,'','2011-07-18 18:02:24'),(979,'PRELEVEMENT_USER',1,'1','chaine',0,'','2011-07-18 18:05:50'),(980,'PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR',1,'1234567','chaine',0,'','2011-07-18 18:05:50'),(981,'PRELEVEMENT_ID_BANKACCOUNT',1,'1','chaine',0,'','2011-07-18 18:05:50'),(983,'FACTURE_RIB_NUMBER',1,'1','chaine',0,'','2011-07-18 18:35:14'),(984,'FACTURE_CHQ_NUMBER',1,'1','chaine',0,'','2011-07-18 18:35:14'),(1005,'MAIN_MODULE_GOOGLE',1,'1',NULL,0,NULL,'2011-07-18 21:20:54'),(1006,'MAIN_MODULE_GOOGLE_TABS_0',1,'agenda:+gcal:MenuAgendaGoogle:@google:$conf->google->enabled && $conf->global->GOOGLE_ENABLE_AGENDA:/google/index.php','chaine',0,NULL,'2011-07-18 21:20:54'),(1007,'MAIN_MODULE_GOOGLE_TRIGGERS',1,'1','chaine',0,NULL,'2011-07-18 21:20:54'),(1008,'GOOGLE_ENABLE_AGENDA',1,'0','chaine',0,'','2011-07-18 21:22:59'),(1009,'GOOGLE_AGENDA_COLOR1',1,'FFFFFF','chaine',0,'','2011-07-18 21:22:59'),(1010,'GOOGLE_AGENDA_COLOR2',1,'7A367A','chaine',0,'','2011-07-18 21:22:59'),(1011,'GOOGLE_AGENDA_COLOR3',1,'7A367A','chaine',0,'','2011-07-18 21:22:59'),(1012,'GOOGLE_AGENDA_COLOR4',1,'7A367A','chaine',0,'','2011-07-18 21:22:59'),(1013,'GOOGLE_AGENDA_COLOR5',1,'7A367A','chaine',0,'','2011-07-18 21:22:59'),(1014,'GOOGLE_AGENDA_TIMEZONE',1,'Europe/Paris','chaine',0,'','2011-07-18 21:22:59'),(1015,'GOOGLE_AGENDA_NB',1,'5','chaine',0,'','2011-07-18 21:22:59'),(1016,'GOOGLE_DUPLICATE_INTO_GCAL',1,'1','chaine',0,'','2011-07-18 21:40:20'),(1017,'MAIN_MAIL_DEBUG',1,'1','',1,'','2011-07-20 10:34:34'),(1018,'MAIN_MODULE_SYSLOG',0,'1',NULL,0,NULL,'2011-07-20 11:36:47'),(1019,'MAIN_DISABLE_ALL_MAILS',1,'0','chaine',0,'','2011-07-20 12:38:08'),(1020,'MAIN_MAIL_SENDMODE',0,'mail','chaine',0,'','2011-07-20 12:38:08'),(1021,'MAIN_MAIL_EMAIL_FROM',1,'mail@mail.com','chaine',0,'','2011-07-20 12:38:08'),(1098,'MAIN_INFO_SOCIETE_LOGO',1,'dolibarr_125x125.png','chaine',0,'','2011-07-28 18:42:09'),(1099,'MAIN_INFO_SOCIETE_LOGO_SMALL',1,'dolibarr_125x125_small.png','chaine',0,'','2011-07-28 18:42:09'),(1100,'MAIN_INFO_SOCIETE_LOGO_MINI',1,'dolibarr_125x125_mini.png','chaine',0,'','2011-07-28 18:42:09'),(1110,'MAIN_MAIL_SMTP_SERVER',0,'','chaine',0,'Host or ip address for SMTP server','2011-07-28 22:27:03'),(1111,'MAIN_MAIL_SMTP_PORT',0,'','chaine',0,'Port for SMTP server','2011-07-28 22:27:03'),(1138,'MAIN_VERSION_LAST_INSTALL',0,'3.1.0-beta','chaine',0,'Dolibarr version when install','2011-07-28 23:05:02'),(1152,'SOCIETE_CODECLIENT_ADDON',1,'mod_codeclient_monkey','chaine',0,'','2011-07-29 20:50:02'),(1231,'MAIN_UPLOAD_DOC',1,'2048','chaine',0,'','2011-07-29 21:04:00'),(1233,'MAIN_SESSION_TIMEOUT',1,'1442','chaine',0,'','2011-07-29 21:04:08'),(1234,'MAIN_UMASK',1,'0664','chaine',0,'','2011-07-29 21:04:11'),(1237,'MAIN_USE_CONNECT_TIMEOUT',1,'10','chaine',0,'','2011-07-29 21:04:56'),(1238,'MAIN_USE_RESPONSE_TIMEOUT',1,'30','chaine',0,'','2011-07-29 21:04:56'),(1239,'MAIN_PROXY_USE',1,'0','chaine',0,'','2011-07-29 21:04:56'),(1240,'MAIN_LOGEVENTS_USER_LOGIN',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1241,'MAIN_LOGEVENTS_USER_LOGIN_FAILED',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1242,'MAIN_LOGEVENTS_USER_LOGOUT',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1243,'MAIN_LOGEVENTS_USER_CREATE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1244,'MAIN_LOGEVENTS_USER_MODIFY',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1245,'MAIN_LOGEVENTS_USER_NEW_PASSWORD',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1246,'MAIN_LOGEVENTS_USER_ENABLEDISABLE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1247,'MAIN_LOGEVENTS_USER_DELETE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1248,'MAIN_LOGEVENTS_GROUP_CREATE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1249,'MAIN_LOGEVENTS_GROUP_MODIFY',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1250,'MAIN_LOGEVENTS_GROUP_DELETE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1251,'MAIN_BOXES_MAXLINES',1,'5','',0,'','2011-07-29 21:05:42'),(1379,'CABINETMED_RHEUMATOLOGY_ON',1,'1','chaine',1,'Enable features for rheumatology','2011-08-01 21:47:53'),(1464,'MAIN_INFO_SOCIETE_PAYS',1,'1:FR:France','chaine',0,'','2011-08-04 21:36:47'),(1465,'MAIN_INFO_SOCIETE_NOM',1,'MyBigCompany','chaine',0,'','2011-08-04 21:36:48'),(1466,'MAIN_INFO_SOCIETE_ADRESSE',1,'21 Jump street','chaine',0,'','2011-08-04 21:36:48'),(1467,'MAIN_INFO_SOCIETE_VILLE',1,'MyTown','chaine',0,'','2011-08-04 21:36:48'),(1468,'MAIN_INFO_SOCIETE_CP',1,'75500','chaine',0,'','2011-08-04 21:36:48'),(1469,'MAIN_INFO_SOCIETE_DEPARTEMENT',1,'0','chaine',0,'','2011-08-04 21:36:48'),(1470,'MAIN_MONNAIE',1,'EUR','chaine',0,'','2011-08-04 21:36:48'),(1471,'MAIN_INFO_SOCIETE_TEL',1,'09123123','chaine',0,'','2011-08-04 21:36:48'),(1472,'MAIN_INFO_SOCIETE_FAX',1,'09123124','chaine',0,'','2011-08-04 21:36:48'),(1473,'MAIN_INFO_SOCIETE_MAIL',1,'myemail@mybigcompany.com','chaine',0,'','2011-08-04 21:36:48'),(1474,'MAIN_INFO_SOCIETE_WEB',1,'http://www.dolibarr.org','chaine',0,'','2011-08-04 21:36:48'),(1475,'MAIN_INFO_SOCIETE_NOTE',1,'This is note about my company','chaine',0,'','2011-08-04 21:36:48'),(1476,'MAIN_INFO_CAPITAL',1,'10000','chaine',0,'','2011-08-04 21:36:48'),(1477,'MAIN_INFO_SOCIETE_FORME_JURIDIQUE',1,'0','chaine',0,'','2011-08-04 21:36:48'),(1478,'MAIN_INFO_TVAINTRA',1,'IN1234567','chaine',0,'','2011-08-04 21:36:48'),(1479,'SOCIETE_FISCAL_MONTH_START',1,'0','chaine',0,'','2011-08-04 21:36:48'),(1480,'FACTURE_TVAOPTION',1,'reel','chaine',0,'','2011-08-04 21:36:48'),(1482,'EXPEDITION_ADDON_NUMBER',1,'mod_expedition_safor','chaine',0,'Nom du gestionnaire de numerotation des expeditions','2011-08-05 17:53:11'),(1485,'MAIN_MODULE_TAX',1,'1',NULL,0,NULL,'2011-08-05 17:54:19'),(1490,'CONTRACT_ADDON',1,'mod_contract_serpis','chaine',0,'Nom du gestionnaire de numerotation des contrats','2011-08-05 18:11:58'),(1502,'MAIN_MENU_STANDARD',1,'auguria_backoffice.php','chaine',0,'','2011-08-05 18:28:30'),(1503,'MAIN_MENU_SMARTPHONE',1,'auguria_backoffice.php','chaine',0,'','2011-08-05 18:28:30'),(1504,'MAIN_MENUFRONT_STANDARD',1,'eldy_frontoffice.php','chaine',0,'','2011-08-05 18:28:30'),(1505,'MAIN_MENUFRONT_SMARTPHONE',1,'eldy_frontoffice.php','chaine',0,'','2011-08-05 18:28:30'),(1507,'MAIN_MODULE_PROPALE',1,'1',NULL,0,NULL,'2011-08-05 20:40:22'),(1509,'MAIN_MODULE_EXPEDITION',1,'1',NULL,0,NULL,'2011-08-05 20:40:24'),(1512,'MAIN_MODULE_CONTRAT',1,'1',NULL,0,NULL,'2011-08-05 20:40:27'),(1514,'MAIN_MODULE_FICHEINTER',1,'1',NULL,0,NULL,'2011-08-05 20:40:30'),(1518,'MAIN_MODULE_COMPTABILITE',1,'1',NULL,0,NULL,'2011-08-05 20:40:33'),(1521,'MAIN_MODULE_BANQUE',1,'1',NULL,0,NULL,'2011-08-05 20:40:34'),(1667,'MAIN_PROFID1_IN_ADDRESS',1,'0','chaine',0,'','2011-08-08 17:33:03'),(1668,'MAIN_PROFID2_IN_ADDRESS',1,'0','chaine',0,'','2011-08-08 17:33:03'),(1669,'MAIN_PROFID3_IN_ADDRESS',1,'0','chaine',0,'','2011-08-08 17:33:03'),(1670,'MAIN_PROFID4_IN_ADDRESS',1,'0','chaine',0,'','2011-08-08 17:33:03'),(1677,'COMMANDE_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/orders','chaine',0,NULL,'2012-04-11 10:03:31'),(1686,'MAIN_MODULE_AGENDA',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1687,'MAIN_MODULE_SOCIETE',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1688,'MAIN_MODULE_SERVICE',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1689,'MAIN_MODULE_COMMANDE',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1690,'MAIN_MODULE_FACTURE',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1691,'MAIN_MODULE_FOURNISSEUR',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1692,'MAIN_MODULE_USER',0,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1693,'MAIN_MODULE_DEPLACEMENT',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1694,'MAIN_MODULE_DON',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1695,'MAIN_MODULE_ECM',1,'1',NULL,0,NULL,'2012-04-11 10:04:58'),(1696,'MAIN_VERSION_LAST_UPGRADE',0,'3.2.0-beta','chaine',0,'Dolibarr version for last upgrade','2012-04-11 10:05:00'),(1698,'MAIN_REMOVE_INSTALL_WARNING',1,'1','',1,'','2012-04-11 10:05:39'),(1749,'MAIN_LANG_DEFAULT',1,'auto','chaine',0,'','2012-04-11 10:07:27'),(1750,'MAIN_MULTILANGS',1,'1','chaine',0,'','2012-04-11 10:07:27'),(1751,'MAIN_SIZE_LISTE_LIMIT',1,'25','chaine',0,'','2012-04-11 10:07:27'),(1752,'MAIN_DISABLE_JAVASCRIPT',1,'0','chaine',0,'','2012-04-11 10:07:27'),(1753,'MAIN_USE_PREVIEW_TABS',1,'0','chaine',0,'','2012-04-11 10:07:27'),(1754,'MAIN_START_WEEK',1,'1','chaine',0,'','2012-04-11 10:07:27'),(1755,'MAIN_SHOW_LOGO',1,'0','chaine',0,'','2012-04-11 10:07:27'),(1756,'MAIN_FIRSTNAME_NAME_POSITION',1,'0','chaine',0,'','2012-04-11 10:07:27'),(1757,'MAIN_THEME',1,'auguria','chaine',0,'','2012-04-11 10:07:27'),(1758,'MAIN_SEARCHFORM_CONTACT',1,'1','chaine',0,'','2012-04-11 10:07:27'),(1759,'MAIN_SEARCHFORM_SOCIETE',1,'1','chaine',0,'','2012-04-11 10:07:27'),(1760,'MAIN_SEARCHFORM_PRODUITSERVICE',1,'1','chaine',0,'','2012-04-11 10:07:27'),(1761,'MAIN_SEARCHFORM_ADHERENT',1,'1','chaine',0,'','2012-04-11 10:07:27'),(1762,'MAIN_HELPCENTER_DISABLELINK',0,'1','chaine',0,'','2012-04-11 10:07:27'),(1763,'MAIN_MOTD',1,'You are on a database initialized for demo.','chaine',0,'','2012-04-11 10:07:27'),(1764,'MAIN_HOME',1,'<span style=\"font-size:11px;\">__(NoteSomeFeaturesAreDisabled)__<br />\r\n<br />\r\n__(SomeTranslationAreUncomplete)__</span>','chaine',0,'','2012-04-11 10:07:27'),(1765,'MAIN_HELP_DISABLELINK',0,'0','chaine',0,'','2012-04-11 10:07:27'),(1766,'SYSTEMTOOLS_MYSQLDUMP',1,'/usr/bin/mysqldump','chaine',0,'','2012-04-11 10:11:49');
/*!40000 ALTER TABLE `llx_const` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contrat`
--

DROP TABLE IF EXISTS `llx_contrat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contrat` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_contrat_ref` (`ref`,`entity`),
  KEY `idx_contrat_fk_soc` (`fk_soc`),
  KEY `idx_contrat_fk_user_author` (`fk_user_author`),
  CONSTRAINT `fk_contrat_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_contrat_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contrat`
--

LOCK TABLES `llx_contrat` WRITE;
/*!40000 ALTER TABLE `llx_contrat` DISABLE KEYS */;
INSERT INTO `llx_contrat` (`rowid`, `ref`, `entity`, `tms`, `datec`, `date_contrat`, `statut`, `mise_en_service`, `fin_validite`, `date_cloture`, `fk_soc`, `fk_projet`, `fk_commercial_signature`, `fk_commercial_suivi`, `fk_user_author`, `fk_user_mise_en_service`, `fk_user_cloture`, `note`, `note_public`, `import_key`, `extraparams`) VALUES (1,'CONTRACT1',1,'2010-07-08 23:53:55','2010-07-09 01:53:25','2010-07-09 00:00:00',1,NULL,NULL,NULL,3,NULL,2,2,1,NULL,NULL,NULL,NULL,NULL,NULL),(2,'CONTRAT1',1,'2010-07-10 16:18:16','2010-07-10 18:13:37','2010-07-10 00:00:00',1,NULL,NULL,NULL,2,NULL,2,2,1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_contrat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contratdet`
--

DROP TABLE IF EXISTS `llx_contratdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contratdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `qty` double NOT NULL,
  `remise_percent` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT '0.00000000',
  `price_ht` double DEFAULT NULL,
  `remise` double DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contratdet`
--

LOCK TABLES `llx_contratdet` WRITE;
/*!40000 ALTER TABLE `llx_contratdet` DISABLE KEYS */;
INSERT INTO `llx_contratdet` (`rowid`, `tms`, `fk_contrat`, `fk_product`, `statut`, `label`, `description`, `fk_remise_except`, `date_commande`, `date_ouverture_prevue`, `date_ouverture`, `date_fin_validite`, `date_cloture`, `tva_tx`, `localtax1_tx`, `localtax2_tx`, `qty`, `remise_percent`, `subprice`, `price_ht`, `remise`, `total_ht`, `total_tva`, `total_localtax1`, `total_localtax2`, `total_ttc`, `info_bits`, `fk_user_author`, `fk_user_ouverture`, `fk_user_cloture`, `commentaire`) VALUES (1,'2010-07-08 23:54:23',1,3,4,'','',NULL,NULL,'2010-07-09 00:00:00','2010-07-09 12:00:00','2011-07-01 12:00:00',NULL,0.000,0.000,0.000,1,0,0.00000000,0,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,0,1,1,''),(2,'2010-07-10 16:14:14',2,NULL,0,'','Abonnement annuel assurance',NULL,NULL,'2010-07-10 00:00:00',NULL,'2011-07-10 00:00:00',NULL,1.000,0.000,0.000,1,0,10.00000000,10,0,10.00000000,0.10000000,0.00000000,0.00000000,10.10000000,0,0,NULL,NULL,NULL),(3,'2010-07-10 16:18:45',2,3,4,'','',NULL,NULL,'2010-07-10 00:00:00','2010-07-10 12:00:00','2011-07-09 12:00:00',NULL,0.000,0.000,0.000,1,0,0.00000000,0,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,0,1,1,''),(4,'2012-04-11 10:04:58',2,3,0,'','',NULL,NULL,'2010-07-10 00:00:00',NULL,NULL,NULL,0.000,0.000,0.000,1,10,40.00000000,40,NULL,36.00000000,0.00000000,0.00000000,0.00000000,36.00000000,0,0,NULL,1,'');
/*!40000 ALTER TABLE `llx_contratdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contratdet_log`
--

DROP TABLE IF EXISTS `llx_contratdet_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contratdet_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_contratdet` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `statut` smallint(6) NOT NULL,
  `fk_user_author` int(11) NOT NULL,
  `commentaire` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_contratdet_log_fk_contratdet` (`fk_contratdet`),
  KEY `idx_contratdet_log_date` (`date`),
  CONSTRAINT `fk_contratdet_log_fk_contratdet` FOREIGN KEY (`fk_contratdet`) REFERENCES `llx_contratdet` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_cotisation` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `fk_adherent` int(11) DEFAULT NULL,
  `dateadh` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `cotisation` double DEFAULT NULL,
  `fk_bank` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_cotisation` (`fk_adherent`,`dateadh`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_cotisation`
--

LOCK TABLES `llx_cotisation` WRITE;
/*!40000 ALTER TABLE `llx_cotisation` DISABLE KEYS */;
INSERT INTO `llx_cotisation` (`rowid`, `tms`, `datec`, `fk_adherent`, `dateadh`, `datef`, `cotisation`, `fk_bank`, `note`) VALUES (1,'2010-07-10 13:05:30','2010-07-10 15:05:30',2,'2010-07-10 00:00:00','2011-07-10',20,NULL,'Adhésion/cotisation 2010'),(2,'2010-07-11 14:20:00','2010-07-11 16:20:00',2,'2011-07-11 00:00:00','2012-07-10',10,NULL,'Adhésion/cotisation 2011'),(3,'2010-07-18 10:20:33','2010-07-18 12:20:33',2,'2012-07-11 00:00:00','2013-07-17',10,NULL,'Adhésion/cotisation 2012');
/*!40000 ALTER TABLE `llx_cotisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_deplacement`
--

DROP TABLE IF EXISTS `llx_deplacement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_deplacement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dated` datetime DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `type` varchar(12) NOT NULL,
  `fk_statut` int(11) NOT NULL DEFAULT '1',
  `km` double DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `note` text,
  `note_public` text,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_deplacement`
--

LOCK TABLES `llx_deplacement` WRITE;
/*!40000 ALTER TABLE `llx_deplacement` DISABLE KEYS */;
INSERT INTO `llx_deplacement` (`rowid`, `ref`, `entity`, `datec`, `tms`, `dated`, `fk_user`, `fk_user_author`, `fk_user_modif`, `type`, `fk_statut`, `km`, `fk_soc`, `fk_projet`, `note`, `note_public`, `extraparams`) VALUES (1,NULL,1,'2010-07-09 01:58:04','2010-07-08 23:58:18','2010-07-09 12:00:00',2,1,NULL,'TF_LUNCH',1,10,2,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_deplacement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document`
--

DROP TABLE IF EXISTS `llx_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_document_generator` (
  `rowid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `classfile` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_document_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `type` varchar(20) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_document_model` (`nom`,`type`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_document_model`
--

LOCK TABLES `llx_document_model` WRITE;
/*!40000 ALTER TABLE `llx_document_model` DISABLE KEYS */;
INSERT INTO `llx_document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES (9,'merou',1,'shipping',NULL,NULL),(15,'fsfe.fr.php',1,'donation',NULL,NULL),(21,'baleine',1,'project',NULL,NULL),(27,'typhon',1,'delivery',NULL,NULL),(101,'azur',1,'propal',NULL,NULL),(102,'rouget',1,'shipping',NULL,NULL),(103,'elevement',1,'delivery',NULL,NULL),(105,'soleil',1,'ficheinter',NULL,NULL),(123,'einstein',1,'order',NULL,NULL),(124,'crabe',1,'invoice',NULL,NULL),(125,'muscadet',1,'order_supplier',NULL,NULL),(126,'html_cerfafr',1,'donation',NULL,NULL);
/*!40000 ALTER TABLE `llx_document_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_dolibarr_modules`
--

DROP TABLE IF EXISTS `llx_dolibarr_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_dolibarr_modules` (
  `numero` int(11) NOT NULL DEFAULT '0',
  `entity` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `active_date` datetime NOT NULL,
  `active_version` varchar(25) NOT NULL,
  PRIMARY KEY (`numero`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_dolibarr_modules`
--

LOCK TABLES `llx_dolibarr_modules` WRITE;
/*!40000 ALTER TABLE `llx_dolibarr_modules` DISABLE KEYS */;
INSERT INTO `llx_dolibarr_modules` (`numero`, `entity`, `active`, `active_date`, `active_version`) VALUES (0,1,1,'2012-04-11 12:04:58','dolibarr'),(1,1,1,'2012-04-11 12:04:58','dolibarr'),(10,1,1,'2011-08-05 22:40:33','dolibarr'),(20,1,1,'2011-08-05 22:40:22','dolibarr'),(22,1,1,'2010-07-08 13:29:37','dolibarr'),(25,1,1,'2012-04-11 12:04:58','dolibarr'),(30,1,1,'2012-04-11 12:04:58','dolibarr'),(40,1,1,'2012-04-11 12:04:58','dolibarr'),(42,1,1,'2011-07-20 13:36:47','dolibarr'),(50,1,1,'2011-07-18 19:30:24','dolibarr'),(52,1,1,'2010-07-08 13:29:18','dolibarr'),(53,1,1,'2012-04-11 12:04:58','dolibarr'),(54,1,1,'2011-08-05 22:40:27','dolibarr'),(57,1,1,'2011-07-18 20:01:59','dolibarr'),(70,1,1,'2011-08-05 22:40:30','dolibarr'),(75,1,1,'2012-04-11 12:04:58','dolibarr'),(80,1,1,'2011-08-05 22:40:24','dolibarr'),(85,1,1,'2011-08-05 22:40:34','dolibarr'),(240,1,1,'2010-07-08 13:29:41','dolibarr'),(250,1,1,'2010-07-08 13:29:45','dolibarr'),(310,1,1,'2010-07-08 13:29:05','dolibarr'),(320,1,1,'2010-07-08 13:30:38','dolibarr'),(330,1,1,'2010-07-08 13:30:03','dolibarr'),(400,1,1,'2010-07-11 15:26:54','dolibarr'),(500,1,1,'2011-08-05 19:54:19','dolibarr'),(700,1,1,'2012-04-11 12:04:58','dolibarr'),(1780,1,1,'2010-07-08 13:29:59','dolibarr'),(2000,1,1,'2010-07-08 13:56:27','dolibarr'),(2400,1,1,'2012-04-11 12:04:58','dolibarr'),(2500,1,1,'2012-04-11 12:04:58','dolibarr'),(2600,1,1,'2010-07-08 13:30:30','dolibarr'),(2900,1,1,'2010-07-08 13:30:36','dolibarr'),(6000,1,1,'2011-07-18 20:02:20','dolibarr'),(10000,1,1,'2010-07-10 01:40:33','dolibarr'),(11000,1,1,'2011-07-18 23:20:54','3.1'),(50100,1,1,'2011-07-18 19:30:24','dolibarr'),(102000,1,1,'2011-08-01 23:48:06','1.0');
/*!40000 ALTER TABLE `llx_dolibarr_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_domain`
--

DROP TABLE IF EXISTS `llx_domain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_domain` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_don` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `phone` varchar(24) DEFAULT NULL,
  `phone_mobile` varchar(24) DEFAULT NULL,
  `public` smallint(6) NOT NULL DEFAULT '1',
  `fk_don_projet` int(11) DEFAULT NULL,
  `fk_user_author` int(11) NOT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_don`
--

LOCK TABLES `llx_don` WRITE;
/*!40000 ALTER TABLE `llx_don` DISABLE KEYS */;
INSERT INTO `llx_don` (`rowid`, `ref`, `entity`, `tms`, `fk_statut`, `datec`, `datedon`, `amount`, `fk_paiement`, `prenom`, `nom`, `societe`, `adresse`, `cp`, `ville`, `pays`, `email`, `phone`, `phone_mobile`, `public`, `fk_don_projet`, `fk_user_author`, `fk_user_valid`, `note`, `note_public`, `model_pdf`, `import_key`) VALUES (1,NULL,1,'2010-07-08 23:57:17',1,'2010-07-09 01:55:33','2010-07-09 12:00:00',10,1,'Donator','','Guest company','','','','France','',NULL,NULL,1,1,1,1,'',NULL,'html_cerfafr',NULL);
/*!40000 ALTER TABLE `llx_don` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_directories`
--

DROP TABLE IF EXISTS `llx_ecm_directories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_ecm_directories` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_parent` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `cachenbofdoc` int(11) NOT NULL DEFAULT '0',
  `date_c` datetime DEFAULT NULL,
  `date_m` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_c` int(11) DEFAULT NULL,
  `fk_user_m` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_ecm_directories` (`label`,`fk_parent`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_ecm_directories`
--

LOCK TABLES `llx_ecm_directories` WRITE;
/*!40000 ALTER TABLE `llx_ecm_directories` DISABLE KEYS */;
INSERT INTO `llx_ecm_directories` (`rowid`, `label`, `entity`, `fk_parent`, `description`, `cachenbofdoc`, `date_c`, `date_m`, `fk_user_c`, `fk_user_m`) VALUES (1,'Répertoire_1',1,0,'',1,'2010-07-11 16:27:26','2010-07-11 14:27:44',1,NULL);
/*!40000 ALTER TABLE `llx_ecm_directories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_documents`
--

DROP TABLE IF EXISTS `llx_ecm_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `date_u` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_directory` int(11) DEFAULT NULL,
  `fk_status` smallint(6) DEFAULT '0',
  `private` smallint(6) DEFAULT '0',
  `crc` varchar(32) NOT NULL DEFAULT '',
  `cryptkey` varchar(50) NOT NULL DEFAULT '',
  `cipher` varchar(50) NOT NULL DEFAULT 'twofish',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_ecm_documents` (`fullpath_dol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_contact` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datecreate` datetime DEFAULT NULL,
  `statut` smallint(6) DEFAULT '5',
  `element_id` int(11) NOT NULL,
  `fk_c_type_contact` int(11) NOT NULL,
  `fk_socpeople` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_contact_idx1` (`element_id`,`fk_c_type_contact`,`fk_socpeople`),
  KEY `fk_element_contact_fk_c_type_contact` (`fk_c_type_contact`),
  KEY `idx_element_contact_fk_socpeople` (`fk_socpeople`),
  CONSTRAINT `fk_element_contact_fk_c_type_contact` FOREIGN KEY (`fk_c_type_contact`) REFERENCES `llx_c_type_contact` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_contact`
--

LOCK TABLES `llx_element_contact` WRITE;
/*!40000 ALTER TABLE `llx_element_contact` DISABLE KEYS */;
INSERT INTO `llx_element_contact` (`rowid`, `datecreate`, `statut`, `element_id`, `fk_c_type_contact`, `fk_socpeople`) VALUES (1,'2010-07-09 00:49:43',4,1,160,1),(2,'2010-07-09 00:49:56',4,2,160,1),(3,'2010-07-09 00:50:19',4,3,160,1),(4,'2010-07-09 00:50:42',4,4,160,1),(5,'2010-07-09 01:52:36',4,1,120,1),(6,'2010-07-09 01:53:25',4,1,10,2),(7,'2010-07-09 01:53:25',4,1,11,2),(8,'2010-07-10 18:13:37',4,2,10,2),(9,'2010-07-10 18:13:37',4,2,11,2),(10,'2010-07-11 15:15:55',4,1,180,1),(11,'2010-07-11 16:22:36',4,5,160,1),(12,'2010-07-11 16:23:53',4,2,180,1);
/*!40000 ALTER TABLE `llx_element_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_element`
--

DROP TABLE IF EXISTS `llx_element_element`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_element` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_source` int(11) NOT NULL,
  `sourcetype` varchar(32) NOT NULL,
  `fk_target` int(11) NOT NULL,
  `targettype` varchar(32) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_element_idx1` (`fk_source`,`sourcetype`,`fk_target`,`targettype`),
  KEY `idx_element_element_fk_target` (`fk_target`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_element`
--

LOCK TABLES `llx_element_element` WRITE;
/*!40000 ALTER TABLE `llx_element_element` DISABLE KEYS */;
INSERT INTO `llx_element_element` (`rowid`, `fk_source`, `sourcetype`, `fk_target`, `targettype`) VALUES (1,2,'contrat',2,'facture'),(2,2,'propal',1,'commande'),(3,5,'commande',1,'shipping');
/*!40000 ALTER TABLE `llx_element_element` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_lock`
--

DROP TABLE IF EXISTS `llx_element_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_lock` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_element` int(11) NOT NULL,
  `elementtype` varchar(16) NOT NULL,
  `datel` datetime DEFAULT NULL,
  `datem` datetime DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_lock`
--

LOCK TABLES `llx_element_lock` WRITE;
/*!40000 ALTER TABLE `llx_element_lock` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_lock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_rang`
--

DROP TABLE IF EXISTS `llx_element_rang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_rang` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_parent` int(11) NOT NULL,
  `parenttype` varchar(16) NOT NULL,
  `fk_child` int(11) NOT NULL,
  `childtype` varchar(16) NOT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_rang_idx1` (`fk_parent`,`parenttype`,`fk_child`,`childtype`),
  KEY `idx_element_rang_fk_parent` (`fk_parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_rang`
--

LOCK TABLES `llx_element_rang` WRITE;
/*!40000 ALTER TABLE `llx_element_rang` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_rang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entity`
--

DROP TABLE IF EXISTS `llx_entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_entity` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `description` text,
  `datec` datetime DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_entity`
--

LOCK TABLES `llx_entity` WRITE;
/*!40000 ALTER TABLE `llx_entity` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_entity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entrepot`
--

DROP TABLE IF EXISTS `llx_entrepot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_entrepot` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `lieu` varchar(64) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `statut` tinyint(4) DEFAULT '1',
  `valo_pmp` float(12,4) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_entrepot_label` (`label`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_entrepot`
--

LOCK TABLES `llx_entrepot` WRITE;
/*!40000 ALTER TABLE `llx_entrepot` DISABLE KEYS */;
INSERT INTO `llx_entrepot` (`rowid`, `datec`, `tms`, `label`, `entity`, `description`, `lieu`, `address`, `cp`, `ville`, `fk_departement`, `fk_pays`, `statut`, `valo_pmp`, `fk_user_author`) VALUES (1,'2010-07-09 00:31:22','2010-07-08 22:40:36','WAREHOUSEHOUSTON',1,'Warehouse located at Houston','Warehouse houston','','','Houston',NULL,11,1,NULL,1),(2,'2010-07-09 00:41:03','2010-07-08 22:41:03','WAREHOUSEPARIS',1,'<br />','Warehouse Paris','','75000','Paris',NULL,1,1,NULL,1),(3,'2010-07-11 16:18:59','2010-07-11 14:18:59','Stock personnel Dupont',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de Alain Dupont','','','','',NULL,0,1,NULL,1);
/*!40000 ALTER TABLE `llx_entrepot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_events`
--

DROP TABLE IF EXISTS `llx_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_events` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `dateevent` datetime DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `description` varchar(250) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fk_object` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_events_dateevent` (`dateevent`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_events`
--

LOCK TABLES `llx_events` WRITE;
/*!40000 ALTER TABLE `llx_events` DISABLE KEYS */;
INSERT INTO `llx_events` (`rowid`, `tms`, `type`, `entity`, `dateevent`, `fk_user`, `description`, `ip`, `user_agent`, `fk_object`) VALUES (30,'2011-07-18 18:23:06','USER_LOGOUT',1,'2011-07-18 20:23:06',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(31,'2011-07-18 18:23:12','USER_LOGIN_FAILED',1,'2011-07-18 20:23:12',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(32,'2011-07-18 18:23:17','USER_LOGIN',1,'2011-07-18 20:23:17',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(33,'2011-07-18 20:10:51','USER_LOGIN_FAILED',1,'2011-07-18 22:10:51',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(34,'2011-07-18 20:10:55','USER_LOGIN',1,'2011-07-18 22:10:55',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(35,'2011-07-18 21:18:57','USER_LOGIN',1,'2011-07-18 23:18:57',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(36,'2011-07-20 10:34:10','USER_LOGIN',1,'2011-07-20 12:34:10',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(37,'2011-07-20 12:36:44','USER_LOGIN',1,'2011-07-20 14:36:44',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(38,'2011-07-20 13:20:51','USER_LOGIN_FAILED',1,'2011-07-20 15:20:51',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(39,'2011-07-20 13:20:54','USER_LOGIN',1,'2011-07-20 15:20:54',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(40,'2011-07-20 15:03:46','USER_LOGIN_FAILED',1,'2011-07-20 17:03:46',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(41,'2011-07-20 15:03:55','USER_LOGIN',1,'2011-07-20 17:03:55',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(42,'2011-07-20 18:05:05','USER_LOGIN_FAILED',1,'2011-07-20 20:05:05',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(43,'2011-07-20 18:05:08','USER_LOGIN',1,'2011-07-20 20:05:08',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(44,'2011-07-20 21:08:53','USER_LOGIN_FAILED',1,'2011-07-20 23:08:53',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(45,'2011-07-20 21:08:56','USER_LOGIN',1,'2011-07-20 23:08:56',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(46,'2011-07-21 01:26:12','USER_LOGIN',1,'2011-07-21 03:26:12',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(47,'2011-07-21 22:35:45','USER_LOGIN_FAILED',1,'2011-07-22 00:35:45',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(48,'2011-07-21 22:35:49','USER_LOGIN',1,'2011-07-22 00:35:49',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(49,'2011-07-26 23:09:47','USER_LOGIN_FAILED',1,'2011-07-27 01:09:47',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(50,'2011-07-26 23:09:50','USER_LOGIN',1,'2011-07-27 01:09:50',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(51,'2011-07-27 17:02:27','USER_LOGIN_FAILED',1,'2011-07-27 19:02:27',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(52,'2011-07-27 17:02:32','USER_LOGIN',1,'2011-07-27 19:02:32',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(53,'2011-07-27 23:33:37','USER_LOGIN_FAILED',1,'2011-07-28 01:33:37',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(54,'2011-07-27 23:33:41','USER_LOGIN',1,'2011-07-28 01:33:41',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(55,'2011-07-28 18:20:36','USER_LOGIN_FAILED',1,'2011-07-28 20:20:36',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(56,'2011-07-28 18:20:38','USER_LOGIN',1,'2011-07-28 20:20:38',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(57,'2011-07-28 20:13:30','USER_LOGIN_FAILED',1,'2011-07-28 22:13:30',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(58,'2011-07-28 20:13:34','USER_LOGIN',1,'2011-07-28 22:13:34',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(59,'2011-07-28 20:22:51','USER_LOGIN',1,'2011-07-28 22:22:51',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(60,'2011-07-28 23:05:06','USER_LOGIN',1,'2011-07-29 01:05:06',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(61,'2011-07-29 20:15:50','USER_LOGIN_FAILED',1,'2011-07-29 22:15:50',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(62,'2011-07-29 20:15:53','USER_LOGIN',1,'2011-07-29 22:15:53',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(68,'2011-07-29 20:51:01','USER_LOGOUT',1,'2011-07-29 22:51:01',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(69,'2011-07-29 20:51:05','USER_LOGIN',1,'2011-07-29 22:51:05',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(70,'2011-07-30 08:46:20','USER_LOGIN_FAILED',1,'2011-07-30 10:46:20',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(71,'2011-07-30 08:46:38','USER_LOGIN_FAILED',1,'2011-07-30 10:46:38',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(72,'2011-07-30 08:46:42','USER_LOGIN',1,'2011-07-30 10:46:42',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(73,'2011-07-30 10:05:12','USER_LOGIN_FAILED',1,'2011-07-30 12:05:12',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(74,'2011-07-30 10:05:15','USER_LOGIN',1,'2011-07-30 12:05:15',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(75,'2011-07-30 12:15:46','USER_LOGIN',1,'2011-07-30 14:15:46',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(76,'2011-07-31 22:19:30','USER_LOGIN',1,'2011-08-01 00:19:30',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(77,'2011-07-31 23:32:52','USER_LOGIN',1,'2011-08-01 01:32:52',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(78,'2011-08-01 01:24:50','USER_LOGIN_FAILED',1,'2011-08-01 03:24:50',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(79,'2011-08-01 01:24:54','USER_LOGIN',1,'2011-08-01 03:24:54',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(80,'2011-08-01 19:31:36','USER_LOGIN_FAILED',1,'2011-08-01 21:31:35',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(81,'2011-08-01 19:31:39','USER_LOGIN',1,'2011-08-01 21:31:39',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(82,'2011-08-01 20:01:36','USER_LOGIN',1,'2011-08-01 22:01:36',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(83,'2011-08-01 20:52:54','USER_LOGIN_FAILED',1,'2011-08-01 22:52:54',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(84,'2011-08-01 20:52:58','USER_LOGIN',1,'2011-08-01 22:52:58',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(85,'2011-08-01 21:17:28','USER_LOGIN_FAILED',1,'2011-08-01 23:17:28',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(86,'2011-08-01 21:17:31','USER_LOGIN',1,'2011-08-01 23:17:31',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(87,'2011-08-04 11:55:17','USER_LOGIN',1,'2011-08-04 13:55:17',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(88,'2011-08-04 20:19:03','USER_LOGIN_FAILED',1,'2011-08-04 22:19:03',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(89,'2011-08-04 20:19:07','USER_LOGIN',1,'2011-08-04 22:19:07',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(90,'2011-08-05 17:51:42','USER_LOGIN_FAILED',1,'2011-08-05 19:51:42',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(91,'2011-08-05 17:51:47','USER_LOGIN',1,'2011-08-05 19:51:47',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(92,'2011-08-05 17:56:03','USER_LOGIN',1,'2011-08-05 19:56:03',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(93,'2011-08-05 17:59:10','USER_LOGIN',1,'2011-08-05 19:59:10',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.100 Safari/534.30',NULL),(94,'2011-08-05 18:01:58','USER_LOGIN',1,'2011-08-05 20:01:58',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.100 Safari/534.30',NULL),(95,'2011-08-05 19:59:56','USER_LOGIN',1,'2011-08-05 21:59:56',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(96,'2011-08-06 18:33:22','USER_LOGIN',1,'2011-08-06 20:33:22',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(97,'2011-08-07 00:56:59','USER_LOGIN',1,'2011-08-07 02:56:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(98,'2011-08-07 22:49:14','USER_LOGIN',1,'2011-08-08 00:49:14',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(99,'2011-08-07 23:05:18','USER_LOGOUT',1,'2011-08-08 01:05:18',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(105,'2011-08-08 00:41:09','USER_LOGIN',1,'2011-08-08 02:41:09',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(106,'2011-08-08 11:58:55','USER_LOGIN',1,'2011-08-08 13:58:55',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(107,'2011-08-08 14:35:48','USER_LOGIN',1,'2011-08-08 16:35:48',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(108,'2011-08-08 14:36:31','USER_LOGOUT',1,'2011-08-08 16:36:31',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(109,'2011-08-08 14:38:28','USER_LOGIN',1,'2011-08-08 16:38:28',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(110,'2011-08-08 14:39:02','USER_LOGOUT',1,'2011-08-08 16:39:02',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(111,'2011-08-08 14:39:10','USER_LOGIN',1,'2011-08-08 16:39:10',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(112,'2011-08-08 14:39:28','USER_LOGOUT',1,'2011-08-08 16:39:28',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(113,'2011-08-08 14:39:37','USER_LOGIN',1,'2011-08-08 16:39:37',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(114,'2011-08-08 14:50:02','USER_LOGOUT',1,'2011-08-08 16:50:02',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(115,'2011-08-08 14:51:45','USER_LOGIN_FAILED',1,'2011-08-08 16:51:45',NULL,'Identifiants login ou mot de passe incorrects - login=','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(116,'2011-08-08 14:51:52','USER_LOGIN',1,'2011-08-08 16:51:52',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(117,'2011-08-08 15:09:54','USER_LOGOUT',1,'2011-08-08 17:09:54',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(118,'2011-08-08 15:10:19','USER_LOGIN_FAILED',1,'2011-08-08 17:10:19',NULL,'Identifiants login ou mot de passe incorrects - login=','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(119,'2011-08-08 15:10:28','USER_LOGIN',1,'2011-08-08 17:10:28',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(121,'2011-08-08 15:14:58','USER_LOGOUT',1,'2011-08-08 17:14:58',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(122,'2011-08-08 15:15:00','USER_LOGIN_FAILED',1,'2011-08-08 17:15:00',NULL,'Identifiants login ou mot de passe incorrects - login=','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(123,'2011-08-08 15:17:57','USER_LOGIN',1,'2011-08-08 17:17:57',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(124,'2011-08-08 15:35:56','USER_LOGOUT',1,'2011-08-08 17:35:56',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(125,'2011-08-08 15:36:05','USER_LOGIN',1,'2011-08-08 17:36:05',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(126,'2011-08-08 17:32:42','USER_LOGIN',1,'2011-08-08 19:32:42',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(127,'2012-04-11 10:05:03','USER_LOGIN',1,'2012-04-11 12:05:03',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.83 Safari/535.11',NULL);
/*!40000 ALTER TABLE `llx_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition`
--

DROP TABLE IF EXISTS `llx_expedition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_expedition` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_customer` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_int` varchar(30) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_expedition` datetime DEFAULT NULL,
  `date_delivery` datetime DEFAULT NULL,
  `fk_address` int(11) DEFAULT NULL,
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
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_expedition_uk_ref` (`ref`,`entity`),
  KEY `idx_expedition_fk_soc` (`fk_soc`),
  KEY `idx_expedition_fk_user_author` (`fk_user_author`),
  KEY `idx_expedition_fk_user_valid` (`fk_user_valid`),
  KEY `idx_expedition_fk_expedition_methode` (`fk_expedition_methode`),
  CONSTRAINT `fk_expedition_fk_expedition_methode` FOREIGN KEY (`fk_expedition_methode`) REFERENCES `llx_c_shipment_mode` (`rowid`),
  CONSTRAINT `fk_expedition_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expedition`
--

LOCK TABLES `llx_expedition` WRITE;
/*!40000 ALTER TABLE `llx_expedition` DISABLE KEYS */;
INSERT INTO `llx_expedition` (`rowid`, `tms`, `ref`, `entity`, `ref_customer`, `fk_soc`, `ref_ext`, `ref_int`, `date_creation`, `fk_user_author`, `date_valid`, `fk_user_valid`, `date_expedition`, `date_delivery`, `fk_address`, `fk_expedition_methode`, `tracking_number`, `fk_statut`, `height`, `width`, `size_units`, `size`, `weight_units`, `weight`, `note`, `model_pdf`) VALUES (1,'2011-08-08 01:05:34','(PROV1)',1,NULL,1,NULL,NULL,'2011-08-08 03:05:34',1,NULL,NULL,NULL,'2011-08-09 00:00:00',NULL,NULL,'',0,NULL,NULL,0,NULL,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_expedition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition_methode`
--

DROP TABLE IF EXISTS `llx_expedition_methode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_expedition_methode` (
  `rowid` int(11) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `description` text,
  `active` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expedition_methode`
--

LOCK TABLES `llx_expedition_methode` WRITE;
/*!40000 ALTER TABLE `llx_expedition_methode` DISABLE KEYS */;
INSERT INTO `llx_expedition_methode` (`rowid`, `tms`, `code`, `libelle`, `description`, `active`) VALUES (1,'2010-07-08 11:18:00','CATCH','Catch','Catch by client',1),(2,'2010-07-08 11:18:00','TRANS','Transporter','Generic transporter',1),(3,'2010-07-08 11:18:01','COLSUI','Colissimo Suivi','Colissimo Suivi',0);
/*!40000 ALTER TABLE `llx_expedition_methode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expeditiondet`
--

DROP TABLE IF EXISTS `llx_expeditiondet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expeditiondet`
--

LOCK TABLES `llx_expeditiondet` WRITE;
/*!40000 ALTER TABLE `llx_expeditiondet` DISABLE KEYS */;
INSERT INTO `llx_expeditiondet` (`rowid`, `fk_expedition`, `fk_origin_line`, `fk_entrepot`, `qty`, `rang`) VALUES (1,1,10,3,1,0);
/*!40000 ALTER TABLE `llx_expeditiondet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_export_compta`
--

DROP TABLE IF EXISTS `llx_export_compta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_export_compta` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(12) NOT NULL,
  `date_export` datetime NOT NULL,
  `fk_user` int(11) NOT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_export_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `label` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `field` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_export_model` (`label`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_export_model`
--

LOCK TABLES `llx_export_model` WRITE;
/*!40000 ALTER TABLE `llx_export_model` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_export_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extrafields`
--

DROP TABLE IF EXISTS `llx_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `elementtype` varchar(64) NOT NULL DEFAULT 'member',
  `name` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `type` varchar(8) DEFAULT NULL,
  `size` int(11) DEFAULT '0',
  `pos` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_extrafields_name` (`name`,`entity`,`elementtype`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_extrafields`
--

LOCK TABLES `llx_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_extrafields` DISABLE KEYS */;
INSERT INTO `llx_extrafields` (`rowid`, `elementtype`, `name`, `entity`, `tms`, `label`, `type`, `size`, `pos`) VALUES (2,'member','zzz',1,'2011-06-19 11:55:40','zzz','varchar',255,0),(22,'company','jjjj',1,'2011-06-22 16:10:02','jjj','varchar',255,0);
/*!40000 ALTER TABLE `llx_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture`
--

DROP TABLE IF EXISTS `llx_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `facnumber` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(255) DEFAULT NULL,
  `ref_int` varchar(255) DEFAULT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `ref_client` varchar(255) DEFAULT NULL,
  `increment` varchar(10) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `date_valid` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `close_code` varchar(16) DEFAULT NULL,
  `close_note` varchar(128) DEFAULT NULL,
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_account` int(11) DEFAULT NULL,
  `fk_currency` varchar(2) DEFAULT NULL,
  `fk_cond_reglement` int(11) NOT NULL DEFAULT '1',
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_uk_facnumber` (`facnumber`,`entity`),
  KEY `idx_facture_fk_soc` (`fk_soc`),
  KEY `idx_facture_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fk_facture_source` (`fk_facture_source`),
  KEY `idx_facture_fk_projet` (`fk_projet`),
  KEY `idx_facture_fk_account` (`fk_account`),
  KEY `idx_facture_fk_currency` (`fk_currency`),
  CONSTRAINT `fk_facture_fk_currency` FOREIGN KEY (`fk_currency`) REFERENCES `llx_c_currencies` (`code_iso`),
  CONSTRAINT `fk_facture_fk_facture_source` FOREIGN KEY (`fk_facture_source`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_facture_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture`
--

LOCK TABLES `llx_facture` WRITE;
/*!40000 ALTER TABLE `llx_facture` DISABLE KEYS */;
INSERT INTO `llx_facture` (`rowid`, `facnumber`, `entity`, `ref_ext`, `ref_int`, `type`, `ref_client`, `increment`, `fk_soc`, `datec`, `datef`, `date_valid`, `tms`, `paye`, `amount`, `remise_percent`, `remise_absolue`, `remise`, `close_code`, `close_note`, `tva`, `localtax1`, `localtax2`, `total`, `total_ttc`, `fk_statut`, `fk_user_author`, `fk_user_valid`, `fk_facture_source`, `fk_projet`, `fk_account`, `fk_currency`, `fk_cond_reglement`, `fk_mode_reglement`, `date_lim_reglement`, `note`, `note_public`, `model_pdf`, `import_key`, `extraparams`) VALUES (1,'FA1007-0001',1,NULL,NULL,0,NULL,NULL,9,'2010-07-10 14:55:26','2010-07-10',NULL,'2011-07-20 11:18:39',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.02000000,0.02000000,1,1,1,NULL,1,NULL,NULL,1,0,'2010-07-10',NULL,NULL,'crabe',NULL,NULL),(2,'FA1007-0002',1,NULL,NULL,0,NULL,NULL,2,'2010-07-10 18:20:13','2010-07-10',NULL,'2011-08-08 00:54:05',1,10.00000000,NULL,NULL,0,NULL,NULL,0.10000000,0.00000000,0.00000000,46.00000000,46.10000000,2,1,1,NULL,NULL,NULL,NULL,1,0,'2010-07-10',NULL,NULL,'crabe',NULL,NULL),(3,'FA1107-0006',1,NULL,NULL,0,NULL,NULL,10,'2011-07-18 20:33:35','2011-07-18',NULL,'2011-08-08 00:55:58',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,15.00000000,15.00000000,2,1,1,NULL,NULL,NULL,NULL,1,0,'2011-07-18',NULL,NULL,'crabe',NULL,NULL),(5,'FA1108-0003',1,NULL,NULL,0,NULL,NULL,7,'2011-08-01 03:34:11','2011-08-01',NULL,'2011-08-01 01:34:11',1,0.00000000,NULL,NULL,0,NULL,NULL,0.63000000,0.00000000,0.00000000,5.00000000,5.63000000,2,1,1,NULL,NULL,NULL,NULL,0,6,'2011-08-01',NULL,NULL,'',NULL,NULL),(6,'FA1108-0004',1,NULL,NULL,0,NULL,NULL,7,'2011-08-06 20:33:53','2011-08-06',NULL,'2011-08-06 18:35:13',1,0.00000000,NULL,NULL,0,NULL,NULL,0.98000000,0.00000000,0.00000000,5.00000000,5.98000000,2,1,1,NULL,NULL,NULL,NULL,0,4,'2011-08-06','Cash\nReceived : 6 EUR\nRendu : 0.02 EUR\n\n--------------------------------------',NULL,'crabe',NULL,NULL),(8,'FA1108-0005',1,NULL,NULL,3,NULL,NULL,2,'2011-08-08 02:41:44','2011-08-08',NULL,'2011-08-08 00:53:40',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,2,1,1,NULL,NULL,NULL,NULL,1,0,'2011-08-08',NULL,NULL,'crabe',NULL,NULL),(9,'FA1108-0007',1,NULL,NULL,3,NULL,NULL,10,'2011-08-08 02:55:14','2011-08-08',NULL,'2011-08-08 00:55:26',0,0.00000000,NULL,NULL,0,NULL,NULL,1.96000000,0.00000000,0.00000000,10.00000000,11.96000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2011-08-08',NULL,NULL,'crabe',NULL,NULL),(11,'(PROV11)',1,NULL,NULL,0,NULL,NULL,1,'2012-04-11 12:06:16','2012-04-11',NULL,'2012-04-11 10:06:16',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,7,'2012-04-11','This is a comment (private)','This is a comment (public)','',NULL,NULL),(12,'(PROV12)',1,NULL,NULL,0,NULL,NULL,1,'2012-04-11 12:06:16','2012-04-11',NULL,'2012-04-11 10:06:16',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,7,'2012-04-11','This is a comment (private)','This is a comment (public)','',NULL,NULL);
/*!40000 ALTER TABLE `llx_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn`
--

DROP TABLE IF EXISTS `llx_facture_fourn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_fourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `facnumber` varchar(50) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `libelle` varchar(255) DEFAULT NULL,
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise` double(24,8) DEFAULT '0.00000000',
  `close_code` varchar(16) DEFAULT NULL,
  `close_note` varchar(128) DEFAULT NULL,
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
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
  `model_pdf` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_fourn`
--

LOCK TABLES `llx_facture_fourn` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn` DISABLE KEYS */;
INSERT INTO `llx_facture_fourn` (`rowid`, `facnumber`, `entity`, `ref_ext`, `type`, `fk_soc`, `datec`, `datef`, `tms`, `libelle`, `paye`, `amount`, `remise`, `close_code`, `close_note`, `tva`, `localtax1`, `localtax2`, `total`, `total_ht`, `total_tva`, `total_ttc`, `fk_statut`, `fk_user_author`, `fk_user_valid`, `fk_facture_source`, `fk_projet`, `fk_cond_reglement`, `date_lim_reglement`, `note`, `note_public`, `model_pdf`, `import_key`, `extraparams`) VALUES (1,'aaa',1,NULL,0,17,'2011-08-04 22:21:18','2011-08-04','2011-08-04 20:21:18','',0,0.00000000,0.00000000,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,1,NULL,NULL,NULL,1,NULL,'','',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_facture_fourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn_det`
--

DROP TABLE IF EXISTS `llx_facture_fourn_det`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `total_ht` double(24,8) DEFAULT NULL,
  `tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_facture_fourn_det_fk_facture` (`fk_facture_fourn`),
  CONSTRAINT `fk_facture_fourn_det_fk_facture` FOREIGN KEY (`fk_facture_fourn`) REFERENCES `llx_facture_fourn` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_rec` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(50) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT '0',
  `fk_mode_reglement` int(11) DEFAULT '0',
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `last_gen` varchar(7) DEFAULT NULL,
  `unit_frequency` varchar(2) DEFAULT 'd',
  `date_when` datetime DEFAULT NULL,
  `date_last_gen` datetime DEFAULT NULL,
  `nb_gen_done` int(11) DEFAULT NULL,
  `nb_gen_max` int(11) DEFAULT NULL,
  `frequency` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_rec_uk_titre` (`titre`,`entity`),
  KEY `idx_facture_rec_fk_soc` (`fk_soc`),
  KEY `idx_facture_rec_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_rec_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_rec_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facturedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `subprice` double(24,8) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `total_ht` double(24,8) DEFAULT NULL,
  `total_tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
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
  UNIQUE KEY `uk_fk_remise_except` (`fk_remise_except`,`fk_facture`),
  KEY `idx_facturedet_fk_facture` (`fk_facture`),
  KEY `idx_facturedet_fk_product` (`fk_product`),
  CONSTRAINT `fk_facturedet_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facturedet`
--

LOCK TABLES `llx_facturedet` WRITE;
/*!40000 ALTER TABLE `llx_facturedet` DISABLE KEYS */;
INSERT INTO `llx_facturedet` (`rowid`, `fk_facture`, `fk_parent_line`, `fk_product`, `description`, `tva_tx`, `localtax1_tx`, `localtax2_tx`, `qty`, `remise_percent`, `remise`, `fk_remise_except`, `subprice`, `price`, `total_ht`, `total_tva`, `total_localtax1`, `total_localtax2`, `total_ttc`, `product_type`, `date_start`, `date_end`, `info_bits`, `fk_code_ventilation`, `fk_export_compta`, `special_code`, `rang`, `import_key`) VALUES (1,1,NULL,4,'',0.000,0.000,0.000,2,0,0,NULL,0.01000000,0.01000000,0.02000000,0.00000000,0.00000000,0.00000000,0.02000000,0,NULL,NULL,0,0,0,0,1,NULL),(3,2,NULL,3,'Service S1',0.000,0.000,0.000,1,10,4,NULL,40.00000000,36.00000000,36.00000000,0.00000000,0.00000000,0.00000000,36.00000000,1,'2010-07-10 00:00:00',NULL,0,0,0,0,2,NULL),(4,2,NULL,NULL,'Abonnement annuel assurance',1.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.10000000,0.00000000,0.00000000,10.10000000,0,'2010-07-10 00:00:00','2011-07-10 00:00:00',0,0,0,0,3,NULL),(11,3,NULL,4,'afsdfsdfsdfsdf',0.000,0.000,0.000,1,0,0,NULL,5.00000000,5.00000000,5.00000000,0.00000000,0.00000000,0.00000000,5.00000000,0,NULL,NULL,0,0,0,0,0,NULL),(12,3,NULL,NULL,'dfdfd',0.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0,0,0,2,NULL),(13,5,NULL,4,'Decapsuleur',12.500,0.000,0.000,1,0,0,NULL,5.00000000,5.00000000,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,0,0,0,0,NULL),(14,6,NULL,4,'Decapsuleur',19.600,0.000,0.000,1,0,0,NULL,5.00000000,5.00000000,5.00000000,0.98000000,0.00000000,0.00000000,5.98000000,0,NULL,NULL,0,0,0,0,0,NULL),(21,8,NULL,NULL,'dddd',0.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0,0,0,1,NULL),(22,9,NULL,NULL,'ggg',19.600,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,0,0,0,1,NULL),(28,11,NULL,NULL,'Desc',10.000,0.000,0.000,1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,0,0,0,1,NULL),(29,11,NULL,NULL,'Desc',10.000,0.000,0.000,1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,0,0,0,2,NULL),(30,12,NULL,NULL,'Desc',10.000,0.000,0.000,1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,0,0,0,1,NULL),(31,12,NULL,NULL,'Desc',10.000,0.000,0.000,1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,0,0,0,2,NULL);
/*!40000 ALTER TABLE `llx_facturedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet_rec`
--

DROP TABLE IF EXISTS `llx_facturedet_rec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facturedet_rec` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `total_ht` double(24,8) DEFAULT NULL,
  `total_tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_fichinter` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `fk_contrat` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `model_pdf` varchar(255) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fichinter_ref` (`ref`,`entity`),
  KEY `idx_fichinter_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_fichinter_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_fichinter`
--

LOCK TABLES `llx_fichinter` WRITE;
/*!40000 ALTER TABLE `llx_fichinter` DISABLE KEYS */;
INSERT INTO `llx_fichinter` (`rowid`, `fk_soc`, `fk_projet`, `fk_contrat`, `ref`, `entity`, `tms`, `datec`, `date_valid`, `datei`, `fk_user_author`, `fk_user_valid`, `fk_statut`, `duree`, `description`, `note_private`, `note_public`, `model_pdf`, `extraparams`) VALUES (1,2,1,0,'FI1007-0001',1,'2010-07-08 23:51:54','2010-07-09 01:42:41','2010-07-09 01:51:54',NULL,1,1,1,51000,NULL,NULL,NULL,'soleil',NULL),(2,1,NULL,0,'FI1007-0002',1,'2012-04-11 10:03:49','2010-07-11 16:07:51',NULL,NULL,1,NULL,0,3600,'ferfrefeferf',NULL,NULL,'soleil',NULL);
/*!40000 ALTER TABLE `llx_fichinter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinterdet`
--

DROP TABLE IF EXISTS `llx_fichinterdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_fichinterdet`
--

LOCK TABLES `llx_fichinterdet` WRITE;
/*!40000 ALTER TABLE `llx_fichinterdet` DISABLE KEYS */;
INSERT INTO `llx_fichinterdet` (`rowid`, `fk_fichinter`, `date`, `description`, `duree`, `rang`) VALUES (1,1,'2010-07-07 04:00:00','Intervention sur site',3600,0),(2,1,'2010-07-08 11:00:00','Autre',47400,0),(3,2,'2010-07-11 05:00:00','Pres',3600,0);
/*!40000 ALTER TABLE `llx_fichinterdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_filemanager_roots`
--

DROP TABLE IF EXISTS `llx_filemanager_roots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_filemanager_roots` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `rootlabel` varchar(64) DEFAULT NULL,
  `rootpath` text,
  `note` text,
  `position` int(11) DEFAULT NULL,
  `entity` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_filemanager_root` (`rootlabel`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_filemanager_roots`
--

LOCK TABLES `llx_filemanager_roots` WRITE;
/*!40000 ALTER TABLE `llx_filemanager_roots` DISABLE KEYS */;
INSERT INTO `llx_filemanager_roots` (`rowid`, `datec`, `rootlabel`, `rootpath`, `note`, `position`, `entity`) VALUES (1,NULL,'c','/media/DATA',NULL,NULL,1),(2,NULL,'home','/home',NULL,NULL,1);
/*!40000 ALTER TABLE `llx_filemanager_roots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fournisseur_ca`
--

DROP TABLE IF EXISTS `llx_fournisseur_ca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_fournisseur_ca` (
  `fk_societe` int(11) DEFAULT NULL,
  `date_calcul` datetime DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `ca_genere` float DEFAULT NULL,
  `ca_achat` float(11,2) DEFAULT '0.00',
  UNIQUE KEY `fk_societe` (`fk_societe`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_import_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `label` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `field` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_import_model` (`label`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_livraison` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_customer` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_int` varchar(30) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_delivery` date DEFAULT NULL,
  `fk_address` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_livraison_uk_ref` (`ref`,`entity`),
  KEY `idx_livraison_fk_soc` (`fk_soc`),
  KEY `idx_livraison_fk_user_author` (`fk_user_author`),
  KEY `idx_livraison_fk_user_valid` (`fk_user_valid`),
  CONSTRAINT `fk_livraison_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_livraisondet`
--

LOCK TABLES `llx_livraisondet` WRITE;
/*!40000 ALTER TABLE `llx_livraisondet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_livraisondet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_localtax`
--

DROP TABLE IF EXISTS `llx_localtax`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_localtax` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` date DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_localtax`
--

LOCK TABLES `llx_localtax` WRITE;
/*!40000 ALTER TABLE `llx_localtax` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_localtax` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_mailing`
--

DROP TABLE IF EXISTS `llx_mailing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `tag` varchar(128) DEFAULT NULL,
  `date_creat` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_appro` datetime DEFAULT NULL,
  `date_envoi` datetime DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_appro` int(11) DEFAULT NULL,
  `joined_file1` varchar(255) DEFAULT NULL,
  `joined_file2` varchar(255) DEFAULT NULL,
  `joined_file3` varchar(255) DEFAULT NULL,
  `joined_file4` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_mailing`
--

LOCK TABLES `llx_mailing` WRITE;
/*!40000 ALTER TABLE `llx_mailing` DISABLE KEYS */;
INSERT INTO `llx_mailing` (`rowid`, `statut`, `titre`, `entity`, `sujet`, `body`, `bgcolor`, `bgimage`, `cible`, `nbemail`, `email_from`, `email_replyto`, `email_errorsto`, `tag`, `date_creat`, `date_valid`, `date_appro`, `date_envoi`, `fk_user_creat`, `fk_user_valid`, `fk_user_appro`, `joined_file1`, `joined_file2`, `joined_file3`, `joined_file4`) VALUES (3,1,'My commercial emailing',1,'Buy my product','Please buy my product<br />','','',NULL,2,'dolibarr@domain.com','','',NULL,'2010-07-11 13:15:59','2010-07-11 13:46:20',NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL),(4,0,'Copy of My commercial emailing',1,'Buy my product','This is a new EMailing content<br />\r\n<br />\r\n<span style=\"color:#800080;\"><strong>You can adit it with the WYSIWYG editor.</strong></span><br />\r\nIt is\r\n<ul>\r\n	<li>\r\n		Fast</li>\r\n	<li>\r\n		Easy to use</li>\r\n	<li>\r\n		Pretty</li>\r\n</ul>','','',NULL,NULL,'dolibarr@domain.com','','',NULL,'2011-07-18 20:44:33',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_mailing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_mailing_cibles`
--

DROP TABLE IF EXISTS `llx_mailing_cibles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_mailing_cibles` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_mailing` int(11) NOT NULL,
  `fk_contact` int(11) NOT NULL,
  `nom` varchar(160) DEFAULT NULL,
  `prenom` varchar(160) DEFAULT NULL,
  `email` varchar(160) NOT NULL,
  `other` varchar(255) DEFAULT NULL,
  `tag` varchar(128) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `source_url` varchar(160) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `source_type` varchar(16) DEFAULT NULL,
  `date_envoi` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_mailing_cibles` (`fk_mailing`,`email`),
  KEY `idx_mailing_cibles_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_mailing_cibles`
--

LOCK TABLES `llx_mailing_cibles` WRITE;
/*!40000 ALTER TABLE `llx_mailing_cibles` DISABLE KEYS */;
INSERT INTO `llx_mailing_cibles` (`rowid`, `fk_mailing`, `fk_contact`, `nom`, `prenom`, `email`, `other`, `tag`, `statut`, `source_url`, `source_id`, `source_type`, `date_envoi`) VALUES (1,1,0,'Dupont','Alain','toto@aa.com','Date fin=10/07/2011',NULL,0,'0',NULL,NULL,NULL),(2,2,0,'Swiss customer supplier','','abademail@aa.com','',NULL,0,'0',NULL,NULL,NULL),(3,2,0,'Smith Vick','','vsmith@email.com','',NULL,0,'0',NULL,NULL,NULL),(4,3,0,'Swiss customer supplier','','abademail@aa.com','',NULL,0,'0',NULL,NULL,NULL),(5,3,0,'Smith Vick','','vsmith@email.com','',NULL,0,'0',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_mailing_cibles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_menu`
--

DROP TABLE IF EXISTS `llx_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_menu` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `menu_handler` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `module` varchar(64) DEFAULT NULL,
  `type` varchar(4) NOT NULL,
  `mainmenu` varchar(100) NOT NULL,
  `fk_menu` int(11) NOT NULL,
  `fk_leftmenu` varchar(16) DEFAULT NULL,
  `fk_mainmenu` varchar(16) DEFAULT NULL,
  `position` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(100) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `langs` varchar(100) DEFAULT NULL,
  `level` smallint(6) DEFAULT NULL,
  `leftmenu` varchar(100) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `enabled` varchar(255) DEFAULT '1',
  `usertype` int(11) NOT NULL DEFAULT '0',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_menu_uk_menu` (`menu_handler`,`fk_menu`,`position`,`url`,`entity`),
  KEY `idx_menu_menuhandler_type` (`menu_handler`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=61175 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_menu`
--

LOCK TABLES `llx_menu` WRITE;
/*!40000 ALTER TABLE `llx_menu` DISABLE KEYS */;
INSERT INTO `llx_menu` (`rowid`, `menu_handler`, `entity`, `module`, `type`, `mainmenu`, `fk_menu`, `fk_leftmenu`, `fk_mainmenu`, `position`, `url`, `target`, `titre`, `langs`, `level`, `leftmenu`, `perms`, `enabled`, `usertype`, `tms`) VALUES (19289,'all',1,'cashdesk','top','cashdesk',0,NULL,NULL,100,'/cashdesk/index.php?user=__LOGIN__','pointofsale','CashDeskMenu','cashdesk',NULL,NULL,'1','$conf->cashdesk->enabled',0,'2012-04-11 10:04:08'),(55933,'all',1,'agenda','top','agenda',0,NULL,NULL,100,'/comm/action/index.php','','Agenda','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55934,'all',1,'agenda','left','agenda',55933,NULL,NULL,100,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Actions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55935,'all',1,'agenda','left','agenda',55934,NULL,NULL,101,'/comm/action/fiche.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create','','NewAction','commercial',NULL,NULL,'($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create)','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55936,'all',1,'agenda','left','agenda',55934,NULL,NULL,102,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Calendar','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55937,'all',1,'agenda','left','agenda',55936,NULL,NULL,103,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55938,'all',1,'agenda','left','agenda',55936,NULL,NULL,104,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55939,'all',1,'agenda','left','agenda',55936,NULL,NULL,105,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2012-04-11 10:04:58'),(55940,'all',1,'agenda','left','agenda',55936,NULL,NULL,106,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2012-04-11 10:04:58'),(55941,'all',1,'agenda','left','agenda',55934,NULL,NULL,112,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda','','List','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55942,'all',1,'agenda','left','agenda',55941,NULL,NULL,113,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55943,'all',1,'agenda','left','agenda',55941,NULL,NULL,114,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55944,'all',1,'agenda','left','agenda',55941,NULL,NULL,115,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2012-04-11 10:04:58'),(55945,'all',1,'agenda','left','agenda',55941,NULL,NULL,116,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2012-04-11 10:04:58'),(55946,'all',1,'agenda','left','agenda',55934,NULL,NULL,120,'/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Reportings','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$conf->agenda->enabled',2,'2012-04-11 10:04:58'),(55947,'all',1,'ecm','top','ecm',0,NULL,NULL,100,'/ecm/index.php','','MenuECM','ecm',NULL,NULL,'$user->rights->ecm->read || $user->rights->ecm->upload || $user->rights->ecm->setup','$conf->ecm->enabled',2,'2012-04-11 10:04:58'),(55948,'all',1,'ecm','left','ecm',55947,NULL,NULL,101,'/ecm/index.php','','ECMArea','ecm',NULL,NULL,'$user->rights->ecm->read || $user->rights->ecm->upload','$user->rights->ecm->read || $user->rights->ecm->upload',2,'2012-04-11 10:04:58'),(55949,'all',1,'ecm','left','ecm',55948,NULL,NULL,100,'/ecm/docdir.php?action=create','','ECMNewSection','ecm',NULL,NULL,'$user->rights->ecm->setup','$user->rights->ecm->setup',2,'2012-04-11 10:04:58'),(55950,'all',1,'ecm','left','ecm',55948,NULL,NULL,102,'/ecm/index.php?action=file_manager','','ECMFileManager','ecm',NULL,NULL,'$user->rights->ecm->read || $user->rights->ecm->upload','$user->rights->ecm->read || $user->rights->ecm->upload',2,'2012-04-11 10:04:58'),(55951,'all',1,'ecm','left','ecm',55948,NULL,NULL,103,'/ecm/search.php','','Search','ecm',NULL,NULL,'$user->rights->ecm->read','$user->rights->ecm->read',2,'2012-04-11 10:04:58'),(55952,'auguria',1,NULL,'top','home',0,NULL,NULL,1,'/index.php?mainmenu=home&amp;leftmenu=','','Home','',-1,'','','1',2,'2012-04-11 10:04:58'),(55953,'auguria',1,NULL,'top','companies',0,NULL,NULL,2,'/societe/index.php?mainmenu=companies&amp;leftmenu=','','ThirdParties','companies',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->societe->enabled || $conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(55954,'auguria',1,NULL,'top','products',0,NULL,NULL,3,'/product/index.php?mainmenu=products&amp;leftmenu=','','Products/Services','products',-1,'','$user->rights->produit->lire||$user->rights->service->lire','$conf->product->enabled || $conf->service->enabled',0,'2012-04-11 10:04:58'),(55956,'auguria',1,NULL,'top','commercial',0,NULL,NULL,5,'/comm/index.php?mainmenu=commercial&amp;leftmenu=','','Commercial','commercial',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(55957,'auguria',1,NULL,'top','accountancy',0,NULL,NULL,6,'/compta/index.php?mainmenu=accountancy&amp;leftmenu=','','MenuFinancial','compta',-1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire || $user->rights->facture->lire|| $user->rights->deplacement->lire || $user->rights->don->lire || $user->rights->tax->charges->lire','$conf->comptabilite->enabled || $conf->accounting->enabled || $conf->facture->enabled || $conf->deplacement->enabled || $conf->don->enabled  || $conf->tax->enabled',2,'2012-04-11 10:04:58'),(55958,'auguria',1,NULL,'top','project',0,NULL,NULL,7,'/projet/index.php?mainmenu=project&amp;leftmenu=','','Projects','projects',-1,'','$user->rights->projet->lire','$conf->projet->enabled',0,'2012-04-11 10:04:58'),(55959,'auguria',1,NULL,'top','tools',0,NULL,NULL,8,'/core/tools.php?mainmenu=tools&amp;leftmenu=','','Tools','other',-1,'','$user->rights->mailing->lire || $user->rights->export->lire || $user->rights->import->run','$conf->mailing->enabled || $conf->export->enabled || $conf->import->enabled',2,'2012-04-11 10:04:58'),(55962,'auguria',1,NULL,'top','shop',0,NULL,NULL,11,'/boutique/index.php?mainmenu=shop&amp;leftmenu=','','OSCommerce','shop',-1,'','','! empty($conf->boutique->enabled)',0,'2012-04-11 10:04:58'),(55964,'auguria',1,NULL,'top','members',0,NULL,NULL,15,'/adherents/index.php?mainmenu=members&amp;leftmenu=','','Members','members',-1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(55965,'auguria',1,NULL,'top','bank',0,NULL,NULL,6,'/compta/bank/index.php?mainmenu=bank&amp;leftmenu=bank','','MenuBankCash','banks',-1,'','$user->rights->banque->lire || $user->rights->prelevement->bons->lire','$conf->banque->enabled || $conf->prelevement->enabled',2,'2012-04-11 10:04:58'),(56051,'auguria',1,NULL,'left','home',55952,NULL,NULL,0,'/admin/index.php?leftmenu=setup','','Setup','admin',0,'setup','','$user->admin',2,'2012-04-11 10:04:58'),(56052,'auguria',1,NULL,'left','home',56051,NULL,NULL,1,'/admin/company.php?leftmenu=setup','','MenuCompanySetup','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56053,'auguria',1,NULL,'left','home',56051,NULL,NULL,4,'/admin/ihm.php?leftmenu=setup','','GUISetup','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56054,'auguria',1,NULL,'left','home',56051,NULL,NULL,2,'/admin/modules.php?leftmenu=setup','','Modules','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56055,'auguria',1,NULL,'left','home',56051,NULL,NULL,5,'/admin/boxes.php?leftmenu=setup','','Boxes','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56056,'auguria',1,NULL,'left','home',56051,NULL,NULL,3,'/admin/menus.php?leftmenu=setup','','Menus','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56057,'auguria',1,NULL,'left','home',56051,NULL,NULL,6,'/admin/delais.php?leftmenu=setup','','Alerts','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56058,'auguria',1,NULL,'left','home',56051,NULL,NULL,9,'/admin/pdf.php?leftmenu=setup','','PDF','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56059,'auguria',1,NULL,'left','home',56051,NULL,NULL,7,'/admin/proxy.php?leftmenu=setup','','Security','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56060,'auguria',1,NULL,'left','home',56051,NULL,NULL,10,'/admin/mails.php?leftmenu=setup','','Emails','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56061,'auguria',1,NULL,'left','home',56051,NULL,NULL,8,'/admin/limits.php?leftmenu=setup','','MenuLimits','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56062,'auguria',1,NULL,'left','home',56051,NULL,NULL,12,'/admin/dict.php?leftmenu=setup','','DictionnarySetup','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56063,'auguria',1,NULL,'left','home',56051,NULL,NULL,13,'/admin/const.php?leftmenu=setup','','OtherSetup','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56064,'auguria',1,NULL,'left','home',56051,NULL,NULL,11,'/admin/sms.php?leftmenu=setup','','Sms','admin',1,'','','$leftmenu==\'setup\'',2,'2012-04-11 10:04:58'),(56151,'auguria',1,NULL,'left','home',55952,NULL,NULL,1,'/admin/system/index.php?leftmenu=system','','SystemInfo','admin',0,'system','','$user->admin',2,'2012-04-11 10:04:58'),(56152,'auguria',1,NULL,'left','home',56151,NULL,NULL,0,'/admin/system/dolibarr.php?leftmenu=system','','Dolibarr','admin',1,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56153,'auguria',1,NULL,'left','home',56152,NULL,NULL,1,'/admin/system/constall.php?leftmenu=system','','AllParameters','admin',2,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56154,'auguria',1,NULL,'left','home',56152,NULL,NULL,4,'/admin/system/about.php?leftmenu=system','','About','admin',2,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56155,'auguria',1,NULL,'left','home',56151,NULL,NULL,1,'/admin/system/os.php?leftmenu=system','','OS','admin',1,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56156,'auguria',1,NULL,'left','home',56151,NULL,NULL,2,'/admin/system/web.php?leftmenu=system','','WebServer','admin',1,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56157,'auguria',1,NULL,'left','home',56151,NULL,NULL,3,'/admin/system/phpinfo.php?leftmenu=system','','Php','admin',1,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56158,'auguria',1,NULL,'left','home',56152,NULL,NULL,3,'/admin/triggers.php?leftmenu=system','','Triggers','admin',2,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56159,'auguria',1,NULL,'left','home',56152,NULL,NULL,2,'/admin/system/modules.php?leftmenu=system','','Modules','admin',2,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56161,'auguria',1,NULL,'left','home',56151,NULL,NULL,4,'/admin/system/database.php?leftmenu=system','','Database','admin',1,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56162,'auguria',1,NULL,'left','home',56161,NULL,NULL,0,'/admin/system/database-tables.php?leftmenu=system','','Tables','admin',2,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56163,'auguria',1,NULL,'left','home',56161,NULL,NULL,1,'/admin/system/database-tables-contraintes.php?leftmenu=system','','Constraints','admin',2,'','','$leftmenu==\'system\'',2,'2012-04-11 10:04:58'),(56251,'auguria',1,NULL,'left','home',55952,NULL,NULL,2,'/admin/tools/index.php?leftmenu=admintools','','SystemTools','admin',0,'admintools','','$user->admin',2,'2012-04-11 10:04:58'),(56252,'auguria',1,NULL,'left','home',56251,NULL,NULL,0,'/admin/tools/dolibarr_export.php?leftmenu=admintools','','Backup','admin',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56253,'auguria',1,NULL,'left','home',56251,NULL,NULL,1,'/admin/tools/dolibarr_import.php?leftmenu=admintools','','Restore','admin',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56254,'auguria',1,NULL,'left','home',56251,NULL,NULL,6,'/admin/tools/purge.php?leftmenu=admintools','','Purge','admin',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56255,'auguria',1,NULL,'left','home',56251,NULL,NULL,3,'/admin/tools/eaccelerator.php?leftmenu=admintools','','EAccelerator','admin',1,'','','$leftmenu==\'admintools\' && function_exists(\'eaccelerator_info\')',2,'2012-04-11 10:04:58'),(56256,'auguria',1,NULL,'left','home',56251,NULL,NULL,2,'/admin/tools/update.php?leftmenu=admintools','','MenuUpgrade','admin',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56257,'auguria',1,NULL,'left','home',56251,NULL,NULL,4,'/admin/tools/listevents.php?leftmenu=admintools','','Audit','admin',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56258,'auguria',1,NULL,'left','home',56251,NULL,NULL,7,'/support/index.php?leftmenu=admintools','_blank','HelpCenter','help',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56259,'auguria',1,NULL,'left','home',56251,NULL,NULL,5,'/admin/tools/listsessions.php?leftmenu=admintools','','Sessions','admin',1,'','','$leftmenu==\'admintools\'',2,'2012-04-11 10:04:58'),(56351,'auguria',1,NULL,'left','home',55952,NULL,NULL,3,'/user/home.php?leftmenu=users','','MenuUsersAndGroups','users',0,'users','','1',2,'2012-04-11 10:04:58'),(56352,'auguria',1,NULL,'left','home',56351,NULL,NULL,0,'/user/index.php?leftmenu=users','','Users','users',1,'','$user->rights->user->user->lire || $user->admin','$leftmenu==\'users\'',2,'2012-04-11 10:04:58'),(56353,'auguria',1,NULL,'left','home',56352,NULL,NULL,0,'/user/fiche.php?leftmenu=users&amp;action=create','','NewUser','users',2,'','$user->rights->user->user->creer || $user->admin','$leftmenu==\'users\'',2,'2012-04-11 10:04:58'),(56354,'auguria',1,NULL,'left','home',56351,NULL,NULL,1,'/user/group/index.php?leftmenu=users','','Groups','users',1,'','($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->read:$user->rights->user->user->lire) || $user->admin','$leftmenu==\'users\'',2,'2012-04-11 10:04:58'),(56355,'auguria',1,NULL,'left','home',56354,NULL,NULL,0,'/user/group/fiche.php?leftmenu=users&amp;action=create','','NewGroup','users',2,'','($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->write:$user->rights->user->user->creer) || $user->admin','$leftmenu==\'users\'',2,'2012-04-11 10:04:58'),(56451,'auguria',1,NULL,'left','companies',55953,NULL,NULL,0,'/societe/index.php?leftmenu=thirdparties','','ThirdParty','companies',0,'thirdparties','$user->rights->societe->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56452,'auguria',1,NULL,'left','companies',56451,NULL,NULL,0,'/societe/soc.php?action=create','','MenuNewThirdParty','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56454,'auguria',1,NULL,'left','companies',56451,NULL,NULL,5,'/fourn/liste.php?leftmenu=suppliers','','ListSuppliersShort','suppliers',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(56455,'auguria',1,NULL,'left','companies',56454,NULL,NULL,0,'/societe/soc.php?leftmenu=supplier&amp;action=create&amp;type=f','','NewSupplier','suppliers',2,'','$user->rights->societe->creer','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(56457,'auguria',1,NULL,'left','companies',56451,NULL,NULL,3,'/comm/prospect/list.php?leftmenu=prospects','','ListProspectsShort','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56458,'auguria',1,NULL,'left','companies',56457,NULL,NULL,0,'/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p','','MenuNewProspect','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56460,'auguria',1,NULL,'left','companies',56451,NULL,NULL,4,'/comm/list.php?leftmenu=customers','','ListCustomersShort','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56461,'auguria',1,NULL,'left','companies',56460,NULL,NULL,0,'/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c','','MenuNewCustomer','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56551,'auguria',1,NULL,'left','companies',55953,NULL,NULL,1,'/contact/list.php?leftmenu=contacts','','ContactsAddresses||Contacts@$conf->global->SOCIETE_ADDRESSES_MANAGEMENT','companies',0,'contacts','$user->rights->societe->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56552,'auguria',1,NULL,'left','companies',56551,NULL,NULL,0,'/contact/fiche.php?leftmenu=contacts&amp;action=create','','NewContactAddress||NewContact@$conf->global->SOCIETE_ADDRESSES_MANAGEMENT','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56553,'auguria',1,NULL,'left','companies',56551,NULL,NULL,1,'/contact/list.php?leftmenu=contacts','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56555,'auguria',1,NULL,'left','companies',56553,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=p','','Prospects','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56556,'auguria',1,NULL,'left','companies',56553,NULL,NULL,2,'/contact/list.php?leftmenu=contacts&amp;type=c','','Customers','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56557,'auguria',1,NULL,'left','companies',56553,NULL,NULL,3,'/contact/list.php?leftmenu=contacts&amp;type=f','','Suppliers','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(56558,'auguria',1,NULL,'left','companies',56553,NULL,NULL,4,'/contact/list.php?leftmenu=contacts&amp;type=o','','Others','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2012-04-11 10:04:58'),(56601,'auguria',1,NULL,'left','companies',55953,NULL,NULL,3,'/categories/index.php?leftmenu=cat&amp;type=1','','SuppliersCategoriesShort','categories',0,'cat','$user->rights->categorie->lire','$conf->societe->enabled && $conf->categorie->enabled',2,'2012-04-11 10:04:58'),(56602,'auguria',1,NULL,'left','companies',56601,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=1','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->societe->enabled && $conf->categorie->enabled',2,'2012-04-11 10:04:58'),(56611,'auguria',1,NULL,'left','companies',55953,NULL,NULL,4,'/categories/index.php?leftmenu=cat&amp;type=2','','CustomersProspectsCategoriesShort','categories',0,'cat','$user->rights->categorie->lire','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2012-04-11 10:04:58'),(56612,'auguria',1,NULL,'left','companies',56611,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=2','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2012-04-11 10:04:58'),(57051,'auguria',1,NULL,'left','commercial',55956,NULL,NULL,4,'/comm/propal/index.php?leftmenu=propals','','Prop','propal',0,'propals','$user->rights->propale->lire','$conf->propal->enabled',2,'2012-04-11 10:04:58'),(57052,'auguria',1,NULL,'left','commercial',57051,NULL,NULL,0,'/societe/societe.php?leftmenu=propals','','NewPropal','propal',1,'','$user->rights->propale->creer','$conf->propal->enabled',2,'2012-04-11 10:04:58'),(57053,'auguria',1,NULL,'left','commercial',57051,NULL,NULL,1,'/comm/propal/card.php?leftmenu=propals','','List','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2012-04-11 10:04:58'),(57054,'auguria',1,NULL,'left','commercial',57053,NULL,NULL,2,'/comm/propal/card.php?leftmenu=propals&amp;viewstatut=0','','PropalsDraft','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2012-04-11 10:04:58'),(57055,'auguria',1,NULL,'left','commercial',57053,NULL,NULL,3,'/comm/propal/card.php?leftmenu=propals&amp;viewstatut=1','','PropalsOpened','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2012-04-11 10:04:58'),(57056,'auguria',1,NULL,'left','commercial',57053,NULL,NULL,4,'/comm/propal/card.php?leftmenu=propals&amp;viewstatut=2','','PropalStatusSigned','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2012-04-11 10:04:58'),(57057,'auguria',1,NULL,'left','commercial',57053,NULL,NULL,5,'/comm/propal/card.php?leftmenu=propals&amp;viewstatut=3','','PropalStatusNotSigned','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2012-04-11 10:04:58'),(57058,'auguria',1,NULL,'left','commercial',57053,NULL,NULL,6,'/comm/propal/card.php?leftmenu=propals&amp;viewstatut=4','','PropalStatusBilled','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2012-04-11 10:04:58'),(57061,'auguria',1,NULL,'left','commercial',57051,NULL,NULL,4,'/comm/propal/stats/index.php?leftmenu=propals','','Statistics','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2012-04-11 10:04:58'),(57151,'auguria',1,NULL,'left','commercial',55956,NULL,NULL,5,'/commande/index.php?leftmenu=orders','','CustomersOrders','orders',0,'orders','$user->rights->commande->lire','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(57152,'auguria',1,NULL,'left','commercial',57151,NULL,NULL,0,'/societe/societe.php?leftmenu=orders','','NewOrder','orders',1,'','$user->rights->commande->creer','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(57153,'auguria',1,NULL,'left','commercial',57151,NULL,NULL,1,'/commande/liste.php?leftmenu=orders','','List','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(57154,'auguria',1,NULL,'left','commercial',57153,NULL,NULL,2,'/commande/liste.php?leftmenu=orders&amp;viewstatut=0','','StatusOrderDraftShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2012-04-11 10:04:58'),(57155,'auguria',1,NULL,'left','commercial',57153,NULL,NULL,3,'/commande/liste.php?leftmenu=orders&amp;viewstatut=1','','StatusOrderValidated','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2012-04-11 10:04:58'),(57156,'auguria',1,NULL,'left','commercial',57153,NULL,NULL,4,'/commande/liste.php?leftmenu=orders&amp;viewstatut=2','','StatusOrderOnProcessShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2012-04-11 10:04:58'),(57157,'auguria',1,NULL,'left','commercial',57153,NULL,NULL,5,'/commande/liste.php?leftmenu=orders&amp;viewstatut=3','','StatusOrderToBill','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2012-04-11 10:04:58'),(57158,'auguria',1,NULL,'left','commercial',57153,NULL,NULL,6,'/commande/liste.php?leftmenu=orders&amp;viewstatut=4','','StatusOrderProcessed','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2012-04-11 10:04:58'),(57159,'auguria',1,NULL,'left','commercial',57153,NULL,NULL,7,'/commande/liste.php?leftmenu=orders&amp;viewstatut=-1','','StatusOrderCanceledShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2012-04-11 10:04:58'),(57160,'auguria',1,NULL,'left','commercial',57151,NULL,NULL,4,'/commande/stats/index.php?leftmenu=orders','','Statistics','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(57251,'auguria',1,NULL,'left','commercial',55954,NULL,NULL,6,'/expedition/index.php?leftmenu=sendings','','Shipments','sendings',0,'sendings','$user->rights->expedition->lire','$conf->expedition->enabled',2,'2012-04-11 10:04:58'),(57252,'auguria',1,NULL,'left','commercial',57251,NULL,NULL,0,'/expedition/fiche.php?action=create2&leftmenu=sendings','','NewSending','sendings',1,'','$user->rights->expedition->creer','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2012-04-11 10:04:58'),(57253,'auguria',1,NULL,'left','commercial',57251,NULL,NULL,1,'/expedition/liste.php?leftmenu=sendings','','List','sendings',1,'','$user->rights->expedition->lire','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2012-04-11 10:04:58'),(57254,'auguria',1,NULL,'left','commercial',57251,NULL,NULL,2,'/expedition/stats/index.php?leftmenu=sendings','','Statistics','sendings',1,'','$user->rights->expedition->lire','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2012-04-11 10:04:58'),(57351,'auguria',1,NULL,'left','commercial',55956,NULL,NULL,7,'/contrat/index.php?leftmenu=contracts','','Contracts','contracts',0,'contracts','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2012-04-11 10:04:58'),(57352,'auguria',1,NULL,'left','commercial',57351,NULL,NULL,0,'/societe/societe.php?leftmenu=contracts','','NewContract','contracts',1,'','$user->rights->contrat->creer','$conf->contrat->enabled',2,'2012-04-11 10:04:58'),(57353,'auguria',1,NULL,'left','commercial',57351,NULL,NULL,1,'/contrat/liste.php?leftmenu=contracts','','List','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2012-04-11 10:04:58'),(57354,'auguria',1,NULL,'left','commercial',57351,NULL,NULL,2,'/contrat/services.php?leftmenu=contracts','','MenuServices','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2012-04-11 10:04:58'),(57355,'auguria',1,NULL,'left','commercial',57354,NULL,NULL,0,'/contrat/services.php?leftmenu=contracts&amp;mode=0','','MenuInactiveServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2012-04-11 10:04:58'),(57356,'auguria',1,NULL,'left','commercial',57354,NULL,NULL,1,'/contrat/services.php?leftmenu=contracts&amp;mode=4','','MenuRunningServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2012-04-11 10:04:58'),(57357,'auguria',1,NULL,'left','commercial',57354,NULL,NULL,2,'/contrat/services.php?leftmenu=contracts&amp;mode=4&amp;filter=expired','','MenuExpiredServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2012-04-11 10:04:58'),(57358,'auguria',1,NULL,'left','commercial',57354,NULL,NULL,3,'/contrat/services.php?leftmenu=contracts&amp;mode=5','','MenuClosedServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2012-04-11 10:04:58'),(57451,'auguria',1,NULL,'left','commercial',55956,NULL,NULL,8,'/fichinter/list.php?leftmenu=ficheinter','','Interventions','interventions',0,'ficheinter','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2012-04-11 10:04:58'),(57452,'auguria',1,NULL,'left','commercial',57451,NULL,NULL,0,'/fichinter/fiche.php?action=create&amp;leftmenu=ficheinter','','NewIntervention','interventions',1,'','$user->rights->ficheinter->creer','$conf->ficheinter->enabled',2,'2012-04-11 10:04:58'),(57453,'auguria',1,NULL,'left','commercial',57451,NULL,NULL,1,'/fichinter/list.php?leftmenu=ficheinter','','List','interventions',1,'','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2012-04-11 10:04:58'),(57551,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,3,'/fourn/facture/index.php?leftmenu=suppliers_bills','','BillsSuppliers','bills',0,'supplier_bills','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(57552,'auguria',1,NULL,'left','accountancy',57551,NULL,NULL,0,'/fourn/facture/fiche.php?action=create&amp;leftmenu=suppliers_bills','','NewBill','bills',1,'','$user->rights->fournisseur->facture->creer','$conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(57553,'auguria',1,NULL,'left','accountancy',57551,NULL,NULL,1,'/fourn/facture/impayees.php?leftmenu=suppliers_bills','','Unpaid','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(57554,'auguria',1,NULL,'left','accountancy',57551,NULL,NULL,2,'/fourn/facture/paiement.php?leftmenu=suppliers_bills','','Payments','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(57555,'auguria',1,NULL,'left','accountancy',57551,NULL,NULL,8,'/compta/facture/stats/index.php?leftmenu=customers_bills&mode=supplier','','Statistics','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2012-04-11 10:04:58'),(57651,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,3,'/compta/facture.php?leftmenu=customers_bills','','BillsCustomers','bills',0,'customer_bills','$user->rights->facture->lire','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57652,'auguria',1,NULL,'left','accountancy',57651,NULL,NULL,3,'/compta/clients.php?action=facturer&amp;leftmenu=customers_bills','','NewBill','bills',1,'','$user->rights->facture->creer','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57653,'auguria',1,NULL,'left','accountancy',57651,NULL,NULL,4,'/compta/facture/fiche-rec.php?leftmenu=customers_bills','','Repeatable','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57654,'auguria',1,NULL,'left','accountancy',57651,NULL,NULL,5,'/compta/facture/impayees.php?action=facturer&amp;leftmenu=customers_bills','','Unpaid','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57655,'auguria',1,NULL,'left','accountancy',57651,NULL,NULL,6,'/compta/paiement/liste.php?leftmenu=customers_bills','','Payments','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57661,'auguria',1,NULL,'left','accountancy',57655,NULL,NULL,1,'/compta/paiement/rapport.php?leftmenu=customers_bills','','Reportings','bills',2,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57662,'auguria',1,NULL,'left','accountancy',55965,NULL,NULL,9,'/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank','','MenuChequeDeposits','bills',0,'checks','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2012-04-11 10:04:58'),(57663,'auguria',1,NULL,'left','accountancy',57662,NULL,NULL,0,'/compta/paiement/cheque/fiche.php?leftmenu=checks&amp;action=new','','NewCheckDeposit','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2012-04-11 10:04:58'),(57664,'auguria',1,NULL,'left','accountancy',57662,NULL,NULL,1,'/compta/paiement/cheque/liste.php?leftmenu=checks','','List','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2012-04-11 10:04:58'),(57665,'auguria',1,NULL,'left','accountancy',57651,NULL,NULL,8,'/compta/facture/stats/index.php?leftmenu=customers_bills','','Statistics','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2012-04-11 10:04:58'),(57851,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,3,'/commande/liste.php?leftmenu=orders&amp;viewstatut=3','','MenuOrdersToBill','orders',0,'orders','$user->rights->commande->lire','$conf->commande->enabled',0,'2012-04-11 10:04:58'),(57951,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,4,'/compta/dons/index.php?leftmenu=donations&amp;mainmenu=accountancy','','Donations','donations',0,'donations','$user->rights->don->lire','$conf->don->enabled',2,'2012-04-11 10:04:58'),(57952,'auguria',1,NULL,'left','accountancy',57951,NULL,NULL,0,'/compta/dons/fiche.php?leftmenu=donations&amp;mainmenu=accountancy&amp;action=create','','NewDonation','donations',1,'','$user->rights->don->creer','$conf->don->enabled && $leftmenu==\"donations\"',2,'2012-04-11 10:04:58'),(57953,'auguria',1,NULL,'left','accountancy',57951,NULL,NULL,1,'/compta/dons/liste.php?leftmenu=donations&amp;mainmenu=accountancy','','List','donations',1,'','$user->rights->don->lire','$conf->don->enabled && $leftmenu==\"donations\"',2,'2012-04-11 10:04:58'),(58051,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,5,'/compta/deplacement/index.php?leftmenu=tripsandexpenses','','TripsAndExpenses','trips',0,'tripsandexpenses','$user->rights->deplacement->lire','$conf->deplacement->enabled',0,'2012-04-11 10:04:58'),(58052,'auguria',1,NULL,'left','accountancy',58051,NULL,NULL,1,'/compta/deplacement/fiche.php?action=create&amp;leftmenu=tripsandexpenses','','New','trips',1,'','$user->rights->deplacement->creer','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2012-04-11 10:04:58'),(58053,'auguria',1,NULL,'left','accountancy',58051,NULL,NULL,2,'/compta/deplacement/list.php?leftmenu=tripsandexpenses','','List','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2012-04-11 10:04:58'),(58054,'auguria',1,NULL,'left','accountancy',58051,NULL,NULL,2,'/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses','','Statistics','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2012-04-11 10:04:58'),(58151,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,6,'/compta/charges/index.php?leftmenu=tax&amp;mainmenu=accountancy','','MenuTaxAndDividends','compta',0,'tax','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2012-04-11 10:04:58'),(58152,'auguria',1,NULL,'left','accountancy',58151,NULL,NULL,1,'/compta/sociales/index.php?leftmenu=tax_social','','SocialContributions','',1,'tax_social','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2012-04-11 10:04:58'),(58153,'auguria',1,NULL,'left','accountancy',58152,NULL,NULL,2,'/compta/sociales/charges.php?leftmenu=tax_social&amp;action=create','','MenuNewSocialContribution','',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2012-04-11 10:04:58'),(58154,'auguria',1,NULL,'left','accountancy',58152,NULL,NULL,3,'/compta/charges/index.php?leftmenu=tax_social&amp;mainmenu=accountancy&amp;mode=sconly','','Payments','',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2012-04-11 10:04:58'),(58251,'auguria',1,NULL,'left','accountancy',58151,NULL,NULL,7,'/compta/tva/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy','','VAT','companies',1,'tax_vat','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS)',0,'2012-04-11 10:04:58'),(58252,'auguria',1,NULL,'left','accountancy',58251,NULL,NULL,0,'/compta/tva/fiche.php?leftmenu=tax_vat&amp;action=create','','NewPayment','companies',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2012-04-11 10:04:58'),(58253,'auguria',1,NULL,'left','accountancy',58251,NULL,NULL,1,'/compta/tva/reglement.php?leftmenu=tax_vat','','Payments','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2012-04-11 10:04:58'),(58254,'auguria',1,NULL,'left','accountancy',58251,NULL,NULL,2,'/compta/tva/clients.php?leftmenu=tax_vat','','ReportByCustomers','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2012-04-11 10:04:58'),(58255,'auguria',1,NULL,'left','accountancy',58251,NULL,NULL,3,'/compta/tva/quadri_detail.php?leftmenu=tax_vat','','ReportByQuarter','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2012-04-11 10:04:58'),(58351,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,8,'/compta/ventilation/index.php?leftmenu=ventil','','Ventilation','companies',0,'ventil','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58352,'auguria',1,NULL,'left','accountancy',58351,NULL,NULL,0,'/compta/ventilation/liste.php','','ToDispatch','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58353,'auguria',1,NULL,'left','accountancy',58351,NULL,NULL,1,'/compta/ventilation/lignes.php','','Dispatched','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58354,'auguria',1,NULL,'left','accountancy',58351,NULL,NULL,2,'/compta/param/','','Setup','companies',1,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58355,'auguria',1,NULL,'left','accountancy',58354,NULL,NULL,0,'/compta/param/comptes/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58356,'auguria',1,NULL,'left','accountancy',58354,NULL,NULL,1,'/compta/param/comptes/fiche.php?action=create','','New','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58357,'auguria',1,NULL,'left','accountancy',58351,NULL,NULL,3,'/compta/export/','','Export','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58358,'auguria',1,NULL,'left','accountancy',58357,NULL,NULL,0,'/compta/export/index.php','','New','companies',2,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58359,'auguria',1,NULL,'left','accountancy',58357,NULL,NULL,1,'/compta/export/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2012-04-11 10:04:58'),(58451,'auguria',1,NULL,'left','accountancy',55965,NULL,NULL,9,'/compta/prelevement/index.php?leftmenu=withdraw&amp;mainmenu=bank','','StandingOrders','withdrawals',0,'withdraw','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled',2,'2012-04-11 10:04:58'),(58453,'auguria',1,NULL,'left','accountancy',58451,NULL,NULL,0,'/compta/prelevement/create.php?leftmenu=withdraw','','NewStandingOrder','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2012-04-11 10:04:58'),(58454,'auguria',1,NULL,'left','accountancy',58451,NULL,NULL,2,'/compta/prelevement/bons.php?leftmenu=withdraw','','WithdrawalsReceipts','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2012-04-11 10:04:58'),(58455,'auguria',1,NULL,'left','accountancy',58451,NULL,NULL,3,'/compta/prelevement/liste.php?leftmenu=withdraw','','WithdrawalsLines','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2012-04-11 10:04:58'),(58457,'auguria',1,NULL,'left','accountancy',58451,NULL,NULL,5,'/compta/prelevement/rejets.php?leftmenu=withdraw','','Rejects','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2012-04-11 10:04:58'),(58458,'auguria',1,NULL,'left','accountancy',58451,NULL,NULL,6,'/compta/prelevement/stats.php?leftmenu=withdraw','','Statistics','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2012-04-11 10:04:58'),(58551,'auguria',1,NULL,'left','accountancy',55965,NULL,NULL,1,'/compta/bank/index.php?leftmenu=bank&amp;mainmenu=bank','','MenuBankCash','banks',0,'bank','$user->rights->banque->lire','$conf->banque->enabled',0,'2012-04-11 10:04:58'),(58552,'auguria',1,NULL,'left','accountancy',58551,NULL,NULL,0,'/compta/bank/fiche.php?action=create&amp;leftmenu=bank','','MenuNewFinancialAccount','banks',1,'','$user->rights->banque->configurer','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2012-04-11 10:04:58'),(58553,'auguria',1,NULL,'left','accountancy',58551,NULL,NULL,1,'/compta/bank/categ.php?leftmenu=bank','','Rubriques','categories',1,'','$user->rights->banque->configurer','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2012-04-11 10:04:58'),(58554,'auguria',1,NULL,'left','accountancy',58551,NULL,NULL,2,'/compta/bank/search.php?leftmenu=bank','','ListTransactions','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2012-04-11 10:04:58'),(58555,'auguria',1,NULL,'left','accountancy',58551,NULL,NULL,3,'/compta/bank/budget.php?leftmenu=bank','','ListTransactionsByCategory','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2012-04-11 10:04:58'),(58557,'auguria',1,NULL,'left','accountancy',58551,NULL,NULL,5,'/compta/bank/virement.php?leftmenu=bank','','BankTransfers','banks',1,'','$user->rights->banque->transfer','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2012-04-11 10:04:58'),(58651,'auguria',1,NULL,'left','accountancy',55957,NULL,NULL,11,'/compta/resultat/index.php?leftmenu=ca&amp;mainmenu=accountancy','','Reportings','main',0,'ca','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58652,'auguria',1,NULL,'left','accountancy',58651,NULL,NULL,0,'/compta/resultat/index.php?leftmenu=ca','','ReportInOut','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58653,'auguria',1,NULL,'left','accountancy',58652,NULL,NULL,0,'/compta/resultat/clientfourn.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58654,'auguria',1,NULL,'left','accountancy',58651,NULL,NULL,1,'/compta/stats/index.php?leftmenu=ca','','ReportTurnover','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58655,'auguria',1,NULL,'left','accountancy',58654,NULL,NULL,0,'/compta/stats/casoc.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58656,'auguria',1,NULL,'left','accountancy',58654,NULL,NULL,1,'/compta/stats/cabyuser.php?leftmenu=ca','','ByUsers','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58657,'auguria',1,NULL,'left','accountancy',58651,NULL,NULL,1,'/compta/journal/sellsjournal.php?leftmenu=ca','','SellsJournal','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58658,'auguria',1,NULL,'left','accountancy',58651,NULL,NULL,1,'/compta/journal/purchasesjournal.php?leftmenu=ca','','PurchasesJournal','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2012-04-11 10:04:58'),(58751,'auguria',1,NULL,'left','products',55954,NULL,NULL,0,'/product/index.php?leftmenu=product&amp;type=0','','Products','products',0,'product','$user->rights->produit->lire','$conf->product->enabled',2,'2012-04-11 10:04:58'),(58752,'auguria',1,NULL,'left','products',58751,NULL,NULL,0,'/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0','','NewProduct','products',1,'','$user->rights->produit->creer','$conf->product->enabled',2,'2012-04-11 10:04:58'),(58753,'auguria',1,NULL,'left','products',58751,NULL,NULL,1,'/product/liste.php?leftmenu=product&amp;type=0','','List','products',1,'','$user->rights->produit->lire','$conf->product->enabled',2,'2012-04-11 10:04:58'),(58754,'auguria',1,NULL,'left','products',58751,NULL,NULL,4,'/product/reassort.php?type=0','','Stocks','products',1,'','$user->rights->produit->lire && $user->rights->stock->lire','$conf->product->enabled',2,'2012-04-11 10:04:58'),(58755,'auguria',1,NULL,'left','products',58751,NULL,NULL,5,'/product/popuprop.php?leftmenu=stats&amp;type=0','','Statistics','main',1,'','$user->rights->produit->lire','$conf->propal->enabled',2,'2012-04-11 10:04:58'),(58851,'auguria',1,NULL,'left','products',55954,NULL,NULL,1,'/product/index.php?leftmenu=service&amp;type=1','','Services','products',0,'service','$user->rights->service->lire','$conf->service->enabled',2,'2012-04-11 10:04:58'),(58852,'auguria',1,NULL,'left','products',58851,NULL,NULL,0,'/product/fiche.php?leftmenu=service&amp;action=create&amp;type=1','','NewService','products',1,'','$user->rights->service->creer','$conf->service->enabled',2,'2012-04-11 10:04:58'),(58853,'auguria',1,NULL,'left','products',58851,NULL,NULL,1,'/product/liste.php?leftmenu=service&amp;type=1','','List','products',1,'','$user->rights->service->lire','$conf->service->enabled',2,'2012-04-11 10:04:58'),(58854,'auguria',1,NULL,'left','products',58851,NULL,NULL,5,'/product/popuprop.php?leftmenu=stats&amp;type=1','','Statistics','main',1,'','$user->rights->service->lire','$conf->propal->enabled',2,'2012-04-11 10:04:58'),(59051,'auguria',1,NULL,'left','products',55954,NULL,NULL,3,'/product/stock/index.php?leftmenu=stock','','Stock','stocks',0,'stock','$user->rights->stock->lire','$conf->stock->enabled',2,'2012-04-11 10:04:58'),(59052,'auguria',1,NULL,'left','products',59051,NULL,NULL,0,'/product/stock/fiche.php?action=create','','MenuNewWarehouse','stocks',1,'','$user->rights->stock->creer','$conf->stock->enabled',2,'2012-04-11 10:04:58'),(59053,'auguria',1,NULL,'left','products',59051,NULL,NULL,1,'/product/stock/liste.php','','List','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2012-04-11 10:04:58'),(59054,'auguria',1,NULL,'left','products',59051,NULL,NULL,2,'/product/stock/valo.php','','EnhancedValue','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2012-04-11 10:04:58'),(59055,'auguria',1,NULL,'left','products',59051,NULL,NULL,3,'/product/stock/mouvement.php','','Movements','stocks',1,'','$user->rights->stock->mouvement->lire','$conf->stock->enabled',2,'2012-04-11 10:04:58'),(59151,'auguria',1,NULL,'left','products',55954,NULL,NULL,4,'/categories/index.php?leftmenu=cat&amp;type=0','','Categories','categories',0,'cat','$user->rights->categorie->lire','$conf->categorie->enabled',2,'2012-04-11 10:04:58'),(59152,'auguria',1,NULL,'left','products',59151,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=0','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->categorie->enabled',2,'2012-04-11 10:04:58'),(59551,'auguria',1,NULL,'left','project',55958,NULL,NULL,0,'/projet/index.php?leftmenu=projects','','Projects','projects',0,'projects','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59552,'auguria',1,NULL,'left','project',59551,NULL,NULL,1,'/projet/fiche.php?leftmenu=projects&amp;action=create','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59553,'auguria',1,NULL,'left','project',59551,NULL,NULL,2,'/projet/liste.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59561,'auguria',1,NULL,'left','project',55958,NULL,NULL,0,'/projet/index.php?leftmenu=projects&amp;mode=mine','','MyProjects','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59562,'auguria',1,NULL,'left','project',59561,NULL,NULL,1,'/projet/fiche.php?leftmenu=projects&amp;action=create&amp;mode=mine','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59563,'auguria',1,NULL,'left','project',59561,NULL,NULL,2,'/projet/liste.php?leftmenu=projects&amp;mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59651,'auguria',1,NULL,'left','project',55958,NULL,NULL,0,'/projet/activity/index.php?leftmenu=projects','','Activities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59652,'auguria',1,NULL,'left','project',59651,NULL,NULL,1,'/projet/tasks.php?leftmenu=projects&amp;action=create','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59653,'auguria',1,NULL,'left','project',59651,NULL,NULL,2,'/projet/tasks/index.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59654,'auguria',1,NULL,'left','project',59651,NULL,NULL,3,'/projet/activity/list.php?leftmenu=projects','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59751,'auguria',1,NULL,'left','project',55958,NULL,NULL,0,'/projet/activity/index.php?leftmenu=projects&amp;mode=mine','','MyActivities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59752,'auguria',1,NULL,'left','project',59751,NULL,NULL,1,'/projet/tasks.php?leftmenu=projects&amp;action=create&amp;mode=mine','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59753,'auguria',1,NULL,'left','project',59751,NULL,NULL,2,'/projet/tasks/index.php?leftmenu=projects&amp;mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59754,'auguria',1,NULL,'left','project',59751,NULL,NULL,3,'/projet/activity/list.php?leftmenu=projects&amp;mode=mine','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2012-04-11 10:04:58'),(59851,'auguria',1,NULL,'left','tools',55959,NULL,NULL,0,'/comm/mailing/index.php?leftmenu=mailing','','EMailings','mails',0,'mailing','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2012-04-11 10:04:58'),(59852,'auguria',1,NULL,'left','tools',59851,NULL,NULL,0,'/comm/mailing/fiche.php?leftmenu=mailing&amp;action=create','','NewMailing','mails',1,'','$user->rights->mailing->creer','$conf->mailing->enabled',0,'2012-04-11 10:04:58'),(59853,'auguria',1,NULL,'left','tools',59851,NULL,NULL,1,'/comm/mailing/liste.php?leftmenu=mailing','','List','mails',1,'','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2012-04-11 10:04:58'),(60051,'auguria',1,NULL,'left','tools',55959,NULL,NULL,2,'/exports/index.php?leftmenu=export','','FormatedExport','exports',0,'export','$user->rights->export->lire','$conf->export->enabled',2,'2012-04-11 10:04:58'),(60052,'auguria',1,NULL,'left','tools',60051,NULL,NULL,0,'/exports/export.php?leftmenu=export','','NewExport','exports',1,'','$user->rights->export->creer','$conf->export->enabled',2,'2012-04-11 10:04:58'),(60081,'auguria',1,NULL,'left','tools',55959,NULL,NULL,2,'/imports/index.php?leftmenu=import','','FormatedImport','exports',0,'import','$user->rights->import->run','$conf->import->enabled',2,'2012-04-11 10:04:58'),(60082,'auguria',1,NULL,'left','tools',60081,NULL,NULL,0,'/imports/import.php?leftmenu=import','','NewImport','exports',1,'','$user->rights->import->run','$conf->import->enabled',2,'2012-04-11 10:04:58'),(60151,'auguria',1,NULL,'left','members',55964,NULL,NULL,0,'/adherents/index.php?leftmenu=members&amp;mainmenu=members','','Members','members',0,'members','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60152,'auguria',1,NULL,'left','members',60151,NULL,NULL,0,'/adherents/fiche.php?leftmenu=members&amp;action=create','','NewMember','members',1,'','$user->rights->adherent->creer','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60153,'auguria',1,NULL,'left','members',60151,NULL,NULL,1,'/adherents/liste.php','','List','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60154,'auguria',1,NULL,'left','members',60153,NULL,NULL,2,'/adherents/liste.php?leftmenu=members&amp;statut=-1','','MenuMembersToValidate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60155,'auguria',1,NULL,'left','members',60153,NULL,NULL,3,'/adherents/liste.php?leftmenu=members&amp;statut=1','','MenuMembersValidated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60156,'auguria',1,NULL,'left','members',60153,NULL,NULL,4,'/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=outofdate','','MenuMembersNotUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60157,'auguria',1,NULL,'left','members',60153,NULL,NULL,5,'/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=uptodate','','MenuMembersUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60158,'auguria',1,NULL,'left','members',60153,NULL,NULL,6,'/adherents/liste.php?leftmenu=members&amp;statut=0','','MenuMembersResiliated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60159,'auguria',1,NULL,'left','members',60151,NULL,NULL,7,'/adherents/stats/geo.php?leftmenu=members&amp;mode=memberbycountry','','MenuMembersStats','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60251,'auguria',1,NULL,'left','members',55964,NULL,NULL,1,'/adherents/index.php?leftmenu=members&amp;mainmenu=members','','Subscriptions','compta',0,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60252,'auguria',1,NULL,'left','members',60251,NULL,NULL,0,'/adherents/liste.php?statut=-1&amp;leftmenu=accountancy&amp;mainmenu=members','','NewSubscription','compta',1,'','$user->rights->adherent->cotisation->creer','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60253,'auguria',1,NULL,'left','members',60251,NULL,NULL,1,'/adherents/cotisations.php?leftmenu=members','','List','compta',1,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60254,'auguria',1,NULL,'left','members',60251,NULL,NULL,7,'/adherents/stats/index.php?leftmenu=members','','MenuMembersStats','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60451,'auguria',1,NULL,'left','members',55964,NULL,NULL,3,'/adherents/index.php?leftmenu=export&amp;mainmenu=members','','Exports','members',0,'export','$user->rights->adherent->export','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60452,'auguria',1,NULL,'left','members',60451,NULL,NULL,0,'/exports/index.php?leftmenu=export','','Datas','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled && $conf->export->enabled',2,'2012-04-11 10:04:58'),(60453,'auguria',1,NULL,'left','members',60451,NULL,NULL,1,'/adherents/htpasswd.php?leftmenu=export','','Filehtpasswd','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60454,'auguria',1,NULL,'left','members',60451,NULL,NULL,2,'/adherents/cartes/carte.php?leftmenu=export','','MembersCards','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60651,'auguria',1,NULL,'left','members',55964,NULL,NULL,5,'/adherents/type.php?leftmenu=setup&amp;mainmenu=members','','MembersTypes','members',0,'setup','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60652,'auguria',1,NULL,'left','members',60651,NULL,NULL,0,'/adherents/type.php?leftmenu=setup&amp;mainmenu=members&amp;action=create','','New','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(60653,'auguria',1,NULL,'left','members',60651,NULL,NULL,1,'/adherents/type.php?leftmenu=setup&amp;mainmenu=members','','List','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2012-04-11 10:04:58'),(61051,'auguria',1,NULL,'left','commercial',55956,NULL,NULL,6,'/fourn/commande/index.php?leftmenu=orders_suppliers','','SuppliersOrders','orders',0,'orders_suppliers','$user->rights->fournisseur->commande->lire','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(61052,'auguria',1,NULL,'left','commercial',61051,NULL,NULL,0,'/societe/societe.php?leftmenu=orders_suppliers','','NewOrder','orders',1,'','$user->rights->fournisseur->commande->creer','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(61053,'auguria',1,NULL,'left','commercial',61051,NULL,NULL,1,'/fourn/commande/liste.php?leftmenu=orders_suppliers&amp;viewstatut=0','','List','orders',1,'','$user->rights->fournisseur->commande->lire','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(61059,'auguria',1,NULL,'left','commercial',61051,NULL,NULL,7,'/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier','','Statistics','orders',1,'','$user->rights->fournisseur->commande->lire','$conf->commande->enabled',2,'2012-04-11 10:04:58'),(61151,'auguria',1,NULL,'left','members',55964,NULL,NULL,3,'/categories/index.php?leftmenu=cat&amp;type=3','','MembersCategoriesShort','categories',0,'cat','$user->rights->categorie->lire','$conf->adherent->enabled && $conf->categorie->enabled',2,'2012-04-11 10:04:58'),(61152,'auguria',1,NULL,'left','members',61151,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=3','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->adherent->enabled && $conf->categorie->enabled',2,'2012-04-11 10:04:58');
/*!40000 ALTER TABLE `llx_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_monitoring_probes`
--

DROP TABLE IF EXISTS `llx_monitoring_probes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_monitoring_probes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `groupname` varchar(64) DEFAULT NULL,
  `url` varchar(250) NOT NULL,
  `useproxy` int(11) DEFAULT '0',
  `checkkey` varchar(250) DEFAULT NULL,
  `maxval` int(11) DEFAULT NULL,
  `frequency` int(11) DEFAULT '60',
  `active` int(11) DEFAULT '1',
  `status` int(11) DEFAULT '0',
  `lastreset` datetime DEFAULT NULL,
  `oldesterrortext` text,
  `oldesterrordate` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_monitoring_probes` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_monitoring_probes`
--

LOCK TABLES `llx_monitoring_probes` WRITE;
/*!40000 ALTER TABLE `llx_monitoring_probes` DISABLE KEYS */;
INSERT INTO `llx_monitoring_probes` (`rowid`, `title`, `groupname`, `url`, `useproxy`, `checkkey`, `maxval`, `frequency`, `active`, `status`, `lastreset`, `oldesterrortext`, `oldesterrordate`) VALUES (1,'aaa',NULL,'http://www.chiensderace.com',0,'chiens',1000,10,1,1,'2011-04-20 23:46:41',NULL,NULL),(2,'ChatsDeRace',NULL,'http://www.chatsderace.com',0,'chats',1000,5,1,1,'2011-04-20 23:46:41',NULL,NULL);
/*!40000 ALTER TABLE `llx_monitoring_probes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_notify`
--

DROP TABLE IF EXISTS `llx_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_notify` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `daten` datetime DEFAULT NULL,
  `fk_action` int(11) NOT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `objet_type` varchar(24) NOT NULL,
  `objet_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `type` varchar(16) DEFAULT 'email',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_notify_def` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` date DEFAULT NULL,
  `fk_action` int(11) NOT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `type` varchar(16) DEFAULT 'email',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` datetime DEFAULT NULL,
  `amount` double(24,8) DEFAULT NULL,
  `fk_paiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_export_compta` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiement`
--

LOCK TABLES `llx_paiement` WRITE;
/*!40000 ALTER TABLE `llx_paiement` DISABLE KEYS */;
INSERT INTO `llx_paiement` (`rowid`, `entity`, `datec`, `tms`, `datep`, `amount`, `fk_paiement`, `num_paiement`, `note`, `fk_bank`, `fk_user_creat`, `fk_user_modif`, `statut`, `fk_export_compta`) VALUES (1,1,'2010-07-10 14:59:41','2010-07-10 12:59:41','2010-07-10 12:00:00',0.02000000,4,'','',4,1,NULL,0,0),(2,1,'2011-07-18 20:50:24','2011-07-18 18:50:24','2011-07-08 12:00:00',20.00000000,6,'','',5,1,NULL,0,0),(3,1,'2011-07-18 20:50:47','2011-07-18 18:50:47','2011-07-08 12:00:00',10.00000000,4,'','',6,1,NULL,0,0),(5,1,'2011-08-01 03:34:11','2011-08-01 01:34:11','2011-08-01 03:34:11',5.63000000,6,'','Payment Invoice FA1108-0003',8,1,NULL,0,0),(6,1,'2011-08-06 20:33:54','2011-08-06 18:33:54','2011-08-06 20:33:53',5.98000000,4,'','Payment Invoice FA1108-0004',13,1,NULL,0,0),(8,1,'2011-08-08 02:53:40','2011-08-08 00:53:40','2011-08-08 12:00:00',26.10000000,4,'','',14,1,NULL,0,0),(9,1,'2011-08-08 02:55:58','2011-08-08 00:55:58','2011-08-08 12:00:00',26.96000000,1,'','',15,1,NULL,0,0);
/*!40000 ALTER TABLE `llx_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement_facture`
--

DROP TABLE IF EXISTS `llx_paiement_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiement_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_paiement` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `amount` double(24,8) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiement_facture` (`fk_paiement`,`fk_facture`),
  KEY `idx_paiement_facture_fk_facture` (`fk_facture`),
  KEY `idx_paiement_facture_fk_paiement` (`fk_paiement`),
  CONSTRAINT `fk_paiement_facture_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_paiement_facture_fk_paiement` FOREIGN KEY (`fk_paiement`) REFERENCES `llx_paiement` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiement_facture`
--

LOCK TABLES `llx_paiement_facture` WRITE;
/*!40000 ALTER TABLE `llx_paiement_facture` DISABLE KEYS */;
INSERT INTO `llx_paiement_facture` (`rowid`, `fk_paiement`, `fk_facture`, `amount`) VALUES (1,1,1,0.02000000),(2,2,2,20.00000000),(3,3,2,10.00000000),(5,5,5,5.63000000),(6,6,6,5.98000000),(9,8,2,16.10000000),(10,8,8,10.00000000),(11,9,3,15.00000000),(12,9,9,11.96000000);
/*!40000 ALTER TABLE `llx_paiement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementcharge`
--

DROP TABLE IF EXISTS `llx_paiementcharge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiementcharge` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_charge` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_typepaiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiementcharge`
--

LOCK TABLES `llx_paiementcharge` WRITE;
/*!40000 ALTER TABLE `llx_paiementcharge` DISABLE KEYS */;
INSERT INTO `llx_paiementcharge` (`rowid`, `fk_charge`, `datec`, `tms`, `datep`, `amount`, `fk_typepaiement`, `num_paiement`, `note`, `fk_bank`, `fk_user_creat`, `fk_user_modif`) VALUES (4,4,'2011-08-05 23:11:37','2011-08-05 21:11:37','2011-08-05 12:00:00',10,2,'','',12,1,NULL);
/*!40000 ALTER TABLE `llx_paiementcharge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiementfourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiementfourn_facturefourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_paiementfourn` int(11) DEFAULT NULL,
  `fk_facturefourn` int(11) DEFAULT NULL,
  `amount` double DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiementfourn_facturefourn` (`fk_paiementfourn`,`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_facture` (`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_paiement` (`fk_paiementfourn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiementfourn_facturefourn`
--

LOCK TABLES `llx_paiementfourn_facturefourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_bons`
--

DROP TABLE IF EXISTS `llx_prelevement_bons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_prelevement_lignes` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_prelevement_facture_fk_prelevement_lignes` (`fk_prelevement_lignes`),
  CONSTRAINT `fk_prelevement_facture_fk_prelevement_lignes` FOREIGN KEY (`fk_prelevement_lignes`) REFERENCES `llx_prelevement_lignes` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_lignes`
--

LOCK TABLES `llx_prelevement_lignes` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_lignes` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_lignes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_rejet`
--

DROP TABLE IF EXISTS `llx_prelevement_rejet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `virtual` tinyint(4) NOT NULL DEFAULT '0',
  `fk_parent` int(11) DEFAULT '0',
  `ref` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(32) DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `note` text,
  `customcode` varchar(32) DEFAULT NULL,
  `fk_country` int(11) DEFAULT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `price_ttc` double(24,8) DEFAULT '0.00000000',
  `price_min` double(24,8) DEFAULT '0.00000000',
  `price_min_ttc` double(24,8) DEFAULT '0.00000000',
  `price_base_type` varchar(3) DEFAULT 'HT',
  `tva_tx` double(6,3) DEFAULT NULL,
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `fk_user_author` int(11) DEFAULT NULL,
  `tosell` tinyint(4) DEFAULT '1',
  `tobuy` tinyint(4) DEFAULT '1',
  `fk_product_type` int(11) DEFAULT '0',
  `duration` varchar(6) DEFAULT NULL,
  `seuil_stock_alerte` int(11) DEFAULT '0',
  `barcode` varchar(255) DEFAULT NULL,
  `fk_barcode_type` int(11) DEFAULT '0',
  `accountancy_code_sell` varchar(15) DEFAULT NULL,
  `accountancy_code_buy` varchar(15) DEFAULT NULL,
  `partnumber` varchar(32) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `weight_units` tinyint(4) DEFAULT NULL,
  `length` float DEFAULT NULL,
  `length_units` tinyint(4) DEFAULT NULL,
  `surface` float DEFAULT NULL,
  `surface_units` tinyint(4) DEFAULT NULL,
  `volume` float DEFAULT NULL,
  `volume_units` tinyint(4) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `pmp` double(24,8) NOT NULL DEFAULT '0.00000000',
  `canvas` varchar(32) DEFAULT 'default@product',
  `finished` tinyint(4) DEFAULT NULL,
  `hidden` tinyint(4) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_ref` (`ref`,`entity`),
  KEY `idx_product_label` (`label`),
  KEY `idx_product_barcode` (`barcode`),
  KEY `idx_product_import_key` (`import_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product`
--

LOCK TABLES `llx_product` WRITE;
/*!40000 ALTER TABLE `llx_product` DISABLE KEYS */;
INSERT INTO `llx_product` (`rowid`, `datec`, `tms`, `virtual`, `fk_parent`, `ref`, `entity`, `ref_ext`, `label`, `description`, `note`, `customcode`, `fk_country`, `price`, `price_ttc`, `price_min`, `price_min_ttc`, `price_base_type`, `tva_tx`, `recuperableonly`, `localtax1_tx`, `localtax2_tx`, `fk_user_author`, `tosell`, `tobuy`, `fk_product_type`, `duration`, `seuil_stock_alerte`, `barcode`, `fk_barcode_type`, `accountancy_code_sell`, `accountancy_code_buy`, `partnumber`, `weight`, `weight_units`, `length`, `length_units`, `surface`, `surface_units`, `volume`, `volume_units`, `stock`, `pmp`, `canvas`, `finished`, `hidden`, `import_key`) VALUES (1,'2010-07-08 14:33:17','2010-07-21 23:19:50',0,0,'PIDRESS',1,NULL,'Pink dress','A beatifull pink dress','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',20,NULL,0,'','',NULL,100,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'default@product',1,0,NULL),(2,'2010-07-09 00:30:01','2010-07-21 23:19:50',0,0,'Product_P1',1,NULL,'Product P1','','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'default@product',1,0,NULL),(3,'2010-07-09 00:30:25','2010-07-21 23:19:50',0,0,'Service_S1',1,NULL,'Service S1','','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,1,'1m',NULL,NULL,0,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00000000,'service@product',0,0,NULL),(4,'2010-07-10 14:44:06','2011-07-31 22:34:27',0,0,'DECAP',1,NULL,'Decapsuleur','','',NULL,NULL,5.00000000,5.62500000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,2,-3,NULL,0,NULL,0,NULL,0,NULL,10.00000000,'default@product',1,0,NULL),(5,'2011-07-20 23:11:38','2011-07-27 17:02:59',0,0,'aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL),(6,'2011-07-29 22:16:44','2011-07-29 20:16:44',0,0,'Copy_of_aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL),(7,'2011-07-29 22:31:21','2011-07-29 20:31:21',0,0,'Copy_of_Copy_of_aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL),(8,'2011-07-29 22:46:54','2012-04-11 10:11:34',0,0,'A_cloned_product',1,NULL,'aaaa','This is a cloned product from product aaaa then modified.','This was a test','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL),(10,'2008-12-31 00:00:00','2011-07-29 21:23:10',0,0,'PR123456',1,NULL,'My product','This is a description example for record','Some note',NULL,NULL,100.00000000,110.00000000,0.00000000,0.00000000,'HT',10.000,0,0.000,0.000,NULL,0,0,0,'1y',0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00000000,'default@product',NULL,0,'20110729232310');
/*!40000 ALTER TABLE `llx_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_association`
--

DROP TABLE IF EXISTS `llx_product_association`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_association` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product_pere` int(11) NOT NULL DEFAULT '0',
  `fk_product_fils` int(11) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_association` (`fk_product_pere`,`fk_product_fils`),
  KEY `idx_product_association` (`fk_product_fils`),
  KEY `idx_product_association_fils` (`fk_product_fils`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_association`
--

LOCK TABLES `llx_product_association` WRITE;
/*!40000 ALTER TABLE `llx_product_association` DISABLE KEYS */;
INSERT INTO `llx_product_association` (`rowid`, `fk_product_pere`, `fk_product_fils`, `qty`) VALUES (1,4,1,2),(2,5,1,1);
/*!40000 ALTER TABLE `llx_product_association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_ca`
--

DROP TABLE IF EXISTS `llx_product_ca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_ca` (
  `fk_product` int(11) DEFAULT NULL,
  `date_calcul` datetime DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `ca_genere` float DEFAULT NULL,
  UNIQUE KEY `fk_product` (`fk_product`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_ca`
--

LOCK TABLES `llx_product_ca` WRITE;
/*!40000 ALTER TABLE `llx_product_ca` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_ca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_extrafields`
--

DROP TABLE IF EXISTS `llx_product_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_product_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_extrafields`
--

LOCK TABLES `llx_product_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_product_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur`
--

DROP TABLE IF EXISTS `llx_product_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `ref_fourn` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_fournisseur_ref` (`ref_fourn`,`fk_soc`,`entity`),
  KEY `idx_product_fourn_fk_product` (`fk_product`,`entity`),
  KEY `idx_product_fourn_fk_soc` (`fk_soc`,`entity`),
  CONSTRAINT `fk_product_fournisseur_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur`
--

LOCK TABLES `llx_product_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur` (`rowid`, `datec`, `tms`, `fk_product`, `fk_soc`, `ref_fourn`, `entity`, `fk_user_author`) VALUES (1,'2010-07-11 18:45:42','2010-07-11 16:45:42',4,1,'ABCD',1,1);
/*!40000 ALTER TABLE `llx_product_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_fournisseur_price` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `ref_fourn` varchar(30) DEFAULT NULL,
  `fk_product_fournisseur` int(11) NOT NULL,
  `fk_availability` int(11) DEFAULT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `unitprice` double(24,8) DEFAULT '0.00000000',
  `tva_tx` double(6,3) NOT NULL DEFAULT '0.000',
  `fk_user` int(11) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_fournisseur_price_ref` (`ref_fourn`,`fk_soc`,`quantity`,`entity`),
  KEY `idx_product_fournisseur_price_fk_user` (`fk_user`),
  KEY `idx_product_fourn_price_fk_product` (`fk_product`,`entity`),
  KEY `idx_product_fourn_price_fk_soc` (`fk_soc`,`entity`),
  CONSTRAINT `fk_product_fournisseur_price_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`),
  CONSTRAINT `fk_product_fournisseur_price_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur_price`
--

LOCK TABLES `llx_product_fournisseur_price` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur_price` (`rowid`, `datec`, `tms`, `fk_product`, `fk_soc`, `ref_fourn`, `fk_product_fournisseur`, `fk_availability`, `price`, `quantity`, `unitprice`, `tva_tx`, `fk_user`, `entity`) VALUES (1,'2010-07-11 18:45:42','2012-04-11 10:03:58',4,1,'ABCD',1,NULL,10.00000000,1,10.00000000,0.000,1,1);
/*!40000 ALTER TABLE `llx_product_fournisseur_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price_log`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_fournisseur_price_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `fk_product_fournisseur` int(11) NOT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur_price_log`
--

LOCK TABLES `llx_product_fournisseur_price_log` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price_log` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur_price_log` (`rowid`, `datec`, `fk_product_fournisseur`, `price`, `quantity`, `fk_user`) VALUES (1,'2010-07-11 18:45:42',1,10.00000000,1,1);
/*!40000 ALTER TABLE `llx_product_fournisseur_price_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_lang`
--

DROP TABLE IF EXISTS `llx_product_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_lang` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL,
  `description` text,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_lang` (`fk_product`,`lang`),
  CONSTRAINT `fk_product_lang_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_lang`
--

LOCK TABLES `llx_product_lang` WRITE;
/*!40000 ALTER TABLE `llx_product_lang` DISABLE KEYS */;
INSERT INTO `llx_product_lang` (`rowid`, `fk_product`, `lang`, `label`, `description`, `note`) VALUES (1,1,'en_US','Pink dress','A beatifull pink dress',''),(2,2,'en_US','Product P1','',''),(3,3,'en_US','Service S1','',''),(4,4,'fr_FR','Decapsuleur','',''),(5,5,'en_US','aaaa','cccc','bbbb'),(6,6,'en_US','aaaa','cccc','bbbb'),(7,7,'en_US','aaaa','cccc','bbbb'),(8,8,'en_US','aaaa','This is a cloned product from product aaaa then modified.','This was a test');
/*!40000 ALTER TABLE `llx_product_lang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_price`
--

DROP TABLE IF EXISTS `llx_product_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_price` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL,
  `date_price` datetime NOT NULL,
  `price_level` smallint(6) DEFAULT '1',
  `price` double(24,8) DEFAULT NULL,
  `price_ttc` double(24,8) DEFAULT NULL,
  `price_min` double(24,8) DEFAULT NULL,
  `price_min_ttc` double(24,8) DEFAULT NULL,
  `price_base_type` varchar(3) DEFAULT 'HT',
  `tva_tx` double(6,3) NOT NULL,
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `fk_user_author` int(11) DEFAULT NULL,
  `tosell` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_price`
--

LOCK TABLES `llx_product_price` WRITE;
/*!40000 ALTER TABLE `llx_product_price` DISABLE KEYS */;
INSERT INTO `llx_product_price` (`rowid`, `entity`, `tms`, `fk_product`, `date_price`, `price_level`, `price`, `price_ttc`, `price_min`, `price_min_ttc`, `price_base_type`, `tva_tx`, `recuperableonly`, `localtax1_tx`, `localtax2_tx`, `fk_user_author`, `tosell`) VALUES (1,1,'2010-07-08 12:33:17',1,'2010-07-08 14:33:17',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(2,1,'2010-07-08 22:30:01',2,'2010-07-09 00:30:01',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(3,1,'2010-07-08 22:30:25',3,'2010-07-09 00:30:25',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(4,1,'2010-07-10 12:44:06',4,'2010-07-10 14:44:06',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(5,1,'2011-07-20 21:11:38',5,'2011-07-20 23:11:38',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,1),(6,1,'2011-07-27 17:02:59',5,'2011-07-27 19:02:59',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,1),(7,1,'2011-07-29 20:16:44',6,'2011-07-29 22:16:44',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0),(8,1,'2011-07-29 20:31:21',7,'2011-07-29 22:31:21',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0),(9,1,'2011-07-29 20:46:54',8,'2011-07-29 22:46:54',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0),(10,1,'2011-07-31 22:34:27',4,'2011-08-01 00:34:27',1,5.00000000,5.62500000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1);
/*!40000 ALTER TABLE `llx_product_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_stock`
--

DROP TABLE IF EXISTS `llx_product_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_stock` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL,
  `fk_entrepot` int(11) NOT NULL,
  `reel` double DEFAULT NULL,
  `pmp` double(24,8) NOT NULL DEFAULT '0.00000000',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_stock` (`fk_product`,`fk_entrepot`),
  KEY `idx_product_stock_fk_product` (`fk_product`),
  KEY `idx_product_stock_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_stock`
--

LOCK TABLES `llx_product_stock` WRITE;
/*!40000 ALTER TABLE `llx_product_stock` DISABLE KEYS */;
INSERT INTO `llx_product_stock` (`rowid`, `tms`, `fk_product`, `fk_entrepot`, `reel`, `pmp`) VALUES (1,'2010-07-08 22:43:51',2,2,1000,0.00000000),(3,'2010-07-10 23:02:20',4,2,1000,0.00000000),(4,'2010-07-11 16:49:44',4,1,2,10.00000000),(5,'2010-07-11 16:49:44',1,1,4,0.00000000);
/*!40000 ALTER TABLE `llx_product_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_subproduct`
--

DROP TABLE IF EXISTS `llx_product_subproduct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_subproduct` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL,
  `fk_product_subproduct` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_product` (`fk_product`,`fk_product_subproduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `datec` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_projet_ref` (`ref`,`entity`),
  KEY `idx_projet_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_projet_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet`
--

LOCK TABLES `llx_projet` WRITE;
/*!40000 ALTER TABLE `llx_projet` DISABLE KEYS */;
INSERT INTO `llx_projet` (`rowid`, `fk_soc`, `datec`, `tms`, `dateo`, `datee`, `ref`, `entity`, `title`, `description`, `fk_user_creat`, `public`, `fk_statut`, `note_private`, `note_public`, `model_pdf`) VALUES (1,NULL,'2010-07-09','2010-07-11 13:28:28','2010-07-09',NULL,'PROJ1',1,'Project One','',1,0,1,NULL,NULL,'baleine'),(2,NULL,'2010-07-09','2010-07-08 22:49:56','2010-07-09',NULL,'PROJ2',1,'Project Two','',1,0,0,NULL,NULL,NULL),(3,1,'2010-07-09','2010-07-08 22:50:19','2010-07-09',NULL,'PROJABC',1,'Project to create ABC company','',1,0,0,NULL,NULL,NULL),(4,NULL,'2010-07-09','2010-07-08 22:50:49','2010-07-09',NULL,'PROJSHARED',1,'The Global project','',1,1,1,NULL,NULL,NULL),(5,NULL,'2010-07-11','2010-07-11 14:22:49','2010-07-11','2011-07-14','RMLL',1,'Projet gestion RMLL 2011','',1,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_projet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task`
--

DROP TABLE IF EXISTS `llx_projet_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_task` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_projet` int(11) NOT NULL,
  `fk_task_parent` int(11) NOT NULL DEFAULT '0',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateo` datetime DEFAULT NULL,
  `datee` datetime DEFAULT NULL,
  `datev` datetime DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `duration_effective` double NOT NULL DEFAULT '0',
  `progress` int(11) DEFAULT '0',
  `priority` int(11) DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet_task`
--

LOCK TABLES `llx_projet_task` WRITE;
/*!40000 ALTER TABLE `llx_projet_task` DISABLE KEYS */;
INSERT INTO `llx_projet_task` (`rowid`, `fk_projet`, `fk_task_parent`, `datec`, `tms`, `dateo`, `datee`, `datev`, `label`, `description`, `duration_effective`, `progress`, `priority`, `fk_user_creat`, `fk_user_valid`, `fk_statut`, `note_private`, `note_public`, `rang`) VALUES (1,1,0,'2010-07-11 15:15:55','2011-02-06 11:20:20','2010-07-11 12:00:00',NULL,NULL,'Work on module','',25920000,0,0,1,NULL,0,NULL,NULL,0),(2,5,0,'2010-07-11 16:23:53','2010-07-11 14:25:39','2010-07-11 12:00:00','2011-07-14 12:00:00',NULL,'Heberger site RMLL','',0,0,0,1,NULL,0,NULL,NULL,0);
/*!40000 ALTER TABLE `llx_projet_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task_time`
--

DROP TABLE IF EXISTS `llx_projet_task_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_task_time` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) NOT NULL,
  `task_date` date DEFAULT NULL,
  `task_duration` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet_task_time`
--

LOCK TABLES `llx_projet_task_time` WRITE;
/*!40000 ALTER TABLE `llx_projet_task_time` DISABLE KEYS */;
INSERT INTO `llx_projet_task_time` (`rowid`, `fk_task`, `task_date`, `task_duration`, `fk_user`, `note`) VALUES (1,1,'2010-07-11',25920000,1,'');
/*!40000 ALTER TABLE `llx_projet_task_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propal`
--

DROP TABLE IF EXISTS `llx_propal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_propal` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(255) DEFAULT NULL,
  `ref_int` varchar(255) DEFAULT NULL,
  `ref_client` varchar(255) DEFAULT NULL,
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
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `fk_account` int(11) DEFAULT NULL,
  `fk_currency` varchar(2) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_availability` int(11) DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  `fk_demand_reason` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_propal_ref` (`ref`,`entity`),
  KEY `idx_propal_fk_soc` (`fk_soc`),
  KEY `idx_propal_fk_user_author` (`fk_user_author`),
  KEY `idx_propal_fk_user_valid` (`fk_user_valid`),
  KEY `idx_propal_fk_user_cloture` (`fk_user_cloture`),
  KEY `idx_propal_fk_projet` (`fk_projet`),
  KEY `idx_propal_fk_account` (`fk_account`),
  KEY `idx_propal_fk_currency` (`fk_currency`),
  CONSTRAINT `fk_propal_fk_currency` FOREIGN KEY (`fk_currency`) REFERENCES `llx_c_currencies` (`code_iso`),
  CONSTRAINT `fk_propal_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_propal_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_propal_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_propal_fk_user_cloture` FOREIGN KEY (`fk_user_cloture`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_propal_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propal`
--

LOCK TABLES `llx_propal` WRITE;
/*!40000 ALTER TABLE `llx_propal` DISABLE KEYS */;
INSERT INTO `llx_propal` (`rowid`, `fk_soc`, `fk_projet`, `tms`, `ref`, `entity`, `ref_ext`, `ref_int`, `ref_client`, `datec`, `datep`, `fin_validite`, `date_valid`, `date_cloture`, `fk_user_author`, `fk_user_valid`, `fk_user_cloture`, `fk_statut`, `price`, `remise_percent`, `remise_absolue`, `remise`, `total_ht`, `tva`, `localtax1`, `localtax2`, `total`, `fk_account`, `fk_currency`, `fk_cond_reglement`, `fk_mode_reglement`, `note`, `note_public`, `model_pdf`, `date_livraison`, `fk_availability`, `fk_adresse_livraison`, `fk_demand_reason`, `import_key`, `extraparams`) VALUES (1,2,NULL,'2012-04-11 10:03:49','PR1007-0001',1,NULL,NULL,'','2010-07-09 01:33:49','2010-07-09','2010-07-24 12:00:00','2011-08-08 14:24:18',NULL,1,1,NULL,1,0,NULL,NULL,0,30.00000000,3.84000000,0.00000000,0.00000000,33.84000000,NULL,NULL,1,0,'','','azur',NULL,NULL,NULL,0,NULL,NULL),(2,1,NULL,'2012-04-11 10:03:49','PR1007-0002',1,NULL,NULL,'','2010-07-10 02:11:44','2010-07-10','2010-07-25 12:00:00','2010-07-10 02:12:55','2011-07-20 15:23:12',1,1,1,2,0,NULL,NULL,0,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,NULL,NULL,1,1,'','','azur',NULL,NULL,NULL,0,NULL,NULL),(3,4,NULL,'2012-04-11 10:03:49','PR1007-0003',1,NULL,NULL,'','2010-07-18 11:35:11','2010-07-18','2010-08-02 12:00:00','2010-07-18 11:36:18','2011-07-20 15:21:15',1,1,1,2,0,NULL,NULL,0,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,NULL,NULL,1,0,'','','azur',NULL,NULL,NULL,0,NULL,NULL),(4,17,NULL,'2012-04-11 10:03:49','PR1108-0004',1,NULL,NULL,'','2011-08-04 23:36:23','2011-08-05','2011-08-20 12:00:00','2011-08-08 14:24:24',NULL,1,1,NULL,1,0,NULL,NULL,0,30.00000000,5.88000000,0.00000000,0.00000000,35.88000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL);
/*!40000 ALTER TABLE `llx_propal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propaldet`
--

DROP TABLE IF EXISTS `llx_propaldet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_propaldet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_propal` int(11) DEFAULT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `fk_remise_except` int(11) DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `price` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
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
  KEY `idx_propaldet_fk_product` (`fk_product`),
  CONSTRAINT `fk_propaldet_fk_propal` FOREIGN KEY (`fk_propal`) REFERENCES `llx_propal` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propaldet`
--

LOCK TABLES `llx_propaldet` WRITE;
/*!40000 ALTER TABLE `llx_propaldet` DISABLE KEYS */;
INSERT INTO `llx_propaldet` (`rowid`, `fk_propal`, `fk_parent_line`, `fk_product`, `description`, `fk_remise_except`, `tva_tx`, `localtax1_tx`, `localtax2_tx`, `qty`, `remise_percent`, `remise`, `price`, `subprice`, `total_ht`, `total_tva`, `total_localtax1`, `total_localtax2`, `total_ttc`, `product_type`, `date_start`, `date_end`, `info_bits`, `pa_ht`, `marge_tx`, `marque_tx`, `special_code`, `rang`) VALUES (1,1,NULL,NULL,'Une machine à café',NULL,12.500,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,1.25000000,0.00000000,0.00000000,11.25000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1),(2,2,NULL,NULL,'Product 1',NULL,0.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1),(3,2,NULL,2,'',NULL,0.000,0.000,0.000,1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,2),(4,3,NULL,NULL,'A new marvelous product',NULL,0.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1),(5,1,NULL,5,'cccc',NULL,19.600,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,2),(11,1,NULL,4,'',NULL,0.000,0.000,0.000,1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,3),(12,1,NULL,4,'',NULL,0.000,0.000,0.000,1,0,0,NULL,5.00000000,5.00000000,0.00000000,0.00000000,0.00000000,5.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,4),(13,1,NULL,4,'',NULL,12.500,0.000,0.000,1,0,0,NULL,5.00000000,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,5),(19,4,NULL,NULL,'bvbcvbcvbcbcbcb',NULL,19.600,0.000,0.000,1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1),(20,4,NULL,NULL,'ghjhgjghjgh',NULL,19.600,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,2),(21,4,NULL,NULL,'ghjghjhgjg',NULL,19.600,0.000,0.000,2,0,0,10,10.00000000,20.00000000,3.92000000,0.00000000,0.00000000,23.92000000,1,NULL,NULL,0,0.00000000,0.000,0.000,0,3);
/*!40000 ALTER TABLE `llx_propaldet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_rights_def`
--

DROP TABLE IF EXISTS `llx_rights_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_rights_def`
--

LOCK TABLES `llx_rights_def` WRITE;
/*!40000 ALTER TABLE `llx_rights_def` DISABLE KEYS */;
INSERT INTO `llx_rights_def` (`id`, `libelle`, `module`, `entity`, `perms`, `subperms`, `type`, `bydefault`) VALUES (11,'Lire les factures','facture',1,'lire',NULL,'a',1),(12,'Creer/modifier les factures','facture',1,'creer',NULL,'a',0),(13,'Dévalider les factures','facture',1,'invoice_advance','unvalidate','a',0),(14,'Valider les factures','facture',1,'valider',NULL,'a',0),(15,'Envoyer les factures par mail','facture',1,'invoice_advance','send','a',0),(16,'Emettre des paiements sur les factures','facture',1,'paiement',NULL,'a',0),(19,'Supprimer les factures','facture',1,'supprimer',NULL,'a',0),(21,'Lire les propositions commerciales','propale',1,'lire',NULL,'r',1),(22,'Creer/modifier les propositions commerciales','propale',1,'creer',NULL,'w',0),(24,'Valider les propositions commerciales','propale',1,'valider',NULL,'d',0),(25,'Envoyer les propositions commerciales aux clients','propale',1,'propal_advance','send','d',0),(26,'Cloturer les propositions commerciales','propale',1,'cloturer',NULL,'d',0),(27,'Supprimer les propositions commerciales','propale',1,'supprimer',NULL,'d',0),(28,'Exporter les propositions commerciales et attributs','propale',1,'export',NULL,'r',0),(31,'Lire les produits','produit',1,'lire',NULL,'r',1),(32,'Creer/modifier les produits','produit',1,'creer',NULL,'w',0),(34,'Supprimer les produits','produit',1,'supprimer',NULL,'d',0),(38,'Exporter les produits','produit',1,'export',NULL,'r',0),(41,'Lire les projets et taches (partagés ou dont je suis contact)','projet',1,'lire',NULL,'r',1),(42,'Creer/modifier les projets et taches (partagés ou dont je suis contact)','projet',1,'creer',NULL,'w',0),(44,'Supprimer les projets et taches (partagés ou dont je suis contact)','projet',1,'supprimer',NULL,'d',0),(61,'Lire les fiches d\'intervention','ficheinter',1,'lire',NULL,'r',1),(62,'Creer/modifier les fiches d\'intervention','ficheinter',1,'creer',NULL,'w',0),(64,'Supprimer les fiches d\'intervention','ficheinter',1,'supprimer',NULL,'d',0),(67,'Exporter les fiches interventions','ficheinter',1,'export',NULL,'r',0),(68,'Envoyer les fiches d\'intervention par courriel','ficheinter',1,'ficheinter_advance','send','r',0),(71,'Read members\' card','adherent',1,'lire',NULL,'r',1),(72,'Create/modify members (need also user module permissions if member linked to a user)','adherent',1,'creer',NULL,'w',1),(74,'Remove members','adherent',1,'supprimer',NULL,'d',1),(75,'Setup types and attributes of members','adherent',1,'configurer',NULL,'w',1),(76,'Export members','adherent',1,'export',NULL,'r',0),(78,'Read subscriptions','adherent',1,'cotisation','lire','r',1),(79,'Create/modify/remove subscriptions','adherent',1,'cotisation','creer','w',1),(81,'Lire les commandes clients','commande',1,'lire',NULL,'r',1),(82,'Creer/modifier les commandes clients','commande',1,'creer',NULL,'w',0),(84,'Valider les commandes clients','commande',1,'valider',NULL,'d',0),(86,'Envoyer les commandes clients','commande',1,'order_advance','send','d',0),(87,'Cloturer les commandes clients','commande',1,'cloturer',NULL,'d',0),(88,'Annuler les commandes clients','commande',1,'annuler',NULL,'d',0),(89,'Supprimer les commandes clients','commande',1,'supprimer',NULL,'d',0),(91,'Lire les charges','tax',1,'charges','lire','r',1),(92,'Creer/modifier les charges','tax',1,'charges','creer','w',0),(93,'Supprimer les charges','tax',1,'charges','supprimer','d',0),(94,'Exporter les charges','tax',1,'charges','export','r',0),(95,'Lire CA, bilans, resultats','compta',1,'resultat','lire','r',1),(96,'Parametrer la ventilation','compta',1,'ventilation','parametrer','r',0),(97,'Lire les ventilations de factures','compta',1,'ventilation','lire','r',1),(98,'Ventiler les lignes de factures','compta',1,'ventilation','creer','r',0),(101,'Lire les expeditions','expedition',1,'lire',NULL,'r',1),(102,'Creer modifier les expeditions','expedition',1,'creer',NULL,'w',0),(104,'Valider les expeditions','expedition',1,'valider',NULL,'d',0),(105,'Envoyer les expeditions aux clients','expedition',1,'shipping_advance','send','d',0),(109,'Supprimer les expeditions','expedition',1,'supprimer',NULL,'d',0),(111,'Lire les comptes bancaires','banque',1,'lire',NULL,'r',1),(112,'Creer/modifier montant/supprimer ecriture bancaire','banque',1,'modifier',NULL,'w',0),(113,'Configurer les comptes bancaires (creer, gerer categories)','banque',1,'configurer',NULL,'a',0),(114,'Rapprocher les ecritures bancaires','banque',1,'consolidate',NULL,'w',0),(115,'Exporter transactions et releves','banque',1,'export',NULL,'r',0),(116,'Virements entre comptes','banque',1,'transfer',NULL,'w',0),(117,'Gerer les envois de cheques','banque',1,'cheque',NULL,'w',0),(121,'Lire les societes','societe',1,'lire',NULL,'r',1),(122,'Creer modifier les societes','societe',1,'creer',NULL,'w',0),(125,'Supprimer les societes','societe',1,'supprimer',NULL,'d',0),(126,'Exporter les societes','societe',1,'export',NULL,'r',0),(141,'Lire tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','lire','r',0),(142,'Creer/modifier tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','creer','w',0),(144,'Supprimer tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','supprimer','d',0),(151,'Read withdrawals','prelevement',1,'bons','lire','r',1),(152,'Create/modify a withdrawals','prelevement',1,'bons','creer','w',0),(153,'Send withdrawals to bank','prelevement',1,'bons','send','a',0),(154,'credit/refuse withdrawals','prelevement',1,'bons','credit','a',0),(161,'Lire les contrats','contrat',1,'lire',NULL,'r',1),(162,'Creer / modifier les contrats','contrat',1,'creer',NULL,'w',0),(163,'Activer un service d\'un contrat','contrat',1,'activer',NULL,'w',0),(164,'Desactiver un service d\'un contrat','contrat',1,'desactiver',NULL,'w',0),(165,'Supprimer un contrat','contrat',1,'supprimer',NULL,'d',0),(171,'Lire les deplacements','deplacement',1,'lire',NULL,'r',1),(172,'Creer/modifier les deplacements','deplacement',1,'creer',NULL,'w',0),(173,'Supprimer les deplacements','deplacement',1,'supprimer',NULL,'d',0),(178,'Exporter les deplacements','deplacement',1,'export',NULL,'d',0),(221,'Consulter les mailings','mailing',1,'lire',NULL,'r',1),(222,'Creer/modifier les mailings (sujet, destinataires...)','mailing',1,'creer',NULL,'w',1),(223,'Valider les mailings (permet leur envoi)','mailing',1,'valider',NULL,'w',0),(229,'Supprimer les mailings)','mailing',1,'supprimer',NULL,'d',1),(241,'Lire les categories','categorie',1,'lire',NULL,'r',1),(242,'Creer/modifier les categories','categorie',1,'creer',NULL,'w',1),(243,'Supprimer les categories','categorie',1,'supprimer',NULL,'d',1),(251,'Consulter les autres utilisateurs','user',1,'user','lire','r',0),(252,'Consulter les permissions des autres utilisateurs','user',1,'user_advance','readperms','r',0),(253,'Creer/modifier utilisateurs internes et externes','user',1,'user','creer','w',0),(254,'Creer/modifier utilisateurs externes seulement','user',1,'user_advance','write','w',0),(255,'Modifier le mot de passe des autres utilisateurs','user',1,'user','password','w',0),(256,'Supprimer ou desactiver les autres utilisateurs','user',1,'user','supprimer','d',0),(262,'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).','societe',1,'client','voir','r',1),(281,'Lire les contacts','societe',1,'contact','lire','r',1),(282,'Creer modifier les contacts','societe',1,'contact','creer','w',0),(283,'Supprimer les contacts','societe',1,'contact','supprimer','d',0),(286,'Exporter les contacts','societe',1,'contact','export','d',0),(331,'Lire les bookmarks','bookmark',1,'lire',NULL,'r',1),(332,'Creer/modifier les bookmarks','bookmark',1,'creer',NULL,'r',0),(333,'Supprimer les bookmarks','bookmark',1,'supprimer',NULL,'r',0),(341,'Consulter ses propres permissions','user',1,'self_advance','readperms','r',1),(342,'Creer/modifier ses propres infos utilisateur','user',1,'self','creer','w',1),(343,'Modifier son propre mot de passe','user',1,'self','password','w',1),(344,'Modifier ses propres permissions','user',1,'self_advance','writeperms','w',1),(351,'Consulter les groupes','user',1,'group_advance','read','r',0),(352,'Consulter les permissions des groupes','user',1,'group_advance','readperms','r',0),(353,'Creer/modifier les groupes et leurs permissions','user',1,'group_advance','write','w',0),(354,'Supprimer ou desactiver les groupes','user',1,'group_advance','delete','d',0),(358,'Exporter les utilisateurs','user',1,'user','export','r',0),(531,'Lire les services','service',1,'lire',NULL,'r',1),(532,'Creer/modifier les services','service',1,'creer',NULL,'w',0),(534,'Supprimer les services','service',1,'supprimer',NULL,'d',0),(538,'Exporter les services','service',1,'export',NULL,'r',0),(700,'Lire les dons','don',1,'lire',NULL,'r',1),(701,'Creer/modifier les dons','don',1,'creer',NULL,'w',0),(702,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(703,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(1001,'Lire les stocks','stock',1,'lire',NULL,'r',1),(1002,'Creer/Modifier les stocks','stock',1,'creer',NULL,'w',1),(1003,'Supprimer les stocks','stock',1,'supprimer',NULL,'d',1),(1004,'Lire mouvements de stocks','stock',1,'mouvement','lire','r',1),(1005,'Creer/modifier mouvements de stocks','stock',1,'mouvement','creer','w',1),(1101,'Lire les bons de livraison','expedition',1,'livraison','lire','r',1),(1102,'Creer modifier les bons de livraison','expedition',1,'livraison','creer','w',0),(1104,'Valider les bons de livraison','expedition',1,'livraison','valider','d',0),(1109,'Supprimer les bons de livraison','expedition',1,'livraison','supprimer','d',0),(1181,'Consulter les fournisseurs','fournisseur',1,'lire',NULL,'r',1),(1182,'Consulter les commandes fournisseur','fournisseur',1,'commande','lire','r',1),(1183,'Creer une commande fournisseur','fournisseur',1,'commande','creer','w',0),(1184,'Valider une commande fournisseur','fournisseur',1,'commande','valider','w',0),(1185,'Approuver une commande fournisseur','fournisseur',1,'commande','approuver','w',0),(1186,'Commander une commande fournisseur','fournisseur',1,'commande','commander','w',0),(1187,'Receptionner une commande fournisseur','fournisseur',1,'commande','receptionner','d',0),(1188,'Supprimer une commande fournisseur','fournisseur',1,'commande','supprimer','d',0),(1201,'Lire les exports','export',1,'lire',NULL,'r',1),(1202,'Creer/modifier un export','export',1,'creer',NULL,'w',1),(1231,'Consulter les factures fournisseur','fournisseur',1,'facture','lire','r',1),(1232,'Creer une facture fournisseur','fournisseur',1,'facture','creer','w',0),(1233,'Valider une facture fournisseur','fournisseur',1,'facture','valider','w',0),(1234,'Supprimer une facture fournisseur','fournisseur',1,'facture','supprimer','d',0),(1235,'Envoyer les factures par mail','fournisseur',1,'supplier_invoice_advance','send','a',0),(1236,'Exporter les factures fournisseurs, attributs et reglements','fournisseur',1,'facture','export','r',0),(1251,'Run mass imports of external data (data load)','import',1,'run',NULL,'r',0),(1321,'Exporter les factures clients, attributs et reglements','facture',1,'facture','export','r',0),(1421,'Exporter les commandes clients et attributs','commande',1,'commande','export','r',0),(2401,'Read actions/tasks linked to his account','agenda',1,'myactions','read','r',1),(2402,'Create/modify actions/tasks linked to his account','agenda',1,'myactions','create','w',0),(2403,'Delete actions/tasks linked to his account','agenda',1,'myactions','delete','w',0),(2411,'Read actions/tasks of others','agenda',1,'allactions','read','r',0),(2412,'Create/modify actions/tasks of others','agenda',1,'allactions','create','w',0),(2413,'Delete actions/tasks of others','agenda',1,'allactions','delete','w',0),(2501,'Consulter/Télécharger les documents','ecm',1,'read',NULL,'r',1),(2503,'Soumettre ou supprimer des documents','ecm',1,'upload',NULL,'w',1),(2515,'Administrer les rubriques de documents','ecm',1,'setup',NULL,'w',1);
/*!40000 ALTER TABLE `llx_rights_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe`
--

DROP TABLE IF EXISTS `llx_societe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `statut` tinyint(4) DEFAULT '0',
  `parent` int(11) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `nom` varchar(60) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(60) DEFAULT NULL,
  `ref_int` varchar(60) DEFAULT NULL,
  `code_client` varchar(24) DEFAULT NULL,
  `code_fournisseur` varchar(24) DEFAULT NULL,
  `code_compta` varchar(24) DEFAULT NULL,
  `code_compta_fournisseur` varchar(24) DEFAULT NULL,
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
  `fk_currency` int(11) DEFAULT '0',
  `siren` varchar(128) DEFAULT NULL,
  `siret` varchar(128) DEFAULT NULL,
  `ape` varchar(128) DEFAULT NULL,
  `idprof4` varchar(128) DEFAULT NULL,
  `tva_intra` varchar(20) DEFAULT NULL,
  `capital` double DEFAULT NULL,
  `description` text,
  `fk_stcomm` int(11) NOT NULL,
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
  `barcode` varchar(255) DEFAULT NULL,
  `price_level` int(11) DEFAULT NULL,
  `default_lang` varchar(6) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  `logo` varchar(255) DEFAULT NULL,
  `idprof5` varchar(128) DEFAULT NULL,
  `fk_barcode_type` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_prefix_comm` (`prefix_comm`,`entity`),
  UNIQUE KEY `uk_societe_code_client` (`code_client`,`entity`),
  KEY `idx_societe_user_creat` (`fk_user_creat`),
  KEY `idx_societe_user_modif` (`fk_user_modif`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe`
--

LOCK TABLES `llx_societe` WRITE;
/*!40000 ALTER TABLE `llx_societe` DISABLE KEYS */;
INSERT INTO `llx_societe` (`rowid`, `statut`, `parent`, `tms`, `datec`, `datea`, `nom`, `entity`, `ref_ext`, `ref_int`, `code_client`, `code_fournisseur`, `code_compta`, `code_compta_fournisseur`, `address`, `cp`, `ville`, `fk_departement`, `fk_pays`, `tel`, `fax`, `url`, `email`, `fk_secteur`, `fk_effectif`, `fk_typent`, `fk_forme_juridique`, `fk_currency`, `siren`, `siret`, `ape`, `idprof4`, `tva_intra`, `capital`, `description`, `fk_stcomm`, `note`, `services`, `prefix_comm`, `client`, `fournisseur`, `supplier_account`, `fk_prospectlevel`, `customer_bad`, `customer_rate`, `supplier_rate`, `fk_user_creat`, `fk_user_modif`, `remise_client`, `mode_reglement`, `cond_reglement`, `tva_assuj`, `localtax1_assuj`, `localtax2_assuj`, `barcode`, `price_level`, `default_lang`, `canvas`, `import_key`, `status`, `logo`, `idprof5`, `fk_barcode_type`) VALUES (1,0,NULL,'2011-07-20 13:23:12','2010-07-08 14:21:44','2010-07-10 18:35:31','ABC and Co',1,NULL,NULL,NULL,NULL,'7050','6050','1 alalah road',NULL,'Delhi',297,117,NULL,NULL,NULL,NULL,0,NULL,4,NULL,0,'','','','','',5000,NULL,1,NULL,0,NULL,1,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'en_IN',NULL,NULL,1,NULL,NULL,0),(2,0,NULL,'2011-07-31 22:35:08','2010-07-08 14:23:48','2011-08-01 00:35:08','Belin SARL',1,NULL,NULL,'CU1108-0001','SU1108-0001',NULL,NULL,'11 rue de la paix.','75000','Paris',0,117,NULL,NULL,'dolibarr.fr',NULL,0,NULL,3,NULL,0,'123456789','','ACE14','','',10000,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'fr_FR',NULL,NULL,1,NULL,NULL,0),(3,0,NULL,'2010-07-08 20:42:12','2010-07-08 22:42:12','2010-07-08 22:42:12','Spanish Comp',1,NULL,NULL,'SPANISHCOMP',NULL,NULL,NULL,'1 via mallere',NULL,'Madrid',123,4,NULL,NULL,NULL,NULL,0,3,4,408,0,'','','','','',10000,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'es_AR',NULL,NULL,1,NULL,NULL,0),(4,0,NULL,'2011-07-20 13:21:15','2010-07-08 22:48:18','2010-07-08 22:48:18','Prospector Vaalen',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,'Bruxelles',103,2,NULL,NULL,NULL,NULL,0,3,4,201,0,'12345678','','','','',0,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(5,0,NULL,'2010-07-08 21:37:56','2010-07-08 23:22:57','2010-07-08 23:37:56','NoCountry Co',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,193,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(6,0,NULL,'2010-07-08 22:25:06','2010-07-09 00:15:09','2010-07-09 00:25:06','Swiss customer supplier',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,'Genevia',0,6,NULL,NULL,NULL,'abademail@aa.com',0,2,2,601,0,'','','','','',56000,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(7,0,NULL,'2010-07-08 23:24:53','2010-07-09 01:24:26','2010-07-09 01:24:26','Generic customer',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,7,NULL,NULL,NULL,NULL,0,NULL,8,NULL,0,'','','','','',0,NULL,0,'Generic customer to use for Point Of Sale module.<br />',0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(8,0,NULL,'2010-07-10 12:54:27','2010-07-10 14:54:27','2010-07-10 14:54:27','Client salon',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(9,0,NULL,'2010-07-10 12:55:11','2010-07-10 14:54:44','2010-07-10 14:55:11','Client salon invidivdu',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,8,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(10,0,NULL,'2010-07-10 13:13:08','2010-07-10 15:13:08','2010-07-10 15:13:08','Smith Vick',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,102,NULL,NULL,NULL,'vsmith@email.com',0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(11,0,NULL,'2010-07-11 12:35:22','2010-07-10 18:35:57','2010-07-10 18:36:24','Mon client',1,NULL,NULL,NULL,NULL,'7051',NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,3,0,NULL,'PL_LOW',0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(12,0,NULL,'2010-07-11 14:18:08','2010-07-11 16:18:08','2010-07-11 16:18:08','Dupont Alain',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,'toto@aa.com',0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(13,0,NULL,'2010-07-11 15:13:20','2010-07-11 17:13:20','2010-07-11 17:13:20','Vendeur de chips',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,0,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(15,0,NULL,'2012-04-11 10:10:21','2011-08-01 02:31:24','2012-04-11 12:10:21','From Island and Co',1,NULL,NULL,'CU1108-0002','SU1108-0002',NULL,NULL,'','78180','mmm',0,31,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(16,0,NULL,'2012-04-11 10:10:04','2011-08-01 02:31:43','2012-04-11 12:10:04','ProCust company',1,NULL,NULL,'CU1108-0003','SU1108-0003',NULL,NULL,'','78180','mmm',103,2,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0),(17,0,NULL,'2011-08-04 21:24:24','2011-08-01 02:41:26','2011-08-04 23:24:24','FFF SARL',1,NULL,NULL,'CU1108-0004','SU1108-0004',NULL,NULL,'The French Company',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,1,3,NULL,0,'','','','','',0,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,0);
/*!40000 ALTER TABLE `llx_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_address`
--

DROP TABLE IF EXISTS `llx_societe_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_address` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT '0',
  `name` varchar(60) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_address`
--

LOCK TABLES `llx_societe_address` WRITE;
/*!40000 ALTER TABLE `llx_societe_address` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_commerciaux`
--

DROP TABLE IF EXISTS `llx_societe_commerciaux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_commerciaux` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_commerciaux` (`fk_soc`,`fk_user`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_commerciaux`
--

LOCK TABLES `llx_societe_commerciaux` WRITE;
/*!40000 ALTER TABLE `llx_societe_commerciaux` DISABLE KEYS */;
INSERT INTO `llx_societe_commerciaux` (`rowid`, `fk_soc`, `fk_user`) VALUES (1,2,2),(2,3,2),(3,15,1),(4,16,1),(5,17,1);
/*!40000 ALTER TABLE `llx_societe_commerciaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_extrafields`
--

DROP TABLE IF EXISTS `llx_societe_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `jjjj` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_societe_extrafields` (`fk_object`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_extrafields`
--

LOCK TABLES `llx_societe_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_societe_extrafields` DISABLE KEYS */;
INSERT INTO `llx_societe_extrafields` (`rowid`, `tms`, `fk_object`, `jjjj`, `import_key`) VALUES (1,'2011-06-22 16:23:01',40,'kkkk',NULL),(2,'2011-06-22 16:23:16',41,'jjj',NULL),(4,'2011-06-23 07:40:40',39,'lll',NULL),(12,'2011-06-29 13:03:12',42,NULL,NULL),(14,'2011-07-02 01:24:03',57,NULL,NULL),(16,'2011-07-02 14:11:29',60,NULL,NULL),(17,'2011-07-18 10:26:55',35,NULL,NULL),(18,'2011-07-31 22:35:08',2,NULL,NULL),(27,'2011-08-04 21:24:24',17,NULL,NULL),(28,'2012-04-11 10:10:04',16,NULL,NULL),(29,'2012-04-11 10:10:21',15,NULL,NULL);
/*!40000 ALTER TABLE `llx_societe_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_log`
--

DROP TABLE IF EXISTS `llx_societe_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datel` datetime DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_statut` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `author` varchar(30) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_prices` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT '0',
  `tms` timestamp NULL DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `price_level` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_remise` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `remise_client` double(6,3) NOT NULL DEFAULT '0.000',
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_rib` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_socpeople` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_soc` int(11) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `civilite` varchar(6) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(25) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
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
  `default_lang` varchar(6) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_socpeople_fk_soc` (`fk_soc`),
  KEY `idx_socpeople_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_socpeople_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_socpeople_user_creat_user_rowid` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_socpeople`
--

LOCK TABLES `llx_socpeople` WRITE;
/*!40000 ALTER TABLE `llx_socpeople` DISABLE KEYS */;
INSERT INTO `llx_socpeople` (`rowid`, `datec`, `tms`, `fk_soc`, `entity`, `civilite`, `name`, `firstname`, `address`, `cp`, `ville`, `fk_departement`, `fk_pays`, `birthday`, `poste`, `phone`, `phone_perso`, `phone_mobile`, `fax`, `email`, `jabberid`, `priv`, `fk_user_creat`, `fk_user_modif`, `note`, `default_lang`, `canvas`, `import_key`) VALUES (1,'2010-07-08 14:26:14','2010-07-08 20:45:28',1,1,'MR','Samira','Aljoun','','','',297,117,'2010-07-08','Project leader','','','','','','',0,1,1,'Met during a congress at Dubai',NULL,NULL,NULL),(2,'2010-07-08 22:44:50','2010-07-08 20:59:57',NULL,1,'MR','Freeman','Public','','','',200,11,NULL,'','','','','','','',0,1,1,'A friend that is a free contact not linked to any company',NULL,NULL,NULL),(3,'2010-07-08 22:59:02','2010-07-08 20:59:35',NULL,1,'MR','Freeman','Private','','','',NULL,11,NULL,'','','','','','','',1,1,1,'This is a private contact',NULL,NULL,NULL),(4,'2010-07-09 00:16:58','2010-07-08 22:16:58',6,1,'MR','Rotchield','Evan','','','',NULL,6,NULL,'Bank director','','','','','','',0,1,1,'The bank director',NULL,NULL,NULL),(5,'2010-07-10 14:54:44','2010-07-10 12:54:44',9,1,'','Client salon invidivdu','','','','',NULL,NULL,NULL,'','','','','','','',0,1,1,'',NULL,NULL,NULL),(6,'2011-08-01 02:41:26','2011-08-01 00:41:26',17,1,'','aaa','','aaa','','',289,117,NULL,'','','','','','','',0,1,1,'',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_socpeople` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_stock_mouvement`
--

DROP TABLE IF EXISTS `llx_stock_mouvement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_stock_mouvement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_stock_mouvement`
--

LOCK TABLES `llx_stock_mouvement` WRITE;
/*!40000 ALTER TABLE `llx_stock_mouvement` DISABLE KEYS */;
INSERT INTO `llx_stock_mouvement` (`rowid`, `tms`, `datem`, `fk_product`, `fk_entrepot`, `value`, `price`, `type_mouvement`, `fk_user_author`, `label`) VALUES (1,'2010-07-08 22:43:51','2010-07-09 00:43:51',2,2,1000,0.0000,0,1,'Correct stock'),(3,'2010-07-10 22:56:18','2010-07-11 00:56:18',4,2,500,0.0000,0,1,'Init'),(4,'2010-07-10 23:02:20','2010-07-11 01:02:20',4,2,500,0.0000,0,1,''),(5,'2010-07-11 16:49:44','2010-07-11 18:49:44',4,1,2,10.0000,3,1,''),(6,'2010-07-11 16:49:44','2010-07-11 18:49:44',1,1,4,0.0000,3,1,'');
/*!40000 ALTER TABLE `llx_stock_mouvement` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `llx_texts`
--

DROP TABLE IF EXISTS `llx_texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_texts`
--

LOCK TABLES `llx_texts` WRITE;
/*!40000 ALTER TABLE `llx_texts` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_texts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_tva`
--

DROP TABLE IF EXISTS `llx_tva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_tva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_tva`
--

LOCK TABLES `llx_tva` WRITE;
/*!40000 ALTER TABLE `llx_tva` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user`
--

DROP TABLE IF EXISTS `llx_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login` varchar(24) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `civilite` varchar(6) DEFAULT NULL,
  `ref_ext` varchar(50) DEFAULT NULL,
  `ref_int` varchar(50) DEFAULT NULL,
  `pass` varchar(32) DEFAULT NULL,
  `pass_crypted` varchar(128) DEFAULT NULL,
  `pass_temp` varchar(32) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `office_fax` varchar(20) DEFAULT NULL,
  `user_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `signature` text,
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
  `openid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_login` (`login`,`entity`),
  UNIQUE KEY `uk_user_fk_socpeople` (`fk_socpeople`),
  UNIQUE KEY `uk_user_fk_member` (`fk_member`),
  KEY `uk_user_fk_societe` (`fk_societe`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user`
--

LOCK TABLES `llx_user` WRITE;
/*!40000 ALTER TABLE `llx_user` DISABLE KEYS */;
INSERT INTO `llx_user` (`rowid`, `datec`, `tms`, `login`, `entity`, `civilite`, `ref_ext`, `ref_int`, `pass`, `pass_crypted`, `pass_temp`, `name`, `firstname`, `office_phone`, `office_fax`, `user_mobile`, `email`, `signature`, `admin`, `webcal_login`, `phenix_login`, `phenix_pass`, `module_comm`, `module_compta`, `fk_societe`, `fk_socpeople`, `fk_member`, `note`, `datelastlogin`, `datepreviouslogin`, `egroupware_id`, `ldap_sid`, `statut`, `photo`, `lang`, `openid`) VALUES (1,'2010-07-08 13:20:11','2010-07-18 09:37:04','admin',0,NULL,NULL,NULL,'admin','21232f297a57a5a743894a0e4a801fc3',NULL,'SuperAdminName','Firstname','','','','',NULL,1,'','','',1,1,NULL,NULL,NULL,'','2012-04-11 12:05:03','2011-08-08 19:32:42',NULL,'',1,NULL,NULL,NULL),(2,'2010-07-08 13:54:48','2010-07-08 11:54:48','demo',1,NULL,NULL,NULL,'demo','fe01ce2a7fbac8fafaed7c982a04e229',NULL,'John','Doe','09123123','','','johndoe@mycompany.com',NULL,0,'','','',1,1,NULL,NULL,NULL,'','2010-07-08 14:12:02',NULL,NULL,'',1,NULL,NULL,NULL),(3,'2010-07-11 16:18:59','2010-07-11 14:18:59','adupont',1,NULL,NULL,NULL,'sng2bdf6','c4cb8e25c3d261afac062a8d1274212c',NULL,'Dupont','Alain','','','','toto@aa.com',NULL,0,'','','',1,1,NULL,NULL,2,'',NULL,NULL,NULL,'',1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_alert`
--

DROP TABLE IF EXISTS `llx_user_alert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_alert` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_alert`
--

LOCK TABLES `llx_user_alert` WRITE;
/*!40000 ALTER TABLE `llx_user_alert` DISABLE KEYS */;
INSERT INTO `llx_user_alert` (`rowid`, `type`, `fk_contact`, `fk_user`) VALUES (1,1,1,1);
/*!40000 ALTER TABLE `llx_user_alert` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_clicktodial`
--

DROP TABLE IF EXISTS `llx_user_clicktodial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_clicktodial` (
  `fk_user` int(11) NOT NULL,
  `login` varchar(32) DEFAULT NULL,
  `pass` varchar(64) DEFAULT NULL,
  `poste` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_clicktodial`
--

LOCK TABLES `llx_user_clicktodial` WRITE;
/*!40000 ALTER TABLE `llx_user_clicktodial` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_clicktodial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_param`
--

DROP TABLE IF EXISTS `llx_user_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_param` (
  `fk_user` int(11) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `param` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `uk_user_param` (`fk_user`,`param`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_param`
--

LOCK TABLES `llx_user_param` WRITE;
/*!40000 ALTER TABLE `llx_user_param` DISABLE KEYS */;
INSERT INTO `llx_user_param` (`fk_user`, `entity`, `param`, `value`) VALUES (1,1,'MAIN_BOXES_0','1'),(1,1,'MAIN_LANG_DEFAULT','en_US');
/*!40000 ALTER TABLE `llx_user_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_rights`
--

DROP TABLE IF EXISTS `llx_user_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_rights` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_rights` (`fk_user`,`fk_id`),
  CONSTRAINT `fk_user_rights_fk_user_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5186 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_rights`
--

LOCK TABLES `llx_user_rights` WRITE;
/*!40000 ALTER TABLE `llx_user_rights` DISABLE KEYS */;
INSERT INTO `llx_user_rights` (`rowid`, `fk_user`, `fk_id`) VALUES (4787,1,11),(4780,1,12),(4781,1,13),(4783,1,14),(4784,1,15),(4786,1,16),(4788,1,19),(4032,1,21),(4024,1,22),(4026,1,24),(4027,1,25),(4029,1,26),(4031,1,27),(4033,1,28),(2300,1,31),(2297,1,32),(2299,1,34),(1910,1,36),(2301,1,38),(1778,1,41),(1777,1,42),(1779,1,44),(4113,1,61),(4110,1,62),(4112,1,64),(4114,1,67),(4115,1,68),(1678,1,71),(1673,1,72),(1675,1,74),(1679,1,75),(1677,1,76),(1681,1,78),(1682,1,79),(4775,1,81),(4767,1,82),(4769,1,84),(4770,1,86),(4772,1,87),(4774,1,88),(4776,1,89),(3874,1,91),(3871,1,92),(3873,1,93),(3875,1,94),(4158,1,95),(4159,1,96),(4161,1,97),(4162,1,98),(4055,1,101),(4051,1,102),(4053,1,104),(4054,1,105),(4056,1,109),(4201,1,111),(4192,1,112),(4194,1,113),(4196,1,114),(4198,1,115),(4200,1,116),(4202,1,117),(4748,1,121),(4745,1,122),(4747,1,125),(4749,1,126),(1783,1,141),(1782,1,142),(1784,1,144),(2307,1,151),(2304,1,152),(2306,1,153),(2308,1,154),(4099,1,161),(4094,1,162),(4096,1,163),(4098,1,164),(4100,1,165),(1585,1,170),(4841,1,171),(4838,1,172),(4840,1,173),(4842,1,178),(1693,1,221),(1690,1,222),(1692,1,223),(1694,1,229),(1686,1,241),(1685,1,242),(1687,1,243),(4834,1,251),(4815,1,252),(4817,1,253),(4818,1,254),(4820,1,255),(4822,1,256),(1617,1,258),(4750,1,262),(4756,1,281),(4753,1,282),(4755,1,283),(4757,1,286),(1763,1,331),(1762,1,332),(1764,1,333),(4823,1,341),(4824,1,342),(4825,1,343),(4826,1,344),(4832,1,351),(4829,1,352),(4831,1,353),(4833,1,354),(4835,1,358),(4763,1,531),(4760,1,532),(4762,1,534),(1625,1,536),(4764,1,538),(4847,1,700),(4844,1,701),(4846,1,702),(4848,1,703),(1755,1,1001),(1754,1,1002),(1756,1,1003),(1758,1,1004),(1759,1,1005),(4062,1,1101),(4059,1,1102),(4061,1,1104),(4063,1,1109),(4790,1,1181),(4802,1,1182),(4793,1,1183),(4795,1,1184),(4797,1,1185),(4799,1,1186),(4801,1,1187),(4803,1,1188),(1578,1,1201),(1579,1,1202),(4812,1,1231),(4806,1,1232),(4808,1,1233),(4810,1,1234),(4811,1,1235),(4813,1,1236),(1736,1,1251),(4789,1,1321),(4777,1,1421),(4736,1,2401),(4735,1,2402),(4737,1,2403),(4741,1,2411),(4740,1,2412),(4742,1,2413),(1618,1,2500),(4852,1,2501),(4851,1,2503),(4853,1,2515),(3564,1,100700),(3565,1,100701),(3582,1,102000),(3583,1,102001),(132,2,11),(133,2,12),(134,2,13),(135,2,14),(136,2,16),(137,2,19),(138,2,21),(139,2,22),(140,2,24),(141,2,25),(142,2,26),(143,2,27),(144,2,31),(145,2,32),(146,2,36),(147,2,41),(148,2,42),(149,2,44),(150,2,61),(151,2,62),(152,2,64),(153,2,71),(154,2,72),(155,2,74),(156,2,75),(157,2,78),(158,2,79),(159,2,81),(160,2,82),(161,2,84),(162,2,86),(163,2,87),(164,2,88),(165,2,89),(166,2,91),(167,2,92),(168,2,93),(2475,2,95),(2476,2,96),(2477,2,97),(2478,2,98),(169,2,101),(170,2,102),(171,2,104),(172,2,109),(173,2,111),(174,2,112),(175,2,113),(176,2,114),(177,2,116),(178,2,117),(179,2,121),(180,2,122),(181,2,125),(182,2,141),(183,2,142),(184,2,144),(2479,2,151),(2480,2,152),(2481,2,153),(2482,2,154),(185,2,161),(186,2,162),(187,2,163),(188,2,164),(189,2,165),(190,2,170),(2471,2,171),(192,2,172),(2472,2,173),(193,2,221),(194,2,222),(195,2,229),(196,2,241),(197,2,242),(198,2,243),(199,2,251),(200,2,255),(201,2,262),(202,2,281),(203,2,282),(204,2,283),(205,2,331),(2483,2,531),(207,2,532),(2484,2,534),(208,2,536),(2473,2,700),(210,2,701),(211,2,702),(2474,2,703),(212,2,1001),(213,2,1002),(214,2,1003),(215,2,1004),(216,2,1005),(217,2,1101),(218,2,1102),(219,2,1104),(220,2,1109),(221,2,1181),(222,2,1182),(223,2,1183),(224,2,1184),(225,2,1185),(226,2,1186),(227,2,1187),(228,2,1188),(229,2,1201),(230,2,1202),(231,2,1231),(232,2,1232),(233,2,1233),(234,2,1234),(235,2,1421),(236,2,2401),(237,2,2402),(238,2,2403),(239,2,2411),(240,2,2412),(241,2,2413),(242,2,2500),(2470,2,2501),(243,2,2515),(1807,3,11),(1808,3,31),(1809,3,36),(1810,3,41),(1811,3,61),(1812,3,71),(1813,3,72),(1814,3,74),(1815,3,75),(1816,3,78),(1817,3,79),(1818,3,91),(1819,3,95),(1820,3,97),(1821,3,111),(1822,3,121),(1823,3,122),(1824,3,125),(1825,3,161),(1826,3,170),(1827,3,171),(1828,3,172),(1829,3,221),(1830,3,222),(1831,3,229),(1832,3,241),(1833,3,242),(1834,3,243),(1835,3,251),(1836,3,255),(1837,3,256),(1838,3,262),(1839,3,281),(1840,3,282),(1841,3,283),(1842,3,331),(1843,3,531),(1844,3,536),(1845,3,700),(1846,3,1001),(1847,3,1002),(1848,3,1003),(1849,3,1004),(1850,3,1005),(1851,3,1181),(1852,3,1182),(1853,3,1201),(1854,3,1202),(1855,3,1231),(1856,3,2401),(1857,3,2402),(1858,3,2403),(1859,3,2411),(1860,3,2412),(1861,3,2413),(1862,3,2500),(1863,3,2515);
/*!40000 ALTER TABLE `llx_user_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup`
--

DROP TABLE IF EXISTS `llx_usergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_usergroup` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_usergroup_name` (`nom`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_usergroup_rights` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_usergroup` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_usergroup` (`fk_usergroup`,`fk_id`),
  CONSTRAINT `fk_usergroup_rights_fk_usergroup` FOREIGN KEY (`fk_usergroup`) REFERENCES `llx_usergroup` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_usergroup_user` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_user` int(11) NOT NULL,
  `fk_usergroup` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_usergroup_user` (`entity`,`fk_user`,`fk_usergroup`),
  KEY `fk_usergroup_user_fk_user` (`fk_user`),
  KEY `fk_usergroup_user_fk_usergroup` (`fk_usergroup`),
  CONSTRAINT `fk_usergroup_user_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_usergroup_user_fk_usergroup` FOREIGN KEY (`fk_usergroup`) REFERENCES `llx_usergroup` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-04-11 12:11:50
