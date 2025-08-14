<?php
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class WindowsPrintConnectorTest extends PHPUnit\Framework\TestCase
{
    private $connector;

    public function testLptWindows()
    {
        // Should attempt to send data to the local printer by writing to it
        $connector = $this -> getMockConnector("LPT1", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> once())
                -> method('runWrite')
                -> with($this -> equalTo(''), $this -> equalTo("LPT1"));
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testLptMac()
    {
        // Cannot print to local printer on Mac with this connector
        $this -> expectException(BadMethodCallException::class);
        $connector = $this -> getMockConnector("LPT1", WindowsPrintConnector::PLATFORM_MAC);
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testLptLinux()
    {
        // Cannot print to local printer on Linux with this connector
        $this -> expectException(BadMethodCallException::class);
        $connector = $this -> getMockConnector("LPT1", WindowsPrintConnector::PLATFORM_LINUX);
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testComWindows()
    {
        // Simple file write
        $connector = $this -> getMockConnector("COM1", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> once())
                -> method('runWrite')
                -> with($this -> equalTo(''), $this -> equalTo("COM1"));
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testComMac()
    {
        // Cannot print to local printer on Mac with this connector
        $this -> expectException(BadMethodCallException::class);
        $connector = $this -> getMockConnector("COM1", WindowsPrintConnector::PLATFORM_MAC);
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testComLinux()
    {
        // Cannot print to local printer on Linux with this connector
        $this -> expectException(BadMethodCallException::class);
        $connector = $this -> getMockConnector("COM1", WindowsPrintConnector::PLATFORM_LINUX);
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testLocalShareWindows()
    {
        $connector = $this -> getMockConnector("Printer", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> once())
                -> method('runCopy')
                -> with($this -> anything(), $this -> stringContains('\\Printer'));
        $connector -> finalize();
    }

    public function testSharedPrinterWindows()
    {
        $connector = $this -> getMockConnector("smb://example-pc/Printer", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> once())
                -> method('runCopy')
                -> with($this -> anything(), $this -> equalTo('\\\\example-pc\\Printer'));
        $connector -> finalize();
    }

    public function testSharedPrinterWindowsUsername()
    {
        $connector = $this -> getMockConnector("smb://bob@example-pc/Printer", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('net use \'\\\\example-pc\\Printer\' \'/user:bob\''));
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> once())
                -> method('runCopy')
                -> with($this -> anything(), $this -> equalTo('\\\\example-pc\\Printer'));
        $connector -> finalize();
    }

    public function testSharedPrinterWindowsUsernameDomain()
    {
        $connector = $this -> getMockConnector("smb://bob@example-pc/home/Printer", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('net use \'\\\\example-pc\\Printer\' \'/user:home\\bob\''));
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> once())
                -> method('runCopy')
                -> with($this -> anything(), $this -> equalTo('\\\\example-pc\\Printer'));
        $connector -> finalize();
    }

    public function testSharedPrinterWindowsUsernamePassword()
    {
        $connector = $this -> getMockConnector("smb://bob:secret@example-pc/Printer", WindowsPrintConnector::PLATFORM_WIN);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('net use \'\\\\example-pc\\Printer\' \'/user:bob\' \'secret\''));
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> once())
                -> method('runCopy')
                -> with($this -> anything(), $this -> equalTo('\\\\example-pc\\Printer'));
        $connector -> finalize();
    }

    public function testSharedPrinterMac()
    {
        // Not implemented
        $this -> expectException(Exception::class);
        $connector = $this -> getMockConnector("smb://Guest@example-pc/Printer", WindowsPrintConnector::PLATFORM_MAC);
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> expects($this -> exactly(0))
                -> method('runCommand');
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> finalize();
    }

    public function testSharedPrinterLinux()
    {
        $connector = $this -> getMockConnector("smb://example-pc/Printer", WindowsPrintConnector::PLATFORM_LINUX);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('smbclient \'//example-pc/Printer\' -c \'print -\' -N -m SMB2'));
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> finalize();
    }

    public function testSharedPrinterLinuxUsername()
    {
        $connector = $this -> getMockConnector("smb://bob@example-pc/Printer", WindowsPrintConnector::PLATFORM_LINUX);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('smbclient \'//example-pc/Printer\' -U \'bob\' -c \'print -\' -N -m SMB2'));
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> finalize();
    }

    public function testSharedPrinterLinuxUsernameDomain()
    {
        $connector = $this -> getMockConnector("smb://bob@example-pc/home/Printer", WindowsPrintConnector::PLATFORM_LINUX);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('smbclient \'//example-pc/Printer\' -U \'home\\bob\' -c \'print -\' -N -m SMB2'));
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> finalize();
    }

    public function testSharedPrinterLinuxUsernamePassword()
    {
        $connector = $this -> getMockConnector("smb://bob:secret@example-pc/Printer", WindowsPrintConnector::PLATFORM_LINUX);
        $connector -> expects($this -> once())
                -> method('runCommand')
                -> with($this -> equalTo('smbclient \'//example-pc/Printer\' \'secret\' -U \'bob\' -c \'print -\' -m SMB2'));
        $connector -> expects($this -> exactly(0))
                -> method('runCopy');
        $connector -> expects($this -> exactly(0))
                -> method('runWrite');
        $connector -> finalize();
    }

    private function getMockConnector($path, $platform)
    {
        $stub = $this -> getMockBuilder('Mike42\Escpos\PrintConnectors\WindowsPrintConnector')
                -> setMethods(array('runCopy', 'runCommand', 'getCurrentPlatform', 'runWrite'))
                -> disableOriginalConstructor()
                -> getMock();
        $stub -> method('runCommand')
                -> willReturn(0);
        $stub -> method('runCopy')
                -> willReturn(true);
        $stub -> method('runWrite')
                -> willReturn(true);
        $stub -> method('getCurrentPlatform')
                -> willReturn($platform);
        $stub -> __construct($path);
        return $stub;
    }

    /**
     * Test for correct identification of bogus or non-supported Samba strings.
     */
    public function testSambaRegex()
    {
        $good = array("smb://foo/bar",
                "smb://foo/bar baz",
                "smb://bob@foo/bar",
                "smb://bob:secret@foo/bar",
                "smb://foo-computer/FooPrinter",
                "smb://foo-computer/workgroup/FooPrinter",
                "smb://foo-computer/Foo-Printer",
                "smb://foo-computer/workgroup/Foo-Printer",
                "smb://foo-computer/Foo Printer",
                "smb://foo-computer.local/Foo Printer",
                "smb://127.0.0.1/abcd"
        );
        $bad = array("",
                "http://google.com",
                "smb:/foo/bar",
                "smb://",
                "smb:///bar",
                "smb://@foo/bar",
                "smb://bob:@foo/bar",
                "smb://:secret@foo/bar",
                "smb://foo/bar/baz/quux",
                "smb://foo-computer//FooPrinter");
        foreach ($good as $item) {
            $this -> assertTrue(preg_match(WindowsPrintConnector::REGEX_SMB, $item) == 1, "Windows samba regex should pass '$item'.");
        }
        foreach ($bad as $item) {
            $this -> assertTrue(preg_match(WindowsPrintConnector::REGEX_SMB, $item) != 1, "Windows samba regex should fail '$item'.");
        }
    }
    
    public function testPrinterNameRegex()
    {
        $good = array("a",
                "ab",
                "a b",
                "a-b",
                "Abcd Efg-",
                "-a",
                "OK1"
        );
        $bad = array("",
                " ",
                "a ",
                " a",
                " a ",
                "a/B",
                "A:b"
        );
        foreach ($good as $item) {
            $this -> assertTrue(preg_match(WindowsPrintConnector::REGEX_PRINTERNAME, $item) == 1, "Windows printer name regex should pass '$item'.");
        }
        foreach ($bad as $item) {
            $this -> assertTrue(preg_match(WindowsPrintConnector::REGEX_PRINTERNAME, $item) != 1, "Windows printer name regex should fail '$item'.");
        }
    }
}
