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
	 *	Single source of truth for build(). Keyed by variable name without the leading '$'. Each descriptor holds:
	 *	 - section: section label used to group variables,
	 *	 - default: default value,
	 *	 - type:    'string' (quoted), 'path' (double quoted, backslashes kept), 'int' (raw integer) or 'raw' (literal PHP, e.g. array()),
	 *	 - mode:    self::MODE_VALUE or self::MODE_COMMENT_IF_EMPTY,
	 *	 - comment: short explanation appended as a reminder,
	 *	 - compact: (optional) true to render a short block without the per-variable comment and default lines,
	 *	 - subsection: (optional) one-line header printed once before a compact block (e.g. a pointer to conf.php.example).
	 *
	 *	@return	array<string,array{section:string,default:string,type:string,mode:string,comment:string,compact?:bool,subsection?:string}>
	 */
	public function getCanvas()
	{
		$modeValue = self::MODE_VALUE;
		$modeCommentIfEmpty = self::MODE_COMMENT_IF_EMPTY;

		return [
			// --- Main parameters ---
			'dolibarr_main_url_root' => ['section' => 'Main parameters', 'default' => '', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Root URL of the index.php page, without ending slash'],
			'dolibarr_main_document_root' => ['section' => 'Main parameters', 'default' => '', 'type' => 'path', 'mode' => $modeValue, 'comment' => 'Absolute path of the htdocs directory'],
			'dolibarr_main_url_root_alt' => ['section' => 'Main parameters', 'default' => '/custom', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Relative sub URL for external modules'],
			'dolibarr_main_document_root_alt' => ['section' => 'Main parameters', 'default' => '', 'type' => 'path', 'mode' => $modeValue, 'comment' => 'Absolute path for external modules'],
			'dolibarr_main_data_root' => ['section' => 'Main parameters', 'default' => '', 'type' => 'path', 'mode' => $modeValue, 'comment' => 'Absolute path of the documents directory'],
			'dolibarr_main_db_host' => ['section' => 'Main parameters', 'default' => 'localhost', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database server host name or ip address'],
			'dolibarr_main_db_port' => ['section' => 'Main parameters', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database server port. Default 0, see conf.php.example for more details'],
			'dolibarr_main_db_name' => ['section' => 'Main parameters', 'default' => 'dolibarr', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database name'],
			'dolibarr_main_db_user' => ['section' => 'Main parameters', 'default' => '', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database user'],
			'dolibarr_main_db_pass' => ['section' => 'Main parameters', 'default' => '', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database password (may be prefixed by crypted: or dolcrypt:)'],
			'dolibarr_main_db_type' => ['section' => 'Main parameters', 'default' => 'mysqli', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database driver: mysqli or pgsql'],
			'dolibarr_main_db_character_set' => ['section' => 'Main parameters', 'default' => 'utf8', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database character set'],
			'dolibarr_main_db_collation' => ['section' => 'Main parameters', 'default' => 'utf8_unicode_ci', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database collation'],
			'dolibarr_main_db_encryption' => ['section' => 'Main parameters', 'default' => '', 'type' => 'int', 'mode' => $modeCommentIfEmpty, 'comment' => 'Deprecated reversible AES encryption of llx_const values (0 = off). Do not use'],
			'dolibarr_main_db_readonly' => ['section' => 'Main parameters', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Set to 1 to run the application in readonly mode'],
			'dolibarr_main_dolcrypt_key' => ['section' => 'Main parameters', 'default' => '', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Secret key for dolEncrypt/dolDecrypt. When empty, instance_unique_id is used'],
			'dolibarr_main_instance_unique_id' => ['section' => 'Main parameters', 'default' => '', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Unique id of the installation (also used as encryption salt)'],
			'dolibarr_main_db_prefix' => ['section' => 'Main parameters', 'default' => 'llx_', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Database tables prefix'],

			// --- Login ---
			'dolibarr_main_authentication' => ['section' => 'Login', 'default' => 'dolibarr', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Authentication mode (dolibarr, http, ldap, openid_connect, ...)'],
			'dolibarr_main_authentication_autocreateuser' => ['section' => 'Login', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Set to 1 to auto-create users on first OIDC login'],
			'dolibarr_auto_user' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Login used when authentication mode is forceuser'],
			'dolibarr_main_auth_ldap_host' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'subsection' => 'LDAP login (only when dolibarr_main_authentication contains \'ldap\'). See conf.php.example for parameters and examples.', 'comment' => 'LDAP server(s), e.g. 127.0.0.1'],
			'dolibarr_main_auth_ldap_port' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP port, e.g. 389'],
			'dolibarr_main_auth_ldap_version' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP protocol version, e.g. 3'],
			'dolibarr_main_auth_ldap_servertype' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'openldap, activedirectory or egroupware'],
			'dolibarr_main_auth_ldap_login_attribute' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP login attribute (uid, samaccountname, ...)'],
			'dolibarr_main_auth_ldap_dn' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP DN'],
			'dolibarr_main_auth_ldap_filter' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP search filter'],
			'dolibarr_main_auth_ldap_admin_login' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP admin login (if anonymous bind disabled)'],
			'dolibarr_main_auth_ldap_admin_pass' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP admin password (if anonymous bind disabled)'],
			'dolibarr_main_auth_ldap_debug' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'compact' => true, 'comment' => 'LDAP debug flag (true/false)'],
			'dolibarr_main_demo' => ['section' => 'Login', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Login and pass for demo mode, e.g. autologin,autopass'],

			// --- Security ---
			'dolibarr_main_force_https' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Force HTTPS mode (0, 1, 2 or an https URL)'],
			'dolibarr_main_prod' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Set to 1 on production to hide error messages'],
			// Written as a raw literal (never user input here) so the secure default keeps its double quote, which dol_escape_php() would otherwise convert.
			'dolibarr_login_badcharunauthorized' => ['section' => 'Security', 'default' => "',@<>\"\\''", 'type' => 'raw', 'mode' => $modeValue, 'comment' => 'Forbidden characters in logins (secure default written explicitly)'],
			'dolibarr_main_restrict_os_commands' => ['section' => 'Security', 'default' => 'mariadb-dump, mariadb, mysqldump, mysql, pg_dump, pg_restore, clamdscan, clamdscan.exe, ls, tar, gzip, bzip2, zstd', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Allowed OS commands for backup feature'],
			'dolibarr_main_restrict_eval_methods' => ['section' => 'Security', 'default' => 'getDolGlobalString, getDolGlobalInt, getDolCurrency, getDolEntity, getDolDBType, fetchNoCompute, hasRight, isAdmin, isModEnabled, isStringVarMatching, dolSort, abs, min, max, round, dol_now, preg_match', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Whitelist of functions allowed in computed fields'],
			'dolibarr_main_disabled_modules' => ['section' => 'Security', 'default' => 'array()', 'type' => 'raw', 'mode' => $modeCommentIfEmpty, 'comment' => 'Modules forbidden to enable, e.g. array(\'dav\', \'api\')'],
			'dolibarr_main_restrict_ip' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Restrict backoffice access to a list of IP/CIDR'],
			'dolibarr_nocsrfcheck' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Disable CSRF protection (0, 1 or 2). Keep 0 in most cases'],
			'dolibarr_main_disable_totp' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Rescue option: set to 1 to disable two-factor authentication (TOTP) for all users. Remove once solved'],
			'dolibarr_api_count_always_enabled' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Set to 1 so API call count can not be disabled from admin'],
			'dolibarr_main_restrict_password_generation_none' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Set to 1 to forbid the \'none\' password generation model'],
			'dolibarr_cron_allow_cli' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Set to 1 to allow command lines in the internal Job scheduler'],
			'dolibarr_mailing_limit_sendbyweb' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Hard limit for mailing sent by web (-1 to forbid)'],
			'dolibarr_mailing_limit_sendbycli' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Hard limit for mailing sent by cli (-1 to forbid)'],
			'dolibarr_mail_allow_unrestricted_smtp_target' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Accept any SMTP host and port, including private addresses. Keep unset unless required'],
			'dolibarr_main_stream_to_disable' => ['section' => 'Security', 'default' => "array('compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zlib')", 'type' => 'raw', 'mode' => $modeValue, 'comment' => 'PHP streams to disable (secure default written explicitly)'],
			'dolibarr_website_allow_custom_php' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Allow PHP dynamic content into a website (0, 1, 2 or 3)'],
			'dolibarr_allow_localurl_for_webhooks' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Allow webhooks to use a local URL'],
			'dolibarr_ai_allow_local_endpoints' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'AI endpoints allowed: 0 external only, 1 local only, 2 both'],
			'dolibarr_allow_unsecured_select_in_extrafields_filter' => ['section' => 'Security', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Allow subrequests inside USF IN filters'],
			'dolibarr_session_db_type' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Internal database session handler (experimental, e.g. db)'],
			'dolibarr_session_db_host' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Session database host'],
			'dolibarr_session_db_name' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Session database name'],
			'dolibarr_session_db_user' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Session database user'],
			'dolibarr_session_db_pass' => ['section' => 'Security', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Session database password'],

			// --- Other ---
			'dolibarr_main_limit_users' => ['section' => 'Other', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Maximum number of users that can be created (0 = unlimited)'],
			'dolibarr_main_limit_sessions_per_user' => ['section' => 'Other', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Maximum concurrent sessions per user, database session handler only (0 = unlimited)'],
			'dolibarr_strict_mode' => ['section' => 'Other', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Enable PHP strict mode (dev only)'],
			'dolibarr_allow_download_app' => ['section' => 'Other', 'default' => '0', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Allow downloading the zip of external modules and of the application files from admin'],
			'dolibarr_main_distrib' => ['section' => 'Other', 'default' => 'standard', 'type' => 'string', 'mode' => $modeValue, 'comment' => 'Distribution name'],

			// --- Paths ---
			'dolibarr_lib_FPDF_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the FPDF library'],
			'dolibarr_lib_TCPDF_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the TCPDF library'],
			'dolibarr_lib_FPDI_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the FPDI library'],
			'dolibarr_lib_TCPDI_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the TCPDI library'],
			'dolibarr_lib_GEOIP_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the GeoIP library'],
			'dolibarr_lib_NUSOAP_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the NuSOAP library'],
			'dolibarr_lib_ODTPHP_PATH' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the ODTPHP library'],
			'dolibarr_lib_ODTPHP_PATHTOPCLZIP' => ['section' => 'Paths', 'default' => '', 'type' => 'path', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the PCLZIP library'],
			'dolibarr_js_CKEDITOR' => ['section' => 'Paths', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the shared CKEditor'],
			'dolibarr_js_JQUERY' => ['section' => 'Paths', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the shared jQuery'],
			'dolibarr_js_JQUERY_UI' => ['section' => 'Paths', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the shared jQuery UI'],
			'dolibarr_font_DOL_DEFAULT_TTF' => ['section' => 'Paths', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the default TTF font'],
			'dolibarr_font_DOL_DEFAULT_TTF_BOLD' => ['section' => 'Paths', 'default' => '', 'type' => 'string', 'mode' => $modeCommentIfEmpty, 'comment' => 'Override path to the default bold TTF font'],
		];
	}

	/**
	 *	Return the list of variable names known by the canvas.
	 *
	 *	@return	string[]	List of variable names (without the leading '$').
	 */
	public function getCanvasKeys()
	{
		return array_keys($this->getCanvas());
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
			$values = [];
		}

		$separator = '// '.str_repeat('=', 61);

		$out = '<?php'."\n";
		$out .= '//'."\n";
		$out .= '// File generated by Dolibarr installer '.(defined('DOL_VERSION') ? DOL_VERSION : '').' on '.dol_print_date(dol_now(), '')."\n";
		$out .= '//'."\n";
		$out .= '// Take a look at conf.php.example file for an example of conf.php file'."\n";
		$out .= '// and explanations for all possibles parameters.'."\n";

		$lastSection = '';
		foreach ($this->getCanvas() as $key => $descriptor) {
			if ($descriptor['section'] !== $lastSection) {
				$out .= "\n".$separator."\n";
				$out .= '//  '.strtoupper($descriptor['section'])."\n";
				$out .= $separator."\n";
				$lastSection = $descriptor['section'];
			}

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
