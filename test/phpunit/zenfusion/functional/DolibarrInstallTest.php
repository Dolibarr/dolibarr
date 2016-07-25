<?php

class ZenFusionInstallTest extends PHPUnit_Extensions_Selenium2TestCase
{
	public function setUp()
	{
		$this->setHost('localhost');
		$this->setPort(4444);
		$this->setBrowserUrl('http://dev.zenfusion.fr');
		$this->setBrowser('chrome');
	}

	public function setUpPage()
	{
		$this->url('/');
	}

	public function testInstallRedirect()
	{
		$this->assertContains('/install/index.php', $this->url());
	}

	public function testInstallPageTitle()
	{
		$this->assertContains('Dolibarr', $this->title());
	}

	public function testInstallProcess()
	{
		$this->byName('forminstall')->submit();
		$this->assertContains('/install/check.php', $this->url());
	}
}
