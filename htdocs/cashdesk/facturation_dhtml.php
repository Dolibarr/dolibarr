<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008-2009 Laurent Destailleur   <eldy@uers.sourceforge.net>
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

$langs->load("@cashdesk");

// Verification
if ( strlen ($_GET["code"]) > 1 ) {

	$res = $sql->query (
			"SELECT ".MAIN_DB_PREFIX."product.rowid, ref, label, tva_tx
			FROM ".MAIN_DB_PREFIX."product
			LEFT JOIN ".MAIN_DB_PREFIX."product_stock ON ".MAIN_DB_PREFIX."product.rowid = ".MAIN_DB_PREFIX."product_stock.fk_product
			WHERE envente = 1
				AND fk_product_type = 0
				AND fk_entrepot = '".$conf_fkentrepot."'
				AND ref LIKE '%".$_GET['code']."%'
				OR label LIKE '%".$_GET['code']."%'
			ORDER BY label");

	if ( $nbr = $sql->num_rows($res) ) {

		$resultat = '<ul class="dhtml_bloc">';

		$ret=array(); $i=0;
		while ( $tab = $sql->fetch_array($res) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
		$tab=$ret;

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
					<li class="dhtml_defaut">'.$langs->trans("NoResults").'</li>
				</ul>
			');

	}

}
?>