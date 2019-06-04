<?php
/* Copyright (C) 2010-2018	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011 		Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *   \file			htdocs/core/class/canvas.class.php
 *   \ingroup		core
 *   \brief			File of class to manage canvas
 */


/**
 *	Class to manage canvas
 */
class Canvas
{
	/**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	public $actiontype;

    public $dirmodule;			// Module directory
    public $targetmodule;      // Module concerned by canvas (ex: thirdparty, contact, ...)
    public $canvas;            // Name of canvas (ex: company, individual, product, service, ...)
    public $card;              // Tab (sub-canvas)

    public $template_dir;		// Initialized by getCanvas with templates directory
    public $control;           	// Initialized by getCanvas with controller instance


   /**
	*   Constructor
	*
	*   @param     DoliDB	$db          	Database handler
	*   @param     string   $actiontype		Action type ('create', 'view', 'edit', 'list')
	*/
	public function __construct($db, $actiontype = 'view')
	{
		$this->db = $db;

		$this->actiontype = $this->_cleanaction($actiontype);
	}

	/**
	 * Return action code cleaned
	 *
	 * @param	string	$action		Action type ('create', 'view', 'edit', 'list', 'add', 'update')
	 * @return 	string				Cleaned action type ('create', 'view', 'edit', 'list')
	 */
	private function _cleanaction($action)
	{
	    $newaction = $action;
	    if ($newaction == 'add')    $newaction='create';
	    if ($newaction == 'update') $newaction='edit';
	    if (empty($newaction) || $newaction == 'delete' || $newaction == 'create_user' || $newaction == 'presend' || $newaction == 'send') $newaction='view';
	    return $newaction;
	}


	/**
	 * 	Initialize properties: ->targetmodule, ->canvas, ->card, ->dirmodule, ->template_dir
	 *
	 * 	@param	string	$module		Name of target module (thirdparty, contact, ...)
	 * 	@param	string	$card	 	Tab name of card (ex: 'card', 'info', 'contactcard', ...) or '' for a list page
	 * 	@param	string	$canvas		Name of canvas (ex: mycanvas, default, or mycanvas@myexternalmodule)
	 * 	@return	void
	 */
	public function getCanvas($module, $card, $canvas)
	{
		global $conf, $langs;

		// Set properties with value specific to dolibarr core: this->targetmodule, this->card, this->canvas
        $this->targetmodule = $module;
        $this->canvas = $canvas;
        $this->card = $card;
        $this->dirmodule = $module;
        // Correct values if canvas is into an external module
		if (preg_match('/^([^@]+)@([^@]+)$/i', $canvas, $regs))
		{
            $this->canvas = $regs[1];
		    $this->dirmodule = $regs[2];
		}
		// For compatibility
        if ($this->dirmodule == 'thirdparty') { $this->dirmodule = 'societe'; }

        // Control file
		$controlclassfile = dol_buildpath('/'.$this->dirmodule.'/canvas/'.$this->canvas.'/actions_'.$this->card.'_'.$this->canvas.'.class.php');
		if (file_exists($controlclassfile))
		{
            // Include actions class (controller)
            $this->control_file=$controlclassfile;
            require_once $controlclassfile;

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
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
	 * 	Shared method for canvas to assign values for templates
	 *
	 * 	@param		string		$action	Action string
	 * 	@param		int			$id			Object id (if ref not provided)
	 * 	@param		string		$ref		Object ref (if id not provided)
	 * 	@return		void
	 */
	public function assign_values(&$action = 'view', $id = 0, $ref = '')
	{
        // phpcs:enable
		if (method_exists($this->control, 'assign_values')) $this->control->assign_values($action, $id, $ref);
	}

    /**
     *	Return the template to display canvas (if it exists)
	 *
	 *	@param	string	$action		Action code
     *	@return	int		0=Canvas template file does not exist, 1=Canvas template file exists
     */
    public function displayCanvasExists($action)
    {
        if (empty($this->template_dir)) return 0;

        if (file_exists($this->template_dir.(!empty($this->card)?$this->card.'_':'').$this->_cleanaction($action).'.tpl.php')) return 1;
        else return 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Display a canvas page. This will include the template for output.
	 *	Variables used by templates may have been defined or loaded before into the assign_values function.
	 *
	 *	@param	string	$action		Action code
	 *	@return	void
	 */
	public function display_canvas($action)
	{
        // phpcs:enable
		global $db, $conf, $langs, $user, $canvas;
		global $form, $formfile;

		include $this->template_dir.(!empty($this->card)?$this->card.'_':'').$this->_cleanaction($action).'.tpl.php';        // Include native PHP template
	}


	// This functions should not be used anymore because canvas should contains only templates.
	// http://wiki.dolibarr.org/index.php/Canvas_development

	/**
	 * 	Return if a canvas contains an action controller
	 *
	 * 	@return		boolean		Return if canvas contains actions (old feature. now actions should be inside hooks)
	 */
	public function hasActions()
	{
        return (is_object($this->control));
	}

	/**
	 * 	Shared method for canvas to execute actions.
     *  @deprecated Use the doActions of hooks instead of this.
	 * 	            This function is called if you add a doActions class inside your canvas. Try to not
	 * 				do that and add action code into a hook instead.
	 *
	 * 	@param		string		$action	Action string
	 * 	@param		int			$id			Object id
	 * 	@return		mixed					Return return code of doActions of canvas
	 * 	@see		http://wiki.dolibarr.org/index.php/Canvas_development
	 */
	public function doActions(&$action = 'view', $id = 0)
	{
		if (method_exists($this->control, 'doActions'))
		{
			$ret = $this->control->doActions($action, $id);
			return $ret;
		}
	}
}
