<?php
$langs->load("main");
?>
<!--Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
	Copyright (C) 2011 		Juanjo Menent	 <jmenent@2byte.es>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
-->
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Summary"); ?></legend>

	<table class="table_resume">

		<tr><td class="resume_label"><?php echo $langs->trans("Invoice"); ?></td><td><?php  echo $obj_facturation->num_facture(); ?></td></tr>
		<tr><td class="resume_label"><?php echo $langs->trans("TotalHT"); ?></td><td><?php echo price2num($obj_facturation->prix_total_ht(),'MT').' '.$conf->monnaie; ?></td></tr>
		<?php
			// Affichage de la tva par taux
			if ( $obj_facturation->montant_tva() ) {

				echo ('<tr><td class="resume_label">'.$langs->trans("VAT").'</td><td>'.price2num($obj_facturation->montant_tva(),'MT').' '.$conf->monnaie.'</td></tr>');

			}
			else
			{

				echo ('<tr><td class="resume_label">'.$langs->trans("VAT").'</td><td>'.$langs->trans("NoVAT").'</td></tr>');

			}
		?>
		<tr><td class="resume_label"><?php echo $langs->trans("TotalTTC"); ?> </td><td><?php echo price2num($obj_facturation->prix_total_ttc(),'MT').' '.$conf->monnaie; ?></td></tr>
		<tr><td class="resume_label"><?php echo $langs->trans("PaymentMode"); ?> </td><td>
		<?php
		switch ($obj_facturation->mode_reglement())
		{
			case 'ESP':
				echo $langs->trans("Cash");
				$filtre='courant=2';
				if (!empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH))
					$selected = $conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
				break;
			case 'CB':
				echo $langs->trans("CreditCard");
				$filtre='courant=1';
				if (!empty($conf->global->CASHDESK_ID_BANKACCOUNT_CB))
					$selected = $conf->global->CASHDESK_ID_BANKACCOUNT_CB;
				break;
			case 'CHQ':
				echo $langs->trans("Cheque");
				$filtre='courant=1';
				if (!empty($conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE))
					$selected = $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
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
			if ( $obj_facturation->mode_reglement() == 'DIF' ) {

				echo ('<tr><td class="resume_label">'.$langs->trans("DateEcheance").'</td><td>'.$obj_facturation->paiement_le().'</td></tr>');

			} else {

				echo ('<tr><td class="resume_label">'.$langs->trans("Received").'</td><td>'.price2num($obj_facturation->montant_encaisse(),'MT').' '.$conf->monnaie.'</td></tr>');

			}

			// Affichage du montant rendu (reglement en especes)
			if ( $obj_facturation->montant_rendu() ) {

				echo ('<tr><td class="resume_label">'.$langs->trans("Change").'</td><td>'.price2num($obj_facturation->montant_rendu(),'MT').' '.$conf->monnaie.'</td></tr>');

			}

		?>

	</table>

	<form id="frmValidation" class="formulaire2" method="post" action="validation_verif.php?action=valide_facture">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
		<p class="note_label">
			<?php
				echo $langs->trans("BankToPay"). "<br>";
				$form = new Form($db);
				$form->select_comptes($selected,'cashdeskbank',0,$filtre);
			?>
		</p>
		<p class="note_label"><?php echo $langs->trans("Notes"); ?><br><textarea class="textarea_note" name="txtaNotes"></textarea></p>

		<span><input class="bouton_validation" type="submit" name="btnValider" value="<?php echo $langs->trans("ValidateInvoice"); ?>" /></span>
		<p><a class="lien1" href="affIndex.php?menu=facturation"><?php echo $langs->trans("RestartSelling"); ?></a></p>
	</form>



</fieldset>
