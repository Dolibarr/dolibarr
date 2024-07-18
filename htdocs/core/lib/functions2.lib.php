<?php
/* Copyright (C) 2008-2011  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2014-2016  Marcos García               <marcosgdf@gmail.com>
 * Copyright (C) 2015       Ferran Marcet               <fmarcet@2byte.es>
 * Copyright (C) 2015-2016  Raphaël Doursenaud          <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Juanjo Menent               <jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file			htdocs/core/lib/functions2.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all rare functions.
 */

// Enable this line to trace path when function is called.
//print xdebug_print_function_stack('Functions2.lib was called');exit;

/**
 * Same function than javascript unescape() function but in PHP.
 *
 * @param 	string	$source		String to decode
 * @return	string				Unescaped string
 */
function jsUnEscape($source)
{
	$decodedStr = "";
	$pos = 0;
	$len = strlen($source);
	while ($pos < $len) {
		$charAt = substr($source, $pos, 1);
		if ($charAt == '%') {
			$pos++;
			$charAt = substr($source, $pos, 1);
			if ($charAt == 'u') {
				// we got a unicode character
				$pos++;
				$unicodeHexVal = substr($source, $pos, 4);
				$unicode = hexdec($unicodeHexVal);
				$entity = "&#".$unicode.';';
				$decodedStr .= mb_convert_encoding($entity, 'UTF-8', 'ISO-8859-1');
				$pos += 4;
			} else {
				// we have an escaped ascii character
				$hexVal = substr($source, $pos, 2);
				$decodedStr .= chr(hexdec($hexVal));
				$pos += 2;
			}
		} else {
			$decodedStr .= $charAt;
			$pos++;
		}
	}
	return dol_html_entity_decode($decodedStr, ENT_COMPAT | ENT_HTML5);
}


/**
 * Return list of directories that contain modules.
 *
 * Detects directories that contain a subdirectory /core/modules.
 * Modules that contains 'disabled' in their name are excluded.
 *
 * @param	string					$subdir	Sub directory (Example: '/mailings' will look for /core/modules/mailings/)
 * @return	array<string,string>			Array of directories that can contain module descriptors ($key==value)
 */
function dolGetModulesDirs($subdir = '')
{
	global $conf;

	$modulesdir = array();

	foreach ($conf->file->dol_document_root as $type => $dirroot) {
		// Default core/modules dir
		if ($type === 'main') {
			$modulesdir[$dirroot.'/core/modules'.$subdir.'/'] = $dirroot.'/core/modules'.$subdir.'/';
		}

		// Scan dir from external modules
		$handle = @opendir($dirroot);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/disabled/', $file)) {
					continue; // We discard module if it contains disabled into name.
				}

				if (substr($file, 0, 1) != '.' && is_dir($dirroot.'/'.$file) && strtoupper(substr($file, 0, 3)) != 'CVS' && $file != 'includes') {
					if (is_dir($dirroot.'/'.$file.'/core/modules'.$subdir.'/')) {
						$modulesdir[$dirroot.'/'.$file.'/core/modules'.$subdir.'/'] = $dirroot.'/'.$file.'/core/modules'.$subdir.'/';
					}
				}
			}
			closedir($handle);
		}
	}
	return $modulesdir;
}


/**
 *  Try to guess default paper format according to language into $langs
 *
 *	@param		Translate|null	$outputlangs		Output lang to use to autodetect output format if setup not done
 *	@return		string								Default paper format code
 */
function dol_getDefaultFormat(Translate $outputlangs = null)
{
	global $langs;

	$selected = 'EUA4';
	if (!$outputlangs) {
		$outputlangs = $langs;
	}

	if ($outputlangs->defaultlang == 'ca_CA') {
		$selected = 'CAP4'; // Canada
	}
	if ($outputlangs->defaultlang == 'en_US') {
		$selected = 'USLetter'; // US
	}
	return $selected;
}


/**
 *	Show information on an object
 *  TODO Move this into html.formother
 *
 *	@param	object	$object			Object to show
 *  @param  int     $usetable       Output into a table
 *	@return	void
 */
