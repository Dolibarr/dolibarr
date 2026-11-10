<?php
/* Copyright (C) 2000-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2025	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Christophe Combelles		<ccomb@free.fr>
 * Copyright (C) 2005-2019	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2018	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2026  Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2014		Cédric GROSS				<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2018-2026  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2019-2023  Thibault Foucart            <support@ptibogxiv.net>
 * Copyright (C) 2020       Open-Dsi         			<support@open-dsi.fr>
 * Copyright (C) 2021       Gauthier VERDOL         	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2022-2026  Anthony Berton	         	<anthony.berton@bb2a.fr>
 * Copyright (C) 2022       Ferran Marcet           	<fmarcet@2byte.es>
 * Copyright (C) 2022-2026  Charlene Benke           	<charlene@patas-monkey.com>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2023-2024  Joachim Kueter              <git-jk@bloxera.com>
 * Copyright (C) 2024		Lenin Rivas					<lenin.rivas777@gmail.com>
 * Copyright (C) 2024		Josep Lluís Amador Teruel	<joseplluis@lliuretic.cat>
 * Copyright (C) 2024		Benoît PASCAL				<contact@p-ben.com>
 * Copyright (C) 2025		Vincent Maury				<vmaury@timgroup.fr>
 * Copyright (C) 2026		Benjamin Falière			<benjamin@faliere.com>
 * Copyright (C) 2026		Pierre Ardoin				<developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */

//include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';

