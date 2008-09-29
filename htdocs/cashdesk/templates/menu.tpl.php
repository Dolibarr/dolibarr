<!--Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>

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
<div class="menu_bloc">
	<ul class="menu">
		<li class="menu_choix1"><a href="affIndex.php?menu=facturation&id=NOUV"><span>Nouvelle vente</span></a></li>
		<li class="menu_choix2"><a href="<?php echo eregi_replace('/cashdesk','',$conf_url_racine); ?>/"><span>Gestion commerciale</span></a></li>
		<li class="menu_choix0"><a href="deconnexion.php"><span title="Cliquez pour quitter la session">Utilisateur : <?php echo $_SESSION['prenom'].' '.$_SESSION['nom']; ?></span></a></li>
	</ul>
</div>