function dol_print_object_info($object, $usetable = 0)
{
	global $langs, $db;

	// Load translation files required by the page
	$langs->loadLangs(array('other', 'admin'));

	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	$deltadateforserver = getServerTimeZoneInt('now');
	$deltadateforclient = ((int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst']);
	//$deltadateforcompany=((int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst']);
	$deltadateforuser = round($deltadateforclient - $deltadateforserver);
	//print "x".$deltadateforserver." - ".$deltadateforclient." - ".$deltadateforuser;

	if ($usetable) {
		print '<table class="border tableforfield centpercent">';
	}

	// Import key
	if (!empty($object->import_key)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ImportedWithSet");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print $object->import_key;
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User creation (old method using already loaded object and not id is kept for backward compatibility)
	if (!empty($object->user_creation) || !empty($object->user_creation_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("CreatedBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		if (! empty($object->user_creation) && is_object($object->user_creation)) {	// deprecated mode
			if ($object->user_creation->id) {
				print $object->user_creation->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		} else {
			$userstatic = new User($db);
			$userstatic->fetch($object->user_creation_id);
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date creation
	if (!empty($object->date_creation)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateCreation");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_creation, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_creation, "dayhour", "tzuserrel").' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User change (old method using already loaded object and not id is kept for backward compatibility)
	if (!empty($object->user_modification) || !empty($object->user_modification_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ModifiedBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		if (is_object($object->user_modification)) {
			if ($object->user_modification->id) {
				print $object->user_modification->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		} else {
			$userstatic = new User($db);
			$userstatic->fetch($object->user_modification_id);
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date change
	if (!empty($object->date_modification)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateLastModification");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_modification, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_modification, "dayhour", "tzuserrel").' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User validation (old method using already loaded object and not id is kept for backward compatibility)
	if (!empty($object->user_validation) || !empty($object->user_validation_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ValidatedBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		if (is_object($object->user_validation)) {
			if ($object->user_validation->id) {
				print $object->user_validation->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		} else {
			$userstatic = new User($db);
			$userstatic->fetch($object->user_validation_id ? $object->user_validation_id : $object->user_validation);
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date validation
	if (!empty($object->date_validation)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateValidation");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_validation, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_validation, "dayhour", 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User approve (old method using already loaded object and not id is kept for backward compatibility)
	if (!empty($object->user_approve) || !empty($object->user_approve_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ApprovedBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		if (!empty($object->user_approve) && is_object($object->user_approve)) {
			if ($object->user_approve->id) {
				print $object->user_approve->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		} else {
			$userstatic = new User($db);
			$userstatic->fetch($object->user_approve_id ? $object->user_approve_id : $object->user_approve);
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date approve
	if (!empty($object->date_approve) || !empty($object->date_approval)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateApprove");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_approve ? $object->date_approve : $object->date_approval, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_approve, "dayhour", 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User approve
	if (!empty($object->user_approve_id2)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ApprovedBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		$userstatic = new User($db);
		$userstatic->fetch($object->user_approve_id2);
		if ($userstatic->id) {
			print $userstatic->getNomUrl(-1, '', 0, 0, 0);
		} else {
			print $langs->trans("Unknown");
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date approve
	if (!empty($object->date_approve2)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateApprove2");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_approve2, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_approve2, "dayhour", 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User signature
	if (!empty($object->user_signature) || !empty($object->user_signature_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans('SignedBy');
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		if (is_object($object->user_signature)) {
			if ($object->user_signature->id) {
				print $object->user_signature->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans('Unknown');
			}
		} else {
			$userstatic = new User($db);
			$userstatic->fetch($object->user_signature_id ? $object->user_signature_id : $object->user_signature);
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans('Unknown');
			}
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date signature
	if (!empty($object->date_signature)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans('DateSigning');
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_signature, 'dayhour');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans('CurrentHour').'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_signature, 'dayhour', 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans('ClientHour').'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User close
	if (!empty($object->user_closing_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ClosedBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		$userstatic = new User($db);
		$userstatic->fetch($object->user_closing_id);
		if ($userstatic->id) {
			print $userstatic->getNomUrl(-1, '', 0, 0, 0);
		} else {
			print $langs->trans("Unknown");
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date close
	if (!empty($object->date_cloture) || !empty($object->date_closing)) {
		if (isset($object->date_cloture) && !empty($object->date_cloture)) {
			$object->date_closing = $object->date_cloture;
		}
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateClosing");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_closing, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_closing, "dayhour", 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// User conciliate
	if (!empty($object->user_rappro) || !empty($object->user_rappro_id)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("ReconciledBy");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		if (is_object($object->user_rappro)) {
			if ($object->user_rappro->id) {
				print $object->user_rappro->getNomUrl(-1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		} else {
			$userstatic = new User($db);
			$userstatic->fetch($object->user_rappro_id ? $object->user_rappro_id : $object->user_rappro);
			if ($userstatic->id) {
				print $userstatic->getNomUrl(1, '', 0, 0, 0);
			} else {
				print $langs->trans("Unknown");
			}
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date conciliate
	if (!empty($object->date_rappro)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateConciliating");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_rappro, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_rappro, "dayhour", 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	// Date send
	if (!empty($object->date_envoi)) {
		if ($usetable) {
			print '<tr><td class="titlefield">';
		}
		print $langs->trans("DateLastSend");
		if ($usetable) {
			print '</td><td>';
		} else {
			print ': ';
		}
		print dol_print_date($object->date_envoi, 'dayhour', 'tzserver');
		if ($deltadateforuser) {
			print ' <span class="opacitymedium">'.$langs->trans("CurrentHour").'</span> &nbsp; / &nbsp; '.dol_print_date($object->date_envoi, "dayhour", 'tzuserrel').' &nbsp;<span class="opacitymedium">'.$langs->trans("ClientHour").'</span>';
		}
		if ($usetable) {
			print '</td></tr>';
		} else {
			print '<br>';
		}
	}

	if ($usetable) {
		print '</table>';
	}
}


/**
 *	Return an email formatted to include a tracking id
 *  For example  myemail@example.com becom myemail+trackingid@example.com
 *
 *	@param	string	$email       	Email address (Ex: "toto@example.com", "John Do <johndo@example.com>")
 *	@param	string	$trackingid    	Tracking id (Ex: thi123 for thirdparty with id 123)
 *	@return string     			    Return email tracker string
 */
function dolAddEmailTrackId($email, $trackingid)
{
	$tmp = explode('@', $email);
	return $tmp[0].'+'.$trackingid.'@'.(isset($tmp[1]) ? $tmp[1] : '');
}

/**
 *	Return true if email has a domain name that can be resolved to MX type.
 *
 *	@param	string	$mail       Email address (Ex: "toto@example.com", "John Do <johndo@example.com>")
 *	@return int     			-1 if error (function not available), 0=Not valid, 1=Valid
 */
function isValidMailDomain($mail)
{
	list($user, $domain) = explode("@", $mail, 2);
	return ($domain ? isValidMXRecord($domain) : 0);
}

/**
 *	Url string validation
 *  <http[s]> :// [user[:pass]@] hostname [port] [/path] [?getquery] [anchor]
 *
 *	@param	string	$url		Url
 *  @param  int		$http		1: verify http is provided, 0: not verify http
 *  @param  int		$pass		1: verify user and pass is provided, 0: not verify user and pass
 *  @param  int		$port		1: verify port is provided, 0: not verify port
 *  @param  int		$path		1: verify a path is provided "/" or "/..." or "/.../", 0: not verify path
 *  @param  int		$query		1: verify query is provided, 0: not verify query
 *  @param  int		$anchor		1: verify anchor is provided, 0: not verify anchor
 *	@return int					1=Check is OK, 0=Check is KO
 */
function isValidUrl($url, $http = 0, $pass = 0, $port = 0, $path = 0, $query = 0, $anchor = 0)
{
	$ValidUrl = 0;
	$urlregex = '';

	// SCHEME
	if ($http) {
		$urlregex .= "^(http:\/\/|https:\/\/)";
	}

	// USER AND PASS
	if ($pass) {
		$urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)";
	}

	// HOSTNAME OR IP
	//$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";  // x allowed (ex. http://localhost, http://routerlogin)
	//$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+";  // x.x
	$urlregex .= "([a-z0-9+\$_\\\:-])+(\.[a-z0-9+\$_-][a-z0-9+\$_-]+)*"; // x ou x.xx (2 x ou plus)
	//use only one of the above

	// PORT
	if ($port) {
		$urlregex .= "(\:[0-9]{2,5})";
	}
	// PATH
	if ($path) {
		$urlregex .= "(\/([a-z0-9+\$_-]\.?)+)*\/";
	}
	// GET Query
	if ($query) {
		$urlregex .= "(\?[a-z+&\$_.-][a-z0-9;:@\/&%=+\$_.-]*)";
	}
	// ANCHOR
	if ($anchor) {
		$urlregex .= "(#[a-z_.-][a-z0-9+\$_.-]*)$";
	}

	// check
	if (preg_match('/'.$urlregex.'/i', $url)) {
		$ValidUrl = 1;
	}
	//print $urlregex.' - '.$url.' - '.$ValidUrl;

	return $ValidUrl;
}

/**
 *	Check if VAT numero is valid (check done on syntax only, no database or remote access)
 *
 *	@param	Societe   $company       VAT number
 *	@return int					     1=Check is OK, 0=Check is KO
 */
function isValidVATID($company)
{
	if ($company->isInEEC()) {    // Syntax check rules for EEC countries
		/* Disabled because some companies can have an address in Irland and a vat number in France.
		$vatprefix = $company->country_code;
		if ($vatprefix == 'GR') $vatprefix = '(EL|GR)';
		elseif ($vatprefix == 'MC') $vatprefix = 'FR';	// Monaco is using french VAT numbers
		else $vatprefix = preg_quote($vatprefix, '/');*/
		$vatprefix = '[a-zA-Z][a-zA-Z]';
		if (!preg_match('/^'.$vatprefix.'[a-zA-Z0-9\-\.]{5,14}$/i', str_replace(' ', '', $company->tva_intra))) {
			return 0;
		}
	}

	return 1;
}

/**
 *	Clean an url string
 *
 *	@param	string	$url		Url
 *	@param  integer	$http		1 = keep both http:// and https://, 0: remove http:// but not https://
 *	@return string				Cleaned url
 */
function clean_url($url, $http = 1)
{
	// Fixed by Matelli (see http://matelli.fr/showcases/patch%73-dolibarr/fix-cleaning-url.html)
	// To include the minus sign in a char class, we must not escape it but put it at the end of the class
	// Also, there's no need of escape a dot sign in a class
	$regs = array();
	if (preg_match('/^(https?:[\\/]+)?([0-9A-Z.-]+\.[A-Z]{2,4})(:[0-9]+)?/i', $url, $regs)) {
		$proto = $regs[1];
		$domain = $regs[2];
		$port = isset($regs[3]) ? $regs[3] : '';
		//print $url." -> ".$proto." - ".$domain." - ".$port;
		//$url = dol_string_nospecial(trim($url));
		$url = trim($url);

		// Si http: defini on supprime le http (Si https on ne supprime pas)
		$newproto = $proto;
		if ($http == 0) {
			if (preg_match('/^http:[\\/]+/i', $url)) {
				$url = preg_replace('/^http:[\\/]+/i', '', $url);
				$newproto = '';
			}
		}

		// On passe le nom de domaine en minuscule
		$CleanUrl = preg_replace('/^'.preg_quote($proto.$domain, '/').'/i', $newproto.strtolower($domain), $url);

		return $CleanUrl;
	} else {
		return $url;
	}
}



/**
 * 	Returns an email value with obfuscated parts.
 *
 * 	@param 		string		$mail				Email
 * 	@param 		string		$replace			Replacement character (default: *)
 * 	@param 		int			$nbreplace			Number of replacement character (default: 8)
 * 	@param 		int			$nbdisplaymail		Number of character unchanged (default: 4)
 * 	@param 		int			$nbdisplaydomain	Number of character unchanged of domain (default: 3)
 * 	@param 		bool		$displaytld			Display tld (default: true)
 * 	@return		string							Return email with hidden parts or '';
 */
function dolObfuscateEmail($mail, $replace = "*", $nbreplace = 8, $nbdisplaymail = 4, $nbdisplaydomain = 3, $displaytld = true)
{
	if (!isValidEmail($mail)) {
		return '';
	}
	$tab = explode('@', $mail);
	$tab2 = explode('.', $tab[1]);
	$string_replace = '';
	$mail_name = $tab[0];
	$mail_domaine = $tab2[0];
	$mail_tld = '';

	$nbofelem = count($tab2);
	for ($i = 1; $i < $nbofelem && $displaytld; $i++) {
		$mail_tld .= '.'.$tab2[$i];
	}

	for ($i = 0; $i < $nbreplace; $i++) {
		$string_replace .= $replace;
	}

	if (strlen($mail_name) > $nbdisplaymail) {
		$mail_name = substr($mail_name, 0, $nbdisplaymail);
	}

	if (strlen($mail_domaine) > $nbdisplaydomain) {
		$mail_domaine = substr($mail_domaine, strlen($mail_domaine) - $nbdisplaydomain);
	}

	return $mail_name.$string_replace.$mail_domaine.$mail_tld;
}


/**
 * 	Return lines of an html table from an array
 * 	Used by array2table function only
 *
 * 	@param	array	$data		Array of data
 * 	@param	string	$troptions	Options for tr
 * 	@param	string	$tdoptions	Options for td
 * 	@return	string
 */
function array2tr($data, $troptions = '', $tdoptions = '')
{
	$text = '<tr '.$troptions.'>';
	foreach ($data as $key => $item) {
		$text .= '<td '.$tdoptions.'>'.$item.'</td>';
	}
	$text .= '</tr>';
	return $text;
}

/**
 * 	Return an html table from an array
 *
 * 	@param	array	$data			Array of data
 * 	@param	int		$tableMarkup	Table markup
 * 	@param	string	$tableoptions	Options for table
 * 	@param	string	$troptions		Options for tr
 * 	@param	string	$tdoptions		Options for td
 * 	@return	string
 */
function array2table($data, $tableMarkup = 1, $tableoptions = '', $troptions = '', $tdoptions = '')
{
	$text = '';
	if ($tableMarkup) {
		$text = '<table '.$tableoptions.'>';
	}
	foreach ($data as $key => $item) {
		if (is_array($item)) {
			$text .= array2tr($item, $troptions, $tdoptions);
		} else {
			$text .= '<tr '.$troptions.'>';
			$text .= '<td '.$tdoptions.'>'.$key.'</td>';
			$text .= '<td '.$tdoptions.'>'.$item.'</td>';
			$text .= '</tr>';
		}
	}
	if ($tableMarkup) {
		$text .= '</table>';
	}
	return $text;
}

/**
 * Return last or next value for a mask (according to area we should not reset)
 *
 * @param   DoliDB		$db				Database handler
 * @param   string		$mask			Mask to use. Must contains {0...0}. Can contains {t..}, {u...}, {user_extra_xxx}, .;.
 * @param   string		$table			Table containing field with counter
 * @param   string		$field			Field containing already used values of counter
 * @param   string		$where			To add a filter on selection (for example to filter on invoice types)
 * @param   Societe|''  $objsoc			The company that own the object we need a counter for
 * @param   string		$date			Date to use for the {y},{m},{d} tags.
 * @param   string		$mode			'next' for next value or 'last' for last value
 * @param   bool		$bentityon		Activate the entity filter. Default is true (for modules not compatible with multicompany)
 * @param	User		$objuser		Object user we need data from.
 * @param	int			$forceentity	Entity id to force
 * @return 	string						New value (numeric) or error message
 */
function get_next_value($db, $mask, $table, $field, $where = '', $objsoc = '', $date = '', $mode = 'next', $bentityon = true, $objuser = null, $forceentity = null)
{
	global $user;

	if (!is_object($objsoc)) {
		$valueforccc = $objsoc;
	} elseif ($table == "commande_fournisseur" || $table == "facture_fourn" || $table == "paiementfourn") {
		$valueforccc = dol_string_unaccent($objsoc->code_fournisseur);
	} else {
		$valueforccc = dol_string_unaccent($objsoc->code_client);
	}

	$sharetable = $table;
	if ($table == 'facture' || $table == 'invoice') {
		$sharetable = 'invoicenumber'; // for getEntity function
	}

	// Clean parameters
	if ($date == '') {
		$date = dol_now(); // We use local year and month of PHP server to search numbers
	}
	// but we should use local year and month of user

	// For debugging
	//dol_syslog("mask=".$mask, LOG_DEBUG);
	//include_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
	//$mask='FA{yy}{mm}-{0000@99}';
	//$date=dol_mktime(12, 0, 0, 1, 1, 1900);
	//$date=dol_stringtotime('20130101');

	$hasglobalcounter = false;
	$reg = array();
	// Extract value for mask counter, mask raz and mask offset
	if (preg_match('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i', $mask, $reg)) {
		$masktri = $reg[1].(!empty($reg[2]) ? $reg[2] : '').(!empty($reg[3]) ? $reg[3] : '');
		$maskcounter = $reg[1];
		$hasglobalcounter = true;
	} else {
		// setting some defaults so the rest of the code won't fail if there is a third party counter
		$masktri = '00000';
		$maskcounter = '00000';
	}

	$maskraz = -1;
	$maskoffset = 0;
	$resetEveryMonth = false;
	if (dol_strlen($maskcounter) < 3 && !getDolGlobalString('MAIN_COUNTER_WITH_LESS_3_DIGITS')) {
		return 'ErrorCounterMustHaveMoreThan3Digits';
	}

	// Extract value for third party mask counter
	$regClientRef = array();
	if (preg_match('/\{(c+)(0*)\}/i', $mask, $regClientRef)) {
		$maskrefclient = $regClientRef[1].$regClientRef[2];
		$maskrefclient_maskclientcode = $regClientRef[1];
		$maskrefclient_maskcounter = $regClientRef[2];
		$maskrefclient_maskoffset = 0; //default value of maskrefclient_counter offset
		$maskrefclient_clientcode = substr($valueforccc, 0, dol_strlen($maskrefclient_maskclientcode)); //get n first characters of client code where n is length in mask
		$maskrefclient_clientcode = str_pad($maskrefclient_clientcode, dol_strlen($maskrefclient_maskclientcode), "#", STR_PAD_RIGHT); //padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
		$maskrefclient_clientcode = dol_string_nospecial($maskrefclient_clientcode); //sanitize maskrefclient_clientcode for sql insert and sql select like
		if (dol_strlen($maskrefclient_maskcounter) > 0 && dol_strlen($maskrefclient_maskcounter) < 3) {
			return 'ErrorCounterMustHaveMoreThan3Digits';
		}
	} else {
		$maskrefclient = '';
	}

	// fail if there is neither a global nor a third party counter
	if (!$hasglobalcounter && ($maskrefclient_maskcounter == '')) {
		return 'ErrorBadMask';
	}

	// Extract value for third party type
	$regType = array();
	if (preg_match('/\{(t+)\}/i', $mask, $regType)) {
		$masktype = $regType[1];
		$masktype_value = dol_substr(preg_replace('/^TE_/', '', $objsoc->typent_code), 0, dol_strlen($regType[1])); // get n first characters of thirdparty typent_code (where n is length in mask)
		$masktype_value = str_pad($masktype_value, dol_strlen($regType[1]), "#", STR_PAD_RIGHT); // we fill on right with # to have same number of char than into mask
	} else {
		$masktype = '';
		$masktype_value = '';
	}

	// Extract value for user
	$regType = array();
	if (preg_match('/\{(u+)\}/i', $mask, $regType)) {
		$lastname = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
		if (is_object($objuser)) {
			$lastname = $objuser->lastname;
		}

		$maskuser = $regType[1];
		$maskuser_value = substr($lastname, 0, dol_strlen($regType[1])); // get n first characters of user firstname (where n is length in mask)
		$maskuser_value = str_pad($maskuser_value, dol_strlen($regType[1]), "#", STR_PAD_RIGHT); // we fill on right with # to have same number of char than into mask
	} else {
		$maskuser = '';
		$maskuser_value = '';
	}

	// Personalized field {XXX-1} à {XXX-9}
	$maskperso = array();
	$maskpersonew = array();
	$tmpmask = $mask;
	$regKey = array();
	while (preg_match('/\{([A-Z]+)\-([1-9])\}/', $tmpmask, $regKey)) {
		$maskperso[$regKey[1]] = '{'.$regKey[1].'-'.$regKey[2].'}';
		// @phan-suppress-next-line PhanParamSuspiciousOrder
		$maskpersonew[$regKey[1]] = str_pad('', (int) $regKey[2], '_', STR_PAD_RIGHT);
		$tmpmask = preg_replace('/\{'.$regKey[1].'\-'.$regKey[2].'\}/i', $maskpersonew[$regKey[1]], $tmpmask);
	}

	if (strstr($mask, 'user_extra_')) {
		$start = "{user_extra_";
		$end = "\}";
		$extra = get_string_between($mask, "user_extra_", "}");
		if (!empty($user->array_options['options_'.$extra])) {
			$mask = preg_replace('#('.$start.')(.*?)('.$end.')#si', $user->array_options['options_'.$extra], $mask);
		}
	}
	$maskwithonlyymcode = $mask;
	$maskwithonlyymcode = preg_replace('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i', $maskcounter, $maskwithonlyymcode);
	$maskwithonlyymcode = preg_replace('/\{dd\}/i', 'dd', $maskwithonlyymcode);
	$maskwithonlyymcode = preg_replace('/\{(c+)(0*)\}/i', $maskrefclient, $maskwithonlyymcode);
	$maskwithonlyymcode = preg_replace('/\{(t+)\}/i', $masktype_value, $maskwithonlyymcode);
	$maskwithonlyymcode = preg_replace('/\{(u+)\}/i', $maskuser_value, $maskwithonlyymcode);
	foreach ($maskperso as $key => $val) {
		$maskwithonlyymcode = preg_replace('/'.preg_quote($val, '/').'/i', $maskpersonew[$key], $maskwithonlyymcode);
	}
	$maskwithnocode = $maskwithonlyymcode;
	$maskwithnocode = preg_replace('/\{yyyy\}/i', 'yyyy', $maskwithnocode);
	$maskwithnocode = preg_replace('/\{yy\}/i', 'yy', $maskwithnocode);
	$maskwithnocode = preg_replace('/\{y\}/i', 'y', $maskwithnocode);
	$maskwithnocode = preg_replace('/\{mm\}/i', 'mm', $maskwithnocode);
	// Now maskwithnocode = 0000ddmmyyyyccc for example
	// and maskcounter    = 0000 for example
	//print "maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";
	//var_dump($reg);

	// If an offset is asked
	if (!empty($reg[2]) && preg_match('/^\+/', $reg[2])) {
		$maskoffset = preg_replace('/^\+/', '', $reg[2]);
	}
	if (!empty($reg[3]) && preg_match('/^\+/', $reg[3])) {
		$maskoffset = preg_replace('/^\+/', '', $reg[3]);
	}

	// Define $sqlwhere
	$sqlwhere = '';
	$yearoffset = 0; // Use year of current $date by default
	$yearoffsettype = false; // false: no reset, 0,-,=,+: reset at offset SOCIETE_FISCAL_MONTH_START, x=reset at offset x

	// If a restore to zero after a month is asked we check if there is already a value for this year.
	if (!empty($reg[2]) && preg_match('/^@/', $reg[2])) {
		$yearoffsettype = preg_replace('/^@/', '', $reg[2]);
	}
	if (!empty($reg[3]) && preg_match('/^@/', $reg[3])) {
		$yearoffsettype = preg_replace('/^@/', '', $reg[3]);
	}

	//print "yearoffset=".$yearoffset." yearoffsettype=".$yearoffsettype;
	if (is_numeric($yearoffsettype) && $yearoffsettype >= 1) {
		$maskraz = $yearoffsettype; // For backward compatibility
	} elseif ($yearoffsettype === '0' || (!empty($yearoffsettype) && !is_numeric($yearoffsettype) && getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1)) {
		$maskraz = getDolGlobalString('SOCIETE_FISCAL_MONTH_START');
	}
	//print "maskraz=".$maskraz;	// -1=no reset

	if ($maskraz > 0) {   // A reset is required
		if ($maskraz == 99) {
			$maskraz = (int) date('m', $date);
			$resetEveryMonth = true;
		}
		if ($maskraz > 12) {
			return 'ErrorBadMaskBadRazMonth';
		}

		// Define posy, posm and reg
		if ($maskraz > 1) {	// if reset is not first month, we need month and year into mask
			if (preg_match('/^(.*)\{(y+)\}\{(m+)\}/i', $maskwithonlyymcode, $reg)) {
				$posy = 2;
				$posm = 3;
			} elseif (preg_match('/^(.*)\{(m+)\}\{(y+)\}/i', $maskwithonlyymcode, $reg)) {
				$posy = 3;
				$posm = 2;
			} else {
				return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';
			}

			if (dol_strlen($reg[$posy]) < 2) {
				return 'ErrorCantUseRazWithYearOnOneDigit';
			}
		} else { // if reset is for a specific month in year, we need year
			if (preg_match('/^(.*)\{(m+)\}\{(y+)\}/i', $maskwithonlyymcode, $reg)) {
				$posy = 3;
				$posm = 2;
			} elseif (preg_match('/^(.*)\{(y+)\}\{(m+)\}/i', $maskwithonlyymcode, $reg)) {
				$posy = 2;
				$posm = 3;
			} elseif (preg_match('/^(.*)\{(y+)\}/i', $maskwithonlyymcode, $reg)) {
				$posy = 2;
				$posm = 0;
			} else {
				return 'ErrorCantUseRazIfNoYearInMask';
			}
		}
		// Define length
		$yearlen = $posy ? dol_strlen($reg[$posy]) : 0;
		$monthlen = $posm ? dol_strlen($reg[$posm]) : 0;
		// Define pos
		$yearpos = (dol_strlen($reg[1]) + 1);
		$monthpos = ($yearpos + $yearlen);
		if ($posy == 3 && $posm == 2) {		// if month is before year
			$monthpos = (dol_strlen($reg[1]) + 1);
			$yearpos = ($monthpos + $monthlen);
		}
		//print "xxx ".$maskwithonlyymcode." maskraz=".$maskraz." posy=".$posy." yearlen=".$yearlen." yearpos=".$yearpos." posm=".$posm." monthlen=".$monthlen." monthpos=".$monthpos." yearoffsettype=".$yearoffsettype." resetEveryMonth=".$resetEveryMonth."\n";

		// Define $yearcomp and $monthcomp (that will be use in the select where to search max number)
		$monthcomp = $maskraz;
		$yearcomp = 0;

		if (!empty($yearoffsettype) && !is_numeric($yearoffsettype) && $yearoffsettype != '=') {	// $yearoffsettype is - or +
			$currentyear = (int) date("Y", $date);
			$fiscaldate = dol_mktime('0', '0', '0', $maskraz, '1', $currentyear);
			$newyeardate = dol_mktime('0', '0', '0', '1', '1', $currentyear);
			$nextnewyeardate = dol_mktime('0', '0', '0', '1', '1', $currentyear + 1);
			//echo 'currentyear='.$currentyear.' date='.dol_print_date($date, 'day').' fiscaldate='.dol_print_date($fiscaldate, 'day').'<br>';

			// If after or equal of current fiscal date
			if ($date >= $fiscaldate) {
				// If before of next new year date
				if ($date < $nextnewyeardate && $yearoffsettype == '+') {
					$yearoffset = 1;
				}
			} elseif ($date >= $newyeardate && $yearoffsettype == '-') {
				// If after or equal of current new year date
				$yearoffset = -1;
			}
		} elseif ((int) date("m", $date) < $maskraz && empty($resetEveryMonth)) {
			// For backward compatibility
			$yearoffset = -1;
		}	// If current month lower that month of return to zero, year is previous year

		if ($yearlen == 4) {
			$yearcomp = sprintf("%04d", idate("Y", $date) + $yearoffset);
		} elseif ($yearlen == 2) {
			$yearcomp = sprintf("%02d", idate("y", $date) + $yearoffset);
		} elseif ($yearlen == 1) {
			$yearcomp = (int) substr(date('y', $date), 1, 1) + $yearoffset;
		}
		if ($monthcomp > 1 && empty($resetEveryMonth)) {	// Test with month is useless if monthcomp = 0 or 1 (0 is same as 1) (regis: $monthcomp can't equal 0)
			if ($yearlen == 4) {
				$yearcomp1 = sprintf("%04d", idate("Y", $date) + $yearoffset + 1);
			} elseif ($yearlen == 2) {
				$yearcomp1 = sprintf("%02d", idate("y", $date) + $yearoffset + 1);
			}

			$sqlwhere .= "(";
			$sqlwhere .= " (SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$db->escape($yearcomp)."'";
			$sqlwhere .= " AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") >= '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."')";
			$sqlwhere .= " OR";
			$sqlwhere .= " (SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$db->escape($yearcomp1)."'";
			$sqlwhere .= " AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") < '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."') ";
			$sqlwhere .= ')';
		} elseif ($resetEveryMonth) {
			$sqlwhere .= "(SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$db->escape($yearcomp)."'";
			$sqlwhere .= " AND SUBSTRING(".$field.", ".$monthpos.", ".$monthlen.") = '".str_pad($monthcomp, $monthlen, '0', STR_PAD_LEFT)."')";
		} else { // reset is done on january
			$sqlwhere .= "(SUBSTRING(".$field.", ".$yearpos.", ".$yearlen.") = '".$db->escape($yearcomp)."')";
		}
	}
	//print "sqlwhere=".$sqlwhere." yearcomp=".$yearcomp."<br>\n";	// sqlwhere and yearcomp defined only if we ask a reset
	//print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

	// Define $sqlstring
	if (function_exists('mb_strrpos')) {
		$posnumstart = mb_strrpos($maskwithnocode, $maskcounter, 0, 'UTF-8');
	} else {
		$posnumstart = strrpos($maskwithnocode, $maskcounter);
	}	// Pos of counter in final string (from 0 to ...)
	if ($posnumstart < 0) {
		return 'ErrorBadMaskFailedToLocatePosOfSequence';
	}
	$sqlstring = "SUBSTRING(".$field.", ".($posnumstart + 1).", ".dol_strlen($maskcounter).")";

	// Define $maskLike
	$maskLike = dol_string_nospecial($mask);
	$maskLike = str_replace("%", "_", $maskLike);

	// Replace protected special codes with matching number of _ as wild card character
	$maskLike = preg_replace('/\{yyyy\}/i', '____', $maskLike);
	$maskLike = preg_replace('/\{yy\}/i', '__', $maskLike);
	$maskLike = preg_replace('/\{y\}/i', '_', $maskLike);
	$maskLike = preg_replace('/\{mm\}/i', '__', $maskLike);
	$maskLike = preg_replace('/\{dd\}/i', '__', $maskLike);
	// @phan-suppress-next-line PhanParamSuspiciousOrder
	$maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'), str_pad("", dol_strlen($maskcounter), "_"), $maskLike);
	if ($maskrefclient) {
		// @phan-suppress-next-line PhanParamSuspiciousOrder
		$maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'), str_pad("", dol_strlen($maskrefclient), "_"), $maskLike);
	}
	if ($masktype) {
		$maskLike = str_replace(dol_string_nospecial('{'.$masktype.'}'), $masktype_value, $maskLike);
	}
	if ($maskuser) {
		$maskLike = str_replace(dol_string_nospecial('{'.$maskuser.'}'), $maskuser_value, $maskLike);
	}
	foreach ($maskperso as $key => $val) {
		$maskLike = str_replace(dol_string_nospecial($maskperso[$key]), $maskpersonew[$key], $maskLike);
	}

	// Get counter in database
	$counter = 0;
	$sql = "SELECT MAX(".$sqlstring.") as val";
	$sql .= " FROM ".MAIN_DB_PREFIX.$table;
	$sql .= " WHERE ".$field." LIKE '".$db->escape($maskLike) . (getDolGlobalString('SEARCH_FOR_NEXT_VAL_ON_START_ONLY') ? "%" : "") . "'";
	$sql .= " AND ".$field." NOT LIKE '(PROV%)'";

	// To ensure that all variables within the MAX() brackets are integers
	// This avoid bad detection of max when data are noised with non numeric values at the position of the numero
	if (getDolGlobalInt('MAIN_NUMBERING_FILTER_ON_INT_ONLY')) {
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$sql .= " AND ". $db->regexpsql($sqlstring, '^[0-9]+$', 1);
	}

	if ($bentityon) { // only if entity enable
		$sql .= " AND entity IN (".getEntity($sharetable).")";
	} elseif (!empty($forceentity)) {
		$sql .= " AND entity IN (".$db->sanitize($forceentity).")";
	}
	if ($where) {
		$sql .= $where;
	}
	if ($sqlwhere) {
		$sql .= " AND ".$sqlwhere;
	}

	//print $sql.'<br>';
	dol_syslog("functions2::get_next_value mode=".$mode, LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$counter = $obj->val;
	} else {
		dol_print_error($db);
	}

	// Check if we must force counter to maskoffset
	if (empty($counter)) {
		$counter = $maskoffset;
	} elseif (preg_match('/[^0-9]/i', $counter)) {
		dol_syslog("Error, the last counter found is '".$counter."' so is not a numeric value. We will restart to 1.", LOG_ERR);
		$counter = 0;
	} elseif ($counter < $maskoffset && !getDolGlobalString('MAIN_NUMBERING_OFFSET_ONLY_FOR_FIRST')) {
		$counter = $maskoffset;
	}

	if ($mode == 'last') {	// We found value for counter = last counter value. Now need to get corresponding ref of invoice.
		$counterpadded = str_pad($counter, dol_strlen($maskcounter), "0", STR_PAD_LEFT);

		// Define $maskLike
		$maskLike = dol_string_nospecial($mask);
		$maskLike = str_replace("%", "_", $maskLike);
		// Replace protected special codes with matching number of _ as wild card character
		$maskLike = preg_replace('/\{yyyy\}/i', '____', $maskLike);
		$maskLike = preg_replace('/\{yy\}/i', '__', $maskLike);
		$maskLike = preg_replace('/\{y\}/i', '_', $maskLike);
		$maskLike = preg_replace('/\{mm\}/i', '__', $maskLike);
		$maskLike = preg_replace('/\{dd\}/i', '__', $maskLike);
		$maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'), $counterpadded, $maskLike);
		if ($maskrefclient) {
			// @phan-suppress-next-line PhanParamSuspiciousOrder
			$maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'), str_pad("", dol_strlen($maskrefclient), "_"), $maskLike);
		}
		if ($masktype) {
			$maskLike = str_replace(dol_string_nospecial('{'.$masktype.'}'), $masktype_value, $maskLike);
		}
		if ($maskuser) {
			$maskLike = str_replace(dol_string_nospecial('{'.$maskuser.'}'), $maskuser_value, $maskLike);
		}

		$ref = '';
		$sql = "SELECT ".$field." as ref";
		$sql .= " FROM ".MAIN_DB_PREFIX.$table;
		$sql .= " WHERE ".$field." LIKE '".$db->escape($maskLike) . (getDolGlobalString('SEARCH_FOR_NEXT_VAL_ON_START_ONLY') ? "%" : "") . "'";
		$sql .= " AND ".$field." NOT LIKE '%PROV%'";
		if ($bentityon) { // only if entity enable
			$sql .= " AND entity IN (".getEntity($sharetable).")";
		} elseif (!empty($forceentity)) {
			$sql .= " AND entity IN (".$db->sanitize($forceentity).")";
		}
		if ($where) {
			$sql .= $where;
		}
		if ($sqlwhere) {
			$sql .= " AND ".$sqlwhere;
		}

		dol_syslog("functions2::get_next_value mode=".$mode, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$ref = $obj->ref;
			}
		} else {
			dol_print_error($db);
		}

		$numFinal = $ref;
	} elseif ($mode == 'next') {
		$counter++;
		$maskrefclient_counter = 0;

		// If value for $counter has a length higher than $maskcounter chars
		if ($counter >= pow(10, dol_strlen($maskcounter))) {
			$counter = 'ErrorMaxNumberReachForThisMask';
		}

		if (!empty($maskrefclient_maskcounter)) {
			//print "maskrefclient_maskcounter=".$maskrefclient_maskcounter." maskwithnocode=".$maskwithnocode." maskrefclient=".$maskrefclient."\n<br>";

			// Define $sqlstring
			$maskrefclient_posnumstart = strpos($maskwithnocode, $maskrefclient_maskcounter, strpos($maskwithnocode, $maskrefclient)); // Pos of counter in final string (from 0 to ...)
			if ($maskrefclient_posnumstart <= 0) {
				return 'ErrorBadMask';
			}
			$maskrefclient_sqlstring = 'SUBSTRING('.$field.', '.($maskrefclient_posnumstart + 1).', '.dol_strlen($maskrefclient_maskcounter).')';
			//print "x".$sqlstring;

			// Define $maskrefclient_maskLike
			$maskrefclient_maskLike = dol_string_nospecial($mask);
			$maskrefclient_maskLike = str_replace("%", "_", $maskrefclient_maskLike);
			// Replace protected special codes with matching number of _ as wild card character
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{yyyy}'), '____', $maskrefclient_maskLike);
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{yy}'), '__', $maskrefclient_maskLike);
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{y}'), '_', $maskrefclient_maskLike);
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{mm}'), '__', $maskrefclient_maskLike);
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{dd}'), '__', $maskrefclient_maskLike);
			// @phan-suppress-next-line PhanParamSuspiciousOrder
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{'.$masktri.'}'), str_pad("", dol_strlen($maskcounter), "_"), $maskrefclient_maskLike);
			// @phan-suppress-next-line PhanParamSuspiciousOrder
			$maskrefclient_maskLike = str_replace(dol_string_nospecial('{'.$maskrefclient.'}'), $maskrefclient_clientcode.str_pad("", dol_strlen($maskrefclient_maskcounter), "_"), $maskrefclient_maskLike);

			// Get counter in database
			$maskrefclient_sql = "SELECT MAX(".$maskrefclient_sqlstring.") as val";
			$maskrefclient_sql .= " FROM ".MAIN_DB_PREFIX.$table;
			//$sql.= " WHERE ".$field." not like '(%'";
			$maskrefclient_sql .= " WHERE ".$field." LIKE '".$db->escape($maskrefclient_maskLike) . (getDolGlobalString('SEARCH_FOR_NEXT_VAL_ON_START_ONLY') ? "%" : "") . "'";
			if ($bentityon) { // only if entity enable
				$maskrefclient_sql .= " AND entity IN (".getEntity($sharetable).")";
			} elseif (!empty($forceentity)) {
				$sql .= " AND entity IN (".$db->sanitize($forceentity).")";
			}
			if ($where) {
				$maskrefclient_sql .= $where; //use the same optional where as general mask
			}
			if ($sqlwhere) {
				$maskrefclient_sql .= ' AND '.$sqlwhere; //use the same sqlwhere as general mask
			}
			$maskrefclient_sql .= " AND (SUBSTRING(".$field.", ".(strpos($maskwithnocode, $maskrefclient) + 1).", ".dol_strlen($maskrefclient_maskclientcode).") = '".$db->escape($maskrefclient_clientcode)."')";

			dol_syslog("functions2::get_next_value maskrefclient", LOG_DEBUG);
			$maskrefclient_resql = $db->query($maskrefclient_sql);
			if ($maskrefclient_resql) {
				$maskrefclient_obj = $db->fetch_object($maskrefclient_resql);
				$maskrefclient_counter = $maskrefclient_obj->val;
			} else {
				dol_print_error($db);
			}

			if (empty($maskrefclient_counter) || preg_match('/[^0-9]/i', $maskrefclient_counter)) {
				$maskrefclient_counter = $maskrefclient_maskoffset;
			}
			$maskrefclient_counter++;
		}

		// Build numFinal
		$numFinal = $mask;

		// We replace special codes except refclient
		if (!empty($yearoffsettype) && !is_numeric($yearoffsettype) && $yearoffsettype != '=') {	// yearoffsettype is - or +, so we don't want current year
			$numFinal = preg_replace('/\{yyyy\}/i', (string) ((int) date("Y", $date) + $yearoffset), $numFinal);
			$numFinal = preg_replace('/\{yy\}/i', (string) ((int) date("y", $date) + $yearoffset), $numFinal);
			$numFinal = preg_replace('/\{y\}/i', (string) ((int) substr((string) date("y", $date), 1, 1) + $yearoffset), $numFinal);
		} else { // we want yyyy to be current year
			$numFinal = preg_replace('/\{yyyy\}/i', date("Y", $date), $numFinal);
			$numFinal = preg_replace('/\{yy\}/i', date("y", $date), $numFinal);
			$numFinal = preg_replace('/\{y\}/i', substr(date("y", $date), 1, 1), $numFinal);
		}
		$numFinal = preg_replace('/\{mm\}/i', date("m", $date), $numFinal);
		$numFinal = preg_replace('/\{dd\}/i', date("d", $date), $numFinal);

		// Now we replace the counter
		$maskbefore = '{'.$masktri.'}';
		$maskafter = str_pad($counter, dol_strlen($maskcounter), "0", STR_PAD_LEFT);
		//print 'x'.$numFinal.' - '.$maskbefore.' - '.$maskafter.'y';exit;
		$numFinal = str_replace($maskbefore, $maskafter, $numFinal);

		// Now we replace the refclient
		if ($maskrefclient) {
			//print "maskrefclient=".$maskrefclient." maskrefclient_counter=".$maskrefclient_counter." maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode." maskrefclient_clientcode=".$maskrefclient_clientcode." maskrefclient_maskcounter=".$maskrefclient_maskcounter."\n<br>";exit;
			$maskrefclient_maskbefore = '{'.$maskrefclient.'}';
			$maskrefclient_maskafter = $maskrefclient_clientcode;
			if (dol_strlen($maskrefclient_maskcounter) > 0) {
				$maskrefclient_maskafter .= str_pad($maskrefclient_counter, dol_strlen($maskrefclient_maskcounter), "0", STR_PAD_LEFT);
			}
			$numFinal = str_replace($maskrefclient_maskbefore, $maskrefclient_maskafter, $numFinal);
		}

		// Now we replace the type
		if ($masktype) {
			$masktype_maskbefore = '{'.$masktype.'}';
			$masktype_maskafter = $masktype_value;
			$numFinal = str_replace($masktype_maskbefore, $masktype_maskafter, $numFinal);
		}

		// Now we replace the user
		if ($maskuser) {
			$maskuser_maskbefore = '{'.$maskuser.'}';
			$maskuser_maskafter = $maskuser_value;
			$numFinal = str_replace($maskuser_maskbefore, $maskuser_maskafter, $numFinal);
		}
	} else {
		$numFinal = "ErrorBadMode";
		dol_syslog("functions2::get_next_value ErrorBadMode '$mode'", LOG_ERR);
	}

	dol_syslog("functions2::get_next_value return ".$numFinal, LOG_DEBUG);
	return $numFinal;
}

/**
 * Get string from "$start" up to "$end"
 *
 * If string is "STARTcontentEND" and $start is "START" and $end is "END",
 * then this function returns "content"
 *
 * @param   string  $string     String to test
 * @param   string  $start      String Value for start
 * @param   string  $end        String Value for end
 * @return  string              Return part of string
 */
function get_string_between($string, $start, $end)
{
	$ini = strpos($string, $start);
	if ($ini === false) {
		return '';
	}
	$ini += strlen($start);
	$endpos = strpos($string, $end, $ini);
	if ($endpos === false) {
		return '';
	}
	return substr($string, $ini, $endpos - $ini);
}

/**
 * Check value
 *
 * @param 	string	$mask		Mask to use
 * @param 	string	$value		Value
 * @return	int|string		    Return integer <0 or error string if KO, 0 if OK
 */
function check_value($mask, $value)
{
	$result = 0;

	$hasglobalcounter = false;
	// Extract value for mask counter, mask raz and mask offset
	$reg = array();
	if (preg_match('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i', $mask, $reg)) {
		$masktri = $reg[1].(isset($reg[2]) ? $reg[2] : '').(isset($reg[3]) ? $reg[3] : '');
		$maskcounter = $reg[1];
		$hasglobalcounter = true;
	} else {
		// setting some defaults so the rest of the code won't fail if there is a third party counter
		$masktri = '00000';
		$maskcounter = '00000';
	}
	$maskraz = -1;
	$maskoffset = 0;
	if (dol_strlen($maskcounter) < 3) {
		return 'ErrorCounterMustHaveMoreThan3Digits';
	}

	// Extract value for third party mask counter
	$regClientRef = array();
	if (preg_match('/\{(c+)(0*)\}/i', $mask, $regClientRef)) {
		$maskrefclient = $regClientRef[1].$regClientRef[2];
		$maskrefclient_maskclientcode = $regClientRef[1];
		$maskrefclient_maskcounter = $regClientRef[2];
		$maskrefclient_maskoffset = 0; //default value of maskrefclient_counter offset
		$maskrefclient_clientcode = substr('', 0, dol_strlen($maskrefclient_maskclientcode)); //get n first characters of client code to form maskrefclient_clientcode
		$maskrefclient_clientcode = str_pad($maskrefclient_clientcode, dol_strlen($maskrefclient_maskclientcode), "#", STR_PAD_RIGHT); //padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
		$maskrefclient_clientcode = dol_string_nospecial($maskrefclient_clientcode); //sanitize maskrefclient_clientcode for sql insert and sql select like
		if (dol_strlen($maskrefclient_maskcounter) > 0 && dol_strlen($maskrefclient_maskcounter) < 3) {
			return 'ErrorCounterMustHaveMoreThan3Digits';
		}
	} else {
		$maskrefclient = '';
	}

	// fail if there is neither a global nor a third party counter
	if (!$hasglobalcounter && ($maskrefclient_maskcounter == '')) {
		return 'ErrorBadMask';
	}

	$maskwithonlyymcode = $mask;
	$maskwithonlyymcode = preg_replace('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i', $maskcounter, $maskwithonlyymcode);
	$maskwithonlyymcode = preg_replace('/\{dd\}/i', 'dd', $maskwithonlyymcode);
	$maskwithonlyymcode = preg_replace('/\{(c+)(0*)\}/i', $maskrefclient, $maskwithonlyymcode);
	$maskwithnocode = $maskwithonlyymcode;
	$maskwithnocode = preg_replace('/\{yyyy\}/i', 'yyyy', $maskwithnocode);
	$maskwithnocode = preg_replace('/\{yy\}/i', 'yy', $maskwithnocode);
	$maskwithnocode = preg_replace('/\{y\}/i', 'y', $maskwithnocode);
	$maskwithnocode = preg_replace('/\{mm\}/i', 'mm', $maskwithnocode);
	// Now maskwithnocode = 0000ddmmyyyyccc for example
	// and maskcounter    = 0000 for example
	//print "maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";

	// If an offset is asked
	if (!empty($reg[2]) && preg_match('/^\+/', $reg[2])) {
		$maskoffset = preg_replace('/^\+/', '', $reg[2]);
	}
	if (!empty($reg[3]) && preg_match('/^\+/', $reg[3])) {
		$maskoffset = preg_replace('/^\+/', '', $reg[3]);
	}

	// Define $sqlwhere

	// If a restore to zero after a month is asked we check if there is already a value for this year.
	if (!empty($reg[2]) && preg_match('/^@/', $reg[2])) {
		$maskraz = preg_replace('/^@/', '', $reg[2]);
	}
	if (!empty($reg[3]) && preg_match('/^@/', $reg[3])) {
		$maskraz = preg_replace('/^@/', '', $reg[3]);
	}
	if ($maskraz >= 0) {
		if ($maskraz == 99) {
			$maskraz = (int) date('m');
			$resetEveryMonth = true;
		}
		if ($maskraz > 12) {
			return 'ErrorBadMaskBadRazMonth';
		}

		// Define reg
		if ($maskraz > 1 && !preg_match('/^(.*)\{(y+)\}\{(m+)\}/i', $maskwithonlyymcode, $reg)) {
			return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';
		}
		if ($maskraz <= 1 && !preg_match('/^(.*)\{(y+)\}/i', $maskwithonlyymcode, $reg)) {
			return 'ErrorCantUseRazIfNoYearInMask';
		}
		//print "x".$maskwithonlyymcode." ".$maskraz;
	}
	//print "masktri=".$masktri." maskcounter=".$maskcounter." maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

	if (function_exists('mb_strrpos')) {
		$posnumstart = mb_strrpos($maskwithnocode, $maskcounter, 0, 'UTF-8');
	} else {
		$posnumstart = strrpos($maskwithnocode, $maskcounter);
	}	// Pos of counter in final string (from 0 to ...)
	if ($posnumstart < 0) {
		return 'ErrorBadMaskFailedToLocatePosOfSequence';
	}

	// Check we have a number in $value at position ($posnumstart+1).', '.dol_strlen($maskcounter)
	// TODO

	// Check length
	$len = dol_strlen($maskwithnocode);
	if (dol_strlen($value) != $len) {
		$result = -1;
	}

	dol_syslog("functions2::check_value result=".$result, LOG_DEBUG);
	return $result;
}

/**
 *	Convert a binary data to string that represent hexadecimal value
 *
 *	@param   string		$bin		Value to convert
 *	@param   boolean	$pad      	Add 0
 *	@param   boolean	$upper		Convert to tupper
 *	@return  string					x
 */
function binhex($bin, $pad = false, $upper = false)
{
	$last = dol_strlen($bin) - 1;
	$x = 0;
	for ($i = 0; $i <= $last; $i++) {
		$x += ($bin[$last - $i] ? 1 : 0) << $i;
	}
	$x = dechex($x);
	if ($pad) {
		while (dol_strlen($x) < intval(dol_strlen($bin)) / 4) {
			$x = "0$x";
		}
	}
	if ($upper) {
		$x = strtoupper($x);
	}
	return $x;
}

/**
 *	Convert an hexadecimal string into a binary string
 *
 *	@param	string	$hexa		Hexadecimal string to convert (example: 'FF')
 *	@return string	    		bin
 */
function hexbin($hexa)
{
	$bin = '';
	$strLength = dol_strlen($hexa);
	for ($i = 0; $i < $strLength; $i++) {
		$bin .= str_pad(decbin(hexdec($hexa[$i])), 4, '0', STR_PAD_LEFT);
	}
	return $bin;
}

/**
 *	Retourne le numero de la semaine par rapport a une date
 *
 *	@param	string	$time   	Date au format 'timestamp'
 *	@return string					Number of week
 */
function numero_semaine($time)
{
	$stime = dol_print_date($time, '%Y-%m-%d');

	if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?/i', $stime, $reg)) {
		// Date est au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
		$annee = (int) $reg[1];
		$mois = (int) $reg[2];
		$jour = (int) $reg[3];
	}

	/*
	 * Norme ISO-8601:
	 * - Week 1 of the year contains Jan 4th, or contains the first Thursday of January.
	 * - Most years have 52 weeks, but 53 weeks for years starting on a Thursday and bisectile years that start on a Wednesday.
	 * - The first day of a week is Monday
	 */

	// Definition du Jeudi de la semaine
	if ((int) date("w", mktime(12, 0, 0, $mois, $jour, $annee)) == 0) { // Dimanche
		$jeudiSemaine = mktime(12, 0, 0, $mois, $jour, $annee) - 3 * 24 * 60 * 60;
	} elseif (date("w", mktime(12, 0, 0, $mois, $jour, $annee)) < 4) { // du Lundi au Mercredi
		$jeudiSemaine = mktime(12, 0, 0, $mois, $jour, $annee) + (4 - (int) date("w", mktime(12, 0, 0, $mois, $jour, $annee))) * 24 * 60 * 60;
	} elseif ((int) date("w", mktime(12, 0, 0, $mois, $jour, $annee)) > 4) { // du Vendredi au Samedi
		$jeudiSemaine = mktime(12, 0, 0, $mois, $jour, $annee) - ((int) date("w", mktime(12, 0, 0, $mois, $jour, $annee)) - 4) * 24 * 60 * 60;
	} else { // Jeudi
		$jeudiSemaine = mktime(12, 0, 0, $mois, $jour, $annee);
	}

	// Definition du premier Jeudi de l'annee
	if ((int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine))) == 0) { // Dimanche
		$premierJeudiAnnee = mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine)) + 4 * 24 * 60 * 60;
	} elseif ((int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine))) < 4) { // du Lundi au Mercredi
		$premierJeudiAnnee = mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine)) + (4 - (int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine)))) * 24 * 60 * 60;
	} elseif ((int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine))) > 4) { // du Vendredi au Samedi
		$premierJeudiAnnee = mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine)) + (7 - ((int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine))) - 4)) * 24 * 60 * 60;
	} else { // Jeudi
		$premierJeudiAnnee = mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine));
	}

	// Definition du numero de semaine: nb de jours entre "premier Jeudi de l'annee" et "Jeudi de la semaine";
	$numeroSemaine = (
		(
			(int) date("z", mktime(12, 0, 0, (int) date("m", $jeudiSemaine), (int) date("d", $jeudiSemaine), (int) date("Y", $jeudiSemaine)))
		-
		(int) date("z", mktime(12, 0, 0, (int) date("m", $premierJeudiAnnee), (int) date("d", $premierJeudiAnnee), (int) date("Y", $premierJeudiAnnee)))
		) / 7
	) + 1;

	// Cas particulier de la semaine 53
	if ($numeroSemaine == 53) {
		// Les annees qui commencent un Jeudi et les annees bissextiles commencant un Mercredi en possedent 53
		if (
			((int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine))) == 4)
			|| (
				((int) date("w", mktime(12, 0, 0, 1, 1, (int) date("Y", $jeudiSemaine))) == 3)
				&& ((int) date("z", mktime(12, 0, 0, 12, 31, (int) date("Y", $jeudiSemaine))) == 365)
			)
		) {
			$numeroSemaine = 53;
		} else {
			$numeroSemaine = 1;
		}
	}

	//echo $jour."-".$mois."-".$annee." (".date("d-m-Y",$premierJeudiAnnee)." - ".date("d-m-Y",$jeudiSemaine).") -> ".$numeroSemaine."<BR>";

	return sprintf("%02d", $numeroSemaine);
}

/**
 *	Convertit une masse d'une unite vers une autre unite
 *
 *	@param	float	$weight    		Masse a convertir
 *	@param  int		$from_unit 		Unite originale en puissance de 10
 *	@param  int		$to_unit   		Nouvelle unite  en puissance de 10
 *	@return float	        		Masse convertie
 */
function weight_convert($weight, &$from_unit, $to_unit)
{
	/* Pour convertire 320 gr en Kg appeler
	 *  $f = -3
	 *  weigh_convert(320, $f, 0) retournera 0.32
	 *
	 */
	$weight = is_numeric($weight) ? $weight : 0;
	while ($from_unit != $to_unit) {
		if ($from_unit > $to_unit) {
			$weight = $weight * 10;
			$from_unit = $from_unit - 1;
			$weight = weight_convert($weight, $from_unit, $to_unit);
		}
		if ($from_unit < $to_unit) {
			$weight = $weight / 10;
			$from_unit = $from_unit + 1;
			$weight = weight_convert($weight, $from_unit, $to_unit);
		}
	}

	return $weight;
}

/**
 *	Save personal parameter
 *
 *	@param	DoliDB	$db         Handler database
 *	@param	Conf	$conf		Object conf
 *	@param	User	$user      	Object user
 *	@param	array	$tab        Array (key=>value) with all parameters to save/update
 *	@return int         		Return integer <0 if KO, >0 if OK
 *
 *	@see		dolibarr_get_const(), dolibarr_set_const(), dolibarr_del_const()
 */
function dol_set_user_param($db, $conf, &$user, $tab)
{
	// Verification parameters
	if (count($tab) < 1) {
		return -1;
	}

	$db->begin();

	// We remove old parameters for all keys in $tab
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
	$sql .= " WHERE fk_user = ".((int) $user->id);
	$sql .= " AND entity = ".((int) $conf->entity);
	$sql .= " AND param in (";
	$i = 0;
	foreach ($tab as $key => $value) {
		if ($i > 0) {
			$sql .= ',';
		}
		$sql .= "'".$db->escape($key)."'";
		$i++;
	}
	$sql .= ")";
	dol_syslog("functions2.lib::dol_set_user_param", LOG_DEBUG);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		$db->rollback();
		return -1;
	}

	foreach ($tab as $key => $value) {
		// Set new parameters
		if ($value) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,entity,param,value)";
			$sql .= " VALUES (".((int) $user->id).",".((int) $conf->entity).",";
			$sql .= " '".$db->escape($key)."','".$db->escape($value)."')";

			dol_syslog("functions2.lib::dol_set_user_param", LOG_DEBUG);
			$result = $db->query($sql);
			if (!$result) {
				dol_print_error($db);
				$db->rollback();
				return -1;
			}
			$user->conf->$key = $value;
			//print "key=".$key." user->conf->key=".$user->conf->$key;
		} else {
			unset($user->conf->$key);
		}
	}

	$db->commit();
	return 1;
}

/**
 *	Returns formatted reduction
 *
 *	@param	int			$reduction		Reduction percentage
 *	@param	Translate	$langs			Output language
 *	@return	string						Formatted reduction
 */
function dol_print_reduction($reduction, $langs)
{
	$string = '';
	if ($reduction == 100) {
		$string = $langs->transnoentities("Offered");
	} else {
		$string = vatrate($reduction, true);
	}

	return $string;
}

/**
 * 	Return OS version.
 *  Note that PHP_OS returns only OS (not version) and OS PHP was built on, not necessarily OS PHP runs on.
 *
 *  @param 		string		$option 	Option string
 * 	@return		string					OS version
 */
function version_os($option = '')
{
	if ($option == 'smr') {
		$osversion = php_uname('s').' '.php_uname('m').' '.php_uname('r');
	} else {
		$osversion = php_uname();
	}
	return $osversion;
}

/**
 * 	Return PHP version
 *
 * 	@return		string			PHP version
 *  @see		versionphparray(), versioncompare()
 */
function version_php()
{
	return phpversion();
}

/**
 * 	Return DB version
 *
 * 	@return		string			PHP version
 */
function version_db()
{
	global $db;
	if (is_object($db) && method_exists($db, 'getVersion')) {
		return $db->getVersion();
	}
	return '';
}

/**
 * 	Return Dolibarr version
 *
 * 	@return		string			Dolibarr version
 *  @see		versiondolibarrarray(), versioncompare()
 */
function version_dolibarr()
{
	return DOL_VERSION;
}

/**
 * 	Return web server version
 *
 * 	@return		string			Web server version
 */
function version_webserver()
{
	return $_SERVER["SERVER_SOFTWARE"];
}

/**
 * 	Return list of activated modules usable for document generation
 *
 * 	@param	DoliDB		$db				    Database handler
 * 	@param	string		$type			    Type of models (company, invoice, ...)
 *  @param  int		    $maxfilenamelength  Max length of value to show
 * 	@return	array|int			    		0 if no module is activated, or array(key=>label). For modules that need directory scan, key is completed with ":filename".
 */
function getListOfModels($db, $type, $maxfilenamelength = 0)
{
	global $conf, $langs;
	$liste = array();
	$found = 0;
	$dirtoscan = '';

	$sql = "SELECT nom as id, nom as doc_template_name, libelle as label, description as description";
	$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
	$sql .= " WHERE type = '".$db->escape($type)."'";
	$sql .= " AND entity IN (0,".$conf->entity.")";
	$sql .= " ORDER BY description DESC";

	dol_syslog('/core/lib/function2.lib.php::getListOfModels', LOG_DEBUG);
	$resql_models = $db->query($sql);
	if ($resql_models) {
		$num = $db->num_rows($resql_models);
		$i = 0;
		while ($i < $num) {
			$found = 1;

			$obj = $db->fetch_object($resql_models);

			// If this generation module needs to scan a directory, then description field is filled
			// with the constant that contains list of directories to scan (COMPANY_ADDON_PDF_ODT_PATH, ...).
			if (!empty($obj->description)) {	// A list of directories to scan is defined
				include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				$const = $obj->description;
				$dirtoscan = preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString($const)));

				$listoffiles = array();

				// Now we add models found in directories scanned
				$listofdir = explode(',', $dirtoscan);
				foreach ($listofdir as $key => $tmpdir) {
					$tmpdir = trim($tmpdir);
					$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
					if (!$tmpdir) {
						unset($listofdir[$key]);
						continue;
					}
					if (is_dir($tmpdir)) {
						// all type of template is allowed
						$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '', null, 'name', SORT_ASC, 0);
						if (count($tmpfiles)) {
							$listoffiles = array_merge($listoffiles, $tmpfiles);
						}
					}
				}

				if (count($listoffiles)) {
					foreach ($listoffiles as $record) {
						$max = ($maxfilenamelength ? $maxfilenamelength : 28);
						$liste[$obj->id.':'.$record['fullname']] = dol_trunc($record['name'], $max, 'middle');
					}
				} else {
					$liste[0] = $obj->label.': '.$langs->trans("None");
				}
			} else {
				if ($type == 'member' && $obj->doc_template_name == 'standard') {   // Special case, if member template, we add variant per format
					global $_Avery_Labels;
					include_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
					foreach ($_Avery_Labels as $key => $val) {
						$liste[$obj->id.':'.$key] = ($obj->label ? $obj->label : $obj->doc_template_name).' '.$val['name'];
					}
				} else {
					// Common usage
					$liste[$obj->id] = $obj->label ? $obj->label : $obj->doc_template_name;
				}
			}
			$i++;
		}
	} else {
		dol_print_error($db);
		return -1;
	}

	if ($found) {
		return $liste;
	} else {
		return 0;
	}
}

/**
 * This function evaluates a string that should be a valid IPv4
 * Note: For ip 169.254.0.0, it returns 0 with some PHP (5.6.24) and 2 with some minor patches of PHP (5.6.25). See https://github.com/php/php-src/pull/1954.
 *
 * @param	string $ip IP Address
 * @return	int 0 if not valid or reserved range, 1 if valid and public IP, 2 if valid and private range IP
 */
function is_ip($ip)
{
	// First we test if it is a valid IPv4
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		// Then we test if it is a private range
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
			return 2;
		}

		// Then we test if it is a reserved range
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
			return 0;
		}

		return 1;
	}

	return 0;
}

/**
 *  Build a login from lastname, firstname
 *
 *  @param	string		$lastname		Lastname
 *  @param  string		$firstname		Firstname
 *	@return	string						Login
 */
function dol_buildlogin($lastname, $firstname)
{
	//$conf->global->MAIN_BUILD_LOGIN_RULE = 'f.lastname';
	$charforseparator = getDolGlobalString("MAIN_USER_SEPARATOR_CHAR_FOR_GENERATED_LOGIN", '.');
	if ($charforseparator == 'none') {
		$charforseparator = '';
	}

	if (getDolGlobalString('MAIN_BUILD_LOGIN_RULE') == 'f.lastname') {	// f.lastname
		$login = strtolower(dol_string_unaccent(dol_trunc($firstname, 1, 'right', 'UTF-8', 1)));
		$login .= ($login ? $charforseparator : '');
		$login .= strtolower(dol_string_unaccent($lastname));
		$login = dol_string_nospecial($login, ''); // For special names
	} else {	// firstname.lastname
		$login = strtolower(dol_string_unaccent($firstname));
		$login .= ($login ? $charforseparator : '');
		$login .= strtolower(dol_string_unaccent($lastname));
		$login = dol_string_nospecial($login, ''); // For special names
	}

	// TODO Add a hook to allow external modules to suggest new rules

	return $login;
}

/**
 *  Return array to use for SoapClient constructor
 *
 *  @return     array
 */
function getSoapParams()
{
	global $conf;

	$params = array();
	$proxyuse = (!getDolGlobalString('MAIN_PROXY_USE') ? false : true);
	$proxyhost = (!getDolGlobalString('MAIN_PROXY_USE') ? false : $conf->global->MAIN_PROXY_HOST);
	$proxyport = (!getDolGlobalString('MAIN_PROXY_USE') ? false : $conf->global->MAIN_PROXY_PORT);
	$proxyuser = (!getDolGlobalString('MAIN_PROXY_USE') ? false : $conf->global->MAIN_PROXY_USER);
	$proxypass = (!getDolGlobalString('MAIN_PROXY_USE') ? false : $conf->global->MAIN_PROXY_PASS);
	$timeout = (!getDolGlobalString('MAIN_USE_CONNECT_TIMEOUT') ? 10 : $conf->global->MAIN_USE_CONNECT_TIMEOUT); // Connection timeout
	$response_timeout = (!getDolGlobalString('MAIN_USE_RESPONSE_TIMEOUT') ? 30 : $conf->global->MAIN_USE_RESPONSE_TIMEOUT); // Response timeout
	//print extension_loaded('soap');
	if ($proxyuse) {
		$params = array('connection_timeout' => $timeout,
					  'response_timeout' => $response_timeout,
					  'proxy_use'      => 1,
					  'proxy_host'     => $proxyhost,
					  'proxy_port'     => $proxyport,
					  'proxy_login'    => $proxyuser,
					  'proxy_password' => $proxypass,
					  'trace'		   => 1
		);
	} else {
		$params = array('connection_timeout' => $timeout,
					  'response_timeout' => $response_timeout,
					  'proxy_use'      => 0,
					  'proxy_host'     => false,
					  'proxy_port'     => false,
					  'proxy_login'    => false,
					  'proxy_password' => false,
					  'trace'		   => 1
		);
	}
	return $params;
}


/**
 * Return link url to an object
 *
 * @param 	int		$objectid		Id of record
 * @param 	string	$objecttype		Type of object ('invoice', 'order', 'expedition_bon', 'myobject@mymodule', ...)
 * @param 	int		$withpicto		Picto to show
 * @param 	string	$option			More options
 * @return	string					URL of link to object id/type
 */
function dolGetElementUrl($objectid, $objecttype, $withpicto = 0, $option = '')
{
	global $db, $conf, $langs;

	$ret = '';
	$regs = array();

	// If we ask a resource form external module (instead of default path)
	if (preg_match('/^([^@]+)@([^@]+)$/i', $objecttype, $regs)) {
		$myobject = $regs[1];
		$module = $regs[2];
	} else {
		// Parse $objecttype (ex: project_task)
		$module = $myobject = $objecttype;
		if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
			$module = $regs[1];
			$myobject = $regs[2];
		}
	}

	// Generic case for $classpath
	$classpath = $module.'/class';

	// Special cases, to work with non standard path
	if ($objecttype == 'facture' || $objecttype == 'invoice') {
		$langs->load('bills');
		$classpath = 'compta/facture/class';
		$module = 'facture';
		$myobject = 'facture';
	} elseif ($objecttype == 'commande' || $objecttype == 'order') {
		$langs->load('orders');
		$classpath = 'commande/class';
		$module = 'commande';
		$myobject = 'commande';
	} elseif ($objecttype == 'propal') {
		$langs->load('propal');
		$classpath = 'comm/propal/class';
	} elseif ($objecttype == 'supplier_proposal') {
		$langs->load('supplier_proposal');
		$classpath = 'supplier_proposal/class';
	} elseif ($objecttype == 'shipping') {
		$langs->load('sendings');
		$classpath = 'expedition/class';
		$myobject = 'expedition';
		$module = 'expedition_bon';
	} elseif ($objecttype == 'delivery') {
		$langs->load('deliveries');
		$classpath = 'delivery/class';
		$myobject = 'delivery';
		$module = 'delivery_note';
	} elseif ($objecttype == 'contract') {
		$langs->load('contracts');
		$classpath = 'contrat/class';
		$module = 'contrat';
		$myobject = 'contrat';
	} elseif ($objecttype == 'member') {
		$langs->load('members');
		$classpath = 'adherents/class';
		$module = 'adherent';
		$myobject = 'adherent';
	} elseif ($objecttype == 'cabinetmed_cons') {
		$classpath = 'cabinetmed/class';
		$module = 'cabinetmed';
		$myobject = 'cabinetmedcons';
	} elseif ($objecttype == 'fichinter') {
		$langs->load('interventions');
		$classpath = 'fichinter/class';
		$module = 'ficheinter';
		$myobject = 'fichinter';
	} elseif ($objecttype == 'project') {
		$langs->load('projects');
		$classpath = 'projet/class';
		$module = 'projet';
	} elseif ($objecttype == 'task') {
		$langs->load('projects');
		$classpath = 'projet/class';
		$module = 'projet';
		$myobject = 'task';
	} elseif ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$module = 'stock';
		$myobject = 'stock';
	} elseif ($objecttype == 'inventory') {
		$classpath = 'product/inventory/class';
		$module = 'stock';
		$myobject = 'inventory';
	} elseif ($objecttype == 'mo') {
		$classpath = 'mrp/class';
		$module = 'mrp';
		$myobject = 'mo';
	} elseif ($objecttype == 'productlot') {
		$classpath = 'product/stock/class';
		$module = 'stock';
		$myobject = 'productlot';
	}

	// Generic case for $classfile and $classname
	$classfile = strtolower($myobject);
	$classname = ucfirst($myobject);
	//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement." classfile=".$classfile." classname=".$classname." classpath=".$classpath;

	if ($objecttype == 'invoice_supplier') {
		$classfile = 'fournisseur.facture';
		$classname = 'FactureFournisseur';
		$classpath = 'fourn/class';
		$module = 'fournisseur';
	} elseif ($objecttype == 'order_supplier') {
		$classfile = 'fournisseur.commande';
		$classname = 'CommandeFournisseur';
		$classpath = 'fourn/class';
		$module = 'fournisseur';
	} elseif ($objecttype == 'supplier_proposal') {
		$classfile = 'supplier_proposal';
		$classname = 'SupplierProposal';
		$classpath = 'supplier_proposal/class';
		$module = 'supplier_proposal';
	} elseif ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$classfile = 'entrepot';
		$classname = 'Entrepot';
	} elseif ($objecttype == 'facturerec') {
		$classpath = 'compta/facture/class';
		$classfile = 'facture-rec';
		$classname = 'FactureRec';
		$module = 'facture';
	} elseif ($objecttype == 'mailing') {
		$classpath = 'comm/mailing/class';
		$classfile = 'mailing';
		$classname = 'Mailing';
	}

	if (isModEnabled($module)) {
		$res = dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
		if ($res) {
			if (class_exists($classname)) {
				$object = new $classname($db);
				$res = $object->fetch($objectid);
				if ($res > 0) {
					$ret = $object->getNomUrl($withpicto, $option);
				} elseif ($res == 0) {
					$ret = $langs->trans('Deleted');
				}
				unset($object);
			} else {
				dol_syslog("Class with classname ".$classname." is unknown even after the include", LOG_ERR);
			}
		}
	}
	return $ret;
}