// Function for better PHP x compatibility
if (!function_exists('utf8_encode')) {
	/**
	 * Implement utf8_encode for PHP that does not support it.
	 *
	 * @param	mixed	$elements		PHP Object to json encode
	 * @return 	string					Json encoded string
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function utf8_encode($elements)
	{
		return mb_convert_encoding($elements, 'UTF-8', 'ISO-8859-1');
	}
}

if (!function_exists('utf8_decode')) {
	/**
	 * Implement utf8_decode for PHP that does not support it.
	 *
	 * @param	mixed	$elements		PHP Object to json encode
	 * @return 	string					Json encoded string
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function utf8_decode($elements)
	{
		return mb_convert_encoding($elements, 'ISO-8859-1', 'UTF-8');
	}
}
if (!function_exists('str_starts_with')) {
	/**
	 * str_starts_with
	 *
	 * @param string $haystack	haystack
	 * @param string $needle	needle
	 * @return boolean
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function str_starts_with($haystack, $needle)
	{
		return (string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}
if (!function_exists('str_ends_with')) {
	/**
	 * str_ends_with
	 *
	 * @param string $haystack	haystack
	 * @param string $needle	needle
	 * @return boolean
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function str_ends_with($haystack, $needle)
	{
		return $needle !== '' && substr($haystack, -strlen($needle)) === (string) $needle;
	}
}
if (!function_exists('str_contains')) {
	/**
	 * str_contains
	 *
	 * @param string $haystack	haystack
	 * @param string $needle	needle
	 * @return boolean
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function str_contains($haystack, $needle)
	{
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}


/**
 *	Return a string serialized to be output on log with dol_syslog()
 *	An option allow to output log in one line instead of a structured human tree
 *
 *  @param	mixed	$data	Any PHP object
 *  @return	string			Serialized data for loh
 */
function formatLogObject($data)
{
	if (getDolGlobalInt("MAIN_LOG_ON_ONE_LINE")) {
		return json_encode($data);
	}

	return var_export($data, true);
}


/**
 * Return the full path of the directory where a module (or an object of a module) stores its files.
 * Path may depends on the entity if a multicompany module is enabled.
 *
 * @param 	CommonObject|BlockedLog|null	$object 	Dolibarr common object.
 * @param 	string 							$module 	Override object element, for example to use 'mycompany' instead of 'societe'
 * @param	int								$forobject	Use 1 to return the more complete path for the given object (including ref) instead of for the module only.
 * @param	string							$mode		'output' (full main dir) or 'outputrel' (relative dir) or 'temp' (full dir for temporary files) or 'version' (full dir for archived files)
 * @return 	string|null									The path of the relative directory of the module, ending with /
 * @since Dolibarr V18
 */
function getMultidirOutput($object, $module = '', $forobject = 0, $mode = 'output')
{
	global $conf;

	$subdirectory = '';
	if (!is_object($object) && empty($module)) {
		return null;
	}
	if (empty($module) && !empty($object->element)) {
		$module = $object->element;
	}

	// Special case for backward compatibility
	switch ($module) {
		case 'fichinter':
			$module = 'ficheinter';
			break;
		case 'invoice_supplier':
			$module = 'supplier_invoice';
			break;
		case 'order_supplier':
			$module = 'supplier_order';
			break;
		case 'recruitmentjobposition':
			$module = 'recruitment';
			$subdirectory = '/recruitmentjobposition';
			break;
		case 'recruitmentcandidature':
			$module = 'recruitment';
			$subdirectory = '/recruitmentcandidature';
			break;
		case 'knowledgerecord':
			$module = 'knowledgemanagement';
			$subdirectory = '/knowledgerecord';
			break;
		case 'partnership':
			$subdirectory = '/partnership';
			break;
		case 'stocktransfer':
			$subdirectory = '/stocktransfer';
			break;
		case 'commande_fournisseur':
			$module = 'fournisseur';
			$subdirectory = '/commande';
			break;
		case 'expedition':
		case 'shipment':
		case 'shipping':
			$module = 'expedition';
			$subdirectory = '/sending';
			break;
		case 'company':
			$module = 'societe';
			break;
		case 'service':
		case 'produit':
			$module = 'product';
			break;
		case 'project_task':
			$module = 'projet';

			// Fetch the project to build the correct path. The signature of this function accepts an object
			// that is not a CommonObject, and even a null when a module is given, so we must not call a method
			// that only a CommonObject owns without testing it exists.
			if (is_object($object) && method_exists($object, 'fetchProject')) {
				$object->fetchProject();
			}

			// The ref must be sanitized with dol_sanitizeFileName() and not only with dol_sanitizePathName()
			// done at the end of this function, because a project ref is a user input that may contain a '/',
			// a ':' or an accented char. dol_sanitizePathName() keeps them, so we would not return the
			// directory used by projet/tasks/document.php, that sanitizes the ref with dol_sanitizeFileName().
			if (!empty($object->project->ref)) {
				$subdirectory = '/'.dol_sanitizeFileName($object->project->ref);
			}
			break;
		case 'action':
		case 'actioncomm':
		case 'event':
			$module = 'agenda';
			break;
		default:
			break;
	}

	// Get the relative path of directory
	if ($mode == 'output' || $mode == 'outputrel' || $mode == 'version') {
		if (isset($conf->$module) && property_exists($conf->$module, 'multidir_output')) {
			$s = '';
			if ($mode != 'outputrel') {
				// An entity with no declared directory returned an undefined index, so an empty path that
				// made the caller read or write a relative path under the web root. Answer the error instead.
				$entity = (int) (empty($object->entity) ? $conf->entity : $object->entity);
				if (!isset($conf->$module->multidir_output[$entity])) {
					return 'error-diroutput-not-defined-for-this-entity-and-object='.$module;
				}
				$s = $conf->$module->multidir_output[$entity] . $subdirectory;
			}
			if ($forobject && $object->id > 0) {
				$s .= ($mode != 'outputrel' ? '/' : '') . get_exdir(0, 0, 0, 0, $object);
			}
			return dol_sanitizePathName($s);
		} elseif (isset($conf->$module) && property_exists($conf->$module, 'dir_output')) {
			$s = '';
			if ($mode != 'outputrel') {
				$s = $conf->$module->dir_output . $subdirectory;
			}
			if ($forobject && $object->id > 0) {
				$s .= ($mode != 'outputrel' ? '/' : '') . get_exdir(0, 0, 0, 0, $object);
			}
			return dol_sanitizePathName($s);
		} else {
			return 'error-diroutput-not-defined-for-this-entity-and-object='.$module;
		}
	} elseif ($mode == 'temp') {
		if (isset($conf->$module) && property_exists($conf->$module, 'multidir_temp')) {
			// Same guard as the 'output' mode above, see the comment there
			$entity = (int) (empty($object->entity) ? $conf->entity : $object->entity);
			if (!isset($conf->$module->multidir_temp[$entity])) {
				return 'error-dirtemp-not-defined-for-this-entity-and-object='.$module;
			}
			return dol_sanitizePathName($conf->$module->multidir_temp[$entity]);
		} elseif (isset($conf->$module) && property_exists($conf->$module, 'dir_temp')) {
			return dol_sanitizePathName($conf->$module->dir_temp);
		} else {
			return 'error-dirtemp-not-defined-for-this-entity-and-object='.$module;
		}
	} else {
		return 'error-bad-value-for-mode';
	}
}

/**
 * Return the full path of the directory where a module (or an object of a module) stores its temporary files.
 * Path may depends on the entity if a multicompany module is enabled.
 *
 * @param 	CommonObject 	$object 	Dolibarr common object
 * @param 	string 			$module 	Override object element, for example to use 'mycompany' instead of 'societe'
 * @param	int				$forobject	Return the more complete path for the given object instead of for the module only.
 * @return 	string|null					The path of the relative temp directory of the module
 */
function getMultidirTemp($object, $module = '', $forobject = 0)
{
	return getMultidirOutput($object, $module, $forobject, 'temp');
}

/**
 * Return the full path of the directory where a module (or an object of a module) stores its versioned files.
 * Path may depends on the entity if a multicompany module is enabled.
 *
 * @param 	CommonObject 	$object 	Dolibarr common object
 * @param 	string 			$module 	Override object element, for example to use 'mycompany' instead of 'societe'
 * @param	int				$forobject	Return the more complete path for the given object instead of for the module only.
 * @return string|null					The path of the relative version directory of the module
 */
function getMultidirVersion($object, $module = '', $forobject = 0)
{
	return getMultidirOutput($object, $module, $forobject, 'version');
}


/**
 * Return a Dolibarr global constant string value
 *
 * @param 	string 				$key 		Key to return value, return $default if not set
 * @param 	string|int|float 	$default 	Value to return if not defined
 * @return 	string							Value returned
 * @see getDolUserString()
 */
function getDolGlobalString($key, $default = '')
{
	global $conf;
	return (string) (isset($conf->global->$key) ? $conf->global->$key : $default);
}

/**
 * Return the list of unauthorized characters in user logins.
 *
 * @return string
 */
function getDolGlobalLoginBadCharUnauthorized()
{
	global $dolibarr_login_badcharunauthorized;

	if (isset($dolibarr_login_badcharunauthorized)) {
		if ($dolibarr_login_badcharunauthorized === 'MAIN_LOGIN_BADCHARUNAUTHORIZED') {
			return getDolGlobalString('MAIN_LOGIN_BADCHARUNAUTHORIZED', ',@<>"\'');
		}

		return (string) $dolibarr_login_badcharunauthorized;
	}

	return ',@<>"\'';
}

/**
 * Return a Dolibarr global constant int value.
 * The constants $conf->global->xxx are loaded by the script master.inc.php included at begin of any PHP page.
 *
 * @param string 	$key 		Key to return value, return $default if not set
 * @param int 		$default 	Value to return if not defined
 * @return int					Value returned
 * @see getDolUserInt()
 */
function getDolGlobalInt($key, $default = 0)
{
	global $conf;
	return (int) (isset($conf->global->$key) ? $conf->global->$key : $default);
}

/**
 * Return a Dolibarr global constant float value.
 * The constants $conf->global->xxx are loaded by the script master.inc.php included at begin of any PHP page.
 *
 * @param string 	$key 		Key to return value, return $default if not set
 * @param float 		$default 	Value to return if not defined
 * @return float					Value returned
 * @see getDolUserInt()
 */
function getDolGlobalFloat($key, $default = 0)
{
	global $conf;
	return (float) (isset($conf->global->$key) ? $conf->global->$key : $default);
}

/**
 * Return a Dolibarr global constant boolean value.
 * The constants $conf->global->xxx are loaded by the script master.inc.php included at begin of any PHP page.
 *
 * @param string 	$key 		Key to return value, return $default if not set
 * @param bool 		$default 	Value to return if not defined
 * @return bool					Value returned
 */
function getDolGlobalBool($key, $default = false)
{
	global $conf;
	return (bool) ($conf->global->$key ?? $default);
}

/**
 * Return the main currency ('EUR', 'USD', ...)
 *
 * @return 	string							Value returned
 */
function getDolCurrency()
{
	global $conf;
	return (string) $conf->currency;
}

/**
 * Return if render must be optimized for small screen
 *
 * @return 	string							Value returned
 */
function getDolOptimizeSmallScreen()
{
	global $conf;
	return (string) $conf->dol_optimize_smallscreen;
}

/**
 * Return the current entity
 *
 * @return 	int							Value returned
 */
function getDolEntity()
{
	global $conf;
	return (int) $conf->entity;
}

/**
 * Return the current entity
 *
 * @return 	string						Value returned
 */
function getDolDBType()
{
	global $conf;
	return $conf->db->type;
}

/**
 * Return the default context page string
 *
 * @param	string		$s					Page path
 * @return 	string							Value returned
 */
function getDolDefaultContextPage($s)
{
	return str_replace('_', '', basename(dirname($s)).basename($s, '.php'));
}

/**
 * Return Dolibarr user constant string value
 *
 * @param string 			$key 		Key to return value, return '' if not set
 * @param string|int|float 	$default 	Value to return
 * @param User   			$tmpuser	To get another user than current user
 * @return string
 * @see getDolGlobalString()
 */
function getDolUserString($key, $default = '', $tmpuser = null)
{
	if (empty($tmpuser)) {
		global $user;
		$tmpuser = $user;
	}

	return (string) (isset($tmpuser->conf->$key) ? $tmpuser->conf->$key : $default);
}

/**
 * Return Dolibarr user constant int value
 *
 * @param string 	$key 			Key to return value, return 0 if not set
 * @param int 		$default 		Value to return
 * @param User   	$tmpuser   		To get another user than current user
 * @return int
 */
function getDolUserInt($key, $default = 0, $tmpuser = null)
{
	if (empty($tmpuser)) {
		global $user;
		$tmpuser = $user;
	}

	return (int) (isset($tmpuser->conf->$key) ? $tmpuser->conf->$key : $default);
}


/**
 * This mapping defines the conversion to the current internal
 * names from the alternative allowed names (including effectively deprecated
 * and future new names not yet used as internal names).
 *
 * This allows to map any temporary or future name to the effective internal name.
 * The value is typically the name of module's root directory.
 */
define(
	'MODULE_MAPPING',
	array(
		// Map deprecated names to new names
		'adherent' => 'member',  // Has new directory
		'member_type' => 'adherent_type',   // No directory, but file called adherent_type
		'banque' => 'bank',   // Has new directory
		'contrat' => 'contract', // Has new directory
		'entrepot' => 'stock',   // Has new directory
		'projet'  => 'project', // Has new directory
		'categorie' => 'category', // Has old directory
		'commande' => 'order',    // Has old directory
		'expedition' => 'shipping', // Has old directory
		'facture' => 'invoice', // Has old directory
		'fichinter' => 'intervention', // Has old directory
		'ficheinter' => 'intervention',  // Backup for 'fichinter'
		'propale' => 'propal', // Has old directory
		'societe' => 'thirdparty',  // Has old directory
		'socpeople' => 'contact', // Has old directory
		'fournisseur' => 'supplier',  // Has old directory

		'actioncomm' => 'agenda',  // NO module directory (public dir agenda)
		'product_price' => 'productprice', // NO directory
		'product_fournisseur_price' => 'productsupplierprice', // NO directory
	)
);

/**
 * Is Dolibarr module enabled
 *
 * @param 	string 	$module 	Module name to check
 * @return 	boolean				True if module is enabled
 */
function isModEnabled($module)
{
	global $conf;

	if (!empty($conf->modules[$module])) {
		return true;	// Most calls use the real name of the module: no need to look at the old/new names mapping
	}

	// Fix old names (map to new names). The mappings are constant for the request, so they are built once: this function is
	// called thousands of times per page (hooks, rights, logs...), and array_flip() on each call was most of its cost.
	static $arrayconv = null;
	static $arrayconvbis = null;
	if ($arrayconv === null) {
		$arrayconv = MODULE_MAPPING;
		$arrayconvbis = array_flip(MODULE_MAPPING);

		if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
			// Special cases: both use the same module.
			$arrayconv['supplier_order'] = 'fournisseur';
			$arrayconv['supplier_invoice'] = 'fournisseur';
		}
	}

	if (!empty($arrayconv[$module]) && !empty($conf->modules[$arrayconv[$module]])) {
		return true;
	}
	if (!empty($arrayconvbis[$module]) && !empty($conf->modules[$arrayconvbis[$module]])) {
		return true;
	}

	return false;
}

