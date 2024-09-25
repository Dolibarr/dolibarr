<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 *
 * This program and files/directory inner it is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program.  If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) : $res=@include '../main.inc.php';
endif;
if (! $res && file_exists("../../main.inc.php")) : $res=@include '../../main.inc.php';
endif;

// Protection if external user
if ($user->socid > 0) : accessforbidden();
endif;

// Includes
dol_include_once('documentation/class/documentation.class.php');

// Load documentation translations
$langs->load('documentation@documentation');

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader();

// Set view for menu and breadcrumb
$documentation->view = array('DocumentationHome');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">
		
	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper"></div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>