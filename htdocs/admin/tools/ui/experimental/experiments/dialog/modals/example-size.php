<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/admin/tools/ui/class/documentation.class.php';

/**
 * @var Translate $langs
 * @var User $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation'); ?>

<h4 class="nomarginbottom nomargintop">A lot of text</h4>
<p class="nomargintop"><?php print Documentation::generateLoremIpsum(1, 100, false); ?></p>

<?php print Documentation::generateLoremIpsum(10, 70, true); ?>

<div class="dol-dialog-footer">
	<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>
</div>
