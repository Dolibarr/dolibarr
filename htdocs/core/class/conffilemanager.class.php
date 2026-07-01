<?php
/* Copyright (C) 2026 ATM Consulting <contact@atm-consulting.fr>
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
 */

/**
 *	\file       htdocs/core/class/conffilemanager.class.php
 *	\ingroup    core
 *	\brief      Class to build, parse and patch the conf.php configuration file.
 */

/**
 *	Manage the content of the Dolibarr conf.php file.
 *
 *	Self-contained helper (no HTTP, no echo, no direct database access): it only manipulates strings, so it can
 *	be unit tested (through the standard PHPUnit bootstrap) and reused by the installer (build/reuse), the upgrade
 *	process (diff) and the admin UI. It still relies on a few Dolibarr helpers (dol_escape_php, dol_print_date,
 *	dolDecrypt, ...) to format values and decrypt the database password.
 *
 *	Two write strategies are offered:
 *	 - build():            generate an exhaustive, sectioned and commented conf.php from a canvas
 *	                       (used when no template is reused).
 *	 - buildFromTemplate(): regenerate the same exhaustive layout while reusing the values of an existing
 *	                       conf.php (used when a template is reused).
 */
class ConfFileManager
{
	/**
	 * @var string Write the assignment even when the value is empty.
	 */
	const MODE_VALUE = 'value';

	/**
	 * @var string Write the assignment commented out when the value is empty,
	 *             so the Dolibarr internal default applies (used for settings
	 *             where "absent" is not equivalent to "empty", e.g. security defaults).
	 */
	const MODE_COMMENT_IF_EMPTY = 'commentifempty';

	/**
	 * @var array<string,string> Lexicon of deprecated conf variables no longer used by Dolibarr, mapped to their
	 *                           replacement (empty string when removed with no replacement). When such a variable is
	 *                           found in a reused conf.php it is reported and kept commented (value preserved for
	 *                           reference). Only variables that are no longer read anywhere in the code belong here.
	 */
	const DEPRECATION_MAP = array(
		'dolibarr_main_cookie_cryptkey'  => 'dolibarr_main_instance_unique_id', // former name of the instance unique id, renamed in Dolibarr 10.0
		'dolibarr_lib_FPDFI_PATH'        => 'dolibarr_lib_FPDI_PATH',           // former path to the FPDI library, renamed before Dolibarr 3.3
		'dolibarr_lib_ADODB_PATH'        => '',                                 // path to the ADODB library, removed in Dolibarr 17.0
		'dolibarr_lib_PHPEXCEL_PATH'     => '',                                 // path to PHPExcel, removed before Dolibarr 3.3
		'dolibarr_js_JQUERY_FLOT'        => '',                                 // path to the jQuery Flot charts, removed in Dolibarr 14.0
		'dolibarr_smarty_cache'          => '',                                 // Smarty cache directory, removed before Dolibarr 3.3
		'dolibarr_smarty_compile'        => '',                                 // Smarty compiled-templates directory, removed before Dolibarr 3.3
		'dolibarr_smarty_libs_dir'       => '',                                 // Smarty libraries directory, removed before Dolibarr 3.3
		'dolibarr_pdf_force_fpdf'        => '',                                 // forced the FPDF generator instead of TCPDF, removed in Dolibarr 8.0
		'dolibarr_allow_overwritekernel' => '',                                // allowed overwriting core files, removed before Dolibarr 3.3
	);

