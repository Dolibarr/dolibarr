<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once 'lib/familydiscounts.lib.php';
require_once 'class/Fare.php';

$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');
llxHeader('', 'Tarifas', '', '', 0, 0, $arrayofjs, $arrayofcss);

print_fiche_titre('Tarifas');

//Fares
$fares = Fare::fetchAll($db);

$categstatic = new Categorie($db);

// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo(0);

// Define data (format for treeview)
$data=array();
$data[] = array('rowid'=>0,'fk_menu'=>-1,'title'=>"racine",'mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
foreach($cate_arbo as $key => $val)
{
	$categstatic->id=$val['id'];
	$categstatic->ref=$val['label'];
	$categstatic->type=$type;
	$li=$categstatic->getNomUrl(1,'',60);

	$entry = '<table class="nobordernopadding centpercent">
		<tr>
			<td>'.$li.'</td>';
	foreach ($fares as $fare) {
		$entry .= '<td><input type="text" name="entry[]" size="2"></td>';
	}

	$entry .= '</tr></table>';

	$data[] = array(
		'rowid'=>$val['rowid'],
		'fk_menu'=>$val['fk_parent'],
		'entry'=>$entry
	);
}


print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td>';

foreach ($fares as $fare) {
	print '<td>'.dol_htmlentities($fare->label).'</td>';
}

print '</tr>';

$nbofentries=(count($data) - 1);

if ($nbofentries > 0)
{
	print '<tr><td colspan="3">';
	fd_tree_recur($data,$data[0],0);
	print '</td></tr>';
}
else
{
	print '<tr>';
	print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '</tr>';
}

print "</table>";

/*
 * Boutons actions
 */
print "<div class='tabsAction'>\n";

print "<a class='butAction' href='create.php'>Crear tarifa</a>";

print "</div>";

llxFooter();