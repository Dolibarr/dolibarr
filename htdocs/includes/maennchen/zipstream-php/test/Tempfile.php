<?php

declare(strict_types=1);

namespace ZipStream\Test;

trait Tempfile
{
    protected string|null $tempfile;

    /**
     * @var resource
     */
    protected $tempfileStream;

    protected function setUp(): void
    {
        [$tempfile, $tempfileStream] = $this->getTmpFileStream();

        $this->tempfile = $tempfile;
        $this->tempfileStream = $tempfileStream;
    }

    protected function tearDown(): void
    {
        unlink($this->tempfile);
        if (is_resource($this->tempfileStream)) {
            fclose($this->tempfileStream);
        }

        $this->tempfile = null;
        $this->tempfileStream = null;
    }

    protected function getTmpFileStream(): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zipstreamtest');
        $stream = fopen($tmp, 'wb+');

        return [$tmp, $stream];
    }
}
