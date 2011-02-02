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
 *	\file       htdocs/milestone/class/actions_milestone.class.php
 *	\ingroup    milestone
 *	\brief      Fichier de la classe des jalons
 *	\version	$Id$
 */


/**
 *	\class      ActionsMilestone
 *	\brief      Classe permettant la gestion des jalons
 */
class ActionsMilestone
{
	var $db;
	var $error;
	var $element='milestone';
	var $table_element='milestone';
	
	// Id of module
	var $module_number=1790;

	var $id;
	var $label;
	var $description;
	var $priority;
	
	var $object;
	var $objParent;
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
	function ActionsMilestone($DB)
	{
		$this->db = $DB;
	}

	/**
	 * 	Return HTML form for add a milestone
	 */
	function formAddObject($object)
	{
		global $conf,$langs;
		global $bcnd,$var;
		
		$langs->load('@milestone');
		
		dol_include_once('/milestone/tpl/addmilestoneform.tpl.php');
	}
	
	/**
	 * 	Return HTML with selected milestone
	 * 	@param		object			Parent object
	 * 	TODO mettre le html dans un template 
	 */
	function printObjectLine($object,$line,$num=0,$i=0)
	{
		global $conf,$langs,$user;
		global $html,$bc,$bcnd;
		
		$return = $this->object->fetch($object,$line);
	
		$element = $object->element;
		// TODO uniformiser
		if ($element == 'propal') $element = 'propale';
		
		// Ligne en mode visu
		if ($_GET['action'] != 'editline' || $_GET['lineid'] != $line->rowid)
		{
			print '<tr id="row-'.$line->id.'" '.$bc[$var].'>';
			
			print '<td colspan="6">';
			print '<a name="'.$line->rowid.'"></a>'; // ancre pour retourner sur la ligne;
			
			$text = img_object($langs->trans('Milestone'),'milestone@milestone');
			$text.= ' '.$this->object->label.'<br>';
			$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->description));
			print $html->textwithtooltip($text,$description,3,'','',$i);
			
			// Show range
			//print_date_range($line->date_start,$line->date_end);
			
			// Add description in form
			if ($conf->global->PRODUIT_DESC_IN_FORM)
			{
				print ($line->description?'<br>'.dol_htmlentitiesbr($line->description):'');
			}
			
			print "</td>\n";
			
			// Icone d'edition et suppression
			if ($object->statut == 0  && $user->rights->$element->creer)
			{
				print '<td align="center">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;lineid='.$line->rowid.'#'.$line->rowid.'">';
				print img_edit();
				print '</a>';
				print '</td>';
				print '<td align="center">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=ask_deletemilestone&amp;lineid='.$line->rowid.'">';
				print img_delete();
				print '</a></td>';
				if ($num > 1)
				{
					print '<td align="center" class="tdlineupdown">';
					if ($i > 0)
					{
						print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=up&amp;rowid='.$line->rowid.'">';
						print img_up();
						print '</a>';
					}
					if ($i < $num-1)
					{
						print '<a class="lineupdown" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=down&amp;rowid='.$line->rowid.'">';
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
		if ($object->statut == 0 && $_GET["action"] == 'editline' && $user->rights->$element->creer && $_GET["lineid"] == $line->rowid)
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$line->rowid.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="updatemilestone">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="lineid" value="'.$_GET["lineid"].'">';
			print '<input type="hidden" name="special_code" value="'.$line->special_code.'">';
			print '<input type="hidden" name="product_type" value="'.$line->product_type.'">';
			
			print '<tr '.$bcnd[$var].'>';
			print '<td>';
			print '<a name="'.$line->rowid.'"></a>'; // ancre pour retourner sur la ligne
			
			// Label
			print '<tr '.$bcnd[$var].'>';
			print '<td colspan="5">';
			print '<input size="30" type="text" id="label" name="label" value="'.$this->object->label.'">';
			print '</td>';
			
			print '<td align="center" colspan="5" rowspan="2" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
			
			print '</tr>';
			
			// Description
			print '<tr '.$bcnd[$var].'>';
			print '<td colspan="5">';
			
			// Editor wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$nbrows=ROWS_2;
			if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
			$doleditor=new DolEditor('description',$line->description,'',100,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
			$doleditor->Create();
			
			print '</td>';
			print '</tr>' . "\n";
			
			print "</form>\n";
		}
	}
	
	/**
	 * 	Return HTML with origin selected milestone
	 * 	@param		object			Parent object
	 * 	TODO mettre le html dans un template 
	 */
	function printOriginObjectLine($line,$i=0)
	{
		global $conf,$langs;
		global $html, $bc;
		
		// Ligne en mode visu
		if ($_GET['action'] != 'editline' || $_GET['lineid'] != $line->rowid)
		{
			print '<tr '.$bc[$var].'>';
			
			print '<td colspan="6">';
			
			$text = img_object($langs->trans('Milestone'),'milestone@milestone');
			$text.= ' '.$line->desc.'<br>';
			$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->desc));
			print $html->textwithtooltip($text,$description,3,'','',$i);
			
			print "</td>\n";
			
			print '</tr>';	
		}
	}
	
