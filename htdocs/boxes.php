<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/boxes.php
 *	\brief      File of class to manage widget boxes
 *	\author     Rodolphe Qiedeville
 *	\author	    Laurent Destailleur
 */



/**
 * 		Show a HTML Tab with boxes of a particular area including personalized choices of user
 *
 * 		@param	   User         $user		 Object User
 * 		@param	   String       $areacode    Code of area for pages (0=value for Home page)
 * 		@return    int                       <0 if KO, Nb of boxes shown of OK (0 to n)
 */
function printBoxesArea($user,$areacode)
{
	global $conf,$langs,$db;

	$infobox=new InfoBox($db);
	$boxarray=$infobox->listboxes($areacode,$user);

	if (count($boxarray))
	{
		print load_fiche_titre($langs->trans("OtherInformationsBoxes"),'','','','otherboxes');
		print '<table width="100%" class="notopnoleftnoright">';
		print '<tr><td class="notopnoleftnoright">'."\n";


        print '<div class="fichehalfleft">';


		print "\n<!-- Box left container -->\n";
		print '<div id="left" class="connectedSortable">'."\n";

		$ii=0;
		foreach ($boxarray as $key => $box)
		{
			if (preg_match('/^A/i',$box->box_order)) // column A
			{
				$ii++;
				//print 'box_id '.$boxarray[$ii]->box_id.' ';
				//print 'box_order '.$boxarray[$ii]->box_order.'<br>';
				// Affichage boite key
				$box->loadBox($conf->box_max_lines);
				$box->showBox();
			}
		}

		$emptybox=new ModeleBoxes($db);
		$emptybox->box_id='A';
		$emptybox->info_box_head=array();
		$emptybox->info_box_contents=array();
		$emptybox->showBox(array(),array());

		print "</div>\n";
		print "<!-- End box container -->\n";

        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		print "\n<!-- Box right container -->\n";
		print '<div id="right" class="connectedSortable">'."\n";

		$ii=0;
		foreach ($boxarray as $key => $box)
		{
			if (preg_match('/^B/i',$box->box_order)) // colonne B
			{
				$ii++;
				//print 'box_id '.$boxarray[$ii]->box_id.' ';
				//print 'box_order '.$boxarray[$ii]->box_order.'<br>';
				// Affichage boite key
				$box->loadBox($conf->box_max_lines);
				$box->showBox();
			}
		}

		$emptybox=new ModeleBoxes($db);
		$emptybox->box_id='B';
		$emptybox->info_box_head=array();
		$emptybox->info_box_contents=array();
		$emptybox->showBox(array(),array());

		print "</div>\n";
		print "<!-- End box container -->\n";

		print '</div></div>';
		print "\n";

		print "</td></tr>";
		print "</table>";

		if ($conf->use_javascript_ajax)
		{
			print "\n";
			print '<script type="text/javascript" language="javascript">';
            print 'jQuery(function() {
                        jQuery("#left, #right").sortable({
                            /* placeholder: \'ui-state-highlight\', */
                            handle: \'.boxhandle\',
                            revert: \'invalid\',
                            items: \'.box\',
                            containment: \'.fiche\',
                            connectWith: \'.connectedSortable\',
                            stop: function(event, ui) {
                                updateOrder();
                            }
                        });
                    });
            ';
            print "\n";
            print 'function updateOrder(){'."\n";
		    print 'var left_list = cleanSerialize(jQuery("#left").sortable("serialize" ));'."\n";
		    print 'var right_list = cleanSerialize(jQuery("#right").sortable("serialize" ));'."\n";
		    print 'var boxorder = \'A:\' + left_list + \'-B:\' + right_list;'."\n";
		    //print 'alert(\'boxorder=\' + boxorder);';
		    print 'var userid = \''.$user->id.'\';'."\n";
		    print 'jQuery.get(\'core/ajaxbox.php?boxorder=\'+boxorder+\'&userid=\'+'.$user->id.');'."\n";
		  	print '}'."\n";
			print '</script>'."\n";
		}
	}

	return count($boxarray);
}



/**
 *	Class to manage boxes on pages
 */
class InfoBox
{
	var $db;

	/**
	 *      Constructor
	 *
	 *      @param      DoliDb     $DB        Database handler
	 */
	function InfoBox($DB)
	{
		$this->db=$DB;
	}


