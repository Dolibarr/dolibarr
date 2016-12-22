<?php
/**
 * Test that all sub-classes of AbstractCapabilityProfile
 * are creating data in the right format.
 */
class EscposCapabilityProfileTest extends PHPUnit_Framework_TestCase {
	private $profiles;
	private $checklist;
	
	function setup() {
		$this -> profiles = array('DefaultCapabilityProfile', 'EposTepCapabilityProfile', 'SimpleCapabilityProfile', 'StarCapabilityProfile', 'P822DCapabilityProfile');
		$this -> checklist = array();
		foreach($this -> profiles as $profile) {
			$this-> checklist[] = $profile::getInstance();
		}
	}	
	
	function testSupportedCodePages() {
		foreach($this -> checklist as $obj) {
			$check = $obj -> getSupportedCodePages();
			$this -> assertTrue(is_array($check) && isset($check[0]) && $check[0] == 'CP437');
			$custom = $obj -> getCustomCodePages();
			foreach($check as $num => $page) {
				$this -> assertTrue(is_numeric($num) && ($page === false || is_string($page)));
				if($page === false || strpos($page, ":") === false) {
					continue;
				}
				$part = explode(":", $page);
				if(!array_shift($part) == "custom") {
					continue;
				}
				$this -> assertTrue(isset($custom[implode(":", $part)]));
			}
		}
	}
	
	function testCustomCodePages() {
		foreach($this -> checklist as $obj) {
			$check = $obj -> getCustomCodePages();
			$this -> assertTrue(is_array($check));
			foreach($check as $name => $customMap) {
				$this -> assertTrue(is_string($name));
				$this -> assertTrue(is_string($customMap) && mb_strlen($customMap, 'UTF-8') == 128);
			}
		}
	}
	
	function testSupportsBitImage() {
		foreach($this -> checklist as $obj) {
			$check = $obj -> getSupportsBitImage();
			$this -> assertTrue(is_bool($check));
		}
	}
	
	function testSupportsGraphics() {
		foreach($this -> checklist as $obj) {
			$check = $obj -> getSupportsGraphics();
			$this -> assertTrue(is_bool($check));
		}
	}
	
	function testSupportsQrCode() {
		foreach($this -> checklist as $obj) {
			$check = $obj -> getSupportsQrCode();
			$this -> assertTrue(is_bool($check));
		}
	}
}
?>