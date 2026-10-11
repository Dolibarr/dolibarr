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
 * \file    htdocs/modulebuilder/class/AndRule.class.php
 * \ingroup modulebuilder
 * \brief   Conjunction node of the access rule tree.
 */

require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/AccessRuleExpression.class.php';

/**
 * Conjunction node of the access rule tree: met when all its rules are met.
 */
final class AndRule implements AccessRuleExpression
{
	const TYPE = 'and';

	/** @var AccessRuleExpression[] */
	private $rules;

	/**
	 * @param	AccessRuleExpression[]	$rules	Child nodes, at least one
	 * @throws	\InvalidArgumentException	When the list is empty or holds something else than nodes
	 */
	public function __construct(array $rules)
	{
		if (empty($rules)) {
			throw new \InvalidArgumentException('An access rule and node requires at least one rule');
		}
		foreach ($rules as $rule) {
			if (!$rule instanceof AccessRuleExpression) {
				throw new \InvalidArgumentException('An access rule and node only accepts access rule nodes');
			}
		}
		$this->rules = array_values($rules);
	}

	/**
	 * @return AccessRuleExpression[]
	 */
	public function getRules(): array
	{
		return $this->rules;
	}

	/**
	 * @return string
	 */
	public function toPhp(): string
	{
		$parts = array();
		foreach ($this->rules as $rule) {
			$parts[] = $rule->toPhp();
		}
		return '('.implode(' && ', $parts).')';
	}

	/**
	 * @return array{type:string,rules:array<int,array<string,mixed>>}
	 */
	public function toArray(): array
	{
		$rules = array();
		foreach ($this->rules as $rule) {
			$rules[] = $rule->toArray();
		}
		return array('type' => self::TYPE, 'rules' => $rules);
	}
}
