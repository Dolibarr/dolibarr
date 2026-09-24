<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace DolibarrTests\DocumentDownloadFixture;

use stdClass;

// CLI-only service doubles for DocumentDownloadTest. Never loads an instance or database.
if (PHP_SAPI !== 'cli') {
	exit(1);
}
$directory = $argv[1];
$scenario = json_decode(file_get_contents($directory.'/request.json'), true);
define('DOL_DOCUMENT_ROOT', $directory.'/htdocs');
define('DOL_DATA_ROOT', $directory.'/documents');
define('MAIN_DB_PREFIX', 'test_');
$_POST = $scenario['post'];
$_GET = $scenario['get'];
$_POST['selecteddocuments'] = array_map(static function ($selection) {
	return base64_encode(json_encode($selection));
}, $scenario['selection']);
$_SERVER['REQUEST_METHOD'] = isset($scenario['method']) ? $scenario['method'] : 'POST';
$_SERVER['HTTP_USER_AGENT'] = 'DocumentDownloadTest';
$state = array('hooks' => array(), 'triggers' => array(), 'regenerations' => 0, 'counterupdates' => 0);
register_shutdown_function(static function () use ($directory, &$state) {
	$state['status'] = http_response_code() ?: 200;
	$state['nologin'] = defined('NOLOGIN');
	$state['nocsrfcheck'] = defined('NOCSRFCHECK');
	file_put_contents($directory.'/state.json', json_encode($state));
});
$conf = (object) array('entity' => 2);
$user = (object) array('id' => empty($scenario['anonymous']) ? 7 : 0, 'socid' => 0);
$langs = new Translate();
$db = new class {
	/**
	 * Service double for close.
	 *
	 * @return void
	 */
	public function close()
	{
	}

	/**
	 * Service double for query.
	 *
	 * @param string $sql Fixture sql
	 * @return bool
	 */
	public function query($sql)
	{
		global $state;
		$state['counterupdates']++;
		return true;
	}
};
$hookmanager = new class {
	public $error = 'Fixture refusal';
	public $errors = array();
	/**
	 * Service double for initHooks.
	 *
	 * @param string[] $contexts Fixture contexts
	 * @return void
	 */
	public function initHooks($contexts)
	{
	}

	/**
	 * Service double for executeHooks.
	 *
	 * @param string $name Fixture name
	 * @param array<string,mixed> $parameters Fixture parameters
	 * @param object $object Fixture object
	 * @param string $action Fixture action
	 * @return int
	 */
	public function executeHooks($name, $parameters, &$object, &$action)
	{
		global $state, $scenario;
		$state['hooks'][] = $parameters;
		return empty($scenario['denyhook']) ? 0 : -1;
	}
};
/** Minimal translations for endpoint errors. */
class Translate
{
	public $charset_output = 'UTF-8';
	/**
	 * Service double for trans.
	 *
	 * @param string $key Fixture key
	 * @return string
	 */
	public function trans($key)
	{
		return $key;
	}

	/**
	 * Service double for setDefaultLang.
	 *
	 * @param string $lang Fixture lang
	 * @return void
	 */
	public function setDefaultLang($lang)
	{
	}

	/**
	 * Service double for loadLangs.
	 *
	 * @param Translate|string[] $langs Fixture langs
	 * @return void
	 */
	public function loadLangs($langs)
	{
	}
}

/** Invoice double recording download effects. */
class Facture
{
	const STATUS_DRAFT = 0;
	const STATUS_CLOSED = 2;
	public $id = 1;
	public $status = 2;
	public $last_main_doc = 'facture/A/report.pdf';
	public $pos_print_counter = 1;
	public $model_pdf = 'fixture';
	public $ref;
	/**
	 * Service double for generateDocument.
	 *
	 * @param string $model Fixture model
	 * @param Translate|string[] $langs Fixture langs
	 * @param int $details Fixture details
	 * @param int $desc Fixture desc
	 * @param string|int $ref Fixture ref
	 * @param array<string,mixed>|null $params Optional document parameters
	 * @return int
	 */
	public function generateDocument($model, $langs, $details, $desc, $ref, $params)
	{
		global $state;
		if ($params !== null && !is_array($params)) {
			throw new \InvalidArgumentException('Document parameters must match the native invoice contract.');
		}
		$state['regenerations']++;
		file_put_contents(DOL_DATA_ROOT.'/A/report.pdf', 'DUPLICATA');
		return 1;
	}
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps -- Native API name.
	/**
	 * Service double for call_trigger.
	 *
	 * @param string $action Fixture action
	 * @param object $user Fixture user
	 * @return int
	 */
	public function call_trigger($action, $user)
	{
		global $state;
		$state['triggers'][] = $action;
		return 1;
	}
	// phpcs:enable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
}

/**
 * Service double for GETPOST.
 *
 * @param string $key Fixture key
 * @param string $type Fixture type
 * @return string|array<string>
 */
function GETPOST($key, $type = '')
{
	$value = isset($_GET[$key]) ? $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : '');
	return $type === 'aZ09' && is_string($value) ? trim($value) : $value;
}

/**
 * Service double for GETPOSTINT.
 *
 * @param string $key Fixture key
 * @return int
 */
function GETPOSTINT($key)
{
	return (int) GETPOST($key);
}

/**
 * Service double for GETPOSTISSET.
 *
 * @param string $key Fixture key
 * @return bool
 */
