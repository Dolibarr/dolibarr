<?php
/* Copyright (C) 2008-2021 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2021 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020	   Ferran Marcet        <fmarcet@2byte.es>
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
 *  \file		htdocs/core/lib/security.lib.php
 *  \ingroup    core
 *  \brief		Set of function used for dolibarr security (common function included into filefunc.inc.php)
 *  			Warning, this file must not depends on other library files, except function.lib.php
 *  			because it is used at low code level.
 */


/**
 *	Encode a string with base 64 algorithm + specific delta change.
 *
 *	@param   string		$chain		string to encode
 *	@param   string		$key		rule to use for delta ('0', '1' or 'myownkey')
 *	@return  string					encoded string
 *  @see dol_decode()
 */
function dol_encode($chain, $key = '1')
{
	if (is_numeric($key) && $key == '1') {	// rule 1 is offset of 17 for char
		$output_tab = array();
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++) {
			$output_tab[$i] = chr(ord(substr($chain, $i, 1)) + 17);
		}
		$chain = implode("", $output_tab);
	} elseif ($key) {
		$result = '';
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++) {
			$keychar = substr($key, ($i % strlen($key)) - 1, 1);
			$result .= chr(ord(substr($chain, $i, 1)) + (ord($keychar) - 65));
		}
		$chain = $result;
	}

	return base64_encode($chain);
}

/**
 *	Decode a base 64 encoded + specific delta change.
 *  This function is called by filefunc.inc.php at each page call.
 *
 *	@param   string		$chain		string to decode
 *	@param   string		$key		rule to use for delta ('0', '1' or 'myownkey')
 *	@return  string					decoded string
 *  @see dol_encode()
 */
function dol_decode($chain, $key = '1')
{
	$chain = base64_decode($chain);

	if (is_numeric($key) && $key == '1') {	// rule 1 is offset of 17 for char
		$output_tab = array();
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++) {
			$output_tab[$i] = chr(ord(substr($chain, $i, 1)) - 17);
		}

		$chain = implode("", $output_tab);
	} elseif ($key) {
		$result = '';
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++) {
			$keychar = substr($key, ($i % strlen($key)) - 1, 1);
			$result .= chr(ord(substr($chain, $i, 1)) - (ord($keychar) - 65));
		}
		$chain = $result;
	}

	return $chain;
}

/**
 * Return a string of random bytes (hexa string) with length = $length fro cryptographic purposes.
 *
 * @param 	int			$length		Length of random string
 * @return	string					Random string
 */
function dolGetRandomBytes($length)
{
	if (function_exists('random_bytes')) {	// Available with PHP 7 only.
		return bin2hex(random_bytes((int) floor($length / 2)));	// the bin2hex will double the number of bytes so we take length / 2
	}

	return bin2hex(openssl_random_pseudo_bytes((int) floor($length / 2)));		// the bin2hex will double the number of bytes so we take length / 2. May be very slow on Windows.
}

/**
 *	Encode a string with a symetric encryption. Used to encrypt sensitive data into database.
 *  Note: If a backup is restored onto another instance with a different $dolibarr_main_instance_unique_id, then decoded value will differ.
 *  This function is called for example by dol_set_const() when saving a sensible data into database configuration table llx_const.
 *
 *	@param   string		$chain		string to encode
 *	@param   string		$key		If '', we use $dolibarr_main_instance_unique_id
 *  @param	 string		$ciphering	Default ciphering algorithm
 *  @param	 string		$forceseed	To force the seed
 *	@return  string					encoded string
 *  @see dolDecrypt(), dol_hash()
 */
function dolEncrypt($chain, $key = '', $ciphering = 'AES-256-CTR', $forceseed = '')
{
	global $dolibarr_main_instance_unique_id;
	global $dolibarr_disable_dolcrypt_for_debug;

	if ($chain === '' || is_null($chain)) {
		return '';
	}

	$reg = array();
	if (preg_match('/^dolcrypt:([^:]+):(.+)$/', $chain, $reg)) {
		// The $chain is already a crypted string
		return $chain;
	}

	if (empty($key)) {
		$key = $dolibarr_main_instance_unique_id;
	}
	if (empty($ciphering)) {
		$ciphering = 'AES-256-CTR';
	}

	$newchain = $chain;

	if (function_exists('openssl_encrypt') && empty($dolibarr_disable_dolcrypt_for_debug)) {
		$ivlen = 16;
		if (function_exists('openssl_cipher_iv_length')) {
			$ivlen = openssl_cipher_iv_length($ciphering);
		}
		if ($ivlen === false || $ivlen < 1 || $ivlen > 32) {
			$ivlen = 16;
		}
		if (empty($forceseed)) {
			$ivseed = dolGetRandomBytes($ivlen);
		} else {
			$ivseed = dol_trunc(md5($forceseed), $ivlen, 'right', 'UTF-8', 1);
		}

		$newchain = openssl_encrypt($chain, $ciphering, $key, 0, $ivseed);
		return 'dolcrypt:'.$ciphering.':'.$ivseed.':'.$newchain;
	} else {
		return $chain;
	}
}

/**
 *	Decode a string with a symetric encryption. Used to decrypt sensitive data saved into database.
 *  Note: If a backup is restored onto another instance with a different $dolibarr_main_instance_unique_id, then decoded value will differ.
 *
 *	@param   string		$chain		string to encode
 *	@param   string		$key		If '', we use $dolibarr_main_instance_unique_id
 *	@return  string					encoded string
 *  @see dolEncrypt(), dol_hash()
 */
