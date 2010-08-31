<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *   \file		htdocs/core/class/canvas.class.php
 *   \ingroup		core
 *   \brief			Fichier de la classe de gestion des canvas
 *   \version		$Id$
 */


/**
 *    \class      Canvas
 *    \brief      Classe de la gestion des canvas
 */

class Canvas
{
	var $canvas;
	var $template_dir;		// Directory with all core and external templates files
	var $action;
	var $smarty;

	var $error;

   /**
	*   Constructor.
	*   @param     DB      Database handler
	*   @param     user    User
	*/
	function Canvas($DB,$user)
	{
		$this->db = $DB;
		$this->user = $user;
	}

	/**
	 * 	Load canvas
	 * 	@param		$element 	Element of canvas
	 * 	@param		$canvas		Name of canvas
	 */
	function load_canvas($element,$canvas)
	{
		global $langs;

		$part1=$part3=$element;
		$part2=$canvas;
		
		// For compatibility
		if (preg_match('/^([^@]+)@([^@]+)$/i',$element,$regs))
		{
			$part1=$regs[2];
			$part3=$regs[1];
		}

		if (preg_match('/^([^@]+)@([^@]+)$/i',$canvas,$regs))
		{
			$part1=$part3=$regs[2];
			$part2=$regs[1];
		}

		if (file_exists(DOL_DOCUMENT_ROOT.'/'.$part1.'/canvas/'.$part2.'/'.$part3.'.'.$part2.'.class.php'))
		{
			$filecanvas = DOL_DOCUMENT_ROOT.'/'.$part1.'/canvas/'.$part2.'/'.$part3.'.'.$part2.'.class.php';
			$classname = ucfirst($part3).ucfirst($part2);
			$this->template_dir = DOL_DOCUMENT_ROOT.'/'.$part1.'/canvas/'.$part2.'/tpl/';

			include_once($filecanvas);
			$this->object = new $classname($this->db,0,$this->user);
			$this->smarty = $this->object->smarty;

			return $this->object;
		}
		else
		{
			$this->error = $langs->trans('CanvasIsInvalid');
			return 0;
		}
	}

	/**
	 * 	\brief 		Fetch object values
	 */
	function fetch($id,$ref='',$action='')
	{
		$ret = $this->object->fetch($id,$ref,$action);
		return $ret;
	}

	/**
	 * 	\brief 		Assign templates values
	 */
	function assign_values($action='')
	{
		$this->action = $action;

		if (!empty($this->smarty))
		{
			global $smarty;

			$this->object->assign_smarty_values($smarty, $this->action);
			$smarty->template_dir = $this->template_dir;
		}
		else
		{
			$this->object->assign_values($this->action);
		}

	}

	/**
	 * 	\brief 		Display canvas
	 */
	function display_canvas()
	{
		global $conf, $langs, $user;

		if (!empty($this->smarty))
		{
			global $smarty;

			$smarty->display($this->action.'.tpl');
		}
		else
		{
			include($this->template_dir.$this->action.'.tpl.php');
		}
	}

}
?>
