<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/opensurvey/adminstuds.php
 *	\ingroup    opensurvey
 *	\brief      Page to edit survey
 */

require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");


// Security check
if (!$user->admin) accessforbidden();


// Initialisation des variables
$action=GETPOST('action');
$numsondage = $numsondageadmin = '';
if (GETPOST('sondage'))
{
	if (strlen(GETPOST('sondage')) == 24)	// recuperation du numero de sondage admin (24 car.) dans l'URL
	{
		$numsondageadmin=GETPOST("sondage",'alpha');
		$numsondage=substr($numsondageadmin, 0, 16);
	}
	else
	{
		$numsondageadmin='';
		$numsondage=GETPOST("sondage",'alpha');
	}
}

$object=new Opensurveysondage($db);

$expiredate=dol_mktime(0, 0, 0, GETPOST('expiremonth'), GETPOST('expireday'), GETPOST('expireyear'));



/*
 * Actions
 */


// Delete
if ($action == 'delete_confirm')
{
	$result=$object->delete($user,'',$numsondageadmin);

	header('Location: '.dol_buildpath('/opensurvey/list.php',1));
	exit();
}

// Update
if ($action == 'update')
{
	$error=0;

	if (! GETPOST('nouveautitre'))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Title")),'errors');
		$error++;
		$action = 'edit';
	}

	if (! $error)
	{
		$res=$object->fetch(0,$numsondageadmin);
		if ($res < 0) dol_print_error($db,$object->error);
	}

	if (! $error)
	{
		$object->titre = GETPOST('nouveautitre');
		$object->commentaires = GETPOST('nouveauxcommentaires');
		$object->mail_admin = GETPOST('nouvelleadresse');
		$object->date_fin = $expiredate;
		$object->survey_link_visible = GETPOST('survey_link_visible')=='on'?1:0;
		$object->canedit = GETPOST('canedit')=='on'?1:0;

		$res=$object->update($user);
		if ($res < 0)
		{
			setEventMessage($object->error,'errors');
			$action='edit';
		}
	}
}


// Add comment
if (GETPOST('ajoutcomment'))
{
	$error=0;

	if (! GETPOST('comment'))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Comment")),'errors');
	}
	if (! GETPOST('commentuser'))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("User")),'errors');
	}

	if (! $error)
	{
		$comment = GETPOST("comment");
		$comment_user = GETPOST('commentuser');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."opensurvey_comments (id_sondage, comment, usercomment)";
		$sql.= " VALUES ('".$db->escape($numsondage)."','".$db->escape($comment)."','".$db->escape($comment_user)."')";
		$resql = $db->query($sql);
		dol_syslog("sql=".$sql);
		if (! $resql)
		{
			$err |= COMMENT_INSERT_FAILED;
		}
	}
}

// Delete comment
$idcomment=GETPOST('deletecomment','int');
if ($idcomment)
{
	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'opensurvey_comments WHERE id_comment = '.$idcomment;
	$resql = $db->query($sql);
}


/*
 * View
 */

$form=new Form($db);

$result=$object->fetch(0,$numsondage);
if ($result <= 0)
{
	print $langs->trans("ErrorRecordNotFound");
	llxFooter();
	exit;
}

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeader('',$object->titre, 0, 0, 0, 0, $arrayofjs, $arrayofcss);


// Define format of choices
$toutsujet=explode(",",$object->sujet);
$listofanswers=array();
foreach ($toutsujet as $value)
{
	$tmp=explode('@',$value);
	$listofanswers[]=array('label'=>$tmp[0],'format'=>($tmp[1]?$tmp[1]:'checkbox'));
}
$toutsujet=str_replace("@","<br>",$toutsujet);
$toutsujet=str_replace("°","'",$toutsujet);


print '<form name="updatesurvey" action="'.$_SERVER["PHP_SELF"].'?sondage='.$numsondageadmin.'" method="POST">'."\n";
print '<input type="hidden" name="action" value="update">';

$head = array();

$head[0][0] = '';
$head[0][1] = $langs->trans("Card");
$head[0][2] = 'general';
$h++;

$head[1][0] = 'adminstuds_preview.php?sondage='.$object->id_sondage_admin;
$head[1][1] = $langs->trans("SurveyResults").'/'.$langs->trans("Preview");
$head[1][2] = 'preview';
$h++;

print dol_get_fiche_head($head,'general',$langs->trans("Survey"),0,dol_buildpath('/opensurvey/img/object_opensurvey.png',1),1);


print '<table class="border" width="100%">';

$linkback = '<a href="'.dol_buildpath('/opensurvey/list.php',1).'">'.$langs->trans("BackToList").'</a>';

// Ref
print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object, 'sondage', $linkback, 1, 'id_sondage_admin', 'id_sondage_admin');
print '</td>';
print '</tr>';

