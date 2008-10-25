<?php
/* Copyright (c) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/html.formactions.class.php
        \brief      Fichier de la classe des fonctions prédéfinie de composants html actions
		\version	$Id$
*/


/**
        \class      FormActions
        \brief      Classe permettant la génération de composants html actions
*/
class FormActions
{
	var $db;
	var $error;
	

	/**
	*		\brief     Constructeur
	*		\param     DB      handler d'accès base de donnée
	*/
	function FormActions($DB)
	{
		$this->db = $DB;
		
		return 1;
	}


	/**
	 *      \brief      Show list of action status
	 * 		\param		formname	Name of form where select in included
	 * 		\param		selected	Preselected value
	 * 		\param		canedit		1=can edit, 0=read only
	 */
	function form_select_status_action($formname,$selected,$canedit=1)
	{
		global $langs,$conf;

		$listofstatus=array('0'=>$langs->trans("ActionRunningNotStarted"),'50'=>$langs->trans("ActionRunningShort"),'100'=>$langs->trans("ActionDoneShort"));

		if ($conf->use_javascript_ajax)
		{
			print "\n";
			print '<script type="text/javascript">'."\n";
			print 'function select_status(mypercentage) {'."\n";
			print 'document.'.$formname.'.percentageshown.value=mypercentage;'."\n";
			print 'document.'.$formname.'.percentage.value=mypercentage;'."\n";
			print 'if (mypercentage == 0) { document.'.$formname.'.percentageshown.disabled=true; }'."\n";
			print 'else if (mypercentage == 100) { document.'.$formname.'.percentageshown.disabled=true; }'."\n";
			print 'else { document.'.$formname.'.percentageshown.disabled=false; }'."\n";
			print '}'."\n";
			print '</script>'."\n";
			print '<select '.($canedit?'':'disabled="true" ').'name="status" class="flat" onChange="select_status(document.'.$formname.'.status.value)">';
			foreach($listofstatus as $key => $val)
			{
				print '<option value="'.$key.'"'.($selected == $key?' selected="true"':'').'>'.$val.'</option>';
			}
			print '</select>';
			if ($selected == 0 || $selected == 100) $canedit=0;
			print ' <input type="text" name="percentageshown" class="flat" value="'.$selected.'" size="2"'.($canedit?'':' disabled="true"').' onChange="select_status(document.'.$formname.'.percentageshown.value)">%';
			print ' <input type="hidden" name="percentage" value="'.$selected.'">';
		}
		else
		{
			print ' <input type="text" name="percentage" class="flat" value="'.$selected.'" size="2"'.($canedit?'':' disabled="true"').'>%';
		}
	}

	
	/**
	*    	\brief      Show list of actions for element
	*    	\param      object			Object
	*    	\param      typeelement		'invoice','propal','order'
	*		\param		socid			socid of user
	*		\return		int				<0 if KO, >=0 if OK
	*/
	function showactions($object,$typeelement,$socid=0)
	{
		global $langs,$conf,$user;
		global $bc;
		
		$sql = 'SELECT a.id, '.$this->db->pdate('a.datep').' as da, a.label, a.note,';
		$sql.= ' u.login';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a, '.MAIN_DB_PREFIX.'user as u';
		$sql.= ' WHERE a.fk_user_author = u.rowid';
		if ($socid) $sql .= ' AND a.fk_soc = '.$socid;
		if ($typeelement == 'invoice') $sql.= ' AND a.fk_facture = '.$object->id;
		if ($typeelement == 'propal')  $sql.= ' AND a.propalrowid = '.$object->id;
		if ($typeelement == 'order')   $sql.= ' AND a.fk_commande = '.$object->id;

		dolibarr_syslog("FormActions::showactions sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				if ($typeelement == 'invoice') $title=$langs->trans('ActionsOnBill');
				if ($typeelement == 'propal')  $title=$langs->trans('ActionsOnPropal');
				if ($typeelement == 'order')   $title=$langs->trans('ActionsOnOrder');
				print_titre($title);

				$i = 0; $total = 0;	$var=true;
				print '<table class="border" width="100%">';
				print '<tr '.$bc[$var].'><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Action').'</td><td>'.$langs->trans('By').'</td></tr>';
				print "\n";

				while ($i < $num)
				{
					$objp = $this->db->fetch_object($resql);
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowTask'),'task').' '.$objp->id.'</a></td>';
					print '<td>'.dolibarr_print_date($objp->da,'day').'</td>';
					print '<td>'.dolibarr_trunc($objp->label,32).'</td>';
					print '<td>'.$objp->login.'</td>';
					print '</tr>';
					$i++;
				}
				print '</table>';
			}
			
			return $num;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}

}
