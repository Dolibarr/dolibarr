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
 *	\file       htdocs/milestone/class/milestone.class.php
 *	\ingroup    milestone
 *	\brief      Fichier de la classe des jalons
 *	\version	$Id$
 */


/**
 *	\class      Milestone
 *	\brief      Classe permettant la gestion des jalons
 */
class Milestone extends CommonObject
{
	var $db;
	var $error;
	var $element='milestone';
	var $table_element='milestone';
	var $table_element_line='milestonedet';
	var $fk_element='fk_milestone';
	
	// Id of module
	var $module_number=1790;

	var $id;
	var $label;
	var $description;
	var $priority;
	
	var $object;
	var $elementid;
	var $elementtype;
	
	var $rang;
	var $rangtouse;
	
	var $datec;
	var $dateo;
	var $datee;

	var $lines=array();			// Tableau en memoire des jalons


	/**
	 * 	Constructor
	 * 	@param	DB		acces base de donnees
	 * 	@param	id		milestone id
	 */
	function Milestone($DB)
	{
		$this->db = $DB;
	}

	/**
	 * 	Charge le jalon
	 * 	@param	id		id du jalon a charger
	 */
	function fetch($id)
	{
		$sql = "SELECT rowid, label, description, fk_element, elementtype";
		$sql.= ", datec, tms, dateo, datee, priority, fk_user_creat, rang";
		$sql.= " FROM ".MAIN_DB_PREFIX."milestone";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog("Milestone::fetch sql=".$sql);
		$resql  = $this->db->query ($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);

			$this->id		   	= $res['rowid'];
			$this->label	   	= $res['label'];
			$this->description 	= $res['description'];
			$this->elementid	= $res['fk_element'];
			$this->elementtype 	= $res['elementtype'];

			$this->db->free($resql);
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 *  Ajoute le jalon dans la base de donnees
	 * 	@return	int 	-1 : erreur SQL
	 *          		-2 : nouvel ID inconnu
	 *          		-3 : jalon invalide
	 */
	function create($user)
	{
		global $conf,$langs;
		
		$langs->load('milestone');

		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);
		
		$this->db->begin();
		
		$result = $this->object->addline(
				$this->object->id,
				$this->description,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				'HT',
				0,
				$this->dateo,
				$this->datee,
				$this->product_type,
				$this->rang,
				$this->special_code
			);
/*		
		$result=$this->object->addline(
			$this->object->id,
			$this->description,
			0,
			0,
			0,
			0,
			0,
			0,
			0,
			'HT',
			0,
			0,
			$this->product_type,
			$this->rang,
			$this->special_code
			);
*/
		if ($result > 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."milestone (";
			$sql.= "label";
			$sql.= ", fk_element";
			$sql.= ", elementtype";
			$sql.= ", dateo";
			$sql.= ", datee";
			$sql.= ") VALUES (";
			$sql.= "'".addslashes($this->label)."'";
			$sql.= ", ".$this->object->line->rowid;
			$sql.= ", '".addslashes($this->object->element)."'";
			$sql.= ", ".($this->dateo!=''?$this->db->idate($this->dateo):'null');
			$sql.= ", ".($this->datee!=''?$this->db->idate($this->datee):'null');
			$sql.= ")";
			
			$res  = $this->db->query ($sql);
			if ($res)
			{
				$this->id = $this->db->last_insert_id (MAIN_DB_PREFIX."milestone");
				
				if ($this->id > 0)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('MILESTONE_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
					
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->error=$this->db->error();
					dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}	
	}

	/**
	 * 	Update milestone
	 * 	@return	int		 1 : OK
	 *          		-1 : SQL error
	 *          		-2 : invalid milestone
	 */
	function update($user)
	{
		global $conf;

		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."milestone SET";
		$sql.= " label = '".addslashes($this->label)."'";
		$sql.= ", description = '".addslashes($this->description)."'";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Milestone::update sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->db->commit();

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MILESTONE_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Delete milestone
	 */
	function remove()
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."milestonedet";
		$sql.= " WHERE fk_milestone = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."milestone";
		$sql.= " WHERE rowid = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MILESTONE_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}

	}
	
	/**
	 * 	\brief		Return an array of object milestones
	 * 	\param		object		
	 */
	function getObjectList($object)
	{
		$sql = "SELECT rowid, label, description, fk_element, elementtype";
		$sql.= ", datec, tms, dateo, datee, priority, fk_user_creat, rang";
		$sql.= " FROM ".MAIN_DB_PREFIX."milestone";
		$sql.= " WHERE fk_element = ".$object->id;
		$sql.= " AND elementtype = '".$object->element."'";
		$sql.= " ORDER BY rang ASC, rowid";
		
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				
				$this->lines[$i]->id				= $obj->rowid;
				$this->lines[$i]->label				= $obj->label;
				$this->lines[$i]->description 		= $obj->description;
				$this->lines[$i]->elementid			= $obj->fk_element;
				$this->lines[$i]->elementtype		= $obj->elementtype;
				$this->lines[$i]->datec				= $this->db->jdate($obj->datec);
				$this->lines[$i]->datem				= $this->db->jdate($obj->datem);
				$this->lines[$i]->dateo				= $this->db->jdate($obj->dateo);
				$this->lines[$i]->datee				= $this->db->jdate($obj->datee);
				$this->lines[$i]->priority			= $obj->priority;
				$this->lines[$i]->fk_user_creat		= $obj->fk_user_creat;
				$this->lines[$i]->rang				= $obj->rang;
				
				$i++;
			}
			$this->db->free($resql);
			
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_syslog("Milestone::getObjectMilestones ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * 	\brief			Link an element to the milestone
	 *	\param			element		Element to link to milestone
	 * 	\return			int		1 : OK, -1 : erreur SQL, -2 : id non renseign, -3 : Already linked
	 */
	function link_element($element)
	{
		if ($this->id == -1)
		{
			return -2;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."milestonedet (fk_milestone, fk_element_line)";
		$sql.= " VALUES (".$this->id.", ".$element->id.")";

		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$this->db->lasterrno();
				return -3;
			}
			else
			{
				$this->error=$this->db->error().' sql='.$sql;
			}
			return -1;
		}
	}

	/**
	 * \brief		Unlink an element to the milestone
	 * \param 		element		Element to unlink to milestone
	 * \return		int			1 : OK, -1 : KO
	 */
	function unlink_element($element)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."milestonedet";
		$sql.= " WHERE fk_milestone = ".$this->id;
		$sql.= " AND fk_element_line = ".$element->id;

		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 * 	\brief	Return list of contents of a milestone
	 */
	function get_elements()
	{
		$objs = array();

		$sql = "SELECT fk_element_line";
		$sql.= " FROM ".MAIN_DB_PREFIX."milestonedet";
		$sql.= " WHERE fk_milestone = ".$this->id;

		dol_syslog("Milestone::get_element sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				
				$objs[$i] = $obj->fk_element_line;

				$i++;
			}
			return $objs;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_syslog("Milestone::get_element ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	\brief      Return name and link of category (with picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien ('', 'xyz')
	 * 	\param		maxlength		Max length of text
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$this->id.'&type='.$this->type.'">';
		$label=$langs->trans("ShowCategory").': '.$this->label;
		$lienfin='</a>';

		$picto='category';


		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.dol_trunc($this->ref,$maxlength).$lienfin;
		return $result;
	}
	
	/**
	 * 	Return HTML form for add a milestone
	 */
	function formAddObject($object)
	{
		global $conf,$langs;
		global $bc,$var;
		
		$langs->load('@milestone');
		
		include(DOL_DOCUMENT_ROOT.'/milestone/tpl/addmilestoneform.tpl.php');
	}
	
	/**
	 * 	Return HTML with milestone lines list
	 * 	@param		object			Parent object
	 * 	@param		lines			Parent object lines
	 * 	@param		sublines		Linked parent object lines
	 */
	function printObjectList($object,$lines,$sublines,$dateSelector=0)
	{
		$nbObjectLine = count($lines);
		$nbMilestone = count($this->lines);
		
		$nbTotal = $nbObjectLine + $nbMilestone;
		
		$var = true;
		$i	 = 0;
		$j	 = 1;
		
		for ($j; $j <= $nbTotal; $j++)
		{
			if (! empty($lines))
			{
				foreach ($lines as $line)
				{
					if ($line->rang == $j)
					{
						$var=!$var;
						$object->printLine($line,$var,$nbTotal,$i,$dateSelector);
						array_shift($lines);
						$i++;
					}
				}
			}
			
			if (! empty($this->lines))
			{
				foreach ($this->lines as $milestone)
				{
					if ($milestone->rang == $j)
					{
						$this->fetch($milestone->id);
						$this->printObjectLine($object, $nbTotal, $i);
						$elements = $this->get_elements();
						
						$num = count($elements);
						$var = true;
						$ii = 0;
						
						// Milestone content
						foreach ($elements as $id)
						{
							foreach ($sublines as $line)
							{
								if ($line->id == $id)
								{
									$var=!$var;
									$object->printLine($line,$var,$num,$ii,$dateSelector);
									$ii++;
								}
							}
						}
						
						array_shift($this->lines);
						$i++;
					}
				}
			}
		}
	}
	
	/**
	 * 	Return HTML with selected milestone
	 * 	@param		object			Parent object
	 * 	TODO mettre le html dans un template 
	 */
	function printObjectLine($object,$num=0,$i=0)
	{
		global $conf,$langs,$user;
		global $html,$bc;
		
		$element = $object->element;
		// TODO uniformiser
		if ($element == 'propal') $element = 'propale';
		
		// Ligne en mode visu
		if ($_GET['action'] != 'edit_milestone' || $_GET['msid'] != $this->id)
		{
			print '<tr '.$bc[$var].'>';
			
			print '<td colspan="6">';
			$text = img_object($langs->trans('Milestone'),'milestone@milestone');
			$text.= ' '.$this->label.'<br>';
			$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($this->description));
			print $html->textwithtooltip($text,$description,3,'','',$i);
			
			// Show range
			//print_date_range($line->date_start,$line->date_end);
			
			// Add description in form
			if ($conf->global->PRODUIT_DESC_IN_FORM)
			{
				print ($this->description?'<br>'.dol_htmlentitiesbr($this->description):'');
			}
			
			print "</td>\n";
			
			// Icone d'edition et suppression
			if ($object->statut == 0  && $user->rights->$element->creer)
			{
				print '<td align="center">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit_milestone&amp;msid='.$this->id.'#ms_'.$this->id.'">';
				print img_edit();
				print '</a>';
				print '</td>';
				print '<td align="center">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=ask_delete_milestone&amp;msid='.$this->id.'">';
				print img_delete();
				print '</a></td>';
				if ($num > 1)
				{
					print '<td align="center">';
					if ($i > 0)
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=milestone_up&amp;msid='.$this->id.'">';
						print img_up();
						print '</a>';
					}
					if ($i < $num-1)
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=milestone_down&amp;msid='.$this->id.'">';
						print img_down();
						print '</a>';
					}
					print '</td>';
				}
			}
			else
			{
				print '<td colspan="3">&nbsp;</td>';
			}
			
			print '</tr>';	
		}
		
		// Ligne en mode update
		if ($object->statut == 0 && $_GET["action"] == 'edit_milestone' && $user->rights->$element->creer && $_GET["msid"] == $this->id)
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#ms_'.$this->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="updatemilestone">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="milestoneid" value="'.$_GET["milestoneid"].'">';
			print '<tr '.$bc[$var].'>';
			print '<td>';
			print '<a name="ms_'.$this->id.'"></a>'; // ancre pour retourner sur la ligne
			
			// Label
			print '<tr '.$bc[$var].'>';
			print '<td colspan="5">';
			print '<input size="30" type="text" id="milestone_label" name="milestone_label" value="'.$this->label.'">';
			print '</td>';
			
			print '<td align="center" colspan="5" rowspan="2" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
			
			print '</tr>';
			
			// Description
			print '<tr '.$bc[$var].'>';
			print '<td colspan="5">';
			
			// Editor wysiwyg
			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
			{
				require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
				$doleditor=new DolEditor('milestone_desc',$this->description,100,'dolibarr_details');
				$doleditor->Create();
			}
			else
			{
				$nbrows=ROWS_2;
				if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
				print '<textarea cols="70" id="milestone_desc" name="milestone_desc" rows="'.$nbrows.'" class="flat">';
				print $this->description;
				print '</textarea>';
			}
			
			print '</td>';
			print '</tr>' . "\n";
			
			print "</form>\n";
		}
	}
	
	/**
	 * 	Return action of hook
	 * 	@param		object			Linked object
	 */
	function doActions($object)
	{
		global $user,$langs;
		
		$element = $object->element;
		// TODO uniformiser
		if ($element == 'propal') $element = 'propale';
		
		// Add milestone action
		if ($_POST['action'] == 'addmilestone' && $user->rights->milestone->creer && $user->rights->$element->creer)
		{	
			if ($_POST['milestone_label'] == $langs->trans('Label') || $_POST['milestone_desc'] == $langs->trans('Description'))
			{
				$this->error = '<div class="error">'.$langs->trans("MilestoneFieldsIsRequired").'</div>';
			}
			else
			{	
				$object->fetch($_GET["id"]);
				
				$linemax = $object->line_max();
				
				$this->rangtouse = $linemax+1;
				
				$this->object		= $object;
				$this->label 		= $_POST['milestone_label'];
				$this->description	= $_POST['milestone_desc'];
				$this->product_type = $_POST['product_type'];
				$this->special_code	= $_POST['special_code'];
				$this->rang			= $this->rangtouse;
				
				$ret = $this->create($user);
				
				if ($ret < 0)
				{
					$this->error = '<div class="error">'.$this->error.'</div>';
				}
				else
				{
					Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			}
		}
	}

}
?>
