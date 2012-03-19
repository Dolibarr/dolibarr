<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011	Regis Houssin			<regis@dolibarr.fr>
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
 */



/**
 * 	Show a HTML Tab with boxes of a particular area including personalized choices of user
 *
 * 	@param	   User         $user		 Object User
 * 	@param	   String       $areacode    Code of area for pages (0=value for Home page)
 * 	@return    int                       <0 if KO, Nb of boxes shown of OK (0 to n)
 */
function printBoxesArea($user,$areacode)
{
    global $conf,$langs,$db;

    $infobox=new InfoBox($db);
    $boxactivated=$infobox->listboxes('activated',$areacode,$user);
    $arrayboxactivatedid=array();
    foreach($boxactivated as $box) $arrayboxactivatedid[$box->id]=$box->id;

    $selectboxlist='';
    if ($conf->use_javascript_ajax)
    {
        $emptyuser=new User($db);
        $boxavailable=$infobox->listboxes('activated',$areacode,$emptyuser,$arrayboxactivatedid);    // Available here is activated for empty user

        $arrayboxtoactivatelabel=array();
        foreach($boxavailable as $box)
        {
            $arrayboxtoactivatelabel[$box->id]=$box->boxlabel;
        }
        $form=new Form($db);

        $selectboxlist=$form->selectarray('boxcombo', $arrayboxtoactivatelabel,'',1);
    }

    print '<script type="text/javascript" language="javascript">
    jQuery(document).ready(function() {
    	jQuery("#boxcombo").change(function() {
    	var boxid=jQuery("#boxcombo").val();
    		if (boxid > 0) {
        		var left_list = cleanSerialize(jQuery("#left").sortable("serialize"));
        		var right_list = cleanSerialize(jQuery("#right").sortable("serialize"));
        		var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
				jQuery.ajax({ url: \''.DOL_URL_ROOT.'/core/ajax/box.php?boxorder=\'+boxorder+\'&boxid=\'+boxid+\'&zone='.$areacode.'&userid='.$user->id.'\',
			        async:   false
		        });
    			//jQuery.get(\''.DOL_URL_ROOT.'/core/ajax/box.php?boxorder=\'+boxorder+\'&boxid=\'+boxid+\'&zone='.$areacode.'&userid='.$user->id.'\');
    			window.location.search=\'mainmenu='.GETPOST("mainmenu").'&leftmenu='.GETPOST('leftmenu').'&action=addbox&boxid=\'+boxid;
				//window.location.href=\''.$_SERVER["PHP_SELF"].'\';
            }
    	});';
    if (! count($arrayboxtoactivatelabel)) print 'jQuery("#boxcombo").hide();';
    print  '
	});
    </script>';

    print load_fiche_titre((count($boxactivated)?$langs->trans("OtherInformationsBoxes"):''),$selectboxlist,'','','otherboxes');

    if (count($boxactivated))
    {
        print '<table width="100%" class="notopnoleftnoright">';
        print '<tr><td class="notopnoleftnoright">'."\n";

        print '<div class="fichehalfleft">';

        print "\n<!-- Box left container -->\n";
        print '<div id="left" class="connectedSortable">'."\n";

        // Define $box_max_lines
        $box_max_lines=5;
        if (! empty($conf->global->MAIN_BOXES_MAXLINES)) $box_max_lines=$conf->global->MAIN_BOXES_MAXLINES;

        $ii=0;
        foreach ($boxactivated as $key => $box)
        {
            if (preg_match('/^A/i',$box->box_order)) // column A
            {
                $ii++;
                //print 'box_id '.$boxactivated[$ii]->box_id.' ';
                //print 'box_order '.$boxactivated[$ii]->box_order.'<br>';
                // Affichage boite key
                $box->loadBox($box_max_lines);
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
        foreach ($boxactivated as $key => $box)
        {
            if (preg_match('/^B/i',$box->box_order)) // colonne B
            {
                $ii++;
                //print 'box_id '.$boxactivated[$ii]->box_id.' ';
                //print 'box_order '.$boxactivated[$ii]->box_order.'<br>';
                // Affichage boite key
                $box->loadBox($box_max_lines);
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
            // For moving
            print 'jQuery(function() {
                        jQuery("#left, #right").sortable({
                            /* placeholder: \'ui-state-highlight\', */
                            handle: \'.boxhandle\',
                            revert: \'invalid\',
                            items: \'.box\',
                            containment: \'.fiche\',
                            connectWith: \'.connectedSortable\',
                            stop: function(event, ui) {
                                updateOrder(0);
                            }
                        });
                    });
            '."\n";
            print 'function updateOrder() {
            		var left_list = cleanSerialize(jQuery("#left").sortable("serialize"));
            		var right_list = cleanSerialize(jQuery("#right").sortable("serialize"));
            		var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
					jQuery.get(\''.DOL_URL_ROOT.'/core/ajax/box.php?boxorder=\'+boxorder+\'&zone='.$areacode.'&userid=\'+'.$user->id.');
            		}'."\n";
            // For closing
            print 'jQuery(document).ready(function() {
                      	jQuery(".boxclose").click(function() {
                      		var self = this;	// because JQuery can modify this
                          	var boxid=self.id.substring(8);
                            jQuery(\'#boxto_\'+boxid).remove();
                            updateOrder();
                       	});
                   });'."\n";
            print '</script>'."\n";
        }
    }

    return count($boxactivated);
}



