<?PHP
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com> 
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net> 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
   \file       htdocs/master.inc.php
   \brief      Prepare Dolibarr environment
   \version    $Id$
*/

define('DOL_VERSION','2.4-dev');
define('EURO',chr(128)); 

// La fonction clearstatcache ne doit pas etre appelée de manière globale car ralenti.
// Elle doit etre appelée uniquement par les pages qui ont besoin d'un cache fichier vidé
// comme par exemple document.php
//clearstatcache();     

// Definition des constantes syslog
if (function_exists("define_syslog_variables"))
{
    define_syslog_variables();
}
else
{
	// Pour PHP sans syslog (comme sous Windows)
	define('LOG_EMERG',0);
	define('LOG_ALERT',1);
	define('LOG_CRIT',2);
	define('LOG_ERR',3);
	define('LOG_WARNING',4);
	define('LOG_NOTICE',5);
	define('LOG_INFO',6);
	define('LOG_DEBUG',7);
}

// Forcage du parametrage PHP error_reporting (Dolibarr non utilisable en mode error E_ALL)
//error_reporting(E_ALL);
error_reporting(E_ALL ^ E_NOTICE);


// Include configuration
$result=@include_once("conf/conf.php");
if (! $result && $_SERVER["GATEWAY_INTERFACE"])
{
	// If install not done and we are in a web session
	header("Location: install/index.php");
	exit;
}
if (empty($dolibarr_main_db_host))
{
   	print 'Error: Dolibarr setup was run but was not completed.<br>'."\n";
	print 'Please, run <a href="install/index.php">Dolibarr install process</a> until the end...'."\n";
   	exit;
}
if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';   // Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
if (empty($dolibarr_main_data_root))
{
	// Si repertoire documents non defini, on utilise celui par defaut
	$dolibarr_main_data_root=ereg_replace("/htdocs","",$dolibarr_main_document_root);
	$dolibarr_main_data_root.="/documents";
}
define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);	// Filesystem pages php (htdocs)
define('DOL_DATA_ROOT', $dolibarr_main_data_root);			// Filesystem donnes (documents)
define('DOL_MAIN_URL_ROOT', $dolibarr_main_url_root);		// URL racine absolue
$uri=eregi_replace('^http(s?)://','',$dolibarr_main_url_root);
$pos = strstr ($uri, '/');      // $pos contient alors url sans nom domaine
if ($pos == '/') $pos = '';     // si $pos vaut /, on le met a ''
define('DOL_URL_ROOT', $pos);								// URL racine relative

// Special code for alternate dev directories (Used on dev env only)
if (! empty($dolibarr_main_document_root_bis)) define('DOL_DOCUMENT_ROOT_BIS', $dolibarr_main_document_root_bis);


/*
 * Controle validite fichier conf
 */
if (! file_exists(DOL_DOCUMENT_ROOT ."/lib/functions.inc.php"))
{
    print "Error: Dolibarr config file content seems to be not correctly defined.<br>\n";
	print "Please run dolibarr setup by calling page <b>/install</b>.<br>\n";
	exit;
}


/*
 * Creation objet $conf
 */

// on décode le mot de passe de la base si besoin
require_once(DOL_DOCUMENT_ROOT ."/lib/functions.inc.php");	// Need 1.1Mo memory
if (! empty($dolibarr_main_db_encrypted_pass)) $dolibarr_main_db_pass = dolibarr_decode($dolibarr_main_db_encrypted_pass);
//stopwithmem();

require_once(DOL_DOCUMENT_ROOT."/conf/conf.class.php");

$conf = new Conf();
// Identifiant propres au serveur base de donnee
$conf->db->host   = $dolibarr_main_db_host;
if (empty($dolibarr_main_db_port)) $dolibarr_main_db_port=0;		// Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
$conf->db->port   = $dolibarr_main_db_port;
$conf->db->name   = $dolibarr_main_db_name;
$conf->db->user   = $dolibarr_main_db_user;
$conf->db->pass   = $dolibarr_main_db_pass;
if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
$conf->db->type   = $dolibarr_main_db_type;
if (empty($dolibarr_main_db_character_set)) $dolibarr_main_db_character_set='latin1'; 
$conf->db->character_set=$dolibarr_main_db_character_set;
if (empty($dolibarr_main_db_prefix)) $dolibarr_main_db_prefix='llx_'; 
$conf->db->prefix = $dolibarr_main_db_prefix;
if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation='latin1_swedish_ci';
$conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;
// Identifiant autres
$conf->main_authentication = $dolibarr_main_authentication;
// Identifiant propre au client
if (empty($character_set_client)) $character_set_client='ISO-8859-1';
$conf->character_set_client=$character_set_client;

