<?php
/* Copyright (C) 2017      Franck Moreau        <franck.moreau@theobald.com>
 * Copyright (C) 2018      Alexandre Spangaro   <aspangaro@zendsi.com>
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

/**
 *  \file       htdocs/loan/createschedule.php
 *  \ingroup    loan
 *  \brief      Schedule card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';

$loanid = GETPOST('loanid', 'int');
$action = GETPOST('action','aZ09');

$object = new Loan($db);
$object->fetch($loanid);

// Load translation files required by the page
$langs->loadLangs(array("compta","bills","loan"));

$title = $langs->trans("Loan") . ' - ' . $langs->trans("Card");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$title,$help_url);

$head=loan_prepare_head($object);
dol_fiche_head($head, 'FinancialCommitment', $langs->trans("Loan"), -1, 'bill');

if ($action == 'createecheancier') {
    
    $i=1;
    while($i <$object->nbterm+1){
        
        $date =  GETPOST('hi_date'.$i,'int');
        $mens = GETPOST('mens'.$i);
        $int = GETPOST('hi_interets'.$i);
        $insurance = GETPOST('hi_insurance'.$i);
        
        $echeance = new LoanSchedule($db);
        
        $echeance->fk_loan = $object->id;
        $echeance->datec = dol_now();
        $echeance->tms = dol_now();
        $echeance->datep = $date;
        $echeance->amount_capital = $mens-$int;
        $echeance->amount_insurance = $insurance;
        $echeance->amount_interest = $int;
        $echeance->fk_typepayment = 3;
        $echeance->fk_bank = 0;
        $echeance->fk_user_creat = $user->id;
        $echeance->fk_user_modif = $user->id;
        $result=$echeance->create($user);
        if ($result<0) {
            setEventMessages($echeance->error, $echeance->errors,'errors');
        }
        $i++;
    }
}

if ($action == 'updateecheancier') {
    
    $i=1;
    while($i <$object->nbterm+1){
        
        $mens = GETPOST('mens'.$i);
        $int = GETPOST('hi_interets'.$i);
        $id = GETPOST('hi_rowid'.$i);
        $insurance = GETPOST('hi_insurance'.$i);
        
        $echeance = new LoanSchedule($db);
        $echeance->fetch($id);
        $echeance->tms = dol_now();
        $echeance->amount_capital = $mens-$int;
        $echeance->amount_insurance = $insurance;
        $echeance->amount_interest = $int;
        $echeance->fk_user_modif = $user->id;
        $result= $echeance->update($user,0);
        if ($result<0) {
            setEventMessages(null, $echeance->errors,'errors');
        }
        $i++;
    }
}

$echeance = new LoanSchedule($db);
$echeance->fetchAll($object->id);

$var = ! $var;


?>
<script type="text/javascript" language="javascript">
$(document).ready(function() {
	$('[name^="mens"]').focusout(function() {
		var echeance=$(this).attr('ech');
		var mens=$(this).val();
		var idcap=echeance-1;
		idcap = '#hi_capital'+idcap;
		var capital=$(idcap).val();
		console.log("Change montly amount echeance="+echeance+" idcap="+idcap+" capital="+capital);
		$.ajax({
			  dataType: 'json',
			  url: 'calcmens.php',
			  data: { echeance: echeance, mens: mens, capital:capital, rate:<?php echo $object->rate/100;?> , nbterm : <?php echo $object->nbterm;?>},
			  success: function(data) {
				$.each(data, function(index, element) {
					var idcap_res='#hi_capital'+index;
					var idcap_res_srt='#capital'+index;
					var interet_res='#hi_interets'+index;
					var interet_res_str='#interets'+index;
					var men_res='#mens'+index;
					$(idcap_res).val(element.cap_rest);
					$(idcap_res_srt).text(element.cap_rest_str+' €');
					$(interet_res).val(element.interet);
					$(interet_res_str).text(element.interet_str+' €');
					$(men_res).val(element.mens);
				});
			}
		});
	});
});
</script>
<?php


print '<form name="createecheancier" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="loanid" value="' . $loanid . '">';
if(count($echeance->lines)>0)
{
	print '<input type="hidden" name="action" value="updateecheancier">';
}else{
	print '<input type="hidden" name="action" value="createecheancier">';
}
print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
$colspan = 6;
if (count($echeance->lines)>0) $colspan++;
print '<th align="center" colspan="'.$colspan.'">';
print $langs->trans("FinancialCommitment");
print '</th>';
print '</tr>';

print '<tr class="liste_titre">';
Print '<th width="5%" align="center">'.$langs->trans("Term").'</th>';
Print '<th width="5%" align="center">'.$langs->trans("Date").'</th>';
print '<th width="15%" align="center">'.$langs->trans("Insurance");
Print '<th width="15%" align="center">'.$langs->trans("InterestAmount").'</th>';
Print '<th width="10%" align="center">'.$langs->trans("Amount").'</th>';
Print '<th width="40%" align="center">'.$langs->trans("CapitalRemain");
print ' ('.price2num($object->capital).')';
print '<input type="hidden" name="hi_capital0" id ="hi_capital0" value="'.$object->capital.'">';
print '</th>';
if (count($echeance->lines)>0) print '<th>'.$langs->trans('DoPayment').'</th>';
print '</tr>'."\n";

if ($object->nbterm > 0 && count($echeance->lines)==0)
{
	$i=1;
	$capital = $object->capital;
	$insurance = $object->insurance_amount/$object->nbterm;
	$insurance = price2num($insurance, 'MT');
	$regulInsurance = price2num($object->insurance_amount - ($insurance * $object->nbterm));
	while($i <$object->nbterm+1)
	{
		$mens = price2num($echeance->calcMonthlyPayments($capital, $object->rate/100, $object->nbterm-$i+1), 'MT');
		$int = ($capital*($object->rate/12))/100;
		$int = price2num($int, 'MT');
		$insu = ($insurance+(($i == 1) ? $regulInsurance : 0));
		$cap_rest = price2num($capital - ($mens-$int), 'MT');
		print '<tr>';
		print '<td align="center" id="n'.$i.'">' . $i .'</td>';
		print '<td align="center" id ="date' .$i .'"><input type="hidden" name="hi_date' .$i .'" id ="hi_date' .$i .'" value="' . dol_time_plus_duree($object->datestart, $i-1, 'm') . '">' . dol_print_date(dol_time_plus_duree($object->datestart, $i-1, 'm'),'day') . '</td>';
		print '<td align="center" id="insurance'.$i.'">'.price($insurance+(($i == 1) ? $regulInsurance : 0),0,'',1).' €</td><input type="hidden" name="hi_insurance' .$i .'" id ="hi_insurance' .$i .'" value="' . ($insurance+(($i == 1) ? $regulInsurance : 0)) . '">';
		print '<td align="center" id="interets'.$i.'">'.price($int,0,'',1).' €</td><input type="hidden" name="hi_interets' .$i .'" id ="hi_interets' .$i .'" value="' . $int . '">';
		print '<td align="center"><input name="mens'.$i.'" id="mens'.$i.'" size="5" value="'.$mens.'" ech="'.$i.'"> €</td>';
		print '<td align="center" id="capital'.$i.'">'.price($cap_rest).' €</td><input type="hidden" name="hi_capital' .$i .'" id ="hi_capital' .$i .'" value="' . $cap_rest . '">';
		print '</tr>'."\n";
		$i++;
		$capital = $cap_rest;
	}
}
elseif(count($echeance->lines)>0)
{
	$i=1;
	$capital = $object->capital;
	$insurance = $object->insurance_amount/$object->nbterm;
	$insurance = price2num($insurance, 'MT');
	$regulInsurance = price2num($object->insurance_amount - ($insurance * $object->nbterm));
	$printed = false;
	foreach ($echeance->lines as $line){
		$mens = $line->amount_capital+$line->amount_interest;
		$int = $line->amount_interest;
		$insu = ($insurance+(($i == 1) ? $regulInsurance : 0));
		$cap_rest = price2num($capital - ($mens-$int), 'MT');
		
		print '<tr>';
		print '<td align="center" id="n'.$i.'"><input type="hidden" name="hi_rowid' .$i .'" id ="hi_rowid' .$i .'" value="' . $line->id . '">' . $i .'</td>';
		print '<td align="center" id ="date' .$i .'"><input type="hidden" name="hi_date' .$i .'" id ="hi_date' .$i .'" value="' . $line->datep . '">' . dol_print_date($line->datep,'day') . '</td>';
		print '<td align="center" id="insurance'.$i.'">'.price($insu,0,'',1).' €</td><input type="hidden" name="hi_insurance' .$i .'" id ="hi_insurance' .$i .'" value="' . $insu . '">';
		print '<td align="center" id="interets'.$i.'">'.price($int,0,'',1).' €</td><input type="hidden" name="hi_interets' .$i .'" id ="hi_interets' .$i .'" value="' . $int . '">';
		if($line->datep > dol_now() && empty($line->fk_bank)){
			print '<td align="center"><input name="mens'.$i.'" id="mens'.$i.'" size="5" value="'.$mens.'" ech="'.$i.'"> €</td>';
		}else{
			print '<td align="center">' . price($mens) . ' €</td><input type="hidden" name="mens' .$i .'" id ="mens' .$i .'" value="' . $mens . '">';
		}
		
		print '<td align="center" id="capital'.$i.'">'.price($cap_rest).' €</td><input type="hidden" name="hi_capital' .$i .'" id ="hi_capital' .$i .'" value="' . $cap_rest . '">';
		print '<td align="center">';
		if (!empty($line->fk_bank)) print $langs->trans('Paid');
		elseif (!$printed)
		{
		    print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&amp;action=create&line_id='.$line->id.'">'.$langs->trans('DoPayment').'</a>';
		    $printed = true;
		}
		print '</td>';
		print '</tr>'."\n";
		$i++;
		$capital = $cap_rest;
	}
}

print '</table>';
print '</br>';
print '</br>';
if (count($echeance->lines)==0) $label = $langs->trans("Create");
else $label = $langs->trans("Save");
print '<div align="center"><input class="button" type="submit" value="'.$label.'"></div>';
print '</form>';

// End of page
llxFooter();
$db->close();
