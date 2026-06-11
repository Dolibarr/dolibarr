<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    test/phpunit/NamingContractTest.php
 * \ingroup modulebuilder
 * \brief   Unit tests for NamingContract and StrictNamingContractValidator.
 */

require_once dirname(__FILE__) . '/../../htdocs/modulebuilder/class/NamingContract.class.php';
require_once dirname(__FILE__) . '/../../htdocs/modulebuilder/class/NamingContractValidator.class.php';

/**
 * @backupGlobals disabled
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanNoopNew
 */
/** @phpstan-ignore class.notFound */
class NamingContractTest extends \PHPUnit\Framework\TestCase
{
	// ── NamingContract — properties ───────────────────────────────────────

	/**
	 * @return void
	 */
	public function testNormalizesModuleFromLowercase(): void
	{
		$nc = new NamingContract('invoice');
		$this->assertSame('Invoice', $nc->moduleNameCase); // @phpstan-ignore method.notFound
		$this->assertSame('invoice', $nc->moduleNameLower); // @phpstan-ignore method.notFound
		$this->assertSame('INVOICE', $nc->moduleNameUpper); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testNormalizesModuleFromPascalCase(): void
	{
		$nc = new NamingContract('Invoice');
		$this->assertSame('Invoice', $nc->moduleNameCase); // @phpstan-ignore method.notFound
		$this->assertSame('invoice', $nc->moduleNameLower); // @phpstan-ignore method.notFound
		$this->assertSame('INVOICE', $nc->moduleNameUpper); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testNormalizesObjectFromLowercase(): void
	{
		$nc = new NamingContract('invoice', 'request');
		$this->assertSame('Request', $nc->objectNameCase); // @phpstan-ignore method.notFound
		$this->assertSame('request', $nc->objectNameLower); // @phpstan-ignore method.notFound
		$this->assertSame('REQUEST', $nc->objectNameUpper); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testPreservesCompoundPascalCaseObject(): void
	{
		$nc = new NamingContract('Invoice', 'InvoiceRequest');
		$this->assertSame('InvoiceRequest', $nc->objectNameCase); // @phpstan-ignore method.notFound
		$this->assertSame('invoicerequest', $nc->objectNameLower); // @phpstan-ignore method.notFound
		$this->assertSame('INVOICEREQUEST', $nc->objectNameUpper); // @phpstan-ignore method.notFound
	}

	// ── NamingContract — map ──────────────────────────────────────────────

	/**
	 * @return void
	 */
	public function testSubstitutionMapHasTwelveKeysWithObject(): void
	{
		$nc = new NamingContract('mymodule', 'myobject');
		$this->assertCount(12, $nc->getSubstitutionMap()); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testSubstitutionMapHasSevenKeysModuleOnly(): void
	{
		$nc = new NamingContract('mymodule');
		$this->assertCount(7, $nc->getSubstitutionMap()); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testSubstitutionMapOrderUpperBeforeLower(): void
	{
		$nc   = new NamingContract('mymodule', 'myobject');
		$keys = array_keys($nc->getSubstitutionMap());

		$posUpper = array_search('MYOBJECT', $keys, true);
		$posCase  = array_search('MyObject', $keys, true);
		$posLower = array_search('myobject', $keys, true);

		$this->assertIsInt($posUpper); // @phpstan-ignore method.notFound
		$this->assertIsInt($posCase); // @phpstan-ignore method.notFound
		$this->assertIsInt($posLower); // @phpstan-ignore method.notFound
		$this->assertLessThan($posCase, $posUpper, 'MYOBJECT must precede MyObject'); // @phpstan-ignore method.notFound
		$this->assertLessThan($posLower, $posCase, 'MyObject must precede myobject'); // @phpstan-ignore method.notFound
	}

	// ── NamingContract — applyTo ──────────────────────────────────────────

	/**
	 * @return void
	 */
	public function testApplyToReplacesAllTwelveVariants(): void
	{
		$nc       = new NamingContract('invoice', 'request');
		$template = 'MYMODULE MyModule My module my module Mon module mon module mymodule MYOBJECT MyObject My Object my object myobject';
		$result   = $nc->applyTo($template);

		$this->assertStringNotContainsStringIgnoringCase('mymodule', $result); // @phpstan-ignore method.notFound
		$this->assertStringNotContainsStringIgnoringCase('myobject', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('INVOICE', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('Invoice', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('invoice', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('REQUEST', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('Request', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('request', $result); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testApplyToPreservesModulebuilderPermissionsMarker(): void
	{
		$nc      = new NamingContract('invoice', 'request');
		$content = '/* BEGIN MODULEBUILDER PERMISSIONS */ code /* END MODULEBUILDER PERMISSIONS */';
		$result  = $nc->applyTo($content);
		$this->assertStringContainsString('/* BEGIN MODULEBUILDER PERMISSIONS */', $result); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('/* END MODULEBUILDER PERMISSIONS */', $result); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testApplyToDoesNotAlterUntokenizedContent(): void
	{
		$nc      = new NamingContract('invoice', 'request');
		$content = 'class CommonObject extends DolibarrObject {}';
		$this->assertSame($content, $nc->applyTo($content)); // @phpstan-ignore method.notFound
	}

	// ── NamingContract — applyToFilename ─────────────────────────────────

	/**
	 * @return void
	 */
	public function testApplyToFilenameReplacesLowercaseTokens(): void
	{
		$nc = new NamingContract('invoice', 'request');
		$this->assertSame('invoice_request.lib.php',    $nc->applyToFilename('mymodule_myobject.lib.php')); // @phpstan-ignore method.notFound
		$this->assertSame('llx_invoice_request.sql',    $nc->applyToFilename('llx_mymodule_myobject.sql')); // @phpstan-ignore method.notFound
		$this->assertSame('admin/request_extrafields.php', $nc->applyToFilename('admin/myobject_extrafields.php')); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testApplyToFilenameModuleOnlyIgnoresObjectToken(): void
	{
		$nc = new NamingContract('invoice');
		$this->assertSame('invoice_myobject.lib.php', $nc->applyToFilename('mymodule_myobject.lib.php')); // @phpstan-ignore method.notFound
	}

	// ── NamingContract — module-only contract ────────────────────────────

	/**
	 * @return void
	 */
	public function testModuleOnlyContractHasEmptyObjectProps(): void
	{
		$nc = new NamingContract('invoice');
		$this->assertSame('', $nc->objectNameCase); // @phpstan-ignore method.notFound
		$this->assertSame('', $nc->objectNameLower); // @phpstan-ignore method.notFound
		$this->assertSame('', $nc->objectNameUpper); // @phpstan-ignore method.notFound
	}

	// ── NamingContract — guard ────────────────────────────────────────────

	/**
	 * @return void
	 */
	public function testCollisionGuardThrowsOnIdenticalNames(): void
	{
		$this->expectException(\InvalidArgumentException::class); // @phpstan-ignore method.notFound
		new NamingContract('invoice', 'invoice');
	}

	/**
	 * @return void
	 */
	public function testCollisionGuardIsCaseInsensitive(): void
	{
		$this->expectException(\InvalidArgumentException::class); // @phpstan-ignore method.notFound
		new NamingContract('Invoice', 'INVOICE');
	}

	// ── StrictNamingContractValidator — validateContent ──────────────────

	/**
	 * @return void
	 */
	public function testValidatorCatchesResidualMyobjectLowercase(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent('$obj = new myobject($db);', 'test.php');
		$this->assertNotEmpty($errors); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('myobject', $errors[0]); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testValidatorCatchesResidualMyobjectMixedCase(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent('class MyObject extends CommonObject', 'test.php');
		$this->assertNotEmpty($errors); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testValidatorCatchesResidualMymodule(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent("isModEnabled('mymodule')", 'test.php');
		$this->assertNotEmpty($errors); // @phpstan-ignore method.notFound
		$this->assertStringContainsString('mymodule', $errors[0]); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testValidatorAcceptsNonRenamableBeginMarker(): void
	{
		$validator = new StrictNamingContractValidator();
		$content   = "/* BEGIN MODULEBUILDER API MYOBJECT */\n\t/* END MODULEBUILDER API MYOBJECT */";
		$errors    = $validator->validateContent($content, 'test.php');
		$this->assertEmpty($errors); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testValidatorAcceptsCleanContent(): void
	{
		$validator = new StrictNamingContractValidator();
		$errors    = $validator->validateContent('$obj = new Invoice($db);', 'test.php');
		$this->assertEmpty($errors); // @phpstan-ignore method.notFound
	}

	// ── StrictNamingContractValidator — other methods ────────────────────

	/**
	 * @return void
	 */
	public function testValidatorValidatesMatchingTriggerFilename(): void
	{
		$nc        = new NamingContract('Invoice');
		$validator = new StrictNamingContractValidator();
		$this->assertTrue( // @phpstan-ignore method.notFound
			$validator->validateTriggerFilename('interface_99_modInvoice_InvoiceTriggers.class.php', $nc)
		);
	}

	/**
	 * @return void
	 */
	public function testValidatorRejectsResidualTriggerFilename(): void
	{
		$nc        = new NamingContract('Invoice');
		$validator = new StrictNamingContractValidator();
		$this->assertFalse( // @phpstan-ignore method.notFound
			$validator->validateTriggerFilename('interface_99_modMyModule_MyModuleTriggers.class.php', $nc)
		);
	}

	/**
	 * @return void
	 */
	public function testValidatorValidatesMatchingClassName(): void
	{
		$nc        = new NamingContract('Invoice', 'Request');
		$validator = new StrictNamingContractValidator();
		$this->assertTrue($validator->validateClassName('Request', $nc)); // @phpstan-ignore method.notFound
		$this->assertFalse($validator->validateClassName('MyObject', $nc)); // @phpstan-ignore method.notFound
	}

	/**
	 * @return void
	 */
	public function testValidatorValidatesRightsKey(): void
	{
		$nc        = new NamingContract('invoice', 'request');
		$validator = new StrictNamingContractValidator();
		$this->assertTrue($validator->validateRightsKey('invoice.request.read', $nc)); // @phpstan-ignore method.notFound
		$this->assertFalse($validator->validateRightsKey('mymodule.myobject.read', $nc)); // @phpstan-ignore method.notFound
	}
}
