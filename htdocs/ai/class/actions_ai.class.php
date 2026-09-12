<?php
/* Copyright (C) 2026 Nick Fragoulis
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
 * \file    htdocs/ai/class/actions_ai.class.php
 * \ingroup ai
 * \brief   Hooks making the AI assistant aware of the page being viewed.
 *          printCommonFooter fires on every standard
 *          page and receives the page's main object when one is loaded.
 */

/**
 * Class ActionsAi
 */
class ActionsAi
{
	/**
	 * @var string[] Errors
	 */
	public $errors = array();

	/**
	 * @var string Return value
	 */
	public $resprints = '';

	/**
	 * @var int[] Row ids collected while the current list page renders
	 */
	private $listRowIds = array();

	/**
	 * @var bool The per-row list hook fired: this page is a list, whatever the
	 *           template object claims and whether id capture succeeded
	 */
	private $listPageSeen = false;

	/**
	 * Collect the id of each rendered list row.
	 *
	 * printFieldListValue is an existing hook fired once per row inside every
	 * standard list loop, the HookManager reuses this instance for the whole
	 * request, so the ids accumulate here and printCommonFooter (which fires
	 * later on the same page) emits them.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters ('obj' = row)
	 * @param CommonObject        $object      Page object
	 * @param string              $action      Current action
	 * @return int 0
	 */
	public function printFieldListValue($parameters, &$object, &$action)
	{
		$this->listPageSeen = true;
		if (count($this->listRowIds) < 100 && isset($parameters['obj']) && is_object($parameters['obj'])) {
			// Lists alias the primary key inconsistently in their SELECT:
			// societe uses "rowid", facture uses "f.rowid as id" - accept both.
			$rowid = (int) ($parameters['obj']->rowid ?? $parameters['obj']->id ?? 0);
			if ($rowid > 0) {
				$this->listRowIds[] = $rowid;
			}
		}

		return 0;
	}

