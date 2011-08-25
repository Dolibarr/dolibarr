<?php
/* Copyright (C) 2010-2011	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2011 		Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *   \file			htdocs/core/class/canvas.class.php
 *   \ingroup		core
 *   \brief			File of class to manage canvas
 *   \version		$Id: canvas.class.php,v 1.45 2011/07/31 23:45:13 eldy Exp $
 */


/**
 *    \class      Canvas
 *    \brief      Class to manage canvas
 */
class Canvas
{
	var $db;
	var $error;
	var $errors=array();

    var $action;

    var $targetmodule;      // Module concerned by canvas (ex: thirdparty, contact, ...)
    var $canvas;            // Name of canvas
    var $card;              // Tab (sub-canvas)

    var $object;            // Initialized by getCanvas with dao class
    var $control;           // Initialized by getCanvas with controller class
    var $template_dir;		// Initialized by getCanvas with templates directory


   /**
	*   Constructor.
	*   @param     DB          Database handler
	*   @param     action      Action ('create', 'view', 'edit')
	*/
	function Canvas($DB, $action='view')
	{
		$this->db = $DB;
		$this->action = $action;
        if ($this->action == 'add')    $this->action='create';
		if ($this->action == 'update') $this->action='edit';
        if (empty($this->action))      $this->action='view';
	}

    /**
     *    Set action type
     *    @deprecated       Kept for backward compatibility
     */
    function setAction($action='view')
    {
        return $this->action = $action;
    }



	/**
	 * 	Initialize properties: ->targetmodule, ->canvas, ->card
	 *  and MVC properties:    ->control (Controller), ->control->object (Model), ->template_dir (View)
	 * 	@param		module		Name of target module (thirdparty, contact, ...)
	 * 	@param		card	 	Type of card (ex: card, info, contactcard, ...)
	 * 	@param		canvas		Name of canvas (ex: mycanvas, default, or mycanvas@myexternalmodule)
	 */
	function getCanvas($module, $card, $canvas)
	{
		global $conf, $langs;

		$error='';

		// Set properties with value specific to dolibarr core: this->targetmodule, this->card, this->canvas
        $this->targetmodule = $module;
        $this->canvas = $canvas;
        $this->card = $card;
        $dirmodule = $module;
        // Correct values if canvas is into an external module
		if (preg_match('/^([^@]+)@([^@]+)$/i',$canvas,$regs))
		{
            $this->canvas = $regs[1];
		    $dirmodule = $regs[2];
		}
		// For compatibility
        if ($dirmodule == 'thirdparty') { $dirmodule = 'societe'; }

		$controlclassfile = dol_buildpath('/'.$dirmodule.'/canvas/'.$this->canvas.'/actions_'.$this->card.'_'.$this->canvas.'.class.php');
		if (file_exists($controlclassfile))
		{
            // Include actions class (controller)
            require_once($controlclassfile);

            // Instantiate actions class (controller)
            $controlclassname = 'Actions'.ucfirst($this->card).ucfirst($this->canvas);
            $this->control = new $controlclassname($this->db,$this->targetmodule,$this->canvas,$this->card);
		}

		// TODO Dao should be declared and used by controller or templates when required only
        $modelclassfile = dol_buildpath('/'.$dirmodule.'/canvas/'.$this->canvas.'/dao_'.$this->targetmodule.'_'.$this->canvas.'.class.php');
        if (file_exists($modelclassfile))
        {
            // Include dataservice class (model)
            require_once($modelclassfile);

            // Instantiate dataservice class (model)
            $modelclassname = 'Dao'.ucfirst($this->targetmodule).ucfirst($this->canvas);
            $this->control->object = new $modelclassname($this->db);
        }

		// Template dir
		$this->template_dir = dol_buildpath('/'.$dirmodule.'/canvas/'.$this->canvas.'/tpl/');
        if (! is_dir($this->template_dir))
        {
            $this->template_dir='';
        }

        //print 'dimodule='.$dirmodule.' canvas='.$this->canvas.' template_dir='.$this->template_dir.'<br>';

		return 1;
	}