/**
 *  Return a warning delay
 *  You can use it like this: if (getWarningDelay('module', 'paramlevel1'))
 *  It replaces old syntax: if ($conf->module->user->rights->module->level1)
 *
 * 	@param	string	$module			Module of permission to check (Example: 'bank', 'contrat', ...)
 *  @param  string	$parmlevel1		Parameter level1 (Example: 'rappro', 'services', ...)
 *  @param  string	$parmlevel2		Parameter level2 (Example: 'inactifs', ...)
 *  @return int						Return the warning delay
 */
function getWarningDelay($module, $parmlevel1, $parmlevel2 = '')
{
	global $conf;

	// For compatibility with bad naming on module
	$moduletomoduletouse = array(
		'invoice' => 'facture',
	);
	$moduleParmsMapping = array(
		'product' => 'produit',
	);

	if (!empty($moduletomoduletouse[$module])) {
		$module = $moduletomoduletouse[$module];
	}

	$warningDelayPath = $parmlevel1;
	if (!empty($moduleParmsMapping[$warningDelayPath])) {
		$warningDelayPath = $moduleParmsMapping[$warningDelayPath];
	}

	if ($parmlevel2) {
		if (!empty($conf->$module) && !empty($conf->$module->$warningDelayPath) && !empty($conf->$module->$warningDelayPath->$parmlevel2) && !empty($conf->$module->$warningDelayPath->$parmlevel2->warning_delay)) {
			return (int) $conf->$module->$warningDelayPath->$parmlevel2->warning_delay;
		}
	} else {
		if (!empty($conf->$module) && !empty($conf->$module->$warningDelayPath) && !empty($conf->$module->$warningDelayPath->warning_delay)) {
			return (int) $conf->$module->$warningDelayPath->warning_delay;
		}
	}

	return 0;
}

/**
 * isDolTms check if a timestamp is valid.
 *
 * @param  int|string|null $timestamp timestamp to check
 * @return bool
 */
function isDolTms($timestamp)
{
	if ($timestamp === '') {
		dol_syslog('Using empty string for a timestamp is deprecated, prefer use of null when calling page ' . $_SERVER["PHP_SELF"] . getCallerInfoString(), LOG_DEBUG);
		return false;
	}
	if (is_null($timestamp) || !is_numeric($timestamp)) {
		return false;
	}

	return true;
}

/**
 * Return a DoliDB instance (database handler).
 *
 * @param   string	$type		Type of database (mysql, pgsql...)
 * @param	string	$host		Address of database server
 * @param	string	$user		Authorized username
 * @param	string	$pass		Password (clear)
 * @param	string	$name		Name of database
 * @param	int		$port		Port of database server
 * @param	bool	$forcenew	Force opening of a genuinely new connection instead of reusing one already opened to the same server/database in this process (only meaningful for drivers, like pgsql, that may otherwise transparently reuse a matching connection)
 * @return	DoliDB				A DoliDB instance
 */
function getDoliDBInstance($type, $host, $user, $pass, $name, $port, $forcenew = false)
{
	require_once DOL_DOCUMENT_ROOT . "/core/db/" . $type . '.class.php';

	$class = 'DoliDB' . ucfirst($type);
	$db = new $class($type, $host, $user, $pass, $name, $port, $forcenew);
	return $db;
}

/**
 * 	Get list of entity id to use.
 *
 * 	@param	string	$element		Current element. The value is usually coming from $object->element property. Example:
 *									'societe', 'socpeople', 'actioncomm', 'agenda', 'resource',
 *									'product', 'productprice', 'stock', 'bom', 'mo',
 *									'propal', 'supplier_proposal', 'invoice', 'supplier_invoice', 'payment_various',
 *									'categorie', 'bank_account', 'bank_account', 'adherent', 'user',
 *									'commande', 'supplier_order', 'expedition', 'intervention', 'survey',
 *									'contract', 'tax', 'expensereport', 'holiday', 'multicurrency', 'project',
 *									'email_template', 'event', 'donation'
 *									'c_paiement', 'c_payment_term', ...
 * 	@param	int<0,1>	$shared		0=Return id of current entity only,
 * 									1=Return id of current entity + shared entities (default)
 *  @param	?CommonObject			$currentobject	Current object if needed
 * 	@return	string					Entity id(s) to use ( eg. entity IN ('.getEntity(elementname).')' )
 */
