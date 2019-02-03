<?php
/* Copyright (C) 2000-2007	Rodolphe Quiedeville			<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Christophe Combelles			<ccomb@free.fr>
 * Copyright (C) 2005-2017	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2018	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2017	Alexandre Spangaro			<aspangaro@open-dsi.fr>
 * Copyright (C) 2014		Cédric GROSS					<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2018       Frédéric France             <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */

include_once DOL_DOCUMENT_ROOT .'/core/lib/json.lib.php';


/**
 * Return a DoliDB instance (database handler).
 *
 * @param   string	$type		Type of database (mysql, pgsql...)
 * @param	string	$host		Address of database server
 * @param	string	$user		Nom de l'utilisateur autorise
 * @param	string	$pass		Mot de passe
 * @param	string	$name		Nom de la database
 * @param	int		$port		Port of database server
 * @return	DoliDB				A DoliDB instance
 */
function getDoliDBInstance($type, $host, $user, $pass, $name, $port)
{
	require_once DOL_DOCUMENT_ROOT ."/core/db/".$type.'.class.php';

	$class='DoliDB'.ucfirst($type);
	$dolidb=new $class($type, $host, $user, $pass, $name, $port);
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
 * 	@return	mixed				Entity id(s) to use
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
		$out='';
		$addzero = array('user', 'usergroup', 'c_email_templates', 'email_template', 'default_values');
		if (in_array($element, $addzero)) $out.= '0,';
		$out.= $conf->entity;
		return $out;
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

	$name='unknown';
	$version='';
	$os='unknown';
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
	if (preg_match('/linux/i', $user_agent))			{ $os='linux'; }
	elseif (preg_match('/macintosh/i', $user_agent))	{ $os='macintosh'; }
	elseif (preg_match('/windows/i', $user_agent))		{ $os='windows'; }

	// Name
	if (preg_match('/firefox(\/|\s)([\d\.]*)/i', $user_agent, $reg))      { $name='firefox';   $version=$reg[2]; }
	elseif (preg_match('/edge(\/|\s)([\d\.]*)/i', $user_agent, $reg))     { $name='edge';      $version=$reg[2]; }
	elseif (preg_match('/chrome(\/|\s)([\d\.]+)/i', $user_agent, $reg))   { $name='chrome';    $version=$reg[2]; }    // we can have 'chrome (Mozilla...) chrome x.y' in one string
	elseif (preg_match('/chrome/i', $user_agent, $reg))                   { $name='chrome'; }
	elseif (preg_match('/iceweasel/i', $user_agent))                      { $name='iceweasel'; }
	elseif (preg_match('/epiphany/i', $user_agent))                       { $name='epiphany';  }
	elseif (preg_match('/safari(\/|\s)([\d\.]*)/i', $user_agent, $reg))   { $name='safari';    $version=$reg[2]; }	// Safari is often present in string for mobile but its not.
	elseif (preg_match('/opera(\/|\s)([\d\.]*)/i', $user_agent, $reg))    { $name='opera';     $version=$reg[2]; }
	elseif (preg_match('/(MSIE\s([0-9]+\.[0-9]))|.*(Trident\/[0-9]+.[0-9];.*rv:([0-9]+\.[0-9]+))/i', $user_agent, $reg))  { $name='ie'; $version=end($reg); }    // MS products at end
	elseif (preg_match('/(Windows NT\s([0-9]+\.[0-9])).*(Trident\/[0-9]+.[0-9];.*rv:([0-9]+\.[0-9]+))/i', $user_agent, $reg))  { $name='ie'; $version=end($reg); }    // MS products at end
	elseif (preg_match('/l(i|y)n(x|ks)(\(|\/|\s)*([\d\.]+)/i', $user_agent, $reg)) { $name='lynxlinks'; $version=$reg[4]; }

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
	global $conf,$user,$langs,$db;
	$disconnectdone=false; $depth=0;
	if (is_object($db) && ! empty($db->connected)) { $depth=$db->transaction_opened; $disconnectdone=$db->close(); }
	dol_syslog("--- End access to ".$_SERVER["PHP_SELF"].(($disconnectdone && $depth)?' (Warn: db disconnection forced, transaction depth was '.$depth.')':''), (($disconnectdone && $depth)?LOG_WARNING:LOG_INFO));
}

/**
 * Return true if we are in a context of submitting a parameter
 *
 * @param 	string	$paramname		Name or parameter to test
 * @return 	boolean					True if we have just submit a POST or GET request with the parameter provided (even if param is empty)
 */