/**
 *	Class to manage boxes on pages
 */
class InfoBox
{
    var $db;

    /**
     *  Constructor
     *
     *  @param      DoliDb     $db        Database handler
     */
    function InfoBox($db)
    {
        $this->db=$db;
    }


    /**
     *  Return array of boxes qualified for area and user
     *
     *  @param	string	$mode			'available' or 'activated'
     *  @param	string	$zone			Name or area (-1 for all, 0 for Homepage, 1 for xxx, ...)
     *  @param  User    $user	  		Objet user to filter (used only if $zone >= 0)
     *  @param	array	$excludelist	Array of box id (box.box_id = boxes_def.rowid) to exclude
     *  @return array               	Array of boxes
     */
    function listBoxes($mode,$zone,$user,$excludelist=array())
    {
        global $conf;

        $boxes=array();

        $confuserzone='MAIN_BOXES_'.$zone;
        if ($mode == 'activated')
        {
            $sql = "SELECT b.rowid, b.position, b.box_order, b.fk_user,";
            $sql.= " d.rowid as box_id, d.file, d.note, d.tms";
            $sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
            $sql.= " WHERE b.box_id = d.rowid";
            $sql.= " AND d.entity = ".$conf->entity;
            if ($zone >= 0) $sql.= " AND b.position = ".$zone;
            if ($user->id && $user->conf->$confuserzone) $sql.= " AND b.fk_user = ".$user->id;
            else $sql.= " AND b.fk_user = 0";
            $sql.= " ORDER BY b.box_order";
        }
        else
        {
            $sql = "SELECT d.rowid as box_id, d.file, d.note, d.tms";
            $sql.= " FROM ".MAIN_DB_PREFIX."boxes_def as d";
            $sql.= " WHERE entity = ".$conf->entity;
        }

        dol_syslog(get_class($this)."::listBoxes get default box list sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $j = 0;
            while ($j < $num)
            {
                $obj = $this->db->fetch_object($resql);

                if (! in_array($obj->box_id, $excludelist))
                {
                    if (preg_match('/^([^@]+)@([^@]+)$/i',$obj->file,$regs))
                    {
                        $boxname = $regs[1];
                        $module = $regs[2];
                        $relsourcefile = "/".$module."/core/boxes/".$boxname.".php";
                    }
                    else
                    {
                        $boxname=preg_replace('/.php$/i','',$obj->file);
                        $relsourcefile = "/core/boxes/".$boxname.".php";
                    }

                    dol_include_once($relsourcefile);
                    if (class_exists($boxname))
                    {
                        $box=new $boxname($this->db,$obj->note);

                        // box properties
                        $box->rowid=$obj->rowid;
                        $box->id=$obj->box_id;
                        $box->position=$obj->position;
                        $box->box_order=$obj->box_order;
                        $box->fk_user=$obj->fk_user;
                        $box->sourcefile=$relsourcefile;
                        if ($mode == 'activated' && (! $user->id || ! $user->conf->$confuserzone))
                        {
                            if (is_numeric($box->box_order))
                            {
                                if ($box->box_order % 2 == 1) $box->box_order='A'.$box->box_order;
                                elseif ($box->box_order % 2 == 0) $box->box_order='B'.$box->box_order;
                            }
                        }
                        // box_def properties
                        $box->box_id=$obj->box_id;
                        $box->note=$obj->note;

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
                    }
                }
                $j++;
            }
        }
        else
        {
            //dol_print_error($this->db);
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::listBoxes Error ".$this->error, LOG_ERR);
            return array();
        }

        return $boxes;
    }


    /**
     *  Save order of boxes for area and user
     *
     *  @param	string	$zone       		Name of area (0 for Homepage, ...)
     *  @param  string  $boxorder   		List of boxes with correct order 'A:123,456,...-B:789,321...'
     *  @param  int     $userid     		Id of user
     *  @return int                   		<0 if KO, >= 0 if OK
     */
    function saveboxorder($zone,$boxorder,$userid=0)
    {
        global $conf;

        $error=0;

        require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");

        dol_syslog(get_class($this)."::saveboxorder zone=".$zone." userid=".$userid);

        if (! $userid || $userid == 0) return 0;

        $user = new User($this->db);
        $user->id=$userid;

        $this->db->begin();

        // Sauve parametre indiquant que le user a une config dediee
        $confuserzone='MAIN_BOXES_'.$zone;
        $tab[$confuserzone]=1;
        if (dol_set_user_param($this->db, $conf, $user, $tab) < 0)
        {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -3;
        }

        // Delete all lines
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
        $sql.= " USING ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
        $sql.= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
        $sql.= " AND ".MAIN_DB_PREFIX."boxes_def.entity = ".$conf->entity;
        $sql.= " AND ".MAIN_DB_PREFIX."boxes.fk_user = ".$userid;
        $sql.= " AND ".MAIN_DB_PREFIX."boxes.position = ".$zone;

        dol_syslog(get_class($this)."::saveboxorder sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $colonnes=explode('-',$boxorder);
            foreach ($colonnes as $collist)
            {
                $part=explode(':',$collist);
                $colonne=$part[0];
                $list=$part[1];
                dol_syslog(get_class($this)."::saveboxorder column=".$colonne.' list='.$list);

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

                        dol_syslog(get_class($this)."::saveboxorder sql=".$sql);
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
            dol_syslog(get_class($this)."::saveboxorder ".$this->error);
            return -1;
        }
    }

}

?>