    /**
     *  Execute actions
     *  @param          id      Id of object (may be empty for creation)
     *  @deprecated     Use actions with hooks instead
     */
    function doActions($id)
    {
        $out='';

        // If function to do actions is overwritten, we use new one
        if (method_exists($this->control,'doActions'))
        {
            $out = $this->control->doActions($id,$this->targetmodule,$this->canvas,$this->card);

            $this->errors = ($this->control->errors?$this->control->errors:$this->control->object->errors);
            $this->error = ($this->control->error?$this->control->error:$this->control->object->error);
        }

        return $out;
    }

    /**
     *  get object
     *  @param      param1          Param1
     *  @param      param2          Param2
     *  @param      param3          Param3
     *  @return     object          Object loaded
     */
    function getObject($param1, $param2='', $param3='')
    {
        if (is_object($this->control->object) && method_exists($this->control->object,'fetch'))
        {
            $this->control->object->fetch($param1, $param2, $param3);
            return $this->control->object;
        }
        else
        {
            return 0;
        }
    }

	/**
	 * 	Check permissions of a user to show a page and an object. Check read permission.
	 * 	If $_REQUEST['action'] defined, we also check write permission.
	 * 	@param      user      	  	User to check
	 * 	@param      features	    Features to check (in most cases, it's module name)
	 * 	@param      objectid      	Object ID if we want to check permission on a particular record (optionnal)
	 *  @param      dbtablename    	Table name where object is stored. Not used if objectid is null (optionnal)
	 *  @param      feature2		Feature to check (second level of permission)
	 *  @param      dbt_keyfield    Field name for socid foreign key if not fk_soc. (optionnal)
	 *  @param      dbt_select      Field name for select if not rowid. (optionnal)
	 *  @return		int				1
	 */
	function restrictedArea($user, $features='societe', $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid')
	{
	    // If function to check permission is overwritten, we use new one
	    if (method_exists($this->control,'restrictedArea')) return $this->control->restrictedArea($user,$features,$objectid,$dbtablename,$feature2,$dbt_keyfield,$dbt_select);
	    else return restrictedArea($user,$features,$objectid,$dbtablename,$feature2,$dbt_keyfield,$dbt_select);
	}


    /**
     *    Assign values into POST into object
     *    // TODO This should be useless. POST is already visible from everywhere.
     */
    function assign_post()
    {
        if (empty($_POST)) return;
        if (method_exists($this->control,'assign_post')) $this->control->assign_post();
    }

    /**
	 * 	Shared method for canvas to assign values for templates
	 */
	function assign_values()
	{
	    if (method_exists($this->control,'assign_values')) $this->control->assign_values($this->action);
	}

    /**
     *     Return the template to display canvas (if it exists).
     *     @param       mode        'create', ''='view', 'edit'
     *     @return      string      Path to display canvas file if it exists, '' otherwise.
     */
    function displayCanvasExists($mode='view')
    {
        $newmode=$mode;
        if (empty($newmode)) $newmode='view';
        if (empty($this->template_dir)) return 0;
        //print $this->template_dir.$this->card.'_'.$newmode.'.tpl.php';
        if (file_exists($this->template_dir.$this->card.'_'.$newmode.'.tpl.php')) return 1;
        else return 0;
    }

	/**
	 * 	   Display canvas
	 *     @param      mode        'create', 'view', 'edit'
	 *     @param      id          Id of object to show
	 */
	function display_canvas($mode='view',$id=0)
	{
		global $db, $conf, $langs, $user, $canvas;
		global $id, $form, $formfile;

		//print $this->template_dir.$this->card.'_'.$mode.'.tpl.php';exit;
		include($this->template_dir.$this->card.'_'.$mode.'.tpl.php');        // Include native PHP template
	}

}
?>
