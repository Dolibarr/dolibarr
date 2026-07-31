<?php
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com>
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
 * \file       htdocs/core/lib/testing.lib.php
 * \ingroup    core
 * \brief      Library of test helper functions
 */

/**
 * Cleanup test companies and all their related data
 *
 * This function deletes companies matching a code pattern (LIKE '%pattern%')
 * and all their related data including:
 * - Supplier orders and invoices
 * - Customer orders and invoices
 * - Expeditions and receptions
 * - Payments
 * - Discounts
 * - Tracking information (extrafields)
 *
 * @param  DoliDB    $db         Database handler
 * @param  User      $user       User object with delete permissions
 * @param  string    $code_pattern Code pattern to match (LIKE '%pattern%')
 * @param  boolean   $verbose    Output progress messages (default: true)
 * @param  integer   $max_passes  Maximum cleanup passes (default: 10)
 * @return integer   Number of items deleted, or -1 on error
 */
function cleanupTestCompaniesByCode(DoliDB $db, $user, $code_pattern, $verbose = true, $max_passes = 10)
{
	global $langs;

	// Safety check: pattern must contain "TEST" (case insensitive)
	if (stripos($code_pattern, 'TEST') === false) {
		if ($verbose) {
			print '<p class="error">ERROR: Code pattern must contain "TEST" for safety. Pattern: '.dol_escape_htmltag($code_pattern).'</p>';
		}
		return -1;
	}

	$total_deleted_count = 0;
	$pass_count = 0;

	// Escape the pattern for SQL
	$sql_escaped_pattern = $db->escape($db->escapeforlike($code_pattern));

	// Use a loop to handle dependencies - keep trying until nothing more can be deleted
	do {
		$pass_count++;
		$workdone = 0;
		$pass_deleted = 0;

		if ($verbose && $pass_count > 1) {
			print '<p>Starting cleanup pass '.$pass_count.' to handle remaining dependencies...</p>';
		}

		// 1. Find company IDs matching the pattern
		$socids = array();
		$sql = "SELECT rowid FROM ".$db->prefix()."societe WHERE code_client LIKE '%" . $sql_escaped_pattern . "%' OR code_fournisseur LIKE '%" . $sql_escaped_pattern . "%' ORDER BY rowid";
		$resql = $db->query($sql);

		if (!$resql) {
			if ($verbose) {
				print '<p class="error">Error searching for companies: '.dol_escape_htmltag($db->lasterror).'</p>';
			}
			return -1;
		}

		while ($obj = $db->fetch_object($resql)) {
			$socids[] = $obj->rowid;
		}

		if (empty($socids)) {
			if ($verbose) {
				print '<p class="ok">No test companies found with code matching ".$code_pattern.". Nothing to tear down.</p>';
			}
			break;
		}

		// 2. Delete expeditions for these companies
		if (!empty($socids)) {
			$socid_list = implode(',', $socids);
			$sql = "SELECT rowid FROM ".$db->prefix()."expedition WHERE fk_soc IN (" . $db->sanitize($socid_list) . ") ORDER BY rowid";
			$resql = $db->query($sql);
			$expeditions = array();
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					$expeditions[] = $obj->rowid;
				}
			}

			if (!empty($expeditions)) {
				if ($verbose) {
					print '<p>Deleting '.count($expeditions).' expedition(s)...</p>';
				}
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				foreach ($expeditions as $expeditionid) {
					$exp = new Expedition($db);
					$result = $exp->fetch($expeditionid);
					if ($result > 0) {
						$result = $exp->delete($user);
						if ($result > 0) {
							$workdone++;
							$total_deleted_count++;
							$pass_deleted++;
							if ($verbose) {
								print '<p class="ok">Deleted expedition ID: '.$expeditionid.'</p>';
							}
						} else {
							if ($verbose) {
								$error_msg = $exp->error ? dol_escape_htmltag($exp->error) : '(no error message)';
								print '<p class="warning">Failed to delete expedition ID: '.$expeditionid.' (error: '.$error_msg.')</p>';
							}
						}
					} else {
						if ($verbose) {
							print '<p class="warning">Expedition ID: '.$expeditionid.' not found</p>';
						}
					}
				}
			}
		}

		// 3. Delete receptions for these companies
		if (!empty($socids)) {
			$socid_list = implode(',', $socids);
			$sql = "SELECT rowid FROM ".$db->prefix()."reception WHERE fk_soc IN (" . $db->sanitize($socid_list) . ") ORDER BY rowid";
			$resql = $db->query($sql);
			$receptions = array();
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					$receptions[] = $obj->rowid;
				}
			}

			if (!empty($receptions)) {
				if ($verbose) {
					print '<p>Deleting '.count($receptions).' reception(s)...</p>';
				}
				require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
				foreach ($receptions as $receptionid) {
					$rec = new Reception($db);
					$result = $rec->fetch($receptionid);
					if ($result > 0) {
						$result = $rec->delete($user);
						if ($result > 0) {
							$workdone++;
							$total_deleted_count++;
							$pass_deleted++;
							if ($verbose) {
								print '<p class="ok">Deleted reception ID: '.$receptionid.'</p>';
							}
						} else {
							if ($verbose) {
								$error_msg = $rec->error ? dol_escape_htmltag($rec->error) : '(no error message)';
								print '<p class="warning">Failed to delete reception ID: '.$receptionid.' (error: '.$error_msg.')</p>';
							}
						}
					} else {
						if ($verbose) {
							print '<p class="warning">Reception ID: '.$receptionid.' not found</p>';
						}
					}
				}
			}
		}

		// 4. Delete supplier invoices for these companies
		if (!empty($socids)) {
			$socid_list = implode(',', $socids);
			$sql = "SELECT rowid FROM ".$db->prefix()."facture_fourn WHERE fk_soc IN (" . $db->sanitize($socid_list) . ") ORDER BY rowid";
			$resql = $db->query($sql);
			$invoices = array();
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					$invoices[] = $obj->rowid;
				}
			}

			if (!empty($invoices)) {
				if ($verbose) {
					print '<p>Deleting '.count($invoices).' supplier invoice(s)...</p>';
				}
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				foreach ($invoices as $invoiceid) {
					$inv = new FactureFournisseur($db);
					$result = $inv->fetch($invoiceid);
					if ($result > 0) {
						$result = $inv->delete($user);
						if ($result > 0) {
							$workdone++;
							$total_deleted_count++;
							$pass_deleted++;
							if ($verbose) {
								print '<p class="ok">Deleted supplier invoice ID: '.$invoiceid.'</p>';
							}
						} else {
							if ($verbose) {
								$error_msg = $inv->error ? dol_escape_htmltag($inv->error) : '(no error message)';
								print '<p class="warning">Failed to delete supplier invoice ID: '.$invoiceid.' (error: '.$error_msg.')</p>';
							}
						}
					} else {
						if ($verbose) {
							print '<p class="warning">Supplier invoice ID: '.$invoiceid.' not found</p>';
						}
					}
				}
			}
		}

		// 5. Delete supplier orders for these companies
		if (!empty($socids)) {
			$socid_list = implode(',', $socids);
			$sql = "SELECT rowid FROM ".$db->prefix()."commande_fournisseur WHERE fk_soc IN (" . $db->sanitize($socid_list) . ") ORDER BY rowid";
			$resql = $db->query($sql);
			$orders = array();
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					$orders[] = $obj->rowid;
				}
			}

			if (!empty($orders)) {
				if ($verbose) {
					print '<p>Deleting '.count($orders).' supplier order(s)...</p>';
				}
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				foreach ($orders as $orderid) {
					$ord = new CommandeFournisseur($db);
					$result = $ord->fetch($orderid);
					if ($result > 0) {
						$result = $ord->delete($user);
						if ($result > 0) {
							$workdone++;
							$total_deleted_count++;
							$pass_deleted++;
							if ($verbose) {
								print '<p class="ok">Deleted supplier order ID: '.$orderid.'</p>';
							}
						} else {
							if ($verbose) {
								$error_msg = $ord->error ? dol_escape_htmltag($ord->error) : '(no error message)';
								print '<p class="warning">Failed to delete supplier order ID: '.$orderid.' (error: '.$error_msg.')</p>';
							}
						}
					} else {
						if ($verbose) {
							print '<p class="warning">Supplier order ID: '.$orderid.' not found</p>';
						}
					}
				}
			}
		}

		// 6. Delete companies
		if (!empty($socids)) {
			if ($verbose) {
				print '<p>Deleting '.count($socids).' company(ies)...</p>';
			}
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			foreach ($socids as $socid) {
				$soc = new Societe($db);
				$result = $soc->fetch($socid);
				if ($result > 0) {
					$result = $soc->delete($user->id);
					if ($result > 0) {
						$workdone++;
						$total_deleted_count++;
						$pass_deleted++;
						if ($verbose) {
							print '<p class="ok">Deleted company ID: '.$socid.' ('.$soc->name.')</p>';
						}
					} else {
						if ($verbose) {
							$error_msg = $soc->error ? dol_escape_htmltag($soc->error) : '(no error message)';
							print '<p class="warning">Failed to delete company ID: '.$socid.' ('.$soc->name.', error: '.$error_msg.')</p>';
						}
					}
				} else {
					if ($verbose) {
						print '<p class="warning">Company ID: '.$socid.' not found</p>';
					}
				}
			}
		}

		if ($verbose && $pass_deleted > 0) {
			print '<p class="ok">Pass '.$pass_count.' deleted '.$pass_deleted.' items.</p>';
		} elseif ($verbose) {
			print '<p class="ok">Pass '.$pass_count.' - nothing more to delete.</p>';
		}
	} while ($workdone > 0 && $pass_count < $max_passes);

	return $total_deleted_count;
}
