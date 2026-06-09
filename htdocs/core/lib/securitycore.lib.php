<?php
/* Copyright (C) 2024  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *  \file		htdocs/core/lib/securitycore.lib.php
 *  \ingroup    core
 *  \brief		Set of function used for dolibarr security (not common functions).
 *  			Warning, this file must not depends on other library files, except function.lib.php
 *  			because it is used at low code level.
 */


/**
 * Return if we are using a HTTPS connection
 * Check HTTPS (no way to be modified by user but may be empty or wrong if user is using a proxy)
 * Take HTTP_X_FORWARDED_PROTO (defined when using proxy)
 * Then HTTP_X_FORWARDED_SSL
 *
 * @return	boolean		True if user is using HTTPS
 */
function isHTTPS()
{
	$isSecure = false;
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		$isSecure = true;
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
		$isSecure = true;
	}
	return $isSecure;
}

define('MAIN_SECURITY_REVERSIBLE_ALGO', 'AES-256-CTR');

/**
 *	Encode a string with a symmetric encryption. Used to encrypt sensitive data into database.
 *  Note: If a backup is restored onto another instance with a different $conf->file->instance_unique_id, then decoded value will differ.
 *  This function is called for example by dol_set_const() when saving a sensible data into database, like into configuration table llx_const, or societe_rib, ...
 *
 *	@param   string		$chain		String to encode
 *	@param   string		$key		If '', we use $conf->file->instance_unique_id (so $dolibarr_main_instance_unique_id in conf.php)
 *  @param	 string		$ciphering	Default ciphering algorithm
 *  @param	 string		$forceseed	To force the seed
 *	@return  string					encoded string, with format 'dolcrypt:CIPHERING:seed:cryptedpass'
 *  @since v17
 *  @see dolDecrypt(), dol_hash()
 */
function dolEncrypt($chain, $key = '', $ciphering = '', $forceseed = '')
{
	global $conf;
	global $dolibarr_disable_dolcrypt_for_debug;

	if ($chain === '' || is_null($chain)) {
		return '';
	}

	$reg = array();
	if (preg_match('/^dolcrypt:([^:]+):(.+)$/', $chain, $reg)) {
		// The $chain is already a encrypted string
		return $chain;
	}

	if (empty($key)) {
		if (!empty($conf->file->dolcrypt_key)) {
			// If dolcrypt_key is defined, we used it in priority. Note: this param was never been set for the moment.
			$key = $conf->file->dolcrypt_key;
		} else {
			// We fall back on the instance_unique_id (coming from $dolibarr_main_instance_unique_id, for backward compatibility).
			$key = $conf->file->instance_unique_id;
		}
	}
	if (empty($ciphering)) {
		$ciphering = constant('MAIN_SECURITY_REVERSIBLE_ALGO');
	}

	$newchain = $chain;

	if (function_exists('openssl_encrypt') && empty($dolibarr_disable_dolcrypt_for_debug)) {
		if (empty($key)) {
			return $chain;
		}

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
			$ivseed = dol_substr(md5($forceseed), 0, $ivlen, 'ascii', 1);
		}

		$newchain = openssl_encrypt($chain, $ciphering, $key, 0, $ivseed);
		return 'dolcrypt:'.$ciphering.':'.$ivseed.':'.$newchain;
	} else {
		return $chain;
	}
}

/**
 *	Decode a string with a symmetric encryption. Used to decrypt sensitive data saved into database.
 *  Note: If a backup is restored onto another instance with a different $conf->file->instance_unique_id, then decoded value will differ.
 *
 *	@param   string		$chain		string to decode
 *	@param   string		$key		If '', we use $conf->file->dolcrypt_key else $conf->file->instance_unique_id
 *	@return  string					encoded string
 *  @since v17
 *  @see dolEncrypt(), dol_hash()
 */
