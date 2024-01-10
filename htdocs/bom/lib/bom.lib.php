<?php
/* Copyright (C) 2019       Maxime Kohlhaas         <maxime@atm-consulting.fr>
 * Copyright (C) 2019-2023  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/bom/lib/bom.lib.php
 * \ingroup bom
 * \brief   Library files with common functions for BillOfMaterials
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function bomAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("mrp");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/bom.php";
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/bom_extrafields.php";
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'bom_extrafields';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/bomline_extrafields.php";
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$head[$h][2] = 'bomline_extrafields';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@bom:/bom/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@bom:/bom/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'bom@mrp');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'bom@mrp', 'remove');

	return $head;
}




/**
 * Prepare array of tabs for BillOfMaterials
 *
 * @param	BOM	      $object		BillOfMaterials
 * @return 	array					Array of tabs
 */
function bomPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("mrp");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/bom/bom_card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("BOM");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/bom/bom_net_needs.php?id=".$object->id;
	$head[$h][1] = $langs->trans("BOMNetNeeds");
	$head[$h][2] = 'net_needs';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/bom/bom_note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->bom->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/bom/bom_document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>' : '');
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/bom/bom_agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@bom:/bom/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@bom:/bom/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bom');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bom', 'remove');

	return $head;
}

/**
 * Manage collapse bom display
 *
 * @return void
 */
function mrpCollapseBomManagement()
{
	?>

	<script type="text/javascript" language="javascript">

		$(document).ready(function () {
			function folderManage(element, onClose = 0) {
				let id_bom_line = element.attr('id').replace('collapse-', '');
				let TSubLines = $('[parentid="'+ id_bom_line +'"]');

				if(element.html().indexOf('folder-open') <= 0 && onClose < 1) {
					$('[parentid="'+ id_bom_line +'"]').show();
					element.html('<?php echo dol_escape_js(img_picto('', 'folder-open')); ?>');
				}
				else {
					for (let i = 0; i < TSubLines.length; i++) {
						let subBomFolder = $(TSubLines[i]).children('.linecoldescription').children('.collapse_bom');

						if (subBomFolder.length > 0) {
							onClose = 1
							folderManage(subBomFolder, onClose);
						}
					}
					TSubLines.hide();
					element.html('<?php echo dol_escape_js(img_picto('', 'folder')); ?>');
				}
			}

			// When clicking on collapse
			$(".collapse_bom").click(function() {
				folderManage($(this));
				return false;
			});

			// To Show all the sub bom lines
			$("#show_all").click(function() {
				console.log("We click on show all");
				$("[class^=sub_bom_lines]").show();
				$("[class^=collapse_bom]").html('<?php echo dol_escape_js(img_picto('', 'folder-open')); ?>');
				return false;
			});

			// To Hide all the sub bom lines
			$("#hide_all").click(function() {
				console.log("We click on hide all");
				$("[class^=sub_bom_lines]").hide();
				$("[class^=collapse_bom]").html('<?php echo dol_escape_js(img_picto('', 'folder')); ?>');
				return false;
			});
		});

	</script>

	<?php
}
