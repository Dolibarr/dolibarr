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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
function tree_showpad(&$fulltree, $key, $silent = 0)
{
	$pos = 1;

	// Loop on each pos, because we will output an img for each pos
	while ($pos <= $fulltree[$key]['level'] && $fulltree[$key]['level'] > 0) {
		// Process for column $pos

		$atleastoneofthislevelafter = 0;
		$nbofdirinsub = 0;
		$nbofdocinsub = 0;
		$found = 0;
		//print 'x'.$key;
		foreach ($fulltree as $key2 => $val2) {
			//print "x".$pos." ".$key2." ".$found." ".$fulltree[$key2]['level'];
			if ($found == 1) { // We are after the entry to show
				if ($fulltree[$key2]['level'] > $pos) {
					$nbofdirinsub++;
					if (isset($fulltree[$key2]['cachenbofdoc']) && $fulltree[$key2]['cachenbofdoc'] > 0) {
						$nbofdocinsub += $fulltree[$key2]['cachenbofdoc'];
					}
				}
				if ($fulltree[$key2]['level'] == $pos) {
					$atleastoneofthislevelafter = 1;
				}
				if ($fulltree[$key2]['level'] <= $pos) {
					break;
				}
			}
			if ($key2 == $key) {    // We found ourself, so now every lower level will be counted
				$found = 1;
			}
		}
		//print $atleastoneofthislevelafter;

		if (!$silent) {
			if ($atleastoneofthislevelafter) {
				if ($fulltree[$key]['level'] == $pos) {
					print img_picto_common('', 'treemenu/branch.gif');
				} else {
					print img_picto_common('', 'treemenu/line.gif');
				}
			} else {
				if ($fulltree[$key]['level'] == $pos) {
					print img_picto_common('', 'treemenu/branchbottom.gif');
				} else {
					print img_picto_common('', 'treemenu/linebottom.gif');
				}
			}
		}
		$pos++;
	}

	return array($atleastoneofthislevelafter, $nbofdirinsub, $nbofdocinsub);
}



// ------------------------------- Used by menu editor, category view, ... -----------------

/**
 *  Recursive function to output a tree. <ul id="iddivjstree"><li>...</li></ul>
 *  It is also used for the tree of categories.
 *  Note: To have this function working, check you have loaded the js and css for treeview.
 *  $arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js',
 *                   '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
 *	$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');
 *  TODO Replace with jstree plugin instead of treeview plugin.
 *
 *  @param	array	$tab    					Array of all elements
 *  @param  array   $pere   					Array with parent ids ('rowid'=>,'mainmenu'=>,'leftmenu'=>,'fk_mainmenu'=>,'fk_leftmenu'=>)
 *  @param  int	    $rang   					Level of element
 *  @param	string	$iddivjstree				Id to use for parent ul element
 *  @param  int     $donoresetalreadyloaded     Do not reset global array $donoresetalreadyloaded used to avoid to go down on an aleady processed record
 *  @param  int     $showfk         			1=show fk_links to parent into label  (used by menu editor only)
 *  @param	string	$moreparam					Add more param on url of elements
 *  @return	void
 */
