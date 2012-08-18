<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2012       Marcos García       <marcosgdf@gmail.com>
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

$langs->load("main");

?>

<h3 class="titre1"><?php echo $langs->trans("SellFinished"); ?></h3>

<div class="cadre_facturation">

<script type="text/javascript">

	function popupTicket()
	{
		largeur = 600;
		hauteur = 500
		opt = 'width='+largeur+', height='+hauteur+', left='+(screen.width - largeur)/2+', top='+(screen.height-hauteur)/2+'';
		window.open('validation_ticket.php?facid=<?php echo $_GET['facid']; ?>', '<?php echo $langs->trans('PrintTicket') ?>', opt);
	}

	popupTicket();

</script>

<p><a class="lien1" href="<?php echo DOL_URL_ROOT ?>/compta/facture.php?action=builddoc&facid=<?php echo $_GET['facid']; ?>" target="_blank"><?php echo $langs->trans("ShowInvoice"); ?></a></p>
<br>
<p><a class="lien1" href="#" onclick="Javascript: popupTicket(); return(false);"><?php echo $langs->trans("PrintTicket"); ?></a></p>

</div>
