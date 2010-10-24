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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
$this->smartheader();
?>

<!-- START MENU SMARTPHONE TEMPLATE -->

<div data-role="page" id="dol-home">
	
	<div data-role="header" data-nobackbtn="true" data-theme="b">
		<div id="dol-homeheader">
			<img src="<?php echo DOL_URL_ROOT.'/theme/phones/smartphone/theme/'.$this->theme.'/thumbs/dolibarr.png'; ?>">
		</div>
	</div>

	<div data-role="content">

	<?php $menusmart->showmenu(); ?>
	
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

<?php $this->smartfooter(); ?>