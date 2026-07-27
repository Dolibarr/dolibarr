<?php
/* Copyright (C) 2026 Gedeon Tshimanga
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
 *	\file		test/phpunit/CloneAgendaEventTest.php
 *	\ingroup	test
 *	\brief		PHPUnit test for automatic agenda events created on clone
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Test automatic agenda events created when customer documents are cloned.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class CloneAgendaEventTest extends CommonClassTest
{
	/**
	 * Provide customer document classes and their creation trigger.
	 *
	 * @return array<int,array{string,string,string,bool}>
	 */
	public static function cloneEventProvider()
	{
		return array(
			array('Propal', 'PROPAL_CREATE', 'propal', false),
			array('Commande', 'ORDER_CREATE', 'order', false),
			array('Facture', 'BILL_CREATE', 'invoice', false),
			array('Propal', 'PROPAL_CREATE', 'propal', true),
			array('Commande', 'ORDER_CREATE', 'order', true),
			array('Facture', 'BILL_CREATE', 'invoice', true),
		);
	}

	/**
	 * Check that create and clone agenda events follow the automatic action configuration.
	 *
	 * @param	string	$className		Document class
	 * @param	string	$triggerCode	Creation trigger code
	 * @param	string	$elementType	Element type
	 * @param	bool	$trackingEnabled	Whether automatic tracking is enabled
	 * @return	void
	 *
	 * @dataProvider cloneEventProvider
	 */
	public function testCloneAgendaEventFollowsConfiguration($className, $triggerCode, $elementType, $trackingEnabled)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$constantName = 'MAIN_AGENDA_ACTIONAUTO_'.$triggerCode;
		$constantWasSet = isset($conf->global->$constantName);
		$constantValue = $constantWasSet ? $conf->global->$constantName : null;
		$conf->global->$constantName = $trackingEnabled ? 1 : 0;

		$lastActionCreatedWasSet = isset($_SESSION['LAST_ACTION_CREATED']);
		$lastActionCreated = $lastActionCreatedWasSet ? $_SESSION['LAST_ACTION_CREATED'] : null;

		try {
			$source = new $className($db);
			if ($source instanceof Facture) {
				$source->initAsSpecimen('nolines');
			} else {
				$source->initAsSpecimen();
				$source->lines = array();
			}
			$source->ref = uniqid('PHPUNIT-');
			$sourceId = $source->create($user);
			$this->assertGreaterThan(0, $sourceId, $source->errorsToString());
			$this->assertGreaterThan(0, $source->fetch($sourceId), $source->errorsToString());
			$sourceRef = $source->ref;

			if ($source instanceof Propal) {
				// createFromClone() only requires the source id, so it must not rely on a preloaded caller reference.
				$source->ref = '';
				$cloneId = $source->createFromClone($user, $source->socid);
			} elseif ($source instanceof Facture) {
				$cloneId = $source->createFromClone($user, $sourceId);
			} else {
				$cloneId = $source->createFromClone($user, $source->socid);
			}
			$this->assertGreaterThan(0, $cloneId, $source->errorsToString());

			$clone = new $className($db);
			$this->assertGreaterThan(0, $clone->fetch($cloneId), $clone->errorsToString());
			$cloneRef = $clone->ref;
			$this->assertNotSame($sourceRef, $cloneRef);

			$sql = "SELECT label, note AS note_private, fk_element";
			$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE code = 'AC_".$db->escape($triggerCode)."'";
			$sql .= " AND fk_element IN (".((int) $sourceId).", ".((int) $cloneId).")";
			$sql .= " AND elementtype = '".$db->escape($elementType)."'";
			$resql = $db->query($sql);
			$this->assertNotFalse($resql, (string) $db->lasterror());

			$events = array();
			$eventsByElement = array();
			while ($event = $db->fetch_object($resql)) {
				$events[] = $event;
				$eventsByElement[(int) $event->fk_element] = $event;
			}
			$this->assertCount($trackingEnabled ? 2 : 0, $events);

			if ($trackingEnabled) {
				$this->assertArrayHasKey($sourceId, $eventsByElement);
				$this->assertArrayHasKey($cloneId, $eventsByElement);

				$langs->loadLangs(array('agenda', 'main'));
				$cloneEvent = $eventsByElement[$cloneId];
				$this->assertSame($langs->transnoentities('CREATEInDolibarr', $cloneRef), $cloneEvent->label);

				$translationKeys = array(
					'PROPAL_CREATE' => 'ProposalClonedInDolibarr',
					'ORDER_CREATE' => 'OrderClonedInDolibarr',
					'BILL_CREATE' => 'InvoiceClonedInDolibarr',
				);
				$cloneMessage = $langs->transnoentities($translationKeys[$triggerCode], $cloneRef, $sourceRef);
				$this->assertStringContainsString($cloneMessage, $cloneEvent->note_private);
				$this->assertStringContainsString($cloneRef, $cloneEvent->note_private);
				$this->assertStringContainsString($sourceRef, $cloneEvent->note_private);
			}
		} finally {
			if ($constantWasSet) {
				$conf->global->$constantName = $constantValue;
			} else {
				unset($conf->global->$constantName);
			}

			if ($lastActionCreatedWasSet) {
				$_SESSION['LAST_ACTION_CREATED'] = $lastActionCreated;
			} else {
				unset($_SESSION['LAST_ACTION_CREATED']);
			}
		}
	}
}