/**
 * Clean corrupted tree (orphelins linked to a not existing parent), record linked to themself and child-parent loop
 *
 * @param	DoliDB	$db					Database handler
 * @param	string	$tabletocleantree	Table to clean
 * @param	string	$fieldfkparent		Field name that contains id of parent
 * @return	int							Nb of records fixed/deleted
 */
function cleanCorruptedTree($db, $tabletocleantree, $fieldfkparent)
{
	$totalnb = 0;
	$listofid = array();
	$listofparentid = array();

	// Get list of all id in array listofid and all parents in array listofparentid
	$sql = "SELECT rowid, ".$fieldfkparent." as parent_id FROM ".MAIN_DB_PREFIX.$tabletocleantree;
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$listofid[] = $obj->rowid;
			if ($obj->parent_id > 0) {
				$listofparentid[$obj->rowid] = $obj->parent_id;
			}
			$i++;
		}
	} else {
		dol_print_error($db);
	}

	if (count($listofid)) {
		print 'Code requested to clean tree (may be to solve data corruption), so we check/clean orphelins and loops.'."<br>\n";

		// Check loops on each other
		$sql = "UPDATE ".MAIN_DB_PREFIX.$tabletocleantree." SET ".$fieldfkparent." = 0 WHERE ".$fieldfkparent." = rowid"; // So we update only records linked to themself
		$resql = $db->query($sql);
		if ($resql) {
			$nb = $db->affected_rows($sql);
			if ($nb > 0) {
				print '<br>Some record that were parent of themself were cleaned.';
			}

			$totalnb += $nb;
		}
		//else dol_print_error($db);

		// Check other loops
		$listofidtoclean = array();
		foreach ($listofparentid as $id => $pid) {
			// Check depth
			//print 'Analyse record id='.$id.' with parent '.$pid.'<br>';

			$cursor = $id;
			$arrayidparsed = array(); // We start from child $id
			while ($cursor > 0) {
				$arrayidparsed[$cursor] = 1;
				if ($arrayidparsed[$listofparentid[$cursor]]) {	// We detect a loop. A record with a parent that was already into child
					print 'Found a loop between id '.$id.' - '.$cursor.'<br>';
					unset($arrayidparsed);
					$listofidtoclean[$cursor] = $id;
					break;
				}
				$cursor = $listofparentid[$cursor];
			}

			if (count($listofidtoclean)) {
				break;
			}
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$tabletocleantree;
		$sql .= " SET ".$fieldfkparent." = 0";
		$sql .= " WHERE rowid IN (".$db->sanitize(implode(',', $listofidtoclean)).")"; // So we update only records detected wrong
		$resql = $db->query($sql);
		if ($resql) {
			$nb = $db->affected_rows($sql);
			if ($nb > 0) {
				// Removed orphelins records
				print '<br>Some records were detected to have parent that is a child, we set them as root record for id: ';
				print implode(',', $listofidtoclean);
			}

			$totalnb += $nb;
		}
		//else dol_print_error($db);

		// Check and clean orphelins
		$sql = "UPDATE ".MAIN_DB_PREFIX.$tabletocleantree;
		$sql .= " SET ".$fieldfkparent." = 0";
		$sql .= " WHERE ".$fieldfkparent." NOT IN (".$db->sanitize(implode(',', $listofid), 1).")"; // So we update only records linked to a non existing parent
		$resql = $db->query($sql);
		if ($resql) {
			$nb = $db->affected_rows($sql);
			if ($nb > 0) {
				// Removed orphelins records
				print '<br>Some orphelins were found and modified to be parent so records are visible again for id: ';
				print implode(',', $listofid);
			}

			$totalnb += $nb;
		}
		//else dol_print_error($db);

		print '<br>We fixed '.$totalnb.' record(s). Some records may still be corrupted. New check may be required.';
		return $totalnb;
	}
	return -1;
}


