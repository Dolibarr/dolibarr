<?php
/* Copyright (C) 2017-2019	Regis Houssin	<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */

dol_include_once('/multicompany/admin/tpl/switch.tpl.php');

$colHidden = (! empty($conf->global->MULTICOMPANY_COLHIDDEN) ? implode(",", json_decode($conf->global->MULTICOMPANY_COLHIDDEN, true)) : null);
$colOrder = (! empty($conf->global->MULTICOMPANY_COLORDER) ? json_decode($conf->global->MULTICOMPANY_COLORDER, true) : array('id' => 0, 'direction' => 'asc'));

$columns = array();

$columns['id'] = array(
	'label'			=> 'ID',
	'sortable'		=> true,
	'searchable'	=> true,
	'priority'		=> 12,
	'center'		=> 'dt-center'
);
$columns['label'] = array(
	'label'			=> 'Label',
	'sortable'		=> true,
	'searchable'	=> true,
	'priority'		=> 1,
);
$columns['description'] = array(
	'label'			=> 'Description',
	'sortable'		=> true,
	'searchable'	=> true,
	'priority'		=> 11,
);
$columns['name'] = array(
	'label'			=> 'Name',
	'priority'		=> 10,
	'center'		=> 'dt-center'
);
$columns['zip'] = array(
	'label'			=> 'Zip',
	'priority'		=> 9,
	'center'		=> 'dt-center'
);
$columns['town'] = array(
	'label'			=> 'Town',
	'priority'		=> 8,
	'center'		=> 'dt-center'
);
$columns['country'] = array(
	'label'			=> 'Country',
	'priority'		=> 7,
	'center'		=> 'dt-center'
);
$columns['currency'] = array(
	'label'			=> 'Currency',
	'priority'		=> 6,
	'center'		=> 'dt-center'
);
$columns['language'] = array(
	'label'			=> 'DefaultLanguageShort',
	'priority'		=> 5,
	'center'		=> 'dt-center'
);
if (! empty($this->tpl['extrafields']->attribute_label)) {
	foreach ($this->tpl['extrafields']->attribute_label as $key => $value)
	{
		$columns[$key] = array(
			'label'		=> $value,
			'center'	=> 'dt-center'
		);
	}
}
$columns['visible'] = array(
	'label'			=> 'Visible',
	'width'			=> '20px',
	'priority'		=> 3,
	'center'		=> 'dt-center'
);
$columns['active'] = array(
	'label'			=> 'Status',
	'width'			=> '20px',
	'priority'		=> 2,
	'center'		=> 'dt-center'
);
$columns['tools'] = array(
	'label'			=> 'Tools',
	'width'			=> '50px',
	'priority'		=> 4,
	'center'		=> 'dt-center'
);

//var_dump($columns);

?>

