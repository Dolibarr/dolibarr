<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var Translate $langs
 * @var User $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation'); ?>

<p class="nomargintop">Hey <b><?php echo $user->login; ?></b>, I'm the new native HTML dialog. I'm here to replace those old jQuery UI modals.</p>

<h4 class="nomarginbottom">Positioning</h4>
<p class="nomargintop">You can display me centered or anchored to the right, depending on your needs.</p>

<h4 class="nomarginbottom">Header & Footer</h4>
<p class="nomargintop">My header and footer are fully customizable. Remove them entirely if you don't need them.</p>

<h4 class="nomarginbottom">Need to pass data?</h4>
<p class="nomargintop">Just use data attributes, it's that simple.</p>

<h4 class="nomarginbottom">Form handling</h4>
<p class="nomargintop">I handle forms with ease, whether you prefer Ajax or standard submissions.</p>

<h4 class="nomarginbottom">Advanced options</h4>
<p class="nomargintop">I also come with cool options like size definitions and HTML persistence in the DOM.</p>

<h4 class="nomarginbottom">Advanced options</h4>
<p class="nomargintop">Want to know everything I can do? Check out the documentation!</p>