	/**
	 * Emit the current page context for the assistant popover.
	 *
	 * Runs on every page footer and emits window.aiPageContext, the page
	 * identity the chat JS posts back with each request so the assistant
	 * resolves references like "this invoice" or "the selected rows".
	 *
	 * Three page shapes are recognized:
	 * - Card/tab pages: the loaded main object (element, id, ref, thirdparty
	 *   id) - only data the page itself already displays, since the page
	 *   enforces its reading rights before this hook fires.
	 * - List pages: element, active filters, the row ids the current page
	 *   renders (printFieldListValue collects them) and the checked ones.
	 * - Module dashboards and the home page: a whitelisted page label only.
	 *
	 * Trust boundary: this emission is a hint, never an authorization. The
	 * POST that carries it back is client-controlled, so parse_intent.php
	 * re-validates everything independently on every chat request (element
	 * whitelist, fetch, user rights, entity) before any of it reaches the
	 * prompt.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param CommonObject        $object      The page's main object (may be empty)
	 * @param string              $action      Current action
	 * @return int 0 on success
	 */
	public function printCommonFooter($parameters, &$object, &$action)
	{
		global $conf, $user;

		if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
			return 0;
		}
		if (empty($user->id) || !$user->hasRight('ai', 'assistant', 'use')) {
			return 0;
		}
		// printCommonFooter's call site passes a hardcoded null object; the
		// page's main object is only reachable via the global scope here.
		$pageObject = $object;
		if (!is_object($pageObject) || empty($pageObject->id)) {
			$pageObject = isset($GLOBALS['object']) ? $GLOBALS['object'] : null;
		}
		// Card vs list discrimination: the per-row hook fired = list page,
		// whatever the template $object happens to hold; and a card emission
		// additionally requires the URL to actually address the object (card
		// pages always carry id or ref), so a list page whose template object
		// carries a stray id can never masquerade as a card.
		$urlIds = array((int) GETPOST('id', 'int'), (int) GETPOST('facid', 'int'), (int) GETPOST('socid', 'int'));
		$looksLikeCard = is_object($pageObject) && !empty($pageObject->id)
			&& (in_array((int) $pageObject->id, $urlIds, true) || (GETPOST('ref', 'alphanohtml') !== '' && GETPOST('ref', 'alphanohtml') == ($pageObject->ref ?? '')));
		if ($this->listPageSeen || !empty($this->listRowIds) || !$looksLikeCard || empty($pageObject->element)) {
			// List/dashboard pages: no single object, but the user's own active
			// filters are context enough - emitted uninterpreted, the model
			// maps them onto tool arguments (which validate as always).
			$filters = array();
			foreach ($_GET as $k => $v) {
				if (!is_string($v) || $v === '' || strlen($v) > 200) {
					continue;
				}
				if (preg_match('/^(search_[a-z0-9_]+|sall|search_all|sortfield|sortorder|contextpage)$/', $k)) {
					$filters[$k] = $v;
				}
			}
			$listElement = (is_object($pageObject) && !empty($pageObject->element)) ? (string) $pageObject->element : '';

			// Module dashboards (index pages): no object, no rows - the page
			// identity itself is the context ("what am I looking at?" and
			// module-scoped statistics questions). Whitelisted paths only, so
			// admin or third-party pages never leak their URLs into prompts.
			if (!$this->listPageSeen && empty($filters)) {
				$dashboards = array(
					'/index.php' => 'main home',
					'/societe/index.php' => 'thirdparties',
					'/compta/facture/index.php' => 'customer invoices',
					'/fourn/facture/index.php' => 'supplier invoices',
					'/commande/index.php' => 'sales orders',
					'/fourn/commande/index.php' => 'supplier orders',
					'/comm/propal/index.php' => 'commercial proposals',
					'/supplier_proposal/index.php' => 'supplier proposals',
					'/product/index.php' => 'products and services',
					'/product/stock/index.php' => 'stock and warehouses',
					'/projet/index.php' => 'projects',
					'/contrat/index.php' => 'contracts',
					'/fichinter/index.php' => 'interventions',
					'/ticket/index.php' => 'tickets',
					'/expedition/index.php' => 'shipments',
					'/reception/index.php' => 'receptions',
					'/adherents/index.php' => 'members',
					'/don/index.php' => 'donations',
					'/expensereport/index.php' => 'expense reports',
					'/comm/action/index.php' => 'agenda',
					'/mrp/index.php' => 'manufacturing',
					'/recruitment/index.php' => 'recruitment'
				);
				// Exact match on the path relative to DOL_URL_ROOT - a suffix
				// match on '/index.php' would hit every module index for the
				// home entry.
				$self = (string) ($_SERVER['PHP_SELF'] ?? '');
				$rel = (DOL_URL_ROOT !== '' && strpos($self, DOL_URL_ROOT) === 0) ? substr($self, strlen(DOL_URL_ROOT)) : $self;
				if (isset($dashboards[$rel])) {
					print "\n".'<script nonce="'.getNonce().'">window.aiPageContext = '.json_encode(array('dashboard' => $dashboards[$rel])).';</script>'."\n";

					return 0;
				}
			}
			if (($this->listPageSeen || !empty($filters) || !empty($this->listRowIds)) && !empty($listElement)) {
				$ctx = array('element' => $listElement, 'list' => 1, 'filters' => $filters);
				if (!empty($this->listRowIds)) {
					$ctx['ids'] = $this->listRowIds;	// rows this page actually shows
				}
				print "\n".'<script nonce="'.getNonce().'">window.aiPageContext = '.json_encode($ctx).';</script>'."\n";
			}

			return 0;
		}

		$context = array(
			'element' => (string) $pageObject->element,
			'id' => (int) $pageObject->id,
			'ref' => (string) ($pageObject->ref ?? ''),
			'socid' => (int) ($pageObject->socid ?? 0)
		);

		// printCommonFooter output convention: print directly (the caller does
		// not echo resPrint for this hook).
		print "\n".'<script nonce="'.getNonce().'">window.aiPageContext = '.json_encode($context).';</script>'."\n";

		return 0;
	}
}
