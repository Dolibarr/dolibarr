<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018-2021 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2023      Maxime Nicolas          <maxime@oarces.com>
 * Copyright (C) 2023      Benjamin GREMBI         <benjamin@oarces.com>
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
 */

/**
 *    \file       htdocs/compta/bank/transfer.php
 *    \ingroup    bank
 *    \brief      Page for entering a bank transfer
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'multicurrency'));

$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
if (!$user->rights->banque->transfer) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

$hookmanager->initHooks(array('banktransfer'));

$MAXLINES = 10;


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if ($action == 'add') {
	$langs->load('errors');
	$i = 1;

	while ($i < $MAXLINES) {
		$dateo[$i] = dol_mktime(12, 0, 0, GETPOST($i.'_month', 'int'), GETPOST($i.'_day', 'int'), GETPOST($i.'_year', 'int'));
		$label[$i] = GETPOST($i.'_label', 'alpha');
		$amount[$i] = intval(price2num(GETPOST($i.'_amount', 'alpha'), 'MT', 2));
		$amountto[$i] = price2num(GETPOST($i.'_amountto', 'alpha'), 'MT', 2);
		$accountfrom[$i] = intval(GETPOST($i.'_account_from', 'int'));
		$accountto[$i] = intval(GETPOST($i.'_account_to', 'int'));
		$type[$i] = GETPOST($i.'_type', 'int');
		$errori[$i] = 0;

		$tabnum[$i] = 0;
		if (!empty($label[$i]) || !($amount[$i] <= 0) || !($accountfrom[$i] < 0) || !($accountto[$i]  < 0)) {
			$tabnum[$i] = 1;
		}
		$i++;
	}

	$n = 1;
	while ($n < $MAXLINES) {
		if ($tabnum[$n] === 1) {
			if ($accountfrom[$n] < 0) {
				$errori[$n]++;
				setEventMessages($langs->trans("ErrorFieldRequired", '#'.$n. ' ' .$langs->transnoentities("TransferFrom")), null, 'errors');
			}
			if ($accountto[$n] < 0) {
				$errori[$n]++;
				setEventMessages($langs->trans("ErrorFieldRequired", '#'.$n. ' ' .$langs->transnoentities("TransferTo")), null, 'errors');
			}
			if (!$type[$n]) {
				$errori[$n]++;
				setEventMessages($langs->trans("ErrorFieldRequired", '#'.$n. ' ' .$langs->transnoentities("Type")), null, 'errors');
			}
			if (!$dateo[$n]) {
				$errori[$n]++;
				setEventMessages($langs->trans("ErrorFieldRequired", '#'.$n. ' ' .$langs->transnoentities("Date")), null, 'errors');
			}

			if (!($label[$n])) {
				$errori[$n]++;
				setEventMessages($langs->trans("ErrorFieldRequired", '#'.$n. ' ' . $langs->transnoentities("Description")), null, 'errors');
			}
			if (!($amount[$n])) {
				$errori[$n]++;
				setEventMessages($langs->trans("ErrorFieldRequired", '#'.$n. ' ' .$langs->transnoentities("Amount")), null, 'errors');
			}

			if (!$errori[$n]) {
				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

				$tmpaccountfrom = new Account($db);
				$tmpaccountfrom->fetch(GETPOST($n.'_account_from', 'int'));

				$tmpaccountto = new Account($db);
				$tmpaccountto->fetch(GETPOST($n.'_account_to', 'int'));

				if ($tmpaccountto->currency_code == $tmpaccountfrom->currency_code) {
					$amountto[$n] = $amount[$n];
				} else {
					if (!$amountto[$n]) {
						$errori[$n]++;
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AmountTo")).' #'.$n, null, 'errors');
					}
				}
				if ($amountto[$n] < 0) {
					$errori[$n]++;
					setEventMessages($langs->trans("AmountMustBePositive").' #'.$n, null, 'errors');
				}

				if ($tmpaccountto->id == $tmpaccountfrom->id) {
					$errori[$n]++;
					setEventMessages($langs->trans("ErrorFromToAccountsMustDiffers").' #'.$n, null, 'errors');
				}
			}

			if ($errori[$n] == 0) {
				$db->begin();

				$bank_line_id_from = 0;
				$bank_line_id_to = 0;
				$result = 0;

				// By default, electronic transfert from bank to bank
				$typefrom = $type[$n];
				$typeto = $type[$n];
				if ($tmpaccountto->courant == Account::TYPE_CASH || $tmpaccountfrom->courant == Account::TYPE_CASH) {
					// This is transfer of change
					$typefrom = 'LIQ';
					$typeto = 'LIQ';
				}

				if (!$errori[$n]) {
					$bank_line_id_from = $tmpaccountfrom->addline($dateo[$n], $typefrom, $label[$n], price2num(-1 * $amount[$n]), '', '', $user);
				}
				if (!($bank_line_id_from > 0)) {
					$errori[$n]++;
				}
				if (!$errori[$n]) {
					$bank_line_id_to = $tmpaccountto->addline($dateo[$n], $typeto, $label[$n], $amountto[$n], '', '', $user);
				}
				if (!($bank_line_id_to > 0)) {
					$errori[$n]++;
				}

				if (!$errori[$n]) {
					$result = $tmpaccountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
				}
				if (!($result > 0)) {
					$errori[$n]++;
				}
				if (!$errori[$n]) {
					$result = $tmpaccountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
				}
				if (!($result > 0)) {
					$errori[$n]++;
				}
				if (!$errori[$n]) {
					$mesgs = $langs->trans("TransferFromToDone", '{s1}', '{s2}', $amount[$n], $langs->transnoentitiesnoconv("Currency".$conf->currency));
					$mesgs = str_replace('{s1}', '<a href="bankentries_list.php?id='.$tmpaccountfrom->id.'&sortfield=b.datev,b.dateo,b.rowid&sortorder=desc">'.$tmpaccountfrom->label.'</a>', $mesgs);
					$mesgs = str_replace('{s2}', '<a href="bankentries_list.php?id='.$tmpaccountto->id.'">'.$tmpaccountto->label.'</a>', $mesgs);
					setEventMessages($mesgs, null, 'mesgs');
					$db->commit();
				} else {
					setEventMessages($tmpaccountfrom->error.' '.$tmpaccountto->error, null, 'errors');
					$db->rollback();
				}
			}
		}
		$n++;
	}
}

/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses|ES:M&oacute;dulo_Bancos_y_Cajas';
$title = $langs->trans('MenuBankInternalTransfer');

llxHeader('', $title, $help_url);


print '		<script type="text/javascript">
        	$(document).ready(function () {
    	  		$(".selectbankaccount").change(function() {
						console.log("We change bank account. We check if currency differs. If yes, we show multicurrency field");
						i = $(this).attr("name").replace("_account_to", "").replace("_account_from", "");
						console.log(i);
						init_page(i);
				});

				function init_page(i) {
					// TODO Scan all line i and set atleast2differentcurrency if there is 2 different values among all lines
        			var account1 = $("#select"+i+"_account_from").val();
        			var account2 = $("#select"+i+"_account_to").val();
					var currencycode1 = $("#select"+i+"_account_from option:selected").attr("data-currency-code");
					var currencycode2 = $("#select"+i+"_account_to option:selected").attr("data-currency-code");
					console.log("Set fields according to currencycode found currencycode1="+currencycode1+" currencycode2="+currencycode2);

					var atleast2differentcurrency = (currencycode2!==currencycode1 && currencycode1 !== undefined && currencycode2 !== undefined && currencycode2!=="" && currencycode1!=="");

					if (atleast2differentcurrency) {
						console.log("We show multucurrency field");
        				$(".multicurrency").show();
        			} else {
						console.log("We hide multucurrency field");
						$(".multicurrency").hide();
					}

					$("select").each(function() {
						if( $(this).attr("view")){
							$(this).closest("tr").removeClass("hidejs").removeClass("hideobject");
						}
					});
        		}

				init_page(1);
        	});
    		</script>';


print load_fiche_titre($langs->trans("MenuBankInternalTransfer"), '', 'bank_account');

print '<span class="opacitymedium">'.$langs->trans("TransferDesc").'</span>';
print '<br><br>';

print '<form name="add" method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add">';

print '<div>';

print '<div class="div-table-responsive-no-min">';
print '<table id="tablemouvbank" class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th>'.$langs->trans("TransferFrom").'</th>';
print '<th>'.$langs->trans("TransferTo").'</th>';
print '<th>'.$langs->trans("Type").'</th>';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("Description").'</th>';
print '<th class="right">'.$langs->trans("Amount").'</th>';
print '<td class="hideobject multicurrency right">'.$langs->trans("AmountToOthercurrency").'</td>';
print '</tr>';

for ($i = 1 ; $i < $MAXLINES; $i++) {
	$label = '';
	$amount = '';
	$amounto = '';

	if ($errori[$i]) {
		$label = GETPOST($i.'_label', 'alpha');
		$amount = GETPOST($i.'_amount', 'alpha');
		$amountto = GETPOST($i.'_amountto', 'alpha');
	}

	if ($i == 1) {
		$classi = 'numvir number'. $i;
		$classi .= ' active';
	} else {
		$classi = 'numvir number'. $i;
		$classi .= ' hidejs hideobject';
	}

	print '<tr class="oddeven nowraponall '.$classi.'"><td>';
	print img_picto('', 'bank_account', 'class="paddingright"');
	$form->select_comptes(($errori[$i] ? GETPOST($i.'_account_from', 'int') : ''), $i.'_account_from', 0, '', 1, ($errori[$i] ? 'view=view' : ''), isModEnabled('multicurrency') ? 1 : 0, 'minwidth100');
	print '</td>';

	print '<td class="nowraponall">';
	print img_picto('', 'bank_account', 'class="paddingright"');
	$form->select_comptes(($errori[$i] ? GETPOST($i.'_account_to', 'int') : ''), $i.'_account_to', 0, '', 1, ($errori[$i] ? 'view=view' : ''), isModEnabled('multicurrency') ? 1 : 0, 'minwidth100');
	print "</td>\n";

	// Payment mode
	print '<td class="nowraponall">';
	$idpaymentmodetransfer = dol_getIdFromCode($db, 'VIR', 'c_paiement');
	$form->select_types_paiements(($errori[$i] ? GETPOST($i.'_type', 'aZ09') : $idpaymentmodetransfer), $i.'_type', '', 0, 1, 0, 0, 1, 'minwidth100');
	print "</td>\n";

	// Date
	print '<td class="nowraponall">';
	print $form->selectDate((!empty($dateo[$i]) ? $dateo[$i] : ''), $i.'_', '', '', '', 'add');
	print "</td>\n";

	// Description
	print '<td><input name="'.$i.'_label" class="flat quatrevingtpercent selectjs" type="text" value="'.dol_escape_htmltag($label).'"></td>';

	// Amount
	print '<td class="right"><input name="'.$i.'_amount" class="flat right selectjs" type="text" size="6" value="'.dol_escape_htmltag($amount).'"></td>';

	// AmountToOthercurrency
	print '<td class="hideobject multicurrency right"><input name="'.$i.'_amountto" class="flat" type="text" size="6" value="'.dol_escape_htmltag($amountto).'"></td>';

	print '</tr>';
}

print '</table>';
print '</div>';
print '</div>';
print '<div id="btncont" style="display: flex; align-items: center">';
print '<a id="btnincrement" style="margin-left:35%" class="btnTitle btnTitlePlus" onclick="increment()" title="'.dol_escape_htmltag($langs->trans("Add")).'">
		<span class="fa fa-plus-circle valignmiddle btnTitle-icon">
		</span>
	   </a>';
print '<br><div  class=""><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
print '</div>';

print '</form>';

print '		<script type="text/javascript">
			function increment(){
				$(".numvir").nextAll(".hidejs:first").removeClass("hidejs").removeClass("hideobject").addClass("active").show();
			};
			$(".number1").on("click",(function() {
				console.log("We click on number1");
				$(".hidejs").each(function (){$(this).hide()});
				$("#btncont").show();
			}))
			</script>
	 ';

// End of page
llxFooter();

$db->close();
