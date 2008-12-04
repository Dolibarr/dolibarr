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
<script type="text/javascript" src="javascript/facturation1.js"></script>
<script type="text/javascript" src="javascript/dhtml.js"></script>

<!-- ========================= Cadre "Article" ============================= -->
<fieldset class="cadre_facturation"><legend class="titre1">Article</legend>
	<form id="frmFacturation" class="formulaire1" method="post" action="facturation_verif.php">

		<input type="hidden" name="hdnSource" value="NULL" />

		<table>
			<tr><th class="label1">Code</th><th class="label1">Désignation</th></tr>
			<tr>
			<!-- Affichage de la référence et de la désignation -->
			<td><input class="texte_ref" type="text" id ="txtRef" name="txtRef" autocomplete="off" value="<?php echo $obj_facturation->ref() ?>"
				onchange="javascript: setSource('REF');"
				onfocus="javascript: this.select(); verifResultat('resultats_dhtml', this.value);"
				onBlur="javascript: document.getElementById('resultats_dhtml').innerHTML = '';"
				onKeyUp="javascript: verifResultat('resultats_dhtml', this.value);" />
			</td>
			<td class="select_design">
				<select name="selProduit" onchange="javascript: setSource('LISTE');">
					<option value="0" class="top_liste"><?php echo $top_liste_produits; ?></option>
					<?php

						$id = $obj_facturation->id();

						// Si trop d'articles ont été trouvés, on n'affiche que les X premiers (défini dans le fichier de configuration) ...
						if ( $nbr_enreg > $conf_taille_listes ) {

							for ($i = 0; $i < $conf_taille_listes; $i++) {

								if ( $id == $tab_designations[$i]['rowid'] )
									$selected = 'selected="selected"';
								else
									$selected = '';

								// On coupe la ligne si elle contient plus de $conf_nbr_car_listes caractères
								if ( strlen ($tab_designations[$i]['label']) > $conf_nbr_car_listes ) {

									$label = substr ($tab_designations[$i]['label'], 0, $conf_nbr_car_listes - 3);
									$label .= '...';

								} else {

									$label = $tab_designations[$i]['label'];

								}

								echo ('<option '.$selected.' value="'.$tab_designations[$i]['rowid'].'">'.$tab_designations[$i]['ref'].' - '.$label.'</option>'."\n				");

							}

						// ... sinon on affiche tout
						} else {

							for ($i = 0; $i < $nbr_enreg; $i++) {

								if ( $id == $tab_designations[$i]['rowid'] )
									$selected = 'selected="selected"';
								else
									$selected = '';

								// On coupe la ligne si elle contient plus de $conf_nbr_car_listes caractères
								if ( strlen ($tab_designations[$i]['label']) > $conf_nbr_car_listes ) {

									$label = substr ($tab_designations[$i]['label'], 0, $conf_nbr_car_listes - 3);
									$label .= '...';

								} else {

									$label = $tab_designations[$i]['label'];

								}

								echo ('<option '.$selected.' value="'.$tab_designations[$i]['rowid'].'">'.$tab_designations[$i]['ref'].' - '.$label.'</option>'."\n				");

							}

						}

					?>
				</select>
			</td>
			</tr>
			<tr><td><div id="resultats_dhtml"></div></td></tr>
		</table>
	</form>

	<form id="frmQte" class="formulaire1" method="post" action="facturation_verif.php?action=ajout_article" onsubmit ="javascript: return verifSaisie();">
		<table>
			<tr><th class="label1">Quantité</th><th class="label1">Stock</th><th class="label1">Prix unitaire</th><th></th><th class="label1">Taux TVA</th><th class="label1">Remise (%)</th><th class="label1">Prix total</th></tr>
			<tr>
				<td><input class="texte1" type="text" name="txtQte" value="1" onkeyup="javascript: modif();" onfocus="javascript: this.select();" /></td>
				<!-- Affichage du stock pour l'article courant -->
				<td><input class="texte1_off" type="text" name="txtStock" value="<?php echo $obj_facturation->stock() ?>" disabled="disabled" /></td>
				<!-- Affichage du prix unitaire -->
				<td><input class="texte1_off" type="text" name="txtPrixUnit" value="<?php echo number_format( $obj_facturation->prix(), 2, '.', '') ?>" disabled="disabled" /></td>
				<td>&#8364;</td>
				<!-- Choix du taux de TVA -->
				<td class="select_tva">
				<select name="selTva" onchange="javascript: modif();" >
					<?php

						$tva_tx = $obj_facturation->tva();
						for ($i = 0; $i < count ($tab_tva); $i++) {

							if ( $tva_tx == $tab_tva[$i]['taux'] )
								$selected = 'selected="selected"';
							else
								$selected = '';

							echo ('<option '.$selected.' value="'.$tab_tva[$i]['rowid'].'">'.$tab_tva[$i]['taux'].'</option>'."\n				");

						}

					?>
				</select>
			</td>
			<!-- Choix de la remise -->
			<td><input class="texte1" type="text" name="txtRemise" value="0" onkeyup="javascript: modif();" onfocus="javascript: this.select();"/></td>
			<!-- Affichage du total HT -->
			<td><input class="texte1_off" type="text" name="txtTotal" value="" disabled="disabled" /></td><td>&#8364;</td>
			</tr>
		</table>

		<input class="bouton_ajout_article" type="submit" id="sbmtEnvoyer" value="Ajouter cet article" />
	</form>
