<?php

namespace Stripe;

class FileUploadTest extends TestCase
{
    const TEST_RESOURCE_ID = 'file_123';

    /**
     * @before
     */
    public function setUpFixture()
    {
        // PHP <= 5.5 does not support arrays as class constants, so we set up
        // the fixture as an instance variable.
        $this->fixture = [
            'id' => self::TEST_RESOURCE_ID,
            'object' => 'file_upload',
        ];
    }

    public function testIsListable()
    {
        $this->stubRequest(
            'get',
            '/v1/files',
            [],
            null,
            false,
            [
                'object' => 'list',
                'data' => [$this->fixture],
                'resource_url' => '/v1/files',
            ],
            200,
            Stripe::$apiUploadBase
        );

        $resources = FileUpload::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\FileUpload", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->stubRequest(
            'get',
            '/v1/files/' . self::TEST_RESOURCE_ID,
            [],
            null,
            false,
            $this->fixture,
            200,
            Stripe::$apiUploadBase
        );
        $resource = FileUpload::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\FileUpload", $resource);
    }

    public function testIsCreatableWithFileHandle()
    {
        $this->stubRequest(
            'post',
            '/v1/files',
            null,
            ['Content-Type: multipart/form-data'],
            true,
            $this->fixture,
            200,
            Stripe::$apiUploadBase
        );
        $fp = fopen(dirname(__FILE__) . '/../data/test.png', 'r');
        $resource = FileUpload::create([
            "purpose" => "dispute_evidence",
            "file" => $fp,
        ]);
        $this->assertInstanceOf("Stripe\\FileUpload", $resource);
    }

    public function testIsCreatableWithCurlFile()
    {
        if (!class_exists('\CurlFile', false)) {
            // Older PHP versions don't support this
            return;
        }

        $this->stubRequest(
            'post',
            '/v1/files',
            null,
            ['Content-Type: multipart/form-data'],
            true,
            $this->fixture,
            200,
            Stripe::$apiUploadBase
        );
        $curlFile = new \CurlFile(dirname(__FILE__) . '/../data/test.png');
        $resource = FileUpload::create([
            "purpose" => "dispute_evidence",
            "file" => $curlFile,
        ]);
        $this->assertInstanceOf("Stripe\\FileUpload", $resource);
    }
}
