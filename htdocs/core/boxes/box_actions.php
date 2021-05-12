<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2014 	   Charles-Fr BENKE        <charles.fr@benke.fr>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *	\file       htdocs/core/boxes/box_actions.php
 *	\ingroup    actions
 *	\brief      Module to build boxe for events
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show last events
 */
class box_actions extends ModeleBoxes
{
<<<<<<< HEAD
	var $boxcode="lastactions";
	var $boximg="object_action";
	var $boxlabel="BoxLastActions";
	var $depends = array("agenda");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();
=======
    public $boxcode="lastactions";
    public $boximg="object_action";
    public $boxlabel="BoxLastActions";
    public $depends = array("agenda");

	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;

    public $info_box_head = array();
    public $info_box_contents = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
<<<<<<< HEAD
	function __construct($db,$param='')
=======
	public function __construct($db, $param = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    global $user;

	    $this->db = $db;

	    $this->hidden = ! ($user->rights->agenda->myactions->read);
	}

	/**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
<<<<<<< HEAD
	function loadBox($max=5)
=======
	public function loadBox($max = 5)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
        $societestatic = new Societe($db);
        $actionstatic = new ActionComm($db);

<<<<<<< HEAD
		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastActionsToDo",$max));
=======
		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastActionsToDo", $max));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        if ($user->rights->agenda->myactions->read) {
			$sql = "SELECT a.id, a.label, a.datep as dp, a.percent as percentage";
            $sql.= ", ta.code";
            $sql.= ", ta.libelle as type_label";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid";
            $sql.= ", s.code_client";
<<<<<<< HEAD
			$sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm AS ta, ";
			$sql.= MAIN_DB_PREFIX."actioncomm AS a)";
=======
			$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm AS ta, ".MAIN_DB_PREFIX."actioncomm AS a";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
			$sql.= " WHERE a.fk_action = ta.id";
			$sql.= " AND a.entity = ".$conf->entity;
			$sql.= " AND a.percent >= 0 AND a.percent < 100";
			if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
			if($user->societe_id)   $sql.= " AND s.rowid = ".$user->societe_id;
			if (! $user->rights->agenda->allactions->read) $sql.= " AND (a.fk_user_author = ".$user->id . " OR a.fk_user_action = ".$user->id . " OR a.fk_user_done = ".$user->id . ")";
			$sql.= " ORDER BY a.datec DESC";
			$sql.= $db->plimit($max, 0);

			dol_syslog("Box_actions::loadBox", LOG_DEBUG);
			$result = $db->query($sql);
            if ($result) {
				$now=dol_now();
				$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

				$num = $db->num_rows($result);
				$line = 0;
                while ($line < $num) {
					$late = '';
					$objp = $db->fetch_object($result);
					$datelimite = $db->jdate($objp->dp);
                    $actionstatic->id = $objp->id;
                    $actionstatic->label = $objp->label;
                    $actionstatic->type_label = $objp->type_label;
                    $actionstatic->code = $objp->code;
                    $societestatic->id = $objp->socid;
                    $societestatic->name = $objp->name;
                    $societestatic->code_client = $objp->code_client;

                    if ($objp->percentage >= 0 && $objp->percentage < 100 && $datelimite  < ($now - $delay_warning))
                        $late=img_warning($langs->trans("Late"));

					//($langs->transnoentities("Action".$objp->code)!=("Action".$objp->code) ? $langs->transnoentities("Action".$objp->code) : $objp->label)
					$label = empty($objp->label)?$objp->type_label:$objp->label;

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $actionstatic->getNomUrl(1),
                        'text2'=> $late,
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => ($societestatic->id > 0 ? $societestatic->getNomUrl(1) : ''),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
<<<<<<< HEAD
                        'td' => 'align="left" class="nowrap"',
=======
                        'td' => 'class="nowrap left"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        'text' => dol_print_date($datelimite, "dayhour"),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => ($objp->percentage>= 0?$objp->percentage.'%':''),
                    );

                    $this->info_box_contents[$line][] = array(
<<<<<<< HEAD
                        'td' => 'align="right" width="18"',
                        'text' => $actionstatic->LibStatut($objp->percentage,3),
=======
                        'td' => 'class="right" width="18"',
                        'text' => $actionstatic->LibStatut($objp->percentage, 3),
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
<<<<<<< HEAD
                        'td' => 'align="center"',
=======
                        'td' => 'class="center"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        'text'=>$langs->trans("NoActionsToDo"),
                    );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
<<<<<<< HEAD
                'td' => 'align="left" class="nohover opacitymedium"',
=======
                'td' => 'class="nohover opacitymedium left"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
		}
	}

	/**
	 *	Method to show box
	 *
<<<<<<< HEAD
	 *	@param	array	$head       Array with properties of box title
=======
	 *	@param  array	$head       Array with properties of box title
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
<<<<<<< HEAD
    function showBox($head = null, $contents = null, $nooutput=0)
=======
    public function showBox($head = null, $contents = null, $nooutput = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
		global $langs, $conf;
		$out = parent::showBox($this->info_box_head, $this->info_box_contents);

        if (! empty($conf->global->SHOW_DIALOG_HOMEPAGE))
        {
			$actioncejour=false;
			$contents=$this->info_box_contents;
			$nblines=count($contents);
			if ($contents[0][0]['text'] != $langs->trans("NoActionsToDo"))
			{
				$out.= '<div id="dialogboxaction" title="'.$nblines." ".$langs->trans("ActionsToDo").'">';
				$out.= '<table width=100%>';
				for ($line=0, $n=$nblines; $line < $n; $line++)
				{
					if (isset($contents[$line]))
					{
						// on affiche que les évènement du jours ou passé
						// qui ne sont pas à 100%
						$actioncejour=true;

						// TR
						$logo=$contents[$line][0]['logo'];
						$label=$contents[$line][1]['text'];
						$urlevent=$contents[$line][1]['url'];
						$logosoc=$contents[$line][2]['logo'];
						$nomsoc=$contents[$line][3]['text'];
						$urlsoc=$contents[$line][3]['url'];
						$dateligne=$contents[$line][4]['text'];
						$percentage=$contents[$line][5]['text'];
						$out.= '<tr class="oddeven">';
<<<<<<< HEAD
						$out.= '<td align=center>';
						$out.= img_object("",$logo);
						$out.= '</td>';
						$out.= '<td align=center><a href="'.$urlevent.'">'.$label.'</a></td>';
						$out.= '<td align=center><a href="'.$urlsoc.'">'.img_object("",$logosoc)." ".$nomsoc.'</a></td>';
						$out.= '<td align=center>'.$dateligne.'</td>';
						$out.= '<td align=center>'.$percentage.'</td>';
=======
						$out.= '<td class="center">';
						$out.= img_object("", $logo);
						$out.= '</td>';
						$out.= '<td class="center"><a href="'.$urlevent.'">'.$label.'</a></td>';
						$out.= '<td class="center"><a href="'.$urlsoc.'">'.img_object("", $logosoc)." ".$nomsoc.'</a></td>';
						$out.= '<td class="center">'.$dateligne.'</td>';
						$out.= '<td class="center">'.$percentage.'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						$out.= '</tr>';
					}
				}
				$out.= '</table>';
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}
			$out.= '</div>';
			if ($actioncejour)
			{
				$out.= '<script>';
				$out.= '$("#dialogboxaction").dialog({ autoOpen: true });';
				if ($conf->global->SHOW_DIALOG_HOMEPAGE > 1)    // autoclose after this delay
				{
					$out.= 'setTimeout(function(){';
					$out.= '$("#dialogboxaction").dialog("close");';
					$out.= '}, '.($conf->global->SHOW_DIALOG_HOMEPAGE*1000).');';
				}
				$out.= '</script>';
			}
			else
			{
				$out.= '<script>';
				$out.= '$("#dialogboxaction").dialog({ autoOpen: false });';
				$out.= '</script>';
			}
		}

		if ($nooutput) return $out;
		else print $out;

		return '';
<<<<<<< HEAD
	}

}

=======
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
