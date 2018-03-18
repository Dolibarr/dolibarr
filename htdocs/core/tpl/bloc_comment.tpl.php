<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Require
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';


// Vars
$userstatic = new User($db);
$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;


// Add comment
print '<br>';
print '<div id="comment">';
print '<form method="POST" action="'.$varpage.'?id='.$object->id.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addcomment">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="comment_element_type" value="'.$object->element.'">';
print '<input type="hidden" name="withproject" value="'.$withproject.'">';

print '<table class="noborder nohover" width="100%">';

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("Comments").'</td>';
print '<td width="25%"></td>';
print '<td width="25%"></td>';
print '<td width="25%"></td>';
print "</tr>\n";

print '<tr class="oddeven">';

// Description
print '<td colspan="3">';

$desc = GETPOST('comment_description');

$doleditor = new DolEditor('comment_description', $desc, '', 80, 'dolibarr_notes', 'In', 0, true, true, ROWS_3, '100%');
print $doleditor->Create(1);

print '</td>';

print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
print '</td></tr>';
print '</table></form>';

// List of comments
if (!empty($object->comments))
{
	// Default color for current user
	$TColors = array($user->id => array('bgcolor'=>'efefef','color'=>'555'));
	$first = true;
	foreach($object->comments as $comment)
	{
		$fk_user = $comment->fk_user_author;
		$userstatic->fetch($fk_user);

		if(empty($TColors[$fk_user])) {
			$bgcolor = random_color(180,240);
			if(!empty($userstatic->color)) {
				$bgcolor = $userstatic->color;
			}
			$color = (colorIsLight($bgcolor))?'555':'fff';
			$TColors[$fk_user] = array('bgcolor'=>$bgcolor,'color'=>$color);
		}
		print '<div class="width100p" style="color:#'.$TColors[$fk_user]['color'].'">';
		if ($fk_user != $user->id) {
			print '<div class="width25p float">&nbsp;</div>';
		}

		print '<div class="width75p float comment comment-table" style="background-color:#'.$TColors[$fk_user]['bgcolor'].'">';
		print '<div class="comment-info comment-cell">';
		if (! empty($user->photo))
		{
			print Form::showphoto('userphoto', $userstatic, 80, 0, 0, '', 'small', 0, 1).'<br/>';
		}
		print $langs->trans('User').' : '.$userstatic->getNomUrl().'<br/>';
		print $langs->trans('Date').' : '.dol_print_date($comment->datec,'dayhoursec');
		print '</div>'; // End comment-info

		print '<div class="comment-cell comment-right">';
		print '<div class="comment-table width100p">';
		print '<div class="comment-description comment-cell">';
		print $comment->description;
		print '</div>'; // End comment-description
		if(($first && $fk_user == $user->id) || $user->admin == 1) {
			print '<a class="comment-delete comment-cell" href="'.$varpage.'?action=deletecomment&id='.$id.'&withproject=1&idcomment='.$comment->id.'" title="'.$langs->trans('Delete').'">';
			print img_picto('', 'delete.png');
			print '</a>';
		}
		print '</div>'; // End comment-table
		print '</div>'; // End comment-right
		print '</div>'; // End comment

		if($fk_user == $user->id) {
			print '<div class="width25p float">&nbsp;</div>';
		}
		print '<div class="clearboth"></div>';
		print '</div>'; // end 100p

		$first = false;
	}
}

print '<br>';
print '</div>';
