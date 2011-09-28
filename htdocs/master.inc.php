<?PHP
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/master.inc.php
 * 	\ingroup	core
 *  \brief      File that defines environment for all Dolibarr process (pages or scripts)
 * 				This script reads the conf file, init $lang, $db and and empty $user
 */


require_once("filefunc.inc.php");	// May have been already require by main.inc.php. But may not by scripts.


/*
 * Create $conf object
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/conf.class.php");

$conf = new Conf();

// Identifiant propres au serveur base de donnee
$conf->db->host   = $dolibarr_main_db_host;
$conf->db->port   = $dolibarr_main_db_port;
$conf->db->name   = $dolibarr_main_db_name;
$conf->db->user   = $dolibarr_main_db_user;
$conf->db->pass   = $dolibarr_main_db_pass;
$conf->db->type   = $dolibarr_main_db_type;
$conf->db->prefix = $dolibarr_main_db_prefix;
$conf->db->character_set=$dolibarr_main_db_character_set;
$conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;
$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;
$conf->file->main_limit_users = $dolibarr_main_limit_users;
$conf->file->mailing_limit_sendbyweb = $dolibarr_mailing_limit_sendbyweb;
if (defined('TEST_DB_FORCE_TYPE')) $conf->db->type=constant('TEST_DB_FORCE_TYPE');	// For test purpose
// Identifiant autres
$conf->file->main_authentication = empty($dolibarr_main_authentication)?'':$dolibarr_main_authentication;
// Force https
$conf->file->main_force_https = empty($dolibarr_main_force_https)?'':$dolibarr_main_force_https;
// Define charset for HTML Output (can set hidden value force_charset in conf file)
$conf->file->character_set_client=strtoupper($force_charset_do_notuse);
// Cookie cryptkey
$conf->file->cookie_cryptkey = empty($dolibarr_main_cookie_cryptkey)?'':$dolibarr_main_cookie_cryptkey;

// Define array of document root directories
$conf->file->dol_document_root=array('main' => DOL_DOCUMENT_ROOT);
if (! empty($dolibarr_main_document_root_alt))
{
	// dolibarr_main_document_root_alt contains several directories
	$values=preg_split('/[;,]/',$dolibarr_main_document_root_alt);
	foreach($values as $value)
	{
		$conf->file->dol_document_root['alt']=$value;
	}
}

// Chargement des includes principaux de librairies communes
if (! defined('NOREQUIREUSER')) require_once(DOL_DOCUMENT_ROOT ."/user/class/user.class.php");		// Need 500ko memory
if (! defined('NOREQUIRETRAN')) require_once(DOL_DOCUMENT_ROOT ."/core/class/translate.class.php");
if (! defined('NOREQUIRESOC'))  require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");

/*
 * Creation objet $langs (must be before all other code)
 */
if (! defined('NOREQUIRETRAN'))
{
	$langs = new Translate("",$conf);	// A mettre apres lecture de la conf
}

/*
 * Creation objet $db
 */
if (! defined('NOREQUIREDB'))
{
    $db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
	//$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

	if ($db->error)
	{
		dol_print_error($db,"host=".$conf->db->host.", port=".$conf->db->port.", user=".$conf->db->user.", databasename=".$conf->db->name.", ".$db->error);
		exit;
	}
}
// Now database connexion is known, so we can forget password
//$dolibarr_main_db_pass=''; 	// Comment this because this constant is used in a lot of pages
$conf->db->pass='';				// This is to avoid password to be shown in memory/swap dump

/*
 * Creation objet $user
 */
if (! defined('NOREQUIREUSER'))
{
	$user = new User($db);
}

/*
 * Load object $conf
 * After this, all parameters conf->global->CONSTANTS are loaded
 */
