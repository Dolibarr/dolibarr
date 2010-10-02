<?php
/* Copyright (C) 2010 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
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
		   var roworder = cleanSerialize(jQuery("#tablelines").tableDnDSerialize());
		   var element = "<?php echo $object->table_element_line; ?>";
		   jQuery.get("<?php echo DOL_URL_ROOT; ?>/core/ajaxrow.php?roworder="+roworder+"&element="+element);
		   jQuery("#tablelines .drag").each(
				function( intIndex ){
					jQuery(this).removeClass("pair impair");
					if (intIndex % 2 == 0) jQuery(this).addClass('impair');
                    if (intIndex % 2 == 1) jQuery(this).addClass('pair');
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