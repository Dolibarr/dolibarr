<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    test/phpunit/DateSentWriterTest.php
 * \brief   PHPUnit tests for DateSentWriter
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
    print "Load permissions for admin user nb 1\n";
    $user->fetch(1);
    $user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class DateSentWriterTest extends CommonClassTest
{
    public function testTableMapCompleteness(): void
    {
        global $db;
        require_once DOL_DOCUMENT_ROOT.'/core/class/datesentwriter.class.php';

        $writer = new DateSentWriter($db);
        $reflection = new ReflectionClass($writer);
        $map = $reflection->getConstant('TABLE_MAP');

        $this->assertIsArray($map);
        $this->assertCount(12, $map);

        foreach (['propal', 'commande', 'facture', 'supplier_proposal',
                  'order_supplier', 'invoice_supplier', 'contrat',
                  'expedition', 'delivery', 'reception', 'fichinter', 'project'] as $key) {
            $this->assertArrayHasKey($key, $map, "Missing key: $key");
        }
        print __METHOD__." ok\n";
    }

    public function testWriteWithNoId(): void
    {
        global $db;
        require_once DOL_DOCUMENT_ROOT.'/core/class/datesentwriter.class.php';

        $propal = new Propal($db);
        $propal->id = 0;

        $writer = new DateSentWriter($db);
        $result = $writer->write($propal, dol_now());

        $this->assertEquals(-1, $result);
        print __METHOD__." result=".$result."\n";
    }

    public function testWriteWithUnsupportedElement(): void
    {
        global $db;
        require_once DOL_DOCUMENT_ROOT.'/core/class/datesentwriter.class.php';

        // Societe::$element = 'societe' — not in TABLE_MAP
        $soc = new Societe($db);
        $soc->id = 1;

        $writer = new DateSentWriter($db);
        $result = $writer->write($soc, dol_now());

        $this->assertEquals(0, $result);
        print __METHOD__." result=".$result."\n";
    }

    /**
     * @return int Propal id for next test
     */
    public function testCreatePropalForTest(): int
    {
        global $conf, $user, $langs, $db;
        $conf = $this->savconf;
        $user = $this->savuser;
        $langs = $this->savlangs;
        $db = $this->savdb;

        $propal = new Propal($db);
        $propal->initAsSpecimen(array('tosell' => 1));
        $result = $propal->create($user);

        $this->assertGreaterThan(0, $result, 'Propal creation failed');
        print __METHOD__." result=".$result."\n";
        return $result;
    }

    /**
     * @depends testCreatePropalForTest
     */
    public function testWriteSuccess(int $propalId): int
    {
        global $db;
        require_once DOL_DOCUMENT_ROOT.'/core/class/datesentwriter.class.php';

        $propal = new Propal($db);
        $propal->fetch($propalId);

        $writer = new DateSentWriter($db);
        $when = dol_now();
        $result = $writer->write($propal, $when);

        $this->assertEquals(1, $result);
        $this->assertEquals($when, $propal->date_sent);
        print __METHOD__." result=".$result."\n";
        return $propalId;
    }

    /**
     * @depends testWriteSuccess
     */
    public function testWriteOverwrites(int $propalId): int
    {
        global $db;
        require_once DOL_DOCUMENT_ROOT.'/core/class/datesentwriter.class.php';

        $propal = new Propal($db);
        $propal->fetch($propalId);

        $newDate = dol_now() + 3600; // simulate a later re-send

        $writer = new DateSentWriter($db);
        $result = $writer->write($propal, $newDate);

        $this->assertEquals(1, $result);
        $this->assertEquals($newDate, $propal->date_sent);

        // Verify persistence via re-fetch (requires Task 4 Propal class modification to pass fully)
        $propal2 = new Propal($db);
        $propal2->fetch($propalId);
        $this->assertEquals($newDate, $propal2->date_sent);

        print __METHOD__." result=".$result."\n";
        return $propalId;
    }

    /**
     * @depends testWriteOverwrites
     */
    public function testDeletePropalUsedForTest(int $propalId): void
    {
        global $user, $db;

        $propal = new Propal($db);
        $propal->fetch($propalId);
        $result = $propal->delete($user);

        $this->assertGreaterThanOrEqual(1, $result);
        print __METHOD__." result=".$result."\n";
    }
}
