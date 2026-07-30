<?php
/* Copyright (C) 2018		Destailleur Laurent	<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frederic France		<frederic.france@free.fr>
 * Copyright (C) 2025-2026	MDW					<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/CDavLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for CdavLib class
 *      \remarks    To run this script as CLI: phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

// Define HTTP_HOST before loading dav.lib.php to avoid warning
if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

require_once dirname(__FILE__).'/../../htdocs/dav/dav.lib.php';
require_once dirname(__FILE__).'/../../htdocs/dav/dav.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

// Ensure constants are defined for tests
if (!defined('CDAV_URI_KEY')) {
	define('CDAV_URI_KEY', substr(md5($_SERVER['HTTP_HOST'] ?? 'localhost'), 0, 8));
}
if (!defined('CDAV_CONTACT_TAG')) {
	define('CDAV_CONTACT_TAG', '');
}

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests of CdavLib
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CDavLibTest extends CommonClassTest
{
	/**
	 * testCdavLibConstruct
	 *
	 * @return	void
	 */
	public function testCdavLibConstruct()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$cdavlib = new CdavLib($user, $db, $langs);

		// Check that the object has been created
		$this->assertInstanceOf(CdavLib::class, $cdavlib);

		// Check that properties are set
		$this->assertSame($user, $this->getProtectedProperty($cdavlib, 'user'));
		$this->assertSame($db, $this->getProtectedProperty($cdavlib, 'db'));
		$this->assertSame($langs, $this->getProtectedProperty($cdavlib, 'langs'));

		print __METHOD__." OK\n";
	}

	/**
	 * testGetSqlCalEvents
	 *
	 * @return	void
	 */
	public function testGetSqlCalEvents()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$cdavlib = new CdavLib($user, $db, $langs);

		// Test with a valid user ID
		$sql = $cdavlib->getSqlCalEvents($user->id);

		// Check that the SQL is not empty
		$this->assertNotEmpty($sql);

		// Check that the SQL is a string
		$this->assertIsString($sql);

		// Check that the SQL contains expected tables
		$this->assertStringContainsString('actioncomm', $sql);
		$this->assertStringContainsString('c_actioncomm', $sql);

		// Test with OID parameter
		$sqlWithOid = $cdavlib->getSqlCalEvents($user->id, 1);
		$this->assertNotEmpty($sqlWithOid);
		$this->assertStringContainsString('a.id = 1', $sqlWithOid);

		// Test with OID and OURI parameters
		// Note: When $ouri is provided, it should match against a.recurid
		$sqlWithOuri = $cdavlib->getSqlCalEvents($user->id, 1, 'test-ouri-123');
		$this->assertNotEmpty($sqlWithOuri);
		// The OID should be in the SQL
		$this->assertStringContainsString('a.id = 1', $sqlWithOuri);
		// The OURI should be matched against a.recurid
		$this->assertStringContainsString('a.recurid = ', $sqlWithOuri);
		$this->assertStringContainsString("'test-ouri-123'", $sqlWithOuri);

		// Test with OURI parameter only (no OID)
		$sqlWithOnlyOuri = $cdavlib->getSqlCalEvents($user->id, false, 'test-ouri-456');
		$this->assertNotEmpty($sqlWithOnlyOuri);
		// Should only have the recurid condition, not the id condition
		$this->assertStringContainsString('a.recurid = ', $sqlWithOnlyOuri);
		$this->assertStringContainsString("'test-ouri-456'", $sqlWithOnlyOuri);
		$this->assertStringNotContainsString('a.id = ', $sqlWithOnlyOuri);

		print __METHOD__." OK\n";
	}

	/**
	 * testToVCalendar
	 *
	 * @return	void
	 */
	public function testToVCalendar()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$cdavlib = new CdavLib($user, $db, $langs);

		// Test 1: Create a VTODO (percent = 0)
		$objTodo = new stdClass();
		$objTodo->id = 1;
		$objTodo->datec = '2024-01-01 10:00:00';
		$objTodo->lastupd = '2024-01-01 10:00:00';
		$objTodo->datep = '2024-01-15 10:00:00';
		$objTodo->datep2 = '2024-01-15 11:00:00';
		$objTodo->label = 'Test Task';
		$objTodo->location = 'Test Location';
		$objTodo->priority = 1;
		$objTodo->fulldayevent = 0;
		$objTodo->percent = 0;
		$objTodo->transparency = 0;
		$objTodo->note = 'Test note for task';
		$objTodo->sourceuid = '';
		$objTodo->address = '';
		$objTodo->zip = '';
		$objTodo->town = '';
		$objTodo->country_label = '';
		$objTodo->soc_nom = '';
		$objTodo->soc_address = '';
		$objTodo->soc_zip = '';
		$objTodo->soc_town = '';
		$objTodo->soc_country_label = '';
		$objTodo->soc_phone = '';
		$objTodo->firstname = '';
		$objTodo->lastname = '';
		$objTodo->phone = '';
		$objTodo->phone_perso = '';
		$objTodo->phone_mobile = '';
		$objTodo->other_users = '';

		$vcalTodo = $cdavlib->toVCalendar($user->id, $objTodo);

		// Check that the output is valid VCalendar with VTODO
		$this->assertNotEmpty($vcalTodo);
		$this->assertIsString($vcalTodo);
		$this->assertStringContainsString('BEGIN:VCALENDAR', $vcalTodo);
		$this->assertStringContainsString('END:VCALENDAR', $vcalTodo);
		$this->assertStringContainsString('BEGIN:VTODO', $vcalTodo);
		$this->assertStringContainsString('END:VTODO', $vcalTodo);
		$this->assertStringContainsString('SUMMARY:Test Task', $vcalTodo);
		$this->assertStringContainsString('LOCATION:Test Location', $vcalTodo);
		$this->assertStringContainsString('STATUS:NEEDS-ACTION', $vcalTodo);

		// Test 2: Create a VEVENT (percent = -1 and datep is set)
		$objEvent = new stdClass();
		$objEvent->id = 2;
		$objEvent->datec = '2024-02-01 10:00:00';
		$objEvent->lastupd = '2024-02-01 10:00:00';
		$objEvent->datep = '2024-02-15 14:00:00';
		$objEvent->datep2 = '2024-02-15 16:00:00';
		$objEvent->label = 'Test Meeting';
		$objEvent->location = 'Conference Room';
		$objEvent->priority = 2;
		$objEvent->fulldayevent = 0;
		$objEvent->percent = -1;  // This creates a VEVENT
		$objEvent->transparency = 1;
		$objEvent->note = 'Test note for meeting';
		$objEvent->sourceuid = '';
		$objEvent->address = '';
		$objEvent->zip = '';
		$objEvent->town = '';
		$objEvent->country_label = '';
		$objEvent->soc_nom = '';
		$objEvent->soc_address = '';
		$objEvent->soc_zip = '';
		$objEvent->soc_town = '';
		$objEvent->soc_country_label = '';
		$objEvent->soc_phone = '';
		$objEvent->firstname = '';
		$objEvent->lastname = '';
		$objEvent->phone = '';
		$objEvent->phone_perso = '';
		$objEvent->phone_mobile = '';
		$objEvent->other_users = '';

		$vcalEvent = $cdavlib->toVCalendar($user->id, $objEvent);

		// Check that the output is valid VCalendar with VEVENT
		$this->assertNotEmpty($vcalEvent);
		$this->assertIsString($vcalEvent);
		$this->assertStringContainsString('BEGIN:VCALENDAR', $vcalEvent);
		$this->assertStringContainsString('END:VCALENDAR', $vcalEvent);
		$this->assertStringContainsString('BEGIN:VEVENT', $vcalEvent);
		$this->assertStringContainsString('END:VEVENT', $vcalEvent);
		$this->assertStringContainsString('SUMMARY:Test Meeting', $vcalEvent);
		$this->assertStringContainsString('LOCATION:Conference Room', $vcalEvent);
		$this->assertStringContainsString('STATUS:CONFIRMED', $vcalEvent);
		$this->assertStringContainsString('TRANSP:TRANSPARENT', $vcalEvent);

		print __METHOD__." OK\n";
	}

	/**
	 * testGetFullCalendarObjects
	 *
	 * @return	void
	 */
	public function testGetFullCalendarObjects()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$cdavlib = new CdavLib($user, $db, $langs);

		// Start a transaction for database operations
		$db->begin();

		try {
			// Check if user has permission to read agenda
			if (empty($user->rights->agenda->myactions->read)) {
				$this->markTestSkipped('User does not have permission to read agenda (myactions->read)');
			}

			// Insert a test actioncomm (calendar event) for the current user
			// First get a valid action code
			$result = $db->query("SELECT code FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE type <> 'systemauto' LIMIT 1");
			$codeObj = $db->fetch_object($result);
			$actionCode = $codeObj->code;

			$now = dol_now();
			// Generate a unique ref for the action - use a simple format that won't conflict
			$ref = 'AC-TEST-' . $now;

			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "actioncomm (ref, fk_user_author, datep, datep2, label, note, fk_user_action, percent, code, entity, datec)";
			$sql .= " VALUES ('" . $db->escape($ref) . "', " . ((int) $user->id) . ", '" . $db->idate($now + 86400) . "', '" . $db->idate($now + 172800) . "', 'Test Calendar Event', 'Test note', " . ((int) $user->id) . ", 0, '" . $db->escape($actionCode) . "', 1, '" . $db->idate($now) . "')";
			$db->query($sql);
			$actionId = $db->last_insert_id(MAIN_DB_PREFIX . "actioncomm");

			// Insert into actioncomm_resources to link the event to the user's calendar
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "actioncomm_resources (fk_actioncomm, element_type, fk_element)";
			$sql .= " VALUES (" . ((int) $actionId) . ", 'user', " . ((int) $user->id) . ")";
			$db->query($sql);

			// Test with calendar ID of current user (should return the event we just inserted)
			$objects = $cdavlib->getFullCalendarObjects($user->id, 0);

			// Check that the result is an array
			$this->assertIsArray($objects);

			// Check that we got at least our test event
			$this->assertGreaterThanOrEqual(1, count($objects), 'Expected at least 1 calendar object, got ' . count($objects));

			// Verify the inserted action exists in the database
			$result = $db->query("SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "actioncomm WHERE id = " . ((int) $actionId));
			$countObj = $db->fetch_object($result);
			$this->assertEquals(1, (int) $countObj->cnt, 'Test action was not inserted into database');

			// If we got results, check the structure of the first object
			if (count($objects) > 0) {
				$firstObject = $objects[0];
				$this->assertArrayHasKey('uri', $firstObject);
				$this->assertArrayHasKey('lastmodified', $firstObject);
				$this->assertArrayHasKey('etag', $firstObject);
				$this->assertArrayHasKey('calendarid', $firstObject);
				$this->assertArrayHasKey('size', $firstObject);
				$this->assertArrayHasKey('component', $firstObject);
				// component should be either 'vevent' or 'vtodo'
				$this->assertContains($firstObject['component'], ['vevent', 'vtodo']);
			}

			// Test with calendar ID and bCalendarData = 1 (includes calendar data)
			$objectsWithData = $cdavlib->getFullCalendarObjects($user->id, 1);

			// Check that the result is an array
			$this->assertIsArray($objectsWithData);

			// With calendar data, we should have the calendardata field
			if (count($objectsWithData) > 0) {
				$firstObjectWithData = $objectsWithData[0];
				$this->assertArrayHasKey('calendardata', $firstObjectWithData);
				$this->assertArrayHasKey('uri', $firstObjectWithData);
				// Verify calendardata contains expected elements
				$this->assertStringContainsString('BEGIN:VCALENDAR', $firstObjectWithData['calendardata']);
			}

			print __METHOD__." OK\n";
		} catch (Exception $e) {
			$this->fail("Exception during test: " . $e->getMessage());
		} finally {
			// Rollback the transaction to clean up test data
			$db->rollback();
		}
	}

	/**
	 * Helper method to get protected property value
	 *
	 * @param object $object     Object instance
	 * @param string $property   Property name
	 * @return mixed
	 */
	private function getProtectedProperty($object, $property)
	{
		$reflection = new ReflectionClass($object);
		$property = $reflection->getProperty($property);
		$property->setAccessible(true);
		return $property->getValue($object);
	}
}