function dolDecrypt($chain, $key = '')
{
	global $dolibarr_main_instance_unique_id;

	if ($chain === '' || is_null($chain)) {
		return '';
	}

	if (empty($key)) {
		$key = $dolibarr_main_instance_unique_id;
	}

	$reg = array();
	if (preg_match('/^dolcrypt:([^:]+):(.+)$/', $chain, $reg)) {
		$ciphering = $reg[1];
		if (function_exists('openssl_decrypt')) {
			if (empty($key)) {
				return 'Error dolDecrypt decrypt key is empty';
			}
			$tmpexplode = explode(':', $reg[2]);
			if (!empty($tmpexplode[1]) && is_string($tmpexplode[0])) {
				$newchain = openssl_decrypt($tmpexplode[1], $ciphering, $key, 0, $tmpexplode[0]);
			} else {
				$newchain = openssl_decrypt($tmpexplode[0], $ciphering, $key, 0, null);
			}
		} else {
			$newchain = 'Error dolDecrypt function openssl_decrypt() not available';
		}
		return $newchain;
	} else {
		return $chain;
	}
}

/**
 * 	Returns a hash (non reversible encryption) of a string.
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function (recommanded value is 'password_hash')
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt (used only if hashing algorightm is something else than 'password_hash').
 *
 * 	@param 		string		$chain		String to hash
 * 	@param		string		$type		Type of hash ('0':auto will use MAIN_SECURITY_HASH_ALGO else md5, '1':sha1, '2':sha1+md5, '3':md5, '4': for OpenLdap, '5':sha256, '6':password_hash). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		string					Hash of string
 *  @see getRandomPassword(), dol_verifyHash()
 */
function dol_hash($chain, $type = '0')
{
	global $conf;

	// No need to add salt for password_hash
	if (($type == '0' || $type == 'auto') && !empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'password_hash' && function_exists('password_hash')) {
		return password_hash($chain, PASSWORD_DEFAULT);
	}

	// Salt value
	if (!empty($conf->global->MAIN_SECURITY_SALT) && $type != '4' && $type !== 'openldap') {
		$chain = $conf->global->MAIN_SECURITY_SALT.$chain;
	}

	if ($type == '1' || $type == 'sha1') {
		return sha1($chain);
	} elseif ($type == '2' || $type == 'sha1md5') {
		return sha1(md5($chain));
	} elseif ($type == '3' || $type == 'md5') {
		return md5($chain);
	} elseif ($type == '4' || $type == 'openldap') {
		return dolGetLdapPasswordHash($chain, getDolGlobalString('LDAP_PASSWORD_HASH_TYPE', 'md5'));
	} elseif ($type == '5' || $type == 'sha256') {
		return hash('sha256', $chain);
	} elseif ($type == '6' || $type == 'password_hash') {
		return password_hash($chain, PASSWORD_DEFAULT);
	} elseif (!empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'sha1') {
		return sha1($chain);
	} elseif (!empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'sha1md5') {
		return sha1(md5($chain));
	}

	// No particular encoding defined, use default
	return md5($chain);
}

/**
 * 	Compute a hash and compare it to the given one
 *  For backward compatibility reasons, if the hash is not in the password_hash format, we will try to match against md5 and sha1md5
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function.
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt.
 *
 * 	@param 		string		$chain		String to hash (not hashed string)
 * 	@param 		string		$hash		hash to compare
 * 	@param		string		$type		Type of hash ('0':auto, '1':sha1, '2':sha1+md5, '3':md5, '4': for OpenLdap, '5':sha256). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		bool					True if the computed hash is the same as the given one
 *  @see dol_hash()
 */
function dol_verifyHash($chain, $hash, $type = '0')
{
	global $conf;

	if ($type == '0' && !empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'password_hash' && function_exists('password_verify')) {
		if (! empty($hash[0]) && $hash[0] == '$') {
			return password_verify($chain, $hash);
		} elseif (dol_strlen($hash) == 32) {
			return dol_verifyHash($chain, $hash, '3'); // md5
		} elseif (dol_strlen($hash) == 40) {
			return dol_verifyHash($chain, $hash, '2'); // sha1md5
		}

		return false;
	}

	return dol_hash($chain, $type) == $hash;
}

/**
 * 	Returns a specific ldap hash of a password.
 *
 * 	@param 		string		$password	Password to hash
 * 	@param		string		$type		Type of hash
 * 	@return		string					Hash of password
 */
