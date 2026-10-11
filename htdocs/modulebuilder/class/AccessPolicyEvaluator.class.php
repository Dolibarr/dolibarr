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
 * \file    htdocs/modulebuilder/class/AccessPolicyEvaluator.class.php
 * \ingroup modulebuilder
 * \brief   Evaluates the rule tree of an access policy.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/AccessRuleEvaluator.class.php';
require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/AccessPolicyConfig.class.php';

/**
 * Evaluates the rule tree of an access policy. This is the reference semantics of the PHP block
 * rendered into the generated pages: both must grant the same access to the same user.
 */
final class AccessPolicyEvaluator implements AccessRuleEvaluator
{
	/** @var AccessPolicyConfig */
	private $policy;

	/**
	 * @param	AccessPolicyConfig	$policy	Policy to evaluate
	 */
	public function __construct(AccessPolicyConfig $policy)
	{
		$this->policy = $policy;
	}

	/**
	 * @param	User			$user	User asking for the access
	 * @param	CommonObject	$obj	Object to access, it provides the module and element of the permissions
	 * @return	bool					True when the access is granted, false otherwise, including on failure
	 */
	public function evaluate(User $user, CommonObject $obj): bool
	{
		try {
			return $this->evaluateNode($this->policy->getExpression(), $user, $obj);
		} catch (\Throwable $e) {
			dol_syslog(__METHOD__.': access policy evaluation failed, access denied: '.$e->getMessage(), LOG_ERR);
			return false;
		}
	}

	/**
	 * @param	AccessRuleExpression	$node	Node to evaluate
	 * @param	User					$user	User asking for the access
	 * @param	CommonObject			$obj	Object to access
	 * @return	bool
	 * @throws	\LogicException			When the node or the predicate is unknown
	 */
	private function evaluateNode(AccessRuleExpression $node, User $user, CommonObject $obj): bool
	{
		if ($node instanceof AndRule) {
			foreach ($node->getRules() as $child) {
				if (!$this->evaluateNode($child, $user, $obj)) {
					return false;
				}
			}
			return true;
		}
		if ($node instanceof OrRule) {
			foreach ($node->getRules() as $child) {
				if ($this->evaluateNode($child, $user, $obj)) {
					return true;
				}
			}
			return false;
		}
		if ($node instanceof PredicateRule) {
			return $this->evaluatePredicate($node->getName(), $user, $obj);
		}
		throw new \LogicException('Unknown access rule node: '.get_class($node));
	}

	/**
	 * @param	string			$name	Name of the predicate
	 * @param	User			$user	User asking for the access
	 * @param	CommonObject	$obj	Object to access
	 * @return	bool
	 * @throws	\LogicException			When the predicate is unknown
	 */
	private function evaluatePredicate(string $name, User $user, CommonObject $obj): bool
	{
		switch ($name) {
			case PredicateRule::IS_ADMIN:
				return !empty($user->admin);
			case PredicateRule::IS_INTERNAL_USER:
				return empty($user->socid);
			case PredicateRule::CAN_READ_OBJECT:
				return (bool) $user->hasRight($obj->module, $obj->element, 'read');
			case PredicateRule::CAN_WRITE_OBJECT:
				return (bool) $user->hasRight($obj->module, $obj->element, 'write');
			case PredicateRule::CAN_DELETE_OBJECT:
				return (bool) $user->hasRight($obj->module, $obj->element, 'delete');
		}
		throw new \LogicException('Unknown access rule predicate: '.$name);
	}
}
