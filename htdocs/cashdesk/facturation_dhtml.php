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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This page is called each time we press a key in the code
 * search form to show product combo list.
 */


//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');

// Change this following line to use the correct relative path (../, ../../, etc)
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php';

//header("Content-type: text/html; charset=UTF-8");
header("Content-type: text/html; charset=".$conf->file->character_set_client);

// Search from criteria
if (dol_strlen($_GET["code"]) >= 0)	// If search criteria is on char length at least
{
	$sql = "SELECT p.rowid, p.ref, p.label, p.tva_tx";
	if (! empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql.= ", ps.reel";
	$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
	if (! empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = '".$conf_fkentrepot."'";
	$sql.= " WHERE p.entity IN (".getEntity('product', 1).")";
	$sql.= " AND p.tosell = 1";
	$sql.= " AND p.fk_product_type = 0";
	// Add criteria on ref/label
	if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))
	{
		$sql.= " AND (p.ref LIKE '".$_GET['code']."%' OR p.label LIKE '".$_GET['code']."%')";
	}
	else
	{
		$sql.= " AND (p.ref LIKE '%".$_GET['code']."%' OR p.label LIKE '%".$_GET['code']."%')";
	}
	$sql.= " ORDER BY label";

	dol_syslog("facturation_dhtml.php sql=".$sql);
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

			$tab_size=count($tab);
			for($i=0;$i < $tab_size;$i++)
			{
				$resultat .= '
					<li class="dhtml_defaut" title="'.$tab[$i]['ref'].'"
						onMouseOver="javascript: this.className = \'dhtml_selection\';"
						onMouseOut="javascript: this.className = \'dhtml_defaut\';"
					>'.$tab[$i]['ref'].' - '.$tab[$i]['label'].'</li>
				';
			}

			$resultat .= '</ul>';

			print $resultat;
		}
		else
		{
			$langs->load("cashdesk");

			print '<ul class="dhtml_bloc">';
			print '<li class="dhtml_defaut">'.$langs->trans("NoResults").'</li>';
			print '</ul>';
		}
	}

}
?>