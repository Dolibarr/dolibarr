<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
}

/**
 * @var string $path
 */
'
@phan-var-force string $path
';

include dol_buildpath($path.'/theme/eldy/progress.inc.php', 0); // actually md use same style as eldy theme
