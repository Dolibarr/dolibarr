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
		'dolibarr_allow_download_external_modules' => 'dolibarr_allow_download_app', // renamed in Dolibarr 25.0
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
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_os_commands', 'default' => 'mariadb-dump, mariadb, mysqldump, mysql, pg_dump, pg_restore, clamdscan, clamdscan.exe, ls, tar, gzip, bzip2, zstd', 'type' => 'string', 'mode' => $s, 'comment' => 'Allowed OS commands for backup feature'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_eval_methods', 'default' => 'getDolGlobalString, getDolGlobalInt, getDolCurrency, getDolEntity, getDolDBType, fetchNoCompute, hasRight, isAdmin, isModEnabled, isStringVarMatching, dolSort, abs, min, max, round, dol_now, preg_match', 'type' => 'string', 'mode' => $s, 'comment' => 'Whitelist of functions allowed in computed fields'),
			array('section' => 'Security', 'key' => 'dolibarr_main_disabled_modules', 'default' => 'array()', 'type' => 'raw', 'mode' => $c, 'comment' => 'Modules forbidden to enable, e.g. array(\'dav\', \'api\')'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_ip', 'default' => '', 'type' => 'string', 'mode' => $s, 'comment' => 'Restrict backoffice access to a list of IP/CIDR'),
			array('section' => 'Security', 'key' => 'dolibarr_nocsrfcheck', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Disable CSRF protection (0, 1 or 2). Keep 0 in most cases'),
			array('section' => 'Security', 'key' => 'dolibarr_main_disable_totp', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Rescue option: set to 1 to disable two-factor authentication (TOTP) for all users. Remove once solved'),
			array('section' => 'Security', 'key' => 'dolibarr_main_csrf_with_token', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Force MAIN_SECURITY_CSRF_WITH_TOKEN (0,1,2,3) over the database value. See conf.php.example'),
			array('section' => 'Security', 'key' => 'dolibarr_main_disable_dns_get_record', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Force MAIN_DISABLE_DNS_GET_RECORD (0 or 1) over the database value. See conf.php.example'),
			array('section' => 'Security', 'key' => 'dolibarr_api_count_always_enabled', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 so API call count can not be disabled from admin'),
			array('section' => 'Security', 'key' => 'dolibarr_main_restrict_password_generation_none', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to forbid the \'none\' password generation model'),
			array('section' => 'Security', 'key' => 'dolibarr_cron_allow_cli', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Set to 1 to allow command lines in the internal Job scheduler'),
			array('section' => 'Security', 'key' => 'dolibarr_mailing_limit_sendbyweb', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Hard limit for mailing sent by web (-1 to forbid)'),
			array('section' => 'Security', 'key' => 'dolibarr_mailing_limit_sendbycli', 'default' => '0', 'type' => 'string', 'mode' => $s, 'comment' => 'Hard limit for mailing sent by cli (-1 to forbid)'),
			array('section' => 'Security', 'key' => 'dolibarr_mail_allow_unrestricted_smtp_target', 'default' => '', 'type' => 'string', 'mode' => $c, 'comment' => 'Accept any SMTP host and port, including private addresses. Keep unset unless required'),
			array('section' => 'Security', 'key' => 'dolibarr_main_stream_to_disable', 'default' => "array('compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zip', 'zlib')", 'type' => 'raw', 'mode' => $s, 'comment' => 'PHP streams to disable (secure default written explicitly)'),
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
	 *	The content is tokenized, never executed. Each top-level statement $dolibarr_xxx = ...; gives a value,
	 *	kept raw (right-hand side as written, possibly on several lines). Any other statement ($_SERVER[...] = ...,
	 *	include, define, if...) is returned verbatim, in statementsBefore when it comes before the first
	 *	$dolibarr_ assignment, in statementsAfter otherwise. Comments outside statements are not kept.
	 *
	 *	@param	string	$content	Raw content of the conf.php file.
	 *	@return	array{values:array<string,string>,unknown:string[],deprecated:string[],missing:string[],statementsBefore:string[],statementsAfter:string[]}
	 */
	public function parse($content)
	{
		$values = array();
		$unknown = array();
		$deprecated = array();
		$statementsBefore = array();
		$statementsAfter = array();

		$canvasKeys = $this->getCanvasKeys();

		foreach ($this->splitStatements((string) $content) as $statement) {
			$reg = array();
			if (!preg_match('/^\$(dolibarr_[a-z0-9_]+)\s*=(?!=)\s*(.*);$/is', $statement, $reg)) {
				if (empty($values)) {
					$statementsBefore[] = $statement;
				} else {
					$statementsAfter[] = $statement;
				}
				continue;
			}
			$key = $reg[1];
			$values[$key] = trim($reg[2]);

			if (array_key_exists($key, self::DEPRECATION_MAP)) {
				if (!in_array($key, $deprecated, true)) {
					$deprecated[] = $key;
				}
			} elseif (!in_array($key, $canvasKeys, true) && !in_array($key, $unknown, true)) {
				$unknown[] = $key;
			}
		}

		$missing = array();
		foreach ($canvasKeys as $canvasKey) {
			if (!array_key_exists($canvasKey, $values)) {
				$missing[] = $canvasKey;
			}
		}

		return array(
			'values' => $values,
			'unknown' => $unknown,
			'deprecated' => $deprecated,
			'missing' => $missing,
			'statementsBefore' => $statementsBefore,
			'statementsAfter' => $statementsAfter,
		);
	}

	/**
	 *	Split PHP content into its top-level statements, as written.
	 *
	 *	@param	string	$content	Raw PHP content.
	 *	@return	string[]	Statements, trimmed, without the comments found between statements.
	 */
	private function splitStatements($content)
	{
		$statements = array();
		$buffer = '';
		$depth = 0;
		foreach (token_get_all($content) as $token) {
			$id = is_array($token) ? $token[0] : null;
			$text = is_array($token) ? $token[1] : $token;
			if ($id === T_OPEN_TAG || $id === T_OPEN_TAG_WITH_ECHO || $id === T_CLOSE_TAG || $id === T_INLINE_HTML) {
				if (trim($buffer) !== '') {
					$statements[] = trim($buffer);
				}
				$buffer = '';
				$depth = 0;
				continue;
			}
			if ($buffer === '' && ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT)) {
				continue;
			}
			$buffer .= $text;
			if ($text === '(' || $text === '[' || $text === '{' || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
				$depth++;
			} elseif ($text === ')' || $text === ']' || $text === '}') {
				$depth--;
			}
			if ($depth <= 0 && ($text === ';' || $text === '}')) {
				$statements[] = trim($buffer);
				$buffer = '';
				$depth = 0;
			}
		}
		if (trim($buffer) !== '') {
			$statements[] = trim($buffer);
		}

		return $statements;
	}

	/**
	 *	Decode a right-hand side when it is a plain literal (quoted string, number, true, false or null).
	 *
	 *	@param	string	$raw	Right-hand side as written in the file.
	 *	@return	string|null		Decoded value, or null when the right-hand side is an expression (getenv(), concatenation...).
	 */
	private function decodeLiteral($raw)
	{
		$significant = array();
		foreach (token_get_all('<?php '.$raw.';') as $token) {
			if (is_array($token) && in_array($token[0], array(T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
				continue;
			}
			$significant[] = $token;
		}
		$sign = '';
		if (count($significant) === 3 && $significant[0] === '-') {
			$sign = '-';
			array_shift($significant);
		}
		if (count($significant) !== 2 || $significant[1] !== ';' || !is_array($significant[0])) {
			return null;
		}
		$id = $significant[0][0];
		$text = $significant[0][1];
		if ($id === T_LNUMBER || $id === T_DNUMBER) {
			return $sign.$text;
		}
		if ($sign !== '') {
			return null;
		}
		if ($id === T_CONSTANT_ENCAPSED_STRING) {
			$inner = substr($text, 1, -1);
			if ($text[0] === '\'') {
				return strtr($inner, array('\\\\' => '\\', '\\\'' => '\''));
			}
			return stripcslashes($inner);
		}
		if ($id === T_STRING) {
			$lower = strtolower($text);
			if ($lower === 'true') {
				return '1';
			}
			if ($lower === 'false' || $lower === 'null') {
				return '';
			}
		}
		return null;
	}

	/**
	 *	Read the value of a template variable.
	 *
	 *	@param	array<string,string>	$values				Parsed values (raw right-hand sides).
	 *	@param	array<string,mixed>		$evaluatedValues	Values of the same file once included, used for expressions.
	 *	@param	string					$key				Variable name.
	 *	@return	string	The value, or '' when absent or when it is an expression with no evaluated value.
	 */
	private function readTemplateValue($values, $evaluatedValues, $key)
	{
		if (!isset($values[$key])) {
			return '';
		}
		$decoded = $this->decodeLiteral($values[$key]);
		if ($decoded !== null) {
			return $decoded;
		}
		return (isset($evaluatedValues[$key]) && is_scalar($evaluatedValues[$key])) ? (string) $evaluatedValues[$key] : '';
	}

	/**
	 *	Resolve the instance unique id from parsed values.
	 *
	 *	An existing (non deprecated) instance_unique_id is kept. A deprecated legacy cookie_cryptkey is
	 *	NOT reused as the key: a fresh id is generated and the old line is flagged to be kept commented for
	 *	reference (so each provisioned instance gets its own unique id, while the old value stays recoverable).
	 *
	 *	@param	array<string,string>	$values		Parsed values (raw right-hand sides).
	 *	@return	array{instanceKey:string,oldKey:string,oldKeyRaw:string,commentOut:string[]}	New id, old effective key (decoded, '' for an expression), its right-hand side as written, and deprecated variables to keep commented.
	 */
	public function resolveInstanceKey($values)
	{
		// A fresh instance unique id is always generated at install, so each instance has its own id even
		// when the reused template already contains one.
		$newId = bin2hex(random_bytes(32));

		// Old effective key, kept so dolcrypt-encrypted data stays decryptable after the id is regenerated:
		// existing dolcrypt_key, else instance_unique_id, else the legacy cookie_cryptkey.
		$oldKey = '';
		$oldKeyRaw = '';
		foreach (array('dolibarr_main_dolcrypt_key', 'dolibarr_main_instance_unique_id', 'dolibarr_main_cookie_cryptkey') as $k) {
			if (!isset($values[$k])) {
				continue;
			}
			$decoded = $this->decodeLiteral($values[$k]);
			if ($decoded === null || $decoded !== '') {
				$oldKey = (string) $decoded;
				$oldKeyRaw = $values[$k];
				break;
			}
		}

		$commentOut = array();
		if (!empty($values['dolibarr_main_cookie_cryptkey'])) {
			$commentOut[] = 'dolibarr_main_cookie_cryptkey';
		}

		return array('instanceKey' => $newId, 'oldKey' => $oldKey, 'oldKeyRaw' => $oldKeyRaw, 'commentOut' => $commentOut);
	}

	/**
	 *	Read the connection parameters (paths + database) from an existing conf.php template.
	 *
	 *	The file is parsed (not included), so no code from the file is executed. The database password is
	 *	decrypted when stored encrypted (crypted: or dolcrypt:). Used when reusing a template: the parameters
	 *	then come from the template instead of the (disabled) install form.
	 *
	 *	@param	string				$rawTemplate		Raw content of the existing conf.php template.
	 *	@param	array<string,mixed>	$evaluatedValues	Values of the template once included (keyed by variable name), used when a value is an expression such as getenv().
	 *	@return	array<string,string>	Map: document_root, data_root, url_root, db_type, db_host, db_port, db_name, db_user, db_prefix, db_pass.
	 */
	public function getConnectionParams($rawTemplate, $evaluatedValues = array())
	{
		$parsed = $this->parse($rawTemplate);
		$values = $parsed['values'];

		$params = array();
		foreach (array('document_root', 'data_root', 'url_root', 'db_type', 'db_host', 'db_port', 'db_name', 'db_user', 'db_prefix') as $short) {
			$key = 'dolibarr_main_'.$short;
			$params[$short] = $this->readTemplateValue($values, $evaluatedValues, $key);
		}

		// dolcrypt key effectively used by the template: dolcrypt_key, else instance_unique_id, else the
		// legacy cookie_cryptkey (so a password from an old conf can still be decrypted for the connection).
		$rawpass = $this->readTemplateValue($values, $evaluatedValues, 'dolibarr_main_db_pass');
		$cryptkey = '';
		foreach (array('dolibarr_main_dolcrypt_key', 'dolibarr_main_instance_unique_id', 'dolibarr_main_cookie_cryptkey') as $k) {
			$cryptkey = $this->readTemplateValue($values, $evaluatedValues, $k);
			if ($cryptkey !== '') {
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
	 *	@param	array<string,bool>		$verbatimKeys	Variables whose value is a right-hand side written as is (keyed by variable name).
	 *	@return	string	Full conf.php content.
	 */
	public function build($values, $topBlock = '', $verbatimKeys = array())
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

			if (isset($verbatimKeys[$key])) {
				$assignment = '$'.$key.'='.$value.';';
			} else {
				$assignment = '$'.$key.'='.$this->serializeValue($descriptor, $value).';';
				if ($descriptor['mode'] === self::MODE_COMMENT_IF_EMPTY && $this->isEmptyValue($value)) {
					$assignment = '//'.$assignment;
				}
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
	 *	A fresh instance_unique_id is always generated; when dolcrypt_key is empty, the old key is pinned into it
	 *	so the values already encrypted with dolEncrypt() stay decryptable. Deprecated variables are kept commented for
	 *	reference, and custom/unknown variables are preserved verbatim, so nothing is silently lost.
	 *	Reused values are written back exactly as in the template, so an expression such as getenv() stays an
	 *	expression. The other statements of the template are written back as is, at the top of the file when they
	 *	came before the first $dolibarr_ assignment, at the end otherwise.
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

		// Reuse the values already set in the template for the known canvas variables.
		$values = array();
		$verbatimKeys = array();
		foreach ($this->getCanvas() as $descriptor) {
			$key = $descriptor['key'];
			if (array_key_exists($key, $parsed['values'])) {
				$raw = $parsed['values'][$key];
				$decoded = $this->decodeLiteral($raw);
				$isempty = ($decoded === null) ? $this->isEmptyValue((string) preg_replace('/\s+/', '', $raw)) : $this->isEmptyValue($decoded);
				// A template value present but empty is treated like a missing one (so mandatory variables fall back).
				if (!$isempty) {
					$values[$key] = $raw;
					$verbatimKeys[$key] = true;
					continue;
				}
			}
			if (isset($fallbackValues[$key]) && $fallbackValues[$key] !== '') {
				$values[$key] = $fallbackValues[$key];
			}
			// else: leave unset -> build() applies the canvas default
		}

		// A fresh instance_unique_id is always generated (see resolveInstanceKey).
		$keyres = $this->resolveInstanceKey($parsed['values']);
		$values['dolibarr_main_instance_unique_id'] = $keyres['instanceKey'];
		unset($verbatimKeys['dolibarr_main_instance_unique_id']);

		// Values already encrypted in the database with dolEncrypt() need the old key, whatever the format of db_pass.
		if (empty($values['dolibarr_main_dolcrypt_key']) && $keyres['oldKeyRaw'] !== '') {
			$values['dolibarr_main_dolcrypt_key'] = $keyres['oldKeyRaw'];
			$verbatimKeys['dolibarr_main_dolcrypt_key'] = true;
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
				$deprecatedBlock .= '//'.str_replace("\n", "\n//", '$'.$key.'='.$parsed['values'][$key].';')."\n";
			}
		}

		$topBlock = $deprecatedBlock;
		if (!empty($parsed['statementsBefore'])) {
			$topBlock .= ($topBlock !== '' ? "\n" : '').$separator."\n";
			$topBlock .= '//  STATEMENTS KEPT FROM THE EXISTING conf.php'."\n";
			$topBlock .= $separator."\n\n";
			$topBlock .= implode("\n", $parsed['statementsBefore'])."\n";
		}

		$content = $this->build($values, $topBlock, $verbatimKeys);

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

		if (!empty($parsed['statementsAfter'])) {
			$content .= $separator."\n";
			$content .= '//  STATEMENTS KEPT FROM THE EXISTING conf.php'."\n";
			$content .= $separator."\n\n";
			$content .= implode("\n", $parsed['statementsAfter'])."\n\n";
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
