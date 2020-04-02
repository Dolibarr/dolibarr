<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/affPied.php
 *	\ingroup    cashdesk
 *	\brief      Bottom of main page of point of sale module
 */

?>
<!-- affPied.php -->
<div class="pied">
<?php

// Wrapper to show tooltips
if (! empty($conf->use_javascript_ajax) && empty($conf->dol_no_mouse_hover))
{
	print "\n<!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip -->\n";
	print '<script type="text/javascript">
    	jQuery(document).ready(function () {
			jQuery(".classfortooltip").tooltip({
				show: { collision: "flipfit", effect:\'toggle\', delay:50 },
				hide: { effect:\'toggle\', delay: 50 },
				tooltipClass: "mytooltip",
				content: function () {
					return $(this).prop(\'title\');		/* To force to get title as is */
				}
			});
		});
	</script>' . "\n";
}

printCommonFooter('private');
?>
</div>