function getEntity($element, $shared = 1, $currentobject = null)
{
	global $conf, $mc, $hookmanager, $object, $action, $db;

	if (!is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}

	// fix different element names (France to English)
	switch ($element) {
		case 'projet':
			$element = 'project';
			break;
		case 'contrat':
			$element = 'contract';
			break; // "/contrat/class/contrat.class.php"
		case 'order_supplier':
			$element = 'supplier_order';
			break; // "/fourn/class/fournisseur.commande.class.php"
		case 'invoice_supplier':
			$element = 'supplier_invoice';
			break; // "/fourn/class/fournisseur.facture.class.php"
	}

	if (is_object($mc)) {
		$out = $mc->getEntity($element, $shared, $currentobject);
	} else {
		$out = '';
		$addzero = array('user', 'usergroup', 'cronjob', 'c_email_templates', 'email_template', 'default_values', 'overwrite_trans', 'extrafields');
		if (getDolGlobalString('HOLIDAY_ALLOW_ZERO_IN_DIC')) { // this constant break the dictionary admin without Multicompany
			$addzero[] = 'c_holiday_types';
		}
		if (in_array($element, $addzero)) {
			$out .= '0,';
		}
		$out .= ((int) $conf->entity);
	}

	// Manipulate entities to query on the fly
	$parameters = array(
		'element' => $element,
		'shared' => $shared,
		'object' => $object,
		'currentobject' => $currentobject,
		'out' => $out
	);
	// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
	$reshook = $hookmanager->executeHooks('hookGetEntity', $parameters, $currentobject, $action); // Note that $action and $object may have been modified by some hooks

	if (is_numeric($reshook)) {
		if ($reshook == 0 && !empty($hookmanager->resPrint)) {
			$out .= ',' . $hookmanager->resPrint; // add
		} elseif ($reshook == 1) {
			$out = $hookmanager->resPrint; // replace
		}
	}

	return $out;
}

/**
 * 	Set entity id to use when to create an object
 *
 * 	@param	CommonObject	$currentobject	Current object
 * 	@return	int								Entity id to use ( eg. entity = '.setEntity($object) )
 */
function setEntity($currentobject)
{
	global $conf, $mc;

	if (is_object($mc) && method_exists($mc, 'setEntity')) {
		return $mc->setEntity($currentobject);
	} else {
		return ((is_object($currentobject) && $currentobject->id > 0 && ((int) $currentobject->entity) > 0) ? (int) $currentobject->entity : $conf->entity);
	}
}

/**
 * 	Return if string has a name dedicated to store a secret
 *
 * 	@param	string	$keyname	Name of key to test
 * 	@return	boolean				True if key is used to store a secret
 */
function isASecretKey($keyname)
{
	return preg_match('/(_pass|password|_pw|_key|securekey|serverkey|secret\d?|p12key|exportkey|_PW_[a-z]+|token)$/i', $keyname);
}


/**
 * Return a numeric value into an Excel like column number. So 0 return 'A', 1 returns 'B'..., 26 return 'AA'
 *
 * @param	int|string		$n		Numeric value
 * @return 	string					Column in Excel format
 */
function num2Alpha($n)
{
	$r = '';
	for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
		$r = chr($n % 26 + 0x41) . $r;
	}
	return $r;
}


/**
 * Return information about user browser
 *
 * Returns array with the following format:
 * array(
 *  'browsername' => Browser name (firefox|chrome|iceweasel|epiphany|safari|opera|ie|unknown)
 *  'browserversion' => Browser version. Empty if unknown
 *  'browseros' => Set with mobile OS (android|blackberry|ios|palm|symbian|webos|maemo|windows|unknown)
 *  'layout' => (tablet|phone|classic)
 *  'phone' => empty if not mobile, (android|blackberry|ios|palm|unknown) if mobile
 *  'tablet' => true/false
 * )
 *
 * @param string $user_agent Content of $_SERVER["HTTP_USER_AGENT"] variable
 * @return	array{browsername:string,browserversion:string,browseros:string,browserua:string,layout:string,phone:string,tablet:bool} Check function documentation
 */
function getBrowserInfo($user_agent)
{
	include_once DOL_DOCUMENT_ROOT . '/includes/mobiledetect/mobiledetectlib/Mobile_Detect.php';

	$name = 'unknown';
	$version = '';
	$os = 'unknown';
	$phone = '';

	$user_agent = substr($user_agent, 0, 512);	// Avoid to process too large user agent

	// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal Bad definition of Mobile_Detect function
	$detectmobile = new Mobile_Detect(null, $user_agent);
	$tablet = $detectmobile->isTablet();

	if ($detectmobile->isMobile()) {
		$phone = 'unknown';

		// If phone/smartphone, we set phone os name.
		if ($detectmobile->is('AndroidOS')) {
			$os = $phone = 'android';
		} elseif ($detectmobile->is('BlackBerryOS')) {
			$os = $phone = 'blackberry';
		} elseif ($detectmobile->is('iOS')) {
			$os = 'ios';
			$phone = 'iphone';
		} elseif ($detectmobile->is('PalmOS')) {
			$os = $phone = 'palm';
		} elseif ($detectmobile->is('SymbianOS')) {
			$os = 'symbian';
		} elseif ($detectmobile->is('webOS')) {
			$os = 'webos';
		} elseif ($detectmobile->is('MaemoOS')) {
			$os = 'maemo';
		} elseif ($detectmobile->is('WindowsMobileOS') || $detectmobile->is('WindowsPhoneOS')) {
			$os = 'windows';
		}
	}

	// OS
	if (preg_match('/linux/i', $user_agent)) {
		$os = 'linux';
	} elseif (preg_match('/macintosh/i', $user_agent)) {
		$os = 'macintosh';
	} elseif (preg_match('/windows/i', $user_agent)) {
		$os = 'windows';
	}

	// Name
	$reg = array();
	if (preg_match('/firefox(\/|\s)([\d\.]*)/i', $user_agent, $reg)) {
		$name = 'firefox';
		$version = empty($reg[2]) ? '' : $reg[2];
	} elseif (preg_match('/edge(\/|\s)([\d\.]*)/i', $user_agent, $reg)) {
		$name = 'edge';
		$version = empty($reg[2]) ? '' : $reg[2];
	} elseif (preg_match('/chrome(\/|\s)([\d\.]+)/i', $user_agent, $reg)) {
		$name = 'chrome';
		$version = empty($reg[2]) ? '' : $reg[2];
	} elseif (preg_match('/chrome/i', $user_agent, $reg)) {
		// we can have 'chrome (Mozilla...) chrome x.y' in one string
		$name = 'chrome';
	} elseif (preg_match('/iceweasel/i', $user_agent)) {
		$name = 'iceweasel';
	} elseif (preg_match('/epiphany/i', $user_agent)) {
		$name = 'epiphany';
	} elseif (preg_match('/safari(\/|\s)([\d\.]*)/i', $user_agent, $reg)) {
		$name = 'safari';
		$version = empty($reg[2]) ? '' : $reg[2];
	} elseif (preg_match('/opera(\/|\s)([\d\.]*)/i', $user_agent, $reg)) {
		// Safari is often present in string for mobile but its not.
		$name = 'opera';
		$version = empty($reg[2]) ? '' : $reg[2];
	} elseif (preg_match('/(MSIE\s([0-9]+\.[0-9]))|.*(Trident\/[0-9]+.[0-9];.*rv:([0-9]+\.[0-9]+))/i', $user_agent, $reg)) {
		$name = 'ie';
		$version = end($reg);
	} elseif (preg_match('/(Windows NT\s([0-9]+\.[0-9])).*(Trident\/[0-9]+.[0-9];.*rv:([0-9]+\.[0-9]+))/i', $user_agent, $reg)) {
		// MS products at end
		$name = 'ie';
		$version = end($reg);
	} elseif (preg_match('/l[iy]n(x|ks)(\(|\/|\s)*([\d\.]+)/i', $user_agent, $reg)) {
		// MS products at end
		$name = 'textbrowser';
		$version = empty($reg[3]) ? '' : $reg[3];
	} elseif (preg_match('/w3m\/([\d\.]+)/i', $user_agent, $reg)) {
		// MS products at end
		$name = 'textbrowser';
		$version = empty($reg[1]) ? '' : $reg[1];
	}

	if ($tablet) {
		$layout = 'tablet';
	} elseif ($phone) {
		$layout = 'phone';
	} else {
		$layout = 'classic';
	}

	return array(
		'browsername' => $name,
		'browserversion' => $version,
		'browseros' => $os,
		'browserua' => $user_agent,
		'layout' => $layout,	// tablet, phone, classic
		'phone' => $phone,		// deprecated
		'tablet' => $tablet		// deprecated
	);
}