if (! defined('NOREQUIREDB'))
{
	// By default conf->entity is 1, but we change this if we ask another value.
	if (session_id() && ! empty($_SESSION["dol_entity"]))				// Entity inside an opened session
	{
		$conf->entity = $_SESSION["dol_entity"];
	}
	elseif (! empty($_ENV["dol_entity"]))								// Entity inside a CLI script
	{
		$conf->entity = $_ENV["dol_entity"];
	}
	elseif (isset($_POST["loginfunction"]) && ! empty($_POST["entity"]))	// Just after a login page
	{
		$conf->entity = $_POST["entity"];
	}
	else
	{
		$prefix=dol_getprefix();
	    $entityCookieName = 'DOLENTITYID_'.$prefix;
		if (! empty($_COOKIE[$entityCookieName]) && ! empty($conf->file->cookie_cryptkey)) 						// Just for view specific login page
		{
			include_once(DOL_DOCUMENT_ROOT."/core/class/cookie.class.php");

			$lastuser = '';
			$lastentity = '';

			$entityCookie = new DolCookie($conf->file->cookie_cryptkey);
			$cookieValue = $entityCookie->_getCookie($entityCookieName);
			list($lastuser, $lastentity) = explode('|', $cookieValue);
			$conf->entity = $lastentity;
		}
	}

	//print "Will work with data into entity instance number '".$conf->entity."'";

	// Here we read database (llx_const table) and define $conf->global->XXX var.
	$conf->setValues($db);
}

// Overwrite database value
if (! empty($conf->file->mailing_limit_sendbyweb))
{
	$conf->global->MAILING_LIMIT_SENDBYWEB = $conf->file->mailing_limit_sendbyweb;
}

// If software has been locked. Only login $conf->global->MAIN_ONLY_LOGIN_ALLOWED is allowed.
if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
{
	/*print '$_SERVER["GATEWAY_INTERFACE"]='.$_SERVER["GATEWAY_INTERFACE"].'<br>';
	 print 'session_id()='.session_id().'<br>';
	 print '$_SESSION["dol_login"]='.$_SESSION["dol_login"].'<br>';
	 print '$conf->global->MAIN_ONLY_LOGIN_ALLOWED='.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'<br>';
	 exit;*/
	$ok=0;
	if ((! session_id() || ! isset($_SESSION["dol_login"])) && ! isset($_POST["username"]) && ! empty($_SERVER["GATEWAY_INTERFACE"])) $ok=1;	// We let working pages if not logged and inside a web browser (login form, to allow login by admin)
	elseif (isset($_POST["username"]) && $_POST["username"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) $ok=1;				// We let working pages that is a login submission (login submit, to allow login by admin)
	elseif (defined('NOREQUIREDB'))   $ok=1;				// We let working pages that don't need database access (xxx.css.php)
	elseif (defined('EVEN_IF_ONLY_LOGIN_ALLOWED')) $ok=1;	// We let working pages that ask to work even if only login enabled (logout.php)
	elseif (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) $ok=1;	// We let working if user is allowed admin
	if (! $ok)
	{
		if (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] != $conf->global->MAIN_ONLY_LOGIN_ALLOWED)
		{
			print 'Sorry, your application is offline.'."\n";
			print 'You are logged with user "'.$_SESSION["dol_login"].'" and only administrator user "'.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'" is allowed to connect for the moment.'."\n";
			$nexturl=DOL_URL_ROOT.'/user/logout.php';
			print 'Please try later or <a href="'.$nexturl.'">click here to disconnect and change login user</a>...'."\n";
		}
		else
		{
			print 'Sorry, your application is offline. Only administrator user "'.$conf->global->MAIN_ONLY_LOGIN_ALLOWED.'" is allowed to connect for the moment.'."\n";
			$nexturl=DOL_URL_ROOT.'/';
			print 'Please try later or <a href="'.$nexturl.'">click here to change login user</a>...'."\n";
		}
		exit;
	}
}

/*
 * Create object $mysoc (A thirdparty object that contains properties of companies managed by Dolibarr.
 */
