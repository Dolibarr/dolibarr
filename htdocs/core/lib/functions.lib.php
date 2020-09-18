<?php
/* Copyright (C) 2000-2007	Rodolphe Quiedeville			<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Christophe Combelles			<ccomb@free.fr>
 * Copyright (C) 2005-2019	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2018	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2017	Alexandre Spangaro			<aspangaro@open-dsi.fr>
 * Copyright (C) 2014		Cédric GROSS					<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2018-2019  Frédéric France             <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Thibault Foucart            <support@ptibogxiv.net>
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

include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';


/**
 * Return a DoliDB instance (database handler).
 *
 * @param   string	$type		Type of database (mysql, pgsql...)
 * @param	string	$host		Address of database server
 * @param	string	$user		Authorized username
 * @param	string	$pass		Password
 * @param	string	$name		Name of database
 * @param	int		$port		Port of database server
 * @return	DoliDB				A DoliDB instance
 */
function getDoliDBInstance($type, $host, $user, $pass, $name, $port)
{
	require_once DOL_DOCUMENT_ROOT."/core/db/".$type.'.class.php';

	$class = 'DoliDB'.ucfirst($type);
	$dolidb = new $class($type, $host, $user, $pass, $name, $port);
	return $dolidb;
}

/**
 * 	Get list of entity id to use.
 *
 * 	@param	string	$element		Current element
 *									'societe', 'socpeople', 'actioncomm', 'agenda', 'resource',
 *									'product', 'productprice', 'stock',
 *									'propal', 'supplier_proposal', 'invoice', 'facture_fourn', 'payment_various',
 *									'categorie', 'bank_account', 'bank_account', 'adherent', 'user',
 *									'commande', 'commande_fournisseur', 'expedition', 'intervention', 'survey',
 *									'contract', 'tax', 'expensereport', 'holiday', 'multicurrency', 'project',
 *									'email_template', 'event', 'donation'
 *									'c_paiement', 'c_payment_term', ...
 * 	@param	int		$shared			0=Return id of current entity only,
 * 									1=Return id of current entity + shared entities (default)
 *  @param	object	$currentobject	Current object if needed
 * 	@return	mixed					Entity id(s) to use ( eg. entity IN ('.getEntity(elementname).')' )
 */
function getEntity($element, $shared = 1, $currentobject = null)
{
	global $conf, $mc;

	if (is_object($mc))
	{
		return $mc->getEntity($element, $shared, $currentobject);
	}
	else
	{
		$out = '';
		$addzero = array('user', 'usergroup', 'c_email_templates', 'email_template', 'default_values');
		if (in_array($element, $addzero)) $out .= '0,';
		$out .= $conf->entity;
		return $out;
	}
}

/**
 * 	Set entity id to use when to create an object
 *
 * 	@param	object	$currentobject	Current object
 * 	@return	mixed					Entity id to use ( eg. entity = '.setEntity($object) )
 */
function setEntity($currentobject)
{
	global $conf, $mc;

	if (is_object($mc) && method_exists($mc, 'setEntity'))
	{
		return $mc->setEntity($currentobject);
	}
	else
	{
		return ((is_object($currentobject) && $currentobject->id > 0 && $currentobject->entity > 0) ? $currentobject->entity : $conf->entity);
	}
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
 * @return	array Check function documentation
 */
function getBrowserInfo($user_agent)
{
	include_once DOL_DOCUMENT_ROOT.'/includes/mobiledetect/mobiledetectlib/Mobile_Detect.php';

	$name = 'unknown';
	$version = '';
	$os = 'unknown';
	$phone = '';

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
	if (preg_match('/linux/i', $user_agent)) { $os = 'linux'; }
	elseif (preg_match('/macintosh/i', $user_agent)) { $os = 'macintosh'; }
	elseif (preg_match('/windows/i', $user_agent)) { $os = 'windows'; }

	// Name
	if (preg_match('/firefox(\/|\s)([\d\.]*)/i', $user_agent, $reg)) { $name = 'firefox'; $version = $reg[2]; }
	elseif (preg_match('/edge(\/|\s)([\d\.]*)/i', $user_agent, $reg)) { $name = 'edge'; $version = $reg[2]; }
	elseif (preg_match('/chrome(\/|\s)([\d\.]+)/i', $user_agent, $reg)) { $name = 'chrome'; $version = $reg[2]; }    // we can have 'chrome (Mozilla...) chrome x.y' in one string
	elseif (preg_match('/chrome/i', $user_agent, $reg)) { $name = 'chrome'; }
	elseif (preg_match('/iceweasel/i', $user_agent)) { $name = 'iceweasel'; }
	elseif (preg_match('/epiphany/i', $user_agent)) { $name = 'epiphany'; }
	elseif (preg_match('/safari(\/|\s)([\d\.]*)/i', $user_agent, $reg)) { $name = 'safari'; $version = $reg[2]; }	// Safari is often present in string for mobile but its not.
	elseif (preg_match('/opera(\/|\s)([\d\.]*)/i', $user_agent, $reg)) { $name = 'opera'; $version = $reg[2]; }
	elseif (preg_match('/(MSIE\s([0-9]+\.[0-9]))|.*(Trident\/[0-9]+.[0-9];.*rv:([0-9]+\.[0-9]+))/i', $user_agent, $reg)) { $name = 'ie'; $version = end($reg); }    // MS products at end
	elseif (preg_match('/(Windows NT\s([0-9]+\.[0-9])).*(Trident\/[0-9]+.[0-9];.*rv:([0-9]+\.[0-9]+))/i', $user_agent, $reg)) { $name = 'ie'; $version = end($reg); }    // MS products at end
	elseif (preg_match('/l(i|y)n(x|ks)(\(|\/|\s)*([\d\.]+)/i', $user_agent, $reg)) { $name = 'lynxlinks'; $version = $reg[4]; }

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
		'layout' => $layout,
		'phone' => $phone,
		'tablet' => $tablet
	);
}

/**
 *  Function called at end of web php process
 *
 *  @return	void
 */
function dol_shutdown()
{
	global $conf, $user, $langs, $db;
	$disconnectdone = false; $depth = 0;
	if (is_object($db) && !empty($db->connected)) { $depth = $db->transaction_opened; $disconnectdone = $db->close(); }
	dol_syslog("--- End access to ".$_SERVER["PHP_SELF"].(($disconnectdone && $depth) ? ' (Warn: db disconnection forced, transaction depth was '.$depth.')' : ''), (($disconnectdone && $depth) ?LOG_WARNING:LOG_INFO));
}

/**
 * Return true if we are in a context of submitting a parameter
 *
 * @param 	string	$paramname		Name or parameter to test
 * @return 	boolean					True if we have just submit a POST or GET request with the parameter provided (even if param is empty)
 */
function GETPOSTISSET($paramname)
{
	$isset = 0;

	$relativepathstring = $_SERVER["PHP_SELF"];
	// Clean $relativepathstring
	if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
	$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
	$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
	//var_dump($relativepathstring);
	//var_dump($user->default_values);

	// Code for search criteria persistence.
	// Retrieve values if restore_lastsearch_values
	if (!empty($_GET['restore_lastsearch_values']))        // Use $_GET here and not GETPOST
	{
		if (!empty($_SESSION['lastsearch_values_'.$relativepathstring]))	// If there is saved values
		{
			$tmp = json_decode($_SESSION['lastsearch_values_'.$relativepathstring], true);
			if (is_array($tmp))
			{
				foreach ($tmp as $key => $val)
				{
					if ($key == $paramname)	// We are on the requested parameter
					{
						$isset = 1;
						break;
					}
				}
			}
		}
		// If there is saved contextpage, page or limit
		if ($paramname == 'contextpage' && !empty($_SESSION['lastsearch_contextpage_'.$relativepathstring]))
		{
			$isset = 1;
		}
		elseif ($paramname == 'page' && !empty($_SESSION['lastsearch_page_'.$relativepathstring]))
		{
			$isset = 1;
		}
		elseif ($paramname == 'limit' && !empty($_SESSION['lastsearch_limit_'.$relativepathstring]))
		{
			$isset = 1;
		}
	}
	else {
		$isset = (isset($_POST[$paramname]) || isset($_GET[$paramname]));
	}

	return $isset;
}

/**
 *  Return value of a param into GET or POST supervariable.
 *  Use the property $user->default_values[path]['creatform'] and/or $user->default_values[path]['filters'] and/or $user->default_values[path]['sortorder']
 *  Note: The property $user->default_values is loaded by main.php when loading the user.
 *
 *  @param  string  $paramname   Name of parameter to found
 *  @param  string  $check	     Type of check
 *                               ''=no check (deprecated)
 *                               'none'=no check (only for param that should have very rich content)
 *                               'int'=check it's numeric (integer or float)
 *                               'intcomma'=check it's integer+comma ('1,2,3,4...')
 *                               'alpha'=check it's text and sign
 *                               'aZ'=check it's a-z only
 *                               'aZ09'=check it's simple alpha string (recommended for keys)
 *                               'array'=check it's array
 *                               'san_alpha'=Use filter_var with FILTER_SANITIZE_STRING (do not use this for free text string)
 *                               'nohtml', 'alphanohtml'=check there is no html content
 *                               'custom'= custom filter specify $filter and $options)
 *  @param	int		$method	     Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get)
 *  @param  int     $filter      Filter to apply when $check is set to 'custom'. (See http://php.net/manual/en/filter.filters.php for détails)
 *  @param  mixed   $options     Options to pass to filter_var when $check is set to 'custom'
 *  @param	string	$noreplace	 Force disable of replacement of __xxx__ strings.
 *  @return string|string[]      Value found (string or array), or '' if check fails
 */
function GETPOST($paramname, $check = 'none', $method = 0, $filter = null, $options = null, $noreplace = 0)
{
	global $mysoc, $user, $conf;

	if (empty($paramname)) return 'BadFirstParameterForGETPOST';
	if (empty($check))
	{
		dol_syslog("Deprecated use of GETPOST, called with 1st param = ".$paramname." and 2nd param is '', when calling page ".$_SERVER["PHP_SELF"], LOG_WARNING);
		// Enable this line to know who call the GETPOST with '' $check parameter.
		//var_dump(debug_backtrace()[0]);
	}

	if (empty($method)) $out = isset($_GET[$paramname]) ? $_GET[$paramname] : (isset($_POST[$paramname]) ? $_POST[$paramname] : '');
	elseif ($method == 1) $out = isset($_GET[$paramname]) ? $_GET[$paramname] : '';
	elseif ($method == 2) $out = isset($_POST[$paramname]) ? $_POST[$paramname] : '';
	elseif ($method == 3) $out = isset($_POST[$paramname]) ? $_POST[$paramname] : (isset($_GET[$paramname]) ? $_GET[$paramname] : '');
	else return 'BadThirdParameterForGETPOST';

	if (empty($method) || $method == 3 || $method == 4)
	{
		$relativepathstring = $_SERVER["PHP_SELF"];
		// Clean $relativepathstring
		if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
		$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
		$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
		//var_dump($relativepathstring);
		//var_dump($user->default_values);

		// Code for search criteria persistence.
		// Retrieve values if restore_lastsearch_values
		if (!empty($_GET['restore_lastsearch_values']))        // Use $_GET here and not GETPOST
		{
			if (!empty($_SESSION['lastsearch_values_'.$relativepathstring]))	// If there is saved values
			{
				$tmp = json_decode($_SESSION['lastsearch_values_'.$relativepathstring], true);
				if (is_array($tmp))
				{
					foreach ($tmp as $key => $val)
					{
						if ($key == $paramname)	// We are on the requested parameter
						{
							$out = $val;
							break;
						}
					}
				}
			}
			// If there is saved contextpage, page or limit
			if ($paramname == 'contextpage' && !empty($_SESSION['lastsearch_contextpage_'.$relativepathstring]))
			{
				$out = $_SESSION['lastsearch_contextpage_'.$relativepathstring];
			}
			elseif ($paramname == 'page' && !empty($_SESSION['lastsearch_page_'.$relativepathstring]))
			{
				$out = $_SESSION['lastsearch_page_'.$relativepathstring];
			}
			elseif ($paramname == 'limit' && !empty($_SESSION['lastsearch_limit_'.$relativepathstring]))
			{
				$out = $_SESSION['lastsearch_limit_'.$relativepathstring];
			}
		}
		// Else, retreive default values if we are not doing a sort
		elseif (!isset($_GET['sortfield']))	// If we did a click on a field to sort, we do no apply default values. Same if option MAIN_ENABLE_DEFAULT_VALUES is not set
		{
			if (!empty($_GET['action']) && $_GET['action'] == 'create' && !isset($_GET[$paramname]) && !isset($_POST[$paramname]))
			{
				// Search default value from $object->field
				global $object;
				if (is_object($object) && isset($object->fields[$paramname]['default']))
				{
					$out = $object->fields[$paramname]['default'];
				}
			}
			if (!empty($conf->global->MAIN_ENABLE_DEFAULT_VALUES))
			{
			    if (!empty($_GET['action']) && (preg_match('/^create/', $_GET['action']) || preg_match('/^presend/', $_GET['action'])) && !isset($_GET[$paramname]) && !isset($_POST[$paramname]))
				{
					// Now search in setup to overwrite default values
					if (!empty($user->default_values))		// $user->default_values defined from menu 'Setup - Default values'
					{
						if (isset($user->default_values[$relativepathstring]['createform']))
						{
							foreach ($user->default_values[$relativepathstring]['createform'] as $defkey => $defval)
							{
								$qualified = 0;
								if ($defkey != '_noquery_')
								{
									$tmpqueryarraytohave = explode('&', $defkey);
									$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
									$foundintru = 0;
									foreach ($tmpqueryarraytohave as $tmpquerytohave)
									{
										if (!in_array($tmpquerytohave, $tmpqueryarraywehave)) $foundintru = 1;
									}
									if (!$foundintru) $qualified = 1;
									//var_dump($defkey.'-'.$qualified);
								}
								else $qualified = 1;

								if ($qualified)
								{
									//var_dump($user->default_values[$relativepathstring][$defkey]['createform']);
									if (isset($user->default_values[$relativepathstring]['createform'][$defkey][$paramname]))
									{
										$out = $user->default_values[$relativepathstring]['createform'][$defkey][$paramname];
										break;
									}
								}
							}
						}
					}
				}
				// Management of default search_filters and sort order
				//elseif (preg_match('/list.php$/', $_SERVER["PHP_SELF"]) && ! empty($paramname) && ! isset($_GET[$paramname]) && ! isset($_POST[$paramname]))
				elseif (!empty($paramname) && !isset($_GET[$paramname]) && !isset($_POST[$paramname]))
				{
					if (!empty($user->default_values))		// $user->default_values defined from menu 'Setup - Default values'
					{
						//var_dump($user->default_values[$relativepathstring]);
						if ($paramname == 'sortfield' || $paramname == 'sortorder')			// Sorted on which fields ? ASC or DESC ?
						{
							if (isset($user->default_values[$relativepathstring]['sortorder']))	// Even if paramname is sortfield, data are stored into ['sortorder...']
							{
								foreach ($user->default_values[$relativepathstring]['sortorder'] as $defkey => $defval)
								{
									$qualified = 0;
									if ($defkey != '_noquery_')
									{
										$tmpqueryarraytohave = explode('&', $defkey);
										$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
										$foundintru = 0;
										foreach ($tmpqueryarraytohave as $tmpquerytohave)
										{
											if (!in_array($tmpquerytohave, $tmpqueryarraywehave)) $foundintru = 1;
										}
										if (!$foundintru) $qualified = 1;
										//var_dump($defkey.'-'.$qualified);
									}
									else $qualified = 1;

									if ($qualified)
									{
										$forbidden_chars_to_replace = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ";", "="); // we accept _, -, . and ,
										foreach ($user->default_values[$relativepathstring]['sortorder'][$defkey] as $key => $val)
										{
											if ($out) $out .= ', ';
											if ($paramname == 'sortfield')
											{
												$out .= dol_string_nospecial($key, '', $forbidden_chars_to_replace);
											}
											if ($paramname == 'sortorder')
											{
												$out .= dol_string_nospecial($val, '', $forbidden_chars_to_replace);
											}
										}
										//break;	// No break for sortfield and sortorder so we can cumulate fields (is it realy usefull ?)
									}
								}
							}
						}
						elseif (isset($user->default_values[$relativepathstring]['filters']))
						{
							foreach ($user->default_values[$relativepathstring]['filters'] as $defkey => $defval)	// $defkey is a querystring like 'a=b&c=d', $defval is key of user
							{
								$qualified = 0;
								if ($defkey != '_noquery_')
								{
									$tmpqueryarraytohave = explode('&', $defkey);
									$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
									$foundintru = 0;
									foreach ($tmpqueryarraytohave as $tmpquerytohave)
									{
										if (!in_array($tmpquerytohave, $tmpqueryarraywehave)) $foundintru = 1;
									}
									if (!$foundintru) $qualified = 1;
									//var_dump($defkey.'-'.$qualified);
								}
								else $qualified = 1;

								if ($qualified)
								{
									if (isset($_POST['sall']) || isset($_POST['search_all']) || isset($_GET['sall']) || isset($_GET['search_all']))
									{
										// We made a search from quick search menu, do we still use default filter ?
										if (empty($conf->global->MAIN_DISABLE_DEFAULT_FILTER_FOR_QUICK_SEARCH))
										{
											$forbidden_chars_to_replace = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ";", "="); // we accept _, -, . and ,
											$out = dol_string_nospecial($user->default_values[$relativepathstring]['filters'][$defkey][$paramname], '', $forbidden_chars_to_replace);
										}
									}
									else
									{
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

	// Substitution variables for GETPOST (used to get final url with variable parameters or final default value with variable parameters)
	// Example of variables: __DAY__, __MONTH__, __YEAR__, __MYCOMPANY_COUNTRY_ID__, __USER_ID__, ...
	// We do this only if var is a GET. If it is a POST, may be we want to post the text with vars as the setup text.
	if (!is_array($out) && empty($_POST[$paramname]) && empty($noreplace))
	{
		$reg = array();
		$maxloop = 20; $loopnb = 0; // Protection against infinite loop
		while (preg_match('/__([A-Z0-9]+_?[A-Z0-9]+)__/i', $out, $reg) && ($loopnb < $maxloop))    // Detect '__ABCDEF__' as key 'ABCDEF' and '__ABC_DEF__' as key 'ABC_DEF'. Detection is also correct when 2 vars are side by side.
		{
			$loopnb++; $newout = '';

			if ($reg[1] == 'DAY') { $tmp = dol_getdate(dol_now(), true); $newout = $tmp['mday']; }
			elseif ($reg[1] == 'MONTH') { $tmp = dol_getdate(dol_now(), true); $newout = $tmp['mon']; }
			elseif ($reg[1] == 'YEAR') { $tmp = dol_getdate(dol_now(), true); $newout = $tmp['year']; }
			elseif ($reg[1] == 'PREVIOUS_DAY') { $tmp = dol_getdate(dol_now(), true); $tmp2 = dol_get_prev_day($tmp['mday'], $tmp['mon'], $tmp['year']); $newout = $tmp2['day']; }
			elseif ($reg[1] == 'PREVIOUS_MONTH') { $tmp = dol_getdate(dol_now(), true); $tmp2 = dol_get_prev_month($tmp['mon'], $tmp['year']); $newout = $tmp2['month']; }
			elseif ($reg[1] == 'PREVIOUS_YEAR') { $tmp = dol_getdate(dol_now(), true); $newout = ($tmp['year'] - 1); }
			elseif ($reg[1] == 'NEXT_DAY') { $tmp = dol_getdate(dol_now(), true); $tmp2 = dol_get_next_day($tmp['mday'], $tmp['mon'], $tmp['year']); $newout = $tmp2['day']; }
			elseif ($reg[1] == 'NEXT_MONTH') { $tmp = dol_getdate(dol_now(), true); $tmp2 = dol_get_next_month($tmp['mon'], $tmp['year']); $newout = $tmp2['month']; }
			elseif ($reg[1] == 'NEXT_YEAR') { $tmp = dol_getdate(dol_now(), true); $newout = ($tmp['year'] + 1); }
			elseif ($reg[1] == 'MYCOMPANY_COUNTRY_ID' || $reg[1] == 'MYCOUNTRY_ID' || $reg[1] == 'MYCOUNTRYID')
			{
				$newout = $mysoc->country_id;
			}
			elseif ($reg[1] == 'USER_ID' || $reg[1] == 'USERID')
			{
				$newout = $user->id;
			}
			elseif ($reg[1] == 'USER_SUPERVISOR_ID' || $reg[1] == 'SUPERVISOR_ID' || $reg[1] == 'SUPERVISORID')
			{
				$newout = $user->fk_user;
			}
			elseif ($reg[1] == 'ENTITY_ID' || $reg[1] == 'ENTITYID')
			{
				$newout = $conf->entity;
			}
			else $newout = ''; // Key not found, we replace with empty string
			//var_dump('__'.$reg[1].'__ -> '.$newout);
			$out = preg_replace('/__'.preg_quote($reg[1], '/').'__/', $newout, $out);
		}
	}

	// Check is done after replacement
	switch ($check)
	{
		case 'none':
			break;
		case 'int':    // Check param is a numeric value (integer but also float or hexadecimal)
			if (!is_numeric($out)) { $out = ''; }
			break;
		case 'intcomma':
			if (preg_match('/[^0-9,-]+/i', $out)) $out = '';
			break;
		case 'alpha':
			if (!is_array($out))
			{
				$out = trim($out);
				// '"' is dangerous because param in url can close the href= or src= and add javascript functions.
				// '../' is dangerous because it allows dir transversals
				if (preg_match('/"/', $out)) $out = '';
				elseif (preg_match('/\.\.\//', $out)) $out = '';
			}
			break;
		case 'san_alpha':
			$out = filter_var($out, FILTER_SANITIZE_STRING);
			break;
		case 'aZ':
			if (!is_array($out))
			{
				$out = trim($out);
				if (preg_match('/[^a-z]+/i', $out)) $out = '';
			}
			break;
		case 'aZ09':
			if (!is_array($out))
			{
				$out = trim($out);
				if (preg_match('/[^a-z0-9_\-\.]+/i', $out)) $out = '';
			}
			break;
		case 'aZ09comma':		// great to sanitize sortfield or sortorder params that can be t.abc,t.def_gh
			if (!is_array($out))
			{
				$out = trim($out);
				if (preg_match('/[^a-z0-9_\-\.,]+/i', $out)) $out = '';
			}
			break;
		case 'array':
			if (!is_array($out) || empty($out)) $out = array();
			break;
		case 'nohtml':		// Recommended for most scalar parameters
			$out = dol_string_nohtmltag($out, 0);
			break;
		case 'alphanohtml':	// Recommended for search parameters
			if (!is_array($out))
			{
				$out = trim($out);
				// '"' is dangerous because param in url can close the href= or src= and add javascript functions.
				// '../' is dangerous because it allows dir transversals
				if (preg_match('/"/', $out)) $out = '';
				elseif (preg_match('/\.\.\//', $out)) $out = '';
				$out = dol_string_nohtmltag($out);
			}
			break;
		case 'restricthtml':		// Recommended for most html textarea
			$out = dol_string_onlythesehtmltags($out, 0);
			break;
		case 'custom':
			if (empty($filter)) return 'BadFourthParameterForGETPOST';
			$out = filter_var($out, $filter, $options);
			break;
	}

	// Code for search criteria persistence.
	// Save data into session if key start with 'search_' or is 'smonth', 'syear', 'month', 'year'
	if (empty($method) || $method == 3 || $method == 4)
	{
		if (preg_match('/^search_/', $paramname) || in_array($paramname, array('sortorder', 'sortfield')))
		{
			//var_dump($paramname.' - '.$out.' '.$user->default_values[$relativepathstring]['filters'][$paramname]);

			// We save search key only if $out not empty that means:
			// - posted value not empty, or
			// - if posted value is empty and a default value exists that is not empty (it means we did a filter to an empty value when default was not).

			if ($out != '')		// $out = '0' or 'abc', it is a search criteria to keep
			{
				$user->lastsearch_values_tmp[$relativepathstring][$paramname] = $out;
			}
		}
	}

	return $out;
}


if (!function_exists('dol_getprefix'))
{
    /**
     *  Return a prefix to use for this Dolibarr instance, for session/cookie names or email id.
     *  The prefix is unique for instance and avoid conflict between multi-instances, even when having two instances with same root dir
     *  or two instances in same virtual servers.
     *
     *  @param  string  $mode                   '' (prefix for session name) or 'email' (prefix for email id)
     *  @return	string                          A calculated prefix
     */
    function dol_getprefix($mode = '')
    {
        global $conf;

		// If prefix is for email
		if ($mode == 'email')
		{
		    if (!empty($conf->global->MAIL_PREFIX_FOR_EMAIL_ID))	// If MAIL_PREFIX_FOR_EMAIL_ID is set (a value initialized with a random value is recommended)
			{
				if ($conf->global->MAIL_PREFIX_FOR_EMAIL_ID != 'SERVER_NAME') return $conf->global->MAIL_PREFIX_FOR_EMAIL_ID;
				elseif (isset($_SERVER["SERVER_NAME"])) return $_SERVER["SERVER_NAME"];
			}

			// The recommended value (may be not defined for old versions)
			if (!empty($conf->file->instance_unique_id)) return $conf->file->instance_unique_id;

			// For backward compatibility
			return dol_hash(DOL_DOCUMENT_ROOT.DOL_URL_ROOT, '3');
		}

		// The recommended value (may be not defined for old versions)
		if (!empty($conf->file->instance_unique_id)) return $conf->file->instance_unique_id;

		// For backward compatibility
		if (isset($_SERVER["SERVER_NAME"]) && isset($_SERVER["DOCUMENT_ROOT"]))
		{
			return dol_hash($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT, '3');
		}

		return dol_hash(DOL_DOCUMENT_ROOT.DOL_URL_ROOT, '3');
	}
}

/**
 *	Make an include_once using default root and alternate root if it fails.
 *  To link to a core file, use include(DOL_DOCUMENT_ROOT.'/pathtofile')
 *  To link to a module file from a module file, use include './mymodulefile';
 *  To link to a module file from a core file, then this function can be used (call by hook / trigger / speciales pages)
 *
 * 	@param	string	$relpath	Relative path to file (Ie: mydir/myfile, ../myfile, ...)
 * 	@param	string	$classname	Class name (deprecated)
 *  @return bool                True if load is a success, False if it fails
 */
function dol_include_once($relpath, $classname = '')
{
	global $conf, $langs, $user, $mysoc; // Do not remove this. They must be defined for files we include. Other globals var must be retreived with $GLOBALS['var']

	$fullpath = dol_buildpath($relpath);

	if (!file_exists($fullpath)) {
		dol_syslog('functions::dol_include_once Tried to load unexisting file: '.$relpath, LOG_WARNING);
		return false;
	}

	if (!empty($classname) && !class_exists($classname)) {
		return include $fullpath;
	} else {
		return include_once $fullpath;
	}
}


/**
 *	Return path of url or filesystem. Can check into alternate dir or alternate dir + main dir depending on value of $returnemptyifnotfound.
 *
 * 	@param	string	$path						Relative path to file (if mode=0) or relative url (if mode=1). Ie: mydir/myfile, ../myfile
 *  @param	int		$type						0=Used for a Filesystem path, 1=Used for an URL path (output relative), 2=Used for an URL path (output full path using same host that current url), 3=Used for an URL path (output full path using host defined into $dolibarr_main_url_root of conf file)
 *  @param	int		$returnemptyifnotfound		0:If $type==0 and if file was not found into alternate dir, return default path into main dir (no test on it)
 *  											1:If $type==0 and if file was not found into alternate dir, return empty string
 *  											2:If $type==0 and if file was not found into alternate dir, test into main dir, return default path if found, empty string if not found
 *  @return string								Full filesystem path (if path=0) or '' if file not found, Full url path (if mode=1)
 */
function dol_buildpath($path, $type = 0, $returnemptyifnotfound = 0)
{
	global $conf;

	$path = preg_replace('/^\//', '', $path);

	if (empty($type))	// For a filesystem path
	{
		$res = DOL_DOCUMENT_ROOT.'/'.$path; // Standard default path
		if (is_array($conf->file->dol_document_root))
		{
			foreach ($conf->file->dol_document_root as $key => $dirroot)	// ex: array("main"=>"/home/main/htdocs", "alt0"=>"/home/dirmod/htdocs", ...)
			{
				if ($key == 'main')
				{
					continue;
				}
				if (file_exists($dirroot.'/'.$path))
				{
					$res = $dirroot.'/'.$path;
					return $res;
				}
			}
		}
		if ($returnemptyifnotfound)								// Not found into alternate dir
		{
			if ($returnemptyifnotfound == 1 || !file_exists($res)) return '';
		}
	}
	else				// For an url path
	{
		// We try to get local path of file on filesystem from url
		// Note that trying to know if a file on disk exist by forging path on disk from url
		// works only for some web server and some setup. This is bugged when
		// using proxy, rewriting, virtual path, etc...
		$res = '';
		if ($type == 1) $res = DOL_URL_ROOT.'/'.$path; // Standard value
		if ($type == 2) $res = DOL_MAIN_URL_ROOT.'/'.$path; // Standard value
		if ($type == 3) $res = DOL_URL_ROOT.'/'.$path;

		foreach ($conf->file->dol_document_root as $key => $dirroot)	// ex: array(["main"]=>"/home/main/htdocs", ["alt0"]=>"/home/dirmod/htdocs", ...)
		{
			if ($key == 'main')
			{
				if ($type == 3)
				{
					global $dolibarr_main_url_root;

					// Define $urlwithroot
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

					$res = (preg_match('/^http/i', $conf->file->dol_url_root[$key]) ? '' : $urlwithroot).'/'.$path; // Test on start with http is for old conf syntax
				}
				continue;
			}
			preg_match('/^([^\?]+(\.css\.php|\.css|\.js\.php|\.js|\.png|\.jpg|\.php)?)/i', $path, $regs); // Take part before '?'
			if (!empty($regs[1]))
			{
				//print $key.'-'.$dirroot.'/'.$path.'-'.$conf->file->dol_url_root[$type].'<br>'."\n";
				if (file_exists($dirroot.'/'.$regs[1]))
				{
					if ($type == 1)
					{
						$res = (preg_match('/^http/i', $conf->file->dol_url_root[$key]) ? '' : DOL_URL_ROOT).$conf->file->dol_url_root[$key].'/'.$path;
					}
					if ($type == 2)
					{
						$res = (preg_match('/^http/i', $conf->file->dol_url_root[$key]) ? '' : DOL_MAIN_URL_ROOT).$conf->file->dol_url_root[$key].'/'.$path;
					}
					if ($type == 3)
					{
						global $dolibarr_main_url_root;

						// Define $urlwithroot
						$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
						//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

						$res = (preg_match('/^http/i', $conf->file->dol_url_root[$key]) ? '' : $urlwithroot).$conf->file->dol_url_root[$key].'/'.$path; // Test on start with http is for old conf syntax
					}
					break;
				}
			}
		}
	}

	return $res;
}

/**
 *	Create a clone of instance of object (new instance with same value for properties)
 *  With native = 0: Property that are reference are also new object (full isolation clone). This means $this->db of new object is not valid.
 *  With native = 1: Use PHP clone. Property that are reference are same pointer. This means $this->db of new object is still valid but point to same this->db than original object.
 *
 * 	@param	object	$object		Object to clone
 *  @param	int		$native		0=Full isolation method, 1=Native PHP method
 *	@return object				Clone object
 *  @see https://php.net/manual/language.oop5.cloning.php
 */
function dol_clone($object, $native = 0)
{
	if (empty($native))
	{
		$myclone = unserialize(serialize($object));
	}
	else
	{
		$myclone = clone $object; // PHP clone is a shallow copy only, not a real clone, so properties of references will keep references (refer to the same target/variable)
	}

	return $myclone;
}

/**
 *	Optimize a size for some browsers (phone, smarphone, ...)
 *
 * 	@param	int		$size		Size we want
 * 	@param	string	$type		Type of optimizing:
 * 								'' = function used to define a size for truncation
 * 								'width' = function is used to define a width
 *	@return int					New size after optimizing
 */
function dol_size($size, $type = '')
{
	global $conf;
	if (empty($conf->dol_optimize_smallscreen)) return $size;
	if ($type == 'width' && $size > 250) return 250;
	else return 10;
}


/**
 *	Clean a string to use it as a file name
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace bad chars with
 *  @param	int	    $unaccent		1=Remove also accent (default), 0 do not remove them
 *	@return string          		String cleaned (a-zA-Z_)
 *
 * 	@see        	dol_string_nospecial(), dol_string_unaccent(), dol_sanitizePathName()
 */
function dol_sanitizeFileName($str, $newstr = '_', $unaccent = 1)
{
	// List of special chars for filenames in windows are defined on page https://docs.microsoft.com/en-us/windows/win32/fileio/naming-a-file
	// Char '>' '<' '|' '$' and ';' are special chars for shells.
	// Char '/' and '\' are file delimiters.
	$filesystem_forbidden_chars = array('<', '>', '/', '\\', '?', '*', '|', '"', ':', '°', '$', ';');
	return dol_string_nospecial($unaccent ?dol_string_unaccent($str) : $str, $newstr, $filesystem_forbidden_chars);
}

/**
 *	Clean a string to use it as a path name
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace bad chars with
 *  @param	int	    $unaccent		1=Remove also accent (default), 0 do not remove them
 *	@return string          		String cleaned (a-zA-Z_)
 *
 * 	@see        	dol_string_nospecial(), dol_string_unaccent(), dol_sanitizeFileName()
 */
function dol_sanitizePathName($str, $newstr = '_', $unaccent = 1)
{
	$filesystem_forbidden_chars = array('<', '>', '?', '*', '|', '"', '°');
	return dol_string_nospecial($unaccent ?dol_string_unaccent($str) : $str, $newstr, $filesystem_forbidden_chars);
}

/**
 *	Clean a string from all accent characters to be used as ref, login or by dol_sanitizeFileName
 *
 *	@param	string	$str			String to clean
 *	@return string   	       		Cleaned string
 *
 * 	@see    		dol_sanitizeFilename(), dol_string_nospecial()
 */
function dol_string_unaccent($str)
{
	if (utf8_check($str))
	{
		// See http://www.utf8-chartable.de/
		$string = rawurlencode($str);
		$replacements = array(
		'%C3%80' => 'A', '%C3%81' => 'A', '%C3%82' => 'A', '%C3%83' => 'A', '%C3%84' => 'A', '%C3%85' => 'A',
		'%C3%88' => 'E', '%C3%89' => 'E', '%C3%8A' => 'E', '%C3%8B' => 'E',
		'%C3%8C' => 'I', '%C3%8D' => 'I', '%C3%8E' => 'I', '%C3%8F' => 'I',
		'%C3%92' => 'O', '%C3%93' => 'O', '%C3%94' => 'O', '%C3%95' => 'O', '%C3%96' => 'O',
		'%C3%99' => 'U', '%C3%9A' => 'U', '%C3%9B' => 'U', '%C3%9C' => 'U',
		'%C3%A0' => 'a', '%C3%A1' => 'a', '%C3%A2' => 'a', '%C3%A3' => 'a', '%C3%A4' => 'a', '%C3%A5' => 'a',
		'%C3%A7' => 'c',
		'%C3%A8' => 'e', '%C3%A9' => 'e', '%C3%AA' => 'e', '%C3%AB' => 'e',
		'%C3%AC' => 'i', '%C3%AD' => 'i', '%C3%AE' => 'i', '%C3%AF' => 'i',
		'%C3%B1' => 'n',
		'%C3%B2' => 'o', '%C3%B3' => 'o', '%C3%B4' => 'o', '%C3%B5' => 'o', '%C3%B6' => 'o',
		'%C3%B9' => 'u', '%C3%BA' => 'u', '%C3%BB' => 'u', '%C3%BC' => 'u',
		'%C3%BF' => 'y'
		);
		$string = strtr($string, $replacements);
		return rawurldecode($string);
	}
	else
	{
		// See http://www.ascii-code.com/
        $string = strtr(
			$str,
			"\xC0\xC1\xC2\xC3\xC4\xC5\xC7
			\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
			\xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD
			\xE0\xE1\xE2\xE3\xE4\xE5\xE7\xE8\xE9\xEA\xEB
			\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8
			\xF9\xFA\xFB\xFC\xFD\xFF",
			"AAAAAAC
			EEEEIIIIDN
			OOOOOUUUY
			aaaaaaceeee
			iiiidnooooo
			uuuuyy"
		);
		$string = strtr($string, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH", "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));
		return $string;
	}
}

/**
 *	Clean a string from all punctuation characters to use it as a ref or login.
 *  This is a more complete function than dol_sanitizeFileName.
 *
 *	@param	string	$str            	String to clean
 * 	@param	string	$newstr				String to replace forbidden chars with
 *  @param  array	$badcharstoreplace  List of forbidden characters
 * 	@return string          			Cleaned string
 *
 * 	@see    		dol_sanitizeFilename(), dol_string_unaccent()
 */
function dol_string_nospecial($str, $newstr = '_', $badcharstoreplace = '')
{
	$forbidden_chars_to_replace = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ",", ";", "=", '°'); // more complete than dol_sanitizeFileName
	$forbidden_chars_to_remove = array();
	if (is_array($badcharstoreplace)) $forbidden_chars_to_replace = $badcharstoreplace;
	//$forbidden_chars_to_remove=array("(",")");

	return str_replace($forbidden_chars_to_replace, $newstr, str_replace($forbidden_chars_to_remove, "", $str));
}


/**
 *  Returns text escaped for inclusion into javascript code
 *
 *  @param      string		$stringtoescape		String to escape
 *  @param		int		$mode				0=Escape also ' and " into ', 1=Escape ' but not " for usage into 'string', 2=Escape " but not ' for usage into "string", 3=Escape ' and " with \
 *  @param		int		$noescapebackslashn	0=Escape also \n. 1=Do not escape \n.
 *  @return     string     		 				Escaped string. Both ' and " are escaped into ' if they are escaped.
 */
function dol_escape_js($stringtoescape, $mode = 0, $noescapebackslashn = 0)
{
	// escape quotes and backslashes, newlines, etc.
	$substitjs = array("&#039;"=>"\\'", "\r"=>'\\r');
	//$substitjs['</']='<\/';	// We removed this. Should be useless.
	if (empty($noescapebackslashn)) { $substitjs["\n"] = '\\n'; $substitjs['\\'] = '\\\\'; }
	if (empty($mode)) { $substitjs["'"] = "\\'"; $substitjs['"'] = "\\'"; }
	elseif ($mode == 1) $substitjs["'"] = "\\'";
	elseif ($mode == 2) { $substitjs['"'] = '\\"'; }
	elseif ($mode == 3) { $substitjs["'"] = "\\'"; $substitjs['"'] = "\\\""; }
	return strtr($stringtoescape, $substitjs);
}


/**
 *  Returns text escaped for inclusion in HTML alt or title tags, or into values of HTML input fields.
 *
 *  @param      string		$stringtoescape		String to escape
 *  @param		int			$keepb				1=Preserve b tags (otherwise, remove them)
 *  @param      int         $keepn              1=Preserve \r\n strings (otherwise, replace them with escaped value). Set to 1 when escaping for a <textarea>.
 *  @param		string		$keepmoretags		'' or 'common' or list of tags
 *  @return     string     				 		Escaped string
 *  @see		dol_string_nohtmltag(), dol_string_nospecial(), dol_string_unaccent()
 */
function dol_escape_htmltag($stringtoescape, $keepb = 0, $keepn = 0, $keepmoretags = '')
{
	if ($keepmoretags == 'common') $keepmoretags = 'html,body,a,em,i,u,ul,li,br,div,img,font,p,span,strong,table,tr,td,th,tbody';
	// TODO Implement $keepmoretags

	// escape quotes and backslashes, newlines, etc.
	$tmp = html_entity_decode($stringtoescape, ENT_COMPAT, 'UTF-8'); // TODO Use htmlspecialchars_decode instead, that make only required change for html tags
	if (!$keepb) $tmp = strtr($tmp, array("<b>"=>'', '</b>'=>''));
	if (!$keepn) $tmp = strtr($tmp, array("\r"=>'\\r', "\n"=>'\\n'));
	return htmlentities($tmp, ENT_COMPAT, 'UTF-8'); // TODO Use htmlspecialchars instead, that make only required change for html tags
}


/**
 * Convert a string to lower. Never use strtolower because it does not works with UTF8 strings.
 *
 * @param 	string		$utf8_string		String to encode
 * @return 	string							String converted
 */
function dol_strtolower($utf8_string)
{
	return mb_strtolower($utf8_string, "UTF-8");
}

/**
 * Convert a string to upper. Never use strtolower because it does not works with UTF8 strings.
 *
 * @param 	string		$utf8_string		String to encode
 * @return 	string							String converted
 */
function dol_strtoupper($utf8_string)
{
	return mb_strtoupper($utf8_string, "UTF-8");
}


/**
 *	Write log message into outputs. Possible outputs can be:
 *	SYSLOG_HANDLERS = ["mod_syslog_file"]  		file name is then defined by SYSLOG_FILE
 *	SYSLOG_HANDLERS = ["mod_syslog_syslog"]  	facility is then defined by SYSLOG_FACILITY
 *  Warning, syslog functions are bugged on Windows, generating memory protection faults. To solve
 *  this, use logging to files instead of syslog (see setup of module).
 *  Note: If constant 'SYSLOG_FILE_NO_ERROR' defined, we never output any error message when writing to log fails.
 *  Note: You can get log message into html sources by adding parameter &logtohtml=1 (constant MAIN_LOGTOHTML must be set)
 *  This function works only if syslog module is enabled.
 * 	This must not use any call to other function calling dol_syslog (avoid infinite loop).
 *
 * 	@param  string		$message				Line to log. ''=Show nothing
 *  @param  int			$level					Log level
 *												On Windows LOG_ERR=4, LOG_WARNING=5, LOG_NOTICE=LOG_INFO=6, LOG_DEBUG=6 si define_syslog_variables ou PHP 5.3+, 7 si dolibarr
 *												On Linux   LOG_ERR=3, LOG_WARNING=4, LOG_INFO=6, LOG_DEBUG=7
 *  @param	int			$ident					1=Increase ident of 1, -1=Decrease ident of 1
 *  @param	string		$suffixinfilename		When output is a file, append this suffix into default log filename.
 *  @param	string		$restricttologhandler	Force output of log only to this log handler
 *  @param	array|null	$logcontext				If defined, an array with extra informations (can be used by some log handlers)
 *  @return	void
 */
function dol_syslog($message, $level = LOG_INFO, $ident = 0, $suffixinfilename = '', $restricttologhandler = '', $logcontext = null)
{
    global $conf, $user, $debugbar;

	// If syslog module enabled
	if (empty($conf->syslog->enabled)) return;

	if ($ident < 0)
	{
		foreach ($conf->loghandlers as $loghandlerinstance)
		{
			$loghandlerinstance->setIdent($ident);
		}
	}

	if (!empty($message))
	{
		// Test log level
		$logLevels = array(LOG_EMERG=>'EMERG', LOG_ALERT=>'ALERT', LOG_CRIT=>'CRITICAL', LOG_ERR=>'ERR', LOG_WARNING=>'WARN', LOG_NOTICE=>'NOTICE', LOG_INFO=>'INFO', LOG_DEBUG=>'DEBUG');
		if (!array_key_exists($level, $logLevels))
		{
			throw new Exception('Incorrect log level');
		}
		if ($level > $conf->global->SYSLOG_LEVEL) return;

		$message = preg_replace('/password=\'[^\']*\'/', 'password=\'hidden\'', $message); // protection to avoid to have value of password in log

		// If adding log inside HTML page is required
		if ((!empty($_REQUEST['logtohtml']) && !empty($conf->global->MAIN_ENABLE_LOG_TO_HTML))
		    || (!empty($user->rights->debugbar->read) && is_object($debugbar)))
		{
		    $conf->logbuffer[] = dol_print_date(time(), "%Y-%m-%d %H:%M:%S")." ".$logLevels[$level]." ".$message;
		}

		//TODO: Remove this. MAIN_ENABLE_LOG_INLINE_HTML should be deprecated and use a log handler dedicated to HTML output
		// If html log tag enabled and url parameter log defined, we show output log on HTML comments
		if (!empty($conf->global->MAIN_ENABLE_LOG_INLINE_HTML) && !empty($_GET["log"]))
		{
			print "\n\n<!-- Log start\n";
			print $message."\n";
			print "Log end -->\n";
		}

		$data = array(
			'message' => $message,
			'script' => (isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF'], '.php') : false),
			'level' => $level,
			'user' => ((is_object($user) && $user->id) ? $user->login : false),
			'ip' => false
		);

		$remoteip = getUserRemoteIP();	// Get ip when page run on a web server
		if (! empty($remoteip)) {
			$data['ip'] = $remoteip;
			// This is when server run behind a reverse proxy
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != $remoteip) $data['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'].' -> '.$data['ip'];
			elseif (!empty($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != $remoteip) $data['ip'] = $_SERVER['HTTP_CLIENT_IP'].' -> '.$data['ip'];
		}
		// This is when PHP session is ran inside a web server but not inside a client request (example: init code of apache)
		elseif (!empty($_SERVER['SERVER_ADDR'])) $data['ip'] = $_SERVER['SERVER_ADDR'];
		// This is when PHP session is ran outside a web server, like from Windows command line (Not always defined, but useful if OS defined it).
		elseif (!empty($_SERVER['COMPUTERNAME'])) $data['ip'] = $_SERVER['COMPUTERNAME'].(empty($_SERVER['USERNAME']) ? '' : '@'.$_SERVER['USERNAME']);
		// This is when PHP session is ran outside a web server, like from Linux command line (Not always defined, but usefull if OS defined it).
		elseif (!empty($_SERVER['LOGNAME'])) $data['ip'] = '???@'.$_SERVER['LOGNAME'];
		// Loop on each log handler and send output
		foreach ($conf->loghandlers as $loghandlerinstance)
		{
			if ($restricttologhandler && $loghandlerinstance->code != $restricttologhandler) continue;
			$loghandlerinstance->export($data, $suffixinfilename);
		}
		unset($data);
	}

	if ($ident > 0)
	{
		foreach ($conf->loghandlers as $loghandlerinstance)
		{
			$loghandlerinstance->setIdent($ident);
		}
	}
}


/**
 *	Show tab header of a card
 *
 *	@param	array	$links				Array of tabs. Currently initialized by calling a function xxx_admin_prepare_head
 *	@param	string	$active     		Active tab name (document', 'info', 'ldap', ....)
 *	@param  string	$title      		Title
 *	@param  int		$notab				-1 or 0=Add tab header, 1=no tab header (if you set this to 1, using dol_fiche_end() to close tab is not required), -2=Add tab header with no seaparation under tab (to start a tab just after)
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodyle/img/myimg.png',1) for $picto.
 *  @param	string	$morehtmlright		Add more html content on right of tabs title
 *  @param	string	$morecss			More Css
 * 	@return	void
 */
function dol_fiche_head($links = array(), $active = '0', $title = '', $notab = 0, $picto = '', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '')
{
	print dol_get_fiche_head($links, $active, $title, $notab, $picto, $pictoisfullpath, $morehtmlright, $morecss);
}

/**
 *  Show tab header of a card
 *
 *	@param	array	$links				Array of tabs
 *	@param	string	$active     		Active tab name
 *	@param  string	$title      		Title
 *	@param  int		$notab				-1 or 0=Add tab header, 1=no tab header (if you set this to 1, using dol_fiche_end() to close tab is not required), -2=Add tab header with no seaparation under tab (to start a tab just after)
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodyle/img/myimg.png',1) for $picto.
 *  @param	string	$morehtmlright		Add more html content on right of tabs title
 *  @param	string	$morecss			More Css
 * 	@return	string
 */
function dol_get_fiche_head($links = array(), $active = '', $title = '', $notab = 0, $picto = '', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '')
{
	global $conf, $langs, $hookmanager;

	// Show title
	$showtitle = 1;
	if (!empty($conf->dol_optimize_smallscreen)) $showtitle = 0;

	$out = "\n".'<!-- dol_get_fiche_head -->';

	if ((!empty($title) && $showtitle) || $morehtmlright || !empty($links)) {
		$out .= '<div class="tabs" data-role="controlgroup" data-type="horizontal">'."\n";
	}

	// Show right part
	if ($morehtmlright) $out .= '<div class="inline-block floatright tabsElem">'.$morehtmlright.'</div>'; // Output right area first so when space is missing, text is in front of tabs and not under.

	// Show title
	if (!empty($title) && $showtitle)
	{
		$limittitle = 30;
		$out .= '<a class="tabTitle">';
		if ($picto) $out .= img_picto($title, ($pictoisfullpath ? '' : 'object_').$picto, '', $pictoisfullpath, 0, 0, '', 'imgTabTitle').' ';
		$out .= '<span class="tabTitleText">'.dol_trunc($title, $limittitle).'</span>';
		$out .= '</a>';
	}

	// Show tabs

	// Define max of key (max may be higher than sizeof because of hole due to module disabling some tabs).
	$maxkey = -1;
	if (is_array($links) && !empty($links))
	{
		$keys = array_keys($links);
		if (count($keys)) $maxkey = max($keys);
	}

	if (!empty($conf->dol_optimize_smallscreen)) $conf->global->MAIN_MAXTABS_IN_CARD = 2;

	// Show tabs
	// if =0 we don't use the feature
	$limittoshow = (empty($conf->global->MAIN_MAXTABS_IN_CARD) ? 99 : $conf->global->MAIN_MAXTABS_IN_CARD);
	$displaytab = 0;
	$nbintab = 0;
	$popuptab = 0;
	$outmore = '';
	for ($i = 0; $i <= $maxkey; $i++)
	{
		if ((is_numeric($active) && $i == $active) || (!empty($links[$i][2]) && !is_numeric($active) && $active == $links[$i][2])) {
			// If active tab is already present
			if ($i >= $limittoshow) $limittoshow--;
		}
	}

	for ($i = 0; $i <= $maxkey; $i++)
	{
		if ((is_numeric($active) && $i == $active) || (!empty($links[$i][2]) && !is_numeric($active) && $active == $links[$i][2])) {
			$isactive = true;
		}
		else {
			$isactive = false;
		}

		if ($i < $limittoshow || $isactive)
		{
			$out .= '<div class="inline-block tabsElem'.($isactive ? ' tabsElemActive' : '').((!$isactive && !empty($conf->global->MAIN_HIDE_INACTIVETAB_ON_PRINT)) ? ' hideonprint' : '').'"><!-- id tab = '.(empty($links[$i][2]) ? '' : $links[$i][2]).' -->';
			if (isset($links[$i][2]) && $links[$i][2] == 'image')
			{
				if (!empty($links[$i][0]))
				{
					$out .= '<a class="tabimage'.($morecss ? ' '.$morecss : '').'" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				}
				else
				{
					$out .= '<span class="tabspan">'.$links[$i][1].'</span>'."\n";
				}
			}
			elseif (!empty($links[$i][1]))
			{
				//print "x $i $active ".$links[$i][2]." z";
				if ($isactive)
				{
					$out .= '<a'.(!empty($links[$i][2]) ? ' id="'.$links[$i][2].'"' : '').' class="tabactive tab inline-block'.($morecss ? ' '.$morecss : '').'" href="'.$links[$i][0].'">';
					$out .= $links[$i][1];
					$out .= '</a>'."\n";
				}
				else
				{
					$out .= '<a'.(!empty($links[$i][2]) ? ' id="'.$links[$i][2].'"' : '').' class="tabunactive tab inline-block'.($morecss ? ' '.$morecss : '').'" href="'.$links[$i][0].'">';
					$out .= $links[$i][1];
					$out .= '</a>'."\n";
				}
			}
			$out .= '</div>';
		}
		else
		{
			// The popup with the other tabs
			if (!$popuptab)
			{
				$popuptab = 1;
				$outmore .= '<div class="popuptabset wordwrap">'; // The css used to hide/show popup
			}
			$outmore .= '<div class="popuptab wordwrap" style="display:inherit;">';
			if (isset($links[$i][2]) && $links[$i][2] == 'image')
			{
				if (!empty($links[$i][0]))
					$outmore .= '<a class="tabimage'.($morecss ? ' '.$morecss : '').'" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				else
					$outmore .= '<span class="tabspan">'.$links[$i][1].'</span>'."\n";
			}
			elseif (!empty($links[$i][1]))
			{
				$outmore .= '<a'.(!empty($links[$i][2]) ? ' id="'.$links[$i][2].'"' : '').' class="wordwrap inline-block'.($morecss ? ' '.$morecss : '').'" href="'.$links[$i][0].'">';
				$outmore .= preg_replace('/([a-z])\/([a-z])/i', '\\1 / \\2', $links[$i][1]); // Replace x/y with x / y to allow wrap on long composed texts.
				$outmore .= '</a>'."\n";
			}
			$outmore .= '</div>';

			$nbintab++;
		}
		$displaytab = $i;
	}
	if ($popuptab) $outmore .= '</div>';

	if ($popuptab)	// If there is some tabs not shown
	{
		$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');
		$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');

		$tabsname = str_replace("@", "", $picto);
		$out .= '<div id="moretabs'.$tabsname.'" class="inline-block tabsElem">';
		$out .= '<a href="#" class="tab moretab inline-block tabunactive reposition">'.$langs->trans("More").'... ('.$nbintab.')</a>';
		$out .= '<div id="moretabsList'.$tabsname.'" style="position: absolute; '.$left.': -999em; text-align: '.$left.'; margin:0px; padding:2px; z-index:10;">';
		$out .= $outmore;
		$out .= '</div>';
		$out .= '<div></div>';
		$out .= "</div>\n";

		$out .= "<script>";
		$out .= "$('#moretabs".$tabsname."').mouseenter( function() { console.log('mouseenter ".$left."'); $('#moretabsList".$tabsname."').css('".$left."','auto');});";
		$out .= "$('#moretabs".$tabsname."').mouseleave( function() { console.log('mouseleave ".$left."'); $('#moretabsList".$tabsname."').css('".$left."','-999em');});";
		$out .= "</script>";
	}

	if ((!empty($title) && $showtitle) || $morehtmlright || !empty($links)) {
		$out .= "</div>\n";
	}

	if (!$notab || $notab == -1 || $notab == -2) $out .= "\n".'<div class="tabBar'.($notab == -1 ? '' : ($notab == -2 ? ' tabBarNoTop' : ' tabBarWithBottom')).'">'."\n";

	$parameters = array('tabname' => $active, 'out' => $out);
	$reshook = $hookmanager->executeHooks('printTabsHead', $parameters); // This hook usage is called just before output the head of tabs. Take also a look at "completeTabsHead"
	if ($reshook > 0)
	{
		$out = $hookmanager->resPrint;
	}

	return $out;
}

/**
 *  Show tab footer of a card
 *
 *  @param	int		$notab       -1 or 0=Add tab footer, 1=no tab footer
 *  @return	void
 */
function dol_fiche_end($notab = 0)
{
	print dol_get_fiche_end($notab);
}

/**
 *	Return tab footer of a card
 *
 *	@param  int		$notab		-1 or 0=Add tab footer, 1=no tab footer
 *  @return	string
 */
function dol_get_fiche_end($notab = 0)
{
	if (!$notab || $notab == -1) return "\n</div>\n";
	else return '';
}

/**
 *  Show tab footer of a card.
 *  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
 *
 *  @param	Object	$object			Object to show
 *  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
 *  @param	string	$morehtml  		More html content to output just before the nav bar
 *  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
 *  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 *  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
 *  @param	string	$morehtmlref  	More html to show after ref
 *  @param	string	$moreparam  	More param to add in nav link url.
 *	@param	int		$nodbprefix		Do not include DB prefix to forge table name
 *	@param	string	$morehtmlleft	More html code to show before ref
 *	@param	string	$morehtmlstatus	More html code to show under navigation arrows
 *  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (this add css 'arearefnobottom' on div)
 *	@param	string	$morehtmlright	More html code to show before navigation arrows
 *  @return	void
 */
function dol_banner_tab($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '')
{
	global $conf, $form, $user, $langs;

	$error = 0;

	$maxvisiblephotos = 1;
	$showimage = 1;
	$entity = (empty($object->entity) ? $conf->entity : $object->entity);
	$showbarcode = empty($conf->barcode->enabled) ? 0 : ($object->barcode ? 1 : 0);
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;
	$modulepart = 'unknown';

	if ($object->element == 'societe')         $modulepart = 'societe';
	if ($object->element == 'contact')         $modulepart = 'contact';
	if ($object->element == 'member')          $modulepart = 'memberphoto';
	if ($object->element == 'user')            $modulepart = 'userphoto';
	if ($object->element == 'product')         $modulepart = 'product';
	if ($object->element == 'ticket')          $modulepart = 'ticket';

	if (class_exists("Imagick"))
	{
		if ($object->element == 'propal')            $modulepart = 'propal';
		if ($object->element == 'commande')          $modulepart = 'commande';
		if ($object->element == 'facture')           $modulepart = 'facture';
		if ($object->element == 'fichinter')         $modulepart = 'ficheinter';
		if ($object->element == 'contrat')           $modulepart = 'contract';
		if ($object->element == 'supplier_proposal') $modulepart = 'supplier_proposal';
		if ($object->element == 'order_supplier')    $modulepart = 'supplier_order';
		if ($object->element == 'invoice_supplier')  $modulepart = 'supplier_invoice';
		if ($object->element == 'expensereport')     $modulepart = 'expensereport';
	}

	if ($object->element == 'product')
	{
		$width = 80; $cssclass = 'photoref';
		$showimage = $object->is_photo_available($conf->product->multidir_output[$entity]);
		$maxvisiblephotos = (isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO) ? $conf->global->PRODUCT_MAX_VISIBLE_PHOTO : 5);
		if ($conf->browser->layout == 'phone') $maxvisiblephotos = 1;
		if ($showimage) $morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos('product', $conf->product->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, $width, 0).'</div>';
		else
		{
			if (!empty($conf->global->PRODUCT_NODISPLAYIFNOPHOTO)) {
				$nophoto = '';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			else {    // Show no photo link
				$nophoto = '/public/theme/common/nophoto.png';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass ? ' '.$cssclass : '').'" alt="No photo"'.($width ? ' style="width: '.$width.'px"' : '').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			}
		}
	}
	elseif ($object->element == 'ticket')
	{
		$width = 80; $cssclass = 'photoref';
		$showimage = $object->is_photo_available($conf->ticket->multidir_output[$entity].'/'.$object->ref);
		$maxvisiblephotos = (isset($conf->global->TICKET_MAX_VISIBLE_PHOTO) ? $conf->global->TICKET_MAX_VISIBLE_PHOTO : 2);
		if ($conf->browser->layout == 'phone') $maxvisiblephotos = 1;
		if ($showimage)
		{
			$showphoto = $object->show_photos('ticket', $conf->ticket->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, $width, 0);
			if ($object->nbphoto > 0)
			{
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$showphoto.'</div>';
			}
			else
			{
				$showimage = 0;
			}
		}
		if (!$showimage)
		{
			if (!empty($conf->global->TICKET_NODISPLAYIFNOPHOTO)) {
				$nophoto = '';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			else {    // Show no photo link
				$nophoto = '/public/theme/common/nophoto.png';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass ? ' '.$cssclass : '').'" alt="No photo" border="0"'.($width ? ' style="width: '.$width.'px"' : '').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			}
		}
	}
	else
	{
		if ($showimage)
		{
			if ($modulepart != 'unknown')
			{
				$phototoshow = '';
				// Check if a preview file is available
				if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick"))
				{
					$objectref = dol_sanitizeFileName($object->ref);
					$dir_output = (empty($conf->$modulepart->multidir_output[$entity]) ? $conf->$modulepart->dir_output : $conf->$modulepart->multidir_output[$entity])."/";
					if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice')))
					{
						$subdir = get_exdir($object->id, 2, 0, 1, $object, $modulepart);
						$subdir .= ((!empty($subdir) && !preg_match('/\/$/', $subdir)) ? '/' : '').$objectref; // the objectref dir is not included into get_exdir when used with level=2, so we add it at end
					}
					else
					{
						$subdir = get_exdir($object->id, 0, 0, 1, $object, $modulepart);
					}
					if (empty($subdir)) $subdir = 'errorgettingsubdirofobject'; // Protection to avoid to return empty path

					$filepath = $dir_output.$subdir."/";

					$filepdf = $filepath.$objectref.".pdf";
					$relativepath = $subdir.'/'.$objectref.'.pdf';

					// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
					$fileimage = $filepdf.'_preview.png';
					$relativepathimage = $relativepath.'_preview.png';

					$pdfexists = file_exists($filepdf);

					// If PDF file exists
					if ($pdfexists)
					{
						// Conversion du PDF en image png si fichier png non existant
						if (!file_exists($fileimage) || (filemtime($fileimage) < filemtime($filepdf)))
						{
							if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS))		// If you experience trouble with pdf thumb generation and imagick, you can disable here.
							{
								include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
								$ret = dol_convert_file($filepdf, 'png', $fileimage, '0'); // Convert first page of PDF into a file _preview.png
								if ($ret < 0) $error++;
							}
						}
					}

					if ($pdfexists && !$error)
					{
						$heightforphotref = 70;
						if (!empty($conf->dol_optimize_smallscreen)) $heightforphotref = 60;
						// If the preview file is found
						if (file_exists($fileimage))
						{
							$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
							$phototoshow .= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
							$phototoshow .= '</div></div>';
						}
					}
				}
				elseif (!$phototoshow)
				{
					$phototoshow .= $form->showphoto($modulepart, $object, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);
				}

				if ($phototoshow)
				{
					$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
					$morehtmlleft .= $phototoshow;
					$morehtmlleft .= '</div>';
				}
			}

			if (!$phototoshow)      // Show No photo link (picto of pbject)
			{
			    $morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
				if ($object->element == 'action')
				{
					$width = 80;
					$cssclass = 'photorefcenter';
					$nophoto = img_picto('', 'title_agenda', '', false, 1);
				}
				else
				{
					$width = 14; $cssclass = 'photorefcenter';
					$picto = $object->picto;
					if ($object->element == 'project' && !$object->public) $picto = 'project'; // instead of projectpub
					$nophoto = img_picto('', 'object_'.$picto, '', false, 1);
				}
				$morehtmlleft .= '<!-- No photo to show -->';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass ? ' '.$cssclass : '').'" alt="No photo"'.($width ? ' style="width: '.$width.'px"' : '').' src="'.$nophoto.'"></div></div>';

				$morehtmlleft .= '</div>';
			}
		}
	}

	if ($showbarcode) $morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object).'</div>';

	if ($object->element == 'societe')
	{
		if (!empty($conf->use_javascript_ajax) && $user->rights->societe->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE))
		{
		   	$morehtmlstatus .= ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
		}
		else {
			$morehtmlstatus .= $object->getLibStatut(6);
		}
	}
	elseif ($object->element == 'product')
	{
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
		if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			$morehtmlstatus .= ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
		} else {
			$morehtmlstatus .= '<span class="statusrefsell">'.$object->getLibStatut(6, 0).'</span>';
		}
		$morehtmlstatus .= ' &nbsp; ';
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
		if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			$morehtmlstatus .= ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
		} else {
			$morehtmlstatus .= '<span class="statusrefbuy">'.$object->getLibStatut(6, 1).'</span>';
		}
	}
	elseif (in_array($object->element, array('facture', 'invoice', 'invoice_supplier', 'chargesociales', 'loan')))
	{
		$tmptxt = $object->getLibStatut(6, $object->totalpaye);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) $tmptxt = $object->getLibStatut(5, $object->totalpaye);
		$morehtmlstatus .= $tmptxt;
	}
	elseif ($object->element == 'contrat' || $object->element == 'contract')
	{
		if ($object->statut == 0) $morehtmlstatus .= $object->getLibStatut(5);
		else $morehtmlstatus .= $object->getLibStatut(4);
	}
	elseif ($object->element == 'facturerec')
	{
		if ($object->frequency == 0) $morehtmlstatus .= $object->getLibStatut(2);
		else $morehtmlstatus .= $object->getLibStatut(5);
	}
	elseif ($object->element == 'project_task')
	{
		$object->fk_statut = 1;
		if ($object->progress > 0) $object->fk_statut = 2;
		if ($object->progress >= 100) $object->fk_statut = 3;
		$tmptxt = $object->getLibStatut(5);
		$morehtmlstatus .= $tmptxt; // No status on task
	}
	else { // Generic case
		$tmptxt = $object->getLibStatut(6);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) $tmptxt = $object->getLibStatut(5);
		$morehtmlstatus .= $tmptxt;
	}

	// Add if object was dispatched "into accountancy"
	if (!empty($conf->accounting->enabled) && in_array($object->element, array('bank', 'facture', 'invoice', 'invoice_supplier', 'expensereport', 'payment_various')))
	{
		if (method_exists($object, 'getVentilExportCompta'))
		{
			$accounted = $object->getVentilExportCompta();
			$langs->load("accountancy");
			$morehtmlstatus .= '</div><div class="statusref statusrefbis"><span class="opacitymedium">'.($accounted > 0 ? $langs->trans("Accounted") : $langs->trans("NotYetAccounted")).'</span>';
		}
	}

	// Add alias for thirdparty
	if (!empty($object->name_alias)) $morehtmlref .= '<div class="refidno">'.$object->name_alias.'</div>';

	// Add label
	if ($object->element == 'product' || $object->element == 'bank_account' || $object->element == 'project_task')
	{
		if (!empty($object->label)) $morehtmlref .= '<div class="refidno">'.$object->label.'</div>';
	}

	if (method_exists($object, 'getBannerAddress') && $object->element != 'product' && $object->element != 'bookmark' && $object->element != 'ecm_directories' && $object->element != 'ecm_files')
	{
		$moreaddress = $object->getBannerAddress('refaddress', $object);
		if ($moreaddress) {
			$morehtmlref .= '<div class="refidno">';
			$morehtmlref .= $moreaddress;
			$morehtmlref .= '</div>';
		}
	}
	if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && in_array($object->element, array('societe', 'contact', 'member', 'product')))
	{
		$morehtmlref .= '<div style="clear: both;"></div><div class="refidno">';
		$morehtmlref .= $langs->trans("TechnicalID").': '.$object->id;
		$morehtmlref .= '</div>';
	}

	print '<div class="'.($onlybanner ? 'arearefnobottom ' : 'arearef ').'heightref valignmiddle centpercent">';
	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	print '</div>';
	print '<div class="underrefbanner clearboth"></div>';
}

/**
 * Show a string with the label tag dedicated to the HTML edit field.
 *
 * @param	string	$langkey		Translation key
 * @param 	string	$fieldkey		Key of the html select field the text refers to
 * @param	int		$fieldrequired	1=Field is mandatory
 * @return string
 * @deprecated Form::editfieldkey
 */
function fieldLabel($langkey, $fieldkey, $fieldrequired = 0)
{
	global $langs;
	$ret = '';
	if ($fieldrequired) $ret .= '<span class="fieldrequired">';
	$ret .= '<label for="'.$fieldkey.'">';
	$ret .= $langs->trans($langkey);
	$ret .= '</label>';
	if ($fieldrequired) $ret .= '</span>';
	return $ret;
}

/**
 * Return string to add class property on html element with pair/impair.
 *
 * @param	string	$var			0 or 1
 * @param	string	$moreclass		More class to add
 * @return	string					String to add class onto HTML element
 */
function dol_bc($var, $moreclass = '')
{
	global $bc;
	$ret = ' '.$bc[$var];
	if ($moreclass) $ret = preg_replace('/class=\"/', 'class="'.$moreclass.' ', $ret);
	return $ret;
}

/**
 *      Return a formated address (part address/zip/town/state) according to country rules.
 *      See https://en.wikipedia.org/wiki/Address
 *
 *      @param  Object		$object			A company or contact object
 * 	    @param	int			$withcountry	1=Add country into address string
 *      @param	string		$sep			Separator to use to build string
 *      @param	Translate	$outputlangs	Object lang that contains language for text translation.
 *      @param	int			$mode			0=Standard output, 1=Remove address
 *      @return string						Formated string
 *      @see dol_print_address()
 */
function dol_format_address($object, $withcountry = 0, $sep = "\n", $outputlangs = '', $mode = 0)
{
	global $conf, $langs;

	$ret = '';
	$countriesusingstate = array('AU', 'CA', 'US', 'IN', 'GB', 'ES', 'UK', 'TR'); // See also MAIN_FORCE_STATE_INTO_ADDRESS

	// See format of addresses on https://en.wikipedia.org/wiki/Address

	// Address
	if (empty($mode)) {
		$ret .= $object->address;
	}
	// Zip/Town/State
	if (in_array($object->country_code, array('AU', 'CA', 'US')) || !empty($conf->global->MAIN_FORCE_STATE_INTO_ADDRESS))   	// US: title firstname name \n address lines \n town, state, zip \n country
	{
		$ret .= ($ret ? $sep : '').$object->town;
		if ($object->state)
		{
			$ret .= ($ret ? ", " : '').$object->state;
		}
		if ($object->zip) $ret .= ($ret ? ", " : '').$object->zip;
	}
	elseif (in_array($object->country_code, array('GB', 'UK'))) // UK: title firstname name \n address lines \n town state \n zip \n country
	{
		$ret .= ($ret ? $sep : '').$object->town;
		if ($object->state)
		{
			$ret .= ($ret ? ", " : '').$object->state;
		}
		if ($object->zip) $ret .= ($ret ? $sep : '').$object->zip;
	}
	elseif (in_array($object->country_code, array('ES', 'TR'))) // ES: title firstname name \n address lines \n zip town \n state \n country
	{
		$ret .= ($ret ? $sep : '').$object->zip;
		$ret .= ($object->town ? (($object->zip ? ' ' : '').$object->town) : '');
		if ($object->state)
		{
			$ret .= "\n".$object->state;
		}
	}
	elseif (in_array($object->country_code, array('IT'))) // IT: tile firstname name\n address lines \n zip (Code Departement) \n country
	{
		$ret .= ($ret ? $sep : '').$object->zip;
		$ret .= ($object->town ? (($object->zip ? ' ' : '').$object->town) : '');
		$ret .= ($object->state_code ? (' '.($object->state_code)) : '');
	}
	else                                        		// Other: title firstname name \n address lines \n zip town \n country
	{
		$ret .= $object->zip ? (($ret ? $sep : '').$object->zip) : '';
		$ret .= ($object->town ? (($object->zip ? ' ' : ($ret ? $sep : '')).$object->town) : '');
		if ($object->state && in_array($object->country_code, $countriesusingstate))
		{
			$ret .= ($ret ? ", " : '').$object->state;
		}
	}
	if (!is_object($outputlangs)) $outputlangs = $langs;
	if ($withcountry)
	{
		$langs->load("dict");
		$ret .= ($object->country_code ? ($ret ? $sep : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->country_code)) : '');
	}

	return $ret;
}



/**
 *	Format a string.
 *
 *	@param	string	$fmt		Format of strftime function (http://php.net/manual/fr/function.strftime.php)
 *  @param	int		$ts			Timesamp (If is_gmt is true, timestamp is already includes timezone and daylight saving offset, if is_gmt is false, timestamp is a GMT timestamp and we must compensate with server PHP TZ)
 *  @param	int		$is_gmt		See comment of timestamp parameter
 *	@return	string				A formatted string
 */
function dol_strftime($fmt, $ts = false, $is_gmt = false)
{
	if ((abs($ts) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
		return ($is_gmt) ? @gmstrftime($fmt, $ts) : @strftime($fmt, $ts);
	}
	else return 'Error date into a not supported range';
}

/**
 *	Output date in a string format according to outputlangs (or langs if not defined).
 * 	Return charset is always UTF-8, except if encodetoouput is defined. In this case charset is output charset
 *
 *	@param	int			$time			GM Timestamps date
 *	@param	string		$format      	Output date format (tag of strftime function)
 *										"%d %b %Y",
 *										"%d/%m/%Y %H:%M",
 *										"%d/%m/%Y %H:%M:%S",
 *                                      "%B"=Long text of month, "%A"=Long text of day, "%b"=Short text of month, "%a"=Short text of day
 *										"day", "daytext", "dayhour", "dayhourldap", "dayhourtext", "dayrfc", "dayhourrfc", "...reduceformat"
 * 	@param	string		$tzoutput		true or 'gmt' => string is for Greenwich location
 * 										false or 'tzserver' => output string is for local PHP server TZ usage
 * 										'tzuser' => output string is for user TZ (current browser TZ with current dst) => In a future, we should have same behaviour than 'tzuserrel'
 *                                      'tzuserrel' => output string is for user TZ (current browser TZ with dst or not, depending on date position) (TODO not implemented yet)
 *	@param	Translate	$outputlangs	Object lang that contains language for text translation.
 *  @param  boolean		$encodetooutput false=no convert into output pagecode
 * 	@return string      				Formated date or '' if time is null
 *
 *  @see        dol_mktime(), dol_stringtotime(), dol_getdate()
 */
function dol_print_date($time, $format = '', $tzoutput = 'tzserver', $outputlangs = '', $encodetooutput = false)
{
	global $conf, $langs;

	// Clean parameters
	$to_gmt = false;
	$offsettz = $offsetdst = 0;
	if ($tzoutput)
	{
		$to_gmt = true; // For backward compatibility
		if (is_string($tzoutput))
		{
			if ($tzoutput == 'tzserver')
			{
				$to_gmt = false;
				$offsettzstring = @date_default_timezone_get(); // Example 'Europe/Berlin' or 'Indian/Reunion'
				$offsettz = 0;
				$offsetdst = 0;
			}
			elseif ($tzoutput == 'tzuser' || $tzoutput == 'tzuserrel')
			{
				$to_gmt = true;
				$offsettzstring = (empty($_SESSION['dol_tz_string']) ? 'UTC' : $_SESSION['dol_tz_string']); // Example 'Europe/Berlin' or 'Indian/Reunion'
				$offsettz = (empty($_SESSION['dol_tz']) ? 0 : $_SESSION['dol_tz']) * 60 * 60; // Will not be used anymore
				$offsetdst = (empty($_SESSION['dol_dst']) ? 0 : $_SESSION['dol_dst']) * 60 * 60; // Will not be used anymore
			}
		}
	}
	if (!is_object($outputlangs)) $outputlangs = $langs;
	if (!$format) $format = 'daytextshort';
	$reduceformat = (!empty($conf->dol_optimize_smallscreen) && in_array($format, array('day', 'dayhour'))) ? 1 : 0;
	$formatwithoutreduce = preg_replace('/reduceformat/', '', $format);
	if ($formatwithoutreduce != $format) { $format = $formatwithoutreduce; $reduceformat = 1; }  // so format 'dayreduceformat' is processed like day

	// Change predefined format into computer format. If found translation in lang file we use it, otherwise we use default.
	// TODO Add format daysmallyear and dayhoursmallyear
	if ($format == 'day')				$format = ($outputlangs->trans("FormatDateShort") != "FormatDateShort" ? $outputlangs->trans("FormatDateShort") : $conf->format_date_short);
	elseif ($format == 'hour')			$format = ($outputlangs->trans("FormatHourShort") != "FormatHourShort" ? $outputlangs->trans("FormatHourShort") : $conf->format_hour_short);
	elseif ($format == 'hourduration')	$format = ($outputlangs->trans("FormatHourShortDuration") != "FormatHourShortDuration" ? $outputlangs->trans("FormatHourShortDuration") : $conf->format_hour_short_duration);
	elseif ($format == 'daytext')			 $format = ($outputlangs->trans("FormatDateText") != "FormatDateText" ? $outputlangs->trans("FormatDateText") : $conf->format_date_text);
	elseif ($format == 'daytextshort')	$format = ($outputlangs->trans("FormatDateTextShort") != "FormatDateTextShort" ? $outputlangs->trans("FormatDateTextShort") : $conf->format_date_text_short);
	elseif ($format == 'dayhour')			 $format = ($outputlangs->trans("FormatDateHourShort") != "FormatDateHourShort" ? $outputlangs->trans("FormatDateHourShort") : $conf->format_date_hour_short);
	elseif ($format == 'dayhoursec')		 $format = ($outputlangs->trans("FormatDateHourSecShort") != "FormatDateHourSecShort" ? $outputlangs->trans("FormatDateHourSecShort") : $conf->format_date_hour_sec_short);
	elseif ($format == 'dayhourtext')		 $format = ($outputlangs->trans("FormatDateHourText") != "FormatDateHourText" ? $outputlangs->trans("FormatDateHourText") : $conf->format_date_hour_text);
	elseif ($format == 'dayhourtextshort') $format = ($outputlangs->trans("FormatDateHourTextShort") != "FormatDateHourTextShort" ? $outputlangs->trans("FormatDateHourTextShort") : $conf->format_date_hour_text_short);
	// Format not sensitive to language
	elseif ($format == 'dayhourlog')		 $format = '%Y%m%d%H%M%S';
	elseif ($format == 'dayhourldap')		 $format = '%Y%m%d%H%M%SZ';
	elseif ($format == 'dayhourxcard')	$format = '%Y%m%dT%H%M%SZ';
	elseif ($format == 'dayxcard')	 	$format = '%Y%m%d';
	elseif ($format == 'dayrfc')			 $format = '%Y-%m-%d'; // DATE_RFC3339
	elseif ($format == 'dayhourrfc')		 $format = '%Y-%m-%dT%H:%M:%SZ'; // DATETIME RFC3339
	elseif ($format == 'standard')		$format = '%Y-%m-%d %H:%M:%S';

	if ($reduceformat)
	{
		$format = str_replace('%Y', '%y', $format);
		$format = str_replace('yyyy', 'yy', $format);
	}

	// If date undefined or "", we return ""
	if (dol_strlen($time) == 0) return ''; // $time=0 allowed (it means 01/01/1970 00:00:00)

	// Clean format
	if (preg_match('/%b/i', $format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format = str_replace('%b', '__b__', $format);
		$format = str_replace('%B', '__B__', $format);
	}
	if (preg_match('/%a/i', $format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format = str_replace('%a', '__a__', $format);
		$format = str_replace('%A', '__A__', $format);
	}


	// Analyze date
	$reg = array();
	if (preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])$/i', $time, $reg))	// Deprecated. Ex: 1970-01-01, 1970-01-01 01:00:00, 19700101010000
	{
	    dol_print_error("Functions.lib::dol_print_date function called with a bad value from page ".$_SERVER["PHP_SELF"]);
	    return '';
	}
	elseif (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i', $time, $reg))    // Still available to solve problems in extrafields of type date
	{
	    // This part of code should not be used.
	    dol_syslog("Functions.lib::dol_print_date function called with a bad value from page ".$_SERVER["PHP_SELF"], LOG_WARNING);
	    //if (function_exists('debug_print_backtrace')) debug_print_backtrace();
	    // Date has format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'
	    $syear	= (!empty($reg[1]) ? $reg[1] : '');
	    $smonth = (!empty($reg[2]) ? $reg[2] : '');
	    $sday	= (!empty($reg[3]) ? $reg[3] : '');
	    $shour	= (!empty($reg[4]) ? $reg[4] : '');
	    $smin	= (!empty($reg[5]) ? $reg[5] : '');
	    $ssec	= (!empty($reg[6]) ? $reg[6] : '');

	    $time = dol_mktime($shour, $smin, $ssec, $smonth, $sday, $syear, true);
	    $ret = adodb_strftime($format, $time + $offsettz + $offsetdst, $to_gmt);
	}
	else
	{
		// Date is a timestamps
		if ($time < 100000000000)	// Protection against bad date values
		{
			$timetouse = $time + $offsettz + $offsetdst; // TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.

			$ret = adodb_strftime($format, $timetouse, $to_gmt);
		}
		else $ret = 'Bad value '.$time.' for date';
	}

	if (preg_match('/__b__/i', $format))
	{
		$timetouse = $time + $offsettz + $offsetdst; // TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.

		// Here ret is string in PHP setup language (strftime was used). Now we convert to $outputlangs.
		$month = adodb_strftime('%m', $timetouse);
		$month = sprintf("%02d", $month); // $month may be return with format '06' on some installation and '6' on other, so we force it to '06'.
		if ($encodetooutput)
		{
			$monthtext = $outputlangs->transnoentities('Month'.$month);
			$monthtextshort = $outputlangs->transnoentities('MonthShort'.$month);
		}
		else
		{
			$monthtext = $outputlangs->transnoentitiesnoconv('Month'.$month);
			$monthtextshort = $outputlangs->transnoentitiesnoconv('MonthShort'.$month);
		}
		//print 'monthtext='.$monthtext.' monthtextshort='.$monthtextshort;
		$ret = str_replace('__b__', $monthtextshort, $ret);
		$ret = str_replace('__B__', $monthtext, $ret);
		//print 'x'.$outputlangs->charset_output.'-'.$ret.'x';
		//return $ret;
	}
	if (preg_match('/__a__/i', $format))
	{
		$timetouse = $time + $offsettz + $offsetdst; // TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.

		$w = adodb_strftime('%w', $timetouse); // TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.
		$dayweek = $outputlangs->transnoentitiesnoconv('Day'.$w);
		$ret = str_replace('__A__', $dayweek, $ret);
		$ret = str_replace('__a__', dol_substr($dayweek, 0, 3), $ret);
	}

	return $ret;
}


/**
 *  Return an array with locale date info.
 *  PHP getdate is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 *  WARNING: This function always use PHP server timezone to return locale informations !!!
 *  Usage must be avoid.
 *  FIXME: Replace this with PHP date function and a parameter $gm
 *
 *	@param	int			$timestamp      Timestamp
 *	@param	boolean		$fast           Fast mode
 *	@return	array						Array of informations
 *										If no fast mode:
 *										'seconds' => $secs,
 *										'minutes' => $min,
 *										'hours' => $hour,
 *										'mday' => $day,
 *										'wday' => $dow,		0=sunday, 6=saturday
 *										'mon' => $month,
 *										'year' => $year,
 *										'yday' => floor($secsInYear/$_day_power),
 *										'weekday' => gmdate('l',$_day_power*(3+$dow)),
 *										'month' => gmdate('F',mktime(0,0,0,$month,2,1971)),
 *										If fast mode:
 *										'seconds' => $secs,
 *										'minutes' => $min,
 *										'hours' => $hour,
 *										'mday' => $day,
 *										'mon' => $month,
 *										'year' => $year,
 *										'yday' => floor($secsInYear/$_day_power),
 *										'leap' => $leaf,
 *										'ndays' => $ndays
 * 	@see 								dol_print_date(), dol_stringtotime(), dol_mktime()
 */
function dol_getdate($timestamp, $fast = false)
{
	global $conf;

	$usealternatemethod = false;
	if ($timestamp <= 0) $usealternatemethod = true; // <= 1970
	if ($timestamp >= 2145913200) $usealternatemethod = true; // >= 2038

	if ($usealternatemethod)
	{
		$arrayinfo = adodb_getdate($timestamp, $fast);
	}
	else
	{
		$arrayinfo = getdate($timestamp);
	}

	return $arrayinfo;
}

/**
 *	Return a timestamp date built from detailed informations (by default a local PHP server timestamp)
 * 	Replace function mktime not available under Windows if year < 1970
 *	PHP mktime is restricted to the years 1901-2038 on Unix and 1970-2038 on Windows
 *
 * 	@param	int			$hour			Hour	(can be -1 for undefined)
 *	@param	int			$minute			Minute	(can be -1 for undefined)
 *	@param	int			$second			Second	(can be -1 for undefined)
 *	@param	int			$month			Month (1 to 12)
 *	@param	int			$day			Day (1 to 31)
 *	@param	int			$year			Year
 *	@param	mixed		$gm				True or 1 or 'gmt'=Input informations are GMT values
 *										False or 0 or 'server' = local to server TZ
 *										'user' = local to user TZ
 *										'tz,TimeZone' = use specified timezone
 *	@param	int			$check			0=No check on parameters (Can use day 32, etc...)
 *	@return	int|string					Date as a timestamp, '' or false if error
 * 	@see 								dol_print_date(), dol_stringtotime(), dol_getdate()
 */
function dol_mktime($hour, $minute, $second, $month, $day, $year, $gm = false, $check = 1)
{
	global $conf;
	//print "- ".$hour.",".$minute.",".$second.",".$month.",".$day.",".$year.",".$_SERVER["WINDIR"]." -";

	// Clean parameters
	if ($hour == -1 || empty($hour)) $hour = 0;
	if ($minute == -1 || empty($minute)) $minute = 0;
	if ($second == -1 || empty($second)) $second = 0;

	// Check parameters
	if ($check)
	{
		if (!$month || !$day)  return '';
		if ($day > 31) return '';
		if ($month > 12) return '';
		if ($hour < 0 || $hour > 24) return '';
		if ($minute < 0 || $minute > 60) return '';
		if ($second < 0 || $second > 60) return '';
	}

	if (empty($gm) || $gm === 'server')
	{
		$default_timezone = @date_default_timezone_get(); // Example 'Europe/Berlin'
		$localtz = new DateTimeZone($default_timezone);
	}
	elseif ($gm === 'user')
	{
		// We use dol_tz_string first because it is more reliable.
		$default_timezone = (empty($_SESSION["dol_tz_string"]) ? @date_default_timezone_get() : $_SESSION["dol_tz_string"]); // Example 'Europe/Berlin'
		try {
			$localtz = new DateTimeZone($default_timezone);
		}
		catch (Exception $e)
		{
			dol_syslog("Warning dol_tz_string contains an invalid value ".$_SESSION["dol_tz_string"], LOG_WARNING);
			$default_timezone = @date_default_timezone_get();
		}
	}
	elseif (strrpos($gm, "tz,") !== false)
	{
		$timezone = str_replace("tz,", "", $gm); // Example 'tz,Europe/Berlin'
		try
		{
			$localtz = new DateTimeZone($timezone);
		}
		catch (Exception $e)
		{
			dol_syslog("Warning passed timezone contains an invalid value ".$timezone, LOG_WARNING);
		}
	}

	if (empty($localtz)) {
		$localtz = new DateTimeZone('UTC');
	}
	//var_dump($localtz);
	//var_dump($year.'-'.$month.'-'.$day.'-'.$hour.'-'.$minute);
	$dt = new DateTime(null, $localtz);
	$dt->setDate((int) $year, (int) $month, (int) $day);
	$dt->setTime((int) $hour, (int) $minute, (int) $second);
	$date = $dt->getTimestamp(); // should include daylight saving time
	//var_dump($date);
	return $date;
}


/**
 *  Return date for now. In most cases, we use this function without parameters (that means GMT time).
 *
 *  @param	string		$mode	'gmt' => we return GMT timestamp,
 * 								'tzserver' => we add the PHP server timezone
 *  							'tzref' => we add the company timezone
 * 								'tzuser' => we add the user timezone
 *	@return int   $date	Timestamp
 */
function dol_now($mode = 'gmt')
{
	$ret = 0;

	// Note that gmmktime and mktime return same value (GMT) when used without parameters
	//if ($mode == 'gmt') $ret=gmmktime(); // Strict Standards: gmmktime(): You should be using the time() function instead
	if ($mode == 'gmt') $ret = time(); // Time for now at greenwich.
	elseif ($mode == 'tzserver')		// Time for now with PHP server timezone added
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$tzsecond = getServerTimeZoneInt('now'); // Contains tz+dayling saving time
		$ret = (int) (dol_now('gmt') + ($tzsecond * 3600));
	}
	/*else if ($mode == 'tzref')				// Time for now with parent company timezone is added
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$tzsecond=getParentCompanyTimeZoneInt();    // Contains tz+dayling saving time
		$ret=dol_now('gmt')+($tzsecond*3600);
	}*/
	elseif ($mode == 'tzuser')				// Time for now with user timezone added
	{
		//print 'time: '.time().'-'.mktime().'-'.gmmktime();
		$offsettz = (empty($_SESSION['dol_tz']) ? 0 : $_SESSION['dol_tz']) * 60 * 60;
		$offsetdst = (empty($_SESSION['dol_dst']) ? 0 : $_SESSION['dol_dst']) * 60 * 60;
		$ret = (int) (dol_now('gmt') + ($offsettz + $offsetdst));
	}

	return $ret;
}


/**
 * Return string with formated size
 *
 * @param	int		$size		Size to print
 * @param	int		$shortvalue	Tell if we want long value to use another unit (Ex: 1.5Kb instead of 1500b)
 * @param	int		$shortunit	Use short label of size unit (for example 'b' instead of 'bytes')
 * @return	string				Link
 */
function dol_print_size($size, $shortvalue = 0, $shortunit = 0)
{
	global $conf, $langs;
	$level = 1024;

	if (!empty($conf->dol_optimize_smallscreen)) $shortunit = 1;

	// Set value text
	if (empty($shortvalue) || $size < ($level * 10))
	{
		$ret = $size;
		$textunitshort = $langs->trans("b");
		$textunitlong = $langs->trans("Bytes");
	}
	else
	{
		$ret = round($size / $level, 0);
		$textunitshort = $langs->trans("Kb");
		$textunitlong = $langs->trans("KiloBytes");
	}
	// Use long or short text unit
	if (empty($shortunit)) { $ret .= ' '.$textunitlong; }
	else { $ret .= ' '.$textunitshort; }

	return $ret;
}

/**
 * Show Url link
 *
 * @param	string		$url		Url to show
 * @param	string		$target		Target for link
 * @param	int			$max		Max number of characters to show
 * @param	int			$withpicto	With picto
 * @return	string					HTML Link
 */
function dol_print_url($url, $target = '_blank', $max = 32, $withpicto = 0)
{
	global $langs;

	if (empty($url)) return '';

	$link = '<a href="';
	if (!preg_match('/^http/i', $url)) $link .= 'http://';
	$link .= $url;
	$link .= '"';
	if ($target) $link .= ' target="'.$target.'"';
	$link .= '>';
	if (!preg_match('/^http/i', $url)) $link .= 'http://';
	$link .= dol_trunc($url, $max);
	$link .= '</a>';
	return '<div class="nospan float" style="margin-right: 10px">'.($withpicto ?img_picto($langs->trans("Url"), 'globe').' ' : '').$link.'</div>';
}

/**
 * Show EMail link
 *
 * @param	string		$email			EMail to show (only email, without 'Name of recipient' before)
 * @param 	int			$cid 			Id of contact if known
 * @param 	int			$socid 			Id of third party if known
 * @param 	int			$addlink		0=no link, 1=email has a html email link (+ link to create action if constant AGENDA_ADDACTIONFOREMAIL is on)
 * @param	int			$max			Max number of characters to show
 * @param	int			$showinvalid	1=Show warning if syntax email is wrong
 * @param	int			$withpicto		Show picto
 * @return	string						HTML Link
 */
function dol_print_email($email, $cid = 0, $socid = 0, $addlink = 0, $max = 64, $showinvalid = 1, $withpicto = 0)
{
	global $conf, $user, $langs, $hookmanager;

	$newemail = $email;

	if (empty($email)) return '&nbsp;';

	if (!empty($addlink))
	{
		$newemail = '<a style="text-overflow: ellipsis;" href="';
		if (!preg_match('/^mailto:/i', $email)) $newemail .= 'mailto:';
		$newemail .= $email;
		$newemail .= '">';
		$newemail .= dol_trunc($email, $max);
		$newemail .= '</a>';
		if ($showinvalid && !isValidEmail($email))
		{
			$langs->load("errors");
			$newemail .= img_warning($langs->trans("ErrorBadEMail", $email));
		}

		if (($cid || $socid) && !empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type = 'AC_EMAIL'; $link = '';
			if (!empty($conf->global->AGENDA_ADDACTIONFOREMAIL)) $link = '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"), "calendar").'</a>';
			if ($link) $newemail = '<div>'.$newemail.' '.$link.'</div>';
		}
	}
	else
	{
		if ($showinvalid && !isValidEmail($email))
		{
			$langs->load("errors");
			$newemail .= img_warning($langs->trans("ErrorBadEMail", $email));
		}
	}

	$rep = '<div class="nospan" style="margin-right: 10px">'.($withpicto ?img_picto($langs->trans("EMail"), 'object_email.png').' ' : '').$newemail.'</div>';
	if ($hookmanager) {
		$parameters = array('cid' => $cid, 'socid' => $socid, 'addlink' => $addlink, 'picto' => $withpicto);
		$reshook = $hookmanager->executeHooks('printEmail', $parameters, $email);
		$rep .= $hookmanager->resPrint;
	}

	return $rep;
}

/**
 * Get array of social network dictionary
 *
 * @return  array       Array of Social Networks Dictionary
 */
function getArrayOfSocialNetworks()
{
    global $conf, $db;
    $sql = "SELECT rowid, code, label, url, icon, active FROM ".MAIN_DB_PREFIX."c_socialnetworks";
    $sql .= " WHERE entity=".$conf->entity;
    $socialnetworks = array();
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $socialnetworks[$obj->code] = array(
                'rowid' => $obj->rowid,
                'label' => $obj->label,
                'url' => $obj->url,
                'icon' => $obj->icon,
                'active' => $obj->active,
            );
        }
    }
    return $socialnetworks;
}

/**
 * Show social network link
 *
 * @param	string		$value			Skype to show (only skype, without 'Name of recipient' before)
 * @param	int 		$cid 			Id of contact if known
 * @param	int 		$socid 			Id of third party if known
 * @param	string 		$type			'skype','facebook',...
 * @return	string						HTML Link
 */
function dol_print_socialnetworks($value, $cid, $socid, $type)
{
	global $conf, $user, $langs;

	$htmllink = $value;

	if (empty($value)) return '&nbsp;';

	if (!empty($type))
	{
		$htmllink = '<div class="divsocialnetwork inline-block valignmiddle">';
		$htmllink .= img_picto($langs->trans(strtoupper($type)), $type.'.png', '', false, 0, 0, '', 'paddingright', 0);
		$htmllink .= $value;
		if ($type == 'skype')
		{
			$htmllink .= '&nbsp;';
			$htmllink .= '<a href="skype:';
			$htmllink .= $value;
			$htmllink .= '?call" alt="'.$langs->trans("Call").'&nbsp;'.$value.'" title="'.$langs->trans("Call").'&nbsp;'.$value.'">';
			$htmllink .= '<img src="'.DOL_URL_ROOT.'/theme/common/skype_callbutton.png" border="0">';
			$htmllink .= '</a><a href="skype:';
			$htmllink .= $value;
			$htmllink .= '?chat" alt="'.$langs->trans("Chat").'&nbsp;'.$value.'" title="'.$langs->trans("Chat").'&nbsp;'.$value.'">';
			$htmllink .= '<img class="paddingleft" src="'.DOL_URL_ROOT.'/theme/common/skype_chatbutton.png" border="0">';
			$htmllink .= '</a>';
		}
		if (($cid || $socid) && !empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create && $type == 'skype')
		{
			$addlink = 'AC_SKYPE';
			$link = '';
			if (!empty($conf->global->AGENDA_ADDACTIONFORSKYPE)) $link = '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$addlink.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"), "calendar").'</a>';
			$htmllink .= ($link ? ' '.$link : '');
		}
		$htmllink .= '</div>';
	}
	else
	{
		$langs->load("errors");
		$htmllink .= img_warning($langs->trans("ErrorBadSocialNetworkValue", $value));
	}
	return $htmllink;
}

/**
 * 	Format phone numbers according to country
 *
 * 	@param  string  $phone          Phone number to format
 * 	@param  string  $countrycode    Country code to use for formatting
 * 	@param 	int		$cid 		    Id of contact if known
 * 	@param 	int		$socid          Id of third party if known
 * 	@param 	string	$addlink	    ''=no link to create action, 'AC_TEL'=add link to clicktodial (if module enabled) and add link to create event (if conf->global->AGENDA_ADDACTIONFORPHONE set)
 * 	@param 	string	$separ 		    Separation between numbers for a better visibility example : xx.xx.xx.xx.xx
 *  @param	string  $withpicto      Show picto
 *  @param	string	$titlealt	    Text to show on alt
 *  @param  int     $adddivfloat    Add div float around phone.
 * 	@return string 				    Formated phone number
 */
function dol_print_phone($phone, $countrycode = '', $cid = 0, $socid = 0, $addlink = '', $separ = "&nbsp;", $withpicto = '', $titlealt = '', $adddivfloat = 0)
{
	global $conf, $user, $langs, $mysoc, $hookmanager;

	// Clean phone parameter
	$phone = preg_replace("/[\s.-]/", "", trim($phone));
	if (empty($phone)) { return ''; }
	if (empty($countrycode)) $countrycode = $mysoc->country_code;

	// Short format for small screens
	if ($conf->dol_optimize_smallscreen) $separ = '';

	$newphone = $phone;
	if (strtoupper($countrycode) == "FR")
	{
		// France
		if (dol_strlen($phone) == 10) {
			$newphone = substr($newphone, 0, 2).$separ.substr($newphone, 2, 2).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2);
		}
		elseif (dol_strlen($phone) == 7)
		{
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 2);
		}
		elseif (dol_strlen($phone) == 9)
		{
			$newphone = substr($newphone, 0, 2).$separ.substr($newphone, 2, 3).$separ.substr($newphone, 5, 2).$separ.substr($newphone, 7, 2);
		}
		elseif (dol_strlen($phone) == 11)
		{
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 2).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
		elseif (dol_strlen($phone) == 12)
		{
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}

	elseif (strtoupper($countrycode) == "CA")
	{
		if (dol_strlen($phone) == 10) {
			$newphone = ($separ != '' ? '(' : '').substr($newphone, 0, 3).($separ != '' ? ')' : '').$separ.substr($newphone, 3, 3).($separ != '' ? '-' : '').substr($newphone, 6, 4);
		}
	}
	elseif (strtoupper($countrycode) == "PT")
	{//Portugal
		if (dol_strlen($phone) == 13)
		{//ex: +351_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "SR")
	{//Suriname
		if (dol_strlen($phone) == 10)
		{//ex: +597_ABC_DEF
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3);
		}
		elseif (dol_strlen($phone) == 11)
		{//ex: +597_ABC_DEFG
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 4);
		}
	}
	elseif (strtoupper($countrycode) == "DE")
	{//Allemagne
		if (dol_strlen($phone) == 14)
		{//ex:  +49_ABCD_EFGH_IJK
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 4).$separ.substr($newphone, 7, 4).$separ.substr($newphone, 11, 3);
		}
		elseif (dol_strlen($phone) == 13)
		{//ex: +49_ABC_DEFG_HIJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 4).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "ES")
	{//Espagne
		if (dol_strlen($phone) == 12)
		{//ex:  +34_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "BF")
	{// Burkina Faso
		if (dol_strlen($phone) == 12)
		{//ex :  +22 A BC_DE_FG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 1).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "RO")
	{// Roumanie
		if (dol_strlen($phone) == 12)
		{//ex :  +40 AB_CDE_FG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "TR")
	{//Turquie
		if (dol_strlen($phone) == 13)
		{//ex :  +90 ABC_DEF_GHIJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 4);
		}
	}
	elseif (strtoupper($countrycode) == "US")
	{//Etat-Unis
		if (dol_strlen($phone) == 12)
		{//ex: +1 ABC_DEF_GHIJ
			$newphone = substr($newphone, 0, 2).$separ.substr($newphone, 2, 3).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 4);
		}
	}
	elseif (strtoupper($countrycode) == "MX")
	{//Mexique
		if (dol_strlen($phone) == 12)
		{//ex: +52 ABCD_EFG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 4).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 2);
		}
		elseif (dol_strlen($phone) == 11)
		{//ex: +52 AB_CD_EF_GH
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 2).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
		elseif (dol_strlen($phone) == 13)
		{//ex: +52 ABC_DEF_GHIJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 4);
		}
	}
	elseif (strtoupper($countrycode) == "ML")
	{//Mali
		if (dol_strlen($phone) == 12)
		{//ex: +223 AB_CD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "TH")
	{//Thaïlande
		if (dol_strlen($phone) == 11)
		{//ex: +66_ABC_DE_FGH
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 3);
		}
		elseif (dol_strlen($phone) == 12)
		{//ex: +66_A_BCD_EF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 1).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "MU")
	{
        //Maurice
		if (dol_strlen($phone) == 11)
		{//ex: +230_ABC_DE_FG
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
		elseif (dol_strlen($phone) == 12)
		{//ex: +230_ABCD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 4).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "ZA")
	{//Afrique du sud
		if (dol_strlen($phone) == 12)
		{//ex: +27_AB_CDE_FG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "SY")
	{//Syrie
		if (dol_strlen($phone) == 12)
		{//ex: +963_AB_CD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
		elseif (dol_strlen($phone) == 13)
		{//ex: +963_AB_CD_EF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "AE")
	{//Emirats Arabes Unis
		if (dol_strlen($phone) == 12)
		{//ex: +971_ABC_DEF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 2);
		}
		elseif (dol_strlen($phone) == 13)
		{//ex: +971_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
		elseif (dol_strlen($phone) == 14)
		{//ex: +971_ABC_DEF_GHIK
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 4);
		}
	}
	elseif (strtoupper($countrycode) == "DZ")
	{//Algérie
		if (dol_strlen($phone) == 13)
		{//ex: +213_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "BE")
	{//Belgique
		if (dol_strlen($phone) == 11)
		{//ex: +32_ABC_DE_FGH
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 3);
		}
		elseif (dol_strlen($phone) == 12)
		{//ex: +32_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "PF")
	{//Polynésie française
		if (dol_strlen($phone) == 12)
		{//ex: +689_AB_CD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "CO")
	{//Colombie
		if (dol_strlen($phone) == 13)
		{//ex: +57_ABC_DEF_GH_IJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "JO")
	{//Jordanie
		if (dol_strlen($phone) == 12)
		{//ex: +962_A_BCD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 1).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
	}
	elseif (strtoupper($countrycode) == "JM")
	{//Jamaïque
		if (dol_strlen($newphone) == 12)
		{//ex: +1867_ABC_DEFG
			$newphone = substr($newphone, 0, 5).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 4);
		}
	}
	elseif (strtoupper($countrycode) == "MG")
	{//Madagascar
		if (dol_strlen($phone) == 13)
		{//ex: +261_AB_CD_EF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "GB")
	{//Royaume uni
		if (dol_strlen($phone) == 13)
		{//ex: +44_ABCD_EFG_HIJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 4).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "CH")
	{//Suisse
		if (dol_strlen($phone) == 12)
		{//ex: +41_AB_CDE_FG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
		elseif (dol_strlen($phone) == 15)
		{// +41_AB_CDE_FGH_IJKL
			$newphone = $newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 3).$separ.substr($newphone, 11, 4);
		}
	}
	elseif (strtoupper($countrycode) == "TN")
	{//Tunisie
		if (dol_strlen($phone) == 12)
		{//ex: +216_AB_CDE_FGH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "GF")
	{//Guyane francaise
		if (dol_strlen($phone) == 13)
		{//ex: +594_ABC_DE_FG_HI  (ABC=594 de nouveau)
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "GP")
	{//Guadeloupe
		if (dol_strlen($phone) == 13)
		{//ex: +590_ABC_DE_FG_HI  (ABC=590 de nouveau)
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "MQ")
	{//Martinique
		if (dol_strlen($phone) == 13)
		{//ex: +596_ABC_DE_FG_HI  (ABC=596 de nouveau)
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "IT")
	{//Italie
		if (dol_strlen($phone) == 12)
		{//ex: +39_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
		elseif (dol_strlen($phone) == 13)
		{//ex: +39_ABC_DEF_GH_IJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "AU")
	{
        //Australie
		if (dol_strlen($phone) == 12)
		{
            //ex: +61_A_BCDE_FGHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 1).$separ.substr($newphone, 4, 4).$separ.substr($newphone, 8, 4);
		}
	}
	if (!empty($addlink))	// Link on phone number (+ link to add action if conf->global->AGENDA_ADDACTIONFORPHONE set)
	{
		if ($conf->browser->layout == 'phone' || (!empty($conf->clicktodial->enabled) && !empty($conf->global->CLICKTODIAL_USE_TEL_LINK_ON_PHONE_NUMBERS)))	// If phone or option for, we use link of phone
		{
			$newphone = '<a href="tel:'.$phone.'"';
			$newphone .= '>'.$phone.'</a>';
		}
		elseif (!empty($conf->clicktodial->enabled) && $addlink == 'AC_TEL')		// If click to dial, we use click to dial url
		{
			if (empty($user->clicktodial_loaded)) $user->fetch_clicktodial();

			// Define urlmask
			$urlmask = 'ErrorClickToDialModuleNotConfigured';
			if (!empty($conf->global->CLICKTODIAL_URL)) $urlmask = $conf->global->CLICKTODIAL_URL;
			if (!empty($user->clicktodial_url)) $urlmask = $user->clicktodial_url;

			$clicktodial_poste = (!empty($user->clicktodial_poste) ?urlencode($user->clicktodial_poste) : '');
			$clicktodial_login = (!empty($user->clicktodial_login) ?urlencode($user->clicktodial_login) : '');
			$clicktodial_password = (!empty($user->clicktodial_password) ?urlencode($user->clicktodial_password) : '');
			// This line is for backward compatibility
			$url = sprintf($urlmask, urlencode($phone), $clicktodial_poste, $clicktodial_login, $clicktodial_password);
			// Thoose lines are for substitution
			$substitarray = array('__PHONEFROM__'=>$clicktodial_poste,
								'__PHONETO__'=>urlencode($phone),
								'__LOGIN__'=>$clicktodial_login,
								'__PASS__'=>$clicktodial_password);
			$url = make_substitutions($url, $substitarray);
			$newphonesav = $newphone;
			$newphone = '<a href="'.$url.'"';
			if (!empty($conf->global->CLICKTODIAL_FORCENEWTARGET)) $newphone .= ' target="_blank"';
			$newphone .= '>'.$newphonesav.'</a>';
		}

		//if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		if (!empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type = 'AC_TEL'; $link = '';
			if ($addlink == 'AC_FAX') $type = 'AC_FAX';
			if (!empty($conf->global->AGENDA_ADDACTIONFORPHONE)) $link = '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.($cid ? '&amp;contactid='.$cid : '').($socid ? '&amp;socid='.$socid : '').'">'.img_object($langs->trans("AddAction"), "calendar").'</a>';
			if ($link) $newphone = '<div>'.$newphone.' '.$link.'</div>';
		}
	}

	if (empty($titlealt))
	{
		$titlealt = ($withpicto == 'fax' ? $langs->trans("Fax") : $langs->trans("Phone"));
	}
	$rep = '';

	if ($hookmanager) {
		$parameters = array('countrycode' => $countrycode, 'cid' => $cid, 'socid' => $socid, 'titlealt' => $titlealt, 'picto' => $withpicto);
		$reshook = $hookmanager->executeHooks('printPhone', $parameters, $phone);
		$rep .= $hookmanager->resPrint;
	}
	if (empty($reshook))
	{
		$picto = '';
		if ($withpicto) {
			if ($withpicto == 'fax') {
				$picto = 'phoning_fax';
			}elseif ($withpicto == 'phone') {
				$picto = 'phoning';
			}elseif ($withpicto == 'mobile') {
				$picto = 'phoning_mobile';
			} else {
				$picto = '';
			}
		}
		if ($adddivfloat) $rep .= '<div class="nospan float" style="margin-right: 10px">';
		else $rep .= '<span style="margin-right: 10px;">';
		$rep .= ($withpicto ?img_picto($titlealt, 'object_'.$picto.'.png').' ' : '').$newphone;
		if ($adddivfloat) $rep .= '</div>';
		else $rep .= '</span>';
	}

	return $rep;
}

/**
 * 	Return an IP formated to be shown on screen
 *
 * 	@param	string	$ip			IP
 * 	@param	int		$mode		0=return IP + country/flag, 1=return only country/flag, 2=return only IP
 * 	@return string 				Formated IP, with country if GeoIP module is enabled
 */
function dol_print_ip($ip, $mode = 0)
{
	global $conf, $langs;

	$ret = '';

	if (empty($mode)) $ret .= $ip;

	if ($mode != 2)
	{
		$countrycode = dolGetCountryCodeFromIp($ip);
		if ($countrycode)	// If success, countrycode is us, fr, ...
		{
			if (file_exists(DOL_DOCUMENT_ROOT.'/theme/common/flags/'.$countrycode.'.png'))
			{
				$ret .= ' '.img_picto($countrycode.' '.$langs->trans("AccordingToGeoIPDatabase"), DOL_URL_ROOT.'/theme/common/flags/'.$countrycode.'.png', '', 1);
			}
			else $ret .= ' ('.$countrycode.')';
		}
		else
		{
			// Nothing
		}
	}

	return $ret;
}

/**
 * Return the IP of remote user.
 * Take HTTP_X_FORWARDED_FOR (defined when using proxy)
 * Then HTTP_CLIENT_IP if defined (rare)
 * Then REMOTE_ADDR (no way to be modified by user but may be wrong if user is using a proxy)
 *
 * @return	string		Ip of remote user.
 */
function getUserRemoteIP()
{
	$ip = empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? (empty($_SERVER['HTTP_CLIENT_IP']) ? (empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR']) : $_SERVER['HTTP_CLIENT_IP']) : $_SERVER['HTTP_X_FORWARDED_FOR'];
	return $ip;
}

/**
 * 	Return a country code from IP. Empty string if not found.
 *
 * 	@param	string	$ip			IP
 * 	@return string 				Country code ('us', 'fr', ...)
 */
function dolGetCountryCodeFromIp($ip)
{
	global $conf;

	$countrycode = '';

	if (!empty($conf->geoipmaxmind->enabled))
	{
		$datafile = $conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;
		//$ip='24.24.24.24';
		//$datafile='/usr/share/GeoIP/GeoIP.dat';    Note that this must be downloaded datafile (not same than datafile provided with ubuntu packages)
		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
		$geoip = new DolGeoIP('country', $datafile);
		//print 'ip='.$ip.' databaseType='.$geoip->gi->databaseType." GEOIP_CITY_EDITION_REV1=".GEOIP_CITY_EDITION_REV1."\n";
		$countrycode = $geoip->getCountryCodeFromIP($ip);
	}

	return $countrycode;
}


/**
 *  Return country code for current user.
 *  If software is used inside a local network, detection may fails (we need a public ip)
 *
 *  @return     string      Country code (fr, es, it, us, ...)
 */
function dol_user_country()
{
	global $conf, $langs, $user;

	//$ret=$user->xxx;
	$ret = '';
	if (!empty($conf->geoipmaxmind->enabled))
	{
		$ip = getUserRemoteIP();
		$datafile = $conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;
		//$ip='24.24.24.24';
		//$datafile='E:\Mes Sites\Web\Admin1\awstats\maxmind\GeoIP.dat';
		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
		$geoip = new DolGeoIP('country', $datafile);
		$countrycode = $geoip->getCountryCodeFromIP($ip);
		$ret = $countrycode;
	}
	return $ret;
}

/**
 *  Format address string
 *
 *  @param	string	$address    Address
 *  @param  int		$htmlid     Html ID (for example 'gmap')
 *  @param  int		$mode       thirdparty|contact|member|other
 *  @param  int		$id         Id of object
 *  @param	int		$noprint	No output. Result is the function return
 *  @param  string  $charfornl  Char to use instead of nl2br. '' means we use a standad nl2br.
 *  @return string|void			Nothing if noprint is 0, formatted address if noprint is 1
 *  @see dol_format_address()
 */
function dol_print_address($address, $htmlid, $mode, $id, $noprint = 0, $charfornl = '')
{
	global $conf, $user, $langs, $hookmanager;

	$out = '';

	if ($address)
	{
		if ($hookmanager) {
			$parameters = array('element' => $mode, 'id' => $id);
			$reshook = $hookmanager->executeHooks('printAddress', $parameters, $address);
			$out .= $hookmanager->resPrint;
		}
		if (empty($reshook))
		{
			if (empty($charfornl)) $out .= nl2br($address);
			else $out .= preg_replace('/[\r\n]+/', $charfornl, $address);

			$showgmap = $showomap = 0;

			// TODO Add a hook here
			if (($mode == 'thirdparty' || $mode == 'societe') && !empty($conf->google->enabled) && !empty($conf->global->GOOGLE_ENABLE_GMAPS)) $showgmap = 1;
			if ($mode == 'contact' && !empty($conf->google->enabled) && !empty($conf->global->GOOGLE_ENABLE_GMAPS_CONTACTS)) $showgmap = 1;
			if ($mode == 'member' && !empty($conf->google->enabled) && !empty($conf->global->GOOGLE_ENABLE_GMAPS_MEMBERS)) $showgmap = 1;
			if (($mode == 'thirdparty' || $mode == 'societe') && !empty($conf->openstreetmap->enabled) && !empty($conf->global->OPENSTREETMAP_ENABLE_MAPS)) $showomap = 1;
			if ($mode == 'contact' && !empty($conf->openstreetmap->enabled) && !empty($conf->global->OPENSTREETMAP_ENABLE_MAPS_CONTACTS)) $showomap = 1;
			if ($mode == 'member' && !empty($conf->openstreetmap->enabled) && !empty($conf->global->OPENSTREETMAP_ENABLE_MAPS_MEMBERS)) $showomap = 1;

			if ($showgmap)
			{
				$url = dol_buildpath('/google/gmaps.php?mode='.$mode.'&id='.$id, 1);
				$out .= ' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'" class="valigntextbottom" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
			}
			if ($showomap)
			{
				$url = dol_buildpath('/openstreetmap/maps.php?mode='.$mode.'&id='.$id, 1);
				$out .= ' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'_openstreetmap" class="valigntextbottom" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
			}
		}
	}
	if ($noprint) return $out;
	else print $out;
}


/**
 *	Return true if email syntax is ok
 *
 *	@param	    string		$address    			email (Ex: "toto@examle.com", "John Do <johndo@example.com>")
 *  @param		int			$acceptsupervisorkey	If 1, the special string '__SUPERVISOREMAIL__' is also accepted as valid
 *	@return     boolean     						true if email syntax is OK, false if KO or empty string
 */
function isValidEmail($address, $acceptsupervisorkey = 0)
{
	if ($acceptsupervisorkey && $address == '__SUPERVISOREMAIL__') return true;
	if (filter_var($address, FILTER_VALIDATE_EMAIL)) return true;

	return false;
}

/**
 *	Return if the domain name has a valid MX record.
 *  WARNING: This need function idn_to_ascii, checkdnsrr and getmxrr
 *
 *	@param	    string		$domain	    			Domain name (Ex: "yahoo.com", "yhaoo.com", "dolibarr.fr")
 *	@return     int     							-1 if error (function not available), 0=Not valid, 1=Valid
 */
function isValidMXRecord($domain)
{
	if (function_exists('idn_to_ascii') && function_exists('checkdnsrr'))
	{
		if (!checkdnsrr(idn_to_ascii($domain), 'MX'))
		{
			return 0;
		}
		if (function_exists('getmxrr'))
		{
			$mxhosts = array();
			$weight = array();
			getmxrr(idn_to_ascii($domain), $mxhosts, $weight);
			if (count($mxhosts) > 1) return 1;
			if (count($mxhosts) == 1 && !empty($mxhosts[0])) return 1;

			return 0;
		}
	}
	return -1;
}

/**
 *  Return true if phone number syntax is ok
 *  TODO Decide what to do with this
 *
 *  @param	string		$phone		phone (Ex: "0601010101")
 *  @return boolean     			true if phone syntax is OK, false if KO or empty string
 */
function isValidPhone($phone)
{
	return true;
}


/**
 * Make a strlen call. Works even if mbstring module not enabled
 *
 * @param   string		$string				String to calculate length
 * @param   string		$stringencoding		Encoding of string
 * @return  int								Length of string
 */
function dol_strlen($string, $stringencoding = 'UTF-8')
{
	if (function_exists('mb_strlen')) return mb_strlen($string, $stringencoding);
	else return strlen($string);
}

/**
 * Make a substring. Works even if mbstring module is not enabled for better compatibility.
 *
 * @param	string	$string				String to scan
 * @param	string	$start				Start position
 * @param	int		$length				Length (in nb of characters or nb of bytes depending on trunconbytes param)
 * @param   string	$stringencoding		Page code used for input string encoding
 * @param	int		$trunconbytes		1=Length is max of bytes instead of max of characters
 * @return  string						substring
 */
function dol_substr($string, $start, $length, $stringencoding = '', $trunconbytes = 0)
{
	global $langs;

	if (empty($stringencoding)) $stringencoding = $langs->charset_output;

	$ret = '';
	if (empty($trunconbytes))
	{
		if (function_exists('mb_substr'))
		{
			$ret = mb_substr($string, $start, $length, $stringencoding);
		}
		else
		{
			$ret = substr($string, $start, $length);
		}
	}
	else
	{
		if (function_exists('mb_strcut'))
		{
			$ret = mb_strcut($string, $start, $length, $stringencoding);
		}
		else
		{
			$ret = substr($string, $start, $length);
		}
	}
	return $ret;
}


/**
 *	Truncate a string to a particular length adding '...' if string larger than length.
 * 	If length = max length+1, we do no truncate to avoid having just 1 char replaced with '...'.
 *  MAIN_DISABLE_TRUNC=1 can disable all truncings
 *
 *	@param	string	$string				String to truncate
 *	@param  int		$size				Max string size visible (excluding ...). 0 for no limit. WARNING: Final string size can have 3 more chars (if we added ..., or if size was max+1 or max+2 or max+3 so it does not worse to replace with ...)
 *	@param	string	$trunc				Where to trunc: right, left, middle (size must be a 2 power), wrap
 * 	@param	string	$stringencoding		Tell what is source string encoding
 *  @param	int		$nodot				Truncation do not add ... after truncation. So it's an exact truncation.
 *  @param  int     $display            Trunc is used to display data and can be changed for small screen. TODO Remove this param (must be dealt with CSS)
 *	@return string						Truncated string. WARNING: length is never higher than $size if $nodot is set, but can be 3 chars higher otherwise.
 */
function dol_trunc($string, $size = 40, $trunc = 'right', $stringencoding = 'UTF-8', $nodot = 0, $display = 0)
{
	global $conf;

	if ($size == 0 || !empty($conf->global->MAIN_DISABLE_TRUNC)) return $string;

	if (empty($stringencoding)) $stringencoding = 'UTF-8';
	// reduce for small screen
	if ($conf->dol_optimize_smallscreen == 1 && $display == 1) $size = round($size / 3);

	// We go always here
	if ($trunc == 'right')
	{
		$newstring = dol_textishtml($string) ?dol_string_nohtmltag($string, 1) : $string;
		if (dol_strlen($newstring, $stringencoding) > ($size + ($nodot ? 0 : 3)))    // If nodot is 0 and size is 1,2 or 3 chars more, we don't trunc and don't add ...
		return dol_substr($newstring, 0, $size, $stringencoding).($nodot ? '' : '...');
		else
		//return 'u'.$size.'-'.$newstring.'-'.dol_strlen($newstring,$stringencoding).'-'.$string;
		return $string;
	}
	elseif ($trunc == 'middle')
	{
		$newstring = dol_textishtml($string) ?dol_string_nohtmltag($string, 1) : $string;
		if (dol_strlen($newstring, $stringencoding) > 2 && dol_strlen($newstring, $stringencoding) > ($size + 1))
		{
			$size1 = round($size / 2);
			$size2 = round($size / 2);
			return dol_substr($newstring, 0, $size1, $stringencoding).'...'.dol_substr($newstring, dol_strlen($newstring, $stringencoding) - $size2, $size2, $stringencoding);
		}
		else
		return $string;
	}
	elseif ($trunc == 'left')
	{
		$newstring = dol_textishtml($string) ?dol_string_nohtmltag($string, 1) : $string;
		if (dol_strlen($newstring, $stringencoding) > ($size + ($nodot ? 0 : 3)))    // If nodot is 0 and size is 1,2 or 3 chars more, we don't trunc and don't add ...
		return '...'.dol_substr($newstring, dol_strlen($newstring, $stringencoding) - $size, $size, $stringencoding);
		else
		return $string;
	}
	elseif ($trunc == 'wrap')
	{
		$newstring = dol_textishtml($string) ?dol_string_nohtmltag($string, 1) : $string;
		if (dol_strlen($newstring, $stringencoding) > ($size + 1))
		return dol_substr($newstring, 0, $size, $stringencoding)."\n".dol_trunc(dol_substr($newstring, $size, dol_strlen($newstring, $stringencoding) - $size, $stringencoding), $size, $trunc);
		else
		return $string;
	}
	else return 'BadParam3CallingDolTrunc';
}

/**
 *	Show picto whatever it's its name (generic function)
 *
 *	@param      string		$titlealt         		Text on title tag for tooltip. Not used if param notitle is set to 1.
 *	@param      string		$picto       			Name of image file to show ('filenew', ...)
 *													If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
 *                                  				Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                  				Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                  				Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *	@param		string		$moreatt				Add more attribute on img tag (For example 'style="float: right"')
 *	@param		boolean|int	$pictoisfullpath		If true or 1, image path is a full path
 *	@param		int			$srconly				Return only content of the src attribute of img.
 *  @param		int			$notitle				1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *  @param		string		$alt					Force alt for bind people
 *  @param		string		$morecss				Add more class css on img tag (For example 'myclascss'). Work only if $moreatt is empty.
 *  @param		string		$marginleftonlyshort	1 = Add a short left margin on picto, 2 = Add a larger left margin on picto, 0 = No margin left. Works for fontawesome picto only.
 *  @return     string       				    	Return img tag
 *  @see        img_object(), img_picto_common()
 */
function img_picto($titlealt, $picto, $moreatt = '', $pictoisfullpath = false, $srconly = 0, $notitle = 0, $alt = '', $morecss = '', $marginleftonlyshort = 2)
{
	global $conf, $langs;

	// We forge fullpathpicto for image to $path/img/$picto. By default, we take DOL_URL_ROOT/theme/$conf->theme/img/$picto
	$url = DOL_URL_ROOT;
	$theme = $conf->theme;
	$path = 'theme/'.$theme;

	// Define fullpathpicto to use into src
	if ($pictoisfullpath) {
		// Clean parameters
		if (!preg_match('/(\.png|\.gif|\.svg)$/i', $picto)) {
			$picto .= '.png';
		}
		$fullpathpicto = $picto;
		$reg = array();
		if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
		    $morecss .= ($morecss ? ' ' : '').$reg[1];
		    $moreatt = str_replace('class="'.$reg[1].'"', '', $moreatt);
		}
	} else {
		$pictowithouttext = preg_replace('/(\.png|\.gif|\.svg)$/', '', $picto);

		//if (in_array($picto, array('switch_off', 'switch_on', 'off', 'on')))
        if (empty($srconly) && in_array($pictowithouttext, array(
        		'1downarrow', '1uparrow', '1leftarrow', '1rightarrow', '1uparrow_selected', '1downarrow_selected', '1leftarrow_selected', '1rightarrow_selected',
        		'address', 'barcode', 'bank', 'bookmark', 'building', 'cash-register', 'close_title', 'cubes', 'delete', 'dolly', 'edit', 'ellipsis-h',
        		'filter', 'file-code', 'grip', 'grip_title', 'list', 'listlight', 'note',
        		'object_bookmark', 'object_list', 'object_calendar', 'object_calendarweek', 'object_calendarmonth', 'object_calendarday', 'object_calendarperuser',
        		'off', 'on', 'play', 'playdisabled', 'printer', 'resize', 'stats',
				'note', 'setup', 'sign-out', 'split', 'switch_off', 'switch_on', 'tools', 'unlink', 'uparrow', 'user', 'wrench', 'globe',
				'jabber', 'skype', 'twitter', 'facebook', 'linkedin',
				'instagram', 'snapchat', 'youtube', 'google-plus-g', 'whatsapp',
				'chevron-left', 'chevron-right', 'chevron-down', 'chevron-top',
				'home', 'companies', 'products', 'commercial', 'invoicing', 'accountancy', 'project', 'hrm', 'members', 'ticket', 'generic',
        		'error', 'warning',
        		'title_setup', 'title_accountancy', 'title_bank', 'title_hrm', 'title_agenda'
			)
		)) {
			$fakey = $pictowithouttext;
			$facolor = ''; $fasize = '';
			$fa = 'fas';
			if (in_array($pictowithouttext, array('off', 'on', 'object_bookmark', 'bookmark'))) {
				$fa = 'far';
			}
			if (in_array($pictowithouttext, array('skype', 'twitter', 'facebook', 'linkedin', 'instagram', 'snapchat', 'youtube', 'google-plus-g', 'whatsapp'))) {
				$fa = 'fab';
			}

		    $arrayconvpictotofa = array(
		    	'address'=> 'address-book', 'setup'=>'cog', 'companies'=>'building', 'products'=>'cube', 'commercial'=>'suitcase', 'invoicing'=>'coins', 'accountancy'=>'money-check-alt', 'project'=>'sitemap',
		    	'hrm'=>'umbrella-beach', 'members'=>'users', 'ticket'=>'ticket-alt', 'generic'=>'folder-open',
		    	'switch_off'=>'toggle-off', 'switch_on'=>'toggle-on', 'object_bookmark'=>'star', 'bookmark'=>'star', 'stats' => 'chart-bar',
		    	'bank'=>'university', 'close_title'=>'window-close', 'delete'=>'trash', 'edit'=>'pencil', 'filter'=>'filter', 'split'=>'code-branch',
		    	'object_list'=>'list-alt', 'object_calendar'=>'calendar-alt', 'object_calendarweek'=>'calendar-week', 'object_calendarmonth'=>'calendar-alt', 'object_calendarday'=>'calendar-day', 'object_calendarperuser'=>'table',
		    	'error'=>'exclamation-triangle', 'warning'=>'exclamation-triangle',
		    	'title_setup'=>'tools', 'title_accountancy'=>'money-check-alt', 'title_bank'=>'university', 'title_hrm'=>'umbrella-beach', 'title_agenda'=>'calendar-alt'
		    );
		    if ($pictowithouttext == 'error' || $pictowithouttext == 'warning') {
		    	$facolor = '';
		    	$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
		    	$marginleftonlyshort = 0;
		    	$morecss .= ($morecss ? ' ' : '').('picto'.$pictowithouttext);
		    } elseif ($pictowithouttext == 'switch_off') {
				$facolor = '#999';
				$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
			}
			elseif ($pictowithouttext == 'switch_on') {
				$morecss .= ($morecss ? ' ' : '').'font-status4';
				$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
			}
			elseif ($pictowithouttext == 'off') {
			    $fakey = 'fa-square';
				$fasize = '1.3em';
			}
			elseif ($pictowithouttext == 'on') {
			    $fakey = 'fa-check-square';
				$fasize = '1.3em';
			}
			elseif ($pictowithouttext == 'bank') {
				$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
				$facolor = '#444';
			}
			elseif ($pictowithouttext == 'stats') {
				$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
				$facolor = '#444';
			}
			elseif ($pictowithouttext == 'delete') {
				$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
				$facolor = '#444';
			}
			elseif ($pictowithouttext == 'edit') {
				$facolor = '#444';
				$fakey = 'fa-pencil-alt';
			}
			elseif ($pictowithouttext == 'grip_title' || $pictowithouttext == 'grip') {
				$fakey = 'fa-arrows-alt';
			}
			elseif ($pictowithouttext == 'listlight') {
				$fakey = 'fa-download';
				$facolor = '#999';
				$marginleftonlyshort = 1;
			}
			elseif ($pictowithouttext == 'printer') {
				$fakey = 'fa-print';
				$fasize = '1.2em';
				$facolor = '#444';
			}
			elseif ($pictowithouttext == 'resize') {
				$fakey = 'fa-crop';
				$facolor = '#444';
			}
			elseif ($pictowithouttext == 'note') {
			    $fakey = 'fa-sticky-note';
			    $fa = 'far';
				$facolor = '#999';
				$marginleftonlyshort = 1;
			}
			elseif ($pictowithouttext == 'uparrow') {
				$fakey = 'fa-mail-forward';
				$facolor = '#555';
			}
			elseif (in_array($pictowithouttext, array('1uparrow', '1downarrow', '1leftarrow', '1rightarrow', '1uparrow_selected', '1downarrow_selected', '1leftarrow_selected', '1rightarrow_selected'))) {
			    $convertarray = array('1uparrow'=>'caret-up', '1downarrow'=>'caret-down', '1leftarrow'=>'caret-left', '1rightarrow'=>'caret-right', '1uparrow_selected'=>'caret-up', '1downarrow_selected'=>'caret-down', '1leftarrow_selected'=>'caret-left', '1rightarrow_selected'=>'caret-right');
			    $fakey = 'fa-'.$convertarray[$pictowithouttext];
			    if (preg_match('/selected/', $pictowithouttext)) $facolor = '#888';
				$marginleftonlyshort = 1;
			}
			elseif ($pictowithouttext == 'sign-out') {
				$fakey = 'fa-sign-out-alt';
			    $marginleftonlyshort = 0;
			}
			elseif ($pictowithouttext == 'unlink') {
				$fakey = 'fa-unlink';
				$facolor = '#555';
			}
			elseif ($pictowithouttext == 'playdisabled') {
				$fakey = 'fa-play';
				$facolor = '#ccc';
			}
			elseif ($pictowithouttext == 'play') {
				$fakey = 'fa-play';
				$facolor = '#444';
			}
			elseif ($pictowithouttext == 'jabber') {
				$fakey = 'fa-comment-o';
			}
			// Img for type of views
			elseif (in_array($pictowithouttext, array('object_list', 'object_calendar', 'object_calendarweek', 'object_calendarmonth', 'object_calendarday', 'object_calendarperuser'))) {
				$fakey = 'imgforviewmode fa-'.$arrayconvpictotofa[$pictowithouttext];
				$marginleftonlyshort = 0;
			}
			elseif (!empty($arrayconvpictotofa[$pictowithouttext]))
			{
				$fakey = 'fa-'.$arrayconvpictotofa[$pictowithouttext];
				//$facolor = '#444';
				$marginleftonlyshort = 0;
			}
			else {
				$fakey = 'fa-'.$pictowithouttext;
				//$facolor = '#444';
				$marginleftonlyshort = 0;
			}

			//this snippet only needed since function img_edit accepts only one additional parameter: no separate one for css only.
            //class/style need to be extracted to avoid duplicate class/style validation errors when $moreatt is added to the end of the attributes
            $reg = array();
			if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
                $morecss .= ($morecss ? ' ' : '').$reg[1];
                $moreatt = str_replace('class="'.$reg[1].'"', '', $moreatt);
            }
            if (preg_match('/style="([^"]+)"/', $moreatt, $reg)) {
                $morestyle = ' '.$reg[1];
                $moreatt = str_replace('style="'.$reg[1].'"', '', $moreatt);
            }
            $moreatt = trim($moreatt);

            $enabledisablehtml = '<span class="'.$fa.' '.$fakey.($marginleftonlyshort ? ($marginleftonlyshort == 1 ? ' marginleftonlyshort' : ' marginleftonly') : '');
            $enabledisablehtml .= ($morecss ? ' '.$morecss : '').'" style="'.($fasize ? ('font-size: '.$fasize.';') : '').($facolor ? (' color: '.$facolor.';') : '').($morestyle ? ' '.$morestyle : '').'"'.(($notitle || empty($titlealt)) ? '' : ' title="'.dol_escape_htmltag($titlealt).'"').($moreatt ? ' '.$moreatt : '').'>';
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$enabledisablehtml .= $titlealt;
			}
			$enabledisablehtml .= '</span>';

			return $enabledisablehtml;
		}

		if (!empty($conf->global->MAIN_OVERWRITE_THEME_PATH)) {
			$path = $conf->global->MAIN_OVERWRITE_THEME_PATH.'/theme/'.$theme; // If the theme does not have the same name as the module
		}
		elseif (!empty($conf->global->MAIN_OVERWRITE_THEME_RES)) {
			$path = $conf->global->MAIN_OVERWRITE_THEME_RES.'/theme/'.$conf->global->MAIN_OVERWRITE_THEME_RES; // To allow an external module to overwrite image resources whatever is activated theme
		}
		elseif (!empty($conf->modules_parts['theme']) && array_key_exists($theme, $conf->modules_parts['theme'])) {
			$path = $theme.'/theme/'.$theme; // If the theme have the same name as the module
		}

		// If we ask an image into $url/$mymodule/img (instead of default path)
		if (preg_match('/^([^@]+)@([^@]+)$/i', $picto, $regs)) {
			$picto = $regs[1];
			$path = $regs[2]; // $path is $mymodule
		}

		// Clean parameters
		if (!preg_match('/(\.png|\.gif|\.svg)$/i', $picto)) {
			$picto .= '.png';
		}
		// If alt path are defined, define url where img file is, according to physical path
		// ex: array(["main"]=>"/home/maindir/htdocs", ["alt0"]=>"/home/moddir0/htdocs", ...)
		foreach ($conf->file->dol_document_root as $type => $dirroot) {
			if ($type == 'main') {
				continue;
			}
			// This need a lot of time, that's why enabling alternative dir like "custom" dir is not recommanded
			if (file_exists($dirroot.'/'.$path.'/img/'.$picto)) {
				$url = DOL_URL_ROOT.$conf->file->dol_url_root[$type];
				break;
			}
		}

		// $url is '' or '/custom', $path is current theme or
		$fullpathpicto = $url.'/'.$path.'/img/'.$picto;
	}

	if ($srconly) {
		return $fullpathpicto;
	}
		// tag title is used for tooltip on <a>, tag alt can be used with very simple text on image for blind people
    return '<img src="'.$fullpathpicto.'" alt="'.dol_escape_htmltag($alt).'"'.(($notitle || empty($titlealt)) ? '' : ' title="'.dol_escape_htmltag($titlealt).'"').($moreatt ? ' '.$moreatt.($morecss ? ' class="'.$morecss.'"' : '') : ' class="inline-block'.($morecss ? ' '.$morecss : '').'"').'>'; // Alt is used for accessibility, title for popup
}

/**
 *	Show a picto called object_picto (generic function)
 *
 *	@param	string	$titlealt			Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$picto				Name of image to show object_picto (example: user, group, action, bill, contract, propal, product, ...)
 *										For external modules use imagename@mymodule to search into directory "img" of module.
 *	@param	string	$moreatt			Add more attribute on img tag (ie: class="datecallink")
 *	@param	int		$pictoisfullpath	If 1, image path is a full path
 *	@param	int		$srconly			Return only content of the src attribute of img.
 *  @param	int		$notitle			1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *	@return	string						Return img tag
 *	@see	#img_picto, #img_picto_common
 */
function img_object($titlealt, $picto, $moreatt = '', $pictoisfullpath = false, $srconly = 0, $notitle = 0)
{
	return img_picto($titlealt, 'object_'.$picto, $moreatt, $pictoisfullpath, $srconly, $notitle);
}

/**
 *	Show weather picto
 *
 *	@param      string		$titlealt         	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param      string|int	$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory. Or level of meteo image (0-4).
 *	@param		string		$moreatt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *  @param      string      $morecss            More CSS
 *	@return     string      					Return img tag
 *  @see        #img_object, #img_picto
 */
function img_weather($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0, $morecss = '')
{
	global $conf;

	if (is_numeric($picto))
	{
		$leveltopicto = array(0=>'weather-clear.png', 1=>'weather-few-clouds.png', 2=>'weather-clouds.png', 3=>'weather-many-clouds.png', 4=>'weather-storm.png');
		//return '<i class="fa fa-weather-level'.$picto.'"></i>';
		$picto = $leveltopicto[$picto];
	}
	elseif (!preg_match('/(\.png|\.gif)$/i', $picto)) $picto .= '.png';

	$path = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/weather/'.$picto;

	return img_picto($titlealt, $path, $moreatt, 1, 0, 0, '', $morecss);
}

/**
 *	Show picto (generic function)
 *
 *	@param      string		$titlealt         	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param      string		$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		string		$moreatt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *	@return     string      					Return img tag
 *  @see        #img_object, #img_picto
 */
function img_picto_common($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0)
{
	global $conf;

	if (!preg_match('/(\.png|\.gif)$/i', $picto)) $picto .= '.png';

	if ($pictoisfullpath) $path = $picto;
	else
	{
		$path = DOL_URL_ROOT.'/theme/common/'.$picto;

		if (!empty($conf->global->MAIN_MODULE_CAN_OVERWRITE_COMMONICONS))
		{
			$themepath = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/'.$picto;

			if (file_exists($themepath)) $path = $themepath;
		}
	}

	return img_picto($titlealt, $path, $moreatt, 1);
}

/**
 *	Show logo action
 *
 *	@param	string		$titlealt       Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string		$numaction   	Action id or code to show
 *	@return string      				Return an img tag
 */
function img_action($titlealt, $numaction)
{
	global $langs;

	if (empty($titlealt) || $titlealt == 'default')
	{
		if ($numaction == '-1' || $numaction == 'ST_NO') { $numaction = -1; $titlealt = $langs->transnoentitiesnoconv('ChangeDoNotContact'); }
		elseif ($numaction == '0' || $numaction == 'ST_NEVER') { $numaction = 0; $titlealt = $langs->transnoentitiesnoconv('ChangeNeverContacted'); }
		elseif ($numaction == '1' || $numaction == 'ST_TODO') { $numaction = 1; $titlealt = $langs->transnoentitiesnoconv('ChangeToContact'); }
		elseif ($numaction == '2' || $numaction == 'ST_PEND') { $numaction = 2; $titlealt = $langs->transnoentitiesnoconv('ChangeContactInProcess'); }
		elseif ($numaction == '3' || $numaction == 'ST_DONE') { $numaction = 3; $titlealt = $langs->transnoentitiesnoconv('ChangeContactDone'); }
		else { $titlealt = $langs->transnoentitiesnoconv('ChangeStatus '.$numaction); $numaction = 0; }
	}
	if (!is_numeric($numaction)) $numaction = 0;

	return img_picto($titlealt, 'stcomm'.$numaction.'.png');
}

/**
 *  Show pdf logo
 *
 *  @param	string		$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param  int		    $size       Taille de l'icone : 3 = 16x16px , 2 = 14x14px
 *  @return string      			Retourne tag img
 */
function img_pdf($titlealt = 'default', $size = 3)
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Show');

	return img_picto($titlealt, 'pdf'.$size.'.png');
}

/**
 *	Show logo +
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit_add($titlealt = 'default', $other = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Add');

	return img_picto($titlealt, 'edit_add.png', $other);
}
/**
 *	Show logo -
 *
 *	@param	string	$titlealt	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit_remove($titlealt = 'default', $other = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Remove');

	return img_picto($titlealt, 'edit_remove.png', $other);
}

/**
 *	Show logo editer/modifier fiche
 *
 *	@param  string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      If you have to put the style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit($titlealt = 'default', $float = 0, $other = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Modify');

	return img_picto($titlealt, 'edit.png', ($float ? 'style="float: '.($langs->tab_translate["DIRECTION"] == 'rtl' ? 'left' : 'right').'"' : "").($other ? ' '.$other : ''));
}

/**
 *	Show logo view card
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      If you have to put the style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_view($titlealt = 'default', $float = 0, $other = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('View');

	$moreatt = ($float ? 'style="float: right" ' : '').$other;

	return img_picto($titlealt, 'view.png', $moreatt);
}

/**
 *  Show delete logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_delete($titlealt = 'default', $other = 'class="pictodelete"')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Delete');

	return img_picto($titlealt, 'delete.png', $other);
	//return '<span class="fa fa-trash fa-2x fa-fw" style="font-size: 1.7em;" title="'.$titlealt.'"></span>';
}

/**
 *  Show printer logo
 *
 *  @param  string  $titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param  string  $other      Add more attributes on img
 *  @return string              Retourne tag img
 */
function img_printer($titlealt = "default", $other = '')
{
	global $langs;
	if ($titlealt == "default") $titlealt = $langs->trans("Print");
	return img_picto($titlealt, 'printer.png', $other);
}

/**
 *  Show split logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_split($titlealt = 'default', $other = 'class="pictosplit"')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Split');

	return img_picto($titlealt, 'split.png', $other);
}

/**
 *	Show help logo with cursor "?"
 *
 * 	@param	int              	$usehelpcursor		1=Use help cursor, 2=Use click pointer cursor, 0=No specific cursor
 * 	@param	int|string	        $usealttitle		Text to use as alt title
 * 	@return string            	           			Return tag img
 */
function img_help($usehelpcursor = 1, $usealttitle = 1)
{
	global $langs;

	if ($usealttitle)
	{
		if (is_string($usealttitle)) $usealttitle = dol_escape_htmltag($usealttitle);
		else $usealttitle = $langs->trans('Info');
	}

	return img_picto($usealttitle, 'info.png', 'style="vertical-align: middle;'.($usehelpcursor == 1 ? ' cursor: help' : ($usehelpcursor == 2 ? ' cursor: pointer' : '')).'"');
}

/**
 *	Show info logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_info($titlealt = 'default')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Informations');

	return img_picto($titlealt, 'info.png', 'style="vertical-align: middle;"');
}

/**
 *	Show warning logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"'). If 1, add float: right. Can't be "class" attribute.
 *  @param	string  $morecss	Add more CSS
 *	@return string      		Return img tag
 */
function img_warning($titlealt = 'default', $moreatt = '', $morecss = 'pictowarning')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Warning');

	//return '<div class="imglatecoin">'.img_picto($titlealt, 'warning_white.png', 'class="pictowarning valignmiddle"'.($moreatt ? ($moreatt == '1' ? ' style="float: right"' : ' '.$moreatt): '')).'</div>';
	return img_picto($titlealt, 'warning.png', 'class="'.$morecss.'"'.($moreatt ? ($moreatt == '1' ? ' style="float: right"' : ' '.$moreatt) : ''));
}

/**
 *  Show error logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_error($titlealt = 'default')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Error');

	return img_picto($titlealt, 'error.png');
}

/**
 *	Show next logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
*	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_next($titlealt = 'default', $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Next');

	//return img_picto($titlealt, 'next.png', $moreatt);
	return '<span class="fa fa-chevron-right paddingright paddingleft" title="'.dol_escape_htmltag($titlealt).'"></span>';
}

/**
 *	Show previous logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_previous($titlealt = 'default', $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Previous');

	//return img_picto($titlealt, 'previous.png', $moreatt);
	return '<span class="fa fa-chevron-left paddingright paddingleft" title="'.dol_escape_htmltag($titlealt).'"></span>';
}

/**
 *	Show down arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected   Selected
 *  @param	string	$moreclass	Add more CSS classes
 *	@return string      		Return img tag
 */
function img_down($titlealt = 'default', $selected = 0, $moreclass = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Down');

	return img_picto($titlealt, ($selected ? '1downarrow_selected.png' : '1downarrow.png'), 'class="imgdown'.($moreclass ? " ".$moreclass : "").'"');
}

/**
 *	Show top arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *  @param	string	$moreclass	Add more CSS classes
 *	@return string      		Return img tag
 */
function img_up($titlealt = 'default', $selected = 0, $moreclass = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Up');

	return img_picto($titlealt, ($selected ? '1uparrow_selected.png' : '1uparrow.png'), 'class="imgup'.($moreclass ? " ".$moreclass : "").'"');
}

/**
 *	Show left arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_left($titlealt = 'default', $selected = 0, $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Left');

	return img_picto($titlealt, ($selected ? '1leftarrow_selected.png' : '1leftarrow.png'), $moreatt);
}

/**
 *	Show right arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_right($titlealt = 'default', $selected = 0, $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Right');

	return img_picto($titlealt, ($selected ? '1rightarrow_selected.png' : '1rightarrow.png'), $moreatt);
}

/**
 *	Show tick logo if allowed
 *
 *	@param	string	$allow		Allow
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_allow($allow, $titlealt = 'default')
{
	global $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Active');

	if ($allow == 1) return img_picto($titlealt, 'tick.png');

	return '-';
}

/**
 *	Return image of a credit card according to its brand name
 *
 *	@param	string	$brand		Brand name of credit card
 *	@return string     			Return img tag
 */
function img_credit_card($brand)
{
	if ($brand == 'visa' || $brand == 'Visa') {$brand = 'cc-visa'; }
	elseif ($brand == 'mastercard' || $brand == 'MasterCard') {$brand = 'cc-mastercard'; }
	elseif ($brand == 'amex' || $brand == 'American Express') {$brand = 'cc-amex'; }
	elseif ($brand == 'discover' || $brand == 'Discover') {$brand = 'cc-discover'; }
	elseif ($brand == 'jcb' || $brand == 'JCB') {$brand = 'cc-jcb'; }
	elseif ($brand == 'diners' || $brand == 'Diners club') {$brand = 'cc-diners-club'; }
	elseif (!in_array($brand, array('cc-visa', 'cc-mastercard', 'cc-amex', 'cc-discover', 'cc-jcb', 'cc-diners-club'))) {$brand = 'credit-card'; }

	return '<span class="fa fa-'.$brand.' fa-2x fa-fw"></span>';
}

/**
 *	Show MIME img of a file
 *
 *	@param	string	$file		Filename
 * 	@param	string	$titlealt	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param	string	$morecss	More css
 *	@return string     			Return img tag
 */
function img_mime($file, $titlealt = '', $morecss = '')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$mimetype = dol_mimetype($file, '', 1);
	$mimeimg = dol_mimetype($file, '', 2);
	$mimefa = dol_mimetype($file, '', 4);

	if (empty($titlealt)) $titlealt = 'Mime type: '.$mimetype;

	//return img_picto_common($titlealt, 'mime/'.$mimeimg, 'class="'.$morecss.'"');
	return '<i class="fa fa-'.$mimefa.' paddingright"'.($titlealt ? ' title="'.$titlealt.'"' : '').'></i>';
}


/**
 *  Show search logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_search($titlealt = 'default', $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Search');

	$img = img_picto($titlealt, 'search.png', $other, false, 1);

	$input = '<input type="image" class="liste_titre" name="button_search" src="'.$img.'" ';
	$input .= 'value="'.dol_escape_htmltag($titlealt).'" title="'.dol_escape_htmltag($titlealt).'" >';

	return $input;
}

/**
 *  Show search logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_searchclear($titlealt = 'default', $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Search');

	$img = img_picto($titlealt, 'searchclear.png', $other, false, 1);

	$input = '<input type="image" class="liste_titre" name="button_removefilter" src="'.$img.'" ';
	$input .= 'value="'.dol_escape_htmltag($titlealt).'" title="'.dol_escape_htmltag($titlealt).'" >';

	return $input;
}

/**
 *	Show information for admin users or standard users
 *
 *	@param	string	$text			Text info
 *	@param  integer	$infoonimgalt	Info is shown only on alt of star picto, otherwise it is show on output after the star picto
 *	@param	int		$nodiv			No div
 *  @param  string  $admin          '1'=Info for admin users. '0'=Info for standard users (change only the look), 'error','xxx'=Other
 *  @param	string	$morecss		More CSS ('', 'warning', 'error')
 *	@return	string					String with info text
 */
function info_admin($text, $infoonimgalt = 0, $nodiv = 0, $admin = '1', $morecss = '')
{
	global $conf, $langs;

	if ($infoonimgalt)
	{
		return img_picto($text, 'info', 'class="hideonsmartphone'.($morecss ? ' '.$morecss : '').'"');
	}

	return ($nodiv ? '' : '<div class="'.(empty($admin) ? '' : ($admin == '1' ? 'info' : $admin)).' hideonsmartphone'.($morecss ? ' '.$morecss : '').'">').'<span class="fa fa-info-circle" title="'.dol_escape_htmltag($admin ? $langs->trans('InfoAdmin') : $langs->trans('Note')).'"></span> '.$text.($nodiv ? '' : '</div>');
}


/**
 *  Displays error message system with all the information to facilitate the diagnosis and the escalation of the bugs.
 *  This function must be called when a blocking technical error is encountered.
 *  However, one must try to call it only within php pages, classes must return their error through their property "error".
 *
 *	@param	 	DoliDB	$db      	Database handler
 *	@param  	mixed	$error		String or array of errors strings to show
 *  @param		array	$errors		Array of errors
 *	@return 	void
 *  @see    	dol_htmloutput_errors()
 */
function dol_print_error($db = '', $error = '', $errors = null)
{
	global $conf, $langs, $argv;
	global $dolibarr_main_prod;

	$out = '';
	$syslog = '';

	// Si erreur intervenue avant chargement langue
	if (!$langs)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
		$langs = new Translate('', $conf);
		$langs->load("main");
	}
	// Load translation files required by the page
    $langs->loadLangs(array('main', 'errors'));

	if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
	{
		$out .= $langs->trans("DolibarrHasDetectedError").".<br>\n";
		if (!empty($conf->global->MAIN_FEATURES_LEVEL)) $out .= "You use an experimental or develop level of features, so please do NOT report any bugs, except if problem is confirmed moving option MAIN_FEATURES_LEVEL back to 0.<br>\n";
		$out .= $langs->trans("InformationToHelpDiagnose").":<br>\n";

		$out .= "<b>".$langs->trans("Date").":</b> ".dol_print_date(time(), 'dayhourlog')."<br>\n";
		$out .= "<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";
		if (isset($conf->global->MAIN_FEATURES_LEVEL)) $out .= "<b>".$langs->trans("LevelOfFeature").":</b> ".$conf->global->MAIN_FEATURES_LEVEL."<br>\n";
		if (function_exists("phpversion"))
		{
			$out .= "<b>".$langs->trans("PHP").":</b> ".phpversion()."<br>\n";
		}
		$out .= "<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
		if (function_exists("php_uname"))
		{
			$out .= "<b>".$langs->trans("OS").":</b> ".php_uname()."<br>\n";
		}
		$out .= "<b>".$langs->trans("UserAgent").":</b> ".$_SERVER["HTTP_USER_AGENT"]."<br>\n";
		$out .= "<br>\n";
		$out .= "<b>".$langs->trans("RequestedUrl").":</b> ".dol_htmlentities($_SERVER["REQUEST_URI"], ENT_COMPAT, 'UTF-8')."<br>\n";
		$out .= "<b>".$langs->trans("Referer").":</b> ".(isset($_SERVER["HTTP_REFERER"]) ?dol_htmlentities($_SERVER["HTTP_REFERER"], ENT_COMPAT, 'UTF-8') : '')."<br>\n";
		$out .= "<b>".$langs->trans("MenuManager").":</b> ".(isset($conf->standard_menu) ? $conf->standard_menu : '')."<br>\n";
		$out .= "<br>\n";
		$syslog .= "url=".dol_escape_htmltag($_SERVER["REQUEST_URI"]);
		$syslog .= ", query_string=".dol_escape_htmltag($_SERVER["QUERY_STRING"]);
	}
	else                              // Mode CLI
	{
		$out .= '> '.$langs->transnoentities("ErrorInternalErrorDetected").":\n".$argv[0]."\n";
		$syslog .= "pid=".dol_getmypid();
	}

	if (!empty($conf->modules))
	{
	    $out .= "<b>".$langs->trans("Modules").":</b> ".join(', ', $conf->modules)."<br>\n";
	}

	if (is_object($db))
	{
		if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
		{
			$out .= "<b>".$langs->trans("DatabaseTypeManager").":</b> ".$db->type."<br>\n";
			$out .= "<b>".$langs->trans("RequestLastAccessInError").":</b> ".($db->lastqueryerror() ?dol_escape_htmltag($db->lastqueryerror()) : $langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out .= "<b>".$langs->trans("ReturnCodeLastAccessInError").":</b> ".($db->lasterrno() ?dol_escape_htmltag($db->lasterrno()) : $langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out .= "<b>".$langs->trans("InformationLastAccessInError").":</b> ".($db->lasterror() ?dol_escape_htmltag($db->lasterror()) : $langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out .= "<br>\n";
		}
		else                            // Mode CLI
		{
			// No dol_escape_htmltag for output, we are in CLI mode
			$out .= '> '.$langs->transnoentities("DatabaseTypeManager").":\n".$db->type."\n";
			$out .= '> '.$langs->transnoentities("RequestLastAccessInError").":\n".($db->lastqueryerror() ? $db->lastqueryerror() : $langs->transnoentities("ErrorNoRequestInError"))."\n";
			$out .= '> '.$langs->transnoentities("ReturnCodeLastAccessInError").":\n".($db->lasterrno() ? $db->lasterrno() : $langs->transnoentities("ErrorNoRequestInError"))."\n";
			$out .= '> '.$langs->transnoentities("InformationLastAccessInError").":\n".($db->lasterror() ? $db->lasterror() : $langs->transnoentities("ErrorNoRequestInError"))."\n";
		}
		$syslog .= ", sql=".$db->lastquery();
		$syslog .= ", db_error=".$db->lasterror();
	}

	if ($error || $errors)
	{
		$langs->load("errors");

		// Merge all into $errors array
		if (is_array($error) && is_array($errors)) $errors = array_merge($error, $errors);
		elseif (is_array($error)) $errors = $error;
		elseif (is_array($errors)) $errors = array_merge(array($error), $errors);
		else $errors = array_merge(array($error));

		foreach ($errors as $msg)
		{
			if (empty($msg)) continue;
			if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
			{
				$out .= "<b>".$langs->trans("Message").":</b> ".dol_escape_htmltag($msg)."<br>\n";
			}
			else                        // Mode CLI
			{
				$out .= '> '.$langs->transnoentities("Message").":\n".$msg."\n";
			}
			$syslog .= ", msg=".$msg;
		}
	}
	if (empty($dolibarr_main_prod) && $_SERVER['DOCUMENT_ROOT'] && function_exists('xdebug_print_function_stack') && function_exists('xdebug_call_file'))
	{
		xdebug_print_function_stack();
		$out .= '<b>XDebug informations:</b>'."<br>\n";
		$out .= 'File: '.xdebug_call_file()."<br>\n";
		$out .= 'Line: '.xdebug_call_line()."<br>\n";
		$out .= 'Function: '.xdebug_call_function()."<br>\n";
		$out .= "<br>\n";
	}

	if (empty($dolibarr_main_prod)) print $out;
	else	// This should not happen, except if there is a bug somewhere. Enabled and check log in such case.
	{
		print 'This website or feature is currently temporarly not available.<br><br>This may be due to a maintenance operation. Current status of operation are on next line...<br><br>'."\n";
		$langs->load("errors");
		print $langs->trans("DolibarrHasDetectedError").'. ';
		print $langs->trans("YouCanSetOptionDolibarrMainProdToZero");
		define("MAIN_CORE_ERROR", 1);
	}
	//else print 'Sorry, an error occured but the parameter $dolibarr_main_prod is defined in conf file so no message is reported to your browser. Please read the log file for error message.';
	dol_syslog("Error ".$syslog, LOG_ERR);
}

/**
 * Show a public email and error code to contact if technical error
 *
 * @param	string	$prefixcode		Prefix of public error code
 * @param   string  $errormessage   Complete error message
 * @param	array	$errormessages	Array of error messages
 * @param	string	$morecss		More css
 * @param	string	$email			Email
 * @return	void
 */
function dol_print_error_email($prefixcode, $errormessage = '', $errormessages = array(), $morecss = 'error', $email = '')
{
	global $langs, $conf;

	if (empty($email)) $email = $conf->global->MAIN_INFO_SOCIETE_MAIL;

	$langs->load("errors");
	$now = dol_now();

	print '<br><div class="center login_main_message"><div class="'.$morecss.'">';
	print $langs->trans("ErrorContactEMail", $email, $prefixcode.dol_print_date($now, '%Y%m%d%H%M%S'));
	if ($errormessage) print '<br><br>'.$errormessage;
	if (is_array($errormessages) && count($errormessages))
	{
		foreach ($errormessages as $mesgtoshow)
		{
			print '<br><br>'.$mesgtoshow;
		}
	}
	print '</div></div>';
}

/**
 *	Show title line of an array
 *
 *	@param	string	$name        Label of field
 *	@param	string	$file        Url used when we click on sort picto
 *	@param	string	$field       Field to use for new sorting
 *	@param	string	$begin       ("" by defaut)
 *	@param	string	$moreparam   Add more parameters on sort url links ("" by default)
 *	@param  string	$moreattrib  Options of attribute td ("" by defaut, example: 'align="center"')
 *	@param  string	$sortfield   Current field used to sort
 *	@param  string	$sortorder   Current sort order
 *  @param	string	$prefix		 Prefix for css. Use space after prefix to add your own CSS tag.
 *  @param	string	$tooltip	 Tooltip
 *	@return	void
 */
function print_liste_field_titre($name, $file = "", $field = "", $begin = "", $moreparam = "", $moreattrib = "", $sortfield = "", $sortorder = "", $prefix = "", $tooltip = "")
{
	print getTitleFieldOfList($name, 0, $file, $field, $begin, $moreparam, $moreattrib, $sortfield, $sortorder, $prefix, 0, $tooltip);
}

/**
 *	Get title line of an array
 *
 *	@param	string	$name        		Translation key of field
 *	@param	int		$thead		 		0=To use with standard table format, 1=To use inside <thead><tr>, 2=To use with <div>
 *	@param	string	$file        		Url used when we click on sort picto
 *	@param	string	$field       		Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
 *	@param	string	$begin       		("" by defaut)
 *	@param	string	$moreparam   		Add more parameters on sort url links ("" by default)
 *	@param  string	$moreattrib  		Add more attributes on th ("" by defaut, example: 'align="center"'). To add more css class, use param $prefix.
 *	@param  string	$sortfield   		Current field used to sort (Ex: 'd.datep,d.id')
 *	@param  string	$sortorder   		Current sort order (Ex: 'asc,desc')
 *  @param	string	$prefix		 		Prefix for css. Use space after prefix to add your own CSS tag, for example 'mycss '.
 *  @param	string	$disablesortlink	1=Disable sort link
 *  @param	string	$tooltip	 		Tooltip
 *	@return	string
 */
function getTitleFieldOfList($name, $thead = 0, $file = "", $field = "", $begin = "", $moreparam = "", $moreattrib = "", $sortfield = "", $sortorder = "", $prefix = "", $disablesortlink = 0, $tooltip = '')
{
	global $conf, $langs, $form;
	//print "$name, $file, $field, $begin, $options, $moreattrib, $sortfield, $sortorder<br>\n";

	if ($moreattrib == 'class="right"') $prefix .= 'right '; // For backward compatibility

	$sortorder = strtoupper($sortorder);
	$out = '';
	$sortimg = '';

	$tag = 'th';
	if ($thead == 2) $tag = 'div';

	$tmpsortfield = explode(',', $sortfield);
	$sortfield1 = trim($tmpsortfield[0]); // If $sortfield is 'd.datep,d.id', it becomes 'd.datep'
	$tmpfield = explode(',', $field);
	$field1 = trim($tmpfield[0]); // If $field is 'd.datep,d.id', it becomes 'd.datep'

	//var_dump('field='.$field.' field1='.$field1.' sortfield='.$sortfield.' sortfield1='.$sortfield1);
	// If field is used as sort criteria we use a specific css class liste_titre_sel
	// Example if (sortfield,field)=("nom","xxx.nom") or (sortfield,field)=("nom","nom")
	if ($field1 && ($sortfield1 == $field1 || $sortfield1 == preg_replace("/^[^\.]+\./", "", $field1))) $out .= '<'.$tag.' class="'.$prefix.'liste_titre_sel" '.$moreattrib.'>';
	else $out .= '<'.$tag.' class="'.$prefix.'liste_titre" '.$moreattrib.'>';

	if (empty($thead) && $field && empty($disablesortlink))    // If this is a sort field
	{
		$options = preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', $moreparam);
		$options = preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options = preg_replace('/&+/i', '&', $options);
		if (!preg_match('/^&/', $options)) $options = '&'.$options;

		$sortordertouseinlink = '';
		if ($field1 != $sortfield1) // We are on another field than current sorted field
		{
			if (preg_match('/^DESC/i', $sortorder))
			{
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			}
			else		// We reverse the var $sortordertouseinlink
			{
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		}
		else                        // We are on field that is the first current sorting criteria
		{
			if (preg_match('/^ASC/i', $sortorder))	// We reverse the var $sortordertouseinlink
			{
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			}
			else
			{
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		}
		$sortordertouseinlink = preg_replace('/,$/', '', $sortordertouseinlink);
		$out .= '<a class="reposition" href="'.$file.'?sortfield='.$field.'&sortorder='.$sortordertouseinlink.'&begin='.$begin.$options.'">';
	}

	if ($tooltip) $out .= $form->textwithpicto($langs->trans($name), $langs->trans($tooltip));
	else $out .= $langs->trans($name);

	if (empty($thead) && $field && empty($disablesortlink))    // If this is a sort field
	{
		$out .= '</a>';
	}

	if (empty($thead) && $field)    // If this is a sort field
	{
		$options = preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', $moreparam);
		$options = preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options = preg_replace('/&+/i', '&', $options);
		if (!preg_match('/^&/', $options)) $options = '&'.$options;

		if (!$sortorder || $field1 != $sortfield1)
		{
			//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
			//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
		}
		else
		{
			if (preg_match('/^DESC/', $sortorder)) {
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",1).'</a>';
				$sortimg .= '<span class="nowrap">'.img_up("Z-A", 0).'</span>';
			}
			if (preg_match('/^ASC/', $sortorder)) {
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",1).'</a>';
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
				$sortimg .= '<span class="nowrap">'.img_down("A-Z", 0).'</span>';
			}
		}
	}

	$out .= $sortimg;

	$out .= '</'.$tag.'>';

	return $out;
}

/**
 *	Show a title.
 *
 *	@param	string	$title			Title to show
 *	@return	string					Title to show
 *  @deprecated						Use load_fiche_titre instead
 *  @see load_fiche_titre()
 */
function print_titre($title)
{
	dol_syslog(__FUNCTION__." is deprecated", LOG_WARNING);

	print '<div class="titre">'.$title.'</div>';
}

/**
 *	Show a title with picto
 *
 *	@param	string	$title				Title to show
 *	@param	string	$mesg				Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	int		$id					To force an id on html objects
 * 	@return	void
 *  @deprecated Use print load_fiche_titre instead
 */
function print_fiche_titre($title, $mesg = '', $picto = 'generic', $pictoisfullpath = 0, $id = '')
{
	print load_fiche_titre($title, $mesg, $picto, $pictoisfullpath, $id);
}

/**
 *	Load a title with picto
 *
 *	@param	string	$titre				Title to show
 *	@param	string	$morehtmlright		Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	string	$id					To force an id on html objects
 *  @param  string  $morecssontable     More css on table
 *	@param	string	$morehtmlcenter		Added message to show on center
 * 	@return	string
 *  @see print_barre_liste()
 */
function load_fiche_titre($titre, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '')
{
	global $conf;

	$return = '';

	if ($picto == 'setup') $picto = 'generic';

	$return .= "\n";
	$return .= '<table '.($id ? 'id="'.$id.'" ' : '').'class="centpercent notopnoleftnoright table-fiche-title '.($morecssontable ? ' '.$morecssontable : '').'"><tr>'; // maring bottom must be same than into print_barre_list
	if ($picto) $return .= '<td class="nobordernopadding widthpictotitle opacityhigh valignmiddle col-picto">'.img_picto('', $picto, 'class="valignmiddle widthpictotitle pictotitle"', $pictoisfullpath).'</td>';
	$return .= '<td class="nobordernopadding valignmiddle col-title">';
	$return .= '<div class="titre inline-block">'.$titre.'</div>';
	$return .= '</td>';
	if (dol_strlen($morehtmlcenter))
	{
		$return .= '<td class="nobordernopadding center valignmiddle">'.$morehtmlcenter.'</td>';
	}
	if (dol_strlen($morehtmlright))
	{
		$return .= '<td class="nobordernopadding titre_right wordbreakimp right valignmiddle">'.$morehtmlright.'</td>';
	}
	$return .= '</tr></table>'."\n";

	return $return;
}

/**
 *	Print a title with navigation controls for pagination
 *
 *	@param	string	    $titre				Title to show (required)
 *	@param	int   	    $page				Numero of page to show in navigation links (required)
 *	@param	string	    $file				Url of page (required)
 *	@param	string	    $options         	More parameters for links ('' by default, does not include sortfield neither sortorder). Value must be 'urlencoded' before calling function.
 *	@param	string    	$sortfield       	Field to sort on ('' by default)
 *	@param	string	    $sortorder       	Order to sort ('' by default)
 *	@param	string	    $morehtmlcenter     String in the middle ('' by default). We often find here string $massaction comming from $form->selectMassAction()
 *	@param	int		    $num				Number of records found by select with limit+1
 *	@param	int|string  $totalnboflines		Total number of records/lines for all pages (if known). Use a negative value of number to not show number. Use '' if unknown.
 *	@param	string	    $picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		    $pictoisfullpath	1=Icon name is a full absolute url of image
 *  @param	string	    $morehtmlright			More html to show
 *  @param  string      $morecss            More css to the table
 *  @param  int         $limit              Max number of lines (-1 = use default, 0 = no limit, > 0 = limit).
 *  @param  int         $hideselectlimit    Force to hide select limit
 *  @param  int         $hidenavigation     Force to hide all navigation tools
 *	@return	void
 */
function print_barre_liste($titre, $page, $file, $options = '', $sortfield = '', $sortorder = '', $morehtmlcenter = '', $num = -1, $totalnboflines = '', $picto = 'generic', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '', $limit = -1, $hideselectlimit = 0, $hidenavigation = 0)
{
	global $conf, $langs;

	$savlimit = $limit;
	$savtotalnboflines = $totalnboflines;
	$totalnboflines = abs($totalnboflines);

	if ($picto == 'setup') $picto = 'title_setup.png';
	if (($conf->browser->name == 'ie') && $picto == 'generic') $picto = 'title.gif';
	if ($limit < 0) $limit = $conf->liste_limit;
	if ($savlimit != 0 && (($num > $limit) || ($num == -1) || ($limit == 0)))
	{
		$nextpage = 1;
	}
	else
	{
		$nextpage = 0;
	}
	//print 'totalnboflines='.$totalnboflines.'-savlimit='.$savlimit.'-limit='.$limit.'-num='.$num.'-nextpage='.$nextpage;

	print "\n";
	print "<!-- Begin title '".$titre."' -->\n";
	print '<table class="centpercent notopnoleftnoright table-fiche-title'.($morecss ? ' '.$morecss : '').'"><tr>'; // maring bottom must be same than into load_fiche_tire

	// Left
	print '<td class="nobordernopadding valignmiddle col-title">';
	if ($picto && $titre) print img_picto('', $picto, 'class="hideonsmartphone valignmiddle opacityhigh pictotitle widthpictotitle"', $pictoisfullpath);
	print '<div class="titre inline-block">'.$titre;
	if (!empty($titre) && $savtotalnboflines >= 0 && (string) $savtotalnboflines != '') print ' ('.$totalnboflines.')';
	print '</div></td>';

	// Center
	if ($morehtmlcenter)
	{
		print '<td class="nobordernopadding center valignmiddle">'.$morehtmlcenter.'</td>';
	}

	// Right
	print '<td class="nobordernopadding valignmiddle right">';
	if ($sortfield) $options .= "&sortfield=".urlencode($sortfield);
	if ($sortorder) $options .= "&sortorder=".urlencode($sortorder);
	// Show navigation bar
	$pagelist = '';
	if ($savlimit != 0 && ($page > 0 || $num > $limit))
	{
		if ($totalnboflines)	// If we know total nb of lines
		{
			// Define nb of extra page links before and after selected page + ... + first or last
			$maxnbofpage = (empty($conf->dol_optimize_smallscreen) ? 4 : 0);

			if ($limit > 0) $nbpages = ceil($totalnboflines / $limit);
			else $nbpages = 1;
			$cpt = ($page - $maxnbofpage);
			if ($cpt < 0) { $cpt = 0; }

			if ($cpt >= 1)
			{
				$pagelist .= '<li class="pagination"><a href="'.$file.'?page=0'.$options.'">1</a></li>';
				if ($cpt > 2) $pagelist .= '<li class="pagination"><span class="inactive">...</span></li>';
				elseif ($cpt == 2) $pagelist .= '<li class="pagination"><a href="'.$file.'?page=1'.$options.'">2</a></li>';
			}

			do
			{
				if ($cpt == $page)
				{
					$pagelist .= '<li class="pagination"><span class="active">'.($page + 1).'</span></li>';
				}
				else
				{
					$pagelist .= '<li class="pagination"><a href="'.$file.'?page='.$cpt.$options.'">'.($cpt + 1).'</a></li>';
				}
				$cpt++;
			}
			while ($cpt < $nbpages && $cpt <= $page + $maxnbofpage);

			if ($cpt < $nbpages)
			{
				if ($cpt < $nbpages - 2) $pagelist .= '<li class="pagination"><span class="inactive">...</span></li>';
				elseif ($cpt == $nbpages - 2) $pagelist .= '<li class="pagination"><a href="'.$file.'?page='.($nbpages - 2).$options.'">'.($nbpages - 1).'</a></li>';
				$pagelist .= '<li class="pagination"><a href="'.$file.'?page='.($nbpages - 1).$options.'">'.$nbpages.'</a></li>';
			}
		}
		else
		{
			$pagelist .= '<li class="pagination"><span class="active">'.($page + 1)."</li>";
		}
	}

	if ($savlimit || $morehtmlright) {
		print_fleche_navigation($page, $file, $options, $nextpage, $pagelist, $morehtmlright, $savlimit, $totalnboflines, $hideselectlimit); // output the div and ul for previous/last completed with page numbers into $pagelist
	}

	print '</td>';

	print '</tr></table>'."\n";
	print "<!-- End title -->\n\n";
}

/**
 *	Function to show navigation arrows into lists
 *
 *	@param	int				$page				Number of page
 *	@param	string			$file				Page URL (in most cases provided with $_SERVER["PHP_SELF"])
 *	@param	string			$options         	Other url parameters to propagate ("" by default, may include sortfield and sortorder)
 *	@param	integer			$nextpage	    	Do we show a next page button
 *	@param	string			$betweenarrows		HTML content to show between arrows. MUST contains '<li> </li>' tags or '<li><span> </span></li>'.
 *  @param	string			$afterarrows		HTML content to show after arrows. Must NOT contains '<li> </li>' tags.
 *  @param  int             $limit              Max nb of record to show  (-1 = no combo with limit, 0 = no limit, > 0 = limit)
 *	@param	int		        $totalnboflines		Total number of records/lines for all pages (if known)
 *  @param  int             $hideselectlimit    Force to hide select limit
 *	@return	void
 */
function print_fleche_navigation($page, $file, $options = '', $nextpage = 0, $betweenarrows = '', $afterarrows = '', $limit = -1, $totalnboflines = 0, $hideselectlimit = 0)
{
	global $conf, $langs;

	print '<div class="pagination"><ul>';
	if ((int) $limit > 0 && empty($hideselectlimit))
	{
		$pagesizechoices = '10:10,15:15,20:20,30:30,40:40,50:50,100:100,250:250,500:500,1000:1000,5000:5000';
		//$pagesizechoices.=',0:'.$langs->trans("All");     // Not yet supported
		//$pagesizechoices.=',2:2';
		if (!empty($conf->global->MAIN_PAGESIZE_CHOICES)) $pagesizechoices = $conf->global->MAIN_PAGESIZE_CHOICES;

		print '<li class="pagination">';
		print '<select class="flat selectlimit" name="limit" title="'.dol_escape_htmltag($langs->trans("MaxNbOfRecordPerPage")).'">';
		$tmpchoice = explode(',', $pagesizechoices);
		$tmpkey = $limit.':'.$limit;
		if (!in_array($tmpkey, $tmpchoice)) $tmpchoice[] = $tmpkey;
		$tmpkey = $conf->liste_limit.':'.$conf->liste_limit;
		if (!in_array($tmpkey, $tmpchoice)) $tmpchoice[] = $tmpkey;
		asort($tmpchoice, SORT_NUMERIC);
		foreach ($tmpchoice as $val)
		{
			$selected = '';
			$tmp = explode(':', $val);
			$key = $tmp[0];
			$val = $tmp[1];
			if ($key != '' && $val != '')
			{
				if ((int) $key == (int) $limit)
				{
					$selected = ' selected="selected"';
				}
				print '<option name="'.$key.'"'.$selected.'>'.dol_escape_htmltag($val).'</option>'."\n";
			}
		}
		print '</select>';
		if ($conf->use_javascript_ajax)
		{
			print '<!-- JS CODE TO ENABLE select limit to launch submit of page -->
            		<script>
                	jQuery(document).ready(function () {
            	  		jQuery(".selectlimit").change(function() {
                            console.log("Change limit. Send submit");
                            $(this).parents(\'form:first\').submit();
            	  		});
                	});
            		</script>
                ';
		}
		print '</li>';
	}
	if ($page > 0)
	{
		print '<li class="pagination"><a class="paginationprevious" href="'.$file.'?page='.($page - 1).$options.'"><i class="fa fa-chevron-left" title="'.dol_escape_htmltag($langs->trans("Previous")).'"></i></a></li>';
	}
	if ($betweenarrows)
	{
		print $betweenarrows;
	}
	if ($nextpage > 0)
	{
		print '<li class="pagination"><a class="paginationnext" href="'.$file.'?page='.($page + 1).$options.'"><i class="fa fa-chevron-right" title="'.dol_escape_htmltag($langs->trans("Next")).'"></i></a></li>';
	}
	if ($afterarrows)
	{
		print '<li class="paginationafterarrows">';
		print $afterarrows;
		print '</li>';
	}
	print '</ul></div>'."\n";
}


/**
 *	Return a string with VAT rate label formated for view output
 *	Used into pdf and HTML pages
 *
 *	@param	string	$rate			Rate value to format ('19.6', '19,6', '19.6%', '19,6%', '19.6 (CODEX)', ...)
 *  @param	boolean	$addpercent		Add a percent % sign in output
 *	@param	int		$info_bits		Miscellaneous information on vat (0=Default, 1=French NPR vat)
 *	@param	int		$usestarfornpr	-1=Never show, 0 or 1=Use '*' for NPR vat rates
 *  @return	string					String with formated amounts ('19,6' or '19,6%' or '8.5% (NPR)' or '8.5% *' or '19,6 (CODEX)')
 */
function vatrate($rate, $addpercent = false, $info_bits = 0, $usestarfornpr = 0)
{
	$morelabel = '';

	if (preg_match('/%/', $rate))
	{
		$rate = str_replace('%', '', $rate);
		$addpercent = true;
	}
	if (preg_match('/\((.*)\)/', $rate, $reg))
	{
		$morelabel = ' ('.$reg[1].')';
		$rate = preg_replace('/\s*'.preg_quote($morelabel, '/').'/', '', $rate);
	}
	if (preg_match('/\*/', $rate))
	{
		$rate = str_replace('*', '', $rate);
		$info_bits |= 1;
	}

	// If rate is '9/9/9' we don't change it.  If rate is '9.000' we apply price()
	if (!preg_match('/\//', $rate)) $ret = price($rate, 0, '', 0, 0).($addpercent ? '%' : '');
	else
	{
		// TODO Split on / and output with a price2num to have clean numbers without ton of 000.
		$ret = $rate.($addpercent ? '%' : '');
	}
	if (($info_bits & 1) && $usestarfornpr >= 0) $ret .= ' *';
	$ret .= $morelabel;
	return $ret;
}


/**
 *		Function to format a value into an amount for visual output
 *		Function used into PDF and HTML pages
 *
 *		@param	float		$amount			Amount to format
 *		@param	integer		$form			Type of format, HTML or not (not by default)
 *		@param	Translate	$outlangs		Object langs for output
 *		@param	int			$trunc			1=Truncate if there is more decimals than MAIN_MAX_DECIMALS_SHOWN (default), 0=Does not truncate. Deprecated because amount are rounded (to unit or total amount accurancy) before beeing inserted into database or after a computation, so this parameter should be useless.
 *		@param	int			$rounding		Minimum number of decimal to show. If 0, no change, if -1, we use min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT)
 *		@param	int			$forcerounding	Force the number of decimal to forcerounding decimal (-1=do not force)
 *		@param	string		$currency_code	To add currency symbol (''=add nothing, 'auto'=Use default currency, 'XXX'=add currency symbols for XXX currency)
 *		@return	string						Chaine avec montant formate
 *
 *		@see	price2num()					Revert function of price
 */
function price($amount, $form = 0, $outlangs = '', $trunc = 1, $rounding = -1, $forcerounding = -1, $currency_code = '')
{
	global $langs, $conf;

	// Clean parameters
	if (empty($amount)) $amount = 0; // To have a numeric value if amount not defined or = ''
	$amount = (is_numeric($amount) ? $amount : 0); // Check if amount is numeric, for example, an error occured when amount value = o (letter) instead 0 (number)
	if ($rounding < 0) $rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);
	$nbdecimal = $rounding;

	// Output separators by default (french)
	$dec = ','; $thousand = ' ';

	// If $outlangs not forced, we use use language
	if (!is_object($outlangs)) $outlangs = $langs;

	if ($outlangs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec = $outlangs->transnoentitiesnoconv("SeparatorDecimal");
	if ($outlangs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $outlangs->transnoentitiesnoconv("SeparatorThousand");
	if ($thousand == 'None') $thousand = '';
	elseif ($thousand == 'Space') $thousand = ' ';
	//print "outlangs=".$outlangs->defaultlang." amount=".$amount." html=".$form." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

	//print "amount=".$amount."-";
	$amount = str_replace(',', '.', $amount); // should be useless
	//print $amount."-";
	$datas = explode('.', $amount);
	$decpart = isset($datas[1]) ? $datas[1] : '';
	$decpart = preg_replace('/0+$/i', '', $decpart); // Supprime les 0 de fin de partie decimale
	//print "decpart=".$decpart."<br>";
	$end = '';

	// We increase nbdecimal if there is more decimal than asked (to not loose information)
	if (dol_strlen($decpart) > $nbdecimal) $nbdecimal = dol_strlen($decpart);
	// Si on depasse max
	if ($trunc && $nbdecimal > $conf->global->MAIN_MAX_DECIMALS_SHOWN)
	{
		$nbdecimal = $conf->global->MAIN_MAX_DECIMALS_SHOWN;
		if (preg_match('/\.\.\./i', $conf->global->MAIN_MAX_DECIMALS_SHOWN))
		{
			// Si un affichage est tronque, on montre des ...
			$end = '...';
		}
	}

	// If force rounding
	if ($forcerounding >= 0) $nbdecimal = $forcerounding;

	// Format number
	$output = number_format($amount, $nbdecimal, $dec, $thousand);
	if ($form)
	{
		$output = preg_replace('/\s/', '&nbsp;', $output);
		$output = preg_replace('/\'/', '&#039;', $output);
	}
	// Add symbol of currency if requested
	$cursymbolbefore = $cursymbolafter = '';
	if ($currency_code)
	{
		if ($currency_code == 'auto') $currency_code = $conf->currency;

		$listofcurrenciesbefore = array('AUD', 'CAD', 'CNY', 'COP', 'CLP', 'GBP', 'HKD', 'MXN', 'PEN', 'USD');
		$listoflanguagesbefore = array('nl_NL');
		if (in_array($currency_code, $listofcurrenciesbefore) || in_array($outlangs->defaultlang, $listoflanguagesbefore))
		{
		    $cursymbolbefore .= $outlangs->getCurrencySymbol($currency_code);
		}
		else
		{
			$tmpcur = $outlangs->getCurrencySymbol($currency_code);
			$cursymbolafter .= ($tmpcur == $currency_code ? ' '.$tmpcur : $tmpcur);
		}
	}
	$output = $cursymbolbefore.$output.$end.($cursymbolafter ? ' ' : '').$cursymbolafter;

	return $output;
}

/**
 *	Function that return a number with universal decimal format (decimal separator is '.') from an amount typed by a user.
 *	Function to use on each input amount before any numeric test or database insert. A better name for this function
 *  should be text2num().
 *
 *	@param	float	$amount			Amount to convert/clean
 *	@param	string	$rounding		''=No rounding
 * 									'MU'=Round to Max unit price (MAIN_MAX_DECIMALS_UNIT)
 *									'MT'=Round to Max for totals with Tax (MAIN_MAX_DECIMALS_TOT)
 *									'MS'=Round to Max for stock quantity (MAIN_MAX_DECIMALS_STOCK)
 *									Numeric = Nb of digits for rounding
 * 	@param	int		$alreadysqlnb	Put 1 if you know that content is already universal format number
 *	@return	string					Amount with universal numeric format (Example: '99.99999').
 *									If conversion fails, it return text unchanged if $rounding = '' or '0' if $rounding is defined.
 *									If amount is null or '', it returns '' if $rounding = '' or '0' if $rounding is defined..
 *
 *	@see    price()					Opposite function of price2num
 */
function price2num($amount, $rounding = '', $alreadysqlnb = 0)
{
	global $langs, $conf;

	// Round PHP function does not allow number like '1,234.56' nor '1.234,56' nor '1 234,56'
	// Numbers must be '1234.56'
	// Decimal delimiter for PHP and database SQL requests must be '.'
	$dec = ','; $thousand = ' ';
	if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
	if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
	if ($thousand == 'None') $thousand = '';
	elseif ($thousand == 'Space') $thousand = ' ';
	//print "amount=".$amount." html=".$form." trunc=".$trunc." nbdecimal=".$nbdecimal." dec='".$dec."' thousand='".$thousand."'<br>";

	// Convert value to universal number format (no thousand separator, '.' as decimal separator)
	if ($alreadysqlnb != 1)	// If not a PHP number or unknown, we change format
	{
		//print 'PP'.$amount.' - '.$dec.' - '.$thousand.' - '.intval($amount).'<br>';

		// Convert amount to format with dolibarr dec and thousand (this is because PHP convert a number
		// to format defined by LC_NUMERIC after a calculation and we want source format to be like defined by Dolibarr setup.
		if (is_numeric($amount))
		{
			// We put in temps value of decimal ("0.00001"). Works with 0 and 2.0E-5 and 9999.10
			$temps = sprintf("%0.10F", $amount - intval($amount)); // temps=0.0000000000 or 0.0000200000 or 9999.1000000000
			$temps = preg_replace('/([\.1-9])0+$/', '\\1', $temps); // temps=0. or 0.00002 or 9999.1
			$nbofdec = max(0, dol_strlen($temps) - 2); // -2 to remove "0."
			$amount = number_format($amount, $nbofdec, $dec, $thousand);
		}
		//print "QQ".$amount.'<br>';

		// Now make replace (the main goal of function)
		if ($thousand != ',' && $thousand != '.') $amount = str_replace(',', '.', $amount); // To accept 2 notations for french users
		$amount = str_replace(' ', '', $amount); // To avoid spaces
		$amount = str_replace($thousand, '', $amount); // Replace of thousand before replace of dec to avoid pb if thousand is .
		$amount = str_replace($dec, '.', $amount);
	}

	// Now, make a rounding if required
	if ($rounding)
	{
		$nbofdectoround = '';
		if ($rounding == 'MU')     $nbofdectoround = $conf->global->MAIN_MAX_DECIMALS_UNIT;
		elseif ($rounding == 'MT') $nbofdectoround = $conf->global->MAIN_MAX_DECIMALS_TOT;
		elseif ($rounding == 'MS') $nbofdectoround = empty($conf->global->MAIN_MAX_DECIMALS_STOCK) ? 5 : $conf->global->MAIN_MAX_DECIMALS_STOCK;
		elseif (is_numeric($rounding))  $nbofdectoround = $rounding;
		//print "RR".$amount.' - '.$nbofdectoround.'<br>';
		if (dol_strlen($nbofdectoround)) $amount = round($amount, $nbofdectoround); // $nbofdectoround can be 0.
		else return 'ErrorBadParameterProvidedToFunction';
		//print 'SS'.$amount.' - '.$nbofdec.' - '.$dec.' - '.$thousand.' - '.$nbofdectoround.'<br>';

		// Convert amount to format with dolibarr dec and thousand (this is because PHP convert a number
		// to format defined by LC_NUMERIC after a calculation and we want source format to be defined by Dolibarr setup.
		if (is_numeric($amount))
		{
			// We put in temps value of decimal ("0.00001"). Works with 0 and 2.0E-5 and 9999.10
			$temps = sprintf("%0.10F", $amount - intval($amount)); // temps=0.0000000000 or 0.0000200000 or 9999.1000000000
			$temps = preg_replace('/([\.1-9])0+$/', '\\1', $temps); // temps=0. or 0.00002 or 9999.1
			$nbofdec = max(0, dol_strlen($temps) - 2); // -2 to remove "0."
			$amount = number_format($amount, min($nbofdec, $nbofdectoround), $dec, $thousand); // Convert amount to format with dolibarr dec and thousand
		}
		//print "TT".$amount.'<br>';

		// Always make replace because each math function (like round) replace
		// with local values and we want a number that has a SQL string format x.y
		if ($thousand != ',' && $thousand != '.') $amount = str_replace(',', '.', $amount); // To accept 2 notations for french users
		$amount = str_replace(' ', '', $amount); // To avoid spaces
		$amount = str_replace($thousand, '', $amount); // Replace of thousand before replace of dec to avoid pb if thousand is .
		$amount = str_replace($dec, '.', $amount);
	}

	return $amount;
}

/**
 * Output a dimension with best unit
 *
 * @param   float       $dimension      Dimension
 * @param   int         $unit           Unit scale of dimension (Example: 0=kg, -3=g, -6=mg, 98=ounce, 99=pound, ...)
 * @param   string      $type           'weight', 'volume', ...
 * @param   Translate   $outputlangs    Translate language object
 * @param   int         $round          -1 = non rounding, x = number of decimal
 * @param   string      $forceunitoutput    'no' or numeric (-3, -6, ...) compared to $unit (In most case, this value is value defined into $conf->global->MAIN_WEIGHT_DEFAULT_UNIT)
 * @return  string                      String to show dimensions
 */
function showDimensionInBestUnit($dimension, $unit, $type, $outputlangs, $round = -1, $forceunitoutput = 'no')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

	if (($forceunitoutput == 'no' && $dimension < 1 / 10000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == -6))
	{
		$dimension = $dimension * 1000000;
		$unit = $unit - 6;
	}
	elseif (($forceunitoutput == 'no' && $dimension < 1 / 10 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == -3))
	{
		$dimension = $dimension * 1000;
		$unit = $unit - 3;
	}
	elseif (($forceunitoutput == 'no' && $dimension > 100000000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == 6))
	{
		$dimension = $dimension / 1000000;
		$unit = $unit + 6;
	}
	elseif (($forceunitoutput == 'no' && $dimension > 100000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == 3))
	{
		$dimension = $dimension / 1000;
		$unit = $unit + 3;
	}
	// Special case when we want output unit into pound or ounce
	/* TODO
	if ($unit < 90 && $type == 'weight' && is_numeric($forceunitoutput) && (($forceunitoutput == 98) || ($forceunitoutput == 99))
	{
	    $dimension = // convert dimension from standard unit into ounce or pound
	    $unit = $forceunitoutput;
	}
	if ($unit > 90 && $type == 'weight' && is_numeric($forceunitoutput) && $forceunitoutput < 90)
	{
	    $dimension = // convert dimension from standard unit into ounce or pound
	    $unit = $forceunitoutput;
	}*/

	$ret = price($dimension, 0, $outputlangs, 0, 0, $round).' '.measuringUnitString(0, $type, $unit);

	return $ret;
}


/**
 *	Return localtax rate for a particular vat, when selling a product with vat $vatrate, from a $thirdparty_buyer to a $thirdparty_seller
 *  Note: This function applies same rules than get_default_tva
 *
 * 	@param	float		$vatrate		        Vat rate. Can be '8.5' or '8.5 (VATCODEX)' for example
 * 	@param  int			$local		         	Local tax to search and return (1 or 2 return only tax rate 1 or tax rate 2)
 *  @param  Societe		$thirdparty_buyer    	Object of buying third party
 *  @param	Societe		$thirdparty_seller		Object of selling third party ($mysoc if not defined)
 *  @param	int			$vatnpr					If vat rate is NPR or not
 * 	@return	mixed			   					0 if not found, localtax rate if found
 *  @see get_default_tva()
 */
function get_localtax($vatrate, $local, $thirdparty_buyer = "", $thirdparty_seller = "", $vatnpr = 0)
{
	global $db, $conf, $mysoc;

	if (empty($thirdparty_seller) || !is_object($thirdparty_seller)) $thirdparty_seller = $mysoc;

	dol_syslog("get_localtax tva=".$vatrate." local=".$local." thirdparty_buyer id=".(is_object($thirdparty_buyer) ? $thirdparty_buyer->id : '')."/country_code=".(is_object($thirdparty_buyer) ? $thirdparty_buyer->country_code : '')." thirdparty_seller id=".$thirdparty_seller->id."/country_code=".$thirdparty_seller->country_code." thirdparty_seller localtax1_assuj=".$thirdparty_seller->localtax1_assuj."  thirdparty_seller localtax2_assuj=".$thirdparty_seller->localtax2_assuj);

	$vatratecleaned = $vatrate;
	$reg = array();
	if (preg_match('/^(.*)\s*\((.*)\)$/', $vatrate, $reg))      // If vat is "xx (yy)"
	{
		$vatratecleaned = trim($reg[1]);
		$vatratecode = $reg[2];
	}

	/*if ($thirdparty_buyer->country_code != $thirdparty_seller->country_code)
	{
		return 0;
	}*/

	// Some test to guess with no need to make database access
	if ($mysoc->country_code == 'ES') // For spain localtaxes 1 and 2, tax is qualified if buyer use local tax
	{
		if ($local == 1)
		{
			if (!$mysoc->localtax1_assuj || (string) $vatratecleaned == "0") return 0;
			if ($thirdparty_seller->id == $mysoc->id)
			{
				if (!$thirdparty_buyer->localtax1_assuj) return 0;
			}
			else
			{
				if (!$thirdparty_seller->localtax1_assuj) return 0;
			}
		}

		if ($local == 2)
		{
			//if (! $mysoc->localtax2_assuj || (string) $vatratecleaned == "0") return 0;
			if (!$mysoc->localtax2_assuj) return 0; // If main vat is 0, IRPF may be different than 0.
			if ($thirdparty_seller->id == $mysoc->id)
			{
				if (!$thirdparty_buyer->localtax2_assuj) return 0;
			}
			else
			{
				if (!$thirdparty_seller->localtax2_assuj) return 0;
			}
		}
	}
	else
	{
		if ($local == 1 && !$thirdparty_seller->localtax1_assuj) return 0;
		if ($local == 2 && !$thirdparty_seller->localtax2_assuj) return 0;
	}

	// For some country MAIN_GET_LOCALTAXES_VALUES_FROM_THIRDPARTY is forced to on.
	if (in_array($mysoc->country_code, array('ES')))
	{
		$conf->global->MAIN_GET_LOCALTAXES_VALUES_FROM_THIRDPARTY = 1;
	}

	// Search local taxes
	if (!empty($conf->global->MAIN_GET_LOCALTAXES_VALUES_FROM_THIRDPARTY))
	{
		if ($local == 1)
		{
			if ($thirdparty_seller != $mysoc)
			{
				if (!isOnlyOneLocalTax($local))  // TODO We should provide $vatrate to search on correct line and not always on line with highest vat rate
				{
					return $thirdparty_seller->localtax1_value;
				}
			}
			else  // i am the seller
			{
				if (!isOnlyOneLocalTax($local))  // TODO If seller is me, why not always returning this, even if there is only one locatax vat.
				{
					return $conf->global->MAIN_INFO_VALUE_LOCALTAX1;
				}
			}
		}
		if ($local == 2)
		{
			if ($thirdparty_seller != $mysoc)
			{
				if (!isOnlyOneLocalTax($local))  // TODO We should provide $vatrate to search on correct line and not always on line with highest vat rate
				// TODO We should also return value defined on thirdparty only if defined
				{
					return $thirdparty_seller->localtax2_value;
				}
			}
			else  // i am the seller
			{
				if (in_array($mysoc->country_code, array('ES')))
				{
					return $thirdparty_buyer->localtax2_value;
				}
				else
				{
					return $conf->global->MAIN_INFO_VALUE_LOCALTAX2;
				}
			}
		}
	}

	// By default, search value of local tax on line of common tax
	$sql = "SELECT t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
   	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
   	$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$thirdparty_seller->country_code."'";
   	$sql .= " AND t.taux = ".((float) $vatratecleaned)." AND t.active = 1";
   	if ($vatratecode) $sql .= " AND t.code ='".$vatratecode."'"; // If we have the code, we use it in priority
   	else $sql .= " AND t.recuperableonly ='".$vatnpr."'";
   	dol_syslog("get_localtax", LOG_DEBUG);
   	$resql = $db->query($sql);

   	if ($resql)
   	{
   		$obj = $db->fetch_object($resql);
   		if ($local == 1) return $obj->localtax1;
   		elseif ($local == 2) return $obj->localtax2;
	}

	return 0;
}


/**
 * Return true if LocalTax (1 or 2) is unique.
 * Example: If localtax1 is 5 on line with highest common vat rate, return true
 * Example: If localtax1 is 5:8:15 on line with highest common vat rate, return false
 *
 * @param   int 	$local	Local tax to test (1 or 2)
 * @return  boolean 		True if LocalTax have multiple values, False if not
 */
function isOnlyOneLocalTax($local)
{
	$tax = get_localtax_by_third($local);

	$valors = explode(":", $tax);

	if (count($valors) > 1)
	{
		return false;
	}
	else
	{
		return true;
	}
}

/**
 * Get values of localtaxes (1 or 2) for company country for the common vat with the highest value
 *
 * @param	int		$local 	LocalTax to get
 * @return	number			Values of localtax
 */
function get_localtax_by_third($local)
{
	global $db, $mysoc;
	$sql = "SELECT t.localtax1, t.localtax2 ";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t inner join ".MAIN_DB_PREFIX."c_country as c ON c.rowid=t.fk_pays";
	$sql .= " WHERE c.code = '".$mysoc->country_code."' AND t.active = 1 AND t.taux=(";
	$sql .= "  SELECT max(tt.taux) FROM ".MAIN_DB_PREFIX."c_tva as tt inner join ".MAIN_DB_PREFIX."c_country as c ON c.rowid=tt.fk_pays";
	$sql .= "  WHERE c.code = '".$mysoc->country_code."' AND tt.active = 1";
	$sql .= "  )";

	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($local == 1) return $obj->localtax1;
		elseif ($local == 2) return $obj->localtax2;
	}

	return 0;
}


/**
 *  Get vat main information from Id.
 *  You can call getLocalTaxesFromRate after to get other fields.
 *
 *  @param	int|string  $vatrate		    VAT ID or Rate. Value can be value or the string with code into parenthesis or rowid if $firstparamisid is 1. Example: '8.5' or '8.5 (8.5NPR)' or 123.
 *  @param	Societe	    $buyer         		Company object
 *  @param	Societe	    $seller        		Company object
 *  @param  int         $firstparamisid     1 if first param is id into table (use this if you can)
 *  @return	array       	  				array('rowid'=> , 'code'=> ...)
 *  @see getLocalTaxesFromRate()
 */
function getTaxesFromId($vatrate, $buyer = null, $seller = null, $firstparamisid = 1)
{
	global $db, $mysoc;

	dol_syslog("getTaxesFromId vat id or rate = ".$vatrate);

	// Search local taxes
	$sql = "SELECT t.rowid, t.code, t.taux as rate, t.recuperableonly as npr, t.accountancy_code_sell, t.accountancy_code_buy";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
	if ($firstparamisid) $sql .= " WHERE t.rowid = ".(int) $vatrate;
	else
	{
		$vatratecleaned = $vatrate;
		$vatratecode = '';
		if (preg_match('/^(.*)\s*\((.*)\)$/', $vatrate, $reg))      // If vat is "xx (yy)"
		{
			$vatratecleaned = $reg[1];
			$vatratecode = $reg[2];
		}

		$sql .= ", ".MAIN_DB_PREFIX."c_country as c";
		/*if ($mysoc->country_code == 'ES') $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$buyer->country_code."'";    // vat in spain use the buyer country ??
		else $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$seller->country_code."'";*/
		$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$seller->country_code."'";
		$sql .= " AND t.taux = ".((float) $vatratecleaned)." AND t.active = 1";
		if ($vatratecode) $sql .= " AND t.code = '".$vatratecode."'";
	}

	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj) return array('rowid'=>$obj->rowid, 'code'=>$obj->code, 'rate'=>$obj->rate, 'npr'=>$obj->npr, 'accountancy_code_sell'=>$obj->accountancy_code_sell, 'accountancy_code_buy'=>$obj->accountancy_code_buy);
		else return array();
	}
	else dol_print_error($db);

	return array();
}

/**
 *  Get type and rate of localtaxes for a particular vat rate/country of a thirdparty.
 *  This does not take into account the seller setup if subject to vat or not, only country.
 *  TODO
 *  This function is ALSO called to retrieve type for building PDF. Such call of function must be removed.
 *  Instead this function must be called when adding a line to get the array of localtax and type, and then
 *  provide it to the function calcul_price_total.
 *
 *  @param	int|string  $vatrate			VAT ID or Rate+Code. Value can be value or the string with code into parenthesis or rowid if $firstparamisid is 1. Example: '8.5' or '8.5 (8.5NPR)' or 123.
 *  @param	int		    $local              Number of localtax (1 or 2, or 0 to return 1 & 2)
 *  @param	Societe	    $buyer         		Company object
 *  @param	Societe	    $seller        		Company object
 *  @param  int         $firstparamisid     1 if first param is ID into table instead of Rate+code (use this if you can)
 *  @return	array    	    				array(localtax_type1(1-6/0 if not found), rate localtax1, localtax_type2, rate localtax2, accountancycodecust, accountancycodesupp)
 *  @see getTaxesFromId()
 */
function getLocalTaxesFromRate($vatrate, $local, $buyer, $seller, $firstparamisid = 0)
{
	global $db, $mysoc;

	dol_syslog("getLocalTaxesFromRate vatrate=".$vatrate." local=".$local);

	// Search local taxes
	$sql  = "SELECT t.taux as rate, t.code, t.localtax1, t.localtax1_type, t.localtax2, t.localtax2_type, t.accountancy_code_sell, t.accountancy_code_buy";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t";
	if ($firstparamisid) $sql .= " WHERE t.rowid = ".(int) $vatrate;
	else
	{
		$vatratecleaned = $vatrate;
		$vatratecode = '';
		$reg = array();
		if (preg_match('/^(.*)\s*\((.*)\)$/', $vatrate, $reg))      // If vat is "x.x (yy)"
		{
			$vatratecleaned = $reg[1];
			$vatratecode = $reg[2];
		}

		$sql .= ", ".MAIN_DB_PREFIX."c_country as c";
		if ($mysoc->country_code == 'ES') $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$buyer->country_code."'"; // local tax in spain use the buyer country ??
		else $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$seller->country_code."'";
		$sql .= " AND t.taux = ".((float) $vatratecleaned)." AND t.active = 1";
		if ($vatratecode) $sql .= " AND t.code = '".$vatratecode."'";
	}

	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);

		$vateratestring = $obj->rate.($obj->code ? ' ('.$obj->code.')' : '');

		if ($local == 1)
		{
			return array($obj->localtax1_type, get_localtax($vateratestring, $local, $buyer, $seller), $obj->accountancy_code_sell, $obj->accountancy_code_buy);
		}
		elseif ($local == 2)
		{
			return array($obj->localtax2_type, get_localtax($vateratestring, $local, $buyer, $seller), $obj->accountancy_code_sell, $obj->accountancy_code_buy);
		}
		else
		{
			return array($obj->localtax1_type, get_localtax($vateratestring, 1, $buyer, $seller), $obj->localtax2_type, get_localtax($vateratestring, 2, $buyer, $seller), $obj->accountancy_code_sell, $obj->accountancy_code_buy);
		}
	}

	return 0;
}

/**
 *	Return vat rate of a product in a particular selling country or default country vat if product is unknown
 *  Function called by get_default_tva
 *
 *  @param	int			$idprod          	Id of product or 0 if not a predefined product
 *  @param  Societe		$thirdparty_seller  Thirdparty with a ->country_code defined (FR, US, IT, ...)
 *	@param	int			$idprodfournprice	Id product_fournisseur_price (for "supplier" proposal/order/invoice)
 *  @return float|string   				    Vat rate to use with format 5.0 or '5.0 (XXX)'
 *  @see get_product_localtax_for_country()
 */
function get_product_vat_for_country($idprod, $thirdparty_seller, $idprodfournprice = 0)
{
	global $db, $conf, $mysoc;

	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	$ret = 0;
	$found = 0;

	if ($idprod > 0)
	{
		// Load product
		$product = new Product($db);
		$result = $product->fetch($idprod);

		if ($mysoc->country_code == $thirdparty_seller->country_code) // If selling country is ours
		{
			if ($idprodfournprice > 0)     // We want vat for product for a "supplier" object
			{
				$product->get_buyprice($idprodfournprice, 0, 0, 0);
				$ret = $product->vatrate_supplier;
				if ($product->default_vat_code) $ret .= ' ('.$product->default_vat_code.')';
			}
			else
			{
				$ret = $product->tva_tx; // Default vat of product we defined
				if ($product->default_vat_code) $ret .= ' ('.$product->default_vat_code.')';
			}
			$found = 1;
		}
		else
		{
			// TODO Read default product vat according to countrycode and product. Vat for couple countrycode/product is a feature not implemeted yet.
			// May be usefull/required if hidden option SERVICE_ARE_ECOMMERCE_200238EC is on
		}
	}

	if (!$found)
	{
		if (empty($conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS))
		{
			// If vat of product for the country not found or not defined, we return the first higher vat of country.
			$sql = "SELECT t.taux as vat_rate, t.code as default_vat_code";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
			$sql .= " WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$thirdparty_seller->country_code."'";
			$sql .= " ORDER BY t.taux DESC, t.code ASC, t.recuperableonly ASC";
			$sql .= $db->plimit(1);

			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$ret = $obj->vat_rate;
					if ($obj->default_vat_code) $ret .= ' ('.$obj->default_vat_code.')';
				}
				$db->free($sql);
			}
			else dol_print_error($db);
		}
		else $ret = $conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS; // Forced value if autodetect fails
	}

	dol_syslog("get_product_vat_for_country: ret=".$ret);
	return $ret;
}

/**
 *	Return localtax vat rate of a product in a particular selling country or default country vat if product is unknown
 *
 *  @param	int		$idprod         		Id of product
 *  @param  int		$local          		1 for localtax1, 2 for localtax 2
 *  @param  Societe	$thirdparty_seller    	Thirdparty with a ->country_code defined (FR, US, IT, ...)
 *  @return int             				<0 if KO, Vat rate if OK
 *  @see get_product_vat_for_country()
 */
function get_product_localtax_for_country($idprod, $local, $thirdparty_seller)
{
	global $db, $mysoc;

	if (!class_exists('Product')) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	}

	$ret = 0;
	$found = 0;

	if ($idprod > 0)
	{
		// Load product
		$product = new Product($db);
		$result = $product->fetch($idprod);

		if ($mysoc->country_code == $thirdparty_seller->country_code) // If selling country is ours
		{
			/* Not defined yet, so we don't use this
			if ($local==1) $ret=$product->localtax1_tx;
			elseif ($local==2) $ret=$product->localtax2_tx;
			$found=1;
			*/
		}
		else
		{
			// TODO Read default product vat according to countrycode and product
		}
	}

	if (!$found)
	{
		// If vat of product for the country not found or not defined, we return higher vat of country.
		$sql = "SELECT taux as vat_rate, localtax1, localtax2";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$thirdparty_seller->country_code."'";
		$sql .= " ORDER BY t.taux DESC, t.recuperableonly ASC";
		$sql .= $db->plimit(1);

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				if ($local == 1) $ret = $obj->localtax1;
				elseif ($local == 2) $ret = $obj->localtax2;
			}
		}
		else dol_print_error($db);
	}

	dol_syslog("get_product_localtax_for_country: ret=".$ret);
	return $ret;
}

/**
 *	Function that return vat rate of a product line (according to seller, buyer and product vat rate)
 *   Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 *	 Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 *	 Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
 *	 Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier ou entreprise sans num TVA intra) alors TVA par defaut=TVA du produit vendu. Fin de regle
 *	 Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise avec num TVA) intra alors TVA par defaut=0. Fin de regle
 *	 Sinon TVA proposee par defaut=0. Fin de regle.
 *
 *	@param	Societe		$thirdparty_seller    	Objet societe vendeuse
 *	@param  Societe		$thirdparty_buyer   	Objet societe acheteuse
 *	@param  int			$idprod					Id product
 *	@param	int			$idprodfournprice		Id product_fournisseur_price (for supplier order/invoice)
 *	@return float|string   				      	Vat rate to use with format 5.0 or '5.0 (XXX)', -1 if we can't guess it
 *  @see get_default_npr(), get_default_localtax()
 */
function get_default_tva(Societe $thirdparty_seller, Societe $thirdparty_buyer, $idprod = 0, $idprodfournprice = 0)
{
	global $conf;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

	// Note: possible values for tva_assuj are 0/1 or franchise/reel
	$seller_use_vat = ((is_numeric($thirdparty_seller->tva_assuj) && !$thirdparty_seller->tva_assuj) || (!is_numeric($thirdparty_seller->tva_assuj) && $thirdparty_seller->tva_assuj == 'franchise')) ? 0 : 1;

	$seller_country_code = $thirdparty_seller->country_code;
	$seller_in_cee = isInEEC($thirdparty_seller);

	$buyer_country_code = $thirdparty_buyer->country_code;
	$buyer_in_cee = isInEEC($thirdparty_buyer);

	dol_syslog("get_default_tva: seller use vat=".$seller_use_vat.", seller country=".$seller_country_code.", seller in cee=".$seller_in_cee.", buyer vat number=".$thirdparty_buyer->tva_intra." buyer country=".$buyer_country_code.", buyer in cee=".$buyer_in_cee.", idprod=".$idprod.", idprodfournprice=".$idprodfournprice.", SERVICE_ARE_ECOMMERCE_200238EC=".(!empty($conf->global->SERVICES_ARE_ECOMMERCE_200238EC) ? $conf->global->SERVICES_ARE_ECOMMERCE_200238EC : ''));

	// If services are eServices according to EU Council Directive 2002/38/EC (http://ec.europa.eu/taxation_customs/taxation/vat/traders/e-commerce/article_1610_en.htm)
	// we use the buyer VAT.
	if (!empty($conf->global->SERVICE_ARE_ECOMMERCE_200238EC))
	{
		if ($seller_in_cee && $buyer_in_cee && !$thirdparty_buyer->isACompany())
		{
			//print 'VATRULE 0';
			return get_product_vat_for_country($idprod, $thirdparty_buyer, $idprodfournprice);
		}
	}

	// If seller does not use VAT
	if (!$seller_use_vat)
	{
		//print 'VATRULE 1';
		return 0;
	}

	// Le test ci-dessus ne devrait pas etre necessaire. Me signaler l'exemple du cas juridique concerne si le test suivant n'est pas suffisant.

	// Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
	if (($seller_country_code == $buyer_country_code)
	|| (in_array($seller_country_code, array('FR,MC')) && in_array($buyer_country_code, array('FR', 'MC')))) // Warning ->country_code not always defined
	{
		//print 'VATRULE 2';
		return get_product_vat_for_country($idprod, $thirdparty_seller, $idprodfournprice);
	}

	// Si (vendeur et acheteur dans Communaute europeenne) et (bien vendu = moyen de transports neuf comme auto, bateau, avion) alors TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
	// Not supported

	// Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = entreprise) alors TVA par defaut=0. Fin de regle
	// Si (vendeur et acheteur dans Communaute europeenne) et (acheteur = particulier) alors TVA par defaut=TVA du produit vendu. Fin de regle
	if (($seller_in_cee && $buyer_in_cee))
	{
		$isacompany = $thirdparty_buyer->isACompany();
		if ($isacompany)
		{
			//print 'VATRULE 3';
			return 0;
		}
		else
		{
			//print 'VATRULE 4';
			return get_product_vat_for_country($idprod, $thirdparty_seller, $idprodfournprice);
		}
	}

	// Si (vendeur en France et acheteur hors Communaute europeenne et acheteur particulier) alors TVA par defaut=TVA du produit vendu. Fin de regle
	if (!empty($conf->global->MAIN_USE_VAT_OF_PRODUCT_FOR_INDIVIDUAL_CUSTOMER_OUT_OF_EEC) && empty($buyer_in_cee) && !$thirdparty_buyer->isACompany()) {
		return get_product_vat_for_country($idprod, $thirdparty_seller, $idprodfournprice);
	}

	// Sinon la TVA proposee par defaut=0. Fin de regle.
	// Rem: Cela signifie qu'au moins un des 2 est hors Communaute europeenne et que le pays differe
	//print 'VATRULE 5';
	return 0;
}


/**
 *	Fonction qui renvoie si tva doit etre tva percue recuperable
 *
 *	@param	Societe		$thirdparty_seller    	Thirdparty seller
 *	@param  Societe		$thirdparty_buyer   	Thirdparty buyer
 *  @param  int			$idprod                 Id product
 *  @param	int			$idprodfournprice		Id supplier price for product
 *	@return float       			        	0 or 1
 *  @see get_default_tva(), get_default_localtax()
 */
function get_default_npr(Societe $thirdparty_seller, Societe $thirdparty_buyer, $idprod = 0, $idprodfournprice = 0)
{
	global $db;

	if ($idprodfournprice > 0)
	{
		if (!class_exists('ProductFournisseur')) {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		}
		$prodprice = new ProductFournisseur($db);
		$prodprice->fetch_product_fournisseur_price($idprodfournprice);
		return $prodprice->fourn_tva_npr;
	}
	elseif ($idprod > 0)
	{
		if (!class_exists('Product')) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		}
		$prod = new Product($db);
		$prod->fetch($idprod);
		return $prod->tva_npr;
	}

	return 0;
}

/**
 *	Function that return localtax of a product line (according to seller, buyer and product vat rate)
 *   Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
 *	 Si le (pays vendeur = pays acheteur) alors TVA par defaut=TVA du produit vendu. Fin de regle.
 *	 Sinon TVA proposee par defaut=0. Fin de regle.
 *
 *	@param	Societe		$thirdparty_seller    	Thirdparty seller
 *	@param  Societe		$thirdparty_buyer   	Thirdparty buyer
 *  @param	int			$local					Localtax to process (1 or 2)
 *	@param  int			$idprod					Id product
 *	@return integer        				       	localtax, -1 si ne peut etre determine
 *  @see get_default_tva(), get_default_npr()
 */
function get_default_localtax($thirdparty_seller, $thirdparty_buyer, $local, $idprod = 0)
{
	global $mysoc;

	if (!is_object($thirdparty_seller)) return -1;
	if (!is_object($thirdparty_buyer)) return -1;

	if ($local == 1) // Localtax 1
	{
		if ($mysoc->country_code == 'ES')
		{
			if (is_numeric($thirdparty_buyer->localtax1_assuj) && !$thirdparty_buyer->localtax1_assuj) return 0;
		}
		else
		{
			// Si vendeur non assujeti a Localtax1, localtax1 par default=0
			if (is_numeric($thirdparty_seller->localtax1_assuj) && !$thirdparty_seller->localtax1_assuj) return 0;
			if (!is_numeric($thirdparty_seller->localtax1_assuj) && $thirdparty_seller->localtax1_assuj == 'localtax1off') return 0;
		}
	}
	elseif ($local == 2) //I Localtax 2
	{
		// Si vendeur non assujeti a Localtax2, localtax2 par default=0
		if (is_numeric($thirdparty_seller->localtax2_assuj) && !$thirdparty_seller->localtax2_assuj) return 0;
		if (!is_numeric($thirdparty_seller->localtax2_assuj) && $thirdparty_seller->localtax2_assuj == 'localtax2off') return 0;
	}

	if ($thirdparty_seller->country_code == $thirdparty_buyer->country_code)
	{
		return get_product_localtax_for_country($idprod, $local, $thirdparty_seller);
	}

	return 0;
}

/**
 *	Return yes or no in current language
 *
 *	@param	string|int	$yesno			Value to test (1, 'yes', 'true' or 0, 'no', 'false')
 *	@param	integer		$case			1=Yes/No, 0=yes/no, 2=Disabled checkbox, 3=Disabled checkbox + Yes/No
 *	@param	int			$color			0=texte only, 1=Text is formated with a color font style ('ok' or 'error'), 2=Text is formated with 'ok' color.
 *	@return	string						HTML string
 */
function yn($yesno, $case = 1, $color = 0)
{
	global $langs;
	$result = 'unknown'; $classname = '';
	if ($yesno == 1 || strtolower($yesno) == 'yes' || strtolower($yesno) == 'true') 	// A mettre avant test sur no a cause du == 0
	{
		$result = $langs->trans('yes');
		if ($case == 1 || $case == 3) $result = $langs->trans("Yes");
		if ($case == 2) $result = '<input type="checkbox" value="1" checked disabled>';
		if ($case == 3) $result = '<input type="checkbox" value="1" checked disabled> '.$result;

		$classname = 'ok';
	}
	elseif ($yesno == 0 || strtolower($yesno) == 'no' || strtolower($yesno) == 'false')
	{
		$result = $langs->trans("no");
		if ($case == 1 || $case == 3) $result = $langs->trans("No");
		if ($case == 2) $result = '<input type="checkbox" value="0" disabled>';
		if ($case == 3) $result = '<input type="checkbox" value="0" disabled> '.$result;

		if ($color == 2) $classname = 'ok';
		else $classname = 'error';
	}
	if ($color) return '<font class="'.$classname.'">'.$result.'</font>';
	return $result;
}

/**
 *	Return a path to have a the directory according to object where files are stored.
 *  New usage:       $conf->module->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, $modulepart)
 *         or:       $conf->module->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, $modulepart)     if multidir_output not defined.
 *  Example our with new usage:       $object is invoice -> 'INYYMM-ABCD'
 *  Example our with old usage:       '015' with level 3->"0/1/5/", '015' with level 1->"5/", 'ABC-1' with level 3 ->"0/0/1/"
 *
 *	@param	string	$num            Id of object (deprecated, $object will be used in future)
 *	@param  int		$level		    Level of subdirs to return (1, 2 or 3 levels). (deprecated, global option will be used in future)
 * 	@param	int		$alpha		    0=Keep number only to forge path, 1=Use alpha part afer the - (By default, use 0). (deprecated, global option will be used in future)
 *  @param  int		$withoutslash   0=With slash at end (except if '/', we return ''), 1=without slash at end
 *  @param	Object	$object			Object
 *  @param	string	$modulepart		Type of object ('invoice_supplier, 'donation', 'invoice', ...')
 *  @return	string					Dir to use ending. Example '' or '1/' or '1/2/'
 */
function get_exdir($num, $level, $alpha, $withoutslash, $object, $modulepart)
{
	global $conf;

	$path = '';

	$arrayforoldpath = array('cheque', 'user', 'category', 'holiday', 'supplier_invoice', 'invoice_supplier', 'mailing', 'supplier_payment');
	if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) $arrayforoldpath[] = 'product';
	if (!empty($level) && in_array($modulepart, $arrayforoldpath))
	{
		// This part should be removed once all code is using "get_exdir" to forge path, with all parameters provided.
		if (empty($alpha)) $num = preg_replace('/([^0-9])/i', '', $num);
		else $num = preg_replace('/^.*\-/i', '', $num);
		$num = substr("000".$num, -$level);
		if ($level == 1) $path = substr($num, 0, 1);
		if ($level == 2) $path = substr($num, 1, 1).'/'.substr($num, 0, 1);
		if ($level == 3) $path = substr($num, 2, 1).'/'.substr($num, 1, 1).'/'.substr($num, 0, 1);
	}
	else
	{
		// TODO
		// We will enhance here a common way of forging path for document storage
		// Here, object->id, object->ref and modulepart are required.
		//var_dump($modulepart);
        if (in_array($modulepart, array('thirdparty', 'contact', 'member', 'propal', 'proposal', 'commande', 'order', 'facture', 'invoice',
			'supplier_order', 'supplier_proposal', 'shipment', 'contract', 'expensereport', 'ficheinter')))
		{
			$path = ($object->ref ? $object->ref : $object->id);
		}
	}

	if (empty($withoutslash) && !empty($path)) $path .= '/';

	return $path;
}

/**
 *	Creation of a directory (this can create recursive subdir)
 *
 *	@param	string		$dir		Directory to create (Separator must be '/'. Example: '/mydir/mysubdir')
 *	@param	string		$dataroot	Data root directory (To avoid having the data root in the loop. Using this will also lost the warning on first dir PHP has no permission when open_basedir is used)
 *  @param	string|null	$newmask	Mask for new file (Defaults to $conf->global->MAIN_UMASK or 0755 if unavailable). Example: '0444'
 *	@return int         			< 0 if KO, 0 = already exists, > 0 if OK
 */
function dol_mkdir($dir, $dataroot = '', $newmask = null)
{
	global $conf;

	dol_syslog("functions.lib::dol_mkdir: dir=".$dir, LOG_INFO);

	$dir_osencoded = dol_osencode($dir);
	if (@is_dir($dir_osencoded)) return 0;

	$nberr = 0;
	$nbcreated = 0;

	$ccdir = '';
	if (!empty($dataroot)) {
		// Remove data root from loop
		$dir = str_replace($dataroot.'/', '', $dir);
		$ccdir = $dataroot.'/';
	}

	$cdir = explode("/", $dir);
	$num = count($cdir);
	for ($i = 0; $i < $num; $i++)
	{
		if ($i > 0) $ccdir .= '/'.$cdir[$i];
		else $ccdir .= $cdir[$i];
		if (preg_match("/^.:$/", $ccdir, $regs)) continue; // Si chemin Windows incomplet, on poursuit par rep suivant

		// Attention, le is_dir() peut echouer bien que le rep existe.
		// (ex selon config de open_basedir)
		if ($ccdir)
		{
			$ccdir_osencoded = dol_osencode($ccdir);
			if (!@is_dir($ccdir_osencoded))
			{
				dol_syslog("functions.lib::dol_mkdir: Directory '".$ccdir."' does not exists or is outside open_basedir PHP setting.", LOG_DEBUG);

				umask(0);
				$dirmaskdec = octdec($newmask);
				if (empty($newmask)) {
					$dirmaskdec = empty($conf->global->MAIN_UMASK) ? octdec('0755') : octdec($conf->global->MAIN_UMASK);
				}
				$dirmaskdec |= octdec('0111'); // Set x bit required for directories
				if (!@mkdir($ccdir_osencoded, $dirmaskdec))
				{
					// Si le is_dir a renvoye une fausse info, alors on passe ici.
					dol_syslog("functions.lib::dol_mkdir: Fails to create directory '".$ccdir."' or directory already exists.", LOG_WARNING);
					$nberr++;
				}
				else
				{
					dol_syslog("functions.lib::dol_mkdir: Directory '".$ccdir."' created", LOG_DEBUG);
					$nberr = 0; // On remet a zero car si on arrive ici, cela veut dire que les echecs precedents peuvent etre ignore
					$nbcreated++;
				}
			}
			else
			{
				$nberr = 0; // On remet a zero car si on arrive ici, cela veut dire que les echecs precedents peuvent etre ignores
			}
		}
	}
	return ($nberr ? -$nberr : $nbcreated);
}


/**
 *	Return picto saying a field is required
 *
 *	@return  string		Chaine avec picto obligatoire
 */
function picto_required()
{
	return '<span class="fieldrequired">*</span>';
}


/**
 *	Clean a string from all HTML tags and entities.
 *  This function differs from strip_tags because:
 *  - <br> are replaced with \n if removelinefeed=0 or 1
 *  - if entities are found, they are decoded BEFORE the strip
 *  - you can decide to convert line feed into a space
 *
 *	@param	string	$stringtoclean		String to clean
 *	@param	integer	$removelinefeed		1=Replace all new lines by 1 space, 0=Only ending new lines are removed others are replaced with \n, 2=Ending new lines are removed but others are kept with a same number of \n than nb of <br> when there is both "...<br>\n..."
 *  @param  string	$pagecodeto      	Encoding of input/output string
 *  @param	integer	$strip_tags			0=Use internal strip, 1=Use strip_tags() php function (bugged when text contains a < char that is not for a html tag)
 *	@return string	    				String cleaned
 *
 * 	@see	dol_escape_htmltag() strip_tags() dol_string_onlythesehtmltags() dol_string_neverthesehtmltags()
 */
function dol_string_nohtmltag($stringtoclean, $removelinefeed = 1, $pagecodeto = 'UTF-8', $strip_tags = 0)
{
	if ($removelinefeed == 2) $stringtoclean = preg_replace('/<br[^>]*>(\n|\r)+/ims', '<br>', $stringtoclean);
	$temp = preg_replace('/<br[^>]*>/i', "\n", $stringtoclean);

	if ($strip_tags) {
		$temp = strip_tags($temp);
	} else {
		$pattern = "/<[^<>]+>/";
		// Exemple of $temp: <a href="/myurl" title="<u>A title</u>">0000-021</a>
		$temp = preg_replace($pattern, "", $temp); // pass 1
		// $temp after pass 1: <a href="/myurl" title="A title">0000-021
		$temp = preg_replace($pattern, "", $temp); // pass 2
		// $temp after pass 2: 0000-021
	}

	$temp = dol_html_entity_decode($temp, ENT_COMPAT, $pagecodeto);

	// Supprime aussi les retours
	if ($removelinefeed == 1) $temp = str_replace(array("\r\n", "\r", "\n"), " ", $temp);

	// et les espaces doubles
	while (strpos($temp, "  "))
	{
		$temp = str_replace("  ", " ", $temp);
	}

	return trim($temp);
}

/**
 *	Clean a string to keep only desirable HTML tags.
 *
 *	@param	string	$stringtoclean			String to clean
 *  @param	string	$cleanalsosomestyles	Clean also some tags
 *	@return string	    					String cleaned
 *
 * 	@see	dol_escape_htmltag() strip_tags() dol_string_nohtmltag() dol_string_neverthesehtmltags()
 */
function dol_string_onlythesehtmltags($stringtoclean, $cleanalsosomestyles = 1)
{
	$allowed_tags = array(
		"html", "head", "meta", "body", "article", "a", "b", "br", "div", "dl", "dd", "dt", "em", "font", "img", "ins", "hr", "i", "li", "link",
		"ol", "p", "s", "section", "span", "strong", "title",
		"table", "tr", "th", "td", "u", "ul"
	);
	$allowed_tags_string = join("><", $allowed_tags);
	$allowed_tags_string = preg_replace('/^>/', '', $allowed_tags_string);
	$allowed_tags_string = preg_replace('/<$/', '', $allowed_tags_string);
	$allowed_tags_string = '<'.$allowed_tags_string.'>';

	if ($cleanalsosomestyles) {
		$stringtoclean = preg_replace('/position\s*:\s*(absolute|fixed)\s*!\s*important/', '', $stringtoclean);	// Note: If hacker try to introduce css comment into string to bypass this regex, the string must also be encoded by the dol_htmlentitiesbr during output so it become harmless
	}

	$temp = strip_tags($stringtoclean, $allowed_tags_string);

	return $temp;
}

/**
 *	Clean a string from some undesirable HTML tags.
 *  Note. Not enough secured as dol_string_onlythesehtmltags().
 *
 *	@param	string	$stringtoclean			String to clean
 *  @param	array	$disallowed_tags		Array of tags not allowed
 *  @param	string	$cleanalsosomestyles	Clean also some tags
 *	@return string	    					String cleaned
 *
 * 	@see	dol_escape_htmltag() strip_tags() dol_string_nohtmltag() dol_string_onlythesehtmltags()
 */
function dol_string_neverthesehtmltags($stringtoclean, $disallowed_tags = array('textarea'), $cleanalsosomestyles = 0)
{
	$temp = $stringtoclean;
	foreach ($disallowed_tags as $tagtoremove)
	{
		$temp = preg_replace('/<\/?'.$tagtoremove.'>/', '', $temp);
		$temp = preg_replace('/<\/?'.$tagtoremove.'\s+[^>]*>/', '', $temp);
	}

	if ($cleanalsosomestyles) {
		$temp = preg_replace('/position\s*:\s*(absolute|fixed)\s*!\s*important/', '', $temp);	// Note: If hacker try to introduce css comment into string to avoid this, string should be encoded by the dol_htmlentitiesbr so be harmless
	}

	return $temp;
}


/**
 * Return first line of text. Cut will depends if content is HTML or not.
 *
 * @param 	string	$text		Input text
 * @param	int		$nboflines  Nb of lines to get (default is 1 = first line only)
 * @param   string  $charset    Charset of $text string (UTF-8 by default)
 * @return	string				Output text
 * @see dol_nboflines_bis(), dol_string_nohtmltag(), dol_escape_htmltag()
 */
function dolGetFirstLineOfText($text, $nboflines = 1, $charset = 'UTF-8')
{
	if ($nboflines == 1)
	{
		if (dol_textishtml($text))
		{
			$firstline = preg_replace('/<br[^>]*>.*$/s', '', $text); // The s pattern modifier means the . can match newline characters
			$firstline = preg_replace('/<div[^>]*>.*$/s', '', $firstline); // The s pattern modifier means the . can match newline characters
		}
		else
		{
			$firstline = preg_replace('/[\n\r].*/', '', $text);
		}
		return $firstline.((strlen($firstline) != strlen($text)) ? '...' : '');
	}
	else
	{
		$ishtml = 0;
		if (dol_textishtml($text))
		{
			$text = preg_replace('/\n/', '', $text);
			$ishtml = 1;
			$repTable = array("\t" => " ", "\n" => " ", "\r" => " ", "\0" => " ", "\x0B" => " ");
		}
		else
		{
			$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
		}

		$text = strtr($text, $repTable);
		if ($charset == 'UTF-8') { $pattern = '/(<br[^>]*>)/Uu'; }	// /U is to have UNGREEDY regex to limit to one html tag. /u is for UTF8 support
		else $pattern = '/(<br[^>]*>)/U'; // /U is to have UNGREEDY regex to limit to one html tag.
		$a = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$firstline = '';
		$i = 0;
		$nba = count($a); // 2x nb of lines in $a because $a contains also a line for each new line separator
		while (($i < $nba) && ($i < ($nboflines * 2)))
		{
			if ($i % 2 == 0) $firstline .= $a[$i];
			elseif (($i < (($nboflines * 2) - 1)) && ($i < ($nba - 1))) $firstline .= ($ishtml ? "<br>\n" : "\n");
			$i++;
		}
		unset($a);
		return $firstline.(($i < $nba) ? '...' : '');
	}
}


/**
 * Replace CRLF in string with a HTML BR tag
 *
 * @param	string	$stringtoencode		String to encode
 * @param	int     $nl2brmode			0=Adding br before \n, 1=Replacing \n by br
 * @param   bool	$forxml             false=Use <br>, true=Use <br />
 * @return	string						String encoded
 * @see dol_nboflines(), dolGetFirstLineOfText()
 */
function dol_nl2br($stringtoencode, $nl2brmode = 0, $forxml = false)
{
	if (!$nl2brmode) {
		return nl2br($stringtoencode, $forxml);
	} else {
		$ret = preg_replace('/(\r\n|\r|\n)/i', ($forxml ? '<br />' : '<br>'), $stringtoencode);
		return $ret;
	}
}


/**
 *	This function is called to encode a string into a HTML string but differs from htmlentities because
 * 	a detection is done before to see if text is already HTML or not. Also, all entities but &,<,>," are converted.
 *  This permits to encode special chars to entities with no double encoding for already encoded HTML strings.
 * 	This function also remove last EOL or BR if $removelasteolbr=1 (default).
 *  For PDF usage, you can show text by 2 ways:
 *              - writeHTMLCell -> param must be encoded into HTML.
 *              - MultiCell -> param must not be encoded into HTML.
 *              Because writeHTMLCell convert also \n into <br>, if function
 *              is used to build PDF, nl2brmode must be 1.
 *
 *	@param	string	$stringtoencode		String to encode
 *	@param	int		$nl2brmode			0=Adding br before \n, 1=Replacing \n by br (for use with FPDF writeHTMLCell function for example)
 *  @param  string	$pagecodefrom       Pagecode stringtoencode is encoded
 *  @param	int		$removelasteolbr	1=Remove last br or lasts \n (default), 0=Do nothing
 *  @return	string						String encoded
 */
function dol_htmlentitiesbr($stringtoencode, $nl2brmode = 0, $pagecodefrom = 'UTF-8', $removelasteolbr = 1)
{
	$newstring = $stringtoencode;
	if (dol_textishtml($stringtoencode))	// Check if text is already HTML or not
	{
		$newstring = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', '<br>', $newstring); // Replace "<br type="_moz" />" by "<br>". It's same and avoid pb with FPDF.
		if ($removelasteolbr) $newstring = preg_replace('/<br>$/i', '', $newstring); // Remove last <br> (remove only last one)
		$newstring = strtr($newstring, array('&'=>'__and__', '<'=>'__lt__', '>'=>'__gt__', '"'=>'__dquot__'));
		$newstring = dol_htmlentities($newstring, ENT_COMPAT, $pagecodefrom); // Make entity encoding
		$newstring = strtr($newstring, array('__and__'=>'&', '__lt__'=>'<', '__gt__'=>'>', '__dquot__'=>'"'));
	}
	else
	{
		if ($removelasteolbr) $newstring = preg_replace('/(\r\n|\r|\n)$/i', '', $newstring); // Remove last \n (may remove several)
		$newstring = dol_nl2br(dol_htmlentities($newstring, ENT_COMPAT, $pagecodefrom), $nl2brmode);
	}
	// Other substitutions that htmlentities does not do
	//$newstring=str_replace(chr(128),'&euro;',$newstring);	// 128 = 0x80. Not in html entity table.     // Seems useles with TCPDF. Make bug with UTF8 languages
	return $newstring;
}

/**
 *	This function is called to decode a HTML string (it decodes entities and br tags)
 *
 *	@param	string	$stringtodecode		String to decode
 *	@param	string	$pagecodeto			Page code for result
 *	@return	string						String decoded
 */
function dol_htmlentitiesbr_decode($stringtodecode, $pagecodeto = 'UTF-8')
{
	$ret = dol_html_entity_decode($stringtodecode, ENT_COMPAT, $pagecodeto);
	$ret = preg_replace('/'."\r\n".'<br(\s[\sa-zA-Z_="]*)?\/?>/i', "<br>", $ret);
	$ret = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>'."\r\n".'/i', "\r\n", $ret);
	$ret = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>'."\n".'/i', "\n", $ret);
	$ret = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', "\n", $ret);
	return $ret;
}

/**
 *	This function remove all ending \n and br at end
 *
 *	@param	string	$stringtodecode		String to decode
 *	@return	string						String decoded
 */
function dol_htmlcleanlastbr($stringtodecode)
{
	$ret = preg_replace('/(<br>|<br(\s[\sa-zA-Z_="]*)?\/?>|'."\n".'|'."\r".')+$/i', "", $stringtodecode);
	return $ret;
}

/**
 * Replace html_entity_decode functions to manage errors
 *
 * @param   string	$a					Operand a
 * @param   string	$b					Operand b (ENT_QUOTES=convert simple and double quotes)
 * @param   string	$c					Operand c
 * @param	string	$keepsomeentities	Entities but &amp;, <, >, " are not converted.
 * @return  string						String decoded
 */
function dol_html_entity_decode($a, $b, $c = 'UTF-8', $keepsomeentities = 0)
{
	$newstring = $a;
	if ($keepsomeentities) $newstring = strtr($newstring, array('&amp;'=>'__andamp__', '&lt;'=>'__andlt__', '&gt;'=>'__andgt__', '"'=>'__dquot__'));
	$newstring = html_entity_decode($newstring, $b, $c);
	if ($keepsomeentities) $newstring = strtr($newstring, array('__andamp__'=>'&amp;', '__andlt__'=>'&lt;', '__andgt__'=>'&gt;', '__dquot__'=>'"'));
	return $newstring;
}

/**
 * Replace htmlentities functions.
 * Goal of this function is to be sure to have default values of htmlentities that match what we need.
 *
 * @param   string  $string         The input string to encode
 * @param   int     $flags          Flags (see PHP doc above)
 * @param   string  $encoding       Encoding page code
 * @param   bool    $double_encode  When double_encode is turned off, PHP will not encode existing html entities
 * @return  string  $ret            Encoded string
 */
function dol_htmlentities($string, $flags = null, $encoding = 'UTF-8', $double_encode = false)
{
	return htmlentities($string, $flags, $encoding, $double_encode);
}

/**
 *	Check if a string is a correct iso string
 *	If not, it will we considered not HTML encoded even if it is by FPDF.
 *	Example, if string contains euro symbol that has ascii code 128
 *
 *	@param	string		$s      	String to check
 *  @param	string		$clean		Clean if it is not an ISO. Warning, if file is utf8, you will get a bad formated file.
 *	@return	int|string  	   		0 if bad iso, 1 if good iso, Or the clean string if $clean is 1
 */
function dol_string_is_good_iso($s, $clean = 0)
{
	$len = dol_strlen($s);
	$out = '';
	$ok = 1;
	for ($scursor = 0; $scursor < $len; $scursor++)
	{
		$ordchar = ord($s[$scursor]);
		//print $scursor.'-'.$ordchar.'<br>';
		if ($ordchar < 32 && $ordchar != 13 && $ordchar != 10) { $ok = 0; break; }
		elseif ($ordchar > 126 && $ordchar < 160) { $ok = 0; break; }
		elseif ($clean) {
			$out .= $s[$scursor];
		}
	}
	if ($clean) return $out;
	return $ok;
}

/**
 *	Return nb of lines of a clear text
 *
 *	@param	string	$s			String to check
 * 	@param	int     $maxchar	Not yet used
 *	@return	int					Number of lines
 *  @see	dol_nboflines_bis(), dolGetFirstLineOfText()
 */
function dol_nboflines($s, $maxchar = 0)
{
	if ($s == '') return 0;
	$arraystring = explode("\n", $s);
	$nb = count($arraystring);

	return $nb;
}


/**
 *	Return nb of lines of a formated text with \n and <br> (WARNING: string must not have mixed \n and br separators)
 *
 *	@param	string	$text      		Text
 *	@param	int		$maxlinesize  	Largeur de ligne en caracteres (ou 0 si pas de limite - defaut)
 * 	@param	string	$charset		Give the charset used to encode the $text variable in memory.
 *	@return int						Number of lines
 *	@see	dol_nboflines(), dolGetFirstLineOfText()
 */
function dol_nboflines_bis($text, $maxlinesize = 0, $charset = 'UTF-8')
{
	$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
	if (dol_textishtml($text)) $repTable = array("\t" => " ", "\n" => " ", "\r" => " ", "\0" => " ", "\x0B" => " ");

	$text = strtr($text, $repTable);
	if ($charset == 'UTF-8') { $pattern = '/(<br[^>]*>)/Uu'; }	// /U is to have UNGREEDY regex to limit to one html tag. /u is for UTF8 support
	else $pattern = '/(<br[^>]*>)/U'; // /U is to have UNGREEDY regex to limit to one html tag.
	$a = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

	$nblines = (int) floor((count($a) + 1) / 2);
	// count possible auto line breaks
	if ($maxlinesize)
	{
		foreach ($a as $line)
		{
			if (dol_strlen($line) > $maxlinesize)
			{
				//$line_dec = html_entity_decode(strip_tags($line));
				$line_dec = html_entity_decode($line);
				if (dol_strlen($line_dec) > $maxlinesize)
				{
					$line_dec = wordwrap($line_dec, $maxlinesize, '\n', true);
					$nblines += substr_count($line_dec, '\n');
				}
			}
		}
	}

	unset($a);
	return $nblines;
}

/**
 *	Return if a text is a html content
 *
 *	@param	string	$msg		Content to check
 *	@param	int		$option		0=Full detection, 1=Fast check
 *	@return	boolean				true/false
 *	@see	dol_concatdesc()
 */
function dol_textishtml($msg, $option = 0)
{
	if ($option == 1)
	{
		if (preg_match('/<html/i', $msg))				return true;
		elseif (preg_match('/<body/i', $msg))			return true;
		elseif (preg_match('/<br/i', $msg))				return true;
		return false;
	}
	else
	{
		if (preg_match('/<html/i', $msg))				return true;
		elseif (preg_match('/<body/i', $msg))			return true;
		elseif (preg_match('/<(b|em|i|u)>/i', $msg))		return true;
		elseif (preg_match('/<br\/>/i', $msg))	  return true;
		elseif (preg_match('/<(br|div|font|li|p|span|strong|table)>/i', $msg)) 	  return true;
		elseif (preg_match('/<(br|div|font|li|p|span|strong|table)\s+[^<>\/]*>/i', $msg)) return true;
		elseif (preg_match('/<(br|div|font|li|p|span|strong|table)\s+[^<>\/]*\/>/i', $msg)) return true;
		elseif (preg_match('/<img\s+[^<>]*src[^<>]*>/i', $msg)) return true; // must accept <img src="http://example.com/aaa.png" />
		elseif (preg_match('/<a\s+[^<>]*href[^<>]*>/i', $msg)) return true; // must accept <a href="http://example.com/aaa.png" />
		elseif (preg_match('/<h[0-9]>/i', $msg))			return true;
		elseif (preg_match('/&[A-Z0-9]{1,6};/i', $msg))	return true; // Html entities names (http://www.w3schools.com/tags/ref_entities.asp)
		elseif (preg_match('/&#[0-9]{2,3};/i', $msg))	return true; // Html entities numbers (http://www.w3schools.com/tags/ref_entities.asp)

		return false;
	}
}

/**
 *  Concat 2 descriptions with a new line between them (second operand after first one with appropriate new line separator)
 *  text1 html + text2 html => text1 + '<br>' + text2
 *  text1 html + text2 txt  => text1 + '<br>' + dol_nl2br(text2)
 *  text1 txt  + text2 html => dol_nl2br(text1) + '<br>' + text2
 *  text1 txt  + text2 txt  => text1 + '\n' + text2
 *
 *  @param  string  $text1          Text 1
 *  @param  string  $text2          Text 2
 *  @param  bool    $forxml         false=Use <br>instead of \n if html content detected, true=Use <br /> instead of \n if html content detected
 *  @param  bool    $invert         invert order of description lines (we often use config MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION in this parameter)
 *  @return string                  Text 1 + new line + Text2
 *  @see    dol_textishtml()
 */
function dol_concatdesc($text1, $text2, $forxml = false, $invert = false)
{
    if (!empty($invert))
    {
            $tmp = $text1;
            $text1 = $text2;
            $text2 = $tmp;
    }

    $ret = '';
    $ret .= (!dol_textishtml($text1) && dol_textishtml($text2)) ?dol_nl2br($text1, 0, $forxml) : $text1;
    $ret .= (!empty($text1) && !empty($text2)) ? ((dol_textishtml($text1) || dol_textishtml($text2)) ? ($forxml ? "<br \>\n" : "<br>\n") : "\n") : "";
    $ret .= (dol_textishtml($text1) && !dol_textishtml($text2)) ?dol_nl2br($text2, 0, $forxml) : $text2;
    return $ret;
}



/**
 * Return array of possible common substitutions. This includes several families like: 'system', 'mycompany', 'object', 'objectamount', 'date', 'user'
 *
 * @param	Translate	$outputlangs	Output language
 * @param   int         $onlykey        1=Do not calculate some heavy values of keys (performance enhancement when we need only the keys), 2=Values are trunc and html sanitized (to use for help tooltip)
 * @param   array       $exclude        Array of family keys we want to exclude. For example array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...)
 * @param   Object      $object         Object for keys on object
 * @return	array						Array of substitutions
 * @see setSubstitFromObject()
 */
function getCommonSubstitutionArray($outputlangs, $onlykey = 0, $exclude = null, $object = null)
{
	global $db, $conf, $mysoc, $user, $extrafields;

	$substitutionarray = array();

	if (empty($exclude) || !in_array('user', $exclude))
	{
		// Add SIGNATURE into substitutionarray first, so, when we will make the substitution,
		// this will include signature content first and then replace var found into content of signature
		$signature = $user->signature;
        $substitutionarray = array_merge($substitutionarray, array(
		'__USER_SIGNATURE__' => (string) (($signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? ($onlykey == 2 ? dol_trunc(dol_string_nohtmltag($signature), 30) : $signature) : '')
		)
			);
		// For backward compatibility
		if ($onlykey != 2)
		{
			$substitutionarray['__SIGNATURE__'] = (string) (($signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? ($onlykey == 2 ? dol_trunc(dol_string_nohtmltag($signature), 30) : $signature) : '');
		}

        $substitutionarray = array_merge($substitutionarray, array(
		'__USER_ID__' => (string) $user->id,
		'__USER_LOGIN__' => (string) $user->login,
        '__USER_EMAIL__' => (string) $user->email,
        '__USER_LASTNAME__' => (string) $user->lastname,
		'__USER_FIRSTNAME__' => (string) $user->firstname,
		'__USER_FULLNAME__' => (string) $user->getFullName($outputlangs),
		'__USER_SUPERVISOR_ID__' => (string) ($user->fk_user ? $user->fk_user : '0'),
		'__USER_REMOTE_IP__' => (string) getUserRemoteIP()
		)
			);
	}
	if ((empty($exclude) || !in_array('mycompany', $exclude)) && is_object($mysoc))
	{
        $substitutionarray = array_merge($substitutionarray, array(
			'__MYCOMPANY_NAME__'    => $mysoc->name,
			'__MYCOMPANY_EMAIL__'   => $mysoc->email,
			'__MYCOMPANY_PROFID1__' => $mysoc->idprof1,
			'__MYCOMPANY_PROFID2__' => $mysoc->idprof2,
			'__MYCOMPANY_PROFID3__' => $mysoc->idprof3,
			'__MYCOMPANY_PROFID4__' => $mysoc->idprof4,
			'__MYCOMPANY_PROFID5__' => $mysoc->idprof5,
			'__MYCOMPANY_PROFID6__' => $mysoc->idprof6,
			'__MYCOMPANY_CAPITAL__' => $mysoc->capital,
			'__MYCOMPANY_FULLADDRESS__' => $mysoc->getFullAddress(1, ', '),
			'__MYCOMPANY_ADDRESS__' => $mysoc->address,
			'__MYCOMPANY_ZIP__'     => $mysoc->zip,
			'__MYCOMPANY_TOWN__'    => $mysoc->town,
			'__MYCOMPANY_COUNTRY__'    => $mysoc->country,
			'__MYCOMPANY_COUNTRY_ID__' => $mysoc->country_id,
        	'__MYCOMPANY_COUNTRY_CODE__' => $mysoc->country_code,
        	'__MYCOMPANY_CURRENCY_CODE__' => $conf->currency
		));
	}

	if (($onlykey || is_object($object)) && (empty($exclude) || !in_array('object', $exclude)))
	{
		if ($onlykey)
		{
			$substitutionarray['__ID__'] = '__ID__';
			$substitutionarray['__REF__'] = '__REF__';
			$substitutionarray['__REF_CLIENT__'] = '__REF_CLIENT__';
			$substitutionarray['__REF_SUPPLIER__'] = '__REF_SUPPLIER__';
			$substitutionarray['__NOTE_PUBLIC__'] = '__NOTE_PUBLIC__';
			$substitutionarray['__NOTE_PRIVATE__'] = '__NOTE_PRIVATE__';
			$substitutionarray['__EXTRAFIELD_XXX__'] = '__EXTRAFIELD_XXX__';

			if (!empty($conf->societe->enabled))
			{
				$substitutionarray['__THIRDPARTY_ID__'] = '__THIRDPARTY_ID__';
				$substitutionarray['__THIRDPARTY_NAME__'] = '__THIRDPARTY_NAME__';
				$substitutionarray['__THIRDPARTY_NAME_ALIAS__'] = '__THIRDPARTY_NAME_ALIAS__';
				$substitutionarray['__THIRDPARTY_CODE_CLIENT__'] = '__THIRDPARTY_CODE_CLIENT__';
				$substitutionarray['__THIRDPARTY_CODE_FOURNISSEUR__'] = '__THIRDPARTY_CODE_FOURNISSEUR__';
				$substitutionarray['__THIRDPARTY_EMAIL__'] = '__THIRDPARTY_EMAIL__';
				$substitutionarray['__THIRDPARTY_PHONE__'] = '__THIRDPARTY_PHONE__';
				$substitutionarray['__THIRDPARTY_FAX__'] = '__THIRDPARTY_FAX__';
				$substitutionarray['__THIRDPARTY_ADDRESS__'] = '__THIRDPARTY_ADDRESS__';
				$substitutionarray['__THIRDPARTY_ZIP__'] = '__THIRDPARTY_ZIP__';
				$substitutionarray['__THIRDPARTY_TOWN__'] = '__THIRDPARTY_TOWN__';
				$substitutionarray['__THIRDPARTY_IDPROF1__'] = '__THIRDPARTY_IDPROF1__';
				$substitutionarray['__THIRDPARTY_IDPROF2__'] = '__THIRDPARTY_IDPROF2__';
				$substitutionarray['__THIRDPARTY_IDPROF3__'] = '__THIRDPARTY_IDPROF3__';
				$substitutionarray['__THIRDPARTY_IDPROF4__'] = '__THIRDPARTY_IDPROF4__';
				$substitutionarray['__THIRDPARTY_IDPROF5__'] = '__THIRDPARTY_IDPROF5__';
				$substitutionarray['__THIRDPARTY_IDPROF6__'] = '__THIRDPARTY_IDPROF6__';
				$substitutionarray['__THIRDPARTY_TVAINTRA__'] = '__THIRDPARTY_TVAINTRA__';
				$substitutionarray['__THIRDPARTY_NOTE_PUBLIC__'] = '__THIRDPARTY_NOTE_PUBLIC__';
				$substitutionarray['__THIRDPARTY_NOTE_PRIVATE__'] = '__THIRDPARTY_NOTE_PRIVATE__';
			}
			if (!empty($conf->adherent->enabled))
			{
				$substitutionarray['__MEMBER_ID__'] = '__MEMBER_ID__';
				$substitutionarray['__MEMBER_CIVILITY__'] = '__MEMBER_CIVILITY__';
				$substitutionarray['__MEMBER_FIRSTNAME__'] = '__MEMBER_FIRSTNAME__';
				$substitutionarray['__MEMBER_LASTNAME__'] = '__MEMBER_LASTNAME__';
				/*$substitutionarray['__MEMBER_NOTE_PUBLIC__'] = '__MEMBER_NOTE_PUBLIC__';
				$substitutionarray['__MEMBER_NOTE_PRIVATE__'] = '__MEMBER_NOTE_PRIVATE__';*/
			}
			if (!empty($conf->projet->enabled))
			{
				$substitutionarray['__PROJECT_ID__'] = '__PROJECT_ID__';
				$substitutionarray['__PROJECT_REF__'] = '__PROJECT_REF__';
				$substitutionarray['__PROJECT_NAME__'] = '__PROJECT_NAME__';
				/*$substitutionarray['__PROJECT_NOTE_PUBLIC__'] = '__PROJECT_NOTE_PUBLIC__';
				$substitutionarray['__PROJECT_NOTE_PRIVATE__'] = '__PROJECT_NOTE_PRIVATE__';*/
			}
			if (!empty($conf->contrat->enabled))
			{
				$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATE__'] = 'Highest date planned for a service start';
				$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATETIME__'] = 'Highest date and hour planned for service start';
				$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATE__'] = 'Lowest data for planned expiration of service';
				$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATETIME__'] = 'Lowest date and hour for planned expiration of service';
			}
			$substitutionarray['__ONLINE_PAYMENT_URL__'] = 'UrlToPayOnlineIfApplicable';
			$substitutionarray['__ONLINE_PAYMENT_TEXT_AND_URL__'] = 'TextAndUrlToPayOnlineIfApplicable';
			$substitutionarray['__SECUREKEYPAYMENT__'] = 'Security key (if key is not unique per record)';
			$substitutionarray['__SECUREKEYPAYMENT_MEMBER__'] = 'Security key for payment on a member subscription (one key per member)';
			$substitutionarray['__SECUREKEYPAYMENT_ORDER__'] = 'Security key for payment on an order';
			$substitutionarray['__SECUREKEYPAYMENT_INVOICE__'] = 'Security key for payment on an invoice';
			$substitutionarray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = 'Security key for payment on a a service';

			$substitutionarray['__DIRECTDOWNLOAD_URL_PROPOSAL__'] = 'Direct download url of a proposal';
			$substitutionarray['__DIRECTDOWNLOAD_URL_ORDER__'] = 'Direct download url of an order';
			$substitutionarray['__DIRECTDOWNLOAD_URL_INVOICE__'] = 'Direct download url of an invoice';

			if (!empty($conf->expedition->enabled))
			{
			    $substitutionarray['__SHIPPINGTRACKNUM__'] = 'Shipping tacking number';
				$substitutionarray['__SHIPPINGTRACKNUMURL__'] = 'Shipping tracking url';
			}
		}
		else
		{
			$substitutionarray['__ID__'] = $object->id;
			$substitutionarray['__REF__'] = $object->ref;
			$substitutionarray['__REF_CLIENT__'] = (isset($object->ref_client) ? $object->ref_client : (isset($object->ref_customer) ? $object->ref_customer : null));
			$substitutionarray['__REF_SUPPLIER__'] = (isset($object->ref_supplier) ? $object->ref_supplier : null);
			$substitutionarray['__NOTE_PUBLIC__'] = (isset($object->note_public) ? $object->note_public : null);
			$substitutionarray['__NOTE_PRIVATE__'] = (isset($object->note_private) ? $object->note_private : null);

			$substitutionarray['__DATE_DELIVERY__'] = (isset($object->date_livraison) ? dol_print_date($object->date_livraison, 'day', 0, $outputlangs) : '');

			// For backward compatibility
			$substitutionarray['__REFCLIENT__'] = (isset($object->ref_client) ? $object->ref_client : (isset($object->ref_customer) ? $object->ref_customer : null));
			$substitutionarray['__REFSUPPLIER__'] = (isset($object->ref_supplier) ? $object->ref_supplier : null);
			$substitutionarray['__REFCLIENT__'] = (isset($object->ref_client) ? $object->ref_client : (isset($object->ref_customer) ? $object->ref_customer : null));
			$substitutionarray['__REFSUPPLIER__'] = (isset($object->ref_supplier) ? $object->ref_supplier : null);
			$substitutionarray['__SUPPLIER_ORDER_DATE_DELIVERY__'] = (isset($object->date_livraison) ? dol_print_date($object->date_livraison, 'day', 0, $outputlangs) : '');
			$substitutionarray['__SUPPLIER_ORDER_DELAY_DELIVERY__'] = $outputlangs->transnoentities("AvailabilityType".$object->availability_code)!=('AvailabilityType'.$object->availability_code)?$outputlangs->transnoentities("AvailabilityType".$object->availability_code):$outputlangs->convToOutputCharset(isset($object->availability)?$object->availability:'');

			$birthday = dol_print_date($object->birth, 'day');

			if ($object->id > 0)
			{
				$substitutionarray['__MEMBER_ID__'] = $object->id;
				if (method_exists($object, 'getCivilityLabel')) $substitutionarray['__MEMBER_CIVILITY__'] = $object->getCivilityLabel();
				$substitutionarray['__MEMBER_FIRSTNAME__'] = $object->firstname;
				$substitutionarray['__MEMBER_LASTNAME__'] = $object->lastname;
				if (method_exists($object, 'getFullName')) $substitutionarray['__MEMBER_FULLNAME__'] = $object->getFullName($outputlangs);
				$substitutionarray['__MEMBER_COMPANY__'] = $object->societe;
				$substitutionarray['__MEMBER_ADDRESS__'] = $object->address;
				$substitutionarray['__MEMBER_ZIP__'] = $object->zip;
				$substitutionarray['__MEMBER_TOWN__'] = $object->town;
				$substitutionarray['__MEMBER_COUNTRY__'] = $object->country;
				$substitutionarray['__MEMBER_EMAIL__'] = $object->email;
				$substitutionarray['__MEMBER_BIRTH__'] = $birthday;
				$substitutionarray['__MEMBER_PHOTO__'] = $object->photo;
				$substitutionarray['__MEMBER_LOGIN__'] = $object->login;
				$substitutionarray['__MEMBER_PASSWORD__'] = $object->pass;
				$substitutionarray['__MEMBER_PHONE__'] = $object->phone;
				$substitutionarray['__MEMBER_PHONEPRO__'] = $object->phone_perso;
				$substitutionarray['__MEMBER_PHONEMOBILE__'] = $object->phone_mobile;
				$substitutionarray['__MEMBER_FIRST_SUBSCRIPTION_DATE__']       = dol_print_date($object->first_subscription_date, 'dayrfc');
				$substitutionarray['__MEMBER_FIRST_SUBSCRIPTION_DATE_START__'] = dol_print_date($object->first_subscription_date_start, 'dayrfc');
				$substitutionarray['__MEMBER_FIRST_SUBSCRIPTION_DATE_END__']   = dol_print_date($object->first_subscription_date_end, 'dayrfc');
				$substitutionarray['__MEMBER_LAST_SUBSCRIPTION_DATE__']        = dol_print_date($object->last_subscription_date, 'dayrfc');
				$substitutionarray['__MEMBER_LAST_SUBSCRIPTION_DATE_START__']  = dol_print_date($object->last_subscription_date_start, 'dayrfc');
				$substitutionarray['__MEMBER_LAST_SUBSCRIPTION_DATE_END__']    = dol_print_date($object->last_subscription_date_end, 'dayrfc');
			}

			if (is_object($object) && $object->element == 'societe')
			{
				$substitutionarray['__THIRDPARTY_ID__'] = (is_object($object) ? $object->id : '');
				$substitutionarray['__THIRDPARTY_NAME__'] = (is_object($object) ? $object->name : '');
				$substitutionarray['__THIRDPARTY_NAME_ALIAS__'] = (is_object($object) ? $object->name_alias : '');
				$substitutionarray['__THIRDPARTY_CODE_CLIENT__'] = (is_object($object) ? $object->code_client : '');
				$substitutionarray['__THIRDPARTY_CODE_FOURNISSEUR__'] = (is_object($object) ? $object->code_fournisseur : '');
				$substitutionarray['__THIRDPARTY_EMAIL__'] = (is_object($object) ? $object->email : '');
				$substitutionarray['__THIRDPARTY_PHONE__'] = (is_object($object) ? $object->phone : '');
				$substitutionarray['__THIRDPARTY_FAX__'] = (is_object($object) ? $object->fax : '');
				$substitutionarray['__THIRDPARTY_ADDRESS__'] = (is_object($object) ? $object->address : '');
				$substitutionarray['__THIRDPARTY_ZIP__'] = (is_object($object) ? $object->zip : '');
				$substitutionarray['__THIRDPARTY_TOWN__'] = (is_object($object) ? $object->town : '');
				$substitutionarray['__THIRDPARTY_IDPROF1__'] = (is_object($object) ? $object->idprof1 : '');
				$substitutionarray['__THIRDPARTY_IDPROF2__'] = (is_object($object) ? $object->idprof2 : '');
				$substitutionarray['__THIRDPARTY_IDPROF3__'] = (is_object($object) ? $object->idprof3 : '');
				$substitutionarray['__THIRDPARTY_IDPROF4__'] = (is_object($object) ? $object->idprof4 : '');
				$substitutionarray['__THIRDPARTY_IDPROF5__'] = (is_object($object) ? $object->idprof5 : '');
				$substitutionarray['__THIRDPARTY_IDPROF6__'] = (is_object($object) ? $object->idprof6 : '');
				$substitutionarray['__THIRDPARTY_TVAINTRA__'] = (is_object($object) ? $object->tva_intra : '');
				$substitutionarray['__THIRDPARTY_NOTE_PUBLIC__'] = (is_object($object) ?dol_htmlentitiesbr($object->note_public) : '');
				$substitutionarray['__THIRDPARTY_NOTE_PRIVATE__'] = (is_object($object) ?dol_htmlentitiesbr($object->note_private) : '');
			}
			elseif (is_object($object->thirdparty) && $object->thirdparty->id > 0)
			{
				$substitutionarray['__THIRDPARTY_ID__'] = (is_object($object->thirdparty) ? $object->thirdparty->id : '');
				$substitutionarray['__THIRDPARTY_NAME__'] = (is_object($object->thirdparty) ? $object->thirdparty->name : '');
				$substitutionarray['__THIRDPARTY_NAME_ALIAS__'] = (is_object($object->thirdparty) ? $object->thirdparty->name_alias : '');
				$substitutionarray['__THIRDPARTY_CODE_CLIENT__'] = (is_object($object->thirdparty) ? $object->thirdparty->code_client : '');
				$substitutionarray['__THIRDPARTY_CODE_FOURNISSEUR__'] = (is_object($object->thirdparty) ? $object->thirdparty->code_fournisseur : '');
				$substitutionarray['__THIRDPARTY_EMAIL__'] = (is_object($object->thirdparty) ? $object->thirdparty->email : '');
				$substitutionarray['__THIRDPARTY_PHONE__'] = (is_object($object->thirdparty) ? $object->thirdparty->phone : '');
				$substitutionarray['__THIRDPARTY_FAX__'] = (is_object($object->thirdparty) ? $object->thirdparty->fax : '');
				$substitutionarray['__THIRDPARTY_ADDRESS__'] = (is_object($object->thirdparty) ? $object->thirdparty->address : '');
				$substitutionarray['__THIRDPARTY_ZIP__'] = (is_object($object->thirdparty) ? $object->thirdparty->zip : '');
				$substitutionarray['__THIRDPARTY_TOWN__'] = (is_object($object->thirdparty) ? $object->thirdparty->town : '');
				$substitutionarray['__THIRDPARTY_IDPROF1__'] = (is_object($object->thirdparty) ? $object->thirdparty->idprof1 : '');
				$substitutionarray['__THIRDPARTY_IDPROF2__'] = (is_object($object->thirdparty) ? $object->thirdparty->idprof2 : '');
				$substitutionarray['__THIRDPARTY_IDPROF3__'] = (is_object($object->thirdparty) ? $object->thirdparty->idprof3 : '');
				$substitutionarray['__THIRDPARTY_IDPROF4__'] = (is_object($object->thirdparty) ? $object->thirdparty->idprof4 : '');
				$substitutionarray['__THIRDPARTY_IDPROF5__'] = (is_object($object->thirdparty) ? $object->thirdparty->idprof5 : '');
				$substitutionarray['__THIRDPARTY_IDPROF6__'] = (is_object($object->thirdparty) ? $object->thirdparty->idprof6 : '');
				$substitutionarray['__THIRDPARTY_TVAINTRA__'] = (is_object($object->thirdparty) ? $object->thirdparty->tva_intra : '');
				$substitutionarray['__THIRDPARTY_NOTE_PUBLIC__'] = (is_object($object->thirdparty) ?dol_htmlentitiesbr($object->thirdparty->note_public) : '');
				$substitutionarray['__THIRDPARTY_NOTE_PRIVATE__'] = (is_object($object->thirdparty) ?dol_htmlentitiesbr($object->thirdparty->note_private) : '');
			}

			if (is_object($object->project) && $object->project->id > 0)
			{
				$substitutionarray['__PROJECT_ID__'] = (is_object($object->project) ? $object->project->id : '');
				$substitutionarray['__PROJECT_REF__'] = (is_object($object->project) ? $object->project->ref : '');
				$substitutionarray['__PROJECT_NAME__'] = (is_object($object->project) ? $object->project->title : '');
			}
			if (is_object($object->projet) && $object->projet->id > 0)	// Deprecated, for backward compatibility
			{
				$substitutionarray['__PROJECT_ID__'] = (is_object($object->projet) ? $object->projet->id : '');
				$substitutionarray['__PROJECT_REF__'] = (is_object($object->projet) ? $object->projet->ref : '');
				$substitutionarray['__PROJECT_NAME__'] = (is_object($object->projet) ? $object->projet->title : '');
			}

			if (is_object($object) && $object->element == 'shipping')
			{
				$substitutionarray['__SHIPPINGTRACKNUM__'] = $object->tracking_number;
				$substitutionarray['__SHIPPINGTRACKNUMURL__'] = $object->tracking_url;
			}

			if (is_object($object) && $object->element == 'contrat' && is_array($object->lines))
			{
				if ($object->id > 0)
				{
					$dateplannedstart = '';
					$datenextexpiration = '';
					foreach ($object->lines as $line)
					{
						if ($line->date_ouverture_prevue > $dateplannedstart) $dateplannedstart = $line->date_ouverture_prevue;
						if ($line->statut == 4 && $line->date_fin_prevue && (!$datenextexpiration || $line->date_fin_prevue < $datenextexpiration)) $datenextexpiration = $line->date_fin_prevue;
					}
					$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATE__'] = dol_print_date($dateplannedstart, 'dayrfc');
					$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATETIME__'] = dol_print_date($dateplannedstart, 'standard');
					$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATE__'] = dol_print_date($datenextexpiration, 'dayrfc');
					$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATETIME__'] = dol_print_date($datenextexpiration, 'standard');
				}
			}

			// Create dynamic tags for __EXTRAFIELD_FIELD__
			if ($object->table_element && $object->id > 0)
			{
				if (!is_object($extrafields)) $extrafields = new ExtraFields($db);
				$extrafields->fetch_name_optionals_label($object->table_element, true);

				if ($object->fetch_optionals() > 0)
				{
					if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
					{
						foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label) {
							$substitutionarray['__EXTRAFIELD_'.strtoupper($key).'__'] = $object->array_options['options_'.$key];
						}
					}
				}
			}

			// Complete substitution array with the url to make online payment
			$paymenturl = '';
			if (empty($substitutionarray['__REF__']))
			{
				$paymenturl = '';
			}
			else
			{
				// Set the online payment url link into __ONLINE_PAYMENT_URL__ key
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				$outputlangs->loadLangs(array('paypal', 'other'));
				$typeforonlinepayment = 'free';
				if (is_object($object) && $object->element == 'commande') $typeforonlinepayment = 'order';
				if (is_object($object) && $object->element == 'facture')  $typeforonlinepayment = 'invoice';
				if (is_object($object) && $object->element == 'member')   $typeforonlinepayment = 'member';
				$url = getOnlinePaymentUrl(0, $typeforonlinepayment, $substitutionarray['__REF__']);
				$paymenturl = $url;
			}

			if ($object->id > 0)
			{
				$substitutionarray['__ONLINE_PAYMENT_TEXT_AND_URL__'] = ($paymenturl ?str_replace('\n', "\n", $outputlangs->trans("PredefinedMailContentLink", $paymenturl)) : '');
				$substitutionarray['__ONLINE_PAYMENT_URL__'] = $paymenturl;

				if (!empty($conf->global->PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD) && is_object($object) && $object->element == 'propal')
				{
					$substitutionarray['__DIRECTDOWNLOAD_URL_PROPOSAL__'] = $object->getLastMainDocLink($object->element);
				}
				else $substitutionarray['__DIRECTDOWNLOAD_URL_PROPOSAL__'] = '';
				if (!empty($conf->global->ORDER_ALLOW_EXTERNAL_DOWNLOAD) && is_object($object) && $object->element == 'commande')
				{
					$substitutionarray['__DIRECTDOWNLOAD_URL_ORDER__'] = $object->getLastMainDocLink($object->element);
				}
				else $substitutionarray['__DIRECTDOWNLOAD_URL_ORDER__'] = '';
				if (!empty($conf->global->INVOICE_ALLOW_EXTERNAL_DOWNLOAD) && is_object($object) && $object->element == 'facture')
				{
					$substitutionarray['__DIRECTDOWNLOAD_URL_INVOICE__'] = $object->getLastMainDocLink($object->element);
				}
				else $substitutionarray['__DIRECTDOWNLOAD_URL_INVOICE__'] = '';
			}
		}
	}
	if (empty($exclude) || !in_array('objectamount', $exclude))
	{
		$substitutionarray['__DATE_YMD__']        = is_object($object) ? (isset($object->date) ? dol_print_date($object->date, 'day', 0, $outputlangs) : null) : '';
		$substitutionarray['__DATE_DUE_YMD__']    = is_object($object) ? (isset($object->date_lim_reglement) ? dol_print_date($object->date_lim_reglement, 'day', 0, $outputlangs) : null) : '';

		$substitutionarray['__AMOUNT__']          = is_object($object) ? $object->total_ttc : '';
		$substitutionarray['__AMOUNT_EXCL_TAX__'] = is_object($object) ? $object->total_ht : '';
		$substitutionarray['__AMOUNT_VAT__']      = is_object($object) ? ($object->total_vat ? $object->total_vat : $object->total_tva) : '';
		if ($onlykey != 2 || $mysoc->useLocalTax(1)) $substitutionarray['__AMOUNT_TAX2__']     = is_object($object) ? $object->total_localtax1 : '';
		if ($onlykey != 2 || $mysoc->useLocalTax(2)) $substitutionarray['__AMOUNT_TAX3__']     = is_object($object) ? $object->total_localtax2 : '';

		$substitutionarray['__AMOUNT_FORMATED__']          = is_object($object) ? ($object->total_ttc ? price($object->total_ttc, 0, $outputlangs, 0, 0, -1, $conf->currency) : null) : '';
		$substitutionarray['__AMOUNT_EXCL_TAX_FORMATED__'] = is_object($object) ? ($object->total_ht ? price($object->total_ht, 0, $outputlangs, 0, 0, -1, $conf->currency) : null) : '';
		$substitutionarray['__AMOUNT_VAT_FORMATED__']      = is_object($object) ? ($object->total_vat ? price($object->total_vat, 0, $outputlangs, 0, 0, -1, $conf->currency) : ($object->total_tva ? price($object->total_tva, 0, $outputlangs, 0, 0, -1, $conf->currency) : null)) : '';
		if ($onlykey != 2 || $mysoc->useLocalTax(1)) $substitutionarray['__AMOUNT_TAX2_FORMATED__']     = is_object($object) ? ($object->total_localtax1 ? price($object->total_localtax1, 0, $outputlangs, 0, 0, -1, $conf->currency) : null) : '';
		if ($onlykey != 2 || $mysoc->useLocalTax(2)) $substitutionarray['__AMOUNT_TAX3_FORMATED__']     = is_object($object) ? ($object->total_localtax2 ? price($object->total_localtax2, 0, $outputlangs, 0, 0, -1, $conf->currency) : null) : '';

		// TODO Add keys for foreign multicurrency

		// For backward compatibility
		if ($onlykey != 2)
		{
			$substitutionarray['__TOTAL_TTC__']    = is_object($object) ? $object->total_ttc : '';
			$substitutionarray['__TOTAL_HT__']     = is_object($object) ? $object->total_ht : '';
			$substitutionarray['__TOTAL_VAT__']    = is_object($object) ? ($object->total_vat ? $object->total_vat : $object->total_tva) : '';
		}
	}

	//var_dump($substitutionarray['__AMOUNT_FORMATED__']);
	if (empty($exclude) || !in_array('date', $exclude))
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		$tmp = dol_getdate(dol_now(), true);
		$tmp2 = dol_get_prev_day($tmp['mday'], $tmp['mon'], $tmp['year']);
		$tmp3 = dol_get_prev_month($tmp['mon'], $tmp['year']);
		$tmp4 = dol_get_next_day($tmp['mday'], $tmp['mon'], $tmp['year']);
		$tmp5 = dol_get_next_month($tmp['mon'], $tmp['year']);

        $substitutionarray = array_merge($substitutionarray, array(
			'__DAY__' => (string) $tmp['mday'],
			'__DAY_TEXT__' => $outputlangs->trans('Day'.$tmp['wday']), // Monday
			'__DAY_TEXT_SHORT__' => $outputlangs->trans($tmp['weekday'].'Min'), // Mon
			'__DAY_TEXT_MIN__' => $outputlangs->trans('Short'.$tmp['weekday']), // M
			'__MONTH__' => (string) $tmp['mon'],
			'__MONTH_TEXT__' => $outputlangs->trans('Month'.sprintf("%02d", $tmp['mon'])),
			'__MONTH_TEXT_SHORT__' => $outputlangs->trans('MonthShort'.sprintf("%02d", $tmp['mon'])),
			'__MONTH_TEXT_MIN__' => $outputlangs->trans('MonthVeryShort'.sprintf("%02d", $tmp['mon'])),
			'__YEAR__' => (string) $tmp['year'],
			'__PREVIOUS_DAY__' => (string) $tmp2['day'],
			'__PREVIOUS_MONTH__' => (string) $tmp3['month'],
			'__PREVIOUS_YEAR__' => (string) ($tmp['year'] - 1),
			'__NEXT_DAY__' => (string) $tmp4['day'],
			'__NEXT_MONTH__' => (string) $tmp5['month'],
			'__NEXT_YEAR__' => (string) ($tmp['year'] + 1),
		));
	}

	if (!empty($conf->multicompany->enabled))
	{
		$substitutionarray = array_merge($substitutionarray, array('__ENTITY_ID__' => $conf->entity));
	}
	if (empty($exclude) || !in_array('system', $exclude))
	{
		$substitutionarray['__DOL_MAIN_URL_ROOT__'] = DOL_MAIN_URL_ROOT;
		$substitutionarray['__(AnyTranslationKey)__'] = $outputlangs->trans('TranslationOfKey');
		$substitutionarray['__(AnyTranslationKey|langfile)__'] = $outputlangs->trans('TranslationOfKey').' (load also language file before)';
		$substitutionarray['__[AnyConstantKey]__'] = $outputlangs->trans('ValueOfConstantKey');
	}

	return $substitutionarray;
}

/**
 *  Make substitution into a text string, replacing keys with vals from $substitutionarray (oldval=>newval),
 *  and texts like __(TranslationKey|langfile)__ and __[ConstantKey]__ are also replaced.
 *  Example of usage:
 *  $substitutionarray = getCommonSubstitutionArray($langs, 0, null, $thirdparty);
 *  complete_substitutions_array($substitutionarray, $langs, $thirdparty);
 *  $mesg = make_substitutions($mesg, $substitutionarray, $langs);
 *
 *  @param	string		$text	      			Source string in which we must do substitution
 *  @param  array		$substitutionarray		Array with key->val to substitute. Example: array('__MYKEY__' => 'MyVal', ...)
 *  @param	Translate	$outputlangs			Output language
 * 	@return string  		    				Output string after substitutions
 *  @see	complete_substitutions_array(), getCommonSubstitutionArray()
 */
function make_substitutions($text, $substitutionarray, $outputlangs = null)
{
	global $conf, $langs;

	if (!is_array($substitutionarray)) return 'ErrorBadParameterSubstitutionArrayWhenCalling_make_substitutions';

	if (empty($outputlangs)) $outputlangs = $langs;

	// Make substitution for language keys: __(AnyTranslationKey)__ or __(AnyTranslationKey|langfile)__
	if (is_object($outputlangs))
	{
		while (preg_match('/__\(([^\)]+)\)__/', $text, $reg))
		{
			$msgishtml = 0;
			if (dol_textishtml($text, 1)) $msgishtml = 1;

			// If key is __(TranslationKey|langfile)__, then force load of langfile.lang
			$tmp = explode('|', $reg[1]);
			if (!empty($tmp[1])) $outputlangs->load($tmp[1]);

			$text = preg_replace('/__\('.preg_quote($reg[1], '/').'\)__/', $msgishtml ?dol_htmlentitiesbr($outputlangs->transnoentitiesnoconv($reg[1])) : $outputlangs->transnoentitiesnoconv($reg[1]), $text);
		}
	}

	// Make substitution for constant keys.
	// Must be after the substitution of translation, so if the text of translation contains a string __[xxx]__, it is also converted.
	while (preg_match('/__\[([^\]]+)\]__/', $text, $reg))
	{
		$msgishtml = 0;
		if (dol_textishtml($text, 1)) $msgishtml = 1;

		$keyfound = $reg[1];
		if (preg_match('/(_pass|password|secret|_key|key$)/i', $keyfound)) $newval = '*****forbidden*****';
		else $newval = empty($conf->global->$keyfound) ? '' : $conf->global->$keyfound;
		$text = preg_replace('/__\['.preg_quote($keyfound, '/').'\]__/', $msgishtml ?dol_htmlentitiesbr($newval) : $newval, $text);
	}

	// Make substitition for array $substitutionarray
	foreach ($substitutionarray as $key => $value)
	{
		if (!isset($value)) continue; // If value is null, it same than not having substitution key at all into array, we do not replace.

		if ($key == '__SIGNATURE__' && (!empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))) $value = ''; // Protection
		if ($key == '__USER_SIGNATURE__' && (!empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))) $value = ''; // Protection

		$text = str_replace("$key", "$value", $text); // We must keep the " to work when value is 123.5 for example
	}

	return $text;
}

/**
 *  Complete the $substitutionarray with more entries coming from external module that had set the "substitutions=1" into module_part array.
 *  In this case, method completesubstitutionarray provided by module is called.
 *
 *  @param  array		$substitutionarray		Array substitution old value => new value value
 *  @param  Translate	$outputlangs            Output language
 *  @param  Object		$object                 Source object
 *  @param  mixed		$parameters       		Add more parameters (useful to pass product lines)
 *  @param  string      $callfunc               What is the name of the custom function that will be called? (default: completesubstitutionarray)
 *  @return	void
 *  @see 	make_substitutions()
 */
function complete_substitutions_array(&$substitutionarray, $outputlangs, $object = null, $parameters = null, $callfunc = "completesubstitutionarray")
{
	global $conf, $user;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Add a substitution key for each extrafields, using key __EXTRA_XXX__
	// TODO Remove this. Already available into the getCommonSubstitutionArray used to build the substitution array.
	/*if (is_object($object) && is_array($object->array_options))
	{
		foreach($object->array_options as $key => $val)
		{
			$keyshort=preg_replace('/^(options|extra)_/','',$key);
			$substitutionarray['__EXTRAFIELD_'.$keyshort.'__']=$val;
			// For backward compatibiliy
			$substitutionarray['%EXTRA_'.$keyshort.'%']=$val;
		}
	}*/

	// Check if there is external substitution to do, requested by plugins
	$dirsubstitutions = array_merge(array(), (array) $conf->modules_parts['substitutions']);

	foreach ($dirsubstitutions as $reldir)
	{
		$dir = dol_buildpath($reldir, 0);

		// Check if directory exists
		if (!dol_is_dir($dir)) continue;

		$substitfiles = dol_dir_list($dir, 'files', 0, 'functions_');
		foreach ($substitfiles as $substitfile)
		{
			if (preg_match('/functions_(.*)\.lib\.php/i', $substitfile['name'], $reg))
			{
				$module = $reg[1];

				dol_syslog("Library ".$substitfile['name']." found into ".$dir);
				// Include the user's functions file
				require_once $dir.$substitfile['name'];
				// Call the user's function, and only if it is defined
				$function_name = $module."_".$callfunc;
				if (function_exists($function_name)) $function_name($substitutionarray, $outputlangs, $object, $parameters);
			}
		}
	}
}

/**
 *    Format output for start and end date
 *
 *    @param	int	$date_start    Start date
 *    @param    int	$date_end      End date
 *    @param    string		$format        Output format
 *    @param	Translate	$outputlangs   Output language
 *    @return	void
 */
function print_date_range($date_start, $date_end, $format = '', $outputlangs = '')
{
	print get_date_range($date_start, $date_end, $format, $outputlangs);
}

/**
 *    Format output for start and end date
 *
 *    @param	int			$date_start    		Start date
 *    @param    int			$date_end      		End date
 *    @param    string		$format        		Output format
 *    @param	Translate	$outputlangs   		Output language
 *    @param	integer		$withparenthesis	1=Add parenthesis, 0=non parenthesis
 *    @return	string							String
 */
function get_date_range($date_start, $date_end, $format = '', $outputlangs = '', $withparenthesis = 1)
{
	global $langs;

	$out = '';

	if (!is_object($outputlangs)) $outputlangs = $langs;

	if ($date_start && $date_end)
	{
		$out .= ($withparenthesis ? ' (' : '').$outputlangs->transnoentitiesnoconv('DateFromTo', dol_print_date($date_start, $format, false, $outputlangs), dol_print_date($date_end, $format, false, $outputlangs)).($withparenthesis ? ')' : '');
	}
	if ($date_start && !$date_end)
	{
		$out .= ($withparenthesis ? ' (' : '').$outputlangs->transnoentitiesnoconv('DateFrom', dol_print_date($date_start, $format, false, $outputlangs)).($withparenthesis ? ')' : '');
	}
	if (!$date_start && $date_end)
	{
		$out .= ($withparenthesis ? ' (' : '').$outputlangs->transnoentitiesnoconv('DateUntil', dol_print_date($date_end, $format, false, $outputlangs)).($withparenthesis ? ')' : '');
	}

	return $out;
}

/**
 * Return firstname and lastname in correct order
 *
 * @param	string	$firstname		Firstname
 * @param	string	$lastname		Lastname
 * @param	int		$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname, 2=Firstname, 3=Firstname if defined else lastname
 * @return	string					Firstname + lastname or Lastname + firstname
 */
function dolGetFirstLastname($firstname, $lastname, $nameorder = -1)
{
	global $conf;

	$ret = '';
	// If order not defined, we use the setup
	if ($nameorder < 0) $nameorder = (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION) ? 1 : 0);
	if ($nameorder && $nameorder != 2 && $nameorder != 3)
	{
		$ret .= $firstname;
		if ($firstname && $lastname) $ret .= ' ';
		$ret .= $lastname;
	}
	elseif ($nameorder == 2 || $nameorder == 3)
	{
	    $ret .= $firstname;
	    if (empty($ret) && $nameorder == 3)
	    {
	    	$ret .= $lastname;
	    }
	}
	else
	{
		$ret .= $lastname;
		if ($firstname && $lastname) $ret .= ' ';
		$ret .= $firstname;
	}
	return $ret;
}


/**
 *	Set event message in dol_events session object. Will be output by calling dol_htmloutput_events.
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
 *  Note: Prefer to use setEventMessages instead.
 *
 *	@param	mixed	$mesgs			Message string or array
 *  @param  string	$style      	Which style to use ('mesgs' by default, 'warnings', 'errors')
 *  @return	void
 *  @see	dol_htmloutput_events()
 */
function setEventMessage($mesgs, $style = 'mesgs')
{
	//dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);		This is not deprecated, it is used by setEventMessages function
	if (!is_array($mesgs))		// If mesgs is a string
	{
		if ($mesgs) $_SESSION['dol_events'][$style][] = $mesgs;
	}
	else						// If mesgs is an array
	{
		foreach ($mesgs as $mesg)
		{
			if ($mesg) $_SESSION['dol_events'][$style][] = $mesg;
		}
	}
}

/**
 *	Set event messages in dol_events session object. Will be output by calling dol_htmloutput_events.
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
 *
 *	@param	string	$mesg			Message string
 *	@param	array	$mesgs			Message array
 *  @param  string	$style      	Which style to use ('mesgs' by default, 'warnings', 'errors')
 *  @param	string	$messagekey		A key to be used to allow the feature "Never show this message again"
 *  @return	void
 *  @see	dol_htmloutput_events()
 */
function setEventMessages($mesg, $mesgs, $style = 'mesgs', $messagekey = '')
{
	if (empty($mesg) && empty($mesgs))
	{
		dol_syslog("Try to add a message in stack with empty message", LOG_WARNING);
	}
	else
	{
		if ($messagekey)
		{
			// Complete message with a js link to set a cookie "DOLHIDEMESSAGE".$messagekey;
			// TODO
			$mesg .= '';
		}
		if (empty($messagekey) || empty($_COOKIE["DOLHIDEMESSAGE".$messagekey]))
		{
			if (!in_array((string) $style, array('mesgs', 'warnings', 'errors'))) dol_print_error('', 'Bad parameter style='.$style.' for setEventMessages');
			if (empty($mesgs)) setEventMessage($mesg, $style);
			else
			{
				if (!empty($mesg) && !in_array($mesg, $mesgs)) setEventMessage($mesg, $style); // Add message string if not already into array
				setEventMessage($mesgs, $style);
			}
		}
	}
}

/**
 *	Print formated messages to output (Used to show messages on html output).
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function, so there is
 *  no need to call it explicitely.
 *
 *  @param	int		$disabledoutputofmessages	Clear all messages stored into session without diplaying them
 *  @return	void
 *  @see    									dol_htmloutput_mesg()
 */
function dol_htmloutput_events($disabledoutputofmessages = 0)
{
	// Show mesgs
	if (isset($_SESSION['dol_events']['mesgs'])) {
		if (empty($disabledoutputofmessages)) dol_htmloutput_mesg('', $_SESSION['dol_events']['mesgs']);
		unset($_SESSION['dol_events']['mesgs']);
	}

	// Show errors
	if (isset($_SESSION['dol_events']['errors'])) {
		if (empty($disabledoutputofmessages)) dol_htmloutput_mesg('', $_SESSION['dol_events']['errors'], 'error');
		unset($_SESSION['dol_events']['errors']);
	}

	// Show warnings
	if (isset($_SESSION['dol_events']['warnings'])) {
		if (empty($disabledoutputofmessages)) dol_htmloutput_mesg('', $_SESSION['dol_events']['warnings'], 'warning');
		unset($_SESSION['dol_events']['warnings']);
	}
}

/**
 *	Get formated messages to output (Used to show messages on html output).
 *  This include also the translation of the message key.
 *
 *	@param	string		$mesgstring		Message string or message key
 *	@param	string[]	$mesgarray      Array of message strings or message keys
 *  @param  string		$style          Style of message output ('ok' or 'error')
 *  @param  int			$keepembedded   Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *	@return	string						Return html output
 *
 *  @see    dol_print_error()
 *  @see    dol_htmloutput_errors()
 *  @see    setEventMessages()
 */
function get_htmloutput_mesg($mesgstring = '', $mesgarray = '', $style = 'ok', $keepembedded = 0)
{
	global $conf, $langs;

	$ret = 0; $return = '';
	$out = '';
	$divstart = $divend = '';

	// If inline message with no format, we add it.
	if ((empty($conf->use_javascript_ajax) || !empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) || $keepembedded) && !preg_match('/<div class=".*">/i', $out))
	{
		$divstart = '<div class="'.$style.' clearboth">';
		$divend = '</div>';
	}

	if ((is_array($mesgarray) && count($mesgarray)) || $mesgstring)
	{
		$langs->load("errors");
		$out .= $divstart;
		if (is_array($mesgarray) && count($mesgarray))
		{
			foreach ($mesgarray as $message)
			{
				$ret++;
				$out .= $langs->trans($message);
				if ($ret < count($mesgarray)) $out .= "<br>\n";
			}
		}
		if ($mesgstring)
		{
			$langs->load("errors");
			$ret++;
			$out .= $langs->trans($mesgstring);
		}
		$out .= $divend;
	}

	if ($out)
	{
		if (!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY) && empty($keepembedded))
		{
			$return = '<script>
					$(document).ready(function() {
						var block = '.(!empty($conf->global->MAIN_USE_JQUERY_BLOCKUI) ? "true" : "false").'
						if (block) {
							$.dolEventValid("","'.dol_escape_js($out).'");
						} else {
							/* jnotify(message, preset of message type, keepmessage) */
							$.jnotify("'.dol_escape_js($out).'",
							"'.($style == "ok" ? 3000 : $style).'",
							'.($style == "ok" ? "false" : "true").',
							{ remove: function (){} } );
						}
					});
				</script>';
		}
		else
		{
			$return = $out;
		}
	}

	return $return;
}

/**
 *  Get formated error messages to output (Used to show messages on html output).
 *
 *  @param  string	$mesgstring         Error message
 *  @param  array	$mesgarray          Error messages array
 *  @param  int		$keepembedded       Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return string                		Return html output
 *
 *  @see    dol_print_error()
 *  @see    dol_htmloutput_mesg()
 */
function get_htmloutput_errors($mesgstring = '', $mesgarray = array(), $keepembedded = 0)
{
	return get_htmloutput_mesg($mesgstring, $mesgarray, 'error', $keepembedded);
}

/**
 *	Print formated messages to output (Used to show messages on html output).
 *
 *	@param	string		$mesgstring		Message string or message key
 *	@param	string[]	$mesgarray      Array of message strings or message keys
 *	@param  string      $style          Which style to use ('ok', 'warning', 'error')
 *	@param  int         $keepembedded   Set to 1 if message must be kept embedded into its html place (this disable jnotify)
 *	@return	void
 *
 *	@see    dol_print_error()
 *	@see    dol_htmloutput_errors()
 *	@see    setEventMessages()
 */
function dol_htmloutput_mesg($mesgstring = '', $mesgarray = array(), $style = 'ok', $keepembedded = 0)
{
	if (empty($mesgstring) && (!is_array($mesgarray) || count($mesgarray) == 0)) return;

	$iserror = 0;
	$iswarning = 0;
	if (is_array($mesgarray))
	{
		foreach ($mesgarray as $val)
		{
			if ($val && preg_match('/class="error"/i', $val)) { $iserror++; break; }
			if ($val && preg_match('/class="warning"/i', $val)) { $iswarning++; break; }
		}
	}
	elseif ($mesgstring && preg_match('/class="error"/i', $mesgstring)) $iserror++;
	elseif ($mesgstring && preg_match('/class="warning"/i', $mesgstring)) $iswarning++;
	if ($style == 'error') $iserror++;
	if ($style == 'warning') $iswarning++;

	if ($iserror || $iswarning)
	{
		// Remove div from texts
		$mesgstring = preg_replace('/<\/div><div class="(error|warning)">/', '<br>', $mesgstring);
		$mesgstring = preg_replace('/<div class="(error|warning)">/', '', $mesgstring);
		$mesgstring = preg_replace('/<\/div>/', '', $mesgstring);
		// Remove div from texts array
		if (is_array($mesgarray))
		{
			$newmesgarray = array();
			foreach ($mesgarray as $val)
			{
				if (is_string($val))
				{
					$tmpmesgstring = preg_replace('/<\/div><div class="(error|warning)">/', '<br>', $val);
					$tmpmesgstring = preg_replace('/<div class="(error|warning)">/', '', $tmpmesgstring);
					$tmpmesgstring = preg_replace('/<\/div>/', '', $tmpmesgstring);
					$newmesgarray[] = $tmpmesgstring;
				}
				else
				{
					dol_syslog("Error call of dol_htmloutput_mesg with an array with a value that is not a string", LOG_WARNING);
				}
			}
			$mesgarray = $newmesgarray;
		}
		print get_htmloutput_mesg($mesgstring, $mesgarray, ($iserror ? 'error' : 'warning'), $keepembedded);
	}
	else print get_htmloutput_mesg($mesgstring, $mesgarray, 'ok', $keepembedded);
}

/**
 *  Print formated error messages to output (Used to show messages on html output).
 *
 *  @param	string	$mesgstring          Error message
 *  @param  array	$mesgarray           Error messages array
 *  @param  int		$keepembedded        Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return	void
 *
 *  @see    dol_print_error()
 *  @see    dol_htmloutput_mesg()
 */
function dol_htmloutput_errors($mesgstring = '', $mesgarray = array(), $keepembedded = 0)
{
	dol_htmloutput_mesg($mesgstring, $mesgarray, 'error', $keepembedded);
}

/**
 * 	Advanced sort array by second index function, which produces ascending (default)
 *  or descending output and uses optionally natural case insensitive sorting (which
 *  can be optionally case sensitive as well).
 *
 *  @param      array		$array      		Array to sort (array of array('key1'=>val1,'key2'=>val2,'key3'...) or array of objects)
 *  @param      string		$index				Key in array to use for sorting criteria
 *  @param      int			$order				Sort order ('asc' or 'desc')
 *  @param      int			$natsort			1=use "natural" sort (natsort), 0=use "standard" sort (asort)
 *  @param      int			$case_sensitive		1=sort is case sensitive, 0=not case sensitive
 *  @param		int			$keepindex			If 0 and index key of array to sort is a numeric, than index will be rewrote. If 1 or index key is not numeric, key for index is kept after sorting.
 *  @return     array							Sorted array
 */
function dol_sort_array(&$array, $index, $order = 'asc', $natsort = 0, $case_sensitive = 0, $keepindex = 0)
{
	// Clean parameters
	$order = strtolower($order);

	if (is_array($array))
	{
		$sizearray = count($array);
		if ($sizearray > 0)
		{
			$temp = array();
			foreach (array_keys($array) as $key)
			{
				if (is_object($array[$key]))
				{
					$temp[$key] = $array[$key]->$index;
				}
				else
				{
					$temp[$key] = $array[$key][$index];
				}
			}

            if (!$natsort) {
                ($order == 'asc') ? asort($temp) : arsort($temp);
            } else {
                ($case_sensitive) ? natsort($temp) : natcasesort($temp);
                if ($order != 'asc') $temp = array_reverse($temp, true);
            }

			$sorted = array();

			foreach (array_keys($temp) as $key)
			{
				(is_numeric($key) && empty($keepindex)) ? $sorted[] = $array[$key] : $sorted[$key] = $array[$key];
			}

			return $sorted;
		}
	}
	return $array;
}


/**
 *      Check if a string is in UTF8
 *
 *      @param	string	$str        String to check
 * 		@return	boolean				True if string is UTF8 or ISO compatible with UTF8, False if not (ISO with special char or Binary)
 */
function utf8_check($str)
{
	// We must use here a binary strlen function (so not dol_strlen)
	$strLength = dol_strlen($str);
	for ($i = 0; $i < $strLength; $i++)
	{
		if (ord($str[$i]) < 0x80) continue; // 0bbbbbbb
		elseif ((ord($str[$i]) & 0xE0) == 0xC0) $n = 1; // 110bbbbb
		elseif ((ord($str[$i]) & 0xF0) == 0xE0) $n = 2; // 1110bbbb
		elseif ((ord($str[$i]) & 0xF8) == 0xF0) $n = 3; // 11110bbb
		elseif ((ord($str[$i]) & 0xFC) == 0xF8) $n = 4; // 111110bb
		elseif ((ord($str[$i]) & 0xFE) == 0xFC) $n = 5; // 1111110b
		else return false; // Does not match any model
		for ($j = 0; $j < $n; $j++) { // n bytes matching 10bbbbbb follow ?
			if ((++$i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80))
			return false;
		}
	}
	return true;
}


/**
 *      Return a string encoded into OS filesystem encoding. This function is used to define
 * 	    value to pass to filesystem PHP functions.
 *
 *      @param	string	$str        String to encode (UTF-8)
 * 		@return	string				Encoded string (UTF-8, ISO-8859-1)
 */
function dol_osencode($str)
{
	global $conf;

	$tmp = ini_get("unicode.filesystem_encoding"); // Disponible avec PHP 6.0
	if (empty($tmp) && !empty($_SERVER["WINDIR"])) $tmp = 'iso-8859-1'; // By default for windows
	if (empty($tmp)) $tmp = 'utf-8'; // By default for other
	if (!empty($conf->global->MAIN_FILESYSTEM_ENCODING)) $tmp = $conf->global->MAIN_FILESYSTEM_ENCODING;

	if ($tmp == 'iso-8859-1') return utf8_decode($str);
	return $str;
}


/**
 *      Return an id or code from a code or id.
 *      Store also Code-Id into a cache to speed up next request on same key.
 *
 * 		@param	DoliDB	$db				Database handler
 * 		@param	string	$key			Code or Id to get Id or Code
 * 		@param	string	$tablename		Table name without prefix
 * 		@param	string	$fieldkey		Field to search the key into
 * 		@param	string	$fieldid		Field to get
 *      @param  int		$entityfilter	Filter by entity
 *      @return int						<0 if KO, Id of code if OK
 *      @see $langs->getLabelFromKey
 */
function dol_getIdFromCode($db, $key, $tablename, $fieldkey = 'code', $fieldid = 'id', $entityfilter = 0)
{
	global $cache_codes;

	// If key empty
	if ($key == '') return '';

	// Check in cache
	if (isset($cache_codes[$tablename][$key][$fieldid]))	// Can be defined to 0 or ''
	{
		return $cache_codes[$tablename][$key][$fieldid]; // Found in cache
	}

	dol_syslog('dol_getIdFromCode (value for field '.$fieldid.' from key '.$key.' not found into cache)', LOG_DEBUG);

	$sql = "SELECT ".$fieldid." as valuetoget";
	$sql .= " FROM ".MAIN_DB_PREFIX.$tablename;
	$sql .= " WHERE ".$fieldkey." = '".$db->escape($key)."'";
	if (!empty($entityfilter))
		$sql .= " AND entity IN (".getEntity($tablename).")";

	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj) $cache_codes[$tablename][$key][$fieldid] = $obj->valuetoget;
		else $cache_codes[$tablename][$key][$fieldid] = '';
		$db->free($resql);
		return $cache_codes[$tablename][$key][$fieldid];
	}
	else
	{
		return -1;
	}
}

/**
 * Verify if condition in string is ok or not
 *
 * @param 	string		$strRights		String with condition to check
 * @return 	boolean						True or False. Return True if strRights is ''
 */
function verifCond($strRights)
{
	global $user, $conf, $langs;
	global $leftmenu;
	global $rights; // To export to dol_eval function

	//print $strRights."<br>\n";
	$rights = true;
	if ($strRights != '')
	{
		$str = 'if(!('.$strRights.')) { $rights = false; }';
		dol_eval($str); // The dol_eval must contains all the global $xxx used into a condition
	}
	return $rights;
}

/**
 * Replace eval function to add more security.
 * This function is called by verifCond() or trans() and transnoentitiesnoconv().
 *
 * @param 	string	$s				String to evaluate
 * @param	int		$returnvalue	0=No return (used to execute eval($a=something)). 1=Value of eval is returned (used to eval($something)).
 * @param   int     $hideerrors     1=Hide errors
 * @return	mixed					Nothing or return of eval
 */
function dol_eval($s, $returnvalue = 0, $hideerrors = 1)
{
	// Only global variables can be changed by eval function and returned to caller
	global $db, $langs, $user, $conf, $website, $websitepage;
	global $action, $mainmenu, $leftmenu;
	global $rights;
	global $object;
	global $mysoc;

	global $obj; // To get $obj used into list when dol_eval is used for computed fields and $obj is not yet $object
	global $soc; // For backward compatibility

	//print $s."<br>\n";
	if ($returnvalue)
	{
		if ($hideerrors) return @eval('return '.$s.';');
		else return eval('return '.$s.';');
	}
	else
	{
		if ($hideerrors) @eval($s);
		else eval($s);
	}
}

/**
 * Return if var element is ok
 *
 * @param   string      $element    Variable to check
 * @return  boolean                 Return true of variable is not empty
 */
function dol_validElement($element)
{
	return (trim($element) != '');
}

/**
 * 	Return img flag of country for a language code or country code
 *
 * 	@param	string	$codelang	Language code (en_IN, fr_CA...) or Country code (IN, FR)
 *  @param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 * 	@return	string				HTML img string with flag.
 */
function picto_from_langcode($codelang, $moreatt = '')
{
	global $langs;

	if (empty($codelang)) return '';

	if ($codelang == 'auto')
	{
		return '<span class="fa fa-globe"></span>';
	}

	$langtocountryflag = array(
		'ar_AR' => '',
		'ca_ES' => 'catalonia',
		'da_DA' => 'dk',
		'fr_CA' => 'mq',
		'sv_SV' => 'se'
	);

	if (isset($langtocountryflag[$codelang])) $flagImage = $langtocountryflag[$codelang];
	else
	{
		$tmparray = explode('_', $codelang);
		$flagImage = empty($tmparray[1]) ? $tmparray[0] : $tmparray[1];
	}

	return img_picto_common($codelang, 'flags/'.strtolower($flagImage).'.png', $moreatt);
}

/**
 * Return default language from country code.
 * Return null if not found.
 *
 * @param 	string 	$countrycode	Country code like 'US', 'FR', 'CA', ...
 * @return	string					Value of locale like 'en_US', 'fr_FR', ...
 */
function getLanguageCodeFromCountryCode($countrycode)
{
	global $mysoc;

	if (empty($countrycode)) return null;

	if (strtoupper($countrycode) == 'MQ') return 'fr_CA';
	if (strtoupper($countrycode) == 'SE') return 'sv_SE'; // se_SE is Sami/Sweden, and we want in priority sv_SE for SE country
	if (strtoupper($countrycode) == 'CH')
	{
		if ($mysoc->country_code == 'FR') return 'fr_CH';
		if ($mysoc->country_code == 'DE') return 'de_CH';
	}

	// Locale list taken from:
	// http://stackoverflow.com/questions/3191664/
	// list-of-all-locales-and-their-short-codes
	$locales = array(
		'af-ZA',
		'am-ET',
		'ar-AE',
		'ar-BH',
		'ar-DZ',
		'ar-EG',
		'ar-IQ',
		'ar-JO',
		'ar-KW',
		'ar-LB',
		'ar-LY',
		'ar-MA',
		'ar-OM',
		'ar-QA',
		'ar-SA',
		'ar-SY',
		'ar-TN',
		'ar-YE',
		'as-IN',
		'ba-RU',
		'be-BY',
		'bg-BG',
		'bn-BD',
		'bn-IN',
		'bo-CN',
		'br-FR',
		'ca-ES',
		'co-FR',
		'cs-CZ',
		'cy-GB',
		'da-DK',
		'de-AT',
		'de-CH',
		'de-DE',
		'de-LI',
		'de-LU',
		'dv-MV',
		'el-GR',
		'en-AU',
		'en-BZ',
		'en-CA',
		'en-GB',
		'en-IE',
		'en-IN',
		'en-JM',
		'en-MY',
		'en-NZ',
		'en-PH',
		'en-SG',
		'en-TT',
		'en-US',
		'en-ZA',
		'en-ZW',
		'es-AR',
		'es-BO',
		'es-CL',
		'es-CO',
		'es-CR',
		'es-DO',
		'es-EC',
		'es-ES',
		'es-GT',
		'es-HN',
		'es-MX',
		'es-NI',
		'es-PA',
		'es-PE',
		'es-PR',
		'es-PY',
		'es-SV',
		'es-US',
		'es-UY',
		'es-VE',
		'et-EE',
		'eu-ES',
		'fa-IR',
		'fi-FI',
		'fo-FO',
		'fr-BE',
		'fr-CA',
		'fr-CH',
		'fr-FR',
		'fr-LU',
		'fr-MC',
		'fy-NL',
		'ga-IE',
		'gd-GB',
		'gl-ES',
		'gu-IN',
		'he-IL',
		'hi-IN',
		'hr-BA',
		'hr-HR',
		'hu-HU',
		'hy-AM',
		'id-ID',
		'ig-NG',
		'ii-CN',
		'is-IS',
		'it-CH',
		'it-IT',
		'ja-JP',
		'ka-GE',
		'kk-KZ',
		'kl-GL',
		'km-KH',
		'kn-IN',
		'ko-KR',
		'ky-KG',
		'lb-LU',
		'lo-LA',
		'lt-LT',
		'lv-LV',
		'mi-NZ',
		'mk-MK',
		'ml-IN',
		'mn-MN',
		'mr-IN',
		'ms-BN',
		'ms-MY',
		'mt-MT',
		'nb-NO',
		'ne-NP',
		'nl-BE',
		'nl-NL',
		'nn-NO',
		'oc-FR',
		'or-IN',
		'pa-IN',
		'pl-PL',
		'ps-AF',
		'pt-BR',
		'pt-PT',
		'rm-CH',
		'ro-RO',
		'ru-RU',
		'rw-RW',
		'sa-IN',
		'se-FI',
		'se-NO',
		'se-SE',
		'si-LK',
		'sk-SK',
		'sl-SI',
		'sq-AL',
		'sv-FI',
		'sv-SE',
		'sw-KE',
		'ta-IN',
		'te-IN',
		'th-TH',
		'tk-TM',
		'tn-ZA',
		'tr-TR',
		'tt-RU',
		'ug-CN',
		'uk-UA',
		'ur-PK',
		'vi-VN',
		'wo-SN',
		'xh-ZA',
		'yo-NG',
		'zh-CN',
		'zh-HK',
		'zh-MO',
		'zh-SG',
		'zh-TW',
		'zu-ZA',
	);

	$buildprimarykeytotest = strtolower($countrycode).'-'.strtoupper($countrycode);
	if (in_array($buildprimarykeytotest, $locales)) return strtolower($countrycode).'_'.strtoupper($countrycode);

	if (function_exists('locale_get_primary_language') && function_exists('locale_get_region'))    // Need extension php-intl
	{
	    foreach ($locales as $locale)
    	{
    		$locale_language = locale_get_primary_language($locale);
    		$locale_region = locale_get_region($locale);
    		if (strtoupper($countrycode) == $locale_region)
    		{
    			//var_dump($locale.'-'.$locale_language.'-'.$locale_region);
    			return strtolower($locale_language).'_'.strtoupper($locale_region);
    		}
    	}
	}
	else
	{
        dol_syslog("Warning Exention php-intl is not available", LOG_WARNING);
	}

	return null;
}

/**
 *  Complete or removed entries into a head array (used to build tabs).
 *  For example, with value added by external modules. Such values are declared into $conf->modules_parts['tab'].
 *  Or by change using hook completeTabsHead
 *
 *  @param	Conf			$conf           Object conf
 *  @param  Translate		$langs          Object langs
 *  @param  object|null		$object         Object object
 *  @param  array			$head          	Object head
 *  @param  int				$h				New position to fill
 *  @param  string			$type           Value for object where objectvalue can be
 *                              			'thirdparty'       to add a tab in third party view
 *		                        	      	'intervention'     to add a tab in intervention view
 *     		                    	     	'supplier_order'   to add a tab in supplier order view
 *          		            	        'supplier_invoice' to add a tab in supplier invoice view
 *                  		    	        'invoice'          to add a tab in customer invoice view
 *                          			    'order'            to add a tab in customer order view
 *                          				'contract'		   to add a tabl in contract view
 *                      			        'product'          to add a tab in product view
 *                              			'propal'           to add a tab in propal view
 *                              			'user'             to add a tab in user view
 *                              			'group'            to add a tab in group view
 * 		        	               	     	'member'           to add a tab in fundation member view
 *      		                        	'categories_x'	   to add a tab in category view ('x': type of category (0=product, 1=supplier, 2=customer, 3=member)
 *      									'ecm'			   to add a tab for another ecm view
 *                                          'stock'            to add a tab for warehouse view
 *  @param  string		$mode  	        	'add' to complete head, 'remove' to remove entries
 *	@return	void
 */
function complete_head_from_modules($conf, $langs, $object, &$head, &$h, $type, $mode = 'add')
{
	global $hookmanager;

	if (isset($conf->modules_parts['tabs'][$type]) && is_array($conf->modules_parts['tabs'][$type]))
	{
		foreach ($conf->modules_parts['tabs'][$type] as $value)
		{
			$values = explode(':', $value);

			if ($mode == 'add' && !preg_match('/^\-/', $values[1]))
			{
				if (count($values) == 6)       // new declaration with permissions:  $value='objecttype:+tabname1:Title1:langfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__'
				{
					if ($values[0] != $type) continue;

					if (verifCond($values[4]))
					{
						if ($values[3]) $langs->load($values[3]);
						if (preg_match('/SUBSTITUTION_([^_]+)/i', $values[2], $reg))
						{
							$substitutionarray = array();
							complete_substitutions_array($substitutionarray, $langs, $object, array('needforkey'=>$values[2]));
							$label = make_substitutions($reg[1], $substitutionarray);
						}
						else $label = $langs->trans($values[2]);

						$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i', ((is_object($object) && !empty($object->id)) ? $object->id : ''), $values[5]), 1);
						$head[$h][1] = $label;
						$head[$h][2] = str_replace('+', '', $values[1]);
						$h++;
					}
				}
				elseif (count($values) == 5)       // deprecated
				{
					dol_syslog('Passing 5 values in tabs module_parts is deprecated. Please update to 6 with permissions.', LOG_WARNING);

					if ($values[0] != $type) continue;
					if ($values[3]) $langs->load($values[3]);
					if (preg_match('/SUBSTITUTION_([^_]+)/i', $values[2], $reg))
					{
						$substitutionarray = array();
						complete_substitutions_array($substitutionarray, $langs, $object, array('needforkey'=>$values[2]));
						$label = make_substitutions($reg[1], $substitutionarray);
					}
					else $label = $langs->trans($values[2]);

					$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i', ((is_object($object) && !empty($object->id)) ? $object->id : ''), $values[4]), 1);
					$head[$h][1] = $label;
					$head[$h][2] = str_replace('+', '', $values[1]);
					$h++;
				}
			}
			elseif ($mode == 'remove' && preg_match('/^\-/', $values[1]))
			{
				if ($values[0] != $type) continue;
				$tabname = str_replace('-', '', $values[1]);
				foreach ($head as $key => $val)
				{
					$condition = (!empty($values[3]) ? verifCond($values[3]) : 1);
					//var_dump($key.' - '.$tabname.' - '.$head[$key][2].' - '.$values[3].' - '.$condition);
					if ($head[$key][2] == $tabname && $condition)
					{
						unset($head[$key]);
						break;
					}
				}
			}
		}
	}

	// No need to make a return $head. Var is modified as a reference
	if (!empty($hookmanager))
	{
		$parameters = array('object' => $object, 'mode' => $mode, 'head' => $head);
		$reshook = $hookmanager->executeHooks('completeTabsHead', $parameters);
		if ($reshook > 0)
		{
			$head = $hookmanager->resArray;
            $h = count($head);
		}
	}
}

/**
 * Print common footer :
 * 		conf->global->MAIN_HTML_FOOTER
 *      js for switch of menu hider
 * 		js for conf->global->MAIN_GOOGLE_AN_ID
 * 		js for conf->global->MAIN_SHOW_TUNING_INFO or $_SERVER["MAIN_SHOW_TUNING_INFO"]
 * 		js for conf->logbuffer
 *
 * @param	string	$zone	'private' (for private pages) or 'public' (for public pages)
 * @return	void
 */
function printCommonFooter($zone = 'private')
{
	global $conf, $hookmanager, $user, $debugbar;
	global $action;
	global $micro_start_time;

	if ($zone == 'private') print "\n".'<!-- Common footer for private page -->'."\n";
	else print "\n".'<!-- Common footer for public page -->'."\n";

	// A div to store page_y POST parameter so we can read it using javascript
	print "\n<!-- A div to store page_y POST parameter -->\n";
	print '<div id="page_y" style="display: none;">'.$_POST['page_y'].'</div>'."\n";

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printCommonFooter', $parameters); // Note that $action and $object may have been modified by some hooks
	if (empty($reshook))
	{
		if (!empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER."\n";

		print "\n";
		if (!empty($conf->use_javascript_ajax))
		{
			print '<script>'."\n";
			print 'jQuery(document).ready(function() {'."\n";

			if ($zone == 'private' && empty($conf->dol_use_jmobile))
			{
				print "\n";
				print '/* JS CODE TO ENABLE to manage handler to switch left menu page (menuhider) */'."\n";
				print 'jQuery("li.menuhider").click(function(event) {';
				print '  if (!$( "body" ).hasClass( "sidebar-collapse" )){ event.preventDefault(); }'."\n";
				print '  console.log("We click on .menuhider");'."\n";
				print '  $("body").toggleClass("sidebar-collapse")'."\n";
				print '});'."\n";
			}

			// Management of focus and mandatory for fields
			if ($action == 'create' || $action == 'edit' || (empty($action) && (preg_match('/new\.php/', $_SERVER["PHP_SELF"]))))
			{
				print '/* JS CODE TO ENABLE to manage focus and mandatory form fields */'."\n";
				$relativepathstring = $_SERVER["PHP_SELF"];
				// Clean $relativepathstring
				if (constant('DOL_URL_ROOT')) $relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
				$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
				$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
				//$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
				if (!empty($user->default_values[$relativepathstring]['focus']))
				{
					foreach ($user->default_values[$relativepathstring]['focus'] as $defkey => $defval)
					{
						$qualified = 0;
						if ($defkey != '_noquery_')
						{
							$tmpqueryarraytohave = explode('&', $defkey);
							$foundintru = 0;
							foreach ($tmpqueryarraytohave as $tmpquerytohave)
							{
								$tmpquerytohaveparam = explode('=', $tmpquerytohave);
								//print "console.log('".$tmpquerytohaveparam[0]." ".$tmpquerytohaveparam[1]." ".GETPOST($tmpquerytohaveparam[0])."');";
								if (!GETPOSTISSET($tmpquerytohaveparam[0]) || ($tmpquerytohaveparam[1] != GETPOST($tmpquerytohaveparam[0]))) $foundintru = 1;
							}
							if (!$foundintru) $qualified = 1;
							//var_dump($defkey.'-'.$qualified);
						}
						else $qualified = 1;

						if ($qualified)
						{
							foreach ($defval as $paramkey => $paramval)
							{
								// Set focus on field
								print 'jQuery("input[name=\''.$paramkey.'\']").focus();'."\n";
								print 'jQuery("textarea[name=\''.$paramkey.'\']").focus();'."\n";
								print 'jQuery("select[name=\''.$paramkey.'\']").focus();'."\n"; // Not really usefull, but we keep it in case of.
							}
						}
					}
				}
				if (!empty($user->default_values[$relativepathstring]['mandatory']))
				{
					foreach ($user->default_values[$relativepathstring]['mandatory'] as $defkey => $defval)
					{
						$qualified = 0;
						if ($defkey != '_noquery_')
						{
							$tmpqueryarraytohave = explode('&', $defkey);
							$foundintru = 0;
							foreach ($tmpqueryarraytohave as $tmpquerytohave)
							{
								$tmpquerytohaveparam = explode('=', $tmpquerytohave);
								//print "console.log('".$tmpquerytohaveparam[0]." ".$tmpquerytohaveparam[1]." ".GETPOST($tmpquerytohaveparam[0])."');";
								if (!GETPOSTISSET($tmpquerytohaveparam[0]) || ($tmpquerytohaveparam[1] != GETPOST($tmpquerytohaveparam[0]))) $foundintru = 1;
							}
							if (!$foundintru) $qualified = 1;
							//var_dump($defkey.'-'.$qualified);
						}
						else $qualified = 1;

						if ($qualified)
						{
							foreach ($defval as $paramkey => $paramval)
							{
								// Add property 'required' on input
								print 'jQuery("input[name=\''.$paramkey.'\']").prop(\'required\',true);'."\n";
								print 'jQuery("textarea[name=\''.$paramkey.'\']").prop(\'required\',true);'."\n";
								print 'jQuery("select[name=\''.$paramkey.'\']").prop(\'required\',true);'."\n"; // required on a select works only if key is "", this does not happen in Dolibarr
							}
						}
					}
				}
			}

			print '});'."\n";

			// Google Analytics
			// TODO Add a hook here
			if (!empty($conf->google->enabled) && !empty($conf->global->MAIN_GOOGLE_AN_ID))
			{
				print "\n";
				print "/* JS CODE TO ENABLE for google analtics tag */\n";
				print '  var _gaq = _gaq || [];'."\n";
				print '  _gaq.push([\'_setAccount\', \''.$conf->global->MAIN_GOOGLE_AN_ID.'\']);'."\n";
				print '  _gaq.push([\'_trackPageview\']);'."\n";
				print ''."\n";
				print '  (function() {'."\n";
				print '    var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;'."\n";
				print '    ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';'."\n";
				print '    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);'."\n";
				print '  })();'."\n";
			}

			// End of tuning
			if (!empty($_SERVER['MAIN_SHOW_TUNING_INFO']) || !empty($conf->global->MAIN_SHOW_TUNING_INFO))
			{
				print "\n";
				print "/* JS CODE TO ENABLE to add memory info */\n";
				print 'window.console && console.log("';
				if (!empty($conf->global->MEMCACHED_SERVER)) print 'MEMCACHED_SERVER='.$conf->global->MEMCACHED_SERVER.' - ';
				print 'MAIN_OPTIMIZE_SPEED='.(isset($conf->global->MAIN_OPTIMIZE_SPEED) ? $conf->global->MAIN_OPTIMIZE_SPEED : 'off');
				if (!empty($micro_start_time))   // Works only if MAIN_SHOW_TUNING_INFO is defined at $_SERVER level. Not in global variable.
				{
					$micro_end_time = microtime(true);
					print ' - Build time: '.ceil(1000 * ($micro_end_time - $micro_start_time)).' ms';
				}
				if (function_exists("memory_get_usage"))
				{
					print ' - Mem: '.memory_get_usage();
				}
				if (function_exists("xdebug_memory_usage"))
				{
					print ' - XDebug time: '.ceil(1000 * xdebug_time_index()).' ms';
					print ' - XDebug mem: '.xdebug_memory_usage();
					print ' - XDebug mem peak: '.xdebug_peak_memory_usage();
				}
				if (function_exists("zend_loader_file_encoded"))
				{
					print ' - Zend encoded file: '.(zend_loader_file_encoded() ? 'yes' : 'no');
				}
				print '");'."\n";
			}

			print "\n".'</script>'."\n";
		}

		// Add Xdebug coverage of code
		if (defined('XDEBUGCOVERAGE'))
		{
		    print_r(xdebug_get_code_coverage());
		}

		// Add DebugBar data
	    if (!empty($user->rights->debugbar->read) && is_object($debugbar))
	    {
	        $debugbar['time']->stopMeasure('pageaftermaster');
	        print '<!-- Output debugbar data -->'."\n";
		    print $debugbar->getRenderer()->render();
		}
		elseif (count($conf->logbuffer))    // If there is some logs in buffer to show
		{
			print "\n";
			print "<!-- Start of log output\n";
			//print '<div class="hidden">'."\n";
			foreach ($conf->logbuffer as $logline)
			{
				print $logline."<br>\n";
			}
			//print '</div>'."\n";
			print "End of log output -->\n";
		}
	}
}

/**
 * Split a string with 2 keys into key array.
 * For example: "A=1;B=2;C=2" is exploded into array('A'=>1,'B'=>2,'C'=>3)
 *
 * @param 	string	$string		String to explode
 * @param 	string	$delimiter	Delimiter between each couple of data
 * @param 	string	$kv			Delimiter between key and value
 * @return	array				Array of data exploded
 */
function dolExplodeIntoArray($string, $delimiter = ';', $kv = '=')
{
	if ($a = explode($delimiter, $string))
	{
		$ka = array();
		foreach ($a as $s) { // each part
			if ($s) {
				if ($pos = strpos($s, $kv)) { // key/value delimiter
					$ka[trim(substr($s, 0, $pos))] = trim(substr($s, $pos + strlen($kv)));
				} else { // key delimiter not found
					$ka[] = trim($s);
				}
			}
		}
		return $ka;
	}
	return array();
}


/**
 * Set focus onto field with selector (similar behaviour of 'autofocus' HTML5 tag)
 *
 * @param 	string	$selector	Selector ('#id' or 'input[name="ref"]') to use to find the HTML input field that must get the autofocus. You must use a CSS selector, so unique id preceding with the '#' char.
 * @return	string				HTML code to set focus
 */
function dol_set_focus($selector)
{
	print "\n".'<!-- Set focus onto a specific field -->'."\n";
	print '<script>jQuery(document).ready(function() { jQuery("'.dol_escape_js($selector).'").focus(); });</script>'."\n";
}


/**
 * Return getmypid() or random PID when function is disabled
 * Some web hosts disable this php function for security reasons
 * and sometimes we can't redeclare function
 *
 * @return	int
 */
function dol_getmypid()
{
	if (!function_exists('getmypid')) {
		return mt_rand(1, 32768);
	} else {
		return getmypid();
	}
}


/**
 * Generate natural SQL search string for a criteria (this criteria can be tested on one or several fields)
 *
 * @param   string|string[]	$fields 	String or array of strings, filled with the name of all fields in the SQL query we must check (combined with a OR). Example: array("p.field1","p.field2")
 * @param   string 			$value 		The value to look for.
 *                          		    If param $mode is 0, can contains several keywords separated with a space or |
 *                                      like "keyword1 keyword2" = We want record field like keyword1 AND field like keyword2
 *                                      or like "keyword1|keyword2" = We want record field like keyword1 OR field like keyword2
 *                             			If param $mode is 1, can contains an operator <, > or = like "<10" or ">=100.5 < 1000"
 *                             			If param $mode is 2, can contains a list of int id separated by comma like "1,3,4"
 *                             			If param $mode is 3, can contains a list of string separated by comma like "a,b,c"
 * @param	integer			$mode		0=value is list of keyword strings, 1=value is a numeric test (Example ">5.5 <10"), 2=value is a list of ID separated with comma (Example '1,3,4')
 * 										3=value is list of string separated with comma (Example 'text 1,text 2'), 4=value is a list of ID separated with comma (Example '1,3,4') for search into a multiselect string ('1,2')
 * @param	integer			$nofirstand	1=Do not output the first 'AND'
 * @return 	string 			$res 		The statement to append to the SQL query
 */
function natural_search($fields, $value, $mode = 0, $nofirstand = 0)
{
	global $db, $langs;

	$value = trim($value);

	if ($mode == 0)
	{
		$value = preg_replace('/\*/', '%', $value); // Replace * with %
	}
	if ($mode == 1)
	{
		$value = preg_replace('/([<>=]+)\s+([0-9'.preg_quote($langs->trans("DecimalSeparator"), '/').'\-])/', '\1\2', $value); // Clean string '< 10' into '<10' so we can the explode on space to get all tests to do
	}

	$value = preg_replace('/\s*\|\s*/', '|', $value);

	$crits = explode(' ', $value);
	$res = '';
	if (!is_array($fields)) $fields = array($fields);

	$nboffields = count($fields);
	$end2 = count($crits);
	$j = 0;
	foreach ($crits as $crit)
	{
		$i = 0; $i2 = 0;
		$newres = '';
		foreach ($fields as $field)
		{
			if ($mode == 1)
			{
				$operator = '=';
				$newcrit = preg_replace('/([<>=]+)/', '', trim($crit));

				$reg = array();
				preg_match('/([<>=]+)/', trim($crit), $reg);
				if ($reg[1])
				{
					$operator = $reg[1];
				}
				if ($newcrit != '')
				{
					$numnewcrit = price2num($newcrit);
					if (is_numeric($numnewcrit))
					{
						$newres .= ($i2 > 0 ? ' OR ' : '').$field.' '.$operator.' '.$numnewcrit;
					}
					else
					{
						$newres .= ($i2 > 0 ? ' OR ' : '').'1 = 2'; // force false
					}
					$i2++; // a criteria was added to string
				}
			}
			elseif ($mode == 2 || $mode == -2)
			{
				$newres .= ($i2 > 0 ? ' OR ' : '').$field." ".($mode == -2 ? 'NOT ' : '')."IN (".$db->escape(trim($crit)).")";
				if ($mode == -2) $newres .= ' OR '.$field.' IS NULL';
				$i2++; // a criteria was added to string
			}
			elseif ($mode == 3 || $mode == -3)
			{
				$tmparray = explode(',', trim($crit));
				if (count($tmparray))
				{
					$listofcodes = '';
					foreach ($tmparray as $val)
					{
						if ($val)
						{
							$listofcodes .= ($listofcodes ? ',' : '');
							$listofcodes .= "'".$db->escape(trim($val))."'";
						}
					}
					$newres .= ($i2 > 0 ? ' OR ' : '').$field." ".($mode == -3 ? 'NOT ' : '')."IN (".$listofcodes.")";
					$i2++; // a criteria was added to string
				}
				if ($mode == -3) $newres .= ' OR '.$field.' IS NULL';
			}
			elseif ($mode == 4)
			{
			    $tmparray = explode(',', trim($crit));
			    if (count($tmparray))
			    {
			        $listofcodes = '';
			        foreach ($tmparray as $val)
			        {
			            if ($val)
			            {
			                $newres .= ($i2 > 0 ? ' OR (' : '(').$field.' LIKE \''.$db->escape(trim($val)).',%\'';
			                $newres .= ' OR '.$field.' = \''.$db->escape(trim($val)).'\'';
			                $newres .= ' OR '.$field.' LIKE \'%,'.$db->escape(trim($val)).'\'';
			                $newres .= ' OR '.$field.' LIKE \'%,'.$db->escape(trim($val)).',%\'';
			                $newres .= ')';
			                $i2++;
			            }
			        }
			    }
			}
			else    // $mode=0
			{
				$tmpcrits = explode('|', $crit);
				$i3 = 0;
				foreach ($tmpcrits as $tmpcrit)
				{
                    if ($tmpcrit !== '0' && empty($tmpcrit)) continue;

					$newres .= (($i2 > 0 || $i3 > 0) ? ' OR ' : '');

					if (preg_match('/\.(id|rowid)$/', $field))	// Special case for rowid that is sometimes a ref so used as a search field
					{
						$newres .= $field." = ".(is_numeric(trim($tmpcrit)) ?trim($tmpcrit) : '0');
					}
					else
					{
						$newres .= $field." LIKE '";

						$tmpcrit = trim($tmpcrit);
						$tmpcrit2 = $tmpcrit;
						$tmpbefore = '%'; $tmpafter = '%';
						if (preg_match('/^[\^\$]/', $tmpcrit))
						{
							$tmpbefore = '';
							$tmpcrit2 = preg_replace('/^[\^\$]/', '', $tmpcrit2);
						}
						if (preg_match('/[\^\$]$/', $tmpcrit))
						{
							$tmpafter = '';
							$tmpcrit2 = preg_replace('/[\^\$]$/', '', $tmpcrit2);
						}
						$newres .= $tmpbefore;
						$newres .= $db->escape($tmpcrit2);
						$newres .= $tmpafter;
						$newres .= "'";
						if ($tmpcrit2 == '')
						{
							$newres .= ' OR '.$field." IS NULL";
						}
					}

					$i3++;
				}
				$i2++; // a criteria was added to string
			}
			$i++;
		}
		if ($newres) $res = $res.($res ? ' AND ' : '').($i2 > 1 ? '(' : '').$newres.($i2 > 1 ? ')' : '');
		$j++;
	}
	$res = ($nofirstand ? "" : " AND ")."(".$res.")";
	//print 'xx'.$res.'yy';
	return $res;
}

/**
 * Return string with full Url. The file qualified is the one defined by relative path in $object->last_main_doc
 *
 * @param   Object	$object				Object
 * @return	string						Url string
 */
function showDirectDownloadLink($object)
{
	global $conf, $langs;

	$out = '';
	$url = $object->getLastMainDocLink($object->element);

	if ($url)
	{
		$out .= img_picto('', 'globe').' '.$langs->trans("DirectDownloadLink").'<br>';
		$out .= '<input type="text" id="directdownloadlink" class="quatrevingtpercent" value="'.$url.'">';
		$out .= ajax_autoselect("directdownloadlink", 0);
	}
	return $out;
}

/**
 * Return the filename of file to get the thumbs
 *
 * @param   string  $file           Original filename (full or relative path)
 * @param   string  $extName        Extension to differenciate thumb file name ('', '_small', '_mini')
 * @param   string  $extImgTarget   Force image extension for thumbs. Use '' to keep same extension than original image (default).
 * @return  string                  New file name (full or relative path, including the thumbs/)
 */
function getImageFileNameForSize($file, $extName, $extImgTarget = '')
{
	$dirName = dirname($file);
	if ($dirName == '.') $dirName = '';

	$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i', '', $file); // We remove extension, whatever is its case
	$fileName = basename($fileName);

	if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.jpg$/i', $file) ? '.jpg' : '');
	if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.jpeg$/i', $file) ? '.jpeg' : '');
	if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.gif$/i', $file) ? '.gif' : '');
	if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.png$/i', $file) ? '.png' : '');
	if (empty($extImgTarget)) $extImgTarget = (preg_match('/\.bmp$/i', $file) ? '.bmp' : '');

	if (!$extImgTarget) return $file;

	$subdir = '';
	if ($extName) $subdir = 'thumbs/';

	return ($dirName ? $dirName.'/' : '').$subdir.$fileName.$extName.$extImgTarget; // New filename for thumb
}


/**
 * Return URL we can use for advanced preview links
 *
 * @param   string    $modulepart     propal, facture, facture_fourn, ...
 * @param   string    $relativepath   Relative path of docs.
 * @param	int		  $alldata		  Return array with all components (1 is recommended, then use a simple a href link with the class, target and mime attribute added. 'documentpreview' css class is handled by jquery code into main.inc.php)
 * @param	string	  $param		  More param on http links
 * @return  string|array              Output string with href link or array with all components of link
 */
function getAdvancedPreviewUrl($modulepart, $relativepath, $alldata = 0, $param = '')
{
	global $conf, $langs;

	if (empty($conf->use_javascript_ajax)) return '';

	$isAllowedForPreview = dolIsAllowedForPreview($relativepath);

	if ($alldata == 1)
	{
		if ($isAllowedForPreview) return array('target'=>'_blank', 'css'=>'documentpreview', 'url'=>DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&attachment=0&file='.urlencode($relativepath).($param ? '&'.$param : ''), 'mime'=>dol_mimetype($relativepath));
		else return array();
	}

	// old behavior, return a string
	if ($isAllowedForPreview) return 'javascript:document_preview(\''.dol_escape_js(DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&attachment=0&file='.urlencode($relativepath).($param ? '&'.$param : '')).'\', \''.dol_mimetype($relativepath).'\', \''.dol_escape_js($langs->trans('Preview')).'\')';
	else return '';
}


/**
 * Make content of an input box selected when we click into input field.
 *
 * @param string	$htmlname	Id of html object
 * @param string	$addlink	Add a 'link to' after
 * @return string
 */
function ajax_autoselect($htmlname, $addlink = '')
{
	global $langs;
	$out = '<script>
               jQuery(document).ready(function () {
				    jQuery("#'.$htmlname.'").click(function() { jQuery(this).select(); } );
				});
		    </script>';
	if ($addlink) $out .= ' <a href="'.$addlink.'" target="_blank">'.$langs->trans("Link").'</a>';
	return $out;
}

/**
 *	Return if a file is qualified for preview
 *
 *	@param	string	$file		Filename we looking for information
 *	@return int					1 If allowed, 0 otherwise
 *  @see    dol_mimetype(), image_format_supported() from images.lib.php
 */
function dolIsAllowedForPreview($file)
{
	global $conf;

	// Check .noexe extension in filename
	if (preg_match('/\.noexe$/i', $file)) return 0;

	// Check mime types
	$mime_preview = array('bmp', 'jpeg', 'png', 'gif', 'tiff', 'pdf', 'plain', 'css', 'webp');
	if (!empty($conf->global->MAIN_ALLOW_SVG_FILES_AS_IMAGES)) $mime_preview[] = 'svg+xml';
	//$mime_preview[]='vnd.oasis.opendocument.presentation';
	//$mime_preview[]='archive';
	$num_mime = array_search(dol_mimetype($file, '', 1), $mime_preview);
	if ($num_mime !== false) return 1;

	// By default, not allowed for preview
	return 0;
}

/**
 *	Return mime type of a file
 *
 *	@param	string	$file		Filename we looking for MIME type
 *  @param  string	$default    Default mime type if extension not found in known list
 * 	@param	int		$mode    	0=Return full mime, 1=otherwise short mime string, 2=image for mime type, 3=source language, 4=css of font fa
 *	@return string 		    	Return a mime type family (text/xxx, application/xxx, image/xxx, audio, video, archive)
 *  @see    dolIsAllowedForPreview(), image_format_supported() from images.lib.php
 */
function dol_mimetype($file, $default = 'application/octet-stream', $mode = 0)
{
	$mime = $default;
	$imgmime = 'other.png';
	$famime = 'file-o';
	$srclang = '';

	$tmpfile = preg_replace('/\.noexe$/', '', $file);

	// Plain text files
	if (preg_match('/\.txt$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $famime = 'file-text-o'; }
	if (preg_match('/\.rtx$/i', $tmpfile)) { $mime = 'text/richtext'; $imgmime = 'text.png'; $famime = 'file-text-o'; }
	if (preg_match('/\.csv$/i', $tmpfile)) { $mime = 'text/csv'; $imgmime = 'text.png'; $famime = 'file-text-o'; }
	if (preg_match('/\.tsv$/i', $tmpfile)) { $mime = 'text/tab-separated-values'; $imgmime = 'text.png'; $famime = 'file-text-o'; }
	if (preg_match('/\.(cf|conf|log)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $famime = 'file-text-o'; }
	if (preg_match('/\.ini$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'ini'; $famime = 'file-text-o'; }
    if (preg_match('/\.md$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'md'; $famime = 'file-text-o'; }
	if (preg_match('/\.css$/i', $tmpfile)) { $mime = 'text/css'; $imgmime = 'css.png'; $srclang = 'css'; $famime = 'file-text-o'; }
	// Certificate files
	if (preg_match('/\.(crt|cer|key|pub)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $famime = 'file-text-o'; }
	// XML based (HTML/XML/XAML)
	if (preg_match('/\.(html|htm|shtml)$/i', $tmpfile)) { $mime = 'text/html'; $imgmime = 'html.png'; $srclang = 'html'; $famime = 'file-text-o'; }
	if (preg_match('/\.(xml|xhtml)$/i', $tmpfile)) { $mime = 'text/xml'; $imgmime = 'other.png'; $srclang = 'xml'; $famime = 'file-text-o'; }
    if (preg_match('/\.xaml$/i', $tmpfile)) { $mime = 'text/xml'; $imgmime = 'other.png'; $srclang = 'xaml'; $famime = 'file-text-o'; }
	// Languages
	if (preg_match('/\.bas$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'bas'; $famime = 'file-code-o'; }
	if (preg_match('/\.(c)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'c'; $famime = 'file-code-o'; }
	if (preg_match('/\.(cpp)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'cpp'; $famime = 'file-code-o'; }
    if (preg_match('/\.cs$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'cs';  $famime = 'file-code-o'; }
	if (preg_match('/\.(h)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'h'; $famime = 'file-code-o'; }
	if (preg_match('/\.(java|jsp)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'java'; $famime = 'file-code-o'; }
	if (preg_match('/\.php([0-9]{1})?$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'php.png'; $srclang = 'php'; $famime = 'file-code-o'; }
	if (preg_match('/\.phtml$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'php.png'; $srclang = 'php'; $famime = 'file-code-o'; }
	if (preg_match('/\.(pl|pm)$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'pl.png'; $srclang = 'perl'; $famime = 'file-code-o'; }
	if (preg_match('/\.sql$/i', $tmpfile)) { $mime = 'text/plain'; $imgmime = 'text.png'; $srclang = 'sql'; $famime = 'file-code-o'; }
	if (preg_match('/\.js$/i', $tmpfile)) { $mime = 'text/x-javascript'; $imgmime = 'jscript.png'; $srclang = 'js'; $famime = 'file-code-o'; }
	// Open office
	if (preg_match('/\.odp$/i', $tmpfile)) { $mime = 'application/vnd.oasis.opendocument.presentation'; $imgmime = 'ooffice.png'; $famime = 'file-powerpoint-o'; }
	if (preg_match('/\.ods$/i', $tmpfile)) { $mime = 'application/vnd.oasis.opendocument.spreadsheet'; $imgmime = 'ooffice.png'; $famime = 'file-excel-o'; }
	if (preg_match('/\.odt$/i', $tmpfile)) { $mime = 'application/vnd.oasis.opendocument.text'; $imgmime = 'ooffice.png'; $famime = 'file-word-o'; }
	// MS Office
	if (preg_match('/\.mdb$/i', $tmpfile)) { $mime = 'application/msaccess'; $imgmime = 'mdb.png'; $famime = 'file-o'; }
	if (preg_match('/\.doc(x|m)?$/i', $tmpfile)) { $mime = 'application/msword'; $imgmime = 'doc.png'; $famime = 'file-word-o'; }
	if (preg_match('/\.dot(x|m)?$/i', $tmpfile)) { $mime = 'application/msword'; $imgmime = 'doc.png'; $famime = 'file-word-o'; }
	if (preg_match('/\.xlt(x)?$/i', $tmpfile)) { $mime = 'application/vnd.ms-excel'; $imgmime = 'xls.png'; $famime = 'file-excel-o'; }
	if (preg_match('/\.xla(m)?$/i', $tmpfile)) { $mime = 'application/vnd.ms-excel'; $imgmime = 'xls.png'; $famime = 'file-excel-o'; }
	if (preg_match('/\.xls$/i', $tmpfile)) { $mime = 'application/vnd.ms-excel'; $imgmime = 'xls.png'; $famime = 'file-excel-o'; }
	if (preg_match('/\.xls(b|m|x)$/i', $tmpfile)) { $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; $imgmime = 'xls.png'; $famime = 'file-excel-o'; }
	if (preg_match('/\.pps(m|x)?$/i', $tmpfile)) { $mime = 'application/vnd.ms-powerpoint'; $imgmime = 'ppt.png'; $famime = 'file-powerpoint-o'; }
	if (preg_match('/\.ppt(m|x)?$/i', $tmpfile)) { $mime = 'application/x-mspowerpoint'; $imgmime = 'ppt.png'; $famime = 'file-powerpoint-o'; }
	// Other
	if (preg_match('/\.pdf$/i', $tmpfile)) { $mime = 'application/pdf'; $imgmime = 'pdf.png'; $famime = 'file-pdf-o'; }
	// Scripts
	if (preg_match('/\.bat$/i', $tmpfile)) { $mime = 'text/x-bat'; $imgmime = 'script.png'; $srclang = 'dos'; $famime = 'file-code-o'; }
	if (preg_match('/\.sh$/i', $tmpfile)) { $mime = 'text/x-sh'; $imgmime = 'script.png'; $srclang = 'bash'; $famime = 'file-code-o'; }
	if (preg_match('/\.ksh$/i', $tmpfile)) { $mime = 'text/x-ksh'; $imgmime = 'script.png'; $srclang = 'bash'; $famime = 'file-code-o'; }
	if (preg_match('/\.bash$/i', $tmpfile)) { $mime = 'text/x-bash'; $imgmime = 'script.png'; $srclang = 'bash'; $famime = 'file-code-o'; }
	// Images
	if (preg_match('/\.ico$/i', $tmpfile)) { $mime = 'image/x-icon'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	if (preg_match('/\.(jpg|jpeg)$/i', $tmpfile)) { $mime = 'image/jpeg'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	if (preg_match('/\.png$/i', $tmpfile)) { $mime = 'image/png'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	if (preg_match('/\.gif$/i', $tmpfile)) { $mime = 'image/gif'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	if (preg_match('/\.bmp$/i', $tmpfile)) { $mime = 'image/bmp'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	if (preg_match('/\.(tif|tiff)$/i', $tmpfile)) { $mime = 'image/tiff'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	if (preg_match('/\.svg$/i', $tmpfile)) { $mime = 'image/svg+xml'; $imgmime = 'image.png'; $famime = 'file-image-o'; }
	// Calendar
	if (preg_match('/\.vcs$/i', $tmpfile)) { $mime = 'text/calendar'; $imgmime = 'other.png'; $famime = 'file-text-o'; }
	if (preg_match('/\.ics$/i', $tmpfile)) { $mime = 'text/calendar'; $imgmime = 'other.png'; $famime = 'file-text-o'; }
	// Other
	if (preg_match('/\.torrent$/i', $tmpfile)) { $mime = 'application/x-bittorrent'; $imgmime = 'other.png'; $famime = 'file-o'; }
	// Audio
	if (preg_match('/\.(mp3|ogg|au|wav|wma|mid)$/i', $tmpfile)) { $mime = 'audio'; $imgmime = 'audio.png'; $famime = 'file-audio-o'; }
	// Video
	if (preg_match('/\.ogv$/i', $tmpfile)) { $mime = 'video/ogg'; $imgmime = 'video.png'; $famime = 'file-video-o'; }
	if (preg_match('/\.webm$/i', $tmpfile)) { $mime = 'video/webm'; $imgmime = 'video.png'; $famime = 'file-video-o'; }
	if (preg_match('/\.avi$/i', $tmpfile)) { $mime = 'video/x-msvideo'; $imgmime = 'video.png'; $famime = 'file-video-o'; }
	if (preg_match('/\.divx$/i', $tmpfile)) { $mime = 'video/divx'; $imgmime = 'video.png'; $famime = 'file-video-o'; }
	if (preg_match('/\.xvid$/i', $tmpfile)) { $mime = 'video/xvid'; $imgmime = 'video.png'; $famime = 'file-video-o'; }
	if (preg_match('/\.(wmv|mpg|mpeg)$/i', $tmpfile)) { $mime = 'video'; $imgmime = 'video.png'; $famime = 'file-video-o'; }
	// Archive
	if (preg_match('/\.(zip|rar|gz|tgz|z|cab|bz2|7z|tar|lzh)$/i', $tmpfile)) { $mime = 'archive'; $imgmime = 'archive.png'; $famime = 'file-archive-o'; }    // application/xxx where zzz is zip, ...
	// Exe
	if (preg_match('/\.(exe|com)$/i', $tmpfile)) { $mime = 'application/octet-stream'; $imgmime = 'other.png'; $famime = 'file-o'; }
	// Lib
	if (preg_match('/\.(dll|lib|o|so|a)$/i', $tmpfile)) { $mime = 'library'; $imgmime = 'library.png'; $famime = 'file-o'; }
	// Err
	if (preg_match('/\.err$/i', $tmpfile)) { $mime = 'error'; $imgmime = 'error.png'; $famime = 'file-text-o'; }

	// Return string
	if ($mode == 1)
	{
		$tmp = explode('/', $mime);
		return (!empty($tmp[1]) ? $tmp[1] : $tmp[0]);
	}
	if ($mode == 2)
	{
		return $imgmime;
	}
	if ($mode == 3)
	{
		return $srclang;
	}
	if ($mode == 4)
	{
		return $famime;
	}
	return $mime;
}

/**
 * Return value from dictionary
 *
 * @param string	$tablename		name of dictionary
 * @param string	$field			the value to return
 * @param int		$id				id of line
 * @param bool		$checkentity	add filter on entity
 * @param string	$rowidfield		name of the column rowid
 * @return string
 */
function getDictvalue($tablename, $field, $id, $checkentity = false, $rowidfield = 'rowid')
{
	global $dictvalues, $db, $langs;

	if (!isset($dictvalues[$tablename]))
	{
		$dictvalues[$tablename] = array();
		$sql = 'SELECT * FROM '.$tablename.' WHERE 1'; // Here select * is allowed as it is generic code and we don't have list of fields
		if ($checkentity) $sql .= ' AND entity IN (0,'.getEntity($tablename).')';

		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql))
			{
				$dictvalues[$tablename][$obj->{$rowidfield}] = $obj;
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	if (!empty($dictvalues[$tablename][$id])) return $dictvalues[$tablename][$id]->{$field}; // Found
	else // Not found
	{
		if ($id > 0) return $id;
		return '';
	}
}

/**
 *	Return true if the color is light
 *
 *  @param	string	$stringcolor		String with hex (FFFFFF) or comma RGB ('255,255,255')
 *  @return	int							-1 : Error with argument passed |0 : color is dark | 1 : color is light
 */
function colorIsLight($stringcolor)
{
    $stringcolor = str_replace('#', '', $stringcolor);
	$res = -1;
	if (!empty($stringcolor))
	{
		$res = 0;
		$tmp = explode(',', $stringcolor);
		if (count($tmp) > 1)   // This is a comma RGB ('255','255','255')
		{
			$r = $tmp[0];
			$g = $tmp[1];
			$b = $tmp[2];
		}
		else
		{
			$hexr = $stringcolor[0].$stringcolor[1];
			$hexg = $stringcolor[2].$stringcolor[3];
			$hexb = $stringcolor[4].$stringcolor[5];
			$r = hexdec($hexr);
			$g = hexdec($hexg);
			$b = hexdec($hexb);
		}
		$bright = (max($r, $g, $b) + min($r, $g, $b)) / 510.0; // HSL algorithm
		if ($bright > 0.6) $res = 1;
	}
	return $res;
}

/**
 * Function to test if an entry is enabled or not
 *
 * @param	string		$type_user					0=We test for internal user, 1=We test for external user
 * @param	array		$menuentry					Array for feature entry to test
 * @param	array		$listofmodulesforexternal	Array with list of modules allowed to external users
 * @return	int										0=Hide, 1=Show, 2=Show gray
 */
function isVisibleToUserType($type_user, &$menuentry, &$listofmodulesforexternal)
{
	global $conf;

	//print 'type_user='.$type_user.' module='.$menuentry['module'].' enabled='.$menuentry['enabled'].' perms='.$menuentry['perms'];
	//print 'ok='.in_array($menuentry['module'], $listofmodulesforexternal);
	if (empty($menuentry['enabled'])) return 0; // Entry disabled by condition
	if ($type_user && $menuentry['module'])
	{
		$tmploops = explode('|', $menuentry['module']);
		$found = 0;
		foreach ($tmploops as $tmploop)
		{
			if (in_array($tmploop, $listofmodulesforexternal)) {
				$found++; break;
			}
		}
		if (!$found) return 0; // Entry is for menus all excluded to external users
	}
	if (!$menuentry['perms'] && $type_user) return 0; // No permissions and user is external
	if (!$menuentry['perms'] && !empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))	return 0; // No permissions and option to hide when not allowed, even for internal user, is on
	if (!$menuentry['perms']) return 2; // No permissions and user is external
	return 1;
}

/**
 * Round to next multiple.
 *
 * @param 	double		$n		Number to round up
 * @param 	integer		$x		Multiple. For example 60 to round up to nearest exact minute for a date with seconds.
 * @return 	integer				Value rounded.
 */
function roundUpToNextMultiple($n, $x = 5)
{
	return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
}

/**
 * Function dolGetBadge
 *
 * @param   string  $label      label of badge no html : use in alt attribute for accessibility
 * @param   string  $html       optional : label of badge with html
 * @param   string  $type       type of badge : Primary Secondary Success Danger Warning Info Light Dark status0 status1 status2 status3 status4 status5 status6 status7 status8 status9
 * @param   string  $mode       default '' , pill, dot
 * @param   string  $url        the url for link
 * @param   array   $params     various params for future : recommended rather than adding more fuction arguments. array('attr'=>array('title'=>'abc'))
 * @return  string              Html badge
 */
function dolGetBadge($label, $html = '', $type = 'primary', $mode = '', $url = '', $params = array())
{
    $attr = array(
    	'class'=>'badge '.(!empty($mode) ? ' badge-'.$mode : '').(!empty($type) ? ' badge-'.$type : '').(empty($params['css']) ? '' : ' '.$params['css'])
    );

    if (empty($html)) {
        $html = $label;
    }

    if (!empty($url)) {
        $attr['href'] = $url;
    }

    if ($mode === 'dot') {
        $attr['class'] .= ' classfortooltip';
        $attr['title'] = $html;
        $attr['aria-label'] = $label;
        $html = '';
    }

    // Override attr
    if (!empty($params['attr']) && is_array($params['attr'])) {
        foreach ($params['attr']as $key => $value) {
			if ($key == 'class') {
				$attr['class'] .= ' '.$value;
			}
			elseif ($key == 'classOverride') {
				$attr['class'] = $value;
			}
			else {
				$attr[$key] = $value;
			}
        }
    }

    // TODO: add hook

    // escape all attribute
    $attr = array_map('dol_escape_htmltag', $attr);

    $TCompiledAttr = array();
    foreach ($attr as $key => $value) {
        $TCompiledAttr[] = $key.'="'.$value.'"';
    }

    $compiledAttributes = !empty($TCompiledAttr) ?implode(' ', $TCompiledAttr) : '';

    $tag = !empty($url) ? 'a' : 'span';

    return '<'.$tag.' '.$compiledAttributes.'>'.$html.'</'.$tag.'>';
}


/**
 * Function dolGetStatus
 *
 * @param   string  $statusLabel       Label of badge no html : use in alt attribute for accessibility
 * @param   string  $statusLabelShort  Short label of badge no html
 * @param   string  $html              Optional : label of badge with html
 * @param   string  $statusType        status0 status1 status2 status3 status4 status5 status6 status7 status8 status9 : image name or badge name
 * @param   int	    $displayMode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
 * @param   string  $url               The url for link
 * @param   array   $params            Various params for future : recommended rather than adding more function arguments
 * @return  string                     Html status string
 */
function dolGetStatus($statusLabel = '', $statusLabelShort = '', $html = '', $statusType = 'status0', $displayMode = 0, $url = '', $params = array())
{
    global $conf;

    $return = '';
    $dolGetBadgeParams = array();

    if (!empty($params['badgeParams'])) {
        $dolGetBadgeParams = $params['badgeParams'];
    }

    // TODO : add a hook

    if ($displayMode == 0) {
        $return = !empty($html) ? $html : $statusLabel;
    }
    elseif ($displayMode == 1) {
        $return = !empty($html) ? $html : (!empty($statusLabelShort) ? $statusLabelShort : $statusLabel);
    }
    // Use status with images (for backward compatibility)
    elseif (!empty($conf->global->MAIN_STATUS_USES_IMAGES)) {
        $return = '';
        $htmlLabel      = (in_array($displayMode, array(1, 2, 5)) ? '<span class="hideonsmartphone">' : '').(!empty($html) ? $html : $statusLabel).(in_array($displayMode, array(1, 2, 5)) ? '</span>' : '');
        $htmlLabelShort = (in_array($displayMode, array(1, 2, 5)) ? '<span class="hideonsmartphone">' : '').(!empty($html) ? $html : (!empty($statusLabelShort) ? $statusLabelShort : $statusLabel)).(in_array($displayMode, array(1, 2, 5)) ? '</span>' : '');

        // For small screen, we always use the short label instead of long label.
        if (!empty($conf->dol_optimize_smallscreen))
        {
        	if ($displayMode == 0) $displayMode = 1;
        	elseif ($displayMode == 4) $displayMode = 2;
        	elseif ($displayMode == 6) $displayMode = 5;
        }

        // For backward compatibility. Image's filename are still in French, so we use this array to convert
        $statusImg = array(
        	'status0' => 'statut0'
        	,'status1' => 'statut1'
        	,'status2' => 'statut2'
        	,'status3' => 'statut3'
        	,'status4' => 'statut4'
        	,'status5' => 'statut5'
        	,'status6' => 'statut6'
        	,'status7' => 'statut7'
        	,'status8' => 'statut8'
        	,'status9' => 'statut9'
        );

        if (!empty($statusImg[$statusType])) {
            $htmlImg = img_picto($statusLabel, $statusImg[$statusType]);
        } else {
            $htmlImg = img_picto($statusLabel, $statusType);
        }

        if ($displayMode === 2) {
            $return = $htmlImg.' '.$htmlLabelShort;
        }
        elseif ($displayMode === 3) {
            $return = $htmlImg;
        }
        elseif ($displayMode === 4) {
            $return = $htmlImg.' '.$htmlLabel;
        }
        elseif ($displayMode === 5) {
            $return = $htmlLabelShort.' '.$htmlImg;
        }
        else { // $displayMode >= 6
            $return = $htmlLabel.' '.$htmlImg;
        }
    }
    // Use new badge
    elseif (empty($conf->global->MAIN_STATUS_USES_IMAGES) && !empty($displayMode)) {
        $statusLabelShort = !empty($statusLabelShort) ? $statusLabelShort : $statusLabel;

		$dolGetBadgeParams['attr']['class'] = 'badge-status';

        if ($displayMode == 3) {
            $return = dolGetBadge($statusLabel, '', $statusType, 'dot', $url, $dolGetBadgeParams);
        }
        elseif ($displayMode === 5) {
            $return = dolGetBadge($statusLabelShort, $html, $statusType, '', $url, $dolGetBadgeParams);
        }
        else {
            $return = dolGetBadge($statusLabel, $html, $statusType, '', $url, $dolGetBadgeParams);
        }
    }

    return $return;
}


/**
 * Function dolGetButtonAction
 *
 * @param string    $label      label of button no html : use in alt attribute for accessibility $html is not empty
 * @param string    $html       optional : content with html
 * @param string    $actionType default, delete, danger
 * @param string    $url        the url for link
 * @param string    $id         attribute id of button
 * @param int       $userRight  user action right
 * @param array     $params     various params for future : recommended rather than adding more function arguments
 * @return string               html button
 */
function dolGetButtonAction($label, $html = '', $actionType = 'default', $url = '', $id = '', $userRight = 1, $params = array())
{
    $class = 'butAction';
    if ($actionType == 'danger' || $actionType == 'delete') {
        $class = 'butActionDelete';
    }

    $attr = array(
        'class' => $class
        ,'href' => empty($url) ? '' : $url
    );

    if (empty($html)) {
        $html = $label;
    } else {
        $attr['aria-label'] = $label;
    }

    if (empty($userRight)) {
        $attr['class'] = 'butActionRefused';
        $attr['href'] = '';
    }

    if (!empty($id)) {
        $attr['id'] = $id;
    }

    // Override attr
    if (!empty($params['attr']) && is_array($params['attr'])) {
        foreach ($params['attr'] as $key => $value) {
            if ($key == 'class') {
                $attr['class'] .= ' '.$value;
            }
            elseif ($key == 'classOverride') {
                $attr['class'] = $value;
            }
            else {
                $attr[$key] = $value;
            }
        }
    }

    if (isset($attr['href']) && empty($attr['href'])) {
        unset($attr['href']);
    }

    // TODO : add a hook

    // escape all attribute
    $attr = array_map('dol_escape_htmltag', $attr);

    $TCompiledAttr = array();
    foreach ($attr as $key => $value) {
        $TCompiledAttr[] = $key.'="'.$value.'"';
    }

    $compiledAttributes = !empty($TCompiledAttr) ?implode(' ', $TCompiledAttr) : '';

    $tag = !empty($attr['href']) ? 'a' : 'span';

    return '<div class="inline-block divButAction"><'.$tag.' '.$compiledAttributes.'>'.$html.'</'.$tag.'></div>';
}

/**
 * Function dolGetButtonTitle : this kind of buttons are used in title in list
 *
 * @param string    $label      label of button
 * @param string    $helpText   optional : content for help tooltip
 * @param string    $iconClass  class for icon element (Example: 'fa fa-file')
 * @param string    $url        the url for link
 * @param string    $id         attribute id of button
 * @param int       $status     0 no user rights, 1 active, -1 Feature Disabled, -2 disable Other reason use helpText as tooltip
 * @param array     $params     various params for future : recommended rather than adding more function arguments
 * @return string               html button
 */
function dolGetButtonTitle($label, $helpText = '', $iconClass = 'fa fa-file', $url = '', $id = '', $status = 1, $params = array())
{
    global $langs, $conf, $user;

    // Actually this conf is used in css too for external module compatibility and smooth transition to this function
    if (!empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED) && (!$user->admin) && $status <= 0) {
        return '';
    }

    $class = 'btnTitle';

    // hidden conf keep during button transition TODO: remove this block
    if (!empty($conf->global->MAIN_USE_OLD_TITLE_BUTTON)) {
        $class = 'butActionNew';
    }
    if (!empty($params['morecss'])) $class .= ' '.$params['morecss'];

    $attr = array(
        'class' => $class
        ,'href' => empty($url) ? '' : $url
    );

    if (!empty($helpText)) {
        $attr['title'] = dol_escape_htmltag($helpText);
    }

    if ($status <= 0) {
        $attr['class'] .= ' refused';

        // hidden conf keep during button transition TODO: remove this block
        if (!empty($conf->global->MAIN_USE_OLD_TITLE_BUTTON)) {
            $attr['class'] = 'butActionNewRefused';
        }

        $attr['href'] = '';

        if ($status == -1) { // disable
            $attr['title'] = dol_escape_htmltag($langs->transnoentitiesnoconv("FeatureDisabled"));
        }
        elseif ($status == 0) { // Not enough permissions
            $attr['title'] = dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions"));
        }
    }

    if (!empty($attr['title'])) {
        $attr['class'] .= ' classfortooltip';
    }

    if (!empty($id)) {
        $attr['id'] = $id;
    }

    // Override attr
    if (!empty($params['attr']) && is_array($params['attr'])) {
        foreach ($params['attr'] as $key => $value) {
            if ($key == 'class') {
                $attr['class'] .= ' '.$value;
            }
            elseif ($key == 'classOverride') {
                $attr['class'] = $value;
            }
            else {
                $attr[$key] = $value;
            }
        }
    }

    if (isset($attr['href']) && empty($attr['href'])) {
        unset($attr['href']);
    }

    // TODO : add a hook

    // escape all attribute
    $attr = array_map('dol_escape_htmltag', $attr);

    $TCompiledAttr = array();
    foreach ($attr as $key => $value) {
        $TCompiledAttr[] = $key.'="'.$value.'"';
    }

    $compiledAttributes = (empty($TCompiledAttr) ? '' : implode(' ', $TCompiledAttr));

    $tag = (empty($attr['href']) ? 'span' : 'a');

    $button = '<'.$tag.' '.$compiledAttributes.' >';
    $button .= '<span class="'.$iconClass.' valignmiddle btnTitle-icon"></span>';
    $button .= '<span class="valignmiddle text-plus-circle btnTitle-label'.(empty($params['forcenohideoftext']) ? ' hideonsmartphone' : '').'">'.$label.'</span>';
    $button .= '</'.$tag.'>';

    // hidden conf keep during button transition TODO: remove this block
    if (!empty($conf->global->MAIN_USE_OLD_TITLE_BUTTON)) {
        $button = '<'.$tag.' '.$compiledAttributes.' ><span class="text-plus-circle">'.$label.'</span>';
        $button .= '<span class="'.$iconClass.' valignmiddle"></span>';
        $button .= '</'.$tag.'>';
    }

    return $button;
}

/**
 * Return if a file can contains executable content
 *
 * @param   string  $filename       File name to test
 * @return  boolean                 True if yes, False if no
 */
function isAFileWithExecutableContent($filename)
{
    if (preg_match('/\.(htm|html|js|phar|php|php\d+|phtml|pht|pl|py|cgi|ksh|sh|shtml|bash|bat|cmd|wpk|exe|dmg)$/i', $filename))
    {
        return true;
    }

    return false;
}

/**
 * Return the value of token currently saved into session with name 'newtoken'.
 * This token must be send by any POST as it will be used by next page for comparison with value in session.
 *
 * @return  string
 */
function newToken()
{
	return $_SESSION['newtoken'];
}

/**
 * Return the value of token currently saved into session with name 'token'.
 *
 * @return  string
 */
function currentToken()
{
	return $_SESSION['token'];
}
