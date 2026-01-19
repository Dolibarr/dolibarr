<?php
/*
* File: Issue355Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 10.01.23 10:48
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Header;

class Issue355Test extends TestCase {

    public function testIssue() {
        $raw_header = "Subject: =?UTF-8?Q?Re=3A_Uppdaterat_=C3=A4rende_=28447899=29=2C_kostnader_f=C3=B6r_hj=C3=A4?= =?UTF-8?Q?lp_med_stadge=C3=A4ndring_enligt_ny_lagstiftning?=\r\n";

        $header = new Header($raw_header, Config::make());
        $subject = $header->get("subject");

        $this->assertEquals("Re: Uppdaterat ärende (447899), kostnader för hjälp med stadgeändring enligt ny lagstiftning", $subject->toString());
    }

}