	/**
	 *	Return the ordered canvas describing every conf.php variable.
	 *
	 *	Single source of truth for build(). Each descriptor holds:
	 *	 - section: section label used to group variables,
	 *	 - key:     variable name without the leading '$',
	 *	 - default: default value,
	 *	 - type:    'string' (quoted), 'int' (raw integer) or 'raw' (literal PHP, e.g. array()),
	 *	 - mode:    self::MODE_VALUE or self::MODE_COMMENT_IF_EMPTY,
	 *	 - comment: short explanation appended as a reminder,
	 *	 - compact: (optional) true to render a short block without the per-variable comment and default lines,
	 *	 - subsection: (optional) one-line header printed once before a compact block (e.g. a pointer to conf.php.example).
	 *
	 *	@return	array<int,array{section:string,key:string,default:string,type:string,mode:string,comment:string,compact?:bool,subsection?:string}>
	 */
	public function getCanvas()
	{
		$s = self::MODE_VALUE;
		$c = self::MODE_COMMENT_IF_EMPTY;

		return array(
			// --- Main parameters ---
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_url_root', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Root URL of the index.php page, without ending slash'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_document_root', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Absolute path of the htdocs directory'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_url_root_alt', 'default' => '/custom', 'type' => 'string', 'mode' => $s, 'comment' => 'Relative sub URL for external modules'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_document_root_alt', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Absolute path for external modules'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_data_root', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Absolute path of the documents directory'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_host', 'default' => 'localhost', 'type' => 'string', 'mode' => $s, 'comment' => 'Database server host name or ip address'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_port', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Database server port. Default 0, see conf.php.example for more details'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_name', 'default' => 'dolibarr', 'type' => 'string', 'mode' => $s, 'comment' => 'Database name'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_user', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Database user'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_pass', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Database password (may be prefixed by crypted: or dolcrypt:)'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_type', 'default' => 'mysqli', 'type' => 'string', 'mode' => $s, 'comment' => 'Database driver: mysqli or pgsql'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_character_set', 'default' => 'utf8', 'type' => 'string', 'mode' => $s, 'comment' => 'Database character set'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_collation', 'default' => 'utf8_unicode_ci', 'type' => 'string', 'mode' => $s, 'comment' => 'Database collation'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_encryption', 'default' => '', 'type' => 'int', 'mode' => $c, 'comment' => 'Deprecated reversible AES encryption of llx_const values (0 = off). Do not use'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_readonly', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to run the application in readonly mode'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_dolcrypt_key', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Secret key for dolEncrypt/dolDecrypt. When empty, instance_unique_id is used'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_instance_unique_id', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Unique id of the installation (also used as encryption salt)'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_db_prefix', 'default' => 'llx_', 'type' => 'string', 'mode' => $s, 'comment' => 'Database tables prefix'),

			// --- Login ---
			array('section' => 'Login', 'key' => 'dolibarr_main_authentication', 'default' => 'dolibarr', 'type' => 'string', 'mode' => $s, 'comment' => 'Authentication mode (dolibarr, http, ldap, openid_connect, ...)'),
			array('section' => 'Login', 'key' => 'dolibarr_main_authentication_autocreateuser', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to auto-create users on first OIDC login'),
			array('section' => 'Login', 'key' => 'dolibarr_auto_user', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Login used when authentication mode is forceuser'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_host', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'subsection' => 'LDAP login (only when dolibarr_main_authentication contains \'ldap\'). See conf.php.example for parameters and examples.', 'comment' => 'LDAP server(s), e.g. 127.0.0.1'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_port', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP port, e.g. 389'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_version', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP protocol version, e.g. 3'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_servertype', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'openldap, activedirectory or egroupware'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_login_attribute', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP login attribute (uid, samaccountname, ...)'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_dn', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP DN'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_filter', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP search filter'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_admin_login', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP admin login (if anonymous bind disabled)'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_admin_pass', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP admin password (if anonymous bind disabled)'),
			array('section' => 'Login', 'key' => 'dolibarr_main_auth_ldap_debug', 'default' => '', 'type' => 'string', 'mode' => $c, 'compact' => true, 'comment' => 'LDAP debug flag (true/false)'),
			array('section' => 'Login', 'key' => 'dolibarr_main_demo', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Login and pass for demo mode, e.g. autologin,autopass'),

			// --- Security ---
			array('section' => 'Security', 'key' => 'dolibarr_main_force_https', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Force HTTPS mode (0, 1, 2 or an https URL)'),
			array('section' => 'Security', 'key' => 'dolibarr_main_prod', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 on production to hide error messages'),
			// Written as a raw literal (never user input here) so the secure default keeps its double quote, which dol_escape_php() would otherwise convert.
			array('section' => 'Security', 'key' => 'dolibarr_login_badcharunauthorized', 'default' => "',@<>\"\\''", 'type' => 'raw', 'mode' => $s, 'comment' => 'Forbidden characters in logins (secure default written explicitly)'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_os_commands', 'default' => 'mariadb-dump, mariadb, mysqldump, mysql, pg_dump, pg_restore, clamdscan, clamdscan.exe', 'type' => 'string', 'mode' => $s, 'comment' => 'Allowed OS commands for backup feature'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_eval_methods', 'default' => 'getDolGlobalString, getDolGlobalInt, getDolCurrency, getDolEntity, getDolDBType, fetchNoCompute, hasRight, isAdmin, isModEnabled, isStringVarMatching, abs, min, max, round, dol_now, preg_match', 'type' => 'string', 'mode' => $s, 'comment' => 'Whitelist of functions allowed in computed fields'),
			array('section' => 'Security', 'key' => 'dolibarr_main_disabled_modules', 'default' => 'array()', 'type' => 'raw', 'mode' => $c, 'comment' => 'Modules forbidden to enable, e.g. array(\'dav\', \'api\')'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_ip', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Restrict backoffice access to a list of IP/CIDR'),
			array('section' => 'Security', 'key' => 'dolibarr_nocsrfcheck', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Disable CSRF protection (0, 1 or 2). Keep 0 in most cases'),
			array('section' => 'Security', 'key' => 'dolibarr_main_csrf_with_token', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Force MAIN_SECURITY_CSRF_WITH_TOKEN (0,1,2,3) over the database value. See conf.php.example'),
			array('section' => 'Security', 'key' => 'dolibarr_api_count_always_enabled', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 so API call count can not be disabled from admin'),
			array('section' => 'Security', 'key' => 'dolibarr_cron_allow_cli', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to allow command lines in the internal Job scheduler'),
			array('section' => 'Security', 'key' => 'dolibarr_mailing_limit_sendbyweb', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Hard limit for mailing sent by web (-1 to forbid)'),
			array('section' => 'Security', 'key' => 'dolibarr_mailing_limit_sendbycli', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Hard limit for mailing sent by cli (-1 to forbid)'),
			array('section' => 'Security', 'key' => 'dolibarr_main_stream_to_disable', 'default' => "array('compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zip', 'zlib')", 'type' => 'raw', 'mode' => $s, 'comment' => 'PHP streams to disable (secure default written explicitly)'),
			array('section' => 'Security', 'key' => 'dolibarr_website_allow_custom_php', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow PHP dynamic content into a website (0, 1 or 2)'),
			array('section' => 'Security', 'key' => 'dolibarr_allow_localurl_for_webhooks', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow webhooks to use a local URL'),
			array('section' => 'Security', 'key' => 'dolibarr_allow_unsecured_select_in_extrafields_filter', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow subrequests inside USF IN filters'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_type', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Internal database session handler (experimental, e.g. db)'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_host', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database host'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_name', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database name'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_user', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database user'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_pass', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database password'),

			// --- Other ---
			array('section' => 'Other', 'key' => 'dolibarr_main_limit_users', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Maximum number of users that can be created (0 = unlimited)'),
			array('section' => 'Other', 'key' => 'dolibarr_strict_mode', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Enable PHP strict mode (dev only)'),
			array('section' => 'Other', 'key' => 'dolibarr_allow_download_external_modules', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow downloading the zip of external modules from admin'),
			array('section' => 'Other', 'key' => 'dolibarr_main_distrib', 'default' => 'standard', 'type' => 'string', 'mode' => $s, 'comment' => 'Distribution name'),

			// --- Paths ---
			array('section' => 'Paths', 'key' => 'dolibarr_lib_FPDF_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the FPDF library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_TCPDF_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the TCPDF library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_FPDI_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the FPDI library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_TCPDI_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the TCPDI library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_GEOIP_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the GeoIP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_NUSOAP_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the NuSOAP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_ODTPHP_PATH', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the ODTPHP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_ODTPHP_PATHTOPCLZIP', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the PCLZIP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_js_CKEDITOR', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the shared CKEditor'),
			array('section' => 'Paths', 'key' => 'dolibarr_js_JQUERY', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the shared jQuery'),
			array('section' => 'Paths', 'key' => 'dolibarr_js_JQUERY_UI', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the shared jQuery UI'),
			array('section' => 'Paths', 'key' => 'dolibarr_font_DOL_DEFAULT_TTF', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the default TTF font'),
			array('section' => 'Paths', 'key' => 'dolibarr_font_DOL_DEFAULT_TTF_BOLD', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Override path to the default bold TTF font'),
		);
	}

	/**
	 *	Return the list of variable names known by the canvas.
	 *
	 *	@return	string[]	List of variable names (without the leading '$').
	 */
	public function getCanvasKeys()
	{
		$keys = array();
		foreach ($this->getCanvas() as $descriptor) {
			$keys[] = $descriptor['key'];
		}
		return $keys;
	}

	/**
	 *	Parse the raw content of a conf.php file.
	 *
	 *	Only non commented assignments of $dolibarr_* variables are extracted. Values are kept raw (right-hand
	 *	side as written) so they can be reused verbatim when reusing a template.
	 *
	 *	Limitation: only single-line assignments ($var = ...; on one line) are recognized. A multi-line value
	 *	(e.g. an array spread over several lines) or a concatenated expression is not parsed and is therefore
	 *	ignored (neither reused nor reported), rather than partially captured.
	 *
	 *	@param	string	$content	Raw content of the conf.php file.
	 *	@return	array{values:array<string,string>,unknown:string[],deprecated:string[],missing:string[]}
	 */
	public function parse($content)
	{
		$values = array();
		$unknown = array();
		$deprecated = array();

		$canvasKeys = $this->getCanvasKeys();

		$lines = preg_split('/\r\n|\r|\n/', (string) $content);
		if (!is_array($lines)) {
			$lines = array();
		}
		foreach ($lines as $line) {
			$reg = array();
			if (!preg_match('/^\s*\$(dolibarr_[a-z0-9_]+)\s*=\s*(.*?);\s*(?:\/\/.*)?$/i', $line, $reg)) {
				continue;
			}
			$key = $reg[1];
			$values[$key] = trim($reg[2]);

			if (array_key_exists($key, self::DEPRECATION_MAP)) {
				$deprecated[] = $key;
			} elseif (!in_array($key, $canvasKeys, true)) {
				$unknown[] = $key;
			}
		}

		$missing = array();
		foreach ($canvasKeys as $canvasKey) {
			if (!array_key_exists($canvasKey, $values)) {
				$missing[] = $canvasKey;
			}
		}

		return array('values' => $values, 'unknown' => $unknown, 'deprecated' => $deprecated, 'missing' => $missing);
	}

	/**
	 *	Resolve the instance unique id from parsed values.
	 *
	 *	An existing (non deprecated) instance_unique_id is kept. A deprecated legacy cookie_cryptkey is
	 *	NOT reused as the key: a fresh id is generated and the old line is flagged to be kept commented for
	 *	reference (so each provisioned instance gets its own unique id, while the old value stays recoverable).
	 *
	 *	@param	array<string,string>	$values		Parsed values (raw right-hand sides).
	 *	@return	array{instanceKey:string,oldKey:string,commentOut:string[]}	New id, old effective key (for dolcrypt) and deprecated variables to keep commented.
	 */
	public function resolveInstanceKey($values)
	{
		// A fresh instance unique id is always generated at install, so each instance has its own id even
		// when the reused template already contains one.
		$newId = bin2hex(random_bytes(32));

		// Old effective key, kept so dolcrypt-encrypted data stays decryptable after the id is regenerated:
		// existing dolcrypt_key, else instance_unique_id, else the legacy cookie_cryptkey.
		$oldKey = '';
		foreach (array('dolibarr_main_dolcrypt_key', 'dolibarr_main_instance_unique_id', 'dolibarr_main_cookie_cryptkey') as $k) {
			if (!empty($values[$k])) {
				$oldKey = $this->unquoteScalar($values[$k]);
				break;
			}
		}

		$commentOut = array();
		if (!empty($values['dolibarr_main_cookie_cryptkey'])) {
			$commentOut[] = 'dolibarr_main_cookie_cryptkey';
		}

		return array('instanceKey' => $newId, 'oldKey' => $oldKey, 'commentOut' => $commentOut);
	}

	/**
	 *	Read the connection parameters (paths + database) from an existing conf.php template.
	 *
	 *	The file is parsed (not included), so no code from the file is executed. The database password is
	 *	decrypted when stored encrypted (crypted: or dolcrypt:). Used when reusing a template: the parameters
	 *	then come from the template instead of the (disabled) install form.
	 *
	 *	@param	string	$rawTemplate	Raw content of the existing conf.php template.
	 *	@return	array<string,string>	Map: document_root, data_root, url_root, db_type, db_host, db_port, db_name, db_user, db_prefix, db_pass.
	 */
	public function getConnectionParams($rawTemplate)
	{
		$parsed = $this->parse($rawTemplate);
		$values = $parsed['values'];

		$params = array();
		foreach (array('document_root', 'data_root', 'url_root', 'db_type', 'db_host', 'db_port', 'db_name', 'db_user', 'db_prefix') as $short) {
			$key = 'dolibarr_main_'.$short;
			$params[$short] = isset($values[$key]) ? $this->unquoteScalar($values[$key]) : '';
		}

		// dolcrypt key effectively used by the template: dolcrypt_key, else instance_unique_id, else the
		// legacy cookie_cryptkey (so a password from an old conf can still be decrypted for the connection).
		$rawpass = isset($values['dolibarr_main_db_pass']) ? $this->unquoteScalar($values['dolibarr_main_db_pass']) : '';
		$cryptkey = '';
		foreach (array('dolibarr_main_dolcrypt_key', 'dolibarr_main_instance_unique_id', 'dolibarr_main_cookie_cryptkey') as $k) {
			if (!empty($values[$k])) {
				$cryptkey = $this->unquoteScalar($values[$k]);
				break;
			}
		}
		$params['db_pass'] = $this->decryptDbPass($rawpass, $cryptkey);

		return $params;
	}

	/**
	 *	Decrypt a database password stored encrypted in a conf file (crypted: or dolcrypt: prefix).
	 *
	 *	@param	string	$pass		Raw password value (possibly prefixed by crypted: or dolcrypt:).
	 *	@param	string	$cryptkey	dolcrypt key to use (dolcrypt_key, instance_unique_id or legacy cookie_cryptkey).
	 *	@return	string	The clear password.
	 */
	private function decryptDbPass($pass, $cryptkey)
	{
		if ($pass === '') {
			return '';
		}
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		if (preg_match('/^crypted:/i', $pass)) {
			return dol_decode(preg_replace('/^crypted:/i', '', $pass));
		}
		if (preg_match('/^dolcrypt:/i', $pass)) {
			return dolDecrypt($pass, $cryptkey);
		}
		return $pass;
	}

	/**
	 *	Build an exhaustive, sectioned and commented conf.php content (no template reused).
	 *
	 *	@param	array<string,string>	$values		Concrete values keyed by variable name. Missing keys fall back to the canvas default.
	 *	@param	string					$topBlock	Optional block inserted right after the file header, before the sections.
	 *	@return	string	Full conf.php content.
	 */
	public function build($values, $topBlock = '')
	{
		if (!is_array($values)) {
			$values = array();
		}

		$separator = '// '.str_repeat('=', 61);

		$out = '<?php'."\n";
		$out .= '//'."\n";
		$out .= '// File generated by Dolibarr installer '.(defined('DOL_VERSION') ? DOL_VERSION : '').' on '.dol_print_date(dol_now(), '')."\n";
		$out .= '//'."\n";
		$out .= '// Take a look at conf.php.example file for an example of conf.php file'."\n";
		$out .= '// and explanations for all possibles parameters.'."\n";

		if ($topBlock !== '') {
			$out .= "\n".$topBlock;
		}

		$lastSection = '';
		foreach ($this->getCanvas() as $descriptor) {
			if ($descriptor['section'] !== $lastSection) {
				$out .= "\n".$separator."\n";
				$out .= '//  '.strtoupper($descriptor['section'])."\n";
				$out .= $separator."\n";
				$lastSection = $descriptor['section'];
			}

			$key = $descriptor['key'];
			$value = array_key_exists($key, $values) ? $values[$key] : $descriptor['default'];

			$assignment = '$'.$key.'='.$this->serializeValue($descriptor, $value).';';
			if ($descriptor['mode'] === self::MODE_COMMENT_IF_EMPTY && $this->isEmptyValue($value)) {
				$assignment = '//'.$assignment;
			}

			if (!empty($descriptor['compact'])) {
				// Compact sub-section: a single optional header line (e.g. a pointer to conf.php.example) followed by
				// bare assignments, to keep large optional blocks (e.g. LDAP) short instead of one comment block per line.
				if (!empty($descriptor['subsection'])) {
					$out .= "\n".'// '.$descriptor['subsection']."\n";
				}
				$out .= $assignment."\n";
				continue;
			}

			// Blank line before each variable, description and default value on their own lines (readability).
			$out .= "\n";
			$out .= '// '.$descriptor['comment']."\n";
			$out .= '// Default: '.$this->renderDefaultComment($descriptor)."\n";
			$out .= $assignment."\n";
		}

		// Trailing blank line at the end of the file.
		$out .= "\n";

		return $out;
	}

	/**
	 *	Build an exhaustive conf.php (same layout as build()) reusing the values of an existing template.
	 *
	 *	The values already set in the template are kept. A variable that is commented out, absent or present but
	 *	empty is NOT reused: it falls back to $fallbackValues (the fresh-install values resolved by the installer,
	 *	e.g. auto-detected paths and standard database defaults) and finally to the canvas default. This guarantees
	 *	mandatory variables are never left empty when reusing a partial template.
	 *	A fresh instance_unique_id is always generated; when the database password is dolcrypt-encrypted the
	 *	old key is pinned into dolcrypt_key so it stays decryptable. Deprecated variables are kept commented for
	 *	reference, and custom/unknown variables are preserved verbatim, so nothing is silently lost.
	 *
	 *	@param	string					$rawTemplate	Raw content of the existing conf.php template.
	 *	@param	array<string,string>	$fallbackValues	Values used when a canvas variable is missing/empty in the template (keyed by variable name).
	 *	@return	array{content:string,unknown:string[],deprecated:string[],missing:string[]}
	 */
	public function buildFromTemplate($rawTemplate, $fallbackValues = array())
	{
		if (!is_array($fallbackValues)) {
			$fallbackValues = array();
		}

		$parsed = $this->parse($rawTemplate);

		// Re-use the values already set in the template for the known canvas variables.
		$values = array();
		foreach ($this->getCanvas() as $descriptor) {
			$key = $descriptor['key'];
			$reused = null;
			if (array_key_exists($key, $parsed['values'])) {
				// 'raw' values (array(...) or pre-quoted literals) are kept verbatim; the others are unquoted
				// so build() re-serializes them properly (and does not double-escape).
				$reused = ($descriptor['type'] === 'raw') ? $parsed['values'][$key] : $this->unquoteScalar($parsed['values'][$key]);
			}
			// A template value present but empty is treated like a missing one (so mandatory variables fall back).
			if ($reused !== null && !$this->isEmptyValue($reused)) {
				$values[$key] = $reused;
			} elseif (isset($fallbackValues[$key]) && $fallbackValues[$key] !== '') {
				$values[$key] = $fallbackValues[$key];
			}
			// else: leave unset -> build() applies the canvas default
		}

		// A fresh instance_unique_id is always generated (see resolveInstanceKey).
		$keyres = $this->resolveInstanceKey($parsed['values']);
		$values['dolibarr_main_instance_unique_id'] = $keyres['instanceKey'];

		// If the database password is dolcrypt-encrypted, it was encrypted with the OLD key. Since the
		// instance_unique_id is regenerated, pin the old key into dolcrypt_key so the password (and any other
		// dolcrypt-encrypted data) stays decryptable.
		$rawpass = isset($parsed['values']['dolibarr_main_db_pass']) ? $this->unquoteScalar($parsed['values']['dolibarr_main_db_pass']) : '';
		if (preg_match('/^dolcrypt:/i', $rawpass) && empty($values['dolibarr_main_dolcrypt_key']) && $keyres['oldKey'] !== '') {
			$values['dolibarr_main_dolcrypt_key'] = $keyres['oldKey'];
		}

		$separator = '// '.str_repeat('=', 61);

		// Deprecated variables: kept commented (value preserved for reference), placed at the TOP of the file.
		$deprecatedBlock = '';
		if (!empty($parsed['deprecated'])) {
			$deprecatedBlock = $separator."\n";
			$deprecatedBlock .= '//  DEPRECATED VARIABLES (commented, kept for reference)'."\n";
			$deprecatedBlock .= $separator."\n";
			foreach ($parsed['deprecated'] as $key) {
				$replacement = array_key_exists($key, self::DEPRECATION_MAP) ? self::DEPRECATION_MAP[$key] : '';
				$note = ($replacement !== '') ? ' (replaced by '.$replacement.')' : ' (no longer used)';
				$deprecatedBlock .= "\n".'// deprecated'.$note.' :'."\n";
				$deprecatedBlock .= '//$'.$key.'='.$parsed['values'][$key].';'."\n";
			}
		}

		$content = $this->build($values, $deprecatedBlock);

		// Preserve custom (unknown) variables verbatim so the administrator's additions are not lost.
		// build() already ends with the trailing blank line, which doubles as the separator before this section.
		if (!empty($parsed['unknown'])) {
			$content .= $separator."\n";
			$content .= '//  ADDITIONAL VARIABLES (kept from the existing conf.php)'."\n";
			$content .= $separator."\n";
			foreach ($parsed['unknown'] as $key) {
				$content .= "\n".'$'.$key.'='.$parsed['values'][$key].';'."\n";
			}
			// Restore the trailing blank line at the very end of the file.
			$content .= "\n";
		}

		return array(
			'content' => $content,
			'unknown' => $parsed['unknown'],
			'deprecated' => $parsed['deprecated'],
			'missing' => $parsed['missing'],
		);
	}

	/**
	 *	Compute, against a parsed template, the canvas variables that are missing from it.
	 *
	 *	Used to warn during an upgrade that new conf variables are available.
	 *
	 *	@param	array<string,string>	$templateValues		Values parsed from the conf in place.
	 *	@return	array{newVars:string[]}	List of canvas variables absent from the conf.
	 */
	public function diff($templateValues)
	{
		if (!is_array($templateValues)) {
			$templateValues = array();
		}

		$newVars = array();
		foreach ($this->getCanvasKeys() as $canvasKey) {
			if (!array_key_exists($canvasKey, $templateValues)) {
				$newVars[] = $canvasKey;
			}
		}

		return array('newVars' => $newVars);
	}

	/**
	 *	Validate that a conf.php content is syntactically valid PHP.
	 *
	 *	Uses token_get_all() with the TOKEN_PARSE flag, which raises a ParseError on invalid PHP. This is a pure
	 *	PHP check (no exec, no temporary file) and handles multi-line constructs natively. The .old backup/rollback
	 *	remains the safety net on the caller side.
	 *
	 *	@param	string	$content	conf.php content to validate.
	 *	@return	bool	True when the content is syntactically valid PHP.
	 */
	public function validateSyntax($content)
	{
		try {
			token_get_all((string) $content, TOKEN_PARSE);
		} catch (\ParseError $e) {
			return false;
		}
		return true;
	}

	/**
	 *	Serialize a value according to its canvas descriptor type.
	 *
	 *	@param	array{type:string}	$descriptor		Canvas descriptor.
	 *	@param	string				$value			Value to serialize.
	 *	@return	string	PHP literal representation.
	 */
	private function serializeValue($descriptor, $value)
	{
		if ($descriptor['type'] === 'int') {
			return (string) ((int) $value);
		}
		if ($descriptor['type'] === 'raw') {
			return (string) ($this->isEmptyValue($value) ? $descriptor['default'] : $value);
		}
		return '\''.dol_escape_php((string) $value, 1).'\'';
	}

	/**
	 *	Render the default value as shown in the reminder comment.
	 *
	 *	@param	array{default:string,type:string}	$descriptor		Canvas descriptor.
	 *	@return	string	Human readable default.
	 */
	private function renderDefaultComment($descriptor)
	{
		if ($descriptor['default'] === '') {
			return '(empty)';
		}
		return $descriptor['default'];
	}

	/**
	 *	Tell whether a value is considered empty for the comment-if-empty mode.
	 *
	 *	@param	string	$value	Value to test.
	 *	@return	bool	True when empty.
	 */
	private function isEmptyValue($value)
	{
		return ($value === '' || $value === null || $value === 'array()');
	}

	/**
	 *	Remove the surrounding quotes of a raw scalar right-hand side.
	 *
	 *	@param	string	$raw	Raw value as written in the file (e.g. "'abc'").
	 *	@return	string	Unquoted scalar.
	 */
	private function unquoteScalar($raw)
	{
		$raw = trim((string) $raw);
		if (strlen($raw) >= 2) {
			$first = $raw[0];
			$last = $raw[strlen($raw) - 1];
			if (($first === '\'' && $last === '\'') || ($first === '"' && $last === '"')) {
				$inner = substr($raw, 1, -1);
				return str_replace(array('\\\'', '\\"', '\\\\'), array('\'', '"', '\\'), $inner);
			}
		}
		return $raw;
	}
}
