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
	
	var $actiontype;

    var $dirmodule;			// Module directory
    var $targetmodule;      // Module concerned by canvas (ex: thirdparty, contact, ...)
    var $canvas;            // Name of canvas
    var $card;              // Tab (sub-canvas)

    var $template_dir;		// Initialized by getCanvas with templates directory
    var $control_file;		// Initialized by getCanvas with controller file name
    var $control;           // Initialized by getCanvas with controller instance
    var $object;            // Initialized by getCanvas with dao instance, filled by getObject


   /**
	*   Constructor
	*
	*   @param     DoliDB	$DB          Database handler
	*/
	function __construct($DB, $actiontype='view')
	{
		$this->db = $DB;
		
		$this->actiontype = $actiontype;
        if ($this->actiontype == 'add')    $this->actiontype='create';
		if ($this->actiontype == 'update') $this->actiontype='edit';
		if (empty($this->actiontype) || $this->actiontype == 'delete' || $this->actiontype == 'create_user') $this->actiontype='view';
	}

	/**
	 * 	Initialize properties: ->targetmodule, ->canvas, ->card
	 *  and MVC properties:    ->control (Controller), ->control->object (Model), ->template_dir (View)
	 *
	 * 	@param		module		Name of target module (thirdparty, contact, ...)
	 * 	@param		card	 	Type of card (ex: 'card', 'info', 'contactcard', ...) or '' for a list page
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
        $this->dirmodule = $module;
        // Correct values if canvas is into an external module
		if (preg_match('/^([^@]+)@([^@]+)$/i',$canvas,$regs))
		{
            $this->canvas = $regs[1];
		    $this->dirmodule = $regs[2];
		}
		// For compatibility
        if ($this->dirmodule == 'thirdparty') { $this->dirmodule = 'societe'; }

		$controlclassfile = dol_buildpath('/'.$this->dirmodule.'/canvas/'.$this->canvas.'/actions_'.$this->card.'_'.$this->canvas.'.class.php');
		if (file_exists($controlclassfile))
		{
            // Include actions class (controller)
            $this->control_file=$controlclassfile;
            require_once($controlclassfile);

            // Instantiate actions class (controller)
            $controlclassname = 'Actions'.ucfirst($this->card).ucfirst($this->canvas);
            $this->control = new $controlclassname($this->db, $this->dirmodule, $this->targetmodule, $this->canvas, $this->card);
		}

		// Template dir
		$this->template_dir = dol_buildpath('/'.$this->dirmodule.'/canvas/'.$this->canvas.'/tpl/');
        if (! is_dir($this->template_dir))
        {
            $this->template_dir='';
        }

        //print 'dimodule='.$dirmodule.' canvas='.$this->canvas.'<br>';
        //print ' => template_dir='.$this->template_dir.'<br>';
        //print ' => control_file='.$this->control_file.' is_object(this->control)='.is_object($this->control).'<br>';

		return 1;
	}
	
	/**
	 * 	Shared method for canvas to execute actions
	 * 
	 * 	@param		string		$action		Action string
	 * 	@param		int			$id			Object id
	 * 	@return		void
	 */
	function doActions(&$action='view', $id=0)
	{
		if (method_exists($this->control,'doActions')) 
		{
			$ret = $this->control->doActions($action, $id);
			return $ret;
		}
	}

    /**
	 * 	Shared method for canvas to assign values for templates
	 * 
	 * 	@param		string		$action		Action string
	 * 	@param		int			$id			Object id
	 * 	@return		void
	 */
	function assign_values(&$action='view', $id=0)
	{
		if (method_exists($this->control,'assign_values')) $this->control->assign_values($action, $id);
	}

    /**
     *	Return the template to display canvas (if it exists)
	 *
     *	@return		string				Path to display canvas file if it exists, '' otherwise.
     */
    function displayCanvasExists()
    {
        if (empty($this->template_dir)) return 0;
        //print $this->template_dir.($this->card?$this->card.'_':'').$this->actiontype.'.tpl.php';
        if (file_exists($this->template_dir.($this->card?$this->card.'_':'').$this->actiontype.'.tpl.php')) return 1;
        else return 0;
    }

	/**
	 *	Display a canvas page. This will include the template for output.
	 *	Variables used by templates may have been defined, loaded before
	 *	into the assign_values function.
	 *
	 *	@return		void
	 */
	function display_canvas()
	{
		global $db, $conf, $langs, $user, $canvas;
		global $form, $formfile;

		include($this->template_dir.($this->card?$this->card.'_':'').$this->actiontype.'.tpl.php');        // Include native PHP template
	}

}
?>
