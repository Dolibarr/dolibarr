<?php
/* Copyright (C) 2026		MDW	<mdeweerd@users.noreply.github.com>
 */
/**
 * \file    htdocs/test_shipping_tracking.php
 * \ingroup test
 * \brief   Interactive test for supplier order/invoice tracking functionality
 */

require_once 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/handlers/shipping_handler.php';


global $db, $user;
/**
 * @var User $user
 * @var DoliDB $db
 */

// Access control - restricted to users with supplier order permissions
if (!$user->rights->fournisseur->commande->lire) {
	accessforbidden();
}

$permission_test_case = true;

$action = GETPOST('action', 'aZ09');
$timestamp = time();
$test_prefix = 'TRACKTEST_'.$timestamp;


llxHeader('', 'Shipping Tracking Test - Issue #32082');

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

// Test 1: Create supplier and test data
if ($action == 'create' && $permission_test_case) {  // @phpstan-ignore-line
	print '<h2>Creating Test Data</h2>';

	// Create a test supplier
	$soc = new Societe($db);
	$soc->name = 'Test Supplier - '.$timestamp;
	$soc->name_alias = 'Test Supplier Alias';
	$soc->code_client = '';
	$soc->code_fournisseur = $test_prefix;
	$soc->client = 0;
	$soc->fournisseur = 1;
	$soc->email = 'test-supplier-'.$timestamp.'@example.com';
	$soc->address = '123 Test Street';
	$soc->zip = '12345';
	$soc->town = 'Test City';
	$soc->country_id = 1; // France by default

	$result = $soc->create($user);
	if ($result <= 0) {
		print '<p class="error">ERROR: Failed to create supplier: '.dol_escape_htmltag($soc->error).'</p>';
		llxFooter();
		exit(1);
	}

	$socid = $soc->id;
	print '<p class="ok">Supplier created: ID '.$socid.' (Code: '.$test_prefix.')</p>';

	// Create a supplier order
	$order = new CommandeFournisseur($db);
	$order->socid = $socid;
	$order->date = dol_now();
	$order->date_livraison = dol_time_plus_duree(dol_now(), 7, 'd');
	$order->ref_supplier = 'ORDER-'.$timestamp;
	$order->statut = 0; // Draft

	$result = $order->create($user);
	if ($result <= 0) {
		print '<p class="error">ERROR: Failed to create supplier order: '.dol_escape_htmltag($order->error).'</p>';
		llxFooter();
		exit(1);
	}

	$orderid = $order->id;
	print '<p class="ok">Supplier Order created: ID '.$orderid.' (Ref: '.$order->ref.')</p>';

	// Add a line to the order
	$order->addline('Test Product', 100.0, 1, 19.6, 0.0, 0.0, 0, 0, '', 0.0, 'HT', 0.0, 0, 0, 0);
	print '<p class="ok">Order line added</p>';

	// Validate the order
	$result = $order->valid($user);
	if ($result <= 0) {
		print '<p class="warning">Warning: Failed to validate order: '.dol_escape_htmltag($order->error).'</p>';
	} else {
		print '<p class="ok">Supplier Order validated</p>';
	}

	// Set tracking information on the order using the shipping handler
	$tracking_awb = 'FX123456789012';
	$carrier_code = 'FX';
	$tracking_link = generate_tracking_link($tracking_awb, $carrier_code);

	// Set the tracking data via extrafields
	$order->array_options['options_tracking_awb'] = $tracking_awb;
	$order->array_options['options_tracking_link'] = $tracking_link;
	$order->array_options['options_carrier_code'] = $carrier_code;

	// Update the order to save extrafields
	$result = $order->update($user);
	if ($result > 0) {
		print '<p class="ok">Tracking info set on order: AWB='.$tracking_awb.', ShippingMethod='.$carrier_code.'</p>';
	} else {
		print '<p class="warning">Warning: Failed to update order with tracking info</p>';
	}

	// Create an invoice from the order
	$invoice = new FactureFournisseur($db);
	$invoice->socid = $socid;
	$invoice->date = dol_now();
	$invoice->type = 0; // Standard invoice
	$invoice->ref_supplier = 'INV-'.$timestamp;

	$result = $invoice->create($user);
	if ($result <= 0) {
		print '<p class="error">ERROR: Failed to create supplier invoice: '.dol_escape_htmltag($invoice->error).'</p>';
		llxFooter();
		exit(1);
	}

	$invoiceid = $invoice->id;
	print '<p class="ok">Supplier Invoice created: ID '.$invoiceid.' (Ref: '.$invoice->ref.')</p>';

	// Add a line to the invoice
	$invoice->addline('Test product for tracking', 100.0, 19.6, 0.0, 0.0, 1, 0);
	print '<p class="ok">Invoice line added</p>';

	// Validate the invoice
	$result = $invoice->validate($user);
	if ($result <= 0) {
		print '<p class="warning">Warning: Failed to validate invoice: '.dol_escape_htmltag($invoice->error).'</p>';
	} else {
		print '<p class="ok">Supplier Invoice validated</p>';
	}

	// Link invoice to order
	$order->add_object_linked('invoice_supplier', $invoiceid);

	// Set tracking information on the invoice using business method
	if ($invoice->setTrackingInfo($tracking_awb, $carrier_code, $tracking_link)) {
		$result = $invoice->update($user);
		if ($result > 0) {
			print '<p class="ok">Tracking info set on invoice: AWB='.$tracking_awb.', ShippingMethod='.$carrier_code.'</p>';
		} else {
			print '<p class="warning">Warning: Failed to update invoice with tracking info</p>';
		}
	} else {
		print '<p class="warning">Warning: Failed to set tracking info on invoice</p>';
	}

	// Create an expedition (shipment) for tracking test
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	$expedition = new Expedition($db);
	$expedition->origin = 'commande';
	$expedition->origin_id = $orderid;
	$expedition->socid = $socid;
	$expedition->date_expedition = dol_now();
	$expedition->ref = 'EXP-'.$timestamp;
	$expedition->statut = 0;

	// Set shipping method to a valid one
	$expedition->shipping_method_id = 1; // Assuming TRANS or CATCH exists

	$result = $expedition->create($user);
	if ($result > 0) {
		print '<p class="ok">Expedition created: ID '.$expedition->id.' (Ref: '.$expedition->ref.')</p>';

		// Set tracking number
		$expedition->tracking_number = 'EXP'.$timestamp;
		$expedition->update($user);
		print '<p class="ok">Expedition tracking set: '.$expedition->tracking_number.'</p>';

		// Generate tracking URL using the class method
		$expedition->getUrlTrackingStatus($expedition->tracking_number);
		print '<p class="ok">Expedition tracking URL: '.($expedition->tracking_url ?: 'Not generated').'</p>';

		$expeditionid = $expedition->id;
		$_SESSION['test_shipping_expeditionid'] = $expeditionid;
	} else {
		print '<p class="warning">Warning: Failed to create expedition: '.dol_escape_htmltag($expedition->error).'</p>';
		$expeditionid = 0;
	}

	// Create a reception for tracking test
	require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
	$reception = new Reception($db);
	$reception->origin = 'commande';
	$reception->origin_id = $orderid;
	$reception->socid = $socid;
	$reception->date_reception = dol_now();
	$reception->ref = 'REC-'.$timestamp;
	$reception->statut = 0;

	// Set shipping method
	$reception->shipping_method_id = 1;

	$result = $reception->create($user);
	if ($result > 0) {
		print '<p class="ok">Reception created: ID '.$reception->id.' (Ref: '.$reception->ref.')</p>';

		// Set tracking number
		$reception->tracking_number = 'REC'.$timestamp;
		$reception->update($user);
		print '<p class="ok">Reception tracking set: '.$reception->tracking_number.'</p>';

		// Generate tracking URL
		$reception->getUrlTrackingStatus($reception->tracking_number);
		print '<p class="ok">Reception tracking URL: '.($reception->tracking_url ?: 'Not generated').'</p>';

		$receptionid = $reception->id;
		$_SESSION['test_shipping_receptionid'] = $receptionid;
	} else {
		print '<p class="warning">Warning: Failed to create reception: '.dol_escape_htmltag($reception->error).'</p>';
		$receptionid = 0;
	}

	print '<br>';
	print '<h3>Test Data Created Successfully!</h3>';
	print '<p><a href="fourn/commande/card.php?id='.$orderid.'" class="butAction" target="_blank">View Supplier Order</a></p>';
	print '<p><a href="fourn/facture/card.php?id='.$invoiceid.'" class="butAction" target="_blank">View Supplier Invoice</a></p>';
	if (!empty($expeditionid)) {
		print '<p><a href="expedition/card.php?id='.$expeditionid.'" class="butAction" target="_blank">View Expedition</a></p>';
	}
	if (!empty($receptionid)) {
		print '<p><a href="reception/card.php?id='.$receptionid.'" class="butAction" target="_blank">View Reception</a></p>';
	}

	// Store IDs in session for teardown
	$_SESSION['test_shipping_socid'] = $socid;
	$_SESSION['test_shipping_orderid'] = $orderid;
	$_SESSION['test_shipping_invoiceid'] = $invoiceid;
	$_SESSION['test_shipping_expeditionid'] = $expeditionid;
	$_SESSION['test_shipping_receptionid'] = $receptionid;
	$_SESSION['test_shipping_prefix'] = $test_prefix;
} elseif ($action == 'teardown' && $permission_test_case) {  // @phpstan-ignore-line
	// Teardown test data
	print '<h2>Deleting Test Data</h2>';

	$deleted_count = 0;

	// Get stored IDs
	$socid = GETPOSTINT('socid') ?: (isset($_SESSION['test_shipping_socid']) ? $_SESSION['test_shipping_socid'] : 0);
	$orderid = GETPOSTINT('orderid') ?: (isset($_SESSION['test_shipping_orderid']) ? $_SESSION['test_shipping_orderid'] : 0);
	$invoiceid = GETPOSTINT('invoiceid') ?: (isset($_SESSION['test_shipping_invoiceid']) ? $_SESSION['test_shipping_invoiceid'] : 0);
	$expeditionid = GETPOSTINT('expeditionid') ?: (isset($_SESSION['test_shipping_expeditionid']) ? $_SESSION['test_shipping_expeditionid'] : 0);
	$receptionid = GETPOSTINT('receptionid') ?: (isset($_SESSION['test_shipping_receptionid']) ? $_SESSION['test_shipping_receptionid'] : 0);
	$test_prefix = GETPOST('prefix', 'alpha') ?: (isset($_SESSION['test_shipping_prefix']) ? $_SESSION['test_shipping_prefix'] : '');

	// If no IDs in session, try to find by prefix
	if (empty($socid) && !empty($test_prefix)) {
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE code_fournisseur LIKE '"
			.$db->escape($test_prefix)."%' AND fournisseur = 1";
		$resql = $db->query($sql);
		if ($resql && $db->num_rows($resql) > 0) {
			$obj = $db->fetch_object($resql);
			$socid = $obj->rowid;
		}
	}

	// Delete reception
	if (!empty($receptionid)) {
		require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
		$reception = new Reception($db);
		if ($reception->fetch($receptionid) > 0) {
			$result = $reception->delete($user);
			if ($result > 0) {
				print '<p class="ok">Deleted Reception ID: '.$receptionid.'</p>';
				$deleted_count++;
			} else {
				print '<p class="warning">Warning: Failed to delete reception ID: '.$receptionid.' - '.dol_escape_htmltag($reception->error).'</p>';
			}
		} else {
			print '<p class="warning">Reception ID: '.$receptionid.' not found</p>';
		}
	}

	// Delete expedition
	if (!empty($expeditionid)) {
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		$expedition = new Expedition($db);
		if ($expedition->fetch($expeditionid) > 0) {
			$result = $expedition->delete($user);
			if ($result > 0) {
				print '<p class="ok">Deleted Expedition ID: '.$expeditionid.'</p>';
				$deleted_count++;
			} else {
				print '<p class="warning">Warning: Failed to delete expedition ID: '.$expeditionid.' - '.dol_escape_htmltag($expedition->error).'</p>';
			}
		} else {
			print '<p class="warning">Expedition ID: '.$expeditionid.' not found</p>';
		}
	}

	// Delete invoice
	if (!empty($invoiceid)) {
		$invoice = new FactureFournisseur($db);
		if ($invoice->fetch($invoiceid) > 0) {
			$result = $invoice->delete($user);
			if ($result > 0) {
				print '<p class="ok">Deleted Supplier Invoice ID: '.$invoiceid.'</p>';
				$deleted_count++;
			} else {
				print '<p class="warning">Warning: Failed to delete invoice ID: '.$invoiceid.' - '.dol_escape_htmltag($invoice->error).'</p>';
			}
		} else {
			print '<p class="warning">Invoice ID: '.$invoiceid.' not found</p>';
		}
	}

	// Delete order
	if (!empty($orderid)) {
		$order = new CommandeFournisseur($db);
		if ($order->fetch($orderid) > 0) {
			$result = $order->delete($user);
			if ($result > 0) {
				print '<p class="ok">Deleted Supplier Order ID: '.$orderid.'</p>';
				$deleted_count++;
			} else {
				print '<p class="warning">Warning: Failed to delete order ID: '.$orderid.' - '.dol_escape_htmltag($order->error).'</p>';
			}
		} else {
			print '<p class="warning">Order ID: '.$orderid.' not found</p>';
		}
	}

	// Delete supplier
	if (!empty($socid)) {
		$soc = new Societe($db);
		if ($soc->fetch($socid) > 0) {
			$result = $soc->delete($soc->id, $user);
			if ($result > 0) {
				print '<p class="ok">Deleted Supplier ID: '.$socid.'</p>';
				$deleted_count++;
			} else {
				print '<p class="warning">Warning: Failed to delete supplier ID: '.$socid.' - '.dol_escape_htmltag($soc->error).'</p>';
			}
		} else {
			print '<p class="warning">Supplier ID: '.$socid.' not found</p>';
		}
	}

	// Clear session
	unset($_SESSION['test_shipping_socid']);
	unset($_SESSION['test_shipping_orderid']);
	unset($_SESSION['test_shipping_invoiceid']);
	unset($_SESSION['test_shipping_expeditionid']);
	unset($_SESSION['test_shipping_receptionid']);
	unset($_SESSION['test_shipping_prefix']);

	print '<br>';
	if ($deleted_count > 0) {
		print '<p class="ok">Test data deleted successfully ('.$deleted_count.' items)</p>';
	} else {
		print '<p class="warning">No test data found to delete</p>';
	}
} else {
	// Show test options
	print '<h2>Shipping Tracking Test</h2>';
	print '<p>This test creates a supplier, supplier order, and supplier invoice with tracking information.</p>';
	print '<p>It tests the tracking functionality implemented for issue #32082.</p>';
	print '<br>';

	// Check if test data already exists
	$has_test_data = false;
	if (isset($_SESSION['test_shipping_orderid']) && !empty($_SESSION['test_shipping_orderid'])) {
		$orderid = $_SESSION['test_shipping_orderid'];
		$order = new CommandeFournisseur($db);
		if ($order->fetch($orderid) > 0) {
			$has_test_data = true;
			print '<div class="ok">Test data already exists:</div>';
			print '<p><a href="fourn/commande/card.php?id='.$orderid.'" class="butAction" target="_blank">View Supplier Order</a></p>';
			if (isset($_SESSION['test_shipping_invoiceid']) && !empty($_SESSION['test_shipping_invoiceid'])) {
				$invoiceid = $_SESSION['test_shipping_invoiceid'];
				print '<p><a href="fourn/facture/card.php?id='.$invoiceid.'" class="butAction" target="_blank">View Supplier Invoice</a></p>';
			}
		}
	}

	if (!$has_test_data) {
		print '<p><a href="?action=create&token='.newToken().'" class="butAction">Create Test Data</a></p>';
	}

	print '<p><a href="?action=teardown&token='.newToken().'" class="butAction" onclick="return confirm(\'Are you sure you want to delete all test data?\');">Delete Test Data</a></p>';

	// Test shipping handler functions
	print '<h3>Shipping Handler Function Tests</h3>';

	// Test get_available_shipping_methods
	$order = new CommandeFournisseur($db);
	$carriers = get_available_shipping_methods($order);
	print '<p><strong>Available ShippingMethods:</strong> ';
	if (is_array($carriers) && !empty($carriers)) {
		$carrier_names = array();
		foreach ($carriers as $code => $carrier) {
			$carrier_names[] = $carrier['name'].' ('.$carrier['code'].')';
		}
		print implode(', ', $carrier_names);
	} else {
		print 'None found';
	}
	print '</p>';

	// Test generate_tracking_link
	$test_awb = '123456789012';
	$test_carrier = 'FX';
	$link = generate_tracking_link($test_awb, $test_carrier);
	print '<p><strong>Tracking Link Test:</strong> ';
	if ($link) {
		print '<a href="'.dol_escape_htmltag($link).'" target="_blank">'.dol_escape_htmltag($link).'</a>';
	} else {
		print 'Failed to generate link';
	}
	print '</p>';
}

print '</div>';
print '<div class="fichehalfright">';
print '<h3>Instructions</h3>';
print '<ol>';
print '<li>Click "Create Test Data" to create test data including:</li>';
print '<ul>';
print '<li>Supplier with tracking info</li>';
print '<li>Supplier order with tracking info (extrafields)</li>';
print '<li>Supplier invoice with tracking info (extrafields)</li>';
print '<li>Expedition (shipment) with tracking number</li>';
print '<li>Reception with tracking number</li>';
print '</ul>';
print '<li>View the pages to verify tracking displays correctly</li>';
print '<li>Try editing the tracking information</li>';
print '<li>Verify that tracking syncs from order to invoice (if linked)</li>';
print '<li>Verify expedition and reception tracking URLs are generated</li>';
print '<li>Click "Delete Test Data" to clean up when done</li>';
print '</ol>';
print '</div>';
print '</div>';

llxFooter();