if (! defined('NOREQUIREDB') && ! defined('NOREQUIRESOC'))
{
	require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");
	$mysoc=new Societe($db);

	$mysoc->id=0;
	$mysoc->nom=$conf->global->MAIN_INFO_SOCIETE_NOM; 			// TODO deprecated
	$mysoc->name=$conf->global->MAIN_INFO_SOCIETE_NOM;
	$mysoc->adresse=$conf->global->MAIN_INFO_SOCIETE_ADRESSE; 	// TODO deprecated
	$mysoc->address=$conf->global->MAIN_INFO_SOCIETE_ADRESSE;
	$mysoc->cp=$conf->global->MAIN_INFO_SOCIETE_CP; 			// TODO deprecated
	$mysoc->zip=$conf->global->MAIN_INFO_SOCIETE_CP;
	$mysoc->ville=$conf->global->MAIN_INFO_SOCIETE_VILLE; 		// TODO deprecated
	$mysoc->town=$conf->global->MAIN_INFO_SOCIETE_VILLE;
	$mysoc->state_id=$conf->global->MAIN_INFO_SOCIETE_DEPARTEMENT;
	$mysoc->note=empty($conf->global->MAIN_INFO_SOCIETE_NOTE)?'':$conf->global->MAIN_INFO_SOCIETE_NOTE;

    // We define pays_id, pays_code and pays_label
    $tmp=explode(':',$conf->global->MAIN_INFO_SOCIETE_PAYS);
    $country_id=$tmp[0];
    if (! empty($tmp[1]))   // If $conf->global->MAIN_INFO_SOCIETE_PAYS is "id:code:label"
    {
        $country_code=$tmp[1];
        $country_label=$tmp[2];
    }
    else                    // For backward compatibility
    {
        dol_syslog("Your country setup use an old syntax. Reedit it in setup area.", LOG_WARNING);
        include_once(DOL_DOCUMENT_ROOT.'/lib/company.lib.php');
        $country_code=getCountry($country_id,2,$db);  // This need a SQL request, but it's the old feature
        $country_label=getCountry($country_id,0,$db);  // This need a SQL request, but it's the old feature
    }
    $mysoc->pays_id=$country_id;		// TODO deprecated
    $mysoc->country_id=$country_id;
    $mysoc->pays_code=$country_code;	// TODO deprecated
    $mysoc->country_code=$country_code;
    $mysoc->country=$country_label;
    if (is_object($langs)) $mysoc->country=($langs->trans('Country'.$country_code)!='Country'.$country_code)?$langs->trans('Country'.$country_code):$country_label;
    $mysoc->pays=$mysoc->country;    	// TODO deprecated

	$mysoc->tel=empty($conf->global->MAIN_INFO_SOCIETE_TEL)?'':$conf->global->MAIN_INFO_SOCIETE_TEL;   // TODO deprecated
    $mysoc->phone=empty($conf->global->MAIN_INFO_SOCIETE_TEL)?'':$conf->global->MAIN_INFO_SOCIETE_TEL;
	$mysoc->fax=empty($conf->global->MAIN_INFO_SOCIETE_FAX)?'':$conf->global->MAIN_INFO_SOCIETE_FAX;
	$mysoc->url=empty($conf->global->MAIN_INFO_SOCIETE_WEB)?'':$conf->global->MAIN_INFO_SOCIETE_WEB;
	// Anciens id prof
	$mysoc->siren=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
	$mysoc->siret=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
	$mysoc->ape=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
	$mysoc->rcs=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
	// Id prof generiques
	$mysoc->idprof1=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
	$mysoc->idprof2=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
	$mysoc->idprof3=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
	$mysoc->idprof4=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
	$mysoc->tva_intra=$conf->global->MAIN_INFO_TVAINTRA;	// VAT number, not necessarly INTRA.
	$mysoc->idtrainer=empty($conf->global->MAIN_INFO_TRAINER)?'':$conf->global->MAIN_INFO_TRAINER;
	$mysoc->capital=$conf->global->MAIN_INFO_CAPITAL;
	$mysoc->forme_juridique_code=$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
	$mysoc->email=$conf->global->MAIN_INFO_SOCIETE_MAIL;
	$mysoc->logo=$conf->global->MAIN_INFO_SOCIETE_LOGO;
	$mysoc->logo_small=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
	$mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;

	// Define if company use vat or not (Do not use conf->global->FACTURE_TVAOPTION anymore)
	$mysoc->tva_assuj=((isset($conf->global->FACTURE_TVAOPTION) && $conf->global->FACTURE_TVAOPTION=='franchise')?0:1);

	// Define if company use local taxes
	$mysoc->localtax1_assuj=((isset($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')?1:0);
	$mysoc->localtax2_assuj=((isset($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')?1:0);

	// For some countries, we need to invert our address with customer address
	if ($mysoc->pays_code == 'DE' && ! isset($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $conf->global->MAIN_INVERT_SENDER_RECIPIENT=1;
}


// Set default language (must be after the setValues of $conf)
if (! defined('NOREQUIRETRAN'))
{
	$langs->setDefaultLang($conf->global->MAIN_LANG_DEFAULT);
}

if (! defined('MAIN_LABEL_MENTION_NPR') ) define('MAIN_LABEL_MENTION_NPR','NPR');

?>