function GETPOSTISSET($paramname)
{
	return (isset($_POST[$paramname]) || isset($_GET[$paramname]));
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
 *  @param	int		$method	     Type of method (0 = get then post, 1 = only get, 2 = only post, 3 = post then get, 4 = post then get then cookie)
 *  @param  int     $filter      Filter to apply when $check is set to 'custom'. (See http://php.net/manual/en/filter.filters.php for détails)
 *  @param  mixed   $options     Options to pass to filter_var when $check is set to 'custom'
 *  @param	string	$noreplace	 Force disable of replacement of __xxx__ strings.
 *  @return string|string[]      Value found (string or array), or '' if check fails
 */
function GETPOST($paramname, $check = 'none', $method = 0, $filter = null, $options = null, $noreplace = 0)
{
	global $mysoc,$user,$conf;

	if (empty($paramname)) return 'BadFirstParameterForGETPOST';
	if (empty($check))
	{
		dol_syslog("Deprecated use of GETPOST, called with 1st param = ".$paramname." and 2nd param is '', when calling page ".$_SERVER["PHP_SELF"], LOG_WARNING);
		// Enable this line to know who call the GETPOST with '' $check parameter.
		//var_dump(debug_backtrace()[0]);
	}

	if (empty($method)) $out = isset($_GET[$paramname])?$_GET[$paramname]:(isset($_POST[$paramname])?$_POST[$paramname]:'');
	elseif ($method==1) $out = isset($_GET[$paramname])?$_GET[$paramname]:'';
	elseif ($method==2) $out = isset($_POST[$paramname])?$_POST[$paramname]:'';
	elseif ($method==3) $out = isset($_POST[$paramname])?$_POST[$paramname]:(isset($_GET[$paramname])?$_GET[$paramname]:'');
	elseif ($method==4) $out = isset($_POST[$paramname])?$_POST[$paramname]:(isset($_GET[$paramname])?$_GET[$paramname]:(isset($_COOKIE[$paramname])?$_COOKIE[$paramname]:''));
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
		if (! empty($_GET['restore_lastsearch_values']))        // Use $_GET here and not GETPOST
		{
			if (! empty($_SESSION['lastsearch_values_'.$relativepathstring]))	// If there is saved values
			{
				$tmp=json_decode($_SESSION['lastsearch_values_'.$relativepathstring], true);
				if (is_array($tmp))
				{
					foreach($tmp as $key => $val)
					{
						if ($key == $paramname)	// We are on the requested parameter
						{
							$out=$val;
							break;
						}
					}
				}
			}
			// If there is saved contextpage, page or limit
			if ($paramname == 'contextpage' && ! empty($_SESSION['lastsearch_contextpage_'.$relativepathstring]))
			{
				$out = $_SESSION['lastsearch_contextpage_'.$relativepathstring];
			}
			elseif ($paramname == 'page' && ! empty($_SESSION['lastsearch_page_'.$relativepathstring]))
			{
				$out = $_SESSION['lastsearch_page_'.$relativepathstring];
			}
			elseif ($paramname == 'limit' && ! empty($_SESSION['lastsearch_limit_'.$relativepathstring]))
			{
				$out = $_SESSION['lastsearch_limit_'.$relativepathstring];
			}
		}
		// Else, retreive default values if we are not doing a sort
		elseif (! isset($_GET['sortfield']))	// If we did a click on a field to sort, we do no apply default values. Same if option MAIN_ENABLE_DEFAULT_VALUES is not set
		{
			if (! empty($_GET['action']) && $_GET['action'] == 'create' && ! isset($_GET[$paramname]) && ! isset($_POST[$paramname]))
			{
				// Search default value from $object->field
				global $object;
				if (is_object($object) && isset($object->fields[$paramname]['default']))
				{
					$out = $object->fields[$paramname]['default'];
				}
			}
			if (! empty($conf->global->MAIN_ENABLE_DEFAULT_VALUES))
			{
				if (! empty($_GET['action']) && $_GET['action'] == 'create' && ! isset($_GET[$paramname]) && ! isset($_POST[$paramname]))
				{
					// Now search in setup to overwrite default values
					if (! empty($user->default_values))		// $user->default_values defined from menu 'Setup - Default values'
					{
						if (isset($user->default_values[$relativepathstring]['createform']))
						{
							foreach($user->default_values[$relativepathstring]['createform'] as $defkey => $defval)
							{
								$qualified = 0;
								if ($defkey != '_noquery_')
								{
									$tmpqueryarraytohave=explode('&', $defkey);
									$tmpqueryarraywehave=explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
									$foundintru=0;
									foreach($tmpqueryarraytohave as $tmpquerytohave)
									{
										if (! in_array($tmpquerytohave, $tmpqueryarraywehave)) $foundintru=1;
									}
									if (! $foundintru) $qualified=1;
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
				elseif (! empty($paramname) && ! isset($_GET[$paramname]) && ! isset($_POST[$paramname]))
				{
					if (! empty($user->default_values))		// $user->default_values defined from menu 'Setup - Default values'
					{
						//var_dump($user->default_values[$relativepathstring]);
						if ($paramname == 'sortfield' || $paramname == 'sortorder')			// Sorted on which fields ? ASC or DESC ?
						{
							if (isset($user->default_values[$relativepathstring]['sortorder']))	// Even if paramname is sortfield, data are stored into ['sortorder...']
							{
								foreach($user->default_values[$relativepathstring]['sortorder'] as $defkey => $defval)
								{
									$qualified = 0;
									if ($defkey != '_noquery_')
									{
										$tmpqueryarraytohave=explode('&', $defkey);
										$tmpqueryarraywehave=explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
										$foundintru=0;
										foreach($tmpqueryarraytohave as $tmpquerytohave)
										{
											if (! in_array($tmpquerytohave, $tmpqueryarraywehave)) $foundintru=1;
										}
										if (! $foundintru) $qualified=1;
										//var_dump($defkey.'-'.$qualified);
									}
									else $qualified = 1;

									if ($qualified)
									{
										$forbidden_chars_to_replace=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",";","=");  // we accept _, -, . and ,
										foreach($user->default_values[$relativepathstring]['sortorder'][$defkey] as $key => $val)
										{
											if ($out) $out.=', ';
											if ($paramname == 'sortfield')
											{
												$out.=dol_string_nospecial($key, '', $forbidden_chars_to_replace);
											}
											if ($paramname == 'sortorder')
											{
												$out.=dol_string_nospecial($val, '', $forbidden_chars_to_replace);
											}
										}
										//break;	// No break for sortfield and sortorder so we can cumulate fields (is it realy usefull ?)
									}
								}
							}
						}
						elseif (isset($user->default_values[$relativepathstring]['filters']))
						{
							foreach($user->default_values[$relativepathstring]['filters'] as $defkey => $defval)	// $defkey is a querystring like 'a=b&c=d', $defval is key of user
							{
								$qualified = 0;
								if ($defkey != '_noquery_')
								{
									$tmpqueryarraytohave=explode('&', $defkey);
									$tmpqueryarraywehave=explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));
									$foundintru=0;
									foreach($tmpqueryarraytohave as $tmpquerytohave)
									{
										if (! in_array($tmpquerytohave, $tmpqueryarraywehave)) $foundintru=1;
									}
									if (! $foundintru) $qualified=1;
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
											$forbidden_chars_to_replace=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",";","=");  // we accept _, -, . and ,
											$out = dol_string_nospecial($user->default_values[$relativepathstring]['filters'][$defkey][$paramname], '', $forbidden_chars_to_replace);
										}
									}
									else
									{
										$forbidden_chars_to_replace=array(" ","'","/","\\",":","*","?","\"","<",">","|","[","]",";","=");  // we accept _, -, . and ,
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

	// Substitution variables for GETPOST (used to get final url with variable parameters or final default value with variable paramaters)
	// Example of variables: __DAY__, __MONTH__, __YEAR__, __MYCOMPANY_COUNTRY_ID__, __USER_ID__, ...
	// We do this only if var is a GET. If it is a POST, may be we want to post the text with vars as the setup text.
	if (! is_array($out) && empty($_POST[$paramname]) && empty($noreplace))
	{
		$maxloop=20; $loopnb=0;    // Protection against infinite loop
		while (preg_match('/__([A-Z0-9]+_?[A-Z0-9]+)__/i', $out, $reg) && ($loopnb < $maxloop))    // Detect '__ABCDEF__' as key 'ABCDEF' and '__ABC_DEF__' as key 'ABC_DEF'. Detection is also correct when 2 vars are side by side.
		{
				$loopnb++; $newout = '';

				if ($reg[1] == 'DAY')                { $tmp=dol_getdate(dol_now(), true); $newout = $tmp['mday']; }
				elseif ($reg[1] == 'MONTH')          { $tmp=dol_getdate(dol_now(), true); $newout = $tmp['mon'];  }
				elseif ($reg[1] == 'YEAR')           { $tmp=dol_getdate(dol_now(), true); $newout = $tmp['year']; }
				elseif ($reg[1] == 'PREVIOUS_DAY')   { $tmp=dol_getdate(dol_now(), true); $tmp2=dol_get_prev_day($tmp['mday'], $tmp['mon'], $tmp['year']); $newout = $tmp2['day']; }
				elseif ($reg[1] == 'PREVIOUS_MONTH') { $tmp=dol_getdate(dol_now(), true); $tmp2=dol_get_prev_month($tmp['mon'], $tmp['year']); $newout = $tmp2['month']; }
				elseif ($reg[1] == 'PREVIOUS_YEAR')  { $tmp=dol_getdate(dol_now(), true); $newout = ($tmp['year'] - 1); }
				elseif ($reg[1] == 'NEXT_DAY')       { $tmp=dol_getdate(dol_now(), true); $tmp2=dol_get_next_day($tmp['mday'], $tmp['mon'], $tmp['year']); $newout = $tmp2['day']; }
				elseif ($reg[1] == 'NEXT_MONTH')     { $tmp=dol_getdate(dol_now(), true); $tmp2=dol_get_next_month($tmp['mon'], $tmp['year']); $newout = $tmp2['month']; }
				elseif ($reg[1] == 'NEXT_YEAR')      { $tmp=dol_getdate(dol_now(), true); $newout = ($tmp['year'] + 1); }
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
				else $newout = '';     // Key not found, we replace with empty string
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
			if (! is_numeric($out)) { $out=''; }
			break;
		case 'intcomma':
			if (preg_match('/[^0-9,-]+/i', $out)) $out='';
			break;
		case 'alpha':
			if (! is_array($out))
			{
				$out=trim($out);
				// '"' is dangerous because param in url can close the href= or src= and add javascript functions.
				// '../' is dangerous because it allows dir transversals
				if (preg_match('/"/', $out)) $out='';
				elseif (preg_match('/\.\.\//', $out)) $out='';
			}
			break;
		case 'san_alpha':
			$out=filter_var($out, FILTER_SANITIZE_STRING);
			break;
		case 'aZ':
			if (! is_array($out))
			{
				$out=trim($out);
				if (preg_match('/[^a-z]+/i', $out)) $out='';
			}
			break;
		case 'aZ09':
			if (! is_array($out))
			{
				$out=trim($out);
				if (preg_match('/[^a-z0-9_\-\.]+/i', $out)) $out='';
			}
			break;
		case 'aZ09comma':		// great to sanitize sortfield or sortorder params that can be t.abc,t.def_gh
			if (! is_array($out))
			{
				$out=trim($out);
				if (preg_match('/[^a-z0-9_\-\.,]+/i', $out)) $out='';
			}
			break;
		case 'array':
			if (! is_array($out) || empty($out)) $out=array();
			break;
		case 'nohtml':		// Recommended for most scalar parameters
			$out=dol_string_nohtmltag($out, 0);
			break;
		case 'alphanohtml':	// Recommended for search parameters
			if (! is_array($out))
			{
				$out=trim($out);
				// '"' is dangerous because param in url can close the href= or src= and add javascript functions.
				// '../' is dangerous because it allows dir transversals
				if (preg_match('/"/', $out)) $out='';
				elseif (preg_match('/\.\.\//', $out)) $out='';
				$out=dol_string_nohtmltag($out);
			}
			break;
		case 'custom':
			if (empty($filter)) return 'BadFourthParameterForGETPOST';
			$out=filter_var($out, $filter, $options);
			break;
	}

	// Code for search criteria persistence.
	// Save data into session if key start with 'search_' or is 'smonth', 'syear', 'month', 'year'
	if (empty($method) || $method == 3 || $method == 4)
	{
		if (preg_match('/^search_/', $paramname) || in_array($paramname, array('sortorder','sortfield')))
		{
			//var_dump($paramname.' - '.$out.' '.$user->default_values[$relativepathstring]['filters'][$paramname]);

			// We save search key only if $out not empty that means:
			// - posted value not empty, or
			// - if posted value is empty and a default value exists that is not empty (it means we did a filter to an empty value when default was not).

			if ($out != '')		// $out = '0' or 'abc', it is a search criteria to keep
			{
				$user->lastsearch_values_tmp[$relativepathstring][$paramname]=$out;
			}
		}
	}

	return $out;
}


if (! function_exists('dol_getprefix'))
{
    /**
     *  Return a prefix to use for this Dolibarr instance, for session/cookie names or email id.
     *  The prefix for session is unique in a web context only and is unique for instance and avoid conflict
     *  between multi-instances, even when having two instances with same root dir or two instances in same virtual servers.
     *  The prefix for email is unique if MAIL_PREFIX_FOR_EMAIL_ID is set to a value, otherwise value may be same than other instance.
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
			if (! empty($conf->global->MAIL_PREFIX_FOR_EMAIL_ID))	// If MAIL_PREFIX_FOR_EMAIL_ID is set (a value initialized with a random value is recommended)
			{
				if ($conf->global->MAIL_PREFIX_FOR_EMAIL_ID != 'SERVER_NAME') return $conf->global->MAIL_PREFIX_FOR_EMAIL_ID;
				elseif (isset($_SERVER["SERVER_NAME"])) return $_SERVER["SERVER_NAME"];
			}
			return dol_hash(DOL_DOCUMENT_ROOT.DOL_URL_ROOT, '3');
		}

		if (isset($_SERVER["SERVER_NAME"]) && isset($_SERVER["DOCUMENT_ROOT"]))
		{
			return dol_hash($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT, '3');
			// Use this for a "readable" key
			//return dol_sanitizeFileName($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"].DOL_DOCUMENT_ROOT.DOL_URL_ROOT);
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
	global $conf,$langs,$user,$mysoc;   // Do not remove this. They must be defined for files we include. Other globals var must be retreived with $GLOBALS['var']

	$fullpath = dol_buildpath($relpath);

	if (!file_exists($fullpath)) {
		dol_syslog('functions::dol_include_once Tried to load unexisting file: '.$relpath, LOG_ERR);
		return false;
	}

	if (! empty($classname) && ! class_exists($classname)) {
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
 *  @return string								Full filesystem path (if path=0), Full url path (if mode=1)
 */
function dol_buildpath($path, $type = 0, $returnemptyifnotfound = 0)
{
	global $conf;

	$path=preg_replace('/^\//', '', $path);

	if (empty($type))	// For a filesystem path
	{
		$res = DOL_DOCUMENT_ROOT.'/'.$path;		// Standard default path
		foreach ($conf->file->dol_document_root as $key => $dirroot)	// ex: array(["main"]=>"/home/main/htdocs", ["alt0"]=>"/home/dirmod/htdocs", ...)
		{
			if ($key == 'main')
			{
				continue;
			}
			if (file_exists($dirroot.'/'.$path))
			{
				$res=$dirroot.'/'.$path;
				return $res;
			}
		}
		if ($returnemptyifnotfound)								// Not found into alternate dir
		{
			if ($returnemptyifnotfound == 1 || ! file_exists($res)) return '';
		}
	}
	else				// For an url path
	{
		// We try to get local path of file on filesystem from url
		// Note that trying to know if a file on disk exist by forging path on disk from url
		// works only for some web server and some setup. This is bugged when
		// using proxy, rewriting, virtual path, etc...
		$res='';
		if ($type == 1) $res = DOL_URL_ROOT.'/'.$path;			// Standard value
		if ($type == 2) $res = DOL_MAIN_URL_ROOT.'/'.$path;		// Standard value
		if ($type == 3) $res = DOL_URL_ROOT.'/'.$path;

		foreach ($conf->file->dol_document_root as $key => $dirroot)	// ex: array(["main"]=>"/home/main/htdocs", ["alt0"]=>"/home/dirmod/htdocs", ...)
		{
			if ($key == 'main')
			{
				if ($type == 3)
				{
					global $dolibarr_main_url_root;

					// Define $urlwithroot
					$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
					//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

					$res=(preg_match('/^http/i', $conf->file->dol_url_root[$key])?'':$urlwithroot).'/'.$path;     // Test on start with http is for old conf syntax
				}
				continue;
			}
			preg_match('/^([^\?]+(\.css\.php|\.css|\.js\.php|\.js|\.png|\.jpg|\.php)?)/i', $path, $regs);    // Take part before '?'
			if (! empty($regs[1]))
			{
				//print $key.'-'.$dirroot.'/'.$path.'-'.$conf->file->dol_url_root[$type].'<br>'."\n";
				if (file_exists($dirroot.'/'.$regs[1]))
				{
					if ($type == 1)
					{
						$res=(preg_match('/^http/i', $conf->file->dol_url_root[$key])?'':DOL_URL_ROOT).$conf->file->dol_url_root[$key].'/'.$path;
					}
					if ($type == 2)
					{
						$res=(preg_match('/^http/i', $conf->file->dol_url_root[$key])?'':DOL_MAIN_URL_ROOT).$conf->file->dol_url_root[$key].'/'.$path;
					}
					if ($type == 3)
					{
						global $dolibarr_main_url_root;

						// Define $urlwithroot
						$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
						//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

						$res=(preg_match('/^http/i', $conf->file->dol_url_root[$key])?'':$urlwithroot).$conf->file->dol_url_root[$key].'/'.$path;     // Test on start with http is for old conf syntax
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
 *  With native = 0: Property that are reference are also new object (true clone). This means $this->db is not valid.
 *  With native = 1: Use PHP clone. Property that are reference are same pointer. This means $this->db is still valid.
 *
 * 	@param	object	$object		Object to clone
 *  @param	int		$native		Native method or true method
 *	@return object				Object clone
 *  @see https://php.net/manual/language.oop5.cloning.php
 */
function dol_clone($object, $native = 0)
{
	//dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	if (empty($native))
	{
		$myclone=unserialize(serialize($object));
	}
	else
	{
		$myclone = clone $object;     // PHP clone is a shallow copy only, not a real clone, so properties of references will keep references (refer to the same target/variable)
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
 * 	@see        	dol_string_nospecial, dol_string_unaccent, dol_sanitizePathName
 */
function dol_sanitizeFileName($str, $newstr = '_', $unaccent = 1)
{
	$filesystem_forbidden_chars = array('<','>','/','\\','?','*','|','"','°');
	return dol_string_nospecial($unaccent?dol_string_unaccent($str):$str, $newstr, $filesystem_forbidden_chars);
}

/**
 *	Clean a string to use it as a path name
 *
 *	@param	string	$str            String to clean
 * 	@param	string	$newstr			String to replace bad chars with
 *  @param	int	    $unaccent		1=Remove also accent (default), 0 do not remove them
 *	@return string          		String cleaned (a-zA-Z_)
 *
 * 	@see        	dol_string_nospecial, dol_string_unaccent, dol_sanitizeFileName
 */
function dol_sanitizePathName($str, $newstr = '_', $unaccent = 1)
{
	$filesystem_forbidden_chars = array('<','>','?','*','|','"','°');
	return dol_string_nospecial($unaccent?dol_string_unaccent($str):$str, $newstr, $filesystem_forbidden_chars);
}

/**
 *	Clean a string from all accent characters to be used as ref, login or by dol_sanitizeFileName
 *
 *	@param	string	$str			String to clean
 *	@return string   	       		Cleaned string
 *
 * 	@see    		dol_sanitizeFilename, dol_string_nospecial
 */
function dol_string_unaccent($str)
{
	if (utf8_check($str))
	{
		// See http://www.utf8-chartable.de/
		$string = rawurlencode($str);
		$replacements = array(
		'%C3%80' => 'A','%C3%81' => 'A','%C3%82' => 'A','%C3%83' => 'A','%C3%84' => 'A','%C3%85' => 'A',
		'%C3%88' => 'E','%C3%89' => 'E','%C3%8A' => 'E','%C3%8B' => 'E',
		'%C3%8C' => 'I','%C3%8D' => 'I','%C3%8E' => 'I','%C3%8F' => 'I',
		'%C3%92' => 'O','%C3%93' => 'O','%C3%94' => 'O','%C3%95' => 'O','%C3%96' => 'O',
		'%C3%99' => 'U','%C3%9A' => 'U','%C3%9B' => 'U','%C3%9C' => 'U',
		'%C3%A0' => 'a','%C3%A1' => 'a','%C3%A2' => 'a','%C3%A3' => 'a','%C3%A4' => 'a','%C3%A5' => 'a',
		'%C3%A7' => 'c',
		'%C3%A8' => 'e','%C3%A9' => 'e','%C3%AA' => 'e','%C3%AB' => 'e',
		'%C3%AC' => 'i','%C3%AD' => 'i','%C3%AE' => 'i','%C3%AF' => 'i',
		'%C3%B1' => 'n',
		'%C3%B2' => 'o','%C3%B3' => 'o','%C3%B4' => 'o','%C3%B5' => 'o','%C3%B6' => 'o',
		'%C3%B9' => 'u','%C3%BA' => 'u','%C3%BB' => 'u','%C3%BC' => 'u',
		'%C3%BF' => 'y'
		);
		$string=strtr($string, $replacements);
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
 * 	@see    		dol_sanitizeFilename, dol_string_unaccent
 */
function dol_string_nospecial($str, $newstr = '_', $badcharstoreplace = '')
{
	$forbidden_chars_to_replace=array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ",", ";", "=", '°');  // more complete than dol_sanitizeFileName
	$forbidden_chars_to_remove=array();
	if (is_array($badcharstoreplace)) $forbidden_chars_to_replace=$badcharstoreplace;
	//$forbidden_chars_to_remove=array("(",")");

	return str_replace($forbidden_chars_to_replace, $newstr, str_replace($forbidden_chars_to_remove, "", $str));
}


/**
 * Encode string for xml usage
 *
 * @param 	string	$string		String to encode
 * @return	string				String encoded
 */
function dolEscapeXML($string)
{
	return strtr($string, array('\''=>'&apos;','"'=>'&quot;','&'=>'&amp;','<'=>'&lt;','>'=>'&gt;'));
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
	$substitjs=array("&#039;"=>"\\'","\r"=>'\\r');
	//$substitjs['</']='<\/';	// We removed this. Should be useless.
	if (empty($noescapebackslashn)) { $substitjs["\n"]='\\n'; $substitjs['\\']='\\\\'; }
	if (empty($mode)) { $substitjs["'"]="\\'"; $substitjs['"']="\\'"; }
	elseif ($mode == 1) $substitjs["'"]="\\'";
	elseif ($mode == 2) { $substitjs['"']='\\"'; }
	elseif ($mode == 3) { $substitjs["'"]="\\'"; $substitjs['"']="\\\""; }
	return strtr($stringtoescape, $substitjs);
}


/**
 *  Returns text escaped for inclusion in HTML alt or title tags, or into values of HTML input fields.
 *
 *  @param      string		$stringtoescape		String to escape
 *  @param		int			$keepb				1=Preserve b tags (otherwise, remove them)
 *  @param      int         $keepn              1=Preserve \r\n strings (otherwise, replace them with escaped value)
 *  @return     string     				 		Escaped string
 *  @see		dol_string_nohtmltag, dol_string_nospecial, dol_string_unaccent
 */
function dol_escape_htmltag($stringtoescape, $keepb = 0, $keepn = 0)
{
	// escape quotes and backslashes, newlines, etc.
	$tmp=html_entity_decode($stringtoescape, ENT_COMPAT, 'UTF-8');		// TODO Use htmlspecialchars_decode instead, that make only required change for html tags
	if (! $keepb) $tmp=strtr($tmp, array("<b>"=>'','</b>'=>''));
	if (! $keepn) $tmp=strtr($tmp, array("\r"=>'\\r',"\n"=>'\\n'));
	return htmlentities($tmp, ENT_COMPAT, 'UTF-8');						// TODO Use htmlspecialchars instead, that make only required change for html tags
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
 *  @param	string		$restricttologhandler	Output log only for this log handler
 *  @return	void
 */
function dol_syslog($message, $level = LOG_INFO, $ident = 0, $suffixinfilename = '', $restricttologhandler = '')
{
	global $conf, $user;

	// If syslog module enabled
	if (empty($conf->syslog->enabled)) return;

	if ($ident < 0)
	{
		foreach ($conf->loghandlers as $loghandlerinstance)
		{
			$loghandlerinstance->setIdent($ident);
		}
	}

	if (! empty($message))
	{
		// Test log level
		$logLevels = array(LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG);
		if (!in_array($level, $logLevels, true))
		{
			throw new Exception('Incorrect log level');
		}
		if ($level > $conf->global->SYSLOG_LEVEL) return;

		$message = preg_replace('/password=\'[^\']*\'/', 'password=\'hidden\'', $message);	// protection to avoid to have value of password in log

		// If adding log inside HTML page is required
		if (! empty($_REQUEST['logtohtml']) && (! empty($conf->global->MAIN_ENABLE_LOG_TO_HTML) || ! empty($conf->global->MAIN_LOGTOHTML)))   // MAIN_LOGTOHTML kept for backward compatibility
		{
			$conf->logbuffer[] = dol_print_date(time(), "%Y-%m-%d %H:%M:%S")." ".$message;
		}

		//TODO: Remove this. MAIN_ENABLE_LOG_INLINE_HTML should be deprecated and use a log handler dedicated to HTML output
		// If html log tag enabled and url parameter log defined, we show output log on HTML comments
		if (! empty($conf->global->MAIN_ENABLE_LOG_INLINE_HTML) && ! empty($_GET["log"]))
		{
			print "\n\n<!-- Log start\n";
			print $message."\n";
			print "Log end -->\n";
		}

		$data = array(
			'message' => $message,
			'script' => (isset($_SERVER['PHP_SELF'])? basename($_SERVER['PHP_SELF'], '.php') : false),
			'level' => $level,
			'user' => ((is_object($user) && $user->id) ? $user->login : false),
			'ip' => false
		);

		// This is when server run behind a reverse proxy
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $data['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'].(empty($_SERVER["REMOTE_ADDR"])?'':'->'.$_SERVER['REMOTE_ADDR']);
		// This is when server run normally on a server
		elseif (! empty($_SERVER["REMOTE_ADDR"])) $data['ip'] = $_SERVER['REMOTE_ADDR'];
		// This is when PHP session is ran inside a web server but not inside a client request (example: init code of apache)
		elseif (! empty($_SERVER['SERVER_ADDR'])) $data['ip'] = $_SERVER['SERVER_ADDR'];
		// This is when PHP session is ran outside a web server, like from Windows command line (Not always defined, but useful if OS defined it).
		elseif (! empty($_SERVER['COMPUTERNAME'])) $data['ip'] = $_SERVER['COMPUTERNAME'].(empty($_SERVER['USERNAME'])?'':'@'.$_SERVER['USERNAME']);
		// This is when PHP session is ran outside a web server, like from Linux command line (Not always defined, but usefull if OS defined it).
		elseif (! empty($_SERVER['LOGNAME'])) $data['ip'] = '???@'.$_SERVER['LOGNAME'];
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
 *	@param  int		$notab				-1 or 0=Add tab header, 1=no tab header. If you set this to 1, using dol_fiche_end() to close tab is not required.
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
 *	@param  int		$notab				-1 or 0=Add tab header, 1=no tab header. If you set this to 1, using dol_fiche_end() to close tab is not required.
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodyle/img/myimg.png',1) for $picto.
 *  @param	string	$morehtmlright		Add more html content on right of tabs title
 *  @param	string	$morecss			More Css
 * 	@return	string
 */
function dol_get_fiche_head($links = array(), $active = '', $title = '', $notab = 0, $picto = '', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '')
{
	global $conf, $langs, $hookmanager;

	$out="\n".'<div class="tabs" data-role="controlgroup" data-type="horizontal">'."\n";

	if ($morehtmlright) $out.='<div class="inline-block floatright tabsElem">'.$morehtmlright.'</div>';	// Output right area first so when space is missing, text is in front of tabs and not under.

	// Show title
	$showtitle=1;
	if (! empty($conf->dol_optimize_smallscreen)) $showtitle=0;
	if (! empty($title) && $showtitle)
	{
		$limittitle=30;
		$out.='<a class="tabTitle">';
		if ($picto) $out.=img_picto($title, ($pictoisfullpath?'':'object_').$picto, '', $pictoisfullpath).' ';
		$out.='<span class="tabTitleText">'.dol_trunc($title, $limittitle).'</span>';
		$out.='</a>';
	}

	// Define max of key (max may be higher than sizeof because of hole due to module disabling some tabs).
	$maxkey=-1;
	if (is_array($links) && ! empty($links))
	{
		$keys=array_keys($links);
		if (count($keys)) $maxkey=max($keys);
	}

	if (! empty($conf->dol_optimize_smallscreen)) $conf->global->MAIN_MAXTABS_IN_CARD=2;

	// Show tabs
	$bactive=false;
	// if =0 we don't use the feature
	$limittoshow=(empty($conf->global->MAIN_MAXTABS_IN_CARD)?99:$conf->global->MAIN_MAXTABS_IN_CARD);
	$displaytab=0;
	$nbintab=0;
	$popuptab=0; $outmore='';
	for ($i = 0 ; $i <= $maxkey ; $i++)
	{
		if ((is_numeric($active) && $i == $active) || (! empty($links[$i][2]) && ! is_numeric($active) && $active == $links[$i][2]))
		{
			// If active tab is already present
			if ($i >= $limittoshow) $limittoshow--;
		}
	}

	for ($i = 0 ; $i <= $maxkey ; $i++)
	{
		if ((is_numeric($active) && $i == $active) || (! empty($links[$i][2]) && ! is_numeric($active) && $active == $links[$i][2]))
		{
			$isactive=true;
			$bactive=true;
		}
		else
		{
			$isactive=false;
		}

		if ($i < $limittoshow || $isactive)
		{
			$out.='<div class="inline-block tabsElem'.($isactive ? ' tabsElemActive' : '').((! $isactive && ! empty($conf->global->MAIN_HIDE_INACTIVETAB_ON_PRINT))?' hideonprint':'').'"><!-- id tab = '.(empty($links[$i][2])?'':$links[$i][2]).' -->';
			if (isset($links[$i][2]) && $links[$i][2] == 'image')
			{
				if (!empty($links[$i][0]))
				{
					$out.='<a class="tabimage'.($morecss?' '.$morecss:'').'" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				}
				else
				{
					$out.='<span class="tabspan">'.$links[$i][1].'</span>'."\n";
				}
			}
			elseif (! empty($links[$i][1]))
			{
				//print "x $i $active ".$links[$i][2]." z";
				if ($isactive)
				{
					$out.='<a'.(! empty($links[$i][2])?' id="'.$links[$i][2].'"':'').' class="tabactive tab inline-block'.($morecss?' '.$morecss:'').'" href="'.$links[$i][0].'">';
					$out.=$links[$i][1];
					$out.='</a>'."\n";
				}
				else
				{
					$out.='<a'.(! empty($links[$i][2])?' id="'.$links[$i][2].'"':'').' class="tabunactive tab inline-block'.($morecss?' '.$morecss:'').'" href="'.$links[$i][0].'">';
					$out.=$links[$i][1];
					$out.='</a>'."\n";
				}
			}
			$out.='</div>';
		}
		else
		{
			// The popup with the other tabs
			if (! $popuptab)
			{
				$popuptab=1;
				$outmore.='<div class="popuptabset wordwrap">';	// The css used to hide/show popup
			}
			$outmore.='<div class="popuptab wordwrap" style="display:inherit;">';
			if (isset($links[$i][2]) && $links[$i][2] == 'image')
			{
				if (!empty($links[$i][0]))
					$outmore.='<a class="tabimage'.($morecss?' '.$morecss:'').'" href="'.$links[$i][0].'">'.$links[$i][1].'</a>'."\n";
				else
					$outmore.='<span class="tabspan">'.$links[$i][1].'</span>'."\n";
			}
			elseif (! empty($links[$i][1]))
			{
				$outmore.='<a'.(! empty($links[$i][2])?' id="'.$links[$i][2].'"':'').' class="wordwrap inline-block'.($morecss?' '.$morecss:'').'" href="'.$links[$i][0].'">';
				$outmore.=preg_replace('/([a-z])\/([a-z])/i', '\\1 / \\2', $links[$i][1]);	// Replace x/y with x / y to allow wrap on long composed texts.
				$outmore.='</a>'."\n";
			}
			$outmore.='</div>';

			$nbintab++;
		}
		$displaytab=$i;
	}
	if ($popuptab) $outmore.='</div>';

	if ($displaytab > $limittoshow)
	{
		$left=($langs->trans("DIRECTION") == 'rtl'?'right':'left');
		$right=($langs->trans("DIRECTION") == 'rtl'?'left':'right');

		$tabsname=str_replace("@", "", $picto);
		$out.='<div id="moretabs'.$tabsname.'" class="inline-block tabsElem">';
		$out.='<a href="#" class="tab moretab inline-block tabunactive reposition">'.$langs->trans("More").'... ('.$nbintab.')</a>';
		$out.='<div id="moretabsList'.$tabsname.'" style="position: absolute; '.$left.': -999em; text-align: '.$left.'; margin:0px; padding:2px">';
		$out.=$outmore;
		$out.='</div>';
		$out.='<div></div>';
		$out.="</div>\n";

		$out.="<script>";
		$out.="$('#moretabs".$tabsname."').mouseenter( function() { console.log('mouseenter ".$left."'); $('#moretabsList".$tabsname."').css('".$left."','auto');});";
		$out.="$('#moretabs".$tabsname."').mouseleave( function() { console.log('mouseleave ".$left."'); $('#moretabsList".$tabsname."').css('".$left."','-999em');});";
		$out.="</script>";
	}

	$out.="</div>\n";

	if (! $notab || $notab == -1) $out.="\n".'<div class="tabBar'.($notab == -1 ? '' : ' tabBarWithBottom').'">'."\n";

	$parameters=array('tabname' => $active, 'out' => $out);
	$reshook=$hookmanager->executeHooks('printTabsHead', $parameters);	// This hook usage is called just before output the head of tabs. Take also a look at "completeTabsHead"
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
	if (! $notab || $notab == -1) return "\n</div>\n";
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

	$maxvisiblephotos=1;
	$showimage=1;
	$entity=(empty($object->entity)?$conf->entity:$object->entity);
	$showbarcode=empty($conf->barcode->enabled)?0:($object->barcode?1:0);
	if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;
	$modulepart='unknown';

	if ($object->element == 'societe')         $modulepart='societe';
	if ($object->element == 'contact')         $modulepart='contact';
	if ($object->element == 'member')          $modulepart='memberphoto';
	if ($object->element == 'user')            $modulepart='userphoto';
	if ($object->element == 'product')         $modulepart='product';

	if (class_exists("Imagick"))
	{
		if ($object->element == 'propal')            $modulepart='propal';
		if ($object->element == 'commande')          $modulepart='commande';
		if ($object->element == 'facture')           $modulepart='facture';
		if ($object->element == 'fichinter')         $modulepart='ficheinter';
		if ($object->element == 'contrat')           $modulepart='contract';
		if ($object->element == 'supplier_proposal') $modulepart='supplier_proposal';
		if ($object->element == 'order_supplier')    $modulepart='supplier_order';
		if ($object->element == 'invoice_supplier')  $modulepart='supplier_invoice';
		if ($object->element == 'expensereport')     $modulepart='expensereport';
	}

	if ($object->element == 'product')
	{
		$width=80; $cssclass='photoref';
		$showimage=$object->is_photo_available($conf->product->multidir_output[$entity]);
		$maxvisiblephotos=(isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO)?$conf->global->PRODUCT_MAX_VISIBLE_PHOTO:5);
		if ($conf->browser->layout == 'phone') $maxvisiblephotos=1;
		if ($showimage) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos('product', $conf->product->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, $width, 0).'</div>';
		else
		{
			if (!empty($conf->global->PRODUCT_NODISPLAYIFNOPHOTO)) {
				$nophoto='';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			//elseif ($conf->browser->layout != 'phone') {    // Show no photo link
				$nophoto='/public/theme/common/nophoto.png';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo"'.($width?' style="width: '.$width.'px"':'').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			//}
		}
	}
	elseif ($object->element == 'ticket')
	{
		$width=80; $cssclass='photoref';
		$showimage=$object->is_photo_available($conf->ticket->multidir_output[$entity].'/'.$object->track_id);
		$maxvisiblephotos=(isset($conf->global->TICKETSUP_MAX_VISIBLE_PHOTO)?$conf->global->TICKETSUP_MAX_VISIBLE_PHOTO:2);
		if ($conf->browser->layout == 'phone') $maxvisiblephotos=1;
		if ($showimage) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos('ticket', $conf->ticket->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, $width, 0).'</div>';
		else
		{
			if (!empty($conf->global->TICKETSUP_NODISPLAYIFNOPHOTO)) {
				$nophoto='';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			//elseif ($conf->browser->layout != 'phone') {    // Show no photo link
			$nophoto='/public/theme/common/nophoto.png';
			$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' style="width: '.$width.'px"':'').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			//}
		}
	}
	else
	{
		if ($showimage)
		{
			if ($modulepart != 'unknown')
			{
				$phototoshow='';
				// Check if a preview file is available
				if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick"))
				{
					$objectref = dol_sanitizeFileName($object->ref);
					$dir_output = (empty($conf->$modulepart->multidir_output[$entity]) ? $conf->$modulepart->dir_output : $conf->$modulepart->multidir_output[$entity]) . "/";
					if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice')))
					{
						$subdir = get_exdir($object->id, 2, 0, 1, $object, $modulepart);
						$subdir.= ((! empty($subdir) && ! preg_match('/\/$/', $subdir))?'/':'').$objectref;		// the objectref dir is not included into get_exdir when used with level=2, so we add it at end
					}
					else
					{
						$subdir = get_exdir($object->id, 0, 0, 1, $object, $modulepart);
					}
					if (empty($subdir)) $subdir = 'errorgettingsubdirofobject';	// Protection to avoid to return empty path

					$filepath = $dir_output . $subdir . "/";

					$file = $filepath . $objectref . ".pdf";
					$relativepath = $subdir.'/'.$objectref.'.pdf';

					// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
					$fileimage = $file.'_preview.png';              // If PDF has 1 page
					$fileimagebis = $file.'_preview-0.png';         // If PDF has more than one page
					$relativepathimage = $relativepath.'_preview.png';

					// Si fichier PDF existe
					if (file_exists($file))
					{
						$encfile = urlencode($file);
						// Conversion du PDF en image png si fichier png non existant
						if ( (! file_exists($fileimage) || (filemtime($fileimage) < filemtime($file)))
						  && (! file_exists($fileimagebis) || (filemtime($fileimagebis) < filemtime($file)))
						   )
						{
							if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS))		// If you experienc trouble with pdf thumb generation and imagick, you can disable here.
							{
								include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
								$ret = dol_convert_file($file, 'png', $fileimage);
								if ($ret < 0) $error++;
							}
						}

						$heightforphotref=70;
						if (! empty($conf->dol_optimize_smallscreen)) $heightforphotref=60;
						// Si fichier png PDF d'1 page trouve
						if (file_exists($fileimage))
						{
							$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
							$phototoshow.= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
							$phototoshow.= '</div></div>';
						}
						// Si fichier png PDF de plus d'1 page trouve
						elseif (file_exists($fileimagebis))
						{
							$preview = preg_replace('/\.png/', '', $relativepathimage) . "-0.png";
							$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
							$phototoshow.= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($preview).'"><p>';
							$phototoshow.= '</div></div>';
						}
					}
				}
				elseif (! $phototoshow)
				{
					$phototoshow = $form->showphoto($modulepart, $object, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);
				}

				if ($phototoshow)
				{
					$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
					$morehtmlleft.=$phototoshow;
					$morehtmlleft.='</div>';
				}
			}

			if (! $phototoshow)      // Show No photo link (picto of pbject)
			{
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
				if ($object->element == 'action')
				{
					$width=80;
					$cssclass='photorefcenter';
					$nophoto=img_picto('', 'title_agenda', '', false, 1);
				}
				else
				{
					$width=14; $cssclass='photorefcenter';
					$picto = $object->picto;
					if ($object->element == 'project' && ! $object->public) $picto = 'project'; // instead of projectpub
					$nophoto=img_picto('', 'object_'.$picto, '', false, 1);
				}
				$morehtmlleft.='<!-- No photo to show -->';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo"'.($width?' style="width: '.$width.'px"':'').' src="'.$nophoto.'"></div></div>';

				$morehtmlleft.='</div>';
			}
		}
	}

	if ($showbarcode) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object).'</div>';

	if ($object->element == 'societe')
	{
		if (! empty($conf->use_javascript_ajax) && $user->rights->societe->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE))
		{
		   	$morehtmlstatus.=ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
		}
		else {
			$morehtmlstatus.=$object->getLibStatut(6);
		}
	}
	elseif ($object->element == 'product')
	{
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
		if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			$morehtmlstatus.=ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
		} else {
			$morehtmlstatus.='<span class="statusrefsell">'.$object->getLibStatut(5, 0).'</span>';
		}
		$morehtmlstatus.=' &nbsp; ';
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
		if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			$morehtmlstatus.=ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
		} else {
			$morehtmlstatus.='<span class="statusrefbuy">'.$object->getLibStatut(5, 1).'</span>';
		}
	}
	elseif (in_array($object->element, array('facture', 'invoice', 'invoice_supplier', 'chargesociales', 'loan')))
	{
		$tmptxt=$object->getLibStatut(6, $object->totalpaye);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5, $object->totalpaye);
		$morehtmlstatus.=$tmptxt;
	}
	elseif ($object->element == 'contrat' || $object->element == 'contract')
	{
		if ($object->statut == 0) $morehtmlstatus.=$object->getLibStatut(5);
		else $morehtmlstatus.=$object->getLibStatut(4);
	}
	elseif ($object->element == 'facturerec')
	{
		if ($object->frequency == 0) $morehtmlstatus.=$object->getLibStatut(2);
		else $morehtmlstatus.=$object->getLibStatut(5);
	}
	elseif ($object->element == 'project_task')
	{
		$object->fk_statut = 1;
		if ($object->progress > 0) $object->fk_statut = 2;
		if ($object->progress >= 100) $object->fk_statut = 3;
		$tmptxt=$object->getLibStatut(5);
		$morehtmlstatus.=$tmptxt;		// No status on task
	}
	else { // Generic case
		$tmptxt=$object->getLibStatut(6);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5);
		$morehtmlstatus.=$tmptxt;
	}

	// Add if object was dispatched "into accountancy"
	if (! empty($conf->accounting->enabled) && in_array($object->element, array('bank', 'facture', 'invoice', 'invoice_supplier', 'expensereport')))
	{
		if (method_exists($object, 'getVentilExportCompta'))
		{
			$accounted = $object->getVentilExportCompta();
			$langs->load("accountancy");
			$morehtmlstatus.='</div><div class="statusref statusrefbis">'.($accounted > 0 ? $langs->trans("Accounted") : '<span class="opacitymedium">'.$langs->trans("NotYetAccounted").'</span>');
		}
	}

	// Add alias for thirdparty
	if (! empty($object->name_alias)) $morehtmlref.='<div class="refidno">'.$object->name_alias.'</div>';

	// Add label
	if ($object->element == 'product' || $object->element == 'bank_account' || $object->element == 'project_task')
	{
		if (! empty($object->label)) $morehtmlref.='<div class="refidno">'.$object->label.'</div>';
	}

	if (method_exists($object, 'getBannerAddress') && $object->element != 'product' && $object->element != 'bookmark' && $object->element != 'ecm_directories' && $object->element != 'ecm_files')
	{
		$morehtmlref.='<div class="refidno">';
		$morehtmlref.=$object->getBannerAddress('refaddress', $object);
		$morehtmlref.='</div>';
	}
	if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && in_array($object->element, array('societe', 'contact', 'member', 'product')))
	{
		$morehtmlref.='<div style="clear: both;"></div><div class="refidno">';
		$morehtmlref.=$langs->trans("TechnicalID").': '.$object->id;
		$morehtmlref.='</div>';
	}

	print '<div class="'.($onlybanner?'arearefnobottom ':'arearef ').'heightref valignmiddle centpercent">';
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
	global $conf, $langs;
	$ret='';
	if ($fieldrequired) $ret.='<span class="fieldrequired">';
	if (($conf->dol_use_jmobile != 4)) $ret.='<label for="'.$fieldkey.'">';
	$ret.=$langs->trans($langkey);
	if (($conf->dol_use_jmobile != 4)) $ret.='</label>';
	if ($fieldrequired) $ret.='</span>';
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
	$ret=' '.$bc[$var];
	if ($moreclass) $ret=preg_replace('/class=\"/', 'class="'.$moreclass.' ', $ret);
	return $ret;
}

/**
 *      Return a formated address (part address/zip/town/state) according to country rules
 *
 *      @param  Object		$object			A company or contact object
 * 	    @param	int			$withcountry		1=Add country into address string
 *      @param	string		$sep				Separator to use to build string
 *      @param	Translate	$outputlangs		Object lang that contains language for text translation.
 *      @param	int		$mode		0=Standard output, 1=Remove address
 *      @return string						Formated string
 *      @see dol_print_address
 */
function dol_format_address($object, $withcountry = 0, $sep = "\n", $outputlangs = '', $mode = 0)
{
	global $conf,$langs;

	$ret='';
	$countriesusingstate=array('AU','CA','US','IN','GB','ES','UK','TR');    // See also MAIN_FORCE_STATE_INTO_ADDRESS

	// Address
	if (empty($mode)) {
		$ret .= $object->address;
	}
	// Zip/Town/State
	if (in_array($object->country_code, array('AU', 'CA', 'US')) || ! empty($conf->global->MAIN_FORCE_STATE_INTO_ADDRESS))   	// US: title firstname name \n address lines \n town, state, zip \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->town;
		if ($object->state)
		{
			$ret.=($ret?", ":'').$object->state;
		}
		if ($object->zip) $ret .= ($ret?", ":'').$object->zip;
	}
	elseif (in_array($object->country_code, array('GB','UK'))) // UK: title firstname name \n address lines \n town state \n zip \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->town;
		if ($object->state)
		{
			$ret.=($ret?", ":'').$object->state;
		}
		if ($object->zip) $ret .= ($ret ? $sep : '' ).$object->zip;
	}
	elseif (in_array($object->country_code, array('ES','TR'))) // ES: title firstname name \n address lines \n zip town \n state \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->zip;
		$ret .= ($object->town?(($object->zip?' ':'').$object->town):'');
		if ($object->state)
		{
			$ret.="\n".$object->state;
		}
	}
	elseif (in_array($object->country_code, array('IT'))) // IT: tile firstname name\n address lines \n zip (Code Departement) \n country
	{
		$ret .= ($ret ? $sep : '' ).$object->zip;
		$ret .= ($object->town?(($object->zip?' ':'').$object->town):'');
		$ret .= ($object->departement_id?(' ('.($object->departement_id).')'):'');
	}
	else                                        		// Other: title firstname name \n address lines \n zip town \n country
	{
		$ret .= $object->zip ? (($ret ? $sep : '' ).$object->zip) : '';
		$ret .= ($object->town?(($object->zip?' ':($ret ? $sep : '' )).$object->town):'');
		if ($object->state && in_array($object->country_code, $countriesusingstate))
		{
			$ret.=($ret?", ":'').$object->state;
		}
	}
	if (! is_object($outputlangs)) $outputlangs=$langs;
	if ($withcountry)
	{
		$langs->load("dict");
		$ret.=($object->country_code?($ret?$sep:'').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->country_code)):'');
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
		return ($is_gmt)? @gmstrftime($fmt, $ts): @strftime($fmt, $ts);
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
 *  @see        dol_mktime, dol_stringtotime, dol_getdate
 */
function dol_print_date($time, $format = '', $tzoutput = 'tzserver', $outputlangs = '', $encodetooutput = false)
{
	global $conf,$langs;

	// Clean parameters
	$to_gmt=false;
	$offsettz=$offsetdst=0;
	if ($tzoutput)
	{
		$to_gmt=true;	// For backward compatibility
		if (is_string($tzoutput))
		{
			if ($tzoutput == 'tzserver')
			{
				$to_gmt=false;
				$offsettzstring=@date_default_timezone_get();		// Example 'Europe/Berlin' or 'Indian/Reunion'
				$offsettz=0;
				$offsetdst=0;
			}
			elseif ($tzoutput == 'tzuser' || $tzoutput == 'tzuserrel')
			{
				$to_gmt=true;
				$offsettzstring=(empty($_SESSION['dol_tz_string'])?'UTC':$_SESSION['dol_tz_string']);	// Example 'Europe/Berlin' or 'Indian/Reunion'
				$offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;		// Will not be used anymore
				$offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;	// Will not be used anymore
			}
		}
	}
	if (! is_object($outputlangs)) $outputlangs=$langs;
	if (! $format) $format='daytextshort';
	$reduceformat=(! empty($conf->dol_optimize_smallscreen) && in_array($format, array('day','dayhour')))?1:0;
	$formatwithoutreduce = preg_replace('/reduceformat/', '', $format);
	if ($formatwithoutreduce != $format) { $format = $formatwithoutreduce; $reduceformat=1; }  // so format 'dayreduceformat' is processed like day

	// Change predefined format into computer format. If found translation in lang file we use it, otherwise we use default.
	// TODO Add format daysmallyear and dayhoursmallyear
	if ($format == 'day')				$format=($outputlangs->trans("FormatDateShort")!="FormatDateShort"?$outputlangs->trans("FormatDateShort"):$conf->format_date_short);
	elseif ($format == 'hour')			$format=($outputlangs->trans("FormatHourShort")!="FormatHourShort"?$outputlangs->trans("FormatHourShort"):$conf->format_hour_short);
	elseif ($format == 'hourduration')	$format=($outputlangs->trans("FormatHourShortDuration")!="FormatHourShortDuration"?$outputlangs->trans("FormatHourShortDuration"):$conf->format_hour_short_duration);
	elseif ($format == 'daytext')			 $format=($outputlangs->trans("FormatDateText")!="FormatDateText"?$outputlangs->trans("FormatDateText"):$conf->format_date_text);
	elseif ($format == 'daytextshort')	$format=($outputlangs->trans("FormatDateTextShort")!="FormatDateTextShort"?$outputlangs->trans("FormatDateTextShort"):$conf->format_date_text_short);
	elseif ($format == 'dayhour')			 $format=($outputlangs->trans("FormatDateHourShort")!="FormatDateHourShort"?$outputlangs->trans("FormatDateHourShort"):$conf->format_date_hour_short);
	elseif ($format == 'dayhoursec')		 $format=($outputlangs->trans("FormatDateHourSecShort")!="FormatDateHourSecShort"?$outputlangs->trans("FormatDateHourSecShort"):$conf->format_date_hour_sec_short);
	elseif ($format == 'dayhourtext')		 $format=($outputlangs->trans("FormatDateHourText")!="FormatDateHourText"?$outputlangs->trans("FormatDateHourText"):$conf->format_date_hour_text);
	elseif ($format == 'dayhourtextshort') $format=($outputlangs->trans("FormatDateHourTextShort")!="FormatDateHourTextShort"?$outputlangs->trans("FormatDateHourTextShort"):$conf->format_date_hour_text_short);
	// Format not sensitive to language
	elseif ($format == 'dayhourlog')		 $format='%Y%m%d%H%M%S';
	elseif ($format == 'dayhourldap')		 $format='%Y%m%d%H%M%SZ';
	elseif ($format == 'dayhourxcard')	$format='%Y%m%dT%H%M%SZ';
	elseif ($format == 'dayxcard')	 	$format='%Y%m%d';
	elseif ($format == 'dayrfc')			 $format='%Y-%m-%d';             // DATE_RFC3339
	elseif ($format == 'dayhourrfc')		 $format='%Y-%m-%dT%H:%M:%SZ';   // DATETIME RFC3339
	elseif ($format == 'standard')		$format='%Y-%m-%d %H:%M:%S';

	if ($reduceformat)
	{
		$format=str_replace('%Y', '%y', $format);
		$format=str_replace('yyyy', 'yy', $format);
	}

	// If date undefined or "", we return ""
	if (dol_strlen($time) == 0) return '';		// $time=0 allowed (it means 01/01/1970 00:00:00)

	// Clean format
	if (preg_match('/%b/i', $format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format=str_replace('%b', '__b__', $format);
		$format=str_replace('%B', '__B__', $format);
	}
	if (preg_match('/%a/i', $format))		// There is some text to translate
	{
		// We inhibate translation to text made by strftime functions. We will use trans instead later.
		$format=str_replace('%a', '__a__', $format);
		$format=str_replace('%A', '__A__', $format);
	}

	// Analyze date
	if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+) ?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i', $time, $reg)
	|| preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])([0-9][0-9])$/i', $time, $reg))	// Deprecated. Ex: 1970-01-01, 1970-01-01 01:00:00, 19700101010000
	{
		// TODO Remove this.
		// This part of code should not be used.
		dol_syslog("Functions.lib::dol_print_date function call with deprecated value of time in page ".$_SERVER["PHP_SELF"], LOG_ERR);
		// Date has format 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS' or 'YYYYMMDDHHMMSS'
		$syear	= (! empty($reg[1]) ? $reg[1] : '');
		$smonth	= (! empty($reg[2]) ? $reg[2] : '');
		$sday	= (! empty($reg[3]) ? $reg[3] : '');
		$shour	= (! empty($reg[4]) ? $reg[4] : '');
		$smin	= (! empty($reg[5]) ? $reg[5] : '');
		$ssec	= (! empty($reg[6]) ? $reg[6] : '');

		$time=dol_mktime($shour, $smin, $ssec, $smonth, $sday, $syear, true);
		$ret=adodb_strftime($format, $time+$offsettz+$offsetdst, $to_gmt);
	}
	else
	{
		// Date is a timestamps
		if ($time < 100000000000)	// Protection against bad date values
		{
			$timetouse = $time+$offsettz+$offsetdst;	// TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.

			$ret=adodb_strftime($format, $timetouse, $to_gmt);
		}
		else $ret='Bad value '.$time.' for date';
	}

	if (preg_match('/__b__/i', $format))
	{
		$timetouse = $time+$offsettz+$offsetdst;	// TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.

		// Here ret is string in PHP setup language (strftime was used). Now we convert to $outputlangs.
		$month=adodb_strftime('%m', $timetouse);
		$month=sprintf("%02d", $month);	// $month may be return with format '06' on some installation and '6' on other, so we force it to '06'.
		if ($encodetooutput)
		{
			$monthtext=$outputlangs->transnoentities('Month'.$month);
			$monthtextshort=$outputlangs->transnoentities('MonthShort'.$month);
		}
		else
		{
			$monthtext=$outputlangs->transnoentitiesnoconv('Month'.$month);
			$monthtextshort=$outputlangs->transnoentitiesnoconv('MonthShort'.$month);
		}
		//print 'monthtext='.$monthtext.' monthtextshort='.$monthtextshort;
		$ret=str_replace('__b__', $monthtextshort, $ret);
		$ret=str_replace('__B__', $monthtext, $ret);
		//print 'x'.$outputlangs->charset_output.'-'.$ret.'x';
		//return $ret;
	}
	if (preg_match('/__a__/i', $format))
	{
		$timetouse = $time+$offsettz+$offsetdst;	// TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.

		$w=adodb_strftime('%w', $timetouse);						// TODO Replace this with function Date PHP. We also should not use anymore offsettz and offsetdst but only offsettzstring.
		$dayweek=$outputlangs->transnoentitiesnoconv('Day'.$w);
		$ret=str_replace('__A__', $dayweek, $ret);
		$ret=str_replace('__a__', dol_substr($dayweek, 0, 3), $ret);
	}

	return $ret;
}


/**
 *	Return an array with locale date info.
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
 * 	@see 								dol_print_date, dol_stringtotime, dol_mktime
 */
function dol_getdate($timestamp, $fast = false)
{
	global $conf;

	$usealternatemethod=false;
	if ($timestamp <= 0) $usealternatemethod=true;				// <= 1970
	if ($timestamp >= 2145913200) $usealternatemethod=true;		// >= 2038

	if ($usealternatemethod)
	{
		$arrayinfo=adodb_getdate($timestamp, $fast);
	}
	else
	{
		$arrayinfo=getdate($timestamp);
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
 * 	@see 								dol_print_date, dol_stringtotime, dol_getdate
 */
function dol_mktime($hour, $minute, $second, $month, $day, $year, $gm = false, $check = 1)
{
	global $conf;
	//print "- ".$hour.",".$minute.",".$second.",".$month.",".$day.",".$year.",".$_SERVER["WINDIR"]." -";

	// Clean parameters
	if ($hour   == -1 || empty($hour)) $hour=0;
	if ($minute == -1 || empty($minute)) $minute=0;
	if ($second == -1 || empty($second)) $second=0;

	// Check parameters
	if ($check)
	{
		if (! $month || ! $day)  return '';
		if ($day   > 31) return '';
		if ($month > 12) return '';
		if ($hour  < 0 || $hour   > 24) return '';
		if ($minute< 0 || $minute > 60) return '';
		if ($second< 0 || $second > 60) return '';
	}

	if (method_exists('DateTime', 'getTimestamp'))
	{
		if (empty($gm) || $gm === 'server')
		{
			$default_timezone=@date_default_timezone_get();		// Example 'Europe/Berlin'
			$localtz = new DateTimeZone($default_timezone);
		}
		elseif ($gm === 'user')
		{
			// We use dol_tz_string first because it is more reliable.
			$default_timezone=(empty($_SESSION["dol_tz_string"])?@date_default_timezone_get():$_SESSION["dol_tz_string"]);		// Example 'Europe/Berlin'
			try {
				$localtz = new DateTimeZone($default_timezone);
			}
			catch(Exception $e)
			{
				dol_syslog("Warning dol_tz_string contains an invalid value ".$_SESSION["dol_tz_string"], LOG_WARNING);
				$default_timezone=@date_default_timezone_get();
			}
		}
		elseif (strrpos($gm, "tz,") !== false)
		{
			$timezone=str_replace("tz,", "", $gm);  // Example 'tz,Europe/Berlin'
			try
			{
				$localtz = new DateTimeZone($timezone);
			}
			catch(Exception $e)
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
		$dt->setDate($year, $month, $day);
		$dt->setTime((int) $hour, (int) $minute, (int) $second);
		$date=$dt->getTimestamp();	// should include daylight saving time
		//var_dump($date);
		return $date;
	}
	else
	{
		dol_print_error('', 'PHP version must be 5.4+');
		return '';
	}
}


/**
 *	Return date for now. In most cases, we use this function without parameters (that means GMT time).
 *
 * 	@param	string		$mode	'gmt' => we return GMT timestamp,
 * 								'tzserver' => we add the PHP server timezone
 *  							'tzref' => we add the company timezone
 * 								'tzuser' => we add the user timezone
 *	@return int   $date	Timestamp
 */
function dol_now($mode = 'gmt')
{
	$ret=0;

	// Note that gmmktime and mktime return same value (GMT) when used without parameters
	//if ($mode == 'gmt') $ret=gmmktime(); // Strict Standards: gmmktime(): You should be using the time() function instead
	if ($mode == 'gmt') $ret=time();	// Time for now at greenwich.
	elseif ($mode == 'tzserver')		// Time for now with PHP server timezone added
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$tzsecond=getServerTimeZoneInt('now');    // Contains tz+dayling saving time
		$ret=(int) (dol_now('gmt')+($tzsecond*3600));
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
		$offsettz=(empty($_SESSION['dol_tz'])?0:$_SESSION['dol_tz'])*60*60;
		$offsetdst=(empty($_SESSION['dol_dst'])?0:$_SESSION['dol_dst'])*60*60;
		$ret=(int) (dol_now('gmt')+($offsettz+$offsetdst));
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
	global $conf,$langs;
	$level=1024;

	if (! empty($conf->dol_optimize_smallscreen)) $shortunit=1;

	// Set value text
	if (empty($shortvalue) || $size < ($level*10))
	{
		$ret=$size;
		$textunitshort=$langs->trans("b");
		$textunitlong=$langs->trans("Bytes");
	}
	else
	{
		$ret=round($size/$level, 0);
		$textunitshort=$langs->trans("Kb");
		$textunitlong=$langs->trans("KiloBytes");
	}
	// Use long or short text unit
	if (empty($shortunit)) { $ret.=' '.$textunitlong; }
	else { $ret.=' '.$textunitshort; }

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

	$link='<a href="';
	if (! preg_match('/^http/i', $url)) $link.='http://';
	$link.=$url;
	$link.='"';
	if ($target) $link.=' target="'.$target.'"';
	$link.='>';
	if (! preg_match('/^http/i', $url)) $link.='http://';
	$link.=dol_trunc($url, $max);
	$link.='</a>';
	return '<div class="nospan float" style="margin-right: 10px">'.($withpicto?img_picto($langs->trans("Url"), 'object_globe.png').' ':'').$link.'</div>';
}

/**
 * Show EMail link
 *
 * @param	string		$email			EMail to show (only email, without 'Name of recipient' before)
 * @param 	int			$cid 			Id of contact if known
 * @param 	int			$socid 			Id of third party if known
 * @param 	int			$addlink		0=no link, 1=email has a html email link (+ link to create action if constant AGENDA_ADDACTIONFOREMAIL is on)
 * @param	int			$max			Max number of characters to show
 * @param	int			$showinvalid	Show warning if syntax email is wrong
 * @param	int			$withpicto		Show picto
 * @return	string						HTML Link
 */
function dol_print_email($email, $cid = 0, $socid = 0, $addlink = 0, $max = 64, $showinvalid = 1, $withpicto = 0)
{
	global $conf,$user,$langs,$hookmanager;

	$newemail=$email;

	if (empty($email)) return '&nbsp;';

	if (! empty($addlink))
	{
		$newemail='<a style="text-overflow: ellipsis;" href="';
		if (! preg_match('/^mailto:/i', $email)) $newemail.='mailto:';
		$newemail.=$email;
		$newemail.='">';
		$newemail.=dol_trunc($email, $max);
		$newemail.='</a>';
		if ($showinvalid && ! isValidEmail($email))
		{
			$langs->load("errors");
			$newemail.=img_warning($langs->trans("ErrorBadEMail", $email));
		}

		if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type='AC_EMAIL'; $link='';
			if (! empty($conf->global->AGENDA_ADDACTIONFOREMAIL)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"), "calendar").'</a>';
			if ($link) $newemail='<div>'.$newemail.' '.$link.'</div>';
		}
	}
	else
	{
		if ($showinvalid && ! isValidEmail($email))
		{
			$langs->load("errors");
			$newemail.=img_warning($langs->trans("ErrorBadEMail", $email));
		}
	}

	$rep = '<div class="nospan float" style="margin-right: 10px">'.($withpicto?img_picto($langs->trans("EMail"), 'object_email.png').' ':'').$newemail.'</div>';
	if ($hookmanager) {
		$parameters = array('cid' => $cid, 'socid' => $socid,'addlink' => $addlink, 'picto' => $withpicto);
		$reshook = $hookmanager->executeHooks('printEmail', $parameters, $email);
		$rep.=$hookmanager->resPrint;
	}

	return $rep;
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
	global $conf,$user,$langs;

	$newskype=$value;

	if (empty($value)) return '&nbsp;';

	if (! empty($type))
	{
		$newskype ='<div class="divsocialnetwork inline-block valignmiddle">';
		$newskype.=img_picto($langs->trans(strtoupper($type)), $type.'.png', '', false, 0, 0, '', 'paddingright');
		$newskype.=$value;
		if ($type == 'skype')
		{
			$newskype.= '&nbsp;';
			$newskype.='<a href="skype:';
			$newskype.=$value;
			$newskype.='?call" alt="'.$langs->trans("Call").'&nbsp;'.$value.'" title="'.$langs->trans("Call").'&nbsp;'.$value.'">';
			$newskype.='<img src="'.DOL_URL_ROOT.'/theme/common/skype_callbutton.png" border="0">';
			$newskype.='</a><a href="skype:';
			$newskype.=$value;
			$newskype.='?chat" alt="'.$langs->trans("Chat").'&nbsp;'.$value.'" title="'.$langs->trans("Chat").'&nbsp;'.$value.'">';
			$newskype.='<img class="paddingleft" src="'.DOL_URL_ROOT.'/theme/common/skype_chatbutton.png" border="0">';
			$newskype.='</a>';
		}
		if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create && $type=='skype')
		{
			$addlink='AC_SKYPE'; $link='';
			if (! empty($conf->global->AGENDA_ADDACTIONFORSKYPE)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$addlink.'&amp;contactid='.$cid.'&amp;socid='.$socid.'">'.img_object($langs->trans("AddAction"), "calendar").'</a>';
			$newskype.=($link?' '.$link:'');
		}
		$newskype.='</div>';
	}
	else
	{
		$langs->load("errors");
		$newskype.=img_warning($langs->trans("ErrorBadSocialNetworkValue", $value));
	}
	return $newskype;
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
	if (empty($countrycode)) $countrycode=$mysoc->country_code;

	// Short format for small screens
	if ($conf->dol_optimize_smallscreen) $separ='';

	$newphone=$phone;
	if (strtoupper($countrycode) == "FR")
	{
		// France
		if (dol_strlen($phone) == 10) {
			$newphone=substr($newphone, 0, 2).$separ.substr($newphone, 2, 2).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2);
		}
		elseif (dol_strlen($phone) == 7)
		{
			$newphone=substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 2);
		}
		elseif (dol_strlen($phone) == 9)
		{
			$newphone=substr($newphone, 0, 2).$separ.substr($newphone, 2, 3).$separ.substr($newphone, 5, 2).$separ.substr($newphone, 7, 2);
		}
		elseif (dol_strlen($phone) == 11)
		{
			$newphone=substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 2).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
		elseif (dol_strlen($phone) == 12)
		{
			$newphone=substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}

	elseif (strtoupper($countrycode) == "CA")
	{
		if (dol_strlen($phone) == 10) {
			$newphone=($separ!=''?'(':'').substr($newphone, 0, 3).($separ!=''?')':'').$separ.substr($newphone, 3, 3).($separ!=''?'-':'').substr($newphone, 6, 4);
		}
	}
	elseif (strtoupper($countrycode) == "PT" )
	{//Portugal
		if (dol_strlen($phone) == 13)
		{//ex: +351_ABC_DEF_GHI
			$newphone= substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "SR" )
	{//Suriname
		if (dol_strlen($phone) == 10)
		{//ex: +597_ABC_DEF
			$newphone= substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3);
		}
		elseif (dol_strlen($phone) == 11)
		{//ex: +597_ABC_DEFG
			$newphone= substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 4);
		}
	}
	elseif (strtoupper($countrycode) == "DE" )
	{//Allemagne
		if (dol_strlen($phone) == 14)
		{//ex:  +49_ABCD_EFGH_IJK
			$newphone= substr($newphone, 0, 3).$separ.substr($newphone, 3, 4).$separ.substr($newphone, 7, 4).$separ.substr($newphone, 11, 3);
		}
		elseif (dol_strlen($phone) == 13)
		{//ex: +49_ABC_DEFG_HIJ
			$newphone= substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 4).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "ES")
	{//Espagne
		if (dol_strlen($phone) == 12)
		{//ex:  +34_ABC_DEF_GHI
			$newphone= substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "BF")
	{// Burkina Faso
		if (dol_strlen($phone) == 12)
		{//ex :  +22 A BC_DE_FG_HI
			$newphone= substr($newphone, 0, 3).$separ.substr($newphone, 3, 1).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "RO")
	{// Roumanie
		if (dol_strlen($phone) == 12)
		{//ex :  +40 AB_CDE_FG_HI
			$newphone= substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "TR")
	{//Turquie
		if (dol_strlen($phone) == 13)
		{//ex :  +90 ABC_DEF_GHIJ
			$newphone= substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 4);
		}
	}
	elseif (strtoupper($countrycode) == "US")
	{//Etat-Unis
		if (dol_strlen($phone) == 12)
		{//ex: +1 ABC_DEF_GHIJ
			$newphone= substr($newphone, 0, 2).$separ.substr($newphone, 2, 3).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 4);
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
		if(dol_strlen($phone) == 12)
		{//ex: +223 AB_CD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "TH")
	{//Thaïlande
		if(dol_strlen($phone) == 11)
		{//ex: +66_ABC_DE_FGH
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 3);
		}
		elseif(dol_strlen($phone) == 12)
		{//ex: +66_A_BCD_EF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 1).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "MU")
	{
        //Maurice
		if(dol_strlen($phone) == 11)
		{//ex: +230_ABC_DE_FG
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
		elseif(dol_strlen($phone) == 12)
		{//ex: +230_ABCD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 4).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "ZA")
	{//Afrique du sud
		if(dol_strlen($phone) == 12)
		{//ex: +27_AB_CDE_FG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "SY")
	{//Syrie
		if(dol_strlen($phone) == 12)
		{//ex: +963_AB_CD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
		elseif(dol_strlen($phone) == 13)
		{//ex: +963_AB_CD_EF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "AE")
	{//Emirats Arabes Unis
		if(dol_strlen($phone) == 12)
		{//ex: +971_ABC_DEF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 2);
		}
		elseif(dol_strlen($phone) == 13)
		{//ex: +971_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
		elseif(dol_strlen($phone) == 14)
		{//ex: +971_ABC_DEF_GHIK
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 4);
		}
	}
	elseif (strtoupper($countrycode) == "DZ")
	{//Algérie
		if(dol_strlen($phone) == 13)
		{//ex: +213_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "BE")
	{//Belgique
		if(dol_strlen($phone) == 11)
		{//ex: +32_ABC_DE_FGH
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 3);
		}
		elseif(dol_strlen($phone) == 12)
		{//ex: +32_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "PF")
	{//Polynésie française
		if(dol_strlen($phone) == 12)
		{//ex: +689_AB_CD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
	}
	elseif (strtoupper($countrycode) == "CO")
	{//Colombie
		if(dol_strlen($phone) == 13)
		{//ex: +57_ABC_DEF_GH_IJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "JO")
	{//Jordanie
		if(dol_strlen($phone) == 12)
		{//ex: +962_A_BCD_EF_GH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 1).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2);
		}
	}
	elseif (strtoupper($countrycode) == "MG")
	{//Madagascar
		if(dol_strlen($phone) == 13)
		{//ex: +261_AB_CD_EF_GHI
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 2).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "GB")
	{//Royaume uni
		if(dol_strlen($phone) == 13)
		{//ex: +44_ABCD_EFG_HIJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 4).$separ.substr($newphone, 7, 3).$separ.substr($newphone, 10, 3);
		}
	}
	elseif (strtoupper($countrycode) == "CH")
	{//Suisse
		if(dol_strlen($phone) == 12)
		{//ex: +41_AB_CDE_FG_HI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 2).$separ.substr($newphone, 10, 2);
		}
		elseif(dol_strlen($phone) == 15)
		{// +41_AB_CDE_FGH_IJKL
			$newphone =$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 2).$separ.substr($newphone, 5, 3).$separ.substr($newphone, 8, 3).$separ.substr($newphone, 11, 4);
		}
	}
	elseif (strtoupper($countrycode) == "TN")
	{//Tunisie
		if(dol_strlen($phone) == 12)
		{//ex: +216_AB_CDE_FGH
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 2).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
	}
	elseif (strtoupper($countrycode) == "GF")
	{//Guyane francaise
		if(dol_strlen($phone) == 13)
		{//ex: +594_ABC_DE_FG_HI  (ABC=594 de nouveau)
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "GP")
	{//Guadeloupe
		if(dol_strlen($phone) == 13)
		{//ex: +590_ABC_DE_FG_HI  (ABC=590 de nouveau)
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "MQ")
	{//Martinique
		if(dol_strlen($phone) == 13)
		{//ex: +596_ABC_DE_FG_HI  (ABC=596 de nouveau)
			$newphone = substr($newphone, 0, 4).$separ.substr($newphone, 4, 3).$separ.substr($newphone, 7, 2).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif (strtoupper($countrycode) == "IT")
	{//Italie
		if(dol_strlen($phone) == 12)
		{//ex: +39_ABC_DEF_GHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 3);
		}
		elseif(dol_strlen($phone) == 13)
		{//ex: +39_ABC_DEF_GH_IJ
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 3).$separ.substr($newphone, 6, 3).$separ.substr($newphone, 9, 2).$separ.substr($newphone, 11, 2);
		}
	}
	elseif(strtoupper($countrycode) == "AU")
	{
        //Australie
		if(dol_strlen($phone) == 12)
		{
            //ex: +61_A_BCDE_FGHI
			$newphone = substr($newphone, 0, 3).$separ.substr($newphone, 3, 1).$separ.substr($newphone, 4, 4).$separ.substr($newphone, 8, 4);
		}
	}
	if (! empty($addlink))	// Link on phone number (+ link to add action if conf->global->AGENDA_ADDACTIONFORPHONE set)
	{
		if ($conf->browser->layout == 'phone' || (! empty($conf->clicktodial->enabled) && ! empty($conf->global->CLICKTODIAL_USE_TEL_LINK_ON_PHONE_NUMBERS)))	// If phone or option for, we use link of phone
		{
			$newphone ='<a href="tel:'.$phone.'"';
			$newphone.='>'.$phone.'</a>';
		}
		elseif (! empty($conf->clicktodial->enabled) && $addlink == 'AC_TEL')		// If click to dial, we use click to dial url
		{
			if (empty($user->clicktodial_loaded)) $user->fetch_clicktodial();

			// Define urlmask
			$urlmask='ErrorClickToDialModuleNotConfigured';
			if (! empty($conf->global->CLICKTODIAL_URL)) $urlmask=$conf->global->CLICKTODIAL_URL;
			if (! empty($user->clicktodial_url)) $urlmask=$user->clicktodial_url;

			$clicktodial_poste=(! empty($user->clicktodial_poste)?urlencode($user->clicktodial_poste):'');
			$clicktodial_login=(! empty($user->clicktodial_login)?urlencode($user->clicktodial_login):'');
			$clicktodial_password=(! empty($user->clicktodial_password)?urlencode($user->clicktodial_password):'');
			// This line is for backward compatibility
			$url = sprintf($urlmask, urlencode($phone), $clicktodial_poste, $clicktodial_login, $clicktodial_password);
			// Thoose lines are for substitution
			$substitarray=array('__PHONEFROM__'=>$clicktodial_poste,
								'__PHONETO__'=>urlencode($phone),
								'__LOGIN__'=>$clicktodial_login,
								'__PASS__'=>$clicktodial_password);
			$url = make_substitutions($url, $substitarray);
			$newphonesav=$newphone;
			$newphone ='<a href="'.$url.'"';
			if (! empty($conf->global->CLICKTODIAL_FORCENEWTARGET)) $newphone.=' target="_blank"';
			$newphone.='>'.$newphonesav.'</a>';
		}

		//if (($cid || $socid) && ! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
		{
			$type='AC_TEL'; $link='';
			if ($addlink == 'AC_FAX') $type='AC_FAX';
			if (! empty($conf->global->AGENDA_ADDACTIONFORPHONE)) $link='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;backtopage=1&amp;actioncode='.$type.($cid?'&amp;contactid='.$cid:'').($socid?'&amp;socid='.$socid:'').'">'.img_object($langs->trans("AddAction"), "calendar").'</a>';
			if ($link) $newphone='<div>'.$newphone.' '.$link.'</div>';
		}
	}

	if (empty($titlealt))
	{
		$titlealt=($withpicto=='fax'?$langs->trans("Fax"):$langs->trans("Phone"));
	}
	$rep='';

	if ($hookmanager) {
		$parameters = array('countrycode' => $countrycode, 'cid' => $cid, 'socid' => $socid,'titlealt' => $titlealt, 'picto' => $withpicto);
		$reshook = $hookmanager->executeHooks('printPhone', $parameters, $phone);
		$rep.=$hookmanager->resPrint;
	}
	if (empty($reshook))
	{
		$picto = '';
		if($withpicto){
			if($withpicto=='fax'){
				$picto = 'phoning_fax';
			}elseif($withpicto=='phone'){
				$picto = 'phoning';
			}elseif($withpicto=='mobile'){
				$picto = 'phoning_mobile';
			}else{
				$picto = '';
			}
		}
		if ($adddivfloat) $rep.='<div class="nospan float" style="margin-right: 10px">';
		else $rep.='<span style="margin-right: 10px;">';
		$rep.=($withpicto?img_picto($titlealt, 'object_'.$picto.'.png').' ':'').$newphone;
		if ($adddivfloat) $rep.='</div>';
		else $rep.='</span>';
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
	global $conf,$langs;

	$ret='';

	if (empty($mode)) $ret.=$ip;

	if ($mode != 2)
	{
		$countrycode=dolGetCountryCodeFromIp($ip);
		if ($countrycode)	// If success, countrycode is us, fr, ...
		{
			if (file_exists(DOL_DOCUMENT_ROOT.'/theme/common/flags/'.$countrycode.'.png'))
			{
				$ret.=' '.img_picto($countrycode.' '.$langs->trans("AccordingToGeoIPDatabase"), DOL_URL_ROOT.'/theme/common/flags/'.$countrycode.'.png', '', 1);
			}
			else $ret.=' ('.$countrycode.')';
		}
	}

	return $ret;
}

/**
 * Return the IP of remote user.
 * Take HTTP_X_FORWARDED_FOR (defined when using proxy)
 * Then HTTP_CLIENT_IP if defined (rare)
 * Then REMOTE_ADDR (not way to be modified by user but may be wrong if using proxy)
 *
 * @return	string		Ip of remote user.
 */
function getUserRemoteIP()
{
	$ip = empty($_SERVER['HTTP_X_FORWARDED_FOR'])? (empty($_SERVER['HTTP_CLIENT_IP'])?(empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR']):$_SERVER['HTTP_CLIENT_IP']) : $_SERVER['HTTP_X_FORWARDED_FOR'];
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

	$countrycode='';

	if (! empty($conf->geoipmaxmind->enabled))
	{
		$datafile=$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;
		//$ip='24.24.24.24';
		//$datafile='E:\Mes Sites\Web\Admin1\awstats\maxmind\GeoIP.dat';    Note that this must be downloaded datafile (not same than datafile provided with ubuntu packages)

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
		$geoip=new DolGeoIP('country', $datafile);
		//print 'ip='.$ip.' databaseType='.$geoip->gi->databaseType." GEOIP_CITY_EDITION_REV1=".GEOIP_CITY_EDITION_REV1."\n";
		//print "geoip_country_id_by_addr=".geoip_country_id_by_addr($geoip->gi,$ip)."\n";
		$countrycode=$geoip->getCountryCodeFromIP($ip);
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
	global $conf,$langs,$user;

	//$ret=$user->xxx;
	$ret='';
	if (! empty($conf->geoipmaxmind->enabled))
	{
		$ip=getUserRemoteIP();
		$datafile=$conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE;
		//$ip='24.24.24.24';
		//$datafile='E:\Mes Sites\Web\Admin1\awstats\maxmind\GeoIP.dat';
		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
		$geoip=new DolGeoIP('country', $datafile);
		$countrycode=$geoip->getCountryCodeFromIP($ip);
		$ret=$countrycode;
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
 *  @see dol_format_address
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
			$out.=$hookmanager->resPrint;
		}
		if (empty($reshook))
		{
			if (empty($charfornl)) $out.=nl2br($address);
			else $out.=preg_replace('/[\r\n]+/', $charfornl, $address);

			$showgmap=$showomap=0;

			// TODO Add a hook here
			if (($mode=='thirdparty' || $mode =='societe') && ! empty($conf->google->enabled) && ! empty($conf->global->GOOGLE_ENABLE_GMAPS)) $showgmap=1;
			if ($mode=='contact' && ! empty($conf->google->enabled) && ! empty($conf->global->GOOGLE_ENABLE_GMAPS_CONTACTS)) $showgmap=1;
			if ($mode=='member' && ! empty($conf->google->enabled) && ! empty($conf->global->GOOGLE_ENABLE_GMAPS_MEMBERS)) $showgmap=1;
			if (($mode=='thirdparty' || $mode =='societe') && ! empty($conf->openstreetmap->enabled) && ! empty($conf->global->OPENSTREETMAP_ENABLE_MAPS)) $showomap=1;
			if ($mode=='contact' && ! empty($conf->openstreetmap->enabled) && ! empty($conf->global->OPENSTREETMAP_ENABLE_MAPS_CONTACTS)) $showomap=1;
			if ($mode=='member' && ! empty($conf->openstreetmap->enabled) && ! empty($conf->global->OPENSTREETMAP_ENABLE_MAPS_MEMBERS)) $showomap=1;

			if ($showgmap)
			{
				$url=dol_buildpath('/google/gmaps.php?mode='.$mode.'&id='.$id, 1);
				$out.=' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'" class="valigntextbottom" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
			}
			if ($showomap)
			{
				$url=dol_buildpath('/openstreetmap/maps.php?mode='.$mode.'&id='.$id, 1);
				$out.=' <a href="'.$url.'" target="_gmaps"><img id="'.$htmlid.'_openstreetmap" class="valigntextbottom" src="'.DOL_URL_ROOT.'/theme/common/gmap.png"></a>';
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
		if (! checkdnsrr(idn_to_ascii($domain), 'MX'))
		{
			return 0;
		}
		if (function_exists('getmxrr'))
		{
			$mxhosts=array();
			$weight=array();
			getmxrr(idn_to_ascii($domain), $mxhosts, $weight);
			if (count($mxhosts) > 1) return 1;
			if (count($mxhosts) == 1 && ! empty($mxhosts[0])) return 1;

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

	if (empty($stringencoding)) $stringencoding=$langs->charset_output;

	$ret='';
	if (empty($trunconbytes))
	{
		if (function_exists('mb_substr'))
		{
			$ret=mb_substr($string, $start, $length, $stringencoding);
		}
		else
		{
			$ret=substr($string, $start, $length);
		}
	}
	else
	{
		if (function_exists('mb_strcut'))
		{
			$ret=mb_strcut($string, $start, $length, $stringencoding);
		}
		else
		{
			$ret=substr($string, $start, $length);
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

	if ($size==0 || ! empty($conf->global->MAIN_DISABLE_TRUNC)) return $string;

	if (empty($stringencoding)) $stringencoding='UTF-8';
	// reduce for small screen
	if ($conf->dol_optimize_smallscreen==1 && $display==1) $size = round($size/3);

	// We go always here
	if ($trunc == 'right')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string, 1):$string;
		if (dol_strlen($newstring, $stringencoding) > ($size+($nodot?0:3)))    // If nodot is 0 and size is 1,2 or 3 chars more, we don't trunc and don't add ...
		return dol_substr($newstring, 0, $size, $stringencoding).($nodot?'':'...');
		else
		//return 'u'.$size.'-'.$newstring.'-'.dol_strlen($newstring,$stringencoding).'-'.$string;
		return $string;
	}
	elseif ($trunc == 'middle')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string, 1):$string;
		if (dol_strlen($newstring, $stringencoding) > 2 && dol_strlen($newstring, $stringencoding) > ($size+1))
		{
			$size1=round($size/2);
			$size2=round($size/2);
			return dol_substr($newstring, 0, $size1, $stringencoding).'...'.dol_substr($newstring, dol_strlen($newstring, $stringencoding) - $size2, $size2, $stringencoding);
		}
		else
		return $string;
	}
	elseif ($trunc == 'left')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string, 1):$string;
		if (dol_strlen($newstring, $stringencoding) > ($size+($nodot?0:3)))    // If nodot is 0 and size is 1,2 or 3 chars more, we don't trunc and don't add ...
		return '...'.dol_substr($newstring, dol_strlen($newstring, $stringencoding) - $size, $size, $stringencoding);
		else
		return $string;
	}
	elseif ($trunc == 'wrap')
	{
		$newstring=dol_textishtml($string)?dol_string_nohtmltag($string, 1):$string;
		if (dol_strlen($newstring, $stringencoding) > ($size+1))
		return dol_substr($newstring, 0, $size, $stringencoding)."\n".dol_trunc(dol_substr($newstring, $size, dol_strlen($newstring, $stringencoding)-$size, $stringencoding), $size, $trunc);
		else
		return $string;
	}
	else return 'BadParam3CallingDolTrunc';
}

/**
 *	Show picto whatever it's its name (generic function)
 *
 *	@param      string		$titlealt         	Text on title tag for tooltip. Not used if param notitle is set to 1.
 *	@param      string		$picto       		Name of image file to show ('filenew', ...)
 *												If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
 *                                  			Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                  			Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                  			Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *	@param		string		$moreatt			Add more attribute on img tag (For example 'style="float: right"')
 *	@param		boolean|int	$pictoisfullpath	If true or 1, image path is a full path
 *	@param		int			$srconly			Return only content of the src attribute of img.
 *  @param		int			$notitle			1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *  @param		string		$alt				Force alt for bind people
 *  @param		string		$morecss			Add more class css on img tag (For example 'myclascss'). Work only if $moreatt is empty.
 *  @return     string       				    Return img tag
 *  @see        #img_object, #img_picto_common
 */
function img_picto($titlealt, $picto, $moreatt = '', $pictoisfullpath = false, $srconly = 0, $notitle = 0, $alt = '', $morecss = '')
{
	global $conf, $langs;

	// We forge fullpathpicto for image to $path/img/$picto. By default, we take DOL_URL_ROOT/theme/$conf->theme/img/$picto
	$url = DOL_URL_ROOT;
	$theme = $conf->theme;
	$path = 'theme/'.$theme;

	// Define fullpathpicto to use into src
	if ($pictoisfullpath) {
		// Clean parameters
		if (! preg_match('/(\.png|\.gif|\.svg)$/i', $picto)) {
			$picto .= '.png';
		}
		$fullpathpicto = $picto;
	}
	else {
		$pictowithoutext = preg_replace('/(\.png|\.gif|\.svg)$/', '', $picto);

		//if (in_array($picto, array('switch_off', 'switch_on', 'off', 'on')))
		if (empty($srconly) && in_array($pictowithoutext, array(
				'bank', 'close_title', 'delete', 'edit', 'ellipsis-h', 'filter', 'grip', 'grip_title', 'list', 'listlight', 'off', 'on', 'play', 'playdisabled', 'printer', 'resize',
				'note', 'split', 'switch_off', 'switch_on', 'unlink', 'uparrow', '1downarrow', '1uparrow',
				'jabber','skype','twitter','facebook'
			)
		)) {
			$fakey = $pictowithoutext;
			$facolor = ''; $fasize = '';
			$marginleftonlyshort = 2;
			if ($pictowithoutext == 'switch_off') {
				$fakey = 'fa-toggle-off';
				$facolor = '#999';
				$fasize = '2em';
			}
			elseif ($pictowithoutext == 'switch_on') {
				$fakey = 'fa-toggle-on';
				$facolor = '#227722';
				$fasize = '2em';
			}
			elseif ($pictowithoutext == 'off') {
				$fakey = 'fa-square-o';
				$fasize = '1.3em';
			}
			elseif ($pictowithoutext == 'on') {
				$fakey = 'fa-check-square-o';
				$fasize = '1.3em';
			}
			elseif ($pictowithoutext == 'bank') {
				$fakey = 'fa-bank';
				$facolor = '#444';
			}
			elseif ($pictowithoutext == 'close_title') {
				$fakey = 'fa-window-close';
			}
			elseif ($pictowithoutext == 'delete') {
				$fakey = 'fa-trash';
				$facolor = '#444';
			}
			elseif ($pictowithoutext == 'edit') {
				$fakey = 'fa-pencil';
				$facolor = '#444';
			}
			elseif ($pictowithoutext == 'filter') {
				$fakey = 'fa-'.$pictowithoutext;
			}
			elseif ($pictowithoutext == 'grip_title' || $pictowithoutext == 'grip') {
				$fakey = 'fa-arrows';
				if (! empty($conf->global->MAIN_USE_FONT_AWESOME_5)) $fakey = 'fa-arrows-alt';
			}
			elseif ($pictowithoutext == 'listlight') {
				$fakey = 'fa-download';
				$facolor = '#999';
				$marginleftonlyshort=1;
			}
			elseif ($pictowithoutext == 'printer') {
				$fakey = 'fa-print';
				$fasize = '1.2em';
				$facolor = '#444';
			}
			elseif ($pictowithoutext == 'resize') {
				$fakey = 'fa-crop';
				$facolor = '#444';
			}
			elseif ($pictowithoutext == 'note') {
				$fakey = 'fa-sticky-note-o';
				$facolor = '#999';
				$marginleftonlyshort=1;
			}
			elseif ($pictowithoutext == 'uparrow') {
				$fakey = 'fa-mail-forward';
				$facolor = '#555';
			}
			elseif ($pictowithoutext == '1uparrow') {
				$fakey = 'fa-caret-up';
				$marginleftonlyshort = 1;
			}
			elseif ($pictowithoutext == '1downarrow') {
				$fakey = 'fa-caret-down';
				$marginleftonlyshort = 1;
			}
			elseif ($pictowithoutext == 'unlink')     {
				$fakey = 'fa-chain-broken';
				$facolor = '#555';
			}
			elseif ($pictowithoutext == 'playdisabled') {
				$fakey = 'fa-play';
				$facolor = '#ccc';
			}
			elseif ($pictowithoutext == 'play') {
				$fakey = 'fa-play';
				$facolor = '#444';
			}
			elseif ($pictowithoutext == 'jabber') {
				$fakey = 'fa-comment-o';
			}
			elseif ($pictowithoutext == 'split') {
			    $fakey = 'fa-code-fork';
			}
			else {
				$fakey = 'fa-'.$pictowithoutext;
				$facolor = '#444';
				$marginleftonlyshort=0;
			}
			//this snippet only needed since function img_edit accepts only one additional parameter: no separate one for css only.
            //class/style need to be extracted to avoid duplicate class/style validation errors when $moreatt is added to the end of the attributes
            $reg=array();
			if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
                $morecss .= ($morecss ? ' ' : '') . $reg[1];
                $moreatt = str_replace('class="'.$reg[1].'"', '', $moreatt);
            }
            if (preg_match('/style="([^"]+)"/', $moreatt, $reg)) {
                $morestyle = ' '. $reg[1];
                $moreatt = str_replace('style="'.$reg[1].'"', '', $moreatt);
            }
            $moreatt=trim($moreatt);

			$fa='fa';
			if (! empty($conf->global->MAIN_USE_FONT_AWESOME_5)) $fa='fas';
            $enabledisablehtml = '<span class="' . $fa . ' ' . $fakey . ' ' . ($marginleftonlyshort ? ($marginleftonlyshort == 1 ? 'marginleftonlyshort' : 'marginleftonly') : '');
            $enabledisablehtml .= ' valignmiddle' . ($morecss ? ' ' . $morecss : '') . '" style="' . ($fasize ? ('font-size: ' . $fasize . ';') : '') . ($facolor ? (' color: ' . $facolor . ';') : '') . ($morestyle ? ' ' . $morestyle : '') . '"' . (($notitle || empty($titlealt)) ? '' : ' title="' . dol_escape_htmltag($titlealt) . '"') . ($moreatt ? ' ' . $moreatt : '') . '>';
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$enabledisablehtml.= $titlealt;
			}
			$enabledisablehtml.= '</span>';

			return $enabledisablehtml;
		}

		if (! empty($conf->global->MAIN_OVERWRITE_THEME_PATH)) {
			$path = $conf->global->MAIN_OVERWRITE_THEME_PATH.'/theme/'.$theme;	// If the theme does not have the same name as the module
		}
		elseif (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) {
			$path = $conf->global->MAIN_OVERWRITE_THEME_RES.'/theme/'.$conf->global->MAIN_OVERWRITE_THEME_RES;  // To allow an external module to overwrite image resources whatever is activated theme
		}
		elseif (! empty($conf->modules_parts['theme']) && array_key_exists($theme, $conf->modules_parts['theme'])) {
			$path = $theme.'/theme/'.$theme;     // If the theme have the same name as the module
		}

		// If we ask an image into $url/$mymodule/img (instead of default path)
		if (preg_match('/^([^@]+)@([^@]+)$/i', $picto, $regs)) {
			$picto = $regs[1];
			$path = $regs[2];	// $path is $mymodule
		}

		// Clean parameters
		if (! preg_match('/(\.png|\.gif|\.svg)$/i', $picto)) {
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
    return '<img src="'.$fullpathpicto.'" alt="'.dol_escape_htmltag($alt).'"'.(($notitle || empty($titlealt))?'':' title="'.dol_escape_htmltag($titlealt).'"').($moreatt?' '.$moreatt:' class="inline-block'.($morecss?' '.$morecss:'').'"').'>';	// Alt is used for accessibility, title for popup
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
 *	@param      string		$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		string		$moreatt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *	@return     string      					Return img tag
 *  @see        #img_object, #img_picto
 */
function img_weather($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0)
{
	global $conf;

	if (! preg_match('/(\.png|\.gif)$/i', $picto)) $picto .= '.png';

	$path = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/weather/'.$picto;

	return img_picto($titlealt, $path, $moreatt, 1);
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

	if (! preg_match('/(\.png|\.gif)$/i', $picto)) $picto .= '.png';

	if ($pictoisfullpath) $path = $picto;
	else
	{
		$path = DOL_URL_ROOT.'/theme/common/'.$picto;

		if (! empty($conf->global->MAIN_MODULE_CAN_OVERWRITE_COMMONICONS))
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
	global $conf, $langs;

	if (empty($titlealt) || $titlealt == 'default')
	{
		if ($numaction == '-1' || $numaction == 'ST_NO')			{ $numaction = -1; $titlealt = $langs->transnoentitiesnoconv('ChangeDoNotContact'); }
		elseif ($numaction ==  '0' || $numaction == 'ST_NEVER') 	{ $numaction = 0; $titlealt = $langs->transnoentitiesnoconv('ChangeNeverContacted'); }
		elseif ($numaction ==  '1' || $numaction == 'ST_TODO')  	{ $numaction = 1; $titlealt = $langs->transnoentitiesnoconv('ChangeToContact'); }
		elseif ($numaction ==  '2' || $numaction == 'ST_PEND')  	{ $numaction = 2; $titlealt = $langs->transnoentitiesnoconv('ChangeContactInProcess'); }
		elseif ($numaction ==  '3' || $numaction == 'ST_DONE')  	{ $numaction = 3; $titlealt = $langs->transnoentitiesnoconv('ChangeContactDone'); }
		else { $titlealt = $langs->transnoentitiesnoconv('ChangeStatus '.$numaction); $numaction = 0; }
	}
	if (! is_numeric($numaction)) $numaction=0;

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
	global $conf, $langs;

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
	global $conf, $langs;

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
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Remove');

	return img_picto($titlealt, 'edit_remove.png', $other);
}

/**
 *	Show logo editer/modifier fiche
 *
 *	@param  string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      Si il faut y mettre le style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit($titlealt = 'default', $float = 0, $other = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Modify');

	return img_picto($titlealt, 'edit.png', ($float ? 'style="float: '.($langs->tab_translate["DIRECTION"] == 'rtl'?'left':'right').'"' : "") . ($other?' '.$other:''));
}

/**
 *	Show logo view card
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      Si il faut y mettre le style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_view($titlealt = 'default', $float = 0, $other = '')
{
	global $conf, $langs;

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
	global $conf, $langs;

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
	global $conf,$langs;
	if ($titlealt=="default") $titlealt=$langs->trans("Print");
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
	global $conf, $langs;

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
	global $conf, $langs;

	if ($usealttitle)
	{
		if (is_string($usealttitle)) $usealttitle = dol_escape_htmltag($usealttitle);
		else $usealttitle = $langs->trans('Info');
	}

	return img_picto($usealttitle, 'info.png', 'style="vertical-align: middle;'.($usehelpcursor == 1 ? ' cursor: help': ($usehelpcursor == 2 ? ' cursor: pointer':'')).'"');
}

/**
 *	Show info logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_info($titlealt = 'default')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Informations');

	return img_picto($titlealt, 'info.png', 'style="vertical-align: middle;"');
}

/**
 *	Show warning logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"'). If 1, add float: right. Can't be "class" attribute.
 *	@return string      		Return img tag
 */
function img_warning($titlealt = 'default', $moreatt = '')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Warning');

	//return '<div class="imglatecoin">'.img_picto($titlealt, 'warning_white.png', 'class="pictowarning valignmiddle"'.($moreatt ? ($moreatt == '1' ? ' style="float: right"' : ' '.$moreatt): '')).'</div>';
	return img_picto($titlealt, 'warning.png', 'class="pictowarning valignmiddle"'.($moreatt ? ($moreatt == '1' ? ' style="float: right"' : ' '.$moreatt): ''));
}

/**
 *  Show error logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_error($titlealt = 'default')
{
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Error');

	return img_picto($titlealt, 'error.png', 'class="valigntextbottom"');
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
	global $conf, $langs;

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
	global $conf, $langs;

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
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Down');

	return img_picto($titlealt, ($selected ? '1downarrow_selected.png' : '1downarrow.png'), 'class="imgdown'.($moreclass?" ".$moreclass:"").'"');
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
	global $conf, $langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Up');

	return img_picto($titlealt, ($selected ? '1uparrow_selected.png' : '1uparrow.png'), 'class="imgup'.($moreclass?" ".$moreclass:"").'"');
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
	global $conf, $langs;

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
	global $conf, $langs;

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
	global $conf, $langs;

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
	if ($brand == 'Visa') {$brand='cc-visa';}
	elseif ($brand == 'MasterCard') {$brand='cc-mastercard';}
	elseif ($brand == 'American Express') {$brand='cc-amex';}
	elseif ($brand == 'Discover') {$brand='cc-discover';}
	elseif ($brand == 'JCB') {$brand='cc-jcb';}
	elseif ($brand == 'Diners Club') {$brand='cc-diners-club';}
	elseif (! in_array($brand, array('cc-visa','cc-mastercard','cc-amex','cc-discover','cc-jcb','cc-diners-club'))) {$brand='credit-card';}

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
	return '<i class="fa fa-'.$mimefa.' paddingright"></i>';
}


/**
 *	Show phone logo.
 *  Use img_picto instead.
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$option		Option
 *	@return string      		Return img tag
 *  @deprecated
 *  @see img_picto
 */
function img_phone($titlealt = 'default', $option = 0)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	global $conf,$langs;

	if ($titlealt == 'default') $titlealt = $langs->trans('Call');

	if ($option == 1) $img = 'call';
	else $img = 'call_out';

	return img_picto($titlealt, $img);
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
	$input.= 'value="'.dol_escape_htmltag($titlealt).'" title="'.dol_escape_htmltag($titlealt).'" >';

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
	$input.= 'value="'.dol_escape_htmltag($titlealt).'" title="'.dol_escape_htmltag($titlealt).'" >';

	return $input;
}

/**
 *	Show information for admin users or standard users
 *
 *	@param	string	$text			Text info
 *	@param  integer	$infoonimgalt	Info is shown only on alt of star picto, otherwise it is show on output after the star picto
 *	@param	int		$nodiv			No div
 *  @param  string  $admin          '1'=Info for admin users. '0'=Info for standard users (change only the look), 'xxx'=Other
 *  @param	string	$morecss		More CSS
 *	@return	string					String with info text
 */
function info_admin($text, $infoonimgalt = 0, $nodiv = 0, $admin = '1', $morecss = '')
{
	global $conf, $langs;

	if ($infoonimgalt)
	{
		return img_picto($text, 'info', 'class="hideonsmartphone'.($morecss?' '.$morecss:'').'"');
	}

	return ($nodiv?'':'<div class="'.(empty($admin)?'':($admin=='1'?'info':$admin)).' hideonsmartphone'.($morecss?' '.$morecss:'').'">').'<span class="fa fa-info-circle" title="'.dol_escape_htmltag($admin?$langs->trans('InfoAdmin'):$langs->trans('Note')).'"></span> '.$text.($nodiv?'':'</div>');
}


/**
 *	Affiche message erreur system avec toutes les informations pour faciliter le diagnostic et la remontee des bugs.
 *	On doit appeler cette fonction quand une erreur technique bloquante est rencontree.
 *	Toutefois, il faut essayer de ne l'appeler qu'au sein de pages php, les classes devant
 *	renvoyer leur erreur par l'intermediaire de leur propriete "error".
 *
 *	@param	 	DoliDB	$db      	Database handler
 *	@param  	mixed	$error		String or array of errors strings to show
 *  @param		array	$errors		Array of errors
 *	@return 	void
 *  @see    	dol_htmloutput_errors
 */
function dol_print_error($db = '', $error = '', $errors = null)
{
	global $conf,$langs,$argv;
	global $dolibarr_main_prod;

	$out = '';
	$syslog = '';

	// Si erreur intervenue avant chargement langue
	if (! $langs)
	{
		require_once DOL_DOCUMENT_ROOT .'/core/class/translate.class.php';
		$langs = new Translate('', $conf);
		$langs->load("main");
	}
	// Load translation files required by the page
    $langs->loadLangs(array('main', 'errors'));

	if ($_SERVER['DOCUMENT_ROOT'])    // Mode web
	{
		$out.=$langs->trans("DolibarrHasDetectedError").".<br>\n";
		if (! empty($conf->global->MAIN_FEATURES_LEVEL)) $out.="You use an experimental or develop level of features, so please do NOT report any bugs, except if problem is confirmed moving option MAIN_FEATURES_LEVEL back to 0.<br>\n";
		$out.=$langs->trans("InformationToHelpDiagnose").":<br>\n";

		$out.="<b>".$langs->trans("Date").":</b> ".dol_print_date(time(), 'dayhourlog')."<br>\n";
		$out.="<b>".$langs->trans("Dolibarr").":</b> ".DOL_VERSION."<br>\n";
		if (isset($conf->global->MAIN_FEATURES_LEVEL)) $out.="<b>".$langs->trans("LevelOfFeature").":</b> ".$conf->global->MAIN_FEATURES_LEVEL."<br>\n";
		if (function_exists("phpversion"))
		{
			$out.="<b>".$langs->trans("PHP").":</b> ".phpversion()."<br>\n";
		}
		$out.="<b>".$langs->trans("Server").":</b> ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
		if (function_exists("php_uname"))
		{
			$out.="<b>".$langs->trans("OS").":</b> ".php_uname()."<br>\n";
		}
		$out.="<b>".$langs->trans("UserAgent").":</b> ".$_SERVER["HTTP_USER_AGENT"]."<br>\n";
		$out.="<br>\n";
		$out.="<b>".$langs->trans("RequestedUrl").":</b> ".dol_htmlentities($_SERVER["REQUEST_URI"], ENT_COMPAT, 'UTF-8')."<br>\n";
		$out.="<b>".$langs->trans("Referer").":</b> ".(isset($_SERVER["HTTP_REFERER"])?dol_htmlentities($_SERVER["HTTP_REFERER"], ENT_COMPAT, 'UTF-8'):'')."<br>\n";
		$out.="<b>".$langs->trans("MenuManager").":</b> ".(isset($conf->standard_menu)?$conf->standard_menu:'')."<br>\n";
		$out.="<br>\n";
		$syslog.="url=".dol_escape_htmltag($_SERVER["REQUEST_URI"]);
		$syslog.=", query_string=".dol_escape_htmltag($_SERVER["QUERY_STRING"]);
	}
	else                              // Mode CLI
	{
		$out.='> '.$langs->transnoentities("ErrorInternalErrorDetected").":\n".$argv[0]."\n";
		$syslog.="pid=".dol_getmypid();
	}

	if (is_object($db))
	{
		if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
		{
			$out.="<b>".$langs->trans("DatabaseTypeManager").":</b> ".$db->type."<br>\n";
			$out.="<b>".$langs->trans("RequestLastAccessInError").":</b> ".($db->lastqueryerror()?dol_escape_htmltag($db->lastqueryerror()):$langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out.="<b>".$langs->trans("ReturnCodeLastAccessInError").":</b> ".($db->lasterrno()?dol_escape_htmltag($db->lasterrno()):$langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out.="<b>".$langs->trans("InformationLastAccessInError").":</b> ".($db->lasterror()?dol_escape_htmltag($db->lasterror()):$langs->trans("ErrorNoRequestInError"))."<br>\n";
			$out.="<br>\n";
		}
		else                            // Mode CLI
		{
			// No dol_escape_htmltag for output, we are in CLI mode
			$out.='> '.$langs->transnoentities("DatabaseTypeManager").":\n".$db->type."\n";
			$out.='> '.$langs->transnoentities("RequestLastAccessInError").":\n".($db->lastqueryerror()?$db->lastqueryerror():$langs->transnoentities("ErrorNoRequestInError"))."\n";
			$out.='> '.$langs->transnoentities("ReturnCodeLastAccessInError").":\n".($db->lasterrno()?$db->lasterrno():$langs->transnoentities("ErrorNoRequestInError"))."\n";
			$out.='> '.$langs->transnoentities("InformationLastAccessInError").":\n".($db->lasterror()?$db->lasterror():$langs->transnoentities("ErrorNoRequestInError"))."\n";
		}
		$syslog.=", sql=".$db->lastquery();
		$syslog.=", db_error=".$db->lasterror();
	}

	if ($error || $errors)
	{
		$langs->load("errors");

		// Merge all into $errors array
		if (is_array($error) && is_array($errors)) $errors=array_merge($error, $errors);
		elseif (is_array($error)) $errors=$error;
		elseif (is_array($errors)) $errors=array_merge(array($error), $errors);
		else $errors=array_merge(array($error));

		foreach($errors as $msg)
		{
			if (empty($msg)) continue;
			if ($_SERVER['DOCUMENT_ROOT'])  // Mode web
			{
				$out.="<b>".$langs->trans("Message").":</b> ".dol_escape_htmltag($msg)."<br>\n" ;
			}
			else                        // Mode CLI
			{
				$out.='> '.$langs->transnoentities("Message").":\n".$msg."\n" ;
			}
			$syslog.=", msg=".$msg;
		}
	}
	if (empty($dolibarr_main_prod) && $_SERVER['DOCUMENT_ROOT'] && function_exists('xdebug_print_function_stack') && function_exists('xdebug_call_file'))
	{
		xdebug_print_function_stack();
		$out.='<b>XDebug informations:</b>'."<br>\n";
		$out.='File: '.xdebug_call_file()."<br>\n";
		$out.='Line: '.xdebug_call_line()."<br>\n";
		$out.='Function: '.xdebug_call_function()."<br>\n";
		$out.="<br>\n";
	}

	if (empty($dolibarr_main_prod)) print $out;
	else
	{
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
	global $langs,$conf;

	if (empty($email)) $email=$conf->global->MAIN_INFO_SOCIETE_MAIL;

	$langs->load("errors");
	$now=dol_now();

	print '<br><div class="center login_main_message"><div class="'.$morecss.'">';
	print $langs->trans("ErrorContactEMail", $email, $prefixcode.dol_print_date($now, '%Y%m%d'));
	if ($errormessage) print '<br><br>'.$errormessage;
	if (is_array($errormessages) && count($errormessages))
	{
		foreach($errormessages as $mesgtoshow)
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

	$sortorder=strtoupper($sortorder);
	$out='';
	$sortimg='';

	$tag='th';
	if ($thead==2) $tag='div';

	$tmpsortfield=explode(',', $sortfield);
	$sortfield1=trim($tmpsortfield[0]);    // If $sortfield is 'd.datep,d.id', it becomes 'd.datep'
	$tmpfield=explode(',', $field);
	$field1=trim($tmpfield[0]);            // If $field is 'd.datep,d.id', it becomes 'd.datep'

	//var_dump('field='.$field.' field1='.$field1.' sortfield='.$sortfield.' sortfield1='.$sortfield1);
	// If field is used as sort criteria we use a specific css class liste_titre_sel
	// Example if (sortfield,field)=("nom","xxx.nom") or (sortfield,field)=("nom","nom")
	if ($field1 && ($sortfield1 == $field1 || $sortfield1 == preg_replace("/^[^\.]+\./", "", $field1))) $out.= '<'.$tag.' class="'.$prefix.'liste_titre_sel" '. $moreattrib.'>';
	else $out.= '<'.$tag.' class="'.$prefix.'liste_titre" '. $moreattrib.'>';

	if (empty($thead) && $field && empty($disablesortlink))    // If this is a sort field
	{
		$options=preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', $moreparam);
		$options=preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options=preg_replace('/&+/i', '&', $options);
		if (! preg_match('/^&/', $options)) $options='&'.$options;

		$sortordertouseinlink='';
		if ($field1 != $sortfield1) // We are on another field than current sorted field
		{
			if (preg_match('/^DESC/i', $sortorder))
			{
				$sortordertouseinlink.=str_repeat('desc,', count(explode(',', $field)));
			}
			else		// We reverse the var $sortordertouseinlink
			{
				$sortordertouseinlink.=str_repeat('asc,', count(explode(',', $field)));
			}
		}
		else                        // We are on field that is the first current sorting criteria
		{
			if (preg_match('/^ASC/i', $sortorder))	// We reverse the var $sortordertouseinlink
			{
				$sortordertouseinlink.=str_repeat('desc,', count(explode(',', $field)));
			}
			else
			{
				$sortordertouseinlink.=str_repeat('asc,', count(explode(',', $field)));
			}
		}
		$sortordertouseinlink=preg_replace('/,$/', '', $sortordertouseinlink);
		$out.= '<a class="reposition" href="'.$file.'?sortfield='.$field.'&sortorder='.$sortordertouseinlink.'&begin='.$begin.$options.'">';
	}

	if ($tooltip) $out.=$form->textwithpicto($langs->trans($name), $langs->trans($tooltip));
	else $out.=$langs->trans($name);

	if (empty($thead) && $field && empty($disablesortlink))    // If this is a sort field
	{
		$out.='</a>';
	}

	if (empty($thead) && $field)    // If this is a sort field
	{
		$options=preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', $moreparam);
		$options=preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options=preg_replace('/&+/i', '&', $options);
		if (! preg_match('/^&/', $options)) $options='&'.$options;

		if (! $sortorder || $field1 != $sortfield1)
		{
			//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
			//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
		}
		else
		{
			if (preg_match('/^DESC/', $sortorder)) {
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",0).'</a>';
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",1).'</a>';
				$sortimg.= '<span class="nowrap">'.img_up("Z-A", 0).'</span>';
			}
			if (preg_match('/^ASC/', $sortorder)) {
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=asc&begin='.$begin.$options.'">'.img_down("A-Z",1).'</a>';
				//$out.= '<a href="'.$file.'?sortfield='.$field.'&sortorder=desc&begin='.$begin.$options.'">'.img_up("Z-A",0).'</a>';
				$sortimg.= '<span class="nowrap">'.img_down("A-Z", 0).'</span>';
			}
		}
	}

	$out.=$sortimg;

	$out.='</'.$tag.'>';

	return $out;
}

/**
 *	Show a title.
 *
 *	@param	string	$title			Title to show
 *	@return	string					Title to show
 *  @deprecated						Use load_fiche_titre instead
 *  @see load_fiche_titre
 */
function print_titre($title)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	print '<div class="titre">'.$title.'</div>';
}

/**
 *	Show a title with picto
 *
 *	@param	string	$title				Title to show
 *	@pa