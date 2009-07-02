<!--Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>

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
<html>
<head><title>Impression du ticket</title>

<style type="text/css">

	body {
		font-size: 1.5em;
		position: relative;
	}

	.entete {
/* 		position: relative; */
	}

		.logo {
			background: url('<?php echo DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_mini); ?>') no-repeat top left;
			height: 128px;
			width: 650px;
		}

			.logo span {
				display: none;
			}

		.adresse {
/* 			float: left; */
		}

		.date_heure {
			position: absolute;
			top: 0;
			right: 0;
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
	<div class="logo"><span>Logo</span></div>
	<div class="infos">
		<p class="adresse">SOCIETE<br />
		RUE DU PORT<br />
		75000 PARIS</p>

		<?php
			// Recuperation et affichage de la date et de l'heure
			$date = date ('d/m/Y');
			$heure = date ('H:i');
			echo ('<p class="date_heure">Le '.$date.' à '.$heure.'</p>');
		?>
	</div>
</div>

<table class="liste_articles">
	<tr class="titres"><th>Code</th><th>Label</th><th>Qté</th><th>Remise (%)</th><th>Tot HT</th></tr>

	<?php

		// Variables
		$res = $sql->query (
		'SELECT id, ref, label, qte, price, remise_percent, remise, total_ht, total_ttc, tva_tx FROM '.MAIN_DB_PREFIX.'tmp_caisse as c
			LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON c.fk_article = p.rowid
			ORDER BY id');

		if ( $sql->num_rows($res) ) {

			$ret=array(); $i=0;
			while ( $tab = $sql->fetch_array($res) )
			{
				foreach ( $tab as $cle => $valeur )
				{
					$ret[$i][$cle] = $valeur;
				}
				$i++;
			}
			$tab = $ret;

			for ( $i = 0; $i < count($tab); $i++ ) {

				$remise = $tab[$i]['remise'];
				$total_ht = $tab[$i]['total_ht'] - $remise;

				echo ('<tr><td>'.$tab[$i]['ref'].'</td><td>'.$tab[$i]['label'].'</td><td>'.$tab[$i]['qte'].'</td><td>'.$tab[$i]['remise_percent'].'</td><td class="total">'.number_format ( $total_ht,2, '.', '').' &#8364;</td></tr>'."\n");

			}

		} else {

			echo ('<p>Erreur : aucun article</p>'."\n");

		}

	?>
</table>

<table class="totaux">
	<?php
		echo '<tr><th>Total HT</th><td>'.price2num($obj_facturation->prix_total_ht())." ".$conf->monnaie."</td></tr>\n";
		if ( $obj_facturation->montant_tva() ) {

			echo '<tr><th>TVA</th><td>'.price2num($obj_facturation->montant_tva())." ".$conf->monnaie."</td></tr>\n";

		}
		else {

			echo '<tr><th></th><td>Pas de TVA</td><tr>'."\n";

		}
		echo '<tr><th>Total TTC</th><td>'.price2num($obj_facturation->prix_total_ttc())." ".$conf->monnaie."</td></tr>\n";
	?>
</table>

<script type="text/javascript">

	window.print();

</script>

<a class="lien" href="#" onclick="javascript: window.close(); return(false);">Fermer cette fenetre</a>

</body>
