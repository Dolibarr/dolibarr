<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *
 */

// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Load translation files required by the page
$langs->loadLangs(array("main", "bills", "cashdesk"));

// Object $form must de defined

?>

<script type="text/javascript" src="javascript/facturation1.js"></script>
<script type="text/javascript" src="javascript/dhtml.js"></script>
<script type="text/javascript" src="javascript/keypad.js"></script>

<!-- ========================= Cadre "Article" ============================= -->
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Article"); ?></legend>
	<form id="frmFacturation" class="formulaire1" method="post" action="facturation_verif.php" autocomplete="off">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>" />

		<input type="hidden" name="hdnSource" value="NULL" />

		<table class="center">
			<tr><th class="label1"><?php echo $langs->trans("FilterRefOrLabelOrBC"); ?></th><th class="label1"><?php echo $langs->trans("Designation"); ?></th></tr>
			<tr>
			<!-- Affichage de la reference et de la designation -->
			<!-- Suppression de l'attribut onkeyup qui causait un probleme d'emulation avec les douchettes -->
			<td><input class="texte_ref" type="text" id ="txtRef" name="txtRef" value="<?php echo $obj_facturation->ref() ?>"
				onchange="javascript: setSource('REF');"
				onfocus="javascript: this.select();" />
			</td>
			<td class="select_design maxwidthonsmartphone">
				<select id="selProduit" class="maxwidthonsmartphone" name="selProduit" onchange="javascript: setSource('LISTE');">
<?php
print '<option value="0">'.$top_liste_produits.'</option>'."\n";

$id = $obj_facturation->id();

// Si trop d'articles ont ete trouves, on n'affiche que les X premiers (defini dans le fichier de configuration) ...

$nbtoshow = $nbr_enreg;
if (!empty($conf_taille_listes) && $nbtoshow > $conf_taille_listes) $nbtoshow = $conf_taille_listes;

for ($i = 0; $i < $nbtoshow; $i++)
{
	if ($id == $tab_designations[$i]['rowid']) {
		$selected = 'selected';
	} else {
		$selected = '';
	}

	$label = $tab_designations[$i]['label'];

	print '<option '.$selected.' value="'.$tab_designations[$i]['rowid'].'">'.dol_trunc($tab_designations[$i]['ref'], 16).' - '.dol_trunc($label, 35, 'middle');
	if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot) && $tab_designations[$i]['fk_product_type'] == 0) {
		print ' ('.$langs->trans("CashDeskStock").': '.(empty($tab_designations[$i]['reel']) ? 0 : $tab_designations[$i]['reel']).')';
	}
	print '</option>'."\n";
}
?>
				</select>
			</td>
			</tr>
		</table>
	</form>

	<form id="frmQte" class="formulaire1" method="post" action="facturation_verif.php?action=ajout_article" onsubmit ="javascript: return verifSaisie();">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>" />
		<table class="center">
			<tr>
			<th><?php echo $langs->trans("Qty"); ?></th>
			<th><?php echo $langs->trans("PriceUHT"); ?></th>
			<th><?php echo $langs->trans("Discount"); ?> (%)</th>
            <th><?php echo $langs->trans("VATRate"); ?></th>
			<th></th>
            </tr>
			<tr>
				<td><input class="texte1 maxwidth50onsmartphone" type="text" id="txtQte" name="txtQte" value="1" onkeyup="javascript: modif();" onfocus="javascript: this.select();" />
