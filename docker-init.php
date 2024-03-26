#!/usr/bin/env php
<?php
require_once '../htdocs/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/modUser.class.php';
$mod = new modUser($db);
$mod->init();
