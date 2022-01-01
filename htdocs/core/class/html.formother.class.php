<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo  <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin 		<patrick.raguin@gmail.com>
 * Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
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
 *	\file       htdocs/core/class/html.formother.class.php
 *  \ingroup    core
 *	\brief      Fichier de la classe des fonctions predefinie de composants html autre
 */


/**
 *	Classe permettant la generation de composants html autre
 *	Only common components are here.
 */
class FormOther
{
    private $db;

    /**
	 * @var string Error code (or message)
	 */
	public $error;


    /**
     *	Constructor
     *
     *	@param	DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    Return HTML select list of export models
     *
     *    @param    string	$selected          Id modele pre-selectionne
     *    @param    string	$htmlname          Nom de la zone select
     *    @param    string	$type              Type des modeles recherches
     *    @param    int		$useempty          Show an empty value in list
     *    @param    int		$fk_user           User that has created the template (this is set to null to get all export model when EXPORTS_SHARE_MODELS is on)
     *    @return	void
     */
    public function select_export_model($selected = '', $htmlname = 'exportmodelid', $type = '', $useempty = 0, $fk_user = null)
    {
        // phpcs:enable
        global $conf, $langs, $user;

    	$sql = "SELECT rowid, label, fk_user";
        $sql .= " FROM ".MAIN_DB_PREFIX."export_model";
        $sql .= " WHERE type = '".$this->db->escape($type)."'";
		if (!empty($fk_user)) $sql .= " AND fk_user IN (0, ".$fk_user.")"; // An export model
        $sql .= " ORDER BY label";
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat minwidth200" name="'.$htmlname.'" id="'.$htmlname.'">';
            if ($useempty)
            {
                print '<option value="-1">&nbsp;</option>';
            }

            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);

