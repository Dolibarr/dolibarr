<?php
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com> */
/**
 * \file    test/phpunit/ShippingHandlerTest.php
 * \ingroup test
 * \brief   PHPUnit test for TrackingTrait functionality
 */

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

/**
 * Test class for TrackingTrait functionality
 * Uses CommandeFournisseur which has TrackingTrait
 * Extends CommonClassTest to get Dolibarr environment setup
 */
class ShippingHandlerTest extends CommonClassTest
{
	/**
	 * Test getTrackingInfo returns expected structure
	 * @return void
	 */
	public function testGetTrackingInfo()
	{
		global $db;

		// Create a real CommandeFournisseur object (has TrackingTrait)
		$order = new CommandeFournisseur($db);

		// Set tracking info using the trait method
		$order->setTrackingInfo('123456789012', 'FX', 'https://www.fedex.com/tracking/123456789012');

		// Get tracking info using the trait method
		$trackingInfo = $order->getTrackingInfo();

		// Verify it returns an array with expected keys
		$this->assertIsArray($trackingInfo);
		$this->assertArrayHasKey('awb', $trackingInfo);
		$this->assertArrayHasKey('carrier_code', $trackingInfo);
		$this->assertArrayHasKey('tracking_link', $trackingInfo);

		// Verify values
		$this->assertEquals('123456789012', $trackingInfo['awb']);
		$this->assertEquals('FX', $trackingInfo['carrier_code']);
		$this->assertEquals('https://www.fedex.com/tracking/123456789012', $trackingInfo['tracking_link']);
	}

	/**
	 * Test getTrackingUrl returns expected URL
	 * @return void
	 */
	public function testGetTrackingUrl()
	{
		global $db;

		$order = new CommandeFournisseur($db);
		$order->setTrackingInfo('123456789012', 'FX', 'https://www.fedex.com/tracking/123456789012');

		$url = $order->getTrackingUrl();

		$this->assertEquals('https://www.fedex.com/tracking/123456789012', $url);
	}

	/**
	 * Test getTrackingAWB returns expected AWB
	 * @return void
	 */
	public function testGetTrackingAWB()
	{
		global $db;

		$order = new CommandeFournisseur($db);
		$order->setTrackingInfo('123456789012', 'FX');

		$awb = $order->getTrackingAWB();

		$this->assertEquals('123456789012', $awb);
	}

	/**
	 * Test getShippingMethodCode returns expected carrier code
	 * @return void
	 */
	public function testGetShippingMethodCode()
	{
		global $db;

		$order = new CommandeFournisseur($db);
		$order->setTrackingInfo('123456789012', 'UPS');

		$code = $order->getShippingMethodCode();

		$this->assertEquals('UPS', $code);
	}

	/**
	 * Test setTrackingInfo with auto-generated tracking link
	 * @return void
	 */
	public function testSetTrackingInfoAutoGenerate()
	{
		global $db, $conf;

		// Insert a test carrier with tracking URL template
		$trackingUrl = 'https://autogen.com/track/{TRACKID}';
		$db->query("INSERT INTO " . MAIN_DB_PREFIX . "c_shipment_mode (code, libelle, tracking, active, entity) VALUES ('AUTO', 'Auto Carrier', '" . $db->escape($trackingUrl) . "', 1, " . ((int) $conf->entity) . ")");

		$order = new CommandeFournisseur($db);
		// Don't provide tracking_link - it should be auto-generated
		$order->setTrackingInfo('ABC123XYZ', 'AUTO');

		$trackingInfo = $order->getTrackingInfo();

		// Verify AWB and carrier code
		$this->assertEquals('ABC123XYZ', $trackingInfo['awb']);
		$this->assertEquals('AUTO', $trackingInfo['carrier_code']);

		// Verify tracking link was auto-generated (contains URL-encoded AWB)
		$this->assertNotEmpty($trackingInfo['tracking_link']);
		$this->assertStringContainsString('autogen.com', $trackingInfo['tracking_link']);
		$this->assertStringContainsString(urlencode('ABC123XYZ'), $trackingInfo['tracking_link']);
		$this->assertStringNotContainsString('{TRACKID}', $trackingInfo['tracking_link']);

		// Clean up
		$db->query("DELETE FROM " . MAIN_DB_PREFIX . "c_shipment_mode WHERE code = 'AUTO'");
	}

