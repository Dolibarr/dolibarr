<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/core/lib/treeview.lib.php
 *  \ingroup    core
 *  \brief      Libraries for tree views
 */


// ------------------------------- Used by ajax tree view -----------------

/**
 * Show indent and picto of a tree line. Return array with information of line.
 *
 * @param	array	$fulltree		Array of entries in correct order
 * @param 	string	$key			Key of entry into fulltree to show picto
 * @param	int		$silent			Do not output indent and picto, returns only value
 * @return	integer[]					array(0 or 1 if at least one of this level after, 0 or 1 if at least one of higher level after, nbofdirinsub, nbofdocinsub)
 */
function tree_showpad(&$fulltree,$key,$silent=0)
{
	$pos=1;

	// Loop on each pos, because we will output an img for each pos
	while ($pos <= $fulltree[$key]['level'] && $fulltree[$key]['level'] > 0)
	{
		// Process for column $pos

		$atleastoneofthislevelafter=0;
		$nbofdirinsub=0;
		$nbofdocinsub=0;
		$found=0;
		//print 'x'.$key;
		foreach($fulltree as $key2 => $val2)
		{
            //print "x".$pos." ".$key2." ".$found." ".$fulltree[$key2]['level'];
			if ($found == 1) // We are after the entry to show
			{
				if ($fulltree[$key2]['level'] > $pos)
				{
					$nbofdirinsub++;
					if (isset($fulltree[$key2]['cachenbofdoc']) && $fulltree[$key2]['cachenbofdoc'] > 0) $nbofdocinsub+=$fulltree[$key2]['cachenbofdoc'];
				}
				if ($fulltree[$key2]['level'] == $pos)
				{
					$atleastoneofthislevelafter=1;
				}
				if ($fulltree[$key2]['level'] <= $pos)
				{
					break;
				}
			}
			if ($key2 == $key)    // We found ourself, so now every lower level will be counted
			{
				$found=1;
			}
		}
		//print $atleastoneofthislevelafter;

		if (! $silent)
		{
    		if ($atleastoneofthislevelafter)
    		{
    			if ($fulltree[$key]['level'] == $pos) print img_picto_common('','treemenu/branch.gif');
    			else print img_picto_common('','treemenu/line.gif');
    		}
    		else
    		{
    			if ($fulltree[$key]['level'] == $pos) print img_picto_common('','treemenu/branchbottom.gif');
    			else print img_picto_common('','treemenu/linebottom.gif');
    		}
		}
		$pos++;
	}

	return array($atleastoneofthislevelafter,$nbofdirinsub,$nbofdocinsub);
}



// ------------------------------- Used by menu editor, category view, ... -----------------

/**
 *  Recursive function to output menu tree. <ul id="iddivjstree"><li>...</li></ul>
 *  It is also used for the tree of categories.
 *  Note: To have this function working, check you have loaded the js and css for treeview.
 *  $arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js',
 *                   '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
 *	$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');
 *  TODO Replace with jstree plugin instead of treeview plugin.
 *
 *  @param	array	$tab    		Array of all elements
 *  @param  int	    $pere   		Array with parent ids ('rowid'=>,'mainmenu'=>,'leftmenu'=>,'fk_mainmenu=>,'fk_leftmenu=>)
 *  @param  int	    $rang   		Level of element
 *  @param	string	$iddivjstree	Id to use for parent ul element
 *  @return	void
 */
function tree_recur($tab, $pere, $rang, $iddivjstree='iddivjstree')
{
	if (empty($pere['rowid']))
	{
		// Test also done with jstree and dynatree (not able to have <a> inside label)
		print '<script type="text/javascript" language="javascript">
		$(document).ready(function(){
			$("#'.$iddivjstree.'").treeview({
				collapsed: true,
				animated: "fast",
				persist: "cookie",
				control: "#'.$iddivjstree.'control",
				toggle: function() {
					/* window.console && console.log("%o was toggled", this); */
				}
			});
		})
		</script>';

		print '<ul id="'.$iddivjstree.'">';
	}

	if ($rang > 50)	return;	// Protect against infinite loop. Max 50 depth

	//ballayage du tableau
	$sizeoftab=count($tab);
	$ulprinted=0;
	for ($x=0; $x < $sizeoftab; $x++)
	{
		//var_dump($tab[$x]);exit;
		// If an element has $pere for parent
		if ($tab[$x]['fk_menu'] != -1 && $tab[$x]['fk_menu'] == $pere['rowid'])
		{
			if (empty($ulprinted) && ! empty($pere['rowid'])) { print '<ul'.(empty($pere['rowid'])?' id="treeData"':'').'>'; $ulprinted++; }
			print "\n".'<li '.($tab[$x]['statut']?' class="liuseractive"':'class="liuserdisabled"').'>';
			print $tab[$x]['entry'];
			// And now we search all its sons of lower level
			tree_recur($tab,$tab[$x],$rang+1);
			print '</li>';
		}
		elseif (! empty($tab[$x]['rowid']) && $tab[$x]['fk_menu'] == -1 && $tab[$x]['fk_mainmenu'] == $pere['mainmenu'] && $tab[$x]['fk_leftmenu'] == $pere['leftmenu'])
		{
			if (empty($ulprinted) && ! empty($pere['rowid'])) { print '<ul'.(empty($pere['rowid'])?' id="treeData"':'').'>'; $ulprinted++; }
			print "\n".'<li '.($tab[$x]['statut']?' class="liuseractive"':'class="liuserdisabled"').'>';
			print $tab[$x]['entry'];
			// And now we search all its sons of lower level
			tree_recur($tab,$tab[$x],$rang+1);
			print '</li>';
		}
	}
	if (! empty($ulprinted) && ! empty($pere['rowid'])) { print '</ul>'."\n"; }

	if (empty($pere['rowid'])) print '</ul>';
}

