<?php
class Tests_PageObjectTest extends Tests_Selenium2TestCase_BaseTestCase
{
    public function testAPageInteractsWithElementsExposingAnHigherLevelApi()
    {
        $this->url('html/test_type_page1.html');
        $page = new Tests_AuthenticationPage($this);
        $welcomePage = $page->username('TestUser')
                            ->password('TestPassword')
                            ->submit();
        $welcomePage->assertWelcomeIs('Welcome, TestUser!');
    }
}

class Tests_AuthenticationPage
{
    public function __construct($test)
    {
        $this->usernameInput = $test->byName('username');
        $this->passwordInput = $test->byName('password');
        $this->test = $test;
    }

    public function username($value)
    {
        $this->usernameInput->value($value);
        return $this;
    }

    public function password($value)
    {
        $this->passwordInput->value($value);
        return $this;
    }

    public function submit()
    {
        $this->test->clickOnElement('submitButton');
        return new Tests_WelcomePage($this->test);
    }
}

class Tests_WelcomePage
{
    public function __construct($test)
    {
        $this->header = $test->byCssSelector('h2');
        $this->test = $test;
    }

    public function assertWelcomeIs($text)
    {
        $this->test->assertRegExp("/$text/", $this->header->text());
    }
}