// Defini prefix
if (isset($_SERVER["LLX_DBNAME"])) $dolibarr_main_db_prefix=$_SERVER["LLX_DBNAME"];
define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"]))
{
  if (eregi('firefox',$_SERVER["HTTP_USER_AGENT"])) $conf->browser->firefox=1;
  if (eregi('iceweasel',$_SERVER["HTTP_USER_AGENT"])) $conf->browser->firefox=1;
}

// Chargement des includes principaux de librairies communes
if (! defined('NOREQUIREUSER')) require_once(DOL_DOCUMENT_ROOT ."/user.class.php");		// Need 500ko memory
if (! defined('NOREQUIRETRAN')) require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
if (! defined('NOREQUIRESOC'))  require_once(DOL_DOCUMENT_ROOT ."/societe.class.php");
if (! defined('NOREQUIREDB'))   require_once(DOL_DOCUMENT_ROOT ."/lib/databases/".$conf->db->type.".lib.php");

/*
 * Creation objet $langs (must be before all other code)
 */
if (! defined('NOREQUIRETRAN')) 
{
	$langs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);	// A mettre apres lecture de la conf
}

/*
 * Creation objet $db
 */
if (! defined('NOREQUIREDB'))   
{
	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

	if ($db->error)
	{
		dolibarr_print_error($db,"host=".$conf->db->host.", port=".$conf->db->port.", user=".$conf->db->user.", databasename=".$conf->db->name.", ".$db->error);
		exit;   
	}
}

/*
 * Creation objet $user
 */
if (! defined('NOREQUIREUSER')) 
{
	$user = new User($db);
}

/*
 * Chargement objet $conf
 * After this, all parameters conf->global->CONSTANTS are loaded
 */
if (! defined('NOREQUIREDB'))   
{
	$conf->setValues($db);
}

/*
 * Chargement langage par défaut (must be after the setValues of $conf)
 */
if (! defined('NOREQUIRETRAN')) 
{
	$langs->setDefaultLang($conf->global->MAIN_LANG_DEFAULT);
	$langs->setPhpLang($conf->global->MAIN_LANG_DEFAULT);
}

/*
 * Pour utiliser d'autres versions des librairies externes que les
 * versions embarquées dans Dolibarr, définir les constantes adequates:
 * Pour FPDF:           FPDF_PATH
 * Pour PHP_WriteExcel: PHP_WRITEEXCEL_PATH
 * Pour MagpieRss:      MAGPIERSS_PATH
 * Pour PHPlot:         PHPLOT_PATH
 * Pour JPGraph:        JPGRAPH_PATH
 * Pour NuSOAP:         NUSOAP_PATH
 * Pour TCPDF:          TCPDF_PATH
 */
