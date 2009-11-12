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

/**
 * This page is called each time we press a key in the code or description
 * search form to show product combo list
 */
include('../master.inc.php');
require ('include/environnement.php');

$langs->load("@cashdesk");

// Verification
if ( strlen ($_GET["code"]) >= 0 )	// If at least one key
{
	$sql = "SELECT p.rowid, p.ref, p.label, p.tva_tx";
	$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
	if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product";
	$sql.= " WHERE p.envente = 1";
	$sql.= " AND p.fk_product_type = 0";
	if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.=" AND ps.fk_entrepot = '".$conf_fkentrepot."'";
	$sql.= " AND (p.ref LIKE '%".$_GET['code']."%' OR p.label LIKE '%".$_GET['code']."%')";
	$sql.= " ORDER BY label";
	
	dol_syslog($sql);
	$result = $db->query($sql);

	if ($result)
	{
		if ( $nbr = $db->num_rows($result) )
		{
			$resultat = '<ul class="dhtml_bloc">';
			
			$ret=array(); $i=0;
			while ( $tab = $db->fetch_array($result) )
			{
				foreach ( $tab as $cle => $valeur )
				{
					$ret[$i][$cle] = $valeur;
				}
				$i++;
			}
			$tab=$ret;
			
			for ( $i = 0; $i < count ($tab); $i++ )
			{
				$resultat .= '
					<li class="dhtml_defaut" title="'.$tab[$i]['ref'].'"
						onMouseOver="javascript: this.className = \'dhtml_selection\';"
						onMouseOut="javascript: this.className = \'dhtml_defaut\';"
					">'.htmlentities($tab[$i]['ref'].' - '.$tab[$i]['label']).'</li>
				';
			}
			
			$resultat .= '</ul>';
			
			print $resultat;
		}
		else
		{
			print '<ul class="dhtml_bloc">';
			print '<li class="dhtml_defaut">'.$langs->trans("NoResults").'</li>';
			print '</ul>';
		}
	}

}
?>