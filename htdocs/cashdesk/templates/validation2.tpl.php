<?php
$langs->load("main");
?>
<!--Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
-->
<h3 class="titre1"><?php echo $langs->trans("SellFinished"); ?></h3>

<div class="cadre_facturation">

<script type="text/javascript">

	function popupTicket () {

		largeur = 600;
		hauteur = 500
		opt = 'width='+largeur+', height='+hauteur+', left='+(screen.width - largeur)/2+', top='+(screen.height-hauteur)/2+'';
		window.open ('validation_ticket.php', 'Impression du ticket', opt);

	}

	popupTicket();

</script>

<p><a class="lien1" href="<?php echo DOL_URL_ROOT ?>/compta/facture.php?facid=<?php echo $_GET['facid']; ?>" target="_blank"><?php echo $langs->trans("ShowInvoice"); ?></a></p>
<p><a class="lien1" href="#" onclick="Javascript: popupTicket(); return(false);"><?php echo $langs->trans("PrintTicket"); ?></a></p>

</div>
