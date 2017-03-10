<?php
/* Copyright (C) 2004-2013  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Frederic France     <frederic.france@free.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/boxes/modules_boxes.php
 *		\ingroup    facture
 *		\brief      Fichier contenant la classe mere des boites
 */


/**
 * Class ModeleBoxes
 *
 * Boxes parent class
 */
class ModeleBoxes    // Can't be abtract as it is instantiated to build "empty" boxes
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var int Maximum lines
	 */
	public $max = 5;

	/**
	 * @var int Status
	 */
	public $enabled=1;

	/**
	 * @var int Box definition database ID
	 */
	public $rowid;

	/**
	 * @var int ID
	 * @deprecated Same as box_id?
	 */
	public $id;

	/**
	 * @var int Position?
	 */
	public $position;

	/**
	 * @var string Display order
	 */
	public $box_order;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	/**
	 * @var string Source file
	 */
	public $sourcefile;

	/**
	 * @var string Class name
	 */
	public $class;

	/**
	 * @var string ID
	 */
	public $box_id;

	/**
	 * @var string Alphanumeric ID
	 */
	public $boxcode;

	/**
	 * @var string Note
	 */
	public $note;

	/**
	 * Constructor
	 *
	 * @param   DoliDB  $db     Database handler
     * @param   string  $param  More parameters
	 */
	function __construct($db,$param='')
	{
		$this->db=$db;
	}

	/**
	 * Return last error message
	 *
	 * @return  string  Error message
	 */
	function error()
	{
		return $this->error;
	}


	/**
	 * Load a box line from its rowid
	 *
	 * @param   int $rowid  Row id to load
	 *
	 * @return  int         <0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		global $conf;

		// Recupere liste des boites d'un user si ce dernier a sa propre liste
		$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b";
		$sql.= " WHERE b.entity = ".$conf->entity;
		$sql.= " AND b.rowid = ".$rowid;
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
	 * Standard method to get content of a box
	 *
	 * @param   array   $head       Array with properties of box title
	 * @param   array   $contents   Array with properties of box lines
	 *
	 * @return  string              
	 */
	function outputBox($head = null, $contents = null)
	{
		global $langs, $user, $conf;
	
		// Trick to get result into a var from a function that makes print instead of return
		// TODO Replace ob_start with param nooutput=1 into showBox
		ob_start();
		$result = $this->showBox($head, $contents);
		$output = ob_get_contents();
		ob_end_clean();
		
		return $output;
	}
	
	/**
	 * Standard method to show a box (usage by boxes not mandatory, a box can still use its own showBox function)
	 *
	 * @param   array   $head       Array with properties of box title
	 * @param   array   $contents   Array with properties of box lines
	 * @param	int		$nooutput	No print, only return string
	 * @return  string
	 */
	function showBox($head = null, $contents = null, $nooutput=0)
	{
		global $langs, $user, $conf;

        require_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';

		$MAXLENGTHBOX=60;   // Mettre 0 pour pas de limite
		$bcx = array();
		$bcx[0] = 'class="box_pair"';
		$bcx[1] = 'class="box_impair"';
		$var = false;

        $cachetime = 900;   // 900 : 15mn
        $cachedir = DOL_DATA_ROOT.'/boxes/temp';
        $fileid = get_class($this).'id-'.$this->box_id.'-e'.$conf->entity.'-u'.$user->id.'-s'.$user->societe_id.'.cache';
        $filename = '/box-'.$fileid;
        $refresh = dol_cache_refresh($cachedir, $filename, $cachetime);
        $out = '';

        if ($refresh) {
            dol_syslog(get_class($this).'::showBox');

            // Define nbcol and nblines of the box to show
            $nbcol=0;
            if (isset($contents[0])) $nbcol=count($contents[0]);
            $nblines=count($contents);

            $out.= "\n<!-- Box ".get_class($this)." start -->\n";
            $out.= '<div class="box" id="boxto_'.$this->box_id.'">'."\n";

            if (! empty($head['text']) || ! empty($head['sublink']) || ! empty($head['subpicto']) || $nblines)
            {
                $out.= '<table summary="boxtable'.$this->box_id.'" width="100%" class="noborder boxtable">'."\n";
            }

            // Show box title
            if (! empty($head['text']) || ! empty($head['sublink']) || ! empty($head['subpicto']))
            {
                $out.= '<tr class="box_titre">';
                $out.= '<td';
                if ($nbcol > 0) { $out.= ' colspan="'.$nbcol.'"'; }
                $out.= '>';
                if ($conf->use_javascript_ajax)
                {
                    $out.= '<table summary="" class="nobordernopadding" width="100%"><tr><td class="tdoverflowmax100 maxwidth100onsmartphone">';
                }
                if (! empty($head['text']))
                {
                    $s=dol_trunc($head['text'],isset($head['limit'])?$head['limit']:$MAXLENGTHBOX);
                    $out.= $s;
                }
                $out.= ' ';

                $sublink='';
                if (! empty($head['sublink']))  $sublink.= '<a href="'.$head['sublink'].'"'.(empty($head['target'])?' target="_blank"':'').'>';
                if (! empty($head['subpicto'])) $sublink.= img_picto($head['subtext'], $head['subpicto'], 'class="'.(empty($head['subclass'])?'':$head['subclass']).'" id="idsubimg'.$this->boxcode.'"');
                if (! empty($head['sublink']))  $sublink.= '</a>';
                if (! empty($conf->use_javascript_ajax))
                {
                    $out.= '</td><td class="nocellnopadd boxclose nowrap">';
                    $out.=$sublink;
                    // The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
                    $out.= img_picto($langs->trans("MoveBox",$this->box_id),'grip_title','class="boxhandle hideonsmartphone" style="cursor:move;"');
                    $out.= img_picto($langs->trans("CloseBox",$this->box_id),'close_title','class="boxclose" rel="x:y" style="cursor:pointer;" id="imgclose'.$this->box_id.'"');
                    $label=$head['text'];
                    if (! empty($head['graph'])) $label.=' ('.$langs->trans("Graph").')';
                    $out.= '<input type="hidden" id="boxlabelentry'.$this->box_id.'" value="'.dol_escape_htmltag($label).'">';
                    $out.= '</td></tr></table>';
                }
                $out.= '</td>';
                $out.= "</tr>\n";
            }

            // Show box lines
            if ($nblines)
            {
                // Loop on each record
                for ($i=0, $n=$nblines; $i < $n; $i++)
                {
                    if (isset($contents[$i]))
                    {
                        $var=!$var;

                        // TR
                        if (isset($contents[$i][0]['tr'])) $out.= '<tr valign="top" '.$contents[$i][0]['tr'].'>';
                        else $out.= '<tr valign="top" '.$bcx[$var].'>';

                        // Loop on each TD
                        $nbcolthisline=count($contents[$i]);
                        for ($j=0; $j < $nbcolthisline; $j++) {
                            // Define tdparam
                            $tdparam='';
                            if (isset($contents[$i][$j]['td'])) $tdparam.=' '.$contents[$i][$j]['td'];

                            $text=isset($contents[$i][$j]['text'])?$contents[$i][$j]['text']:'';
                            $textwithnotags=preg_replace('/<([^>]+)>/i','',$text);
                            $text2=isset($contents[$i][$j]['text2'])?$contents[$i][$j]['text2']:'';
                            $text2withnotags=preg_replace('/<([^>]+)>/i','',$text2);
                            $textnoformat=isset($contents[$i][$j]['textnoformat'])?$contents[$i][$j]['textnoformat']:'';
                            //$out.= "xxx $textwithnotags y";
                            if (empty($contents[$i][$j]['tooltip'])) $contents[$i][$j]['tooltip']="";
                            $tooltip=isset($contents[$i][$j]['tooltip'])?$contents[$i][$j]['tooltip']:'';

                            $out.= '<td'.$tdparam.'>'."\n";

                            // Url
                            if (! empty($contents[$i][$j]['url']) && empty($contents[$i][$j]['logo']))
                            {
                                $out.= '<a href="'.$contents[$i][$j]['url'].'"';
                                if (!empty($tooltip)) {
                                    $out .= ' title="'.dol_escape_htmltag($langs->trans("Show").' '.$tooltip, 1).'" class="classfortooltip"';
                                }
                                //$out.= ' alt="'.$textwithnotags.'"';      // Pas de alt sur un "<a href>"
                                $out.= isset($contents[$i][$j]['target'])?' target="'.$contents[$i][$j]['target'].'"':'';
                                $out.= '>';
                            }

                            // Logo
                            if (! empty($contents[$i][$j]['logo']))
                            {
                                $logo=preg_replace("/^object_/i","",$contents[$i][$j]['logo']);
                                $out.= '<a href="'.$contents[$i][$j]['url'].'">';
                                $out.= img_object($langs->trans("Show").' '.$tooltip, $logo, 'class="classfortooltip"');
                            }

                            $maxlength=$MAXLENGTHBOX;
                            if (! empty($contents[$i][$j]['maxlength'])) $maxlength=$contents[$i][$j]['maxlength'];

                            if ($maxlength) $textwithnotags=dol_trunc($textwithnotags,$maxlength);
                            if (preg_match('/^<img/i',$text) || ! empty($contents[$i][$j]['asis'])) $out.= $text;   // show text with no html cleaning
                            else $out.= $textwithnotags;                // show text with html cleaning

                            // End Url
                            if (! empty($contents[$i][$j]['url'])) $out.= '</a>';

                            if (preg_match('/^<img/i',$text2) || ! empty($contents[$i][$j]['asis2'])) $out.= $text2; // show text with no html cleaning
                            else $out.= $text2withnotags;               // show text with html cleaning

                            if (! empty($textnoformat)) $out.= "\n".$textnoformat."\n";

                            $out.= "</td>\n";
                        }

                        $out.= "</tr>\n";
                    }
                }
            }

            if (! empty($head['text']) || ! empty($head['sublink']) || ! empty($head['subpicto']) || $nblines)
            {
                $out.= "</table>\n";
            }

            // If invisible box with no contents
            if (empty($head['text']) && empty($head['sublink']) && empty($head['subpicto']) && ! $nblines) $out.= "<br>\n";

            $out.= "</div>\n";
            $out.= "<!-- Box ".get_class($this)." end -->\n\n";
            if (! empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
                dol_filecache($cachedir, $filename, $out);
            }
        } else {
            dol_syslog(get_class($this).'::showBoxCached');
            $out = "<!-- Box ".get_class($this)." from cache -->";
            $out.= dol_readcachefile($cachedir, $filename);
        }
        
        if ($nooutput) return $out;
        else print $out;
    
        return '';
	}
	
}


