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
<fieldset class="cadre_facturation"><legend class="titre1">Résumé</legend>

	<table class="table_resume">

		<tr><td class="resume_label">Facture N°</td><td><?php  echo $obj_facturation->num_facture(); ?></td></tr>
		<tr><td class="resume_label">Prix HT :</td><td><?php echo price2num($obj_facturation->prix_total_ht()).' '.$conf->monnaie; ?></td></tr>
		<?php
			// Affichage de la tva par taux
			if ( $obj_facturation->montant_tva() ) {

				echo ('<tr><td class="resume_label">Montant TVA 19.6% :</td><td>'.price2num($obj_facturation->montant_tva()).' '.$conf->monnaie.'</td></tr>');

			}
			else
			{

				echo ('<tr><td class="resume_label">Montant TVA :</td><td>Pas de TVA pour cette vente</td></tr>');

			}
		?>
		<tr><td class="resume_label">A payer :</td><td><?php echo price2num($obj_facturation->prix_total_ttc()).' '.$conf->monnaie; ?></td></tr>
		<tr><td class="resume_label">Mode de réglement :</td><td><?php echo $obj_facturation->mode_reglement(); ?></td></tr>

		<?php

			// Affichage des infos en fonction du mode de paiement
			if ( $obj_facturation->mode_reglement() == 'DIF' ) {

				echo ('<tr><td class="resume_label">Paiement le :</td><td>'.$obj_facturation->paiement_le().'</td></tr>');

			} else {

				echo ('<tr><td class="resume_label">Encaissé :</td><td>'.price2num($obj_facturation->montant_encaisse()).' '.$conf->monnaie.'</td></tr>');

			}

			// Affichage du montant rendu (réglement en espèces)
			if ( $obj_facturation->montant_rendu() ) {

				echo ('<tr><td class="resume_label">Rendu :</td><td>'.price2num($obj_facturation->montant_rendu()).' '.$conf->monnaie.'</td></tr>');

			}

		?>

	</table>

	<form id="frmValidation" class="formulaire2" method="post" action="validation_verif.php?action=valide_facture">
		<input type="hidden" name="token_level_1" value="<?php echo $_SESSION['newtoken']; ?>" />

		<p class="note_label">Notes<br /><textarea class="textarea_note" name="txtaNotes"></textarea></p>

		<span><input class="bouton_validation" type="submit" name="btnValider" value="Valider la facture" /></span>
		<p><a class="lien1" href="affIndex.php?menu=facturation">Reprendre la vente</a></p>
	</form>



</fieldset>
