<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Standalone regression test for native photo access hooks.
 * Run: php test/other/test_secure_access_hook.php
 * Uses the real file library and HookManager with simulated configuration,
 * permissions and a sharing hook; no database or deployed Multicompany instance.
 */

define('MAIN_DB_PREFIX', 'llx_');

function dol_syslog($message, $level = 0)
{
}

function isModEnabled($module)
{
	return in_array($module, array('product', 'sharingtest'), true);
}

function getDolGlobalInt($name, $default = 0)
{
	return $default;
}

function getEntity($element)
{
	return '1,2';
}

require_once __DIR__.'/../../htdocs/core/lib/files.lib.php';
require_once __DIR__.'/../../htdocs/core/class/hookmanager.class.php';

class PhotoAccessTestUser
{
	public $allowed = true;
	public $admin = 0;

	public function hasRight(...$parts)
	{
		return $this->allowed;
	}
}

class PhotoAccessTestHook
{
	public $results = array();
	public $resprints;
	public $error = '';
	public $errors = array();
	public $warnings = array();
	public $mode = 'neutral';
	public $calls = 0;
	public $lastEntity;

	public function checkSecureAccess($parameters, &$object, &$action, $hookmanager)
	{
		$this->calls++;
		$this->lastEntity = $parameters['entity'];
		if ($parameters['entity'] !== 1) {
			throw new RuntimeException('The URL entity replaced the session entity');
		}
		if ($this->mode === 'error') {
			return -1;
		}
		if ($this->mode === 'deny') {
			$this->results = array('accessallowed' => 0);
			return 1;
		}
		if ($this->mode === 'share' && $parameters['fuser']->allowed && ($_GET['entity'] ?? 0) === 2) {
			$this->results = array(
				'accessallowed' => 1,
				'original_file' => str_replace('/documents/', '/documents/2/', $parameters['original_file']),
			);
			return 1;
		}
		return 0;
	}
}

$user = new PhotoAccessTestUser();
$conf = (object) array(
	'entity' => 1,
	'modules_parts' => array('hooks' => array('sharingtest' => array('fileslib'))),
	'societe' => (object) array('multidir_output' => array(1 => '/documents/societe')),
	'product' => (object) array('multidir_output' => array(1 => '/documents/produit')),
	'service' => (object) array('multidir_output' => array(1 => '/documents/produit')),
	'testmodule' => (object) array('dir_output' => '/documents/testmodule', 'multidir_output' => array(1 => '/documents/testmodule')),
);
$hook = new PhotoAccessTestHook();
$hookmanager = new HookManager(null);
$hookmanager->hooks = array('fileslib' => array('sharingtest' => $hook));
$hookmanager->hooksSorted = array('fileslib' => array('50:sharingtest' => $hook));
$object = null;
$checks = 0;

foreach (array('contact', 'socpeople', 'product', 'produit', 'service', 'produit|service', 'testmodule') as $modulepart) {
	foreach (array('neutral', 'share', 'deny', 'error', 'neutral') as $mode) {
		$hook->mode = $mode;
		$_GET['entity'] = 2;
		$before = $hook->calls;
		$result = dol_check_secure_access_document($modulepart, '42/photos/photo.jpg', 1, $user);
		$expectedAccess = in_array($mode, array('deny', 'error'), true) ? 0 : 1;
		$expectedDirectory = $mode === 'share' ? '/documents/2/' : '/documents/';
		if ($hook->calls !== $before + 1 || $result['accessallowed'] !== $expectedAccess
			|| strpos($result['original_file'], $expectedDirectory) !== 0
			|| ($mode !== 'share' && strpos($result['original_file'], '/documents/2/') === 0)) {
			throw new RuntimeException('Unexpected access result for '.$modulepart.' / '.$mode);
		}
		$checks++;
	}
	$user->allowed = false;
	$hook->mode = 'share';
	$result = dol_check_secure_access_document($modulepart, '42/photos/photo.jpg', 1, $user);
	if ($result['accessallowed'] !== 0) {
		throw new RuntimeException('Missing functional permission was bypassed');
	}
	$user->allowed = true;
	$checks++;
	$_GET['entity'] = 9;
	$result = dol_check_secure_access_document($modulepart, '42/photos/photo.jpg', 1, $user);
	if (strpos($result['original_file'], '/documents/9/') === 0 || $hook->lastEntity !== 1) {
		throw new RuntimeException('An unapproved URL entity changed the file directory');
	}
	$checks++;
}

// Without an installed hook, native permissions and paths must remain unchanged.
$conf->modules_parts['hooks'] = array();
$hookmanager = new HookManager(null);
foreach (array('contact', 'product') as $modulepart) {
	foreach (array(true, false) as $allowed) {
		$user->allowed = $allowed;
		$result = dol_check_secure_access_document($modulepart, '42/photos/photo.jpg', 1, $user);
		if ($result['accessallowed'] !== (int) $allowed || strpos($result['original_file'], '/documents/9/') === 0) {
			throw new RuntimeException('Native access changed without a hook');
		}
		$checks++;
	}
}

echo $checks." access checks passed with the native HookManager.\n";
