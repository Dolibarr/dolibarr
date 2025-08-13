<?php
use Mike42\Escpos\CapabilityProfile;

class CapabilityProfileTest extends PHPUnit\Framework\TestCase
{

    public function testNames()
    {
        // Default is on the list
        $names = CapabilityProfile::getProfileNames();
        $this->assertFalse(array_search('simple', $names) === false);
        $this->assertFalse(array_search('default', $names) === false);
        $this->assertTrue(array_search('lalalalala', $names) === false);
    }

    public function testLoadDefault()
    {
        // Just load the default profile and check it out
        $profile = CapabilityProfile::load('default');
        $this->assertEquals("default", $profile->getId());
        $this->assertEquals("Default", $profile->getName());
        $this->assertTrue($profile->getSupportsBarcodeB());
        $this->assertTrue($profile->getSupportsBitImageRaster());
        $this->assertTrue($profile->getSupportsGraphics());
        $this->assertTrue($profile->getSupportsQrCode());
        $this->assertTrue($profile->getSupportsPdf417Code());
        $this->assertFalse($profile->getSupportsStarCommands());
        $this->assertArrayHasKey('0', $profile->getCodePages());
    }

    public function testCodePageCacheKey()
    {
        $default = CapabilityProfile::load('default');
        $simple = CapabilityProfile::load('simple');
        $this->assertNotEquals($default->getCodePageCacheKey(), $simple->getCodePageCacheKey());
    }

    public function testBadProfileNameSuggestion()
    {
        $this->expectException(InvalidArgumentException::class);
        $profile = CapabilityProfile::load('simpel');
    }

    public function testBadFeatureNameSuggestion()
    {
        $this->expectException(InvalidArgumentException::class);
        $profile = CapabilityProfile::load('default');
        $profile->getFeature('graphicx');
    }

    public function testSuggestions()
    {
        $input = "orangee";
        $choices = array("apple", "orange", "pear");
        $suggestions = CapabilityProfile::suggestNearest($input, $choices, 1);
        $this->assertEquals(1, count($suggestions));
        $this->assertEquals("orange", $suggestions[0]);
    }
}