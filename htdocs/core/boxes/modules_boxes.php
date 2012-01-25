<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/boxes/modules_boxes.php
 *		\ingroup    facture
 *		\brief      Fichier contenant la classe mere des boites
 */


/**
 *	    \class      ModeleBoxes
 *		\brief      Classe mere des boites
 */
class ModeleBoxes    // Can't be abtract as it is instanciated to build "empty" boxes
{
	var $db;
	var $error='';
	var $max=5;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db		Database hanlder
	 */
	function ModeleBoxes($db)
	{
		$this->db=$db;
	}

	/**
	 *  Return last error message
	 *
	 *  @return	string				Error message
	 */
	function error()
	{
		return $this->error;
	}


	/**
	 *  Load a box line from its rowid
	 *
	 *  @param	int		$rowid		Row id to load
	 *  @return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		// Recupere liste des boites d'un user si ce dernier a sa propre liste
		$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b";
		$sql.= " WHERE b.rowid = ".$rowid;
		dol_syslog(get_class($this)."::fetch rowid=".$rowid);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->rowid=$obj->rowid;
				$this->box_id=$obj->box_id;
				$this->position=$obj->position;
				$this->box_order=$obj->box_order;
				$this->fk_user=$obj->fk_user;
				return 1;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			return -1;
		}
	}


	/**
	 *	Standard method to show a box (usage by boxes not mandatory, a box can still use its own function)
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head, $contents)
	{
		global $langs,$conf;

		$MAXLENGTHBOX=60;   // Mettre 0 pour pas de limite
		$bcx[0] = 'class="box_pair"';
		$bcx[1] = 'class="box_impair"';
		$var = false;

		dol_syslog(get_Class($this));

		// Define nbcol and nblines of the box to show
		$nbcol=0;
		if (isset($contents[0])) $nbcol=count($contents[0]);
		$nblines=count($contents);

		print "\n\n<!-- Box start -->\n";
		print '<div class="box" id="boxto_'.$this->box_id.'">'."\n";

		if (! empty($head['text']) || ! empty($head['sublink']) || $nblines)
		{
			print '<table summary="boxtable'.$this->box_id.'" width="100%" class="noborder boxtable">'."\n";
		}

		// Show box title
		if (! empty($head['text']) || ! empty($head['sublink']))
		{
			//print '<div id="boxto_'.$this->box_id.'_title">'."\n";
			//print '<table summary="boxtabletitle'.$this->box_id.'" width="100%" class="noborder">'."\n";
			print '<tr class="box_titre">';
			print '<td';
			if ($nbcol > 0) { print ' colspan="'.$nbcol.'"'; }
			print '>';
			if ($conf->use_javascript_ajax)
			{
				print '<table summary="" class="nobordernopadding" width="100%"><tr><td>';
			}
			if (! empty($head['text']))
			{
				$s=dol_trunc($head['text'],isset($head['limit'])?$head['limit']:$MAXLENGTHBOX);
				print $s;
			}
			if (! empty($head['sublink']))
			{
				print ' <a href="'.$head['sublink'].'" target="_blank">'.img_picto($head['subtext'],$head['subpicto']).'</a>';
			}
			if ($conf->use_javascript_ajax)
			{
				print '</td><td class="nocellnopadd" width="14">';
				// The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
				print img_picto($langs->trans("MoveBox",$this->box_id),'uparrow','class="boxhandle" style="cursor:move;"');
				print '</td></tr></table>';
			}
			print '</td>';
			print "</tr>\n";
//			print "</table>\n";
//			print "</div>\n";
		}

		// Show box lines
		if ($nblines)
		{
			//print '<table summary="boxtablelines'.$this->box_id.'" width="100%" class="noborder">'."\n";
			// Loop on each record
			for ($i=0, $n=$nblines; $i < $n; $i++)
			{
				if (isset($contents[$i]))
				{
					$var=!$var;

					// TR
					if (isset($contents[$i][0]['tr'])) print '<tr valign="top" '.$contents[$i][0]['tr'].'>';
					else print '<tr valign="top" '.$bcx[$var].'>';

					// Loop on each TD
					$nbcolthisline=count($contents[$i]);
					for ($j=0; $j < $nbcolthisline; $j++)
					{
						// Define tdparam
						$tdparam='';
						if (isset($contents[$i][$j]['td'])) $tdparam.=' '.$contents[$i][$j]['td'];

						if (empty($contents[$i][$j]['text'])) $contents[$i][$j]['text']="";
						$texte=isset($contents[$i][$j]['text'])?$contents[$i][$j]['text']:'';
						$textewithnotags=preg_replace('/<([^>]+)>/i','',$texte);
						$texte2=isset($contents[$i][$j]['text2'])?$contents[$i][$j]['text2']:'';
						$texte2withnotags=preg_replace('/<([^>]+)>/i','',$texte2);
						//print "xxx $textewithnotags y";

						print '<td'.$tdparam.'>';

						// Url
						if (! empty($contents[$i][$j]['url']))
						{
							print '<a href="'.$contents[$i][$j]['url'].'" title="'.$textewithnotags.'"';
							//print ' alt="'.$textewithnotags.'"';      // Pas de alt sur un "<a href>"
							print isset($contents[$i][$j]['target'])?' target="'.$contents[$i][$j]['target'].'"':'';
							print '>';
						}

						// Logo
						if (! empty($contents[$i][$j]['logo']))
						{
							$logo=preg_replace("/^object_/i","",$contents[$i][$j]['logo']);
							print img_object($langs->trans("Show"),$logo);
						}

						$maxlength=$MAXLENGTHBOX;
						if (! empty($contents[$i][$j]['maxlength'])) $maxlength=$contents[$i][$j]['maxlength'];

						if ($maxlength) $textewithnotags=dol_trunc($textewithnotags,$maxlength);
						if (preg_match('/^<img/i',$texte) || ! empty($contents[$i][$j]['asis'])) print $texte;	// show text with no html cleaning
						else print $textewithnotags;				// show text with html cleaning

						// End Url
						if (! empty($contents[$i][$j]['url'])) print '</a>';

						if (preg_match('/^<img/i',$texte2) || ! empty($contents[$i][$j]['asis2'])) print $texte2;	// show text with no html cleaning
						else print $texte2withnotags;				// show text with html cleaning

						print "</td>";
					}

					print "</tr>\n";
				}
			}

			// Complete line to max
			/*
			while ($i < $this->max)
			{
				$var=!$var;
				print '<tr '.$bcx[$var].'><td colspan="'.$nbcol.'">&nbsp;</td></tr>';
				$i++;
			}*/

			//print "</table>\n";
		}

		if (! empty($head['text']) || ! empty($head['sublink']) || $nblines)
		{
			print "</table>\n";
		}

		// If invisible box with no contents
		if (empty($head['text']) && empty($head['sublink']) && ! $nblines) print "<br>\n";

		print "</div>\n";
		print "<!-- Box end -->\n\n";
	}

}


?>