                $label = $obj->label;
                if ($obj->fk_user == 0) {
                	$label .= ' <span class="opacitymedium">('.$langs->trans("Everybody").')</span>';
                }
                elseif (!empty($conf->global->EXPORTS_SHARE_MODELS) && empty($fk_user) && is_object($user) && $user->id != $obj->fk_user) {
                	$tmpuser = new User($this->db);
                	$tmpuser->fetch($obj->fk_user);
                	$label .= ' <span class="opacitymedium">('.$tmpuser->getFullName($langs).')</span>';
                }

                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected data-html="'.dol_escape_htmltag($label).'">';
                }
                else
                {
                    print '<option value="'.$obj->rowid.'" data-html="'.dol_escape_htmltag($label).'">';
                }
                print $label;
                print '</option>';
                $i++;
            }
            print "</select>";
            print ajax_combobox($htmlname);
        }
        else {
            dol_print_error($this->db);
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    Return list of export models
     *
     *    @param    string	$selected          Id modele pre-selectionne
     *    @param    string	$htmlname          Nom de la zone select
     *    @param    string	$type              Type des modeles recherches
     *    @param    int		$useempty          Affiche valeur vide dans liste
     *    @param    int		$fk_user           User that has created the template (this is set to null to get all export model when EXPORTS_SHARE_MODELS is on)
     *    @return	void
     */
    public function select_import_model($selected = '', $htmlname = 'importmodelid', $type = '', $useempty = 0, $fk_user = null)
    {
        // phpcs:enable
    	global $conf, $langs, $user;

        $sql = "SELECT rowid, label, fk_user";
        $sql .= " FROM ".MAIN_DB_PREFIX."import_model";
        $sql .= " WHERE type = '".$this->db->escape($type)."'";
        if (!empty($fk_user)) $sql .= " AND fk_user IN (0, ".$fk_user.")"; // An export model
        $sql .= " ORDER BY rowid";
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat minwidth200" name="'.$htmlname.'" id="'.$htmlname.'">';
            if ($useempty)
            {
                print '<option value="-1">&nbsp;</option>';
            }

            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);

                $label = $obj->label;
                if ($obj->fk_user == 0) {
                	$label .= ' <span class="opacitymedium">('.$langs->trans("Everybody").')</span>';
                }
                elseif (!empty($conf->global->EXPORTS_SHARE_MODELS) && empty($fk_user) && is_object($user) && $user->id != $obj->fk_user) {
                	$tmpuser = new User($this->db);
                	$tmpuser->fetch($obj->fk_user);
                	$label .= ' <span class="opacitymedium">('.$tmpuser->getFullName($langs).')</span>';
                }

                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected data-html="'.dol_escape_htmltag($label).'">';
                }
                else
                {
                    print '<option value="'.$obj->rowid.'" data-html="'.dol_escape_htmltag($label).'">';
                }
                print $label;
                print '</option>';
                $i++;
            }
            print "</select>";
            print ajax_combobox($htmlname);
        }
        else {
            dol_print_error($this->db);
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    Return list of ecotaxes with label
     *
     *    @param	string	$selected   Preselected ecotaxes
     *    @param    string	$htmlname	Name of combo list
     *    @return	integer
     */
    public function select_ecotaxes($selected = '', $htmlname = 'ecotaxe_id')
    {
        // phpcs:enable
        global $langs;

        $sql = "SELECT e.rowid, e.code, e.label, e.price, e.organization,";
        $sql .= " c.label as country";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_ecotaxe as e,".MAIN_DB_PREFIX."c_country as c";
        $sql .= " WHERE e.active = 1 AND e.fk_pays = c.rowid";
        $sql .= " ORDER BY country, e.organization ASC, e.code ASC";

    	dol_syslog(get_class($this).'::select_ecotaxes', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);
            $i = 0;
            print '<option value="-1">&nbsp;</option>'."\n";
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected && $selected == $obj->rowid)
                    {
                        print '<option value="'.$obj->rowid.'" selected>';
                    }
                    else
                    {
                        print '<option value="'.$obj->rowid.'">';
                        //print '<option onmouseover="showtip(\''.$obj->label.'\')" onMouseout="hidetip()" value="'.$obj->rowid.'">';
                    }
                    $selectOptionValue = $obj->code.' - '.$obj->label.' : '.price($obj->price).' '.$langs->trans("HT").' ('.$obj->organization.')';
                    print $selectOptionValue;
                    print '</option>';
                    $i++;
                }
            }
            print '</select>';
            return 0;
        }
        else
        {
            dol_print_error($this->db);
            return 1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    Return list of revenue stamp for country
     *
     *    @param	string	$selected   	Value of preselected revenue stamp
     *    @param    string	$htmlname   	Name of combo list
     *    @param    string	$country_code   Country Code
     *    @return	string					HTML select list
     */
    public function select_revenue_stamp($selected = '', $htmlname = 'revenuestamp', $country_code = '')
    {
        // phpcs:enable
    	global $langs;

    	$out = '';

    	$sql = "SELECT r.taux, r.revenuestamp_type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."c_revenuestamp as r,".MAIN_DB_PREFIX."c_country as c";
    	$sql .= " WHERE r.active = 1 AND r.fk_pays = c.rowid";
    	$sql .= " AND c.code = '".$country_code."'";

    	dol_syslog(get_class($this).'::select_revenue_stamp', LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$out .= '<select class="flat" name="'.$htmlname.'">';
    		$num = $this->db->num_rows($resql);
    		$i = 0;
    		$out .= '<option value="0">&nbsp;</option>'."\n";
    		if ($num)
    		{
    			while ($i < $num)
    			{
    				$obj = $this->db->fetch_object($resql);
    				if (($selected && $selected == $obj->taux) || $num == 1)
    				{
    					$out .= '<option value="'.$obj->taux.($obj->revenuestamp_type == 'percent' ? '%' : '').'"'.($obj->revenuestamp_type == 'percent' ? ' data-type="percent"' : '').' selected>';
    				}
    				else
    				{
    					$out .= '<option value="'.$obj->taux.($obj->revenuestamp_type == 'percent' ? '%' : '').'"'.($obj->revenuestamp_type == 'percent' ? ' data-type="percent"' : '').'>';
    					//print '<option onmouseover="showtip(\''.$obj->libelle.'\')" onMouseout="hidetip()" value="'.$obj->rowid.'">';
    				}
    				$out .= $obj->taux.($obj->revenuestamp_type == 'percent' ? '%' : '');
    				$out .= '</option>';
    				$i++;
    			}
    		}
    		$out .= '</select>';
    		return $out;
    	}
    	else
    	{
    		dol_print_error($this->db);
    		return '';
    	}
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    Return a HTML select list to select a percent
     *
     *    @param	integer	$selected      	pourcentage pre-selectionne
     *    @param    string	$htmlname      	nom de la liste deroulante
     *    @param	int		$disabled		Disabled or not
     *    @param    int		$increment     	increment value
     *    @param    int		$start         	start value
     *    @param    int		$end           	end value
     *    @param    int     $showempty      Add also an empty line
     *    @return   string					HTML select string
     */
    public function select_percent($selected = 0, $htmlname = 'percent', $disabled = 0, $increment = 5, $start = 0, $end = 100, $showempty = 0)
    {
        // phpcs:enable
        $return = '<select class="flat" name="'.$htmlname.'" '.($disabled ? 'disabled' : '').'>';
        if ($showempty) $return .= '<option value="-1"'.(($selected == -1 || $selected == '') ? ' selected' : '').'>&nbsp;</option>';

        for ($i = $start; $i <= $end; $i += $increment)
        {
            if ($selected != '' && (int) $selected == $i)
            {
                $return .= '<option value="'.$i.'" selected>';
            }
            else
            {
                $return .= '<option value="'.$i.'">';
            }
            $return .= $i.' % ';
            $return .= '</option>';
        }

        $return .= '</select>';

        return $return;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return select list for categories (to use in form search selectors)
     *
     * @param	int		$type			Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
     * @param   integer	$selected     	Preselected value
     * @param   string	$htmlname      	Name of combo list
     * @param	int		$nocateg		Show also an entry "Not categorized"
     * @param   int     $showempty      Add also an empty line
     * @param   string  $morecss        More CSS
     * @return  string		        	Html combo list code
     * @see	select_all_categories()
     */
    public function select_categories($type, $selected = 0, $htmlname = 'search_categ', $nocateg = 0, $showempty = 1, $morecss = '')
    {
        // phpcs:enable
        global $conf, $langs;
        require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

        // For backward compatibility
        if (is_numeric($type))
        {
            dol_syslog(__METHOD__.': using numeric value for parameter type is deprecated. Use string code instead.', LOG_WARNING);
        }

        // Load list of "categories"
        $static_categs = new Categorie($this->db);
        $tab_categs = $static_categs->get_full_arbo($type);

        $moreforfilter = '';
        // Enhance with select2
        if ($conf->use_javascript_ajax)
        {
            include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
            $comboenhancement = ajax_combobox('select_categ_'.$htmlname);
            $moreforfilter .= $comboenhancement;
        }

        // Print a select with each of them
        $moreforfilter .= '<select class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" id="select_categ_'.$htmlname.'" name="'.$htmlname.'">';
        if ($showempty) $moreforfilter .= '<option value="0">&nbsp;</option>'; // Should use -1 to say nothing

        if (is_array($tab_categs))
        {
            foreach ($tab_categs as $categ)
            {
                $moreforfilter .= '<option value="'.$categ['id'].'"';
                if ($categ['id'] == $selected) $moreforfilter .= ' selected';
                $moreforfilter .= '>'.dol_trunc($categ['fulllabel'], 50, 'middle').'</option>';
            }
        }
        if ($nocateg)
        {
        	$langs->load("categories");
        	$moreforfilter .= '<option value="-2"'.($selected == -2 ? ' selected' : '').'>- '.$langs->trans("NotCategorized").' -</option>';
        }
        $moreforfilter .= '</select>';

        return $moreforfilter;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return select list for categories (to use in form search selectors)
     *
     *  @param	string	$selected     		Preselected value
     *  @param  string	$htmlname      		Name of combo list (example: 'search_sale')
     *  @param  User	$user           	Object user
     *  @param	int		$showstatus			0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
     *  @param	int		$showempty			1=show also an empty value
     *  @param	string	$morecss			More CSS
     *  @param	int		$norepresentative	Show also an entry "Not categorized"
     *  @return string						Html combo list code
     */
    public function select_salesrepresentatives($selected, $htmlname, $user, $showstatus = 0, $showempty = 1, $morecss = '', $norepresentative = 0)
    {
        // phpcs:enable
        global $conf, $langs, $hookmanager;

        $langs->load('users');

        $out = '';
        // Enhance with select2
        if ($conf->use_javascript_ajax)
        {
            include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';

            $comboenhancement = ajax_combobox($htmlname);
            if ($comboenhancement)
            {
            	$out .= $comboenhancement;
            }
        }

	    $reshook = $hookmanager->executeHooks('addSQLWhereFilterOnSelectSalesRep', array(), $this, $action);

        // Select each sales and print them in a select input
        $out .= '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.$htmlname.'" name="'.$htmlname.'">';
        if ($showempty) $out .= '<option value="0">&nbsp;</option>';

        // Get list of users allowed to be viewed
        $sql_usr = "SELECT u.rowid, u.lastname, u.firstname, u.statut, u.login";
        $sql_usr .= " FROM ".MAIN_DB_PREFIX."user as u";

        if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
        {
        	if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
        		$sql_usr .= " WHERE u.entity IS NOT NULL"; // Show all users
        	} else {
        		$sql_usr .= " WHERE EXISTS (SELECT ug.fk_user FROM ".MAIN_DB_PREFIX."usergroup_user as ug WHERE u.rowid = ug.fk_user AND ug.entity IN (".getEntity('usergroup')."))";
        		$sql_usr .= " OR u.entity = 0"; // Show always superadmin
        	}
        }
        else
        {
        	$sql_usr .= " WHERE u.entity IN (".getEntity('user').")";
        }

        if (empty($user->rights->user->user->lire)) $sql_usr .= " AND u.rowid = ".$user->id;
        if (!empty($user->socid)) $sql_usr .= " AND u.fk_soc = ".$user->socid;

	    //Add hook to filter on user (for exemple on usergroup define in custom modules)
	    if (!empty($reshook)) $sql_usr .= $hookmanager->resArray[0];

        // Add existing sales representatives of thirdparty of external user
        if (empty($user->rights->user->user->lire) && $user->socid)
        {
            $sql_usr .= " UNION ";
            $sql_usr .= "SELECT u2.rowid, u2.lastname, u2.firstname, u2.statut, u2.login";
            $sql_usr .= " FROM ".MAIN_DB_PREFIX."user as u2, ".MAIN_DB_PREFIX."societe_commerciaux as sc";

            if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
            {
            	if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
            		$sql_usr .= " WHERE u2.entity IS NOT NULL"; // Show all users
            	} else {
            		$sql_usr .= " WHERE EXISTS (SELECT ug2.fk_user FROM ".MAIN_DB_PREFIX."usergroup_user as ug2 WHERE u2.rowid = ug2.fk_user AND ug2.entity IN (".getEntity('usergroup')."))";
            	}
            }
            else
            {
            	$sql_usr .= " WHERE u2.entity IN (".getEntity('user').")";
            }

            $sql_usr .= " AND u2.rowid = sc.fk_user AND sc.fk_soc=".$user->socid;

	        //Add hook to filter on user (for exemple on usergroup define in custom modules)
	        if (!empty($reshook)) $sql_usr .= $hookmanager->resArray[1];
        }
	    $sql_usr .= " ORDER BY statut DESC, lastname ASC"; // Do not use 'ORDER BY u.statut' here, not compatible with the UNION.
        //print $sql_usr;exit;

        $resql_usr = $this->db->query($sql_usr);
        if ($resql_usr)
        {
            while ($obj_usr = $this->db->fetch_object($resql_usr))
            {
                $out .= '<option value="'.$obj_usr->rowid.'"';

                if ($obj_usr->rowid == $selected) $out .= ' selected';

                $out .= '>';
                $out .= dolGetFirstLastname($obj_usr->firstname, $obj_usr->lastname);
                // Complete name with more info
                $moreinfo = 0;
                if (!empty($conf->global->MAIN_SHOW_LOGIN))
                {
                    $out .= ($moreinfo ? ' - ' : ' (').$obj_usr->login;
                    $moreinfo++;
                }
                if ($showstatus >= 0)
                {
					if ($obj_usr->statut == 1 && $showstatus == 1)
					{
						$out .= ($moreinfo ? ' - ' : ' (').$langs->trans('Enabled');
	                	$moreinfo++;
					}
					if ($obj_usr->statut == 0)
					{
						$out .= ($moreinfo ? ' - ' : ' (').$langs->trans('Disabled');
                		$moreinfo++;
					}
				}
				$out .= ($moreinfo ? ')' : '');
                $out .= '</option>';
            }
            $this->db->free($resql_usr);
        }
        else
        {
            dol_print_error($this->db);
        }

        if ($norepresentative)
        {
        	$langs->load("companies");
        	$out .= '<option value="-2"'.($selected == -2 ? ' selected' : '').'>- '.$langs->trans("NoSalesRepresentativeAffected").' -</option>';
        }

        $out .= '</select>';

        return $out;
    }

    /**
     *	Return list of project and tasks
     *
     *	@param  int		$selectedtask   		Pre-selected task
     *  @param  int		$projectid				Project id
     * 	@param  string	$htmlname    			Name of html select
     * 	@param	int		$modeproject			1 to restrict on projects owned by user
     * 	@param	int		$modetask				1 to restrict on tasks associated to user
     * 	@param	int		$mode					0=Return list of tasks and their projects, 1=Return projects and tasks if exists
     *  @param  int		$useempty       		0=Allow empty values
     *  @param	int		$disablechildoftaskid	1=Disable task that are child of the provided task id
	 *  @param	string	$filteronprojstatus		Filter on project status ('-1'=no filter, '0,1'=Draft+Validated status)
     *  @param	string	$morecss				More css
     *  @return	void
     */
    public function selectProjectTasks($selectedtask = '', $projectid = 0, $htmlname = 'task_parent', $modeproject = 0, $modetask = 0, $mode = 0, $useempty = 0, $disablechildoftaskid = 0, $filteronprojstatus = '', $morecss = '')
    {
        global $user, $langs;

        require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

        //print $modeproject.'-'.$modetask;
        $task = new Task($this->db);
        $tasksarray = $task->getTasksArray($modetask ? $user : 0, $modeproject ? $user : 0, $projectid, 0, $mode, '', $filteronprojstatus);
        if ($tasksarray)
        {
        	print '<select class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'" id="'.$htmlname.'">';
            if ($useempty) print '<option value="0">&nbsp;</option>';
            $j = 0;
            $level = 0;
            $this->_pLineSelect($j, 0, $tasksarray, $level, $selectedtask, $projectid, $disablechildoftaskid);
            print '</select>';

            print ajax_combobox($htmlname);
        }
        else
        {
            print '<div class="warning">'.$langs->trans("NoProject").'</div>';
        }
    }

    /**
     * Write lines of a project (all lines of a project if parent = 0)
     *
     * @param 	int		$inc					Cursor counter
     * @param 	int		$parent					Id of parent task we want to see
     * @param 	array	$lines					Array of task lines
     * @param 	int		$level					Level
     * @param 	int		$selectedtask			Id selected task
     * @param 	int		$selectedproject		Id selected project
     * @param	int		$disablechildoftaskid	1=Disable task that are child of the provided task id
     * @return	void
     */
    private function _pLineSelect(&$inc, $parent, $lines, $level = 0, $selectedtask = 0, $selectedproject = 0, $disablechildoftaskid = 0)
    {
        global $langs, $user, $conf;

        $lastprojectid = 0;

        $numlines = count($lines);
        for ($i = 0; $i < $numlines; $i++) {
            if ($lines[$i]->fk_parent == $parent) {
                //var_dump($selectedproject."--".$selectedtask."--".$lines[$i]->fk_project."_".$lines[$i]->id);		// $lines[$i]->id may be empty if project has no lines

                // Break on a new project
                if ($parent == 0)	// We are on a task at first level
                {
                    if ($lines[$i]->fk_project != $lastprojectid)	// Break found on project
                    {
                        if ($i > 0) print '<option value="0" disabled>----------</option>';
                        print '<option value="'.$lines[$i]->fk_project.'_0"';
                        if ($selectedproject == $lines[$i]->fk_project) print ' selected';
                        print '>'; // Project -> Task
                        print $langs->trans("Project").' '.$lines[$i]->projectref;
                        if (empty($lines[$i]->public))
                        {
                            print ' ('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')';
                        }
                        else
                        {
                            print ' ('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')';
                        }
                        //print '-'.$parent.'-'.$lines[$i]->fk_project.'-'.$lastprojectid;
                        print "</option>\n";

                        $lastprojectid = $lines[$i]->fk_project;
                        $inc++;
                    }
                }

                $newdisablechildoftaskid = $disablechildoftaskid;

                // Print task
                if (isset($lines[$i]->id))		// We use isset because $lines[$i]->id may be null if project has no task and are on root project (tasks may be caught by a left join). We enter here only if '0' or >0
                {
                	// Check if we must disable entry
                	$disabled = 0;
                	if ($disablechildoftaskid && (($lines[$i]->id == $disablechildoftaskid || $lines[$i]->fk_parent == $disablechildoftaskid)))
                	{
               			$disabled++;
               			if ($lines[$i]->fk_parent == $disablechildoftaskid) $newdisablechildoftaskid = $lines[$i]->id; // If task is child of a disabled parent, we will propagate id to disable next child too
                	}

                    print '<option value="'.$lines[$i]->fk_project.'_'.$lines[$i]->id.'"';
                    if (($lines[$i]->id == $selectedtask) || ($lines[$i]->fk_project.'_'.$lines[$i]->id == $selectedtask)) print ' selected';
                    if ($disabled) print ' disabled';
                    print '>';
                    print $langs->trans("Project").' '.$lines[$i]->projectref;
                    print ' '.$lines[$i]->projectlabel;
                    if (empty($lines[$i]->public))
                    {
                        print ' ('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')';
                    }
                    else
                    {
                        print ' ('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')';
                    }
                    if ($lines[$i]->id) print ' > ';
                    for ($k = 0; $k < $level; $k++)
                    {
                        print "&nbsp;&nbsp;&nbsp;";
                    }
                    print $lines[$i]->ref.' '.$lines[$i]->label."</option>\n";
                    $inc++;
                }

                $level++;
                if ($lines[$i]->id) $this->_pLineSelect($inc, $lines[$i]->id, $lines, $level, $selectedtask, $selectedproject, $newdisablechildoftaskid);
                $level--;
            }
        }
    }


    /**
     *  Output a HTML thumb of color or a text if not defined.
     *
     *  @param	string		$color				String with hex (FFFFFF) or comma RGB ('255,255,255')
     *  @param	string		$textifnotdefined	Text to show if color not defined
     *  @return	string							HTML code for color thumb
     *  @see selectColor()
     */
    public static function showColor($color, $textifnotdefined = '')
    {
    	$textcolor = 'FFF';
    	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
    	if (colorIsLight($color)) $textcolor = '000';

    	$color = colorArrayToHex(colorStringToArray($color, array()), '');

		if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; color: #'.$textcolor.'; background-color: #'.$color.'" value="'.$color.'">';
		else print $textifnotdefined;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Output a HTML code to select a color
     *
     *  @param	string		$set_color		Pre-selected color
     *  @param	string		$prefix			Name of HTML field
     *  @param	string		$form_name		Deprecated. Not used.
     *  @param	int			$showcolorbox	1=Show color code and color box, 0=Show only color code
     *  @param 	array		$arrayofcolors	Array of colors. Example: array('29527A','5229A3','A32929','7A367A','B1365F','0D7813')
     *  @return	void
     *  @deprecated Use instead selectColor
     *  @see selectColor()
     */
    public function select_color($set_color = '', $prefix = 'f_color', $form_name = '', $showcolorbox = 1, $arrayofcolors = '')
    {
        // phpcs:enable
    	print $this->selectColor($set_color, $prefix, $form_name, $showcolorbox, $arrayofcolors);
    }

    /**
     *  Output a HTML code to select a color. Field will return an hexa color like '334455'.
     *
     *  @param	string		$set_color		Pre-selected color
     *  @param	string		$prefix			Name of HTML field
     *  @param	string		$form_name		Deprecated. Not used.
     *  @param	int			$showcolorbox	1=Show color code and color box, 0=Show only color code
     *  @param 	array		$arrayofcolors	Array of colors. Example: array('29527A','5229A3','A32929','7A367A','B1365F','0D7813')
     *  @param	string		$morecss		Add css style into input field
     *  @return	string
     *  @see showColor()
     */
    public static function selectColor($set_color = '', $prefix = 'f_color', $form_name = '', $showcolorbox = 1, $arrayofcolors = '', $morecss = '')
    {
        // Deprecation warning
        if ($form_name) {
            dol_syslog(__METHOD__.": form_name parameter is deprecated", LOG_WARNING);
        }

        global $langs, $conf;

        $out = '';

        if (!is_array($arrayofcolors) || count($arrayofcolors) < 1)
        {
            $langs->load("other");
            if (empty($conf->dol_use_jmobile) && !empty($conf->use_javascript_ajax))
            {
	            $out .= '<link rel="stylesheet" media="screen" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css" />';
	            $out .= '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/jpicker-1.1.6.js"></script>';
	            $out .= '<script type="text/javascript">
	             jQuery(document).ready(function(){
	                $(\'#colorpicker'.$prefix.'\').jPicker( {
	                window: {
	                  title: \''.dol_escape_js($langs->trans("SelectAColor")).'\', /* any title for the jPicker window itself - displays "Drag Markers To Pick A Color" if left null */
	                  effects:
	                    {
	                    type: \'show\', /* effect used to show/hide an expandable picker. Acceptable values "slide", "show", "fade" */
	                    speed:
	                    {
	                      show: \'fast\', /* duration of "show" effect. Acceptable values are "fast", "slow", or time in ms */
	                      hide: \'fast\' /* duration of "hide" effect. Acceptable values are "fast", "slow", or time in ms */
	                    }
	                    },
	                  position:
	                    {
	                    x: \'screenCenter\', /* acceptable values "left", "center", "right", "screenCenter", or relative px value */
	                    y: \'center\' /* acceptable values "top", "bottom", "center", or relative px value */
	                    },
	                },
	                images: {
	                    clientPath: \''.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/images/\',
	                    picker: { file: \'../../../../../theme/common/colorpicker.png\', width: 14, height: 14 }
	          		},
	                localization: // alter these to change the text presented by the picker (e.g. different language)
	                  {
	                    text:
	                    {
	                      title: \''.dol_escape_js($langs->trans("SelectAColor")).'\',
	                      newColor: \''.dol_escape_js($langs->trans("New")).'\',
	                      currentColor: \''.dol_escape_js($langs->trans("Current")).'\',
	                      ok: \''.dol_escape_js($langs->trans("Save")).'\',
	                      cancel: \''.dol_escape_js($langs->trans("Cancel")).'\'
	                    }
	                  }
			        } ); });
	             </script>';
            }
            $out .= '<input id="colorpicker'.$prefix.'" name="'.$prefix.'" size="6" maxlength="7" class="flat'.($morecss ? ' '.$morecss : '').'" type="text" value="'.dol_escape_htmltag($set_color).'" />';
        }
        else  // In most cases, this is not used. We used instead function with no specific list of colors
        {
        	if (empty($conf->dol_use_jmobile) && !empty($conf->use_javascript_ajax))
            {
	        	$out .= '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/colorpicker/jquery.colorpicker.css" type="text/css" media="screen" />';
	            $out .= '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/colorpicker/jquery.colorpicker.js" type="text/javascript"></script>';
	            $out .= '<script type="text/javascript">
	             jQuery(document).ready(function(){
	                 jQuery(\'#colorpicker'.$prefix.'\').colorpicker({
	                     size: 14,
	                     label: \'\',
	                     hide: true
	                 });
	             });
	             </script>';
            }
            $out .= '<select id="colorpicker'.$prefix.'" class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$prefix.'">';
            //print '<option value="-1">&nbsp;</option>';
            foreach ($arrayofcolors as $val)
            {
                $out .= '<option value="'.$val.'"';
                if ($set_color == $val) $out .= ' selected';
                $out .= '>'.$val.'</option>';
            }
            $out .= '</select>';
        }

        return $out;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Creation d'un icone de couleur
     *
     *	@param	string	$color		Couleur de l'image
     *	@param	string	$module 	Nom du module
     *	@param	string	$name		Nom de l'image
     *	@param	int		$x 			Largeur de l'image en pixels
     *	@param	int		$y      	Hauteur de l'image en pixels
     *	@return	void
     */
    public function CreateColorIcon($color, $module, $name, $x = '12', $y = '12')
    {
        // phpcs:enable
        global $conf;

        $file = $conf->$module->dir_temp.'/'.$name.'.png';

        // On cree le repertoire contenant les icones
        if (!file_exists($conf->$module->dir_temp))
        {
            dol_mkdir($conf->$module->dir_temp);
        }

        // On cree l'image en vraies couleurs
        $image = imagecreatetruecolor($x, $y);

        $color = substr($color, 1, 6);

        $rouge = hexdec(substr($color, 0, 2)); //conversion du canal rouge
        $vert  = hexdec(substr($color, 2, 2)); //conversion du canal vert
        $bleu  = hexdec(substr($color, 4, 2)); //conversion du canal bleu

        $couleur = imagecolorallocate($image, $rouge, $vert, $bleu);
        //print $rouge.$vert.$bleu;
        imagefill($image, 0, 0, $couleur); //on remplit l'image
        // On cree la couleur et on l'attribue a une variable pour ne pas la perdre
        ImagePng($image, $file); //renvoie une image sous format png
        ImageDestroy($image);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    	Return HTML combo list of week
     *
     *    	@param	string		$selected          Preselected value
     *    	@param  string		$htmlname          Nom de la zone select
     *    	@param  int			$useempty          Affiche valeur vide dans liste
     *    	@return	string
     */
    public function select_dayofweek($selected = '', $htmlname = 'weekid', $useempty = 0)
    {
        // phpcs:enable
        global $langs;

        $week = array(
            0=>$langs->trans("Day0"),
            1=>$langs->trans("Day1"),
            2=>$langs->trans("Day2"),
            3=>$langs->trans("Day3"),
            4=>$langs->trans("Day4"),
            5=>$langs->trans("Day5"),
            6=>$langs->trans("Day6")
        );

        $select_week = '<select class="flat" name="'.$htmlname.'">';
        if ($useempty)
        {
            $select_week .= '<option value="-1">&nbsp;</option>';
        }
        foreach ($week as $key => $val)
        {
            if ($selected == $key)
            {
                $select_week .= '<option value="'.$key.'" selected>';
            }
            else
            {
                $select_week .= '<option value="'.$key.'">';
            }
            $select_week .= $val;
            $select_week .= '</option>';
        }
        $select_week .= '</select>';
        return $select_week;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *      Return HTML combo list of month
     *
     *      @param  string      $selected          	Preselected value
     *      @param  string      $htmlname          	Name of HTML select object
     *      @param  int         $useempty          	Show empty in list
     *      @param  int         $longlabel         	Show long label
     *      @param	string		$morecss			More Css
     *      @return string
     */
    public function select_month($selected = '', $htmlname = 'monthid', $useempty = 0, $longlabel = 0, $morecss = 'maxwidth50imp valignmiddle')
    {
        // phpcs:enable
        global $langs;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

        if ($longlabel) $montharray = monthArray($langs, 0); // Get array
        else $montharray = monthArray($langs, 1);

        $select_month = '<select class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'" id="'.$htmlname.'">';
        if ($useempty)
        {
            $select_month .= '<option value="0">&nbsp;</option>';
        }
        foreach ($montharray as $key => $val)
        {
            if ($selected == $key)
            {
                $select_month .= '<option value="'.$key.'" selected>';
            }
            else
            {
                $select_month .= '<option value="'.$key.'">';
            }
            $select_month .= $val;
            $select_month .= '</option>';
        }
        $select_month .= '</select>';
        return $select_month;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Return HTML combo list of years
     *
     *  @param  string		$selected       Preselected value (''=current year, -1=none, year otherwise)
     *  @param  string		$htmlname       Name of HTML select object
     *  @param  int			$useempty       Affiche valeur vide dans liste
     *  @param  int			$min_year       Offset of minimum year into list (by default current year -10)
     *  @param  int		    $max_year		Offset of maximum year into list (by default current year + 5)
     *  @param	int			$offset			Offset
     *  @param	int			$invert			Invert
     *  @param	string		$option			Option
     *  @param	string		$morecss		More CSS
     *  @return	string
     */
    public function select_year($selected = '', $htmlname = 'yearid', $useempty = 0, $min_year = 10, $max_year = 5, $offset = 0, $invert = 0, $option = '', $morecss = 'valignmiddle maxwidth75imp')
    {
        // phpcs:enable
        print $this->selectyear($selected, $htmlname, $useempty, $min_year, $max_year, $offset, $invert, $option, $morecss);
    }

    /**
     *	Return HTML combo list of years
     *
     *  @param  string	$selected       Preselected value (''=current year, -1=none, year otherwise)
     *  @param  string	$htmlname       Name of HTML select object
     *  @param  int	    $useempty       Affiche valeur vide dans liste
     *  @param  int	    $min_year		Offset of minimum year into list (by default current year -10)
     *  @param  int	    $max_year       Offset of maximum year into list (by default current year + 5)
     *  @param	int		$offset			Offset
     *  @param	int		$invert			Invert
     *  @param	string	$option			Option
     *  @param	string	$morecss		More css
     *  @return	string
     */
    public function selectyear($selected = '', $htmlname = 'yearid', $useempty = 0, $min_year = 10, $max_year = 5, $offset = 0, $invert = 0, $option = '', $morecss = 'valignmiddle maxwidth75imp')
    {
        $out = '';

        $currentyear = date("Y") + $offset;
        $max_year = $currentyear + $max_year;
        $min_year = $currentyear - $min_year;
        if (empty($selected) && empty($useempty)) $selected = $currentyear;

        $out .= '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.$htmlname.'" name="'.$htmlname.'"'.$option.' >';
        if ($useempty)
        {
        	$selected_html = '';
            if ($selected == '') $selected_html = ' selected';
            $out .= '<option value=""'.$selected_html.'>&nbsp;</option>';
        }
        if (!$invert)
        {
            for ($y = $max_year; $y >= $min_year; $y--)
            {
                $selected_html = '';
                if ($selected > 0 && $y == $selected) $selected_html = ' selected';
                $out .= '<option value="'.$y.'"'.$selected_html.' >'.$y.'</option>';
            }
        }
        else
        {
            for ($y = $min_year; $y <= $max_year; $y++)
            {
                $selected_html = '';
                if ($selected > 0 && $y == $selected) $selected_html = ' selected';
                $out .= '<option value="'.$y.'"'.$selected_html.' >'.$y.'</option>';
            }
        }
        $out .= "</select>\n";

        return $out;
    }


    /**
     * 	Get array with HTML tabs with boxes of a particular area including personalized choices of user.
     *  Class 'Form' must be known.
     *
     * 	@param	   User         $user		 Object User
     * 	@param	   String       $areacode    Code of area for pages - 0 = Home page ... See getListOfPagesForBoxes()
	 *	@return    array                     array('selectboxlist'=>, 'boxactivated'=>, 'boxlista'=>, 'boxlistb'=>)
     */
    public static function getBoxesArea($user, $areacode)
    {
        global $conf, $langs, $db;

        include_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

        $confuserzone = 'MAIN_BOXES_'.$areacode;

        // $boxactivated will be array of boxes enabled into global setup
        // $boxidactivatedforuser will be array of boxes choosed by user

        $selectboxlist = '';
        $boxactivated = InfoBox::listBoxes($db, 'activated', $areacode, (empty($user->conf->$confuserzone) ?null:$user), array(), 0); // Search boxes of common+user (or common only if user has no specific setup)

        $boxidactivatedforuser = array();
        foreach ($boxactivated as $box)
        {
        	if (empty($user->conf->$confuserzone) || $box->fk_user == $user->id) $boxidactivatedforuser[$box->id] = $box->id; // We keep only boxes to show for user
        }

        // Define selectboxlist
        $arrayboxtoactivatelabel = array();
        if (!empty($user->conf->$confuserzone))
        {
        	$boxorder = '';
        	$langs->load("boxes"); // Load label of boxes
        	foreach ($boxactivated as $box)
        	{
        		if (!empty($boxidactivatedforuser[$box->id])) continue; // Already visible for user
        		$label = $langs->transnoentitiesnoconv($box->boxlabel);
        		//if (preg_match('/graph/',$box->class)) $label.=' ('.$langs->trans("Graph").')';
        		if (preg_match('/graph/', $box->class) && $conf->browser->layout != 'phone')
        		{
        			$label = $label.' <span class="fa fa-bar-chart"></span>';
        		}
        		$arrayboxtoactivatelabel[$box->id] = $label; // We keep only boxes not shown for user, to show into combo list
        	}
            foreach ($boxidactivatedforuser as $boxid)
        	{
       			if (empty($boxorder)) $boxorder .= 'A:';
  				$boxorder .= $boxid.',';
        	}

        	//var_dump($boxidactivatedforuser);

        	// Class Form must have been already loaded
        	$selectboxlist .= '<!-- Form with select box list -->'."\n";
			$selectboxlist .= '<form id="addbox" name="addbox" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			$selectboxlist .= '<input type="hidden" name="addbox" value="addbox">';
			$selectboxlist .= '<input type="hidden" name="userid" value="'.$user->id.'">';
			$selectboxlist .= '<input type="hidden" name="areacode" value="'.$areacode.'">';
			$selectboxlist .= '<input type="hidden" name="boxorder" value="'.$boxorder.'">';
			$selectboxlist .= Form::selectarray('boxcombo', $arrayboxtoactivatelabel, -1, $langs->trans("ChooseBoxToAdd").'...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth150onsmartphone', 0, 'hidden selected', 0, 1);
            if (empty($conf->use_javascript_ajax)) $selectboxlist .= ' <input type="submit" class="button" value="'.$langs->trans("AddBox").'">';
            $selectboxlist .= '</form>';
            if (!empty($conf->use_javascript_ajax))
            {
            	include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
            	$selectboxlist .= ajax_combobox("boxcombo");
            }
        }

        // Javascript code for dynamic actions
        if (!empty($conf->use_javascript_ajax))
        {
	        $selectboxlist .= '<script type="text/javascript" language="javascript">

	        // To update list of activated boxes
	        function updateBoxOrder(closing) {
	        	var left_list = cleanSerialize(jQuery("#boxhalfleft").sortable("serialize"));
	        	var right_list = cleanSerialize(jQuery("#boxhalfright").sortable("serialize"));
	        	var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
	        	if (boxorder==\'A:A-B:B\' && closing == 1)	// There is no more boxes on screen, and we are after a delete of a box so we must hide title
	        	{
	        		jQuery.ajax({
	        			url: \''.DOL_URL_ROOT.'/core/ajax/box.php?closing=0&boxorder=\'+boxorder+\'&zone='.$areacode.'&userid=\'+'.$user->id.',
	        			async: false
	        		});
	        		// We force reload to be sure to get all boxes into list
	        		window.location.search=\'mainmenu='.GETPOST("mainmenu", "aZ09").'&leftmenu='.GETPOST('leftmenu', "aZ09").'&action=delbox\';
	        	}
	        	else
	        	{
	        		jQuery.ajax({
	        			url: \''.DOL_URL_ROOT.'/core/ajax/box.php?closing=\'+closing+\'&boxorder=\'+boxorder+\'&zone='.$areacode.'&userid=\'+'.$user->id.',
	        			async: true
	        		});
	        	}
	        }

	        jQuery(document).ready(function() {
	        	jQuery("#boxcombo").change(function() {
	        	var boxid=jQuery("#boxcombo").val();
	        		if (boxid > 0) {
	            		var left_list = cleanSerialize(jQuery("#boxhalfleft").sortable("serialize"));
	            		var right_list = cleanSerialize(jQuery("#boxhalfright").sortable("serialize"));
	            		var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
	    				jQuery.ajax({
	    					url: \''.DOL_URL_ROOT.'/core/ajax/box.php?boxorder=\'+boxorder+\'&boxid=\'+boxid+\'&zone='.$areacode.'&userid='.$user->id.'\',
	    			        async: false
	    		        });
	        			window.location.search=\'mainmenu='.GETPOST("mainmenu", "aZ09").'&leftmenu='.GETPOST('leftmenu', "aZ09").'&action=addbox&boxid=\'+boxid;
	                }
	        	});';
	        	if (!count($arrayboxtoactivatelabel)) $selectboxlist .= 'jQuery("#boxcombo").hide();';
	        	$selectboxlist .= '

	        	jQuery("#boxhalfleft, #boxhalfright").sortable({
	    	    	handle: \'.boxhandle\',
	    	    	revert: \'invalid\',
	       			items: \'.boxdraggable\',
					containment: \'document\',
	        		connectWith: \'#boxhalfleft, #boxhalfright\',
	        		stop: function(event, ui) {
	        			updateBoxOrder(1);  /* 1 to avoid message after a move */
	        		}
	    		});

	        	jQuery(".boxclose").click(function() {
	        		var self = this;	// because JQuery can modify this
	        		var boxid=self.id.substring(8);
	        		var label=jQuery(\'#boxlabelentry\'+boxid).val();
	        		console.log("We close box "+boxid);
	        		jQuery(\'#boxto_\'+boxid).remove();
	        		if (boxid > 0) jQuery(\'#boxcombo\').append(new Option(label, boxid));
	        		updateBoxOrder(1);  /* 1 to avoid message after a remove */
	        	});

        	});'."\n";

	        $selectboxlist .= '</script>'."\n";
        }

        // Define boxlista and boxlistb
        $nbboxactivated = count($boxidactivatedforuser);

        if ($nbboxactivated)
        {
        	// Load translation files required by the page
            $langs->loadLangs(array("boxes", "projects"));

        	$emptybox = new ModeleBoxes($db);

            $boxlista .= "\n<!-- Box left container -->\n";

            // Define $box_max_lines
            $box_max_lines = 5;
            if (!empty($conf->global->MAIN_BOXES_MAXLINES)) $box_max_lines = $conf->global->MAIN_BOXES_MAXLINES;

            $ii = 0;
            foreach ($boxactivated as $key => $box)
            {
            	if ((!empty($user->conf->$confuserzone) && $box->fk_user == 0) || (empty($user->conf->$confuserzone) && $box->fk_user != 0)) continue;
				if (empty($box->box_order) && $ii < ($nbboxactivated / 2)) $box->box_order = 'A'.sprintf("%02d", ($ii + 1)); // When box_order was not yet set to Axx or Bxx and is still 0
            	if (preg_match('/^A/i', $box->box_order)) // column A
                {
                    $ii++;
                    //print 'box_id '.$boxactivated[$ii]->box_id.' ';
                    //print 'box_order '.$boxactivated[$ii]->box_order.'<br>';
                    // Show box
                    $box->loadBox($box_max_lines);
                    $boxlista .= $box->outputBox();
                }
            }

            if ($conf->browser->layout != 'phone')
            {
            	$emptybox->box_id = 'A';
            	$emptybox->info_box_head = array();
            	$emptybox->info_box_contents = array();
            	$boxlista .= $emptybox->outputBox(array(), array());
            }
            $boxlista .= "<!-- End box left container -->\n";

            $boxlistb .= "\n<!-- Box right container -->\n";

            $ii = 0;
            foreach ($boxactivated as $key => $box)
            {
            	if ((!empty($user->conf->$confuserzone) && $box->fk_user == 0) || (empty($user->conf->$confuserzone) && $box->fk_user != 0)) continue;
            	if (empty($box->box_order) && $ii < ($nbboxactivated / 2)) $box->box_order = 'B'.sprintf("%02d", ($ii + 1)); // When box_order was not yet set to Axx or Bxx and is still 0
            	if (preg_match('/^B/i', $box->box_order)) // colonne B
                {
                    $ii++;
                    //print 'box_id '.$boxactivated[$ii]->box_id.' ';
                    //print 'box_order '.$boxactivated[$ii]->box_order.'<br>';
                    // Show box
                    $box->loadBox($box_max_lines);
                    $boxlistb .= $box->outputBox();
                }
            }

            if ($conf->browser->layout != 'phone')
            {
            	$emptybox->box_id = 'B';
            	$emptybox->info_box_head = array();
            	$emptybox->info_box_contents = array();
            	$boxlistb .= $emptybox->outputBox(array(), array());
            }

            $boxlistb .= "<!-- End box right container -->\n";
        }

        return array('selectboxlist'=>count($boxactivated) ? $selectboxlist : '', 'boxactivated'=>$boxactivated, 'boxlista'=>$boxlista, 'boxlistb'=>$boxlistb);
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return a HTML select list of a dictionary
     *
     *  @param  string	$htmlname          	Name of select zone
     *  @param	string	$dictionarytable	Dictionary table
     *  @param	string	$keyfield			Field for key
     *  @param	string	$labelfield			Label field
     *  @param	string	$selected			Selected value
     *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *  @param  string  $moreattrib         More attributes on HTML select tag
     * 	@return	void
     */
    public function select_dictionary($htmlname, $dictionarytable, $keyfield = 'code', $labelfield = 'label', $selected = '', $useempty = 0, $moreattrib = '')
    {
        // phpcs:enable
        global $langs, $conf;

        $langs->load("admin");

        $sql = "SELECT rowid, ".$keyfield.", ".$labelfield;
        $sql .= " FROM ".MAIN_DB_PREFIX.$dictionarytable;
        $sql .= " ORDER BY ".$labelfield;

        dol_syslog(get_class($this)."::select_dictionary", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            if ($num)
            {
                print '<select id="select'.$htmlname.'" class="flat selectdictionary" name="'.$htmlname.'"'.($moreattrib ? ' '.$moreattrib : '').'>';
                if ($useempty == 1 || ($useempty == 2 && $num > 1))
                {
                    print '<option value="-1">&nbsp;</option>';
                }

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    if ($selected == $obj->rowid || $selected == $obj->$keyfield)
                    {
                        print '<option value="'.$obj->$keyfield.'" selected>';
                    }
                    else
                    {
                        print '<option value="'.$obj->$keyfield.'">';
                    }
                    print $obj->$labelfield;
                    print '</option>';
                    $i++;
                }
                print "</select>";
            } else {
                print $langs->trans("DictionaryEmpty");
            }
        }
        else {
            dol_print_error($this->db);
        }
    }

    /**
	 *	Return an html string with a select combo box to choose yes or no
	 *
	 *	@param	string		$htmlname		Name of html select field
	 *	@param	string		$value			Pre-selected value
	 *	@param	int			$option			0 return automatic/manual, 1 return 1/0
	 *	@param	bool		$disabled		true or false
	 *  @param	int      	$useempty		1=Add empty line
	 *	@return	string						See option
	 */
    public function selectAutoManual($htmlname, $value = '', $option = 0, $disabled = false, $useempty = 0)
	{
		global $langs;

		$automatic = "automatic"; $manual = "manual";
		if ($option)
		{
			$automatic = "1";
			$manual = "0";
		}

		$disabled = ($disabled ? ' disabled' : '');

		$resultautomanual = '<select class="flat width100" id="'.$htmlname.'" name="'.$htmlname.'"'.$disabled.'>'."\n";
		if ($useempty) $resultautomanual .= '<option value="-1"'.(($value < 0) ? ' selected' : '').'>&nbsp;</option>'."\n";
		if (("$value" == 'automatic') || ($value == 1))
		{
			$resultautomanual .= '<option value="'.$automatic.'" selected>'.$langs->trans("Automatic").'</option>'."\n";
			$resultautomanual .= '<option value="'.$manual.'">'.$langs->trans("Manual").'</option>'."\n";
		}
		else
	    {
	   		$selected = (($useempty && $value != '0' && $value != 'manual') ? '' : ' selected');
			$resultautomanual .= '<option value="'.$automatic.'">'.$langs->trans("Automatic").'</option>'."\n";
			$resultautomanual .= '<option value="'.$manual.'"'.$selected.'>'.$langs->trans("Manual").'</option>'."\n";
		}
		$resultautomanual .= '</select>'."\n";
		return $resultautomanual;
	}


	/**
	 * Return HTML select list to select a group by field
	 *
	 * @param 	mixed	$object				Object analyzed
	 * @param	array	$search_groupby		Array of preselected fields
	 * @param	array	$arrayofgroupby		Array of groupby to fill
	 * @return string						HTML string component
	 */
	public function selectGroupByField($object, $search_groupby, &$arrayofgroupby)
	{
		global $langs, $extrafields, $form;

		$YYYY = substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1);
		$MM = substr($langs->trans("Month"), 0, 1).substr($langs->trans("Month"), 0, 1);
		$DD = substr($langs->trans("Day"), 0, 1).substr($langs->trans("Day"), 0, 1);
		$HH = substr($langs->trans("Hour"), 0, 1).substr($langs->trans("Hour"), 0, 1);
		$MI = substr($langs->trans("Minute"), 0, 1).substr($langs->trans("Minute"), 0, 1);
		$SS = substr($langs->trans("Second"), 0, 1).substr($langs->trans("Second"), 0, 1);

		foreach ($object->fields as $key => $val) {
			if (!$val['measure']) {
				if (in_array($key, array(
					'id', 'ref_int', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
					'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) continue;
				if (isset($val['enabled']) && !dol_eval($val['enabled'], 1)) continue;
				if (isset($val['visible']) && !dol_eval($val['visible'], 1)) continue;
				if (preg_match('/^fk_/', $key) && !preg_match('/^fk_statu/', $key)) continue;
				if (preg_match('/^pass/', $key)) continue;
				if (in_array($val['type'], array('html', 'text'))) continue;
				if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
					$arrayofgroupby['t.'.$key.'-year'] = array('label' => $langs->trans($val['label']).' ('.$YYYY.')', 'position' => $val['position'].'-y');
					$arrayofgroupby['t.'.$key.'-month'] = array('label' => $langs->trans($val['label']).' ('.$YYYY.'-'.$MM.')', 'position' => $val['position'].'-m');
					$arrayofgroupby['t.'.$key.'-day'] = array('label' => $langs->trans($val['label']).' ('.$YYYY.'-'.$MM.'-'.$DD.')', 'position' => $val['position'].'-d');
				} else {
					$arrayofgroupby['t.'.$key] = array('label' => $langs->trans($val['label']), 'position' => (int) $val['position']);
				}
			}
		}
		// Add extrafields to Group by
		if ($object->isextrafieldmanaged) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
				if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') continue;
				if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) continue;
				$arrayofgroupby['te.'.$key] = array('label' => $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]), 'position' => 1000 + (int) $extrafields->attributes[$object->table_element]['pos'][$key]);
			}
		}

		$arrayofgroupby = dol_sort_array($arrayofgroupby, 'position', 'asc', 0, 0, 1);
		$arrayofgroupbylabel = array();
		foreach ($arrayofgroupby as $key => $val) {
			$arrayofgroupbylabel[$key] = $val['label'];
		}
		$result = $form->selectarray('search_groupby', $arrayofgroupbylabel, $search_groupby, 1, 0, 0, '', 0, 0, 0, '', 'minwidth250', 1);

		return $result;
	}

	/**
	 * Return HTML select list to select a group by field
	 *
	 * @param 	mixed	$object				Object analyzed
	 * @param	array	$search_xaxis		Array of preselected fields
	 * @param	array	$arrayofxaxis		Array of groupby to fill
	 * @return string						HTML string component
	 */
	public function selectXAxisField($object, $search_xaxis, &$arrayofxaxis)
	{
		global $langs, $extrafields, $form;

		$YYYY = substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1);
		$MM = substr($langs->trans("Month"), 0, 1).substr($langs->trans("Month"), 0, 1);
		$DD = substr($langs->trans("Day"), 0, 1).substr($langs->trans("Day"), 0, 1);
		$HH = substr($langs->trans("Hour"), 0, 1).substr($langs->trans("Hour"), 0, 1);
		$MI = substr($langs->trans("Minute"), 0, 1).substr($langs->trans("Minute"), 0, 1);
		$SS = substr($langs->trans("Second"), 0, 1).substr($langs->trans("Second"), 0, 1);


		foreach ($object->fields as $key => $val) {
			if (!$val['measure']) {
				if (in_array($key, array(
					'id', 'ref_int', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
					'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) continue;
				if (isset($val['enabled']) && !dol_eval($val['enabled'], 1)) continue;
				if (isset($val['visible']) && !dol_eval($val['visible'], 1)) continue;
				if (preg_match('/^fk_/', $key) && !preg_match('/^fk_statu/', $key)) continue;
				if (preg_match('/^pass/', $key)) continue;
				if (in_array($val['type'], array('html', 'text'))) continue;
				if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
					$arrayofxaxis['t.'.$key.'-year'] = array('label' => $langs->trans($val['label']).' ('.$YYYY.')', 'position' => $val['position'].'-y');
					$arrayofxaxis['t.'.$key.'-month'] = array('label' => $langs->trans($val['label']).' ('.$YYYY.'-'.$MM.')', 'position' => $val['position'].'-m');
					$arrayofxaxis['t.'.$key.'-day'] = array('label' => $langs->trans($val['label']).' ('.$YYYY.'-'.$MM.'-'.$DD.')', 'position' => $val['position'].'-d');
				} else {
					$arrayofxaxis['t.'.$key] = array('label' => $langs->trans($val['label']), 'position' => (int) $val['position']);
				}
			}
		}

		// Add extrafields to X-Axis
		if ($object->isextrafieldmanaged) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
				if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') continue;
				if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) continue;
				$arrayofxaxis['te.'.$key] = array('label' => $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]), 'position' => 1000 + (int) $extrafields->attributes[$object->table_element]['pos'][$key]);
			}
		}

		$arrayofxaxis = dol_sort_array($arrayofxaxis, 'position', 'asc', 0, 0, 1);

		$arrayofxaxislabel = array();
		foreach ($arrayofxaxis as $key => $val) {
			$arrayofxaxislabel[$key] = $val['label'];
		}
		$result = $form->selectarray('search_xaxis', $arrayofxaxislabel, $search_xaxis, 1, 0, 0, '', 0, 0, 0, '', 'minwidth250', 1);

		return $result;
	}
}