	/**
	 *      Return array of boxes qualified for area and user
	 *
	 *      @param      string     $zone      Name or area (0 for Homepage, ...)
	 *      @param      User       $user	  Objet user
	 *      @return     array                 Array of boxes
	 */
	function listBoxes($zone,$user)
	{
		global $conf;

		$boxes=array();

		$confuserzone='MAIN_BOXES_'.$zone;
		if ($user->id && $user->conf->$confuserzone)
		{
			// Get list of boxes of a particular user (if this one has its own list)
			$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user,";
			$sql.= " d.file, d.note";
			$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
			$sql.= " WHERE b.box_id = d.rowid";
			$sql.= " AND d.entity = ".$conf->entity;
			$sql.= " AND b.position = ".$zone;
			$sql.= " AND b.fk_user = ".$user->id;
			$sql.= " ORDER BY b.box_order";

			dol_syslog("InfoBox::listBoxes get user box list sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num)
				{
					$obj = $this->db->fetch_object($result);

					if (preg_match('/^([^@]+)@([^@]+)$/i',$obj->file,$regs))
					{
						$boxname = $regs[1];
						$module = $regs[2];
						$sourcefile = dol_buildpath("/".$module."/includes/boxes/".$boxname.".php");
					}
					else
					{
						$boxname=preg_replace('/.php$/i','',$obj->file);
						$sourcefile = DOL_DOCUMENT_ROOT."/includes/boxes/".$boxname.".php";
					}

					include_once($sourcefile);
					$box=new $boxname($this->db,$obj->note);

					$box->rowid=$obj->rowid;
					$box->box_id=$obj->box_id;
					$box->position=$obj->position;
					$box->box_order=$obj->box_order;
					$box->fk_user=$obj->fk_user;
					$enabled=true;
					if ($box->depends && count($box->depends) > 0)
					{
						foreach($box->depends as $module)
						{
							//print $module.'<br>';
							if (empty($conf->$module->enabled)) $enabled=false;
						}
					}
					if ($enabled) $boxes[]=$box;
					$j++;
				}
			}
			else {
				$this->error=$this->db->error();
				dol_syslog("InfoBox::listBoxes Error ".$this->error, LOG_ERR);
				return array();
			}
		}
		else
		{
			// Recupere liste des boites active par defaut pour tous
			$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user,";
			$sql.= " d.file, d.note";
			$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
			$sql.= " WHERE b.box_id = d.rowid";
			$sql.= " AND d.entity = ".$conf->entity;
			$sql.= " AND b.position = ".$zone;
			$sql.= " AND b.fk_user = 0";
			$sql.= " ORDER BY b.box_order";

			dol_syslog("InfoBox::listBoxes get default box list sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num)
				{
					$obj = $this->db->fetch_object($result);

					if (preg_match('/^([^@]+)@([^@]+)$/i',$obj->file,$regs))
					{
						$boxname = $regs[1];
						$module = $regs[2];
						$sourcefile = "/".$module."/includes/boxes/".$boxname.".php";
					}
					else
					{
						$boxname=preg_replace('/.php$/i','',$obj->file);
						$sourcefile = "/includes/boxes/".$boxname.".php";
					}

					dol_include_once($sourcefile);
					$box=new $boxname($db,$obj->note);

					$box->rowid=$obj->rowid;
					$box->box_id=$obj->box_id;
					$box->position=$obj->position;
					$box->box_order=$obj->box_order;
					if (is_numeric($box->box_order))
					{
						if ($box->box_order % 2 == 1) $box->box_order='A'.$box->box_order;
						elseif ($box->box_order % 2 == 0) $box->box_order='B'.$box->box_order;
					}
					$box->fk_user=$obj->fk_user;
					$enabled=true;
					if ($box->depends && count($box->depends) > 0)
					{
						foreach($box->depends as $module)
						{
							//print $boxname.'-'.$module.'<br>';
							if (empty($conf->$module->enabled)) $enabled=false;
						}
					}
					if ($enabled) $boxes[]=$box;
					$j++;
				}
			}
			else {
				$this->error=$this->db->error();
				dol_syslog("InfoBox::listBoxes Error ".$this->error, LOG_ERR);
				return array();
			}
		}

		return $boxes;
	}



    /**
     *      Save order of boxes for area and user
     *
     *      @param      string     $zone       Name of area (0 for Homepage, ...)
     *      @param      string     $boxorder   List of boxes with correct order 'A:123,456,...-B:789,321...'
     *      @param      int        $userid     Id of user
     *      @return     int                    <0 if KO, >= 0 if OK
     */
	function saveboxorder($zone,$boxorder,$userid=0)
	{
		global $conf;

		require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");

		dol_syslog("InfoBoxes::saveboxorder zone=".$zone." user=".$userid);

		if (! $userid || $userid == 0) return 0;

		$user = new User($this->db);
        $user->id=$userid;

		$this->db->begin();

		// Sauve parametre indiquant que le user a une
		$confuserzone='MAIN_BOXES_'.$zone;
		$tab[$confuserzone]=1;
		if (dol_set_user_param($this->db, $conf, $user, $tab) < 0)
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -3;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
		$sql.= " USING ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
		$sql.= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
		$sql.= " AND ".MAIN_DB_PREFIX."boxes_def.entity = ".$conf->entity;
		$sql.= " AND ".MAIN_DB_PREFIX."boxes.fk_user = ".$userid;
		$sql.= " AND ".MAIN_DB_PREFIX."boxes.position = ".$zone;

		dol_syslog("InfoBox::saveboxorder sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$colonnes=explode('-',$boxorder);
			foreach ($colonnes as $collist)
			{
				$part=explode(':',$collist);
				$colonne=$part[0];
				$list=$part[1];
				dol_syslog('InfoBox::saveboxorder column='.$colonne.' list='.$list);

				$i=0;
				$listarray=explode(',',$list);
				foreach ($listarray as $id)
				{
					if (is_numeric($id))
					{
						//dol_syslog("aaaaa".count($listarray));
						$i++;
						$ii=sprintf('%02d',$i);
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes";
						$sql.= "(box_id, position, box_order, fk_user)";
						$sql.= " values (";
						$sql.= " ".$id.",";
						$sql.= " ".$zone.",";
						$sql.= " '".$colonne.$ii."',";
						$sql.= " ".$userid;
						$sql.= ")";

						dol_syslog("InfoBox::saveboxorder sql=".$sql);
						$result = $this->db->query($sql);
						if ($result < 0)
						{
							$error++;
							break;
						}
					}
				}
			}
			if ($error)
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
			else
			{
				$this->db->commit();
				return 1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			dol_syslog("InfoBox::saveboxorder ".$this->error);
			return -1;
		}
	}

}

?>
