<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/modulebuilder/test/phpunit/NamingContractTest.php
 * \ingroup modulebuilder
 * \brief   Unit tests for NamingContract and StrictNamingContractValidator.
 */

require_once dirname(__FILE__) . '/../../class/NamingContract.class.php';
require_once dirname(__FILE__) . '/../../class/NamingContractValidator.class.php';

/**
 * @backupGlobals disabled
 */
class NamingContractTest extends \PHPUnit\Framework\TestCase
{
	// ── NamingContract — properties ───────────────────────────────────────

	public function testNormalizesModuleFromLowercase(): void
	{
		$nc = new NamingContract('invoice');
		$this->assertSame('Invoice', $nc->moduleNameCase);
		$this->assertSame('invoice', $nc->moduleNameLower);
		$this->assertSame('INVOICE', $nc->moduleNameUpper);
	}

	public function testNormalizesModuleFromPascalCase(): void
	{
		$nc = new NamingContract('Invoice');
		$this->assertSame('Invoice', $nc->moduleNameCase);
		$this->assertSame('invoice', $nc->moduleNameLower);
		$this->assertSame('INVOICE', $nc->moduleNameUpper);
	}

	public function testNormalizesObjectFromLowercase(): void
	{
		$nc = new NamingContract('invoice', 'request');
		$this->assertSame('Request', $nc->objectNameCase);
		$this->assertSame('request', $nc->objectNameLower);
		$this->assertSame('REQUEST', $nc->objectNameUpper);
	}

	public function testPreservesCompoundPascalCaseObject(): void
	{
		$nc = new NamingContract('Invoice', 'InvoiceRequest');
		$this->assertSame('InvoiceRequest', $nc->objectNameCase);
		$this->assertSame('invoicerequest', $nc->objectNameLower);
		$this->assertSame('INVOICEREQUEST', $nc->objectNameUpper);
	}

	// ── NamingContract — map ──────────────────────────────────────────────

	public function testSubstitutionMapHasTwelveKeysWithObject(): void
	{
		$nc = new NamingContract('mymodule', 'myobject');
		$this->assertCount(12, $nc->getSubstitutionMap());
	}

	public function testSubstitutionMapHasSevenKeysModuleOnly(): void
	{
		$nc = new NamingContract('mymodule');
		$this->assertCount(7, $nc->getSubstitutionMap());
	}

	public function testSubstitutionMapOrderUpperBeforeLower(): void
	{
		$nc   = new NamingContract('mymodule', 'myobject');
		$keys = array_keys($nc->getSubstitutionMap());

		$posUpper = array_search('MYOBJECT', $keys, true);
		$posCase  = array_search('MyObject', $keys, true);
		$posLower = array_search('myobject', $keys, true);

		$this->assertIsInt($posUpper);
		$this->assertIsInt($posCase);
		$this->assertIsInt($posLower);
		$this->assertLessThan($posCase, $posUpper, 'MYOBJECT must precede MyObject');
		$this->assertLessThan($posLower, $posCase, 'MyObject must precede myobject');
	}

	// ── NamingContract — applyTo ──────────────────────────────────────────

	public function testApplyToReplacesAllTwelveVariants(): void
	{
		$nc       = new NamingContract('invoice', 'request');
		$template = 'MYMODULE MyModule My module my module Mon module mon module mymodule'
			. ' MYOBJECT MyObject My Object my object myobject';
		$result   = $nc->applyTo($template);

		$this->assertStringNotContainsStringIgnoringCase('mymodule', $result);
		$this->assertStringNotContainsStringIgnoringCase('myobject', $result);
		$this->assertStringContainsString('INVOICE', $result);
		$this->assertStringContainsString('Invoice', $result);
		$this->assertStringContainsString('invoice', $result);
		$this->assertStringContainsString('REQUEST', $result);
		$this->assertStringContainsString('Request', $result);
		$this->assertStringContainsString('request', $result);
	}

	public function testApplyToPreservesModulebuilderPermissionsMarker(): void
	{
		$nc      = new NamingContract('invoice', 'request');
		$content = '/* BEGIN MODULEBUILDER PERMISSIONS */ code /* END MODULEBUILDER PERMISSIONS */';
		$result  = $nc->applyTo($content);
		$this->assertStringContainsString('/* BEGIN MODULEBUILDER PERMISSIONS */', $result);
		$this->assertStringContainsString('/* END MODULEBUILDER PERMISSIONS */', $result);
	}

