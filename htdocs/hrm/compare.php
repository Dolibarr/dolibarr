<?php
/* Copyright (C) 2017		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2021		Gauthier VERDOL				<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021		Greg Rastklan				<greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021		Jean-Pascal BOUDET			<jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021		Grégory BLEMAND				<gregory.blemand@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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

 * \file        htdocs/hrm/compare.php
 * \ingroup     hrm
 * \brief       This file compares skills of user groups
 *
 * Displays a table in three parts.
 * 1- the left part displays the list of users for the selected group 1.
 *
 * 2- the central part displays the skills. Display of the maximum score for this group and the number of occurrences.
 *
 * 3- the right part displays the members of group 2 or the job to be compared
 */


// Load Dolibarr environment
require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/skill.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/evaluation.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/position.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm.lib.php';


// Load translation files required by the page
$langs->load('hrm');

$job = new Job($db);

// Permissions
$permissiontoread = $user->hasRight('hrm', 'evaluation', 'read') || $user->hasRight('hrm', 'compare_advance', 'read');
$permissiontoadd = 0;

if (empty($conf->hrm->enabled)) {
	accessforbidden();
}
if (!$permissiontoread || ($action === 'create' && !$permissiontoadd)) {
	accessforbidden();
}


/*
 * View
 */

$css = array('/hrm/css/style.css');

llxHeader('', $langs->trans('SkillComparison'), '', '', 0, 0, '', $css);

$head = array();

$h = 0;
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("SkillComparison");
$head[$h][2] = 'compare';

print dol_get_fiche_head($head, 'compare', '', 1);

?>
	<script type="text/javascript">

		$(document).ready(function () {

			$("li[fk_user]").click(function () {

				if ($(this).hasClass('disabled')) {
					$(this).removeClass('disabled');
				} else {
					$(this).addClass('disabled');
				}


				var $userl = $(this).closest('ul');
				var listname = $userl.attr('name');

				var TId = [];

				$userl.find('li').each(function (i, item) {

					if ($(item).hasClass('disabled')) {
						TId.push($(item).attr('fk_user'));
					}

				});

				$('#' + listname + '_excluded_id').val(TId.join(','));

			});

		});


	</script>


<?php

$fk_usergroup2 = 0;
$fk_job = (int) GETPOST('fk_job');
if ($fk_job <= 0) {
	$fk_usergroup2 = GETPOST('fk_usergroup2');
}

$fk_usergroup1 = GETPOST('fk_usergroup1');

