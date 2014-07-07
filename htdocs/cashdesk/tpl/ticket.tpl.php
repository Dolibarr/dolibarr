<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
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
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("main");
$langs->load('cashdesk');
header("Content-type: text/html; charset=".$conf->file->character_set_client);

$facid=GETPOST('facid','int');
$object=new Facture($db);
$object->fetch($facid);

?>
<html>
<head>
<title><?php echo $langs->trans('PrintTicket') ?></title>

<style type="text/css">
body {
	font-size: 1.5em;
	position: relative;
}

.entete { /* 		position: relative; */

}

.address { /* 			float: left; */
	font-size: 12px;
}

.date_heure {
	position: absolute;
	top: 0;
	right: 0;
	font-size: 16px;
}

.infos {
	position: relative;
}

.liste_articles {
	width: 100%;
	border-bottom: 1px solid #000;
	text-align: center;
}

.liste_articles tr.titres th {
	border-bottom: 1px solid #000;
}

.liste_articles td.total {
	text-align: right;
}

.totaux {
	margin-top: 20px;
	width: 30%;
	float: right;
	text-align: right;
}

.lien {
	position: absolute;
	top: 0;
	left: 0;
	display: none;
}

@media print {
	.lien {
		display: none;
	}
}
</style>

</head>

<body>

<div class="entete">
<div class="logo"><?php print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_small).'">'; ?>
</div>
<div class="infos">
<p class="address"><?php echo $mysoc->name; ?><br>
<?php print dol_nl2br(dol_format_address($mysoc)); ?><br>
</p>

<p class="date_heure"><?php
// Recuperation et affichage de la date et de l'heure
$now = dol_now();
print dol_print_date($now,'dayhourtext').'<br>';
print $object->ref;
?></p>
</div>
</div>

<br>

<table class="liste_articles">
	<tr class="titres">
		<th><?php print $langs->trans("Code"); ?></th>
		<th><?php print $langs->trans("Label"); ?></th>
		<th><?php print $langs->trans("Qty"); ?></th>
		<th><?php print $langs->trans("Discount").' (%)'; ?></th>
		<th><?php print $langs->trans("TotalHT"); ?></th>
	</tr>

	<?php

	$tab=array();
    $tab = $_SESSION['poscart'];

    $tab_size=count($tab);
    for($i=0;$i < $tab_size;$i++)
    {
        $remise = $tab[$i]['remise'];
        echo ('<tr><td>'.$tab[$i]['ref'].'</td><td>'.$tab[$i]['label'].'</td><td>'.$tab[$i]['qte'].'</td><td>'.$tab[$i]['remise_percent'].'</td><td class="total">'.price(price2num($tab[$i]['total_ht'],'MT'),0,$langs,0,0,-1,$conf->currency).'</td></tr>'."\n");
    }

	?>
</table>

<table class="totaux">
<?php
echo '<tr><th class="nowrap">'.$langs->trans("TotalHT").'</th><td class="nowrap">'.price(price2num($obj_facturation->prixTotalHt(),'MT'),'',$langs,0,-1,-1,$conf->currency)."</td></tr>\n";
echo '<tr><th class="nowrap">'.$langs->trans("TotalVAT").'</th><td class="nowrap">'.price(price2num($obj_facturation->montantTva(),'MT'),'',$langs,0,-1,-1,$conf->currency)."</td></tr>\n";
echo '<tr><th class="nowrap">'.$langs->trans("TotalTTC").'</th><td class="nowrap">'.price(price2num($obj_facturation->prixTotalTtc(),'MT'),'',$langs,0,-1,-1,$conf->currency)."</td></tr>\n";
?>
</table>

<script type="text/javascript">
	window.print();
</script>

<a class="lien" href="#"
	onclick="javascript: window.close(); return(false);"><?php echo $langs->trans("Close"); ?></a>

</body>
</html>