/**
 *	Convert an array with RGB value into hex RGB value.
 *  This is the opposite function of colorStringToArray
 *
 *  @param	array	$arraycolor			Array
 *  @param	string	$colorifnotfound	Color code to return if entry not defined or not a RGB format
 *  @return	string						RGB hex value (without # before). For example: 'FF00FF', '01FF02'
 *  @see	colorStringToArray(), colorHexToRgb()
 */
function colorArrayToHex($arraycolor, $colorifnotfound = '888888')
{
	if (!is_array($arraycolor)) {
		return $colorifnotfound;
	}
	if (empty($arraycolor)) {
		return $colorifnotfound;
	}
	return sprintf("%02s", dechex($arraycolor[0])).sprintf("%02s", dechex($arraycolor[1])).sprintf("%02s", dechex($arraycolor[2]));
}

/**
 *	Convert a string RGB value ('FFFFFF', '255,255,255') into an array RGB array(255,255,255).
 *  This is the opposite function of colorArrayToHex.
 *  If entry is already an array, return it.
 *
 *  @param	string	$stringcolor		String with hex (FFFFFF) or comma RGB ('255,255,255')
 *  @param	array	$colorifnotfound	Color code array to return if entry not defined
 *  @return	array   					RGB hex value (without # before). For example: FF00FF
 *  @see	colorArrayToHex(), colorHexToRgb()
 */
