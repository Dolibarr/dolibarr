<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    htdocs/modulebuilder/class/RightsConfig.class.php
 * \ingroup modulebuilder
 * \brief   Permissions ModuleBuilder generates for an object, and the permission each operation checks.
 */

require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/RightsGenerationMode.class.php';

/**
 * Permissions ModuleBuilder generates for an object, and the permission each operation checks.
 *
 * The permissions generated are exactly the codes the operations are mapped to, so a generated
 * page never checks a permission that is not declared.
 */
final class RightsConfig
{
	/** Operations checked by the generated pages; they are also the only codes a permission can have. */
	const OPERATIONS = array('read', 'write', 'delete');

	/** Second level of the permission, as in hasRight('mymodule', '<key>', 'read'). */
	const KEY_PATTERN = '/^[a-z][a-z0-9_]{0,63}\z/';

	/** A key containing a template placeholder would be rewritten by the name substitution of later generations. */
	const FORBIDDEN_KEY_PARTS = array('myobject', 'mymodule');

	/** @var string One of RightsGenerationMode constants */
	private $mode;

	/** @var string Permission key replacing the object key, '' to keep the object key */
	private $rightsKeyOverride;

	/** @var array<string,string> Operation => permission code, empty in mode NONE */
	private $map;

	/**
	 * @param string              $mode              One of RightsGenerationMode constants
	 * @param string              $rightsKeyOverride Permission key, '' to keep the object key
	 * @param array<mixed,mixed>  $map               Operation => permission code
	 * @throws \InvalidArgumentException When the settings are inconsistent with the mode
	 */
	private function __construct(string $mode, string $rightsKeyOverride, array $map)
	{
		if (!RightsGenerationMode::isValid($mode)) {
			throw new \InvalidArgumentException('Unknown rights generation mode "'.$mode.'"');
		}

		if ($mode === RightsGenerationMode::NONE) {
			if ($rightsKeyOverride !== '' || !empty($map)) {
				throw new \InvalidArgumentException('Mode none declares no permission key and no operation map');
			}
		} else {
			self::checkKey($rightsKeyOverride);
			$map = self::normalizeMap($map);
			if ($mode === RightsGenerationMode::AUTO && ($rightsKeyOverride !== '' || $map !== array_combine(self::OPERATIONS, self::OPERATIONS))) {
				throw new \InvalidArgumentException('Mode auto checks each operation with its own permission under the object key');
			}
		}

		$this->mode = $mode;
		$this->rightsKeyOverride = $rightsKeyOverride;
		$this->map = $map;
	}

	/**
	 * @return self No permission generated
	 */
	public static function none(): self
	{
		return new self(RightsGenerationMode::NONE, '', array());
	}

	/**
	 * @return self Read, write and delete permissions under the object key
	 */
	public static function auto(): self
	{
		return new self(RightsGenerationMode::AUTO, '', array_combine(self::OPERATIONS, self::OPERATIONS));
	}

	/**
	 * @param string             $rightsKeyOverride Permission key, '' to keep the object key
	 * @param array<mixed,mixed> $map               Operation => permission code, one entry per operation
	 * @return self Custom settings
	 * @throws \InvalidArgumentException When the key or the map is invalid
	 */
	public static function custom(string $rightsKeyOverride, array $map): self
	{
		return new self(RightsGenerationMode::CUSTOM, $rightsKeyOverride, $map);
	}

	/**
	 * @param string $json Settings serialized by toJson()
	 * @return self Settings
	 * @throws \InvalidArgumentException When the content is not exactly what toJson() produces
	 */
	public static function fromJson(string $json): self
	{
		$data = json_decode($json, true);
		if (!is_array($data)) {
			throw new \InvalidArgumentException('Rights settings are not a JSON object');
		}
		$fields = array_keys($data);
		sort($fields);
		if ($fields !== array('key', 'map', 'mode')) {
			throw new \InvalidArgumentException('Rights settings must hold exactly mode, key and map');
		}
		if (!is_string($data['mode']) || !is_string($data['key']) || !is_array($data['map'])) {
			throw new \InvalidArgumentException('Rights settings have a field of the wrong type');
		}

		return new self($data['mode'], $data['key'], $data['map']);
	}

	/**
	 * @return string JSON on one line, holding only [a-z0-9_] values
	 */
	public function toJson(): string
	{
		return (string) json_encode(array(
			'mode' => $this->mode,
			'key' => $this->rightsKeyOverride,
			'map' => (object) $this->map,
		));
	}

	/**
	 * @return string One of RightsGenerationMode constants
	 */
	public function getMode(): string
	{
		return $this->mode;
	}

	/**
	 * @return bool False in mode NONE
	 */
	public function generatesRights(): bool
	{
		return $this->mode !== RightsGenerationMode::NONE;
	}

	/**
	 * @return string Permission key replacing the object key, '' when the object key is kept
	 */
	public function getRightsKeyOverride(): string
	{
		return $this->rightsKeyOverride;
	}

	/**
	 * @param string $operation One of OPERATIONS
	 * @return string Permission code checked by that operation
	 * @throws \LogicException In mode NONE, where no operation is checked
	 * @throws \InvalidArgumentException When $operation is unknown
	 */
	public function getCodeFor(string $operation): string
	{
		if (!$this->generatesRights()) {
			throw new \LogicException('No permission is checked in mode none');
		}
		if (!isset($this->map[$operation])) {
			throw new \InvalidArgumentException('Unknown operation "'.$operation.'"');
		}

		return $this->map[$operation];
	}

	/**
	 * @return string[] Permission codes to declare, in OPERATIONS order
	 */
	public function getGeneratedCodes(): array
	{
		return array_values(array_intersect(self::OPERATIONS, $this->map));
	}

	/**
	 * @param string $key Permission key override, '' to keep the object key
	 * @return void
	 * @throws \InvalidArgumentException When the key is not a plain identifier
	 */
	public static function checkKey(string $key): void
	{
		if ($key === '') {
			return;
		}
		if (!preg_match(self::KEY_PATTERN, $key)) {
			throw new \InvalidArgumentException('Permission key must start with a lowercase letter and hold at most 64 lowercase letters, digits and underscores');
		}
		foreach (self::FORBIDDEN_KEY_PARTS as $part) {
			if (strpos($key, $part) !== false) {
				throw new \InvalidArgumentException('Permission key must not contain "'.$part.'"');
			}
		}
	}

	/**
	 * @param array<mixed,mixed> $map Operation => permission code
	 * @return array<string,string> Same map, in OPERATIONS order
	 * @throws \InvalidArgumentException When an operation is missing or extra, or a code is unknown
	 */
	private static function normalizeMap(array $map): array
	{
		$keys = array_keys($map);
		sort($keys);
		$expected = self::OPERATIONS;
		sort($expected);
		if ($keys !== $expected) {
			throw new \InvalidArgumentException('Operation map must hold exactly '.implode(', ', self::OPERATIONS));
		}

		$normalized = array();
		foreach (self::OPERATIONS as $operation) {
			if (!in_array($map[$operation], self::OPERATIONS, true)) {
				throw new \InvalidArgumentException('Operation "'.$operation.'" must be checked by one of '.implode(', ', self::OPERATIONS));
			}
			$normalized[$operation] = $map[$operation];
		}

		return $normalized;
	}
}
