<?php
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
	include('../master.inc.php');
	require ('include/environnement.php');

	// Verification
	if ( strlen ($_GET["code"]) > 1 ) {

		$res = $sql->query ("
			SELECT llx_product.rowid, ref, label, tva_tx
			FROM llx_product
			LEFT JOIN llx_product_stock ON llx_product.rowid = llx_product_stock.fk_product
			WHERE envente = 1
				AND fk_product_type = 0
				AND fk_entrepot = '".$conf_fkentrepot."'
				AND ref LIKE '%".$_GET['code']."%'
				OR label LIKE '%".$_GET['code']."%'
			ORDER BY label
		;");

		if ( $nbr = $sql->numRows($res) ) {

			$resultat = '<ul class="dhtml_bloc">';

			$tab = $sql->fetchAll($res);
			for ( $i = 0; $i < count ($tab); $i++ ) {

				$resultat .= '
					<li class="dhtml_defaut" title="'.$tab[$i]['ref'].'"
						onMouseOver="javascript: this.className = \'dhtml_selection\';"
						onMouseOut="javascript: this.className = \'dhtml_defaut\';"
					">'.htmlentities($tab[$i]['ref'].' - '.$tab[$i]['label']).'</li>
				';

			}

			$resultat .= '</ul>';

			echo $resultat;

		} else {

			echo ('
				<ul class="dhtml_bloc">
					<li class="dhtml_defaut">'.htmlentities ('Aucun résultat').'</li>
				</ul>
			');

		}

	}
?>