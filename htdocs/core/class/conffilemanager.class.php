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
 *	\brief      Class to build the conf.php configuration file.
 */

/**
 *	Build the content of the Dolibarr conf.php file from a canvas listing every variable of conf.php.example.
 *
 *	Pure string manipulation (no HTTP, no output, no database access).
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
	 *	Return the ordered canvas describing every conf.php variable.
	 *
	 *	Single source of truth for build(). Each descriptor holds:
	 *	 - section: section label used to group variables,
	 *	 - key:     variable name without the leading '$',
	 *	 - default: default value,
	 *	 - type:    'string' (quoted), 'path' (double quoted, backslashes kept), 'int' (raw integer) or 'raw' (literal PHP, e.g. array()),
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
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_document_root', 'default' => '', 'type' => 'path', 'mode' => $s, 'comment' => 'Absolute path of the htdocs directory'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_url_root_alt', 'default' => '/custom', 'type' => 'string', 'mode' => $s, 'comment' => 'Relative sub URL for external modules'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_document_root_alt', 'default' => '', 'type' => 'path', 'mode' => $s, 'comment' => 'Absolute path for external modules'),
			array('section' => 'Main parameters', 'key' => 'dolibarr_main_data_root', 'default' => '', 'type' => 'path', 'mode' => $s, 'comment' => 'Absolute path of the documents directory'),
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
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_os_commands', 'default' => 'mariadb-dump, mariadb, mysqldump, mysql, pg_dump, pg_restore, clamdscan, clamdscan.exe, ls, tar, gzip, bzip2, zstd', 'type' => 'string', 'mode' => $s, 'comment' => 'Allowed OS commands for backup feature'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_eval_methods', 'default' => 'getDolGlobalString, getDolGlobalInt, getDolCurrency, getDolEntity, getDolDBType, fetchNoCompute, hasRight, isAdmin, isModEnabled, isStringVarMatching, dolSort, abs, min, max, round, dol_now, preg_match', 'type' => 'string', 'mode' => $s, 'comment' => 'Whitelist of functions allowed in computed fields'),
			array('section' => 'Security', 'key' => 'dolibarr_main_disabled_modules', 'default' => 'array()', 'type' => 'raw', 'mode' => $c, 'comment' => 'Modules forbidden to enable, e.g. array(\'dav\', \'api\')'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_ip', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Restrict backoffice access to a list of IP/CIDR'),
			array('section' => 'Security', 'key' => 'dolibarr_nocsrfcheck', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Disable CSRF protection (0, 1 or 2). Keep 0 in most cases'),
			array('section' => 'Security', 'key' => 'dolibarr_main_disable_totp', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Rescue option: set to 1 to disable two-factor authentication (TOTP) for all users. Remove once solved'),
			array('section' => 'Security', 'key' => 'dolibarr_api_count_always_enabled', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 so API call count can not be disabled from admin'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_password_generation_none', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to forbid the \'none\' password generation model'),
			array('section' => 'Security', 'key' => 'dolibarr_cron_allow_cli', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to allow command lines in the internal Job scheduler'),
			array('section' => 'Security', 'key' => 'dolibarr_mailing_limit_sendbyweb', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Hard limit for mailing sent by web (-1 to forbid)'),
			array('section' => 'Security', 'key' => 'dolibarr_mailing_limit_sendbycli', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Hard limit for mailing sent by cli (-1 to forbid)'),
			array('section' => 'Security', 'key' => 'dolibarr_mail_allow_unrestricted_smtp_target', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Accept any SMTP host and port, including private addresses. Keep unset unless required'),
			array('section' => 'Security', 'key' => 'dolibarr_main_stream_to_disable', 'default' => "array('compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zlib')", 'type' => 'raw', 'mode' => $s, 'comment' => 'PHP streams to disable (secure default written explicitly)'),
			array('section' => 'Security', 'key' => 'dolibarr_website_allow_custom_php', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow PHP dynamic content into a website (0, 1, 2 or 3)'),
			array('section' => 'Security', 'key' => 'dolibarr_allow_localurl_for_webhooks', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow webhooks to use a local URL'),
			array('section' => 'Security', 'key' => 'dolibarr_ai_allow_local_endpoints', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'AI endpoints allowed: 0 external only, 1 local only, 2 both'),
			array('section' => 'Security', 'key' => 'dolibarr_allow_unsecured_select_in_extrafields_filter', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow subrequests inside USF IN filters'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_type', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Internal database session handler (experimental, e.g. db)'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_host', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database host'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_name', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database name'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_user', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database user'),
			array('section' => 'Security', 'key' => 'dolibarr_session_db_pass', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Session database password'),

			// --- Other ---
			array('section' => 'Other', 'key' => 'dolibarr_main_limit_users', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Maximum number of users that can be created (0 = unlimited)'),
			array('section' => 'Other', 'key' => 'dolibarr_main_limit_sessions_per_user', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Maximum concurrent sessions per user, database session handler only (0 = unlimited)'),
			array('section' => 'Other', 'key' => 'dolibarr_strict_mode', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Enable PHP strict mode (dev only)'),
			array('section' => 'Other', 'key' => 'dolibarr_allow_download_app', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Allow downloading the zip of external modules and of the application files from admin'),
			array('section' => 'Other', 'key' => 'dolibarr_main_distrib', 'default' => 'standard', 'type' => 'string', 'mode' => $s, 'comment' => 'Distribution name'),

			// --- Paths ---
			array('section' => 'Paths', 'key' => 'dolibarr_lib_FPDF_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the FPDF library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_TCPDF_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the TCPDF library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_FPDI_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the FPDI library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_TCPDI_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the TCPDI library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_GEOIP_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the GeoIP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_NUSOAP_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the NuSOAP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_ODTPHP_PATH', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the ODTPHP library'),
			array('section' => 'Paths', 'key' => 'dolibarr_lib_ODTPHP_PATHTOPCLZIP', 'default' => '', 'type' => 'path', 'mode' => $c, 'comment' => 'Override path to the PCLZIP library'),
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
	 *	Build an exhaustive, sectioned and commented conf.php content.
	 *
	 *	@param	array<string,string>	$values		Concrete values keyed by variable name. Missing keys fall back to the canvas default.
	 *	@return	string	Full conf.php content.
	 */
	public function build($values)
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
			// TOKEN_PARSE makes token_get_all() throw a ParseError on invalid PHP; a valid conf.php yields tokens.
			$tokens = token_get_all((string) $content, TOKEN_PARSE);
		} catch (\ParseError $e) {
			return false;
		}
		return count($tokens) > 0;
	}

	/**
	 *	Serialize a value according to its canvas descriptor type.
	 *
	 *	@param	array{type:string,default:string}	$descriptor		Canvas descriptor.
	 *	@param	string								$value			Value to serialize.
	 *	@return	string	PHP literal representation.
	 */
	private function serializeValue($descriptor, $value)
	{
		if ($descriptor['type'] === 'int') {
			return (string) ((int) $value);
		}
		if ($descriptor['type'] === 'path') {
			// Mode 2 keeps the backslashes of a Windows path; the caller must have removed '$' with dol_sanitizePathName().
			return '"'.dol_escape_php((string) $value).'"';
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
}