// Les path racines
if (! defined('FPDF_PATH'))           { define('FPDF_PATH',          DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdf/'); }
if (! defined('FPDFI_PATH'))          { define('FPDFI_PATH',         DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdfi/'); }
if (! defined('MAGPIERSS_PATH'))      { define('MAGPIERSS_PATH',     DOL_DOCUMENT_ROOT .'/includes/magpierss/'); }
if (! defined('JPGRAPH_PATH'))        { define('JPGRAPH_PATH',       DOL_DOCUMENT_ROOT .'/includes/jpgraph/'); }
if (! defined('NUSOAP_PATH'))         { define('NUSOAP_PATH',        DOL_DOCUMENT_ROOT .'/includes/nusoap/lib/'); }
if (! defined('PHP_WRITEEXCEL_PATH')) { define('PHP_WRITEEXCEL_PATH',DOL_DOCUMENT_ROOT .'/includes/php_writeexcel/'); }
if (! defined('PHPEXCELREADER'))      { define('PHPEXCELREADER',     DOL_DOCUMENT_ROOT .'/includes/phpexcelreader/'); }
// Les autres path
if (! defined('FPDF_FONTPATH'))       { define('FPDF_FONTPATH',      FPDF_PATH . 'font/'); }
if (! defined('MAGPIE_DIR'))          { define('MAGPIE_DIR',         MAGPIERSS_PATH); }
if (! defined('MAGPIE_CACHE_DIR'))    { define('MAGPIE_CACHE_DIR',   $conf->externalrss->dir_temp); }



/* 
 * Creation objet mysoc
 * Objet Societe qui contient carac de l'institution géré par Dolibarr.
 */
if (! defined('NOREQUIRESOC'))
{
	$mysoc=new Societe($db);
	$mysoc->id=0;
	$mysoc->nom=$conf->global->MAIN_INFO_SOCIETE_NOM;
	$mysoc->adresse=$conf->global->MAIN_INFO_SOCIETE_ADRESSE;
	$mysoc->cp=$conf->global->MAIN_INFO_SOCIETE_CP;
	$mysoc->ville=$conf->global->MAIN_INFO_SOCIETE_VILLE;
	// Si dans MAIN_INFO_SOCIETE_PAYS on a un id de pays, on recupere code
	if (is_numeric($conf->global->MAIN_INFO_SOCIETE_PAYS))
	{
		$mysoc->pays_id=$conf->global->MAIN_INFO_SOCIETE_PAYS;
	    $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
	    $sql .= " WHERE rowid = ".$conf->global->MAIN_INFO_SOCIETE_PAYS;
	    $result=$db->query($sql);
	    if ($result)
	    {
	        $obj = $db->fetch_object();
	        $mysoc->pays_code=$obj->code;
	    }
	    else {
	        dolibarr_print_error($db);
	    }
	}
	// Si dans MAIN_INFO_SOCIETE_PAYS on a deja un code, tout est fait
	else
	{
		$mysoc->pays_code=$conf->global->MAIN_INFO_SOCIETE_PAYS;
	}
	$mysoc->tel=$conf->global->MAIN_INFO_SOCIETE_TEL;
	$mysoc->fax=$conf->global->MAIN_INFO_SOCIETE_FAX;
	$mysoc->url=$conf->global->MAIN_INFO_SOCIETE_WEB;
	// Anciens id prof
	$mysoc->siren=$conf->global->MAIN_INFO_SIREN;
	$mysoc->siret=$conf->global->MAIN_INFO_SIRET;
	$mysoc->ape=$conf->global->MAIN_INFO_APE;
	$mysoc->rcs=$conf->global->MAIN_INFO_RCS;
	// Id prof génériques
	$mysoc->profid1=$conf->global->MAIN_INFO_SIREN;
	$mysoc->profid2=$conf->global->MAIN_INFO_SIRET;
	$mysoc->profid3=$conf->global->MAIN_INFO_APE;
	$mysoc->profid4=$conf->global->MAIN_INFO_RCS;
	$mysoc->tva_assuj=$conf->global->FACTURE_TVAOPTION;
	$mysoc->tva_intra=$conf->global->MAIN_INFO_TVAINTRA;
	$mysoc->capital=$conf->global->MAIN_INFO_CAPITAL;
	$mysoc->forme_juridique_code=$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
	$mysoc->email=$conf->global->MAIN_INFO_SOCIETE_MAIL;
	$mysoc->adresse_full=$mysoc->adresse."\n".$mysoc->cp." ".$mysoc->ville;
	$mysoc->logo=$conf->global->MAIN_INFO_SOCIETE_LOGO;
	$mysoc->logo_small=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
	$mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;
}

// Sert uniquement dans module telephonie
$yesno[0]="no";
$yesno[1]="yes";

// Sert pour styles lignes tableaux
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";

if ( ! defined('MAIN_LABEL_MENTION_NPR') ) define('MAIN_LABEL_MENTION_NPR','npr');

?>
