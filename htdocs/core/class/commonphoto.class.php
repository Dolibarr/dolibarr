<?php
/* Copyright (C) 2023       Frédéric France     <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/class/commonphoto.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of object classes that support photo
 */


/**
 *      Superclass for object classes that support photo
 */
trait CommonPhoto
{
	/**
	 * @var int how many photos
	 */
	public $nbphoto = 0;

	public $imgWidth;
	public $imgHeight;

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show photos of an object (nbmax maximum), into several columns
	 *
	 *  @param		string		$modulepart		'product', 'ticket', ...
	 *  @param      string		$sdir        	Directory to scan (full absolute path)
	 *  @param      int			$size        	0=original size, 1='small' use thumbnail if possible
	 *  @param      int			$nbmax       	Nombre maximum de photos (0=pas de max)
	 *  @param      int			$nbbyrow     	Number of image per line or -1 to use div separator or 0 to use no separator. Used only if size=1 or 'small'.
	 * 	@param		int			$showfilename	1=Show filename
	 * 	@param		int			$showaction		1=Show icon with action links (resize, delete)
	 * 	@param		int			$maxHeight		Max height of original image when size='small' (so we can use original even if small requested). If 0, always use 'small' thumb image.
	 * 	@param		int			$maxWidth		Max width of original image when size='small'
	 *  @param      int     	$nolink         Do not add a href link to view enlarged imaged into a new tab
	 *  @param      int|string  $overwritetitle Do not add title tag on image
	 *  @param		int			$usesharelink	Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
	 *  @param		string		$cache			A string if we want to use a cached version of image
	 *  @param		string		$addphotorefcss	Add CSS to img of photos
	 *  @return     string						Html code to show photo. Number of photos shown is saved in this->nbphoto
	 */
	public function show_photos($modulepart, $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $overwritetitle = 0, $usesharelink = 0, $cache = '', $addphotorefcss = 'photoref')
	{
		// phpcs:enable
		global $conf, $user, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

		$sortfield = 'position_name';
		$sortorder = 'asc';

		$dir = $sdir.'/';
		$pdir = '/';

		$dir .= get_exdir(0, 0, 0, 0, $this, $modulepart);
		$pdir .= get_exdir(0, 0, 0, 0, $this, $modulepart);

		// For backward compatibility
		if ($modulepart == 'product') {
			if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
				$dir = $sdir.'/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
				$pdir = '/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
			}
		}

		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($dir) {
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
			$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
			$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
		}

		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';

		$return = '<!-- Photo -->'."\n";
		$nbphoto = 0;