?>


	<div class="fichecenter">
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>">

			<div class="tabBar tabBarWithBottom">
				<div class="fichehalfleft">
					<table class="border tableforfield" width="100%">
						<tr>
							<td><?php
							print $langs->trans('group1ToCompare').'</td><td>';
							print img_picto('', 'group', 'class="pictofixedwidth"');
							print $form->select_dolgroups($fk_usergroup1, 'fk_usergroup1', 1);
							?></td>
						</tr>
						<tr><td>&nbsp;</td></tr>
						<tr>
							<td><?php
							print $langs->trans('group2ToCompare').'</td><td>';
							print img_picto('', 'group', 'class="pictofixedwidth"');
							print $form->select_dolgroups($fk_usergroup2, 'fk_usergroup2', 1);
							?></td>
						</tr>
						<tr>
							<td><STRONG><?php print $langs->trans('or'); ?></STRONG></td>
						</tr>
						<tr>
							<td><?php
							echo $langs->trans('OrJobToCompare') . '</td><td>';
							$j = new Job($db);
							$jobs = $j->fetchAll();
							$TJobs = array();

							foreach ($jobs as &$j) {
								$TJobs[$j->id] = $j->label;
							}

							print img_picto('', 'jobprofile', 'class="pictofixedwidth"').$form->selectarray('fk_job', $TJobs, $fk_job, 1);
							?></td>
						</tr>
					</table>
				</div>

				<div style="background:#eee;border-radius:5px 0;margin:0px 0 10px;font-style:italic;padding:5px;" class="fichehalfright">
					<!--<h4><?php echo $langs->trans('legend'); ?></h4>-->
						<table class="border" width="100%">
							<tr>
								<td><span style="vertical-align:middle" class="toohappy diffnote little"></span>
								<?php echo $langs->trans('CompetenceAcquiredByOneOrMore'); ?></td>
							</tr>
							<tr>
								<td><span style="vertical-align:middle" class="veryhappy diffnote little"></span>
								<?php echo $langs->trans('MaxlevelGreaterThan'); ?></td>
							</tr>
							<tr>
								<td><span style="vertical-align:middle" class="happy diffnote little"></span>
								<?php echo $langs->trans('MaxLevelEqualTo'); ?></td>
							</tr>
							<tr>
								<td><span style="vertical-align:middle" class="sad diffnote little"></span>
								<?php echo $langs->trans('MaxLevelLowerThan'); ?></td>
							</tr>
							<tr>
								<td><span style="vertical-align:middle" class="toosad diffnote little"></span>
								<?php echo $langs->trans('SkillNotAcquired'); ?></td>
							</tr>
						</table>

				</div>

				<div class="clearboth"></div>

			</div>

			<br><br>
			<div class="center">
				<input class="button" type="SUBMIT" name="bt1" VALUE="<?php print $langs->trans('Refresh'); ?>">
			</div>
			<br><br>

			<div id="compare" width="100%" style="position:relative;">

				<?php if ($fk_usergroup1 > 0 || $fk_usergroup2 > 0 || $fk_job > 0) { ?>
					<table width="100%">
						<tr>
							<th></th>
							<th><?php print $langs->trans('skill'); ?></th>
							<th><?php print $langs->trans('rank'); ?></th>
							<th><?php print $langs->trans('difference'); ?></th>
							<th><?php print $langs->trans('rank'); ?></th>
							<th></th>
						</tr>

						<?php
						echo '<tr><td id="list-user-left" style="width:30%" valign="top">';

						$TUser1 = $TUser2 = array();

						$userlist1 = displayUsersListWithPicto($TUser1, $fk_usergroup1, 'list1');


						$skill = new Skill($db);
						$TSkill1 = getSkillForUsers($TUser1);

						if ($fk_job > 0) {
							$TSkill2 = getSkillForJob($fk_job);

							$job = new Job($db);
							$job->fetch($fk_job);
							$userlist2 = '<ul>
											  <li>
												  <h3>' . $job->label . '</h3>
												  <p>'  . $job->description . '</p>
											  </li>
										  </ul>';
						} else {
							$userlist2 = displayUsersListWithPicto($TUser2, $fk_usergroup2, 'list2');
							$TSkill2 = getSkillForUsers($TUser2);
						}

						$TMergedSkills = mergeSkills($TSkill1, $TSkill2);

						echo $userlist1;

						echo '</td>';

						echo '<td id="" style="width:20%" valign="top">' . skillList($TMergedSkills) . '</td>';
						echo '<td id="" style="width:5%" valign="top">' . rate($TMergedSkills, 'rate1') . '</td>';
						echo '<td id="" style="width:10%" valign="top">' . diff($TMergedSkills) . '</td>';
						echo '<td id="" style="width:5%" valign="top">' . rate($TMergedSkills, 'rate2') . '</td>';

						echo '<td id="list-user-right" style="width:30%" valign="top">';

						echo $userlist2;

						echo '</td></tr>';

						?>

					</table>

				<?php } ?>

			</div>

		</form>

	</div>

<?php

print dol_get_fiche_end();

llxFooter();
$db->close();



/**
 *
 * 	Return a html list element with diff  between required rank  and user rank
 *
 * 		@param array $TMergedSkills skill list with all rate to add good picto
 * 		@return string
 */
function diff(&$TMergedSkills)
{
	$out = '<ul class="diff">';

	foreach ($TMergedSkills as $id => &$sk) {
		$class = 'diffnote';

		if (empty($sk->rate2)) {
			$class .= ' toohappy';
		} elseif (empty($sk->rate1)) {
			$class .= ' toosad';
		} elseif ($sk->rate1 == $sk->rate2) {
			$class .= ' happy';
		} elseif ($sk->rate2 < $sk->rate1) {
			$class .= ' veryhappy';
		} elseif ($sk->rate2 > $sk->rate1) {
			$class .= ' sad';
		}

		$out .= '<li fk_skill="' . $id . '" class="' . $class . '" style="text-align:center;">
	      <span class="' . $class . '">&nbsp;</span>
	    </li>';
	}

	$out .= '</ul>';

	return $out;
}

/**
 * 	Return a html list with rank information
 * 		@param array $TMergedSkills skill list for display
 * 		@param string $field which column of comparison we are working with
 * 		@return string
 */
function rate(&$TMergedSkills, $field)
{
	global $langs, $fk_job;

	$out = '<ul class="competence">';

	foreach ($TMergedSkills as $id => &$sk) {
		$class = "note";
		$how_many = 0;
		if (empty($sk->$field)) {
			$note = 'x';
			$class .= ' none';
		} else {
			$note = $sk->$field;
			$how_many = ($field === 'rate1') ? $sk->how_many_max1 : $sk->how_many_max2;
		}

		if ($field === 'rate2' && $fk_job > 0) {
			$trad = $langs->trans('RequiredRank');
		} else {
			$trad = $langs->trans('HighestRank');
		}

		$out .= '<li fk_skill="' . $id . '" style="text-align:center;">
	      <p><span class="' . $class . ' classfortooltip" title="' . $trad . '">' . $note . '</span>' . ($how_many > 0 ? '<span class="bubble classfortooltip" title="' . $langs->trans('HowManyUserWithThisMaxNote') . '">' . $how_many . '</span>' : '') . '</p>
	    </li>';
	}

	$out .= '</ul>';

	return $out;
}

