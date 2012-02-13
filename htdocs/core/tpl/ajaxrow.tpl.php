<?php
/* Copyright (C) 2010-2012 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
<?php if (count($object->lines) > 1 && $_GET['action'] != 'editline') { ?>
<script>
$(document).ready(function(){
	$(".imgup").hide();
	$(".imgdown").hide();
    $(".lineupdown").removeAttr('href');
    $(".tdlineupdown").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
    $(".tdlineupdown").css("background-repeat","no-repeat");
    $(".tdlineupdown").css("background-position","center center");

    $("#tablelines").tableDnD({
		onDrop: function(table, row) {
			var reloadpage = "<?php echo $conf->global->MAIN_FORCE_RELOAD_PAGE; ?>";
			var roworder = cleanSerialize($("#tablelines").tableDnDSerialize());
			var table_element_line = "<?php echo $object->table_element_line; ?>";
			var fk_element = "<?php echo $object->fk_element; ?>";
			var element_id = "<?php echo $object->id; ?>";
			$.post("<?php echo DOL_URL_ROOT; ?>/core/ajax/row.php",
					{
						roworder: roworder,
						table_element_line: table_element_line,
						fk_element: fk_element,
						element_id: element_id
					},
					function() {
						if (reloadpage == 1) {
							location.href = '<?php echo $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']; ?>';
						} else {
							$("#tablelines .drag").each(
									function( intIndex ) {
										$(this).removeClass("pair impair");
										if (intIndex % 2 == 0) $(this).addClass('impair');
										if (intIndex % 2 == 1) $(this).addClass('pair');
									});
						}
					});
		},
		onDragClass: "dragClass",
		dragHandle: "tdlineupdown"
	});
    $(".tdlineupdown").hover( function() { $(this).addClass('showDragHandle'); },
    	function() { $(this).removeClass('showDragHandle'); }
    );
});
</script>
<?php } else { ?>
<script>
$(document).ready(function(){
	$(".imgup").hide();
	$(".imgdown").hide();
    $(".lineupdown").removeAttr('href');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE -->