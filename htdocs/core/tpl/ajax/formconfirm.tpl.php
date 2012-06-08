<?php
/* Copyright (C) 2011-2012 Regis Houssin <regis@dolibarr.fr>
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

<!-- START TEMPLATE FORMCONFIRM PAGE="<?php echo $page; ?>" -->
<div id="<?php echo $dialogconfirm; ?>" title="<?php echo dol_escape_htmltag($title); ?>" style="display: none;">
	<?php if (! empty($more)) { ?>
	<p><?php echo $more; ?></p>
	<?php } ?>
	<?php echo img_help('','').' '.$question; ?>
</div>
<script type="text/javascript">
$(function() {
	$( "#<?php echo $dialogconfirm; ?>" ).dialog({
        autoOpen: <?php echo ($autoOpen ? 'true' : 'false'); ?>,
        resizable: false,
        height: '<?php echo $height; ?>',
        width: '<?php echo $width; ?>',
        modal: true,
        closeOnEscape: false,
        buttons: {
            '<?php echo dol_escape_js($langs->transnoentities("Yes")); ?>': function() {
            	var options="";
             	var inputok = <?php echo json_encode($inputok); ?>;
             	var pageyes = '<?php echo dol_escape_js($pageyes?$pageyes:''); ?>';
             	if (inputok.length>0) {
             		$.each(inputok, function(i, inputname) {
             			var more = '';
             			if ($("#" + inputname).attr("type") == 'checkbox') { more = ':checked'; }
             			var inputvalue = $("#" + inputname + more).val();
             			if (typeof inputvalue == 'undefined') { inputvalue=''; }
             			options += '&' + inputname + '=' + inputvalue;
             		});
             	}
             	var urljump = pageyes + (pageyes.indexOf('?') < 0 ? '?' : '') + options;
             	//alert(urljump);
				if (pageyes.length > 0) { location.href = urljump; }
                $(this).dialog('close');
            },
            '<?php echo dol_escape_js($langs->transnoentities("No")); ?>': function() {
            	var options = '';
             	var inputko = <?php echo json_encode($inputko); ?>;
             	var pageno='<?php echo dol_escape_js($pageno?$pageno:''); ?>';
             	if (inputko.length>0) {
             		$.each(inputko, function(i, inputname) {
             			var more = '';
             			if ($("#" + inputname).attr("type") == 'checkbox') { more = ':checked'; }
             			var inputvalue = $("#" + inputname + more).val();
             			if (typeof inputvalue == 'undefined') { inputvalue=''; }
             			options += '&' + inputname + '=' + inputvalue;
             		});
             	}
             	var urljump=pageno + (pageno.indexOf('?') < 0 ? '?' : '') + options;
             	//alert(urljump);
				if (pageno.length > 0) { location.href = urljump; }
                $(this).dialog('close');
            }
        }
    });

	var button = '<?php echo $button; ?>';
    if (button.length > 0) {
    	$( "#" + button ).click(function() {
    		$("#<?php echo $dialogconfirm; ?>").dialog('open');
    	});
    }
});
</script>
<!-- END TEMPLATE FORM CONFIRM -->