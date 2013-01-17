<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
?>

<!-- BEGIN PHP TEMPLATE admin_extrafields.tpl.php -->
<script type="text/javascript">
    jQuery(document).ready(function() {
    	function init_typeoffields(type)
    	{
    		var size = jQuery("#size");
    		var unique = jQuery("#unique");
    		var required = jQuery("#required");
    		if (type == 'date') { size.val('').attr('disabled','disabled'); }
    		else if (type == 'datetime') { size.val('').attr('disabled','disabled'); }
    		else if (type == 'double') { size.val('24,8').removeAttr('disabled'); }
    		else if (type == 'int') { size.val('10').removeAttr('disabled'); }
    		else if (type == 'text') { size.val('2000').removeAttr('disabled'); }
    		else if (type == 'varchar') { size.val('255').removeAttr('disabled'); }
    		else if (type == 'boolean') { size.val('').attr('disabled','disabled'); unique.attr('disabled','disabled');}
    		else if (type == 'price') { size.val('').attr('disabled','disabled'); unique.attr('disabled','disabled');}
    		else size.val('').attr('disabled','disabled');
    	}
    	init_typeoffields();
    	jQuery("#type").change(function() {
    		init_typeoffields($(this).val());
    	});
    });
</script>
<!-- END PHP TEMPLATE admin_extrafields.tpl.php -->