<!-- BEGIN PHP TEMPLATE -->
<script type="text/javascript">
$(document).ready(function() {
	$("#multicompany_entity_list").dataTable( {
		"dom": 'B<"clear">lfrtip',
		//"responsive": true,
		"buttons": [
			{
				"extend": "colvis",
				"text": "<?php echo $langs->transnoentities('ShowHideColumns'); ?>"
			}
		],
		"pagingType": "full_numbers",
		"columns": [
			<?php foreach($columns as $key => $values) { ?>
			{
				"name": "entity_<?php echo $key; ?>",
				"data": "entity_<?php echo $key; ?>",
				<?php if (! empty($values['sortable'])) { ?>
				"sortable": true,
				<?php } ?>
				<?php if (! empty($values['searchable'])) { ?>
				"searchable": true,
				<?php } ?>
				<?php if (! empty($values['width'])) { ?>
				"width": "<?php echo $values['width']; ?>",
				<?php } ?>
				<?php if (! empty($values['center'])) { ?>
				"class": "<?php echo $values['center']; ?>",
				<?php } ?>
				<?php //if (! empty($values['priority'])) { ?>
				//"responsivePriority": <?php //echo $values['priority']; ?>,
				<?php //} ?>
			},
			<?php } ?>
		  ],
		"columnDefs": [
			{ "targets": '_all', "sortable": false },
			{ "targets": '_all', "searchable": false },
			<?php if (! empty($colHidden)) { ?>
			{ "visible": false, "targets": [ <?php echo $colHidden; ?> ] }
			<?php } ?>
		],
		"language": {
			"lengthMenu": "<?php echo $langs->transnoentities('Showing'); ?> _MENU_ <?php echo $langs->transnoentities('LineEntries'); ?>",
			"search": "<?php echo $langs->transnoentities('Search'); ?>:",
			"processing": "<?php echo $langs->transnoentities('Processing'); ?>",
			"zeroRecords": "<?php echo $langs->transnoentities('NoRecordsToDisplay'); ?>",
			"infoEmpty": "<?php echo $langs->transnoentities('NoEntriesToShow'); ?>",
			"infoFiltered": "(<?php echo $langs->transnoentities('FilteredFrom'); ?> _MAX_ <?php echo $langs->transnoentities('TotalEntries'); ?>)",
			"info": "<?php echo $langs->transnoentities('ShowingOf'); ?> _START_ <?php echo $langs->transnoentities('To'); ?> _END_ <?php echo $langs->transnoentities('TotalOf'); ?> _TOTAL_ <?php echo $langs->transnoentities('LineEntries'); ?>",
			"paginate": {
				"first": "<?php echo $langs->transnoentities('First'); ?>",
				"last": "<?php echo $langs->transnoentities('Last'); ?>",
				"previous": "<?php echo $langs->transnoentities('Previous'); ?>",
				"next": "<?php echo $langs->transnoentities('Next'); ?>"
			}
		},
		"processing": true,
		"serverSide": true,
		"deferRender": true,
		"pageLength": 25,
		<?php if (! empty($colOrder)) { ?>
		"order": [[ <?php echo $colOrder['id']; ?>,"<?php echo $colOrder['direction']; ?>" ]],
		<?php } ?>
		"ajax": {
			"url": "<?php echo dol_buildpath('/multicompany/core/ajax/list.php', 1); ?>",
			"type": "POST"
		}
	});
	$('#multicompany_entity_list').on( 'order.dt', function ( e, settings, column ) {
		//console.log(column);
		var newid = column[0]['col'];
		var newdir = column[0]['dir'];
		var currentid = <?php echo $colOrder['id']; ?>;
		var currentdir = "<?php echo $colOrder['direction']; ?>";
		if (currentid != newid || currentdir != newdir) {
			$.get( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
				"action" : "setColOrder",
				"id" : newid,
				"dir" : newdir
				},
				function (result) {

				}
			);
		}
	});
	$('#multicompany_entity_list').on( 'column-visibility.dt', function ( e, settings, column, state ) {
		//console.log('Column '+ column +' has changed to '+ (state ? 'visible' : 'hidden'));
		$.get( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
			"action" : "setColHidden",
			"id" : column,
			"state" : (state ? 'visible' : 'hidden')
			},
			function (result) {

			}
		);
	});
});
</script>
<table width="100%" id="multicompany_entity_list">
	<thead>
		<tr>
			<?php
			foreach($columns as $key => $values) {
				//$moreattr = (! empty($values['priority'])?'data-priority="'.$values['priority'].'"':'');
				echo getTitleFieldOfList($values['label'], 1, '', '', '', '', $moreattr, '', '', 'entity_' . $key . ' ');
			}
			?>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="5" class="dataTables_empty"><?php echo $langs->trans('LoadingDataFromServer'); ?></td>
		</tr>
	</tbody>
</table></div>
<div class="tabsAction">
<a class="butAction" href="<?php echo $_SERVER["PHP_SELF"]; ?>?action=create"><?php echo $langs->trans('AddEntity'); ?></a>
</div>
<!-- END PHP TEMPLATE -->