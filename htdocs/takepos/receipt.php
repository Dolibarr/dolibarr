<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra    <jove@bisquerra.com>
 * Copyright (C) 2019		JC Prieto			<jcprieto@virtual20.com>
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

require '../main.inc.php';	// Load $user and permissions
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array("main", "cashdesk"));
$langs->load('takepos@takepos');	//V20

/*
 * View
 */

top_httphead('text/html');

$facid=GETPOST('facid', 'int');
$place=GETPOST('place', 'int');
$placelabel=GETPOST('placelabel', 'alpha');
$isticket=false;	//V20

if ($place>0){		
  
    $sql="SELECT f.rowid, f.total_ttc, t.label FROM ".MAIN_DB_PREFIX."facture AS f , ".MAIN_DB_PREFIX."takepos_floor_tables AS t ".
	"WHERE f.facnumber='(PROV-POS-".$place.")' AND t.rowid=".$place;	//V20
	$resql = $db->query($sql);
	if($resql){
		$row = $db->fetch_array($resql);
		$facid = $row['rowid'];
		$placelabel=$row['label'];	//V20
	}
}


$object=new Facture($db);
$object->fetch($facid);
if(!$placelabel>'')		$placelabel=$object->ref_client;	//V20
if($object->socid==$conf->global->CASHDESK_ID_THIRDPARTY) $isticket=true;    //V20: Default customer
    

// IMPORTANT: This file is sended to 'Takepos Printing' application. Keep basic file. No external files as css, js... If you need images use absolute path.
?>
<html>
<body>
<center>
<font size="4">
<?php //echo '<b>'.$mysoc->name.'</b>';
		echo '<b>Bodega Latarce</b>';
?>
</font>
</center>
<br>
<table width="100%"><tr><td align="left">
<p align="left">
<?php
$substitutionarray=getCommonSubstitutionArray($langs);
if (! empty($conf->global->TAKEPOS_HEADER))
{
	$newfreetext=make_substitutions($conf->global->TAKEPOS_HEADER, $substitutionarray);
	echo $newfreetext;
}
?>
</p></td><td align="right">
<p align="right">
<?php
print $langs->trans('Site').': '.$placelabel.'<br>';
print $langs->trans('Date').": ".dol_print_date($object->date, 'day').'<br>';	//Invoice date

//V20: Spain: Simple invoice not print customer.
if($isticket){
	if ($mysoc->country_code == 'ES') print $langs->trans("InvoiceSimple").': ';
	print $object->id;
	print '</p></tr></table>';
}else{
	if ($mysoc->country_code == 'ES') print $langs->trans("Invoice").': ';
	
	print $object->ref;
	print '</p></tr></table><p>';
	$object->fetch_thirdparty();
	print $object->thirdparty->name.'<br>';
	print $object->thirdparty->idprof1.'<br>';
	print $object->thirdparty->address.'<br>';
	print $object->thirdparty->zip.'. '.$object->thirdparty->town.' ('.$object->thirdparty->state.')';
	print '</p>';
}

//V20: 2 decimals in printed ticket:
?>

<br>

<table width="100%" style="border-top-style: double;">
    <thead>
	<tr>
        <th align="center"><?php print $langs->trans("Label"); ?></th>
        <th align="right"><?php print $langs->trans("Qty"); ?></th>
        <th align="right"><?php print $langs->trans("Price"); ?></th>
        <th align="right"><?php print $langs->trans("TotalTTC"); ?></th>
	</tr>
    </thead>
    <div style="border-top-style: double;"></div>
    <tbody>
    <?php
    foreach ($object->lines as $line)
    {
    	//V20: Is plate of menu and total=0?, not print.
        if ($line->special_code == "9" && $line->total_ttc==0)	continue; 
        
    ?>
    <tr>
        <td><?php echo (empty($line->product_label) ? $line->desc : $line->product_label);?></td>
        <td align="right"><?php echo $line->qty;?></td>
        <td align="right"><?php echo price($line->total_ttc/$line->qty,0,'',1,2,2);?></td>
        <td align="right"><?php echo price($line->total_ttc,0,'',1,2,2);?></td>
    </tr>
    <?php
    }
    ?>
    </tbody>
</table>
<br>
<table align="right" width="100%" style="border-top-style: double;">
<div style="border-top-style: double;"></div>
<tr>
    <th align="right"><?php echo $langs->trans("TotalHT");?></th>
    <td align="right"><?php echo price($object->total_ht, 1, '', 1, 2, 2, $conf->currency)."\n";?></td>
</tr>
<?php 
if($conf->global->TAKEPOS_TICKET_VAT_GROUPPED){
	$vat_groups = array();
	foreach ($object->lines as $line)
	{
		if(!array_key_exists($line->tva_tx, $vat_groups)){
			$vat_groups[$line->tva_tx] = 0;
		}
		$vat_groups[$line->tva_tx] += $line->total_tva;
	}
	foreach($vat_groups as $key => $val){
	?>
	<tr>
		<th align="right"><?php echo $langs->trans("VAT").' '.vatrate($key, 1);?></th>
		<td align="right"><?php echo price($val, 1, '', 1, - 1, - 1, $conf->currency)."\n";?></td>
	</tr>
<?php
	}
}else{
?>
<tr>
    <th align="right"><?php echo $langs->trans("TotalVAT").'</th><td align="right">'.price($object->total_tva, 1, '', 1, 2, 2, $conf->currency)."\n";?></td>
</tr>
<?php
}
?>
<tr style="font-size: x-large;">
    <th align="right" ><?php echo ''.$langs->trans("TotalTTC").'</th><td align="right">'.price($object->total_ttc, 1, '', 1, 2, 2, $conf->currency)."\n";?></td>
</tr>
</table>


<table align="right" width="100%" style="border-top-style: double;">
<tr><td>
<?php
$substitutionarray=getCommonSubstitutionArray($langs);
if (! empty($conf->global->TAKEPOS_FOOTER))
{
	$newfreetext=make_substitutions($conf->global->TAKEPOS_FOOTER, $substitutionarray);
	echo $newfreetext;
}
?>
</td></tr></table>
<script type="text/javascript">
    window.print();
</script>
</body>
</html>