		$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);

		/*if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO'))    // For backward compatiblity, we scan also old dirs
		 {
		 $filearrayold=dol_dir_list($dirold,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		 $filearray=array_merge($filearray, $filearrayold);
		 }*/

		completeFileArrayWithDatabaseInfo($filearray, $relativedir);

		if (count($filearray)) {
			if ($sortfield && $sortorder) {
				$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
			}

			foreach ($filearray as $key => $val) {
				$photo = '';
				$file = $val['name'];

				//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
				if (image_format_supported($file) >= 0) {
					$nbphoto++;
					$photo = $file;
					$viewfilename = $file;

					if ($size == 1 || $size == 'small') {   // Format vignette
						// Find name of thumb file
						$photo_vignette = basename(getImageFileNameForSize($dir.$file, '_small'));
						if (!dol_is_file($dirthumb.$photo_vignette)) {
							// The thumb does not exists, so we will use the original file
							$dirthumb = $dir;
							$pdirthumb = $pdir;
							$photo_vignette = basename($file);
						}

						// Get filesize of original file
						$imgarray = dol_getImageSize($dir.$photo);

						if ($nbbyrow > 0) {
							if ($nbphoto == 1) {
								$return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';
							}

							if ($nbphoto % $nbbyrow == 1) {
								$return .= '<tr class="center valignmiddle" style="border: 1px">';
							}
							$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%" class="photo">'."\n";
						} elseif ($nbbyrow < 0) {
							$return .= '<div class="inline-block">'."\n";
						}

						$relativefile = preg_replace('/^\//', '', $pdir.$photo);
						if (empty($nolink)) {
							$urladvanced = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity='.$this->entity);
							if ($urladvanced) {
								$return .= '<a href="'.$urladvanced.'">';
							} else {
								$return .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'" class="aphoto" target="_blank" rel="noopener noreferrer">';
							}
						}

						// Show image (width height=$maxHeight)
						// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
						$alt = $langs->transnoentitiesnoconv('File').': '.$relativefile;
						$alt .= ' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
						if ($overwritetitle) {
							if (is_numeric($overwritetitle)) {
								$alt = '';
							} else {
								$alt = $overwritetitle;
							}
						}

						if ($usesharelink) {
							if ($val['share']) {
								if (empty($maxHeight) || ($photo_vignette && $imgarray['height'] > $maxHeight)) {
									$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
									$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'"'.($maxHeight ?' height="'.$maxHeight.'"': '').' src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).($cache ? '&cache='.urlencode($cache) : '').'" title="'.dol_escape_htmltag($alt).'">';
								} else {
									$return .= '<!-- Show original file -->';
									$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).($cache ? '&cache='.urlencode($cache) : '').'" title="'.dol_escape_htmltag($alt).'">';
								}
							} else {
								$return .= '<!-- Show nophoto file (because file is not shared) -->';
								$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" title="'.dol_escape_htmltag($alt).'">';
							}
						} else {
							if (empty($maxHeight) || ($photo_vignette && $imgarray['height'] > $maxHeight)) {
								$return .= '<!-- Show thumb -->';
								$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').' maxwidth150onsmartphone maxwidth200"'.($maxHeight ?' height="'.$maxHeight.'"': '').' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.($cache ? '&cache='.urlencode($cache) : '').'&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.($cache ? '&cache='.urlencode($cache) : '').'&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
							}
						}

						if (empty($nolink)) {
							$return .= '</a>';
						}

						if ($showfilename) {
							$return .= '<br>'.$viewfilename;
						}
						if ($showaction) {
							$return .= '<br>';
							// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
							if ($photo_vignette && (image_format_supported($photo) > 0) && ($this->imgWidth > $maxWidth || $this->imgHeight > $maxHeight)) {
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=addthumb&token='.newToken().'&file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
							}
							// Special cas for product
							if ($modulepart == 'product' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
								// Link to resize
								$return .= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"), 'resize', '').'</a> &nbsp; ';

								// Link to delete
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=delete&token='.newToken().'&file='.urlencode($pdir.$viewfilename).'">';
								$return .= img_delete().'</a>';
							}
						}
						$return .= "\n";

						if ($nbbyrow > 0) {
							$return .= '</td>';
							if (($nbphoto % $nbbyrow) == 0) {
								$return .= '</tr>';
							}
						} elseif ($nbbyrow < 0) {
							$return .= '</div>'."\n";
						}
					}

					if (empty($size)) {     // Format origine
						$return .= '<img class="photo photowithmargin" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'">';

						if ($showfilename) {
							$return .= '<br>'.$viewfilename;
						}
						if ($showaction) {
							// Special case for product
							if ($modulepart == 'product' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
								// Link to resize
								$return .= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"), 'resize', '').'</a> &nbsp; ';

								// Link to delete
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=delete&token='.newToken().'&file='.urlencode($pdir.$viewfilename).'">';
								$return .= img_delete().'</a>';
							}
						}
					}

					// On continue ou on arrete de boucler ?
					if ($nbmax && $nbphoto >= $nbmax) {
						break;
					}
				}
			}

			if ($size == 1 || $size == 'small') {
				if ($nbbyrow > 0) {
					// Ferme tableau
					while ($nbphoto % $nbbyrow) {
						$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
						$nbphoto++;
					}

					if ($nbphoto) {
						$return .= '</table>';
					}
				}
			}
		}

		$this->nbphoto = $nbphoto;

		return $return;
	}
}
