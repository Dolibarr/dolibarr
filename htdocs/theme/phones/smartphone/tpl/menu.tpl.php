<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
 * $Id: menu.tpl.php,v 1.18 2011/07/31 23:19:28 eldy Exp $
 */
top_httphead();
?>
<!DOCTYPE html>
<html>
<?php 
require('header.tpl.php');
?>
<body>
<script type="text/javascript">
jQuery(document).bind("mobileinit", function(){
    jQuery.mobile.defaultTransition('pop');
});
</script>

<div data-role="page" data-theme="b" id="dol-home">

	<div data-role="header" data-nobackbtn="true" data-theme="b">
		<div id="dol-homeheader">
            <?php
            $appli='Dolibarr';
            if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;
            print $appli;
            ?>
		</div>
	</div>

    <div data-role="content">
<!--
        <ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="b">
            <li data-role="list-divider">Overview</li>
            <li><a href="http://jquerymobile.com/test/docs/about/intro.html">Intro to jQuery Mobile</a></li>
            <li><a href="http://localhost/dolibarrnew/public/error-401.php">Features</a></li>
        </ul>
-->

	<?php $menusmart->showmenu($limitmenuto); ?>

	</div><!-- /content -->

	<div data-role="footer" data-theme="b">
		<div data-role="navbar">
			<ul>
				<li><a href="<?php echo DOL_URL_ROOT.'/user/logout.php'; ?>" data-icon="grid"><?php echo $langs->trans("Logout"); ?></a></li>
				<li><a href="">&nbsp;</a></li>
			</ul>
		</div><!-- /navbar -->
	</div><!-- /footer -->

</div><!-- /page -->

<!-- END MENU SMARTPHONE TEMPLATE -->
</body>
</html>
