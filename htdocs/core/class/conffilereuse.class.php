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
 *	\file       htdocs/core/class/conffilereuse.class.php
 *	\ingroup    core
 *	\brief      Rules applied by the installer when it reuses a conf.php already in place.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/conffileeditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/securitycore.lib.php';

/**
 *	Update a conf.php already in place so it can be used by a new instance.
 *
 *	Data encrypted in database by dolEncrypt() use $dolibarr_main_instance_unique_id as key, while the database
 *	password uses $dolibarr_main_dolcrypt_key when it is set (see filefunc.inc.php). $dolibarr_main_dolcrypt_key is
 *	never written here.
 */
class ConfFileReuse
{
	const ACTION_INSTANCE_ID_REGENERATED = 'InstanceIdRegenerated';
	const ACTION_INSTANCE_ID_KEPT_DATABASE = 'InstanceIdKeptExistingDatabase';
	const ACTION_INSTANCE_ID_KEPT_EXPRESSION = 'InstanceIdKeptExpression';
	const ACTION_DB_PASS_REENCRYPTED = 'DbPassReencrypted';
	const ACTION_DB_PASS_ENCRYPTED = 'DbPassEncrypted';
	const ACTION_DB_PASS_EXPRESSION = 'DbPassExpressionUntouched';

	/**
	 * @var array<string,string> Variables no longer read by Dolibarr, mapped to the variable that replaces them ('' when removed).
	 *                           Only variables that are read nowhere else belong here, except cookie_cryptkey: it is only read as
	 *                           a fallback when instance_unique_id is empty.
	 */
	const DEPRECATION_MAP = [
		'dolibarr_main_cookie_cryptkey' => 'dolibarr_main_instance_unique_id',
		'dolibarr_lib_FPDFI_PATH' => 'dolibarr_lib_FPDI_PATH',
		'dolibarr_allow_download_external_modules' => 'dolibarr_allow_download_app',
		'dolibarr_lib_ADODB_PATH' => '',
		'dolibarr_lib_PHPEXCEL_PATH' => '',
		'dolibarr_js_JQUERY_FLOT' => '',
		'dolibarr_smarty_cache' => '',
		'dolibarr_smarty_compile' => '',
		'dolibarr_smarty_libs_dir' => '',
		'dolibarr_pdf_force_fpdf' => '',
		'dolibarr_allow_overwritekernel' => '',
	];