/**
 *  Function called at end of web php process
 *
 *  @return	void
 */
function dol_shutdown()
{
	global $db;
	$disconnectdone = false;
	$depth = 0;
	if (is_object($db) && !empty($db->connected)) {
		$depth = $db->transaction_opened;
		$disconnectdone = $db->close();
	}
	dol_syslog("--- End access to " . (empty($_SERVER["PHP_SELF"]) ? 'unknown' : $_SERVER["PHP_SELF"]) . (($disconnectdone && $depth) ? ' (Warn: db disconnection forced, transaction depth was ' . $depth . ')' : ''), (($disconnectdone && $depth) ? LOG_WARNING : LOG_INFO));
}

/**
 * Return true if we are in a context of submitting the parameter $paramname from a POST of a form.
 * Warning:
 * For action=add, use:     $var = GETPOST('var');		// No GETPOSTISSET, so GETPOST always called and default value is retrieved if not a form POST, and value of form is retrieved if it is a form POST.
 * For action=update, use:  $var = GETPOSTISSET('var') ? GETPOST('var') : $object->var;
 *
 * @param 	string	$paramname		Name or parameter to test
 * @return 	boolean					True if we have just submit a POST or GET request with the parameter provided (even if param is empty)
 */
function GETPOSTISSET($paramname)
{
	$isset = false;

	$relativepathstring = $_SERVER["PHP_SELF"];
	// Clean $relativepathstring
	if (constant('DOL_URL_ROOT')) {
		$relativepathstring = preg_replace('/^' . preg_quote(constant('DOL_URL_ROOT'), '/') . '/', '', $relativepathstring);
	}
	$relativepathstring = ltrim($relativepathstring, '/');
	$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);

	// Code for search criteria persistence.
	// Retrieve values if restore_lastsearch_values
	if (!empty($_GET['restore_lastsearch_values'])) {        // Use $_GET here and not GETPOST
		if (!empty($_SESSION['lastsearch_values_' . $relativepathstring])) {	// If there is saved values
			$tmp = json_decode($_SESSION['lastsearch_values_' . $relativepathstring], true);
			if (is_array($tmp)) {
				foreach ($tmp as $key => $val) {
					if ($key == $paramname) {	// We are on the requested parameter
						$isset = true;
						break;
					}
				}
			}
		}
		// If there is saved contextpage, limit, page or mode
		if ($paramname == 'contextpage' && !empty($_SESSION['lastsearch_contextpage_' . $relativepathstring])) {
			$isset = true;
		} elseif ($paramname == 'limit' && !empty($_SESSION['lastsearch_limit_' . $relativepathstring])) {
			$isset = true;
		} elseif ($paramname == 'page' && !empty($_SESSION['lastsearch_page_' . $relativepathstring])) {
			$isset = true;
		} elseif ($paramname == 'mode' && !empty($_SESSION['lastsearch_mode_' . $relativepathstring])) {
			$isset = true;
		}
	} else {
		$isset = (isset($_POST[$paramname]) || isset($_GET[$paramname])); // We must keep $_POST and $_GET here
	}

	return $isset;
}

/**
 * Return true if the parameter $paramname is submit from a POST OR GET as an array.
 * Can be used before GETPOST to know if the $check param of GETPOST need to check an array or a string
 *
 * @param 	string		$paramname	Name or parameter to test
 * @param	int<0,3>	$method		Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get)
 * @return 	bool 					True if we have just submit a POST or GET request with the parameter provided (even if param is empty)
 */
function GETPOSTISARRAY($paramname, $method = 0)
{
	// for $method test need return the same $val as GETPOST
	if (empty($method)) {
		$val = isset($_GET[$paramname]) ? $_GET[$paramname] : (isset($_POST[$paramname]) ? $_POST[$paramname] : '');
	} elseif ($method == 1) {
		$val = isset($_GET[$paramname]) ? $_GET[$paramname] : '';
	} elseif ($method == 2) {
		$val = isset($_POST[$paramname]) ? $_POST[$paramname] : '';
	} elseif ($method == 3) {
		$val = isset($_POST[$paramname]) ? $_POST[$paramname] : (isset($_GET[$paramname]) ? $_GET[$paramname] : '');
	} else {
		$val = 'BadFirstParameterForGETPOST';
	}

	return is_array($val);
}


/**
 *  Return the value of a $_GET or $_POST supervariable, converted into integer.
 *  Use the property $user->default_values[path]['creatform'] and/or $user->default_values[path]['filters'] and/or $user->default_values[path]['sortorder']
 *  Note: The property $user->default_values is loaded by main.php when loading the user.
 *
 *  @param  string		$paramname	Name of the $_GET or $_POST parameter
 *  @param  int<0,3>	$method		Type of method (0 = $_GET then $_POST, 1 = only $_GET, 2 = only $_POST, 3 = $_POST then $_GET)
 *  @param	int 		$nodefault	Force disable of default values set from the admin setup page
 *  @return int						Value converted into integer
 */
