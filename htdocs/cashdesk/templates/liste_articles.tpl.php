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
<div class="liste_articles_haut">
<div class="liste_articles_bas">

<p class="titre">Panier</p>

<?php
	// Recuperation du contenu de la vente
	$res = $sql->query ('SELECT id, ref, label, qte, price, remise_percent, remise, total_ht, total_ttc FROM '.MAIN_DB_PREFIX.'tmp_caisse as c
			LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON c.fk_article = p.rowid
			ORDER BY id');

	if ( $sql->num_rows($res) ) {

		$ret=array(); $i=0;
		while ( $tab = mysql_fetch_array($res) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
		$tab = $ret;

		for ( $i = 0; $i < count($tab); $i++ ) {

			echo ('<div class="cadre_article">'."\n");
				echo ('<p><a href="facturation_verif.php?action=suppr_article&suppr_id='.$tab[$i]['id'].'" title="Cliquez pour enlever cet article">'.$tab[$i]['ref'].' - '.$tab[$i]['label'].'</a></p>'."\n");

				if ( $tab[$i]['remise_percent'] > 0 ) {

					$remise_percent = ' -'.$tab[$i]['remise_percent'].'%';

				} else {

					$remise_percent = '';

				}

				$remise = $tab[$i]['remise'];
				$total_ht = ($tab[$i]['total_ht'] - $remise);

				echo ('<p>'.$tab[$i]['qte'].' x '.number_format ( $tab[$i]['price'], 2, '.', '' ).$remise_percent.' = '.number_format ($total_ht, 2, '.', '').'&#8364; HT ('.number_format ($tab[$i]['total_ttc'], 2, '.', '').'&#8364; TTC)</p>'."\n");
			echo ('</div>'."\n");

		}

		$obj_facturation->calculTotaux();
		$total_ttc = $obj_facturation->prix_total_ttc();
		echo ('<p class="cadre_prix_total">TOTAL : '.number_format ($total_ttc, 2, '.', '').' '.$conf->monnaie.'<br /></p>'."\n");

	} else {

		echo ('<p class="cadre_aucun_article">Aucun article pour le moment</p>'."\n");

	}

?>
</div>
</div>