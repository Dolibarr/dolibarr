<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015		Regis Houssin		<regis.houssin@capnetworks.com>
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
 *
 */

$langs->load("main");
$langs->load("bills");
$langs->load("cashdesk");

// Object $form must de defined

?>

<script type="text/javascript" src="javascript/facturation1.js"></script>
<script type="text/javascript" src="javascript/dhtml.js"></script>
<script type="text/javascript" src="javascript/keypad.js"></script>

<!-- ========================= Cadre "Article" ============================= -->
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Article"); ?></legend>
	<form id="frmFacturation" class="formulaire1" method="post" action="facturation_verif.php" autocomplete="off">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

		<input type="hidden" name="hdnSource" value="NULL" />

		<table>
			<tr><th class="label1"><?php echo $langs->trans("FilterRefOrLabelOrBC"); ?></th><th class="label1"><?php echo $langs->trans("Designation"); ?></th></tr>
			<tr>
			<!-- Affichage de la reference et de la designation -->
			<td><input class="texte_ref" type="text" id ="txtRef" name="txtRef" value="<?php echo $obj_facturation->ref() ?>"
				onchange="javascript: setSource('REF');"
				onkeyup="javascript: verifResultat('resultats_dhtml', this.value, <?php echo (isset($conf->global->BARCODE_USE_SEARCH_TO_SELECT) ? (int) $conf->global->BARCODE_USE_SEARCH_TO_SELECT : 1) ?>);"
				onfocus="javascript: this.select(); verifResultat('resultats_dhtml', this.value, <?php echo (isset($conf->global->BARCODE_USE_SEARCH_TO_SELECT) ? (int) $conf->global->BARCODE_USE_SEARCH_TO_SELECT : 1) ?>);"
				onBlur="javascript: document.getElementById('resultats_dhtml').innerHTML = '';"/>
			</td>
			<td class="select_design maxwidthonsmartphone">
            <?php /*
            $selected='';
            $htmlname='idprod';
            $status=-1;
            $rice_level=$company->price_level;
            print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/product/ajax/products.php', 'outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
            if (! $hidelabel) print $langs->trans("RefOrLabel").' : ';
            print '<input type="text" size="4" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'" />';
            */
            ?>

				<select id="selProduit" class="maxwidthonsmartphone" name="selProduit" onchange="javascript: setSource('LISTE');">
					<?php
                        print '<option value="0">'.$top_liste_produits.'</option>'."\n";

						$id = $obj_facturation->id();

						// Si trop d'articles ont ete trouves, on n'affiche que les X premiers (defini dans le fichier de configuration) ...

						$nbtoshow = $nbr_enreg;
						if (! empty($conf_taille_listes) && $nbtoshow > $conf_taille_listes) $nbtoshow = $conf_taille_listes;

						for ($i = 0; $i < $nbtoshow; $i++)
						{
							if ( $id == $tab_designations[$i]['rowid'] )
								$selected = 'selected';
							else
								$selected = '';

							$label = $tab_designations[$i]['label'];

							print '<option '.$selected.' value="'.$tab_designations[$i]['rowid'].'">'.dol_trunc($tab_designations[$i]['ref'],16).' - '.dol_trunc($label,35,'middle');
							if (! empty($conf->stock->enabled) && !empty($conf_fkentrepot) && $tab_designations[$i]['fk_product_type']==0) print ' ('.$langs->trans("CashDeskStock").': '.(empty($tab_designations[$i]['reel'])?0:$tab_designations[$i]['reel']).')';
							print '</option>'."\n";

						}
					?>
				</select>
			</td>
			</tr>
			  <tr><td><div id="resultats_dhtml"></div></td></tr>
		</table>
	</form>

	<form id="frmQte" class="formulaire1" method="post" action="facturation_verif.php?action=ajout_article" onsubmit ="javascript: return verifSaisie();">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
		<table>
			<tr><th><?php echo $langs->trans("Qty"); ?></th>
			<th><?php echo $langs->trans("Stock"); ?></th>
			<th><?php echo $langs->trans("PriceUHT"); ?></th>
			<th></th>
			<th><?php echo $langs->trans("Discount"); ?> (%)</th>
			<th><?php echo $langs->trans("TotalHT"); ?></th>
            <th>&nbsp;</th>
            <th><?php echo $langs->trans("VATRate"); ?></th>
            </tr>
			<tr>
				<td><input class="texte1 maxwidth50onsmartphone" type="text" id="txtQte" name="txtQte" value="1" onkeyup="javascript: modif();" onfocus="javascript: this.select();" />
