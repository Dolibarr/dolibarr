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
	 *	@return	array{content:string,actions:string[]}		New content and the ACTION_* codes of what was done.
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

		try {
			token_get_all($content, TOKEN_PARSE);
		} catch (ParseError $e) {
			throw new Exception('The updated conf.php is not valid PHP: '.$e->getMessage());
		}

		return ['content' => $content, 'actions' => $actions];
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