</fieldset>

<!-- ========================= Cadre "Différence" ============================= -->
<form id="frmDifference"  class="formulaire1" method="post" onsubmit="javascript: return verifReglement()" action="validation_verif.php?action=valide_achat">
<fieldset class="cadre_facturation"><legend class="titre1">Différence</legend>
		<table>
			<tr><th class="label1">Montant dû</th><th class="label1">Encaissé</th><th class="label1">Rendu</th></tr>
			<tr>
			<!-- Affichage du montant dû -->
			<td><input class="texte2_off" type="text" name="txtDu" value="<?php echo number_format ($obj_facturation->prix_total_ttc (), 2, '.', ''); ?>" disabled="disabled" /></td>
			<!-- Choix du montant encaissé -->
			<td><input class="texte2" type="text" name="txtEncaisse" value="" onkeyup="javascript: verifDifference();" onfocus="javascript: this.select();" /></td>
			<!-- Affichage du montant rendu -->
			<td><input class="texte2_off" type="text" name="txtRendu" value="0" disabled="disabled" /></td>
			</tr>
			<tr>
		</table>
</fieldset>

<fieldset class="cadre_facturation"><legend class="titre1">Mode de paiement</legend>
		<table>
			<input type="hidden" name="hdnChoix" value="" />
			<tr>
			<td><input class="bouton_mode_reglement" type="submit" name="btnModeReglement" value="Espèces" onclick="javascript: verifClic('ESP');" /></td>
			<td><input class="bouton_mode_reglement" type="submit" name="btnModeReglement" value="Carte" onclick="javascript: verifClic('CB');" /></td>
			<td><input class="bouton_mode_reglement" type="submit" name="btnModeReglement" value="Chèque" onclick="javascript: verifClic('CHQ');" /></td>
			</tr>
		</table>
		<table>
			<tr>
				<td>
				<input class="bouton_mode_reglement" type="submit" name="btnModeReglement" value="Différé" onclick="javascript: verifClic('DIF');" />
				Date limite de paiement :
				<input class="texte2" type="text" id="txtDatePaiement" name="txtDatePaiement" value="" />
				<input class="bouton_cal" type="image" src="images/calendrier.png" id="btnCalendrier" value="..." title="Cliquez pour afficher le calendrier" />
				</td>
			</tr>

			<script type="text/javascript">
				Calendar.setup ({
					inputField	: "txtDatePaiement",
					ifFormat	: "%Y-%m-%d",
					button		: "btnCalendrier"
				});
			</script>
		</table>
</fieldset>
</form>


<script type="text/javascript">

	if (document.getElementById('frmFacturation').txtRef.value) {

		modif();
		document.getElementById('frmQte').txtQte.focus();
		document.getElementById('frmQte').txtQte.select();

	} else {

		document.getElementById('frmFacturation').txtRef.focus();

	}

</script>
