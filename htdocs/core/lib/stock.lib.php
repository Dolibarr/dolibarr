<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/stock.lib.php
 *		\brief      Library file with function for stock module
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function stock_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$object->id;
	$head[$h][1] = $langs->trans("StockMovements");
	$head[$h][2] = 'movements';
	$h++;

	/*
	$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$object->id;
	$head[$h][1] = $langs->trans("EnhancedValue");
	$head[$h][2] = 'value';
	$h++;
	*/

	/* Disabled because will never be implemented. Table always empty.
	if (! empty($conf->global->STOCK_USE_WAREHOUSE_BY_USER))
	{
		// Should not be enabled by defaut because does not work yet correctly because
		// personnal stocks are not tagged into table llx_entrepot
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Users");
		$head[$h][2] = 'user';
		$h++;
	}
	*/

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'stock');

    $head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'stock','remove');

    return $head;
}

///**
// *	Return list of entrepot (for the stock
//  *
// *	@param  string	$selected       Preselected type
// *	@param  string	$htmlname       Name of field in html form
// * 	@param	int	$showempty	Add an empty field
// * 	@param	int	$hidetext	Do not show label before combo box
// * 	@param	int	$size		Width of the select list
// *  @return	void
// */
function select_entrepot($selected='', $htmlname='entrepotid', $showempty=0, $hidetext=0, $size=0)
{
	
    global $db, $langs, $user, $conf;

	if (empty($hidetext)) print $langs->trans("EntrepotStock").': ';
	
	// select the warehouse
	$sql = "SELECT rowid, label, zip";
	$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
	//$sql.= " WHERE statut = 1";
	$sql.= " ORDER BY zip ASC";
	
	dol_syslog("stock.Lib::select_entrepot sql=".$sql);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			if ($size == 0)
				print '<select class="flat" name="'.$htmlname.'">';
			else
				print '<select class="flat" size='.$size.' name="'.$htmlname.'">';
			if ($showempty)
			{
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->label."(".$obj->zip.")</option>";
				$i++;
			}
			print '</select>';
		}
		else
		{
			// if not warehouse, we display an empty hidden field
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
		}
	}
}
