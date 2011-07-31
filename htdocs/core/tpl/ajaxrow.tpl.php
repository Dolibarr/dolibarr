<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
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
 * $Id: ajaxrow.tpl.php,v 1.15 2011/07/31 23:45:12 eldy Exp $
 */
?>

<!-- BEGIN PHP TEMPLATE FOR JQUERY -->
<?php if (count($object->lines) > 1 && $_GET['action'] != 'editline') { ?>
<script>
jQuery(document).ready(function(){
	jQuery(".imgup").hide();
	jQuery(".imgdown").hide();
    jQuery(".lineupdown").removeAttr('href');
    jQuery(".tdlineupdown").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
    jQuery(".tdlineupdown").css("background-repeat","no-repeat");
    jQuery(".tdlineupdown").css("background-position","center center");

    jQuery("#tablelines").tableDnD({
		onDrop: function(table, row) {
			var reloadpage = "<?php echo $conf->global->MAIN_FORCE_RELOAD_PAGE; ?>";
			var roworder = cleanSerialize(jQuery("#tablelines").tableDnDSerialize());
			var table_element_line = "<?php echo $object->table_element_line; ?>";
			var fk_element = "<?php echo $object->fk_element; ?>";
			var element_id = "<?php echo $object->id; ?>";
			jQuery.get("<?php echo DOL_URL_ROOT; ?>/core/ajaxrow.php",
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
							jQuery("#tablelines .drag").each(
									function( intIndex ) {
										jQuery(this).removeClass("pair impair");
										if (intIndex % 2 == 0) jQuery(this).addClass('impair');
										if (intIndex % 2 == 1) jQuery(this).addClass('pair');
									});
						}
					});
		},
		onDragClass: "dragClass",
		dragHandle: "tdlineupdown"
	});
    jQuery(".tdlineupdown").hover(
    	function() { jQuery(this).addClass('showDragHandle'); },
    	function() { jQuery(this).removeClass('showDragHandle'); }
    );
});
</script>
<?php } else { ?>
<script>
jQuery(document).ready(function(){
	jQuery(".imgup").hide();
	jQuery(".imgdown").hide();
    jQuery(".lineupdown").removeAttr('href');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE -->