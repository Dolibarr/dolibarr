<?php
/* Copyright (C) 2011 Regis Houssin	<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE FOR JQUERY -->
<script>
$(document).ready(function() {
	$(document).ready(function() {
		$('.edit_area').editable('<?php echo DOL_URL_ROOT.'/core/ajax/saveinplace.php'; ?>', {
			type		: 'textarea',
			rows		: 4,
			id			: 'field',
			tooltip		: '<?php echo $langs->trans('ClickToEdit'); ?>',
			cancel		: '<?php echo $langs->trans('Cancel'); ?>',
			submit		: '<?php echo $langs->trans('Ok'); ?>',
			indicator	: '<img src="<?php echo DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif"; ?>">',
			loadurl		: '<?php echo DOL_URL_ROOT.'/core/ajax/loadinplace.php'; ?>',
			loaddata	: { 
				table_element: "<?php echo $object->table_element; ?>",
				fk_element: "<?php echo $object->id; ?>"
			},
			submitdata	: { 
				table_element: "<?php echo $object->table_element; ?>",
				fk_element: "<?php echo $object->id; ?>"
			}
		});
	});
});
</script>
<!-- END PHP TEMPLATE FOR JQUERY -->