function colorStringToArray($stringcolor, $colorifnotfound = array(88, 88, 88))
{
	if (is_array($stringcolor)) {
		return $stringcolor; // If already into correct output format, we return as is
	}
	$reg = array();
	$tmp = preg_match('/^#?([0-9a-fA-F][0-9a-fA-F])([0-9a-fA-F][0-9a-fA-F])([0-9a-fA-F][0-9a-fA-F])$/', $stringcolor, $reg);
	if (!$tmp) {
		$tmp = explode(',', $stringcolor);
		if (count($tmp) < 3) {
			return $colorifnotfound;
		}
		return $tmp;
	}
	return array(hexdec($reg[1]), hexdec($reg[2]), hexdec($reg[3]));
}

/**
 * @param string 	$color 			the color you need to valid
 * @param boolean 	$allow_white 	in case of white isn't valid
 * @return boolean
 */
function colorValidateHex($color, $allow_white = true)
{
	if (!$allow_white && ($color === '#fff' || $color === '#ffffff')) {
		return false;
	}

	if (preg_match('/^#[a-f0-9]{6}$/i', $color)) { //hex color is valid
		return true;
	}
	return false;
}

/**
 * Change color to make it less aggressive (ratio is negative) or more aggressive (ratio is positive)
 *
 * @param 	string 		$hex			Color in hex ('#AA1122' or 'AA1122' or '#a12' or 'a12')
 * @param 	integer		$ratio			Default=-50. Note: 0=Component color is unchanged, -100=Component color become 88, +100=Component color become 00 or FF
 * @param	integer		$brightness 	Default=0. Adjust brightness. -100=Decrease brightness by 100%, +100=Increase of 100%.
 * @return string		New string of color
 * @see colorAdjustBrightness()
 */