function GETPOSTISSET($key)
{
	return isset($_POST[$key]) || isset($_GET[$key]);
}

/**
 * Service double for getDolGlobalString.
 *
 * @param string $key Fixture key
 * @return string
 */
function getDolGlobalString($key)
{
	return '';
}

/**
 * Service double for dol_sanitizePathName.
 *
 * @param string $path Fixture path
 * @return string
 */
function dol_sanitizePathName($path)
{
	return str_replace('../', '/', $path);
}

/**
 * Service double for dol_sanitizeFileName.
 *
 * @param string $file Fixture file
 * @return string
 */
function dol_sanitizeFileName($file)
{
	return basename($file);
}

/**
 * Service double for dol_check_secure_access_document.
 *
 * @param string $modulepart Fixture modulepart
 * @param string $file Fixture file
 * @param int $entity Fixture entity
 * @param object $user Fixture user
 * @param string|int $ref Fixture ref
 * @param string $mode Fixture mode
 * @return array{accessallowed:bool,sqlprotectagainstexternals:string,original_file:string}
 */
function dol_check_secure_access_document($modulepart, $file, $entity, $user, $ref, $mode)
{
	global $scenario;
	$allowedentity = isset($scenario['allowedentity']) ? $scenario['allowedentity'] : 2;
	return array('accessallowed' => empty($scenario['denymodule']) && $entity === $allowedentity, 'sqlprotectagainstexternals' => '', 'original_file' => DOL_DATA_ROOT.'/'.$file);
}

/**
 * Service double for fetchObjectByElement.
 *
 * @param int $id Fixture id
 * @param string $modulepart Fixture modulepart
 * @param string|int $ref Fixture ref
 * @return Facture|stdClass|false
 */
function fetchObjectByElement($id, $modulepart, $ref)
{
	global $scenario;
	if (isset($scenario['missingref']) && $scenario['missingref'] === $ref) {
		return false;
	}
	$object = $modulepart === 'facture' ? new Facture() : new stdClass();
	$object->ref = $ref;
	return $object;
}

/**
 * Service double for restrictedArea.
 *
 * @param object $user Fixture user
 * @param string $modulepart Fixture modulepart
 * @param object $object Fixture object
 * @return bool
 */
function restrictedArea($user, $modulepart, $object)
{
	global $scenario;
	return !isset($scenario['denyref']) || $scenario['denyref'] !== $object->ref;
}

/**
 * Service double for accessforbidden.
 *
 * @param string $message Fixture message
 * @return never
 */
function accessforbidden($message = '')
{
	http_response_code(403); exit('Forbidden');
}

/**
 * Service double for httponly_accessforbidden.
 *
 * @param string $message Fixture message
 * @param int $status Fixture status
 * @return never
 */
function httponly_accessforbidden($message, $status)
{
	http_response_code($status); exit($message);
}

/**
 * Service double for dol_syslog.
 *
 * @param string $message Fixture message
 * @param int $level Fixture level
 * @return void
 */
function dol_syslog($message, $level = 0)
{
}

/**
 * Service double for dol_escape_htmltag.
 *
 * @param string $text Fixture text
 * @return string
 */
function dol_escape_htmltag($text)
{
	return htmlspecialchars($text);
}

/**
 * Service double for dol_osencode.
 *
 * @param string $path Fixture path
 * @return string
 */
function dol_osencode($path)
{
	return $path;
}

/**
 * Service double for dol_mimetype.
 *
 * @param string $path Fixture path
 * @return string
 */
function dol_mimetype($path)
{
	return 'application/pdf';
}

/**
 * Service double for dolIsAllowedForPreview.
 *
 * @param string $path Fixture path
 * @return bool
 */
function dolIsAllowedForPreview($path)
{
	return true;
}

/**
 * Service double for dol_is_file.
 *
 * @param string $path Fixture path
 * @return bool
 */
function dol_is_file($path)
{
	return is_file($path);
}

/**
 * Service double for dol_is_dir.
 *
 * @param string $path Fixture path
 * @return bool
 */
function dol_is_dir($path)
{
	return is_dir($path);
}

/**
 * Service double for dol_mkdir.
 *
 * @param string $path Fixture path
 * @return int
 */
function dol_mkdir($path)
{
	return mkdir($path, 0700, true) ? 1 : -1;
}

/**
 * Service double for dol_delete_file.
 *
 * @param string $path Fixture path
 * @param array<int,int|null|bool> ...$options Fixture options
 * @return bool
 */
function dol_delete_file($path, ...$options)
{
	return unlink($path);
}

/**
 * Service double for dol_now.
 *
 * @return int
 */
function dol_now()
{
	return 1700000000;
}

/**
 * Service double for dol_print_date.
 *
 * @param int $timestamp Fixture timestamp
 * @param string $format Fixture format
 * @return string
 */
function dol_print_date($timestamp, $format)
{
	return '20231114-221320';
}

/**
 * Service double for top_httphead.
 *
 * @param string $type Fixture type
 * @return void
 */
function top_httphead($type = '')
{
}

/**
 * Service double for dol_filesize.
 *
 * @param string $path Fixture path
 * @return int|false
 */
function dol_filesize($path)
{
	return filesize($path);
}

/**
 * Service double for readfileLowMemory.
 *
 * @param string $path Fixture path
 * @return int|false
 */
function readfileLowMemory($path)
{
	return readfile($path);
}
require DOL_DOCUMENT_ROOT.'/document.php';