/**
 * return a html ul list of skills
 *
 * @param array $TMergedSkills skill list for display
 * @return string (ul list in html )
 */
function skillList(&$TMergedSkills)
{
	$out = '<ul class="competence">';

	foreach ($TMergedSkills as $id => &$sk) {
		$out .= '<li fk_skill="' . $id . '">
	      <h3>' . $sk->label . '</h3>
	      <p>' . $sk->description . '</p>
	    </li>';
	}

	$out .= '</ul>';

	return $out;
}

/**
 *  create an array of lines [ skillLabel,description, maxrank on group1 , minrank needed for this skill ]
 *
 * @param array $TSkill1 skill list of first column
 * @param array $TSkill2 skill list of second column
 * @return array
 */
function mergeSkills($TSkill1, $TSkill2)
{
	$Tab = array();

	foreach ($TSkill1 as &$sk) {
		if (empty($Tab[$sk->fk_skill])) {
			$Tab[$sk->fk_skill] = new stdClass();
		}

		$Tab[$sk->fk_skill]->rate1 = $sk->rankorder;
		$Tab[$sk->fk_skill]->how_many_max1 = $sk->how_many_max;
		$Tab[$sk->fk_skill]->label = $sk->label;
		$Tab[$sk->fk_skill]->description = $sk->description;
	}

	foreach ($TSkill2 as &$sk) {
		if (empty($Tab[$sk->fk_skill])) {
			$Tab[$sk->fk_skill] = new stdClass();
		}
		$Tab[$sk->fk_skill]->rate2 = $sk->rankorder;
		$Tab[$sk->fk_skill]->label = $sk->label;
		$Tab[$sk->fk_skill]->description = $sk->description;
		$Tab[$sk->fk_skill]->how_many_max2 = $sk->how_many_max;
	}

	return $Tab;
}

/**
 * 	Display a list of User with picto
 *
 * 	@param 	array 	$TUser 			list of users (employees) in selected usergroup of a column
 * 	@param 	int 	$fk_usergroup 	selected usergroup id
 * 	@param 	string 	$namelist 		html name
 * 	@return string
 */
function displayUsersListWithPicto(&$TUser, $fk_usergroup = 0, $namelist = 'list-user')
{
	global $db, $langs, $conf, $form;

	$out = '';
	if ($fk_usergroup > 0) {
		$list = $namelist . '_excluded_id';

		$excludedIdsList = GETPOST($list);

		$sql = "SELECT u.rowid FROM " . MAIN_DB_PREFIX . "user u
		LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ugu ON (u.rowid = ugu.fk_user)
		WHERE u.statut > 0 AND ugu.entity = ".((int) $conf->entity);
		$sql .= " AND ugu.fk_usergroup=" . ((int) $fk_usergroup);

		$res = $db->query($sql);
		$out .= '<ul name="' . $namelist . '">';

		$TExcludedId = explode(',', $excludedIdsList);

		$out .= '<input id="'.$list.'" type="hidden" name="'.$list.'" value="'.$excludedIdsList.'"> ';

		$job = new Job($db);

		while ($obj = $db->fetch_object($res)) {
			$class = '';

			$user = new User($db);
			$user->fetch($obj->rowid);

			$name = $user->getFullName($langs);
			if (empty($name)) {
				$name = $user->login;
			}

			if (in_array($user->id, $TExcludedId)) {
				$class .= ' disabled';
			} else {
				if (!in_array($user->id, $TUser)) {
					$TUser[] = $user->id;
				}
			}

			$desc = '';

			$jobstring = $job->getLastJobForUser($user->id);
			$desc .= $jobstring;

			$static_eval = new Evaluation($db);
			$evaluation = $static_eval->getLastEvaluationForUser($user->id);

			if (!empty($evaluation) && !empty($evaluation->date_eval)) {
				$desc .= $langs->trans('DateLastEval') . ' : ' . dol_print_date($evaluation->date_eval);
			} else {
				$desc .= $langs->trans('NoEval');
			}

			if (!empty($user->array_options['options_DDA'])) {
				$desc .= '<br>' . $langs->trans('Anciennete') . ' : ' . dol_print_date(strtotime($user->array_options['options_DDA']));
			}

			$out .= '<li fk_user="' . $user->id . '" class="' . $class . '">
		      ' . $form->showphoto('userphoto', $user, 0, 0, 0, 'photoref', 'small', 1, 0, 1) . '
		      <h3>' . $name . '</h3>
		      <p>' . $desc . '</p>
		    </li>';
		}

		$out .= '</ul>';
	}

	return $out;
}


