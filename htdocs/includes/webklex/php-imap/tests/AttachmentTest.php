<?php

declare(strict_types=1);

namespace Tests;

use Tests\fixtures\FixtureTestCase;
use Webklex\PHPIMAP\Attachment;

class AttachmentTest extends FixtureTestCase
{
    protected Attachment $attachment;

    public function setUp(): void
    {
        $message = $this->getFixture("attachment_encoded_filename.eml");
        $this->attachment = $message->getAttachments()->first();
    }
    /**
     * @dataProvider decodeNameDataProvider
     */
    public function testDecodeName(string $input, string $output): void
    {
        $name = $this->attachment->decodeName($input);
        $this->assertEquals($output, $name);
    }

    public function decodeNameDataProvider(): array
    {
        return [
            ['../../../../../../../../../../../var/www/shell.php', 'varwwwshell.php'],
            ['test..xml', 'test.xml'],
            [chr(0), ''],
            ['C:\\file.txt', 'Cfile.txt'],
        ];
    }
}