// Type
$type=($object->format=="A"||$object->format=="A+")?'classic':'date';
print '<tr><td>'.$langs->trans("Type").'</td><td colspan="2">';
print img_picto('',dol_buildpath('/opensurvey/img/'.($type == 'classic'?'chart-32.png':'calendar-32.png'),1),'width="16"',1);
print ' '.$langs->trans($type=='classic'?"TypeClassic":"TypeDate").'</td></tr>';

// Title
print '<tr><td>';
$adresseadmin=$object->mail_admin;
print $langs->trans("Title") .'</td><td colspan="2">';
if ($action == 'edit')
{
	print '<input type="text" name="nouveautitre" size="40" value="'.dol_escape_htmltag($object->titre).'">';
}
else print $object->titre;
print '</td></tr>';

// Auteur
print '<tr><td>';
print $langs->trans("Author") .'</td><td colspan="2">';
print $object->nom_admin;
print '</td></tr>';

// Description
print '<tr><td>'.$langs->trans("Description") .'</td><td colspan="2">';
if ($action == 'edit')
{
	print '<textarea name="nouveauxcommentaires" rows="7" cols="80">'.$object->commentaires.'</textarea>'."\n";
}
else print dol_nl2br($object->commentaires);
print '</td></tr>';

// EMail
print '<tr><td>'.$langs->trans("EMail") .'</td><td colspan="2">';
if ($action == 'edit')
{
	print '<input type="text" name="nouvelleadresse" size="40" value="'.$object->mail_admin.'">';
}
else print dol_print_email($object->mail_admin);
print '</td></tr>';

// Can edit other votes
print '<tr><td>'.$langs->trans('CanEditVotes').'</td><td colspan="2">';
if ($action == 'edit')
{
	print '<input type="checkbox" name="canedit" size="40"'.($object->canedit?' checked="true"':'').'">';
}
else print yn($object->canedit);
print '</td></tr>';

// Expire date
print '<tr><td>'.$langs->trans('ExpireDate').'</td><td colspan="2">';
if ($action == 'edit') print $form->select_date($expiredate?$expiredate:$object->date_fin,'expire');
else print dol_print_date($object->date_fin,'day');
print '</td></tr>';


// Link
print '<tr><td>'.img_picto('','object_globe.png').' '.$langs->trans("UrlForSurvey",'').'</td><td colspan="2">';

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$url=$urlwithouturlroot.dol_buildpath('/opensurvey/public/studs.php',1).'?sondage='.$numsondage;
$urllink='<a href="'.$url.'" target="_blank">'.$url.'</a>';
print $urllink;

print '</table>';

if ($action == 'edit') print '<center><br><input type="submit" class="button" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'"></center>';

print '</form>'."\n";

dol_fiche_end();


/*
 * Barre d'actions
 */
print '<div class="tabsAction">';

if ($action != 'edit') print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&sondage=' . $numsondageadmin . '">'.$langs->trans("Modify") . '</a>';

if ($action != 'edit') print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?suppressionsondage=1&sondage='.$numsondageadmin.'&amp;action=delete">'.$langs->trans('Delete').'</a>';

print '</div>';

if ($action == 'delete')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?&sondage='.$numsondageadmin, $langs->trans("RemovePoll"), $langs->trans("ConfirmRemovalOfPoll",$id), 'delete_confirm', '', '', 1);
}



print '<br>';


print '<form name="formulaire5" action="#" method="POST">'."\n";

print_fiche_titre($langs->trans("CommentsOfVoters"),'','');

// Comment list
$sql = 'SELECT id_comment, usercomment, comment';
$sql.= ' FROM '.MAIN_DB_PREFIX.'opensurvey_comments';
$sql.= " WHERE id_sondage='".$db->escape($numsondage)."'";
$sql.= " ORDER BY id_comment";
$resql = $db->query($sql);
$num_rows=$db->num_rows($resql);
if ($num_rows > 0)
{
	$i = 0;
	while ( $i < $num_rows)
	{
		$obj=$db->fetch_object($resql);
		print '<a href="'.dol_buildpath('/opensurvey/adminstuds.php',1).'?deletecomment='.$obj->id_comment.'&sondage='.$numsondageadmin.'"> '.img_picto('', 'delete.png').'</a> ';
		print $obj->usercomment.' : '.dol_nl2br($obj->comment)." <br>";
		$i++;
	}
}
else
{
	print $langs->trans("NoCommentYet").'<br>';;
}

print '<br>';

// Add comment
print $langs->trans("AddACommentForPoll") . '<br>';
print '<textarea name="comment" rows="2" cols="80"></textarea><br>'."\n";
print $langs->trans("Name") .' : <input type=text name="commentuser"><br>'."\n";
print '<input type="submit" class="button" name="ajoutcomment" value="'.dol_escape_htmltag($langs->trans("AddComment")).'"><br>'."\n";
if (isset($erreur_commentaire_vide) && $erreur_commentaire_vide=="yes") {
	print "<font color=#FF0000>" . $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Name")) . "</font>";
}

print '</form>';

llxFooterSurvey();

$db->close();
?>