function dolDecrypt($chain, $key = '')
{
	global $conf;

	if ($chain === '' || is_null($chain)) {
		return '';
	}

	if (empty($key)) {
		if (!empty($conf->file->dolcrypt_key)) {
			// If dolcrypt_key is defined, we used it in priority. Note: this param was never been set for the moment.
			$key = $conf->file->dolcrypt_key;
		} else {
			// We fall back on the instance_unique_id (coming from $dolibarr_main_instance_unique_id, for backward compatibility).
			$key = !empty($conf->file->instance_unique_id) ? $conf->file->instance_unique_id : "";
		}
	}

	$reg = array();

	// Old method (no more used, kept for compatibility)
	if (preg_match('/^crypted:(.+)$/', $chain, $reg)) {
		return dol_decode($reg[1]);
	}

	// New method
	if (preg_match('/^dolcrypt:([^:]+):(.+)$/', $chain, $reg)) {
		// Do not enable this log, except during debug
		//dol_syslog("We try to decrypt the chain: ".$chain, LOG_DEBUG);

		$ciphering = $reg[1];
		if (function_exists('openssl_decrypt')) {
			if (empty($key)) {
				dol_syslog("Error dolDecrypt decrypt key is empty", LOG_WARNING);
				return $chain;
			}
			$tmpexplode = explode(':', $reg[2]);
			if (!empty($tmpexplode[1])) {
				$newchain = openssl_decrypt($tmpexplode[1], $ciphering, $key, 0, $tmpexplode[0]);
			} else {
				$newchain = openssl_decrypt((string) $tmpexplode[0], $ciphering, $key, 0, '');
			}
			// Test validity of decryption
			if (!ascii_check($newchain)) {
				dol_syslog("Error dolDecrypt failed: The key dolibarr_main_dolcrypt or dolibarr_main_instance_unique_id, found in conf.php file, is the the one used to encrypt this encrypted string", LOG_ERR);
				return $chain;
			}
		} else {
			dol_syslog("Error dolDecrypt openssl_decrypt is not available", LOG_ERR);
			return $chain;
		}
		return $newchain;
	} else {
		return $chain;
	}
}

/**
 * 	Returns a hash (non reversible encryption) of a string.
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function (recommended value is 'password_hash')
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt (used only if hashing algorithm is something else than 'password_hash').
 *
 * 	@param 		string		$chain		String to hash
 * 	@param		'auto'|'0'|'sha1'|'1'|'sha1md5'|'2'|'md5'|'3'|'openldap'|'4'|'sha256'|'5'|'password_hash'|'6'|'hash'	$type		Type of hash:
 *                                                                                                                  		        'auto' or '0': will use MAIN_SECURITY_HASH_ALGO else md5
 *                                                                                                                          		'sha1' or '1': sha1
 *  		                                                                                                                        'sha1md5' or '2': sha1md5
 *      		                                                                                                                    'md5' or '3': md5
 *              		                                                                                                            'openldapxxx' or '4': for OpenLdap
 *                      		                                                                                                    'sha256' or '5': sha256
 *                              		                                                                                            'password_hash' or '6': password_hash
 *                                      		                                                                                    Use 'md5' if hash is not needed for security purpose. For security need, prefer 'auto'.
 * 	@param 		int 		$nosalt		Do not include any salt
 *  @param		int			$mode		0=Return encoded password, 1=Return array with encoding password + encoding algorithm
 * 	@return		string|array{pass_encrypted:string,pass_encoding:string}	Hash of string or array with pass_encrypted and pass_encoding
 *  @see getRandomPassword(), dol_verifyHash()
 */