	/**
	 * Test setTrackingInfo with empty values
	 * @return void
	 */
	public function testSetTrackingInfoEmptyValues()
	{
		global $db;

		$order = new CommandeFournisseur($db);

		// setTrackingInfo just sets values, doesn't validate
		$result = $order->setTrackingInfo('', '');
		$this->assertTrue($result);

		// But the getters should return empty for missing values
		$awb = $order->getTrackingAWB();
		$this->assertEmpty($awb);

		$code = $order->getShippingMethodCode();
		$this->assertEmpty($code);
	}

	/**
	 * Test tracking link generation URL-encodes special characters
	 * @return void
	 */
	public function testTrackingLinkUrlEncoding()
	{
		global $db, $conf;

		// Insert test carrier
		$trackingUrl = 'https://test-carrier.com/track/{TRACKID}';
		$db->query("INSERT INTO " . MAIN_DB_PREFIX . "c_shipment_mode (code, libelle, tracking, active, entity) VALUES ('TESTENC', 'Test Encoding', '" . $db->escape($trackingUrl) . "', 1, " . ((int) $conf->entity) . ")");

		$order = new CommandeFournisseur($db);
		$order->setTrackingInfo('123-456&789', 'TESTENC');

		$trackingInfo = $order->getTrackingInfo();

		// The AWB should be URL-encoded in the tracking link
		$this->assertStringContainsString(urlencode('123-456&789'), $trackingInfo['tracking_link']);

		// Clean up
		$db->query("DELETE FROM " . MAIN_DB_PREFIX . "c_shipment_mode WHERE code = 'TESTENC'");
	}

	/**
	 * Test tracking with whitespace in AWB
	 * @return void
	 */
	public function testTrackingWithWhitespace()
	{
		global $db, $conf;

		// Insert test carrier
		$trackingUrl = 'https://test-carrier.com/track/{TRACKID}';
		$db->query("INSERT INTO " . MAIN_DB_PREFIX . "c_shipment_mode (code, libelle, tracking, active, entity) VALUES ('TESTUP', 'Test UPS', '" . $db->escape($trackingUrl) . "', 1, " . ((int) $conf->entity) . ")");

		$order = new CommandeFournisseur($db);
		$order->setTrackingInfo(' 123456789012 ', 'TESTUP');

		$trackingInfo = $order->getTrackingInfo();

		// AWB should be trimmed in the tracking link
		$this->assertStringContainsString(urlencode('123456789012'), $trackingInfo['tracking_link']);
		// Should not contain spaces in the URL
		$this->assertStringNotContainsString(' ', $trackingInfo['tracking_link']);

		// Clean up
		$db->query("DELETE FROM " . MAIN_DB_PREFIX . "c_shipment_mode WHERE code = 'TESTUP'");
	}

	/**
	 * Test tracking link generation with inactive carrier
	 * @return void
	 */
	public function testTrackingLinkInactiveCarrier()
	{
		global $db, $conf;

		// Insert inactive shipping method
		$trackingUrl = 'https://inactive.com/{TRACKID}';
		$db->query("INSERT INTO " . MAIN_DB_PREFIX . "c_shipment_mode (code, libelle, tracking, active, entity) VALUES ('INACTIVE', 'Inactive Carrier', '" . $db->escape($trackingUrl) . "', 0, " . ((int) $conf->entity) . ")");

		$order = new CommandeFournisseur($db);
		$order->setTrackingInfo('123456', 'INACTIVE');

		$trackingInfo = $order->getTrackingInfo();

		// For inactive carrier, generate_tracking_link returns false, so tracking_link should be empty
		$this->assertEmpty($trackingInfo['tracking_link']);

		// Clean up
		$db->query("DELETE FROM " . MAIN_DB_PREFIX . "c_shipment_mode WHERE code = 'INACTIVE'");
	}
}