function GETPOSTINT($paramname, $method = 0, $nodefault = 0)
{
	return (int) GETPOST($paramname, 'int', $method, null, null, 0, $nodefault);
}

/**
 *  Return the value of a $_GET or $_POST supervariable, converted into float.
 *  Warning: This function assumes by default that the input is a number entered by end user in user format in local language (with possible thousands separator and decimal separator).
 *  If it is not the case, use the parameter $option = 1 instead.
 *
 *  @param  string          $paramname      Name of the $_GET or $_POST parameter
 *	@param	''|'MU'|'MT'|'MS'|'CU'|'CT'|int	$rounding	Type of rounding ('', 'MU', 'MT, 'MS', 'CU', 'CT', integer) {@see price2num()}
 * 	@param	int<0,2>		$option			Put 1 if you know that content is already universal format number (so no correction on decimal will be done)
 * 											Put 2 if you know that number is a user input (so we know we have to fix decimal separator).
 * 					                        Use 0 if unknown (never use this anymore, automatic detection is not reliable with some languages).
 *  @return float                           Value converted into float
 *  @since	Dolibarr V20
 */
function GETPOSTFLOAT($paramname, $rounding = '', $option = 2)
{
	// price2num() can be used to round to an expected accuracy and/or to sanitize any valid user input (such as "1 234.5", "1 234,5", "1'234,5", "1·234,5", "1,234.5", etc.)
	return (float) price2num(GETPOST($paramname), $rounding, $option);
}

/**
 * Helper function that combines values of a dolibarr DatePicker (such as Form::selectDate) for year, month, day (and
 * optionally hour, minute, second) fields to return a timestamp.
 *
 * @param 	string 		$prefix 		Prefix used to build the date selector (for instance using Form::selectDate). Example: 'select_datec'
 * @param 	string 		$hourTime		'getpost' or 'getpostend' to include hour, minute, second values from the HTTP request,
 * 										or 'XX:YY:ZZ' to set hour, minute, second respectively, for example '23:59:59'
 * 										or 'end' means '23:59:59'
 * 										or '' means '00:00:00' (default)
 * @param 	int|string 	$gm 			Passed to dol_mktime. In most cases, when used with 'getpost' or 'getpostend', it should be 'tzuserrel'. Use 'auto' if you need dates related to 'tzserver' (like in accountancy).
 * @param	string		$saverestore	Use a string family context to save retrieved date so it will be used on the next retrieval for the same family context (if value not already defined in parameters).
 * @return 	int|string  				Date as a timestamp, '' or false if error
 *
 * @see dol_mktime()
 */
function GETPOSTDATE($prefix, $hourTime = '', $gm = 'auto', $saverestore = '')
{
	$m = array();
	if ($hourTime === 'getpost' || $hourTime === 'getpostend') {
		$hour   = (GETPOSTISSET($prefix . 'hour') && GETPOSTINT($prefix . 'hour') >= 0) ? GETPOSTINT($prefix . 'hour') : ($hourTime === 'getpostend' ? 23 : 0);
		$minute = (GETPOSTISSET($prefix . 'min') && GETPOSTINT($prefix . 'min') >= 0) ? GETPOSTINT($prefix . 'min') : ($hourTime === 'getpostend' ? 59 : 0);
		$second = (GETPOSTISSET($prefix . 'sec') && GETPOSTINT($prefix . 'sec') >= 0) ? GETPOSTINT($prefix . 'sec') : ($hourTime === 'getpostend' ? 59 : 0);
	} elseif (preg_match('/^(\d\d):(\d\d):(\d\d)$/', $hourTime, $m)) {
		$hour   = intval($m[1]);
		$minute = intval($m[2]);
		$second = intval($m[3]);
	} elseif ($hourTime === 'end') {
		$hour = 23;
		$minute = 59;
		$second = 59;
	} else {
		$hour = $minute = $second = 0;
	}

	if (
		$saverestore
		&& !GETPOSTISSET($prefix . 'day')
		&& !GETPOSTISSET($prefix . 'month')
		&& !GETPOSTISSET($prefix . 'year')
		&& isset($_SESSION['DOLDATE_' . $saverestore . '_day'])
		&& isset($_SESSION['DOLDATE_' . $saverestore . '_month'])
		&& isset($_SESSION['DOLDATE_' . $saverestore . '_year'])
	) {
		$day = $_SESSION['DOLDATE_' . $saverestore . '_day'];
		$month = $_SESSION['DOLDATE_' . $saverestore . '_month'];
		$year = $_SESSION['DOLDATE_' . $saverestore . '_year'];
	} else {
		$month = GETPOSTINT($prefix . 'month');
		$day = GETPOSTINT($prefix . 'day');
		$year = GETPOSTINT($prefix . 'year');
	}

	// normalize out of range values
	$hour = (int) min($hour, 23);
	$minute = (int) min($minute, 59);
	$second = (int) min($second, 59);

	if ($saverestore) {
		$_SESSION['DOLDATE_' . $saverestore . '_day'] = $day;
		$_SESSION['DOLDATE_' . $saverestore . '_month'] = $month;
		$_SESSION['DOLDATE_' . $saverestore . '_year'] = $year;
	}

	//print "$hour, $minute, $second, $month, $day, $year, $gm<br>";
	return dol_mktime($hour, $minute, $second, $month, $day, $year, $gm);
}

