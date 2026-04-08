<?php
/* Copyright (C) 2026       Alexandre Spangaro          <alexandre@inovea-conseil.com>
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
 *  \file       htdocs/core/lib/supplier_rights.lib.php
 *  \ingroup    fournisseur
 *  \brief      Common helpers to manage supplier order and supplier invoice rights
 */

/**
 * Check if user matches one of provided rights definitions.
 *
 * A right definition can contain:
 * - 2 levels: array('module', 'perm')
 * - 3 levels: array('module', 'subperm', 'perm')
 *
 * @param User  $user    User object
 * @param array<int, array{0:string,1:string}|array{0:string,1:string,2:string}> $rights List of rights definitions
 * @return bool
 */
function supplierRightsMatch(User $user, array $rights)
{
	foreach ($rights as $right) {
		if (!is_array($right)) {
			continue;
		}

		if (count($right) === 3 && $user->hasRight($right[0], $right[1], $right[2])) {
			return true;
		}

		if (count($right) === 2 && $user->hasRight($right[0], $right[1])) {
			return true;
		}
	}

	return false;
}


/**
 * Check a supplier order right using legacy and new permissions.
 *
 * Examples:
 * - supplierOrderHasRight($user, 'lire')
 * - supplierOrderHasRight($user, 'creer')
 * - supplierOrderHasRight($user, 'supprimer')
 * - supplierOrderHasRight($user, 'approuver')
 * - supplierOrderHasRight($user, 'commander')
 * - supplierOrderHasRight($user, 'receptionner')
 * - supplierOrderHasRight($user, 'check')
 * - supplierOrderHasRight($user, 'validate')
 * - supplierOrderHasRight($user, 'approve2')
 * - supplierOrderHasRight($user, 'export')
 *
 * @param User   $user  User object
 * @param string $perm  Permission key
 * @return bool
 */
function supplierOrderHasRight(User $user, $perm)
{
	$map = array(
		'lire' => array(
			array('fournisseur', 'commande', 'lire'),
			array('supplier_order', 'lire'),
		),
		'creer' => array(
			array('fournisseur', 'commande', 'creer'),
			array('$supplier_order', 'creer'),
		),
		'supprimer' => array(
			array('fournisseur', 'commande', 'supprimer'),
			array('$supplier_order', 'supprimer'),
		),
		'approuver' => array(
			array('fournisseur', 'commande', 'approuver'),
			array('$supplier_order', 'approuver'),
		),
		'commander' => array(
			array('fournisseur', 'commande', 'commander'),
			array('$supplier_order', 'commander'),
		),
		'receptionner' => array(
			array('fournisseur', 'commande', 'receptionner'),
			array('$supplier_order', 'receptionner'),
		),
		'check' => array(
			array('fournisseur', 'commandeadvance', 'check'),
			array('$supplier_order', 'supplier_order_advance', 'check'),
		),
		'validate' => array(
			array('fournisseur', 'supplier_order_advance', 'validate'),
			array('$supplier_order', 'supplier_order_advance', 'validate'),
		),
		'approve2' => array(
			array('fournisseur', 'commande', 'approve2'),
			array('$supplier_order', 'approve2'),
		),
		'export' => array(
			array('fournisseur', 'commande', 'export'),
			array('$supplier_order', 'export'),
		),
	);

	if (empty($map[$perm])) {
		return false;
	}

	return supplierRightsMatch($user, $map[$perm]);
}


/**
 * Check a supplier invoice right using legacy and new permissions.
 *
 * Examples:
 * - supplierInvoiceHasRight($user, 'lire')
 * - supplierInvoiceHasRight($user, 'creer')
 * - supplierInvoiceHasRight($user, 'validate')
 * - supplierInvoiceHasRight($user, 'supprimer')
 * - supplierInvoiceHasRight($user, 'send')
 * - supplierInvoiceHasRight($user, 'export')
 *
 * @param User   $user  User object
 * @param string $perm  Permission key
 * @return bool
 */
function supplierInvoiceHasRight(User $user, $perm)
{
	$map = array(
		'lire' => array(
			array('fournisseur', 'facture', 'lire'),
			array('supplier_invoice', 'lire'),
		),
		'creer' => array(
			array('fournisseur', 'facture', 'creer'),
			array('supplier_invoice', 'creer'),
		),
		'validate' => array(
			array('fournisseur', 'supplier_invoice_advance', 'validate'),
			array('supplier_invoice', 'supplier_invoice_advance', 'validate'),
		),
		'supprimer' => array(
			array('fournisseur', 'facture', 'supprimer'),
			array('supplier_invoice', 'supprimer'),
		),
		'send' => array(
			array('fournisseur', 'supplier_invoice_advance', 'send'),
			array('supplier_invoice', 'supplier_invoice_advance', 'send'),
		),
		'export' => array(
			array('fournisseur', 'facture', 'export'),
			array('supplier_invoice', 'export'),
		),
	);

	if (empty($map[$perm])) {
		return false;
	}

	return supplierRightsMatch($user, $map[$perm]);
}