function tree_recur($tab, $pere, $rang, $iddivjstree = 'iddivjstree', $donoresetalreadyloaded = 0, $showfk = 0, $moreparam = '')
{
	global $tree_recur_alreadyadded, $menu_handler_to_search;

	if ($rang == 0 && empty($donoresetalreadyloaded)) {
		$tree_recur_alreadyadded = array();
	}

	if ($rang == 0) {
		// Test also done with jstree and dynatree (not able to have <a> inside label)
		print '<script nonce="'.getNonce().'" type="text/javascript">
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

	if ($rang > 50) {
		return; // Protect against infinite loop. Max 50 depth
	}

	// Loop on each element of tree
	$ulprinted = 0;
	foreach ($tab as $tmpkey => $tmpval) {
		$x = $tmpkey;

		//var_dump($tab[$x]);exit;
		// If an element has $pere for parent
		if ($tab[$x]['fk_menu'] != -1 && ((int) $tab[$x]['fk_menu']) == $pere['rowid']) {
			//print 'rang='.$rang.'-x='.$x." rowid=".$tab[$x]['rowid']." tab[x]['fk_leftmenu'] = ".$tab[$x]['fk_leftmenu']." leftmenu pere = ".$pere['leftmenu']."<br>\n";
			if (empty($ulprinted) && !empty($pere['rowid'])) {
				if (!empty($tree_recur_alreadyadded[$tab[$x]['rowid']])) {
					dol_syslog('Error, record with id '.$tab[$x]['rowid'].' seems to be a child of record with id '.$pere['rowid'].' but it was already output. Complete field "leftmenu" and "mainmenu" on ALL records to avoid ambiguity.', LOG_WARNING);
					continue;
				}

				print "\n".'<ul'.(empty($pere['rowid']) ? ' id="treeData"' : '').'>';
				$ulprinted++;
			}
			print "\n".'<li '.(!empty($tab[$x]['statut']) ? ' class="liuseractive"' : 'class="liuserdisabled"').'>';
			if ($showfk) {
				print '<table class="nobordernopadding centpercent"><tr>';
				print '<td class="tdoverflowmax300">';
				print '<span class="paddingleft paddingright">'.$tab[$x]['title'].'</span>';
				print '<span class="opacitymedium">(fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].')</span>';
				print '</td>';
				print '<td class="right nowraponall">';
				print $tab[$x]['buttons'];
				print '</td></tr></table>';
			} else {
				// Show the badge with color for the category
				print $tab[$x]['entry'];
			}
			//print ' -> A '.$tab[$x]['rowid'].' mainmenu='.$tab[$x]['mainmenu'].' leftmenu='.$tab[$x]['leftmenu'].' fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].'<br>'."\n";
			$tree_recur_alreadyadded[$tab[$x]['rowid']] = ($rang + 1);
			// And now we search all its sons of lower level
			tree_recur($tab, $tab[$x], $rang + 1, 'iddivjstree', 0, $showfk);
			print '</li>';
		} elseif (!empty($tab[$x]['rowid']) && ((int) $tab[$x]['fk_menu']) == -1 && $tab[$x]['fk_mainmenu'] == $pere['mainmenu'] && $tab[$x]['fk_leftmenu'] == $pere['leftmenu']) {
			//print 'rang='.$rang.'-x='.$x." rowid=".$tab[$x]['rowid']." tab[x]['fk_leftmenu'] = ".$tab[$x]['fk_leftmenu']." leftmenu pere = ".$pere['leftmenu']."<br>\n";
			if (empty($ulprinted) && !empty($pere['rowid'])) {
				if (!empty($tree_recur_alreadyadded[$tab[$x]['rowid']])) {
					dol_syslog('Error, record with id '.$tab[$x]['rowid'].' seems to be a child of record with id '.$pere['rowid'].' but it was already output. Complete field "leftmenu" and "mainmenu" on ALL records to avoid ambiguity.', LOG_WARNING);
					//print 'Error, record with id '.$tab[$x]['rowid'].' seems to be a child of record with id '.$pere['rowid'].' but it was already output. Complete field "leftmenu" and "mainmenu" on ALL records to avoid ambiguity.';
					continue;
				}

				print "\n".'<ul'.(empty($pere['rowid']) ? ' id="treeData"' : '').'>';
				$ulprinted++;
			}
			print "\n".'<li '.(!empty($tab[$x]['statut']) ? ' class="liuseractive"' : 'class="liuserdisabled"').'>';
			if ($showfk) {
				print '<table class="nobordernopadding centpercent"><tr>';
				print '<td class="tdoverflowmax200">';
				print '<strong class="paddingleft paddingright">';
				print '<a href="edit.php?menu_handler='.$menu_handler_to_search.'&action=edit&token='.newToken().'&menuId='.$tab[$x]['rowid'].$moreparam.'">';
				print $tab[$x]['title'];
				print '</a>';
				print '</strong>';
				print '<span class="small opacitymedium">(mainmenu='.$tab[$x]['mainmenu'].' - leftmenu='.$tab[$x]['leftmenu'].', fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].')</small>';
				print '</td>';
				print '<td class="right nowraponall">';
				print $tab[$x]['buttons'];
				print '</td></tr></table>';
			} else {
				print $tab[$x]['entry'];
			}
			//print ' -> B '.$tab[$x]['rowid'].' mainmenu='.$tab[$x]['mainmenu'].' leftmenu='.$tab[$x]['leftmenu'].' fk_mainmenu='.$tab[$x]['fk_mainmenu'].' fk_leftmenu='.$tab[$x]['fk_leftmenu'].'<br>'."\n";
			$tree_recur_alreadyadded[$tab[$x]['rowid']] = ($rang + 1);
			// And now we search all its sons of lower level
			//print 'Call tree_recur for x='.$x.' rowid='.$tab[$x]['rowid']." fk_mainmenu pere = ".$tab[$x]['fk_mainmenu']." fk_leftmenu pere = ".$tab[$x]['fk_leftmenu']."<br>\n";
			tree_recur($tab, $tab[$x], $rang + 1, 'iddivjstree', 0, $showfk);
			print '</li>';
		}
	}
	if (!empty($ulprinted) && !empty($pere['rowid'])) {
		print '</ul>'."\n";
	}

	if ($rang == 0) {
		print '</ul>';
	}
}