function dolGetLdapPasswordHash($password, $type = 'md5')
{
	if (empty($type)) {
		$type = 'md5';
	}

	$salt = substr(sha1(time()), 0, 8);

	if ($type === 'md5') {
		return '{MD5}' . base64_encode(hash("md5", $password, true)); //For OpenLdap with md5 (based on an unencrypted password in base)
	} elseif ($type === 'md5frommd5') {
		return '{MD5}' . base64_encode(hex2bin($password)); // Create OpenLDAP MD5 password from Dolibarr MD5 password
	} elseif ($type === 'smd5') {
		return "{SMD5}" . base64_encode(hash("md5", $password . $salt, true) . $salt);
	} elseif ($type === 'sha') {
		return '{SHA}' . base64_encode(hash("sha1", $password, true));
	} elseif ($type === 'ssha') {
		return "{SSHA}" . base64_encode(hash("sha1", $password . $salt, true) . $salt);
	} elseif ($type === 'sha256') {
		return "{SHA256}" . base64_encode(hash("sha256", $password, true));
	} elseif ($type === 'ssha256') {
		return "{SSHA256}" . base64_encode(hash("sha256", $password . $salt, true) . $salt);
	} elseif ($type === 'sha384') {
		return "{SHA384}" . base64_encode(hash("sha384", $password, true));
	} elseif ($type === 'ssha384') {
		return "{SSHA384}" . base64_encode(hash("sha384", $password . $salt, true) . $salt);
	} elseif ($type === 'sha512') {
		return "{SHA512}" . base64_encode(hash("sha512", $password, true));
	} elseif ($type === 'ssha512') {
		return "{SSHA512}" . base64_encode(hash("sha512", $password . $salt, true) . $salt);
	} elseif ($type === 'crypt') {
		return '{CRYPT}' . crypt($password, $salt);
	} elseif ($type === 'clear') {
		return '{CLEAR}' . $password;  // Just for test, plain text password is not secured !
	}
}

/**
 *	Check permissions of a user to show a page and an object. Check read permission.
 * 	If GETPOST('action','aZ09') defined, we also check write and delete permission.
 *  This method check permission on module then call checkUserAccessToObject() for permission on object (according to entity and socid of user).
 *
 *	@param	User				$user      	  	User to check
 *	@param  string				$features	    Features to check (it must be module name or $object->element. Can be a 'or' check with 'levela|levelb'.
 *												Examples: 'societe', 'contact', 'produit&service', 'produit|service', ...)
 *												This is used to check permission $user->rights->features->...
 *	@param  int|string|object	$object      	Object or Object ID or list of Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
 *	@param  string				$tableandshare  'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity for multicompany module. Param not used if objectid is null (optional).
 *	@param  string				$feature2		Feature to check, second level of permission (optional). Can be a 'or' check with 'sublevela|sublevelb'.
 *												This is used to check permission $user->rights->features->feature2...
 *  @param  string				$dbt_keyfield   Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional). Can use '' if NA.
 *  @param  string				$dbt_select     Field rowid name, for select into tableandshare if not "rowid". Not used if objectid is null (optional)
 *  @param	int					$isdraft		1=The object with id=$objectid is a draft
 *  @param	int					$mode			Mode (0=default, 1=return without dieing)
 * 	@return	int									If mode = 0 (default): Always 1, die process if not allowed. If mode = 1: Return 0 if access not allowed.
 *  @see dol_check_secure_access_document(), checkUserAccessToObject()
 */