/**
 *  Return value of a param into GET or POST supervariable.
 *  Use the property $user->default_values[path]['createform'] and/or $user->default_values[path]['filters'] and/or $user->default_values[path]['sortorder']
 *  Note: The property $user->default_values is loaded by main.php when loading the user.
 *
 *  @param  string  $paramname   Name of parameter to found
 *  @param 'int'|'intcomma'|'array'|'array:int'|'array:intcomma'|'array:alpha'|'array:alphanohtml'|'array:aZ09'|'array:restricthtml'|'password'|'email'|'alpha'|'alphanohtml'|'alphanohtmlquote'|'nohtml'|'restricthtml'|'alphawithlgt'|'aZ09'|'aZ'|'aZ09arobase'|'aZ09comma'|'url'|'san_alpha'|'custom'|'none'|'restricthtmlallowclass'|'restricthtmlallowunvalid'|'restricthtmlallowiframe'|'restricthtmlallowlinkscript'|'' $check Type of check
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    '' or 'none'=no check (deprecated)
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'password'=allow characters for a password
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'email'=allow characters for an email "email@domain.com"
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'url'=allow characters for an url
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'array', 'array:restricthtml' or 'array:aZ09' to check it's an array
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'int'=check it's numeric (integer or float)
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'intcomma'=check it's integer+comma ('1,2,3,4...')
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'alphanohtml'=check there is no html content and no " and no ../    ('alpha' is an alias of 'alphanohtml')
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'alphanohtmlquote'=same than 'alphanohtml' but " is replaced with ' instead of being removed
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'alphawithlgt'=alpha with lgt and no " and no ../   (Can be used for email string like "Name <email@domain.com>")
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'aZ'=check it's a-Z only
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'aZ09'=check it's simple alpha string (recommended for keys, it includes a-Z0-9_\-\.)
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'aZ09arobase'=check it's a string for an element type ('myobject@mymodule')
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'aZ09comma'=check it's a string for a sortfield or sortorder
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'san_alpha'=Use filter_var with FILTER_SANITIZE_STRING (do not use this for free text string)
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'nohtml'=check there is no html content
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'restricthtml'=check html content is restricted to some tags only
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'custom'= custom filter specify $filter and $options)
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'restricthtmlallowclass'
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'restricthtmlallowunvalid'
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'restricthtmlallowiframe'
 *                                                                                                                                                                                                                                                                                                                                                                                                                                    'restricthtmlallowlinkscript'
 *  @param	int		$method	     Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get)
 *  @param  ?int	$filter      Filter to apply when $check is set to 'custom'. (See http://php.net/manual/en/filter.filters.php for details)
 *  @param  mixed	$options     Options to pass to filter_var when $check is set to 'custom'
 *  @param	int 	$noreplace	 Force disable of replacement of __xxx__ strings.
 *  @param	int 	$nodefault	 Force disable of default values set from the admin setup page
 *  @return string|array<mixed>  Value found (string or array), or '' if check fails
 *  @phpstan-return (
 *      $check is 'int' ? numeric-string|'' :
 *      $check is 'array:int' ? numeric-string[]|array{} :
 *      $check is 'array' | 'array:aZ09' | 'array:alpha' | 'array:intcomma' | 'array:restricthtml' ? string[] :
 *      $check is 'alpha' | 'aZ' | 'aZ09' | 'aZ09arobase' | 'aZ09comma' | 'password' | 'email' | 'url' | 'alphanohtml' | 'alphanohtmlquote' |'nohtml' | 'restricthtml' | 'alphawithlgt' | 'intcomma' | 'restricthtmlallowclass' | 'restricthtmlallowunvalid' | 'restricthtmlallowiframe' | 'restricthtmlallowlinkscript' ? string : string|array<mixed>
 *  )
 */
