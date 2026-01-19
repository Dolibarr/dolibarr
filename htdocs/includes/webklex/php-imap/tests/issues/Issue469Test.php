<?php

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;

class Issue469Test extends TestCase {

    /**
     * Test issue #469 - Case sensitive \NoSelect flag check doesn't work for Gmail
     */
    public function testIssue(): void {
		$client = $this->createStub(Client::class);
		$folder_name = '[Gmail]';
		$delimiter = '/';

		$attributes = [
			'\NoInferiors',
			'\NoSelect',
		];
		$folder = new Folder($client, $folder_name, $delimiter, $attributes);

		$attributes_lowercase = [
			'\Noinferiors',
			'\Noselect',
		];
		$folder_lowercase = new Folder($client, $folder_name, $delimiter, $attributes_lowercase);

		self::assertSame(
			$folder->no_inferiors,
			$folder_lowercase->no_inferiors,
			'The parsed "\NoInferiors" attribute does not match the parsed "\Noinferiors" attribute'
		);
		self::assertSame(
			$folder->no_select,
			$folder_lowercase->no_select,
			'The parsed "\NoSelect" attribute does not match the parsed "\Noselect" attribute'
		);
	}
}