<?php print genkeypad("txtQte", "frmQte");?>
				</td>
				<!-- Affichage du stock pour l'article courant -->
				<td>
				<input class="texte1_off maxwidth50onsmartphone" type="text" name="txtStock" value="<?php echo $obj_facturation->stock() ?>" disabled />
				</td>
				<!-- Show unit price -->
				<?php // TODO Remove the disabled and use this value when adding product into cart ?>
				<td><input class="texte1_off maxwidth50onsmartphone" type="text" name="txtPrixUnit" value="<?php echo price2num($obj_facturation->prix(), 'MU'); ?>" onchange="javascript: modif();" disabled /></td>
				<td></td>
    			<!-- Choix de la remise -->
    			<td><input class="texte1 maxwidth50onsmartphone" type="text" id="txtRemise" name="txtRemise" value="0" onkeyup="javascript: modif();" onfocus="javascript: this.select();"/>
					<?php print genkeypad("txtRemise", "frmQte");?>
    			</td>
    			<!-- Affichage du total HT -->
    			<td><input class="texte1_off maxwidth50onsmartphone" type="text" name="txtTotal" value="" disabled /></td><td></td>
                <!-- Choix du taux de TVA -->
                <td class="select_tva">
                <?php //var_dump($tab_tva); 
					$tva_tx = $obj_facturation->tva();  // Try to get a previously entered VAT rowid. First time, this will return empty.
					$buyer = new Societe($db);
					if ($_SESSION["CASHDESK_ID_THIRDPARTY"] > 0) $buyer->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);
					
					echo $form->load_tva('selTva', (isset($_POST["selTva"])?GETPOST("selTva",'alpha',2):-1), $mysoc, $buyer, 0, 0, '', false, -1);
			    ?>
                </td>
			</tr>
		</table>

		<input class="button bouton_ajout_article" type="submit" id="sbmtEnvoyer" value="<?php echo $langs->trans("AddThisArticle"); ?>" />
	</form>
</fieldset>

<!-- ========================= Cadre "Amount" ============================= -->
<form id="frmDifference"  class="formulaire1" method="post" onsubmit="javascript: return verifReglement()" action="validation_verif.php?action=valide_achat">
	<input type="hidden" name="hdnChoix" value="" />
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Amount"); ?></legend>
		<table class="centpercent">
			<tr><th class="label1"><?php echo $langs->trans("TotalTicket"); ?></th><th class="label1"><?php echo $langs->trans("Received"); ?></th><th class="label1"><?php echo $langs->trans("Change"); ?></th></tr>
			<tr>
			<!-- Affichage du montant du -->
			<td><input class="texte2_off maxwidthonsmartphone" type="text" name="txtDu" value="<?php echo price2num($obj_facturation->prixTotalTtc(), 'MT'); ?>" disabled /></td>
			<!-- Choix du montant encaisse -->
			<td><input class="texte2 maxwidthonsmartphone" type="text" id="txtEncaisse" name="txtEncaisse" value="" onkeyup="javascript: verifDifference();" onfocus="javascript: this.select();" />
<?php print genkeypad("txtEncaisse", "frmDifference");?>
			</td>
			<!-- Affichage du montant rendu -->
			<td><input class="texte2_off maxwidthonsmartphone" type="text" name="txtRendu" value="0" disabled /></td>
			</tr>
			<tr>
		</table>
</fieldset>

<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("PaymentMode"); ?></legend>
		<div class="inline-block">
			<?php
			print '<div class="inline-block" style="margin: 6px;">';
			if (empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CASH']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CASH'] < 0)
			{
				$langs->load("errors");
				print '<input class="bouton_mode_reglement_disabled" type="button" name="btnModeReglement" value="'.$langs->trans("Cash").'" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")).'" />';
			}
			else print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("Cash").'" onclick="javascript: verifClic(\'ESP\');" />';
			print '</div>';
			print '<div class="inline-block" style="margin: 6px;">';
			if (empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE'] < 0)
			{
				$langs->load("errors");
				print '<input class="bouton_mode_reglement_disabled" type="button" name="btnModeReglement" value="'.$langs->trans("CreditCard").'" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")).'" />';
			}
			else print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("CreditCard").'" onclick="javascript: verifClic(\'CB\');" />';
			print '</div>';
			print '<div class="inline-block" style="margin: 6px;">';
			if (empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CB']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CB'] < 0)
			{
				$langs->load("errors");
				print '<input class="bouton_mode_reglement_disabled" type="button" name="btnModeReglement" value="'.$langs->trans("CheckBank").'" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")).'" />';
			}
			else print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("CheckBank").'" onclick="javascript: verifClic(\'CHQ\');" />';
			print '</div>';
			print '<div class="clearboth">';
			print '<div class="inline-block" style="margin: 6px;">';
			?>
				<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="<?php echo $langs->trans("Reported"); ?>" onclick="javascript: verifClic('DIF');" />
			<?php
			print $langs->trans("DateDue").' :';
			print $form->select_date(-1,'txtDatePaiement',0,0,0,'paymentmode',1,0,1);
			print '</div>';
			?>
		</div>
</fieldset>
</form>

<script type="text/javascript">
/*	Calendar.setup ({
		inputField	: "txtDatePaiement",
		ifFormat	: "%Y-%m-%d",
		button		: "btnCalendrier"
	});
*/
	if (document.getElementById('frmFacturation').txtRef.value) {

		modif();
		document.getElementById('frmQte').txtQte.focus();
		document.getElementById('frmQte').txtQte.select();

	} else {

		document.getElementById('frmFacturation').txtRef.focus();

	}

</script>