	public function testApplyToDoesNotAlterUntokenizedContent(): void
	{
		$nc      = new NamingContract('invoice', 'request');
		$content = 'class CommonObject extends DolibarrObject {}';
		$this->assertSame($content, $nc->applyTo($content));
	}

	// ── NamingContract — applyToFilename ─────────────────────────────────

	public function testApplyToFilenameReplacesLowercaseTokens(): void
	{
		$nc = new NamingContract('invoice', 'request');
		$this->assertSame('invoice_request.lib.php',    $nc->applyToFilename('mymodule_myobject.lib.php'));
		$this->assertSame('llx_invoice_request.sql',    $nc->applyToFilename('llx_mymodule_myobject.sql'));
		$this->assertSame('admin/request_extrafields.php', $nc->applyToFilename('admin/myobject_extrafields.php'));
	}

	public function testApplyToFilenameModuleOnlyIgnoresObjectToken(): void
	{
		$nc = new NamingContract('invoice');
		$this->assertSame('invoice_myobject.lib.php', $nc->applyToFilename('mymodule_myobject.lib.php'));
	}

	// ── NamingContract — module-only contract ────────────────────────────

	public function testModuleOnlyContractHasEmptyObjectProps(): void
	{
		$nc = new NamingContract('invoice');
		$this->assertSame('', $nc->objectNameCase);
		$this->assertSame('', $nc->objectNameLower);
		$this->assertSame('', $nc->objectNameUpper);
	}

	// ── NamingContract — guard ────────────────────────────────────────────

	public function testCollisionGuardThrowsOnIdenticalNames(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new NamingContract('invoice', 'invoice');
	}

	public function testCollisionGuardIsCaseInsensitive(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new NamingContract('Invoice', 'INVOICE');
	}

	// ── StrictNamingContractValidator — validateContent ──────────────────

	public function testValidatorCatchesResidualMyobjectLowercase(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent('$obj = new myobject($db);', 'test.php');
		$this->assertNotEmpty($errors);
		$this->assertStringContainsString('myobject', $errors[0]);
	}

	public function testValidatorCatchesResidualMyobjectMixedCase(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent('class MyObject extends CommonObject', 'test.php');
		$this->assertNotEmpty($errors);
	}

	public function testValidatorCatchesResidualMymodule(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent("isModEnabled('mymodule')", 'test.php');
		$this->assertNotEmpty($errors);
		$this->assertStringContainsString('mymodule', $errors[0]);
	}

	public function testValidatorAcceptsNonRenamableBeginMarker(): void
	{
		$validator = new StrictNamingContractValidator();
		$content   = "/* BEGIN MODULEBUILDER API MYOBJECT */\n\t/* END MODULEBUILDER API MYOBJECT */";
		$errors    = $validator->validateContent($content, 'test.php');
		$this->assertEmpty($errors);
	}

	public function testValidatorAcceptsCleanContent(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent('$obj = new Invoice($db);', 'test.php');
		$this->assertEmpty($errors);
	}

	// ── StrictNamingContractValidator — other methods ────────────────────

	public function testValidatorValidatesMatchingTriggerFilename(): void
	{
		$nc        = new NamingContract('Invoice');
		$validator = new StrictNamingContractValidator();
		$this->assertTrue(
			$validator->validateTriggerFilename('interface_99_modInvoice_InvoiceTriggers.class.php', $nc)
		);
	}

	public function testValidatorRejectsResidualTriggerFilename(): void
	{
		$nc        = new NamingContract('Invoice');
		$validator = new StrictNamingContractValidator();
		$this->assertFalse(
			$validator->validateTriggerFilename('interface_99_modMyModule_MyModuleTriggers.class.php', $nc)
		);
	}

	public function testValidatorValidatesMatchingClassName(): void
	{
		$nc        = new NamingContract('Invoice', 'Request');
		$validator = new StrictNamingContractValidator();
		$this->assertTrue($validator->validateClassName('Request', $nc));
		$this->assertFalse($validator->validateClassName('MyObject', $nc));
	}

	public function testValidatorValidatesRightsKey(): void
	{
		$nc        = new NamingContract('invoice', 'request');
		$validator = new StrictNamingContractValidator();
		$this->assertTrue($validator->validateRightsKey('invoice.request.read', $nc));
		$this->assertFalse($validator->validateRightsKey('mymodule.myobject.read', $nc));
	}
}
