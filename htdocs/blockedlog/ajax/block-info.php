<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 ATM Consulting       <contact@atm-consulting.fr>
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
 *      \file       htdocs/blockedlog/ajax/block-info.php
 *      \ingroup    blockedlog
 *      \brief      block-info
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
//if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';

$id = GETPOST('id','int');
$block = new BlockedLog($db);

if ((! $user->admin && ! $user->rights->blockedlog->read) || empty($conf->blockedlog->enabled)) accessforbidden();


/*
 * View
 */

print '<div id="pop-info"><table width="100%" height="80%" class="border"><thead><th width="50%" class="left">'.$langs->trans('Field').'</th><th class="left">'.$langs->trans('Value').'</th></thead>';
print '<tbody>';

if ($block->fetch($id) > 0)
{
	$objtoshow = $block->object_data;
	print formatObject($objtoshow, '');
}
else {
	print 'Error, failed to get unalterable log with id '.$id;
}

print '</tbody>';
print '</table></div>';


$db->close();


/**
 * formatObject
 *
 * @param 	Object	$objtoshow		Object to show
 * @param	string	$prefix			Prefix of key
 * @return	string					String formatted
 */
function formatObject($objtoshow, $prefix)
{
	$s = '';

	$newobjtoshow = $objtoshow;

	if (is_object($newobjtoshow) || is_array($newobjtoshow))
	{
		//var_dump($newobjtoshow);
		foreach($newobjtoshow as $key => $val)
		{
			if (! is_object($val) && ! is_array($val))
			{
				$s.='<tr><td>'.($prefix?$prefix.' > ':'').$key.'</td>';
				$s.='<td>';
				if (in_array($key, array('date','datef')))
				{
					$s.=dol_print_date($val, 'dayhour');
				}
				else
				{
					$s.=$val;
				}
				$s.='</td></tr>';
			}
			elseif (is_array($val))
			{
				$s.=formatObject($val, ($prefix?$prefix.' > ':'').$key);
			}
			elseif (is_object($val))
			{
				$s.=formatObject($val, ($prefix?$prefix.' > ':'').$key);
			}
		}
	}

	return $s;
}
