<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 */
?>

<!-- BEGIN PHP TEMPLATE admin_extrafields.tpl.php -->
<script type="text/javascript">
    jQuery(document).ready(function() {
    	function init_typeoffields()
    	{
    		if (jQuery("#type").val() == 'date') { jQuery("#size").val(''); jQuery("#size").attr('disabled','disabled'); }
    		else if (jQuery("#type").val() == 'datetime') { jQuery("#size").val(''); jQuery("#size").attr('disabled','disabled'); }
    		else if (jQuery("#type").val() == 'double') { jQuery("#size").val('24,8'); jQuery("#size").removeAttr('disabled'); }
    		else if (jQuery("#type").val() == 'int') { jQuery("#size").val('10'); jQuery("#size").removeAttr('disabled'); }
    		else if (jQuery("#type").val() == 'text') { jQuery("#size").val('2000'); jQuery("#size").removeAttr('disabled'); }
    		else if (jQuery("#type").val() == 'varchar') { jQuery("#size").val('255'); jQuery("#size").removeAttr('disabled'); }
    		else if (jQuery("#type").val() == '') { jQuery("#size").val(''); jQuery("#size").attr('disabled','disabled'); }
    		else jQuery("#size").attr('disabled','disabled');
    	}
    	init_typeoffields();
    	jQuery("#type").change(function() {
    		init_typeoffields();
    	});
    });
</script>
<!-- END PHP TEMPLATE admin_extrafields.tpl.php -->
