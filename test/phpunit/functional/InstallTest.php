<?php

/* Copyright (C) 2016  Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 *
 * Install functional test using PHPUnit's Selenium
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Class InstallTest
 */
class InstallTest extends PHPUnit_Extensions_Selenium2TestCase
{
	protected static $url = 'https://dev.dolibarr.org';
	protected static $db_name = 'dolibarr_test';
	protected static $db_host = 'localhost';
	protected static $db_admin_user = 'root';
	protected static $db_admin_pass = '';
	protected static $db_user = 'dolibarr';
	protected static $db_pass = 'dolibarr';
	protected static $dol_admin_user = 'admin';
	protected static $dol_admin_pass = 'admin';

	public static $browsers = array(
		array(
			'browser' => 'chrome',
			'browserName' => 'chrome',
			'sessionStrategy' => 'shared',
			'desiredCapabilities' => array()
		)
	);

    /**
     * setUpBeforeClass
     *
     * @return	void
     */
	public static function setUpBeforeClass()
	{
		// Make sure we backup and remove the configuration file to force new install.
		@rename('htdocs/conf/conf.php', sys_get_temp_dir() . '/conf.php');

		// Start without a database
		self::dropTestDatabase();

		// Run the tests in the same window
		self::shareSession(true);
	}

    /**
     * dropTestDatabase
     *
     * @return	void
     */
	protected static function dropTestDatabase()
	{
		$mysqli = new mysqli(self::$db_host, self::$db_admin_user, self::$db_admin_pass);
		$mysqli->query("DROP DATABASE " . self::$db_name);
	}

    /**
     * tearDownAfterClass
     *
     * @return	void
     */
	public static function tearDownAfterClass()
	{
		// Remove the generated configuration and restore the backed up file.
		@unlink('htdocs/conf/conf.php');
		@rename(sys_get_temp_dir() . '/conf.php', 'htdocs/conf/conf.php');

		// Cleanup test database
		self::dropTestDatabase();
	}

    /**
     * setUp
     *
     * @return  void
     */
	public function setUp()
	{
		// Populating the database can take quite long.
		$this->setSeleniumServerRequestsTimeout(120000);
		$this->setBrowserUrl(self::$url);
	}

    /**
     * testInstallRedirect
     *
     * @return  void
     */
	public function testInstallRedirect()
	{
		$this->url('/');
		$this->assertContains('/install/index.php', $this->url());
	}

    /**
     * testInstallPageTitle
     *
     * @return  void
     */
	public function testInstallPageTitle()
	{
		$this->assertContains('Dolibarr', $this->title());
	}

    /**
     * testInstallProcess
     *
     * @return  void
     */
	public function testInstallProcess()
	{
		// FIXME: the button itself should have an ID
		$this->byId('nextbutton')->byTag('input')->click();
		$this->assertContains('/install/check.php', $this->url());
	}

    /**
     * testCheckPage
     *
     * @return  void
     */
	public function testCheckPage()
	{
		$unavailable_choices = $this->byId('navail_choices');
		$show_hide_choices = $this->byId('AShowChoices')->byTag('a');
		$this->assertFalse($unavailable_choices->displayed());
		// FIXME: the link itself should have an ID
		$show_hide_choices->click();
		$this->assertTrue($unavailable_choices->displayed());
		$show_hide_choices->click();
		$this->assertFalse($unavailable_choices->displayed());
		$this->byClassName('button')->click();
		$this->assertContains('/install/fileconf.php', $this->url());
	}

    /**
     * testForm
     *
     * @return  void
     */
	public function testForm()
	{
		$this->assertFalse($this->byClassName('hideroot')->displayed());
		$this->assertTrue($this->byClassName('hidesqlite')->displayed());

		// FIXME: This element should have an ID
		$this->assertFalse($this->byName('main_force_https')->selected());
		$this->byName('main_force_https')->click();

		$this->assertEquals('dolibarr', $this->byId('db_name')->value());
		$this->byId('db_name')->clear();
		$this->byId('db_name')->value(self::$db_name);

		$this->assertEquals('mysqli', $this->byId('db_type')->value());

		// FIXME: This element should have an ID
		$this->assertEquals('localhost', $this->byName('db_host')->value());

		$this->assertEquals(3306, $this->byId('db_port')->value());

		$this->assertEquals('llx_', $this->byId('db_prefix')->value());

		$this->byId('db_create_database')->click();
		$this->assertTrue($this->byClassName('hideroot')->displayed());
		$this->byId('db_create_database')->click();
		$this->assertFalse($this->byClassName('hideroot')->displayed());

		$this->byId('db_user')->value(self::$db_user);

		$this->byId('db_pass')->value(self::$db_pass);

		$this->byId('db_create_user')->click();
		$this->assertTrue($this->byClassName('hideroot')->displayed());
		$this->byId('db_create_user')->click();
		$this->assertFalse($this->byClassName('hideroot')->displayed());

		$this->byId('db_create_database')->click();
		$this->byId('db_create_user')->click();
		$this->assertTrue($this->byClassName('hideroot')->displayed());

		$this->byId('db_user_root')->value('root');
		$this->byId('db_pass_root')->value('');
	}

    /**
     * testFormSubmit
     *
     * @return  void
     */
	public function testFormSubmit()
	{
		$this->byName('forminstall')->submit();
		$this->assertContains('/install/step1.php', $this->url());
	}

    /**
     * testStep1
     *
     * @return  void
     */
	public function testStep1()
	{
		$this->assertFalse($this->byId('pleasewait')->displayed());
		$start = new DateTimeImmutable();
		// FIXME: the button itself should have an ID
		$this->byId('nextbutton')->byTag('input')->click();
		$time = $start->diff(new DateTimeImmutable());
		echo "\nPopulating the database took " . $time->format("%s seconds.\n");
		$this->assertContains('/install/step2.php', $this->url());
	}

    /**
     * testStep2
     *
     * @return  void
     */
	public function testStep2()
	{
		$this->byName('forminstall')->submit();
		$this->assertContains('/install/step4.php', $this->url());
	}

	// There is no step 3

    /**
     * testStep4
     *
     * @return  void
     */
	public function testStep4()
	{
		// FIXME: should have an ID
		$this->byName('login')->value(self::$dol_admin_user);
		// FIXME: should have an ID
		$this->byName('pass')->value('admin');
		// FIXME: should have an ID
		$this->byName('pass_verif')->value(self::$dol_admin_pass);
		// FIXME: the button itself should have an ID
		$this->byId('nextbutton')->byTag('input')->click();
		$this->assertContains('/install/step5.php', $this->url());
	}

    /**
     * testStep5
     *
     * @return  void
     */
	public function testStep5()
	{
		// FIXME: this button should have an ID
		$this->byTag('a')->click();
		$this->assertContains('/admin/index.php', $this->url());
	}

    /**
     * testFirstLogin
     *
     * @return  void
     */
	public function testFirstLogin()
	{
		$this->assertEquals('login', $this->byTag('form')->attribute('id'));
		$this->assertEquals(self::$dol_admin_user, $this->byId('username')->value());
		$this->byId('password')->value(self::$dol_admin_pass);
		// FIXME: login button should have an ID
		$this->byId('login')->submit();
		$this->assertEquals('mainbody', $this->byTag('body')->attribute('id'));
	}
}