	/**
	 * @var ConfFileEditor
	 */
	private $editor;

	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->editor = new ConfFileEditor();
	}

	/**
	 *	Apply the reuse rules to a conf.php content.
	 *
	 *	@param	string					$content				Raw content of the conf.php in place.
	 *	@param	array<string,string>	$evaluated				Values of that conf.php once included, keyed by variable name
	 *															(dolibarr_main_instance_unique_id, dolibarr_main_cookie_cryptkey,
	 *															dolibarr_main_dolcrypt_key), to resolve expressions.
	 *	@param	bool					$databaseHasTables		True when the target database already contains Dolibarr tables.
	 *	@param	bool					$encryptClearPassword	True to encrypt a database password written in clear.
	 *	@return	array{content:string,actions:string[],deprecated:array<string,string>}
	 *			New content, the ACTION_* codes of what was done, and the deprecated variables commented out
	 *			(mapped to the variable that received their value, '' when none).
	 *	@throws	Exception									When the database password can not be decrypted or encrypted,
	 *														or when the result is not valid PHP.
	 */
	public function apply(string $content, array $evaluated, bool $databaseHasTables, bool $encryptClearPassword): array
	{
		$parsed = $this->editor->parse($content);
		$actions = [];

		$currentInstanceId = $this->firstNotEmpty($evaluated, ['dolibarr_main_instance_unique_id', 'dolibarr_main_cookie_cryptkey']);
		$dolcryptKey = $evaluated['dolibarr_main_dolcrypt_key'] ?? '';

		$newInstanceId = $this->resolveInstanceId($parsed, $databaseHasTables, $actions);
		if ($newInstanceId !== null) {
			$content = $this->editor->setValue($content, 'dolibarr_main_instance_unique_id', $newInstanceId);
		}

		$oldPasswordKey = $dolcryptKey !== '' ? $dolcryptKey : $currentInstanceId;
		$newPasswordKey = $dolcryptKey !== '' ? $dolcryptKey : ($newInstanceId ?? $currentInstanceId);

		if (isset($parsed['dolibarr_main_db_pass'])) {
			$password = $parsed['dolibarr_main_db_pass']['literal'];
			if ($password === null) {
				$actions[] = self::ACTION_DB_PASS_EXPRESSION;
			} else {
				$newPassword = $this->reencryptDbPass($password, $oldPasswordKey, $newPasswordKey, $encryptClearPassword);
				if ($newPassword !== null) {
					$content = $this->editor->setValue($content, 'dolibarr_main_db_pass', $newPassword);
					$actions[] = (strpos($password, 'dolcrypt:') === 0) ? self::ACTION_DB_PASS_REENCRYPTED : self::ACTION_DB_PASS_ENCRYPTED;
				}
			}
		}

		$deprecated = [];
		$content = $this->handleDeprecated($content, $deprecated);

		try {
			token_get_all($content, TOKEN_PARSE);
		} catch (ParseError $e) {
			throw new Exception('The updated conf.php is not valid PHP: '.$e->getMessage());
		}

		return ['content' => $content, 'actions' => $actions, 'deprecated' => $deprecated];
	}

	/**
	 *	Analyse a conf.php in place before its reuse, to warn the administrator.
	 *
	 *	@param	string					$content	Raw content of the conf.php in place.
	 *	@param	array<string,string>	$evaluated	Values of that conf.php once included, keyed by variable name.
	 *	@return	array{deprecated:string[],unknown:string[],missing:string[],invalid:array<string,string>}
	 *			Deprecated, unknown (not documented in conf.php.example) and missing variables, and the invalid values
	 *			(variable name => translation key of the problem).
	 */
	public function analyse(string $content, array $evaluated): array
	{
		$assigned = array_keys($this->editor->parse($content));
		$known = $this->getDocumentedKeys();

		$deprecated = array_values(array_intersect($assigned, array_keys(self::DEPRECATION_MAP)));
		$unknown = array_values(array_diff($assigned, $known, $deprecated));
		$missing = array_values(array_diff($known, $assigned));

		return ['deprecated' => $deprecated, 'unknown' => $unknown, 'missing' => $missing, 'invalid' => $this->checkValues($evaluated)];
	}

	/**
	 *	Check the values the installer will use, as evaluated from conf.php.
	 *
	 *	@param	array<string,string>	$values		Values keyed by variable name.
	 *	@return	array<string,string>				Invalid values: variable name => translation key of the problem.
	 */
	public function checkValues(array $values): array
	{
		$invalid = [];

		$documentRoot = (string) ($values['dolibarr_main_document_root'] ?? '');
		if ($documentRoot === '' || !is_file($documentRoot.'/main.inc.php')) {
			$invalid['dolibarr_main_document_root'] = 'ConfReuseInvalidDocumentRoot';
		}

		$dataRoot = (string) ($values['dolibarr_main_data_root'] ?? '');
		if ($dataRoot === '') {
			$invalid['dolibarr_main_data_root'] = 'ConfReuseInvalidDataRoot';
		} elseif (is_dir($dataRoot) ? !is_writable($dataRoot) : !is_writable(dirname($dataRoot))) {
			$invalid['dolibarr_main_data_root'] = 'ConfReuseInvalidDataRootNotWritable';
		}

		$dbType = (string) ($values['dolibarr_main_db_type'] ?? '');
		if (!preg_match('/^\w+$/', $dbType) || !is_file(DOL_DOCUMENT_ROOT.'/core/db/'.$dbType.'.class.php')) {
			$invalid['dolibarr_main_db_type'] = 'ConfReuseInvalidDbType';
		}

		if (!preg_match('#^https?://#i', (string) ($values['dolibarr_main_url_root'] ?? ''))) {
			$invalid['dolibarr_main_url_root'] = 'ConfReuseInvalidUrlRoot';
		}

		return $invalid;
	}

	/**
	 *	Comment out the deprecated variables, after copying their value into the variable that replaces them when it is not set.
	 *
	 *	cookie_cryptkey is kept when instance_unique_id is an expression, since it may still be used as its fallback.
	 *
	 *	@param	string					$content	Raw content of the conf.php.
	 *	@param	array<string,string>	$handled	Deprecated variables commented out, completed by this method.
	 *	@return	string								New content.
	 */
	private function handleDeprecated(string $content, array &$handled): string
	{
		foreach (self::DEPRECATION_MAP as $old => $new) {
			$parsed = $this->editor->parse($content);
			if (!isset($parsed[$old])) {
				continue;
			}
			if ($new === 'dolibarr_main_instance_unique_id' && isset($parsed[$new]) && $parsed[$new]['literal'] === null) {
				continue;
			}
			$copied = '';
			if ($new !== '' && !isset($parsed[$new]) && $parsed[$old]['literal'] !== null) {
				$content = $this->editor->setValue($content, $new, $parsed[$old]['literal']);
				$copied = $new;
			}
			$note = 'Deprecated, no longer used'.($new !== '' ? ', replaced by $'.$new : '');
			$content = $this->editor->commentOut($content, $old, $note);
			$handled[$old] = $copied;
		}
		return $content;
	}

	/**
	 *	Return the variables documented in conf.php.example (active or commented examples).
	 *
	 *	@return	string[]
	 */
	private function getDocumentedKeys(): array
	{
		$example = @file_get_contents(DOL_DOCUMENT_ROOT.'/conf/conf.php.example');
		if ($example === false || !preg_match_all('/^\s*(?:\/\/\s*)?\$(dolibarr_\w+)\s*=/m', $example, $matches)) {
			return [];
		}
		return array_values(array_unique($matches[1]));
	}

	/**
	 *	Return the new instance unique id to write, or null when the current one must be kept.
	 *
	 *	@param	array<string,array{literal:?string}>	$parsed				Assignments returned by ConfFileEditor::parse().
	 *	@param	bool									$databaseHasTables	True when the database already contains Dolibarr tables.
	 *	@param	string[]								$actions			ACTION_* codes, completed by this method.
	 *	@return	?string
	 */
	public function resolveInstanceId(array $parsed, bool $databaseHasTables, array &$actions): ?string
	{
		if (isset($parsed['dolibarr_main_instance_unique_id']) && $parsed['dolibarr_main_instance_unique_id']['literal'] === null) {
			$actions[] = self::ACTION_INSTANCE_ID_KEPT_EXPRESSION;
			return null;
		}
		if ($databaseHasTables) {
			$actions[] = self::ACTION_INSTANCE_ID_KEPT_DATABASE;
			return null;
		}
		$actions[] = self::ACTION_INSTANCE_ID_REGENERATED;
		return bin2hex(random_bytes(32));
	}

	/**
	 *	Return the database password to write once the key changes, or null when it must stay as written.
	 *
	 *	@param	string	$password		Database password as written in conf.php (clear, crypted: or dolcrypt:).
	 *	@param	string	$oldKey			Key that decrypts the current dolcrypt: value.
	 *	@param	string	$newKey			Key that will decrypt the password once the file is updated.
	 *	@param	bool	$encryptClear	True to encrypt a password written in clear.
	 *	@return	?string
	 *	@throws	Exception				When the password can not be decrypted with $oldKey or encrypted with $newKey.
	 */
	public function reencryptDbPass(string $password, string $oldKey, string $newKey, bool $encryptClear): ?string
	{
		if ($password === '' || strpos($password, 'crypted:') === 0) {
			return null;
		}

		if (strpos($password, 'dolcrypt:') === 0) {
			if ($oldKey === $newKey) {
				return null;
			}
			$clear = dolDecrypt($password, $oldKey);
			if ($clear === $password) {
				throw new Exception('The database password can not be decrypted with the key of the conf.php in place.');
			}
			return $this->encrypt($clear, $newKey);
		}

		return $encryptClear ? $this->encrypt($password, $newKey) : null;
	}

	/**
	 *	Encrypt a value with dolEncrypt().
	 *
	 *	@param	string	$clear	Value to encrypt.
	 *	@param	string	$key	Encryption key.
	 *	@return	string
	 *	@throws	Exception		When encryption is not available (no key or no openssl).
	 */
	private function encrypt(string $clear, string $key): string
	{
		$encrypted = dolEncrypt($clear, $key);
		if (strpos($encrypted, 'dolcrypt:') !== 0) {
			throw new Exception('The database password can not be encrypted (missing key or openssl).');
		}
		return $encrypted;
	}

	/**
	 *	Return the first non empty value among some keys.
	 *
	 *	@param	array<string,string>	$values		Values keyed by variable name.
	 *	@param	string[]				$keys		Keys to try, in order.
	 *	@return	string
	 */
	private function firstNotEmpty(array $values, array $keys): string
	{
		foreach ($keys as $key) {
			if (!empty($values[$key])) {
				return (string) $values[$key];
			}
		}
		return '';
	}
}