function colorAgressiveness($hex, $ratio = -50, $brightness = 0)
{
	if (empty($ratio)) {
		$ratio = 0; // To avoid null
	}

	// Steps should be between -255 and 255. Negative = darker, positive = lighter
	$ratio = max(-100, min(100, $ratio));

	// Normalize into a six character long hex string
	$hex = str_replace('#', '', $hex);
	if (strlen($hex) == 3) {
		$hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
	}

	// Split into three parts: R, G and B
	$color_parts = str_split($hex, 2);
	$return = '#';

	foreach ($color_parts as $color) {
		$color = hexdec($color); // Convert to decimal
		if ($ratio > 0) {	// We increase aggressivity
			if ($color > 127) {
				$color += ((255 - $color) * ($ratio / 100));
			}
			if ($color < 128) {
				$color -= ($color * ($ratio / 100));
			}
		} else { // We decrease aggressiveness
			if ($color > 128) {
				$color -= (($color - 128) * (abs($ratio) / 100));
			}
			if ($color < 127) {
				$color += ((128 - $color) * (abs($ratio) / 100));
			}
		}
		if ($brightness > 0) {
			$color = ($color * (100 + abs($brightness)) / 100);
		} else {
			$color = ($color * (100 - abs($brightness)) / 100);
		}

		$color   = max(0, min(255, $color)); // Adjust color to stay into valid range
		$return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
	}

	//var_dump($hex.' '.$ratio.' -> '.$return);
	return $return;
}

