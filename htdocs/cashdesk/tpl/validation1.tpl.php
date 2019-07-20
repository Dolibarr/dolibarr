<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
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
 */

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Load translation files required by the page
$langs->loadLangs(array("main","bills","banks"));

// Object $form must de defined

?>

<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Summary"); ?></legend>

	<table class="table_resume">

		<tr><td class="resume_label"><?php echo $langs->trans("Invoice"); ?></td><td><?php  echo $obj_facturation->numInvoice(); ?></td></tr>
		<tr><td class="resume_label"><?php echo $langs->trans("TotalHT"); ?></td><td><?php echo price(price2num($obj_facturation->prixTotalHt(), 'MT'), 0, $langs, 0, 0, -1, $conf->currency); ?></td></tr>
		<?php
			// Affichage de la tva par taux
			if ( $obj_facturation->montantTva() ) {

				echo ('<tr><td class="resume_label">'.$langs->trans("VAT").'</td><td>'.price(price2num($obj_facturation->montantTva(), 'MT'), 0, $langs, 0, 0, -1, $conf->currency).'</td></tr>');
			}
			else
			{

				echo ('<tr><td class="resume_label">'.$langs->trans("VAT").'</td><td>'.$langs->trans("NoVAT").'</td></tr>');
			}
		?>
		<tr><td class="resume_label"><?php echo $langs->trans("TotalTTC"); ?> </td><td><?php echo price(price2num($obj_facturation->prixTotalTtc(), 'MT'), 0, $langs, 0, 0, -1, $conf->currency); ?></td></tr>
		<tr><td class="resume_label"><?php echo $langs->trans("PaymentMode"); ?> </td><td>
		<?php
		switch ($obj_facturation->getSetPaymentMode())
		{
			case 'ESP':
				echo $langs->trans("Cash");
				$filtre='courant=2';
				if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]))
					$selected = $_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"];
				break;
			case 'CB':
				echo $langs->trans("CreditCard");
				$filtre='courant=1';
				if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]))
					$selected = $_SESSION["CASHDESK_ID_BANKACCOUNT_CB"];
				break;
			case 'CHQ':
				echo $langs->trans("Cheque");
				$filtre='courant=1';
				if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]))
					$selected = $_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"];
				break;
			case 'DIF':
				echo $langs->trans("Reported");
				$filtre='courant=1 OR courant=2';
				$selected='';
				break;
			default:
				$filtre='courant=1 OR courant=2';
				$selected='';
		}

		?>
		</td></tr>

		<?php
			// Affichage des infos en fonction du mode de paiement
			if ( $obj_facturation->getsetPaymentMode() == 'DIF' ) {

				echo ('<tr><td class="resume_label">'.$langs->trans("DateDue").'</td><td>'.$obj_facturation->paiementLe().'</td></tr>');
			} else {

				echo ('<tr><td class="resume_label">'.$langs->trans("Received").'</td><td>'.price(price2num($obj_facturation->montantEncaisse(), 'MT'), 0, $langs, 0, 0, -1, $conf->currency).'</td></tr>');
			}

			// Affichage du montant rendu (reglement en especes)
			if ( $obj_facturation->montantRendu() ) {

				echo ('<tr><td class="resume_label">'.$langs->trans("Change").'</td><td>'.price(price2num($obj_facturation->montantRendu(), 'MT'), 0, $langs, 0, 0, -1, $conf->currency).'</td></tr>');
			}

		?>

	</table>

	<form id="frmValidation" class="formulaire2" method="post" action="validation_verif.php?action=valide_facture">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
		<p class="note_label">
			<?php
				echo $langs->trans("BankToPay"). "<br>";
				$form->select_comptes($selected, 'cashdeskbank', 0, $filtre);
			?>
		</p>
		<p class="note_label"><?php echo $langs->trans("Notes"); ?><br><textarea class="textarea_note" name="txtaNotes"></textarea></p>

		<div class="center"><input class="button" type="submit" name="btnValider" value="<?php echo $langs->trans("ValidateInvoice"); ?>" /><br>
		<br><a class="lien1" href="affIndex.php?menutpl=facturation"><?php echo $langs->trans("RestartSelling"); ?></a>
		</div>
	</form>



</fieldset>
