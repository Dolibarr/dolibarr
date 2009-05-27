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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/includes/boxes/modules_boxes.php
 *		\ingroup    facture
 *		\brief      Fichier contenant la classe mere des boites
 *		\version    $Id$
 */


/**
 *	    \class      ModeleBoxes
 *		\brief      Classe mere des boites
 */
class ModeleBoxes
{
	var $db;
	var $error='';
	var $max=5;

	/*
	 *	\brief		Constructeur
	 */
	function ModeleBoxes($DB)
	{
		$this->db=$DB;
	}


	/**
	 *    \brief      Renvoi le dernier message d'erreur de creation de facture
	 */
	function error()
	{
		return $this->error;
	}


	/**
	 *    \brief      Charge une ligne boxe depuis son rowid
	 */
	function fetch($rowid)
	{
		// Recupere liste des boites d'un user si ce dernier a sa propre liste
		$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b";
		$sql.= " WHERE b.rowid = ".$rowid;
		dol_syslog("ModeleBoxes::fetch rowid=".$rowid);

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
	 *	\brief      Methode standard d'affichage des boites
	 *	\param      $head       tableau des caracteristiques du titre
	 *	\param      $contents   tableau des lignes de contenu
	 */
	function showBox($head, $contents)
	{
		global $langs,$conf;

		$MAXLENGTHBOX=60;   // Mettre 0 pour pas de limite
		$bcx[0] = 'class="box_pair"';
		$bcx[1] = 'class="box_impair"';
		$var = true;

		dol_syslog("modules_box::showBox ".get_Class($this));

		// Define nbcol and nblines of the box to show
		$nbcol=0;
		if (isset($contents[0])) $nbcol=sizeof($contents[0]);
		$nblines=sizeof($contents);

		print "\n\n<!-- Box start -->\n";
		print '<div style="padding-right: 2px; padding-left: 2px; padding-bottom: 4px;" id="boxto_'.$this->box_id.'">'."\n";

		// Show box title
		if (! empty($head['text']) || ! empty($head['sublink']))
		{
			print '<div id="boxto_'.$this->box_id.'_title">'."\n";
			print '<table summary="boxtabletitle'.$this->box_id.'" width="100%" class="noborder">'."\n";
			print '<tr class="box_titre">';
			print '<td';
			if ($nbcol > 0) { print ' colspan="'.$nbcol.'"'; }
			print '>';
			if ($conf->use_javascript_ajax)
			{
				print '<table summary="" class="nobordernopadding" width="100%"><tr><td align="left">';
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
			print "</table>\n";
			print "</div>\n";
		}

		// Show box lines
		if ($nblines)
		{
			print '<table summary="boxtablelines'.$this->box_id.'" width="100%" class="noborder">'."\n";
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
					$nbcolthisline=sizeof($contents[$i]);
					for ($j=0; $j < $nbcolthisline; $j++)
					{
						// Define tdparam
						$tdparam='';
						if (isset($contents[$i][$j]['td'])) $tdparam.=' '.$contents[$i][$j]['td'];

						if (empty($contents[$i][$j]['text'])) $contents[$i][$j]['text']="";
						$texte=isset($contents[$i][$j]['text'])?$contents[$i][$j]['text']:'';
						$textewithnotags=eregi_replace('<[^>]+>','',$texte);
						$texte2=isset($contents[$i][$j]['text2'])?$contents[$i][$j]['text2']:'';
						$texte2withnotags=eregi_replace('<[^>]+>','',$texte2);
						//print "xxx $textewithnotags y";

						print '<td'.$tdparam.'>';

						// Url
						if (! empty($contents[$i][$j]['url'])) {
							print '<a href="'.$contents[$i][$j]['url'].'" title="'.$textewithnotags.'"';
							//print ' alt="'.$textewithnotags.'"';      // Pas de alt sur un "<a href>"
							print isset($contents[$i][$j]['target'])?' target="'.$contents[$i][$j]['target'].'"':'';
							print '>';
						}

						// Logo
						if (! empty($contents[$i][$j]['logo']))
						{
							$logo=eregi_replace("^object_","",$contents[$i][$j]['logo']);
							print img_object($langs->trans("Show"),$logo);
						}

						$maxlength=$MAXLENGTHBOX;
						if (! empty($contents[$i][$j]['maxlength'])) $maxlength=$contents[$i][$j]['maxlength'];

						if ($maxlength) $textewithnotags=dol_trunc($textewithnotags,$maxlength);
						if (eregi('^<img',$texte)) print $texte;	// show text with no html cleaning
						else print $textewithnotags;				// show text with html cleaning

						// End Url
						if (! empty($contents[$i][$j]['url'])) print '</a>';

						if (eregi('^<img',$texte2)) print $texte2;	// show text with no html cleaning
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

			print "</table>\n";
		}

		// If invisible box with no contents
		if (empty($head['text']) && empty($head['sublink']) && ! $nblines) print "<br><br>\n";

		print "</div>\n";
		print "<!-- Box end -->\n\n";
	}

}


?>