/**
 * @param string 	$hex 		Color in hex ('#AA1122' or 'AA1122' or '#a12' or 'a12')
 * @param integer 	$steps 		Step/offset added to each color component. It should be between -255 and 255. Negative = darker, positive = lighter
 * @return string				New color with format '#AA1122'
 * @see colorAgressiveness()
 */
function colorAdjustBrightness($hex, $steps)
{
	// Steps should be between -255 and 255. Negative = darker, positive = lighter
	$steps = max(-255, min(255, $steps));

	// Normalize into a six character long hex string
	$hex = str_replace('#', '', $hex);
	if (strlen($hex) == 3) {
		$hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
	}

	// Split into three parts: R, G and B
	$color_parts = str_split($hex, 2);
	$return = '#';

	foreach ($color_parts as $color) {
		$color   = hexdec($color); // Convert to decimal
		$color   = max(0, min(255, $color + $steps)); // Adjust color
		$return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
	}

	return $return;
}

/**
 * @param string $hex color in hex
 * @param integer $percent 0 to 100
 * @return string
 */
function colorDarker($hex, $percent)
{
	$steps = intval(255 * $percent / 100) * -1;
	return colorAdjustBrightness($hex, $steps);
}

/**
 * @param string $hex color in hex
 * @param integer $percent 0 to 100
 * @return string
 */
function colorLighten($hex, $percent)
{
	$steps = intval(255 * $percent / 100);
	return colorAdjustBrightness($hex, $steps);
}


/**
 * @param string 		$hex 			color in hex
 * @param float|false	$alpha 			0 to 1 to add alpha channel
 * @param bool 			$returnArray	true=return an array instead, false=return string
 * @return string|array					String or array
 */
function colorHexToRgb($hex, $alpha = false, $returnArray = false)
{
	$string = '';
	$hex = str_replace('#', '', $hex);
	$length = strlen($hex);
	$rgb = array();
	$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
	$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
	$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
	if ($alpha !== false) {
		$rgb['a'] = (float) $alpha;
		$string = 'rgba('.implode(',', array_map('strval', $rgb)).')';
	} else {
		$string = 'rgb('.implode(',', array_map('strval', $rgb)).')';
	}

	if ($returnArray) {
		return $rgb;
	} else {
		return $string;
	}
}

/**
 * Color Hex to Hsl (used for style)
 *
 * @param	string 			$hex 			Color in hex
 * @param	float|false 	$alpha 			0 to 1 to add alpha channel
 * @param	bool 			$returnArray	true=return an array instead, false=return string
 * @return	string|array					String or array
 */
function colorHexToHsl($hex, $alpha = false, $returnArray = false)
{
	$hex = str_replace('#', '', $hex);
	$red = hexdec(substr($hex, 0, 2)) / 255;
	$green = hexdec(substr($hex, 2, 2)) / 255;
	$blue = hexdec(substr($hex, 4, 2)) / 255;

	$cmin = min($red, $green, $blue);
	$cmax = max($red, $green, $blue);
	$delta = $cmax - $cmin;

	if ($delta == 0) {
		$hue = 0;
	} elseif ($cmax === $red) {
		$hue = (($green - $blue) / $delta);
	} elseif ($cmax === $green) {
		$hue = ($blue - $red) / $delta + 2;
	} else {
		$hue = ($red - $green) / $delta + 4;
	}

	$hue = round($hue * 60);
	if ($hue < 0) {
		$hue += 360;
	}

	$lightness = (($cmax + $cmin) / 2);
	$saturation = $delta === 0 ? 0 : ($delta / (1 - abs(2 * $lightness - 1)));
	if ($saturation < 0) {
		$saturation += 1;
	}

	$lightness = round($lightness * 100);
	$saturation = round($saturation * 100);

	if ($returnArray) {
		return array(
			'h' => $hue,
			'l' => $lightness,
			's' => $saturation,
			'a' => $alpha === false ? 1 : $alpha
		);
	} elseif ($alpha) {
		return 'hsla('.$hue.', '.$saturation.', '.$lightness.' / '.$alpha.')';
	} else {
		return 'hsl('.$hue.', '.$saturation.', '.$lightness.')';
	}
}

/**
 * Applies the Cartesian product algorithm to an array
 * Source: http://stackoverflow.com/a/15973172
 *
 * @param   array $input    Array of products
 * @return  array           Array of combinations
 */
function cartesianArray(array $input)
{
	// filter out empty values
	$input = array_filter($input);

	$result = array(array());

	foreach ($input as $key => $values) {
		$append = array();

		foreach ($result as $product) {
			foreach ($values as $item) {
				$product[$key] = $item;
				$append[] = $product;
			}
		}

		$result = $append;
	}

	return $result;
}


/**
 * Get name of directory where the api_...class.php file is stored
 *
 * @param   string  $moduleobject     Module object name
 * @return  string              	  Directory name
 */
