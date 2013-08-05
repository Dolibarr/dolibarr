<?php
/* Copyright (C) 2013      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	\file       htdocs/opensurvey/public/studs.php
 *	\ingroup    opensurvey
 *	\brief      Page to list surveys
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");


// Init vars
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
$result=$object->fetch(0,$numsondage);
if ($result <= 0) dol_print_error('','Failed to get survey id '.$numsondage);

$nblignes=count($object->fetch_lines());


/*
 * Actions
 */

$nbcolonnes = substr_count($object->sujet, ',') + 1;

$listofvoters=explode(',',$_SESSION["savevoter"]);

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
		if (! $resql) dol_print_error($db);
	}
}

// Add vote
if (isset($_POST["boutonp"]) || isset($_POST["boutonp_x"]))
{
	//Si le nom est bien entré
	if (GETPOST('nom'))
	{
		$nouveauchoix = '';
		for ($i=0;$i<$nbcolonnes;$i++)
		{
			if (isset($_POST["choix$i"]) && $_POST["choix$i"] == '1')
			{
				$nouveauchoix.="1";
			}
			else if (isset($_POST["choix$i"]) && $_POST["choix$i"] == '2')
			{
				$nouveauchoix.="2";
			}
			else { // sinon c'est 0
				$nouveauchoix.="0";
			}
		}

		$nom=substr($_POST["nom"],0,64);

		// Check if vote already exists
		$sql = 'SELECT id_users, nom FROM '.MAIN_DB_PREFIX."opensurvey_user_studs WHERE id_sondage='".$db->escape($numsondage)."' AND nom = '".$db->escape($nom)."' ORDER BY id_users";
		$resql = $db->query($sql);
		$num_rows = $db->num_rows($resql);
		if ($num_rows > 0)
		{
			setEventMessage($langs->trans("VoteNameAlreadyExists"),'errors');
			$error++;
		}
		else
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'opensurvey_user_studs (nom, id_sondage, reponses)';
			$sql.= " VALUES ('".$db->escape($nom)."', '".$db->escape($numsondage)."','".$db->escape($nouveauchoix)."')";
			$resql=$db->query($sql);

			if ($resql)
			{
				// Add voter to session
				$_SESSION["savevoter"]=$nom.','.(empty($_SESSION["savevoter"])?'':$_SESSION["savevoter"]);	// Save voter
				$listofvoters=explode(',',$_SESSION["savevoter"]);

				if (! empty($object->mailsonde))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
					$cmailfile=new CMailFile("[".MAIN_APPLICATION_TITLE."] ".$langs->trans("Poll").': '.$object->titre, $object->mail_admin, $conf->global->MAIN_MAIL_EMAIL_FROM, $nom." has filled a line.\nYou can find your poll at the link:\n".getUrlSondage($numsondage));
					$result=$cmailfile->sendfile();
					if ($result)
					{

					}
					else
					{

					}
				}
			}
			else dol_print_error($db);
		}
	}
	else
	{
		$err |= NAME_EMPTY;
	}
}


// Update vote
$testmodifier = false;
$testligneamodifier = false;
$ligneamodifier = -1;
for ($i=0; $i<$nblignes; $i++)
{
	if (isset($_POST['modifierligne'.$i]))
	{
		$ligneamodifier=$i;
		$testligneamodifier=true;
	}

	//test pour voir si une ligne est a modifier
	if (isset($_POST['validermodifier'.$i]))
	{
		$modifier=$i;
		$testmodifier=true;
	}
}