/**
 *
 * 		Allow to get skill(s) of a user
 *
 * 		@param int[] $TUser array of employees we need to get skills
 * 		@return array<int,stdClass>
 */
function getSkillForUsers($TUser)
{
	global $db;

	//I go back to the user with the highest score in a given group for all the skills assessed in that group
	if (empty($TUser)) {
		return array();
	}

	$sql = 'SELECT sk.rowid, sk.label, sk.description, sk.skill_type, sr.fk_object, sr.objecttype, sr.fk_skill, ';
	$sql .= ' MAX(sr.rankorder) as rankorder';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'hrm_skill sk';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'hrm_skillrank sr ON (sk.rowid = sr.fk_skill)';
	$sql .= " WHERE sr.objecttype = '".$db->escape(SkillRank::SKILLRANK_TYPE_USER)."'";
	$sql .= ' AND sr.fk_object IN ('.$db->sanitize(implode(',', $TUser)).')';
	$sql .= " GROUP BY sk.rowid, sk.label, sk.description, sk.skill_type, sr.fk_object, sr.objecttype, sr.fk_skill "; // group par competence

	$resql = $db->query($sql);
	$Tab = array();

	if ($resql) {
		//For each skill, we count the number of times that the max score has been reached within a given group
		$num = 0;
		while ($obj = $db->fetch_object($resql)) {
			$sql1 = "SELECT COUNT(rowid) as how_many_max FROM ".MAIN_DB_PREFIX."hrm_skillrank as sr";
			$sql1 .= " WHERE sr.rankorder = ".((int) $obj->rankorder);
			$sql1 .= " AND sr.objecttype = '".$db->escape(SkillRank::SKILLRANK_TYPE_USER)."'";
			$sql1 .= " AND sr.fk_skill = ".((int) $obj->fk_skill);
			$sql1 .= " AND sr.fk_object IN (".$db->sanitize(implode(',', $TUser)).")";
			$resql1 = $db->query($sql1);

			$objMax = $db->fetch_object($resql1);

			$Tab[$num] = new stdClass();
			$Tab[$num]->fk_skill = $obj->fk_skill;
			$Tab[$num]->label = $obj->label;
			$Tab[$num]->description = $obj->description;
			$Tab[$num]->skill_type = $obj->skill_type;
			$Tab[$num]->fk_object = $obj->fk_object;
			$Tab[$num]->objectType = SkillRank::SKILLRANK_TYPE_USER;
			$Tab[$num]->rankorder = $obj->rankorder;
			$Tab[$num]->how_many_max = $objMax->how_many_max;

			$num++;
		}
	} else {
		dol_print_error($db);
	}

	return $Tab;
}

/**
 * 		Allow to get skill(s) of a job
 *
 * 		@param int $fk_job job we need to get required skills
 * 		@return stdClass[]
 */
function getSkillForJob($fk_job)
{
	global $db;

	if (empty($fk_job)) {
		return array();
	}

	$sql = 'SELECT sk.rowid, sk.label, sk.description, sk.skill_type, sr.fk_object, sr.objecttype, sr.fk_skill,';
	$sql .= " MAX(sr.rankorder) as rankorder";
	$sql .= ' FROM '.MAIN_DB_PREFIX.'hrm_skill as sk';
	$sql .= '	LEFT JOIN '.MAIN_DB_PREFIX.'hrm_skillrank as sr ON (sk.rowid = sr.fk_skill)';
	$sql .= "	WHERE sr.objecttype = '".SkillRank::SKILLRANK_TYPE_JOB."'";
	$sql .= ' AND sr.fk_object = '.((int) $fk_job);
	$sql .= ' GROUP BY sk.rowid, sk.label, sk.description, sk.skill_type, sr.fk_object, sr.objecttype, sr.fk_skill'; // group par competence*/

	$resql = $db->query($sql);
	$Tab = array();

	if ($resql) {
		$num = 0;
		while ($obj = $db->fetch_object($resql)) {
			$Tab[$num] = new stdClass();
			$Tab[$num]->fk_skill = $obj->fk_skill;
			$Tab[$num]->label = $obj->label;
			$Tab[$num]->description = $obj->description;
			$Tab[$num]->skill_type = $obj->skill_type;
			//$Tab[$num]->date_start = '';// du poste
			//$Tab[$num]->date_end = ''; //  du poste
			$Tab[$num]->fk_object = $obj->fk_object;
			$Tab[$num]->objectType = SkillRank::SKILLRANK_TYPE_JOB;
			$Tab[$num]->rankorder = $obj->rankorder;
			$Tab[$num]->how_many_max = $obj->how_many_max;

			$num++;
		}
	} else {
		dol_print_error($db);
	}

	return $Tab;
}