function getModuleDirForApiClass($moduleobject)
{
	$moduledirforclass = $moduleobject;
	if ($moduledirforclass != 'api') {
		$moduledirforclass = preg_replace('/api$/i', '', $moduledirforclass);
	}

	if ($moduleobject == 'contracts') {
		$moduledirforclass = 'contrat';
	} elseif (in_array($moduleobject, array('admin', 'login', 'setup', 'access', 'status', 'tools', 'documents'))) {
		$moduledirforclass = 'api';
	} elseif ($moduleobject == 'contact' || $moduleobject == 'contacts' || $moduleobject == 'customer' || $moduleobject == 'thirdparty' || $moduleobject == 'thirdparties') {
		$moduledirforclass = 'societe';
	} elseif ($moduleobject == 'propale' || $moduleobject == 'proposals') {
		$moduledirforclass = 'comm/propal';
	} elseif ($moduleobject == 'agenda' || $moduleobject == 'agendaevents') {
		$moduledirforclass = 'comm/action';
	} elseif ($moduleobject == 'adherent' || $moduleobject == 'members' || $moduleobject == 'memberstypes' || $moduleobject == 'subscriptions') {
		$moduledirforclass = 'adherents';
	} elseif ($moduleobject == 'don' || $moduleobject == 'donations') {
		$moduledirforclass = 'don';
	} elseif ($moduleobject == 'banque' || $moduleobject == 'bankaccounts') {
		$moduledirforclass = 'compta/bank';
	} elseif ($moduleobject == 'category' || $moduleobject == 'categorie') {
		$moduledirforclass = 'categories';
	} elseif ($moduleobject == 'order' || $moduleobject == 'orders') {
		$moduledirforclass = 'commande';
	} elseif ($moduleobject == 'shipments') {
		$moduledirforclass = 'expedition';
	} elseif ($moduleobject == 'multicurrencies') {
		$moduledirforclass = 'multicurrency';
	} elseif ($moduleobject == 'facture' || $moduleobject == 'invoice' || $moduleobject == 'invoices') {
		$moduledirforclass = 'compta/facture';
	} elseif ($moduleobject == 'project' || $moduleobject == 'projects' || $moduleobject == 'task' || $moduleobject == 'tasks') {
		$moduledirforclass = 'projet';
	} elseif ($moduleobject == 'stock' || $moduleobject == 'stockmovements' || $moduleobject == 'warehouses') {
		$moduledirforclass = 'product/stock';
	} elseif ($moduleobject == 'supplierproposals' || $moduleobject == 'supplierproposal' || $moduleobject == 'supplier_proposal') {
		$moduledirforclass = 'supplier_proposal';
	} elseif ($moduleobject == 'fournisseur' || $moduleobject == 'supplierinvoices' || $moduleobject == 'supplierorders') {
		$moduledirforclass = 'fourn';
	} elseif ($moduleobject == 'ficheinter' || $moduleobject == 'interventions') {
		$moduledirforclass = 'fichinter';
	} elseif ($moduleobject == 'mos') {
		$moduledirforclass = 'mrp';
	} elseif ($moduleobject == 'workstations') {
		$moduledirforclass = 'workstation';
	} elseif ($moduleobject == 'accounting') {
		$moduledirforclass = 'accountancy';
	} elseif (in_array($moduleobject, array('products', 'expensereports', 'users', 'tickets', 'boms', 'receptions', 'partnerships', 'recruitments'))) {
		$moduledirforclass = preg_replace('/s$/', '', $moduleobject);
	} elseif ($moduleobject == 'paymentsalaries') {
		$moduledirforclass = 'salaries';
	} elseif ($moduleobject == 'paymentexpensereports') {
		$moduledirforclass = 'expensereport';
	}

	return $moduledirforclass;
}

/**
 * Return 2 hexa code randomly
 *
 * @param	int   $min	    Between 0 and 255
 * @param	int   $max	    Between 0 and 255
 * @return  string          A color string '12'
 */
function randomColorPart($min = 0, $max = 255)
{
	return str_pad(dechex(mt_rand($min, $max)), 2, '0', STR_PAD_LEFT);
}

/**
 * Return hexadecimal color randomly
 *
 * @param	int   $min	   Between 0 and 255
 * @param	int   $max	   Between 0 and 255
 * @return  string         A color string '123456'
 */
function randomColor($min = 0, $max = 255)
{
	return randomColorPart($min, $max).randomColorPart($min, $max).randomColorPart($min, $max);
}


if (!function_exists('dolEscapeXML')) {
	/**
	 * Encode string for xml usage
	 *
	 * @param 	string	$string		String to encode
	 * @return	string				String encoded
	 */
	function dolEscapeXML($string)
	{
		return strtr($string, array('\'' => '&apos;', '"' => '&quot;', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'));
	}
}


/**
 * Convert links to local wrapper to medias files into a string into a public external URL readable on internet
 *
 * @param   string      $notetoshow      Text to convert
 * @return  string                       String
 */
function convertBackOfficeMediasLinksToPublicLinks($notetoshow)
{
	global $dolibarr_main_url_root;
	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
	$notetoshow = preg_replace('/src="[a-zA-Z0-9_\/\-\.]*(viewimage\.php\?modulepart=medias[^"]*)"/', 'src="'.$urlwithroot.'/\1"', $notetoshow);
	return $notetoshow;
}

/**
 *		Function to format a value into a defined format for French administration (no thousand separator & decimal separator force to ',' with two decimals)
 *		Function used into accountancy FEC export
 *
 *		@param	float		$amount		Amount to format
 *		@return	string					Chain with formatted upright
 *		@see	price2num()				Format a numeric into a price for FEC files
 */
function price2fec($amount)
{
	global $conf;

	// Clean parameters
	if (empty($amount)) {
		$amount = 0; // To have a numeric value if amount not defined or = ''
	}
	$amount = (is_numeric($amount) ? $amount : 0); // Check if amount is numeric, for example, an error occurred when amount value = o (letter) instead 0 (number)

	// Output decimal number by default
	$nbdecimal = (!getDolGlobalString('ACCOUNTING_FEC_DECIMAL_LENGTH') ? 2 : $conf->global->ACCOUNTING_FEC_DECIMAL_LENGTH);

	// Output separators by default
	$dec = (!getDolGlobalString('ACCOUNTING_FEC_DECIMAL_SEPARATOR') ? ',' : $conf->global->ACCOUNTING_FEC_DECIMAL_SEPARATOR);
	$thousand = (!getDolGlobalString('ACCOUNTING_FEC_THOUSAND_SEPARATOR') ? '' : $conf->global->ACCOUNTING_FEC_THOUSAND_SEPARATOR);

	// Format number
	$output = number_format($amount, $nbdecimal, $dec, $thousand);

	return $output;
}

/**
 * Check the syntax of some PHP code.
 *
 * @param 	string 			$code 	PHP code to check.
 * @return 	boolean|array 			If false, then check was successful, otherwise an array(message,line) of errors is returned.
 */
function phpSyntaxError($code)
{
	if (!defined("CR")) {
		define("CR", "\r");
	}
	if (!defined("LF")) {
		define("LF", "\n");
	}
	if (!defined("CRLF")) {
		define("CRLF", "\r\n");
	}

	$braces = 0;
	$inString = 0;
	foreach (token_get_all('<?php '.$code) as $token) {
		if (is_array($token)) {
			switch ($token[0]) {
				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_START_HEREDOC:
					++$inString;
					break;
				case T_END_HEREDOC:
					--$inString;
					break;
			}
		} elseif ($inString & 1) {
			switch ($token) {
				case '`':
				case '\'':
				case '"':
					--$inString;
					break;
			}
		} else {
			switch ($token) {
				case '`':
				case '\'':
				case '"':
					++$inString;
					break;
				case '{':
					++$braces;
					break;
				case '}':
					if ($inString) {
						--$inString;
					} else {
						--$braces;
						if ($braces < 0) {
							break 2;
						}
					}
					break;
			}
		}
	}
	$inString = @ini_set('log_errors', false);
	$token = @ini_set('display_errors', true);
	ob_start();
	$code = substr($code, strlen('<?php '));
	$braces || $code = "if(0){{$code}\n}";
	// @phan-suppress-next-line PhanPluginUnsafeEval
	if (eval($code) === false) {
		if ($braces) {
			$braces = PHP_INT_MAX;
		} else {
			false !== strpos($code, CR) && $code = strtr(str_replace(CRLF, LF, $code), CR, LF);
			$braces = substr_count($code, LF);
		}
		$code = ob_get_clean();
		$code = strip_tags($code);
		if (preg_match("'syntax error, (.+) in .+ on line (\d+)$'s", $code, $code)) {
			$code[2] = (int) $code[2];
			$code = $code[2] <= $braces
				? array($code[1], $code[2])
				: array('unexpected $end'.substr($code[1], 14), $braces);
		} else {
			$code = array('syntax error', 0);
		}
	} else {
		ob_end_clean();
		$code = false;
	}
	@ini_set('display_errors', $token);
	@ini_set('log_errors', $inString);
	return $code;
}


/**
 * Check the syntax of some PHP code.
 *
 * @return 	int		>0 if OK, 0 if no			Return if we accept link added from the media browser into HTML field for public usage
 */
function acceptLocalLinktoMedia()
{
	global $user;

	// If $acceptlocallinktomedia is true, we can add link media files int email templates (we already can do this into HTML editor of an email).
	// Note that local link to a file into medias are replaced with a real link by email in CMailFile.class.php with value $urlwithroot defined like this:
	// $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	// $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	$acceptlocallinktomedia = getDolGlobalInt('MAIN_DISALLOW_MEDIAS_IN_EMAIL_TEMPLATES') ? 0 : 1;
	if ($acceptlocallinktomedia) {
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));

		// Parse $newUrl
		$newUrlArray = parse_url($urlwithouturlroot);
		$hosttocheck = $newUrlArray['host'];
		$hosttocheck = str_replace(array('[', ']'), '', $hosttocheck); // Remove brackets of IPv6

		if (function_exists('gethostbyname')) {
			$iptocheck = gethostbyname($hosttocheck);
		} else {
			$iptocheck = $hosttocheck;
		}

		//var_dump($iptocheck.' '.$acceptlocallinktomedia);
		$allowParamName = 'MAIN_ALLOW_WYSIWYG_LOCAL_MEDIAS_ON_PRIVATE_NETWORK';
		$allowPrivateNetworkIP = getDolGlobalInt($allowParamName);
		if (!$allowPrivateNetworkIP && !filter_var($iptocheck, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			// If ip of public url is a private network IP, we do not allow this.
			$acceptlocallinktomedia = 0;
			//dol_syslog("WYSIWYG Editor : local media not allowed (checked IP: {$iptocheck}). Use {$allowParamName} = 1 to allow local URL into WYSIWYG html content");
		}

		if (preg_match('/http:/i', $urlwithouturlroot)) {
			// If public url is not a https, we do not allow to add medias link. It will generate security alerts when email will be sent.
			$acceptlocallinktomedia = 0;
			// TODO Show a warning
		}

		if (!empty($user->socid)) {
			$acceptlocallinktomedia = 0;
		}
	}

	//return 1;
	return $acceptlocallinktomedia;
}


/**
 * Remove first and last parenthesis but only if first is the opening and last the closing of the same group
 *
 * @param 	string	$string		String to sanitize
 * @return 	string				String without global parenthesis
 */
function removeGlobalParenthesis($string)
{
	$string = trim($string);

	// If string does not start and end with parenthesis, we return $string as is.
	if (! preg_match('/^\(.*\)$/', $string)) {
		return $string;
	}

	$nbofchars = dol_strlen($string);
	$i = 0;
	$g = 0;
	$countparenthesis = 0;
	while ($i < $nbofchars) {
		$char = dol_substr($string, $i, 1);
		if ($char == '(') {
			$countparenthesis++;
		} elseif ($char == ')') {
			$countparenthesis--;
			if ($countparenthesis <= 0) {	// We reach the end of an independent group of parenthesis
				$g++;
			}
		}
		$i++;
	}

	if ($g <= 1) {
		return preg_replace('/^\(/', '', preg_replace('/\)$/', '', $string));
	}

	return $string;
}


/**
 * Return array of Emojis for miscellaneous use.
 *
 * @return 	array<string,array<string>>			Array of Emojis in hexadecimal
 * @see getArrayOfEmoji()
 */
function getArrayOfEmojiBis()
{
	$arrayofcommonemoji = array(
		'misc' => array('2600', '26FF'),		// Miscellaneous Symbols
		'ding' => array('2700', '27BF'),		// Dingbats
		'????' => array('9989', '9989'),		// Variation Selectors
		'vars' => array('FE00', 'FE0F'),		// Variation Selectors
		'pict' => array('1F300', '1F5FF'),		// Miscellaneous Symbols and Pictographs
		'emot' => array('1F600', '1F64F'),		// Emoticons
		'tran' => array('1F680', '1F6FF'),		// Transport and Map Symbols
		'flag' => array('1F1E0', '1F1FF'),		// Flags (note: may be 1F1E6 instead of 1F1E0)
		'supp' => array('1F900', '1F9FF'),		// Supplemental Symbols and Pictographs
	);

	return $arrayofcommonemoji;
}

/**
 * Remove EMoji from email content
 *
 * @param 	string	$text			String to sanitize
 * @param	int		$allowedemoji	Mode to allow emoji
 * @return 	string					Sanitized string
 */
function removeEmoji($text, $allowedemoji = 1)
{
	// $allowedemoji can be
	// 0=no emoji, 1=exclude the main known emojis (default), 2=keep only the main known (not implemented), 3=accept all
	// Note that to accept emoji in database, you must use utf8mb4, utf8mb3 is not enough.

	if ($allowedemoji == 0) {
		// For a large removal:
		$text = preg_replace('/[\x{2600}-\x{FFFF}]/u', '', $text);
		$text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
	}

	// Delete emoji chars with a regex
	// See https://www.unicode.org/emoji/charts/full-emoji-list.html
	if ($allowedemoji == 1) {
		$arrayofcommonemoji = getArrayOfEmojiBis();

		foreach ($arrayofcommonemoji as $key => $valarray) {
			$text = preg_replace('/[\x{'.$valarray[0].'}-\x{'.$valarray[1].'}]/u', '', $text);
		}
	}

	if ($allowedemoji == 2) {
		// TODO Not yet implemented
	}

	return $text;
}