function restrictedArea(User $user, $features, $object = 0, $tableandshare = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $isdraft = 0, $mode = 0)
{
	global $db, $conf;
	global $hookmanager;

	// Define $objectid
	if (is_object($object)) {
		$objectid = $object->id;
	} else {
		$objectid = $object;		// $objectid can be X or 'X,Y,Z'
	}
	if ($objectid) {
		$objectid = preg_replace('/[^0-9\.\,]/', '', $objectid);	// For the case value is coming from a non sanitized user input
	}

	//dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename, $feature2, $dbt_socfield, $dbt_select, $isdraft");
	//print "user_id=".$user->id.", features=".$features.", feature2=".$feature2.", objectid=".$objectid;
	//print ", dbtablename=".$tableandshare.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select;
	//print ", perm: ".$features."->".$feature2."=".($user->hasRight($features, $feature2, 'lire'))."<br>";

	$parentfortableentity = '';

	// Fix syntax of $features param
	$originalfeatures = $features;
	if ($features == 'agenda') {
		$tableandshare = 'actioncomm&societe';
		$feature2 = 'myactions|allactions';
		$dbt_select = 'id';
	}
	if ($features == 'facturerec') {
		$features = 'facture';
	}
	if ($features == 'mo') {
		$features = 'mrp';
	}
	if ($features == 'member') {
		$features = 'adherent';
	}
	if ($features == 'subscription') {
		$features = 'adherent';
		$feature2 = 'cotisation';
	}
	if ($features == 'website' && is_object($object) && $object->element == 'websitepage') {
		$parentfortableentity = 'fk_website@website';
	}
	if ($features == 'project') {
		$features = 'projet';
	}
	if ($features == 'product') {
		$features = 'produit';
	}
	if ($features == 'productbatch') {
		$features = 'produit';
	}
	if ($features == 'fournisseur') {	// When vendor invoice and pruchase order are into module 'fournisseur'
		$features = 'fournisseur';
		if ($object->element == 'invoice_supplier') {
			$feature2 = 'facture';
		} elseif ($object->element == 'order_supplier') {
			$feature2 = 'commande';
		}
	}

	//print $features.' - '.$tableandshare.' - '.$feature2.' - '.$dbt_select."\n";

	// Get more permissions checks from hooks
	$parameters = array('features'=>$features, 'originalfeatures'=>$originalfeatures, 'objectid'=>$objectid, 'dbt_select'=>$dbt_select, 'idtype'=>$dbt_select, 'isdraft'=>$isdraft);
	$reshook = $hookmanager->executeHooks('restrictedArea', $parameters);

	if (isset($hookmanager->resArray['result'])) {
		if ($hookmanager->resArray['result'] == 0) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden(); // Module returns 0, so access forbidden
			}
		}
	}
	if ($reshook > 0) {		// No other test done.
		return 1;
	}

	// Features/modules to check
	$featuresarray = array($features);
	if (preg_match('/&/', $features)) {
		$featuresarray = explode("&", $features);
	} elseif (preg_match('/\|/', $features)) {
		$featuresarray = explode("|", $features);
	}

	// More subfeatures to check
	if (!empty($feature2)) {
		$feature2 = explode("|", $feature2);
	}

	$listofmodules = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);

	// Check read permission from module
	$readok = 1;
	$nbko = 0;
	foreach ($featuresarray as $feature) {	// first we check nb of test ko
		$featureforlistofmodule = $feature;
		if ($featureforlistofmodule == 'produit') {
			$featureforlistofmodule = 'product';
		}
		if (!empty($user->socid) && !empty($conf->global->MAIN_MODULES_FOR_EXTERNAL) && !in_array($featureforlistofmodule, $listofmodules)) {	// If limits on modules for external users, module must be into list of modules for external users
			$readok = 0;
			$nbko++;
			continue;
		}

		if ($feature == 'societe') {
			if (!$user->hasRight('societe', 'lire') && !$user->hasRight('fournisseur', 'lire')) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'contact') {
			if (empty($user->rights->societe->contact->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'produit|service') {
			if (empty($user->rights->produit->lire) && empty($user->rights->service->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'prelevement') {
			if (empty($user->rights->prelevement->bons->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'cheque') {
			if (empty($user->rights->banque->cheque)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'projet') {
			if (empty($user->rights->projet->lire) && empty($user->rights->projet->all->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'payment') {
			if (empty($user->rights->facture->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'payment_supplier') {
			if (empty($user->rights->fournisseur->facture->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif ($feature == 'payment_sc') {
			if (empty($user->rights->tax->charges->lire)) {
				$readok = 0;
				$nbko++;
			}
		} elseif (!empty($feature2)) { 													// This is for permissions on 2 levels (module->object->read)
			$tmpreadok = 1;
			foreach ($feature2 as $subfeature) {
				if ($subfeature == 'user' && $user->id == $objectid) {
					continue; // A user can always read its own card
				}
				if (!empty($subfeature) && empty($user->rights->$feature->$subfeature->lire) && empty($user->rights->$feature->$subfeature->read)) {
					$tmpreadok = 0;
				} elseif (empty($subfeature) && empty($user->rights->$feature->lire) && empty($user->rights->$feature->read)) {
					$tmpreadok = 0;
				} else {
					$tmpreadok = 1;
					break;
				} // Break is to bypass second test if the first is ok
			}
			if (!$tmpreadok) {	// We found a test on feature that is ko
				$readok = 0; // All tests are ko (we manage here the and, the or will be managed later using $nbko).
				$nbko++;
			}
		} elseif (!empty($feature) && ($feature != 'user' && $feature != 'usergroup')) {		// This is permissions on 1 level (module->read)
			if (empty($user->rights->$feature->lire)
				&& empty($user->rights->$feature->read)
				&& empty($user->rights->$feature->run)) {
				$readok = 0;
				$nbko++;
			}
		}
	}

	// If a or and at least one ok
	if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) {
		$readok = 1;
	}

	if (!$readok) {
		if ($mode) {
			return 0;
		} else {
			accessforbidden();
		}
	}
	//print "Read access is ok";

	// Check write permission from module (we need to know write permission to create but also to delete drafts record or to upload files)
	$createok = 1;
	$nbko = 0;
	$wemustcheckpermissionforcreate = (GETPOST('sendit', 'alpha') || GETPOST('linkit', 'alpha') || in_array(GETPOST('action', 'aZ09'), array('create', 'update', 'set', 'upload', 'add_element_resource', 'confirm_delete_linked_resource')) || GETPOST('roworder', 'alpha', 2));
	$wemustcheckpermissionfordeletedraft = ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete');

	if ($wemustcheckpermissionforcreate || $wemustcheckpermissionfordeletedraft) {
		foreach ($featuresarray as $feature) {
			if ($feature == 'contact') {
				if (empty($user->rights->societe->contact->creer)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'produit|service') {
				if (empty($user->rights->produit->creer) && empty($user->rights->service->creer)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'prelevement') {
				if (!$user->rights->prelevement->bons->creer) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'commande_fournisseur') {
				if (empty($user->rights->fournisseur->commande->creer) || empty($user->rights->supplier_order->creer)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'banque') {
				if (empty($user->rights->banque->modifier)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'cheque') {
				if (empty($user->rights->banque->cheque)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'import') {
				if (empty($user->rights->import->run)) {
					$createok = 0;
					$nbko++;
				}
			} elseif ($feature == 'ecm') {
				if (!$user->rights->ecm->upload) {
					$createok = 0;
					$nbko++;
				}
			} elseif (!empty($feature2)) {													// This is for permissions on 2 levels (module->object->write)
				foreach ($feature2 as $subfeature) {
					if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->creer) {
						continue; // User can edit its own card
					}
					if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->password) {
						continue; // User can edit its own password
					}
					if ($subfeature == 'user' && $user->id != $objectid && $user->rights->user->user->password) {
						continue; // User can edit another user's password
					}

					if (empty($user->rights->$feature->$subfeature->creer)
					&& empty($user->rights->$feature->$subfeature->write)
					&& empty($user->rights->$feature->$subfeature->create)) {
						$createok = 0;
						$nbko++;
					} else {
						$createok = 1;
						// Break to bypass second test if the first is ok
						break;
					}
				}
			} elseif (!empty($feature)) {												// This is for permissions on 1 levels (module->write)
				//print '<br>feature='.$feature.' creer='.$user->rights->$feature->creer.' write='.$user->rights->$feature->write; exit;
				if (empty($user->rights->$feature->creer)
				&& empty($user->rights->$feature->write)
				&& empty($user->rights->$feature->create)) {
					$createok = 0;
					$nbko++;
				}
			}
		}

		// If a or and at least one ok
		if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) {
			$createok = 1;
		}

		if ($wemustcheckpermissionforcreate && !$createok) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden();
			}
		}
		//print "Write access is ok";
	}

	// Check create user permission
	$createuserok = 1;
	if (GETPOST('action', 'aZ09') == 'confirm_create_user' && GETPOST("confirm", 'aZ09') == 'yes') {
		if (!$user->rights->user->user->creer) {
			$createuserok = 0;
		}

		if (!$createuserok) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden();
			}
		}
		//print "Create user access is ok";
	}

	// Check delete permission from module
	$deleteok = 1;
	$nbko = 0;
	if ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete') {
		foreach ($featuresarray as $feature) {
			if ($feature == 'bookmark') {
				if (!$user->rights->bookmark->supprimer) {
					if ($user->id != $object->fk_user || empty($user->rights->bookmark->creer)) {
						$deleteok = 0;
					}
				}
			} elseif ($feature == 'contact') {
				if (!$user->rights->societe->contact->supprimer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'produit|service') {
				if (!$user->rights->produit->supprimer && !$user->rights->service->supprimer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'commande_fournisseur') {
				if (!$user->rights->fournisseur->commande->supprimer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'payment_supplier') {	// Permission to delete a payment of an invoice is permission to edit an invoice.
				if (!$user->rights->fournisseur->facture->creer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'payment') {
				if (!$user->rights->facture->paiement) {
						$deleteok = 0;
				}
			} elseif ($feature == 'payment_sc') {
				if (!$user->rights->tax->charges->creer) {
					$deleteok = 0;
				}
			} elseif ($feature == 'banque') {
				if (empty($user->rights->banque->modifier)) {
					$deleteok = 0;
				}
			} elseif ($feature == 'cheque') {
				if (empty($user->rights->banque->cheque)) {
					$deleteok = 0;
				}
			} elseif ($feature == 'ecm') {
				if (!$user->rights->ecm->upload) {
					$deleteok = 0;
				}
			} elseif ($feature == 'ftp') {
				if (!$user->rights->ftp->write) {
					$deleteok = 0;
				}
			} elseif ($feature == 'salaries') {
				if (!$user->rights->salaries->delete) {
					$deleteok = 0;
				}
			} elseif ($feature == 'adherent') {
				if (empty($user->rights->adherent->supprimer)) {
					$deleteok = 0;
				}
			} elseif ($feature == 'paymentbybanktransfer') {
				if (empty($user->rights->paymentbybanktransfer->create)) {	// There is no delete permission
					$deleteok = 0;
				}
			} elseif ($feature == 'prelevement') {
				if (empty($user->rights->prelevement->bons->creer)) {		// There is no delete permission
					$deleteok = 0;
				}
			} elseif (!empty($feature2)) {							// This is for permissions on 2 levels
				foreach ($feature2 as $subfeature) {
					if (empty($user->rights->$feature->$subfeature->supprimer) && empty($user->rights->$feature->$subfeature->delete)) {
						$deleteok = 0;
					} else {
						$deleteok = 1;
						break;
					} // For bypass the second test if the first is ok
				}
			} elseif (!empty($feature)) {							// This is used for permissions on 1 level
				//print '<br>feature='.$feature.' creer='.$user->rights->$feature->supprimer.' write='.$user->rights->$feature->delete;
				if (empty($user->rights->$feature->supprimer)
					&& empty($user->rights->$feature->delete)
					&& empty($user->rights->$feature->run)) {
					$deleteok = 0;
				}
			}
		}

		// If a or and at least one ok
		if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) {
			$deleteok = 1;
		}

		if (!$deleteok && !($isdraft && $createok)) {
			if ($mode) {
				return 0;
			} else {
				accessforbidden();
			}
		}
		//print "Delete access is ok";
	}

	// If we have a particular object to check permissions on, we check if $user has permission
	// for this given object (link to company, is contact for project, ...)
	if (!empty($objectid) && $objectid > 0) {
		$ok = checkUserAccessToObject($user, $featuresarray, $object, $tableandshare, $feature2, $dbt_keyfield, $dbt_select, $parentfortableentity);
		$params = array('objectid' => $objectid, 'features' => join(',', $featuresarray), 'features2' => $feature2);
		//print 'checkUserAccessToObject ok='.$ok;
		if ($mode) {
			return $ok ? 1 : 0;
		} else {
			if ($ok) {
				return 1;
			} else {
				accessforbidden('', 1, 1, 0, $params);
			}
		}
	}

	return 1;
}

/**
 * Check that access by a given user to an object is ok.
 * This function is also called by restrictedArea() that check before if module is enabled and if permission of user for $action is ok.
 *
 * @param 	User				$user					User to check
 * @param 	array				$featuresarray			Features/modules to check. Example: ('user','service','member','project','task',...)
 * @param 	int|string|Object	$object					Full object or object ID or list of object id. For example if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
 * @param 	string				$tableandshare			'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity for multicompany modume. Param not used if objectid is null (optional).
 * @param 	array|string		$feature2				Feature to check, second level of permission (optional). Can be or check with 'level1|level2'.
 * @param 	string				$dbt_keyfield			Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional). Can use '' if NA.
 * @param 	string				$dbt_select				Field name for select if not rowid. Not used if objectid is null (optional).
 * @param 	string				$parenttableforentity  	Parent table for entity. Example 'fk_website@website'
 * @return	bool										True if user has access, False otherwise
 * @see restrictedArea()
 */
function checkUserAccessToObject($user, array $featuresarray, $object = 0, $tableandshare = '', $feature2 = '', $dbt_keyfield = '', $dbt_select = 'rowid', $parenttableforentity = '')
{
	global $db, $conf;

	if (is_object($object)) {
		$objectid = $object->id;
	} else {
		$objectid = $object;		// $objectid can be X or 'X,Y,Z'
	}
	$objectid = preg_replace('/[^0-9\.\,]/', '', $objectid);	// For the case value is coming from a non sanitized user input

	//dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename, $feature2, $dbt_socfield, $dbt_select, $isdraft");
	//print "user_id=".$user->id.", features=".join(',', $featuresarray).", objectid=".$objectid;
	//print ", tableandshare=".$tableandshare.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select."<br>";

	// More parameters
	$params = explode('&', $tableandshare);
	$dbtablename = (!empty($params[0]) ? $params[0] : '');
	$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename);

	foreach ($featuresarray as $feature) {
		$sql = '';

		//var_dump($feature);exit;

		// For backward compatibility
		if ($feature == 'member') {
			$feature = 'adherent';
		}
		if ($feature == 'project') {
			$feature = 'projet';
		}
		if ($feature == 'task') {
			$feature = 'projet_task';
		}

		$checkonentitydone = 0;

		// Array to define rules of checks to do
		$check = array('adherent', 'banque', 'bom', 'don', 'mrp', 'user', 'usergroup', 'payment', 'payment_supplier', 'product', 'produit', 'service', 'produit|service', 'categorie', 'resource', 'expensereport', 'holiday', 'salaries', 'website', 'recruitment'); // Test on entity only (Objects with no link to company)
		$checksoc = array('societe'); // Test for object Societe
		$checkother = array('contact', 'agenda', 'contrat'); // Test on entity + link to third party on field $dbt_keyfield. Allowed if link is empty (Ex: contacts...).
		$checkproject = array('projet', 'project'); // Test for project object
		$checktask = array('projet_task'); // Test for task object
		$checkhierarchy = array('expensereport', 'holiday');	// check permission among the hierarchy of user
		$checkuser = array('bookmark');	// check permission among the fk_user (must be myself or null)
		$nocheck = array('barcode', 'stock'); // No test

		//$checkdefault = 'all other not already defined'; // Test on entity + link to third party on field $dbt_keyfield. Not allowed if link is empty (Ex: invoice, orders...).

		// If dbtablename not defined, we use same name for table than module name
		if (empty($dbtablename)) {
			$dbtablename = $feature;
			$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename); // We change dbtablename, so we set sharedelement too.
		}

		// To avoid an access forbidden with a numeric ref
		if ($dbt_select != 'rowid' && $dbt_select != 'id') {
			$objectid = "'".$objectid."'";	// Note: $objectid was already cast into int at begin of this method.
		}

		// Check permission for objectid on entity only
		if (in_array($feature, $check) && $objectid > 0) {		// For $objectid = 0, no check
			$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
			if (($feature == 'user' || $feature == 'usergroup') && isModEnabled('multicompany')) {	// Special for multicompany
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					if ($conf->entity == 1 && $user->admin && !$user->entity) {
						$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
						$sql .= " AND dbt.entity IS NOT NULL";
					} else {
						$sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
						$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
						$sql .= " AND ((ug.fk_user = dbt.rowid";
						$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
						$sql .= " OR dbt.entity = 0)"; // Show always superadmin
					}
				} else {
					$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			} else {
				$reg = array();
				if ($parenttableforentity && preg_match('/(.*)@(.*)/', $parenttableforentity, $reg)) {
					$sql .= ", ".MAIN_DB_PREFIX.$reg[2]." as dbtp";
					$sql .= " WHERE dbt.".$reg[1]." = dbtp.rowid AND dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
					$sql .= " AND dbtp.entity IN (".getEntity($sharedelement, 1).")";
				} else {
					$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			}
			$checkonentitydone = 1;
		}
		if (in_array($feature, $checksoc) && $objectid > 0) {	// We check feature = checksoc. For $objectid = 0, no check
			// If external user: Check permission for external users
			if ($user->socid > 0) {
				if ($user->socid != $objectid) {
					return false;
				}
			} elseif (isModEnabled("societe") && ($user->hasRight('societe', 'lire') && empty($user->rights->societe->client->voir))) {
				// If internal user: Check permission for internal users that are restricted on their objects
				$sql = "SELECT COUNT(sc.fk_soc) as nb";
				$sql .= " FROM (".MAIN_DB_PREFIX."societe_commerciaux as sc";
				$sql .= ", ".MAIN_DB_PREFIX."societe as s)";
				$sql .= " WHERE sc.fk_soc IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND sc.fk_user = ".((int) $user->id);
				$sql .= " AND sc.fk_soc = s.rowid";
				$sql .= " AND s.entity IN (".getEntity($sharedelement, 1).")";
			} elseif (isModEnabled('multicompany')) {
				// If multicompany and internal users with all permissions, check user is in correct entity
				$sql = "SELECT COUNT(s.rowid) as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
				$sql .= " WHERE s.rowid IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND s.entity IN (".getEntity($sharedelement, 1).")";
			}

			$checkonentitydone = 1;
		}
		if (in_array($feature, $checkother) && $objectid > 0) {	// Test on entity + link to thirdparty. Allowed if link is empty (Ex: contacts...).
			// If external user: Check permission for external users
			if ($user->socid > 0) {
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND dbt.fk_soc = ".((int) $user->socid);
			} elseif (isModEnabled("societe") && ($user->hasRight('societe', 'lire') && empty($user->rights->societe->client->voir))) {
				// If internal user: Check permission for internal users that are restricted on their objects
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON dbt.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
				$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL)"; // Contact not linked to a company or to a company of user
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			} elseif (isModEnabled('multicompany')) {
				// If multicompany and internal users with all permissions, check user is in correct entity
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}

			$checkonentitydone = 1;
		}
		if (in_array($feature, $checkproject) && $objectid > 0) {
			if (isModEnabled('project') && empty($user->rights->projet->all->lire)) {
				$projectid = $objectid;

				include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$projectstatic = new Project($db);
				$tmps = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0);

				$tmparray = explode(',', $tmps);
				if (!in_array($projectid, $tmparray)) {
					return false;
				}
			} else {
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}

			$checkonentitydone = 1;
		}
		if (in_array($feature, $checktask) && $objectid > 0) {
			if (isModEnabled('project') && empty($user->rights->projet->all->lire)) {
				$task = new Task($db);
				$task->fetch($objectid);
				$projectid = $task->fk_project;

				include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$projectstatic = new Project($db);
				$tmps = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0);

				$tmparray = explode(',', $tmps);
				if (!in_array($projectid, $tmparray)) {
					return false;
				}
			} else {
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}

			$checkonentitydone = 1;
		}
		if (!$checkonentitydone && !in_array($feature, $nocheck) && $objectid > 0) {		// By default (case of $checkdefault), we check on object entity + link to third party on field $dbt_keyfield
			// If external user: Check permission for external users
			if ($user->socid > 0) {
				if (empty($dbt_keyfield)) {
					dol_print_error('', 'Param dbt_keyfield is required but not defined');
				}
				$sql = "SELECT COUNT(dbt.".$dbt_keyfield.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.rowid IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND dbt.".$dbt_keyfield." = ".((int) $user->socid);
			} elseif (isModEnabled("societe") && empty($user->rights->societe->client->voir)) {
				// If internal user: Check permission for internal users that are restricted on their objects
				if ($feature != 'ticket') {
					if (empty($dbt_keyfield)) {
						dol_print_error('', 'Param dbt_keyfield is required but not defined');
					}
					$sql = "SELECT COUNT(sc.fk_soc) as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
					$sql .= " AND sc.fk_soc = dbt.".$dbt_keyfield;
					$sql .= " AND sc.fk_user = ".((int) $user->id);
				} else {
					// On ticket, the thirdparty is not mandatory, so we need a special test to accept record with no thirdparties.
					$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = dbt.".$dbt_keyfield." AND sc.fk_user = ".((int) $user->id);
					$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
					$sql .= " AND (sc.fk_user = ".((int) $user->id)." OR sc.fk_user IS NULL)";
				}
			} elseif (isModEnabled('multicompany')) {
				// If multicompany and internal users with all permissions, check user is in correct entity
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$db->sanitize($objectid, 1).")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}
		}
		//print $sql;

		// For events, check on users assigned to event
		if ($feature === 'agenda' && $objectid > 0) {
			// Also check owner or attendee for users without allactions->read
			if ($objectid > 0 && empty($user->rights->agenda->allactions->read)) {
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$action = new ActionComm($db);
				$action->fetch($objectid);
				if ($action->authorid != $user->id && $action->userownerid != $user->id && !(array_key_exists($user->id, $action->userassigned))) {
					return false;
				}
			}
		}

		// For some object, we also have to check it is in the user hierarchy
		// Param $object must be the full object and not a simple id to have this test possible.
		if (in_array($feature, $checkhierarchy) && is_object($object) && $objectid > 0) {
			$childids = $user->getAllChildIds(1);
			$useridtocheck = 0;
			if ($feature == 'holiday') {
				$useridtocheck = $object->fk_user;
				if (!in_array($useridtocheck, $childids)) {
					return false;
				}
				$useridtocheck = $object->fk_validator;
				if (!in_array($useridtocheck, $childids)) {
					return false;
				}
			}
			if ($feature == 'expensereport') {
				$useridtocheck = $object->fk_user_author;
				if (!$user->rights->expensereport->readall) {
					if (!in_array($useridtocheck, $childids)) {
						return false;
					}
				}
			}
		}

		// For some object, we also have to check it is public or owned by user
		// Param $object must be the full object and not a simple id to have this test possible.
		if (in_array($feature, $checkuser) && is_object($object) && $objectid > 0) {
			$useridtocheck = $object->fk_user;
			if (!empty($useridtocheck) && $useridtocheck > 0 && $useridtocheck != $user->id && empty($user->admin)) {
				return false;
			}
		}

		if ($sql) {
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if (!$obj || $obj->nb < count(explode(',', $objectid))) {	// error if we found 0 or less record than nb of id provided
					return false;
				}
			} else {
				dol_syslog("Bad forged sql in checkUserAccessToObject", LOG_WARNING);
				return false;
			}
		}
	}

	return true;
}


/**
 *	Show a message to say access is forbidden and stop program.
 *  This includes only HTTP header.
 *	Calling this function terminate execution of PHP.
 *
 *	@param	string		$message					Force error message
 *	@param	int			$http_response_code			HTTP response code
 *  @param	int			$stringalreadysanitized		1 if string is already sanitized with HTML entities
 *  @return	void
 *  @see accessforbidden()
 */
function httponly_accessforbidden($message = 1, $http_response_code = 403, $stringalreadysanitized = 0)
{
	top_httphead();
	http_response_code($http_response_code);

	if ($stringalreadysanitized) {
		print $message;
	} else {
		print htmlentities($message);
	}

	exit(1);
}

/**
 *	Show a message to say access is forbidden and stop program.
 *  This includes HTTP and HTML header and footer (except if $printheader and $printfooter is  0, use this case inside an already started page).
 *	Calling this function terminate execution of PHP.
 *
 *	@param	string		$message			Force error message
 *	@param	int			$printheader		Show header before
 *  @param  int			$printfooter        Show footer after
 *  @param  int			$showonlymessage    Show only message parameter. Otherwise add more information.
 *  @param  array|null  $params         	More parameters provided to hook
 *  @return	void
 *  @see httponly_accessforbidden()
 */
function accessforbidden($message = '', $printheader = 1, $printfooter = 1, $showonlymessage = 0, $params = null)
{
	global $conf, $db, $user, $langs, $hookmanager;
	global $action, $object;

	if (!is_object($langs)) {
		include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
		$langs = new Translate('', $conf);
		$langs->setDefaultLang();
	}

	$langs->load("errors");

	if ($printheader) {
		if (function_exists("llxHeader")) {
			llxHeader('');
		} elseif (function_exists("llxHeaderVierge")) {
			llxHeaderVierge('');
		}
	}
	print '<div class="error">';
	if (empty($message)) {
		print $langs->trans("ErrorForbidden");
	} else {
		print $langs->trans($message);
	}
	print '</div>';
	print '<br>';
	if (empty($showonlymessage)) {
		if (empty($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
			// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
			$hookmanager->initHooks(array('main'));
		}

		$parameters = array('message'=>$message, 'params'=>$params);
		$reshook = $hookmanager->executeHooks('getAccessForbiddenMessage', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			$langs->loadLangs(array("errors"));
			if ($user->login) {
				print $langs->trans("CurrentLogin").': <span class="error">'.$user->login.'</span><br>';
				print $langs->trans("ErrorForbidden2", $langs->transnoentitiesnoconv("Home"), $langs->transnoentitiesnoconv("Users"));
				print $langs->trans("ErrorForbidden4");
			} else {
				print $langs->trans("ErrorForbidden3");
			}
		}
	}
	if ($printfooter && function_exists("llxFooter")) {
		llxFooter();
	}

	exit(0);
}


/**
 *	Return the max allowed for file upload.
 *  Analyze among: upload_max_filesize, post_max_size, MAIN_UPLOAD_DOC
 *
 *  @return	array		Array with all max size for file upload
 */
function getMaxFileSizeArray()
{
	global $conf;

	$max = $conf->global->MAIN_UPLOAD_DOC; // In Kb
	$maxphp = @ini_get('upload_max_filesize'); // In unknown
	if (preg_match('/k$/i', $maxphp)) {
		$maxphp = preg_replace('/k$/i', '', $maxphp);
		$maxphp = $maxphp * 1;
	}
	if (preg_match('/m$/i', $maxphp)) {
		$maxphp = preg_replace('/m$/i', '', $maxphp);
		$maxphp = $maxphp * 1024;
	}
	if (preg_match('/g$/i', $maxphp)) {
		$maxphp = preg_replace('/g$/i', '', $maxphp);
		$maxphp = $maxphp * 1024 * 1024;
	}
	if (preg_match('/t$/i', $maxphp)) {
		$maxphp = preg_replace('/t$/i', '', $maxphp);
		$maxphp = $maxphp * 1024 * 1024 * 1024;
	}
	$maxphp2 = @ini_get('post_max_size'); // In unknown
	if (preg_match('/k$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/k$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1;
	}
	if (preg_match('/m$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/m$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024;
	}
	if (preg_match('/g$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/g$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024 * 1024;
	}
	if (preg_match('/t$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/t$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024 * 1024 * 1024;
	}
	// Now $max and $maxphp and $maxphp2 are in Kb
	$maxmin = $max;
	$maxphptoshow = $maxphptoshowparam = '';
	if ($maxphp > 0) {
		$maxmin = min($maxmin, $maxphp);
		$maxphptoshow = $maxphp;
		$maxphptoshowparam = 'upload_max_filesize';
	}
	if ($maxphp2 > 0) {
		$maxmin = min($maxmin, $maxphp2);
		if ($maxphp2 < $maxphp) {
			$maxphptoshow = $maxphp2;
			$maxphptoshowparam = 'post_max_size';
		}
	}
	//var_dump($maxphp.'-'.$maxphp2);
	//var_dump($maxmin);

	return array('max'=>$max, 'maxmin'=>$maxmin, 'maxphptoshow'=>$maxphptoshow, 'maxphptoshowparam'=>$maxphptoshowparam);
}