	/**
	 * 	Return action of hook
	 * 	@param		object			Linked object
	 */
	function doActions($objParent)
	{
		global $conf,$user,$langs;
		global $html;

		$element = $objParent->element;
		// TODO uniformiser
		if ($element == 'propal') $element = 'propale';

		/*
		 * 	Add milestone
		 */
		if ($_POST['action'] == 'addmilestone' && $user->rights->milestone->creer && $user->rights->$element->creer)
		{
			if ($_POST['milestone_label'] == $langs->trans('Label') || $_POST['milestone_desc'] == $langs->trans('Description'))
			{
				$this->error = '<div class="error">'.$langs->trans("MilestoneFieldsIsRequired").'</div>';
			}
			else
			{	
				$id = ( GETPOST("id") ? GETPOST("id") : GETPOST("facid") );

				$objParent->fetch($id);
				
				$linemax = $objParent->line_max();
				$rangtouse = $linemax+1;
				
				$this->object->objParent	= $objParent;
				$this->object->label 		= $_POST['milestone_label'];
				$this->object->description	= $_POST['milestone_desc'];
				$this->object->product_type = $_POST['product_type'];
				$this->object->special_code	= $_POST['special_code'];
				$this->object->rang			= $rangtouse;
				
				$ret = $this->object->create($user);

				if ($ret < 0)
				{
					$this->error = '<div class="error">'.$this->object->error.'</div>';
				}
				else
				{
					Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$objParent->id);
					exit;
				}
			}
		}
		
		/*
		 * 	Update Milestone
		 */
		if ($_POST['action'] == 'updatemilestone' && $user->rights->milestone->creer && $user->rights->$element->creer && $_POST["save"] == $langs->trans("Save"))
		{
			$id = $_GET["id"]?$_GET["id"]:$_GET["facid"];
			
			if (! $objParent->fetch($id) > 0)
			{
				dol_print_error($db,$objParent->error);
				exit;
			}
			
			$objParent->fetch_thirdparty();
			
			$this->object->objParent	= $objParent;
			$this->object->id			= $_POST['lineid'];
			$this->object->label 		= $_POST['label'];
			$this->object->description	= $_POST['description'];
			$this->object->product_type	= $_POST['product_type'];
			$this->object->special_code	= $_POST['special_code'];
		
			$result = $this->object->update($user);
			
			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$objParent->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			//propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
		}
		
		// Remove line
		if ($_REQUEST['action'] == 'confirm_deletemilestone' && $_REQUEST['confirm'] == 'yes' && $user->rights->milestone->creer && $user->rights->$element->creer)
		{
			$id = $_GET["id"]?$_GET["id"]:$_GET["facid"];
			
			$objParent->fetch($id);
			$objParent->fetch_thirdparty();
			
			$this->object->objParent	= $objParent;
			
			$result = $this->object->delete($_GET['lineid']);
				
			// reorder lines
			if ($result) $objParent->line_order(true);
				
			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$objParent->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			//propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
			
			if ($objParent->element != 'facture') Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$objParent->id);
			else Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$objParent->id);

			exit;
		}
	}
	
	/**
	 *	Return line description translated in outputlangs and encoded in UTF8
	 *	@param		objectParent		Object parent
	 *	@param		$i					Current line
	 *  @param    	outputlang			Object lang for output
	 *  @param    	hideref       		Hide reference
	 *  @param      hidedesc            Hide description
	 */
	function pdf_writelinedesc(&$pdf,$object,$i,$outputlangs,$w,$h,$posx,$posy,$hideref=0,$hidedesc=0)
	{
		$this->object->fetch($object,$object->lines[$i]);
		
		$pdf->SetFont('','BU', 9);
		
		$pdf->SetXY ($posx, $posy);
		$pdf->MultiCell($w, $h, $outputlangs->convToOutputCharset($this->object->label), 0, 'L');
		
		$nexy = $pdf->GetY();
		
		$pdf->SetFont('','I', 9);
		$description = dol_htmlentitiesbr($object->lines[$i]->desc,1);
		
		if ($object->lines[$i]->date_start || $object->lines[$i]->date_end)
        {
        	// Show duration if exists
        	if ($object->lines[$i]->date_start && $object->lines[$i]->date_end)
        	{
        		$period='('.$outputlangs->transnoentitiesnoconv('DateFromTo',dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs),dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
        	}
        	if ($object->lines[$i]->date_start && ! $object->lines[$i]->date_end)
        	{
        		$period='('.$outputlangs->transnoentitiesnoconv('DateFrom',dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs)).')';
        	}
        	if (! $object->lines[$i]->date_start && $object->lines[$i]->date_end)
        	{
        		$period='('.$outputlangs->transnoentitiesnoconv('DateUntil',dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
        	}
        	
        	$description.="<br>".dol_htmlentitiesbr($period,1);
        }
		
		$pdf->writeHTMLCell($w, $h, $posx, $nexy+1, $outputlangs->convToOutputCharset($description), 0, 1);
	}

	/**
	 *		Load an object from its id and create a new one in database
	 *		@param      objFrom			From object
	 *		@param      idTo			To object id
	 * 	 	@return		int				New id of clone
	 */
	function createfrom($objFrom,$idTo,$elementTo)
	{
		global $user;

		$error=0;
		
		if ((!empty($objFrom) && is_object($objFrom)) && !empty($idTo) && !empty($elementTo))
		{
			$classname = ucfirst($elementTo);
			$objTo = new $classname($this->db);
			$objTo->fetch($idTo);

			$this->object->objParent = $objTo;

			for($i=0; $i < count($objTo->lines); $i++)
			{
				if ($objTo->lines[$i]->product_type == 9 && $objTo->lines[$i]->special_code == $this->module_number)
				{
					$this->object->fetch($objFrom,$objFrom->lines[$i]);
					$this->object->objParent->line = $objTo->lines[$i];
					$ret = $this->object->create($user,1);
					if ($ret < 0) $error++;
				}
			}
		}
		
		if (! $error) return 1;
		else return -1;
	}

}
?>
