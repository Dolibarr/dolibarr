<?php
/* Copyright (C) 2010 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *   \file			htdocs/core/class/canvas.class.php
 *   \ingroup		core
 *   \brief			File of class to manage canvas
 *   \version		$Id$
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

	var $card;
	var $canvas;
	var $object;
	var $control;
	var $module;
	var $targetmodule;      // Module built into dolibarr replaced by canvas (ex: thirdparty, contact, ...)
	var $aliasmodule;		// Module that provide the canvas
	var $template_dir;		// Directory with all core and external templates files
	var $action;


   /**
	*   Constructor.
	*   @param     DB          Database handler
	*   @param     action      Action ('create', 'view', 'edit')
	*/
	function Canvas($DB,$action='view')
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
	 * 	Initialize properties like ->control, ->control->object, ->template_dir
	 * 	@param		module		Name of target module (thirdparty, ...)
	 * 	@param		card	 	Type of card (ex: card, info, ...)
	 * 	@param		canvas		Name of canvas (ex: mycanvas@mymodule)
	 */
	function getCanvas($module,$card,$canvas)
	{
		global $conf, $langs;

		$error='';
		$this->card = $card;

		// Define this->canvas, this->targetmodule, this->aliasmodule, targetmodule, childmodule
        $this->canvas = $canvas;
		$this->targetmodule = $this->aliasmodule = $module;
		if (preg_match('/^([^@]+)@([^@]+)$/i',$canvas,$regs))
		{
            $this->canvas = $regs[1];
		    $this->aliasmodule = $regs[2];
		}
        $targetmodule = $this->targetmodule;
        $childmodule = $this->aliasmodule;
		// For compatibility
        if ($targetmodule == 'thirdparty') { $targetmodule = 'societe'; }
        if ($childmodule == 'thirdparty')  { $childmodule = 'societe'; $this->aliasmodule = 'societe'; }
        if ($targetmodule == 'contact')    { $targetmodule = 'societe'; }
        if ($childmodule == 'contact')     { $childmodule = 'societe'; }

		/*print 'canvas='.$this->canvas.'<br>';
		print 'childmodule='.$childmodule.' targetmodule='.$targetmodule.'<br>';
		print 'this->aliasmodule='.$this->aliasmodule.' this->targetmodule='.$this->targetmodule.'<br>';
		print 'childmodule='.$conf->$childmodule->enabled.' targetmodule='.$conf->$targetmodule->enabled.'<br>';
        */

		if (! $conf->$childmodule->enabled || ! $conf->$targetmodule->enabled) accessforbidden();

		$controlclassfile = dol_buildpath('/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/actions_'.$this->card.'_'.$this->canvas.'.class.php');
		if (file_exists($controlclassfile))
		{
            // Include actions class (controller)
            require_once($controlclassfile);

            // Instantiate actions class (controller)
            $controlclassname = 'Actions'.ucfirst($this->card).ucfirst($this->canvas);
            $this->control = new $controlclassname($this->db);
		}

		// TODO Dao should be declared and used by controller or templates when required only
        $modelclassfile = dol_buildpath('/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/dao_'.$this->targetmodule.'_'.$this->canvas.'.class.php');
        if (file_exists($modelclassfile))
        {
            // Include dataservice class (model)
            require_once($modelclassfile);

            // Instantiate dataservice class (model)
            $modelclassname = 'Dao'.ucfirst($this->targetmodule).ucfirst($this->canvas);
            $this->control->object = new $modelclassname($this->db);
        }

		// Include specific library
		// FIXME Specific libraries must be included by files that need them only, so by actions and/or dao files.
		$libfile = dol_buildpath('/'.$this->aliasmodule.'/lib/'.$this->aliasmodule.'.lib.php');
		if (file_exists($libfile)) require_once($libfile);

		// Template dir
		$this->template_dir = dol_buildpath('/'.$this->aliasmodule.'/canvas/'.$this->canvas.'/tpl/');
        if (! is_dir($this->template_dir))
        {
            $this->template_dir='';
        }

		return 1;
	}

    /**
     *  Execute actions
     *  @param      Id of object (may be empty for creation)
     */
    function doActions($id)
    {
        $out='';

        // If function to do actions is overwritten, we use new one
        if (method_exists($this->control,'doActions'))
        {
            $out = $this->control->doActions($id);

            $this->errors = ($this->control->errors?$this->control->errors:$this->control->object->errors);
            $this->error = ($this->control->error?$this->control->error:$this->control->object->error);
        }

        return $out;
    }

    /**
     *  Fetch object values
     *  @param      id          Element id
     */
    function fetch($id)
    {
        return $this->control->object->fetch($id);
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
     *  Return the title of card
     *  // FIXME An output function is stored inside an action class. Must change this.
     */
    function getTitle()
    {
        if (method_exists($this->control,'getTitle')) return $this->control->getTitle($this->action);
        else return '';
    }

    /**
     *  Return the head of card (tabs)
     *  // FIXME An output function is stored inside an action class. Must change this.
     */
    function showHead()
    {
        if (method_exists($this->control,'showHead')) return $this->control->showHead($this->action);
        else return '';
    }

    /**
     *    Assigne les valeurs POST dans l'objet
     *    // FIXME This is useless. POST is already visible from everywhere.
     */
    function assign_post()
    {
        if (method_exists($this->control,'assign_post')) $this->control->assign_post();
    }

    /**
	 * 	Shared method for canvas to assign values of templates
	 * 	@param	action	Type of action
	 */
	function assign_values()
	{
		$this->control->assign_values($this->action);
	}

	/**
	 * 	   Display canvas
	 *     @param      mode        'create', 'view', 'edit'
	 */
	function display_canvas($mode='view')
	{
		global $conf, $langs, $user, $canvas;

		//print $this->template_dir.$this->card.'_'.$mode.'.tpl.php';exit;
		include($this->template_dir.$this->card.'_'.$mode.'.tpl.php');        // Include native PHP template
	}

}
?>