function GETPOST($paramname, $check = 'alphanohtml', $method = 0, $filter = null, $options = null, $noreplace = 0, $nodefault = 0)
{
	global $langs, $user;

	if (empty($paramname)) {   // Explicit test for null for phan.
		return 'BadFirstParameterForGETPOST';
	}
	if (empty($check)) {
		dol_syslog("Deprecated use of GETPOST, called with 1st param = " . $paramname . " and a 2nd param that is '', when calling page " . $_SERVER["PHP_SELF"], LOG_WARNING);
		// Enable this line to know who call the GETPOST with '' $check parameter.
		//var_dump(getCallerInfoString());
	}
	if (in_array($paramname, array('sortfield', 'sortorder'))) {	// Force the $check to a more appropriated value
		$check = 'aZ09comma';
	}

	if (empty($method)) {
		$out = isset($_GET[$paramname]) ? $_GET[$paramname] : (isset($_POST[$paramname]) ? $_POST[$paramname] : '');
	} elseif ($method == 1) {
		$out = isset($_GET[$paramname]) ? $_GET[$paramname] : '';
	} elseif ($method == 2) {
		$out = isset($_POST[$paramname]) ? $_POST[$paramname] : '';
	} elseif ($method == 3) {
		$out = isset($_POST[$paramname]) ? $_POST[$paramname] : (isset($_GET[$paramname]) ? $_GET[$paramname] : '');
	} else {
		return 'BadThirdParameterForGETPOST';
	}

	$relativepathstring = ''; // For static analysis - looks possibly undefined if not set.

	if (empty($method) || $method == 3 || $method == 4) {
		$relativepathstring = (empty($_SERVER["PHP_SELF"]) ? '' : $_SERVER["PHP_SELF"]);
		// Clean $relativepathstring
		if (constant('DOL_URL_ROOT')) {
			$relativepathstring = preg_replace('/^' . preg_quote(constant('DOL_URL_ROOT'), '/') . '/', '', $relativepathstring);
		}
		$relativepathstring = ltrim($relativepathstring, '/');
		$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);

		// Code for search criteria persistence.
		// Retrieve saved values if restore_lastsearch_values is set
		if (!empty($_GET['restore_lastsearch_values'])) {        // Use $_GET here and not GETPOST
			if (!empty($_SESSION['lastsearch_values_' . $relativepathstring])) {	// If there is saved values
				$tmp = json_decode($_SESSION['lastsearch_values_' . $relativepathstring], true);
				if (is_array($tmp)) {
					foreach ($tmp as $key => $val) {
						if ($key == $paramname) {	// We are on the requested parameter
							$out = $val;
							break;
						}
					}
				}
			}
			// If there is saved contextpage, page or limit
			if ($paramname == 'contextpage' && !empty($_SESSION['lastsearch_contextpage_' . $relativepathstring])) {
				$out = $_SESSION['lastsearch_contextpage_' . $relativepathstring];
			} elseif ($paramname == 'limit' && !empty($_SESSION['lastsearch_limit_' . $relativepathstring])) {
				$out = $_SESSION['lastsearch_limit_' . $relativepathstring];
			} elseif ($paramname == 'page' && !empty($_SESSION['lastsearch_page_' . $relativepathstring])) {
				$out = $_SESSION['lastsearch_page_' . $relativepathstring];
			} elseif ($paramname == 'mode' && !empty($_SESSION['lastsearch_mode_' . $relativepathstring])) {
				$out = $_SESSION['lastsearch_mode_' . $relativepathstring];
			}
		} elseif (!isset($_GET['sortfield'])) {
			// Else, retrieve default values if we are not doing a sort
			// If we did a click on a field to sort, we do no apply default values. Same if option MAIN_ENABLE_DEFAULT_VALUES is not set
			if (!empty($_GET['action']) && $_GET['action'] == 'create' && !isset($_GET[$paramname]) && !isset($_POST[$paramname])) {
				// Search default value from $object->field
				global $object;
				'@phan-var-force CommonObject $object'; // Suppose it's a CommonObject for analysis, but other objects have the $fields field as well
				if (is_object($object) && isset($object->fields[$paramname]['default'])) {
					// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
					$out = $object->fields[$paramname]['default'];
				}
			}
			if (getDolGlobalString('MAIN_ENABLE_DEFAULT_VALUES')) {
				if (!empty($_GET['action']) && (preg_match('/^create/', $_GET['action']) || preg_match('/^presend/', $_GET['action'])) && !isset($_GET[$paramname]) && !isset($_POST[$paramname])) {
					// Now search in setup to overwrite default values
					if (!empty($user->default_values)) {		// $user->default_values defined from menu 'Setup - Default values'
						if (isset($user->default_values[$relativepathstring]['createform'])) {
							foreach ($user->default_values[$relativepathstring]['createform'] as $defkey => $defval) {
								$qualified = 0;
								if ($defkey != '_noquery_') {
									$tmpqueryarraytohave = explode('&', $defkey);
									$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
									$foundintru = 0;
									foreach ($tmpqueryarraytohave as $tmpquerytohave) {
										if (!in_array($tmpquerytohave, $tmpqueryarraywehave)) {
											$foundintru = 1;
										}
									}
									if (!$foundintru) {
										$qualified = 1;
									}
								} else {
									$qualified = 1;
								}

								if ($qualified) {
									if (isset($user->default_values[$relativepathstring]['createform'][$defkey][$paramname])) {
										$out = $user->default_values[$relativepathstring]['createform'][$defkey][$paramname];
										break;
									}
								}
							}
						}
					}
				} elseif (!empty($paramname) && !isset($_GET[$paramname]) && !isset($_POST[$paramname]) && empty($nodefault)) {
					// Management of default search_filters and sort order
					if (!empty($user->default_values)) {
						// $user->default_values defined from menu 'Setup - Default values'
						//var_dump($user->default_values[$relativepathstring]);
						if ($paramname == 'sortfield' || $paramname == 'sortorder') {
							// Sorted on which fields ? ASC or DESC ?
							if (isset($user->default_values[$relativepathstring]['sortorder'])) {
								// Even if paramname is sortfield, data are stored into ['sortorder...']
								foreach ($user->default_values[$relativepathstring]['sortorder'] as $defkey => $defval) {
									$qualified = 0;
									if ($defkey != '_noquery_') {
										$tmpqueryarraytohave = explode('&', $defkey);
										$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
										$foundintru = 0;
										foreach ($tmpqueryarraytohave as $tmpquerytohave) {
											if (!in_array($tmpquerytohave, $tmpqueryarraywehave)) {
												$foundintru = 1;
											}
										}
										if (!$foundintru) {
											$qualified = 1;
										}
									} else {
										$qualified = 1;
									}

									if ($qualified) {
										$forbidden_chars_to_replace = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ";", "="); // we accept _, -, . and ,
										foreach ($user->default_values[$relativepathstring]['sortorder'][$defkey] as $key => $val) {
											if ($out) {
												$out .= ', ';
											}
											if ($paramname == 'sortfield') {
												$out .= dol_string_nospecial($key, '', $forbidden_chars_to_replace);
											}
											if ($paramname == 'sortorder') {
												$out .= dol_string_nospecial($val, '', $forbidden_chars_to_replace);
											}
										}
										//break;	// No break for sortfield and sortorder so we can cumulate fields (is it really useful ?)
									}
								}
							}
						} elseif (isset($user->default_values[$relativepathstring]['filters'])) {
							foreach ($user->default_values[$relativepathstring]['filters'] as $defkey => $defval) {	// $defkey is a querystring like 'a=b&c=d', $defval is key of user
								if (!empty($_GET['disabledefaultvalues'])) {	// If set of default values has been disabled by a request parameter
									continue;
								}
								$qualified = 0;
								if ($defkey != '_noquery_') {
									$tmpqueryarraytohave = explode('&', $defkey);
									$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
									$foundintru = 0;
									foreach ($tmpqueryarraytohave as $tmpquerytohave) {
										if (!in_array($tmpquerytohave, $tmpqueryarraywehave)) {
											$foundintru = 1;
										}
									}
									if (!$foundintru) {
										$qualified = 1;
									}
								} else {
									$qualified = 1;
								}

								if ($qualified && isset($user->default_values[$relativepathstring]['filters'][$defkey][$paramname])) {
									// We must keep $_POST and $_GET here
									if (isset($_POST['search_all']) || isset($_GET['search_all'])) {
										// We made a search from quick search menu, do we still use default filter ?
										if (!getDolGlobalString('MAIN_DISABLE_DEFAULT_FILTER_FOR_QUICK_SEARCH')) {
											$forbidden_chars_to_replace = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ";", "="); // we accept _, -, . and ,
											$out = dol_string_nospecial($user->default_values[$relativepathstring]['filters'][$defkey][$paramname], '', $forbidden_chars_to_replace);
										}
									} else {
										$forbidden_chars_to_replace = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ";", "="); // we accept _, -, . and ,
										$out = dol_string_nospecial($user->default_values[$relativepathstring]['filters'][$defkey][$paramname], '', $forbidden_chars_to_replace);
									}
									break;
								}
							}
						}
					}
				}
			}
		}
	}

	// Replace substitution variables for GETPOST (used to get final url with variable parameters or final default value, when using variable parameters __XXX__ in the GET URL)
	// Example of variables: __DAY__, __MONTH__, __YEAR__, __MYCOMPANY_COUNTRY_ID__, __USER_ID__, ...
	// We do this only if var is a GET. If it is a POST, may be we want to post the text with vars as the setup text.
	'@phan-var-force string $paramname';
	if (!is_array($out) && empty($_POST[$paramname]) && empty($noreplace)) {
		if (preg_match('/__([A-Z0-9]+(?:_[A-Z0-9]+){0,3})__/i', $out)) {	// If there is at least one substitution key, we try to replace all known substitution keys
			$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $user, array('mycompany', 'date', 'system', 'user'));
			complete_substitutions_array($substitutionarray, $langs, $user);

			$out = make_substitutions($out, $substitutionarray, $langs);
		}
	}

	// Check type of variable and make sanitization according to this
	if (preg_match('/^array/', $check)) {	// If 'array' or 'array:restricthtml' or 'array:aZ09' or 'array:int'
		$tmpcheck = 'alphanohtml';
		if ($out === null || $out === '') {
			$out = array();
		} elseif (!is_array($out)) {
			$out = explode(',', $out);
		} else {
			$tmparray = explode(':', $check);
			if (!empty($tmparray[1])) {
				$tmpcheck = $tmparray[1];
			}
		}
		foreach ($out as $outkey => $outval) {
			$out[$outkey] = sanitizeVal($outval, $tmpcheck, $filter, $options);
		}
	} else {
		// If field name is 'search_xxx' then we force the add of space after each < and > (when following char is numeric) because it means
		// we use the < or > to make a search on a numeric value to do higher or lower so we can add a space to break html tags
		if (strpos($paramname, 'search_') === 0) {
			$out = preg_replace('/([<>])([-+]?\d)/', '\1 \2', $out);
		}

		// @phan-suppress-next-line UnknownSanitizeType
		$out = sanitizeVal($out, $check, $filter, $options);
	}

	// Sanitizing for special parameters.
	// Note: There is no reason to allow the backtopage/backtopageforcancel/backtopagejs, backtolist or backtourl parameter to contains an external URL. Only relative URLs are allowed.
	// @TODO Merge backtopage with backtourl
	// @TODO Rename backtolist into backtopagelist
	// @TODO Merge urlfrom into backtourl
	if (preg_match('/^backto/i', $paramname) || preg_match('/^urlfrom/i', $paramname)) {
		$out = str_replace('\\', '/', $out);								// Can be before the loop because only 1 char is replaced. No risk to get it after other replacements.
		$out = str_replace(array(':', ';', '@', "\t", ' '), '', $out);		// Can be before the loop because only 1 char is replaced. No risk to retrieve it after other replacements.
		do {
			$oldstringtoclean = $out;
