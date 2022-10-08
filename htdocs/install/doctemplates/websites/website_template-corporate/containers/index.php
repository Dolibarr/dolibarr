<?php
// BEGIN PHP File generated to provide an index.php as Home Page or alias redirector - DO NOT MODIFY - It is just a generated wrapper.
$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;
if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Load master if not already loaded
if (!empty($_GET['pageref']) || !empty($_GET['pagealiasalt']) || !empty($_GET['pageid'])) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';
	redirectToContainer($_GET['pageref'], $_GET['pagealiasalt'], $_GET['pageid']);
}
include_once './page189.tpl.php'
// END PHP ?>