function dol_hash($chain, $type = '0', $nosalt = 0, $mode = 0)
{
	// No need to add salt for password_hash
	if (($type == '0' || $type == 'auto') && getDolGlobalString('MAIN_SECURITY_HASH_ALGO') == 'password_hash' && function_exists('password_hash')) {
		// if string contains a null character that can't be encoded. Return an error instead of fatal error.
		if (strpos($chain, "\0") !== false) {
			if ($mode == 1) {
				return array('pass_encrypted' => 'Invalid string to encrypt. Contains a null character', 'pass_encoding' => '');
			} else {
				return 'Invalid string to encrypt. Contains a null character.';
			}
		}

		// Build a password hash with default algorithm
		if ($mode == 1) {
			return array('pass_encrypted' => password_hash($chain, PASSWORD_DEFAULT), 'pass_encoding' => 'password_hash');
		} else {
			return password_hash($chain, PASSWORD_DEFAULT);
		}
	}

	// Salt value
	if (getDolGlobalString('MAIN_SECURITY_SALT') && $type != '4' && $type !== 'openldap' && empty($nosalt)) {
		$chain = getDolGlobalString('MAIN_SECURITY_SALT') . $chain;
	}

	if ($type == '1' || $type == 'sha1') {
		if ($mode == 1) {
			return array('pass_encrypted' => sha1($chain), 'pass_encoding' => 'sha1');
		} else {
			return sha1($chain);
		}
	} elseif ($type == '2' || $type == 'sha1md5') {
		if ($mode == 1) {
			return array('pass_encrypted' => sha1(md5($chain)), 'pass_encoding' => 'sha1md5');
		} else {
			return sha1(md5($chain));
		}
	} elseif ($type == '3' || $type == 'md5') {		// For hashing with no need of security
		if ($mode == 1) {
			return array('pass_encrypted' => md5($chain), 'pass_encoding' => 'md5');
		} else {
			return md5($chain);
		}
	} elseif ($type == '4' || $type == 'openldap') {
		if ($mode == 1) {
			return array('pass_encrypted' => dolGetLdapPasswordHash($chain, getDolGlobalString('LDAP_PASSWORD_HASH_TYPE', 'md5')), 'pass_encoding' => 'ldappasswordhash'.getDolGlobalString('LDAP_PASSWORD_HASH_TYPE', 'md5'));
		} else {
			return dolGetLdapPasswordHash($chain, getDolGlobalString('LDAP_PASSWORD_HASH_TYPE', 'md5'));
		}
	} elseif ($type == '5' || $type == 'sha256') {
		if ($mode == 1) {
			return array('pass_encrypted' => hash('sha256', $chain), 'pass_encoding' => 'sha256');
		} else {
			return hash('sha256', $chain);
		}
	} elseif ($type == '6' || $type == 'password_hash') {
		if ($mode == 1) {
			return array('pass_encrypted' => password_hash($chain, PASSWORD_DEFAULT), 'pass_encoding' => 'password_hash');
		} else {
			return password_hash($chain, PASSWORD_DEFAULT);
		}
	} elseif (getDolGlobalString('MAIN_SECURITY_HASH_ALGO') == 'sha1') {
		if ($mode == 1) {
			return array('pass_encrypted' => sha1($chain), 'pass_encoding' => 'sha1');
		} else {
			return sha1($chain);
		}
	} elseif (getDolGlobalString('MAIN_SECURITY_HASH_ALGO') == 'sha1md5') {
		if ($mode == 1) {
			return array('pass_encrypted' => sha1(md5($chain)), 'pass_encoding' => 'sha1md5');
		} else {
			return sha1(md5($chain));
		}
	}

	// No particular encoding defined, use default
	if ($mode == 1) {
		return array('pass_encrypted' => md5($chain), 'pass_encoding' => 'md5');
	} else {
		return md5($chain);
	}
}

/**
 * 	Compute a hash and compare it to the given one
 *  For backward compatibility reasons, if the hash is not in the password_hash format, we will try to match against md5 and sha1md5
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function.
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt.
 *
 * 	@param 		string		$chain		String to hash (not hashed string)
 * 	@param 		string		$hash		hash to compare
 * 	@param		string		$type		Type of hash ('0':auto, '1':sha1, '2':sha1+md5, '3':md5, '4': for OpenLdap, '5':sha256, 'hash'). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		bool					True if the computed hash is the same as the given one
 *  @see dol_hash()
 */
function dol_verifyHash($chain, $hash, $type = '0')
{
	if ($type == '0' && getDolGlobalString('MAIN_SECURITY_HASH_ALGO') == 'password_hash' && function_exists('password_verify')) {
		// Try to autodetect which algo we used
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