<?php print genkeypad("txtQte", "frmQte"); ?>
				</td>
				<!-- Show unit price -->
				<?php // TODO Remove the disabled and use this value when adding product into cart ?>
				<td><input class="texte1_off maxwidth50onsmartphone" type="text" name="txtPrixUnit" value="<?php echo price2num($obj_facturation->prix(), 'MU'); ?>" onchange="javascript: modif();" disabled /></td>
    			<!-- Choix de la remise -->
    			<td><input class="texte1 maxwidth50onsmartphone" type="text" id="txtRemise" name="txtRemise" value="0" onkeyup="javascript: modif();" onfocus="javascript: this.select();"/>
					<?php print genkeypad("txtRemise", "frmQte"); ?>
    			</td>
                <!-- Choix du taux de TVA -->
                <td class="select_tva center">
                <?php
					$vatrate = $obj_facturation->vatrate; // To get vat rate we just have selected

					$buyer = new Societe($db);
					if ($_SESSION["CASHDESK_ID_THIRDPARTY"] > 0) $buyer->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);
					echo $form->load_tva('selTva', (GETPOSTISSET("selTva") ? GETPOST("selTva", 'alpha', 2) : $vatrate), $mysoc, $buyer, 0, 0, '', false, -1);
			    ?>
                </td>
				<td></td>
			</tr>
			<tr>
				<!-- Affichage du stock pour l'article courant -->
			<tr>
				<td><?php echo $langs->trans("Stock"); ?></td>
				<td>
				<input class="texte1_off maxwidth50onsmartphone" type="text" name="txtStock" value="<?php echo $obj_facturation->stock() ?>" disabled />
				</td>
				<td><?php echo $langs->trans("TotalHT"); ?></td>
    			<!-- Affichage du total HT -->
    			<td colspan="2"><input class="texte1_off maxwidth50onsmartphone" type="text" name="txtTotal" value="" disabled /></td><td></td>
			</tr>

		</table>

		<input class="button bouton_ajout_article" type="submit" id="sbmtEnvoyer" value="<?php echo $langs->trans("AddThisArticle"); ?>" />
	</form>
</fieldset>

<!-- ========================= Cadre "Amount" ============================= -->
<form id="frmDifference"  class="formulaire1" method="post" onsubmit="javascript: return verifReglement()" action="validation_verif.php?action=valide_achat">
	<input type="hidden" name="hdnChoix" value="" />
	<input type="hidden" name="token" value="<?php echo newToken(); ?>" />
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Amount"); ?></legend>
		<table class="centpercent">
			<tr><th class="label1"><?php echo $langs->trans("TotalTicket"); ?></th><th class="label1"><?php echo $langs->trans("Received"); ?></th><th class="label1"><?php echo $langs->trans("Change"); ?></th></tr>
			<tr>
			<!-- Affichage du montant du -->
			<td><input class="texte2_off maxwidth100onsmartphone" type="text" name="txtDu" value="<?php echo price2num($obj_facturation->prixTotalTtc(), 'MT'); ?>" disabled /></td>
			<!-- Choix du montant encaisse -->
			<td><input class="texte2 maxwidth100onsmartphone" type="text" id="txtEncaisse" name="txtEncaisse" value="" onkeyup="javascript: verifDifference();" onfocus="javascript: this.select();" />
<?php print genkeypad("txtEncaisse", "frmDifference"); ?>
			</td>
			<!-- Affichage du montant rendu -->
			<td><input class="texte2_off maxwidth100onsmartphone" type="text" name="txtRendu" value="0" disabled /></td>
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
				print '<input class="bouton_mode_reglement_disabled" type="button" name="btnModeReglement" value="'.$langs->trans("Cash").'" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("CashDesk"))).'" />';
			}
			else print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("Cash").'" onclick="javascript: verifClic(\'ESP\');" />';
			print '</div>';
			print '<div class="inline-block" style="margin: 6px;">';
			if (empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CB']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CB'] < 0)
			{
				$langs->load("errors");
				print '<input class="bouton_mode_reglement_disabled" type="button" name="btnModeReglement" value="'.$langs->trans("CreditCard").'" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("CashDesk"))).'" />';
			}
			else print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("CreditCard").'" onclick="javascript: verifClic(\'CB\');" />';
			print '</div>';
			print '<div class="inline-block" style="margin: 6px;">';
			if (empty($_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE']) || $_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE'] < 0)
			{
				$langs->load("errors");
				print '<input class="bouton_mode_reglement_disabled" type="button" name="btnModeReglement" value="'.$langs->trans("CheckBank").'" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete"), $langs->transnoentitiesnoconv("CashDesk")).'" />';
			}
			else print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("CheckBank").'" onclick="javascript: verifClic(\'CHQ\');" />';
			print '</div>';
			print '<div class="clearboth">';
			print '<div class="inline-block" style="margin: 6px;">';
			?>
				<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="<?php echo $langs->trans("Reported"); ?>" onclick="javascript: verifClic('DIF');" />
			<?php
			print $langs->trans("DateDue").' :';
			print $form->selectDate(-1, 'txtDatePaiement', 0, 0, 0, 'paymentmode', 1, 0);
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