if ($testmodifier)
{
	//var_dump($_POST);exit;
	$nouveauchoix = '';
	for ($i=0;$i<$nbcolonnes;$i++)
	{
		//var_dump($_POST["choix$i"]);
		if (isset($_POST["choix$i"]) && $_POST["choix$i"] == '1')
		{
			$nouveauchoix.="1";
		}
		else if (isset($_POST["choix$i"]) && $_POST["choix$i"] == '2')
		{
			$nouveauchoix.="2";
		}
		else { // sinon c'est 0
			$nouveauchoix.="0";
		}
	}

	$idtomodify=$_POST["idtomodify".$modifier];
	$sql = 'UPDATE '.MAIN_DB_PREFIX."opensurvey_user_studs";
	$sql.= " SET reponses = '".$db->escape($nouveauchoix)."'";
	$sql.= " WHERE id_users = '".$db->escape($idtomodify)."'";

	dol_syslog("sql=".$sql);
	$resql = $db->query($sql);
	if (! $resql) dol_print_error($db);
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
$object=new OpenSurveySondage($db);

$result=$object->fetch(0,$numsondage);
if ($result <= 0)
{
	print $langs->trans("ErrorRecordNotFound");
	llxFooterSurvey();
	exit;
}

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeaderSurvey($object->titre, "", 0, 0, $arrayofjs, $arrayofcss);


// Define format of choices
$toutsujet=explode(",",$object->sujet);
$listofanswers=array();
foreach ($toutsujet as $value)
{
	$tmp=explode('@',$value);
	$listofanswers[]=array('label'=>$tmp[0],'format'=>($tmp[1]?$tmp[1]:'checkbox'));
}
$toutsujet=str_replace("°","'",$toutsujet);


print '<div class="survey_invitation">'.$langs->trans("YouAreInivitedToVote").'</div>';
print $langs->trans("OpenSurveyHowTo").'<br><br>';

print '<div class="corps"> '."\n";

//affichage du titre du sondage
$titre=str_replace("\\","",$object->titre);
print '<strong>'.$titre.'</strong><br>'."\n";

//affichage du nom de l'auteur du sondage
print $langs->trans("InitiatorOfPoll") .' : '.$object->nom_admin.'<br>'."\n";

//affichage des commentaires du sondage
if ($object->commentaires)
{
	print '<br>'.$langs->trans("Description") .' :<br>'."\n";
	$commentaires=dol_nl2br($object->commentaires);
	print $commentaires;
	print '<br>'."\n";
}

print '</div>'."\n";

print '<form name="formulaire" action="studs.php?sondage='.$numsondage.'"'.'#bas" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";
print '<input type="hidden" name="sondage" value="' . $numsondage . '"/>';

print '<div class="cadre"> '."\n";
print '<br><br>'."\n";

// Start to show survey result
print '<table class="resultats">'."\n";

// Show choice titles
if ($object->format=="D"||$object->format=="D+")
{
	//affichage des sujets du sondage
	print '<tr>'."\n";
	print '<td></td>'."\n";

	//affichage des années
	$colspan=1;
	$nbofsujet=count($toutsujet);
	for ($i=0;$i<$nbofsujet;$i++)
	{
		if (isset($toutsujet[$i+1]) && date('Y', intval($toutsujet[$i])) == date('Y', intval($toutsujet[$i+1]))) {
			$colspan++;
		} else {
			print '<td colspan='.$colspan.' class="annee">'.date('Y', intval($toutsujet[$i])).'</td>'."\n";
			$colspan=1;
		}
	}

	print '</tr>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";

	//affichage des mois
	$colspan=1;
	for ($i=0;$i<$nbofsujet;$i++) {
		$cur = intval($toutsujet[$i]);	// intval() est utiliser pour supprimer le suffixe @* qui déplaît logiquement à strftime()

		if (isset($toutsujet[$i+1]) === false) {
			$next = false;
		} else {
			$next = intval($toutsujet[$i+1]);
		}

		if ($next && dol_print_date($cur, "%B") == dol_print_date($next, "%B") && dol_print_date($cur, "%Y") == dol_print_date($next, "%Y")){
			$colspan++;
		} else {
			print '<td colspan='.$colspan.' class="mois">'.dol_print_date($cur, "%B").'</td>'."\n";
			$colspan=1;
		}
	}

	print '</tr>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";

	//affichage des jours
	$colspan=1;
	for ($i=0;$i<$nbofsujet;$i++) {
		$cur = intval($toutsujet[$i]);
		if (isset($toutsujet[$i+1]) === false) {
			$next = false;
		} else {
			$next = intval($toutsujet[$i+1]);
		}
		if ($next && dol_print_date($cur, "%a %e") == dol_print_date($next,"%a %e") && dol_print_date($cur, "%B") == dol_print_date($next, "%B")) {
			$colspan++;
		} else {
			print '<td colspan="'.$colspan.'" class="jour">'.dol_print_date($cur, "%a %e").'</td>'."\n";
			$colspan=1;
		}
	}

	print '</tr>'."\n";

	//affichage des horaires
	if (strpos($object->sujet, '@') !== false) {
		print '<tr>'."\n";
		print '<td></td>'."\n";

		for ($i=0; isset($toutsujet[$i]); $i++) {
			$heures=explode('@',$toutsujet[$i]);
			if (isset($heures[1])) {
				print '<td class="heure">'.$heures[1].'</td>'."\n";
			} else {
				print '<td class="heure"></td>'."\n";
			}
		}

		print '</tr>'."\n";
	}
}
else
{
	//affichage des sujets du sondage
	print '<tr>'."\n";
	print '<td></td>'."\n";

	for ($i=0; isset($toutsujet[$i]); $i++)
	{
		$tmp=explode('@',$toutsujet[$i]);
		print '<td class="sujet">'.$tmp[0].'</td>'."\n";
	}

	print '</tr>'."\n";
}


// Loop on each answer
$sumfor = array();
$sumagainst = array();
$compteur = 0;
$sql ="SELECT id_users, nom, id_sondage, reponses";
$sql.=" FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
$sql.=" WHERE id_sondage = '".$db->escape($numsondage)."'";
dol_syslog('sql='.$sql);
$resql=$db->query($sql);
if (! $resql)
{
	dol_print_error($db);
	exit;
}
$num=$db->num_rows($resql);
while ($compteur < $num)
{
	$obj=$db->fetch_object($resql);

	$ensemblereponses = $obj->reponses;

	print '<tr>'."\n";

	// ligne d'un usager pré-authentifié
	$mod_ok = ($object->canedit || (! empty($nombase) && in_array($nombase, $listofvoters)));

	// Name
	$nombase=str_replace("°","'",$obj->nom);
	print '<td class="nom">'.$nombase.'</td>'."\n";

	// si la ligne n'est pas a changer, on affiche les données
	if (! $testligneamodifier)
	{
		for ($i = 0; $i < $nbcolonnes; $i++)
		{
			$car = substr($ensemblereponses, $i, 1);
			//print 'xx'.$i."-".$car.'-'.$listofanswers[$i]['format'].'zz';

			if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst')))
			{
				if (((string) $car) == "1") print '<td class="ok">OK</td>'."\n";
				else print '<td class="non">KO</td>'."\n";
				// Total
				if (! isset($sumfor[$i])) $sumfor[$i] = 0;
				if (((string) $car) == "1") $sumfor[$i]++;
			}
			if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno')
			{
				if (((string) $car) == "1") print '<td class="ok">'.$langs->trans("Yes").'</td>'."\n";
				else if (((string) $car) == "0") print '<td class="non">'.$langs->trans("No").'</td>'."\n";
				else print '<td class="vide">&nbsp;</td>'."\n";
				// Total
				if (! isset($sumfor[$i])) $sumfor[$i] = 0;
				if (! isset($sumagainst[$i])) $sumagainst[$i] = 0;
				if (((string) $car) == "1") $sumfor[$i]++;
				if (((string) $car) == "0") $sumagainst[$i]++;
			}
			if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst')
			{
				if (((string) $car) == "1") print '<td class="ok">'.$langs->trans("For").'</td>'."\n";
				else if (((string) $car) == "0") print '<td class="non">'.$langs->trans("Against").'</td>'."\n";
				else print '<td class="vide">&nbsp;</td>'."\n";
				// Total
				if (! isset($sumfor[$i])) $sumfor[$i] = 0;
				if (! isset($sumagainst[$i])) $sumagainst[$i] = 0;
				if (((string) $car) == "1") $sumfor[$i]++;
				if (((string) $car) == "0") $sumagainst[$i]++;
			}
		}
	}
	else
	{
		//sinon on remplace les choix de l'utilisateur par une ligne de checkbox pour recuperer de nouvelles valeurs
		if ($compteur == $ligneamodifier)
		{
			for ($i = 0; $i < $nbcolonnes; $i++)
			{
				$car = substr($ensemblereponses, $i, 1);
				print '<td class="vide">';
				if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst')))
				{
					print '<input type="checkbox" name="choix'.$i.'" value="1" ';
					if ($car == '1') print 'checked="checked"';
					print '>';
				}
				if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno')
				{
					$arraychoice=array('2'=>'&nbsp;','0'=>$langs->trans("No"),'1'=>$langs->trans("Yes"));
					print $form->selectarray("choix".$i, $arraychoice, $car);
				}
				if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst')
				{
					$arraychoice=array('2'=>'&nbsp;','0'=>$langs->trans("Against"),'1'=>$langs->trans("For"));
					print $form->selectarray("choix".$i, $arraychoice, $car);
				}
				print '</td>'."\n";
			}
		}
		else
		{
			for ($i = 0; $i < $nbcolonnes; $i++)
			{
				$car = substr($ensemblereponses, $i, 1);
				if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst')))
				{
					if (((string) $car) == "1") print '<td class="ok">OK</td>'."\n";
					else print '<td class="non">KO</td>'."\n";
					// Total
					if (! isset($sumfor[$i])) $sumfor[$i] = 0;
					if (((string) $car) == "1") $sumfor[$i]++;
				}
				if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno')
				{
					if (((string) $car) == "1") print '<td class="ok">'.$langs->trans("For").'</td>'."\n";
					else if (((string) $car) == "0") print '<td class="non">'.$langs->trans("Against").'</td>'."\n";
					else print '<td class="vide">&nbsp;</td>'."\n";
					// Total
					if (! isset($sumfor[$i])) $sumfor[$i] = 0;
					if (! isset($sumagainst[$i])) $sumagainst[$i] = 0;
					if (((string) $car) == "1") $sumfor[$i]++;
					if (((string) $car) == "0") $sumagainst[$i]++;
				}
				if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst')
				{
					if (((string) $car) == "1") print '<td class="ok">'.$langs->trans("For").'</td>'."\n";
					else if (((string) $car) == "0") print '<td class="non">'.$langs->trans("Against").'</td>'."\n";
					else print '<td class="vide">&nbsp;</td>'."\n";
					// Total
					if (! isset($sumfor[$i])) $sumfor[$i] = 0;
					if (! isset($sumagainst[$i])) $sumagainst[$i] = 0;
					if (((string) $car) == "1") $sumfor[$i]++;
					if (((string) $car) == "0") $sumagainst[$i]++;
				}
			}
		}
	}

	// Button edit at end of line
	if ($compteur != $ligneamodifier && $mod_ok)
	{
		print '<td class="casevide"><input type="submit" class="button" name="modifierligne'.$compteur.'" value="'.dol_escape_htmltag($langs->trans("Edit")).'"></td>'."\n";
	}

	//demande de confirmation pour modification de ligne
	for ($i=0; $i<$nblignes; $i++)
	{
		if (isset($_POST["modifierligne".$i]))
		{
			if ($compteur == $i)
			{
				print '<td class="casevide">';
				print '<input type="hidden" name="idtomodify'.$compteur.'" value="'.$obj->id_users.'">';
				print '<input type="submit" class="button" name="validermodifier'.$compteur.'" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print '</td>'."\n";
			}
		}
	}

	$compteur++;
	print '</tr>'."\n";
}

// Add line to add new record
if ($ligneamodifier < 0 && (! isset($_SESSION['nom'])))
{
	print '<tr>'."\n";
	print '<td class="nom">'."\n";
	if (isset($_SESSION['nom']))
	{
		print '<input type=hidden name="nom" value="'.$_SESSION['nom'].'">'.$_SESSION['nom']."\n";
	} else {
		print '<input type="text" name="nom" placeholder="'.dol_escape_htmltag($langs->trans("Name")).'" maxlength="64" size="24">'."\n";
	}
	print '</td>'."\n";

	// affichage des cases de formulaire checkbox pour un nouveau choix
	for ($i=0;$i<$nbcolonnes;$i++)
	{
		print '<td class="vide">';
		if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst')))
		{
			print '<input type="checkbox" name="choix'.$i.'" value="1"';
			if (isset($_POST['choix'.$i]) && $_POST['choix'.$i] == '1' && is_error(NAME_EMPTY) )
			{
				print ' checked="checked"';
			}
			print '>';
		}
		if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno')
		{
			$arraychoice=array('2'=>'&nbsp;','0'=>$langs->trans("No"),'1'=>$langs->trans("Yes"));
			print $form->selectarray("choix".$i, $arraychoice, GETPOST('choix'.$i));
		}
		if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst')
		{
			$arraychoice=array('2'=>'&nbsp;','0'=>$langs->trans("Against"),'1'=>$langs->trans("For"));
			print $form->selectarray("choix".$i, $arraychoice, GETPOST('choix'.$i));
		}
		print '</td>'."\n";
	}

	// Affichage du bouton de formulaire pour inscrire un nouvel utilisateur dans la base
	print '<td><input type="image" name="boutonp" value="'.$langs->trans("Vote").'" src="'.dol_buildpath('/opensurvey/img/add-24.png',1).'"></td>'."\n";
	print '</tr>'."\n";
}

// Select value of best choice (for checkbox columns only)
$nbofcheckbox=0;
for ($i=0; $i < $nbcolonnes; $i++)
{
	if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst')))
	$nbofcheckbox++;
	if (isset($sumfor[$i]))
	{
		if ($i == 0)
		{
			$meilleurecolonne = $sumfor[$i];
		}
		if (! isset($meilleurecolonne) || $sumfor[$i] > $meilleurecolonne)
		{
			$meilleurecolonne = $sumfor[$i];
		}
	}
}

// Show line total
print '<tr>'."\n";
print '<td align="center">'. $langs->trans("Total") .'</td>'."\n";
for ($i = 0; $i < $nbcolonnes; $i++)
{
	$showsumfor = isset($sumfor[$i])?$sumfor[$i]:'';
	$showsumagainst = isset($sumagainst[$i])?$sumagainst[$i]:'';
	if (empty($showsumfor)) $showsumfor = 0;
	if (empty($showsumagainst)) $showsumagainst = 0;

	print '<td>';
	if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst'))) print $showsumfor;
	if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno') print $langs->trans("Yes").': '.$showsumfor.'<br>'.$langs->trans("No").': '.$showsumagainst;
	if (! empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') print $langs->trans("For").': '.$showsumfor.'<br>'.$langs->trans("Against").': '.$showsumagainst;
	print '</td>'."\n";
}
print '</tr>';
// Show picto winner
if ($nbofcheckbox >= 2)
{
	print '<tr>'."\n";
	print '<td class="somme"></td>'."\n";
	for ($i=0; $i < $nbcolonnes; $i++)
	{
		//print 'xx'.(! empty($listofanswers[$i]['format'])).'-'.$sumfor[$i].'-'.$meilleurecolonne;
		if (empty($listofanswers[$i]['format']) || ! in_array($listofanswers[$i]['format'],array('yesno','foragainst')) && isset($sumfor[$i]) && isset($meilleurecolonne) && $sumfor[$i] == $meilleurecolonne)
		{
			print '<td class="somme"><img src="'.dol_buildpath('/opensurvey/img/medaille.png',1).'"></td>'."\n";
		} else {
			print '<td class="somme"></td>'."\n";
		}
	}
	print '</tr>'."\n";
}
print '</table>'."\n";
print '</div>'."\n";

$toutsujet=explode(",",$object->sujet);
$toutsujet=str_replace("°","'",$toutsujet);

$compteursujet=0;
$meilleursujet = '';

for ($i = 0; $i < $nbcolonnes; $i++) {
	if (isset($sumfor[$i]) && isset($meilleurecolonne) && $sumfor[$i] == $meilleurecolonne) {
		$meilleursujet.=", ";
		if ($object->format=="D"||$object->format=="D+") {
			$meilleursujetexport = $toutsujet[$i];

			if (strpos($toutsujet[$i], '@') !== false) {
				$toutsujetdate = explode("@", $toutsujet[$i]);
				$meilleursujet .= dol_print_date($toutsujetdate[0],'daytext'). ' ('.dol_print_date($toutsujetdate[0],'%A').')' . _("for")  . ' ' . $toutsujetdate[1];
			} else {
				$meilleursujet .= dol_print_date($toutsujet[$i],'daytext'). ' ('.dol_print_date($toutsujet[$i],'%A').')';
			}
		}
		else
		{
			$tmps=explode('@',$toutsujet[$i]);
			$meilleursujet .= $tmps[0];
		}

		$compteursujet++;
	}
}

$meilleursujet=substr("$meilleursujet", 1);
$meilleursujet = str_replace("°", "'", $meilleursujet);


// Show best choice
if ($nbofcheckbox >= 2)
{
	$vote_str = $langs->trans('votes');
	print '<p class="affichageresultats">'."\n";

	if (isset($meilleurecolonne) && $compteursujet == "1") {
		print '<img src="'.dol_buildpath('/opensurvey/img/medaille.png',1).'"> ' . $langs->trans('TheBestChoice') . ": <b>$meilleursujet</b> " . $langs->trans('with') . " <b>$meilleurecolonne </b>" . $vote_str . ".\n";
	} elseif (isset($meilleurecolonne)) {
		print '<img src="'.dol_buildpath('/opensurvey/img/medaille.png',1).'"> ' . $langs->trans('TheBestChoices')  . ": <b>$meilleursujet</b> " . $langs->trans('with') . "  <b>$meilleurecolonne </b>" . $vote_str . ".\n";
	}

	print '</p><br>'."\n";
}

print '<br>';


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
	print "<br><b>" . $langs->trans("CommentsOfVoters") . " :</b><br>\n";
	while ( $i < $num_rows)
	{
		$obj=$db->fetch_object($resql);
		print '<div class="comment"><span class="usercomment">';
		if (in_array($obj->usercomment, $listofvoters)) print '<a href="'.$_SERVER["PHP_SELF"].'?deletecomment='.$obj->id_comment.'&sondage='.$numsondage.'"> '.img_picto('', 'delete.png').'</a> ';
		print $obj->usercomment.' :</span> <span class="comment">'.dol_nl2br($obj->comment)."</span></div>";
		$i++;
	}
}

// Form to add comment
print '<div class="addcomment">' .$langs->trans("AddACommentForPoll") . "<br>\n";

print '<textarea name="comment" rows="2" cols="60"></textarea><br>'."\n";
print $langs->trans("Name") .' : ';
print '<input type="text" name="commentuser" maxlength="64" /> &nbsp; '."\n";
print '<input type="submit" class="button" name="ajoutcomment" value="'.dol_escape_htmltag($langs->trans("AddComment")).'"><br>'."\n";
print '</form>'."\n";

print '</div>'."\n";	// div add comment

print '<br><br>';

/*
// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$message='';
$url=$urlwithouturlroot.dol_buildpath('/opensurvey/public/studs.php',1).'?sondage='.$numsondage;
$urlvcal='<a href="'.$url.'" target="_blank">'.$url.'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("UrlForSurvey").': '.$urlvcal;

print '<center>'.$message.'</center>';
*/


print '<a name="bas"></a>'."\n";

llxFooterSurvey();

$db->close();
?>