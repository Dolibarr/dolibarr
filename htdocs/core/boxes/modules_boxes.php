<?php
/* Copyright (C) 2004-2013  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin       <regis.houssin@inodbox.com>
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
class ModeleBoxes // Can't be abtract as it is instantiated to build "empty" boxes
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
	 * @var int Condition to have widget enabled
	 */
	public $enabled=1;

	/**
	 * @var int Condition to have widget visible (in most cases, permissions)
	 */
	public $hidden=0;

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

		if (! empty($this->hidden)) return '\n<!-- Box ".get_class($this)." hidden -->\n';    // Nothing done if hidden (for example when user has no permission)

        require_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';

		$MAXLENGTHBOX=60;   // Mettre 0 pour pas de limite

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

            $out.= '<div class="box boxdraggable" id="boxto_'.$this->box_id.'">'."\n";

            if (! empty($head['text']) || ! empty($head['sublink']) || ! empty($head['subpicto']) || $nblines)
            {
                $out.= '<table summary="boxtable'.$this->box_id.'" width="100%" class="noborder boxtable">'."\n";
            }

            // Show box title
            if (! empty($head['text']) || ! empty($head['sublink']) || ! empty($head['subpicto']))
            {
                $out.= '<tr class="liste_titre box_titre">';
                $out.= '<td';
                if ($nbcol > 0) { $out.= ' colspan="'.$nbcol.'"'; }
                $out.= '>';
                if ($conf->use_javascript_ajax)
                {
                    $out.= '<table summary="" class="nobordernopadding" width="100%"><tr><td class="tdoverflowmax150 maxwidth150onsmartphone">';
                }
                if (! empty($head['text']))
                {
                    $s=dol_trunc($head['text'],isset($head['limit'])?$head['limit']:$MAXLENGTHBOX);
                    $out.= $s;
                }
                $out.= '</td>';

                if (! empty($conf->use_javascript_ajax))
                {
                    $sublink='';
                    if (! empty($head['sublink']))  $sublink.= '<a href="'.$head['sublink'].'"'.(empty($head['target'])?'':' target="'.$head['target'].'"').'>';
                    if (! empty($head['subpicto'])) $sublink.= img_picto($head['subtext'], $head['subpicto'], 'class="opacitymedium '.(empty($head['subclass'])?'':$head['subclass']).'" id="idsubimg'.$this->boxcode.'"');
                    if (! empty($head['sublink']))  $sublink.= '</a>';

                    $out.= '<td class="nocellnopadd boxclose right nowraponall">';
                    $out.=$sublink;
                    // The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
                    $out.= img_picto($langs->trans("MoveBox",$this->box_id),'grip_title','class="opacitymedium boxhandle hideonsmartphone cursormove"');
                    $out.= img_picto($langs->trans("CloseBox",$this->box_id),'close_title','class="opacitymedium boxclose cursorpointer" rel="x:y" id="imgclose'.$this->box_id.'"');
                    $label=$head['text'];
                    //if (! empty($head['graph'])) $label.=' ('.$langs->trans("Graph").')';
                    if (! empty($head['graph'])) $label.=' <span class="opacitymedium fa fa-bar-chart"></span>';
                    $out.= '<input type="hidden" id="boxlabelentry'.$this->box_id.'" value="'.dol_escape_htmltag($label).'">';
                    $out.= '</td></tr></table>';
                }

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
                        // TR
                        if (isset($contents[$i][0]['tr'])) $out.= '<tr '.$contents[$i][0]['tr'].'>';
                        else $out.= '<tr class="oddeven">';

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
                            if (preg_match('/^<img/i',$text) || preg_match('/^<div/i',$text) || ! empty($contents[$i][$j]['asis'])) $out.= $text;   // show text with no html cleaning
                            else $out.= $textwithnotags;                // show text with html cleaning

                            // End Url
                            if (! empty($contents[$i][$j]['url'])) $out.= '</a>';

                            if (preg_match('/^<img/i',$text2) || preg_match('/^<div/i',$text2) || ! empty($contents[$i][$j]['asis2'])) $out.= $text2; // show text with no html cleaning
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


	/**
	 *  Return list of widget. Function used by admin page htdoc/admin/widget.
	 *  List is sorted by widget filename so by priority to run.
	 *
	 *  @param	array	$forcedirwidget		null=All default directories. This parameter is used by modulebuilder module only.
	 * 	@return	array						Array list of widget
	 */
	static function getWidgetsList($forcedirwidget=null)
	{
		global $conf, $langs, $db;

		$files = array();
		$fullpath = array();
		$relpath = array();
		$iscoreorexternal = array();
		$modules = array();
		$orders = array();
		$i = 0;

		$dirwidget=array_merge(array('/core/boxes/'));
		if (is_array($forcedirwidget))
		{
			$dirwidget=$forcedirwidget;
		}

		foreach($dirwidget as $reldir)
		{
			$dir=dol_buildpath($reldir,0);
			$newdir=dol_osencode($dir);

			// Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php at each call)
			if (! is_dir($newdir)) continue;

			$handle=opendir($newdir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					if (is_readable($newdir.'/'.$file) && preg_match('/^(.+)\.php/',$file,$reg))
					{
						if (preg_match('/\.back$/',$file)) continue;

						$part1=$reg[1];

						$modName = ucfirst($reg[1]);
						//print "file=$file"; print "modName=$modName"; exit;
						if (in_array($modName,$modules))
						{
							$langs->load("errors");
							print '<div class="error">'.$langs->trans("Error").' : '.$langs->trans("ErrorDuplicateWidget",$modName,"").'</div>';
						}
						else
						{
							try {
								include_once $newdir.'/'.$file;
							}
							catch(Exception $e)
							{
								print $e->getMessage();
							}
						}

						$files[$i] = $file;
						$fullpath[$i] = $dir.'/'.$file;
						$relpath[$i] = preg_replace('/^\//','',$reldir).'/'.$file;
						$iscoreorexternal[$i] = ($reldir == '/core/boxes/'?'internal':'external');
						$modules[$i] = $modName;
						$orders[$i] = $part1;   // Set sort criteria value

						$i++;
					}
				}
				closedir($handle);
			}
		}

		asort($orders);

		$widget = array();
		$j = 0;

		// Loop on each widget
		foreach ($orders as $key => $value)
		{
			$modName = $modules[$key];
			if (empty($modName)) continue;

			if (! class_exists($modName))
			{
				print 'Error: A widget file was found but its class "'.$modName.'" was not found.'."<br>\n";
				continue;
			}

			$objMod = new $modName($db);
			if (is_object($objMod))
			{
				// Define disabledbyname and disabledbymodule
				$disabledbyname=0;
				$module='';

				// Check if widget file is disabled by name
				if (preg_match('/NORUN$/i',$files[$key])) $disabledbyname=1;

				// We set info of modules
				$widget[$j]['picto'] = $objMod->picto?img_object('',$objMod->picto):img_object('','generic');
				$widget[$j]['file'] = $files[$key];
				$widget[$j]['fullpath'] = $fullpath[$key];
				$widget[$j]['relpath'] = $relpath[$key];
				$widget[$j]['iscoreorexternal'] = $iscoreorexternal[$key];
				//$widget[$j]['version'] = $objMod->getVersion();
				$widget[$j]['status'] = img_picto($langs->trans("Active"),'tick');
				if ($disabledbyname > 0 || $disabledbymodule > 1) $widget[$j]['status'] = '';

				$text ='<b>'.$langs->trans("Description").':</b><br>';
				$text.=$objMod->boxlabel.'<br>';
				$text.='<br><b>'.$langs->trans("Status").':</b><br>';
				if ($disabledbymodule == 2) $text.=$langs->trans("HooksDisabledAsModuleDisabled",$module).'<br>';

				$widget[$j]['info'] = $text;
			}
			$j++;
		}
		return $widget;
	}
}


