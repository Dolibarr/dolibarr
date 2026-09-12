<?php
/* Copyright (C) 2018 - Dolibarr Contributed Code */
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com> */
/**
 * @family shipping_logic
 * @brief Handles reusable logic for carrier selection, AWB generation, and tracking link formatting across various Dolibarr modules.
 * This reuses the existing expedition module's shipping method logic.
 */

/**
 * Fetches available carriers/shipping services from the database.
 * Reuses the logic from Expedition::list_delivery_methods().
 *
 * @param CommonObject $object The core object
 * @return array<array{rowid:int,code:string,name:string,libelle:string,description:string,tracking:string,active:int}> List of available carrier arrays with code, name, tracking URL template.
 */
function get_available_shipping_methods($object)
{
	global $db, $langs;

	$carriers = array();

	// Query active shipping methods from the database (same as Expedition::list_delivery_methods)
	$sql = "SELECT em.rowid, em.code, em.libelle as label, em.description, em.tracking, em.active";
	$sql .= " FROM " . $db->prefix() . "c_shipment_mode as em";
	$sql .= " WHERE em.active = 1";
	$sql .= " ORDER BY em.libelle ASC";

	$resql = $db->query($sql);

	if ($resql) {
		$i = 0;
		while ($obj = $db->fetch_object($resql)) {
			$label = $langs->trans('SendingMethod' . $obj->code);
			$carriers[$obj->code] = array(
				'rowid' => (int) $obj->rowid,
				'code' => (string) $obj->code,
				'name' => (string) ($label != 'SendingMethod' . $obj->code ? $label : $obj->label),
				'libelle' => (string) $obj->label,
				'description' => (string) $obj->description,
				'tracking' => (string) $obj->tracking,
				'active' => (int) $obj->active,
			);
			$i++;
		}
		$db->free($resql);
	}

	return $carriers;
}

/**
 * Formats the final trackable link using AWB number and selected carrier details.
 * Reuses the logic from Expedition::getUrlTrackingStatus().
 *
 * @param string $awb The Air Waybill or tracking number.
 * @param string $carrierCode The code of the selected carrier.
 *
 * @return string|false The canonical tracking URL, or false if input is invalid.
 */
function generate_tracking_link($awb, $carrierCode)
{
	global $db;

	if (empty($awb) || empty($carrierCode)) {
		return false;
	}

	// Get the tracking URL template from the database (same as Expedition::getUrlTrackingStatus)
	$sql = "SELECT em.code, em.tracking";
	$sql .= " FROM " . $db->prefix() . "c_shipment_mode as em";
	$sql .= " WHERE em.code = '" . $db->escape($carrierCode) . "' AND em.active = 1 LIMIT 1";

	$resql = $db->query($sql);

	if ($resql) {
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		if ($obj && !empty($obj->tracking)) {
			// Replace {TRACKID} placeholder with the actual AWB number (same as Expedition::getUrlTrackingStatus)
			$tracking = (string) $obj->tracking;
			$url = str_replace('{TRACKID}', urlencode(trim($awb)), $tracking);
			return $url;
		}
	}

	return false;
}

/**
 * Ensures tracking extrafields are defined for a given object type.
 * Creates the extrafields if they don't already exist.
 *
 * @param string $elementtype The element type (e.g., 'commande_fournisseur', 'facture_fournisseur').
 * @return void
 */
function ensure_tracking_extrafields($elementtype)
{
	global $db;

	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($elementtype);

	// Tracking extrafields configuration
	$tracking_fields = array(
		'tracking_awb' => array(
			'label' => 'TrackingAWB',
			'type' => 'varchar',
			'size' => '64',
			'enabled' => 1
		),
		'tracking_link' => array(
			'label' => 'TrackingLink',
			'type' => 'url',
			'size' => '255',
			'enabled' => '1'
		),
		'carrier_code' => array(
			'label' => 'ShippingMethodCode',
			'type' => 'varchar',
			'size' => '16',
			'enabled' => '1'
		)
	);

	foreach ($tracking_fields as $name => $config) {
		// Check if field already exists
		if (!isset($extralabels[$name])) {
			// Naming the arguments to get help from static analysis
			$pos = 0;  // 0 = auto
			$unique = 0;
			$required = 0;
			$default_value = '0';
			$param = '';
			$alwayseditable = 0;
			$perms = '0';
			$list = '0';  // '0' = never visible
			$help = '';
			$computed = '';
			$entity = '';
			$langfile = '';
			$enabled = $config['enabled'];

			$result = $extrafields->addExtraField(
				$name,
				$config['label'],
				$config['type'],
				$pos,  // pos (0 = auto)
				$config['size'],
				$elementtype,
				$unique,  // unique
				$required,  // required
				$default_value, // default_value
				$param, // param
				$alwayseditable,
				$perms,  // perms
				$list, // list ('0' = never visible)
				$help,
				$computed,
				$entity,
				$langfile,
				$enabled
			);
		}
	}
}

/**
 * Helper function to set shipping related data (ShippingMethod, Tracking Number, Link)
 * on a target object using extrafields.
 *
 * @param object $object The module object instance to update.
 * @param array{carrier_code:string,awb:string,tracking_link?:string} $data Array containing 'carrier_code', 'awb', and optionally 'tracking_link'.
 * @return bool True if successful, false otherwise.
 */
function set_shipping_data($object, $data)
{
	global $db;

	// Validate required fields
	if (!isset($data['carrier_code']) || !isset($data['awb'])) {
		return false;
	}

	// Ensure extrafields are defined
	ensure_tracking_extrafields($object->table_element);

	// Set the tracking data in array_options (extrafields)
	// These field names match what's used in the UI: options_tracking_awb, options_tracking_link, options_carrier_code
	$object->array_options['options_tracking_awb'] = $data['awb'];
	$object->array_options['options_carrier_code'] = $data['carrier_code'];

	// Generate tracking link if not provided
	if (empty($data['tracking_link'])) {
		$object->array_options['options_tracking_link'] = generate_tracking_link($data['awb'], $data['carrier_code']);
	} else {
		$object->array_options['options_tracking_link'] = $data['tracking_link'];
	}

	return true;
}
